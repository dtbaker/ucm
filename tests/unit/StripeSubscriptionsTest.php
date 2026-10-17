<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_paymethod_stripe/subscriptions.php';

/**
 * The parts of the Stripe subscription flow that are pure functions.
 *
 * The period mapping is the one that matters. A UCM subscription is three independent
 * numbers and a Stripe price recurs on one unit, so the only safe answers are "here is
 * the exact equivalent" and "there isn't one". The code this replaced had a third answer
 * - a month is thirty days, a year is three hundred and sixty five - which billed on a
 * schedule that drifted away from UCM's own a little further every cycle.
 */
class StripeSubscriptionsTest extends TestCase
{
	public function test_single_units_map_exactly() {
		$monthly = ucm_stripe_subscriptions::period( 0, 1, 0 );
		$this->assertTrue( $monthly['ok'] );
		$this->assertSame( 'month', $monthly['interval'] );
		$this->assertSame( 1, $monthly['interval_count'] );

		$weekly = ucm_stripe_subscriptions::period( 7, 0, 0 );
		$this->assertTrue( $weekly['ok'] );
		$this->assertSame( 'day', $weekly['interval'] );
		$this->assertSame( 7, $weekly['interval_count'] );

		$yearly = ucm_stripe_subscriptions::period( 0, 0, 1 );
		$this->assertTrue( $yearly['ok'] );
		$this->assertSame( 'year', $yearly['interval'] );
		$this->assertSame( 1, $yearly['interval_count'] );

		$quarterly = ucm_stripe_subscriptions::period( 0, 3, 0 );
		$this->assertTrue( $quarterly['ok'] );
		$this->assertSame( 'month', $quarterly['interval'] );
		$this->assertSame( 3, $quarterly['interval_count'] );
	}

	/**
	 * The drift bug, stated as a test. One month and fifteen days used to go to Stripe as
	 * forty five days while UCM advanced by a calendar month and fifteen days.
	 */
	public function test_a_mixed_period_is_refused_rather_than_approximated() {
		$mixed = ucm_stripe_subscriptions::period( 15, 1, 0 );
		$this->assertFalse( $mixed['ok'] );
		$this->assertSame( 'mixed_units', $mixed['reason'] );
		$this->assertNotSame( '', $mixed['message'] );
		$this->assertArrayNotHasKey( 'interval', $mixed );

		$also_mixed = ucm_stripe_subscriptions::period( 0, 6, 1 );
		$this->assertFalse( $also_mixed['ok'] );
		$this->assertSame( 'mixed_units', $also_mixed['reason'] );
	}

	public function test_a_period_longer_than_stripe_allows_is_refused() {
		// Stripe will not bill less often than once a year.
		$this->assertFalse( ucm_stripe_subscriptions::period( 0, 0, 2 )['ok'] );
		$this->assertSame( 'too_long', ucm_stripe_subscriptions::period( 0, 0, 2 )['reason'] );
		$this->assertSame( 'too_long', ucm_stripe_subscriptions::period( 0, 24, 0 )['reason'] );
		$this->assertSame( 'too_long', ucm_stripe_subscriptions::period( 400, 0, 0 )['reason'] );

		// ...and the boundaries themselves are fine.
		$this->assertTrue( ucm_stripe_subscriptions::period( 365, 0, 0 )['ok'] );
		$this->assertTrue( ucm_stripe_subscriptions::period( 0, 12, 0 )['ok'] );
		$this->assertTrue( ucm_stripe_subscriptions::period( 0, 0, 1 )['ok'] );
	}

	/**
	 * Every subscription in the dev database has days = months = years = 0. UCM's own
	 * form accepts that and bills the same date forever; Stripe has nothing to repeat.
	 */
	public function test_a_subscription_with_no_period_is_refused() {
		$none = ucm_stripe_subscriptions::period( 0, 0, 0 );
		$this->assertFalse( $none['ok'] );
		$this->assertSame( 'no_period', $none['reason'] );
	}

	public function test_negative_numbers_are_not_treated_as_a_period() {
		$this->assertSame( 'no_period', ucm_stripe_subscriptions::period( -5, 0, 0 )['reason'] );
	}

