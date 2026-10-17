<?php

if(_DEMO_MODE && isset($_GET['demo_login'])){

    if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
        $url  = $_SERVER['REQUEST_URI'];
        $bits = explode( '/', $url );
        if ( empty( $bits[0] ) ) {
            array_shift( $bits );
        } // for some reason this is needed on wpdocker ?
        $sub_host = preg_replace( '$[^a-zA-Z0-9]$', '', $bits[0] );
        // strpos() returns 0 for a match at the start, so !strpos() was true for the very
        // names this was meant to skip. And it only makes sense when the demo is served
        // from a subdirectory - at the root of demo.ultimateclientmanager.com the first
        // path segment is a page name, and redirecting to /<page>/index.php goes nowhere.
        if( strlen( $sub_host ) && strpos( $sub_host, 'template' ) === false && is_dir( $sub_host ) ){
	        ob_end_clean();
	        ?>

            <html>
            <head>
                <title>Ultimate Client Manager - Demo</title>
            </head>
            <body>
            <div style="text-align: center; padding:40px; font-size: 23px;">
                <a href="/<?php echo $sub_host;?>/index.php" target="_top" id="preview_link">Please click here to launch the demo.</a>
            </div>
            <script>
                document.getElementById('preview_link').style.display = 'none';
                top.location.href = '/<?php echo $sub_host;?>/index.php';
            </script>

            </body>
            </html>
	        <?php
	        exit;
        }
    }


}

// The theme switcher needs nothing here. module_theme::get_current_theme() already reads
// demo_theme in demo mode and keeps the choice in the session, so each visitor sees their
// own theme and the stored one is left alone.
//
// What used to be here called save_config( 'theme_name', ... ) when demo mode was OFF -
// the one case where it is not sandboxed - so any unauthenticated request with
// ?demo_theme= could change the theme of a real install. It did nothing for the demo.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Some time in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

//echo 'checking '.$page."<br>";
$custom_page = 'design_header.php';

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

// Without demo mode there is no demo chrome to add, so render the real header and stop.
// This file is only present on the demo and in a development checkout, but it being
// present is not the same as the demo being on - ./scripts/demo-mode.sh switches that,
// and the banner has to follow it or the switch does not mean anything.
if ( ! _DEMO_MODE ) {
	include( $page_to_use );

	return;
}

// Take back exactly the buffer opened on the line above. The loop that used to be here
// drained every level, which on a host with output_buffering on swallows the one PHP
// opened for the request as well, and leaves the rest of the page writing to nothing.
$ob_level_before_header = ob_get_level();
ob_start();
include($page_to_use);
$header_html = '';
while(ob_get_level() > $ob_level_before_header){
	$header_html = ob_get_clean() . $header_html;
}

