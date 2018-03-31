<?php

$links = array();

if ( ( ! isset( $_GET['customer_id'] ) || ! $_GET['customer_id'] ) && class_exists( 'module_faq', false ) && ( module_config::c( 'ticket_separate_product_queue', 0 ) || module_config::c( 'ticket_separate_product_menu', 0 ) ) ) {

	$showing_ticket = false;
	if ( isset( $_GET['ticket_id'] ) && (int) $_GET['ticket_id'] > 0 ) {
		$ticket_data = module_ticket::get_ticket( $_GET['ticket_id'] );
		if ( $ticket_data && $ticket_data['ticket_id'] == $_GET['ticket_id'] ) {
			$showing_ticket             = true;
			$_REQUEST['faq_product_id'] = (int) $ticket_data['faq_product_id'];
			/*
			array_unshift($links,array(
					"name"=>_l('Ticket:')." <strong>".module_ticket::ticket_number($ticket_data['ticket_id']).'</strong>',
					'm' => 'ticket',
					'p' => 'ticket_admin',
					'default_page' => 'ticket_admin_open',
					'order' => 0,
					'menu_include_parent' => 0,
					'allow_nesting' => 0,
					'current' => true,
					'args'=>array(
							'faq_product_id'=>false,
							'ticket_id'=>$ticket_data['ticket_id'],
					)
			));*/
		}
	}
	array_unshift( $links, array(
		"name"                => _l( 'All' ),
		'm'                   => 'ticket',
		'p'                   => 'ticket_admin',
		'default_page'        => 'ticket_admin_open',
		'order'               => 1,
		'menu_include_parent' => 0,
		'allow_nesting'       => 0,
		'current'             => ( ! $showing_ticket && ! isset( $_REQUEST['faq_product_id'] ) ),
		'args'                => array(
			'faq_product_id' => false,
			'ticket_id'      => false,
		)
	) );

	$link_name = _l( 'No Product' );
	if ( module_config::c( 'menu_show_summary', 0 ) ) {
		$product_tickets = module_ticket::get_tickets( array(
			'faq_product_id' => '0',
			'status_id'      => '<' . _TICKET_STATUS_RESOLVED_ID,
		) );
		$link_name       .= " <span class='menu_label'>" . mysqli_num_rows( $product_tickets ) . '</span>';
		$ticket_count    = module_ticket::get_ticket_count( 0 );
		if ( $ticket_count && $ticket_count['priority'] > 0 ) {
			$link_name .= " <span class='menu_label important'>" . $ticket_count['priority'] . "</span> ";
			//    $link_name .= ' <em>+ '.$ticket_count['priority'].'</em>';
		}
	}
	//$link_name .= '</span>';

	array_unshift( $links, array(
		"name"                => $link_name,
		'm'                   => 'ticket',
		'p'                   => 'ticket_admin',
		'default_page'        => 'ticket_admin_open',
		'order'               => 2,
		'menu_include_parent' => 0,
		'allow_nesting'       => 0,
		'current'             => ( ! $showing_ticket && isset( $_REQUEST['faq_product_id'] ) && $_REQUEST['faq_product_id'] == 0 ),
		//(!$showing_ticket && (!isset($_REQUEST['faq_product_id']) || !$_REQUEST['faq_product_id'])),
		'args'                => array(
			'faq_product_id' => 0,
			'ticket_id'      => false,
		)
	) );
	/*if(!$showing_ticket && (!isset($_REQUEST['faq_product_id']) || !$_REQUEST['faq_product_id'])){
			// hack for search to work correctly.
			$_REQUEST['search'] = isset($_REQUEST['search']) ? $_REQUEST['search'] : array();
			$_REQUEST['search']['faq_product_id'] = 0;
	}*/

	$products = module_faq::get_faq_products_rel();
	$order    = 3;
	foreach ( $products as $product_id => $product_name ) {
		$link_name = htmlspecialchars( $product_name );
		if ( module_config::c( 'menu_show_summary', 0 ) ) {
			$ticket_count = module_ticket::get_ticket_count( $product_id );
			if ( ! $ticket_count || ! $ticket_count['count'] ) {
				continue;
			}
			$link_name .= " <span class='menu_label'>" . $ticket_count['count'] . '</span>';
			if ( $ticket_count && $ticket_count['priority'] > 0 ) {
				$link_name .= " <span class='menu_label important'>" . $ticket_count['priority'] . "</span> ";
				//$link_name .= ' <em>+ '.$ticket_count['priority'].'</em>';
			}
		}
		//$link_name .= '</span>';

		array_unshift( $links, array(
			"name"                => $link_name,
			'm'                   => 'ticket',
			'p'                   => 'ticket_admin',
			'default_page'        => 'ticket_admin_open',
			'order'               => ++ $order,
			'menu_include_parent' => 0,
			'allow_nesting'       => 0,
			'current'             => ( isset( $_REQUEST['faq_product_id'] ) && $_REQUEST['faq_product_id'] == $product_id ),
			'args'                => array(
				'faq_product_id' => $product_id,
				'ticket_id'      => false,
			)
		) );
	}
} else {
	include( 'ticket_admin_open.php' );
}


