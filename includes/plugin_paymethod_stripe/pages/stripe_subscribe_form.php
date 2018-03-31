<?php

require_once( 'includes/plugin_paymethod_stripe/stripe-php/lib/Stripe.php' );

$stripe = array(
	"secret_key"      => module_config::c( 'payment_method_stripe_secret_key' ),
	"publishable_key" => module_config::c( 'payment_method_stripe_publishable_key' )
);

Stripe::setApiKey( $stripe['secret_key'] );
?>