<?php

define( '_NEWSLETTER_STATUS_NEW', 0 );
define( '_NEWSLETTER_STATUS_PENDING', 1 );
define( '_NEWSLETTER_STATUS_PAUSED', 2 );
define( '_NEWSLETTER_STATUS_SENT', 3 );
define( '_NEWSLETTER_STATUS_DELETED', 4 );
define( '_NEWSLETTER_STATUS_FAILED', 6 );
define( '_NEWSLETTER_STATUS_BOUNCED', 7 );

define( '_MEMBER_HASH_URL_REDIRECT_BITS', 'UCMmhurb' );


class module_newsletter extends module_base {

	public $links;
	public $newsletter_types;

	public $version = 2.469;
	// 2.469 - 2021-04-07 - php8 compatibility fix
	// 2.468 - 2017-05-07 - template file improvements
	// 2.467 - 2017-05-02 - file path configuration
	// 2.466 - 2017-02-20 - default newsletter template
	// 2.465 - 2016-11-16 - fontawesome icon fixes
	// 2.464 - 2016-07-10 - big update to mysqli
	// 2.463 - 2015-03-15 - speed improvements
	// 2.462 - 2015-03-07 - link tracking fix
	// 2.461 - 2015-01-16 - css link tracking fix
	// 2.46 - 2014-10-06 - image link fixes
	// 2.459 - 2014-08-05 - responsive improvements

	// 2.3 - added templates to the listing.
	// 2.4 - newsletter subscriptions and modifying subscriptiond etials.
	// 2.41 - view online render correctly.
	// 2.42 - fixed hash on external viewing, making the newsletters render same as in email
	// 2.421 - configurable tab name.
	// 2.422 - preview email and view shows dynamic fields.
	// 2.423 - short tag issue in newsletter list page.
	// 2.424 - correct link click showing in stats.
	// 2.425 - fixed email preview showing empty fields
	// 2.426 - correct from on double opt in addresses.
	// 2.427 - member details url
	// 2.428 - fix for emails wiht spaces in them
	// 2.429 - total link click fix
	// 2.4301 - fix for viewing newsletters with wrong id.
	// 2.4302 - fix for double opt in confirmation
	// 2.431 - checking bounces manually via settings, adn sending correctiyl via cron.
	// 2.432 - bounce bug fix
	// 2.433 - bounce bug fix
	// 2.434 - layout tweaks
	// 2.435 - permission fix
	// 2.436 - bounce extra options for ssl/port
	// 2.437 - bounce checking fixes part 1 of 2 (part 2 will be reading message id back from server upon submission)
	// 2.438 - fix 'edit' menu
	// 2.439 - BIG UPDATE! limit on number of emails per minute/hour/day
	// 2.440 - send statistics page shows which links were clicked how many times.
	// 2.441 - preview link added to past sends screen
	// 2.442 - re-subscribe bug fix
	// 2.443 - link tracking improvements.
	// 2.444 - language fix
	// 2.445 - view newsletter online bug fix
	// 2.446 - bounce detection improvements
	// 2.447 - improved quick search
	// 2.448 - 2013-06-14 - newsletter send to all contacts under grouped customer
	// 2.449 - 2013-07-01 - deleted member fix
	// 2.451 - 2013-07-29 - new _UCM_SECRET hash in config.php
	// 2.452 - 2013-08-27 - cron job fix
	// 2.453 - 2013-11-10 - new ui layout
	// 2.454 - 2014-02-05 - newsletter speed improvements for large member lists
	// 2.455 - 2014-02-14 - default content box in template editor
	// 2.456 - 2014-02-14 - faster image loading
	// 2.457 - 2014-02-23 - installation fix
	// 2.458 - 2014-03-26 - duplicate newsletter fix


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
		$this->links            = array();
		$this->newsletter_types = array();
		$this->module_name      = "newsletter";
		$this->module_position  = 22;

		if ( $this->can_i( 'view', 'Newsletters' ) ) {
			$this->links[] = array(
				"name"      => module_config::c( 'newsletter_tab_name', 'Newsletters' ),
				"p"         => "newsletter_admin",
				'args'      => array( 'newsletter_id' => false ),
				'icon_name' => 'envelope-o',
			);
			if ( isset( $_REQUEST['member_id'] ) && (int) $_REQUEST['member_id'] > 0 ) {
				$this->links[] = array(
					"name"                => "Member Newsletters",
					"p"                   => "newsletter_member",
					'args'                => array( 'newsletter_id' => false, 'member_id' => (int) $_REQUEST['member_id'] ),
					'holder_module'       => 'member', // which parent module this link will sit under.
					'holder_module_page'  => 'member_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 1, // 0 on it's own
					'allow_nesting'       => 1, // missing on it's own menu setting.
				);
			}
		}

