<?php


if ( _UCM_INSTALLED && ! module_security::is_logged_in() && ! module_config::c( 'cron_last_run', 0 ) && ! module_config::c( 'initial_setup_complete', 0 ) ) {
	module_config::save_config( 'initial_setup_complete', 1 );
	$_REQUEST['auto_login'] = module_security::get_auto_login_string( 1 );
	if ( ! module_security::auto_login( false ) ) {
		echo 'Failed to login automatically...';
	}
}

if ( _UCM_INSTALLED && ! module_security::is_logged_in() ) {
	ob_end_clean();
	echo 'Something went wrong. Please login and go to Settings > Upgrade. <a href="' . _BASE_HREF . '">Click here to login</a>.';
	exit;
}

print_heading( 'Step #3: Initial system update' );

if (
	isset( $_REQUEST['run_upgrade'] ) ||
	(
		isset( $_REQUEST['install_upgrade'] ) &&
		isset( $_REQUEST['save_license_codes'] ) &&
		isset( $_REQUEST['license_codes'] ) &&
		trim( $_REQUEST['license_codes'][0] )
	)
) {
	$setup_upgrade_hack = true;
	include( 'includes/plugin_config/pages/config_upgrade.php' );

} else {
	?>

	<p>
		This will automatically upgrade you to the latest version of Ultimate Client Manager. <br>
		To proceed you will need to enter your <strong>license purchase code</strong> below.<br/>
		This is available in the "license" file in your downloads page on CodeCanyon.net (<a
			href="http://dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click here
			for help</a>). <br>
		The license code will look something like this: 30d91230-a8df-4545-1237-467abcd5b920
	</p>

	<h3>Please enter your license purchase code:</h3>
	<div style="padding:10px;">
		<form action="" method="post">

			<input type="hidden" name="install_upgrade" value="true">
			<input type="hidden" name="save_license_codes" value="true">
			<input type="text" name="license_codes[0]" value="<?php echo module_config::c( '_installation_code', '' ); ?>"
			       style="width:400px; padding:5px; border:1px solid #CCC;">

			<input type="submit" name="go" value="<?php _e( 'Check for Updates' ); ?>" class="submit_button btn btn-success"
			       onclick="this.value='Checking... this may take a few minutes.'">

		</form>
	</div>

<?php } ?>