<?php


if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$square_webhook_url   = module_paymethod_square::webhook_url();
$square_configured    = module_paymethod_square::is_configured();
$square_signature_key = trim( (string) module_config::c( 'payment_method_square_signature_key', '' ) );

if ( module_paymethod_square::is_enabled() && ! $square_configured ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'Square is switched on but not set up yet.' ); ?></strong>
			<?php _e( 'It needs an access token and a location ID before it can take a payment. The steps are below.' ); ?>
		</p>
	</div>
<?php } elseif ( $square_configured && $square_signature_key === '' ) { ?>
	<div class="alert alert-warning" role="alert">
		<p>
			<strong><?php _e( 'Square notifications are not set up yet.' ); ?></strong>
			<?php _e( 'Payments are still recorded when the payer lands back on your site, so nothing is broken - but if someone closes their browser at the wrong moment, nothing tells your site the money arrived. Add the notification URL below and paste its signature key in.' ); ?>
		</p>
	</div>
<?php }

print_heading( 'Square Settings' ); ?>


<?php module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_square_enabled',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Enable Square Checkout',
		),
		array(
			'key'         => 'payment_method_square_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.',
		),
		array(
			'key'         => 'payment_method_square_label',
			'default'     => 'Square',
			'type'        => 'text',
			'description' => 'Payment Method Label',
			'help'        => 'This will display on invoices as the name of this payment method.',
		),
		array(
			'key'         => 'payment_method_square_sandbox',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Use Square Sandbox Mode (for testing payments)',
			'help'        => 'Sandbox and production have completely separate credentials. Switching this changes which Square server is used, so the token and location ID below have to match.',
		),
		array(
			'key'         => 'payment_method_square_access_token',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Square Access Token',
			'help'        => 'From developer.squareup.com, on your application\'s Credentials page. Treat it like a password: a Square access token has full access to your account.',
		),
		array(
			'key'         => 'payment_method_square_location_id',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Square Location ID',
			'help'        => 'From the Locations page of the same application. Payments are recorded against this location in Square.',
		),
		array(
			'key'         => 'payment_method_square_signature_key',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Your Square Webhook Signature Key',
			'help'        => 'Square shows this after you add the notification URL listed at the bottom of this page. It is what lets your site check that a notification really came from Square.',
		),
		array(
			'key'         => 'payment_method_square_currency',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Which Currencies To Support',
			'help'        => 'A comma separated list of currencies to support, eg: AUD,USD Leave this blank to support all currencies. If an invoice is in an unsupported currency then this payment method will not display.',
		),
		array(
			'key'         => 'payment_method_square_limit_type',
			'default'     => 'above',
			'type'        => 'select',
			'options'     => array(
				'above' => _l( 'Greater Than...' ),
				'below' => _l( 'Less Than...' ),
			),
			'description' => 'Only show when invoice value is ...',
			'help'        => 'Only show the Square option if the dollar value is greater than or less than the below value.',
		),
		array(
			'key'         => 'payment_method_square_limit_value',
			'default'     => '0',
			'type'        => 'text',
			'description' => '... this amount',
			'help'        => 'What value to restrict Square payments to',
		),
		'payment_method_square_charge_percent' => array(
			'key'         => 'payment_method_square_charge_percent',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as %)',
			'help'        => 'Example: 2.9 do not enter %% sign',
		),
		'payment_method_square_charge_amount' => array(
			'key'         => 'payment_method_square_charge_amount',
			'default'     => 0,
			'type'        => 'text',
			'description' => 'Additional Charge (as $)',
			'help'        => 'Example: 0.30 do not enter $ sign',
		),
		array(
			'key'         => 'payment_method_square_charge_description',
			'default'     => 'Square Fee',
			'type'        => 'text',
			'description' => 'Additional Charge (Description)',
			'help'        => 'This will show on the Invoice when paying via Square',
		),
	)
); ?>

<?php print_heading( 'Square setup instructions:' ); ?>

<ol>
	<li>Sign in at <a href="https://developer.squareup.com/apps" target="_blank" rel="noopener">developer.squareup.com/apps</a>
		with your Square account, and create an application.
	</li>
	<li>On the application's <strong>Credentials</strong> page, choose <strong>Sandbox</strong> to test or
		<strong>Production</strong> to take real money, and copy the <strong>Access token</strong> into the field
		above. The two modes have entirely separate tokens.
	</li>
	<li>On the <strong>Locations</strong> page of the same application, copy a <strong>Location ID</strong> into the
		field above.
	</li>
	<li>Tick <strong>Use Square Sandbox Mode</strong> above while you are testing with a sandbox token, and untick
		it when you paste the production one in.
	</li>
	<li>On the <strong>Webhooks</strong> page, add a subscription and paste this address into it,
		<strong>exactly</strong> as it appears here:
		<br><code><?php echo htmlspecialchars( $square_webhook_url ); ?></code>
	</li>
	<li>Subscribe it to <code>payment.created</code> and <code>payment.updated</code>, then copy the
		<strong>Signature key</strong> Square shows into the field above.
	</li>
	<li>With sandbox credentials in place, pay a test invoice with card <code>4111 1111 1111 1111</code>, any
		future expiry and any CVV. For a card that asks for a 3-D Secure challenge, use
		<code>4310 0000 0020 1019</code> and the verification code <code>123456</code>. To see a decline, use
		<code>4811 1100 0000 0008</code>.
	</li>
</ol>

<div class="alert alert-info" role="alert">
	<p>
		<strong><?php _e( 'The notification URL has to match character for character.' ); ?></strong>
		<?php _e( 'Square signs the address you typed into its console, joined to the message body, so a trailing slash, or http where this page says https, makes every notification fail to verify - and the error looks exactly like a wrong signature key. Copy the address above rather than typing it.' ); ?>
	</p>
</div>

<p>Square processes card payments in Australia, Canada, France, Ireland, Japan, Spain, the United Kingdom and the
	United States. Elsewhere, use Stripe or PayPal.</p>
