<?php

/**
 * Turning a UCM subscription period into a PayPal subscription period.
 *
 * Website Payments Standard takes a single unit (`t3`) and a count (`p3`), with its own
 * ceiling per unit. A UCM subscription is three independent numbers, so most combinations
 * have no faithful PayPal equivalent.
 *
 * The old code added the units up as days, counting a month as 30 and a year as 365. A
 * subscription of one month and fifteen days was sent to PayPal as 45 days while UCM
 * advanced by a calendar month and fifteen days; the two schedules drift apart by several
 * days a year and then disagree about which period has been paid for. Nothing errors -
 * the money is simply billed on the wrong day, and increasingly so.
 *
 * It also never checked PayPal's ceilings, so a subscription of, say, every 120 days was
 * sent as `p3=120 t3=D` and PayPal rejected the button with its own error page, in front
 * of the customer.
 *
 * Both are refused here instead. A period PayPal cannot express exactly means the payer
 * is not offered automatic payments; they pay this invoice once and UCM's own scheduled
 * job generates the next one. That is how Square works and it is the safest of the three.
 *
 * Pure functions over integers. No app, no network.
 */
class ucm_paypal_recurring
{
	/**
	 * The largest `p3` PayPal accepts for each `t3`.
	 *
	 * @return array
	 */
	public static function period_limits() {
		return array(
			'D' => 90,
			'W' => 52,
			'M' => 24,
			'Y' => 5,
		);
	}

	/**
	 * Can PayPal bill this period, and if so how?
	 *
	 * @param int $days
	 * @param int $months
	 * @param int $years
	 *
	 * @return array ok, and on success unit ( t3 ) + count ( p3 ); on failure reason +
	 *               message, written to be shown to a merchant
	 */
	public static function period( $days, $months, $years ) {
		$units = array();
		if ( (int) $days > 0 ) {
			$units['day'] = (int) $days;
		}
		if ( (int) $months > 0 ) {
			$units['month'] = (int) $months;
		}
		if ( (int) $years > 0 ) {
			$units['year'] = (int) $years;
		}

		if ( ! count( $units ) ) {
			return array(
				'ok'      => false,
				'reason'  => 'no_period',
				'message' => 'This subscription has no billing period, so there is nothing for PayPal to repeat.',
			);
		}

		if ( count( $units ) > 1 ) {
			return array(
				'ok'      => false,
				'reason'  => 'mixed_units',
				'message' => 'PayPal bills on one unit of time. This subscription mixes ' . implode( ' and ', array_keys( $units ) ) . 's, which PayPal cannot repeat exactly, so automatic payments are not offered for it.',
			);
		}

		$codes  = array( 'day' => 'D', 'month' => 'M', 'year' => 'Y' );
		$name   = key( $units );
		$count  = current( $units );
		$unit   = $codes[ $name ];
		$limits = self::period_limits();

		if ( $count > $limits[ $unit ] ) {
			return array(
				'ok'      => false,
				'reason'  => 'too_long',
				'message' => 'PayPal will not bill less often than every ' . $limits[ $unit ] . ' ' . $name . 's. This subscription bills every ' . $count . ' ' . $name . 's, so automatic payments are not offered for it.',
			);
		}

		return array(
			'ok'      => true,
			'reason'  => '',
			'message' => '',
			'unit'    => $unit,
			'count'   => $count,
		);
	}
}
