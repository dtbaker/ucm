<?php

define( '_CALENDAR_ACCESS_ALL', 'All calendar entries in system' ); // do not change string
define( '_CALENDAR_ACCESS_ASSIGNED', 'Only from Customers or assigned items' ); // do not change string

class module_calendar extends module_base {

	var $links;

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
		$this->module_name     = "calendar";
		$this->module_position = 4.1;

		$this->version = 2.238;
		// 2.238 - 2017-05-02 - calendar staff name
		// 2.237 - 2016-10-06 - fix for multi-day events
		// 2.236 - 2016-07-10 - big update to mysqli
		// 2.235 - 2016-03-14 - language translation fix
		// 2.234 - 2015-08-04 - 12pm bug fix
		// 2.233 - 2015-07-29 - calendar functions
		// 2.232 - 2015-06-10 - calendar more events date fix
		// 2.231 - 2015-06-09 - calendar timezone ical fix
		// 2.23 - 2015-06-08 - calendar customer address in ical
		// 2.229 - 2015-05-14 - am/pm bug fix
		// 2.228 - 2015-04-12 - full calendar export via ical
		// 2.227 - 2015-03-08 - color picker visible fix
		// 2.226 - 2015-01-28 - 12/24 hour fix, missing image and save event bug fix
		// 2.225 - 2014-12-23 - 12/24 hour time fix on month view
		// 2.224 - 2014-10-07 - calendar_default_view setting week/month/day
		// 2.223 - 2014-09-16 - calendar_start_hour and calendar_end_hour added
		// 2.222 - 2014-09-02 - calendar_hour_format 12 or 24 setting fixed
		// 2.221 - 2014-08-18 - calendar_hour_format 12 or 24 setting added
		// 2.22 - 2014-07-18 - customer default fix
		// 2.219 - 2014-07-16 - create/edit/delete permission fix
		// 2.218 - 2014-07-15 - calendar ajax tweak
		// 2.217 - 2014-07-13 - new calendar features + permissions
		// 2.216 - 2014-07-13 - new calendar permissions
		// 2.215 - 2014-07-05 - calendar translation improvements
		// 2.214 - 2014-03-26 - displaying jobs in calendar
		// 2.213 - 2014-03-25 - fix for Customer calendar
		// 2.212 - 2014-03-19 - fix for Customer calendar
		// 2.21 - 2014-03-17 - basic Customer Calendar feature added
		// 2.132 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.131 - 2013-07-02 - language translation fix
		// 2.13 - buffering fix
		// 2.12 - permission fix
		// 2.11 - date format fix in cal export
		// 2.1 - initial

	}

	public function pre_menu() {

		if ( module_security::has_feature_access( array(
			'name'        => 'Settings',
			'module'      => 'config',
			'category'    => 'Config',
			'view'        => 1,
			'description' => 'view',
		) ) ) {
			$this->links[] = array(
				"name"                => "Calendar",
				"p"                   => "calendar_settings",
				"args"                => array( 'calendar_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}

		if ( $this->can_i( 'view', 'Calendar' ) ) {
			$this->links['calendar'] = array(
				"name"      => 'Calendar',
				"p"         => "calendar_admin",
				'icon_name' => 'calendar',
			);
			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
				$link_name = _l( 'Calendar' );

				$this->links['calendar_customer'] = array(
					"name"                => $link_name,
					"p"                   => "calendar_admin",
					'args'                => array( 'calendar_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'calendar',
				);
			}
		}
	}


	public function process() {

		if ( "ajax_calendar" == $_REQUEST['_process'] && module_calendar::can_i( 'view', 'Calendar' ) ) {
			// ajax functions from wdCalendar. copied from the datafeed.php sample files.
			header( 'Content-type: text/javascript' );
			$ret    = array();
			$method = isset( $_REQUEST['method'] ) ? $_REQUEST['method'] : false;
			switch ( $method ) {
				case "quick_add":
					if ( module_calendar::can_i( 'create', 'Calendar' ) ) {
						$ret = addCalendar( $_POST["CalendarStartTime"], $_POST["CalendarEndTime"], $_POST["CalendarTitle"], $_POST["IsAllDayEvent"] );
					}
					break;
				case "list":
					$ret = listCalendar( $_POST["showdate"], $_POST["viewtype"] );
					break;
				case "quick_update":
					if ( module_calendar::can_i( 'edit', 'Calendar' ) ) {
						$ret = updateCalendar( $_POST["calendarId"], $_POST["CalendarStartTime"], $_POST["CalendarEndTime"] );
					}
					break;
				case "quick_remove":
					if ( module_calendar::can_i( 'delete', 'Calendar' ) ) {
						$ret = removeCalendar( $_POST["calendarId"] );
					}
					break;
			}
			echo json_encode( $ret );
			exit;
		}
		if ( "save_calendar_entry" == $_REQUEST['_process'] ) {
			header( 'Content-type: text/javascript' );
			$calendar_id = isset( $_REQUEST['calendar_id'] ) ? (int) $_REQUEST['calendar_id'] : 0;
			$response    = array();
			if (
				( $calendar_id && module_calendar::can_i( 'edit', 'Calendar' ) ) ||
				( ! $calendar_id && module_calendar::can_i( 'create', 'Calendar' ) )
			) {
				$data = $_REQUEST;
				if ( isset( $data['start'] ) ) {
					$start_time = $data['start'];
					if ( isset( $data['start_time'] ) && ( ! isset( $data['is_all_day'] ) || ! $data['is_all_day'] ) ) {
						$data['is_all_day'] = 0;
						$time_hack          = $data['start_time'];
						$time_hack          = str_ireplace( _l( "am" ), '', $time_hack );
						$time_hack          = str_ireplace( _l( "pm" ), '', $time_hack );
						$bits               = explode( ':', $time_hack );
						if ( strpos( $data['start_time'], _l( "pm" ) ) ) {
							if ( $bits[0] < 12 ) {
								$bits[0] += 12;
							}
						}
						// add the time if it exists
						$start_time    .= ' ' . implode( ':', $bits ) . ':00';
						$data['start'] = strtotime( input_date( $start_time, true ) );
					} else {
						$data['start'] = strtotime( input_date( $start_time ) );

					}
				}
				if ( isset( $data['end'] ) ) {
					$end_time = $data['end'];
					if ( isset( $data['end_time'] ) && ( ! isset( $data['is_all_day'] ) || ! $data['is_all_day'] ) ) {
						$data['is_all_day'] = 0;
						$time_hack          = $data['end_time'];
						$time_hack          = str_ireplace( _l( "am" ), '', $time_hack );
						$time_hack          = str_ireplace( _l( "pm" ), '', $time_hack );
						$bits               = explode( ':', $time_hack );
						if ( strpos( $data['end_time'], _l( "pm" ) ) ) {
							if ( $bits[0] < 12 ) {
								$bits[0] += 12;
							}
						}
						// add the time if it exists
						$end_time .= ' ' . implode( ':', $bits ) . ':00';
						//echo $end_time;
						$data['end'] = strtotime( input_date( $end_time, true ) );
					} else {
						$data['end'] = strtotime( input_date( $end_time ) );

					}
				}
				if ( ! $data['start'] || ! $data['end'] ) {
					$response['message'] = 'Missing Date';
				} else {
					//print_r($_REQUEST); print_r($data); exit;
					$calendar_id = update_insert( 'calendar_id', $calendar_id, 'calendar', $data );
					if ( $calendar_id ) {
						// save staff members.
						$staff_ids = isset( $_REQUEST['staff_ids'] ) && is_array( $_REQUEST['staff_ids'] ) ? $_REQUEST['staff_ids'] : array();
						delete_from_db( 'calendar_user_rel', 'calendar_id', $calendar_id );
						foreach ( $staff_ids as $staff_id ) {
							if ( (int) $staff_id > 0 ) {
								$sql = "INSERT INTO `" . _DB_PREFIX . "calendar_user_rel` SET calendar_id = " . (int) $calendar_id . ", user_id = " . (int) $staff_id;
								query( $sql );
							}
						}
						$response['calendar_id'] = $calendar_id;
						$response['message']     = 'Success';
					} else {
						$response['message'] = 'Error Saving';
					}
				}
			} else {
				$response['message'] = 'Access Denied';
			}
			echo json_encode( $response );
			exit;
		}
	}

	public static function staff_hash( $staff_id ) {
		return md5( 'secret hash for staff member ' . $staff_id . ' with secret ' . _UCM_SECRET );
	}

	public static function link_calendar( $calendar_type, $options = array(), $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for calendar ' . _UCM_SECRET . ' ' . $calendar_type . serialize( $options ) );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.calendar/h.ical/i.' . $calendar_type . '/o.' . base64_encode( serialize( $options ) ) . '/hash.' . self::link_calendar( $calendar_type, $options, true ) . '/cal.ics' );
	}

	public static function link_calendar_ajax_functions( $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for ajax calendar ' . _UCM_SECRET );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=calendar&h=ajax&hash=' . self::link_calendar_ajax_functions( true ) . '' );
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'ical':
				$calendar_type = ( isset( $_REQUEST['i'] ) ) ? $_REQUEST['i'] : false;
				$options       = ( isset( $_REQUEST['o'] ) ) ? (array) unserialize( base64_decode( $_REQUEST['o'] ) ) : array();
				$hash          = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $calendar_type && $hash ) {
					$correct_hash = $this->link_calendar( $calendar_type, $options, true );
					if ( $correct_hash == $hash ) {

						if ( ob_get_level() ) {
							ob_end_clean();
						}
						include( 'pages/ical_' . basename( $calendar_type ) . '.php' );
						exit;

					}
				}
				break;
		}


	}


	public static function get_calendar_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Calendar Data Access', array(
				_CALENDAR_ACCESS_ALL,
				_CALENDAR_ACCESS_ASSIGNED,
			) );
		} else {
			return true;
		}
	}


	public static function get_calendar( $calendar_id ) {
		$calendar              = get_single( 'calendar', 'calendar_id', $calendar_id );
		$calendar['staff_ids'] = array();
		if ( $calendar_id > 0 ) {
			$s = get_multiple( 'calendar_user_rel', array( 'calendar_id' => $calendar_id ) );
			foreach ( $s as $user ) {
				$calendar['staff_ids'][] = $user['user_id'];
			}
		}

		return $calendar;
	}

	public function get_upgrade_sql() {
		$sql = '';


		if ( ! self::db_table_exists( 'calendar_user_rel' ) ) {
			$sql .= 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'calendar_user_rel` (
  `calendar_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`calendar_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		}


		return $sql;
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>calendar` (
		`calendar_id` int(11) NOT NULL AUTO_INCREMENT,
		`subject` varchar(255) NOT NULL DEFAULT '',
		`description` text NOT NULL,
		`location` varchar(255) NOT NULL DEFAULT '',
		`start` int(11) NOT NULL DEFAULT '0',
		`end` int(11) NOT NULL DEFAULT '0',
		`is_all_day` tinyint(1) NOT NULL DEFAULT '0',
		`color` varchar(50) NOT NULL DEFAULT '',
		`recurring_rule` varchar(500) NOT NULL DEFAULT '',
		`customer_id` int(11) NOT NULL DEFAULT '0',
		`job_id` int(11) NOT NULL DEFAULT '0',
		`quote_id` int(11) NOT NULL DEFAULT '0',
		`invoice_id` int(11) NOT NULL DEFAULT '0',
		`website_id` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL DEFAULT '0',
		`update_user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`calendar_id`),
		KEY `customer_id` (`customer_id`),
		KEY `invoice_id` (`invoice_id`),
		KEY `job_id` (`job_id`),
		KEY `website_id` (`website_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>calendar_user_rel` (
		`calendar_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		PRIMARY KEY (`calendar_id`,`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		<?php

		return ob_get_clean();
	}


}

