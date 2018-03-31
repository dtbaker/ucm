<?php


$ticket_count = 0;
if ( module_config::c( 'menu_show_summary', 0 ) ) {
	switch ( module_config::c( 'ticket_show_summary_type', 'unread' ) ) {
		case 'unread':
			$ticket_count = module_ticket::get_unread_ticket_count();
			break;
		case 'total':
		default:
			$ticket_count = module_ticket::get_total_ticket_count();
			break;
	}
}

if ( $ticket_count > 0 ) {
	$module->page_title = _l( 'Tickets (%s)', $ticket_count );
} else {
	$module->page_title = _l( 'Tickets' );
}

// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) ) {
	$group_pagination_settings = array();
	if ( module_config::c( 'ticket_enable_groups', 1 ) ) {
		$group_pagination_settings['fields'] = array(
			'owner_id'    => 'ticket_id',
			'owner_table' => 'ticket',
		);
	}
	$group_pagination_settings['bulk_actions'] = array(
		'delete'          => array(
			'label'    => 'Delete selected tickets',
			'type'     => 'delete',
			'callback' => 'module_ticket::bulk_handle_delete',
		),
		'unread'          => array(
			'label'    => 'Mark selected tickets as unread',
			'type'     => 'button',
			'callback' => 'module_ticket::bulk_handle_unread',
		),
		'read'            => array(
			'label'    => 'Mark selected tickets as read',
			'type'     => 'button',
			'callback' => 'module_ticket::bulk_handle_read',
		),
		'status_resolved' => array(
			'label'    => 'Change status to:',
			'type'     => 'form',
			'callback' => 'module_ticket::bulk_handle_status',
			'elements' => array(
				array(
					'type'    => 'select',
					'name'    => 'bulk_change_status_id',
					'options' => module_ticket::get_statuses(),
				)
			),
		),
		'change_assigned' => array(
			'label'    => 'Change Assigned:',
			'type'     => 'form',
			'callback' => 'module_ticket::bulk_handle_staff_change',
			'elements' => array(
				array(
					'type'    => 'select',
					'name'    => 'bulk_change_staff_id',
					'options' => module_ticket::get_ticket_staff_rel(),
					'blank'   => _l( ' - Unassigned - ' ),
				)
			),
		),
	);
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this customers?
		$group_pagination_settings
	);
}

if ( class_exists( 'module_table_sort', false ) ) {
	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'ticket_list',
			'sortable' => array(
				'ticket_id'       => array(
					'field' => 'ticket_id',
					//'current' => 1, // 1 asc, 2 desc
				),
				'ticket_time'     => array(
					'field' => 'last_message_timestamp',
					//'current' => 1, // 1 asc, 2 desc
				),
				'ticket_due_time' => array(
					'field' => 'due_timestamp',
				),
				'status'          => array(
					'field' => 'status_id',
				),
				'ticket_priority' => array(
					'field' => 'priority',
				),
				// special case for group sorting.
				'ticket_group'    => array(
					'group_sort'  => true,
					'owner_table' => 'ticket',
					'owner_id'    => 'ticket_id',
				),
				// special case for extra field sorting.
				'extra_ticket'    => array(
					'extra_sort'  => true,
					'owner_table' => 'ticket',
					'owner_id'    => 'ticket_id',
				),
			),
		)
	);
}
/*module_form::enable_pagination_bulk_operations(
	array(
		'callback' => 'module_ticket::process_bulk_operation',
		'options' => array(
			'delete' => array(
				'label' => 'Delete selected tickets',
			),
			'unread' => array(
				'label' => 'Mark selected tickets as unread',
			),
			'read' => array(
				'label' => 'Mark selected tickets as read',
			),
		),
	)
);*/

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( ! $search && isset( $_REQUEST['do_last_search'] ) && isset( $_SESSION['ticket_last_search'] ) ) {
	$search = $_SESSION['ticket_last_search'];
}
if ( isset( $_REQUEST['faq_product_id'] ) && ! isset( $search['faq_product_id'] ) ) {
	$search['faq_product_id'] = (int) $_REQUEST['faq_product_id'];
}
if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
	$search['customer_id'] = (int) $_REQUEST['customer_id'];
} else {
	$search['customer_id'] = false;
}


$search_statuses          = module_ticket::get_statuses();
$search_statuses['2,3,5'] = 'New/Replied/In Progress';
if ( ! isset( $search['status_id'] ) && module_ticket::can_edit_tickets() ) {
	$search['status_id'] = '2,3,5';
}

