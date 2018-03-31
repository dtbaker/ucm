<?php


class module_website extends module_base {

	public $links;
	public $website_types;

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
		$this->website_types   = array();
		$this->module_name     = "website";
		$this->module_position = 16;
		$this->version         = 2.271;
		//2.271 - 2017-07-23 - adding website invoices and timers to edit page
		//2.270 - 2016-11-02 - website timer feature
		//2.269 - 2016-07-10 - big update to mysqli
		//2.268 - 2016-04-28 - permission fix
		//2.267 - 2015-12-28 - menu speed up
		//2.266 - 2015-12-28 - extra field permission fix
		//2.265 - 2015-03-24 - import notes via csv
		//2.264 - 2015-01-23 - hook for custom data integration
		//2.263 - 2014-08-13 - website notes show summary of linked items
		//2.262 - 2014-07-31 - responsive improvements

		//2.21 - fix for customer id not getting saved.
		//2.22 - delete customer from a group
		//2.23 - theme include support.
		//2.24 - hooks for change request plugin
		//2.241 - got CSV import working nicely
		//2.242 - urlify added for better url field support
		//2.243 - bug fix in swapping customers
		//2.244 - bug fix for job currency in "edit website" page
		//2.245 - extra fields update - show in main listing option
		//2.246 - extra fields update - show in main listing option
		//2.247 - customer link fix permissions
		//2.248 - improved quick search
		//2.249 - https support in website links.
		//2.25 - 2013-04-10 - new customer permissions
		//2.251 - 2013-05-28 - email template tag improvements
		//2.252 - 2013-06-18 - customer signup fixes
		//2.253 - 2013-06-21 - permission update
		//2.254 - 2013-08-29 - support for recurring website subscriptions - eg: hosting
		//2.255 - 2013-08-29 - support for recurring website subscriptions - eg: hosting
		//2.256 - 2013-10-04 - option to disable website plugin
		//2.257 - 2013-11-15 - working on new UI
		//2.258 - 2014-03-19 - demo fix
		//2.259 - 2014-04-15 - show quotes in website view
		//2.26 - 2014-06-11 - quote/website permission fix
		//2.261 - 2014-07-02 - permission improvement


