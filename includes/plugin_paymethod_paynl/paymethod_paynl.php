<?php

class module_paymethod_paynl extends module_base
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
		$this->module_name     = 'paymethod_paynl';
		$this->module_position = 380;
		$this->version         = 2.201;
	}

	function init() {
		// 2.1 - 2015-03-14 - new payment gateway
		// 2.2 - 2017-03-25 - cron speed up
		// 2.201 - 2026-10-17 - 4.0.0 relaunch: retired, kept so past payments still display
	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) && self::show_settings_page() ) {
			$this->links[] = array(
				"name"                => "PayNL",
				"p"                   => "paynl_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_payment',  // which page this link will be automatically added to.
				'menu_include_parent' => 1,
			);
		}
	}

	/**
	 * Whether this install should see the settings page at all.
	 *
	 * Retired, so it is hidden from a fresh install - but an install that is using it,
	 * that was using it before an upgrade turned it off, or that has payment history
	 * against it keeps the page, because that is where the explanation lives and where
	 * the credentials can still be read.
	 *
	 * @return bool
	 */
	public static function show_settings_page() {
		if ( self::is_enabled() || module_config::c( 'payment_method_paynl_was_enabled', false ) ) {
			return true;
		}

		return module_invoice::payment_method_has_history( self::get_payment_method_key(), array( 'paynl', 'PayNL', self::get_payment_method_name() ) );
	}

	public function handle_hook( $hook ) {
		switch ( $hook ) {
			case 'get_payment_methods':
				return $this;
				break;
		}
	}

	public function is_method( $method ) {
		return $method == 'online';
	}

	public static function is_enabled() {
		return module_config::c( 'payment_method_paynl_enabled', 0 );
	}

	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['paynl'] ) ) {
			return $invoice_payment_methods['paynl']['enabled'];
		}
		// check if this invoice is in the allowed currency.
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$cur          = trim( strtolower( module_config::c( 'payment_method_paynl_currency', '' ) ) );
		$dollar_limit = module_config::c( 'payment_method_paynl_limit_type', 'above' );
		$dollar_value = module_config::c( 'payment_method_paynl_limit_value', 0 );

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

		return module_config::c( 'payment_method_paynl_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'paynl', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	/**
	 * The stable identifier for this payment method, never the admin-editable label.
	 *
	 * @return string
	 */
	public static function get_payment_method_key() {
		return 'paynl';
	}

	/**
	 * Retired. The method still works everywhere it is already in use - it starts
	 * payments, answers its notification endpoint, and its history renders unchanged -
	 * but a fresh install never sees it.
	 *
	 * @return bool
	 */
	public function is_deprecated() {
		return true;
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_paynl_label', 'paynl' );
	}


	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

	}

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id, $user_id = false ) {
		if ( $invoice_id && $payment_amount && $invoice_payment_id ) {
			// we are starting a payment via paynl!
			// setup a pending payment and redirect to paynl.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			if ( ! $user_id ) {
				$user_id = $invoice_data['user_id'];
			}
			if ( ! $user_id ) {
				$user_id = isset( $invoice_data['primary_user_id'] ) ? $invoice_data['primary_user_id'] : 0;
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

			// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
			$fee_percent     = module_config::c( 'payment_method_paynl_charge_percent', 0 );
			$fee_amount      = module_config::c( 'payment_method_paynl_charge_amount', 0 );
			$fee_description = module_config::c( 'payment_method_paynl_charge_description', 'paynl Fee' );
			$fee_total       = 0;
			if ( $fee_percent != 0 || $fee_amount != 0 ) {
				$fee_total = module_invoice::calculate_fee( $invoice_id, $invoice_data, $payment_amount, array(
					'percent'     => $fee_percent,
					'amount'      => $fee_amount,
					'description' => $fee_description,
				) );

				if ( $fee_total != 0 ) {
					// add this percent/amount to the invoice payment
					$payment_amount = $payment_amount + $fee_total;
					update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array(
						'fee_percent'     => $fee_percent,
						'fee_amount'      => $fee_amount,
						'fee_description' => $fee_description,
						'fee_total'       => $fee_total,
						'amount'          => $payment_amount, // todo: confirm this doesn't double up or whatever
					) );
				}
			}

			$description = _l( 'Payment for Invoice %s', $invoice_data['name'] );
			self::paynl_redirect( $description, $payment_amount, $user_id, $invoice_payment_id, $invoice_id, $invoice_payment_data['currency_id'] );

			return true;
		}

		return false;
	}

	public static function add_payment_data( $invoice_payment_id, $key, $val ) {
		$payment      = module_invoice::get_invoice_payment( $invoice_payment_id );
		$payment_data = @unserialize( $payment['data'] );
		if ( ! is_array( $payment_data ) ) {
			$payment_data = array();
		}
		if ( ! isset( $payment_data[ $key ] ) ) {
			$payment_data[ $key ] = array();
		}
		$payment_data[ $key ][] = $val;
		update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array( 'data' => serialize( $payment_data ) ) );
	}


	public static function paynl_redirect( $description, $amount, $user_id, $payment_id, $invoice_id, $currency_id ) {

		$currency = module_config::get_currency( $currency_id );

		# Setup API URL
		$strUrl = 'https://rest-api.pay.nl/v5/Transaction/start/json?';
		# Add arguments
		$arrArguments                               = array();
		$arrArguments['token']                      = module_config::c( 'payment_method_paynl_token', '' );
		$arrArguments['serviceId']                  = module_config::c( 'payment_method_paynl_serviceid', '' );
		$arrArguments['amount']                     = $amount * 100;
		$arrArguments['finishUrl']                  = module_invoice::link_public_payment_complete( $invoice_id );
		$arrArguments['ipAddress']                  = $_SERVER['REMOTE_ADDR'];
		$arrArguments['transactionl']               = array();
		$arrArguments['transaction']['description'] = $description;
		//$currency['code']

		# Prepare and call API URL
		$strUrl .= http_build_query( $arrArguments );
		$ch     = curl_init( $strUrl );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		$jsonResult = curl_exec( $ch );
		//$jsonResult = file_get_contents($strUrl);
		$json = json_decode( $jsonResult, true );
		if ( $json && isset( $json['transaction'] ) && isset( $json['transaction']['paymentURL'] ) && isset( $json['transaction']['transactionId'] ) ) {
			module_paymethod_paynl::add_payment_data( $payment_id, 'log', "Started PayNL Payment: \n " . var_export( $json, true ) );
			update_insert( 'invoice_payment_id', $payment_id, 'invoice_payment', array( 'other_id' => $json['transaction']['transactionId'] ) );
			redirect_browser( $json['transaction']['paymentURL'] );
		} else {
			module_paymethod_paynl::add_payment_data( $payment_id, 'log', "PayNL ERROR: \n " . $jsonResult );
			echo 'Sorry an error occured during payment processing. Please try again.';
			echo $jsonResult;
			exit;
		}

	}

	public function run_cron( $debug = false ) {
		// check for payments.

		$token = module_config::c( 'payment_method_paynl_token', '' );
		if ( $debug ) {
			if ( ! $token ) {
				echo "Skipping payln as no token defined";
			}
		}
		if ( ! $token ) {
			return;
		}
		// This used to be `method` = 'paynl', the lowercase factory default of an
		// admin-editable label. Renaming the payment method in settings stopped
		// reconciliation for good, and because Pay.nl has no webhook this cron is the
		// only way one of its payments is ever recorded. Match the stable key, and keep
		// matching the label for rows written before method_key existed.
		$label  = db_escape( self::get_payment_method_name() );
		$fields = get_fields( 'invoice_payment' );
		$sql    = "SELECT * FROM `" . _DB_PREFIX . "invoice_payment` ip WHERE 1 ";
		if ( isset( $fields['method_key'] ) ) {
			$sql .= " AND ( `method_key` = '" . db_escape( self::get_payment_method_key() ) . "' OR ( `method_key` = '' AND `method` IN ( 'paynl', '" . $label . "' ) ) ) ";
		} else {
			// Pre-upgrade database, the column is not there yet.
			$sql .= " AND `method` IN ( 'paynl', '" . $label . "' ) ";
		}
		$sql .= " AND  `date_paid` = '0000-00-00' ";
		$sql .= " AND  `other_id` != '' ";
		foreach ( qa( $sql ) as $payment ) {
			// check api status:
			$strUrl                        = 'https://token:' . $token . '@rest-api.pay.nl/v5/Transaction/info/json?';
			$arrArguments                  = array();
			$arrArguments['transactionId'] = $payment['other_id'];
			# Prepare and call API URL
			$strUrl .= http_build_query( $arrArguments );
			if ( $debug ) {
				echo "Checking URL $strUrl <br>\n";
				$jsonResult = file_get_contents( $strUrl );
			} else {
				$jsonResult = @file_get_contents( $strUrl );
			}
			$json = @json_decode( $jsonResult, true );
			if ( $debug ) {
				echo "Got result: <br>\n";
				print_r( $json );
			}
			if ( $json && isset( $json['paymentDetails'] ) && isset( $json['paymentDetails']['stateName'] ) && isset( $json['paymentDetails']['amount'] ) ) {
				module_paymethod_paynl::add_payment_data( $payment['invoice_payment_id'], 'log', "PayNL Status " . $json['paymentDetails']['stateName'] . ": \n " . var_export( $json, true ) );
				switch ( $json['paymentDetails']['stateName'] ) {
					case 'PENDING':
						// defauly, still waiting for payment.

						break;
					case 'PAID':
						update_insert( "invoice_payment_id", $payment['invoice_payment_id'], "invoice_payment", array(
							'date_paid' => date( 'Y-m-d' ),
							'amount'    => $json['paymentDetails']['amount'] / 100,
							'other_id'  => '', // stops cron hitting it agagin
						) );
						module_invoice::save_invoice( $payment['invoice_id'], array() );
						break;
					case 'CANCEL':
						update_insert( "invoice_payment_id", $payment['invoice_payment_id'], "invoice_payment", array(
							'other_id' => '', // stops cron hitting it agagin
						) );
						module_invoice::save_invoice( $payment['invoice_id'], array() );
						send_error( 'PayNL payment cancelled for invoice: ' . module_invoice::link_open( $payment['invoice_id'], true ) );
						break;
				}
			} else {
				module_paymethod_paynl::add_payment_data( $payment['invoice_payment_id'], 'log', "PayNL Status ERROR: \n " . $jsonResult );
			}
		}
	}
}