$tickets                        = module_ticket::get_tickets( $search, true );
$_SESSION['ticket_last_search'] = $search;
if ( ! isset( $_REQUEST['nonext'] ) ) {
	$_SESSION['_ticket_nextprev'] = array();
	while ( $ticket = mysqli_fetch_assoc( $tickets ) ) {
		$_SESSION['_ticket_nextprev'][] = $ticket['ticket_id'];
	}
	if ( mysqli_num_rows( $tickets ) > 0 ) {
		mysqli_data_seek( $tickets, 0 );
	}
}

$priorities = module_ticket::get_ticket_priorities();

$header = array(
	'title'  => _l( 'Customer Tickets' ),
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
if ( module_ticket::can_i( 'create', 'Tickets' ) ) {
	$header['button'] = array(
		'url'   => module_ticket::link_open( 'new' ),
		'title' => _l( 'Add New Ticket' ),
		'type'  => 'add',
	);
}
print_heading( $header );
?>


<form action="" method="<?php echo _DEFAULT_FORM_METHOD; ?>">

	<input type="hidden" name="customer_id"
	       value="<?php echo isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : ''; ?>">

	<?php

	module_form::print_form_auth();

	$search_bar = array(
		'elements' => array(
			'ticket_id'      => array(
				'title' => _l( 'Number:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[ticket_id]',
					'value' => isset( $search['ticket_id'] ) ? $search['ticket_id'] : '',
					'size'  => 5,
				)
			),
			'name'           => array(
				'title' => _l( 'Subject:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 10,
				)
			),
			'ticket_content' => array(
				'title' => _l( 'Message:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[ticket_content]',
					'value' => isset( $search['ticket_content'] ) ? $search['ticket_content'] : '',
					'size'  => 10,
				)
			),
			'contact'        => array(
				'title' => _l( 'Contact:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[contact]',
					'value' => isset( $search['contact'] ) ? $search['contact'] : '',
					'size'  => 10,
				)
			),
			'date'           => array(
				'title'  => _l( 'Date:' ),
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => 'search[date_from]',
						'value' => isset( $search['date_from'] ) ? $search['date_from'] : '',
					),
					_l( 'to' ),
					array(
						'type'  => 'date',
						'name'  => 'search[date_to]',
						'value' => isset( $search['date_to'] ) ? $search['date_to'] : '',
					),

				)
			),
			'type'           => array(
				'title' => _l( 'Type:' ),
				'field' => array(
					'type'             => 'select',
					'name'             => 'search[ticket_type_id]',
					'value'            => isset( $search['ticket_type_id'] ) ? $search['ticket_type_id'] : '',
					'options'          => module_ticket::get_types(),
					'options_array_id' => 'name',
				)
			),
			'Status'         => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[status_id]',
					'value'   => isset( $search['status_id'] ) ? $search['status_id'] : '',
					'options' => $search_statuses,
					'blank'   => _l( 'All' ),
				)
			),
			'Priority'       => array(
				'title' => _l( 'Priority:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[priority]',
					'value'   => isset( $search['priority'] ) ? $search['priority'] : '',
					'options' => module_ticket::get_ticket_priorities(),
				)
			),
			'Staff'          => array(
				'title' => _l( 'Staff:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[assigned_user_id]',
					'value'   => isset( $search['assigned_user_id'] ) ? $search['assigned_user_id'] : '',
					'options' => module_ticket::get_ticket_staff_rel(),
				)
			),
		)
	);
	if ( class_exists( 'module_faq', false ) && module_config::c( 'ticket_show_product_list', 1 ) ) {
		$search_bar['elements']['Product'] = array(
			'title' => _l( 'Product:' ),
			'field' => array(
				'type'    => 'select',
				'name'    => 'search[faq_product_id]',
				'value'   => isset( $search['faq_product_id'] ) ? $search['faq_product_id'] : '',
				'options' => module_faq::get_faq_products_rel(),
			)
		);
	}
	if ( class_exists( 'module_envato', false ) ) {
		$search_bar['elements']['envato'] = array(
			'title' => _l( 'Envato:' ),
			'field' => array(
				'type'    => 'select',
				'name'    => 'search[envato_item_id]',
				'value'   => isset( $search['envato_item_id'] ) ? $search['envato_item_id'] : '',
				'options' => array( - 1 => 'No product' ) + module_envato::get_envato_items_rel(),
			)
		);
	}
	echo module_form::search_bar( $search_bar );

	if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_show_ticket_earning', 0 ) ) {
		$item_ticket_count = array();
		$envato_count      = module_cache::get( 'ticket', 'envato_ticket_earning' );
		//if($envato_count===false){
		while ( $ticket = mysqli_fetch_assoc( $tickets ) ) {
			$items = module_envato::get_items_by_ticket( $ticket['ticket_id'] );
			if ( count( $items ) ) {
				foreach ( $items as $item_id => $item ) {
					if ( ! isset( $item_ticket_count[ $item_id ] ) ) {
						$item_ticket_count[ $item_id ] = array(
							'envato_id' => $item_id,
							'name'      => $item['name'],
							'count'     => 0,
							'cost'      => $item['cost'],
						);
					}
					$item_ticket_count[ $item_id ]['count'] ++;
					$envato_count += $item['cost'];
				}
			} else {
				$item_id = '-1';
				if ( ! isset( $item_ticket_count[ $item_id ] ) ) {
					$item_ticket_count[ $item_id ] = array(
						'envato_id' => $item_id,
						'name'      => 'No product',
						'count'     => 0,
						'cost'      => 0,
					);
				}
				$item_ticket_count[ $item_id ]['count'] ++;
			}
		}
		if ( mysqli_num_rows( $tickets ) > 0 ) {
			mysqli_data_seek( $tickets, 0 );
		}
		module_cache::put( 'ticket', 'envato_ticket_earning', $envato_count );
		//}
		function sort_envato_ticket_count( $a, $b ) {
			//return ($a['count']*$a['cost'])<=($b['count']*$b['cost']);
			return $a['count'] <= $b['count'];
		}

		uasort( $item_ticket_count, 'sort_envato_ticket_count' );
		foreach ( $item_ticket_count as $i ) {
			?> <a
				href="?search[envato_item_id][]=<?php echo $i['envato_id']; ?>"><?php echo htmlspecialchars( $i['name'] ); ?>
				(<?php echo $i['count']; ?><?php echo $i['cost'] ? ' - ' . dollar( $i['count'] * $i['cost'] ) : ''; ?>
				)</a> <?php
		}
	}


	/** START TABLE LAYOUT **/
	$table_manager             = module_theme::new_table_manager();
	$columns                   = array();
	$columns['ticket_number']  = array(
		'title'      => 'Number',
		'callback'   => function ( $ticket ) {
			echo module_ticket::link_open( $ticket['ticket_id'], true, $ticket );
			echo ' (' . $ticket['message_count'] . ')';
		},
		'cell_class' => 'row_action',
	);
	$columns['ticket_subject'] = array(
		'title'    => 'Subject',
		'callback' => function ( $ticket ) {
			$ticket['subject'] = preg_replace( '#Message sent via your Den#', '', $ticket['subject'] );
			if ( $ticket['priority'] ) {

			}
			if ( $ticket['unread'] ) {
				echo '<strong>';
				echo ' ' . _l( '* ' ) . ' ';
				echo htmlspecialchars( $ticket['subject'] );
				echo '</strong>';
			} else {
				echo htmlspecialchars( $ticket['subject'] );
			}
		},
	);
	$columns['ticket_time']    = array(
		'title'    => 'Last Date/Time',
		'callback' => function ( $ticket ) {
			if ( $ticket['last_message_timestamp'] > 0 ) {
				if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
					echo '<span class="important">';
				}
				echo print_date( $ticket['last_message_timestamp'], true );
				// how many days ago was this?
				echo ' (' . fuzzy_date( $ticket['last_message_timestamp'] ) . ')';
				if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
					echo '</span>';
				}
			}
		},
	);
	if ( module_config::c( 'ticket_allow_due_time', 1 ) ) {
		$columns['ticket_due_time'] = array(
			'title'    => 'Due Date/Time',
			'callback' => function ( $ticket ) {
				if ( ! empty( $ticket['due_timestamp'] ) ) {
					if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
						echo '<span class="important">';
					}
					echo print_date( $ticket['due_timestamp'], true );
					// how many days ago was this?
					echo ' (' . fuzzy_date( $ticket['due_timestamp'] ) . ')';
					if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
						echo '</span>';
					}
				}
			},
		);
	}

	$columns['ticket_type']   = array(
		'title'    => 'Type',
		'callback' => function ( $ticket ) {
			echo htmlspecialchars( $ticket['ticket_type'] );
		},
	);
	$columns['ticket_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $ticket ) {
			echo htmlspecialchars( module_ticket::$ticket_statuses[ $ticket['status_id'] ] );
		},
	);
	$columns['ticket_staff']  = array(
		'title'    => 'Staff',
		'callback' => function ( $ticket ) {
			echo module_user::link_open( $ticket['assigned_user_id'], true );
		},
	);
	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) && module_config::c( 'ticket_list_show_customer', 1 ) ) {
		$columns['ticket_customer'] = array(
			'title'    => 'Customer',
			'callback' => function ( $ticket ) {
				echo module_customer::link_open( $ticket['customer_id'], true );
			},
		);
	}
	$columns['ticket_contact'] = array(
		'title'    => 'Contact',
		'callback' => function ( $ticket ) {
			echo module_user::link_open( $ticket['user_id'], true, array(), true );
		},
	);
	if ( class_exists( 'module_faq', false ) && module_config::c( 'ticket_show_product_list', 1 ) ) {
		$columns['ticket_product'] = array(
			'title'    => 'Product',
			'callback' => function ( $ticket ) {
				if ( $ticket['faq_product_id'] ) {
					$faq_product = module_faq::get_faq_product( $ticket['faq_product_id'] );
					echo $faq_product && isset( $faq_product['name'] ) ? htmlspecialchars( $faq_product['name'] ) : '';
				}
			},
		);
	}
	if ( class_exists( 'module_envato', false ) ) {
		$columns['ticket_envato'] = array(
			'title'    => _l( 'Envato%s', module_config::c( 'envato_show_ticket_earning', 0 ) ? ' (' . dollar( $envato_count * .7 ) . ')' : '' ),
			'callback' => function ( $ticket ) {
				$items = module_envato::get_items_by_ticket( $ticket['ticket_id'] );
				foreach ( $items as $item ) {
					echo '<a href="' . $item['url'] . '">' . htmlspecialchars( $item['name'] ) . '</a> ';
				}
			},
		);
	}
	if ( class_exists( 'module_group', false ) && module_config::c( 'ticket_enable_groups', 1 ) && module_group::groups_enabled() ) {
		$columns['ticket_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $ticket ) {
				// find the groups for this customer.
				$groups = module_group::get_groups_search( array(
					'owner_table' => 'ticket',
					'owner_id'    => $ticket['ticket_id'],
				) );
				$g      = array();
				foreach ( $groups as $group ) {
					$g[] = $group['name'];
				}
				echo implode( ', ', $g );
			},
		);
	}
	if ( module_config::c( 'ticket_allow_priority', 0 ) && module_config::c( 'ticket_show_priority', 1 ) ) {
		$columns['ticket_priority'] = array(
			'title'    => 'Priority',
			'callback' => function ( $ticket ) use ( $priorities ) {
				echo $priorities[ $ticket['priority'] ];
			},
		);
	}
	if ( module_ticket::can_edit_tickets() ) {
		$columns['ticket_action'] = array(
			'title'    => ' <input type="checkbox" name="bulk_operation_all" id="bulk_operation_all" value="yehaw" > ',
			'callback' => function ( $ticket ) {
				echo '<input type="checkbox" name="bulk_operation[' . $ticket['ticket_id'] . ']" class="ticket_bulk_check" value="yes">';
			}
		);
	}
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'ticket', function ( $ticket ) {
			module_extra::print_table_data( 'ticket', $ticket['ticket_id'] );
		} );
	}
	$table_manager->set_columns( $columns );
	$time                        = time();
	$today                       = strtotime( date( 'Y-m-d' ) );
	$table_manager->row_callback = function ( $row_data ) use ( $time, $today, $limit_time ) {
		// load the full vendor data before displaying each row so we have access to more details
		/*if(class_exists('module_envato',false) && isset($_REQUEST['faq_product_envato_hack']) && (!$ticket['faq_product_id'] || $ticket['faq_product_id'] == $_REQUEST['faq_product_envato_hack'])){
		}*/
		$return = array();
		if ( isset( $row_data['ticket_id'] ) && (int) $row_data['ticket_id'] > 0 ) {
			$return = module_ticket::get_ticket( $row_data['ticket_id'] );
			if ( ! empty( $return['due_timestamp'] ) ) {
				$return['limit_time'] = $return['due_timestamp'];
			}
		}
		$return['time']  = $time;
		$return['today'] = $today;
		if ( empty( $return['limit_time'] ) ) {
			$return['limit_time'] = $limit_time;
		}

		return $return;
	};
	$table_manager->set_rows( $tickets );
	$table_manager->table_id    = 'ticket_list';
	$table_manager->table_class = $table_manager->table_class . ' '; //tbl_fixed // fixed width cells to stop overflowing. see ticket.css
	$table_manager->pagination  = true;
	$table_manager->print_table();


	?>
</form>