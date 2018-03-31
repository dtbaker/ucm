<?php
define("_REWRITE_LINKS",false);

// cache wordpress calls to speed things up a bit.
if(strpos($_SERVER['REQUEST_URI'],'/m.wordpress/h.public') !== false && isset($_REQUEST['action']) && $_REQUEST['action'] != 'download_update'){
	$key = md5($_SERVER['REMOTE_ADDR'] . serialize($_REQUEST));
	$GLOBALS['wordpress_temp_file'] = dirname(__FILE__).'/temp/wp_'.basename($key);
	if(is_file($GLOBALS['wordpress_temp_file']) && filemtime($GLOBALS['wordpress_temp_file']) > (time() - 3600)){
//		header("Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($GLOBALS['wordpress_temp_file']))." GMT");
//        header("HTTP/1.1 304 Not Modified");
		$data = unserialize(file_get_contents($GLOBALS['wordpress_temp_file']));
		echo $data['content'];
		exit;
	}
	ob_start();
	function wordpress_shutdown() {
		$data = array(
			'request' => $_REQUEST,
			'content' => ob_get_clean(),
		);
		if(isset($GLOBALS['wordpress_temp_file'])) {
			file_put_contents( $GLOBALS['wordpress_temp_file'], serialize( $data ) );
		}
		echo $data['content'];
	}
	register_shutdown_function('wordpress_shutdown');
}

$noredirect = true;
$external = true;
$dont_init_plugins = isset($_REQUEST['plight']) ||
                     (isset($_SERVER['REQUEST_URI']) &&
                      (
	                      strpos($_SERVER['REQUEST_URI'],'/m.newsletter/h.i') !== false ||
	                      strpos($_SERVER['REQUEST_URI'],'/m.newsletter/h.l') !== false ||
	                      strpos($_SERVER['REQUEST_URI'],'/m.wordpress/h.public') !== false ||
	                      strpos($_SERVER['REQUEST_URI'],'/m.file/h.download/') !== false ||
	                      strpos($_SERVER['REQUEST_URI'],'m=file&h=download&i') !== false
                      ));
if(strpos($_SERVER['REQUEST_URI'],'/m.wordpress/h.public') !== false){
	$plugins_to_init = isset($plugins_to_init) && is_array($plugins_to_init) ? $plugins_to_init : array();
	$plugins_to_init[] = 'extra'; // wordpress envato integration needs extra
}
if(strpos($_SERVER['REQUEST_URI'],'/m.newsletter/h.i') !== false || strpos($_SERVER['REQUEST_URI'],'/m.newsletter/h.l') !== false){
	$plugins_to_init = isset($plugins_to_init) && is_array($plugins_to_init) ? $plugins_to_init : array();
	$plugins_to_init[] = 'newsletter'; // wordpress envato integration needs extra
}
if($dont_init_plugins){
    $disable_sessions = true;
//    ini_set('display_errors',true);
//    ini_set('error_reporting',E_ALL);
}
include('init.php');


if($load_modules){
    $m = current($load_modules);
    $m = basename(trim($m));
}else{
    $m = false;
}
//$m = (isset($_REQUEST['m'])) ? trim(basename($_REQUEST['m'])) : false;
$h = (isset($_REQUEST['h'])) ? trim(basename($_REQUEST['h'])) : false;

if($dont_init_plugins && $m && !isset($plugins[$m])){
    $external_plugins_to_load = array();
    $external_plugins_to_load[] = basename($m);
    if(in_array('wordpress',$external_plugins_to_load)){
        $external_plugins_to_load[] = 'envato';
    }
    foreach($external_plugins_to_load as $external_plugin_to_load){
        $external_plugin_to_load = basename($external_plugin_to_load);
        $plugin_dir = 'includes/plugin_'.$external_plugin_to_load;
        if(is_dir($plugin_dir) && is_file($plugin_dir."/".$external_plugin_to_load.".php")){
            require_once($plugin_dir."/".$external_plugin_to_load.".php");
            eval('$plugins[$external_plugin_to_load] = new module_'.$external_plugin_to_load.'();');
            $plugins[$external_plugin_to_load]->init();
        }
    }
}
if($m && isset($plugins[$m])){
    if(method_exists($plugins[$m],'external_hook')){
        if(function_exists('newrelic_name_transaction')){
            newrelic_name_transaction('External: '.$m.'/'.$h);
            if(function_exists('newrelic_capture_params')){
                newrelic_capture_params();
            }
        }
        $plugins[$m] -> external_hook($h);
    }
}

hook_finish();