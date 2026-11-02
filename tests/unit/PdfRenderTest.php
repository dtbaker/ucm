<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_pdf/class.ucm_pdf.php';

/**
 * ucm_pdf renders HTML with the Composer copy of dompdf.
 *
 * These render real PDFs, so they would notice dompdf failing to load, its font folder not
 * being writable, or a CSS feature the invoice templates use being dropped in an upgrade.
 */
class PdfRenderTest extends TestCase
{
	/** @var string[] temp folders to remove */
	private $cleanup = array();

	public static function setUpBeforeClass(): void {
		ucm_libraries::register( 'dompdf', ucm_pdf::library() );
	}

	protected function tearDown(): void {
		foreach ( $this->cleanup as $dir ) {
			$this->remove_tree( $dir );
		}
		$this->cleanup = array();
	}

	private function invoice_html( $title = 'Invoice INV-0042', $body_extra = '' ) {
		return '<html><head><meta charset="utf-8"><title>' . $title . '</title><style>
				body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; }
				table { width: 100%; border-collapse: collapse; }
				th, td { border: 1px solid #999; padding: 4px; text-align: left; }
				.total { font-weight: bold; text-align: right; }
			</style></head><body>
			<h1>' . $title . '</h1>
			<p>Customer: Acme Pty Ltd</p>
			<table>
				<tr><th>Description</th><th>Qty</th><th>Amount</th></tr>
				<tr><td>Website design</td><td>1</td><td>$1,500.00</td></tr>
				<tr><td>Hosting</td><td>12</td><td>$240.00</td></tr>
			</table>
			<p class="total">Total due: $1,740.00</p>' . $body_extra . '
			</body></html>';
	}

	private function pdf_text( $pdf ) {
		$bin = trim( (string) shell_exec( 'command -v pdftotext 2>/dev/null' ) );
		if ( '' === $bin ) {
			return false;
		}
		$file = tempnam( sys_get_temp_dir(), 'ucmpdf' );
		file_put_contents( $file, $pdf );
		$text = shell_exec( escapeshellarg( $bin ) . ' -enc UTF-8 ' . escapeshellarg( $file ) . ' - 2>/dev/null' );
		unlink( $file );

		return (string) $text;
	}

	private function remove_tree( $dir ) {
		if ( is_link( $dir ) || is_file( $dir ) ) {
			@unlink( $dir );

			return;
		}
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( scandir( $dir ) as $entry ) {
			if ( '.' !== $entry && '..' !== $entry ) {
				$this->remove_tree( $dir . '/' . $entry );
			}
		}
		rmdir( $dir );
	}

	private function make_temp_dir() {
		$dir = sys_get_temp_dir() . '/ucm-pdf-test-' . bin2hex( random_bytes( 6 ) );
		mkdir( $dir, 0755, true );
		$this->cleanup[] = $dir;

		return realpath( $dir );
	}

	public function test_dompdf_comes_from_composer() {
		$this->assertTrue( ucm_libraries::have( 'dompdf' ) );
		$this->assertSame( 'composer', ucm_libraries::source( 'dompdf' ) );
		$this->assertSame( '', ucm_pdf::library()['bundled'], 'there is no bundled fallback any more' );
		$this->assertFileDoesNotExist( dirname( __DIR__, 2 ) . '/includes/plugin_pdf/dompdf' );
	}

	public function test_an_invoice_renders_to_a_pdf() {
		$pdf = ucm_pdf::render( $this->invoice_html() );

		$this->assertIsString( $pdf );
		$this->assertStringStartsWith( '%PDF-', $pdf );
		$this->assertStringContainsString( '%%EOF', substr( $pdf, -64 ) );
		$this->assertGreaterThan( 1000, strlen( $pdf ) );
	}

	public function test_the_pdf_carries_the_title_and_the_page_text() {
		$pdf = ucm_pdf::render( $this->invoice_html( 'Invoice INV-0042' ) );

		// The document title lands in the info dictionary, as plain text or UTF-16BE with a byte order mark.
		$this->assertTrue(
			false !== strpos( $pdf, 'Invoice INV-0042' ) || false !== strpos( $pdf, "\xFE\xFF" . mb_convert_encoding( 'Invoice INV-0042', 'UTF-16BE', 'UTF-8' ) ),
			'the HTML <title> should be in the PDF metadata'
		);

		$text = $this->pdf_text( $pdf );
		if ( false === $text ) {
			$this->markTestIncomplete( 'pdftotext is not installed, so the page text was not checked.' );
		}
		$this->assertStringContainsString( 'Invoice INV-0042', $text );
		$this->assertStringContainsString( 'Acme Pty Ltd', $text );
		$this->assertStringContainsString( 'Website design', $text );
		$this->assertStringContainsString( 'Total due: $1,740.00', $text );
	}

