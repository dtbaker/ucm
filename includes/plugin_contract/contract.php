<?php


define('_CONTRACT_ACCESS_ALL','All contracts in system');
define('_CONTRACT_ACCESS_CUSTOMER','Contracts from customers I have access to');

define('_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_FOOTER',10);
define('_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_SIDEBAR',11);


class module_contract extends module_base{

	public $links;
	public $contract_types;

    public $version = 2.11;
	//2.11 - 2017-07-28 - terminate date
	//2.1 - 2017-07-23 - initial release


    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
	public static function get_class() {
        return __CLASS__;
    }

    public function init(){
		$this->links = array();
		$this->contract_types = array();
		$this->module_name = "contract";
		$this->module_position = 15.99;

        module_config::register_css('contract','contract.css');
        module_config::register_js('contract','contract.js');

	    hook_add('custom_data_menu_locations','module_contract::hook_filter_custom_data_menu_locations');
	    hook_add('customer_archived','module_contract::customer_archived');
	    hook_add('customer_unarchived','module_contract::customer_unarchived');

		if(class_exists('module_template',false) && module_security::is_logged_in()){
			module_template::init_template('contract_external','{HEADER}<h2>Contract Overview</h2>
Contract Name: <strong>{CONTRACT_NAME}</strong> <br/>
{PROJECT_TYPE} Name: <strong>{PROJECT_NAME}</strong> <br/>
Create Date: <strong>{DATE_CREATE}</strong><br/>
Contract Status: <strong>{if:DATE_APPROVED}Accepted on {DATE_APPROVED}{else}Pending{endif:DATE_APPROVED}</strong> <br/>
<h2>Contract Details</h2> 
{CONTRACT_TEXT}
<br>
{if:date_approved}
<h2>Contract Has Been Accepted</h2>
<p>Thank you, the contract was accepted by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>.</p>
{else}
<h2>Contract Approval Pending</h2>
<p>If you would like to approve this contract please complete the form below:</p>
<form action="" method="POST">
<p>Your Name: <input type="text" name="contract_approve_name"> </p>
<p><input type="checkbox" name="contract_approve_go" value="yes"> Yes, I approve this contract. </p>
<p><input type="submit" name="contract_approve" value="Approve Contract" class="submit_button save_button"></p>
</form>
{endif:date_approved}

','Used when displaying the external view of a contract for approval.','code');


			module_template::init_template('contract_print','<html>
<head>
<title>Contract</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
body{
font-family:Helvetica, sans-serif;
padding:0;
margin:0;
}
td{
font-family:Helvetica, sans-serif;
padding:2px;
}
h3{
font-size: 22px;
font-weight: bold;
margin:10px 0 10px 0;
padding:0 0 5px 0;
border-bottom:1px solid #6f6f6f;
width:100%;
}
.style11 {
font-size: 24px;
font-weight: bold;
margin:0;
padding:0;
}
.task_header,
.task_header th{
background-color:#e8e8e8;
color: #6f6f6f;
font-weight: bold;
}
tr.odd{
background-color:#f9f9f9;
}
</style>
</head>
<body>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
<tbody>
<tr>
<td colspan="2" align="left" valign="top"><img title="Logo" src="http://ultimateclientmanager.com/images/logo_ucm.png" alt="Logo" width="202" height="60" /></td>
<td colspan="2" align="left" valign="top"><span class="style11">CONTRACT</span></td>
</tr>
<tr>
<td width="12%">&nbsp;</td>
<td width="43%">&nbsp;</td>
<td width="14%">&nbsp;</td>
<td width="31%">&nbsp;</td>
</tr>
<tr>
<td><strong>ABN:</strong></td>
<td>12 345 678 912</td>
<td><strong>Contract No: <br /> </strong></td>
<td>{CONTRACT_NUMBER}</td>
</tr>
<tr>
<td><strong>Email: </strong></td>
<td>your@company.com</td>
<td><strong>Issued Date:</strong></td>
<td>{DATE_CREATE}</td>
</tr>
<tr>
<td><strong>Web: </strong></td>
<td>www.company.com</td>
<td><strong>Valid Until:</strong></td>
<td>{DATE_TERMINATE}</td>
</tr>
</tbody>
</table>
<h3>RECIPIENT</h3>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
<tbody>
<tr>
<td valign="top" width="12%"><strong>Company:</strong></td>
<td valign="top" width="43%">{CUSTOMER_NAME}</td>
<td valign="top" width="14%"><strong>Email:</strong></td>
<td valign="top" width="31%">{CONTACT_EMAIL}</td>
</tr>
<tr>
<td valign="top"><strong>Contact:</strong></td>
<td valign="top">{CONTACT_NAME}</td>
<td valign="top"><strong>{PROJECT_TYPE}:</strong>&nbsp;</td>
<td valign="top">{PROJECT_NAME}&nbsp;</td>
</tr>
<tr>
<td valign="top"><strong>Phone:</strong></td>
<td valign="top">{CONTACT_PHONE}</td>
<td valign="top">&nbsp;</td>
<td valign="top">&nbsp;</td>
</tr>
</tbody>
</table>
<h3>CONTRACT DETAILS</h3>
<div>{CONTRACT_TEXT}</div>
<p>&nbsp;</p>
<h3>CONTRACT APPROVAL</h3>
<p>{if:DATE_APPROVED}Thank you, this Contract was approved by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>{else} If you are happy with this contract please click the buttom below to process approval.</p>
<p><a href="{CONTRACT_LINK}">Approve This Contract</a>{endif:DATE_APPROVED}</p>
</body>
</html>','Used for printing out an contract for the customer.','html');


			module_template::init_template('contract_email','Dear {CUSTOMER_NAME},<br>
<br>
Please find attached details on your contract: {CONTRACT_NAME}.<br><br>
You can view and approve this contract online by <a href="{CONTRACT_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
','Contract: {CONTRACT_NAME}',array(
               'CUSTOMER_NAME' => 'Customers Name',
               'CONTRACT_NAME' => 'Contract Name',
               'TOTAL_AMOUNT' => 'Total amount of contract',
               'TOTAL_AMOUNT_DUE' => 'Total amount of contract remaining to be paid',
               'FROM_NAME' => 'Your name',
               'CONTRACT_URL' => 'Link to contract for customer',
               'CONTRACT_TASKS' => 'Output of contract tasks similar to public link',
               ));


			module_template::init_template('contract_approved_email','Dear {TO_NAME},<br>
<br>
This Contract has been approved: {CONTRACT_NAME}.<br><br>
This Contract was approved by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>
You can view this contract by <a href="{CONTRACT_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
','Contract Approved: {CONTRACT_NAME}',array(
               'CONTRACT_NAME' => 'Contract Name',
               'CONTRACT_URL' => 'Link to contract for customer',
               ));

		}

	}

