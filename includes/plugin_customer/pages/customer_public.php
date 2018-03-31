<?php

defined( '_UCM_SECRET' ) || die( 'No access' );
if ( ! $customer_id || ! $customer_data ) {
	die( 'No customer Id' );
}


$invoices = module_invoice::get_invoices( array( 'customer_id' => $customer_id ) );
$quotes   = module_quote::get_quotes( array( 'customer_id' => $customer_id ) );
$jobs     = module_job::get_jobs( array( 'customer_id' => $customer_id ) );


?>
<style type="text/css">


	@media print {
		table {
			page-break-after: auto
		}

		tr {
			page-break-inside: avoid;
			page-break-after: auto
		}

		td {
			page-break-inside: avoid;
			page-break-after: auto
		}

		thead {
			display: table-header-group
		}

		tfoot {
			display: table-footer-group
		}

		img.logo {
			diplay: none;
		}
	}

	table {
		width: 100%;
		margin: 15px auto 42px;
		border-collapse: collapse;
		-fs-table-paginate: paginate;
		table-layout: fixed;
	}

	table tfoot td,
	table th {
		background: #f3f3f3;
		color: #734641;
		text-align: center;
		padding: 10px 8px;
		font-size: 12px;
		font-weight: normal;
		border: thin solid #cbcbcb;
	}

	table td {
		background: #FFF;
		border: thin solid #cbcbcb;
		font-size: 12px;
		padding: 9px 8px;
		overflow: hidden;
		white-space: nowrap;
	}

	table tr.public td {
		background: #FFF;
	}

	table td.currency {
		text-align: right;
	}

	table td.currency.year {
		background: #fdebca;
	}

	table tfoot.old td {
		-moz-transform: translateY(10px);
		-webkit-transform: translateY(10px);
		-o-transform: translateY(10px);
		-ms-transform: translateY(10px);
		transform: translateY(10px);
	}

	table td .invoice {
		background: #6d89bd;
		color: white;
		border-radius: 11px;
		padding: 4px 6px;
	}

	table td .payment {
		background: #53a28a;
		color: white;
		border-radius: 11px;
		padding: 4px 6px;
	}

</style>


<?php
if ( $quotes ) {
	?>

	<h2>Quotes</h2>

	<?php

	$table_manager                   = module_theme::new_table_manager();
	$table_manager->table_class      = 'public';
	$table_manager->row_class        = 'public';
	$columns                         = array();
	$columns['quote_title']          = array(
		'title'      => 'Quote Title',
		'callback'   => function ( $quote ) {
			?> <a href="<?php echo module_quote::link_public( $quote['quote_id'] ); ?>"
			      target="_blank"><?php echo htmlspecialchars( $quote['name'] ); ?></a> <?php
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
				$website = module_website::get_website( $quote['website_id'] );
				echo htmlspecialchars( $website['name'] );
			},
		);
	}

	$columns['quote_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $quote ) {
			echo htmlspecialchars( $quote['status'] );
		},
	);
	$job_ids                 = array();
	$columns['job']          = array(
		'title'    => 'Job',
		'callback' => function ( $quote ) use ( &$job_ids ) {
			$job_ids = array();
			foreach ( module_job::get_jobs( array( 'quote_id' => $quote['quote_id'] ) ) as $job ) {
				$job = module_job::get_job( $job['job_id'] );
				if ( ! $job ) {
					continue;
				}
				//				echo module_job::link_open( $job['job_id'], true );
				?> <a href="<?php echo module_job::link_public( $job['job_id'] ); ?>"
				      target="_blank"><?php echo htmlspecialchars( $job['name'] ); ?></a> <?php
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
			if ( ! $job_ids ) {
				echo _l( 'N/A' );
			}
		},
	);
	//if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
	$columns['invoice'] = array(
		'title'    => 'Invoice',
		'callback' => function ( $quote ) use ( &$job_ids ) {
			$invoices = false;
			foreach ( $job_ids as $job_id ) {
				foreach ( module_invoice::get_invoices( array( 'job_id' => $job_id ) ) as $invoice ) {
					$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					if ( ! $invoice ) {
						continue;
					}
					//echo module_invoice::link_open( $invoice['invoice_id'], true );
					?> <a href="<?php echo module_invoice::link_public( $invoice['invoice_id'] ); ?>"
					      target="_blank"><?php echo htmlspecialchars( $invoice['name'] ); ?></a> <?php
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
					$invoices = true;
					echo '</span>';
					echo "<br>";
				}
			}
			if ( ! $invoices ) {
				echo _l( 'N/A' );
			}
		},
	);
	//}

	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_quote::get_quote( $row_data['quote_id'] );
	};
	$table_manager->set_rows( $quotes );
	$table_manager->pagination = false;
	$table_manager->print_table();
}

