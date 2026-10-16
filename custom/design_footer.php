<?php

//echo 'checking '.$page."<br>";
$custom_page = 'design_footer.php';

$display_mode = get_display_mode();


        $check_files = array();
        $current_theme = basename(module_theme::$current_theme);
        if(strlen($current_theme)>2 && is_dir('includes/plugin_theme/themes/'.$current_theme.'/')){
            // we have an active theme!
        }else{
            $current_theme = false;
        }

        // build up our file listing.
        if($display_mode == 'mobile'){
            //$check_files[] = 'custom/'.dirname($page).'/mobile/'.basename($page);
        }
        //$check_files[] = 'custom/'.$page;
        if($display_mode == 'mobile'){
            //$check_files[] = 'includes/plugin_mobile/custom_layout/'.$page;
            $check_files[] = dirname($custom_page).'/mobile/'.basename($custom_page);
        }
        if($current_theme)$check_files[] = 'includes/plugin_theme/themes/'.$current_theme.'/'.$custom_page;
if(module_theme::$current_theme_settings && isset(module_theme::$current_theme_settings['base_dir']) && strlen(module_theme::$current_theme_settings['base_dir']) > 2 && is_dir(module_theme::$current_theme_settings['base_dir'])){
            // we have an active theme!
            $check_files[] = module_theme::$current_theme_settings['base_dir'].$custom_page;
        }
//print_r($check_files);
        $check_files[] = $custom_page;


$page_to_use = $custom_page;

        foreach($check_files as $file){
            module_debug::log(array(
                'title' => 'IncludeUCM',
                'file' => 'includes/plugin_theme/theme.php',
                'data' => "Checking for include file: ".$file,
            ));
            if(is_file($file)){
                module_debug::log(array(
                    'title' => 'IncludeUCM',
                    'file' => 'includes/plugin_theme/theme.php',
                    'data' => "FOUND FILE! ".$file,
                ));
                $page_to_use = $file;
break;
            }
        }

// Same as the header: present is not the same as on.
if ( ! _DEMO_MODE ) {
	include( $page_to_use );

	return;
}

$ob_level_before_footer = ob_get_level();
ob_start();
include($page_to_use);
$custom_footer = '';
while(ob_get_level() > $ob_level_before_footer){
	$custom_footer = ob_get_clean() . $custom_footer;
}

// NOTE: this is Universal Analytics. UA properties stopped processing data in July 2023,
// so analytics.js collects nothing - it is a third party script on every demo page doing
// no work. Replace it with a GA4 tag or delete it; left in place because removing the
// demo's analytics is the owner's call, not a side effect of a refactor.
$custom_footer = str_replace("</body>","<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-27663088-4', 'ultimateclientmanager.com');
  ga('send', 'pageview');

</script></body>",$custom_footer);
echo $custom_footer;

