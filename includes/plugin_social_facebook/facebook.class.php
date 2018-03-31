<?php

class ucm_facebook {

	public function __construct() {
		$this->reset();
	}

	private $accounts = array();

	private function reset() {
		$this->accounts = array();
	}


	public function get_accounts() {
		$this->accounts = get_multiple( 'social_facebook', array(), 'social_facebook_id' );

		return $this->accounts;
	}

	public function get_url_info( $url ) {
		$data = $this->graph_post( '', array(
			'id'     => $url,
			'scrape' => true,
		) );

		return $data;
	}

	public function graph( $endpoint, $args = array(), $fields = '' ) {
		$url = 'https://graph.facebook.com/' . $endpoint . '?';
		foreach ( $args as $key => $val ) {
			if ( $val !== false ) {
				$url .= $key . '=' . urlencode( $val ) . '&';
			}
		}
		if ( $fields ) {
			$url .= '&fields=' . $fields;
		}
		$data = $this->get_url( $url );

		return $data;
	}

	public function graph_post( $endpoint, $args = array() ) {
		$url  = 'https://graph.facebook.com/' . $endpoint . '';
		$data = $this->get_url( $url, $args );

		return $data;
	}

	private function get_url( $url, $post_data = false ) {
		// get feed from fb:

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		if ( $post_data ) {
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data );
		}
		$data = curl_exec( $ch );
		$feed = @json_decode( $data, true );

