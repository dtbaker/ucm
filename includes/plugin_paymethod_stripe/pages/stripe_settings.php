<?php


if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$stripe_checkout        = module_paymethod_stripe::use_checkout_sessions();
$stripe_have_sdk        = module_paymethod_stripe::have_sdk();
$stripe_webhook_url     = module_paymethod_stripe::webhook_url();
$stripe_webhook_secret  = trim( (string) module_config::c( 'payment_method_stripe_webhook_secret', '' ) );
$stripe_method_enabled  = module_paymethod_stripe::is_enabled();
$stripe_subscriptions   = module_config::c( 'payment_method_stripe_subscriptions', 0 );

if ( ! $stripe_have_sdk ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'The Stripe library is not installed, so payments are still going through the old Stripe Checkout.' ); ?></strong>
			<?php _e( 'Stripe does not accept that flow from accounts opened recently, and it cannot answer a 3-D Secure challenge, so cards issued in the EEA and the UK will decline. Re-upload the files for this release, including the vendor folder, or run composer install.' ); ?>
		</p>
	</div>
<?php } elseif ( ! $stripe_checkout ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'This site is deliberately set back to the old Stripe Checkout.' ); ?></strong>
			<?php _e( 'Turn off the "Use the old Stripe Checkout" advanced setting to take payments through Stripe Checkout again. The old flow is not accepted from accounts opened recently and declines EEA and UK cards.' ); ?>
		</p>
	</div>
<?php } elseif ( $stripe_method_enabled && $stripe_webhook_secret === '' ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'Stripe webhooks are running without signature checking.' ); ?></strong>
			<?php _e( 'Payments are still recorded, so nothing is broken - but until a signing secret is set, a webhook delivery cannot be checked for replay. Add the endpoint below in your Stripe dashboard, then paste its signing secret into the field on this page.' ); ?>
		</p>
	</div>
<?php }

// Separate from the chain above rather than another branch of it: when subscriptions are
// switched on and cannot run, the reason is always one of the two notices above, and an
// admin needs to be told both things.
if ( $stripe_subscriptions && ! module_paymethod_stripe::subscriptions_available() ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'Stripe is not billing subscriptions on this site, whatever the setting below says.' ); ?></strong>
			<?php _e( 'Automatic payments need the Stripe library and Stripe Checkout, and one of those is missing - see the notice above. Subscription invoices are still generated and emailed by the scheduled job and the customer pays each one, so nothing is lost; it is just not automatic.' ); ?>
		</p>
	</div>
<?php }

print_heading( 'Stripe Settings' ); ?>


<?php module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_stripe_enabled',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Enable Stripe Checkout',
		),
		array(
			'key'         => 'payment_method_stripe_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.'
		),
		array(
			'key'         => 'payment_method_stripe_label',
			'default'     => 'Stripe',
			'type'        => 'text',
			'description' => 'Payment Method Label',
			'help'        => 'This will display on invoices as the name of this payment method.'
		),
		array(
			'key'         => 'payment_method_stripe_secret_key',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Stripe Secret Key (Test or Live)',
		),
		array(
			'key'         => 'payment_method_stripe_publishable_key',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Stripe Publishable Key (Test or Live)',
		),
		array(
			'key'         => 'payment_method_stripe_webhook_secret',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Stripe Webhook Signing Secret',
			'help'        => 'Starts with whsec_. Found in your Stripe dashboard under Developers, Webhooks, after you add the endpoint shown at the bottom of this page. Payments are still recorded without it, but Stripe deliveries cannot be checked for replay until it is set.',
		),
		array(
			'key'         => 'payment_method_stripe_subscriptions',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Let Stripe bill subscriptions automatically',
			'help'        => 'When an invoice came from a subscription, the payer is offered automatic payments as well as a one-off payment, and Stripe keeps the schedule and charges the saved card. Leave this off and subscriptions still work - this site generates and emails each renewal invoice itself, and the customer pays each one. Needs the webhook below.',
		),
		array(
			'key'         => 'payment_method_stripe_currency',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Which Currencies To Support',
			'help'        => 'A comma separated list of currencies to support, eg: AUD,USD Leave this blank to support all currencies. If an invoice is in an unsupported currency then this payment method will not display.',
		),
		array(
			'key'         => 'payment_method_stripe_limit_type',
			'default'     => 'above',
			'type'        => 'select',
			'options'     => array(
				'above' => _l( 'Greater Than...' ),
				'below' => _l( 'Less Than...' ),
			),
			'description' => 'Only show when invoice value is ...',
			'help'        => 'Only show the stripe option if the dollar value is greater than or less than the below value.',
		),
		array(
			'key'         => 'payment_method_stripe_limit_value',
			'default'     => '0',
			'type'        => 'text',
			'description' => '... this amount',
			'help'        => 'What value to restrict stripe payments to',
		),
		'payment_method_stripe_charge_percent' => array(
			'key'         => 'payment_method_stripe_charge_percent',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as %)',
			'help'        => 'Example: 1.5 do not enter %% sign',
		),
		'payment_method_stripe_charge_amount'  => array(
			'key'         => 'payment_method_stripe_charge_amount',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as $)',
			'help'        => 'Example: 1.5 do not enter $ sign',
		),
		array(
			'key'         => 'payment_method_stripe_charge_description',
			'default'     => 'Stripe Fee',
			'type'        => 'text',
			'description' => 'Additional Charge (Description)',
			'help'        => 'This will show on the Invoice when paying via stripe',
		),
	)
); ?>

