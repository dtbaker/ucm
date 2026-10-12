<?php

use PHPUnit\Framework\TestCase;

/**
 * The form token is only as good as the next form somebody writes.
 *
 * module_form::print_form_open_tag() emits form_auth_key for you. A hand-written
 * <form> does not, so a hand-written form that posts a _process is an unprotected
 * write the moment the module guards it - and nothing about writing one looks wrong.
 *
 * This test reads the source and says so. It does two things:
 *
 *  1. Every raw <form> that submits something a module dispatches on must publish the
 *     token, or be named in $allowed below with a reason.
 *  2. The number of raw <form> tags may go down but not up. New forms go through the
 *     helper. This is what stops the problem growing back while the migration runs.
 *
 * It is a source scan, not a browser test, because it has to be able to say "this file
 * is wrong" about a page nothing in the suite happens to visit.
 */
class RawFormTest extends TestCase
{
	/**
	 * Request fields that reach a write handler. _process is the module dispatcher;
	 * the other three are read straight out of $_REQUEST by the pagination hooks.
	 *
	 * _process_login and _process_reset are deliberately NOT here - login forms are
	 * pre-session and have no token to publish. See the note in $allowed.
	 */
	private const WRITE_FIELDS = array(
		'_process',
		'bulk_action_go',
		'add_group_go',
		'import_export_go',
	);

	/**
	 * Raw forms that submit a write field and legitimately have no token.
	 *
	 * Adding an entry here is the whole point of the list: it is a visible, reviewable
	 * act with a reason attached, rather than a form that quietly has no token. A stale
	 * entry fails the test too, so this cannot rot.
	 */
	private const ALLOWED = array(
		'includes/plugin_ticket/public/ticket_customer_new.php'
			=> 'Public page on ext.php. Handled by external_hook(), which has no _process dispatch, and the visitor has no session to mint a token from.',
		'includes/plugin_ticket/public/ticket_customer_view.php'
			=> 'Same as ticket_customer_new.php.',
	);

	/**
	 * How many raw <form> tags the tree is allowed to contain.
	 *
	 * Lower this as forms move to module_form::print_form_open_tag(). Never raise it:
	 * a new form has a helper to go through.
	 */
	private const RAW_FORM_BUDGET = 155;

	public function test_a_raw_form_that_posts_a_write_field_publishes_the_token() {
		$offenders = array();
		foreach ( self::forms() as $form ) {
			if ( ! $form['writes'] || $form['token'] ) {
				continue;
			}
			if ( isset( self::ALLOWED[ $form['file'] ] ) ) {
				continue;
			}
			$offenders[] = $form['file'] . ':' . $form['line'] . ' posts ' . $form['writes'];
		}

		$this->assertSame( array(), $offenders, "\n"
			. "These raw <form> tags submit a field a write handler dispatches on, but do not\n"
			. "publish form_auth_key. Either open the form with module_form::print_form_open_tag(),\n"
			. "which emits the token for you, or add the file to RawFormTest::ALLOWED with a reason.\n"
		);
	}

	public function test_every_allowed_form_is_still_a_raw_form_without_a_token() {
		$still_matching = array();
		foreach ( self::forms() as $form ) {
			if ( $form['writes'] && ! $form['token'] ) {
				$still_matching[ $form['file'] ] = true;
			}
		}

		$stale = array_diff( array_keys( self::ALLOWED ), array_keys( $still_matching ) );

		$this->assertSame( array(), array_values( $stale ), "\n"
			. "These files are allowed to skip the form token but no longer need to be - they\n"
			. "either carry the token now or no longer post a write field. Remove them from\n"
			. "RawFormTest::ALLOWED so the list keeps meaning something.\n"
		);
	}

	public function test_the_number_of_raw_forms_does_not_grow() {
		$count = count( self::forms() );

		$this->assertLessThanOrEqual( self::RAW_FORM_BUDGET, $count, "\n"
			. "The tree has $count raw <form> tags, over the budget of " . self::RAW_FORM_BUDGET . ".\n"
			. "New forms go through module_form::print_form_open_tag() so the token, and anything\n"
			. "else that has to be on every form, is structural rather than remembered.\n"
		);

		// And if the migration has got ahead of the budget, say so, so the ratchet tightens.
		$this->assertGreaterThan( self::RAW_FORM_BUDGET - 10, $count, "\n"
			. "Only $count raw <form> tags left, well under the budget of " . self::RAW_FORM_BUDGET . ".\n"
			. "Lower RawFormTest::RAW_FORM_BUDGET to $count so the ratchet keeps holding.\n"
		);
	}

