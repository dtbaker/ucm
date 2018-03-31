<?php
if(!module_change_request::can_i('edit','Change Requests'))die('no perms');
$change_request_id = (int)$_REQUEST['change_request_id'];
$change_request = module_change_request::get_change_request($change_request_id);
if(!$change_request['website_id'])die('no linked website');
$website_data = module_website::get_website($change_request['website_id']);

print_heading(_l('Email Change Request: %s',$change_request['url']));


module_template::init_template('change_request_email','Dear {NAME},<br>
<br>
This email is regarding your recent change request on the website {URL}.<br><br>
<em>{REQUEST}</em><br><br>
You can view and modify this change request by <a href="{CHANGE_REQUEST_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
','Change Request: {URL}',array(
                                       'NAME' => 'Customers Name',
                                       'URL' => 'Website address',
                                       'REQUEST' => 'Change REquest',
                                       'FROM_NAME' => 'Your name',
                                       'CHANGE_REQUEST_URL' => 'Link to change request for customer',
                                       ));



// template for sending emails.
// are we sending the paid one? or the dueone.
//$template_name = 'change_request_email';
$template_name = isset($_REQUEST['template_name']) ? $_REQUEST['template_name'] : 'change_request_email';
$template = module_template::get_template_by_key($template_name);
$change_request['from_name'] = module_security::get_loggedin_name();
$change_request['change_request_url'] = module_change_request::link_public_change($website_data['website_id'],$change_request_id);

ob_start();
$change_request['change_request_tasks'] = ob_get_clean();

// find available "to" recipients.
// customer contacts.
$to_select=false;
if($website_data['customer_id']){
    $customer = module_customer::get_customer($website_data['customer_id']);
    $change_request['customer_name'] = $customer['customer_name'];
    $to = module_user::get_contacts(array('customer_id'=>$website_data['customer_id']));
    if($customer['primary_user_id']){
        $primary = module_user::get_user($customer['primary_user_id']);
        if($primary){
            $to_select = $primary['email'];
        }
    }
}else{
    $to = array();
}

$template->assign_values($change_request);


module_email::print_compose(
    array(
        'find_other_templates' => 'change_request_email', // find others based on this name, eg: change_request_email*
        'current_template' => $template_name,
        'customer_id'=>$website_data['customer_id'],
        'change_request_id'=>$change_request['change_request_id'],
        'debug_message' => 'Sending change request email',

        'to'=>$to,
        'to_select'=>$to_select,
        'bcc'=>module_config::c('admin_email_address',''),
        'content' => $template->render('html'),
        'subject' => $template->replace_description(),
        'success_url'=>module_website::link_open($website_data['website_id']),
        //'success_callback'=>'module_change_request::email_sent('.$change_request_id.',"'.$template_name.'");',
        'cancel_url'=>module_website::link_open($website_data['website_id']),
    )
);
?>