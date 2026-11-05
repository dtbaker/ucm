<?php

use PHPUnit\Framework\TestCase;

/**
 * send_error() emails the request to the administrator. Credentials in it must not travel.
 */
class ErrorEmailRedactionTest extends TestCase {

	public function test_credentials_and_cookies_are_removed_at_any_depth() {
		$clean = ucm_redact_for_email( array(
			'business'    => 'me@example.com',
			'password'    => 'hunter2',
			'HTTP_COOKIE' => 'PHPSESSID=abc',
			'nested'      => array( 'api_key' => 'k', 'amount' => '10.00' ),
			'txn_id'      => '123',
		) );

		$this->assertSame( 'me@example.com', $clean['business'] );
		$this->assertSame( '10.00', $clean['nested']['amount'] );
		$this->assertSame( '123', $clean['txn_id'] );
		$this->assertSame( '[removed]', $clean['password'] );
		$this->assertSame( '[removed]', $clean['HTTP_COOKIE'] );
		$this->assertSame( '[removed]', $clean['nested']['api_key'] );
		$this->assertStringNotContainsString( 'hunter2', var_export( $clean, true ) );
	}
}