if ( $jobs ) {
	?>

	<h2>Jobs</h2>

	<?php
	$table_manager                 = module_theme::new_table_manager();
	$table_manager->table_class    = 'public';
	$table_manager->row_class      = 'public';
	$columns                       = array();
	$columns['job_title']          = array(
		'title'      => 'Job Title',
		'callback'   => function ( $job ) {
			?> <a href="<?php echo module_job::link_public( $job['job_id'] ); ?>"
			      target="_blank"><?php echo htmlspecialchars( $job['name'] ); ?></a> <?php
		},
		'cell_class' => 'row_action',
	);
	$columns['job_start_date']     = array(
		'title'    => 'Started',
		'callback' => function ( $job ) {
			echo print_date( $job['date_start'] );
			//is there a renewal date?
			if ( isset( $job['date_renew'] ) && $job['date_renew'] && $job['date_renew'] != '0000-00-00' ) {
				_e( ' to %s', print_date( strtotime( "-1 day", strtotime( $job['date_renew'] ) ) ) );
			}
		},
	);
	$columns['job_due_date']       = array(
		'title'    => 'Due',
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
		'title'    => 'Completed',
		'callback' => function ( $job ) {
			echo print_date( $job['date_completed'] );
		},
	);
	if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
		$columns['job_website'] = array(
			'title'    => module_config::c( 'project_name_single', 'Website' ),
			'callback' => function ( $job ) {
				$website = module_website::get_website( $job['website_id'] );
				echo htmlspecialchars( $website['name'] );
			},
		);
	}

	$columns['job_type']                  = array(
		'title'    => 'Type',
		'callback' => function ( $job ) {
			echo htmlspecialchars( $job['type'] );
		},
	);
	$columns['job_progress']              = array(
		'title'    => 'Progress',
		'type'     => 'progress_bar',
		'callback' => function ( $job ) {
			?> <span data-percent="<?php echo( $job['total_percent_complete'] * 100 ); ?>"
			         class="progress_bar <?php echo $job['total_percent_complete'] >= 1 ? 'success_text' : ''; ?>">
            <?php echo ( $job['total_percent_complete'] * 100 ) . '%'; ?>
        </span> <?php
		},
	);
	$columns['job_total']                 = array(
		'title'    => 'Total',
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
			$invoiced = false;
			foreach ( module_invoice::get_invoices( array( 'job_id' => $job['job_id'] ) ) as $invoice ) {
				$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
				if ( ! $invoice ) {
					continue;
				}
				//				echo module_invoice::link_open($invoice['invoice_id'],true);
				?> <a href="<?php echo module_invoice::link_public( $invoice['invoice_id'] ); ?>"
				      target="_blank"><?php echo htmlspecialchars( $invoice['name'] ); ?></a> <?php
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
				$invoiced = true;
				echo '</span>';
				echo "<br>";
			}
			if ( ! $invoiced ) {
				_e( 'N/A' );
			}
		},
	);

	$table_manager->set_id( 'job_list' );
	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $row_data ) {
		// load the full vendor data before displaying each row so we have access to more details
		return module_job::get_job( $row_data['job_id'] );
	};
	$table_manager->set_rows( $jobs );
	$table_manager->pagination = false;
	$table_manager->print_table();
}
?>

