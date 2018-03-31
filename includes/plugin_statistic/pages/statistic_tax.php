<?php


$page_title = _l( 'Tax Report' );

$search      = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array(
	'type'      => 'ie',
	'date_from' => print_date( date( 'Y-m-d', strtotime( '-1 month' ) ) ),
	'date_to'   => print_date( date( 'Y-m-d' ) )
);
$tax_reports = module_statistic::get_statistics_tax( $search );

if ( ! module_statistic::can_i( 'view', 'Tax Report' ) ) {
	redirect_browser( _BASE_HREF );
}

$currencies             = array();
$available_credit_taxes = array();
$available_debit_taxes  = array();
foreach ( $tax_reports as $id => $finance ) {
	if ( isset( $finance['payment_type'] ) ) {
		if ( $finance['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT || $finance['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT ) {
			// ADD THIS CODE TO get_finance_summary() in finance.php too!
			// ADD THIS CODE TO finance_list.php too!
			// dont add these ones to the totals at thebottom, mark then with asterix so people know.
			unset( $tax_reports[ $id ] );
			continue;
		}
	}
	if ( ! isset( $currencies[ $finance['currency_id'] ] ) ) {
		$currencies[ $finance['currency_id'] ] = $finance['currency_id'];
	}
	if ( isset( $finance['finance_id'] ) && $finance['finance_id'] ) {
		$finance_record = module_finance::get_finance( $finance['finance_id'] );
	} else if ( isset( $finance['invoice_payment_id'] ) && $finance['invoice_payment_id'] > 0 ) {
		$finance_record = module_finance::get_finance( false, true, $finance['invoice_payment_id'] );
	} else {
		$finance_record = false;
	}
	if ( $finance_record && isset( $finance_record['taxes'] ) ) {
		foreach ( $finance_record['taxes'] as $tax ) {
			if ( $finance['credit'] > 0 ) {
				if ( $tax['percent'] > 0 ) {
					$available_credit_taxes[ $finance['currency_id'] ] [ $tax['percent'] ] = true;
				}
			}
			if ( $finance['debit'] > 0 ) {
				if ( $tax['percent'] > 0 ) {
					$available_debit_taxes[ $finance['currency_id'] ] [ $tax['percent'] ] = true;
				}
			}
		}
	}
}


print_heading( array(
	'title' => 'Tax Report',
	'type'  => 'h2',
	'main'  => true,
) );
?>

<p>This report will show an overview of the taxible income and expenditure from items recorded in the "Transactions"
	area.</p>

<form action="" method="post" id="statistic_form">

	<?php
	$search_bar = array(
		'elements' => array(
			'date' => array(
				'title'  => _l( 'Transaction Date:' ),
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
			'type' => array(
				'title'  => _l( 'Type:' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'search[type]',
						'value'   => isset( $search['type'] ) ? $search['type'] : '',
						'options' => array( 'ie' => 'Income and Expense', 'i' => 'Income', 'e' => 'Expense' ),
					),
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar ); ?>


</form>

<p>&nbsp;</p>

<table class="tableclass tableclass_rows tableclass_full">
	<thead>
	<tr class="title">
		<th id="sort_date" rowspan="2"><?php _e( 'Date' ); ?></th>
		<th id="sort_name" rowspan="2"><?php _e( 'Transaction Name' ); ?></th>
		<th id="sort_customer" rowspan="2"><?php _e( 'Customer' ); ?></th>
		<th id="sort_invoice" rowspan="2"><?php _e( 'Invoice' ); ?></th>
		<th id="sort_job" rowspan="2"><?php _e( 'Job' ); ?></th>
		<?php foreach ( $currencies as $currency_id ) {
			$currency = get_single( 'currency', 'currency_id', $currency_id );
			if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'i' ) { ?>
				<th
					colspan="<?php echo 2 + ( isset( $available_credit_taxes[ $currency_id ] ) ? count( $available_credit_taxes[ $currency_id ] ) : 0 ); ?>"><?php echo $currency && isset( $currency['code'] ) ? $currency['code'] : '' ?><?php _e( 'Income/Credit' ); ?></th>
			<?php } ?>
			<?php if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'e' ) { ?>
				<th
					colspan="<?php echo 2 + ( isset( $available_debit_taxes[ $currency_id ] ) ? count( $available_debit_taxes[ $currency_id ] ) : 0 ); ?>"><?php echo $currency && isset( $currency['code'] ) ? $currency['code'] : '' ?><?php _e( 'Expense/Debit' ); ?></th>
			<?php } ?>
		<?php } ?>
		<th id="sort_account" rowspan="2"><?php _e( 'Account' ); ?></th>
		<th id="sort_categories" rowspan="2"><?php _e( 'Categories' ); ?></th>
	</tr>
	<tr>
		<?php foreach ( $currencies as $currency_id ) {
			$currency = get_single( 'currency', 'currency_id', $currency_id );
			if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'i' ) { ?>
				<th id="sort_credit"><?php _e( 'Sub-Total' ); ?></th>
				<?php if ( isset( $available_credit_taxes[ $currency_id ] ) ) {
					foreach ( $available_credit_taxes[ $currency_id ] as $tax_percent => $tf ) { ?>
						<th id="sort_credit_tax"><?php _e( 'Tax %s%%', number_out( $tax_percent, true ) ); ?></th>
					<?php }
				} ?>
				<th id="sort_credit_total"><?php _e( 'Total' ); ?></th>
			<?php } ?>
			<?php if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'e' ) { ?>
				<th id="sort_debit"><?php _e( 'Sub-Total' ); ?></th>
				<?php if ( isset( $available_debit_taxes[ $currency_id ] ) ) {
					foreach ( $available_debit_taxes[ $currency_id ] as $tax_percent => $tf ) { ?>
						<th id="sort_debit_tax"><?php _e( 'Tax %s%%', number_out( $tax_percent, true ) ); ?></th>
					<?php }
				} ?>
				<th id="sort_debit_total"><?php _e( 'Total' ); ?></th>
			<?php } ?>
		<?php } ?>
	</tr>
	</thead>
	<tbody>
	<?php
	$c                 = 0;
	$total_by_currency = array();
	$total             = array(
		'transaction_count' => 0,
		'total_credit'      => 0,
		'total_sub_credit'  => 0,
		'total_credit_tax'  => array(),
		'total_debit'       => 0,
		'total_sub_debit'   => 0,
		'total_debit_tax'   => array(),
	);
	// calc taxes and stuff first loop, then display later.
	foreach ( $tax_reports as $finance ) {

		if ( isset( $finance['finance_id'] ) && $finance['finance_id'] ) {
			$finance_record = module_finance::get_finance( $finance['finance_id'] );
		} else if ( isset( $finance['invoice_payment_id'] ) && $finance['invoice_payment_id'] > 0 ) {
			$finance_record = module_finance::get_finance( false, true, $finance['invoice_payment_id'] );
		} else {
			$finance_record = false;
		}

		if ( ! isset( $total_by_currency[ $finance['currency_id'] ] ) ) {
			$total_by_currency[ $finance['currency_id'] ] = $total;
		}
		$total_by_currency[ $finance['currency_id'] ]['transaction_count'] ++;

		?>
		<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
			<td>
				<?php
				$dates = array();
				//$links = array();
				$dates[ print_date( $finance['transaction_date'] ) ] = true;
				//$links[$finance['url']]=!trim($finance['name']) ? 'N/A' :    htmlspecialchars($finance['name']);
				if ( $finance_record ) {
					if ( isset( $finance_record['linked_finances'] ) ) {
						foreach ( $finance_record['linked_finances'] as $f ) {
							$dates[ print_date( $f['transaction_date'] ) ] = true;
						}
					}
					if ( isset( $finance_record['linked_invoice_payments'] ) ) {
						foreach ( $finance_record['linked_invoice_payments'] as $f ) {
							$dates[ print_date( $f['transaction_date'] ) ] = true;
						}
					}
				}
				echo implode( ', ', array_keys( $dates ) );
				?>
			</td>
			<td>
				<a
					href="<?php echo $finance['url']; ?>"><?php echo ! trim( $finance['name'] ) ? 'N/A' : htmlspecialchars( $finance['name'] ); ?></a>
			</td>
			<td><?php echo $finance['customer_id'] ? module_customer::link_open( $finance['customer_id'], true ) : _l( 'N/A' ); ?></td>
			<td><?php
				if ( $finance['invoice_id'] ) {
					$invoice_data = module_invoice::get_invoice( $finance['invoice_id'] );
					echo module_invoice::link_open( $finance['invoice_id'], true, $invoice_data );
				} else {
					_e( 'N/A' );
				}
				?></td>
			<td><?php
				if ( isset( $finance['job_id'] ) && $finance['job_id'] ) {
					echo module_job::link_open( $finance['job_id'], true );
				} else if ( $finance['invoice_id'] && count( $invoice_data['job_ids'] ) ) {
					foreach ( $invoice_data['job_ids'] as $job_id ) {
						echo module_job::link_open( $job_id, true ) . ' ';
					}
				} else {
					_e( 'N/A' );
				} ?></td>
			<?php foreach ( $currencies as $currency_id ) {
				$currency = get_single( 'currency', 'currency_id', $currency_id );
				if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'i' ) { ?>
					<td>
						<?php if ( $currency_id == $finance['currency_id'] ) {
							$total_by_currency[ $finance['currency_id'] ]['total_sub_credit'] += $finance['credit'] > 0 && isset( $finance['sub_amount'] ) && $finance['sub_amount'] > 0 ? $finance['sub_amount'] : 0;
							echo $finance['credit'] > 0 && isset( $finance['sub_amount'] ) && $finance['sub_amount'] > 0 ? '' . dollar( $finance['sub_amount'], true, $finance['currency_id'] ) : '';
						} ?>
					</td>
					<?php
					if ( isset( $available_credit_taxes[ $currency_id ] ) ) {
						foreach ( $available_credit_taxes[ $currency_id ] as $tax_percent => $tf ) {
							?>
							<td><?php
								if ( $currency_id == $finance['currency_id'] && $finance_record && isset( $finance_record['taxes'] ) ) {
									foreach ( $finance_record['taxes'] as $tax ) {
										if ( $finance['credit'] > 0 && $tax['percent'] == $tax_percent ) {
											if ( $tax['amount'] > 0 || $tax['percent'] > 0 ) {
												if ( ! $tax['name'] ) {
													$tax['name'] = _l( 'N/A' );
												}
												if ( ! $tax['percent'] ) {
													$tax['percent'] = 0;
												}
												/*if(!isset($total_by_currency[$finance['currency_id']]['total_credit_tax'][$tax['name']])){
														$total_by_currency[$finance['currency_id']]['total_credit_tax'][$tax['name']] = array();
												}*/
												if ( ! isset( $total_by_currency[ $finance['currency_id'] ]['total_credit_tax'][ $tax['percent'] ] ) ) {
													$total_by_currency[ $finance['currency_id'] ]['total_credit_tax'][ $tax['percent'] ] = 0;
												}
												$total_by_currency[ $finance['currency_id'] ]['total_credit_tax'][ $tax['percent'] ] += isset( $tax['amount'] ) ? $tax['amount'] : 0;
											}
											echo dollar( $tax['amount'], true, $currency_id );
										}
									}
								}
								?></td> <?php
						}
					} ?>
					<td>
						<?php if ( $currency_id == $finance['currency_id'] ) {
							$total_by_currency[ $finance['currency_id'] ]['total_credit'] += $finance['credit'];
							?>
							<span
								class="success_text"><?php echo $finance['credit'] > 0 ? '+' . dollar( $finance['credit'], true, $finance['currency_id'] ) : ''; ?></span>
						<?php } ?>
					</td>
				<?php }
				if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'e' ) { ?>
					<td>
						<?php if ( $currency_id == $finance['currency_id'] ) {
							$total_by_currency[ $finance['currency_id'] ]['total_sub_debit'] += $finance['debit'] > 0 && isset( $finance['sub_amount'] ) && $finance['sub_amount'] > 0 ? $finance['sub_amount'] : 0;
							echo $finance['debit'] > 0 && isset( $finance['sub_amount'] ) && $finance['sub_amount'] > 0 ? '' . dollar( $finance['sub_amount'], true, $finance['currency_id'] ) : '';
						} ?>
					</td>
					<?php
					if ( isset( $available_debit_taxes[ $currency_id ] ) ) {
						foreach ( $available_debit_taxes[ $currency_id ] as $tax_percent => $tf ) {
							?>
							<td><?php
								if ( $currency_id == $finance['currency_id'] && $finance_record && isset( $finance_record['taxes'] ) ) {
									foreach ( $finance_record['taxes'] as $tax ) {
										if ( $finance['debit'] > 0 && $tax['percent'] == $tax_percent ) {
											if ( $tax['amount'] > 0 || $tax['percent'] > 0 ) {
												if ( ! $tax['name'] ) {
													$tax['name'] = _l( 'N/A' );
												}
												if ( ! $tax['percent'] ) {
													$tax['percent'] = 0;
												}
												/*if(!isset($total_by_currency[$finance['currency_id']]['total_debit_tax'][$tax['name']])){
														$total_by_currency[$finance['currency_id']]['total_debit_tax'][$tax['name']] = array();
												}*/
												if ( ! isset( $total_by_currency[ $finance['currency_id'] ]['total_debit_tax'][ $tax['percent'] ] ) ) {
													$total_by_currency[ $finance['currency_id'] ]['total_debit_tax'][ $tax['percent'] ] = 0;
												}
												$total_by_currency[ $finance['currency_id'] ]['total_debit_tax'][ $tax['percent'] ] += isset( $tax['amount'] ) ? $tax['amount'] : 0;
											}
											echo dollar( $tax['amount'], true, $currency_id );
										}
									}
								}
								?></td> <?php
						}
					} ?>
					<td>
						<?php if ( $currency_id == $finance['currency_id'] ) {
							$total_by_currency[ $finance['currency_id'] ]['total_debit'] += $finance['debit'];
							?>
							<span
								class="error_text"><?php echo $finance['debit'] > 0 ? '-' . dollar( $finance['debit'], true, $finance['currency_id'] ) : ''; ?></span>
						<?php } ?>
					</td>
				<?php } ?>
			<?php } ?>
			<td>
				<?php echo htmlspecialchars( $finance['account_name'] ); ?>
			</td>
			<td>
				<?php echo $finance['categories']; ?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
	<tfoot>
	<tr>
		<td><strong><?php _e( 'Totals:' ); ?></strong></td>
		<td colspan="4"></td>
		<?php foreach ( $currencies as $currency_id ) {
			$currency = get_single( 'currency', 'currency_id', $currency_id );
			if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'i' ) { ?>
				<td>
					<?php if ( isset( $total_by_currency[ $currency_id ]['total_sub_credit'] ) ) {
						echo dollar( $total_by_currency[ $currency_id ]['total_sub_credit'], true, $currency_id );
					} ?>
				</td>
				<?php
				if ( isset( $available_credit_taxes[ $currency_id ] ) ) {
					foreach ( $available_credit_taxes[ $currency_id ] as $tax_percent => $tf ) {
						?>
						<td><?php
							if ( isset( $total_by_currency[ $currency_id ]['total_credit_tax'][ $tax_percent ] ) ) {
								echo dollar( $total_by_currency[ $currency_id ]['total_credit_tax'][ $tax_percent ], true, $currency_id );
							}
							?></td> <?php
					}
				} ?>
				<td>
					<?php if ( isset( $total_by_currency[ $currency_id ]['total_credit'] ) ) {
						?>
						<span
							class="success_text">+<?php echo dollar( $total_by_currency[ $currency_id ]['total_credit'], true, $currency_id ); ?></span>
					<?php } ?>
				</td>
			<?php }
			if ( ! isset( $search['type'] ) || $search['type'] == 'ie' || $search['type'] == 'e' ) { ?>
				<td>
					<?php if ( isset( $total_by_currency[ $currency_id ]['total_sub_debit'] ) ) {
						echo dollar( $total_by_currency[ $currency_id ]['total_sub_debit'], true, $currency_id );
					} ?>
				</td>
				<?php
				if ( isset( $available_debit_taxes[ $currency_id ] ) ) {
					foreach ( $available_debit_taxes[ $currency_id ] as $tax_percent => $tf ) {
						?>
						<td><?php
							if ( isset( $total_by_currency[ $currency_id ]['total_debit_tax'][ $tax_percent ] ) ) {
								echo dollar( $total_by_currency[ $currency_id ]['total_debit_tax'][ $tax_percent ], true, $currency_id );
							}
							?></td> <?php
					}
				} ?>
				<td>
					<?php if ( isset( $total_by_currency[ $currency_id ]['total_debit'] ) ) {
						?>
						<span
							class="error_text">-<?php echo dollar( $total_by_currency[ $currency_id ]['total_debit'], true, $currency_id ); ?></span>
					<?php } ?>
				</td>
			<?php } ?>
		<?php } ?>
		<td colspan="2"></td>
	</tr>
	</tfoot>
</table>
