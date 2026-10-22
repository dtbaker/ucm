<?php

// Steps 1, 3, 4 and 5 all refuse to run on an installed site unless someone is logged in.
// This one did not, and index.php lets the setup module through without a login, so
// anyone could ask an installed site for ?m=setup&step=2 and have it run every plugin's
// install and upgrade SQL again.
// On an installed site the wizard is for whoever may change the settings, not for any login.
if ( _UCM_INSTALLED && ( ! module_security::is_logged_in() || ! module_config::can_i( 'edit', 'Settings' ) ) ) {
	ob_end_clean();
	echo 'Sorry the system is already installed. You need to be logged in as someone who can change the settings to run the setup again.';
	exit;
}

print_heading( 'Step #2: Database Installation' );

// check for existing database tables.
// upgrade if neccessary.
// check with each plugin to get a list of SQL to install / upgrade.

//$current_db_version = _UCM_VERSION;

// check if db is installed
//$sql = "SHOW TABLES LIKE '"._DB_PREFIX."config'";
//$res = qa1($sql);
/*
if(count($res)){
    // something is installed, find out what version.
    $sql = "SELECT * FROM `"._DB_PREFIX."config` WHERE `key` = 'db_version'";
    $res = qa1($sql);
    if(count($res)){
        // found a version.
        $current_db_version = $res['val'];
    }
    $do_upgrade = true;
}else{
    $do_upgrade = false;
}*/

// echo 'Setup plugins';
// print_r(array_keys($plugins));
// exit;

// start running all the hooks to install plugins.
$fail         = false;
$set_versions = array();
foreach ( $plugins as $plugin_name => &$p ) {
	echo "Installing <span style='text-decoration:underline;'>$plugin_name</span> plugin version " . $p->get_plugin_version() . ".... ";
	if ( $version = $p->install_upgrade() ) { //$do_upgrade,$current_db_version
		echo '<span class="success_text">success</span>';
		$set_versions[ $plugin_name ] = $version;
	} else {
		$fail = true;
		echo '<span class="error_text">fail</span> ';
	}
	echo '<br>';
}
// all done?

if ( isset( $set_versions['config'] ) ) {
	// config db worked.
	foreach ( $plugins as $plugin_name => &$p ) {
		if ( isset( $set_versions[ $plugin_name ] ) ) {
			$p->init();
			$p->set_installed_plugin_version( $set_versions[ $plugin_name ] );
		}
	}
}


if ( $fail ) {
	print_header_message();
	?>
	<br/>
	Some things failed. Would you like to retry? <br/>
	<a href="?m=setup&amp;step=2" class="uibutton">Retry</a>
	<?php
} else {
	?>

	<p>Database Installation Success!</p>

	<p><a href="?m=setup&amp;step=3" class="submit_button btn btn-success">Continue to Step 3 &raquo;</a></p>

	<?php
}

?>