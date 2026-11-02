<?php

use PHPUnit\Framework\TestCase;

/**
 * The warning shown to an administrator whose site secret is just its install path.
 */
class SecretNoticeTest extends TestCase {

	public static function setUpBeforeClass(): void {
		// _l() reads it before translating anything.
		if ( ! defined( '_DEBUG_MODE' ) ) {
			define( '_DEBUG_MODE', false );
		}
	}

	public function test_nothing_is_shown_when_the_site_has_its_own_secret() {
		$this->assertSame( '', ucm_secret_notice_html( false, false ) );
	}

	public function test_a_site_using_its_install_path_is_told_what_to_do_and_what_it_costs() {
		$html = ucm_secret_notice_html( true, false );

		$this->assertStringContainsString( '_UCM_SECRET', $html );
		$this->assertStringContainsString( 'config.local.php', $html );
		$this->assertStringContainsString( 'already emailed', $html );
		$this->assertStringContainsString( 'cron link', $html );
	}

	public function test_the_public_demo_is_never_nagged() {
		$this->assertSame( '', ucm_secret_notice_html( true, true ) );
	}
}
