<?php

require_once( dirname( __FILE__ ) . '/checkout.php' );

/**
 * Square, through Square-hosted payment links.
 *
 * The newest of the three live gateways and the only one with no history to protect, so
 * it is built straight onto the shared contract: a stable method key, a signature-verified
 * webhook, and module_invoice::verify_and_record() doing the amount, currency and
 * duplicate checks. Square runs the card form and the 3-D Secure challenge on its own
 * domain, so no card details ever touch this server.
 */
class module_paymethod_square extends module_base
{
	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function __construct() {
		$this->module_name     = 'paymethod_square';
		$this->module_position = 410;
		$this->version         = 2.1;
	}

	function init() {
		// 2.1 - 2026-10-13 - new payment gateway
	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Square",
				"p"                   => "square_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_payment',  // which page this link will be automatically added to.
				'menu_include_parent' => 1,
			);
		}
	}

	public function handle_hook( $hook ) {
		switch ( $hook ) {
			case 'get_payment_methods':
				return $this;
		}
	}

	public function is_method( $method ) {
		return $method == 'online';
	}

	public static function is_enabled() {
		return module_config::c( 'payment_method_square_enabled', 0 );
	}

	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['square'] ) ) {
			return $invoice_payment_methods['square']['enabled'];
		}

		// check if this invoice is in the allowed currency.
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$cur          = trim( strtolower( module_config::c( 'payment_method_square_currency', '' ) ) );
		$dollar_limit = module_config::c( 'payment_method_square_limit_type', 'above' );
		$dollar_value = module_config::c( 'payment_method_square_limit_value', 0 );

		if ( $dollar_limit == 'above' && $invoice_data['total_amount_due'] < $dollar_value ) {
			return false;
		} elseif ( $dollar_limit == 'below' && $invoice_data['total_amount_due'] > $dollar_value ) {
			return false;
		}
		if ( strlen( $cur ) > 1 ) {
			$allowed_currencies = explode( ',', $cur );
			if ( count( $allowed_currencies ) ) {
				$currency = module_config::get_currency( $invoice_data['currency_id'] );
				if ( ! in_array( strtolower( $currency['code'] ), $allowed_currencies ) ) {
					return false;
				}
			}
		}

		return module_config::c( 'payment_method_square_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'square', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	/**
	 * The stable identifier for this payment method, never the admin-editable label.
	 *
	 * @return string
	 */
	public static function get_payment_method_key() {
		return 'square';
	}

	/**
	 * @return bool
	 */
	public function is_deprecated() {
		return false;
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_square_label', 'Square' );
	}

	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

	}

	/**
	 * Square keeps sandbox and production on separate hosts and separate credentials, so
	 * unlike Stripe the mode is a setting rather than something read off the key.
	 *
	 * @return bool
	 */
	public static function is_sandbox() {
		return (bool) module_config::c( 'payment_method_square_sandbox', 0 );
	}

	/**
	 * @return array
	 */
	public static function credentials() {
		return array(
			'access_token' => trim( (string) module_config::c( 'payment_method_square_access_token', '' ) ),
			'location_id'  => trim( (string) module_config::c( 'payment_method_square_location_id', '' ) ),
			'sandbox'      => self::is_sandbox(),
		);
	}

	/**
	 * @return bool
	 */
	public static function is_configured() {
		$credentials = self::credentials();

		return $credentials['access_token'] !== '' && $credentials['location_id'] !== '';
	}

	/**
	 * The URL to paste into Square as the notification endpoint.
	 *
	 * This is not a convenience. Square signs the notification URL *exactly as typed into
	 * the Developer Console*, concatenated with the request body, so a trailing slash or
	 * an http/https difference fails verification with an error that looks like a wrong
	 * key. Computing it here, with the same code that later feeds the HMAC, is the only
	 * way to be sure the two match.
	 *
	 * @return string
	 */
	public static function webhook_url() {
		return full_link( _EXTERNAL_TUNNEL . '?m=paymethod_square&h=ipn' );
	}

	/**
	 * Where Square sends the payer back to once the hosted page is done.
	 *
	 * @param int  $invoice_payment_id
	 * @param bool $h return the hash rather than the link
	 *
	 * @return string
	 */
	public static function link_payment_complete( $invoice_payment_id, $h = false ) {
		if ( $h ) {
			return md5( 'square checkout complete ' . _UCM_SECRET . ' ' . (int) $invoice_payment_id );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=paymethod_square&h=complete&i=' . (int) $invoice_payment_id . '&hash=' . self::link_payment_complete( $invoice_payment_id, true ) );
	}

	/**
	 * The Square order id this payment was started with, as recorded at the time.
	 *
	 * Read back from the payment's own log rather than from the request, so there is
	 * nothing here for a payer to substitute.
	 *
	 * @param int $invoice_payment_id
	 *
	 * @return string
	 */
	public static function stored_order_id( $invoice_payment_id ) {
		$payment = get_single( 'invoice_payment', 'invoice_payment_id', (int) $invoice_payment_id );
		if ( ! $payment ) {
			return '';
		}
		$data = @unserialize( (string) $payment['data'], array( 'allowed_classes' => false ) );
		if ( ! is_array( $data ) || empty( $data['square_order_id'] ) || ! is_array( $data['square_order_id'] ) ) {
			return '';
		}

		return (string) end( $data['square_order_id'] );
	}

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id, $user_id = false ) {
		if ( ! $invoice_id || ! $payment_amount || ! $invoice_payment_id ) {
			return false;
		}

		$invoice_data         = module_invoice::get_invoice( $invoice_id );
		$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

		// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
		$fee_percent     = module_config::c( 'payment_method_square_charge_percent', 0 );
		$fee_amount      = module_config::c( 'payment_method_square_charge_amount', 0 );
		$fee_description = module_config::c( 'payment_method_square_charge_description', 'Square Fee' );
		$fee_total       = 0;
		if ( $fee_percent != 0 || $fee_amount != 0 ) {
			$fee_total = module_invoice::calculate_fee( $invoice_id, $invoice_data, $payment_amount, array(
				'percent'     => $fee_percent,
				'amount'      => $fee_amount,
				'description' => $fee_description,
			) );

			if ( $fee_total != 0 ) {
				$payment_amount = $payment_amount + $fee_total;
				update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array(
					'fee_percent'     => $fee_percent,
					'fee_amount'      => $fee_amount,
					'fee_description' => $fee_description,
					'fee_total'       => $fee_total,
					'amount'          => $payment_amount,
				) );
			}
		}

		if ( ! self::is_configured() ) {
			echo htmlspecialchars( _l( 'This site is not finished setting up card payments. Please contact us to arrange payment another way.' ) );
			exit;
		}

		$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
		$currency_code = isset( $currency['code'] ) ? strtoupper( $currency['code'] ) : 'USD';
		$description   = _l( 'Payment for invoice %s', $invoice_data['name'] );

		$link = ucm_square_checkout::create_payment_link( self::credentials(), array(
			'amount'             => $payment_amount,
			'currency'           => $currency_code,
			'description'        => $description,
			'invoice_payment_id' => $invoice_payment_id,
			'redirect_url'       => self::link_payment_complete( $invoice_payment_id ),
		) );

		if ( ! $link['ok'] ) {
			module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Square would not create a payment link: ' . $link['error'] );
			send_error( 'Square payment link could not be created for invoice payment ' . (int) $invoice_payment_id . ': ' . $link['error'] );
			echo htmlspecialchars( _l( 'Sorry, we could not start this payment. Please try again, or contact us.' ) );
			exit;
		}

		module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Square payment link ' . $link['payment_link_id'] . ' created for ' . number_format( (float) $payment_amount, 2 ) . ' ' . $currency_code . '.' );
		if ( $link['order_id'] !== '' ) {
			module_invoice::add_payment_data( $invoice_payment_id, 'square_order_id', $link['order_id'] );
		}

		redirect_browser( $link['url'] );

		return true;
	}

	/**
	 * Read an order back from Square and record the payment it represents.
	 *
	 * The read is authenticated, so what comes back is Square's own account of what
	 * happened rather than anything the caller supplied. That is what makes this safe to
	 * call from both the payer's return and the webhook, and verify_and_record() makes it
	 * safe to call twice.
	 *
	 * @param string $order_id
	 * @param int    $expect_invoice_payment_id 0 to accept whatever the order says
	 *
	 * @return array the verification result
	 */
	public static function record_order( $order_id, $expect_invoice_payment_id = 0 ) {
		$order_id = trim( (string) $order_id );
		if ( $order_id === '' || ! preg_match( '#^[A-Za-z0-9_-]{5,192}$#', $order_id ) ) {
			return self::refuse( 'bad_order_id', 'Not a Square order id.' );
		}

		$order = ucm_square_checkout::get_order( self::credentials(), $order_id );
		if ( ! $order['ok'] ) {
			return self::refuse( 'retrieve_failed', 'Square would not return this order: ' . $order['error'] );
		}

		$settlement         = ucm_square_checkout::settlement( $order['order'] );
		$invoice_payment_id = (int) $settlement['reference_id'];

		if ( $expect_invoice_payment_id && $invoice_payment_id !== (int) $expect_invoice_payment_id ) {
			return self::refuse( 'wrong_payment', 'This Square order belongs to a different payment.' );
		}

		return module_invoice::verify_and_record( $invoice_payment_id, array(
			'amount'         => $settlement['amount'],
			'currency'       => $settlement['currency'],
			'transaction_id' => $settlement['transaction_id'],
			'paid'           => $settlement['paid'],
			'test'           => self::is_sandbox(),
		), array(
			'method_key' => self::get_payment_method_key(),
			'sandbox'    => self::is_sandbox(),
		) );
	}

	/**
	 * @param string $code
	 * @param string $message
	 *
	 * @return array
	 */
	private static function refuse( $code, $message ) {
		return array( 'ok' => false, 'code' => $code, 'message' => $message, 'amount' => 0, 'flags' => array() );
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'complete':
				// Square sends the payer back here. Nothing in this request is trusted -
				// the order id comes from the payment's own log and the money comes from
				// an authenticated read - and because this is the same idempotent path
				// the webhook uses, an install Square cannot reach still records its
				// payments.
				$invoice_payment_id = isset( $_REQUEST['i'] ) ? (int) $_REQUEST['i'] : 0;
				$hash               = isset( $_REQUEST['hash'] ) ? (string) $_REQUEST['hash'] : '';

				if ( ! $invoice_payment_id || ! hash_equals( self::link_payment_complete( $invoice_payment_id, true ), $hash ) ) {
					echo htmlspecialchars( _l( 'Sorry, this payment link is not valid.' ) );
					exit;
				}

				$payment = get_single( 'invoice_payment', 'invoice_payment_id', $invoice_payment_id );
				if ( ! $payment ) {
					echo htmlspecialchars( _l( 'Sorry, this payment link is not valid.' ) );
					exit;
				}

				if ( self::is_configured() ) {
					self::record_order( self::stored_order_id( $invoice_payment_id ), $invoice_payment_id );
				}

				redirect_browser( module_invoice::link_public_payment_complete( $payment['invoice_id'] ) );
				exit;

			case 'ipn':
				$raw_body  = (string) @file_get_contents( 'php://input' );
				$signature = isset( $_SERVER['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] ) ? (string) $_SERVER['HTTP_X_SQUARE_HMACSHA256_SIGNATURE'] : '';
				$key       = trim( (string) module_config::c( 'payment_method_square_signature_key', '' ) );

				if ( ! self::is_configured() || ! ucm_square_checkout::verify_signature( $raw_body, $signature, $key, self::webhook_url() ) ) {
					// Square retries a non-2xx, which is what should happen if this was a
					// real delivery and something here is misconfigured. The most likely
					// cause by far is the notification URL in Square not matching the one
					// on the settings page character for character.
					header( 'HTTP/1.1 400 Bad Request' );
					echo 'Could not verify this notification. Check that the notification URL in Square exactly matches the one shown on the Square settings page.';
					exit;
				}

				$event = json_decode( $raw_body, true );
				$type  = is_array( $event ) && isset( $event['type'] ) ? (string) $event['type'] : '';

				// payment.updated is the one that reaches COMPLETED. payment.created
				// fires while the payment is still only approved.
				if ( $type !== 'payment.created' && $type !== 'payment.updated' ) {
					echo 'OK';
					exit;
				}

				$order_id = isset( $event['data']['object']['payment']['order_id'] ) ? (string) $event['data']['object']['payment']['order_id'] : '';
				if ( $order_id === '' ) {
					echo 'OK';
					exit;
				}

				$result = self::record_order( $order_id );
				echo $result['ok'] ? 'Payment recorded.' : 'Nothing to do.';
				exit;
		}
	}
}
