<?php
if ( ! $invoice_safe ) {
	die( 'failed' );
}

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['customer_id'] ) ) {
	$search['customer_id'] = $_REQUEST['customer_id'];
}

$reset_archived = false;
if ( ! isset( $search['archived_status'] ) ) {
	$reset_archived            = true;
	$search['archived_status'] = _ARCHIVED_SEARCH_NONARCHIVED;
}
$invoices = module_invoice::get_invoices( $search );
if ( $reset_archived ) {
	unset( $search['archived_status'] );
}


$all_invoice_ids = array();
foreach ( $invoices as $invoice ) {
	$all_invoice_ids[] = $invoice['invoice_id'];
}

if ( class_exists( 'module_table_sort', false ) && module_table_sort::is_plugin_enabled() ) {

	// get full invoice data.
	// todo: only grab data if we're sorting by something
	// that isn't in the default invoice listing.


	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
		array(
			'table_id' => 'invoice_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'invoice_number'      => array(
					'field' => 'name',
				),
				'invoice_status'      => array(
					'field' => 'status',
				),
				'invoice_create_date' => array(
					'field'   => 'date_create',
					'current' => 2, // 1 asc, 2 desc
				),
				'invoice_due_date'    => array(
					'field' => 'date_due',
				),
				'invoice_sent_date'   => array(
					'field' => 'date_sent',
				),
				'invoice_paid_date'   => array(
					'field' => 'date_paid',
				),

				/*'invoice_website' => array(
						'field' => 'website_name',
				),
				'invoice_job' => array(
						'field' => 'job_name',
				),*/
				'invoice_customer'    => array(
					'field' => 'customer_name',
				),

				'c_invoice_total'     => array(
					'field' => 'cached_total',
					//'field' => 'total_amount',
				),
				'c_invoice_total_due' => array(
					'field' => 'total_amount_due',
				),

			),
		)
	);
	if ( isset( $_REQUEST['table_sort_column'] ) || ( isset( $_SESSION['_table_sort'] ) && isset( $_SESSION['_table_sort']['invoice_list'] ) && isset( $_SESSION['_table_sort']['invoice_list'][0] ) ) ) {
		// we're sorting by something!
		reset( $invoices );
		$test = current( $invoices );
		if ( $test && $test['invoice_id'] ) {
			$column = isset( $_REQUEST['table_sort_column'] ) ? $_REQUEST['table_sort_column'] : $_SESSION['_table_sort']['invoice_list'][0];
			if ( isset( module_table_sort::$table_sort_options['sortable'][ $column ] ) ) {
				$dbcolumn = module_table_sort::$table_sort_options['sortable'][ $column ]['field'];
				if ( ! isset( $test[ $dbcolumn ] ) ) {
					$test = module_invoice::get_invoice( $test['invoice_id'] );
					if ( isset( $test[ $dbcolumn ] ) ) {
						// load all invoice data (EEP!) so we can sort better
						foreach ( $invoices as $invoice_id => $invoice ) {
							$full_invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
							if ( isset( $full_invoice[ $dbcolumn ] ) ) {
								$invoices[ $invoice_id ][ $dbcolumn ] = $full_invoice[ $dbcolumn ];
							}
						}
					}
				}
			}
		}
	}
}


// calculate totals at the bottom
if ( module_config::c( 'invoice_list_show_totals', 1 ) ) {
	$invoice_total     = array();
	$invoice_total_due = array();
	foreach ( $invoices as $invoice ) {
		if ( $invoice['credit_note_id'] ) {
			continue;
		}
		if ( ! isset( $invoice_total[ $invoice['currency_id'] ] ) ) {
			$invoice_total[ $invoice['currency_id'] ] = 0;
		}
		if ( $invoice['c_total_amount'] == 0 ) {
			$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
		}
		$invoice_total[ $invoice['currency_id'] ] += $invoice['c_total_amount'];
		if ( ! isset( $invoice_total_due[ $invoice['currency_id'] ] ) ) {
			$invoice_total_due[ $invoice['currency_id'] ] = 0;
		}
		$invoice_total_due[ $invoice['currency_id'] ] += $invoice['c_total_amount_due'];
	}
}

// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_invoice::can_i( 'view', 'Export Invoices' ) ) {
	$export_settings = array(
		'name'    => 'Invoice Export',
		'fields'  => array(
			'Invoice ID'       => 'invoice_id',
			'Invoice Number'   => 'name',
			'Status'           => 'status',
			'Total Amount'     => 'c_total_amount',
			'Total Amount Due' => 'c_total_amount_due',
			'Create Date'      => 'date_create',
			'Sent Date'        => 'date_sent',
			'Due Date'         => 'date_due',
			'Paid Date'        => 'date_paid',
			'Cancel Date'      => 'date_cancel',
			'Customer Name'    => 'customer_name',
			//module_config::c('project_name_single','Website').' Name' => 'website_name',
			//'Job Name' => 'job_name',
			//'Staff Member' => 'staff_member',
			//'Tax Name' => 'total_tax_name',
			//'Tax Percent' => 'total_tax_rate',
			'Renewal Date'     => 'date_renew',
		),
		// do we look for extra fields?
		'extra'   => array(
			'owner_table' => 'invoice',
			'owner_id'    => 'invoice_id',
		),
		'summary' => array()
	);
	if ( module_config::c( 'invoice_list_show_totals', 1 ) ) {
		foreach ( $invoice_total + $invoice_total_due as $currency_id => $foo ) {
			$currency                     = get_single( 'currency', 'currency_id', $currency_id );
			$export_settings['summary'][] = array(
				'invoice_id'         => _l( '%s Totals:', $currency && isset( $currency['code'] ) ? $currency['code'] : '' ),
				'c_total_amount'     => dollar( isset( $invoice_total[ $currency_id ] ) ? $invoice_total[ $currency_id ] : 0, true, $currency_id ),
				'c_total_amount_due' => dollar( isset( $invoice_total_due[ $currency_id ] ) ? $invoice_total_due[ $currency_id ] : 0, true, $currency_id ),
			);
		}
	}

	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this customers?
		$export_settings
	);
}


// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() && module_invoice::can_i( 'edit', 'Invoices' ) ) {
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this customers?
		array(
			'bulk_actions' => array(
				'delete' => array(
					'label'    => 'Delete selected invoices',
					'type'     => 'delete',
					'callback' => 'module_invoice::bulk_handle_delete',
				),
			),
		)
	);
}

