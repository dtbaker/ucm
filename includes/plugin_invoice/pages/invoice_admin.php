<?php

$invoice_safe = true;

if ( isset( $_REQUEST['print'] ) ) {
	include( module_theme::include_ucm( "includes/plugin_invoice/pages/invoice_admin_print.php" ) );
	//include('invoice_admin_print.php');
} else if ( isset( $_REQUEST['invoice_id'] ) ) {

	if ( isset( $_REQUEST['email'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_invoice/pages/invoice_admin_email.php" ) );
		//include('invoice_admin_email.php');
	} else {
		/*if(module_security::getlevel() > 1){
				include('invoice_customer_view.php');
		}else{*/
		include( module_theme::include_ucm( "includes/plugin_invoice/pages/invoice_admin_edit.php" ) );
		//include("invoice_admin_edit.php");
		/*}*/
	}

} else {

	include( module_theme::include_ucm( "includes/plugin_invoice/pages/invoice_admin_list.php" ) );
	//include("invoice_admin_list.php");

}

