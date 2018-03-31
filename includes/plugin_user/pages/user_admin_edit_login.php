<?php


$fieldset_data = array(
	'title'    => _l( 'User Security' ),
	'class'    => 'tableclass tableclass_form tableclass_full',
	'elements' => array()
);

if ( module_user::can_i( 'edit', 'Users Permissions', 'Config' ) ) {
	if ( $user_id == 1 ) {
		$fieldset_data['elements']['role'] = array(
			'title'  => _l( 'User Role' ),
			'fields' => array(
				_l( 'All Permissions' ),
			),
		);
	} else {
		$user_roles       = isset( $user['roles'] ) && is_array( $user['roles'] ) ? $user['roles'] : array();
		$roles            = module_security::get_roles();
		$roles_attributes = array();
		foreach ( $roles as $role ) {
			$roles_attributes[ $role['security_role_id'] ] = $role['name'];
		}
		$current_role = current( $user_roles );

		$fieldset_data['elements']['role'] = array(
			'title'  => _l( 'User Role' ),
			'fields' => array(
				array(
					'type'    => 'select',
					'name'    => 'role_id',
					'value'   => isset( $current_role['security_role_id'] ) ? $current_role['security_role_id'] : false,
					'options' => $roles_attributes,
				),
			)
		);
		if ( module_security::can_i( 'view', 'Security Roles', 'Security' ) ) {
			$fieldset_data['elements']['role']['fields'][] = ' <a href="' . module_security::link_open_role( $current_role['security_role_id'] ) . '">edit</a> ';
		}
		$fieldset_data['elements']['role']['fields'][] = _hr( 'You can setup a list of permissions to re-use over and over again under Settings > Roles. This will control what parts of the application this user can access (if any). ' );
	}
}
$fieldset_data['elements']['username'] = array(
	'title'  => _l( 'Username' ),
	'fields' => array(
		_l( '(same as email address)' ),
	),
);
?>
	<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
	<input style="display:none" type="text" name="fakeusernameremembered"/>
	<input style="display:none" type="password" name="fakepasswordremembered"/>
<?php
if ( $user_id == module_security::get_loggedin_id() || module_user::can_i( 'edit', 'Users Passwords', 'Config' ) ) {
	// do we allow this user to create a password ? or do they have to enter their old password first to change it.
	if ( ! $user['password'] || module_user::can_i( 'create', 'Users Passwords', 'Config' ) || ( isset( $_REQUEST['reset_password'] ) && $_REQUEST['reset_password'] == module_security::get_auto_login_string( $user['user_id'] ) ) ) {
		$fieldset_data['elements']['password'] = array(
			'title'  => _l( 'Set Password' ),
			'fields' => array(
				array(
					'type'         => 'password',
					'name'         => 'password_new',
					'autocomplete' => 'off',
					'value'        => '',
					'class'        => 'no_permissions',
					'help'         => 'Giving this user a password and login permissions will let them gain access to this system. Depending on the permissions you give them will decide what parts of the system they can access.',
				)
			),
		);
	} else {
		ob_start();
		?>
		<table width="100%">
			<tr>
				<td><?php _e( 'Old Password' ); ?></td>
				<td>
					<input type="password" name="password_old" value=""/>
					<?php _h( 'Please enter your old password in order to set a new password.' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'New Password' ); ?></td>
				<td>
					<input type="password" name="password_new1" value=""/>
					<?php _h( 'Type in your new desired password here.' ); ?>
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Verify Password' ); ?></td>
				<td>
					<input type="password" name="password_new2" value=""/>
					<?php _h( 'Please confirm your new password here a second time.' ); ?>
				</td>
			</tr>
		</table>
		<?php
		$fieldset_data['elements']['password'] = array(
			'title'  => _l( 'Change Password' ),
			'fields' => array(
				ob_get_clean()
			),
		);
	}
}
if (
	(
		( module_user::can_i( 'view', 'Users Passwords', 'Config' ) && $user_id == module_security::get_loggedin_id() )
		||
		module_user::can_i( 'edit', 'Users Passwords', 'Config' )
	)
	&& (int) $user_id > 0 && $user_id != "new"
) {
	$fieldset_data['elements']['auto'] = array(
		'title'  => _l( 'Auto Login Link' ),
		'fields' => array(
			'<a href="' . module_security::generate_auto_login_link( $user_id ) . '">' . _l( 'right click - copy link' ) . '</a> ',
			_hr( 'If you give this link to a user (or bookmark it for yourself) then it will log in automatically. To re-set an auto-login link simply change your password to something new.' )
		),
	);
}
if ( ! module_security::can_user_login( $user_id ) ) {
	$fieldset_data['elements']['warninglogin'] = array(
		'warning' => _l( '(note: this user does not have login permissions yet - login will not work)' )
	);
}
echo module_form::generate_fieldset( $fieldset_data );


// todo - hook in here for a user configuration area
// modules can load configuration variables in here.
// hmm, is this the same as user roles. i guess it is, we'll just use user roles for now.

?>

