<?php


if ( defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG && function_exists( 'hook_add' ) ) {
	// here so we catch config vars sooner rather than later
	hook_add( 'config_init_vars', 'module_company::hook_config_init_vars' );
}

define( '_COMPANY_ACCESS_ALL', 'All companies in system' ); // do not change string
define( '_COMPANY_ACCESS_ASSIGNED', 'Only companies I am assigned to in staff area' ); // do not change string
define( '_COMPANY_ACCESS_CONTACT', 'Only companies I am assigned to as a contact' ); // do not change string

// todo: remove the two "Only Companies" entries from security database that would have been added to early adopters

class module_company extends module_base {

	var $links;
	public $version = 2.143;
	// 2.143 - 2016-07-10 - big update to mysqli
	// 2.142 - 2014-07-21 - fix for custom company configurations
	// 2.141 - 2014-07-21 - company permission improvement
	// 2.14 - 2014-07-16 - vendor feature
	// 2.13 - 2014-06-19 - company permission fix
	// 2.129 - 2013-11-15 - working on new UI
	// 2.128 - 2013-09-23 - support for company specific templates
	// 2.127 - 2013-09-12 - support for company specific templates
	// 2.126 - 2013-07-30 - customer delete improvement
	// 2.125 - 2013-07-29 - fix for unique company settings
	// 2.124 - 2013-07-25 - fix for missing company section
	// 2.123 - 2013-07-15 - permission improvement
	// 2.122 - 2013-07-02 - bug fix with single companies defined
	// 2.121 - 2013-06-26 - update to edit config.php if unique config variables required -should fix errors
	// 2.12 - 2013-06-21 - custom configuration variables available per company (see company_unique_config setting)
	// 2.11 - 2013-06-21 - custom configuration variables available per company (see company_unique_config setting)
	// 2.1 - 2013-06-18 - first release - basic company/customer linking

	//private static $do_company_custom_config = false;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->links           = array();
		$this->module_name     = "company";
		$this->module_position = 10;


