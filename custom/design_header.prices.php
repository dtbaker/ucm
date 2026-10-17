<?php

if(!_DEMO_MODE && isset($_REQUEST['demo_theme'])){
	module_config::save_config('theme_name',$_REQUEST['demo_theme']);
}

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

ob_start();
include($page_to_use);
$header_html = '';
while(ob_get_level()){
	$header_html .= ob_get_clean();
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
			<a href="http://ultimateclientmanager.com/online-demo/" target="_blank"><img src="//ultimateclientmanager.com/files/demo-images/button-about.png"></a>
			<a href="http://ultimateclientmanager.com/support/documentation-wiki/" target="_blank"><img src="//ultimateclientmanager.com/files/demo-images/button-documentation.png"></a>
		</div>
		<ul class="theme-presets">
			<li class="core-system current">
				<a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker" target="_blank" class="dtbaker_demo_item">
					<img src="//ultimateclientmanager.com/files/demo-images/ucm-pro-logo.png"
					     alt="UCM CRM Pro">
					<div class="price">$63</div>
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
						<div class="price"><?php echo $theme['cost'] ? '$'.$theme['cost'] : 'Free';?></div>
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
										<th>$63</th>
									</tr>
									<tr class="addon">
										<td><span>+</span> <a href="<?php echo $has_addon['link'];?>" target="_blank"><?php echo $has_addon['name'];?> Theme</a></td>
										<th>$<?php echo $has_addon['cost'];?></th>
									</tr>
									<tr class="total">
										<td>
											Total:
										</td>
										<th>$<?php echo $total_price;?></th>
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
    Welcome to the <strong>Ultimate Client Manager Demo</strong>. This is a preview of <a href="http://codecanyon.net/item/ultimate-client-manager-crm-pro-edition/2621629?ref=dtbaker&amp;utm_source=Demo&amp;utm_medium=Header&amp;utm_campaign=Metis&amp;utm_content=Metis" target="_blank">UCM Pro Edition</a> with the default Metis theme.  This demo resets every now and then, feel
    free to test out all the features before purchase. (<a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) . (strpos($_SERVER['REQUEST_URI'],'?') === false ? '?' : '&') . 'demo_theme=theme_adminlte';?>">Swap to the new AdminLTE theme</a>)
</div>
<?php } ?>

<script type="text/javascript">
var fb_param = {};
fb_param.pixel_id = '6008149695449';
fb_param.value = '0.00';
fb_param.currency = 'USD';
(function(){
  var fpw = document.createElement('script');
  fpw.async = true;
  fpw.src = '//connect.facebook.net/en_US/fp.js';
  var ref = document.getElementsByTagName('script')[0];
  ref.parentNode.insertBefore(fpw, ref);
})();
</script>
<noscript><img height="1" width="1" alt="" style="display:none" src="https://www.facebook.com/offsite_event.php?id=6008149695449&amp;value=0&amp;currency=USD" /></noscript>
