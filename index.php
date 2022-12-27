<?php


/**
 * Ultimate Client Manager
 * Version 2
 *
 * Author: David Baker
 * Email: dtbaker@gmail.com
 * Please send all EMAILS through http://codecanyon.net/user/dtbaker
 * Copyright 2011 David Baker
 * You must have purchased a valid license from CodeCanyon to use this script.
 *
 * If you would like to re-use parts of this system or ideas from this system elsewhere please ask permission first.
 */

$start_time   = microtime( true );
$debug_info   = array();
$debug_info[] = array(
	'time'  => $start_time,
	'title' => 'Start',
	'mem'   => function_exists( 'memory_get_usage' ) ? round( memory_get_usage() / 1048576, 4 ) : '',
);

if ( preg_match( '#/external/(m.*$)#', $_SERVER['REQUEST_URI'], $matches ) ) {
	// hack for dodgy email clients.
	$parts = explode( '/', trim( $matches[1], '/' ) );
	foreach ( $parts as $key => $val ) {
		$foo = explode( '.', $val );
		if ( $foo && isset( $foo[0] ) && isset( $foo[1] ) ) {
			$_REQUEST[ $foo[0] ] = preg_replace( '#\?.*$#', '', $foo[1] );
		}
	}

	include( 'ext.php' );
	exit;
}


header( 'Content-Type: text/html; charset=UTF-8' );
//header('Pragma: no-cache');
//header('Cache-Control: private, no-cache, no-store, max-age=0, must-revalidate, proxy-revalidate');
//header('Expires: Tue, 04 Sep 2012 05:32:29 GMT');

$debug_info[] = array(
	'time'  => microtime( true ),
	'title' => 'Including init.php',
	'mem'   => function_exists( 'memory_get_usage' ) ? round( memory_get_usage() / 1048576, 4 ) : '',
);
require_once( 'init.php' );
if ( _DEBUG_MODE ) {
	module_debug::log( array(
		'title' => 'including init.php done',
	) );
}


$required_php_version = '5.3.0';
if ( version_compare( PHP_VERSION, $required_php_version ) < 0 ) {
	$setup_errors = true;
	echo "PHP version of 5.3 or above is REQUIRED to run this software - the current PHP version is: " . PHP_VERSION . ". Please contact the web hosting provider and request a PHP version upgrade.";
}

if ( ! _UCM_INSTALLED ) {
	$_REQUEST['m']            = 'setup';
	$_REQUEST['display_mode'] = 'normal';
}

$display_mode = get_display_mode();

// stop more than 1 of the same page load.
$loaded_pages = array();

$current_menu_level = 0; // increases each time we load a menu on the page.


// this is an update for design_menu.php
$menu_modules      = $load_modules;
$menu_module_index = count( $menu_modules );

$page_unique_id = ''; // used for styling.


$required_php_version = '5.3.0';
if ( version_compare( PHP_VERSION, $required_php_version ) < 0 ) {
	echo( "I'm sorry, a PHP version of $required_php_version or above is REQUIRED to run this - the current PHP version is: " . PHP_VERSION . ". Please ask the web hosting provider to upgrade you." );
}

