<?php

use PHPUnit\Framework\TestCase;

/**
 * Demo mode is an environment variable, and the switch has to read like one.
 *
 * It used to be `define( "_DEMO_MODE", false )` edited into includes/config.php - a file
 * that ships to buyers, so turning the demo on meant a code change that could travel. The
 * demo sets UCM_DEMO_MODE=1 instead.
 *
 * The string "0" is the case worth a test: a bare cast reads it as true, and "0" is
 * exactly the shape a docker-compose default takes.
 */
class DemoModeConfigTest extends TestCase
{
	/** @var array<string, string|false> */
	private $saved = array();

	protected function setUp(): void {
		if ( ! function_exists( 'ucm_truthy_env' ) ) {
			require_once _UCM_FOLDER . 'includes/config.php';
		}
		$this->saved['UCM_DEMO_MODE'] = getenv( 'UCM_DEMO_MODE' );
	}

	protected function tearDown(): void {
		foreach ( $this->saved as $name => $value ) {
			if ( false === $value ) {
				putenv( $name );
			} else {
				putenv( "$name=$value" );
			}
		}
	}

	public function test_the_off_spellings_are_off() {
		foreach ( array( '0', 'false', 'FALSE', 'off', 'no', '' ) as $value ) {
			putenv( "UCM_DEMO_MODE=$value" );
			$this->assertFalse( ucm_truthy_env( 'UCM_DEMO_MODE' ), "\"$value\" should read as off" );
		}
	}

	public function test_an_unset_variable_is_off() {
		putenv( 'UCM_DEMO_MODE' );
		$this->assertFalse( ucm_truthy_env( 'UCM_DEMO_MODE' ) );
	}

	public function test_the_on_spellings_are_on() {
		foreach ( array( '1', 'true', 'on', 'yes' ) as $value ) {
			putenv( "UCM_DEMO_MODE=$value" );
			$this->assertTrue( ucm_truthy_env( 'UCM_DEMO_MODE' ), "\"$value\" should read as on" );
		}
	}

	public function test_surrounding_space_does_not_change_the_answer() {
		putenv( 'UCM_DEMO_MODE= 0 ' );
		$this->assertFalse( ucm_truthy_env( 'UCM_DEMO_MODE' ) );
		putenv( 'UCM_DEMO_MODE= 1 ' );
		$this->assertTrue( ucm_truthy_env( 'UCM_DEMO_MODE' ) );
	}
}
