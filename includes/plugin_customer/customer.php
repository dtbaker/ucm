<?php


define( '_CUSTOMER_ACCESS_ALL', 'All customers in system' ); // do not change string
define( '_CUSTOMER_ACCESS_ALL_COMPANY', 'Only customers from companies I have access to' ); // do not change string
define( '_CUSTOMER_ACCESS_CONTACTS', 'Only customer I am assigned to as a contact' ); // do not change string
define( '_CUSTOMER_ACCESS_TASKS', 'Only customers I am assigned to in a job' ); // do not change string
define( '_CUSTOMER_ACCESS_STAFF', 'Only customers I am assigned to as a staff member' ); // do not change string

define( '_CUSTOMER_STATUS_OVERDUE', 3 );
define( '_CUSTOMER_STATUS_OWING', 2 );
define( '_CUSTOMER_STATUS_PAID', 1 );


define( '_CUSTOM_DATA_HOOK_LOCATION_CUSTOMER_SIDEBAR', 8 );
define( '_CUSTOM_DATA_HOOK_LOCATION_CUSTOMER_FOOTER', 9 ); // inspect the select element on Custom Data page to see used ID's and choose an unused one for new hook areas.
define( '_CUSTOMER_BILLING_TYPE_NORMAL', 0 );
define( '_CUSTOMER_BILLING_TYPE_SUPPLIER', 1 );


class module_customer extends module_base {

	public $links;
	public $customer_types;
	public $customer_id;

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
		$this->customer_types  = array();
		$this->module_name     = "customer";
		$this->module_position = 5.1;
		$this->version         = 2.490;
		//2.490 - 2018-06-05 - fix for supplier groups
		//2.489 - 2018-04-03 - fix saving customer.
		//2.488 - 2018-04-01 - logging improvements.
		//2.487 - 2017-07-26 - customer portal improvements
		//2.486 - 2017-07-23 - customer portal improvements
		//2.485 - 2017-05-30 - customer api work
		//2.484 - 2017-05-28 - customer api work
		//2.483 - 2017-05-25 - invoice prefix fixes
		//2.482 - 2017-05-22 - customer email fixes
		//2.481 - 2017-05-02 - archive feature on customers
		//2.480 - 2017-05-02 - big changes
		//2.479 - 2017-04-19 - adding support for vendor types and customer ID
		//2.478 - 2017-04-18 - archived fix
		//2.476 - 2017-03-15 - external customer view link.
		//2.475 - 2017-03-09 - credit view permission
		//2.474 - 2017-03-01 - archived
		//2.473 - 2017-02-20 - extra field link to custom data
		//2.472 - 2017-01-02 - quote, job, invoice prefix
		//2.471 - 2016-11-02 - unique customer config
		//2.470 - 2016-07-15 - create customer link fix
		//2.469 - 2016-07-10 - big update to mysqli
		//2.468 - 2016-06-09 - custom data embed on customer page
		//2.467 - 2016-04-29 - signup customer link fix
		//2.466 - 2016-04-28 - welcome email button
		//2.465 - 2016-02-06 - signup and installation fix
		//2.464 - 2016-02-05 - ajax customer listing
		//2.463 - 2016-02-01 - customer type bug fix
		//2.462 - 2016-01-28 - initial page widget development work
		//2.461 - 2015-12-08 - import/signup fix for customer types
		//2.46 - 2015-10-17 - email optional required field
		//2.459 - 2015-09-27 - customer_signup_email_welcome_template config variable
		//2.458 - 2015-09-25 - ability to create more customer types
		//2.457 - 2015-09-25 - ability to create more customer types
		//2.456 - 2015-09-23 - ability to create more customer types
		//2.455 - 2015-07-18 - customer map
		//2.454 - 2015-06-15 - customer creation bug fix
		//2.453 - 2015-06-08 - leads permission fix
		//2.452 - 2015-06-08 - leads permission fix
		//2.451 - 2015-06-07 - bug fix
		//2.45 - 2015-05-17 - signup ajax fix
		//2.449 - 2015-05-03 - responsive improvements
		//2.448 - 2015-03-08 - extra field permission fix
		//2.447 - 2015-02-12 - customer/lead staff import/export
		//2.446 - 2015-01-28 - customer list sort by extra field support
		//2.445 - 2015-01-23 - hook for custom data integration
		//2.444 - 2015-01-14 - hooks added to table print out and data processing
		//2.443 - 2015-01-08 - signup fixes
		//2.442 - 2014-12-19 - signup form required fields
		//2.441 - 2014-12-17 - signup form on login
		//2.44 - 2014-12-12 - customer signup css bug fix
		//2.439 - 2014-12-09 - customer signup improvements
		//2.438 - 2014-12-08 - customer signup improvements
		//2.437 - 2014-11-26 - job extra fields added to signup
		//2.436 - 2014-11-26 - invoice delete speed improvement
		//2.435 - 2014-11-05 - import notes into system
		//2.434 - 2014-09-18 - cron job debug customer status information
		//2.433 - 2014-09-03 - delete quote when customer is deleted
		//2.432 - 2014-08-28 - invoice prefix back up
		//2.431 - 2014-08-27 - started work on customer statement email feature
		//2.43 - 2014-08-20 - customer group signup
		//2.429 - 2014-07-31 - responsive improvements
		//2.427 - 2014-07-21 - company permission improvement
		//2.426 - 2014-07-16 - cache speed fix
		//2.425 - 2014-07-15 - translation fix
		//2.424 - 2014-07-14 - import + export fixes
		//2.423 - 2014-07-02 - permission improvement
		//2.422 - 2014-06-19 - company permission fix
		//2.421 - 2014-04-15 - merged customer contact fix
		//2.42 - 2014-04-10 - customer signup subscription start
		//2.419 - 2014-03-31 - customer CSV export contact extra fields
		//2.418 - 2014-03-26 - save and return button added
		//2.417 - 2014-03-15 - password reset fix for customer contacts
		//2.416 - 2014-03-04 - return credit to customer after invoice payment is deleted
		//2.415 - 2014-03-03 - currency format fix for credit balance
		//2.414 - 2014-02-17 - leads_enabled fix
		//2.413 - 2014-01-31 - demo fix
		//2.412 - 2014-01-24 - delete customer fix
		//2.411 - 2014-01-23 - searching by extra fields
		//2.41 - 2014-01-23 - deleting customer fix
		//2.399 - 2014-01-21 - lead from customer signup page
		//2.398 - 2014-01-20 - new leads feature
		//2.397 - 2014-01-18 - new leads feature
		//2.396 - 2014-01-10 - phone / email links in contact listings
		//2.395 - 2014-01-01 - leads feature
		//2.394 - 2013-12-15 - working on new UI
		//2.393 - 2013-11-11 - new UI work
		//2.392 - 2013-11-03 - contact extra fields supported in invoice
		//2.391 - 2013-09-26 - fax/mobile added to customer import
		//2.389 - 2013-09-20 - update customer status after invoice cancel
		//2.388 - 2013-09-12 - update customer status after invoice delete
		//2.387 - 2013-09-12 - create new link fixes
		//2.386 - 2013-09-11 - customer dropdown selection fix in job creation
		//2.385 - 2013-09-10 - dashboard speed fixes
		//2.384 - 2013-09-09 - cache fix
		//2.383 - 2013-09-06 - customer signup send invoice fix
		//2.382 - 2013-09-06 - easier to disable certain plugins
		//2.381 - 2013-09-05 - subscriptions on customer signup, option for auto sending invoice
		//2.38 - 2013-09-03 - subscriptions added to customer signup form
		//2.379 - 2013-08-30 - added memcache support for huge speed improvements
		//2.378 - 2013-07-30 - customer delete improvement
		//2.377 - 2013-07-16 - customer delete fix
		//2.376 - 2013-06-21 - permission update
		//2.375 - 2013-06-18 - customer signup fixes
		//2.374 - 2013-06-18 - making room for the upcoming company feature
		//2.373 - 2013-06-14 - customer color coding
		//2.372 - 2013-05-28 - email template field first_name/last_name fix
		//2.371 - 2013-05-28 - email template field customer_address fix
		//2.37 - 2013-05-28 - email template field improvements
		//2.369 - 2013-04-27 - fix for large customer lists

		//2.31 - added group export
		//2.32 - added search by group
		//2.33 - search group permissions
		//2.331 - fix for group perms on main listing
		//2.332 - fix for customer_id null in get. retured an array with address.
		//2.333 - customer importing extra fields.
		//2.334 - customer contacts - all permissions on main customer listing.
		//2.335 - delete customer from group
		//2.336 - delete button on new customer page.
		//2.337 - import customers fixed.
		//2.34 - new feature: customer logo preview
		//2.35 - support for "ajax_contact_list" used in ticket edit area.
		//2.351 - more button on primary contact header in customer
		//2.352 - customer link htmlspecialchars fix
		//2.353 - showing notes in manual invoices
		//2.355 - importing user passwords and roles.
		//2.356 - subscriptions for customers
		//2.357 - bug fix on customers editing logos and searching by last name
		//2.358 - php5/6 fix
		//2.359 - create customer from ticket
		//2.360 - speed improvements
		//2.361 - address search fix for customers
		//2.362 - better moving customer contacts between customers
		//2.363 - show invoice list on main customer page (turn off with customer_list_show_invoices setting)
		//2.364 - extra fields update - show in main listing option
		//2.365 - support for customer signup system
		//2.366 - customer signup fixes
		//2.367 - 2013-04-10 - new customer permissions and staff selection
		//2.368 - 2013-04-10 - fix for new customer permissions

		module_config::register_css( 'customer', 'customer.css' );
		module_config::register_js( 'customer', 'customer.js' );

		hook_add( 'customer_list', 'module_customer::hook_filter_var_customer_list' );
		hook_add( 'header_print_js', 'module_customer::hook_header_print_js' );
		hook_add( 'custom_data_menu_locations', 'module_customer::hook_filter_custom_data_menu_locations' );

		hook_add( 'api_callback_customer', 'module_customer::api_filter_customer' );

		if ( class_exists( 'module_template', false ) ) {

			module_template::init_template( 'customer_statement_email', 'Dear {CUSTOMER_NAME},<br>
<br>
Please find below a copy of your details.<br><br>
{EMAIL_DETAILS}<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Customer Statement: {CUSTOMER_NAME}', array(
				'CUSTOMER_NAME' => 'Customers Name',
			) );


			module_template::init_template( 'customer_welcome_email', 'Dear {CUSTOMER_NAME},<br/>
<br/>
Welcome to the Admin System<br/><br/>
Your username is: <strong>{CONTACT_EMAIL}</strong><br/>
To access the system and set your password please <a href="{PASSWORD_RESET_URL}">click here</a>.<br/><br/>
Kind Regards,<br/><br/>
{FROM_NAME}
', 'Welcome {CUSTOMER_NAME} to the Admin System', array() );

			if ( file_exists( dirname( __FILE__ ) . '/pages/customer_signup.php' ) ) {
				module_template::init_template( 'customer_signup_thank_you_page', '<h2>Thank You</h2>
    <p>Thank you. Your  request has been submitted successfully.</p>
    <p>Please check your email.</p>
    ', 'Displayed after a customer signs up.', 'code' );

				module_template::init_template( 'customer_signup_email_welcome', 'Dear {CUSTOMER_NAME},<br>
<br>
Thank you for completing the information form on our website. We will be in touch shortly.<br><br>
Kind Regards,<br><br>
{FROM_NAME}
', 'Welcome {CUSTOMER_NAME}', array() );

				module_template::init_template( 'customer_signup_email_admin', 'Dear Admin,<br>
<br>
A customer has signed up in the system!<br><br>
View/Edit this customer by going here: {CUSTOMER_NAME_LINK}<br><br>
Website: {WEBSITE_NAME_LINK}<br><br>
Jobs: {JOB_LINKS}<br><br>
Notes: {NOTES}<br><br>
{UPLOADED_FILES}<br><br>
{SYSTEM_NOTE}
', 'New Customer Signup: {CUSTOMER_NAME}', array() );
				module_template::init_template( 'customer_signup_form_wrapper', '{SIGNUP_FORM}
', 'Customer Signup Form', array() );
			}


			module_template::init_template( 'customer_portal_invoice', 'A customer has requested an invoice from the portal..<br><br>
Customer: <strong>{CUSTOMER_LINK}</strong> 
Invoice: <strong>{INVOICE_LINK}</strong>
', 'Portal Invoice Request: {CUSTOMER_NAME}', array(
				'CUSTOMER_NAME' => 'Contract Name',
				'CUSTOMER_LINK' => 'Link to contract for customer',
				'INVOICE_LINK'  => 'Link to contract for customer',
			) );


		}
	}

	public static function hook_filter_custom_data_menu_locations( $call, $menu_locations ) {
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_CUSTOMER_FOOTER ]  = _l( 'Customer Footer' );
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_CUSTOMER_SIDEBAR ] = _l( 'Customer Sidebar' );

		return $menu_locations;
	}


