<?php

/**
 * Square Checkout, by hand.
 *
 * No SDK, deliberately. square/square ^47 is 21MB and pulls in fourteen packages,
 * symfony/http-foundation among them, and this integration needs exactly two endpoints
 * and one HMAC. UCM ships its vendor folder inside the release archive, so that weight
 * is paid by every one of the downloads, for a saving of about a hundred lines.
 *
 * The one thing worth taking from the SDK is the exact signing rule, and it is copied
 * faithfully below: base64( HMAC-SHA256( notification URL . raw body, signature key ) ).
 *
 * Everything here returns an array and never throws.
 */
class ucm_square_checkout
{
	/**
	 * Pinned, not floating. Square dates its API versions, and an unpinned request gets
	 * whatever the account defaults to - which can change underneath an install that has
	 * not been touched in two years.
	 */
	const API_VERSION = '2026-09-16';

	/**
	 * @param bool $sandbox
	 *
	 * @return string
	 */
	public static function api_base( $sandbox ) {
		return $sandbox ? 'https://connect.squareupsandbox.com' : 'https://connect.squareup.com';
	}

	/**
	 * Create a Square-hosted payment page and return its URL.
	 *
	 * reference_id on the order carries the invoice_payment_id. It comes back on the
	 * order, which is what both the payer's return and the webhook read.
	 *
	 * @param array $credentials access_token, location_id, sandbox
	 * @param array $payment     amount, currency, description, invoice_payment_id, redirect_url
	 *
	 * @return array ok, url, order_id, payment_link_id, error
	 */
	public static function create_payment_link( $credentials, $payment ) {
		$currency = strtoupper( (string) $payment['currency'] );
		$body     = array(
			// Same payment row, same key: a double submit cannot create two links.
			'idempotency_key'  => 'ucm-' . (int) $payment['invoice_payment_id'] . '-' . substr( md5( (string) $payment['amount'] . $currency ), 0, 16 ),
			'order'            => array(
				'location_id'  => (string) $credentials['location_id'],
				'reference_id' => (string) (int) $payment['invoice_payment_id'],
				'line_items'   => array(
					array(
						'name'             => self::trim_to( (string) $payment['description'], 255 ),
						'quantity'         => '1',
						'base_price_money' => array(
							'amount'   => ucm_payment_verification::to_minor_units( $payment['amount'], $currency ),
							'currency' => $currency,
						),
					),
				),
			),
			'checkout_options' => array(
				'redirect_url'             => (string) $payment['redirect_url'],
				'ask_for_shipping_address' => false,
			),
		);

		$response = self::request( $credentials, 'POST', '/v2/online-checkout/payment-links', $body );

		if ( ! $response['ok'] || empty( $response['body']['payment_link']['url'] ) ) {
			return array( 'ok' => false, 'url' => '', 'order_id' => '', 'payment_link_id' => '', 'error' => self::describe_error( $response ) );
		}

		$link = $response['body']['payment_link'];

		return array(
			'ok'              => true,
			'url'             => (string) $link['url'],
			'order_id'        => isset( $link['order_id'] ) ? (string) $link['order_id'] : '',
			'payment_link_id' => isset( $link['id'] ) ? (string) $link['id'] : '',
			'error'           => '',
		);
	}

	/**
	 * Read an order back from Square, authenticated.
	 *
	 * @param array  $credentials
	 * @param string $order_id
	 *
	 * @return array ok, order, error
	 */
	public static function get_order( $credentials, $order_id ) {
		$response = self::request( $credentials, 'GET', '/v2/orders/' . rawurlencode( $order_id ), null );

		if ( ! $response['ok'] || empty( $response['body']['order'] ) ) {
			return array( 'ok' => false, 'order' => array(), 'error' => self::describe_error( $response ) );
		}

		return array( 'ok' => true, 'order' => $response['body']['order'], 'error' => '' );
	}

	/**
	 * What an order says was actually paid.
	 *
	 * Deliberately the sum of the tenders rather than the order's own total_money: the
	 * total is what was asked for, the tenders are what arrived.
	 *
	 * @param array $order
	 *
	 * @return array paid (bool), amount (float), currency, transaction_id, reference_id
	 */
	public static function settlement( $order ) {
		$result = array( 'paid' => false, 'amount' => 0.0, 'currency' => '', 'transaction_id' => '', 'reference_id' => '' );
		if ( ! is_array( $order ) ) {
			return $result;
		}

		$result['reference_id'] = isset( $order['reference_id'] ) ? (string) $order['reference_id'] : '';

		$minor = 0;
		if ( ! empty( $order['tenders'] ) && is_array( $order['tenders'] ) ) {
			foreach ( $order['tenders'] as $tender ) {
				if ( isset( $tender['card_details']['status'] ) && $tender['card_details']['status'] !== 'CAPTURED' ) {
					continue;
				}
				if ( ! isset( $tender['amount_money']['amount'] ) ) {
					continue;
				}
				$minor                 += (int) $tender['amount_money']['amount'];
				$result['currency']    = isset( $tender['amount_money']['currency'] ) ? (string) $tender['amount_money']['currency'] : $result['currency'];
				$result['transaction_id'] = $result['transaction_id'] === '' && isset( $tender['id'] ) ? (string) $tender['id'] : $result['transaction_id'];
			}
		}

		if ( $result['currency'] === '' && isset( $order['total_money']['currency'] ) ) {
			$result['currency'] = (string) $order['total_money']['currency'];
		}
		if ( $result['transaction_id'] === '' && isset( $order['id'] ) ) {
			$result['transaction_id'] = (string) $order['id'];
		}

		$result['amount'] = ucm_payment_verification::from_minor_units( $minor, $result['currency'] );
		$result['paid']   = ( isset( $order['state'] ) && $order['state'] === 'COMPLETED' && $minor > 0 );

		return $result;
	}

