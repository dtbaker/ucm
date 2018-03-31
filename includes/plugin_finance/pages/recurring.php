<?php


if ( ! module_finance::can_i( 'view', 'Finance Upcoming' ) ) {
	redirect_browser( _BASE_HREF );
}

if ( isset( $_REQUEST['finance_recurring_id'] ) && $_REQUEST['finance_recurring_id'] && isset( $_REQUEST['record_new'] ) ) {
	include( module_theme::include_ucm( dirname( __FILE__ ) . '/finance_edit.php' ) );
} else if ( isset( $_REQUEST['finance_recurring_id'] ) && $_REQUEST['finance_recurring_id'] ) {
	//include("recurring_edit.php");
	include( module_theme::include_ucm( dirname( __FILE__ ) . '/recurring_edit.php' ) );
} else {
	//include("recurring_list.php");
	include( module_theme::include_ucm( dirname( __FILE__ ) . '/recurring_list.php' ) );
}