<?php
if ( class_exists( 'module_job', false ) && module_job::can_i( 'view', 'Jobs' ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Widgets' ) ) {
	// find out how many open jobs are left..
	$jobs = module_job::get_jobs( array( 'completed' => 3 ), array( 'columns' => 'u.job_id' ) );
	ob_start();
	// icons from http://ionicons.com/
	?>

	<div class="small-box bg-aqua">
		<div class="inner">
			<h3>
				<?php echo count( $jobs ); ?>
			</h3>
			<p>
				<?php _e( 'Incomplete Jobs' ); ?>
			</p>
		</div>
		<div class="icon">
			<i class="ion ion-document-text"></i>
		</div>
		<a href="<?php echo module_job::link_open( false ); ?>" class="small-box-footer">
			<?php _e( 'View Jobs' ); ?> <i class="fa fa-arrow-circle-right"></i>
		</a>
	</div>

	<?php
	$widgets[] = array(
		'id'      => 'open_jobs',
		'columns' => 4,
		'raw'     => true,
		'content' => ob_get_clean(),
	);
}