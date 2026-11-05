<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin.php';
require_once dirname( __DIR__, 2 ) . '/includes/plugin_ticket/ticket.php';

/**
 * Users created from a ticket used to get substr(md5(time().mt_rand(0,600)),3,7): a seven
 * character guess space of about 600 values per second. They now come from random_int().
 */
class TicketPasswordTest extends TestCase
{
	public function test_the_default_password_is_long_enough_and_plain() {
		$password = module_ticket::generate_password();
		$this->assertGreaterThanOrEqual( 10, strlen( $password ) );
		$this->assertMatchesRegularExpression( '/^[a-z0-9]+$/', $password );
	}

	public function test_a_short_length_is_raised_to_the_minimum() {
		$this->assertSame( 10, strlen( module_ticket::generate_password( 3 ) ) );
		$this->assertSame( 10, strlen( module_ticket::generate_password( 0 ) ) );
	}

	public function test_a_longer_length_is_honoured() {
		$this->assertSame( 24, strlen( module_ticket::generate_password( 24 ) ) );
	}

	public function test_passwords_do_not_repeat() {
		$seen = array();
		for ( $i = 0; $i < 200; $i++ ) {
			$seen[ module_ticket::generate_password() ] = true;
		}
		$this->assertCount( 200, $seen );
	}

	public function test_every_character_of_the_alphabet_turns_up() {
		$chars = '';
		for ( $i = 0; $i < 200; $i++ ) {
			$chars .= module_ticket::generate_password( 20 );
		}
		$this->assertSame( 36, count( array_unique( str_split( $chars ) ) ) );
	}

	public function test_no_password_is_built_from_the_clock_any_more() {
		$code = file_get_contents( dirname( __DIR__, 2 ) . '/includes/plugin_ticket/ticket.php' );
		$this->assertStringNotContainsString( 'md5( time()', $code );
		$this->assertStringNotContainsString( 'mt_rand', $code );
	}
}