<?php
/*if(module_user::can_i('edit','Fine Tune Permissions','Config')){

		$user_permissions = module_security::get_user_permissions($user_id);
		if($user_id && !$current_role){
				// do they have any permissions at all?
				if(!$user_permissions){
						$current_role = true; // hack to hide permissions area on new blank users (ie: contacts).
				}
		}
		?>
		<div id="permissions_editor" style="<?php echo (!(int)$user_id || $current_role) ? 'display:none;' : '';?>">

				<h3>
<?php echo _l('Fine Tune Permissions');?>
				</h3>

				<?php if($user_id==1){

						echo _l('User ID #1 has all permissions. This stops you accidently locking yourself out of the application. Please create a new user to assign permissions to.');
				}else{ ?>

				<p><?php _e('We recommend you use roles for permissions. It will make things easier! But if you want to fine tune permissions on a per user basis, you can do so below. This will not affect the role, it will only apply to this user.');?></p>

<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full">
<thead>
<tr>
	<th><?php _e('Category');?></th>
	<th><?php _e('Sub Category');?></th>
	<!-- <th>Access</th> -->
	<?php  foreach(module_security::$available_permissions as $permission){ ?>
	<th><?php echo _l(ucwords(str_replace('_',' ',$permission)));?></th>
	<?php } ?>
</tr>
</thead>
<tbody><?php
								$available_permissions = module_security::get_permissions();
								$x=0;
								$last_category=false;
								$drop_down_permissions = array();
								$checkbox_permissions = array();
								foreach($available_permissions as $available_permission){
										// start hack for special case drop down options:
										if($available_permission['description']=='drop_down'){
												$drop_down_permissions[] = $available_permission;
												continue;
										}
										// start hack for special case drop down options:
										if($available_permission['description']=='checkbox'){
												$checkbox_permissions[] = $available_permission;
												continue;
										}
										$available_perms = @unserialize($available_permission['available_perms']);
										if(!$last_category || $last_category != $available_permission['category']){
												$x++;
										}
										?>
								<tr class="<?php echo $x%2?'odd':'even';?>">
										<?php if(!$last_category || $last_category != $available_permission['category']){ ?>
										<td>
												<?php echo $available_permission['category']; ?>
												&nbsp;
										</td>
										<?php }else{ ?>
										<td align="right">
												&nbsp;
										</td>
										<?php } ?>
										<td>
?>
												&raquo;
												<?php echo $available_permission['name']; ?>
										</td>
										<?php foreach(module_security::$available_permissions as $permission){ ?>
										<td align="center">
												<?php if(isset($available_perms[$permission])){ ?>
												<input type="checkbox" name="permission[<?php echo $available_permission['security_permission_id'];?>][<?php echo $permission;?>]" value="1"<?php
														//if(isset($security_role['permissions']) && isset($security_role['permissions'][$available_permission['security_permission_id']]) && $security_role['permissions'][$available_permission['security_permission_id']][$permission]){
														//    echo ' checked';
														//}
														if(isset($user_permissions[$available_permission['security_permission_id']]) && $user_permissions[$available_permission['security_permission_id']][$permission]){
																echo ' checked';
														}
														?>>
												<?php } ?>
										</td>
										<?php } ?>
								</tr>
										<?php
										$last_category = $available_permission['category'];
								} ?>
</tbody>
</table>


				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full">
						<thead>
						<tr>
								<th>User Permission</th>
								<th>Option</th>
						</tr>
						</thead>
						<tbody>
								<?php
								$drop_down_permissions_grouped = array();
								foreach($drop_down_permissions as $available_permission){
										if(!isset($drop_down_permissions_grouped[$available_permission['category']])){
												$drop_down_permissions_grouped[$available_permission['category']] = array();
										}
										$drop_down_permissions_grouped[$available_permission['category']] [] = $available_permission;
								}
								$permission = 'view';
								foreach($drop_down_permissions_grouped as $category_name => $available_permissions){
										?>
								<tr class="<?php echo $x++%2?'odd':'even';?>">
										<td>
												<?php echo $category_name; ?>
												&nbsp;
										</td>
										<td>
												<select name="permission_drop_down[<?php foreach($available_permissions as $available_permission){ echo $available_permission['security_permission_id'].'|'; } ?>]">
														<option value=""><?php _e('N/A');?></option>
														<?php foreach($available_permissions as $available_permission){ ?>
														<option value="<?php echo $available_permission['security_permission_id'];?>"<?php

																if(isset($user_permissions[$available_permission['security_permission_id']]) && $user_permissions[$available_permission['security_permission_id']][$permission]){
																		echo ' selected';
																}
																?>><?php echo $available_permission['name'];?></option>
														<?php } ?>
												</select>
										</td>
								</tr>
										<?php
								} ?>
								<?php
								$permission = 'view';
								foreach($checkbox_permissions as $available_permission){
										$available_perms = @unserialize($available_permission['available_perms']);
										?>
								<tr class="<?php echo $x++%2?'odd':'even';?>">
										<td>
												<?php echo $available_permission['name']; ?>
										</td>
										<td>
												<?php if(isset($available_perms[$permission])){ ?>
												<input type="checkbox" name="permission[<?php echo $available_permission['security_permission_id'];?>][<?php echo $permission;?>]" value="1"<?php
														if(isset($user_permissions[$available_permission['security_permission_id']]) && $user_permissions[$available_permission['security_permission_id']][$permission]){
																echo ' checked';
														}
														?>>
												<?php } ?>
										</td>
								</tr>
										<?php
								} ?>
						</tbody>
				</table>


<?php }  ?>

				</div>

<?php } */ ?>