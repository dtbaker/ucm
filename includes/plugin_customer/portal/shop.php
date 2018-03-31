<form action="" method="post" id="shop-order-form">

	<input type="hidden" name="portal_action" value="generate_invoice">
	<?php module_form::print_form_auth(); ?>

	<?php

	// for now we just loop over all active contracts and find products.
	$products = array();
	foreach ( $contracts as $contract ) {
		$UCMContract = UCMContract::singleton( $contract['contract_id'] );
		if ( $UCMContract->is_active() ) {
			foreach ( $UCMContract->get_products() as $product_id => $contract_id ) {
				$products[ $product_id ] = UCMProduct::singleton( $product_id );
			}
		}
	}
	if ( ! $products ) {
		echo 'No products associated with account';
	} else {

		$table_manager              = module_theme::new_table_manager();
		$table_manager->table_class = 'public';
		$table_manager->row_class   = 'public';
		$columns                    = array();
		$columns['product_name']    = array(
			'title'      => 'Product Name',
			'callback'   => function ( $product ) {
				echo $product->get( 'name' );
			},
			'cell_class' => 'row_action',
		);

		$columns['quantity'] = array(
			'title' => _l( 'Hours/Quantity' ),
		);
		$columns['amount']   = array(
			'title'    => _l( 'Amount' ),
			'callback' => function ( $product ) {
				echo dollar( $product->get( 'amount' ) );
			}
		);

		$columns['order_quantity'] = array(
			'title'      => 'Order Quantity',
			'cell_class' => 'quantity',
			'callback'   => function ( $product ) {
				?>
				<input type="text" name="qty[<?php echo $product->get( 'product_id' ); ?>]" value="" placeholder="0"
				       class="shop-order">
				<?php
			},
		);

		$table_manager->set_columns( $columns );
		$table_manager->set_rows( $products );

		$footer_rows   = array();
		$footer_rows[] = array(
			'product_name'   => array(
				'data'         => '',
				'cell_colspan' => 3,
			),
			'order_quantity' => array(
				'data'         => ' <input type="submit" value="Generate Invoice"> ',
				'cell_colspan' => 1
			),
		);
		$table_manager->set_footer_rows( $footer_rows );

		$table_manager->pagination = false;
		$table_manager->print_table();

	}

	?>


</form>

