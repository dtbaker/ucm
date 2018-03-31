<?php

$contract_safe = true; // stop including files directly.
if ( ! module_contract::can_i( 'view', 'Contracts' ) ) {
	echo 'permission denied';

	return;
}

if ( isset( $_REQUEST['contract_id'] ) ) {

	if ( isset( $_REQUEST['email'] ) ) {
		include( module_theme::include_ucm( "includes/plugin_contract/pages/contract_admin_email.php" ) );

	} else if ( (int) $_REQUEST['contract_id'] > 0 ) {
		include( module_theme::include_ucm( "includes/plugin_contract/pages/contract_admin_edit.php" ) );
		//include("contract_admin_edit.php");
	} else {
		include( module_theme::include_ucm( "includes/plugin_contract/pages/contract_admin_create.php" ) );
		//include("contract_admin_create.php");
	}

} else {

	include( module_theme::include_ucm( "includes/plugin_contract/pages/contract_admin_list.php" ) );
	//include("contract_admin_list.php");

} 

