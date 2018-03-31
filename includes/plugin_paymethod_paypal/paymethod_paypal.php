<?php

//306412171 ucm_1306412206_per@gmail.com


class module_paymethod_paypal extends module_base {


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
		$this->version         = 2.261;
		$this->module_name     = "paymethod_paypal";
		$this->module_position = 8882;

		// 2.261 - 2017-02-27 - config fix
		// 2.260 - 2017-01-05 - php7 support
		// 2.259 - 2015-03-14 - better default payment method options
		// 2.258 - 2014-12-22 - paypal fee bug fix in subscriptions
		// 2.257 - 2014-09-05 - paypal trial period feature
		// 2.256 - 2014-06-27 - more paypal settings
		// 2.255 - 2014-06-23 - paypal fee reverse calculation support
		// 2.254 - 2014-06-11 - added payment_method_paypal_force_subscription advanced setting
		// 2.253 - 2014-03-12 - paypal fee + currency restriction
		// 2.252 - 2014-03-10 - paypal fee support
		// 2.251 - 2014-01-20 - paypal automatic subscription payments
		// 2.25 - 2014-01-18 - starting work on automatic recurring payments
		// 2.24 - 2013-09-20 - paypal invoice payment fix for invoices without assigned contacts
		// 2.23 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.22 - 2013-04-16 - added paypal page style
		// 2.21 - perm fix
	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "PayPal",
				"p"                   => "paypal_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_payment',  // which page this link will be automatically added to.
				'menu_include_parent' => 1,
			);
		}
	}

	public static function is_sandbox() {
		return module_config::c( 'payment_method_paypal_sandbox', 0 );
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
		return module_config::c( 'payment_method_paypal_enabled', 1 );
	}


	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_paypal_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_paypal_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['paypal'] ) ) {
			return $invoice_payment_methods['paypal']['enabled'];
		}
		// check if this invoice is in the allowed currency.
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$cur          = trim( strtolower( module_config::c( 'payment_method_paypal_currency', '' ) ) );
		$dollar_limit = module_config::c( 'payment_method_paypal_limit_type', 'above' );
		$dollar_value = module_config::c( 'payment_method_paypal_limit_value', 0 );

		if ( $dollar_limit == 'above' && $invoice_data['total_amount_due'] < $dollar_value ) {
			return false;
		} else if ( $dollar_limit == 'below' && $invoice_data['total_amount_due'] > $dollar_value ) {
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

		return module_config::c( 'payment_method_paypal_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'paypal', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_paypal_label', 'PayPal' );
	}


	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

	}

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id, $user_id = false ) {
		if ( $invoice_id && $payment_amount && $invoice_payment_id ) {
			// we are starting a payment via paypal!
			// setup a pending payment and redirect to paypal.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			if ( ! $user_id ) {
				$user_id = $invoice_data['user_id'];
			}
			if ( ! $user_id ) {
				$user_id = isset( $invoice_data['primary_user_id'] ) ? $invoice_data['primary_user_id'] : 0;
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

			// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
			$fee_percent     = module_config::c( 'payment_method_paypal_charge_percent', 0 );
			$fee_amount      = module_config::c( 'payment_method_paypal_charge_amount', 0 );
			$fee_description = module_config::c( 'payment_method_paypal_charge_description', 'PayPal Fee' );
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

			// we check if this payment is a recurring payment or a standard one off payment.
			if ( module_config::c( 'payment_method_paypal_subscriptions', 0 ) ) {
				// we support subscriptions!
				// first check if the subscription module is active, and if this invoice is part of an active subscription.
				$is_subscription = false;
				if ( class_exists( 'module_subscription', false ) ) {
					$subscription_history = get_single( 'subscription_history', 'invoice_id', $invoice_id );
					if ( $subscription_history && $subscription_history['subscription_id'] ) {
						// this invoice is for a subscription! woo!
						// work out when we should bill for this subscription.
						$subscription       = module_subscription::get_subscription( $subscription_history['subscription_id'] );
						$subscription_owner = module_subscription::get_subscription_owner( $subscription_history['subscription_owner_id'] );
						if ( $subscription_owner['owner_table'] && $subscription_owner['owner_id'] ) {
							// work out when the next invoice will be generated for this subscription.
							$members_subscriptions = module_subscription::get_subscriptions_by( $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
							if ( isset( $members_subscriptions[ $subscription_history['subscription_id'] ] ) ) {
								$member_subscription = $members_subscriptions[ $subscription_history['subscription_id'] ];
								// everything checks out! good to go....

								// for now we just do a basic "EVERY X TIME" subscription
								// todo: work out how long until next generate date, and set that (possibly smaller) time period as the first portion of the subscription
								/*echo '<pre>';
								print_r($subscription_history);
								print_r($subscription);
								print_r($subscription_owner);
								print_r($member_subscription);
								exit;*/

								$is_subscription = array();
								if ( $subscription['days'] > 0 ) {
									$is_subscription['days'] = $subscription['days'];
								}
								if ( $subscription['months'] > 0 ) {
									$is_subscription['months'] = $subscription['months'];
								}
								if ( $subscription['years'] > 0 ) {
									$is_subscription['years'] = $subscription['years'];
								}
								if ( count( $is_subscription ) ) {
									$is_subscription['name'] = $subscription['name'];
								}

							}
						}
					}
				}
				// todo: check if this invoice has a manual renewal date, perform subscription feature as above.

				if ( $is_subscription ) {

					$bits = array();
					if ( isset( $is_subscription['days'] ) && $is_subscription['days'] > 0 ) {
						$bits[] = _l( '%s days', $is_subscription['days'] );
					}
					if ( isset( $is_subscription['months'] ) && $is_subscription['months'] > 0 ) {
						$bits[] = _l( '%s months', $is_subscription['months'] );
					}
					if ( isset( $is_subscription['years'] ) && $is_subscription['years'] > 0 ) {
						$bits[] = _l( '%s years', $is_subscription['years'] );
					}

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					if ( isset( $invoice_payment_data['invoice_payment_subscription_id'] ) && (int) $invoice_payment_data['invoice_payment_subscription_id'] > 0 ) {
						// existing subscription already!
						// not really sure what to do here, just redirect to paypal as if the user is doing it for the first time.
						$_REQUEST['payment_subscription'] = true; // hacks!
					}

					if ( isset( $_REQUEST['payment_subscription'] ) || module_config::c( 'payment_method_paypal_force_subscription', 0 ) ) {
						// user is setting up a subscription! yes!!

						// we create an entry in our database for this particular subscription
						// or if one exists for this payment already then we just continue with that (ie: the user is going in again to redo it)

						// setup a new subscription in the database for us.
						if ( isset( $invoice_payment_data['invoice_payment_subscription_id'] ) && (int) $invoice_payment_data['invoice_payment_subscription_id'] > 0 ) {
							$invoice_payment_subscription_id = $invoice_payment_data['invoice_payment_subscription_id'];
						} else {
							$invoice_payment_subscription_id = update_insert( 'invoice_payment_subscription_id', false, 'invoice_payment_subscription', array(
								'status'        => _INVOICE_SUBSCRIPTION_PENDING,
								'days'          => isset( $is_subscription['days'] ) ? $is_subscription['days'] : 0,
								'months'        => isset( $is_subscription['months'] ) ? $is_subscription['months'] : 0,
								'years'         => isset( $is_subscription['years'] ) ? $is_subscription['years'] : 0,
								'date_start'    => '0000-00-00',
								'date_last_pay' => '0000-00-00',
								'date_next'     => '0000-00-00',
							) );
							update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array(
								'invoice_payment_subscription_id' => $invoice_payment_subscription_id
							) );
						}

						$description = _l( 'Recurring payment for %s every %s', $is_subscription['name'], implode( ', ', $bits ) );
						unset( $is_subscription['name'] ); // so reset/key cals below rosk.

						$currency = module_config::get_currency( $invoice_payment_data['currency_id'] );

						$url = 'https://www.' . ( self::is_sandbox() ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr?';

						// if there are more than 1 recurring amounts then we convert it to days, as paypal only supports one time period.
						if ( count( $is_subscription ) > 1 ) {
							$days = isset( $is_subscription['days'] ) ? $is_subscription['days'] : 0;
							if ( isset( $is_subscription['months'] ) ) {
								$days += ( $is_subscription['months'] * 30 );
								unset( $is_subscription['months'] );
							}
							if ( isset( $is_subscription['years'] ) ) {
								$days += ( $is_subscription['years'] * 365 );
								unset( $is_subscription['years'] );
							}
							$is_subscription['days'] = $days;
						}
						reset( $is_subscription );
						$time = key( $is_subscription );
						if ( $time == 'days' ) {
							$time = 'D';
						} else if ( $time == 'months' ) {
							$time = 'M';
						} else if ( $time == 'years' ) {
							$time = 'Y';
						}

						$fields = array(
							'cmd'           => '_xclick-subscriptions',
							'business'      => module_config::c( 'payment_method_paypal_email', _ERROR_EMAIL ),
							'currency_code' => $currency['code'],
							'item_name'     => $description,
							'no_shipping'   => 1,
							//'amount' => $payment_amount,
							'page_style'    => module_config::c( 'paypal_page_style', '' ),
							'return'        => module_invoice::link_public_payment_complete( $invoice_id ),
							'rm'            => 1,
							'cancel_return' => module_invoice::link_public( $invoice_id ),
							'notify_url'    => full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ),
							'custom'        => self::paypal_custom( $user_id, $invoice_payment_id, $invoice_id, $invoice_payment_subscription_id ),
							'a3'            => $payment_amount,
							'p3'            => current( $is_subscription ),
							't3'            => $time,
							'src'           => 1,
							'sra'           => 1,
							'no_note'       => 1,
						);
						// is there a subscription trail period
						if ( isset( $subscription['settings']['trial_period'] ) && $subscription['settings']['trial_period'] > 0 ) {
							// we have to hacck the payment_amount here.
							// $payment_amount will be the discounted amount (eg: $5 instead of $10)
							// so we reverse that discounted amount for the real amount.
							$real_amount    = $payment_amount - $fee_amount - ( isset( $subscription['settings']['trial_price_adjust'] ) ? $subscription['settings']['trial_price_adjust'] : 0 );
							$real_fee_total = module_invoice::calculate_fee( $invoice_id, $invoice_data, $real_amount, array(
								'percent'     => $fee_percent,
								'amount'      => $fee_amount,
								'description' => $fee_description,
							) );
							$real_amount    += $real_fee_total;
							$fields['a3']   = $real_amount;
							$fields['a1']   = $payment_amount; // $real_amount + (isset($subscription['settings']['trial_price_adjust']) ? $subscription['settings']['trial_price_adjust'] : 0);
							$fields['p1']   = current( $is_subscription ); // * $subscription['settings']['trial_period'];
							$fields['t1']   = $time;
						}
						//echo '<pre>'; print_r($fields);exit;


						foreach ( $fields as $key => $val ) {
							$url .= $key . '=' . urlencode( $val ) . '&';
						}

						//echo '<a href="'.$url.'">'.$url.'</a>';exit;

						redirect_browser( $url );

					} else if ( isset( $_REQUEST['payment_single'] ) ) {
						// use is choosing to continue payment as a once off amount

					} else {
						// give the user an option

						module_template::init_template( 'invoice_payment_subscription', '<h2>Payment for Invoice {INVOICE_NUMBER}</h2>
                        <p>Please choose from the available payment options below:</p>
                        <form action="{PAYMENT_URL}" method="post">
                        <input type="hidden" name="invoice_payment_id" value="{INVOICE_PAYMENT_ID}">
                        <input type="hidden" name="payment_method" value="{PAYMENT_METHOD}">
                        <input type="hidden" name="payment_amount" value="{PAYMENT_AMOUNT}">
                        <p><input type="submit" name="payment_single" value="Pay a Once Off amount of {PRETTY_PAYMENT_AMOUNT}"></p>
                        <p><input type="submit" name="payment_subscription" value="Setup Automatic Payments of {PRETTY_PAYMENT_AMOUNT} every {SUBSCRIPTION_PERIOD}"></p>
                        </form>
                        ', 'Used when a customer tries to pay an invoice that has a subscription option.', 'code' );
						$template = module_template::get_template_by_key( 'invoice_payment_subscription' );

						$template->page_title = htmlspecialchars( $invoice_data['name'] );

						$template->assign_values( $invoice_payment_data );
						$template->assign_values( module_invoice::get_replace_fields( $invoice_data['invoice_id'], $invoice_data ) );
						$template->assign_values( array(
							'invoice_payment_id'    => $invoice_payment_id,
							'payment_url'           => module_invoice::link_public_pay( $invoice_data['invoice_id'] ),
							'payment_method'        => 'paymethod_paypal',
							'payment_amount'        => $payment_amount,
							'pretty_payment_amount' => dollar( $payment_amount, true, $invoice_data['currency_id'] ),
							'subscription_period'   => implode( ', ', $bits ),
							'fee_amount'            => dollar( $fee_amount, true, $invoice_data['currency_id'] ),
							'fee_total'             => dollar( $fee_total, true, $invoice_data['currency_id'] ),
							'fee_percent'           => $fee_percent,
							'fee_description'       => $fee_description,
						) );
						echo $template->render( 'pretty_html' );
						exit;
					}


				}
			}

			$description = _l( 'Payment for Invoice %s', $invoice_data['name'] );
			self::paypal_redirect( $description, $payment_amount, $user_id, $invoice_payment_id, $invoice_id, $invoice_payment_data['currency_id'] );

			return true;
		}

		return false;
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'ipn':
				// handle IPN response from paypal.
				$this->handle_paypal_ipn();
				break;
		}
	}

	public static function paypal_redirect( $description, $amount, $user_id, $payment_id, $invoice_id, $currency_id ) {

		$currency = module_config::get_currency( $currency_id );

		$url = 'https://www.' . ( self::is_sandbox() ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr?';

		$fields = array(
			'cmd'           => '_xclick',
			'business'      => module_config::c( 'payment_method_paypal_email', _ERROR_EMAIL ),
			'currency_code' => $currency['code'],
			'item_name'     => $description,
			'amount'        => $amount,
			'page_style'    => module_config::c( 'paypal_page_style', '' ),
			'return'        => module_invoice::link_public_payment_complete( $invoice_id ),
			'rm'            => 1,
			'notify_url'    => full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ),
			'custom'        => self::paypal_custom( $user_id, $payment_id, $invoice_id ),
		);

		foreach ( $fields as $key => $val ) {
			$url .= $key . '=' . urlencode( $val ) . '&';
		}

		//echo '<a href="'.$url.'">'.$url.'</a>';exit;

		redirect_browser( $url );

	}

	public static function fsockPost( $url, $data ) {
		$web      = parse_url( $url );
		$postdata = '';
		$info     = array();
		//build post string
		foreach ( $data as $i => $v ) {
			$postdata .= $i . "=" . urlencode( $v ) . "&";
		}
		$postdata .= "cmd=_notify-validate";
		$ssl      = '';
		if ( $web['scheme'] == "https" ) {
			$web['port'] = "443";
			$ssl         = "ssl://";
		} else {
			$web['port'] = "80";
		}

		//Create paypal connection
		// todo - this can generate an "unknown ssl" error.
		$fp = @fsockopen( $ssl . $web['host'], $web['port'], $errnum, $errstr, 30 );

		//Error checking
		if ( ! $fp ) {
			send_error( "There was a problem with PayPal IPN and fsockopen: $errnum: $errstr" );

			return false;
		} else {
			fputs( $fp, "POST $web[path] HTTP/1.1\r\n" );
			fputs( $fp, "Host: $web[host]\r\n" );
			fputs( $fp, "Content-type: application/x-www-form-urlencoded\r\n" );
			fputs( $fp, "Content-length: " . strlen( $postdata ) . "\r\n" );
			fputs( $fp, "Connection: close\r\n\r\n" );
			fputs( $fp, $postdata . "\r\n\r\n" );
			//loop through the response from the server
			while ( ! feof( $fp ) ) {
				$info[] = @fgets( $fp, 1024 );
			}
			//close fp - we are done with it
			fclose( $fp );
			//break up results into a string
			$info = implode( ",", $info );
		}

		return $info;
	}


	public static function paypal_custom( $user_id, $payment_id, $invoice_id, $invoice_payment_subscription_id = false ) {
		if ( $invoice_payment_subscription_id ) {
			return ( (int) $user_id ) . '|' . $payment_id . '|' . $invoice_id . '|' . $invoice_payment_subscription_id . '|' . md5( _UCM_SECRET . " user: " . ( (int) $user_id ) . " payment: $payment_id invoice: $invoice_id subscription $invoice_payment_subscription_id" );
		} else {
			return ( (int) $user_id ) . '|' . $payment_id . '|' . $invoice_id . '|' . md5( _UCM_SECRET . " user: " . ( (int) $user_id ) . " payment: $payment_id invoice: $invoice_id " );
		}
	}

	function handle_paypal_ipn() {

		ob_end_clean();

		if ( ! isset( $_REQUEST['custom'] ) ) {
			return;
		}

		$paypal_bits                     = explode( "|", $_REQUEST['custom'] );
		$user_id                         = (int) $paypal_bits[0];
		$payment_id                      = (int) $paypal_bits[1];
		$invoice_id                      = (int) $paypal_bits[2];
		$invoice_payment_subscription_id = false;
		if ( count( $paypal_bits ) == 4 ) {
			// normal IPN, single payment.
		} else if ( count( $paypal_bits ) == 5 ) {
			// subscription IPN, with subscription id.
			$invoice_payment_subscription_id = (int) $paypal_bits[3];
			$invoice_payment_subscription    = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $invoice_payment_subscription_id );
		}
		//send_error('bad?');
		if ( $payment_id && $invoice_id ) {
			$hash = $this->paypal_custom( $user_id, $payment_id, $invoice_id, $invoice_payment_subscription_id );
			if ( $hash != $_REQUEST['custom'] ) {
				send_error( "PayPal IPN Error (incorrect hash) it should be " . $hash );
				exit;
			}

			/*$sql = "SELECT * FROM `"._DB_PREFIX."user` WHERE user_id = '$user_id' LIMIT 1";
			$res = qa($sql);
			if($res){

					$user = array_shift($res);
					if($user && $user['user_id'] == $user_id){*/

			// check for payment exists
			$payment = module_invoice::get_invoice_payment( $payment_id );
			$invoice = module_invoice::get_invoice( $invoice_id );
			if ( $payment && $invoice ) {

				/*if(isset($_REQUEST['fakepay'])){
						if($invoice_payment_subscription_id){
								// we have a subscription payment. woo!
								// this gets a bit tricky, we have to work out if the invoice has been generated for this subscription yet.
								// if this invoice hasn't been generated yet then we have to generate it.
								// pass this back to the invoice class so we can reuse this feature in the future.
								$data = module_invoice::create_new_invoice_for_subscription_payment($invoice_id, $payment_id, $invoice_payment_subscription_id);
								if($data && $data['invoice_id'] && $data['invoice_payment_id']){

										$next_time = time();
										$next_time = strtotime('+'.abs((int)$invoice_payment_subscription['days']).' days',$next_time);
										$next_time = strtotime('+'.abs((int)$invoice_payment_subscription['months']).' months',$next_time);
										$next_time = strtotime('+'.abs((int)$invoice_payment_subscription['years']).' years',$next_time);
										update_insert('invoice_payment_subscription_id',$invoice_payment_subscription_id,'invoice_payment_subscription',array(
												'date_last_pay' => date('Y-m-d'),
												'date_next' => date('Y-m-d',$next_time),
										));
										$new_payment_details = array(
													'date_paid' => date('Y-m-d'),
													'amount' => $_REQUEST['mc_gross'],
													'method' => 'PayPal (Subscription)',
													'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
										 );
										foreach(array('fee_percent','fee_amount','fee_description','fee_total') as $fee_field){
												if(isset($payment[$fee_field])) {
														$new_payment_details[ $fee_field ] = $payment[ $fee_field ];
												}
										}
										 update_insert("invoice_payment_id",$data['invoice_payment_id'],"invoice_payment",$new_payment_details);

										module_invoice::save_invoice($data['invoice_id'],array());

										echo "Successful Subscription Payment!";

								}else{
										send_error("PayPal IPN Subscription Error (failed to generate new invoice!) ".var_export($result,true));
								}

						}else{
								// mark a normal payment as paid

								update_insert("invoice_payment_id",$payment_id,"invoice_payment",array(
													'date_paid' => date('Y-m-d'),
													'amount' => $_REQUEST['mc_gross'],
													'method' => 'PayPal (IPN)',
								 ));

								module_invoice::save_invoice($invoice_id,array());

								echo "Successful Payment!";

						}
						echo 'fakepay done';exit;
				}*/

				$invoice_currency      = module_config::get_currency( $invoice['currency_id'] );
				$invoice_currency_code = $invoice_currency['code'];

				// check correct business
				if ( ! $_REQUEST['business'] && $_REQUEST['receiver_email'] ) {
					$_REQUEST['business'] = $_REQUEST['receiver_email'];
				}
				if ( $_REQUEST['business'] != module_config::c( 'payment_method_paypal_email', _ERROR_EMAIL ) ) {
					send_error( 'PayPal error! Paid the wrong business name. ' . $_REQUEST['business'] . ' instead of ' . module_config::c( 'payment_method_paypal_email', _ERROR_EMAIL ) );
					exit;
				}
				// check correct currency
				if ( $invoice_currency_code && $_REQUEST['mc_currency'] != $invoice_currency_code ) {
					send_error( 'PayPal error! Paid the wrong currency code. ' . $_REQUEST['mc_currency'] . ' instead of ' . $invoice_currency_code );
					exit;
				}
				switch ( $_REQUEST['txn_type'] ) {
					// handle subscriptions first.
					// https://www.paypal.com/au/cgi-bin/webscr?cmd=p/acc/ipn-subscriptions-outside
					case "subscr_signup":
						// started! we update the start date of this one.
						if ( $invoice_payment_subscription_id ) {
							update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
								'status'     => _INVOICE_SUBSCRIPTION_ACTIVE,
								'date_start' => date( 'Y-m-d' ),
							) );
						}
						break;
					case "subscr_cancel":
					case "subscr_failed":
					case "subscr_eot":
						if ( $invoice_payment_subscription_id ) {
							update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
								'status' => _INVOICE_SUBSCRIPTION_FAILED,
							) );
						}
						break;
						break;
					case "subscr_payment":
					case "web_accept":

						if ( $_REQUEST['payment_status'] == "Canceled_Reversal" || $_REQUEST['payment_status'] == "Refunded" ) {
							// funky refund!! oh noes!!
							// TODO: store this in the database as a negative payment... should be easy.
							// populate $_REQUEST vars then do something like $payment_history_id = update_insert("payment_history_id","new","payment_history");
							send_error( "PayPal Error! The payment $payment_id has been refunded or reversed! BAD BAD! You have to follup up customer for money manually now." );

						} else if ( $_REQUEST['payment_status'] == "Completed" ) {

							// payment is completed! yeye getting closer...

							// running in paypal sandbox or not?
							//$sandbox = (self::is_sandbox())?"sandbox.":'';
							// quick check we're not getting a fake payment request.
							$url    = 'https://www.' . ( self::is_sandbox() ? 'sandbox.' : '' ) . 'paypal.com/cgi-bin/webscr';
							$result = self::fsockPost( $url, $_POST );
							//send_error('paypal sock post: '.$url."\n\n".var_export($result,true));
							if ( stripos( $result, "VERIFIED" ) !== false ) {
								// finally have everything.
								// mark the payment as completed.

								if ( $invoice_payment_subscription_id ) {
									// we have a subscription payment. woo!
									// this gets a bit tricky, we have to work out if the invoice has been generated for this subscription yet.
									// if this invoice hasn't been generated yet then we have to generate it.
									// pass this back to the invoice class so we can reuse this feature in the future.
									$data = module_invoice::create_new_invoice_for_subscription_payment( $invoice_id, $payment_id, $invoice_payment_subscription_id );
									if ( $data && $data['invoice_id'] && $data['invoice_payment_id'] ) {

										$next_time = time();
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['days'] ) . ' days', $next_time );
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['months'] ) . ' months', $next_time );
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['years'] ) . ' years', $next_time );
										update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
											'date_last_pay' => date( 'Y-m-d' ),
											'date_next'     => date( 'Y-m-d', $next_time ),
										) );
										$new_payment_details = array(
											'date_paid'                       => date( 'Y-m-d' ),
											'amount'                          => $_REQUEST['mc_gross'],
											'method'                          => 'PayPal (Subscription)',
											'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
										);
										foreach ( array( 'fee_percent', 'fee_amount', 'fee_description', 'fee_total' ) as $fee_field ) {
											if ( isset( $payment[ $fee_field ] ) ) {
												$new_payment_details[ $fee_field ] = $payment[ $fee_field ];
											}
										}
										update_insert( "invoice_payment_id", $data['invoice_payment_id'], "invoice_payment", $new_payment_details );

										module_invoice::save_invoice( $data['invoice_id'], array() );

										echo "Successful Subscription Payment!";

									} else {
										send_error( "PayPal IPN Subscription Error (failed to generate new invoice!) " . var_export( $result, true ) );
									}

								} else {
									// mark a normal payment as paid

									update_insert( "invoice_payment_id", $payment_id, "invoice_payment", array(
										'date_paid' => date( 'Y-m-d' ),
										'amount'    => $_REQUEST['mc_gross'],
										'method'    => 'PayPal (IPN)',
									) );

									module_invoice::save_invoice( $invoice_id, array() );

									echo "Successful Payment!";

								}
								/*// send customer an email thanking them for their payment.
								$sql = "SELECT * FROM "._DB_PREFIX."users WHERE user_id = '"._ADMIN_USER_ID."'";
								$res = qa($sql);
								$admin = array_shift($res);
								$from_email = $admin['email'];
								$from_name = $admin['real_name'];
								$mail_content = "Dear ".$user['real_name'].", \n\n";
								$mail_content .= "Your ".dollar($payment['outstanding'])." payment for '".$payment['description']."' has been processed. \n\n";
								$mail_content .= "We have successfully recorded your ".dollar($_REQUEST['mc_gross'])." payment in our system.\n\n";
								$mail_content .= "You will receive another email shortly from PayPal with details of the transaction.\n\n";
								$mail_content .= "Kind Regards,\n\n";
								$mail_content .= $from_name."\n".$from_email;

								send_error("PayPal SUCCESS!! User has paid you ".$_REQUEST['mc_gross']." we have recorded this against the payment and sent them an email");
								//$this->send_email( $payment_id, $user['email'], $mail_content, "Payment Successful", $from_email, $from_name );
								send_email($user['email'], "Payment Successful", $mail_content, array("FROM"=>$from_email,"FROM_NAME"=>$from_name));
								*/
								// check if it's been paid in full..


							} else {
								send_error( "PayPal IPN Error (paypal rejected the payment!) " . var_export( $result, true ) );
							}
						} else {
							send_error( "PayPal info: This payment is not yet completed, this usually means it's an e-cheque, follow it up in a few days if you dont hear anything. This also means you may have to login to paypal and 'Accept' the payment. So check there first." );
						}
						break;
					default:
						send_error( "PayPal IPN Error (unknown transaction t ype!) " );
						break;
				}

			} else {
				send_error( "PayPal IPN Error (no payment found in database!)" );
			}
			/*}else{
					send_error("PayPal IPN Error (error with user that was found in database..)");
			}
	}else{
			send_error("PayPal IPN Error (no user found in database #1)");
	}*/


		} else {
			send_error( "PayPal IPN Error (no payment or invoice id found)" );
		}


		exit;
	}
}