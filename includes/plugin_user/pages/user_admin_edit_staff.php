<?php


if ( module_user::can_i( 'edit', 'Staff Settings', 'Config' ) ) {


	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => 'Staff Settings',
			'help'  => 'A staff member is someone who can be assigned to a Job, Job Task or Customer. ',
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(),
	);

	$fieldset_data['elements']['is_staff']    = array(
		'title'  => _l( 'Staff Member' ),
		'fields' => array(
			array(
				'type'    => 'select',
				'options' => get_yes_no(),
				'blank'   => false,
				'name'    => 'is_staff',
				'value'   => isset( $user['is_staff'] ) && $user['is_staff'] >= 0 ? $user['is_staff'] : ( module_user::is_staff_member( $user_id ) ? '1' : '0' ),
				'help'    => 'If the user is a staff member they will display in the staff drop down list.',
			),
		)
	);
	$fieldset_data['elements']['split_hours'] = array(
		'title'  => _l( 'Split Pricing' ),
		'fields' => array(
			array(
				'type'    => 'select',
				'options' => get_yes_no(),
				'blank'   => false,
				'name'    => 'split_hours',
				'value'   => isset( $user['split_hours'] ) && $user['split_hours'] >= 0 ? $user['split_hours'] : '0',
				'help'    => 'Will this user have a different hourly rate than what is entered on a Job? e.g. If this user is a contractor and will be paid a different amount to what the customer is charged for the project.',
			),
		)
	);
	$fieldset_data['elements']['hourly_rate'] = array(
		'title'  => _l( 'Hourly Rate' ),
		'fields' => array(
			array(
				'type'  => 'currency',
				'name'  => 'hourly_rate',
				'value' => isset( $user['hourly_rate'] ) ? $user['hourly_rate'] : '',
				'help'  => 'The default hourly rate this staff member will receive when working on jobs (can be changed per job).',
			),
		)
	);
	echo module_form::generate_fieldset( $fieldset_data );
}

