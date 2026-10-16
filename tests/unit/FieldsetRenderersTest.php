<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The two fieldset renderers must not drift apart.
 *
 * module_form::generate_fieldset() is the renderer, and the metis theme replaces it
 * wholesale via hook_add( 'generate_fieldset', 'metris_generate_fieldset' ). metis is the
 * active theme, so metis's copy is what nearly every page actually renders - and anything
 * added to one has to be added to the other or it silently does nothing on the screens
 * people use.
 *
 * That has already happened three times:
 *
 *   - a 'style' option was added to module_form and never worked, because metis ignored it;
 *   - row_class and row_style had to be added twice, once per renderer;
 *   - metis's fieldset id line was missing the heading guard, so every fieldset with
 *     neither an id nor a heading got the same id back, read the `form` table under it and
 *     came out editable.
 *
 * This is a source scan rather than a render test because module_form needs the whole
 * application and a database to load; tests/unit/bootstrap.php deliberately loads neither.
 * It cannot check that the two produce the same markup - the Playwright suite does that -
 * only that neither has quietly stopped honouring something the other does.
 */
class FieldsetRenderersTest extends TestCase
{
	private const CORE  = 'includes/plugin_form/form.php';
	private const THEME = 'includes/plugin_theme/themes/metis/metis_functions.php';

	/** Things both renderers have to do, as a substring of each file. */
	public static function sharedBehaviour(): array
	{
		return array(
			'renders the data cell through the shared helper' => array(
				'render_fields( $element[\'fields\'] )',
			),
			'renders the header cell through the shared helper' => array(
				'render_fields( $element[\'title_fields\'] )',
			),
			'emits caller supplied header cell attributes' => array(
				'render_cell_attributes( $element[\'title_attributes\']',
			),
			'opens the header cell for a title or for title_fields' => array(
				'isset( $element[\'title\'] ) || isset( $element[\'title_fields\'] )',
			),
			'leaves the fieldset id false when there is no id and no heading' => array(
				'! empty( $options[\'heading\'] ) ? \'fieldset_\'',
			),
			'marks a fieldset with no id as not editable' => array(
				'! $fieldset_id || ( isset( $options[\'editable\'] ) && ! $options[\'editable\'] )',
			),
			'puts a caller supplied class on the row' => array(
				'$element[\'row_class\']',
			),
			'puts a caller supplied style on the row' => array(
				'$element[\'row_style\']',
			),
		);
	}

	#[DataProvider( 'sharedBehaviour' )]
	public function testBothRenderersDoIt( string $needle ): void
	{
		foreach ( array( self::CORE, self::THEME ) as $file ) {
			$source = file_get_contents( _UCM_FOLDER . $file );
			$this->assertIsString( $source, $file . ' could not be read' );
			$this->assertStringContainsString(
				$needle,
				$source,
				$file . " does not do this, so it will behave differently from the other renderer.\n"
				. 'Whatever was added to one of these two has to be added to both.'
			);
		}
	}

	/** The hook that makes the theme copy the one that matters. */
	public function testTheThemeStillReplacesTheCoreRenderer(): void
	{
		$source = file_get_contents( _UCM_FOLDER . self::THEME );
		$this->assertStringContainsString(
			"hook_add( 'generate_fieldset', 'metris_generate_fieldset' )",
			$source,
			'If metis no longer replaces generate_fieldset then this test is guarding nothing - '
			. 'check whether it should be deleted or pointed at whatever replaced it.'
		);
	}
}