<h2>Invoices &amp; Payments</h2>
<table>
	<thead>
	<tr>
		<th>Date</th>
		<th>Type</th>
		<th>Description</th>
		<th>Charge</th>
		<th>Credit</th>
		<th>Balance</th>
	</tr>
	</thead>
	<?php


	$transactions = array();
	foreach ( $invoices as $invoice ) {
		$dd  = date( 'd/m/Y', strtotime( $invoice['date_create'] ) );
		$ddd = date( 'Ymd', strtotime( $invoice['date_create'] ) );

		if ( $invoice['deposit_job_id'] > 0 ) {
			$transactions[] = array( 'id'      => $invoice['invoice_id'],
			                         'name'    => $invoice['name'] . ' Deposit invoice<br/>(payments assigned directly to invoice)',
			                         'date'    => $dd,
			                         'amount'  => $invoice['c_total_amount'],
			                         'dd'      => $ddd,
			                         'deposit' => $invoice['deposit_job_id'],
			                         'charge'  => $invoice['c_total_amount'],
			                         'class'   => "deposit",
			                         'credit'  => 0
			);
		} else {
			$transactions[] = array( 'id'      => $invoice['invoice_id'],
			                         'name'    => $invoice['name'],
			                         'date'    => $dd,
			                         'amount'  => $invoice['c_total_amount'],
			                         'dd'      => $ddd,
			                         'deposit' => $invoice['deposit_job_id'],
			                         'charge'  => $invoice['c_total_amount'],
			                         'class'   => "invoice",
			                         'credit'  => 0
			);
		}
	}

	usort( $transactions, function ( $a, $b ) {
		$da = $a['dd'];
		$db = $b['dd'];
		if ( $da > $db ) {
			return 1;
		} else {
			return 0;
		}
	} );

	$charges = array_column( $transactions, 'amount' );

	$total = array_sum( $charges );

	$transactions2 = array();

	foreach ( $transactions as $transaction ) {
		$transactions2[] = $transaction;
		if ( $transaction['deposit'] == 0 ) {
			$invoiceid = $transaction['id'];

			$payments = module_invoice::get_invoice_payments( $invoiceid );
			foreach ( $payments as $payment ) {

				if ( $payment['date_paid'] && $payment['date_paid'] != '0000-00-00' ) {
					$dd  = date( 'd/m/Y', strtotime( $payment['date_paid'] ) );
					$ddd = date( 'Ymd', strtotime( $payment['date_paid'] ) );

					if ( $payment['method'] != 'Credit' && $payment['method'] != 'Assigning Credit' ) {
						$var             = $payment['method'] . ' > ';
						$transactions2[] = array(
							'id'      => $invoiceid,
							'class'   => "payment",
							'name'    => ( $var . $transaction['name'] ),
							'date'    => $dd,
							'amount'  => ( - 1 * $payment['amount'] ),
							'dd'      => $ddd,
							'deposit' => 'Payment',
							'charge'  => 0,
							'credit'  => $payment['amount']
						);
					} else {
						//$var = 'Transfering credit to ';
						//$transactions2[] = array('class' => "transfer", 'name' => ($var.$transaction['name']), 'date' => $dd, 'amount' => (-1* $row2['amount']), 'dd' => $ddd, 'deposit' => 'Payment', 'transfer' => $row2['amount']);
					}
				}
			}
		}
	}

	$charges2 = array_column( $transactions2, 'amount' );

	$chargecol    = array_column( $transactions2, 'charge' );
	$charge_total = array_sum( $chargecol );

	$creditcol    = array_column( $transactions2, 'credit' );
	$credit_total = array_sum( $creditcol );

	$transfercol    = array_column( $transactions2, 'transfer' );
	$transfer_total = array_sum( $transfercol );


	$total2 = array_sum( $charges2 );

	/*usort($transactions2, function($a, $b) {
		$da = $a['dd'];
		$db = $b['dd'];
		if($da > $db){
			return 1;
		}else{
			return 0;
		}
	});*/


	$current_total         = 0;
	$parent_date           = 2900;
	$current_charge_total  = 0;
	$current_credit_total  = 0;
	$current_balance_total = 0;

	foreach ( $transactions2 as $transaction ) {
		// sum each year
		$currentdate = $transaction['date'];
		$d_old       = substr( $parent_date, - 4 );
		$d_new       = substr( $currentdate, - 4 );
		if ( $d_new > $d_old ) {
			?>
			<!-- YEAR CHANGE -->
			<tr>
				<td class="currency year" colspan="3">TOTAL (<?php echo $d_old; ?>):</td>
				<td class="currency year"><?php echo dollar( $current_charge_total, 2 ); ?></td>
				<td class="currency year"><?php echo dollar( $current_credit_total, 2 ); ?></td>
				<td class="currency year"><?php echo dollar( $current_balance_total, 2 ); ?></td>
			</tr>
		<?php }

		$current_charge_total  += $transaction['charge'];
		$current_credit_total  += $transaction['credit'];
		$current_balance_total = $current_charge_total - $current_credit_total;

		?>
		<tr>
			<td><?php echo $transaction['date']; ?></td>
			<td class="type">

					<span class="<?php echo $transaction['class']; ?>">
					<?php switch ( $transaction['class'] ) {
						case 'payment':
							_e( 'Payment' );
							break;
						case 'invoice':
							_e( 'Invoice' );
							break;
						case 'deposit':
							_e( 'Deposit' );
							break;
					} ?>
						</span>
			</td>
			<td class="">
				<a href="<?php echo module_invoice::link_public( $transaction['id'] ); ?>"
				   target="_blank"><?php echo htmlspecialchars( $transaction['name'] ); ?></a>
			</td>
			<td class="currency"><?php echo ( $transaction['charge'] > 0 ) ? dollar( $transaction['charge'], 2 ) : ''; ?></td>
			<td class="currency"><?php echo ( $transaction['credit'] > 0 ) ? dollar( $transaction['credit'], 2 ) : ''; ?></td>
			<?php $current_total += $transaction['amount']; ?>
			<td class="currency"><?php echo dollar( $current_total, 2 ); ?></td>
		</tr>
		<?php $parent_date = $transaction['date'];
	} ?>
	<tfoot>
	<tr>
		<td class="currency totals" colspan="3">TOTAL (all):</td>
		<td class="currency totals"><?php echo dollar( $charge_total, 2 ); ?></td>
		<td class="currency totals"><?php echo dollar( $credit_total, 2 ); ?></td>
		<td class="currency totals"><?php echo dollar( $total2, 2 ); ?></td>
	</tr>
	</tfoot>
</table>