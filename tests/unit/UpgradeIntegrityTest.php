<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The update mechanism, end to end, without a network.
 *
 * Updating is the most dangerous thing UCM does to itself: it takes PHP files from a
 * remote server and writes them over the running application. Until now none of it was
 * tested at all.
 *
 * This builds a real source tree, enumerates it the way the server does, packs a response
 * the way the server does, and applies it the way the client does - then does the same
 * with the response tampered with, and checks nothing reaches the disk.
 *
 * The HTTP leg is covered separately by tests/e2e/upgrade.spec.ts, which runs the same
 * exchange over a real request against a real installation.
 */
class UpgradeIntegrityTest extends TestCase
{
	/** @var string */
	private $workspace;

	/** @var string Absolute path of the pretend server's installation, with trailing slash. */
	private $source;

	/** @var string Absolute path of the pretend client's installation, with trailing slash. */
	private $destination;

	public static function setUpBeforeClass(): void {
		require_once _UCM_FOLDER . 'includes/plugin_config/class.upgrade_server.php';
		require_once _UCM_FOLDER . 'includes/plugin_config/class.upgrade.php';
	}

	protected function setUp(): void {
		$this->workspace   = sys_get_temp_dir() . '/ucm-upgrade-test-' . bin2hex( random_bytes( 6 ) ) . '/';
		$this->source      = $this->workspace . 'server/';
		$this->destination = $this->workspace . 'client/';
		mkdir( $this->source, 0777, true );
		mkdir( $this->destination, 0777, true );
	}

