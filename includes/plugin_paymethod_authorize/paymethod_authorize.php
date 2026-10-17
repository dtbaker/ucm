<?php

class module_paymethod_authorize extends module_base
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
		$this->module_name     = 'paymethod_authorize';
		$this->module_position = 320;
		$this->version         = 2.231;
	}

	function init() {
		// 2.231 - 2026-10-17 - 4.0.0 relaunch: retired and switched off on upgrade, correct payment amount, certificate always checked
		// 2.24 - 2017-03-21 - authorize fee charge percent
		// 2.229 - 2015-11-26 - processing fee
		// 2.229 - 2015-11-26 - processing fee
		// 2.228 - 2015-04-23 - authorize error message
		// 2.227 - 2015-04-05 - authorize error message
		// 2.226 - 2015-03-14 - better default payment method options
		// 2.225 - 2014-11-26 - Authorize gateway URLs in settings
		// 2.224 - 2013-10-02 - Authorize form fix - go to Settings-Templates-authorize_credit_card_form and click reset default
		// 2.223 - 2013-09-05 - marking invoice as paid fix
		// 2.222 - 2013-07-29 - sandbox mode option
		// 2.221 - 2013-07-15 - verifypeer option
		// 2.22 - 2013-04-27 - cancel url on payments screen
		// 2.21 - 2013-04-20 - initial release

		self::retire();
	}

	/**
	 * Switch this payment method off, once, on the first page load after upgrading.
	 *
	 * The rule everywhere else in this release is that an install which upgrades and does
	 * nothing else behaves exactly as before. This is the one deliberate exception. An
	 * earlier version let a payer set the amount their own card was charged and mark an
	 * invoice paid in full for a cent; the hole itself is fixed, but leaving a method
	 * that collects raw card numbers on the merchant's own server switched on, in a
	 * release that retires it, is worse than making the admin turn it back on knowingly.
	 *
	 * Nothing is deleted. Every credential and setting stays where it is, the settings
	 * page stays reachable, and payment history is untouched. The admin is told what
	 * happened and why on that page.
	 *
	 * @return void
	 */
	private static function retire() {
		if ( module_config::c( 'payment_method_authorize_retired', false ) ) {
			return;
		}
		module_config::save_config( 'payment_method_authorize_retired', 1 );

		if ( ! module_config::c( 'payment_method_authorize_enabled', 0 ) ) {
			return;
		}
		module_config::save_config( 'payment_method_authorize_enabled', 0 );
		// Remembered so the settings page keeps showing, and can explain itself.
		module_config::save_config( 'payment_method_authorize_was_enabled', 1 );
	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) && self::show_settings_page() ) {
			$this->links[] = array(
				"name"                => "Authorize",
				"p"                   => "authorize_settings",
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
		if ( self::is_enabled() || module_config::c( 'payment_method_authorize_was_enabled', false ) ) {
			return true;
		}

		return module_invoice::payment_method_has_history( self::get_payment_method_key(), array( 'Authorize', self::get_payment_method_name() ) );
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
		return module_config::c( 'payment_method_authorize_enabled', 0 );
	}

	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_authorize_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_authorize_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['authorize'] ) ) {
			return $invoice_payment_methods['authorize']['enabled'];
		}

		return module_config::c( 'payment_method_authorize_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'authorize', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	/**
	 * The stable identifier for this payment method, never the admin-editable label.
	 *
	 * @return string
	 */
	public static function get_payment_method_key() {
		return 'authorize';
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
		return module_config::s( 'payment_method_authorize_label', 'Authorize' );
	}

	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

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

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id, $user_id = false ) {
		if ( $invoice_id && $payment_amount && $invoice_payment_id ) {
			// we are starting a payment via authorize!
			// setup a pending payment and redirect to authorize.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			if ( ! $user_id ) {
				$user_id = $invoice_data['user_id'];
			}
			if ( ! $user_id ) {
				$user_id = module_security::get_loggedin_id();
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

			// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
			$fee_percent     = module_config::c( 'payment_method_authorize_charge_percent', 0 );
			$fee_amount      = module_config::c( 'payment_method_authorize_charge_amount', 0 );
			$fee_description = module_config::c( 'payment_method_authorize_charge_description', 'Authorize Fee' );
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


			if ( $invoice_payment_data && $invoice_payment_data['invoice_id'] == $invoice_data['invoice_id'] ) {
				//self::authorize_redirect($description,$payment_amount,$user_id,$invoice_payment_id,$invoice_id,$invoice_payment_data['currency_id']);
				$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
				$currency_code = $currency['code'];
				ob_start();
				include( 'includes/plugin_paymethod_authorize/pages/authorize_form_default.php' );
				module_template::init_template( 'authorize_credit_card_form', ob_get_clean(), 'Form displayed for payments via Authorize.net', 'code' );
				$form = module_template::get_template_by_key( 'authorize_credit_card_form' );
				//                $form = new module_template();
				//                $form->content = ob_get_clean();
				ob_start();
				?>
				<?php module_form::print_form_open_tag( array(
					'action' => ( full_link( _EXTERNAL_TUNNEL . '?m=paymethod_authorize&h=pay&method=authorize' ) ),
					'id'     => 'authorize-payment-form',
				) ); ?>
					<input type="hidden" name="invoice_payment_id" value="<?php echo $invoice_payment_id; ?>">
					<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
					<input type="hidden" name="invoice_num" value="<?php echo htmlspecialchars( $invoice_data['name'] ); ?>">
					<input type="hidden" name="description"
					       value="<?php _e( 'Payment for Invoice #%', htmlspecialchars( $invoice_data['name'] ) ); ?>">
					<?php echo $form->content; ?>
				<?php module_form::print_form_close_tag(); ?>
				<?php
				$form->content = ob_get_clean();
				$form->assign_values(
					array(
						'INVOICE_NUMBER'  => $invoice_data['name'],
						'AMOUNT'          => dollar( $payment_amount, true, $invoice_payment_data['currency_id'] ),
						'CANCEL_URL'      => module_invoice::link_public( $invoice_id ),
						'fee_amount'      => dollar( $fee_amount, true, $invoice_data['currency_id'] ),
						'fee_total'       => dollar( $fee_total, true, $invoice_data['currency_id'] ),
						'fee_percent'     => $fee_percent,
						'fee_description' => $fee_description,
					)
				);
				// we also want to grab all the normal invoice replace fields and add those in as well.
				$form->assign_values( module_invoice::get_replace_fields( $invoice_id, $invoice_data ) );
				echo $form->render( 'pretty_html' );
			}
			exit;
		}

		return false;
	}

	public function external_hook( $hook ) {
		// Unlike the other retired gateways, this hook is not a provider callback with a
		// payment possibly in flight behind it - it is the merchant's own card form
		// posting back, synchronously. There is nothing to lose by closing it with the
		// method, and it is the surface that carries the card number.
		if ( ! self::is_enabled() ) {
			echo _l( 'This payment method is no longer available.' );
			exit;
		}
		switch ( $hook ) {
			case 'pay':
				// result is retured via ajax and displayed on the page.
				$invoice_id         = isset( $_REQUEST['invoice_id'] ) ? $_REQUEST['invoice_id'] : false;
				$invoice_payment_id = isset( $_REQUEST['invoice_payment_id'] ) ? $_REQUEST['invoice_payment_id'] : false;
				if ( $invoice_id && $invoice_payment_id ) {

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					$invoice_data         = module_invoice::get_invoice( $invoice_id );
					if ( $invoice_payment_data && $invoice_data && $invoice_id == $invoice_data['invoice_id'] && $invoice_payment_data['invoice_id'] == $invoice_data['invoice_id'] ) {
						$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
						$currency_code = $currency['code'];
						$description   = _l( 'Payment for invoice %s', $invoice_data['name'] );

						require_once 'includes/plugin_paymethod_authorize/anet_php_1.1.8/AuthorizeNet.php';

						$transaction = new AuthorizeNetAIM( module_config::c( 'payment_method_authorize_api_login_id', '' ), module_config::c( 'payment_method_authorize_transaction_key', '' ) );
						$transaction->setSandbox( module_config::c( 'payment_method_authorize_sandbox', 0 ) );
						$transaction->VERIFY_PEER = module_config::c( 'payment_method_authorize_ssl_verify', 1 );
						$transaction->amount      = $invoice_payment_data['amount']; // USD ONLY
						// Only the billing details the payment form actually asks for. This used to be
						// the entire AIM field list, which let the browser set "amount" and
						// "test_request" - and because __set() and setField() write the same
						// _x_post_fields slot, a posted amount overwrote the invoice amount set just
						// above. Anyone holding a payment link could pay a cent, or send
						// test_request=TRUE for an approval that moves no money, and the invoice was
						// then marked paid in full.
						foreach (
							array(
								"address",
								"city",
								"company",
								"country",
								"first_name",
								"last_name",
								"state",
								"zip",
							) as $possible_value
						) {
							if ( isset( $_POST[ $possible_value ] ) && is_string( $_POST[ $possible_value ] ) ) {
								$transaction->setField( $possible_value, $_POST[ $possible_value ] );
							}
						}
						$transaction->setField( 'card_num', isset( $_POST['number'] ) ? $_POST['number'] : '' );
						$transaction->setField( 'exp_date', $_POST['month'] . '/' . $_POST['year'] );
						$transaction->setField( 'card_code', $_POST['cvv'] );
						//$transaction->card_num = isset($_POST['number']) ? $_POST['number'] : '';
						//$transaction->exp_date = $_POST['month'].'/'.$_POST['year'];
						//$transaction->card_code = $_POST['cvv'];

						$response = $transaction->authorizeAndCapture();

						// Check what was actually charged before calling it paid. An approval on
						// its own says nothing about the amount, and this used to record the
						// payment without ever looking.
						$expected_amount = (float) $invoice_payment_data['amount'];
						$charged_amount  = isset( $response->amount ) ? (float) $response->amount : false;
						if ( $response->approved && $charged_amount !== false && abs( $charged_amount - $expected_amount ) > 0.001 ) {
							module_paymethod_stripe::add_payment_data( $invoice_payment_id, 'log',
								'Authorize.Net approved ' . $charged_amount . ' but the payment was for ' . $expected_amount . '. Not recorded as paid.' );
							echo 'The amount charged did not match this invoice. Please contact us.';
							exit;
						}

						if ( $response->approved ) {
							//                          echo "<h1>Success! The test credit card has been charged!</h1>";
							//                          echo "Transaction ID: " . $response->transaction_id;
							update_insert( "invoice_payment_id", $invoice_payment_id, "invoice_payment", array(
								'date_paid' => date( 'Y-m-d' ),
							) );
							module_paymethod_stripe::add_payment_data( $invoice_payment_id, 'log', "Successfully paid: " . var_export( $response, true ) );

							module_invoice::save_invoice( $invoice_id, array() );
							// success!
							// redirect to receipt page.
							redirect_browser( module_invoice::link_receipt( $invoice_payment_id ) );
						} else {
							echo isset( $response->error_message ) ? $response->error_message : ( isset( $response->response_reason_text ) ? $response->response_reason_text : var_export( $response, true ) );
						}

						exit;
					}
				}
				echo 'Error paying via Authorize';
				exit;
		}
	}
}