<table class="public">
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
	<tbody>
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
	$year_charge_total     = 0;
	$year_credit_total     = 0;
	$year_balance_total    = 0;

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
				<td class="currency year"><?php echo dollar( $year_charge_total, 2 ); ?></td>
				<td class="currency year"><?php echo dollar( $year_credit_total, 2 ); ?></td>
				<td class="currency year"><?php echo dollar( $year_balance_total, 2 ); ?></td>
			</tr>
			<?php
			$year_charge_total  = 0;
			$year_credit_total  = 0;
			$year_balance_total = 0;
		}

		$year_charge_total  += $transaction['charge'];
		$year_credit_total  += $transaction['credit'];
		$year_balance_total = $current_charge_total - $current_credit_total;

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

	<!-- YEAR CHANGE -->
	<tr>
		<td class="currency year" colspan="3">TOTAL (<?php echo substr( $parent_date, - 4 ); ?>):</td>
		<td class="currency year"><?php echo dollar( $year_charge_total, 2 ); ?></td>
		<td class="currency year"><?php echo dollar( $year_credit_total, 2 ); ?></td>
		<td class="currency year"><?php echo dollar( $year_balance_total, 2 ); ?></td>
	</tr>
	</tbody>
	<tfoot>
	<tr>
		<td class="currency totals" colspan="3">TOTAL (all):</td>
		<td class="currency totals"><?php echo dollar( $charge_total, 2 ); ?></td>
		<td class="currency totals"><?php echo dollar( $credit_total, 2 ); ?></td>
		<td class="currency totals"><?php echo dollar( $total2, 2 ); ?></td>
	</tr>
	</tfoot>
</table>