<?php

use PHPUnit\Framework\TestCase;

/**
 * What a request gets when the database will not connect.
 *
 * A buyer who unzips the release and opens the site has no config.local.php yet, so
 * includes/config.php falls back to placeholder credentials that cannot work. Since PHP 8.1
 * mysqli throws on a failed connection, and nothing caught it: the first page anyone saw
 * was a blank HTTP 500 instead of the setup wizard. The browser suite never saw it because
 * every container it runs against is pointed at a real database.
 *
 * These run index.php in a separate PHP process, because the database settings are
 * constants and the application cannot be loaded twice in one process. The database is
 * 127.0.0.1 port 1, which refuses at once on any machine, so neither test needs MySQL.
 */
class UnconfiguredInstallTest extends TestCase
{
	/** @var string[] */
	private $cleanup = array();

	protected function setUp(): void {
		if ( ! extension_loaded( 'mysqli' ) ) {
			$this->markTestSkipped( 'Needs the mysqli extension.' );
		}
	}

	protected function tearDown(): void {
		foreach ( $this->cleanup as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}

	public function test_a_copy_with_no_local_config_shows_the_setup_wizard() {
		$result = $this->request( sys_get_temp_dir() . '/ucm-no-such-config-' . getmypid() . '.php' );

		$this->assertSame( '', $result['fatal'], 'The request died before the wizard' );
		$this->assertStringContainsString( 'Database Connection Details', $result['body'] );
	}

	public function test_a_set_up_copy_says_its_database_is_unreachable() {
		$config = tempnam( sys_get_temp_dir(), 'ucm-config-local-' );
		$this->assertNotFalse( $config );
		$this->cleanup[] = $config;
		file_put_contents( $config, "<?php\n"
			. "define( '_DB_SERVER', '127.0.0.1:1' );\n"
			. "define( '_DB_NAME', 'ucm' );\n"
			. "define( '_DB_USER', 'ucm' );\n"
			. "define( '_DB_PASS', 'not-the-password' );\n"
			. "define( '_DB_PREFIX', 'ucm_' );\n" );

		$result = $this->request( $config );

		$this->assertSame( '', $result['fatal'] );
		$this->assertSame( '503', $result['status'] );
		$this->assertStringContainsString( 'Database connection failed', $result['body'] );
		// A site that was set up must not offer the wizard to whoever visits while its
		// database is down - that would let them point it at a database of their own.
		$this->assertStringNotContainsString( 'Database Connection Details', $result['body'] );
		// The database's own message names the user and host. Off screen outside debug mode.
		$this->assertStringNotContainsString( 'Connection refused', $result['body'] );
	}

	/**
	 * Run the setup wizard's first page in a fresh PHP process.
	 *
	 * The environment is built from scratch rather than inherited, so a UCM_DB_* variable
	 * exported in the shell running the tests cannot point this at a real database.
	 *
	 * @return array{body: string, status: string, fatal: string}
	 */
	private function request( $config_local ) {
		$root = dirname( __DIR__, 2 );
		$code = '$_SERVER["REQUEST_URI"] = "/?m=setup&step=1";'
		        . '$_SERVER["HTTP_HOST"] = "localhost";'
		        . '$_SERVER["SCRIPT_NAME"] = "/index.php";'
		        . '$_GET = $_REQUEST = array( "m" => "setup", "step" => "1" );'
		        . 'register_shutdown_function( function () {'
		        . '  $e = error_get_last();'
		        . '  $fatal = $e && in_array( $e["type"], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ? $e["message"] : "";'
		        . '  fwrite( STDERR, "\nUCM-TEST " . json_encode( array( "status" => (string) http_response_code(), "fatal" => $fatal ) ) . "\n" );'
		        . '} );'
		        . 'chdir( ' . var_export( $root, true ) . ' );'
		        . 'require "index.php";';
		$env  = array(
			'PATH'             => (string) getenv( 'PATH' ),
			'UCM_CONFIG_LOCAL' => $config_local,
			'UCM_DB_SERVER'    => '127.0.0.1:1',
		);

		$process = proc_open(
			array( PHP_BINARY, '-d', 'display_errors=0', '-r', $code ),
			array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ),
			$pipes,
			$root,
			$env
		);
		$this->assertIsResource( $process );
		$body   = (string) stream_get_contents( $pipes[1] );
		$stderr = (string) stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $process );

		$this->assertMatchesRegularExpression( '/^UCM-TEST (.*)$/m', $stderr, "The request never finished:\n$stderr" );
		preg_match( '/^UCM-TEST (.*)$/m', $stderr, $match );
		$outcome = json_decode( $match[1], true );

		return array(
			'body'   => $body,
			'status' => $outcome['status'],
			'fatal'  => $outcome['fatal'],
		);
	}
}
