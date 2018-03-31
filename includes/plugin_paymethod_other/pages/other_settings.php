<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'Settings' );
module_config::print_settings_form(
	array(
		array(
			'key'         => 'payment_method_other_enabled',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Enable Payment Method',
		),
		array(
			'key'         => 'payment_method_other_enabled_default',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Available By Default On Invoices',
			'help'        => 'If this option is enabled, all new invoices will have this payment method available. If this option is disabled, it will have to be enabled on individual invoices.'
		),
		array(
			'key'         => 'payment_method_other_label',
			'default'     => 'Other',
			'type'        => 'text',
			'description' => 'Name this payment method',
		),
	)
);

print_heading( 'Templates' );
echo module_template::link_open_popup( 'paymethod_other' );
echo module_template::link_open_popup( 'paymethod_other_details' );
?>
