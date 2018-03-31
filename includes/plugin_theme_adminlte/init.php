<?php

if ( ! isset( $_REQUEST['display_mode'] ) || ( isset( $_REQUEST['display_mode'] ) && $_REQUEST['display_mode'] != 'iframe' && $_REQUEST['display_mode'] != 'ajax' ) ) {
	$_REQUEST['display_mode'] = 'adminlte';
}
require_once( module_theme::include_ucm( 'includes/plugin_theme_adminlte/functions.php' ) );


// theme overrides and styles:
module_config::register_css( 'theme', 'AdminLTE.css', full_link( '/includes/plugin_theme_adminlte/css/AdminLTE.css' ), 12 );
if ( isset( $_SERVER['REQUEST_URI'] ) && ( strpos( $_SERVER['REQUEST_URI'], _EXTERNAL_TUNNEL ) || strpos( $_SERVER['REQUEST_URI'], _EXTERNAL_TUNNEL_REWRITE ) ) ) {
	module_config::register_css( 'theme', 'external.css', full_link( '/includes/plugin_theme_adminlte/css/external.css' ), 100 );
}
module_config::register_js( 'theme', 'app.js', full_link( '/includes/plugin_theme_adminlte/js/AdminLTE/app.js' ) );
module_config::register_js( 'theme', 'adminlte.js', full_link( '/includes/plugin_theme_adminlte/js/adminlte.js' ) );

function adminlte_dashboard_widgets() {
	$widgets = array();

	// the 4 column widget areas:
	foreach ( glob( dirname( __FILE__ ) . '/dashboard_widgets/widget_*.php' ) as $dashboard_widget_file ) {
		// echo $dashboard_widget_file;
		include( $dashboard_widget_file );
	}

	return $widgets;
} // end hook function
hook_add( 'dashboard_widgets', 'adminlte_dashboard_widgets' );