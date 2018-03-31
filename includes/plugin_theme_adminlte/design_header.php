<?php

switch ( $display_mode ) {
	case 'ajax':

		break;
	case 'iframe':
	case 'normal':
	default:

		?>
		<!DOCTYPE html>
	<html dir="<?php echo module_config::c( 'text_direction', 'ltr' ); ?>"
	      id="html-<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
			<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
			<title><?php echo $page_title; ?></title>

			<?php $header_favicon = module_theme::get_config( 'theme_favicon', '' );
			if ( $header_favicon ) { ?>
				<link rel="icon" href="<?php echo htmlspecialchars( $header_favicon ); ?>">
			<?php } ?>

			<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
			<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
			<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
			<![endif]-->

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
              if (typeof ucm.adminlte != 'undefined') {
                  ucm.adminlte.init();
              }
          });
			</script>
			<?php if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'page_header' );
			} ?>
		</head>
		<?php if ( module_security::is_logged_in() ) { ?>
		<body class="theme-adminlte <?php echo module_theme::get_config( 'adminlte_colorstyle', 'dark' ) == 'light' ? 'skin-blue' : 'skin-black'; ?> <?php echo module_theme::get_config( 'adminlte_menustyle', 'fixed' ) == 'fixed' ? 'fixed' : ''; ?>"  id="<?php echo isset( $page_unique_id ) ? $page_unique_id : 'page'; ?>" <?php if ( $display_mode == 'iframe' ) {
			echo ' style="background:#FFF;"';
		} ?>>

		<?php if ( $display_mode == 'iframe' ) { ?>

			<div id="iframe">

		<?php } else {
			if ( _DEBUG_MODE ) {
				module_debug::print_heading();
			} ?>

			<header class="header">
				<a href="<?php echo _BASE_HREF; ?>" class="logo">
					<!-- Add the class icon to your logo image or logo icon to add the margining -->
					<?php if ( $header_logo = module_theme::get_config( 'theme_logo', _BASE_HREF . 'images/logo.png' ) ) { ?>
						<img src="<?php echo htmlspecialchars( $header_logo ); ?>" border="0"
						     title="<?php echo htmlspecialchars( module_config::s( 'header_title', 'UCM' ) ); ?>">
					<?php } else { ?>
						<?php echo module_config::s( 'header_title', 'UCM' ); ?>
					<?php } ?>
				</a>
				<!-- Header Navbar: style can be found in header.less -->
				<nav class="navbar navbar-static-top" id="main-navbar" role="navigation">
					<!-- Sidebar toggle button-->
					<a href="#" class="navbar-btn sidebar-toggle" data-toggle="offcanvas" role="button">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</a>

					<div class="navbar-right">

						<?php if ( module_security::is_logged_in() ) { ?>
							<ul class="nav navbar-nav">

								<?php

								$header_buttons = array();
								if ( module_security::is_logged_in() ) {
									$header_buttons = hook_filter_var( 'header_buttons', $header_buttons );
								}
								foreach ( $header_buttons as $header_button ) {
									?>
									<li class="dropdown tasks-menu">
										<?php if ( ! empty( $header_button['dropdown'] ) ) { ?>
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
										<?php } else { ?>
											<a href="#" id="<?php echo $header_button['id']; ?>">
												<!-- <?php echo $header_button['title']; ?> -->
												<i class="fa fa-<?php echo $header_button['fa-icon']; ?>"></i>
											</a>
										<?php } ?>
									</li>
									<?php
								}

								?>

								<!-- User Account: style can be found in dropdown.less -->
								<li class="dropdown user user-menu">
									<?php $user = module_user::get_user( module_security::get_loggedin_id() ); ?>
									<a href="#" class="dropdown-toggle" data-toggle="dropdown">
										<i class="glyphicon glyphicon-user"></i>
										<span><?php echo htmlspecialchars( $user['name'] ); ?> <i class="caret"></i></span>
									</a>
									<ul class="dropdown-menu">
										<!-- User image -->
										<li class="user-header bg-light-blue">
											<?php if ( module_config::c( 'adminlte_enable_gravitar', 1 ) ) { ?>
												<img
													src="//www.gravatar.com/avatar/<?php echo md5( strtolower( trim( $user['email'] ) ) ); ?>"
													class="img-circle" alt="User Image">
											<?php } ?>
											<p>
												<a href="<?php echo module_user::link_open( module_security::get_loggedin_id() ); ?>">
													<i class="fa fa-user"></i> <?php _e( 'Welcome %s', htmlspecialchars( $user['name'] ) ); ?>
												</a>
											</p>
											<small><?php echo _l( '%s %s%s of %s %s', _l( date( 'D' ) ), date( 'j' ), _l( date( 'S' ) ), _l( date( 'F' ) ), date( 'Y' ) ); ?></small>
										</li>
										<!-- Menu Footer-->
										<li class="user-footer">
											<div class="pull-left">
												<a href="<?php echo module_user::link_open( module_security::get_loggedin_id() ); ?>"
												   class="btn btn-default btn-flat"><?php _e( 'Profile' ); ?></a>
											</div>
											<div class="pull-right">
												<a href="<?php echo _BASE_HREF; ?>index.php?_logout=true"
												   class="btn btn-default btn-flat"><?php _e( 'Sign out' ); ?></a>
											</div>
										</li>
									</ul>
								</li>
							</ul>
						<?php } ?>
					</div>
				</nav>
			</header>


			<div class="wrapper row-offcanvas row-offcanvas-left">
			<!-- Left side column. contains the logo and sidebar -->
			<aside class="left-side sidebar-offcanvas">
				<!-- sidebar: style can be found in sidebar.less -->
				<section class="sidebar">
					<!-- Sidebar user panel -->
					<div class="user-panel">
						<?php $user = module_user::get_user( module_security::get_loggedin_id() );
						if ( module_config::c( 'adminlte_enable_gravitar', 1 ) ) {
							?>
							<div class="pull-left image" style="padding-top: 18px">
								<img
									src="//www.gravatar.com/avatar/<?php echo md5( strtolower( trim( $user['email'] ) ) ); ?>"
									class="img-circle" alt="User Image">
							</div>
						<?php } ?>
						<div class="pull-left info">
							<p><?php _e( 'Welcome' );
								echo '<br/>';
								echo htmlspecialchars( $user['name'] ); ?></p>
							<a href="<?php echo module_user::link_open( module_security::get_loggedin_id() ); ?>"><i
									class="fa fa-user"></i> <?php _e( 'Edit Profile' ); ?></a> <br/>
							<a href="#" onclick="return false;"><i
									class="fa fa-calendar"></i> <?php echo _l( '%s %s%s of %s %s', _l( date( 'D' ) ), date( 'j' ), _l( date( 'S' ) ), _l( date( 'F' ) ), date( 'Y' ) ); ?>
							</a>
						</div>
					</div>
					<?php if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Quick Search' ) ) {

						if ( module_config::c( 'global_search_focus', 1 ) == 1 ) {
							module_form::set_default_field( 'ajax_search_text' );
						}
						?>
						<!-- search form -->
						<form action="<?php echo _BASE_HREF; ?>?p=search_results" method="post" class="sidebar-form">
							<div class="input-group">
								<input type="text" name="quick_search" class="form-control"
								       value="<?php echo isset( $_REQUEST['quick_search'] ) ? htmlspecialchars( $_REQUEST['quick_search'] ) : ''; ?>"
								       placeholder="<?php _e( 'Quick Search:' ); ?>"/>
								<span class="input-group-btn">
	                                    <button type='submit' name='seach' id='search-btn' class="btn btn-flat"><i
			                                    class="fa fa-search"></i></button>
	                                </span>
							</div>
						</form>
						<!-- /.search form -->
						<?php
					} ?>


					<!-- sidebar menu: : style can be found in sidebar.less -->
					<?php
					$menu_include_parent = false;
					$show_quick_search   = true;
					include( module_theme::include_ucm( "design_menu.php" ) );
					?>

				</section>
				<!-- /.sidebar -->
			</aside>

			<!-- Right side column. Contains the navbar and content of the page -->
			<aside class="right-side">


			<?php
			// copied from print_header_message();
			if ( ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) || ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) ) {
				?>
				<div id="header_messages">
					<?php if ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) {
						foreach ( $_SESSION['_message'] as $msg ) {
							?>
							<div class="alert alert-success alert-dismissable" style="margin:20px 15px 10px 34px">
								<i class="fa fa-check"></i>
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
								<?php echo nl2br( ( $msg ) ); ?>
							</div>

							<?php
						}
					}
					if ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) {
						foreach ( $_SESSION['_errors'] as $msg ) {
							?>
							<div class="alert alert-danger alert-dismissable" style="margin:20px 15px 10px 34px">
								<i class="fa fa-ban"></i>
								<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
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
			<!-- Content Header (Page header) -->
			<section class="content-header">
				<h1>
					<?php
					//<i class="fa fa-home"></i> {page title here}
					echo isset( $GLOBALS['adminlte_main_title'] ) ? $GLOBALS['adminlte_main_title'] : $page_title;
					?>
				</h1>
				<!-- <ol class="breadcrumb">
<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
<li><a href="#">Examples</a></li>
<li class="active">Blank page</li>
</ol> -->
			</section>

			<!-- Main content -->
			<section class="content">


			<?php
			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'content_header' );
			}
		} // iframe ?>

		<div id="content">

		<?php
	} else{
		// not logged in
	    ?>
        <body class="theme-adminlte bg-black" id="login-page">
        <?php
	    print_header_message();
	}
} // switch

ob_start();