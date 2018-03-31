<?php

if ( ! $quote_safe ) {
	die( 'denied' );
}

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['customer_id'] ) ) {
	$search['customer_id'] = $_REQUEST['customer_id'];
}
if ( ! isset( $search['completed'] ) ) {
	$search['completed'] = module_config::c( 'quote_search_completed_default', 1 );
}

$reset_archived = false;
if ( ! isset( $search['archived_status'] ) ) {
	$reset_archived            = true;
	$search['archived_status'] = _ARCHIVED_SEARCH_NONARCHIVED;
}
$quotes = module_quote::get_quotes( $search );
if ( $reset_archived ) {
	unset( $search['archived_status'] );
}


if ( class_exists( 'module_table_sort', false ) ) {

	// get full quote data.
	// todo: only grab data if we're sorting by something
	// that isn't in the default invoice listing.
	foreach ( $quotes as $quote_id => $quote ) {
		$quotes[ $quote_id ]                 = array_merge( $quote, module_quote::get_quote( $quote['quote_id'] ) );
		$quotes[ $quote_id ]['website_name'] = $quote['website_name'];
	}

	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'quote_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'quote_title'                 => array(
					'field'   => 'name',
					'current' => 1, // 1 asc, 2 desc
				),
				'quote_start_date'            => array(
					'field' => 'date_create',
				),
				'quote_completed_date'        => array(
					'field' => 'date_approved',
				),
				'quote_website'               => array(
					'field' => 'website_name',
				),
				'quote_customer'              => array(
					'field' => 'customer_name',
				),
				'quote_type'                  => array(
					'field' => 'type',
				),
				'quote_status'                => array(
					'field' => 'status',
				),
				'quote_progress'              => array(
					'field' => 'total_percent_complete',
				),
				'quote_total'                 => array(
					'field' => 'total_amount',
				),
				'quote_total_amount_invoiced' => array(
					'field' => 'total_amount_invoiced',
				),
				// special case for group sorting.
				'quote_group'                 => array(
					'group_sort'  => true,
					'owner_table' => 'quote',
					'owner_id'    => 'quote_id',
				),
			),
		)
	);
}

// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_quote::can_i( 'view', 'Export Quotes' ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this customers?
		array(
			'name'   => 'Quote Export',
			'fields' => array(
				'Quote ID'                                                     => 'quote_id',
				'Quote Title'                                                  => 'name',
				'Hourly Rate'                                                  => 'hourly_rate',
				'Start Date'                                                   => 'date_create',
				'Completed Date'                                               => 'date_approved',
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
				'owner_table' => 'quote',
				'owner_id'    => 'quote_id',
			),
		)
	);
}

$header = array(
	'title'  => _l( 'Customer Quotes' ),
	'main'   => true,
	'button' => array()
);
if ( module_quote::can_i( 'create', 'Quotes' ) ) {
	$header['button'][] = array(
		'url'   => module_quote::link_open( 'new' ),
		'title' => 'Add New Quote',
		'type'  => 'add',
	);
}
if ( class_exists( 'module_import_export', false ) && module_quote::can_i( 'view', 'Import Quotes' ) ) {
	$link               = module_import_export::import_link(
		array(
			'callback'         => 'module_quote::handle_import',
			'callback_preview' => 'module_quote::handle_import_row_debug',
			'name'             => 'Quotes',
			'return_url'       => $_SERVER['REQUEST_URI'],
			'group'            => 'quote',
			'fields'           => array(
				//'Quote ID' => 'quote_id',
				'Quote Title'                                                  => 'name',
				'Hourly Rate'                                                  => 'hourly_rate',
				'Start Date'                                                   => 'date_create',
				'Completed Date'                                               => 'date_approved',
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
				'owner_table' => 'quote',
				'owner_id'    => 'quote_id',
			),
		)
	);
	$header['button'][] = array(
		'url'   => $link,
		'title' => 'Import Quotes',
		'type'  => 'add',
	);
}
print_heading( $header );
?>


