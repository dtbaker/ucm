<?php

function config_sort_css( $a, $b ) {
	return $a[3] > $b[3];
}

class module_config extends module_base {

	private static $config_vars = array();

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->module_name     = "config";
		$this->module_position = 40;
		$this->version         = 2.433;
		//2.433 - 2019-04-06 - php error fix
		//2.432 - 2017-06-27 - date format
		//2.431 - 2017-06-14 - file path configuration
		//2.430 - 2017-05-02 - file path configuration
		//2.429 - 2017-05-02 - upgrade improvement
		//2.428 - 2017-05-02 - upgrade improvement
		//2.427 - 2017-02-26 - config fix
		//2.426 - 2017-01-03 - database utf8 fix
		//2.425 - 2016-11-28 - cron job dashboard notice fix
		//2.424 - 2016-07-10 - big update to mysqli
		//2.423 - 2016-04-29 - fix for settings form
		//2.422 - 2016-02-05 - settings page bug fix
		//2.421 - 2016-02-02 - menu configuration
		//2.420 - 2016-02-02 - menu configuration
		//2.419 - 2016-01-26 - config settings form
		//2.418 - 2015-12-28 - menu speed up option
		//2.417 - 2015-12-27 - menu icons
		//2.416 - 2015-06-07 - new settings button
		//2.415 - 2015-04-05 - stuck plugin update fix
		//2.414 - 2015-04-05 - character encoding fix
		//2.413 - 2015-03-14 - speed improvement
		//2.412 - 2015-02-08 - theme/custom override js file support
		//2.411 - 2015-01-20 - more speed improvements
		//2.41 - 2014-12-22 - ssl fix
		//2.4 - 2014-11-17 - much faster upgrade system
		//2.393 - 2014-11-04 - upgrade page improvement
		//2.392 - 2014-11-04 - upgrade page improvement
		//2.391 - 2014-10-07 - showing latest updates/blog posts in upgrade window.
		//2.39 - 2014-09-29 - faster update checking
		//2.389 - 2014-09-05 - improved config defaults
		//2.388 - 2014-08-12 - faster updates
		//2.387 - 2014-08-10 - fixed updater
		//2.386 - 2014-08-10 - fixed updater
		//2.385 - 2014-08-10 - progress showing in upgrader
		//2.384 - 2014-08-09 - bug fix for older jquery
		//2.383 - 2014-08-06 - better js handling
		//2.382 - 2014-07-25 - faster updates
		//2.381 - 2014-07-09 - js_combine / css_combine for much faster page loading
		//2.38 - 2014-07-05 - js_combine / css_combine for much faster page loading
		//2.379 - 2014-07-02 - js_combine / css_combine for much faster page loading
		//2.378 - 2014-03-12 - improved upgrader
		//2.377 - 2014-02-25 - improved installer
		//2.376 - 2013-11-13 - company config bug fix
		//2.375 - 2013-10-06 - software update reminder on dashboard
		//2.374 - 2013-10-05 - settings page improvement
		//2.373 - 2013-09-06 - installation improvement
		//2.372 - 2013-09-01 - fix for cache bug
		//2.371 - 2013-06-21 - different config vars per company
		//2.37 - 2013-04-30 - clearer upgrade instructions

		//2.31 - putting date_input to the general settings area
		//2.32 - friendly licence code names
		//2.33 - menu fix.
		//2.34 - js / css callbacks
		//2.35 - skipping custom files in the upgrade process
		//2.36 - permission fixes
		//2.361 - memory limit via config
		//2.362 - memory limit fix
		//2.363 - upload php limit fix
		//2.364 - php5/6 fix
		//2.365 - date format settings fix
		//2.366 - css/js updates
		//2.367 - css loading fix
		//2.368 - upgrade fixing
		//2.369 - click to edit config values

		// load some default configurations.
		if ( ! defined( '_DATE_FORMAT' ) ) {
			define( '_DATE_FORMAT', module_config::c( 'date_format', 'd/m/Y' ) ); // todo: read from database
		}
		if ( ! defined( '_DATE_INPUT' ) ) {
			// 1 = DD/MM/YYYY
			// 2 = YYYY/MM/DD
			// 3 = MM/DD/YYYY
			// 4 = DD.MM.YYYY
			define( '_DATE_INPUT', module_config::c( 'date_input', '1' ) );
		}
		if ( ! defined( '_ERROR_EMAIL' ) ) {
			define( '_ERROR_EMAIL', module_config::c( 'admin_email_address', 'info@' . $_SERVER['HTTP_HOST'] ) );
		}


