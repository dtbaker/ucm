<?php


class module_paymethod_check extends module_base {

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
		$this->version = 2.11;
		//2.1 - 2014-11-27 - initial release
		//2.11 - 2015-03-14 - better default payment method options

		$this->module_name     = "paymethod_check";
		$this->module_position = 8882;

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'paymethod_check', 'Hello,
Please make payment by Check as per the below details:

{CHECK_DETAILS}

If you have any questions please feel free to contact us.

Please <a href="{LINK}" target="_blank">click here</a> to return to your previous page.

Thank you
', 'Displayed when Check payment method is selected.' );

			module_template::init_template( 'paymethod_check_details', 'Payable To: <strong>Your Name Here</strong>
Post To: <strong>123 Your Address, LA</strong>
Amount: <strong>{AMOUNT}</strong>', 'Check details for invoice payments.' );
		}

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Check",
				"p"                   => "check_settings",
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
		return $method == 'offline';
	}

	public static function is_enabled() {
		return module_config::c( 'payment_method_check_enabled', 0 );
	}


	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_check_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_check_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['check'] ) ) {
			return $invoice_payment_methods['check']['enabled'];
		}

		return module_config::c( 'payment_method_check_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'check', `enabled` = " . (int) $allowed;
		query( $sql );
	}


	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_check_label', 'Check' );
	}

	public function get_invoice_payment_description( $invoice_id, $method = '' ) {
		$template     = module_template::get_template_by_key( 'paymethod_check_details' );
		$invoice_data = module_invoice::get_invoice( $invoice_id );
		$template->assign_values( $invoice_data + array(
				'amount' => dollar( $invoice_data['total_amount_due'], true, $invoice_data['currency_id'] ),
			) );
		$invoice_replace = module_invoice::get_replace_fields( $invoice_id, $invoice_data );
		$template->assign_values( $invoice_replace );

		return $template->render( 'html' );
	}

	public static function start_payment( $invoice_id, $payment_amount, $invoice_payment_id ) {
		if ( $invoice_id && $payment_amount && $invoice_payment_id ) {
			// we are starting a payment via check!
			// setup a pending payment and redirect to check.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			$description  = _l( 'Payment for invoice %s', $invoice_data['name'] );
			self::check_redirect( $description, $payment_amount, module_security::get_loggedin_id(), $invoice_payment_id, $invoice_id );

			return true;
		}

		return false;
	}

	public static function check_redirect( $description, $amount, $user_id, $payment_id, $invoice_id ) {

		$invoice_data    = module_invoice::get_invoice( $invoice_id );
		$invoice_replace = module_invoice::get_replace_fields( $invoice_id, $invoice_data );

		$check_details = module_template::get_template_by_key( 'paymethod_check_details' );
		$check_details->assign_values( $invoice_data + array(
				'amount' => dollar( $amount, true, $invoice_data['currency_id'] ),
			) );
		$check_details->assign_values( $invoice_replace );
		$check_details_html = $check_details->render( 'html' );

		// display a template with the check details in it.
		$template = module_template::get_template_by_key( 'paymethod_check' );
		$template->assign_values( array(
			'check_details' => $check_details_html,
			'link'          => module_invoice::link_open( $invoice_id ),
		) );
		$template->assign_values( $invoice_replace );
		echo $template->render( 'pretty_html' );
		exit;
	}

}