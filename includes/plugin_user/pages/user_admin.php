<?php


if ( isset( $_REQUEST['user_id'] ) ) {

	$user_id = (int) $_REQUEST['user_id'];

	if ( class_exists( 'module_security', false ) ) {
		if ( $user_id > 0 ) {
			$user = module_user::get_user( $user_id );

			if ( ! $user ) {
				die( 'Permission denied to view this user' );
			}
			$user_id = (int) $user['user_id'];
		}
		if ( $user_id > 0 ) {
			module_security::check_page( array(
				'category'  => 'Config',
				'page_name' => 'Users',
				'module'    => 'user',
				'feature'   => 'edit',
			) );
		} else {
			module_security::check_page( array(
				'category'  => 'Config',
				'page_name' => 'Users',
				'module'    => 'user',
				'feature'   => 'create',
			) );
		}
	}

	$user_safe = true;
	include( module_theme::include_ucm( "includes/plugin_user/pages/user_admin_edit.php" ) );
	//include("user_admin_edit.php");

} else {

	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'category'  => 'Config',
			'page_name' => 'Users',
			'module'    => 'user',
			'feature'   => 'view',
		) );
	}

	include( module_theme::include_ucm( "includes/plugin_user/pages/user_admin_list.php" ) );
	//include("user_admin_list.php");

} 