		if ( ! defined( '_UCM_FILE_STORAGE_HREF' ) ) {
			define( '_UCM_FILE_STORAGE_HREF', _BASE_HREF );
		}

		date_default_timezone_set( module_config::c( 'timezone', 'America/New_York' ) );

		if ( module_security::is_logged_in() && isset( $_POST['_config_settings_hook'] ) && $_POST['_config_settings_hook'] == 'save_config' ) {
			$this->_handle_save_settings_hook();
		}

		// try to set our memory limit.
		$desired_limit_r = module_config::c( 'php_memory_limit', '64M' );
		$desired_limit   = trim( $desired_limit_r );
		$last            = $desired_limit[ strlen( $desired_limit ) - 1 ];
		$desired_limit   = rtrim( $desired_limit_r, $last );
		$last = strtolower($last);
		switch ( $last ) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$desired_limit *= 1024;
			case 'm':
				$desired_limit *= 1024;
			case 'k':
				$desired_limit *= 1024;
		}

		$memory_limit = ini_get( 'memory_limit' );
		$val          = trim( $memory_limit );
		$last         = $val[ strlen( $val ) - 1 ];
		$val = rtrim($val, $last);
		$last = strtolower($last);
		switch ( $last ) {
			// The 'G' modifier is available since PHP 5.1.0
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}


		if ( ! $memory_limit || $val < $desired_limit ) {
			// try to increase to 64M
			if ( ! _DEMO_MODE ) {
				@ini_set( 'memory_limit', $desired_limit_r );
			}
		}

		/*
						// try to set our post_max_size limit.
						$desired_limit_r = module_config::c('php_post_max_size','10M');
						$desired_limit = trim($desired_limit_r);
						$last = strtolower($desired_limit[strlen($desired_limit)-1]);
						switch($last) {
								// The 'G' modifier is available since PHP 5.1.0
								case 'g':
										$desired_limit *= 1024;
								case 'm':
										$desired_limit *= 1024;
								case 'k':
										$desired_limit *= 1024;
						}

						$post_max_size_limit = ini_get('post_max_size');
						$val = trim($post_max_size_limit);
						$last = strtolower($val[strlen($val)-1]);
						switch($last) {
								// The 'G' modifier is available since PHP 5.1.0
								case 'g':
										$val *= 1024;
								case 'm':
										$val *= 1024;
								case 'k':
										$val *= 1024;
						}


						if(!$post_max_size_limit || $val < $desired_limit){
								// try to increase to 64M
								if(!_DEMO_MODE){
										@ini_set('post_max_size',$desired_limit_r);
								}
						}*/

		self::register_js( 'config', 'settings.js' );

	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"      => "Settings",
				"p"         => "config_admin",
				"order"     => 99,
				'icon_name' => 'cogs',
			);
		}

	}


	public function handle_hook( $hook, $mod = false ) {
		switch ( $hook ) {
			case "home_alerts":
				$alerts = array();
				// check if the cron job hasn'e run in a certian amount of time.

				if ( module_config::can_i( 'view', 'Upgrade System' ) ) {
					$last_update = module_config::c( 'last_update', time() );
					// prompt them to do an update every 7 days
					if ( $last_update < ( time() - 604800 ) ) {
						$alert_res = process_alert( date( 'Y-m-d' ), _l( 'Please check for Software Updates' ) );
						if ( $alert_res ) {
							$alert_res['link'] = $this->link_generate( false, array( 'page' => 'config_upgrade' ) );
							$alert_res['name'] = _l( 'Please go to Settings > Upgrade and check for latest Software Updates.' );
							$alerts[]          = $alert_res;
						}
					}
				}
				if ( module_config::can_i( 'view', 'Settings' ) ) {
					$last_cron_run = module_config::c( 'cron_last_run', 0 );
					if ( $last_cron_run < ( time() - 86400 ) && ! _DEMO_MODE ) {
						$alert_res = process_alert( date( 'Y-m-d' ), _l( 'CRON Job Not Setup' ) );
						if ( $alert_res ) {
							$alert_res['link'] = $this->link_generate( false, array( 'page' => 'config_cron' ) );
							$alert_res['name'] = _l( 'Has not run since: %s', ( $last_cron_run > 0 ? print_date( $last_cron_run ) : _l( 'Never' ) ) );
							$alerts[]          = $alert_res;
						}
					}

					// check our memory limit.
					if ( class_exists( 'module_pdf', false ) ) {
						$desired_limit_r = module_config::c( 'php_memory_limit', '64M' );
						$desired_limit   = trim( $desired_limit_r );
						$last            = strtolower( $desired_limit[ strlen( $desired_limit ) - 1 ] );
						switch ( $last ) {
							// The 'G' modifier is available since PHP 5.1.0
							case 'g':
								$desired_limit *= 1024;
							case 'm':
								$desired_limit *= 1024;
							case 'k':
								$desired_limit *= 1024;
						}

						$memory_limit = ini_get( 'memory_limit' );
						$val          = trim( $memory_limit );
						$last         = strtolower( $val[ strlen( $val ) - 1 ] );
						switch ( $last ) {
							// The 'G' modifier is available since PHP 5.1.0
							case 'g':
								$val *= 1024;
							case 'm':
								$val *= 1024;
							case 'k':
								$val *= 1024;
						}


						if ( ! $memory_limit || $val < $desired_limit || $val < 67108864 ) {
							$alert_res = process_alert( date( 'Y-m-d' ), _l( 'PDF Memory Limit Low' ) );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_generate( false, array( 'page' => 'config_settings' ) );
								$alert_res['name'] = _l( 'php_memory_limit should be 64M or above: %s', $memory_limit );
								$alerts[]          = $alert_res;
							}
						}
					}

					/*$desired_limit_r = module_config::c('php_post_max_size','10M');
					$desired_limit = trim($desired_limit_r);
					$last = strtolower($desired_limit[strlen($desired_limit)-1]);
					switch($last) {
							// The 'G' modifier is available since PHP 5.1.0
							case 'g':
									$desired_limit *= 1024;
							case 'm':
									$desired_limit *= 1024;
							case 'k':
									$desired_limit *= 1024;
					}

					$memory_limit = ini_get('post_max_size');
					$val = trim($memory_limit);
					$last = strtolower($val[strlen($val)-1]);
					switch($last) {
							// The 'G' modifier is available since PHP 5.1.0
							case 'g':
									$val *= 1024;
							case 'm':
									$val *= 1024;
							case 'k':
									$val *= 1024;
					}


					if(!strlen($memory_limit) || $val < $desired_limit || $val < 10485760){
							$alert_res = process_alert(date('Y-m-d'), _l('CSV Import Limit Too Low'));
							if($alert_res){
									$alert_res['link'] = $this->link_generate(false,array('page'=>'config_settings'));
									$alert_res['name'] = _l('php_post_max_size should be %s or above: %s',$desired_limit_r. ' ('.$desired_limit.')',$memory_limit. " ($val)");
									$alerts[] = $alert_res;
							}
					}*/
				}

				return $alerts;
				break;
		}
	}

	public function link_generate( $config_id = false, $options = array(), $link_options = array() ) {

		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.

		// we check if we're bubbling from a sub link, and find the item id from a sub link
		if ( $config_id === false && $link_options ) {
			$key = 'config_id';
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
		}
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)
		$data            = array();
		$options['data'] = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['part_number'] ) || ! trim( $data['part_number'] ) ) ? 'N/A' : $data['part_number'];
		// generate the arguments for this link
		$options['arguments'] = array(
			'config_id' => $config_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = ( isset( $options['page'] ) ) ? $options['page'] : 'config_admin';
		$options['module'] = $this->module_name;
		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		if ( isset( $options['bubble_to_module'] ) ) {
			$bubble_to_module = $options['bubble_to_module'];
		}

		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, $bubble_to_module, $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );
		}
	}


	public function process() {
		if ( 'save_config' == $_REQUEST['_process'] ) {
			$count = $this->handle_post_save_config();
			set_message( $count . ' configuration values saved successfully' );
			redirect_browser( $_SERVER['REQUEST_URI'] );
		}
	}

	public static function save_config( $key, $val ) {

		if ( _DEMO_MODE ) {
			// dont save particular values
			switch ( $key ) {
				case 'system_base_dir':
				case 'system_base_href':
				case 'php_memory_limit':
				case 'force_ssl':
					set_error( 'Changing some settings is disabled in DEMO mode.' );

					return $val;
				default:
					if (
						strpos( $key, 'license' ) !== false ||
						strpos( $key, 'licence' ) !== false
					) {
						set_error( 'Changing some settings is disabled in DEMO mode.' );

						return $val;
					}
					/*if(
                        strpos($key,'plugin_enabled') !== false ||
                        strpos($key,'table_sort') !== false ||
                        strpos($key,'menu_order') !== false ||
                        strpos($key,'leads_enabled') !== false ||
                        strpos($key,'pin_show_in_menu') !== false ||
                        strpos($key,'timer_enabled') !== false ||
                        strpos($key,'header_title') !== false ||
                        strpos($key,'header_title') !== false ||
                        strpos($key,'theme_name') !== false ||
                        strpos($key,'admin_system_name') !== false ||
                        strpos($key,'default_language') !== false ||
                        strpos($key,'_theme') !== false
					){*/
					// save some settings into the _SESSION variable for demo mode
					//if(!isset($_SESSION['_demo_config']))$_SESSION['_demo_config']=array();
					//$_SESSION['_demo_config'][$key] = $val;
					//self::$config_vars[$key] = $val;
					//return $val;
					//}
					break;
			}
		}

		$sql = "SELECT * FROM `" . _DB_PREFIX . "config` c ";
		$sql .= " WHERE `key` = '" . db_escape( $key ) . "'";
		$res = qa1( $sql );
		if ( ! $res ) {
			$sql = "INSERT INTO `" . _DB_PREFIX . "config` SET `key` = '" . db_escape( $key ) . "', `val` = '" . db_escape( $val ) . "'";
			query( $sql );
		} else {

			// a default for this key exists already, we give the option of updating the company config here

			if ( class_exists( 'module_company', false ) && module_company::is_enabled() ) {
				// pass setting saving over to company module for now
				// if company module returns true we don't save it below
				if ( module_company::save_company_config( $key, $val ) ) {
					// saved in company module, don't save in defaults below
					self::$config_vars[ $key ] = $val;

					return true;
				}
			}
			$sql = "UPDATE `" . _DB_PREFIX . "config` SET `val` = '" . db_escape( $val ) . "' WHERE `key` = '" . db_escape( $key ) . "' LIMIT 1";
			query( $sql );
		}
		self::$config_vars[ $key ] = $val;
	}

	public function handle_post_save_config() {

		if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
			die( "Permission denied to Edit 'Config &raquo; Settings'. Please ask Administrator to adjust settings." );
		}
		$x = 0;

		if ( isset( $_POST['config'] ) && is_array( $_POST['config'] ) ) {
			foreach ( $_POST['config'] as $key => $val ) {
				$this->save_config( $key, $val );
				$x ++;
			}
		}

		return $x;
	}

	public static function get_setting( $key ) {
		$val = get_single( 'config', 'key', $key );

		return ( isset( $val['val'] ) ) ? $val['val'] : false;
	}


	private static function _init_vars( $only_load_key = false ) {
		if ( self::$config_vars ) {
			return false;
		}
		self::$config_vars = array();
		$sql               = "SELECT `key`,`val` FROM `" . _DB_PREFIX . "config` ";
		foreach ( qa( $sql ) as $c ) {
			self::$config_vars[ $c['key'] ] = $c['val'];
		}
		if ( _DEMO_MODE && isset( $_SESSION['_demo_config'] ) ) {
			foreach ( $_SESSION['_demo_config'] as $key => $val ) {
				self::$config_vars[ $key ] = $val;
			}
		}
		if ( function_exists( 'hook_handle_callback' ) ) {
			// hook into the company module (or any other modules in the future) to modify this if needed
			$new_configs = hook_handle_callback( 'config_init_vars', self::$config_vars );
			// returns a list of new configs from other modules
			if ( is_array( $new_configs ) ) {
				foreach ( $new_configs as $new_config ) {
					if ( is_array( $new_config ) ) {
						self::$config_vars = array_merge( self::$config_vars, $new_config );
					}
				}
			}
		}
	}

	/**
	 * @static returns a setting from the database.
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return mixed|string
	 */
	private static $_c = array();

	public static function c( $key, $default = false, $options = array() ) {

		//        if(!defined('_UCM_INSTALLED'))return $default;
		if ( isset( self::$_c[ $key ] ) ) {
			return false;
		} // init_vars and save_config can sometimes cause a loop
		self::$_c[ $key ] = true;
		// check config table exists.
		if ( ! defined( '_UCM_INSTALLED' ) || ! _UCM_INSTALLED ) {
			if ( _DB_USER && _DB_NAME ) {
				db_connect();
				$sql = "SHOW TABLES LIKE '" . _DB_PREFIX . "config'";
				$res = qa1( $sql );
			} else {
				$res = array();
			}
			if ( $res != false && count( $res ) ) {
				// config table exists, we're right to query
			} else {
				unset( self::$_c[ $key ] );

				return $default;
			}
		}
		// special keys, we only load once.
		switch ( $key ) {
			case 'sessions_in_database':
			case 'database_utf8':
				$sql = "SELECT `key`,`val` FROM `" . _DB_PREFIX . "config` WHERE `key` = '" . db_escape( $key ) . "'";
				$res = qa1( $sql );
				if ( $res && $res['key'] == $key ) {
					return $res['val'];
				} else if ( $default !== false ) {
					self::save_config( $key, $default );
				}
				break;
			default:
				// load all vars if needed.
				self::_init_vars();
		}

		if ( ! isset( self::$config_vars[ $key ] ) && $default !== false ) {
			self::save_config( $key, $default );
			/*$sql = "INSERT INTO `"._DB_PREFIX."config` SET `key` = '".db_escape($key)."', `val` = '".db_escape($default)."'";
			query($sql);
			self::$config_vars[$key] = $default;*/
		}
		unset( self::$_c[ $key ] );

		return isset( self::$config_vars[ $key ] ) ? self::$config_vars[ $key ] : false;
	}

	/**
	 * @static Returns a translated string from a database call
	 *
	 * @param      $key
	 * @param bool $default
	 *
	 * @return mixed|string
	 */
	public static function s( $key, $default = false ) {
		return _l( self::c( $key, $default ) );
	}

	private static $css_files = array();

	public static function register_css( $module, $file_name, $url = true, $position = 10 ) {
		self::$css_files[ $module . $file_name ] = array( $module, $file_name, $url, $position );
	}

	public static function print_css( $version = false ) {
		// sort the css files by position
		uasort( self::$css_files, 'config_sort_css' );
		if ( module_config::c( 'css_combine', 1 ) ) {
			self::css_combine( $version );
			uasort( self::$css_files, 'config_sort_css' );
		}
		foreach ( self::$css_files as $hash => $css_file_info ) {
			if ( strlen( $css_file_info[2] ) < 3 ) { // url is set to 'true', use the module/file name combo
				?>
				<link rel="stylesheet"
				      href="<?php echo _BASE_HREF; ?>includes/plugin_<?php echo $css_file_info[0]; ?>/css/<?php echo $css_file_info[1];
				      echo ( $version && strpos( '?', $css_file_info[1] ) === false ) ? '?ver=' . $version : ''; ?>"
				      type="text/css"> <?php

			} else { ?>
				<link rel="stylesheet" href="<?php echo htmlspecialchars( $css_file_info[2] ); ?>" type="text/css"> <?php
			}
		}
		if ( function_exists( 'hook_handle_callback' ) ) {
			hook_handle_callback( 'header_print_css' );
		}
	}

	public static function css_combine( $version, $reinit = false ) {
		// combine and cache the css files into a single file.
		$cache_name = 'cache-' . md5( serialize( self::$css_files ) . $version ) . '.css';
		$cache_file = _UCM_FILE_STORAGE_DIR . 'temp/' . $cache_name;
		if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'temp/' ) ) {
			mkdir( _UCM_FILE_STORAGE_DIR . 'temp/' );
		}
		if ( is_file( $cache_file ) && filesize( $cache_file ) > 10 && module_config::c( 'css_cached_version', $version ) == $version ) {
			// it's already combined, print this one out and discard the rest.
			self::register_css( false, false, _UCM_FILE_STORAGE_HREF . 'temp/' . $cache_name );
			foreach ( self::$css_files as $hash => $css_file_info ) {
				if ( strlen( $css_file_info[2] ) < 3 ) {
					// this file is already cached in the above cache file.
					unset( self::$css_files[ $hash ] );
				}
			}
		} else if ( ! $reinit ) {
			// time to generate a new cache file based on these css files.
			$css_content = '';
			foreach ( self::$css_files as $hash => $css_file_info ) {
				if ( strlen( $css_file_info[2] ) < 3 ) {
					if ( is_file( 'includes/plugin_' . basename( $css_file_info[0] ) . '/css/' . basename( $css_file_info[1] ) ) ) {
						$css = file_get_contents( 'includes/plugin_' . basename( $css_file_info[0] ) . '/css/' . basename( $css_file_info[1] ) );
						if ( strpos( $css, 'url' ) ) {
							$css = preg_replace(
								'/url\((.+)\)/i',
								'url(' . _BASE_HREF . 'includes/plugin_' . basename( $css_file_info[0] ) . '/css/$1)',
								$css
							);
						}
						$css_content .= $css;
					}
				}
			}
			if ( strlen( $css_content ) > 10 ) {
				file_put_contents( $cache_file, $css_content );
				module_config::save_config( 'css_cached_version', $version );
				// remove other older css files.
				$old_files = @glob( _UCM_FILE_STORAGE_DIR . 'temp/cache-*.css' );
				if ( is_array( $old_files ) ) {
					foreach ( $old_files as $old_file ) {
						if ( is_file( $old_file ) && filemtime( $old_file ) < time() - 172800 ) { // 2 days
							@unlink( $old_file );
						}
					}
				}

			}
			self::css_combine( $version, true ); // load the cache file again now that it has been generated.
		}
	}

	private static $js_files = array();

	public static function register_js( $module, $file_name, $url = true, $position = 10 ) {
		self::$js_files[ $module . $file_name ] = array( $module, $file_name, $url, $position );
	}

	public static function print_js( $version = false ) {
		// sort the js files by position
		uasort( self::$js_files, 'config_sort_css' ); // use the css function for sorting, does the same thing.
		if ( module_config::c( 'js_combine', 1 ) ) {
			self::js_combine( $version );
			uasort( self::$js_files, 'config_sort_css' ); // use the css function for sorting, does the same thing.
		}
		foreach ( self::$js_files as $hash => $js_file_info ) {
			if ( strlen( $js_file_info[2] ) < 3 ) { // url is set to 'true', use the module/file name combo
				// see if there is a theme override for this js file.
				$js_file = 'includes/plugin_' . basename( $js_file_info[0] ) . '/js/' . basename( $js_file_info[1] );
				// see if we have an override.
				$js_file = module_theme::include_ucm( $js_file );
				?>
				<script type="text/javascript" language="javascript" src="<?php echo _BASE_HREF . $js_file;
				echo ( $version && strpos( '?', $js_file[1] ) === false ) ? '?ver=' . $version : ''; ?>"></script> <?php
			} else { ?>
				<script type="text/javascript" language="javascript"
				        src="<?php echo htmlspecialchars( $js_file_info[2] ); ?>"></script> <?php
			}
		}
		if ( function_exists( 'hook_handle_callback' ) ) {
			hook_handle_callback( 'header_print_js' );
		}
	}

	public static function js_combine( $version, $reinit = false ) {
		// combine and cache the js files into a single file.
		foreach ( self::$js_files as $hash => $js_file_info ) {
			if ( strlen( $js_file_info[2] ) < 3 ) {
				$js_file = 'includes/plugin_' . basename( $js_file_info[0] ) . '/js/' . basename( $js_file_info[1] );
				// see if we have an override.
				self::$js_files[ $hash ]['file_to_load'] = module_theme::include_ucm( $js_file );
			}
		}

		$cache_name = 'cache-' . md5( serialize( self::$js_files ) . $version ) . '.js';
		$cache_file = _UCM_FILE_STORAGE_DIR . 'temp/' . $cache_name;
		if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'temp/' ) ) {
			mkdir( _UCM_FILE_STORAGE_DIR . 'temp/' );
		}
		if ( is_file( $cache_file ) && filesize( $cache_file ) > 10 && module_config::c( 'js_cached_version', $version ) == $version ) {
			// it's already combined, print this one out and discard the rest.
			self::register_js( false, false, _UCM_FILE_STORAGE_HREF . 'temp/' . $cache_name );
			foreach ( self::$js_files as $hash => $js_file_info ) {
				if ( strlen( $js_file_info[2] ) < 3 ) {
					// this file is already cached in the above cache file.
					unset( self::$js_files[ $hash ] );
				}
			}
		} else if ( ! $reinit ) {
			// time to generate a new cache file based on these js files.
			$js_content = '';
			foreach ( self::$js_files as $hash => $js_file_info ) {
				if ( isset( $js_file_info['file_to_load'] ) && $js_file_info['file_to_load'] ) {
					if ( is_file( $js_file_info['file_to_load'] ) ) {
						$js_content .= "\n";
						$js_content .= " // " . $js_file_info['file_to_load'] . "\n\n";
						$js_content .= file_get_contents( $js_file_info['file_to_load'] );
						$js_content .= "\n";
					}
				}
			}
			if ( strlen( $js_content ) > 10 ) {
				file_put_contents( $cache_file, $js_content );
				module_config::save_config( 'js_cached_version', $version );
				// remove other older css files.
				$old_files = @glob( _UCM_FILE_STORAGE_DIR . 'temp/cache-*.js' );
				if ( is_array( $old_files ) ) {
					foreach ( $old_files as $old_file ) {
						if ( is_file( $old_file ) && filemtime( $old_file ) < time() - 172800 ) { // 2 days
							@unlink( $old_file );
						}
					}
				}
			}
			self::js_combine( $version, true ); // load the cache file again now that it has been generated.
		}
	}

	/*public static function register_js($module, $file_name, $url=true) {
			self::$js_files[$module][$file_name] = $url;
	}
	public static function print_js($version=false) {

		if(module_config::c('js_combine',1)){
				self::js_combine($version);
			}
			foreach(self::$js_files as $module=>$file_names){
					foreach($file_names as $file_name => $url){
							if($url === true){
							?> <script type="text/javascript" language="javascript" src="<?php echo _BASE_HREF;?>includes/plugin_<?php echo $module;?>/js/<?php echo $file_name; echo ($version && strpos('?',$file_name)===false) ? '?ver='.$version : '';?>"></script> <?php
							}else{
							?>
							<script type="text/javascript" language="javascript" src="<?php echo htmlspecialchars($url);?>"></script> <?php
							}
					}
			}
			if(function_exists('hook_handle_callback')){
					hook_handle_callback('header_print_js');
			}
	}
public static function js_combine($version, $reinit=false){
	// combine and cache the js files into a single file.
	$cache_name = 'cache-'.md5(serialize(self::$js_files)).'.js';
	$cache_file = _UCM_FOLDER . 'temp/' . $cache_name;
	if(is_file($cache_file) && filesize($cache_file) > 10 && module_config::c('js_cached_version',$version) == $version){
		// it's already combined, print this one out and discard the rest.
		self::register_js(false,false,_BASE_HREF . 'temp/' . $cache_name);
		foreach(self::$js_files as $module=>$file_names){
						foreach($file_names as $file_name => $url){
								if($url === true){
					// this file is already cached in the above cache file.
					unset(self::$js_files[$module][$file_name]);
				}
			}
		}
	}else if(!$reinit){
		// time to generate a new cache file based on these js files.
		$js_content = '';
		foreach(self::$js_files as $module=>$file_names){
						foreach($file_names as $file_name => $url){
								if($url === true){
					if(is_file('includes/plugin_'.basename($module).'/js/'.basename($file_name))){
						$js_content .= file_get_contents('includes/plugin_'.basename($module).'/js/'.basename($file_name)) . "\n\n";
					}
				}
			}
		}
		if(strlen($js_content)>10){
			file_put_contents($cache_file,$js_content);
			module_config::save_config('js_cached_version',$version);
		}
		self::js_combine($version,true); // load the cache file again now that it has been generated.
	}
}*/

	public static function print_settings_form( $settings ) {
		include( 'pages/settings_form.php' );
	}

	private function _handle_save_settings_hook() {

		if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
			die( "Permission denied to Edit 'Config &raquo; Settings'. Please ask Administrator to adjust settings." );
		}

		if ( ! module_form::check_secure_key() ) {
			die( 'Failed' );
		}

		$config          = isset( $_REQUEST['config'] ) && is_array( $_REQUEST['config'] ) ? $_REQUEST['config'] : array();
		$config_defaults = isset( $_REQUEST['default_config'] ) && is_array( $_REQUEST['default_config'] ) ? $_REQUEST['default_config'] : array();
		foreach ( $config_defaults as $key => $val ) {
			if ( ! isset( $config[ $key ] ) ) {
				$config[ $key ] = ''; // the checkbox has been unticked, save a blank option.
			}
		}
		foreach ( $config as $key => $val ) {
			$this->save_config( $key, $val );
		}
		set_message( 'Configuration saved successfully' );
		redirect_browser( $_SERVER['REQUEST_URI'] );
	}

	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE  `<?php echo _DB_PREFIX; ?>config` (
		`key` VARCHAR( 255 ) NOT NULL ,
		`val` TEXT NOT NULL ,
		PRIMARY KEY (  `key` )
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		INSERT INTO `<?php echo _DB_PREFIX; ?>config` SET `key` = '_installation_code', `val` = 'UCM_LICENCE_CODES_GO_HERE';
		<?php /***DEFAULT EMAIL SETTINGS GO HERE**/ ?>

		<?php
		return ob_get_clean();
	}

	public static function get_currency( $currency_id ) {
		return get_single( 'currency', 'currency_id', $currency_id );
	}

	public static function download_update( $update_name ) {

		$update = self::check_for_upgrades( $update_name, true );

		return $update;

	}

	public static function check_for_upgrades( $requested_plugin = '', $get_file_contents = 0 ) {

		// compile a list of current plugins
		// along with the users installation code
		// send it to our server and get a response with a list of available updates for this user.

		$current_plugins = array();
		$current_files   = array();
		global $plugins;


		if ( _DEBUG_MODE ) {
			module_debug::log( array(
				'title' => 'Checking for upgrades:',
				'data'  => 'start',
			) );
		}
		foreach ( $plugins as $plugin_name => $p ) {
			if ( $requested_plugin && $requested_plugin != $plugin_name ) {
				continue;
			}
			if ( ! $p->is_plugin_enabled() ) {
				$p->init();
			}
			$current_plugins[ $plugin_name ] = $p->get_plugin_version();
			// find all the files related to this plugin.
			if ( function_exists( 'getFilesFromDir' ) && module_config::c( 'upgrade_post_file_list', 1 ) ) {
				$directory = 'includes/plugin_' . $plugin_name . '/';
				$files     = getFilesFromDir( $directory );
				$files     = array_flip( $files );
				foreach ( $files as $file => $tf ) {
					// ignore certain files.
					if (
						strpos( $file, 'plugin_file/upload' ) !== false ||
						strpos( $file, 'plugin_data/upload' ) !== false ||
						strpos( $file, '/cache/' ) !== false ||
						strpos( $file, '/html2ps/' ) !== false ||
						strpos( $file, 'backup/backups/backup_' ) !== false ||
						strpos( $file, '/attachments/' ) !== false ||
						strpos( $file, '/temp/' ) !== false ||
						strpos( $file, '/tmp/' ) !== false
					) {
						unset( $files[ $file ] );
					} else {
						$d              = preg_replace( '#Envato:[^\r\n]*#', '', preg_replace( '#Package Date:[^\r\n]*#', '', preg_replace( '#IP Address:[^\r\n]*#', '', preg_replace( '#Licence:[^\r\n]*#', '', file_get_contents( $file ) ) ) ) );
						$files[ $file ] = md5( base64_encode( $d ) );
					}
				}
				$current_files[ $plugin_name ] = $files;
			}
			if ( _DEBUG_MODE ) {
				module_debug::log( array(
					'title' => 'Checking for upgrades:',
					'data'  => $plugin_name . ' done',
				) );
			}
		}
		//print_r($current_files);exit;

		$available_updates = array();

		$post_fields = array(
			'application'           => _APPLICATION_ID,
			'installation_code'     => module_config::c( '_installation_code' ),
			'current_version'       => module_config::c( '_admin_system_version', 2.1 ),
			'current_plugins'       => json_encode( $current_plugins ),
			'current_files'         => json_encode( $current_files ),
			'client_ip'             => $_SERVER['REMOTE_ADDR'],
			'installation_location' => full_link( '/' ),
			'requested_plugin'      => $requested_plugin,
			'get_file_contents'     => $get_file_contents,
		);
		$url         = module_config::c( 'ucm_upgrade_url', 'http://api.ultimateclientmanager.com/upgrade.php' );
		if ( $url == 'http://ultimateclientmanager.com/api/upgrade.php' ) {
			$url = 'http://api.ultimateclientmanager.com/upgrade.php'; // hack to use new update subdomain
		}
		if ( $url != 'http://ultimateclientmanager.com/api/upgrade.php' && $url != 'http://api.ultimateclientmanager.com/upgrade.php' ) {
			set_error( 'Incorrect API url' );
			redirect_browser( _BASE_HREF );
		}
		if ( _DEBUG_MODE ) {
			module_debug::log( array(
				'title' => 'Checking for upgrades:',
				'data'  => 'Posting to API',
			) );
		}
		if ( ! function_exists( 'curl_init' ) ) {
			$postdata = http_build_query(
				$post_fields
			);
			$opts     = array(
				'http' =>
					array(
						'method'  => 'POST',
						'header'  => 'Content-type: application/x-www-form-urlencoded',
						'content' => $postdata
					)
			);
			$context  = stream_context_create( $opts );
			$result   = file_get_contents( $url, false, $context );
		} else {
			//$url = 'http://localhost/ucm/web/api/upgrade.php';
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_fields );
			curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 ); // fixes netregistr, may break others?
			$result = curl_exec( $ch );
		}
		$data = json_decode( $result, true );
		if ( _DEBUG_MODE ) {
			module_debug::log( array(
				'title' => 'Checking for upgrades:',
				'data'  => 'Received response from API',
			) );
		}

		if ( $data && isset( $data['available_updates'] ) && is_array( $data['available_updates'] ) ) {
			$available_updates = $data['available_updates'];
		}
		if ( $data && isset( $data['licence_codes'] ) && is_array( $data['licence_codes'] ) ) {
			// find out what the licence codes  are (url / name) so we can dispaly this under each code nicely.
			foreach ( $data['licence_codes'] as $code => $foo ) {
				if ( strlen( $code ) > 10 && strlen( $foo ) > 10 ) {
					module_config::save_config( '_licence_code_' . $code, $foo ); // this might not be working
				}
			}
		}

		if ( ! $data ) {
			echo $result;
		}

		//echo '<pre>';print_r($current_plugins);print_r($result);echo '</pre>';

		return $available_updates;

	}


	public static function current_version() {
		return self::c( '_admin_system_version', 2.1 );
	}

	public static function set_system_version( $version ) {
		return self::save_config( '_admin_system_version', $version );
	}


}