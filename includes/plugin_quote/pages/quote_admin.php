<?php

$quote_safe = true; // stop including files directly.
if ( ! module_quote::can_i( 'view', 'Quotes' ) ) {
	echo 'permission denied';

	return;
}

if ( isset( $_REQUEST['quote_id'] ) ) {

	if ( isset( $_REQUEST['email_staff'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_quote/pages/quote_admin_email_staff.php" ) );

	} else if ( isset( $_REQUEST['email'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_quote/pages/quote_admin_email.php" ) );

	} else if ( (int) $_REQUEST['quote_id'] > 0 ) {
		include( module_theme::include_ucm( "includes/plugin_quote/pages/quote_admin_edit.php" ) );
		//include("quote_admin_edit.php");
	} else {
		include( module_theme::include_ucm( "includes/plugin_quote/pages/quote_admin_create.php" ) );
		//include("quote_admin_create.php");
	}

} else {

	include( module_theme::include_ucm( "includes/plugin_quote/pages/quote_admin_list.php" ) );
	//include("quote_admin_list.php");

} 

