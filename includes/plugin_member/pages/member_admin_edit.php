<?php

$member_id = (int) $_REQUEST['member_id'];
$member    = array();

$member = module_member::get_member( $member_id );

// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $member_id > 0 && $member['member_id'] == $member_id ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Member',
			'page_name' => 'Members',
			'module'    => 'member',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Member',
			'page_name' => 'Members',
			'module'    => 'member',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'member', $member );
}

$module->page_title = _l( 'Member: %s', htmlspecialchars( $member['first_name'] . ' ' . $member['last_name'] ) );

?>
<form action="" method="post" id="member_form">
	<input type="hidden" name="_process" value="save_member"/>
	<input type="hidden" name="member_id" value="<?php echo $member_id; ?>"/>

	<?php
	module_form::set_required( array(
			'fields' => array(
				'first_name' => 'Name',
				'email'      => 'Email',
			)
		)
	);
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.submit_small', // subscription add
			)
		)
	);

	hook_handle_callback( 'layout_column_half', 1 );


	$fieldset_data = array(
		'heading'        => array(
			'type'  => 'h3',
			'title' => 'Member Information',
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(
			array(
				'title' => _l( 'First Name' ),
				'field' => array(
					'name'  => 'first_name',
					'type'  => 'text',
					'value' => $member['first_name'],
				),
			),
			array(
				'title' => _l( 'Last Name' ),
				'field' => array(
					'name'  => 'last_name',
					'type'  => 'text',
					'value' => $member['last_name'],
				),
			),
			array(
				'title' => _l( 'Business Name' ),
				'field' => array(
					'name'  => 'business',
					'type'  => 'text',
					'value' => $member['business'],
				),
			),
			array(
				'title' => _l( 'Email' ),
				'field' => array(
					'name'  => 'email',
					'type'  => 'text',
					'value' => $member['email'],
				),
			),
			array(
				'title' => _l( 'Phone' ),
				'field' => array(
					'name'  => 'phone',
					'type'  => 'text',
					'value' => $member['phone'],
				),
			),
			array(
				'title' => _l( 'Mobile' ),
				'field' => array(
					'name'  => 'mobile',
					'type'  => 'text',
					'value' => $member['mobile'],
				),
			),
			array(
				'title'  => _l( 'External' ),
				'ignore' => ! ( $member_id > 0 ),
				'field'  => array(
					'type'  => 'html',
					'value' => '<a href="' . module_member::link_public_details( $member_id ) . '" target="_blank">' . _l( 'Edit Details' ) . '</a>',
					'help'  => 'You can send this link to your customer so they can edit their details.',
				),
			),
		),
		'extra_settings' => array(
			'owner_table' => 'member',
			'owner_key'   => 'member_id',
			'owner_id'    => $member_id,
			'layout'      => 'table_row',
		),
	);
	echo module_form::generate_fieldset( $fieldset_data );

	if ( $member_id && (int) $member_id > 0 ) {
		hook_handle_callback( 'member_edit', $member_id );
	}
	hook_handle_callback( 'layout_column_half', 2 );

	if ( $member_id && (int) $member_id > 0 ) {
		if ( class_exists( 'module_group', false ) ) {
			module_group::display_groups( array(
				'title'       => 'Member Groups',
				'description' => _l( 'These are for you to group your members. The member cannot see or change these groups. You can choose members based on these groups.' ),
				'owner_table' => 'member',
				'owner_id'    => $member_id,
				'view_link'   => $module->link_open( $member_id ),

			) );

			if ( class_exists( 'module_newsletter', false ) ) {
				module_group::display_groups( array(
					'title'       => 'Newsletter',
					'description' => _l( 'The member can choose which of the below subscriptions they would like to receive. The member can see and change these subscriptions themselves. You can choose members based on these subscriptions.' ),
					'owner_table' => 'newsletter_subscription',
					'owner_id'    => $member_id,
				) );
			}
		}

	}

	hook_handle_callback( 'layout_column_half', 'end' );


	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'ignore' => ! ( $member_id > 0 ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . $module->link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	?>


</form>

