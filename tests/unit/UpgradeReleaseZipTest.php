<?php

use PHPUnit\Framework\TestCase;

/**
 * The zip the update server builds for the old one-file installer.
 *
 * install.php, and ucm-web/api/download_zip.php after it, ask the update server for the
 * whole product as one zip (return_as_zip + return_base_system_in_zip). That zip used to be
 * put together from a hand-kept list of folders, and it fell behind the product: it left
 * out includes/class.libraries.php, vendor/, n/ and every guard file, and swapped the real
 * includes/config.php for a generated one. A site installed from it failed on every page.
 *
 * It is built from the release's own file list now, the one scripts/build-release.sh
 * writes into Main Files. These tests hold it to that list, and check that a live server's
 * customer files cannot get into it by any route.
 */
class UpgradeReleaseZipTest extends TestCase
{
	/** @var string Absolute path of a pretend server installation, with trailing slash. */
	private $root;

	public static function setUpBeforeClass(): void {
		require_once _UCM_FOLDER . 'includes/plugin_config/class.upgrade_server.php';
	}

	protected function setUp(): void {
		$this->root = sys_get_temp_dir() . '/ucm-release-zip-test-' . bin2hex( random_bytes( 6 ) ) . '/';
		mkdir( $this->root, 0777, true );
	}

	protected function tearDown(): void {
		$this->remove( rtrim( $this->root, '/' ) );
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
		$full = $this->root . $relative;
		if ( ! is_dir( dirname( $full ) ) ) {
			mkdir( dirname( $full ), 0777, true );
		}
		file_put_contents( $full, $contents );
	}

	/**
	 * Build the zip the way upgrade_server.php does and return its entries, sorted.
	 *
	 * @param array<string, string> $files
	 *
	 * @return array<string, string> Entry name => contents.
	 */
	private function zip_entries( array $files ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->markTestSkipped( 'this PHP has no zip extension' );
		}
		$zip_file = $this->root . '../' . basename( $this->root ) . '.zip';
		$this->assertTrue( ucm_upgrade_server::write_release_zip( $zip_file, $files ) );

