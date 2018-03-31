<?php

// not used!
// subscriptions are in the member module. hmmmm.

if ( isset( $_REQUEST['email'] ) && trim( $_REQUEST['email'] ) ) {

	$email = htmlspecialchars( strtolower( trim( $_REQUEST['email'] ) ) );
	if ( ! module_newsletter::subscribe_member( $email ) ) {
		echo 'Subscribe failed... Please go back and enter a valid email address.';
		exit;
	}

	// is the newsletter module giving us a subscription redirection?
	if ( module_config::c( 'newsletter_subscribe_redirect', '' ) ) {
		redirect_browser( module_config::c( 'newsletter_subscribe_redirect', '' ) );
	}
	// or display a message.

	$template             = module_template::get_template_by_key( 'newsletter_subscribe_done' );
	$data['email']        = $email;
	$template->page_title = htmlspecialchars( _l( 'Subscribe' ) );
	$template->assign_values( $data );
	echo $template->render( 'pretty_html' );
	exit;


}
$template             = module_template::get_template_by_key( 'newsletter_subscribe' );
$data['email']        = ''; // to be sure to be sure
$template->page_title = htmlspecialchars( _l( 'Subscribe' ) );

$template->assign_values( $data );
echo $template->render( 'pretty_html' );