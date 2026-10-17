<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The release version, as the update server reads it.
 *
 * The server compared plugin versions and nothing else, so a release that changed only
 * core files - init.php, includes/functions.php - was offered to nobody. A client now
 * sends its _SCRIPT_VERSION as `release_version`, and the server offers the core rows when
 * its own is newer. A client that sends nothing must get exactly the answer it always got:
 * five thousand of those are how the update that teaches them this one arrives.
 *
 * Each test builds the answer the way upgrade_server.php does, from the same calls.
 */
class UpgradeReleaseVersionTest extends TestCase
{
	/** @var string Absolute path of the pretend server's installation, with trailing slash. */
	private $source;

	public static function setUpBeforeClass(): void {
		require_once _UCM_FOLDER . 'includes/plugin_config/class.upgrade_server.php';
	}

	protected function setUp(): void {
		$this->source = sys_get_temp_dir() . '/ucm-release-test-' . bin2hex( random_bytes( 6 ) ) . '/';
		foreach ( ucm_upgrade_server::CORE_FILES as $file ) {
			$this->write( $file, (string) file_get_contents( _UCM_FOLDER . $file ) );
		}
		$this->write( 'init.php', "<?php\n// The release version. Bump it with a line below.\ndefine( '_SCRIPT_VERSION', '4.0.1' );\n// 4.0.1 - 2026-11-02 - fixed the footer\n// 4.0.0 - 2026-10-17 - relaunch\n" );
	}

	protected function tearDown(): void {
		$this->remove( rtrim( $this->source, '/' ) );
	}

	private function remove( $path ) {
		if ( is_dir( $path ) && ! is_link( $path ) ) {
			foreach ( scandir( $path ) as $entry ) {
				if ( '.' !== $entry && '..' !== $entry ) {
					$this->remove( $path . '/' . $entry );
				}
			}
			rmdir( $path );
		} elseif ( file_exists( $path ) || is_link( $path ) ) {
			unlink( $path );
		}
	}

	private function write( $relative, $contents ) {
		$full = $this->source . $relative;
		if ( ! is_dir( dirname( $full ) ) ) {
			mkdir( dirname( $full ), 0777, true );
		}
		file_put_contents( $full, $contents );
	}

	/**
	 * The rows upgrade_server.php answers with, or null for its empty answer.
	 *
	 * @param array<string, array<string, mixed>> $plugin_rows    The plugins it is offering.
	 * @param mixed                               $release_field  The client's `release_version`, or null when it sent none.
	 */
	private function answer( array $plugin_rows, $release_field, $requested_plugin = false ) {
		$client_release = ucm_upgrade_server::client_release( $release_field );
		$server_release = '4.0.1';
		if ( ! ucm_upgrade_server::offers_core_files( (bool) $plugin_rows, $requested_plugin, $server_release, $client_release ) ) {
			return $plugin_rows ? $plugin_rows : null;
		}
		$recommended = (bool) $plugin_rows || ucm_upgrade_server::release_is_newer( $server_release, $client_release );
		$core_rows   = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, $recommended, false, 2 );
		$core_rows   = ucm_upgrade_server::label_release( $core_rows, $server_release, $client_release, (string) file_get_contents( $this->source . 'init.php' ) );

