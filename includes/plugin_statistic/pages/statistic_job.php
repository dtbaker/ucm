<?php


$page_title = _l( 'Job Report' );

$search      = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array(
	'date_from' => print_date( date( 'Y-m-d', strtotime( '-1 month' ) ) ),
	'date_to'   => print_date( date( 'Y-m-d' ) )
);
$job_reports = module_statistic::get_statistics_jobs( $search );


if ( ! module_statistic::can_i( 'view', 'Job Report' ) ) {
	redirect_browser( _BASE_HREF );
}


print_heading( array(
	'title' => 'Job Report',
	'type'  => 'h2',
	'main'  => true,
) );
?>


<p>This report will show an overview of all jobs that have been created (or are due to be renewed) within a specified
	date range.</p>

<form action="" method="post" id="statistic_form">

	<?php
	$search_bar = array(
		'elements' => array(
			'date' => array(
				'title'  => _l( 'Job Create/Renew Date:' ),
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
				'title'  => _l( 'Job Type:' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'search[type]',
						'value'   => isset( $search['type'] ) ? $search['type'] : '',
						'options' => module_job::get_types(),
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
		<th id="sort_jobname"><?php _e( 'Job Name' ); ?></th>
		<th id="sort_website"><?php echo _l( module_config::c( 'project_name_single', 'Website' ) ); ?></th>
		<th id="sort_customer"><?php _e( 'Customer' ); ?></th>
		<th id="sort_jobtype"><?php _e( 'Job Type' ); ?></th>
		<th id="sort_startdate"><?php _e( 'Job Date' ); ?></th>
		<th id="sort_hours"><?php _e( 'Task Hours' ); ?></th>
		<th id="sort_amount"><?php _e( 'Job Amount' ); ?></th>
		<th id="sort_invoice"><?php _e( 'Invoice' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$c     = 0;
	$total = array(
		'total_hours'             => 0,
		'total_amount_invoicable' => array(),
		'invoice_paid'            => array(),
		'invoice_unpaid'          => array(),
		'invoice_pending'         => array(),
	);
	foreach ( $job_reports as $original_job_data ) {
		$job_data             = module_job::get_job( $original_job_data['job_id'], true );
		$total['total_hours'] += $job_data['total_hours'];
		if ( ! isset( $total['total_amount_invoicable'][ $job_data['currency_id'] ] ) ) {
			$total['total_amount_invoicable'][ $job_data['currency_id'] ] = 0;
		}
		$total['total_amount_invoicable'][ $job_data['currency_id'] ] += $job_data['total_amount'];
		?>
		<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
			<td>
				<?php echo module_job::link_open( $job_data['job_id'], true, $job_data ); ?>
				<?php if ( isset( $original_job_data['renew_from_job_id'] ) ) {
					_e( '(will renew on %s)', print_date( $original_job_data['date_start'] ) );
				} ?>
			</td>
			<td>
				<?php echo module_website::link_open( $original_job_data['website_id'], true ); ?>
			</td>
			<td>
				<?php echo module_customer::link_open( $job_data['customer_id'], true ); ?>
			</td>
			<td>
				<?php echo htmlspecialchars( $original_job_data['type'] ); ?>
			</td>
			<td>
				<?php echo print_date( $original_job_data['date_start'] );
				//is there a renewal date?
				if ( isset( $original_job_data['date_renew'] ) && $original_job_data['date_renew'] && $original_job_data['date_renew'] != '0000-00-00' ) {
					_e( ' to %s', print_date( strtotime( "-1 day", strtotime( $original_job_data['date_renew'] ) ) ) );
				}
				?>
			</td>
			<td>
				<?php echo $job_data['total_hours']; ?>
			</td>
			<td>
				<?php echo dollar( $job_data['total_amount'], true, $job_data['currency_id'] ); ?>
			</td>
			<td>
				<?php
				$uninvoiced_amount = $job_data['total_amount'];
				if ( ! isset( $original_job_data['renew_from_job_id'] ) ) {
					foreach ( module_invoice::get_invoices( array( 'job_id' => $job_data['job_id'] ) ) as $invoice ) {
						$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
						echo module_invoice::link_open( $invoice['invoice_id'], true );
						echo " ";
						if ( $invoice['total_amount'] > 0 ) {
							$uninvoiced_amount -= $uninvoiced_amount;
						}
						if ( $invoice['total_amount_due'] > 0 ) {
							echo '<span class="error_text">';
							if ( ! isset( $total['invoice_unpaid'][ $invoice['currency_id'] ] ) ) {
								$total['invoice_unpaid'][ $invoice['currency_id'] ] = 0;
							}
							$total['invoice_unpaid'][ $invoice['currency_id'] ] += $invoice['total_amount_due'];
							echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
							echo ' ' . _l( 'due' );
						}
						if ( $invoice['total_amount_paid'] > 0 ) {
							echo '<span class="success_text">';
							if ( ! isset( $total['invoice_paid'][ $invoice['currency_id'] ] ) ) {
								$total['invoice_paid'][ $invoice['currency_id'] ] = 0;
							}
							$total['invoice_paid'][ $invoice['currency_id'] ] += $invoice['total_amount_paid'];
							echo _l( '%s paid', dollar( $invoice['total_amount_paid'], true, $invoice['currency_id'] ) );
						}
						echo '</span>';
						echo "<br>";
					}
				}
				if ( $uninvoiced_amount > 0 ) {
					if ( ! isset( $total['invoice_pending'][ $job_data['currency_id'] ] ) ) {
						$total['invoice_pending'][ $job_data['currency_id'] ] = 0;
					}
					$total['invoice_pending'][ $job_data['currency_id'] ] += $uninvoiced_amount;
				}
				?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
	<tfoot>
	<tr>
		<td>
			<strong><?php _e( 'Totals:' ); ?></strong>
		</td>
		<td>
			<?php _e( '(%s jobs)', count( $job_reports ) ); ?>
		</td>
		<td>
		</td>
		<td>
		</td>
		<td>
		</td>
		<td>
			<?php echo $total['total_hours']; ?>
		</td>
		<td>
			<?php foreach ( $total['total_amount_invoicable'] as $currency_id => $amount ) {
				echo dollar( $amount, true, $currency_id );
				echo '<br/> ';
			} ?>
		</td>
		<td>
			<?php foreach ( $total['invoice_pending'] as $currency_id => $amount ) {
				echo dollar( $amount, true, $currency_id );
				echo ' ' . _l( 'uninvoiced' ) . '<br/> ';
			}
			foreach ( $total['invoice_paid'] as $currency_id => $amount ) {
				echo '<span class="success_text">';
				echo dollar( $amount, true, $currency_id );
				echo '</span>';
				echo ' ' . _l( 'paid' ) . '<br/> ';
			}
			foreach ( $total['invoice_unpaid'] as $currency_id => $amount ) {
				echo '<span class="error_text">';
				echo dollar( $amount, true, $currency_id );
				echo '</span>';
				echo ' ' . _l( 'unpaid' ) . '<br/> ';
			}

			?>
		</td>
	</tr>
	</tfoot>
</table>
