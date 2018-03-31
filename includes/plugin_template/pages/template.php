<?php
//include("top_menu.php");

if ( isset( $_REQUEST['template_id'] ) && $_REQUEST['template_id'] ) {
	include( "template_edit.php" );
} else {
	include( "template_list.php" );
}
?>