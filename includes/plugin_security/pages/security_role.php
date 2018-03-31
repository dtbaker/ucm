<?php

if ( ! module_config::can_i( 'view', 'Settings' ) || ! module_security::can_i( 'view', 'Security Roles', 'Security' ) ) {
	redirect_browser( _BASE_HREF );
}
if ( isset( $_REQUEST['security_role_id'] ) ) {

	include( "security_role_edit.php" );

} else {

	include( "security_role_list.php" );

}

