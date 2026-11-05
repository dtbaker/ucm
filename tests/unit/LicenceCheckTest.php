<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/plugin_config/class.licence_check.php';

/**
 * The update server only serves installations with a real purchase code. These tests give
 * ucm_licence_check a pretend Envato, so no network is involved.
 */
class LicenceCheckTest extends TestCase {

	private const GOOD = '30d91230-a8df-4545-1237-467abcd5b920';
	private const OTHER = '11111111-2222-3333-4444-555555555555';

	/** @var string */
	private $cache;

	/** @var string[] Codes the pretend Envato was asked about. */
	private $asked = array();

	protected function setUp(): void {
		$this->cache = sys_get_temp_dir() . '/ucm-licence-test-' . bin2hex( random_bytes( 4 ) );
		$this->asked = array();
	}

	protected function tearDown(): void {
		foreach ( (array) glob( $this->cache . '/*' ) as $file ) {
			@unlink( $file );
		}
		@rmdir( $this->cache );
	}

	/** A pretend Envato: GOOD is a UCM sale, OTHER is a sale of some other item, the rest are unknown. */
	private function envato( $outage = false ) {
		return function ( $code ) use ( $outage ) {
			$this->asked[] = $code;
			if ( $outage ) {
				return array( 'status' => 503, 'body' => '' );
			}
			if ( self::GOOD === $code ) {
				return array( 'status' => 200, 'body' => json_encode( array( 'item' => array( 'id' => 2621629 ) ) ) );
			}
			if ( self::OTHER === $code ) {
				return array( 'status' => 200, 'body' => json_encode( array( 'item' => array( 'id' => 42 ) ) ) );
			}

			return array( 'status' => 404, 'body' => '{}' );
		};
	}

	public function test_codes_in_a_request_are_split_and_only_uuids_survive() {
		$codes = ucm_licence_check::codes_from_request( strtoupper( self::GOOD ) . '| free |not-a-code|' . self::GOOD . '|' . self::OTHER );

		$this->assertSame( array( self::GOOD, self::OTHER ), $codes );
		$this->assertSame( array(), ucm_licence_check::codes_from_request( array( self::GOOD ) ) );
		$this->assertSame( array(), ucm_licence_check::codes_from_request( 'UCM_LICENCE_CODES_GO_HERE' ) );
	}

	public function test_no_code_means_no_updates_and_no_call_to_envato() {
		$result = ucm_licence_check::check( array(), $this->envato(), $this->cache, 1000 );

		$this->assertSame( ucm_licence_check::NONE, $result['status'] );
		$this->assertSame( array(), $this->asked );
	}

	public function test_a_purchase_of_this_item_is_valid() {
		$result = ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 );

