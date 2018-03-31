<?php

$search = (isset($_REQUEST['search']) && is_array($_REQUEST['search'])) ? $_REQUEST['search'] : array();
if(isset($_REQUEST['customer_id'])){
    $search['customer_id'] = $_REQUEST['customer_id'];
}
if(isset($_REQUEST['job_id']) && (int)$_REQUEST['job_id']>0){
    $search['job_id'] = (int)$_REQUEST['job_id'];
    //$job = module_job::get_job($search['job_id'],false);
}
$emails = module_email::get_emails($search);


$header = array(
    'title' => _l('Customer Emails'),
    'type' => 'h2',
    'main' => true,
    'button' => array(),
);
if(module_email::can_i('create','Emails')){
    $header['button'] = array(
        'url' => module_email::link_open('new'),
        'title' => _l('Send New Email'),
        'type' => 'add',
    );
}
print_heading($header);
?>


<form action="" method="post">

<?php module_form::print_form_auth();?>

<?php $search_bar = array(
    'elements' => array(
        'name' => array(
            'title' => _l('Email Subject:'),
            'field' => array(
                'type' => 'text',
                'name' => 'search[generic]',
                'value' => isset($search['generic'])?$search['generic']:'',
            )
        ),
        'date' => array(
            'title' => _l('Sent Date:'),
            'fields' => array(
                array(
                    'type' => 'date',
                    'name' => 'search[date_from]',
                    'value' => isset($search['date_from'])?$search['date_from']:'',
                ),
                _l('to'),
                array(
                    'type' => 'date',
                    'name' => 'search[date_to]',
                    'value' => isset($search['date_to'])?$search['date_to']:'',
                ),

            )
        ),
    )
);
echo module_form::search_bar($search_bar);



$table_manager = module_theme::new_table_manager();
$columns = array();
$columns['email_subject'] = array(
    'title' => 'Email Subject',
    'callback' => function($email){
        echo module_email::link_open($email['email_id'],true);
    },
    'cell_class' => 'row_action',
);
$columns['email_date'] = array(
    'title' => 'Sent Date',
    'callback' => function($email){
        switch($email['status']){
            case _MAIL_STATUS_SCHEDULED:
                echo "Scheduled ".print_date($email['schedule_time'],true);
                break;
            default:
	            echo print_date($email['sent_time'], true);
        }

    },
);
$columns['email_to'] = array(
    'title' => 'Sent To',
    'callback' => function($email){
		$headers = unserialize($email['headers']);
        if(isset($headers['to']) && is_array($headers['to'])){
            foreach($headers['to'] as $to){
                echo $to['email'].' ';
            }
        }
    },
);
$columns['email_from'] = array(
    'title' => 'Sent By',
    'callback' => function($email){
        echo module_user::link_open($email['create_user_id'],true);
    },
);
if(!isset($_REQUEST['customer_id'])) {
	$columns['email_customer'] = array(
		'title'    => 'Customer',
		'callback' => function ( $email ) {
			echo module_customer::link_open($email['customer_id'],true);
		},
	);
}
$table_manager->set_columns($columns);
$table_manager->row_callback = function($row_data){
    // load the full email data before displaying each row so we have access to more details
	if(isset($row_data['email_id']) && (int)$row_data['email_id']>0){
	    // not needed in this case
	    //return module_email::get_email($row_data['email_id']);
    }
    return array();
};
$table_manager->set_rows($emails);
$table_manager->pagination = true;
$table_manager->print_table();
?>
</form>