ob_start();
$demo_header_height = 117;
$demo_header_height_small_adjustment = 10;
?>
	<!-- start theme demo code - copyright @dtbaker -->
	<style type="text/css">
		body.demo_visible {
			margin-top: <?php echo $demo_header_height+5;?>px !important;
		}
		/* adminlte fix */
		@media screen and (min-width: 992px) {
			body.theme-adminlte.demo_visible .left-side {
				top: <?php echo $demo_header_height+50;?>px;
			}
		}
		body.demo_visible > #holder {
			top: <?php echo $demo_header_height+10;?>px;
		}
		body.demo_visible .modal {
			top: <?php echo $demo_header_height+30;?>px;
		}
		body.demo_visible #dtbaker_demo_header {
			height: <?php echo $demo_header_height+5;?>px;
		}
		body.demo_visible #dtbaker_demo_header.loading #demo_loading {
			top: <?php echo $demo_header_height+5;?>px;
		}
		body.demo_visible #dtbaker_demo_header div.preview_holder {
			height: <?php echo $demo_header_height;?>px;
		}
		body.demo_visible #dtbaker_demo_header div.preview__envato-logo{
			height: <?php echo $demo_header_height;?>px;
		}
		body.demo_visible #dtbaker_demo_header div.preview_buttons {
			height: <?php echo $demo_header_height;?>px;
		}
		body.demo_visible #dtbaker_demo_header ul li {
			height: <?php echo $demo_header_height;?>px;
		}
		body.demo_visible #dtbaker_demo_header ul li.options {
			height: <?php echo $demo_header_height;?>px;
		}
	</style>
	<link rel="stylesheet" href="//ultimateclientmanager.com/files/demo-images/demo.css">
	<div id="dtbaker_demo_header">
		<div class="preview__action--close">
			<a href="#" id="hide_demo_options">
				<i class="fa fa-times"></i> <span>Close Demo Options</span>
			</a>
		</div>
		<div class="preview_hidden">
			<a href="#" id="show_demo_options">
				<i class="fa fa-arrow-down"></i> <span>Show Demo Options</span>
			</a>
		</div>
		<!-- <div class="preview__envato-logo">
			<a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank">Envato Market</a>
		</div> -->
		<div class="preview_buttons">
			<a href="http://ultimateclientmanager.com/" target="_blank"><img src="//ultimateclientmanager.com/files/demo-images/button-about.png"></a>
			<a href="http://ultimateclientmanager.com/support/documentation-wiki/" target="_blank"><img src="//ultimateclientmanager.com/files/demo-images/button-documentation.png"></a>
		</div>
		<ul class="theme-presets">
			<li class="core-system current">
				<a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank" class="dtbaker_demo_item">
					<img src="//ultimateclientmanager.com/files/demo-images/ucm-pro-logo.png"
					     alt="UCM CRM Pro">
					<span>UCM Pro<br/>System</span>
				</a>
			</li>
			<li class="plus">
				<span>+</span>
			</li>
			<!-- <li class="theme-label">
				<a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank"><img
						src="//ultimateclientmanager.com/files/demo-images/themes.png"
						alt="PHP CRM Demo"></a>
			</li> -->
			<?php
			$current_demo = module_theme::get_current_theme();
			$themes = array(
				'pro' => array(
					'cost' => 0,
					'name' => 'Default',
					'image' => '//ultimateclientmanager.com/files/demo-images/theme-pro.png',
					'link' => 'https://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker',
					'purchase' => 'https://codecanyon.net/cart/add_items?item_ids=2621629&ref=dtbaker',
				),
				'metis' => array(
					'cost' => 0,
					'name' => 'Metis',
					'image' => '//ultimateclientmanager.com/files/demo-images/theme-default.png',
					'link' => 'https://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker',
					'purchase' => 'https://codecanyon.net/cart/add_items?item_ids=2621629&ref=dtbaker',
				),
				'theme_adminlte' => array(
					'cost' => 21,
					'name' => 'AdminLTE',
					'image' => '//ultimateclientmanager.com/files/demo-images/theme-adminlte.png',
					'link' => 'https://themeforest.net/item/ucm-theme-adminlte-crm/8565409?ref=dtbaker',
					'purchase' => 'https://codecanyon.net/cart/add_items?item_ids=2621629,8565409&ref=dtbaker',
				),
				'whitelabel1' => array(
					'cost' => 21,
					'name' => 'WhiteLabel',
					'image' => '//ultimateclientmanager.com/files/demo-images/theme-whitelabel.png',
					'link' => 'https://themeforest.net/item/ucm-theme-white-label/4120556?ref=dtbaker',
					'purchase' => 'https://codecanyon.net/cart/add_items?item_ids=2621629,4120556&ref=dtbaker',
				),
			);
			if(is_dir('includes/plugin_theme_blocks/')){
				$themes['theme_blocks'] = array(
					'cost' => 21,
					'name' => 'Blocks',
					'image' => '//ultimateclientmanager.com/files/demo-images/theme-blocks.png',
					'link' => 'https://themeforest.net/item/ucm-theme-blocks-crm/14745039?ref=dtbaker',
					'purchase' => 'https://codecanyon.net/cart/add_items?item_ids=2621629,14745039&ref=dtbaker',
				);
			}
			$u = preg_replace('#.demo_theme=\w+#','',$_SERVER['REQUEST_URI']);
			$has_addon = false;
			$total_price = 63;
			foreach($themes as $theme_id => $theme){
				if($theme_id == $current_demo){ //} && $theme['cost']){
					$total_price += $theme['cost'];
					$has_addon = $theme;
				}
				?>
				<li class="theme-switch <?php echo $theme_id == $current_demo ? ' current ' : ''; ?>">
					<a href="<?php echo htmlspecialchars($u) . (strpos($u,'?') ? '&' : '?');?>demo_theme=<?php echo $theme_id; ?>" class="dtbaker_demo_style_switch">
						<img src="<?php echo $theme['image'];?>"
						     alt="<?php echo $theme['name']; ?> theme preview">
						<span><?php echo $theme['name'];?><br/>Theme</span>
					</a>
				</li>
			<?php }
			if($has_addon){ ?>
				<li class="options">
					<div class="preview__actions">
						<div class="preview__action--buy">

							<div>
								<table>
									<tr class="title">
										<td colspan="2">
											<img src="//ultimateclientmanager.com/files/demo-images/your-package.png">
										</td>
									</tr>
									<tr class="main">
										<td>UCM Pro Edition</td>
									</tr>
									<tr class="addon">
										<td><span>+</span> <a href="<?php echo $has_addon['link'];?>" target="_blank"><?php echo $has_addon['name'];?> Theme</a></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</li>
			<?php } ?>
			<li class="options buy_now">
				<a href="<?php echo $has_addon['purchase'];?>" target="_blank"
				   class="btn btn--primary">Buy Now</a>
			</li>
            <!-- <li class="options">
                <div class="preview__actions">
                    <div class="preview__action--buy">

                        <div>
                            <p>Time Left: <span id="timer-counter">0:00</span> <?php _h('Your private demo website will expire when this timer runs out');?></p>
                        </div>
                    </div>
                </div>
            </li> -->
		</ul>
		<div id="demo_loading">
			<span>Loading UCM Demo Theme...</span>
			<div class="demo_loading_bg"></div>
		</div>
	</div>
	<script type="text/javascript">
		function dtbaker_demo_loading() {
			jQuery('#dtbaker_demo_header').addClass('loading');
		}
		var fixedFix = false;
		$(function(){
			if($('body').hasClass('fixed')){
				fixedFix = true;
			}
			if(Get_Cookie('demo_hidden') == 'yes'){
				$('body').addClass('demo_hidden');
			}else{
				$('body').addClass('demo_visible').removeClass('fixed');
			}
			$('#hide_demo_options').click(function () {
				Set_Cookie('demo_hidden', 'yes');
				$('body').removeClass('demo_visible').addClass('demo_hidden').addClass( fixedFix ? 'fixed' : 'fixednope');
				return false;
			});
			$('#show_demo_options').click(function () {
				Set_Cookie('demo_hidden', 'no');
				$('body').removeClass('demo_hidden').addClass('demo_visible').removeClass( fixedFix ? 'fixed' : 'fixednope');
				return false;
			});
			$('.dtbaker_demo_style_switch').click(function () {
				dtbaker_demo_loading();
				return true;
			});
			$('.dtbaker_demo_theme_option').change(function () {
				dtbaker_demo_loading();
				window.location.href = '?dtbaker_demo=' + $(this).data('style') + '&dtbaker_demo_option=' + $(this).data('option') + '&dtbaker_demo_option_val=' + ($(this).is(':checked') ? 1 : 0);
			});
		});
	</script>
