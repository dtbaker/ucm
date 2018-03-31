<?php

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();

if ( isset( $_GET['customer_id'] ) ) {
	$search['customer_id'] = (int) $_GET['customer_id'];
}
$ucmtimers = new UCMTimers();
$timers    = $ucmtimers->get( $search, array( 'timer_id' => 'DESC' ) );


if ( class_exists( 'module_group', false ) ) {
	// this adds the bulk "add to group" feature at the bottom.
	module_group::enable_pagination_hook(
		array(
			'fields' => array(
				'owner_id'    => 'timer_id',
				'owner_table' => 'timer',
			),
		)
	);
}

if ( class_exists( 'module_table_sort', false ) ) {
	// this adds the "column sorting" to the table output
	module_table_sort::enable_pagination_hook(
		array(
			'table_id' => 'timer_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'timer_status'  => array(
					'field' => 'timer_status',
				),
				'customer_id'   => array(
					'field' => 'customer_id',
				),
				'start_time'    => array(
					'field' => 'start_time',
				),
				'end_time'      => array(
					'field' => 'end_time',
				),
				'duration_calc' => array(
					'field' => 'duration_calc',
				),
				'invoice_id'    => array(
					'field' => 'invoice_id',
				),
				'user_id'       => array(
					'field' => 'user_id',
				),
				// special case for group sorting.
				'timer_group'   => array(
					'group_sort'  => true,
					'owner_table' => 'timer',
					'owner_id'    => 'timer_id',
				),
			),
		)
	);
}
if ( class_exists( 'module_import_export', false ) && module_timer::can_i( 'view', 'Export Timers' ) ) {
	// this adds the 'export' button at teh bottom of timers
	module_import_export::enable_pagination_hook(
		array(
			'name'   => 'Timer Export',
			'fields' => array(
				'Timer ID'       => 'timer_id',
				'Start Time'     => 'start_time',
				'End Time'       => 'end_time',
				'Timer Duration' => 'duration_calc',
				'Timer Status'   => 'timer_status',
				'Owner'          => 'owner_table',
				'Owner ID'       => 'owner_id',
			),
			// do we look for extra fields?
			'extra'  => array(
				'owner_table' => 'timer',
				'owner_id'    => 'timer_id',
			),
		)
	);
}
$header_buttons = array();
if ( module_timer::can_i( 'create', 'Timers' ) ) {
	$header_buttons[] = array(
		'url'        => module_timer::link_open( 'new' ),
		'type'       => 'add',
		'title'      => _l( 'Start New Timer' ),
		'ajax-modal' => array(
			'type' => 'normal',
		),
	);
}
print_heading( array(
	'type'   => 'h2',
	'main'   => true,
	'title'  => _l( 'Timers' ),
	'button' => $header_buttons,
) )
?>


