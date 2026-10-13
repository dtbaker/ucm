<?php

use PHPUnit\Framework\TestCase;

/**
 * The form token is only as good as the next form somebody writes.
 *
 * module_form::print_form_open_tag() emits form_auth_key for you. A hand-written
 * <form> does not, so a hand-written form that posts a _process is an unprotected
 * write the moment the module guards it - and nothing about writing one looks wrong.
 *
 * The migration is finished: every form a page prints is opened by the helper. This test
 * reads the source and keeps it that way. It does two things:
 *
 *  1. Every raw <form> that submits something a module dispatches on must publish the
 *     token, or be named in ALLOWED below with a reason.
 *  2. The raw <form> tags that remain must be exactly the ones in RESIDUE - the three
 *     kinds that cannot go through a PHP helper. Not a budget: an exact list, so a new
 *     raw form fails whether or not it happens to post a write field.
 *
 * It is a source scan, not a browser test, because it has to be able to say "this file
 * is wrong" about a page nothing in the suite happens to visit.
 */
class RawFormTest extends TestCase
{
	/**
	 * Request fields that reach a write handler. _process is the module dispatcher; the
	 * next three are read straight out of $_REQUEST by the pagination hooks, the next two
	 * by the import page, which writes rows without going through _process at all, and the
	 * last by the public file page, which saves a comment the same way.
	 *
	 * _process_login and _process_reset are deliberately NOT here. Login forms go through
	 * the helper now and so do carry a token, but it is minted against the visitor's
	 * pre-login session and nothing checks it - requiring one would mean a login that
	 * breaks whenever the session turns over.
	 */
	private const WRITE_FIELDS = array(
		'_process',
		'bulk_action_go',
		'add_group_go',
		'import_export_go',
		'run_import',
		'upload_import',
		'save_file_comments',
	);

	/**
	 * Raw forms that submit a write field and legitimately have no token.
	 *
	 * Empty, and worth keeping that way. The two public ticket pages used to be here,
	 * on the grounds that an anonymous visitor has no session to mint a token from; they
	 * go through the helper now, which publishes one against whatever session the visitor
	 * does have. Nothing checks it on those pages, so it costs a hidden field and removes
	 * the exception.
	 *
	 * Adding an entry is a visible, reviewable act with a reason attached, rather than a
	 * form that quietly has no token. A stale entry fails the test too, so this cannot rot.
	 */
	private const ALLOWED = array();

	/**
	 * The raw <form> tags that are left, and why each one is still raw.
	 *
	 * Every form a page prints goes through module_form::print_form_open_tag(). What is
	 * left is the three kinds that cannot.
	 *
	 * This is an exact list rather than a budget, which catches more: a new raw form fails
	 * because the file is not listed, a second form in a listed file fails because the
	 * count is wrong, and a file that no longer needs to be here fails as stale.
	 */
	private const RESIDUE = array(
		// Default template bodies, seeded into the database by init_template() and edited
		// by the user afterwards. They are rows, not code - there is no PHP left to run by
		// the time one is rendered, only placeholder substitution. The payment ones post to
		// {PAYMENT_URL} and are filled in and shown to a customer who has no session.
		'includes/plugin_contract/contract.php'                  => 1,
		'includes/plugin_invoice/invoice.php'                    => 1,
		'includes/plugin_member/member.php'                      => 1,
		'includes/plugin_newsletter/newsletter.php'              => 1,
		'includes/plugin_paymethod_paypal/paymethod_paypal.php'  => 1,
		'includes/plugin_paymethod_stripe/paymethod_stripe.php'  => 1,
		'includes/plugin_quote/quote.php'                        => 1,

		// Third-party libraries bundled inside a plugin. Not ours to rewrite, same reason
		// htmlpurifier is skipped by the file walk.
		'includes/plugin_paymethod_authorize/anet_php_1.1.8/lib/AuthorizeNetDPM.php' => 1,
		'includes/plugin_paymethod_multisafepay/MultiSafepay.combined.php'           => 3,

		// The helper itself, which is where the one real <form> now comes from.
		'includes/plugin_form/form.php' => 1,
	);

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

	public function test_the_only_raw_forms_left_are_the_ones_that_cannot_be_converted() {
		$counts = array();
		foreach ( self::forms() as $form ) {
			$counts[ $form['file'] ] = ( $counts[ $form['file'] ] ?? 0 ) + 1;
		}
		ksort( $counts );
		$expected = self::RESIDUE;
		ksort( $expected );

		$this->assertSame( $expected, $counts, "\n"
			. "The set of raw <form> tags has changed.\n"
			. "A file here that is not in RawFormTest::RESIDUE is a form that should be opened\n"
			. "with module_form::print_form_open_tag(), which emits form_auth_key for you. If it\n"
			. "genuinely cannot be - a database template body, or third-party code - add it to\n"
			. "RESIDUE with the reason. A file in RESIDUE that no longer appears has been\n"
			. "converted: remove it, so the list keeps meaning something.\n"
		);
	}

	public function test_the_scanner_finds_what_it_is_supposed_to_find() {
		// A scanner that silently matched nothing would make every test above pass.
		$this->assertGreaterThan( 400, count( self::source_files() ), 'the file walk found almost nothing' );

		// The fixtures below are what prove the two detections work. They used to be backed
		// up by floors on the real tree ("at least 10 raw forms publish a token"), which only
		// held while the tree still had raw forms to count. It does not any more, so the
		// fixtures stand alone.
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