		if ( self::can_i( 'edit', 'Company' ) ) {
			$this->links[] = array(
				"name"                => "Company",
				"p"                   => "company_settings",
				"icon"                => "icon.png",
				"args"                => array( 'company_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}

		hook_add( 'customer_deleted', 'module_company::hook_customer_deleted' );
		hook_add( 'vendor_deleted', 'module_company::hook_vendor_deleted' );
		//self::$do_company_custom_config = defined('COMPANY_UNIQUE_CONFIG') && COMPANY_UNIQUE_CONFIG; //module_config::c('company_unique_config',0);

	}


	public static function link_generate( $company_id = false, $options = array(), $link_options = array() ) {

		$key = 'company_id';
		if ( $company_id === false && $link_options ) {
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
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'company';
		}
		$options['page'] = 'company_settings';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['company_id'] = $company_id;
		$options['module']                  = 'company';
		$data                               = self::get_company( $company_id );
		$options['data']                    = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		//if(isset($data['company_id']) && $data['company_id']>0){
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'company_id',
		);
		// }
		array_unshift( $link_options, $options );

		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $company_id, $full = false ) {
		return self::link_generate( $company_id, array( 'full' => $full ) );
	}


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['company_id'] && self::can_i( 'delete', 'Company' ) ) {
			$data = self::get_company( $_REQUEST['company_id'] );
			if ( $data && $data['company_id'] == $_REQUEST['company_id'] && module_form::confirm_delete( 'company_id', "Really delete company: " . $data['name'], self::link_open( $_REQUEST['company_id'] ) ) ) {
				$this->delete_company( $_REQUEST['company_id'] );
				set_message( "company deleted successfully" );
				redirect_browser( $this->link_open( false ) );
			}
		} else if ( 'save_company' == $_REQUEST['_process'] && self::can_i( 'edit', 'Company' ) ) {
			$company_id = update_insert( 'company_id', $_REQUEST['company_id'], 'company', $_POST );
			set_message( 'Company saved successfully' );
			redirect_browser( $this->link_open( $company_id ) );
		}
	}

	/**
	 * @var int
	 * This is used to set a copmany id from the invoice module (or anything else)
	 * so that the templating and config system can generate the correct variables
	 * depending on what action is getting performed.
	 * eg: invoices have the ability to select which 'company' the invoice comes from if the customer has more than 1 company selected.
	 * when a template or configuration variable is loaded we want to use these unique configs rather than
	 */
	private static $_current_company_id = 0;

	public static function set_current_company_id( $company_id ) {
		self::$_current_company_id = $company_id;
	}

	public static function get_current_logged_in_company_id() {
		if ( self::$_current_company_id ) {
			return self::$_current_company_id;
		}
		if ( module_security::is_logged_in() ) {
			$company_access = self::get_company_data_access();
			switch ( $company_access ) {
				case _COMPANY_ACCESS_ALL:

					break;
				case _COMPANY_ACCESS_ASSIGNED:
				case _COMPANY_ACCESS_CONTACT:
					// this is a possibility that this user only has access to a single customer
					$companies = self::get_companys();
					if ( count( $companies ) == 1 ) {
						// only 1 woo! get this id and load in any custom config values.
						$company = array_shift( $companies );
						if ( $company && $company['company_id'] > 0 ) {
							self::$_current_company_id = $company['company_id'];

							return self::$_current_company_id;
						}
					}
			}
		}

		return false;
	}

	public static function hook_config_init_vars( $callback_name, $existing_config_vars ) {
		$new_config_vars = array();
		if ( defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG ) {
			// only do this if the current logged in user is restricted to a single company.
			// todo - manually check 'company_unique_config' field in db with manual sql to ensure we're still doing this right
			$company_id = self::get_current_logged_in_company_id();
			if ( $company_id > 0 ) {
				$sql = "SELECT `key`,`val` FROM `" . _DB_PREFIX . "company_config` WHERE company_id = " . (int) $company_id;
				foreach ( qa( $sql ) as $c ) {
					$new_config_vars[ $c['key'] ] = $c['val'];
				}
			}
		}

		return $new_config_vars;
	}

	public static function save_company_config( $key, $val ) {
		if ( defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG ) {
			$company_id = self::get_current_logged_in_company_id();
			if ( $company_id > 0 ) {
				// only save this value if it's different to the current value.
				$current_value = module_config::c( $key );
				if ( $val != $current_value ) {
					$sql = "REPLACE INTO `" . _DB_PREFIX . "company_config` SET `key` = '" . db_escape( $key ) . "', company_id = " . (int) $company_id . ", `val` = '" . db_escape( $val ) . "'";
					query( $sql );
					set_message( 'Successfully saved unique company configuration' . $current_value . ' | ' . $val );

					return true;
				}
			}
		}

		return false;
	}

	public static function delete_company( $company_id ) {
		if ( self::can_i( 'delete', 'Company' ) ) {
			$sql = "DELETE FROM `" . _DB_PREFIX . "company` WHERE `company_id` = " . (int) $company_id . "";
			query( $sql );
			$sql = "DELETE FROM `" . _DB_PREFIX . "company_customer` WHERE `company_id` = " . (int) $company_id . "";
			query( $sql );
			$sql = "DELETE FROM `" . _DB_PREFIX . "company_user_rel` WHERE `company_id` = " . (int) $company_id . "";
			query( $sql );
			$sql = "DELETE FROM `" . _DB_PREFIX . "company_template` WHERE `company_id` = " . (int) $company_id . "";
			query( $sql );
		}
	}

	public static function get_company( $company_id ) {
		$company = array();
		if ( (int) $company_id > 0 ) {
			$where          = 'WHERE 1 AND c.company_id = ' . (int) $company_id;
			$sql            = "SELECT c.*, c.company_id AS id ";
			$sql            .= " FROM `" . _DB_PREFIX . "company` c ";
			$company_access = self::get_company_data_access();
			switch ( $company_access ) {
				case _COMPANY_ACCESS_ALL:

					break;
				case _COMPANY_ACCESS_ASSIGNED:
					// we only want companies that are directly linked with the currently logged in user contact (from the staff user account settings area)
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_user_rel` cur ON c.company_id = cur.company_id ";
					$where .= " AND (cur.user_id = " . (int) module_security::get_loggedin_id() . ")";
					break;
				case _COMPANY_ACCESS_CONTACT:
					// only parent company of current user account contact
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON c.company_id = cc.company_id ";
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON cc.customer_id = u.customer_id ";
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_vendor` cv ON c.company_id = cv.company_id ";
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user` uv ON cv.vendor_id = uv.vendor_id ";
					$where .= " AND (u.user_id = " . (int) module_security::get_loggedin_id() . " OR uv.user_id = " . (int) module_security::get_loggedin_id() . ")";
					break;
			}
			$sql     .= $where;
			$company = qa1( $sql );
		}

		return $company;
	}

	public static function get_companys_by_customer( $customer_id ) {
		$sql = "SELECT c.*, c.company_id AS id ";
		$sql .= " FROM `" . _DB_PREFIX . "company_customer` cc ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "company` c USING (company_id) ";
		$sql .= " WHERE cc.customer_id = '" . (int) $customer_id . "'";
		$sql .= " GROUP BY c.company_id ";

		return qa( $sql );
	}

	public static function get_companys_by_vendor( $vendor_id ) {
		$sql = "SELECT c.*, c.company_id AS id ";
		$sql .= " FROM `" . _DB_PREFIX . "company_vendor` cc ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "company` c USING (company_id) ";
		$sql .= " WHERE cc.vendor_id = '" . (int) $vendor_id . "'";
		$sql .= " GROUP BY c.company_id ";

		return qa( $sql );
	}

	public static function get_company_data_access() {

		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Company Data Access', array(
				_COMPANY_ACCESS_ALL,
				_COMPANY_ACCESS_ASSIGNED,
				_COMPANY_ACCESS_CONTACT,
			) );
		} else {
			return true;
		}
	}

	static $get_companys_cache = false;

	public static function get_companys( $search = false ) {
		if ( self::$get_companys_cache !== false ) {
			return self::$get_companys_cache;
		}
		$where          = 'WHERE 1';
		$sql            = "SELECT c.*, c.company_id AS id ";
		$sql            .= " FROM `" . _DB_PREFIX . "company` c ";
		$company_access = self::get_company_data_access();
		switch ( $company_access ) {
			case _COMPANY_ACCESS_ALL:

				break;
			case _COMPANY_ACCESS_ASSIGNED:
				// we only want companies that are directly linked with the currently logged in user contact (from the staff user account settings area)
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_user_rel` cur ON c.company_id = cur.company_id ";
				$where .= " AND (cur.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
			case _COMPANY_ACCESS_CONTACT:
				// only parent company of current user account contact
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON c.company_id = cc.company_id ";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON cc.customer_id = u.customer_id ";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_vendor` cv ON c.company_id = cv.company_id ";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user` uv ON cv.vendor_id = uv.vendor_id ";
				$where .= " AND (u.user_id = " . (int) module_security::get_loggedin_id() . " OR uv.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
		}
		$sql                      .= $where;
		$sql                      .= " GROUP BY c.company_id ";
		self::$get_companys_cache = qa( $sql );

		return self::$get_companys_cache;
	}

	static $get_companys_access_restrictions_cache = false;

	public static function get_companys_access_restrictions() {
		if ( self::$get_companys_access_restrictions_cache !== false ) {
			return self::$get_companys_access_restrictions_cache;
		}
		$where          = 'WHERE 1';
		$sql            = "SELECT c.company_id  ";
		$from           = " FROM `" . _DB_PREFIX . "company` c ";
		$company_access = self::get_company_data_access();
		switch ( $company_access ) {
			case _COMPANY_ACCESS_ALL:

				break;
			case _COMPANY_ACCESS_ASSIGNED:
				// we only want companies that are directly linked with the currently logged in user contact (from the staff user account settings area)
				$sql  .= ", cur.user_id AS user_assigned ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "company_user_rel` cur ON c.company_id = cur.company_id ";
				//$where .= " AND (cur.user_id = ".(int)module_security::get_loggedin_id().")";
				break;
			case _COMPANY_ACCESS_CONTACT:
				// only parent company of current user account contact
				$sql  .= ", u.user_id AS user_id1, uv.user_id AS user_id2 ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON c.company_id = cc.company_id ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON cc.customer_id = u.customer_id ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "company_vendor` cv ON c.company_id = cv.company_id ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "user` uv ON cv.vendor_id = uv.vendor_id ";
				//$where .= " AND (u.user_id = ".(int)module_security::get_loggedin_id()." OR uv.user_id = ".(int)module_security::get_loggedin_id().")";
				break;
		}
		$sql .= $from;
		$sql .= $where;
		//$sql .= " GROUP BY c.company_id ";
		$res                                          = qa( $sql );
		self::$get_companys_access_restrictions_cache = array();
		switch ( $company_access ) {
			case _COMPANY_ACCESS_ALL:

				break;
			case _COMPANY_ACCESS_ASSIGNED:
				// we only want companies that are directly linked with the currently logged in user contact (from the staff user account settings area)
				//$where .= " AND (cur.user_id = ".(int)module_security::get_loggedin_id().")";
				foreach ( $res as $r ) {
					if ( (int) $r['user_assigned'] > 0 && $r['user_assigned'] == module_security::get_loggedin_id() ) {
						// this is an assigned user! add this company to the list.
						self::$get_companys_access_restrictions_cache[ $r['company_id'] ] = $r['company_id'];
					}
				}
				break;
			case _COMPANY_ACCESS_CONTACT:
				foreach ( $res as $r ) {
					if ( (int) $r['user_id1'] > 0 && $r['user_id1'] == module_security::get_loggedin_id() ) {
						// this is an assigned user! add this company to the list.
						self::$get_companys_access_restrictions_cache[ $r['company_id'] ] = $r['company_id'];
					} else if ( (int) $r['user_id2'] > 0 && $r['user_id2'] == module_security::get_loggedin_id() ) {
						// this is an assigned user! add this company to the list.
						self::$get_companys_access_restrictions_cache[ $r['company_id'] ] = $r['company_id'];
					}
				}
				break;
		}
		if ( ! count( self::$get_companys_access_restrictions_cache ) && count( $res ) ) {
			// we dont have access to any copmpanies, use the special -1 case so SQl works correctly.
			self::$get_companys_access_restrictions_cache[ - 1 ] = - 1;
		}

		return self::$get_companys_access_restrictions_cache;
	}


	public static function get_customers( $company_id ) {
		$sql = "SELECT gm.company_id, gm.customer_id ";
		$sql .= " FROM `" . _DB_PREFIX . "company_customer` gm ";
		$sql .= " WHERE gm.company_id = " . (int) $company_id;

		return qa( $sql );
	}

	public static function delete_customer( $company_id, $customer_id ) {
		$company_permissions = self::get_companys();
		if ( isset( $company_permissions[ $company_id ] ) ) {
			$sql = "DELETE FROM `" . _DB_PREFIX . "company_customer` WHERE ";
			$sql .= " `company_id` = '" . (int) $company_id . "' AND ";
			$sql .= " `customer_id` = '" . (int) $customer_id . "' LIMIT 1";
			query( $sql );
		}
	}

	public static function delete_vendor( $company_id, $vendor_id ) {
		$company_permissions = self::get_companys();
		if ( isset( $company_permissions[ $company_id ] ) ) {
			$sql = "DELETE FROM `" . _DB_PREFIX . "company_vendor` WHERE ";
			$sql .= " `company_id` = '" . (int) $company_id . "' AND ";
			$sql .= " `vendor_id` = '" . (int) $vendor_id . "' LIMIT 1";
			query( $sql );
		}
	}

	public static function hook_customer_deleted( $callback_name, $customer_id, $remove_linked_data ) {
		if ( (int) $customer_id > 0 ) {
			delete_from_db( 'company_customer', 'customer_id', $customer_id );
		}
	}

	public static function hook_vendor_deleted( $callback_name, $vendor_id, $remove_linked_data ) {
		if ( (int) $vendor_id > 0 ) {
			delete_from_db( 'company_vendor', 'vendor_id', $vendor_id );
		}
	}

	public static function add_customer_to_company( $company_id, $customer_id ) {
		if ( $company_id > 0 && $customer_id > 0 ) {
			$company_permissions = self::get_companys();
			if ( isset( $company_permissions[ $company_id ] ) ) {
				$sql = "REPLACE INTO `" . _DB_PREFIX . "company_customer` SET ";
				$sql .= " `company_id` = '" . (int) $company_id . "', ";
				$sql .= " `customer_id` = '" . (int) $customer_id . "' ";
				query( $sql );
			}
		}
	}


	public static function add_vendor_to_company( $company_id, $vendor_id ) {
		if ( $company_id > 0 && $vendor_id > 0 ) {
			$company_permissions = self::get_companys();
			if ( isset( $company_permissions[ $company_id ] ) ) {
				$sql = "REPLACE INTO `" . _DB_PREFIX . "company_vendor` SET ";
				$sql .= " `company_id` = '" . (int) $company_id . "', ";
				$sql .= " `vendor_id` = '" . (int) $vendor_id . "' ";
				query( $sql );
			}
		}
	}


	public static function get_companys_by_user( $user_id ) {
		$sql = "SELECT c.*, c.company_id AS id ";
		$sql .= " FROM `" . _DB_PREFIX . "company_user_rel` cc ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "company` c USING (company_id) ";
		$sql .= " WHERE cc.user_id = '" . (int) $user_id . "'";
		$sql .= " GROUP BY c.company_id ";

		return qa( $sql );
	}

	public static function delete_user( $company_id, $user_id ) {
		$sql = "DELETE FROM `" . _DB_PREFIX . "company_user_rel` WHERE ";
		$sql .= " `company_id` = '" . (int) $company_id . "' AND ";
		$sql .= " `user_id` = '" . (int) $user_id . "' LIMIT 1";
		query( $sql );
	}

	public static function add_user_to_company( $company_id, $user_id ) {
		if ( self::can_i( 'edit', 'Company' ) && $company_id > 0 && $user_id > 0 ) {
			$sql = "REPLACE INTO `" . _DB_PREFIX . "company_user_rel` SET ";
			$sql .= " `company_id` = '" . (int) $company_id . "', ";
			$sql .= " `user_id` = '" . (int) $user_id . "' ";
			query( $sql );
		}
	}


	// stops our loopback bug
	private static $checking_enabled = false;

	public static function is_enabled() {
		if ( self::$checking_enabled ) {
			return false;
		}
		self::$checking_enabled = true;
		$companys               = self::get_companys_access_restrictions();
		$enabled                = count( $companys ) > 0 && module_config::c( 'company_enabled', 1 );
		self::$checking_enabled = false;

		return $enabled;
	}


	// template related changes.
	public static function template_handle_save( $template_id, $data ) {
		// check if we're savniga company id and that this user has access to this company, and permissions to edit templates.
		$company_id = isset( $_REQUEST['company_id'] ) ? (int) $_REQUEST['company_id'] : false;
		if ( $company_id ) {
			$company = self::get_company( $company_id );
			if ( $company ) {
				$existing_template = module_template::get_template( $template_id );
				if ( $existing_template && $existing_template['template_id'] == $template_id ) {
					// we're saving a template for this particular company.
					// if it's an empty template content then we remove this company template so it reverts to the system default.
					if ( isset( $data['content'] ) && ! strlen( trim( $data['content'] ) ) ) {
						delete_from_db( 'company_template', array( 'company_id', 'template_id' ), array(
							$company_id,
							$template_id
						) );
						set_message( 'Company template successfully reset to default' );
						redirect_browser( module_template::link_open( $template_id ) );
					} else {
						$sql = 'REPLACE INTO `' . _DB_PREFIX . "company_template` SET company_id = " . (int) $company_id . ", `template_id` = " . (int) $template_id . ", `description` = '" . db_escape( isset( $data['description'] ) ? $data['description'] : '' ) . "', `content` = '" . db_escape( isset( $data['content'] ) ? $data['content'] : '' ) . "', `wysiwyg` = '" . db_escape( isset( $data['wysiwyg'] ) ? $data['wysiwyg'] : '' ) . "'";
						query( $sql );
						set_message( 'Unique company template successfully updated' );
						redirect_browser( module_template::link_open( $template_id ) . '&company_id=' . $company_id );

					}
				}
			}
		}
	}

	public static function template_edit_form( $template_id, $company_id ) {
		?>
		<input type="hidden" name="company_id" value="<?php echo (int) $company_id; ?>">
		<?php

	}

	public static function template_get_company( $template_id, $existing_template_data, $override_company_id = false ) {
		if ( defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG ) {
			$company_id = $override_company_id ? $override_company_id : self::get_current_logged_in_company_id();
			if ( (int) $company_id > 0 ) {
				// check user has access to this company.
				$company = self::get_company( $company_id );
				if ( $company && $company['company_id'] == $company_id ) {
					$data = get_single( "company_template", array( 'company_id', 'template_id' ), array(
						$company_id,
						$template_id
					) );
					if ( $data && $data['company_id'] == (int) $company_id ) {
						return array_merge( $existing_template_data, $data );
					}
				}
			}
		}

		return false;
	}


	public function get_upgrade_sql() {
		$sql = '';
		if ( ! self::db_table_exists( 'company_user_rel' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_user_rel` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`company_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! self::db_table_exists( 'company_template' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_template` (
  `company_id` int(11) NOT NULL,
  `template_id` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT  \'\',
  `content` LONGTEXT NULL,
  `wysiwyg` CHAR( 1 ) NOT NULL DEFAULT  \'1\',
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `date_created` date NOT NULL,
  `date_updated` date NULL,
  PRIMARY KEY  (`company_id`, `template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! self::db_table_exists( 'company_config' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_config` (
  `key` varchar(255) NOT NULL,
  `company_id` int(11) NOT NULL,
  `val` text NOT NULL,
  PRIMARY KEY  (`key`, `company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! self::db_table_exists( 'company_vendor' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_vendor` (
      `company_id` int(11) NOT NULL,
      `vendor_id` int(11) NOT NULL,
      KEY `company_id` (`company_id`),
      KEY `vendor_id` (`vendor_id`),
      PRIMARY KEY ( `company_id`, `vendor_id` )
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';
		}

		return $sql;
	}

	public function get_install_sql() {
		$sql = 'CREATE TABLE `' . _DB_PREFIX . 'company` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT \'\',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';

		$sql .= "\n";

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_customer` (
  `company_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  KEY `company_id` (`company_id`),
  KEY `customer_id` (`customer_id`),
  PRIMARY KEY ( `company_id`, `customer_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_vendor` (
  `company_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  KEY `company_id` (`company_id`),
  KEY `vendor_id` (`vendor_id`),
  PRIMARY KEY ( `company_id`, `vendor_id` )
) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_user_rel` (
  `company_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  KEY `company_id` (`company_id`),
  KEY `user_id` (`user_id`),
  PRIMARY KEY  (`company_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_config` (
  `key` varchar(255) NOT NULL,
  `company_id` int(11) NOT NULL,
  `val` text NOT NULL,
  PRIMARY KEY  (`key`, `company_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'company_template` (
  `company_id` int(11) NOT NULL,
  `template_id` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT  \'\',
  `content` LONGTEXT NULL,
  `wysiwyg` CHAR( 1 ) NOT NULL DEFAULT  \'1\',
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `date_created` date NOT NULL,
  `date_updated` date NULL,
  PRIMARY KEY  (`company_id`, `template_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';

		/* $sql .= 'CREATE TABLE `'._DB_PREFIX.'company_template` (
`company_id` int(11) NOT NULL,
`user_id` int(11) NOT NULL,
KEY `company_id` (`company_id`),
KEY `user_id` (`user_id`),
PRIMARY KEY  (`company_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';*/

		return $sql;
	}


}