<form action="" method="post">


	<?php $search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[timer_status]',
					'value'   => isset( $search['timer_status'] ) ? $search['timer_status'] : '',
					'options' => module_timer::get_statuses(),
				)
			),
		)
	);

	if ( class_exists( 'module_group', false ) ) {
		$search_bar['elements']['group_id'] = array(
			'title' => false,
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[group_id]',
				'value'            => isset( $search['group_id'] ) ? $search['group_id'] : '',
				'options'          => module_group::get_groups( 'timer' ),
				'options_array_id' => 'name',
				'blank'            => _l( ' - Group - ' ),
			)
		);
	}

	echo module_form::search_bar( $search_bar );


	/** START TABLE LAYOUT **/
	// todo: also update website_admin_edit.php if this layout changes.
	// todo: merge this code with website_admin_edit.php so they both run at same time. hook or include or something.
	$table_manager         = module_theme::new_table_manager();
	$columns               = array();
	$columns['timer_name'] = array(
		'title'      => 'Description',
		'callback'   => function ( $timer ) {
			echo module_timer::link_open( $timer['timer_id'], true, $timer );
		},
		'cell_class' => 'row_action',
	);

	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
		$columns['customer_id'] = array(
			'title'    => 'Customer',
			'callback' => function ( $timer ) {
				echo module_customer::link_open( $timer['customer_id'], true );
			},
		);
	}

	$columns['linked_data'] = array(
		'title'    => 'Linked',
		'callback' => function ( $timer ) {
			// re-use the autocomplete code to show this information.
			global $plugins;
			if ( ! empty( $timer['owner_table'] ) && ! empty( $timer['owner_id'] ) ) {
				$data = $plugins['timer']->autocomplete_display( $timer['owner_id'], array(
					'owner_table' => $timer['owner_table'],
					'return_link' => true,
				) );
				if ( ! empty( $data ) && is_array( $data ) ) {
					echo $data[2];
					echo ': <a href="' . htmlspecialchars( $data[1] ) . '">' . htmlspecialchars( $data[0] ) . '</a>';
					if ( ! empty( $timer['owner_child_id'] ) ) {
						echo ' (' . module_timer::get_child_id_link( $timer ) . ')';
					}
				}
			}
		},
	);

	$columns['timer_status']   = array(
		'title'    => 'Status',
		'callback' => function ( $timer ) {
			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			echo $ucmtimer->get_status_text();
		},
	);
	$columns['start_time']     = array(
		'title'    => 'Start Time',
		'callback' => function ( $timer ) {
			echo print_date( $timer['start_time'], true );
		},
	);
	$columns['timer_duration'] = array(
		'title'    => 'Duration',
		'callback' => function ( $timer ) {
			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			echo $ucmtimer->get_total_time();
		},
	);

	$columns['user_id']    = array(
		'title'    => 'User',
		'callback' => function ( $timer ) {
			if ( ! empty( $timer['user_id'] ) ) {
				echo module_user::link_open( $timer['user_id'], true );
			}
		},
	);
	$columns['invoice_id'] = array(
		'title'    => 'Billable',
		'callback' => function ( $timer ) {
			if ( ! empty( $timer['billable'] ) ) {
				_e( 'Yes' );
			} else {
				_e( 'No' );
			}
			if ( ! empty( $timer['invoice_id'] ) ) {
				echo ' ' . module_invoice::link_open( $timer['invoice_id'], true );
			}
		},
	);

	if ( class_exists( 'module_group', false ) ) {
		$columns['timer_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $timer ) {
				if ( isset( $timer['group_sort_timer'] ) ) {
					echo htmlspecialchars( $timer['group_sort_timer'] );
				} else {
					// find the groups for this timer.
					$groups = module_group::get_groups_search( array(
						'owner_table' => 'timer',
						'owner_id'    => $timer['timer_id'],
					) );
					$g      = array();
					foreach ( $groups as $group ) {
						$g[] = $group['name'];
					}
					echo htmlspecialchars( implode( ', ', $g ) );
				}
			}
		);
	}
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'timer', function ( $timer ) {
			module_extra::print_table_data( 'timer', $timer['timer_id'] );
		} );
	}

	$columns['timer_action'] = array(
		'title'    => 'Action',
		'callback' => function ( $timer ) {

			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			if ( $ucmtimer->timer_status == _TIMER_STATUS_RUNNING && module_timer::can_i( 'edit', 'Timers' ) ) {
				$latest = $ucmtimer->get_latest();
				if ( $latest ) {
					?>
					<a
						href="<?php echo $ucmtimer->link_open( false ); ?>&_process=complete_timer&timer_segment_id=<?php echo (int) $latest['timer_segment_id']; ?>&form_auth_key=<?php echo module_form::get_secure_key(); ?>"
						title="<?php _e( 'Completed' ); ?>"><i
							class="fa fa-check-square"></i></a>
					<?php
				}
			} else if ( module_timer::can_i( 'delete', 'Timers' ) ) {
				?>
				<a href="<?php echo $ucmtimer->link_open( false ); ?>&_process=save_timer&butt_del=true&return="
				   data-ajax-modal='{"type":"normal","title":"<?php _e( 'Delete' ); ?>"}'><i
						class="fa fa-times-circle-o"></i></a>
				<?php
			}

		},
	);


	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $timers );
	$table_manager->pagination = true;
	$table_manager->print_table();
	?>
</form>