		if ( $this->can_i( 'view', module_config::c( 'project_name_plural', 'Websites' ) ) && module_website::is_plugin_enabled() ) {
			hook_add( 'website_list', 'module_website::hook_filter_var_website_list' );
			/*$this->ajax_search_keys = array(
					_DB_PREFIX.'website' => array(
							'plugin' => 'website',
							'search_fields' => array(
									'url',
									'name',
							),
							'key' => 'website_id',
							'title' => _l(module_config::c('project_name_single','Website').': '),
					),
			);*/

			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many websites?
				$name = module_config::c( 'project_name_plural', 'Websites' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$websites = $this->get_websites( array( 'customer_id' => $_REQUEST['customer_id'] ) );
					if ( count( $websites ) ) {
						$name .= " <span class='menu_label'>" . count( $websites ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "website_admin",
					'args'                => array( 'website_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'globe',
				);
			}
			$this->links[] = array(
				"name"      => module_config::c( 'project_name_plural', 'Websites' ),
				"p"         => "website_admin",
				'args'      => array( 'website_id' => false ),
				'icon_name' => 'globe',
			);

		}

	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."website` c WHERE ";
			//$sql .= " c.`website_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_websites( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					// what part of this matched?
					/*if(
							preg_match('#'.preg_quote($search_key,'#').'#i',$result['name']) ||
							preg_match('#'.preg_quote($search_key,'#').'#i',$result['last_name']) ||
							preg_match('#'.preg_quote($search_key,'#').'#i',$result['phone'])
					){
							// we matched the website contact details.
							$match_string = _l('Website Contact: ');
							$match_string .= _shl($result['website_name'],$search_key);
							$match_string .= ' - ';
							$match_string .= _shl($result['name'],$search_key);
							// hack
							$_REQUEST['website_id'] = $result['website_id'];
							$ajax_results [] = '<a href="'.module_user::link_open_contact($result['user_id']) . '">' . $match_string . '</a>';
					}else{*/
					$match_string    = _l( 'Website: ' );
					$match_string    .= _shl( $result['name'] . ( $result['url'] != $result['name'] ? ' (' . self::urlify( $result['url'] ) . ')' : '' ), $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['website_id'] ) . '">' . $match_string . '</a>';
					//$ajax_results [] = $this->link_open($result['website_id'],true);
					/*}*/
				}
			}
		}

		return $ajax_results;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_website::can_i( 'view', 'Websites' ) ) {
			$customer_id = ! empty( $search_options['vars']['lookup_customer_id'] ) ? (int) $search_options['vars']['lookup_customer_id'] : false;

			$res = module_website::get_websites( array(
				'generic'     => $search_string,
				'customer_id' => $customer_id,
			), array( 'columns' => 'u.website_id, u.name' ) );

			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['website_id'],
					'value' => $row['name']
				);
			}
		}

		return $result;
	}


	public static function link_generate( $website_id = false, $options = array(), $link_options = array() ) {

		$key = 'website_id';
		if ( $website_id === false && $link_options ) {
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
			$options['type'] = 'website';
		}
		$options['page'] = 'website_admin';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['website_id'] = $website_id;
		$options['module']                  = 'website';
		if ( (int) $website_id > 0 ) {
			$data            = self::get_website( $website_id );
			$options['data'] = $data;
		} else {
			$data = array();
			if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
				$data['customer_id'] = (int) $_REQUEST['customer_id'];
			}
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				// we are not doing a full <a href> link, only the url (eg: create new website)
			} else {
				// we are trying to do a full <a href> link -
				return _l( 'N/A' );
			}
		}
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? _l( 'N/A' ) : $data['name'];
		if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
			$bubble_to_module = array(
				'module'   => 'customer',
				'argument' => 'customer_id',
			);
		}
		array_unshift( $link_options, $options );

		if ( ! module_security::has_feature_access( array(
			'name'        => 'Customers',
			'module'      => 'customer',
			'category'    => 'Customer',
			'view'        => 1,
			'description' => 'view',
		) ) ) {
			$bubble_to_module = false;
			/*if(!isset($options['full']) || !$options['full']){
					return '#';
			}else{
					return isset($options['text']) ? $options['text'] : _l('N/A');
			}*/

		}
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $website_id, $full = false ) {
		return self::link_generate( $website_id, array( 'full' => $full ) );
	}


	public function process() {
		$errors = array();
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['website_id'] ) {
			$data = self::get_website( $_REQUEST['website_id'] );
			if ( module_form::confirm_delete( 'website_id', "Really delete " . module_config::c( 'project_name_single', 'Website' ) . ": " . $data['name'], self::link_open( $_REQUEST['website_id'] ) ) ) {
				$this->delete_website( $_REQUEST['website_id'] );
				set_message( module_config::c( 'project_name_single', 'Website' ) . " deleted successfully" );
				redirect_browser( self::link_open( false ) );
			}
		} else if ( "save_website" == $_REQUEST['_process'] ) {
			$website_id = $this->save_website( $_REQUEST['website_id'], $_POST );
			hook_handle_callback( 'website_save', $website_id );
			$_REQUEST['_redirect'] = $this->link_open( $website_id );
			set_message( module_config::c( 'project_name_single', 'Website' ) . " saved successfully" );
		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}


	public static function get_websites( $search = array(), $return_options = array() ) {
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
		if ( isset( $return_options['columns'] ) ) {
			$sql .= $return_options['columns'];
		} else {
			$sql .= " u.*,u.website_id AS id ";
			$sql .= ", u.name AS name ";
			$sql .= ", c.customer_name ";
			$sql .= ", cc.name AS customer_contact_fname ";
			$sql .= ", cc.last_name AS customer_contact_lname ";
			$sql .= ", cc.email AS customer_contact_email ";
			// add in our extra fields for the csv export
			//if(isset($_REQUEST['import_export_go']) && $_REQUEST['import_export_go'] == 'yes'){
			if ( class_exists( 'module_extra', false ) ) {
				$sql .= " , (SELECT GROUP_CONCAT(ex.`extra_key` ORDER BY ex.`extra_id` ASC SEPARATOR '" . _EXTRA_FIELD_DELIM . "') FROM `" . _DB_PREFIX . "extra` ex WHERE owner_id = u.website_id AND owner_table = 'website') AS extra_keys";
				$sql .= " , (SELECT GROUP_CONCAT(ex.`extra` ORDER BY ex.`extra_id` ASC SEPARATOR '" . _EXTRA_FIELD_DELIM . "') FROM `" . _DB_PREFIX . "extra` ex WHERE owner_id = u.website_id AND owner_table = 'website') AS extra_vals";
			}
		}
		$from  = " FROM `" . _DB_PREFIX . "website` u ";
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "customer` c USING (customer_id)";
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "user` cc ON c.primary_user_id = cc.user_id ";
		$where = " WHERE 1 ";
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' OR ";
			$where .= " u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
		if ( isset( $search['url'] ) && $search['url'] ) {
			$str   = db_escape( $search['url'] );
			$where .= " AND ";
			$where .= " u.url = '$str' ";
			$where .= ' ';
		}
		foreach ( array( 'customer_id', 'status' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` = '$str'";
			}
		}
		// tie in with customer permissions to only get jobs from customers we can access.
		switch ( module_customer::get_customer_data_access() ) {
			case _CUSTOMER_ACCESS_ALL:
				// all customers! so this means all jobs!
				break;
			case _CUSTOMER_ACCESS_ALL_COMPANY:
			case _CUSTOMER_ACCESS_CONTACTS:
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
				break;
			case _CUSTOMER_ACCESS_TASKS:
				// only customers who have a job that I have a task under.
				// this is different to "assigned jobs" Above
				// this will return all jobs for a customer even if we're only assigned a single job for that customer
				// tricky!
				// copied from customer.php
				$where .= " AND u.website_id IN ";
				$where .= " ( SELECT jj.website_id FROM `" . _DB_PREFIX . "job` jj ";
				$where .= " LEFT JOIN `" . _DB_PREFIX . "task` tt ON jj.job_id = tt.job_id ";
				$where .= " WHERE (jj.user_id = " . (int) module_security::get_loggedin_id() . " OR tt.user_id = " . (int) module_security::get_loggedin_id() . ")";
				$where .= " )";

				break;

		}

		$group_order = ' GROUP BY u.website_id ORDER BY u.name'; // stop when multiple company sites have same region
		$sql         = $sql . $from . $where . $group_order;


		$result = qa( $sql );

		//module_security::filter_data_set("website",$result);
		return $result;
		//		return get_multiple("website",$search,"website_id","fuzzy","name");

	}

	public static function get_website( $website_id ) {
		$website = get_single( "website", "website_id", $website_id );
		if ( $website ) {
			switch ( module_customer::get_customer_data_access() ) {
				case _CUSTOMER_ACCESS_ALL:
					// all customers! so this means all jobs!
					break;
				case _CUSTOMER_ACCESS_ALL_COMPANY:
				case _CUSTOMER_ACCESS_CONTACTS:
				case _CUSTOMER_ACCESS_STAFF:
					$valid_customer_ids = module_security::get_customer_restrictions();
					$is_valid_website   = isset( $valid_customer_ids[ $website['customer_id'] ] );
					if ( ! $is_valid_website ) {
						$website = false;
					}
					break;
				case _CUSTOMER_ACCESS_TASKS:
					// only customers who have linked jobs that I am assigned to.
					$has_job_access = false;
					if ( isset( $website['customer_id'] ) && $website['customer_id'] ) {
						$jobs = module_job::get_jobs( array( 'customer_id' => $website['customer_id'] ) );
						foreach ( $jobs as $job ) {
							if ( $job['user_id'] == module_security::get_loggedin_id() ) {
								$has_job_access = true;
								break;
							}
							$tasks = module_job::get_tasks( $job['job_id'] );
							foreach ( $tasks as $task ) {
								if ( $task['user_id'] == module_security::get_loggedin_id() ) {
									$has_job_access = true;
									break;
								}
							}
						}
					}
					if ( ! $has_job_access ) {
						$website = false;
					}
					break;

			}
		}

		if ( ! $website ) {
			$website = array(
				'website_id'  => 'new',
				'customer_id' => isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : 0,
				'name'        => '',
				'status'      => module_config::s( 'website_status_default', 'New' ),
				'url'         => '',
			);
		}

		return $website;
	}

	public function save_website( $website_id, $data ) {
		if ( (int) $website_id > 0 ) {
			$original_website_data = $this->get_website( $website_id );
			if ( ! $original_website_data || $original_website_data['website_id'] != $website_id ) {
				$original_website_data = array();
				$website_id            = false;
			}
		} else {
			$original_website_data = array();
			$website_id            = false;
		}
		if ( _DEMO_MODE && $website_id == 1 ) {
			set_error( 'This is a Demo Website. Some things cannot be changed.' );
			foreach ( array( 'name', 'url', 'customer_id' ) as $key ) {
				if ( isset( $data[ $key ] ) ) {
					unset( $data[ $key ] );
				}
			}
		}

		// check create permissions.
		if ( ! $website_id && ! self::can_i( 'create', 'Websites' ) ) {
			// user not allowed to create websites.
			set_error( 'Unable to create new Websites' );
			redirect_browser( self::link_open( false ) );
		}

		$website_id = update_insert( "website_id", $website_id, "website", $data );
		if ( isset( $original_website_data['customer_id'] ) && $original_website_data['customer_id'] && isset( $data['customer_id'] ) && $data['customer_id'] && $original_website_data['customer_id'] != $data['customer_id'] ) {
			//module_cache::clear_cache();
			// the customer id has changed. update jobs and invoices.
			// bad! this will swap all jobs, invoices and files from this customer to another customer.
			//module_job::customer_id_changed($original_website_data['customer_id'],$data['customer_id']);
		}
		module_extra::save_extras( 'website', 'website_id', $website_id );

		return $website_id;
	}

	public static function delete_website( $website_id ) {
		$website_id = (int) $website_id;
		if ( _DEMO_MODE && $website_id == 1 ) {
			set_error( 'Sorry this is a Demo Website. It cannot be deleted.' );

			return;
		}
		if ( (int) $website_id > 0 ) {
			$original_website_data = self::get_website( $website_id );
			if ( ! $original_website_data || $original_website_data['website_id'] != $website_id ) {
				return false;
			}
		}
		if ( ! self::can_i( 'delete', 'Websites' ) ) {
			return false;
		}

		hook_handle_callback( 'website_deleted', $website_id );
		$sql = "DELETE FROM " . _DB_PREFIX . "website WHERE website_id = '" . $website_id . "' LIMIT 1";
		query( $sql );
		if ( class_exists( 'module_group', false ) ) {
			module_group::delete_member( $website_id, 'website' );
		}
		foreach ( module_job::get_jobs( array( 'website_id' => $website_id ) ) as $val ) {
			module_job::delete_job( $val['website_id'] );
		}
		module_note::note_delete( "website", $website_id );
		module_extra::delete_extras( 'website', 'website_id', $website_id );
	}

	public function login_link( $website_id ) {
		return module_security::generate_auto_login_link( $website_id );
	}

	public static function get_statuses() {
		$sql      = "SELECT `status` FROM `" . _DB_PREFIX . "website` GROUP BY `status` ORDER BY `status`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['status'] ] = $r['status'];
		}

		return $statuses;
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>website` (
		`website_id` int(11) NOT NULL auto_increment,
		`customer_id` INT(11) NULL,
		`url` varchar(255) NOT NULL DEFAULT  '',
		`name` varchar(255) NOT NULL DEFAULT  '',
		`status` varchar(255) NOT NULL DEFAULT  '',
		`date_created` date NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`website_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php
		// todo: add default admin permissions.

		return ob_get_clean();
	}

	public static function handle_import_row_debug( $row, $add_to_group, $extra_options ) {
		return self::handle_import_row( $row, true, $add_to_group, $extra_options );
	}

	public static function handle_import_row( $row, $debug, $add_to_group, $extra_options ) {

		$debug_string = '';
		if ( ! isset( $row['name'] ) ) {
			$row['name'] = '';
		}
		if ( ! isset( $row['url'] ) ) {
			$row['url'] = '';
		}

		if ( isset( $row['website_id'] ) && (int) $row['website_id'] > 0 ) {
			// check if this ID exists.
			$website = self::get_website( $row['website_id'] );
			if ( ! $website || $website['website_id'] != $row['website_id'] ) {
				$row['website_id'] = 0;
			}
		}
		if ( ! isset( $row['website_id'] ) || ! $row['website_id'] ) {
			$row['website_id'] = 0;
		}
		if ( isset( $row['name'] ) && strlen( trim( $row['name'] ) ) ) {
			// we have a website name!
			// search for a website based on name.
			$website = get_single( 'website', 'name', $row['name'] );
			if ( $website && $website['website_id'] > 0 ) {
				$row['website_id'] = $website['website_id'];
			}
		} else if ( isset( $row['url'] ) ) {
			$row['name'] = $row['url'];
		}
		if ( ! $row['website_id'] && isset( $row['url'] ) && strlen( trim( $row['url'] ) ) ) {
			// we have a url! find a match too.
			$website = get_single( 'website', 'url', $row['url'] );
			if ( $website && $website['website_id'] > 0 ) {
				$row['website_id'] = $website['website_id'];
			}
		}
		if ( ! strlen( $row['name'] ) && ! strlen( $row['url'] ) ) {
			$debug_string .= _l( 'No website data to import' );
			if ( $debug ) {
				echo $debug_string;
			}

			return false;
		}
		// duplicates.
		//print_r($extra_options);exit;
		if ( isset( $extra_options['duplicates'] ) && $extra_options['duplicates'] == 'ignore' && (int) $row['website_id'] > 0 ) {
			if ( $debug ) {
				$debug_string .= _l( 'Skipping import, duplicate of website %s', self::link_open( $row['website_id'], true ) );
				echo $debug_string;
			}

			// don't import duplicates
			return false;
		}
		$row['customer_id'] = 0; // todo - support importing of this id? nah
		if ( isset( $row['customer_name'] ) && strlen( trim( $row['customer_name'] ) ) > 0 ) {
			// check if this customer exists.
			$customer = get_single( 'customer', 'customer_name', $row['customer_name'] );
			if ( $customer && $customer['customer_id'] > 0 ) {
				$row['customer_id'] = $customer['customer_id'];
				$debug_string       .= _l( 'Linked to customer %s', module_customer::link_open( $row['customer_id'], true ) ) . ' ';
			} else {
				$debug_string .= _l( 'Create new customer: %s', htmlspecialchars( $row['customer_name'] ) ) . ' ';
			}
		} else {
			$debug_string .= _l( 'No customer' ) . ' ';
		}
		if ( $row['website_id'] ) {
			$debug_string .= _l( 'Replace existing website: %s', self::link_open( $row['website_id'], true ) ) . ' ';
		} else {
			$debug_string .= _l( 'Insert new website: %s', htmlspecialchars( $row['url'] ) ) . ' ';
		}

		$customer_primary_user_id = 0;
		if ( $row['customer_id'] > 0 && isset( $row['customer_contact_email'] ) && strlen( trim( $row['customer_contact_email'] ) ) ) {
			$users = module_user::get_users( array( 'customer_id' => $row['customer_id'] > 0 ) );
			foreach ( $users as $user ) {
				if ( strtolower( trim( $user['email'] ) ) == strtolower( trim( $row['customer_contact_email'] ) ) ) {
					$customer_primary_user_id = $user['user_id'];
					$debug_string             .= _l( 'Customer primary contact is: %s', module_user::link_open_contact( $customer_primary_user_id, true ) ) . ' ';
					break;
				}
			}
		}

		if ( $debug ) {
			echo $debug_string;

			return true;
		}
		if ( isset( $extra_options['duplicates'] ) && $extra_options['duplicates'] == 'ignore' && $row['customer_id'] > 0 ) {
			// don't update customer record with new one.

		} else if ( ( isset( $row['customer_name'] ) && strlen( trim( $row['customer_name'] ) ) > 0 ) || $row['customer_id'] > 0 ) {
			// update customer record with new one.
			$row['customer_id'] = update_insert( 'customer_id', $row['customer_id'], 'customer', $row );
			if ( isset( $row['customer_contact_fname'] ) || isset( $row['customer_contact_email'] ) ) {
				$data = array(
					'customer_id' => $row['customer_id']
				);
				if ( isset( $row['customer_contact_fname'] ) ) {
					$data['name'] = $row['customer_contact_fname'];
				}
				if ( isset( $row['customer_contact_lname'] ) ) {
					$data['last_name'] = $row['customer_contact_lname'];
				}
				if ( isset( $row['customer_contact_email'] ) ) {
					$data['email'] = $row['customer_contact_email'];
				}
				if ( isset( $row['customer_contact_phone'] ) ) {
					$data['phone'] = $row['customer_contact_phone'];
				}
				$customer_primary_user_id = update_insert( "user_id", $customer_primary_user_id, "user", $data );
				module_customer::set_primary_user_id( $row['customer_id'], $customer_primary_user_id );
			}
		}
		$website_id = (int) $row['website_id'];
		// check if this ID exists.
		$website = self::get_website( $website_id );
		if ( ! $website || $website['website_id'] != $website_id ) {
			$website_id = 0;
		}
		$website_id = update_insert( "website_id", $website_id, "website", $row );

		// ad notes if possible
		if ( isset( $row['notes'] ) && strlen( trim( $row['notes'] ) ) ) {
			if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
				module_note::save_note( array(
					'owner_table' => 'website',
					'owner_id'    => $website_id,
					'note'        => trim( $row['notes'] ),
					'note_time'   => time(),
				) );
			}
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
			foreach ( $extra as $extra_key => $extra_val ) {
				// does this one exist?
				$existing_extra = module_extra::get_extras( array(
					'owner_table' => 'website',
					'owner_id'    => $website_id,
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
					'owner_table' => 'website',
					'owner_id'    => $website_id,
				);
				$extra_id = (int) $extra_id;
				update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
			}
		}

		foreach ( $add_to_group as $group_id => $tf ) {
			module_group::add_to_group( $group_id, $website_id, 'website' );
		}

		return $website_id;

	}

	public static function handle_import( $data, $add_to_group, $extra_options ) {

		// woo! we're doing an import.
		$count = 0;
		// first we find any matching existing websites. skipping duplicates if option is set.
		foreach ( $data as $rowid => $row ) {
			if ( self::handle_import_row( $row, false, $add_to_group, $extra_options ) ) {
				$count ++;
			}
		}

		return $count;


	}

	public static function urlify( $url ) {
		// todo: check for http:// etc..
		if ( $url ) {
			return htmlspecialchars( ! preg_match( '#^https?://#', $url ) ? 'http://' . $url : $url );
		}

		return '';
	}

	public static function get_replace_fields( $website_id, $website_data = false ) {

		if ( ! $website_data ) {
			$website_data = self::get_website( $website_id );
		}

		$data = array(
			'website_name' => $website_data['name'],
			'website_url'  => self::urlify( $website_data['url'] ),
		);

		$data = array_merge( $data, $website_data );


		if ( class_exists( 'module_group', false ) ) {
			// get the website groups
			$g = array();
			if ( $website_id > 0 ) {
				$website_data = module_website::get_website( $website_id );
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'website',
						'owner_id'    => $website_id,
					) ) as $group
				) {
					$g[ $group['group_id'] ] = $group['name'];
				}
			}
			$data['website_group'] = implode( ', ', $g );
		}

		// addition. find all extra keys for this website and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		$all_extra_fields = module_extra::get_defaults( 'website' );
		foreach ( $all_extra_fields as $e ) {
			$data[ $e['key'] ] = _l( 'N/A' );
		}
		// and find the ones with values:
		$extras = module_extra::get_extras( array( 'owner_table' => 'website', 'owner_id' => $website_id ) );
		foreach ( $extras as $e ) {
			$data[ $e['extra_key'] ] = $e['extra'];
		}

		return $data;
	}

	public static function hook_filter_var_website_list( $call, $attributes ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}
		foreach (
			module_website::get_websites( array(
				'customer_id' => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false,
			), array( 'columns' => 'u.website_id, u.name' ) ) as $website
		) {
			$attributes[ $website['website_id'] ] = $website['name'];
		}

		return $attributes;
	}

}