	public function test_a_reference_is_read_whether_or_not_it_was_expanded() {
		$this->assertSame( 'sub_123', ucm_stripe_subscriptions::reference_id( 'sub_123' ) );
		$this->assertSame( 'sub_123', ucm_stripe_subscriptions::reference_id( array( 'id' => 'sub_123', 'object' => 'subscription' ) ) );
		$this->assertSame( '', ucm_stripe_subscriptions::reference_id( null ) );
		$this->assertSame( '', ucm_stripe_subscriptions::reference_id( array( 'object' => 'subscription' ) ) );
	}

	public function test_the_subscription_a_checkout_session_created_is_found() {
		$session = array(
			'id'                  => 'cs_test_1',
			'mode'                => 'subscription',
			'client_reference_id' => '42',
			'customer'            => 'cus_1',
			'subscription'        => 'sub_1',
		);
		$this->assertSame( 'sub_1', ucm_stripe_subscriptions::session_subscription_id( $session ) );
		$this->assertSame( 'cus_1', ucm_stripe_subscriptions::session_customer_id( $session ) );

		$one_off = array( 'id' => 'cs_test_2', 'mode' => 'payment', 'payment_intent' => 'pi_1' );
		$this->assertSame( '', ucm_stripe_subscriptions::session_subscription_id( $one_off ) );
	}

	/**
	 * Stripe moved this field. Up to 2025-03-31 an Invoice carried `subscription` at the
	 * top level; the Basil versions that stripe-php 17 pins put it under
	 * `parent.subscription_details.subscription`. Reading only the old place would mean
	 * every renewal webhook silently matched nothing - the gateway charges the customer
	 * and UCM records no payment, which is the exact failure this whole pass exists to
	 * stop.
	 */
	public function test_a_renewal_invoice_names_its_subscription_in_either_api_shape() {
		$basil = array(
			'id'     => 'in_1',
			'parent' => array(
				'type'                  => 'subscription_details',
				'subscription_details'  => array( 'subscription' => 'sub_1' ),
			),
		);
		$this->assertSame( 'sub_1', ucm_stripe_subscriptions::invoice_subscription_id( $basil ) );

		$pre_basil = array( 'id' => 'in_1', 'subscription' => 'sub_1' );
		$this->assertSame( 'sub_1', ucm_stripe_subscriptions::invoice_subscription_id( $pre_basil ) );

		$expanded = array( 'id' => 'in_1', 'subscription' => array( 'id' => 'sub_1' ) );
		$this->assertSame( 'sub_1', ucm_stripe_subscriptions::invoice_subscription_id( $expanded ) );

		$not_a_subscription = array( 'id' => 'in_1', 'parent' => null );
		$this->assertSame( '', ucm_stripe_subscriptions::invoice_subscription_id( $not_a_subscription ) );
	}

	/**
	 * The first charge of a subscription arrives twice: once as the Checkout Session that
	 * created it, once as an invoice. Acting on both would generate a second UCM invoice
	 * for a period nobody has reached.
	 */
	public function test_the_first_charge_of_a_subscription_is_recognised() {
		$this->assertTrue( ucm_stripe_subscriptions::is_first_subscription_invoice( array( 'billing_reason' => 'subscription_create' ) ) );
		$this->assertFalse( ucm_stripe_subscriptions::is_first_subscription_invoice( array( 'billing_reason' => 'subscription_cycle' ) ) );
		$this->assertFalse( ucm_stripe_subscriptions::is_first_subscription_invoice( array( 'billing_reason' => 'subscription_update' ) ) );
		$this->assertFalse( ucm_stripe_subscriptions::is_first_subscription_invoice( array( 'id' => 'in_1' ) ) );
	}

	public function test_stripe_objects_are_read_the_same_as_decoded_json() {
		$session       = new stdClass();
		$session->mode = 'subscription';
		$session->subscription = 'sub_obj';
		$customer      = new stdClass();
		$customer->id  = 'cus_obj';
		$session->customer = $customer;

		$this->assertSame( 'sub_obj', ucm_stripe_subscriptions::session_subscription_id( $session ) );
		$this->assertSame( 'cus_obj', ucm_stripe_subscriptions::session_customer_id( $session ) );
	}
}