<?php print_heading( 'Stripe setup instructions:' ); ?>

<ol>
	<li>Sign up at <a href="https://stripe.com" target="_blank" rel="noopener">stripe.com</a>.</li>
	<li>In your Stripe dashboard go to <strong>Developers &rarr; API keys</strong> and copy the
		<strong>Secret key</strong> into the field above. A key starting <code>sk_test_</code> puts this
		site in test mode; <code>sk_live_</code> takes real money.
	</li>
	<li>Go to <strong>Developers &rarr; Webhooks</strong>, add an endpoint, and paste this address into it:
		<br><code><?php echo htmlspecialchars( $stripe_webhook_url ); ?></code>
	</li>
	<li>Subscribe that endpoint to the <code>checkout.session.completed</code> event. If you are also letting
		Stripe bill subscriptions, add <code>invoice.payment_succeeded</code> - that is the one that arrives
		for every renewal after the first.
	</li>
	<li>Copy the endpoint's <strong>Signing secret</strong> (it starts <code>whsec_</code>) into the
		webhook field above.
	</li>
	<li>With test keys in place, pay a test invoice with card <code>4242 4242 4242 4242</code>, any future
		expiry and any CVC. For a card that asks for a 3-D Secure challenge, use
		<code>4000 0025 0000 3155</code>.
	</li>
</ol>

<p>
	Payments are recorded twice over on purpose: once when the payer lands back here, and once when
	Stripe's webhook arrives. Whichever gets there first wins and the other is ignored, so a site
	that Stripe cannot reach - behind a firewall, on a local network - still records its payments.
</p>

<?php print_heading( 'Stripe subscriptions:' ); ?>

<p>Subscriptions go through Stripe Checkout, the same as a one-off payment. The payer saves a card
	once and Stripe charges it on the schedule from then on; when a charge succeeds, this site
	generates the invoice for that period if the scheduled job has not already, and marks it paid.
	Renewals arrive on the <code>invoice.payment_succeeded</code> webhook, so without that webhook
	the first payment is recorded and no renewal ever is.</p>

<p><strong>Stripe bills on one unit of time.</strong> A subscription set to every N days, every N
	months up to twelve, or every year can be handed to Stripe exactly. A subscription that mixes
	units - say one month and fifteen days - or one longer than a year cannot, so the payer is not
	offered automatic payments for it and pays each invoice as it arrives. Nothing is rounded: an
	approximated schedule drifts away from this site's own a little more every cycle, and the two
	then disagree about which period has been paid for.</p>

<p>Automatic payments are only offered when the Stripe library is installed and this site is on
	Stripe Checkout. On the old Stripe Checkout setting, and on an install whose vendor folder has
	not arrived, the option is hidden rather than half-working.</p>

<p>Leaving this switched off is a complete, working setup: the scheduled job generates and emails
	each renewal invoice and the customer pays it like any other. That is how the Square payment
	method works and it needs no webhook at all.</p>

<p>Also please go to Settings > Update and click the Manual Update button, just to be sure!</p>
<p>As always, set the TEST API keys above first and do a test payment to ensure it all works correctly.</p>