	public static function api_filter_customer( $hook, $response, $endpoint, $method ) {
		$response['customer'] = true;
		switch ( $method ) {
			case 'create':

				$customer_data = isset( $_POST['customer_data'] ) && is_array( $_POST['customer_data'] ) ? $_POST['customer_data'] : array();

				$response['customer_id'] = 0;
				if (
					! empty( $customer_data )
					&& ! empty( $customer_data['customer_name'] )
					&& ! empty( $customer_data['contact_first_name'] )
					&& ! empty( $customer_data['contact_email'] )
					&& ! empty( $customer_data['contact_phone'] )
				) {
					// we're good to create a customer.
					$customer_id = update_insert( 'customer_id', 0, 'customer', array(
						'customer_name' => $customer_data['customer_name'],
					) );
					if ( $customer_id ) {
						//address details.
						if ( ! empty( $customer_data['customer_address'] ) ) {
							$address_fields = array(
								'line_1',
								'line_2',
								'suburb',
								'state',
								'region',
								'country',
								'post_code',
							);
							$address        = array(
								'owner_id'     => $customer_id,
								'owner_table'  => 'customer',
								'address_type' => 'physical',
							);
							foreach ( $address_fields as $address_field ) {
								if ( ! empty( $customer_data['customer_address'][ $address_field ] ) ) {
									$address[ $address_field ] = $customer_data['customer_address'][ $address_field ];
								}
							}
							module_address::save_address( 'new', $address );
						}
						$response['customer_id'] = $customer_id;
						// create a contact.
						global $plugins;
						$contact_data = array(
							'customer_id' => $customer_id,
							'email'       => $customer_data['contact_email'],
							'name'        => $customer_data['contact_first_name'],
							'last_name'   => ! empty( $customer_data['contact_last_name'] ) ? $customer_data['contact_last_name'] : '',
							'phone'       => $customer_data['contact_phone'],
							'fax'         => ! empty( $customer_data['contact_fax'] ) ? $customer_data['contact_fax'] : '',
							'mobile'      => ! empty( $customer_data['contact_mobile'] ) ? $customer_data['contact_mobile'] : '',
						);
						$user_id      = $plugins['user']->create_user( $contact_data, 'contact' );
						if ( $user_id ) {
							$response['contact_id'] = $user_id;
							self::set_primary_user_id( $customer_id, $user_id );
						}
					}
				}

				break;
			case 'search':

				$search                = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				$customers             = self::get_customers( $search );
				$response['customers'] = array();
				foreach ( $customers as $customer ) {
					$response['customers'][] = $customer;
				}

				break;
			case 'add_to_group':


				$group_owner = self::get_group_owner_slug();

				$customer_id = ! empty( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
				if ( $customer_id ) {
					$customer_data = self::get_customer( $customer_id );

					if ( $customer_data && $customer_data['customer_id'] == $customer_id ) {
						// group to add
						$response['customer_id'] = $customer_id;
						$group_name              = ! empty( $_REQUEST['group_name'] ) ? $_REQUEST['group_name'] : false;
						if ( $group_name ) {
							$existing_groups = module_group::get_groups( $group_owner );
							$existing_id     = false;
							foreach ( $existing_groups as $group ) {
								if ( $group['name'] == $group_name ) {
									$existing_id = $group['group_id'];
								}
							}
							if ( ! $existing_id ) {
								$existing_id = update_insert( 'group_id', 'new', 'group', array(
									'name'        => $group_name,
									'owner_table' => $group_owner,
								) );
							}
							if ( $existing_id ) {
								module_group::add_to_group( $existing_id, $customer_id, $group_owner );
								$response['group_added'] = true;
							}
						}
					}

				}

				break;
		}

		return $response;
	}


	public function pre_menu() {

		global $load_modules;

		/*if($this->can_i('view','Customers')){
			$this->links['customers'] = array(
				"name"=>"Customers",
				"p"=>"customer_admin_list",
				"args"=>array('customer_id'=>false,'customer_type_id'=>0),
                'icon_name' => 'users',
			);
            if(in_array('customer',$load_modules)){
                $this->links['customers']['current'] = (self::get_current_customer_type_id() == 0);
            }
        }*/

		$customer_types = self::get_customer_types();
		foreach ( $customer_types as $customer_type ) {
			if ( ! empty( $customer_type['type_name_plural'] ) ) {
				if ( $this->can_i( 'view', $customer_type['type_name_plural'] ) ) {
					$this->links[ 'customer_type_' . $customer_type['customer_type_id'] ] = array(
						"name"      => $customer_type['type_name_plural'],
						"p"         => "customer_admin_list",
						"args"      => array( 'customer_id' => false, 'customer_type_id' => $customer_type['customer_type_id'] ),
						'icon_name' => $customer_type['menu_icon'] ? $customer_type['menu_icon'] : 'users',
						'order'     => $customer_type['menu_position'],
					);
					if ( in_array( 'customer', $load_modules ) ) {
						$this->links[ 'customer_type_' . $customer_type['customer_type_id'] ]['current'] = ( self::get_current_customer_type_id() == $customer_type['customer_type_id'] );
					}
				}
			}
		}


		if ( $this->can_i( 'edit', 'Customer Settings', 'Config' ) ) {
			$this->links[] = array(
				"name"                => "Customers",
				"p"                   => "customer_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}


	}

	public static $checking_customer_type = false;

	public static function get_current_customer_type_id() {
		if ( isset( $_GET['customer_type_id'] ) ) {
			return (int) $_GET['customer_type_id'];
		}
		if ( ! self::$checking_customer_type && isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
			self::$checking_customer_type = true;
			$temp_customer_type_check     = self::get_customer( $_REQUEST['customer_id'], false, true );
			self::$checking_customer_type = false;
			if ( isset( $temp_customer_type_check['customer_type_id'] ) ) {
				return $temp_customer_type_check['customer_type_id'];
			}
		}

		return 0; // default 0 is customer
	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."customer` c WHERE ";
			//$sql .= " c.`customer_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_customers( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					// what part of this matched?
					if (
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['name'] ) ||
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['last_name'] ) ||
						preg_match( '#' . preg_quote( $search_key, '#' ) . '#i', $result['phone'] )
					) {
						// we matched the customer contact details.
						$match_string = _l( 'Customer Contact: ' );
						$match_string .= _shl( $result['customer_name'], $search_key );
						$match_string .= ' - ';
						$match_string .= _shl( $result['name'], $search_key );
						// hack
						$_REQUEST['customer_id'] = $result['customer_id'];
						$ajax_results []         = '<a href="' . module_user::link_open_contact( $result['user_id'] ) . '">' . $match_string . '</a>';
					} else {
						$match_string    = _l( 'Customer: ' );
						$match_string    .= _shl( $result['customer_name'], $search_key );
						$ajax_results [] = '<a href="' . $this->link_open( $result['customer_id'] ) . '">' . $match_string . '</a>';
						//$ajax_results [] = $this->link_open($result['customer_id'],true);
					}
				}
			}
		}

		return $ajax_results;
	}

	/** static stuff */


	public static function link_generate( $customer_id = false, $options = array(), $link_options = array() ) {

		// link generation can be cached and save a few db calls.
		$link_cache_key     = 'customer_link_' . md5( module_security::get_loggedin_id() . '_' . serialize( func_get_args() ) . '_' . ( isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : false ) . '_' . self::get_current_customer_type_id() );
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );
		if ( $cached_link = module_cache::get( 'customer', $link_cache_key ) ) {
			return $cached_link;
		}

		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'customer_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

		$customer_data = false;
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
			// this is a hack incase the customer is deleted, the invoices are still left behind.
			if ( ${$key} && $link_options ) {
				$customer_data = self::get_customer( ${$key}, true, true );
				if ( ! $customer_data || ! isset( $customer_data[ $key ] ) || $customer_data[ $key ] != ${$key} ) {
					$link = link_generate( $link_options );
					module_cache::put( 'customer', $link_cache_key, $link, $link_cache_timeout );

					return $link;
				}
			}
		}
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		//$options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
		// generate the arguments for this link
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'customer';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'customer_admin_' . ( ( $customer_id || $customer_id == 'new' ) ? 'open' : 'list' );
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['customer_id'] = $customer_id;
		if ( ! isset( $options['arguments']['customer_type_id'] ) ) {
			$options['arguments']['customer_type_id'] = self::get_current_customer_type_id();
		}
		// generate the path (module & page) for this link
		$options['module'] = 'customer';
		if ( $options['page'] == 'customer_settings_types' ) {
			if ( empty( $options['data'] ) ) {
				$data            = self::get_customer_type( $customer_id );
				$options['data'] = $data;
			}
			$options['text'] = isset( $options['data']['type_name_plural'] ) ? $options['data']['type_name_plural'] : '';
			array_unshift( $link_options, $options );
			$options['page'] = 'customer_settings';

			// bubble back onto ourselves for the link.
			return self::link_generate( 0, $options, $link_options );
		}

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $customer_id > 0 ) {
					$data = $customer_data ? $customer_data : self::get_customer( $customer_id, true, true );
				} else {
					$data = array();
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = ! empty( $options['text'] ) ? $options['text'] : ( ( ! isset( $data['customer_name'] ) || ! trim( $data['customer_name'] ) ) ? _l( 'N/A' ) : $data['customer_name'] );
			if ( ! $data || ( ! $customer_id && $options['page'] != 'customer_settings' ) || ( ! $customer_id && $options['page'] == 'customer_settings' && ! $options['arguments']['customer_type_id'] ) || isset( $data['_no_access'] ) ) {
				$link = $options['text'];
				module_cache::put( 'customer', $link_cache_key, $link, $link_cache_timeout );

				return $link;
			}
		}
		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		// $options['skip_permissions'] is used in password reset, otherwise we get # in url

		$page_type = 'Customers';
		if ( $options['arguments']['customer_type_id'] > 0 ) {
			$customer_type = module_customer::get_customer_type( $options['arguments']['customer_type_id'] );
			if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
				$page_type = $customer_type['type_name_plural'];
			}
		}

		if ( ! self::can_i( 'view', $page_type ) && ( ! isset( $options['skip_permissions'] ) || ! $options['skip_permissions'] ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				$link = '#';
			} else {
				$link = isset( $options['text'] ) ? $options['text'] : 'N/A';
			}
			module_cache::put( 'customer', $link_cache_key, $link, $link_cache_timeout );

			return $link;
		}

		if ( isset( $data['customer_status'] ) ) {
			switch ( $data['customer_status'] ) {
				case _CUSTOMER_STATUS_OVERDUE:
					$link_options['class'] = 'customer_overdue error_text';
					break;
				case _CUSTOMER_STATUS_OWING:
					$link_options['class'] = 'customer_owing';
					break;
				case _CUSTOMER_STATUS_PAID:
					$link_options['class'] = 'customer_paid success_text';
					break;
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		if ( $options['page'] == 'customer_settings' ) {

			$link_options[0]['arguments']['customer_id'] = false;
			$bubble_to_module                            = array(
				'module' => 'config',
			);
		}
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
		module_cache::put( 'customer', $link_cache_key, $link, $link_cache_timeout );

		return $link;
	}


	public static function link_open( $customer_id, $full = false, $data = array() ) {
		return self::link_generate( $customer_id, array( 'full' => $full, 'data' => $data ) );
	}


	public static function link_open_customer_type( $customer_type_id, $full = false, $data = array() ) {
		return self::link_generate( $customer_type_id, array(
			'page'      => 'customer_settings_types',
			'full'      => $full,
			'data'      => $data,
			'arguments' => array( 'customer_type_id' => $customer_type_id )
		) );
	}


	public static function get_customers( $search = array(), $return_options = false ) {

		$cache_key_args = func_get_args();
		$cache_key      = self::_customer_cache_key( 'all', $cache_key_args );
		$cache_timeout  = module_config::c( 'cache_objects', 60 );
		if ( $cached_item = module_cache::get( 'customer', $cache_key ) ) {
			return $cached_item;
		}

		// work out what customers this user can access?
		$customer_access = self::get_customer_data_access();

		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
		if ( is_array( $return_options ) && isset( $return_options['columns'] ) ) {
			$sql .= $return_options['columns'];
		} else {
			$sql .= " c.*, c.customer_id AS id, u.user_id, u.name, u.last_name, u.phone ";
			$sql .= " , pu.user_id, pu.name AS primary_user_name, pu.last_name AS primary_user_last_name, pu.phone AS primary_user_phone, pu.email AS primary_user_email";
			$sql .= " , pu.fax AS primary_user_fax, pu.mobile AS primary_user_mobile, pu.language AS primary_user_language";
			$sql .= " , a.line_1, a.line_2, a.suburb, a.state, a.region, a.country, a.post_code ";
			if ( ! count( $search ) ) {
				// we're pulling all available customers into an array.
				//echo "all customers! ";
			}
			if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
				// doing the export, pull in the staff names as well.
				$sql .= ', GROUP_CONCAT( DISTINCT st.name, \' \', st.last_name SEPARATOR \', \' ) AS `customer_staff` ';
			}
		}
		$sql   .= " FROM `" . _DB_PREFIX . "customer` c ";
		$where = "";
		if ( defined( '_SYSTEM_ID' ) ) {
			$sql .= " AND c.system_id = '" . _SYSTEM_ID . "' ";
		}
		$group_order = '';
		$sql         .= ' LEFT JOIN `' . _DB_PREFIX . "user` u ON c.customer_id = u.customer_id"; //c.primary_user_id = u.user_id AND
		$sql         .= ' LEFT JOIN `' . _DB_PREFIX . "user` pu ON c.primary_user_id = pu.user_id";
		if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
			// doing the export, pull in the staff names as well.
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "customer_user_rel` cur ON (c.customer_id = cur.customer_id)";
			$sql .= ' LEFT JOIN `' . _DB_PREFIX . "user` st ON cur.user_id = st.user_id";
		}
		$sql .= ' LEFT JOIN `' . _DB_PREFIX . "address` a ON c.customer_id = a.owner_id AND a.owner_table = 'customer' AND a.address_type = 'physical'";


		if ( ! empty( $search['archived_status'] ) ) {
			switch ( $search['archived_status'] ) {
				case _ARCHIVED_SEARCH_NONARCHIVED:
					$where .= ' AND c.archived = 0 ';
					break;
				case _ARCHIVED_SEARCH_ARCHIVED:
					$where .= ' AND c.archived = 1 ';
					break;
				case _ARCHIVED_SEARCH_BOTH:
					//                    $where .= ' AND c.archived = 0 ';
					break;
			}
		}

		if ( isset( $search['generic'] ) && trim( $search['generic'] ) ) {
			$str = db_escape( trim( $search['generic'] ) );
			// search the customer name, contact name, cusomter phone, contact phone, contact email.
			//$where .= 'AND u.customer_id IS NOT NULL AND ( ';
			$where .= " AND ( ";
			$where .= "c.customer_name LIKE '%$str%' OR ";
			// $where .= "c.phone LIKE '%$str%' OR "; // search company phone number too.
			$where .= "u.name LIKE '%$str%' OR u.email LIKE '%$str%' OR ";
			$where .= "u.last_name LIKE '%$str%' OR ";
			$where .= "u.phone LIKE '%$str%' OR u.fax LIKE '%$str%' ";
			$where .= ') ';
		}
		if ( isset( $search['email'] ) && trim( $search['email'] ) ) {
			$str = db_escape( filter_var( trim( $search['email'] ), FILTER_VALIDATE_EMAIL ) );
			if ( $str ) {
				// search the customer contact emails for a match
				$where .= " AND ( ";
				$where .= "u.email LIKE '%$str%' ";
				$where .= ') ';
			}
		}
		if ( isset( $search['customer_id'] ) && (int) $search['customer_id'] > 0 ) {
			$where .= " AND c.customer_id = " . (int) $search['customer_id'];
		}
		if ( isset( $search['customer_type_id'] ) ) {
			$where .= " AND c.customer_type_id = " . (int) $search['customer_type_id'];
		}
		if ( isset( $search['address'] ) && trim( $search['address'] ) ) {
			$str = db_escape( trim( $search['address'] ) );
			// search all the customer site addresses.
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
			// search all the customer site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "address` a ON (a.owner_id = c.customer_id)"; // swap join around? meh.
			$where .= " AND (a.state_id = '$str' AND a.owner_table = 'customer')";
		}
		if ( isset( $search['customer_billing_type_id'] ) ) {
			$str = (int) $search['customer_billing_type_id'];
			// search all the customer site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "customer_type` ct ON (c.customer_type_id = ct.customer_type_id)";
			$where .= " AND (ct.billing_type = '$str')";
		}
		if ( isset( $search['staff_id'] ) && trim( $search['staff_id'] ) ) {
			$str = (int) $search['staff_id'];
			// search all the customer site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "customer_user_rel` cur ON (c.customer_id = cur.customer_id)";
			$where .= " AND (cur.user_id = '$str')";
		}
		if ( isset( $search['company_id'] ) && trim( $search['company_id'] ) ) {
			$str = (int) $search['company_id'];
			// search all the customer site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` ccr ON (c.customer_id = ccr.customer_id)";
			$where .= " AND (ccr.company_id = '$str')";
		}
		if ( isset( $search['group_id'] ) && trim( $search['group_id'] ) ) {


			$group_owner = self::get_group_owner_slug();

			$str   = (int) $search['group_id'];
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (c.customer_id = gm.owner_id)";
			$where .= " AND (gm.group_id = '$str' AND gm.owner_table = '$group_owner')";
		}
		if ( isset( $search['extra_fields'] ) && is_array( $search['extra_fields'] ) && class_exists( 'module_extra', false ) ) {
			$extra_fields = array();
			foreach ( $search['extra_fields'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$extra_fields[ $key ] = trim( $val );
				}
			}
			if ( count( $extra_fields ) ) {
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "extra` ext ON (ext.owner_id = c.customer_id)"; //AND ext.owner_table = 'customer'
				$where .= " AND (ext.owner_table = 'customer' AND ( ";
				foreach ( $extra_fields as $key => $val ) {
					$val   = db_escape( $val );
					$key   = db_escape( $key );
					$where .= "( ext.`extra` LIKE '%$val%' AND ext.`extra_key` = '$key') OR ";
				}
				$where = rtrim( $where, ' OR' );
				$where .= ' ) )';
			}
		}
		switch ( $customer_access ) {
			case _CUSTOMER_ACCESS_ALL:

				break;
			case _CUSTOMER_ACCESS_ALL_COMPANY:
				if ( class_exists( 'module_company', false ) && module_company::is_enabled() ) {
					$companys = module_company::get_companys_access_restrictions();
					if ( count( $companys ) ) {
						$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON c.customer_id = cc.customer_id";
						$where .= " AND ( ";
						if ( module_config::c( 'customer_show_unassigned_company', 0 ) ) {
							$where .= 'cc.company_id IS NULL OR ';
						}
						$where .= "cc.company_id IN ( ";
						$where .= db_escape( implode( ', ', $companys ) );
						$where .= " ) ) ";
					}
				}
				break;
			case _CUSTOMER_ACCESS_CONTACTS:
				// we only want customers that are directly linked with the currently logged in user contact.
				//$sql .= " LEFT JOIN `"._DB_PREFIX."user` u ON c.customer_id = u.customer_id "; // done above.
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "user_customer_rel` ucr ON c.customer_id = ucr.customer_id ";
				$where .= " AND (";
				$where .= "u.user_id = " . (int) module_security::get_loggedin_id();
				$where .= " OR ( ucr.customer_id = c.customer_id AND ucr.user_id = " . (int) module_security::get_loggedin_id() . " AND ucr.primary = u.user_id )";
				$where .= " OR ( ucr.customer_id = c.customer_id AND ucr.primary = " . (int) module_security::get_loggedin_id() . " AND ucr.user_id = u.user_id )";
				$where .= ' )';
				/*
//                if(isset($_SESSION['_restrict_customer_id']) && (int)$_SESSION['_restrict_customer_id']> 0){
                    // this session variable is set upon login, it holds their customer id.
                    // todo - share a user account between multiple customers!
                    //$where .= " AND c.customer_id IN (SELECT customer_id FROM )";

                if(isset($res['linked_parent_user_id']) && $res['linked_parent_user_id'] == $res['user_id']){
                    // this user is a primary user.
                    $_SESSION['_restrict_customer_id'] = array();
                    $_SESSION['_restrict_customer_id'][$res['customer_id']] = $res['customer_id'];
                    foreach(module_user::get_contact_customer_links($res['user_id']) as $linked){
                        $_SESSION['_restrict_customer_id'][$linked['customer_id']] = $linked['customer_id'];
                    }


                }else{
                    // oldschool permissions.
                    $_SESSION['_restrict_customer_id'] = $res['customer_id'];
                }*/

				/*$valid_customer_ids = module_security::get_customer_restrictions();
                if(count($valid_customer_ids)){
                    $where .= " AND ( ";
                    foreach($valid_customer_ids as $valid_customer_id){
                        $where .= " c.customer_id = '".(int)$valid_customer_id."' OR ";
                    }
                    $where = rtrim($where,'OR ');
                    $where .= " )";
                }*/
				//                }
				break;
			case _CUSTOMER_ACCESS_TASKS:
				// only customers who have linked jobs that I am assigned to.
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON c.customer_id = j.customer_id ";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON j.job_id = t.job_id ";
				$where .= " AND (j.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
			case _CUSTOMER_ACCESS_STAFF:
				// only customers who have linked staff entries
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "customer_user_rel` cur ON c.customer_id = cur.customer_id ";
				$where .= " AND (cur.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
		}


		$group_order = ' GROUP BY c.customer_id ORDER BY c.customer_name ASC'; // stop when multiple company sites have same region
		$sql         = $sql . ( strlen( $where ) > 0 ? ' WHERE 1' . $where : '' ) . $group_order;
		if ( ( ! is_array( $return_options ) && $return_options === true ) || ( is_array( $return_options ) && isset( $return_options['as_resource'] ) && $return_options['as_resource'] ) ) {
			return query( $sql );
		}
		$result = qa( $sql );
		/*if(!function_exists('sort_customers')){
            function sort_customers($a,$b){
                return strnatcasecmp($a['customer_name'],$b['customer_name']);
            }
        }
        uasort($result,'sort_customers');*/

		// we are filtering in the SQL code now..
		//module_security::filter_data_set("customer",$result);

		module_cache::put( 'customer', $cache_key, $result, $cache_timeout );

		return $result;
		//return get_multiple("customer",$search,"customer_id","fuzzy","name");
	}


	private static function _customer_cache_key( $customer_id, $args = array() ) {
		return 'customer_' . $customer_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_customer( $customer_id, $skip_permissions = false, $basic_for_link = false ) {
		$customer_id = (int) $customer_id;
		$customer    = false;
		if ( $customer_id > 0 ) {

			$cache_key_args = func_get_args();
			$cache_key      = self::_customer_cache_key( $customer_id, $cache_key_args );
			$cache_timeout  = module_config::c( 'cache_objects', 60 );
			if ( $cached_item = module_cache::get( 'customer', $cache_key ) ) {
				return $cached_item;
			}

			$customer = get_single( "customer", "customer_id", $customer_id );
			// get their address.
			if ( $customer && isset( $customer['customer_id'] ) && $customer['customer_id'] == $customer_id ) {

				if ( ! $basic_for_link ) {

					$customer['staff_ids'] = array();
					foreach ( get_multiple( 'customer_user_rel', array( 'customer_id' => $customer_id ), 'user_id' ) as $val ) {
						$customer['staff_ids'][] = $val['user_id'];
					}

					$customer['customer_address'] = module_address::get_address( $customer_id, 'customer', 'physical', true );
				}

				switch ( self::get_customer_data_access() ) {
					case _CUSTOMER_ACCESS_ALL:

						break;
					case _CUSTOMER_ACCESS_ALL_COMPANY:
					case _CUSTOMER_ACCESS_CONTACTS:
					case _CUSTOMER_ACCESS_TASKS:
					case _CUSTOMER_ACCESS_STAFF:
						$valid_customer_ids = module_security::get_customer_restrictions();
						$is_valid_customer  = isset( $valid_customer_ids[ $customer['customer_id'] ] );
						if ( ! $is_valid_customer ) {
							if ( $skip_permissions ) {
								$customer['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
							} else {
								$customer = false;
							}
						}
						break;
				}
			}
		}
		if ( ! $customer ) {
			$customer = array(
				'customer_number'  => 0,
				'customer_id'      => 'new',
				'customer_name'    => '',
				'customer_status'  => _CUSTOMER_STATUS_PAID,
				'primary_user_id'  => '',
				'credit'           => '0',
				'customer_address' => array(),
				'staff_ids'        => array(),
				'customer_type_id' => self::get_current_customer_type_id(),
			);
			if ( module_config::c( 'customer_have_id_numbers', 0 ) ) {
				if ( function_exists( 'new_customer_id_number' ) ) {
					$customer['customer_number'] = new_customer_id_number();
				} else {

					// auto increment with a custom prefix or sprintf from config area.
					$config_mask = module_config::c( 'customer_mask_' . $customer['customer_type_id'], 'C-%05s' );
					$config_next = 'customer_next_' . $customer['customer_type_id'];
					if ( $next_value = (int) module_config::c( $config_next, 1 ) ) {
						$customer['customer_number'] = sprintf( $config_mask, $next_value );
						module_config::save_config( $config_next, $next_value + 1 );
					}
				}
			}
		}
		if ( class_exists( 'module_company', false ) && module_company::is_enabled() && ! $basic_for_link ) {
			$customer['company_ids'] = array();
			if ( isset( $customer['customer_id'] ) && (int) $customer['customer_id'] > 0 ) {
				foreach ( module_company::get_companys_by_customer( $customer['customer_id'] ) as $company ) {
					$customer['company_ids'][ $company['company_id'] ] = $company['name'];
				}
			}
		}
		//$customer['customer_industry_id'] = get_multiple('customer_industry_rel',array('customer_id'=>$customer_id),'customer_industry_id');
		//echo $customer_id;print_r($customer);exit;
		if ( isset( $cache_key ) && isset( $cache_timeout ) ) {
			module_cache::put( 'customer', $cache_key, $customer, $cache_timeout );
		}

		return $customer;
	}


	public static function get_customer_type( $customer_type_id ) {
		return get_single( 'customer_type', 'customer_type_id', $customer_type_id );
	}

	public static function get_customer_types( $search = array() ) {
		$array = array_replace(
			array(
				0 => array(
					'customer_type_id' => 0,
					'type_name'        => _l( 'Customer' ),
					'type_name_plural' => _l( 'Customers' ),
					'menu_position'    => module_config::c( '_menu_order_customer', 5.1 ),
					'menu_icon'        => 'users',
				)
			),
			get_multiple( 'customer_type', $search, 'customer_type_id' )
		);

		return $array;
	}

	public static function print_customer_summary( $customer_id, $output = 'html', $fields = array() ) {
		global $plugins;
		$customer_data = $plugins['customer']->get_customer( $customer_id );
		if ( ! $fields ) {
			$fields = array( 'customer_name' );
		}
		$customer_output = '';
		foreach ( $fields as $key ) {
			if ( isset( $customer_data[ $key ] ) && $customer_data[ $key ] ) {
				$customer_output .= $customer_data[ $key ] . ', ';
			}
		}
		$customer_output = rtrim( $customer_output, ', ' );
		if ( $customer_data ) {
			switch ( $output ) {
				case 'text':
					echo $customer_output;
					break;
				case 'html':
					?>
					<span class="customer">
						<a href="<?php echo $plugins['customer']->link_open( $customer_id ); ?>">
							<?php echo $customer_output; ?>
						</a>
					</span>
					<?php
					break;
				case 'full':
					include( 'pages/customer_summary.php' );
					break;
			}
		}
	}


	/** methods  */


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && ! empty( $_REQUEST['customer_id'] ) && module_customer::can_i( 'delete', 'Customers' ) ) {
			if ( module_form::check_secure_key() ) {
				$data = self::get_customer( $_REQUEST['customer_id'] );
				if ( $data['customer_id'] && $data['customer_id'] = $_REQUEST['customer_id'] ) {
					if ( module_form::confirm_delete(
						'customer_id',
						_l( "Really delete customer: %s", $data['customer_name'] ),
						self::link_open( $_REQUEST['customer_id'] ),
						array(
							'options' => array(
								array(
									'label'   => _l( 'Also delete all Customer %s, Jobs, Invoices, Tickets and Files', module_config::c( 'project_name_plural' ) ),
									'name'    => 'delete_others',
									'type'    => 'checkbox',
									'value'   => 1,
									'checked' => true,
								)
							),
						)
					)
					) {
						$this->delete_customer( $_REQUEST['customer_id'], isset( $_REQUEST['delete_others'] ) && $_REQUEST['delete_others'] );

						set_message( "Customer deleted successfully" );
						redirect_browser( self::link_open( false ) );
					}
				}
			}
		} else if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && ! empty( $_REQUEST['customer_type_id'] ) ) {

			if ( module_form::check_secure_key() ) {

				$data = self::get_customer_type( $_REQUEST['customer_type_id'] );
				if ( $data['customer_type_id'] && $data['customer_type_id'] = $_REQUEST['customer_type_id'] ) {
					if ( module_form::confirm_delete(
						'customer_type_id',
						_l( "Really delete customer type: %s", $data['type_name'] ),
						self::link_open_customer_type( $_REQUEST['customer_type_id'] )
					)
					) {
						delete_from_db( 'customer_type', 'customer_type_id', $data['customer_type_id'] );
						$sql = "UPDATE `" . _DB_PREFIX . "customer` SET `customer_type_id` = 0 WHERE `customer_type_id` = " . (int) $data['customer_type_id'];
						query( $sql );

						set_message( "Customer type deleted successfully" );
						redirect_browser( self::link_open_customer_type( false ) );
					}
				}
			}
		} else if ( "ajax_customer_list" == $_REQUEST['_process'] ) {

			header( "Content-type: text/javascript" );
			if ( module_form::check_secure_key() && module_customer::can_i( 'view', 'Customers' ) ) {
				$search  = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				$res     = module_customer::get_customers( $search, array( 'columns' => 'c.customer_id, c.customer_name' ) );
				$options = array();
				foreach ( $res as $row ) {
					$options[ $row['customer_id'] ] = $row['customer_name'];
				}
				echo json_encode( $options );
			}
			exit;

		} else if ( "ajax_contact_list" == $_REQUEST['_process'] ) {

			header( "Content-type: text/javascript" );
			if ( module_form::check_secure_key() && module_customer::can_i( 'view', 'Customers' ) ) {
				$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
				$res         = module_user::get_contacts( array( 'customer_id' => $customer_id ) );
				$options     = array();
				foreach ( $res as $row ) {
					$options[ $row['user_id'] ] = $row['name'] . ' ' . $row['last_name'];
				}
				echo json_encode( $options );
			}
			exit;

		} else if ( "save_customer" == $_REQUEST['_process'] ) {
			$customer_id = $this->save_customer( $_REQUEST['customer_id'], $_POST );
			hook_handle_callback( 'customer_save', $customer_id );
			if ( isset( $_REQUEST['butt_send_statement_email'] ) ) {
				redirect_browser( self::link_open( $customer_id ) . '&email=statement' );
			} else if ( isset( $_REQUEST['butt_send_welcome_email'] ) ) {
				redirect_browser( self::link_open( $customer_id ) . '&email=welcome' );
			} else if ( ! empty( $_REQUEST['butt_archive'] ) ) {
				$UCMCustomer = new UCMCustomer( $customer_id );
				if ( $UCMCustomer->is_archived() ) {
					$UCMCustomer->unarchive();
					set_message( "Customer unarchived successfully" );
				} else {
					$UCMCustomer->archive();
					set_message( "Customer archived successfully" );
				}
				redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : self::link_open( $customer_id ) );
			} else {
				set_message( "Customer saved successfully" );
				redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : self::link_open( $customer_id ) );
			}
		} else if ( "save_customer_type" == $_REQUEST['_process'] ) {
			$customer_type_id = $this->save_customer_type( $_REQUEST['customer_type_id'], $_POST );
			hook_handle_callback( 'customer_save_type', $customer_type_id );
			set_message( "Customer saved successfully" );
			redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : self::link_open_customer_type( $customer_type_id ) );
		}
	}

	public function load( $customer_id ) {
		$data = self::get_customer( $customer_id );
		foreach ( $data as $key => $val ) {
			$this->$key = $val;
		}

		return $data;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_customer::can_i( 'view', 'Customers' ) ) {
			$search_array = array(
				'generic' => $search_string,
			);
			if ( isset( $_REQUEST['customer_type_id'] ) ) {
				$search_array['customer_type_id'] = (int) $_REQUEST['customer_type_id'];
			}
			if ( isset( $_REQUEST['customer_billing_type_id'] ) ) {
				$search_array['customer_billing_type_id'] = (int) $_REQUEST['customer_billing_type_id'];
			}
			$res = module_customer::get_customers( $search_array, array( 'columns' => 'c.customer_id, c.customer_name' ) );
			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['customer_id'],
					'value' => $row['customer_name']
				);
			}
		}

		return $result;
	}

	public static function c( $config_key, $config_default, $customer_id ) {
		if ( (int) $customer_id > 0 ) {
			$config = get_single( 'customer_config', array( 'customer_id', 'config_key' ), array(
				$customer_id,
				$config_key
			) );
			if ( $config && strlen( $config['config_val'] ) ) {
				return $config['config_val'];
			}
		}

		return module_config::c( $config_key, $config_default );
	}

	public static function save_config( $config_key, $config_value, $customer_id ) {
		if ( (int) $customer_id > 0 ) {
			$config = get_single( 'customer_config', array( 'customer_id', 'config_key' ), array(
				$customer_id,
				$config_key
			) );
			if ( $config && strlen( $config['config_val'] ) ) {
				// overwrite this local customer value.
				$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_config` SET ";
				$sql .= "`customer_id` = " . (int) $customer_id;
				$sql .= ", `config_key` = '" . module_db::escape( $config_key ) . "'";
				$sql .= ", `config_val` = '" . module_db::escape( $config_value ) . "'";
				query( $sql );

				return;
			}
		}
		// otherwise save default as normal.
		module_config::save_config( $config_key, $config_value );
	}

	public static function run_cron( $debug = false ) {
		// only run this cron max once every hour
		// so if the cron job runs every 5 minutes only execute this every 20
		$refresh_interval      = module_config::c( 'customer_status_cron_refresh_time', 60 );
		$last_customer_refresh = module_config::c( 'customer_status_cron_refresh_last', 0 );
		if ( $last_customer_refresh <= 0 || ( $last_customer_refresh + ( $refresh_interval * 60 ) ) <= time() ) {
			module_config::save_config( 'customer_status_cron_refresh_last', time() );
			// find any customers with unpaid invoices
			if ( class_exists( 'module_invoice', false ) ) {
				$sql       = "SELECT * FROM `" . _DB_PREFIX . "customer` c ";
				$sql       .= " RIGHT JOIN `" . _DB_PREFIX . "invoice` i ON c.customer_id = i.customer_id";
				$sql       .= " WHERE ";
				$sql       .= " c.customer_status = 0 ";
				$sql       .= " OR ( i.date_paid = '0000-00-00' AND i.date_due <= '" . date( 'Y-m-d' ) . "' AND c.customer_status != " . _CUSTOMER_STATUS_OVERDUE . " )";
				$sql       .= " OR ( i.date_paid != '0000-00-00' AND ( c.customer_status = " . _CUSTOMER_STATUS_OWING . " OR c.customer_status = " . _CUSTOMER_STATUS_OVERDUE . " ) )";
				$sql       .= " GROUP BY c.customer_id";
				$customers = qa( $sql );
				//print_r($customers);
				foreach ( $customers as $c ) {
					self::update_customer_status( $c['customer_id'], $debug );
				}
			}
		}
	}

	// run this update in a cron job from time to time:
	public static function update_customer_status( $customer_id, $debug = false ) {
		// find out if this customer has any invoices owing, paid or overdue
		if ( class_exists( 'module_invoice', false ) ) {
			if ( $debug ) {
				echo "Updating customer status of $customer_id ... ";
			}
			module_cache::clear( 'invoice' );
			$invoices       = module_invoice::get_invoices( array( 'customer_id' => $customer_id ) );
			$total_due      = 0;
			$total_paid     = 0;
			$total_overdue  = 0;
			$total_invoices = 0;
			if ( count( $invoices ) ) {
				foreach ( $invoices as $invoice ) {
					if ( ! $invoice['credit_note_id'] && $invoice['date_cancel'] == '0000-00-00' ) {
						$total_invoices ++;
						//$invoice = module_invoice::get_invoice($invoice['invoice_id']);
						$total_due  += $invoice['c_total_amount_due'];
						$total_paid += ( $invoice['c_total_amount'] - $invoice['c_total_amount_due'] );
						if ( ( $invoice['date_due'] && $invoice['date_due'] != '0000-00-00' ) && ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < time() ) {
							$total_overdue += $invoice['c_total_amount_due'];
						}
					}
				}
			}
			if ( $debug ) {
				echo "$total_invoices. invoices, $total_overdue overdue, $total_due due ";
			}
			if ( $total_overdue > 0 ) {
				update_insert( 'customer_id', $customer_id, 'customer', array( 'customer_status' => _CUSTOMER_STATUS_OVERDUE ) );
			} else if ( $total_due > 0 ) {
				update_insert( 'customer_id', $customer_id, 'customer', array( 'customer_status' => _CUSTOMER_STATUS_OWING ) );
			} else if ( $total_invoices > 0 ) {
				update_insert( 'customer_id', $customer_id, 'customer', array( 'customer_status' => _CUSTOMER_STATUS_PAID ) );
			} else {
				update_insert( 'customer_id', $customer_id, 'customer', array( 'customer_status' => 0 ) );
			}
			module_cache::clear( 'customer' );
			if ( $debug ) {
				echo "... done <br>\n ";
			}
		}
	}

	public function save_customer( $customer_id, $data ) {

		$customer_id   = (int) $customer_id;
		$temp_customer = false;
		if ( $customer_id > 0 ) {
			// check permissions
			$temp_customer = $this->get_customer( $customer_id );
			if ( ! $temp_customer || $temp_customer['customer_id'] != $customer_id ) {
				$temp_customer = false;
				$customer_id   = false;
			}
		}

		if ( _DEMO_MODE && $customer_id == 1 ) {
			set_error( 'Sorry this is a Demo Customer. It cannot be changed.' );
			redirect_browser( self::link_open( $customer_id ) );
		}

		if ( isset( $data['default_tax_system'] ) && $data['default_tax_system'] ) {
			$data['default_tax']      = - 1;
			$data['default_tax_name'] = '';
		}

		if ( isset( $data['primary_user_id'] ) ) {
			unset( $data['primary_user_id'] );
		} // only allow this to be set through the method.

		// Migrating to new class based update method.
		//$customer_id = update_insert( "customer_id", $customer_id, "customer", $data );
		$UCMCustomer = new UCMCustomer( $customer_id );
		$customer_id = $UCMCustomer->save_data( $data );


		if ( isset( $data['single_staff_id'] ) && (int) $data['single_staff_id'] > 0 && module_customer::get_customer_data_access() == _CUSTOMER_ACCESS_STAFF && $data['single_staff_id'] == module_security::get_loggedin_id() ) {
			$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_user_rel` SET ";
			$sql .= " `user_id` = " . (int) $data['single_staff_id'];
			$sql .= ", `customer_id` = " . (int) $customer_id;
			query( $sql );
		} else if ( isset( $data['staff_ids'] ) && is_array( $data['staff_ids'] ) && module_customer::can_i( 'edit', 'Customer Staff' ) ) {
			$existing_staff = array();
			if ( $temp_customer ) {
				$existing_staff = $temp_customer['staff_ids'];
			}
			foreach ( $data['staff_ids'] as $staff_id ) {
				$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_user_rel` SET ";
				$sql .= " `user_id` = " . (int) $staff_id;
				$sql .= ", `customer_id` = " . (int) $customer_id;
				$key = array_search( $staff_id, $existing_staff );
				if ( $key !== false ) {
					unset( $existing_staff[ $key ] );
				}
				query( $sql );
			}
			foreach ( $existing_staff as $staff_id ) {
				delete_from_db( 'customer_user_rel', array( 'user_id', 'customer_id' ), array( $staff_id, $customer_id ) );
			}
		}
		if ( isset( $data['customer_config'] ) && is_array( $data['customer_config'] ) ) {
			delete_from_db( 'customer_config', array( 'customer_id' ), array( $customer_id ) );
			foreach ( $data['customer_config'] as $config_key => $config_val ) {
				if ( strlen( $config_val ) ) {
					$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_config` SET ";
					$sql .= "`customer_id` = " . (int) $customer_id;
					$sql .= ", `config_key` = '" . module_db::escape( $config_key ) . "'";
					$sql .= ", `config_val` = '" . module_db::escape( $config_val ) . "'";
					query( $sql );
				}
			}
			if ( ! empty( $data['default_customer_config'] ) ) {
				foreach ( $data['default_customer_config'] as $config_key => $config_val ) {
					if ( empty( $data['customer_config'][ $config_key ] ) ) {
						// user didn't check this box on save. write an empty value.
						$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_config` SET ";
						$sql .= "`customer_id` = " . (int) $customer_id;
						$sql .= ", `config_key` = '" . module_db::escape( $config_key ) . "'";
						$sql .= ", `config_val` = '0'";
						query( $sql );
					}
				}
			}

		}
		if ( isset( $_REQUEST['user_id'] ) ) {
			$user_id = (int) $_REQUEST['user_id'];
			if ( $user_id > 0 ) {
				// check permissions
				$temp_user = module_user::get_user( $user_id );
				if ( ! $temp_user || $temp_user['user_id'] != $user_id ) {
					$user_id = false;
				}
			}
			// assign specified user_id to this customer.
			// could this be a problem?
			// maybe?
			// todo: think about security precautions here, maybe only allow admins to set primary contacts.
			$data['customer_id'] = $customer_id;
			if ( ! $user_id ) {
				// hack to set the default role of a contact (if one is set in settings).
				if ( ! isset( $data['last_name'] ) && isset( $data['name'] ) && strpos( $data['name'], ' ' ) > 0 ) {
					// todo - save from customer import
					$bits              = explode( ' ', $data['name'] );
					$data['last_name'] = array_pop( $bits );
					$data['name']      = implode( ' ', $bits );
				}
				global $plugins;
				$user_id = $plugins['user']->create_user( $data, 'contact' );
				//$user_id = update_insert("user_id",false,"user",$data);
				//module_cache::clear('user');
				$role_id = module_config::c( 'contact_default_role', 0 );
				if ( $role_id > 0 ) {
					module_user::add_user_to_role( $user_id, $role_id );
				}
				$this->set_primary_user_id( $customer_id, $user_id );
			} else {
				// make sure this user is part of this customer.
				// wait! addition, we want to be able to move an existing customer contact to this new customer.
				$saved_user_id = false;
				if ( isset( $_REQUEST['move_user_id'] ) && (int) $_REQUEST['move_user_id'] && module_customer::can_i( 'create', 'Customers' ) ) {
					$old_user = module_user::get_user( (int) $_REQUEST['move_user_id'] );
					if ( $old_user && $old_user['user_id'] == (int) $_REQUEST['move_user_id'] ) {
						$saved_user_id = $user_id = update_insert( "user_id", $user_id, "user", $data );
						module_cache::clear( 'user' );
						hook_handle_callback( 'customer_contact_moved', $user_id, $old_user['customer_id'], $customer_id );
						$this->set_primary_user_id( $customer_id, $user_id );
						module_cache::clear( 'user' );
					}
				} else {
					// save normally, only those linked to this account:
					$users = module_user::get_contacts( array( 'customer_id' => $customer_id ) );
					foreach ( $users as $user ) {
						if ( $user['user_id'] == $user_id ) {
							$saved_user_id = $user_id = update_insert( "user_id", $user_id, "user", $data );
							$this->set_primary_user_id( $customer_id, $user_id );
							module_cache::clear( 'user' );
							break;
						}
					}
				}
				if ( ! $saved_user_id ) {
					$this->set_primary_user_id( $customer_id, 0 );
					module_cache::clear( 'user' );
				}
			}
			// todo: move this functionality back into the user class.
			// maybe with a static save_user method ?
			if ( $user_id > 0 && class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				module_extra::save_extras( 'user', 'user_id', $user_id );
			}
		}

		handle_hook( "address_block_save", $this, "physical", "customer", "customer_id", $customer_id );
		//handle_hook("address_block_save",$this,"postal","customer","customer_id",$customer_id);
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::save_extras( 'customer', 'customer_id', $customer_id );
		}

		// save the company information if it's available
		if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
			if ( isset( $_REQUEST['available_customer_company'] ) && is_array( $_REQUEST['available_customer_company'] ) ) {
				$selected_companies = isset( $_POST['customer_company'] ) && is_array( $_POST['customer_company'] ) ? $_POST['customer_company'] : array();
				$company_access     = module_company::get_company_data_access();
				if ( $company_access == _COMPANY_ACCESS_ALL && ! count( $selected_companies ) ) {
					// user is unassignging this customer from all companies we have access to, dont let them do this?

				}
				foreach ( $_REQUEST['available_customer_company'] as $company_id => $tf ) {
					if ( ! isset( $selected_companies[ $company_id ] ) || ! $selected_companies[ $company_id ] ) {
						// remove customer from this company
						module_company::delete_customer( $company_id, $customer_id );
					} else {
						// add customer to this company (if they are not already existing)
						module_company::add_customer_to_company( $company_id, $customer_id );
					}
				}
			}
		}

		self::update_customer_status( $customer_id );
		module_cache::clear( 'customer' );

		return $customer_id;
	}

	public function save_customer_type( $customer_type_id, $data ) {

		if ( _DEMO_MODE && $customer_type_id == 1 ) {
			set_error( 'Sorry this is a Demo Lead Type. It cannot be changed.' );
			redirect_browser( self::link_open_customer_type( $customer_type_id ) );
		}

		$customer_type_id = update_insert( "customer_type_id", $customer_type_id, "customer_type", $data );

		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::save_extras( 'customer_type', 'customer_type_id', $customer_type_id );
		}

		module_cache::clear( 'customer' );

		return $customer_type_id;
	}

	public static function set_primary_user_id( $customer_id, $user_id ) {
		if ( _DEMO_MODE && $customer_id == 1 ) {
			set_error( 'Sorry this is a Demo Customer. It cannot be changed.' );
			redirect_browser( self::link_open( $customer_id ) );
		}
		update_insert( 'customer_id', $customer_id, 'customer', array( 'primary_user_id' => $user_id ) );
		module_cache::clear( 'customer' );
	}

	public function delete_customer( $customer_id, $remove_linked_data = true ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			if ( _DEMO_MODE && $customer_id == 1 ) {
				set_error( 'Sorry this is a Demo Customer. It cannot be changed.' );
				redirect_browser( self::link_open( $customer_id ) );
			}
			$customer = self::get_customer( $customer_id );
			if ( $customer && $customer['customer_id'] == $customer_id ) {


				$group_owner = self::get_group_owner_slug();
				// todo: Delete emails (wack these in this customer_deleted hook)
				hook_handle_callback( 'customer_deleted', $customer_id, $remove_linked_data );

				if ( class_exists( 'module_group', false ) ) {
					// remove the customer from his groups
					module_group::delete_member( $customer_id, $group_owner );
				}
				if ( class_exists( 'module_extra', false ) ) {
					module_extra::delete_extras( 'customer', 'customer_id', $customer_id );
				}
				// remove the contacts from this customer
				foreach ( module_user::get_contacts( array( 'customer_id' => $customer_id ) ) as $val ) {
					if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
						module_user::delete_user( $val['user_id'] );
					}
				}
				// remove staff
				delete_from_db( 'customer_user_rel', 'customer_id', $customer_id );
				if ( class_exists( 'module_note', false ) ) {
					module_note::note_delete( "customer", 'customer_id', $customer_id );
				}
				handle_hook( "address_delete", $this, 'all', "customer", 'customer_id', $customer_id );

				// todo, check the 'delete' permission on each one of these 'delete' method calls
				// do that better when we remove each of these and put them into the customer delete hook
				if ( $remove_linked_data ) {

					if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
						foreach ( module_website::get_websites( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								module_website::delete_website( $val['website_id'] );
							}
						}
					}
					if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
						foreach ( module_job::get_jobs( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								module_job::delete_job( $val['job_id'] );
							}
						}
					}
					if ( class_exists( 'module_invoice', false ) && module_invoice::is_plugin_enabled() ) {
						foreach ( module_invoice::get_invoices( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								module_invoice::delete_invoice( $val['invoice_id'] );
							}
						}
					}
					if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() ) {
						foreach ( module_quote::get_quotes( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								module_quote::delete_quote( $val['quote_id'] );
							}
						}
					}
					//handle_hook("file_delete",$this,"customer",'customer_id',$customer_id);
				} else {
					// instead of deleting these records we just update them to customer_id = 0
					if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
						foreach ( module_website::get_websites( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								update_insert( 'website_id', $val['website_id'], 'website', array( 'customer_id' => 0 ) );
							}
						}
					}
					if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
						foreach ( module_job::get_jobs( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								update_insert( 'job_id', $val['job_id'], 'job', array( 'customer_id' => 0 ) );
							}
						}
					}
					if ( class_exists( 'module_invoice', false ) && module_invoice::is_plugin_enabled() ) {
						foreach ( module_invoice::get_invoices( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								update_insert( 'invoice_id', $val['invoice_id'], 'invoice', array( 'customer_id' => 0 ) );
							}
						}
					}
					if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() ) {
						foreach ( module_quote::get_quotes( array( 'customer_id' => $customer_id ) ) as $val ) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								update_insert( 'quote_id', $val['quote_id'], 'quote', array( 'customer_id' => 0 ) );
							}
						}
					}
					if ( class_exists( 'module_file', false ) && module_file::is_plugin_enabled() ) {
						foreach (
							module_file::get_files( array(
								'owner_id'    => $customer_id,
								'owner_table' => 'customer'
							) ) as $val
						) {
							if ( $val['customer_id'] && $val['customer_id'] == $customer_id ) {
								update_insert( 'file_id', $val['file_id'], 'file', array( 'owner_id' => 0, 'owner_table' => '' ) );
							}
						}
					}
				}
				// finally delete the main customer record
				// (this is so the above code works with its sql joins)
				$sql = "DELETE FROM " . _DB_PREFIX . "customer WHERE customer_id = '" . $customer_id . "' LIMIT 1";
				query( $sql );
			}
		}
	}

	public static function handle_import( $data, $add_to_group, $options = array() ) {

		// woo! we're doing an import.

		// our first loop we go through and find matching customers by their "customer_name" (required field)
		// and then we assign that customer_id to the import data.
		// our second loop through if there is a customer_id we overwrite that existing customer with the import data (ignoring blanks).
		// if there is no customer id we create a new customer record :) awesome.

		foreach ( $data as $rowid => $row ) {
			if ( ! isset( $row['customer_name'] ) || ! trim( $row['customer_name'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['customer_id'] ) || ! $row['customer_id'] ) {
				$data[ $rowid ]['customer_id'] = 0;
			}

		}


		$staff_members     = module_user::get_staff_members();
		$staff_member_rel  = array();
		$staff_member_rel2 = array();
		foreach ( $staff_members as $staff_member ) {
			$staff_member_rel[ $staff_member['name'] ]                                     = $staff_member['user_id'];
			$staff_member_rel2[ $staff_member['name'] . ' ' . $staff_member['last_name'] ] = $staff_member['user_id'];
		}

		// now save the data.
		foreach ( $data as $rowid => $row ) {
			//module_cache::clear_cache();
			$customer_id = isset( $row['customer_id'] ) ? (int) $row['customer_id'] : 0;
			// check if this ID exists.
			if ( $customer_id > 0 ) {
				$customer = self::get_customer( $customer_id );
				if ( ! $customer || ! isset( $customer['customer_id'] ) || $customer['customer_id'] != $customer_id ) {
					$customer_id = 0;
				}
			}
			if ( ! $customer_id ) {
				// search for a custoemr based on name.
				$customer = get_single( 'customer', 'customer_name', $row['customer_name'] );
				//print_r($row); print_r($customer);echo '<hr>';
				if ( $customer && $customer['customer_id'] > 0 ) {
					$customer_id = $customer['customer_id'];
				}
			}
			if ( isset( $options['customer_type_id'] ) ) {
				$row['customer_type_id'] = (int) $options['customer_type_id'];
			}
			$customer_id = update_insert( "customer_id", $customer_id, "customer", $row );
			// add staff to customer.

			if ( isset( $row['customer_staff'] ) ) {
				$staff_split = explode( ',', $row['customer_staff'] );
				foreach ( $staff_split as $staff_name ) {
					$staff_name2 = trim( $staff_name );
					$staff_id    = false;
					if ( isset( $staff_member_rel[ $staff_name ] ) ) {
						$staff_id = $staff_member_rel[ $staff_name ];
					} else if ( isset( $staff_member_rel[ $staff_name2 ] ) ) {
						$staff_id = $staff_member_rel[ $staff_name2 ];
					} else if ( isset( $staff_member_rel2[ $staff_name ] ) ) {
						$staff_id = $staff_member_rel2[ $staff_name ];
					} else if ( isset( $staff_member_rel2[ $staff_name2 ] ) ) {
						$staff_id = $staff_member_rel2[ $staff_name2 ];
					}
					if ( $staff_id ) {
						$sql = "REPLACE INTO `" . _DB_PREFIX . "customer_user_rel` SET ";
						$sql .= " `user_id` = " . (int) $staff_id;
						$sql .= ", `customer_id` = " . (int) $customer_id;
						query( $sql );
					}
				}
			}
			// ad notes if possible
			if ( isset( $row['notes'] ) && strlen( trim( $row['notes'] ) ) ) {
				if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
					module_note::save_note( array(
						'owner_table' => 'customer',
						'owner_id'    => $customer_id,
						'note'        => trim( $row['notes'] ),
						'note_time'   => time(),
					) );
				}
			}
			// see if we're updating an old contact, or adding a new primary contact.
			// match on name since that's a required field.
			$users      = module_user::get_contacts( array( 'customer_id' => $customer_id ) );
			$user_match = 0;
			foreach ( $users as $user ) {
				if ( $user['name'] == $row['primary_user_name'] ) {
					$user_match = $user['user_id'];
					break;
				}
			}
			$user_update = array(
				'customer_id' => $customer_id,
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
			self::set_primary_user_id( $customer_id, $user_match );

			// do a hack to save address.
			$existing_address = module_address::get_address( $customer_id, 'customer', 'physical' );
			$address_id       = ( $existing_address && isset( $existing_address['address_id'] ) ) ? (int) $existing_address['address_id'] : 'new';
			$address          = array_merge( $row, array(
				'owner_id'     => $customer_id,
				'owner_table'  => 'customer',
				'address_type' => 'physical',
			) );
			module_address::save_address( $address_id, $address );


			$group_owner = self::get_group_owner_slug();

			foreach ( $add_to_group as $group_id => $tf ) {
				module_group::add_to_group( $group_id, $customer_id, $group_owner );
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
				// we handle extra importing for customer extra fields and contact extra fields.
				// sort out which are which.
				// but they have to be unique names. for now. oh well that'll do.

				$sql             = "SELECT `extra_key` as `id` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = 'customer' AND `extra_key` != '' GROUP BY `extra_key` ORDER BY `extra_key`";
				$customer_fields = qa( $sql );
				$sql             = "SELECT `extra_key` as `id` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = 'user' AND `extra_key` != '' GROUP BY `extra_key` ORDER BY `extra_key`";
				$contact_fields  = qa( $sql );
				foreach ( $extra as $extra_key => $extra_val ) {
					// does this one exist?
					if ( isset( $customer_fields[ $extra_key ] ) ) {
						// this is a customer extra field.
						$existing_extra = module_extra::get_extras( array(
							'owner_table' => 'customer',
							'owner_id'    => $customer_id,
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
							'owner_table' => 'customer',
							'owner_id'    => $customer_id,
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

	public static function add_credit( $customer_id, $credit, $note = false ) {
		$customer_data           = self::get_customer( $customer_id );
		$customer_data['credit'] += $credit;
		update_insert( 'customer_id', $customer_id, 'customer', array( 'credit' => $customer_data['credit'] ) );
		if ( $note ) {
			self::add_history( $customer_id, $note );
		}
	}

	public static function remove_credit( $customer_id, $credit, $note = false ) {
		$customer_data           = self::get_customer( $customer_id );
		$customer_data['credit'] -= $credit;
		update_insert( 'customer_id', $customer_id, 'customer', array( 'credit' => $customer_data['credit'] ) );
		module_cache::clear( 'customer' );
	}


	public static function add_history( $customer_id, $message ) {
		if ( class_exists( 'module_note', false ) ) {
			module_note::save_note( array(
				'owner_table' => 'customer',
				'owner_id'    => $customer_id,
				'note'        => $message,
				'rel_data'    => self::link_open( $customer_id ),
				'note_time'   => time(),
			) );
		}
	}

	public static function hook_filter_var_customer_list( $call, $customer_attributes ) {
		if ( ! is_array( $customer_attributes ) ) {
			$customer_attributes = array();
		}
		foreach ( module_customer::get_customers( array(), array( 'columns' => 'c.customer_id, c.customer_name' ) ) as $customer ) {
			$customer_attributes[ $customer['customer_id'] ] = $customer['customer_name'];
		}

		return $customer_attributes;
	}


	public static function get_customer_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Customer Data Access', array(
				_CUSTOMER_ACCESS_ALL,
				_CUSTOMER_ACCESS_ALL_COMPANY,
				_CUSTOMER_ACCESS_CONTACTS,
				_CUSTOMER_ACCESS_TASKS,
				_CUSTOMER_ACCESS_STAFF,
			) );
		} else {
			return true;
		}
	}

	public static function link_public_signup() {
		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.customer/h.public_signup' );
	}

	public static function link_public_signup_form() {
		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.customer/h.public_signup_form' );
	}

	public static function get_customer_signup_form_html() {
		ob_start();
		include( module_theme::include_ucm( 'includes/plugin_customer/pages/customer_signup_form.php' ) );

		return ob_get_clean();
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'public_signup_form':
				$signup_form             = module_template::get_template_by_key( 'customer_signup_form_wrapper' );
				$signup_form->page_title = $signup_form->description;
				$signup_form->assign_values( array( 'signup_form' => self::get_customer_signup_form_html() ) );
				echo $signup_form->render( 'pretty_html' );
				exit;

			case 'public':
				$customer_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash        = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $customer_id && $hash ) {
					$correct_hash = $this->link_public( $customer_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$customer_data = $this->get_customer( $customer_id );

						if ( $customer_data ) {

							// load our template for the public display.

							// load our code in from that guy.

							include( module_theme::include_ucm( 'includes/plugin_customer/portal/index.php' ) );
							/*

                            module_template::init_template('customer_portal','<h1>Customer Portal</h1>
{OVERVIEW_HTML}
','Used when displaying the portal to a customer.','code');
					        // correct!
					        // load up the receipt template.
					        $template = module_template::get_template_by_key('customer_portal');
					        // generate the html for the task output
					        ob_start();
					        include(module_theme::include_ucm('includes/plugin_customer/pages/customer_public.php'));
					        $customer_data['overview_html'] = ob_get_clean();
					        $template->assign_values($customer_data);
					        $template->assign_values(self::get_replace_fields($customer_id));
					        $template->page_title = $customer_data['customer_name'];
					        echo $template->render();*/

						}
					}
				}
				exit;
				break;
			case 'public_signup':

				// sign out if testing.
				if ( module_security::is_logged_in() ) {
					set_message( 'Logged out due to signup' );
					module_security::logout();
				}

				$result = array(
					'messages' => array(),
				);

				function customer_signup_complete( $result ) {
					if ( isset( $_REQUEST['via_ajax'] ) ) {
						echo json_encode( $result );
					} else {
						echo implode( '<br/>', $result['messages'] );
					}
					exit;
				}

				if ( ! module_config::c( 'customer_signup_allowed', 0 ) ) {
					$result['error']      = 1;
					$result['messages'][] = 'Customer signup disabled';
					customer_signup_complete( $result );
				}

				//recaptcha on signup form.
				if ( module_config::c( 'captcha_on_signup_form', 0 ) ) {
					if ( ! module_captcha::check_captcha_form() ) {
						$result['error']      = 1;
						$result['messages'][] = 'Captcha fail, please go back and enter correct captcha code.';
						customer_signup_complete( $result );
					}
				}


				$customer       = isset( $_POST['customer'] ) && is_array( $_POST['customer'] ) ? $_POST['customer'] : array();
				$contact        = isset( $_POST['contact'] ) && is_array( $_POST['contact'] ) ? $_POST['contact'] : array();
				$contact_extra  = isset( $contact['extra'] ) && is_array( $contact['extra'] ) ? $contact['extra'] : array();
				$contact_group  = isset( $contact['group_ids'] ) && is_array( $contact['group_ids'] ) ? $contact['group_ids'] : array();
				$customer_extra = isset( $customer['extra'] ) ? $customer['extra'] : array();
				$customer_group = isset( $customer['group_ids'] ) && is_array( $customer['group_ids'] ) ? $customer['group_ids'] : array();
				$address        = isset( $_POST['address'] ) ? $_POST['address'] : array();
				$website        = isset( $_POST['website'] ) ? $_POST['website'] : array();
				$website_extra  = isset( $website['extra'] ) ? $website['extra'] : array();
				$website_group  = isset( $website['group_ids'] ) && is_array( $website['group_ids'] ) ? $website['group_ids'] : array();
				$job            = isset( $_POST['job'] ) ? $_POST['job'] : array();
				$job_extra      = isset( $job['extra'] ) ? $job['extra'] : array();
				$subscription   = isset( $_POST['subscription'] ) ? $_POST['subscription'] : array();

				// sanatise possibly problematic fields:
				// customer:
				if ( isset( $customer['type'] ) ) {
					$customer['customer_type_id'] = $customer['type'];
					unset( $customer['type'] );
				}
				$allowed = array(
					'name',
					'last_name',
					'customer_name',
					'email',
					'phone',
					'mobile',
					'extra',
					'customer_type_id'
				);
				foreach ( $customer as $key => $val ) {
					if ( ! in_array( $key, $allowed ) ) {
						unset( $customer[ $key ] );
					}
				}
				$customer_types = self::get_customer_types();
				if ( isset( $customer['customer_type_id'] ) ) {
					$found = false;
					foreach ( $customer_types as $customer_type ) {
						if ( $customer_type['customer_type_id'] == $customer['customer_type_id'] ) {
							$found = true;
							break;
						}
					}
					if ( ! $found ) {
						unset( $customer['customer_type_id'] );
					}
				}
				// added multiple contact support in the form of arrays.
				$contact_fields = array( 'name', 'last_name', 'email', 'phone' );
				if ( module_config::c( 'customer_signup_password', 0 ) ) {
					$contact_fields[] = 'password';
				}
				foreach ( $contact_fields as $multi_value ) {
					if ( isset( $contact[ $multi_value ] ) ) {
						if ( ! is_array( $contact[ $multi_value ] ) ) {
							$contact[ $multi_value ] = array( $contact[ $multi_value ] );
						}
					} else if ( isset( $customer[ $multi_value ] ) ) {
						$contact[ $multi_value ] = array( $customer[ $multi_value ] );
					} else {
						$contact[ $multi_value ] = array();
					}
				}
				$valid_contact_email = false;
				$name_fallback       = false;
				$primary_email       = false;
				foreach ( $contact['email'] as $contact_key => $email ) {
					if ( ! $name_fallback && isset( $contact['name'][ $contact_key ] ) ) {
						$name_fallback = $contact['name'][ $contact_key ];
					}
					$contact['email'][ $contact_key ] = filter_var( strtolower( trim( $email ) ), FILTER_VALIDATE_EMAIL );
					if ( $contact['email'][ $contact_key ] ) {
						$valid_contact_email = true;
						if ( ! $primary_email ) {
							$primary_email = $contact['email'][ $contact_key ];
							// set the primary contact details here by adding them to the master customer array
							foreach ( $contact_fields as $primary_contact_field ) {
								$customer[ $primary_contact_field ] = isset( $contact[ $primary_contact_field ][ $contact_key ] ) ? $contact[ $primary_contact_field ][ $contact_key ] : '';
								unset( $contact[ $primary_contact_field ][ $contact_key ] );
							}
						}
					}
				}
				// start error checking / required fields
				if ( ! isset( $customer['customer_name'] ) || ! strlen( $customer['customer_name'] ) ) {
					$customer['customer_name'] = $name_fallback;
				}
				if ( ! strlen( $customer['customer_name'] ) ) {
					$result['error']      = 1;
					$result['messages'][] = "Failed, please go back and provide a customer name.";
				}
				if ( ! $valid_contact_email || ! $primary_email ) {
					$result['error']      = 1;
					$result['messages'][] = "Failed, please go back and provide an email address.";
				}
				// check all posted required fields.
				function check_required( $postdata, $messages = array() ) {
					if ( is_array( $postdata ) ) {
						foreach ( $postdata as $key => $val ) {
							if ( strpos( $key, '_required' ) && strlen( $val ) ) {
								$required_key = str_replace( '_required', '', $key );
								if ( ! isset( $postdata[ $required_key ] ) || ! $postdata[ $required_key ] ) {
									$messages[] = 'Required field missing: ' . htmlspecialchars( $val );
								}
							}
							if ( is_array( $val ) ) {
								$messages = check_required( $val, $messages );
							}
						}
					}

					return $messages;
				}

				$messages = check_required( $_POST );
				if ( count( $messages ) ) {
					$result['error']    = 1;
					$result['messages'] = array_merge( $result['messages'], $messages );
				}
				if ( isset( $result['error'] ) ) {
					customer_signup_complete( $result );
				}
				// end error checking / required fields.


				// check if this customer already exists in the system, based on email address
				$customer_id         = false;
				$creating_new        = true;
				$_REQUEST['user_id'] = 0;
				if ( isset( $customer['email'] ) && strlen( $customer['email'] ) && ! module_config::c( 'customer_signup_always_new', 0 ) ) {
					$users = module_user::get_contacts( array( 'email' => $customer['email'] ) );
					foreach ( $users as $user ) {
						if ( isset( $user['customer_id'] ) && (int) $user['customer_id'] > 0 ) {
							// this user exists as a customer! yey!
							// add them to this listing.
							$customer_id         = $user['customer_id'];
							$creating_new        = false;
							$_REQUEST['user_id'] = $user['user_id'];
							// dont let signups update existing passwords.
							if ( isset( $customer['password'] ) ) {
								unset( $customer['password'] );
							}
							if ( isset( $customer['new_password'] ) ) {
								unset( $customer['new_password'] );
							}
						}
					}
				}

				$_REQUEST['extra_customer_field']                = array();
				$_REQUEST['extra_user_field']                    = array();
				module_extra::$config['allow_new_keys']          = false;
				module_extra::$config['delete_existing_empties'] = false;

				// save customer extra fields.
				if ( count( $customer_extra ) ) {
					// format the address so "save_customer" handles the save for us
					foreach ( $customer_extra as $key => $val ) {
						$_REQUEST['extra_customer_field'][] = array(
							'key' => $key,
							'val' => $val,
						);
					}
				}
				// save customer and customer contact details:
				$customer_id = $this->save_customer( $customer_id, $customer );
				if ( ! $customer_id ) {
					$result['error']      = 1;
					$result['messages'][] = 'System error: failed to create customer.';
					customer_signup_complete( $result );
				}
				$customer_data = module_customer::get_customer( $customer_id );
				// todo - merge primary and secondary contact/extra/group saving into a single loop
				if ( ! $customer_data['primary_user_id'] ) {
					$result['error']      = 1;
					$result['messages'][] = 'System error: Failed to create customer contact.';
					customer_signup_complete( $result );
				} else {
					$role_id = module_config::c( 'customer_signup_role', 0 );
					if ( $role_id > 0 ) {
						module_user::add_user_to_role( $customer_data['primary_user_id'], $role_id );
					}
					// save contact extra data (repeated below for additional contacts)
					if ( isset( $contact_extra[0] ) && count( $contact_extra[0] ) ) {
						$_REQUEST['extra_user_field'] = array();
						foreach ( $contact_extra[0] as $key => $val ) {
							$_REQUEST['extra_user_field'][] = array(
								'key' => $key,
								'val' => $val,
							);
						}
						module_extra::save_extras( 'user', 'user_id', $customer_data['primary_user_id'] );
					}
					// save contact groups
					if ( isset( $contact_group[0] ) && count( $contact_group[0] ) ) {
						foreach ( $contact_group[0] as $group_id => $tf ) {
							if ( $tf ) {
								module_group::add_to_group( $group_id, $customer_data['primary_user_id'], 'user' );
							}
						}
					}
				}
				foreach ( $contact['email'] as $contact_key => $email ) {
					// add any additional contacts to the customer.
					$users = module_user::get_contacts( array( 'email' => $email, 'customer_id' => $customer_id ) );
					if ( count( $users ) ) {
						// this contact already exists for this customer, dont update/change it.
						continue;
					}
					$new_contact = array(
						'customer_id' => $customer_id,
					);
					foreach ( $contact_fields as $primary_contact_field ) {
						$new_contact[ $primary_contact_field ] = isset( $contact[ $primary_contact_field ][ $contact_key ] ) ? $contact[ $primary_contact_field ][ $contact_key ] : '';
					}
					// dont let additional contacts have passwords.
					if ( isset( $new_contact['password'] ) ) {
						unset( $new_contact['password'] );
					}
					if ( isset( $new_contact['new_password'] ) ) {
						unset( $new_contact['new_password'] );
					}
					global $plugins;
					$contact_user_id = $plugins['user']->create_user( $new_contact, 'signup' );
					if ( $contact_user_id ) {
						$role_id = module_config::c( 'customer_signup_role', 0 );
						if ( $role_id > 0 ) {
							module_user::add_user_to_role( $contact_user_id, $role_id );
						}
						// save contact extra data  (repeated below for primary contacts)
						if ( isset( $contact_extra[ $contact_key ] ) && count( $contact_extra[ $contact_key ] ) ) {
							$_REQUEST['extra_user_field'] = array();
							foreach ( $contact_extra[ $contact_key ] as $key => $val ) {
								$_REQUEST['extra_user_field'][] = array(
									'key' => $key,
									'val' => $val,
								);
							}
							module_extra::save_extras( 'user', 'user_id', $contact_user_id );
						}
						// save contact groups
						if ( isset( $contact_group[ $contact_key ] ) && count( $contact_group[ $contact_key ] ) ) {
							foreach ( $contact_group[ $contact_key ] as $group_id => $tf ) {
								if ( $tf ) {
									module_group::add_to_group( $group_id, $contact_user_id, 'user' );
								}
							}
						}
					}
				}
				if ( count( $customer_group ) ) {
					// format the address so "save_customer" handles the save for us
					foreach ( $customer_group as $group_id => $tf ) {
						if ( $tf ) {
							module_group::add_to_group( $group_id, $customer_id, 'customer' );
						}
					}
				}
				$note_keys = array( 'customer', 'website', 'job', 'address', 'subscription' );
				$note_text = _l( 'Customer signed up from Signup Form:' );
				$note_text .= "\n\n";
				foreach ( $note_keys as $note_key ) {
					$note_text .= "\n" . ucwords( _l( $note_key ) ) . "\n";
					if ( isset( $_POST[ $note_key ] ) && is_array( $_POST[ $note_key ] ) ) {
						foreach ( $_POST[ $note_key ] as $post_key => $post_val ) {
							$note_text .= "\n - " . _l( $post_key ) . ": ";
							if ( is_array( $post_val ) ) {
								foreach ( $post_val as $p => $v ) {
									$note_text .= "\n  - - " . _l( $p ) . ': ' . $v;
								}
							} else {
								$note_text .= $post_val;
							}
						}
					}
				}
				$note_data = array(
					'note_id'     => false,
					'owner_id'    => $customer_id,
					'owner_table' => 'customer',
					'note_time'   => time(),
					'note'        => $note_text,
					'rel_data'    => module_customer::link_open( $customer_id ),
					'reminder'    => 0,
					'user_id'     => 0,
				);
				update_insert( 'note_id', false, 'note', $note_data );

				// save customer address fields.
				if ( count( $address ) ) {
					$address_db              = module_address::get_address( $customer_id, 'customer', 'physical' );
					$address_id              = $address_db && isset( $address_db['address_id'] ) ? (int) $address_db['address_id'] : false;
					$address['owner_id']     = $customer_id;
					$address['owner_table']  = 'customer';
					$address['address_type'] = 'physical';
					// we have post data to save, write it to the table!!
					module_address::save_address( $address_id, $address );
				}

				// website:
				$allowed = array( 'url', 'name', 'extra', 'notes' );
				foreach ( $website as $key => $val ) {
					if ( ! in_array( $key, $allowed ) ) {
						unset( $website[ $key ] );
					}
				}
				$website['url'] = isset( $website['url'] ) ? strtolower( trim( $website['url'] ) ) : '';
				$website_id     = 0;
				if ( count( $website ) && class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
					if ( strlen( $website['url'] ) ) {
						// see if website already exists, don't create or update existing one for now.
						$existing_websites = module_website::get_websites( array(
							'customer_id' => $customer_id,
							'url'         => $website['url']
						) );
						foreach ( $existing_websites as $existing_website ) {
							$website_id = $existing_website['website_id'];
						}
					}
					//   echo $website_id;echo $website['url']; print_r($website_extra);exit;
					if ( ! $website_id ) {
						$website_data                = module_website::get_website( $website_id );
						$website_data['url']         = isset( $website['url'] ) ? $website['url'] : 'N/A';
						$website_data['name']        = isset( $website['url'] ) ? $website['url'] : 'N/A';
						$website_data['customer_id'] = $customer_id;
						$website_id                  = update_insert( 'website_id', false, 'website', $website_data );
						// save website extra data.
						if ( $website_id && count( $website_extra ) ) {
							$_REQUEST['extra_website_field'] = array();
							foreach ( $website_extra as $key => $val ) {
								$_REQUEST['extra_website_field'][] = array(
									'key' => $key,
									'val' => $val,
								);
							}
							module_extra::save_extras( 'website', 'website_id', $website_id );
						}
						if ( $website_id && isset( $website['notes'] ) && strlen( $website['notes'] ) ) {
							// add notes to this website.
							$note_data = array(
								'note_id'     => false,
								'owner_id'    => $website_id,
								'owner_table' => 'website',
								'note_time'   => time(),
								'note'        => $website['notes'],
								'rel_data'    => module_website::link_open( $website_id ),
								'reminder'    => 0,
								'user_id'     => $customer_data['primary_user_id'],
							);
							$note_id   = update_insert( 'note_id', false, 'note', $note_data );
						}
					}
					if ( $website_id ) {
						if ( count( $website_group ) ) {
							// format the address so "save_customer" handles the save for us
							foreach ( $website_group as $group_id => $tf ) {
								if ( $tf ) {
									module_group::add_to_group( $group_id, $website_id, 'website' );
								}
							}
						}
					}
				}
				// generate jobs for this customer.
				$job_created = array();
				if ( $job && isset( $job['type'] ) && is_array( $job['type'] ) ) {
					if ( module_config::c( 'customer_signup_any_job_type', 0 ) ) {
						foreach ( $job['type'] as $type_name ) {
							// we have a match in our system. create the job.
							$job_data         = module_job::get_job( false );
							$job_data['type'] = $type_name;
							if ( ! $job_data['name'] ) {
								$job_data['name'] = $type_name;
							}
							$job_data['website_id']  = $website_id;
							$job_data['customer_id'] = $customer_id;
							$job_id                  = update_insert( 'job_id', false, 'job', $job_data );
							// todo: add default tasks for this job type.
							$job_created [] = $job_id;
						}
					} else {
						foreach ( module_job::get_types() as $type_id => $type ) {
							foreach ( $job['type'] as $type_name ) {
								if ( $type_name == $type ) {
									// we have a match in our system. create the job.
									$job_data         = module_job::get_job( false );
									$job_data['type'] = $type;
									if ( ! $job_data['name'] ) {
										$job_data['name'] = $type;
									}
									$job_data['website_id']  = $website_id;
									$job_data['customer_id'] = $customer_id;
									$job_id                  = update_insert( 'job_id', false, 'job', $job_data );
									// todo: add default tasks for this job type.
									$job_created [] = $job_id;
								}
							}
						}
					}
					if ( count( $job_created ) && count( $job_extra ) ) {
						// save job extra data.
						foreach ( $job_created as $job_created_id ) {
							if ( $job_created_id && count( $job_extra ) ) {
								$_REQUEST['extra_job_field'] = array();
								foreach ( $job_extra as $key => $val ) {
									$_REQUEST['extra_job_field'][] = array(
										'key' => $key,
										'val' => $val,
									);
								}
								module_extra::save_extras( 'job', 'job_id', $job_created_id );
							}
						}
					}
				}
				// save files against customer
				$uploaded_files = array();
				if ( isset( $_FILES['customerfiles'] ) && isset( $_FILES['customerfiles']['tmp_name'] ) ) {
					foreach ( $_FILES['customerfiles']['tmp_name'] as $file_id => $tmp_file ) {
						if ( is_uploaded_file( $tmp_file ) ) {
							// save to file module for this customer
							$file_name = basename( $_FILES['customerfiles']['name'][ $file_id ] );
							if ( strlen( $file_name ) ) {
								$file_path = 'includes/plugin_file/upload/' . md5( time() . $file_name );
								if ( move_uploaded_file( $tmp_file, $file_path ) ) {
									// success! write to db.
									$file_data        = array(
										'customer_id' => $customer_id,
										'job_id'      => current( $job_created ), // just use the first job id as linked job.
										'website_id'  => $website_id, // doesn't actually save anywhere
										'status'      => module_config::c( 'file_default_status', 'Uploaded' ),
										'pointers'    => false,
										'description' => "Uploaded from Customer Signup form",
										'file_time'   => time(), // allow UI to set a file time? nah.
										'file_name'   => $file_name,
										'file_path'   => $file_path,
										'file_url'    => false,
									);
									$file_id          = update_insert( 'file_id', false, 'file', $file_data );
									$uploaded_files[] = $file_id;
								}
							}
						}
					}
				}

				// we create subscriptions for this customer/website (if none already exist)
				$subscription['subscription_name']    = array();
				$subscription['subscription_invoice'] = array();
				if ( class_exists( 'module_subscription', false ) && module_subscription::is_plugin_enabled() && isset( $subscription['for'] ) && isset( $subscription['subscriptions'] ) ) {
					if ( $subscription['for'] == 'website' && $website_id > 0 ) {
						$owner_table = 'website';
						$owner_id    = $website_id;
					} else {
						$owner_table = 'customer';
						$owner_id    = $customer_id;
					}

					$available_subscriptions = module_subscription::get_subscriptions();
					$members_subscriptions   = module_subscription::get_subscriptions_by( $owner_table, $owner_id );
					foreach ( $subscription['subscriptions'] as $subscription_id => $tf ) {
						if ( isset( $available_subscriptions[ $subscription_id ] ) ) {

							if ( isset( $members_subscriptions[ $subscription_id ] ) ) {
								// we don't allow a member to sign up to the same subscription twice (just yet)
							} else {
								$subscription['subscription_name'][ $subscription_id ] = $available_subscriptions[ $subscription_id ]['name'];

								$start_date          = date( 'Y-m-d' );
								$start_modifications = module_config::c( 'customer_signup_subscription_start', '' );
								if ( $start_modifications == 'hidden' ) {
									$start_modifications = isset( $_REQUEST['customer_signup_subscription_start'] ) ? $_REQUEST['customer_signup_subscription_start'] : '';
								}
								if ( ! empty( $start_modifications ) ) {
									$start_date = date( 'Y-m-d', strtotime( $start_modifications ) );
								}
								$sql = "INSERT INTO `" . _DB_PREFIX . "subscription_owner` SET ";
								$sql .= " owner_id = '" . (int) $owner_id . "'";
								$sql .= ", owner_table = '" . db_escape( $owner_table ) . "'";
								$sql .= ", subscription_id = '" . (int) $subscription_id . "'";
								$sql .= ", start_date = '$start_date'";
								query( $sql );
								module_subscription::update_next_due_date( $subscription_id, $owner_table, $owner_id, true );
								// and the same option here to send a subscription straight away upon signup
								if ( module_config::c( 'subscription_send_invoice_straight_away', 0 ) ) {
									global $plugins;
									$plugins['subscription']->run_cron();
									// check if there are any invoices for this subscription
									$history = module_subscription::get_subscription_history( $subscription_id, $owner_table, $owner_id );
									if ( count( $history ) > 0 ) {
										foreach ( $history as $h ) {
											if ( $h['invoice_id'] ) {
												$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
												if ( $invoice_data['date_cancel'] != '0000-00-00' ) {
													continue;
												}
												$subscription['subscription_invoice'][] = '<a href="' . module_invoice::link_public( $h['invoice_id'] ) . '">' . _l( 'Invoice #%s for %s',
														htmlspecialchars( $invoice_data['name'] ),
														dollar( $invoice_data['total_amount'], true, $invoice_data['currency_id'] )
													) . '</a>';
											}
										}
									}
								}
							}
						}
					}

				}
				if ( ! count( $subscription['subscription_name'] ) ) {
					$subscription['subscription_name'][] = _l( 'N/A' );
				}
				if ( ! count( $subscription['subscription_invoice'] ) ) {
					$subscription['subscription_invoice'][] = _l( 'N/A' );
				}
				$subscription['subscription_name']    = implode( ', ', $subscription['subscription_name'] );
				$subscription['subscription_invoice'] = implode( ', ', $subscription['subscription_invoice'] );

				// email the admin when a customer signs up.
				$values                  = array_merge( $customer, $customer_extra, $website, $website_extra, $address, $subscription );
				$values['customer_name'] = $customer['customer_name'];
				$values['CUSTOMER_LINK'] = module_customer::link_generate( $customer_id, array(
					'skip_permissions' => true,
					'full'             => false,
					'data'             => $customer_data
				) );
				//$values['CUSTOMER_NAME_LINK'] = module_customer::link_open($customer_id,true,$customer_data);
				$values['CUSTOMER_NAME_LINK'] = module_customer::link_generate( $customer_id, array(
					'skip_permissions' => true,
					'full'             => true,
					'data'             => $customer_data
				) );
				if ( $website_id ) {
					$values['WEBSITE_LINK']      = module_website::link_open( $website_id );
					$values['WEBSITE_NAME_LINK'] = module_website::link_open( $website_id, true );
				} else {
					$values['WEBSITE_LINK']      = _l( 'N/A' );
					$values['WEBSITE_NAME_LINK'] = _l( 'N/A' );
				}
				$values['JOB_LINKS'] = '';
				if ( count( $job_created ) ) {
					$values['JOB_LINKS'] .= 'The customer created ' . count( $job_created ) . ' jobs in the system: <br>';
					foreach ( $job_created as $job_created_id ) {
						$values['JOB_LINKS'] .= module_job::link_open( $job_created_id, true ) . "<br>\n";
					}
				} else {
					$values['JOB_LINKS'] = _l( 'N/A' );
				}

				if ( count( $uploaded_files ) ) {
					$values['uploaded_files'] = 'The customer uploaded ' . count( $uploaded_files ) . " files:<br>\n";
					foreach ( $uploaded_files as $uploaded_file ) {
						$values['uploaded_files'] .= module_file::link_open( $uploaded_file, true ) . "<br>\n";
					}
				} else {
					$values['uploaded_files'] = 'No files were uploaded';
				}
				$values['WEBSITE_NAME'] = ( isset( $website['url'] ) ) ? $website['url'] : 'N/A';
				if ( ! $creating_new ) {
					$values['system_note'] = "Note: this signup updated the existing customer record in the system.";
				} else {
					$values['system_note'] = "Note: this signup created a new customer record in the system.";
				}

				$customer_signup_template = module_config::c( 'customer_signup_email_admin_template', 'customer_signup_email_admin' );
				if ( isset( $_REQUEST['customer_signup_email_admin_template'] ) ) {
					$customer_signup_template = $_REQUEST['customer_signup_email_admin_template'];
				}
				if ( $customer_signup_template ) {
					$template = module_template::get_template_by_key( $customer_signup_template );
					if ( $template->template_id ) {
						$template->assign_values( $values );
						$html                  = $template->render( 'html' );
						$email                 = module_email::new_email();
						$email->replace_values = $values;
						$email->set_subject( $template->description );
						$email->set_to_manual( module_config::c( 'customer_signup_admin_email', module_config::c( 'admin_email_address' ) ) );
						// do we send images inline?
						$email->set_html( $html );
						if ( $email->send() ) {
							// it worked successfully!!
						} else {
							/// log err?
						}
					}
				}

				$customer_signup_template = module_config::c( 'customer_signup_email_welcome_template', 'customer_signup_email_welcome' );
				if ( isset( $_REQUEST['customer_signup_email_welcome_template'] ) ) {
					$customer_signup_template = $_REQUEST['customer_signup_email_welcome_template'];
				}
				if ( $customer_signup_template ) {
					$template = module_template::get_template_by_key( $customer_signup_template );
					if ( $template->template_id ) {
						$template->assign_values( $values );
						$html                  = $template->render( 'html' );
						$email                 = module_email::new_email();
						$email->customer_id    = $customer_id;
						$email->replace_values = $values;
						$email->set_subject( $template->description );
						$email->set_to( 'user', $customer_data['primary_user_id'] );
						// do we send images inline?
						$email->set_html( $html );
						if ( $email->send() ) {
							// it worked successfully!!
						} else {
							/// log err?
						}
					}
				}

				//todo: optional redirect to url
				if ( isset( $_REQUEST['via_ajax'] ) ) {
					echo json_encode( array( 'success' => 1, 'customer_id' => $customer_id ) );
					exit;
				}
				if ( module_config::c( 'customer_signup_redirect', '' ) ) {
					redirect_browser( module_config::c( 'customer_signup_redirect', '' ) );
				}
				// load up the thank you template.
				$template             = module_template::get_template_by_key( 'customer_signup_thank_you_page' );
				$template->page_title = _l( "Customer Signup" );
				foreach ( $values as $key => $val ) {
					if ( ! is_array( $val ) ) {
						$values[ $key ] = htmlspecialchars( $val );
					}
				}
				$template->assign_values( $values );
				echo $template->render( 'pretty_html' );
				exit;

				break;
		}
	}

	public static function get_replace_fields( $customer_id, $primary_user_id = false ) {

		$customer_data    = module_customer::get_customer( $customer_id );
		$address_combined = array();
		if ( isset( $customer_data['customer_address'] ) ) {
			foreach ( $customer_data['customer_address'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$address_combined[ $key ] = $val;
				}
			}
		}
		// do we use the primary contact or
		if ( $primary_user_id <= 0 ) {
			$primary_user_id = 0;
		}
		$contact_data = module_user::get_user( $primary_user_id ? $primary_user_id : $customer_data['primary_user_id'] );
		//print_r($contact_data);exit;
		if ( $contact_data && $contact_data['customer_id'] != $customer_id && ( ! isset( $contact_data['linked_parent_user_id'] ) || ! $contact_data['linked_parent_user_id'] ) ) {
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

		$data = array(
			'customer_details'        => ' - todo - ',
			'customer_name'           => isset( $customer_data['customer_name'] ) ? htmlspecialchars( $customer_data['customer_name'] ) : _l( 'N/A' ),
			'customer_address'        => htmlspecialchars( implode( ', ', $address_combined ) ),
			'contact_name'            => ( $contact_data['name'] != $contact_data['email'] ) ? htmlspecialchars( $contact_data['name'] . ' ' . $contact_data['last_name'] ) : '',
			'contact_first_name'      => $contact_data['name'],
			'contact_last_name'       => $contact_data['last_name'],
			// these two may be overridden when sending an email and selecting a different contact from the drop down menu.
			'first_name'              => $contact_data['name'],
			'last_name'               => $contact_data['last_name'],
			'contact_email'           => htmlspecialchars( $contact_data['email'] ),
			'contact_phone'           => htmlspecialchars( $contact_data['phone'] ),
			'contact_mobile'          => htmlspecialchars( $contact_data['mobile'] ),
			'customer_invoice_prefix' => isset( $customer_data['default_invoice_prefix'] ) ? $customer_data['default_invoice_prefix'] : '',
		);

		$data = array_merge( $customer_data, $data );

		foreach ( $customer_data['customer_address'] as $key => $val ) {
			$data[ 'address_' . $key ] = $val;
		}


		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {


			$group_owner = self::get_group_owner_slug();

			// get the customer groups
			$g = array();
			if ( (int) $customer_data['customer_id'] > 0 ) {
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => $group_owner,
						'owner_id'    => $customer_data['customer_id'],
					) ) as $group
				) {
					$g[] = $group['name'];
				}
			}
			$data['customer_group'] = implode( ', ', $g );
			// get the customer groups
			$g = array();
			if ( $customer_id > 0 ) {
				$customer_data = module_customer::get_customer( $customer_id );
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => $group_owner,
						'owner_id'    => $customer_id,
					) ) as $group
				) {
					$g[ $group['group_id'] ] = $group['name'];
				}
			}
			$data['customer_group'] = implode( ', ', $g );
		}

		// addition. find all extra keys for this customer and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'customer' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'customer', 'owner_id' => $customer_id ) );
			foreach ( $extras as $e ) {
				//                $data[$e['extra_key']] = $e['extra'];
				$extra_value = module_extra::get_display_value( $e );
				if ( is_array( $extra_value ) ) {
					$data[ $e['extra_key'] ] = '(array)';
					foreach ( $extra_value as $extra_key => $extra_val ) {
						$data[ $e['extra_key'] . '.' . $extra_key ] = $extra_val;
					}
				} else {
					$data[ $e['extra_key'] ] = $extra_value;
				}
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

	public static function get_note_summary_owners( $customer_id ) {
		$note_summary_owners = array();
		// generate a list of all possible notes we can display for this customer.
		// display all the notes which are owned by all the sites we have access to

		// display all the notes which are owned by all the users we have access to
		foreach ( module_user::get_contacts( array( 'customer_id' => $customer_id ) ) as $val ) {
			$note_summary_owners['user'][] = $val['user_id'];
		}
		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
			foreach ( module_website::get_websites( array( 'customer_id' => $customer_id ) ) as $val ) {
				$note_summary_owners['website'][] = $val['website_id'];
			}
		}
		if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
			foreach ( module_job::get_jobs( array( 'customer_id' => $customer_id ) ) as $val ) {
				$note_summary_owners['job'][] = $val['job_id'];
				foreach ( module_invoice::get_invoices( array( 'job_id' => $val['job_id'] ) ) as $inv ) {
					$note_summary_owners['invoice'][ $inv['invoice_id'] ] = $inv['invoice_id'];
				}
			}
		}
		if ( class_exists( 'module_invoice', false ) && module_invoice::is_plugin_enabled() ) {
			foreach ( module_invoice::get_invoices( array( 'customer_id' => $customer_id ) ) as $val ) {
				$note_summary_owners['invoice'][ $val['invoice_id'] ] = $val['invoice_id'];
			}
		}

		return $note_summary_owners;
	}

	public static function hook_header_print_js() {
		if ( module_security::is_logged_in() ) {
			?>
			<script type="text/javascript">
          if (typeof ucm.customer != 'undefined') {
              ucm.customer.settings.ajax_url = '<?php echo module_customer::link_open( false );?>';
              ucm.customer.settings.choose = '<?php _e( ' - Choose - ' );?>';
              ucm.customer.settings.loading = '<?php _e( 'Loading...' );?>';
          }
			</script>
			<?php
		}
	}

	public static function dynamic_customer_selection( $customer_id = false, $input_id = 'customer_id' ) {
		?>
		<div class="dynamic_customer_selection <?php echo $customer_id ? ' has-current' : ''; ?>">
			<div class="current_customer">
				<?php
				if ( $customer_id && module_customer::can_i( 'view', 'Customers' ) ) { ?>
					<?php echo module_customer::link_open( $customer_id, true ); ?> <br/>
					<input type="button" name="choose_new" value="Choose New" class="choose_new_customer btn small_button">
				<?php } ?>
			</div>
			<div class="choose_customer">
				<input type="hidden" name="<?php echo $input_id; ?>" value="<?php echo (int) $customer_id; ?>"
				       class="change_customer_id_input">
				<div class="choose_customer_type">
					<?php
					echo print_select_box( self::get_customer_types(), 'dynamic_choose_customer_type', false, 'dynamic_choose_customer_type', _l( ' - Type - ' ), 'type_name_plural' );
					?>
				</div>
				<div class="choose_customer_select">
				</div>
			</div>
		</div>
		<?php
	}


	public static function get_group_owner_slug() {

		$group_owner = 'customer';
		if(module_config::c('groups_unique_per_customer_type',0)) {
			$current_customer_type_id = module_customer::get_current_customer_type_id();
			if ( $current_customer_type_id > 0 ) {
				$customer_type = module_customer::get_customer_type( $current_customer_type_id );
				if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
					$group_owner = 'customer_' . $current_customer_type_id;
				}
			}
		}
		return $group_owner;

	}

	public static function get_config_fields( $customer_id ) {

		$sql         = "SELECT config_key FROM `" . _DB_PREFIX . "customer_config` GROUP BY `config_key`";
		$all_configs = qa( $sql );

		$return = array();

		$return['default_quote_prefix'] = array( 'text' => 'Quote Prefix', 'config_val' => '' );
		$return['default_job_prefix']   = array( 'text' => 'Job Prefix', 'config_val' => '' );

		// give our custom descriptions here:
		$return['hourly_rate']                    = array( 'text' => 'Hourly Rate', 'config_val' => '' );
		$return['default_task_type']              = array( 'text' => 'Default Task Type', 'config_val' => '' );
		$return['invoice_automatic_receipt']      = array( 'text' => 'Automatic Invoice Receipts', 'config_val' => '' );
		$return['invoice_template_print_default'] = array( 'text' => 'Invoice Print Template', 'config_val' => '' );
		$return['invoice_due_days']               = array( 'text' => 'Invoice Due Days', 'config_val' => '' );

		foreach ( $all_configs as $all_config ) {
			if ( ! isset( $return[ $all_config['config_key'] ] ) && $all_config['config_key'][0] != '_' ) {
				$return[ $all_config['config_key'] ] = array(
					'text'       => $all_config['config_key'],
					'config_val' => '',
				);
			}
		}

		// get the customer config stuff
		if ( (int) $customer_id > 0 ) {
			$sql             = "SELECT * FROM `" . _DB_PREFIX . "customer_config` WHERE `customer_id` = " . (int) $customer_id;
			$customer_config = qa( $sql );
			foreach ( $customer_config as $c ) {
				if ( isset( $return[ $c['config_key'] ] ) ) {
					$return[ $c['config_key'] ]['config_val'] = $c['config_val'];
				}
			}
		}


		return $return;

	}

	public static function link_public( $customer_id, $h = false ) {
		if ( $h ) {
			return md5( 'customer s3cret7hash for job ' . _UCM_SECRET . ' ' . $customer_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.customer/h.public/i.' . $customer_id . '/hash.' . self::link_public( $customer_id, true ) );
	}


	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'customer' );
		if ( ! isset( $fields['default_tax'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD  `default_tax` double(10,2) NOT NULL DEFAULT \'-1\' AFTER `credit`;';
		}
		if ( ! isset( $fields['default_tax_name'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD  `default_tax_name` varchar(10) NOT NULL DEFAULT \'\' AFTER `default_tax`;';
		}
		if ( ! isset( $fields['default_invoice_prefix'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD  `default_invoice_prefix` varchar(10) NOT NULL DEFAULT \'\' AFTER `default_tax_name`;';
		}
		if ( ! isset( $fields['customer_status'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD  `customer_status` tinyint(2) NOT NULL DEFAULT \'0\' AFTER `primary_user_id`;';
		}
		if ( ! isset( $fields['archived'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD `archived` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `customer_status`;';
		}
		if ( ! isset( $fields['customer_number'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD `customer_number` varchar(50) NOT NULL DEFAULT  \'\' AFTER `customer_status`;';
		}
		if ( ! isset( $fields['customer_type_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer` ADD  `customer_type_id` int(11) NOT NULL DEFAULT \'0\' AFTER `customer_id`;';
		} else {
			self::add_table_index( 'customer', 'customer_type_id' );
		}
		if ( ! self::db_table_exists( 'customer_user_rel' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'customer_user_rel` (
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`customer_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! self::db_table_exists( 'customer_config' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'customer_config` (
  `customer_id` int(11) NOT NULL,
  `config_key` varchar(255) NOT NULL DEFAULT \'\',
  `config_val` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY  (`customer_id`, `config_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! self::db_table_exists( 'customer_type' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'customer_type` (
		`customer_type_id` int(11) NOT NULL auto_increment,
		`menu_position` int(11) NOT NULL DEFAULT \'0\',
		`menu_icon` varchar(40) NOT NULL DEFAULT \'\',
		`type_name` varchar(255) NOT NULL DEFAULT \'\',
		`type_name_plural` varchar(255) NOT NULL DEFAULT \'\',
		`billing_type` tinyint(1) NOT NULL DEFAULT \'0\',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`customer_type_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
			$sql .= 'INSERT INTO `' . _DB_PREFIX . 'customer_type` VALUES (1, 0, \'users\', \'Lead\', \'Leads\', 0, NOW(), NOW());';
			//			$sql .= 'UPDATE `'._DB_PREFIX.'customer` SET `customer_type_id` = 1 WHERE `type` = 1;';

		}

		$fields = get_fields( 'customer_type' );
		if ( ! isset( $fields['billing_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'customer_type` ADD  `billing_type` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `type_name_plural`;';
		}

		return $sql;
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>customer` (
		`customer_id` int(11) NOT NULL auto_increment,
		`customer_type_id` int(11) NOT NULL DEFAULT '0',
		`primary_user_id` int(11) NOT NULL DEFAULT '0',
		`customer_status` tinyint(2) NOT NULL DEFAULT '0',
		`customer_number` varchar(50) NOT NULL DEFAULT '',
		`customer_name` varchar(255) NOT NULL DEFAULT '',
		`credit` double(10,2) NOT NULL DEFAULT '0',
		`default_tax` double(10,2) NOT NULL DEFAULT '-1',
		`default_tax_name` varchar(10) NOT NULL DEFAULT '',
		`default_invoice_prefix` varchar(10) NOT NULL DEFAULT '',
		`archived` tinyint(1) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`customer_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		INSERT INTO `<?php echo _DB_PREFIX; ?>customer` VALUES (1, 0, 3, 0, '1', 'Bobs Printing Service', 0, -1, '', '', 0, NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>customer` VALUES (2, 0, 4, 0, '2', 'Richards Roof Repairs', 0, -1, '', '', 0, NOW(), NOW());

		CREATE TABLE `<?php echo _DB_PREFIX; ?>customer_user_rel` (
		`customer_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		PRIMARY KEY  (`customer_id`, `user_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>customer_config` (
		`customer_id` int(11) NOT NULL,
		`config_key` varchar(255) NOT NULL DEFAULT '',
		`config_val` varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY  (`customer_id`, `config_key`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>customer_type` (
		`customer_type_id` int(11) NOT NULL auto_increment,
		`menu_position` int(11) NOT NULL DEFAULT '0',
		`menu_icon` varchar(40) NOT NULL DEFAULT '',
		`type_name` varchar(255) NOT NULL DEFAULT '',
		`type_name_plural` varchar(255) NOT NULL DEFAULT '',
		`billing_type` tinyint(1) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`customer_type_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		INSERT INTO `<?php echo _DB_PREFIX; ?>customer_type` VALUES (1, 0, 'users', 'Lead', 'Leads', 0, NOW(), NOW());
		INSERT INTO `<?php echo _DB_PREFIX; ?>customer_type` VALUES (2, 0, 'users', 'Supplier', 'Suppliers', 1, NOW(), NOW());

		<?php
		return ob_get_clean();
	}
}


include_once 'class.customer.php';

