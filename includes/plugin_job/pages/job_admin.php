<?php

$job_safe = true; // stop including files directly.
if ( ! module_job::can_i( 'view', 'Jobs' ) ) {
	echo 'permission denied';

	return;
}

if ( isset( $_REQUEST['job_id'] ) ) {

	if ( isset( $_REQUEST['email_staff'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_job/pages/job_admin_email_staff.php" ) );

	} else if ( isset( $_REQUEST['email'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_job/pages/job_admin_email.php" ) );

	} else if ( (int) $_REQUEST['job_id'] > 0 ) {
		include( module_theme::include_ucm( "includes/plugin_job/pages/job_admin_edit.php" ) );
		//include("job_admin_edit.php");
	} else {
		include( module_theme::include_ucm( "includes/plugin_job/pages/job_admin_create.php" ) );
		//include("job_admin_create.php");
	}

} else {

	include( module_theme::include_ucm( "includes/plugin_job/pages/job_admin_list.php" ) );
	//include("job_admin_list.php");

} 

