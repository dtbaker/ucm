<?php

$newsletter_template_id = isset( $_REQUEST['newsletter_template_id'] ) ? $_REQUEST['newsletter_template_id'] : false;

if ( $newsletter_template_id ) {
	include( 'newsletter_template_edit.php' );
} else {
	include( 'newsletter_template_list.php' );
}