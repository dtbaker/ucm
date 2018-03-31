<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'Check Settings' );
module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_check_enabled',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Enable Payment Method',
		),
		array(
			'key'         => 'payment_method_check_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.'
		),
		array(
			'key'         => 'payment_method_check_label',
			'default'     => 'Check',
			'type'        => 'text',
			'description' => 'Name this payment method',
		),
	)
);

print_heading( 'Check Templates' );
echo module_template::link_open_popup( 'paymethod_check' );
echo module_template::link_open_popup( 'paymethod_check_details' );
?>
