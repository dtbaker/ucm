<?php

if ( ! module_customer::can_i( 'edit', 'Customer Settings', 'Config' ) ) {
	redirect_browser( _BASE_HREF );
}

$module->page_title = 'Customer Settings';

$links = array(
	array(
		"name"                => 'Settings',
		'm'                   => 'customer',
		'p'                   => 'customer_settings_basic',
		'force_current_check' => true,
		'order'               => 1, // at start.
		'menu_include_parent' => 1,
		'allow_nesting'       => 1,
		'args'                => array( 'customer_id' => false, 'customer_type_id' => false ),
	),
	array(
		"name"                => 'Customer Types',
		'm'                   => 'customer',
		'p'                   => 'customer_settings_types',
		'force_current_check' => true,
		'order'               => 2, // at start.
		'menu_include_parent' => 1,
		'allow_nesting'       => 1,
		'args'                => array( 'customer_id' => false, 'customer_type_id' => false ),
	),
);


if ( file_exists( dirname( __FILE__ ) . '/customer_signup.php' ) ) {
	$links[] = array(
		"name"                => 'Signup Settings',
		'm'                   => 'customer',
		'p'                   => 'customer_signup',
		'force_current_check' => true,
		'order'               => 3, // at start.
		'menu_include_parent' => 1,
		'allow_nesting'       => 1,
		'args'                => array( 'customer_id' => false ),
	);
}

