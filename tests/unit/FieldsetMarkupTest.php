<?php

use PHPUnit\Framework\TestCase;

/**
 * No page writes its own form table any more.
 *
 * Every <table class="... tableclass_form ..."> that a page printed by hand has been moved
 * to module_form::generate_fieldset(). That matters beyond tidiness: a hand-written table
 * does not get the per-user field display settings, the role-based field hiding, the
 * dependency attributes or the responsive heading that a fieldset does, and it will not
 * pick up anything added to the renderers later. Each one was also, in practice, hiding
 * something - an empty cell nobody saw, markup commented out years ago, a dead layout
 * branch, a fatal.
 *
 * So this is an exact rule rather than a budget: no new one, whether or not it happens to
 * look harmless. A page that needs markup a fieldset cannot produce should get the option
 * added to BOTH renderers, the way title_fields, title_attributes, row_attributes,
 * row_class and row_style were.
 *
 * A source scan, because this has to be able to say "this file is wrong" about a page
 * nothing in the suite happens to visit.
 */
class FieldsetMarkupTest extends TestCase
{
	/**
	 * The renderers themselves, which is where the class legitimately appears - both as the
	 * default class and in the tag they emit.
	 */
	private const RENDERERS = array(
		'includes/plugin_form/form.php',
		'includes/plugin_theme/themes/metis/metis_functions.php',
	);

	/**
	 * The one <table> tag outside the renderers, with why.
	 *
	 * It holds no fields. module_form::generate_fieldset() emits a single <tbody> and has no
	 * option for a second, and includes/plugin_faq/faq.php answers a product change with
	 * $('#faq_product_area').html('<tr>...</tr>') - table rows, which need a row parent or
	 * the browser hoists them out. So the fieldset renders the product row and this empty
	 * table, directly below it and in the same class, is the target those rows land in.
	 */
	private const ALLOWED = array(
		'includes/plugin_faq/hooks/ticket_create.php' => 1,
	);

	public function testNoPagePrintsItsOwnFormTable(): void
	{
		$offenders = array();
		$root      = rtrim( _UCM_FOLDER, '/' ) . '/includes';
		$files     = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );

		foreach ( $files as $file ) {
			if ( $file->getExtension() !== 'php' ) {
				continue;
			}
			$relative = ltrim( str_replace( rtrim( _UCM_FOLDER, '/' ), '', $file->getPathname() ), '/' );
			if ( in_array( $relative, self::RENDERERS, true ) ) {
				continue;
			}
			$source = file_get_contents( $file->getPathname() );
			if ( $source === false ) {
				continue;
			}
			// An opening <table> tag whose class list carries tableclass_form. Passing that
			// class to a fieldset as its 'class' option is fine and is not matched.
			if ( preg_match_all( '#<table[^>]*tableclass_form#i', $source, $matches ) ) {
				$offenders[ $relative ] = count( $matches[0] );
			}
		}

		ksort( $offenders );
		$allowed = self::ALLOWED;
		ksort( $allowed );

		// An exact list, not a budget: a new hand-written table fails whether or not it looks
		// harmless, and so does an allowed one that grows a second table or disappears.
		$this->assertSame(
			$allowed,
			$offenders,
			"The <table class=\"tableclass_form\"> tags outside the two renderers are not the"
			. " ones this test expects.\nFound:\n" . json_encode( $offenders, JSON_PRETTY_PRINT )
			. "\nExpected:\n" . json_encode( $allowed, JSON_PRETTY_PRINT )
			. "\nIf the markup genuinely cannot come out of a fieldset, add the option to both"
			. " renderers rather than hand-writing the table - see"
			. " tests/unit/FieldsetRenderersTest.php. If you have removed one, delete its entry"
			. " from ALLOWED."
		);
	}
}