<?php
$demo_html = ob_get_clean();

$header_html = preg_replace('#<body[^>]*>#','$0'.str_replace('$','\$',$demo_html),$header_html);
echo $header_html;

if($current_theme == 'metis_old'){
?>

<div class="alert alert-info alert-dismissable">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    Welcome to the <strong>Ultimate Client Manager Demo</strong>. This is a preview of <a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a> with the default Metis theme.  This demo resets every now and then, feel
    free to test out all the features before purchase. (<a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'],'?') === false ? '?' : '&') . 'demo_theme=theme_adminlte';?>">Swap to the new AdminLTE theme</a>)
</div>
<?php }

$bits = explode('/', ltrim($_SERVER['REQUEST_URI'], '/'));
if(count($bits) > 1 && strpos($_SERVER['HTTP_HOST'], 'demo.net')){
	?>
    <script type="text/javascript" src="https://code-demo.net/script/<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/' . $bits[0] . '/' . base64_encode(implode('/', array_slice($bits,1))) );?>"></script>

    <script type="text/javascript">
        top.postMessage({
            from_dave: true,
            hide_demo_bar: true
        },'*');
        var DtbakerUCMDemo = (function() {
            var end_time, end_time_stamp, counter_going = false;

            function do_countdown() {

                var t = end_time_stamp - Date.parse(new Date());
                if (t <= 0) {
                    $('#timer-counter').html('0:00');
                } else {
                    var minutes = Math.floor((t / 1000 / 60) % 60);
                    var hours = Math.floor((t / (1000 * 60 * 60)) % 24);
                    if (minutes < 10) {
                        minutes = '0' + minutes;
                    }
                    $('#timer-counter').html(hours + ':' + minutes);
                }

            }

            function start_countdown() {
                if (counter_going)return;
                counter_going = true;
                do_countdown();
                setInterval(do_countdown, 5000);
            }

            return {
                init: function(){
                    // what time did we start demo?
                    end_time_stamp = parseInt(Get_Cookie('demo_end_time'));
                    if(!end_time_stamp){
                        end_time = new Date();
                        end_time.setTime(end_time.getTime() + ( 1 * 60 * 60 * 1000 ) - 100);
                        end_time_stamp = Date.parse(end_time);
                        Set_Cookie('demo_end_time', end_time_stamp);
                    }
                    start_countdown();
                }
            }
        })();
        DtbakerUCMDemo.init();

    </script>

	<?php
}
?>

