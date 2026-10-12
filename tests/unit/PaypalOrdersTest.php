<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_invoice/payment_verification.php';
require_once dirname( __DIR__, 2 ) . '/includes/plugin_paymethod_paypal/orders.php';

/**
 * The parts of the PayPal Orders v2 client that are pure functions.
 *
 * Finding the capture inside an order response is the one that matters: an order has
 * purchase units, a unit has payments, payments have captures, and only the capture knows
 * what money actually moved. Reading the amount from the wrong level is how a gateway
 * records what was asked for rather than what was paid.
 */
class PaypalOrdersTest extends TestCase
{
	private function captured_order( $overrides = array() ) {
		return array(
			'id'             => '5O190127TN364715T',
			'status'         => 'COMPLETED',
			'purchase_units' => array(
				array(
					'reference_id' => '42',
					'custom_id'    => '42',
					'amount'       => array( 'currency_code' => 'USD', 'value' => '100.00' ),
					'payments'     => array(
						'captures' => array(
							array(
								'id'     => '3C679366HH908993F',
								'status' => isset( $overrides['status'] ) ? $overrides['status'] : 'COMPLETED',
								'amount' => array(
									'currency_code' => 'USD',
									'value'         => isset( $overrides['value'] ) ? $overrides['value'] : '100.00',
								),
							),
						),
					),
				),
			),
		);
	}

	public function test_the_completed_capture_is_found() {
		$capture = ucm_paypal_orders::completed_capture( $this->captured_order() );

		$this->assertIsArray( $capture );
		$this->assertSame( '3C679366HH908993F', $capture['id'] );
		$this->assertSame( '100.00', $capture['amount']['value'] );
		$this->assertSame( '42', $capture['_custom_id'] );
	}

	/**
	 * A payer who changes the amount at PayPal produces a capture that does not match the
	 * order. The capture is what gets recorded, and verify_and_record() flags the gap.
	 */
	public function test_the_capture_amount_is_used_not_the_order_amount() {
		$capture = ucm_paypal_orders::completed_capture( $this->captured_order( array( 'value' => '1.00' ) ) );

		$this->assertSame( '1.00', $capture['amount']['value'] );
	}

	public function test_a_pending_capture_is_not_treated_as_completed() {
		$this->assertFalse( ucm_paypal_orders::completed_capture( $this->captured_order( array( 'status' => 'PENDING' ) ) ) );
	}

	public function test_an_approved_but_uncaptured_order_has_no_capture() {
		$order = array(
			'id'             => '5O190127TN364715T',
			'status'         => 'APPROVED',
			'purchase_units' => array( array( 'reference_id' => '42', 'amount' => array( 'currency_code' => 'USD', 'value' => '100.00' ) ) ),
		);

		$this->assertFalse( ucm_paypal_orders::completed_capture( $order ) );
	}

	public function test_a_unit_level_custom_id_is_picked_up_when_the_capture_has_none() {
		$order = $this->captured_order();
		unset( $order['purchase_units'][0]['payments']['captures'][0]['custom_id'] );

		$capture = ucm_paypal_orders::completed_capture( $order );

		$this->assertSame( '42', $capture['_custom_id'] );
	}

	public function test_rubbish_does_not_fatal() {
		$this->assertFalse( ucm_paypal_orders::completed_capture( array() ) );
		$this->assertFalse( ucm_paypal_orders::completed_capture( 'not an order' ) );
		$this->assertFalse( ucm_paypal_orders::completed_capture( array( 'purchase_units' => 'nope' ) ) );
	}

	/**
	 * PayPal rejects an amount that does not have exactly the right number of decimal
	 * places for the currency.
	 */
	public function test_amounts_are_formatted_the_way_paypal_insists() {
		$this->assertSame( '100.00', ucm_paypal_orders::format_amount( 100, 'USD' ) );
		$this->assertSame( '1.15', ucm_paypal_orders::format_amount( 1.15, 'usd' ) );
		$this->assertSame( '1234.50', ucm_paypal_orders::format_amount( 1234.5, 'EUR' ) );
		$this->assertSame( '1000', ucm_paypal_orders::format_amount( 1000, 'JPY' ) );
	}

	public function test_amounts_never_come_out_with_a_thousands_separator() {
		$this->assertStringNotContainsString( ',', ucm_paypal_orders::format_amount( 1234567.89, 'USD' ) );
	}

	public function test_the_sandbox_and_live_hosts_are_the_documented_ones() {
		$this->assertSame( 'https://api-m.sandbox.paypal.com', ucm_paypal_orders::api_base( true ) );
		$this->assertSame( 'https://api-m.paypal.com', ucm_paypal_orders::api_base( false ) );
	}

	/**
	 * Verification needs five transmission headers and a webhook id. Missing any of them
	 * must fail rather than fall through to a network call that might succeed.
	 */
	public function test_webhook_verification_refuses_without_a_webhook_id() {
		$headers = array(
			'paypal-auth-algo'         => 'SHA256withRSA',
			'paypal-cert-url'          => 'https://api.paypal.com/v1/notifications/certs/CERT-1',
			'paypal-transmission-id'   => 'abc',
			'paypal-transmission-sig'  => 'sig',
			'paypal-transmission-time' => '2026-10-13T00:00:00Z',
		);

		$this->assertFalse( ucm_paypal_orders::verify_webhook( array( 'client_id' => 'x', 'secret' => 'y', 'sandbox' => true ), '', $headers, '{}' ) );
	}

	public function test_webhook_verification_refuses_with_a_header_missing() {
		$headers = array(
			'paypal-auth-algo'       => 'SHA256withRSA',
			'paypal-transmission-id' => 'abc',
		);

		$this->assertFalse( ucm_paypal_orders::verify_webhook( array( 'client_id' => 'x', 'secret' => 'y', 'sandbox' => true ), 'WH-123', $headers, '{}' ) );
	}
}
