<?php

// The "Modern" theme: a plain CSS skin in the style of shadcn/ui. Bootstrap 3 and jQuery UI
// stay loaded (the app's JS needs them), so shadcn.css overrides them rather than removing them.

if ( ! isset( $_REQUEST['display_mode'] ) || ( $_REQUEST['display_mode'] != 'iframe' && $_REQUEST['display_mode'] != 'ajax' ) ) {
	$_REQUEST['display_mode'] = 'shadcn';
}
require_once( module_theme::include_ucm( 'includes/plugin_theme/themes/shadcn/shadcn_functions.php' ) );

module_config::register_css( 'theme', 'shadcn.css', full_link( '/includes/plugin_theme/themes/shadcn/css/shadcn.css' ), 18 );
module_config::register_js( 'theme', 'shadcn.js', full_link( '/includes/plugin_theme/themes/shadcn/js/shadcn.js' ), 18 );
