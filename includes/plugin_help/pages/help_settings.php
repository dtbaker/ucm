<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'Help Settings' );

module_config::print_settings_form(
	array(
		array(
			'key'         => 'help_only_for_admin',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Only show help menu for Super Administrator.',
			'help'        => 'By default only the Super Administrator (first user created) can see the help documentation. If this option is disabled you will still need to give each User Role access to "view help" for them to see the "help" menu correctly. Please note that the help documentation may contain branding.'
		),
	)
);
