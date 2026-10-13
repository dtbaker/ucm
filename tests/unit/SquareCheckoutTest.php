<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_invoice/payment_verification.php';
require_once dirname( __DIR__, 2 ) . '/includes/plugin_paymethod_square/checkout.php';

/**
 * Square webhook signatures and settlement reading.
 *
 * The signature cases are the point of this file. Square signs the notification URL as it
 * was typed into the Developer Console, joined to the raw body - so the usual failure is
 * not a bad key, it is a URL that differs by a slash. These tests pin that behaviour so
 * the error message on the settings page keeps telling the truth.
 */
class SquareCheckoutTest extends TestCase
{
	private const KEY = 'sq_signature_key_example';
	private const URL = 'https://example.com/ext.php?m=paymethod_square&h=ipn';

	private function body() {
		return '{"merchant_id":"M1","type":"payment.updated","data":{"object":{"payment":{"id":"p1","order_id":"o1","status":"COMPLETED"}}}}';
	}

	private function sign( $body, $url = self::URL, $key = self::KEY ) {
		return base64_encode( hash_hmac( 'sha256', $url . $body, $key, true ) );
	}

	public function test_a_correctly_signed_notification_is_accepted() {
		$body = $this->body();

		$this->assertTrue( ucm_square_checkout::verify_signature( $body, $this->sign( $body ), self::KEY, self::URL ) );
	}

	public function test_a_tampered_body_is_rejected() {
		$body      = $this->body();
		$signature = $this->sign( $body );

		$this->assertFalse( ucm_square_checkout::verify_signature( str_replace( '"o1"', '"o2"', $body ), $signature, self::KEY, self::URL ) );
	}

