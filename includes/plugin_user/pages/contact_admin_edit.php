<?php

if ( ! $user_safe ) {
	die( 'fail' );
}

$module->page_title = _l( 'Contact' );

$user_id = (int) $_REQUEST['user_id'];
$user    = module_user::get_user( $user_id, true, false );
if ( ! $user || $user['user_id'] != $user_id ) {
	$user_id = 0;
}

$use_master_key = module_user::get_contact_master_key();
if ( ! $use_master_key ) {
	throw new Exception( 'Sorry no Customer or Vendor selected' );
}


switch ( $use_master_key ) {
	case 'customer_id':
		$contact_type            = 'Customer';
		$contact_type_permission = 'Customer';
		$contact_module_name     = 'customer';

		$current_customer_type_id = module_customer::get_current_customer_type_id();
		if ( $current_customer_type_id > 0 ) {
			$customer_type = module_customer::get_customer_type( $current_customer_type_id );
			if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
				$contact_type_permission = $customer_type['type_name'];
			}
		}
		break;
	case 'vendor_id':
		$contact_type            = 'Vendor';
		$contact_type_permission = 'Vendor';
		$contact_module_name     = 'vendor';
		break;
	default:
		die( 'Unsupported type' );
}
if ( ( ! $user || $user['user_id'] != $user_id ) && $user_id > 0 ) {
	// bad url. hack attempt?
	// direct back to customer page
	set_error( 'Bad user account or no permissions' );
	redirect_browser( '/' );
}


// addition for the 'all customer contacts' permission
// if user doesn't' have this permission then we only show ourselves in this list.
// todo: this is a problem - think about how this new "All Contacts" permission affects staff members viewing contact details, not just user contacts.
if ( $user_id && ! module_user::can_i( 'view', 'All ' . $contact_type_permission . ' Contacts', $contact_type, $contact_module_name ) ) {
	if ( $user_id != module_security::get_loggedin_id() ) {
		set_error( 'No permissions to view this contact' );
		redirect_browser( _BASE_HREF );
	}
}
if ( $user_id && ! module_user::can_i( 'edit', 'All ' . $contact_type_permission . ' Contacts', $contact_type, $contact_module_name ) ) {
	if ( $user_id != module_security::get_loggedin_id() ) {
		// dont let them edit this page
		ob_start();
		module_security::disallow_page_editing();
	}
}


// permission check.
if ( ! $user_id ) {
	// check if can create.
	module_security::check_page( array(
		'category'  => $contact_type,
		'page_name' => 'Contacts',
		'module'    => 'user',
		'feature'   => 'create',
	) );
} else {
	// check if can view/edit.
	module_security::check_page( array(
		'category'  => $contact_type,
		'page_name' => 'Contacts',
		'module'    => 'user',
		'feature'   => 'edit',
	) );
}


if ( $user_id > 0 && $user['user_id'] == $user_id ) {
	$module->page_title = _l( 'Contact: %s', $user['name'] );
} else {
	$module->page_title = _l( 'Contact: %s', _l( 'New' ) );
}

if ( isset( $user[ $use_master_key ] ) && $user[ $use_master_key ] ) {
	// we have a contact!
	// are we creating a new user?
	if ( ! $user_id || $user_id == 'new' ) {
		$user['roles'] = array(
			array( 'security_role_id' => module_config::c( 'contact_default_role', 0 ) ),
		);
	}
} else {
	die( 'Wrong file' );
}

?>


