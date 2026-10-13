<?php

/**
 * Turning a UCM subscription period into a Stripe recurring price.
 *
 * A UCM subscription is three independent numbers - days, months and years - and any
 * combination of them is accepted by the subscription form. A Stripe price recurs on
 * exactly one unit with a count, so most combinations have no faithful Stripe equivalent.
 *
 * The old code papered over that by adding the units together as days, with a month
 * counted as 30 and a year as 365. A subscription of one month and fifteen days went to
 * Stripe as 45 days while UCM itself advanced by a calendar month and fifteen days, so
 * the two schedules drifted apart over the year and then disagreed about which period had
 * been paid for. That is the worst shape a billing bug can take - nothing errors, the
 * numbers are simply wrong, and only a customer comparing statements would ever notice.
 *
 * So this refuses instead. When a period cannot be expressed exactly, the payer is not
 * offered automatic payments at all and pays this invoice as a one-off; UCM's own
 * scheduled job then generates the next invoice, which is how Square works and is the
 * safest of the three gateways. Nothing is silently approximated.
 *
 * Pure functions over integers. No app, no network.
 */
class ucm_stripe_subscriptions
{
	/**
	 * The largest interval_count Stripe accepts for each interval.
	 *
	 * Stripe's rule is that a recurring interval may not exceed one year, expressed per
	 * unit. Anything longer has to be refused rather than rounded.
	 *
	 * @return array
	 */
	public static function interval_limits() {
		return array(
			'day'   => 365,
			'week'  => 52,
			'month' => 12,
			'year'  => 1,
		);
	}

	/**
	 * Can Stripe bill this period, and if so how?
	 *
	 * @param int $days
	 * @param int $months
	 * @param int $years
	 *
	 * @return array ok, and on success interval + interval_count; on failure reason +
	 *               message, where the message is written to be shown to a merchant
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
				'message' => 'This subscription has no billing period, so there is nothing for Stripe to repeat.',
			);
		}

		if ( count( $units ) > 1 ) {
			return array(
				'ok'      => false,
				'reason'  => 'mixed_units',
				'message' => 'Stripe bills on one unit of time. This subscription mixes ' . implode( ' and ', array_keys( $units ) ) . 's, which Stripe cannot repeat exactly, so automatic payments are not offered for it.',
			);
		}

		$interval = key( $units );
		$count    = current( $units );
		$limits   = self::interval_limits();

		if ( $count > $limits[ $interval ] ) {
			return array(
				'ok'      => false,
				'reason'  => 'too_long',
				'message' => 'Stripe will not bill less often than once a year. This subscription bills every ' . $count . ' ' . $interval . 's, so automatic payments are not offered for it.',
			);
		}

		return array(
			'ok'             => true,
			'reason'         => '',
			'message'        => '',
			'interval'       => $interval,
			'interval_count' => $count,
		);
	}

	/**
	 * The Stripe subscription id a Checkout Session ended up creating.
	 *
	 * @param object|array $session
	 *
	 * @return string empty when the session was not a subscription
	 */
	public static function session_subscription_id( $session ) {
		return self::reference_id( self::field( $session, 'subscription' ) );
	}

	/**
	 * The Stripe customer id a Checkout Session ended up using.
	 *
	 * @param object|array $session
	 *
	 * @return string
	 */
	public static function session_customer_id( $session ) {
		return self::reference_id( self::field( $session, 'customer' ) );
	}

	/**
	 * Which subscription a Stripe invoice belongs to.
	 *
	 * The API moved this. Up to 2025-03-31 an Invoice carried `subscription` at the top
	 * level; from the Basil versions - which is what stripe-php 17 pins - it lives at
	 * `parent.subscription_details.subscription`. Both are read, newest first, so this
	 * keeps working whichever version an account's older webhook deliveries were cut on.
	 *
	 * @param object|array $invoice
	 *
	 * @return string
	 */
	public static function invoice_subscription_id( $invoice ) {
		$parent = self::field( $invoice, 'parent' );
		if ( $parent ) {
			$details = self::field( $parent, 'subscription_details' );
			if ( $details ) {
				$id = self::reference_id( self::field( $details, 'subscription' ) );
				if ( $id !== '' ) {
					return $id;
				}
			}
		}

		return self::reference_id( self::field( $invoice, 'subscription' ) );
	}

	/**
	 * Is this Stripe invoice the first charge of a brand new subscription?
	 *
	 * The first charge is already recorded against the invoice the payer was looking at,
	 * by the Checkout Session that created the subscription. Treating it as a renewal as
	 * well would generate a second UCM invoice for a period nobody has reached yet.
	 *
	 * @param object|array $invoice
	 *
	 * @return bool
	 */
	public static function is_first_subscription_invoice( $invoice ) {
		$reason = self::field( $invoice, 'billing_reason' );
		if ( ! is_string( $reason ) ) {
			$parent = self::field( $invoice, 'parent' );
			$reason = $parent ? self::field( $parent, 'billing_reason' ) : '';
		}

		return in_array( (string) $reason, array( 'subscription_create', 'subscription_threshold' ), true );
	}

	/**
	 * Read a property off either an object or an array. Stripe objects behave as both,
	 * but a decoded webhook body is plain arrays and the tests use those.
	 *
	 * @param object|array $thing
	 * @param string       $key
	 *
	 * @return mixed null when absent
	 */
	private static function field( $thing, $key ) {
		if ( is_array( $thing ) ) {
			return isset( $thing[ $key ] ) ? $thing[ $key ] : null;
		}
		if ( is_object( $thing ) ) {
			// Stripe objects answer isset() through ArrayAccess, so this covers both the
			// expanded and the unexpanded shape.
			return isset( $thing->{$key} ) ? $thing->{$key} : null;
		}

		return null;
	}

	/**
	 * A Stripe reference is either an id or the expanded object that id points at.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function reference_id( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		}
		$id = self::field( $value, 'id' );

		return is_string( $id ) ? $id : '';
	}
}
