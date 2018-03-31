<?php


define( '_USER_ACCESS_ALL', 'All Contact and User Accounts' ); // do not change string
define( '_USER_ACCESS_ME', 'Only My Account' ); // do not change string
define( '_USER_ACCESS_CONTACTS', 'Only Contact Accounts' ); // do not change string


class module_user extends module_base {

	public $links;
	public $user_types;

	public $version = 2.289;
	// 2.289 - 2017-05-28 - api improvements
	// 2.288 - 2017-02-27 - staff member first/last name flag for drop downs
	// 2.287 - 2016-11-01 - ajax autocompletion
	// 2.286 - 2016-09-09 - user/staff list in custom data
	// 2.285 - 2016-08-15 - vendor fix
	// 2.284 - 2016-07-10 - big update to mysqli
	// 2.283 - 2015-12-28 - group permissions
	// 2.282 - 2015-10-17 - email optional required field
	// 2.281 - 2015-09-25 - customer type improvements
	// 2.28 - 2015-09-07 - api user details
	// 2.279 - 2015-06-08 - lead permission fixes
	// 2.278 - 2015-05-03 - responsive improvements
	// 2.277 - 2015-04-23 - more template tags for emails
	// 2.276 - 2015-03-08 - extra field permission fix
	// 2.275 - 2015-01-23 - hook for custom data integration
	// 2.274 - 2015-01-08 - customer signup fix
	// 2.273 - 2015-01-03 - vendor bug fix
	// 2.272 - 2014-12-27 - user permission improvement
	// 2.271 - 2014-12-08 - customer signup improvements
	// 2.27 - 2014-11-27 - primary contact bug fix
	// 2.269 - 2014-11-05 - password autocomplete fix
	// 2.268 - 2014-08-18 - vendor staff permission fixes
	// 2.267 - 2014-07-31 - responsive improvements
	// 2.266 - 2014-07-24 - working on better staff feature
	// 2.265 - 2014-07-21 - working on better staff feature
	// 2.264 - 2014-07-17 - working on better staff feature
	// 2.263 - 2014-07-14 - staff_remove_admin advanced field
	// 2.262 - 2014-07-05 - language improvement
	// 2.261 - 2014-03-17 - password reset fix for customer contacts
	// 2.26 - 2014-03-15 - password reset fix for customer contacts
	// 2.259 - 2014-01-18 - cache bug fix in customer contacts
	// 2.258 - 2014-01-10 - phone / email links in contact listings
	// 2.257 - 2013-11-20 - export customer contact listing
	// 2.256 - 2013-11-15 - working on new UI
	// 2.255 - 2013-09-21 - staff by flag advanced key
	// 2.254 - 2013-09-10 - dashboard speed fixes
	// 2.253 - 2013-09-06 - easier to disable certain plugins
	// 2.252 - 2013-08-30 - added memcache support for huge speed improvements
	// 2.251 - 2013-06-21 - permission update
	// 2.25 - 2013-06-17 - form autocomplete/autofill improvement for google chrome
	// 2.249 - 2013-05-01 - ticket create user fix
	// 2.248 - 2013-04-30 - new user permissions
	// 2.247 - 2013-04-10 - new customer permissions

	// 2.21 - contact deletion redirect / permissions fix
	// 2.22 - all customer contacts permission expansion
	// 2.221 - viewing customer contact groups as permissions.
	// 2.222 - searching by user role.
	// 2.223 - adding user to role from customer creation.
	// 2.224 - redirect fixed on no "All Customer Contacts" permissions
	// 2.23 - theme support for contact and user pages
	// 2.24 - "staff members" are now considered people who can "edit" job tasks - this will confuse some people! and probably break some peoples configs. oh well.
	// 2.241 - perm bug fix
	// 2.242 - fix for forgot password
	// 2.243 - hashed passwords
	// 2.244 - fix for creating users from support tickets
	// 2.245 - speed improvements
	// 2.246 - speed improvements

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
		$this->links           = array();
		$this->user_types      = array();
		$this->module_name     = "user";
		$this->module_position = 15;

		hook_add( 'contact_list', 'module_user::hook_filter_var_contact_list' );
		hook_add( 'staff_list', 'module_user::hook_filter_var_staff_list' );

		/*if(module_security::has_feature_access(array(
				'name' => 'Admin',
				'module' => 'config',
				'category' => 'Config',
				'view' => 1,
				'description' => 'view',
		))){*/

