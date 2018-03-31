<?php


if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'PayPal Settings' ); ?>


<?php module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_paypal_enabled',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Enable PayPal Checkout',
		),
		array(
			'key'         => 'payment_method_paypal_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.'
		),
		array(
			'key'         => 'payment_method_paypal_label',
			'default'     => 'PayPal',
			'type'        => 'text',
			'description' => 'Payment Method Label',
			'help'        => 'This will display on invoices as the name of this payment method.'
		),
		array(
			'key'         => 'payment_method_paypal_email',
			'default'     => _ERROR_EMAIL,
			'type'        => 'text',
			'description' => 'Your PayPal registered email address',
		),
		array(
			'key'         => 'payment_method_paypal_sandbox',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Use PayPal Sandbox Mode (for testing payments)',
		),
		array(
			'key'         => 'payment_method_paypal_subscriptions',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Enable PayPal recurring payments',
			'help'        => 'Be sure to set the paypal IPN url to ' . full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ) . ' in your paypal account settings.',
		),
		array(
			'key'         => 'payment_method_paypal_currency',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Which Currencies To Support',
			'help'        => 'A comma separated list of currencies to support, eg: AUD,USD Leave this blank to support all currencies. If an invoice is in an unsupported currency then this payment method will not display.',
		),
		array(
			'key'         => 'payment_method_paypal_limit_type',
			'default'     => 'above',
			'type'        => 'select',
			'options'     => array(
				'above' => _l( 'Greater Than...' ),
				'below' => _l( 'Less Than...' ),
			),
			'description' => 'Only show when invoice value is ...',
			'help'        => 'Only show the paypal option if the dollar value is greater than or less than the below value.',
		),
		array(
			'key'         => 'payment_method_paypal_limit_value',
			'default'     => '0',
			'type'        => 'text',
			'description' => '... this amount',
			'help'        => 'What value to restrict paypal payments to',
		),
		'payment_method_paypal_charge_percent' => array(
			'key'         => 'payment_method_paypal_charge_percent',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as %)',
			'help'        => 'Example: 2.9 do not enter %% sign',
		),
		'payment_method_paypal_charge_amount'  => array(
			'key'         => 'payment_method_paypal_charge_amount',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as $)',
			'help'        => 'Example: 0.30 do not enter $ sign',
		),
		array(
			'key'         => 'invoice_fee_calculate_reverse',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Calculate as provider fee',
			'help'        => 'If this is enabled then it will work out the fee in reverse. Enable this option in order to receive the correct amount from PayPal if you have set 2.9%% and 0.30 above.',
		),
		array(
			'key'         => 'payment_method_paypal_charge_description',
			'default'     => 'PayPal Fee',
			'type'        => 'text',
			'description' => 'Additional Charge (Description)',
			'help'        => 'This will show on the Invoice when paying via PayPal',
		),
	)
); ?>

<?php print_heading( 'PayPal setup instructions:' ); ?>

	<p>Please signup for a PayPal business account here: http://www.paypal.com - please enter your paypal email address
		above.</p>

<?php if ( module_config::c( 'payment_method_paypal_subscriptions', 0 ) ) { ?>

	<?php print_heading( 'PayPal subscription setup instructions:' ); ?>

	<p>Please signup for a <strong>Business or Premier</strong> account as above. <strong>Important:</strong> for
		subscriptions to work correctly you need to go into your PayPal settings and add this URL as your IPN
		address: <?php echo full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ); ?> (<a
			href="https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/">instructions</a>) </p>
<?php } ?>