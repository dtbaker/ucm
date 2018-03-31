<?php


define( '_TICKET_ACCESS_ALL', 'All support tickets' );
define( '_TICKET_ACCESS_ASSIGNED', 'Only assigned tickets' );
define( '_TICKET_ACCESS_CREATED', 'Only tickets I created' );
define( '_TICKET_ACCESS_GROUP', 'Only tickets from my groups' );
define( '_TICKET_ACCESS_CUSTOMER', 'Only tickets from my customer account' );

define( '_TICKET_MESSAGE_TYPE_CREATOR', 1 );
define( '_TICKET_MESSAGE_TYPE_ADMIN', 0 );
define( '_TICKET_MESSAGE_TYPE_AUTOREPLY', 3 );


define( '_TICKET_PRIORITY_STATUS_ID', 5 );

define( '_TICKET_STATUS_NEW_ID', 2 );
define( '_TICKET_STATUS_IN_PROGRESS_ID', 5 );
define( '_TICKET_STATUS_RESOLVED_ID', 6 );

class module_ticket extends module_base {

	public $links;
	public $ticket_types;

	public $version = 2.504;
	// 2.504 - 2017-05-02 - file path configuration
	// 2.503 - 2017-04-22 - more debug messages on save test page
	// 2.502 - 2017-02-20 - gmail email markup
	// 2.501 - 2017-02-06 - date highlghting fix
	// 2.500 - 2016-11-07 - ticket due date on create page.
	// 2.499 - 2016-11-06 - ticket due date and sorting.
	// 2.498 - 2016-11-01 - ticket timer feature
	// 2.497 - 2016-07-10 - big update to mysqli
	// 2.496 - 2015-03-14 - lightbox preview of ticket attachments
	// 2.495 - 2015-02-02 - ticket_send_customer_alerts and ticket_send_admin_alerts
	// 2.494 - 2015-12-30 - ticket permission sql fix
	// 2.493 - 2015-12-28 - ticket group permissions
	// 2.492 - 2015-12-28 - ticket type fix
	// 2.491 - 2015-09-08 - ticket api improvements
	// 2.49 - 2015-09-07 - ticket api improvements
	// 2.489 - 2015-06-08 - change to me button on staff assignment during reply
	// 2.488 - 2015-05-26 - first attempt at an API
	// 2.487 - 2015-05-07 - prev/next button fixes
	// 2.486 - 2015-05-04 - responsive improvements
	// 2.485 - 2015-04-27 - responsive improvements
	// 2.484 - 2015-04-05 - different staff member per ticket type
	// 2.483 - 2015-03-14 - html check improvement
	// 2.482 - 2015-03-13 - ticket_admin_email template improvement
	// 2.481 - 2015-03-08 - ticket remember last search on unread/delete
	// 2.48 - 2015-03-08 - ticket bulk action improvement
	// 2.479 - 2015-03-07 - ticket cron bug fix
	// 2.478 - 2015-03-05 - ticket contact permission fix
	// 2.477 - 2015-03-05 - bug fix
	// 2.476 - 2015-02-24 - contact_name template tag added to ticket_admin_email
	// 2.475 - 2015-01-19 - staff member drop down on search
	// 2.474 - 2014-12-11 - bug fix assigning ticket contacts
	// 2.473 - 2014-12-08 - correctly assigning BCC customer emails
	// 2.472 - 2014-11-26 - create quote from ticket
	// 2.471 - 2014-11-26 - bulk ticket improvements
	// 2.47 - 2014-11-17 - ticket_auto_notify_staff improvement
	// 2.469 - 2014-11-14 - ticket_auto_notify_staff improvement
	// 2.468 - 2014-11-05 - ticket_auto_notify_staff improvement
	// 2.467 - 2014-10-06 - typo fix

	// 2.466 - 2014-09-03 - ticket_from_creators_email setting on settings page.
	// 2.465 - 2014-09-02 - page numbers on support tickets
	// 2.464 - 2014-08-12 - priority support invoice creation fixes
	// 2.463 - 2014-08-01 - responsive improvements
	// 2.462 - 2014-07-28 - menu generation speed improvement
	// 2.461 - 2014-07-23 - ticket replace fields in admin subject
	// 2.46 - 2014-07-18 - ticket new status fix
	// 2.459 - 2014-07-18 - ticket unread count fix
	// 2.458 - 2014-07-14 - submit goto to prev ticket button
	// 2.457 - 2014-07-14 - ticket extra field fix
	// 2.456 - 2014-07-14 - ticket unread count fix
	// 2.455 - 2014-07-12 - ticket speed improvements
	// 2.454 - 2014-06-23 - staff report + admin email improvements
	// 2.453 - 2014-05-29 - delete ticket icon cache fix
	// 2.452 - 2014-05-23 - ticket private messages fix
	// 2.451 - 2014-05-23 - ticket private messages and easy cc staff member feature
	// 2.45 - 2014-05-12 - ticket bulk assign staff option
	// 2.449 - 2014-04-01 - show which user who replied to which ticket message and how the status changed.
	// 2.448 - 2014-04-01 - quoted printable character fix on imported mail with links
	// 2.447 - 2014-03-06 - change assigned contact on ticket
	// 2.446 - 2014-02-25 - ticket_turn_around_rate_show to bottom of create ticket form
	// 2.445 - 2014-02-24 - ticket_turn_around_rate_show added
	// 2.444 - 2014-02-23 - ticket_ordering - latest_message_first option
	// 2.443 - 2014-02-17 - show previous messages via ajax
	// 2.442 - 2014-02-15 - extra fields in email templates
	// 2.441 - 2014-02-15 - convert contact to customer improvement
	// 2.439 - 2014-02-14 - convert contact to customer fix
	// 2.438 - 2014-02-14 - fix to show correct email when ticket_from_creators_email is 0
	// 2.437 - 2014-02-10 - ticket_messages_in_reverse option added
	// 2.436 - 2014-02-10 - ticket_enable_groups and ticket_enable_notes added
	// 2.435 - 2014-02-05 - newsletter speed improvements
	// 2.434 - 2014-01-30 - extra fields showing in email templates
	// 2.433 - 2014-01-27 - ticket_other_list_by advanced setting added
	// 2.432 - 2014-01-21 - ticket_auto_notify_staff advanced setting added
	// 2.431 - 2013-12-19 - ticket_autoreply_enabled advanced setting added
	// 2.43 - 2013-12-15 - search ticket message content
	// 2.429 - 2013-12-08 - upgraded version of tinymce in ticket forms
	// 2.428 - 2013-12-01 - save & test account button fix
	// 2.427 - 2013-11-23 - file download improvement for some hosting accounts
	// 2.426 - 2013-11-15 - working on new UI
	// 2.425 - 2013-11-11 - insert saved ticket messages fix
	// 2.424 - 2013-11-11 - strict standards php error
	// 2.423 - 2013-11-01 - send email error on email import issues
	// 2.422 - 2013-10-06 - no auto overdue email on unpaid priority support tickets
	// 2.421 - 2013-09-03 - ticket cache
	// 2.42 - 2013-08-31 - bulk ticket operations
	// 2.419 - 2013-08-31 - ticket import, cache and speed improvements
	// 2.418 - 2013-08-31 - cache and speed improvements
	// 2.417 - 2013-07-30 - customer delete improvement
	// 2.416 - 2013-07-29 - new _UCM_SECRET hash in config.php
	// 2.415 - 2013-05-28 - faq improvement
	// 2.414 - 2013-05-02 - sanitised html message output in external ticket view
	// 2.413 - 2013-04-16 - fix for updated invoice system

	// old history:
	// 2.351 - ability to change the assigned contact / customer in the ticket.
	// 2.352 - added novalidate-cert to ticket IMAP connection
	// 2.353 - fix for get_user() to get_contact()
	// 2.354 - new option (ticket_allow_priority_selection) that allows user to select priority on ticket creation. also we allow extra fields on ticket creation.
	// 2.355 - delete a ticket from group
	// 2.36 - extra field encryption support.
	// 2.361 - bug fix for encryption
	// 2.362 - ajax customer drop down list, try not to sned autoreply to customer if admin created message.
	// 2.363 - fix for ticket cron
	// 2.37 - moved list/edit to support themeable pages.
	// 2.371 - hiding previosu messages.
	// 2.372 - mobile layout fixes
	// 2.373 - 20 out of 19 in email footer bug
	// 2.374 - fix pop3 connection string
	// 2.375 - ticket_from_creators_email config variable added
	// 2.376 - permissions fix
	// 2.377 - priority bug fix
	// 2.378 - notify staff member
	// 2.379 - priority invoice fix
	// 2.38 - bulk actions (beta) and public status change
	// 2.381 - replace ticket status links in auto-reply and
	// 2.382 - fix for importing support tickets without a default customer selected
	// 2.383 - improve ticket layout for large ticket threads
	// 2.384 - new jquery version
	// 2.385 - integration with new FAQ feature
	// 2.386 - config option added: ticket_reply_status_id
	// 2.387 - fix to import support emails without subjects
	// 2.388 - from email address as full name of user
	// 2.389 - create customer from ticket
	// 2.39 - fix for faq + product selection
	// 2.391 - ability to reject new support tickets via email, only allow replies
	// 2.392 - ability to create a support ticket from staff to customer.
	// 2.393 - support for multiple ticket queues based on "products" (set advanced 'ticket_separate_product_queue' to 1)
	// 2.394 - priority support fix
	// 2.395 - dropdown support in ticket extra fields
	// 2.396 - better moving customer contacts between customers
	// 2.397 - bug fix, sending customer ticket message alerts
	// 2.398 - extra fields update - show in main listing option
	// 2.399 - speed improvements
	// 2.401 - bug fix with ticket creation on staff accounts
	// 2.402 - ticket creation fix
	// 2.403 - character encoding fixes
	// 2.404 - improvement on displaying html ticket messages.
	// 2.405 - improved quick search
	// 2.406 - improved replying from administrator email account
	// 2.407 - email import fix
	// 2.408 - started work on email CC/BCC fields
	// 2.409 - new cc/bcc option for ticket administrators
	// 2.41 - 2013-04-07 - ticket faq fixes
	// 2.411 - 2013-04-10 - new customer permissions
	// 2.412 - 2013-04-12 - status drop down clarification


	public static $ticket_statuses = array();

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
		$this->ticket_types    = array();
		$this->module_name     = "ticket";
		$this->module_position = 25;

		self::$ticket_statuses = array(
			1                             => _l( 'Unassigned' ),
			2                             => _l( 'New' ),
			3                             => _l( 'Replied' ),
			_TICKET_STATUS_IN_PROGRESS_ID => _l( 'In Progress' ),
			_TICKET_STATUS_RESOLVED_ID    => _l( 'Resolved' ),
			7                             => _l( 'Canceled' ),
		);


		/*$this->ajax_search_keys = array(
            _DB_PREFIX.'ticket' => array(
                'plugin' => 'ticket',
                'search_fields' => array(
                    'ticket_id',
                    'subject',
                ),
                'key' => 'ticket_id',
                'title' => _l('Ticket: '),
            ),
        );*/

		module_config::register_css( 'ticket', 'tickets.css' );
		module_config::register_js( 'ticket', 'tickets.js' );

		hook_add( 'invoice_admin_list_job', 'module_ticket::hook_invoice_admin_list_job' );
		hook_add( 'invoice_sidebar', 'module_ticket::hook_invoice_sidebar' );
		hook_add( 'customer_contact_moved', 'module_ticket::hook_customer_contact_moved' );
		hook_add( 'customer_deleted', 'module_ticket::hook_customer_deleted' );
		// filter var hooks
		hook_add( 'generate_fieldset_options', 'module_ticket::hook_filter_generate_fieldset_options' );
		hook_add( 'get_quote', 'module_ticket::hook_filter_get_quote' );
		hook_add( 'quote_save', 'module_ticket::hook_quote_save' );
		hook_add( 'quote_delete', 'module_ticket::hook_quote_delete' );
		hook_add( 'invoice_saved', 'module_ticket::hook_invoice_save' );
		hook_add( 'invoice_delete', 'module_ticket::hook_invoice_delete' );
		hook_add( 'api_callback_ticket', 'module_ticket::api_filter_ticket' );


		if ( class_exists( 'module_template', false ) ) {

			module_template::init_template( 'ticket_email_notify', 'Dear {STAFF_NAME},<br>
<br>
A support ticket has been assigned to you.<br>
Ticket Number: <strong>{TICKET_NUMBER}</strong><br>
Ticket Subject: <strong>{TICKET_SUBJECT}</strong><br>
To view this ticket please <a href="{TICKET_URL}">click here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Ticket Assigned: {TICKET_NUMBER}', array(
				'STAFF_NAME'     => 'Staff Name',
				'TICKET_NUMBER'  => 'Ticket Number',
				'TICKET_SUBJECT' => 'Ticket Subject',
				'TICKET_URL'     => 'Link to ticket for customer',
			) );

