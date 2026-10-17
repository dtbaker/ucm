<?php


if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

// Authorize.Net is the one method an upgrade switches off by itself, so an install that
// had it on gets told so in those words rather than being left to wonder.
$authorize_was_enabled = module_config::c( 'payment_method_authorize_was_enabled', false );

module_invoice::print_deprecated_payment_method_notice(
	module_paymethod_authorize::get_payment_method_name(),
	_l( 'It collects raw card numbers on your own server, which puts you in PCI DSS SAQ D - the most demanding level of PCI compliance, and not one a self-hosted install can realistically meet.' ),
	$authorize_was_enabled
		? '<strong>' . _l( 'Upgrading turned this payment method off.' ) . '</strong> ' . _l( 'An earlier version let a payer choose the amount their own card was charged, so an invoice could be marked paid in full for a cent. That hole is fixed, but the method is being retired rather than modernised, so it was switched off rather than left on. Every setting below is exactly as you left it, and you can turn it back on if you need to.' )
		: ''
);

print_heading( 'Authorize Settings' ); ?>


<?php module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_authorize_enabled',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Enable Authorize Checkout',
		),
		array(
			'key'         => 'payment_method_authorize_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.'
		),
		array(
			'key'         => 'payment_method_authorize_sandbox',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Enable Sandbox Mode',
		),
		array(
			'key'         => 'payment_method_authorize_api_login_id',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Authorize API Login ID',
		),
		array(
			'key'         => 'payment_method_authorize_transaction_key',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Authorize Transaction Key',
		),
		'payment_method_authorize_charge_percent' => array(
			'key'         => 'payment_method_authorize_charge_percent',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as %)',
			'help'        => 'Example: 2.9 do not enter %% sign',
		),
		'payment_method_authorize_charge_amount'  => array(
			'key'         => 'payment_method_authorize_charge_amount',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as $)',
			'help'        => 'Example: 0.30 do not enter $ sign',
		),
		array(
			'key'         => 'payment_method_authorize_charge_description',
			'default'     => 'Authorize Fee',
			'type'        => 'text',
			'description' => 'Additional Charge (Description)',
			'help'        => 'This will show on the Invoice when paying via authorize',
		),
	)
); ?>

<?php print_heading( 'Authorize setup instructions:' ); ?>

<p>Authorize.net only supports payments in USD. Please make sure all your invoices are in USD to use Authorize.net</p>
<p>Please signup for a Authorize account here: https://authorize.net - please enter your authorize API Keys above.</p>
<p>For testing select "Visa" and enter the test credit card number 4111111111111111, any expiration date (MMYY) in the
	future (such as "1120"), and hit "Submit".</p>
<p><strong>Please use SSL on your website (eg: https://) in order to use Authorize.net</strong></p>
<p>To change how the Authorize credit card form looks please go to Settings > Templates and look for
	'authorize_credit_card_form'.</p>
<p>If you get "cannot connect" errors when making a payment, your server cannot verify Authorize.net's
	certificate. Ask your host to update the certificate authority bundle PHP uses. This page used to tell you
	to turn 'payment_method_authorize_ssl_verify' off instead; that setting no longer disables the check,
	because turning it off sent the card number over a connection nothing had checked.</p>