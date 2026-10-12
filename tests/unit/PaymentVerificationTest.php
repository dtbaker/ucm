<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_invoice/payment_verification.php';

/**
 * The shared payment checks.
 *
 * Every case here is a bug that shipped in at least one of the six online gateways. The
 * Authorize.Net ones in particular are regression tests for a free-payment path: a
 * client-controlled amount and a client-controlled x_test_request, neither of which was
 * ever compared against what the invoice said was owed.
 */
class PaymentVerificationTest extends TestCase
{
	private function expected( $overrides = array() ) {
		return $overrides + array(
			'amount'   => 100.00,
			'currency' => 'USD',
			'sandbox'  => false,
		);
	}

	private function payload( $overrides = array() ) {
		return $overrides + array(
			'amount'         => 100.00,
			'currency'       => 'USD',
			'transaction_id' => 'ch_abc123',
			'paid'           => true,
			'test'           => false,
		);
	}

	public function test_a_matching_payment_is_recorded() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload() );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 'ok', $result['code'] );
		$this->assertSame( 100.00, $result['amount'] );
		$this->assertSame( array(), $result['flags'] );
	}

	public function test_half_a_cent_of_rounding_is_not_a_discrepancy() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'amount' => 100.001 ) ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( array(), $result['flags'] );
	}

	public function test_a_notification_without_a_transaction_reference_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'transaction_id' => '' ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'missing_transaction', $result['code'] );
	}

	public function test_an_uncaptured_payment_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'paid' => false ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'not_paid', $result['code'] );
	}

	/**
	 * Authorize.Net accepted x_test_request=TRUE from the payer's own form post and
	 * returned an approval with no money behind it. The invoice was then marked paid.
	 */
	public function test_a_test_transaction_against_a_live_gateway_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'test' => true ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'test_transaction', $result['code'] );
	}

	public function test_a_test_transaction_against_a_sandbox_gateway_is_fine() {
		$result = ucm_payment_verification::check(
			$this->expected( array( 'sandbox' => true ) ),
			$this->payload( array( 'test' => true ) )
		);

		$this->assertTrue( $result['ok'] );
	}

	public function test_a_zero_payment_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'amount' => 0 ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'zero_amount', $result['code'] );
	}

	public function test_a_negative_payment_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'amount' => - 10 ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'zero_amount', $result['code'] );
	}

	/**
	 * The Authorize.Net amount hole. A payer who posted amount=0.01 got the full invoice
	 * amount stamped as paid, because the pending row's amount was written before the
	 * gateway was contacted and never revisited.
	 */
	public function test_underpayment_records_what_was_paid_and_flags_it() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'amount' => 0.01 ) ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 0.01, $result['amount'], 'the amount recorded must be the amount paid, never the amount owed' );
		$this->assertContains( 'underpaid', $result['flags'] );
	}

	public function test_overpayment_records_what_was_paid_and_flags_it() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'amount' => 150.00 ) ) );

		$this->assertTrue( $result['ok'] );
		$this->assertSame( 150.00, $result['amount'] );
		$this->assertContains( 'overpaid', $result['flags'] );
	}

	/**
	 * Pay.nl sent no currency at all, so a GBP invoice was charged at face value in the
	 * account default. MultiSafepay printed a warning about EUR and carried on anyway.
	 */
	public function test_a_payment_in_the_wrong_currency_is_refused() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'currency' => 'EUR' ) ) );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'currency_mismatch', $result['code'] );
	}

	public function test_currency_comparison_ignores_case_and_padding() {
		$result = ucm_payment_verification::check(
			$this->expected( array( 'currency' => 'usd' ) ),
			$this->payload( array( 'currency' => ' USD ' ) )
		);

		$this->assertTrue( $result['ok'] );
	}

	public function test_a_provider_that_reports_no_currency_is_allowed_but_flagged() {
		$result = ucm_payment_verification::check( $this->expected(), $this->payload( array( 'currency' => '' ) ) );

		$this->assertTrue( $result['ok'] );
		$this->assertContains( 'currency_unknown', $result['flags'] );
	}

	public function test_an_unpaid_row_has_not_been_replayed() {
		$row = array( 'date_paid' => '0000-00-00', 'other_id' => '' );

		$this->assertTrue( ucm_payment_verification::check_not_replayed( $row, 'ch_abc123' )['ok'] );
	}

	public function test_a_redelivered_notification_is_a_duplicate_not_an_error() {
		$row = array( 'date_paid' => '2026-10-13', 'other_id' => 'ch_abc123' );

		$result = ucm_payment_verification::check_not_replayed( $row, 'ch_abc123' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'duplicate', $result['code'] );
	}

	public function test_a_different_transaction_against_a_paid_row_stops_rather_than_overwrites() {
		$row = array( 'date_paid' => '2026-10-13', 'other_id' => 'ch_abc123' );

		$result = ucm_payment_verification::check_not_replayed( $row, 'ch_something_else' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'already_paid', $result['code'] );
	}

	public function test_a_paid_row_with_no_stored_reference_is_not_overwritten() {
		$row = array( 'date_paid' => '2026-10-13', 'other_id' => '' );

		$result = ucm_payment_verification::check_not_replayed( $row, 'ch_abc123' );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'already_paid', $result['code'] );
	}

	public function test_missing_arrays_do_not_fatal() {
		$result = ucm_payment_verification::check( array(), array() );

		$this->assertFalse( $result['ok'] );
		$this->assertSame( 'missing_transaction', $result['code'] );
	}
}