	public function test_non_ascii_text_is_kept() {
		// DejaVu Sans ships with dompdf and covers Latin Extended, Cyrillic, Greek and symbols.
		$sample = 'Zażółć gęślą jaźń — Привет мир — Ελληνικά — € £ ü ñ é';
		$html   = '<html><head><meta charset="utf-8"><style>body { font-family: "DejaVu Sans", sans-serif; }</style></head><body><p>' . $sample . '</p></body></html>';
		$pdf    = ucm_pdf::render( $html );

		$this->assertStringStartsWith( '%PDF-', $pdf );
		$this->assertStringContainsString( 'DejaVuSans', $pdf, 'the TrueType font should be embedded' );

		$text = $this->pdf_text( $pdf );
		if ( false === $text ) {
			$this->markTestIncomplete( 'pdftotext is not installed, so the extracted text was not checked.' );
		}
		$this->assertStringContainsString( 'Zażółć gęślą jaźń', $text );
		$this->assertStringContainsString( 'Привет мир', $text );
		$this->assertStringContainsString( '€ £ ü ñ é', $text );
	}

	public function test_latin_accents_and_currency_work_in_the_default_fonts() {
		$pdf  = ucm_pdf::render( '<html><head><meta charset="utf-8"></head><body style="font-family: Helvetica"><p>Café Müller — €99,50 · Zoë</p></body></html>' );
		$text = $this->pdf_text( $pdf );
		if ( false === $text ) {
			$this->markTestIncomplete( 'pdftotext is not installed.' );
		}
		$this->assertStringContainsString( 'Café Müller', $text );
		$this->assertStringContainsString( '€99,50', $text );
		$this->assertStringContainsString( 'Zoë', $text );
	}

	public function test_paper_size_is_honoured() {
		$a4     = ucm_pdf::render( $this->invoice_html(), 'A4' );
		$letter = ucm_pdf::render( $this->invoice_html(), 'Letter' );

		$this->assertMatchesRegularExpression( '#/MediaBox \[0\.0+ 0\.0+ 595\.280 841\.890\]#', $a4 );
		$this->assertMatchesRegularExpression( '#/MediaBox \[0\.0+ 0\.0+ 612(\.0+)? 792(\.0+)?\]#', $letter );
	}

	public function test_a_long_document_makes_more_than_one_page() {
		$rows = str_repeat( '<p>A line of the statement that takes up some room on the page.</p>', 120 );
		$pdf  = ucm_pdf::render( $this->invoice_html( 'Statement', $rows ) );

		$this->assertGreaterThan( 1, preg_match_all( '#/Type /Page\b#', $pdf ) );
	}

	public function test_a_page_break_and_landscape_css_still_work() {
		$html = '<html><head><style>@page { size: A4 landscape; }</style></head><body><p>one</p><div style="page-break-before: always;">two</div></body></html>';
		$pdf  = ucm_pdf::render( $html );

		$this->assertMatchesRegularExpression( '#/MediaBox \[0\.0+ 0\.0+ 841\.890 595\.280\]#', $pdf );
		$this->assertSame( 2, preg_match_all( '#/Type /Page\b#', $pdf ) );
	}

	public function test_an_embedded_image_is_drawn() {
		// A 1x1 PNG as a data: URI - how the stock templates carry their images.
		$png  = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';
		$with = ucm_pdf::render( $this->invoice_html( 'Logo', '<img src="' . $png . '" width="50" height="50">' ) );

		$this->assertStringContainsString( '/Subtype /Image', $with );
	}

	public function test_remote_fetching_php_and_javascript_are_off() {
		$options = ucm_pdf::options();

		$this->assertFalse( $options->getIsRemoteEnabled() );
		$this->assertFalse( $options->getIsPhpEnabled() );
		$this->assertFalse( $options->getIsJavascriptEnabled() );
		$this->assertArrayNotHasKey( 'http://', $options->getAllowedProtocols() );
		$this->assertArrayNotHasKey( 'https://', $options->getAllowedProtocols() );
		$this->assertArrayNotHasKey( 'phar://', $options->getAllowedProtocols() );
	}

	public function test_local_files_are_limited_to_the_install_folder() {
		$options = ucm_pdf::options();
		$inside  = dirname( __DIR__, 2 ) . '/composer.json';

		$this->assertTrue( $options->validateLocalUri( $inside )[0] );
		$this->assertFalse( $options->validateLocalUri( '/etc/hosts' )[0] );
		$this->assertContains( realpath( dirname( __DIR__, 2 ) ), $options->getChroot() );
	}

