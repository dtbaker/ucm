<?php

//306412171 ucm_1306412206_per@gmail.com


class module_paymethod_stripe extends module_base {


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
		$this->version         = 2.245;
		$this->module_name     = "paymethod_stripe";
		$this->module_position = 8882;

		// 2.245 - 2017-02-27 - config fix
		// 2.244 - 2017-01-12 - stripe debugging information
		// 2.243 - 2017-01-03 - stripe debugging information
		// 2.242 - 2015-03-14 - better default payment method options
		// 2.241 - 2015-03-08 - stripe supports daily subscription renewals now
		// 2.24 - 2015-03-05 - undefined variable bug fix
		// 2.239 - 2014-06-27 - more stripe settings
		// 2.238 - 2014-06-04 - advanced setting payment_method_stripe_force_subscription added
		// 2.237 - 2014-03-12 - stripe fee support
		// 2.236 - 2014-03-04 - restrict Stripe to certain currencies
		// 2.235 - 2014-02-26 - restrict Stripe to certain currencies
		// 2.234 - 2014-02-25 - installation database fix
		// 2.233 - 2014-02-22 - payment fix on stripe subscriptions
		// 2.232 - 2014-02-20 - stripe recurring payments missing files fix
		// 2.231 - 2014-02-18 - stripe recurring payments
		// 2.22 - 2013-09-05 - marking invoice as paid fix
		// 2.21 - 2013-04-16 - initial release

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Stripe",
				"p"                   => "stripe_settings",
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
		return module_config::c( 'payment_method_stripe_enabled', 0 );
	}

	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_stripe_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_stripe_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['stripe'] ) ) {
			return $invoice_payment_methods['stripe']['enabled'];
		}

		// check if this invoice is in the allowed currency.
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$cur          = trim( strtolower( module_config::c( 'payment_method_stripe_currency', '' ) ) );
		$dollar_limit = module_config::c( 'payment_method_stripe_limit_type', 'above' );
		$dollar_value = module_config::c( 'payment_method_stripe_limit_value', 0 );

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

		return module_config::c( 'payment_method_stripe_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'stripe', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_stripe_label', 'Stripe' );
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
			// we are starting a payment via stripe!
			// setup a pending payment and redirect to stripe.
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
				die( 'Please ensure your user account has a valid email address before paying with stripe' );
			}
			$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );

			// we add the fee details to the invoice payment record so that the new invoice total can be calculated.
			$fee_percent     = module_config::c( 'payment_method_stripe_charge_percent', 0 );
			$fee_amount      = module_config::c( 'payment_method_stripe_charge_amount', 0 );
			$fee_description = module_config::c( 'payment_method_stripe_charge_description', 'Stripe Fee' );
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
			if ( module_config::c( 'payment_method_stripe_subscriptions', 0 ) ) {
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
						// not really sure what to do here, just redirect to stripe as if the user is doing it for the first time.
						$_REQUEST['payment_subscription'] = true; // hacks!
					}

					if ( isset( $_REQUEST['payment_subscription'] ) || module_config::c( 'payment_method_stripe_force_subscription', 0 ) ) {
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

						$description       = _l( 'Recurring payment for %s every %s', $is_subscription['name'], implode( ', ', $bits ) );
						$subscription_name = $is_subscription['name'];
						unset( $is_subscription['name'] ); // so reset/key cals below rosk.
						$subscription_id = $is_subscription['id'];
						unset( $is_subscription['id'] ); // so reset/key cals below rosk.

						$currency = module_config::get_currency( $invoice_payment_data['currency_id'] );

						// if there are more than 1 recurring amounts then we convert it to weeks, as stripe only supports one time period.
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
							// convert days to weeks
							//$time = 'week';
							$time   = 'day';
							$period = $is_subscription['days'];
							//$period = max(1,floor($is_subscription['days'] / 7));
						} else if ( $time == 'months' ) {
							$time   = 'month';
							$period = $is_subscription['months'];
						} else if ( $time == 'years' ) {
							$time   = 'year';
							$period = $is_subscription['years'];
						} else {
							die( 'Failed to create subscription, invalid settings' );
						}
						$stripe_amount = $payment_amount * 100;

						ini_set( 'display_errors', true );
						ini_set( 'error_reporting', E_ALL );
						// create or retrieve this subscription.
						require_once( 'includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php' );
						$stripe = array(
							"secret_key"      => module_config::c( 'payment_method_stripe_secret_key' ),
							"publishable_key" => module_config::c( 'payment_method_stripe_publishable_key' )
						);
						Stripe::setApiKey( $stripe['secret_key'] );

						$stripe_plan_id = 'sub_' . $subscription_id;
						$stripe_plan    = false;
						if ( $stripe_plan_id ) {
							// get this plan from stripe, and check it's still valid:
							try {
								$stripe_plan = Stripe_Plan::retrieve( $stripe_plan_id );
							} catch ( Exception $e ) {
								//print_r($e);
							}
							if ( $stripe_plan && $stripe_plan->interval == $time && $stripe_plan->interval_count == $period && $stripe_plan->amount == $stripe_amount ) {
								// still have a valid plan! yes!
							} else {
								// plan no longer exists or has changed
								$stripe_plan = false;
							}
						}
						if ( ! $stripe_plan ) {
							try {
								$settings    = array(
									"amount"         => $stripe_amount,
									"interval"       => $time,
									'interval_count' => $period,
									"name"           => $subscription_name,
									"currency"       => $currency['code'],
									"id"             => $stripe_plan_id,
									'metadata'       => array(
										'subscription_id' => $subscription_id,
									)
								);
								$stripe_plan = Stripe_Plan::create( $settings );
							} catch ( Exception $e ) {
								//print_r($e);
							}
							//                            print_r($stripe_plan);
						}
						if ( $stripe_plan ) {
							// right to go!
							// display the stripe payment form (same as stripe_form.php, just we do a subscription rather than once off payment)
							//self::stripe_redirect($description,$payment_amount,$user_id,$invoice_payment_id,$invoice_id,$invoice_payment_data['currency_id']);
							$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
							$currency_code = $currency['code'];
							$template      = new module_template();
							ob_start();
							?>
							<h1><?php echo htmlspecialchars( $description ); ?></h1>
							<form
								action="<?php echo full_link( _EXTERNAL_TUNNEL . '?m=paymethod_stripe&h=pay_subscription&method=stripe' ); ?>"
								method="post">
								<input type="hidden" name="invoice_payment_subscription_id"
								       value="<?php echo $invoice_payment_subscription_id; ?>">
								<input type="hidden" name="invoice_payment_id" value="<?php echo $invoice_payment_id; ?>">
								<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
								<input type="hidden" name="stripe_plan_id" value="<?php echo $stripe_plan_id; ?>">
								<input type="hidden" name="description" value="<?php echo htmlspecialchars( $description ); ?>">
								<input type="hidden" name="user_id" value="<?php echo htmlspecialchars( $user_id ); ?>">
								<script src="https://checkout.stripe.com/v2/checkout.js" class="stripe-button"
								        data-key="<?php echo $stripe['publishable_key']; ?>"
								        data-amount="<?php echo $payment_amount * 100; ?>"
									<?php if ( isset( $user_data['email'] ) && strlen( $user_data['email'] ) ) { ?>
										data-email="<?php echo htmlspecialchars( $user_data['email'] ); ?>"
									<?php } ?>
									      data-currency="<?php echo htmlspecialchars( $currency_code ); ?>"
									      data-label="<?php _e( 'Pay %s by Credit Card', dollar( $payment_amount, true, $invoice_payment_data['currency_id'] ) ); ?>"
									      data-description="<?php echo htmlspecialchars( $description ); ?>"></script>
							</form>

							<p>&nbsp;</p>
							<p>

								<a href="<?php echo module_invoice::link_public( $invoice_id ); ?>"><?php _e( "Cancel" ); ?></a>
							</p>
							<?php
							$template->content = ob_get_clean();
							echo $template->render( 'pretty_html' );
							exit;

						} else {
							die( 'Failed to create stripe plan. Please check settings: ' . var_export( $stripe_plan, true ) );
						}


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
							'payment_method'        => 'paymethod_stripe',
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

			$description = _l( 'Payment for invoice %s', $invoice_data['name'] );
			//self::stripe_redirect($description,$payment_amount,$user_id,$invoice_payment_id,$invoice_id,$invoice_payment_data['currency_id']);
			$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
			$currency_code = $currency['code'];
			$template      = new module_template();
			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_paymethod_stripe/pages/stripe_form.php' ) );
			$template->content = ob_get_clean();
			echo $template->render( 'pretty_html' );
			exit;
		}

		return false;
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'event_ipn':
				require_once( 'includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php' );

				$stripe = array(
					"secret_key"      => module_config::c( 'payment_method_stripe_secret_key' ),
					"publishable_key" => module_config::c( 'payment_method_stripe_publishable_key' )
				);

				Stripe::setApiKey( $stripe['secret_key'] );

				$body       = @file_get_contents( 'php://input' );
				$event_json = json_decode( $body );

				ob_start();
				//                 echo "INPUT: <br>\n";
				//                 print_r($body);
				//                 echo "<br><br>\n";
				echo "UCM STRIPE DEBUG:<br><br>JSON: <br>\n";
				print_r( $event_json );
				echo "<br><br>\n";

				$event_id = $event_json->id;
				try {
					$event = Stripe_Event::retrieve( $event_id );
					// This will send receipts on succesful invoices
					if ( $event->type == 'charge.succeeded' && $event->data->object->invoice ) {
						$paid_amount = $event->data->object->amount / 100;
						// get the invoice.
						$invoice = Stripe_Invoice::retrieve( $event->data->object->invoice );
						echo "INVOICE: <br>\n";
						print_r( $invoice );
						echo "<br><br>\n";
						if ( $invoice && $invoice->subscription && $invoice->paid ) {
							// this payment was for a subscription! which one though?
							$customer = Stripe_Customer::retrieve( $invoice->customer );
							echo "CUSTOMER: <br>\n";
							print_r( $customer );
							echo "<br><br>\n";
							$subscription = $customer->subscriptions->retrieve( $invoice->subscription );

							echo "SUBSCRIPTION: <br>\n";
							print_r( $subscription );
							echo "<br><br>\n";

							// now we have the Customer and Subscription we can look through our invoice_payment_subscription table for those values.
							/*update_insert('invoice_payment_subscription_id',$invoice_payment_subscription_id,'invoice_payment_subscription',array(
															'status' => _INVOICE_SUBSCRIPTION_ACTIVE,
															'date_start' => date('Y-m-d'),
													// we also have to store the stripe details here so we can easily search for them later on.
													'stripe_customer' => $stripe_customer->id,
													'stripe_subscription' => $stripe_subscription->id,
													));*/
							$invoice_payment_subscription = get_single( 'invoice_payment_subscription', array(
								'stripe_customer',
								'stripe_subscription'
							), array( $customer->id, $subscription->id ) );

							if ( $invoice_payment_subscription ) {
								// FIND THE linked invoice_payment for this original invoice payment subscription, this allows us to perform the same creatE_new_invoice as paypal below:
								$invoice_payment_subscription_id = $invoice_payment_subscription['invoice_payment_subscription_id'];
								$invoice_payment                 = get_single( 'invoice_payment', 'invoice_payment_subscription_id', $invoice_payment_subscription_id );
								if ( $invoice_payment ) {
									$payment_id = $invoice_payment['invoice_payment_id'];
									$invoice_id = $invoice_payment['invoice_id'];

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
										update_insert( "invoice_payment_id", $data['invoice_payment_id'], "invoice_payment", array(
											'date_paid'                       => date( 'Y-m-d' ),
											'amount'                          => $paid_amount,
											'method'                          => 'Stripe (Subscription)',
											'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
										) );
										module_paymethod_stripe::add_payment_data( $data['invoice_payment_id'], 'log', "Payment Received via Webhook: " . var_export( array(
												'event.type'                       => $event->type,
												'invoice.id'                       => $invoice->id,
												'subscription.id'                  => $subscription->id,
												'customer.id'                      => $customer->id,
												'$invoice_payment_subscription_id' => $invoice_payment_subscription_id,
												'$invoice_payment_id'              => $payment_id,
											), true ) );

										module_invoice::save_invoice( $data['invoice_id'], array() );

										echo "Successful Subscription Payment For Invoice " . $data['invoice_id'];

									} else {
										send_error( "Stripe Webhook Subscription Error (failed to generate new invoice!) " . var_export( $data, true ) );
									}
								} else {
									echo 'Failed to find matching invoice payment in db';
								}

							} else {
								echo 'Failed to find matching subscription payment in db';
							}
						}
					}
				} catch ( Exception $e ) {
					$body  = $e->getJsonBody();
					$err   = $body['error'];
					$error = "Sorry: Webhook failed. <br><br>\n\n";
					$error .= $err['message'];
					$error .= "\n\n\n" . var_export( $e, true );
					echo $error;
				}

				$debug = ob_get_clean();
				//mail('dtbaker@gmail.com','Stripe Webhook debug',$debug);
				if ( module_config::c( 'stripe_payment_debug', 0 ) ) {
					echo $debug;
				}
				echo "Thanks! (set stripe_payment_debug to 1 in UCM to see more data here)";
				exit;

				break;
			case 'pay_subscription':
				$invoice_id                      = isset( $_REQUEST['invoice_id'] ) ? $_REQUEST['invoice_id'] : false;
				$invoice_payment_id              = isset( $_REQUEST['invoice_payment_id'] ) ? $_REQUEST['invoice_payment_id'] : false;
				$invoice_payment_subscription_id = isset( $_REQUEST['invoice_payment_subscription_id'] ) ? $_REQUEST['invoice_payment_subscription_id'] : false;
				$stripe_plan_id                  = isset( $_REQUEST['stripe_plan_id'] ) ? $_REQUEST['stripe_plan_id'] : false;
				$user_id                         = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : false;
				if ( $invoice_id && $invoice_payment_id && $stripe_plan_id && $invoice_payment_subscription_id && $user_id && isset( $_POST['stripeToken'] ) ) {

					$user_data = module_user::get_user( $user_id );
					$email     = isset( $_REQUEST['stripeEmail'] ) && strlen( $_REQUEST['stripeEmail'] ) ? $_REQUEST['stripeEmail'] : $user_data['email'];
					if ( ! $email || ! strpos( $email, '@' ) ) {
						die( 'Please ensure your user account has a valid email address before paying with stripe' );
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
						require_once( 'includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php' );

						$stripe = array(
							"secret_key"      => module_config::c( 'payment_method_stripe_secret_key' ),
							"publishable_key" => module_config::c( 'payment_method_stripe_publishable_key' )
						);

						Stripe::setApiKey( $stripe['secret_key'] );

						try {
							// todo- search for existing customer based on email address???
							// todo: check if adding new plan to existing customer work??
							$stripe_customer = Stripe_Customer::create( array(
									"card"     => $_POST['stripeToken'],
									"email"    => $email,
									//'plan' => $stripe_plan_id,
									'metadata' => array(
										'user_id' => $user_id,
									)
								)
							);
							if ( $stripe_customer && $stripe_customer->id ) { //} && $stripe_customer->subscriptions){

								$stripe_subscription = $stripe_customer->subscriptions->create( array(
									'plan' => $stripe_plan_id
								) );

								if ( $stripe_subscription && $stripe_subscription->id ) {
									update_insert( 'invoice_payment_subscription_id', $invoice_payment_subscription_id, 'invoice_payment_subscription', array(
										'status'              => _INVOICE_SUBSCRIPTION_ACTIVE,
										'date_start'          => date( 'Y-m-d' ),
										// we also have to store the stripe details here so we can easily search for them later on.
										'stripe_customer'     => $stripe_customer->id,
										'stripe_subscription' => $stripe_subscription->id,
									) );

									module_paymethod_stripe::add_payment_data( $invoice_payment_id, 'log', "Started Stripe Subscription: " . var_export( array(
											'customer.id'     => $stripe_customer->id,
											'plan.id'         => $stripe_plan_id,
											'subscription.id' => $stripe_subscription->id,
										), true ) );
									// success!
									// redirect to receipt page.
									redirect_browser( module_invoice::link_public_payment_complete( $invoice_id ) );
								} else {
									echo 'Failed to create subscription with stripe';
								}

							}

							$error = "Something went wrong during stripe payment. Please confirm invoice payment went through: " . htmlspecialchars( $description );
							send_error( $error );
							echo $error;

						} catch ( Stripe_CardError $e ) {
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
				echo 'Error paying via Stripe';
				exit;
			case 'pay':
				$invoice_id         = isset( $_REQUEST['invoice_id'] ) ? $_REQUEST['invoice_id'] : false;
				$invoice_payment_id = isset( $_REQUEST['invoice_payment_id'] ) ? $_REQUEST['invoice_payment_id'] : false;
				if ( $invoice_id && $invoice_payment_id && isset( $_POST['stripeToken'] ) ) {

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					$invoice_data         = module_invoice::get_invoice( $invoice_id );
					if ( $invoice_payment_data && $invoice_data && $invoice_id == $invoice_data['invoice_id'] && $invoice_payment_data['invoice_id'] == $invoice_data['invoice_id'] ) {
						$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
						$currency_code = $currency['code'];
						$description   = _l( 'Payment for invoice %s', $invoice_data['name'] );

						$template = new module_template();
						ob_start();
						include( module_theme::include_ucm( 'includes/plugin_paymethod_stripe/pages/stripe_form.php' ) );
						$template->content = ob_get_clean();
						echo $template->render( 'pretty_html' );
						exit;
					}
				}
				echo 'Error paying via Stripe';
				exit;
		}
	}

	public function is_installed() {
		return true;
	}

	public function get_install_sql() {
		return $this->get_upgrade_sql();
	}

	public function get_upgrade_sql() {
		$sql = '';
		if ( self::db_table_exists( 'invoice_payment_subscription' ) ) {
			$fields = get_fields( 'invoice_payment_subscription' );
			if ( ! isset( $fields['stripe_customer'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_payment_subscription` ADD  `stripe_customer` varchar(200) NOT NULL DEFAULT \'\';';
			}
			if ( ! isset( $fields['stripe_subscription'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_payment_subscription` ADD  `stripe_subscription` varchar(200) NOT NULL DEFAULT \'\';';
			}
		}

		return $sql;
	}

}