if ( ! function_exists( 'js2PhpTime' ) ) {
	function js2PhpTime( $jsdate ) {
		$ret = '';
		if ( preg_match( '@(\d+)/(\d+)/(\d+)\s+(\d+):(\d+)@', $jsdate, $matches ) == 1 ) {
			$ret = mktime( $matches[4], $matches[5], 0, $matches[1], $matches[2], $matches[3] );
			//echo $matches[4] ."-". $matches[5] ."-". 0  ."-". $matches[1] ."-". $matches[2] ."-". $matches[3];
		} else if ( preg_match( '@(\d+)/(\d+)/(\d+)@', $jsdate, $matches ) == 1 ) {
			$ret = mktime( 0, 0, 0, $matches[1], $matches[2], $matches[3] );
			//echo 0 ."-". 0 ."-". 0 ."-". $matches[1] ."-". $matches[2] ."-". $matches[3];
		}

		return $ret;
	}
}

if ( ! function_exists( 'php2JsTime' ) ) {
	function php2JsTime( $phpDate ) {
		//echo $phpDate;
		//return "/Date(" . $phpDate*1000 . ")/";
		return date( "m/d/Y H:i", $phpDate );
	}
}


if ( ! function_exists( 'php2MySqlTime' ) ) {
	function php2MySqlTime( $phpDate ) {
		return date( "Y-m-d H:i:s", $phpDate );
	}
}

