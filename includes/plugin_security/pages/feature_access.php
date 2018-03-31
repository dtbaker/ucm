<?php

if ( isset( $access_requirements['feature'] ) && $access_requirements['feature'] == 'menu' ) {
	switch ( $access_requirements['module'] ) {
		case 'people':
		case 'inventory':
		case 'invoice':
		case 'config':
		case 'sales_order':
		case 'quote':
		case 'task':
			$access = true; //($_SERVER['SERVER_ADDR'] == '192.168.0.54');
			break;
		default:
			$access = true;
	}
} else if ( isset( $access_requirements['feature'] ) && $access_requirements['feature'] == 'submenu' ) {
	switch ( $access_requirements['module'] ) {
		case 'user':
		case 'supplier_product':
		case 'customer':
		case 'supplier':
			$access = true;
			break;
		default:
			$access = true; //($_SERVER['SERVER_ADDR'] == '192.168.0.54');
	}
} else {

	if ( false ) { //self::getlevel() == 1){
		// can access any part or feature of the system!
		$access = true;
	} else {
		$access = true;
		// check based on selected user permissions
		if ( isset( $access_requirements['feature'] ) ) {
			switch ( $access_requirements['feature'] ) {
				case 'page':
					break;
				case 'add_link':
				case 'edit_link':
				case 'delete_link':
				case 'edit_field':
					break;
				case 'view_link':
					switch ( $access_requirements['database_table'] ) {
						case 'inventory_test':
						case 'inventory':
							$access = true; // users only allowed to view inventory
							break;
					}
					break;
			}
		}
	}
}
?>