		if ( $this->can_i( 'view', 'Users', 'Config' ) ) {
			$this->links[] = array(
				"name"                => "Users",
				"p"                   => "user_admin",
				"args"                => array( 'user_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'order'               => 3,
			);
		}

		if ( file_exists( 'includes/plugin_user/pages/contact_admin_list.php' ) && $this->can_i( 'view', 'Contacts', 'Customer' ) ) {
			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many contacts?
				$name = _l( 'Contacts' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$contacts = $this->get_contacts( array( 'customer_id' => $_REQUEST['customer_id'] ), true, false );
					if ( mysqli_num_rows( $contacts ) > 0 ) {
						$name .= " <span class='menu_label'>" . mysqli_num_rows( $contacts ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "contact_admin",
					'args'                => array( 'user_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'user',
				);
			}
		}
		if ( file_exists( 'includes/plugin_user/pages/contact_admin_list.php' ) && $this->can_i( 'view', 'Contacts', 'Vendor' ) ) {
			// only display if a vendor has been created.
			if ( isset( $_REQUEST['vendor_id'] ) && (int) $_REQUEST['vendor_id'] > 0 ) {
				// how many contacts?
				$name = _l( 'Contacts' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$contacts = $this->get_contacts( array( 'vendor_id' => $_REQUEST['vendor_id'] ), true, false );
					if ( mysqli_num_rows( $contacts ) > 0 ) {
						$name .= " <span class='menu_label'>" . mysqli_num_rows( $contacts ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "contact_admin",
					'args'                => array( 'user_id' => false ),
					'holder_module'       => 'vendor', // which parent module this link will sit under.
					'holder_module_page'  => 'vendor_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'user',
				);
			}
		}
		hook_add( 'api_callback_user', 'module_user::api_filter_user' );

	}

	public static function link_generate( $user_id = false, $options = array(), $link_options = array() ) {

		$link_cache_key     = 'user_link_' . md5( module_security::get_loggedin_id() . '_' . serialize( func_get_args() ) );
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );
		if ( $cached_link = module_cache::get( 'user', $link_cache_key ) ) {
			return $cached_link;
		}

		$key = 'user_id';
		if ( $user_id === false && $link_options ) {
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
		$bubble_to_module = false;

		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		} else {
			$data = array();
		}
		if ( ! $data && $user_id ) {
			$data            = self::get_user( $user_id, false, true, true ); //,2);
			$options['data'] = $data;
		}
		if ( isset( $options['full'] ) && $options['full'] ) {

			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'] . ( isset( $data['last_name'] ) ? ' ' . $data['last_name'] : '' );
			if ( ! $data || ! $user_id ) {
				// linking to a new user?
				// shouldn't happen in a "full" link.
				return $options['text'];
			}
			if ( isset( $data['_perms'] ) && ! $data['_perms'] ) {
				return $options['text'];
			}
		}

		$use_master_key = self::get_contact_master_key();
		if ( isset( $data[ $use_master_key ] ) && $data[ $use_master_key ] ) {
			$options['type'] = 'contact';
		} else if ( ( isset( $data['customer_id'] ) && $data['customer_id'] ) ) {
			$use_master_key  = 'customer_id';
			$options['type'] = 'contact';
		} else if ( ( isset( $data['vendor_id'] ) && $data['vendor_id'] ) ) {
			$use_master_key  = 'vendor_id';
			$options['type'] = 'contact';
		}
		$options['arguments'] = array(
			'user_id' => $user_id,
		);

		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'user';
		}
		switch ( $options['type'] ) {
			case 'contact':
				$options['arguments'][ $use_master_key ] = isset( $data[ $use_master_key ] ) ? $data[ $use_master_key ] : 0;
				// for a contact link under supplier or a customer
				$options['page'] = 'contact_admin';
				switch ( $use_master_key ) {
					case 'customer_id':
						if ( $user_id == 'new' || (int) $user_id > 0 ) { // so the "view all contacts" link works.
							$bubble_to_module = array(
								'module'   => 'customer',
								'argument' => 'customer_id',
							);
						}
						break;
					case 'vendor_id':
						if ( $user_id == 'new' || (int) $user_id > 0 ) { // so the "view all contacts" link works.
							$bubble_to_module = array(
								'module'   => 'vendor',
								'argument' => 'vendor_id',
							);
						}
						break;
				}
				break;
			default:
				$bubble_to_module = array(
					'module' => 'config',
				);
				$options['page']  = 'user_admin';
		}

		$options['module'] = 'user';


		array_unshift( $link_options, $options );

		// check if people have permission to link to this item.
		/*if((!isset($options['skip_permissions']) || !$options['skip_permissions'])){
            if(isset($options['type']) && $options['type'] == 'contact'){
                // check they can access this particular contact type
                if(!module_security::has_feature_access(array(
                    'name' => 'Customers',
                    'module' => 'customer',
                    'category' => 'Customer',
                    'view' => 1,
                    'description' => 'Permissions',
                    ))
                    && (!isset($options['skip_permissions']) || !$options['skip_permissions'])
                ){
                    if(!isset($options['full']) || !$options['full']){
                        return '#';
                    }else{
                        return isset($options['text']) ? $options['text'] : 'N/A';
                    }

                }
            }else{
                // we assume we're linking to a staff user.
                if(
                    !module_security::has_feature_access(array(
                        'name' => 'Users',
                        'module' => 'user',
                        'category' => 'Config',
                        'description' => 'Permissions',
                        'view' => 1,
                    ))
                    && (int)$user_id != module_security::get_loggedin_id()
                ){
                    if(!isset($options['full']) || !$options['full']){
                        return '#';
                    }else{
                        return isset($options['text']) ? $options['text'] : 'N/A';
                    }
                }

            }
        }*/

		if ( $bubble_to_module ) {
			global $plugins;
			if ( isset( $plugins[ $bubble_to_module['module'] ] ) ) {
				$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(
					'skip_permissions' => isset( $options['skip_permissions'] ) ? $options['skip_permissions'] : false
				), $link_options );
				module_cache::put( 'user', $link_cache_key, $link, $link_cache_timeout );

				return $link;
			}
		}
		// return the link as-is, no more bubbling or anything.
		// pass this off to the global link_generate() function
		$link = link_generate( $link_options );
		module_cache::put( 'user', $link_cache_key, $link, $link_cache_timeout );

		return $link;


	}


	/**
	 * @param       $user_id
	 * @param bool  $full
	 * @param array $data
	 * @param bool  $skip_permissions - this is only used in security.php during the password reset.
	 *
	 * @return mixed|string
	 */
	public static function link_open( $user_id, $full = false, $data = array(), $skip_permissions = false ) {
		return self::link_generate( $user_id, array(
			'full'             => $full,
			'data'             => $data,
			'skip_permissions' => $skip_permissions
		) );
	}


	/**
	 * @param       $user_id
	 * @param bool  $full
	 * @param array $data
	 * @param bool  $skip_permissions only used in security.php during password reset
	 *
	 * @return mixed|string
	 */
	public static function link_open_contact( $user_id, $full = false, $data = array(), $skip_permissions = false ) {
		return self::link_generate( $user_id, array(
			'type'             => 'contact',
			'full'             => $full,
			'data'             => $data,
			'skip_permissions' => $skip_permissions
		) );
	}


	public function process() {
		if ( _DEMO_MODE && isset( $_REQUEST['user_id'] ) && (int) $_REQUEST['user_id'] > 0 && (int) $_REQUEST['user_id'] <= 4 ) {
			set_error( 'Sorry no changes to demo users. Please create a new user.' );
			redirect_browser( $this->link_open( $_REQUEST['user_id'] ) );
		}
		$errors = array();
		if ( isset( $_REQUEST['butt_del_contact'] ) && $_REQUEST['butt_del_contact'] && $_REQUEST['user_id'] && $_REQUEST['user_id'] != 1 && self::can_i( 'delete', 'Contacts', 'Customer' ) ) {
			$data = self::get_user( $_REQUEST['user_id'] );
			if ( module_form::confirm_delete( 'user_id', "Really delete contact: " . $data['name'], self::link_open_contact( $_REQUEST['user_id'] ) ) ) {
				$this->delete_user( $_REQUEST['user_id'] );
				set_message( "Contact deleted successfully" );
				redirect_browser( module_customer::link_open( $data['customer_id'] ) );
			}
		} else if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['user_id'] && self::can_i( 'delete', 'Users', 'Config' ) ) {
			$data = self::get_user( $_REQUEST['user_id'] );
			if ( module_form::confirm_delete( 'user_id', "Really delete user: " . $data['name'], self::link_open( $_REQUEST['user_id'] ) ) ) {
				$this->delete_user( $_REQUEST['user_id'] );
				set_message( "User deleted successfully" );
				redirect_browser( self::link_open( false ) );
			}
		} else if ( "save_user" == $_REQUEST['_process'] ) {
			$user_id = (int) $_REQUEST['user_id'];
			if ( $user_id == 1 && module_security::get_loggedin_id() != 1 ) {
				set_error( 'Sorry, only the Administrator can access this page.' );
				redirect_browser( _UCM_HOST . _BASE_HREF );
			}
			// check create permissions.
			$use_master_key = $this->get_contact_master_key();

			// are we creating or editing a user?
			if ( ! $user_id ) {
				$method = 'create';
			} else {
				$method        = 'edit';
				$existing_user = module_user::get_user( $user_id, true, false );
				if ( ! $existing_user || $existing_user['user_id'] != $user_id ) {
					$user_id = false;
					$method  = 'create';
				}
			}


			if ( isset( $_POST[ $use_master_key ] ) && $_POST[ $use_master_key ] ) {
				if ( ! module_user::can_i( $method, 'Contacts', 'Customer' ) ) {
					set_error( 'No permissions to ' . $method . ' contacts' );
					redirect_browser( module_customer::link_open( $_POST['customer_id'] ) );
				}
			} else if ( ! module_user::can_i( $method, 'Users', 'Config' ) ) {
				set_error( 'No permissions to ' . $method . ' users' );
				redirect_browser( module_user::link_open( false ) );
			}

			$user_id = $this->save_user( $user_id, $_POST );
			if ( $use_master_key && isset( $_REQUEST[ $use_master_key ] ) && $_REQUEST[ $use_master_key ] ) {
				set_message( "Customer contact saved successfully" );
				redirect_browser( $this->link_open_contact( $user_id ) );
			} else {
				set_message( "User saved successfully" );
				redirect_browser( $this->link_open( $user_id ) );
			}

		}/*else if("save_contact" == $_REQUEST['_process']){
			$user_id = $this->save_contact($_POST['user_id'],$_POST);
			$_REQUEST['_redirect'] = $this->link_open_contact(false);
			if($user_id){
				set_message("Contact saved successfully");
			}else{
				// todo error creating contact
			}
		}*/
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}

	public static function get_contact_master_key() {

		// contacts can either be for a customer or a vendor
		$master_keys    = array( 'customer', 'vendor' );
		$use_master_key = false;
		foreach ( $master_keys as $master_key ) {
			if ( isset( $_REQUEST[ $master_key . '_id' ] ) ) { // && $_REQUEST[$master_key.'_id']
				$use_master_key = $master_key . '_id';
				break;
			}
		}
		if ( ! $use_master_key ) {
			foreach ( $master_keys as $master_key ) {
				if ( isset( $_REQUEST['m'] ) && in_array( $master_key, $_REQUEST['m'] ) ) {
					$use_master_key = $master_key . '_id';
					break;
				}
			}
		}

		return $use_master_key;
	}


	public static function get_statuses() {
		return array(
			1 => 'Active',
			0 => 'Inactive',
		);
	}

