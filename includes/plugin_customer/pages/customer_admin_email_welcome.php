<?php


$page_type        = 'Customers';
$page_type_single = 'Customer';

$current_customer_type_id = module_customer::get_current_customer_type_id();
if ( $current_customer_type_id > 0 ) {
	$customer_type = module_customer::get_customer_type( $current_customer_type_id );
	if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
		$page_type        = $customer_type['type_name_plural'];
		$page_type_single = $customer_type['type_name'];
	}
}
if ( ! module_customer::can_i( 'view', $page_type ) ) {
	redirect_browser( _BASE_HREF );
}


$customer_id = (int) $_REQUEST['customer_id'];
$customer    = array();

$customer = module_customer::get_customer( $customer_id );

if ( $customer_id > 0 && $customer['customer_id'] == $customer_id ) {
	$module->page_title = _l( $page_type_single . ': %s', $customer['customer_name'] );
} else {
	$module->page_title = _l( $page_type_single . ': %s', _l( 'New' ) );
}
// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $customer_id > 0 && $customer['customer_id'] == $customer_id ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Customer',
			'page_name' => $page_type,
			'module'    => 'customer',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Customer',
			'page_name' => $page_type,
			'module'    => 'customer',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'customer', $customer );
}

$replace_fields = module_customer::get_replace_fields( $customer_id );
if ( ! $customer['primary_user_id'] ) {
	echo 'There is no primary contact. Please go back and create a primary contact for this customer first.';

	return;
}
if ( ! $replace_fields['contact_email'] ) {
	echo 'The primary contact does not have an email address. Please go back and set an email address for this customer contact.';

	return;
}
if ( ! module_security::can_user_login( $customer['primary_user_id'] ) ) {
	echo _l( 'Warning: the user %s does not have login permissions yet - login will not work. Please <a href="%s">click here</a> and give this contact a User Role that has permissions to login to the system.', $replace_fields['contact_email'], module_user::link_open_contact( $customer['primary_user_id'], false, array(), true ) );

	return;

}

$template_name = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'customer_welcome_email';
$template      = module_template::get_template_by_key( $template_name );

$to        = module_user::get_contacts( array( 'customer_id' => $customer['customer_id'] ) );
$to_select = false;
if ( $customer['primary_user_id'] ) {
	$primary = module_user::get_user( $customer['primary_user_id'] );
	if ( $primary ) {
		$to_select = $primary['email'];
	}
}

$template->assign_values( $replace_fields );

if (
	(
		( module_user::can_i( 'view', 'Users Passwords', 'Config' ) && $customer['primary_user_id'] == module_security::get_loggedin_id() )
		||
		module_user::can_i( 'edit', 'Users Passwords', 'Config' )
	)
	&& (int) $customer['primary_user_id'] > 0
) {
	// same permissions as used on user password change page.
	$url = module_user::link_open_contact( $customer['primary_user_id'], false, array(), true );
	$url .= ( strpos( $url, '?' ) ? '&' : '?' ) . 'reset_password=' . module_security::get_auto_login_string( $customer['primary_user_id'] );
	$url .= '&auto_login=' . module_security::get_auto_login_string( $customer['primary_user_id'] );
	$template->assign_values( array(
		'PASSWORD_RESET_URL' => $url,
	) );
}


module_email::print_compose(
	array(
		'title'                => _l( 'Email Customer: %s', $customer['customer_name'] ),
		'find_other_templates' => 'customer_welcome_email', // find others based on this name, eg: job_email*
		'current_template'     => $template_name,
		'customer_id'          => $customer['customer_id'],
		'debug_message'        => 'Sending customer welcome email',
		'to'                   => $to,
		'to_select'            => $to_select,
		'bcc'                  => module_config::c( 'admin_email_address', '' ),
		'content'              => $template->render( 'html' ),
		'subject'              => $template->replace_description(),
		'success_url'          => module_customer::link_open( $customer['customer_id'] ),
		//'success_callback'=>'module_job::email_sent('.$job_id.',"'.$template_name.'");',
		'cancel_url'           => module_customer::link_open( $customer['customer_id'] ),
	)
);


