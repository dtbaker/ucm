<?php


class module_member extends module_base {

	public $links;
	public $member_types;
	public $member_id;

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
		$this->member_types    = array();
		$this->module_name     = "member";
		$this->module_position = 20.1;
		$this->version         = 2.185;
		// 2.185 - 2016-07-10 - big update to mysqli
		// 2.184 - 2014-11-26 - duplicate member warning on add
		// 2.183 - 2014-08-10 - responsive fixes
		// 2.182 - 2014-02-18 - member feature to delete double optin spam
		// 2.181 - 2014-02-05 - newsletter speed improvements
		// 2.18 - 2013-09-01 - member automatic subscription fix
		// 2.17 - 2013-08-31 - cache and speed improvements
		// 2.169 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.168 - 2013-05-06 - member import csv fix

		// 2.14 - delete member from a group
		// 2.15 - newsletter subscriptions and modifying subscription details.
		// 2.16 - release of the subscription plugin, incrememt version number just for the push
		// 2.161 - delete fix
		// 2.162 - support extra fields in external subscribe
		// 2.163 - adding member id through to newsletter subscription table. also showing doublt opt in waiting in member stats.
		// 2.164 - submit_small in subscription
		// 2.165 - delete members bug fix
		// 2.166 - fix search
		// 2.167 - bulk member delete

	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Members' ) ) {

			// how many members are there?
			$link_name = _l( 'Members' );
			if ( module_config::c( 'member_show_summary', 1 ) ) {
				$member_count = module_cache::get( 'member', 'member_menu_count' );
				if ( $member_count === false ) {
					$sql          = "SELECT COUNT(member_id) AS c FROM `" . _DB_PREFIX . "member` m";
					$res          = qa1( $sql );
					$member_count = $res['c'];
					module_cache::put( 'member', 'member_menu_count', $member_count );
				}
				if ( $member_count > 0 ) {
					$link_name .= " <span class='menu_label'>" . $member_count . "</span> ";
				}
			}

			$this->links['members'] = array(
				"name" => $link_name,
				"p"    => "member_admin",
				"args" => array( 'member_id' => false ),
			);
			if ( class_exists( 'module_newsletter', false ) && module_config::c( 'member_menu_under_newsletter', 1 ) ) {
				$this->links['members']['holder_module']       = 'newsletter';
				$this->links['members']['holder_module_page']  = 'newsletter_admin';
				$this->links['members']['menu_include_parent'] = 0;
				$this->links['members']['allow_nesting']       = 1;
			}
		}

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'member_subscription_form', '<h2>Subscribe</h2>
<form action="" method="post">
    <p>Please Enter Your Email Address: <input type="text" name="member[email]" value="{EMAIL}"> </p>
    <p>Please Enter Your First Name: <input type="text" name="member[first_name]" value="{FIRST_NAME}"> </p>
    <p>Please Enter Your Last Name: <input type="text" name="member[last_name]" value="{LAST_NAME}"> </p>
    <p>Please Enter Your Business Name: <input type="text" name="member[business]" value="{BUSINESS}"> </p>
    <p>Please Enter Your Phone Number: <input type="text" name="member[phone]" value="{PHONE}"> </p>
    <p>
    Please choose your newsletter subscription options: <br/>
    {NEWSLETTER_OPTIONS}
    </p>
    <p><input type="submit" name="confirm" value="Subscribe"></p>
