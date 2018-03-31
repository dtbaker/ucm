<?php

switch ( $display_mode ) {
	case 'ajax':

		break;
	case 'iframe':
	case 'normal':
	default:

		$page_widgets = new theme_widget_manager( $page_unique_id );
		?>
		<!DOCTYPE html>
	<html dir="<?php echo module_config::c( 'text_direction', 'ltr' ); ?>"
	      id="html-<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<title><?php echo $page_title; ?></title>
			<?php $header_favicon = module_theme::get_config( 'theme_favicon', '' );
			if ( $header_favicon ) { ?>
				<link rel="icon" href="<?php echo htmlspecialchars( $header_favicon ); ?>">
			<?php } ?>

			<style type="text/css">
				nav ul.menu {
					background: <?php echo htmlspecialchars(module_theme::get_config('blocks_menu_bg','#aef6fa'));?>;
				}

				nav ul.menu li.dropdown:after,
				nav ul.menu p,
				nav ul.menu li ul li a,
				nav ul.menu li > a > span {
					color: <?php echo htmlspecialchars(module_theme::get_config('blocks_menu_fg','#1f3334'));?>;
				}

				<?php if(module_theme::get_config('blocks_menu_collapse','allow') != 'allow'){ ?>
				nav a.menu-btn {
					display: none;
				}

				body.box-large {
					padding: 0;
				}

				<?php }else{ ?>
				body.box-large {
					padding: 0 0 0<?php echo module_theme::get_config('blocks_boxstyle','box-large') == 'box-large' ? '61px' : '35px';?>;
				}

				<?php } ?>
			</style>

			<?php module_config::print_css( _SCRIPT_VERSION ); ?>

			<?php module_config::print_js( _SCRIPT_VERSION ); ?>
			<!--
			Author: dtbaker.net
			Date: 2014-08-12
			Package: UCM
			-->
			<script type="text/javascript">
          $(function () {
              // override default button init - since we're using jquery ui here.
              ucm.init_buttons = function () {
              };
              ucm.init_interface();
              if (typeof ucm.blocks != 'undefined') {
                  ucm.blocks.init();
              }
          });
			</script>

			<?php
			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'page_header' );
			}
			?>
		</head>
		<?php if ( module_security::is_logged_in() ) { ?>
		<body class="theme-blocks <?php echo module_theme::get_config( 'blocks_colorstyle', 'dark' ) == 'light' ? 'skin-light' : 'skin-dark'; ?> <?php echo module_theme::get_config( 'blocks_boxstyle', 'box-large' ); ?> <?php echo module_theme::get_config( 'blocks_content_padding', 'with-content-padding' ); ?>"  id="<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>" <?php if ( $display_mode == 'iframe' ) {
			echo ' style="background:#FFF;"';
		} ?>>

		<?php if ( $display_mode == 'iframe' ) { ?>

			<div id="iframe">

		<?php } else {
			if ( _DEBUG_MODE ) {
				module_debug::print_heading();
			} ?>

			<header class="clearfix">
				<div class="user left clearfix">
					<?php
					$user = module_user::get_user( module_security::get_loggedin_id() );
					?>
					<?php if ( module_config::c( 'blocks_enable_gravitar', 1 ) ) {
						?>
						<div class="avatar">
							<img
								src="//www.gravatar.com/avatar/<?php echo md5( strtolower( trim( $user['email'] ) ) ); ?>"
								class="" alt="User Image">
						</div>
					<?php } ?>
					<p>
						<?php _e( 'Welcome:' ); ?>
						<a
							href="<?php echo module_user::link_open( module_security::get_loggedin_id() ); ?>"><?php echo htmlspecialchars( $user['name'] ); ?></a><br>
						<span><?php echo _l( '%s %s%s of %s %s', _l( date( 'D' ) ), date( 'j' ), _l( date( 'S' ) ), _l( date( 'F' ) ), date( 'Y' ) ); ?></span>
					</p>
				</div>
				<div class="user left clearfix headerlogo">
					<a href="<?php echo _BASE_HREF; ?>" class="logo">
						<!-- Add the class icon to your logo image or logo icon to add the margining -->
						<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
							<img src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
							     title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>">
						<?php } else { ?>
							<?php echo module_config::s( 'header_title', 'UCM' ); ?>
						<?php } ?>
					</a>
				</div>
				<?php if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Quick Search' ) ) {

					if ( module_config::c( 'global_search_focus', 1 ) == 1 ) {
						module_form::set_default_field( 'ajax_search_text' );
					}
					?>
					<div class="search right clearfix">
						<?php
						$header_buttons = array();
						if ( module_security::is_logged_in() ) {
							$header_buttons = hook_filter_var( 'header_buttons', $header_buttons );
						}
						foreach ( $header_buttons as $header_button ) {
							?>
							<?php if ( ! empty( $header_button['dropdown'] ) ) { ?>
								<div class="options">
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">
										<!-- <?php echo $header_button['title']; ?> -->
										<i class="fa fa-<?php echo $header_button['fa-icon']; ?>"></i>
										<span class="label label-danger"><?php echo count( $header_button['dropdown'] ); ?></span>
									</a>

									<ul class="dropdown-menu">
										<?php if ( ! empty( $header_button['header'] ) ) { ?>
											<li class="header"><?php echo $header_button['header']; ?></li>
										<?php } ?>
										<li>
											<ul class="menu">
												<?php foreach ( $header_button['dropdown'] as $dropdown_item ) { ?>
													<li><!-- Task item -->
														<a href="<?php echo $dropdown_item['link']; ?>">
															<h3>
																<?php
																echo $dropdown_item['title'];
																echo ' <br/> ';
																echo $dropdown_item['description']; ?>
																<small class="pull-right"><?php echo $dropdown_item['sub-description']; ?></small>
															</h3>
															<?php if ( ! empty( $dropdown_item['percentage'] ) ) { ?>
																<div class="progress xs">
																	<div class="progress-bar progress-bar-aqua"
																	     style="width: <?php echo round( $dropdown_item['percentage'] * 100 ); ?>%"
																	     role="progressbar"
																	     aria-valuenow="<?php echo round( $dropdown_item['percentage'] * 100 ); ?>"
																	     aria-valuemin="0"
																	     aria-valuemax="100">
																		<span
																			class="sr-only"><?php _e( '%s%% Complete', round( $dropdown_item['percentage'] * 100 ) ); ?></span>
																	</div>
																</div>
															<?php } ?>
														</a>
													</li><!-- end task item -->
												<?php } ?>
											</ul>
										</li>
										<?php if ( ! empty( $header_button['footer'] ) ) { ?>
											<li class="footer"><?php echo $header_button['footer']; ?></li>
										<?php } ?>
									</ul>
								</div>
							<?php } else { ?>
								<a href="#" class="options" id="<?php echo $header_button['id']; ?>">
									<!-- <?php echo $header_button['title']; ?> -->
									<i class="fa fa-<?php echo $header_button['fa-icon']; ?>"></i>
								</a>
							<?php } ?>
							<?php
						}

						if ( module_config::can_i( 'view', 'Settings' ) ) {
							?>
							<a href="<?php echo $plugins['config']->link_generate( false, array( 'page' => 'config_admin' ) ); ?>"
							   class="options"><i class="fa fa-cog"></i></a>
						<?php } ?>
						<a href="<?php echo _BASE_HREF; ?>index.php?_logout=true" class="options"><i
								class="fa fa-power-off"></i></a>
						<form action="<?php echo _BASE_HREF; ?>?p=search_results" method="post">
							<input type="text" name="quick_search" class="form-control"
							       value="<?php echo isset( $_REQUEST['quick_search'] ) ? htmlspecialchars( $_REQUEST['quick_search'] ) : ''; ?>"
							       placeholder="<?php _e( 'Quick Search:' ); ?>"/>
							<button type="submit">
								<i class="fa fa-search"></i>
							</button>
						</form>
					</div>
				<?php } ?>
			</header>

			<div id="wrapper" class="clearfix expand">

			<!-- sidebar menu: : style can be found in sidebar.less -->
			<?php
			$menu_include_parent = false;
			$show_quick_search   = true;
			include( module_theme::include_ucm( "design_menu.php" ) );
			?>

			<div id="content" class="right">

			<div id="page-header">
				<div class="breadcrumbs clearfix">
					<?php
					//<i class="fa fa-home"></i> {page title here}
					echo isset( $GLOBALS['blocks_main_title'] ) ? $GLOBALS['blocks_main_title'] : '<ul class="breadcrumbs left"><li><span class="title">' . $page_title . '</span></li></ul>';
					?>
				</div>
				<div class="header-widgets">
					<?php $page_widgets->display(); ?>
				</div>
			</div>
			<?php
			// copied from print_header_message();
			if ( ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) || ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) ) {
				?>
				<div id="header_messages" class="alerts">
					<?php if ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) {
						foreach ( $_SESSION['_message'] as $msg ) {
							?>
							<div class="success">
								<i class="fa fa-check"></i>
								<?php echo nl2br( ( $msg ) ); ?>
							</div>

							<?php
						}
					}
					if ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) {
						foreach ( $_SESSION['_errors'] as $msg ) {
							?>
							<div class="error">
								<i class="fa fa-ban"></i>
								<?php echo nl2br( ( $msg ) ); ?>
							</div>

							<?php
						}
					} ?>
				</div>
				<?php
				$_SESSION['_message'] = array();
				$_SESSION['_errors']  = array();
			}
			?>

			<section class="inner-content">


			<?php

			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'content_header' );
			}
		} // iframe ?>

		<div id="inner-content">

		<?php
	} else{
		// not logged in
	    ?>
        <body class="bg-black theme-blocks" id="login-page">
        <?php
	    print_header_message();
	}
} // switch

ob_start();