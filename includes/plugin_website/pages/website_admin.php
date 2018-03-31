<?php

if ( isset( $_REQUEST['website_id'] ) ) {

	include( module_theme::include_ucm( "includes/plugin_website/pages/website_admin_edit.php" ) );

} else {

	include( module_theme::include_ucm( "includes/plugin_website/pages/website_admin_list.php" ) );

} 