			module_template::init_template( 'ticket_container', '<span style="font-size:10px; color:#666666;">{REPLY_LINE}</span>
<span style="font-size:10px; color:#666666;">Your ticket has been updated, please see the message below:</span>


{MESSAGE}


<span style="font-size:10px; color:#666666;">Ticket Number: <strong>{TICKET_NUMBER}</strong></span>
<span style="font-size:10px; color:#666666;">Ticket Status: <strong>{TICKET_STATUS}</strong></span>
<span style="font-size:10px; color:#666666;">Your position in the support queue: <strong>{POSITION_CURRENT} out of {POSITION_ALL}</strong>.</span>
<span style="font-size:10px; color:#666666;">Estimated time for a reply: <strong>within {DAYS} days</strong></span>
<span style="font-size:10px; color:#666666;">You can view the status of your support query at any time by following this link:</span>
<span style="font-size:10px; color:#666666;"><a href="{URL}" style="color:#666666;">View Ticket {TICKET_NUMBER} History Online</a></span>

', 'The email sent along with all ticket replies.', 'text' );

			module_template::init_template( 'ticket_admin_email', '{MESSAGE}


<span style="font-size:12px; color:#666666; font-weight: bold;">Ticket Details:</span>
<span style="font-size:10px; color:#666666;">Number of messages: <strong>{MESSAGE_COUNT}</strong></span>
<span style="font-size:10px; color:#666666;">Ticket Number: <strong>{TICKET_NUMBER}</strong></span>
<span style="font-size:10px; color:#666666;">Ticket Status: <strong>{TICKET_STATUS}</strong></span>
<span style="font-size:10px; color:#666666;">Position in the support queue: <strong>{POSITION_CURRENT} out of {POSITION_ALL}</strong>.</span>
<span style="font-size:10px; color:#666666;">Estimated time for a reply: <strong>within {DAYS} days</strong></span>
<span style="font-size:10px; color:#666666;">View the ticket: <strong>{URL_ADMIN}</strong></span>
        ', 'Sent as an email to the administrator when a new ticket is created.', 'text' );

			module_template::init_template( 'ticket_autoreply', 'Hello,

Thank you for your email. We will reply shortly.

        ', 'Sent as an email after a support ticket is received.', 'text' );

			module_template::init_template( 'ticket_rejection', 'Hello,

Please submit all NEW support tickets via our website by following this link:
{TICKET_URL}

New support tickets are no longer accepted via email due to high levels of spam causing delays for everyone.

Thanks,

        ', 'Email Bounced: {SUBJECT}.', 'text' );
		}


	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."ticket` c WHERE ";
			//$sql .= " c.`ticket_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_tickets( array( 'generic' => $search_key ) );
			if ( mysqli_num_rows( $results ) ) {
				while ( $result = mysqli_fetch_assoc( $results ) ) {
					// what part of this matched?
					/*if(
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['last_name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['phone'])
                    ){
                        // we matched the ticket contact details.
                        $match_string = _l('Ticket Contact: ');
                        $match_string .= _shl($result['ticket_name'],$search_key);
                        $match_string .= ' - ';
                        $match_string .= _shl($result['name'],$search_key);
                        // hack
                        $_REQUEST['ticket_id'] = $result['ticket_id'];
                        $ajax_results [] = '<a href="'.module_user::link_open_contact($result['user_id']) . '">' . $match_string . '</a>';
                    }else{*/
					$match_string    = _l( 'Ticket: ' );
					$match_string    .= _shl( '#' . self::ticket_number( $result['ticket_id'] ) . ' ' . $result['subject'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['ticket_id'] ) . '">' . $match_string . '</a>';
					//$ajax_results [] = $this->link_open($result['ticket_id'],true);
					/*}*/
				}
			}
		}

		return $ajax_results;
	}

	public function pre_menu() {


		if ( $this->is_installed() && $this->can_i( 'view', 'Tickets' ) ) {

			/* module_security::has_feature_access(array(
                    'name' => 'Settings',
                    'module' => 'config',
                    'category' => 'Config',
                    'view' => 1,
                    'description' => 'view',
            ))*/
			if ( $this->can_i( 'edit', 'Ticket Settings' ) ) {
				$this->links['ticket_settings'] = array(
					"name"                => "Ticket",
					"p"                   => "ticket_settings",
					'args'                => array( 'ticket_account_id' => false, 'ticket_id' => false ),
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}

			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
				$link_name = _l( 'Tickets' );
				if ( module_config::c( 'menu_show_summary', 0 ) && module_config::c( 'ticket_show_summary', 1 ) && self::can_edit_tickets() ) {
					// how many tickets?
					// cache results for 30 seconds.
					switch ( module_config::c( 'ticket_show_summary_type', 'unread' ) ) {
						case 'unread':
							$ticket_count = self::get_unread_ticket_count( $_REQUEST['customer_id'] );
							break;
						case 'total':
						default:
							$ticket_count = self::get_total_ticket_count( $_REQUEST['customer_id'] );
							break;
					}

					if ( $ticket_count > 0 ) {
						$link_name .= " <span class='menu_label'>" . $ticket_count . "</span> ";
					}
				}

				$this->links['ticket_customer'] = array(
					"name"                => $link_name,
					"p"                   => "ticket_admin",
					'args'                => array( 'ticket_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'comments-o',
				);
			}


			$link_name = _l( 'Tickets' );
			if ( module_config::c( 'menu_show_summary', 0 ) && module_config::c( 'ticket_show_summary', 1 ) && self::can_edit_tickets() ) {
				switch ( module_config::c( 'ticket_show_summary_type', 'unread' ) ) {
					case 'unread':
						$ticket_count = self::get_unread_ticket_count();
						break;
					case 'total':
					default:
						$ticket_count = self::get_total_ticket_count();
						break;
				}

				if ( $ticket_count > 0 ) {
					$link_name .= " <span class='menu_label'>" . $ticket_count . "</span> ";
				}
				$ticket_count = self::get_ticket_count();
				if ( $ticket_count && $ticket_count['priority'] > 0 ) {
					$link_name .= " <span class='menu_label" . ( $ticket_count['unread'] > 0 ? ' important' : '' ) . "'>" . $ticket_count['priority'] . "</span> ";
				}
			}
			$this->links['ticket_main'] = array(
				"name"      => $link_name,
				"p"         => "ticket_admin",
				'args'      => array( 'ticket_id' => false ),
				'icon_name' => 'comments-o',
			);
		}
	}

	public static function can_edit_tickets() {
		return module_security::is_logged_in() && self::can_i( 'edit', 'Tickets' );
	}

	public static function creator_hash( $creator_id ) {
		return md5( 'secret key! ' . _UCM_SECRET . $creator_id );
	}

	public function handle_hook( $hook ) {
		switch ( $hook ) {
			case "invoice_paid":


				$foo        = func_get_args();
				$invoice_id = (int) $foo[1];
				if ( $invoice_id > 0 ) {
					// see if any tickets match this invoice.
					$ticket = get_single( 'ticket', 'invoice_id', $invoice_id );
					if ( $ticket ) {
						// check it's status and make it priority if it isn't already
						if ( $ticket['priority'] != _TICKET_PRIORITY_STATUS_ID ) {
							update_insert( 'ticket_id', $ticket['ticket_id'], 'ticket', array(
								'priority' => _TICKET_PRIORITY_STATUS_ID,
							) );
							// todo - send email to admin?
							//send_email('dtbaker@gmail.com','priority ticket',var_export($ticket,true));
						}
					}
				}

				break;
			case "home_alerts":
				$alerts = array();
				if ( module_ticket::can_edit_tickets() ) {
					if ( module_config::c( 'ticket_alerts', 1 ) ) {
						// find any tickets that are past the due date and dont have a finished date.
						$sql     = "SELECT * FROM `" . _DB_PREFIX . "ticket` p ";
						$sql     .= " WHERE p.status_id <= 2 AND p.date_updated <= '" . date( 'Y-m-d', strtotime( '-' . module_config::c( 'ticket_turn_around_days', 5 ) . ' days' ) ) . "'";
						$tickets = qa( $sql );
						foreach ( $tickets as $ticket ) {
							$alert_res = process_alert( $ticket['date_updated'], _l( 'Ticket Not Completed' ), module_config::c( 'ticket_turn_around_days', 5 ) );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $ticket['ticket_id'] );
								$alert_res['name'] = $ticket['subject'];
								$alerts[]          = $alert_res;
							}
						}
					}
				}

				return $alerts;
				break;
		}
	}

	public static function link_generate( $ticket_id = false, $options = array(), $link_options = array() ) {

		$key = 'ticket_id';
		if ( $ticket_id === false && $link_options ) {
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
			$options['type'] = 'ticket';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'ticket_admin';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['ticket_id'] = $ticket_id;
		$options['module']                 = 'ticket';
		// what text should we display in this link?
		if ( $options['page'] == 'ticket_settings_fields' ) {
			if ( isset( $options['data'] ) && $options['data'] ) {
				//$options['data'] = $options['data'];
			} else {
				$data            = self::get_ticket_extras_key( $ticket_id );
				$options['data'] = $data;
			}
			$options['text'] = isset( $options['data']['key'] ) ? $options['data']['key'] : '';
			array_unshift( $link_options, $options );
			$options['page'] = 'ticket_settings';

			// bubble back onto ourselves for the link.
			return self::link_generate( $ticket_id, $options, $link_options );
		} else if ( $options['page'] == 'ticket_settings_types' ) {
			if ( isset( $options['data'] ) && $options['data'] ) {
				//$options['data'] = $options['data'];
			} else {
				$data            = self::get_ticket_type( $ticket_id );
				$options['data'] = $data;
			}
			$options['text'] = isset( $options['data']['name'] ) ? $options['data']['name'] : '';
			array_unshift( $link_options, $options );
			$options['page'] = 'ticket_settings';

			// bubble back onto ourselves for the link.
			return self::link_generate( $ticket_id, $options, $link_options );
		} elseif ( $options['page'] == 'ticket_settings_accounts' ) {
			if ( isset( $options['data'] ) && $options['data'] ) {
				//$options['data'] = $options['data'];
			} else {
				$data            = self::get_ticket_account( $ticket_id );
				$options['data'] = $data;
			}
			$options['text'] = $options['data']['name'];
			array_unshift( $link_options, $options );
			$options['page'] = 'ticket_settings';

			// bubble back onto ourselves for the link.
			return self::link_generate( $ticket_id, $options, $link_options );
		} else {
			if ( isset( $options['data'] ) && $options['data'] ) {
				//$options['data'] = $options['data'];
			} else {
				$data             = self::get_ticket( $ticket_id );
				$options['data']  = $data;
				$options['class'] = 'error';
			}
			$options['text'] = $ticket_id ? self::ticket_number( $ticket_id ) : 'N/A';
		}
		array_unshift( $link_options, $options );
		if ( $options['page'] == 'ticket_admin' && $options['data'] && isset( $options['data']['status_id'] ) ) {
			// pick the class name for the error. or ticket status
			$link_options['class'] = 'ticket_status_' . $options['data']['status_id'];
		}
		if ( self::can_i( 'edit', 'Ticket Settings' ) && $options['page'] == 'ticket_settings' ) {
			$bubble_to_module = array(
				'module' => 'config',
			);
		} else if ( ( ! isset( $_GET['customer_id'] ) || ! $_GET['customer_id'] ) && class_exists( 'module_faq', false ) && ( module_config::c( 'ticket_separate_product_queue', 0 ) || module_config::c( 'ticket_separate_product_menu', 0 ) ) ) {

		} else if ( $options['data']['customer_id'] > 0 ) {
			if ( ! module_security::has_feature_access( array(
				'name'        => 'Customers',
				'module'      => 'customer',
				'category'    => 'Customer',
				'view'        => 1,
				'description' => 'view',
			) ) ) {
				/*if(!isset($options['full']) || !$options['full']){
                    return '#';
                }else{
                    return isset($options['text']) ? $options['text'] : 'N/A';
                }*/
			} else {
				$bubble_to_module = array(
					'module'   => 'customer',
					'argument' => 'customer_id',
				);
			}
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

	public static function link_open( $ticket_id, $full = false, $ticket_data = array() ) {
		return self::link_generate( $ticket_id, array( 'full' => $full, 'data' => $ticket_data ) );
	}

	public static function link_open_notify( $ticket_id, $full = false, $ticket_data = array() ) {
		return self::link_generate( $ticket_id, array(
			'data'      => $ticket_data,
			'full'      => $full,
			'arguments' => array( 'notify' => 1 )
		) );
	}

	public static function link_open_account( $ticket_account_id, $full = false ) {
		return self::link_generate( $ticket_account_id, array(
			'page'      => 'ticket_settings_accounts',
			'full'      => $full,
			'arguments' => array( 'ticket_account_id' => $ticket_account_id )
		) );
	}

	public static function link_open_field( $ticket_data_key_id, $full = false ) {
		return self::link_generate( $ticket_data_key_id, array(
			'page'      => 'ticket_settings_fields',
			'full'      => $full,
			'arguments' => array( 'ticket_data_key_id' => $ticket_data_key_id )
		) );
	}

	public static function link_open_type( $ticket_type_id, $full = false ) {
		return self::link_generate( $ticket_type_id, array(
			'page'      => 'ticket_settings_types',
			'full'      => $full,
			'arguments' => array( 'ticket_type_id' => $ticket_type_id )
		) );
	}


	public static function link_public_status( $ticket_id, $new_status_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for tickets ' . $new_status_id . 'statuses ' . _UCM_SECRET . ' ' . $ticket_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.ticket/h.public_status/i.' . $ticket_id . '/s.' . $new_status_id . '/hash.' . self::link_public_status( $ticket_id, $new_status_id, true ) );

	}

	public static function link_public( $ticket_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for tickets ' . _UCM_SECRET . ' ' . $ticket_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.ticket/h.public/i.' . $ticket_id . '/hash.' . self::link_public( $ticket_id, true ) );
		//return full_link(_EXTERNAL_TUNNEL.'?m=ticket&h=public&i='.$ticket_id.'&hash='.self::link_public($ticket_id,true));
		/*
        // return an auto login link for the end user.
        $ticket_data = self::get_ticket($ticket_id);
        if($ticket_data['user_id']){
            $auto_login_link = 'auto_login='.module_security::get_auto_login_string($ticket_data['user_id']);
        }else{
            $auto_login_link = '';
        }
        $link_options = array();
        $options['page'] = 'ticket_admin';
        $options['arguments'] = array();
        $options['arguments']['ticket_id'] = $ticket_id;
        $options['module'] = 'ticket';
        $options['data'] = $ticket_data;
        array_unshift($link_options,$options);
        $link = link_generate($link_options);
        $link .= strpos($link,'?') === false ? '?' : '&';
        $link .= $auto_login_link;
        return $link;
        */
	}

	public static function link_open_attachment( $ticket_id, $ticket_message_attachment_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for ticket attacments ' . _UCM_SECRET . ' ' . $ticket_id . '-' . $ticket_message_attachment_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.ticket/h.attachment/t.' . $ticket_id . '/tma.' . $ticket_message_attachment_id . '/hash.' . self::link_open_attachment( $ticket_id, $ticket_message_attachment_id, true ) );
		//return full_link(_EXTERNAL_TUNNEL.'?m=ticket&h=attachment&t='.$ticket_id.'&tma='.$ticket_message_attachment_id.'&hash='.self::link_open_attachment($ticket_id,$ticket_message_attachment_id,true));
	}

	public static function link_public_new() {
		return full_link( _EXTERNAL_TUNNEL . '?m=ticket&h=public_new' );
	}

	public static function api_filter_ticket( $hook, $response, $endpoint, $method ) {
		$response['ticket'] = true;
		switch ( $method ) {
			case 'list':
				if ( class_exists( 'module_envato', false ) ) {
					// todo: filter this in the api filter so we keep envato code in the envato plugin
					$all_items_rel = module_envato::get_envato_items_rel();
				}
				$search = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				if ( ! isset( $search['status_id'] ) ) {
					$search['status_id'] = '2,3,5';
				} // new/replied/inprogress
				$tickets_mysql       = module_ticket::get_tickets( $search, true );
				$response['tickets'] = array();
				$extra_keys          = module_ticket::get_ticket_extras_keys();
				while ( $ticket = mysqli_fetch_assoc( $tickets_mysql ) ) {
					// return user details along with this ticket
					$user           = module_user::get_user( $ticket['user_id'], false, true, true );
					$ticket['user'] = array();
					foreach ( array( 'user_id', 'customer_id', 'vendor_id', 'email', 'name', 'last_name' ) as $field ) {
						$ticket['user'][ $field ] = isset( $user[ $field ] ) ? $user[ $field ] : '';
					}
					$staff           = module_user::get_user( $ticket['assigned_user_id'] ? $ticket['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 ), false, true, true );
					$ticket['staff'] = array();
					foreach ( array( 'user_id', 'customer_id', 'vendor_id', 'email', 'name', 'last_name' ) as $field ) {
						$ticket['staff'][ $field ] = isset( $staff[ $field ] ) ? $staff[ $field ] : '';
					}
					// find the extra data associated with this ticket.
					$ticket['extra'] = array();
					foreach ( self::get_ticket_extras( $ticket['ticket_id'] ) as $extra_data ) {
						if ( isset( $extra_keys[ $extra_data['ticket_data_key_id'] ] ) ) {
							$ticket['extra'][ $extra_keys[ $extra_data['ticket_data_key_id'] ]['key'] ] = $extra_data['value'];
						}
					}
					if ( class_exists( 'module_envato', false ) ) {
						$ticket['user']['envato']              = array();
						$ticket['user']['envato']['user']      = array();
						$ticket['user']['envato']['purchases'] = array();
						$envato_tickets                        = get_multiple( 'envato_ticket', array( 'ticket_id' => $ticket['ticket_id'] ) );

						foreach ( $envato_tickets as $envato_ticket ) {
							if ( $envato_ticket && $envato_ticket['envato_ticket_id'] ) {
								if ( isset( $all_items_rel[ $envato_ticket['envato_item_id'] ] ) && $envato_ticket['envato_author_id'] ) {
									$envato_author    = get_single( 'envato_author', 'envato_author_id', $envato_ticket['envato_author_id'] );
									$buys             = @unserialize( $envato_author['purchase_history'] );
									$purchase_history = array();
									foreach ( $buys as $buy ) {
										$purchase_history[] = $buy;
									}
									$ticket['user']['envato']['purchases'][ $envato_ticket['envato_item_id'] ] = array(
										'item_name'      => $all_items_rel[ $envato_ticket['envato_item_id'] ],
										'envato_item_id' => $envato_ticket['envato_item_id'],
										'license_code'   => $envato_ticket['license_code'],
									);
									$ticket['user']['envato']['user'][ $envato_author['envato_username'] ]     = array(
										'envato_username'  => $envato_author['envato_username'],
										'purchase_history' => $purchase_history,
									);
								}
							}
						}
					}
					$ticket['url']         = self::link_open( $ticket['ticket_id'], false, $ticket );
					$response['tickets'][] = $ticket;

					$response['reply_options'] = array(
						array(
							'title' => 'Mark Thread Resolved',
							'field' => array(
								'type'    => 'checkbox',
								'value'   => 1,
								'name'    => 'resolved',
								'checked' => true,
							)
						)
					);
				}
				break;
			case 'message':
				$search              = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				$response['tickets'] = array();
				if ( empty( $search['ticket_ids'] ) && isset( $_REQUEST['ticket_ids'] ) ) {
					$search['ticket_ids'] = explode( ',', $_REQUEST['ticket_ids'] );
				}
				if ( isset( $search['ticket_ids'] ) && is_array( $search['ticket_ids'] ) ) {
					foreach ( $search['ticket_ids'] as $ticket_id ) {
						$ticket_id = (int) trim( $ticket_id );
						if ( $ticket_id ) {
							$messages = module_ticket::get_ticket_messages( $ticket_id, false );
							if ( $messages ) {
								foreach ( $messages as $message_id => $message ) {
									$user                            = module_user::get_user( $message['from_user_id'], false );
									$messages[ $message_id ]['user'] = array();
									foreach ( array( 'customer_id', 'vendor_id', 'email', 'name', 'last_name' ) as $field ) {
										$messages[ $message_id ]['user'][ $field ] = isset( $user[ $field ] ) ? $user[ $field ] : '';
									}
								}
								$response['tickets'][ $ticket_id ] = $messages;
							}
						}
					}
				}
				break;
			case 'reply':
				$ticket_id  = (int) $_REQUEST['ticket_id'];
				$message    = $_REQUEST['message'];
				$extra_data = isset( $_REQUEST['extra_data'] ) && is_array( $_REQUEST['extra_data'] ) ? $_REQUEST['extra_data'] : array();
				// do we mark is as resolved? similar to bbpress api
				if ( $ticket_id && $message ) {

					// send a reply to this particular message.
					$ticket_data = self::get_ticket( $ticket_id );
					if ( $ticket_data && $ticket_data['ticket_id'] == $ticket_id ) {

						$from_user_id                  = $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 ); // send this back in initial api response.
						$ticket_message_id             = self::send_reply( $ticket_id, $message, $from_user_id, $ticket_data['user_id'], 'admin' );
						$response['ticket_message_id'] = $ticket_message_id;

						if ( $ticket_message_id ) {
							if ( ! empty( $extra_data['resolved'] ) ) {
								update_insert( "ticket_message_id", $ticket_message_id, "ticket_message", array( 'status_id' => _TICKET_STATUS_RESOLVED_ID ) );
								update_insert( "ticket_id", $ticket_id, "ticket", array( 'status_id' => _TICKET_STATUS_RESOLVED_ID ) );
								$response['stat'] = 'Changing status to ' . _TICKET_STATUS_RESOLVED_ID;
							} else {
								// change the ticket to in progress
								update_insert( "ticket_message_id", $ticket_message_id, "ticket_message", array( 'status_id' => _TICKET_STATUS_IN_PROGRESS_ID ) );
								update_insert( "ticket_id", $ticket_id, "ticket", array( 'status_id' => _TICKET_STATUS_IN_PROGRESS_ID ) );
								$response['stat'] = 'Changing status to ' . _TICKET_STATUS_IN_PROGRESS_ID;
							}
						}
						module_cache::clear( 'ticket' );
					}
				}

				if ( empty( $response['ticket_message_id'] ) ) {
					$response['error']   = true;
					$response['message'] = 'Failed to send message reply.';
				}


				break;
		}

		return $response;
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'attachment':

				$ticket_id                    = ( isset( $_REQUEST['t'] ) ) ? (int) $_REQUEST['t'] : false;
				$ticket_message_attachment_id = ( isset( $_REQUEST['tma'] ) ) ? (int) $_REQUEST['tma'] : false;
				$hash                         = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $ticket_id && $ticket_message_attachment_id && $hash ) {
					$correct_hash = $this->link_open_attachment( $ticket_id, $ticket_message_attachment_id, true );
					if ( $correct_hash == $hash ) {
						$attach = get_single( 'ticket_message_attachment', 'ticket_message_attachment_id', $ticket_message_attachment_id );
						if ( file_exists( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attach['ticket_message_attachment_id'] ) ) {
							header( "Content-type: application/octet-stream" );
							header( 'Content-Disposition: attachment; filename="' . $attach['file_name'] . '";' );
							$size = @readfile( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attach['ticket_message_attachment_id'] );
							if ( ! $size ) {
								echo file_get_contents( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attach['ticket_message_attachment_id'] );
							}
						} else {
							echo 'File no longer exists';
						}
					}
				}
				exit;
				break;
			case 'status':
				ob_start();
				?>

				<table class="wpetss wpetss_status">
					<tbody>
					<tr>
						<th><?php _e( 'New/Pending Tickets' ); ?></th>
						<td>
							<?php
							$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` WHERE status_id = 1 OR status_id = 2";
							$res = qa1( $sql );
							echo $res['c'];
							?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'In Progress Tickets' ); ?></th>
						<td>
							<?php
							$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` WHERE status_id = 3 OR status_id = " . _TICKET_STATUS_IN_PROGRESS_ID;
							$res = qa1( $sql );
							echo $res['c'];
							?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Resolved Tickets' ); ?></th>
						<td>
							<?php
							$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` WHERE status_id >= " . _TICKET_STATUS_RESOLVED_ID;
							$res = qa1( $sql );
							echo $res['c'];
							?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Estimated Turn Around' ); ?></th>
						<td>
							<?php echo _l( 'We will reply within %s and %s %S', module_config::c( 'ticket_turn_around_days_min', 2 ), module_config::c( 'ticket_turn_around_days', 5 ), module_config::c( 'ticket_turn_around_period', 'days' ) ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Current Reply Rate' ); ?></th>
						<td>
							<?php
							$rate = module_ticket::get_reply_rate();
							echo _l( 'We are currently processing %s tickets every 24 hours', $rate['daily'] ); ?>
						</td>
					</tr>
					</tbody>
				</table>
				<?php
				echo preg_replace( '/\s+/', ' ', ob_get_clean() );
				exit;
				break;
			case 'public_new':

				$ticket_id         = 'new';
				$ticket_account_id = module_config::c( 'ticket_default_account_id', 0 ); //todo: set from a hashed variable in GET string.
				if ( $ticket_account_id ) {
					$ticket_account = self::get_ticket_account( $ticket_account_id );
				} else {
					$ticket_account_id = 0;
					$ticket_account    = array();
				}
				if ( ! $ticket_account || $ticket_account['ticket_account_id'] != $ticket_account_id ) {
					// dont support accounts yet. work out the default customer id etc.. from settings.
					$ticket_account = array(
						'ticket_account_id'   => 0,
						'default_customer_id' => module_config::c( 'ticket_default_customer_id', 1 ),
						'default_user_id'     => module_config::c( 'ticket_default_user_id', 1 ),
						'default_type'        => module_config::c( 'ticket_type_id_default', 0 ),
					);
				}

				// hack to better support recaptcha errors.
				$save_public_ticket = false;
				$errors             = array();

				if ( isset( $_REQUEST['_process'] ) && $_REQUEST['_process'] == 'save_public_ticket' ) {
					// user is saving the ticket.
					// process it!

					$save_public_ticket = true;

					if ( module_config::c( 'ticket_recaptcha', 1 ) ) {
						if ( ! module_captcha::check_captcha_form() ) {
							// captcha was wrong.
							$errors [] = _l( 'Sorry the captcha code you entered was incorrect. Please try again.' );
							if ( isset( $_FILES['attachment'] ) && isset( $_FILES['attachment']['tmp_name'] ) && is_array( $_FILES['attachment']['tmp_name'] ) ) {
								foreach ( $_FILES['attachment']['tmp_name'] as $key => $val ) {
									if ( is_uploaded_file( $val ) ) {
										$errors [] = _l( 'Please select your file attachments again as well.' );
										break;
									}
								}
							}
							$save_public_ticket = false;
						}
					}
				}
				if ( $save_public_ticket && isset( $_POST['new_ticket_message'] ) && strlen( $_POST['new_ticket_message'] ) > 1 ) {

					// this allows input variables to be added to our $_POST
					// like extra fields etc.. from envato module.
					handle_hook( 'ticket_create_post', $ticket_id );

					// we're posting from a public account.
					// check required fields.
					if ( ! trim( $_POST['subject'] ) ) {
						return false;
					}
					// check this user has a valid email address, find/create a user in the ticket user table.
					// see if this email address exists in the wp user table, and link that user there.
					$email = trim( strtolower( $_POST['email'] ) );
					$name  = trim( $_POST['name'] );
					if ( strpos( $email, '@' ) ) { //todo - validate email.
						$sql       = "SELECT * FROM `" . _DB_PREFIX . "user` u WHERE u.`email` LIKE '" . db_escape( $email ) . "'";
						$from_user = qa1( $sql );
						if ( $from_user ) {
							$from_user_id = $from_user['user_id'];
							// woo!! found a user. assign this customer to the ticket.
							if ( $from_user['customer_id'] ) {
								$ticket_account['default_customer_id'] = $from_user['customer_id'];
							}
						} else {
							// create a user under this account customer.
							$default_customer_id = 0;
							if ( $ticket_account && $ticket_account['default_customer_id'] ) {
								$default_customer_id = $ticket_account['default_customer_id'];
							}
							// create a new support user! go go!
							if ( strlen( $name ) ) {
								$bits       = explode( ' ', $name );
								$first_name = array_shift( $bits );
								$last_name  = implode( ' ', $bits );
							} else {
								$first_name = $email;
								$last_name  = '';
							}
							$from_user = array(
								'name'        => $first_name,
								'last_name'   => $last_name,
								'customer_id' => $default_customer_id,
								'email'       => $email,
								'status_id'   => 1,
								'password'    => substr( md5( time() . mt_rand( 0, 600 ) ), 3, 7 ),
							);
							global $plugins;
							$from_user_id = $plugins['user']->create_user( $from_user );
							// todo: set the default role for this user
							// based on the settings
							/*}else{
                                        echo 'Failed - no from accoutn set';
                                        return;
                                    }*/
						}

						if ( ! $from_user_id ) {
							echo 'Failed - cannot find the from user id';
							echo $email . ' to support<hr>';

							return;
						}

						// what type of ticket is this?
						$public_types   = $this->get_types( true );
						$ticket_type_id = $ticket_account['default_type'];
						if ( isset( $_POST['ticket_type_id'] ) && isset( $public_types[ $_POST['ticket_type_id'] ] ) ) {
							$ticket_type_id = $_POST['ticket_type_id'];
						}
						//                                echo $ticket_type_id;exit;

						$ticket_data = array(
							'user_id'                 => $from_user_id,
							'force_logged_in_user_id' => $from_user_id,
							'assigned_user_id'        => $ticket_account['default_user_id'] ? $ticket_account['default_user_id'] : 0,
							//module_config::c('ticket_default_user_id',1),
							'ticket_type_id'          => $ticket_type_id,
							'customer_id'             => $ticket_account['default_customer_id'],
							'status_id'               => 2,
							'ticket_account_id'       => $ticket_account_id,
							'unread'                  => 1,
							'subject'                 => $_POST['subject'],
							'new_ticket_message'      => $_POST['new_ticket_message'],
							'ticket_extra'            => isset( $_POST['ticket_extra'] ) && is_array( $_POST['ticket_extra'] ) ? $_POST['ticket_extra'] : array(),
							'faq_product_id'          => isset( $_POST['faq_product_id'] ) ? (int) $_POST['faq_product_id'] : 0,
						);
						if ( isset( $public_types[ $ticket_type_id ] ) && isset( $public_types[ $ticket_type_id ]['default_user_id'] ) && $public_types[ $ticket_type_id ]['default_user_id'] > 0 ) {
							$ticket_data['assigned_user_id'] = $public_types[ $ticket_type_id ]['default_user_id'];
						}
						if ( module_config::c( 'ticket_allow_priority_selection', 0 ) && isset( $_POST['priority'] ) ) {
							$priorities = $this->get_ticket_priorities();
							if ( isset( $priorities[ $_POST['priority'] ] ) ) {
								$ticket_data['priority'] = $_POST['priority'];
							}
						}
						$ticket_id = $this->save_ticket( 'new', $ticket_data );

						// set the default group if one exists for this particular ticket type.
						if ( $ticket_id && isset( $public_types[ $ticket_type_id ] ) && ! empty( $public_types[ $ticket_type_id ]['default_groups'] ) ) {
							$default_groups = @unserialize( $public_types[ $ticket_type_id ]['default_groups'] );
							if ( is_array( $default_groups ) && count( $default_groups ) ) {
								foreach ( $default_groups as $group_id ) {
									module_group::add_to_group( $group_id, $ticket_id, 'ticket' );
								}
							}
						}

						// check if they want a priority support
						if ( isset( $_POST['do_priority'] ) && $_POST['do_priority'] ) {
							// generate a "priority invoice" against this support ticket using the invoice module.
							// this will display the invoice in the sidebar and the user can pay.
							$this->generate_priority_invoice( $ticket_id );
						}

						handle_hook( 'ticket_public_created', $ticket_id );

						// where to redirect?
						$url = module_config::c( 'ticket_public_new_redirect', '' );
						if ( ! $url ) {
							$url = $this->link_public( $ticket_id );
						}

						redirect_browser( $url );

					}
				}

				$ticket = self::get_ticket( $ticket_id );
				include( 'public/ticket_customer_new.php' );

				break;
			case 'public_status':

				$ticket_id     = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$new_status_id = ( isset( $_REQUEST['s'] ) ) ? (int) $_REQUEST['s'] : false;
				$hash          = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $ticket_id && $new_status_id && $hash ) {
					$correct_hash = $this->link_public_status( $ticket_id, $new_status_id, true );
					if ( $correct_hash == $hash ) {
						// change the status.
						update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'status_id' => $new_status_id ) );
						module_template::init_template( 'ticket_status_change', '<h2>Ticket</h2>
<p>Thank you. Your support ticket status has been adjusted.</p>
<p>Please <a href="{TICKET_URL}">click here</a> to view your ticket.</p>
', 'Displayed after an external ticket status is changed.', 'code' );
						// correct!
						// load up the receipt template.
						$template = module_template::get_template_by_key( 'ticket_status_change' );

						$data                 = $this->get_ticket( $ticket_id );
						$data['ticket_url']   = $this->link_public( $ticket_id );
						$template->page_title = _l( "Ticket" );

						$template->assign_values( self::get_replace_fields( $ticket_id, $data ) );
						$template->assign_values( $data );
						echo $template->render( 'pretty_html' );
					}
				}
				exit;
				break;
			case 'public':

				$ticket_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash      = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $ticket_id && $hash ) {
					$correct_hash = $this->link_public( $ticket_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$ticket = $this->get_ticket( $ticket_id );

						if ( isset( $_POST['_process'] ) && $_POST['_process'] == 'send_public_ticket' ) {
							// user is saving the ticket.
							// process it!
							if ( isset( $_POST['new_ticket_message'] ) && strlen( $_POST['new_ticket_message'] ) > 1 ) {
								// post a new reply to this message.
								// who are we replying to?
								// it's either a reply from the admin, or from the user via the web interface.
								$ticket_creator    = $ticket['user_id'];
								$to_user_id        = $ticket['assigned_user_id'] ? $ticket['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 );
								$ticket_message_id = $this->send_reply( $ticket_id, $_POST['new_ticket_message'], $ticket_creator, $to_user_id, 'end_user' );

								/*$new_status_id = $ticket['status_id'];
                                    if($ticket['status_id']>=6){
                                        // it's cancelled or resolved.
                                    }*/
								$new_status_id = 5;
								if ( $ticket_message_id ) {
									// so we can track a history of ticket status changes
									update_insert( "ticket_message_id", $ticket_message_id, "ticket_message", array( 'status_id' => $new_status_id ) );
								}
								update_insert( "ticket_id", $ticket_id, "ticket", array(
									'unread'    => 1,
									'status_id' => $new_status_id
								) );
							}

							if ( isset( $_REQUEST['generate_priority_invoice'] ) ) {
								$invoice_id = $this->generate_priority_invoice( $ticket_id );
								redirect_browser( module_invoice::link_public( $invoice_id ) );
							}

							// where to redirect?
							$url = module_config::c( 'ticket_public_reply_redirect', '' );
							if ( ! $url ) {
								$url = $this->link_public( $ticket_id );
							}

							redirect_browser( $url );

						}


						if ( $ticket && $ticket['ticket_id'] == $ticket_id ) {


							$admins_rel = self::get_ticket_staff_rel();
							/*if(!isset($logged_in_user) || !$logged_in_user){
                                    // we assume the user is on the public side.
                                    // use the creator id as the logged in id.
                                    $logged_in_user = module_security::get_loggedin_id();
                                }*/
							// public hack, we are the ticket responder.
							$logged_in_user = $ticket['user_id'];

							$ticket_creator = $ticket['user_id'];
							if ( $ticket_creator == $logged_in_user ) {
								// we are sending a reply back to the admin, from the end user.
								$to_user_id   = $ticket['assigned_user_id'] ? $ticket['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 );
								$from_user_id = $logged_in_user;
							} else {
								// we are sending a reply back to the ticket user.
								$to_user_id   = $ticket['user_id'];
								$from_user_id = $logged_in_user;
							}
							$to_user_a   = module_user::get_user( $to_user_id, false );
							$from_user_a = module_user::get_user( $from_user_id, false );

							if ( isset( $ticket['ticket_account_id'] ) && $ticket['ticket_account_id'] ) {
								$ticket_account = module_ticket::get_ticket_account( $ticket['ticket_account_id'] );
							} else {
								$ticket_account = false;
							}

							if ( $ticket_account && $ticket_account['email'] ) {
								$reply_to_address = $ticket_account['email'];
								$reply_to_name    = $ticket_account['name'];
							} else {
								// reply to creator.
								$reply_to_address = $from_user_a['email'];
								$reply_to_name    = $from_user_a['name'];
							}


							if ( $ticket_creator == $logged_in_user ) {
								$send_as_name    = $from_user_a['name'];
								$send_as_address = $from_user_a['email'];
							} else {
								$send_as_address = $reply_to_address;
								$send_as_name    = $reply_to_name;
							}

							$admins_rel = self::get_ticket_staff_rel();

							ob_start();
							include( 'public/ticket_customer_view.php' );
							$html = ob_get_clean();

							module_template::init_template( 'external_ticket_public_view', '{TICKET_HTML}', 'Used when displaying the external view of a ticket to the customer.', 'code' );
							$template = module_template::get_template_by_key( 'external_ticket_public_view' );
							$template->assign_values( array(
								'ticket_html' => $html,
							) );
							$template->page_title = _l( 'Ticket: %s', module_ticket::ticket_number( $ticket['ticket_id'] ) );

							echo $template->render( 'pretty_html' );
							exit;

						} else {
							_e( 'Permission Denied. Please logout and try again.' );
						}
					}
				}
				break;
		}
	}


	public static function ticket_number( $id ) {
		$id = (int) $id;
		if ( ! $id ) {
			return _l( 'New' );
		}

		return str_pad( $id, 6, '0', STR_PAD_LEFT );
	}

	// will return the total ticket count if given no parameters.
	// if given a ticket id then faq_product_id is ignored.
	//if given a faq_product_id and no ticket id it will show only for that product
	// if given a ticket_id then faq_product_id is pulled from ticket read, and priority is pulled as well.
	public static function ticket_position( $ticket_id = false, $faq_product_id = false ) {
		$ordering = module_config::c( 'ticket_ordering', 'latest_message_last' );
		if ( $ticket_id ) {
			// want a count of all tickets above this one.
			$ticket_data = self::get_ticket( $ticket_id, 2 ); // this gets basic data
			if ( $ticket_data && $ticket_data['ticket_id'] == $ticket_id ) {
				$faq_product_id = ! $faq_product_id ? $ticket_data['faq_product_id'] : $faq_product_id;
				$sql            = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` t WHERE t.status_id < " . _TICKET_STATUS_RESOLVED_ID . "";
				// find tickets that are above or equal to this priority
				if ( $faq_product_id && module_config::c( 'ticket_separate_product_queue', 0 ) ) {
					$sql .= "  AND ( t.faq_product_id = " . (int) $faq_product_id . "";
					/*if(module_config::c('ticket_separate_product_queue_incempty',1)){
                        $sql .= " OR t.faq_product_id = 0 ";
                    }*/
					$sql .= " ) ";
				}
				$sql .= "  AND ( t.priority > " . (int) $ticket_data['priority'] . " OR ( t.priority = " . (int) $ticket_data['priority'] . " ";
				switch ( $ordering ) {
					case 'unread_first':
					case 'ticket_id':
						$sql .= " AND t.ticket_id <= " . (int) $ticket_id . "";
						break;
					case 'latest_message_last':
					default:
						$sql .= " AND t.last_message_timestamp <= " . (int) $ticket_data['last_message_timestamp'] . "";
						break;
				}
				$sql     .= " )";
				$sql     .= " )";
				$current = qa1( $sql, false );
				if ( $total = module_cache::get( 'ticket', 'unresolved_count_' . $faq_product_id ) ) {
					// good.
				} else {
					$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` t WHERE (t.status_id < " . _TICKET_STATUS_RESOLVED_ID . "";
					if ( $faq_product_id && module_config::c( 'ticket_separate_product_queue', 0 ) ) {
						$sql .= "  AND t.faq_product_id = " . (int) $faq_product_id . "";
					}
					$sql   .= " )";
					$total = qa1( $sql, false );
					module_cache::put( 'ticket', 'unresolved_count_' . $faq_product_id, $total );
				}

				return array(
					'current' => $current['c'],
					'total'   => $total['c']
				);
			}
		} else if ( $faq_product_id ) {
			// want a count of all tickets that have this faq_product_id
			$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` t WHERE (t.status_id < " . _TICKET_STATUS_RESOLVED_ID . "";
			if ( $faq_product_id && module_config::c( 'ticket_separate_product_queue', 0 ) ) {
				$sql .= "  AND ( t.faq_product_id = " . (int) $faq_product_id . "";
				/*if(module_config::c('ticket_separate_product_queue_incempty',1)){
                    $sql .= " OR t.faq_product_id = 0 ";
                }*/
				$sql .= " ) ";
			}
			$sql     .= " )";
			$current = qa1( $sql, false );

			return array(
				'current' => $current['c'],
				'total'   => $current['c']
			);
		} else {
			// just a count on all tickets.
			$x = self::get_total_ticket_count();

			return array(
				'current' => $x,
				'total'   => $x,
			);
		}
	}

	/** old ticket_count method, we're slowly moving to the new one (above) that will better handle our new features (eg: different queue per product) */
	public static function ticket_count( $type, $time = false, $ticket_id = false, $ticket_priority = false ) {
		switch ( $type ) {
			case 'paid':
			case 'priority':
				$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` t WHERE (t.status_id < " . _TICKET_STATUS_RESOLVED_ID . " AND t.priority = " . _TICKET_PRIORITY_STATUS_ID;
				switch ( module_config::c( 'ticket_ordering', 'latest_message_last' ) ) {
					case 'unread_first':
					case 'ticket_id':
						if ( $ticket_id ) {
							$sql .= " AND t.ticket_id <= " . (int) $ticket_id . "";
						}
						break;
					case 'latest_message_last':
					default:
						if ( $time ) {
							$sql .= " AND t.last_message_timestamp <= " . (int) $time . "";
						}
						break;
				}

				$sql .= " )";
				$res = qa1( $sql );

				return $res['c'];
			default:
				$sql = "SELECT COUNT(ticket_id) AS c FROM `" . _DB_PREFIX . "ticket` t WHERE (t.status_id < " . _TICKET_STATUS_RESOLVED_ID;
				// we filter by the priority id too.
				if ( $ticket_priority ) {
					$sql .= " AND ( t.priority > " . (int) $ticket_priority . " OR ( t.priority = " . (int) $ticket_priority . " ";
				}
				switch ( module_config::c( 'ticket_ordering', 'latest_message_last' ) ) {
					case 'unread_first':
					case 'ticket_id':
						if ( $ticket_id ) {
							$sql .= " AND t.ticket_id <= " . (int) $ticket_id . "";
						}
						break;
					case 'latest_message_last':
					default:
						if ( $time ) {
							$sql .= " AND t.last_message_timestamp <= " . (int) $time . "";
						}
						break;
				}
				if ( $ticket_priority ) {
					$sql .= " ) ) ";
				}
				$sql .= " )";
				$res = qa1( $sql, false ); // fix bug with 20 out of 19.

				return $res['c'];
		}
	}


	public function process() {
		$errors = array();
		if ( 'save_saved_response' == $_REQUEST['_process'] ) {

			$data              = array(
				'value' => $_REQUEST['value'],
			);
			$saved_response_id = (int) $_REQUEST['saved_response_id'];
			if ( (string) $saved_response_id != (string) $_REQUEST['saved_response_id'] ) {
				// we are saving a new response, not overwriting an old one.
				$data['name']      = $_REQUEST['saved_response_id'];
				$saved_response_id = 'new';
			} else {
				// overwriting an old one.
			}
			$this->save_saved_response( $saved_response_id, $data );
			// saved via ajax
			exit;

		} else if ( 'insert_saved_response' == $_REQUEST['_process'] ) {

			$x = 1;
			while ( $x ++ < 10 && ob_get_level() ) {
				ob_end_clean();
			}
			$response = $this->get_saved_response( $_REQUEST['saved_response_id'] );
			echo json_encode( $response );
			exit;

		} else if ( 'save_ticket_type' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				die( 'No perms to save ticket settings.' );
			}

			$ticket_type_id = update_insert( 'ticket_type_id', $_REQUEST['ticket_type_id'], 'ticket_type', $_POST );
			if ( isset( $_REQUEST['butt_del'] ) ) {
				// deleting ticket type all together
				delete_from_db( 'ticket_type', 'ticket_type_id', $_REQUEST['ticket_type_id'] );
				set_message( 'Ticket type deleted successfully.' );
				redirect_browser( $this->link_open_type( false ) );
			}
			set_message( 'Ticket type saved successfully' );
			redirect_browser( $this->link_open_type( $ticket_type_id ) );


		} else if ( 'save_ticket_data_key' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				die( 'No perms to save ticket settings.' );
			}

			$data = $_POST;
			if ( isset( $data['options'] ) ) {
				$options = array();
				foreach ( explode( "\n", $data['options'] ) as $line ) {
					$line = trim( $line );
					if ( strlen( $line ) > 0 ) {
						$bits = explode( '|', $line );
						$key  = $bits[0];
						if ( count( $bits ) == 2 ) {
							$val = $bits[1];
						} else {
							$val = $bits[0];
						}
						$options[ $key ] = $val;
					}
				}
				$data['options'] = serialize( $options );
			}

			$ticket_data_key_id = update_insert( 'ticket_data_key_id', $_REQUEST['ticket_data_key_id'], 'ticket_data_key', $data );
			if ( isset( $_REQUEST['butt_del'] ) ) {
				// deleting ticket data_key all together
				delete_from_db( 'ticket_data_key', 'ticket_data_key_id', $_REQUEST['ticket_data_key_id'] );
				set_message( 'Ticket field deleted successfully.' );
				redirect_browser( $this->link_open_field( false ) );
			}
			set_message( 'Ticket field saved successfully' );
			redirect_browser( $this->link_open_field( $ticket_data_key_id ) );


		} else if ( 'save_ticket_account' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				die( 'No perms to save ticket settings.' );
			}
			$ticket_account_id = update_insert( 'ticket_account_id', $_REQUEST['ticket_account_id'], 'ticket_account', $_POST );
			if ( isset( $_REQUEST['butt_save_test'] ) ) {
				?> <a href="<?php echo $this->link_open_account( $ticket_account_id ); ?>">Return to account settings</a><br>
				<br> <?php
				self::import_email( $ticket_account_id, false, true );
				exit;
			} else if ( isset( $_REQUEST['butt_del'] ) ) {
				// deleting ticket account all together
				delete_from_db( 'ticket_account', 'ticket_account_id', $_REQUEST['ticket_account_id'] );
				set_message( 'Ticket account deleted successfully.' );
				redirect_browser( $this->link_open_account( false ) );
			}
			set_message( 'Ticket account saved successfully' );
			redirect_browser( $this->link_open_account( $ticket_account_id ) );

		} else if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['ticket_id'] ) {
			$data = self::get_ticket( $_REQUEST['ticket_id'] );
			if ( module_form::confirm_delete( 'ticket_id', "Really delete ticket: " . $this->ticket_number( $data['ticket_id'] ), self::link_open( $_REQUEST['ticket_id'] ) ) ) {
				$this->delete_ticket( $_REQUEST['ticket_id'] );
				set_message( "Ticket deleted successfully" );
				$url = $this->link_open( false );
				$url .= ( strpos( '?', $url ) !== false ? '?' : '&' ) . 'do_last_search';
				redirect_browser( $url );
			}
		} else if ( "save_ticket" == $_REQUEST['_process'] ) {
			$this->_handle_save_ticket();


		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}


	public static function get_tickets( $search = array(), $message_count = false ) {


		/*$cache_key_args = func_get_args();
        $cache_key = self::_ticket_cache_key('search', $cache_key_args);
        $cache_timeout = module_config::c('cache_objects',60);
        if($cached_item = module_cache::get('ticket',$cache_key)){
            return $cached_item;
        }*/

		// work out what customers this user can access?
		$ticket_access = self::get_ticket_data_access();

		$sql = "SELECT t.* ";
		if ( $message_count ) {
			$sql .= ", COUNT(tm.ticket_message_id) AS message_count ";
		}
		$sql  .= ", tt.`name` AS `ticket_type`";
		$from = " FROM `" . _DB_PREFIX . "ticket` t ";
		if ( $message_count || isset( $search['ticket_content'] ) ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "ticket_message` tm ON t.ticket_id = tm.ticket_id ";
		}
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "ticket_type` tt ON t.ticket_type_id = tt.ticket_type_id";
		$where = " WHERE 1 ";
		if ( isset( $search['ticket_content'] ) && strlen( trim( $search['ticket_content'] ) ) ) {
			$str   = db_escape( trim( $search['ticket_content'] ) );
			$where .= " AND ( tm.`content` LIKE '%$str%' OR tm.`htmlcontent` LIKE '%$str%' ) ";
		}
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str     = db_escape( $search['generic'] );
			$where   .= " AND ( ";
			$where   .= " t.subject LIKE '%$str%' ";
			$id_test = trim( ltrim( $search['generic'], '0' ) );
			if ( is_numeric( $id_test ) && (int) $id_test > 0 ) {
				$where .= " OR t.ticket_id LIKE '%" . (int) $id_test . "%' ";
			}
			$where .= ' ) ';
		}
		if ( isset( $search['time_from'] ) && $search['time_from'] ) {
			$str   = (int) $search['time_from'];
			$where .= " AND ( ";
			$where .= " t.last_message_timestamp >= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_from'] ) && $search['date_from'] ) {
			$str   = strtotime( input_date( $search['date_from'] ) );
			$where .= " AND ( ";
			$where .= " t.last_message_timestamp >= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_to'] ) && $search['date_to'] ) {
			$str   = strtotime( input_date( $search['date_to'] . ' 23:59:59', true ) );
			$where .= " AND ( ";
			$where .= " t.last_message_timestamp <= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['ticket_id'] ) ) {
			$search['ticket_id'] = trim( ltrim( $search['ticket_id'], '0' ) );
		}
		/*if(isset($search['status_id']) && $search['status_id'] == -1){
            $where .= ' AND ( t.`status_id` = 2 OR t.`status_id` = 3 OR t.`status_id` = 5 ) ';
            unset($search['status_id']);
        }*/

		if ( isset( $search['status_id'] ) && strpos( $search['status_id'], ',' ) !== false ) {
			$where .= ' AND ( ';
			foreach ( explode( ',', $search['status_id'] ) as $s ) {
				$s = (int) trim( $s );
				if ( ! $s ) {
					continue;
				}
				$where .= ' t.`status_id` = ' . $s . ' OR ';
			}
			$where = rtrim( $where, ' OR ' );
			$where .= ' ) ';
			unset( $search['status_id'] );
		} else if ( isset( $search['status_id'] ) && strpos( $search['status_id'], '<' ) !== false ) {
			$search['status_id'] = ltrim( $search['status_id'], '<' );
			if ( (int) $search['status_id'] > 0 ) {
				$where .= ' AND t.`status_id` < ' . (int) $search['status_id'] . ' ';
			}
			unset( $search['status_id'] );
		}
		if ( isset( $search['contact'] ) && strlen( trim( $search['contact'] ) ) ) {
			$search['contact'] = trim( $search['contact'] );
			$from              .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON t.user_id = u.user_id ";
			if ( class_exists( 'module_envato', false ) ) {
				$from .= " LEFT JOIN `" . _DB_PREFIX . "envato_ticket` et ON t.ticket_id = et.ticket_id ";
				$from .= " LEFT JOIN `" . _DB_PREFIX . "envato_author` ea ON et.envato_author_id = ea.envato_author_id ";
			}
			$where .= " AND ( ";
			$where .= " u.email LIKE '%" . db_escape( $search['contact'] ) . "%' ";
			$where .= " OR u.name LIKE '%" . db_escape( $search['contact'] ) . "%' ";
			if ( class_exists( 'module_envato', false ) ) {
				$where .= " OR ea.envato_username LIKE '%" . db_escape( $search['contact'] ) . "%' ";
			}
			$where .= " )";
		}
		if ( isset( $search['envato_item_id'] ) && is_array( $search['envato_item_id'] ) ) {
			// the new multi-select envato item id serach.
			$from              .= " LEFT JOIN `" . _DB_PREFIX . "envato_ticket` et ON t.ticket_id = et.ticket_id ";
			$envato_item_where = '';
			foreach ( $search['envato_item_id'] as $envato_item_id ) {
				$envato_item_id = (int) $envato_item_id;
				if ( $envato_item_id > 0 ) {
					$envato_item_where .= " et.envato_item_id = " . (int) $envato_item_id . " OR ";
				} else if ( $envato_item_id == - 1 ) {
					$envato_item_where .= " et.envato_item_id IS NULL OR ";
				}
			}
			if ( strlen( $envato_item_where ) ) {
				$envato_item_where = rtrim( $envato_item_where, ' OR' );
				$where             .= " AND (" . $envato_item_where . ")";
			}
		} else if ( isset( $search['envato_item_id'] ) && strlen( trim( $search['envato_item_id'] ) ) ) {
			$search['envato_item_id'] = (int) $search['envato_item_id'];
			$from                     .= " LEFT JOIN `" . _DB_PREFIX . "envato_ticket` et ON t.ticket_id = et.ticket_id ";
			$where                    .= " AND ( ";
			$where                    .= " et.envato_item_id = '" . $search['envato_item_id'] . "'";
			$where                    .= " )";
		}
		if ( isset( $search['status_id'] ) && ! $search['status_id'] ) {
			unset( $search['status_id'] );//hack
		}
		foreach (
			array(
				'user_id',
				'assigned_user_id',
				'customer_id',
				'website_id',
				'ticket_id',
				'status_id',
				'unread',
				'ticket_type_id',
				'priority',
				'faq_product_id'
			) as $key
		) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND t.`$key` = '$str'";
			}
		}
		switch ( $ticket_access ) {
			case _TICKET_ACCESS_ALL:

				break;
			case _TICKET_ACCESS_ASSIGNED:
				// we only want tickets assigned to me.
				$where .= " AND (t.assigned_user_id = '" . (int) module_security::get_loggedin_id() . "' OR t.assigned_user_id = 0)";
				break;
			case _TICKET_ACCESS_CREATED:
				// we only want tickets i created.
				$where .= " AND t.user_id = '" . (int) module_security::get_loggedin_id() . "'";
				break;
			case _TICKET_ACCESS_GROUP:
				// we only want tickets from the groups I have access to.
				$from            .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (t.ticket_id = gm.owner_id)";
				$valid_group_ids = module_security::get_group_restrictions( 'ticket' );
				$where           .= " AND (gm.group_id IN (" . implode( ',', $valid_group_ids ) . ") AND gm.owner_table = 'ticket')";
				break;
			case _TICKET_ACCESS_CUSTOMER:
				$valid_customer_ids = module_security::get_customer_restrictions();
				if ( is_array( $valid_customer_ids ) && count( $valid_customer_ids ) ) {
					$where .= " AND t.customer_id IN ( ";
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
					$where .= " )";
				}
				break;
		}
		// want multiple options for ordering.
		switch ( module_config::c( 'ticket_ordering', 'latest_message_last' ) ) {
			case 'unread_first':
				$group_order = ' GROUP BY t.ticket_id ORDER BY t.priority DESC, t.unread DESC, t.ticket_id ASC';
				break;
			case 'ticket_id':
				$group_order = ' GROUP BY t.ticket_id ORDER BY t.priority DESC, t.ticket_id ASC';
				break;
			case 'latest_message_first':
				$group_order = ' GROUP BY t.ticket_id ORDER BY t.priority DESC, t.last_message_timestamp DESC'; // t.unread DESC,
				break;
			case 'latest_message_last':
			default:
				$group_order = ' GROUP BY t.ticket_id ORDER BY t.priority DESC, t.last_message_timestamp ASC'; // t.unread DESC,
				break;
		}

