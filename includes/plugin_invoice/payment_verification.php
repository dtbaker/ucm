<?php

/**
 * The checks every payment gateway should have been doing and none of them did.
 *
 * Six online gateways shipped with six different subsets of "is this notification real,
 * is it for this invoice, is the amount right, have I already processed it". This class
 * is the one answer to the middle two questions, plus the duplicate check. It is
 * deliberately a pure function over two arrays: no database, no config, no network, no
 * globals, so it can be unit tested without the application around it.
 *
 * Authenticity - a provider signature or an authoritative re-fetch - stays with the
 * gateway, because only the gateway knows how its provider signs things. Everything
 * after that point is the same job for all of them, and it lives here.
 *
 * @see module_invoice::verify_and_record() for the half that touches the database.
 */
class ucm_payment_verification
{
	/**
	 * Money is stored as decimal(10,2), so anything under half a cent is rounding.
	 */
	const AMOUNT_TOLERANCE = 0.005;

	/**
	 * Decide whether a provider notification may be recorded as a payment.
	 *
	 * $expected describes what UCM is waiting for:
	 *   amount    float   the pending invoice_payment row's amount, fees included
	 *   currency  string  ISO code of the invoice currency, e.g. 'USD'
	 *   sandbox   bool    true when the gateway is configured for test mode
	 *
	 * $payload describes what the provider says happened, normalised by the gateway:
	 *   amount          float   what was actually paid
	 *   currency        string  ISO code the provider charged in
	 *   transaction_id  string  the provider's id for this payment, for de-duplication
	 *   paid            bool    true when the provider considers the money captured
	 *   test            bool    true when the provider says this was a test transaction
	 *
	 * The return is always the same shape:
	 *   ok       bool    may this be recorded
	 *   code     string  machine readable reason, 'ok' on success
	 *   message  string  one line, safe to put in a log, never shown to a payer
	 *   amount   float   the amount to record - always what was *paid*, never what was owed
	 *   flags    array   non-fatal oddities worth an admin's attention
	 *
	 * @param array $expected
	 * @param array $payload
	 *
	 * @return array
	 */
	public static function check( $expected, $payload ) {
		$expected = is_array( $expected ) ? $expected : array();
		$payload  = is_array( $payload ) ? $payload : array();

		$expected_amount   = isset( $expected['amount'] ) ? (float) $expected['amount'] : 0;
		$expected_currency = isset( $expected['currency'] ) ? strtoupper( trim( (string) $expected['currency'] ) ) : '';
		$sandbox           = ! empty( $expected['sandbox'] );

		$paid_amount   = isset( $payload['amount'] ) ? (float) $payload['amount'] : 0;
		$paid_currency = isset( $payload['currency'] ) ? strtoupper( trim( (string) $payload['currency'] ) ) : '';
		$transaction   = isset( $payload['transaction_id'] ) ? trim( (string) $payload['transaction_id'] ) : '';

		$flags = array();

		// A notification with no provider reference cannot be de-duplicated, and a
		// payment that cannot be de-duplicated can be replayed. Refuse it.
		if ( $transaction === '' ) {
			return self::fail( 'missing_transaction', 'The provider sent no transaction reference, so this payment cannot be de-duplicated.' );
		}

		// The provider has to actually say the money moved. Gateways normalise their own
		// status vocabulary into this one boolean, so "pending" and "authorised but not
		// captured" never reach here as true.
		if ( empty( $payload['paid'] ) ) {
			return self::fail( 'not_paid', 'The provider has not confirmed this payment as captured.' );
		}

		// A test transaction recorded against a live gateway is the Authorize.Net
		// x_test_request hole: an approval response with no money behind it.
		if ( ! empty( $payload['test'] ) && ! $sandbox ) {
			return self::fail( 'test_transaction', 'The provider reported a test transaction while this payment method is in live mode.' );
		}

		if ( $paid_amount <= 0 ) {
			return self::fail( 'zero_amount', 'The provider reported an amount of zero or less.' );
		}

		// A payment in the wrong currency is worth face value in whatever the provider
		// decided to charge. Never record it against the invoice.
		if ( $expected_currency !== '' && $paid_currency !== '' && $expected_currency !== $paid_currency ) {
			return self::fail( 'currency_mismatch', 'Paid in ' . $paid_currency . ' but the invoice is in ' . $expected_currency . '.' );
		}
		if ( $expected_currency !== '' && $paid_currency === '' ) {
			$flags[] = 'currency_unknown';
		}

		// The amount is recorded as paid, not as owed. Under- and overpayment are real
		// things a payer can do at a hosted checkout, so they are flagged rather than
		// refused - but the number that lands in the database is always the one the
		// provider says it took.
		$difference = $paid_amount - $expected_amount;
		if ( $difference < - self::AMOUNT_TOLERANCE ) {
			$flags[] = 'underpaid';
		} elseif ( $difference > self::AMOUNT_TOLERANCE ) {
			$flags[] = 'overpaid';
		}

		return array(
			'ok'      => true,
			'code'    => 'ok',
			'message' => self::describe_amount( $paid_amount, $expected_amount, $flags ),
			'amount'  => $paid_amount,
			'flags'   => $flags,
		);
	}

