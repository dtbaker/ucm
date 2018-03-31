<?php

define( '_TWITTER_MESSAGE_TYPE_MENTION', 1 );
define( '_TWITTER_MESSAGE_TYPE_MYTWEET', 2 );
define( '_TWITTER_MESSAGE_TYPE_OTHERTWEET', 3 );
define( '_TWITTER_MESSAGE_TYPE_MYRETWEET', 4 );
define( '_TWITTER_MESSAGE_TYPE_OTHERRETWEET', 5 );
define( '_TWITTER_MESSAGE_TYPE_DIRECT', 6 );


require_once( 'twitter.class.php' );


class module_social_twitter extends module_base {

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
		$this->module_name     = "social_twitter";
		$this->module_position = 25.2;

		$this->version = 2.133;
		// 2.133 - 2016-07-10 - big update to mysqli
		// 2.132 - 2016-01-23 - message type fix
		// 2.131 - 2014-08-04 - responsive im
		// 2.13 - 2014-05-23 - social fixes
		// 2.12 - 2014-04-05 - ability to disable social plugin
		// 2.11 - 2014-04-05 - better message archiving
		// 2.1 - 2014-04-05 - initial release

		if ( module_social::is_plugin_enabled() && self::is_plugin_enabled() ) {
			module_config::register_js( 'social_twitter', 'social_twitter.js' );
			module_config::register_css( 'social_twitter', 'social_twitter.css' );
		}

