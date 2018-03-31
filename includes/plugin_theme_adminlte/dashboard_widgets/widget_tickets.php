<?php
if ( class_exists( 'module_ticket', false ) && module_ticket::can_i( 'view', 'Tickets' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open tickets are left..
	$ticket_count = module_ticket::get_total_ticket_count();
	ob_start();
	// icons from http://ionicons.com/
	?>

	<div class="small-box bg-green">
		<div class="inner">
			<h3>
				<?php echo $ticket_count; ?>
			</h3>
			<p>
				<?php _e( 'Open Tickets' ); ?>
			</p>
		</div>
		<div class="icon"><i class="ion ion-ios7-pricetag-outline"></i></div>
		<a href="<?php echo module_ticket::link_open( false ); ?>" class="small-box-footer">
			<?php _e( 'View Tickets' ); ?> <i class="fa fa-arrow-circle-right"></i>
		</a>
	</div>

	<?php
	$widgets[] = array(
		'id'      => 'open_tickets',
		'columns' => 4,
		'raw'     => true,
		'content' => ob_get_clean(),
	);
}