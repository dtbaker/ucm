<?php


$settings = array(
	array(
		'key'         => 'enable_customer_maps',
		'default'     => '1',
		'type'        => 'checkbox',
		'description' => 'Enable Customer Maps',
	),
	array(
		'key'         => 'google_maps_api_key',
		'default'     => 'AIzaSyDFYt1ozmTn34lp96W0AakC-tSJVzEdXjk',
		'type'        => 'text',
		'description' => 'Google Maps API Key',
		'help'        => 'This is required to get markers displaying on the map. If markers are not displaying please sign up for your own Google Maps/Geocoding API key and put it here.'
	),
);
module_config::print_settings_form(
	array(
		'heading'  => array(
			'title' => 'Map Settings',
			'type'  => 'h2',
			'main'  => true,
		),
		'settings' => $settings,
	)
);
