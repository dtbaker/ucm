<?php

$module->page_title = 'Admin';

if ( module_config::can_i( 'view', 'Settings' ) ) {
	$links = array(
		array(
			"name"                => "System Settings",
			'm'                   => 'config',
			'p'                   => 'config_basic_settings',
			'force_current_check' => true,
			//'default_page' => 'config_basic_settings',
			'order'               => 1, // at start.
			'menu_include_parent' => 0,
			'allow_nesting'       => 1,
		),
		array(
			"name"                => "Menu",
			'm'                   => 'config',
			'p'                   => 'config_menu',
			'force_current_check' => true,
			'order'               => 9994,
			'menu_include_parent' => 0,
			'allow_nesting'       => 1,
		),
		array(
			"name"                => "Payments",
			'm'                   => 'config',
			'p'                   => 'config_payment',
			'force_current_check' => true,
			'order'               => 9995,
			'menu_include_parent' => 0,
			'allow_nesting'       => 1,
		),
		array(
			"name"                => "Advanced",
			'm'                   => 'config',
			'p'                   => 'config_settings',
			'force_current_check' => true,
			//'default_page' => 'config_settings',
			'order'               => 9999, // at end.
			'menu_include_parent' => 0,
			'allow_nesting'       => 1,
		),
	);
}

if ( module_config::can_i( 'view', 'Upgrade System' ) && ! defined( '_UCM_HIDE_UPGRADE' ) ) {
	$links[] = array(
		"name"                => "Upgrade",
		'm'                   => 'config',
		'p'                   => 'config_upgrade',
		'force_current_check' => true,
		'order'               => 9998, // at end.
		'menu_include_parent' => 0,
		'allow_nesting'       => 1,
	);
}

?>