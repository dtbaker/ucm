<?php
if(!module_change_request::can_i('delete','Change Requests'))die('no perms');
$change_request_id = (int)$_REQUEST['change_request_id'];
$change_request = module_change_request::get_change_request($change_request_id);
if(!$change_request['website_id'])die('no linked website');
$website_data = module_website::get_website($change_request['website_id']);

if(module_form::confirm_delete('change_request_id',"Really delete Change Request?",module_website::link_open($change_request['website_id']))){
    module_change_request::delete_change_request($_REQUEST['change_request_id']);
    set_message("Change request deleted successfully");
    redirect_browser(module_website::link_open($change_request['website_id']));
}