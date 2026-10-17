<?php

if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

print_heading( 'Help Settings' );

module_config::print_settings_form(
	array(
		array(
			'key'         => 'help_only_for_admin',
			'default'     => 0,
			'type'        => 'checkbox',
			'description' => 'Only show help menu for Super Administrator.',
			'help'        => 'Tick this to hide the documentation from everyone except the Super Administrator (the first user created). Left unticked, anyone whose User Role has "view help" can read the documentation for the screen they are on.'
		),
		array(
			'key'         => 'help_allow_remote',
			'default'     => 1,
			'type'        => 'checkbox',
			'description' => 'Fetch the latest documentation from ultimateclientmanager.com.',
			'help'        => 'A copy of the documentation is included in this download and is used first, so help works with no internet connection at all. With this ticked, opening a help article also checks the documentation site for a newer version and keeps it for a week. The request is made by this server, not by your browser, and it sends only three things: which article, which version of this software, and a language code. It does not send your licence key, your web address, your name, or anything about your customers. Untick it on a closed network and this installation will never contact anything.'
		),
	)
);