</form>
    ', 'Used when a user wishes to subscribe.', 'code', array() );

			module_template::init_template( 'member_subscription_error', '<h2>Subscription Error</h2>
    <p>Sorry there was an error when processing your request:</p>
    <p>{MESSAGE}</p>
    ', 'Displayed when subscription fails (eg: missing email address).', 'code', array(
				'MESSAGE' => 'Message to the user',
			) );
			module_template::init_template( 'member_subscription_success', '<h2>Subscription Success</h2>
    <p>Thank you, subscription successful.</p>
    <p>A message has been sent to your email address ({EMAIL}) to confirm your newsletter subscription.</p>
    ', 'Displayed when subscription is successful.', 'code', array(
				'EMAIL' => 'Users email address',
			) );
			module_template::init_template( 'member_update_details_success', '<h2>Subscription Success</h2>
    <p>Thank you, subscription details updated.</p>
    <p>Your email address: ({EMAIL})</p>
    ', 'Displayed when updating details is successful.', 'code', array(
				'EMAIL' => 'Users email address',
			) );
		}
	}

	/** static stuff */


	public static function link_generate( $member_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'member_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

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
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				$data            = self::get_member( $member_id );
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = $data['first_name'] . ' ' . $data['last_name'];
		}
		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'member_id' => $member_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = 'member_admin' . ( ( $member_id || $member_id == 'new' ) ? '' : '_list' );
		$options['module'] = 'member';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! self::can_i( 'view', 'Members' ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				return '#';
			} else {
				return isset( $options['text'] ) ? $options['text'] : 'N/A';
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		if ( class_exists( 'module_newsletter', false ) && module_config::c( 'member_menu_under_newsletter', 1 ) ) {
			$bubble_to_module = array(
				'module'   => 'newsletter',
				'argument' => 'member_id', //?
			);
		}
		/*$bubble_to_module = array(
				'module' => 'people',
				'argument' => 'people_id',
		);*/
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );
		}
	}


	public static function link_open( $member_id, $full = false, $data = array() ) {
		return self::link_generate( $member_id, array( 'full' => $full, 'data' => $data ) );
	}


	public static function get_members( $search = array() ) {
		// build up a custom search sql query based on the provided search fields
		$sql   = "SELECT c.*, c.member_id AS id ";
		$sql   .= " FROM `" . _DB_PREFIX . "member` c ";
		$where = " WHERE 1";
		if ( isset( $search['generic'] ) && trim( $search['generic'] ) ) {
			$str = db_escape( trim( $search['generic'] ) );
			// search the member name, contact name, cusomter phone, contact phone, contact email.
			//$where .= 'AND u.member_id IS NOT NULL AND ( ';
			$where .= " AND ( ";
			$where .= "c.business LIKE '%$str%' OR ";
			$where .= "c.first_name LIKE '%$str%' OR ";
			$where .= "c.last_name LIKE '%$str%' OR ";
			$where .= "c.email LIKE '%$str%' OR ";
			$where .= "c.phone LIKE '%$str%' OR ";
			$where .= "c.mobile LIKE '%$str%' ";
			$where .= ') ';
		}
		if ( isset( $search['group_id'] ) && trim( $search['group_id'] ) ) {
			$str = (int) $search['group_id'];
			// search all the member site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (c.member_id = gm.owner_id)";
			$where .= " AND (gm.group_id = '$str' AND gm.owner_table = 'member'";
			$where .= " )";
		}
		if ( isset( $search['group_id2'] ) && trim( $search['group_id2'] ) && class_exists( 'module_newsletter', false ) ) {
			$str = (int) $search['group_id2'];
			// search all the member site addresses.
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gmN ON (c.member_id = gmN.owner_id)";
			$where .= " AND (gmN.group_id = '$str' AND gmN.owner_table = 'newsletter_subscription'";
			$where .= " )";
		}

		$group_order = ' GROUP BY c.member_id ORDER BY c.first_name ASC'; // stop when multiple company sites have same region
		$sql         = $sql . $where . $group_order;

		return qa( $sql );
		//return get_multiple("member",$search,"member_id","fuzzy","first_name");
	}

	public static function get_member( $member_id ) {
		$member_id = (int) $member_id;
		$member    = false;
		if ( $member_id > 0 ) {
			$member = get_single( "member", "member_id", $member_id );

		}
		if ( ! $member ) {
			$member = array(
				'member_id'  => 'new',
				'first_name' => '',
				'last_name'  => '',
				'business'   => '',
				'email'      => '',
				'phone'      => '',
				'mobile'     => '',
			);
		}

		return $member;
	}


	/**
	 * @static
	 *
	 * @param $member_id
	 *
	 * @return array
	 *
	 * return a member recipient ready for sending a newsletter based on the member id.
	 *
	 */
	public static function get_newsletter_recipient( $member_id, $basic = false ) {
		$member = self::get_member( $member_id );
		if ( ! $member || ! (int) $member['member_id'] ) {
			return false;
		} // member doesn't exist any more
		if ( $basic ) {
			return $member;
		}
		$member['company_name'] = $member['business'];
		// some other details the newsletter system might need.
		$member['_edit_link'] = self::link_open( $member_id, false, $member );
		$extras               = module_extra::get_extras( array(
			'owner_table' => 'member',
			'owner_id'    => $member['member_id']
		) );
		foreach ( $extras as $extra ) {
			if ( ! strlen( trim( $extra['extra'] ) ) ) {
				continue;
			}
			$key = $extra['extra_key'];
			$x   = 1;
			while ( isset( $member[ $key ] ) ) {
				$key = $extra['extra_key'] . $x;
				$x ++;
			}
			$member[ $key ] = trim( $extra['extra'] );
		}

		return $member;
	}

	/** methods  */


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['member_id'] ) {
			$data = self::get_member( $_REQUEST['member_id'] );
			if ( module_form::confirm_delete( 'member_id', "Really delete member: " . $data['first_name'] . ' ' . $data['last_name'], self::link_open( $_REQUEST['member_id'] ) ) ) {
				$this->delete_member( $_REQUEST['member_id'] );
				set_message( "Member deleted successfully" );
				redirect_browser( self::link_open( false ) );
			}
		} else if ( "save_member" == $_REQUEST['_process'] ) {
			$member_id = $this->save_member( $_REQUEST['member_id'], $_POST );
			hook_handle_callback( 'member_save', $member_id );
			set_message( "Member saved successfully" );
			redirect_browser( self::link_open( $member_id ) );
		}
	}

	public function load( $member_id ) {
		$data = self::get_member( $member_id );
		foreach ( $data as $key => $val ) {
			$this->$key = $val;
		}

		return $data;
	}

	public function save_member( $member_id, $data ) {
		if ( module_config::c( 'member_duplicate_check', 1 ) && ! (int) $member_id ) {
			if ( isset( $data['email'] ) && strlen( $data['email'] ) ) {
				$exists = get_single( 'member', 'email', $data['email'] );
				if ( $exists && $exists['member_id'] ) {
					set_error( _l( 'Sorry a member with email %s already exists', '<a href="' . self::link_open( $exists['member_id'], false ) . '">' . htmlspecialchars( $data['email'] ) . '</a>' ) );

					return false;
				}
			}
		}
		$member_id = update_insert( "member_id", $member_id, "member", $data );

		module_extra::save_extras( 'member', 'member_id', $member_id );

		return $member_id;
	}


	public function delete_member( $member_id ) {
		$member_id = (int) $member_id;
		$member    = self::get_member( $member_id );
		if ( $member && $member['member_id'] == $member_id ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "member WHERE member_id = '" . $member_id . "' LIMIT 1";
			query( $sql );
			module_extra::delete_extras( 'member', 'member_id', $member_id );

			if ( class_exists( 'module_group', false ) ) {
				module_group::delete_member( $member_id, 'member' );
				module_group::delete_member( $member_id, 'newsletter_subscription' );
			}
			hook_handle_callback( 'member_deleted', $member_id );
		}
	}

	public static function handle_import( $data, $add_to_group ) {

		// woo! we're doing an import.

		// our first loop we go through and find matching members by their "member_name" (required field)
		// and then we assign that member_id to the import data.
		// our second loop through if there is a member_id we overwrite that existing member with the import data (ignoring blanks).
		// if there is no member id we create a new member record :) awesome.

		foreach ( $data as $rowid => $row ) {
			if ( ! isset( $row['email'] ) || ! trim( $row['email'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['member_id'] ) || ! $row['member_id'] ) {
				$data[ $rowid ]['member_id'] = 0;
			}
		}

		// now save the data.
		foreach ( $data as $rowid => $row ) {
			$member_id = (int) $row['member_id'];
			// check if this ID exists.
			if ( $member_id > 0 ) {
				$member = self::get_member( $member_id );
				if ( ! $member || $member['member_id'] != $member_id ) {
					$member_id = 0;
				}
			}
			if ( ! $member_id ) {
				// search for a member based on email.
				$member = get_single( 'member', 'email', $row['email'] );
				if ( $member && $member['member_id'] > 0 ) {
					$member_id = $member['member_id'];
				}
			}
			$member_id = update_insert( "member_id", $member_id, "member", $row );

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
						'owner_table' => 'member',
						'owner_id'    => $member_id,
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
						'owner_table' => 'member',
						'owner_id'    => $member_id,
					);
					$extra_id = (int) $extra_id;
					update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
				}
			}

			foreach ( $add_to_group as $group_id => $tf ) {
				module_group::add_to_group( $group_id, $member_id );
			}

		}


	}

	public static function link_public_subscribe() {
		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.member/h.subscribe' );
	}

	public static function link_public_details( $member_id, $h = false ) {
		if ( $h ) {
			return md5( 'secret hash for member ' . _UCM_SECRET . ' ' . $member_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.member/h.subscribe_form/i.' . $member_id . '/hash.' . self::link_public_details( $member_id, true ) );
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'subscribe_form':
				// handle subscriptions to the member database and also the newsletter system.
				// todo - tie in with "subscription" module to allow users to select which subscription they want as well.
			case 'subscribe':

				$member = isset( $_REQUEST['member'] ) && is_array( $_REQUEST['member'] ) ? $_REQUEST['member'] : false;

				$provided_member_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash               = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				$member_id          = false;

				if ( $member ) {
					if ( isset( $member['email'] ) && $member['email'] ) {
						// proceed with signup
						$email = filter_var( strtolower( trim( $member['email'] ) ), FILTER_VALIDATE_EMAIL );
						if ( strlen( $email ) > 3 ) {

							$adding_new_member = true;
							// are we adding a new member to the system or updating an old one
							if ( $provided_member_id && $hash ) {
								$real_hash = $this->link_public_details( $provided_member_id, true );
								if ( $real_hash == $hash ) {
									$existing_member = get_single( 'member', 'email', $email );
									if ( $existing_member && $existing_member['member_id'] != $provided_member_id ) {
										// this user is trying to update their email address to a user who exists in the system already
										$template             = module_template::get_template_by_key( 'member_subscription_error' );
										$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
										$template->assign_values( array( 'message' => _l( 'The email address %s is already linked to another member.', htmlspecialchars( $email ) ) ) );
										echo $template->render( 'pretty_html' );
										exit;
									}
									$adding_new_member = false;
									// updating details in the system.
									update_insert( "member_id", $provided_member_id, "member", $member );
									$member_id = $provided_member_id;
									// update extra fields...
								}
							}

							if ( ! $member_id ) {
								// add member to system.
								$existing_member = get_single( 'member', 'email', $email );
								if ( $existing_member && $existing_member['member_id'] > 0 ) {
									// todo: give them link to change details.
									$template             = module_template::get_template_by_key( 'member_subscription_error' );
									$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
									$template->assign_values( array( 'message' => _l( 'The email address %s is already a member. Please click the link in our newsletter to modify your details.', htmlspecialchars( $email ) ) ) );
									echo $template->render( 'pretty_html' );
									exit;
								}

								// todo - sanatise input here, this will allow anyone to insert member details:
								$member_id = update_insert( "member_id", 'new', "member", $member );

							}
							if ( $member_id ) {

								// save extra fields against member.
								$extra_fields = module_extra::get_defaults( 'member' );
								$extra_values = array();
								foreach ( $extra_fields as $extra_field ) {
									// check if this field was submitted.
									if ( isset( $member[ $extra_field['key'] ] ) ) {
										$extra_values[ $extra_field['key'] ] = array(
											'val' => $member[ $extra_field['key'] ],
											'key' => $extra_field['key'],
										);
									}
								}
								if ( count( $extra_values ) ) {
									$_REQUEST['extra_member_field'] = $extra_values;
									module_extra::save_extras( 'member', 'member_id', $member_id, false );
								}


								if ( class_exists( 'module_newsletter', false ) ) {

									$newsletter_member_id = module_newsletter::member_from_email( array(
										'email'         => $email,
										'member_id'     => $member_id,
										'data_callback' => 'module_member::get_newsletter_recipient',
										'data_args'     => $member_id,
									), true, true );
									module_newsletter::subscribe_member( $email, $newsletter_member_id );
									// now add thsi member to the grups they have selected.
									if ( isset( $member['group'] ) && is_array( $member['group'] ) ) {
										$group_items      = module_group::get_groups( 'newsletter_subscription' );
										$public_group_ids = array();
										foreach ( $group_items as $group_item ) {
											$public_group_ids[ $group_item['group_id'] ] = true;
											// remove user group all these groups.
											module_group::delete_member( $member_id, 'newsletter_subscription' );
										}

										//print_r($member['group']);print_r($public_group_ids);exit;
										foreach ( $member['group'] as $group_id => $tf ) {
											if ( $tf && isset( $public_group_ids[ $group_id ] ) ) {
												// add member to group - but only public group ids!
												module_group::add_to_group( $group_id, $member_id );
											}
										}
									}
								}

								// is the newsletter module giving us a subscription redirection?
								if ( $adding_new_member ) {
									if ( module_config::c( 'newsletter_subscribe_redirect', '' ) ) {
										redirect_browser( module_config::c( 'newsletter_subscribe_redirect', '' ) );
									}

									$template             = module_template::get_template_by_key( 'member_subscription_success' );
									$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
									$template->assign_values( array( 'email' => $email ) );
									echo $template->render( 'pretty_html' );
									exit;
								} else {
									if ( module_config::c( 'newsletter_update_details_redirect', '' ) ) {
										redirect_browser( module_config::c( 'newsletter_update_details_redirect', '' ) );
									}
									$template             = module_template::get_template_by_key( 'member_update_details_success' );
									$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
									$template->assign_values( array( 'email' => $email ) );
									echo $template->render( 'pretty_html' );
									exit;
								}

							} else {
								echo 'database failure.. please try again.';
							}

						} else {
							$template             = module_template::get_template_by_key( 'member_subscription_error' );
							$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
							$template->assign_values( array( 'message' => _l( 'Sorry please go back and complete all required fields (especially email address)' ) ) );
							echo $template->render( 'pretty_html' );
							exit;
						}
					} else {
						$template             = module_template::get_template_by_key( 'member_subscription_error' );
						$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
						$template->assign_values( array( 'message' => _l( 'Sorry please go back and complete all required fields' ) ) );
						echo $template->render( 'pretty_html' );
						exit;
					}
				} else {

					$template             = module_template::get_template_by_key( 'member_subscription_form' );
					$template->page_title = htmlspecialchars( _l( 'Subscription' ) );
					// we also treat this as a subscription modification form.
					$newsletter_subscriptions = array();
					$member                   = array(
						'email'      => '',
						'first_name' => '',
						'last_name'  => '',
						'business'   => '',
						'phone'      => '',
						'mobile'     => '',
					);
					// extra fields:
					$extra_fields = module_extra::get_defaults( 'member' );
					foreach ( $extra_fields as $extra_field ) {
						$member[ $extra_field['key'] ] = '';
					}
					if ( $provided_member_id && $hash ) {
						$real_hash = $this->link_public_details( $provided_member_id, true );
						if ( $real_hash == $hash ) {
							// we can load these details into the forum successfully.
							$member = array_merge( $member, $this->get_member( $provided_member_id ) );
							// get their fields:
							$extra_fields = module_extra::get_extras( array(
								'owner_table' => 'member',
								'owner_id'    => $provided_member_id
							) );
							foreach ( $extra_fields as $extra_field ) {
								$member[ $extra_field['extra_key'] ] = $extra_field['extra'];
							}
							// find out what newsletter subscriptions this member has.
							if ( class_exists( 'module_newsletter', false ) ) {
								$newsletter_member_id     = module_newsletter::member_from_email( $member, true, true );
								$newsletter_subscriptions = module_group::get_member_groups( 'newsletter_subscription', $provided_member_id );
							}
						}
					}

					$template->assign_values( $member );

					if ( class_exists( 'module_newsletter', false ) ) {
						$group_items = module_group::get_groups( 'newsletter_subscription' );
						ob_start();
						foreach ( $group_items as $group_item ) {
							?>
							<div class="group_select">
								<input type="checkbox" name="member[group][<?php echo $group_item['group_id']; ?>]"
								       value="1"<?php foreach ( $newsletter_subscriptions as $newsletter_subscription ) {
									if ( $newsletter_subscription['group_id'] == $group_item['group_id'] ) {
										echo ' checked';
									}
								} ?> > <?php echo htmlspecialchars( $group_item['name'] ); ?>
							</div>
							<?php
						}
						$template->assign_values( array( 'newsletter_options' => ob_get_clean() ) );
					} else {
						$template->assign_values( array( 'newsletter_options' => '' ) );
					}

					echo $template->render( 'pretty_html' );
					exit;

				}


				break;
		}
	}

	public static function handle_bulk_delete( $rows ) {
		if ( module_form::confirm_delete( 'bulk_member_array', "Really delete all " . count( $rows ) . " selected members?", $_SERVER['REQUEST_URI'] ) ) {
			foreach ( $rows as $member_to_delete ) {
				self::delete_member( $member_to_delete['member_id'] );
			}
			set_message( "Selected members deleted successfully" );
			redirect_browser( self::link_open( false ) );
		}
	}

	public static function handle_bulk_delete_double_optin( $rows ) {
		$delete = array();
		foreach ( $rows as $member_to_delete ) {
			$newsletter_member_id = module_newsletter::member_from_email( $member_to_delete, false );
			if ( $newsletter_member_id ) {
				if ( $res = module_newsletter::is_member_unsubscribed( $newsletter_member_id, $member_to_delete ) ) {
					if ( class_exists( 'module_subscription', false ) ) {
						// check this isn't a member from a subscription or something.
						$sub = module_subscription::get_subscriptions_by( 'member', $member_to_delete['member_id'] );
						if ( count( $sub ) ) {
							continue;
						}
					}
					if ( isset( $res['reason'] ) && $res['reason'] == 'doubleoptin' ) {
						//delete this onee!
						$delete[] = array(
							'member_id' => $member_to_delete['member_id']
						);
					}
				}
			}
		}
		if ( module_form::confirm_delete( 'bulk_optin_array', "Really delete all " . count( $delete ) . " failed double-opt-in members?", $_SERVER['REQUEST_URI'] ) ) {
			foreach ( $delete as $member_to_delete ) {
				self::delete_member( $member_to_delete['member_id'] );
			}
			set_message( "Selected members deleted successfully" );
			redirect_browser( self::link_open( false ) );
		}
	}

	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'member' );
		if ( ! isset( $fields['business'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'member` ADD `business` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `last_name`;';
		}
		if ( ! isset( $fields['phone'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'member` ADD `phone` VARCHAR(255) NOT NULL DEFAULT \'\' AFTER `last_name`;';
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>member` (
		`member_id` int(11) NOT NULL auto_increment,
		`first_name` varchar(255) NOT NULL DEFAULT '',
		`last_name` varchar(255) NOT NULL DEFAULT '',
		`business` varchar(255) NOT NULL DEFAULT '',
		`email` varchar(255) NOT NULL DEFAULT '',
		`phone` varchar(255) NOT NULL DEFAULT '',
		`mobile` varchar(255) NOT NULL DEFAULT '',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`member_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		<?php
		return ob_get_clean();
	}


}