$header = array(
	'title'  => _l( 'Invoices' ),
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
if ( module_invoice::can_i( 'create', 'Invoices' ) ) {
	$header['button'] = array(
		'url'   => module_invoice::link_open( 'new' ),
		'title' => _l( 'New Manual Invoice' ),
		'type'  => 'add',
	);
}
print_heading( $header );

?>


	<form action="" method="post">

		<?php module_form::print_form_auth(); ?>

		<?php $search_bar = array(
			'elements' => array(
				'name'   => array(
					'title' => _l( 'Invoice Number:' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'search[generic]',
						'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					)
				),
				'date'   => array(
					'title'  => _l( 'Create Date:' ),
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
				'status' => array(
					'title' => false,
					'field' => array(
						'type'    => 'select',
						'name'    => 'search[status]',
						'value'   => isset( $search['status'] ) ? $search['status'] : '',
						'options' => module_invoice::get_statuses(),
						'blank'   => _l( ' - Status - ' ),
					)
				),
				/*'completed' => array(
						'title' => _l('Completed:'),
						'field' => array(
								'type' => 'select',
								'name' => 'search[completed]',
								'value' => isset($search['completed'])?$search['completed']:'',
								'options' => array(
												1=>_l('Both Completed and Non-Completed Jobs'),
												2=>_l('Only Completed Jobs'),
												3=>_l('Only Non-Completed Jobs'),
												4=>_l('Only Quoted Jobs'),
										),
						)
				),*/
			)
		);
		if ( ! isset( $_REQUEST['customer_id'] ) && class_exists( 'module_group', false ) && module_customer::can_i( 'view', 'Customer Groups' ) ) {
			$search_bar['elements']['group'] = array(
				'title' => false,
				'field' => array(
					'type'             => 'select',
					'name'             => 'search[customer_group_id]',
					'value'            => isset( $search['customer_group_id'] ) ? $search['customer_group_id'] : '',
					'options'          => module_group::get_groups( 'customer' ),
					'options_array_id' => 'name',
					'blank'            => _l( ' - Customer Group - ' ),
				)
			);
		}
		if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
			$companys = module_company::get_companys();
			if ( count( $companys ) > 0 ) {
				$companys_rel = array();
				foreach ( $companys as $company ) {
					$companys_rel[ $company['company_id'] ] = $company['name'];
				}
				$search_bar['elements']['company'] = array(
					'title' => false,
					'field' => array(
						'type'    => 'select',
						'name'    => 'search[company_id]',
						'value'   => isset( $search['company_id'] ) ? $search['company_id'] : '',
						'options' => $companys_rel,
						'blank'   => _l( ' - Company - ' ),
					)
				);
			}
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
		$colspan  = 9;
		$colspan2 = 0;

		$table_manager                  = module_theme::new_table_manager();
		$columns                        = array();
		$columns['invoice_number']      = array(
			'title'      => 'Invoice Number',
			'callback'   => function ( $invoice ) {
				echo module_invoice::link_open( $invoice['invoice_id'], true, $invoice );
			},
			'cell_class' => 'row_action',
		);
		$columns['invoice_status']      = array(
			'title'    => 'Status',
			'callback' => function ( $invoice ) {
				echo htmlspecialchars( $invoice['status'] );
			},
		);
		$columns['invoice_create_date'] = array(
			'title'    => 'Create Date',
			'callback' => function ( $invoice ) {
				if ( ( ! $invoice['date_create'] || $invoice['date_create'] == '0000-00-00' ) ) {
					//echo print_date($invoice['date_created']);
				} else {
					echo print_date( $invoice['date_create'] );
				}
			},
		);
		$columns['invoice_due_date']    = array(
			'title'    => 'Due Date',
			'callback' => function ( $invoice ) {
				if ( ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < time() ) {
					echo '<span class="error_text">';
					echo print_date( $invoice['date_due'] );
					echo '</span>';
				} else {
					echo print_date( $invoice['date_due'] );
				}
			},
		);
		$columns['invoice_sent_date']   = array(
			'title'    => 'Sent Date',
			'callback' => function ( $invoice ) {
				if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) { ?>
					<?php echo print_date( $invoice['date_sent'] ); ?>
				<?php } else { ?>
					<span class="error_text"><?php _e( 'Not sent' ); ?></span>
				<?php }
			},
		);
		$columns['invoice_paid_date']   = array(
			'title'    => 'Paid Date',
			'callback' => function ( $invoice ) {
				if ( $invoice['credit_note_id'] ) {
					echo _l( 'N/A' );
				} else if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) { ?>
					<?php echo print_date( $invoice['date_paid'] ); ?>
				<?php } else if ( ( $invoice['date_cancel'] && $invoice['date_cancel'] != '0000-00-00' ) ) { ?>
					<span class="error_text"><?php _e( 'Cancelled' ); ?></span>
				<?php } else if ( $invoice['overdue'] ) { ?>
					<span class="error_text"
					      style="font-weight: bold; text-decoration: underline;"><?php _e( 'Overdue' ); ?></span>
				<?php } else { ?>
					<span class="error_text"><?php _e( 'Not paid' ); ?></span>
				<?php }
			},
		);
		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() && module_website::can_i( 'view', module_config::c( 'project_name_plural', 'Websites' ) ) ) {
			$colspan ++;
			$columns['invoice_website'] = array(
				'title'    => module_config::c( 'project_name_single', 'Website' ),
				'callback' => function ( $invoice ) {
					if ( isset( $invoice['website_ids'] ) ) {
						foreach ( $invoice['website_ids'] as $website_id ) {
							if ( (int) $website_id > 0 ) {
								echo module_website::link_open( $website_id, true );
								echo '<br/>';
							}
						}
					}
				},
			);
		}
		if ( module_job::is_plugin_enabled() && module_job::can_i( 'view', 'Jobs' ) ) {
			$columns['invoice_job'] = array(
				'title'    => 'Job',
				'callback' => function ( $invoice ) {
					foreach ( $invoice['job_ids'] as $job_id ) {
						if ( (int) $job_id > 0 ) {
							echo module_job::link_open( $job_id, true );
							$job_data = module_job::get_job( $job_id );
							if ( $job_data['date_start'] && $job_data['date_start'] != '0000-00-00' && $job_data['date_renew'] && $job_data['date_renew'] != '0000-00-00' ) {
								_e( ' (%s to %s)', print_date( $job_data['date_start'] ), print_date( strtotime( "-1 day", strtotime( $job_data['date_renew'] ) ) ) );
							}
							echo "<br/>\n";

						}
					}
					hook_handle_callback( 'invoice_admin_list_job', $invoice['invoice_id'] );
				},
			);
		}
		if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
			$colspan ++;
			$columns['invoice_customer'] = array(
				'title'    => 'Customer',
				'callback' => function ( $invoice ) {
					echo module_customer::link_open( $invoice['customer_id'], true );
				},
			);
		}
		$columns['c_invoice_total']     = array(
			'title'    => 'Invoice Total',
			'callback' => function ( $invoice ) {
				echo dollar( $invoice['total_amount'], true, $invoice['currency_id'] );
			},
		);
		$columns['c_invoice_total_due'] = array(
			'title'    => 'Amount Due',
			'callback' => function ( $invoice ) {
				if ( $invoice['credit_note_id'] ) {
					echo _l( 'N/A' );
				} else {
					echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
					<?php if ( $invoice['total_amount_credit'] > 0 ) { ?>
						<span
							class="success_text"><?php echo _l( 'Credit: %s', dollar( $invoice['total_amount_credit'], true, $invoice['currency_id'] ) ); ?></span>
						<?php
					}
				}
			},
		);
		if ( class_exists( 'module_extra', false ) ) {
			ob_start();
			$colspan2 += module_extra::print_table_header( 'invoice' ); // used in the footer calc.
			ob_end_clean();
			$table_manager->display_extra( 'invoice', function ( $invoice ) {
				module_extra::print_table_data( 'invoice', $invoice['invoice_id'] );
			} );
		}
		if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
			$colspan2 ++;// used in the footer
			$columns['row_bulk_action'] = array(
				'title'    => ' ',
				'callback' => function ( $invoice ) {
					echo '<input type="checkbox" name="invoice_bulk_operation[' . $invoice['invoice_id'] . ']" value="yes">';
				}
			);
		}
		$table_manager->set_columns( $columns );
		$table_manager->row_callback = function ( $row_data ) {
			// load the full vendor data before displaying each row so we have access to more details
			if ( isset( $row_data['invoice_id'] ) && (int) $row_data['invoice_id'] > 0 ) {
				return module_invoice::get_invoice( $row_data['invoice_id'] );
			}

			return array();
		};
		$table_manager->set_rows( $invoices );
		if ( module_config::c( 'invoice_list_show_totals', 1 ) ) {
			$footer_rows = array();
			foreach ( $invoice_total + $invoice_total_due as $currency_id => $foo ) {
				$currency      = get_single( 'currency', 'currency_id', $currency_id );
				$footer_rows[] = array(
					'invoice_number'      => array(
						'data'         => '<strong>' . _l( '%s Totals:', $currency && isset( $currency['code'] ) ? $currency['code'] : '' ) . '</strong>',
						'cell_colspan' => $colspan - 2,
						'cell_class'   => 'text-right',
					),
					'c_invoice_total'     => array(
						'data' => '<strong>' . dollar( isset( $invoice_total[ $currency_id ] ) ? $invoice_total[ $currency_id ] : 0, true, $currency_id ) . '</strong>',
					),
					'c_invoice_total_due' => array(
						'data' => '<strong>' . dollar( isset( $invoice_total_due[ $currency_id ] ) ? $invoice_total_due[ $currency_id ] : 0, true, $currency_id ) . '</strong>',
					),
					'row_bulk_action'     => array(
						'data'         => ' ',
						'cell_colspan' => $colspan2
					),
				);
			}
			$table_manager->set_footer_rows( $footer_rows );
		}

		$table_manager->pagination = true;
		$table_manager->print_table();

		?>
	</form>

<?php if ( function_exists( 'convert_html2pdf' ) && get_display_mode() != 'mobile' ) { ?>
	<form action="<?php echo module_invoice::link_generate( false, array( 'arguments' => array( 'print' => 1 ) ) ); ?>"
	      method="post">
		<input type="hidden" name="invoice_ids" value="<?php echo implode( ",", $all_invoice_ids ); ?>">
		<input type="submit" name="butt_print" value="<?php echo _l( 'Export all results as PDF' ); ?>"
		       class="submit_button"/>
	</form>
<?php } ?>