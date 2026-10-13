<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_paymethod_paypal/recurring.php';

/**
 * Turning a UCM subscription period into a PayPal subscription period.
 *
 * The case that matters is the one that used to go through quietly: a period PayPal
 * cannot express, converted into days by counting a month as thirty and a year as three
 * hundred and sixty five, so PayPal and UCM billed on schedules that drifted apart.
 */
class PaypalRecurringTest extends TestCase
{
	public function test_single_units_map_exactly() {
		$monthly = ucm_paypal_recurring::period( 0, 1, 0 );
		$this->assertTrue( $monthly['ok'] );
		$this->assertSame( 'M', $monthly['unit'] );
		$this->assertSame( 1, $monthly['count'] );

		$fortnightly = ucm_paypal_recurring::period( 14, 0, 0 );
		$this->assertTrue( $fortnightly['ok'] );
		$this->assertSame( 'D', $fortnightly['unit'] );
		$this->assertSame( 14, $fortnightly['count'] );

		$yearly = ucm_paypal_recurring::period( 0, 0, 1 );
		$this->assertTrue( $yearly['ok'] );
		$this->assertSame( 'Y', $yearly['unit'] );
		$this->assertSame( 1, $yearly['count'] );
	}

	/**
	 * The drift bug. One month and fifteen days used to be sent to PayPal as 45 D while
	 * UCM advanced by a calendar month and fifteen days - different by a day or two every
	 * cycle, and the two then disagree about which period has been paid for.
	 */
	public function test_a_mixed_period_is_refused_rather_than_approximated() {
		$mixed = ucm_paypal_recurring::period( 15, 1, 0 );
		$this->assertFalse( $mixed['ok'] );
		$this->assertSame( 'mixed_units', $mixed['reason'] );
		$this->assertArrayNotHasKey( 'unit', $mixed );

		$this->assertSame( 'mixed_units', ucm_paypal_recurring::period( 0, 6, 1 )['reason'] );
		$this->assertSame( 'mixed_units', ucm_paypal_recurring::period( 3, 0, 1 )['reason'] );
	}

	/**
	 * PayPal has its own ceiling per unit and rejects the button if you exceed it - in
	 * front of the customer, on PayPal's own error page. The old code never checked.
	 */
	public function test_a_period_past_paypals_ceiling_is_refused() {
		$this->assertSame( 'too_long', ucm_paypal_recurring::period( 120, 0, 0 )['reason'] );
		$this->assertSame( 'too_long', ucm_paypal_recurring::period( 0, 36, 0 )['reason'] );
		$this->assertSame( 'too_long', ucm_paypal_recurring::period( 0, 0, 10 )['reason'] );

		// The ceilings themselves are fine.
		$this->assertTrue( ucm_paypal_recurring::period( 90, 0, 0 )['ok'] );
		$this->assertTrue( ucm_paypal_recurring::period( 0, 24, 0 )['ok'] );
		$this->assertTrue( ucm_paypal_recurring::period( 0, 0, 5 )['ok'] );
	}

	public function test_a_subscription_with_no_period_is_refused() {
		$none = ucm_paypal_recurring::period( 0, 0, 0 );
		$this->assertFalse( $none['ok'] );
		$this->assertSame( 'no_period', $none['reason'] );
	}

	public function test_every_refusal_carries_something_to_show_a_merchant() {
		foreach ( array( array( 0, 0, 0 ), array( 15, 1, 0 ), array( 120, 0, 0 ) ) as $period ) {
			$result = ucm_paypal_recurring::period( $period[0], $period[1], $period[2] );
			$this->assertFalse( $result['ok'] );
			$this->assertNotSame( '', trim( $result['message'] ) );
		}
	}

	/**
	 * Stripe and PayPal both bill on one unit, but their ceilings differ - twenty four
	 * months is fine at PayPal and too long for Stripe. A subscription can therefore be
	 * automatic on one gateway and not the other, which is why each has its own mapping
	 * rather than a shared one.
	 */
	public function test_the_two_gateways_do_not_share_a_ceiling() {
		$this->assertTrue( ucm_paypal_recurring::period( 0, 24, 0 )['ok'] );
		$this->assertSame( 90, ucm_paypal_recurring::period_limits()['D'] );
	}
}