try {
	foreach ( $load_modules as $load_module_key => $load_module ) {

		$load_module = basename( $load_module );
		$load_page   = isset( $load_pages[ $load_module_key ] ) ? basename( $load_pages[ $load_module_key ] ) : false;
		// if the user isn't logged in, display the login page.
		if ( ! _UCM_INSTALLED || $load_module == 'setup' ) {
			$load_page   = 'setup';
			$load_module = 'setup';
		} else if ( ! getcred() ) {
			if ( is_callable( 'module_security::check_ssl' ) ) {
				module_security::check_ssl();
			}
			$load_page = 'login';
		} else if ( ! $load_page ) {
			$load_page = 'home';
		}
		if ( $load_page ) { //} && is_file("pages/".$load_page.".php")){
			$page = "pages/" . $load_page . ".php";
		} else {
			$page = 'pages/home.php';
		}
		// load this particular module so other scripts can access the $module variable.
		$module = false;
		if ( isset( $plugins[ $load_module ] ) ) {
			$module = &$plugins[ $load_module ];
		}

		if ( module_security::getcred() || $load_module == 'setup' ) {

			// handle any form submits for this module.
			if ( isset( $_REQUEST['_process'] ) && $_REQUEST['_process'] ) {
				if ( $module ) {
					module_debug::log( array(
						'title' => 'Process Post Back',
						'file'  => 'index.php',
						'data'  => "_process variable found, passing this through to module: $load_module",
					) );
					$module->process();
				} else {
					die( 'invalid process' );
				}
			}
			if ( $module && $load_page ) {
				$page = "includes/plugin_" . $load_module . "/pages/" . $load_page . ".php";
				// this allows us to have a custom page file that doesn't exist in the core system
				$page_test = module_theme::include_ucm( $page );
			}
			if ( $module && $load_page && is_file( $page_test ) ) {
				// pull out the module in a local var ready for these pages to use.
				$page = "includes/plugin_" . $load_module . "/pages/" . $load_page . ".php";
			} else if ( $load_page ) { // && is_file("pages/".$load_page.".php")){
				$page = "pages/" . $load_page . ".php";
			}

			module_debug::log( array(
				'title' => 'Found Page',
				'file'  => 'index.php',
				'data'  => "found a page to load for module $load_module: $page",
			) );
		}


		if ( ! isset( $loaded_pages[ $page ] ) ) {
			$loaded_pages[ $page ] = true;

			$page_unique_id .= ( strlen( $page_unique_id ) ? '-' : '' ) . str_replace( '/', '-', str_replace( '.php', '', str_replace( 'includes/plugin_', '', str_replace( '/pages', '', $page ) ) ) );
			ob_start(); // START INNER CONTENT OB
			module_debug::log( array(
				'title' => 'Page Render (0)',
				'file'  => 'index.php',
				'data'  => "Including this page: $page",
			) );
			// update! we check if this "page" has a custom version as per the current display (eg: mobile) or theme
			$page_final = module_theme::include_ucm( $page );
			if ( ! is_file( $page_final ) ) {
				$page_final = module_theme::include_ucm( 'pages/home.php' );
			}
			hook_handle_callback( 'inner_content_start', $module, $page_final, $page_unique_id );
			include( $page_final );
			//include($page);
			if ( class_exists( 'module_security', false ) ) {
				// this will do some magic if the user only has "view" permissions to this editable page.
				module_security::render_page_finished();
			}
			if ( $module ) {
				// we find any sub module LINKS that have to be displayed here,
				// which will guve the user the option of navigating to a sub module.
				$has_sub_links = false;
				if ( $display_mode != 'ajax' ) {
					foreach ( $plugins as $plugin_name => &$plugin ) {
						if ( $plugin->get_menu( $module->module_name, $load_page ) ) {
							$has_sub_links = true;
							break;
						}
					}
				}
				if ( ( isset( $links ) && count( $links ) ) || $has_sub_links ) {
					$menu_include_parent = $current_menu_level;
					module_debug::log( array(
						'title' => 'Page Render (1)',
						'file'  => 'index.php',
						'data'  => "Including this page (the menu): design_menu.php",
					) );
					ob_start(); // START MENU OB
					if ( $display_mode != 'ajax' && is_file( 'design_menu.php' ) ) {
						// remove 'final_content_wrap' from other outputs!
						include( module_theme::include_ucm( "design_menu.php" ) );
						if ( ! isset( $do_menu_wrap ) ) {
							echo '<div class="final_content_wrap">';
							$do_menu_wrap = true;
						}
					}
					// todo - fix but with more than 2 levels of menus.
					// maybe instead of "include parents" we just pass whatever level we are currently on to the script
					// and it will work out the rest.
					// could maybe move the design_menu call from design_header.php up here to fix the issue. all in 1 place then :)
					$current_menu_level ++;
					?>
					<div class="content">
						<?php
						// the inner content will display where this place holder is:
						if ( isset( $inner_content ) && count( $inner_content ) ) {
							module_debug::log( array(
								'title' => 'Page Render (2)',
								'file'  => 'index.php',
								'data'  => "Displaying content from the 'inner_content' array.",
							) );
							echo array_shift( $inner_content );
						} else if ( $current_selected_link ) {
							if ( isset( $current_selected_link['default_page'] ) && is_file( "includes/plugin_" . $current_selected_link['m'] . "/pages/" . basename( $current_selected_link['default_page'] ) . ".php" ) ) {
								$module_page = "includes/plugin_" . $current_selected_link['m'] . "/pages/" . basename( $current_selected_link['default_page'] ) . ".php";
								module_debug::log( array(
									'title' => 'Page Render (3)',
									'file'  => 'index.php',
									'data'  => "Including this page: $module_page",
								) );
								//include($module_page);
								include( module_theme::include_ucm( $module_page ) );
								if ( class_exists( 'module_security', false ) ) {
									// this will do some magic if the user only has "view" permissions to this editable page.
									module_security::render_page_finished();
								}
							}
						}
						?>
					</div>
					<?php
					if ( isset( $do_menu_wrap ) && $do_menu_wrap ) {
						echo '</div>';
					}
					echo ob_get_clean(); // END MENU OB
				} else if ( ! isset( $do_menu_wrap ) ) {
					// no sub links!
					$do_menu_wrap = false;
					$content      = ob_get_contents();
					ob_clean();
					echo '<div class="final_content_wrap">';
					echo $content;
					echo '</div>';

				}
				if ( isset( $links ) ) {
					unset( $links );
				}
			}
			hook_handle_callback( 'inner_content_end', $module, $page_final, $page_unique_id );
			$inner_content [] = ob_get_clean(); // END INNER CONTENT OB
		}

		// see if this module has a page title.
		if ( $module && module_security::is_logged_in() ) {
			if ( $module->get_page_title() ) {
				$page_title = htmlspecialchars( $module->get_page_title() ) . $page_title_delim . $page_title;
			}
		}

		if ( isset( $module ) ) {
			unset( $module );
		}
		/*if(preg_match('#\{INNER_CONTENT\}#',$inner_content)){
			$inner_content = preg_replace('#\{INNER_CONTENT\}#',$this_content,$inner_content);
		}else{
			$inner_content .= $this_content;
		}
		unset($this_content);*/
		unset( $load_page );

		if ( $display_mode == 'iframe' || $display_mode == 'ajax' ) {
			break;
		}

	}
} catch ( Exception $e ) {
	$inner_content[] = 'Error: ' . $e->getMessage();
}
// combine any inner content together looking for place holders.

