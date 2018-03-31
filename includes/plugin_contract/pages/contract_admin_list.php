<?php

if ( ! $contract_safe ) {
	die( 'denied' );
}

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['customer_id'] ) ) {
	$search['customer_id'] = $_REQUEST['customer_id'];
}
if ( ! isset( $search['completed'] ) ) {
	$search['completed'] = module_config::c( 'contract_search_completed_default', 1 );
}

$reset_archived = false;
if ( ! isset( $search['archived_status'] ) ) {
	$reset_archived            = true;
	$search['archived_status'] = _ARCHIVED_SEARCH_NONARCHIVED;
}
$contracts = module_contract::get_contracts( $search );
if ( $reset_archived ) {
	unset( $search['archived_status'] );
}


if ( class_exists( 'module_table_sort', false ) ) {

	// get full contract data.
	// todo: only grab data if we're sorting by something
	// that isn't in the default invoice listing.
	foreach ( $contracts as $contract_id => $contract ) {
		$contracts[ $contract_id ]                 = array_merge( $contract, module_contract::get_contract( $contract['contract_id'] ) );
		$contracts[ $contract_id ]['website_name'] = $contract['website_name'];
	}

	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'contract_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'contract_title'                 => array(
					'field'   => 'name',
					'current' => 1, // 1 asc, 2 desc
				),
				'contract_start_date'            => array(
					'field' => 'date_create',
				),
				'contract_completed_date'        => array(
					'field' => 'date_approved',
				),
				'contract_website'               => array(
					'field' => 'website_name',
				),
				'contract_customer'              => array(
					'field' => 'customer_name',
				),
				'contract_type'                  => array(
					'field' => 'type',
				),
				'contract_status'                => array(
					'field' => 'status',
				),
				'contract_progress'              => array(
					'field' => 'total_percent_complete',
				),
				'contract_total'                 => array(
					'field' => 'total_amount',
				),
				'contract_total_amount_invoiced' => array(
					'field' => 'total_amount_invoiced',
				),
				// special case for group sorting.
				'contract_group'                 => array(
					'group_sort'  => true,
					'owner_table' => 'contract',
					'owner_id'    => 'contract_id',
				),
			),
		)
	);
}

// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_contract::can_i( 'view', 'Export Contracts' ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this customers?
		array(
			'name'   => 'Contract Export',
			'fields' => array(
				'Contract ID'                                                  => 'contract_id',
				'Contract Title'                                               => 'name',
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
				'owner_table' => 'contract',
				'owner_id'    => 'contract_id',
			),
		)
	);
}

$header = array(
	'title'  => _l( 'Customer Contracts' ),
	'main'   => true,
	'button' => array()
);
if ( module_contract::can_i( 'create', 'Contracts' ) ) {
	$header['button'][] = array(
		'url'   => module_contract::link_open( 'new' ),
		'title' => 'Add New Contract',
		'type'  => 'add',
	);
}
if ( class_exists( 'module_import_export', false ) && module_contract::can_i( 'view', 'Import Contracts' ) ) {
	$link               = module_import_export::import_link(
		array(
			'callback'         => 'module_contract::handle_import',
			'callback_preview' => 'module_contract::handle_import_row_debug',
			'name'             => 'Contracts',
			'return_url'       => $_SERVER['REQUEST_URI'],
			'group'            => 'contract',
			'fields'           => array(
				//'Contract ID' => 'contract_id',
				'Contract Title'                                               => 'name',
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
				'owner_table' => 'contract',
				'owner_id'    => 'contract_id',
			),
		)
	);
	$header['button'][] = array(
		'url'   => $link,
		'title' => 'Import Contracts',
		'type'  => 'add',
	);
}
print_heading( $header );
?>


<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Contract Title:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 30,
				)
			),
			'type' => array(
				'title' => _l( 'Type:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[type]',
					'value'   => isset( $search['type'] ) ? $search['type'] : '',
					'options' => module_contract::get_types(),
				)
			),

			'accepted' => array(
				'title' => _l( 'Accepted:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[accepted]',
					'value'   => isset( $search['accepted'] ) ? $search['accepted'] : '',
					'options' => array(
						1 => _l( 'Both Accepted and Un-Accepted Contracts' ),
						2 => _l( 'Only Accepted Contracts' ),
						3 => _l( 'Only Un-Accepted Contracts' ),
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

	$table_manager                      = module_theme::new_table_manager();
	$columns                            = array();
	$columns['contract_title']          = array(
		'title'      => 'Contract Title',
		'callback'   => function ( $contract ) {
			echo module_contract::link_open( $contract['contract_id'], true, $contract );
		},
		'cell_class' => 'row_action',
	);
	$columns['contract_start_date']     = array(
		'title'    => 'Create Date',
		'callback' => function ( $contract ) {
			echo print_date( $contract['date_create'] );
		},
	);
	$columns['contract_completed_date'] = array(
		'title'    => 'Accepted Date',
		'callback' => function ( $contract ) {
			echo print_date( $contract['date_approved'] );
		},
	);
	$columns['contract_termintatedate'] = array(
		'title'    => 'End Date',
		'callback' => function ( $contract ) {
			echo print_date( $contract['date_terminate'] );
		},
	);
	if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
		$columns['contract_website'] = array(
			'title'    => module_config::c( 'project_name_single', 'Website' ),
			'callback' => function ( $contract ) {
				echo module_website::link_open( $contract['website_id'], true );
			},
		);
	}
	if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
		$columns['contract_customer'] = array(
			'title'    => 'Customer',
			'callback' => function ( $contract ) {
				echo module_customer::link_open( $contract['customer_id'], true );
			},
		);
	}
	$columns['contract_type'] = array(
		'title'    => 'Type',
		'callback' => function ( $contract ) {
			echo htmlspecialchars( $contract['type'] );
		},
	);


	if ( class_exists( 'module_group', false ) ) {
		$columns['group'] = array(
			'title'    => 'Group',
			'callback' => function ( $contract ) {
				$groups = module_group::get_groups_search( array(
					'owner_table' => 'contract',
					'owner_id'    => $contract['contract_id'],
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
		return module_contract::get_contract( $row_data['contract_id'] );
	};
	$table_manager->set_rows( $contracts );
	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'contract', function ( $contract ) {
			module_extra::print_table_data( 'contract', $contract['contract_id'] );
		} );
	}
	$table_manager->pagination = true;
	$table_manager->print_table();

	?>

</form>