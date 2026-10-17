<?php

/**
 * PayPal Orders v2, by hand.
 *
 * No SDK. PayPal's current official package, paypal/paypal-server-sdk, covers five
 * endpoint groups and webhook verification is not one of them - so the fiddliest part of
 * this file would be hand-rolled either way - and it pulls in three apimatic packages to
 * do it. Its two predecessors are both marked abandoned on Packagist. For a flow this
 * small, cURL against a documented REST API is less code and less to go wrong.
 *
 * Everything here returns an array and never throws. A payment page is not the place to
 * discover that an HTTP call raised something nobody caught.
 */
class ucm_paypal_orders
{
	/**
	 * @param bool $sandbox
	 *
	 * @return string
	 */
	public static function api_base( $sandbox ) {
		return $sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
	}

	/**
	 * An OAuth2 access token, good for several hours. Cached for this request only -
	 * caching it across requests would mean storing a bearer token in the database for a
	 * saving of one HTTP call per payment.
	 *
	 * @param string $client_id
	 * @param string $secret
	 * @param bool   $sandbox
	 *
	 * @return string empty on failure
	 */
	public static function access_token( $client_id, $secret, $sandbox ) {
		static $tokens = array();
		$cache_key = md5( $client_id . '|' . $secret . '|' . ( $sandbox ? 'sandbox' : 'live' ) );
		if ( isset( $tokens[ $cache_key ] ) ) {
			return $tokens[ $cache_key ];
		}
		$tokens[ $cache_key ] = '';

		$response = self::request(
			'POST',
			self::api_base( $sandbox ) . '/v1/oauth2/token',
			'grant_type=client_credentials',
			array( 'Content-Type: application/x-www-form-urlencoded' ),
			$client_id . ':' . $secret
		);

		if ( $response['ok'] && ! empty( $response['body']['access_token'] ) ) {
			$tokens[ $cache_key ] = (string) $response['body']['access_token'];
		}

		return $tokens[ $cache_key ];
	}