    public function pre_menu(){

        if($this->can_i('view','Contracts')){
            // only display if a customer has been created.
            if(isset($_REQUEST['customer_id']) && $_REQUEST['customer_id'] && $_REQUEST['customer_id']!='new'){
                // how many contracts?
	            $name = _l('Contracts');
	            if(module_config::c('menu_show_summary',0)) {
		            $contracts = $this->get_contracts( array( 'customer_id' => $_REQUEST['customer_id'] ) );
		            if ( count( $contracts ) ) {
			            $name .= " <span class='menu_label'>" . count( $contracts ) . "</span> ";
		            }
	            }
                $this->links[] = array(
                    "name"=>$name,
                    "p"=>"contract_admin",
                    'args'=>array('contract_id'=>false),
                    'holder_module' => 'customer', // which parent module this link will sit under.
                    'holder_module_page' => 'customer_admin_open',  // which page this link will be automatically added to.
                    'menu_include_parent' => 0,
                    'icon_name' => 'edit',
                );
            }
            $this->links[] = array(
                "name"=>"Contracts",
                "p"=>"contract_admin",
                'args'=>array('contract_id'=>false),
                'icon_name' => 'edit',
            );
        }

    }

	public static function is_plugin_enabled(){
        if(parent::is_plugin_enabled()){
            // check if contract base exists.
            if( !class_exists('UCMBaseDocument') ){
                set_error('Please upgrade to the latest version of UCM');
                return false;
            }
            return true;
        }
        return false;
	}

    public function ajax_search($search_key){
        // return results based on an ajax search.
        $ajax_results = array();
        $search_key = trim($search_key);
        if(strlen($search_key) > module_config::c('search_ajax_min_length',2)){
            $results = $this->get_contracts(array('generic'=>$search_key));
            if(count($results)){
                foreach($results as $result){
                    $match_string = _l('Contract: ');
                    $match_string .= _shl($result['name'],$search_key);
                    $ajax_results [] = '<a href="'.$this->link_open($result['contract_id']) . '">' . $match_string . '</a>';
                }
            }
        }
        return $ajax_results;
    }