if ( ! function_exists( 'mySql2PhpTime' ) ) {
	function mySql2PhpTime( $sqlDate ) {
		$arr = date_parse( $sqlDate );

		return mktime( $arr["hour"], $arr["minute"], $arr["second"], $arr["month"], $arr["day"], $arr["year"] );

	}
}

// wdCalendar functions, modified to work with UCM database format
function addCalendar( $st, $et, $sub, $ade ) {
	$ret = array();
	try {
		$customer_data = isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ? module_customer::get_customer( $_REQUEST['customer_id'] ) : false;
		$calendar_id   = update_insert( 'calendar_id', false, 'calendar', array(
			'subject'     => $sub,
			'start'       => js2PhpTime( $st ),
			'end'         => js2PhpTime( $et ),
			'is_all_day'  => $ade,
			'customer_id' => $customer_data && isset( $customer_data['customer_id'] ) ? (int) $customer_data['customer_id'] : 0,
		) );
		if ( $calendar_id ) {
			$ret['IsSuccess'] = true;
			$ret['Msg']       = _l( 'add success' );
			$ret['Data']      = $calendar_id;
		} else {
			$ret['IsSuccess'] = false;
			$ret['Msg']       = _l( 'add failed' );
		}
	} catch ( Exception $e ) {
		$ret['IsSuccess'] = false;
		$ret['Msg']       = $e->getMessage();
	}

	return $ret;
}


