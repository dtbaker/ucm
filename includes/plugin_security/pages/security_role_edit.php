<?php


if ( ! module_config::can_i( 'view', 'Settings' ) || ! module_security::can_i( 'view', 'Security Roles', 'Security' ) ) {
	redirect_browser( _BASE_HREF );
}
$security_role_id = $_REQUEST['security_role_id'];
$security_role    = array();
if ( $security_role_id && $security_role_id != 'new' ) {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'category'  => 'Security',
			'page_name' => 'Security Roles',
			'module'    => 'security',
			'feature'   => 'edit',
		) );
	}
	$security_role = module_security::get_security_role( $security_role_id );
	if ( ! $security_role ) {
		$security_role_id = 'new';
	}
}

if ( $security_role_id == 'new' || ! $security_role_id ) {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'category'  => 'Security',
			'page_name' => 'Security Roles',
			'module'    => 'security',
			'feature'   => 'create',
		) );
	}
	$security_role = array(
		'security_role_id' => 'new',
		'name'             => '',
	);
}

if ( module_security::can_i( 'edit', 'Security Roles', 'Security' ) && isset( $_REQUEST['delete_security_permission_id'] ) ) {
	$id = (int) $_REQUEST['delete_security_permission_id'];
	if ( $id > 0 ) {
		delete_from_db( 'security_permission', 'security_permission_id', $id );
		delete_from_db( 'security_role_perm', 'security_permission_id', $id );
	}
	redirect_browser( module_security::link_open_role( $security_role_id ) . '&advanced' );
}

if ( isset( $_REQUEST['export_json'] ) ) {
	$export_json = array();
}

