<?php


if ( ! _UCM_INSTALLED ) {
	module_security::logout();
}

$setup_errors = false;
// see if we can write to a file, ext.php or design_menu.php
if ( ! touch( _UCM_FOLDER . 'cron.php' ) || ! touch( _UCM_FOLDER . 'ext.php' ) || ! touch( _UCM_FOLDER . 'design_menu.php' ) ) {
	set_error( 'Sorry, the base folder <strong>' . _UCM_FOLDER . '</strong> is not writable by PHP. Please contact your hosting provider and ask for this folder to be set writable by PHP. Or change the permissions to writable (777 in most cases) using your FTP program.' );
	$setup_errors = true;
}

// check folder permissions and the like.
$temp_folder = _UCM_FILE_STORAGE_DIR . "temp/";
if ( ! is_dir( $temp_folder ) || ! is_writable( $temp_folder ) ) {
	if ( $temp_folder === false ) {// doesn't exist.
		$temp_folder = '/temp/';
	}
	set_error( 'Sorry, the folder <strong>' . $temp_folder . '</strong> is not writable. Please contact your hosting provider and ask for this folder to be set writable by PHP. Or change the permissions to writable using your FTP program.' );
	$setup_errors = true;
}
// check folder permissions and the like.
$temp_folder = _UCM_FOLDER . "includes/";
if ( ! is_dir( $temp_folder ) || ! is_writable( $temp_folder ) ) {
	if ( $temp_folder === false ) {// doesn't exist.
		$temp_folder = '/includes/';
	}
	$setup_errors = true;
	set_error( 'Sorry, the folder <strong>' . $temp_folder . '</strong> is not writable. Please contact your hosting provider and ask for this folder to be set writable by PHP. Or change the permissions to writable using your FTP program.' );
}
// check folder permissions and the like.
/*$temp_folder = _UCM_FOLDER . "includes/plugin_ticket/attachments/";
if(!is_dir($temp_folder) || !is_writable($temp_folder)){
    if($temp_folder===false){// doesn't exist.
        $temp_folder = '/includes/plugin_ticket/attachments/';
    }
    $setup_errors=true;
    set_error('Sorry, the folder <strong>'.$temp_folder.'</strong> is not writable. Please contact your hosting provider and ask for this folder to be set writable by PHP. Or change the permissions to writable using your FTP program.');
}*/

if ( ! is_writable( _UCM_FOLDER . 'includes/config.php' ) ) {
	$setup_errors = true;
	set_error( 'Please make sure the file <strong>includes/config.php</strong> is writable by PHP. Your hosting provider can do this or you can change these settings in your favorite FTP program.' );
}

$required_php_version = 5.1;
if ( floatval( phpversion() ) < $required_php_version ) {
	$setup_errors = true;
	set_error( "I'm sorry, a PHP version of $required_php_version or above is REQUIRED to run this - the current PHP version is: " . floatval( phpversion() ) . ". Your web hosting provider can usually push a button to upgrade you, please contact them" );
}

$required_php_version = 5.3;
if ( floatval( phpversion() ) < $required_php_version ) {
	$setup_errors = true;
	set_error( "PHP version $required_php_version or above RECOMMENDED to run this program - the current PHP version is: " . floatval( phpversion() ) . ". Your web hosting provider can usually push a button to upgrade you, please contact them. You can still try to install this by clicking the Ignore Errors button." );
}

// check sql mode.
/*
$sql = "SELECT @@sql_mode AS `mode`";
$res = qa1($sql);
if($res && isset($res['mode']) && strpos($res['mode'],'STRICT_TRANS_TABLES') !== false){
    $setup_errors=true;
    set_error("Your MySQL is in STRICT mode. This system will not work. Please ask your hosting provider to disable strict mode, or run this SQL statement if you have root mysql access: SET @@global.sql_mode= '';");
}*/


if ( ! class_exists( 'SimpleXMLElement', false ) ) {
	$setup_errors = true;
	set_error( 'Sorry SimpleXMLElement is not enabled on your server. Please enable this before continuing.' );
} else {
	$xml = '<foo><bar id="f"><moo>123</moo></bar><cat /></foo>';
	$foo = new SimpleXMLElement( $xml );
	if ( ! $foo || $foo->bar->moo != 123 ) {
		$setup_errors = true;
		set_error( 'Error with SimpleXMLElement class. Please check it is enabled on your hosting account' );
	}
}

if ( ! function_exists( 'imap_open' ) ) {
	$setup_errors = true;
	set_error( 'Sorry IMAP is not enabled on your hosting account. Please contact your host to have this enabled.' );
}
if ( ! function_exists( 'curl_init' ) ) {
	$setup_errors = true;
	set_error( 'Sorry CURL is not enabled on your hosting account. Please contact your host to have this enabled.' );
} else {
	// do a test connection
	$ch = curl_init( 'http://ultimateclientmanager.com/api/?curl_check' );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_HEADER, false );
	$result = curl_exec( $ch );
	if ( trim( $result ) != 'success' ) {
		$setup_errors = true;
		set_error( 'There was a problem with CURL. Please check CURL is enabled and your server has a connection to the internet.' );
	}
}


if ( $setup_errors ) {
	print_heading( 'Setup Error' );
	print_header_message();
	?>
	<p>If you require support, or assistance installing this item, please send in a support ticket here: <a
			href="http://ultimateclientmanager.com/support-ticket.html" target="_blank">http://ultimateclientmanager.com/support-ticket.html</a>
	</p>

	<a href="?p=setup" class="uibutton">Try Again</a>
	<a href="?m=setup&step=1" class="uibutton">Ignore Errors</a>
	<?php
} else {
	redirect_browser( '?m=setup&step=1' );
}


?>