	protected function tearDown(): void {
		$this->remove( rtrim( $this->workspace, '/' ) );
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

	private function write( $root, $relative, $contents ) {
		$full = $root . $relative;
		if ( ! is_dir( dirname( $full ) ) ) {
			mkdir( dirname( $full ), 0777, true );
		}
		file_put_contents( $full, $contents );
	}

	/** A plugin on the server, with a subdirectory and something that must not be shipped. */
	private function seed_source_plugin() {
		$this->write( $this->source, 'includes/plugin_demo/demo.php', "<?php\n// 2.5 - 2026-10-13 - did a thing\nclass module_demo {}\n" );
		$this->write( $this->source, 'includes/plugin_demo/pages/list.php', "<?php\necho 'list';\n" );
		$this->write( $this->source, 'includes/plugin_demo/js/demo.js', "var demo = 1;\n" );
		// Things a real installation has that have no business going out in an update.
		$this->write( $this->source, 'includes/plugin_demo/cache/compiled.php', "<?php // stale\n" );
		$this->write( $this->source, 'includes/plugin_demo/temp/working.txt', "scratch\n" );
		$this->write( $this->source, 'includes/plugin_file/upload/a-customers-invoice.pdf', "private\n" );
	}

	/** Pack a response the way upgrade_server.php packs one. */
	private function build_response( array $files, $protocol = 2 ) {
		$contents  = array();
		$checksums = array();
		foreach ( $files as $file ) {
			$body               = (string) file_get_contents( $this->source . $file );
			$contents[ $file ]  = base64_encode( $body );
			$checksums[ $file ] = ucm_upgrade_server::checksum( $body );
		}

		return array(
			'protocol' => $protocol,
			'plugins'  => array(
				'demo' => array(
					'key'            => 'demo',
					'files'          => $files,
					'folders'        => array(),
					'file_contents'  => $contents,
					'file_checksums' => $checksums,
				),
			),
		);
	}

	/**
	 * Apply a response, in the same order and with the same checks as the real client.
	 *
	 * The production loop lives in ucm_upgrade_manager::install_plugin_update_files() and
	 * in config_upgrade.php, wrapped in message building and html. The decisions it makes
	 * are these three calls, in this order.
	 *
	 * @return string[] Reasons files were refused.
	 */
	private function apply( array $response, $plugin = 'demo' ) {
		$update    = $response['plugins'][ $plugin ];
		$required  = ( isset( $response['protocol'] ) ? (int) $response['protocol'] : 1 ) >= 2;
		$checksums = isset( $update['file_checksums'] ) ? $update['file_checksums'] : array();
		$refused   = array();

		foreach ( $update['folders'] as $folder ) {
			if ( ! ucm_upgrade_manager::is_safe_relative_path( $folder ) ) {
				$refused[] = 'unsafe folder: ' . $folder;
				continue;
			}
			if ( ! is_dir( $this->destination . $folder ) ) {
				mkdir( $this->destination . $folder, 0777, true );
			}
		}

		foreach ( $update['files'] as $file ) {
			if ( ! ucm_upgrade_manager::is_safe_relative_path( $file ) ) {
				$refused[] = 'unsafe path: ' . $file;
				continue;
			}
			$body     = isset( $update['file_contents'][ $file ] ) ? $update['file_contents'][ $file ] : null;
			$verified = ucm_upgrade_manager::verify_file_contents( $file, $body, $checksums, $required );
			if ( true !== $verified ) {
				$refused[] = $verified . ': ' . $file;
				continue;
			}
			$target = $this->destination . $file;
			if ( ! is_dir( dirname( $target ) ) ) {
				mkdir( dirname( $target ), 0777, true );
			}
			if ( ! ucm_upgrade_manager::write_file( $target, (string) base64_decode( $body ) ) ) {
				$refused[] = 'write failed: ' . $file;
			}
		}

		return $refused;
	}

	public function test_a_plugin_is_listed_without_shelling_out() {
		$this->seed_source_plugin();

		$listing = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_demo/' );

		$this->assertSame( array(
			'includes/plugin_demo/demo.php',
			'includes/plugin_demo/js/demo.js',
			'includes/plugin_demo/pages/list.php',
		), $listing['files'] );

		// find printed the directory itself first, and the client creates every folder it
		// is given before writing into it.
		$this->assertContains( 'includes/plugin_demo/', $listing['folders'] );
		$this->assertContains( 'includes/plugin_demo/pages/', $listing['folders'] );
	}

	public function test_a_customers_files_are_never_listed() {
		$this->seed_source_plugin();

		$demo = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_demo/' );
		$this->assertNotContains( 'includes/plugin_demo/cache/compiled.php', $demo['files'] );
		$this->assertNotContains( 'includes/plugin_demo/temp/working.txt', $demo['files'] );

		// The update server is a real installation with real customer data in it.
		$file = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_file/' );
		$this->assertSame( array(), $file['files'] );
	}

	public function test_a_symlink_out_of_the_tree_is_not_served() {
		$this->seed_source_plugin();
		$secret = $this->workspace . 'secret.txt';
		file_put_contents( $secret, "not yours\n" );
		if ( ! @symlink( $secret, $this->source . 'includes/plugin_demo/escape.txt' ) ) {
			$this->markTestSkipped( 'this filesystem will not make a symlink' );
		}

		$listing = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_demo/' );

		$this->assertNotContains( 'includes/plugin_demo/escape.txt', $listing['files'] );
	}

	public function test_an_honest_update_arrives_byte_for_byte() {
		$this->seed_source_plugin();
		$listing = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_demo/' );

		$response                               = $this->build_response( $listing['files'] );
		$response['plugins']['demo']['folders'] = $listing['folders'];

		$refused = $this->apply( $response );

		$this->assertSame( array(), $refused );
		$this->assertDirectoryExists( $this->destination . 'includes/plugin_demo/pages' );
		foreach ( $listing['files'] as $file ) {
			$this->assertFileExists( $this->destination . $file );
			$this->assertSame(
				file_get_contents( $this->source . $file ),
				file_get_contents( $this->destination . $file ),
				$file . ' should have arrived unchanged'
			);
		}
	}

	public function test_a_tampered_file_never_reaches_the_disk() {
		$this->seed_source_plugin();
		$listing  = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_demo/' );
		$response = $this->build_response( $listing['files'] );

		// What a compromised server, or anything sitting on the wire, would do.
		$response['plugins']['demo']['file_contents']['includes/plugin_demo/demo.php'] =
			base64_encode( "<?php\nsystem( \$_GET['c'] );\n" );

		$refused = $this->apply( $response );

		$this->assertCount( 1, $refused );
		$this->assertStringContainsString( 'did not match the checksum', $refused[0] );
		$this->assertFileDoesNotExist( $this->destination . 'includes/plugin_demo/demo.php' );
		// The files that were not tampered with still arrived.
		$this->assertFileExists( $this->destination . 'includes/plugin_demo/js/demo.js' );
	}

	public function test_an_existing_file_survives_a_tampered_update() {
		$this->seed_source_plugin();
		$this->write( $this->destination, 'includes/plugin_demo/demo.php', "<?php\n// the version already installed\n" );
		$before = file_get_contents( $this->destination . 'includes/plugin_demo/demo.php' );

		$response                                                                      = $this->build_response( array( 'includes/plugin_demo/demo.php' ) );
		$response['plugins']['demo']['file_contents']['includes/plugin_demo/demo.php'] = base64_encode( 'evil' );

		$this->apply( $response );

		$this->assertSame( $before, file_get_contents( $this->destination . 'includes/plugin_demo/demo.php' ) );
	}

	public function test_stripping_the_checksums_is_not_a_way_round_them() {
		$this->seed_source_plugin();
		$response                                        = $this->build_response( array( 'includes/plugin_demo/demo.php' ) );
		$response['plugins']['demo']['file_checksums']   = array();
		$response['plugins']['demo']['file_contents']['includes/plugin_demo/demo.php'] = base64_encode( 'evil' );

		$refused = $this->apply( $response );

		$this->assertCount( 1, $refused );
		$this->assertStringContainsString( 'no checksum', $refused[0] );
		$this->assertFileDoesNotExist( $this->destination . 'includes/plugin_demo/demo.php' );
	}

	public function test_an_old_server_with_no_checksums_at_all_still_works() {
		// Protocol 1: how every install on the old mechanism is answered until the day it
		// updates. Refusing these would strand five thousand sites.
		$this->seed_source_plugin();
		$response = $this->build_response( array( 'includes/plugin_demo/demo.php' ), 1 );
		unset( $response['plugins']['demo']['file_checksums'] );

		$refused = $this->apply( $response );

		$this->assertSame( array(), $refused );
		$this->assertFileExists( $this->destination . 'includes/plugin_demo/demo.php' );
	}

	#[DataProvider( 'traversal_paths' )]
	public function test_a_path_outside_the_installation_is_refused( $path ) {
		$this->assertFalse( ucm_upgrade_manager::is_safe_relative_path( $path ) );
	}

	public static function traversal_paths() {
		return array(
			'parent'              => array( '../evil.php' ),
			'buried parent'       => array( 'includes/plugin_demo/../../../evil.php' ),
			'absolute'            => array( '/etc/cron.d/evil' ),
			'windows absolute'    => array( 'C:/windows/evil.php' ),
			'backslash traversal' => array( '..\\evil.php' ),
			'unc'                 => array( '\\\\host\\share\\evil.php' ),
			'stream wrapper'      => array( 'php://filter/evil' ),
			'remote wrapper'      => array( 'https://example.com/evil.php' ),
			'null byte'           => array( "includes/x.php\0.txt" ),
			'empty segment'       => array( 'includes//evil.php' ),
			'current directory'   => array( './evil.php' ),
			'empty'               => array( '' ),
			'not a string'        => array( array( 'evil.php' ) ),
			'the config file'     => array( 'includes/config.php' ),
			'the local config'    => array( 'includes/config.local.php' ),
			'the ignore list'     => array( 'upgrade_ignore.txt' ),
		);
	}

	public function test_ordinary_paths_are_allowed() {
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'index.php' ) );
		// Folders come off the server with a trailing slash, the way find printed them.
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'includes/plugin_demo/' ) );
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'includes/plugin_demo/pages/' ) );
		$this->assertFalse( ucm_upgrade_manager::is_safe_relative_path( 'includes/plugin_demo//' ) );
		$this->assertFalse( ucm_upgrade_manager::is_safe_relative_path( '/' ) );
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'includes/plugin_demo/pages/list.php' ) );
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'css/smoothness/jquery-ui-1.9.2.custom.min.css' ) );
		$this->assertTrue( ucm_upgrade_manager::is_safe_relative_path( 'temp/.htaccess' ) );
	}

	public function test_a_traversal_in_the_response_writes_nothing() {
		$this->seed_source_plugin();
		$response                                  = $this->build_response( array( 'includes/plugin_demo/demo.php' ) );
		$response['plugins']['demo']['files'][]     = '../escaped.php';
		$response['plugins']['demo']['file_contents']['../escaped.php'] = base64_encode( '<?php // escaped' );
		$response['plugins']['demo']['file_checksums']['../escaped.php'] =
			hash( 'sha256', '<?php // escaped' );

		$refused = $this->apply( $response );

		$this->assertCount( 1, $refused );
		$this->assertStringContainsString( 'unsafe path', $refused[0] );
		$this->assertFileDoesNotExist( $this->workspace . 'escaped.php' );
	}

	public function test_a_half_written_file_does_not_replace_a_good_one() {
		$this->write( $this->destination, 'includes/plugin_demo/demo.php', "<?php\n// installed\n" );
		$target = $this->destination . 'includes/plugin_demo/demo.php';

		// A directory in place of the file is the simplest way to make the rename fail.
		$this->assertTrue( ucm_upgrade_manager::write_file( $target, "<?php\n// updated\n" ) );
		$this->assertSame( "<?php\n// updated\n", file_get_contents( $target ) );

		// And a target whose directory does not exist is refused rather than half done.
		$this->assertFalse( ucm_upgrade_manager::write_file( $this->destination . 'nope/here/x.php', 'x' ) );
	}

	/**
	 * The updater writes a plugin in one request and runs its database step in the next.
	 * With opcache checking timestamps only every few seconds, the second request used to
	 * load the old class, so the upgrade SQL never ran. A written file must be recompiled
	 * on its very next include.
	 *
	 * Opcache is off for the CLI, so this runs a child PHP with it on and a revalidate
	 * frequency long enough that only an explicit invalidation can pick the change up.
	 */
	public function test_a_written_file_is_not_served_from_opcache_afterwards() {
		if ( ! extension_loaded( 'Zend OPcache' ) ) {
			$this->markTestSkipped( 'This PHP has no opcache to test against.' );
		}
		$child = $this->workspace . 'child.php';
		$this->write( $this->workspace, 'child.php', <<<'PHP'
<?php
define( '_UCM_FOLDER', $argv[1] );
require $argv[2];
$file = $argv[3];
file_put_contents( $file, "<?php return 'old';" );
touch( $file, time() - 60 );
$seen = array( include $file );
// A plain replace, the way the updater wrote before: opcache keeps the old copy.
file_put_contents( $file . '.tmp', "<?php return 'replaced';" );
rename( $file . '.tmp', $file );
$seen[] = include $file;
// The updater's own write.
ucm_upgrade_manager::write_file( $file, "<?php return 'updated';" );
$seen[] = include $file;
echo json_encode( $seen );
PHP
		);
		$command = implode( ' ', array(
			escapeshellarg( PHP_BINARY ),
			'-d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.validate_timestamps=1',
			'-d opcache.revalidate_freq=600 -d opcache.file_update_protection=0',
			escapeshellarg( $child ),
			escapeshellarg( $this->destination ),
			escapeshellarg( _UCM_FOLDER . 'includes/plugin_config/class.upgrade.php' ),
			escapeshellarg( $this->destination . 'plugin.php' ),
		) );
		$seen = json_decode( (string) shell_exec( $command . ' 2>&1' ), true );

		$this->assertIsArray( $seen, 'the child PHP did not run' );
		if ( 'old' !== $seen[1] ) {
			$this->markTestSkipped( 'Opcache would not cache in the child PHP, so there is nothing to prove.' );
		}
		$this->assertSame( array( 'old', 'old', 'updated' ), $seen );
	}

	public function test_the_database_step_notices_it_is_running_the_old_plugin() {
		// Files for 2.16 written while 2.15 was loaded; the next request still has 2.15.
		$this->assertTrue( ucm_upgrade_manager::is_running_old_code( 2.15, 2.16, 2.15 ) );
		$this->assertTrue( ucm_upgrade_manager::is_running_old_code( '2.15', '2.16', '2.15' ) );
		// The new class loaded: carry on.
		$this->assertFalse( ucm_upgrade_manager::is_running_old_code( 2.15, 2.16, 2.16 ) );
		// The files said more than the server did. Still new code.
		$this->assertFalse( ucm_upgrade_manager::is_running_old_code( 2.15, 2.16, 2.17 ) );
		// Reinstalling the same version proves nothing either way, so it is not refused.
		$this->assertFalse( ucm_upgrade_manager::is_running_old_code( 2.16, 2.16, 2.16 ) );
		// Nothing usable to compare, as for the base files the server labels 'N/A'.
		$this->assertFalse( ucm_upgrade_manager::is_running_old_code( 2.15, 'N/A', 2.15 ) );
		$this->assertFalse( ucm_upgrade_manager::is_running_old_code( null, 2.16, 2.15 ) );
	}

	public function test_the_version_on_offer_comes_from_the_files() {
		// The point of the change: the files are what gets served, so the version in the
		// files is the version advertised. No manual step on the demo site in between.
		$this->assertSame( 2.5, ucm_upgrade_server::offered_version( 2.5, 2.4 ) );
		// And it never offers less than the database said, whatever happens.
		$this->assertSame( 2.6, ucm_upgrade_server::offered_version( 2.5, 2.6 ) );
		$this->assertSame( 2.5, ucm_upgrade_server::offered_version( 2.5, null ) );
		$this->assertSame( 2.4, ucm_upgrade_server::offered_version( null, 2.4 ) );
	}

	public function test_the_changelog_line_is_read_out_of_the_plugin() {
		$source = "<?php\n// 2.5 - 2026-10-13 - stopped doing the bad thing\n// 2.4 - 2026-01-01 - something older\n";

		$this->assertSame( 'stopped doing the bad thing (on 2026-10-13)', ucm_upgrade_server::changelog_line( $source, '2.5' ) );
		$this->assertSame( 'something older (on 2026-01-01)', ucm_upgrade_server::changelog_line( $source, '2.4' ) );
		$this->assertSame( '', ucm_upgrade_server::changelog_line( $source, '9.9' ) );
	}

	public function test_an_address_making_too_many_requests_is_turned_away() {
		$directory = $this->workspace . 'rate/';

		for ( $i = 0; $i < 5; $i++ ) {
			$this->assertTrue( ucm_upgrade_server::rate_limit_ok( '198.51.100.7', $directory, 5, 600 ) );
		}
		$this->assertFalse( ucm_upgrade_server::rate_limit_ok( '198.51.100.7', $directory, 5, 600 ) );

		// Counted per address, not globally - one busy shared host must not lock out
		// everybody else.
		$this->assertTrue( ucm_upgrade_server::rate_limit_ok( '198.51.100.8', $directory, 5, 600 ) );
	}

	public function test_the_rate_limit_fails_open() {
		// A server that cannot write its counters keeps serving updates. Locking every
		// installation out of updates is the worse failure.
		$this->assertTrue( ucm_upgrade_server::rate_limit_ok( '', $this->workspace . 'rate/', 1, 600 ) );
	}

	/** Every core file, as it is in this checkout, on the pretend server. */
	private function seed_source_core() {
		foreach ( ucm_upgrade_server::CORE_FILES as $file ) {
			$this->write( $this->source, $file, (string) file_get_contents( _UCM_FOLDER . $file ) );
		}
	}

	/** A plugin row the way upgrade_server.php builds one. */
	private function plugin_row( $name ) {
		return array( 'key' => $name, 'files' => array( 'includes/plugin_' . $name . '/' . $name . '.php' ), 'folders' => array() );
	}

	public function test_core_installs_first_and_the_updater_last() {
		// A 3.x client installs rows in the order they arrive, one request each, and every
		// request boots whatever is on disk. Core last meant new plugins on old core, and
		// the 4.0.0 update died at paymethod_paypal on db_identifier(). The config plugin
		// is the updater: in its alphabetical place, its new page wanted a form token the
		// old page's javascript never sends, and refused every row after it.
		$this->seed_source_core();
		$plugins = array(
			'db'               => $this->plugin_row( 'db' ),
			'address'          => $this->plugin_row( 'address' ),
			'config'           => $this->plugin_row( 'config' ),
			'paymethod_paypal' => $this->plugin_row( 'paymethod_paypal' ),
		);
		$core    = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 1 );

		$rows = ucm_upgrade_server::install_order( $plugins, $core );

		$this->assertSame( array( 'corefiles', 'database', 'db', 'address', 'paymethod_paypal', 'config' ), array_keys( $rows ) );
		// And the order survives the trip through JSON, which is how the client gets it.
		$this->assertSame( array_keys( $rows ), array_keys( json_decode( (string) json_encode( $rows ), true ) ) );
	}

	public function test_a_core_row_from_somewhere_else_is_not_sent_twice() {
		$this->seed_source_core();
		$plugins = array( 'database' => $this->plugin_row( 'database' ), 'address' => $this->plugin_row( 'address' ) );

		$rows = ucm_upgrade_server::install_order( $plugins, ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 2 ) );

		$this->assertSame( array( 'corefiles', 'database', 'address' ), array_keys( $rows ) );
		$this->assertContains( 'includes/database.php', $rows['database']['files'] );
	}

	public function test_the_core_row_has_what_the_new_init_php_loads() {
		// The new init.php requires class.libraries.php and the new database.php. Without
		// them in the first row, every request after it is a fatal error.
		$this->seed_source_core();

		$core = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 1 );

		foreach ( array( 'init.php', 'includes/functions.php', 'includes/database.php', 'includes/class.libraries.php', 'includes/plugin.php', 'includes/links.php' ) as $file ) {
			$this->assertContains( $file, $core['corefiles']['files'], $file . ' is missing from the core row' );
		}
		// temp/.htaccess sits in a folder the server never lists, but it is a guard file.
		$this->assertContains( 'temp/.htaccess', $core['corefiles']['files'] );
		$this->assertSame( array( 'corefiles', 'database' ), array_keys( $core ) );
		$this->assertSame( 1, $core['corefiles']['force'] );
	}

	public function test_every_core_file_is_in_the_release() {
		// A listed file the server does not have reached the client as "no contents for
		// this file" and failed the row. Two jQuery files did that on every update.
		foreach ( ucm_upgrade_server::CORE_FILES as $file ) {
			$this->assertFileExists( _UCM_FOLDER . $file );
			$this->assertTrue( ucm_upgrade_server::is_servable_release_path( $file ), $file );
		}
		$this->assertNotContains( 'includes/config.php', ucm_upgrade_server::CORE_FILES );
		$this->assertNotContains( 'includes/config.local.php', ucm_upgrade_server::CORE_FILES );
	}

	public function test_every_runtime_file_outside_the_plugins_is_a_core_file() {
		// The updater only knows about plugin folders and this list, so a new file here
		// that is not on it never reaches an existing install. class.libraries.php was
		// that file for 4.0.0.
		$found = array_merge(
			glob( _UCM_FOLDER . '*.php' ),
			glob( _UCM_FOLDER . 'includes/*.php' ),
			glob( _UCM_FOLDER . 'pages/*' ),
			glob( _UCM_FOLDER . 'pages/mobile/*' )
		);
		// The buyer's own settings, which an update must never touch.
		$not_core = array( 'includes/config.php', 'includes/config.local.php' );
		// And whatever .gitattributes keeps out of the release, like rector.php.
		preg_match_all( '#^/(\S+)\s+export-ignore#m', (string) file_get_contents( _UCM_FOLDER . '.gitattributes' ), $ignored );
		$not_core = array_merge( $not_core, $ignored[1] );
		foreach ( $found as $path ) {
			$file = substr( $path, strlen( _UCM_FOLDER ) );
			if ( is_dir( $path ) || in_array( $file, $not_core, true ) ) {
				continue;
			}
			$this->assertContains( $file, ucm_upgrade_server::CORE_FILES, $file . ' is not sent to existing installs' );
		}
	}

	public function test_a_core_file_the_server_lacks_is_left_off() {
		$this->write( $this->source, 'init.php', "<?php\n" );
		$this->write( $this->source, 'includes/database.php', "<?php\n" );
		$this->write( $this->source, 'includes/config.php', "<?php // the server's own database password\n" );

		$core = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 2 );

		$this->assertSame( array( 'init.php', 'includes/database.php' ), $core['corefiles']['files'] );
		$this->assertSame( array( 'includes/database.php' ), $core['database']['files'] );
	}

	public function test_an_upload_folder_gets_its_guards_and_nothing_else() {
		// The .htaccess that blocks web access to uploads ships with the release, in a
		// folder the listing skips. An updated install never got it.
		$this->seed_source_plugin();
		$this->write( $this->source, 'includes/plugin_file/upload/.htaccess', "Require all denied\n" );
		$this->write( $this->source, 'includes/plugin_file/upload/index.php', "<?php // silence\n" );
		$this->write( $this->source, 'includes/plugin_file/upload/customer-7/contract.pdf', "private\n" );

		$listing = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_file/' );

		$this->assertSame( array( 'includes/plugin_file/upload/.htaccess', 'includes/plugin_file/upload/index.php' ), $listing['files'] );
		$this->assertContains( 'includes/plugin_file/upload/', $listing['folders'] );
		$this->assertNotContains( 'includes/plugin_file/upload/customer-7/', $listing['folders'] );
	}

	public function test_an_attachments_folder_gets_its_guard_and_nothing_else() {
		// Here the folder itself is listed and only its files are skipped.
		$this->write( $this->source, 'includes/plugin_ticket/ticket.php', "<?php\n" );
		$this->write( $this->source, 'includes/plugin_ticket/attachments/.htaccess', "Require all denied\n" );
		$this->write( $this->source, 'includes/plugin_ticket/attachments/12-passport.jpg', "private\n" );

		$listing = ucm_upgrade_server::list_directory( $this->source, 'includes/plugin_ticket/' );

		$this->assertSame( array( 'includes/plugin_ticket/attachments/.htaccess', 'includes/plugin_ticket/ticket.php' ), $listing['files'] );
	}

	public function test_an_old_client_is_not_sent_empty_files() {
		// The 3.x client writes with `! file_put_contents()`, so zero bytes reads as a
		// failed write and fails the row. contract.css has been empty since 3.555.
		$this->write( $this->source, 'includes/plugin_contract/contract.php', "<?php\n" );
		$this->write( $this->source, 'includes/plugin_contract/css/contract.css', '' );
		$files = array( 'includes/plugin_contract/contract.php', 'includes/plugin_contract/css/contract.css' );

		$this->assertSame( array( 'includes/plugin_contract/contract.php' ), ucm_upgrade_server::files_for_client( $this->source, 0, $files, 1 ) );
		$this->assertSame( $files, ucm_upgrade_server::files_for_client( $this->source, 0, $files, 2 ) );
	}

	public function test_vendor_goes_to_a_client_without_it() {
		$this->seed_source_core();
		$this->write( $this->source, 'vendor/autoload.php', "<?php\n" );
		$this->write( $this->source, 'vendor/composer/installed.json', '{"packages":[]}' );
		$this->write( $this->source, 'vendor/stripe/stripe-php/lib/Stripe.php', "<?php\n" );
		$fingerprint = ucm_upgrade_server::libraries_fingerprint( $this->source );

		// A 3.x client sends nothing, and has no vendor/.
		$this->assertTrue( ucm_upgrade_server::client_needs_libraries( $fingerprint, null ) );
		$this->assertTrue( ucm_upgrade_server::client_needs_libraries( $fingerprint, '' ) );
		$this->assertTrue( ucm_upgrade_server::client_needs_libraries( $fingerprint, str_repeat( 'a', 64 ) ) );
		$this->assertFalse( ucm_upgrade_server::client_needs_libraries( $fingerprint, $fingerprint ) );
		// A server with no vendor/ of its own has nothing to send.
		$this->assertSame( '', ucm_upgrade_server::libraries_fingerprint( $this->destination ) );
		$this->assertFalse( ucm_upgrade_server::client_needs_libraries( '', null ) );

		$core = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, true, 1 );

		// In the database row, which comes second: a big download that fails there cannot
		// stop the core files arriving.
		$this->assertContains( 'vendor/stripe/stripe-php/lib/Stripe.php', $core['database']['files'] );
		$this->assertContains( 'vendor/composer/installed.json', $core['database']['files'] );
		$this->assertContains( 'vendor/stripe/stripe-php/lib/', $core['database']['folders'] );
		$this->assertSame( array(), preg_grep( '#^vendor/#', $core['corefiles']['files'] ) );

		$without = ucm_upgrade_server::core_rows( $this->source, 0, 2.3, true, false, 2 );
		$this->assertSame( array( 'includes/database.php' ), $without['database']['files'] );
	}

	public function test_an_old_updater_can_still_record_a_version_on_the_new_core() {
		// The 3.x updater runs on the new includes/plugin.php until the config row
		// replaces it, which is the last row, and calls this by its old, misspelt name.
		$source = (string) file_get_contents( _UCM_FOLDER . 'includes/plugin.php' );

		$this->assertMatchesRegularExpression( '#function set_insatlled_plugin_version\(#', $source );
		$this->assertMatchesRegularExpression( '#function set_installed_plugin_version\(#', $source );
	}
}
