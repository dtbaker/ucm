<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$c         = array();
$customers = module_customer::get_customers();
foreach ( $customers as $customer ) {
	$c[ $customer['customer_id'] ] = $customer['customer_name'];
}
$pop3imap_accounts = array();
foreach ( module_ticket::get_accounts() as $account ) {
	$pop3imap_accounts[ $account['ticket_account_id'] ] = $account['name'];
}

module_config::print_settings_form(
	array(
		'heading'  => array(
			'title' => 'Ticket Settings',
			'type'  => 'h2',
			'main'  => true,
		),
		'settings' => array(
			array(
				'key'         => 'ticket_show_summary',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Show unread ticket count in the menu item.',
			),
			array(
				'key'         => 'ticket_recaptcha',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Show recaptcha on ticket form',
			),
			array(
				'key'         => 'ticket_show_position',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Show ticket position (eg: 1st of 10)',
			),
			array(
				'key'         => 'ticket_allow_priority',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Allow priority paid support',
			),
			array(
				'key'         => 'ticket_priority_cost',
				'default'     => 10,
				'type'        => 'currency',
				'description' => 'Cost of a priority support ticket',
			),
			array(
				'key'         => 'faq_ticket_show_product_selection',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Show FAQ product selection',
				'help'        => 'If you have the FAQ module installed this will show a drop down list of products and commoon support questions before the user creates a support ticket.'
			),
			array(
				'key'         => 'ticket_allow_extra_data',
				'default'     => 1,
				'type'        => 'checkbox',
				'description' => 'Allow for extra input boxes on tickets',
				'help'        => 'For FTP usernames and passwords, or whatever else you need',
			),
			array(
				'key'         => 'ticket_from_creators_email',
				'default'     => 0,
				'type'        => 'checkbox',
				'description' => 'Customer replies go directly to staff',
				'help'        => 'Enable this option and any customer email replies will go back to the staff member who sent the support ticket (rather than into the system). Recommended unticking this option.',
			),
			array(
				'key'         => 'ticket_admin_email_alert',
				'default'     => '',
				'type'        => 'text',
				'description' => 'Send notifications of new tickets to this address.',
			),
			array(
				'key'         => 'ticket_admin_alert_subject',
				'default'     => 'Support Ticket Updated: #%s',
				'type'        => 'text',
				'description' => 'The subject to have in ticket notification emails.',
			),
			array(
				'key'         => 'ticket_public_header',
				'default'     => 'Submit a support ticket',
				'type'        => 'text',
				'description' => 'Message to display at the top of the embed ticket form.',
			),
			array(
				'key'         => 'ticket_public_welcome',
				'default'     => '',
				'type'        => 'textarea',
				'description' => 'Text to display at the top of the embed ticket form.',
				'help'        => 'You can use text or html code',
			),
			array(
				'key'         => 'ticket_default_customer_id',
				'default'     => 1,
				'type'        => 'select',
				'options'     => $c,
				'description' => 'Which customer to assign tickets to from the public Ticket Embed Form',
				'help'        => 'Only use this default customer if the customer cannot be found based on the ticket users email address.'
			),
			array(
				'key'              => 'ticket_type_id_default',
				'default'          => 0,
				'type'             => 'select',
				'options'          => module_ticket::get_types(),
				'options_array_id' => 'name',
				'description'      => 'What default ticket type for tickets',
			),
			array(
				'key'         => 'ticket_default_account_id',
				'default'     => 0,
				'type'        => 'select',
				'options'     => $pop3imap_accounts,
				'description' => 'Which default POP3/IMAP account to use',
			),
			array(
				'key'         => 'ticket_public_new_redirect',
				'default'     => '',
				'type'        => 'text',
				'description' => 'Public New Ticket Redirect URL',
				'help'        => 'When a user submits a new public ticket, take them to this URL. Leave blank to use default. Use full URL with http://',
			),
			array(
				'key'         => 'ticket_public_reply_redirect',
				'default'     => '',
				'type'        => 'text',
				'description' => 'Public Reply Ticket Redirect URL',
				'help'        => 'When a user submits a reply to the public ticket form, take them to this URL. Leave blank to use default. Use full URL with http://',
			),
			/*array(
				 'key'=>'ticket_internal_reply_redirect',
				 'default'=>'',
					'type'=>'text',
					'description'=>'Customer Reply Ticket Redirect URL',
					'help' => 'When a custom (who is logged into the system) submits a reply to a ticket, take them to this URL. Leave blank to use default. Use full URL with http://',
			),*/
		)
	)
);