	public static function get_users( $search = array(), $mysql = false ) {
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
		// build up a custom search sql query based on the provided search fields
		$sql   = "SELECT *,u.user_id AS id ";
		$sql   .= ", u.name AS name ";
		$from  = " FROM `" . _DB_PREFIX . "user` u ";
		$where = " WHERE 1 ";
		$where .= " AND ( (u.customer_id = 0 OR u.customer_id IS NULL) AND (u.vendor_id = 0 OR u.vendor_id IS NULL)) ";
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' OR ";
			$where .= " u.email LIKE '%$str%' OR ";
			$where .= " u.phone LIKE '%$str%' OR ";
			$where .= " u.mobile LIKE '%$str%' ";
			$where .= ' ) ';
		}
		if ( isset( $search['customer_id'] ) && $search['customer_id'] ) {
			/*$str = db_escape($search['customer_id']);
			$where .= " AND u.customer_id = '$str'";
            $sql .= " , c.primary_user_id AS is_primary ";
            $from .= " LEFT JOIN `"._DB_PREFIX."customer` c ON u.customer_id = c.customer_id ";*/
			set_error( 'Bad usage of get_user() - please report this error.' );

			return array();
		}
		if ( isset( $search['security_role_id'] ) && (int) $search['security_role_id'] > 0 ) {
			$str   = (int) $search['security_role_id'];
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "user_role` ur ON u.user_id = ur.user_id";
			$where .= " AND ur.security_role_id = $str";
		}
		foreach ( array( 'email' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` LIKE '$str'";
			}
		}
		foreach ( array( 'is_staff', 'split_hours' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` = '$str'";
			}
		}
		if ( class_exists( 'module_customer', false ) ) {
			switch ( module_user::get_user_data_access() ) {
				case _USER_ACCESS_ALL:
					// all user accounts.
					break;
				case _USER_ACCESS_ME:
					$where .= " AND u.`user_id` = " . (int) module_security::get_loggedin_id();
					break;
				case _USER_ACCESS_CONTACTS:
					$where .= " AND u.`customer_id` > 0 ";
					break;
			}
			/*switch(module_customer::get_customer_data_access()){
                case _CUSTOMER_ACCESS_ALL:
                    // all customers! so this means all jobs!
                    break;
                case _CUSTOMER_ACCESS_CONTACTS:
                case _CUSTOMER_ACCESS_TASKS:
                case _CUSTOMER_ACCESS_STAFF:
                    $valid_customer_ids = module_security::get_customer_restrictions();
                    if(count($valid_customer_ids)){
                        $where .= " AND u.customer_id IN ( ";
                        foreach($valid_customer_ids as $valid_customer_id){
                            $where .= (int)$valid_customer_id.", ";
                        }
                        $where = rtrim($where,', ');
                        $where .= " )";
                    }
            }*/
		}
		$group_order = ' GROUP BY u.user_id ORDER BY u.name'; // stop when multiple company sites have same region
		$sql         = $sql . $from . $where . $group_order;
		if ( $mysql ) {
			return query( $sql );
		}
		$result = qa( $sql );
		module_security::filter_data_set( "user", $result );