function listCalendarByRange( $sd, $ed ) {
	$ret           = array();
	$ret['events'] = array();
	$ret["issort"] = true;
	$ret["start"]  = php2JsTime( $sd );
	$ret["end"]    = php2JsTime( $ed );
	$ret['error']  = null;

	$calendar_data_access = module_calendar::get_calendar_data_access();

	// hook into things like jobs and stuff who want to return calendar entries.
	$hook_results = hook_handle_callback( 'calendar_events', $sd, $ed );
	if ( is_array( $hook_results ) && count( $hook_results ) ) {
		foreach ( $hook_results as $hook_result ) {
			if ( is_array( $hook_result ) ) {
				foreach ( $hook_result as $result ) {
					// format our hook results to match our bad (indexed) array,
					// will update that array in the future
					/*$calendar_events[] = array(
							'subject' => $job['name'],
							'customer_id' => $job['customer_id'],
							'start_time' => $job['date_start'],
							'user_id' => $job['user_id'],
							'description' => 'Test Description',
							'link' => module_job::link_open($job['job_id'],true,$job),
					);*/
					$staff_names = array();
					if ( isset( $result['staff_ids'] ) && count( $result['staff_ids'] ) ) {
						switch ( $calendar_data_access ) {
							case _CALENDAR_ACCESS_ALL:

								break;
							case _CALENDAR_ACCESS_ASSIGNED:
							default:
								$current_user = module_security::get_loggedin_id();
								if ( ! in_array( $current_user, $result['staff_ids'] ) ) {
									continue 2;
								}
								break;
						}
						foreach ( $result['staff_ids'] as $staff_id ) {
							$staff_names[] = module_user::link_open( $staff_id, true );
						}
					}
					$staff_names = implode( ', ', $staff_names );

					$result[0]       = false; // no calendar ID at the moment
					$result[1]       = $result['subject'];
					$result[2]       = php2JsTime( $result['start_time'] );
					$result[3]       = php2JsTime( isset( $result['end_time'] ) ? $result['end_time'] : $result['start_time'] );
					$result[4]       = ! isset( $result['all_day'] ) || $result['all_day'];
					$result[5]       = 0;
					$result[6]       = 0;
					$result[7]       = 0;//col
					$result[8]       = 2;
					$result[9]       = 0;
					$result[10]      = 0;
					$result[13]      = $result['customer_id'];
					$result[12]      = $result['link'];
					$result[14]      = isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] != $result['customer_id'] ? 'chip-fade' : '';
					$result['staff'] = $staff_names;

					$ret['events'][] = $result;
				}
			}
		}
	}

	try {
		$sql = "select * from `" . _DB_PREFIX . "calendar` where `start` >= '" . db_escape( $sd ) . "' AND `start` <= '" . db_escape( $ed ) . "'";
		//  echo $sql;
		$rows = qa( $sql );
		foreach ( $rows as $row ) {
			//$ret['events'][] = $row;
			//$attends = $row->AttendeeNames;
			//if($row->OtherAttendee){
			//  $attends .= $row->OtherAttendee;
			//}
			//echo $row->StartTime;
			$more_than_1_day = date( 'Ymd', $row['start'] ) == date( 'Ymd', $row['end'] ) ? 0 : 1;
			$customer_name   = $customer_link = '';
			if ( $row['customer_id'] > 0 ) {
				$customer_data = module_customer::get_customer( $row['customer_id'], true, true );
				if ( ! $customer_data || $customer_data['customer_id'] != $row['customer_id'] ) {
					$row['customer_id'] = 0;
				} else {
					switch ( $calendar_data_access ) {
						case _CALENDAR_ACCESS_ALL:

							break;
						case _CALENDAR_ACCESS_ASSIGNED:
						default:
							if ( isset( $customer_data['_no_access'] ) ) {
								continue 2;
							}
							break;
					}
					$customer_name = $customer_data['customer_name'];
					$customer_link = module_customer::link_open( $row['customer_id'], true, $customer_data );
				}
			}

			$calendar_event = module_calendar::get_calendar( $row['calendar_id'] );
			$staff_names    = array();
			if ( count( $calendar_event['staff_ids'] ) ) {
				switch ( $calendar_data_access ) {
					case _CALENDAR_ACCESS_ALL:

						break;
					case _CALENDAR_ACCESS_ASSIGNED:
					default:
						$current_user = module_security::get_loggedin_id();
						if ( ! in_array( $current_user, $calendar_event['staff_ids'] ) ) {
							continue 2;
						}
						break;
				}
				foreach ( $calendar_event['staff_ids'] as $staff_id ) {
					$staff_names[] = module_user::link_open( $staff_id, true );
				}
			}
			$staff_names = implode( ', ', $staff_names );


			$ret['events'][] = array(
				0       => $row['calendar_id'],
				1       => $row['subject'],
				2       => php2JsTime( $row['start'] ),
				3       => php2JsTime( $row['end'] ),
				4       => $row['is_all_day'],
				5       => $more_than_1_day,
				//more than one day event
				//$row->InstanceType,
				6       => 0,
				//Recurring event,
				7       => $row['color'],
				8       => 1,
				//editable ( 0 not editable or clickable, 1 editable, 2 clickable but not editable - from hooks)
				9       => '',
				//location
				10      => '',
				//$attends
				11      => $customer_name,
				//customer name
				12      => $customer_link,
				13      => $row['customer_id'],
				14      => isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] != $row['customer_id'] ? 'chip-fade' : '',
				// should we fade this element out ?
				'staff' => $staff_names,
			);
		}
	} catch ( Exception $e ) {
		$ret['error'] = $e->getMessage();
	}

	// build bubble content based on event data:
	foreach ( $ret['events'] as $event_id => $event ) {
		if ( ! isset( $event['bubble'] ) ) {
			$ret['events'][ $event_id ]['bubble'] = '<div id="bbit-cs-buddle" style="z-index: 1080; width: 400px;visibility:hidden;" class="bubble"><table class="bubble-table" cellSpacing="0" cellPadding="0"><tbody><tr><td class="bubble-cell-side"><div id="tl1" class="bubble-corner"><div class="bubble-sprite bubble-tl"></div></div><td class="bubble-cell-main"><div class="bubble-top"></div><td class="bubble-cell-side"><div id="tr1" class="bubble-corner"><div class="bubble-sprite bubble-tr"></div></div>  <tr><td class="bubble-mid" colSpan="3"><div style="overflow: hidden" id="bubbleContent1"><div><div></div><div class="cb-root"><table class="cb-table" cellSpacing="0" cellPadding="0"><tbody>' .
			                                        '<tr>' .
			                                        '<td class="cb-value"><div class="textbox-fill-wrapper"><div class="textbox-fill-mid"><div id="bbit-cs-what" title="'
			                                        . htmlspecialchars( _l( 'View Details' ) ) . '" class="textbox-fill-div lk" style="cursor:pointer;">' . htmlspecialchars( $event[1] ) . '</div></div></div></td></tr><tr><td class=cb-value><div id="bbit-cs-buddle-timeshow"></div></td>' .
			                                        '</tr>' .
			                                        '<tr><td class=cb-value><div id="bbit-cs-customer-link">' . _l( 'Customer: %s', $event[12] ? $event[12] : _l( 'N/A' ) ) . '</div></td></tr>' .
			                                        ( isset( $event['other_details'] ) && strlen( $event['other_details'] ) ? '<tr><td class=cb-value><div id="bbit-cs-customer-link">' . $event['other_details'] . '</div></td></tr>' : '' ) .
			                                        '<tr><td class=cb-value><div id="bbit-cs-staff-link">' . _l( 'Staff: %s', $event['staff'] ? $event['staff'] : _l( 'N/A' ) ) . '</div></td></tr>' .
			                                        '</tbody></table>' .
			                                        ( $event[8] == 1 ?
				                                        '<div class="bbit-cs-split"><input id="bbit-cs-id" type="hidden" value=""/>' .
				                                        ( module_calendar::can_i( 'delete', 'Calendar' ) ? '[ <span id="bbit-cs-delete" class="lk">' . htmlspecialchars( _l( 'Delete' ) ) . '</span> ]&nbsp;' : '' ) .
				                                        ( module_calendar::can_i( 'edit', 'Calendar' ) ? ' <span id="bbit-cs-editLink" class="lk">' . htmlspecialchars( _l( 'Edit Event' ) ) . ' </span>' : '' ) .
				                                        '</div> ' : '' ) .
			                                        '</div></div></div><tr><td><div id="bl1" class="bubble-corner"><div class="bubble-sprite bubble-bl"></div></div><td><div class="bubble-bottom"></div><td><div id="br1" class="bubble-corner"><div class="bubble-sprite bubble-br"></div></div></tr></tbody></table><div id="bubbleClose2" class="bubble-closebutton"></div><div id="prong1" class="prong"><div class=bubble-sprite></div></div></div>';
		}
	}

	return $ret;
}