		$this->assertSame( ucm_licence_check::VALID, $result['status'] );
		$this->assertSame( array( self::GOOD ), array_keys( $result['codes'] ) );
		$this->assertStringContainsString( 'codecanyon.net/item/', $result['codes'][ self::GOOD ] );
	}

	public function test_a_code_for_a_different_item_or_an_unknown_code_is_invalid() {
		$this->assertSame( ucm_licence_check::INVALID, ucm_licence_check::check( array( self::OTHER ), $this->envato(), $this->cache, 1000 )['status'] );
		$this->assertSame( ucm_licence_check::INVALID, ucm_licence_check::check( array( '99999999-9999-9999-9999-999999999999' ), $this->envato(), $this->cache, 1000 )['status'] );
	}

	public function test_one_good_code_among_bad_ones_is_enough() {
		$result = ucm_licence_check::check( array( self::OTHER, self::GOOD ), $this->envato(), $this->cache, 1000 );

		$this->assertSame( ucm_licence_check::VALID, $result['status'] );
		$this->assertSame( array( self::GOOD ), array_keys( $result['codes'] ) );
	}

	public function test_an_answer_is_cached_for_a_day() {
		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 );
		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 + 3600 );
		$this->assertSame( array( self::GOOD ), $this->asked );

		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 + ucm_licence_check::CACHE_VALID_FOR + 1 );
		$this->assertSame( array( self::GOOD, self::GOOD ), $this->asked );
	}

	public function test_a_refused_code_is_asked_about_again_after_an_hour() {
		ucm_licence_check::check( array( self::OTHER ), $this->envato(), $this->cache, 1000 );
		ucm_licence_check::check( array( self::OTHER ), $this->envato(), $this->cache, 1000 + 60 );
		$this->assertCount( 1, $this->asked );

		ucm_licence_check::check( array( self::OTHER ), $this->envato(), $this->cache, 1000 + ucm_licence_check::CACHE_INVALID_FOR + 1 );
		$this->assertCount( 2, $this->asked );
	}

	public function test_an_outage_is_not_the_same_as_an_invalid_code() {
		$result = ucm_licence_check::check( array( self::GOOD ), $this->envato( true ), $this->cache, 1000 );

		$this->assertSame( ucm_licence_check::UNAVAILABLE, $result['status'] );
		$this->assertSame( array(), glob( $this->cache . '/*' ) ?: array(), 'an outage is not cached' );
	}

	public function test_a_recently_good_code_survives_an_outage() {
		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 );

		$during = 1000 + ucm_licence_check::CACHE_VALID_FOR + 60;
		$this->assertSame( ucm_licence_check::VALID, ucm_licence_check::check( array( self::GOOD ), $this->envato( true ), $this->cache, $during )['status'] );

		$after = 1000 + ucm_licence_check::OUTAGE_GRACE + 60;
		$this->assertSame( ucm_licence_check::UNAVAILABLE, ucm_licence_check::check( array( self::GOOD ), $this->envato( true ), $this->cache, $after )['status'] );
	}

	public function test_a_code_that_stops_being_valid_is_refused_once_the_cache_expires() {
		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 );
		$refunded = function ( $code ) {
			return array( 'status' => 404, 'body' => '{}' );
		};

		$later = 1000 + ucm_licence_check::CACHE_VALID_FOR + 1;
		$this->assertSame( ucm_licence_check::INVALID, ucm_licence_check::check( array( self::GOOD ), $refunded, $this->cache, $later )['status'] );
	}

	public function test_the_cache_holds_no_trace_of_the_code_or_the_buyer() {
		ucm_licence_check::check( array( self::GOOD ), $this->envato(), $this->cache, 1000 );

		$files = glob( $this->cache . '/*' );
		$this->assertCount( 1, $files );
		$this->assertStringNotContainsString( self::GOOD, basename( $files[0] ) );
		$this->assertStringNotContainsString( self::GOOD, file_get_contents( $files[0] ) );
		$this->assertSame( array( 's' => 'valid', 't' => 1000 ), json_decode( file_get_contents( $files[0] ), true ) );
	}

	public function test_without_a_token_nothing_can_be_verified() {
		putenv( 'UCM_ENVATO_TOKEN' );
		if ( defined( '_UCM_ENVATO_TOKEN' ) ) {
			$this->markTestSkipped( 'a token is configured in this environment' );
		}
		$result = ucm_licence_check::check( array( self::GOOD ), null, $this->cache, 1000 );

		$this->assertSame( ucm_licence_check::UNAVAILABLE, $result['status'] );
	}

	public function test_every_refusal_is_a_message_the_client_shows_and_stops_on() {
		foreach ( array( ucm_licence_check::NONE, ucm_licence_check::INVALID, ucm_licence_check::UNAVAILABLE ) as $status ) {
			$response = ucm_licence_check::refusal( $status );
			$updates  = $response['available_updates'];

			$this->assertNotSame( '', $updates['message'], $status );
			$this->assertSame( array(), $updates['plugins'], $status );
			$this->assertSame( 0, $updates['new_version'], $status );
			$this->assertTrue( $updates['licence_required'], $status );
		}
		$this->assertStringContainsString( 'licence code', ucm_licence_check::message_for( ucm_licence_check::NONE ) );
	}

	public function test_the_one_file_installer_is_told_to_install_by_hand_not_to_use_a_licence_box() {
		$updater   = ucm_licence_check::message_for( ucm_licence_check::NONE );
		$installer = ucm_licence_check::message_for( ucm_licence_check::NONE, true );

		$this->assertStringContainsString( 'licence box', $updater );
		$this->assertStringNotContainsString( 'licence box', $installer );
		$this->assertStringContainsString( 'installer needs a licence code', $installer );
		$this->assertStringContainsString( 'upload the files yourself', $installer );
		$this->assertSame( $installer, ucm_licence_check::refusal( ucm_licence_check::NONE, true )['available_updates']['message'] );
		// A wrong code reads the same either way.
		$this->assertSame(
			ucm_licence_check::message_for( ucm_licence_check::INVALID ),
			ucm_licence_check::message_for( ucm_licence_check::INVALID, true )
		);
	}
}
