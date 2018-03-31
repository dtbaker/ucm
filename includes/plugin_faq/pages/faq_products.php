<?php


if ( ! module_config::can_i( 'view', 'Settings' ) || ! module_faq::can_i( 'edit', 'FAQ' ) ) {
	redirect_browser( _BASE_HREF );
}

$faq_products = module_faq::get_faq_products();
$types        = module_ticket::get_types();
if ( class_exists( 'module_envato', false ) ) {
	$all_items_rel = module_envato::get_envato_items_rel();
}

if ( isset( $_REQUEST['faq_product_id'] ) && $_REQUEST['faq_product_id'] ) {
	$show_other_settings = false;
	$faq_product_id      = (int) $_REQUEST['faq_product_id'];
	if ( $faq_product_id > 0 ) {
		$faq_product = module_faq::get_faq_product( $faq_product_id );
	} else {
		$faq_product = array();
	}
	if ( ! $faq_product ) {
		$faq_product = array(
			'name'            => '',
			'envato_item_ids' => '',
			'default_type_id' => '',
		);
	}
	?>


	<form action="" method="post">
		<input type="hidden" name="_process" value="save_faq_product">
		<input type="hidden" name="faq_product_id" value="<?php echo $faq_product_id; ?>"/>

		<?php
		$fieldset_data               = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Edit FAQ Product',
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(),
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Product Name',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $faq_product['name'],
				),
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Default Type/Department',
			'fields' => array(
				array(
					'type'             => 'select',
					'name'             => 'default_type_id',
					'value'            => $faq_product['default_type_id'],
					'options'          => $types,
					'options_array_id' => 'name',
				),
			)
		);
		if ( class_exists( 'module_envato', false ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Envato Items',
				'fields' => array(
					function () use ( $faq_product, $all_items_rel ) {
						$linked_items = explode( '|', $faq_product['envato_item_ids'] );
						foreach ( $linked_items as $id => $linked_item ) {
							if ( ! strlen( trim( $linked_item ) ) ) {
								unset( $linked_items[ $id ] );
							}
						}
						if ( ! count( $linked_items ) ) {
							$linked_items[] = '';
						}
						?>

						<div id="envato_items_holder">
							<?php foreach ( $linked_items as $linked_item ) {
								?>
								<div class="dynamic_block">

									<?php
									echo print_select_box( $all_items_rel, 'envato_item_ids[]', $linked_item );
									?>
									<a href="#" class="add_addit" onclick="return seladd(this);">+</a>
									<a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
								</div>
							<?php } ?>
						</div>
						<script type="text/javascript">
                set_add_del('envato_items_holder');
						</script>
						<?php
					}
				)
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
					'ignore' => ! (int) $faq_product_id,
					'type'   => 'delete_button',
					'name'   => 'butt_del',
					'value'  => _l( 'Delete' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $module->link_open_faq_product( false ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
		?>


	</form>

	<?php
} else {

	$header = array(
		'title'  => _l( 'FAQ Product' ),
		'type'   => 'h2',
		'main'   => true,
		'button' => array(),
	);
	if ( module_faq::can_i( 'create', 'FAQ' ) ) {
		$header['button'] = array(
			'url'   => module_faq::link_open_faq_product( 'new' ),
			'title' => _l( 'Add New Product' ),
			'type'  => 'add',
		);
	}
	print_heading( $header );

	/** START TABLE LAYOUT **/
	$table_manager           = module_theme::new_table_manager();
	$columns                 = array();
	$columns['product_name'] = array(
		'title'      => _l( 'Product Name' ),
		'callback'   => function ( $data ) {
			echo module_faq::link_open_faq_product( $data['faq_product_id'], true );
		},
		'cell_class' => 'row_action',
	);
	$columns['department']   = array(
		'title'    => _l( 'Default Type/Department' ),
		'callback' => function ( $data ) use ( $types ) {
			echo isset( $types[ $data['default_type_id'] ] ) ? htmlspecialchars( $types[ $data['default_type_id'] ]['name'] ) : '';
		},
	);
	if ( class_exists( 'module_envato', false ) ) {
		$columns['envato'] = array(
			'title'    => _l( 'Envato Item' ),
			'callback' => function ( $data ) use ( $all_items_rel ) {
				$linked_items = explode( '|', $data['envato_item_ids'] );
				foreach ( $linked_items as $id => $linked_item ) {
					if ( ! strlen( trim( $linked_item ) ) ) {
						unset( $linked_items[ $id ] );
					}
					if ( isset( $all_items_rel[ $linked_item ] ) ) {
						$linked_items[ $id ] = $all_items_rel[ $linked_item ];
					}
				}
				echo implode( ', ', $linked_items );
			},
		);
	}
	$table_manager->set_id( 'faq_list' );
	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $faq_products );
	$table_manager->pagination = true;
	$table_manager->print_table();
	/** END TABLE LAYOUT **/
}