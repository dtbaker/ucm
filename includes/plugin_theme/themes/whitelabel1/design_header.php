<?php

switch ( $display_mode ) {
	case 'mobile':
		if ( class_exists( 'module_mobile', false ) ) {
			module_mobile::render_start( $page_title, $page );
		}
		break;
	case 'ajax':

		break;
	case 'iframe':
	case 'normal':
	default:

			$header_logo = module_theme::get_config('theme_logo',_BASE_HREF.'images/logo.png');

			?>

			<!DOCTYPE html>
			<html dir="<?php echo module_config::c('text_direction','ltr');?>">
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<title><?php echo $page_title; ?></title>

			<!-- Apple iOS and Android stuff -->
			<meta name="apple-mobile-web-app-capable" content="no">
			<meta name="apple-mobile-web-app-status-bar-style" content="black">
			<link rel="apple-touch-icon-precomposed" href="<?php echo htmlspecialchars($header_logo);?>">

			<!-- Apple iOS and Android stuff - don't remove! -->
			<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no,maximum-scale=1">


			<?php $header_favicon = module_theme::get_config('theme_favicon','');
					if($header_favicon){ ?>
							<link rel="icon" href="<?php echo htmlspecialchars($header_favicon);?>">
			<?php } ?>

			<link rel="stylesheet" href="//fonts.googleapis.com/css?family=PT+Sans:regular,bold&subset=latin,latin-ext">
			<?php module_config::print_css(_SCRIPT_VERSION);?>

			<?php module_config::print_js(_SCRIPT_VERSION);?>

			<!--
			Author: David Baker (dtbaker.com.au)
			10/May/2010
			-->
			<script type="text/javascript">
					$(function(){
							ucm.init_interface();
					});
			</script>

<?php if(function_exists('hook_handle_callback')){
	hook_handle_callback('page_header');
} ?>

			</head>
			<body id="<?php echo isset($page_unique_id) ? $page_unique_id : 'page';?>" <?php if($display_mode=='iframe') {echo ' style="background:#FFF;"';}?> class="theme-whitelabel1">

<?php if($display_mode=='iframe'){ ?>

	<section id="iframe">

<?php }else{ ?>

			<?php if(_DEBUG_MODE){
					module_debug::print_heading();
			} ?>
			<div id="pageoptions">
		<ul>
					<?php if (module_security::getcred()){ ?>
							<li><a href="<?php echo _BASE_HREF;?>index.php?_logout=true"><?php _e('Logout');?></a></li>
						<?php
							$header_buttons = array();
				if(module_security::is_logged_in()) {
					$header_buttons = hook_filter_var( 'header_buttons', $header_buttons );
				}
				foreach($header_buttons as $header_button){
					?>
					<li>
						<a href="#" id="<?php echo $header_button['id'];?>">
								<i class="fa fa-<?php echo $header_button['fa-icon'];?>"></i>
								<?php echo $header_button['title'];?>
						</a>
					</li>
				<?php
				}
				?>
							<li><?php echo module_user::link_open($_SESSION['_user_id'],true);?></li>
							<li><?php echo _l('%s %s%s of %s %s',_l(date('l')),date('j'),_l(date('S')),_l(date('F')),date('Y')); ?></li>
							<?php
							}
							?>
		</ul>
			</div>

	<!-- start header -->
	<header>
			<div id="header_logo">
					<?php if($header_logo){ ?>
							<a href="<?php echo _BASE_HREF;?>"><img src="<?php echo htmlspecialchars($header_logo);?>" border="0" title="<?php echo htmlspecialchars(module_config::s('header_title','UCM'));?>"></a>
					<?php }else{ ?>
							<a href="<?php echo _BASE_HREF;?>"><?php echo module_config::s('header_title','UCM');?></a>
					<?php } ?>
					<?php if(_DEMO_MODE){ ?>
							<a href="http://themeforest.net/item/ucm-theme-white-label/4120556?ref=dtbaker" target="_blank"><img src="http://ultimateclientmanager.com/webimages/logo_whitelabel.png" border="0"></a>
					<?php } ?>
	</div>
			<div id="header_menu_holder">
					<?php if(module_security::getcred() && module_security::can_user(module_security::get_loggedin_id(),'Show Quick Search') && $display_mode != 'mobile'){
							if(module_config::c('global_search_focus',1)==1){
											module_form::set_default_field('ajax_search_text');
									}
							?>
							<div id="searchbox">
									<form id="searchform" autocomplete="off">
											<input type="search" name="query" id="ajax_search_text" placeholder="<?php _e('Quick Search:'); ?>" class="">
									</form>
							</div>
					<?php } ?>
					<div class="headernavwrap">
							<?php if(isset($header_submenu_content)){echo $header_submenu_content;}?>
					</div>
					<div class="clear"></div>
			</div>
			<div id="ajax_search_result"></div>
			<div class="clear"></div>
	</header>

	<!-- end header -->

			<nav>
					<?php
					$menu_include_parent=false;
					$show_quick_search=true;
					if(is_file('design_menu.php')){
							//include("design_menu.php");
							include(module_theme::include_ucm("design_menu.php"));
					}
					?>
	</nav>
<?php
} // iframe. ?>

<section id="content">
	<?php
	$show_messages = false;
	//print_header_message() // << fail
	if(isset($_SESSION['_message']) && count($_SESSION['_message'])){
			$show_messages = true;
			?>
			<div class="alert success message_click_fade">
							<?php
							$x=1;
							foreach($_SESSION['_message'] as $msg){
									if(count($_SESSION['_message'])>1){
											echo "<strong>#$x</strong> ";
											$x++;
									}
									echo nl2br(($msg))."<br>";
							}
							?>
			</div>
	<?php
			$_SESSION['_message'] = array();
}
if(isset($_SESSION['_errors']) && count($_SESSION['_errors'])){
			$show_messages = true;
			foreach($_SESSION['_errors'] as $msg){
					?>
					<div class="alert warning message_click_fade">
							<?php echo nl2br($msg)."<br>"; ?>
					</div>
			<?php
			}
			$_SESSION['_errors'] = array();
}
	if($show_messages){
	?>
	<script type="text/javascript">
			$(function(){
					$('.message_click_fade').click(function(){$(this).fadeOut('fast');});
			});
	</script>
	<?php
	}


if(function_exists('hook_handle_callback')){
	hook_handle_callback('content_header');
}
}

?>