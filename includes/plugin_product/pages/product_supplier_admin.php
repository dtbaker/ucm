<?php


defined( '_UCM_VERSION' ) || die( '-4' );

if ( ! $module->can_i( 'view', 'Products' ) || ! $module->can_i( 'edit', 'Products' ) ) {
	redirect_browser( _BASE_HREF );
}

$product_id  = (int) $_REQUEST['product_id'];
$supplier_id = (int) $_REQUEST['customer_id'];

$product          = new UCMProduct( $product_id );
$product_supplier = new UCMProductSupplier( array( 'product_id' => $product_id, 'customer_id' => $supplier_id ) );

$module->page_title = _l( 'Product Supplier' );

module_form::print_form_open_tag( array(
	'process' => 'save_product_supplier',
	'hidden'  => array(
		'product_id'  => (int) $product['product_id'],
		'customer_id' => (int) $product_supplier['customer_id'],
	)
) );

$fieldset_data               = array(
	'heading'  => array(
		'type'  => 'h3',
		'title' => 'Product Supplier',
	),
	'class'    => 'tableclass tableclass_form tableclass_full',
	'elements' => array(),
);
$fieldset_data['elements'][] = array(
	'title'  => 'Supplier Name',
	'fields' => array(
		array(
			'type'   => 'text',
			'name'   => 'new_customer_id',
			'lookup' => array(
				'key'         => 'customer_id',
				'display_key' => 'customer_name',
				//	            'customer_billing_type_id' => _CUSTOMER_BILLING_TYPE_SUPPLIER,
				'plugin'      => 'customer',
				'lookup'      => 'customer_name',
				'return_link' => true,
				'display'     => '',
			),
			'value'  => $product_supplier['customer_id'],
		),
	)
);
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
			'ignore' => ! (int) $product_id,
			'type'   => 'delete_button',
			'name'   => 'butt_del',
			'value'  => _l( 'Delete' ),
		),
		array(
			'type'    => 'button',
			'name'    => 'cancel',
			'value'   => _l( 'Cancel' ),
			'class'   => 'submit_button',
			'onclick' => "window.location.href='" . $product->link_open( false ) . "';",
		),
	),
);
echo module_form::generate_form_actions( $form_actions );

module_form::print_form_close_tag();