	public function test_a_local_image_outside_the_chroot_is_not_embedded() {
		$dir = $this->make_temp_dir();
		file_put_contents( $dir . '/secret.png', base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$pdf = ucm_pdf::render( $this->invoice_html( 'Outside', '<img src="file://' . $dir . '/secret.png" width="50" height="50">' ) );

		$this->assertStringStartsWith( '%PDF-', $pdf );
		$this->assertStringNotContainsString( '/Subtype /Image', $pdf );
	}

	public function test_the_work_folder_is_writable_and_outside_vendor() {
		$dir = ucm_pdf::work_dir();

		$this->assertNotFalse( $dir );
		$this->assertDirectoryIsWritable( $dir );
		$this->assertStringNotContainsString( '/vendor/', $dir . '/' );
		$options = ucm_pdf::options();
		$this->assertSame( $dir, $options->getFontCache() );
		$this->assertSame( $dir, $options->getTempDir() );
	}

	// -- removing the old bundled copy --------------------------------------------------

	private function fake_install() {
		$root = $this->make_temp_dir();
		mkdir( $root . '/includes/plugin_pdf/dompdf/lib/fonts', 0755, true );
		file_put_contents( $root . '/includes/plugin_pdf/dompdf/autoload.inc.php', '<?php // old' );
		file_put_contents( $root . '/includes/plugin_pdf/dompdf/lib/fonts/Helvetica.afm', 'metrics' );
		file_put_contents( $root . '/includes/plugin_pdf/dompdf/lib/fonts/.hidden', 'x' );
		file_put_contents( $root . '/includes/plugin_pdf/pdf.php', '<?php // plugin' );
		mkdir( $root . '/includes/plugin_pdf/pages', 0755, true );
		file_put_contents( $root . '/includes/plugin_pdf/pages/pdf_settings.php', '<?php' );

		return $root;
	}

	public function test_the_old_bundled_dompdf_is_removed_and_the_rest_of_the_plugin_stays() {
		$root = $this->fake_install();

		$this->assertTrue( ucm_pdf::remove_bundled_dompdf( $root . '/' ) );
		$this->assertDirectoryDoesNotExist( $root . '/includes/plugin_pdf/dompdf' );
		$this->assertFileExists( $root . '/includes/plugin_pdf/pdf.php' );
		$this->assertFileExists( $root . '/includes/plugin_pdf/pages/pdf_settings.php' );
	}

	public function test_removing_when_there_is_nothing_to_remove_is_fine() {
		$root = $this->make_temp_dir();
		mkdir( $root . '/includes/plugin_pdf', 0755, true );

		$this->assertTrue( ucm_pdf::remove_bundled_dompdf( $root . '/' ) );
		$this->assertTrue( ucm_pdf::remove_bundled_dompdf( $root . '/' ), 'a second run is harmless' );
	}

	public function test_removing_with_no_plugin_folder_does_nothing() {
		$root = $this->make_temp_dir();

		$this->assertFalse( ucm_pdf::remove_bundled_dompdf( $root . '/' ) );
	}

	public function test_links_inside_the_old_folder_are_removed_not_followed() {
		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink() is not available' );
		}
		$root    = $this->fake_install();
		$outside = $this->make_temp_dir();
		file_put_contents( $outside . '/keep.txt', 'must survive' );
		symlink( $outside, $root . '/includes/plugin_pdf/dompdf/lib/linked' );
		symlink( $outside . '/keep.txt', $root . '/includes/plugin_pdf/dompdf/lib/linked_file' );

		$this->assertTrue( ucm_pdf::remove_bundled_dompdf( $root . '/' ) );
		$this->assertDirectoryDoesNotExist( $root . '/includes/plugin_pdf/dompdf' );
		$this->assertFileExists( $outside . '/keep.txt' );
		$this->assertSame( 'must survive', file_get_contents( $outside . '/keep.txt' ) );
	}

	public function test_a_link_in_place_of_the_folder_is_left_alone() {
		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink() is not available' );
		}
		$root    = $this->make_temp_dir();
		$outside = $this->make_temp_dir();
		mkdir( $root . '/includes/plugin_pdf', 0755, true );
		file_put_contents( $outside . '/keep.txt', 'must survive' );
		symlink( $outside, $root . '/includes/plugin_pdf/dompdf' );

		$this->assertFalse( ucm_pdf::remove_bundled_dompdf( $root . '/' ) );
		$this->assertFileExists( $outside . '/keep.txt' );
	}

	public function test_a_plugin_folder_that_is_a_link_cannot_be_used_to_escape() {
		if ( ! function_exists( 'symlink' ) ) {
			$this->markTestSkipped( 'symlink() is not available' );
		}
		// includes/plugin_pdf -> somewhere else that happens to contain a dompdf folder.
		$root    = $this->make_temp_dir();
		$outside = $this->make_temp_dir();
		mkdir( $outside . '/dompdf', 0755, true );
		file_put_contents( $outside . '/dompdf/keep.txt', 'not ours' );
		mkdir( $root . '/includes', 0755, true );
		symlink( $outside, $root . '/includes/plugin_pdf' );

		ucm_pdf::remove_bundled_dompdf( $root . '/' );
		$this->assertFileExists( $outside . '/dompdf/keep.txt' );
	}
}
