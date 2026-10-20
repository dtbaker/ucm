<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/custom/demo/shell-only.php';

/**
 * The guard on custom/demo/reset.php, which replaces the demo's database and sits inside
 * the web root.
 *
 * It has to refuse every web request, and it has to run from a shell on the demo server,
 * where PHP is the CGI build. Refusing there as well is the bug that let the deploy and
 * reset workflows pass while the script did nothing.
 */
class DemoShellGuardTest extends TestCase {

	public function test_the_command_line_binary_is_a_shell() {
		$this->assertTrue( ucm_demo_is_shell( 'cli', array() ) );
	}

	public function test_the_cgi_binary_run_from_a_shell_is_a_shell() {
		// What SiteGround's /usr/local/php82/bin/php reports, with no request around it.
		$this->assertTrue( ucm_demo_is_shell( 'cgi-fcgi', array( 'argv' => array( 'reset.php', 'repair' ), 'argc' => 2 ) ) );
		$this->assertTrue( ucm_demo_is_shell( 'cgi', array() ) );
	}

	public function test_a_request_is_never_a_shell_whichever_sapi_serves_it() {
		$request = array( 'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'demo.ultimateclientmanager.com', 'REMOTE_ADDR' => '203.0.113.9' );
		foreach ( array( 'cgi-fcgi', 'cgi', 'fpm-fcgi', 'apache2handler', 'litespeed', 'cli-server' ) as $sapi ) {
			$this->assertFalse( ucm_demo_is_shell( $sapi, $request ), "$sapi serving a request must be refused" );
		}
	}

	public function test_any_one_request_variable_is_enough_to_refuse_a_cgi_run() {
		foreach ( array( 'REQUEST_METHOD', 'HTTP_HOST', 'REMOTE_ADDR', 'SERVER_PROTOCOL' ) as $key ) {
			$this->assertFalse( ucm_demo_is_shell( 'cgi-fcgi', array( $key => 'x' ) ), "$key marks a web request" );
		}
	}

	public function test_a_server_sapi_with_no_request_variables_is_still_refused() {
		foreach ( array( 'fpm-fcgi', 'apache2handler', 'litespeed', 'cli-server' ) as $sapi ) {
			$this->assertFalse( ucm_demo_is_shell( $sapi, array() ), "$sapi is a web server" );
		}
	}

	public function test_the_command_is_the_first_argument() {
		$this->assertSame( 'repair', ucm_demo_command( array( 'reset.php', 'repair' ), false, false, 'custom/demo/reset.php' ) );
	}

	public function test_the_environment_names_the_command_when_there_is_no_argv() {
		// The server's CGI build leaves $argv unset.
		$this->assertSame( 'ensure', ucm_demo_command( array(), 'ensure', false, 'custom/demo/reset.php' ) );
	}

	public function test_the_process_command_line_is_the_last_resort() {
		$cmdline = "/usr/local/php82/bin/php\0-q\0custom/demo/reset.php\0snapshot\0";
		$this->assertSame( 'snapshot', ucm_demo_command( array(), false, $cmdline, '/srv/site/custom/demo/reset.php' ) );
	}

	public function test_an_argument_wins_over_the_environment() {
		$this->assertSame( 'check', ucm_demo_command( array( 'reset.php', 'check' ), 'restore', false, 'reset.php' ) );
	}

	public function test_no_command_anywhere_is_an_empty_string() {
		$this->assertSame( '', ucm_demo_command( array(), false, false, 'reset.php' ) );
		$this->assertSame( '', ucm_demo_command( array(), '', "php\0-q\0reset.php\0", 'reset.php' ) );
		$this->assertSame( '', ucm_demo_command( array( 'reset.php' ), false, "php\0other.php\0x\0", 'reset.php' ) );
	}
}
