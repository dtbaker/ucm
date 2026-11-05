<?php

use PHPUnit\Framework\TestCase;

/**
 * includes/config.php picks _UCM_SECRET from, in order, config.local.php, the UCM_SECRET
 * environment variable, then the install path. Every capability link UCM has issued is signed
 * with it, so it must never change under an existing install - but a secret that is only the
 * install path is guessable, and _UCM_SECRET_IS_INSTALL_PATH lets the app say so.
 *
 * config.php defines constants, so each case runs it in its own PHP process.
 */
class UcmSecretConfigTest extends TestCase
{
	/** @return array{secret: string, flag: string} */
	private function load_config( array $env, $local_php = null ) {
		$local = sys_get_temp_dir() . '/ucm_config_local_' . bin2hex( random_bytes( 4 ) ) . '.php';
		if ( null !== $local_php ) {
			file_put_contents( $local, "<?php\n" . $local_php );
		}
		$script = 'require ' . var_export( dirname( __DIR__, 2 ) . '/includes/config.php', true ) . ';'
		          . 'echo json_encode( array( "secret" => _UCM_SECRET, "flag" => _UCM_SECRET_IS_INSTALL_PATH, "folder" => _UCM_FOLDER ) );';
		$env    = array_merge( array( 'UCM_CONFIG_LOCAL' => $local, 'PATH' => (string) getenv( 'PATH' ) ), $env );
		$proc   = proc_open( array( PHP_BINARY, '-r', $script ), array( 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes, null, $env );
		$out    = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		proc_close( $proc );
		@unlink( $local );
		$decoded = json_decode( $out, true );
		$this->assertIsArray( $decoded, 'config.php did not load: ' . $out );

		return $decoded;
	}

	public function test_with_nothing_configured_the_install_path_is_used_and_flagged() {
		$result = $this->load_config( array() );
		$this->assertSame( $result['folder'], $result['secret'] );
		$this->assertTrue( $result['flag'] );
	}

	public function test_the_environment_variable_wins_over_the_path() {
		$result = $this->load_config( array( 'UCM_SECRET' => 'from-the-environment' ) );
		$this->assertSame( 'from-the-environment', $result['secret'] );
		$this->assertFalse( $result['flag'] );
	}

	public function test_config_local_wins_over_everything() {
		$result = $this->load_config( array( 'UCM_SECRET' => 'from-the-environment' ), "define( '_UCM_SECRET', 'from-config-local' );\n" );
		$this->assertSame( 'from-config-local', $result['secret'] );
		$this->assertFalse( $result['flag'] );
	}

	public function test_config_local_alone_is_not_flagged() {
		$result = $this->load_config( array(), "define( '_UCM_SECRET', 'abc123' );\n" );
		$this->assertSame( 'abc123', $result['secret'] );
		$this->assertFalse( $result['flag'] );
	}
}