	/**
	 * Create an order for the payer to approve.
	 *
	 * reference_id and custom_id both carry the invoice_payment_id. custom_id is the one
	 * that survives into the capture and into the webhook, which is what lets a
	 * notification be tied back to a row.
	 *
	 * @param array $credentials client_id, secret, sandbox
	 * @param array $order       amount, currency, description, invoice_payment_id, invoice_id
	 *
	 * @return array ok, id, body, error
	 */
	public static function create_order( $credentials, $order ) {
		$token = self::access_token( $credentials['client_id'], $credentials['secret'], ! empty( $credentials['sandbox'] ) );
		if ( $token === '' ) {
			return array( 'ok' => false, 'id' => '', 'body' => array(), 'error' => 'PayPal would not issue an access token. Check the client ID and secret.' );
		}

		$payload = array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'reference_id' => (string) (int) $order['invoice_payment_id'],
					'custom_id'    => (string) (int) $order['invoice_payment_id'],
					'description'  => self::trim_to( (string) $order['description'], 127 ),
					'amount'       => array(
						'currency_code' => strtoupper( (string) $order['currency'] ),
						'value'         => self::format_amount( $order['amount'], $order['currency'] ),
					),
				),
			),
		);

		$response = self::request(
			'POST',
			self::api_base( ! empty( $credentials['sandbox'] ) ) . '/v2/checkout/orders',
			json_encode( $payload ),
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token,
				// Same payment row, same request id: a double submit cannot create two
				// orders for one invoice payment.
				'PayPal-Request-Id: ucm-order-' . (int) $order['invoice_payment_id'],
			)
		);

		if ( ! $response['ok'] || empty( $response['body']['id'] ) ) {
			return array( 'ok' => false, 'id' => '', 'body' => $response['body'], 'error' => self::describe_error( $response ) );
		}

		return array( 'ok' => true, 'id' => (string) $response['body']['id'], 'body' => $response['body'], 'error' => '' );
	}

	/**
	 * Take the money for an order the payer has approved.
	 *
	 * @param array  $credentials
	 * @param string $order_id
	 *
	 * @return array ok, body, error
	 */
	public static function capture_order( $credentials, $order_id ) {
		$token = self::access_token( $credentials['client_id'], $credentials['secret'], ! empty( $credentials['sandbox'] ) );
		if ( $token === '' ) {
			return array( 'ok' => false, 'body' => array(), 'error' => 'PayPal would not issue an access token.' );
		}

		$response = self::request(
			'POST',
			self::api_base( ! empty( $credentials['sandbox'] ) ) . '/v2/checkout/orders/' . rawurlencode( $order_id ) . '/capture',
			'{}',
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token,
				'PayPal-Request-Id: ucm-capture-' . $order_id,
			)
		);

		if ( ! $response['ok'] ) {
			return array( 'ok' => false, 'body' => $response['body'], 'error' => self::describe_error( $response ) );
		}

		return array( 'ok' => true, 'body' => $response['body'], 'error' => '' );
	}

	/**
	 * Read an order back from PayPal, authenticated, rather than trusting the caller.
	 *
	 * @param array  $credentials
	 * @param string $order_id
	 *
	 * @return array ok, body, error
	 */
	public static function get_order( $credentials, $order_id ) {
		$token = self::access_token( $credentials['client_id'], $credentials['secret'], ! empty( $credentials['sandbox'] ) );
		if ( $token === '' ) {
			return array( 'ok' => false, 'body' => array(), 'error' => 'PayPal would not issue an access token.' );
		}

		$response = self::request(
			'GET',
			self::api_base( ! empty( $credentials['sandbox'] ) ) . '/v2/checkout/orders/' . rawurlencode( $order_id ),
			null,
			array( 'Authorization: Bearer ' . $token )
		);

		return array( 'ok' => $response['ok'], 'body' => $response['body'], 'error' => self::describe_error( $response ) );
	}

	/**
	 * Ask PayPal whether a webhook delivery really came from PayPal.
	 *
	 * The alternative is verifying the SHA256withRSA signature locally, which means
	 * building "transmissionId|timeStamp|webhookId|crc32(raw body)" exactly right and
	 * fetching a certificate from a URL supplied in a request header - an SSRF hazard
	 * unless the host is validated, and an easy thing to get subtly wrong. One extra
	 * authenticated round trip per webhook buys away that whole class of mistake.
	 *
	 * @param array  $credentials
	 * @param string $webhook_id the id of the webhook as registered in PayPal
	 * @param array  $headers    the transmission headers, lowercased keys
	 * @param string $raw_body
	 *
	 * @return bool
	 */
	public static function verify_webhook( $credentials, $webhook_id, $headers, $raw_body ) {
		$webhook_id = trim( (string) $webhook_id );
		if ( $webhook_id === '' ) {
			return false;
		}
		foreach ( array( 'paypal-auth-algo', 'paypal-cert-url', 'paypal-transmission-id', 'paypal-transmission-sig', 'paypal-transmission-time' ) as $required ) {
			if ( empty( $headers[ $required ] ) ) {
				return false;
			}
		}

		$event = json_decode( $raw_body, true );
		if ( ! is_array( $event ) ) {
			return false;
		}

		$token = self::access_token( $credentials['client_id'], $credentials['secret'], ! empty( $credentials['sandbox'] ) );
		if ( $token === '' ) {
			return false;
		}

		$response = self::request(
			'POST',
			self::api_base( ! empty( $credentials['sandbox'] ) ) . '/v1/notifications/verify-webhook-signature',
			json_encode( array(
				'auth_algo'         => $headers['paypal-auth-algo'],
				'cert_url'          => $headers['paypal-cert-url'],
				'transmission_id'   => $headers['paypal-transmission-id'],
				'transmission_sig'  => $headers['paypal-transmission-sig'],
				'transmission_time' => $headers['paypal-transmission-time'],
				'webhook_id'        => $webhook_id,
				'webhook_event'     => $event,
			) ),
			array(
				'Content-Type: application/json',
				'Authorization: Bearer ' . $token,
			)
		);

		return $response['ok'] && isset( $response['body']['verification_status'] ) && $response['body']['verification_status'] === 'SUCCESS';
	}

	/**
	 * Pull the completed capture out of an order, wherever PayPal put it.
	 *
	 * An order has purchase units, a purchase unit has payments, payments have captures,
	 * and only a capture knows what was actually taken. Returns the first COMPLETED one.
	 *
	 * @param array $order the decoded order or capture response
	 *
	 * @return array|false
	 */
	public static function completed_capture( $order ) {
		if ( ! is_array( $order ) || empty( $order['purchase_units'] ) || ! is_array( $order['purchase_units'] ) ) {
			return false;
		}
		foreach ( $order['purchase_units'] as $unit ) {
			if ( empty( $unit['payments']['captures'] ) || ! is_array( $unit['payments']['captures'] ) ) {
				continue;
			}
			foreach ( $unit['payments']['captures'] as $capture ) {
				if ( isset( $capture['status'] ) && $capture['status'] === 'COMPLETED' ) {
					$capture['_custom_id']   = isset( $capture['custom_id'] ) ? $capture['custom_id'] : ( isset( $unit['custom_id'] ) ? $unit['custom_id'] : '' );
					$capture['_reference_id'] = isset( $unit['reference_id'] ) ? $unit['reference_id'] : '';

					return $capture;
				}
			}
		}

		return false;
	}

	/**
	 * PayPal wants a string with exactly the right number of decimal places for the
	 * currency, and rejects anything else.
	 *
	 * @param float  $amount
	 * @param string $currency_code
	 *
	 * @return string
	 */
	public static function format_amount( $amount, $currency_code ) {
		$decimals = in_array( strtoupper( trim( (string) $currency_code ) ), ucm_payment_verification::zero_decimal_currencies(), true ) ? 0 : 2;

		return number_format( (float) $amount, $decimals, '.', '' );
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
			return 'Could not reach PayPal: ' . $response['transport_error'];
		}
		$body = is_array( $response['body'] ) ? $response['body'] : array();
		$bits = array();
		if ( ! empty( $body['name'] ) ) {
			$bits[] = (string) $body['name'];
		}
		if ( ! empty( $body['message'] ) ) {
			$bits[] = (string) $body['message'];
		}
		if ( ! empty( $body['details'][0]['description'] ) ) {
			$bits[] = (string) $body['details'][0]['description'];
		}
		if ( ! $bits ) {
			$bits[] = 'HTTP ' . (int) $response['status'];
		}

		return implode( ': ', $bits );
	}

	/**
	 * One HTTP call. Never throws, never warns, never follows a redirect.
	 *
	 * @param string      $method
	 * @param string      $url
	 * @param string|null $body
	 * @param array       $headers
	 * @param string      $basic_auth
	 *
	 * @return array ok, status, body (decoded), transport_error
	 */
	private static function request( $method, $url, $body, $headers, $basic_auth = '' ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return array( 'ok' => false, 'status' => 0, 'body' => array(), 'transport_error' => 'this server has no cURL support' );
		}

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 15 );
		// Never off, not even behind a config flag. This carries money.
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
		if ( $basic_auth !== '' ) {
			curl_setopt( $ch, CURLOPT_USERPWD, $basic_auth );
		}
		if ( $body !== null ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
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
