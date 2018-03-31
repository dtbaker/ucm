<?php

switch ( $display_mode ) {
	case 'ajax':

		break;
	case 'iframe':
	case 'normal':
	default:

		//$header_nav_mini = '';
		ob_start();
		$menu_include_parent = false;
		$show_quick_search   = true;
		include( module_theme::include_ucm( "design_menu.php" ) );
		$main_menu = ob_get_clean();

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




			<?php module_config::print_css( _SCRIPT_VERSION ); ?>



			<?php module_config::print_js( _SCRIPT_VERSION ); ?>


			<!--
			Author: David Baker (dtbaker.com.au)
			10/May/2010
			-->
			<script type="text/javascript">
          $(function () {
              // override default button init - since we're using jquery ui here.
              ucm.init_buttons = function () {
              };
              ucm.init_interface();
              if (typeof ucm.metis != 'undefined') {
                  ucm.metis.init();
								<?php if(module_theme::get_config( 'metismenustyle', 'normal' ) == 'fixed'){ ?>
                  $('#menu').affix({offset: {top: $('#menu').offset().top}}, 100);
								<?php } ?>
              }

          });
			</script>

			<?php
			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'page_header' );
			} ?>


		</head>
		<?php //fixed side-right 
		?>
	<body class="theme-metis <?php
	if ( module_theme::get_config( 'metissidebar-position', 'left' ) == 'right' ) {
		echo 'side-right ';
	} else {
		echo 'side-left ';
	}
	if ( module_theme::get_config( 'metispagewidth', 'wide' ) == 'narrow' ) {
		echo 'fixed ';
	} else {
		echo 'wide ';
	}
	?>" id="<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>" <?php if ( $display_mode == 'iframe' ) {
		echo ' style="background:#FFF;"';
	} ?>>

		<?php if ( $display_mode == 'iframe' ){ ?>

	<div id="iframe">

		<?php }else{ ?>

		<?php if ( _DEBUG_MODE ) {
			module_debug::print_heading();
		} ?>

		<?php if ( module_security::getcred() ) { ?>
			<header class="navbar navbar-inverse navbar-top visible-xs" role="banner" id="responsive_mini_header">
				<div class="container" id="menu_copy_holder">
					<div class="navbar-header">
						<button class="navbar-toggle" type="button" data-toggle="collapse"
						        data-target="#responsive_mini_header > div > nav">
							<span class="sr-only">Toggle navigation</span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
							<span class="icon-bar"></span>
						</button>
						<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
							<a href="<?php echo _BASE_HREF; ?>" class="navbar-mini-brand"><img
									src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
									title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>"></a>
						<?php } else { ?>
							<a href="<?php echo _BASE_HREF; ?>"
							   class="navbar-mini-brand"><?php echo module_config::s( 'header_title', 'UCM' ); ?></a>
						<?php } ?>
					</div>
					<nav class="collapse navbar-collapse bs-navbar-collapse" role="navigation">
						<ul class="nav navbar-nav">
							<?php if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Quick Search' ) ) {

								if ( module_config::c( 'global_search_focus', 1 ) == 1 ) {
									module_form::set_default_field( 'ajax_search_text' );
								}
								?>
								<li>
									<form class="mini-search" action="<?php echo _BASE_HREF; ?>?p=search_results" method="post">
										<div class="input-group">
											<input type="text" class="input-small form-control"
											       value="<?php echo isset( $_REQUEST['search'] ) && is_string( $_REQUEST['search'] ) ? htmlspecialchars( $_REQUEST['search'] ) : ''; ?>"
											       name="search" placeholder="<?php _e( 'Quick Search:' ); ?>">
											<span class="input-group-btn">
                                    <button class="btn btn-default btn-sm" type="submit"><i
		                                    class="fa fa-search"></i></button>
                                </span>
										</div>
									</form>
								</li>
								<?php
							}
							if ( module_security::getcred() ) { ?>
								<li>
									<a href="<?php echo module_user::link_open( $_SESSION['_user_id'] ); ?>">
										<i
											class="fa fa-user"></i> <?php $user = module_user::get_user( module_security::get_loggedin_id() );
										_e( 'Welcome %s', $user['name'] ); ?>
									</a>
								</li>
							<?php } ?>
							<?php
							// this special nav menu is generated from header_menu.php to contain items that display only in responsive mini mode
							echo ! empty( $menu_result['nav_items_mini'] ) ? $menu_result['nav_items_mini'] : '';
							// add some custom stuff underneath here
							?>
							<li>
								<a href="<?php echo _BASE_HREF; ?>index.php?_logout=true"><i
										class="fa fa-power-off"></i> <?php _e( 'Logout' ); ?></a>
							</li>
						</ul>
					</nav>

				</div>
			</header>
		<?php } ?>

		<div id="wrap">

		<div id="top">

			<!-- <div class="header-spacer visible-xs" style="height:51px;"></div> -->
			<nav class="navbar navbar-inverse navbar-static-top hidden-xs">
				<!-- Brand and toggle get grouped for better mobile display -->
				<header class="navbar-header">
					<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
						<a href="<?php echo _BASE_HREF; ?>" class="navbar-brand"><img
								src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
								title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>"></a>
					<?php } else { ?>
						<a href="<?php echo _BASE_HREF; ?>"
						   class="navbar-brand"><?php echo module_config::s( 'header_title', 'UCM' ); ?></a>
					<?php } ?>
				</header>

				<?php if ( module_security::getcred() ) { ?>

					<div class="topnav">
						<?php
						$header_buttons = array();
						$header_buttons = hook_filter_var( 'header_buttons', $header_buttons );
						foreach ( $header_buttons as $header_button ) {
							?>
							<div class="btn-group">
								<?php if ( ! empty( $header_button['dropdown'] ) ) { ?>
									<a id="<?php echo $header_button['id']; ?>" data-placement="bottom"
									   data-original-title="<?php echo $header_button['title']; ?>" href="#" data-toggle="dropdown"
									   class="btn btn-default btn-sm dropdown-toggle">
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
								                     <span>
									                     <?php
									                     echo $dropdown_item['title'];
									                     echo ' <br/> ';
									                     echo $dropdown_item['description']; ?>
									                     <small
										                     class="pull-right"><?php echo $dropdown_item['sub-description']; ?></small>
								                     </span>
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
								<?php } else { ?>
									<a id="<?php echo $header_button['id']; ?>" data-placement="bottom"
									   data-original-title="<?php echo $header_button['title']; ?>" href="#" data-toggle="tooltip"
									   class="btn btn-default btn-sm">
										<i class="fa fa-<?php echo $header_button['fa-icon']; ?>"></i>
									</a>
								<?php } ?>
							</div>
							<?php
						} ?>
						<div class="btn-group">
							<a href="<?php echo _BASE_HREF; ?>index.php?_logout=true" data-toggle="tooltip"
							   data-original-title="<?php _e( 'Logout' ); ?>" data-placement="bottom" class="btn btn-metis-1 btn-sm">
								<i class="fa fa-power-off"></i>
							</a>
						</div>
					</div>

				<?php } ?>

			</nav>
			<!-- /.navbar -->

			<div>
				<div style="position:absolute; z-index:1004; margin-left:367px;width:293px; display:none;" id="message_popdown">
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

			<!-- header.head -->
			<header class="head">
				<div class="search-bar hidden-xs">
					<a data-original-title="Show/Hide Menu" data-placement="bottom" data-tooltip="tooltip"
					   class="accordion-toggle btn btn-primary btn-sm visible-xs" data-toggle="collapse" href="#menu"
					   id="menu-toggle">
						<i class="fa fa-resize-full"></i>
					</a>

					<?php if ( module_security::getcred() && module_security::can_user( module_security::get_loggedin_id(), 'Show Quick Search' ) ) {

						if ( module_config::c( 'global_search_focus', 1 ) == 1 ) {
							module_form::set_default_field( 'ajax_search_text' );
						}
						?>
						<form class="main-search" action="<?php echo _BASE_HREF; ?>?p=search_results" method="post">
							<div class="input-group">

								<input type="text" class="input-small form-control" name="quicksearch"
								       value="<?php echo isset( $_REQUEST['quicksearch'] ) ? htmlspecialchars( $_REQUEST['quicksearch'] ) : ''; ?>"
								       placeholder="<?php _e( 'Quick Search:' ); ?>">
								<div id="ajax_search_result"></div>
								<span class="input-group-btn">
                                <button class="btn btn-default btn-sm" type="submit"><i
		                                class="fa fa-search"></i></button>
                            </span>
							</div>
						</form>
						<?php
					} ?>
				</div>
				<!-- ."main-bar -->
				<div class="main-bar">
					<h3>
						<?php
						//<i class="fa fa-home"></i> {page title here}
						echo isset( $GLOBALS['metis_main_title'] ) ? $GLOBALS['metis_main_title'] : $page_title;
						?>
					</h3>
				</div>
				<!-- /.main-bar -->
			</header>
			<!-- end header.head -->


		</div>
		<!-- /#top -->

		<?php if ( module_security::getcred() ) { ?>
			<div id="left">

				<div class="media user-media">
					<div class="media-body">
						<h5 class="media-heading"><?php $user = module_user::get_user( module_security::get_loggedin_id() );
							_e( 'Welcome %s', htmlspecialchars( $user['name'] ) ); ?></h5>
						<ul class="list-unstyled user-info">
							<li>
								<small><i class="fa fa-user"></i> <a
										href="<?php echo module_user::link_open( $_SESSION['_user_id'] ); ?>"><?php _e( 'Edit Profile' ); ?></a>
								</small>
							</li>
							<li>
								<small><i
										class="fa fa-calendar"></i> <?php echo _l( '%s %s%s of %s %s', _l( date( 'D' ) ), date( 'j' ), _l( date( 'S' ) ), _l( date( 'F' ) ), date( 'Y' ) ); ?>
								</small>
							</li>
							<li>
								<small><i class="fa fa-power-off"></i><a
										href="<?php echo _BASE_HREF; ?>index.php?_logout=true"> <?php _e( 'Logout' ); ?></a></small>
							</li>
						</ul>
					</div>
				</div>

				<?php
				// this is generated from header_menu.php at the top of this file.
				echo $main_menu;
				?>

			</div>
			<!-- /#left -->
		<?php } // logged in ?>

	<?php } // iframe 
		?>

		<div id="content">
		<div class="outer">
		<?php // <div class="inner"> moved to design_menu in a hack to get it looking better 
		?>
		<div class="inner">

		<?php


		if ( function_exists( 'hook_handle_callback' ) ) {
			hook_handle_callback( 'content_header' );
		}
}

ob_start();