<?php

$access = true;


switch ( $table_name ) {
	case 'invoice':
	default:
		// check if current user can access this invoice.
		if ( $data && isset( $data['customer_id'] ) && (int) $data['customer_id'] > 0 ) {
			$valid_customer_ids = module_security::get_customer_restrictions();
			if ( $valid_customer_ids ) {
				$access = isset( $valid_customer_ids[ $data['customer_id'] ] );
				if ( ! $access ) {
					return false;
				}
			}
		}
		break;
}