$page_title = trim( preg_replace( '#' . preg_quote( $page_title_delim, '#' ) . '\s*$#', '', $page_title ) );
if ( ! trim( $page_title ) ) {
	$page_title = htmlspecialchars( module_config::s( 'admin_system_name', 'Ultimate Client Manager' ) );
}

if ( $page_unique_id && function_exists( 'newrelic_name_transaction' ) ) {
	newrelic_name_transaction( 'Admin: ' . $page_unique_id );
	if ( function_exists( 'newrelic_capture_params' ) ) {
		newrelic_capture_params();
	}
}


if ( _DEBUG_MODE ) {
	module_debug::log( array(
		'title' => 'Displaying contents: ',
		'data'  => '',
	) );
}

require_once( module_theme::include_ucm( "design_header.php" ) );
echo implode( '', $inner_content );
require_once( module_theme::include_ucm( "design_footer.php" ) );

if ( _DEBUG_MODE ) {
	module_debug::log( array(
		'title' => 'Finished displaying contents, running finish hook ',
		'data'  => '',
	) );
}


hook_finish();


if ( _DEBUG_MODE ) {
	module_debug::log( array(
		'title' => 'Finished final hook ',
		'data'  => '',
	) );
}
if ( _DEBUG_MODE ) {
	module_debug::print_footer();
}