		//print_r($feed);
		return $feed;

	}

	public function get_paged_data( $data, $pagination ) {

	}

	public static function format_person( $data ) {
		$return = '';
		if ( $data && isset( $data['id'] ) ) {
			$return .= '<a href="//facebook.com/' . $data['id'] . '" target="_blank">';
		}
		if ( $data && isset( $data['name'] ) ) {
			$return .= htmlspecialchars( $data['name'] );
		}
		if ( $data && isset( $data['id'] ) ) {
			$return .= '</a>';
		}

		return $return;
	}

	private $all_messages = false;

	public function load_all_messages( $search = array(), $order = array() ) {
		$sql = "SELECT m.*, m.last_active AS `message_time`, mr.read_time FROM `" . _DB_PREFIX . "social_facebook_message` m ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "social_facebook_message_read` mr ON m.social_facebook_message_id = mr.social_facebook_message_id";
		$sql .= " WHERE 1 ";
		if ( isset( $search['status'] ) && $search['status'] !== false ) {
			$sql .= " AND `status` = " . (int) $search['status'];
		}
		if ( isset( $search['social_facebook_page_id'] ) && $search['social_facebook_page_id'] !== false ) {
			$sql .= " AND `social_facebook_page_id` = " . (int) $search['social_facebook_page_id'];
		}
		if ( isset( $search['social_message_id'] ) && $search['social_message_id'] !== false ) {
			$sql .= " AND `social_message_id` = " . (int) $search['social_message_id'];
		}
		if ( isset( $search['social_facebook_id'] ) && $search['social_facebook_id'] !== false ) {
			$sql .= " AND `social_facebook_id` = " . (int) $search['social_facebook_id'];
		}
		if ( isset( $search['generic'] ) && ! empty( $search['generic'] ) ) {
			$sql .= " AND `summary` LIKE '%" . db_escape( $search['generic'] ) . "%'";
		}
		$sql                .= " ORDER BY `last_active` DESC ";
		$this->all_messages = query( $sql );

		return $this->all_messages;
	}

	public function get_next_message() {
		if ( mysqli_num_rows( $this->all_messages ) ) {
			return mysqli_fetch_assoc( $this->all_messages );
		}

		return false;
	}


	// used in our Wp "outbox" view showing combined messages.
	public function get_message_details( $social_message_id ) {
		if ( ! $social_message_id ) {
			return array();
		}
		$messages = $this->load_all_messages( array( 'social_message_id' => $social_message_id ) );
		// we want data for our colum outputs in the WP table:
		/*'social_column_time'    => __( 'Date/Time', 'simple_social_inbox' ),
	    'social_column_social' => __( 'Social Accounts', 'simple_social_inbox' ),
		'social_column_summary'    => __( 'Summary', 'simple_social_inbox' ),
		'social_column_links'    => __( 'Link Clicks', 'simple_social_inbox' ),
		'social_column_stats'    => __( 'Stats', 'simple_social_inbox' ),
		'social_column_action'    => __( 'Action', 'simple_social_inbox' ),*/
		$data        = array(
			'social_column_social'  => '',
			'social_column_summary' => '',
			'social_column_links'   => '',
		);
		$link_clicks = 0;
		foreach ( $messages as $message ) {
			$facebook_message              = new ucm_facebook_message( false, false, $message['social_facebook_message_id'] );
			$data['message']               = $facebook_message;
			$data['social_column_social']  .= '<div><img src="' . plugins_url( 'images/facebook.png', dirname( __FILE__ ) ) . '" class="facebook_icon small"><a href="' . $facebook_message->get_link() . '" target="_blank">' . htmlspecialchars( $facebook_message->get( 'facebook_page' )->get( 'page_name' ) ) . '</a></div>';
			$data['social_column_summary'] .= '<div><img src="' . plugins_url( 'images/facebook.png', dirname( __FILE__ ) ) . '" class="facebook_icon small"><a href="' . $facebook_message->get_link() . '" target="_blank">' . htmlspecialchars( $facebook_message->get_summary() ) . '</a></div>';
			// how many link clicks does this one have?
			$sql                         = "SELECT count(*) AS `link_clicks` FROM ";
			$sql                         .= " `" . _DB_PREFIX . "social_facebook_message` m ";
			$sql                         .= " LEFT JOIN `" . _DB_PREFIX . "social_facebook_message_link` ml USING (social_facebook_message_id) ";
			$sql                         .= " LEFT JOIN `" . _DB_PREFIX . "social_facebook_message_link_click` lc USING (social_facebook_message_link_id) ";
			$sql                         .= " WHERE 1 ";
			$sql                         .= " AND m.social_facebook_message_id = " . (int) $message['social_facebook_message_id'];
			$sql                         .= " AND lc.social_facebook_message_link_id IS NOT NULL ";
			$sql                         .= " AND lc.user_agent NOT LIKE '%Google%' ";
			$sql                         .= " AND lc.user_agent NOT LIKE '%Yahoo%' ";
			$sql                         .= " AND lc.user_agent NOT LIKE '%facebookexternalhit%' ";
			$sql                         .= " AND lc.user_agent NOT LIKE '%Meta%' ";
			$res                         = qa1( $sql );
			$link_clicks                 = $res && $res['link_clicks'] ? $res['link_clicks'] : 0;
			$data['social_column_links'] .= '<div><img src="' . plugins_url( 'images/facebook.png', dirname( __FILE__ ) ) . '" class="facebook_icon small">' . $link_clicks . '</div>';
		}
		if ( count( $messages ) && $link_clicks > 0 ) {
			//$data['social_column_links'] = '<div><img src="'.plugins_url('images/facebook.png', dirname(__FILE__)).'" class="facebook_icon small">'. $link_clicks  .'</div>';
		}

		return $data;

	}


	public function get_unread_count( $search = array() ) {
		if ( ! module_security::is_logged_in() ) {
			return 0;
		}
		$sql = "SELECT count(*) AS `unread` FROM `" . _DB_PREFIX . "social_facebook_message` m ";
		$sql .= " WHERE 1 ";
		$sql .= " AND m.social_facebook_message_id NOT IN (SELECT mr.social_facebook_message_id FROM `" . _DB_PREFIX . "social_facebook_message_read` mr WHERE mr.user_id = '" . (int) module_security::get_loggedin_id() . "' AND mr.social_facebook_message_id = m.social_facebook_message_id)";
		$sql .= " AND m.`status` = " . _SOCIAL_MESSAGE_STATUS_UNANSWERED;
		if ( isset( $search['social_facebook_page_id'] ) && $search['social_facebook_page_id'] !== false ) {
			$sql .= " AND m.`social_facebook_page_id` = " . (int) $search['social_facebook_page_id'];
		}
		if ( isset( $search['social_facebook_id'] ) && $search['social_facebook_id'] !== false ) {
			$sql .= " AND m.`social_facebook_id` = " . (int) $search['social_facebook_id'];
		}
		$res = qa1( $sql );

		return $res ? $res['unread'] : 0;
	}


	public function output_row( $message, $settings ) {
		$facebook_message = new ucm_facebook_message( false, false, $message['social_facebook_message_id'] );
		$comments         = $facebook_message->get_comments();
		?>
		<tr
			class="<?php echo isset( $settings['row_class'] ) ? $settings['row_class'] : ''; ?> facebook_message_row <?php echo ! isset( $message['read_time'] ) || ! $message['read_time'] ? ' message_row_unread' : ''; ?>"
			data-id="<?php echo (int) $message['social_facebook_message_id']; ?>">
			<td>
				<img src="<?php echo _BASE_HREF; ?>includes/plugin_social_facebook/images/facebook.png" class="facebook_icon">
				<a href="<?php echo $facebook_message->get_link(); ?>"
				   target="_blank"><?php echo htmlspecialchars( $facebook_message->get( 'facebook_page' )->get( 'page_name' ) ); ?></a>
				<br/>
				<?php echo htmlspecialchars( $facebook_message->get_type_pretty() ); ?>
			</td>
			<td class="social_column_time"><?php echo print_date( $message['message_time'], true ); ?></td>
			<td class="social_column_from">
				<?php
				// work out who this is from.
				$from = $facebook_message->get_from();
				?>
				<div class="social_from_holder social_facebook">
					<div class="social_from_full">
						<?php
						foreach ( $from as $id => $name ) {
							?>
							<div>
								<a href="//facebook.com/<?php echo $id; ?>" target="_blank"><img
										src="//graph.facebook.com/<?php echo $id; ?>/picture"
										class="social_from_picture"></a> <?php echo htmlspecialchars( $name ); ?>
							</div>
							<?php
						} ?>
					</div>
					<?php
					reset( $from );
					echo '<a href="//facebook.com/' . key( $from ) . '" target="_blank">' . '<img src="//graph.facebook.com/' . key( $from ) . '/picture" class="social_from_picture"></a> ';
					echo '<span class="social_from_count">';
					if ( count( $from ) > 1 ) {
						echo '+' . ( count( $from ) - 1 );
					}
					echo '</span>';
					?>
				</div>
			</td>
			<td class="social_column_summary">
			    <span style="float:right;">
				    <?php echo count( $comments ) > 0 ? '(' . count( $comments ) . ')' : ''; ?>
			    </span>
				<div
					class="facebook_message_summary<?php echo ! isset( $message['read_time'] ) || ! $message['read_time'] ? ' unread' : ''; ?>"> <?php
					$summary = $facebook_message->get_summary();
					echo $summary;
					?>
				</div>
			</td>
			<!--<td></td>-->
			<td nowrap>
				<?php if ( module_social::can_i( 'view', 'Facebook Comments', 'Social', 'social' ) ) { ?>

					<a
						href="<?php echo module_social_facebook::link_open_facebook_message( $message['social_facebook_id'], $message['social_facebook_message_id'] ); ?>"
						class="socialfacebook_message_open social_modal btn btn-default btn-xs"
						data-modal-title="<?php echo htmlspecialchars( $summary ); ?>"><?php _e( 'Open' ); ?></a>

				<?php } ?>
				<?php if ( module_social::can_i( 'edit', 'Facebook Comments', 'Social', 'social' ) ) { ?>
					<?php if ( $facebook_message->get( 'status' ) == _SOCIAL_MESSAGE_STATUS_ANSWERED ) { ?>
						<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
						   data-action="set-unanswered"
						   data-id="<?php echo (int) $facebook_message->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Un-Archive' ); ?></a>
					<?php } else { ?>
						<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
						   data-action="set-answered"
						   data-id="<?php echo (int) $facebook_message->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Archive' ); ?></a>
					<?php } ?>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	public function init_js() {
		?>
		ucm.social.facebook.api_url = '<?php echo module_social_facebook::link_social_ajax_functions(); ?>';
		ucm.social.facebook.init();
		<?php
	}

	public function handle_process( $process, $options = array() ) {
		switch ( $process ) {
			case 'send_social_message':
				check_admin_referer( 'social_send-message' );
				$message_count = 0;
				if ( isset( $options['social_message_id'] ) && (int) $options['social_message_id'] > 0 && isset( $_POST['facebook_message'] ) && ! empty( $_POST['facebook_message'] ) ) {
					// we have a social message id, ready to send!
					// which facebook accounts are we sending too?
					$facebook_accounts = isset( $_POST['compose_facebook_id'] ) && is_array( $_POST['compose_facebook_id'] ) ? $_POST['compose_facebook_id'] : array();
					foreach ( $facebook_accounts as $facebook_account_id => $send_pages ) {
						$facebook_account = new ucm_facebook_account( $facebook_account_id );
						if ( $facebook_account->get( 'social_facebook_id' ) == $facebook_account_id ) {
							/* @var $available_pages ucm_facebook_page[] */
							$available_pages = $facebook_account->get( 'pages' );
							if ( $send_pages ) {
								foreach ( $send_pages as $facebook_page_id => $tf ) {
									if ( ! $tf ) {
										continue;
									}// shouldnt happen
									// see if this is an available page.
									if ( isset( $available_pages[ $facebook_page_id ] ) ) {
										// push to db! then send.
										$facebook_message = new ucm_facebook_message( $facebook_account, $available_pages[ $facebook_page_id ], false );
										$facebook_message->create_new();
										$facebook_message->update( 'social_facebook_page_id', $available_pages[ $facebook_page_id ]->get( 'social_facebook_page_id' ) );
										$facebook_message->update( 'social_message_id', $options['social_message_id'] );
										$facebook_message->update( 'social_facebook_id', $facebook_account->get( 'social_facebook_id' ) );
										$facebook_message->update( 'summary', isset( $_POST['facebook_message'] ) ? $_POST['facebook_message'] : '' );
										if ( isset( $_POST['track_links'] ) && $_POST['track_links'] ) {
											$facebook_message->parse_links();
										}
										$facebook_message->update( 'type', 'pending' );
										$facebook_message->update( 'link', isset( $_POST['link'] ) ? $_POST['link'] : '' );
										$facebook_message->update( 'data', json_encode( $_POST ) );
										$facebook_message->update( 'user_id', get_current_user_id() );
										// do we send this one now? or schedule it later.
										$facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_PENDINGSEND );
										if ( isset( $options['send_time'] ) && ! empty( $options['send_time'] ) ) {
											// schedule for sending at a different time (now or in the past)
											$facebook_message->update( 'last_active', $options['send_time'] );
										} else {
											// send it now.
											$facebook_message->update( 'last_active', 0 );
										}
										if ( isset( $_FILES['picture']['tmp_name'] ) && is_uploaded_file( $_FILES['picture']['tmp_name'] ) ) {
											$facebook_message->add_attachment( $_FILES['picture']['tmp_name'] );
										}
										$now = time();
										if ( ! $facebook_message->get( 'last_active' ) || $facebook_message->get( 'last_active' ) <= $now ) {
											// send now! otherwise we wait for cron job..
											if ( $facebook_message->send_queued( isset( $_POST['debug'] ) && $_POST['debug'] ) ) {
												$message_count ++;
											}
										} else {
											$message_count ++;
											if ( isset( $_POST['debug'] ) && $_POST['debug'] ) {
												echo "Message will be sent in cron job after " . print_date( $facebook_message->get( 'last_active' ), true );
											}
										}

									} else {
										// log error?
									}
								}
							}
						}
					}
				}

				return $message_count;
				break;
			case 'save_facebook':
				$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
				check_admin_referer( 'save-facebook' . $social_facebook_id );
				$facebook = new ucm_facebook_account( $social_facebook_id );
				if ( isset( $_POST['butt_delete'] ) ) {
					$facebook->delete();
					$redirect = 'admin.php?page=simple_social_inbox_facebook_settings';
				} else {
					$facebook->save_data( $_POST );
					$social_facebook_id = $facebook->get( 'social_facebook_id' );
					if ( isset( $_POST['butt_save_reconnect'] ) ) {
						$redirect = $facebook->link_connect();
					} else {
						$redirect = $facebook->link_edit();
					}
				}
				header( "Location: $redirect" );
				exit;

				break;
		}
	}

	public function handle_ajax( $action, $simple_social_inbox_wp ) {
		switch ( $action ) {
			case 'fb_url_info':
				if ( ! headers_sent() ) {
					header( 'Content-type: text/javascript' );
				}
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
				break;
			case 'send-message-reply':
				if ( ! headers_sent() ) {
					header( 'Content-type: text/javascript' );
				}
				if ( isset( $_REQUEST['facebook_id'] ) && ! empty( $_REQUEST['facebook_id'] ) && isset( $_REQUEST['id'] ) && (int) $_REQUEST['id'] > 0 ) {
					$ucm_facebook_message = new ucm_facebook_message( false, false, $_REQUEST['id'] );
					if ( $ucm_facebook_message->get( 'social_facebook_message_id' ) == $_REQUEST['id'] ) {
						$return      = array();
						$message     = isset( $_POST['message'] ) && $_POST['message'] ? $_POST['message'] : '';
						$facebook_id = isset( $_REQUEST['facebook_id'] ) && $_REQUEST['facebook_id'] ? $_REQUEST['facebook_id'] : false;
						$debug       = isset( $_POST['debug'] ) && $_POST['debug'] ? $_POST['debug'] : false;
						if ( $message ) {
							if ( $debug ) {
								ob_start();
							}
							$ucm_facebook_message->send_reply( $facebook_id, $message, $debug );
							if ( $debug ) {
								$return['message'] = ob_get_clean();
							} else {
								//set_message( _l( 'Message sent and conversation archived.' ) );
								$return['redirect'] = 'admin.php?page=simple_social_inbox_main';

							}
						}
						echo json_encode( $return );
					}

				}
				break;
			case 'modal':
				if ( isset( $_REQUEST['socialfacebookmessageid'] ) && (int) $_REQUEST['socialfacebookmessageid'] > 0 ) {
					$ucm_facebook_message = new ucm_facebook_message( false, false, $_REQUEST['socialfacebookmessageid'] );
					if ( $ucm_facebook_message->get( 'social_facebook_message_id' ) == $_REQUEST['socialfacebookmessageid'] ) {

						$social_facebook_id         = $ucm_facebook_message->get( 'facebook_account' )->get( 'social_facebook_id' );
						$social_facebook_message_id = $ucm_facebook_message->get( 'social_facebook_message_id' );
						include( trailingslashit( $simple_social_inbox_wp->dir ) . 'pages/facebook_message.php' );
					}

				}
				break;
			case 'set-answered':
				if ( ! headers_sent() ) {
					header( 'Content-type: text/javascript' );
				}
				if ( isset( $_REQUEST['social_facebook_message_id'] ) && (int) $_REQUEST['social_facebook_message_id'] > 0 ) {
					$ucm_facebook_message = new ucm_facebook_message( false, false, $_REQUEST['social_facebook_message_id'] );
					if ( $ucm_facebook_message->get( 'social_facebook_message_id' ) == $_REQUEST['social_facebook_message_id'] ) {
						$ucm_facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
						?>
						jQuery('.socialfacebook_message_action[data-id=<?php echo (int) $ucm_facebook_message->get( 'social_facebook_message_id' ); ?>]').parents('tr').first().hide();
						<?php
					}
				}
				break;
			case 'set-unanswered':
				if ( ! headers_sent() ) {
					header( 'Content-type: text/javascript' );
				}
				if ( isset( $_REQUEST['social_facebook_message_id'] ) && (int) $_REQUEST['social_facebook_message_id'] > 0 ) {
					$ucm_facebook_message = new ucm_facebook_message( false, false, $_REQUEST['social_facebook_message_id'] );
					if ( $ucm_facebook_message->get( 'social_facebook_message_id' ) == $_REQUEST['social_facebook_message_id'] ) {
						$ucm_facebook_message->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
						?>
						jQuery('.socialfacebook_message_action[data-id=<?php echo (int) $ucm_facebook_message->get( 'social_facebook_message_id' ); ?>]').parents('tr').first().hide();
						<?php
					}
				}
				break;
		}

		return false;
	}

}

class ucm_facebook_account {

	public function __construct( $social_facebook_id ) {
		$this->load( $social_facebook_id );
	}

	private $social_facebook_id = false; // the current user id in our system.
	private $details = array();

	/* @var $pages ucm_facebook_page[] */
	private $pages = array();

	private function reset() {
		$this->social_facebook_id = false;
		$this->details            = array();
		$this->pages              = array();
		$fields                   = get_fields( 'social_facebook' );
		foreach ( $fields as $field_id => $field_data ) {
			$this->{$field_id} = '';
		}
	}

	public function create_new() {
		$this->reset();
		$this->social_facebook_id = update_insert( 'social_facebook_id', false, 'social_facebook', array() );
		$this->load( $this->social_facebook_id );
	}

	public function load( $social_facebook_id = false ) {
		if ( ! $social_facebook_id ) {
			$social_facebook_id = $this->social_facebook_id;
		}
		$this->reset();
		$this->social_facebook_id = $social_facebook_id;
		if ( $this->social_facebook_id ) {
			$this->details = get_single( 'social_facebook', 'social_facebook_id', $this->social_facebook_id );
			if ( ! is_array( $this->details ) || $this->details['social_facebook_id'] != $this->social_facebook_id ) {
				$this->reset();

				return false;
			}
		}
		foreach ( $this->details as $key => $val ) {
			$this->{$key} = $val;
		}
		$this->pages = array();
		if ( ! $this->social_facebook_id ) {
			return false;
		}
		foreach ( get_multiple( 'social_facebook_page', array( 'social_facebook_id' => $this->social_facebook_id ), 'social_facebook_page_id' ) as $page ) {
			$page                                   = new ucm_facebook_page( $this, $page['social_facebook_page_id'] );
			$this->pages[ $page->get( 'page_id' ) ] = $page;
		}

		return $this->social_facebook_id;
	}

	public function get( $field ) {
		return isset( $this->{$field} ) ? $this->{$field} : false;
	}

	public function save_data( $post_data ) {
		if ( ! $this->get( 'social_facebook_id' ) ) {
			$this->create_new();
		}
		if ( is_array( $post_data ) ) {
			$fields = get_fields( 'social_facebook' );
			foreach ( $post_data as $key => $val ) {
				if ( isset( $fields[ $key ] ) ) {
					$this->update( $key, $val );
				}
			}
		}
		// save the active facebook pages.
		if ( isset( $post_data['save_facebook_pages'] ) && $post_data['save_facebook_pages'] == 'yep' ) {
			$currently_active_pages = $this->pages;
			$data                   = @json_decode( $this->get( 'facebook_data' ), true );
			$available_pages        = isset( $data['pages'] ) && is_array( $data['pages'] ) ? $data['pages'] : array();
			if ( isset( $post_data['facebook_page'] ) && is_array( $post_data['facebook_page'] ) ) {
				foreach ( $post_data['facebook_page'] as $facebook_page_id => $yesno ) {
					if ( isset( $currently_active_pages[ $facebook_page_id ] ) ) {
						unset( $currently_active_pages[ $facebook_page_id ] );
					}
					if ( $yesno && isset( $available_pages[ $facebook_page_id ] ) ) {
						// we are adding this page to the list. check if it doesn't already exist.
						if ( ! isset( $this->pages[ $facebook_page_id ] ) ) {
							$page = new ucm_facebook_page( $this );
							$page->create_new();
							$page->update( 'social_facebook_id', $this->social_facebook_id );
							$page->update( 'facebook_token', $available_pages[ $facebook_page_id ]['access_token'] );
							$page->update( 'page_name', $available_pages[ $facebook_page_id ]['name'] );
							$page->update( 'page_id', $facebook_page_id );
						}
					}
				}
			}
			// remove any pages that are no longer active.
			foreach ( $currently_active_pages as $page ) {
				$page->delete();
			}
		}
		$this->load();

		return $this->get( 'social_facebook_id' );
	}

	public function update( $field, $value ) {
		// what fields to we allow? or not allow?
		if ( in_array( $field, array( 'social_facebook_id' ) ) ) {
			return;
		}
		if ( $this->social_facebook_id ) {
			$this->{$field} = $value;
			update_insert( 'social_facebook_id', $this->social_facebook_id, 'social_facebook', array(
				$field => $value,
			) );
		}
	}

	public function delete() {
		if ( $this->social_facebook_id ) {
			// delete all the pages for this twitter account.
			$pages = $this->get( 'pages' );
			foreach ( $pages as $page ) {
				$page->delete();
			}
			delete_from_db( 'social_facebook', 'social_facebook_id', $this->social_facebook_id );
		}
	}

	public function is_active() {
		// is there a 'last_checked' date?
		if ( ! $this->get( 'last_checked' ) ) {
			return false; // never checked this account, not active yet.
		} else {
			// do we have a token?
			if ( $this->get( 'facebook_token' ) ) {
				// assume we have access, we remove the token if we get a facebook failure at any point.
				return true;
			}
		}

		return false;
	}

	public function is_page_active( $facebook_page_id ) {
		if ( isset( $this->pages[ $facebook_page_id ] ) && $this->pages[ $facebook_page_id ]->get( 'page_id' ) == $facebook_page_id && $this->pages[ $facebook_page_id ]->get( 'facebook_token' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/* start FB graph calls */
	public function graph_load_available_pages() {
		// serialise this result into facebook_data.

		$access_token = $this->get( 'facebook_token' );
		$machine_id   = $this->get( 'machine_id' );
		if ( ! $access_token ) {
			return;
		}
		// grab the users details.
		$url = 'https://graph.facebook.com/me?access_token=' . $access_token . '';
		if ( $machine_id ) {
			$url .= '&machine_id=' . $machine_id;
		}
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$data                  = curl_exec( $ch );
		$facebook_account_data = @json_decode( $data, true );
		if ( ! $facebook_account_data || ! $facebook_account_data['id'] ) {
			die( 'Failed to get facebook user account: ' . $data );
		}
		$facebook_user_id   = $facebook_account_data['id'];
		$facebook_user_name = isset( $facebook_account_data['name'] ) ? $facebook_account_data['name'] : '';

		//echo "Hello $facebook_user_id - $facebook_user_name <br>";
		// get list of pages we hav eaccess to:
		$url = 'https://graph.facebook.com/' . $facebook_user_id . '/accounts?access_token=' . $access_token . '';
		if ( $machine_id ) {
			$url .= '&machine_id=' . $machine_id;
		}
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$data   = curl_exec( $ch );
		$result = @json_decode( $data, true );
		$pages  = array();
		do {
			$go = false;
			if ( $result && $result['data'] ) {
				foreach ( $result['data'] as $page ) {
					$pages[ $page['id'] ] = array(
						'name'         => $page['name'],
						'access_token' => $page['access_token'],
					);
				}
				if ( isset( $result['paging'] ) && isset( $result['paging']['next'] ) ) {
					$go  = true;
					$url = $result['paging']['next'];
					if ( $machine_id ) {
						$url .= '&machine_id=' . $machine_id;
					}
					$ch = curl_init( $url );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					$data   = curl_exec( $ch );
					$result = @json_decode( $data, true );
				}
			}
		} while ( $go );
		//print_r($pages);
		$save_data = array(
			'me'    => $facebook_account_data,
			'pages' => $pages,
		);
		$this->update( 'facebook_data', json_encode( $save_data ) );
		$this->update( 'last_checked', time() );
	}


	/**
	 * Links for wordpress
	 */
	public function link_connect() {
		return module_social_facebook::link_open( $this->get( 'social_facebook_id' ), false, false, 'facebook_account_connect' );
	}

	public function link_edit() {
		return module_social_facebook::link_open( $this->get( 'social_facebook_id' ) );
	}

	public function link_new_message() {
		return module_social_facebook::link_open_facebook_message( $this->get( 'social_facebook_id' ), false );
	}

}


class ucm_facebook_page {

	public function __construct( $facebook_account = false, $social_facebook_page_id = false ) {
		$this->facebook_account = $facebook_account;
		$this->load( $social_facebook_page_id );
	}

	/* @var $facebook_account ucm_facebook_account */
	private $facebook_account = false;
	private $social_facebook_page_id = false; // the current user id in our system.
	private $details = array();

	private function reset() {
		$this->social_facebook_page_id = false;
		$this->details                 = array();
		$fields                        = get_fields( 'social_facebook_page' );
		foreach ( $fields as $field_id => $field_data ) {
			$this->{$field_id} = '';
		}
	}

	public function create_new() {
		$this->reset();
		$this->social_facebook_page_id = update_insert( 'social_facebook_page_id', false, 'social_facebook_page', array() );
		$this->load( $this->social_facebook_page_id );
	}

	public function load( $social_facebook_page_id = false ) {
		if ( ! $social_facebook_page_id ) {
			$social_facebook_page_id = $this->social_facebook_page_id;
		}
		$this->reset();
		$this->social_facebook_page_id = $social_facebook_page_id;
		if ( $this->social_facebook_page_id ) {
			$this->details = get_single( 'social_facebook_page', 'social_facebook_page_id', $this->social_facebook_page_id );
			if ( ! is_array( $this->details ) || $this->details['social_facebook_page_id'] != $this->social_facebook_page_id ) {
				$this->reset();

				return false;
			}
		}
		foreach ( $this->details as $key => $val ) {
			$this->{$key} = $val;
		}

		return $this->social_facebook_page_id;
	}

	public function get( $field ) {
		return isset( $this->{$field} ) ? $this->{$field} : false;
	}

	public function update( $field, $value ) {
		// what fields to we allow? or not allow?
		if ( in_array( $field, array( 'social_facebook_page_id' ) ) ) {
			return;
		}
		if ( $this->social_facebook_page_id ) {
			$this->{$field} = $value;
			update_insert( 'social_facebook_page_id', $this->social_facebook_page_id, 'social_facebook_page', array(
				$field => $value,
			) );
		}
	}

	public function delete() {
		if ( $this->social_facebook_page_id ) {
			// delete all the messages for this twitter account.
			$messages = get_multiple( 'social_facebook_message', array(
				'social_facebook_page_id' => $this->social_facebook_page_id,
			), 'social_facebook_message_id' );
			foreach ( $messages as $message ) {
				if ( $message && isset( $message['social_facebook_page_id'] ) && $message['social_facebook_page_id'] == $this->social_facebook_page_id ) {
					delete_from_db( 'social_facebook_message', 'social_facebook_message_id', $message['social_facebook_message_id'] );
					delete_from_db( 'social_facebook_message_link', 'social_facebook_message_id', $message['social_facebook_message_id'] );
					delete_from_db( 'social_facebook_message_read', 'social_facebook_message_id', $message['social_facebook_message_id'] );
				}
			}
			delete_from_db( 'social_facebook_page', 'social_facebook_page_id', $this->social_facebook_page_id );
		}
	}

	public function get_messages( $search = array() ) {
		$facebook                          = new ucm_facebook();
		$search['social_facebook_page_id'] = $this->social_facebook_page_id;

		return $facebook->load_all_messages( $search );
		//return get_m ultiple('social_facebook_message',$search,'social_facebook_message_id','exact','last_active');
	}

	public function run_cron( $debug = false ) {
		// find all messages that haven't been sent yet.
		$messages = $this->get_messages( array(
			'status' => _SOCIAL_MESSAGE_STATUS_PENDINGSEND,
		) );
		$now      = time();
		foreach ( $messages as $message ) {
			if ( isset( $message['message_time'] ) && $message['message_time'] < $now ) {
				$ucm_facebook_message = new ucm_facebook_message( false, $this, $message['social_facebook_message_id'] );
				$ucm_facebook_message->send_queued( $debug );
			}
		}
	}

	/* start FB graph calls */
	public function graph_load_latest_page_data( $debug = false ) {
		// serialise this result into facebook_data.
		if ( ! $this->facebook_account ) {
			echo 'No facebook account linked, please try again';

			return;
		}

		$access_token = $this->get( 'facebook_token' );
		// get the machine id from the parent facebook_account
		$machine_id = $this->facebook_account->get( 'machine_id' );
		if ( ! $access_token ) {
			echo 'No access token for facebook page found';

			return;
		}

		$facebook_page_id = $this->get( 'page_id' );
		if ( ! $facebook_page_id ) {
			echo 'No facebook page id found';

			return;
		}

		if ( $debug ) {
			echo "Getting the latest page data for FB Page: " . $facebook_page_id . "<br>";
		}

		// we keep a record of the last message received so we know where to stop checking in the FB feed
		$last_message_received = (int) $this->get( 'last_message' );

		if ( $debug ) {
			echo "The last message we received for this page was on: " . print_date( $last_message_received, true ) . '<br>';
		}

		$newest_message_received = 0;

		if ( $debug ) {
			echo "Getting /tagged page posts... <br>";
		}
		$facebook_api = new ucm_facebook();
		$page_feed    = $facebook_api->graph( '/' . $facebook_page_id . '/tagged', array(
			'access_token' => $access_token,
			'machine_id'   => $machine_id,
		) );
		$count        = 0;
		if ( isset( $page_feed['error'] ) && ! empty( $page_feed['error'] ) ) {
			if ( $debug ) {
				echo " FACEBOOK ERROR : " . $page_feed['error']['message'] . "<br>";
			}
		}
		if ( isset( $page_feed['data'] ) && ! empty( $page_feed['data'] ) ) {
			foreach ( $page_feed['data'] as $page_feed_message ) {
				if ( ! $page_feed_message['id'] ) {
					continue;
				}
				$message_time            = strtotime( isset( $page_feed_message['updated_time'] ) && strlen( $page_feed_message['updated_time'] ) ? $page_feed_message['updated_time'] : $page_feed_message['created_time'] );
				$newest_message_received = max( $message_time, $newest_message_received );
				if ( $last_message_received && $last_message_received >= $message_time ) {
					// we've already processed messages after this time.
					if ( $debug ) {
						echo " - Skipping this message because it was received on " . print_date( $message_time, true ) . ' and we only want ones after ' . print_date( $last_message_received, true ) . '<br>';
					}
					break;
				} else {
					if ( $debug ) {
						echo ' - storing message received on ' . print_date( $message_time, true ) . '<br>';
					}
				}
				// check if we have this message in our database already.
				$facebook_message = new ucm_facebook_message( $this->facebook_account, $this, false );
				if ( $facebook_message->load_by_facebook_id( $page_feed_message['id'], $page_feed_message, 'feed' ) ) {
					$count ++;
				}
				if ( $debug ) {
					?>
					<div>
					<pre> <?php echo $facebook_message->get( 'facebook_id' ); ?>
						<?php print_r( $facebook_message->get( 'data' ) ); ?>
					</pre>
					</div>
					<?php
				}
			}
		}
		if ( $debug ) {
			echo " got $count new posts <br>";
		}


		// instead of /feed
		if ( $debug ) {
			echo "Getting /posts page posts... <br>";
		}
		$facebook_api = new ucm_facebook();
		$page_feed    = $facebook_api->graph( '/' . $facebook_page_id . '/posts', array(
			'access_token' => $access_token,
			'machine_id'   => $machine_id,
		) );
		$count        = 0;
		if ( isset( $page_feed['error'] ) && ! empty( $page_feed['error'] ) ) {
			if ( $debug ) {
				echo " FACEBOOK ERROR: " . $page_feed['error']['message'] . "<br>";
			}
		}
		if ( isset( $page_feed['data'] ) && ! empty( $page_feed['data'] ) ) {
			foreach ( $page_feed['data'] as $page_feed_message ) {
				if ( ! $page_feed_message['id'] ) {
					continue;
				}
				$message_time            = strtotime( isset( $page_feed_message['updated_time'] ) && strlen( $page_feed_message['updated_time'] ) ? $page_feed_message['updated_time'] : $page_feed_message['created_time'] );
				$newest_message_received = max( $message_time, $newest_message_received );
				if ( $last_message_received && $last_message_received >= $message_time ) {
					// we've already processed messages after this time.
					if ( $debug ) {
						echo " - Skipping this message because it was received on " . print_date( $message_time, true ) . ' and we only want ones after ' . print_date( $last_message_received, true ) . '<br>';
					}
					break;
				} else {
					if ( $debug ) {
						echo ' - storing message received on ' . print_date( $message_time, true ) . '<br>';
					}
				}
				// check if we have this message in our database already.
				$facebook_message = new ucm_facebook_message( $this->facebook_account, $this, false );
				if ( $facebook_message->load_by_facebook_id( $page_feed_message['id'], $page_feed_message, 'feed' ) ) {
					$count ++;
				}
				if ( $debug ) {
					?>
					<div>
					<pre> <?php echo $facebook_message->get( 'facebook_id' ); ?>
						<?php print_r( $facebook_message->get( 'data' ) ); ?>
					</pre>
					</div>
					<?php
				}
			}
		}
		if ( $debug ) {
			echo " got $count new posts <br>";
		}

		if ( $debug ) {
			echo "Getting /conversations inbox messages... <br>";
		}
		// get conversations (inbox) from fb:
		$conversation_feed = $facebook_api->graph( '/' . $facebook_page_id . '/conversations', array(
			'access_token' => $access_token,
			'machine_id'   => $machine_id,
		), 'id,snippet,updated_time,senders,messages{from,to,id,message}' );
		$count             = 0;
		if ( isset( $conversation_feed['error'] ) && ! empty( $conversation_feed['error'] ) ) {
			if ( $debug ) {
				echo " FACEBOOK ERROR: " . $conversation_feed['error']['message'] . "<br>";
			}
		}
		if ( isset( $conversation_feed['data'] ) && ! empty( $conversation_feed['data'] ) ) {
			foreach ( $conversation_feed['data'] as $conversation ) {
				if ( ! $conversation['id'] ) {
					continue;
				}
				$message_time            = strtotime( isset( $conversation['updated_time'] ) && strlen( $conversation['updated_time'] ) ? $conversation['updated_time'] : $conversation['created_time'] );
				$newest_message_received = max( $message_time, $newest_message_received );
				if ( $last_message_received && $last_message_received >= $message_time ) {
					// we've already processed messages after this time.
					if ( $debug ) {
						echo " - Skipping this message because it was received on " . print_date( $message_time, true ) . ' and we only want ones after ' . print_date( $last_message_received, true ) . '<br>';
					}
					break;
				} else {
					if ( $debug ) {
						echo ' - storing message received on ' . print_date( $message_time, true ) . '<br>';
					}
				}
				// check if we have this message in our database already.
				$facebook_message = new ucm_facebook_message( $this->facebook_account, $this, false );
				if ( $facebook_message->load_by_facebook_id( $conversation['id'], $conversation, 'conversation' ) ) {
					$count ++;
				}
			}
		}
		if ( $debug ) {
			echo " got $count new messages <br>";
		}

		if ( $debug ) {
			echo "The last message we received for this page was now on: " . print_date( $newest_message_received, true ) . '<br>';
		}
		if ( $debug ) {
			echo "Finished checking this page messages at " . print_date( time(), true ) . "<br>";
		}

		$this->update( 'last_message', $newest_message_received );
		$this->update( 'last_checked', time() );
	}

	public function link_refresh() {
		return 'admin.php?page=simple_social_inbox_facebook_settings&manualrefresh&social_facebook_id=' . $this->get( 'social_facebook_id' ) . '&facebook_page_id=' . $this->get( 'page_id' );
	}


}


class ucm_facebook_message {

	public function __construct( $facebook_account = false, $facebook_page = false, $social_facebook_message_id = false ) {
		$this->facebook_account = $facebook_account;
		$this->facebook_page    = $facebook_page;
		$this->load( $social_facebook_message_id );
	}

	/* @var $facebook_page ucm_facebook_page */
	private $facebook_page = false;
	/* @var $facebook_account ucm_facebook_account */
	private $facebook_account = false;
	private $social_facebook_message_id = false; // the current user id in our system.
	private $details = array();

	private function reset() {
		$this->social_facebook_message_id = false;
		$this->details                    = array(
			'social_facebook_message_id' => '',
			'marketing_message_id'       => '',
			'social_facebook_page_id'    => '',
			'social_facebook_id'         => '',
			'facebook_id'                => '',
			'summary'                    => '',
			'last_active'                => '',
			'comments'                   => '',
			'type'                       => '',
			'link'                       => '',
			'data'                       => '',
			'status'                     => '',
			'user_id'                    => '',
		);
		foreach ( $this->details as $key => $val ) {
			$this->{$key} = '';
		}
	}

	public function create_new() {
		$this->reset();
		$this->social_facebook_message_id = update_insert( 'social_facebook_message_id', false, 'social_facebook_message', array() );
		$this->load( $this->social_facebook_message_id );
	}

	public function load_by_facebook_id( $facebook_id, $message_data, $type, $debug = false ) {


		$access_token = $this->facebook_page ? $this->facebook_page->get( 'facebook_token' ) : '';
		// get the machine id from the parent facebook_account
		$machine_id = $this->facebook_account ? $this->facebook_account->get( 'machine_id' ) : '';

		if ( ! $message_data ) {
			$facebook_api = new ucm_facebook();
			$data         = $facebook_api->graph( $facebook_id, array(
				'access_token' => $access_token,
				'machine_id'   => $machine_id,
			) );
			if ( $data && isset( $data['id'] ) ) {
				$message_data = $data;
			} else {
				return false;
			}
		}

		// check if exists already
		$existing = get_single( 'social_facebook_message', 'facebook_id', $facebook_id );
		if ( $existing ) {
			// load it up.
			$this->load( $existing['social_facebook_message_id'] );
		} else {
			// ignore if status and feeds match a user
			if ( isset( $message_data['type'] ) && $message_data['type'] == 'status' && $message_data['from']['id'] == $this->facebook_page->get( 'page_id' ) && isset( $message_data['story_tags'] ) && $message_data['story_tags'] ) {
				$tags = current( $message_data['story_tags'] );
				if ( $tags[0]['type'] == 'user' ) {
					return false;
				}
			}
			// create
			$this->create_new();
		}
		// wack out message data into the database.
		if ( $type == 'conversation' ) {
			$message_time = strtotime( isset( $message_data['updated_time'] ) && strlen( $message_data['updated_time'] ) ? $message_data['updated_time'] : $message_data['created_time'] );
			$this->update( 'last_active', $message_time );
			$this->update( 'facebook_id', $message_data['id'] );
			$this->update( 'summary', $message_data['snippet'] );
			$this->update( 'comments', isset( $message_data['messages'] ) ? json_encode( $message_data['messages'] ) : '' );
			if ( isset( $message_data['messages']['data'][0]['from']['id'] ) && $this->facebook_page && $message_data['messages']['data'][0]['from']['id'] == $this->facebook_page->get( 'page_id' ) ) {
				// was the last comment from us?
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
			} else {
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
			}
			$this->update( 'data', json_encode( $message_data ) );
			$this->update( 'type', isset( $message_data['type'] ) ? $message_data['type'] : $type );
			if ( $this->facebook_page ) {
				$this->update( 'social_facebook_page_id', $this->facebook_page->get( 'social_facebook_page_id' ) );
			}
			if ( $this->facebook_account ) {
				$this->update( 'social_facebook_id', $this->facebook_account->get( 'social_facebook_id' ) );
			}
		} else {
			$message_time = strtotime( isset( $message_data['updated_time'] ) && strlen( $message_data['updated_time'] ) ? $message_data['updated_time'] : $message_data['created_time'] );
			$this->update( 'last_active', $message_time );
			$this->update( 'facebook_id', $message_data['id'] );
			$this->update( 'summary', isset( $message_data['message'] ) ? $message_data['message'] : ( isset( $message_data['story'] ) ? $message_data['story'] : 'N/A' ) );
			// grab the comments rom the api again.
			$facebook_api = new ucm_facebook();
			$data         = $facebook_api->graph( $message_data['id'] . '/comments', array(
				//'filter'=>'stream',
				//'fields'=>'from,message,id,attachment,created_time',
				'fields'       => 'from,message,id,attachment,created_time,comments.fields(from,message,id,attachment,created_time)',
				'access_token' => $access_token,
				'machine_id'   => $machine_id,
			) );
			$comments     = isset( $data ) ? $data : ( isset( $message_data['comments'] ) ? $message_data['comments'] : false );
			$this->update( 'comments', json_encode( $comments ) );
			if ( isset( $message_data['comments']['data'][0]['from']['id'] ) && $this->facebook_page && $message_data['comments']['data'][0]['from']['id'] == $this->facebook_page->get( 'page_id' ) ) {
				// was the last comment from us?
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
			} else {
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
			}
			if ( isset( $message_data['messages']['data'][0]['from']['id'] ) && $this->facebook_page && $message_data['messages']['data'][0]['from']['id'] == $this->facebook_page->get( 'page_id' ) ) {
				// was the last comment from us?
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );
			} else {
				$this->update( 'status', _SOCIAL_MESSAGE_STATUS_UNANSWERED );
			}
			$this->update( 'data', json_encode( $message_data ) );
			$this->update( 'type', isset( $message_data['type'] ) ? $message_data['type'] : $type );
			if ( $this->facebook_page ) {
				$this->update( 'social_facebook_page_id', $this->facebook_page->get( 'social_facebook_page_id' ) );
			}
			if ( $this->facebook_account ) {
				$this->update( 'social_facebook_id', $this->facebook_account->get( 'social_facebook_id' ) );
			}
		}

		// work out if this message is answered or not.


		return $this->social_facebook_message_id;
	}

	public function load( $social_facebook_message_id = false ) {
		if ( ! $social_facebook_message_id ) {
			$social_facebook_message_id = $this->social_facebook_message_id;
		}
		$this->reset();
		$this->social_facebook_message_id = $social_facebook_message_id;
		if ( $this->social_facebook_message_id ) {
			$this->details = get_single( 'social_facebook_message', 'social_facebook_message_id', $this->social_facebook_message_id );
			if ( ! is_array( $this->details ) || ! isset( $this->details['social_facebook_message_id'] ) || $this->details['social_facebook_message_id'] != $this->social_facebook_message_id ) {
				$this->reset();

				return false;
			}
		}
		foreach ( $this->details as $key => $val ) {
			$this->{$key} = $val;
		}
		if ( ! $this->facebook_account && $this->get( 'social_facebook_id' ) ) {
			$this->facebook_account = new ucm_facebook_account( $this->get( 'social_facebook_id' ) );
		}
		if ( ! $this->facebook_page && $this->get( 'social_facebook_page_id' ) ) {
			$this->facebook_page = new ucm_facebook_page( $this->facebook_account, $this->get( 'social_facebook_page_id' ) );
		}

		return $this->social_facebook_message_id;
	}

	public function get( $field ) {
		return isset( $this->{$field} ) ? $this->{$field} : false;
	}


	public function update( $field, $value ) {
		// what fields to we allow? or not allow?
		if ( in_array( $field, array( 'social_facebook_message_id' ) ) ) {
			return;
		}
		if ( $this->social_facebook_message_id ) {
			$this->{$field} = $value;
			update_insert( 'social_facebook_message_id', $this->social_facebook_message_id, 'social_facebook_message', array(
				$field => $value,
			) );
			// special processing for certain fields.
			if ( $field == 'comments' ) {
				// we push all thsee comments into a social_facebook_message_comment database table
				// this is so we can do quick lookups on comment ids so we dont import duplicate items from graph (ie: a reply on a comment comes in as a separate item sometimes)
				$data = @json_decode( $value, true );
				if ( $data && isset( $data['data'] ) ) {
					// clear previous comment history.
					$existing_comments = get_multiple( 'social_facebook_message_comment', array( 'social_facebook_message_id' => $this->social_facebook_message_id ), 'social_facebook_message_comment_id' );
					//delete_from_db('social_facebook_message_comment','social_facebook_message_id',$this->social_facebook_message_id);
					$remaining_comments = $this->_update_comments( $data, $existing_comments );
					// $remaining_comments contains any comments that no longer exist...
					// todo: remove these? yer prolly. do a quick test on removing a comment - i think the only thing is it will show the 'from' name still.
				}
			}
		}
	}

	public function parse_links( $content = false ) {
		if ( ! $this->get( 'social_facebook_message_id' ) ) {
			return;
		}
		// strip out any links in the tweet and write them to the facebook_message_link table.
		$url_clickable = '~
		            ([\\s(<.,;:!?])                                        # 1: Leading whitespace, or punctuation
		            (                                                      # 2: URL
		                    [\\w]{1,20}+://                                # Scheme and hier-part prefix
		                    (?=\S{1,2000}\s)                               # Limit to URLs less than about 2000 characters long
		                    [\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]*+         # Non-punctuation URL character
		                    (?:                                            # Unroll the Loop: Only allow puctuation URL character if followed by a non-punctuation URL character
		                            [\'.,;:!?)]                            # Punctuation URL character
		                            [\\w\\x80-\\xff#%\\~/@\\[\\]*(+=&$-]++ # Non-punctuation URL character
		                    )*
		            )
		            (\)?)                                                  # 3: Trailing closing parenthesis (for parethesis balancing post processing)
		    ~xS'; // The regex is a non-anchored pattern and does not have a single fixed starting character.
		// Tell PCRE to spend more time optimizing since, when used on a page load, it will probably be used several times.
		if ( ! $content ) {
			$content       = $this->get( 'summary' );
			$doing_summary = true;
		}
		$summary = ' ' . $content . ' ';
		if ( strlen( $summary ) && preg_match_all( $url_clickable, $summary, $matches ) ) {
			foreach ( $matches[2] as $id => $url ) {
				$url = trim( $url );
				if ( strlen( $url ) ) {
					// wack this url into the database and replace it with our rewritten url.
					$social_facebook_message_link_id = ucm_update_insert( 'social_facebook_message_link_id', false, 'social_facebook_message_link', array(
						'social_facebook_message_id' => $this->get( 'social_facebook_message_id' ),
						'link'                       => $url,
					) );
					if ( $social_facebook_message_link_id ) {
						$new_link = trailingslashit( get_site_url() );
						$new_link .= strpos( $new_link, '?' ) === false ? '?' : '&';
						$new_link .= _SIMPLE_SOCIAL_FACEBOOK_LINK_REWRITE_PREFIX . '=' . $social_facebook_message_link_id;
						// basic hash to stop brute force.
						if ( defined( 'AUTH_KEY' ) ) {
							$new_link .= ':' . substr( md5( AUTH_KEY . ' facebook link ' . $social_facebook_message_link_id ), 1, 5 );
						}
						$newsummary = trim( preg_replace( '#' . preg_quote( $url, '#' ) . '#', $new_link, $summary, 1 ) );
						if ( strlen( $newsummary ) ) {// just incase.
							$summary = $newsummary;
						}
					}
				}
			}
		}
		if ( isset( $doing_summary ) && $doing_summary ) {
			$this->update( 'summary', $summary );
		}

		return trim( $summary );
	}

	private function _update_comments( $data, $existing_comments ) {
		if ( $data && isset( $data['data'] ) && is_array( $data['data'] ) ) {
			foreach ( $data['data'] as $comment ) {
				if ( $comment['id'] ) {
					// does this id exist in the db already?
					$exists = get_single( 'social_facebook_message_comment', array(
						'facebook_id',
						'social_facebook_message_id'
					), array( $comment['id'], $this->social_facebook_message_id ) );

					/*if(!isset($comment['from']) || !isset($comment['to']) || !isset($comment['message'])){
					    $facebook_api = new ucm_facebook();
					    $access_token = $this->facebook_page->get('facebook_token');
					    // get the machine id from the parent facebook_account
					    $machine_id = $this->facebook_account->get('machine_id');
					    $conversation_feed = $facebook_api->graph('/'.$comment['id'].'',array(
						    'access_token' => $access_token,
						    'machine_id' => $machine_id,
					    ), 'id,from,to,message');
				    }*/

					$social_facebook_message_comment_id = update_insert( 'social_facebook_message_comment_id', $exists ? $exists['social_facebook_message_comment_id'] : false, 'social_facebook_message_comment', array(
						'social_facebook_message_id' => $this->social_facebook_message_id,
						'facebook_id'                => $comment['id'],
						'time'                       => isset( $comment['updated_time'] ) ? strtotime( $comment['updated_time'] ) : ( isset( $comment['created_time'] ) ? strtotime( $comment['created_time'] ) : 0 ),
						'data'                       => json_encode( $comment ),
						'from'                       => isset( $comment['from'] ) ? json_encode( $comment['from'] ) : '',
						'to'                         => isset( $comment['to'] ) ? json_encode( $comment['to'] ) : '',
					) );
					if ( isset( $existing_comments[ $social_facebook_message_comment_id ] ) ) {
						unset( $existing_comments[ $social_facebook_message_comment_id ] );
					}
					if ( isset( $comment['comments'] ) && is_array( $comment['comments'] ) ) {
						$existing_comments = $this->_update_comments( $comment['comments'], $existing_comments );
					}
				}
			}
		}

		return $existing_comments;
	}

	public function delete() {
		if ( $this->social_facebook_message_id ) {
			delete_from_db( 'social_facebook_message', 'social_facebook_message_id', $this->social_facebook_message_id );
		}
	}


	public function mark_as_read() {
		if ( $this->social_facebook_message_id && module_security::is_logged_in() ) {
			$sql = "REPLACE INTO `" . _DB_PREFIX . "social_facebook_message_read` SET `social_facebook_message_id` = " . (int) $this->social_facebook_message_id . ", `user_id` = " . (int) module_security::get_loggedin_id() . ", read_time = " . (int) time();
			query( $sql );
		}
	}

	public function get_summary() {
		// who was the last person to contribute to this post? show their details here instead of the 'summary' box maybe?
		$summary = $this->get( 'summary' );
		if ( empty( $summary ) ) {
			$summary = _l( 'N/A' );
		}

		return htmlspecialchars( strlen( $summary ) > 80 ? substr( $summary, 0, 80 ) . '...' : $summary );
	}

	private $can_reply = false;

	private function _output_block( $facebook_data, $level ) {
		if ( ! isset( $facebook_data['picture'] ) && isset( $facebook_data['attachment'], $facebook_data['attachment']['type'], $facebook_data['attachment']['media']['image']['src'] ) ) {
			$facebook_data['picture'] = $facebook_data['attachment']['media']['image']['src'];
			$facebook_data['link']    = isset( $facebook_data['attachment']['url'] ) ? $facebook_data['attachment']['url'] : false;
		}
		if ( isset( $facebook_data['comments'] ) ) {
			$comments = $this->get_comments( $facebook_data['comments'] );
		} else {
			$comments = array();
		}
		//echo '<pre>';print_r($facebook_data);echo '</pre>';
		if ( $facebook_data['message'] !== false ) {
			?>
			<div class="facebook_comment">
				<div class="facebook_comment_picture">
					<?php if ( isset( $facebook_data['from']['id'] ) ) { ?>
						<img src="//graph.facebook.com/<?php echo $facebook_data['from']['id']; ?>/picture">
					<?php } ?>
				</div>
				<div class="facebook_comment_header">
					<?php echo isset( $facebook_data, $facebook_data['from'] ) ? ucm_facebook::format_person( $facebook_data['from'] ) : 'N/A'; ?>
					<span><?php $time = strtotime( isset( $facebook_data['updated_time'] ) ? $facebook_data['updated_time'] : ( isset( $facebook_data['created_time'] ) ? $facebook_data['created_time'] : false ) );
						echo $time ? ' @ ' . print_date( $time, true ) : '';

						// todo - better this! don't call on every comment, load list in main loop and pass through all results.
						if ( isset( $facebook_data['user_id'] ) && $facebook_data['user_id'] ) {
							echo ' (sent by ' . module_user::link_open( $facebook_data['user_id'], true ) . ')';
						} else if ( isset( $facebook_data['id'] ) && $facebook_data['id'] ) {
							$exists = get_single( 'social_facebook_message_comment', array(
								'facebook_id',
								'social_facebook_message_id'
							), array( $facebook_data['id'], $this->social_facebook_message_id ) );
							if ( $exists && isset( $exists['user_id'] ) && $exists['user_id'] ) {
								echo ' (sent by ' . module_user::link_open( $exists['user_id'], true ) . ')';
							}
						}
						?>
				</span>
				</div>
				<div class="facebook_comment_body">
					<?php if ( isset( $facebook_data['picture'] ) && $facebook_data['picture'] ) { ?>
						<div class="facebook_picture">
							<?php if ( isset( $facebook_data['link'] ) && $facebook_data['link'] ){ ?> <a
								href="<?php echo htmlspecialchars( $facebook_data['link'] ); ?>" target="_blank"> <?php } ?>
								<img src="<?php echo htmlspecialchars( $facebook_data['picture'] ); ?>">
								<?php if ( isset( $facebook_data['link'] ) && $facebook_data['link'] ){ ?> </a> <?php } ?>
						</div>
					<?php } ?>
					<div>
						<?php echo forum_text( $facebook_data['message'] ); ?>
					</div>
				</div>
				<div class="facebook_comment_actions">
					<?php if ( $this->can_reply && ( ( $this->get( 'type' ) != 'conversation' && $level == 2 ) ) ) { ?>
						<a href="#" class="facebook_reply_button"><?php _e( 'Reply' ); ?></a>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
		<div class="facebook_comment_replies">
			<?php
			//if(strpos($facebook_data['message'],'picture')){
			//echo '<pre>'; print_r($facebook_data); echo '</pre>';
			//}
			if ( count( $comments ) ) {
				// recursively print out our comments!
				//$comments = array_reverse($comments);
				foreach ( $comments as $comment ) {
					$this->_output_block( $comment, $level + 1 );
				}
			}
			if ( $this->can_reply && isset( $facebook_data['id'] ) && $facebook_data['id'] && ( ( $this->get( 'type' ) == 'conversation' && $level == 1 ) || ( $this->get( 'type' ) != 'conversation' && $level <= 2 ) ) ) {
				$this->reply_box( $facebook_data['id'], $level );
			}
			?>
		</div>
		<?php
	}

	public function full_message_output( $can_reply = false ) {
		$this->can_reply = $can_reply;
		// used in social_facebook_list.php to display the full message and its comments
		switch ( $this->get( 'type' ) ) {
			case 'conversation':
				$facebook_data['id']       = $this->get( 'facebook_id' );
				$facebook_data['message']  = false;
				$facebook_data['comments'] = array_reverse( $this->get_comments() );
				$this->_output_block( $facebook_data, 1 );
				break;
			default:
				$facebook_data             = @json_decode( $this->get( 'data' ), true );
				$facebook_data['message']  = $this->get( 'summary' );
				$facebook_data['user_id']  = $this->get( 'user_id' );
				$facebook_data['comments'] = array_reverse( $this->get_comments() );
				//echo '<pre>'; print_r($facebook_data['comments']); echo '</pre>';
				$this->_output_block( $facebook_data, 1 );

				break;
		}
	}

	public function reply_box( $facebook_id, $level = 1 ) {
		if ( $this->facebook_account && $this->facebook_page && $this->social_facebook_message_id ) {
			?>
			<div class="facebook_comment facebook_comment_reply_box facebook_comment_reply_box_level<?php echo $level; ?>">
				<div class="facebook_comment_picture">
					<img src="//graph.facebook.com/<?php echo $this->facebook_page->get( 'page_id' ); ?>/picture">
				</div>
				<div class="facebook_comment_header">
					<?php echo ucm_facebook::format_person( array(
						'id'   => $this->facebook_page->get( 'page_id' ),
						'name' => $this->facebook_page->get( 'page_name' )
					) ); ?>
				</div>
				<div class="facebook_comment_reply">
					<textarea placeholder="Write a reply..."></textarea>
					<button data-facebook-id="<?php echo htmlspecialchars( $facebook_id ); ?>"
					        data-id="<?php echo (int) $this->social_facebook_message_id; ?>"><?php _e( 'Send' ); ?></button>
					<br/>
					(debug) <input type="checkbox" name="debug" class="reply-debug" value="1">
				</div>
				<div class="facebook_comment_actions"></div>
			</div>
			<?php
		} else {
			?>
			<div class="facebook_comment facebook_comment_reply_box">
				(incorrect settings, please report this bug)
			</div>
			<?php
		}
	}

	public function get_link() {
		// todo: doesn't work on image uploads by admin
		return '//facebook.com/' . htmlspecialchars( $this->get( 'facebook_id' ) );
	}

	private $attachment_name = '';

	public function add_attachment( $local_filename ) {
		if ( is_file( $local_filename ) ) {
			$this->attachment_name = $local_filename;
		}
	}

	public function send_queued( $debug = false ) {
		if ( $this->facebook_account && $this->facebook_page && $this->social_facebook_message_id ) {
			// send this message out to facebook.
			// this is run when user is composing a new message from the UI,
			if ( $this->get( 'status' ) == _SOCIAL_MESSAGE_STATUS_SENDING ) {
				return;
			} // dont double up on cron.
			$this->update( 'status', _SOCIAL_MESSAGE_STATUS_SENDING );

			$access_token = $this->facebook_page->get( 'facebook_token' );
			// get the machine id from the parent facebook_account
			$machine_id = $this->facebook_account->get( 'machine_id' );

			$user_post_data = @json_decode( $this->get( 'data' ), true );

			$facebook_page_id = $this->facebook_page->get( 'page_id' );

			if ( $debug ) {
				echo "Sending a new message to facebook Page ID: $facebook_page_id <br>\n";
			}
			$result    = false;
			$facebook  = new ucm_facebook();
			$post_data = array(
				'access_token' => $access_token,
				'machine_id'   => $machine_id,
			);

			// todo: message or link are required.
			$message = $this->get( 'summary' );
			if ( ! empty( $message ) ) {
				$post_data['message'] = $message;
			}
			$now       = time();
			$send_time = $this->get( 'last_active' );

			if ( isset( $user_post_data['post_type'] ) && $user_post_data['post_type'] == 'picture' && ! empty( $this->attachment_name ) && is_file( $this->attachment_name ) ) {
				// we're posting a photo! change the post source from /feed to /photos

				//$post_data['source'] = new CURLFile($this->attachment_name, 'image/jpg'); //'@'.$this->attachment_name;
				$post_data['source'] = '@' . $this->attachment_name;
				/*if($send_time && $send_time > $now) {
					// schedule in the future / image posts dont support backdating.
					$post_data['scheduled_publish_time'] = $send_time; //date('c',$send_time);
					$post_data['published']              = 0;
				}*/

				$result = $facebook->graph_post( '' . $facebook_page_id . '/photos', $post_data );

			} else if ( isset( $user_post_data['post_type'] ) && $user_post_data['post_type'] == 'link' ) {
				// sending a normal wall post, support links.
				$link = $this->get( 'link' );
				if ( ! empty( $link ) ) {
					// do we format this link into something trackable?
					if ( isset( $user_post_data['track_links'] ) && $user_post_data['track_links'] ) {
						$link = $this->parse_links( $link );
					}
					$post_data['link'] = $link;
					if ( isset( $user_post_data['link_picture'] ) && ! empty( $user_post_data['link_picture'] ) ) {
						$post_data['picture'] = $user_post_data['link_picture'];
					}
					if ( isset( $user_post_data['link_name'] ) && ! empty( $user_post_data['link_name'] ) ) {
						$post_data['name'] = $user_post_data['link_name'];
					}
					if ( isset( $user_post_data['link_caption'] ) && ! empty( $user_post_data['link_caption'] ) ) {
						$post_data['caption'] = $user_post_data['link_caption'];
					}
					if ( isset( $user_post_data['link_description'] ) && ! empty( $user_post_data['link_description'] ) ) {
						$post_data['description'] = $user_post_data['link_description'];
					}
				}
				/*if($send_time && $send_time > $now){
					// schedule in the future.
					$post_data['scheduled_publish_time'] = $send_time; //date('c',$send_time);
					$post_data['published'] = 0;
				}else if($send_time && $send_time < $now){
					$post_data['backdated_time'] = date('c',$send_time);
				}else{

				}*/
				$result = $facebook->graph_post( '' . $facebook_page_id . '/feed', $post_data );
			} else {
				// standard wall post, no link or picture..
				/*if($send_time && $send_time > $now){
					// schedule in the future.
					$post_data['scheduled_publish_time'] = $send_time; //date('c',$send_time);
					$post_data['published'] = 0;
				}else if($send_time && $send_time < $now){
					$post_data['backdated_time'] = date('c',$send_time);
				}else{

				}*/
				$result = $facebook->graph_post( '' . $facebook_page_id . '/feed', $post_data );
			}
			if ( $debug ) {
				echo "Graph Post Result: <br>\n" . var_export( $result, true ) . " <br>\n";
			}
			if ( $result && isset( $result['id'] ) ) {
				$this->update( 'facebook_id', $result['id'] );
				// reload this message and comments from the graph api.
				$this->load_by_facebook_id( $this->get( 'facebook_id' ), false, $this->get( 'type' ), $debug );
			} else {
				echo 'Failed to send message. Error was: ' . var_export( $result, true );
				// remove from database.
				$this->delete();

				return false;
			}

			// successfully sent, mark is as answered.
			$this->update( 'status', _SOCIAL_MESSAGE_STATUS_ANSWERED );

			return true;
		}

		return false;
	}

	public function send_reply( $facebook_id, $message, $debug = false ) {
		if ( $this->facebook_account && $this->facebook_page && $this->social_facebook_message_id ) {
			$access_token = $this->facebook_page->get( 'facebook_token' );
			// get the machine id from the parent facebook_account
			$machine_id = $this->facebook_account->get( 'machine_id' );

			if ( ! $facebook_id ) {
				$facebook_id = $this->get( 'facebook_id' );
			}

			if ( $debug ) {
				echo "Sending a reply to facebook ID: $facebook_id <br>\n";
			}
			if ( $debug ) {
				echo "Type: " . $this->get( 'type' ) . " <br>\n";
			}
			$result = false;
			switch ( $this->get( 'type' ) ) {
				case 'conversation':
					// do we reply to the previous comment?
					// nah for now we just add it to the bottom of the list.
					$facebook = new ucm_facebook();
					$result   = $facebook->graph_post( '' . $facebook_id . '/messages', array(
						'message'      => $message,
						'access_token' => $access_token,
						'machine_id'   => $machine_id,
					) );
					if ( $debug ) {
						echo "Graph Post Result: <br>\n" . var_export( $result, true ) . " <br>\n";
					}
					// reload this message and comments from the graph api.
					$this->load_by_facebook_id( $this->get( 'facebook_id' ), false, $this->get( 'type' ), $debug );
					break;
					break;
				default:
					// do we reply to the previous comment?
					// nah for now we just add it to the bottom of the list.
					$facebook = new ucm_facebook();
					$result   = $facebook->graph_post( '' . $facebook_id . '/comments', array(
						'message'      => $message,
						'access_token' => $access_token,
						'machine_id'   => $machine_id,
					) );
					if ( $debug ) {
						echo "Graph Post Result: <br>\n" . var_export( $result, true ) . " <br>\n";
					}
					// reload this message and comments from the graph api.
					$this->load_by_facebook_id( $this->get( 'facebook_id' ), false, $this->get( 'type' ), $debug );
					break;
			}
			// hack to add the 'user_id' of who created this reply to the db for logging.
			if ( $result && isset( $result['id'] ) ) {
				// find this comment id in our facebook comment database.
				$exists = get_single( 'social_facebook_message_comment', array(
					'facebook_id',
					'social_facebook_message_id'
				), array( $result['id'], $this->social_facebook_message_id ) );
				if ( $exists && $exists['social_facebook_message_comment_id'] ) {
					// it really should exist after we've done the 'load_by_facebook_id' above. however with a post with lots of comments it may not appear without graph api pagination.
					// todo - pagination!
					update_insert( 'social_facebook_message_comment_id', $exists['social_facebook_message_comment_id'], 'social_facebook_message_comment', array(
						'user_id' => module_security::get_loggedin_id(),
					) );
				}
			}

		}
	}

	public function get_comments( $comment_data = false ) {
		$data     = $comment_data ? $comment_data : @json_decode( $this->get( 'comments' ), true );
		$comments = array();
		if ( $data && isset( $data['data'] ) ) {
			$comments = $data['data'];
			// format them up nicely.
		} else {
			$comments = $data;
		}

		return $comments;
	}

	public function get_type_pretty() {
		$type = $this->get( 'type' );
		switch ( $type ) {
			case 'conversation':
				return 'Inbox Message';
				break;
			case 'status':
				return 'Wall Post';
				break;
			default:
				return ucwords( $type );
		}
	}

	public function get_from() {
		if ( $this->social_facebook_message_id ) {
			$from = array();
			$data = @json_decode( $this->get( 'data' ), true );
			if ( isset( $data['from']['id'] ) ) {
				$from[ $data['from']['id'] ] = $data['from']['name'];
			}
			if ( isset( $data['senders']['data'] ) && is_array( $data['senders']['data'] ) ) {
				foreach ( $data['senders']['data'] as $sender ) {
					$from[ $sender['id'] ] = $sender['name'];
				}
			}

			$messages = get_multiple( 'social_facebook_message_comment', array( 'social_facebook_message_id' => $this->social_facebook_message_id ), 'social_facebook_message_comment_id' );
			foreach ( $messages as $message ) {
				if ( $message['from'] ) {
					$data = @json_decode( $message['from'], true );
					if ( isset( $data['id'] ) ) {
						$from[ $data['id'] ] = $data['name'];
					}
				}
			}

			return $from;
		}

		return array();
	}


	public function link_open() {
		return 'admin.php?page=simple_social_inbox_main&social_facebook_id=' . $this->facebook_account->get( 'social_facebook_id' ) . '&social_facebook_message_id=' . $this->social_facebook_message_id;
	}


}