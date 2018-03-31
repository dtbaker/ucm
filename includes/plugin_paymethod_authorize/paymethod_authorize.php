<?php


class module_paymethod_authorize extends module_base {


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->version         = 2.23;
		$this->module_name     = "paymethod_authorize";
		$this->module_position = 8882;

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

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Authorize",
				"p"                   => "authorize_settings",
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
				<form action="<?php echo full_link( _EXTERNAL_TUNNEL . '?m=paymethod_authorize&h=pay&method=authorize' ); ?>"
				      method="POST" id="authorize-payment-form">
					<input type="hidden" name="invoice_payment_id" value="<?php echo $invoice_payment_id; ?>">
					<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
					<input type="hidden" name="invoice_num" value="<?php echo htmlspecialchars( $invoice_data['name'] ); ?>">
					<input type="hidden" name="description"
					       value="<?php _e( 'Payment for Invoice #%', htmlspecialchars( $invoice_data['name'] ) ); ?>">
					<?php echo $form->content; ?>
				</form>
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
						foreach (
							array(
								"address",
								"allow_partial_auth",
								"amount",
								"auth_code",
								"authentication_indicator",
								"bank_aba_code",
								"bank_acct_name",
								"bank_acct_num",
								"bank_acct_type",
								"bank_check_number",
								"bank_name",
								"card_code",
								"card_num",
								"cardholder_authentication_value",
								"city",
								"company",
								"country",
								"cust_id",
								"customer_ip",
								"delim_char",
								"delim_data",
								"description",
								"duplicate_window",
								"duty",
								"echeck_type",
								"email",
								"email_customer",
								"encap_char",
								"exp_date",
								"fax",
								"first_name",
								"footer_email_receipt",
								"freight",
								"header_email_receipt",
								"invoice_num",
								"last_name",
								"line_item",
								"login",
								"method",
								"phone",
								"po_num",
								"recurring_billing",
								"relay_response",
								"ship_to_address",
								"ship_to_city",
								"ship_to_company",
								"ship_to_country",
								"ship_to_first_name",
								"ship_to_last_name",
								"ship_to_state",
								"ship_to_zip",
								"split_tender_id",
								"state",
								"tax",
								"tax_exempt",
								"test_request",
								"tran_key",
								"trans_id",
								"type",
								"version",
								"zip"
							) as $possible_value
						) {
							if ( isset( $_POST[ $possible_value ] ) ) {
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