	/**
	 * Did this webhook delivery really come from Square?
	 *
	 * Square signs the notification URL exactly as it was typed into the Developer
	 * Console, concatenated with the raw request body. Any difference - a trailing
	 * slash, http against https, www against bare - fails here and looks exactly like a
	 * wrong key, which is why the settings page prints the URL to paste rather than
	 * describing it.
	 *
	 * Only the SHA-256 header is accepted. Square's Connect v1 sent an HMAC-SHA1 header
	 * as well and third parties report it still arrives; accepting it would be a
	 * downgrade.
	 *
	 * @param string $raw_body
	 * @param string $signature_header the x-square-hmacsha256-signature header
	 * @param string $signature_key
	 * @param string $notification_url
	 *
	 * @return bool
	 */
	public static function verify_signature( $raw_body, $signature_header, $signature_key, $notification_url ) {
		$raw_body         = (string) $raw_body;
		$signature_header = (string) $signature_header;
		$signature_key    = (string) $signature_key;
		$notification_url = (string) $notification_url;

		if ( $raw_body === '' || $signature_header === '' || $signature_key === '' || $notification_url === '' ) {
			return false;
		}

		$expected = base64_encode( hash_hmac( 'sha256', $notification_url . $raw_body, $signature_key, true ) );

		return hash_equals( $expected, $signature_header );
	}

	/**
	 * @param string $text
	 * @param int    $length
	 *
	 * @return string
	 */
	private static function trim_to( $text, $length ) {
		$text = trim( preg_replace( '#\s+#', ' ', $text ) );

		return strlen( $text ) > $length ? substr( $text, 0, $length ) : $text;
	}

	/**
	 * @param array $response
	 *
	 * @return string one line, safe for a log
	 */
	private static function describe_error( $response ) {
		if ( ! empty( $response['ok'] ) ) {
			return '';
		}
		if ( ! empty( $response['transport_error'] ) ) {
			return 'Could not reach Square: ' . $response['transport_error'];
		}
		$body = is_array( $response['body'] ) ? $response['body'] : array();
		if ( ! empty( $body['errors'][0] ) ) {
			$error = $body['errors'][0];
			$bits  = array();
			foreach ( array( 'category', 'code', 'detail', 'field' ) as $part ) {
				if ( ! empty( $error[ $part ] ) ) {
					$bits[] = (string) $error[ $part ];
				}
			}

			return implode( ': ', $bits );
		}

		return 'HTTP ' . (int) $response['status'];
	}

	/**
	 * One HTTP call. Never throws, never warns, never follows a redirect.
	 *
	 * @param array       $credentials
	 * @param string      $method
	 * @param string      $path
	 * @param array|null  $body
	 *
	 * @return array ok, status, body (decoded), transport_error
	 */
	private static function request( $credentials, $method, $path, $body ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return array( 'ok' => false, 'status' => 0, 'body' => array(), 'transport_error' => 'this server has no cURL support' );
		}

		$ch = curl_init( self::api_base( ! empty( $credentials['sandbox'] ) ) . $path );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer ' . $credentials['access_token'],
			'Square-Version: ' . self::API_VERSION,
			'Content-Type: application/json',
			'Accept: application/json',
		) );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
		// Never off, not even behind a config flag. This carries money.
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		if ( $body !== null ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $body ) );
		}

		$raw    = curl_exec( $ch );
		$status = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$error  = curl_error( $ch );
		curl_close( $ch );

		if ( $raw === false ) {
			return array( 'ok' => false, 'status' => $status, 'body' => array(), 'transport_error' => $error !== '' ? $error : 'no response' );
		}

		$decoded = json_decode( (string) $raw, true );

		return array(
			'ok'              => ( $status >= 200 && $status < 300 ),
			'status'          => $status,
			'body'            => is_array( $decoded ) ? $decoded : array(),
			'transport_error' => '',
		);
	}
}
