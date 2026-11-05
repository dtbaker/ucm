<?php

use PHPUnit\Framework\TestCase;

/**
 * What the in-app update check tells the update server. The readme and the item page list
 * these fields, so a new one has to be a deliberate, documented decision.
 */
class UpdateCheckPrivacyTest extends TestCase {

	private function source() {
		return file_get_contents( __DIR__ . '/../../includes/plugin_config/config.php' );
	}

	public function test_the_check_sends_the_licence_code_and_the_site_address_and_nothing_personal() {
		$source = $this->source();

		$this->assertStringContainsString( "'installation_code'     => \$licence_code", $source );
		$this->assertStringContainsString( "'installation_location' => full_link( '/' )", $source );
		// The visitor's own address is not the update server's business. It sees the
		// connection's address anyway, for rate limiting.
		$this->assertStringNotContainsString( "'client_ip'", $source );
	}

	public function test_the_check_does_not_contact_the_server_without_a_licence_code() {
		$source = $this->source();

		$this->assertMatchesRegularExpression( '/licence_code_for_updates\(\);\s*if \( ! strlen\( \$licence_code \) \) \{\s*return array\(/', $source );
	}

	public function test_the_user_agent_does_not_carry_the_site_address() {
		$this->assertMatchesRegularExpression( "/\\\$user_agent\\s*=\\s*'UCM\\/'\\s*\\.\\s*_SCRIPT_VERSION;/", $this->source() );
	}
}