		return $result;
		//		return get_multiple("user",$search,"user_id","fuzzy","name");

	}

	public static function get_contact_customer_links( $user_id ) {
		$sql = "SELECT * FROM `" . _DB_PREFIX . "user_customer_rel` WHERE `primary` = '" . (int) $user_id . "' OR `user_id` = '" . (int) $user_id . "'";

		return qa( $sql );
	}

	public static function get_contacts( $search = array(), $new_security_check = false, $as_array = true ) {
		// limit based on customer id

		// build up a custom search sql query based on the provided search fields
		$sql   = "SELECT u.*,u.user_id AS id ";
		$sql   .= ", u.name AS name ";
		$from  = " FROM `" . _DB_PREFIX . "user` u ";
		$where = " WHERE (u.customer_id > 0 OR u.vendor_id > 0) ";
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' OR ";
			$where .= " u.email LIKE '%$str%' OR ";
			$where .= " u.phone LIKE '%$str%' OR ";
			$where .= " u.mobile LIKE '%$str%' ";
			$where .= ' ) ';
		}
		if ( isset( $search['customer_id'] ) ) {
			$sql  .= ", c.* ";
			$sql  .= " , c.primary_user_id AS is_primary ";
			$from .= " LEFT JOIN `" . _DB_PREFIX . "customer` c ON u.customer_id = c.customer_id ";
			$str  = (int) $search['customer_id'];
			if ( $str > 0 ) {
				$where .= " AND u.customer_id = '$str'";
			} else {
				// searching all customers
				$where .= " AND u.customer_id > 0 ";
			}
		} else if ( isset( $search['vendor_id'] ) ) { //$search['vendor_id']
			$sql  .= ", c.* ";
			$sql  .= " , c.primary_user_id AS is_primary ";
			$from .= " LEFT JOIN `" . _DB_PREFIX . "vendor` c ON u.vendor_id = c.vendor_id ";
			$str  = (int) $search['vendor_id'];
			if ( $str > 0 ) {
				$where .= " AND u.vendor_id = '$str'";
			} else {
				// searching all vendors
				$where .= " AND u.vendor_id > 0 ";
			}
		}
		foreach ( array( 'is_staff', 'split_hours' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` = '$str'";
			}
		}
		if ( isset( $search['security_role_id'] ) && (int) $search['security_role_id'] > 0 ) {
			$str   = (int) $search['security_role_id'];
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "user_role` ur ON u.user_id = ur.user_id";
			$where .= " AND ur.security_role_id = $str";
		}
		foreach ( array( 'email', 'user_id' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` LIKE '$str'";
			}
		}
		if ( class_exists( 'module_customer', false ) ) {
			switch ( module_user::get_user_data_access() ) {
				case _USER_ACCESS_ALL:
					// all user accounts.
					break;
				case _USER_ACCESS_ME:
					$where .= " AND u.`user_id` = " . (int) module_security::get_loggedin_id();
					break;
				case _USER_ACCESS_CONTACTS:
					$where .= " AND (u.`customer_id` > 0 OR u.`vendor_id` > 0) ";
					break;
			}
			switch ( module_customer::get_customer_data_access() ) {
				case _CUSTOMER_ACCESS_ALL:
					// all customers! so this means all jobs!
					break;
				case _CUSTOMER_ACCESS_ALL_COMPANY:
				case _CUSTOMER_ACCESS_CONTACTS:
				case _CUSTOMER_ACCESS_TASKS:
				case _CUSTOMER_ACCESS_STAFF:
					$valid_customer_ids = module_security::get_customer_restrictions();
					if ( count( $valid_customer_ids ) ) {
						$where .= " AND u.customer_id IN ( ";
						foreach ( $valid_customer_ids as $valid_customer_id ) {
							$where .= (int) $valid_customer_id . ", ";
						}
						$where = rtrim( $where, ', ' );
						$where .= " )";
					}
			}
			if ( class_exists( 'module_vendor', false ) ) {
				switch ( module_vendor::get_vendor_data_access() ) {
					case _VENDOR_ACCESS_ALL:
						// all vendors! so this means all jobs!
						break;
					case _VENDOR_ACCESS_ALL_COMPANY:
					case _VENDOR_ACCESS_CONTACTS:
					case _VENDOR_ACCESS_TASKS:
						$valid_vendor_ids = module_vendor::get_vendors( array(), array(
							'columns',
							'c.vendor_id AS id'
						) );
						if ( count( $valid_vendor_ids ) ) {
							$where .= " AND u.vendor_id IN ( ";
							foreach ( $valid_vendor_ids as $valid_vendor_id => $v ) {
								$where .= (int) $valid_vendor_id . ", ";
							}
							$where = rtrim( $where, ', ' );
							$where .= " )";
						}
				}
			}
		}
		if ( $new_security_check ) {
			// addition for the 'all customer contacts' permission
			// if user doesn't' have this permission then we only show ourselves in this list.
			$current_customer_type_id = module_customer::get_current_customer_type_id();
			$permission_check_string  = 'Customer';
			if ( $current_customer_type_id > 0 ) {
				$customer_type = module_customer::get_customer_type( $current_customer_type_id );
				if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
					$permission_check_string = $customer_type['type_name'];
				}
			}
			if ( isset( $search['customer_id'] ) && $search['customer_id'] && ! module_user::can_i( 'view', 'All ' . $permission_check_string . ' Contacts', 'Customer', 'customer' ) ) {
				$where .= " AND u.user_id = " . (int) module_security::get_loggedin_id();
				/*foreach($result as $key=>$val){
                    if($val['user_id']!=module_security::get_loggedin_id())unset($result[$key]);
                }*/
			} else if ( isset( $search['vendor_id'] ) && $search['vendor_id'] && ! module_user::can_i( 'view', 'All Vendor Contacts', 'Vendor', 'vendor' ) ) {
				$where .= " AND u.user_id = " . (int) module_security::get_loggedin_id();
			}
		}
		$group_order = ' GROUP BY u.user_id  ';
		if ( isset( $search['customer_id'] ) && $search['customer_id'] ) {
			$group_order .= 'ORDER BY c.customer_name, u.name'; // stop when multiple company sites have same region
		} else if ( isset( $search['vendor_id'] ) && $search['vendor_id'] ) {
			$group_order .= 'ORDER BY c.vendor_name, u.name'; // stop when multiple company sites have same region
		}
		$sql = $sql . $from . $where . $group_order;
		if ( $as_array ) {
			$result = qa( $sql );
		} else {
			$result = query( $sql );
		}

		//module_security::filter_data_set("user",$result);

		return $result;
		//		return get_multiple("user",$search,"user_id","fuzzy","name");

	}

	private static function _user_cache_key( $user_id, $args = array() ) {
		return 'user_' . $user_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_user( $user_id, $perms = true, $do_link = true, $basic_for_link = false ) { //,$basic=false

		$cache_key_args = func_get_args();
		$cache_key      = self::_user_cache_key( $user_id, $cache_key_args );
		$cache_timeout  = module_config::c( 'cache_objects', 60 );
		if ( $cached_item = module_cache::get( 'user', $cache_key ) ) {
			return $cached_item;
		}

		$user = get_single( "user", "user_id", $user_id );
		if ( $do_link && $user && isset( $user['linked_parent_user_id'] ) && $user['linked_parent_user_id'] && $user['linked_parent_user_id'] != $user['user_id'] ) {
			$user = self::get_user( $user['linked_parent_user_id'] );
			module_cache::put( 'user', $cache_key, $user, $cache_timeout );

			return $user;
		}
		if ( $user ) {

			if ( $basic_for_link ) {
				module_cache::put( 'user', $cache_key, $user, $cache_timeout );

				return $user;
			}

			// if this user is a linked contact to the current contact then we allow access.
			if ( isset( $user['linked_parent_user_id'] ) && $user['linked_parent_user_id'] == module_security::get_loggedin_id() ) {
				// allow all access.
			} else {

				if ( class_exists( 'module_customer', false ) ) {
					if ( $user ) {
						switch ( module_user::get_user_data_access() ) {
							case _USER_ACCESS_ME:
								if ( $user['user_id'] != module_security::get_loggedin_id() ) {
									if ( $perms ) {
										$user = false;
									} else {
										// eg for linking.
										$user['_perms'] = false;
									}
								}
								break;
							case _USER_ACCESS_CONTACTS:
								if ( ! $user['customer_id'] && ! $user['vendor_id'] && $user['user_id'] != module_security::get_loggedin_id() ) {
									// this user is not a customer contact, don't let them access it.
									if ( $perms ) {
										$user = false;
									} else {
										// eg for linking.
										$user['_perms'] = false;
									}
								}
								break;
							case _USER_ACCESS_ALL:
							default:
								// all user accounts.

								break;
						}
					}
					if ( $user && $user['customer_id'] > 0 ) {
						switch ( module_customer::get_customer_data_access() ) {
							case _CUSTOMER_ACCESS_ALL:
								// all customers! so this means all jobs!
								break;
							case _CUSTOMER_ACCESS_ALL_COMPANY:
							case _CUSTOMER_ACCESS_CONTACTS:
							case _CUSTOMER_ACCESS_TASKS:
							case _CUSTOMER_ACCESS_STAFF:
								$valid_customer_ids = module_security::get_customer_restrictions();
								$is_valid_user      = isset( $valid_customer_ids[ $user['customer_id'] ] );
								if ( ! $is_valid_user ) {
									if ( $perms ) {
										$user = false;
									} else {
										// eg for linking.
										$user['_perms'] = false;
									}
								}
						}
					}
				}

				if ( $user && $user['vendor_id'] > 0 && class_exists( 'module_vendor', false ) && module_vendor::is_plugin_enabled() ) {
					switch ( module_vendor::get_vendor_data_access() ) {
						case _VENDOR_ACCESS_ALL:
							// all vendors! so this means all jobs!
							break;
						case _VENDOR_ACCESS_ALL_COMPANY:
						case _VENDOR_ACCESS_CONTACTS:
							$valid_vendor_check = module_vendor::get_vendor( $user['vendor_id'] );
							$is_valid_user      = $valid_vendor_check && isset( $valid_vendor_check['vendor_id'] ) && $valid_vendor_check['vendor_id'] == $user['vendor_id'];
							if ( ! $is_valid_user ) {
								if ( $perms ) {
									$user = false;
								} else {
									// eg for linking.
									$user['_perms'] = false;
								}
							}
					}
				}
			}
		}
		if ( ! $user ) {
			$user           = array(
				'user_id'     => 'new',
				'customer_id' => 0,
				'vendor_id'   => 0,
				//'user_type_id' => 0,
				'name'        => '',
				'last_name'   => '',
				'email'       => '',
				'password'    => '',
				'phone'       => '',
				'mobile'      => '',
				'fax'         => '',
				'roles'       => array(),
				'language'    => module_config::c( 'default_language', 'en' ),
				'company_ids' => array(),
			);
			$use_master_key = self::get_contact_master_key();
			if ( isset( $_REQUEST[ $use_master_key ] ) ) {
				$user[ $use_master_key ] = $_REQUEST[ $use_master_key ];
			}
		} else {
			$user['roles'] = get_multiple( 'user_role', array( 'user_id' => $user_id ) );
			if ( class_exists( 'module_company', false ) && module_company::is_enabled() ) {
				$user['company_ids'] = array();
				foreach ( module_company::get_companys_by_user( $user['user_id'] ) as $company ) {
					$user['company_ids'][ $company['company_id'] ] = $company['name'];
				}
			}

			module_cache::put( 'user', $cache_key, $user, $cache_timeout );
		}

		return $user;
	}

	public function create_user( $user_data ) { // $user_type=false
		// todo - check user data is correct.
		$user_data['status_id'] = 1;
		if ( isset( $user_data['password'] ) ) {
			$user_data['password_new'] = $user_data['password'];
		}
		$user_id = $this->save_user( 0, $user_data, true );
		module_cache::clear( 'user' );

		//self::set_user_type($user_id,$user_type);
		return $user_id;
	}

	public function save_user( $user_id, $data, $from_public = false ) {
		$use_master_key = $this->get_contact_master_key();
		if ( $from_public ) {
			$user_id = 0;
		} else {
			if ( $use_master_key && isset( $data[ $use_master_key ] ) && $data[ $use_master_key ] ) {
				if ( ! module_user::can_i( 'edit', 'Contacts', 'Customer' ) ) {
					set_error( 'Unable to edit contacts.' );

					return false;
				}
			} else if ( ! self::can_i( 'edit', 'Users', 'Config' ) ) {
				set_error( 'Unable to edit users.' );

				return false;
			}
			$user_id = (int) $user_id;
		}
		$temp_user = array();
		if ( $user_id > 0 ) {
			// check permissions
			$temp_user = $this->get_user( $user_id, true, false );
			if ( ! $temp_user || $temp_user['user_id'] != $user_id || isset( $temp_user['_perms'] ) ) {
				$user_id = false;
			}
		}
		if ( ! $user_id && ! $from_public ) {
			if ( $use_master_key && isset( $data[ $use_master_key ] ) && $data[ $use_master_key ] ) {
				if ( ! module_user::can_i( 'create', 'Contacts', 'Customer' ) ) {
					set_error( 'Unable to create new contacts.' );

					return false;
				}
			} else if ( ! self::can_i( 'create', 'Users', 'Config' ) ) {
				set_error( 'Unable to create new users.' );

				return false;
			}
		} else if ( $user_id == 1 && module_security::get_loggedin_id() != 1 ) {
			set_error( 'Sorry only the administrator can modify this account' );
		}
		// check the customer id is valid assignment to someone who has these perms.
		if ( ! $from_public ) {
			if ( isset( $data['customer_id'] ) && (int) $data['customer_id'] > 0 ) {
				$temp_customer = module_customer::get_customer( $data['customer_id'] );
				if ( ! $temp_customer || $temp_customer['customer_id'] != $data['customer_id'] ) {
					unset( $data['customer_id'] );
				}
			}
			if ( isset( $data['vendor_id'] ) && (int) $data['vendor_id'] > 0 && class_exists( 'module_vendor', false ) && module_vendor::is_plugin_enabled() ) {
				$temp_vendor = module_vendor::get_vendor( $data['vendor_id'] );
				if ( ! $temp_vendor || $temp_vendor['vendor_id'] != $data['vendor_id'] ) {
					unset( $data['vendor_id'] );
				}
			}
		}
		if ( isset( $data['password'] ) ) {
			unset( $data['password'] );
		}
		// we do the password hash thing here.
		if ( isset( $data['password_new'] ) && strlen( $data['password_new'] ) ) {
			// an admin is trying to set the password for this account.
			// same permissions checks as on the user_admin_edit_login.php page
			if ( ! $user_id || ( isset( $temp_user['password'] ) && ! $temp_user['password'] ) || module_user::can_i( 'create', 'Users Passwords', 'Config' ) || ( isset( $_REQUEST['reset_password'] ) && $_REQUEST['reset_password'] == module_security::get_auto_login_string( $user_id ) ) ) {
				// we allow the admin to set a new password without typing in previous password.
				$data['password'] = $data['password_new'];
			} else {
				set_error( 'Sorry, no permissions to set a new password.' );
			}
		} else if ( $user_id && isset( $data['password_new1'] ) && isset( $data['password_new2'] ) && strlen( $data['password_new1'] ) ) {
			// the user is trying to change their password.
			// only do this if the user has edit password permissions and their password matches.
			if ( module_user::can_i( 'edit', 'Users Passwords', 'Config' ) || $user_id == module_security::get_loggedin_id() ) {
				if ( isset( $data['password_old'] ) && ( md5( $data['password_old'] ) == $temp_user['password'] || $data['password_old'] == $temp_user['password'] ) ) {
					// correct old password
					// verify new password.
					if ( $data['password_new1'] == $data['password_new2'] ) {
						$data['password'] = $data['password_new1'];
					} else {
						set_error( 'Verified password mismatch. Password unchanged.' );
					}
				} else {
					set_error( 'Old password does not match. Password unchanged.' );
				}
			} else {
				set_error( 'No permissions to change passwords' );
			}
		}
		// and we finally hash our password
		if ( isset( $data['password'] ) && strlen( $data['password'] ) > 0 ) {
			$data['password'] = md5( $data['password'] );
			// if you change md5 also change it in customer import.
			// todo - salt? meh.
		}
		$user_id = update_insert( "user_id", $user_id, "user", $data );

		$use_master_key = $this->get_contact_master_key();
		// this will be customer_id or supplier_id
		if (
			$use_master_key && ( isset( $data[ $use_master_key ] ) && $data[ $use_master_key ] )
		) {
			if ( $user_id ) {
				if ( isset( $data['customer_primary'] ) && $data['customer_primary'] ) {
					// update the customer/supplier to mark them as primary or not..
					switch ( $use_master_key ) {
						case 'customer_id':
							module_customer::set_primary_user_id( $data['customer_id'], $user_id );
							break;
						case 'vendor_id':
							module_vendor::set_primary_user_id( $data['vendor_id'], $user_id );
							break;
					}
				} else {
					// check if this contact was the old customer/supplier primary and
					switch ( $use_master_key ) {
						case 'customer_id':
							$customer_data = module_customer::get_customer( $data['customer_id'] );
							if ( $customer_data['primary_user_id'] == $user_id ) {
								module_customer::set_primary_user_id( $data['customer_id'], 0 );
							}
							break;
						case 'vendor_id':
							$vendor_data = module_vendor::get_vendor( $data['vendor_id'] );
							if ( $vendor_data['primary_user_id'] == $user_id ) {
								module_vendor::set_primary_user_id( $data['vendor_id'], 0 );
							}
							break;
					}
				}
			}
		}
		if ( ! $from_public ) {

			// hack for linked user accounts.
			if ( $user_id && isset( $data['link_customers'] ) && $data['link_customers'] == 'yes' && isset( $data['link_user_ids'] ) && is_array( $data['link_user_ids'] ) && isset( $data['email'] ) && $data['email'] ) {
				$others = module_user::get_contacts( array( 'email' => $data['email'] ) );
				foreach ( $data['link_user_ids'] as $link_user_id ) {
					if ( ! (int) $link_user_id ) {
						continue;
					}
					if ( $link_user_id == $user_id ) {
						continue;
					} // shouldnt happen
					foreach ( $others as $other ) {
						if ( $other['user_id'] == $link_user_id ) {
							// success! they'renot trying to hack us.
							$sql = "REPLACE INTO `" . _DB_PREFIX . "user_customer_rel` SET user_id = '" . (int) $link_user_id . "', customer_id = '" . (int) $other['customer_id'] . "', `primary` = " . (int) $user_id;
							query( $sql );
							update_insert( 'user_id', $link_user_id, 'user', array( 'linked_parent_user_id' => $user_id ) );
						}
					}
				}
				update_insert( 'user_id', $user_id, 'user', array( 'linked_parent_user_id' => $user_id ) );
			}

			if ( $user_id && isset( $data['unlink'] ) && $data['unlink'] == 'yes' ) {
				$sql = "DELETE FROM `" . _DB_PREFIX . "user_customer_rel` WHERE user_id = '" . (int) $user_id . "'";
				query( $sql );
				update_insert( 'user_id', $user_id, 'user', array( 'linked_parent_user_id' => 0 ) );
			}

			handle_hook( "address_block_save", $this, "physical", "user", "user_id", $user_id );
			handle_hook( "address_block_save", $this, "postal", "user", "user_id", $user_id );

			if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				module_extra::save_extras( 'user', 'user_id', $user_id );
			}

			// find current role / permissions
			$user_data           = $this->get_user( $user_id );
			$previous_user_roles = $user_data['roles'];
			$re_save_role_perms  = false;

			// hack to support only 1 role (we may support multi-role in the future)
			// TODO: check we have permissions to set this role id, otherwise anyone can set their own role.
			if ( isset( $_REQUEST['role_id'] ) ) {
				$sql = "DELETE FROM `" . _DB_PREFIX . "user_role` WHERE user_id = '" . (int) $user_id . "'";
				query( $sql );
				if ( (int) $_REQUEST['role_id'] > 0 ) {
					if ( ! isset( $previous_user_roles[ $_REQUEST['role_id'] ] ) ) {
						$re_save_role_perms = (int) $_REQUEST['role_id'];
					}
					$_REQUEST['role'] = array(
						$_REQUEST['role_id'] => 1,
					);
				}
			}
			// save users roles (support for multi roles in future - but probably will never happen)
			if ( isset( $_REQUEST['role'] ) && is_array( $_REQUEST['role'] ) ) {
				foreach ( $_REQUEST['role'] as $role_id => $tf ) {
					$this->add_user_to_role( $user_id, $role_id );
				}
			}

			/*if ( $re_save_role_perms ) {
				// copy role permissiosn to user permissions
				$sql = "DELETE FROM `" . _DB_PREFIX . "user_perm` WHERE user_id = " . (int) $user_id;
				query( $sql );
				// update - we are not relying on these permissions any more.
				// if the user has a role assigned, we use those permissions period
				// we ignore all permissions in the user_perm table if the user has a role.
				// if the user doesn't have a role, then we use these user_perm permissions.
				$security_role = module_security::get_security_role($re_save_role_perms);
				foreach($security_role['permissions'] as $security_permission_id => $d){
					$sql = "INSERT INTO `"._DB_PREFIX."user_perm` SET user_id = ".(int)$user_id.", security_permission_id = '".(int)$security_permission_id."'";
					foreach(module_security::$available_permissions as $perm){
						$sql .= ", `".$perm."` = ".(int)$d[$perm];
					}
					query($sql);
				}
			} else if ( isset( $_REQUEST['permission'] ) && is_array( $_REQUEST['permission'] ) ) {
				$sql = "DELETE FROM `" . _DB_PREFIX . "user_perm` WHERE user_id = '" . (int) $user_id . "'";
				query( $sql );
				// update permissions for this user.
				foreach ( $_REQUEST['permission'] as $security_permission_id => $permissions ) {
					$actions = array();
					foreach ( module_security::$available_permissions as $permission ) {
						if ( isset( $permissions[ $permission ] ) && $permissions[ $permission ] ) {
							$actions[ $permission ] = 1;
						}
					}
					$sql = "REPLACE INTO `" . _DB_PREFIX . "user_perm` SET user_id = '" . (int) $user_id . "', security_permission_id = '" . (int) $security_permission_id . "' ";
					foreach ( $actions as $permission => $tf ) {
						$sql .= ", `" . db_escape( $permission ) . "` = 1";
					}
					query( $sql );
				}

			}*/


			/*global $plugins;
			if($user_id && isset($data['user_type_id']) && $data['user_type_id'] == 1 && $data['site_id']){
				// update the site.
				$plugins['site']->set_primary_user_id($data['site_id'],$user_id);
			}else{
				//this use isn't (or isnt any more) the sites primary user.
				// unset this if he was the primary user before
				$site_data = $plugins['site']->get_site($data['site_id']);
				if(isset($site_data['primary_user_id']) && $site_data['primary_user_id'] == $user_id){
					$plugins['site']->set_primary_user_id($data['site_id'],0);
				}
			}*/

			// save the company information if it's available
			if ( class_exists( 'module_company', false ) && module_company::can_i( 'edit', 'Company' ) && module_company::is_enabled() && module_user::can_i( 'edit', 'User' ) ) {
				if ( isset( $_REQUEST['available_user_company'] ) && is_array( $_REQUEST['available_user_company'] ) ) {
					$selected_companies = isset( $_POST['user_company'] ) && is_array( $_POST['user_company'] ) ? $_POST['user_company'] : array();
					foreach ( $_REQUEST['available_user_company'] as $company_id => $tf ) {
						if ( ! isset( $selected_companies[ $company_id ] ) || ! $selected_companies[ $company_id ] ) {
							// remove user from this company
							module_company::delete_user( $company_id, $user_id );
						} else {
							// add user to this company (if they are not already existing)
							module_company::add_user_to_company( $company_id, $user_id );
						}
					}
				}
			}
		}

		module_cache::clear( 'user' );

		return $user_id;
	}

	public static function add_user_to_role( $user_id, $role_id ) {
		$sql = "DELETE FROM `" . _DB_PREFIX . "user_role` WHERE user_id = '" . (int) $user_id . "'";
		query( $sql );
		$sql = "REPLACE INTO `" . _DB_PREFIX . "user_role` SET user_id = '" . (int) $user_id . "', security_role_id = '" . (int) $role_id . "'";
		query( $sql );
	}


	public static function print_user_summary( $user_id, $output = 'html', $fields = array() ) {
		global $plugins;
		$user_data = $plugins['user']->get_user( $user_id );
		if ( ! $fields ) {
			$fields = array( 'name' );
		}
		$user_output = '';
		foreach ( $fields as $key ) {
			if ( isset( $user_data[ $key ] ) && $user_data[ $key ] ) {
				$user_output .= $user_data[ $key ] . ', ';
			}
		}
		$user_output = rtrim( $user_output, ', ' );
		if ( $user_data ) {
			switch ( $output ) {
				case 'text':
					echo $user_output;
					break;
				case 'html':
					?>
					<span class="user">
						<a href="<?php echo $plugins['user']->link_open( $user_id ); ?>">
							<?php echo $user_output; ?>
						</a>
					</span>
					<?php
					break;
				case 'full':
					include( 'pages/user_summary.php' );
					break;
			}
		}
	}

	public static function print_contact_summary( $user_id, $output = 'html', $fields = array() ) {
		$user = self::get_user( $user_id, false );
		if ( ! $user['customer_id'] && ! $user['vendor_id'] ) {
			$user = array();
		} //  only do this for customer contact details.
		// todo: confirm the user has permissions to access this particular customer as well as this particular contact.
		if ( ! $fields ) {
			$fields = array( 'name' );
		}
		$user_output = '';
		$new_link    = false;
		foreach ( $fields as $key ) {
			foreach ( explode( '|', $key ) as $k ) {
				if ( isset( $user[ $k ] ) && $user[ $k ] ) {
					if ( $k == 'phone' || $k == 'mobile' ) {
						$new_link = 'tel:' . htmlspecialchars( $user[ $k ] );
					}
					if ( $k == 'email' ) {
						$new_link = 'mailto:' . htmlspecialchars( $user[ $k ] );
					}
					$user_output .= htmlspecialchars( $user[ $k ] );
					$user_output .= ', ';
					break;
				}
			}
		}
		$user_output = rtrim( $user_output, ', ' );
		switch ( $output ) {
			case 'text':
				echo $user_output;
				break;
			case 'html':
				?>
				<span class="user">
                    <a href="<?php echo $new_link ? $new_link : self::link_open_contact( $user_id, false, $user ); ?>">
                        <?php echo $user_output; ?>
                    </a>
                </span>
				<?php
				break;
		}

	}

	public static function print_contact_form( $user_id ) {
		$user = self::get_user( $user_id, false );
		if ( ! $user['customer_id'] && ! $user['vendor_id'] ) {
			$user = array();
		} //  only do this for customer contact details.
		// todo: confirm the user has permissions to access this particular customer as well as this particular contact.
		$show_more_button = true;
		include( 'pages/contact_admin_form.php' );

	}


	/*public function save_contact($user_id,$data){
		// user must have a customer_id
		// todo, check user has access to this customer id and they're not just messing with the contacts.
		$use_master_key = $this->get_contact_master_key();
        // this will be customer_id or supplier_id
        if(
            (isset($data[$use_master_key]) && $data[$use_master_key])
        ){
            $data['user_type'] = 1; // marks the 'user' as a contact in the db.
            $user_id = update_insert("user_id",$user_id,"user",$data);
            if($user_id){
                global $plugins;
                if(isset($data['customer_primary']) && $data['customer_primary']){
                    // update the customer/supplier to mark them as primary or not..
                    switch($use_master_key){
                        case 'customer_id':
                            $plugins['customer']->set_primary_user_id($data['customer_id'],$user_id);
                            break;
                    }
                }else{
                    // check if this contact was the old customer/supplier primary and
                    switch($use_master_key){
                        case 'customer_id':
                            $customer_data = $plugins['customer']->get_customer($data['customer_id']);
                            if($customer_data['primary_user_id'] == $user_id){
                                $plugins['customer']->set_primary_user_id($data['customer_id'],0);
                            }
                            break;
                    }
                }
            }
        }
        module_extra::save_extras('user','user_id',$user_id);

        return $user_id;
	}*/
	public static function delete_user( $user_id ) {
		$user_id = (int) $user_id;
		if ( _DEMO_MODE && $user_id == 1 ) {
			return;
		}
		$sql = "DELETE FROM " . _DB_PREFIX . "user WHERE user_id = '" . $user_id . "' LIMIT 1";
		query( $sql );
		module_note::note_delete( "user", $user_id );
		$sql = "DELETE FROM " . _DB_PREFIX . "user_customer_rel WHERE user_id = '" . $user_id . "'";
		query( $sql );
		$sql = "DELETE FROM `" . _DB_PREFIX . "user_role` WHERE user_id = '" . (int) $user_id . "'";
		query( $sql );
		$sql = "UPDATE " . _DB_PREFIX . "user SET linked_parent_user_id = 0 WHERE linked_parent_user_id = '" . $user_id . "'";
		query( $sql );
	}

	public function login_link( $user_id ) {
		return module_security::generate_auto_login_link( $user_id );
	}


	public static function get_user_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'User Account Access', array(
				_USER_ACCESS_ALL,
				_USER_ACCESS_ME,
				_USER_ACCESS_CONTACTS,
			) );
		} else {
			return true;
		}
	}


	/*
    array(
    'category' => 'Ticket',
    'name' => 'Tickets',
    'module' => 'ticket',
    'edit' => 1,
    )
    */

	static $users_by_perm_cache = array();

	public static function get_users_by_permission( $access_requirements ) {

		$cache_key = md5( serialize( $access_requirements ) );
		if ( isset( self::$users_by_perm_cache[ $cache_key ] ) ) {
			return self::$users_by_perm_cache[ $cache_key ];
		}

		// find all the users that have these permissions set.
		$permission             = get_single( 'security_permission', array(
			'name',
			'category',
			'module',
		), array(
			$access_requirements['name'],
			$access_requirements['category'],
			$access_requirements['module'],
		) );
		$security_permission_id = false;
		if ( $permission ) {
			$security_permission_id = $permission['security_permission_id'];
		}
		if ( ! $security_permission_id ) {
			return array();
		}
		// we have the ID!
		// time to check the actual permission now.
		$check_for_permissions = array();
		foreach ( module_security::$available_permissions as $available_permission ) {
			if ( isset( $access_requirements[ $available_permission ] ) ) {
				// we want users with this permission.
				$check_for_permissions[ $available_permission ] = true;
			}
		}
		//echo $security_permission_id;
		//print_r($check_for_permissions);
		// do a query to find out permissions based on the users role, or by the hardcoded assigned roles.
		$sql = "SELECT u.*, u.user_id AS id FROM `" . _DB_PREFIX . "user` u WHERE u.user_id IN (";
		$sql .= "SELECT ur.user_id FROM `" . _DB_PREFIX . "security_role_perm` sp LEFT JOIN `" . _DB_PREFIX . "user_role` ur
        USING (security_role_id) WHERE sp.security_permission_id = $security_permission_id";
		foreach ( $check_for_permissions as $permission_type => $tf ) {
			$sql .= " AND sp.`" . $permission_type . "` = 1";
		}
		$sql .= ')'; // OR u.user_id IN (';
		// no role set - just use hardcoded perms on the user account.
		//$sql .= "SELECT up.user_id FROM `"._DB_PREFIX."user_perm` up WHERE security_permission_id = $security_permission_id";
		//foreach($check_for_permissions as $permission_type => $tf){
		//$sql .= " AND up.`".$permission_type."` = 1";
		//}
		//$sql .= ')';
		$sql .= ' OR u.user_id IN (' . implode( module_security::get_super_admin_ids() ) . ')';
		//        echo $sql;
		$users = qa( $sql );

		self::$users_by_perm_cache[ $cache_key ] = $users;

		return $users;

	}

	public static function is_staff_member( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id > 0 ) {
			$staff_members = self::get_staff_members();
			if ( isset( $staff_members[ $user_id ] ) ) {
				return true;
			}
		}

		return false;
	}

	private static $_staff_members_cache = false;

	public static function get_staff_members() {
		if ( is_array( self::$_staff_members_cache ) ) {
			return self::$_staff_members_cache;
		}
		// todo: a different kinda perimssion outlines staff members maybe?
		if ( module_config::c( 'staff_by_flag', 0 ) ) {
			$label = 'Staff Member';
			// seed security permission:
			module_security::can_user( module_security::get_loggedin_id(), $label );
			// find any that exist.
			$staff = self::get_users_by_permission(
				array(
					'category'    => _LABEL_USER_SPECIFIC,
					'name'        => 'Staff Member',
					'module'      => 'config',
					'view'        => 1,
					'description' => 'checkbox',
				)
			);
		} else {
			$staff = self::get_users_by_permission(
				array(
					'category' => 'Job',
					'name'     => 'Job Tasks',
					'module'   => 'job',
					'edit'     => 1,
				)
			);
		}
		foreach ( $staff as $staff_id => $s ) {
			if ( isset( $s['is_staff'] ) && $s['is_staff'] == 0 ) {
				// user has staff option manually disabled override the role settings.
				unset( $staff[ $staff_id ] );
			}
		}
		// add any staff members that have the new is_staff flag set
		$staff_users = self::get_users( array( 'is_staff' => 1 ) );
		if ( ! is_array( $staff_users ) ) {
			$staff_users = array();
		}
		$staff_contacts = self::get_contacts( array( 'is_staff' => 1 ) );
		if ( ! is_array( $staff_contacts ) ) {
			$staff_contacts = array();
		}

		$staff = $staff + $staff_users + $staff_contacts;
		if ( module_config::c( 'staff_remove_admin', 0 ) && isset( $staff[ module_config::c( 'staff_remove_admin', 0 ) ] ) ) {
			unset( $staff[ module_config::c( 'staff_remove_admin', 0 ) ] );
		}
		if ( module_config::c( 'staff_first_last', 1 ) ) {
			foreach ( $staff as $staff_id => $s ) {
				$staff[ $staff_id ]['name'] = $s['name'] . ' ' . $s['last_name'];
			}
		}
		self::$_staff_members_cache = $staff;

		return self::$_staff_members_cache;
	}


	public static function hook_filter_var_contact_list( $call, $attributes ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}
		foreach (
			module_user::get_contacts( array(
				'customer_id' => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false,
			) ) as $contact
		) {
			$attributes[ $contact['user_id'] ] = $contact['name'] . ' ' . $contact['last_name'];
		}

		return $attributes;
	}

	public static function hook_filter_var_staff_list( $call, $attributes ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}
		foreach ( module_user::get_staff_members() as $contact ) {
			$attributes[ $contact['user_id'] ] = $contact['name'] . ' ' . $contact['last_name'];
		}

		return $attributes;
	}

	public static function get_replace_fields( $user_id ) {

		// do we use the primary contact or
		$contact_data = module_user::get_user( $user_id );
		//print_r($contact_data);exit;
		if ( $contact_data && $contact_data['user_id'] != $user_id ) {
			$contact_data = array(
				'user_id'     => 0,
				'customer_id' => 0,
				'name'        => '',
				'last_name'   => '',
				'email'       => '',
				'password'    => '',
				'phone'       => '',
				'mobile'      => '',
				'fax'         => '',
			);
		}

		$contact_data['password'] = '';

		$contact_data['first_name'] = $contact_data['name'];

		// addition. find all extra keys for this customer and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			// and the primary contact
			$all_extra_fields = module_extra::get_defaults( 'user' );
			foreach ( $all_extra_fields as $e ) {
				$contact_data[ $e['key'] ] = _l( 'N/A' );
			}
			if ( $contact_data && $contact_data['user_id'] ) {
				// and find the ones with values:
				$extras = module_extra::get_extras( array( 'owner_table' => 'user', 'owner_id' => $contact_data['user_id'] ) );
				foreach ( $extras as $e ) {
					$contact_data[ $e['extra_key'] ] = $e['extra'];
				}
			}
		}

		return $contact_data;
	}


	public static function api_filter_user( $hook, $response, $endpoint, $method ) {
		$response['user'] = true;
		switch ( $method ) {
			case 'get':
				// return details about the currently logged in user.
				$user_details = self::get_user( module_security::get_loggedin_id() );
				foreach (
					array(
						'user_id',
						'customer_id',
						'name',
						'last_name',
						'email',
						'phone',
						'mobile',
						'fax'
					) as $field
				) {
					$response[ $field ] = $user_details[ $field ];
				}
				break;
			case 'search_contacts':
				// return details about the currently logged in user.
				$search               = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				$response['contacts'] = self::get_contacts( $search );
				break;
			case 'create_contact':
				// create a contact.
				$contact_data = isset( $_POST['contact_data'] ) && is_array( $_POST['contact_data'] ) ? $_POST['contact_data'] : array();

				$response['contact_id'] = 0;
				if (
					! empty( $contact_data )
					&& ! empty( $contact_data['customer_id'] )
					&& ! empty( $contact_data['contact_first_name'] )
					&& ! empty( $contact_data['contact_email'] )
					&& ! empty( $contact_data['contact_phone'] )
				) {
					$save_data = array(
						'customer_id' => (int) $contact_data['customer_id'],
						'email'       => $contact_data['contact_email'],
						'name'        => $contact_data['contact_first_name'],
						'last_name'   => ! empty( $contact_data['contact_last_name'] ) ? $contact_data['contact_last_name'] : '',
						'phone'       => $contact_data['contact_phone'],
						'fax'         => ! empty( $contact_data['contact_fax'] ) ? $contact_data['contact_fax'] : '',
						'mobile'      => ! empty( $contact_data['contact_mobile'] ) ? $contact_data['contact_mobile'] : '',
					);
					global $plugins;
					$user_id = $plugins['user']->create_user( $save_data, 'contact' );
					if ( $user_id ) {
						$response['contact_id'] = $user_id;
					}

				}

				break;
		}

		return $response;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_customer::can_i( 'view', 'Customers' ) ) {

			$customer_id = ! empty( $search_options['vars']['lookup_customer_id'] ) ? (int) $search_options['vars']['lookup_customer_id'] : 0;
			$res         = module_user::get_contacts( array( 'customer_id' => $customer_id ) );

			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['user_id'],
					'value' => $row['name'] . ' ' . $row['last_name']
				);
			}
		}

		return $result;
	}

	public function autocomplete_display( $key = 0, $search_options = array() ) {
		if ( ! empty( $search_options['plugin'] ) && self::db_table_exists( $search_options['plugin'] ) ) {
			$fields = get_fields( $search_options['plugin'] );
			if ( ! empty( $fields[ $search_options['key'] ] ) && ! empty( $fields[ $search_options['display_key'] ] ) ) {
				$row = get_single( $search_options['plugin'], $search_options['key'], $key );
				if ( ! empty( $row ) ) {
					if ( ! empty( $search_options['return_link'] ) ) {
						switch ( $search_options['display_key'] ) {
							case 'name':
								return array( $row['name'] . ' ' . $row['last_name'], static::link_open( $key, false ) );
								break;
							default:
								return array( $row[ $search_options['display_key'] ], static::link_open( $key, false ) );
						}

					}

					return $row[ $search_options['display_key'] ];
				}
			}
		}

		return '';
	}


	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'user' );
		if ( ! isset( $fields['last_name'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `last_name` VARCHAR( 90 ) NOT NULL DEFAULT  \'\' AFTER  `name`;';
		}
		if ( ! isset( $fields['vendor_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `vendor_id` INT( 11 ) NOT NULL DEFAULT  \'0\' AFTER  `customer_id`;';
		}
		if ( ! isset( $fields['linked_parent_user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `linked_parent_user_id` INT( 11 ) NOT NULL DEFAULT  \'0\' AFTER  `customer_id`;';
		}
		if ( ! isset( $fields['is_staff'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `is_staff` TINYINT(2) NOT NULL DEFAULT  \'-1\' AFTER  `status_id`;';
		}
		if ( ! isset( $fields['split_hours'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `split_hours` TINYINT(2) NOT NULL DEFAULT  \'0\' AFTER  `is_staff`;';
		}
		if ( ! isset( $fields['hourly_rate'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user` ADD  `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `split_hours`;';
		}

		// check for indexes
		self::add_table_index( 'user', 'customer_id' );
		self::add_table_index( 'user', 'vendor_id' );
		self::add_table_index( 'user', 'linked_parent_user_id' );
		self::add_table_index( 'user', 'is_staff' );
		/*$sql_check = 'SHOW INDEX FROM `'._DB_PREFIX.'user';
        $res = qa($sql_check);
        //print_r($res);exit;
        $add_index=true;
        foreach($res as $r){
            if(isset($r['Column_name']) && $r['Column_name'] == 'customer_id'){
                $add_index=false;
            }
        }
        if($add_index){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'user` ADD INDEX ( `customer_id` );';
        }

        $add_index=true;
        foreach($res as $r){
            if(isset($r['Column_name']) && $r['Column_name'] == 'linked_parent_user_id'){
                $add_index=false;
            }
        }
        if($add_index){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'user` ADD INDEX ( `linked_parent_user_id` );';
        }*/


		$sql_check = "SHOW TABLES LIKE '" . _DB_PREFIX . "user_customer_rel'";
		$res       = qa1( $sql_check );
		if ( ! $res || ! count( $res ) ) {
			// create our new table.
			$sql .= 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'user_customer_rel` (
            `user_id` int(11) NOT NULL,
            `customer_id` int(11) NOT NULL,
            `primary` INT NOT NULL DEFAULT  \'0\',
            PRIMARY KEY (`user_id`,`customer_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		} else {
			// check primary exists
			$fields = get_fields( 'user_customer_rel' );
			if ( ! isset( $fields['primary'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'user_customer_rel` ADD  `primary` INT NOT NULL DEFAULT  \'0\'';
			}
		}

		return $sql;
	}


	public function get_install_sql() {
		ob_start();
		//`user_type_id` INT(11) NOT NULL DEFAULT '2',
		/*
CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>user_type` (
  `user_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NULL,
  `date_created` datetime NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NULL,
  `update_user_id` int(11) NULL,
  PRIMARY KEY (`user_type_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;*/
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>user` (
		`user_id` int(11) NOT NULL auto_increment,
		`customer_id` INT(11) NULL,
		`vendor_id` INT(11) NULL,
		`linked_parent_user_id` INT(11) NOT NULL DEFAULT '0',
		`status_id` INT(11) NOT NULL DEFAULT '1',
		`is_staff` TINYINT(2) NOT NULL DEFAULT '-1',
		`split_hours` TINYINT(2) NOT NULL DEFAULT '0',
		`hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT '0',
		`email` varchar(255) NOT NULL DEFAULT  '',
		`password` varchar(255) NOT NULL DEFAULT  '',
		`name` varchar(255) NOT NULL DEFAULT  '',
		`last_name` varchar(255) NOT NULL DEFAULT  '',
		`phone` varchar(255) NOT NULL DEFAULT  '',
		`fax` varchar(255) NOT NULL DEFAULT  '',
		`mobile` varchar(255) NOT NULL DEFAULT  '',
		`language` varchar(4) NOT NULL DEFAULT  '',
		`date_created` date NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`user_id`),
		KEY `customer_id` (`customer_id`),
		KEY `vendor_id` (`vendor_id`),
		KEY `is_staff` (`is_staff`),
		KEY `linked_parent_user_id` (`linked_parent_user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		INSERT INTO `<?php echo _DB_PREFIX; ?>user` VALUES (1, 0, 0, 0, 1, -1, 0, 0, 'admin@example.com', 'password', 'Administrator', '',  '+61 7 55 123 456', '+61 7 56 321 654', '+61419789789', 'en', NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>user` VALUES (2, 0, 0, 0, 1, -1, 0, 0, 'user@example.com', 'password', 'User', '', '+61 7 55 123 456', '+61 7 56 321 654', '+61419789789', 'en', NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>user` VALUES (3, 1, 0, 0, 1, -1, 0, 0, 'user1@example.com', 'password', 'Contact 1', '',  '+61 7 55 123 456', '+61 7 56 321 654', '+61419789789', 'en', NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>user` VALUES (4, 2, 0, 0, 1, -1, 0, 0, 'user2@example.com', 'password', 'Contact 2', '', '+61 7 55 123 456', '+61 7 56 321 654', '+61419789789', 'en', NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>user` VALUES (5, 0, 1, 0, 1, 1, 1, 50, 'staff@example.com', 'password', 'Staff Member', '', '+61 7 55 123 456', '+61 7 56 321 654', '+61419789789', 'en', NOW(), NOW());

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>user_perm` (
		`user_id` int(11) NOT NULL,
		`security_permission_id` int(11) NOT NULL,
		`view` tinyint(4) NOT NULL DEFAULT '0',
		`edit` tinyint(4) NOT NULL DEFAULT '0',
		`delete` tinyint(4) NOT NULL DEFAULT '0',
		`create` tinyint(4) NOT NULL DEFAULT '0',
		`date_created` datetime NULL,
		`date_updated` datetime NULL,
		`create_user_id` int(11) NULL,
		`update_user_id` int(11) NULL,
		PRIMARY KEY (`user_id`,`security_permission_id`),
		KEY `security_permission_id` (`security_permission_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>user_role` (
		`user_id` int(11) NOT NULL,
		`security_role_id` int(11) NOT NULL,
		PRIMARY KEY (`user_id`,`security_role_id`),
		KEY `security_role_id` (`security_role_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>user_customer_rel` (
		`user_id` int(11) NOT NULL,
		`customer_id` int(11) NOT NULL,
		`primary` INT NOT NULL DEFAULT  '0',
		PRIMARY KEY (`user_id`,`customer_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		ALTER TABLE `<?php echo _DB_PREFIX; ?>user_perm`
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>user_perm_ibfk_1`  FOREIGN KEY (`security_permission_id`) REFERENCES `<?php echo _DB_PREFIX; ?>security_permission` (`security_permission_id`) ON DELETE CASCADE,
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>user_perm_ibfk_2`  FOREIGN KEY (`user_id`) REFERENCES `<?php echo _DB_PREFIX; ?>user` (`user_id`) ON DELETE CASCADE;

		ALTER TABLE `<?php echo _DB_PREFIX; ?>user_role`
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>user_role_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `<?php echo _DB_PREFIX; ?>user` (`user_id`) ON DELETE CASCADE,
		ADD CONSTRAINT `<?php echo _DB_PREFIX; ?>user_role_ibfk_2` FOREIGN KEY (`security_role_id`) REFERENCES `<?php echo _DB_PREFIX; ?>security_role` (`security_role_id`) ON DELETE CASCADE;


		<?php
		/*INSERT INTO `<?php echo _DB_PREFIX; ?>user_type` VALUES (1, 'User', NOW(), NOW(), 1, 0);
        INSERT INTO `<?php echo _DB_PREFIX; ?>user_type` VALUES (2, 'Contact', NOW(), NOW(), 1, 0);
        INSERT INTO `<?php echo _DB_PREFIX; ?>user_type` VALUES (3, 'Support', NOW(), NOW(), 1, 0);
        */

		return ob_get_clean();
	}


}