<form action="" method="post" autocomplete="off">
	<input type="hidden" name="_process" value="save_user"/>
	<!-- <input type="hidden" name="_redirect" value="<?php echo $module->link( "", array( "saved"   => true,
	                                                                                       "user_id" => ( (int) $user_id ) ? $user_id : ''
	) ); ?>" /> -->
	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>"/>
	<input type="hidden" name="<?php echo $use_master_key; ?>" value="<?php echo $user[ $use_master_key ]; ?>"/>


	<?php

	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);

	$required = array(
		'fields' => array(
			'name' => 'Name',
			//'email' => 'Email',
			//'password' => 'Password',
			//'status_id' => 'Status',
		),
	);
	if ( module_config::c( 'user_email_required', 1 ) ) {
		$required['fields']['email'] = true;
	}
	module_form::set_required( $required );

	// check if this customer is linked to anyone else. and isn't the primary
	$contact_links = array();
	if ( (int) $user_id > 0 && $use_master_key == 'customer_id' ) {
		$this_one_is_linked_primary = false;
		$contact_links              = module_user::get_contact_customer_links( $user['user_id'] );
		if ( count( $contact_links ) ) {
			// check if this user is primary.

			$this_one_is_linked_primary = ( $user['linked_parent_user_id'] == $user_id );
			$c                          = array();
			foreach ( $contact_links as $contact_link ) {
				$other_contact = module_user::get_user( $contact_link['user_id'] );
				if ( $this_one_is_linked_primary && ! $other_contact['linked_parent_user_id'] ) {
					// hack to ensure data validity
					$other_contact['linked_parent_user_id'] = $user_id;
					update_insert( 'user_id', $other_contact['user_id'], 'user', array( 'linked_parent_user_id' => $user_id ) );
				}
				$c[] = module_customer::link_open( $contact_link['customer_id'], true );
			}
			if ( $this_one_is_linked_primary ) {
				?>
				<div>
					<?php _e( 'Notice: This contact is primary and has access to the other linked customers: %s', implode( ', ', $c ) ); ?>
				</div>
				<?php
			} else if ( $user['linked_parent_user_id'] ) {
				?>
				<div>
					<?php _e( 'Notice: This contact has been linked to %s. Please go there to edit their details.', module_user::link_open_contact( $user['linked_parent_user_id'], true ) ); ?>

					<p>&nbsp;</p>
					<p>&nbsp;</p>
					<p>&nbsp;</p>
					<p>&nbsp;</p>
					<input type="hidden" name="unlink" id="unlink" value="">
					<input type="button" name="go" value="<?php _e( 'Unlink this contact from the group' ); ?>"
					       onclick="$('#unlink').val('yes'); this.form.submit();" class="delete_button submit_button">
				</div>

				<?php
				return;
			} else {
				?>
				Fatal contact linking error. Sorry I rushed this feature!
				<?php
			}
		}
	}


	hook_handle_callback( 'layout_column_half', 1 );

	$hide_more_button = true;
	$title            = 'Contact Details';
	include( module_theme::include_ucm( 'includes/plugin_user/pages/contact_admin_form.php' ) );

	if ( class_exists( 'module_address', false ) && module_config::c( 'users_have_address', 0 ) ) {
		module_address::print_address_form( $user_id, 'user', 'physical', 'Address' );
	}
	?>


	<?php
	if ( (int) $user_id > 0 && module_user::can_i( 'edit', 'Contacts', $contact_type ) && strlen( $user['email'] ) > 2 && $use_master_key == 'customer_id' ) {
		// check if contact exists under other customer accounts.
		$others = module_user::get_contacts( array(
			'email'                     => $user['email'],
			"${contact_module_name}_id" => 0,
		) );
		if ( count( $others ) > 1 ) {
			foreach ( $others as $other_id => $other ) {
				if ( $other['user_id'] == $user['user_id'] ) {
					// this "other" person is from teh same customer as us.
					unset( $others[ $other_id ] );
				} else if ( count( $contact_links ) ) {
					// check if this one is already linked somewhere.
					foreach ( $contact_links as $contact_link ) {
						if ( $contact_link['user_id'] == $other['user_id'] ) {
							unset( $others[ $other_id ] );
							break;
						}
					}
				}
			}
			if ( count( $others ) ) {
				ob_start();
				?>
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<?php foreach ( $others as $other ) { ?>
						<tr>
							<td>
								<input type="hidden" name="link_user_ids[]" value="<?php echo $other['user_id']; ?>">
								<!-- todo- checkbox -->
								<?php echo _l( '%s under customer %s', module_user::link_open_contact( $other['user_id'], true, $other ), module_customer::link_open( $other['customer_id'], true ) ); ?>
							</td>
						</tr>
					<?php } ?>
					<tr>
						<td align="center">
							<input type="hidden" name="link_customers" id="link_customers" value="">
							<input type="button" name="link"
							       value="<?php _e( 'Link above contacts to this one, and make THIS one primary' ); ?>"
							       onclick="$('#link_customers').val('yes'); this.form.submit();">
						</td>
					</tr>
					</tbody>
				</table>
				<?php
				$fieldset_data = array(
					'heading'         => array(
						'type'  => 'h3',
						'title' => _l( 'Create Linked Contacts' ),
						'help'  => 'This email address exists as a contact in another user account. By linking these accounts together, this user will be able to access all the linked customers from this single login. '
					),
					'class'           => 'tableclass tableclass_form tableclass_full',
					'elements_before' => ob_get_clean(),
				);
				echo module_form::generate_fieldset( $fieldset_data );
			}
		}

		//todo: display a warning if the same email address is used within the same customer as a different contact
		//todo: display a warning if this email address is used as a main system "user" (similar to what we do in users anyway).

	}
	if ( (int) $user_id > 0 ) {
		//handle_hook("note_list",$module,"user","user_id",$user_id);
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::display_notes( array(
					'title'       => 'Contact Notes',
					'owner_table' => 'user',
					'owner_id'    => $user_id,
					'view_link'   => $module->link_open( $user_id ),
					//'bypass_security' => true,
				)
			);
		}
		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {

			module_group::display_groups( array(
				'title'       => 'Contact Groups',
				'owner_table' => 'user',
				'owner_id'    => $user_id,
				'view_link'   => module_user::link_open( $user_id ),

			) );
		}
	}


	hook_handle_callback( 'layout_column_half', 2 );

	if ( is_file( 'includes/plugin_user/pages/user_admin_edit_login.php' ) ) {
		include( module_theme::include_ucm( 'includes/plugin_user/pages/user_admin_edit_login.php' ) );
	}
	if ( is_file( 'includes/plugin_user/pages/user_admin_edit_staff.php' ) ) {
		include( module_theme::include_ucm( 'includes/plugin_user/pages/user_admin_edit_staff.php' ) );
	}
	if ( $use_master_key == 'vendor_id' && is_file( 'includes/plugin_user/pages/user_admin_edit_company.php' ) ) {
		include( module_theme::include_ucm( 'includes/plugin_user/pages/user_admin_edit_company.php' ) );
	}

	hook_handle_callback( 'layout_column_half', 'end' );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save Contact' ),
			),
			array(
				'ignore' => ! ( (int) $user_id > 1 && module_user::can_i( 'delete', 'Contacts', $contact_type ) ),
				'type'   => 'delete_button',
				'name'   => 'butt_del_contact',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => $use_master_key == 'customer_id' ? "window.location.href='" . module_customer::link_open( $user['customer_id'] ) . "';" : "window.location.href='" . module_vendor::link_open( $user['vendor_id'] ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );
	?>

</form>
