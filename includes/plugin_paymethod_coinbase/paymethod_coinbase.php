<?php


class module_paymethod_coinbase extends module_base {


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
		$this->version         = 2.11;
		$this->module_name     = "paymethod_coinbase";
		$this->module_position = 8882;
		// 2.1 - 2013-06-24 - initial release
		// 2.11 - 2015-03-14 - better default payment method options

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Coinbase (bitcoin)",
				"p"                   => "coinbase_settings",
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
		return module_config::c( 'payment_method_coinbase_enabled', 0 );
	}


	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_coinbase_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_coinbase_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['coinbase'] ) ) {
			return $invoice_payment_methods['coinbase']['enabled'];
		}

		// check currency and value amounts
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$cur          = trim( strtolower( module_config::c( 'payment_method_coinbase_currency', '' ) ) );
		$dollar_limit = module_config::c( 'payment_method_coinbase_limit_type', 'above' );
		$dollar_value = module_config::c( 'payment_method_coinbase_limit_value', 0 );

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

		return module_config::c( 'payment_method_coinbase_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'coinbase', `enabled` = " . (int) $allowed;
		query( $sql );
	}


	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_coinbase_label', 'Bitcoin' );
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
			// we are starting a payment via coinbase!
			// setup a pending payment and redirect to coinbase.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			if ( ! $user_id ) {
				$user_id = $invoice_data['user_id'];
			}
			if ( ! $user_id ) {
				$user_id = isset( $invoice_data['primary_user_id'] ) ? $invoice_data['primary_user_id'] : 0;
			}
			if ( ! $user_id ) {
				$user_id = module_security::get_loggedin_id();
			}
			$user_data = module_user::get_user( $user_id );
			if ( ! $user_data || ! strpos( $user_data['email'], '@' ) ) {
				die( 'Please ensure your user account has a valid email address before paying with coinbase' );
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

			// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
			$fee_percent     = module_config::c( 'payment_method_coinbase_charge_percent', 0 );
			$fee_amount      = module_config::c( 'payment_method_coinbase_charge_amount', 0 );
			$fee_description = module_config::c( 'payment_method_coinbase_charge_description', 'Coinbase Fee' );
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
			if ( module_config::c( 'payment_method_coinbase_subscriptions', 0 ) ) {
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
									$is_subscription['id']   = $subscription_history['subscription_id'];
								}

							}
						}
					}
				}
				// todo: check if this invoice has a manual renewal date, perform subscription feature as above.

				if ( $is_subscription ) {

					// coinbase only supports these recurring methods:
					// daily, weekly, every_two_weeks, monthly, quarterly, and yearly
					// work out which one our days are at.
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
					if ( $days == 1 ) {
						$is_subscription['coinbase_period'] = 'daily';
					} else if ( $days == 7 || $days == 6 || $days == 8 ) {
						$is_subscription['coinbase_period'] = 'weekly';
					} else if ( $days == 14 || $days == 13 || $days == 15 ) {
						$is_subscription['coinbase_period'] = 'every_two_weeks';
					} else if ( $days == 29 || $days == 30 || $days == 31 ) {
						$is_subscription['coinbase_period'] = 'monthly';
					} else if ( $days >= 87 && $days <= 95 ) {
						$is_subscription['coinbase_period'] = 'quarterly';
					} else if ( $days >= 363 && $days <= 370 ) {
						$is_subscription['coinbase_period'] = 'yearly';
					} else {
						send_error( 'Someone tried to pay with coinbase but coinbase does not support a recurring subscription period of ' . $days . ' days. Only:  daily, weekly, every_two_weeks, monthly, quarterly, and yearly ' );
						$is_subscription = false; // not supported.
					}

				}
				if ( $is_subscription && isset( $is_subscription['coinbase_period'] ) ) {

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					if ( isset( $invoice_payment_data['invoice_payment_subscription_id'] ) && (int) $invoice_payment_data['invoice_payment_subscription_id'] > 0 ) {
						// existing subscription already!
						// not really sure what to do here, just redirect to coinbase as if the user is doing it for the first time.
						$_REQUEST['payment_subscription'] = true; // hacks!
					}

					if ( isset( $_REQUEST['payment_subscription'] ) || module_config::c( 'payment_method_coinbase_force_subscription', 0 ) ) {
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

						$description       = _l( 'Recurring payment for %s %s', $is_subscription['name'], _l( str_replace( '_', ' ', $is_subscription['coinbase_period'] ) ) );
						$subscription_name = $is_subscription['name'];
						unset( $is_subscription['name'] ); // so reset/key cals below rosk.
						$subscription_id = $is_subscription['id'];
						unset( $is_subscription['id'] ); // so reset/key cals below rosk.

						$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
						$currency_code = $currency['code'];

						include( module_theme::include_ucm( 'includes/plugin_paymethod_coinbase/pages/coinbase_form.php' ) );
						exit;


					} else if ( isset( $_REQUEST['payment_single'] ) ) {
						// use is choosing to continue payment as a once off amount

					} else {
						// give the user an option

						$template = module_template::get_template_by_key( 'invoice_payment_subscription' );

						$template->page_title = htmlspecialchars( $invoice_data['name'] );

						$template->assign_values( $invoice_payment_data );
						$template->assign_values( module_invoice::get_replace_fields( $invoice_data['invoice_id'], $invoice_data ) );
						$template->assign_values( array(
							'invoice_payment_id'    => $invoice_payment_id,
							'payment_url'           => module_invoice::link_public_pay( $invoice_data['invoice_id'] ),
							'payment_method'        => 'paymethod_coinbase',
							'payment_amount'        => $payment_amount,
							'pretty_payment_amount' => dollar( $payment_amount, true, $invoice_data['currency_id'] ),
							'subscription_period'   => _l( '%s days (%s)', $is_subscription['days'], $is_subscription['coinbase_period'] ),
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

			$description = _l( 'Payment for invoice %s', $invoice_data['name'] );
			//self::coinbase_redirect($description,$payment_amount,$user_id,$invoice_payment_id,$invoice_id,$invoice_payment_data['currency_id']);
			$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
			$currency_code = $currency['code'];
			include( module_theme::include_ucm( 'includes/plugin_paymethod_coinbase/pages/coinbase_form.php' ) );

			/*$template = new module_template();
			 ob_start();
			 $template->content = ob_get_clean();
			 echo $template->render('pretty_html');*/
			exit;
		}

		return false;
	}

	public static function external_url_callback() {
		return full_link( _EXTERNAL_TUNNEL . '?m=paymethod_coinbase&h=event_ipn&method=coinbase' );
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'event_ipn':

				$body       = @file_get_contents( 'php://input' );
				$event_json = json_decode( $body );

				ob_start();
				echo "UCM coinbase DEBUG:<br><br>JSON: <br>\n";
				print_r( $event_json );
				echo "<br><br>\n";
				$success = false;

				$bits = explode( ':', isset( $event_json->order->custom ) ? $event_json->order->custom : '' );
				if ( count( $bits ) == 4 ) {
					// we have our custom bits, invoice_id, invoice_payment_id and hash
					// check they are right
					$invoice_id                      = (int) $bits[0];
					$invoice_payment_id              = (int) $bits[1];
					$invoice_payment_subscription_id = (int) $bits[2];
					$hash                            = $bits[3];
					$correct_hash                    = self::get_payment_key( $invoice_id, $invoice_payment_id, $invoice_payment_subscription_id, true );
					if ( $invoice_id && $invoice_payment_id && $hash == $correct_hash ) {


						// This will send receipts on succesful invoices
						// todo - coinbase doesnt sent this callback correctly just yet
						if ( $event_json && isset( $event_json->recurring_payment ) && $invoice_payment_subscription_id ) {
							// status changes on a recurring payment.
							$invoice_payment_subscription = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $invoice_payment_subscription_id );
							if ( ! $invoice_payment_subscription['date_start'] || $invoice_payment_subscription['date_start'] == '0000-00-00' ) {
								// no start date yet, set the start date now.
								if ( $event_json->recurring_payment->status == 'active' ) {
									update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
										'status'     => _INVOICE_SUBSCRIPTION_ACTIVE,
										'date_start' => date( 'Y-m-d' ),
									) );
								}
							}
							if ( $event_json->recurring_payment->status == 'paused' || $event_json->recurring_payment->status == 'canceled' ) {
								update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
									'status' => _INVOICE_SUBSCRIPTION_FAILED,
								) );
							}


						}
						if ( $event_json && isset( $event_json->order->status ) && $event_json->order->status == 'completed' && isset( $event_json->order->total_native ) && isset( $event_json->order->custom ) ) {

							// crab out the custom bits so we know what to deal with.


							$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
							$currency             = module_config::get_currency( $invoice_payment_data['currency_id'] );

							if ( $invoice_payment_subscription_id ) {
								// this API result is for a subscription payment.
								$invoice_payment_subscription = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $invoice_payment_subscription_id );
								if ( $invoice_payment_subscription && $invoice_payment_subscription['invoice_payment_subscription_id'] == $invoice_payment_subscription_id && $currency['code'] == $event_json->order->total_native->currency_iso ) {

									if ( ! $invoice_payment_subscription['date_start'] || $invoice_payment_subscription['date_start'] == '0000-00-00' ) {
										// no start date yet, set the start date now (this should really happen in the above callback, but coinbase isn't working right now)
										update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
											'status'     => _INVOICE_SUBSCRIPTION_ACTIVE,
											'date_start' => date( 'Y-m-d' ),
										) );
									}
									// we have a subscription payment. woo!
									// this gets a bit tricky, we have to work out if the invoice has been generated for this subscription yet.
									// if this invoice hasn't been generated yet then we have to generate it.
									// pass this back to the invoice class so we can reuse this feature in the future.
									$data = module_invoice::create_new_invoice_for_subscription_payment( $invoice_id, $invoice_payment_id, $invoice_payment_subscription_id );
									if ( $data && $data['invoice_id'] && $data['invoice_payment_id'] ) {

										$next_time = time();
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['days'] ) . ' days', $next_time );
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['months'] ) . ' months', $next_time );
										$next_time = strtotime( '+' . abs( (int) $invoice_payment_subscription['years'] ) . ' years', $next_time );
										update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
											'date_last_pay' => date( 'Y-m-d' ),
											'date_next'     => date( 'Y-m-d', $next_time ),
										) );
										update_insert( "invoice_payment_id", $data['invoice_payment_id'], "invoice_payment", array(
											'date_paid'                       => date( 'Y-m-d' ),
											'amount'                          => $event_json->order->total_native->cents / 100,
											'method'                          => self::get_payment_method_name() . ' (Subscription)',
											'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
										) );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Invoice Payment Subscription Received!" );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "API IP is " . $_SERVER['REMOTE_ADDR'] );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Received BTC: " . $event_json->order->total_btc->cents / 10000000 );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Received " . $event_json->order->total_native->currency_iso . ': ' . $event_json->order->total_native->cents / 100 );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Destination Address: " . $event_json->order->receive_address );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Currency code matches, marking invoice as paid." );
										self::add_payment_data( $data['invoice_payment_id'], 'log', "Raw Event Data: \n" . json_encode( $event_json ) );

										module_invoice::save_invoice( $data['invoice_id'], array() );

										echo "Successful Subscription Payment!";

									} else {
										send_error( "Coinbase Subscription Error (failed to generate new invoice!) " . var_export( $data, true ) );
									}

								} else {
									send_error( 'Currency code missmatch on coinbase subscription payment' );
								}


							} else {
								// this is a normal once off payment.

								self::add_payment_data( $invoice_payment_id, 'log', "API IP is " . $_SERVER['REMOTE_ADDR'] );
								self::add_payment_data( $invoice_payment_id, 'log', "Received BTC: " . $event_json->order->total_btc->cents / 10000000 );
								self::add_payment_data( $invoice_payment_id, 'log', "Received " . $event_json->order->total_native->currency_iso . ': ' . $event_json->order->total_native->cents / 100 );
								self::add_payment_data( $invoice_payment_id, 'log', "Destination Address: " . $event_json->order->receive_address );

								if ( $currency['code'] == $event_json->order->total_native->currency_iso ) {
									self::add_payment_data( $invoice_payment_id, 'log', "Currency code matches, marking invoice as paid." );
									update_insert( "invoice_payment_id", $invoice_payment_id, "invoice_payment", array(
										'date_paid' => date( 'Y-m-d' ),
										'amount'    => $event_json->order->total_native->cents / 100,
									) );

									module_invoice::save_invoice( $invoice_id, array() );
									echo "Successful Payment!";
									$success = true;
								} else {
									self::add_payment_data( $invoice_payment_id, 'log', "Currency code missmatch, please check settings!" );
								}
								self::add_payment_data( $invoice_payment_id, 'log', "Raw Event Data: \n" . json_encode( $event_json ) );
							}


						}
					}
				}

				$debug = ob_get_clean();
				if ( module_config::c( 'coinbase_payment_debug', 0 ) ) {
					send_error( "Coinbase Debug: $debug" );
				}
				exit;

				break;
			case 'pay_subscription':
				$invoice_id                      = isset( $_REQUEST['invoice_id'] ) ? $_REQUEST['invoice_id'] : false;
				$invoice_payment_id              = isset( $_REQUEST['invoice_payment_id'] ) ? $_REQUEST['invoice_payment_id'] : false;
				$invoice_payment_subscription_id = isset( $_REQUEST['invoice_payment_subscription_id'] ) ? $_REQUEST['invoice_payment_subscription_id'] : false;
				$coinbase_plan_id                = isset( $_REQUEST['coinbase_plan_id'] ) ? $_REQUEST['coinbase_plan_id'] : false;
				$user_id                         = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : false;
				if ( $invoice_id && $invoice_payment_id && $coinbase_plan_id && $invoice_payment_subscription_id && $user_id && isset( $_POST['coinbaseToken'] ) ) {

					$user_data = module_user::get_user( $user_id );
					$email     = isset( $_REQUEST['coinbaseEmail'] ) && strlen( $_REQUEST['coinbaseEmail'] ) ? $_REQUEST['coinbaseEmail'] : $user_data['email'];
					if ( ! $email || ! strpos( $email, '@' ) ) {
						die( 'Please ensure your user account has a valid email address before paying with coinbase' );
					}
					$invoice_payment              = get_single( 'invoice_payment', 'invoice_payment_id', $invoice_payment_id );
					$invoice_payment_subscription = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $invoice_payment_subscription_id );
					if ( ! $invoice_payment || ! $invoice_payment_subscription || $invoice_payment['invoice_id'] != $invoice_id || $invoice_payment['invoice_payment_subscription_id'] != $invoice_payment_subscription_id ) {
						die( 'Invalid invoice payment subscription id' );
					}

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					$invoice_data         = module_invoice::get_invoice( $invoice_id );
					if ( $invoice_payment_data && $invoice_data && $invoice_id == $invoice_data['invoice_id'] && $invoice_payment_data['invoice_id'] == $invoice_data['invoice_id'] ) {
						$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
						$currency_code = $currency['code'];
						$description   = isset( $_REQUEST['description'] ) ? $_REQUEST['description'] : 'N/A';

						$template = new module_template();
						ob_start();
						require_once( 'includes/plugin_paymethod_coinbase/coinbase-php/lib/coinbase.php' );

						$coinbase = array(
							"secret_key"      => module_config::c( 'payment_method_coinbase_api_key' ),
							"publishable_key" => module_config::c( 'payment_method_coinbase_secret_key' )
						);

						coinbase::setApiKey( $coinbase['secret_key'] );

						try {
							// todo- search for existing customer based on email address???
							// todo: check if adding new plan to existing customer work??
							$coinbase_customer = coinbase_Customer::create( array(
									"card"     => $_POST['coinbaseToken'],
									"email"    => $email,
									//'plan' => $coinbase_plan_id,
									'metadata' => array(
										'user_id' => $user_id,
									)
								)
							);
							if ( $coinbase_customer && $coinbase_customer->id ) { //} && $coinbase_customer->subscriptions){

								$coinbase_subscription = $coinbase_customer->subscriptions->create( array(
									'plan' => $coinbase_plan_id
								) );

								if ( $coinbase_subscription && $coinbase_subscription->id ) {
									update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
										'status'                => _INVOICE_SUBSCRIPTION_ACTIVE,
										'date_start'            => date( 'Y-m-d' ),
										// we also have to store the coinbase details here so we can easily search for them later on.
										'coinbase_customer'     => $coinbase_customer->id,
										'coinbase_subscription' => $coinbase_subscription->id,
									) );

									module_paymethod_coinbase::add_payment_data( $invoice_payment_id, 'log', "Started coinbase Subscription: " . var_export( array(
											'customer.id'     => $coinbase_customer->id,
											'plan.id'         => $coinbase_plan_id,
											'subscription.id' => $coinbase_subscription->id,
										), true ) );
									// success!
									// redirect to receipt page.
									redirect_browser( module_invoice::link_public_payment_complete( $invoice_id ) );
								} else {
									echo 'Failed to create subscription with coinbase';
								}

							}

							$error = "Something went wrong during coinbase payment. Please confirm invoice payment went through: " . htmlspecialchars( $description );
							send_error( $error );
							echo $error;

						} catch ( coinbase_CardError $e ) {
							// The card has been declined
							$body  = $e->getJsonBody();
							$err   = $body['error'];
							$error = "Sorry: Payment failed. <br><br>\n\n" . htmlspecialchars( $description ) . ". <br><br>\n\n";
							$error .= $err['message'];
							echo $error;
							$error .= "\n\n\n" . var_export( $err, true );
							send_error( $error );
						} catch ( Exception $e ) {
							$body  = $e->getJsonBody();
							$err   = $body['error'];
							$error = "Sorry: Payment failed. <br><br>\n\n" . htmlspecialchars( $description ) . ". <br><br>\n\n";
							$error .= $err['message'];
							echo $error;
							$error .= "\n\n\n" . var_export( $err, true );
							send_error( $error );
						}

						$template->content = ob_get_clean();
						echo $template->render( 'pretty_html' );
						exit;
					}
				}
				echo 'Error paying via coinbase';
				exit;

		}
	}

	public static function get_payment_key( $invoice_id, $invoice_payment_id, $invoice_payment_subscription_id = 0, $hash = false ) {
		$hashbit = md5( "Invoice $invoice_id with payment $invoice_payment_id on subscription $invoice_payment_subscription_id " . _UCM_SECRET );
		if ( $hash ) {
			return $hashbit;
		}

		return $invoice_id . ':' . $invoice_payment_id . ':' . $invoice_payment_subscription_id . ':' . $hashbit;
	}

}