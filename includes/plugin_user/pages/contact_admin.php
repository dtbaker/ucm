<?php


if ( isset( $_REQUEST['user_id'] ) ) {


	$user_safe = true;
	include( module_theme::include_ucm( "includes/plugin_user/pages/contact_admin_edit.php" ) );
	//include("contact_admin_edit.php");

} else {

	include( module_theme::include_ucm( "includes/plugin_user/pages/contact_admin_list.php" ) );
	//include("contact_admin_list.php");

} 