		return ucm_upgrade_server::install_order( $plugin_rows, $core_rows );
	}

	private function plugin_row( $name ) {
		return array( 'key' => $name, 'available_version' => 2.5, 'files' => array( 'includes/plugin_' . $name . '/' . $name . '.php' ), 'folders' => array() );
	}

	public function test_a_newer_release_with_no_plugin_bump_is_offered() {
		$rows = $this->answer( array(), '4.0.0' );

		$this->assertNotNull( $rows, 'a release that changed only core files must reach the client' );
		$this->assertSame( array( 'corefiles', 'database' ), array_keys( $rows ) );
		$this->assertContains( 'init.php', $rows['corefiles']['files'] );
		$this->assertTrue( $rows['corefiles']['recommended'] );
		$this->assertSame( '4.0.0', $rows['corefiles']['installed_version'] );
		$this->assertSame( '4.0.1', $rows['corefiles']['available_version'] );
		$this->assertSame( 'Core application files ( 4.0.1: fixed the footer (on 2026-11-02))', $rows['corefiles']['description'] );
	}

	/** @return array<string, array{string}> */
	public static function not_newer(): array {
		return array(
			'the same release'    => array( '4.0.1' ),
			'a newer client'      => array( '4.0.2' ),
			'a much newer client' => array( '5.0' ),
		);
	}

	#[DataProvider( 'not_newer' )]
	public function test_an_equal_or_newer_client_release_is_not_offered( $client ) {
		$this->assertNull( $this->answer( array(), $client ) );
	}

	public function test_versions_compare_as_versions_not_as_numbers() {
		// 4.0.10 is after 4.0.9. Compared as floats or strings, it is not.
		$this->assertTrue( ucm_upgrade_server::release_is_newer( '4.0.10', '4.0.9' ) );
		$this->assertFalse( ucm_upgrade_server::release_is_newer( '4.0.9', '4.0.10' ) );
		$this->assertTrue( ucm_upgrade_server::release_is_newer( '4.0.0', '3.555' ) );
	}

	public function test_an_old_client_with_no_release_gets_the_old_answer() {
		// Nothing for a client that sent no release and has every plugin: the empty answer.
		$this->assertNull( $this->answer( array(), null ) );

		// With a plugin on offer, the core rows ride along exactly as they always did:
		// plugin version numbers and the old wording, nothing about releases.
		$rows = $this->answer( array( 'faq' => $this->plugin_row( 'faq' ) ), null );
		$old  = ucm_upgrade_server::install_order(
			array( 'faq' => $this->plugin_row( 'faq' ) ),
			ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 2 )
		);
		$this->assertSame( $old, $rows );
		$this->assertSame( 'N/A', $rows['corefiles']['installed_version'] );
		$this->assertSame( 2.3, $rows['corefiles']['available_version'] );
		$this->assertSame( 'Core application files', $rows['corefiles']['description'] );

		// And asking for the core rows by name still works without a release.
		$this->assertSame( array( 'corefiles', 'database' ), array_keys( (array) $this->answer( array(), null, 'corefiles' ) ) );
	}

	/** @return array<string, array{mixed}> */
	public static function not_a_release(): array {
		return array(
			'empty'        => array( '' ),
			'an array'     => array( array( '4.0.0' ) ),
			'markup'       => array( '<b>4</b>' ),
			'a word'       => array( 'latest' ),
			'far too long' => array( str_repeat( '1.', 40 ) . '1' ),
		);
	}

	#[DataProvider( 'not_a_release' )]
	public function test_a_field_that_is_not_a_release_counts_as_none( $field ) {
		$this->assertSame( '', ucm_upgrade_server::client_release( $field ) );
		$this->assertNull( $this->answer( array(), $field ) );
	}

	public function test_a_plugin_update_shows_the_release_to_a_client_that_sent_one() {
		$rows = $this->answer( array( 'faq' => $this->plugin_row( 'faq' ) ), '4.0.1' );

		$this->assertSame( array( 'corefiles', 'database', 'faq' ), array_keys( $rows ) );
		$this->assertSame( '4.0.1', $rows['corefiles']['available_version'] );
		// The database row keeps the number the old client knows.
		$this->assertSame( 2.3, $rows['database']['available_version'] );
	}

	public function test_the_newest_changelog_line_under_the_release_is_used() {
		$init = "<?php\n// 1.0 - 2020-01-01 - a comment above the define is not the changelog\n"
			. "define( '_SCRIPT_VERSION', '4.0.2' );\n// 4.0.2 - 2026-12-01 - newest\n// 4.0.1 - 2026-11-02 - older\n";
		$this->assertSame( '4.0.2: newest (on 2026-12-01)', ucm_upgrade_server::release_changelog_line( $init ) );

		// The real init.php has one, and it is the release it declares.
		$real = (string) file_get_contents( _UCM_FOLDER . 'init.php' );
		preg_match( "#define\\(\\s*'_SCRIPT_VERSION',\\s*'([^']+)'#", $real, $declared );
		$this->assertStringStartsWith( $declared[1] . ': ', ucm_upgrade_server::release_changelog_line( $real ) );

		// No line, no description beyond the plain one.
		$this->assertSame( '', ucm_upgrade_server::release_changelog_line( "<?php\ndefine( '_SCRIPT_VERSION', '4.0.2' );\n" ) );
		$this->assertSame( '', ucm_upgrade_server::release_changelog_line( false ) );
	}
}
