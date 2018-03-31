<?php


if ( ! module_invoice::can_i( 'edit', 'Invoice Settings', 'Config' ) ) {
	redirect_browser( _BASE_HREF );
}
ob_start();
$templates                                    = module_template::get_templates();
$invoice_templates                            = array();
$invoice_templates['external_invoice']        = 1;
$invoice_templates['invoice_external']        = 1;
$invoice_templates['credit_note_external']    = 1;
$invoice_templates['invoice_payment_receipt'] = 1;
$invoice_templates['invoice_print']           = 1;
$invoice_templates['invoice_print_basic']     = 1;
$invoice_templates['invoice_task_list']       = 1;
$invoice_templates['credit_note_pdf']         = 1;
$invoice_templates['invoice_email_due']       = 1;
$invoice_templates['invoice_email_overdue']   = 1;
$invoice_templates['invoice_email_paid']      = 1;
$invoice_templates['credit_note_email']       = 1;;
foreach ( $templates as $template ) {
	if ( stripos( $template['template_key'], 'invoice' ) ) {
		$invoice_templates[ $template['template_key'] ] = 1;
	}
}
foreach ( $invoice_templates as $template_key => $tf ) {
	module_template::link_open_popup( $template_key );
}
$template_html = ob_get_clean();

$payment_methods_options = array();
$payment_methods         = handle_hook( 'get_payment_methods', $module );
foreach ( $payment_methods as $payment_method ) {
	if ( $payment_method->is_enabled() ) {
		$payment_methods_options[ $payment_method->module_name ] = $payment_method->get_payment_method_name();
	}
}


$settings = array(
	array(
		'key'         => 'overdue_email_auto',
		'default'     => '0',
		'type'        => 'checkbox',
		'description' => 'Automatic Overdue Emails',
		'help'        => 'If this is ticked then by default newly created invoices will be sent automatic overdue notices. This can be disabled/enabled per invoice. See the "Auto Overdue Email" option near "Due Date".',
	),
	array(
		'key'         => 'invoice_automatic_receipt',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Automatic Send Invoice Receipt',
		'help'        => 'Automatically send the invoice receipt to the customer once the invoice is marked as paid. If this is disabled you will have to go into the invoice and manually send it after payment is received.',
	),
	array(
		'key'         => 'invoice_template_print_default',
		'default'     => 'invoice_print',
		'type'        => 'text',
		'description' => 'Default PDF invoice template',
		'help'        => 'Used for invoice PDF. You can overwrite in the Advanced settings of each invoice.',
	),
	array(
		'key'         => 'overdue_email_auto_days',
		'default'     => '3',
		'type'        => 'text',
		'description' => 'Automically send after',
		'help'        => 'How many days after the invoice is overdue is the automated email sent (set to 0 will send on the date the invoice is due)',
	),
	array(
		'key'         => 'overdue_email_auto_days_repeat',
		'default'     => '7',
		'type'        => 'text',
		'description' => 'Automically re-send every',
		'help'        => 'How many days after the last automatic overdue reminder is the overdue reminder re-sent automatically (set to 0 to disable this option)',
	),
	array(
		'key'         => 'invoice_automatic_after_time',
		'default'     => '7',
		'type'        => 'text',
		'description' => 'Hour of day to perform automatic operations',
		'help'        => 'Enter the hour of day (eg: 7 for 7am, 14 for 2pm) to perform automatic actions - such as renewing invoices, subscriptions, overdue notices, etc...',
	),
	array(
		'key'         => 'invoice_auto_renew_only_paid_invoices',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Only renew paid invoices',
		'help'        => 'If an invoice (or past subscription invoice) has not been paid then do not renew the next one until original payment has been received.',
	),
	array(
		'key'         => 'invoice_default_payment_method',
		'default'     => 'paymethod_paypal',
		'type'        => 'select',
		'options'     => $payment_methods_options,
		'description' => 'Default Payment Method',
	),
	array(
		'key'         => 'invoice_due_days',
		'default'     => '30',
		'type'        => 'text',
		'description' => 'Invoice Due Days',
		'help'        => 'The number of days used to calculate the "Due Date" on new invoices. Due Date can be overridden per invoice.'
	),
	array(
		'key'         => 'invoice_name_match_job',
		'default'     => '0',
		'type'        => 'checkbox',
		'description' => 'Match Invoice with Job Name',
		'help'        => 'If an invoice is created from a Job, set the Invoice name the same as the job name',
	),
	array(
		'key'         => 'invoice_incrementing',
		'default'     => '0',
		'type'        => 'checkbox',
		'description' => 'Incrementing Invoice Numbers',
		'help'        => 'If this is enabled the system will pick a new invoice number each time. Choose what number to start from below.',
	),
	array(
		'key'         => 'invoice_incrementing_next',
		'default'     => '1',
		'type'        => 'text',
		'description' => 'Incrementing Invoice Number',
		'help'        => 'What will be the next invoice number',
	),
	array(
		'key'         => 'invoice_task_list_show_date',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Show Dates on Invoice Items',
	),
	array(
		'key'         => 'invoice_task_numbers',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Show Task Numbers on Invoice Items',
	),
	array(
		'key'         => 'invoice_allow_payment_amount_adjustment',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Allow User To Enter Payment Amount',
		'help'        => 'If this is enabled the user can change the payment amount on invoices. For example, they might want to pay $50 of a $100 invoice with PayPal, and $50 with cash.',
	),
	array(
		'type'        => 'html',
		'description' => 'Templates',
		'html'        => $template_html,
	),
);


module_config::print_settings_form(
	array(
		'heading'  => array(
			'title' => 'Invoice Settings',
			'type'  => 'h2',
			'main'  => true,
		),
		'settings' => $settings,
	)
);

// find any blank invoices.
$sql            = "SELECT * FROM `" . _DB_PREFIX . "invoice` WHERE customer_id IS NULL AND `name` = '' AND `status` = '' AND `date_create` = '0000-00-00' AND `date_sent` = '0000-00-00' AND `date_paid` = '0000-00-00' AND `date_due` = '0000-00-00' AND c_total_amount = 0 ";
$invoices       = qa( $sql );
$blank_invoices = array();
foreach ( $invoices as $invoice ) {
	$items = module_invoice::get_invoice_items( $invoice['invoice_id'] );
	if ( empty( $items ) ) {
		$blank_invoices[] = $invoice;
	}
}
if ( count( $blank_invoices ) && isset( $_POST['remove_duplicates'] ) && $_POST['remove_duplicates'] == 'yes' ) {
	foreach ( $blank_invoices as $id => $blank_invoice ) {
		module_invoice::delete_invoice( $blank_invoice['invoice_id'] );
		unset( $blank_invoices[ $id ] );
	}
}
if ( count( $blank_invoices ) ) {
	?>
	<h2>Blank invoices found</h2>
	We found the following <?php echo count( $blank_invoices ); ?> blank invoices that were created from a recent "Subscription" bug:
	<ul>
		<?php foreach ( $blank_invoices as $blank_invoice ) { ?>
			<li><?php echo module_invoice::link_open( $blank_invoice['invoice_id'], true ); ?> created
				on <?php echo print_date( $blank_invoice['date_created'] ); ?></li>
		<?php } ?>
	</ul>
	You can remove all these invoices manually, or click the button below to remove them automatically.
	<form action="" method="post">
		<input type="hidden" name="remove_duplicates" value="yes">
		<input type="submit" value="Remove these <?php echo count( $blank_invoices ); ?> invoices">
	</form>
	<?php
}