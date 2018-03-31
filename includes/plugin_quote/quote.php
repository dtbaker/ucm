<?php


define( '_QUOTE_TASK_ACCESS_ALL', 'All tasks within a quote' );
define( '_QUOTE_TASK_ACCESS_ASSIGNED_ONLY', 'Only assigned tasks within a quote' );

define( '_QUOTE_ACCESS_ALL', 'All quotes in system' );
define( '_QUOTE_ACCESS_ASSIGNED', 'Only quotes I am assigned to' );
define( '_QUOTE_ACCESS_CUSTOMER', 'Quotes from customers I have access to' );

define( '_CUSTOM_DATA_HOOK_LOCATION_QUOTE_FOOTER', 6 );
define( '_CUSTOM_DATA_HOOK_LOCATION_QUOTE_SIDEBAR', 7 );


class module_quote extends module_base {

	public $links;
	public $quote_types;

	public $version = 2.179;
	//2.179 - 2017-05-18 - quote email fix
	//2.178 - 2017-05-07 - quote pdf fix
	//2.177 - 2017-05-02 - file path configuration
	//2.176 - 2017-05-02 - tax display at 0%
	//2.175 - 2017-05-02 - archive fix
	//2.174 - 2017-05-02 - big changes
	//2.173 - 2017-04-19 - start work on vendor quotes
	//2.172 - 2017-03-01 - archived
	//2.171 - 2017-01-02 - quote number incrementing fix
	//2.170 - 2016-12-20 - bug fix
	//2.169 - 2016-11-23 - product fields in task list
	//2.168 - 2016-11-16 - fontawesome icon fixes
	//2.166 - 2016-09-29 - product extra fields in task template
	//2.165 - 2016-08-12 - quote task wysiwyg fix
	//2.164 - 2016-07-18 - quote task wysiwyg
	//2.163 - 2016-07-10 - big update to mysqli
	//2.162 - 2016-05-15 - unit of measurement
	//2.161 - 2016-04-30 - basic inventory
	//2.160 - 2016-02-02 - layout fix
	//2.159 - 2015-12-28 - menu speed up
	//2.158 - 2015-12-28 - quote extra field permission fix
	//2.157 - 2015-12-09 - bug fix with certain php version
	//2.156 - 2015-12-08 - custom data integration
	//2.155 - 2015-06-16 - quote_url template fix
	//2.154 - 2015-06-08 - quote duplicate button
	//2.153 - 2015-05-10 - ampersand fix in pdf
	//2.152 - 2015-03-08 - arithmetic in quote_task_list template
	//2.151 - 2015-03-08 - quote pdf custom template task list support added
	//2.15 - 2015-02-12 - product defaults (tax/bill/type)
	//2.149 - 2014-12-22 - quote task decimal places
	//2.148 - 2014-12-05 - quote_dashboard_show_all_unapproved setting added
	//2.147 - 2014-11-26 - quote creation improvements
	//2.146 - 2014-11-04 - only quote assigned to permission bug fix
	//2.145 - 2014-10-08 - quote product hourly rate fix
	//2.144 - 2014-09-03 - negative taxes
	//2.143 - 2014-08-19 - tax_decimal_places and tax_trim_decimal
	//2.142 - 2014-07-31 - responsive improvements
	//2.141 - 2014-07-22 - quote translation improvement Qty/Amount/Discount
	//2.14 - 2014-07-18 - task_list added to quote email template
	//2.139 - 2014-07-08 - quote discounts and approval email notification
	//2.138 - 2014-06-24 - fix for negative quantites and amounts
	//2.137 - 2014-06-09 - permission to create job button fix
	//2.136 - 2014-06-09 - hours:minutes task formatting
	//2.135 - 2014-05-21 - new quote pdf template and faster pdf generation
	//2.134 - 2014-04-10 - {STAFF_FIRST_NAME} etc.. added to quote templates
	//2.133 - 2014-04-01 - quote description improvement
	//2.132 - 2014-03-31 - css fix on job create page
	//2.131 - 2014-03-26 - save and return button added
	//2.13 - 2014-03-15 - comma separated default list for multiple taxes
	//2.129 - 2014-03-10 - quote no tax bug fix
	//2.128 - 2014-02-26 - js toggle fix
	//2.127 - 2014-02-22 - print_link fix
	//2.126 - 2014-02-17 - qty / amount fix in quote pdf
	//2.125 - 2014-02-12 - convert quote to job fix
	//2.124 - 2014-02-06 - quote approval button in front end
	//2.123 - 2014-02-06 - number_trim_decimals advanced settings
	//2.122 - 2014-02-06 - qty and amount fix
	//2.121 - 2014-02-05 - convert a quote into a job
	//2.12 - 2014-02-05 - convert a quote into a job
	//2.11 - 2014-02-05 - quote tool - dashboard alerts
	//2.1 - 2014-02-05 - quote tool - initial release


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public static function get_task_types() {
		return module_job::get_task_types();
	}

