<?php
if(!$contract_safe)die('failed');
if(!module_contract::can_i('edit','Contracts'))die('no perms');
$contract_id = (int)$_REQUEST['contract_id'];
$contract = module_contract::get_contract($contract_id);


// template for sending emails.
// are we sending the paid one? or the dueone.
//$template_name = 'contract_email';
$template_name = isset($_REQUEST['template_name']) ? $_REQUEST['template_name'] : 'contract_email';
$template = module_template::get_template_by_key($template_name);
$contract['total_amount_print'] = dollar($contract['total_amount'],true,$contract['currency_id']);
$contract['total_amount_due_print'] = dollar($contract['total_amount_due'],true,$contract['currency_id']);
$contract['contract_name'] = $contract['name'];
$contract['from_name'] = module_security::get_loggedin_name();
$contract['contract_url'] = module_contract::link_public($contract_id);


ob_start();
include(module_theme::include_ucm('includes/plugin_contract/template/contract_task_list.php'));
$public_html = ob_get_clean();
$contract['task_list'] = $public_html;

/*ob_start();
$contract_data = $contract;
$ignore_task_hook=true;
$for_email=true;
include('contract_public.php');
$contract['contract_tasks'] = ob_get_clean();*/

// generate the PDF ready for sending.
$pdf = module_contract::generate_pdf($contract_id);

// find available "to" recipients.
// customer contacts.
$to_select=false;
if($contract['customer_id']){
    $customer = module_customer::get_customer($contract['customer_id']);
    $contract['customer_name'] = $customer['customer_name'];
    $to = module_user::get_contacts(array('customer_id'=>$contract['customer_id']));
    if($contract['contact_user_id'] > 0){
	    $primary = module_user::get_user($contract['contact_user_id']);
        if($primary){
            $to_select = $primary['email'];
        }
    }else if($customer['primary_user_id']){
        $primary = module_user::get_user($customer['primary_user_id']);
        if($primary){
            $to_select = $primary['email'];
        }
    }
}else{
    $to = array();
}

$template->assign_values($contract);

ob_start();
module_email::print_compose(
    array(
        'title' => _l('Email Contract: %s',$contract['name']),
        'find_other_templates' => 'contract_email', // find others based on this name, eg: contract_email*
        'current_template' => $template_name,
        'customer_id'=>$contract['customer_id'],
        'contract_id'=>$contract['contract_id'],
        'debug_message' => 'Sending contract as email',

        'to'=>$to,
        'to_select'=>$to_select,
        'bcc'=>module_config::c('admin_email_address',''),
        'content' => $template->render('html'),
        'subject' => $template->replace_description(),
        'success_url'=>module_contract::link_open($contract_id),
        //'success_callback'=>'module_contract::email_sent('.$contract_id.',"'.$template_name.'");',
        'cancel_url'=>module_contract::link_open($contract_id),
        'attachments' => array(
            array(
                'path'=>$pdf,
                'name'=>basename($pdf),
                'preview'=>module_contract::link_public_print($contract_id),
            ),
        ),
    )
);