	public function test_a_signature_from_a_different_key_is_rejected() {
		$body = $this->body();

		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $this->sign( $body, self::URL, 'someone_elses_key' ), self::KEY, self::URL ) );
	}

	/**
	 * The single largest source of support tickets this integration can produce: the URL
	 * in Square's console differing from the one UCM computes.
	 */
	public function test_a_trailing_slash_on_the_notification_url_fails_verification() {
		$body = $this->body();

		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $this->sign( $body, self::URL . '/' ), self::KEY, self::URL ) );
	}

	public function test_http_against_https_fails_verification() {
		$body = $this->body();

		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $this->sign( $body, str_replace( 'https://', 'http://', self::URL ) ), self::KEY, self::URL ) );
	}

	/**
	 * Square's Connect v1 sent an HMAC-SHA1 header and third parties report it is still
	 * delivered. Accepting it would be a downgrade.
	 */
	public function test_a_sha1_signature_is_rejected() {
		$body      = $this->body();
		$signature = base64_encode( hash_hmac( 'sha1', self::URL . $body, self::KEY, true ) );

		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $signature, self::KEY, self::URL ) );
	}

	public function test_an_empty_signature_body_key_or_url_is_rejected() {
		$body = $this->body();

		$this->assertFalse( ucm_square_checkout::verify_signature( '', $this->sign( $body ), self::KEY, self::URL ) );
		$this->assertFalse( ucm_square_checkout::verify_signature( $body, '', self::KEY, self::URL ) );
		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $this->sign( $body ), '', self::URL ) );
		$this->assertFalse( ucm_square_checkout::verify_signature( $body, $this->sign( $body ), self::KEY, '' ) );
	}

	public function test_the_sandbox_and_live_hosts_are_the_documented_ones() {
		$this->assertSame( 'https://connect.squareupsandbox.com', ucm_square_checkout::api_base( true ) );
		$this->assertSame( 'https://connect.squareup.com', ucm_square_checkout::api_base( false ) );
	}

	private function paid_order( $overrides = array() ) {
		return array(
			'id'           => 'order_abc',
			'reference_id' => '42',
			'state'        => isset( $overrides['state'] ) ? $overrides['state'] : 'COMPLETED',
			'total_money'  => array( 'amount' => 10000, 'currency' => 'USD' ),
			'tenders'      => isset( $overrides['tenders'] ) ? $overrides['tenders'] : array(
				array(
					'id'           => 'tender_1',
					'amount_money' => array( 'amount' => 10000, 'currency' => 'USD' ),
					'card_details' => array( 'status' => 'CAPTURED' ),
				),
			),
		);
	}

	public function test_a_paid_order_settles() {
		$settlement = ucm_square_checkout::settlement( $this->paid_order() );

		$this->assertTrue( $settlement['paid'] );
		$this->assertSame( 100.00, $settlement['amount'] );
		$this->assertSame( 'USD', $settlement['currency'] );
		$this->assertSame( 'tender_1', $settlement['transaction_id'] );
		$this->assertSame( '42', $settlement['reference_id'] );
	}

	/**
	 * total_money is what was asked for; the tenders are what arrived. Recording the
	 * first instead of the second is how a gateway marks an invoice paid in full for a
	 * cent.
	 */
	public function test_the_tenders_are_believed_not_the_order_total() {
		$order = $this->paid_order( array(
			'tenders' => array(
				array( 'id' => 'tender_1', 'amount_money' => array( 'amount' => 1, 'currency' => 'USD' ), 'card_details' => array( 'status' => 'CAPTURED' ) ),
			),
		) );

		$settlement = ucm_square_checkout::settlement( $order );

		$this->assertSame( 0.01, $settlement['amount'] );
	}

	public function test_several_tenders_are_added_up() {
		$order = $this->paid_order( array(
			'tenders' => array(
				array( 'id' => 'tender_1', 'amount_money' => array( 'amount' => 4000, 'currency' => 'USD' ), 'card_details' => array( 'status' => 'CAPTURED' ) ),
				array( 'id' => 'tender_2', 'amount_money' => array( 'amount' => 6000, 'currency' => 'USD' ), 'card_details' => array( 'status' => 'CAPTURED' ) ),
			),
		) );

		$this->assertSame( 100.00, ucm_square_checkout::settlement( $order )['amount'] );
	}

	public function test_an_authorised_but_uncaptured_tender_does_not_count() {
		$order = $this->paid_order( array(
			'tenders' => array(
				array( 'id' => 'tender_1', 'amount_money' => array( 'amount' => 10000, 'currency' => 'USD' ), 'card_details' => array( 'status' => 'AUTHORIZED' ) ),
			),
		) );

		$settlement = ucm_square_checkout::settlement( $order );

		$this->assertFalse( $settlement['paid'] );
		$this->assertSame( 0.0, $settlement['amount'] );
	}

	public function test_an_open_order_is_not_paid() {
		$settlement = ucm_square_checkout::settlement( $this->paid_order( array( 'state' => 'OPEN' ) ) );

		$this->assertFalse( $settlement['paid'] );
	}

	public function test_an_order_with_no_tenders_is_not_paid() {
		$settlement = ucm_square_checkout::settlement( $this->paid_order( array( 'tenders' => array() ) ) );

		$this->assertFalse( $settlement['paid'] );
		$this->assertSame( 'order_abc', $settlement['transaction_id'], 'falls back to the order id rather than having no reference at all' );
	}

	public function test_a_zero_decimal_currency_settles_without_being_divided() {
		$order = array(
			'id'           => 'order_jp',
			'reference_id' => '7',
			'state'        => 'COMPLETED',
			'tenders'      => array(
				array( 'id' => 't', 'amount_money' => array( 'amount' => 1000, 'currency' => 'JPY' ), 'card_details' => array( 'status' => 'CAPTURED' ) ),
			),
		);

		$this->assertSame( 1000.0, ucm_square_checkout::settlement( $order )['amount'] );
	}

	public function test_rubbish_does_not_fatal() {
		$settlement = ucm_square_checkout::settlement( 'not an order' );

		$this->assertFalse( $settlement['paid'] );
		$this->assertSame( 0.0, $settlement['amount'] );
	}
}
