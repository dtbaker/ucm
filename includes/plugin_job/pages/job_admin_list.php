<?php

if ( ! $job_safe ) {
	die( 'denied' );
}

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['customer_id'] ) ) {
	$search['customer_id'] = $_REQUEST['customer_id'];
}
if ( ! isset( $search['completed'] ) ) {
	$search['completed'] = module_config::c( 'job_search_completed_default', 1 );
}
$reset_archived = false;
if ( ! isset( $search['archived_status'] ) ) {
	$reset_archived            = true;
	$search['archived_status'] = _ARCHIVED_SEARCH_NONARCHIVED;
}
$jobs = module_job::get_jobs( $search );
if ( $reset_archived ) {
	unset( $search['archived_status'] );
}

// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_job::can_i( 'view', 'Export Jobs' ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this customers?
		array(
			'name'   => 'Job Export',
			'fields' => array(
				'Job ID'                                                       => 'job_id',
				'Job Title'                                                    => 'name',
				'Hourly Rate'                                                  => 'hourly_rate',
				'Start Date'                                                   => 'date_start',
				'Due Date'                                                     => 'date_due',
				'Completed Date'                                               => 'date_completed',
				module_config::c( 'project_name_single', 'Website' ) . ' Name' => 'website_name',
				'Customer Name'                                                => 'customer_name',
				'Type'                                                         => 'type',
				'Status'                                                       => 'status',
				'Staff Member'                                                 => 'staff_member',
				'Tax Name'                                                     => 'total_tax_name',
				'Tax Percent'                                                  => 'total_tax_rate',
				'Renewal Date'                                                 => 'date_renew',
			),
			// do we look for extra fields?
			'extra'  => array(
				'owner_table' => 'job',
				'owner_id'    => 'job_id',
			),
		)
	);
}

$header = array(
	'title'  => _l( 'Customer Jobs' ),
	'main'   => true,
	'button' => array()
);
if ( module_job::can_i( 'create', 'Jobs' ) ) {
	$header['button'][] = array(
		'url'   => module_job::link_open( 'new' ),
		'title' => 'Add New Job',
		'type'  => 'add',
	);
}
if ( class_exists( 'module_import_export', false ) && module_job::can_i( 'view', 'Import Jobs' ) ) {
	$link               = module_import_export::import_link(
		array(
			'callback'         => 'module_job::handle_import',
			'callback_preview' => 'module_job::handle_import_row_debug',
			'name'             => 'Jobs',
			'return_url'       => $_SERVER['REQUEST_URI'],
			'group'            => 'job',
			'fields'           => array(
				'Job ID'                                                       => 'job_id',
				'Job Title'                                                    => 'name',
				'Hourly Rate'                                                  => 'hourly_rate',
				'Start Date'                                                   => 'date_start',
				'Due Date'                                                     => 'date_due',
				'Completed Date'                                               => 'date_completed',
				module_config::c( 'project_name_single', 'Website' ) . ' Name' => 'website_name',
				'Customer Name'                                                => 'customer_name',
				'Type'                                                         => 'type',
				'Status'                                                       => 'status',
				'Staff Member'                                                 => 'staff_member',
				'Tax Name'                                                     => 'total_tax_name',
				'Tax Percent'                                                  => 'total_tax_rate',
				'Renewal Date'                                                 => 'date_renew',
			),
			// do we attempt to import extra fields?
			'extra'            => array(
				'owner_table' => 'job',
				'owner_id'    => 'job_id',
			),
		)
	);
	$header['button'][] = array(
		'url'   => $link,
		'title' => 'Import Jobs',
		'type'  => 'add',
	);
}
print_heading( $header );
?>


