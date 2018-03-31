<?php

$subscription_id = isset( $_REQUEST['subscription_id'] ) && $_REQUEST['subscription_id'] != '';
if ( $subscription_id ) {
	$subscription = module_subscription::get_subscription( $subscription_id );
	include( 'subscription_admin_edit.php' );
} else {
	include( 'subscription_admin_list.php' );
}
