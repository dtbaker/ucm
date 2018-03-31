<?php


if ( ! $module->can_i( 'view', 'Products' ) || ! $module->can_i( 'edit', 'Products' ) ) {
	redirect_browser( _BASE_HREF );
}

// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) && module_product::can_i( 'edit', 'Products' ) ) {
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this customers?
		array(
			'bulk_actions' => array(
				'delete' => array(
					'label'    => 'Delete selected products',
					'type'     => 'delete',
					'callback' => 'module_product::bulk_handle_delete',
				),
			),
		)
	);
}

$search   = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
$products = module_product::get_products( $search );


$heading = array(
	'title'  => 'Products',
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
if ( module_product::can_i( 'create', 'Products' ) ) {
	if ( class_exists( 'module_import_export', false ) ) {
		$link                = module_import_export::import_link(
			array(
				'callback'   => 'module_product::handle_import',
				'name'       => 'Products',
				'return_url' => $_SERVER['REQUEST_URI'],
				'fields'     => array(
					'Product ID'        => 'product_id',
					'Product Name'      => 'name',
					'Product Category'  => 'category_name',
					'Hours/Qty'         => 'quantity',
					'Amount'            => 'amount',
					'Description'       => 'description',
					'Enable Inventory'  => 'inventory_control',
					'Current Inventory' => 'inventory_level',
					'Cost Price'        => 'purchase_price',
				),
			)
		);
		$heading['button'][] = array(
			'title' => "Import Products",
			'type'  => 'add',
			'url'   => $link,
		);
	}
	$heading['button'][] = array(
		'title' => "Create New Product",
		'type'  => 'add',
		'url'   => module_product::link_open( 'new' ),
	);
}
print_heading( $heading );
?>


<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name'      => array(
				'title' => _l( 'Product Name' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[general]',
					'value' => isset( $search['general'] ) ? $search['general'] : '',
					'size'  => 30,
				)
			),
			'category'  => array(
				'title' => false,
				'field' => array(
					'type'             => 'select',
					'name'             => 'search[product_category_id]',
					'value'            => isset( $search['product_category_id'] ) ? $search['product_category_id'] : '',
					'options'          => module_product::get_product_categories(),
					'options_array_id' => 'product_category_name',
					'blank'            => ' - ' . _l( 'Category' ) . ' - ',
				)
			),
			'inventory' => array(
				'title' => false,
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[inventory]',
					'value'   => isset( $search['inventory'] ) ? $search['inventory'] : '',
					'options' => array(
						'1' => 'In Stock',
						'2' => 'Out of Stock',
						'3' => 'Not Inventory Item',
					),
					'blank'   => ' - ' . _l( 'Inventory' ) . ' - ',
				)
			),
		)
	);
	if ( class_exists( 'module_extra', false ) ) {
		$search_bar['extra_fields'] = 'product';
	}
	echo module_form::search_bar( $search_bar );


	/** START TABLE LAYOUT **/
	$table_manager                    = module_theme::new_table_manager();
	$columns                          = array();
	$columns['product_name']          = array(
		'title'      => _l( 'Product Name' ),
		'callback'   => function ( $product ) {
			echo module_product::link_open( $product['product_id'], true, $product );
		},
		'cell_class' => 'row_action',
	);
	$columns['product_category_name'] = array(
		'title' => _l( 'Category Name' ),
	);
	$columns['quantity']              = array(
		'title' => _l( 'Hours/Quantity' ),
	);
	$columns['amount']                = array(
		'title'    => _l( 'Amount' ),
		'callback' => function ( $product ) {
			echo dollar( $product['amount'] );
		}
	);
	$columns['inventory']             = array(
		'title'    => _l( 'Inventory' ),
		'callback' => function ( $product ) {
			if ( ! empty( $product['inventory_control'] ) ) {
				_e( '%s remain', $product['inventory_level_current'] );
			} else {
				_e( 'N/A' );
			}
		}
	);
	if ( module_product::can_i( 'edit', 'Products' ) ) {
		$columns['bulk_action'] = array(
			'title'    => ' ',
			'callback' => function ( $product ) {
				echo '<input type="checkbox" name="bulk_operation[' . $product['product_id'] . ']" value="yes">';
			}
		);
	}
	if ( class_exists( 'module_extra', false ) ) {
		// do extra before "table sorting" so that it can hook in with the table sort call
		$table_manager->display_extra( 'product', function ( $product ) {
			module_extra::print_table_data( 'product', $product['product_id'] );
		}, 'product_id' );
	}
	$table_manager->set_id( 'product_list' );
	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $products );
	$table_manager->pagination = true;
	$table_manager->print_table();
	/** END TABLE LAYOUT **/

	?>
</form>