<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name'      => array(
				'title' => _l( 'Job Title:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 30,
				)
			),
			'type'      => array(
				'title' => _l( 'Type:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[type]',
					'value'   => isset( $search['type'] ) ? $search['type'] : '',
					'options' => module_job::get_types(),
				)
			),
			'status'    => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[status]',
					'value'   => isset( $search['status'] ) ? $search['status'] : '',
					'options' => module_job::get_statuses(),
				)
			),
			'completed' => array(
				'title' => _l( 'Completed:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[completed]',
					'value'   => isset( $search['completed'] ) ? $search['completed'] : '',
					'options' => array(
						1 => _l( 'Both Completed and Non-Completed Jobs' ),
						2 => _l( 'Only Completed Jobs' ),
						3 => _l( 'Only Non-Completed Jobs' ),
					),
				)
			),
		)
	);
	$staff            = module_user::get_staff_members();
	if ( count( $staff ) > 0 ) {
		$search_bar['elements'][] = array(
			'title' => _l( 'Staff:' ),
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[user_id]',
				'value'            => isset( $search['user_id'] ) ? $search['user_id'] : '',
				'options'          => $staff,
				'options_array_id' => 'name',
			)
		);
	}

	if ( module_config::c( 'job_allow_quotes', 0 ) ) {
		$search_bar['elements']['completed']['field']['options'][4] = _l( 'Only Quoted Jobs' );
	}
	if ( class_exists( 'module_extra', false ) ) {
		$search_bar['extra_fields'] = 'job';
	}
	if ( class_exists( 'module_group', false ) && module_job::can_i( 'view', 'Job Groups' ) ) {
		$search_bar['elements']['group_id'] = array(
			'title' => false,
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[group_id]',
				'value'            => isset( $search['group_id'] ) ? $search['group_id'] : '',
				'options'          => module_group::get_groups( 'job' ),
				'options_array_id' => 'name',
				'blank'            => _l( ' - Group - ' ),
			)
		);
	}

	$search_bar['elements']['archived'] = array(
		'title' => false,
		'field' => array(
			'type'    => 'select',
			'name'    => 'search[archived_status]',
			'value'   => isset( $search['archived_status'] ) ? $search['archived_status'] : '',
			'options' => array(
				_ARCHIVED_SEARCH_NONARCHIVED => 'Only Unarchived Items',
				_ARCHIVED_SEARCH_ARCHIVED    => 'Only Archived Items',
				_ARCHIVED_SEARCH_BOTH        => 'Both Unarchived and Archived',
			),
			'blank'   => _l( ' - Archived - ' ),
		)
	);
	echo module_form::search_bar( $search_bar );


	/** START TABLE LAYOUT **/
	$table_manager                 = module_theme::new_table_manager();
	$columns                       = array();
	$columns['job_title']          = array(
		'title'      => 'Job Title',
		'callback'   => function ( $job ) {
			echo module_job::link_open( $job['job_id'], true, $job );
		},
		'cell_class' => 'row_action',
	);
	$columns['job_start_date']     = array(
		'title'    => 'Date',
		'callback' => function ( $job ) {
			echo print_date( $job['date_start'] );
			//is there a renewal date?
			if ( isset( $job['date_renew'] ) && $job['date_renew'] && $job['date_renew'] != '0000-00-00' ) {
				_e( ' to %s', print_date( strtotime( "-1 day", strtotime( $job['date_renew'] ) ) ) );
			}
		},
	);
	$columns['job_due_date']       = array(
		'title'    => 'Due Date',
		'callback' => function ( $job ) {
			if ( $job['total_percent_complete'] != 1 && strtotime( $job['date_due'] ) < time() ) {
				echo '<span class="error_text">';
				echo print_date( $job['date_due'] );
				echo '</span>';
			} else {
				echo print_date( $job['date_due'] );
			}
		},
	);
	$columns['job_completed_date'] = array(
		'title'    => 'Completed Date',
		'callback' => function ( $job ) {
			echo print_date( $job['date_completed'] );
		},
	);
	if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() && module_website::can_i( 'view', module_config::c( 'project_name_plural', 'Websites' ) ) ) {
		$columns['job_website'] = array(
			'title'    => module_config::c( 'project_name_single', 'Website' ),
			'callback' => function ( $job ) {
				echo module_website::link_open( $job['website_id'], true );
			},
		);
	}
	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
		$columns['job_customer'] = array(
			'title'    => 'Customer',
			'callback' => function ( $job ) {
				echo module_customer::link_open( $job['customer_id'], true );
			},
		);
	}
	$columns['job_type']   = array(
		'title'    => 'Type',
		'callback' => function ( $job ) {
			echo htmlspecialchars( $job['type'] );
		},
	);
	$columns['job_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $job ) {
			echo htmlspecialchars( $job['status'] );
		},
	);
	if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) {
		$columns['job_staff'] = array(
			'title'    => 'Staff Member',
			'callback' => function ( $job ) {
				echo module_user::link_open( $job['user_id'], true );
			},
		);
	}
	$columns['job_progress'] = array(
		'title'    => 'Progress',
		'type'     => 'progress_bar',
		'callback' => function ( $job ) {
			?> <span data-percent="<?php echo( $job['total_percent_complete'] * 100 ); ?>"
			         class="progress_bar <?php echo $job['total_percent_complete'] >= 1 ? 'success_text' : ''; ?>">
            <?php echo ( $job['total_percent_complete'] * 100 ) . '%'; ?>
        </span> <?php
		},
	);
	if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
		$columns['job_total']                 = array(
			'title'    => 'Job Total',
			'callback' => function ( $job ) {

				if ( module_job::is_staff_view( $job ) ) {

				} else {
					?><span class="currency">
					<?php echo dollar( $job['total_amount'], true, $job['currency_id'] ); ?>
					</span>
					<?php
					if ( $job['total_amount_invoiced'] > 0 && $job['total_amount'] != ( $job['total_amount_invoiced'] ) ) { //+$job['total_amount_invoiced_deposit']
						?>
						<br/>
						<span class="currency">
                (<?php echo dollar( $job['total_amount_invoiced'], true, $job['currency_id'] ); ?>)
                </span>
					<?php }
				}
			},
		);
		$columns['job_total_amount_invoiced'] = array(
			'title'    => 'Invoice',
			'callback' => function ( $job ) {
				foreach ( module_invoice::get_invoices( array( 'job_id' => $job['job_id'] ) ) as $invoice ) {
					$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					if ( ! $invoice ) {
						continue;
					}
					echo module_invoice::link_open( $invoice['invoice_id'], true );
					echo " ";
					echo '<span class="';
					if ( $invoice['total_amount_due'] > 0 ) {
						echo 'error_text';
					} else {
						echo 'success_text';
					}
					echo '">';
					if ( $invoice['total_amount_due'] > 0 ) {
						echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
						echo ' ' . _l( 'due' );
					} else {
						echo _l( '%s paid', dollar( $invoice['total_amount'], true, $invoice['currency_id'] ) );
					}
					echo '</span>';
					echo "<br>";
				}
			},
		);
	}

	if ( class_exists( 'module_group', false ) ) {
		$columns['job_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $job ) {
				if ( isset( $job['group_sort_job'] ) ) {
					echo htmlspecialchars( $job['group_sort_job'] );
				} else {
					// find the groups for this job.
					$groups = module_group::get_groups_search( array(
						'owner_table' => 'job',
						'owner_id'    => $job['job_id'],
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
		$table_manager->display_extra( 'job', function ( $job ) {
			module_extra::print_table_data( 'job', $job['job_id'] );
		}, 'job_id' );
	}
	$table_manager->enable_table_sorting( array(
			'table_id' => 'job_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'job_title'                 => array(
					'field'   => 'name',
					'current' => 1, // 1 asc, 2 desc
				),
				'job_start_date'            => array(
					'field' => 'date_start',
				),
				'job_due_date'              => array(
					'field' => 'date_due',
				),
				'job_completed_date'        => array(
					'field' => 'date_completed',
				),
				'job_website'               => array(
					'field' => 'website_name',
				),
				'job_customer'              => array(
					'field' => 'customer_name',
				),
				'job_type'                  => array(
					'field' => 'type',
				),
				'job_status'                => array(
					'field' => 'status',
				),
				'job_progress'              => array(
					'field' => 'total_percent_complete',
				),
				'job_total'                 => array(
					'field' => 'total_amount',
				),
				'job_total_amount_invoiced' => array(
					'field' => 'total_amount_invoiced',
				),
				// special case for group sorting.
				'job_group'                 => array(
					'group_sort'  => true,
					'owner_table' => 'job',
					'owner_id'    => 'job_id',
				),
			),
		)
	);

	if ( class_exists( 'module_table_sort', false ) ) {

		if ( isset( $_REQUEST['table_sort_column'] ) || ( isset( $_SESSION['_table_sort'] ) && isset( $_SESSION['_table_sort']['job_list'] ) && isset( $_SESSION['_table_sort']['job_list'][0] ) ) ) {
			// we're sorting by something!
			reset( $jobs );
			$test = current( $jobs );
			if ( $test && $test['job_id'] ) {
				$column = isset( $_REQUEST['table_sort_column'] ) ? $_REQUEST['table_sort_column'] : $_SESSION['_table_sort']['job_list'][0];
				if ( isset( module_table_sort::$table_sort_options['sortable'][ $column ] ) ) {
					$dbcolumn = module_table_sort::$table_sort_options['sortable'][ $column ]['field'];
					if ( ! isset( $test[ $dbcolumn ] ) ) {
						$test = module_job::get_job( $test['job_id'] );
						if ( isset( $test[ $dbcolumn ] ) ) {
							// load all job data (EEP!) so we can sort better
							foreach ( $jobs as $job_id => $job ) {
								$full_job = module_job::get_job( $job['job_id'] );
								if ( isset( $full_job[ $dbcolumn ] ) ) {
									$jobs[ $job_id ][ $dbcolumn ] = $full_job[ $dbcolumn ];
								}
							}
						}
					}
				}
			}
		}
	}

	$table_manager->set_id( 'job_list' );
	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_job::get_job( $row_data['job_id'] );
	};
	$table_manager->set_rows( $jobs );
	$table_manager->pagination = true;
	$table_manager->print_table();
	?>
</form>