?>


	<form action="" method="post">
		<input type="hidden" name="_process" value="save_security_role"/>
		<input type="hidden" name="security_role_id" value="<?php echo $security_role_id; ?>"/>

		<?php
		/** ROLE DETAILS */

		$fieldset_data                     = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Role Details',
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(),
		);
		$fieldset_data['elements']['name'] = array(
			'title'  => 'Name',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $security_role['name'],
				),
			)
		);
		if ( (int) $security_role_id > 0 ) {
			$fieldset_data['elements']['users'] = array(
				'title'  => 'Name',
				'fields' => array(
					function () use ( $security_role_id ) {
						$users    = module_user::get_users( array( 'security_role_id' => $security_role_id ) );
						$contacts = module_user::get_contacts( array( 'security_role_id' => $security_role_id ) );
						$url1     = module_user::link_open_contact( false );
						$url1     .= strpos( $url1, '?' ) ? '&' : '?';
						$url2     = module_user::link_open( false );
						$url2     .= strpos( $url1, '?' ) ? '&' : '?';
						_e( 'There are <a href="%s">%s customer contacts</a> and <a href="%s">%s system users</a> with this role.', $url1 . 'search[security_role_id]=' . (int) $security_role_id, count( $contacts ), $url2 . 'search[security_role_id]=' . (int) $security_role_id, count( $users ) );
					}
				)
			);
		}

		$fieldset_data['elements']['defaults'] = array(
			'title'  => 'Load Defaults',
			'fields' => array(
				array(
					'type'    => 'select',
					'name'    => 'load_defaults',
					'value'   => '',
					'options' => array(
						'{"Change Request|change_request|Change Requests|Permissions":["view"],"Customer|customer|Customers|Permissions":["view"],"Customer|user|Contacts|Permissions":["view","edit","create"],"Customer|customer|All Customer Contacts|Permissions":["view","edit"],"Invoice|invoice|Invoices|Permissions":["view"],"Job|job|Jobs|Permissions":["view"],"Job|job|Job Tasks|Permissions":["view"],"Ticket|ticket|Tickets|Permissions":["view","create"],"Website|website|Websites|Permissions":["view"],"Customer Data Access|config|Only customer I am assigned to as a contact|drop_down":["view"],"Job Data Access|config|Jobs from customers I have access to|drop_down":["view"],"Job Task Creation|config|Created tasks require admin approval|drop_down":["view"],"Ticket Access|config|Only tickets from my customer account|drop_down":["view"],"User Account Access|config|Only Contact Accounts|drop_down":["view"],"User Specific|config|Can User Login|checkbox":["view"]}' => _l( 'Customer View Only' ),
						'{"Company|company|Company|Permissions":["view"],"Customer|customer|Customers|Permissions":["view","edit","create"],"Customer|user|Contacts|Permissions":["view","edit","create"],"Customer|customer|All Customer Notes|Permissions":["view","edit","create"],"Customer|customer|All Customer Contacts|Permissions":["view"],"Customer|customer|Customer Groups|Permissions":["view","edit","create"],"File|file|Files|Permissions":["view","edit","create"],"File|file|File Comments|Permissions":["view","create"],"Invoice|invoice|Invoices|Permissions":["view","edit","create"],"Invoice|invoice|Invoice Notes|Permissions":["view","edit","create"],"Invoice|invoice|Invoice Payments|Permissions":["edit","create"],"Job|job|Jobs|Permissions":["view","edit","create"],"Job|job|Job Notes|Permissions":["view","edit","create"],"Job|job|Job Tasks|Permissions":["view","edit","create"],"Job|job|Job Groups|Permissions":["view","edit","create"],"Job|job|Job Advanced|Permissions":["view"],"Job Discussion|job_discussion|Job Discussions|Permissions":["view"],"Pin|pin|Header Pin|Permissions":["view","edit","create","delete"],"Ticket|ticket|Tickets|Permissions":["view","edit","create"],"User|user|User Notes|Permissions":["view","edit","create"],"Website|website|Websites|Permissions":["view","edit","create"],"Website|website|Website Notes|Permissions":["view","edit","create"],"Website|website|Website Groups|Permissions":["view","edit","create"],"Company Data Access|config|Only companies I am assigned to in staff area|drop_down":["view"],"Customer Data Access|config|Only customers from companies I have access to|drop_down":["view"],"Invoice Data Access|config|Invoices from Jobs I have access to|drop_down":["view"],"Job Data Access|config|Only jobs I am assigned to|drop_down":["view"],"Job Task Creation|config|Created tasks do not require approval|drop_down":["view"],"Job Task Data Access|config|All tasks within a job|drop_down":["view"],"Ticket Access|config|Only tickets from my customer account|drop_down":["view"],"User Account Access|config|Only My Account|drop_down":["view"],"User Specific|config|Can User Login|checkbox":["view"],"User Specific|config|Show Quick Search|checkbox":["view"],"User Specific|config|Show Dashboard Alerts|checkbox":["view"],"User Specific|config|Show Dashboard Todo List|checkbox":["view"],"User Specific|config|Receive File Upload Alerts|checkbox":["view"]}' => _l( 'Staff Member' ),
						'{"Company|company|Company|Permissions":["view"],"Customer|customer|Customers|Permissions":["view","edit","create"],"Customer|user|Contacts|Permissions":["view","edit","create"],"Customer|customer|All Customer Notes|Permissions":["view","edit","create"],"Customer|customer|All Customer Contacts|Permissions":["view","edit"],"Customer|customer|Customer Groups|Permissions":["view","edit","create"],"Customer|customer|All Lead Contacts|Permissions":["view","edit"],"Customer|customer|Lead Groups|Permissions":["view","edit","create"],"Customer|customer|All Lead Notes|Permissions":["view","edit","create"],"File|file|Files|Permissions":["view","edit","create"],"File|file|File Comments|Permissions":["view","create"],"Invoice|invoice|Invoices|Permissions":["view","edit","create"],"Invoice|invoice|Invoice Notes|Permissions":["view","edit","create"],"Invoice|invoice|Invoice Payments|Permissions":["edit","create"],"Job|job|Jobs|Permissions":["view","edit","create"],"Job|job|Job Notes|Permissions":["view","edit","create"],"Job|job|Job Tasks|Permissions":["view","edit","create"],"Job|job|Job Groups|Permissions":["view","edit","create"],"Job|job|Job Advanced|Permissions":["view"],"Job Discussion|job_discussion|Job Discussions|Permissions":["view"],"Pin|pin|Header Pin|Permissions":["view","edit","create","delete"],"Ticket|ticket|Tickets|Permissions":["view","edit","create"],"User|user|User Notes|Permissions":["view","edit","create"],"Vendor|user|Contacts|Permissions":["view","edit"],"Vendor|vendor|Vendors|Permissions":["view"],"Vendor|vendor|All Vendor Contacts|Permissions":["view"],"Website|website|Websites|Permissions":["view","edit","create"],"Website|website|Website Notes|Permissions":["view","edit","create"],"Website|website|Website Groups|Permissions":["view","edit","create"],"Calendar Data Access|config|Only from Customers or assigned items|drop_down":["view"],"Company Data Access|config|All companies in system|drop_down":["view"],"Customer Data Access|config|Only customers I am assigned to as a staff member|drop_down":["view"],"File Data Access|config|Only files from customers I have access to|drop_down":["view"],"Invoice Data Access|config|Invoices from customers I have access to|drop_down":["view"],"Job Data Access|config|Jobs from customers I have access to|drop_down":["view"],"Job Task Creation|config|Created tasks do not require approval|drop_down":["view"],"Job Task Data Access|config|All tasks within a job|drop_down":["view"],"Quote Data Access|config|Quotes from customers I have access to|drop_down":["view"],"Quote Task Creation|config|Created tasks do not require approval|drop_down":["view"],"Quote Task Data Access|config|All tasks within a quote|drop_down":["view"],"Ticket Access|config|Only tickets from my customer account|drop_down":["view"],"User Account Access|config|All Contact and User Accounts|drop_down":["view"],"Vendor Data Access|config|Only vendor I am assigned to as a contact|drop_down":["view"],"User Specific|config|Can User Login|checkbox":["view"],"User Specific|config|Show Quick Search|checkbox":["view"],"User Specific|config|Show Dashboard Alerts|checkbox":["view"],"User Specific|config|Show Dashboard Todo List|checkbox":["view"],"User Specific|config|Receive File Upload Alerts|checkbox":["view"],"User Specific|config|Receive File Comment Alerts|checkbox":["view"]}' => _l( 'Staff Member Improved' ),
						'{"Calendar|calendar|Calendar|Permissions":["view","edit","create"],"Company|company|Company|Permissions":["view"],"Config|user|Users|Permissions":["view","edit"],"Config|user|Users Passwords|Permissions":["view","edit","create"],"Config|user|Staff Settings|Permissions":["edit"],"Customer|user|Contacts|Permissions":["view","edit","create","delete"],"Customer|customer|Customers|Permissions":["view","edit","create","delete"],"Customer|customer|Leads|Permissions":["view","edit","create"],"Customer|customer|All Customer Notes|Permissions":["view","edit","create","delete"],"Customer|customer|All Customer Contacts|Permissions":["view","edit"],"Customer|customer|Customer Groups|Permissions":["view","edit","create","delete"],"Customer|customer|Export Customers|Permissions":["view"],"Customer|customer|Import Customers|Permissions":["view"],"Customer|customer|Customer Staff|Permissions":["edit"],"Customer|customer|Customer Credit|Permissions":["edit"],"Customer|customer|Export Leads|Permissions":["view"],"Customer|customer|Import Leads|Permissions":["view"],"Customer|customer|All Lead Contacts|Permissions":["view","edit"],"Customer|customer|Lead Groups|Permissions":["view","delete"],"Customer|customer|All Lead Notes|Permissions":["delete"],"File|file|Files|Permissions":["view","edit","create","delete"],"File|file|File Comments|Permissions":["view","create","delete"],"File|file|File Approval|Permissions":["edit"],"Finance|finance|Dashboard Finance Summary|Permissions":["view"],"Invoice|invoice|Invoices|Permissions":["view","edit","create","delete"],"Invoice|invoice|Invoice Notes|Permissions":["view","edit","create","delete"],"Invoice|invoice|Invoice Payments|Permissions":["edit","create"],"Invoice|invoice|Export Invoices|Permissions":["view"],"Job|job|Jobs|Permissions":["view","edit","create","delete"],"Job|job|Job Notes|Permissions":["view","edit","create","delete"],"Job|job|Job Tasks|Permissions":["view","edit","create"],"Job|job|Job Groups|Permissions":["view","edit","create","delete"],"Job|job|Job Advanced|Permissions":["view"],"Job|job|Export Job Tasks|Permissions":["view"],"Job|job|Import Job Tasks|Permissions":["view"],"Job|job|Export Jobs|Permissions":["view"],"Job|job|Import Jobs|Permissions":["view"],"Job Discussion|job_discussion|Job Discussions|Permissions":["view"],"User|user|User Notes|Permissions":["view","edit","create"],"Website|website|Websites|Permissions":["view","edit","create","delete"],"Website|website|Website Notes|Permissions":["view","edit","create","delete"],"Website|website|Website Groups|Permissions":["view","edit","create","delete"],"Website|website|Export Websites|Permissions":["view"],"Website|website|Import Websites|Permissions":["view"],"Calendar Data Access|config|Only from Customers or assigned items|drop_down":["view"],"Company Data Access|config|Only companies I am assigned to in staff area|drop_down":["view"],"Customer Data Access|config|Only customers from companies I have access to|drop_down":["view"],"File Data Access|config|Only files from customers I have access to|drop_down":["view"],"Invoice Data Access|config|Invoices from customers I have access to|drop_down":["view"],"Job Data Access|config|Jobs from customers I have access to|drop_down":["view"],"Job Task Creation|config|Created tasks do not require approval|drop_down":["view"],"Job Task Data Access|config|All tasks within a job|drop_down":["view"],"Quote Data Access|config|Quotes from customers I have access to|drop_down":["view"],"Quote Task Creation|config|Created tasks do not require approval|drop_down":["view"],"Quote Task Data Access|config|All tasks within a quote|drop_down":["view"],"Ticket Access|config|Only tickets from my customer account|drop_down":["view"],"User Account Access|config|Only Contact Accounts|drop_down":["view"],"Vendor Data Access|config|Only vendors from companies I have access to|drop_down":["view"],"User Specific|config|Show Quick Search|checkbox":["view"],"User Specific|config|Show Dashboard Alerts|checkbox":["view"],"User Specific|config|Show Dashboard Todo List|checkbox":["view"],"User Specific|config|Receive File Upload Alerts|checkbox":["view"],"User Specific|config|Can User Login|checkbox":["view"],"User Specific|config|Receive File Comment Alerts|checkbox":["view"]}' => _l( 'Reseller' ),
					),
					'help'    => 'This will override any options selected below and replace them with defaults. You can change the selected permissions once the defaults are loaded',
				),
			)
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );


		/** PERMIOSSIONS */
		ob_start();

		hook_handle_callback( 'layout_column_half', 1 );
		?>

		<table class="tableclass tableclass_rows tableclass_full">
			<thead>
			<tr>
				<th><?php _e( 'Category' ); ?></th>
				<th><?php _e( 'Permissions' ); ?></th>
				<?php foreach ( module_security::$available_permissions as $permission ) { ?>
					<th><?php echo ucwords( $permission ); ?></th>
				<?php } ?>
				<?php if ( isset( $_REQUEST['advanced'] ) ) { ?>
					<th>More</th>
				<?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php
			$available_permissions = module_security::get_permissions();
			$x                     = 0;
			$last_category         = false;
			$drop_down_permissions = array();
			$checkbox_permissions  = array();
			foreach ( $available_permissions as $available_permission ) {
				// start hack for special case drop down options:
				if ( $available_permission['description'] == 'drop_down' ) {
					$drop_down_permissions[] = $available_permission;
					continue;
				}
				// start hack for special case drop down options:
				if ( $available_permission['description'] == 'checkbox' ) {
					$checkbox_permissions[] = $available_permission;
					continue;
				}
				$available_perms = @unserialize( $available_permission['available_perms'] );
				if ( ! $last_category || $last_category != $available_permission['category'] ) {
					$x ++;
				}
				?>
				<tr class="<?php echo $x % 2 ? 'odd' : 'even'; ?>">
					<?php if ( ! $last_category || $last_category != $available_permission['category'] ) { ?>
						<td>
							<?php echo $available_permission['category']; ?>
							&nbsp;
						</td>
					<?php } else { ?>
						<td align="right">
							&nbsp;
						</td>
					<?php } ?>
					<td>
						<?php /*if(!$last_category || $last_category != $available_permission['category']){ ?>

                        <?php }else{ ?>
                        <?php } */ ?>
						&raquo;
						<?php echo $available_permission['name']; ?>
					</td>
					<?php foreach ( module_security::$available_permissions as $permission ) { ?>
						<td align="center">
							<?php if ( isset( $available_perms[ $permission ] ) ) { ?>
								<input type="checkbox"
								       name="permission[<?php echo $available_permission['security_permission_id']; ?>][<?php echo $permission; ?>]"
								       value="1"<?php
								if ( isset( $security_role['permissions'] ) && isset( $security_role['permissions'][ $available_permission['security_permission_id'] ] ) && $security_role['permissions'][ $available_permission['security_permission_id'] ][ $permission ] ) {
									echo ' checked';
									if ( isset( $_REQUEST['export_json'] ) ) {
										$export_json[ $available_permission['category'] . '|' . $available_permission['module'] . '|' . $available_permission['name'] . '|' . $available_permission['description'] ][] = $permission;
									}
								}
								?>>
							<?php } ?>
						</td>
					<?php } ?>
					<?php if ( isset( $_REQUEST['advanced'] ) ) { ?>
						<td>
							<?php echo $available_permission['security_permission_id'] . ' | ' . $available_permission['module']; ?>
							<a
								href="<?php echo htmlspecialchars( $_SERVER['REQUEST_URI'] ) . '&delete_security_permission_id=' . $available_permission['security_permission_id']; ?>">x</a>
						</td>
					<?php } ?>
				</tr>
				<?php
				$last_category = $available_permission['category'];
			} ?>
			</tbody>
		</table>
		<em>Items will appear above only after that permission is attemped to be accessed. Please create a new user, assign
			them to this role and login as that user to generate more available permissions above.</em>

		<?php

		hook_handle_callback( 'layout_column_half', 2 );

		?>

		<table class="tableclass tableclass_rows tableclass_full">
			<thead>
			<tr>
				<th><?php _e( 'User Permission' ); ?></th>
				<th><?php _e( 'Option' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$drop_down_permissions_grouped = array();
			foreach ( $drop_down_permissions as $available_permission ) {
				if ( ! isset( $drop_down_permissions_grouped[ $available_permission['category'] ] ) ) {
					$drop_down_permissions_grouped[ $available_permission['category'] ] = array();
				}
				$drop_down_permissions_grouped[ $available_permission['category'] ] [] = $available_permission;
			}
			$permission = 'view';
			foreach ( $drop_down_permissions_grouped as $category_name => $available_permissions ) {
				?>
				<tr class="<?php echo $x ++ % 2 ? 'odd' : 'even'; ?>">
					<td>
						<?php echo $category_name; ?>
						&nbsp;
					</td>
					<td>
						<select name="permission_drop_down[<?php foreach ( $available_permissions as $available_permission ) {
							echo $available_permission['security_permission_id'] . '|';
						} ?>]">
							<option value=""><?php _e( 'N/A' ); ?></option>
							<?php foreach ( $available_permissions as $available_permission ) { ?>
								<option value="<?php echo $available_permission['security_permission_id']; ?>"<?php
								if ( isset( $security_role['permissions'] ) && isset( $security_role['permissions'][ $available_permission['security_permission_id'] ] ) && $security_role['permissions'][ $available_permission['security_permission_id'] ][ $permission ] ) {
									echo ' selected';
									if ( isset( $_REQUEST['export_json'] ) ) {
										$export_json[ $available_permission['category'] . '|' . $available_permission['module'] . '|' . $available_permission['name'] . '|' . $available_permission['description'] ][] = $permission;
									}
								}
								?>><?php echo $available_permission['name']; ?></option>
							<?php } ?>
						</select>
					</td>
				</tr>
				<?php
			} ?>
			<?php
			$permission = 'view';
			foreach ( $checkbox_permissions as $available_permission ) {
				$available_perms = @unserialize( $available_permission['available_perms'] );
				?>
				<tr class="<?php echo $x ++ % 2 ? 'odd' : 'even'; ?>">
					<td>
						<?php echo $available_permission['name']; ?>
					</td>
					<td>
						<?php if ( isset( $available_perms[ $permission ] ) ) { ?>
							<input type="checkbox"
							       name="permission[<?php echo $available_permission['security_permission_id']; ?>][<?php echo $permission; ?>]"
							       value="1"<?php
							if ( isset( $security_role['permissions'] ) && isset( $security_role['permissions'][ $available_permission['security_permission_id'] ] ) && $security_role['permissions'][ $available_permission['security_permission_id'] ][ $permission ] ) {
								echo ' checked';
								if ( isset( $_REQUEST['export_json'] ) ) {
									$export_json[ $available_permission['category'] . '|' . $available_permission['module'] . '|' . $available_permission['name'] . '|' . $available_permission['description'] ][] = $permission;
								}
							}
							?>>
						<?php } ?>
					</td>
				</tr>
				<?php
			} ?>
			</tbody>
		</table>

		<p><em>Group Permissions:</em> Experimental: this is a new feature we are trying to allow permissions based on
			"Groups". Please set the "Data Access" dropdown above to "Only my groups" in order for these permissions to work.
			More groups (e.g. customers) will be added shortly after testing is complete on tickets.</p>
		<table class="tableclass tableclass_rows tableclass_full">
			<thead>
			<tr>
				<th><?php _e( 'Group Section' ); ?></th>
				<th><?php _e( 'Allow Access To:' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			// these are the groups in the system that have permissions coded for them already.
			// continue to add them here as time goes on.
			$groups         = array(
				'ticket',
			);
			$enabled_groups = module_security::get_role_groups( (int) $security_role_id );
			foreach ( $groups as $group ) {
				?>
				<tr>
					<td><?php echo ucwords( $group ); ?></td>
					<td>
						<?php
						$these_groups = module_group::get_groups( $group );
						foreach ( $these_groups as $this_group ) {
							?>
							<input type="checkbox" id="group_<?php echo $this_group['group_id']; ?>"
							       name="group_access[<?php echo $this_group['group_id']; ?>]"
							       value="1"<?php echo isset( $enabled_groups[ $this_group['group_id'] ] ) ? ' checked' : ''; ?>>
							<label
								for="group_<?php echo $this_group['group_id']; ?>"><?php echo htmlspecialchars( $this_group['name'] ); ?></label>
							<br/>
							<?php
						}
						?>
					</td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>

		<?php

		hook_handle_callback( 'layout_column_half', 'end' );

		$fieldset_data = array(
			'heading'         => array(
				'type'  => 'h3',
				'title' => 'Permissions',
			),
			'elements_before' => ob_get_clean(),
		);

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );


		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_save',
					'value' => _l( 'Save Role' ),
				),
				array(
					'ignore' => ! ( (int) $security_role_id > 0 && module_security::can_i( 'delete', 'Security Roles', 'Security' ) ),
					'type'   => 'delete_button',
					'name'   => 'butt_del',
					'value'  => _l( 'Delete' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $module->link_open_role( false ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
		?>


	</form>

<?php
if ( isset( $_REQUEST['export_json'] ) ) {
	echo '<pre>';
	print_r( $export_json );
	echo "\n\n";
	echo json_encode( $export_json );
	echo '</pre>';
}
?>