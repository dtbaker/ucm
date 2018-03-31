<?php

if ( ! isset( $_REQUEST['display_mode'] ) || ( isset( $_REQUEST['display_mode'] ) && $_REQUEST['display_mode'] != 'iframe' && $_REQUEST['display_mode'] != 'ajax' ) ) {
	$_REQUEST['display_mode'] = 'blocks';
}
require_once( module_theme::include_ucm( 'includes/plugin_theme_blocks/functions.php' ) );
require_once( module_theme::include_ucm( 'includes/plugin_theme_blocks/header_widgets.php' ) );

// theme styles and overrides
module_config::register_css( 'theme', 'style.css', full_link( '/includes/plugin_theme_blocks/css/style.css' ), 14 );
module_config::register_css( 'theme', 'overrides.css', full_link( '/includes/plugin_theme_blocks/css/overrides.css' ), 15 );

if ( isset( $_SERVER['REQUEST_URI'] ) && ( strpos( $_SERVER['REQUEST_URI'], _EXTERNAL_TUNNEL ) || strpos( $_SERVER['REQUEST_URI'], _EXTERNAL_TUNNEL_REWRITE ) ) ) {
	module_config::register_css( 'theme', 'external.css', full_link( '/includes/plugin_theme_blocks/css/external.css' ), 100 );
}
module_config::register_js( 'theme', 'app.js', full_link( '/includes/plugin_theme_blocks/js/app.js' ) );
module_config::register_js( 'theme', 'blocks.js', full_link( '/includes/plugin_theme_blocks/js/blocks.js' ) );

function blocks_dashboard_widgets() {
	$widgets = array();

	// the 4 column widget areas:
	foreach ( glob( dirname( __FILE__ ) . '/dashboard_widgets/widget_*.php' ) as $dashboard_widget_file ) {
		// echo $dashboard_widget_file;
		include( $dashboard_widget_file );
	}

	return $widgets;
} // end hook function
hook_add( 'dashboard_widgets', 'blocks_dashboard_widgets' );