<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name'     => array(
				'title' => _l( 'Quote Title:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 30,
				)
			),
			'type'     => array(
				'title' => _l( 'Type:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[type]',
					'value'   => isset( $search['type'] ) ? $search['type'] : '',
					'options' => module_quote::get_types(),
				)
			),
			'status'   => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[status]',
					'value'   => isset( $search['status'] ) ? $search['status'] : '',
					'options' => module_quote::get_statuses(),
				)
			),
			'accepted' => array(
				'title' => _l( 'Accepted:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[accepted]',
					'value'   => isset( $search['accepted'] ) ? $search['accepted'] : '',
					'options' => array(
						1 => _l( 'Both Accepted and Un-Accepted Quotes' ),
						2 => _l( 'Only Accepted Quotes' ),
						3 => _l( 'Only Un-Accepted Quotes' ),
					),
				)
			),
		)
	);

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

	$table_manager                   = module_theme::new_table_manager();
	$columns                         = array();
	$columns['quote_title']          = array(
		'title'      => 'Quote Title',
		'callback'   => function ( $quote ) {
			echo module_quote::link_open( $quote['quote_id'], true, $quote );
		},
		'cell_class' => 'row_action',
	);
	$columns['quote_start_date']     = array(
		'title'    => 'Create Date',
		'callback' => function ( $quote ) {
			echo print_date( $quote['date_create'] );
		},
	);
	$columns['quote_completed_date'] = array(
		'title'    => 'Accepted Date',
		'callback' => function ( $quote ) {
			echo print_date( $quote['date_approved'] );
		},
	);
	if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
		$columns['quote_website'] = array(
			'title'    => module_config::c( 'project_name_single', 'Website' ),
			'callback' => function ( $quote ) {
				echo module_website::link_open( $quote['website_id'], true );
			},
		);
	}
	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
		$columns['quote_customer'] = array(
			'title'    => 'Customer',
			'callback' => function ( $quote ) {
				echo module_customer::link_open( $quote['customer_id'], true );
			},
		);
	}
	$columns['quote_type']   = array(
		'title'    => 'Type',
		'callback' => function ( $quote ) {
			echo htmlspecialchars( $quote['type'] );
		},
	);
	$columns['quote_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $quote ) {
			echo htmlspecialchars( $quote['status'] );
		},
	);
	if ( module_config::c( 'quote_allow_staff_assignment', 1 ) ) {
		$columns['quote_staff'] = array(
			'title'    => 'Staff Member',
			'callback' => function ( $quote ) {
				echo module_user::link_open( $quote['user_id'], true );
			},
		);
	}
	if ( module_job::can_i( 'view', 'Jobs' ) ) {
		$job_ids        = array();
		$columns['job'] = array(
			'title'    => 'Job',
			'callback' => function ( $quote ) use ( &$job_ids ) {
				$job_ids = array();
				foreach ( module_job::get_jobs( array( 'quote_id' => $quote['quote_id'] ) ) as $job ) {
					$job = module_job::get_job( $job['job_id'] );
					if ( ! $job ) {
						continue;
					}
					echo module_job::link_open( $job['job_id'], true );
					$job_ids[] = $job['job_id'];
					echo " ";
					echo '<span class="';
					if ( $job['total_amount_due'] > 0 ) {
						echo 'error_text';
					} else {
						echo 'success_text';
					}
					echo '">';
					if ( $job['total_amount'] > 0 ) {
						echo dollar( $job['total_amount'], true, $job['currency_id'] );
					}
					echo '</span>';
					echo "<br>";
				}
			},
		);
		if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
			$columns['invoice'] = array(
				'title'    => 'Invoice',
				'callback' => function ( $quote ) use ( &$job_ids ) {
					foreach ( $job_ids as $job_id ) {
						foreach ( module_invoice::get_invoices( array( 'job_id' => $job_id ) ) as $invoice ) {
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
					}
				},
			);
		}
	}
	if ( class_exists( 'module_group', false ) ) {
		$columns['group'] = array(
			'title'    => 'Group',
			'callback' => function ( $quote ) {
				$groups = module_group::get_groups_search( array(
					'owner_table' => 'quote',
					'owner_id'    => $quote['quote_id'],
				) );
				$g      = array();
				foreach ( $groups as $group ) {
					$g[] = $group['name'];
				}
				echo implode( ', ', $g );
			}
		);
	}

	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_quote::get_quote( $row_data['quote_id'] );
	};
	$table_manager->set_rows( $quotes );
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'quote', function ( $quote ) {
			module_extra::print_table_data( 'quote', $quote['quote_id'] );
		} );
	}
	$table_manager->pagination = true;
	$table_manager->print_table();

	?>

</form>