		$sql = $sql . $from . $where . $group_order;
		//        echo $sql;exit;
		$result = query( $sql );

		//module_security::filter_data_set("ticket",$result);
		return $result;
		//return get_multiple("ticket",$search,"ticket_id","fuzzy","last_message_timestamp DESC");

	}

	public static function get_ticket_messages( $ticket_id, $as_resource = false ) {
		if ( ! (int) $ticket_id ) {
			return $as_resource ? false : array();
		}
		$sql = "SELECT * FROM `" . _DB_PREFIX . "ticket_message` WHERE ticket_id = " . (int) $ticket_id;
		if ( self::can_edit_tickets() ) {
			$sql .= '';
		} else {
			$sql .= ' AND `private_message` = 0 ';
		}
		$sql .= ' ORDER BY `ticket_message_id` ';
		if ( $as_resource ) {
			return query( $sql );
		}

		return qa( $sql );
		//return get_multiple("ticket_message",array('ticket_id'=>$ticket_id),"ticket_message_id","exact","ticket_message_id",true);

	}

	public static function get_ticket_message( $ticket_message_id ) {
		return get_single( 'ticket_message', 'ticket_message_id', $ticket_message_id );
	}

	public static function get_ticket_message_attachments( $ticket_message_id ) {
		return get_multiple( "ticket_message_attachment", array( 'ticket_message_id' => $ticket_message_id ), "ticket_message_attachment_id", "exact", "ticket_message_attachment_id" );

	}

	public static function get_accounts() {
		return get_multiple( "ticket_account", false, "ticket_account_id" );

	}

	public static function get_accounts_rel() {
		$res = array();
		foreach ( self::get_accounts() as $row ) {
			$res[ $row['ticket_account_id'] ] = $row['name'];
		}

		return $res;
	}

	public static function get_ticket_staff() {
		$admins = module_user::get_users_by_permission(
			array(
				'category' => 'Ticket',
				'name'     => 'Tickets',
				'module'   => 'ticket',
				'edit'     => 1,
			)

		);

		return $admins;
	}

	public static function get_ticket_staff_rel() {
		$admins     = self::get_ticket_staff();
		$admins_rel = array();
		foreach ( $admins as $admin ) {
			$admins_rel[ $admin['user_id'] ] = $admin['name'];
		}

		return $admins_rel;
	}

	public static function get_ticket_account( $ticket_account_id ) {
		$ticket_account_id = (int) $ticket_account_id;
		$ticket_account    = false;
		if ( $ticket_account_id > 0 ) {
			$ticket_account = get_single( "ticket_account", "ticket_account_id", $ticket_account_id );
		}

		return $ticket_account;
	}

	private static function _ticket_cache_key( $ticket_id, $args = array() ) {
		return 'ticket_' . $ticket_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) . '_' . ( isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : 0 ) );
	}

	public static function get_ticket( $ticket_id, $full = true ) {

		$cache_key_args = func_get_args();
		$cache_key      = self::_ticket_cache_key( $ticket_id, $cache_key_args );
		$cache_timeout  = module_config::c( 'cache_objects', 60 );
		if ( $cached_item = module_cache::get( 'ticket', $cache_key ) ) {
			return $cached_item;
		}

		$ticket_access = self::get_ticket_data_access();

		$ticket_id = (int) $ticket_id;
		$ticket    = false;
		if ( $ticket_id > 0 ) {
			//$ticket = get_single("ticket","ticket_id",$ticket_id);
			$sql   = "SELECT * FROM `" . _DB_PREFIX . "ticket` t";
			$where = " WHERE t.ticket_id = $ticket_id ";
			switch ( $ticket_access ) {
				case _TICKET_ACCESS_ALL:

					break;
				case _TICKET_ACCESS_ASSIGNED:
					// we only want tickets assigned to me.
					//$sql .= " AND t.assigned_user_id = '".(int)module_security::get_loggedin_id()."'";
					$where .= " AND (t.assigned_user_id = '" . (int) module_security::get_loggedin_id() . "' OR t.assigned_user_id = 0)";
					break;
				case _TICKET_ACCESS_CREATED:
					// we only want tickets I created.
					$where .= " AND t.user_id = '" . (int) module_security::get_loggedin_id() . "'";
					break;
				case _TICKET_ACCESS_GROUP:
					// we only want tickets from the groups I have access to.
					$sql             .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (t.ticket_id = gm.owner_id)";
					$valid_group_ids = module_security::get_group_restrictions( 'ticket' );
					$where           .= " AND (gm.group_id IN (" . implode( ',', $valid_group_ids ) . ") AND gm.owner_table = 'ticket')";
					break;
				case _TICKET_ACCESS_CUSTOMER:
					$valid_customer_ids = module_security::get_customer_restrictions();
					if ( is_array( $valid_customer_ids ) && count( $valid_customer_ids ) ) {
						$where .= " AND ( ";
						foreach ( $valid_customer_ids as $valid_customer_id ) {
							$where .= " t.customer_id = '" . (int) $valid_customer_id . "' OR ";
						}
						$where = rtrim( $where, 'OR ' );
						$where .= " )";
					}
					break;
			}
			$ticket = qa1( $sql . $where, false );
		}
		if ( $full === 2 ) {
			module_cache::put( 'ticket', $cache_key, $ticket, $cache_timeout );

			return $ticket;
		}

		if ( ! $ticket ) {
			$customer_id = $website_id = 0;
			$user_id     = module_security::get_loggedin_id();
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] ) {
				//
				$customer_id = (int) $_REQUEST['customer_id'];
				$customer    = module_customer::get_customer( $customer_id );
				if ( ! $customer || $customer['customer_id'] != $customer_id ) {
					$customer_id = 0;
				} else {
					$user_id = $customer['primary_user_id'];
				}
				// find default website id to use.
				if ( isset( $_REQUEST['website_id'] ) ) {
					$website_id = (int) $_REQUEST['website_id'];
					$website    = module_website::get_website( $website_id );
					if ( ! $website || $website['website_id'] != $website_id || $website['customer_id'] != $customer_id ) {
						$website_id = 0;
					}
				} else {
					$website_id = 0;
				}
			}
			$position = self::ticket_position();
			$ticket   = array(
				'ticket_id'              => 'new',
				'customer_id'            => $customer_id,
				'website_id'             => $website_id,
				'subject'                => '',
				'date_completed'         => '',
				'status_id'              => _TICKET_STATUS_NEW_ID,
				// new
				'user_id'                => $user_id,
				'assigned_user_id'       => module_config::c( 'ticket_default_user_id', 1 ),
				// who is the default assigned user?
				'ticket_account_id'      => module_config::c( 'ticket_default_account_id', 0 ),
				// default pop3 account for pro users.
				'last_message_timestamp' => 0,
				'last_ticket_message_id' => 0,
				'message_count'          => 0,
				'position'               => $position['current'] + 1,
				'priority'               => 0,
				// 0, 1, 2, etc...
				'ticket_type_id'         => module_config::c( 'ticket_type_id_default', 0 ),
				'total_pending'          => $position['total'] + 1,
				'extra_data'             => array(),
				'invoice_id'             => false,
				'faq_product_id'         => false,
				'due_timestamp'          => strtotime( '+' . module_config::c( 'ticket_turn_around_days', 5 ) . ' days', time() ),
			);

		} else {
			// find the position of this ticket
			// the position is determined by the number of pending tickets
			// that have a last_message_timestamp earlier than this ticket.

			$position                = self::ticket_position( $ticket_id );
			$ticket['position']      = $position['current'];
			$ticket['total_pending'] = $position['total'];

			/*if($ticket['priority'] == _TICKET_PRIORITY_STATUS_ID){
                $ticket['position'] = self::ticket_count('priority',$ticket['last_message_timestamp'],$ticket['ticket_id'],$ticket['priority']);
            }else{
                $ticket['position'] = self::ticket_count('pending',$ticket['last_message_timestamp'],$ticket['ticket_id'],$ticket['priority']);
            }
            $ticket['total_pending'] = self::ticket_count('pending');*/
			$messages = self::get_ticket_messages( $ticket_id, true );
			//$ticket['message_count'] = count($messages);
			$ticket['message_count'] = mysqli_num_rows( $messages );
			//end($messages);
			if ( $ticket['message_count'] > 0 ) {
				mysqli_data_seek( $messages, $ticket['message_count'] - 1 );
			}
			//$last_message = current($messages);
			$last_message                       = mysqli_fetch_assoc( $messages );
			$ticket['last_ticket_message_id']   = $last_message['ticket_message_id'];
			$ticket['last_message_was_private'] = isset( $last_message['private_message'] ) && $last_message['private_message'];
			// for passwords and website addresses..
			$ticket['extra_data'] = self::get_ticket_extras( $ticket_id );

			// hook into the envato module.
			// link any missing envato/faqproduct items together.
			if ( class_exists( 'module_envato', false ) && isset( $_REQUEST['faq_product_envato_hack'] ) && ( ! $ticket['faq_product_id'] || $ticket['faq_product_id'] == $_REQUEST['faq_product_envato_hack'] ) ) {
				$items = module_envato::get_items_by_ticket( $ticket['ticket_id'] );
				foreach ( $items as $envato_item_id => $item ) {
					// see if this item is linked to a product.
					if ( $item['envato_item_id'] ) {
						$sql = "SELECT * FROM `" . _DB_PREFIX . "faq_product` WHERE envato_item_ids REGEXP '[|]*" . $envato_item_id . "[|]*'";
						$res = qa1( $sql );
						if ( $res && $res['faq_product_id'] ) {
							// found a product matching this one. link her up.
							update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'faq_product_id' => $res['faq_product_id'] ) );
							break;
						}
					}
				}
			}

		}
		module_cache::put( 'ticket', $cache_key, $ticket, $cache_timeout );

		return $ticket;
	}


	public static function get_ticket_type( $ticket_type_id = 0 ) {
		return get_single( 'ticket_type', 'ticket_type_id', $ticket_type_id );
	}

	public static function get_ticket_extras_key( $ticket_data_key_id = 0 ) {
		return get_single( 'ticket_data_key', 'ticket_data_key_id', $ticket_data_key_id );
	}

	public static function get_ticket_extras_keys( $ticket_account_id = 0 ) {
		//array('ticket_account_id'=>$ticket_account_id)
		return get_multiple( 'ticket_data_key', array(), 'ticket_data_key_id', 'exact', 'order' );
	}

	public static function get_ticket_extras( $ticket_id ) {
		return get_multiple( 'ticket_data', array( 'ticket_id' => $ticket_id ), 'ticket_data_key_id' );
	}

	public static function mark_as_read( $ticket_id, $credential_check = false ) {
		$ticket_id = (int) $ticket_id;
		if ( $ticket_id > 0 ) {
			/*if($credential_check){
                $admins_rel = self::get_ticket_staff_rel();
                // we check what the last message is.
                $messages = self::get_ticket_messages($ticket_id);
                end($messages);
                $last_message = current($messages);
                // if the last message is from an admin:
                if($last_message['']);
                // FUCK. this isn't going to work.
                // will do it later.
            }*/
			update_insert( "ticket_id", $ticket_id, "ticket", array( 'unread' => 0 ) );
		}
	}

	public static function mark_as_unread( $ticket_id ) {
		$ticket_id = (int) $ticket_id;
		if ( $ticket_id > 0 ) {
			update_insert( "ticket_id", $ticket_id, "ticket", array( 'unread' => 1 ) );
		}
	}

	public function save_ticket( $ticket_id, $data ) {
		if ( isset( $data['website_id'] ) && $data['website_id'] ) {
			$website             = module_website::get_website( $data['website_id'] );
			$data['customer_id'] = $website['customer_id'];
		}
		if ( isset( $data['user_id'] ) && $data['user_id'] ) {
			$user = module_user::get_user( $data['user_id'], false );
			if ( ! isset( $data['customer_id'] ) || ! $data['customer_id'] ) {
				$data['customer_id'] = $user['customer_id'];
			}
		}
		if ( (int) $ticket_id > 0 ) {
			$existing_ticket_data = $this->get_ticket( $ticket_id );
		} else {
			$existing_ticket_data = array();
			// creating a new ticket.
			// populate the due timestamp
			if ( empty( $data['due_timestamp'] ) ) {
				$data['due_timestamp'] = strtotime( '+' . module_config::c( 'ticket_turn_around_days', 5 ) . ' days' );
			}
		}
		if ( isset( $data['change_assigned_user_id'] ) && (int) $data['change_assigned_user_id'] > 0 ) {
			// check if we're realling changing the user.
			if ( $ticket_id > 0 ) {
				if ( $existing_ticket_data['assigned_user_id'] != $data['change_assigned_user_id'] ) {
					// they are really changing the user
					$data['assigned_user_id'] = $data['change_assigned_user_id'];
				}
			} else {
				$data['assigned_user_id'] = $data['change_assigned_user_id'];
			}
			module_cache::clear( 'ticket' );
		}
		$ticket_id = update_insert( "ticket_id", $ticket_id, "ticket", $data );

		if ( $ticket_id ) {

			// save any extra data
			if ( isset( $data['ticket_extra'] ) && is_array( $data['ticket_extra'] ) ) {
				$available_extra_fields = $this->get_ticket_extras_keys();
				foreach ( $data['ticket_extra'] as $ticket_data_key_id => $ticket_data_key_value ) {
					if ( strlen( $ticket_data_key_value ) > 0 && isset( $available_extra_fields[ $ticket_data_key_id ] ) ) {
						// save this one!
						// hack: addition for encryption module.
						// bit nasty, but it works.
						if ( class_exists( 'module_encrypt', false ) && isset( $available_extra_fields[ $ticket_data_key_id ]['encrypt_key_id'] ) && $available_extra_fields[ $ticket_data_key_id ]['encrypt_key_id'] && strpos( $ticket_data_key_value, 'encrypt:' ) === false
						     &&
						     ( $available_extra_fields[ $ticket_data_key_id ]['type'] == 'text' || $available_extra_fields[ $ticket_data_key_id ]['type'] == 'textarea' )
						) {
							// encrypt this value using this key.
							$page_name             = 'ticket_extras'; // match the page_name we have in ticket_extra_sidebar.php
							$input_id              = 'ticket_extras_' . $ticket_data_key_id; // match the input id we have in ticket_extra_sidebar.php
							$ticket_data_key_value = module_encrypt::save_encrypt_value( $available_extra_fields[ $ticket_data_key_id ]['encrypt_key_id'], $ticket_data_key_value, $page_name, $input_id );
						}

						// check for existing
						$existing = get_single( 'ticket_data', array( 'ticket_id', 'ticket_data_key_id' ), array(
							$ticket_id,
							$ticket_data_key_id
						) );
						if ( $existing ) {
							update_insert( 'ticket_data_id', $existing['ticket_data_id'], 'ticket_data', array(
								'value' => $ticket_data_key_value,
							) );
						} else {
							update_insert( 'ticket_data_id', 'new', 'ticket_data', array(
								'ticket_data_key_id' => $ticket_data_key_id,
								'ticket_id'          => $ticket_id,
								'value'              => $ticket_data_key_value,
							) );
						}
					}
				}
			}

			$ticket_message_id = false;

			if ( isset( $data['new_ticket_message'] ) && strlen( $data['new_ticket_message'] ) > 1 ) {
				// post a new reply to this message.
				// who are we replying to?


				$ticket_data = $this->get_ticket( $ticket_id );

				if ( isset( $data['change_status_id'] ) && $data['change_status_id'] ) {
					update_insert( "ticket_id", $ticket_id, "ticket", array( 'status_id' => $data['change_status_id'] ) );
				} else if ( $ticket_data['status_id'] == _TICKET_STATUS_RESOLVED_ID || $ticket_data['status_id'] == 7 ) {
					$data['change_status_id'] = _TICKET_STATUS_IN_PROGRESS_ID; // change to in progress.
				}


				module_cache::clear( 'ticket' );
				// it's either a reply from the admin, or from the user via the web interface.
				$ticket_data = $this->get_ticket( $ticket_id );


				$logged_in_user = isset( $data['force_logged_in_user_id'] ) ? $data['force_logged_in_user_id'] : false;
				if ( ! $logged_in_user ) {
					$logged_in_user = module_security::get_loggedin_id();
					if ( ! $logged_in_user ) {
						$logged_in_user = $ticket_data['user_id'];
					}
				}

				if ( ! $ticket_data['user_id'] && module_security::get_loggedin_id() ) {
					update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'user_id' => module_security::get_loggedin_id() ) );
					$ticket_data['user_id'] = module_security::get_loggedin_id();
				}
				$ticket_creator = $ticket_data['user_id'];
				// echo "creator: $ticket_creator logged in: $logged_in_user"; print_r($ticket_data);exit;
				//echo "Creator: ".$ticket_data['user_id'] . " logged in ".$logged_in_user;exit;
				if ( $ticket_creator == $logged_in_user ) {
					// we are sending a reply back to the admin, from the end user.
					self::mark_as_unread( $ticket_id );
					$ticket_message_id = $this->send_reply( $ticket_id, $data['new_ticket_message'], $ticket_creator, $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 ), 'end_user', '', array(
						'private_message' => isset( $data['private_message'] ) && $data['private_message']
					) );
				} else {
					// we are sending a reply back to the ticket user.
					// admin is allowed to change the status of a message.
					$from_user_id = $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : module_security::get_loggedin_id();
					//echo "From $from_user_id to $ticket_creator ";exit;
					$ticket_message_id = $this->send_reply( $ticket_id, $data['new_ticket_message'], $from_user_id, $ticket_creator, 'admin', '', array(
						'private_message' => isset( $data['private_message'] ) && $data['private_message']
					) ); // do we add cc/bcc here?
				}

				if ( $ticket_message_id && isset( $data['change_status_id'] ) && $data['change_status_id'] ) {
					// store the ticket status change here.
					update_insert( "ticket_message_id", $ticket_message_id, "ticket_message", array( 'status_id' => $data['change_status_id'] ) );
				}
			}

			if ( isset( $data['change_status_id'] ) && $data['change_status_id'] ) {
				// we only update this status if the sent reply or send reply and next buttons are clicked.
				if ( isset( $_REQUEST['newmsg'] ) || isset( $_REQUEST['newmsg_next'] ) ) {
					update_insert( "ticket_id", $ticket_id, "ticket", array( 'status_id' => $data['change_status_id'] ) );
				}
			}

		}
		module_extra::save_extras( 'ticket', 'ticket_id', $ticket_id );


		// automaticall send notification email to assigned staff membeR?
		if ( module_config::c( 'ticket_auto_notify_staff', 0 ) ) {
			module_cache::clear( 'ticket' );
			$new_ticket_data = self::get_ticket( $ticket_id );

			if ( $new_ticket_data['assigned_user_id'] && ( ! $existing_ticket_data || $existing_ticket_data['assigned_user_id'] != $new_ticket_data['assigned_user_id'] ) ) {

				// copied from ticket_admin_notify.php

				// template for sending emails.
				// are we sending the paid one? or the dueone.
				$template                          = module_template::get_template_by_key( 'ticket_email_notify' );
				$new_ticket_data['from_name']      = module_security::get_loggedin_name();
				$new_ticket_data['ticket_url']     = module_ticket::link_open( $ticket_id );
				$new_ticket_data['ticket_subject'] = $new_ticket_data['subject'];

				// sending to the staff member.
				$replace_fields = self::get_replace_fields( $new_ticket_data['ticket_id'], $new_ticket_data );
				$template->assign_values( $replace_fields );
				$template->assign_values( $new_ticket_data );
				$html = $template->render( 'html' );

				$email                 = module_email::new_email();
				$email->replace_values = $new_ticket_data + $replace_fields;
				$email->set_subject( $template->description );
				$email->set_to( 'user', $new_ticket_data['assigned_user_id'] );
				// do we send images inline?
				$email->set_html( $html );
				if ( $email->send() ) {
					// it worked successfully!!
				} else {
					/// log err?
				}
			}

		}

		module_cache::clear( 'ticket' );

		return $ticket_id;
	}

	public static function get_replace_fields( $ticket_id, $ticket_data = array() ) {

		if ( ! $ticket_data ) {
			$ticket_data = module_ticket::get_ticket( $ticket_id );
		}
		$staff_user_id                = $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 );
		$to                           = module_user::get_user( $staff_user_id ); //$ticket_data['assigned_user_id']);
		$ticket_data['staff_name']    = $to['name'] . ' ' . $to['last_name'];
		$ticket_data['ticket_number'] = module_ticket::ticket_number( $ticket_data['ticket_id'] );

		$ticket_contact               = module_user::get_user( $ticket_data['user_id'], false );
		$ticket_data['contact_name']  = ( isset( $ticket_contact['name'] ) ? $ticket_contact['name'] . ' ' : '' ) . ( isset( $ticket_contact['last_name'] ) ? $ticket_contact['last_name'] : '' );
		$ticket_data['contact_fname'] = ( isset( $ticket_contact['name'] ) ? $ticket_contact['name'] : '' );
		$ticket_data['contact_lname'] = ( isset( $ticket_contact['last_name'] ) ? $ticket_contact['last_name'] : '' );


		// addition. find all extra keys for this ticket and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'ticket' );
			foreach ( $all_extra_fields as $e ) {
				$ticket_data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'ticket', 'owner_id' => $ticket_id ) );
			foreach ( $extras as $e ) {
				$ticket_data[ $e['extra_key'] ] = $e['extra'];
			}
		}

		if ( isset( $ticket_data['faq_product_id'] ) && (int) $ticket_data['faq_product_id'] > 0 ) {
			$ticket_data['faq_product'] = friendly_key( module_faq::get_faq_products_rel(), $ticket_data['faq_product_id'] );
		} else {
			$ticket_data['faq_product'] = _l( 'N/A' );
		}

		// find any extra keys (defined in the db for ticket submission, not the module_extra extra keys)
		$extras = module_ticket::get_ticket_extras_keys();
		if ( count( $extras ) ) {
			foreach ( $extras as $extra ) {
				$key = strtolower( $extra['key'] );
				if ( ! isset( $ticket_data[ $key ] ) ) {
					$ticket_data[ $key ] = isset( $ticket_data['extra_data'][ $extra['ticket_data_key_id'] ] ) ? $ticket_data['extra_data'][ $extra['ticket_data_key_id'] ]['value'] : '';
				}
			}
		}

		return $ticket_data;
	}

	// this saves the ticket_rel_ids[] select elements into the ticket_quote_rel database table.
	public static function hook_quote_save( $callback_name, $quote_id ) {
		if ( (int) $quote_id > 0 && isset( $_POST['ticket_rel_ids'] ) && is_array( $_POST['ticket_rel_ids'] ) ) {
			// remove existing.
			delete_from_db( 'ticket_quote_rel', 'quote_id', $quote_id );
			foreach ( array_unique( $_POST['ticket_rel_ids'] ) as $ticket_id ) {
				if ( (int) $ticket_id > 0 ) {
					$sql = "INSERT INTO `" . _DB_PREFIX . "ticket_quote_rel` SET ticket_id = " . (int) $ticket_id . ", quote_id = " . (int) $quote_id;
					query( $sql );
				}
			}
		}
	}

	public static function hook_quote_delete( $callback_name, $quote_id ) {
		if ( (int) $quote_id > 0 ) {
			// remove existing.
			delete_from_db( 'ticket_quote_rel', 'quote_id', $quote_id );
		}
	}

	// this saves the invoice_ticket_ids[] select elements into the ticket_invoice_rel database table.
	public static function hook_invoice_save( $callback_name, $invoice_id ) {
		if ( (int) $invoice_id > 0 && isset( $_POST['invoice_ticket_ids'] ) && is_array( $_POST['invoice_ticket_ids'] ) ) {
			// remove existing.
			delete_from_db( 'ticket_invoice_rel', 'invoice_id', $invoice_id );
			foreach ( array_unique( $_POST['invoice_ticket_ids'] ) as $ticket_id ) {
				if ( (int) $ticket_id > 0 ) {
					$sql = "INSERT INTO `" . _DB_PREFIX . "ticket_invoice_rel` SET ticket_id = " . (int) $ticket_id . ", invoice_id = " . (int) $invoice_id;
					query( $sql );
				}
			}
		}
	}

	public static function hook_invoice_delete( $callback_name, $invoice_id ) {
		if ( (int) $invoice_id > 0 ) {
			// remove existing.
			delete_from_db( 'ticket_invoice_rel', 'invoice_id', $invoice_id );
		}
	}

	// this populates the default quote data with values when we're creating a quote from a ticket
	public static function hook_filter_get_quote( $callback_name, $quote_data, $quote_id ) {
		if ( ! $quote_id && isset( $_REQUEST['ticket_id'] ) && (int) $_REQUEST['ticket_id'] > 0 ) {
			if ( isset( $quote_data['customer_id'] ) ) {
				$ticket_data = module_ticket::get_ticket( $_REQUEST['ticket_id'], false );
				if ( $ticket_data && $ticket_data['ticket_id'] == $_REQUEST['ticket_id'] ) {
					// we're creating a new quote linked to this particular ticket
					$quote_data['customer_id']     = $ticket_data['customer_id'];
					$quote_data['contact_user_id'] = $ticket_data['user_id'];
				}
			}
		}

		return $quote_data;
	}

	// this is a hook that adds a new "Tickets" area to the advanced panel in the Quote page.
	public static function hook_filter_generate_fieldset_options( $callback_name, $fieldset_options ) {
		if ( is_array( $fieldset_options ) && isset( $fieldset_options['id'] ) && $fieldset_options['id'] == 'quote_advanced' ) {
			$ticket_quote_rel_data = array();
			$customer_id           = 0; // which customer to bring related quotes in from.
			if ( isset( $_REQUEST['ticket_id'] ) && (int) $_REQUEST['ticket_id'] > 0 ) {
				$ticket_data = module_ticket::get_ticket( $_REQUEST['ticket_id'], false );
				if ( $ticket_data && $ticket_data['ticket_id'] == $_REQUEST['ticket_id'] ) {
					// we're creating a new quote linked to this particular ticket
					$ticket_quote_rel_data[] = $ticket_data['ticket_id'];
					$customer_id             = $ticket_data['customer_id'];
				}
			}
			if ( isset( $_REQUEST['quote_id'] ) && (int) $_REQUEST['quote_id'] > 0 ) {
				// we're opening an existing quote, find any matching linked tickets.
				$quote_data = module_quote::get_quote( $_REQUEST['quote_id'] );
				if ( $quote_data && $quote_data['quote_id'] == $_REQUEST['quote_id'] ) {
					if ( $quote_data['customer_id'] ) {
						$customer_id = $quote_data['customer_id'];
					}
					// any existing ones from within the database?
					$existing = get_multiple( 'ticket_quote_rel', array( 'quote_id' => $quote_data['quote_id'] ) );
					foreach ( $existing as $e ) {
						if ( $e['ticket_id'] ) {
							$ticket_quote_rel_data[] = $e['ticket_id'];
						}
					}
				}
			}
			$select_values = array();

			if ( $customer_id > 0 ) {
				$tickets = module_ticket::get_tickets( array( 'customer_id' => $customer_id ) );
				while ( $row = mysqli_fetch_assoc( $tickets ) ) {
					$select_values[ $row['ticket_id'] ] = module_ticket::ticket_number( $row['ticket_id'] ) . ' ' . substr( $row['subject'], 0, 20 ) . '...';
				}
			}
			if ( ! count( $ticket_quote_rel_data ) ) {
				$ticket_quote_rel_data = array( false );
			}
			$ticket_links = array();
			foreach ( $ticket_quote_rel_data as $ticket_id ) {
				if ( $ticket_id > 0 ) {
					$ticket_links[] = module_ticket::link_open( $ticket_id, true );
				}
			}

			$fieldset_options['elements']['ticket_rel_ids'] = array(
				'title'  => 'Linked Tickets',
				'fields' => array(
					'<div id="ticket_rel_ids_holder">',
					array(
						'type'     => 'select',
						'name'     => 'ticket_rel_ids[]',
						'options'  => $select_values,
						'multiple' => 'ticket_rel_ids_holder',
						'values'   => $ticket_quote_rel_data,
					),
					'</div>',
					'<div>' . implode( ' ', $ticket_links ) . '</div>',
				),
			);
		}

		return $fieldset_options;
	}


	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( self::can_i( 'view', 'Tickets' ) ) {

			$customer_id = ! empty( $search_options['vars']['lookup_customer_id'] ) ? (int) $search_options['vars']['lookup_customer_id'] : false;
			$res         = self::get_tickets( array( 'customer_id' => $customer_id ) );

			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['ticket_id'],
					'value' => $row['subject']
				);
			}
		}

		return $result;
	}

	public static function hook_customer_deleted( $callback_name, $customer_id, $remove_linked_data ) {
		if ( (int) $customer_id > 0 ) {
			while ( $row = mysqli_fetch_assoc( module_ticket::get_tickets( array( 'customer_id' => $customer_id ) ) ) ) {
				if ( $remove_linked_data && self::can_i( 'delete', 'Tickets' ) ) {
					self::delete_ticket( $row['ticket_id'] );
				} else {
					update_insert( 'ticket_id', $row['ticket_id'], 'ticket', array( 'customer_id' => 0 ) );
				}
			}
		}
	}

	public static function delete_ticket( $ticket_id ) {
		$ticket_id = (int) $ticket_id;
		$sql       = "DELETE FROM " . _DB_PREFIX . "ticket WHERE ticket_id = '" . $ticket_id . "' LIMIT 1";
		$res       = query( $sql );
		$sql       = "DELETE FROM " . _DB_PREFIX . "ticket_message WHERE ticket_id = '" . $ticket_id . "'";
		$res       = query( $sql );
		$sql       = "DELETE FROM " . _DB_PREFIX . "ticket_message_attachment WHERE ticket_id = '" . $ticket_id . "'";
		$res       = query( $sql );
		if ( class_exists( 'module_group', false ) ) {
			module_group::delete_member( $ticket_id, 'ticket' );
		}
		module_cache::clear( 'ticket' );

		//		module_note::note_delete("ticket",$ticket_id);
		//        module_extra::delete_extras('ticket','ticket_id',$ticket_id);
	}

	public function login_link( $ticket_id ) {
		return module_security::generate_auto_login_link( $ticket_id );
	}

	public function generate_priority_invoice( $ticket_id ) {
		// call the invoice module and create an invoice for this ticket.
		// once this invoice is paid it will do a callback to the ticket.
		$ticket_data = $this->get_ticket( $ticket_id );
		// check if no invoice exists.
		if ( ! $ticket_data['invoice_id'] ) {
			$task_name     = module_config::c( 'ticket_priority_invoice_task', 'Priority Support Ticket' );
			$task_cost     = module_config::c( 'ticket_priority_cost', 10 );
			$task_currency = module_config::c( 'ticket_priority_currency', 1 );

			// we do this hack so that the customer can have different invoice templates for support tickets.
			$old_customer_id         = isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : false;
			$_REQUEST['customer_id'] = $ticket_data['customer_id'];
			$invoice_data            = module_invoice::get_invoice( 'new', true );
			$_REQUEST['customer_id'] = $old_customer_id;
			// todo - if the ticket customer_id changes (a feature for later on) then we have to update any of these invoices.
			// maybe it's best we don't have a customer_id here? hmmmmmmmmmmmmmmmmmm
			// the user will have to enter their own invoice details anyway.
			// maybe we can read the customer_id from the user table if there is no customer_id in the invoice table? that might fix some things.
			$invoice_data['customer_id'] = $ticket_data['customer_id'];
			$invoice_data['user_id']     = $ticket_data['user_id'];
			$invoice_data['currency_id'] = $task_currency;
			$invoice_data['date_sent']   = date( 'Y-m-d' );
			$invoice_data['name']        = 'T' . $this->ticket_number( $ticket_id );
			// don't set an automatic reminder on invoices
			$invoice_data['overdue_email_auto'] = module_config::c( 'ticket_priority_auto_overdue_email', 0 );
			// pick a tax rate for this automatic invoice.
			//if(module_config::c('ticket_priority_tax_name','')){
			$invoice_data['total_tax_name'] = module_config::c( 'ticket_priority_tax_name', '' );
			//}
			//if(module_config::c('ticket_priority_tax_rate','')){
			$invoice_data['total_tax_rate'] = module_config::c( 'ticket_priority_tax_rate', '' );
			//}

			$invoice_data['invoice_invoice_item'] = array(
				'new' => array(
					'description'      => $task_name . ' - ' . _l( 'Ticket #' . $this->ticket_number( $ticket_id ) ),
					'hourly_rate'      => $task_cost,
					'manual_task_type' => _TASK_TYPE_AMOUNT_ONLY,
					//'amount' => $task_cost,
					'completed'        => 1, // not needed?
				)
			);
			$invoice_id                           = module_invoice::save_invoice( 'new', $invoice_data );
			update_insert( 'ticket_id', $ticket_id, 'ticket', array(
				'invoice_id' => $invoice_id,
			) );
			module_invoice::add_history( $invoice_id, 'Created invoice from support ticket #' . $this->ticket_number( $ticket_id ) );

			return $invoice_id;
		}

		return $ticket_data['invoice_id'];
	}

	public static function get_statuses() {
		return self::$ticket_statuses;
	}

	public static function get_types( $only_public = false ) {

		//$sql = "SELECT `type` FROM `"._DB_PREFIX."ticket` GROUP BY `type` ORDER BY `type`";
		$sql = "SELECT * FROM `" . _DB_PREFIX . "ticket_type` tt";
		if ( $only_public ) {
			$sql .= " WHERE tt.`public` = 1 ";
		}
		$sql      .= " ORDER BY tt.`name`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['ticket_type_id'] ] = $r;
		}

		return $statuses;
	}


	/**
	 * Used only when admin/user replies via web interface
	 * Or when a new ticket/reply is submitted via public interface
	 * Or when an autoreply is sent
	 * */
	public static function send_reply( $ticket_id, $message, $from_user_id, $to_user_id, $reply_type = 'admin', $internal_from = '', $other_options = array() ) {


		// we also check if this message contains anything, or anything above the "reply line"
		// this is a hack to stop the autoreply loop that seems to happen when sending an email as yourself from  your envato profile.

		// stip out the text before our "--reply above this line-- bit.
		// copied code from ticket_admin_edit.php
		/*$reply__ine_default = '----- (Please reply above this line) -----'; // incase they change it
        $reply__ine =   module_config::s('ticket_reply_line',$reply__ine_default);
        $text = preg_replace("#<br[^>]*>#",'',$message);
        // convert to single text.
        $text = preg_replace('#\s+#imsU',' ',$text);
        if(
            preg_match('#^\s*'.preg_quote($reply__ine,'#').'.*#ims',$text) ||
            preg_match('#^\s*'.preg_quote($reply__ine_default,'#').'.*#ims',$text)
        ){
            // no content. don't send email
            //mail('dtbaker@gmail.com','ticket reply '.$ticket_id,'sending reply for text:\''.$text."' \n\n\n Original:\n".$message);
            return false;
        }*/


		// $message is in text format, need to nl2br it before printing.

		$ticket_number  = self::ticket_number( $ticket_id );
		$ticket_details = self::get_ticket( $ticket_id );


		$to_user_a   = module_user::get_user( $to_user_id, false );
		$from_user_a = module_user::get_user( $from_user_id, false );


		// we have to replace some special text within these messages. this is just a hack to support text in my autoreply.
		$replace = array(
			'name'                  => $to_user_a['name'],
			'ticket_url'            => module_ticket::link_public( $ticket_id ),
			'ticket_url_cancel'     => module_ticket::link_public_status( $ticket_id, 7 ),
			'ticket_url_resolved'   => module_ticket::link_public_status( $ticket_id, _TICKET_STATUS_RESOLVED_ID ),
			'ticket_url_inprogress' => module_ticket::link_public_status( $ticket_id, 5 ),
			'faq_product_id'        => $ticket_details['faq_product_id'],
		);
		foreach ( $replace as $key => $val ) {
			$message = str_replace( '{' . strtoupper( $key ) . '}', $val, $message );
		}

		// the from details need to match the ticket account details.
		if ( $ticket_details['ticket_account_id'] ) {
			$ticket_account = self::get_ticket_account( $ticket_details['ticket_account_id'] );
		} else {
			$ticket_account = false;
		}
		if ( $ticket_account && $ticket_account['email'] ) {
			// want the user to reply to our ticketing system.
			$reply_to_address = $ticket_account['email'];
			$reply_to_name    = $ticket_account['name'];
		} else {
			// reply to creator of the email.
			$reply_to_address = $from_user_a['email'];
			$reply_to_name    = $from_user_a['name'];
		}

		$htmlmessage = '';
		if ( self::is_text_html( $message ) ) {
			$htmlmessage = $message;
			$message     = strip_tags( $message );
		}


		$ticket_message_data = array(
			'ticket_id'       => $ticket_id,
			'content'         => $message,
			'htmlcontent'     => $htmlmessage,
			'message_time'    => time(),
			'from_user_id'    => $from_user_id,
			'to_user_id'      => $to_user_id,
			'message_type_id' => ( $reply_type == 'admin' ? _TICKET_MESSAGE_TYPE_ADMIN : _TICKET_MESSAGE_TYPE_CREATOR ),
			'private_message' => isset( $other_options['private_message'] ) && $other_options['private_message'] ? $other_options['private_message'] : 0,
		);
		if ( $internal_from == 'autoreply' ) {
			$ticket_message_data['message_type_id'] = _TICKET_MESSAGE_TYPE_AUTOREPLY;
		}
		if ( self::can_edit_tickets() ) {
			// we look for the extra cc/bcc headers.
			if ( module_config::c( 'ticket_allow_cc_bcc', 1 ) ) {
				$headers = array();
				// look for cc staff options here.
				if ( isset( $_POST['ticket_cc_staff'] ) && is_array( $_POST['ticket_cc_staff'] ) ) {
					$admins_rel = self::get_ticket_staff_rel();
					foreach ( $admins_rel as $staff_id => $staff_name ) {
						if ( isset( $_POST['ticket_cc_staff'][ $staff_id ] ) ) {
							$staff_user = module_user::get_user( $staff_id );
							if ( $staff_user && isset( $staff_user['email'] ) && strlen( $staff_user['email'] ) ) {
								// found a staff member to cc!
								if ( ! isset( $headers['cc_emails'] ) ) {
									$headers['cc_emails'] = array();
								}
								$headers['cc_emails'][] = array( 'address' => $staff_user['email'] );
							}
						}
					}
				}
				if ( isset( $_POST['ticket_cc'] ) && strlen( $_POST['ticket_cc'] ) ) {
					$bits = explode( ',', $_POST['ticket_cc'] );
					foreach ( $bits as $b ) {
						$b = trim( $b );
						if ( strlen( $b ) ) {
							if ( ! isset( $headers['cc_emails'] ) ) {
								$headers['cc_emails'] = array();
							}
							$headers['cc_emails'][] = array( 'address' => $b );
						}
					}
				}
				if ( isset( $_POST['ticket_bcc'] ) && strlen( $_POST['ticket_bcc'] ) ) {
					$bits = explode( ',', $_POST['ticket_bcc'] );
					foreach ( $bits as $b ) {
						$b = trim( $b );
						if ( strlen( $b ) ) {
							if ( ! isset( $headers['bcc_emails'] ) ) {
								$headers['bcc_emails'] = array();
							}
							$headers['bcc_emails'][] = array( 'address' => $b );
						}
					}
				}
				if ( count( $headers ) ) {
					$ticket_message_data['cache'] = serialize( $headers );
				}
			}
		}
		$ticket_message_id = update_insert( 'ticket_message_id', 'new', 'ticket_message', $ticket_message_data );
		if ( ! $ticket_message_id ) {
			return false;
		}

		// handle any attachemnts.

		// are there any attachments?
		if ( $ticket_message_id && isset( $_FILES['attachment'] ) && isset( $_FILES['attachment']['tmp_name'] ) && is_array( $_FILES['attachment']['tmp_name'] ) ) {
			foreach ( $_FILES['attachment']['tmp_name'] as $key => $val ) {
				if ( is_uploaded_file( $val ) ) {
					// save attachments against ticket!

					$mime = dtbaker_mime_type( $_FILES['attachment']['name'][ $key ], $val );

					$attachment_id = update_insert( 'ticket_message_attachment_id', 'new', 'ticket_message_attachment', array(
						'ticket_id'         => $ticket_id,
						'ticket_message_id' => $ticket_message_id,
						'file_name'         => $_FILES['attachment']['name'][ $key ],
						'content_type'      => $mime,
					) );
					//echo getcwd();exit;
					//ini_set('display_errors',true);
					if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' ) ) {
						mkdir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/', 0777, true );
					}
					if ( ! move_uploaded_file( $val, _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attachment_id . '' ) ) {
						//echo 'error uploading file';exit;
					}
				}
			}
		}


		if ( $internal_from != 'autoreply' ) {
			// stops them all having the same timestamp on a big import.
			update_insert( 'ticket_id', $ticket_id, 'ticket', array(
				'last_message_timestamp' => time(),
			) );
			/*}else{
            // we are sending an auto reply, flag this in the special cache field.
            // hacky!
            update_insert('ticket_message_id',$ticket_message_id,'ticket_message',array(
                     'cache'=>'autoreply',
             ));*/
		}
		//$reply_line = module_config::s('ticket_reply_line','----- (Please reply above this line) -----');

		$s = self::get_statuses();


		if ( isset( $other_options['private_message'] ) && $other_options['private_message'] ) {
			// private message, dont send an email to the customer.
			if ( ! self::is_text_html( $message ) ) {
				$message = nl2br( htmlspecialchars( $message ) ); // because message is in text format, before we send admin notification do this.
			}
			module_ticket::send_admin_alert( $ticket_id, strlen( $htmlmessage ) ? $htmlmessage : $message, true );

		} else {
			if ( $to_user_id == $ticket_details['user_id'] ) {
				// WE ARE emailing the "User" from support.
				// so the support is emailing a response back to the customer.
				module_ticket::send_customer_alert( $ticket_id, strlen( $htmlmessage ) ? $htmlmessage : $message, $ticket_message_id );

			} else {
				if ( ! self::is_text_html( $message ) ) {
					$message = nl2br( htmlspecialchars( $message ) ); // because message is in text format, before we send admin notification do this.
				}
				module_ticket::send_admin_alert( $ticket_id, strlen( $htmlmessage ) ? $htmlmessage : $message );
			}


			if ( $reply_type == 'end_user' && ( ! $ticket_details['message_count'] || module_config::c( 'ticket_autoreply_every_message', 0 ) ) ) {
				// this is the first message!
				// send an email back to the user confirming this submissions via the web interface.
				self::send_autoreply( $ticket_id, $message );
			}
		}

		return $ticket_message_id;

	}

	/**
	 * Sends the customer an email telling them to use the online form to submit support tickets.
	 *
	 * @static
	 *
	 * @param array  $from_user
	 * @param string $subject
	 */
	public static function send_customer_rejection_alert( $from_user, $subject ) {

		$template = module_template::get_template_by_key( 'ticket_rejection' );
		$data     = array(
			            'subject'    => $subject,
			            'ticket_url' => module_config::c( 'ticket_public_submit_url', 'http://yoursite.com/support-tickets.html' ),
		            ) + $from_user;
		$template->assign_values( $data );
		$content = $template->replace_content();

		$email = module_email::new_email();
		$email->set_to_manual( $from_user['email'], $from_user['name'] );
		$email->set_subject( $template->description );
		foreach ( $data as $key => $val ) {
			$email->replace( $key, $val );
		}
		$email->send();
	}

	/**
	 * Sends the customer an email letting them know the administrator has updated
	 * their ticket with a new message.
	 *
	 * @static
	 *
	 * @param        $ticket_id
	 * @param string $message
	 */
	public static function send_customer_alert( $ticket_id, $message = '', $ticket_message_id = false ) {

		if ( ! module_config::c( 'ticket_send_customer_alerts', 1 ) ) {
			return false;
		}

		$ticket_details      = self::get_ticket( $ticket_id );
		$ticket_account_data = self::get_ticket_account( $ticket_details['ticket_account_id'] );
		$ticket_number       = self::ticket_number( $ticket_id );
		$s                   = self::get_statuses();
		$reply_line          = module_config::s( 'ticket_reply_line', '----- (Please reply above this line) -----' );
		if ( ! $ticket_message_id ) {
			$no_ticket_message_id = true; // used for our new cc/bcc hack. only send cc/bcc if $ticket_message_id is provided
			// ticket_message_id isn't provided when sending mails from a cron job.
			$ticket_message_id = $ticket_details['last_ticket_message_id'];
		}
		$last_ticket_message = self::get_ticket_message( $ticket_message_id );
		if ( ! self::is_text_html( $message ) ) {
			$message = nl2br( htmlspecialchars( $message ) );
		}

		if ( ! $ticket_message_id || $last_ticket_message['ticket_message_id'] != $ticket_message_id ) {
			return false;
		}

		if ( ! $message && $last_ticket_message ) {
			if ( $last_ticket_message['htmlcontent'] ) {
				$message = trim( $last_ticket_message['htmlcontent'] );
			} else if ( $last_ticket_message['content'] ) {
				$message = nl2br( htmlspecialchars( $last_ticket_message['content'] ) );
			}
		}

		$to_user_id = $last_ticket_message['to_user_id'];
		if ( ! $to_user_id || $last_ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_ADMIN ) {
			// default to assigned user
			// always sent admin messages back to the end user
			$to_user_id = $ticket_details['user_id'];
		}

		// bug fix! don't send a customer alert back to a staff member account.
		$staff_members             = self::get_ticket_staff_rel();
		$ticket_accounts           = self::get_accounts();
		$to_user_a                 = module_user::get_user( $to_user_id, false );
		$sending_to_ticket_account = false;
		foreach ( $ticket_accounts as $ta ) {
			if ( strlen( $ta['email'] ) > 0 && strtolower( $ta['email'] == strtolower( $to_user_a['email'] ) ) ) {
				$sending_to_ticket_account = true;
			}
		}
		if ( $sending_to_ticket_account ) {
			send_error( 'Ticket ' . $ticket_id . ' error! Attempted to send a customer alert back to a ticket account email address. This would probably create a new ticket based on the customer auto-reply when the system sends it back. Please report this error to us if you believe it is wrong.' );

			return false;
		}
		/*if(isset($staff_members[$to_user_id])){
            // we send 1 customer alert back to the staff member, but we check the last ticket message and don't send it if it's going to the same user again.
            if($last_ticket_message && isset($last_ticket_message['to_user_id']) && $last_ticket_message['to_user_id'] == $to_user_id){
                send_error('Ticket '.$ticket_id.' error! Attempted to send a customer alert back to a staff member. This could cause all sorts of problems. Please check this Customer Contact permissions, if they have TICKET EDIT permissions please turn this off and try again to see if that fixes the problem.');
                return false;
            }
        }*/


		$from_user_id = $last_ticket_message['from_user_id'];
		if ( ! $from_user_id ) {
			$from_user_id = $ticket_details['assigned_user_id'] ? $ticket_details['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 );
		}
		//if(!$from_user_id)$from_user_id = $ticket_details['assigned_user_id']; // default to assigned staff member
		$from_user_a = module_user::get_user( $from_user_id, false );

		if ( $ticket_details['ticket_account_id'] ) {
			$ticket_account = self::get_ticket_account( $ticket_details['ticket_account_id'] );
		} else {
			$ticket_account = false;
		}
		if ( $ticket_account && $ticket_account['email'] ) {
			// want the user to reply to our ticketing system.
			$reply_to_address = $ticket_account['email'];
			$reply_to_name    = $ticket_account['name'];
		} else {
			// reply to creator of the email.
			$reply_to_address = $from_user_a['email'];
			$reply_to_name    = $from_user_a['name'] . ( isset( $from_user_a['last_name'] ) && $from_user_a['last_name'] ) ? ' ' . $from_user_a['last_name'] : '';
		}

		$template = module_template::get_template_by_key( 'ticket_container' );
		$template->assign_values( self::get_replace_fields( $ticket_id, $ticket_details ) );
		$template->assign_values( array(
			'ticket_number'     => self::ticket_number( $ticket_id ),
			'ticket_status'     => $s[ $ticket_details['status_id'] ],
			'message'           => $message,
			'subject'           => $ticket_details['subject'],
			'position_current'  => $ticket_details['position'],
			'position_all'      => $ticket_details['total_pending'],
			'reply_line'        => $reply_line,
			'days'              => module_config::c( 'ticket_turn_around_days', 5 ),
			'url'               => self::link_public( $ticket_id ),
			'message_count'     => $ticket_details['message_count'],
			'message_date_time' => date( 'l jS \of F Y h:i A' ),

			'ticket_url_cancel'     => module_ticket::link_public_status( $ticket_id, 7 ),
			'ticket_url_resolved'   => module_ticket::link_public_status( $ticket_id, _TICKET_STATUS_RESOLVED_ID ),
			'ticket_url_inprogress' => module_ticket::link_public_status( $ticket_id, 5 ),

			'faq_product_id' => $ticket_details['faq_product_id'],
		) );
		$content = $template->replace_content();

		$email = module_email::new_email();
		$email->set_to( 'user', $to_user_id );


		$headers = @unserialize( $last_ticket_message['cache'] );
		if ( ! is_array( $headers ) ) {
			$headers = array();
		}

		if ( module_config::c( 'ticket_from_creators_email', 0 ) == 1 ) {
			$email->set_from( 'user', $from_user_id );
		} else {
			$email->set_from_manual( $reply_to_address, $reply_to_name );

			//if($last_ticket_message['cache'] != 'autoreply'){
			// now we update the ticket messages with the correct from address
			$headers['from_email'] = $reply_to_address;
			//}
		}

		if ( isset( $no_ticket_message_id ) && $no_ticket_message_id ) {
			// we are sending this message as a results of an admin email collected from the cron job.
			$headers['admin_email_inbound'] = true;
			// the headers here contain the from/to address as support@xxxx.com - we need to add the real "to" customer address in here so it looks correct inthe ticket output.
			if ( $to_user_id != $ticket_details['user_id'] ) {
				update_insert( 'ticket_message_id', $ticket_message_id, 'ticket_message', array( 'to_user_id' => $to_user_id ) );
			}
		}

		if ( ! isset( $no_ticket_message_id ) && is_array( $headers ) ) {
			// we're right to do our cc/bcc hack
			if ( $headers && isset( $headers['to_emails'] ) ) {
				foreach ( $headers['to_emails'] as $to_emails ) {
					if ( isset( $to_emails['address'] ) && strlen( $to_emails['address'] ) ) {
						$email->set_to_manual( $to_emails['address'], isset( $to_emails['name'] ) ? $to_emails['name'] : '' );
					}
				}
			}
			if ( $headers && isset( $headers['cc_emails'] ) ) {
				foreach ( $headers['cc_emails'] as $cc_emails ) {
					if ( isset( $cc_emails['address'] ) && strlen( $cc_emails['address'] ) ) {
						$email->set_cc_manual( $cc_emails['address'], isset( $cc_emails['name'] ) ? $cc_emails['name'] : '' );
					}
				}
			}
			if ( $headers && isset( $headers['bcc_emails'] ) ) {
				foreach ( $headers['bcc_emails'] as $bcc_emails ) {
					if ( isset( $bcc_emails['address'] ) && strlen( $bcc_emails['address'] ) ) {
						$email->set_bcc_manual( $bcc_emails['address'], isset( $bcc_emails['name'] ) ? $bcc_emails['name'] : '' );
					}
				}
			}
		}
		update_insert( 'ticket_message_id', $ticket_message_id, 'ticket_message', array( 'cache' => serialize( $headers ) ) );

		$email->set_reply_to( $reply_to_address, $reply_to_name );
		$email->set_subject( '[TICKET:' . $ticket_number . '] Re: ' . $ticket_details['subject'] );

		if ( module_config::c( 'ticket_alert_microdata', 1 ) ) {
			$content = '<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {
    "@type": "ViewAction",
    "target": "' . self::link_public( $ticket_id ) . '",
    "name": "View Ticket"
  },
  "description": "View the ticket online"
}
</script>
' . $content;
		}

		$email->set_html( $content );
		// check attachments:
		$attachments = self::get_ticket_message_attachments( $ticket_message_id );
		foreach ( $attachments as $attachment ) {
			$file_path = _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'];
			$file_name = $attachment['file_name'];
			$email->AddAttachment( $file_path, $file_name );
		}
		$email->send();
	}


	// send an alert to the admin letting them know there's a new ticket.
	public static function send_admin_alert( $ticket_id, $message = '', $allow_to_cc_bcc = false ) {


		if ( ! module_config::c( 'ticket_send_admin_alerts', 1 ) ) {
			return false;
		}
		module_cache::clear( 'ticket' );
		$ticket_data         = self::get_ticket( $ticket_id );
		$ticket_account_data = self::get_ticket_account( $ticket_data['ticket_account_id'] );
		$ticket_number       = self::ticket_number( $ticket_id );
		if ( $ticket_data['last_ticket_message_id'] ) {
			$last_message = self::get_ticket_message( $ticket_data['last_ticket_message_id'] );
			if ( ! $message ) {
				$htmlmessage = trim( $last_message['htmlcontent'] );
				if ( $htmlmessage ) {
					$message = $htmlmessage;
				} else {
					$message = nl2br( htmlspecialchars( trim( $last_message['content'] ) ) );
				}
			}
		} else {
			$last_message = false;
		}
		$to         = module_config::c( 'ticket_admin_email_alert', _ERROR_EMAIL );
		$to_user_id = 0;
		$cc         = false;
		if ( module_config::c( 'ticket_auto_notify_staff', 0 ) && $ticket_data['assigned_user_id'] ) {
			$staff = module_user::get_user( $ticket_data['assigned_user_id'], false );
			if ( $staff && $staff['user_id'] == $ticket_data['assigned_user_id'] && $staff['email'] ) {
				$cc         = $to;
				$to         = $staff['email'];
				$to_user_id = $staff['user_id'];
			}
		}
		if ( strlen( $to ) < 4 ) {
			return;
		}
		// do we only send this on first emails or not ?
		$first_only = module_config::c( 'ticket_admin_alert_first_only', 0 );
		if ( $first_only && $ticket_data['message_count'] > 1 ) {
			return;
		}
		$s          = self::get_statuses();
		$reply_line = module_config::s( 'ticket_reply_line', '----- (Please reply above this line) -----' );
		// autoreplies go back to the user - not our admin system:
		$from_user_a      = module_user::get_user( $ticket_data['user_id'], false );
		$reply_to_address = $from_user_a['email'];
		$reply_to_name    = $from_user_a['name'];

		$template = module_template::get_template_by_key( 'ticket_admin_email' );
		$template->assign_values( self::get_replace_fields( $ticket_id, $ticket_data ) );
		$template->assign_values( array(
			'ticket_number'    => self::ticket_number( $ticket_id ),
			'ticket_status'    => $s[ $ticket_data['status_id'] ],
			'message'          => $message,
			'subject'          => $ticket_data['subject'],
			'position_current' => $ticket_data['position'],
			'position_all'     => $ticket_data['total_pending'],
			'reply_line'       => $reply_line,
			'days'             => module_config::c( 'ticket_turn_around_days', 5 ),
			'url'              => self::link_public( $ticket_id ),
			'url_admin'        => self::link_open( $ticket_id ),
			'message_count'    => $ticket_data['message_count'],

			'ticket_url_cancel'     => module_ticket::link_public_status( $ticket_id, 7 ),
			'ticket_url_resolved'   => module_ticket::link_public_status( $ticket_id, _TICKET_STATUS_RESOLVED_ID ),
			'ticket_url_inprogress' => module_ticket::link_public_status( $ticket_id, 5 ),

			'faq_product_id' => $ticket_data['faq_product_id'],
		) );
		$content = $template->replace_content();

		$email                 = module_email::new_email();
		$email->replace_values = $template->values;
		if ( $to_user_id ) {
			$email->set_to( 'user', $to_user_id );
		} else {
			$email->set_to_manual( $to );
		}
		if ( $cc ) {
			$email->set_cc_manual( $cc );
		}
		if ( $ticket_account_data && $ticket_account_data['email'] ) {
			$email->set_from_manual( $ticket_account_data['email'], $ticket_account_data['name'] );
			$email->set_bounce_address( $ticket_account_data['email'] );
		} else {
			$email->set_from_manual( $to, module_config::s( 'admin_system_name' ) );
			$email->set_bounce_address( $to );
		}
		//$email->set_from('user',$from_user_id);
		//$email->set_from('foo','foo',$to,'Admin');

		$headers = $last_message ? @unserialize( $last_message['cache'] ) : false;

		if ( $allow_to_cc_bcc && $headers && is_array( $headers ) ) {
			// we're right to do our cc/bcc hack
			if ( $headers && isset( $headers['to_emails'] ) ) {
				foreach ( $headers['to_emails'] as $to_emails ) {
					if ( isset( $to_emails['address'] ) && strlen( $to_emails['address'] ) ) {
						$email->set_to_manual( $to_emails['address'], isset( $to_emails['name'] ) ? $to_emails['name'] : '' );
					}
				}
			}
			if ( $headers && isset( $headers['cc_emails'] ) ) {
				foreach ( $headers['cc_emails'] as $cc_emails ) {
					if ( isset( $cc_emails['address'] ) && strlen( $cc_emails['address'] ) ) {
						$email->set_cc_manual( $cc_emails['address'], isset( $cc_emails['name'] ) ? $cc_emails['name'] : '' );
					}
				}
			}
			if ( $headers && isset( $headers['bcc_emails'] ) ) {
				foreach ( $headers['bcc_emails'] as $bcc_emails ) {
					if ( isset( $bcc_emails['address'] ) && strlen( $bcc_emails['address'] ) ) {
						$email->set_bcc_manual( $bcc_emails['address'], isset( $bcc_emails['name'] ) ? $bcc_emails['name'] : '' );
					}
				}
			}
		}


		// do we reply to the user who created this, or to our ticketing system?
		if ( module_config::c( 'ticket_admin_alert_postback', 1 ) && $ticket_account_data && $ticket_account_data['email'] ) {
			$email->set_reply_to( $ticket_account_data['email'], $ticket_account_data['name'] );
		} else {
			$email->set_reply_to( $reply_to_address, $reply_to_name );
		}
		if ( $last_message && $last_message['private_message'] ) {
			$email->set_subject( sprintf( module_config::c( 'ticket_private_message_email_subject', 'Private Support Ticket Message: [TICKET:%s]' ), $ticket_number ) );
		} else {
			$email->set_subject( sprintf( module_config::c( 'ticket_admin_alert_subject', 'Support Ticket Updated: [TICKET:%s]' ), $ticket_number ) );
		}
		if ( module_config::c( 'ticket_alert_microdata', 1 ) ) {
			$content = '<script type="application/ld+json">
{
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {
    "@type": "ViewAction",
    "target": "' . self::link_open( $ticket_id ) . '",
    "name": "View Ticket"
  },
  "description": "View the ticket online"
}
</script>
' . $content;
		}
		$email->set_html( $content );
		// check attachments:
		$attachments = self::get_ticket_message_attachments( $ticket_data['last_ticket_message_id'] );
		foreach ( $attachments as $attachment ) {
			$file_path = _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'];
			$file_name = $attachment['file_name'];
			$email->AddAttachment( $file_path, $file_name );
		}
		$email->send();
	}


	public static function send_autoreply( $ticket_id ) {

		if ( ! module_config::c( 'ticket_autoreply_enabled', 1, array(
			'plugin'      => 'ticket',
			'description' => 'Should autoreplies be sent to ticket messages?',
			'type'        => 'select',
			'options'     => get_yes_no(),
			'default'     => 1,
		) ) ) {
			return;
		}

		// send back an auto responder letting them know where they are in the queue.
		$ticket_data = self::get_ticket( $ticket_id );

		$template           = module_template::get_template_by_key( 'ticket_autoreply' );
		$auto_reply_message = $template->content;
		$from_user_id       = $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : module_config::c( 'ticket_default_user_id', 1 );
		//if($ticket_data['user_id'] != $from_user_id){
		// check if we have sent an autoreply to this address in the past 5 minutes, if we have we dont send another one.
		// this stops autoresponder spam messages.
		$time = time() - 300; // 5 mins
		$sql  = "SELECT * FROM `" . _DB_PREFIX . "ticket_message` tm WHERE to_user_id = '" . (int) $ticket_data['user_id'] . "' AND message_time > '" . $time . "' AND ( `cache` = 'autoreply' OR `message_type_id` = " . _TICKET_MESSAGE_TYPE_AUTOREPLY . " )";
		$res  = qa( $sql );
		if ( ! count( $res ) ) {

			$send_autoreply = true;

			if ( ! module_config::c( 'ticket_send_customer_alerts', 1 ) ) {
				$send_autoreply = false;
			}

			// other logic to check here???
			// see if this user has any 'ticket settings' extra fields, if we find a 'no_autoreply' value in here we don't send it.
			if ( class_exists( 'module_extra', false ) ) {
				$extra_fields = module_extra::get_extras( array(
					'owner_table' => 'user',
					'owner_id'    => $ticket_data['user_id']
				) );
				foreach ( $extra_fields as $extra_field ) {
					if ( stripos( $extra_field['extra_key'], 'ticket settings' ) !== false ) {
						if ( stripos( $extra_field['extra'], 'no_autoreply' ) !== false ) {
							$send_autoreply = false;
							break;
						}
					}
				}
			}

			if ( $send_autoreply ) {
				self::send_reply( $ticket_id, $auto_reply_message, $from_user_id, $ticket_data['user_id'], 'admin', 'autoreply' );
			}
		}
		//}
	}

	public static function run_cron() {

		if ( ! function_exists( 'imap_open' ) ) {
			set_error( 'Please contact hosting provider and enable IMAP for PHP' );
			echo 'Imap extension not available for php';

			return false;
		}

		include( 'cron/read_emails.php' );
	}


	private static function _subject_decode( $str, $mode = 0, $charset = "UTF-8" ) {

		return iconv_mime_decode( $str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8" );

		$data = imap_mime_header_decode( $str );
		if ( count( $data ) > 0 ) {
			// because iconv doesn't like the 'default' for charset
			$charset = ( $data[0]->charset == 'default' ) ? 'ASCII' : $data[0]->charset;

			return ( iconv( $charset, $charset, $data[0]->text ) );
		}

		return ( "" );
	}


	public static function import_email( $ticket_account_id, $import = true, $debug = false ) {


		require_once( 'includes/plugin_ticket/cron/rfc822_addresses.php' );
		require_once( 'includes/plugin_ticket/cron/mime_parser.php' );

		$admins_rel        = self::get_ticket_staff_rel();
		$created_tickets   = array();
		$ticket_account_id = (int) $ticket_account_id;
		$account           = self::get_ticket_account( $ticket_account_id );
		if ( ! $account ) {
			return false;
		}
		$email_account_address = $account['email'];
		$email_username        = $account['username'];
		$email_password        = $account['password'];
		$email_host            = $account['host'];
		$email_port            = $account['port'];
		$reply_from_user_id    = $account['default_user_id'];
		$ticket_type_id        = (int) $account['default_type'];
		$subject_regex         = $account['subject_regex'];
		$body_regex            = $account['body_regex'];
		$to_regex              = $account['to_regex'];
		$search_string         = $account['search_string'];
		$mailbox               = $account['mailbox'];
		$imap                  = (int) $account['imap'];
		$secure                = (int) $account['secure'];
		$start_date            = ( $account['start_date'] && $account['start_date'] != '0000-00-00' ) ? $account['start_date'] : false;


		if ( ! $email_host || ! $email_username ) {
			return false;
		}

		// try to connect with ssl first:
		$ssl = ( $secure ) ? '/ssl' : '';
		if ( $imap ) {
			$host = '{' . $email_host . ':' . $email_port . '/imap' . $ssl . '/novalidate-cert}' . $mailbox;
			if ( $debug ) {
				echo "Connecting to $host <br>\n";
			}
			$mbox = imap_open( $host, $email_username, $email_password );
		} else {
			$host = '{' . $email_host . ':' . $email_port . '/pop3' . $ssl . '/novalidate-cert}' . $mailbox;
			if ( $debug ) {
				echo "Connecting to $host <br>\n";
			}
			$mbox = imap_open( $host, $email_username, $email_password );
		}
		if ( ! $mbox ) {
			// todo: send email letting them know bounce checking failed?
			echo 'Failed to connect when checking for support ticket emails.' . imap_last_error();
			imap_errors();

			return false;
		}


		update_insert( 'ticket_account_id', $account['ticket_account_id'], 'ticket_account', array(
			'last_checked' => time(),
		) );

		$MC = imap_check( $mbox );
		//echo 'Connected'.$MC->Nmsgs;
		// do a search if
		$search_results = array( - 1 );
		if ( $imap && $search_string ) {
			//imap_sort($mbox,SORTARRIVAL,0);
			// we do a hack to support multiple searches in the imap string.
			if ( strpos( $search_string, '||' ) ) {
				$search_strings = explode( '||', $search_string );
			} else {
				$search_strings = array( $search_string );
			}
			$search_results = array();
			foreach ( $search_strings as $this_search_string ) {
				$this_search_string = trim( $this_search_string );
				if ( ! $this_search_string ) {
					return false;
				}
				if ( $debug ) {
					echo "Searching for $this_search_string <br>\n";
				}
				$this_search_results = imap_search( $mbox, $this_search_string );
				if ( $debug ) {
					echo " -- found " . count( $this_search_results ) . " results <br>\n";
				}
				$search_results = array_merge( $search_results, $this_search_results );
			}
			if ( ! $search_results ) {
				echo "No search results for $search_string ";

				return false;
			} else {
				sort( $search_results );
			}
		}

		if ( $debug ) {
			echo "Got " . (int) $MC->Nmsgs . " messages <br>\n";
			if ( $MC->Nmsgs >= 100 ) {
				echo " There are more than 100 messages in this mailbox. This may not work. Suggestions: Use a new empty email account, remove messages from the mail account, use IMAP instead of POP3, ensure delete after import is set to true.<br>\n";
			}
		}


		imap_errors();
		//print_r($search_results);//imap_close($mbox);return false;
		$sorted_emails = array();
		foreach ( $search_results as $search_result ) {

			if ( $search_result >= 0 ) {
				$result = imap_fetch_overview( $mbox, $search_result, 0 );
			} else {
				//$result = imap_fetch_overview($mbox,"1:100",0);
				$result = imap_fetch_overview( $mbox, "1:" . min( 100, $MC->Nmsgs ), 0 );
			}
			foreach ( $result as $overview ) {


				if ( ! isset( $overview->subject ) && ( ! isset( $overview->date ) || ! $overview->date ) ) {
					continue;
				}
				$overview->subject = self::_subject_decode( isset( $overview->subject ) ? (string) $overview->subject : '' );

				if ( $subject_regex && ! preg_match( $subject_regex, $overview->subject ) ) {
					continue;
				}
				if ( ! isset( $overview->date ) ) {
					$overview->date = date( 'Y-m-d H:i:s' );
				}
				if ( $start_date > 1000 ) {
					if ( strtotime( $overview->date ) < strtotime( $start_date ) ) {
						continue;
					}
				}

				$message_id = isset( $overview->message_id ) ? (string) $overview->message_id : false;
				if ( ! $message_id ) {
					$overview->message_id = $message_id = md5( $overview->subject . $overview->date );
				}

				//echo "#{$overview->msgno} ({$overview->date}) - From: {$overview->from} <br> {$this_subject} <br>\n";
				// check this email hasn't been processed before.
				// check this message hasn't been processed yet.
				$ticket = get_single( 'ticket_message', 'message_id', $message_id );
				if ( $ticket ) {
					continue;
				}

				// get ready to sort them.
				$overview->time   = strtotime( $overview->date );
				$sorted_emails [] = $overview;
			}
		}
		if ( ! function_exists( 'dtbaker_ticket_import_sort' ) ) {
			function dtbaker_ticket_import_sort( $a, $b ) {
				return $a->time > $b->time;
			}
		}
		uasort( $sorted_emails, 'dtbaker_ticket_import_sort' );
		$message_number = 0;
		foreach ( $sorted_emails as $overview ) {
			$message_number ++;

			$message_id = (string) $overview->message_id;

			if ( $debug ) {
				?>
				<div style="padding:5px; border:1px solid #EFEFEF; margin:4px;">
					<div>
						<strong><?php echo $message_number; ?></strong>
						Date: <strong><?php echo $overview->date; ?></strong> <br/>
						Subject: <strong><?php echo htmlspecialchars( $overview->subject ); ?></strong> <br/>
						From: <strong><?php echo htmlspecialchars( $overview->from ); ?></strong>
						To: <strong><?php echo htmlspecialchars( $overview->to ); ?></strong>
						<!-- <a href="#" onclick="document.getElementById('msg_<?php echo $message_number; ?>').style.display='block'; return false;">view body</a>
                            </div>
                            <div style="display:none; padding:10px; border:1px solid #CCC;" id="msg_<?php echo $message_number; ?>">
                                <?php
						// echo htmlspecialchars($results['Data']);
						?> -->
					</div>
				</div>
				<?php
			}
			if ( ! $import ) {
				continue;
			}

			$tmp_file = tempnam( _UCM_FILE_STORAGE_DIR . 'temp/', 'ticket' );
			imap_savebody( $mbox, $tmp_file, $overview->msgno );
			$mail_content = file_get_contents( $tmp_file );


			$mime                       = new mime_parser_class();
			$mime->mbox                 = 0;
			$mime->decode_bodies        = 1;
			$mime->ignore_syntax_errors = 1;
			$parameters                 = array(
				//'File'=>$mailfile,
				'Data' => $mail_content,
				//'SaveBody'=>'/tmp',
				//'SkipBody'=>0,
			);

			$parse_success = false;
			if ( ! $mime->Decode( $parameters, $decoded ) ) {
				//echo 'MIME message decoding error: '.$mime->error.' at position '.$mime->error_position."\n";
				// TODO - send warning email to admin.
				send_error( "Failed to decode this email: " . $mail_content );
				$parse_success = true; // so it delets the email below if that account setting is setfalse;
			} else {
				for ( $message = 0; $message < count( $decoded ); $message ++ ) {
					if ( $mime->Analyze( $decoded[ $message ], $results ) ) {

						if ( isset( $results['From'][0]['address'] ) ) {
							$from_address = $results['From'][0]['address'];
						} else {
							continue;
						}


						/*$results: Array
                                (
                                    [Type] => html
                                    [Description] => HTML message
                                    [Encoding] => iso-8859-1
                                    [Data] => asdfasdf
                                    [Alternative] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [Type] => text
                                                    [Description] => Text message
                                                    [Encoding] => iso-8859-1
                                                    [Data] => asdfasdf
                                                )
                                        )
                                    [Subject] => [TICKET:004372] Re: Testing cc and bcc fields...
                                    [Date] => Sun, 24 Mar 2013 22:04:49 +1000
                                    [From] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [address] => email@gmail.com
                                                    [name] => Dave
                                                )
                                        )

                                    [To] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [address] => email@dtbaker.
                                                    [name] => dtbaker Support
                                                )

                                            [1] => Array
                                                (
                                                    [address] => email+test@gmail.com
                                                )
                                        )

                                    [Cc] => Array
                                        (
                                            [0] => Array
                                                (
                                                    [address] => email+testcc@gmail.com
                                                )

                                            [1] => Array
                                                (
                                                    [address] => info@email.com.au
                                                    [name] => Hayley
                                                )
                                        )
                                ) */
						if ( $to_regex ) {
							$to_match = false;
							foreach ( $results['To'] as $possible_to_address ) {
								if ( preg_match( $to_regex, $possible_to_address['address'] ) ) {
									$to_match = true;
								}
							}
							if ( ! $to_match ) {
								continue;
							}
						}

						// find out which accout this sender is from.
						if ( preg_match( '/@(.*)$/', $from_address, $matches ) ) {

							// run a hook now to parse the from address.

							$domain = $matches[1];

							// find this sender in the database.
							// if we cant find this sender/customer in the database
							// then we add this customer as a "support user" to the default customer for this ticketing system.
							// based on the "to" address of this message.

							//store this as an eamil
							$email_to       = '';
							$email_to_first = current( $results['To'] );
							if ( $email_to_first ) {
								$email_to = $email_to_first['address'];
							}

							// work out the from and to users.
							$from_user_id = 0;
							$to_user_id   = 0; // this is admin. leave blank for now i guess.
							// try to find a user based on this from email address.
							$sql                 = "SELECT * FROM `" . _DB_PREFIX . "user` u WHERE u.`email` LIKE '" . db_escape( $from_address ) . "' ORDER BY `date_created` DESC";
							$ticket_user_account = $from_user = qa1( $sql );

							// convert the name if it's encoded strangely:
							if ( isset( $results['From'][0]['name'] ) && strlen( $results['From'][0]['name'] ) && isset( $results['Encoding'] ) && strtolower( $results['Encoding'] ) != 'utf8' && strtolower( $results['Encoding'] ) != 'utf-8' ) {
								//$name_decoded = quoted_printable_decode($results['From'][0]['name']);
								if ( function_exists( 'mb_convert_encoding' ) ) {
									$name_decoded = mb_convert_encoding( $results['From'][0]['name'], 'UTF-8', $results['Encoding'] );
									if ( strlen( $name_decoded ) > 0 ) {
										$results['From'][0]['name'] = $name_decoded;
									}
								}
							}

							// todo! this user may be in the system twice!
							// eg: once from submitting a ticket - then again when creating that user as a contact under a different customer.
							// so we find the latest entry and use that... ^^ done! updated the above to sort by date updated.
							if ( $from_user ) {
								$from_user_id = $from_user['user_id'];
								// woo!!found a user. assign this customer to the ticket.
								if ( $from_user['customer_id'] ) {
									//$account['default_customer_id'] = $from_user['customer_id'];
								}

							} else if ( module_config::c( 'ticket_allow_new_from_email', 1 ) ) {
								// create a user under this account customer because we allow new emails to be created
								if ( $account['default_customer_id'] ) {
									// create a new support user! go go!
									$ticket_user_account = $from_user = array(
										'name'        => isset( $results['From'][0]['name'] ) ? $results['From'][0]['name'] : $from_address,
										'customer_id' => $account['default_customer_id'],
										'email'       => $from_address,
										'status_id'   => 1,
										'password'    => substr( md5( time() . mt_rand( 0, 600 ) ), 3 ),
									);
									global $plugins;
									$from_user_id                   = $plugins['user']->create_user( $from_user, 'support' );
									$ticket_user_account['user_id'] = $from_user_id;
								} else {
									$ticket_user_account = $from_user = array(
										'name'        => isset( $results['From'][0]['name'] ) ? $results['From'][0]['name'] : $from_address,
										'customer_id' => - 1, // instead of 0, use -1.
										'email'       => $from_address,
										'status_id'   => 1,
										'password'    => substr( md5( time() . mt_rand( 0, 600 ) ), 3 ),
									);
									global $plugins;
									$from_user_id                   = $plugins['user']->create_user( $from_user, 'support' );
									$ticket_user_account['user_id'] = $from_user_id;
									//echo 'Failed - no from account set';
									//continue;
								}
							}
							if ( ! $from_user_id ) {
								// creating a new user for this ticket. not allowed for spam reasons sometimes.
								if ( module_config::c( 'ticket_allow_new_from_email', 1 ) ) {
									// failed to create a user in the database.
									echo 'Failed - cannot find the from user id';
									echo $from_address . ' to ' . var_export( $results['To'], true ) . ' : subject: ' . $overview->subject . '<hr>';
									continue;
								} else {
									// new option to ignore these emails and force people to submit new tickets via the web interface
									// send an autoreply to this user saying that their ticket was not created.
									$temp_from_user = array(
										'name'  => isset( $results['From'][0]['name'] ) ? $results['From'][0]['name'] : $from_address,
										'email' => $from_address,
									);
									module_ticket::send_customer_rejection_alert( $temp_from_user, $overview->subject );
									echo 'Rejecting new tickets from ' . $from_address;
									echo "\n\n";
									print_r( $results );
									$parse_success = true;
									continue;
								}
							}


							$message_type_id = _TICKET_MESSAGE_TYPE_CREATOR; // from an end user.
							if ( strtolower( $from_address ) == strtolower( $email_account_address ) ) {
								$message_type_id = _TICKET_MESSAGE_TYPE_ADMIN; // from an admin replying via email.
							} else if ( strtolower( $from_address ) == strtolower( module_config::c( 'ticket_admin_email_alert' ) ) ) {
								$message_type_id = _TICKET_MESSAGE_TYPE_ADMIN; // from an admin replying via email.
							} else if ( isset( $admins_rel[ $from_user_id ] ) ) {
								$message_type_id = _TICKET_MESSAGE_TYPE_ADMIN; // from an admin replying via email.
							}


							$sql          = "SELECT * FROM `" . _DB_PREFIX . "user` u WHERE u.`email` LIKE '" . db_escape( $email_to ) . "'";
							$to_user_temp = qa1( $sql );
							if ( $to_user_temp ) {
								$to_user_id = $to_user_temp['user_id'];
								// hack for BCC support (eg: email invoice, bcc goes to our ticket email address).
								if ( $message_type_id == _TICKET_MESSAGE_TYPE_ADMIN ) {
									// swap these around. the email is coming from us to the customer.
									$ticket_user_account = array(
										'customer_id' => $to_user_temp['customer_id'],
										'user_id'     => $to_user_temp['user_id'],
									);
								}
							}

							$ticket_id   = false;
							$new_message = true;
							// check if the subject matches an existing ticket subject.
							if ( preg_match( '#\[TICKET:(\d+)\]#i', $overview->subject, $subject_matches ) || preg_match( '#\#(\d+)#', $overview->subject, $subject_matches ) ) {
								// found an existing ticket.
								// find this ticket in the system.
								$ticket_id = ltrim( $subject_matches[1], '0' );
								// see if it exists.
								$existing_ticket = get_single( 'ticket', 'ticket_id', $ticket_id );
								if ( $existing_ticket ) {
									// woot!
									// search to see if this "from" address is in any of the past ticket messages.
									$valid_previous_contact = false;
									if ( $message_type_id == _TICKET_MESSAGE_TYPE_ADMIN ) {
										$valid_previous_contact = true;
									} else {
										$past_ticket_messages = self::get_ticket_messages( $existing_ticket['ticket_id'], true );
										//foreach($past_ticket_messages as $past_ticket_message){
										while ( $past_ticket_message = mysqli_fetch_assoc( $past_ticket_messages ) ) {
											$past_header_cache = @unserialize( $past_ticket_message['cache'] );
											$past_to_temp      = array();
											if ( $past_ticket_message['to_user_id'] ) {
												$past_to_temp = module_user::get_user( $past_ticket_message['to_user_id'], false );
											} else {
												if ( $past_header_cache && isset( $past_header_cache['to_email'] ) ) {
													$past_to_temp['email'] = $past_header_cache['to_email'];
												}
											}
											if ( isset( $past_to_temp['email'] ) && strtolower( $past_to_temp['email'] ) == strtolower( $from_address ) ) {
												$valid_previous_contact = true;
												break;
											}
											foreach ( array( 'to_emails', 'cc_emails', 'bcc_emails' ) as $header_cache_key ) {
												if ( $past_header_cache && isset( $past_header_cache[ $header_cache_key ] ) && is_array( $past_header_cache[ $header_cache_key ] ) ) {
													foreach ( $past_header_cache[ $header_cache_key ] as $to_email_additional ) {
														if ( isset( $to_email_additional['address'] ) && strlen( $to_email_additional['address'] ) && strtolower( $to_email_additional['address'] ) == strtolower( $from_address ) ) {
															$valid_previous_contact = true;
															break 3;
														}
													}
												}
											}
										}
									}
									if ( $valid_previous_contact ) {
										update_insert( 'ticket_id', $ticket_id, 'ticket', array(
											'status_id'              => _TICKET_STATUS_IN_PROGRESS_ID,// change status to in progress.
											'last_message_timestamp' => strtotime( $overview->date ),
										) );
										$new_message = false;
									} else {
										// create new message based on this one.
										// remove the old ticket ID number from subject
										$ticket_id         = false;
										$overview->subject = str_replace( $subject_matches[0], '', $overview->subject );
									}
								} else {
									// fail..
									$ticket_id = false;
								}
							} else {
								// we search for this subject, and this sender, to see if they have sent a follow up
								// before we started the ticketing system.
								// handy for importing an existing inbox with replies etc..

								// check to see if the subject matches any existing subjects.
								$search_subject1 = trim( preg_replace( '#^Re:?\s*#i', '', $overview->subject ) );
								$search_subject2 = trim( preg_replace( '#^Fwd?:?\s*#i', '', $overview->subject ) );
								$search_subject3 = trim( $overview->subject );
								// find any threads that match this subject, from this user id.
								$sql   = "SELECT * FROM `" . _DB_PREFIX . "ticket` t ";
								$sql   .= " WHERE t.`user_id` = " . (int) $from_user_id . " ";
								$sql   .= " AND ( t.`subject` LIKE '%" . db_escape( $search_subject1 ) . "%' OR ";
								$sql   .= " t.`subject` LIKE '%" . db_escape( $search_subject2 ) . "%' OR ";
								$sql   .= " t.`subject` LIKE '%" . db_escape( $search_subject3 ) . "%') ";
								$sql   .= " ORDER BY ticket_id DESC;";
								$match = qa1( $sql );
								if ( count( $match ) && (int) $match['ticket_id'] > 0 ) {
									// found a matching email. stoked!
									// add it in as a reply from the end user.
									$ticket_id = $match['ticket_id'];
									update_insert( 'ticket_id', $ticket_id, 'ticket', array(
										'status_id'              => _TICKET_STATUS_IN_PROGRESS_ID,// change status to in progress.
										'last_message_timestamp' => strtotime( $overview->date ),
									) );
									$new_message = false;

								}

								if ( ! $ticket_id ) {
									// now we see if any match the "TO" address, ie: it's us replying to the user.
									// handly from a gmail import.
									if ( $email_to ) {
										$sql          = "SELECT * FROM `" . _DB_PREFIX . "user` u WHERE u.`email` LIKE '" . db_escape( $email_to ) . "'";
										$temp_to_user = qa1( $sql );
										if ( $temp_to_user && $temp_to_user['user_id'] ) {
											// we have sent emails to this user before...
											// check to see if the subject matches any existing subjects.

											$sql   = "SELECT * FROM `" . _DB_PREFIX . "ticket` t ";
											$sql   .= " WHERE t.`user_id` = " . (int) $temp_to_user['user_id'] . " ";
											$sql   .= " AND ( t.`subject` LIKE '%" . db_escape( $search_subject1 ) . "%' OR ";
											$sql   .= " t.`subject` LIKE '%" . db_escape( $search_subject2 ) . "%' OR ";
											$sql   .= " t.`subject` LIKE '%" . db_escape( $search_subject3 ) . "%') ";
											$sql   .= " ORDER BY ticket_id DESC;";
											$match = qa1( $sql );
											if ( count( $match ) && (int) $match['ticket_id'] > 0 ) {
												// found a matching email. stoked!
												// add it in as a reply from the end user.
												$ticket_id = $match['ticket_id'];
												update_insert( 'ticket_id', $ticket_id, 'ticket', array(
													'status_id'              => _TICKET_STATUS_IN_PROGRESS_ID,// change status to in progress.
													'last_message_timestamp' => strtotime( $overview->date ),
												) );
												$new_message = false;

											}
										}
									}
								}
							}

							if ( ! $ticket_id ) {
								$new_ticket_data = array(
									'subject'                => $overview->subject,
									'ticket_account_id'      => $account['ticket_account_id'],
									'status_id'              => _TICKET_STATUS_NEW_ID,
									'user_id'                => $ticket_user_account['user_id'],
									'customer_id'            => $ticket_user_account['customer_id'],
									'assigned_user_id'       => $reply_from_user_id, // staff member
									'ticket_type_id'         => $ticket_type_id,
									'last_message_timestamp' => strtotime( $overview->date ),
								);
								// overwrite some settings based on the ticket_type_id
								if ( $ticket_type_id ) {
									$ticket_types = self::get_types();
									if ( isset( $ticket_types[ $ticket_type_id ] ) && isset( $ticket_types[ $ticket_type_id ]['default_user_id'] ) && $ticket_types[ $ticket_type_id ]['default_user_id'] > 0 ) {
										$new_ticket_data['assigned_user_id'] = $ticket_types[ $ticket_type_id ]['default_user_id'];
									}
								}
								$ticket_id = update_insert( 'ticket_id', 'new', 'ticket', $new_ticket_data );
								// set the default group if one exists for this particular ticket type.
								if ( $ticket_id && $ticket_type_id && isset( $ticket_types[ $ticket_type_id ] ) && ! empty( $ticket_types[ $ticket_type_id ]['default_groups'] ) ) {
									$default_groups = @unserialize( $ticket_types[ $ticket_type_id ]['default_groups'] );
									if ( is_array( $default_groups ) && count( $default_groups ) ) {
										foreach ( $default_groups as $group_id ) {
											module_group::add_to_group( $group_id, $ticket_id, 'ticket' );
										}
									}
								}

							}

							if ( ! $ticket_id ) {
								echo 'Error creating ticket';
								continue;
							}
							module_ticket::mark_as_unread( $ticket_id );

							$cache = array(
								'from_email' => $from_address,
								'to_email'   => $email_to,
								'to_emails'  => isset( $results['To'] ) && is_array( $results['To'] ) ? $results['To'] : array(),
								'cc_emails'  => isset( $results['Cc'] ) && is_array( $results['Cc'] ) ? $results['Cc'] : array(),
							);

							// pull otu the email bodyu.
							$body = $results['Data'];
							//if($from_address=='dtbaker@gmail.com'){
							if ( isset( $results['Encoding'] ) && strtolower( $results['Encoding'] ) != 'utf8' && strtolower( $results['Encoding'] ) != 'utf-8' ) {
								//mail('dtbaker@gmail.com','Ticket import results: Encoding',$results['Encoding']."\n\n".var_export($results,true));
								//$body2 = quoted_printable_decode($body);
								if ( function_exists( 'mb_convert_encoding' ) ) {
									$body3 = mb_convert_encoding( $body, 'UTF-8', $results['Encoding'] );
									//$body3 = mb_convert_encoding($body,'HTML-ENTITIES',$results['Encoding']);
									//$body4 = iconv_mime_decode($body,ICONV_MIME_DECODE_CONTINUE_ON_ERROR,"UTF-8");
									//mail('dtbaker@gmail.com','Ticket import results: Converted',$body . "\n\n\n\n\n ------------ " . $body2 . "\n\n\n\n\n ------------ " . $body3);
									if ( strlen( $body3 ) > 0 ) {
										$body = $body3;
									}
								}
							}
							//} // debug
							if ( $results['Type'] == "html" ) {
								$is_html = true;
							} else {
								// convert body to html, so we can do wrap.
								$body    = nl2br( $body );
								$is_html = true;
							}
							// find the alt body.
							$altbody = '';
							if ( isset( $results['Alternative'] ) && is_array( $results['Alternative'] ) ) {
								foreach ( $results['Alternative'] as $alt_id => $alt ) {
									if ( $alt['Type'] == "text" ) {
										$altbody = $alt['Data'];
										// if($from_address=='dtbaker@gmail.com'){
										if ( isset( $results['Encoding'] ) && strtolower( $results['Encoding'] ) != 'utf8' && strtolower( $results['Encoding'] ) != 'utf-8' ) {
											//$altbody2 = quoted_printable_decode($altbody);
											if ( function_exists( 'mb_convert_encoding' ) ) {
												$altbody3 = mb_convert_encoding( $altbody, 'UTF-8', $results['Encoding'] );
												if ( strlen( $altbody3 ) > 0 ) {
													$altbody = $altbody3;
												}
											}
										}
										//}
										break;
									}
								}
							}

							if ( ! $altbody ) {
								// should really never happen, but who knows.
								// edit - i think this happens with godaddy webmailer.
								$altbody = $body; // todo: strip any html.
								$altbody = preg_replace( '#<br[^>]*>\n*#imsU', "\n", $altbody );
								$altbody = strip_tags( $altbody );
							}


							// pass the body and altbody through a hook so we can modify it if needed.
							// eg: for envato tickets we strip the header/footer out and check the link to see if the buyer really bought anything.
							// run_hook(...

							//echo "<hr>$body<hr>$altbody<hr><br><br><br>";
							// save the message!
							$ticket_message_id = update_insert( 'ticket_message_id', 'new', 'ticket_message', array(
								'ticket_id'       => $ticket_id,
								'message_id'      => $message_id,
								'content'         => $altbody,
								// save html content later on.
								'htmlcontent'     => $body,
								'message_time'    => strtotime( $overview->date ),
								'message_type_id' => $message_type_id, // from a support user.
								'from_user_id'    => $from_user_id,
								'to_user_id'      => $to_user_id,
								'cache'           => serialize( $cache ),
								'status_id'       => _TICKET_STATUS_IN_PROGRESS_ID,// change status to in progress (same as above)
							) );

							if ( isset( $results['Related'] ) ) {
								foreach ( $results['Related'] as $related ) {
									if ( isset( $related['FileName'] ) && $related['FileName'] ) {
										// save as attachment against this email.
										$attachment_id = update_insert( 'ticket_message_attachment_id', 'new', 'ticket_message_attachment', array(
											'ticket_id'         => $ticket_id,
											'ticket_message_id' => $ticket_message_id,
											'file_name'         => $related['FileName'],
											'content_type'      => $related['Type'] . ( isset( $related['SubType'] ) ? '/' . $related['SubType'] : '' ),
										) );
										if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' ) ) {
											mkdir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/', 0777, true );
										}
										$result = file_put_contents( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attachment_id . '', $related['Data'] );
										if ( ! $result ) {
											send_error( "Failed to save attachment (named: " . $related['FileName'] . " for this email: \n\n\n\n" . var_export( $related, true ) . "\n\n\n" . var_export( $results, true ) . "\n\n\n" . $mail_content );
										}
									}
								}
							}
							if ( isset( $results['Attachments'] ) ) {
								foreach ( $results['Attachments'] as $related ) {
									if ( isset( $related['FileName'] ) && $related['FileName'] ) {
										// save as attachment against this email.
										$attachment_id = update_insert( 'ticket_message_attachment_id', 'new', 'ticket_message_attachment', array(
											'ticket_id'         => $ticket_id,
											'ticket_message_id' => $ticket_message_id,
											'file_name'         => $related['FileName'],
											'content_type'      => $related['Type'] . ( isset( $related['SubType'] ) ? '/' . $related['SubType'] : '' ),
										) );
										if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' ) ) {
											mkdir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/', 0777, true );
										}
										$result = file_put_contents( _UCM_FILE_STORAGE_DIR . 'includes/plugin_ticket/attachments/' . $attachment_id . '', $related['Data'] );
										if ( ! $result ) {
											send_error( "Failed to save attachment (named: " . $related['FileName'] . " for this email: \n\n\n\n" . var_export( $related, true ) . "\n\n\n" . var_export( $results, true ) . "\n\n\n" . $mail_content );
										}
									}
								}
							}

							//$new_message &&
							if ( ! preg_match( '#failure notice#i', $overview->subject ) ) {

								// we don't sent ticket autoresponders when the from user and to user are teh same
								if ( $from_user_id && $to_user_id && $from_user_id == $to_user_id ) {

								} else {
									$created_tickets [ $ticket_id ] = $ticket_id;
								}

							}

							$parse_success = true;

						}
					}
				}
			}

			if ( $parse_success && $account['delete'] ) {
				// remove email from inbox if needed.
				imap_delete( $mbox, $overview->msgno );
			}

			unlink( $tmp_file );
		}

		imap_errors();
		//}
		imap_expunge( $mbox );
		imap_close( $mbox );
		imap_errors();

		return $created_tickets;

	}

	public static function get_saved_responses() {
		// we use the extra module for saving canned responses for now.
		// why not? meh - use a new table later when we start with a FAQ system.
		$extra_fields = module_extra::get_extras( array( 'owner_table' => 'ticket_responses', 'owner_id' => 1 ) );

		$responses = array();
		foreach ( $extra_fields as $extra ) {
			$responses[ $extra['extra_id'] ] = $extra['extra_key'];
		}

		return $responses;
	}

	public static function get_saved_response( $saved_response_id ) {
		// we use the extra module for saving canned responses for now.
		// why not? meh - use a new table later when we start with a FAQ system.
		$extra = module_extra::get_extra( $saved_response_id );

		return array(
			'saved_response_id' => $extra['extra_id'],
			'name'              => $extra['extra_key'],
			'value'             => $extra['extra'],
		);
	}

	public static function save_saved_response( $saved_response_id, $data ) {
		// we use the extra module for saving canned responses for now.
		// why not? meh - use a new table later when we start with a FAQ system.
		$extra_db = array(
			'extra'       => $data['value'],
			'owner_table' => 'ticket_responses',
			'owner_id'    => 1,
		);
		if ( isset( $data['name'] ) && $data['name'] ) {
			$extra_db['extra_key'] = $data['name'];
		} else if ( ! (int) $saved_response_id ) {
			return; // not saving correctly.
		}
		$extra_id = update_insert( 'extra_id', $saved_response_id, 'extra', $extra_db );

		return $extra_id;
	}

	public static function get_ticket_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Ticket Access', array(
				_TICKET_ACCESS_ALL,
				_TICKET_ACCESS_ASSIGNED,
				_TICKET_ACCESS_CREATED,
				_TICKET_ACCESS_GROUP,
				_TICKET_ACCESS_CUSTOMER,
			) );
		} else {
			return _TICKET_ACCESS_ALL;
		}
	}

	public static function get_ticket_priorities() {
		$s = array(
			0 => _l( 'Normal' ),
			1 => _l( 'Medium' ),
			2 => _l( 'High' ),
		);
		if ( module_config::c( 'ticket_allow_priority', 0 ) ) {
			$s[ _TICKET_PRIORITY_STATUS_ID ] = _l( 'Paid' );
		}

		return $s;
	}

	/**
	 * @static
	 *
	 * @param $ticket_id
	 *
	 * @return array
	 *
	 * return a ticket recipient ready for sending a newsletter based on the ticket id.
	 *
	 */
	public static function get_newsletter_recipient( $ticket_id, $basic = false ) {
		if ( $basic ) {
			$sql    = "SELECT u.*, u.name AS user_name, t.* FROM `" . _DB_PREFIX . "ticket` t LEFT JOIN `" . _DB_PREFIX . "user` u USING (user_id) WHERE t.ticket_id = " . (int) $ticket_id;
			$ticket = qa1( $sql );
			if ( $ticket && $ticket['user_id'] ) {
				$name_parts           = explode( " ", preg_replace( '/\s+/', ' ', $ticket['user_name'] ) );
				$ticket['first_name'] = array_shift( $name_parts );
				$ticket['last_name']  = implode( ' ', $name_parts );
			} else {
				return false;
			}
		} else {
			$ticket = self::get_ticket( $ticket_id );
			if ( ! $ticket || ! (int) $ticket['ticket_id'] || ! (int) $ticket['user_id'] ) {
				return false;
			} // doesn't exist any more
			// some other details the newsletter system might need.
			$contact                  = module_user::get_user( $ticket['user_id'], false );
			$name_parts               = explode( " ", preg_replace( '/\s+/', ' ', $contact['name'] ) );
			$ticket['first_name']     = array_shift( $name_parts );
			$ticket['last_name']      = implode( ' ', $name_parts );
			$ticket['email']          = $contact['email'];
			$ticket['public_link']    = self::link_public( $ticket_id );
			$ticket['ticket_number']  = self::ticket_number( $ticket_id );
			$ticket['ticket_subject'] = $ticket['subject'];
			unset( $ticket['subject'] );
			if ( $ticket['status_id'] == 2 || $ticket['status_id'] == 3 || $ticket['status_id'] == 5 ) {
				$ticket['pending_status'] = _l( '%s out of %s tickets', ordinal( $ticket['position'] ), $ticket['total_pending'] );
			} else {
				$ticket['pending_status'] = 'ticket completed';
			}
			$ticket['_edit_link'] = self::link_open( $ticket_id, false, $ticket );
		}

		return $ticket;
	}

	private function _handle_save_ticket() {

		$ticket_data = $_POST;
		$ticket_id   = (int) $_REQUEST['ticket_id'];
		// check security can user edit this ticket
		if ( $ticket_id > 0 ) {
			$test = self::get_ticket( $ticket_id );
			if ( ! $test || $test['ticket_id'] != $ticket_id ) {
				$ticket_id = 0;
			}
		}
		// handle some security before passing if off to the save
		if ( ! self::can_edit_tickets() ) {
			// dont allow new "types" to be created
			/*if(isset($ticket_data['type']) && $ticket_data['type']){
                $types = self::get_types();
                $existing=false;
                foreach($types as $type){
                    if($type==$ticket_data['type']){
                        $existing=true;
                    }
                }
                if(!$existing){
                    unset($ticket_data['type']);
                }
            }*/
			if ( isset( $ticket_data['change_customer_id'] ) ) {
				unset( $ticket_data['change_customer_id'] );
			}
			if ( isset( $ticket_data['change_user_id'] ) ) {
				unset( $ticket_data['change_user_id'] );
			}
			if ( isset( $ticket_data['ticket_account_id'] ) ) {
				unset( $ticket_data['ticket_account_id'] );
			}
			if ( isset( $ticket_data['assigned_user_id'] ) ) {
				unset( $ticket_data['assigned_user_id'] );
			}
			if ( isset( $ticket_data['change_status_id'] ) ) {
				unset( $ticket_data['change_status_id'] );
			}
			if ( isset( $ticket_data['change_assigned_user_id'] ) ) {
				unset( $ticket_data['change_assigned_user_id'] );
			}
			if ( isset( $ticket_data['priority'] ) ) {
				unset( $ticket_data['priority'] );
			}
			if ( $ticket_id > 0 && isset( $ticket_data['status_id'] ) ) {
				unset( $ticket_data['status_id'] );
			}
			if ( $ticket_id > 0 && isset( $ticket_data['user_id'] ) ) {
				unset( $ticket_data['user_id'] );
			}
		}
		$ticket_data = array_merge( self::get_ticket( $ticket_id ), $ticket_data );
		if ( isset( $_REQUEST['mark_as_unread'] ) && $_REQUEST['mark_as_unread'] ) {
			$ticket_data['unread'] = 1;
		}
		if ( isset( $ticket_data['change_customer_id'] ) && (int) $ticket_data['change_customer_id'] > 0 && $ticket_data['change_customer_id'] != $ticket_data['customer_id'] ) {
			// we are changing customer ids
			// todo - some extra logic in here to swap the user contact over to this new customer or something?
			$ticket_data['customer_id'] = $ticket_data['change_customer_id'];
		}
		if ( isset( $ticket_data['change_user_id'] ) && (int) $ticket_data['change_user_id'] > 0 && $ticket_data['change_user_id'] != $ticket_data['user_id'] ) {
			// we are changing customer ids
			// todo - some extra logic in here to swap the user contact over to this new customer or something?
			$ticket_data['user_id'] = $ticket_data['change_user_id'];
		}
		$ticket_id = $this->save_ticket( $ticket_id, $ticket_data );

		// run the envato hook incase we're posting data to our sidebar bit.
		ob_start();
		handle_hook( 'ticket_sidebar', $ticket_id );
		ob_end_clean();

		if ( isset( $_REQUEST['generate_priority_invoice'] ) ) {
			$invoice_id = $this->generate_priority_invoice( $ticket_id );
			redirect_browser( module_invoice::link_public( $invoice_id ) );
		}

		set_message( "Ticket saved successfully" );
		if ( isset( $_REQUEST['butt_notify_staff'] ) && $_REQUEST['butt_notify_staff'] ) {
			redirect_browser( $this->link_open_notify( $ticket_id, false, $ticket_data ) );
		} else if ( isset( $_REQUEST['mark_as_unread'] ) && $_REQUEST['mark_as_unread'] ) {
			$url = $this->link_open( false );
			$url .= ( strpos( '?', $url ) !== false ? '?' : '&' ) . 'do_last_search';
			redirect_browser( $url );
		} else {
			if ( isset( $_REQUEST['newmsg_next'] ) && isset( $_REQUEST['next_ticket_id'] ) && (int) $_REQUEST['next_ticket_id'] > 0 ) {
				$key = array_search( $ticket_id, $_SESSION['_ticket_nextprev'] );
				if ( $key !== false ) {
					unset( $_SESSION['_ticket_nextprev'][ $key ] );
				}
				redirect_browser( $this->link_open( $_REQUEST['next_ticket_id'] ) );
			}
			redirect_browser( $this->link_open( $ticket_id ) );
		}
	}

	public static function get_total_ticket_count( $customer_id = false ) {
		$ticket_count = module_cache::get( 'ticket', 'ticket_total_count' . $customer_id );
		if ( $ticket_count === false ) {
			if ( $customer_id > 0 ) {
				$tickets = self::get_tickets( array(
					'customer_id' => $customer_id,
					'status_id'   => '2,3,5'
				) ); //,'status_id'=>-1
			} else {
				$tickets = self::get_tickets( array( 'status_id' => '2,3,5' ) );
			}
			$ticket_count = mysqli_num_rows( $tickets );
			module_cache::put( 'ticket', 'ticket_total_count' . $customer_id, $ticket_count );
		}

		return $ticket_count;
	}

	public static function get_unread_ticket_count( $customer_id = false ) {
		$ticket_count = module_cache::get( 'ticket', 'ticket_unread_count' . $customer_id );
		if ( $ticket_count === false ) {
			$search = array(
				'unread'    => 1,
				'status_id' => '<' . _TICKET_STATUS_RESOLVED_ID,
			);
			if ( $customer_id > 0 ) {
				$search['customer_id'] = $customer_id;
			}
			$res = self::get_tickets( $search );

			/*$sql = "SELECT * FROM `"._DB_PREFIX."ticket` t WHERE t.unread = 1 AND t.status_id < 6 ";
            // work out what customers this user can access?
            $ticket_access = self::get_ticket_data_access();
            switch($ticket_access){
                case _TICKET_ACCESS_ALL:

                    break;
                case _TICKET_ACCESS_ASSIGNED:
                    // we only want tickets assigned to me.
                    $sql .= " AND t.assigned_user_id = '".(int)module_security::get_loggedin_id()."'";
                    break;
                case _TICKET_ACCESS_CREATED:
                    // we only want tickets I created.
                    $sql .= " AND t.user_id = '".(int)module_security::get_loggedin_id()."'";
                    break;
            }
            $res = query($sql);*/
			$ticket_count = mysqli_num_rows( $res );
			module_cache::put( 'ticket', 'ticket_unread_count' . $customer_id, $ticket_count );
		}

		return $ticket_count;
	}

	public static function get_ticket_count( $faq_product_id = false ) {
		$foo = module_cache::get( 'ticket', 'ticket_count_ur' . ( $faq_product_id !== false ? $faq_product_id : '' ) );
		if ( $foo === false ) {
			$search = array(
				//'priority'=>_TICKET_PRIORITY_STATUS_ID,
				'status_id' => '<' . _TICKET_STATUS_RESOLVED_ID,
			);
			if ( $faq_product_id !== false && ( module_config::c( 'ticket_separate_product_queue', 0 ) || module_config::c( 'ticket_separate_product_menu', 0 ) ) ) {
				$search['faq_product_id'] = $faq_product_id;
			}
			$res            = self::get_tickets( $search );
			$ticket_count   = mysqli_num_rows( $res );
			$unread         = 0;
			$priority_count = 0;
			while ( $ticket = mysqli_fetch_assoc( $res ) ) {
				if ( $ticket['unread'] ) {
					$unread ++;
				}
				if ( $ticket['priority'] == _TICKET_PRIORITY_STATUS_ID ) {
					$priority_count ++;
				}
			}
			$foo = array(
				'priority' => $priority_count,
				'unread'   => $unread,
				'count'    => $ticket_count,
			);
			module_cache::put( 'ticket', 'ticket_count_ur' . ( $faq_product_id !== false ? $faq_product_id : '' ), $foo );
		}

		return $foo;
	}

	public static function get_reply_rate() {
		// cached?
		$rate = module_cache::get( 'ticket', 'ticket_count_rate' );
		if ( $rate === false ) {
			$rate = array(
				'daily'  => 0,
				'weekly' => 0,
			);
			// how many messages were replied to by the admin in the last week?
			$admins_rel = module_ticket::get_ticket_staff_rel();
			if ( count( $admins_rel ) ) {
				$sql            = "SELECT COUNT(*) AS c FROM `" . _DB_PREFIX . "ticket_message` WHERE from_user_id IN (" . implode( ', ', array_keys( $admins_rel ) ) . ") AND message_time >= " . (int) strtotime( '-7 days' );
				$res            = qa1( $sql );
				$rate['weekly'] = $res['c'];
				$rate['daily']  = ceil( $res['c'] / 7 );
				module_cache::put( 'ticket', 'ticket_count_rate', $rate );
			}
		}

		return $rate;
	}

	public static function hook_customer_contact_moved( $callback, $user_id, $old_customer_id, $customer_id ) {
		// $user_id has been moved from $old_customer_id to $customer_id
		// find all support tickets with a user_id / old_customer_id and update them to new customer id
		if ( (int) $user_id > 0 && (int) $customer_id > 0 ) {
			// find all the tickets from this user_id
			$tickets = get_multiple( 'ticket', array( 'user_id' => $user_id ) );
			foreach ( $tickets as $ticket ) {
				if ( $ticket['ticket_id'] && $ticket['user_id'] == $user_id ) {
					$sql = "UPDATE `" . _DB_PREFIX . "ticket` SET `customer_id` = " . (int) $customer_id . " WHERE `ticket_id` = " . (int) $ticket['ticket_id'] . ' LIMIT 1';
					query( $sql );
					// any invoices for this ticket.
					if ( $ticket['invoice_id'] ) {
						$sql = "UPDATE `" . _DB_PREFIX . "invoice` SET `customer_id` = " . (int) $customer_id . " WHERE `invoice_id` = " . (int) $ticket['invoice_id'] . ' LIMIT 1';
						query( $sql );
					}
				}
			}
		}
	}

	public static function hook_invoice_admin_list_job( $callback, $invoice_id ) {
		// see if any tickets match this  invoice.
		$tickets = get_multiple( 'ticket', array( 'invoice_id' => $invoice_id ) );
		if ( $tickets ) {
			foreach ( $tickets as $ticket ) {
				_e( 'Ticket: %s', module_ticket::link_open( $ticket['ticket_id'], true, $ticket ) );
			}
		}
	}

	public static function hook_invoice_sidebar( $callback, $invoice_id ) {
		// see if any tickets match this  invoice.
		$tickets = get_multiple( 'ticket', array( 'invoice_id' => $invoice_id ) );
		if ( $tickets ) {
			foreach ( $tickets as $ticket ) {
				?>
				<h3><?php _e( 'Priority Support Ticket' ); ?></h3>
				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1">
							<?php _e( 'Ticket' ); ?>
						</th>
						<td>
							<?php echo module_ticket::link_open( $ticket['ticket_id'], true, $ticket ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e( 'Subject' ); ?>
						</th>
						<td>
							<?php echo htmlspecialchars( $ticket['subject'] ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e( 'Status' ); ?>
						</th>
						<td>
							<?php
							$s = module_ticket::get_statuses();
							echo $s[ $ticket['status_id'] ];
							?>
						</td>
					</tr>
					</tbody>
				</table>
				<?php
			}
		}
	}

	public static function is_text_html( $text ) {
		if ( function_exists( 'is_text_html' ) ) {
			return is_text_html( $text );
		}

		return ( stripos( $text, '<br' ) !== false || stripos( $text, '<p>' ) !== false );
	}


	public static function bulk_handle_delete() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['delete'] ) && $_REQUEST['bulk_action']['delete'] == 'yes' && self::can_i( 'delete', 'Tickets' ) ) {
			// confirm deletion of these tickets:
			$ticket_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $ticket_ids as $ticket_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $ticket_ids[ $ticket_id ] );
				} else {
					$ticket_ids[ $ticket_id ] = '#' . self::ticket_number( $ticket_id );
				}
			}
			if ( count( $ticket_ids ) > 0 ) {
				if ( module_form::confirm_delete( 'ticket_id', "Really delete tickets: " . implode( ', ', $ticket_ids ), self::link_open( false ) ) ) {
					foreach ( $ticket_ids as $ticket_id => $ticket_number ) {
						self::delete_ticket( $ticket_id );
					}
					module_cache::clear( 'ticket' );
					set_message( _l( "%s tickets deleted successfully", count( $ticket_ids ) ) );
					//redirect_browser(self::link_open(false));
				}
			}
		}
	}

	public static function bulk_handle_unread() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['unread'] ) && $_REQUEST['bulk_action']['unread'] == 'yes' ) {
			// confirm deletion of these tickets:
			$ticket_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $ticket_ids as $ticket_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $ticket_ids[ $ticket_id ] );
				} else {
					$ticket_ids[ $ticket_id ] = '#' . self::ticket_number( $ticket_id );
				}
			}
			if ( count( $ticket_ids ) > 0 ) {
				foreach ( $ticket_ids as $ticket_id => $ticket_number ) {
					self::mark_as_unread( $ticket_id );
				}
				module_cache::clear( 'ticket' );
				set_message( _l( "%s tickets marked as unread successfully", count( $ticket_ids ) ) );
				//redirect_browser(self::link_open(false));
			}
		}
	}

	public static function bulk_handle_staff_change() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['change_assigned'] ) && $_REQUEST['bulk_action']['change_assigned'] == 'yes' && isset( $_REQUEST['bulk_change_staff_id'] ) ) {
			// confirm deletion of these tickets:
			$ticket_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $ticket_ids as $ticket_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $ticket_ids[ $ticket_id ] );
				} else {
					$ticket_ids[ $ticket_id ] = '#' . self::ticket_number( $ticket_id );
				}
			}
			if ( count( $ticket_ids ) > 0 ) {
				$staff_id = (int) $_REQUEST['bulk_change_staff_id'];
				foreach ( $ticket_ids as $ticket_id => $ticket_number ) {
					update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'assigned_user_id' => $staff_id ) );
				}
				module_cache::clear( 'ticket' );
				set_message( _l( "%s tickets changed to staff id %s", count( $ticket_ids ), $staff_id ) );
				//redirect_browser(self::link_open(false));
			}
		}
	}

	public static function bulk_handle_read() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['read'] ) && $_REQUEST['bulk_action']['read'] == 'yes' ) {
			// confirm deletion of these tickets:
			$ticket_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $ticket_ids as $ticket_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $ticket_ids[ $ticket_id ] );
				} else {
					$ticket_ids[ $ticket_id ] = '#' . self::ticket_number( $ticket_id );
				}
			}
			if ( count( $ticket_ids ) > 0 ) {
				foreach ( $ticket_ids as $ticket_id => $ticket_number ) {
					self::mark_as_read( $ticket_id );
				}
				module_cache::clear( 'ticket' );
				set_message( _l( "%s tickets marked as read successfully", count( $ticket_ids ) ) );
				//redirect_browser(self::link_open(false));
			}
		}
	}

	public static function bulk_handle_status() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['status_resolved'] ) && $_REQUEST['bulk_action']['status_resolved'] == 'yes' && isset( $_REQUEST['bulk_change_status_id'] ) && $_REQUEST['bulk_change_status_id'] > 0 ) {
			// confirm deletion of these tickets:
			$ticket_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $ticket_ids as $ticket_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $ticket_ids[ $ticket_id ] );
				} else {
					$ticket_ids[ $ticket_id ] = '#' . self::ticket_number( $ticket_id );
				}
			}
			if ( count( $ticket_ids ) > 0 ) {
				foreach ( $ticket_ids as $ticket_id => $ticket_number ) {
					update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'status_id' => $_REQUEST['bulk_change_status_id'] ) );
				}
				module_cache::clear( 'ticket' );
				$statuses = self::get_statuses();
				set_message( _l( "%s tickets marked as %s", count( $ticket_ids ), $statuses[ $_REQUEST['bulk_change_status_id'] ] ) );
				//redirect_browser(self::link_open(false));
			}
		}
	}

	public static function is_ticket_overdue( $ticket_id, $ticket_data ) {
		if ( ! empty( $ticket_data['due_timestamp'] ) ) {
			if ( $ticket_data['last_message_timestamp'] > $ticket_data['due_timestamp'] ) {
				return true;
			}
		} else {
			$limit_time = strtotime( '-' . module_config::c( 'ticket_turn_around_days', 5 ) . ' days', time() );
			if ( $ticket_data['last_message_timestamp'] < $limit_time ) {
				return true;
			}
		}

		return false;
	}


	public function get_upgrade_sql() {
		$sql = '';


		$res = qa1( "SHOW TABLES LIKE '" . _DB_PREFIX . "ticket_data'" );
		if ( ! $res || ! count( $res ) ) {
			$sql .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "ticket_data` (
    `ticket_data_id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_data_key_id` int(11) NOT NULL,
    `ticket_id` int(11) NOT NULL,
    `value` text NOT NULL,
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NOT NULL,
    `date_updated` date NOT NULL,
    `date_created` int(11) NOT NULL,
    PRIMARY KEY (`ticket_data_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;";
		}
		$res = qa1( "SHOW TABLES LIKE '" . _DB_PREFIX . "ticket_data_key'" );
		if ( ! $res || ! count( $res ) ) {
			$sql .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "ticket_data_key` (
              `ticket_data_key_id` int(11) NOT NULL AUTO_INCREMENT,
              `ticket_account_id` int(11) NOT NULL,
              `key` varchar(255) NOT NULL,
              `type` varchar(50) NOT NULL,
              `options` text NOT NULL,
              `order` int(11) NOT NULL DEFAULT '0',
                `encrypt_key_id` int(11) NOT NULL DEFAULT '0',
              `create_user_id` int(11) NOT NULL,
              `update_user_id` int(11) NOT NULL,
              `date_updated` date NOT NULL,
              `date_created` int(11) NOT NULL,
              PRIMARY KEY (`ticket_data_key_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		}

		$res = qa1( "SHOW TABLES LIKE '" . _DB_PREFIX . "ticket_message_attachment'" );
		if ( ! $res || ! count( $res ) ) {
			$sql_create = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'ticket_message_attachment` (
              `ticket_message_attachment_id` int(11) NOT NULL AUTO_INCREMENT,
              `ticket_id` int(11) DEFAULT NULL,
              `ticket_message_id` int(11) DEFAULT NULL,
              `file_name` varchar(255) NOT NULL,
              `content_type` varchar(60) NOT NULL,
              `create_user_id` int(11) NOT NULL,
              `update_user_id` int(11) NULL,
              `date_created` date NOT NULL,
              `date_updated` date NULL,
              PRIMARY KEY (`ticket_message_attachment_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;';
			query( $sql_create );
		}
		$res = qa1( "SHOW TABLES LIKE '" . _DB_PREFIX . "ticket_type'" );
		if ( ! $res || ! count( $res ) ) {
			$sql_create = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'ticket_type` (
              `ticket_type_id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `public` tinyint(1) NOT NULL DEFAULT \'0\',
                `default_user_id` int(11) NOT NULL DEFAULT \'0\',
                `default_groups` varchar(255) NOT NULL DEFAULT \'\',
              `create_user_id` int(11) NOT NULL,
              `update_user_id` int(11) NOT NULL,
              `date_updated` date NOT NULL,
              `date_created` int(11) NOT NULL,
              PRIMARY KEY (`ticket_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
            ';
			query( $sql_create );
		}

		$fields = get_fields( 'ticket_data_key' );
		if ( ! isset( $fields['encrypt_key_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket_data_key` ADD `encrypt_key_id` int(11) NOT NULL DEFAULT \'0\' AFTER  `order`;';
		}
		$fields = get_fields( 'ticket' );
		if ( ! isset( $fields['priority'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket` ADD `priority` INT NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['invoice_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket` ADD `invoice_id` INT NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['faq_product_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket` ADD `faq_product_id` INT NOT NULL DEFAULT  \'0\' AFTER  `ticket_account_id`;';
		}


		$fields = get_fields( 'ticket' );
		if ( ! isset( $fields['ticket_type_id'] ) ) {
			$ticket_type_sql = 'ALTER TABLE `' . _DB_PREFIX . 'ticket` ADD `ticket_type_id` INT NOT NULL DEFAULT  \'0\' AFTER  `type`;';
			query( $ticket_type_sql );
			// upgrade our ticket types into this new table.
			$sql_old_types = "SELECT `type` FROM `" . _DB_PREFIX . "ticket` GROUP BY `type` ORDER BY `type`";
			$statuses      = array();
			foreach ( qa( $sql_old_types ) as $r ) {
				if ( strlen( trim( $r['type'] ) ) > 0 ) {
					$ticket_type_id     = update_insert( 'ticket_type_id', 'new', 'ticket_type', array( 'name' => $r['type'] ) );
					$sql_ticket_type_id = "UPDATE `" . _DB_PREFIX . "ticket` SET ticket_type_id = '" . (int) $ticket_type_id . "' WHERE `type` = '" . db_escape( $r['type'] ) . "'";
					query( $sql_ticket_type_id );
				}
			}

		}
		if ( ! isset( $fields['due_timestamp'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket` ADD `due_timestamp` INT NULL AFTER  `last_message_timestamp`;';


		}
		$fields = get_fields( 'ticket_message' );
		if ( ! isset( $fields['create_user_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket_message` ADD `create_user_id` INT NOT NULL DEFAULT  \'0\';';
		}
		if ( ! isset( $fields['private_message'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket_message` ADD `private_message` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `status_id`;';
		}

		$fields = get_fields( 'ticket_type' );
		if ( ! isset( $fields['default_user_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket_type` ADD `default_user_id` INT(11) NOT NULL DEFAULT  \'0\';';
		}
		if ( ! isset( $fields['default_groups'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'ticket_type` ADD `default_groups` varchar(255) NOT NULL DEFAULT \'\';';
		}


		if ( ! $this->db_table_exists( 'ticket_quote_rel' ) ) {
			$sql .= 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'ticket_quote_rel` (
			    `ticket_id` int(11) NOT NULL,
			    `quote_id` int(11) NOT NULL,
			    PRIMARY KEY (`ticket_id`, `quote_id`)
			    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
            ';
		}
		if ( ! $this->db_table_exists( 'ticket_invoice_rel' ) ) {
			$sql .= 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'ticket_invoice_rel` (
			    `ticket_id` int(11) NOT NULL,
			    `invoice_id` int(11) NOT NULL,
			    PRIMARY KEY (`ticket_id`, `invoice_id`)
			    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
            ';
		}

		// todo - other tables.

		self::add_table_index( 'ticket', 'assigned_user_id' );
		self::add_table_index( 'ticket', 'ticket_account_id' );
		self::add_table_index( 'ticket', 'last_message_timestamp' );
		self::add_table_index( 'ticket', 'status_id' );
		self::add_table_index( 'ticket', 'user_id' );
		self::add_table_index( 'ticket', 'customer_id' );
		self::add_table_index( 'ticket', 'faq_product_id' );

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket` (
		`ticket_id` int(11) NOT NULL AUTO_INCREMENT,
		`ticket_account_id` int(11) NOT NULL,
		`faq_product_id` int(11) NOT NULL DEFAULT '0',
		`customer_id` int(11) DEFAULT NULL,
		`website_id` int(11) DEFAULT NULL,
		`user_id` int(11) NOT NULL,
		`invoice_id` int(11) NOT NULL,
		`priority` int(11) NOT NULL,
		`assigned_user_id` int(11) NOT NULL,
		`last_message_timestamp` int(11) NOT NULL,
		`due_timestamp` int(11) NOT NULL,
		`status_id` int(11) NOT NULL,
		`subject` varchar(255) NOT NULL DEFAULT '',
		`type` varchar(255) NOT NULL DEFAULT '',
		`ticket_type_id` INT NOT NULL DEFAULT  '0',
		`unread` tinyint(1) NOT NULL DEFAULT '1',
		`date_completed` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`ticket_id`),
		KEY `assigned_user_id` (`assigned_user_id`),
		KEY `ticket_account_id` (`ticket_account_id`),
		KEY `last_message_timestamp` (`last_message_timestamp`),
		KEY `status_id` (`status_id`),
		KEY `user_id` (`user_id`),
		KEY `customer_id` (`customer_id`),
		KEY `faq_product_id` (`faq_product_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_account` (
		`ticket_account_id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`email` varchar(255) NOT NULL,
		`username` varchar(255) NOT NULL,
		`password` varchar(255) NOT NULL,
		`host` varchar(255) NOT NULL,
		`port` int(11) NOT NULL DEFAULT '110',
		`delete` tinyint(4) NOT NULL DEFAULT '0',
		`default_user_id` int(11) NOT NULL DEFAULT '0',
		`default_customer_id` int(11) NOT NULL DEFAULT '0',
		`default_type` int(11) NOT NULL,
		`subject_regex` varchar(255) NOT NULL,
		`body_regex` varchar(255) NOT NULL,
		`to_regex` varchar(255) NOT NULL,
		`start_date` datetime NOT NULL,
		`secure` tinyint(4) NOT NULL DEFAULT '0',
		`imap` tinyint(4) NOT NULL DEFAULT '0',
		`search_string` varchar(255) NOT NULL,
		`mailbox` varchar(255) NOT NULL,
		`last_checked` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		PRIMARY KEY (`ticket_account_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_message` (
		`ticket_message_id` int(11) NOT NULL AUTO_INCREMENT,
		`ticket_id` int(11) DEFAULT NULL,
		`message_id` varchar(255) NOT NULL,
		`content` text NOT NULL,
		`htmlcontent` text NOT NULL,
		`message_time` int(11) NOT NULL,
		`message_type_id` int(11) NOT NULL,
		`from_user_id` int(11) NOT NULL,
		`to_user_id` int(11) NOT NULL,
		`cache` text NOT NULL,
		`status_id` int(11) NOT NULL,
		`private_message` tinyint(1) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL DEFAULT '0',
		`update_user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`ticket_message_id`),
		KEY `message_id` (`message_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

		ALTER TABLE  `<?php echo _DB_PREFIX; ?>ticket_message` ADD INDEX (  `ticket_id` );


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_message_attachment` (
		`ticket_message_attachment_id` int(11) NOT NULL AUTO_INCREMENT,
		`ticket_id` int(11) DEFAULT NULL,
		`ticket_message_id` int(11) DEFAULT NULL,
		`file_name` varchar(255) NOT NULL,
		`content_type` varchar(60) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`ticket_message_attachment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_type` (
		`ticket_type_id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`public` tinyint(1) NOT NULL DEFAULT '0',
		`default_user_id` int(11) NOT NULL DEFAULT '0',
		`default_groups` varchar(255) NOT NULL DEFAULT '',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`date_updated` date NOT NULL,
		`date_created` int(11) NOT NULL,
		PRIMARY KEY (`ticket_type_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_data` (
		`ticket_data_id` int(11) NOT NULL AUTO_INCREMENT,
		`ticket_data_key_id` int(11) NOT NULL,
		`ticket_id` int(11) NOT NULL,
		`value` text NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`date_updated` date NOT NULL,
		`date_created` int(11) NOT NULL,
		PRIMARY KEY (`ticket_data_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_data_key` (
		`ticket_data_key_id` int(11) NOT NULL AUTO_INCREMENT,
		`ticket_account_id` int(11) NOT NULL,
		`key` varchar(255) NOT NULL,
		`type` varchar(50) NOT NULL,
		`options` text NOT NULL,
		`order` int(11) NOT NULL DEFAULT '0',
		`encrypt_key_id` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`date_updated` date NOT NULL,
		`date_created` int(11) NOT NULL,
		PRIMARY KEY (`ticket_data_key_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_quote_rel` (
		`ticket_id` int(11) NOT NULL,
		`quote_id` int(11) NOT NULL,
		PRIMARY KEY (`ticket_id`, `quote_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>ticket_invoice_rel` (
		`ticket_id` int(11) NOT NULL,
		`invoice_id` int(11) NOT NULL,
		PRIMARY KEY (`ticket_id`, `invoice_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		<?php
		// todo: add default admin permissions.

		return ob_get_clean();
	}


}