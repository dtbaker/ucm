<?php

if ( ! module_job::can_i( 'edit', 'Job Tasks' ) ) {
	die( '-4' );
}

// todo: check users permissions to access this task.

$task_id = isset( $_GET['task_id'] ) ? (int) $_GET['task_id'] : false;
$job_id  = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : false;

$UCMJob     = new UCMJob( $job_id );
$UCMJobTask = $UCMJob->get_task( $task_id );

$module->page_title = _l( 'Job Task' );

$staff_members    = module_user::get_staff_members();
$staff_member_rel = array();
foreach ( $staff_members as $staff_member ) {
	$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
}

$form_tag_settings = array(
	'action' => module_job::link_open( $job_id ),
	'hidden' => array(
		'_process' => 'save_job_task',
		'job_id'   => $job_id,
		'task_id'  => $task_id,
	),
);
if ( get_display_mode() == 'ajax' ) {
	$form_tag_settings['ajax'] = array(
		'callback' => 'job_task_ajax_saved'
	);
}
module_form::print_form_open_tag( $form_tag_settings );


hook_handle_callback( 'layout_column_half', 1, 50 );

if ( $invoice_id = $UCMJobTask->is_task_invoiced() ) {
	message_box( 'This task has already been invoiced. Invoice: ' . module_invoice::link_open( $invoice_id, true ), 'Warning:', 'warning' );
}

echo $UCMJobTask->generate_edit_fieldset();

if ( function_exists( 'hook_handle_callback' ) ) {

	hook_handle_callback( 'job_task_after', $job_id, $task_id, $UCMJob->get(), $UCMJobTask->get() );
}

hook_handle_callback( 'layout_column_half', 2, 50 );

$form_actions = array(
	'class'    => 'action_bar action_bar_center',
	'elements' => array(
		array(
			'type'  => 'save_button',
			'name'  => 'butt_save',
			'value' => _l( 'Save Task' ),
		),
		array(
			'ignore' => ! ( (int) $task_id && module_job::can_i( 'delete', 'Job Tasks' ) ),
			'type'   => 'delete_button',
			'name'   => 'butt_del_task',
			'value'  => _l( 'Delete' ),
		),
		array(
			'ignore'  => get_display_mode() == 'ajax',
			'type'    => 'button',
			'name'    => 'cancel',
			'value'   => _l( 'Cancel' ),
			'class'   => 'submit_button cancel_button',
			'onclick' => "window.location.href='" . module_job::link_open( $job_id ) . "';",
		),
	),
);

echo module_form::generate_form_actions( $form_actions );

hook_handle_callback( 'layout_column_half', 'end' );

// display legacy timer information from before we moved everything over to the timer module
$task_logs = module_job::get_task_log( $task_id );
if ( $task_logs ) {

	/** START TABLE LAYOUT **/
	ob_start();
	$table_manager         = module_theme::new_table_manager();
	$columns               = array();
	$columns['timer_name'] = array(
		'title'      => 'Description',
		'callback'   => function ( $task_log ) use ( $staff_member_rel ) {
			if ( function_exists( 'decimal_time_out' ) ) {
				$hours_value = decimal_time_out( $task_log['hours'] );
			} else {
				$hours_value = number_out( $task_log['hours'], true );
			}
			_e( '%s hrs <span class="text_shrink">%s</a> - <span class="text_shrink">%s</span>', $hours_value, print_date( $task_log['log_time'], true ), $staff_member_rel[ $task_log['create_user_id'] ] );
		},
		'cell_class' => 'row_action',
	);

	$columns['timer_action'] = array(
		'title'    => ' ',
		'callback' => function ( $task_log ) {
			?>
			<a href="#" class="error_text"
			   onclick="return delete_task_hours(<?php echo $task_log['task_id']; ?>,<?php echo $task_log['task_log_id']; ?>);">x</a>
			<?php
		},
	);


	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $task_logs );
	$table_manager->print_table();
	$timer_table = ob_get_clean();

	$fieldset_data = array();

	$fieldset_data['heading']         = array(
		'type'  => 'h3',
		'title' => 'Old Timer Details',
	);
	$fieldset_data['elements_before'] = $timer_table;
	echo module_form::generate_fieldset( $fieldset_data );
}

if ( class_exists( 'module_timer', false ) && $UCMJobTask->task_id && module_timer::is_plugin_enabled() && module_config::c( 'timer_enable_tasks', 1 ) ) {

	module_timer::display_timers( array(
			'title'           => 'Task Timers',
			'avoid_automatic' => true,
			'owner_table'     => 'job',
			'owner_id'        => $UCMJob->job_id,
			'owner_child_id'  => $UCMJobTask->task_id,
			'customer_id'     => ! empty( $UCMJob->customer_id ) ? (int) $UCMJob->customer_id : 0,
		)
	);
}
module_form::print_form_close_tag();