<?php


class module_paymethod_banktransfer extends module_base {

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
		$this->version = 2.241;
		//2.21 - currency fix.
		//2.22 - edit template
		//2.23 - improved settings page
		//2.24 - 2014-03-16 - added more tags to bank transfer templates
		//2.241 - 2015-03-14 - better default payment method options

		$this->module_name     = "paymethod_banktransfer";
		$this->module_position = 8882;

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'paymethod_banktransfer', 'Hello,
Please make payment via Bank Transfer using the following details:

{BANK_DETAILS}

If you have any questions please feel free to contact us.

Please <a href="{LINK}" target="_blank">click here</a> to return to your previous page.

Thank you
', 'Displayed when Bank Transfer payment method is selected.' );

			module_template::init_template( 'paymethod_banktransfer_details', 'Bank Name: <strong>Name Here</strong>
Bank Account: <strong>123456</strong>
Payment Reference: <strong>{NAME}</strong>
Amount: <strong>{AMOUNT}</strong>', 'Bank transfer details for invoice payments.' );
		}

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => "Bank Transfer",
				"p"                   => "bank_settings",
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
		return module_config::c( 'payment_method_banktransfer_enabled', 1 );
	}


	public function is_allowed_for_invoice( $invoice_id ) {
		if ( ! self::is_enabled() ) {
			return false;
		}
		$old_default = module_config::c( '__inv_banktransfer_' . $invoice_id );
		if ( $old_default !== false ) {
			$this->set_allowed_for_invoice( $invoice_id, $old_default );
			delete_from_db( 'config', 'key', '__inv_banktransfer_' . $invoice_id );
			module_cache::clear( 'config' );

			return $old_default;
		}
		// check for manually enabled invoice payment method.
		$invoice_payment_methods = module_invoice::get_invoice_payment_methods( $invoice_id );
		if ( isset( $invoice_payment_methods['banktransfer'] ) ) {
			return $invoice_payment_methods['banktransfer']['enabled'];
		}

		return module_config::c( 'payment_method_banktransfer_enabled_default', 1 );
	}

	public function set_allowed_for_invoice( $invoice_id, $allowed = 1 ) {
		$sql = "REPLACE INTO `" . _DB_PREFIX . "invoice_payment_method` SET `invoice_id` = " . (int) $invoice_id . ", `payment_method` = 'banktransfer', `enabled` = " . (int) $allowed;
		query( $sql );
	}

	public static function get_payment_method_name() {
		return module_config::s( 'payment_method_banktransfer_label', 'Bank Transfer' );
	}

	public function get_invoice_payment_description( $invoice_id, $method = '' ) {
		$template     = module_template::get_template_by_key( 'paymethod_banktransfer_details' );
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
			// we are starting a payment via banktransfer!
			// setup a pending payment and redirect to banktransfer.
			$invoice_data = module_invoice::get_invoice( $invoice_id );
			$description  = _l( 'Payment for invoice %s', $invoice_data['name'] );
			self::banktransfer_redirect( $description, $payment_amount, module_security::get_loggedin_id(), $invoice_payment_id, $invoice_id );

			return true;
		}

		return false;
	}

	public static function banktransfer_redirect( $description, $amount, $user_id, $payment_id, $invoice_id ) {

		$invoice_data    = module_invoice::get_invoice( $invoice_id );
		$invoice_replace = module_invoice::get_replace_fields( $invoice_id, $invoice_data );

		$bank_details = module_template::get_template_by_key( 'paymethod_banktransfer_details' );
		$bank_details->assign_values( $invoice_data + array(
				'amount' => dollar( $amount, true, $invoice_data['currency_id'] ),
			) );
		$bank_details->assign_values( $invoice_replace );
		$bank_details_html = $bank_details->render( 'html' );

		// display a template with the bank details in it.
		$template = module_template::get_template_by_key( 'paymethod_banktransfer' );
		$template->assign_values( array(
			'bank_details' => $bank_details_html,
			'link'         => module_invoice::link_open( $invoice_id ),
		) );
		$template->assign_values( $invoice_replace );
		echo $template->render( 'pretty_html' );
		exit;
	}

}