<?php


if ( isset( $_REQUEST['product_category_id'] ) && $_REQUEST['product_category_id'] != '' ) {
	$product_category_id = (int) $_REQUEST['product_category_id'];
	$product_category    = module_product::get_product_category( $product_category_id );
	include( 'product_admin_category_edit.php' );
} else {
	include( 'product_admin_category_list.php' );
}
