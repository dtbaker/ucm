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
		// save these settings to the db.
		$config_file = file_get_contents( _UCM_FOLDER . 'includes/config.php' );
		$config_file = preg_replace( "#define\('_DB_SERVER','[^']*'\)#", "define('_DB_SERVER','$db_host')", $config_file );
		$config_file = preg_replace( "#define\('_DB_NAME','[^']*'\)#", "define('_DB_NAME','$db_name')", $config_file );
		$config_file = preg_replace( "#define\('_DB_USER','[^']*'\)#", "define('_DB_USER','$db_user')", $config_file );
		$config_file = preg_replace( "#define\('_DB_PASS','[^']*'\)#", "define('_DB_PASS','$db_pass')", $config_file );
		$config_file = preg_replace( "#define\('_DB_PREFIX','[^']*'\)#", "define('_DB_PREFIX','$db_prefix')", $config_file );
		if ( file_put_contents( _UCM_FOLDER . 'includes/config.php', $config_file ) ) {
			redirect_browser( '?m=setup&step=2' );
		}
	}
}
?>


<?php print_heading( 'Step #1: Database Connection Details' ); ?>

<p>
	<em>Please contact your hosting provider for assistance with creating a database.</em>
</p>

<form action="?m=setup&amp;step=<?php echo $step; ?>&amp;save=true" method="post">
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