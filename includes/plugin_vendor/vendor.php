<?php


define( '_VENDOR_ACCESS_ALL', 'All vendors in system' ); // do not change string
define( '_VENDOR_ACCESS_ALL_COMPANY', 'Only vendors from companies I have access to' ); // do not change string
define( '_VENDOR_ACCESS_CONTACTS', 'Only vendor I am assigned to as a contact' ); // do not change string
// todo!
define( '_VENDOR_ACCESS_TASKS', 'Only vendors I am assigned to in a job' ); // do not change string

define( '_VENDOR_STATUS_OVERDUE', 3 );
define( '_VENDOR_STATUS_OWING', 2 );
define( '_VENDOR_STATUS_PAID', 1 );


class module_vendor extends module_base {

	public $links;
	public $vendor_types;
	public $vendor_id;

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
		$this->vendor_types    = array();
		$this->module_name     = "vendor";
		$this->module_position = 5.1;
		$this->version         = 2.122;
		// 2.122 - 2016-07-10 - big update to mysqli
		// 2.121 - 2016-04-30 - update message
		// 2.12 - 2014-07-31 - responsive improvements
		// 2.11 - 2014-07-21 - company permission improvement
		// 2.1 - 2014-07-16 - vendor initial release

		module_config::register_css( 'vendor', 'vendor.css' );
	}

	public function pre_menu() {

		if ( count( $this->get_vendors( array(), array( 'columns' => 'c.vendor_id' ) ) ) && $this->can_i( 'view', 'Vendors' ) ) {
			$this->links['vendors'] = array(
				"name"      => "Vendors",
				"p"         => "vendor_admin_list",
				"args"      => array( 'vendor_id' => false ),
				'icon_name' => 'users',
			);
		}
	}


	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."vendor` c WHERE ";
			//$sql .= " c.`vendor_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_vendors( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					// what part of this matched?
					if (
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['name'] ) ||
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['last_name'] ) ||
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['phone'] )
					) {
						// we matched the vendor contact details.
						$match_string = _l( 'Vendor Contact: ' );
						$match_string .= _shl( $result['vendor_name'], $search_key );
						$match_string .= ' - ';
						$match_string .= _shl( $result['name'], $search_key );
						// hack
						$_REQUEST['vendor_id'] = $result['vendor_id'];
						$ajax_results []       = '<a href="' . module_user::link_open_contact( $result['user_id'] ) . '">' . $match_string . '</a>';
					} else {
						$match_string    = _l( 'Vendor: ' );
						$match_string    .= _shl( $result['vendor_name'], $search_key );
						$ajax_results [] = '<a href="' . $this->link_open( $result['vendor_id'] ) . '">' . $match_string . '</a>';
						//$ajax_results [] = $this->link_open($result['vendor_id'],true);
					}
				}
			}
		}

		return $ajax_results;
	}

	/** static stuff */


	public static function link_generate( $vendor_id = false, $options = array(), $link_options = array() ) {

		// link generation can be cached and save a few db calls.
		$link_cache_key     = 'vendor_link_' . md5( module_security::get_loggedin_id() . '_' . serialize( func_get_args() ) . '_' . ( isset( $_REQUEST['vendor_id'] ) ? $_REQUEST['vendor_id'] : false ) );
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );
		if ( $cached_link = module_cache::get( 'vendor', $link_cache_key ) ) {
			return $cached_link;
		}

		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'vendor_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

		$vendor_data = false;
		// we check if we're bubbling from a sub link, and find the item id from a sub link
		if ( ${$key} === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
			// check if this still exists.
			// this is a hack incase the vendor is deleted, the invoices are still left behind.
			if ( ${$key} && $link_options ) {
				$vendor_data = self::get_vendor( ${$key}, true, true );
				if ( ! $vendor_data || ! isset( $vendor_data[ $key ] ) || $vendor_data[ $key ] != ${$key} ) {
					$link = link_generate( $link_options );
					module_cache::put( 'vendor', $link_cache_key, $link, $link_cache_timeout );

					return $link;
				}
			}
		}
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $vendor_id > 0 ) {
					$data = $vendor_data ? $vendor_data : self::get_vendor( $vendor_id, true, true );
				} else {
					$data = array();
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['vendor_name'] ) || ! trim( $data['vendor_name'] ) ) ? _l( 'N/A' ) : $data['vendor_name'];
			if ( ! $data || ! $vendor_id || isset( $data['_no_access'] ) ) {
				$link = $options['text'];
				module_cache::put( 'vendor', $link_cache_key, $link, $link_cache_timeout );

				return $link;
			}
		}
		//$options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'vendor_id' => $vendor_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = 'vendor_admin_' . ( ( $vendor_id || $vendor_id == 'new' ) ? 'open' : 'list' );
		$options['module'] = 'vendor';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		// $options['skip_permissions'] is used in password reset, otherwise we get # in url
		if ( ! self::can_i( 'view', 'Vendors' ) && ( ! isset( $options['skip_permissions'] ) || ! $options['skip_permissions'] ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				$link = '#';
			} else {
				$link = isset( $options['text'] ) ? $options['text'] : 'N/A';
			}
			module_cache::put( 'vendor', $link_cache_key, $link, $link_cache_timeout );

			return $link;
		}

		if ( isset( $data['vendor_status'] ) ) {
			switch ( $data['vendor_status'] ) {
				case _VENDOR_STATUS_OVERDUE:
					$link_options['class'] = 'vendor_overdue error_text';
					break;
				case _VENDOR_STATUS_OWING:
					$link_options['class'] = 'vendor_owing';
					break;
				case _VENDOR_STATUS_PAID:
					$link_options['class'] = 'vendor_paid success_text';
					break;
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		/*$bubble_to_module = array(
				'module' => 'people',
				'argument' => 'people_id',
		);*/
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			$link = link_generate( $link_options );
		}
		module_cache::put( 'vendor', $link_cache_key, $link, $link_cache_timeout );

		return $link;
	}


	public static function link_open( $vendor_id, $full = false, $data = array() ) {
		return self::link_generate( $vendor_id, array( 'full' => $full, 'data' => $data ) );
	}


	public static function get_vendors( $search = array(), $return_options = false ) {

		$cache_key_args = func_get_args();
		$cache_key      = self::_vendor_cache_key( 'all', $cache_key_args );
		$cache_timeout  = module_config::c( 'cache_objects', 60 );
		if ( $cached_item = module_cache::get( 'vendor', $cache_key ) ) {
			return $cached_item;
		}

		// work out what vendors this user can access?
		$vendor_access = self::get_vendor_data_access();

		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
		if ( is_array( $return_options ) && isset( $return_options['columns'] ) ) {
			$sql .= $return_options['columns'];
		} else {
			$sql .= " c.*, c.vendor_id AS id, u.user_id, u.name, u.last_name, u.phone ";
			$sql .= " , pu.user_id, pu.name AS primary_user_name, pu.last_name AS primary_user_last_name, pu.phone AS primary_user_phone, pu.email AS primary_user_email";
			$sql .= " , pu.fax AS primary_user_fax, pu.mobile AS primary_user_mobile, pu.language AS primary_user_language";
			$sql .= " , a.line_1, a.line_2, a.suburb, a.state, a.region, a.country, a.post_code ";
			if ( ! count( $search ) ) {
				// we're pulling all available vendors into an array.
				//echo "all vendors! ";
			}
		}
		$sql   .= " FROM `" . _DB_PREFIX . "vendor` c ";
		$where = "";
		if ( defined( '_SYSTEM_ID' ) ) {
			$sql .= " AND c.system_id = '" . _SYSTEM_ID . "' ";
		}
		$group_order = '';
		$sql         .= ' LEFT JOIN `' . _DB_PREFIX . "user` u ON c.vendor_id = u.vendor_id"; //c.primary_user_id = u.user_id AND
		$sql         .= ' LEFT JOIN `' . _DB_PREFIX . "user` pu ON c.primary_user_id = pu.user_id";
		$sql         .= ' LEFT JOIN `' . _DB_PREFIX . "address` a ON c.vendor_id = a.owner_id AND a.owner_table = 'vendor' AND a.address_type = 'physical'";
		if ( isset( $search['generic'] ) && trim( $search['generic'] ) ) {
			$str = db_escape( trim( $search['generic'] ) );
			// search the vendor name, contact name, cusomter phone, contact phone, contact email.
			//$where .= 'AND u.vendor_id IS NOT NULL AND ( ';
			$where .= " AND ( ";
			$where .= "c.vendor_name LIKE '%$str%' OR ";
			// $where .= "c.phone LIKE '%$str%' OR "; // search company phone number too.
			$where .= "u.name LIKE '%$str%' OR u.email LIKE '%$str%' OR ";
			$where .= "u.last_name LIKE '%$str%' OR ";
			$where .= "u.phone LIKE '%$str%' OR u.fax LIKE '%$str%' ";
			$where .= ') ';
		}
		if ( isset( $search['type'] ) ) {
			$where .= " AND c.type = " . (int) $search['type'];
		}
		if ( isset( $search['address'] ) && trim( $search['address'] ) ) {
			$str = db_escape( trim( $search['address'] ) );
			// search all the vendor site addresses.
			$where .= " AND ( ";
			$where .= " a.line_1 LIKE '%$str%' OR ";
			$where .= " a.line_2 LIKE '%$str%' OR ";
			$where .= " a.suburb LIKE '%$str%' OR ";
			$where .= " a.state LIKE '%$str%' OR ";
			$where .= " a.region LIKE '%$str%' OR ";
			$where .= " a.country LIKE '%$str%' OR ";
			$where .= " a.post_code LIKE '%$str%' ";
			$where .= " ) ";
		}
		if ( isset( $search['state_id'] ) && trim( $search['state_id'] ) ) {
			$str = (int) $search['state_id'];
			// search all the vendor site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "address` a ON (a.owner_id = c.vendor_id)"; // swap join around? meh.
			$where .= " AND (a.state_id = '$str' AND a.owner_table = 'vendor')";
		}

		if ( isset( $search['vendor_id'] ) && trim( $search['vendor_id'] ) ) {
			$str = (int) $search['vendor_id'];
			// search all the vendor site addresses.
			$where .= " AND (c.vendor_id = '$str')";
		}
		if ( isset( $search['company_id'] ) && trim( $search['company_id'] ) ) {
			$str = (int) $search['company_id'];
			// search all the vendor site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_vendor` ccr ON (c.vendor_id = ccr.vendor_id)";
			$where .= " AND (ccr.company_id = '$str')";
		}
		if ( isset( $search['group_id'] ) && trim( $search['group_id'] ) ) {
			$str   = (int) $search['group_id'];
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (c.vendor_id = gm.owner_id)";
			$where .= " AND (gm.group_id = '$str' AND gm.owner_table = 'vendor')";
		}
		if ( isset( $search['extra_fields'] ) && is_array( $search['extra_fields'] ) && class_exists( 'module_extra', false ) ) {
			$extra_fields = array();
			foreach ( $search['extra_fields'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$extra_fields[ $key ] = trim( $val );
				}
			}
			if ( count( $extra_fields ) ) {
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "extra` ext ON (ext.owner_id = c.vendor_id)"; //AND ext.owner_table = 'vendor'
				$where .= " AND (ext.owner_table = 'vendor' AND ( ";
				foreach ( $extra_fields as $key => $val ) {
					$val   = db_escape( $val );
					$key   = db_escape( $key );
					$where .= "( ext.`extra` LIKE '%$val%' AND ext.`extra_key` = '$key') OR ";
				}
				$where = rtrim( $where, ' OR' );
				$where .= ' ) )';
			}
		}
		switch ( $vendor_access ) {
			case _VENDOR_ACCESS_ALL:

				break;
			case _VENDOR_ACCESS_ALL_COMPANY:
				if ( class_exists( 'module_company', false ) && module_company::is_enabled() ) {
					$companys = module_company::get_companys_access_restrictions();
					if ( count( $companys ) ) {
						$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_vendor` cc ON c.vendor_id = cc.vendor_id";
						$where .= " AND ( ";
						if ( module_config::c( 'vendor_show_unassigned_company', 0 ) ) {
							$where .= 'cc.company_id IS NULL OR ';
						}
						$where .= "cc.company_id IN ( ";
						$where .= db_escape( implode( ', ', $companys ) );
						$where .= " ) ) ";
					}
				}
				break;
			case _VENDOR_ACCESS_CONTACTS:
				// we only want vendors that are directly linked with the currently logged in user contact.
				//$sql .= " LEFT JOIN `"._DB_PREFIX."user` u ON c.vendor_id = u.vendor_id "; // done above.
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user_vendor_rel` ucr ON c.vendor_id = ucr.vendor_id ";
				$where .= " AND (";
				$where .= "u.user_id = " . (int) module_security::get_loggedin_id();
				$where .= " OR ( ucr.vendor_id = c.vendor_id AND ucr.user_id = " . (int) module_security::get_loggedin_id() . " AND ucr.primary = u.user_id )";
				$where .= " OR ( ucr.vendor_id = c.vendor_id AND ucr.primary = " . (int) module_security::get_loggedin_id() . " AND ucr.user_id = u.user_id )";
				$where .= ' )';
				/*
				//                if(isset($_SESSION['_restrict_vendor_id']) && (int)$_SESSION['_restrict_vendor_id']> 0){
														// this session variable is set upon login, it holds their vendor id.
														// todo - share a user account between multiple vendors!
														//$where .= " AND c.vendor_id IN (SELECT vendor_id FROM )";

												if(isset($res['linked_parent_user_id']) && $res['linked_parent_user_id'] == $res['user_id']){
														// this user is a primary user.
														$_SESSION['_restrict_vendor_id'] = array();
														$_SESSION['_restrict_vendor_id'][$res['vendor_id']] = $res['vendor_id'];
														foreach(module_user::get_contact_vendor_links($res['user_id']) as $linked){
																$_SESSION['_restrict_vendor_id'][$linked['vendor_id']] = $linked['vendor_id'];
														}


												}else{
														// oldschool permissions.
														$_SESSION['_restrict_vendor_id'] = $res['vendor_id'];
												}*/

				/*$valid_vendor_ids = module_security::get_vendor_restrictions();
				if(count($valid_vendor_ids)){
						$where .= " AND ( ";
						foreach($valid_vendor_ids as $valid_vendor_id){
								$where .= " c.vendor_id = '".(int)$valid_vendor_id."' OR ";
						}
						$where = rtrim($where,'OR ');
						$where .= " )";
				}*/
				//                }
				break;
			case _VENDOR_ACCESS_TASKS:
				// only vendors who have linked jobs that I am assigned to.
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON c.vendor_id = j.vendor_id ";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON j.job_id = t.job_id ";
				$where .= " AND (j.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
		}


		$group_order = ' GROUP BY c.vendor_id ORDER BY c.vendor_name ASC'; // stop when multiple company sites have same region
		$sql         = $sql . ( strlen( $where ) > 0 ? ' WHERE 1' . $where : '' ) . $group_order;
		if ( ( ! is_array( $return_options ) && $return_options === true ) || ( is_array( $return_options ) && isset( $return_options['as_resource'] ) && $return_options['as_resource'] ) ) {
			return query( $sql );
		}
		$result = qa( $sql );
		/*if(!function_exists('sort_vendors')){
				function sort_vendors($a,$b){
						return strnatcasecmp($a['vendor_name'],$b['vendor_name']);
				}
		}
		uasort($result,'sort_vendors');*/

		// we are filtering in the SQL code now..
		//module_security::filter_data_set("vendor",$result);

		module_cache::put( 'vendor', $cache_key, $result, $cache_timeout );

		return $result;
		//return get_multiple("vendor",$search,"vendor_id","fuzzy","name");
	}


	private static function _vendor_cache_key( $vendor_id, $args = array() ) {
		return 'vendor_' . $vendor_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_vendor( $vendor_id, $skip_permissions = false, $basic_for_link = false ) {
		$vendor_id = (int) $vendor_id;
		$vendor    = false;
		if ( $vendor_id > 0 ) {

			$cache_key_args = func_get_args();
			$cache_key      = self::_vendor_cache_key( $vendor_id, $cache_key_args );
			$cache_timeout  = module_config::c( 'cache_objects', 60 );
			if ( $cached_item = module_cache::get( 'vendor', $cache_key ) ) {
				return $cached_item;
			}

			$vendor = get_single( "vendor", "vendor_id", $vendor_id );
			// get their address.
			if ( $vendor && isset( $vendor['vendor_id'] ) && $vendor['vendor_id'] == $vendor_id ) {

				if ( ! $basic_for_link ) {

					$vendor['vendor_address'] = module_address::get_address( $vendor_id, 'vendor', 'physical', true );
				}

				switch ( self::get_vendor_data_access() ) {
					case _VENDOR_ACCESS_ALL:

						break;
					case _VENDOR_ACCESS_ALL_COMPANY:
					case _VENDOR_ACCESS_CONTACTS:
					case _VENDOR_ACCESS_TASKS:
						$is_valid_vendor = self::get_vendors( array( 'vendor_id' => $vendor['vendor_id'] ) );
						if ( ! $is_valid_vendor || ! isset( $is_valid_vendor[ $vendor['vendor_id'] ] ) ) {
							if ( $skip_permissions ) {
								$vendor['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_vendor with the skip permissions argument. (eg: in the ticket file listing link)
							} else {
								$vendor = false;
							}
						}
						break;
				}
			}
		}
		if ( ! $vendor ) {
			$vendor = array(
				'vendor_id'       => 'new',
				'vendor_name'     => '',
				'vendor_status'   => _VENDOR_STATUS_PAID,
				'primary_user_id' => '',
				'credit'          => '0',
				'vendor_address'  => array(),
			);
		}
		if ( class_exists( 'module_company', false ) && module_company::is_enabled() && ! $basic_for_link ) {
			$vendor['company_ids'] = array();
			if ( isset( $vendor['vendor_id'] ) && (int) $vendor['vendor_id'] > 0 ) {
				foreach ( module_company::get_companys_by_vendor( $vendor['vendor_id'] ) as $company ) {
					$vendor['company_ids'][ $company['company_id'] ] = $company['name'];
				}
			}
		}
		//$vendor['vendor_industry_id'] = get_multiple('vendor_industry_rel',array('vendor_id'=>$vendor_id),'vendor_industry_id');
		//echo $vendor_id;print_r($vendor);exit;
		if ( isset( $cache_key ) && isset( $cache_timeout ) ) {
			module_cache::put( 'vendor', $cache_key, $vendor, $cache_timeout );
		}

		return $vendor;
	}


	/** methods  */


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['vendor_id'] && module_vendor::can_i( 'delete', 'Vendors' ) ) {
			$data = self::get_vendor( $_REQUEST['vendor_id'] );
			if ( $data['vendor_id'] && $data['vendor_id'] = $_REQUEST['vendor_id'] ) {
				if ( module_form::confirm_delete(
					'vendor_id',
					_l( "Really delete vendor: %s", $data['vendor_name'] ),
					self::link_open( $_REQUEST['vendor_id'] ),
					array(
						'options' => array(
							array(
								'label'   => _l( 'Also delete all Vendor %s, Jobs, Invoices, Tickets and Files', module_config::c( 'project_name_plural' ) ),
								'name'    => 'delete_others',
								'type'    => 'checkbox',
								'value'   => 1,
								'checked' => true,
							)
						),
					)
				) ) {
					$this->delete_vendor( $_REQUEST['vendor_id'], isset( $_REQUEST['delete_others'] ) && $_REQUEST['delete_others'] );

					set_message( "Vendor deleted successfully" );
					redirect_browser( self::link_open( false ) );
				}
			}
		} else if ( "ajax_contact_list" == $_REQUEST['_process'] ) {

			$vendor_id = isset( $_REQUEST['vendor_id'] ) ? (int) $_REQUEST['vendor_id'] : 0;
			$res       = module_user::get_contacts( array( 'vendor_id' => $vendor_id ) );
			$options   = array();
			foreach ( $res as $row ) {
				$options[ $row['user_id'] ] = $row['name'] . ' ' . $row['last_name'];
			}
			echo json_encode( $options );
			exit;

		} else if ( "save_vendor" == $_REQUEST['_process'] ) {
			$vendor_id = $this->save_vendor( $_REQUEST['vendor_id'], $_POST );
			hook_handle_callback( 'vendor_save', $vendor_id );
			set_message( "Vendor saved successfully" );
			redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : self::link_open( $vendor_id ) );
		}
	}

	public function load( $vendor_id ) {
		$data = self::get_vendor( $vendor_id );
		foreach ( $data as $key => $val ) {
			$this->$key = $val;
		}

		return $data;
	}

	public static function run_cron() {

	}

	// run this update in a cron job from time to time:
	public static function update_vendor_status( $vendor_id ) {
		// find out if this vendor has any invoices owing, paid or overdue

	}

	public function save_vendor( $vendor_id, $data ) {

		$vendor_id   = (int) $vendor_id;
		$temp_vendor = false;
		if ( $vendor_id > 0 ) {
			// check permissions
			$temp_vendor = $this->get_vendor( $vendor_id );
			if ( ! $temp_vendor || $temp_vendor['vendor_id'] != $vendor_id ) {
				$temp_vendor = false;
				$vendor_id   = false;
			}
		}

		if ( _DEMO_MODE && $vendor_id == 1 ) {
			set_error( 'Sorry this is a Demo Vendor. It cannot be changed.' );
			redirect_browser( self::link_open( $vendor_id ) );
		}

		if ( isset( $data['default_tax_system'] ) && $data['default_tax_system'] ) {
			$data['default_tax']      = - 1;
			$data['default_tax_name'] = '';
		}

		if ( isset( $data['primary_user_id'] ) ) {
			unset( $data['primary_user_id'] );
		} // only allow this to be set through the method.

		$vendor_id = update_insert( "vendor_id", $vendor_id, "vendor", $data );


		if ( isset( $_REQUEST['user_id'] ) ) {
			$user_id = (int) $_REQUEST['user_id'];
			if ( $user_id > 0 ) {
				// check permissions
				$temp_user = module_user::get_user( $user_id );
				if ( ! $temp_user || $temp_user['user_id'] != $user_id ) {
					$user_id = false;
				}
			}
			// assign specified user_id to this vendor.
			// could this be a problem?
			// maybe?
			// todo: think about security precautions here, maybe only allow admins to set primary contacts.
			$data['vendor_id'] = $vendor_id;
			if ( ! $user_id ) {
				// hack to set the default role of a contact (if one is set in settings).
				if ( ! isset( $data['last_name'] ) && isset( $data['name'] ) && strpos( $data['name'], ' ' ) > 0 ) {
					// todo - save from vendor import
					$bits              = explode( ' ', $data['name'] );
					$data['last_name'] = array_pop( $bits );
					$data['name']      = implode( ' ', $bits );
				}
				$user_id = update_insert( "user_id", false, "user", $data );
				module_cache::clear( 'user' );
				$role_id = module_config::c( 'contact_default_role', 0 );
				if ( $role_id > 0 ) {
					module_user::add_user_to_role( $user_id, $role_id );
				}
				$this->set_primary_user_id( $vendor_id, $user_id );
			} else {
				// make sure this user is part of this vendor.
				// wait! addition, we want to be able to move an existing vendor contact to this new vendor.
				$saved_user_id = false;
				if ( isset( $_REQUEST['move_user_id'] ) && (int) $_REQUEST['move_user_id'] && module_vendor::can_i( 'create', 'Vendors' ) ) {
					$old_user = module_user::get_user( (int) $_REQUEST['move_user_id'] );
					if ( $old_user && $old_user['user_id'] == (int) $_REQUEST['move_user_id'] ) {
						$saved_user_id = $user_id = update_insert( "user_id", $user_id, "user", $data );
						module_cache::clear( 'user' );
						hook_handle_callback( 'vendor_contact_moved', $user_id, $old_user['vendor_id'], $vendor_id );
						$this->set_primary_user_id( $vendor_id, $user_id );
						module_cache::clear( 'user' );
					}
				} else {
					// save normally, only those linked to this account:
					$users = module_user::get_contacts( array( 'vendor_id' => $vendor_id ) );
					foreach ( $users as $user ) {
						if ( $user['user_id'] == $user_id ) {
							$saved_user_id = $user_id = update_insert( "user_id", $user_id, "user", $data );
							$this->set_primary_user_id( $vendor_id, $user_id );
							module_cache::clear( 'user' );
							break;
						}
					}
				}
				if ( ! $saved_user_id ) {
					$this->set_primary_user_id( $vendor_id, 0 );
					module_cache::clear( 'user' );
				}
			}
			// todo: move this functionality back into the user class.
			// maybe with a static save_user method ?
			if ( $user_id > 0 && class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				module_extra::save_extras( 'user', 'user_id', $user_id );
			}
		}

		handle_hook( "address_block_save", $this, "physical", "vendor", "vendor_id", $vendor_id );
		//handle_hook("address_block_save",$this,"postal","vendor","vendor_id",$vendor_id);
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::save_extras( 'vendor', 'vendor_id', $vendor_id );
		}

		// save the company information if it's available
		if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
			if ( isset( $_REQUEST['available_vendor_company'] ) && is_array( $_REQUEST['available_vendor_company'] ) ) {
				$selected_companies = isset( $_POST['vendor_company'] ) && is_array( $_POST['vendor_company'] ) ? $_POST['vendor_company'] : array();
				$company_access     = module_company::get_company_data_access();
				if ( $company_access == _COMPANY_ACCESS_ALL && ! count( $selected_companies ) ) {
					// user is unassignging this vendor from all companies we have access to, dont let them do this?

				}
				foreach ( $_REQUEST['available_vendor_company'] as $company_id => $tf ) {
					if ( ! isset( $selected_companies[ $company_id ] ) || ! $selected_companies[ $company_id ] ) {
						// remove vendor from this company
						module_company::delete_vendor( $company_id, $vendor_id );
					} else {
						// add vendor to this company (if they are not already existing)
						module_company::add_vendor_to_company( $company_id, $vendor_id );
					}
				}
			}
		}

		self::update_vendor_status( $vendor_id );
		module_cache::clear( 'vendor' );

		return $vendor_id;
	}

	public static function set_primary_user_id( $vendor_id, $user_id ) {
		if ( _DEMO_MODE && $vendor_id == 1 ) {
			set_error( 'Sorry this is a Demo Vendor. It cannot be changed.' );
			redirect_browser( self::link_open( $vendor_id ) );
		}
		update_insert( 'vendor_id', $vendor_id, 'vendor', array( 'primary_user_id' => $user_id ) );
		module_cache::clear( 'vendor' );
	}

	public function delete_vendor( $vendor_id, $remove_linked_data = true ) {
		$vendor_id = (int) $vendor_id;
		if ( $vendor_id > 0 ) {
			if ( _DEMO_MODE && $vendor_id == 1 ) {
				set_error( 'Sorry this is a Demo Vendor. It cannot be changed.' );
				redirect_browser( self::link_open( $vendor_id ) );
			}
			$vendor = self::get_vendor( $vendor_id );
			if ( $vendor && $vendor['vendor_id'] == $vendor_id ) {

				// todo: Delete emails (wack these in this vendor_deleted hook)
				hook_handle_callback( 'vendor_deleted', $vendor_id, $remove_linked_data );

				if ( class_exists( 'module_group', false ) ) {
					// remove the vendor from his groups
					module_group::delete_member( $vendor_id, 'vendor' );
				}
				if ( class_exists( 'module_extra', false ) ) {
					module_extra::delete_extras( 'vendor', 'vendor_id', $vendor_id );
				}
				// remove the contacts from this vendor
				foreach ( module_user::get_contacts( array( 'vendor_id' => $vendor_id ) ) as $val ) {
					if ( $val['vendor_id'] && $val['vendor_id'] == $vendor_id ) {
						module_user::delete_user( $val['user_id'] );
					}
				}
				if ( class_exists( 'module_note', false ) ) {
					module_note::note_delete( "vendor", 'vendor_id', $vendor_id );
				}
				handle_hook( "address_delete", $this, 'all', "vendor", 'vendor_id', $vendor_id );

				// finally delete the main vendor record
				// (this is so the above code works with its sql joins)
				$sql = "DELETE FROM " . _DB_PREFIX . "vendor WHERE vendor_id = '" . $vendor_id . "' LIMIT 1";
				query( $sql );
			}
		}
	}


	public static function handle_import( $data, $add_to_group ) {

		// woo! we're doing an import.

		// our first loop we go through and find matching vendors by their "vendor_name" (required field)
		// and then we assign that vendor_id to the import data.
		// our second loop through if there is a vendor_id we overwrite that existing vendor with the import data (ignoring blanks).
		// if there is no vendor id we create a new vendor record :) awesome.

		foreach ( $data as $rowid => $row ) {
			if ( ! isset( $row['vendor_name'] ) || ! trim( $row['vendor_name'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['vendor_id'] ) || ! $row['vendor_id'] ) {
				$data[ $rowid ]['vendor_id'] = 0;
			}

		}

		// now save the data.
		foreach ( $data as $rowid => $row ) {
			//module_cache::clear_cache();
			$vendor_id = isset( $row['vendor_id'] ) ? (int) $row['vendor_id'] : 0;
			// check if this ID exists.
			if ( $vendor_id > 0 ) {
				$vendor = self::get_vendor( $vendor_id );
				if ( ! $vendor || ! isset( $vendor['vendor_id'] ) || $vendor['vendor_id'] != $vendor_id ) {
					$vendor_id = 0;
				}
			}
			if ( ! $vendor_id ) {
				// search for a custoemr based on name.
				$vendor = get_single( 'vendor', 'vendor_name', $row['vendor_name'] );
				//print_r($row); print_r($vendor);echo '<hr>';
				if ( $vendor && $vendor['vendor_id'] > 0 ) {
					$vendor_id = $vendor['vendor_id'];
				}
			}
			$vendor_id = update_insert( "vendor_id", $vendor_id, "vendor", $row );
			// see if we're updating an old contact, or adding a new primary contact.
			// match on name since that's a required field.
			$users      = module_user::get_contacts( array( 'vendor_id' => $vendor_id ) );
			$user_match = 0;
			foreach ( $users as $user ) {
				if ( $user['name'] == $row['primary_user_name'] ) {
					$user_match = $user['user_id'];
					break;
				}
			}
			$user_update = array(
				'vendor_id' => $vendor_id,
				//'name' => isset($row['primary_user_name']) ? $row['primary_user_name'] : '',
				//'last_name' => isset($row['primary_user_last_name']) ? $row['primary_user_last_name'] : '',
				//'email' => isset($row['primary_user_email']) ? $row['primary_user_email'] : '',
				//'phone' => isset($row['primary_user_phone']) ? $row['primary_user_phone'] : '',
				//'fax' => isset($row['primary_user_fax']) ? $row['primary_user_fax'] : '',
				//'mobile' => isset($row['primary_user_mobile']) ? $row['primary_user_mobile'] : '',
				//'password' => isset($row['password']) && strlen($row['password']) ? md5(trim($row['password'])) : '',
			);
			if ( isset( $row['primary_user_name'] ) ) {
				$user_update['name'] = $row['primary_user_name'];
			}
			if ( isset( $row['primary_user_last_name'] ) ) {
				$user_update['last_name'] = $row['primary_user_last_name'];
			}
			if ( isset( $row['primary_user_email'] ) ) {
				$user_update['email'] = $row['primary_user_email'];
			}
			if ( isset( $row['primary_user_phone'] ) ) {
				$user_update['phone'] = $row['primary_user_phone'];
			}
			if ( isset( $row['primary_user_fax'] ) ) {
				$user_update['fax'] = $row['primary_user_fax'];
			}
			if ( isset( $row['primary_user_mobile'] ) ) {
				$user_update['mobile'] = $row['primary_user_mobile'];
			}
			if ( isset( $row['primary_user_language'] ) ) {
				$user_update['language'] = $row['primary_user_language'];
			}
			if ( isset( $row['password'] ) && strlen( $row['password'] ) ) {
				$user_update['password'] = md5( trim( $row['password'] ) );
			}
			$user_match = update_insert( "user_id", $user_match, "user", $user_update );
			if ( $user_match && isset( $row['role'] ) && strlen( trim( $row['role'] ) ) ) {
				// find this role name and assign it to this user.
				$role = module_security::get_roles( array( 'name' => $row['role'] ) );
				if ( $role ) {
					$user_role = array_shift( $role );
					$role_id   = $user_role['security_role_id'];
					module_user::add_user_to_role( $user_match, $role_id );
				}
			}
			self::set_primary_user_id( $vendor_id, $user_match );

			// do a hack to save address.
			$existing_address = module_address::get_address( $vendor_id, 'vendor', 'physical' );
			$address_id       = ( $existing_address && isset( $existing_address['address_id'] ) ) ? (int) $existing_address['address_id'] : 'new';
			$address          = array_merge( $row, array(
				'owner_id'     => $vendor_id,
				'owner_table'  => 'vendor',
				'address_type' => 'physical',
			) );
			module_address::save_address( $address_id, $address );

			foreach ( $add_to_group as $group_id => $tf ) {
				module_group::add_to_group( $group_id, $vendor_id, 'vendor' );
			}

			// handle any extra fields.
			$extra = array();
			foreach ( $row as $key => $val ) {
				if ( ! strlen( trim( $val ) ) ) {
					continue;
				}
				if ( strpos( $key, 'extra:' ) !== false ) {
					$extra_key = str_replace( 'extra:', '', $key );
					if ( strlen( $extra_key ) ) {
						$extra[ $extra_key ] = $val;
					}
				}
			}
			if ( $extra ) {
				// we handle extra importing for vendor extra fields and contact extra fields.
				// sort out which are which.
				// but they have to be unique names. for now. oh well that'll do.

				$sql            = "SELECT `extra_key` as `id` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = 'vendor' AND `extra_key` != '' GROUP BY `extra_key` ORDER BY `extra_key`";
				$vendor_fields  = qa( $sql );
				$sql            = "SELECT `extra_key` as `id` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = 'user' AND `extra_key` != '' GROUP BY `extra_key` ORDER BY `extra_key`";
				$contact_fields = qa( $sql );
				foreach ( $extra as $extra_key => $extra_val ) {
					// does this one exist?
					if ( isset( $vendor_fields[ $extra_key ] ) ) {
						// this is a vendor extra field.
						$existing_extra = module_extra::get_extras( array(
							'owner_table' => 'vendor',
							'owner_id'    => $vendor_id,
							'extra_key'   => $extra_key
						) );
						$extra_id       = false;
						foreach ( $existing_extra as $key => $val ) {
							if ( $val['extra_key'] == $extra_key ) {
								$extra_id = $val['extra_id'];
							}
						}
						$extra_db = array(
							'extra_key'   => $extra_key,
							'extra'       => $extra_val,
							'owner_table' => 'vendor',
							'owner_id'    => $vendor_id,
						);
						$extra_id = (int) $extra_id;
						update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
					} else if ( $user_match && isset( $contact_fields[ $extra_key ] ) ) {
						// this is a primary contact extra field
						$existing_extra = module_extra::get_extras( array(
							'owner_table' => 'user',
							'owner_id'    => $user_match,
							'extra_key'   => $extra_key
						) );
						$extra_id       = false;
						foreach ( $existing_extra as $key => $val ) {
							if ( $val['extra_key'] == $extra_key ) {
								$extra_id = $val['extra_id'];
							}
						}
						$extra_db = array(
							'extra_key'   => $extra_key,
							'extra'       => $extra_val,
							'owner_table' => 'user',
							'owner_id'    => $user_match,
						);
						$extra_id = (int) $extra_id;
						update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
					}
				}
			}

		}


	}

	public static function add_credit( $vendor_id, $credit, $note = false ) {
		$vendor_data           = self::get_vendor( $vendor_id );
		$vendor_data['credit'] += $credit;
		update_insert( 'vendor_id', $vendor_id, 'vendor', array( 'credit' => $vendor_data['credit'] ) );
		if ( $note ) {
			self::add_history( $vendor_id, $note );
		}
	}

	public static function remove_credit( $vendor_id, $credit, $note = false ) {
		$vendor_data           = self::get_vendor( $vendor_id );
		$vendor_data['credit'] -= $credit;
		update_insert( 'vendor_id', $vendor_id, 'vendor', array( 'credit' => $vendor_data['credit'] ) );
		module_cache::clear( 'vendor' );
		//self::add_history($vendor_id,'Added '.dollar($credit).' credit to vendors account.');
	}


	public static function add_history( $vendor_id, $message ) {
		if ( class_exists( 'module_note', false ) ) {
			module_note::save_note( array(
				'owner_table' => 'vendor',
				'owner_id'    => $vendor_id,
				'note'        => $message,
				'rel_data'    => self::link_open( $vendor_id ),
				'note_time'   => time(),
			) );
		}
	}

	public static function get_vendor_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Vendor Data Access', array(
				_VENDOR_ACCESS_ALL,
				_VENDOR_ACCESS_ALL_COMPANY,
				_VENDOR_ACCESS_CONTACTS,
				// _VENDOR_ACCESS_TASKS,
			) );
		} else {
			return true;
		}
	}

	public static function link_public_signup() {
		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.vendor/h.public_signup' );
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
		}
	}

	public static function get_replace_fields( $vendor_id, $primary_user_id = false ) {

		$vendor_data      = module_vendor::get_vendor( $vendor_id );
		$address_combined = array();
		if ( isset( $vendor_data['vendor_address'] ) ) {
			foreach ( $vendor_data['vendor_address'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$address_combined[ $key ] = $val;
				}
			}
		}
		// do we use the primary contact or
		$contact_data = module_user::get_user( $primary_user_id ? $primary_user_id : $vendor_data['primary_user_id'] );
		//print_r($contact_data);exit;
		if ( $contact_data && $contact_data['vendor_id'] != $vendor_id && ! $contact_data['linked_parent_user_id'] ) {
			$contact_data = array(
				'user_id'   => 0,
				'vendor_id' => 0,
				'name'      => '',
				'last_name' => '',
				'email'     => '',
				'password'  => '',
				'phone'     => '',
				'mobile'    => '',
				'fax'       => '',
			);
		}

		$data = array(
			'vendor_details'        => ' - todo - ',
			'vendor_name'           => isset( $vendor_data['vendor_name'] ) ? htmlspecialchars( $vendor_data['vendor_name'] ) : _l( 'N/A' ),
			'vendor_address'        => htmlspecialchars( implode( ', ', $address_combined ) ),
			'contact_name'          => ( $contact_data['name'] != $contact_data['email'] ) ? htmlspecialchars( $contact_data['name'] . ' ' . $contact_data['last_name'] ) : '',
			'contact_first_name'    => $contact_data['name'],
			'contact_last_name'     => $contact_data['last_name'],
			// these two may be overridden when sending an email and selecting a different contact from the drop down menu.
			'first_name'            => $contact_data['name'],
			'last_name'             => $contact_data['last_name'],
			'contact_email'         => htmlspecialchars( $contact_data['email'] ),
			'contact_phone'         => htmlspecialchars( $contact_data['phone'] ),
			'contact_mobile'        => htmlspecialchars( $contact_data['mobile'] ),
			'vendor_invoice_prefix' => isset( $vendor_data['default_invoice_prefix'] ) ? $vendor_data['default_invoice_prefix'] : '',
		);

		$data = array_merge( $vendor_data, $data );

		foreach ( $vendor_data['vendor_address'] as $key => $val ) {
			$data[ 'address_' . $key ] = $val;
		}


		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			// get the vendor groups
			$g = array();
			if ( (int) $vendor_data['vendor_id'] > 0 ) {
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'vendor',
						'owner_id'    => $vendor_data['vendor_id'],
					) ) as $group
				) {
					$g[] = $group['name'];
				}
			}
			$data['vendor_group'] = implode( ', ', $g );
			// get the vendor groups
			$g = array();
			if ( $vendor_id > 0 ) {
				$vendor_data = module_vendor::get_vendor( $vendor_id );
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'vendor',
						'owner_id'    => $vendor_id,
					) ) as $group
				) {
					$g[ $group['group_id'] ] = $group['name'];
				}
			}
			$data['vendor_group'] = implode( ', ', $g );
		}

		// addition. find all extra keys for this vendor and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'vendor' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'vendor', 'owner_id' => $vendor_id ) );
			foreach ( $extras as $e ) {
				$data[ $e['extra_key'] ] = $e['extra'];
			}
			// and the primary contact
			$all_extra_fields = module_extra::get_defaults( 'user' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			if ( $contact_data && $contact_data['user_id'] ) {
				// and find the ones with values:
				$extras = module_extra::get_extras( array( 'owner_table' => 'user', 'owner_id' => $contact_data['user_id'] ) );
				foreach ( $extras as $e ) {
					$data[ $e['extra_key'] ] = $e['extra'];
				}
			}
		}

		return $data;
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>vendor` (
		`vendor_id` int(11) NOT NULL auto_increment,
		`primary_user_id` int(11) NOT NULL DEFAULT '0',
		`vendor_status` tinyint(2) NOT NULL DEFAULT '0',
		`vendor_name` varchar(255) NOT NULL DEFAULT '',
		`credit` double(10,2) NOT NULL DEFAULT '0',
		`default_tax` double(10,2) NOT NULL DEFAULT '-1',
		`default_tax_name` varchar(10) NOT NULL DEFAULT '',
		`default_invoice_prefix` varchar(10) NOT NULL DEFAULT '',
		`type` tinyint(2) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`vendor_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>user_vendor_rel` (
		`user_id` int(11) NOT NULL,
		`vendor_id` int(11) NOT NULL,
		`primary` INT NOT NULL DEFAULT  '0',
		PRIMARY KEY (`user_id`,`vendor_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		<?php
		return ob_get_clean();
	}

}
