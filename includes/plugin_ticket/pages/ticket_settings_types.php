<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$ticket_types = module_ticket::get_types( false );

if ( isset( $_REQUEST['ticket_type_id'] ) && $_REQUEST['ticket_type_id'] ) {
	$show_other_settings = false;
	$ticket_type_id      = (int) $_REQUEST['ticket_type_id'];
	if ( $ticket_type_id > 0 ) {
		$ticket_type = module_ticket::get_ticket_type( $ticket_type_id );
	} else {
		$ticket_type = array();
	}
	if ( ! $ticket_type ) {
		$ticket_type = array(
			'name'            => '',
			'public'          => '1',
			'default_user_id' => 0,
		);
	}
	?>


	<form action="" method="post">
		<input type="hidden" name="_process" value="save_ticket_type">
		<input type="hidden" name="ticket_type_id" value="<?php echo $ticket_type_id; ?>"/>

		<?php

		$fieldset_data               = array(
			'heading' => array(
				'type'  => 'h3',
				'main'  => true,
				'title' => 'Edit Ticket Type',
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Type/Department',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $ticket_type['name'],
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Public',
			'fields' => array(
				array(
					'type'    => 'select',
					'name'    => 'public',
					'value'   => $ticket_type['public'],
					'options' => get_yes_no(),
					'help'    => 'If this is public this option will display in the public ticket submission form.',
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Default Staff',
			'fields' => array(
				array(
					'type'    => 'select',
					'name'    => 'default_user_id',
					'value'   => $ticket_type['default_user_id'],
					'options' => module_ticket::get_ticket_staff_rel(),
				),
			)
		);
		$groups                      = module_group::get_groups( 'ticket' );
		$default_groups              = @unserialize( $ticket_type['default_groups'] );
		if ( ! is_array( $default_groups ) ) {
			$default_groups = array( 0 );
		}
		$fieldset_data['elements'][] = array(
			'title'  => 'Default Groups',
			'fields' => array(
				array(
					'type'             => 'select',
					'name'             => 'default_groups[]',
					'values'           => $default_groups,
					'options'          => $groups,
					'options_array_id' => 'name',
					'multiple'         => true,
				),
			)
		);

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );


		$form_actions = array(
			'class'    => 'action_bar action_bar_center action_bar_single',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_save',
					'value' => _l( 'Save' ),
				),
				array(
					'type'    => 'delete_button',
					'name'    => 'butt_del',
					'value'   => _l( 'Delete' ),
					'onclick' => "return confirm('" . _l( 'Really delete this record?' ) . "');",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>


	</form>

	<?php
} else {


	print_heading( array(
		'title'  => 'Ticket Types/Departments',
		'type'   => 'h2',
		'main'   => true,
		'button' => array(
			'url'   => module_ticket::link_open_type( 'new' ),
			'title' => 'Add New Type',
			'type'  => 'add',
		),
	) );

	$staff = module_ticket::get_ticket_staff_rel();

	/** START TABLE LAYOUT **/
	$table_manager     = module_theme::new_table_manager();
	$columns           = array();
	$columns['type']   = array(
		'title'      => _l( 'Type/Department' ),
		'callback'   => function ( $ticket_type ) {
			echo module_ticket::link_open_type( $ticket_type['ticket_type_id'], true );
		},
		'cell_class' => 'row_action',
	);
	$columns['public'] = array(
		'title'    => _l( 'Public' ),
		'callback' => function ( $ticket_type ) {
			$yn = get_yes_no();
			echo _l( $yn[ $ticket_type['public'] ] );
		},
	);
	$columns['staff']  = array(
		'title'    => _l( 'Staff' ),
		'callback' => function ( $ticket_type ) use ( $staff ) {
			echo isset( $staff[ $ticket_type['default_user_id'] ] ) ? $staff[ $ticket_type['default_user_id'] ] : _l( 'Default' );
		},
	);
	$table_manager->set_id( 'ticket_type_list' );
	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $ticket_types );
	$table_manager->pagination = true;
	$table_manager->print_table();
	/** END TABLE LAYOUT **/

}

