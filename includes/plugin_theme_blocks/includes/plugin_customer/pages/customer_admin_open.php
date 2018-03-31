<?php

// add a new 'edit' link to the customer page.
if ( module_config::c( 'customer_widgets', 1 ) ) {
	$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
	if ( (int) $customer_id > 0 ) {

		$links = isset( $links ) ? $links : array();

		array_unshift( $links, array(
			"name"                => 'Edit',
			"icon_name"           => "pencil",
			'm'                   => 'customer',
			'p'                   => 'customer_admin_open',
			'default_page'        => 'customer_admin_edit',
			'order'               => 1,
			'current'             => isset( $_REQUEST['edit'] ),
			'menu_include_parent' => 0,
			'args'                => array( 'edit' => 'yes' )
		) );


	}
}
// todo: this doesn't allow overriding any more:
include_once 'includes/plugin_customer/pages/customer_admin_open.php';