	/**
	 * Has this provider transaction already been recorded against this payment row?
	 *
	 * Providers retry. PayPal retries an IPN up to 25 times over three days. The second
	 * delivery must not record a second payment, and must not be treated as an error
	 * either - it is the provider doing exactly what it documents.
	 *
	 * @param array  $payment_row    the invoice_payment row, as stored
	 * @param string $transaction_id the provider reference from this notification
	 *
	 * @return array ok=false with code 'duplicate' or 'already_paid' when it has, ok=true when it is new
	 */
	public static function check_not_replayed( $payment_row, $transaction_id ) {
		$payment_row    = is_array( $payment_row ) ? $payment_row : array();
		$transaction_id = trim( (string) $transaction_id );

		$date_paid = isset( $payment_row['date_paid'] ) ? trim( (string) $payment_row['date_paid'] ) : '';
		$recorded  = isset( $payment_row['other_id'] ) ? trim( (string) $payment_row['other_id'] ) : '';

		$is_paid = $date_paid !== '' && $date_paid !== '0000-00-00';

		if ( ! $is_paid ) {
			return array(
				'ok'      => true,
				'code'    => 'ok',
				'message' => '',
				'amount'  => 0,
				'flags'   => array(),
			);
		}

		if ( $recorded !== '' && hash_equals( $recorded, $transaction_id ) ) {
			return self::fail( 'duplicate', 'This provider transaction has already been recorded; nothing to do.' );
		}

		// Paid, but by something else. Recording over it would silently lose a payment,
		// so stop and let a human look.
		return self::fail( 'already_paid', 'This payment is already marked paid against a different provider transaction.' );
	}

	/**
	 * Is this payment row the one the notification is talking about?
	 *
	 * A gateway's notification endpoint is public. Nothing stops a Stripe webhook
	 * carrying a PayPal payment's id, or an invoice id that belongs to somebody else's
	 * payment. The reference the provider echoes back is only trustworthy once it has
	 * been checked against the row it claims to be for.
	 *
	 * @param array $payment_row the invoice_payment row, as stored
	 * @param array $claims      'invoice_id' and/or 'method_key' the notification asserts
	 *
	 * @return array
	 */
	public static function check_belongs_to( $payment_row, $claims ) {
		$payment_row = is_array( $payment_row ) ? $payment_row : array();
		$claims      = is_array( $claims ) ? $claims : array();

		if ( empty( $payment_row['invoice_payment_id'] ) ) {
			return self::fail( 'unknown_payment', 'No payment record matches this notification.' );
		}

		if ( ! empty( $claims['invoice_id'] ) && (int) $claims['invoice_id'] !== (int) $payment_row['invoice_id'] ) {
			return self::fail( 'wrong_invoice', 'This payment belongs to a different invoice than the notification claims.' );
		}

		// An empty stored key means the row predates method_key, so there is nothing to
		// compare against and the gateway's own reference has to carry it.
		$stored_key = isset( $payment_row['method_key'] ) ? trim( (string) $payment_row['method_key'] ) : '';
		if ( ! empty( $claims['method_key'] ) && $stored_key !== '' && $stored_key !== $claims['method_key'] ) {
			return self::fail( 'wrong_method', 'This payment was started with a different payment method.' );
		}

		return array(
			'ok'      => true,
			'code'    => 'ok',
			'message' => '',
			'amount'  => 0,
			'flags'   => array(),
		);
	}

	/**
	 * @param string $code
	 * @param string $message
	 *
	 * @return array
	 */
	private static function fail( $code, $message ) {
		return array(
			'ok'      => false,
			'code'    => $code,
			'message' => $message,
			'amount'  => 0,
			'flags'   => array(),
		);
	}

	/**
	 * @param float $paid
	 * @param float $owed
	 * @param array $flags
	 *
	 * @return string
	 */
	private static function describe_amount( $paid, $owed, $flags ) {
		if ( in_array( 'underpaid', $flags, true ) ) {
			return 'Paid ' . number_format( $paid, 2 ) . ' against ' . number_format( $owed, 2 ) . ' requested; recording the amount paid.';
		}
		if ( in_array( 'overpaid', $flags, true ) ) {
			return 'Paid ' . number_format( $paid, 2 ) . ', more than the ' . number_format( $owed, 2 ) . ' requested; recording the amount paid.';
		}

		return 'Paid ' . number_format( $paid, 2 ) . ' as expected.';
	}
}