	public function test_the_scanner_finds_what_it_is_supposed_to_find() {
		// A scanner that silently matched nothing would make every test above pass.
		$forms = self::forms();
		$this->assertGreaterThan( 100, count( $forms ), 'the scan found almost nothing - the file walk is probably wrong' );

		$writes = array_filter( $forms, static fn( $f ) => (bool) $f['writes'] );
		$tokens = array_filter( $forms, static fn( $f ) => $f['token'] );
		$this->assertGreaterThan( 40, count( $writes ), 'no raw form appears to post a write field' );
		$this->assertGreaterThan( 40, count( $tokens ), 'no raw form appears to publish a token' );

		// And it has to be able to see a form that is wrong, not just ones that are right.
		$bad = self::scan( 'fixture.php', '<form action="" method="post">'
			. '<input type="hidden" name="_process" value="save_thing"></form>' );
		$this->assertCount( 1, $bad );
		$this->assertSame( '_process', $bad[0]['writes'] );
		$this->assertFalse( $bad[0]['token'] );

		$good = self::scan( 'fixture.php', '<form action="" method="post">'
			. '<input type="hidden" name="_process" value="save_thing">'
			. '<input type="hidden" name="form_auth_key" value="x"></form>' );
		$this->assertTrue( $good[0]['token'] );
	}

	/**
	 * Every raw <form> in the tree, with whether it submits a write field and whether it
	 * publishes a token.
	 *
	 * @return array<int, array{file: string, line: int, writes: string, token: bool}>
	 */
	private static function forms() {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();
		foreach ( self::source_files() as $file ) {
			$source = file_get_contents( _UCM_FOLDER . $file );
			if ( false === $source || false === strpos( $source, '<form' ) ) {
				continue;
			}
			foreach ( self::scan( $file, $source ) as $form ) {
				$cache[] = $form;
			}
		}

		return $cache;
	}

	/**
	 * Pull the raw <form> regions out of one file's source.
	 *
	 * A region runs from <form to whichever comes first of </form>, the next <form, or
	 * the end of the file. Templates open and close forms across includes often enough
	 * that insisting on a matched pair would just lose forms, and losing a form here
	 * means not checking it.
	 *
	 * @return array<int, array{file: string, line: int, writes: string, token: bool}>
	 */
	private static function scan( $file, $source ) {
		$found  = array();
		$offset = 0;
		while ( preg_match( '#<form\b#i', $source, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			$start = $match[0][1];

			$end = strlen( $source );
			if ( preg_match( '#</form#i', $source, $close, PREG_OFFSET_CAPTURE, $start + 1 ) ) {
				$end = $close[0][1];
			}
			if ( preg_match( '#<form\b#i', $source, $next, PREG_OFFSET_CAPTURE, $start + 1 ) ) {
				$end = min( $end, $next[0][1] );
			}

			$body   = substr( $source, $start, $end - $start );
			$writes = '';
			foreach ( self::WRITE_FIELDS as $field ) {
				// \\? so a form built inside a PHP string ( name=\"_process\" ) matches too.
				if ( preg_match( '#name\s*=\s*\\\\?["\']' . preg_quote( $field, '#' ) . '\\\\?["\']#i', $body ) ) {
					$writes = $field;
					break;
				}
			}

			$found[] = array(
				'file'   => $file,
				'line'   => substr_count( $source, "\n", 0, $start ) + 1,
				'writes' => $writes,
				'token'  => (bool) preg_match( '#form_auth_key|print_form_auth|print_form_open_tag#', $body ),
			);

			$offset = $start + 1;
		}

		return $found;
	}

	/**
	 * @return array<int, string> paths relative to _UCM_FOLDER
	 */
	private static function source_files() {
		$files = array( 'ext.php', 'index.php' );

		$directory = new RecursiveDirectoryIterator( _UCM_FOLDER . 'includes', FilesystemIterator::SKIP_DOTS );
		$iterator  = new RecursiveIteratorIterator( $directory );
		foreach ( $iterator as $file ) {
			if ( 'php' !== strtolower( $file->getExtension() ) ) {
				continue;
			}
			$path = str_replace( '\\', '/', substr( $file->getPathname(), strlen( _UCM_FOLDER ) ) );
			// Third-party code bundled inside a plugin is not ours to rewrite.
			if ( false !== strpos( $path, '/htmlpurifier/' ) || false !== strpos( $path, '/vendor/' ) ) {
				continue;
			}
			$files[] = $path;
		}

		sort( $files );

		return $files;
	}
}
