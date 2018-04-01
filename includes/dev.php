<?php

// full systems:
define( '_UCM_LITE_ID', 47626 );
define( '_UCM_NEWSLETTER_ID', 1765020 ); // drupal newsletter system/standalone ucm item.
define( '_UCM_PRO_ID', 2621629 ); // 555557
define( '_UCM_FREE_ID', 1 );

// plugins
define( '_UCM_PDF_ID', 1396677 );
define( '_UCM_MOBILE_ID', 1396970 );
define( '_UCM_GROUP_ID', 1397215 );
define( '_UCM_FINANCE_ID', 1396831 );
define( '_UCM_EMAIL_ID', 2067514 ); // email ticket support
define( '_UCM_PIN_ID', 2124883 );
define( '_UCM_TABLE_SORT_ID', 2142496 );
define( '_UCM_NEWSLETTER_PLUGIN_ID', 2565050 ); // newsletter plugin
define( '_UCM_SUBSCRIPTION_ID', 2597920 );
define( '_UCM_ENCRYPTION_ID', 2597947 );
define( '_UCM_CALENDAR_ID', 2613245 );
define( '_UCM_JOB_DISCUSSION_ID', 2616958 ); // 1111119
define( '_UCM_STATISTIC_ID', 4071177 ); // uploaded
define( '_UCM_PAYMENT_GOOGLE_ID', 4071153 ); // uploaded
define( '_UCM_FAQ_ID', 4071302 ); // uploaded!

define( '_UCM_CHANGE_REQUEST_ID', 5334041 ); // READY!
define( '_UCM_PRODUCT_ID', 5333696 ); // READY!
define( '_UCM_PAYMENT_MULTISAFE_ID', 5333846 ); // READY!
define( '_UCM_PAYMENT_STRIPE_ID', 5333838 ); // READY!
define( '_UCM_PAYMENT_AUTHORIZE_ID', 5333804 ); // READY!
define( '_UCM_TIMER_ID', 5333901 ); // READY!
define( '_UCM_SOCIAL_ID', 7415905 ); // READY
define( '_UCM_PAYMENT_COINBASE', 8130249 ); // uploaded

// to launch:
define( '_UCM_DATA_ID', 10 ); // READY!
define( '_UCM_PAYMENT_CHECK', 11 ); // READY!
define( '_UCM_QUOTE_ID', 12 ); // READY
define( '_UCM_SIGNATURE_ID', 13 ); // READY
define( '_UCM_MAP_ID', 30 ); // READY
define( '_UCM_CONTRACT_ID', 34 ); // READY

// in development.
define( '_UCM_VENDOR_ID', 14 ); // in development
define( '_UCM_COMPANY_ID', 15 ); // READY! TODO = company reporting
define( '_UCM_PAYMENT_BRAINTREE_ID', 16 ); // someone already done it: http://codecanyon.net/item/ucm-plugin-braintree-payment-gateway/8935413
define( '_UCM_PAYMENT_MONEYBOOKERS_ID', 17 ); // TODO
define( '_UCM_THEME_DEVELOPR_ID', 18 ); // ALMOST DONE!

// THEMES:
define( '_UCM_THEME_WHITELABEL_ID', 4120556 ); // launched http://themeforest.net/item/ucm-theme-white-label/4120556
define( '_UCM_THEME_METIS_ID', 8 ); // only pro
define( '_UCM_THEME_ADMINLTE_ID', 8565409 ); // launched
define( '_UCM_THEME_BLOCKS_ID', 14745039 ); // pending launch


