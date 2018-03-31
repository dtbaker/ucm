<?php

$count_widgets = array();

if ( class_exists( 'module_invoice', false ) && module_invoice::can_i( 'view', 'Invoices' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open invoices are left..
	$count    = 0;
	$invoices = module_invoice::get_invoices( array(), array(
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
		$count ++;
	}

	$count_widgets[] = array(
		'id'      => 'open_invoices',
		'columns' => 4,
		'counter' => true,
		'link'    => module_invoice::link_open( false ),
		'count'   => $count,
		'hidden'  => ! $count,
		'title'   => _l( 'Overdue Invoices' ),
	);
}

if ( class_exists( 'module_job', false ) && module_job::can_i( 'view', 'Jobs' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open jobs are left..
	$jobs = module_job::get_jobs( array( 'completed' => 3 ), array( 'columns' => 'u.job_id' ) );

	$count_widgets[] = array(
		'id'      => 'open_jobs',
		'columns' => 4,
		'counter' => true,
		'link'    => module_job::link_open( false ),
		'count'   => count( $jobs ),
		'hidden'  => ! count( $jobs ),
		'title'   => _l( 'Incomplete Jobs' ),
	);
}

if ( class_exists( 'module_customer', false ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	$customer_types = module_customer::get_customer_types();
	foreach ( $customer_types as $customer_type ) {
		if ( ! empty( $customer_type['type_name_plural'] ) && $customer_type['customer_type_id'] ) {
			if ( module_customer::can_i( 'view', $customer_type['type_name_plural'] ) ) {
				// find out how many open customers are left..
				$customers = module_customer::get_customers( array(
					'customer_type_id' => $customer_type['customer_type_id'],
				), true );
				// icons from http://ionicons.com/

				$link            = module_customer::link_open( false );
				$link            = $link . ( strpos( $link, '?' ) ? '&' : '?' ) . 'customer_type_id=' . $customer_type['customer_type_id'];
				$count_widgets[] = array(
					'id'      => 'open_customers_' . $customer_type['customer_type_id'],
					'columns' => 4,
					'counter' => true,
					'hidden'  => ! mysqli_num_rows( $customers ),
					'link'    => $link,
					'count'   => mysqli_num_rows( $customers ),
					'title'   => htmlspecialchars( $customer_type['type_name_plural'] ),
				);
			}
		}
	}
}
if ( class_exists( 'module_ticket', false ) && module_ticket::can_i( 'view', 'Tickets' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open tickets are left..
	$ticket_count = module_ticket::get_total_ticket_count();

	$count_widgets[] = array(
		'id'      => 'open_tickets',
		'columns' => 4,
		'counter' => true,
		'link'    => module_ticket::link_open( false ),
		'hidden'  => ! $ticket_count,
		'count'   => $ticket_count,
		'title'   => _l( 'Open Tickets' ),
	);
}

$count_widgets = hook_filter_var( 'page_count_widgets', $count_widgets, 'pages-home' );

if ( $count_widgets ) {
	ob_start();
	$colors      = array( 'red', 'green', 'blue', 'yellow' );
	$color_count = 0;
	?>
	<div class="circle-stats">
		<div class="fake-table">
			<div class="fake-table-cell">
				<?php foreach ( $count_widgets as $counter_circle ) {
					if ( ! empty( $counter_circle['hidden'] ) ) {
						continue;
					}
					?>
					<div class="circle <?php echo $colors[ $color_count ];
					$color_count ++;
					if ( $color_count >= count( $colors ) ) {
						$color_count = 0;
					} ?>">
						<a href="<?php echo $counter_circle['link']; ?>" class="fake-table">
							<div class="fake-table-cell">
								<p class="counter"><?php echo $counter_circle['count']; ?></p>
								<span><?php echo $counter_circle['title']; ?></span>
							</div>
						</a>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<?php
	$widgets[] = array(
		'id'      => 'counter_widget',
		'title'   => false,
		'icon'    => false,
		'columns' => 1,
		'content' => ob_get_clean(),
	);
}