    public function handle_hook($hook,&$calling_module=false,$show_all=false){
		switch($hook){
            case 'dashboard_widgets':
                // see finance for example of widget usage.
                break;
			case "home_alerts":

                $cache_timeout = module_config::c('cache_objects',60);
                $cache_key = 'home_alerts_'.module_security::get_loggedin_id();

				$alerts = array();

                if($show_all || module_config::c('contract_alerts',1)){
                    // find any contracts that are past the due date and dont have a finished date.

                    $key = _l('Unapproved Contract');
                    if(class_exists('module_dashboard',false)){
                        $columns = array(
                            'contract'=>_l('Contract Title'),
                            'customer'=>_l('Customer'),
                            'website'=>module_config::c('project_name_single','Website'),
                            'assigned_staff'=>_l('Staff'),
                            'date'=>_l('Sent Date'),
                            'days'=>_l('Day Count'),
                        );
                        if(!class_exists('module_website',false) || !module_website::is_plugin_enabled()){
                            unset($columns['website']);
                        }
                        if(!module_customer::can_i('view','Customers')){
                            unset($columns['customer']);
                        }
                        module_dashboard::register_group($key,array(
                            'columns'=>$columns,
                            'sort'=>array(
                                'time'=>'DESC',
                            )
                        ));
                    }
                    if($cached_alerts = module_cache::get('contract',$cache_key.$key)){
                         $alerts = array_merge($alerts, $cached_alerts);
                    }else{
                        module_debug::log(array(
                            'title' => 'Contract Home Alerts: ',
                            'data' => " starting: ".$key,
                         ));
                        $this_alerts = array();
	                    if(module_config::c('contract_dashboard_show_all_unapproved',1)) {
		                    $contracts = self::get_contracts( array(), array(
			                    'custom_where' => " AND u.date_approved = '0000-00-00'"
		                    ) );
	                    }else{
		                    $contracts = self::get_contracts( array(), array(
			                    'custom_where' => " AND u.date_approved = '0000-00-00' AND u.date_create <= '" . date( 'Y-m-d', strtotime( '-' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "'"
		                    ) );
	                    }
                        foreach($contracts as $contract_data){
                            // permission check:
                            $alert_res = process_alert($contract_data['date_create'], 'temp');
                            if($alert_res){
                                $alert_res['link'] = $this->link_open($contract_data['contract_id'],false,$contract_data);
                                $alert_res['name'] = $contract_data['name'];

                                // new dashboard alert layout here:
                                $alert_res['time'] = strtotime($alert_res['date']);
                                $alert_res['group'] = $key;
                                $alert_res['contract'] = $this->link_open($contract_data['contract_id'],true,$contract_data);
                                $alert_res['customer'] = $contract_data['customer_id'] ? module_customer::link_open($contract_data['customer_id'],true) : _l('N/A');
                                $alert_res['website'] = $contract_data['website_id'] ? module_website::link_open($contract_data['website_id'],true) : _l('N/A');
                                $alert_res['assigned_staff'] = $contract_data['user_id'] ? module_user::link_open($contract_data['user_id'],true) : _l('N/A');
                                $alert_res['date'] = print_date($alert_res['date']);
                                $alert_res['days'] = ($alert_res['warning']) ? '<span class="important">'.$alert_res['days'].'</span>' : $alert_res['days'];

                                $this_alerts['contractincomplete'.$contract_data['contract_id']] = $alert_res;
                            }
                        }

                        module_cache::put('contract',$cache_key.$key,$this_alerts,$cache_timeout);
                        $alerts = array_merge($alerts, $this_alerts);
                    }
				}
				return $alerts;
				break;
        }
        return false;
    }

    public static function link_generate($contract_id=false,$options=array(),$link_options=array()){

        // link generation can be cached and save a few db calls.
        $cache_options = $options;
        if(isset($cache_options['data'])){
            unset($cache_options['data']);
            $cache_options['data_name'] = isset($options['data']) && isset($options['data']['name']) ? $options['data']['name'] : '';
        }
        $cache_options['customer_id'] = isset($_REQUEST['customer_id']) ? $_REQUEST['customer_id'] : false;
        $cache_options['current_user'] = module_security::get_loggedin_id();
        $link_cache_key = 'contract_link_'.$contract_id.'_'.md5(serialize($cache_options));
        if($cached_link = module_cache::get('contract',$link_cache_key)){
            return $cached_link;
        }
        $link_cache_timeout = module_config::c('cache_link_timeout',3600);

        $key = 'contract_id';
        if($contract_id === false && $link_options){
            foreach($link_options as $link_option){
                if(isset($link_option['data']) && isset($link_option['data'][$key])){
                    ${$key} = $link_option['data'][$key];
                    break;
                }
            }
            if(!${$key} && isset($_REQUEST[$key])){
                ${$key} = $_REQUEST[$key];
            }
        }
        $bubble_to_module = false;
        if(!isset($options['type']))$options['type']='contract';
        $options['page'] = 'contract_admin';
        if(!isset($options['arguments'])){
            $options['arguments'] = array();
        }
        $options['module'] = 'contract';

        $data = array();
        if(isset($options['data'])){
            $data = $options['data'];
        }

        if(isset($options['full']) && $options['full']){
            // only hit database if we need to print a full link with the name in it.
            if(!isset($options['data']) || !$options['data']){
                if((int)$contract_id>0){
                    $data = self::get_contract($contract_id,false,true);
                }else{
                    $data = array();
                }
                $options['data'] = $data;
            }else{
                $data = $options['data'];
            }
            // what text should we display in this link?
            $options['text'] = (!isset($data['name'])||!trim($data['name'])) ? _l('N/A') : $data['name'];
            if(!$data||!$contract_id||isset($data['_no_access'])){
                $link = $options['text'];
                module_cache::put('contract',$link_cache_key,$link,$link_cache_timeout);
                return $link;
            }
        }else{
            if(isset($_REQUEST['customer_id']) && (int)$_REQUEST['customer_id']>0){
                $data['customer_id'] = (int)$_REQUEST['customer_id'];
            }
        }
        $options['text'] = isset($options['text']) ? ($options['text']) : ''; // htmlspecialchars is done in link_generatE() function
        // generate the arguments for this link
        $options['arguments']['contract_id'] = $contract_id;

        if(isset($data['customer_id']) && $data['customer_id']>0){
            $bubble_to_module = array(
                'module' => 'customer',
                'argument' => 'customer_id',
            );
        }
        array_unshift($link_options,$options);

        if(!module_security::has_feature_access(array(
            'name' => 'Customers',
            'module' => 'customer',
            'category' => 'Customer',
            'view' => 1,
            'description' => 'view',
        ))){

            $bubble_to_module = false;
            /*
            if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : _l('N/A');
            }
            */

        }
        if($bubble_to_module){
            global $plugins;
            $link = $plugins[$bubble_to_module['module']]->link_generate(false,array(),$link_options);
        }else{
            // return the link as-is, no more bubbling or anything.
            // pass this off to the global link_generate() function
            $link = link_generate($link_options);

        }
        module_cache::put('contract',$link_cache_key,$link,$link_cache_timeout);
        return $link;
    }

	public static function link_open($contract_id,$full=false,$data=array()){
        return self::link_generate($contract_id,array('full'=>$full,'data'=>$data));
    }
	public static function link_create_contract_invoice($contract_id,$full=false){
        return self::link_generate($contract_id,array('full'=>$full,'arguments'=>array('_process'=>'ajax_create_invoice')));
    }


    public static function link_public($contract_id,$h=false){
        if($h){
            return md5('s3cret7hash for contract '._UCM_SECRET.' '.$contract_id);
        }
        return full_link(_EXTERNAL_TUNNEL_REWRITE.'m.contract/h.public/i.'.$contract_id.'/hash.'.self::link_public($contract_id,true));
    }

    public static function get_replace_fields($contract_id,$contract_data=false){

        if(!$contract_data)$contract_data = self::get_contract($contract_id);

        $data = array(
            'contract_number' => htmlspecialchars($contract_data['name']),
            'contract_name' => htmlspecialchars($contract_data['name']),
            'project_type' => _l(module_config::c('project_name_single','Website')),
            'print_link' => self::link_public_print($contract_id),
            'contract_url' => self::link_public($contract_id),

            'title' => module_config::s('admin_system_name'),
            'create_date' => print_date($contract_data['date_create']),
            'terminate_date' => print_date($contract_data['date_terminate']),
        );
        if(isset($contract_data['customer_id']) && $contract_data['customer_id']){
            $customer_data = module_customer::get_replace_fields($contract_data['customer_id'], $contract_data['contact_user_id'] ? $contract_data['contact_user_id'] : false);
            $data = array_merge($data,$customer_data); // so we get total_amount_due and stuff.
        }
	    $user_details = array(
	        'staff_first_name' => '',
	        'staff_last_name' => '',
	        'staff_email' => '',
	        'staff_phone' => '',
	        'staff_fax' => '',
	        'staff_mobile' => '',
        );
        if(isset($contract_data['user_id']) && $contract_data['user_id']){
            $user_data = module_user::get_user($contract_data['user_id'],false);
	        if($user_data && $user_data['user_id'] == $contract_data['user_id']){
		        $user_details = array(
			        'staff_first_name' => $user_data['name'],
			        'staff_last_name' => $user_data['last_name'],
			        'staff_email' => $user_data['email'],
			        'staff_phone' => $user_data['phone'],
			        'staff_fax' => $user_data['fax'],
			        'staff_mobile' => $user_data['mobile'],
		        );
	        }

        }
	    $data = array_merge($data,$user_details);

        foreach($contract_data as $key=>$val){
            if(strpos($key,'date')!== false){
                $contract_data[$key] = print_date($val);
            }
        }

	    if(isset($contract_data['contract_text'])){
		    $contract_data['contract_text'] = module_security::purify_html($contract_data['contract_text']);
	    }

//        $customer_data = $contract_data['customer_id'] ? module_customer::get_replace_fields($contract_data['customer_id']) : array();
//        $website_data = $contract_data['website_id'] ? module_website::get_replace_fields($contract_data['website_id']) : array();
//        $data = array_merge($data,$customer_data,$website_data,$contract_data);
        $data = array_merge($data,$contract_data);


        $website_url = $project_names = $project_names_and_url = array();
        if($contract_data['website_id']){
            $website_data = module_website::get_website($contract_data['website_id']);
            if($website_data && $website_data['website_id']==$contract_data['website_id']){
                if(isset($website_data['url']) && $website_data['url']){
                    $website_url[$website_data['website_id']] = module_website::urlify($website_data['url']);
                    $website_data['name_url'] = $website_data['name'] . ' ('.module_website::urlify($website_data['url']).')';
                }else{
                    $website_data['name_url'] = $website_data['name'];
                }
                $project_names[$website_data['website_id']] = $website_data['name'];
                $project_names_and_url[$website_data['website_id']] = $website_data['name_url'];
                $fields = module_website::get_replace_fields($website_data['website_id'], $website_data);
                foreach($fields as $key=>$val){
                    if(!isset($data[$key]) || (!$data[$key] && $val)){
                        $data[$key] = $val;
                    }
                }
            }
        }
        $data['website_name'] = $data['project_name'] = forum_text(count($project_names) ? implode(', ',$project_names) : '');
        $data['website_name_url'] = forum_text(count($project_names_and_url) ? implode(', ',$project_names_and_url) : '');
        $data['website_url'] = forum_text(count($website_url) ? implode(', ',$website_url) : '');


        if(class_exists('module_group',false) && module_group::is_plugin_enabled()){
            // get the contract groups
            $wg = array();
            $g = array();
            if($contract_id>0){
                $contract_data = module_contract::get_contract($contract_id);
                foreach(module_group::get_groups_search(array(
                    'owner_table' => 'contract',
                    'owner_id' => $contract_id,
                )) as $group){
                    $g[$group['group_id']] = $group['name'];
                }
                /*// get the website groups
                foreach(module_group::get_groups_search(array(
                    'owner_table' => 'website',
                    'owner_id' => $contract_data['website_id'],
                )) as $group){
                    $wg[$group['group_id']] = $group['name'];
                }*/
            }
            $data['contract_group'] = implode(', ',$g);
            /*$data['website_group'] = implode(', ',$wg);*/
        }

        // addition. find all extra keys for this contract and add them in.
        // we also have to find any EMPTY extra fields, and add those in as well.
        if(class_exists('module_extra',false) && module_extra::is_plugin_enabled()){
            $all_extra_fields = module_extra::get_defaults('contract');
            foreach($all_extra_fields as $e){
                $data[$e['key']] = _l('N/A');
            }
            // and find the ones with values:
            $extras = module_extra::get_extras(array('owner_table'=>'contract','owner_id'=>$contract_id));
            foreach($extras as $e){
                $data[$e['extra_key']] = $e['extra'];
            }
        }
        // also do this for customer fields
        /*if($contract_data['customer_id']){
            $all_extra_fields = module_extra::get_defaults('customer');
            foreach($all_extra_fields as $e){
                $data[$e['key']] = _l('N/A');
            }
            $extras = module_extra::get_extras(array('owner_table'=>'customer','owner_id'=>$contract_data['customer_id']));
            foreach($extras as $e){
                $data[$e['extra_key']] = $e['extra'];
            }
        }*/


        return $data;
    }


    public static function link_public_print($contract_id,$h=false){
        if($h){
            return md5('s3cret7hash for contract '._UCM_SECRET.' '.$contract_id);
        }
        return full_link(_EXTERNAL_TUNNEL_REWRITE.'m.contract/h.public_print/i.'.$contract_id.'/hash.'.self::link_public_print($contract_id,true));
    }

    public function external_hook($hook){

        switch($hook){
            case 'public_print':
                ob_start();

                $contract_id = (isset($_REQUEST['i'])) ? (int)$_REQUEST['i'] : false;
                $hash = (isset($_REQUEST['hash'])) ? trim($_REQUEST['hash']) : false;
                if($contract_id && $hash){
                    $correct_hash = $this->link_public_print($contract_id,true);
                    if($correct_hash == $hash){
                        // check contract still exists.
                        $contract_data = $this->get_contract($contract_id);
                        if(!$contract_data || $contract_data['contract_id'] != $contract_id){
                            echo 'contract no longer exists';
                            exit;
                        }
                        $pdf_file = $this->generate_pdf($contract_id);

                        if($pdf_file && is_file($pdf_file)){
                            @ob_end_clean();
                            @ob_end_clean();

                            // send pdf headers and prompt the user to download the PDF

                            header("Pragma: public");
                            header("Expires: 0");
                            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                            header("Cache-Control: private",false);
                            header("Content-Type: application/pdf");
                            header("Content-Disposition: attachment; filename=\"".basename($pdf_file)."\";");
                            header("Content-Transfer-Encoding: binary");
                            $filesize = filesize($pdf_file);
                            if($filesize>0){
                                header("Content-Length: ".$filesize);
                            }
                            // some hosting providershave issues with readfile()
                            $read = readfile($pdf_file);
                            if(!$read){
                                echo file_get_contents($pdf_file);
                            }

                        }else{
                            echo _l('Sorry PDF is not currently available.');
                        }
                    }
                }

                exit;

                break;
            case 'public':
                $contract_id = (isset($_REQUEST['i'])) ? (int)$_REQUEST['i'] : false;
                $hash = (isset($_REQUEST['hash'])) ? trim($_REQUEST['hash']) : false;
                if($contract_id && $hash){
                    $correct_hash = $this->link_public($contract_id,true);
                    if($correct_hash == $hash){
                        // all good to print a receipt for this payment.
                        $contract_data = $this->get_contract($contract_id);

                        if($contract_data){

                            if(isset($_POST['contract_approve']) && isset($_POST['contract_approve_go']) && isset($_POST['contract_approve_name']) && strlen($_POST['contract_approve_name'])>0){
                                update_insert('contract_id',$contract_id,'contract',array(
                                    'date_approved'=>date('Y-m-d'),
                                    'approved_by'=>$_POST['contract_approve_name'],
                                ));
	                            self::contract_approved($contract_id);

                                redirect_browser($this->link_public($contract_id));
                            }
                            $contract_data = self::get_replace_fields($contract_id,$contract_data);
                            $customer_data = $contract_data['customer_id'] ? module_customer::get_replace_fields($contract_data['customer_id']) : array();
                            $website_data = $contract_data['website_id'] ? module_website::get_replace_fields($contract_data['website_id']) : array();

                            // correct!
                            // load up the receipt template.
	                        $template = false;
	                        if(!empty($contract_data['contract_template_external'])){
		                        $template = module_template::get_template_by_key($contract_data['contract_template_external']);
		                        if ( ! $template->template_id ) {
			                        $template = false;
		                        }
	                        }
	                        if(!$template) {
		                        $template = module_template::get_template_by_key( 'contract_external' );
	                        }

                            // do we link the contract name?
                            $contract_data['header'] = '';
                            if(module_security::is_logged_in() && $this->can_i('edit','Contracts')){
                                $contract_data['header'] = '<div style="text-align: center; padding: 0 0 10px 0; font-style: italic;">You can send this page to your customer as a contract or progress update (this message will be hidden).</div>';
                            }


                            //$contract_data['contract_name'] = $contract_data['name'];
                            $contract_data['contract_name'] = self::link_open($contract_id,true);
                            // format some dates:
                            $contract_data['date_create'] = $contract_data['date_create'] == '0000-00-00' ? '' : print_date($contract_data['date_create']);
                            $contract_data['date_terminate'] = $contract_data['date_terminate'] == '0000-00-00' ? '' : print_date($contract_data['date_terminate']);
                            $contract_data['date_approved'] = $contract_data['date_approved'] == '0000-00-00' ? '' : print_date($contract_data['date_approved']);

                            $contract_data['project_type'] = _l(module_config::c('project_name_single','Website'));
                            //$website_data = $contract_data['website_id'] ? module_website::get_website($contract_data['website_id']) : array();
                            $contract_data['project_name'] = isset($website_data['name']) && strlen($website_data['name']) ? $website_data['name'] : _l('N/A');
                            $template->assign_values($customer_data);
                            $template->assign_values($website_data);
                            $template->assign_values($contract_data);
                            $template->page_title = $contract_data['name'];
                            echo $template->render('pretty_html');
                        }
                    }
                }
                break;
        }
    }


	public function process(){
		$errors=array();
		if(isset($_REQUEST['butt_del']) && $_REQUEST['butt_del'] && $_REQUEST['contract_id']){
            $data = self::get_contract($_REQUEST['contract_id']);
            if(module_form::confirm_delete('contract_id',"Really delete contract: ".$data['name'],self::link_open($_REQUEST['contract_id']))){
                $this->delete_contract($_REQUEST['contract_id']);
                set_message("contract deleted successfully");
                redirect_browser($this->link_open(false));
            }
		}else if("save_contract" == $_REQUEST['_process']){


			$contract_id = $this->save_contract($_REQUEST['contract_id'],$_POST);
			if(!$contract_id){
				set_error('Failed to save contract');
				redirect_browser(module_contract::link_open(false));
			}

            if(isset($_REQUEST['butt_print']) && $_REQUEST['butt_print']){
                redirect_browser(module_contract::link_public_print($contract_id));
            }
            if(isset($_REQUEST['butt_duplicate']) && $_REQUEST['butt_duplicate'] && module_contract::can_i('create','Contracts')){
	            $new_contract_id = module_contract::duplicate_contract($contract_id);
	            set_message('Contract duplicated successfully');
                redirect_browser(module_contract::link_generate($new_contract_id));
            }

			if(!empty($_REQUEST['butt_archive'])){
				$UCMContract = new UCMContract( $contract_id);
				if($UCMContract->is_archived()){
					$UCMContract->unarchive();
					set_message("Contract unarchived successfully");
				}else {
					$UCMContract->archive();
					set_message("Contract archived successfully");
				}
			}else {
				set_message( "Contract saved successfully" );
			}
            //redirect_browser($this->link_open($contract_id));
            redirect_browser(isset($_REQUEST['_redirect']) && !empty($_REQUEST['_redirect']) ? $_REQUEST['_redirect'] : $this->link_open($contract_id));


		}
		if(!count($errors)){
			redirect_browser($_REQUEST['_redirect']);
			exit;
		}
		print_error($errors,true);
	}



	public static function get_valid_contract_ids(){
        return self::get_contracts(array(),array('columns'=>'u.contract_id'));
    }
	public static function get_contracts($search=array(),$return_options=array()){
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
        $cache_key = 'get_contracts_'.md5(serialize(array($search,$return_options)));
        if($cached_item = module_cache::get('contract',$cache_key)){
            return $cached_item;
        }
        $cache_timeout = module_config::c('cache_objects',60);

		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
        if(isset($return_options['columns'])){
            $sql .= $return_options['columns'];
        }else{
            $sql .= "u.*,u.contract_id AS id ";
            $sql .= ", u.name AS name ";
            $sql .= ", c.customer_name ";
            if(class_exists('module_website',false) && module_website::is_plugin_enabled()){
                $sql .= ", w.name AS website_name";// for export
            }
            $sql .= ", us.name AS staff_member";// for export
        }
        $from = " FROM `"._DB_PREFIX."contract` u ";
        $from .= " LEFT JOIN `"._DB_PREFIX."customer` c USING (customer_id)";
        if(class_exists('module_website',false) && module_website::is_plugin_enabled()){
            $from .= " LEFT JOIN `"._DB_PREFIX."website` w ON u.website_id = w.website_id"; // for export
        }
        $from .= " LEFT JOIN `"._DB_PREFIX."user` us ON u.user_id = us.user_id"; // for export
		$where = " WHERE 1 ";
        if(is_array($return_options) && isset($return_options['custom_where'])){
            // put in return options so harder to push through from user end.
            $where .= $return_options['custom_where'];
        }


		if(!empty($search['archived_status'])){
			switch($search['archived_status']){
				case _ARCHIVED_SEARCH_NONARCHIVED:
					$where .= ' AND u.archived = 0 ';
					break;
				case _ARCHIVED_SEARCH_ARCHIVED:
					$where .= ' AND u.archived = 1 ';
					break;
				case _ARCHIVED_SEARCH_BOTH:
//                    $where .= ' AND u.archived = 0 ';
					break;
			}
		}


		if(isset($search['generic']) && $search['generic']){
			$str = db_escape($search['generic']);
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' "; //OR ";
			//$where .= " u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
        foreach(array('customer_id','website_id','status','type','date_create','date_terminate') as $key){
            if(isset($search[$key]) && $search[$key] !== ''&& $search[$key] !== false){
                $str = db_escape($search[$key]);
                if($str[0]=='!'){
                    // hack for != sql searching.
                    $str = ltrim($str,'!');
                    $where .= " AND u.`$key` != '$str'";
                }else{
                    $where .= " AND u.`$key` = '$str'";
                }
            }
        }
        if(isset($search['ticket_id']) && (int)$search['ticket_id']>0){
	        // join on the ticket_contract_rel tab.e
	        $from .= " LEFT JOIN `"._DB_PREFIX."ticket_contract_rel` tqr USING (contract_id)";
	        $where .= " AND tqr.ticket_id = ".(int)$search['ticket_id'];

        }
        if(isset($search['accepted']) && (int)$search['accepted']>0){
            switch($search['accepted']){
                case 1:
                    // both complete and not complete contracts, dont modify query
                    break;
                case 2:
                    // only completed contracts.
                    $where .= " AND u.date_approved != '0000-00-00'";
                    break;
                case 3:
                    // only non-completed contracts.
                    $where .= " AND u.date_approved = '0000-00-00'";
                    break;
            }
        }
		$group_order = ' GROUP BY u.contract_id ORDER BY u.name';


        switch(self::get_contract_access_permissions()){
            case _CONTRACT_ACCESS_ALL:

                break;
            case _CONTRACT_ACCESS_CUSTOMER:
                // tie in with customer permissions to only get contracts from customers we can access.
                $customers = module_customer::get_customers();
                if(count($customers)){
                    $where .= " AND u.customer_id IN ( ";
                    foreach($customers as $customer){
                        $where .= $customer['customer_id'] .', ';
                    }
                    $where = rtrim($where,', ');
                    $where .= " ) ";
                }
                break;
        }

        // tie in with customer permissions to only get contracts from customers we can access.
        switch(module_customer::get_customer_data_access()){
            case _CUSTOMER_ACCESS_ALL:
                // all customers! so this means all contracts!
                break;
            case _CUSTOMER_ACCESS_ALL_COMPANY:
            case _CUSTOMER_ACCESS_CONTACTS:
            case _CUSTOMER_ACCESS_TASKS:
            case _CUSTOMER_ACCESS_STAFF:
                $valid_customer_ids = module_security::get_customer_restrictions();
                if(count($valid_customer_ids)){
                    $where .= " AND ( u.customer_id = 0 OR u.customer_id IN ( ";
                    foreach($valid_customer_ids as $valid_customer_id){
                        $where .= (int)$valid_customer_id.", ";
                    }
                    $where = rtrim($where,', ');
                    $where .= " )";
                    $where .= " )";
                }

        }

		$sql = $sql . $from . $where . $group_order;
//        echo $sql;print_r(debug_backtrace());exit;
		$result = qa($sql);
		//module_security::filter_data_set("contract",$result);
        module_cache::put('contract',$cache_key,$result,$cache_timeout);
		return $result;
//		return get_multiple("contract",$search,"contract_id","fuzzy","name");

	}


    private static function _contract_cache_key($contract_id,$args=array()){
        return 'contract_'.$contract_id.'_'.md5(module_security::get_loggedin_id().'_'.serialize($args));
    }

	public static function get_contract($contract_id,$full=true,$skip_permissions=false){

        $contract_id = (int)$contract_id;
        if($contract_id<=0){
            $contract=array();
        }else{

            $cache_key = self::_contract_cache_key($contract_id, array($contract_id, $full, $skip_permissions));
            if($cached_item = module_cache::get('contract',$cache_key)){
	            if(function_exists('hook_filter_var')){
					$cached_item = hook_filter_var('get_contract',$cached_item,$contract_id);
				}
                return $cached_item;
            }
            $cache_key_full = self::_contract_cache_key($contract_id, array($contract_id, true, $skip_permissions));
            if($cache_key_full != $cache_key && $cached_item = module_cache::get('contract',$cache_key_full)){
	            if(function_exists('hook_filter_var')){
					$cached_item = hook_filter_var('get_contract',$cached_item,$contract_id);
				}
                return $cached_item;
            }
            $cache_timeout = module_config::c('cache_objects',60);


            $contract = get_single("contract","contract_id",$contract_id);
        }
        // check permissions
        if($contract && isset($contract['contract_id']) && $contract['contract_id']==$contract_id){
            switch(self::get_contract_access_permissions()){
                case _CONTRACT_ACCESS_ALL:

                    break;
                case _CONTRACT_ACCESS_CUSTOMER:
                    // tie in with customer permissions to only get contracts from customers we can access.
                    $customers = module_customer::get_customers();
                    $has_contract_access = false;
                    if(isset($customers[$contract['customer_id']])){
                        $has_contract_access = true;
                    }
                    /*foreach($customers as $customer){
                        // todo, if($contract['customer_id'] == 0) // ignore this permission
                        if($customer['customer_id']==$contract['customer_id']){
                            $has_contract_access = true;
                            break;
                        }
                    }*/
                    unset($customers);
                    if(!$has_contract_access){
                        if($skip_permissions){
                            $contract['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
                        }else{
                            $contract = false;
                        }
                    }
                    break;
            }
            if(!$contract){
	            $contract = array();
	            if(function_exists('hook_filter_var')){
					$contract = hook_filter_var('get_contract',$contract,$contract_id);
				}
	            return $contract;
            }
        }
        if(!$full){
            if(isset($cache_key)){
                module_cache::put('contract',$cache_key,$contract,$cache_timeout);
            }
            if(function_exists('hook_filter_var')){
				$contract = hook_filter_var('get_contract',$contract,$contract_id);
			}
            return $contract;
        }
        if(!$contract){
            $customer_id = 0;
            if(isset($_REQUEST['customer_id']) && $_REQUEST['customer_id']){
                //
                $customer_id = (int)$_REQUEST['customer_id'];
                // find default website id to use.
                if(isset($_REQUEST['website_id'])){
                    $website_id = (int)$_REQUEST['website_id'];
                }else{

                }
            }

            $ucmcontract = new UCMContract();
	        $ucmcontract->customer_id = $customer_id;
	        $default_contract_name = $ucmcontract->get_new_document_number();

            $contract = array(
                'contract_id' => 'new',
                'customer_id' => $customer_id,
                'website_id' => (isset($_REQUEST['website_id'])? $_REQUEST['website_id'] : 0),
                'name' => $default_contract_name,
                'date_create' => date('Y-m-d'),
                'date_terminate' => '0000-00-00',
                'date_approved' => '0000-00-00',
                'approved_by' => '',
                'user_id' => module_security::get_loggedin_id(),
                'contact_user_id' => -1, // default primary contact
                'type'  => module_config::s('contract_type_default','Hourly Rate'),
                'currency_id' => module_config::c('default_currency_id',1),
                'contract_text' => '',
            );


        }

        if($contract){
            // work out total hours etc..
            $contract['total_hours'] = 0;
            $contract['total_hours_completed'] = 0;
            $contract['total_hours_overworked'] = 0;
            $contract['total_sub_amount'] = 0;
            $contract['total_sub_amount_taxable'] = 0;
            $contract['total_sub_amount_unbillable'] = 0;
            $contract['total_sub_amount_invoicable'] = 0;
            $contract['total_sub_amount_invoicable_taxable'] = 0;
            $contract['total_amount_invoicable'] = 0;

            $contract['total_amount'] = 0;
            $contract['total_amount_paid'] = 0;
            $contract['total_amount_invoiced'] = 0;
            $contract['total_amount_invoiced_deposit'] = 0;
            $contract['total_amount_todo'] = 0;
            $contract['total_amount_outstanding'] = 0;
            $contract['total_amount_due'] = 0;
            $contract['total_hours_remain'] = 0;
            $contract['total_percent_complete'] = 0;

            $contract['total_tax'] = 0;
            $contract['total_tax_invoicable'] = 0;

//            $contract['invoice_discount_amount'] = 0;
//            $contract['invoice_discount_amount_on_tax'] = 0;
//            $contract['total_amount_discounted'] = 0;


        }
        if(isset($cache_key)){
            module_cache::put('contract',$cache_key,$contract,$cache_timeout);
        }
		if(function_exists('hook_filter_var')){
			$contract = hook_filter_var('get_contract',$contract,$contract_id);
		}
		return $contract;
	}
	public static function duplicate_contract($contract_id){
		$new_contract_id = false;
		$contract_data = self::get_contract($contract_id,true);
		// duplicate data from contract, contract_tax and contract_task tables
		unset($contract_data['contract_id']);
		unset($contract_data['date_approved']);
		unset($contract_data['approved_by']);
		$contract_data['name'] = '(dup) '.$contract_data['name'];
		$new_contract_id = update_insert('contract_id',false,'contract',$contract_data);


		return $new_contract_id;
	}
	public static function save_contract($contract_id,$data){


        if(isset($data['customer_id']) && $data['customer_id']>0){
            // check we have access to this customer from this contract.
            $customer_check = module_customer::get_customer($data['customer_id']);
            if(!$customer_check || $customer_check['customer_id'] != $data['customer_id']){
                unset($data['customer_id']);
            }
        }
        if(isset($data['website_id']) && $data['website_id']){
            $website = module_website::get_website($data['website_id']);
            if($website && (int)$website['website_id'] > 0 && $website['website_id']==$data['website_id']){
                // website exists.
                // make this one match the website customer_id, or set teh website customer_id if it doesn't have any.
                if((int)$website['customer_id']>0){
                    if($data['customer_id']>0 && $data['customer_id'] != $website['customer_id']){
                        set_message('Changed this Contract to match the Website customer');
                    }
                    $data['customer_id']=$website['customer_id'];
                }else if(isset($data['customer_id']) && $data['customer_id'] >0){
                    // set the website customer id to this as well.
                    update_insert('website_id',$website['website_id'],'website',array('customer_id'=>$data['customer_id']));
                }
            }
        }

        if((int)$contract_id>0){
            $original_contract_data = self::get_contract($contract_id,false);
            if(!$original_contract_data || $original_contract_data['contract_id']!=$contract_id){
                $original_contract_data = array();
                $contract_id = false;
            }
        }else{
            $original_contract_data = array();
            $contract_id = false;
        }

        // check create permissions.
        if(!$contract_id && !self::can_i('create','Contracts')){
            // user not allowed to create contracts.
            set_error('Unable to create new Contracts');
            redirect_browser(self::link_open(false));
        }

		$contract_id = update_insert("contract_id",$contract_id,"contract",$data);
        if($contract_id){
	        hook_handle_callback('contract_save',$contract_id);


	        $UCMContractProducts = new UCMContractProducts();
	        $contract_products = $UCMContractProducts->get( array( 'contract_id' => $contract_id ) );
	        foreach($contract_products as $contract_product){
	            $UCMContractProduct = new UCMContractProduct();
		        $UCMContractProduct->load( array($contract_product['contract_id'], $contract_product['product_id']));
		        $UCMContractProduct->delete();
            }
	        if(isset($data['assign_product']) && is_array($data['assign_product'])){
		        foreach($data['assign_product'] as $product_id => $tf){
			        if($tf){
				        $UCMContractProduct = new UCMContractProduct();
				        $UCMContractProduct->create_new(array(
                            'contract_id' => $contract_id,
                            'product_id' => $product_id,
                        ));
			        }
		        }
	        }

            if($original_contract_data){

	            // check if the contract was approved.
	            if(!isset($original_contract_data['date_approved']) || !$original_contract_data['date_approved'] || $original_contract_data['date_approved'] == '0000-00-00'){
		            // original contract wasn't approved.
		            if(isset($data['date_approved']) && !empty($data['date_approved']) && $data['date_approved'] != '0000-00-00'){
			            // contract was approved!
			            self::contract_approved($contract_id);
		            }
	            }
            }

        }
        if(class_exists('module_extra',false) && module_extra::is_plugin_enabled()){
            module_extra::save_extras('contract','contract_id',$contract_id);
        }
        module_cache::clear('contract');
		return $contract_id;
	}

	public static function contract_approved($contract_id){
        module_cache::clear('contract');
		$contract_data = module_contract::get_contract($contract_id);
		hook_handle_callback('contract_approved',$contract_id);
        self::add_history($contract_id,'Contract approved by '.$contract_data['approved_by']);
		if(module_config::c('contract_approval_auto_email',1) && $contract_data['user_id']){
			// send an email to the assigned staff member letting them know the contract was approved.
			$template = module_template::get_template_by_key('contract_approved_email');
	        $replace = module_contract::get_replace_fields($contract_id,$contract_data);

		    if(defined('_BLOCK_EMAILS') && _BLOCK_EMAILS){
			    $pdf = false;
		    }else{
			    $pdf = module_contract::generate_pdf($contract_id);
		    }

	        $template->assign_values($replace);
	        $html = $template->render('html');
	        // send an email to this user.
	        $email = module_email::new_email();
	        $email->replace_values = $replace;
			$email->set_to('user',$contract_data['user_id']);
	        $email->set_bcc_manual(module_config::c('admin_email_address',''),'');
	        //$email->set_from('user',); // nfi
	        $email->set_subject($template->description);
	        // do we send images inline?
	        $email->set_html($html);
		    if($pdf){
			    $email->add_attachment($pdf);
		    }
	        $email->contract_id = $contract_id;
	        $email->customer_id = $contract_data['customer_id'];
		    $email->prevent_duplicates = true;
	        if($email->send()){
	            // it worked successfully!!
	            // record a log on the contract when it's done.
	            self::add_history($contract_id,_l('Contract approval emailed to staff member'));
	        }else{
	            /// log err?
	        }
		}
        module_cache::clear('contract');
	}

    public static function email_sent($contract_id,$template_name){
        // add sent date if it doesn't exist
        self::add_history($contract_id,_l('Contract emailed to customer successfully'));
    }
    public static function staff_email_sent($options){
        $contract_id = (int)$options['contract_id'];
        // add sent date if it doesn't exist
        self::add_history($contract_id,_l('Contract emailed to staff successfully'));
    }

    public static function add_history($contract_id,$message){
        module_note::save_note(array(
            'owner_table' => 'contract',
            'owner_id' => $contract_id,
            'note' => $message,
            'rel_data' => self::link_open($contract_id),
            'note_time' => time(),
        ));
    }


	public static function delete_contract($contract_id){
		$contract_id=(int)$contract_id;
		if(_DEMO_MODE && $contract_id == 1){
			return;
		}

        if((int)$contract_id>0){
            $original_contract_data = self::get_contract($contract_id);
            if(!$original_contract_data || $original_contract_data['contract_id'] != $contract_id){
                return false;
            }
        }else{
            return false;
        }

        if(!self::can_i('delete','Contracts')){
            return false;
        }

		$sql = "DELETE FROM "._DB_PREFIX."contract WHERE contract_id = '".$contract_id."' LIMIT 1";
		$res = query($sql);
        if(class_exists('module_file',false)){
            $sql = "UPDATE "._DB_PREFIX."file SET contract_id = 0 WHERE contract_id = '".$contract_id."'";
            query($sql);
        }

        if(class_exists('module_group',false)){
            module_group::delete_member($contract_id,'contract');
        }
        if(class_exists('module_note',false) && module_note::is_plugin_enabled()){
		    module_note::note_delete("contract",$contract_id);
        }
        if(class_exists('module_extra',false) && module_extra::is_plugin_enabled()){
            module_extra::delete_extras('contract','contract_id',$contract_id);
        }

        hook_handle_callback('contract_delete',$contract_id);
        module_cache::clear('contract');
	}
    public function login_link($contract_id){
        return module_security::generate_auto_login_link($contract_id);
    }


    public static function get_types(){
        $sql = "SELECT `type` FROM `"._DB_PREFIX."contract` GROUP BY `type` ORDER BY `type`";
        $statuses = module_job::get_types();
        foreach(qa($sql) as $r){
            $statuses[$r['type']] = $r['type'];
        }
        return $statuses;
    }



    public static function customer_id_changed($old_customer_id, $new_customer_id) {
        $old_customer_id = (int)$old_customer_id;
        $new_customer_id = (int)$new_customer_id;
        if($old_customer_id>0 && $new_customer_id>0){
            $sql = "UPDATE `"._DB_PREFIX."contract` SET customer_id = ".$new_customer_id." WHERE customer_id = ".$old_customer_id;
            query($sql);
            module_invoice::customer_id_changed($old_customer_id,$new_customer_id);
            module_file::customer_id_changed($old_customer_id,$new_customer_id);
        }
    }




    public static function get_contract_access_permissions() {
        if (class_exists('module_security',false)){
            return module_security::can_user_with_options(module_security::get_loggedin_id(),'Contract Data Access',array(
                _CONTRACT_ACCESS_ALL,
                _CONTRACT_ACCESS_CUSTOMER,
            ));
        }else{
            return _CONTRACT_ACCESS_ALL; // default to all permissions.
        }
    }


    public static function handle_import_row_debug($row, $add_to_group, $extra_options){
        return self::handle_import_row($row,true,$add_to_group,$extra_options);
    }

    /* Contract Title	Hourly Rate	Start Date	Due Date	Completed Date	Website Name	Customer Name	Type	Status	Staff Member	Tax Name	Tax Percent	Renewal Date */
    public static function handle_import_row($row, $debug, $add_to_group, $extra_options){

        $debug_string = '';

        if(isset($row['contract_id']) && (int)$row['contract_id']>0){
            // check if this ID exists.
            $contract = self::get_contract($row['contract_id']);
            if(!$contract || $contract['contract_id'] != $row['contract_id']){
                $row['contract_id'] = 0;
            }
        }
        if(!isset($row['contract_id']) || !$row['contract_id']){
            $row['contract_id'] = 0;
        }
        if(!isset($row['name']) || !strlen($row['name'])) {
            $debug_string .= _l('No contract data to import');
            if($debug){
                echo $debug_string;
            }
            return false;
        }
        // duplicates.
        //print_r($extra_options);exit;
        if(isset($extra_options['duplicates']) && $extra_options['duplicates'] == 'ignore' && (int)$row['contract_id']>0){
            if($debug){
                $debug_string .= _l('Skipping import, duplicate of contract %s',self::link_open($row['contract_id'],true));
                echo $debug_string;
            }
            // don't import duplicates
            return false;
        }
        $row['customer_id'] = 0; // todo - support importing of this id? nah
        if(isset($row['customer_name']) && strlen(trim($row['customer_name']))>0){
            // check if this customer exists.
            $customer = get_single('customer','customer_name',$row['customer_name']);
            if($customer && $customer['customer_id'] > 0){
                $row['customer_id'] = $customer['customer_id'];
                $debug_string .= _l('Linked to customer %s',module_customer::link_open($row['customer_id'],true)) .' ';
            }else{
                $debug_string .= _l('Create new customer: %s',htmlspecialchars($row['customer_name'])) .' ';
            }
        }else{
            $debug_string .= _l('No customer').' ';
        }
        if($row['contract_id']){
            $debug_string .= _l('Replace existing contract: %s',self::link_open($row['contract_id'],true)).' ';
        }else{
            $debug_string .= _l('Insert new contract: %s',htmlspecialchars($row['name'])).' ';
        }

        if($debug){
            echo $debug_string;
            return true;
        }
        if(isset($extra_options['duplicates']) && $extra_options['duplicates'] == 'ignore' && $row['customer_id'] > 0){
            // don't update customer record with new one.

        }else if((isset($row['customer_name']) && strlen(trim($row['customer_name']))>0) || $row['customer_id']>0){
            // update customer record with new one.
            $row['customer_id'] = update_insert('customer_id',$row['customer_id'],'customer',$row);

        }
        $contract_id = (int)$row['contract_id'];
        // check if this ID exists.
        $contract = self::get_contract($contract_id);
        if(!$contract || $contract['contract_id'] != $contract_id){
            $contract_id = 0;
        }
        $contract_id = update_insert("contract_id",$contract_id,"contract",$row);

        // handle any extra fields.
        $extra = array();
        foreach($row as $key=>$val){
            if(!strlen(trim($val)))continue;
            if(strpos($key,'extra:')!==false){
                $extra_key = str_replace('extra:','',$key);
                if(strlen($extra_key)){
                    $extra[$extra_key] = $val;
                }
            }
        }
        if($extra){
            foreach($extra as $extra_key => $extra_val){
                // does this one exist?
                $existing_extra = module_extra::get_extras(array('owner_table'=>'contract','owner_id'=>$contract_id,'extra_key'=>$extra_key));
                $extra_id = false;
                foreach($existing_extra as $key=>$val){
                    if($val['extra_key']==$extra_key){
                        $extra_id = $val['extra_id'];
                    }
                }
                $extra_db = array(
                    'extra_key' => $extra_key,
                    'extra' => $extra_val,
                    'owner_table' => 'contract',
                    'owner_id' => $contract_id,
                );
                $extra_id = (int)$extra_id;
                update_insert('extra_id',$extra_id,'extra',$extra_db);
            }
        }

        foreach($add_to_group as $group_id => $tf){
            module_group::add_to_group($group_id,$contract_id,'contract');
        }

        return $contract_id;

    }

    public static function handle_import($data,$add_to_group,$extra_options){

        // woo! we're doing an import.
        $count = 0;
        // first we find any matching existing contracts. skipping duplicates if option is set.
        foreach($data as $rowid => $row){
            if(self::handle_import_row($row, false, $add_to_group, $extra_options)){
                $count++;
            }
        }
        return $count;


    }



    /**
     * Generate a PDF for the currently load()'d contract
     * Return the path to the file name for this contract.
     * @return bool
     */

    public static function generate_pdf($contract_id){

        if(!function_exists('convert_html2pdf'))return false;

        $contract_id = (int)$contract_id;
        $contract_data = self::get_contract($contract_id);
        $contract_html = self::contract_html($contract_id,$contract_data,'pdf');
        if($contract_html){
            //echo $contract_html;exit;

            $base_name = basename(preg_replace('#[^a-zA-Z0-9_]#','',module_config::c('contract_file_prefix','Contract_')));
            $file_name = preg_replace('#[^a-zA-Z0-9]#','',$contract_data['name']);
            $html_file_name = _UCM_FILE_STORAGE_DIR . 'temp/'.$base_name.$file_name.'.html';
            $pdf_file_name = _UCM_FILE_STORAGE_DIR . 'temp/'.$base_name.$file_name.'.pdf';

            file_put_contents($html_file_name,$contract_html);

            return convert_html2pdf($html_file_name,$pdf_file_name);


        }
        return false;
    }

    public static function contract_html($contract_id,$contract_data,$mode='html'){

        if($contract_id && $contract_data){
            // spit out the contract html into a file, then pass it to the pdf converter
            // to convert it into a PDF.

            $contract = $contract_data;

            if(class_exists('module_company',false) && isset($contract_data['company_id']) && (int)$contract_data['company_id']>0){
                module_company::set_current_company_id($contract_data['company_id']);
            }

	        $contract_template = isset($contract_data['contract_template_print']) && strlen($contract_data['contract_template_print']) ? $contract_data['contract_template_print'] : module_config::c('contract_template_print_default','contract_print');

            if($contract_template == 'contract_pdf'){
	            $contract_template = 'contract_print';
            }



            $replace = self::get_replace_fields($contract_id,$contract_data);
            $replace['task_list'] = '';
            $replace['contract_link'] = module_contract::link_public($contract_id);

            $replace['external_contract_template_html'] = '';
            $external_contract_template = module_template::get_template_by_key($contract_template);
            $external_contract_template->assign_values($replace);
            $replace['external_contract_template_html'] = $external_contract_template->replace_content();

            ob_start();
            $template = module_template::get_template_by_key($contract_template);
            $template->assign_values($replace);
            echo $template->render('html');
            $contract_html = ob_get_clean();
            return $contract_html;
        }
        return false;
    }

	public function autocomplete( $search_string = '', $search_options = array() ){
		$result = array();

		if(module_contract::can_i('view','Contracts')) {
			$customer_id = !empty($search_options['vars']['lookup_customer_id']) ? (int)$search_options['vars']['lookup_customer_id'] : false;

			$res     = module_contract::get_contracts( array(
				'generic' => $search_string,
                'customer_id' => $customer_id,
			) , array('columns'=>'u.contract_id, u.name') );
			foreach ( $res as $row ) {
				$result[] = array(
					'key' => $row['contract_id'],
					'value' => $row['name']
				);
			}
		}

		return $result;
	}
	

	public static function hook_filter_custom_data_menu_locations($call, $menu_locations){
		$menu_locations[_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_FOOTER] = _l('Contract Footer');
		$menu_locations[_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_SIDEBAR] = _l('Contract Sidebar');
		return $menu_locations;
	}


	public static function customer_archived($hook,$customer_id){
		$customer_id = (int)$customer_id;
		if($customer_id > 0) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'contract` SET `archived` = 1 WHERE `customer_id` = ' . $customer_id;
			query($sql);
		}
	}
	public static function customer_unarchived($hook,$customer_id){
		$customer_id = (int)$customer_id;
		if($customer_id > 0) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'contract` SET `archived` = 0 WHERE `customer_id` = ' . $customer_id;
			query($sql);
		}
	}

	public function get_upgrade_sql(){
        $sql = '';

        return $sql;
    }

    public function get_install_sql(){
        ob_start();
        ?>

    CREATE TABLE `<?php echo _DB_PREFIX; ?>contract` (
    `contract_id` int(11) NOT NULL auto_increment,
    `customer_id` INT(11) NULL,
    `website_id` INT(11) NULL,
    `name` varchar(255) NOT NULL DEFAULT  '',
    `type` varchar(255) NOT NULL DEFAULT  '',
    `date_create` date NOT NULL,
    `date_terminate` date NOT NULL,
    `date_approved` date NOT NULL,
    `approved_by` varchar(255) NOT NULL DEFAULT '',
    `user_id` INT NOT NULL DEFAULT  '0',
    `contact_user_id` INT NOT NULL DEFAULT  '-1',
    `currency_id` INT NOT NULL DEFAULT  '1',
    `contract_text` LONGTEXT NOT NULL DEFAULT  '',
    `archived` tinyint(1) NOT NULL DEFAULT '0',
        `contract_template_print` varchar(50) NOT NULL DEFAULT '',
        `contract_template_email` varchar(50) NOT NULL DEFAULT '',
        `contract_template_external` varchar(50) NOT NULL DEFAULT '',
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY  (`contract_id`),
        KEY `customer_id` (`customer_id`),
        KEY `user_id` (`user_id`),
        KEY `archived` (`archived`),
        KEY `website_id` (`website_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


    CREATE TABLE `<?php echo _DB_PREFIX; ?>contract_product` (
    `contract_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    PRIMARY KEY  (`contract_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


    <?php

        return ob_get_clean();
    }

}



include_once 'class.contract.php';