		//hook_add('get_social_methods','module_social_twitter::hook_get_social_methods');
	}

	public static function is_plugin_enabled() {
		if ( ! class_exists( 'module_social' ) ) {
			return false;
		}

		return parent::is_plugin_enabled();
	}

	public function pre_menu() {

		if ( module_social::is_plugin_enabled() && self::is_plugin_enabled() ) {
			if ( module_security::has_feature_access( array(
				'name'        => 'Settings',
				'module'      => 'config',
				'category'    => 'Config',
				'view'        => 1,
				'description' => 'view',
			) ) ) {
				$this->links[] = array(
					"name"                => "Twitter",
					"p"                   => "twitter_settings",
					"args"                => array( 'social_twitter_id' => false ),
					'holder_module'       => 'social', // which parent module this link will sit under.
					'holder_module_page'  => 'social_settings',  // which page this link will be automatically added to.
					'menu_include_parent' => 1,
				);
			}

			global $load_modules;

			if ( module_social::can_i( 'view', 'Twitter', 'Social', 'social' ) && $load_modules && $load_modules[0] == 'social' ) {
				$accounts = self::get_accounts();
				$twitter  = new ucm_twitter();
				foreach ( $accounts as $account ) {
					$twitter_account = new ucm_twitter_account( $account['social_twitter_id'] );
					$unread          = $twitter->get_unread_count( array( 'social_twitter_id' => $account['social_twitter_id'] ) );
					$this->links[]   = array(
						"name"                => $twitter_account->get( 'account_name' ) . ( $unread > 0 ? " <span class='menu_label'>" . $unread . "</span>" : '' ),
						"p"                   => "social_twitter_list",
						"args"                => array(
							'social_twitter_id'  => $account['social_twitter_id'],
							'social_facebook_id' => false
						),
						'holder_module'       => 'social', // which parent module this link will sit under.
						'holder_module_page'  => 'social_admin',  // which page this link will be automatically added to.
						'menu_include_parent' => 0,
						'current'             => isset( $_REQUEST['social_twitter_id'] ) && $_REQUEST['social_twitter_id'] == $account['social_twitter_id'],
						'allow_nesting'       => 0,
					);
				}
			}
		}
	}

	public function process() {

		if ( "save_twitter" == $_REQUEST['_process'] ) {

			$social_twitter_id = isset( $_REQUEST['social_twitter_id'] ) ? (int) $_REQUEST['social_twitter_id'] : 0;
			$twitter           = new ucm_twitter_account( $social_twitter_id );


			if ( isset( $_POST['butt_del'] ) && module_social::can_i( 'delete', 'Twitter', 'Social', 'social' ) ) {
				if ( module_form::confirm_delete(
					'social_twitter_id',
					"Really delete this Twitter account from the system? All messages will be lost.",
					self::link_open( $_REQUEST['social_twitter_id'] )
				) ) {
					$twitter->delete();
					set_message( "Twitter account deleted successfully" );
					redirect_browser( self::link_open( false ) );
				}
			}
			$twitter->save_data( $_POST );
			$social_twitter_id = $twitter->get( 'social_twitter_id' );

			if ( isset( $_POST['butt_save_connect'] ) ) {
				$redirect = $this->link_open( $social_twitter_id, false, false, 'twitter_account_connect' );
			} else {
				set_message( 'Twitter account saved successfully' );
				$redirect = $this->link_open( $social_twitter_id );
			}
			redirect_browser( $redirect );

			exit;
		} else if ( "send_twitter_message" == $_REQUEST['_process'] ) {

			if ( module_form::check_secure_key() ) {


				// queue the message into the twitter_message table
				// if there's a scheduled date in the past we send it in the past, no date we send straight away, date in the future we leave it in the db table for the cron job to pick up.
				//print_r($_POST);exit;

				$send_time = false; // default: now
				if ( isset( $_POST['schedule_date'] ) && isset( $_POST['schedule_time'] ) && ! empty( $_POST['schedule_date'] ) && ! empty( $_POST['schedule_time'] ) ) {
					$date      = $_POST['schedule_date'];
					$time_hack = $_POST['schedule_time'];
					$time_hack = str_ireplace( 'am', '', $time_hack );
					$time_hack = str_ireplace( 'pm', '', $time_hack );
					$bits      = explode( ':', $time_hack );
					if ( strpos( $_POST['schedule_time'], 'pm' ) ) {
						$bits[0] += 12;
					}
					// add the time if it exists
					$date      .= ' ' . implode( ':', $bits ) . ':00';
					$send_time = strtotime( input_date( $date, true ) );
				} else if ( isset( $_POST['schedule_date'] ) && ! empty( $_POST['schedule_date'] ) ) {
					$send_time = strtotime( input_date( $_POST['schedule_date'], true ) );
				}
				//echo print_date($send_time,true);
				//echo '<br>';
				//echo date('c',$send_time);
				//exit;

				$send_accounts           = isset( $_POST['compose_account_id'] ) && is_array( $_POST['compose_account_id'] ) ? $_POST['compose_account_id'] : array();
				$page_count              = 0;
				$last_twitter_account_id = false;
				if ( $send_accounts ) {
					foreach ( $send_accounts as $twitter_account_id => $tf ) {
						if ( ! $tf ) {
							continue;
						}
						// see if this is an available account.
						$twitter_account = new ucm_twitter_account( $twitter_account_id );
						//todo: check permissiont o access thi saccount
						if ( $twitter_account->get( 'social_twitter_id' ) == $twitter_account_id ) {
							// push to db! then send.
							$last_twitter_account_id = $twitter_account_id;
							$twitter_message         = new ucm_twitter_message( $twitter_account, false );
							$twitter_message->create_new();
							$twitter_message->update( 'social_twitter_id', $twitter_account->get( 'social_twitter_id' ) );
							$twitter_message->update( 'summary', isset( $_POST['message'] ) ? $_POST['message'] : '' );
							$twitter_message->update( 'type', 'pending' );
							$twitter_message->update( 'data', json_encode( $_POST ) );
							$twitter_message->update( 'user_id', module_security::get_loggedin_id() );
							// do we send this one now? or schedule it later.
							$twitter_message->update( 'status', _SOCIAL_MESSAGE_STATUS_PENDINGSEND );
							if ( $send_time ) {
								// schedule for sending at a different time (now or in the past)
								$twitter_message->update( 'message_time', $send_time );
							} else {
								// send it now.
								$twitter_message->update( 'message_time', 0 );
							}
							if ( isset( $_FILES['picture']['tmp_name'] ) && is_uploaded_file( $_FILES['picture']['tmp_name'] ) ) {
								$twitter_message->add_attachment( $_FILES['picture']['tmp_name'] );
							}
							$twitter_message->send_queued( isset( $_POST['debug'] ) && $_POST['debug'] );
							$page_count ++;

						} else {
							// log error?
						}
					}
				}

				set_message( _l( 'Message delivered successfully to %s Twitter accounts', $page_count ) );
				$redirect = $this->link_open_message_view( $last_twitter_account_id );
				redirect_browser( $redirect );

			}

			exit;


		} else if ( "ajax_social_twitter" == $_REQUEST['_process'] ) {
			// ajax functions from wdsocial. copied from the datafeed.php sample files.
			header( 'Content-type: text/javascript' );
			if ( module_form::check_secure_key() ) {
				$social_twitter_id = isset( $_REQUEST['social_twitter_id'] ) ? (int) $_REQUEST['social_twitter_id'] : 0;
				$twitter           = new ucm_twitter_account( $social_twitter_id );
				if ( $social_twitter_id && $twitter->get( 'social_twitter_id' ) == $social_twitter_id ) {
					$action          = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
					$message_id      = isset( $_REQUEST['social_twitter_message_id'] ) ? (int) $_REQUEST['social_twitter_message_id'] : 0;
					$twitter_message = new ucm_twitter_message();
					$twitter_message->load( $message_id );
					if ( $twitter_message->get( 'social_twitter_id' ) == $social_twitter_id && $twitter_message->get( 'social_twitter_message_id' ) == $message_id ) {
						switch ( $action ) {
							case "send-message-reply":
								if ( module_social::can_i( 'create', 'Twitter Comments', 'Social', 'social' ) ) {
									$return  = array();
									$message = isset( $_POST['message'] ) && $_POST['message'] ? $_POST['message'] : '';
									$debug   = isset( $_POST['debug'] ) && $_POST['debug'] ? $_POST['debug'] : false;
									if ( $message ) {
										ob_start();
										//$twitter_message->send_reply( $message, $debug );
										$new_twitter_message = new ucm_twitter_message( $twitter, false );
										$new_twitter_message->create_new();
										$new_twitter_message->update( 'reply_to_id', $twitter_message->get( 'social_twitter_message_id' ) );
										$new_twitter_message->update( 'social_twitter_id', $twitter->get( 'social_twitter_id' ) );
										$new_twitter_message->update( 'summary', $message );
										//$new_twitter_message->update('type','pending');
										$new_twitter_message->update( 'data', json_encode( $_POST ) );
										$new_twitter_message->update( 'user_id', module_security::get_loggedin_id() );
										// do we send this one now? or schedule it later.
										$new_twitter_message->update( 'status', _SOCIAL_MESSAGE_STATUS_PENDINGSEND );
										if ( isset( $_FILES['picture']['tmp_name'] ) && is_uploaded_file( $_FILES['picture']['tmp_name'] ) ) {
											$new_twitter_message->add_attachment( $_FILES['picture']['tmp_name'] );
										}
										$worked            = $new_twitter_message->send_queued( isset( $_POST['debug'] ) && $_POST['debug'] );
										$return['message'] = ob_get_clean();
										if ( $debug ) {
											// just return message
										} else if ( $worked ) {
											// success, redicet!
											set_message( _l( 'Message sent and conversation archived.' ) );
											$return['redirect'] = module_social_twitter::link_open_message_view( $social_twitter_id );
										} else {
											// failed, no debug, force debug and show error.
										}
									}
									echo json_encode( $return );
								}
								break;
							case "set-answered":
								if ( module_social::can_i( 'edit', 'Twitter Comments', 'Social', 'social' ) ) {
									$twitter_message->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
									?>
									$('.twitter_message_row[data-id=<?php echo $message_id; ?>]').hide();
									<?php
									// if this is a direct message, we also archive all other messages in it.
									if ( $twitter_message->get( 'type' ) == _TWITTER_MESSAGE_TYPE_DIRECT ) {
										$from = preg_replace( '#[^0-9]#', '', $twitter_message->get( 'twitter_from_id' ) );
										$to   = preg_replace( '#[^0-9]#', '', $twitter_message->get( 'twitter_to_id' ) );
										if ( $from && $to ) {
											$sql    = "SELECT * FROM `" . _DB_PREFIX . "social_twitter_message` WHERE `type` = " . _TWITTER_MESSAGE_TYPE_DIRECT . " AND `status` = " . (int) _SOCIAL_MESSAGE_STATUS_UNANSWERED . " AND social_twitter_id = " . (int) $twitter_message->get( 'twitter_account' )->get( 'social_twitter_id' ) . " AND ( (`twitter_from_id` = '$from' AND `twitter_to_id` = '$to') OR (`twitter_from_id` = '$to' AND `twitter_to_id` = '$from') ) ";
											$others = qa( $sql );
											if ( count( $others ) ) {
												foreach ( $others as $other_message ) {
													$ucm_twitter_message = new ucm_twitter_message( false, $other_message['social_twitter_message_id'] );
													if ( $ucm_twitter_message->get( 'social_twitter_message_id' ) == $other_message['social_twitter_message_id'] ) {
														$ucm_twitter_message->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
														?>
														$('.twitter_message_row[data-id=<?php echo $ucm_twitter_message->get( 'social_twitter_message_id' ); ?>]').hide();
														<?php
													}
												}
											}
										}
									}
								}
								break;
							case "set-unanswered":
								if ( module_social::can_i( 'edit', 'Twitter Comments', 'Social', 'social' ) ) {
									$twitter_message->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
									?>
									$('.twitter_message_row[data-id=<?php echo $message_id; ?>]').hide();
									<?php
								}
								break;
						}
						//echo 'The status is '.$twitter_message->get('status');
					}
				}
			}
			exit;
		}
	}


	public static function link_generate( $social_twitter_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'social_twitter_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

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
		}

		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'social_twitter';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'social_twitter_settings';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['social_twitter_id'] = $social_twitter_id;
		$options['module']                         = 'social_twitter';

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $social_twitter_id > 0 ) {
					$data = self::get( $social_twitter_id );
				} else {
					$data = array();

					return _l( 'N/A' );
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = $data['account_name'];
		}
		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the path (module & page) for this link
		$options['module'] = 'social_twitter';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! module_social::can_i( 'view', 'Twitter', 'Social', 'social' ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				return '#';
			} else {
				return isset( $options['text'] ) ? $options['text'] : _l( 'N/A' );
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		$bubble_options   = array();
		if ( $options['page'] == 'social_twitter_list' || $options['page'] == 'social_twitter_edit' ) {
			$bubble_to_module         = array(
				'module'   => 'social',
				'argument' => 'social_twitter_id',
			);
			$bubble_options['config'] = false;
			$bubble_options['page']   = 'social_admin';
		} else {
			$bubble_to_module         = array(
				'module'   => 'social',
				'argument' => 'social_twitter_id',
			);
			$bubble_options['config'] = true;
			$bubble_options['page']   = 'social_settings';
		}
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, $bubble_options, $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );
		}
	}


	public static function link_open( $social_twitter_id, $full = false, $data = array(), $page = 'twitter_settings' ) {
		return self::link_generate( $social_twitter_id, array( 'full' => $full, 'data' => $data, 'page' => $page ) );
	}

	public static function link_open_twitter_account_refresh( $social_twitter_id, $full = false, $data = array() ) {
		return self::link_generate( $social_twitter_id, array( 'full'      => $full,
		                                                       'data'      => $data,
		                                                       'arguments' => array(),
		                                                       'page'      => 'twitter_account_refresh'
		) );
	}

	public static function link_open_message_view( $social_twitter_id, $full = false, $data = array() ) {
		return self::link_generate( $social_twitter_id, array( 'full'      => $full,
		                                                       'data'      => $data,
		                                                       'arguments' => array(),
		                                                       'page'      => 'social_twitter_list'
		) );
	}

	public static function link_open_twitter_message( $social_twitter_id, $social_twitter_message_id, $full = false, $data = array() ) {
		return self::link_generate( $social_twitter_id, array(
			'full'      => $full,
			'data'      => $data,
			'arguments' => array(
				'social_twitter_message_id' => $social_twitter_message_id
			),
			'page'      => 'social_twitter_edit'
		) );
	}

	public static function link_social_ajax_functions( $social_twitter_id = 0 ) {
		/*if($h){
				return md5('s3cret7hash for ajax social twitter links '._UCM_SECRET);
		}
		return full_link(_EXTERNAL_TUNNEL.'?m=social&h=ajax&hash='.self::link_social_ajax_functions(true).'');*/
		return _BASE_HREF . '?m=social_twitter&_process=ajax_social_twitter' . ( $social_twitter_id ? '&social_twitter_id=' . (int) $social_twitter_id : '' );
	}


	public static function get( $social_twitter_id ) {
		return get_single( 'social_twitter', 'social_twitter_id', $social_twitter_id );
	}

	public static function get_accounts() {
		return get_multiple( 'social_twitter', array(), 'social_twitter_id' );
	}


	public static function run_cron() {
		if ( module_social::is_plugin_enabled() && self::is_plugin_enabled() ) {
			$accounts = self::get_accounts();
			foreach ( $accounts as $account ) {
				$twitter_account = new ucm_twitter_account( $account['social_twitter_id'] );
				$twitter_account->import_data( module_config::c( 'debug_cron_jobs', 0 ) );
			}
		}
	}

	public function get_upgrade_sql() {
		$sql = '';
		if ( ! self::db_table_exists( 'social_twitter_message_read' ) ) {
			$sql .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "social_twitter_message_read` (
  `social_twitter_message_id` int(11) NOT NULL,
  `read_time` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`social_twitter_message_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_twitter` (
		`social_twitter_id` int(11) NOT NULL AUTO_INCREMENT,
		`twitter_id` varchar(255) NOT NULL,
		`twitter_name` varchar(50) NOT NULL,
		`twitter_data` text NOT NULL,
		`last_checked` int(11) NOT NULL DEFAULT '0',
		`user_key` varchar(255) NOT NULL,
		`user_secret` varchar(255) NOT NULL,
		`import_dm` tinyint(1) NOT NULL DEFAULT '0',
		`import_mentions` tinyint(1) NOT NULL DEFAULT '0',
		`import_tweets` tinyint(1) NOT NULL DEFAULT '0',
		`user_data` text NOT NULL,
		`searches` text NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`account_name` varchar(80) NOT NULL,
		PRIMARY KEY (`social_twitter_id`),
		KEY `twitter_id` (`twitter_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_twitter_message` (
		`social_twitter_message_id` int(11) NOT NULL AUTO_INCREMENT,
		`social_twitter_id` int(11) NOT NULL,
		`reply_to_id` int(11) NOT NULL DEFAULT '0',
		`twitter_message_id` varchar(255) NOT NULL,
		`twitter_from_id` varchar(80) NOT NULL,
		`twitter_to_id` varchar(80) NOT NULL,
		`twitter_from_name` varchar(80) NOT NULL,
		`twitter_to_name` varchar(80) NOT NULL,
		`type` tinyint(1) NOT NULL DEFAULT '0',
		`status` tinyint(1) NOT NULL DEFAULT '0',
		`summary` text NOT NULL,
		`message_time` int(11) NOT NULL DEFAULT '0',
		`data` text NOT NULL,
		`user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`social_twitter_message_id`),
		KEY `social_twitter_id` (`social_twitter_id`),
		KEY `message_time` (`message_time`),
		KEY `twitter_message_id` (`twitter_message_id`),
		KEY `twitter_from_id` (`twitter_from_id`,`twitter_to_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_twitter_message_read` (
		`social_twitter_message_id` int(11) NOT NULL,
		`read_time` int(11) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`social_twitter_message_id`,`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php

		return ob_get_clean();
	}

}
