<?php
if ( ! $invoice_safe ) {
	die( 'failed' );
}
$invoice_id = (int) $_REQUEST['invoice_id'];
$invoice    = module_invoice::get_invoice( $invoice_id );

if ( class_exists( 'module_company', false ) && isset( $invoice['company_id'] ) && (int) $invoice['company_id'] > 0 ) {
	module_company::set_current_company_id( $invoice['company_id'] );
}


// template for sending emails.
// are we sending the paid one? or the dueone.
$original_template_name = $template_name = '';
$template_name          = '';
$template_type          = 'due'; // this is used in our new json based hook feature
$template_prefix        = isset( $invoice['invoice_template_email'] ) && strlen( $invoice['invoice_template_email'] ) ? $invoice['invoice_template_email'] : 'invoice_email';
if ( isset( $invoice['credit_note_id'] ) && $invoice['credit_note_id'] ) {
	$original_template_name = $template_name = 'credit_note_email';
	$template_type          = 'credit_note'; // this is used in our new json based hook feature
} else if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) {
	$original_template_name = $template_name = $template_prefix . '_paid';
	$template_type          = 'paid'; // this is used in our new json based hook feature
} else if ( $invoice['overdue'] && $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
	$original_template_name = $template_name = $template_prefix . '_overdue';
	$template_type          = 'overdue'; // this is used in our new json based hook feature
} else {
	$original_template_name = $template_name = $template_prefix . '_due';
}
$template_name = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : $template_name;
$template_name = hook_filter_var( 'invoice_email_template', $template_name, $invoice_id, $invoice );
$template      = module_template::get_template_by_key( $template_name );

$replace = module_invoice::get_replace_fields( $invoice_id, $invoice );

$replace['from_name'] = module_security::get_loggedin_name();

// generate the PDF ready for sending.
$pdf = module_invoice::generate_pdf( $invoice_id );

// find available "to" recipients.
// customer contacts.
$to_select = false;
$to        = array();
if ( $invoice['customer_id'] ) {
	$customer                 = module_customer::get_customer( $invoice['customer_id'] );
	$replace['customer_name'] = $customer['customer_name'];
	$to                       = module_user::get_contacts( array( 'customer_id' => $invoice['customer_id'] ) );
	if ( $invoice['contact_user_id'] > 0 ) {
		$primary = module_user::get_user( $invoice['contact_user_id'] );
		if ( $primary ) {
			$to_select = $primary['email'];
		}
	} else {
		// hunt for 'accounts' extra field
		$field_to_find = strtolower( module_config::c( 'accounts_extra_field_name', 'Accounts' ) );
		foreach ( $to as $contact ) {
			$extras = module_extra::get_extras( array(
				'owner_table' => 'user',
				'owner_id'    => $contact['user_id']
			) );
			foreach ( $extras as $e ) {
				if ( strtolower( $e['extra_key'] ) == $field_to_find ) {
					// this is the accounts contact - woo!
					$to_select = $contact['email'];
				}
			}
		}
		if ( ! $to_select && $customer['primary_user_id'] ) {
			$primary = module_user::get_user( $customer['primary_user_id'] );
			if ( $primary ) {
				$to_select = $primary['email'];
			}
		}
	}
} else if ( $invoice['member_id'] ) {
	$member                   = module_member::get_member( $invoice['member_id'] );
	$to                       = array( $member );
	$replace['customer_name'] = $member['first_name'];
} else {
	$to = array();
}

$template->assign_values( $replace );


module_email::print_compose(
	array(
		'title'                 => _l( 'Email Invoice: %s', $invoice['name'] ),
		'find_other_templates'  => 'invoice_email',
		// find others based on this name, eg: job_email*
		'current_template'      => $template_name,
		'customer_id'           => $invoice['customer_id'],
		'company_id'            => isset( $invoice['company_id'] ) ? $invoice['company_id'] : 0,
		'to'                    => $to,
		'to_select'             => $to_select,
		'bcc'                   => module_config::c( 'admin_email_address', '' ),
		'content'               => $template->render( 'html' ),
		'subject'               => $template->replace_description(),
		'success_url'           => module_invoice::link_open( $invoice_id ),
		'success_callback'      => 'module_invoice::email_sent',
		// ('.$invoice_id.',"'.$template_name.'","{SUBJECT}","{TO}");
		'success_callback_args' => array(
			'invoice_id'    => $invoice_id,
			'template_name' => $template_name,
			'template_type' => $template_type,
		),
		'invoice_id'            => $invoice_id,
		'cancel_url'            => module_invoice::link_open( $invoice_id ),
		'attachments'           => array(
			array(
				'path'    => $pdf,
				'name'    => basename( $pdf ),
				'preview' => module_invoice::link_generate( $invoice_id, array(
					'arguments' => array( 'go' => 1, 'print' => 1 ),
					'page'      => 'invoice_admin',
					'full'      => false
				) ),
			),
		),
	)
);
?>