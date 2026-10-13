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
		Your system is installed and ready to use. This optional step checks for a newer
		version of Ultimate Client Manager. <br>
		If you bought this on CodeCanyon.net you can enter your <strong>license purchase
			code</strong> below - it is in the "license" file on your downloads page. <br>
		The license code will look something like this: 30d91230-a8df-4545-1237-467abcd5b920
	</p>

	<div style="padding:10px;">
		<?php module_form::print_form_open_tag( array( 'action' => '' ) ); ?>

			<input type="hidden" name="install_upgrade" value="true">
			<input type="hidden" name="save_license_codes" value="true">
			<input type="text" name="license_codes[0]" value="<?php echo module_config::c( '_installation_code', '' ); ?>"
			       style="width:400px; padding:5px; border:1px solid #CCC;">

			<input type="submit" name="go" value="<?php _e( 'Check for Updates' ); ?>" class="submit_button btn btn-success"
			       onclick="this.value='Checking... this may take a few minutes.'">

		</form>
	</div>

	<?php
	// There used to be no way past this step without a purchase code, which left a fresh
	// install stuck here. Checking for updates is optional, so let people carry on.
	?>
	<p align="center">
		<a href="?m=setup&amp;step=4" class="submit_button btn btn-default"><?php _e( 'Skip this step' ); ?> &raquo;</a>
	</p>

<?php } ?>