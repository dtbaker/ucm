<?php

require_once( 'facebook.class.php' );


class module_social_facebook extends module_base {

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
		$this->module_name     = "social_facebook";
		$this->module_position = 25.2;

		$this->version = 2.135;
		// 2.135 - 2016-07-10 - big update to mysqli
		// 2.134 - 2016-01-23 - facebook api update
		// 2.133 - 2016-01-22 - facebook publish pages permission
		// 2.132 - 2014-10-13 - support for local FB app/api
		// 2.131 - 2014-08-04 - responsive improvements
		// 2.13 - 2014-05-23 - social fixes
		// 2.12 - 2014-04-05 - ability to disable social plugin
		// 2.11 - 2014-04-05 - better message archiving
		// 2.1 - 2014-04-05 - initial release


		if ( module_social::is_plugin_enabled() && self::is_plugin_enabled() ) {
			module_config::register_js( 'social_facebook', 'social_facebook.js' );
			module_config::register_css( 'social_facebook', 'social_facebook.css' );
		}

		//hook_add('get_social_methods','module_social_facebook::hook_get_social_methods');
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
					"name"                => "Facebook",
					"p"                   => "facebook_settings",
					"args"                => array( 'social_facebook_id' => false ),
					'holder_module'       => 'social', // which parent module this link will sit under.
					'holder_module_page'  => 'social_settings',  // which page this link will be automatically added to.
					'menu_include_parent' => 1,
				);
			}

			global $load_modules;
			if ( module_social::can_i( 'view', 'Facebook', 'Social', 'social' ) && $load_modules && $load_modules[0] == 'social' ) {
				$accounts = self::get_accounts();
				$facebook = new ucm_facebook();
				foreach ( $accounts as $account ) {
					$facebook_account = new ucm_facebook_account( $account['social_facebook_id'] );
					$unread           = $facebook->get_unread_count( array( 'social_facebook_id' => $account['social_facebook_id'] ) );
					$this->links[]    = array(
						"name"                => $facebook_account->get( 'facebook_name' ) . ( $unread > 0 ? " <span class='menu_label'>" . $unread . "</span>" : '' ),
						"p"                   => "social_facebook_list",
						"args"                => array(
							'social_facebook_id' => $account['social_facebook_id'],
							'social_twitter_id'  => false
						),
						'holder_module'       => 'social', // which parent module this link will sit under.
						'holder_module_page'  => 'social_admin',  // which page this link will be automatically added to.
						'menu_include_parent' => 0,
						'current'             => isset( $_REQUEST['social_facebook_id'] ) && $_REQUEST['social_facebook_id'] == $account['social_facebook_id'],
						'allow_nesting'       => 0,
					);
				}
			}
		}
	}

	public function process() {

		if ( "save_facebook" == $_REQUEST['_process'] ) {

			$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
			$facebook           = new ucm_facebook_account( $social_facebook_id );


			if ( isset( $_POST['butt_del'] ) && module_social::can_i( 'delete', 'Facebook', 'Social', 'social' ) ) {
				if ( module_form::confirm_delete(
					'social_facebook_id',
					"Really delete this Facebook account from the system? All messages will be lost.",
					self::link_open( $_REQUEST['social_facebook_id'] )
				) ) {
					$facebook->delete();
					set_message( "Facebook account deleted successfully" );
					redirect_browser( self::link_open( false ) );
				}
			}
			$facebook->save_data( $_POST );
			$social_facebook_id = $facebook->get( 'social_facebook_id' );

			if ( isset( $_POST['butt_save_connect'] ) ) {
				$redirect = $this->link_open( $social_facebook_id, false, false, 'facebook_account_connect' );
			} else {
				set_message( 'Facebook account saved successfully' );
				$redirect = $this->link_open( $social_facebook_id );
			}
			redirect_browser( $redirect );

			exit;
		} else if ( "send_facebook_message" == $_REQUEST['_process'] ) {

			if ( module_form::check_secure_key() ) {
				$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
				$facebook           = new ucm_facebook_account( $social_facebook_id );
				if ( $social_facebook_id && $facebook->get( 'social_facebook_id' ) == $social_facebook_id ) {

					// queue the message into the facebook_message table
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

					/* @var $available_pages ucm_facebook_page[] */
					$available_pages = $facebook->get( 'pages' );

					$send_pages = isset( $_POST['compose_page_id'] ) && is_array( $_POST['compose_page_id'] ) ? $_POST['compose_page_id'] : array();
					$page_count = 0;
					if ( $send_pages ) {
						foreach ( $send_pages as $facebook_page_id => $tf ) {
							if ( ! $tf ) {
								continue;
							}
							// see if this is an available page.
							if ( isset( $available_pages[ $facebook_page_id ] ) ) {
								// push to db! then send.
								$facebook_message = new ucm_facebook_message( $facebook, $available_pages[ $facebook_page_id ], false );
								$facebook_message->create_new();
								$facebook_message->update( 'social_facebook_page_id', $available_pages[ $facebook_page_id ]->get( 'social_facebook_page_id' ) );
								$facebook_message->update( 'social_facebook_id', $facebook->get( 'social_facebook_id' ) );
								$facebook_message->update( 'summary', isset( $_POST['message'] ) ? $_POST['message'] : '' );
								$facebook_message->update( 'type', 'pending' );
								$facebook_message->update( 'link', isset( $_POST['link'] ) ? $_POST['link'] : '' );
								$facebook_message->update( 'data', json_encode( $_POST ) );
								$facebook_message->update( 'user_id', module_security::get_loggedin_id() );
								// do we send this one now? or schedule it later.
								$facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_PENDINGSEND );
								if ( $send_time ) {
									// schedule for sending at a different time (now or in the past)
									$facebook_message->update( 'last_active', $send_time );
								} else {
									// send it now.
									$facebook_message->update( 'last_active', 0 );
								}
								if ( isset( $_FILES['picture']['tmp_name'] ) && is_uploaded_file( $_FILES['picture']['tmp_name'] ) ) {
									$facebook_message->add_attachment( $_FILES['picture']['tmp_name'] );
								}
								$facebook_message->send_queued( isset( $_POST['debug'] ) );
								$page_count ++;

							} else {
								// log error?
							}
						}
					}

					set_message( _l( 'Message delivered successfully to %s Facebook pages', $page_count ) );
					$redirect = $this->link_open_message_view( $social_facebook_id );
					redirect_browser( $redirect );

				}
			}


		} else if ( "ajax_facebook_url_info" == $_REQUEST['_process'] ) {

			header( 'Content-type: text/javascript' );
			$url = isset( $_REQUEST['url'] ) ? $_REQUEST['url'] : false;
			if ( strlen( $url ) > 4 && preg_match( '#https?://#', $url ) ) {
				// pass this into graph api debugger to get some information back about the URL
				$facebook = new ucm_facebook();
				$data     = $facebook->get_url_info( $url );
				// return the data formatted in json ready to be added into the relevant input boxes.
				$data['link_picture']     = isset( $data['image'][0]['url'] ) ? $data['image'][0]['url'] : '';
				$data['link_name']        = isset( $data['title'] ) ? $data['title'] : '';
				$data['link_caption']     = isset( $data['caption'] ) ? $data['caption'] : '';
				$data['link_description'] = isset( $data['description'] ) ? $data['description'] : '';
				echo json_encode( $data );
			}
			exit;

		} else if ( "ajax_social_facebook" == $_REQUEST['_process'] ) {
			// ajax functions from wdsocial. copied from the datafeed.php sample files.
			header( 'Content-type: text/javascript' );
			if ( module_form::check_secure_key() ) {
				// todo: check user has access to this message.
				$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
				//$facebook = new ucm_facebook_account($social_facebook_id);
				//if($social_facebook_id && $facebook->get('social_facebook_id') == $social_facebook_id){
				$action           = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
				$message_id       = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
				$facebook_message = new ucm_facebook_message();
				$facebook_message->load( $message_id );
				//if($facebook_message->get('social_facebook_id') == $social_facebook_id){
				switch ( $action ) {
					case "send-message-reply":
						if ( module_social::can_i( 'create', 'Facebook Comments', 'Social', 'social' ) ) {
							$return      = array();
							$message     = isset( $_POST['message'] ) && $_POST['message'] ? $_POST['message'] : '';
							$facebook_id = isset( $_POST['facebook_id'] ) && $_POST['facebook_id'] ? $_POST['facebook_id'] : false;
							$debug       = isset( $_POST['debug'] ) && $_POST['debug'] ? $_POST['debug'] : false;
							if ( $message ) {
								if ( $debug ) {
									ob_start();
								}
								$facebook_message->send_reply( $facebook_id, $message, $debug );
								if ( $debug ) {
									$return['message'] = ob_get_clean();
								} else {
									// todo - option to ask the user if they want to archive a message during the send.
									set_message( _l( 'Message sent and conversation archived.' ) );
									if ( $social_facebook_id ) {
										$return['redirect'] = module_social_facebook::link_open_message_view( $social_facebook_id );
									} else {
										// return to the 'combined' view:

									}
								}
							}
							echo json_encode( $return );
						}
						break;
					case "set-answered":
						if ( module_social::can_i( 'edit', 'Facebook Comments', 'Social', 'social' ) ) {
							$facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
							?>
							$('.facebook_message_row[data-id=<?php echo $message_id; ?>]').hide();
							<?php
						}
						break;
					case "set-unanswered":
						if ( module_social::can_i( 'edit', 'Facebook Comments', 'Social', 'social' ) ) {
							$facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
							?>
							$('.facebook_message_row[data-id=<?php echo $message_id; ?>]').hide();
							<?php
						}
						break;
				}
				//echo 'The status is '.$facebook_message->get('status');
				//}
			}
			// }
			exit;
		}
	}


	public static function link_generate( $social_facebook_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'social_facebook_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

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
			$options['type'] = 'social_facebook';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'social_facebook_settings';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['social_facebook_id'] = $social_facebook_id;
		$options['module']                          = 'social_facebook';

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $social_facebook_id > 0 ) {
					$data = self::get( $social_facebook_id );
				} else {
					$data = array();

					return _l( 'N/A' );
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = $data['facebook_name'];
		}
		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the path (module & page) for this link
		$options['module'] = 'social_facebook';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! module_social::can_i( 'view', 'Facebook', 'Social', 'social' ) ) {
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
		if ( $options['page'] == 'social_facebook_list' || $options['page'] == 'social_facebook_edit' ) {
			$bubble_to_module         = array(
				'module'   => 'social',
				'argument' => 'social_facebook_id',
			);
			$bubble_options['config'] = false;
			$bubble_options['page']   = 'social_admin';
		} else {
			$bubble_to_module         = array(
				'module'   => 'social',
				'argument' => 'social_facebook_id',
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


	public static function link_open( $social_facebook_id, $full = false, $data = array(), $page = 'facebook_settings' ) {
		return self::link_generate( $social_facebook_id, array( 'full' => $full, 'data' => $data, 'page' => $page ) );
	}

	public static function link_open_facebook_page_refresh( $social_facebook_id, $facebook_page_id, $full = false, $data = array() ) {
		return self::link_generate( $social_facebook_id, array( 'full'      => $full,
		                                                        'data'      => $data,
		                                                        'arguments' => array( 'facebook_page_id' => $facebook_page_id ),
		                                                        'page'      => 'facebook_page_refresh'
		) );
	}

	public static function link_open_message_view( $social_facebook_id, $full = false, $data = array() ) {
		return self::link_generate( $social_facebook_id, array( 'full'      => $full,
		                                                        'data'      => $data,
		                                                        'arguments' => array(),
		                                                        'page'      => 'social_facebook_list'
		) );
	}

	public static function link_open_facebook_message( $social_facebook_id, $social_facebook_message_id, $full = false, $data = array() ) {
		return self::link_generate( $social_facebook_id, array(
			'full'      => $full,
			'data'      => $data,
			'arguments' => array(
				'social_facebook_message_id' => $social_facebook_message_id
			),
			'page'      => 'social_facebook_edit'
		) );
	}

	public static function link_social_ajax_functions( $social_facebook_id = 0 ) {
		/*if($h){
				return md5('s3cret7hash for ajax social facebook links '._UCM_SECRET);
		}
		return full_link(_EXTERNAL_TUNNEL.'?m=social&h=ajax&hash='.self::link_social_ajax_functions(true).'');*/
		return _BASE_HREF . '?m=social_facebook&_process=ajax_social_facebook' . ( $social_facebook_id ? '&social_facebook_id=' . (int) $social_facebook_id : '' );
	}

	/*public static function hook_get_social_methods(){
			$methods = array();
			if(module_social::can_i('view','Facebook','Social','social')){
					$methods = array();
				$accounts = self::get_accounts();
				$id=1;
				foreach($accounts as $account) {
					$facebook_account = new ucm_facebook_account($account['social_facebook_id']);
					$methods []       = array(
						'unique_id' => 'facebook'.$id,
						'name'      => $facebook_account->get('facebook_name'),
						'url_load'  => _BASE_HREF . '?m=social_facebook&_process=ajax_social_facebook&social_facebook_id=' . $account['social_facebook_id'],
					);
					$id++;
				}
			}

			return $methods;
	}*/


	public static function get( $social_facebook_id ) {
		return get_single( 'social_facebook', 'social_facebook_id', $social_facebook_id );
	}

	public static function get_accounts() {
		return get_multiple( 'social_facebook', array(), 'social_facebook_id' );
	}


	public static function run_cron() {
		if ( module_social::is_plugin_enabled() && self::is_plugin_enabled() ) {
			$accounts = self::get_accounts();
			foreach ( $accounts as $account ) {
				$facebook_account = new ucm_facebook_account( $account['social_facebook_id'] );
				/* @var $pages ucm_facebook_page[] */
				$pages = $facebook_account->get( 'pages' );
				foreach ( $pages as $page ) {
					ob_start();
					$page->graph_load_latest_page_data();
					$output = ob_get_clean();
					if ( module_config::c( 'debug_cron_jobs', 0 ) ) {
						echo $output;
					}
				}
			}
		}
	}


	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'social_facebook' );
		if ( ! isset( $fields['facebook_app_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'social_facebook` ADD  `facebook_app_id` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `facebook_token`;';
		}
		if ( ! isset( $fields['facebook_app_secret'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'social_facebook` ADD  `facebook_app_secret` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `facebook_app_id`;';
		}
		if ( ! self::db_table_exists( 'social_facebook_message_read' ) ) {
			$sql .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "social_facebook_message_read` (
  `social_facebook_message_id` int(11) NOT NULL,
  `read_time` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`social_facebook_message_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_facebook` (
		`social_facebook_id` int(11) NOT NULL AUTO_INCREMENT,
		`facebook_name` varchar(50) NOT NULL,
		`last_checked` int(11) NOT NULL DEFAULT '0',
		`facebook_data` text NOT NULL,
		`facebook_token` varchar(255) NOT NULL,
		`facebook_app_id` varchar(255) NOT NULL,
		`facebook_app_secret` varchar(255) NOT NULL,
		`machine_id` varchar(255) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`social_facebook_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_facebook_message` (
		`social_facebook_message_id` int(11) NOT NULL AUTO_INCREMENT,
		`marketing_message_id` int(11) NOT NULL DEFAULT '0',
		`social_facebook_page_id` int(11) NOT NULL,
		`social_facebook_id` int(11) NOT NULL,
		`facebook_id` varchar(255) NOT NULL,
		`summary` text NOT NULL,
		`last_active` int(11) NOT NULL DEFAULT '0',
		`comments` text NOT NULL,
		`type` varchar(20) NOT NULL,
		`link` varchar(255) NOT NULL,
		`data` text NOT NULL,
		`status` tinyint(1) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`social_facebook_message_id`),
		KEY `social_facebook_id` (`social_facebook_id`),
		KEY `last_active` (`last_active`),
		KEY `social_facebook_page_id` (`social_facebook_page_id`),
		KEY `facebook_id` (`facebook_id`),
		KEY `status` (`status`),
		KEY `marketing_message_id` (`marketing_message_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_facebook_message_read` (
		`social_facebook_message_id` int(11) NOT NULL,
		`read_time` int(11) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`social_facebook_message_id`,`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_facebook_message_comment` (
		`social_facebook_message_comment_id` int(11) NOT NULL AUTO_INCREMENT,
		`social_facebook_message_id` int(11) NOT NULL,
		`facebook_id` varchar(255) NOT NULL,
		`time` int(11) NOT NULL,
		`from` text NOT NULL,
		`to` text NOT NULL,
		`data` text NOT NULL,
		`user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`social_facebook_message_comment_id`),
		KEY `social_facebook_message_id` (`social_facebook_message_id`),
		KEY `facebook_id` (`facebook_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>social_facebook_page` (
		`social_facebook_page_id` int(11) NOT NULL AUTO_INCREMENT,
		`social_facebook_id` int(11) NOT NULL,
		`page_name` varchar(50) NOT NULL,
		`last_message` int(11) NOT NULL DEFAULT '0',
		`last_checked` int(11) NOT NULL,
		`page_id` varchar(255) NOT NULL,
		`facebook_token` varchar(255) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`social_facebook_page_id`),
		KEY `social_facebook_id` (`social_facebook_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		<?php

		return ob_get_clean();
	}

}
