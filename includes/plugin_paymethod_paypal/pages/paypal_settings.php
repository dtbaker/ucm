<?php


if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$paypal_orders_v2 = module_paymethod_paypal::use_orders_v2();
$paypal_webhook_url = module_paymethod_paypal::webhook_url();
$paypal_webhook_id = trim( (string) module_config::c( 'payment_method_paypal_webhook_id', '' ) );

if ( ! $paypal_orders_v2 ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'PayPal payments are still going through Website Payments Standard.' ); ?></strong>
			<?php _e( 'PayPal has deprecated it and says it should not be used for new integrations. It still works, and nothing here has changed, but moving off it needs three values that cannot be worked out from your email address. The steps are at the bottom of this page and take about five minutes.' ); ?>
		</p>
	</div>
<?php } elseif ( $paypal_webhook_id === '' ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'PayPal webhooks are not set up yet.' ); ?></strong>
			<?php _e( 'Payments are still recorded when the payer lands back on your site, so nothing is broken - but if someone closes their browser at the wrong moment, nothing tells your site the money arrived. The steps are at the bottom of this page.' ); ?>
		</p>
	</div>
<?php }

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
			'key'         => 'payment_method_paypal_client_id',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your PayPal REST Client ID',
			'help'        => 'From an app created at developer.paypal.com. Entering a client ID and secret moves this site onto PayPal\'s current Orders API. Leave both blank to carry on exactly as before.',
		),
		array(
			'key'         => 'payment_method_paypal_secret',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your PayPal REST Secret',
			'help'        => 'From the same app as the client ID. Treat it like a password.',
		),
		array(
			'key'         => 'payment_method_paypal_webhook_id',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your PayPal Webhook ID',
			'help'        => 'PayPal shows this after you add the webhook address listed at the bottom of this page. It is what lets your site check that a notification really came from PayPal.',
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
			'description' => 'Let PayPal bill subscriptions automatically',
			'help'        => 'When an invoice came from a subscription, the payer is offered automatic payments as well as a one-off payment, and PayPal keeps the schedule. This uses PayPal subscription buttons and IPN rather than the current PayPal integration - see the notes below. Leave it off and subscriptions still work: this site generates and emails each renewal invoice itself. Be sure to set the PayPal IPN url to ' . full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ) . ' in your PayPal account settings.',
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

	<p>Sign up for a PayPal business account at <a href="https://www.paypal.com" target="_blank" rel="noopener">paypal.com</a>
		and enter your PayPal email address above. That is all Website Payments Standard needs, and it is how this
		site has always worked.</p>

<?php print_heading( 'Moving to the current PayPal integration:' ); ?>

	<p>PayPal has deprecated Website Payments Standard. Moving off it needs a REST app, which takes about five
		minutes and changes nothing about your PayPal account or your money.</p>

	<ol>
		<li>Sign in at <a href="https://developer.paypal.com/dashboard/" target="_blank" rel="noopener">developer.paypal.com/dashboard</a>
			with your PayPal business account.
		</li>
		<li>Open <strong>Apps &amp; Credentials</strong>. Switch to <strong>Sandbox</strong> to test or
			<strong>Live</strong> to take real money - the two have separate credentials and you will want both,
			in turn.
		</li>
		<li>Select <strong>Create App</strong>, give it any name, and choose <strong>Merchant</strong>.</li>
		<li>Copy the <strong>Client ID</strong> and the <strong>Secret</strong> into the two fields above.</li>
		<li>On the same app page, scroll to <strong>Webhooks</strong> and select <strong>Add Webhook</strong>. Paste
			this address into it:
			<br><code><?php echo htmlspecialchars( $paypal_webhook_url ); ?></code>
		</li>
		<li>Subscribe that webhook to <strong>Payment capture completed</strong>
			(<code>PAYMENT.CAPTURE.COMPLETED</code>). Save, then copy the <strong>Webhook ID</strong> PayPal shows
			into the third field above.
		</li>
		<li>Tick <strong>Use PayPal Sandbox Mode</strong> above while you are testing with sandbox credentials, and
			untick it when you paste the live ones in.
		</li>
	</ol>

	<p>While the client ID and secret are blank, nothing changes: payments carry on through Website Payments
		Standard exactly as before. Entering them switches this site to PayPal's current Orders API, where the
		payment is confirmed by your own server rather than by a message PayPal posts back.</p>

<?php if ( module_config::c( 'payment_method_paypal_subscriptions', 0 ) ) { ?>

	<?php print_heading( 'PayPal subscription setup instructions:' ); ?>

	<p>Please signup for a <strong>Business or Premier</strong> account as above. <strong>Important:</strong> for
		subscriptions to work correctly you need to go into your PayPal settings and add this URL as your IPN
		address: <?php echo full_link( _EXTERNAL_TUNNEL . '?m=paymethod_paypal&h=ipn&method=paypal' ); ?> (<a
			href="https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/">instructions</a>) </p>

	<div class="alert alert-info" role="alert">
		<p><strong>Recurring payments do not go through the current PayPal integration.</strong> One-off payments
			use the Orders API and the webhook you set up above. Automatic payments are different: they use
			PayPal's subscription buttons and come back to this site by IPN, which is why the IPN address above
			still matters even once you have entered a client ID, a secret and a webhook ID. It is the older
			integration, PayPal still runs it, and it is left alone deliberately - rewriting working recurring
			billing without a sandbox account to prove it against is not a trade worth making.</p>
	</div>

	<p><strong>PayPal bills on one unit of time.</strong> A subscription set to every N days up to ninety, every
		N months up to twenty four, or every N years up to five can be handed to PayPal exactly. A subscription
		that mixes units - say one month and fifteen days - or one past those limits cannot, so the payer is not
		offered automatic payments for it and pays each invoice as it arrives. Nothing is rounded: this site used
		to send PayPal a month as thirty days and a year as three hundred and sixty five, and PayPal's schedule
		then drifted away from this site's own a little more every cycle until the two disagreed about which
		period had been paid for.</p>

	<p>Leaving this switched off is a complete, working setup: the scheduled job generates and emails each
		renewal invoice and the customer pays it like any other. That is how the Square payment method works and
		it needs no IPN at all.</p>
<?php } ?>