		if ( module_config::can_i( 'view', 'Settings' ) ) {
			$this->links[] = array(
				"name"                => module_config::c( 'newsletter_tab_name', 'Newsletters' ),
				"p"                   => "newsletter_settings",
				"args"                => array( 'user_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'order'               => 3,
			);
		}

		module_config::register_css( 'newsletter', 'newsletter.css' );

		// todo - search the newsletter_send list for subjects as well..
		/*$this->ajax_search_keys = array(
            _DB_PREFIX.'newsletter' => array(
                'plugin' => 'newsletter',
                'search_fields' => array(
                    'subject',
                ),
                'key' => 'newsletter_id',
                'title' => _l('Newsletter: '),
            ),
        );*/

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'newsletter_unsubscribe_done', '<h2>Unsubscribe Successful</h2>
    <p>Email Address: <strong>{EMAIL}</strong> </p>
    <p>You have been successfully unsubscribed from the newsletter system.</p>
    ', 'Displayed when unsubscription is complete.', 'code', array(
				'EMAIL' => 'The users email address',
			) );

			module_template::init_template( 'newsletter_unsubscribe', '<h2>Unsubscribe</h2>
<form action="" method="post">
    <p>Please Enter Your Email Address: <input type="text" name="email" value="{EMAIL}"> </p>
    <p><input type="submit" name="confirm" value="Unsubscribe"></p>
</form>
    ', 'Used when a user wishes to unsubscribe.', 'code', array(
				'EMAIL'         => 'The users email address',
				'UNSUB_CONFIRM' => 'The URL to confirm unsubscription',
			) );


			module_template::init_template( 'member_subscription_double_optin', '<h2>Confirm Subscription</h2>
<p>Thank you for subscribing to our newsletter system. Please click the link below to confirm your subscription.</p>
<p><a href="{LINK}">{LINK}</a></p>
    ', 'Sent to a user when they subscribe via your website.', 'code', array(
				'EMAIL' => 'The users email address',
				'LINK'  => 'The URL to confirm subscription',
			) );
			module_template::init_template( 'member_subscription_confirmed', '<h2>Subscription Confirmed</h2>
<p>Thank you for confirming your newsletter subscription.</p>
    ', 'Displayed after use clicks their double opt-in link.', 'code', array() );
		}

	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."newsletter` c WHERE ";
			//$sql .= " c.`newsletter_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_newsletters( array( 'subject' => $search_key, 'group_results' => 1 ) );
			if ( mysqli_num_rows( $results ) ) {
				while ( $result = mysqli_fetch_assoc( $results ) ) {
					// what part of this matched?
					/*if(
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['last_name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['phone'])
                    ){
                        // we matched the newsletter contact details.
                        $match_string = _l('Newsletter Contact: ');
                        $match_string .= _shl($result['newsletter_name'],$search_key);
                        $match_string .= ' - ';
                        $match_string .= _shl($result['name'],$search_key);
                        // hack
                        $_REQUEST['newsletter_id'] = $result['newsletter_id'];
                        $ajax_results [] = '<a href="'.module_user::link_open_contact($result['user_id']) . '">' . $match_string . '</a>';
                    }else{*/
					$match_string    = _l( 'Newsletter: ' );
					$match_string    .= _shl( $result['subject'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['newsletter_id'] ) . '">' . $match_string . '</a>';
					//$ajax_results [] = $this->link_open($result['newsletter_id'],true);
					/*}*/
				}
			}
		}

		return $ajax_results;
	}

	public static function link_generate( $newsletter_id = false, $options = array(), $link_options = array(), $data = false ) {

		$key = 'newsletter_id';
		if ( $newsletter_id === false && $link_options ) {
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
			$options['type'] = 'newsletter';
		}

		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['newsletter_id'] = $newsletter_id;
		$options['module']                     = 'newsletter';
		$data                                  = isset( $options['data'] ) ? $options['data'] : array();
		if ( isset( $options['full'] ) && $options['full'] ) {
			if ( ! $data ) {
				$data = self::get_newsletter( $newsletter_id );
			}
			$options['data'] = $data;
		}
		// what text should we display in this link?
		if ( ! isset( $options['text'] ) || ! $options['text'] ) {
			$options['text'] = ( ! isset( $data['subject'] ) || ! trim( $data['subject'] ) ) ? 'N/A' : $data['subject'];
		}
		if ( ! $link_options ) {
			// only bubble up once to this same module.
			//$options['page'] = 'newsletter_edit';
			if ( ! isset( $options['page'] ) ) {
				$options['page'] = 'newsletter_edit';
			}
			$bubble_to_module = array(
				'module' => 'newsletter',
			);
		} else {
			// for first loop
			$options['page'] = 'newsletter_admin';
		}

		array_unshift( $link_options, $options );

		if ( ! module_security::has_feature_access( array(
			'name'        => 'Newsletters',
			'module'      => 'newsletter',
			'category'    => 'Newsletter',
			'view'        => 1,
			'description' => 'view',
		) ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				return '#';
			} else {
				return isset( $options['text'] ) ? $options['text'] : 'N/A';
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

	public static function link_open( $newsletter_id, $full = false, $data = array() ) {
		return self::link_generate( $newsletter_id, array( 'full' => $full, 'data' => $data ) );
	}

	public static function link_list( $newsletter_id, $full = false ) {
		return self::link_generate( $newsletter_id, array( 'full' => $full, 'page' => 'newsletter_list' ) );
	}

	public static function link_preview( $newsletter_id, $full = false ) {
		return self::link_generate( $newsletter_id, array( 'full' => $full, 'page' => 'newsletter_preview' ) );
	}

	public static function link_statistics( $newsletter_id, $send_id, $full = false, $data = array() ) {
		if ( ! $data ) {
			$data = self::get_send( $send_id );
		}

		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'data'      => $data,
			'page'      => 'newsletter_statistics',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_statistics_link_clicks( $newsletter_id, $send_id, $full = false, $data = array() ) {
		if ( ! $data ) {
			$data = self::get_send( $send_id );
		}

		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'data'      => $data,
			'page'      => 'newsletter_statistics_link_clicks',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_send( $newsletter_id, $full = false, $send_id = false ) {
		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'page'      => 'newsletter_send',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_queue( $newsletter_id, $send_id, $full = false ) {
		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'page'      => 'newsletter_queue',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_queue_watch( $newsletter_id, $send_id, $full = false ) {
		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'page'      => 'newsletter_queue_watch',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_queue_manual( $newsletter_id, $send_id, $full = false ) {
		return self::link_generate( $newsletter_id, array(
			'full'      => $full,
			'page'      => 'newsletter_queue_manual',
			'arguments' => array( 'send_id' => $send_id )
		) );
	}

	public static function link_open_template( $newsletter_template_id, $full = false ) {
		$data = self::get_newsletter_template( $newsletter_template_id );

		return self::link_generate( false, array(
			'full'      => $full,
			'page'      => 'newsletter_template',
			'arguments' => array(
				'newsletter_template_id' => $newsletter_template_id,
			),
			'text'      => $data['newsletter_template_name'],
		), array(), $data );
	}


	public function process() {
		$errors = array();
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['newsletter_id'] ) {
			$data = self::get_newsletter( $_REQUEST['newsletter_id'] );
			if ( module_form::confirm_delete( 'newsletter_id', "Really delete newsletter: " . $data['subject'], self::link_open( $_REQUEST['newsletter_id'] ) ) ) {
				$this->delete_newsletter( $_REQUEST['newsletter_id'] );
				set_message( "Newsletter deleted successfully" );
				redirect_browser( self::link_list( false ) );
			}
		} else if ( "save_newsletter" == $_REQUEST['_process'] ) {
			$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
			$newsletter_id = $this->save_newsletter( $newsletter_id, $_POST );
			if ( isset( $_REQUEST['butt_send'] ) ) {
				redirect_browser( $this->link_send( $newsletter_id ) );
			}
			if ( isset( $_REQUEST['butt_duplicate'] ) ) {
				$newsletter_id = $this->duplicate_newsetter( $newsletter_id );
				set_message( 'Newsletter duplicated successfully' );
				redirect_browser( $this->link_open( $newsletter_id ) );
			}
			if ( isset( $_REQUEST['butt_preview_email'] ) ) {
				if ( $this->send_preview( $newsletter_id, $_REQUEST['quick_email'] ) ) {
					//set_message("Newsletter preview sent successfully.");
					redirect_browser( $this->link_open( $newsletter_id ) );
				}/*else{
                    echo "<br><br>Failed to send preview. <br><br>";
                    echo '<a href="'.$this->link_open($newsletter_id).'">try again</a> ';
                    exit;
                }*/
			}
			if ( isset( $_REQUEST['butt_preview'] ) ) {
				redirect_browser( $this->link_preview( $newsletter_id ) );
			}
			set_message( "Newsletter saved successfully" );
			redirect_browser( $this->link_open( $newsletter_id ) );
		} else if ( "send_send" == $_REQUEST['_process'] ) {
			$newsletter_id = (int) $_REQUEST['newsletter_id'];
			$send_id       = (int) $_REQUEST['send_id'];
			if ( $newsletter_id && $send_id ) {
				$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send` SET `status` = " . _NEWSLETTER_STATUS_PENDING . " WHERE send_id = $send_id AND newsletter_id = $newsletter_id";
				query( $sql );
				self::update_member_data_for_send( $send_id );
				self::remove_unsubscribed_members_from_send( $send_id );
				//ready to send
				redirect_browser( $this->link_queue_watch( $newsletter_id, $send_id ) );
			}

		} else if ( "modify_send" == $_REQUEST['_process'] ) {
			$send_id       = (int) $_REQUEST['send_id'];
			$newsletter_id = (int) $_REQUEST['newsletter_id'];
			$send          = get_single( 'newsletter_send', array( 'send_id', 'newsletter_id' ), array(
				$send_id,
				$newsletter_id
			) );
			if ( isset( $_POST['status'] ) && $_POST['status'] == 'delete' ) {
				if ( module_form::confirm_delete( 'newsletter_id', "Really delete this send?", self::link_queue_watch( $newsletter_id, $send_id ) ) ) {
					if ( $send && $send['send_id'] == $send_id ) {
						set_message( "Newsletter send deleted successfully" );
						update_insert( 'send_id', $send_id, 'newsletter_send', array(
							'status' => _NEWSLETTER_STATUS_DELETED,
						) );
					}
					redirect_browser( self::link_list( false ) );
				}
				unset( $_POST['status'] );
			}
			if ( ! $send['start_time'] ) {
				$_POST['start_time'] = time();
			} // hack cos sometimes it doesn't save start time? i think i fixed this bug though.
			if ( $send && $send['send_id'] == $send_id ) {
				update_insert( 'send_id', $send_id, 'newsletter_send', $_POST );

				redirect_browser( $this->link_queue_watch( $newsletter_id, $send_id ) );
			}

		} else if ( "enque_send" == $_REQUEST['_process'] ) {
			$newsletter_id = (int) $_REQUEST['newsletter_id'];
			$send_id       = (int) $_REQUEST['send_id'];

			$newsletter_data = self::get_newsletter( $newsletter_id );
			if ( $newsletter_data['newsletter_id'] != $newsletter_id ) {
				die( 'failed to enqueue send' );
			}

			// are we adding members to an existing send? or overwriting them to an existing draft / or creating a new blank send.
			if ( $send_id > 0 ) {
				$adding_members = true;
			} else {
				$adding_members = false;
			}

			$members = array();
			//todo: pass this off as a hook.
			// so we could have another module (eg: module_drupal or module_wordpress) that
			// checks which members were selected on the previous screen, and return a standard member array
			if ( class_exists( 'module_group', false ) ) {
				// find the groups we are sending to.
				$send_groups = array();
				$groups      = module_group::get_groups();
				foreach ( $groups as $group ) {
					if ( isset( $_REQUEST['group'] ) && isset( $_REQUEST['group'][ $group['group_id'] ] ) && $_REQUEST['group'][ $group['group_id'] ] == 'yes' ) {
						// we are sending to this group
						// get a list of members in this group and add them to a send table ready to go.
						$send_groups[ $group['group_id'] ] = true;
					}
				}
				// find the members for these groups
				$callback    = 'module_group::newsletter_callback';
				$error_count = 0;
				foreach ( $send_groups as $group_id => $tf ) {
					$group_members = module_group::get_members( $group_id );
					//echo '<pre>';print_r($group_members);exit;
					// give all these members a callback so the newsletter system can get more data from them.
					$group_members_with_data = array();
					foreach ( $group_members as $id => $group_member ) {
						$args = array(
							'group_id'    => $group_id,
							'owner_id'    => $group_member['owner_id'],
							'owner_table' => $group_member['owner_table'],
						);
						// run this data callback to get the data from this group member.
						$all_callback_data = self::member_data_callback( $callback, $args, false ); // false, just want the email address for now.
						if ( ! $all_callback_data ) {
							$error_count ++;
						}
						if ( is_array( $all_callback_data ) ) {
							// check if $callback_data is a multi-array - sometimes this will return more than 1 record (eg: customer = returns all contacts under that customer)
							if ( ! isset( $all_callback_data['_multi'] ) ) {
								// this is a single record. make it multi
								$all_callback_data = array( $all_callback_data );
							} else {
								unset( $all_callback_data['_multi'] );
							}
							foreach ( $all_callback_data as $callback_data ) {
								if ( ! $callback_data ) {
									continue;
								}
								if ( ! isset( $callback_data['data_callback'] ) || ! $callback_data['data_callback'] ) {
									$callback_data['data_callback'] = $callback;
								}
								if ( ! isset( $callback_data['data_args'] ) || ! $callback_data['data_args'] ) {
									$callback_data['data_args'] = json_encode( $args );
								}
								$group_members_with_data[] = $callback_data;
							}
						}
						/*$group_members[$id] = self::member_data_callback($callback,$args);
                        if(!$group_members[$id]){
                            // todo: report this problematic group member, possibly remove group member from list.
                            $error_count++;
                            unset($group_members[$id]);
                        }else{
                            // a callback on customers will return all contacts for that customer (if advanced option is set)
                            if(!isset($group_members[$id]['data_callback']) || !$group_members[$id]['data_callback']){
                                $group_members[$id]['data_callback'] = $callback;
                            }
                            if(!isset($group_members[$id]['data_args']) || !$group_members[$id]['data_args']){
                                $group_members[$id]['data_args'] = json_encode($args);
                            }
                        }*/

					}
					unset( $group_members );
					//$members = array_merge($members,$group_members);
					$members = array_merge( $members, $group_members_with_data );
				}
				if ( $error_count > 0 ) {
					set_error( 'Failed to get the information on ' . $error_count . ' group members.' );
				}
			}
			/*if(class_exists('module_company',false) && module_company::can_i('view','Company') && module_company::is_enabled()){
                // copy of the group logic above, but we're adding companies to the list.
                // find the groups we are sending to.
                $send_companys = array();
                $companys = module_company::get_companys();
                foreach($companys as $company){
                    if(isset($_REQUEST['company']) && isset($_REQUEST['company'][$company['company_id']]) && $_REQUEST['company'][$company['company_id']] == 'yes'){
                        // we are sending to this company
                        // get a list of members in this company and add them to a send table ready to go.
                        $send_companys[$company['company_id']] = true;
                    }
                }
                // find the members for these companys
                $callback = 'module_company::newsletter_callback';
                $error_count = 0;
                foreach($send_companys as $company_id => $tf){
                    $company_members = module_company::get_members($company_id);
                    //echo '<pre>';print_r($company_members);exit;
                    // give all these members a callback so the newsletter system can get more data from them.
                    $company_members_with_data = array();
                    foreach($company_members as $id => $company_member){
                        $args = array(
                             'company_id'=>$company_id,
                             'owner_id'=>$company_member['owner_id'],
                             'owner_table'=>$company_member['owner_table'],
                         );
                        // run this data callback to get the data from this company member.
                        $all_callback_data = self::member_data_callback($callback,$args);
                        if(!$all_callback_data){
                            $error_count++;
                        }
                        if(is_array($all_callback_data)){
                            // check if $callback_data is a multi-array - sometimes this will return more than 1 record (eg: customer = returns all contacts under that customer)
                            if(!isset($all_callback_data['_multi'])){
                                // this is a single record. make it multi
                                $all_callback_data = array($all_callback_data);
                            }else{
                                unset($all_callback_data['_multi']);
                            }
                            foreach($all_callback_data as $callback_data){
                                if(!$callback_data)continue;
                                if(!isset($callback_data['data_callback']) || !$callback_data['data_callback']){
                                    $callback_data['data_callback'] = $callback;
                                }
                                if(!isset($callback_data['data_args']) || !$callback_data['data_args']){
                                    $callback_data['data_args'] = json_encode($args);
                                }
                                $company_members_with_data[] = $callback_data;
                            }
                        }


                    }
                    unset($company_members);
                    //$members = array_merge($members,$company_members);
                    $members = array_merge($members,$company_members_with_data);
                }
                if($error_count>0){
                    set_error('Failed to get the information on '.$error_count.' company members.');
                }
            }*/
			//echo '<pre>';print_r($members);exit;
			// todo - load CSV formats in too. IDEA! make a new CSV module, it will work in with GROUP hook above! YESS!
			if ( ! $adding_members && ! count( $members ) ) {
				set_error( 'Please select at least 1 person to send this newsletter to' );
				redirect_browser( self::link_send( $newsletter_id ) );
			}
			if ( ! $adding_members && ! $send_id ) {
				// see if we can re-use a previously unsent send (ie: draft send)
				$drafts = get_multiple( 'newsletter_send', array(
					'newsletter_id' => $newsletter_id,
					'status'        => _NEWSLETTER_STATUS_NEW
				) );
				if ( count( $drafts ) ) {
					$draft = array_shift( $drafts );
					if ( $draft['send_id'] ) {
						$send_id = (int) $draft['send_id'];
						$sql     = "DELETE FROM `" . _DB_PREFIX . "newsletter_send_member` WHERE send_id = " . (int) $send_id;
						query( $sql );
					}
				}
			}

			if ( isset( $_REQUEST['start_time'] ) ) {
				$start_time = strtotime( input_date( $_REQUEST['start_time'], true ) );
				if ( ! $start_time ) {
					$start_time = time();
				}
			} else {
				$start_time = time();
			}
			$allow_duplicates = isset( $_REQUEST['allow_duplicates'] ) ? $_REQUEST['allow_duplicates'] : 0;

			// remove cache from send newsletter data history
			if ( isset( $newsletter_data['sends'] ) && is_array( $newsletter_data['sends'] ) ) {
				foreach ( $newsletter_data['sends'] as $previous_newsletter_data_send_id => $previous_newsletter_data_send ) {
					if ( isset( $previous_newsletter_data_send['cache'] ) ) {
						unset( $newsletter_data['sends'][ $previous_newsletter_data_send_id ]['cache'] );
					}
				}
			}
			$send_id     = self::save_send( $send_id, array(
				'newsletter_id'    => $newsletter_id,
				'status'           => _NEWSLETTER_STATUS_NEW,
				// don't send yet.
				'start_time'       => $start_time,
				'allow_duplicates' => $allow_duplicates,
				// cache a copy of the newsletter so we can pull back the old subject in histories
				'cache'            => serialize( $newsletter_data ),
				'subject'          => $newsletter_data['subject'],
			) );
			$done_member = false;
			if ( $send_id ) {
				// add the members from this send into the listing.
				// this will be a snapshop of the members details at the time this send is created.
				// todo: figure out if this will come back and bite me in the bum :)
				$failed_due_to_unsubscribe = false;
				$error_count               = 0;
				foreach ( $members as $member ) {
					//print_r($member);
					// check uniquness of this member's email in the send listing.
					// find this member by email.
					$newsletter_member_id = self::member_from_email( $member );
					if ( $newsletter_member_id > 0 ) {
						// found a member! add it to the send queue for this send.
						if ( ! $allow_duplicates ) {
							// check if this member has received this email before.
							$sql   = "SELECT * FROM `" . _DB_PREFIX . "newsletter_send_member` sm";
							$sql   .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_send` s USING (send_id) ";
							$sql   .= " WHERE sm.newsletter_member_id = " . (int) $newsletter_member_id;
							$sql   .= " AND sm.send_id IN (SELECT send_id FROM `" . _DB_PREFIX . "newsletter_send` WHERE newsletter_id = $newsletter_id)";
							$sql   .= " AND sm.send_id != " . (int) $send_id;
							$sql   .= " AND s.status != 4 "; // so we ignore deleted sends.
							$check = query( $sql );
							if ( mysqli_num_rows( $check ) ) {
								// user has received this before.
								//echo 'received before';
								mysqli_free_result( $check );
								continue;
							}
							mysqli_free_result( $check );
						}
						// check if this member is unsubscribed or marked as not receiving emails?
						if ( self::is_member_unsubscribed( $newsletter_member_id, $member ) ) { // unsubscribe checks blacklist so no need to inclde it here:  || self::email_blacklisted($member['email'])
							//echo 'unsubscribed';
							$failed_due_to_unsubscribe = true;
							continue;
						}
						$sql = "REPLACE INTO `" . _DB_PREFIX . "newsletter_send_member` SET ";
						$sql .= " send_id = " . (int) $send_id . " ";
						$sql .= ", newsletter_member_id = " . (int) $newsletter_member_id . " ";
						$sql .= ", `sent_time` = 0";
						$sql .= ", `status` = 0";
						$sql .= ", `open_time` = 0";
						$sql .= ", `bounce_time` = 0";
						query( $sql );
						//echo 'done';
						$done_member = true;
					} else {
						$error_count ++;
						if ( _DEBUG_MODE ) {
							echo 'failed to create member from email';
							print_r( $member );
							echo '<hr>';
						}
					}
				}
				if ( $error_count ) {
					set_error( 'Failed to add ' . $error_count . ' members to the queue. Possibly because they have no valid email address.' );
					if ( _DEBUG_MODE ) {
						//exit;
					}
				}
				// exit;
				if ( ! $done_member && ! $adding_members ) {
					if ( $failed_due_to_unsubscribe ) {
						set_error( 'All selected members have been unsubscribed or bounced, please select other members.' );
						// this member is added, redirect and show the errors ..
					} else {
						set_error( 'Please select at least 1 person to send this newsletter to.' );
						redirect_browser( self::link_send( $newsletter_id ) );
					}
				}
				redirect_browser( $this->link_queue( $newsletter_id, $send_id ) );
			}
		} else if ( "save_newsletter_template" == $_REQUEST['_process'] ) {

			if ( isset( $_REQUEST['butt_del'] ) ) {
				$data = self::get_newsletter_template( $_REQUEST['newsletter_template_id'] );
				if ( module_form::confirm_delete( 'newsletter_template_id', "Really delete newsletter template: " . $data['newsletter_template_name'], self::link_open_template( $_REQUEST['newsletter_template_id'] ) ) ) {
					$this->delete_newsletter_template( $_REQUEST['newsletter_template_id'] );
					set_message( "Newsletter template deleted successfully" );
					redirect_browser( self::link_open_template( false ) );
				}
			}
			$newsletter_template_id = $this->save_newsletter_template( $_REQUEST['newsletter_template_id'], $_POST );
			set_message( "Newsletter template saved successfully" );
			redirect_browser( $this->link_open_template( $newsletter_template_id ) );
		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}


	public static function member_from_email( $data, $create_new = true, $public_signup = false ) {
		$email = filter_var( strtolower( trim( $data['email'] ) ), FILTER_VALIDATE_EMAIL );
		if ( strlen( $email ) > 3 ) {
			$data['email'] = $email; // after formatting.
			// search for this member by email
			$sql        = "SELECT newsletter_member_id,`email`,`company_name`,`first_name`,`last_name` FROM `" . _DB_PREFIX . "newsletter_member` WHERE `email` LIKE '" . db_escape( $email ) . "'";
			$res        = qa1( $sql );
			$data_cache = $data;
			if ( isset( $data_cache['data_cache'] ) ) {
				unset( $data_cache['data_cache'] );
			}
			if ( isset( $data_cache['data_callback'] ) ) {
				unset( $data_cache['data_callback'] );
			}
			if ( isset( $data_cache['data_args'] ) ) {
				unset( $data_cache['data_args'] );
			}
			if ( $res && strtolower( $res['email'] ) == strtolower( $email ) ) {
				// found existing member!
				if ( ! $create_new ) {
					// just return thsi newsletter_member_id
					// don't go and update their details.
					return $res['newsletter_member_id'];
				}

				$update_data = $data;
				// todo: update their name ? create new entry if names are different? meh..
				/*$update_data = array();
                if(!$res['company_name']&&$data['company_name']){
                    $update_data['company_name']=$data['company_name'];
                }
                if(!$res['first_name']&&$data['first_name']){
                    $update_data['first_name']=$data['first_name'];
                }
                if(!$res['last_name']&&$data['last_name']){
                    $update_data['last_name']=$data['last_name'];
                }*/
				if ( $update_data ) {
					update_insert( 'newsletter_member_id', $res['newsletter_member_id'], 'newsletter_member', $update_data );
				}
				// update this one with any new data callbacks.
				if ( isset( $data['data_callback'] ) && $data['data_callback'] ) {
					update_insert( 'newsletter_member_id', $res['newsletter_member_id'], 'newsletter_member', array(
						'data_callback' => $data['data_callback'],
						'data_args'     => isset( $data['data_args'] ) ? $data['data_args'] : '',
						'data_cache'    => serialize( $data_cache ),
					) );
				}

				return $res['newsletter_member_id'];
			} else if ( $create_new ) {
				// create member with this email / data
				if ( isset( $data['name'] ) && ( ! isset( $data['first_name'] ) || ! isset( $data['last_name'] ) ) ) {
					$name_parts         = explode( " ", preg_replace( '/\s+/', ' ', $data['name'] ) );
					$data['first_name'] = array_shift( $name_parts );
					$data['last_name']  = implode( ' ', $name_parts );
				}
				$data['data_cache'] = serialize( $data_cache );

				if ( $public_signup && module_config::c( 'newsletter_double_opt_in', 1 ) ) {
					// dont subscribe them straight away.
					$data['receive_email'] = 0;
				} else {
					$data['join_date'] = date( 'Y-m-d' );
				}
				$newsletter_member_id = update_insert( 'newsletter_member_id', 'new', 'newsletter_member', $data );

				return $newsletter_member_id;
			}
		}

		return false; // no member found or able to be created.
	}

	public static function get_templates( $search = array() ) {
		return get_multiple( 'newsletter_template', $search );
	}

	public static function get_newsletter_template( $newsletter_template_id ) {
		$t              = get_single( 'newsletter_template', 'newsletter_template_id', $newsletter_template_id );
		$t['directory'] = false;
		if ( ! $t || ! isset( $t['newsletter_template_id'] ) || ! (int) $t['newsletter_template_id'] ) {
			$t['newsletter_template_name'] = '';
			$t['body']                     = '';
			$t['wizard']                   = 0;
			$t['content_url']              = '';
		} else {
			if ( is_dir( 'includes/plugin_newsletter/email_template/' . (int) $t['newsletter_template_id'] . '/' ) ) {
				$t['directory'] = 'includes/plugin_newsletter/email_template/' . (int) $t['newsletter_template_id'] . '/';
			}
			if ( strlen( basename( $t['newsletter_template_name'] ) ) > 1 && is_dir( 'includes/plugin_newsletter/email_template/' . basename( $t['newsletter_template_name'] ) . '/' ) ) {
				$t['directory'] = 'includes/plugin_newsletter/email_template/' . basename( $t['newsletter_template_name'] ) . '/';
			}

			if ( $t['directory'] && ! $t['body'] && is_file( $t['directory'] . 'template.html' ) ) {
				$t['body'] = file_get_contents( $t['directory'] . 'template.html' );
			}

		}
		$t['wizard'] = false; // disable all wizardry until it's finished.

		return $t;
	}

	public static function get_newsletters( $search = array() ) {
		// build up a custom search sql query based on the provided search fields
		$sql   = "SELECT u.*,u.newsletter_id AS id ";
		$sql   .= ' , nt.newsletter_template_name ';
		$sql   .= ' , ns.start_time AS last_sent ';
		$sql   .= ' , ns.send_id ';
		$sql   .= ' , ns.status AS send_status ';
		$from  = " FROM `" . _DB_PREFIX . "newsletter` u ";
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_template` nt USING (newsletter_template_id) ";
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_send` ns ON u.newsletter_id = ns.newsletter_id ";
		$where = " WHERE 1 AND (ns.`status` IS NULL OR ns.`status` != " . _NEWSLETTER_STATUS_DELETED . ')';
		//$where .= " AND ;
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.subject LIKE '%$str%' OR ";
			$where .= " ns.subject LIKE '%$str%' OR ";
			$where .= " u.from_name LIKE '%$str%' OR ";
			$where .= " u.from_email LIKE '%$str%' OR ";
			$where .= " u.bounce_email LIKE '%$str%' ";
			$where .= ' ) ';
		}
		if ( isset( $search['subject'] ) ) {
			$str   = db_escape( $search['subject'] );
			$where .= " AND ( ";
			$where .= " u.subject LIKE '%$str%' "; /* OR ";
			$where .= " ns.subject LIKE '%$str%'*/
			$where .= ' ) ';
		}
		if ( isset( $search['pending'] ) && $search['pending'] ) {
			$where .= " AND ns.send_id IS NOT NULL AND ( ns.status = " . _NEWSLETTER_STATUS_PAUSED;
			$where .= " OR ns.status = " . _NEWSLETTER_STATUS_PENDING . ") ";
		} else if ( isset( $search['draft'] ) && $search['draft'] ) {
			// remove deleted from drafs list.
			$where .= ' AND (ns.newsletter_id IS NULL OR ns.`status` = ' . _NEWSLETTER_STATUS_NEW . ")";
		} else {
			$where .= ' AND ns.newsletter_id IS NOT NULL AND ns.`status` != ' . _NEWSLETTER_STATUS_NEW . ' ';
		}
		foreach ( array( 'status' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` = '$str'";
			}
		}
		$group_order = '';
		if (
			isset( $search['draft'] ) && $search['draft']
			||
			isset( $search['group_results'] )
		) {
			// only show 1 newsletter in drafts
			$group_order .= ' GROUP BY u.newsletter_id ';
		}
		$group_order .= ' ORDER BY ns.start_time DESC ';
		$sql         = $sql . $from . $where . $group_order;

		//if(isset($search['draft']) && $search['draft'])echo $sql;
		return query( $sql );
		/*$result = qa($sql);
		module_security::filter_data_set("newsletter",$result);
		return $result;*/
		//		return get_multiple("newsletter",$search,"newsletter_id","fuzzy","name");

	}

	public static function get_newsletter( $newsletter_id ) {
		$newsletter_id = (int) $newsletter_id;
		if ( $newsletter_id ) {
			$newsletter = get_single( "newsletter", "newsletter_id", $newsletter_id );
			// grab any additional content.
			$newsletter['extra_content'] = get_multiple( 'newsletter_content', array( 'newsletter_id' => $newsletter_id ), 'newsletter_content_id', 'exact' );
			$sql                         = "SELECT *, send_id AS id FROM `" . _DB_PREFIX . "newsletter_send` WHERE newsletter_id = $newsletter_id AND `status` != " . _NEWSLETTER_STATUS_DELETED;
			$newsletter['sends']         = qa( $sql ); //get_multiple('newsletter_send',array('newsletter_id'=>$newsletter_id));
		}
		if ( ! $newsletter_id || ! isset( $newsletter ) || ! $newsletter ) {
			$templates           = self::get_templates();
			$template            = array_shift( $templates ); //todo - search for 'default' one.
			$default_template_id = module_config::c( 'newsletter_default_template', $template['newsletter_template_id'] );
			$newsletter          = array(
				'newsletter_id'          => 'new',
				'subject'                => '',
				'newsletter_template_id' => $default_template_id,
				'from_name'              => module_config::c( 'newsletter_default_from_name', module_config::c( 'admin_system_name' ) ),
				'from_email'             => module_config::c( 'newsletter_default_from_email', module_config::c( 'admin_email_address' ) ),
				'to_name'                => '{FIRST_NAME} {LAST_NAME}',
				'bounce_email'           => module_config::c( 'newsletter_default_bounce', module_config::c( 'admin_email_address' ) ),
				'content'                => '',
				'extra_content'          => array(),
				'sends'                  => array(),
			);
		}

		return $newsletter;
	}

	public function save_newsletter( $newsletter_id, $data ) {
		$newsletter_id = update_insert( "newsletter_id", $newsletter_id, "newsletter", $data );
		module_extra::save_extras( 'newsletter', 'newsletter_id', $newsletter_id );

		return $newsletter_id;
	}

	public function save_newsletter_template( $newsletter_template_id, $data ) {
		$newsletter_template_id = update_insert( "newsletter_template_id", $newsletter_template_id, "newsletter_template", $data );

		return $newsletter_template_id;
	}

	public function delete_newsletter_template( $newsletter_template_id ) {
		$newsletter_template_id = (int) $newsletter_template_id;
		if ( _DEMO_MODE && $newsletter_template_id == 1 ) {
			return;
		}
		$sql = "DELETE FROM " . _DB_PREFIX . "newsletter_template WHERE newsletter_template_id = '" . $newsletter_template_id . "' LIMIT 1";
		query( $sql );
		module_file::delete_files( 'newsletter_template', $newsletter_template_id );
	}

	public function delete_newsletter( $newsletter_id ) {
		$newsletter_id = (int) $newsletter_id;

		$sql = "DELETE FROM " . _DB_PREFIX . "newsletter WHERE newsletter_id = '" . $newsletter_id . "' LIMIT 1";
		query( $sql );
		$sql = "DELETE FROM `" . _DB_PREFIX . "newsletter_send` WHERE newsletter_id = '" . $newsletter_id . "' LIMIT 1";
		query( $sql );
		// todo - empty the newsletter_send_member newsletter_link and newsletter_image and newsletter_link_open tables based on the newsletter_send ids.
		$sql = "DELETE FROM `" . _DB_PREFIX . "newsletter_campaign_newsletter` WHERE newsletter_id = '" . $newsletter_id . "' LIMIT 1";
		query( $sql );
		module_file::delete_files( 'newsletter', $newsletter_id );
		module_file::delete_files( 'newsletter_files', $newsletter_id );
	}


	public static function render( $newsletter_id, $send_id = false, $newsletter_member_id = false, $render_type = 'preview' ) {
		$newsletter = self::get_newsletter( $newsletter_id );
		if ( ! $newsletter || $newsletter['newsletter_id'] != $newsletter_id ) {
			return;
		}
		$newsletter_content = $newsletter['content'];
		if ( $send_id ) {
			$send_data = self::get_send( $send_id );
			if ( $send_data && $send_data['newsletter_id'] != $newsletter_id ) {
				return;
			}
		}
		$replace = self::get_replace_fields( $newsletter_id, $send_id, $newsletter_member_id );

		$template_html = '';
		if ( $newsletter['newsletter_template_id'] ) {
			// load template in.
			$template = self::get_newsletter_template( $newsletter['newsletter_template_id'] );

			$replace['TEMPLATE_PATH'] = full_link( $template['directory'] );
			// check if there's a template file for this template
			// and pass the processing off to it's module.
			// this can replace the newsletter_content variable with something
			// more advanced like a listing from a WP database.
			/*if(!_DEMO_MODE && !defined('_DISABLE_DANGEROUS')){
                // todo - run the content url
                // execute any php code inside the template.
                ob_start();
                @eval(' ?>'.$template['body'].'<?php ');
                $template_html = ob_get_clean();
                if(!$template_html){
                    $template_html = $template['body'];
                }
            }else{*/
			$template_html = $template['body'];
			//  }
			if ( $template && $template['directory'] && is_dir( $template['directory'] ) ) {
				if ( is_file( $template['directory'] . 'render.php' ) ) {
					$return_html = false;
					include( $template['directory'] . 'render.php' );
					// do we return the html right from here?
					if ( $return_html ) {
						return $template_html;
					}
				}
			}
		}
		if ( ! preg_match( '#\{BODY\}#i', $template_html ) ) {
			$template_html .= '{BODY}';
		}
		//$template_html = str_replace('{BODY}',$newsletter_content,$template_html);
		// replace any content from our newsletter variables.
		$replace['BODY'] = $newsletter_content;


		// custom for send.

		// TODO: we build up a list of 'recipients' based on a search (or whatever) from any part of the application.
		// the list will be a CSV style file, with column headers. Each column can be linked into here as a replacement variable.
		// eg: the customer list will be "Customer Name" "Primary Contact" etc..
		// maybe tie this into the pagination function? so the table can be exported from anywhere?
		// store this listing of contacts as-is as new rows in the database. allow the user to edit this listing like a spreadsheet (ie: add new rows from another search, remove rows, edit details).
		// try to store a link back to the original record where this item came from (eg: a link to edit customer)
		// but always keep the original (eg:) email address of the customer on file, so if customer record changes the history will still show old address etc..

		// unsubscribes will add this users email to a separate black list, so that future sends will flag these members as unsubscribed.
		// admin has the option to ignore these unsubscribes if they want.

		// bounces will add this users email to a separate bounce list. after a certain number of bounces (from settings) we stop sending this user emails
		// and we notify future sends that this list of members wont be emailed due to bounces. (same warning/info listing that shows about unsubscribed users)

		// todo: end :)
		// nite dave!


		$final_newsletter_html = $template_html;
		// do HTML loop twice so we catch all conversions
		// ignore certain links for the below process
		// todo: doing this breaks link tracking on unsub/view online links.
		// option1: put every single unique link into the link table (eg, with member id on it)
		// option2: like old newsletter system, append newsletter member id onto end of all redirected links. then we can store a single "unsub" url in the db and it will redirect correctly.
		// todo - put generic url's in for unsub, view online, member details, etc..
		// todo - redirect opened links in external hook, append a new member id and new member id hash onto each redirect url.
		// todo - modify the unsub / view online / etc.. to look for these new appended memberid/memberhash variables along with existing old system ones.
		/*$special_link_replace_items = array(
            'UNSUBSCRIBE'=>self::unsubscribe_url($newsletter_id,$newsletter_member_id,$send_id),
            'VIEW_ONLINE'=>self::view_online_url($newsletter_id,$newsletter_member_id,$send_id),
            'LINK_ACCOUNT'=>'#',
            'SENDTOFRIEND'=>'#',
            'MEMBER_URL'=>'#',
        );*/
		$special_link_replace_items = array(); // only contains the member_url at the moment.
		// are we a member?
		if ( $newsletter_member_id > 0 ) {
			$member_data = self::get_newsletter_member( $newsletter_member_id );
			if ( $member_data && $member_data['member_id'] > 0 ) {
				$special_link_replace_items['MEMBER_URL'] = module_member::link_public_details( $member_data['member_id'] );
			}
		}
		for ( $x = 0; $x < 2; $x ++ ) {
			foreach ( $replace as $key => $val ) {
				if ( isset( $special_link_replace_items[ $key ] ) ) {
					continue;
				} // do these ones later.
				if ( $render_type == 'preview' && ! strlen( trim( $val ) ) ) {
					continue;
				}
				//$val = str_replace('//','////',$val); // todo- check this is correct in older version
				//$val = str_replace('\\','\\\\',$val); // todo- check this is correct in older version
				$final_newsletter_html = str_replace( '{' . strtoupper( $key ) . '}', $val, $final_newsletter_html );
			}
		}

		if ( $send_id && $render_type != 'preview' ) {
			// we process links and images for tracking purposes.
			if ( module_config::c( 'newsletter_convert_links', 1 ) ) {
				// check if there have been any converted links for this send already.
				$page_index = 1;
				foreach ( array( "href" ) as $type ) {
					preg_match_all( '/<[^>]*(' . $type . '=(["\'])([^"\']+)\2)/', $final_newsletter_html, $links );
					if ( is_array( $links[3] ) ) {
						foreach ( $links[3] as $link_id => $l ) {
							//if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !preg_match('/^mailto:/',$l)){
							if ( ! preg_match( '/^\{/', $l ) && ! preg_match( '/^#/', $l ) && ! stripos( $links[0][ $link_id ], '<link' ) &&
							     ! ( preg_match( '/^\w+:/', $l ) && ! preg_match( '/^http/', $l ) ) // catch mailto: etc.., but let http(s) slip through.
							) {
								//echo $links[0][$link_id] ."<br>";
								$search = preg_quote( $links[1][ $link_id ], "/" );
								//echo $search."<br>\n";
								$l       = preg_replace( "/[\?|&]phpsessid=([\w\d]+)/i", '', $l );
								$l       = ltrim( $l, '/' );
								$newlink = ( ( ! preg_match( '/^http/', $l ) ) ? full_link( '' ) : '' ) . $l;
								// we are sending this out, we need to store a link to this in the db
								// to record clicks etc..
								// check if this link already exists in the database for this send.
								$sql        = "SELECT * FROM `" . _DB_PREFIX . "newsletter_link` WHERE `send_id` = " . (int) $send_id . " AND `link_url` = '" . db_escape( $newlink ) . "' AND `page_index` = " . (int) $page_index;
								$existing   = qa1( $sql );
								$db_link_id = false;
								if ( $existing && $existing['link_id'] ) {
									$db_link_id = $existing['link_id'];
								} else if ( $render_type != 'preview' ) {
									// todo - don't re-create a link in the db if this send is bogus. like what if someone sends, then modifies the newsletter content, then goes back and views the stats. all the page index will be out of date. eep1!!!
									$db_link_id = update_insert( 'link_id', false, 'newsletter_link', array(
										'send_id'    => $send_id,
										'link_url'   => $newlink,
										'page_index' => $page_index,
									) );
									//$sql = "INSERT INTO `"._DB_PREFIX."newsletter_link` SET `send_id` = ".(int)$send_id.", `link_url` = '".db_escape($newlink)."'";
									//query($sql);
									//$link_id = db_insert_id();
								}
								if ( $db_link_id ) {
									$newlink = self::link_to_link( $send_id, $db_link_id, $newsletter_member_id ? $newsletter_member_id : '' );
									$replace = $type . '="' . $newlink . '"';
									//echo $replace."<br>\n";
									//preg_match('/'.$search."/",$template,$matches);print_r($matches);
									$final_newsletter_html = preg_replace( '/' . $search . '/', $replace, $final_newsletter_html, 1 );
								}
								$page_index ++;
							}
						}
					}
				}
			}
			if ( module_config::c( 'newsletter_convert_images', 1 ) ) {
				foreach ( array( "src", "background" ) as $type ) {
					preg_match_all( '/' . $type . '=(["\'])([^"\']+)\1/', $final_newsletter_html, $links );
					if ( is_array( $links[2] ) ) {
						foreach ( $links[2] as $link_id => $l ) {
							//if(!preg_match('/^\{/',$l) && !preg_match('/^#/',$l) && !preg_match('/^mailto:/',$l)){
							if ( ! preg_match( '/^\{/', $l ) && ! preg_match( '/^#/', $l ) && ! ( preg_match( '/^\w+:/', $l ) && ! preg_match( '/^http/', $l ) ) ) {
								//echo $links[0][$link_id] ."<br>";
								$search = preg_quote( $links[0][ $link_id ], "/" );
								//echo $search."<br>\n";
								$l       = preg_replace( "/[\?|&]phpsessid=([\w\d]+)/i", '', $l );
								$l       = html_entity_decode( ltrim( $l, '/' ) );
								$newlink = ( ( ! preg_match( '/^http/', $l ) ) ? full_link( '' ) : '' ) . $l;
								// we are sending this out, we need to store a link to this in the db
								// to record clicks etc..
								// check if this link already exists in the database for this send.
								$sql      = "SELECT * FROM `" . _DB_PREFIX . "newsletter_image` WHERE `send_id` = " . (int) $send_id . " AND `image_url` = '" . db_escape( $newlink ) . "'";
								$existing = qa1( $sql );
								if ( $existing && $existing['image_id'] ) {
									$image_id = $existing['image_id'];
								} else {
									$sql = "INSERT INTO `" . _DB_PREFIX . "newsletter_image` SET `send_id` = " . (int) $send_id . ", `image_url` = '" . db_escape( $newlink ) . "'";
									query( $sql );
									$image_id = db_insert_id();
								}
								$newlink = self::link_to_image( $send_id, $image_id, $newsletter_member_id ? $newsletter_member_id : '' );
								$replace = $type . '="' . $newlink . '"';
								//echo $replace."<br>\n";
								//preg_match('/'.$search."/",$template,$matches);print_r($matches);
								$final_newsletter_html = preg_replace( '/' . $search . '/', $replace, $final_newsletter_html, 1 );
							}
						}
					}
				}
			}
		}

		foreach ( $special_link_replace_items as $key => $val ) {
			if ( $render_type == 'preview' && ! strlen( trim( $val ) ) ) {
				continue;
			}
			//$val = str_replace('//','////',$val); // todo- check this is correct in older version
			//$val = str_replace('\\','\\\\',$val); // todo- check this is correct in older version
			$final_newsletter_html = str_replace( '{' . strtoupper( $key ) . '}', $val, $final_newsletter_html );
		}

		// todo - a text version of the html version.

		return $final_newsletter_html;
	}


	public static function save_send( $send_id, $data ) {
		$send_id = update_insert( 'send_id', $send_id, 'newsletter_send', $data );

		return $send_id;
	}

	/*public static function get_pending_sends(){
        $sql = "SELECT * FROM `"._DB_PREFIX."newsletter_send` ns ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter` n USING (newsletter_id)";
        $sql .= " WHERE ns.status = "._NEWSLETTER_STATUS_PAUSED;
        $sql .= " OR ns.status = "._NEWSLETTER_STATUS_PENDING;
        return qa($sql);
    }*/
	public static function get_send( $send_id ) {
		$send_id = (int) $send_id;
		if ( ! $send_id ) {
			return array();
		}
		$sql = "SELECT ns.* ";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm1.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm1 WHERE ns.send_id = sm1.send_id) AS `total_member_count`";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm2.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm2 WHERE ns.send_id = sm2.send_id AND sm2.`status` != "._NEWSLETTER_STATUS_NEW." AND sm2.`status` != "._NEWSLETTER_STATUS_PAUSED.") AS `total_sent_count`";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm3.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm3 WHERE ns.send_id = sm3.send_id AND sm3.`open_time` > 0) AS `total_open_count`";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm4.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm4 WHERE ns.send_id = sm4.send_id AND sm4.`bounce_time` > 0) AS `total_bounce_count`";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm6.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm6 WHERE ns.send_id = sm6.send_id AND sm6.`status` = "._NEWSLETTER_STATUS_FAILED.") AS `total_fail_count`";
		//$sql .= " , (SELECT COUNT(DISTINCT(sm5.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_send_member` sm5 WHERE ns.send_id = sm5.send_id AND sm5.`unsubscribe_time` > 0) AS `total_unsubscribe_count`";
		// the "GROUP BY" here didn't seem to work, so I had to use DISTINCT within the COUNT() . hmm??
		//$sql .= " , (SELECT COUNT(DISTINCT(lo.newsletter_member_id)) FROM `"._DB_PREFIX."newsletter_link_open` lo WHERE lo.send_id = ns.send_id GROUP BY lo.newsletter_member_id) AS `total_link_clicks`";
		$sql .= " FROM `" . _DB_PREFIX . "newsletter_send` ns ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_send_member` sm1 ON ns.send_id = sm1.send_id ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_send_member` sm2 ON ns.send_id = sm2.send_id AND sm2.`sent_time` > 0";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_send_member` sm3 ON ns.send_id = sm3.send_id AND sm3.`open_time` > 0 ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_send_member` sm4 ON ns.send_id = sm4.send_id AND sm4.`bounce_time` > 0";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_send_member` sm5 ON ns.send_id = sm5.send_id AND sm5.`unsubscribe_time` > 0";
		$sql .= " WHERE ns.send_id = " . (int) $send_id;
		//$sql .= " GROUP BY ns.send_id";
		//echo $sql;
		$send                       = qa1( $sql, false );
		$sql                        = "SELECT COUNT(sm1.newsletter_member_id) AS `total_member_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm1 WHERE sm1.send_id = " . (int) $send_id;
		$res                        = qa1( $sql, false );
		$send['total_member_count'] = $res['total_member_count'];

		$sql                             = "SELECT COUNT(sm2.newsletter_member_id) AS `total_sent_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm2 WHERE sm2.send_id = " . (int) $send_id . " AND sm2.`status` != " . _NEWSLETTER_STATUS_NEW . " AND sm2.`status` != " . _NEWSLETTER_STATUS_PAUSED . "";
		$res                             = qa1( $sql, false );
		$send['total_sent_count']        = $res['total_sent_count'];
		$sql                             = "SELECT COUNT(sm3.newsletter_member_id) AS `total_open_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm3 WHERE sm3.send_id = " . (int) $send_id . " AND sm3.`open_time` > 0";
		$res                             = qa1( $sql, false );
		$send['total_open_count']        = $res['total_open_count'];
		$sql                             = "SELECT COUNT(sm4.newsletter_member_id) AS `total_bounce_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm4 WHERE sm4.send_id = " . (int) $send_id . " AND sm4.`bounce_time` > 0";
		$res                             = qa1( $sql, false );
		$send['total_bounce_count']      = $res['total_bounce_count'];
		$sql                             = "SELECT COUNT(sm6.newsletter_member_id) AS `total_fail_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm6 WHERE sm6.send_id = " . (int) $send_id . " AND sm6.`status` = " . _NEWSLETTER_STATUS_FAILED . "";
		$res                             = qa1( $sql, false );
		$send['total_fail_count']        = $res['total_fail_count'];
		$sql                             = "SELECT COUNT(sm5.newsletter_member_id) AS `total_unsubscribe_count` FROM `" . _DB_PREFIX . "newsletter_send_member` sm5 WHERE sm5.send_id = " . (int) $send_id . " AND sm5.`unsubscribe_time` > 0";
		$res                             = qa1( $sql, false );
		$send['total_unsubscribe_count'] = $res['total_unsubscribe_count'];
		$sql                             = "SELECT COUNT(DISTINCT(lo.newsletter_member_id)) AS `total_link_clicks` FROM `" . _DB_PREFIX . "newsletter_link_open` lo WHERE lo.send_id = " . (int) $send_id . ""; // GROUP BY lo.newsletter_member_id";
		$res                             = qa1( $sql, false );
		$send['total_link_clicks']       = $res['total_link_clicks'];

		return $send;
		//return get_single('newsletter_send','send_id',$send_id);
	}

	public static function get_newsletter_member( $newsletter_member_id ) {
		return get_single( 'newsletter_member', 'newsletter_member_id', $newsletter_member_id );
	}

	public static function get_member( $member_id ) {
		return get_multiple( 'newsletter_member', array( 'member_id' => $member_id ), 'newsletter_member_id' );
	}

	public static function get_send_members( $send_id, $all = false, $unprocessed = false ) {
		// unprocessed flag is set from the processing cron job inside process_send()
		$sql = "SELECT s.*, m.*  ";
		$sql .= " , COUNT(lo.link_open_id) AS links_clicked ";
		//$sql .= " , lo.timestamp AS links_clicked ";
		$sql .= " , b.newsletter_blacklist_id,  b.`time` AS `unsubscribe_time2` ";
		if ( module_config::c( 'newsletter_doubleoptin_bypass', 0 ) ) {
			// this value is used on the statistics page:
			$sql .= " , b.`reason` AS blacklist_reason";
		}
		$sql .= " FROM `" . _DB_PREFIX . "newsletter_send_member` s ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_link_open` lo ON ( s.newsletter_member_id = lo.newsletter_member_id AND s.send_id = lo.send_id) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_member` m ON ( s.newsletter_member_id = m.newsletter_member_id ) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_blacklist` b ON m.email = b.email ";
		$sql .= " WHERE s.send_id = " . (int) $send_id;// . " AND lc.send_id = ".(int)$send_id;
		if ( $all ) {
			// return all, no matter what their settings./

			// used on the statistics page.
		} else {
			$sql .= " AND m.bounce_count < " . (int) module_config::c( 'newsletter_bounce_threshold', 3 );
			$sql .= " AND m.email != ''";
			$sql .= " AND (m.unsubscribe_date IS NULL OR m.unsubscribe_date = '0000-00-00')";
			if ( module_config::c( 'newsletter_doubleoptin_bypass', 0 ) ) {
				$sql .= " AND ( b.email IS NULL OR ( b.email IS NOT NULL AND b.reason = 'doubleoptin' ) ) ";
				// todo: work out what to do with m.receive_email
			} else {
				$sql .= " AND m.receive_email = 1";
				$sql .= " AND b.email IS NULL";
			}
		}
		$sql .= " GROUP BY s.newsletter_member_id ";

		//echo $sql;
		return query( $sql );
	}

	public static function get_send_member( $send_id, $newsletter_member_id ) {
		// unprocessed flag is set from the processing cron job inside process_send()
		$sql = "SELECT s.*, m.*  ";
		$sql .= " , COUNT(lo.link_open_id) AS links_clicked ";
		//$sql .= " , lo.timestamp AS links_clicked ";
		$sql .= " , b.newsletter_blacklist_id,  b.`time` AS `unsubscribe_time2` ";
		// this value is used on the statistics page:
		$sql .= " , b.`reason` AS blacklist_reason";
		$sql .= " FROM `" . _DB_PREFIX . "newsletter_send_member` s ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_link_open` lo ON ( s.newsletter_member_id = lo.newsletter_member_id AND s.send_id = lo.send_id) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_member` m ON ( s.newsletter_member_id = m.newsletter_member_id ) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_blacklist` b ON m.email = b.email ";
		$sql .= " WHERE s.send_id = " . (int) $send_id . " AND s.newsletter_member_id = " . (int) $newsletter_member_id;
		$sql .= " GROUP BY s.newsletter_member_id ";

		return qa1( $sql );
	}

	public static function get_member_sends( $member_id ) {
		$sql = "SELECT *, s.subject AS subject FROM `" . _DB_PREFIX . "newsletter_member` m ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_send_member` sm USING (newsletter_member_id) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_send` s USING (send_id) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter` n USING (newsletter_id) ";
		$sql .= " WHERE m.member_id = " . (int) $member_id;
		$sql .= " AND s.status != 4 "; // deleted sends.

		return query( $sql );
	}

	public static function get_problem_members( $send_id ) {
		$sql = "SELECT s.*, m.* ";
		$sql .= " , b.newsletter_blacklist_id, b.`time` AS `unsubscribe_time2` ";
		$sql .= " FROM `" . _DB_PREFIX . "newsletter_send_member`s ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_member` m USING (newsletter_member_id) ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_blacklist` b ON m.email = b.email ";
		$sql .= " WHERE s.send_id = " . (int) $send_id;
		$sql .= " AND ( m.bounce_count >= " . (int) module_config::c( 'newsletter_bounce_threshold', 3 );
		$sql .= " OR m.receive_email = 0";
		$sql .= " OR m.email = ''";
		$sql .= " OR (m.unsubscribe_date IS NOT NULL AND m.unsubscribe_date != '0000-00-00') ";
		$sql .= " OR ( b.newsletter_blacklist_id IS NOT NULL ";
		if ( module_config::c( 'newsletter_doubleoptin_bypass', 0 ) ) {
			$sql .= " AND  b.reason != 'doubleoptin' ";
		}
		$sql .= " )";
		$sql .= " )";
		$sql .= " GROUP BY s.newsletter_member_id ";

		return query( $sql );
	}

	public function duplicate_newsetter( $newsletter_id ) {
		$newsletter_data = get_single( 'newsletter', 'newsletter_id', $newsletter_id );
		unset( $newsletter_data['newsletter_id'] );
		$new_newsletter_id = update_insert( 'newsletter_id', 'new', 'newsletter', $newsletter_data );
		// duplicate the images and attachments (these are in the file plugin)
		// images first
		$files = module_file::get_files( array( 'owner_table' => 'newsletter', 'owner_id' => $newsletter_id, ) );
		foreach ( $files as $file_data ) {
			if ( is_file( $file_data['file_path'] ) ) {
				$new_file_path = 'includes/plugin_file/upload/' . md5( time() . $file_data['file_name'] );
				copy( $file_data['file_path'], $new_file_path );
				$file_data['file_path'] = $new_file_path;
			}
			unset( $file_data['file_id'] );
			unset( $file_data['date_updated'] );
			$file_data['owner_id'] = $new_newsletter_id;
			update_insert( 'file_id', 'new', 'file', $file_data );
		}
		// now attachemtns
		$files = module_file::get_files( array( 'owner_table' => 'newsletter_files', 'owner_id' => $newsletter_id, ) );
		foreach ( $files as $file_data ) {
			if ( is_file( $file_data['file_path'] ) ) {
				$new_file_path = 'includes/plugin_file/upload/' . md5( time() . $file_data['file_name'] );
				copy( $file_data['file_path'], $new_file_path );
				$file_data['file_path'] = $new_file_path;
			}
			unset( $file_data['file_id'] );
			unset( $file_data['date_updated'] );
			$file_data['owner_id'] = $new_newsletter_id;
			update_insert( 'file_id', 'new', 'file', $file_data );
		}

		return $new_newsletter_id;
	}

	public static function send_preview( $newsletter_id, $email_address ) {
		$newsletter = self::get_newsletter( $newsletter_id );
		$email      = module_email::new_email();
		$fields     = self::get_replace_fields( $newsletter_id );
		foreach ( $fields as $key => $val ) {
			if ( ! strlen( trim( $val ) ) ) {
				$fields[ $key ] = '{' . $key . '}';
			}
		}
		$email->replace_values = $fields;
		$email->set_bounce_address( $newsletter['bounce_email'] );
		$email->set_from_manual( $newsletter['from_email'], $newsletter['from_name'] );
		$email->set_to_manual( $email_address, $newsletter['to_name'] );
		$email->set_subject( $newsletter['subject'] );
		// do we send images inline?
		$html = self::render( $newsletter_id, false, false, 'preview' );
		$email->set_html( $html );

		// do we attach anything else?
		$files = module_file::get_files( array( 'owner_table' => 'newsletter_files', 'owner_id' => $newsletter_id, ) );
		foreach ( $files as $file_data ) {
			if ( is_file( $file_data['file_path'] ) ) {
				$email->AddAttachment( $file_data['file_path'], $file_data['file_name'] );
			}
		}
		if ( $email->send() ) {
			set_message( 'Email preview sent successfully' );

			return true;
		} else {
			set_error( 'Failed to send email preview: ' . $email->error_text );

			return false;
		}
	}


	static $failed_retries = array();

	/**
	 *
	 * This is called from newsletter_queue_manual.php and the cron job (todo)
	 * This will pick the next email in the queue to send and send it.
	 * If there are any problems it will record the problem and return the error to the calling function for display.
	 * TODO: make this support more than one at a time in send_limit (put newsletter_member_id into an array and return that)
	 *
	 * @static
	 *
	 * @param      $newsletter_id
	 * @param      $send_id
	 * @param int  $send_limit
	 * @param bool $retry_failures
	 *
	 * @return array
	 */
	public static function process_send( $newsletter_id, $send_id, $send_limit = 1, $retry_failures = false, $retry_pending = false ) {
		$newsletter_id = (int) $newsletter_id;
		$send_id       = (int) $send_id;
		// todo, make the result friendly for multiple members.
		$result     = array(
			'send_count'   => 0,
			'send_members' => array(),
			/*'status'=>false,
            'email'=>'',
            'error'=>'',
            'newsletter_member_id'=>0,*/
		);
		$send_count = 0;
		// we pick the next member off the list in this send and send it out.
		// check the status of this send is still ok to send.
		$sql = "SELECT `send_id`,`status` FROM `" . _DB_PREFIX . "newsletter_send` WHERE `newsletter_id` = $newsletter_id AND `send_id` = $send_id";
		// $sql .= " AND `status` = " . _NEWSLETTER_STATUS_PENDING ;
		$status_check = qa1( $sql, false );
		if ( $status_check['send_id'] == $send_id ) { //$status_check['status']==_NEWSLETTER_STATUS_PENDING &&
			// we have a go for sending to the next member!
			$sql = "SELECT sm.*, m.* FROM `" . _DB_PREFIX . "newsletter_send_member` sm ";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "newsletter_member` m ON sm.newsletter_member_id = m.newsletter_member_id ";
			// update! we assume all members in the newsletter_send_member table are legit and not unsubscribed.
			/// this is because we're now removing all unsubscribed members from the mailing list before sending starts.
			//$sql .= " LEFT JOIN `"._DB_PREFIX."newsletter_blacklist` b ON m.email = b.email ";
			$sql .= " WHERE sm.`send_id` = $send_id ";
			//sql .= " AND b.newsletter_blacklist_id IS NULL ";

			// copied from get send members:
			//$sql .= " AND m.bounce_count < ".(int)module_config::c('newsletter_bounce_threshold',3);
			//$sql .= " AND m.receive_email = 1";
			//$sql .= " AND m.email != ''";
			//$sql .= " AND (m.unsubscribe_date IS NULL OR m.unsubscribe_date = '0000-00-00')";
			//$sql .= " AND b.email IS NULL";
			// todo - instead of this sql query pass this back to our central "get send members" query

			if ( $retry_failures ) {
				$sql .= " AND sm.`status` = " . _NEWSLETTER_STATUS_FAILED . " AND sm.newsletter_member_id > 0";
			} else if ( $retry_pending ) {
				$sql .= " AND sm.`status` = " . _NEWSLETTER_STATUS_PENDING . " AND sm.newsletter_member_id > 0";
			} else {
				$sql .= " AND (sm.`status` = " . _NEWSLETTER_STATUS_NEW . " OR sm.`status` = " . _NEWSLETTER_STATUS_PAUSED . ") AND sm.newsletter_member_id > 0";
			}
			$sql              .= " GROUP BY sm.newsletter_member_id ";
			$send_member_list = query( $sql );
			if ( mysqli_num_rows( $send_member_list ) > 0 ) {
				while ( $send_member = mysqli_fetch_assoc( $send_member_list ) ) {
					if ( $send_count >= $send_limit ) {
						break;
					}

					// have we already tried sending this member in this loop?
					if ( ( $retry_failures || $retry_pending ) && isset( self::$failed_retries[ $send_member['newsletter_member_id'] ] ) ) {
						continue;
					}
					self::$failed_retries[ $send_member['newsletter_member_id'] ] = true;

					// a quick lock checking on the member to ensure we don't send it twice
					// first we check the status of the member.
					if ( $retry_failures || $retry_pending ) {

					} else {
						// added this in just to be sure we're not sending out duplicates...
						$sql             = "SELECT * FROM `" . _DB_PREFIX . "newsletter_send_member` WHERE (`status` = " . _NEWSLETTER_STATUS_NEW . " OR `status` = " . _NEWSLETTER_STATUS_PAUSED . ") AND `send_id` = $send_id AND newsletter_member_id = '" . (int) $send_member['newsletter_member_id'] . "'";
						$duplicate_check = qa1( $sql );
						if ( empty( $duplicate_check ) ) {
							// cant find this member, already sent this one then!
							continue;
						}
					}
					// and we do another check just to make sure we're not sending out duplicates again:
					$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET `status` = " . _NEWSLETTER_STATUS_PENDING . " WHERE `send_id` = $send_id AND newsletter_member_id = '" . (int) $send_member['newsletter_member_id'] . "' AND `status` != " . _NEWSLETTER_STATUS_PENDING . "";
					query( $sql );
					if ( $retry_pending || mysqli_affected_rows( module_db::$dbcnx ) ) {
						// we haev a lock on this member! ok to proceed.

						$member_result['newsletter_member_id'] = (int) $send_member['newsletter_member_id'];
						// check this member hasn't unsubscribed or any other issues with it...

						$newsletter = self::get_newsletter( $newsletter_id );

						// both these two do their own "replacey" things. same inside email.
						$fields = self::get_replace_fields( $newsletter_id, $send_id, $send_member['newsletter_member_id'], true );
						$html   = self::render( $newsletter_id, $send_id, $send_member['newsletter_member_id'], 'real' );


						// make any changes here in the send_preview method as well.
						$email                 = module_email::new_email();
						$email->replace_values = $fields;
						$email->set_bounce_address( $newsletter['bounce_email'] );
						$email->set_from_manual( $newsletter['from_email'], $newsletter['from_name'] );
						$email->set_to_manual( $send_member['email'], $newsletter['to_name'] );
						$email->set_subject( $newsletter['subject'] );
						// do we send images inline?
						$email->set_html( $html );
						$email->message_id = self::generate_bounce_message_id( $newsletter_id, $send_id, $send_member['newsletter_member_id'] );

						// do we attach anything else?
						$files = module_file::get_files( array(
							'owner_table' => 'newsletter_files',
							'owner_id'    => $newsletter_id,
						) );
						foreach ( $files as $file_data ) {
							if ( is_file( $file_data['file_path'] ) ) {
								$email->AddAttachment( $file_data['file_path'], $file_data['file_name'] );
							}
						}
						if ( $email->send() ) {
							// it worked successfully!!
						}
						$member_result['error'] = $email->error_text;
						$member_result['email'] = $send_member['email'];

						switch ( $email->status ) {
							case _MAIL_STATUS_OVER_QUOTA:
								// over quota! pause this send until another time.
								$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET `status` = " . _NEWSLETTER_STATUS_PAUSED . ", `sent_time` = 0, `bounce_time` = 0 WHERE `send_id` = $send_id AND newsletter_member_id = '" . (int) $send_member['newsletter_member_id'] . "'";
								query( $sql );
								break;
							case _MAIL_STATUS_FAILED:
								//self::member_email_bounced($send_member['newsletter_member_id'],$send_id);
								$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET `status` = " . _NEWSLETTER_STATUS_FAILED . ", `bounce_time` = '0' WHERE `send_id` = $send_id AND newsletter_member_id = '" . (int) $send_member['newsletter_member_id'] . "'";
								query( $sql );
								break;
							case _MAIL_STATUS_SENT:
							default:
								// update its status to sent.
								// do this as a default catch so we don't end up sending duplicates for some weird mail class error.
								$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET `status` = " . _NEWSLETTER_STATUS_SENT . ", `sent_time` = '" . time() . "', `bounce_time` = 0 WHERE `send_id` = $send_id AND newsletter_member_id = '" . (int) $send_member['newsletter_member_id'] . "'";
								query( $sql );
								break;
						}
						$member_result['status'] = $email->status; // so the calling script can handle whatever as well.

						$result['send_members'][ (int) $send_member['newsletter_member_id'] ] = $member_result;
						$send_count ++;

					} else {
						// didn't get a lock on that member for some reason.
						// probably because the cron job is runing in the background on this same send.

					}
				}
			} else {
				// no more members left?
				// update this send to completd.
				$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send` SET `status` = " . _NEWSLETTER_STATUS_SENT . ", finish_time = '" . time() . "' WHERE `send_id` = $send_id";
				query( $sql );
			}
		}
		$result['send_count'] = $send_count;

		return $result;
	}

	private static function member_data_callback( $callback, $args, $all_details = true ) {
		if ( is_callable( $callback ) ) {
			$args['basic'] = ! $all_details;

			return call_user_func( $callback, $args );
		}

		return array();
	}

	public static function update_member_data_for_send( $send_id ) {
		if ( ! isset( $_SESSION['_updated_member_data_for_send'] ) ) {
			$_SESSION['_updated_member_data_for_send'] = array();
		}
		if ( isset( $_SESSION['_updated_member_data_for_send'][ $send_id ] ) && $_SESSION['_updated_member_data_for_send'][ $send_id ] + 30 > time() ) {
			// stops it running too quickly - every 30 seconds should be great.
			return;
		}
		$_SESSION['_updated_member_data_for_send'][ $send_id ] = time();
		$send_members                                          = module_newsletter::get_send_members( $send_id );
		while ( $send_member = mysqli_fetch_assoc( $send_members ) ) {
			if ( isset( $send_member['data_callback'] ) && strlen( $send_member['data_callback'] ) > 1 && isset( $send_member['data_cache'] ) && strlen( $send_member['data_cache'] ) > 1 ) {
				// check if the cache matches our stored data
				$new_cache = self::member_data_callback( $send_member['data_callback'], json_decode( $send_member['data_args'], true ), false );
				if ( $new_cache ) {
					$new_cache = serialize( $new_cache );

					if ( $new_cache != $send_member['data_cache'] ) {
						update_insert( 'newsletter_member_id', $send_member['newsletter_member_id'], 'newsletter_member', array(
							'data_cache' => $new_cache,
						) );
					}
				}
			}
		}
	}

	public static function update_individual_member_data( $send_id, $newsletter_member_id, $send_member = false ) {
		$send_member = $send_member ? $send_member : self::get_send_member( $send_id, $newsletter_member_id );
		if ( is_array( $send_member ) && isset( $send_member['data_callback'] ) && strlen( $send_member['data_callback'] ) > 1 && isset( $send_member['data_cache'] ) && strlen( $send_member['data_cache'] ) > 1 ) {
			// check if the cache matches our stored data
			$new_member_data = self::member_data_callback( $send_member['data_callback'], json_decode( $send_member['data_args'], true ), true );
			if ( $new_member_data ) {
				$new_cache = serialize( $new_member_data );
				if ( $new_cache != $send_member['data_cache'] ) {
					$send_member               = array_merge( $send_member, $new_member_data );
					$send_member['data_cache'] = $new_cache;
					update_insert( 'newsletter_member_id', $send_member['newsletter_member_id'], 'newsletter_member', array(
						'data_cache' => $new_cache,
					) );
				}
			}
		}

		return $send_member;
	}

	/*
     * This is called when the user clicks the final "Send" button on the newsletter.
     * Somtimes users who are unsubscribed make their way through the system
     *
     */
	public static function remove_unsubscribed_members_from_send( $send_id ) {
		//$send_members = module_newsletter::get_send_members($send_id,true);
		$send_members = self::get_problem_members( $send_id );
		while ( $send_member = mysqli_fetch_assoc( $send_members ) ) {
			//if(self::is_member_unsubscribed($send_member['newsletter_member_id'])){
			$sql = "DELETE FROM `" . _DB_PREFIX . "newsletter_send_member` WHERE send_id = '" . (int) $send_id . "' AND newsletter_member_id = " . (int) $send_member['newsletter_member_id'] . " LIMIT 1";
			query( $sql );
			//}
		}
	}


	public static function get_replace_fields( $newsletter_id = false, $send_id = false, $newsletter_member_id = false, $update_member_details = false ) {
		$newsletter = self::get_newsletter( $newsletter_id );
		$send_data  = self::get_send( $send_id );
		if ( $send_data && $newsletter && $send_data['newsletter_id'] != $newsletter['newsletter_id'] ) {
			return array();
		}

		$send_time = isset( $send_data['start_time'] ) ? $send_data['start_time'] : ( isset( $newsletter['date_updated'] ) ? strtotime( $newsletter['date_updated'] ) : time() );
		$replace   = array();

		$replace['COMPANY_NAME'] = '';
		$replace['FIRST_NAME']   = '';
		$replace['LAST_NAME']    = '';
		$replace['EMAIL']        = '';

		$replace['FROM_EMAIL'] = $newsletter['from_email'];
		$replace['FROM_NAME']  = $newsletter['from_name'];

		$replace['DAY']   = date( 'd', $send_time );
		$replace['MONTH'] = date( 'm', $send_time );
		$replace['YEAR']  = date( 'Y', $send_time );
		$replace['DATE']  = print_date( $send_time );
		//urls
		$replace['UNSUBSCRIBE']  = self::unsubscribe_url( $newsletter_id );
		$replace['VIEW_ONLINE']  = self::view_online_url( $newsletter_id );
		$replace['LINK_ACCOUNT'] = '#';
		$replace['SENDTOFRIEND'] = '#';
		$replace['MEMBER_URL']   = '#';


		$replace['SUBJECT'] = ( $send_data && isset( $send_data['subject'] ) && $send_data['subject'] ) ? $send_data['subject'] : $newsletter['subject'];
		// these will be overridden when the send goes out
		$use_newsletter_member = array();

		if ( $send_id && $send_data ) {

			// todo: BIG TODO: cache this loop, it will run every time a newsletter is mailed during the render query. eep!
			$extra_fields = $cache_data = array();
			if ( isset( $send_data['cache'] ) ) {
				$cache_data = @unserialize( $send_data['cache'] );
				if ( $cache_data && isset( $cache_data['extra_fields'] ) ) {
					$extra_fields = $cache_data['extra_fields'];
				}
			}
			if ( ! $extra_fields ) {
				$send_members = module_newsletter::get_send_members( $send_id );
				// what other fields are we pulling in here?
				// hunt through the recipient listing and find the extra fields.
				$extra_fields = array();
				while ( $send_member = mysqli_fetch_assoc( $send_members ) ) {
					$cache = array();
					if ( isset( $send_member['data_cache'] ) && strlen( $send_member['data_cache'] ) > 1 ) {
						$cache = unserialize( $send_member['data_cache'] );
						if ( $cache ) {
							// we have extra fields! woo!
							foreach ( $cache as $key => $val ) {
								if ( strpos( $key, '_id' ) ) {
									continue;
								} // skip ids for now.
								$extra_fields[ $key ] = true;
							}
						}
					}
					if ( $newsletter_member_id && $send_member['newsletter_member_id'] == $newsletter_member_id ) {
						$use_newsletter_member = $send_member;
					}
				}
				//ksort($extra_fields);
				mysqli_free_result( $send_members );
				//module_cache::put('newsletter','send_extra_fields_'.$send_id,$extra_fields,240);
				if ( ! is_array( $cache_data ) ) {
					$cache_data = array();
				}
				$cache_data['extra_fields'] = $extra_fields;
				self::save_send( $send_id, array(
					'cache' => serialize( $cache_data ),
				) );

			} else if ( $newsletter_member_id ) {
				$use_newsletter_member = self::get_send_member( $send_id, $newsletter_member_id );
			}
			if ( is_array( $extra_fields ) ) {
				foreach ( $extra_fields as $extra_field => $tf ) {
					if ( $extra_field[0] == '_' ) {
						continue;
					}
					$replace[ strtoupper( $extra_field ) ] = '';
				}
			}
			if ( $use_newsletter_member ) {

				if ( $update_member_details ) {
					// grab the latest member details here
					// this is because in the initial loop we only grab their email/basic details to speed up the queing process.
					$new_data = self::update_individual_member_data( $send_id, $newsletter_member_id, $use_newsletter_member );
					if ( is_array( $new_data ) ) {
						$use_newsletter_member = array_merge( $use_newsletter_member, $new_data );
					}
				}
				// we are using this members data in this array.
				// insert its values into the replace values.
				foreach ( $use_newsletter_member as $key => $val ) {
					if ( is_array( $val ) ) {
						continue;
					}
					//if(isset($replace[strtoupper($key)])){
					$replace[ strtoupper( $key ) ] = $val;
					//}
				}
				if ( isset( $use_newsletter_member['data_cache'] ) && strlen( $use_newsletter_member['data_cache'] ) > 1 ) {
					$cache = unserialize( $use_newsletter_member['data_cache'] );
					if ( is_array( $cache ) ) {
						foreach ( $cache as $key => $val ) {
							if ( is_array( $val ) ) {
								continue;
							}
							//if(isset($replace[strtoupper($key)])){
							$replace[ strtoupper( $key ) ] = $val;
							//}
						}
					}
				}
			}
		}


		return $replace;
	}

	public static function subscribe_member_double_optin_done( $email ) {
		module_newsletter::unsubscribe_member_via_email( $email, 'doubleoptin', true );

		// mark this member as
		$newsletter_member_id = self::member_from_email( array( 'email' => $email ), false );
		if ( $newsletter_member_id ) {
			$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET bounce_count = 0, receive_email = 1, unsubscribe_send_id = 0 WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
			query( $sql );
		}

		// redirect on double opt in subscription confirmation.
		// is the newsletter module giving us a subscription redirection?
		if ( module_config::c( 'newsletter_subscribe_redirect_double', '' ) ) {
			redirect_browser( module_config::c( 'newsletter_subscribe_redirect_double', '' ) );
		}
		// or display a message.

		$template             = module_template::get_template_by_key( 'member_subscription_confirmed' );
		$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
		echo $template->render( 'pretty_html' );

	}

	public static function subscribe_member( $email_address, $newsletter_member_id = false ) {

		// we're subscribing this email address.

		// check they're not already subscribed.
		$already_subscribed = false;
		if ( $newsletter_member_id ) {
			$newsletter_member = get_single( 'newsletter_member', 'newsletter_member_id', $newsletter_member_id );
			if ( $newsletter_member && $newsletter_member['join_date'] && $newsletter_member['join_date'] != '0000-00-00' ) {
				// they're already subscribed.
				$already_subscribed = true;
			}
		}

		// send double opt in?
		if ( ! $already_subscribed && module_config::c( 'newsletter_double_opt_in', 1 ) ) {

			// add this new member to the blacklist, this will be removed when they confirm.
			module_newsletter::unsubscribe_member_via_email( $email_address, 'doubleoptin' );

			$template = module_template::get_template_by_key( 'member_subscription_double_optin' );
			$template->assign_values( array(
				'email' => $email_address,
				'link'  => self::double_optin_confirmation_link( $email_address ),
			) );
			$html = $template->render( 'html' );

			$email                 = module_email::new_email();
			$email->replace_values = array(
				'email' => $email_address,
				'link'  => self::double_optin_confirmation_link( $email_address ),
			);
			$email->set_to_manual( $email_address );
			$email->set_from_manual( module_config::c( 'newsletter_default_from_email', module_config::c( 'admin_email_address' ) ), module_config::c( 'newsletter_default_from_name', module_config::c( 'admin_system_name' ) ) );
			$email->set_subject( module_config::c( 'newsletter_double_opt_in_subject', 'Please confirm your newsletter subscription' ) );
			// do we send images inline?
			$email->set_html( $html );

			if ( $email->send() ) {
				// it worked successfully!!
				return true;
			} else {
				return false;
			}
		} else {

			// remove them from a blacklist and remove any bounce counters that could prevent us sending them emails.
			module_newsletter::unsubscribe_member_via_email( $email_address, 'new_subscribe', true );
			if ( $newsletter_member_id ) {
				$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET bounce_count = 0, receive_email = 1, unsubscribe_send_id = 0 WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
				query( $sql );
				if ( ! $already_subscribed ) {
					$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET join_date = NOW() WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
					query( $sql );
				}
			}

			return true; // dont need to do anything.
		}
	}

	/** unsubscribing  */

	public static function is_member_unsubscribed( $newsletter_member_id, $member = false ) {
		if ( ! $member ) {
			$member = self::get_newsletter_member( $newsletter_member_id );
		}
		// check if this email address is blacklisted first.
		if ( strlen( $member['email'] ) ) {
			$res = self::email_blacklisted( $member['email'] );
			if ( $res ) {
				// we have a blacklisted result.
				if ( module_config::c( 'newsletter_doubleoptin_bypass', 0 ) && $res['reason'] == 'doubleoptin' ) {
					// bypass this double opt in situation
				} else {
					$res['blacklist'] = true;

					return $res;
				}
			}
		}
		if ( isset( $member['unsubscribe_send_id'] ) && $member['unsubscribe_send_id'] ) {
			return array(
				'reason'              => 'unsubscribe',
				'unsubscribe_send_id' => $member['unsubscribe_send_id'],
				'time'                => strtotime( $member['unsubscribe_date'] ),
			);
		} else if ( isset( $member['receive_email'] ) && ! $member['receive_email'] && ! module_config::c( 'newsletter_doubleoptin_bypass', 0 ) ) {
			return array(
				'reason' => 'no_email',
				'time'   => strtotime( $member['unsubscribe_date'] ),
			);
		}

		return false;
	}

	public static function email_blacklisted( $email ) {
		$email = trim( strtolower( $email ) );
		if ( ! $email ) {
			return true;
		}
		$sql = "SELECT * FROM `" . _DB_PREFIX . "newsletter_blacklist` b";
		$sql .= " WHERE b.email LIKE '" . db_escape( $email ) . "'";

		return qa1( $sql );
	}

	public static function unsubscribe_member_via_email( $email, $reason = 'unsubscribe', $remove = false ) {
		// add thsi email to a blacklist.
		// or remove them if they re-subscribe.
		$email = strtolower( trim( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) );
		if ( strlen( $email ) > 3 ) {
			if ( $remove ) {
				// remove all occurances of this email address.
				delete_from_db( 'newsletter_blacklist', 'email', $email );
			} else if ( ! self::email_blacklisted( $email ) ) {
				// check if it already exists ^^
				// add to backlist.
				update_insert( 'newsletter_blacklist_id', 'new', 'newsletter_blacklist', array(
					'email'  => $email,
					'time'   => time(),
					'reason' => $reason,
				) );
			}

			return true;
		}

		return false;

	}

	public static function unsubscribe_member( $newsletter_id, $newsletter_member_id = 0, $send_id = 0 ) {
		$newsletter_member = self::get_newsletter_member( $newsletter_member_id );
		if ( $newsletter_member && $newsletter_member['email'] ) {
			self::unsubscribe_member_via_email( $newsletter_member['email'] );
		}
		$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET unsubscribe_date = '" . date( 'Y-m-d' ) . "', unsubscribe_send_id = " . (int) $send_id . " WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
		query( $sql );
		$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET unsubscribe_time = '" . time() . "' WHERE newsletter_member_id = " . (int) $newsletter_member_id . " AND send_id = '" . (int) $send_id . "' LIMIT 1";
		query( $sql );
		// also list this as opened.
		self::member_opened_newsletter( $send_id, $newsletter_member_id, 'unsubscribe' );

	}

	/**
	 * Called when a member opens a newsletter. Either by unsubscribing, viewing an image or clicking a link.
	 * @static
	 *
	 * @param        $send_id
	 * @param        $newsletter_member_id
	 * @param string $open_type
	 * @param int    $open_id
	 */
	public static function member_opened_newsletter( $send_id, $newsletter_member_id, $open_type = false, $open_id = false ) {

		// we also clear any bounce_count on the newsletter_member
		// this is the only way we can reset the bounce count reliably.
		$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET bounce_count = 0 WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
		query( $sql );

		$sql = "UPDATE `" . _DB_PREFIX . "newsletter_send_member` SET open_time = '" . time() . "' WHERE newsletter_member_id = " . (int) $newsletter_member_id . " AND send_id = '" . (int) $send_id . "' AND open_time = 0 LIMIT 1";
		query( $sql );
		switch ( $open_type ) {
			case 'link':
				if ( $open_id > 0 ) {
					update_insert( 'link_open_id', 'new', 'newsletter_link_open', array(
						'link_id'              => $open_id,
						'newsletter_member_id' => $newsletter_member_id,
						'send_id'              => $send_id,
						'timestamp'            => time(),
					) );
				}
				break;
			case 'image':
				if ( $open_id > 0 ) {
					// we're not tracking which images have been opened.
					// but we could do this down the track to see what percentage of members have images enabled.
				}
				break;
		}
	}


	/** external stuff */
	public static function double_optin_confirmation_link( $email, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for newsletter confirmation ' . _UCM_SECRET . ' ' . $email );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=newsletter&h=doubleoptin&e=' . $email . '&hash=' . self::double_optin_confirmation_link( $email, true ) );
	}

	public static function unsubscribe_url( $newsletter_id = 0, $newsletter_member_id = 0, $unsubscribe_send_id = 0, $h = false ) {
		if ( ! $newsletter_member_id ) {
			return full_link( _EXTERNAL_TUNNEL . '?m=newsletter&h=unsubscribe&n=' . $newsletter_id . '&' . _MEMBER_HASH_URL_REDIRECT_BITS );
		}
		if ( $h ) {
			return md5( 's3cret7hash for newsletter unsub! ' . _UCM_SECRET . ' ' . $newsletter_id . '-' . $newsletter_member_id . '=' . $unsubscribe_send_id );
		}

		// note: nm= is parsed out in our new statstics_link_clicks.php page
		return full_link( _EXTERNAL_TUNNEL . '?m=newsletter&h=unsubscribe&n=' . $newsletter_id . '&nm=' . $newsletter_member_id . '&s=' . $unsubscribe_send_id . '&hash=' . self::unsubscribe_url( $newsletter_id, $newsletter_member_id, $unsubscribe_send_id, true ) );
	}

	public static function view_online_url( $newsletter_id, $newsletter_member_id = 0, $send_id = 0, $h = false ) {
		if ( ! $newsletter_member_id ) {
			if ( $h ) {
				return md5( 's3cret7hash for newsletter view online ' . _UCM_SECRET . ' ' . $newsletter_id . '-' );
			}

			return full_link( _EXTERNAL_TUNNEL . '?m=newsletter&h=vo&n=' . $newsletter_id . '&voh=' . self::view_online_url( $newsletter_id, 0, 0, true ) . '&' . _MEMBER_HASH_URL_REDIRECT_BITS );
		}
		if ( $h ) {
			return md5( 's3cret7hash for newsletter view online ' . _UCM_SECRET . ' ' . $newsletter_id . '-' . $newsletter_member_id . '=' . $send_id );
		}

		// note: nm= is parsed out in our new statstics_link_clicks.php page
		return full_link( _EXTERNAL_TUNNEL . '?m=newsletter&h=vo&n=' . $newsletter_id . '&nm=' . $newsletter_member_id . '&s=' . $send_id . '&hash=' . self::view_online_url( $newsletter_id, $newsletter_member_id, $send_id, true ) );
	}

	public static function link_to_link( $send_id, $link_id, $newsletter_member_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for newsletter link ' . $newsletter_member_id . ' ' . _UCM_SECRET . ' ' . $send_id . ' ' . $link_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.newsletter/h.l/s.' . $send_id . '/l.' . $link_id . '/a.' . $newsletter_member_id . '/hash.' . self::link_to_link( $send_id, $link_id, $newsletter_member_id, true ) . '?plight' );
	}

	public static function link_to_image( $send_id, $image_id, $newsletter_member_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for newsletter image ' . $newsletter_member_id . ' ' . _UCM_SECRET . ' ' . $send_id . ' ' . $image_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.newsletter/h.i/s.' . $send_id . '/i.' . $image_id . '/a.' . $newsletter_member_id . '/hash.' . self::link_to_image( $send_id, $image_id, $newsletter_member_id, true ) . '?plight' );
	}

	public static function newsletter_redirect_hash( $newsletter_member_id, $send_id ) {
		return md5( module_config::c( 'random_seed', mt_rand() ) . ' member ' . $newsletter_member_id . ' with send of ' . $send_id );
	}


	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'l': // opening a tracked link.
				$send_id              = isset( $_REQUEST['a'] ) ? (int) $_REQUEST['s'] : 0;
				$link_id              = isset( $_REQUEST['l'] ) ? (int) $_REQUEST['l'] : 0;
				$newsletter_member_id = isset( $_REQUEST['a'] ) ? (int) $_REQUEST['a'] : 0;
				$correct_hash         = $this->link_to_link( $send_id, $link_id, $newsletter_member_id, true );
				$provided_hash        = isset( $_REQUEST['hash'] ) ? $_REQUEST['hash'] : false;
				if ( $correct_hash == $provided_hash ) {
					// we have a correct link from this member.
					// track that they opened thsi newsletter.
					$this->member_opened_newsletter( $send_id, $newsletter_member_id, 'link', $link_id );
				}
				// redirect to this link even if the hash is incorrect.
				// todo - this is possible information disclosure. maybe we shouldn't do this? oh well.
				if ( $link_id > 0 ) {
					$link = get_single( 'newsletter_link', 'link_id', $link_id );
					//print_r($link);exit;
					$url = $link['link_url'];
					if ( strlen( $url ) < 3 ) {
						// bad link?
						echo 'Incorrect link, sorry';
					} else {
						// todo - format link to full url's or check for common mistakes
						// like having www. and no http://
						if ( $correct_hash == $provided_hash && $newsletter_member_id ) {
							// we append some bits to certain urls (eg: unsubscribe url etc..)
							if ( strpos( $url, _MEMBER_HASH_URL_REDIRECT_BITS ) !== false ) {
								// this url needs some member bits added to it!
								$url = str_replace( _MEMBER_HASH_URL_REDIRECT_BITS, _MEMBER_HASH_URL_REDIRECT_BITS . '&nm=' . $newsletter_member_id . '&s=' . $send_id . '&hash=' . self::newsletter_redirect_hash( $newsletter_member_id, $send_id ), $url );
							}
						}
						header( "Location: " . $url );
					}
				} else {
					echo 'Bad Link';
				}
				exit;
				break;
			case 'i': // viewing a tracked image.
				$send_id              = isset( $_REQUEST['a'] ) ? (int) $_REQUEST['s'] : 0;
				$image_id             = isset( $_REQUEST['i'] ) ? (int) $_REQUEST['i'] : 0;
				$newsletter_member_id = isset( $_REQUEST['a'] ) ? (int) $_REQUEST['a'] : 0;
				$correct_hash         = $this->link_to_image( $send_id, $image_id, $newsletter_member_id, true );
				$provided_hash        = isset( $_REQUEST['hash'] ) ? $_REQUEST['hash'] : false;
				if ( $correct_hash == $provided_hash ) {
					// we have a correct link from this member.
					// track that they opened thsi newsletter.
					$this->member_opened_newsletter( $send_id, $newsletter_member_id, 'image', $image_id );
				}
				// redirect to this link even if the hash is incorrect.
				// todo - this is possible information disclosure. maybe we shouldn't do this? oh well.
				if ( $image_id > 0 ) {
					$image = get_single( 'newsletter_image', 'image_id', $image_id );
					//print_r($image);exit;
					$url = $image['image_url'];
					if ( strlen( $url ) < 3 ) {
						// bad link?
						echo 'Incorrect image link, sorry';
					} else {
						// todo - format link to full url's or check for common mistakes
						// like having www. and no http://
						header( "Location: " . $url );
					}
				} else {
					echo 'Bad image Link';
				}
				exit;
				break;
			case 'doubleoptin': // confirning their subscription via double opt in

				$email     = isset( $_REQUEST['e'] ) ? trim( $_REQUEST['e'] ) : false;
				$real_hash = $this->double_optin_confirmation_link( $email, true );
				$email2    = false;
				if ( strpos( $email, ' ' ) !== false ) {
					$email2     = str_replace( ' ', '+', $email );
					$real_hash2 = $this->double_optin_confirmation_link( $email2, true );
				}
				if ( $email && $_REQUEST['hash'] == $real_hash ) {
					// we have a go!
					$this->subscribe_member_double_optin_done( $email );
				} else if ( $email2 && $_REQUEST['hash'] == $real_hash2 ) {
					// we have a go!
					$this->subscribe_member_double_optin_done( $email2 );
				} else {
					echo _l( 'Sorry, link is incorrect. Please contact us to let us know about this problem.' );
				}
				break;
			case 'unsubscribe': // user is viewing the unsubscribe form.
				include( 'public/unsubscribe.php' );

				break;
			case 'vo': // viewing the specified newsletter online.
				$newsletter_id        = isset( $_REQUEST['n'] ) ? (int) $_REQUEST['n'] : 0;
				$send_id              = isset( $_REQUEST['s'] ) ? (int) $_REQUEST['s'] : 0;
				$voh                  = isset( $_REQUEST['voh'] ) ? $_REQUEST['voh'] : false; // ifi no member id, eg: public viewing link.
				$hash                 = isset( $_REQUEST['hash'] ) ? $_REQUEST['hash'] : false; // set if member id, eg: view link from a send.
				$newsletter_member_id = isset( $_REQUEST['nm'] ) ? (int) $_REQUEST['nm'] : 0;
				if ( $newsletter_id > 0 ) {
					if ( ! $voh && ! $hash ) {
						//
						echo 'Bad hash. Please report this error.';
						exit;
					} else if ( $newsletter_id && $newsletter_member_id && $send_id && $hash ) {
						if ( isset( $_REQUEST[ _MEMBER_HASH_URL_REDIRECT_BITS ] ) ) {
							$correct_hash = self::newsletter_redirect_hash( $newsletter_member_id, $send_id );
						} else {
							$correct_hash = self::view_online_url( $newsletter_id, $newsletter_member_id, $send_id, true );
						}
						if ( $correct_hash == $hash ) {
							echo module_newsletter::render( $newsletter_id, $send_id, $newsletter_member_id, 'view_online' );
							exit;
						}
					}
					if ( $voh ) {
						// public view link
						$correct_voh = self::view_online_url( $newsletter_id, false, false, true );
						if ( $correct_voh == $voh ) {
							echo module_newsletter::render( $newsletter_id, $send_id, false, 'view_online' );
							exit;
						} else {
							echo 'Bad newsletter hash. Please report this error.';
							exit;
						}
					}
				}
				echo 'Bad newsletter link. Please report this error.';
				exit;
				break;
			case 'view_online': // todo - remove 'view_online' soon and go with 'vo' plus hash. helps prevent viewing past newsletters by changing id without hash.

				$newsletter_id = isset( $_REQUEST['n'] ) ? (int) $_REQUEST['n'] : 0;
				if ( $newsletter_id > 0 ) {
					$newsletter_member_id = isset( $_REQUEST['nm'] ) ? (int) $_REQUEST['nm'] : 0;
					$send_id              = isset( $_REQUEST['s'] ) ? (int) $_REQUEST['s'] : 0;
					$hash                 = isset( $_REQUEST['hash'] ) ? $_REQUEST['hash'] : 0;

					if ( $newsletter_id && $newsletter_member_id && $send_id && $hash ) {
						if ( isset( $_REQUEST[ _MEMBER_HASH_URL_REDIRECT_BITS ] ) ) {
							$correct_hash = self::newsletter_redirect_hash( $newsletter_member_id, $send_id );
						} else {
							$correct_hash = self::view_online_url( $newsletter_id, $newsletter_member_id, $send_id, true );
						}
						if ( $correct_hash == $hash ) {
							echo module_newsletter::render( $newsletter_id, $send_id, $newsletter_member_id, 'view_online' );
							exit;
						}
					}

					if ( $newsletter_id ) {
						echo module_newsletter::render( $newsletter_id, $send_id, false, 'view_online' );
					}
				}
				exit;

				break;
		}
	}


	public static function run_cron( $debug = false ) {

		// send any scheduled newsletters via cron job
		$pending = self::get_newsletters( array( 'pending' => 1 ) );
		if ( mysqli_num_rows( $pending ) > 0 ) {
			while ( $send = mysqli_fetch_assoc( $pending ) ) {
				if ( $debug ) {
					echo "Attempting to send: ";
				}
				if ( $debug ) {
					print_r( $send['send_id'] );
				}
				if ( $send['send_id'] ) {
					$send       = module_newsletter::get_send( $send['send_id'] );
					$start_time = $send['start_time'];
					if ( $start_time > time() ) {
						if ( $debug ) {
							echo 'not sending this one yet due to start time';
						}
					} else if ( $send['status'] == _NEWSLETTER_STATUS_PENDING ) {
						$newsletter_send_burst_count = module_config::c( 'newsletter_send_burst_count', 40 );
						$newsletter_send_burst_break = module_config::c( 'newsletter_send_burst_break', 2 );
						for ( $x = 0; $x < 10; $x ++ ) { // todo: find a better way to run the cron job, eg: a timeout in configuration, or a max sends per cron run.
							$send_result = module_newsletter::process_send( $send['newsletter_id'], $send['send_id'], $newsletter_send_burst_count, false, false );
							if ( ! isset( $send_result['send_members'] ) || ! count( $send_result['send_members'] ) ) {
								//$output_messages[] = _l('All done');
							} else {
								foreach ( $send_result['send_members'] as $send_member_result ) {
									$update_members[ $send_member_result['newsletter_member_id'] ] = array();
									switch ( $send_member_result['status'] ) {
										case _MAIL_STATUS_SENT:
											$update_members[ $send_member_result['newsletter_member_id'] ]['.sent_time'] = print_date( time(), true );
											$update_members[ $send_member_result['newsletter_member_id'] ]['.status']    = _l( 'sent' );
											$output_messages[]                                                           = _l( 'Sent successfully: %s', $send_member_result['email'] );
											break;
										case _MAIL_STATUS_OVER_QUOTA:
											$output_messages[]                                                        = _l( 'Over quota, please wait: %s', $send_member_result['email'] );
											$update_members[ $send_member_result['newsletter_member_id'] ]['.status'] = _l( 'pending' );
											// todo - update the main newsletter status to over quota? nah..
											break 2;
										case _MAIL_STATUS_FAILED:
										default:
											$output_messages[]                                                        = _l( 'FAILED: %s Reason: %s', $send_member_result['email'], $send_member_result['error'] );
											$update_members[ $send_member_result['newsletter_member_id'] ]['.status'] = _l( 'failed' );
											break;
									}
								}
							}

							// get an update:
							$send   = module_newsletter::get_send( $send['send_id'] );
							$remain = (int) $send['total_member_count'] - (int) $send['total_sent_count'];
							if ( $remain > 0 ) {
								if ( $debug ) {
									echo " Finished sending, $remain people remain\n";
								}
							} else {
								if ( $debug ) {
									echo " Everyone sent!\n";
								}
								if ( ! $send['finish_time'] ) {
									// just to make sure we set the finish time.
									module_newsletter::process_send( $send['newsletter_id'], $send['send_id'] );
								}
								break; //exit for loop.
							}
						}
					} else {
						if ( $debug ) {
							echo 'not sending due to status of ' . $send['status'];
						}
					}
				}
			}
		}


		if ( ! function_exists( 'imap_open' ) ) {
			set_error( 'Please contact hosting provider and enable IMAP for PHP' );
			echo 'Imap extension not available for php';

			return false;
		}
		self::check_bounces();
	}

	private static function generate_bounce_message_id( $newsletter_id, $send_id, $newsletter_member_id, $hash_only = false ) {
		$hash = md5( "bounce check for $newsletter_id - $newsletter_member_id in send $send_id" );
		if ( $hash_only ) {
			return $hash;
		}

		return "Newsletter-$newsletter_id-$send_id-$newsletter_member_id-" . $hash;
	}

	public static function check_bounces( $debug = false ) {

		$email_username = module_config::c( 'newsletter_bounce_username', '' );
		$email_password = module_config::c( 'newsletter_bounce_password', '' );
		$email_host     = module_config::c( 'newsletter_bounce_host', '' );
		$email_port     = module_config::c( 'newsletter_bounce_port', '110' );
		$email_ssl      = module_config::c( 'newsletter_bounce_ssl', '/ssl' );

		if ( ! $email_username || ! $email_password || ! $email_host || ! $email_port ) {
			if ( $debug ) {
				echo "No username, password, host or port defined. Please check settings.\n";
			}

			return;
		}

		$connect_string = '{' . $email_host . ':' . $email_port . $email_ssl . '/pop3/novalidate-cert}INBOX';
		if ( $debug ) {
			echo "Connecting using: " . $connect_string . "\n\n";
		}

		$mbox = imap_open( $connect_string, $email_username, $email_password, 0, 1 ); // or die();
		if ( ! $mbox ) {
			// send email letting them know bounce checking failed?
			// meh. later.
			echo 'Failed to connect';
			echo print_r( imap_errors() );
		} else {
			$MC     = imap_check( $mbox );
			$result = imap_fetch_overview( $mbox, "1:{$MC->Nmsgs}", 0 );
			foreach ( $result as $overview ) {
				if ( $debug ) {
					$this_subject = (string) $overview->subject;
					echo "\n\n--------------------------------------------------\nFound an email! \n";
					echo "   Subject: $this_subject\n";
					echo "   #{$overview->msgno} ({$overview->date}) - From: {$overview->from} \n";
				}
				if ( is_dir( _UCM_FILE_STORAGE_DIR . "temp/" ) && is_writable( _UCM_FILE_STORAGE_DIR . "temp/" ) ) {
					$tmp_file = tempnam( _UCM_FILE_STORAGE_DIR . "temp/", 'newsletter_bounce' );
				} else {
					$tmp_file = tempnam( sys_get_temp_dir(), 'newsletter_bounce' );
				}
				imap_savebody( $mbox, $tmp_file, $overview->msgno );
				$body             = file_get_contents( $tmp_file );
				$this_is_a_bounce = false;

				if ( preg_match( '/Message-ID:\s*<?Newsletter-(\d+)-(\d+)-(\d+)-([A-Fa-f0-9]{32})/imsU', $body, $matches ) ) {
					// we have a newsletter message id, check the hash and mark a bounce.
					$newsletter_id        = (int) $matches[1];
					$send_id              = (int) $matches[2];
					$newsletter_member_id = (int) $matches[3];
					$provided_hash        = trim( $matches[4] );
					if ( $debug ) {
						echo "  this is a bounce newsletter. ID: $newsletter_id SEND: $send_id, Member: $newsletter_member_id \n";
					}
					$real_hash = self::generate_bounce_message_id( $newsletter_id, $send_id, $newsletter_member_id, true );
					if ( $provided_hash == $real_hash ) {
						$this_is_a_bounce = true;
						// YAY! valid bounce!
						// have to update the newsletter_member with an extra bounce_count.
						//$newsletter_member = get_single('newsletter_member','newsletter_member_id',$newsletter_member_id);
						//if($newsletter_member && $newsletter_member['newsletter_member_id'] == $newsletter_member_id){
						// found the member! increment count.
						$sql = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET bounce_count = bounce_count + 1 WHERE newsletter_member_id = " . (int) $newsletter_member_id . " LIMIT 1";
						query( $sql );
						//}
						// have to update newsletter_send_member with a bounce timestamp.
						$sql = "UPDATE " . _DB_PREFIX . "newsletter_send_member SET `status` = " . _NEWSLETTER_STATUS_BOUNCED . ", bounce_time = '" . time() . "' WHERE `newsletter_member_id` = '" . $newsletter_member_id . "' AND send_id = '" . $send_id . "' LIMIT 1";
						query( $sql );
					} else {
						if ( $debug ) {
							echo "   WOA! Hash doesn't match, this is a big problem. Get in touch with dtbaker. \n";
							echo "    " . $matches[0] . " with \n";
							echo "    " . $real_hash . "\n";
						}
						// bad hash, or no hash found, report this so the user can login manually and review the bounced message.
					}
				}
				if ( ! $this_is_a_bounce ) {
					// didn't find a bounce using message ID hash.
					// this can be because we're sending through google.
					// look for the subject fields.
					if ( $debug ) {
						echo '    - no bounce message id found, checking for "delivery failure" message' . "\n";
					}
					if (
						( preg_match( '#Delivery.*Failure#i', $body ) || preg_match( '#Delivery.*Failed#i', $body ) || preg_match( '#Failed.*Delivery#i', $body ) )
						&& preg_match_all( '#Subject: (.*)#', $body, $subject_matches )
					) {

						if ( $debug ) {
							echo '    - FOUND DELIVERY FAILURE' . "\n";
						}
						// find who this newsletter was sent to
						$to_emails = array();
						if ( preg_match_all( '#To: (.*)#', $body, $to_matches ) ) {
							foreach ( $to_matches[1] as $possible_to_email ) {
								// todo: ignore the "To" address of the original sender email in newsletter table,
								$possible_to_email = str_replace( '<', ' ', $possible_to_email );
								$possible_to_email = str_replace( '>', ' ', $possible_to_email );
								foreach ( explode( ' ', $possible_to_email ) as $token ) {
									if ( $debug ) {
										echo '    - parsing email:' . $token . "\n";
									}
									$email = filter_var( filter_var( $token, FILTER_SANITIZE_EMAIL ), FILTER_VALIDATE_EMAIL );
									if ( $email !== false ) {
										if ( $debug ) {
											echo '    - checking for local member:' . $email . "\n";
										}
										$search_newsletter_member_id = self::member_from_email( array( 'email' => $email ), false );
										if ( $search_newsletter_member_id ) {
											if ( $debug ) {
												echo '    - Found Local Member!:' . $email . " (ID: $search_newsletter_member_id) \n";
											}
											$to_emails [] = $search_newsletter_member_id;
										}
									}
								}
							}
						}
						if ( count( $to_emails ) ) {
							// we have some to emails.
							foreach ( $subject_matches[1] as $subject ) {
								$subject = trim( $subject );
								if ( ! strlen( $subject ) ) {
									continue;
								}
								if ( $debug ) {
									echo '    - Checking email subject:' . $subject . "\n";
								}
								// find a newsletter that matches this subject in our listing.
								$newsletters = get_multiple( 'newsletter_send', array( 'subject' => $subject ) );
								if ( count( $newsletters ) ) {
									foreach ( $newsletters as $newsletter_send ) {
										if ( $debug ) {
											echo '    - found a local newsletter send matching this subject' . "\n";
											//print_r($newsletter_send);
										}
										// see if this to address was in the recipient list.
										foreach ( $to_emails as $to_member_id ) {
											// see if this member exists in this send.
											$send_members = get_multiple( 'newsletter_send_member', array(
												'newsletter_member_id' => $to_member_id,
												'send_id'              => $newsletter_send['send_id']
											) );
											if ( count( $send_members ) ) {
												// should really only be one, oh well.
												foreach ( $send_members as $send_member ) {
													if ( $send_member['newsletter_member_id'] == $to_member_id ) {
														$this_is_a_bounce = true;
														$sql              = "UPDATE `" . _DB_PREFIX . "newsletter_member` SET bounce_count = bounce_count + 1 WHERE newsletter_member_id = " . (int) $send_member['newsletter_member_id'] . " LIMIT 1";
														query( $sql );
														//}
														// have to update newsletter_send_member with a bounce timestamp.
														$sql = "UPDATE " . _DB_PREFIX . "newsletter_send_member SET `status` = " . _NEWSLETTER_STATUS_BOUNCED . ", bounce_time = '" . time() . "' WHERE `newsletter_member_id` = '" . $send_member['newsletter_member_id'] . "' AND send_id = '" . $send_member['send_id'] . "' LIMIT 1";
														query( $sql );
														if ( $debug ) {
															echo '    - FOUND MEMBER TO BOUNCE!' . " (ID: " . $send_member['newsletter_member_id'] . "\n";

														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}

				if ( $this_is_a_bounce ) {

					if ( $debug ) {
						echo " SUCESS! Bounce recorded. Deleting email from inbox.\n";
					}
					imap_delete( $mbox, $overview->msgno );
				} else {
					if ( $debug ) {
						echo " FAILED! No message ID found in this email. It must not be a bounce from the newsletter system. \n";
					}
				}
				unlink( $tmp_file );
			}
			imap_expunge( $mbox );
			imap_close( $mbox );
		}
		if ( $debug ) {
			echo "Bounce checking finished\n";
		}

	}


	public function get_upgrade_sql() {
		$sql = '';
		/*$installed_version = (string)$installed_version;
        $new_version = (string)$new_version;
        // special hack to see if an upgrade is needed.
        // this is just to test a different way of doing an upgrade with a bit of php
        // rather than just handing back sql.
        $sql = '';
        switch($installed_version){
            case '2':
                switch($new_version){
                    case '2.1':
                        $fields = get_fields('newsletter_member');
                        if(isset($fields['name']) && !isset($fields['company_name'])){
                            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'newsletter_member` CHANGE  `name`  `company_name` VARCHAR( 70 ) NOT NULL DEFAULT \'\';';
                        }
                        $fields = get_fields('newsletter_send_member');
                        if(!isset($fields['unsubscribe_time'])){
                            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'newsletter_send_member` ADD  `unsubscribe_time` INT( 11 ) NOT NULL DEFAULT \'0\' AFTER `bounce_time`;';
                        }
                        break;
                }
                break;
        }*/
		/*$options = array(
            '2' => array(
                '2.1' =>   'ALTER TABLE  `'._DB_PREFIX.'newsletter_member` CHANGE  `name`  `company_name` VARCHAR( 70 ) NOT NULL DEFAULT \'\';' .
                         'ALTER TABLE  `'._DB_PREFIX.'newsletter_send_member` ADD  `unsubscribe_time` INT( 11 ) NOT NULL DEFAULT \'0\' AFTER `bounce_time`;',
            ),

        );
        if(isset($options[$installed_version]) && isset($options[$installed_version][$new_version])){
            $sql = $options[$installed_version][$new_version];
        }*/

		if ( ! $this->db_table_exists( 'newsletter_blacklist' ) ) {
			// add this new table
			$sql .= 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX . 'newsletter_blacklist` (
      `newsletter_blacklist_id` int(11) NOT NULL auto_increment,
      `email` varchar(255) NOT NULL DEFAULT \'\',
      `reason` varchar(20) NOT NULL DEFAULT \'\',
      `time` INT (11) NOT NULL DEFAULT \'0\',
      `date_created` date NULL,
      `date_updated` date NULL,
      PRIMARY KEY  (`newsletter_blacklist_id`),
      KEY `email` (`email`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;';
		}

		$fields = get_fields( 'newsletter_member' );
		if ( ! isset( $fields['member_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_member` ADD `member_id` INT(11) NOT NULL DEFAULT \'0\';';
		}

		$fields = get_fields( 'newsletter_template' );
		if ( ! isset( $fields['date_created'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `date_created` DATE NULL ;';
		}
		if ( ! isset( $fields['date_updated'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `date_updated` DATE NULL ;';
		}
		if ( ! isset( $fields['content_url'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `content_url` varchar(255) NOT NULL DEFAULT \'\';';
		}
		if ( ! isset( $fields['body'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `body` LONGTEXT NOT NULL DEFAULT \'\';';
		}
		if ( ! isset( $fields['wizard'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `wizard` TINYINT( 1 ) NOT NULL DEFAULT \'0\' AFTER  `body`;';
		}
		if ( ! isset( $fields['default_inner'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_template` ADD `default_inner` LONGTEXT NOT NULL DEFAULT \'\' AFTER  `body`;';
		}
		$fields = get_fields( 'newsletter_link' );
		if ( ! isset( $fields['send_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_link` ADD `send_id` INT(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['page_index'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_link` ADD  `page_index` INT NOT NULL DEFAULT  \'0\' AFTER  `link_url`';
		}
		$fields = get_fields( 'newsletter_link_open' );
		if ( ! isset( $fields['date_created'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_link_open` ADD `date_created` DATE NOT NULL;';
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_link_open` ADD `date_updated` DATE NOT NULL;';
		}
		$fields = get_fields( 'newsletter_image' );
		if ( ! isset( $fields['send_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'newsletter_image` ADD `send_id` INT(11) NOT NULL DEFAULT \'0\';';
		}

		self::add_table_index( 'newsletter_link_open', 'link_id' );
		self::add_table_index( 'newsletter_link_open', 'newsletter_member_id' );
		self::add_table_index( 'newsletter_link_open', 'send_id' );
		//        self::add_table_index('newsletter_link','link_url');
		self::add_table_index( 'newsletter_link', 'send_id' );
		self::add_table_index( 'newsletter_member', 'member_id' );
		self::add_table_index( 'newsletter_member', 'email' );
		self::add_table_index( 'newsletter_member', 'join_date' );
		self::add_table_index( 'newsletter_member', 'receive_email' );
		self::add_table_index( 'newsletter_member', 'unsubscribe_date' );
		self::add_table_index( 'newsletter_member', 'unsubscribe_send_id' );
		self::add_table_index( 'newsletter_member', 'bounce_count' );
		self::add_table_index( 'newsletter_send', 'newsletter_id' );
		self::add_table_index( 'newsletter_blacklist', 'email' );
		self::add_table_index( 'newsletter_blacklist', 'reason' );


		return $sql;
	}


	public function get_install_sql() {
		ob_start();
		?>


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter` (
		`newsletter_id` int(11) NOT NULL AUTO_INCREMENT,
		`create_date` date NOT NULL,
		`last_sent` int(11) NOT NULL,
		`newsletter_template_id` int(11) NOT NULL,
		`subject` varchar(255) NOT NULL,
		`from_name` varchar(255) NOT NULL,
		`from_email` varchar(255) NOT NULL,
		`to_name` varchar(255) NOT NULL DEFAULT '',
		`content` text NOT NULL,
		`bounce_email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
		`extra` text CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`newsletter_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_blacklist` (
		`newsletter_blacklist_id` int(11) NOT NULL AUTO_INCREMENT,
		`email` varchar(255) NOT NULL DEFAULT '',
		`reason` varchar(20) NOT NULL DEFAULT '',
		`time` int(11) NOT NULL DEFAULT '0',
		`date_created` date DEFAULT NULL,
		`date_updated` date DEFAULT NULL,
		PRIMARY KEY (`newsletter_blacklist_id`),
		KEY `email` (`email`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_campaign` (
		`campaign_id` int(11) NOT NULL AUTO_INCREMENT,
		`campaign_name` varchar(255) NOT NULL,
		`public` int(11) NOT NULL DEFAULT '1',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`campaign_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_campaign_member` (
		`campaign_id` int(11) NOT NULL,
		`newsletter_member_id` int(11) NOT NULL,
		`current_newsletter_id` int(11) NOT NULL,
		`join_time` int(11) NOT NULL,
		PRIMARY KEY (`campaign_id`,`newsletter_member_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_campaign_newsletter` (
		`campaign_id` int(11) NOT NULL,
		`newsletter_id` int(11) NOT NULL,
		`send_time` int(11) NOT NULL,
		PRIMARY KEY (`campaign_id`,`newsletter_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_content` (
		`newsletter_content_id` int(11) NOT NULL AUTO_INCREMENT,
		`newsletter_id` int(11) NOT NULL,
		`group_title` varchar(255) NOT NULL,
		`position` float NOT NULL,
		`title` varchar(255) NOT NULL,
		`content_full` text NOT NULL,
		`content_summary` text NOT NULL,
		`image_thumb` varchar(255) NOT NULL,
		`image_main` varchar(255) NOT NULL,
		`create_date` datetime NOT NULL,
		`extra` text NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`newsletter_content_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_image` (
		`image_id` int(11) NOT NULL AUTO_INCREMENT,
		`send_id` int(11) NOT NULL,
		`image_url` varchar(255) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`image_id`),
		KEY `send_id` (`send_id`),
		KEY `image_url` (`image_url`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_link` (
		`link_id` int(11) NOT NULL AUTO_INCREMENT,
		`send_id` int(11) NOT NULL,
		`link_url` varchar(255) NOT NULL,
		`page_index` INT NOT NULL DEFAULT  '0',
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`link_id`),
		KEY `send_id` (`send_id`),
		KEY `link_url` (`link_url`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_link_open` (
		`link_open_id` int(11) NOT NULL AUTO_INCREMENT,
		`link_id` int(11) NOT NULL,
		`newsletter_member_id` int(11) NOT NULL,
		`send_id` int(11) NOT NULL,
		`timestamp` int(11) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`link_open_id`),
		KEY `send_id` (`send_id`),
		KEY `link_id` (`link_id`),
		KEY `newsletter_member_id` (`newsletter_member_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_member` (
		`newsletter_member_id` int(11) NOT NULL AUTO_INCREMENT,
		`company_name` varchar(255) NOT NULL DEFAULT '',
		`first_name` varchar(255) NOT NULL DEFAULT '',
		`last_name` varchar(255) NOT NULL DEFAULT '',
		`email` varchar(255) NOT NULL DEFAULT '',
		`mobile` varchar(255) NOT NULL DEFAULT '',
		`join_date` date DEFAULT NULL,
		`ip_address` varchar(15) NOT NULL DEFAULT '',
		`receive_email` char(1) NOT NULL DEFAULT '1',
		`receive_sms` char(1) NOT NULL DEFAULT '1',
		`unsubscribe_date` date DEFAULT NULL,
		`unsubscribe_send_id` int(11) NOT NULL DEFAULT '0',
		`bounce_count` int(11) NOT NULL DEFAULT '0',
		`data_callback` varchar(255) NOT NULL DEFAULT '' COMMENT 'will run this callback just before sending mail to get updated details and any more fields from it.',
		`data_args` varchar(255) NOT NULL DEFAULT '',
		`data_cache` longtext NOT NULL,
		`date_created` date DEFAULT NULL,
		`date_updated` date DEFAULT NULL,
		`member_id` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`newsletter_member_id`),
		KEY `join_date` (`join_date`),
		KEY `mobile` (`mobile`),
		KEY `email` (`email`),
		KEY `first_name` (`first_name`),
		KEY `member_id` (`member_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_send` (
		`send_id` int(11) NOT NULL AUTO_INCREMENT,
		`start_time` int(11) NOT NULL DEFAULT '0',
		`status` int(11) NOT NULL DEFAULT '0',
		`finish_time` int(11) NOT NULL DEFAULT '0',
		`newsletter_id` int(11) NOT NULL DEFAULT '0',
		`campaign_id` int(11) NOT NULL DEFAULT '0',
		`cache` longtext NOT NULL,
		`subject` varchar(255) NOT NULL DEFAULT '',
		`allow_duplicates` tinyint(4) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date DEFAULT NULL,
		PRIMARY KEY (`send_id`),
		KEY `newsletter_id` (`newsletter_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_send_member` (
		`send_id` int(11) NOT NULL,
		`newsletter_member_id` int(11) NOT NULL,
		`sent_time` int(11) NOT NULL DEFAULT '0',
		`status` int(11) NOT NULL DEFAULT '0',
		`open_time` int(11) NOT NULL DEFAULT '0',
		`bounce_time` int(11) NOT NULL DEFAULT '0',
		`unsubscribe_time` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`send_id`,`newsletter_member_id`),
		KEY `open_time` (`open_time`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_sync` (
		`sync_id` int(11) NOT NULL AUTO_INCREMENT,
		`sync_name` varchar(50) NOT NULL,
		`edit_url` varchar(255) NOT NULL,
		`db_username` varchar(40) NOT NULL,
		`db_password` varchar(40) NOT NULL,
		`db_host` varchar(40) NOT NULL,
		`db_name` varchar(40) NOT NULL,
		`db_table` varchar(40) NOT NULL,
		`db_table_key` varchar(40) NOT NULL,
		`db_table_email_key` varchar(40) NOT NULL,
		`db_table_fname_key` varchar(40) NOT NULL,
		`db_table_lname_key` varchar(40) NOT NULL,
		`callback_function` varchar(60) NOT NULL,
		`last_sync` int(11) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`sync_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_sync_group` (
		`sync_id` int(11) NOT NULL,
		`group_id` int(11) NOT NULL,
		PRIMARY KEY (`sync_id`,`group_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_sync_member` (
		`sync_id` int(11) NOT NULL,
		`sync_unique_id` int(11) NOT NULL,
		`newsletter_member_id` int(11) NOT NULL,
		PRIMARY KEY (`sync_id`,`sync_unique_id`,`newsletter_member_id`),
		KEY `sync_id` (`sync_id`),
		KEY `sync_unique_id` (`sync_unique_id`),
		KEY `newsletter_member_id` (`newsletter_member_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>newsletter_template` (
		`newsletter_template_id` int(11) NOT NULL AUTO_INCREMENT,
		`newsletter_template_name` varchar(255) NOT NULL,
		`content_url` varchar(255) NOT NULL,
		`body` longtext NOT NULL,
		`default_inner` longtext NOT NULL,
		`wizard` tinyint(1) NOT NULL DEFAULT '0',
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		PRIMARY KEY (`newsletter_template_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


		INSERT INTO `<?php echo _DB_PREFIX; ?>newsletter_template` (`newsletter_template_id`, `newsletter_template_name`, `content_url`, `body`, `default_inner`, `wizard`, `date_created`, `date_updated`, `create_user_id`, `update_user_id`) VALUES
		(1, 'Boutique', '', '', '', 0, '2012-03-05 15:32:32', '2012-06-23 15:38:38', 1, 1),
		(2, 'Classic', '', '', '', 0, '2012-03-05 15:32:56', '2012-06-23 15:38:53', 1, 1),
		(3, 'Basic1', '', '', '', 0, '2012-06-23 15:23:49', '2012-06-23 15:57:45', 1, 1),
		(4, 'Modern', '', '', '', 0, '2012-06-23 15:39:02', '0000-00-00 00:00:00', 1, 0),
		(5, 'Natural', '', '', '', 0, '2012-06-23 15:39:09', '0000-00-00 00:00:00', 1, 0);


		<?php
		return ob_get_clean();
	}

	public static function save_member( $member_id, $data ) {
		return update_insert( 'newsletter_member_id', $member_id, 'newsletter_member', $data );
	}


}
