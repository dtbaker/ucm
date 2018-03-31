<?php
if ( ! $quote_safe ) {
	die( 'failed' );
}
if ( ! module_quote::can_i( 'edit', 'Quotes' ) ) {
	die( 'no perms' );
}
$quote_id    = (int) $_REQUEST['quote_id'];
$staff_id    = (int) $_REQUEST['staff_id'];
$staff       = module_user::get_user( $staff_id );
$quote       = module_quote::get_quote( $quote_id );
$quote_tasks = module_quote::get_tasks( $quote_id );


// template for sending emails.
// are we sending the paid one? or the dueone.
$template_name       = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'quote_staff_email';
$template            = module_template::get_template_by_key( $template_name );
$quote['quote_name'] = $quote['name'];
$quote['staff_name'] = $staff['name'];
$quote['quote_url']  = module_quote::link_open( $quote_id );

$quote['quote_tasks'] = '<ul>';
$quote['task_count']  = 0;
foreach ( $quote_tasks as $quote_task ) {
	if ( $quote_task['user_id'] != $staff_id ) {
		continue;
	}
	$quote['quote_tasks'] .= '<li><strong>' . $quote_task['description'] . '</strong>';
	$quote['quote_tasks'] .= ' <br/>';
	if ( $quote_task['long_description'] ) {
		$quote['quote_tasks'] .= _l( 'Notes:' ) . ' <em>' . $quote_task['long_description'] . '</em><br/>';
	}
	if ( $quote_task['hours'] ) {
		$quote['quote_tasks'] .= _l( 'Hours:' ) . ' ' . $quote_task['hours'] . '<br/>';
	}
	$quote['quote_tasks'] .= '</li>';
	$quote['task_count'] ++;
}
$quote['quote_tasks'] .= '</ul>';

// find available "to" recipients.
// customer contacts.
$to   = array();
$to[] = array(
	'name'  => $staff['name'],
	'email' => $staff['email'],
);

$template->assign_values( $quote );

module_email::print_compose(
	array(
		'title'                => _l( 'Email Quote: %s', $quote['name'] ),
		'find_other_templates' => 'quote_staff_email', // find others based on this name, eg: quote_staff_email*
		'current_template'     => $template_name,
		'quote_id'             => $quote['quote_id'],
		'debug_message'        => 'Sending quote to staff',
		'to'                   => $to,
		'bcc'                  => module_config::c( 'admin_email_address', '' ),
		'content'              => $template->render( 'html' ),
		'subject'              => $template->replace_description(),
		'success_url'          => module_quote::link_open( $quote_id ),
		//'success_callback'=>'module_quote::staff_email_sent('.$quote_id.',"'.$template_name.'");',
		/*'success_callback'=>'module_quote::staff_email_sent',
		'success_callback_args'=>array(
				'quote_id' => $quote_id,
				'template_name' => $template_name,
		),*/
		'cancel_url'           => module_quote::link_open( $quote_id ),
	)
);