		$zip = new ZipArchive();
		$this->assertTrue( $zip->open( $zip_file ) );
		$entries = array();
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name             = (string) $zip->getNameIndex( $i );
			$entries[ $name ] = (string) $zip->getFromIndex( $i );
		}
		$zip->close();
		unlink( $zip_file );
		ksort( $entries, SORT_STRING );

		return $entries;
	}

	/**
	 * The committed part of the release: what `git archive` puts in Main Files before
	 * Composer adds vendor/. The same command scripts/build-release.sh runs.
	 *
	 * @return string[]
	 */
	private function release_list_from_git() {
		if ( ! function_exists( 'shell_exec' ) || ! file_exists( _UCM_FOLDER . '.git' ) ) {
			$this->markTestSkipped( 'needs a git checkout to know what the release holds' );
		}
		$output = shell_exec( 'git -C ' . escapeshellarg( _UCM_FOLDER ) . ' archive --worktree-attributes HEAD 2>/dev/null | tar -t 2>/dev/null' );
		$paths  = array();
		foreach ( explode( "\n", (string) $output ) as $line ) {
			if ( '' !== $line && '/' !== substr( $line, -1 ) ) {
				$paths[] = $line;
			}
		}
		if ( count( $paths ) < 100 ) {
			$this->markTestSkipped( 'git archive did not run here' );
		}

		return $paths;
	}

	public function test_the_install_zip_is_the_release() {
		$release = $this->release_list_from_git();
		// vendor/ is added by Composer at build time, so it is not in git. The checkout's own
		// copy stands in for it, to show vendor files are served like any other.
		if ( is_file( _UCM_FOLDER . 'vendor/autoload.php' ) ) {
			$release[] = 'vendor/autoload.php';
		}
		sort( $release, SORT_STRING );

		$files = ucm_upgrade_server::release_files( _UCM_FOLDER, 0, $release );

		$this->assertSame( array(), $files['missing'], 'listed in the release but not served' );
		$this->assertSame( array(), $files['refused'], 'listed in the release but refused' );

		$entries = $this->zip_entries( $files['files'] );
		$this->assertSame( $release, array_keys( $entries ) );

		// The real config.php, byte for byte: it loads config.local.php, which is where the
		// setup wizard saves the database details. The old zip generated its own.
		$this->assertSame( file_get_contents( _UCM_FOLDER . 'includes/config.php' ), $entries['includes/config.php'] );

		// Each of these was missing from the old zip.
		foreach ( array(
			'includes/class.libraries.php',
			'n/index.php',
			'temp/.htaccess',
			'temp/index.php',
			'temp/.gitkeep',
			'includes/plugin_file/upload/.htaccess',
			'includes/plugin_file/upload/index.php',
			'includes/plugin_file/upload/plupload/index.php',
			'includes/plugin_data/upload/.htaccess',
			'includes/plugin_data/upload/index.php',
			'includes/plugin_ticket/attachments/.htaccess',
			'includes/plugin_ticket/attachments/index.php',
			'includes/plugin_email/attachments/index.php',
		) as $path ) {
			$this->assertArrayHasKey( $path, $entries );
		}
	}

	public function test_a_customers_files_never_leave_the_server() {
		$release = array(
			'index.php',
			'includes/config.php',
			'temp/.gitkeep',
			'temp/.htaccess',
			'temp/index.php',
			'includes/plugin_data/upload/.htaccess',
			'includes/plugin_data/upload/index.php',
			'includes/plugin_email/attachments/index.php',
			'includes/plugin_file/upload/.htaccess',
			'includes/plugin_file/upload/index.php',
			'includes/plugin_file/upload/plupload/index.php',
			'includes/plugin_ticket/attachments/.htaccess',
			'includes/plugin_ticket/attachments/index.php',
		);
		foreach ( $release as $path ) {
			$this->write( $path, 'release copy of ' . $path );
		}
		// What a live server has besides the release.
		$private = array(
			'includes/config.local.php'                         => "<?php define('_DB_PASS','hunter2');",
			'temp/cache_1234.php'                               => 'cached page',
			'includes/plugin_file/upload/42-signed-contract.pdf' => 'a customer file',
			'includes/plugin_file/upload/plupload/chunk.part'   => 'half an upload',
			'includes/plugin_data/upload/7-passport.jpg'        => 'a customer file',
			'includes/plugin_ticket/attachments/9-invoice.pdf'  => 'a customer file',
			'includes/plugin_email/attachments/3-quote.pdf'     => 'a customer file',
			'custom/design_header.php'                          => 'the demo banner',
		);
		foreach ( $private as $path => $contents ) {
			$this->write( $path, $contents );
		}

		$files   = ucm_upgrade_server::release_files( $this->root, 0, $release );
		$entries = $this->zip_entries( $files['files'] );

		sort( $release, SORT_STRING );
		$this->assertSame( $release, array_keys( $entries ) );
		foreach ( $entries as $path => $contents ) {
			$this->assertSame( 'release copy of ' . $path, $contents );
		}
	}

	public function test_a_list_that_names_a_customers_file_is_not_believed() {
		$tampered = array(
			'includes/config.local.php',
			'temp/cache_1234.php',
			'includes/plugin_file/upload/42-signed-contract.pdf',
			'includes/plugin_file/upload/plupload/chunk.part',
			'includes/plugin_data/upload/7-passport.jpg',
			'includes/plugin_ticket/attachments/9-invoice.pdf',
			'includes/plugin_email/attachments/3-quote.pdf',
			'includes/plugin_backup/backups/backup_2026-10-17.zip',
			'../outside.php',
			'includes/../../outside.php',
			'/etc/passwd',
			'includes\\config.local.php',
		);
		foreach ( $tampered as $path ) {
			if ( false === strpos( $path, '..' ) && '/' !== $path[0] ) {
				$this->write( $path, 'private' );
			}
		}

		$files = ucm_upgrade_server::release_files( $this->root, 0, $tampered );

		$this->assertSame( array(), $files['files'] );
		$this->assertSame( $tampered, $files['refused'] );
	}

	public function test_a_file_the_list_names_but_the_server_lacks_is_reported() {
		$this->write( 'index.php', '<?php' );

		$files = ucm_upgrade_server::release_files( $this->root, 0, array( 'index.php', 'includes/class.libraries.php' ) );

		$this->assertSame( array( 'includes/class.libraries.php' ), $files['missing'] );
		$this->assertSame( array( 'index.php' ), array_keys( $files['files'] ) );
	}

	public function test_the_list_is_read_as_the_build_writes_it() {
		$this->assertFalse( ucm_upgrade_server::release_manifest( $this->root ), 'a checkout has no list' );

		$this->write( ucm_upgrade_server::RELEASE_MANIFEST, "# Every file in this release.\n# Do not edit.\n" );
		$this->assertFalse( ucm_upgrade_server::release_manifest( $this->root ), 'a list of nothing is no list' );

		$this->write(
			ucm_upgrade_server::RELEASE_MANIFEST,
			"# Every file in this release.\n# Do not edit.\n.htaccess\nincludes/release-files.txt\nindex.php\r\n\nvendor/autoload.php\n"
		);
		$this->assertSame(
			array( '.htaccess', 'includes/release-files.txt', 'index.php', 'vendor/autoload.php' ),
			ucm_upgrade_server::release_manifest( $this->root )
		);
	}

	public function test_a_branded_copy_still_wins_and_cannot_climb_out() {
		$this->write( 'images/logo.png', 'stock logo' );
		$this->write( 'deploy/7/images/logo.png', 'branded logo' );

		$this->assertSame( $this->root . 'deploy/7/images/logo.png', ucm_upgrade_server::source_path( $this->root, 7, 'images/logo.png' ) );
		$this->assertSame( $this->root . 'images/logo.png', ucm_upgrade_server::source_path( $this->root, 8, 'images/logo.png' ) );
		// basename() keeps the application id inside deploy/: this asks for a folder beside
		// the installation, and gets the stock file.
		$outside = dirname( $this->root ) . '/' . basename( $this->root ) . '-outside/';
		mkdir( $outside . 'images', 0777, true );
		file_put_contents( $outside . 'images/logo.png', 'not this server\'s' );
		$found = ucm_upgrade_server::source_path( $this->root, '../../' . basename( $this->root ) . '-outside', 'images/logo.png' );
		$this->remove( rtrim( $outside, '/' ) );
		$this->assertSame( $this->root . 'images/logo.png', $found );
	}
}
