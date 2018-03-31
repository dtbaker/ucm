<?php

// include this file to list some type of data
// supports different types of lists, everything from a major table list down to a select dropdown list

$display_type = 'table';
$allow_search = true;


switch ( $display_type ) {
	case 'table':

		$data_types = $module->get_data_types();
		foreach ( $data_types as $data_type ) {
			$data_type_id = $data_type['data_type_id'];
			if ( isset( $_REQUEST['data_type_id'] ) && $data_type_id != $_REQUEST['data_type_id'] ) {
				continue;
			}

			include( 'admin_data_list_type.php' );

		}

		break;
	case 'select':

		break;
	default:
		echo 'Display type: ' . $display_type . ' unknown.';
		break;
}