function listCalendar( $day, $type ) {
	$phpTime = js2PhpTime( $day );
	//echo $phpTime . "+" . $type;
	switch ( $type ) {
		case "month":
			$st = mktime( 0, 0, 0, date( "m", $phpTime ), 1, date( "Y", $phpTime ) );
			$et = mktime( 0, 0, - 1, date( "m", $phpTime ) + 1, 1, date( "Y", $phpTime ) );
			break;
		case "week":
			//suppose first day of a week is monday
			$monday = date( "d", $phpTime ) - date( 'N', $phpTime ) + 1;
			//echo date('N', $phpTime);
			$st = mktime( 0, 0, 0, date( "m", $phpTime ), $monday, date( "Y", $phpTime ) );
			$et = mktime( 0, 0, - 1, date( "m", $phpTime ), $monday + 7, date( "Y", $phpTime ) );
			break;
		case "day":
			$st = mktime( 0, 0, 0, date( "m", $phpTime ), date( "d", $phpTime ), date( "Y", $phpTime ) );
			$et = mktime( 0, 0, - 1, date( "m", $phpTime ), date( "d", $phpTime ) + 1, date( "Y", $phpTime ) );
			break;
	}

	//echo $st . "--" . $et;
	return listCalendarByRange( $st, $et );
}

function updateCalendar( $id, $st, $et ) {
	$ret = array();
	try {
		$calendar_id = update_insert( 'calendar_id', $id, 'calendar', array(
			'start' => js2PhpTime( $st ),
			'end'   => js2PhpTime( $et ),
		) );
		if ( $calendar_id ) {
			$ret['IsSuccess'] = true;
			$ret['Msg']       = _l( 'Change success' );
			$ret['Data']      = $calendar_id;
		} else {
			$ret['IsSuccess'] = false;
			$ret['Msg']       = _l( 'Change failed' );
		}
	} catch ( Exception $e ) {
		$ret['IsSuccess'] = false;
		$ret['Msg']       = $e->getMessage();
	}

	return $ret;
}


function removeCalendar( $id ) {
	$ret = array();
	try {
		if ( ! delete_from_db( 'calendar', 'calendar_id', $id ) ) {
			$ret['IsSuccess'] = false;
			$ret['Msg']       = module_db::last_error();
		} else {
			$ret['IsSuccess'] = true;
			$ret['Msg']       = 'Succefully';
		}
	} catch ( Exception $e ) {
		$ret['IsSuccess'] = false;
		$ret['Msg']       = $e->getMessage();
	}

	return $ret;
}

