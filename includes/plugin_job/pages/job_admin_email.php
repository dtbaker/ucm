<?php
if ( ! $job_safe ) {
	die( 'failed' );
}
if ( ! module_job::can_i( 'edit', 'Jobs' ) ) {
	die( 'no perms' );
}
$job_id = (int) $_REQUEST['job_id'];
$job    = module_job::get_job( $job_id );


module_template::init_template( 'job_email', 'Dear {CUSTOMER_NAME},<br>
<br>
Please find below details on your job request: {JOB_NAME}.<br><br>
You can also view this job online by <a href="{JOB_URL}">clicking here</a>.<br><br>
{JOB_TASKS}<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Job: {JOB_NAME}', array(
	'CUSTOMER_NAME'    => 'Customers Name',
	'JOB_NAME'         => 'Job Name',
	'TOTAL_AMOUNT'     => 'Total amount of job',
	'TOTAL_AMOUNT_DUE' => 'Total amount of job remaining to be paid',
	'FROM_NAME'        => 'Your name',
	'JOB_URL'          => 'Link to job for customer',
	'JOB_TASKS'        => 'Output of job tasks similar to public link',
) );


// template for sending emails.
// are we sending the paid one? or the dueone.
//$template_name = 'job_email';
$template_name                 = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'job_email';
$template                      = module_template::get_template_by_key( $template_name );
$job['total_amount_print']     = dollar( $job['total_amount'], true, $job['currency_id'] );
$job['total_amount_due_print'] = dollar( $job['total_amount_due'], true, $job['currency_id'] );
$job['job_name']               = $job['name'];
$job['from_name']              = module_security::get_loggedin_name();
$job['job_url']                = module_job::link_public( $job_id );

ob_start();
$job_data         = $job;
$ignore_task_hook = true;
$for_email        = true;
include( 'job_public.php' );
$job['job_tasks'] = ob_get_clean();

// find available "to" recipients.
// customer contacts.
$to_select = false;
if ( $job['customer_id'] ) {
	$customer             = module_customer::get_customer( $job['customer_id'] );
	$job['customer_name'] = $customer['customer_name'];
	$to                   = module_user::get_contacts( array( 'customer_id' => $job['customer_id'] ) );
	if ( $customer['primary_user_id'] ) {
		$primary = module_user::get_user( $customer['primary_user_id'] );
		if ( $primary ) {
			$to_select = $primary['email'];
		}
	}
} else {
	$to = array();
}

$template->assign_values( $job );

module_email::print_compose(
	array(
		'title'                => _l( 'Email Job: %s', $job['name'] ),
		'find_other_templates' => 'job_email', // find others based on this name, eg: job_email*
		'current_template'     => $template_name,
		'customer_id'          => $job['customer_id'],
		'job_id'               => $job['job_id'],
		'debug_message'        => 'Sending job as email',

		'to'          => $to,
		'to_select'   => $to_select,
		'bcc'         => module_config::c( 'admin_email_address', '' ),
		'content'     => $template->render( 'html' ),
		'subject'     => $template->replace_description(),
		'success_url' => module_job::link_open( $job_id ),
		//'success_callback'=>'module_job::email_sent('.$job_id.',"'.$template_name.'");',
		'cancel_url'  => module_job::link_open( $job_id ),
	)
);

