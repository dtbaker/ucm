<?php

define( '_LABEL_USER_SPECIFIC', 'User Specific' );

class module_security extends module_base
{
	//test
	public $links;
	public $security_types;

	/**
	 * @var array
	 * if you change these you have to add a corresponding key to the security_role_perm and user_perm table.
	 */
	public static $available_permissions = array(
		'view',
		'edit',
		'create',
		'delete',
	);

	public static $bypass_security = false;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function __construct() {
		$this->module_name     = 'security';
		$this->module_position = 50;
		$this->version         = 2.529;
	}

	/**
	 * @return array
	 */
	public function third_party_libraries() {
		return array(
			'htmlpurifier' => array(
				'name'       => 'HTML Purifier',
				'package'    => 'ezyang/htmlpurifier',
				'constraint' => '^4.19',
				'class'      => 'HTMLPurifier',
				'bundled'    => 'includes/plugin_security/htmlpurifier/HTMLPurifier.standalone.php',
				'feature'    => 'Cleaning up HTML that people paste into the system',
			),
		);
	}

	public function init() {
		// 2.529 - 2026-10-22 - ending a session checks the permission to edit settings
		$this->links          = array();
		$this->security_types = array();
		// 2.528 - 2026-10-17 - 4.0.0 relaunch: shipped default passwords must be changed, auto-login links fixed, role saves fixed
		// 2.527 - 2021-04-07 - php8 compatibility fix
		// 2.526 - 2017-05-02 - file path configuration
		// 2.525 - 2017-02-16 - js error fix
		// 2.524 - 2017-01-12 - edit form fields based on user role
		// 2.523 - 2016-09-12 - merged subscription invoices
		// 2.522 - 2016-07-10 - big update to mysqli
		// 2.521 - 2016-05-26 - login hooks
		// 2.520 - 2016-02-02 - login fix
		// 2.519 - 2016-01-30 - help icon fix
		// 2.518 - 2015-12-28 - group based permissions
		// 2.517 - 2015-11-03 - super admin check
		// 2.516 - 2015-03-17 - new staff role default
		// 2.515 - 2015-01-19 - db speed improvement
		// 2.514 - 2015-01-08 - permission speed up
		// 2.513 - 2014-12-27 - reseller permission defaults
		// 2.512 - 2014-12-22 - auto login link over ssl
		// 2.511 - 2014-08-06 - responsive improvements
		// 2.51 - 2014-07-02 - file permission/alert improvements
		// 2.5 - 2014-04-01 - html purify improvement
		// 2.499 - 2014-02-19 - forgot password fix for customer contacts
		// 2.498 - 2014-02-17 - added password_reset_debug config
		// 2.497 - 2013-11-24 - staff member defaults
		// 2.496 - 2013-11-19 - delete security role
		// 2.495 - 2013-11-15 - working on new UI
		// 2.494 - 2013-08-30 - speed/memory improvement
		// 2.493 - 2013-04-20 - added first default user role - Customer


		// 2.41 - public $process_editable_page so we can set it from contact edit
		// 2.42 - better login with linked accounts.
		// 2.43 - display user count under selected role
		// 2.44 - correct can_i check ('Security Roles','Security') instead of ('Security','Config')
		// 2.45 - bug fix for
		// 2.46 - increased length of auto login string. as config variable.
		// 2.47 - output buffering bug fix
		// 2.471 - remove HELP popups on non-editable pages.
		// 2.48 - forgot password
		// 2.481 - hashed passwords
		// 2.482 - fix for cron running with hashed passwords
		// 2.483 - option to force SSL upon login
		// 2.484 - php6 fix
		// 2.485 - link fix
		// 2.486 - big update, html purification for safer/prettier ticket/email display
		// 2.487 - 2013-04-10 - new customer permissions
		// 2.488 - 2013-04-10 - new customer permissions
		// 2.489 - 2013-04-10 - new customer permissions
		// 2.49 - 2013-04-11 - error message on invalid login
		// 2.491 - 2013-04-11 - database sessions, login history and user session ending.
		// 2.492 - 2013-04-12 - auto login improvement

		//self::can_user_login(1);

	}

