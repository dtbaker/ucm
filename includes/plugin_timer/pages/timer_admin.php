<?php

if ( isset( $_REQUEST['timer_id'] ) ) {

	include( module_theme::include_ucm( "includes/plugin_timer/pages/timer_admin_edit.php" ) );

} else {

	include( module_theme::include_ucm( "includes/plugin_timer/pages/timer_admin_list.php" ) );

}

