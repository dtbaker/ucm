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


		?>

		<!DOCTYPE html>
	<html dir="<?php echo module_config::c( 'text_direction', 'ltr' ); ?>">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<title><?php echo $page_title; ?></title>

			<?php $header_favicon = module_theme::get_config( 'theme_favicon', '' );
			if ( $header_favicon ) { ?>
				<link rel="icon" href="<?php echo htmlspecialchars( $header_favicon ); ?>">
			<?php } ?>

			<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/desktop.css?ver=<?php echo _SCRIPT_VERSION; ?>"
			      type="text/css"/>
			<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/styles.css?ver=<?php echo _SCRIPT_VERSION; ?>"
			      type="text/css"/>
			<?php module_config::print_css( _SCRIPT_VERSION ); ?>

			<?php module_config::print_js( _SCRIPT_VERSION ); ?>


			<!--
			Author: David Baker (dtbaker.com.au)
			10/May/2010
			-->
			<script type="text/javascript">
          $(function () {
              init_interface();
          });
			</script>

			<?php if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'page_header' );
			} ?>


		</head>
	<body id="<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>" <?php
	if ( $display_mode == 'iframe' ) {
		echo ' style="background:#FFF;"';
	} ?>>
		<?php if ( $display_mode == 'iframe' ){ ?>

	<div id="iframe">

		<?php }else{ ?>

		<?php if ( _DEBUG_MODE ) {
			module_debug::print_heading();
		} ?>
		<div id="holder">

		<div id="header">

			<div>
				<div style="position:absolute; z-index:1004; margin-left:367px;width:293px; display:none;"
				     id="message_popdown">
					<?php if ( print_header_message() ) {
						?>
						<script type="text/javascript">
                $('#message_popdown').fadeIn('slow');
								<?php if(module_config::c( 'header_messages_fade_out', 1 )){ ?>
                $(function () {
                    setTimeout(function () {
                        $('#message_popdown').fadeOut();
                    }, 4000);
                });
								<?php } ?>
						</script>
						<?php
					} ?>
				</div>
			</div>

			<?php if ( _DEMO_MODE && preg_match( '#/demo_lite/#', $_SERVER['REQUEST_URI'] ) ) { ?>
				<div style="margin: 10px 0 0 296px;position:absolute;">
					<a href="http://goo.gl/YYgVJ" title="Download Ultimate Client Manager"><img
							src="http://ultimateclientmanager.com/webimages/like-what-you-see-here.png" border="0"
							alt="Freelance Database - php client manager"></a>
				</div>
			<?php } ?>

			<div id="header_logo">
				<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
					<a href="<?php echo _BASE_HREF; ?>"><img src="<?php echo htmlspecialchars( $header_logo ); ?>"
					                                         border="0"
					                                         title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>"></a>
				<?php } else { ?>
					<a href="<?php echo _BASE_HREF; ?>"><?php echo module_config::s( 'header_title', 'UCM' ); ?></a>
				<?php } ?>
			</div>
			<?php
			if ( module_security::getcred() ) {
				?>
				<div id="profile_info">
					<?php
					$header_buttons = array();
					if ( module_security::is_logged_in() ) {
						$header_buttons = hook_filter_var( 'header_buttons', $header_buttons );
					}
					foreach ( $header_buttons as $header_button ) {
						?>
						<a href="#" id="<?php echo $header_button['id']; ?>">
							<i class="fa fa-<?php echo $header_button['fa-icon']; ?>"></i>
							<?php echo $header_button['title']; ?>
						</a>
						<span class="sep">|</span>
						<?php
					}
					?>
					<?php echo module_user::link_open( $_SESSION['_user_id'], true ); ?> <span class="sep">|</span>
					<a href="<?php echo _BASE_HREF; ?>index.php?_logout=true"><?php _e( 'Logout' ); ?></a>
					<div
						class="date"><?php echo _l( '%s %s%s of %s %s', _l( date( 'l' ) ), date( 'j' ), _l( date( 'S' ) ), _l( date( 'F' ) ), date( 'Y' ) ); ?></div>
				</div>
				<?php
			}
			?>

		</div>

		<div id="main_menu">
			<?php
			$menu_include_parent = false;
			$show_quick_search   = true;
			if ( is_file( 'design_menu.php' ) ) {
				//include("design_menu.php");
				include( module_theme::include_ucm( "design_menu.php" ) );
			}
			?>
		</div>

		<div id="page_middle">
		<?php
		if ( function_exists( 'hook_handle_callback' ) ) {
			hook_handle_callback( 'content_header' );
		}
	}// end ifelse iframe mode
		?>

		<div class="content">


	<?php
}
