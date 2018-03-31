<?php

if ( ! module_security::has_feature_access( array(
	'name'        => 'Settings',
	'module'      => 'config',
	'category'    => 'Config',
	'view'        => 1,
	'description' => 'view',
) ) ) {
	die( 'no permissions' );
}

$ips = array();


hook_handle_callback( 'layout_column_half', 1 );
?>


	<h3><?php _e( 'Login History' ); ?></h3>

	<p>Below is a list of which users have logged into the system.</p>

	<table class="tableclass tableclass_rows tableclass_full">
		<thead>
		<tr>
			<th>
				Date/Time
			</th>
			<th>IP Address</th>
			<?php if ( module_config::c( 'session_show_hostname', 1 ) ) { ?>
				<th>Host</th>
			<?php } ?>
			<th>User</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$sql     = "SELECT * FROM `" . _DB_PREFIX . "security_login` l LEFT JOIN `" . _DB_PREFIX . "user` u ON l.user_id = u.user_id ORDER BY user_login_id DESC LIMIT 30";
		$history = qa( $sql );
		foreach ( $history as $h ) {
			if ( module_config::c( 'session_show_hostname', 1 ) && ! _DEMO_MODE ) {
				if ( ! isset( $ips[ $h['ip_address'] ] ) ) {
					$ips[ $h['ip_address'] ] = @gethostbyaddr( $h['ip_address'] );
				}
			}
			?>
			<tr>
				<td>
					<?php echo print_date( $h['time'], true ); ?>
				</td>
				<td>
					<?php echo _DEMO_MODE ? 'Hidden in demo ' : $h['ip_address']; ?>
				</td>
				<?php if ( module_config::c( 'session_show_hostname', 1 ) ) { ?>
					<td>
						<?php echo _DEMO_MODE ? 'Hidden in demo ' : $ips[ $h['ip_address'] ]; ?>
					</td>
				<?php } ?>
				<td>
					<?php echo module_user::link_open( $h['user_id'], true ); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

<?php
hook_handle_callback( 'layout_column_half', 2 ); ?>
<?php if ( class_exists( 'module_session', false ) ) { ?>
	<h3><?php _e( 'User Session History' ); ?></h3>

	<p>Below is a list of active (and inactive) user sessions. Here you can end users sessions.</p>

	<table class="tableclass tableclass_rows tableclass_full">
		<thead>
		<tr>
			<th>Last Activity</th>
			<th>Start Date/Time</th>
			<th>IP Address</th>
			<?php if ( module_config::c( 'session_show_hostname', 1 ) ) { ?>
				<th>Host</th>
			<?php } ?>
			<th>User</th>
			<th>Logged in</th>
			<th>Action</th>
		</tr>
		</thead>
		<tbody>
		<?php

		// bad hack, oh well, in a hurry
		if ( isset( $_REQUEST['end'] ) && strlen( $_REQUEST['end'] ) ) {
			$sql = "UPDATE `" . _DB_PREFIX . "session` SET logged_in = 0, `session_data` = '' WHERE session_id = '" . db_escape( $_REQUEST['end'] ) . "'";
			query( $sql );
		}

		$sql     = "SELECT * FROM `" . _DB_PREFIX . "session` s LEFT JOIN `" . _DB_PREFIX . "user` u ON s.user_id = u.user_id WHERE s.user_id > 0 ORDER BY last_access DESC LIMIT 30";
		$history = qa( $sql );
		foreach ( $history as $h ) {
			if ( module_config::c( 'session_show_hostname', 1 ) && ! _DEMO_MODE ) {
				if ( ! isset( $ips[ $h['ip_address'] ] ) ) {
					$ips[ $h['ip_address'] ] = @gethostbyaddr( $h['ip_address'] );
				}
			}
			?>
			<tr style="<?php echo $h['session_id'] == session_id() ? 'font-weight:bold; background:#EFEFEF' : ''; ?>">
				<td>
					<?php echo print_date( $h['last_access'], true ); ?>
				</td>
				<td>
					<?php echo print_date( $h['created'], true ); ?>
				</td>
				<td>
					<?php echo _DEMO_MODE ? 'Hidden in demo ' : $h['ip_address']; ?>
				</td>
				<?php if ( module_config::c( 'session_show_hostname', 1 ) ) { ?>
					<td>
						<?php echo _DEMO_MODE ? 'Hidden in demo ' : $ips[ $h['ip_address'] ]; ?>
					</td>
				<?php } ?>
				<td>
					<?php echo module_user::link_open( $h['user_id'], true ); ?>
				</td>
				<td>
					<?php echo $h['logged_in'] ? 'Yes' : 'No'; ?>
				</td>
				<td>
					<?php if ( $h['session_id'] == session_id() ) { ?>
						Current
					<?php } else if ( $h['logged_in'] ) { ?>
						<a href="<?php
						$url = preg_replace( '#[?&]end=.*#', '', $_SERVER['REQUEST_URI'] );
						$url .= strpos( $url, '?' ) === false ? '?' : '&';
						$url .= 'end=' . $h['session_id'];
						echo htmlspecialchars( $url ); ?>">Logout</a>
					<?php } else { ?>
						<!-- a delete button? -->
						Ended
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
<?php } ?>

<?php
hook_handle_callback( 'layout_column_half', 'end' );