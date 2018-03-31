<?php


class module_paymethod_multisafepay extends module_base {


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
		$this->version         = 2.222;
		$this->module_name     = "paymethod_multisafepay";
		$this->module_position = 8882;

		// 2.1 - initial release
		// 2.22 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.221 - 2013-08-07 - currency fix
		// 2.222 - 2015-03-14 - better default payment method options
	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "MultiSafepay",
				"p"                   => "multisafepay_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_payment',  // which page this link will be automatically added to.
				'menu_include_parent' => 1,
			);
		}
	}

	public static function is_sandbox() {
		return module_config::c( 'payment_method_multisafepay_sandbox', 0 );
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
		return module_config::c( 'payment_method_multisafepay_enabled', 0 );
	}

	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_multisafepay_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_multisafepay_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['multisafepay'] ) ) {
			return $invoice_payment_methods['multisafepay']['enabled'];
		}

		return module_config::c( 'payment_method_multisafepay_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'multisafepay', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_multisafepay_label', 'MultiSafepay' );
	}


	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

	}

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id, $user_id = false ) {
		if ( $invoice_id && $payment_amount && $invoice_payment_id ) {
			// we are starting a payment via multisafepay!
			// setup a pending payment and redirect to multisafepay.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			if ( ! $user_id ) {
				$user_id = $invoice_data['user_id'];
			}
			if ( ! $user_id ) {
				$user_id = module_security::get_loggedin_id();
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
			$description          = _l( 'Payment for invoice %s', $invoice_data['name'] );
			self::multisafepay_redirect( $description, $payment_amount, $user_id, $invoice_payment_id, $invoice_id, $invoice_payment_data['currency_id'] );

			return true;
		}

		return false;
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'ipn':
				// handle IPN response from multisafepay.
				$this->handle_multisafepay_ipn();
				break;
		}
	}

	public static function multisafepay_redirect( $description, $amount, $user_id, $payment_id, $invoice_id, $currency_id ) {

		$currency = module_config::get_currency( $currency_id );

		if ( $currency['code'] != 'EUR' ) {
			echo "Multisafepay only accepts currency in EUR";
		}


		include( 'MultiSafepay.combined.php' );

		$msp = new MultiSafepay();

		/*
		 * Merchant Settings
		 */
		$msp->test                         = self::is_sandbox();
		$msp->merchant['account_id']       = module_config::c( 'payment_method_multisafepay_account', '' );
		$msp->merchant['site_id']          = module_config::c( 'payment_method_multisafepay_site_id', '' );
		$msp->merchant['site_code']        = module_config::c( 'payment_method_multisafepay_side_code', '' );
		$msp->merchant['notification_url'] = full_link( _EXTERNAL_TUNNEL . '?m=paymethod_multisafepay&h=ipn&method=multisafepay&type=initial' );
		$msp->merchant['cancel_url']       = module_invoice::link_public( $invoice_id );
		// optional automatic redirect back to the shop:
		$msp->merchant['redirect_url'] = module_invoice::link_public( $invoice_id );

		/*
		 * Customer Details
		 */
		$invoice  = $invoice_data = module_invoice::get_invoice( $invoice_id );
		$customer = module_customer::get_customer( $invoice_data['customer_id'], true );
		if ( ! $user_id ) {
			$user_id = $customer['primary_user_id'];
		}
		$user = module_user::get_user( $user_id, false );
		//$msp->customer['locale']           = 'nl';
		$msp->customer['firstname'] = $user['name'];
		$msp->customer['lastname']  = $user['last_name'];
		$address                    = module_address::get_address( $invoice_data['customer_id'], 'customer', 'physical' );
		$msp->customer['zipcode']   = isset( $address['post_code'] ) ? $address['post_code'] : '';
		$msp->customer['city']      = isset( $address['region'] ) ? $address['region'] : '';
		$msp->customer['country']   = isset( $address['country'] ) ? $address['country'] : module_config::c( 'payment_method_multisafepay_country', '' );
		$msp->customer['phone']     = $user['phone'];
		$msp->customer['email']     = $user['email'];

		$msp->parseCustomerAddress( isset( $address['line_1'] ) ? $address['line_1'] : '' );
		// or
		// $msp->customer['address1']         = 'Teststraat';
		// $msp->customer['housenumber']      = '21';

		/*
		 * Transaction Details
		 */
		$msp->transaction['id']          = self::multisafepay_custom( $user_id, $payment_id, $invoice_id );
		$msp->transaction['currency']    = $currency['code'];
		$msp->transaction['amount']      = $amount * 100; // cents
		$msp->transaction['description'] = $description;
		$msp->transaction['items']       = '<br/><ul>';
		// copied from invoice_task_list.php
		foreach ( module_invoice::get_invoice_items( $invoice_id ) as $invoice_item_id => $invoice_item_data ) {
			// copy any changes here to template/invoice_task_list.php
			$task_hourly_rate = isset( $invoice_item_data['hourly_rate'] ) && $invoice_item_data['hourly_rate'] != 0 ? $invoice_item_data['hourly_rate'] : $invoice_data['hourly_rate'];
			// if there are no hours logged against this task
			if ( ! $invoice_item_data['hours'] ) {
				//$task_hourly_rate=0;
			}
			// if we have a custom price for this task
			if ( $invoice_item_data['amount'] != 0 && $invoice_item_data['amount'] != ( $invoice_item_data['hours'] * $task_hourly_rate ) ) {
				$invoice_item_amount = $invoice_item_data['amount'];
				$task_hourly_rate    = false;
			} else if ( $invoice_item_data['hours'] > 0 ) {
				$invoice_item_amount = $invoice_item_data['hours'] * $task_hourly_rate;
			} else {
				$invoice_item_amount = 0;
				$task_hourly_rate    = false;
			}
			$msp->transaction['items'] .= '<li>';
			$msp->transaction['items'] .= $invoice_item_data['hours'] > 0 ? $invoice_item_data['hours'] . ' x ' : '';
			$msp->transaction['items'] .= $invoice_item_data['custom_description'] ? htmlspecialchars( $invoice_item_data['custom_description'] ) : htmlspecialchars( $invoice_item_data['description'] );
			$msp->transaction['items'] .= ' = ' . dollar( $invoice_item_amount, true, $invoice['currency_id'] );
			$msp->transaction['items'] .= '</li>';
		}

		$msp->transaction['items'] .= '<li>Sub Total: ' . dollar( $invoice_data['total_sub_amount'], true, $invoice_data['currency_id'] ) . '</li>';
		if ( $invoice_data['total_tax_rate'] > 0 ) {
			$msp->transaction['items'] .= '<li>' . $invoice['total_tax_name'] . ' ' . $invoice['total_tax_rate'] . '% = ' . dollar( $invoice['total_tax'], true, $invoice['currency_id'] ) . '</li>';
		}
		$msp->transaction['items'] .= '<li>Total: ' . dollar( $invoice['total_amount'], true, $invoice['currency_id'] ) . '</li>';
		$msp->transaction['items'] .= '</ul>';

		// returns a payment url
		$url = $msp->startTransaction();

		if ( $msp->error ) {
			echo "Error " . $msp->error_code . ": " . $msp->error;
			exit();
		}

		// redirect
		redirect_browser( $url );
		/*
						$url = 'https://www.'. (self::is_sandbox()? 'sandbox.' : '') . 'multisafepay.com/cgi-bin/webscr?';
		
						$fields = array(
								'cmd' => '_xclick',
								'business' => module_config::c('payment_method_multisafepay_email',_ERROR_EMAIL),
								'currency_code' => $currency['code'],
								'item_name' => $description,
								'amount' => $amount,
								'return' => module_invoice::link_open($invoice_id),
								'notify_url' => full_link(_EXTERNAL_TUNNEL.'?m=paymethod_multisafepay&h=ipn&method=multisafepay'),
								'custom' => self::multisafepay_custom($user_id,$payment_id,$invoice_id),
						);
		
						foreach($fields as $key=>$val){
								$url .= $key.'='.urlencode($val).'&';
						}
		
						//echo '<a href="'.$url.'">'.$url.'</a>';exit;
		
						redirect_browser($url);
		*/
	}

	public static function multisafepay_custom( $user_id, $payment_id, $invoice_id ) {
		return $user_id . '|' . $payment_id . '|' . $invoice_id . '|' . md5( _UCM_SECRET . " user: $user_id payment: $payment_id invoice: $invoice_id " );
	}

	function handle_multisafepay_ipn() {

		ob_end_clean();
		ini_set( 'display_errors', false );


		include( 'MultiSafepay.combined.php' );

		$msp = new MultiSafepay();

		// transaction id (same as the transaction->id given in the transaction request)
		$transactionid = isset( $_GET['transactionid'] ) ? $_GET['transactionid'] : false;
		if ( ! $transactionid ) {
			send_error( 'No MultiSafepay transaction ID' );
		}
		$multisafepay_bits = explode( "|", $transactionid );
		$user_id           = $multisafepay_bits[0];
		$payment_id        = (int) $multisafepay_bits[1];
		$invoice_id        = (int) $multisafepay_bits[2];
		//send_error('bad?');

		//send_error($payment_id.' multisafepay IPN check started',var_export($_REQUEST,true));

		if ( $payment_id && $invoice_id ) {
			$hash = $this->multisafepay_custom( $user_id, $payment_id, $invoice_id );
			if ( $hash != $transactionid ) {
				send_error( "Multisafepay IPN Error (incorrect hash)" );
				exit;
			}
			$user_id = (int) $user_id;// sometimes userid is ''

			$paymetn_history = get_single( 'invoice_payment', 'invoice_payment_id', $payment_id );
			if ( ! $paymetn_history ) {
				send_error( "Unknown Multisafe Payment - maybe a history was deleted?" );
				exit;
			}

			// (notify.php?type=initial is used as notification_url and should output a link)
			$initial = ( isset( $_GET['type'] ) && $_GET['type'] == "initial" );

			/*
			 * Merchant Settings
			 */
			//        $msp->test                         = MSP_TEST_API;
			//        $msp->merchant['account_id']       = MSP_ACCOUNT_ID;
			//        $msp->merchant['site_id']          = MSP_SITE_ID;
			//        $msp->merchant['site_code']        = MSP_SITE_CODE;

			$msp->test                   = self::is_sandbox();
			$msp->merchant['account_id'] = module_config::c( 'payment_method_multisafepay_account', '' );
			$msp->merchant['site_id']    = module_config::c( 'payment_method_multisafepay_site_id', '' );
			$msp->merchant['site_code']  = module_config::c( 'payment_method_multisafepay_side_code', '' );

			/*
			 * Transaction Details
			 */
			$msp->transaction['id'] = $transactionid;


			// returns the status
			$status = $msp->getStatus();

			if ( $msp->error && ! $initial ) { // only show error if we dont need to display the link
				echo "Error " . $msp->error_code . ": " . $msp->error;
				exit();
			}

			//send_error($payment_id.' MultiSafepay Status of '.$status,var_export($_REQUEST,true));

			$payment_history_data = isset( $paymetn_history['data'] ) && strlen( $paymetn_history['data'] ) ? unserialize( $paymetn_history['data'] ) : array();
			if ( ! is_array( $payment_history_data ) ) {
				$payment_history_data = array();
			}
			if ( ! isset( $payment_history_data['log'] ) ) {
				$payment_history_data['log'] = array();
			}
			$payment_history_data['log'][] = 'Payment ' . $status . ' at ' . print_date( time(), true );
			update_insert( "invoice_payment_id", $payment_id, "invoice_payment", array(
				'data' => serialize( $payment_history_data ),
			) );

			switch ( $status ) {
				case "initialized": // waiting
					break;
				case "completed":   // payment complete
					update_insert( "invoice_payment_id", $payment_id, "invoice_payment", array(
						'date_paid' => date( 'Y-m-d' ),
						'method'    => 'MultiSafepay',
					) );
					module_invoice::save_invoice( $invoice_id, array() );
					break;
				case "uncleared":   // waiting (credit cards or direct debit)
					break;
				case "void":        // canceled
					break;
				case "declined":    // declined
					break;
				case "refunded":    // refunded
					send_error( "Multisafepay Error! The payment $payment_id has been refunded or reversed! BAD BAD! You have to follup up customer for money manually now." );
					break;
				case "expired":     // expired
					break;
				default:
			}

			if ( $initial ) {
				// displayed at the last page of the transaction proces (if no redirect_url is set)
				echo '<a href="' . module_invoice::link_public( $invoice_id ) . '">Return to Invoice</a>';
			} else {
				// link to notify.php for MultiSafepay back-end (for delayed payment notifications)
				// backend expects an "ok" if no error occurred
				echo "ok";
			}
		} else {
			send_error( 'No bits in transaction id' );
		}

		exit;

		$multisafepay_bits = explode( "|", $_REQUEST['custom'] );
		$user_id           = (int) $multisafepay_bits[0];
		$payment_id        = (int) $multisafepay_bits[1];
		$invoice_id        = (int) $multisafepay_bits[2];
		//send_error('bad?');
		if ( $user_id && $payment_id && $invoice_id ) {
			$hash = $this->multisafepay_custom( $user_id, $payment_id, $invoice_id );
			if ( $hash != $_REQUEST['custom'] ) {
				send_error( "Multisafepay IPN Error (incorrect hash)" );
				exit;
			}

			$sql = "SELECT * FROM `" . _DB_PREFIX . "user` WHERE user_id = '$user_id' LIMIT 1";
			$res = qa( $sql );
			if ( $res ) {

				$user = array_shift( $res );
				if ( $user && $user['user_id'] == $user_id ) {

					// check for payment exists
					$payment = module_invoice::get_invoice_payment( $payment_id );
					$invoice = module_invoice::get_invoice( $invoice_id );
					if ( $payment && $invoice ) {

						$invoice_currency      = module_config::get_currency( $invoice['currency_id'] );
						$invoice_currency_code = $invoice_currency['code'];

						// check correct business
						if ( ! $_REQUEST['business'] && $_REQUEST['receiver_email'] ) {
							$_REQUEST['business'] = $_REQUEST['receiver_email'];
						}
						if ( $_REQUEST['business'] != module_config::c( 'payment_method_multisafepay_email', _ERROR_EMAIL ) ) {
							send_error( 'Multisafepay error! Paid the wrong business name. ' . $_REQUEST['business'] . ' instead of ' . module_config::c( 'payment_method_multisafepay_email', _ERROR_EMAIL ) );
							exit;
						}
						// check correct currency
						if ( $invoice_currency_code && $_REQUEST['mc_currency'] != $invoice_currency_code ) {
							send_error( 'Multisafepay error! Paid the wrong currency code. ' . $_REQUEST['mc_currency'] . ' instead of ' . $invoice_currency_code );
							exit;
						}

						if ( $_REQUEST['payment_status'] == "Canceled_Reversal" || $_REQUEST['payment_status'] == "Refunded" ) {
							// funky refund!! oh noes!!
							// TODO: store this in the database as a negative payment... should be easy.
							// populate $_REQUEST vars then do something like $payment_history_id = update_insert("payment_history_id","new","payment_history");
							send_error( "Multisafepay Error! The payment $payment_id has been refunded or reversed! BAD BAD! You have to follup up customer for money manually now." );

						} else if ( $_REQUEST['payment_status'] == "Completed" ) {

							// payment is completed! yeye getting closer...

							switch ( $_REQUEST['txn_type'] ) {
								case "web_accept":
									// running in multisafepay sandbox or not?
									//$sandbox = (self::is_sandbox())?"sandbox.":'';
									// quick check we're not getting a fake payment request.
									$url    = 'https://www.' . ( self::is_sandbox() ? 'sandbox.' : '' ) . 'multisafepay.com/cgi-bin/webscr';
									$result = self::fsockPost( $url, $_POST );
									//send_error('multisafepay sock post: '.$url."\n\n".var_export($result,true));
									if ( eregi( "VERIFIED", $result ) ) {
										// finally have everything.
										// mark the payment as completed.
										update_insert( "invoice_payment_id", $payment_id, "invoice_payment", array(
											'date_paid' => date( 'Y-m-d' ),
											'amount'    => $_REQUEST['mc_gross'],
											'method'    => 'Multisafepay (IPN)',
										) );

										module_invoice::save_invoice( $invoice_id, array() );

										echo "Successful Payment!";

									} else {
										send_error( "Multisafepay IPN Error (multisafepay rejected the payment!) " . var_export( $result, true ) );
									}
									break;
								case "subscr_signup":
								default:
									// TODO: support different payment methods later? like a monthly hosting fee..
									send_error( "Multisafepay IPN Error (we dont currently support this payment method: " . $_REQUEST['txn_type'] . ")" );
									break;
							}
						} else {
							send_error( "Multisafepay info: This payment is not yet completed, this usually means it's an e-cheque, follow it up in a few days if you dont hear anything. This also means you may have to login to multisafepay and 'Accept' the payment. So check there first." );
						}

					} else {
						send_error( "Multisafepay IPN Error (no payment found in database!)" );
					}
				} else {
					send_error( "Multisafepay IPN Error (error with user that was found in database..)" );
				}
			} else {
				send_error( "Multisafepay IPN Error (no user found in database #1)" );
			}


		} else {
			send_error( "Multisafepay IPN Error (no user id found)" );
		}


		exit;
	}
}