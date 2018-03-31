<?php

define('_CHANGE_REQUEST_STATUS_NEW',1);
define('_CHANGE_REQUEST_STATUS_COMPLETE',2);
define('_CHANGE_REQUEST_STATUS_DELETE',3);

class module_change_request extends module_base{

	public $links;
	public $change_request_types;
    public $change_request_id;

    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
	public static function get_class() {
        return __CLASS__;
    }
    public function init(){
		$this->links = array();
		$this->change_request_types = array();
		$this->module_name = "change_request";
		$this->module_position = 30;
        $this->version = 2.297;
        // 2.297 - 2016-07-10 - big update to mysqli
        // 2.296 - 2015-06-14 - fix for newer fancybox version
        // 2.295 - 2015-04-03 - fixed translation
        // 2.294 - 2014-08-14 - better email error messages
        // 2.293 - 2014-08-01 - responsive improvements
        // 2.292 - 2014-06-09 - permission and job assignment fix
        // 2.291 - 2014-04-09 - js fix for url
        // 2.29 - 2014-01-01 - css fix for dark background websites
        // 2.289 - 2013-12-05 - fix for mod_security
        // 2.288 - 2013-08-30 - dashboard speed improvement
        // 2.287 - 2013-08-07 - new console code option
        // 2.286 - 2013-07-29 - new _UCM_SECRET hash in config.php
        // 2.285 - 2013-04-20 - permission fix


        // 2.1 - initial release
        // 2.21 - bug fix in javascript code
        // 2.22 - email/dashboard alerts and other fixes
        // 2.23 - redirecting bug fixes (please clear browser cache)
        // 2.24 - bug fix for multi-line change requests
        // 2.25 - big fix showing all changes on home page.
        // 2.26 - bug fix for wordpress themes and jquery
        // 2.27 - support for the new jQuery 1.9+
        // 2.28 - hide/show completed change requests
        // 2.281 - possible bug fix for some hosting accounts
        // 2.282 - delete change request button
        // 2.283 - bug fix in change request error logging
        // 2.284 - 2013-04-04 - better support for change request on HTTPS websites


        //module_config::register_css('change_request','change_request.css');
        if(module_config::c('change_request_enabled',1)){

            hook_add('website_sidebar','module_change_request::hook_website_sidebar');
            hook_add('website_main','module_change_request::hook_website_main');

            hook_add('website_save','module_change_request::website_form_save');
            hook_add('job_delete','module_change_request::hook_job_delete');
            if(class_exists('module_template',false)){
                module_template::init_template('change_request_alert_email','Dear {TO_NAME},<br>
<br>
A change request has been submitted by {NAME} for the website {WEBSITE_LINK}.<br><br>
Change request details:<br><br>
{REQUEST}<br/>
','Change Request: {WEBSITE_NAME}',array(
            'to_name' => 'Recipient name',
            'from_name' => 'Uploader name',
            'website_name'=>'Name of website',
            'website_link'=>'Link to website',
            'name'=>'Change request name',
            'request'=>'Change request text',
                ));
            }

        }
	}

    public function pre_menu(){

		/*if($this->can_i('view','Change Requests') && module_config::can_i('view','Settings')){


            // how many change_requests are there?
            $link_name = _l('Change Requests');

			$this->links['change_requests'] = array(
				"name"=>$link_name,
				"p"=>"change_request_admin",
				"args"=>array('change_request_id'=>false),
                'holder_module' => 'config', // which parent module this link will sit under.
                'holder_module_page' => 'config_admin',  // which page this link will be automatically added to.
                'menu_include_parent' => 0,
			);
		}*/

    }

    /** static stuff */

    
     public static function link_generate($change_request_id=false,$options=array(),$link_options=array()){
        // we accept link options from a bubbled link call.
        // so we have to prepent our options to the start of the link_options array incase
        // anything bubbled up to this method.
        // build our options into the $options variable and array_unshift this onto the link_options at the end.
        $key = 'change_request_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

        // we check if we're bubbling from a sub link, and find the item id from a sub link
        if(${$key} === false && $link_options){
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
        // grab the data for this particular link, so that any parent bubbled link_generate() methods
        // can access data from a sub item (eg: an id)

        if(isset($options['full']) && $options['full']){
            // only hit database if we need to print a full link with the name in it.
            if(!isset($options['data']) || !$options['data']){
                if((int)$change_request_id>0){
                    $data = self::get_change_request($change_request_id);
                }else{
                    $data = array();
                    return _l('N/A');
                }
                $options['data'] = $data;
            }else{
                $data = $options['data'];
            }
            // what text should we display in this link?
            $options['text'] = $data['name'];
        }
        $options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
        // generate the arguments for this link
        $options['arguments'] = array(
            'change_request_id' => $change_request_id,
        );
        // generate the path (module & page) for this link
        if(!isset($options['page']))$options['page'] = 'change_request_email';
        $options['module'] = 'change_request';

        // append this to our link options array, which is eventually passed to the
        // global link generate function which takes all these arguments and builds a link out of them.

         if(!self::can_i('view','Change Requests')){
            if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : _l('N/A');
            }
        }

        // optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
        // change this variable to the one we are going to bubble up to:
        $bubble_to_module = false;
        $bubble_to_module2 = array(
            'module' => 'customer',
            'argument' => 'change_request_id',
        );
        array_unshift($link_options,$options);
        if($bubble_to_module){
            global $plugins;
            return $plugins[$bubble_to_module['module']]->link_generate(false,array(),$link_options);
        }else{
            // return the link as-is, no more bubbling or anything.
            // pass this off to the global link_generate() function
            return link_generate($link_options);
        }
    }


	public static function link_open($change_request_id,$full=false,$data=array()){
		return self::link_generate($change_request_id,array('full'=>$full,'data'=>$data));
	}
	public static function link_open_delete($change_request_id,$full=false,$data=array()){
		return self::link_generate($change_request_id,array('full'=>$full,'data'=>$data,'page'=>'change_request_delete'));
	}

    public function handle_hook($hook,&$calling_module=false){
		switch($hook){
			case "home_alerts":



				$alerts = array();
                if(module_config::c('change_request_alerts',1) && class_exists('module_website',false)){
                    $cache_key = "home_alerts_".module_security::get_loggedin_id();
                    $cache_timeout = module_config::c('cache_objects',60);
                    if($alerts = module_cache::get('change_request',$cache_key)){
                         return $alerts;
                     }
                    // find any open change requests for all customers.
                    $websites = module_website::get_websites(array(), array('columns' => 'u.website_id')); // this gets websites we have permission to view.
                    if(count($websites)>0){
                        $website_ids = array();
                        foreach($websites as $website){
                            $website_ids[] = $website['website_id'];
                        }
                        // build a query to find all new change requests for websitse we have access to
                        $sql = "SELECT * FROM `"._DB_PREFIX."change_request` cr WHERE `website_id` IN (".implode(', ',$website_ids).") AND `status` = "._CHANGE_REQUEST_STATUS_NEW;
                        $website_requests = qa($sql);
                        foreach($website_requests as $website_request){
                            $alert_res = process_alert($website_request['date_created'], _l('Change Request'));
                            if($alert_res){
                                $alert_res['link'] = module_website::link_open($website_request['website_id'],false);
                                $alert_res['name'] = $website_request['url'];
                                $alerts[] = $alert_res;
                            }
                        }
                        /*$website_requests = self::get_change_requests(array(
                            'website_id'=>$website['website_id'],
                            'status'=>_CHANGE_REQUEST_STATUS_NEW,
                        ));
                        foreach($website_requests as $website_request){

                            $alert_res = process_alert($website_request['date_created'], _l('Change Request'));
                            if($alert_res){
                                $alert_res['link'] = module_website::link_open($website['website_id'],false);
                                $alert_res['name'] = $website_request['url'];
                                $alerts[] = $alert_res;
                            }

                        }*/
                    }
                    module_cache::put('change_request',$cache_key,$alerts,$cache_timeout);
				}
                return $alerts;
        }
    }


	public static function get_change_requests($search=array()){

		return get_multiple("change_request",$search,"change_request_id","exact","name");
	}


	public static function get_change_request($change_request_id){
        return get_single('change_request','change_request_id',$change_request_id);
	}
	public static function get_change_request_by_website($website_id,$change_request_id){
        $cr = get_single('change_request',array('website_id','change_request_id'),array($website_id,$change_request_id));
        $cr['attachments']=array();
        return $cr;
	}


    
	public function process(){
		/*if(isset($_REQUEST['butt_del']) && $_REQUEST['butt_del'] && $_REQUEST['change_request_id']){
			$data = self::get_change_request($_REQUEST['change_request_id']);
            if(module_form::confirm_delete('change_request_id',"Really delete change request: ".$data['name'],self::link_open($_REQUEST['change_request_id']))){
                $this->delete_change_request($_REQUEST['change_request_id']);
                set_message("Change request deleted successfully");
                redirect_browser(module_website::link_open($data['website_id']));
            }
		}else */
        if("save_change_request" == $_REQUEST['_process']){
			$change_request_id = $this->save_change_request($_REQUEST['change_request_id'],$_POST);
			set_message("Change_request saved successfully");
			redirect_browser(self::link_open($change_request_id));
		}
	}


	public function save_change_request($change_request_id,$data){
		$change_request_id = update_insert("change_request_id",$change_request_id,"change_request",$data);
		return $change_request_id;
	}


    public static function hook_job_delete($callback_name, $job_id){
        // remove this job from these change requests.
        // should really remove the change request as well, oh well.
        $sql = "UPDATE `"._DB_PREFIX."change_request` SET job_id = 0, task_id = 0 WHERE job_id = ".(int)$job_id;
        query($sql);
    }
    public static function website_form_save($callback_name, $website_id){
        if(isset($_POST['change_request']) && is_array($_POST['change_request'])){
            // save this against this website.
            $data = $_POST['change_request'];
            $data['website_id']=$website_id;
            update_insert('website_id',$website_id,'change_request_website',$data,true);
            // are we clicking the send button?
            if(isset($_POST['change_request']['sendemail']) && $_POST['change_request']['sendemail']){
                // redirect to send email page.

            }
        }
        if(isset($_POST['add_change_request_to_website']) && (int)$_POST['add_change_request_to_website']>0 &&
            isset($_POST['add_change_request_to_website_job_id']) && (int)$_POST['add_change_request_to_website_job_id']>0){
            $change_requests = self::get_change_request_by_website($website_id,$_POST['add_change_request_to_website']);
            if($change_requests && $change_requests['change_request_id'] == $_POST['add_change_request_to_website']){
                $task_id = update_insert('task_id',0,'task',array(
                    'job_id'=>$_POST['add_change_request_to_website_job_id'],
                    'description'=>_l('Change Request: %s',$change_requests['request']),
                    'hours'=>isset($_POST['add_change_request_to_website_hours']) ? $_POST['add_change_request_to_website_hours'] : module_config::c('change_request_job_hours',1),
                ));
                if($task_id){
                    update_insert('change_request_id',$change_requests['change_request_id'],'change_request',array(
                        'job_id'=>$_POST['add_change_request_to_website_job_id'],
                        'task_id'=>$task_id,
                    ));
                }
            }
        }
    }


	public static function delete_change_request($change_request_id){
		$change_request_id=(int)$change_request_id;
        $change_request = self::get_change_request($change_request_id);
        if($change_request && $change_request['change_request_id'] == $change_request_id){
            $sql = "DELETE FROM "._DB_PREFIX."change_request WHERE change_request_id = '".$change_request_id."' LIMIT 1";
            query($sql);
        }
	}


    public static function hook_website_sidebar($callback_name, $website_id){
        if(module_config::c('change_request_enabled',1) && (int)$website_id>0 && self::can_i('edit','Change Requests')){
            // check if this website is linked to any change_request payments.
            $change_request_website = get_single('change_request_website','website_id',$website_id);
            include('hooks/website_sidebar.php');
        }
    }
    public static function hook_website_main($callback_name, $website_id){
        if((int)$website_id>0 && self::can_i('view','Change Requests')){
            // check if this website is linked to any change_request payments.
            $change_requests = self::get_change_requests(array('website_id'=>$website_id,'status'=>'1')) + self::get_change_requests(array('website_id'=>$website_id,'status'=>'2'));
            $change_request_website = get_single('change_request_website','website_id',$website_id);
            if(($change_request_website && $change_request_website['enabled']) || count($change_requests)){
                include('hooks/website_main.php');
            }
        }
    }

    public static function link_public($website_id,$h=false){
        if($h){
            return md5('s3cret7hash for change requests '._UCM_SECRET.' '.$website_id);
        }
        // we have to redirect to the customers website after setting a cookie on our domain.
        return full_link(_EXTERNAL_TUNNEL_REWRITE.'m.change_request/h.public/i.'.$website_id.'/hash.'.self::link_public($website_id,true));
    }
    public static function link_public_change($website_id,$change_request_id){
        $link = self::link_public($website_id);
        $link .= strpos($link,'?') ? '&' : '?';
        $link .= "change_request_id=".$change_request_id;
        return $link;
    }
    public static function link_script($website_id,$h=false){
        if($h){
            return md5('s3cret7hash for change requests script! '._UCM_SECRET.' '.$website_id);
        }
        // we have to redirect to the customers website after setting a cookie on our domain.
        // HASH IS ADDED BY CLIENT.
        return rtrim(full_link(_EXTERNAL_TUNNEL_REWRITE.'m.change_request/h.script/i.'.$website_id.'/hash.'),'&'); //.self::link_script($website_id,true));
    }
    public static function link_popup($website_id,$h=false){
        if($h){
            return md5('s3cret7hash for change requests script! '._UCM_SECRET.' '.$website_id.' ');
        }
        // we have to redirect to the customers website after setting a cookie on our domain.
        // HASH IS ADDED BY CLIENT.
        return full_link(_EXTERNAL_TUNNEL.'?m=change_request&h=popup&i='.$website_id.'&hash='.self::link_popup($website_id,true));
    }

    public static function get_remaining_changes($website_id){
        $change_request_website = get_single('change_request_website','website_id',$website_id);
        $changes = array(0,isset($change_request_website['limit_number'])?$change_request_website['limit_number']:5);
        if(isset($change_request_website['limit_per'])){
            switch($change_request_website['limit_per']){
                case 1: // week
                    $period = 'week';
                    $start_time = date('Y-m-d H:i:s',strtotime('-7 days'));
                    break;
                case 2: // month
                    $period = 'month';
                    $start_time = date('Y-m-d H:i:s',strtotime('-1 month'));
                    break;
                case 3: // year
                    $period = 'year';
                    $start_time = date('Y-m-d H:i:s',strtotime('-1 year'));
                    break;
                default:
                    // all time.
                    $period = 'all time';
                    $start_time = 0;
            }
            $changes[2] = $period;
            $sql = " SELECT * FROM `"._DB_PREFIX."change_request` ";
            $sql .= " WHERE website_id = ".(int)$website_id;
            $sql .= " AND (`status` = 1 OR `status` = 2) ";
            if($start_time){
                $sql .= " AND `date_created` >= '".db_escape($start_time)."'";
            }
            $all = qa($sql);
            $changes[0] = count($all);
        }
        /*$all = $wpdb->get_results("SELECT * FROM $table_name
        WHERE (published = 1 OR published = 2) AND `user_id` = '$user_id' AND `time` > '$start_time'"); //completed or published.
        $changes[0] = count($all);*/
        return $changes;
    }

    public function external_hook($hook){
        switch($hook){

            case 'popup': // popup not used any more. cross domain issues.
                // load up the full script to be injected into our clients website.
                $website_id = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : false;
                $change_request_id = $change_id = isset($_REQUEST['change_id']) ? (int)$_REQUEST['change_id'] : false;
                $hash = isset($_REQUEST['hash']) ? $_REQUEST['hash']:false;
                $type = isset($_REQUEST['type']) ? $_REQUEST['type']:false;

                if($type=='popupjs'){
                    @ob_end_clean();
                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Cache-Control: no-cache");
                    header("Pragma: no-cache");
                    header("Content-type: text/javascript");
                }

                if($website_id  && $hash && module_change_request::link_popup($website_id,true) == $hash){


                    $change_history = module_change_request::get_remaining_changes($website_id);
                    $step = isset($_REQUEST['step']) ? (int)$_REQUEST['step']: 0;

                    // get the change details out
                    if($change_request_id){
                        $change_request = module_change_request::get_change_request_by_website($website_id,$change_request_id);
                    }else{
                        $change_request = array();
                    }
                    if(!$change_request){
                        $change_request = array(
                            'change_request_id' => 0,
                            'name'=>'',
                            'request'=>'',
                            'attachments'=>array(),
                        );
                    }

                    switch($type){
                        case 'save':
                            // saving a change.
                            $data = $_POST;
                            $data['url'] = urldecode($data['url']);
                            $data['website_id'] = $website_id;
                            $data['change_request_id'] = $change_request['change_request_id'];



                            if(isset($_REQUEST['completed_test'])){
                                if(!isset($_REQUEST['completed'])||!$_REQUEST['completed']){
                                    $data['status'] = _CHANGE_REQUEST_STATUS_NEW; // not completed.
                                }else{
                                    $data['status'] = _CHANGE_REQUEST_STATUS_COMPLETE; // completed!
                                }
                            }
                            if(isset($_REQUEST['delete_request'])){
                                $data['status'] = _CHANGE_REQUEST_STATUS_DELETE; // deleted
                            }
                            $change_request_id = update_insert('change_request_id',$change_request['change_request_id'],'change_request',$data);


                            // redirect to send email page if we're logged in
                            if(module_security::is_logged_in() && isset($_REQUEST['completed_send_email']) && $_REQUEST['completed_send_email'] && self::can_i('edit','Change Requests')){
                                // don't do the template, do the redirect to the email page (todo!)
                                redirect_browser(self::link_open($change_request_id));
                            }else{
                                // send email to administrator (everyone with change request edit permissions?) about this change request.
                                $alert_users = module_user::get_users_by_permission(
                                    array(
                                        'category' => 'Change Request',
                                        'name' =>  'Change Requests',
                                        'module' => 'change_request',
                                        'edit' => 1,
                                    )
                                );
	                            $email_data = get_single('change_request','change_request_id',$change_request_id);
                                $customer_data = $website_data = array();
                                if($website_id){
                                    $website_data = module_website::get_website($website_id);
                                    $email_data['website_name'] = $website_data['name'];
                                    $email_data['website_link'] = module_website::link_open($website_id,true);
                                    if($website_data && $website_data['customer_id']){
                                        $customer_data = module_customer::get_customer($website_data['customer_id'],true);
                                    }
                                }
	                            if(isset($email_data['request'])){
		                            $email_data['request'] = nl2br($email_data['request']);// for the plain text emails.
	                            }
                                foreach($alert_users as $alert_user){
                                    // todo: make sure this staff member has access to this website?
                                    // nfi how to figure this out. maybe we just look for staff members who are assigned jobs/tasks against this website?

                                    $template = module_template::get_template_by_key('change_request_alert_email');
                                    $template->assign_values(array_merge($customer_data,$website_data,$email_data));
                                    $html = $template->render('html');
                                    // send an email to this user.
                                    $email = module_email::new_email();
                                    $email->replace_values = array_merge($customer_data,$website_data,$email_data);
                                    $email->set_to('user',$alert_user['user_id']);
                                    $email->set_from('user',module_security::get_loggedin_id() ? module_security::get_loggedin_id() : (isset($customer_data['primary_user_id'])) ? $customer_data['primary_user_id'] : 0);
                                    $email->set_subject($template->description);
                                    // do we send images inline?
                                    $email->set_html($html);

                                    if($email->send()){
                                        // it worked successfully!!
                                        // sweet.
                                    }else{
                                        /// log err?
                                        set_error(_l('Failed to send change notification email to User ID: %s Email: %s Status: %s Error: %s',$alert_user['user_id'],json_encode($email->to),$email->status,$email->error_text));
                                    }
                                }
                            }

                            // display thankyou template.
                            module_template::init_template('change_request_submitted','<h2>Change Request</h2>
    <p>Thank you. Your change request has been submitted successfully.</p>
    <p>Please <a href="{URL}">click here</a> to continue.</p>
    ','Displayed after a change request is created/updated.','code');
                            // correct!


                            // load up the receipt template.
                            $template = module_template::get_template_by_key('change_request_submitted');

                            $template->page_title = _l("Change Request");

                            foreach($data as $key=>$val){
                                if(!is_array($val))$data[$key]=htmlspecialchars($val);
                            }
                            $template->assign_values($data);
                            echo $template->render('pretty_html');
                            exit;
                            break;
                        case 'display_change':
                            ob_start();
                            ?>
                            <div class="title">
                                <?php _e('Change request');?>
                            </div>
                            <div class="content">
                                <p><?php echo nl2br(htmlspecialchars($change_request['request']));?></p>
                                <div class="wp3changerequest_actions">
                                    <p>
                                       <!-- <strong><?php _e('Attachments:');?></strong>
                                        <?php if(!$change_request['attachments']){ ?> - none - <?php }else{
                                        foreach($change_request['attachments'] as $attachment){ ?>
                                            <a href="#"><?php
                                                echo htmlspecialchars($attachment->name);
                                                ?></a>
                                            <?php } ?>
                                        <?php } ?>
                                        <br/>-->
                                        <strong><?php _e('Created by:');?></strong> <?php echo htmlspecialchars($change_request['name']);?> <br/>
                                        <strong><?php _e('Created on:');?></strong> <?php echo print_date($change_request['date_created'],true);?>
	                                    <?php if(isset($change_request['job_id']) && $change_request['job_id']){ ?> <br/>
		                                    <strong><?php _e('Converted to job:');?></strong> <?php _e('This task has been converted to a Job');?>
	                                    <?php } ?>
                                    </p>
                                    <?php if(!isset($change_request['job_id']) || !$change_request['job_id'] || self::can_i('edit','Change Requests')){ ?>
                                    <p align="center">
                                        <input type="button" name="edit" value="<?php _e('Edit');?>" class="wp3changerequest_button wp3changerequest_button_small"  onclick="dtbaker_changerequest.edit(<?php echo $change_request_id;?>); return false;">
                                    </p>
	                                <?php } ?>
                                </div>
                            </div>
                            <?php
                            $change_request['html'] = preg_replace('/\s+/',' ',ob_get_clean());
//                                echo json_encode($change_request);
//                                exit;

                                @ob_end_clean();
                                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                                header("Cache-Control: no-cache");
                                header("Pragma: no-cache");
                                header("Content-type: text/javascript");
                                ?>

                            var t = dtbaker_changerequest;
                            var change_id = <?php echo $change_request_id;?>;
                                var msg = <?php echo json_encode($change_request);?>;

                            jQuery('body').prepend('<div class="wp3changerequest_change" id="dtbaker_change_'+change_id+'" style="'+((!t.show_postits) ? 'display:none;':'')+'"></div>');
                            var box = jQuery('#dtbaker_change_'+change_id);
                            box.html(msg.html);
                            if(msg.status == 0){
                                box.addClass('wp3changerequest_change_pending');
                            }else if(msg.status == 2){
                                box.addClass('wp3changerequest_change_complete');
                            }else if(msg.status == 3){
                                box.addClass('wp3changerequest_change_deleted');
                            }
                            box.css('top',msg.y+'px');
                            box.data('window_width',msg.window_width);
                            box.data('left',msg.x);
                            t.set_left(change_id);
                            with({i:change_id}){
                                jQuery(window).resize(function () {
                                    t.set_left(i);
                                });
                            }
                            box.data('original_height',box.height());
                            box.css('overflow','hidden');
                            jQuery('.title',box).slideUp();
                            box.stop(true, true).animate({
                                height: t.min_height,
                                width: t.min_width
                            },500);
                            box.hover(function(){
                                jQuery(this).addClass('wp3changerequest_change_active');
                                jQuery('.title',this).stop(true, true).slideDown();
                                jQuery(this).stop().animate({
                                    width: t.max_width,
                                    height: jQuery(this).data('original_height'),
                                    opacity: 1
                                },500);
                            },function(){
                                jQuery('.title',this).stop(true, true).slideUp();
                                jQuery(this).stop().animate({
                                    width: t.min_width,
                                    height: t.min_height,
                                    opacity: 0.7
                                },500,function(){
                                    jQuery(this).removeClass('wp3changerequest_change_active');
                                });
                            })


                                <?php
                            break;
                        default:
                            @ob_end_clean();
                            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                            header("Cache-Control: no-cache");
                            header("Pragma: no-cache");
                            header("Content-type: text/javascript");
                            ob_start();
                            include('pages/popup.php');
                            $html = ob_get_clean();
                            $html = addcslashes($html,"'");
                            $html = preg_replace('#\r|\n#',"' +\n'",$html);
                            // inject using javascript. fixes cross domain issues
                            ?>
                            if(!jQuery('#dtbaker_changerequest_inlinewizard').length){
                                // fix for jQuery 1.9+
                                jQuery('body').append('<div id="dtbaker_changerequest_inlinewizard" style="display:none;"></div>');
                            }
                            jQuery('#dtbaker_changerequest_inlinewizard').html('<?php echo $html;?>');
                            <?php
                    }
                }
                exit;
                break;
            case 'script':
                // load up the full script to be injected into our clients website.
                $website_id = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : false;
                $hash = isset($_REQUEST['hash']) ? $_REQUEST['hash']:false;
                @ob_end_clean();
                header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                header("Cache-Control: no-cache");
                header("Pragma: no-cache");
                header("Content-type: text/javascript");
                if($website_id && $hash && module_change_request::link_script($website_id,true) == $hash){
                    include("js/client.js");
                    $client_url = isset($_REQUEST['url']) ? $_REQUEST['url'] : false;
                    if($client_url){

                        $change_requests = self::get_change_requests(array('website_id'=>$website_id,'url'=>$client_url)); // todo - option this out incase url causes issues. ie: old js check method
                        ?>
                        jQuery(function(){
                            <?php foreach($change_requests as $change_request){
                            $displayed=false;
                                if($change_request['status']==_CHANGE_REQUEST_STATUS_NEW){
                                    $displayed=true;
                                    ?>
                                    dtbaker_changerequest.display_change(<?php echo $change_request['change_request_id'];?>);
                            <?php }
                            if(isset($_SESSION['_change_request_highlight']) && $_SESSION['_change_request_highlight'] == $change_request['change_request_id']){ ?>
                                    <?php if(!$displayed){ ?>
                                    dtbaker_changerequest.display_change(<?php echo $change_request['change_request_id'];?>);
                                    <?php } ?>
                                    dtbaker_changerequest.highlight(<?php echo (int)$_SESSION['_change_request_highlight'];?>);
                                    <?php
                                    unset($_SESSION['_change_request_highlight']);
                                }
                            } ?>
                        });
                        <?php
                    }else{
                        // not posting the URL, some setups do not like this
                        // get list of active change requests
                        $change_requests = self::get_change_requests(array('website_id'=>$website_id,'status'=>_CHANGE_REQUEST_STATUS_NEW));
                        // we also do completed ones because the change request highlight countbe in there
                        $completed_change_requests = self::get_change_requests(array('website_id'=>$website_id,'status'=>_CHANGE_REQUEST_STATUS_COMPLETE));
                        ?>

                        jQuery(function(){
                            var current_url = window.location.href;
                            <?php foreach($change_requests as $change_request){ ?>
                            if(current_url == '<?php echo addcslashes(htmlspecialchars($change_request['url']),"'");?>'){
                                // todo: do this better!
                                dtbaker_changerequest.display_change(<?php echo $change_request['change_request_id'];?>);
                            }
                            <?php } ?>
                            <?php
                        // todo: do we display all previous change requests on the page or not?
                        if(isset($_SESSION['_change_request_highlight']) && $_SESSION['_change_request_highlight']){
                            echo '// Checking for request: '.(int)$_SESSION['_change_request_highlight'];
                                foreach($completed_change_requests as $complete_change_request){
                                    if($complete_change_request['change_request_id'] == $_SESSION['_change_request_highlight']){
                                        // show this completed one as well.
                                        ?>
                                        dtbaker_changerequest.display_change(<?php echo $complete_change_request['change_request_id'];?>);
                                        <?php
                                    }
                                }
                                ?>
                            dtbaker_changerequest.highlight(<?php echo (int)$_SESSION['_change_request_highlight'];?>);
                            <?php
                            // todo: move this unset over to the "display_change" callback so we only remove the session when we know it has been displayed.
                            unset($_SESSION['_change_request_highlight']);
                            } ?>
                        });
                        <?php
                    }
                }
                exit;
                break;
            case 'public':
                $website_id = isset($_REQUEST['i']) ? (int)$_REQUEST['i'] : false;
                $hash = isset($_REQUEST['hash']) ? $_REQUEST['hash']:false;
                if($website_id && $hash && module_change_request::link_public($website_id,true) == $hash){
                    // correct!

                    // redirect to website with our "change_request" url parameter, that is picked up by the included text.
                    $website = module_website::get_website($website_id);
                    $change_request_website = get_single('change_request_website','website_id',$website_id);
                    if($change_request_website && $change_request_website['enabled']){
                        $url = module_website::urlify($website['url']); // todo - pass this to a (yet to be created) method in website that will deal with https:// or http:// based on user input. stop hardcoding http!
                        if(isset($_REQUEST['change_request_id'])){
                            $selected_change_request = self::get_change_request_by_website($website_id,(int)$_REQUEST['change_request_id']);
                            if($selected_change_request && $selected_change_request['url']){
                                $url = $selected_change_request['url'];
                            }
                            //$url .= "&change_request_id=".(int)$_REQUEST['change_request_id'];
                            $_SESSION['_change_request_highlight'] = (int)$_REQUEST['change_request_id'];
                        }
                        $url = $url . (strpos($url,'?')===false ? '?' : '&') . 'change_request='.self::link_script($website_id,true);
                        redirect_browser($url);
                    }
                }
                echo "Change request disabled.";
                break;
        }
    }

    public function get_install_sql(){
        ob_start();
        ?>

CREATE TABLE `<?php echo _DB_PREFIX; ?>change_request` (
  `change_request_id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `website_id` int(11) NOT NULL DEFAULT '0',
  `request` TEXT NOT NULL DEFAULT '',
  `status` INT(11) NOT NULL DEFAULT '1',
  `job_id` INT(11) NOT NULL DEFAULT '0',
  `task_id` INT(11) NOT NULL DEFAULT '0',
  `x` INT(11) NOT NULL DEFAULT '0',
  `y` INT(11) NOT NULL DEFAULT '0',
  `window_width` INT(11) NOT NULL DEFAULT '0',
  `create_ip` varchar(15) NOT NULL DEFAULT '',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  PRIMARY KEY  (`change_request_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE `<?php echo _DB_PREFIX; ?>change_request_website` (
  `website_id` int(11) NOT NULL auto_increment,
  `enabled` char(1) NOT NULL DEFAULT '0',
  `limit_number` int(11) NOT NULL DEFAULT '0',
  `limit_per` int(11) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  PRIMARY KEY  (`website_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE `<?php echo _DB_PREFIX; ?>change_request_file` (
  `change_request_id` int(11) NOT NULL ,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY  (`change_request_id`, `file_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


<?php
        return ob_get_clean();
    }


}
