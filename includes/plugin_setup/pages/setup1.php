<?php


if ( _UCM_INSTALLED && ! module_security::is_logged_in() ) {
	ob_end_clean();
	echo 'Sorry the system is already installed. You need to be logged in to run the setup again.';
	exit;
}

$db_host   = _DB_SERVER;
$db_prefix = _DB_PREFIX;
$db_name   = _DB_NAME;
$db_user   = _DB_USER;
$db_pass   = _DB_PASS;
if ( isset( $_REQUEST['save'] ) ) {
	// check the database details.
	$db_host   = $_REQUEST['db_host'];
	$db_name   = $_REQUEST['db_name'];
	$db_user   = $_REQUEST['db_user'];
	$db_pass   = $_REQUEST['db_pass'];
	$db_prefix = $_REQUEST['db_prefix'];
	$link      = mysqli_connect( $db_host, $db_user, $db_pass, $db_name );
	if ( ! $link || mysqli_connect_errno() ) {
		set_error( "Sorry there was an error connecting to the mysql database. Please ensure the details you entered are correct and try again.\n The error was: <strong>" . mysqli_connect_error() . "</strong> \nPlease contact your hosting provider if you need assistance with database connection details." );
		print_header_message();
	} else {
		// db worked! woop woop
		// Write credentials to includes/config.local.php, which config.php loads before
		// its own defaults.
		//
		// This used to preg_replace the define() lines inside includes/config.php itself.
		// That broke silently when config.php was reformatted: the pattern stopped
		// matching, file_put_contents still returned a truthy byte count for the unchanged
		// file, so setup advanced to step 2 looking healthy while the credentials were
		// never saved. Writing a separate file avoids depending on config.php's formatting,
		// and keeps the user's database password out of a tracked, shippable file.
		$config_local = defined( '_UCM_CONFIG_LOCAL' ) ? _UCM_CONFIG_LOCAL : _UCM_FOLDER . 'includes/config.local.php';
		$existing     = is_file( $config_local ) ? file_get_contents( $config_local ) : '';

		$generated = "<?php\n"
		             . "/**\n"
		             . " * Local configuration for UCM. Written by the installer.\n"
		             . " * This file is loaded before the defaults in includes/config.php.\n"
		             . " * Keep it out of version control - it holds your database password.\n"
		             . " */\n\n"
		             . "define( '_DB_SERVER', " . var_export( $db_host, true ) . " );\n"
		             . "define( '_DB_NAME', " . var_export( $db_name, true ) . " );\n"
		             . "define( '_DB_USER', " . var_export( $db_user, true ) . " );\n"
		             . "define( '_DB_PASS', " . var_export( $db_pass, true ) . " );\n"
		             . "define( '_DB_PREFIX', " . var_export( $db_prefix, true ) . " );\n";

		// A per-install secret, so capability tokens are not derived from the install path.
		// Preserve any existing value - regenerating it would invalidate every token and
		// link already issued by this install.
		if ( preg_match( "#define\(\s*'_UCM_SECRET'\s*,\s*'([^']+)'\s*\)#", $existing, $secret_match ) ) {
			$ucm_secret = $secret_match[1];
		} else {
			$ucm_secret = bin2hex( random_bytes( 32 ) );
		}
		$generated .= "define( '_UCM_SECRET', " . var_export( $ucm_secret, true ) . " );\n";

		if ( file_put_contents( $config_local, $generated ) === false ) {
			set_error( 'Could not write to ' . h( $config_local ) . '. Please make the includes/ folder writable and try again.' );
			print_header_message();
		} else {
			redirect_browser( '?m=setup&step=2' );
		}
	}
}
?>


<?php print_heading( 'Step #1: Database Connection Details' ); ?>

<p>
	<em>Please contact your hosting provider for assistance with creating a database.</em>
</p>

<?php
// No token on the setup wizard. Minting one writes a secure_hash row through
// module_config::save_config(), and this step is where the database credentials are
// still being entered - the config table does not exist yet, so asking for a token
// here replaces the form with "Table 'ucm_config' doesn't exist". There is nothing to
// protect either: the installer refuses to run once _UCM_INSTALLED is set unless you
// are logged in (see the guard at the top of each step).
module_form::print_form_open_tag( array(
	'action' => '?m=setup&step=' . $step . '&save=true',
	'auth'   => false,
) );
?>
	<?php
	module_form::set_required( array(
		'fields' => array(
			'db_host' => 'Database Host',
			'db_name' => 'Database Name',
			'db_user' => 'Database Username',
			'db_pass' => 'Database Password',
		)
	) );
	?>

	<table class="tableclass tableclass_form">
		<tbody>
		<tr>
			<th class="width2"><?php echo _l( 'Database Host:' ); ?></th>
			<td>
				<input type="text" name="db_host" value="<?php echo h( $db_host ); ?>">
			</td>
		</tr>
		<tr>
			<th><?php echo _l( 'Database Prefix:' ); ?></th>
			<td>
				<input type="text" name="db_prefix" value="<?php echo h( $db_prefix ); ?>">
			</td>
		</tr>
		<tr>
			<th><?php echo _l( 'Database Name:' ); ?></th>
			<td>
				<input type="text" name="db_name" value="<?php echo h( $db_name ); ?>">
			</td>
		</tr>
		<tr>
			<th><?php echo _l( 'Database Username:' ); ?></th>
			<td>
				<input type="text" name="db_user" value="<?php echo h( $db_user ); ?>">
			</td>
		</tr>
		<tr>
			<th><?php echo _l( 'Database Password:' ); ?></th>
			<td>
				<input type="text" name="db_pass" value="<?php echo h( $db_pass ); ?>">
			</td>
		</tr>
		</tbody>
	</table>
	<input type="submit" name="next" value="Next Step &raquo;" class="submit_button btn btn-success">

</form>