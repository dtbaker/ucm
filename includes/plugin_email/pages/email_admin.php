<?php 

if(isset($_REQUEST['email_id'])){

    include(module_theme::include_ucm("email_admin_edit.php"));

}else{ 
	
	include(module_theme::include_ucm("email_admin_list.php"));
	
} 

