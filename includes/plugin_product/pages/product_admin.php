<?php

if ( isset( $_REQUEST['product_id'] ) ) {

	include( module_theme::include_ucm( "includes/plugin_product/pages/product_admin_edit.php" ) );

} else {

	include( module_theme::include_ucm( "includes/plugin_product/pages/product_admin_list.php" ) );

}
