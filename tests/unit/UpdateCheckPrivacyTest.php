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

	public function test_the_check_does_not_send_the_visitors_ip_or_the_site_address() {
		$source = $this->source();

		$this->assertStringNotContainsString( "'client_ip'", $source );
		$this->assertStringNotContainsString( "'installation_location'", $source );
	}

	public function test_the_check_does_not_send_the_licence_code() {
		$this->assertStringNotContainsString( "'installation_code'", $this->source() );
	}

	public function test_the_user_agent_does_not_carry_the_site_address() {
		$this->assertMatchesRegularExpression( "/\\\$user_agent\\s*=\\s*'UCM\\/'\\s*\\.\\s*_SCRIPT_VERSION;/", $this->source() );
	}
}
