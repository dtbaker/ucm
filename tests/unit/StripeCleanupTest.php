<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_paymethod_stripe/cleanup.php';

/**
 * The old stripe-php 1.11.0 folder is deleted on upgrade, and nothing else is.
 */
class StripeCleanupTest extends TestCase
{
	/** @var string[] */
	private $temp = array();

	protected function tearDown(): void {
		foreach ( $this->temp as $dir ) {
			$this->rm( $dir );
		}
		$this->temp = array();
	}

	private function rm( $path ) {
		if ( is_link( $path ) || is_file( $path ) ) {
			@unlink( $path );

			return;
		}
		if ( is_dir( $path ) ) {
			foreach ( scandir( $path ) as $entry ) {
				if ( '.' !== $entry && '..' !== $entry ) {
					$this->rm( $path . '/' . $entry );
				}
			}
			@rmdir( $path );
		}
	}

	private function temp_dir() {
		$dir = sys_get_temp_dir() . '/ucm-stripe-cleanup-' . bin2hex( random_bytes( 6 ) );
		mkdir( $dir, 0755, true );
		$this->temp[] = $dir;

		return realpath( $dir );
	}

	private function fake_install() {
		$root = $this->temp_dir();
		mkdir( $root . '/includes/plugin_paymethod_stripe/stripe-php/lib/Stripe', 0755, true );
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php', '<?php // 1.11.0' );
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/stripe-php/lib/Stripe/Charge.php', '<?php' );
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/stripe-php/.hidden', 'x' );
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/paymethod_stripe.php', '<?php // plugin' );
		mkdir( $root . '/includes/plugin_paymethod_stripe/pages', 0755, true );
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/pages/stripe_settings.php', '<?php' );
		mkdir( $root . '/vendor/stripe/stripe-php', 0755, true );
		file_put_contents( $root . '/vendor/stripe/stripe-php/README', 'modern' );

		return $root;
	}

	public function test_the_old_folder_is_removed_and_everything_else_stays() {
		$root = $this->fake_install();

		$this->assertTrue( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
		$this->assertDirectoryDoesNotExist( $root . '/includes/plugin_paymethod_stripe/stripe-php' );
		$this->assertFileExists( $root . '/includes/plugin_paymethod_stripe/paymethod_stripe.php' );
		$this->assertFileExists( $root . '/includes/plugin_paymethod_stripe/pages/stripe_settings.php' );
		$this->assertFileExists( $root . '/vendor/stripe/stripe-php/README' );
	}

	public function test_running_twice_or_with_nothing_to_remove_is_fine() {
		$root = $this->temp_dir();
		mkdir( $root . '/includes/plugin_paymethod_stripe', 0755, true );

		$this->assertTrue( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
		$this->assertTrue( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
	}

	public function test_no_plugin_folder_means_nothing_happens() {
		$root = $this->temp_dir();

		$this->assertFalse( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
	}

	public function test_a_symlink_in_place_of_the_folder_is_never_followed() {
		$root    = $this->temp_dir();
		$outside = $this->temp_dir();
		file_put_contents( $outside . '/keep.txt', 'mine' );
		mkdir( $root . '/includes/plugin_paymethod_stripe', 0755, true );
		symlink( $outside, $root . '/includes/plugin_paymethod_stripe/stripe-php' );

		$this->assertFalse( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
		$this->assertFileExists( $outside . '/keep.txt' );
	}

	public function test_a_symlinked_plugin_folder_or_includes_folder_is_left_alone() {
		$outside = $this->temp_dir();
		mkdir( $outside . '/stripe-php', 0755, true );
		file_put_contents( $outside . '/stripe-php/keep.txt', 'mine' );

		$root = $this->temp_dir();
		mkdir( $root . '/includes', 0755, true );
		symlink( $outside, $root . '/includes/plugin_paymethod_stripe' );
		$this->assertFalse( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );

		$root2   = $this->temp_dir();
		$outside2 = $this->temp_dir();
		mkdir( $outside2 . '/plugin_paymethod_stripe/stripe-php', 0755, true );
		file_put_contents( $outside2 . '/plugin_paymethod_stripe/stripe-php/keep.txt', 'mine' );
		symlink( $outside2, $root2 . '/includes' );
		$this->assertFalse( ucm_stripe_cleanup::remove_bundled_stripe_php( $root2 . '/' ) );

		$this->assertFileExists( $outside . '/stripe-php/keep.txt' );
		$this->assertFileExists( $outside2 . '/plugin_paymethod_stripe/stripe-php/keep.txt' );
	}

	public function test_a_symlink_inside_the_old_folder_is_removed_not_followed() {
		$root    = $this->fake_install();
		$outside = $this->temp_dir();
		file_put_contents( $outside . '/keep.txt', 'mine' );
		symlink( $outside, $root . '/includes/plugin_paymethod_stripe/stripe-php/lib/link' );

		$this->assertTrue( ucm_stripe_cleanup::remove_bundled_stripe_php( $root . '/' ) );
		$this->assertDirectoryDoesNotExist( $root . '/includes/plugin_paymethod_stripe/stripe-php' );
		$this->assertFileExists( $outside . '/keep.txt' );
	}

	public function test_the_plugin_removes_it_on_upgrade_and_ships_no_copy() {
		$root = dirname( __DIR__, 2 );
		$code = file_get_contents( $root . '/includes/plugin_paymethod_stripe/paymethod_stripe.php' );
		$this->assertStringContainsString( 'ucm_stripe_cleanup::remove_bundled_stripe_php()', $code );
		$this->assertDirectoryDoesNotExist( $root . '/includes/plugin_paymethod_stripe/stripe-php' );
		$this->assertStringNotContainsString( 'stripe-php/lib', $code );
		$this->assertMatchesRegularExpression( "/'bundled'\s*=>\s*''/", $code, 'the library declaration has no bundled fallback' );
	}

	public function test_the_old_charges_form_is_deleted_and_the_other_pages_are_not() {
		$root = $this->fake_install();
		file_put_contents( $root . '/includes/plugin_paymethod_stripe/pages/stripe_form.php', '<?php // old form' );

		$this->assertTrue( ucm_stripe_cleanup::remove_old_charges_form( $root ) );
		$this->assertFileDoesNotExist( $root . '/includes/plugin_paymethod_stripe/pages/stripe_form.php' );
		$this->assertFileExists( $root . '/includes/plugin_paymethod_stripe/pages/stripe_settings.php' );
		$this->assertTrue( ucm_stripe_cleanup::remove_old_charges_form( $root ), 'and a second run is harmless' );
	}

	public function test_a_symlinked_charges_form_is_left_alone() {
		$root   = $this->fake_install();
		$secret = $this->temp_dir() . '/keep.php';
		file_put_contents( $secret, '<?php // not ours' );
		symlink( $secret, $root . '/includes/plugin_paymethod_stripe/pages/stripe_form.php' );

		$this->assertFalse( ucm_stripe_cleanup::remove_old_charges_form( $root ) );
		$this->assertFileExists( $secret );
	}
}