function dev_ignore_file_for_application( $file, $item_id ) {

	if ( preg_match( '#/reset\.php#', $file ) ||
	     preg_match( '#help\.json#', $file ) ||
	     preg_match( '#\.php\.orig#', $file ) ||
	     preg_match( '#/sql\.sql#', $file ) ||
	     preg_match( '#/dev\.php#', $file ) ||
	     preg_match( '#\.php\.deploy\.php#', $file ) ||
	     preg_match( '#upload/.+#', $file ) ||
	     preg_match( '#/cache/.+#', $file ) ||
	     preg_match( '#templates/.+#', $file ) ||
	     preg_match( '#attachments/.+#', $file ) ||
	     //preg_match('#plugin_backup#',$file) ||
	     preg_match( '#plugin_backup/backups/back#', $file ) ||
	     preg_match( '#plugin_portfolio#', $file ) ||
	     preg_match( '#plugin_wordpress#', $file ) ||
	     //preg_match('#plugin_paymethod_coinbase#',$file) ||
	     //preg_match('#plugin_change_request#',$file) ||
	     preg_match( '#config_generate#', $file ) ) {
		return true;
	}


	if ( $item_id == 'lite' ) {
		$item_id = _UCM_LITE_ID;
	}
	switch ( $item_id ) {
		case _UCM_CONTRACT_ID:
			if (
			preg_match( '#plugin_contract#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_MAP_ID:
			if (
			preg_match( '#plugin_map#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_THEME_BLOCKS_ID:
			if (
			preg_match( '#plugin_theme_blocks#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_THEME_ADMINLTE_ID:
			if (
			preg_match( '#plugin_theme_adminlte#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_VENDOR_ID:
			if (
				preg_match( '#plugin_vendor#', $file ) ||
				preg_match( '#user_admin_edit_staff#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_SIGNATURE_ID:
			if (
			preg_match( '#plugin_signature#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PAYMENT_COINBASE:
			if (
			preg_match( '#plugin_paymethod_coinbase#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_SOCIAL_ID:
			if (
			preg_match( '#plugin_social#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_DATA_ID:
			if (
			preg_match( '#plugin_data/#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_QUOTE_ID:
			if (
			preg_match( '#plugin_quote#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_COMPANY_ID:
			if (
			preg_match( '#plugin_company#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_THEME_WHITELABEL_ID:
			if (
				preg_match( '#plugin_theme/theme.php#', $file ) ||
				preg_match( '#plugin_theme/css/#', $file ) ||
				preg_match( '#plugin_theme/pages/#', $file ) ||
				preg_match( '#plugin_theme/themes/index\.php#', $file ) ||
				preg_match( '#plugin_theme/themes/whitelabel1#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_THEME_METIS_ID:
			if (
				preg_match( '#plugin_theme/theme.php#', $file ) ||
				preg_match( '#plugin_theme/css/#', $file ) ||
				preg_match( '#plugin_theme/pages/#', $file ) ||
				preg_match( '#plugin_theme/themes/index\.php#', $file ) ||
				preg_match( '#plugin_theme/themes/metis#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_TIMER_ID:
			if ( preg_match( '#plugin_timer#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_CHANGE_REQUEST_ID:
			if ( preg_match( '#plugin_change_request#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_STATISTIC_ID:
			if ( preg_match( '#plugin_statistic#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PRODUCT_ID:
			if ( preg_match( '#plugin_product#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_JOB_DISCUSSION_ID:
			if ( preg_match( '#plugin_job_discussion#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_CALENDAR_ID:
			if ( preg_match( '#plugin_calendar#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_ENCRYPTION_ID:
			if ( preg_match( '#plugin_encrypt#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_SUBSCRIPTION_ID:
			// push group plugin out as well.
			if ( preg_match( '#plugin_subscription#', $file ) || preg_match( '#plugin_member#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PAYMENT_STRIPE_ID:
			// push group plugin out as well.
			if ( preg_match( '#plugin_paymethod_stripe#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files
			break;
		case _UCM_PAYMENT_AUTHORIZE_ID:
			// push group plugin out as well.
			if ( preg_match( '#plugin_paymethod_authorize#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files
			break;
		case _UCM_PAYMENT_CHECK:
			// push group plugin out as well.
			if ( preg_match( '#plugin_paymethod_check#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files
			break;
		case _UCM_PAYMENT_MULTISAFE_ID:
			// push group plugin out as well.
			if ( preg_match( '#plugin_paymethod_multisafepay#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files
			break;
		case _UCM_PAYMENT_GOOGLE_ID:
			// push group plugin out as well.
			if ( preg_match( '#plugin_paymethod_google#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_NEWSLETTER_PLUGIN_ID:
			// push group plugin out as well.
			if (
				preg_match( '#plugin_newsletter#', $file ) ||
				preg_match( '#plugin_member#', $file ) ||
				preg_match( '#plugin_core#', $file ) ||
				preg_match( '#plugin_langauge#', $file ) ||
				preg_match( '#plugin_db#', $file ) ||
				preg_match( '#plugin_group#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_TABLE_SORT_ID:
			if ( preg_match( '#plugin_table_sort#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PIN_ID:
			if ( preg_match( '#plugin_pin#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_FAQ_ID:
			if ( preg_match( '#plugin_faq#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_EMAIL_ID:
			if ( preg_match( '#ticket_settings_accounts#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_FINANCE_ID:
			if ( preg_match( '#plugin_finance/pages/finance#', $file ) ||
			     preg_match( '#plugin_finance/pages/recurring#', $file ) ) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_GROUP_ID:
			if ( preg_match( '#plugin_group#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_MOBILE_ID:
			if ( preg_match( '#plugin_mobile#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PDF_ID:
			// we only want the html2ps and pdf files in this plugin
			if ( preg_match( '#plugin_pdf#', $file ) ||
			     preg_match( '#invoice_admin_print#', $file )
			) {
				return false; // dont ignore these
			}

			return true; // ignore all other files.
			break;
		case _UCM_PRO_ID:
			if (
				//preg_match('#customer_signup#',$file) ||
				//preg_match('#plugin_timer#',$file) || // not ready for pro yet
				preg_match( '#plugin_theme/themes/whitelabel1#', $file ) // don't push this theme out as part of pro
				|| preg_match( '#plugin_theme/themes/developr#', $file ) // don't push this theme out as part of pro
				|| preg_match( '#plugin_theme_adminlte#', $file ) // don't push this theme out as part of pro
				|| preg_match( '#plugin_theme_blocks#', $file ) // don't push this theme out as part of pro
				|| preg_match( '#plugin_vendor#', $file )
				//|| preg_match('#plugin_theme/themes/metis#',$file) // don't push this theme out as part of pro (just yet, do it when ready)
				//|| preg_match('#html2ps#',$file)  // don't push this out, too big!. include it in install zip.
			) {
				// ignore certain files even from Pro edition.
				return true;
			}

			if (
				preg_match( '#plugin_address#', $file ) ||
				//preg_match('#plugin_api#',$file) ||
				preg_match( '#plugin_backup#', $file ) ||
				preg_match( '#plugin_cache#', $file ) ||
				preg_match( '#plugin_calendar#', $file ) ||
				preg_match( '#plugin_captcha#', $file ) ||
				preg_match( '#plugin_config#', $file ) ||
				preg_match( '#plugin_core#', $file ) ||
				preg_match( '#plugin_change_request#', $file ) ||
				preg_match( '#plugin_company#', $file ) ||
				preg_match( '#plugin_contract#', $file ) ||
				preg_match( '#plugin_customer#', $file ) ||
				preg_match( '#plugin_dashboard#', $file ) ||
				preg_match( '#plugin_data#', $file ) ||
				preg_match( '#plugin_db#', $file ) ||
				preg_match( '#plugin_debug#', $file ) ||
				preg_match( '#plugin_email#', $file ) ||
				preg_match( '#plugin_encrypt#', $file ) ||
				preg_match( '#plugin_extra#', $file ) ||
				preg_match( '#plugin_faq#', $file ) ||
				preg_match( '#plugin_file#', $file ) ||
				preg_match( '#plugin_finance#', $file ) ||
				preg_match( '#plugin_form#', $file ) ||
				preg_match( '#plugin_group#', $file ) ||
				preg_match( '#plugin_help#', $file ) ||
				preg_match( '#plugin_import_export#', $file ) ||
				preg_match( '#plugin_invoice#', $file ) ||
				preg_match( '#plugin_job/#', $file ) ||
				preg_match( '#plugin_job_discussion#', $file ) ||
				preg_match( '#plugin_log#', $file ) ||
				preg_match( '#plugin_language#', $file ) ||
				preg_match( '#plugin_map#', $file ) ||
				preg_match( '#plugin_member#', $file ) ||
				preg_match( '#plugin_mobile#', $file ) ||
				preg_match( '#plugin_newsletter#', $file ) ||
				preg_match( '#plugin_note#', $file ) ||
				preg_match( '#plugin_paymethod_authorize#', $file ) ||
				preg_match( '#plugin_paymethod_banktransfer#', $file ) ||
				preg_match( '#plugin_paymethod_coinbase#', $file ) ||
				preg_match( '#plugin_paymethod_check#', $file ) ||
				preg_match( '#plugin_paymethod_google#', $file ) ||
				preg_match( '#plugin_paymethod_paypal#', $file ) ||
				preg_match( '#plugin_paymethod_paynl#', $file ) ||
				preg_match( '#plugin_paymethod_stripe#', $file ) ||
				preg_match( '#plugin_paymethod_multisafepay#', $file ) ||
				preg_match( '#plugin_paymethod_other#', $file ) ||
				preg_match( '#plugin_pdf#', $file ) ||
				preg_match( '#plugin_product#', $file ) ||
				preg_match( '#plugin_pin#', $file ) ||
				preg_match( '#plugin_quote#', $file ) ||
				preg_match( '#plugin_security#', $file ) ||
				preg_match( '#plugin_session#', $file ) ||
				preg_match( '#plugin_setup#', $file ) ||
				preg_match( '#plugin_social#', $file ) ||
				preg_match( '#plugin_subscription#', $file ) ||
				preg_match( '#plugin_statistic#', $file ) ||
				preg_match( '#plugin_table_sort#', $file ) ||
				preg_match( '#plugin_template#', $file ) ||
				preg_match( '#plugin_theme#', $file ) ||
				preg_match( '#plugin_ticket#', $file ) ||
				preg_match( '#plugin_timer#', $file ) ||
				preg_match( '#plugin_user#', $file ) ||
				//preg_match('#plugin_vendor#',$file) ||
				preg_match( '#plugin_website#', $file )
			) {
				// we want to keep these files! don't ignore them
				return false;
			}

			// ignore all others.
			return true;
			break;
		case _UCM_NEWSLETTER_ID:
			// DRUPAL NEWSLETTER PLUGIN. similar to standalone newsletter script (NOT THE PLUGIN FOR LITE EDITION)
			// work out which files to keep.
			if ( preg_match( '#plugin_theme/themes/#', $file ) ) {
				return true; // ignore themes.
			}
			if (
				preg_match( '#plugin_cache#', $file ) ||
				preg_match( '#plugin_setup#', $file ) ||
				preg_match( '#plugin_extra#', $file ) ||
				preg_match( '#plugin_debug#', $file ) ||
				preg_match( '#plugin_newsletter#', $file ) ||
				preg_match( '#plugin_group#', $file ) ||
				preg_match( '#plugin_user#', $file ) ||
				preg_match( '#plugin_config#', $file ) ||
				preg_match( '#plugin_email#', $file ) ||
				preg_match( '#plugin_file#', $file ) ||
				preg_match( '#plugin_form#', $file ) ||
				preg_match( '#plugin_language#', $file ) ||
				preg_match( '#plugin_import_export#', $file ) ||
				preg_match( '#plugin_security#', $file ) ||
				preg_match( '#plugin_template#', $file ) ||
				preg_match( '#plugin_theme/#', $file ) || // but keep theme plugin
				preg_match( '#plugin_member#', $file )
			) {
				// keep these files!
				return false;
			}

			// ignore everything else
			return true;
			break;
		case _UCM_LITE_ID:
			// ultimate client manager lite edition
			// ignore these files from the lite edition.
			return preg_match( '#plugin_calendar#', $file ) ||
			       preg_match( '#plugin_api#', $file ) ||
			       preg_match( '#plugin_change_request#', $file ) ||
			       preg_match( '#plugin_company#', $file ) ||
			       preg_match( '#plugin_contract#', $file ) ||
			       preg_match( '#plugin_data/#', $file ) ||
			       preg_match( '#plugin_employer#', $file ) ||
			       preg_match( '#plugin_envato#', $file ) ||
			       preg_match( '#plugin_faq#', $file ) ||
			       preg_match( '#plugin_encrypt#', $file ) ||
			       preg_match( '#plugin_finance/pages/finance#', $file ) ||
			       preg_match( '#plugin_finance/pages/recurring#', $file ) ||
			       preg_match( '#plugin_group#', $file ) ||
			       preg_match( '#invoice_admin_print#', $file ) ||
			       preg_match( '#plugin_mobile#', $file ) ||
			       preg_match( '#plugin_member#', $file ) ||
			       preg_match( '#plugin_newsletter#', $file ) ||
			       preg_match( '#plugin_pdf#', $file ) ||
			       preg_match( '#plugin_job_discussion#', $file ) ||
			       preg_match( '#plugin_log#', $file ) ||
			       preg_match( '#plugin_pin#', $file ) ||
			       preg_match( '#plugin_paymethod_authorize#', $file ) ||
			       preg_match( '#plugin_paymethod_creditcard#', $file ) ||
			       preg_match( '#plugin_paymethod_coinbase#', $file ) ||
			       preg_match( '#plugin_paymethod_google#', $file ) ||
			       preg_match( '#plugin_paymethod_stripe#', $file ) ||
			       preg_match( '#plugin_paymethod_multisafepay#', $file ) ||
			       preg_match( '#plugin_paymethod_braintree#', $file ) ||
			       preg_match( '#plugin_paymethod_other#', $file ) ||
			       preg_match( '#plugin_paymethod_paynl#', $file ) ||
			       preg_match( '#plugin_product#', $file ) ||
			       preg_match( '#plugin_quote#', $file ) ||
			       //preg_match('#theme_settings#',$file) ||
			       preg_match( '#plugin_statistic#', $file ) ||
			       preg_match( '#plugin_signature#', $file ) ||
			       preg_match( '#plugin_social#', $file ) ||
			       preg_match( '#plugin_subscription#', $file ) ||
			       preg_match( '#ticket_settings_accounts#', $file ) ||
			       preg_match( '#plugin_table_sort#', $file ) ||
			       preg_match( '#plugin_timer#', $file ) ||
			       preg_match( '#plugin_vendor#', $file ) ||
			       preg_match( '#html2ps#', $file ) ||
			       preg_match( '#/reset\.php#', $file ) ||
			       preg_match( '#sql\.sql#', $file ) ||
			       preg_match( '#config_generate#', $file ) ||
			       preg_match( '#customer_signup#', $file ) ||
			       preg_match( '#plugin_theme/themes/#', $file ) ||
			       preg_match( '#plugin_theme_adminlte#', $file ) ||
			       preg_match( '#plugin_theme_blocks#', $file ) ||
			       preg_match( '#/pro\.php#', $file );
			break;
		case _UCM_FREE_ID:
			// ultimate client manager free edition
			// ignore these files from the lite edition.
			return preg_match( '#plugin_calendar#', $file ) ||
			       preg_match( '#plugin_api#', $file ) ||
			       preg_match( '#plugin_backup#', $file ) ||
			       preg_match( '#plugin_change_request#', $file ) ||
			       preg_match( '#plugin_company#', $file ) ||
			       preg_match( '#plugin_contract#', $file ) ||
			       preg_match( '#plugin_data/#', $file ) ||
			       preg_match( '#plugin_employer#', $file ) ||
			       preg_match( '#plugin_envato#', $file ) ||
			       preg_match( '#plugin_extra#', $file ) ||
			       preg_match( '#plugin_faq#', $file ) ||
			       preg_match( '#plugin_file#', $file ) ||
			       preg_match( '#plugin_encrypt#', $file ) ||
			       preg_match( '#plugin_finance#', $file ) ||
			       preg_match( '#plugin_group#', $file ) ||
			       preg_match( '#plugin_import_export#', $file ) ||
			       preg_match( '#invoice_admin_print#', $file ) ||
			       preg_match( '#plugin_mobile#', $file ) ||
			       preg_match( '#plugin_map#', $file ) ||
			       preg_match( '#plugin_member#', $file ) ||
			       preg_match( '#plugin_newsletter#', $file ) ||
			       preg_match( '#plugin_note#', $file ) ||
			       //preg_match('#plugin_job#',$file) ||
			       preg_match( '#plugin_job_discussion#', $file ) ||
			       preg_match( '#plugin_pdf#', $file ) ||
			       preg_match( '#plugin_pin#', $file ) ||
			       preg_match( '#plugin_paymethod_authorize#', $file ) ||
			       preg_match( '#plugin_paymethod_coinbase#', $file ) ||
			       preg_match( '#plugin_paymethod_check#', $file ) ||
			       preg_match( '#plugin_paymethod_creditcard#', $file ) ||
			       preg_match( '#plugin_paymethod_google#', $file ) ||
			       preg_match( '#plugin_paymethod_stripe#', $file ) ||
			       preg_match( '#plugin_paymethod_multisafepay#', $file ) ||
			       preg_match( '#plugin_paymethod_braintree#', $file ) ||
			       preg_match( '#plugin_paymethod_other#', $file ) ||
			       preg_match( '#plugin_paymethod_paynl#', $file ) ||
			       preg_match( '#plugin_product#', $file ) ||
			       preg_match( '#plugin_quote#', $file ) ||
			       preg_match( '#plugin_session#', $file ) ||
			       preg_match( '#plugin_signature#', $file ) ||
			       preg_match( '#plugin_social#', $file ) ||
			       preg_match( '#plugin_statistic#', $file ) ||
			       preg_match( '#plugin_subscription#', $file ) ||
			       preg_match( '#ticket_settings_accounts#', $file ) ||
			       preg_match( '#plugin_table_sort#', $file ) ||
			       preg_match( '#plugin_timer#', $file ) ||
			       preg_match( '#plugin_ticket#', $file ) ||
			       preg_match( '#plugin_vendor#', $file ) ||
			       preg_match( '#user_admin_edit_staff#', $file ) ||
			       preg_match( '#plugin_website#', $file ) ||
			       preg_match( '#contact_admin_list#', $file ) ||
			       preg_match( '#invoice_recurring#', $file ) ||
			       //preg_match('#htmlpurifier#',$file) ||
			       preg_match( '#html2ps#', $file ) ||
			       preg_match( '#/reset\.php#', $file ) ||
			       preg_match( '#sql\.sql#', $file ) ||
			       preg_match( '#config_generate#', $file ) ||
			       preg_match( '#customer_signup#', $file ) ||
			       preg_match( '#plugin_theme/themes/#', $file ) ||
			       preg_match( '#plugin_theme_adminlte#', $file ) ||
			       preg_match( '#plugin_theme_blocks#', $file ) ||
			       preg_match( '#/pro\.php#', $file );
			break;
	}

	return true; // default to ignore files from all.
}

function dev_ignore_file_for_packaging( $file ) {


	if ( ! file_exists( $file ) ) {
		return true;
	}

	if ( strpos( $file, '.deploy.php' ) ) {
		return false; // dont ignore these files.
	}
	if ( is_file( $file . '.deploy.php' ) ) {
		// we return the deploy version, not this version.
		// ignore this file.
		return true;
	}


	return
		preg_match( '#/reset\.php#', $file ) ||
		preg_match( '#\.php\.orig#', $file ) ||
		preg_match( '#sql\.sql#', $file ) ||
		preg_match( '#/dev\.php#', $file ) ||
		preg_match( '#/pro\.php#', $file ) ||
		//preg_match('#html2ps#',$file) ||
		preg_match( '#plugin_address#', $file ) ||
		preg_match( '#plugin_calendar#', $file ) ||
		preg_match( '#plugin_company#', $file ) ||
		preg_match( '#plugin_customer#', $file ) ||
		preg_match( '#plugin_change_request#', $file ) ||
		preg_match( '#plugin_data/#', $file ) ||
		preg_match( '#plugin_email#', $file ) ||
		preg_match( '#plugin_employer#', $file ) ||
		preg_match( '#plugin_encrypt#', $file ) ||
		preg_match( '#plugin_envato#', $file ) ||
		preg_match( '#plugin_extra#', $file ) ||
		preg_match( '#plugin_faq#', $file ) ||
		preg_match( '#plugin_file#', $file ) ||
		preg_match( '#plugin_finance#', $file ) ||
		preg_match( '#plugin_group#', $file ) ||
		preg_match( '#plugin_import_export#', $file ) ||
		preg_match( '#plugin_invoice#', $file ) ||
		preg_match( '#plugin_job/#', $file ) ||
		preg_match( '#plugin_job_discussion#', $file ) ||
		preg_match( '#plugin_mobile#', $file ) ||
		preg_match( '#plugin_newsletter#', $file ) ||
		preg_match( '#plugin_member#', $file ) ||
		preg_match( '#plugin_pdf#', $file ) ||
		preg_match( '#plugin_pin#', $file ) ||
		preg_match( '#plugin_paymethod#', $file ) ||
		preg_match( '#plugin_product#', $file ) ||
		preg_match( '#plugin_note#', $file ) ||
		preg_match( '#plugin_table_sort#', $file ) ||
		preg_match( '#plugin_template#', $file ) ||
		preg_match( '#plugin_timer#', $file ) ||
		preg_match( '#plugin_portfolio#', $file ) ||
		preg_match( '#plugin_quote#', $file ) ||
		preg_match( '#plugin_subscription#', $file ) ||
		preg_match( '#plugin_ticket#', $file ) ||
		preg_match( '#plugin_statistic#', $file ) ||
		preg_match( '#plugin_social#', $file ) ||
		preg_match( '#plugin_user/pages#', $file ) ||
		preg_match( '#plugin_website#', $file ) ||
		preg_match( '#plugin_wordpress#', $file ) ||
		preg_match( '#process\.php#', $file ) ||
		preg_match( '#/ajax\.php#', $file ) ||
		preg_match( '#/cron\.php#', $file ) ||
		preg_match( '#/design_menu\.php#', $file ) ||
		preg_match( '#/ext\.php#', $file ) ||
		preg_match( '#plugin_theme/themes/#', $file ) ||
		preg_match( '#config_generate#', $file );
}

