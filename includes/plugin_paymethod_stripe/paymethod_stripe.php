<?php

//306412171 ucm_1306412206_per@gmail.com

require_once( dirname( __FILE__ ) . '/subscriptions.php' );
require_once( dirname( __FILE__ ) . '/cleanup.php' );

class module_paymethod_stripe extends module_base
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
		$this->module_name     = 'paymethod_stripe';
		$this->module_position = 400;
		$this->version         = 2.248;
	}

	/**
	 * No bundled fallback, deliberately: the Stripe SDK comes from Composer (vendor/).
	 *
	 * This plugin used to carry stripe-php 1.11.0 from 2014. It predates namespaces and speaks
	 * the Charges API, which Stripe no longer accepts from new integrations, so it could never
	 * stand in for the modern SDK. It and the one-off Charges form that used it are gone.
	 *
	 * @return array
	 */
	public function third_party_libraries() {
		return array(
			'stripe' => array(
				'name'       => 'Stripe PHP',
				'package'    => 'stripe/stripe-php',
				'constraint' => '^22.0',
				'class'      => 'Stripe\\StripeClient',
				'bundled'    => '',
				'feature'    => 'Stripe Checkout payments',
			),
		);
	}

	/**
	 * Runs when this plugin's version is applied, on in-app updates and zip uploads alike.
	 * Updating never deletes files, so take out the old bundled stripe-php 1.11.0 here.
	 */
	function install_upgrade() {
		ucm_stripe_cleanup::remove_bundled_stripe_php();
		ucm_stripe_cleanup::remove_old_charges_form();

		return parent::install_upgrade();
	}

	function init() {
		// 2.248 - 2026-11-02 - the dead bundled stripe-php 1.11.0 and the old Charges form are removed (the folder is deleted on upgrade); stored values are read without instantiating objects
		// 2.247 - 2026-10-17 - Stripe PHP library 22, on Stripe API version 2026-09-30.endive
		// 2.246 - 2026-10-17 - 4.0.0 relaunch: Stripe Checkout Sessions for payments and subscriptions, clearer key setup
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

		return module_config::c( 'payment_method_stripe_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'stripe', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	/**
	 * The stable identifier for this payment method.
	 *
	 * Not the label. get_payment_method_name() returns admin-editable free text, and
	 * invoice_payment.method stores a copy of whatever it said at the time - which is why
	 * renaming "Stripe" to "Credit Card" used to break the resume-a-payment lookup, and
	 * why Pay.nl's reconciliation cron stopped for good if anyone touched its label. This
	 * never changes, and it is what invoice_payment.method_key records.
	 *
	 * @return string
	 */
	public static function get_payment_method_key() {
		return 'stripe';
	}

	/**
	 * @return bool
	 */
	public function is_deprecated() {
		return false;
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_stripe_label', 'Stripe' );
	}

	public function get_invoice_payment_description( $invoice_id, $method = '' ) {

	}

	public static function add_payment_data( $invoice_payment_id, $key, $val ) {
		$payment      = module_invoice::get_invoice_payment( $invoice_payment_id );
		$payment_data = @unserialize( $payment['data'], array( 'allowed_classes' => false ) );
		if ( ! is_array( $payment_data ) ) {
			$payment_data = array();
		}
		if ( ! isset( $payment_data[ $key ] ) ) {
			$payment_data[ $key ] = array();
		}
		$payment_data[ $key ][] = $val;
		update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array( 'data' => serialize( $payment_data ) ) );
	}

	/**
	 * Is the modern Stripe SDK available to us?
	 *
	 * @return bool
	 */
	public static function have_sdk() {
		return class_exists( 'ucm_libraries', false ) && ucm_libraries::have( 'stripe' );
	}

	/**
	 * Can this install take payments through Checkout Sessions? Yes when the SDK is there.
	 *
	 * @return bool
	 */
	public static function use_checkout_sessions() {
		return self::have_sdk();
	}

	/**
	 * @return string
	 */
	public static function secret_key() {
		return trim( (string) module_config::c( 'payment_method_stripe_secret_key', '' ) );
	}

	/**
	 * Stripe keys carry their own mode, so there is no separate sandbox setting to get
	 * out of step with the keys.
	 *
	 * @return bool
	 */
	public static function is_sandbox() {
		return strpos( self::secret_key(), 'sk_live' ) !== 0;
	}

	/**
	 * @return \Stripe\StripeClient
	 */
	public static function stripe_client() {
		return new \Stripe\StripeClient( self::secret_key() );
	}

	/**
	 * The URL to paste into Stripe as the webhook endpoint.
	 *
	 * Shown on the settings page, because it differs per install depending on how
	 * _EXTERNAL_TUNNEL is configured and nobody can be expected to guess it.
	 *
	 * @return string
	 */
	public static function webhook_url() {
		return full_link( _EXTERNAL_TUNNEL . '?m=paymethod_stripe&h=event_ipn' );
	}

	/**
	 * Where Stripe sends the payer back to once Checkout is done.
	 *
	 * @param int  $invoice_payment_id
	 * @param bool $h return the hash rather than the link
	 *
	 * @return string
	 */
	public static function link_checkout_complete( $invoice_payment_id, $h = false ) {
		if ( $h ) {
			return md5( 'stripe checkout complete ' . _UCM_SECRET . ' ' . (int) $invoice_payment_id );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=paymethod_stripe&h=complete&i=' . (int) $invoice_payment_id . '&hash=' . self::link_checkout_complete( $invoice_payment_id, true ) );
	}

	/**
	 * Hand the payer to a Stripe-hosted Checkout page.
	 *
	 * This replaces a form on the merchant's own domain that POSTed a card token to the
	 * Charges API. Stripe does not accept that from a new integration at all any more,
	 * and where it still works it has no way to answer an SCA challenge, so EEA and UK
	 * cards decline with authentication_required and the payer has no retry path.
	 *
	 * Never returns on the success path.
	 *
	 * @param int    $invoice_id
	 * @param float  $payment_amount
	 * @param int    $invoice_payment_id
	 * @param array  $invoice_payment_data
	 * @param string $description
	 *
	 * @return bool
	 */
	public static function start_checkout_session( $invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data, $description ) {
		if ( self::secret_key() === '' ) {
			echo htmlspecialchars( _l( 'This site is not finished setting up card payments. Please contact us to arrange payment another way.' ) );
			exit;
		}

		$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
		$currency_code = isset( $currency['code'] ) ? $currency['code'] : 'USD';

		try {
			$session = self::stripe_client()->checkout->sessions->create( array(
				'mode'                => 'payment',
				// Echoed back on both the return and the webhook, and checked against
				// the row before anything is recorded.
				'client_reference_id' => (string) (int) $invoice_payment_id,
				'line_items'          => array(
					array(
						'quantity'   => 1,
						'price_data' => array(
							'currency'     => strtolower( $currency_code ),
							'unit_amount'  => ucm_payment_verification::to_minor_units( $payment_amount, $currency_code ),
							'product_data' => array(
								'name' => $description,
							),
						),
					),
				),
				'metadata'            => array(
					'invoice_id'         => (string) (int) $invoice_id,
					'invoice_payment_id' => (string) (int) $invoice_payment_id,
				),
				'success_url'         => self::link_checkout_complete( $invoice_payment_id ) . '&session_id={CHECKOUT_SESSION_ID}',
				'cancel_url'          => module_invoice::link_public( $invoice_id ),
			) );
		} catch ( Exception $e ) {
			module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Stripe refused to open a Checkout Session: ' . $e->getMessage() );
			send_error( 'Stripe Checkout Session could not be created for invoice payment ' . (int) $invoice_payment_id . ': ' . $e->getMessage() );
			echo htmlspecialchars( _l( 'Sorry, we could not start this payment. Please try again, or contact us.' ) );
			exit;
		}

		module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Stripe Checkout Session ' . $session->id . ' opened for ' . number_format( (float) $payment_amount, 2 ) . ' ' . $currency_code . '.' );
		redirect_browser( $session->url );

		return true;
	}

	/**
	 * Read a Checkout Session back from Stripe and record the payment it represents.
	 *
	 * The retrieve is authenticated with the secret key, so whatever comes back is
	 * Stripe's own account of what happened rather than anything the caller supplied.
	 * That is what makes this safe to call from both the payer's return and the webhook,
	 * and verify_and_record() makes it safe to call twice.
	 *
	 * @param string $session_id
	 * @param int    $expect_invoice_payment_id 0 to accept whatever the session says
	 *
	 * @return array the verification result
	 */
	public static function record_checkout_session( $session_id, $expect_invoice_payment_id = 0 ) {
		$session_id = trim( (string) $session_id );
		if ( ! preg_match( '#^cs_[A-Za-z0-9_]+$#', $session_id ) ) {
			return array( 'ok' => false, 'code' => 'bad_session_id', 'message' => 'Not a Stripe Checkout Session id.', 'amount' => 0, 'flags' => array() );
		}

		try {
			$session = self::stripe_client()->checkout->sessions->retrieve( $session_id, array( 'expand' => array( 'payment_intent' ) ) );
		} catch ( Exception $e ) {
			return array( 'ok' => false, 'code' => 'retrieve_failed', 'message' => 'Stripe would not return this session: ' . $e->getMessage(), 'amount' => 0, 'flags' => array() );
		}

		$invoice_payment_id = (int) $session->client_reference_id;
		if ( $expect_invoice_payment_id && $invoice_payment_id !== (int) $expect_invoice_payment_id ) {
			return array( 'ok' => false, 'code' => 'wrong_payment', 'message' => 'This Checkout Session belongs to a different payment.', 'amount' => 0, 'flags' => array() );
		}

		$mode = isset( $session->mode ) ? (string) $session->mode : 'payment';

		// A subscription session has no PaymentIntent of its own - the money moves on the
		// Stripe invoice it raised - so that invoice's id is what de-duplication keys on,
		// and it is the same id the renewal webhook will later guard against. A one-off
		// session keys on its PaymentIntent, which is the same on the return and on every
		// webhook delivery. A session with neither falls back to its own id rather than
		// arriving without a reference at all.
		$transaction_id = '';
		if ( $mode === 'subscription' ) {
			$transaction_id = ucm_stripe_subscriptions::reference_id( isset( $session->invoice ) ? $session->invoice : null );
		}
		if ( $transaction_id === '' ) {
			$payment_intent = isset( $session->payment_intent ) ? $session->payment_intent : null;
			$transaction_id = is_object( $payment_intent ) ? (string) $payment_intent->id : (string) $payment_intent;
		}
		if ( $transaction_id === '' ) {
			$transaction_id = (string) $session->id;
		}

		if ( $mode === 'subscription' ) {
			// Do this before recording the payment, and whatever the payment turns out
			// to be worth. The Stripe subscription exists either way, and an install that
			// does not store its id has no way of ever matching a renewal to it.
			self::activate_subscription( $invoice_payment_id, $session );
		}

		$payload = array(
			'amount'         => ucm_payment_verification::from_minor_units( $session->amount_total, $session->currency ),
			'currency'       => (string) $session->currency,
			'transaction_id' => $transaction_id,
			'paid'           => ( $session->payment_status === 'paid' ),
			'test'           => ! $session->livemode,
		);

		return module_invoice::verify_and_record( $invoice_payment_id, $payload, array(
			'method_key' => self::get_payment_method_key(),
			'invoice_id' => isset( $session->metadata['invoice_id'] ) ? (int) $session->metadata['invoice_id'] : 0,
			'sandbox'    => self::is_sandbox(),
		) );
	}

	/**
	 * Can this install offer a Stripe subscription at all?
	 *
	 * The subscription flow is Checkout, the same as a one-off payment, so it needs the
	 * same two things: the modern SDK, and an admin who has not deliberately gone back to
	 * the old form. On the old form there is nothing to fall back to - the 2014 library
	 * that used to drive recurring payments is gone - so the payer is offered a one-off
	 * payment and UCM's own scheduled job bills the next period.
	 *
	 * @return bool
	 */
	public static function subscriptions_available() {
		return module_config::c( 'payment_method_stripe_subscriptions', 0 ) && self::use_checkout_sessions();
	}

	/**
	 * The UCM subscription an invoice was generated from, if any.
	 *
	 * @param int $invoice_id
	 *
	 * @return array|false id, name, days, months, years and the Stripe period mapping
	 */
	public static function invoice_subscription( $invoice_id ) {
		if ( ! class_exists( 'module_subscription', false ) ) {
			return false;
		}
		$subscription_history = get_single( 'subscription_history', 'invoice_id', $invoice_id );
		if ( ! $subscription_history || empty( $subscription_history['subscription_id'] ) ) {
			return false;
		}
		$subscription       = module_subscription::get_subscription( $subscription_history['subscription_id'] );
		$subscription_owner = module_subscription::get_subscription_owner( $subscription_history['subscription_owner_id'] );
		if ( ! $subscription || ! $subscription_owner || empty( $subscription_owner['owner_table'] ) || empty( $subscription_owner['owner_id'] ) ) {
			return false;
		}
		// The owner still has to hold this subscription - an invoice can outlive the
		// attachment it was generated from.
		$members_subscriptions = module_subscription::get_subscriptions_by( $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
		if ( ! isset( $members_subscriptions[ $subscription_history['subscription_id'] ] ) ) {
			return false;
		}

		return array(
			'id'       => (int) $subscription_history['subscription_id'],
			'name'     => isset( $subscription['name'] ) ? $subscription['name'] : '',
			'days'     => (int) $subscription['days'],
			'months'   => (int) $subscription['months'],
			'years'    => (int) $subscription['years'],
			'settings' => isset( $subscription['settings'] ) ? $subscription['settings'] : array(),
			'period'   => ucm_stripe_subscriptions::period( $subscription['days'], $subscription['months'], $subscription['years'] ),
		);
	}

	/**
	 * @param array $subscription as returned by invoice_subscription()
	 *
	 * @return array
	 */
	private static function period_in_words( $subscription ) {
		$bits = array();
		if ( $subscription['days'] > 0 ) {
			$bits[] = _l( '%s days', $subscription['days'] );
		}
		if ( $subscription['months'] > 0 ) {
			$bits[] = _l( '%s months', $subscription['months'] );
		}
		if ( $subscription['years'] > 0 ) {
			$bits[] = _l( '%s years', $subscription['years'] );
		}

		return $bits;
	}

	/**
	 * Build the Checkout Session request for a subscription.
	 *
	 * Kept apart from the call that sends it so the request itself can be checked without
	 * a Stripe key. Everything Stripe would reject is decided here - the mode, the inline
	 * recurring price, the amount in minor units, and the metadata that ties a renewal
	 * back to the row it belongs to - and none of it is otherwise provable until somebody
	 * has a real key in their hand.
	 *
	 * Verified against Stripe's documented Checkout Session schema on 2026-10-13 by calling
	 * this with a monthly $49.95 subscription and reading the result: mode subscription,
	 * currency lowercased, unit_amount 4995 in minor units, the recurring interval inline on
	 * price_data, {CHECKOUT_SESSION_ID} present in success_url, and the ucm ids on both the
	 * session and the subscription metadata. What that does not prove is that Stripe accepts
	 * it - the request is rejected at authentication before the body is looked at, so the
	 * last word needs a test key.
	 *
	 * @return array the arguments for checkout->sessions->create()
	 */
	public static function subscription_session_args( $invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data, $description, $subscription, $invoice_payment_subscription_id, $user_data = array() ) {
		$currency      = module_config::get_currency( $invoice_payment_data['currency_id'] );
		$currency_code = isset( $currency['code'] ) ? $currency['code'] : 'USD';
		$product_name  = trim( (string) $subscription['name'] );
		if ( $product_name === '' ) {
			$product_name = $description;
		}

		$args = array(
			'mode'                => 'subscription',
			'client_reference_id' => (string) (int) $invoice_payment_id,
			'line_items'          => array(
				array(
					'quantity'   => 1,
					'price_data' => array(
						'currency'     => strtolower( $currency_code ),
						'unit_amount'  => ucm_payment_verification::to_minor_units( $payment_amount, $currency_code ),
						'recurring'    => array(
							'interval'       => $subscription['period']['interval'],
							'interval_count' => $subscription['period']['interval_count'],
						),
						'product_data' => array(
							'name' => $product_name,
						),
					),
				),
			),
			// Stripe copies this onto the subscription, so every renewal invoice arrives
			// carrying the row it belongs to even if the match by subscription id ever
			// fails.
			'subscription_data'   => array(
				'metadata' => array(
					'ucm_subscription_id'                 => (string) (int) $subscription['id'],
					'ucm_invoice_payment_subscription_id' => (string) (int) $invoice_payment_subscription_id,
					'ucm_invoice_payment_id'              => (string) (int) $invoice_payment_id,
				),
			),
			'metadata'            => array(
				'invoice_id'                      => (string) (int) $invoice_id,
				'invoice_payment_id'              => (string) (int) $invoice_payment_id,
				'invoice_payment_subscription_id' => (string) (int) $invoice_payment_subscription_id,
			),
			'success_url'         => self::link_checkout_complete( $invoice_payment_id ) . '&session_id={CHECKOUT_SESSION_ID}',
			'cancel_url'          => module_invoice::link_public( $invoice_id ),
		);

		if ( isset( $user_data['email'] ) && strpos( (string) $user_data['email'], '@' ) ) {
			$args['customer_email'] = $user_data['email'];
		}

		return $args;
	}

	/**
	 * Hand the payer to a Stripe-hosted Checkout page that sets up a subscription.
	 *
	 * This replaces a Plan created through the 2014 bundled library and a checkout.js v2
	 * modal. The Plan call passed a top-level `name`, which Stripe stopped accepting when
	 * it introduced Products, and the modal cannot answer a 3-D Secure challenge. The
	 * recurring price is built inline here, so there is no Plan or Product to create,
	 * retrieve, keep in step with an edited subscription, or leave behind in the account.
	 *
	 * Never returns on the success path.
	 *
	 * @param int    $invoice_id
	 * @param float  $payment_amount
	 * @param int    $invoice_payment_id
	 * @param array  $invoice_payment_data
	 * @param string $description
	 * @param array  $subscription as returned by invoice_subscription()
	 * @param int    $invoice_payment_subscription_id
	 * @param array  $user_data
	 *
	 * @return bool
	 */
	public static function start_subscription_checkout( $invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data, $description, $subscription, $invoice_payment_subscription_id, $user_data = array() ) {
		if ( self::secret_key() === '' ) {
			echo htmlspecialchars( _l( 'This site is not finished setting up card payments. Please contact us to arrange payment another way.' ) );
			exit;
		}

		$args = self::subscription_session_args(
			$invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data,
			$description, $subscription, $invoice_payment_subscription_id, $user_data
		);
		$currency_code = strtoupper( (string) $args['line_items'][0]['price_data']['currency'] );

		try {
			$session = self::stripe_client()->checkout->sessions->create( $args );
		} catch ( Exception $e ) {
			module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Stripe refused to open a subscription Checkout Session: ' . $e->getMessage() );
			send_error( 'Stripe subscription Checkout Session could not be created for invoice payment ' . (int) $invoice_payment_id . ': ' . $e->getMessage() );
			echo htmlspecialchars( _l( 'Sorry, we could not set up automatic payments. Please try again, or contact us.' ) );
			exit;
		}

		module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Stripe subscription Checkout Session ' . $session->id . ' opened for ' . number_format( (float) $payment_amount, 2 ) . ' ' . $currency_code . ' every ' . $subscription['period']['interval_count'] . ' ' . $subscription['period']['interval'] . '.' );
		redirect_browser( $session->url );

		return true;
	}

	/**
	 * Write the Stripe subscription a completed Checkout Session created onto the
	 * invoice_payment_subscription row it belongs to.
	 *
	 * Idempotent: the payer's return and the webhook both reach it, and the second one
	 * finds the ids already stored and does nothing.
	 *
	 * @param int    $invoice_payment_id
	 * @param object $session
	 *
	 * @return void
	 */
	private static function activate_subscription( $invoice_payment_id, $session ) {
		$stripe_subscription = ucm_stripe_subscriptions::session_subscription_id( $session );
		if ( $stripe_subscription === '' ) {
			return;
		}

		$payment = get_single( 'invoice_payment', 'invoice_payment_id', (int) $invoice_payment_id );
		if ( ! $payment || empty( $payment['invoice_payment_subscription_id'] ) ) {
			return;
		}
		$row = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $payment['invoice_payment_subscription_id'] );
		if ( ! $row ) {
			return;
		}
		if ( isset( $row['stripe_subscription'] ) && $row['stripe_subscription'] === $stripe_subscription ) {
			return;
		}

		update_insert( 'invoice_payment_subscription_id', $row['invoice_payment_subscription_id'], 'invoice_payment_subscription', array(
			'status'              => _INVOICE_SUBSCRIPTION_ACTIVE,
			'date_start'          => date( 'Y-m-d' ),
			'date_last_pay'       => date( 'Y-m-d' ),
			'date_next'           => date( 'Y-m-d', self::next_payment_time( $row ) ),
			'stripe_customer'     => ucm_stripe_subscriptions::session_customer_id( $session ),
			'stripe_subscription' => $stripe_subscription,
		) );

		module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Stripe subscription ' . $stripe_subscription . ' started.' );
	}

	/**
	 * When the gateway is next expected to bill, by UCM's own reckoning.
	 *
	 * Stripe keeps the authoritative schedule; this is only so the admin screens have
	 * something to show between renewals.
	 *
	 * @param array $row an invoice_payment_subscription row
	 *
	 * @return int
	 */
	private static function next_payment_time( $row ) {
		$next = time();
		$next = strtotime( '+' . abs( (int) $row['days'] ) . ' days', $next );
		$next = strtotime( '+' . abs( (int) $row['months'] ) . ' months', $next );
		$next = strtotime( '+' . abs( (int) $row['years'] ) . ' years', $next );

		return $next;
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
			if ( self::subscriptions_available() ) {
				$subscription = self::invoice_subscription( $invoice_id );

				if ( $subscription && $subscription['period']['ok'] ) {

					$bits = self::period_in_words( $subscription );

					$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
					if ( isset( $invoice_payment_data['invoice_payment_subscription_id'] ) && (int) $invoice_payment_data['invoice_payment_subscription_id'] > 0 ) {
						// This payment already has a subscription row against it, so the
						// payer has been here before and is coming back to finish. Send
						// them to Stripe rather than asking the question again.
						$_REQUEST['payment_subscription'] = true;
					}

					if ( isset( $_REQUEST['payment_subscription'] ) || module_config::c( 'payment_method_stripe_force_subscription', 0 ) ) {
						// user is setting up a subscription! yes!!

						// we create an entry in our database for this particular subscription
						// or if one exists for this payment already then we just continue with that (ie: the user is going in again to redo it)
						if ( isset( $invoice_payment_data['invoice_payment_subscription_id'] ) && (int) $invoice_payment_data['invoice_payment_subscription_id'] > 0 ) {
							$invoice_payment_subscription_id = (int) $invoice_payment_data['invoice_payment_subscription_id'];
						} else {
							$invoice_payment_subscription_id = update_insert( 'invoice_payment_subscription_id', false, 'invoice_payment_subscription', array(
								'status'        => _INVOICE_SUBSCRIPTION_PENDING,
								'days'          => $subscription['days'],
								'months'        => $subscription['months'],
								'years'         => $subscription['years'],
								'date_start'    => '0000-00-00',
								'date_last_pay' => '0000-00-00',
								'date_next'     => '0000-00-00',
							) );
							update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', array(
								'invoice_payment_subscription_id' => $invoice_payment_subscription_id
							) );
						}

						$description = _l( 'Recurring payment for %s every %s', $subscription['name'], implode( ', ', $bits ) );

						return self::start_subscription_checkout( $invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data, $description, $subscription, $invoice_payment_subscription_id, $user_data );

					} elseif ( isset( $_REQUEST['payment_single'] ) ) {
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

				} elseif ( $subscription ) {
					// The subscription has a period Stripe cannot repeat exactly. Rather
					// than approximate it - which is what the old code did, and which
					// drifts apart from UCM's own schedule a little more every cycle -
					// automatic payments are simply not offered. The payer pays this
					// invoice once and UCM's scheduled job generates the next one, which
					// is how Square has always worked.
					module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Automatic payments not offered: ' . $subscription['period']['message'] );
				}
			}


			$description = _l( 'Payment for invoice %s', $invoice_data['name'] );

			if ( self::use_checkout_sessions() ) {
				return self::start_checkout_session( $invoice_id, $payment_amount, $invoice_payment_id, $invoice_payment_data, $description );
			}

			// Without the Stripe library there is nothing to pay with: the old Charges form is
			// gone, and Stripe no longer accepts it from newer accounts anyway.
			$template          = new module_template();
			$template->content = '<h1>' . htmlspecialchars( $description ) . '</h1><p>' . htmlspecialchars( _l( 'Stripe payments are not available on this site right now. Please choose another payment method, or contact us.' ) ) . '</p>';
			send_error( 'Stripe payment attempted for invoice payment ' . (int) $invoice_payment_id . ' but the Stripe library (vendor/stripe/stripe-php) is not installed.' );
			echo $template->render( 'pretty_html' );
			exit;
		}

		return false;
	}

	/**
	 * Handle a Stripe webhook.
	 *
	 * Two events matter. `checkout.session.completed` covers a one-off payment and the
	 * first payment of a subscription; `invoice.payment_succeeded` covers every renewal
	 * after that. Anything else is answered 2xx and ignored, so Stripe stops retrying it.
	 *
	 * Returns true when it dealt with the delivery and has already answered Stripe.
	 *
	 * @return bool
	 */
	private static function handle_checkout_webhook() {
		$body      = (string) @file_get_contents( 'php://input' );
		$secret    = trim( (string) module_config::c( 'payment_method_stripe_webhook_secret', '' ) );
		$signature = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? (string) $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
		$event     = null;

		if ( $secret !== '' ) {
			try {
				$event = \Stripe\Webhook::constructEvent( $body, $signature, $secret );
			} catch ( Exception $e ) {
				// Wrong signature, no signature, or outside the timestamp tolerance.
				// Stripe retries a non-2xx, which is the correct outcome if this was a
				// real delivery and something is misconfigured.
				header( 'HTTP/1.1 400 Bad Request' );
				echo 'Signature verification failed.';

				return true;
			}
		} else {
			// No signing secret configured yet. Rather than fail closed - which would
			// silently drop real payments on every install that upgrades - fall back to
			// what this endpoint has always done: take the event id from the body and
			// re-fetch the event with the secret key. A forged body cannot invent an
			// event that way, so it is sound; it just has no replay protection, which is
			// why the settings page asks for the secret.
			$posted   = json_decode( $body, true );
			$event_id = is_array( $posted ) && isset( $posted['id'] ) ? (string) $posted['id'] : '';
			if ( ! preg_match( '#^evt_[A-Za-z0-9_]+$#', $event_id ) ) {
				return false;
			}
			try {
				$event = self::stripe_client()->events->retrieve( $event_id );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! $event ) {
			return false;
		}

		if ( in_array( $event->type, array( 'invoice.payment_succeeded', 'invoice.paid' ), true ) ) {
			echo self::record_subscription_renewal( $event->data->object );

			return true;
		}

		if ( $event->type !== 'checkout.session.completed' ) {
			// Nothing else here does anything with a delivery, and answering 2xx stops
			// Stripe retrying one we are never going to act on.
			echo 'Nothing to do.';

			return true;
		}

		$session = $event->data->object;
		$result  = self::record_checkout_session( isset( $session->id ) ? $session->id : '' );

		if ( $secret === '' ) {
			$invoice_payment_id = isset( $session->client_reference_id ) ? (int) $session->client_reference_id : 0;
			if ( $invoice_payment_id ) {
				module_invoice::add_payment_data( $invoice_payment_id, 'log', 'Webhook accepted without a signing secret (compatibility mode). Set the Stripe webhook signing secret in Settings to turn signature checking on.' );
			}
		}

		// Stripe only needs a 2xx. It does not need to be told what we did with it, and
		// this endpoint is public.
		echo $result['ok'] ? 'Payment recorded.' : 'Nothing to do.';

		return true;
	}

	/**
	 * Record a renewal that Stripe billed against a subscription it holds the schedule
	 * for, generating the UCM invoice for the period if the scheduled job has not got to
	 * it yet.
	 *
	 * The guard that matters is the first one. Stripe retries a delivery it does not get
	 * a 2xx for, up to a few days, and create_new_invoice_for_subscription_payment() is
	 * not idempotent - a second call for the same Stripe invoice walks past the period it
	 * already billed, finds it paid, and generates another one. So the Stripe invoice id
	 * is written to invoice_payment.other_id and looked up here before anything else
	 * happens. That is the same column and the same idea one-off payments use.
	 *
	 * Returns what to say to Stripe. Deliberately says little: the endpoint is public.
	 *
	 * @param object $stripe_invoice the Invoice object off the event
	 *
	 * @return string
	 */
	private static function record_subscription_renewal( $stripe_invoice ) {
		$stripe_invoice_id = ucm_stripe_subscriptions::reference_id( $stripe_invoice );
		if ( $stripe_invoice_id === '' ) {
			return 'Nothing to do.';
		}

		if ( ucm_stripe_subscriptions::is_first_subscription_invoice( $stripe_invoice ) ) {
			// The Checkout Session that created the subscription records this one against
			// the invoice the payer was looking at. Taking it again here would generate a
			// second UCM invoice for a period nobody has reached.
			return 'First subscription payment, recorded with the checkout.';
		}

		if ( get_single( 'invoice_payment', 'other_id', $stripe_invoice_id ) ) {
			return 'Already recorded.';
		}

		$stripe_subscription_id = ucm_stripe_subscriptions::invoice_subscription_id( $stripe_invoice );
		if ( $stripe_subscription_id === '' ) {
			return 'Nothing to do.';
		}

		$subscription_row = get_single( 'invoice_payment_subscription', 'stripe_subscription', $stripe_subscription_id );
		if ( ! $subscription_row ) {
			return 'Nothing to do.';
		}
		$invoice_payment = get_single( 'invoice_payment', 'invoice_payment_subscription_id', $subscription_row['invoice_payment_subscription_id'] );
		if ( ! $invoice_payment ) {
			return 'Nothing to do.';
		}

		$data = module_invoice::create_new_invoice_for_subscription_payment( $invoice_payment['invoice_id'], $invoice_payment['invoice_payment_id'], $subscription_row['invoice_payment_subscription_id'] );
		if ( ! $data || empty( $data['invoice_id'] ) || empty( $data['invoice_payment_id'] ) ) {
			send_error( 'Stripe subscription renewal ' . $stripe_invoice_id . ' arrived and UCM could not find or generate the invoice for it. The customer has been charged.' );

			return 'Could not record this payment.';
		}

		$currency    = isset( $stripe_invoice->currency ) ? (string) $stripe_invoice->currency : '';
		$paid_minor  = isset( $stripe_invoice->amount_paid ) ? $stripe_invoice->amount_paid : 0;
		$paid_amount = ucm_payment_verification::from_minor_units( $paid_minor, $currency );

		update_insert( 'invoice_payment_id', $data['invoice_payment_id'], 'invoice_payment', array(
			'date_paid'                       => date( 'Y-m-d' ),
			'amount'                          => $paid_amount,
			'method'                          => self::get_payment_method_name() . ' (Subscription)',
			'method_key'                      => self::get_payment_method_key(),
			'other_id'                        => $stripe_invoice_id,
			'invoice_payment_subscription_id' => $subscription_row['invoice_payment_subscription_id'],
		) );

		update_insert( 'invoice_payment_subscription_id', $subscription_row['invoice_payment_subscription_id'], 'invoice_payment_subscription', array(
			'date_last_pay' => date( 'Y-m-d' ),
			'date_next'     => date( 'Y-m-d', self::next_payment_time( $subscription_row ) ),
		) );

		module_invoice::add_payment_data( $data['invoice_payment_id'], 'log', 'Stripe subscription renewal recorded from invoice ' . $stripe_invoice_id . ' on subscription ' . $stripe_subscription_id . '.' );

		module_cache::clear( 'invoice' );
		module_invoice::save_invoice( $data['invoice_id'], array() );

		return 'Payment recorded.';
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'complete':
				// Stripe sends the payer back here once Checkout is done. The payment is
				// recorded from an authenticated re-fetch rather than from anything in
				// this request, so a payer who edits the URL achieves nothing - and
				// because it is the same idempotent path the webhook uses, an install
				// whose webhook never arrives (no public URL, blocked outbound, a host
				// that eats the raw body) still records its payments.
				$invoice_payment_id = isset( $_REQUEST['i'] ) ? (int) $_REQUEST['i'] : 0;
				$hash               = isset( $_REQUEST['hash'] ) ? (string) $_REQUEST['hash'] : '';
				$session_id         = isset( $_REQUEST['session_id'] ) ? (string) $_REQUEST['session_id'] : '';

				if ( ! $invoice_payment_id || ! hash_equals( self::link_checkout_complete( $invoice_payment_id, true ), $hash ) ) {
					echo htmlspecialchars( _l( 'Sorry, this payment link is not valid.' ) );
					exit;
				}

				$payment = get_single( 'invoice_payment', 'invoice_payment_id', $invoice_payment_id );
				if ( ! $payment ) {
					echo htmlspecialchars( _l( 'Sorry, this payment link is not valid.' ) );
					exit;
				}

				if ( $session_id !== '' && self::have_sdk() ) {
					self::record_checkout_session( $session_id, $invoice_payment_id );
				}

				redirect_browser( module_invoice::link_public_payment_complete( $payment['invoice_id'] ) );
				exit;
			case 'event_ipn':
				// Everything this endpoint handles goes through the modern SDK. The old
				// handler that ran on the bundled 2014 library is gone, along with the
				// library's ten PHP deprecation notices - which printed into this
				// response, and into the page a customer was standing on to pay, on any
				// host with display_errors on.
				if ( self::have_sdk() && self::handle_checkout_webhook() ) {
					exit;
				}
				// Could not make sense of the delivery, or the SDK is not installed.
				// Stripe only needs a 2xx; retrying would not help either case.
				echo 'OK';
				exit;

			case 'pay':
				// The old Charges form posted a card token here. That flow is gone; payments
				// now go through Stripe Checkout, which starts from the invoice page.
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
