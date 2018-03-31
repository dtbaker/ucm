<?php

require_once( 'includes/plugin_paymethod_coinbase/coinbase-php/lib/coinbase.php' );

$coinbase = array(
	"secret_key"      => module_config::c( 'payment_method_coinbase_api_key' ),
	"publishable_key" => module_config::c( 'payment_method_coinbase_secret_key' )
);

coinbase::setApiKey( $coinbase['secret_key'] );
?>