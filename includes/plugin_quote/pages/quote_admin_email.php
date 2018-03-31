<?php
if ( ! $quote_safe ) {
	die( 'failed' );
}
if ( ! module_quote::can_i( 'edit', 'Quotes' ) ) {
	die( 'no perms' );
}
$quote_id = (int) $_REQUEST['quote_id'];
$quote    = module_quote::get_quote( $quote_id );


// template for sending emails.
// are we sending the paid one? or the dueone.
//$template_name = 'quote_email';
$template_name                   = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'quote_email';
$template                        = module_template::get_template_by_key( $template_name );
$quote['total_amount_print']     = dollar( $quote['total_amount'], true, $quote['currency_id'] );
$quote['total_amount_due_print'] = dollar( $quote['total_amount_due'], true, $quote['currency_id'] );
$quote['quote_name']             = $quote['name'];
$quote['from_name']              = module_security::get_loggedin_name();
$quote['quote_url']              = module_quote::link_public( $quote_id );


ob_start();
include( module_theme::include_ucm( 'includes/plugin_quote/template/quote_task_list.php' ) );
$public_html        = ob_get_clean();
$quote['task_list'] = $public_html;

/*ob_start();
$quote_data = $quote;
$ignore_task_hook=true;
$for_email=true;
include('quote_public.php');
$quote['quote_tasks'] = ob_get_clean();*/

// generate the PDF ready for sending.
$pdf = module_quote::generate_pdf( $quote_id );

// find available "to" recipients.
// customer contacts.
$to_select = false;
if ( $quote['customer_id'] ) {
	$customer               = module_customer::get_customer( $quote['customer_id'] );
	$quote['customer_name'] = $customer['customer_name'];
	$to                     = module_user::get_contacts( array( 'customer_id' => $quote['customer_id'] ) );
	if ( $quote['contact_user_id'] > 0 ) {
		$primary = module_user::get_user( $quote['contact_user_id'] );
		if ( $primary ) {
			$to_select = $primary['email'];
		}
	} else if ( $customer['primary_user_id'] ) {
		$primary = module_user::get_user( $customer['primary_user_id'] );
		if ( $primary ) {
			$to_select = $primary['email'];
		}
	}
} else {
	$to = array();
}

$template->assign_values( $quote );

ob_start();
module_email::print_compose(
	array(
		'title'                => _l( 'Email Quote: %s', $quote['name'] ),
		'find_other_templates' => 'quote_email', // find others based on this name, eg: quote_email*
		'current_template'     => $template_name,
		'customer_id'          => $quote['customer_id'],
		'quote_id'             => $quote['quote_id'],
		'debug_message'        => 'Sending quote as email',

		'to'          => $to,
		'to_select'   => $to_select,
		'bcc'         => module_config::c( 'admin_email_address', '' ),
		'content'     => $template->render( 'html' ),
		'subject'     => $template->replace_description(),
		'success_url' => module_quote::link_open( $quote_id ),
		//'success_callback'=>'module_quote::email_sent('.$quote_id.',"'.$template_name.'");',
		'cancel_url'  => module_quote::link_open( $quote_id ),
		'attachments' => array(
			array(
				'path'    => $pdf,
				'name'    => basename( $pdf ),
				'preview' => module_quote::link_public_print( $quote_id ),
			),
		),
	)
);