	public function pre_menu() {

		if ( module_security::has_feature_access( array(
				'name'        => 'Settings',
				'module'      => 'config',
				'category'    => 'Config',
				'view'        => 1,
				'description' => 'view',
			) ) && $this->can_i( 'view', 'Security Roles', 'Security' ) ) {
			$this->links[] = array(
				"name"                => "User Roles",
				"p"                   => "security_role",
				"args"                => array( 'security_role_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'order'               => 4,
			);
			$this->links[] = array(
				"name"                => "Login History",
				"p"                   => "login_history",
				"args"                => array( 'security_role_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'order'               => 40,
			);
		}

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'password_reset', 'Dear {NAME},<br>
    <br>
    Please click the link below to login and reset your password.<br><br>
    <a href="{AUTO_LOGIN_URL}">CLICK HERE</a> <br/><br/>
    Thank you,<br><br>
    {FROM_NAME}
    ', 'Reset your password', array(
				'NAME' => 'Users Name',
			) );
		}
	}

	public static function link_generate( $task_id = false, $options = array(), $link_options = array() ) {

		$key = 'task_id';


		if ( $task_id === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}
		$bubble_to_module = isset( $options['bubble_to_module'] ) ? $options['bubble_to_module'] : false;
		$options['page']  = ( isset( $options['page'] ) ) ? $options['page'] : 'task_admin';

		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['task_id'] = $task_id;

		$options['module'] = 'security';
		if ( $task_id ) {
			$data = self::get_security_role( $task_id );
		} else {
			$data = array();
		}
		$options['data'] = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;
			if ( isset( $plugins[ $bubble_to_module['module'] ] ) ) {
				return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
			}
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );
		}
	}


	public static function link_open_role( $security_role_id, $full = false ) {
		return self::link_generate( $security_role_id, array(
			'page'             => 'security_role',
			'bubble_to_module' => array(
				'module' => 'config',
			),
			'arguments'        => array(
				'security_role_id' => $security_role_id,
			),
			'full'             => $full,
		) );
	}

	public static function get_user_features( $access_requirements ) {
		$user_features = array();
		if ( isset( $access_requirements['check_features'] ) && is_array( $access_requirements['check_features'] ) ) {
			foreach ( $access_requirements['check_features'] as $feature ) {
				$access_requirements['feature'] = $feature;
				if ( self::has_feature_access( $access_requirements ) ) {
					$user_features[] = $feature;
				}
			}
		}

		return $user_features;
	}

	private static $user_perms = array();
	public static $perms_cache = array();

	public static function get_user_permissions( $user_id ) {
		$user_id = (int) $user_id;
		if ( isset( self::$user_perms[ $user_id ] ) ) {
			return self::$user_perms[ $user_id ];
		}
		self::$user_perms[ $user_id ] = array();
		// check if this user has a role or not.

		$sql                          = "SELECT sp.*, sp.security_permission_id AS id FROM `" . _DB_PREFIX . "security_role_perm` sp LEFT JOIN `" . _DB_PREFIX . "user_role` ur USING (security_role_id) WHERE ur.user_id = $user_id";
		self::$user_perms[ $user_id ] = qa( $sql );

		/*if(!count(self::$user_perms[$user_id])){
				// no role set - just use hardcoded perms on the user account.
				$sql = "SELECT *, security_permission_id AS id FROM `"._DB_PREFIX."user_perm` WHERE user_id = $user_id";
				self::$user_perms[$user_id] = qa($sql);
		}*/

		return self::$user_perms[ $user_id ];
	}

	public static function has_feature_access( $access_requirements, $user_id = false ) {

		if ( ! $user_id ) {
			$user_id = self::get_loggedin_id();
		}

		if ( ! $user_id ) {
			// not logged in, dont allow anything.
			return false;
		}

		// load all perms to start with
		if ( ! self::$perms_cache ) {
			self::_build_perms_cache();
		}


		// check if this permission exists within the database.
		if ( isset( $access_requirements['description'] ) ) {
			// hack while we're developing this.
			$security_permission_id = false;
			foreach ( self::$perms_cache as $ps ) {
				if ( $ps['name'] == $access_requirements['name'] && $ps['category'] == $access_requirements['category'] && $ps['module'] == $access_requirements['module'] ) {
					$security_permission_id = $ps['security_permission_id'];
					$available_perms        = @unserialize( $ps['available_perms'] );
					if ( ! is_array( $available_perms ) ) {
						$available_perms = array();
					}
				}
			}
			if ( ! $security_permission_id ) {
				// The cache is per request, so a permission first reached by two requests
				// at once is missing from both and inserted twice. That is how this table
				// came to hold four rows for some names: the browser suite runs four
				// workers, and they all registered the same new data type in the same
				// second. Look again in the database before adding one.
				$existing = get_single( 'security_permission', array(
					'name',
					'category',
					'module',
				), array(
					$access_requirements['name'],
					$access_requirements['category'],
					$access_requirements['module'],
				) );
				if ( $existing ) {
					$security_permission_id = $existing['security_permission_id'];
					$available_perms        = @unserialize( $existing['available_perms'] );
					if ( ! is_array( $available_perms ) ) {
						$available_perms = array();
					}
				} else {
					$security_permission_id = update_insert( 'security_permission_id', 'new', 'security_permission', array(
						'name'        => $access_requirements['name'],
						'category'    => $access_requirements['category'],
						'module'      => $access_requirements['module'],
						'description' => $access_requirements['description'],
					) );
					$available_perms = array();
				}
				self::_build_perms_cache();
			}/*
			$permission = get_single('security_permission',array(
				'name',
				'category',
				'module',
			),array(
				$access_requirements['name'],
				$access_requirements['category'],
				$access_requirements['module'],
			));
			if($permission){
				$security_permission_id = $permission['security_permission_id'];
				// check available perms
				$available_perms = @unserialize($permission['available_perms']);
				if(!is_array($available_perms)){
					$available_perms = array();
				}
			}else{
				// create this permission.
				$security_permission_id = update_insert('security_permission_id','new','security_permission',array(
					'name' => $access_requirements['name'],
					'category' => $access_requirements['category'],
					'module' => $access_requirements['module'],
					'description' => $access_requirements['description'],
				));
				$available_perms = array();
			}*/
			$save_perms = false;
			foreach ( self::$available_permissions as $permission ) {
				if ( isset( $access_requirements[ $permission ] ) ) {
					// the script is asking for this available permission.
					// check if it exists in the db as an option
					if ( ! isset( $available_perms[ $permission ] ) ) {
						// time to add it to the db so we can configure this in the future.
						$available_perms[ $permission ] = true;
						$save_perms                     = true;
					}
				}
			}
			if ( $save_perms ) {
				update_insert( 'security_permission_id', $security_permission_id, 'security_permission', array(
					'available_perms' => serialize( $available_perms )
				) );
			}
		}
		/*if(isset($access_requirements['required_access_level']) && self::getlevel() < $access_requirements['required_access_level']){
			return false;
		}*/
		/*if(self::getlevel() == 1){
			// can access any part or feature of the system!
		 	//return true;
		}*/
		$access = false;
		if ( isset( $security_permission_id ) && $security_permission_id ) {
			// check with the database if the current user has these permissions

			if ( self::check_user_permissions( $user_id, $access_requirements, $security_permission_id ) ) {
				$access = true;
			} else {

				// Todo: remove this
				$access = false; // change to false
				//return false;
			}

		} else {
			include( "pages/feature_access.php" );
		}


		// user id 1 has all access.
		if ( $user_id == 1 ) {
			return true;
		}

		return $access;
	}

	public static function check_user_permissions( $user_id, $access_requirements, $security_permission_id = false ) {

		if ( ! $security_permission_id ) {
			$permission = get_single( 'security_permission', array(
				'name',
				'category',
				'module',
			), array(
				$access_requirements['name'],
				$access_requirements['category'],
				$access_requirements['module'],
			) );
			if ( $permission ) {
				$security_permission_id = $permission['security_permission_id'];
			}
		}
		$access            = false;
		$users_permissions = self::get_user_permissions( $user_id );
		if ( isset( $users_permissions[ $security_permission_id ] ) ) {
			foreach ( self::$available_permissions as $permission ) {
				if ( isset( $users_permissions[ $security_permission_id ][ $permission ] ) && isset( $access_requirements[ $permission ] ) ) {
					if ( $users_permissions[ $security_permission_id ][ $permission ] == $access_requirements[ $permission ] ) {
						// matching permission!
						$access = true;
					} else {
						// found a permission but it didn't match
						return false;
					}
				}
			}
		}

		return $access;
	}

	/**
	 * @static
	 *
	 * @param $access_requirements
	 *
	 * @return bool
	 *
	 * Example usage:
	 *
	 *
	 *
	 */
	public static function check_page( $access_requirements ) {
		if ( ! isset( $access_requirements['module'] ) || ! $access_requirements['module'] ) {
			$debug = array_shift( debug_backtrace() );
			if ( preg_match( '#plugin_(\w+)/pages#', $debug['file'], $matches ) ) {
				$access_requirements['module'] = $matches[1];
			} else {
				return true; // true - they have access
			}
			// todo: work it out here.
		}
		// find out the file this was called from if it's not specified.
		if ( ! isset( $access_requirements['page_name'] ) || ! $access_requirements['page_name'] ) {
			// use the main module name just like ::can_i
			$access_requirements['page_name'] = ucwords( str_replace( '_', ' ', $access_requirements['module'] ) ) . 's';
		}

		// find out the file this was called from if it's not specified.
		if ( ! isset( $access_requirements['category'] ) || ! $access_requirements['category'] ) {
			// use the main module name just like ::can_i
			$access_requirements['category'] = ucwords( str_replace( '_', ' ', $access_requirements['module'] ) );
		}

		$perms = array(
			'name'        => $access_requirements['page_name'],
			'module'      => $access_requirements['module'],
			'category'    => $access_requirements['category'],
			'description' => 'Permissions',
		);
		foreach ( self::$available_permissions as $foo ) {
			if ( isset( $access_requirements[ $foo ] ) && $access_requirements[ $foo ] ) {
				$perms[ $foo ] = 1;
			}
		}
		if ( isset( $access_requirements['feature'] ) ) {
			$perms[ strtolower( $access_requirements['feature'] ) ] = 1;
		} else {
			//$perms['view'] = 1;
		}
		//isset($perms['edit']) || // for debugging
		if ( ! self::has_feature_access( $perms ) ) {

			module_debug::log( array(
				'title' => 'Page Editable',
				'file'  => 'includes/plugin_security/security.php',
				'data'  => "User doesn't have edit permissions, the following page content will be un-editable.",
			) );
			// if they don't have "edit" permissions, we check "view" permissions
			// we then continue rendering the page but at the end we run a hook to remove all editing capabilities
			// eg: form elements and the link.
			if ( isset( $perms['edit'] ) ) {
				// check again with 'view' permissions
				unset( $perms['edit'] );
				$perms['view'] = 1;
				if ( self::has_feature_access( $perms ) ) {
					self::disallow_page_editing();
					ob_start(); // ready for the end page processing request below ( in ::render_page_finished)

					return false;
				}
			}

			// redirect to warning message if cannot access this page.
			// with option to login again maybe??
			if ( isset( $access_requirements['return'] ) && $access_requirements['return'] ) {
				return false;// not needed because we return false below too?
			}
			$denied_perms = array();
			foreach ( self::$available_permissions as $foo ) {
				if ( isset( $perms[ $foo ] ) && $perms[ $foo ] ) {
					$denied_perms[] = ucwords( $foo );
				}
			}
			ob_start(); // ready for the end page processing request below. ( in ::render_page_finished)
			self::$page_denied         = true;
			self::$page_denied_message = _l( 'Access denied to %s %s (under %s).<br>Please contact your administrator if you would like access.', implode( ', ', $denied_perms ), $access_requirements['page_name'], $access_requirements['category'] );

			return false;
		}

		return true;
	}


	public static function sanatise_data( $table_name, &$data ) {
		return; // skip sanatisation for now.
		if ( is_null( $data ) ) {
			return;
		}
		include( "pages/sanatise.php" );
	}

	// is this user allowed to access this single array of data.
	public static function can_access_data( $table_name, &$data, $data_id = false ) {
		if ( self::$bypass_security ) {
			return true;
		}
		if ( self::is_super_admin() ) {
			return true;
		} // super admin can access everything.
		static $checking_access = array();
		if ( isset( $checking_access[ $table_name ] ) ) {
			return true; // stop nesting loops
		}
		$checking_access[ $table_name ] = true;
		$access                         = false;
		$logged                         = false;
		include( "pages/data_access.php" );
		if ( ! $access ) {
			//print_r($data);
			if ( ! $logged ) {
				module_debug::log( array(
					'title' => 'No Access',
					'file'  => 'includes/plugin_security/security.php',
					'data'  => "Table: $table_name, " . var_export( $data, true ) . ", data_id: $data_id",
				) );
			}
			$data = null;
			//echo 'Failed to access data'. $table_name;
			//exit;
			unset( $checking_access[ $table_name ] );

			return false;
		} else {
			unset( $checking_access[ $table_name ] );

			return true;
		}
	}

	// is this user allowed to access this single array of data.
	public static function filter_data_set( $table_name, &$data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $data_id => &$data_row ) {
				if ( self::can_access_data( $table_name, $data_row, $data_id ) ) {
					if ( is_array( $data_row ) ) {
						self::sanatise_data( $table_name, $data_row );
					}
				} else {
					unset( $data[ $data_id ] );
				}
			}
		}
	}


	public static function is_role( $role, $user_id = false ) {
		if ( ! self::getcred() ) {
			return false;
		}
		if ( ! $user_id ) {
			$user_id = self::get_loggedin_id();
		}
		module_debug::log( array(
			'title' => 'DEPRECATED',
			'file'  => 'includes/plugin_security/security.php',
			'data'  => 'Called is_role() - use can_user() for more fine tuning!!',
		) );

		return self::can_user( $user_id, 'User Role: ' . $role );
		/*return module_security::has_feature_access(array(
										'category' => _LABEL_USER_SPECIFIC,
										'name' => 'User Role: '.$role,
										'module' => 'config',
										'view' => 1,
										'description' => 'access',
								),$user_id);*/

		/*switch($role){
				case 'admin':
						return self::is_admin();
				case 'support':
						return self::is_support();
				case 'contact':
						return self::is_contact();
				default:
						// todo: allow modules to define their own role and configure it here.
						return false;
		}
		return false;*/
	}

	public static function is_admin() {
		module_debug::log( array(
			'title' => 'DEPRECATED',
			'file'  => 'includes/plugin_security/security.php',
			'data'  => 'Called is_admin()',
		) );

		return ( self::is_role( 'admin' ) || self::get_loggedin_id() == 1 );
		//return (self::getcred() && self::getlevel() == 1);
	}

	public static function get_super_admin_ids() {
		return array( 1 ); // todo - database this out.
	}

	public static function is_super_admin() {
		return ( in_array( self::get_loggedin_id(), self::get_super_admin_ids() ) ); // todo - other user ids here mark as super admin.
	}

	public static function is_contact() {
		// is the user a "contact" - that is - is the user assigned to a particluar customer id ?
		module_debug::log( array(
			'title' => 'DEPRECATED',
			'file'  => 'includes/plugin_security/security.php',
			'data'  => 'Called is_contact()',
		) );

		return self::is_role( 'contact' );
	}

	public static function is_support() {
		module_debug::log( array(
			'title' => 'DEPRECATED',
			'file'  => 'includes/plugin_security/security.php',
			'data'  => 'Called is_support()',
		) );

		return self::is_role( 'support' );
		//return (self::getcred() && self::getlevel() == 3);
	}

	private static $account_checked = false;

	public static function getcred() {
		if ( ! self::$account_checked ) {
			self::$account_checked = true;
			// see if this user is logged in.
			$user_id = self::get_loggedin_id();
			// check if this account still exists (ie: they haven't deleted themselves)
			if ( $user_id ) {
				$user = get_single( 'user', 'user_id', $user_id );
				if ( ! $user || $user['user_id'] != $user_id ) {
					self::logout();
					redirect_browser( '/' );
				}
			}
		}

		return isset( $_SESSION['_AVA_logged_in'] ) ? $_SESSION['_AVA_logged_in'] : false;
	}

	public static function is_logged_in() {
		return self::getcred();
	}

	/*public static function getlevel(){
		return (self::getcred() && isset($_SESSION['_user_type_id'])) ? $_SESSION['_user_type_id'] : false;
		//return (self::getcred() && isset($_SESSION['_access_level'])) ? $_SESSION['_access_level'] : false;
	}*/
	public static function get_data_access() {
		return ( self::getcred() && isset( $_SESSION['_data_access'] ) && $_SESSION['_data_access'] ) ? unserialize( $_SESSION['_data_access'] ) : array();
	}

	// this is used when filtering out customers from the system.
	// it's also used in the can_access_data call that is used in some parts of the system.
	private static $customer_restrictions = array();

	public static function get_customer_restrictions() {

		if ( ! self::is_logged_in() ) {
			return array( - 1 => - 1 );
		}
		/*
		new feature: we use this function instead of our hardcoded:
		switch($customer_access){
				case _CUSTOMER_ACCESS_ALL:
				case _CUSTOMER_ACCESS_CONTACTS:
				case _CUSTOMER_ACCESS_TASKS:
				case _CUSTOMER_ACCESS_STAFF:
		}
		through out the system.
		*/

		/*$res = (isset($_SESSION['_restrict_customer_id'])) ? $_SESSION['_restrict_customer_id'] : false;
		if(!is_array($res) && $res > 0){
				$res = array($res);
		}*/
		if ( isset( self::$customer_restrictions[ module_security::get_loggedin_id() ] ) ) {
			return self::$customer_restrictions[ module_security::get_loggedin_id() ];
		}
		self::$customer_restrictions[ module_security::get_loggedin_id() ] = array();
		$customers                                                         = module_customer::get_customers( array(), array( 'columns'     => 'c.customer_id',
		                                                                                                                     'as_resource' => false
		) );
		if ( count( $customers ) > 0 ) {
			foreach ( $customers as $customer ) {
				self::$customer_restrictions[ module_security::get_loggedin_id() ][ $customer['customer_id'] ] = $customer['customer_id'];
			}
		} else {
			self::$customer_restrictions[ module_security::get_loggedin_id() ] = array(
				- 1 => - 1,
			);
		}

		return self::$customer_restrictions[ module_security::get_loggedin_id() ];
	}

	public static function logout() {
		$_SESSION['_AVA_logged_in'] = false;
		//$_SESSION['_access_level'] = false;
		$_SESSION['_user_type_id'] = false;
		$_SESSION['_user_id']      = false;
		$_SESSION['_user_role_id'] = false;
		//$_SESSION['_restrict_customer_id'] = false;
		$_SESSION['display_mode'] = false;
		//session_unset();
		//session_destroy();
	}

	/**
	 * Sign in from an auto-login key.
	 *
	 * The key is derived from the stored password hash, so holding a valid one already
	 * proves as much as a password does. It used to put the stored hash in
	 * $_REQUEST['password'] and run the normal login, which only ever worked because
	 * verification accepted a hash as its own password - the hole 31855c3 closed. Since
	 * then the only accounts an auto-login link worked for were the ones still on the
	 * shipped default password, which quietly broke password reset emails, customer
	 * welcome emails and every public invoice, quote, job, ticket and contract link.
	 */
	public static function auto_login( $redirect = true ) {
		if ( ! isset( $_REQUEST['auto_login'] ) || ! is_string( $_REQUEST['auto_login'] ) || strlen( $_REQUEST['auto_login'] ) <= 7 ) {
			return false;
		}
		$foo     = explode( ":", $_REQUEST['auto_login'] );
		$user_id = (int) $foo[0];
		if ( $user_id <= 0 ) {
			return false;
		}
		// get the real key.
		$real_key = self::get_auto_login_string( $user_id );
		if ( ! $real_key || ! hash_equals( (string) $real_key, $_REQUEST['auto_login'] ) ) {
			return false;
		}
		// The same row shape process_login() works with, so the session is set up
		// identically however somebody arrived.
		$sql = "SELECT u.*, sr.security_role_id FROM `" . _DB_PREFIX . "user` u LEFT JOIN `" . _DB_PREFIX . "user_role` sr ON u.user_id = sr.user_id WHERE u.user_id = '" . (int) $user_id . "' LIMIT 1";
		$res = qa1( $sql );
		if ( ! $res ) {
			return false;
		}
		if ( getcred() ) {
			set_message( _l( "You have been logged out." ) );
		}
		hook_handle_callback( 'before_login' );

		return self::establish_session( $res, $redirect );
	}
	/*public static function set_access_level($user_id,$access_level){
			// move this back to the user module
			//module_user::set_user_type($user_id,$access_level);
			// todo - search the user_type table for their access level.
			$user_id = (int)$user_id;
			switch($access_level){
					case 'support':
							$access_level = 3;
							break;
					case 'contact':
							$access_level = 2;
							break;
					case 'admin':
					case 'user':
							$access_level = 1;
							break;
					default:
							$access_level = (int)$access_level;
			}
			if(!$user_id || !$access_level)return;
			$sql = "REPLACE INTO `"._DB_PREFIX."security_access` SET user_id = '".$user_id."', `access_level` = '$access_level'";
			query($sql);
	}*/
	/*public static function get_access_level($user_id){
        // back to the user module.
		$level = get_single('security_access','user_id',$user_id);
		if(!$level){
			// insert a default level of 2?
            $access_level = 2; // this means they are a contact user.
            if(!$_SESSION['_restrict_customer_id']){
                // this means they are an administrator
                $access_level = 1;
            }
			self::set_access_level($user_id,$access_level);
			$level = get_single('security_access','user_id',$user_id);
		}
		$level['data_access'] = unserialize($level['data_access']);
		if(!$level['data_access'])$level['data_access'] = array();
		return $level;
	}*/
	public static function process_password_reset() {
		// grab our email template and send it to this email address.
		$email   = trim( strtolower( $_REQUEST['email'] ) );
		$success = false;
		if ( strlen( $email ) > 4 && strpos( $email, '@' ) ) {
			$users    = module_user::get_users( array( 'email' => $email ) );
			$contacts = module_user::get_contacts( array( 'email' => $email ) );
			$users    = array_merge( $users, $contacts );
			foreach ( $users as $user ) {
				// send auto login link for this user.
				if ( strtolower( $user['email'] ) == $email ) {
					$template = module_template::get_template_by_key( 'password_reset' );
					$template->assign_values( $user );
					if ( $user['customer_id'] ) {
						$url = module_user::link_open_contact( $user['user_id'], false, $user, true );
					} else {
						$url = module_user::link_open( $user['user_id'], false, $user, true );
					}
					$url .= ( strpos( $url, '?' ) ? '&' : '?' ) . 'reset_password=' . self::get_auto_login_string( $user['user_id'] );
					$url .= '&auto_login=' . self::get_auto_login_string( $user['user_id'] );
					$template->assign_values( array(
						'auto_login_url' => $url,
					) );
					$html = $template->render( 'html' );

					$email                 = module_email::new_email();
					$email->replace_values = $user;
					$email->set_to( 'user', $user['user_id'] );
					$email->set_subject( $template->description );
					// do we send images inline?
					$email->set_html( $html );

					if ( $email->send() ) {
						// it worked successfully!!
						$success = true;
					} else {
						/// log err?
						echo 'failed to send email, sorry';
						exit;
					}
				}
			}
		}
		if ( $success || ! module_config::c( 'password_reset_debug', 0 ) ) {
			set_message( 'Please check your email for password reset instructions.' );
		} else {
			echo 'No users found matching ' . htmlspecialchars( $email );
			exit;
		}
		redirect_browser( _BASE_HREF );
	}

	public static function process_login( $redirect = true, $captcha_check = true ) {

		hook_handle_callback( 'before_login' );

		if ( $captcha_check && module_config::c( 'login_recaptcha', 0 ) ) {
			// ignore captcha check from auto_login call (sets $captcha_check=false)
			if ( ! module_captcha::check_captcha_form() ) {
				// captcha was wrong.
				set_error( 'Sorry the captcha code you entered was incorrect. Please try again.' );

				return;
				//_e('Sorry the captcha code you entered was incorrect. Please <a href="%s" onclick="%s">go back</a> and try again.','#','window.history.go(-1); return false;');
				//exit;
			}
		}


		if ( ! empty( $_REQUEST['email'] ) && ! empty( $_REQUEST['password'] ) ) {
			$email                      = trim( $_REQUEST['email'] );
			$password                   = trim( $_REQUEST['password'] );
			$_SESSION['_AVA_logged_in'] = false;
			if ( strlen( $email ) && strlen( $password ) ) {
				// Fetch user by email only (password verified in PHP for security)
				$sql = "SELECT u.*, sr.security_role_id FROM `" . _DB_PREFIX . "user` u LEFT JOIN `" . _DB_PREFIX . "user_role` sr ON u.user_id = sr.user_id WHERE `email` LIKE '" . db_escape( $email ) . "' LIMIT 1";
				$res = qa1( $sql );
				if ( $res && strlen( trim( $res['email'] ) ) > 0 && strtolower( $res['email'] ) == strtolower( $email ) && ucm_password_verify( $password, $res['password'], $res['user_id'] ) ) {
					// Migrate legacy MD5 to bcrypt on successful login
					if ( ucm_password_needs_rehash( $res['password'] ) ) {
						$new_hash = ucm_password_hash( $password );
						query( "UPDATE `" . _DB_PREFIX . "user` SET `password` = '" . db_escape( $new_hash ) . "' WHERE user_id = '" . (int) $res['user_id'] . "' LIMIT 1" );
					}

					return self::establish_session( $res, $redirect );
				}
			}
		}
		hook_handle_callback( 'login_failed' );
		set_error( 'Invalid username or password, please try again.' );

		return true;
	}

	/**
	 * Put a verified user into the session.
	 *
	 * Everything after "this really is them", shared by the password login and the
	 * auto-login link so the two cannot drift apart.
	 *
	 * @param array $res      the user row, already verified by the caller
	 * @param bool  $redirect reload the current URL once the session is set up
	 *
	 * @return bool
	 */
	private static function establish_session( $res, $redirect = true ) {
		$_SESSION['_AVA_logged_in'] = false;

		// A contact can be linked to a parent login; sign that one in instead.
		if ( isset( $res['linked_parent_user_id'] ) && $res['linked_parent_user_id'] > 0 ) {
			$parent_user = module_user::get_user( $res['linked_parent_user_id'], false, false );
			if ( $parent_user && $res['linked_parent_user_id'] == $parent_user['user_id'] ) {
				$res = $parent_user;
			}
		}

		// check this user has permissions to login.
		if ( $res['user_id'] != 1 && ! self::can_user_login( $res['user_id'] ) ) {
			set_error( 'Account disabled' );
			if ( $redirect ) {
				$_SERVER['REQUEST_URI'] = preg_replace( '/auto_login=[^&]*&?/', '', $_SERVER['REQUEST_URI'] );
				redirect_browser( $_SERVER['REQUEST_URI'] );
			}

			return false;
		}
		$_SESSION['_AVA_logged_in'] = true;

		$sql = "INSERT INTO `" . _DB_PREFIX . "security_login` SET user_id = '" . (int) $res['user_id'] . "', `time` = '" . (int) time() . "', ip_address = '" . db_escape( $_SERVER['REMOTE_ADDR'] ?? '' ) . "'";
		query( $sql );

		$_SESSION['_user_name']    = $res['name'];
		$_SESSION['_user_email']   = $res['email'];
		$_SESSION['_user_id']      = $res['user_id'];
		$_SESSION['_user_role_id'] = isset( $res['security_role_id'] ) ? $res['security_role_id'] : false;
		$_SESSION['_language']     = $res['language'];
		hook_handle_callback( 'login_success' );
		set_message( _l( "You have successfully logged in." ) );

		// The install ships the literal string 'password' rather than a hash, so the
		// stored value is itself the record of "nobody has ever set one". The code has
		// always claimed it prompts for a change and never did. Land them on their own
		// login settings, every time, until they do. Not on the public demo: its accounts
		// all keep the install password and cannot change it, so the prompt would fire on
		// every sign-in and send visitors to a page that refuses the change.
		if ( $redirect && ! _DEMO_MODE && ucm_password_is_default( isset( $res['password'] ) ? $res['password'] : '' ) ) {
			set_error( _l( 'Please set a password for this account. It is still the one the system was installed with.' ) );
			// Built rather than taken from module_user::link_open(), which bubbles the
			// user page under whichever module the link came from.
			redirect_browser( link_generate( array(
				array(
					'module'    => 'user',
					'page'      => 'user_admin',
					'arguments' => array( 'user_id' => (int) $res['user_id'] ),
				),
			) ) );
		}

		if ( $redirect ) {
			$_SERVER['REQUEST_URI'] = preg_replace( '/auto_login=[^&]*&?/', '', $_SERVER['REQUEST_URI'] );
			redirect_browser( $_SERVER['REQUEST_URI'] );
			exit;
		}

		return true;
	}

	public function handle_hook( $hook, &$calling_module = false ) {
		switch ( $hook ) {

		}
	}

	public function process() {
		// Every form that reaches here is posted from a UCM page, so it carries the
		// form auth key. Refuse anything that does not - see module_form::check_secure_key().
		if ( ! module_form::check_secure_key() ) {
			module_form::reject_invalid_form_auth();
		}
		/*if('save_data_access_popup' == $_REQUEST['_process']){
			// saving data access for specieid user id.
			// get user id from post.
			// todo - make this secure, check current user has permissions to access security :)
			// dodgy dave.
			$user_id = (int)$_REQUEST['user_id'];
			if($user_id && $_REQUEST['access_level']){
				$sql = "UPDATE `"._DB_PREFIX."security_access` SET `access_level` = '".(int)$_REQUEST['access_level']."' WHERE user_id = '".$user_id."' LIMIT 1";
				query($sql);
			}
			if($user_id && is_array($_REQUEST['data_access'])){
				$sql = "UPDATE `"._DB_PREFIX."security_access` SET `data_access` = '".db_escape(serialize($_REQUEST['data_access']))."' WHERE user_id = '".$user_id."' LIMIT 1";
				query($sql);
			}

		}else */
		if ( 'save_security_role' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				redirect_browser( _BASE_HREF );
			}
			if ( ! module_security::can_i( 'edit', 'Security Roles', 'Security' ) ) {
				redirect_browser( '/' );
			}

			if ( isset( $_REQUEST['butt_del'] ) && module_security::can_i( 'delete', 'Security Roles', 'Security' ) ) {
				$security_role_id = (int) $_REQUEST['security_role_id'];
				$role             = self::get_security_role( $security_role_id );
				if ( $role && $security_role_id == $role['security_role_id'] ) {
					if ( module_form::confirm_delete(
						'security_role_id',
						"Really delete security role: " . $role['name'],
						self::link_open_role( $security_role_id )
					) ) {
						$sql = "DELETE FROM `" . _DB_PREFIX . "security_role_perm` WHERE security_role_id = '" . (int) $security_role_id . "'";
						query( $sql );
						$sql = "DELETE FROM `" . _DB_PREFIX . "user_role` WHERE security_role_id = '" . (int) $security_role_id . "'";
						query( $sql );
						$sql = "DELETE FROM `" . _DB_PREFIX . "security_role` WHERE security_role_id = '" . (int) $security_role_id . "'";
						query( $sql );
						$sql = "DELETE FROM `" . _DB_PREFIX . "security_group_role_rel` WHERE security_role_id = '" . (int) $security_role_id . "'";
						query( $sql );
					}
				}
				set_message( 'Role deleted successfully.' );
				redirect_browser( $this->link_open_role( false ) );
			}
			$security_role_id = update_insert( 'security_role_id', $_REQUEST['security_role_id'], 'security_role', $_POST );
			// todo - deleting.

			// The role form posts its permission grid as one field rather than one per
			// permission per action - see security_role_edit.php for why. Anything else
			// posting to save_security_role still works the old way.
			$posted = self::decode_permission_data( $_REQUEST );

			if ( $security_role_id ) {
				$sql = "DELETE FROM `" . _DB_PREFIX . "security_role_perm` WHERE security_role_id = '" . (int) $security_role_id . "'";
				query( $sql );
				$sql = "DELETE FROM `" . _DB_PREFIX . "security_group_role_rel` WHERE security_role_id = '" . (int) $security_role_id . "'";
				query( $sql );
				if ( isset( $_REQUEST['load_defaults'] ) && strlen( $_REQUEST['load_defaults'] ) > 0 && $defaults = json_decode( $_REQUEST['load_defaults'], true ) ) {

					//$export_json[$available_permission['category'].'|'.$available_permission['module'].'|'.$available_permission['name'].'|'.$available_permission['description']][] = $permission;
					foreach ( $defaults as $key => $permissions ) {
						list( $category, $module, $name, $description ) = explode( '|', $key );
						$existing               = get_single( 'security_permission', array(
							'name',
							'category',
							'description',
							'module',
						), array(
							$name,
							$category,
							$description,
							$module,
						) );
						$security_permission_id = false;
						$available_perms        = array();
						if ( $existing ) {
							$security_permission_id = $existing['security_permission_id'];
							$available_perms        = @unserialize( $existing['available_perms'] );
							if ( ! is_array( $available_perms ) ) {
								$available_perms = array();
							}
						}
						if ( ! $security_permission_id ) {
							$security_permission_id = update_insert( 'security_permission_id', 'new', 'security_permission', array(
								'name'        => $name,
								'category'    => $category,
								'module'      => $module,
								'description' => $description,
							) );
						}
						$save_perms = false;
						foreach ( self::$available_permissions as $permission ) {
							if ( in_array( $permission, $permissions ) ) {
								// the script is asking for this available permission.
								// check if it exists in the db as an option
								if ( ! isset( $available_perms[ $permission ] ) ) {
									// time to add it to the db so we can configure this in the future.
									$available_perms[ $permission ] = true;
									$save_perms                     = true;
								}
							}
						}
						if ( $save_perms && $security_permission_id ) {
							update_insert( 'security_permission_id', $security_permission_id, 'security_permission', array(
								'available_perms' => serialize( $available_perms )
							) );
						}
						if ( $security_permission_id ) {
							$actions = array();
							foreach ( self::$available_permissions as $permission ) {
								if ( in_array( $permission, $permissions ) ) {
									$actions[ $permission ] = 1;
								}
							}
							if ( count( $actions ) ) {
								$sql = "REPLACE INTO `" . _DB_PREFIX . "security_role_perm` SET security_role_id = '" . (int) $security_role_id . "', security_permission_id = '" . (int) $security_permission_id . "' ";
								foreach ( $actions as $permission => $tf ) {
									$sql .= ", `" . db_identifier( $permission ) . "` = 1";
								}
								query( $sql );
							}
						}
					}
					set_message( 'Defaults loaded successfully.' );


				} else {
					if ( isset( $posted['permission'] ) && is_array( $posted['permission'] ) ) {
						// update permissions for this role.
						foreach ( $posted['permission'] as $security_permission_id => $permissions ) {
							$actions = array();
							foreach ( self::$available_permissions as $permission ) {
								if ( isset( $permissions[ $permission ] ) && $permissions[ $permission ] ) {
									$actions[ $permission ] = 1;
								}
							}
							$sql = "REPLACE INTO `" . _DB_PREFIX . "security_role_perm` SET security_role_id = '" . (int) $security_role_id . "', security_permission_id = '" . (int) $security_permission_id . "' ";
							foreach ( $actions as $permission => $tf ) {
								$sql .= ", `" . db_identifier( $permission ) . "` = 1";
							}
							query( $sql );
						}
					}
					if ( isset( $posted['permission_drop_down'] ) && is_array( $posted['permission_drop_down'] ) ) {
						// update permissions for this role.
						$permission = 'view';
						foreach ( $posted['permission_drop_down'] as $security_permission_ids => $selected_security_permission_id ) {
							$ids_to_clear = explode( '|', $security_permission_ids );
							foreach ( $ids_to_clear as $id_to_clear ) {
								$id_to_clear = (int) $id_to_clear;
								if ( ! $id_to_clear ) {
									continue;
								}
								$sql = "DELETE FROM `" . _DB_PREFIX . "security_role_perm` WHERE security_role_id = '" . (int) $security_role_id . "' AND security_permission_id = '" . (int) $id_to_clear . "' ";
								query( $sql );
							}
							if ( (int) $selected_security_permission_id > 0 ) {
								$sql = "REPLACE INTO `" . _DB_PREFIX . "security_role_perm` SET security_role_id = '" . (int) $security_role_id . "', security_permission_id = '" . (int) $selected_security_permission_id . "' ";
								$sql .= ", `" . db_identifier( $permission ) . "` = 1";
								query( $sql );
							}
						}
					}
					if ( isset( $posted['group_access'] ) && is_array( $posted['group_access'] ) ) {
						foreach ( $posted['group_access'] as $group_id => $enabled ) {
							if ( $enabled ) {
								$sql = "INSERT INTO `" . _DB_PREFIX . "security_group_role_rel` SET security_role_id = '" . (int) $security_role_id . "', group_id = '" . (int) $group_id . "' ";
								query( $sql );
							}
						}
					}
					set_message( 'Role saved successfully.' );
				}
				redirect_browser( $this->link_open_role( $security_role_id ) );
			}
		}
	}

	public static function get_auto_login_string( $user_id ) {
		$user_id = (int) $user_id;
		$sql     = "SELECT * FROM `" . _DB_PREFIX . "user` WHERE user_id = '$user_id'";
		$res = qa( $sql );
		if ( $res ) {
			$user = array_shift( $res );
			if ( $user ) {
				return $user_id . ":" . substr( md5( "user $user_id pass " . $user['password'] ), 4, module_config::c( 'security_auto_login_length', 20 ) ); //shorten it a tad.
			}
		}

		return '';
	}


	public static function generate_auto_login_link( $user_id ) {
		if ( (int) $user_id ) {
			return full_link( "?auto_login=" . self::get_auto_login_string( $user_id ) );
		}
	}

	public static function get_loggedin_id() {
		if ( self::$user_id_override !== false ) {
			return self::$user_id_override;
		}

		return isset( $_SESSION['_user_id'] ) ? $_SESSION['_user_id'] : false;
	}

	public static function get_loggedin_role() {
		if ( ! empty( $_SESSION['_user_role_id'] ) ) {
			return $_SESSION['_user_role_id'];
		}

		return false;
	}

	public static $user_id_override = false;

	public static function user_id_temp_set( $temp_user_id ) {
		self::$user_id_override = $temp_user_id;
	}

	public static function user_id_temp_restore() {
		self::$user_id_override = false;
	}


	/**
	 * The permission state a save_security_role post is asking for.
	 *
	 * The role form posts `permission_data`, one JSON field holding the whole grid, the
	 * data access drop downs and the group access boxes. It does that because a field per
	 * permission per action stopped fitting in a post: PHP parses at most max_input_vars
	 * of them (1000 by default) and silently drops the rest, and the grid has one control
	 * per row in `security_permission`, a table that grows as the install is used.
	 *
	 * Anything that posts the older `permission[id][action]` fields - a saved integration,
	 * a script - still works, so long as it stays under the limit. The JSON field wins
	 * when both are present.
	 *
	 * @param array $request The request to read, normally $_REQUEST.
	 *
	 * @return array With `permission`, `permission_drop_down` and `group_access` keys,
	 *               each present only if the request actually carried it.
	 */
	public static function decode_permission_data( $request ) {
		$legacy = array();
		foreach ( array( 'permission', 'permission_drop_down', 'group_access' ) as $key ) {
			if ( isset( $request[ $key ] ) && is_array( $request[ $key ] ) ) {
				$legacy[ $key ] = $request[ $key ];
			}
		}

		if ( ! isset( $request['permission_data'] ) || ! is_string( $request['permission_data'] ) ) {
			return $legacy;
		}
		$bundle = json_decode( $request['permission_data'], true );
		if ( ! is_array( $bundle ) ) {
			return $legacy;
		}

		$decoded = array();

		if ( isset( $bundle['permission'] ) && is_array( $bundle['permission'] ) ) {
			$decoded['permission'] = array();
			foreach ( $bundle['permission'] as $security_permission_id => $actions ) {
				if ( ! (int) $security_permission_id || ! is_array( $actions ) ) {
					continue;
				}
				$allowed = array();
				foreach ( self::$available_permissions as $action ) {
					if ( ! empty( $actions[ $action ] ) ) {
						$allowed[ $action ] = 1;
					}
				}
				$decoded['permission'][ (int) $security_permission_id ] = $allowed;
			}
		}

		if ( isset( $bundle['permission_drop_down'] ) && is_array( $bundle['permission_drop_down'] ) ) {
			$decoded['permission_drop_down'] = array();
			foreach ( $bundle['permission_drop_down'] as $security_permission_ids => $selected ) {
				if ( is_array( $selected ) ) {
					continue;
				}
				// The key is the pipe separated list of ids the drop down covers; the
				// handler explodes it again to clear them. Keep it to digits and pipes.
				$key = preg_replace( '/[^0-9|]/', '', (string) $security_permission_ids );
				if ( '' === $key ) {
					continue;
				}
				$decoded['permission_drop_down'][ $key ] = (string) (int) $selected;
			}
		}

		if ( isset( $bundle['group_access'] ) && is_array( $bundle['group_access'] ) ) {
			$decoded['group_access'] = array();
			foreach ( $bundle['group_access'] as $group_id => $enabled ) {
				if ( (int) $group_id && ! empty( $enabled ) && ! is_array( $enabled ) ) {
					$decoded['group_access'][ (int) $group_id ] = 1;
				}
			}
		}

		return $decoded + $legacy;
	}

	public static function get_permissions( $search = array() ) {
		return get_multiple( 'security_permission', $search, 'security_permission_id', 'exact', 'category' );
	}

	public static function get_roles( $search = array() ) {
		return get_multiple( 'security_role', $search, 'security_role_id', 'exact', 'name' );
	}

	public static function get_security_role( $security_role_id ) {
		$role = get_single( 'security_role', 'security_role_id', $security_role_id );
		if ( ! is_array( $role ) || ! $role ) {
			// Adding permissions to a "not found" result would make it look like a real
			// role to every caller that only checks the return value.
			return array();
		}
		$role['permissions'] = get_multiple( 'security_role_perm', array( 'security_role_id' => $security_role_id ), 'security_permission_id' );

		return $role;
	}

	function is_installed() {
		return self::db_table_exists( 'security_login' );
	}

	public function get_install_sql() {
		/*

CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_access` (
`user_id` int(11) NOT NULL,
`access_level` int(11) NOT NULL,
`data_access` text NOT NULL,
PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8; */
		ob_start();
		?>


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_login` (
		`user_login_id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`time` int(11) NOT NULL,
		`ip_address` varchar(15) NOT NULL,
		PRIMARY KEY (`user_login_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_permission` (
		`security_permission_id` int(11) NOT NULL AUTO_INCREMENT,
		`system_id` int(11) NOT NULL,
		`name` varchar(255) NOT NULL,
		`category` varchar(30) NOT NULL,
		`module` varchar(30) NOT NULL,
		`description` text NOT NULL,
		`available_perms` text NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		PRIMARY KEY (`security_permission_id`),
		KEY `system_id` (`system_id`),
		KEY `name` (`name`),
		KEY `category` (`category`),
		KEY `module` (`module`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_role` (
		`security_role_id` int(11) NOT NULL AUTO_INCREMENT,
		`system_id` int(11) NOT NULL,
		`name` varchar(255) NOT NULL,
		`description` text NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		PRIMARY KEY (`security_role_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_role_perm` (
		`security_role_id` int(11) NOT NULL,
		`security_permission_id` int(11) NOT NULL,
		`view` tinyint(4) NOT NULL DEFAULT '0',
		`edit` tinyint(4) NOT NULL DEFAULT '0',
		`delete` tinyint(4) NOT NULL DEFAULT '0',
		`create` tinyint(4) NOT NULL DEFAULT '0',
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		PRIMARY KEY (`security_role_id`,`security_permission_id`),
		KEY `security_permission_id` (`security_permission_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_group_rel` (
		`group_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		PRIMARY KEY (`group_id`,`user_id`),
		KEY `user_id` (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>security_group_role_rel` (
		`group_id` int(11) NOT NULL,
		`security_role_id` int(11) NOT NULL,
		PRIMARY KEY (`group_id`,`security_role_id`),
		KEY `security_role_id` (`security_role_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		ALTER TABLE `<?php echo _DB_PREFIX; ?>security_role_perm`
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>security_role_perm_ibfk_1` FOREIGN KEY (`security_role_id`) REFERENCES `<?php echo _DB_PREFIX; ?>security_role` (`security_role_id`) ON DELETE CASCADE,
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>security_role_perm_ibfk_2` FOREIGN KEY (`security_permission_id`) REFERENCES `<?php echo _DB_PREFIX; ?>security_permission` (`security_permission_id`) ON DELETE CASCADE;


		<?php

		/*
	INSERT INTO `<?php echo _DB_PREFIX; ?>security_access` VALUES (1, 1, '');
	INSERT INTO `<?php echo _DB_PREFIX; ?>security_access` VALUES (2, 2, '');
	*/

		return ob_get_clean();
	}

	public function get_upgrade_sql() {
		$sql = '';
		if ( ! self::db_table_exists( 'security_group_rel' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'security_group_rel` (
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		}
		if ( ! self::db_table_exists( 'security_group_role_rel' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'security_group_role_rel` (
  `group_id` int(11) NOT NULL,
  `security_role_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`security_role_id`),
  KEY `security_role_id` (`security_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		}

		return $sql;
	}

	public static function get_group_restrictions( $group_type ) {
		// find available groups for this owner_table type.
		$restrictions = array();
		if ( class_exists( 'module_group' ) ) {
			//$groups = module_group::get_groups($group_type);
			// find out what groups the current user can access out of this particular group set.
			// todo - might not need this 'get_groups' check, just hit up the security_group_rel table
			// todo - find any user account overridden groups
			// for now group permissions are set at the user role level. we'll add an override feature to the user profile page down the track.
			//$allowed_groups = get_multiple('security_group_rel',array('user_id'=>self::get_loggedin_id()));
			$user_id        = (int) self::get_loggedin_id();
			$sql            = "SELECT sg.*, sg.group_id AS id FROM `" . _DB_PREFIX . "security_group_role_rel` sg LEFT JOIN `" . _DB_PREFIX . "user_role` ur USING (security_role_id) WHERE ur.user_id = $user_id";
			$allowed_groups = qa( $sql );
			foreach ( $allowed_groups as $allowed_group ) {
				$restrictions[ $allowed_group['group_id'] ] = $allowed_group['group_id'];
			}
		}
		if ( ! count( $restrictions ) ) {
			$restrictions = array( - 1 => - 1 );
		}

		return $restrictions;
	}

	public static function get_role_groups( $security_role_id ) {
		return get_multiple( 'security_group_role_rel', array( 'security_role_id' => $security_role_id ), 'group_id' );
	}

	// these actions are displayed as a yes/no tick box in the permissions area.
	public static function can_user( $user_id, $action ) {
		return self::has_feature_access( array(
			'category'    => _LABEL_USER_SPECIFIC,
			'name'        => $action,
			'module'      => 'config',
			'view'        => 1,
			'description' => 'checkbox',
		), $user_id );
	}

	// these actions are displayed as a drop down options in the permissions area.
	public static function can_user_with_options( $user_id, $action, $options = array() ) {
		if ( ! self::is_logged_in() ) {
			return false;
		}
		if ( ! is_array( $options ) ) {
			$options = array( $options );
		}
		$return = false;
		foreach ( $options as $option ) {
			$access = self::has_feature_access( array(
				'category'    => $action,
				'name'        => $option,
				'module'      => 'config',
				'view'        => 1,
				'description' => 'drop_down',
			), $user_id );
			if ( $access && ! $return ) {
				$return = $option;
			}
		}

		return $return;
	}

	public static function can_user_login( $user_id ) {
		// return the query for a checkbox option in permissions.
		return self::can_user( $user_id, 'Can User Login' );

	}

	private static function _build_perms_cache() {

		$sql               = "SELECT `name`, `category`, `module`, available_perms, security_permission_id, `description` ";
		$sql               .= "FROM `" . _DB_PREFIX . "security_permission` sp";
		self::$perms_cache = qa( $sql, false );
	}

	public static function get_loggedin_name() {
		return ! empty( $_SESSION['_user_name'] ) ? $_SESSION['_user_name'] : '';
	}

	private static $process_editable_page = false;

	public static function disallow_page_editing() {
		self::$process_editable_page = true;
	}

	private static $page_denied = false;
	private static $page_denied_message = '';

	public static function is_page_editable() {
		if ( self::$process_editable_page ) {
			return false;
		} // page isn't editable
		if ( self::$page_denied ) {
			return false;
		} // no permissions to access page.

		return true; // defaults to page editable.
	}

	public static function render_page_finished() {
		if ( self::$page_denied ) {

			self::$page_denied = false;
			$null              = ob_get_clean(); // remove page content.
			echo self::$page_denied_message;
			self::$page_denied_message = '';

		} elseif ( self::$process_editable_page ) {
			module_debug::log( array(
				'title' => 'Page Editable',
				'file'  => 'includes/plugin_security/security.php',
				'data'  => "User doesn't have edit permissions, time to remove all form elements.",
			) );
			self::$process_editable_page = false;
			$editable_content            = ob_get_clean();
			//ob_clean();
			//$editable_content = preg_replace('#</?form[^>]*>#imsU','',$editable_content);
			//$editable_content = preg_replace('#<input[^>]*type="submit"[^>]*>#imsU','',$editable_content);
			//$editable_content = preg_replace('#<input[^>]*type="button"[^>]*>#imsU','',$editable_content);
			//$editable_content = preg_replace('#<input[^>]*type="radio"[^>]*>#imsU','',$editable_content);
			//$editable_content = preg_replace('#<input[^>]*type="hidden"[^>]*>#imsU','',$editable_content);

			// check if this is bad: disabling script. eg: file edit screen.
			//$editable_content = preg_replace('#<script[^>]*>.*</script>#imsU','',$editable_content);

			$editable_content = preg_replace( '#<a[^>]+help-icon[^>]*>[^<]*</a>#imsU', '', $editable_content );
			$editable_content = preg_replace( '#<span[^>]class="button"[^>]*>.*</span>#imsU', '', $editable_content );
			if ( preg_match_all( '#<input[^>]*type="text"[^>]*>#imsU', $editable_content, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					if ( strpos( $match, 'no_permissions' ) === false ) {
						$replace_with = '';
						if ( preg_match( '#value="([^"]*)"#imsU', $match, $value ) ) {
							$replace_with = $value[1];
						}
						$editable_content = preg_replace( '#' . preg_quote( $match, '#' ) . '#msU', $replace_with, $editable_content );
					}
				}
			}
			if ( preg_match_all( '#<input[^>]*type="checkbox"[^>]*>#imsU', $editable_content, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					if ( ! strpos( $match, 'disabled=' ) ) {
						if ( strpos( $match, 'no_permissions' ) === false ) {
							$replace_with     = str_replace( 'type=', 'disabled="disabled" type=', $match );
							$editable_content = preg_replace( '#' . preg_quote( $match, '#' ) . '#msU', $replace_with, $editable_content );
						}
					}
				}
			}
			if ( preg_match_all( '#<textarea[^>]*>(.*)</textarea>#imsU', $editable_content, $matches ) ) {
				foreach ( $matches[0] as $match_key => $match ) {
					if ( strpos( $match, 'no_permissions' ) === false ) {
						$replace_with     = $matches[1][ $match_key ];
						$editable_content = preg_replace( '#' . preg_quote( $match, '#' ) . '#msU', $replace_with, $editable_content );
					}
				}
			}
			if ( preg_match_all( '#<select[^>]*>.*</select>#imsU', $editable_content, $matches ) ) {
				foreach ( $matches[0] as $match_key => $match ) {
					if ( strpos( $match, 'no_permissions' ) === false ) {
						// find out which <option> is selected.
						$replace_with = '';
						if ( preg_match( '#<option[^>]*selected[^>]*>(.*)</option>#imsU', $match, $options ) ) {
							$replace_with = $options[1];
						}
						$editable_content = str_replace( $match, $replace_with, $editable_content );
						//$editable_content = preg_replace('#'.preg_quote($match,'#').'#msU',$replace_with,$editable_content);
					}
				}
			}

			// remove all input elements that do not have a class of "no_permissions"
			if ( preg_match_all( '#<input[^>]*>#imsU', $editable_content, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					if ( strpos( $match, 'no_permissions' ) === false && strpos( $match, 'type="text"' ) === false ) {
						$editable_content = preg_replace( '#' . preg_quote( $match, '#' ) . '#imsU', '', $editable_content );
					}
				}
			}

			echo $editable_content;
		}
	}

	public static function check_ssl() {
		if ( module_config::c( 'force_ssl', 0 ) && ! isset( $_REQUEST['nossl'] ) ) {
			if ( empty( $_SERVER['HTTPS'] ) ) {
				$url = module_config::c( 'system_base_href' ) . $_SERVER['REQUEST_URI'];
				$url = preg_replace( '#^http:#', 'https:', $url );
				redirect_browser( $url );
			}
		}
	}

	public static function purify_html( $dirty_html ) {
		if ( ! ucm_libraries::load( 'htmlpurifier' ) ) {
			// Without the library there is no safe way to keep any of this, so keep none
			// of it. Better a plain-text version than markup nobody has checked.
			return htmlspecialchars( (string) $dirty_html, ENT_QUOTES, 'UTF-8' );
		}
		$config = HTMLPurifier_Config::createDefault();
		$config->set( 'HTML.SafeIframe', true );
		$config->set( 'URI.SafeIframeRegexp', '%^http%' );
		$config->set( 'Cache.SerializerPath', _UCM_FILE_STORAGE_DIR . 'temp/' );
		$purifier = new HTMLPurifier( $config );

		return $purifier->purify( $dirty_html );
	}
}
