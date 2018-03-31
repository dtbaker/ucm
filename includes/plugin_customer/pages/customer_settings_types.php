<?php

if ( ! module_customer::can_i( 'edit', 'Customer Settings', 'Config' ) ) {
	redirect_browser( _BASE_HREF );
}

if ( isset( $_REQUEST['customer_type_id'] ) && $_REQUEST['customer_type_id'] ) {
	$show_other_settings = false;
	$customer_type_id    = (int) $_REQUEST['customer_type_id'];
	if ( $customer_type_id > 0 ) {
		$customer_type = module_customer::get_customer_type( $customer_type_id );
	} else {
		$customer_type = array();
	}
	if ( ! $customer_type ) {
		$customer_type = array(
			'type_name'        => '',
			'type_name_plural' => '',
			'menu_position'    => 0,
			'menu_icon'        => 'users',
			'billing_type'     => 0,
		);
	}
	?>


	<form action="" method="post">
		<input type="hidden" name="_process" value="save_customer_type">
		<input type="hidden" name="customer_type_id" value="<?php echo $customer_type_id; ?>"/>

		<?php

		module_form::print_form_auth();

		$fieldset_data               = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Edit Customer Type',
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(),
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Customer Type',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'type_name',
					'value' => $customer_type['type_name'],
					'help'  => 'example: Lead',
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Customer Type (plural)',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'type_name_plural',
					'value' => $customer_type['type_name_plural'],
					'help'  => 'example: Leads',
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Menu Position',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'menu_position',
					'value' => $customer_type['menu_position'],
					'help'  => 'Where this should appear in the main menu'
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Menu Icon',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'menu_icon',
					'value' => $customer_type['menu_icon'],
					'help'  => 'Type the icon name from http://fontawesome.io/icons/ (eg: bell). Compatible with the Metis theme.'
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Billing Type',
			'fields' => array(
				array(
					'type'    => 'select',
					'name'    => 'billing_type',
					'value'   => $customer_type['billing_type'],
					'options' => array(
						_CUSTOMER_BILLING_TYPE_NORMAL   => 'Normal Customer',
						_CUSTOMER_BILLING_TYPE_SUPPLIER => 'Supplier/Vendor',
					)
				),
			)
		);
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$fieldset_data['extra_settings'] = array(
				'owner_table' => 'customer_type',
				'owner_key'   => 'customer_type_id',
				'owner_id'    => $customer_type_id,
				'layout'      => 'table_row',
				//				'allow_new' => module_extra::can_i('create',$page_type),
				//				'allow_edit' => module_extra::can_i('edit',$page_type),
			);
		}

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );


		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_save',
					'value' => _l( 'Save' ),
				),
				array(
					'ignore' => ! (int) $customer_type_id,
					'type'   => 'delete_button',
					'name'   => 'butt_del',
					'value'  => _l( 'Delete' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $module->link_open_customer_type( false ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
		?>


	</form>

	<?php
} else {

	$customer_types = module_customer::get_customer_types();


	$header           = array(
		'title'  => _l( 'Customer Types' ),
		'type'   => 'h2',
		'main'   => true,
		'button' => array(),
	);
	$header['button'] = array(
		'url'   => module_customer::link_open_customer_type( 'new' ),
		'title' => _l( 'Add New Type' ),
		'type'  => 'add',
	);
	print_heading( $header );

	/** START TABLE LAYOUT **/
	$table_manager            = module_theme::new_table_manager();
	$columns                  = array();
	$columns['customer_type'] = array(
		'title'      => _l( 'Customer Type' ),
		'callback'   => function ( $data ) {
			echo module_customer::link_open_customer_type( $data['customer_type_id'], true, $data );
		},
		'cell_class' => 'row_action',
	);
	$columns['menu_position'] = array(
		'title' => _l( 'Menu Position' ),
	);
	$table_manager->set_id( 'customer_type_list' );
	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $customer_types );
	$table_manager->pagination = true;
	$table_manager->print_table();
	/** END TABLE LAYOUT **/
}