	public function init() {
		$this->links           = array();
		$this->quote_types     = array();
		$this->module_name     = "quote";
		$this->module_position = 15.9;

		module_config::register_css( 'quote', 'quote.css' );
		module_config::register_js( 'quote', 'quote.js' );

		hook_add( 'custom_data_menu_locations', 'module_quote::hook_filter_custom_data_menu_locations' );
		hook_add( 'customer_archived', 'module_quote::customer_archived' );
		hook_add( 'customer_unarchived', 'module_quote::customer_unarchived' );

		if ( class_exists( 'module_template', false ) && module_security::is_logged_in() ) {
			module_template::init_template( 'quote_external', '{HEADER}<h2>Quote Overview</h2>
Quote Name: <strong>{QUOTE_NAME}</strong> <br/>
{PROJECT_TYPE} Name: <strong>{PROJECT_NAME}</strong> <br/>
Create Date: <strong>{DATE_CREATE}</strong><br/>
Quote Status: <strong>{if:DATE_APPROVED}Accepted on {DATE_APPROVED}{else}Pending{endif:DATE_APPROVED}</strong> <br/>
{DESCRIPTION}
<br/>
{if:date_approved}
<h2>Quote Has Been Accepted</h2>
<p>Thank you, the quote was accepted by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>.</p>
{else}
<h2>Quote Approval Pending</h2>
<p>If you would like to approve this quote please complete the form below:</p>
<form action="" method="POST">
<p>Your Name: <input type="text" name="quote_approve_name"> </p>
<p><input type="checkbox" name="quote_approve_go" value="yes"> Yes, I approve this quote. </p>
<p><input type="submit" name="quote_approve" value="Approve Quote" class="submit_button save_button"></p>
</form>
{endif:date_approved}

<h2>Task List</h2> <br/>
{TASK_LIST}
', 'Used when displaying the external view of a quote for approval.', 'code' );


			// old template rename change
			// todo: copy this to jobs and quotes as well.
			$sql          = "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE 'quote_pdf%'";
			$oldtemplates = qa( $sql );
			foreach ( $oldtemplates as $oldtemplate ) {
				$new_key = str_replace( 'quote_pdf', 'quote_print', $oldtemplate['template_key'] );
				if ( $new_key ) {
					$existingnew = qa( "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE '" . module_db::escape( $new_key ) . "'" );
					if ( ! $existingnew ) {
						update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => $new_key ) );
					} else {
						update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => 'old_' . $oldtemplate['template_key'] ) );
					}
					$old_default = module_config::c( 'quote_template_print_default', 'quote_print' );
					if ( $old_default == 'quote_pdf' ) {
						module_config::save_config( 'quote_template_print_default', 'quote_print' );
					}
				}
			}


			module_template::init_template( 'quote_print', '<html>
<head>
<title>Quote</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<style type="text/css">
body{
font-family:Helvetica, sans-serif;
padding:0;
margin:0;
}
td{
font-family:Helvetica, sans-serif;
padding:2px;
}
h3{
font-size: 22px;
font-weight: bold;
margin:10px 0 10px 0;
padding:0 0 5px 0;
border-bottom:1px solid #6f6f6f;
width:100%;
}
.style11 {
font-size: 24px;
font-weight: bold;
margin:0;
padding:0;
}
.task_header,
.task_header th{
background-color:#e8e8e8;
color: #6f6f6f;
font-weight: bold;
}
tr.odd{
background-color:#f9f9f9;
}
</style>
</head>
<body>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
<tbody>
<tr>
<td colspan="2" align="left" valign="top"><img title="Logo" src="http://ultimateclientmanager.com/images/logo_ucm.png" alt="Logo" width="202" height="60" /></td>
<td colspan="2" align="left" valign="top"><span class="style11">QUOTE</span></td>
</tr>
<tr>
<td width="12%">&nbsp;</td>
<td width="43%">&nbsp;</td>
<td width="14%">&nbsp;</td>
<td width="31%">&nbsp;</td>
</tr>
<tr>
<td><strong>ABN:</strong></td>
<td>12 345 678 912</td>
<td><strong>Quote No: <br /> </strong></td>
<td>{QUOTE_NUMBER}</td>
</tr>
<tr>
<td><strong>Email: </strong></td>
<td>your@company.com</td>
<td><strong>Issued Date:</strong></td>
<td>{DATE_CREATE}</td>
</tr>
<tr>
<td><strong>Web: </strong></td>
<td>www.company.com</td>
<td><strong>Valid Until:</strong></td>
<td>{DATE_CREATE+30d}</td>
</tr>
</tbody>
</table>
<h3>RECIPIENT</h3>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
<tbody>
<tr>
<td valign="top" width="12%"><strong>Company:</strong></td>
<td valign="top" width="43%">{CUSTOMER_NAME}</td>
<td valign="top" width="14%"><strong>Email:</strong></td>
<td valign="top" width="31%">{CONTACT_EMAIL}</td>
</tr>
<tr>
<td valign="top"><strong>Contact:</strong></td>
<td valign="top">{CONTACT_NAME}</td>
<td valign="top"><strong>{PROJECT_TYPE}:</strong>&nbsp;</td>
<td valign="top">{PROJECT_NAME}&nbsp;</td>
</tr>
<tr>
<td valign="top"><strong>Phone:</strong></td>
<td valign="top">{CONTACT_PHONE}</td>
<td valign="top">&nbsp;</td>
<td valign="top">&nbsp;</td>
</tr>
</tbody>
</table>
<h3>QUOTE DETAILS</h3>
<div>{TASK_LIST}</div>
<p>&nbsp;</p>
<h3>QUOTE APPROVAL</h3>
<p>{if:DATE_APPROVED}Thank you, this Quote was approved by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>{else} If you are happy with this quote please click the buttom below to process approval.</p>
<p><a href="{QUOTE_LINK}">Approve This Quote</a>{endif:DATE_APPROVED}</p>
</body>
</html>', 'Used for printing out an quote for the customer.', 'html' );


			module_template::init_template( 'quote_email', 'Dear {CUSTOMER_NAME},<br>
<br>
Please find attached details on your quote: {QUOTE_NAME}.<br><br>
You can view and approve this quote online by <a href="{QUOTE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Quote: {QUOTE_NAME}', array(
				'CUSTOMER_NAME'    => 'Customers Name',
				'QUOTE_NAME'       => 'Quote Name',
				'TOTAL_AMOUNT'     => 'Total amount of quote',
				'TOTAL_AMOUNT_DUE' => 'Total amount of quote remaining to be paid',
				'FROM_NAME'        => 'Your name',
				'QUOTE_URL'        => 'Link to quote for customer',
				'QUOTE_TASKS'      => 'Output of quote tasks similar to public link',
			) );


			module_template::init_template( 'quote_staff_email', 'Dear {STAFF_NAME},<br>
<br>
Please find below your {TASK_COUNT} assigned tasks for quote: {QUOTE_NAME}.<br><br>
You can view this quote by <a href="{QUOTE_URL}">clicking here</a>.<br><br>
{QUOTE_TASKS}<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Assigned Quote Tasks: {QUOTE_NAME}', array(
				'STAFF_NAME'  => 'Customers Name',
				'QUOTE_NAME'  => 'Quote Name',
				'TASK_COUNT'  => 'Number of assigned tasks',
				'QUOTE_URL'   => 'Link to quote for customer',
				'QUOTE_TASKS' => 'Output of quote tasks for this staff member',
			) );

			module_template::init_template( 'quote_approved_email', 'Dear {TO_NAME},<br>
<br>
This Quote has been approved: {QUOTE_NAME}.<br><br>
This Quote was approved by <strong>{APPROVED_BY}</strong> on <strong>{DATE_APPROVED}</strong>
You can view this quote by <a href="{QUOTE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Quote Approved: {QUOTE_NAME}', array(
				'QUOTE_NAME' => 'Quote Name',
				'QUOTE_URL'  => 'Link to quote for customer',
			) );


		}

	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Quotes' ) ) {
			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many quotes?
				$name = _l( 'Quotes' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$quotes = $this->get_quotes( array( 'customer_id' => $_REQUEST['customer_id'] ) );
					if ( count( $quotes ) ) {
						$name .= " <span class='menu_label'>" . count( $quotes ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "quote_admin",
					'args'                => array( 'quote_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'edit',
				);
			}
			$this->links[] = array(
				"name"      => "Quotes",
				"p"         => "quote_admin",
				'args'      => array( 'quote_id' => false ),
				'icon_name' => 'edit',
			);
		}

	}

	public static function is_plugin_enabled() {
		if ( parent::is_plugin_enabled() ) {
			// check if quote base exists.
			if ( ! class_exists( 'UCMBaseDocument' ) ) {
				set_error( 'Please upgrade to the latest version of UCM' );

				return false;
			}

			return true;
		}

		return false;
	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			$results = $this->get_quotes( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Quote: ' );
					$match_string    .= _shl( $result['name'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['quote_id'] ) . '">' . $match_string . '</a>';
				}
			}
		}

		return $ajax_results;
	}


	public function handle_hook( $hook, &$calling_module = false, $show_all = false ) {
		switch ( $hook ) {
			case 'dashboard_widgets':
				// see finance for example of widget usage.
				break;
			case "home_alerts":

				$cache_timeout = module_config::c( 'cache_objects', 60 );
				$cache_key     = 'home_alerts_' . module_security::get_loggedin_id();

				$alerts = array();

				if ( $show_all || module_config::c( 'quote_alerts', 1 ) ) {
					// find any quotes that are past the due date and dont have a finished date.

					$key = _l( 'Unapproved Quote' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'quote'          => _l( 'Quote Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							'assigned_staff' => _l( 'Staff' ),
							'date'           => _l( 'Sent Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns,
							'sort'    => array(
								'time' => 'DESC',
							)
						) );
					}
					if ( $cached_alerts = module_cache::get( 'quote', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {
						module_debug::log( array(
							'title' => 'Quote Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						if ( module_config::c( 'quote_dashboard_show_all_unapproved', 1 ) ) {
							$quotes = self::get_quotes( array(), array(
								'custom_where' => " AND u.date_approved = '0000-00-00'"
							) );
						} else {
							$quotes = self::get_quotes( array(), array(
								'custom_where' => " AND u.date_approved = '0000-00-00' AND u.date_create <= '" . date( 'Y-m-d', strtotime( '-' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "'"
							) );
						}
						foreach ( $quotes as $quote_data ) {
							// permission check:
							//                            $quote_data = self::get_quote($task['quote_id']);
							//                            if(!$quote_data || $quote_data['quote_id']!=$task['quote_id'])continue;
							$alert_res = process_alert( $quote_data['date_create'], 'temp' );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $quote_data['quote_id'], false, $quote_data );
								$alert_res['name'] = $quote_data['name'];

								// new dashboard alert layout here:
								$alert_res['time']           = strtotime( $alert_res['date'] );
								$alert_res['group']          = $key;
								$alert_res['quote']          = $this->link_open( $quote_data['quote_id'], true, $quote_data );
								$alert_res['customer']       = $quote_data['customer_id'] ? module_customer::link_open( $quote_data['customer_id'], true ) : _l( 'N/A' );
								$alert_res['website']        = $quote_data['website_id'] ? module_website::link_open( $quote_data['website_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $quote_data['user_id'] ? module_user::link_open( $quote_data['user_id'], true ) : _l( 'N/A' );
								$alert_res['date']           = print_date( $alert_res['date'] );
								$alert_res['days']           = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[ 'quoteincomplete' . $quote_data['quote_id'] ] = $alert_res;
							}
						}

						module_cache::put( 'quote', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}

				return $alerts;
				break;
		}

		return false;
	}

	public static function link_generate( $quote_id = false, $options = array(), $link_options = array() ) {

		// link generation can be cached and save a few db calls.
		$cache_options = $options;
		if ( isset( $cache_options['data'] ) ) {
			unset( $cache_options['data'] );
			$cache_options['data_name'] = isset( $options['data'] ) && isset( $options['data']['name'] ) ? $options['data']['name'] : '';
		}
		$cache_options['customer_id']  = isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : false;
		$cache_options['current_user'] = module_security::get_loggedin_id();
		$link_cache_key                = 'quote_link_' . $quote_id . '_' . md5( serialize( $cache_options ) );
		if ( $cached_link = module_cache::get( 'quote', $link_cache_key ) ) {
			return $cached_link;
		}
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );

		$key = 'quote_id';
		if ( $quote_id === false && $link_options ) {
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
			$options['type'] = 'quote';
		}
		$options['page'] = 'quote_admin';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['module'] = 'quote';

		$data = array();
		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		}

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $quote_id > 0 ) {
					$data = self::get_quote( $quote_id, false, true );
				} else {
					$data = array();
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? _l( 'N/A' ) : $data['name'];
			if ( ! $data || ! $quote_id || isset( $data['_no_access'] ) ) {
				$link = $options['text'];
				module_cache::put( 'quote', $link_cache_key, $link, $link_cache_timeout );

				return $link;
			}
		} else {
			if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
				$data['customer_id'] = (int) $_REQUEST['customer_id'];
			}
		}
		$options['text'] = isset( $options['text'] ) ? ( $options['text'] ) : ''; // htmlspecialchars is done in link_generatE() function
		// generate the arguments for this link
		$options['arguments']['quote_id'] = $quote_id;

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
			/*
            if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : _l('N/A');
            }
            */

		}
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			$link = link_generate( $link_options );

		}
		module_cache::put( 'quote', $link_cache_key, $link, $link_cache_timeout );

		return $link;
	}

	public static function link_open( $quote_id, $full = false, $data = array() ) {
		return self::link_generate( $quote_id, array( 'full' => $full, 'data' => $data ) );
	}

	public static function link_ajax_task( $quote_id, $full = false ) {
		return self::link_generate( $quote_id, array(
			'full'      => $full,
			'arguments' => array( '_process' => 'ajax_task' )
		) );
	}

	public static function link_create_quote_invoice( $quote_id, $full = false ) {
		return self::link_generate( $quote_id, array(
			'full'      => $full,
			'arguments' => array( '_process' => 'ajax_create_invoice' )
		) );
	}


	public static function link_public( $quote_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for quote ' . _UCM_SECRET . ' ' . $quote_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.quote/h.public/i.' . $quote_id . '/hash.' . self::link_public( $quote_id, true ) );
	}

	public static function get_replace_fields( $quote_id, $quote_data = false ) {

		if ( ! $quote_data ) {
			$quote_data = self::get_quote( $quote_id );
		}

		$data = array(
			'quote_number' => htmlspecialchars( $quote_data['name'] ),
			'quote_name'   => htmlspecialchars( $quote_data['name'] ),
			'project_type' => _l( module_config::c( 'project_name_single', 'Website' ) ),
			'print_link'   => self::link_public_print( $quote_id ),
			'quote_url'    => self::link_public( $quote_id ),

			'title'       => module_config::s( 'admin_system_name' ),
			'create_date' => print_date( $quote_data['date_create'] ),
		);
		if ( isset( $quote_data['customer_id'] ) && $quote_data['customer_id'] ) {
			$customer_data = module_customer::get_replace_fields( $quote_data['customer_id'], $quote_data['contact_user_id'] ? $quote_data['contact_user_id'] : false );
			$data          = array_merge( $data, $customer_data ); // so we get total_amount_due and stuff.
		}
		$user_details = array(
			'staff_first_name' => '',
			'staff_last_name'  => '',
			'staff_email'      => '',
			'staff_phone'      => '',
			'staff_fax'        => '',
			'staff_mobile'     => '',
		);
		if ( isset( $quote_data['user_id'] ) && $quote_data['user_id'] ) {
			$user_data = module_user::get_user( $quote_data['user_id'], false );
			if ( $user_data && $user_data['user_id'] == $quote_data['user_id'] ) {
				$user_details = array(
					'staff_first_name' => $user_data['name'],
					'staff_last_name'  => $user_data['last_name'],
					'staff_email'      => $user_data['email'],
					'staff_phone'      => $user_data['phone'],
					'staff_fax'        => $user_data['fax'],
					'staff_mobile'     => $user_data['mobile'],
				);
			}

		}
		$data = array_merge( $data, $user_details );

		foreach ( $quote_data as $key => $val ) {
			if ( strpos( $key, 'date' ) !== false ) {
				$quote_data[ $key ] = print_date( $val );
			}
		}

		if ( isset( $quote_data['description'] ) ) {
			$quote_data['description'] = module_security::purify_html( $quote_data['description'] );
		}

		//        $customer_data = $quote_data['customer_id'] ? module_customer::get_replace_fields($quote_data['customer_id']) : array();
		//        $website_data = $quote_data['website_id'] ? module_website::get_replace_fields($quote_data['website_id']) : array();
		//        $data = array_merge($data,$customer_data,$website_data,$quote_data);
		$data = array_merge( $data, $quote_data );


		$website_url = $project_names = $project_names_and_url = array();
		if ( $quote_data['website_id'] ) {
			$website_data = module_website::get_website( $quote_data['website_id'] );
			if ( $website_data && $website_data['website_id'] == $quote_data['website_id'] ) {
				if ( isset( $website_data['url'] ) && $website_data['url'] ) {
					$website_url[ $website_data['website_id'] ] = module_website::urlify( $website_data['url'] );
					$website_data['name_url']                   = $website_data['name'] . ' (' . module_website::urlify( $website_data['url'] ) . ')';
				} else {
					$website_data['name_url'] = $website_data['name'];
				}
				$project_names[ $website_data['website_id'] ]         = $website_data['name'];
				$project_names_and_url[ $website_data['website_id'] ] = $website_data['name_url'];
				$fields                                               = module_website::get_replace_fields( $website_data['website_id'], $website_data );
				foreach ( $fields as $key => $val ) {
					if ( ! isset( $data[ $key ] ) || ( ! $data[ $key ] && $val ) ) {
						$data[ $key ] = $val;
					}
				}
			}
		}
		$data['website_name']     = $data['project_name'] = forum_text( count( $project_names ) ? implode( ', ', $project_names ) : '' );
		$data['website_name_url'] = forum_text( count( $project_names_and_url ) ? implode( ', ', $project_names_and_url ) : '' );
		$data['website_url']      = forum_text( count( $website_url ) ? implode( ', ', $website_url ) : '' );


		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			// get the quote groups
			$wg = array();
			$g  = array();
			if ( $quote_id > 0 ) {
				$quote_data = module_quote::get_quote( $quote_id );
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'quote',
						'owner_id'    => $quote_id,
					) ) as $group
				) {
					$g[ $group['group_id'] ] = $group['name'];
				}
				/*// get the website groups
                foreach(module_group::get_groups_search(array(
                    'owner_table' => 'website',
                    'owner_id' => $quote_data['website_id'],
                )) as $group){
                    $wg[$group['group_id']] = $group['name'];
                }*/
			}
			$data['quote_group'] = implode( ', ', $g );
			/*$data['website_group'] = implode(', ',$wg);*/
		}

		// addition. find all extra keys for this quote and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'quote' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'quote', 'owner_id' => $quote_id ) );
			foreach ( $extras as $e ) {
				$data[ $e['extra_key'] ] = $e['extra'];
			}
		}
		// also do this for customer fields
		/*if($quote_data['customer_id']){
            $all_extra_fields = module_extra::get_defaults('customer');
            foreach($all_extra_fields as $e){
                $data[$e['key']] = _l('N/A');
            }
            $extras = module_extra::get_extras(array('owner_table'=>'customer','owner_id'=>$quote_data['customer_id']));
            foreach($extras as $e){
                $data[$e['extra_key']] = $e['extra'];
            }
        }*/


		return $data;
	}


	public static function link_public_print( $quote_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for quote ' . _UCM_SECRET . ' ' . $quote_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.quote/h.public_print/i.' . $quote_id . '/hash.' . self::link_public_print( $quote_id, true ) );
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'public_print':
				ob_start();

				$quote_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash     = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $quote_id && $hash ) {
					$correct_hash = $this->link_public_print( $quote_id, true );
					if ( $correct_hash == $hash ) {
						// check quote still exists.
						$quote_data = $this->get_quote( $quote_id );
						if ( ! $quote_data || $quote_data['quote_id'] != $quote_id ) {
							echo 'quote no longer exists';
							exit;
						}
						$pdf_file = $this->generate_pdf( $quote_id );

						if ( $pdf_file && is_file( $pdf_file ) ) {
							@ob_end_clean();
							@ob_end_clean();

							// send pdf headers and prompt the user to download the PDF

							header( "Pragma: public" );
							header( "Expires: 0" );
							header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
							header( "Cache-Control: private", false );
							header( "Content-Type: application/pdf" );
							header( "Content-Disposition: attachment; filename=\"" . basename( $pdf_file ) . "\";" );
							header( "Content-Transfer-Encoding: binary" );
							$filesize = filesize( $pdf_file );
							if ( $filesize > 0 ) {
								header( "Content-Length: " . $filesize );
							}
							// some hosting providershave issues with readfile()
							$read = readfile( $pdf_file );
							if ( ! $read ) {
								echo file_get_contents( $pdf_file );
							}

						} else {
							echo _l( 'Sorry PDF is not currently available.' );
						}
					}
				}

				exit;

				break;
			case 'public':
				$quote_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash     = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $quote_id && $hash ) {
					$correct_hash = $this->link_public( $quote_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$quote_data = $this->get_quote( $quote_id );

						if ( $quote_data ) {

							if ( isset( $_POST['quote_approve'] ) && isset( $_POST['quote_approve_go'] ) && isset( $_POST['quote_approve_name'] ) && strlen( $_POST['quote_approve_name'] ) > 0 ) {
								update_insert( 'quote_id', $quote_id, 'quote', array(
									'date_approved' => date( 'Y-m-d' ),
									'approved_by'   => $_POST['quote_approve_name'],
								) );
								self::quote_approved( $quote_id );

								redirect_browser( $this->link_public( $quote_id ) );
							}
							$quote_data    = self::get_replace_fields( $quote_id, $quote_data );
							$customer_data = $quote_data['customer_id'] ? module_customer::get_replace_fields( $quote_data['customer_id'] ) : array();
							$website_data  = $quote_data['website_id'] ? module_website::get_replace_fields( $quote_data['website_id'] ) : array();

							// correct!
							// load up the receipt template.
							$template = false;
							if ( ! empty( $quote_data['quote_template_external'] ) ) {
								$template = module_template::get_template_by_key( $quote_data['quote_template_external'] );
								if ( ! $template->template_id ) {
									$template = false;
								}
							}
							if ( ! $template ) {
								$template = module_template::get_template_by_key( 'quote_external' );
							}
							// generate the html for the task output
							ob_start();
							include( module_theme::include_ucm( 'includes/plugin_quote/template/quote_task_list.php' ) );
							$public_html             = ob_get_clean();
							$quote_data['task_list'] = $public_html;
							// do we link the quote name?
							$quote_data['header'] = '';
							if ( module_security::is_logged_in() && $this->can_i( 'edit', 'Quotes' ) ) {
								$quote_data['header'] = '<div style="text-align: center; padding: 0 0 10px 0; font-style: italic;">You can send this page to your customer as a quote or progress update (this message will be hidden).</div>';
							}


							//$quote_data['quote_name'] = $quote_data['name'];
							$quote_data['quote_name'] = self::link_open( $quote_id, true );
							// format some dates:
							$quote_data['date_create']   = $quote_data['date_create'] == '0000-00-00' ? '' : print_date( $quote_data['date_create'] );
							$quote_data['date_approved'] = $quote_data['date_approved'] == '0000-00-00' ? '' : print_date( $quote_data['date_approved'] );

							$quote_data['project_type'] = _l( module_config::c( 'project_name_single', 'Website' ) );
							//$website_data = $quote_data['website_id'] ? module_website::get_website($quote_data['website_id']) : array();
							$quote_data['project_name'] = isset( $website_data['name'] ) && strlen( $website_data['name'] ) ? $website_data['name'] : _l( 'N/A' );
							$template->assign_values( $customer_data );
							$template->assign_values( $website_data );
							$template->assign_values( $quote_data );
							$template->page_title = $quote_data['name'];
							echo $template->render( 'pretty_html' );
						}
					}
				}
				break;
		}
	}


	public function process() {
		$errors = array();
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['quote_id'] ) {
			$data = self::get_quote( $_REQUEST['quote_id'] );
			if ( module_form::confirm_delete( 'quote_id', "Really delete quote: " . $data['name'], self::link_open( $_REQUEST['quote_id'] ) ) ) {
				$this->delete_quote( $_REQUEST['quote_id'] );
				set_message( "quote deleted successfully" );
				redirect_browser( $this->link_open( false ) );
			}
		} else if ( "ajax_quote_list" == $_REQUEST['_process'] ) {

			$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
			$res         = module_quote::get_quotes( array( 'customer_id' => $customer_id ) );
			$options     = array();
			foreach ( $res as $row ) {
				$options[ $row['quote_id'] ] = $row['name'];
			}
			echo json_encode( $options );
			exit;

		} else if ( "ajax_create_invoice" == $_REQUEST['_process'] ) {

			$quote_id    = (int) $_REQUEST['quote_id'];
			$quote       = self::get_quote( $quote_id, true );
			$quote_tasks = self::get_tasks( $quote_id );

			if ( ! $quote || $quote['quote_id'] != $quote_id ) {
				exit;
			} // no permissions.
			if ( ! module_invoice::can_i( 'create', 'Invoices' ) ) {
				exit;
			} // no permissions

			ob_start();
			?>
			<p><?php _e( 'Please select which tasks to generate an invoice for:' ); ?></p>
			<ul>
				<?php foreach ( $quote['uninvoiced_quote_task_ids'] as $quote_task_id ) {
					if ( isset( $quote_tasks[ $quote_task_id ] ) ) {
						?>
						<li>
							<input type="checkbox" id="invoice_create_task_<?php echo $quote_task_id; ?>"
							       data-taskid="<?php echo $quote_task_id; ?>" class="invoice_create_task"
							       name="invoice_quote_task_id[<?php echo $quote_task_id; ?>]"
							       value="1" <?php echo $quote_tasks[ $quote_task_id ]['fully_completed'] ? 'checked' : ''; ?>>
							<label for="invoice_create_task_<?php echo $quote_task_id; ?>">
								(#<?php echo $quote_tasks[ $quote_task_id ]['task_order']; ?>)
								<?php echo htmlspecialchars( $quote_tasks[ $quote_task_id ]['description'] ); ?>
							</label>
						</li>
					<?php }
				} ?>
			</ul>
			<?php


			$html = ob_get_clean();

			echo $html;
			exit;


		} else if ( "ajax_task" == $_REQUEST['_process'] ) {

			// we are requesting editing a task.
			$quote_id    = (int) $_REQUEST['quote_id'];
			$quote       = self::get_quote( $quote_id, true );
			$quote_tasks = self::get_tasks( $quote_id );

			if ( ! $quote || $quote['quote_id'] != $quote_id ) {
				exit;
			} // no permissions.
			if ( ! self::can_i( 'edit', 'Quote Tasks' ) ) {
				exit;
			} // no permissions

			if ( isset( $_REQUEST['toggle_completed'] ) ) {

				$quote_task_id = (int) $_REQUEST['quote_task_id'];
				$task_data     = $quote_tasks[ $quote_task_id ];
				$result        = array();
				if ( $task_data && $task_data['quote_id'] == $quote_id ) {
					if ( $task_data['invoiced'] && $task_data['fully_completed'] ) {
						// dont allow to 'uncompleted' fully completed invoice tasks
					} else {
						// it is editable.
						$task_data['fully_completed_t'] = 1;
						$task_data['fully_completed']   = $task_data['fully_completed'] ? 0 : 1;
						// save a single quote task
						$this->save_quote_tasks( $quote_id, array( 'quote_task' => array( $quote_task_id => $task_data ) ) );
						$result['success']       = 1;
						$result['quote_id']      = $quote_id;
						$result['quote_task_id'] = $quote_task_id;
					}
				}
				echo json_encode( $result );
				exit;


			} else if ( isset( $_REQUEST['update_task_order'] ) ) {

				// updating the task orders for this task..
				$task_order = (array) $_REQUEST['task_order'];
				foreach ( $task_order as $quote_task_id => $new_order ) {
					if ( (int) $new_order > 0 && isset( $quote_tasks[ $quote_task_id ] ) ) {
						update_insert( 'quote_task_id', $quote_task_id, 'quote_task', array(
							'task_order' => (int) $new_order,
						) );
					}
				}
				echo 'done';
			} else {

				$quote_task_id = (int) $_REQUEST['quote_task_id'];
				$task_data     = $quote_tasks[ $quote_task_id ];
				$task_editable = true;


				// todo - load this select box in via javascript from existing one on page.
				$staff_members    = module_user::get_staff_members();
				$staff_member_rel = array();
				foreach ( $staff_members as $staff_member ) {
					$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
				}

				// new different formats for quote data.
				$task_data['manual_task_type_real'] = $task_data['manual_task_type'];
				if ( ( ! isset( $task_data['manual_task_type'] ) || $task_data['manual_task_type'] < 0 ) && isset( $quote['default_task_type'] ) ) {
					// use the quote task type
					$task_data['manual_task_type'] = $quote['default_task_type'];
				}

				if ( is_callable( 'module_product::sanitise_product_name' ) ) {
					$task_data = module_product::sanitise_product_name( $task_data, $quote['default_task_type'] );
				}

				if ( isset( $_REQUEST['get_preview'] ) ) {
					$after_quote_task_id    = $quote_task_id; // this will put it right back where it started.
					$previous_quote_task_id = 0;
					$quote_tasks            = self::get_tasks( $quote_id );
					foreach ( $quote_tasks as $k => $v ) {
						// find out where this new task position is!
						if ( $k == $quote_task_id ) {
							$after_quote_task_id = $previous_quote_task_id;
							break;
						}
						$previous_quote_task_id = $k;
					}
					$create_invoice_button = '';
					//if($quote['total_amount_invoicable'] > 0 && module_invoice::can_i('create','Invoices')){
					if ( count( $quote['uninvoiced_quote_task_ids'] ) && module_invoice::can_i( 'create', 'Invoices' ) ) {
						//href="'.module_invoice::link_generate('new',array('arguments'=>array( 'quote_id' => $quote_id, ))).'"
						$create_invoice_button = '<a class="submit_button save_button uibutton quote_generate_invoice_button" onclick="return ucm.quote.generate_invoice();">' . _l( 'Create Invoice' ) . '</a>';
					}
					$result = array(
						'quote_task_id'         => $quote_task_id,
						'after_quote_task_id'   => $after_quote_task_id,
						'html'                  => self::generate_task_preview( $quote_id, $quote, $quote_task_id, $task_data ),
						'summary_html'          => self::generate_quote_summary( $quote_id, $quote ),
						'create_invoice_button' => $create_invoice_button,
					);
					echo json_encode( $result );
				} else {
					$show_task_numbers = ( module_config::c( 'quote_show_task_numbers', 1 ) && $quote['auto_task_numbers'] != 2 );
					ob_start();
					include( 'pages/ajax_task_edit.php' );
					$result = array(
						'quote_task_id' => $quote_task_id,
						'hours'         => isset( $_REQUEST['hours'] ) ? (float) $_REQUEST['hours'] : 0,
						'html'          => ob_get_clean(),
						//'summary_html' => self::generate_quote_summary($quote_id,$quote),
					);
					echo json_encode( $result );
				}
			}

			exit;

		} else if ( "save_quote" == $_REQUEST['_process'] ) {


			$save_status = $this->save_quote( $_REQUEST['quote_id'], $_POST );
			$quote_id    = isset( $save_status['quote_id'] ) ? $save_status['quote_id'] : false;
			if ( ! $quote_id ) {
				set_error( 'Failed to save quote' );
				redirect_browser( module_quote::link_open( false ) );
			}

			// look for the new tasks flag.
			if ( isset( $_REQUEST['default_task_list_id'] ) && isset( $_REQUEST['default_tasks_action'] ) ) {
				switch ( $_REQUEST['default_tasks_action'] ) {
					case 'insert_default':
						if ( (int) $_REQUEST['default_task_list_id'] > 0 ) {
							$default       = self::get_default_task( $_REQUEST['default_task_list_id'] );
							$task_data     = $default['task_data'];
							$new_task_data = array( 'quote_task' => array() );
							foreach ( $task_data as $task ) {
								$task['quote_id']              = $quote_id;
								$new_task_data['quote_task'][] = $task;
							}
							$this->save_quote_tasks( $quote_id, $new_task_data );
						}
						break;
					case 'save_default':
						$new_default_name = trim( $_REQUEST['default_task_list_id'] );
						if ( $new_default_name != '' ) {
							// time to save it!
							$task_data        = self::get_tasks( $quote_id );
							$cached_task_data = array();
							foreach ( $task_data as $task ) {
								unset( $task['quote_task_id'] );
								unset( $task['date_done'] );
								unset( $task['invoice_id'] );
								unset( $task['task_order'] );
								unset( $task['create_user_id'] );
								unset( $task['update_user_id'] );
								unset( $task['date_created'] );
								unset( $task['date_updated'] );
								$task['saved_time'] = time();
								$cached_task_data[] = $task;

								/*$cached_task_data[] = array(
                                    'hours' => $task['hours'],
                                    'amount' => $task['amount'],
                                    'billable' => $task['billable'],
                                    'fully_completed' => $task['fully_completed'],
                                    'description' => $task['description'],
                                    'long_description' => $task['long_description'],
                                    'date_due' => $task['date_due'],
                                    'user_id' => $task['user_id'],
                                    'approval_required' => $task['approval_required'],
                                    'task_order' => $task['task_order'],
                                    'saved_time' => time(),
                                );*/
							}
							self::save_default_tasks( (int) $_REQUEST['default_task_list_id'], $new_default_name, $cached_task_data );
							unset( $task_data );
						}
						break;
				}
			}

			if ( isset( $_REQUEST['save_ajax_task'] ) ) {
				// do everything via ajax. trickery!

				if ( $quote_id <= 0 ) {
					die( 'Shouldnt happen' );
				}
				//$result     = $this->save_quote_tasks( $quote_id, $_POST );
				$result     = isset( $save_status['task_result'] ) ? $save_status['task_result'] : false;
				$quote_data = self::get_quote( $quote_id, false );
				$new_status = self::update_quote_completion_status( $quote_id );
				$new_status = addcslashes( htmlspecialchars( $new_status ), "'" );
				//module_cache::clear_cache();
				$new_quote_data = self::get_quote( $quote_id, false );

				if ( $quote_id != $_REQUEST['quote_id'] ) {
					?>
					<script type="text/javascript">
              top.location.href = '<?php echo $this->link_open( $quote_id );?>&added=true';
					</script>
					<?php
					exit;
				}

				// we now have to edit the parent DOM to reflect these changes.
				// what were we doing? adding a new task? editing an existing task?
				switch ( $result['status'] ) {
					case 'created':
						// we added a new task.
						// add a new task to the bottom (OR MID WAY!) through the task list.
						if ( (int) $result['quote_task_id'] > 0 ) {
							?>
							<script type="text/javascript">
                  parent.refresh_task_preview(<?php echo (int) $result['quote_task_id'];?>);
                  parent.clear_create_form();
                  parent.ucm.add_message('<?php _e( 'New task created successfully' );?>');
                  parent.ucm.display_messages(true);
									<?php if($quote_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();
									<?php } ?>
									<?php if($new_quote_data['date_approved'] != $quote_data['date_approved']){ ?>parent.jQuery('#date_approved').val('<?php echo print_date( $new_quote_data['date_approved'] );?>').change();
									<?php } ?>
							</script>
							<?php
						} else {
							set_error( 'New task creation failed.' );
							?>
							<script type="text/javascript">
                  top.location.href = '<?php echo $this->link_open( $quote_id );?>&added=true';
							</script>
							<?php
						}
						break;
					case 'deleted':
						// we deleted a task.
						set_message( 'Task removed successfully' );
						?>
						<script type="text/javascript">
                top.location.href = '<?php echo $this->link_open( $quote_id );?>';
								<?php if($quote_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();
								<?php } ?>
						</script>
						<?php
						break;
					case 'error':
						set_error( 'Something happened while trying to save a task. Unknown error.' );
						// something happened, refresh the parent browser frame
						?>
						<script type="text/javascript">
                top.location.href = '<?php echo $this->link_open( $quote_id );?>';
						</script>
						<?php
						break;
					case 'edited':
						// we changed a task (ie: completed?);
						// update this task above.
						if ( (int) $result['quote_task_id'] > 0 ) {
							?>
							<script type="text/javascript">
                  parent.canceledittask();
                  //parent.refresh_task_preview(<?php echo (int) $result['quote_task_id'];?>);
                  parent.ucm.add_message('<?php _e( 'Task saved successfully' );?>');
                  parent.ucm.display_messages(true);
									<?php if($quote_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();
									<?php } ?>
									<?php if($new_quote_data['date_approved'] != $quote_data['date_approved']){ ?>parent.jQuery('#date_approved').val('<?php echo print_date( $new_quote_data['date_approved'] );?>').change();
									<?php } ?>
							</script>
							<?php
						} else {
							?>
							<script type="text/javascript">
                  parent.canceledittask();
                  parent.ucm.add_error('<?php _e( 'Unable to save task' );?>');
                  parent.ucm.display_messages(true);
									<?php if($quote_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();
									<?php } ?>
							</script>
							<?php
						}
						break;
					default:
						?>
						<script type="text/javascript">
                parent.ucm.add_error('<?php _e( 'Unable to save task. Please check required fields.' );?>');
                parent.ucm.display_messages(true);
						</script>
						<?php
						break;
				}

				exit;
			}

			if ( isset( $_REQUEST['butt_print'] ) && $_REQUEST['butt_print'] ) {
				redirect_browser( module_quote::link_public_print( $quote_id ) );
			}
			if ( isset( $_REQUEST['butt_email'] ) && $_REQUEST['butt_email'] ) {
				redirect_browser( module_quote::link_generate( $quote_id, array( 'arguments' => array( 'email' => 1 ) ) ) );
			}
			if ( isset( $_REQUEST['butt_duplicate'] ) && $_REQUEST['butt_duplicate'] && module_quote::can_i( 'create', 'Quotes' ) ) {
				$new_quote_id = module_quote::duplicate_quote( $quote_id );
				set_message( 'Quote duplicated successfully' );
				redirect_browser( module_quote::link_generate( $new_quote_id ) );
			}

			if ( ! empty( $_REQUEST['butt_archive'] ) ) {
				$UCMQuote = new UCMQuote( $quote_id );
				if ( $UCMQuote->is_archived() ) {
					$UCMQuote->unarchive();
					set_message( "Quote unarchived successfully" );
				} else {
					$UCMQuote->archive();
					set_message( "Quote archived successfully" );
				}
			} else {
				set_message( "Quote saved successfully" );
			}
			//redirect_browser($this->link_open($quote_id));
			redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : $this->link_open( $quote_id ) );


		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}


	public static function get_valid_quote_ids() {
		return self::get_quotes( array(), array( 'columns' => 'u.quote_id' ) );
	}

	public static function get_quotes( $search = array(), $return_options = array() ) {
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
		$cache_key = 'get_quotes_' . md5( serialize( array( $search, $return_options ) ) );
		if ( $cached_item = module_cache::get( 'quote', $cache_key ) ) {
			return $cached_item;
		}
		$cache_timeout = module_config::c( 'cache_objects', 60 );

		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
		if ( isset( $return_options['columns'] ) ) {
			$sql .= $return_options['columns'];
		} else {
			$sql .= "u.*,u.quote_id AS id ";
			$sql .= ", u.name AS name ";
			$sql .= ", c.customer_name ";
			if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
				$sql .= ", w.name AS website_name";// for export
			}
			$sql .= ", us.name AS staff_member";// for export
		}
		$from = " FROM `" . _DB_PREFIX . "quote` u ";
		$from .= " LEFT JOIN `" . _DB_PREFIX . "customer` c USING (customer_id)";
		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "website` w ON u.website_id = w.website_id"; // for export
		}
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "user` us ON u.user_id = us.user_id"; // for export
		$where = " WHERE 1 ";
		if ( is_array( $return_options ) && isset( $return_options['custom_where'] ) ) {
			// put in return options so harder to push through from user end.
			$where .= $return_options['custom_where'];
		}


		if ( ! empty( $search['archived_status'] ) ) {
			switch ( $search['archived_status'] ) {
				case _ARCHIVED_SEARCH_NONARCHIVED:
					$where .= ' AND u.archived = 0 ';
					break;
				case _ARCHIVED_SEARCH_ARCHIVED:
					$where .= ' AND u.archived = 1 ';
					break;
				case _ARCHIVED_SEARCH_BOTH:
					//                    $where .= ' AND u.archived = 0 ';
					break;
			}
		}


		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' "; //OR ";
			//$where .= " u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
		foreach ( array( 'customer_id', 'website_id', 'status', 'type', 'date_create' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str = db_escape( $search[ $key ] );
				if ( $str[0] == '!' ) {
					// hack for != sql searching.
					$str   = ltrim( $str, '!' );
					$where .= " AND u.`$key` != '$str'";
				} else {
					$where .= " AND u.`$key` = '$str'";
				}
			}
		}
		if ( isset( $search['ticket_id'] ) && (int) $search['ticket_id'] > 0 ) {
			// join on the ticket_quote_rel tab.e
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "ticket_quote_rel` tqr USING (quote_id)";
			$where .= " AND tqr.ticket_id = " . (int) $search['ticket_id'];

		}
		if ( isset( $search['accepted'] ) && (int) $search['accepted'] > 0 ) {
			switch ( $search['accepted'] ) {
				case 1:
					// both complete and not complete quotes, dont modify query
					break;
				case 2:
					// only completed quotes.
					$where .= " AND u.date_approved != '0000-00-00'";
					break;
				case 3:
					// only non-completed quotes.
					$where .= " AND u.date_approved = '0000-00-00'";
					break;
			}
		}
		$group_order = ' GROUP BY u.quote_id ORDER BY u.name';


		switch ( self::get_quote_access_permissions() ) {
			case _QUOTE_ACCESS_ALL:

				break;
			case _QUOTE_ACCESS_ASSIGNED:
				// only assigned quotes!
				$from  .= " LEFT JOIN `" . _DB_PREFIX . "quote_task` t ON u.quote_id = t.quote_id ";
				$where .= " AND (u.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
			case _QUOTE_ACCESS_CUSTOMER:
				// tie in with customer permissions to only get quotes from customers we can access.
				$customers = module_customer::get_customers();
				if ( count( $customers ) ) {
					$where .= " AND u.customer_id IN ( ";
					foreach ( $customers as $customer ) {
						$where .= $customer['customer_id'] . ', ';
					}
					$where = rtrim( $where, ', ' );
					$where .= " ) ";
				}
				break;
		}

		// tie in with customer permissions to only get quotes from customers we can access.
		switch ( module_customer::get_customer_data_access() ) {
			case _CUSTOMER_ACCESS_ALL:
				// all customers! so this means all quotes!
				break;
			case _CUSTOMER_ACCESS_ALL_COMPANY:
			case _CUSTOMER_ACCESS_CONTACTS:
			case _CUSTOMER_ACCESS_TASKS:
			case _CUSTOMER_ACCESS_STAFF:
				$valid_customer_ids = module_security::get_customer_restrictions();
				if ( count( $valid_customer_ids ) ) {
					$where .= " AND ( u.customer_id = 0 OR u.customer_id IN ( ";
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
					$where .= " )";
					$where .= " )";
				}

		}

		$sql = $sql . $from . $where . $group_order;
		//        echo $sql;print_r(debug_backtrace());exit;
		$result = qa( $sql );
		//module_security::filter_data_set("quote",$result);
		module_cache::put( 'quote', $cache_key, $result, $cache_timeout );

		return $result;
		//		return get_multiple("quote",$search,"quote_id","fuzzy","name");

	}

	public static function get_task( $quote_id, $quote_task_id ) {
		return get_single( 'quote_task', array( 'quote_id', 'quote_task_id' ), array( $quote_id, $quote_task_id ) );
	}

	public static function get_tasks( $quote_id, $order_by = 'task' ) {
		if ( (int) $quote_id <= 0 ) {
			return array();
		}
		$sql = "SELECT t.*, t.quote_task_id AS id ";
		$sql .= ", u.name AS user_name";
		$sql .= ", j.name AS quote_name";
		$sql .= ", j.default_task_type";
		$sql .= " FROM `" . _DB_PREFIX . "quote_task` t ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON t.user_id = u.user_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "quote` j ON t.quote_id = j.quote_id";
		$sql .= " WHERE t.`quote_id` = " . (int) $quote_id;
		// permissions
		$quote_task_permissions = self::get_quote_task_access_permissions();
		switch ( $quote_task_permissions ) {
			case _QUOTE_TASK_ACCESS_ASSIGNED_ONLY:
				$sql .= " AND t.`user_id` = " . (int) module_security::get_loggedin_id();
				break;
		}
		$sql .= " GROUP BY t.quote_task_id ";
		switch ( $order_by ) {
			case 'task':
				$sql .= " ORDER BY t.task_order ";
				break;
		}

		return qa( $sql, false );
		//return get_multiple("task",array('quote_id'=>$quote_id),"quote_task_id","exact","quote_task_id");

	}

	public static function get_quote_items( $quote_id, $quote ) {
		// copy from quote.
		$quote_id = (int) $quote_id;
		if ( ! $quote ) {
			$quote = self::get_quote( $quote_id, true );
		}
		$sql         = "SELECT ii.quote_task_id AS id, ii.* "; // , j.hourly_rate
		$sql         .= " ,p.inventory_control ";
		$sql         .= " ,p.inventory_level_current ";
		$sql         .= " ,p.unitname ";
		$sql         .= " FROM `" . _DB_PREFIX . "quote_task` ii ";
		$sql         .= " LEFT JOIN `" . _DB_PREFIX . "product` p ";
		$sql         .= " USING (product_id) ";
		$sql         .= " WHERE ii.quote_id = $quote_id";
		$sql         .= " ORDER BY ii.task_order ";
		$quote_items = qa( $sql );
		//        print_r($quote_items);
		// DAVE READ THIS: tasks come in with 'hours' and 'amount' and 'manual_task_type'
		// calculate the 'task_hourly_rate' and 'invoite_item_amount' based on this.
		// 'amount' is NOT used in quote items. only 'quote_item_amount'
		foreach ( $quote_items as $quote_task_id => $quote_item_data ) {

			// new feature, task type.
			$quote_item_data['manual_task_type_real'] = $quote_item_data['manual_task_type'];
			if ( $quote_item_data['manual_task_type'] < 0 && isset( $quote['default_task_type'] ) ) {
				$quote_item_data['manual_task_type'] = $quote['default_task_type'];
			}
			if ( is_callable( 'module_product::sanitise_product_name' ) ) {
				$quote_item_data = module_product::sanitise_product_name( $quote_item_data, $quote['default_task_type'] );
			}

			// if there are no hours logged against this task
			if ( ! $quote_item_data['hours'] ) {
				//$quote_item_data['task_hourly_rate']=0;
			}
			// task_hourly_rate is used for calculations, if the hourly_rate is -1 then we use the default quote hourly rate
			$quote_item_data['task_hourly_rate'] = isset( $quote_item_data['hourly_rate'] ) && $quote_item_data['hourly_rate'] != 0 ? $quote_item_data['hourly_rate'] : $quote['hourly_rate'];
			// if we have a custom price for this task
			if ( $quote_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
				if ( $quote_item_data['amount'] != 0 ) {
					$quote_item_data['quote_item_amount'] = $quote_item_data['amount'];
					if ( $quote_item_data['hours'] == 0 ) {
						// hack to fix $0 quotes
						$quote_item_data['hours']            = 1;
						$quote_item_data['task_hourly_rate'] = $quote_item_data['amount'];
					}
					if ( $quote_item_data['task_hourly_rate'] * $quote_item_data['hours'] != $quote_item_data['amount'] ) {
						// hack to fix manual amount with non-matching hours.
						$quote_item_data['task_hourly_rate'] = $quote_item_data['amount'] / $quote_item_data['hours'];
					}
				} else {
					$quote_item_data['quote_item_amount'] = $quote_item_data['task_hourly_rate'] * $quote_item_data['hours'];
				}
			} else if ( $quote_item_data['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) {
				if ( $quote_item_data['amount'] != 0 ) {
					$quote_item_data['task_hourly_rate']  = $quote_item_data['amount'];
					$quote_item_data['quote_item_amount'] = $quote_item_data['amount'] * $quote_item_data['hours'];
				} else {
					$quote_item_data['quote_item_amount'] = $quote_item_data['task_hourly_rate'] * $quote_item_data['hours'];
				}
			} else {

				// this item is an 'amount only' column.
				// no calculations based on quantity and hours.
				if ( $quote_item_data['amount'] != 0 ) {
					$quote_item_data['task_hourly_rate']  = $quote_item_data['amount'];
					$quote_item_data['quote_item_amount'] = $quote_item_data['amount'];
				} else {
					$quote_item_data['task_hourly_rate']  = 0;
					$quote_item_data['quote_item_amount'] = 0;

				}
			}

			// set a default taxes to match the quote taxes if none defined
			if ( ( ! isset( $quote_item_data['taxes'] ) || ! count( $quote_item_data['taxes'] ) ) && isset( $quote_item_data['taxable'] ) && $quote_item_data['taxable'] && isset( $quote['taxes'] ) && count( $quote['taxes'] ) ) {
				$quote_item_data['taxes'] = $quote['taxes'];
			}
			if ( ! isset( $quote_item_data['taxes'] ) ) {
				$quote_item_data['taxes'] = array();
			}

			$quote_items[ $quote_task_id ] = $quote_item_data;

		}

		//print_r($quote_items);exit;
		return $quote_items;
	}

	private static function _quote_cache_key( $quote_id, $args = array() ) {
		return 'quote_' . $quote_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_quote( $quote_id, $full = true, $skip_permissions = false ) {
		$quote_id = (int) $quote_id;
		if ( $quote_id <= 0 ) {
			$quote = array();
		} else {

			$cache_key = self::_quote_cache_key( $quote_id, array( $quote_id, $full, $skip_permissions ) );
			if ( $cached_item = module_cache::get( 'quote', $cache_key ) ) {
				if ( function_exists( 'hook_filter_var' ) ) {
					$cached_item = hook_filter_var( 'get_quote', $cached_item, $quote_id );
				}

				return $cached_item;
			}
			$cache_key_full = self::_quote_cache_key( $quote_id, array( $quote_id, true, $skip_permissions ) );
			if ( $cache_key_full != $cache_key && $cached_item = module_cache::get( 'quote', $cache_key_full ) ) {
				if ( function_exists( 'hook_filter_var' ) ) {
					$cached_item = hook_filter_var( 'get_quote', $cached_item, $quote_id );
				}

				return $cached_item;
			}
			$cache_timeout = module_config::c( 'cache_objects', 60 );


			$quote = get_single( "quote", "quote_id", $quote_id );
		}
		// check permissions
		if ( $quote && isset( $quote['quote_id'] ) && $quote['quote_id'] == $quote_id ) {
			switch ( self::get_quote_access_permissions() ) {
				case _QUOTE_ACCESS_ALL:

					break;
				case _QUOTE_ACCESS_ASSIGNED:
					// only assigned quotes!
					$has_quote_access = false;
					if ( $quote['user_id'] == module_security::get_loggedin_id() ) {
						$has_quote_access = true;
						break;
					}
					$tasks = module_quote::get_tasks( $quote['quote_id'] );
					foreach ( $tasks as $task ) {
						if ( $task['user_id'] == module_security::get_loggedin_id() ) {
							$has_quote_access = true;
							break;
						}
					}
					unset( $tasks );
					if ( ! $has_quote_access ) {
						if ( $skip_permissions ) {
							$quote['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
						} else {
							$quote = false;
						}
					}
					break;
				case _QUOTE_ACCESS_CUSTOMER:
					// tie in with customer permissions to only get quotes from customers we can access.
					$customers        = module_customer::get_customers();
					$has_quote_access = false;
					if ( isset( $customers[ $quote['customer_id'] ] ) ) {
						$has_quote_access = true;
					}
					/*foreach($customers as $customer){
                        // todo, if($quote['customer_id'] == 0) // ignore this permission
                        if($customer['customer_id']==$quote['customer_id']){
                            $has_quote_access = true;
                            break;
                        }
                    }*/
					unset( $customers );
					if ( ! $has_quote_access ) {
						if ( $skip_permissions ) {
							$quote['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
						} else {
							$quote = false;
						}
					}
					break;
			}
			if ( ! $quote ) {
				$quote = array();
				if ( function_exists( 'hook_filter_var' ) ) {
					$quote = hook_filter_var( 'get_quote', $quote, $quote_id );
				}

				return $quote;
			}
			$quote['taxes'] = get_multiple( 'quote_tax', array( 'quote_id' => $quote_id ), 'quote_tax_id', 'exact', 'order' );
		}
		if ( ! $full ) {
			if ( isset( $cache_key ) ) {
				module_cache::put( 'quote', $cache_key, $quote, $cache_timeout );
			}
			if ( function_exists( 'hook_filter_var' ) ) {
				$quote = hook_filter_var( 'get_quote', $quote, $quote_id );
			}

			return $quote;
		}
		if ( ! $quote ) {
			$customer_id = 0;
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] ) {
				//
				$customer_id = (int) $_REQUEST['customer_id'];
				// find default website id to use.
				if ( isset( $_REQUEST['website_id'] ) ) {
					$website_id = (int) $_REQUEST['website_id'];
				} else {

				}
			}

			$ucmquote              = new UCMQuote();
			$ucmquote->customer_id = $customer_id;
			$default_quote_name    = $ucmquote->get_new_document_number();

			$quote = array(
				'quote_id'             => 'new',
				'customer_id'          => $customer_id,
				'website_id'           => ( isset( $_REQUEST['website_id'] ) ? $_REQUEST['website_id'] : 0 ),
				'hourly_rate'          => module_customer::c( 'hourly_rate', 60, $customer_id ),
				'name'                 => $default_quote_name,
				'date_create'          => date( 'Y-m-d' ),
				'date_approved'        => '0000-00-00',
				'approved_by'          => '',
				'user_id'              => module_security::get_loggedin_id(),
				'contact_user_id'      => - 1, // default primary contact
				'status'               => module_config::s( 'quote_status_default', 'New' ),
				'tax_type'             => module_config::c( 'invoice_tax_type', 0 ), // 0 = added, 1 = included
				'type'                 => module_config::s( 'quote_type_default', 'Website Design' ),
				'currency_id'          => module_config::c( 'default_currency_id', 1 ),
				'auto_task_numbers'    => '0',
				'default_task_type'    => module_config::c( 'default_task_type', _TASK_TYPE_HOURS_AMOUNT ), //
				'description'          => '',
				'discount_description' => _l( 'Discount:' ),
				'discount_amount'      => 0,
				'discount_type'        => module_config::c( 'invoice_discount_type', _DISCOUNT_TYPE_BEFORE_TAX ),
			);
			// some defaults from the db.
			$quote['total_tax_rate'] = module_config::c( 'tax_percent', 10 );
			$quote['total_tax_name'] = module_config::c( 'tax_name', 'TAX' );
			if ( $customer_id > 0 ) {
				$customer_data = module_customer::get_customer( $customer_id, false, true );
				if ( $customer_data && isset( $customer_data['default_tax'] ) && $customer_data['default_tax'] >= 0 ) {
					$quote['total_tax_rate'] = $customer_data['default_tax'];
					$quote['total_tax_name'] = $customer_data['default_tax_name'];
				}
			}
		}
		// new support for multiple taxes
		if ( ! isset( $quote['taxes'] ) || ( ! count( $quote['taxes'] ) && $quote['total_tax_rate'] > 0 ) ) {
			$quote['taxes'] = array();
			$tax_rates      = explode( ',', $quote['total_tax_rate'] );
			$tax_names      = explode( ',', $quote['total_tax_name'] );
			foreach ( $tax_rates as $tax_rate_id => $tax_rate_amount ) {
				if ( $tax_rate_amount > 0 ) {
					$quote['taxes'][] = array(
						'order'     => 0,
						'percent'   => $tax_rate_amount,
						'name'      => isset( $tax_names[ $tax_rate_id ] ) ? $tax_names[ $tax_rate_id ] : $quote['total_tax_name'],
						'total'     => 0,
						// original value that tax was calculated againt
						'amount'    => 0,
						// final amount of calculated tax
						'discount'  => 0,
						// if any discounts are applied to taxes, add them here. this is used in a complicated hack back in job.php to work out new job prices.
						'increment' => module_config::c( 'tax_multiple_increment', 0 ),
						//todo: db this option
					);
				}
			}
		}

		if ( $quote ) {
			// work out total hours etc..
			$quote['total_hours']                         = 0;
			$quote['total_hours_completed']               = 0;
			$quote['total_hours_overworked']              = 0;
			$quote['total_sub_amount']                    = 0;
			$quote['total_sub_amount_taxable']            = 0;
			$quote['total_sub_amount_unbillable']         = 0;
			$quote['total_sub_amount_invoicable']         = 0;
			$quote['total_sub_amount_invoicable_taxable'] = 0;
			$quote['total_amount_invoicable']             = 0;
			$quote['total_tasks_remain']                  = 0;

			$quote['total_amount']                  = 0;
			$quote['total_amount_paid']             = 0;
			$quote['total_amount_invoiced']         = 0;
			$quote['total_amount_invoiced_deposit'] = 0;
			$quote['total_amount_todo']             = 0;
			$quote['total_amount_outstanding']      = 0;
			$quote['total_amount_due']              = 0;
			$quote['total_hours_remain']            = 0;
			$quote['total_percent_complete']        = 0;

			$quote['total_tax']            = 0;
			$quote['total_tax_invoicable'] = 0;

			//            $quote['invoice_discount_amount'] = 0;
			//            $quote['invoice_discount_amount_on_tax'] = 0;
			//            $quote['total_amount_discounted'] = 0;

			// new feature to invoice incompleted tasks
			$quote['uninvoiced_quote_task_ids'] = array();

			$quote_items = self::get_quote_items( (int) $quote['quote_id'], $quote );
			foreach ( $quote_items as $quote_item ) {
				if ( $quote_item['quote_item_amount'] != 0 ) {
					// we have a custom amount for this quote_item
					if ( $quote_item['billable'] ) {
						$quote['total_sub_amount'] += $quote_item['quote_item_amount'];
						if ( $quote_item['taxable'] ) {
							$quote['total_sub_amount_taxable'] += $quote_item['quote_item_amount'];
							if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
								// tax calculated along the way (this isn't the recommended way, but was included as a feature request)
								// we add tax to each of the tax array items
								//$quote['total_tax'] += round(($quote_item['quote_item_amount'] * ($quote['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
								foreach ( $quote['taxes'] as $quote_tax_id => $quote_tax ) {
									if ( ! isset( $quote['taxes'][ $quote_tax_id ]['total'] ) ) {
										$quote['taxes'][ $quote_tax_id ]['total'] = 0;
									}
									$quote['taxes'][ $quote_tax_id ]['total']  += $quote_item['quote_item_amount'];
									$quote['taxes'][ $quote_tax_id ]['amount'] += round( ( $quote_item['quote_item_amount'] * ( $quote_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
								}
							}
						}
					} else {
						$quote['total_sub_amount_unbillable'] += $quote_item['quote_item_amount'];
					}
				}
			}

			// add any discounts.
			if ( $quote['discount_amount'] != 0 ) {
				if ( $quote['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
					// after tax discount ::::::::::
					// handled below.
					//$quote['final_modification'] = -$quote['discount_amount'];
				} else if ( $quote['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
					// before tax discount:::::
					//$quote['final_modification'] = -$quote['discount_amount'];
					// problem : this 'discount_amount_on_tax' calculation may not match the correct final discount calculation as per below
					if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
						// tax calculated along the way.
						// we have discounted the 'total amount taxable' so that means we need to reduce the tax amount by that much as well.
						foreach ( $quote['taxes'] as $quote_tax_id => $quote_tax ) {
							$this_tax_discount               = round( ( $quote['discount_amount'] * ( $quote['taxes'][ $quote_tax_id ]['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
							$quote['discount_amount_on_tax'] += $this_tax_discount;
							if ( ! isset( $quote['taxes'][ $quote_tax_id ]['total'] ) ) {
								$quote['taxes'][ $quote_tax_id ]['total'] = 0;
							}
							$quote['taxes'][ $quote_tax_id ]['total']    -= $quote['discount_amount'];
							$quote['taxes'][ $quote_tax_id ]['amount']   -= $this_tax_discount;
							$quote['taxes'][ $quote_tax_id ]['discount'] = $this_tax_discount;
						}
					} else {

						// we work out what the tax would have been if there was no applied discount
						// this is used in job.php
						$quote['taxes_backup']                    = $quote['taxes'];
						$quote['total_sub_amount_taxable_backup'] = $quote['total_sub_amount_taxable'];
						$total_tax_before_discount                = 0;
						foreach ( $quote['taxes'] as $quote_tax_id => $quote_tax ) {
							$quote['taxes'][ $quote_tax_id ]['total']  = $quote['total_sub_amount_taxable'];
							$quote['taxes'][ $quote_tax_id ]['amount'] = round( ( $quote['total_sub_amount_taxable'] * ( $quote_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
							// here we adjust the 'total_sub_amount_taxable' to include the value from the previous calculation.
							// this is for multiple taxes that addup as they go (eg: Canada)
							if ( isset( $quote_tax['increment'] ) && $quote_tax['increment'] ) {
								$quote['total_sub_amount_taxable'] += $quote['taxes'][ $quote_tax_id ]['amount'];
							}
							$total_tax_before_discount += $quote['taxes'][ $quote_tax_id ]['amount'];
						}
						$quote['taxes']                    = $quote['taxes_backup'];
						$quote['total_sub_amount_taxable'] = $quote['total_sub_amount_taxable_backup'];
					}
					$quote['total_sub_amount']         -= $quote['discount_amount'];
					$quote['total_sub_amount_taxable'] -= $quote['discount_amount'];
				}
			}

			if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_AT_END ) {
				// tax needs to be calculated based on the total_sub_amount_taxable
				$previous_quote_tax_id = false;
				foreach ( $quote['taxes'] as $quote_tax_id => $quote_tax ) {
					$quote['taxes'][ $quote_tax_id ]['total'] = $quote['total_sub_amount_taxable'];
					if ( isset( $quote_tax['increment'] ) && $quote_tax['increment'] && $previous_quote_tax_id ) {
						$quote['taxes'][ $quote_tax_id ]['total'] += $quote['taxes'][ $previous_quote_tax_id ]['amount'];
					}
					$quote['taxes'][ $quote_tax_id ]['amount'] = round( ( $quote['taxes'][ $quote_tax_id ]['total'] * ( $quote_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
					// here we adjust the 'total_sub_amount_taxable' to include the value from the previous calculation.
					// this is for multiple taxes that addup as they go (eg: Canada)
					$previous_quote_tax_id = $quote_tax_id;
				}
				//$quote['total_tax'] = round(($quote['total_sub_amount_taxable'] * ($quote['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
			} else {
				//$quote['total_tax'] = 0;
			}
			if ( isset( $quote['tax_type'] ) && $quote['tax_type'] == 1 ) {
				// hack! not completely correct, oh well.
				// todo - make this work with more than 1 tax rate.
				// $amount / 1.05  ( this is 1 + tax %)
				// this will only work if a single tax has been included.
				if ( is_array( $quote['taxes'] ) && count( $quote['taxes'] ) > 1 ) {
					set_error( 'Included tax calculation only works with 1 tax rate' );
				} else if ( is_array( $quote['taxes'] ) && count( $quote['taxes'] ) ) {
					reset( $quote['taxes'] );
					$quote_tax_id = key( $quote['taxes'] );
					if ( isset( $quote['taxes'][ $quote_tax_id ] ) ) {
						$taxable_amount                            = $quote['total_sub_amount_taxable'] / ( 1 + ( $quote['taxes'][ $quote_tax_id ]['percent'] / 100 ) );
						$quote['taxes'][ $quote_tax_id ]['amount'] = $quote['total_sub_amount_taxable'] - $taxable_amount;
						$quote['total_sub_amount']                 = $quote['total_sub_amount'] - $quote['taxes'][ $quote_tax_id ]['amount'];
					}

				}
			}
			$quote['total_tax'] = 0;
			foreach ( $quote['taxes'] as $quote_tax_id => $quote_tax ) {
				$quote['total_tax'] += $quote_tax['amount'];
			}
			$quote['total_amount'] = $quote['total_sub_amount'] + $quote['total_tax'];
			if ( $quote['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
				$quote['total_amount'] -= $quote['discount_amount'];
			}
			$quote['total_amount'] = round( $quote['total_amount'], module_config::c( 'currency_decimal_places', 2 ) );


		}
		if ( isset( $cache_key ) ) {
			module_cache::put( 'quote', $cache_key, $quote, $cache_timeout );
		}
		if ( function_exists( 'hook_filter_var' ) ) {
			$quote = hook_filter_var( 'get_quote', $quote, $quote_id );
		}

		return $quote;
	}

	public static function duplicate_quote( $quote_id ) {
		$new_quote_id = false;
		$quote_data   = self::get_quote( $quote_id, true );
		// duplicate data from quote, quote_tax and quote_task tables
		unset( $quote_data['quote_id'] );
		unset( $quote_data['date_approved'] );
		unset( $quote_data['approved_by'] );
		$quote_data['name'] = '(dup) ' . $quote_data['name'];
		$new_quote_id       = update_insert( 'quote_id', false, 'quote', $quote_data );
		if ( $new_quote_id ) {
			foreach ( get_multiple( 'quote_tax', array( 'quote_id' => $quote_id ) ) as $quote_tax ) {
				$quote_tax['quote_id'] = $new_quote_id;
				update_insert( 'quote_tax_id', false, 'quote_tax', $quote_tax );
			}
			foreach ( get_multiple( 'quote_task', array( 'quote_id' => $quote_id ) ) as $quote_task ) {
				$quote_task['quote_id'] = $new_quote_id;
				update_insert( 'quote_task_id', false, 'quote_task', $quote_task );
			}
		}

		return $new_quote_id;
	}

	public static function save_quote( $quote_id, $data ) {


		if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
			// check we have access to this customer from this quote.
			$customer_check = module_customer::get_customer( $data['customer_id'] );
			if ( ! $customer_check || $customer_check['customer_id'] != $data['customer_id'] ) {
				unset( $data['customer_id'] );
			}
		}
		if ( isset( $data['website_id'] ) && $data['website_id'] ) {
			$website = module_website::get_website( $data['website_id'] );
			if ( $website && (int) $website['website_id'] > 0 && $website['website_id'] == $data['website_id'] ) {
				// website exists.
				// make this one match the website customer_id, or set teh website customer_id if it doesn't have any.
				if ( (int) $website['customer_id'] > 0 ) {
					if ( $data['customer_id'] > 0 && $data['customer_id'] != $website['customer_id'] ) {
						set_message( 'Changed this Quote to match the Website customer' );
					}
					$data['customer_id'] = $website['customer_id'];
				} else if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
					// set the website customer id to this as well.
					update_insert( 'website_id', $website['website_id'], 'website', array( 'customer_id' => $data['customer_id'] ) );
				}
			}
		}
		if ( (int) $quote_id > 0 ) {
			$original_quote_data = self::get_quote( $quote_id, false );
			if ( ! $original_quote_data || $original_quote_data['quote_id'] != $quote_id ) {
				$original_quote_data = array();
				$quote_id            = false;
			}
		} else {
			$original_quote_data = array();
			$quote_id            = false;
		}

		// check create permissions.
		if ( ! $quote_id && ! self::can_i( 'create', 'Quotes' ) ) {
			// user not allowed to create quotes.
			set_error( 'Unable to create new Quotes' );
			redirect_browser( self::link_open( false ) );
		}

		$quote_id = update_insert( "quote_id", $quote_id, "quote", $data );
		$return   = false;
		if ( $quote_id ) {
			hook_handle_callback( 'quote_save', $quote_id );

			// save the quote tax rates (copied from invoice.php)
			if ( isset( $data['tax_ids'] ) && isset( $data['tax_names'] ) && $data['tax_percents'] ) {
				$existing_taxes = get_multiple( 'quote_tax', array( 'quote_id' => $quote_id ), 'quote_tax_id', 'exact', 'order' );
				$order          = 1;
				foreach ( $data['tax_ids'] as $key => $val ) {
					//if(isset($data['tax_percents'][$key]) && $data['tax_percents'][$key] == 0){
					// we are not saving this particular tax item because it has a 0% tax rate
					//}else{
					if ( (int) $val > 0 && isset( $existing_taxes[ $val ] ) ) {
						// this means we are trying to update an existing record on the quote_tax table, we confirm this id matches this quote.
						$quote_tax_id = $val;
						unset( $existing_taxes[ $quote_tax_id ] ); // so we know which ones to remove from the end.
					} else {
						$quote_tax_id = false; // create new record
					}
					$quote_tax_data = array(
						'quote_id'  => $quote_id,
						'percent'   => isset( $data['tax_percents'][ $key ] ) ? $data['tax_percents'][ $key ] : 0,
						'amount'    => 0, // calculate this where? nfi? maybe on final quote get or something.
						'name'      => isset( $data['tax_names'][ $key ] ) ? $data['tax_names'][ $key ] : 'TAX',
						'order'     => $order ++,
						'increment' => isset( $data['tax_increment_checkbox'] ) && $data['tax_increment_checkbox'] ? 1 : 0,
					);
					$quote_tax_id   = update_insert( 'quote_tax_id', $quote_tax_id, 'quote_tax', $quote_tax_data );
					//}
				}
				foreach ( $existing_taxes as $existing_tax ) {
					delete_from_db( 'quote_tax', array( 'quote_id', 'quote_tax_id' ), array(
						$quote_id,
						$existing_tax['quote_tax_id']
					) );
				}
			}


			module_cache::clear( 'quote' );
			$return          = array(
				'quote_id'    => $quote_id,
				'task_result' => self::save_quote_tasks( $quote_id, $data ),
			);
			$check_completed = true;
			switch ( $return['task_result']['status'] ) {
				case 'created':
					// we added a new task.

					break;
				case 'deleted':
					// we deleted a task.

					break;
				case 'edited':
					// we changed a task (ie: completed?);

					break;
				default:
					// nothing changed.
					// $check_completed = false;
					break;
			}
			if ( $check_completed ) {
				self::update_quote_completion_status( $quote_id );
			}
			if ( $original_quote_data ) {
				// we check if the hourly rate has changed
				if ( isset( $data['hourly_rate'] ) && $data['hourly_rate'] != $original_quote_data['hourly_rate'] ) {
					// update all the task hours, but only for hourly tasks:
					$sql = "UPDATE `" . _DB_PREFIX . "quote_task` SET `amount` = 0 WHERE `hours` > 0 AND quote_id = " . (int) $quote_id . " AND ( manual_task_type = " . _TASK_TYPE_HOURS_AMOUNT;
					if ( $data['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
						$sql .= " OR manual_task_type = -1 ";
					}
					$sql .= " )";
					query( $sql );

				}
				// check if the quote assigned user id has changed.
				if ( module_config::c( 'quote_allow_staff_assignment', 1 ) ) {
					if ( isset( $data['user_id'] ) ) { // && $data['user_id'] != $original_quote_data['user_id']){
						// user id has changed! update any that were the old user id.
						$sql = "UPDATE `" . _DB_PREFIX . "quote_task` SET `user_id` = " . (int) $data['user_id'] .
						       " WHERE (`user_id` = " . (int) $original_quote_data['user_id'] . " OR user_id = 0) AND quote_id = " . (int) $quote_id;
						query( $sql );
					}
				}
				// check if the quote was approved.
				if ( ! isset( $original_quote_data['date_approved'] ) || ! $original_quote_data['date_approved'] || $original_quote_data['date_approved'] == '0000-00-00' ) {
					// original quote wasn't approved.
					if ( isset( $data['date_approved'] ) && ! empty( $data['date_approved'] ) && $data['date_approved'] != '0000-00-00' ) {
						// quote was approved!
						self::quote_approved( $quote_id );
					}
				}
			}

		}
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::save_extras( 'quote', 'quote_id', $quote_id );
		}
		module_cache::clear( 'quote' );

		return $return;
	}

	public static function quote_approved( $quote_id ) {
		module_cache::clear( 'quote' );
		$quote_data = module_quote::get_quote( $quote_id );
		hook_handle_callback( 'quote_approved', $quote_id );
		self::add_history( $quote_id, 'Quote approved by ' . $quote_data['approved_by'] );
		if ( module_config::c( 'quote_approval_auto_email', 1 ) && $quote_data['user_id'] ) {
			// send an email to the assigned staff member letting them know the quote was approved.
			$template = module_template::get_template_by_key( 'quote_approved_email' );
			$replace  = module_quote::get_replace_fields( $quote_id, $quote_data );

			if ( defined( '_BLOCK_EMAILS' ) && _BLOCK_EMAILS ) {
				$pdf = false;
			} else {
				$pdf = module_quote::generate_pdf( $quote_id );
			}

			$template->assign_values( $replace );
			$html = $template->render( 'html' );
			// send an email to this user.
			$email                 = module_email::new_email();
			$email->replace_values = $replace;
			$email->set_to( 'user', $quote_data['user_id'] );
			$email->set_bcc_manual( module_config::c( 'admin_email_address', '' ), '' );
			//$email->set_from('user',); // nfi
			$email->set_subject( $template->description );
			// do we send images inline?
			$email->set_html( $html );
			if ( $pdf ) {
				$email->add_attachment( $pdf );
			}
			$email->quote_id           = $quote_id;
			$email->customer_id        = $quote_data['customer_id'];
			$email->prevent_duplicates = true;
			if ( $email->send() ) {
				// it worked successfully!!
				// record a log on the quote when it's done.
				self::add_history( $quote_id, _l( 'Quote approval emailed to staff member' ) );
			} else {
				/// log err?
			}
		}
		module_cache::clear( 'quote' );
	}

	public static function email_sent( $quote_id, $template_name ) {
		// add sent date if it doesn't exist
		self::add_history( $quote_id, _l( 'Quote emailed to customer successfully' ) );
	}

	public static function staff_email_sent( $options ) {
		$quote_id = (int) $options['quote_id'];
		// add sent date if it doesn't exist
		self::add_history( $quote_id, _l( 'Quote emailed to staff successfully' ) );
	}

	public static function add_history( $quote_id, $message ) {
		module_note::save_note( array(
			'owner_table' => 'quote',
			'owner_id'    => $quote_id,
			'note'        => $message,
			'rel_data'    => self::link_open( $quote_id ),
			'note_time'   => time(),
		) );
	}

	private static function save_quote_tasks( $quote_id, $data ) {

		$result          = array(
			'status' => false,
		);
		$check_completed = false;

		$quote_data = false;

		// check for new tasks or changed tasks.
		$tasks = self::get_tasks( $quote_id );
		if ( isset( $data['quote_task'] ) && is_array( $data['quote_task'] ) ) {
			foreach ( $data['quote_task'] as $quote_task_id => $task_data ) {

				if ( isset( $task_data['manual_percent'] ) && strlen( $task_data['manual_percent'] ) == 0 ) {
					unset( $task_data['manual_percent'] );
				}

				$original_quote_task_id = $quote_task_id;
				$quote_task_id          = (int) $quote_task_id;
				if ( ! is_array( $task_data ) ) {
					continue;
				}
				if ( $quote_task_id > 0 && ! isset( $tasks[ $quote_task_id ] ) ) {
					$quote_task_id = 0; // creating a new task on this quote.
				}
				if ( ! isset( $task_data['description'] ) || $task_data['description'] == '' || $task_data['description'] == _TASK_DELETE_KEY ) {
					if ( $quote_task_id > 0 && $task_data['description'] == _TASK_DELETE_KEY ) {
						// remove task.
						// but onyl remove it if it hasn't been invoiced.
						$sql = "DELETE FROM `" . _DB_PREFIX . "quote_task` WHERE quote_task_id = '$quote_task_id' AND quote_id = $quote_id LIMIT 1";
						query( $sql );
						$result['status']        = 'deleted';
						$result['quote_task_id'] = $quote_task_id;
					}
					continue;
				}
				// add / save this task.
				$task_data['quote_id'] = $quote_id;
				$task_data['hours']    = isset( $task_data['hours'] ) ? ( function_exists( 'decimal_time_in' ) ? decimal_time_in( $task_data['hours'] ) : $task_data['hours'] ) : 0;
				// remove the amount of it equals the hourly rate.
				if ( isset( $task_data['amount'] ) && $task_data['amount'] != 0 && isset( $task_data['hours'] ) && $task_data['hours'] > 0 ) {
					if ( isset( $data['hourly_rate'] ) && ( $task_data['amount'] - ( $task_data['hours'] * $data['hourly_rate'] ) == 0 ) ) {
						unset( $task_data['amount'] );
					}
				}
				// check if we haven't unticked a non-hourly task
				// check if we haven't unticked a billable task
				if ( isset( $task_data['billable_t'] ) && $task_data['billable_t'] && ! isset( $task_data['billable'] ) ) {
					$task_data['billable'] = 0;
				}
				// set default taxable status
				if ( ! isset( $task_data['taxable_t'] ) ) {
					// we're creating a new task.
					$task_data['taxable'] = module_config::c( 'task_taxable_default', 1 );
				}
				if ( isset( $task_data['taxable_t'] ) && $task_data['taxable_t'] && ! isset( $task_data['taxable'] ) ) {
					$task_data['taxable'] = 0;
				}

				// todo: move the task creation code into a public method so that the public user can add tasks to their quotes.
				if ( ! $quote_task_id && module_security::is_logged_in() && ! module_quote::can_i( 'create', 'Quote Tasks' ) ) {
					continue; // dont allow new tasks.
				}

				// check if the user is allowed to create new tasks.

				$quote_task_id           = update_insert( 'quote_task_id', $quote_task_id, 'quote_task', $task_data ); // todo - fix cross task quote boundary issue. meh.
				$result['quote_task_id'] = $quote_task_id;
				if ( $quote_task_id != $original_quote_task_id ) {
					$result['status'] = 'created';
				} else {
					$result['status'] = 'edited';
				}

			}
		}

		if ( $check_completed ) {
			self::update_quote_completion_status( $quote_id );
		}
		module_cache::clear( 'quote' );

		return $result;
	}

	public static function delete_quote( $quote_id ) {
		$quote_id = (int) $quote_id;
		if ( _DEMO_MODE && $quote_id == 1 ) {
			return;
		}

		if ( (int) $quote_id > 0 ) {
			$original_quote_data = self::get_quote( $quote_id );
			if ( ! $original_quote_data || $original_quote_data['quote_id'] != $quote_id ) {
				return false;
			}
		} else {
			return false;
		}

		if ( ! self::can_i( 'delete', 'Quotes' ) ) {
			return false;
		}

		$sql = "DELETE FROM " . _DB_PREFIX . "quote WHERE quote_id = '" . $quote_id . "' LIMIT 1";
		$res = query( $sql );
		$sql = "DELETE FROM " . _DB_PREFIX . "quote_tax WHERE quote_id = '" . $quote_id . "'";
		$res = query( $sql );
		$sql = "DELETE FROM " . _DB_PREFIX . "quote_task WHERE quote_id = '" . $quote_id . "'";
		$res = query( $sql );
		if ( class_exists( 'module_file', false ) ) {
			$sql = "UPDATE " . _DB_PREFIX . "file SET quote_id = 0 WHERE quote_id = '" . $quote_id . "'";
			query( $sql );
		}

		if ( class_exists( 'module_group', false ) ) {
			module_group::delete_member( $quote_id, 'quote' );
		}
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::note_delete( "quote", $quote_id );
		}
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::delete_extras( 'quote', 'quote_id', $quote_id );
		}

		hook_handle_callback( 'quote_delete', $quote_id );
		module_cache::clear( 'quote' );
	}

	public function login_link( $quote_id ) {
		return module_security::generate_auto_login_link( $quote_id );
	}

	public static function get_statuses() {
		$sql      = "SELECT `status` FROM `" . _DB_PREFIX . "quote` GROUP BY `status` ORDER BY `status`";
		$statuses = module_job::get_statuses();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['status'] ] = $r['status'];
		}

		return $statuses;
	}

	public static function get_types() {
		$sql      = "SELECT `type` FROM `" . _DB_PREFIX . "quote` GROUP BY `type` ORDER BY `type`";
		$statuses = module_job::get_types();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['type'] ] = $r['type'];
		}

		return $statuses;
	}


	public static function customer_id_changed( $old_customer_id, $new_customer_id ) {
		$old_customer_id = (int) $old_customer_id;
		$new_customer_id = (int) $new_customer_id;
		if ( $old_customer_id > 0 && $new_customer_id > 0 ) {
			$sql = "UPDATE `" . _DB_PREFIX . "quote` SET customer_id = " . $new_customer_id . " WHERE customer_id = " . $old_customer_id;
			query( $sql );
			module_invoice::customer_id_changed( $old_customer_id, $new_customer_id );
			module_file::customer_id_changed( $old_customer_id, $new_customer_id );
		}
	}


	public static function get_quote_access_permissions() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Quote Data Access', array(
				_QUOTE_ACCESS_ALL,
				_QUOTE_ACCESS_ASSIGNED,
				_QUOTE_ACCESS_CUSTOMER,
			) );
		} else {
			return _QUOTE_ACCESS_ALL; // default to all permissions.
		}
	}

	public static function get_quote_task_access_permissions() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Quote Task Data Access', array(
				_QUOTE_TASK_ACCESS_ALL,
				_QUOTE_TASK_ACCESS_ASSIGNED_ONLY,
			) );
		} else {
			return _QUOTE_TASK_ACCESS_ALL; // default to all permissions.
		}
	}


	public static function handle_import_row_debug( $row, $add_to_group, $extra_options ) {
		return self::handle_import_row( $row, true, $add_to_group, $extra_options );
	}

	/* Quote Title	Hourly Rate	Start Date	Due Date	Completed Date	Website Name	Customer Name	Type	Status	Staff Member	Tax Name	Tax Percent	Renewal Date */
	public static function handle_import_row( $row, $debug, $add_to_group, $extra_options ) {

		$debug_string = '';

		if ( isset( $row['quote_id'] ) && (int) $row['quote_id'] > 0 ) {
			// check if this ID exists.
			$quote = self::get_quote( $row['quote_id'] );
			if ( ! $quote || $quote['quote_id'] != $row['quote_id'] ) {
				$row['quote_id'] = 0;
			}
		}
		if ( ! isset( $row['quote_id'] ) || ! $row['quote_id'] ) {
			$row['quote_id'] = 0;
		}
		if ( ! isset( $row['name'] ) || ! strlen( $row['name'] ) ) {
			$debug_string .= _l( 'No quote data to import' );
			if ( $debug ) {
				echo $debug_string;
			}

			return false;
		}
		// duplicates.
		//print_r($extra_options);exit;
		if ( isset( $extra_options['duplicates'] ) && $extra_options['duplicates'] == 'ignore' && (int) $row['quote_id'] > 0 ) {
			if ( $debug ) {
				$debug_string .= _l( 'Skipping import, duplicate of quote %s', self::link_open( $row['quote_id'], true ) );
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
		if ( $row['quote_id'] ) {
			$debug_string .= _l( 'Replace existing quote: %s', self::link_open( $row['quote_id'], true ) ) . ' ';
		} else {
			$debug_string .= _l( 'Insert new quote: %s', htmlspecialchars( $row['name'] ) ) . ' ';
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

		}
		$quote_id = (int) $row['quote_id'];
		// check if this ID exists.
		$quote = self::get_quote( $quote_id );
		if ( ! $quote || $quote['quote_id'] != $quote_id ) {
			$quote_id = 0;
		}
		$quote_id = update_insert( "quote_id", $quote_id, "quote", $row );

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
					'owner_table' => 'quote',
					'owner_id'    => $quote_id,
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
					'owner_table' => 'quote',
					'owner_id'    => $quote_id,
				);
				$extra_id = (int) $extra_id;
				update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
			}
		}

		foreach ( $add_to_group as $group_id => $tf ) {
			module_group::add_to_group( $group_id, $quote_id, 'quote' );
		}

		return $quote_id;

	}

	public static function handle_import( $data, $add_to_group, $extra_options ) {

		// woo! we're doing an import.
		$count = 0;
		// first we find any matching existing quotes. skipping duplicates if option is set.
		foreach ( $data as $rowid => $row ) {
			if ( self::handle_import_row( $row, false, $add_to_group, $extra_options ) ) {
				$count ++;
			}
		}

		return $count;


	}

	public static function handle_import_tasks( $data, $add_to_group ) {

		$import_options = json_decode( base64_decode( $_REQUEST['import_options'] ), true );
		$quote_id       = (int) $import_options['quote_id'];
		if ( ! $import_options || ! is_array( $import_options ) || $quote_id <= 0 ) {
			echo 'Sorry import failed. Please try again';
			exit;
		}
		$existing_tasks = self::get_tasks( $quote_id );
		$existing_staff = module_user::get_staff_members();


		// woo! we're doing an import.
		// make sure we have a quote id


		foreach ( $data as $rowid => $row ) {
			$row['quote_id'] = $quote_id;
			// check for required fields
			if ( ! isset( $row['description'] ) || ! trim( $row['description'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['quote_task_id'] ) || ! $row['quote_task_id'] ) {
				$data[ $rowid ]['quote_task_id'] = 0;
			}
			// make sure this task id exists in the system against this quote.
			if ( $data[ $rowid ]['quote_task_id'] > 0 ) {
				if ( ! isset( $existing_tasks[ $data[ $rowid ]['quote_task_id'] ] ) ) {
					$data[ $rowid ]['quote_task_id'] = 0; // create a new task.
					// this stops them updating a task in another quote.
				}
			}
			if ( ! $data[ $rowid ]['quote_task_id'] && $row['description'] ) {
				// search for a task based on this name. dont want duplicates in the system.
				$existing_task = get_single( 'quote_task', array( 'quote_id', 'description' ), array(
					$quote_id,
					$row['description']
				) );
				if ( $existing_task ) {
					$data[ $rowid ]['quote_task_id'] = $existing_task['quote_task_id'];
				}
			}

			// we have to save the user_name specially.
			/*if(isset($row['user_name']) && $row['user_name']){
                // see if this staff member exists.
                foreach($existing_staff as $staff_member){
                    if(strtolower($staff_member['name']) == strtolower($row['user_name'])){
                        $data[$rowid]['user_id'] = $staff_member['user_id'];
                    }
                }
            }*/

		}
		$c         = 0;
		$task_data = array();
		foreach ( $data as $rowid => $row ) {
			// now save the data.

			// we specify a "log_hours" value if we are logging more hours on a specific task.
			if ( isset( $row['completed'] ) && $row['completed'] > 0 && isset( $row['hours'] ) && $row['hours'] != 0 ) {
				if ( $row['quote_task_id'] == 0 ) {
					// we are logging hours against a new task
					$row['log_hours'] = $row['completed'];
				} else if ( $row['quote_task_id'] > 0 ) {
					// we are adjusting hours on an existing task.
					$existing_completed_hours = $existing_tasks[ $row['quote_task_id'] ]['completed'];
					if ( $row['completed'] > $existing_completed_hours ) {
						// we are logging additional hours against the quote.
						$row['log_hours'] = $row['completed'] - $existing_completed_hours;
					} else if ( $row['completed'] < $existing_completed_hours ) {
						// we are removing hours on this task!
						// tricky!!
						$sql = "DELETE FROM `" . _DB_PREFIX . "task_log` WHERE quote_task_id = " . (int) $row['quote_task_id'];
						query( $sql );
						$row['log_hours'] = $row['completed'];
					}
				}
			}

			if ( $row['quote_task_id'] > 0 ) {
				$quote_task_id = $row['quote_task_id'];
			} else {
				$quote_task_id = 'new' . $c . 'new';
				$c ++;
			}

			$task_data[ $quote_task_id ] = $row;

			/*foreach($add_to_group as $group_id => $tf){
                module_group::add_to_group($group_id,$quote_task_id,'task');
            }*/

		}

		self::save_quote( $quote_id, array(
			'quote_id'   => $quote_id,
			'quote_task' => $task_data,
		) );


	}

	public static function generate_task_preview( $quote_id, $quote, $quote_task_id, $task_data, $task_editable = true, $unit_measurement = false ) {

		ob_start();
		// can we edit this task?
		// if its been invoiced we cannot edit it.

		// todo-move this into a method so we can update it via ajax.

		/*if($task_data['hours'] <= 0 && $task_data['fully_completed']){
            $percentage = 1;
        }else if ($task_data['completed'] > 0) {
            if($task_data['hours'] > 0){
                $percentage = round($task_data['completed'] / $task_data['hours'],2);
                $percentage = min(1,$percentage);
            }else{
                $percentage = 1;
            }
        }else{
            $percentage = 0;
        }*/

		$show_task_numbers = ( module_config::c( 'quote_show_task_numbers', 1 ) && $quote['auto_task_numbers'] != 2 );


		$staff_members    = module_user::get_staff_members();
		$staff_member_rel = array();
		foreach ( $staff_members as $staff_member ) {
			$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
		}

		// we pull in product data
		if ( is_callable( 'module_product::sanitise_product_name' ) ) {
			$task_data = module_product::sanitise_product_name( $task_data, $quote['default_task_type'] );
		}

		// new different formats for quote data.
		if ( ( ! isset( $task_data['manual_task_type'] ) || $task_data['manual_task_type'] < 0 ) && isset( $quote['default_task_type'] ) ) {
			// use the quote task type
			$task_data['manual_task_type'] = $quote['default_task_type'];
		}

		include( 'pages/ajax_task_preview.php' );

		return ob_get_clean();
	}

	public static function get_default_tasks() {
		// we use the extra module for saving default task lists for now
		// why not? meh - use a new table later (similar to ticket default responses)
		$extra_fields = module_extra::get_extras( array( 'owner_table' => 'job_task_defaults', 'owner_id' => 1 ) );
		$responses    = array();
		foreach ( $extra_fields as $extra ) {
			$responses[ $extra['extra_id'] ] = $extra['extra_key'];
		}

		return $responses;
	}

	public static function get_default_task( $default_task_list_id ) {
		$extra = module_extra::get_extra( $default_task_list_id );

		return array(
			'default_task_list_id' => $extra['extra_id'],
			'name'                 => $extra['extra_key'],
			'task_data'            => unserialize( $extra['extra'] ),
		);
	}

	public static function save_default_tasks( $default_task_list_id, $name, $task_data ) {
		if ( (int) $default_task_list_id > 0 && ! count( $task_data ) ) {
			// deleting a task.
			delete_from_db( 'extra', array( 'extra_id', 'owner_table' ), array(
				$default_task_list_id,
				'job_task_defaults'
			) );

			return false;
		} else {
			$extra_db = array(
				'extra'       => serialize( $task_data ),
				'owner_table' => 'job_task_defaults',
				'owner_id'    => 1,
			);
			if ( ! (int) $default_task_list_id ) {
				$extra_db['extra_key'] = $name; // don't update names of previous ones.
			}
			$extra_id = update_insert( 'extra_id', $default_task_list_id, 'extra', $extra_db );

			return $extra_id;
		}
	}


	public static function generate_quote_summary( $quote_id, $quote ) {
		$show_task_numbers = ( module_config::c( 'quote_show_task_numbers', 1 ) && $quote['auto_task_numbers'] != 2 );
		ob_start();
		include( 'pages/ajax_quote_summary.php' );

		return ob_get_clean();
	}

	private static function update_quote_completion_status( $quote_id ) {
		//module_cache::clear_cache();
		module_cache::clear( 'quote' );
		$data = self::get_quote( $quote_id );
		// save our cacheable items
		foreach (
			array(
				'total_amount_invoicable', // in datbase as c_total_amount_invoicable
			) as $cacheable_item
		) {
			if ( isset( $data[ $cacheable_item ] ) ) {
				// cacheable items can be the same name or prefixed with c_
				update_insert( 'quote_id', $quote_id, 'quote', array(
					$cacheable_item     => $data[ $cacheable_item ],
					"c_$cacheable_item" => $data[ $cacheable_item ],
				) );
			}
		}
		$return_status = $data['status'];

		module_cache::clear( 'quote' );

		return $return_status;
	}

	/**
	 * Generate a PDF for the currently load()'d quote
	 * Return the path to the file name for this quote.
	 * @return bool
	 */

	public static function generate_pdf( $quote_id ) {

		if ( ! function_exists( 'convert_html2pdf' ) ) {
			return false;
		}

		$quote_id   = (int) $quote_id;
		$quote_data = self::get_quote( $quote_id );
		$quote_html = self::quote_html( $quote_id, $quote_data, 'pdf' );
		if ( $quote_html ) {
			//echo $quote_html;exit;

			$base_name      = basename( preg_replace( '#[^a-zA-Z0-9_]#', '', module_config::c( 'quote_file_prefix', 'Quote_' ) ) );
			$file_name      = preg_replace( '#[^a-zA-Z0-9]#', '', $quote_data['name'] );
			$html_file_name = _UCM_FILE_STORAGE_DIR . 'temp/' . $base_name . $file_name . '.html';
			$pdf_file_name  = _UCM_FILE_STORAGE_DIR . 'temp/' . $base_name . $file_name . '.pdf';

			file_put_contents( $html_file_name, $quote_html );

			return convert_html2pdf( $html_file_name, $pdf_file_name );


		}

		return false;
	}

	public static function quote_html( $quote_id, $quote_data, $mode = 'html' ) {

		if ( $quote_id && $quote_data ) {
			// spit out the quote html into a file, then pass it to the pdf converter
			// to convert it into a PDF.

			$quote = $quote_data;

			if ( class_exists( 'module_company', false ) && isset( $quote_data['company_id'] ) && (int) $quote_data['company_id'] > 0 ) {
				module_company::set_current_company_id( $quote_data['company_id'] );
			}

			$quote_template = isset( $quote_data['quote_template_print'] ) && strlen( $quote_data['quote_template_print'] ) ? $quote_data['quote_template_print'] : module_config::c( 'quote_template_print_default', 'quote_print' );

			if ( $quote_template == 'quote_pdf' ) {
				$quote_template = 'quote_print';
			}

			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_quote/template/quote_task_list.php' ) );
			$task_list_html = ob_get_clean();

			$replace               = self::get_replace_fields( $quote_id, $quote_data );
			$replace['task_list']  = $task_list_html;
			$replace['quote_link'] = module_quote::link_public( $quote_id );

			$replace['external_quote_template_html'] = '';
			$external_quote_template                 = module_template::get_template_by_key( $quote_template );
			$external_quote_template->assign_values( $replace );
			$replace['external_quote_template_html'] = $external_quote_template->replace_content();

			ob_start();
			$template = module_template::get_template_by_key( $quote_template );
			$template->assign_values( $replace );
			echo $template->render( 'html' );
			$quote_html = ob_get_clean();

			return $quote_html;
		}

		return false;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_quote::can_i( 'view', 'Quotes' ) ) {
			$customer_id = ! empty( $search_options['vars']['lookup_customer_id'] ) ? (int) $search_options['vars']['lookup_customer_id'] : false;

			$res = module_quote::get_quotes( array(
				'generic'     => $search_string,
				'customer_id' => $customer_id,
			), array( 'columns' => 'u.quote_id, u.name' ) );
			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['quote_id'],
					'value' => $row['name']
				);
			}
		}

		return $result;
	}


	public static function hook_filter_custom_data_menu_locations( $call, $menu_locations ) {
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_QUOTE_FOOTER ]  = _l( 'Quote Footer' );
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_QUOTE_SIDEBAR ] = _l( 'Quote Sidebar' );

		return $menu_locations;
	}


	public static function customer_archived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'quote` SET `archived` = 1 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}

	public static function customer_unarchived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'quote` SET `archived` = 0 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}

	public function get_upgrade_sql() {
		$sql = '';

		$fields = get_fields( 'quote' );
		/*if(!isset($fields['auto_task_numbers'])){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'quote` ADD  `auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
        }*/

		if ( ! isset( $fields['approved_by'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `approved_by` varchar(255) NOT NULL DEFAULT \'\' AFTER `date_approved`;';
		}
		if ( ! isset( $fields['discount_amount'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT \'0\' AFTER `description`;';
		}
		if ( ! isset( $fields['discount_description'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `discount_description` varchar(255) NOT NULL DEFAULT \'\' AFTER `discount_amount`;';
		}
		if ( ! isset( $fields['discount_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `discount_type` INT NOT NULL DEFAULT \'0\' AFTER `discount_description`;';
		}
		if ( ! isset( $fields['contact_user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `contact_user_id` INT NOT NULL DEFAULT \'-1\' AFTER `user_id`;';
		}
		if ( ! isset( $fields['archived'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `archived` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `contact_user_id`;';
		}
		if ( ! isset( $fields['billing_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote` ADD `billing_type` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `archived`;';
		}

		if ( ! isset( $fields['quote_template_print'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'quote` ADD `quote_template_print` varchar(50) NOT NULL DEFAULT \'\' AFTER `archived`;';
		}
		if ( ! isset( $fields['quote_template_email'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'quote` ADD `quote_template_email` varchar(50) NOT NULL DEFAULT \'\' AFTER `archived`;';
		}
		if ( ! isset( $fields['quote_template_external'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'quote` ADD `quote_template_external` varchar(50) NOT NULL DEFAULT \'\' AFTER `archived`;';
		}
		if ( ! isset( $fields['calendar_show'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'quote` ADD  `calendar_show` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `archived`;';
		}


		if ( ! self::db_table_exists( 'quote_task' ) ) {
			$sql .= "CREATE TABLE `" . _DB_PREFIX . "quote_task` (
    `quote_task_id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_id` int(11) NULL,
    `hours` decimal(10,2) NOT NULL DEFAULT '0',
    `amount` decimal(10,2) NOT NULL DEFAULT '0',
    `taxable` tinyint(1) NOT NULL DEFAULT '1',
    `billable` tinyint(2) NOT NULL DEFAULT '1',
    `description` text NULL,
    `long_description` LONGTEXT NULL,
    `manual_task_type` tinyint(2) NOT NULL DEFAULT '-1',
    `user_id` INT NOT NULL DEFAULT  '0',
    `task_order` INT NOT NULL DEFAULT  '0',
    `product_id` INT NOT NULL DEFAULT  '0',
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY (`quote_task_id`),
        KEY `quote_id` (`quote_id`),
        KEY `product_id` (`product_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		} else {

			$fields = get_fields( 'quote_task' );
			if ( ! isset( $fields['product_id'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'quote_task` ADD `product_id` INT NOT NULL DEFAULT  \'0\' AFTER `task_order`;';
			}
		}
		if ( ! self::db_table_exists( 'quote_tax' ) ) {
			$sql .= "CREATE TABLE `" . _DB_PREFIX . "quote_tax` (
    `quote_tax_id` int(11) NOT NULL AUTO_INCREMENT,
    `quote_id` int(11) NOT NULL,
    `percent` decimal(10,2) NOT NULL DEFAULT  '0',
    `amount` decimal(10,2) NOT NULL DEFAULT  '0',
    `name` varchar(50) NOT NULL DEFAULT  '',
    `order` INT( 4 ) NOT NULL DEFAULT  '0',
    `increment` TINYINT( 1 ) NOT NULL DEFAULT  '0',
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY (`quote_tax_id`),
    KEY (`quote_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}


		self::add_table_index( 'quote', 'customer_id' );
		self::add_table_index( 'quote', 'user_id' );
		self::add_table_index( 'quote', 'website_id' );
		self::add_table_index( 'quote', 'archived' );
		self::add_table_index( 'quote_task', 'quote_id' );
		self::add_table_index( 'quote_task', 'user_id' );
		self::add_table_index( 'quote_task', 'product_id' );

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>quote` (
		`quote_id` int(11) NOT NULL auto_increment,
		`customer_id` INT(11) NULL,
		`website_id` INT(11) NULL,
		`hourly_rate` DECIMAL(10,2) NULL,
		`name` varchar(255) NOT NULL DEFAULT  '',
		`type` varchar(255) NOT NULL DEFAULT  '',
		`status` varchar(255) NOT NULL DEFAULT  '',
		`tax_type` int(11) NOT NULL DEFAULT  '0',
		`total_tax_name` varchar(20) NOT NULL DEFAULT  '',
		`total_tax_rate` DECIMAL(10,2) NULL,
		`date_create` date NOT NULL,
		`date_approved` date NOT NULL,
		`approved_by` varchar(255) NOT NULL DEFAULT '',
		`user_id` INT NOT NULL DEFAULT  '0',
		`contact_user_id` INT NOT NULL DEFAULT  '-1',
		`default_task_type` int(3) NOT NULL DEFAULT  '0',
		`auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`quote_discussion` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`currency_id` INT NOT NULL DEFAULT  '1',
		`total_percent_complete` DECIMAL( 6,4 ) NOT NULL DEFAULT  '0',
		`c_total_amount_invoicable` DECIMAL( 10,2 ) NOT NULL DEFAULT  '-1',
		`description` TEXT NOT NULL DEFAULT  '',
		`discount_amount` DECIMAL(10,2) NULL,
		`discount_description` varchar(255) NULL,
		`discount_type` INT NOT NULL DEFAULT '0',
		`archived` tinyint(1) NOT NULL DEFAULT '0',
		`billing_type` tinyint(1) NOT NULL DEFAULT '0',
		`quote_template_print` varchar(50) NOT NULL DEFAULT '',
		`quote_template_email` varchar(50) NOT NULL DEFAULT '',
		`quote_template_external` varchar(50) NOT NULL DEFAULT '',
		`calendar_show` tinyint(1) NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`quote_id`),
		KEY `customer_id` (`customer_id`),
		KEY `user_id` (`user_id`),
		KEY `website_id` (`website_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE `<?php echo _DB_PREFIX; ?>quote_task` (
		`quote_task_id` int(11) NOT NULL AUTO_INCREMENT,
		`quote_id` int(11) NULL,
		`hours` decimal(10,2) NOT NULL DEFAULT '0',
		`amount` decimal(10,2) NOT NULL DEFAULT '0',
		`taxable` tinyint(1) NOT NULL DEFAULT '1',
		`billable` tinyint(2) NOT NULL DEFAULT '1',
		`description` text NULL,
		`long_description` LONGTEXT NULL,
		`manual_task_type` tinyint(2) NOT NULL DEFAULT '-1',
		`user_id` INT NOT NULL DEFAULT  '0',
		`task_order` INT NOT NULL DEFAULT  '0',
		`product_id` INT NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`quote_task_id`),
		KEY `quote_id` (`quote_id`),
		KEY `product_id` (`product_id`),
		KEY `user_id` (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>quote_tax` (
		`quote_tax_id` int(11) NOT NULL AUTO_INCREMENT,
		`quote_id` int(11) NOT NULL,
		`percent` decimal(10,2) NOT NULL DEFAULT  '0',
		`amount` decimal(10,2) NOT NULL DEFAULT  '0',
		`name` varchar(50) NOT NULL DEFAULT  '',
		`order` INT( 4 ) NOT NULL DEFAULT  '0',
		`increment` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`quote_tax_id`),
		KEY (`quote_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php

		return ob_get_clean();
	}

}

include_once 'class.quote.php';

