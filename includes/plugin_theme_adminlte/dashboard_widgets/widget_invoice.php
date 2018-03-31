<?php
if ( class_exists( 'module_invoice', false ) && module_invoice::can_i( 'view', 'Invoices' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open invoices are left..
	$count         = 0;
	$count_overdue = 0;
	$invoices      = module_invoice::get_invoices( array(), array(
		'custom_where' => " AND u.date_due != '0000-00-00' AND u.date_due <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "' AND u.date_paid = '0000-00-00'",
	) );

	foreach ( $invoices as $invoice ) {
		// needs 'overdue' and stuff which are unfortunately calculated.
		$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
		if ( ! $invoice || $invoice['invoice_id'] != $invoice['invoice_id'] ) {
			continue;
		}
		if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
			continue;
		}
		if ( $invoice['overdue'] ) {
			$count_overdue ++;
		}
		$count ++;
	}
	ob_start();
	// icons from http://ionicons.com/
	?>

	<div class="small-box bg-red">
		<div class="inner">
			<h3>
				<?php echo $count_overdue; ?>
			</h3>
			<p>
				<?php _e( 'Overdue Invoices' ); ?>
			</p>
		</div>
		<div class="icon">
			<i class="ion ion-stats-bars"></i>
		</div>
		<a href="<?php echo module_invoice::link_open( false ); ?>" class="small-box-footer">
			<?php _e( 'View Invoices' ); ?> <i class="fa fa-arrow-circle-right"></i>
		</a>
	</div>

	<?php
	$widgets[] = array(
		'id'      => 'open_invoices',
		'columns' => 4,
		'raw'     => true,
		'content' => ob_get_clean(),
	);
}