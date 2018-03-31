<?php

function sort_groups( $a, $b ) {
	return $a['group_time'] < $b['group_time'];
}

class module_group extends module_base {

	var $links;
	public $version = 2.198;
	// 2.198 - 2016-11-01 - layout improvement
	// 2.197 - 2016-07-10 - big update to mysqli
	// 2.196 - 2016-03-29 - autocomplete fix
	// 2.195 - 2016-01-25 - placeholder text

	// 2.15 - group id as get_groups() index for easier drop down searching.
	// 2.16 - better fine tuning of group permissions
	// 2.17 - deleting members from groups when they have been deleted (eg: delete customer)
	// 2.18 - extra feature for searching which groups a member is part of.
	// 2.181 - permission fix
	// 2.182 - bulk actions addition
	// 2.183 - customer contact last name correctly flows through to newsletter system now.
	// 2.184 - mobile fix
	// 2.185 - 2013-06-14 - newsletter send to all contacts under grouped customer
	// 2.186 - 2013-07-01 - deleted member fix
	// 2.187 - 2013-08-31 - bulk operations improvement
	// 2.188 - 2013-11-15 - working on new UI
	// 2.189 - 2014-02-05 - newsletter speed improvements
	// 2.19 - 2014-04-05 - group ordering alphabetically
	// 2.191 - 2014-05-12 - bulk operations fix
	// 2.192 - 2014-07-28 - group speed improvements
	// 2.193 - 2015-04-27 - responsive improvements
	// 2.194 - 2015-06-08 - quick settings button

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
		$this->module_name     = "group";
		$this->module_position = 8882;


		if ( self::can_i( 'edit', 'Groups' ) ) {
			$this->links[] = array(
				"name"                => "Groups",
				"p"                   => "group_settings",
				"icon"                => "icon.png",
				"args"                => array( 'group_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}
		// check for any post backs that are saving group information.
		if ( isset( $_REQUEST['group_module'] ) && is_array( $_REQUEST['group_module'] ) ) {
			foreach ( $_REQUEST['group_module'] as $owner_table => $groups ) {
				foreach ( $groups as $group_id => $owner_id ) {
					if ( $group_id == 'new' ) {
						// is user creating a new group for this owner table?
						$name = trim( $_REQUEST['group_module_name'][ $owner_table ][ $group_id ] );
						if ( strlen( $name ) > 0 ) {
							// new group!
							// update: only if checkbox selected
							if ( isset( $_REQUEST['used_group_module'][ $owner_table ]['new'] ) ) {
								$group_id = update_insert( 'group_id', 'new', 'group', array(
									'name'        => $name,
									'owner_table' => $owner_table,
								) );
								if ( isset( $_REQUEST['used_group_module'][ $owner_table ]['new'] ) ) {
									$_REQUEST['used_group_module'][ $owner_table ][ $group_id ] = $_REQUEST['used_group_module'][ $owner_table ]['new'];
								}
							}
						}
						if ( ! $group_id || $group_id == 'new' ) {
							continue;
						}
					}
					if ( isset( $_REQUEST['used_group_module'][ $owner_table ][ $group_id ] ) ) {
						$sql = "REPLACE INTO `" . _DB_PREFIX . "group_member` SET ";
						$sql .= " `group_id` = '" . (int) $group_id . "', ";
						$sql .= " `owner_id` = '" . (int) $owner_id . "', ";
						$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "' ";
						query( $sql );
					} else {
						$sql = "DELETE FROM`" . _DB_PREFIX . "group_member` WHERE ";
						$sql .= " `group_id` = '" . (int) $group_id . "' AND ";
						$sql .= " `owner_id` = '" . (int) $owner_id . "' AND ";
						$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "' ";
						query( $sql );
					}
				}
			}
		}
	}


	public static function link_generate( $group_id = false, $options = array(), $link_options = array() ) {

		$key = 'group_id';
		if ( $group_id === false && $link_options ) {
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
			$options['type'] = 'group';
		}
		$options['page'] = 'group_settings';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['group_id'] = $group_id;
		$options['module']                = 'group';
		$data                             = self::get_group( $group_id );
		$options['data']                  = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		//if(isset($data['group_id']) && $data['group_id']>0){
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'group_id',
		);
		// }
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

	public static function link_open( $group_id, $full = false ) {
		return self::link_generate( $group_id, array( 'full' => $full ) );
	}


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['group_id'] ) {
			$data = self::get_group( $_REQUEST['group_id'] );
			if ( module_form::confirm_delete( 'group_id', "Really delete group: " . $data['name'], self::link_open( $_REQUEST['group_id'] ) ) ) {
				$this->delete_group( $_REQUEST['group_id'] );
				set_message( "group deleted successfully" );
				redirect_browser( $this->link_open( false ) );
			}
		} else if ( 'save_group' == $_REQUEST['_process'] ) {
			$group_id = update_insert( 'group_id', $_REQUEST['group_id'], 'group', $_POST );
			set_message( 'Group saved successfully' );
			redirect_browser( $this->link_open( $group_id ) );
		}

	}


	public static function display_groups( $options ) {
		//if(!self::can_i('view','Group','Groups'))return '';
		$owner_id    = ( isset( $options['owner_id'] ) && $options['owner_id'] ) ? (int) $options['owner_id'] : false;
		$owner_table = ( isset( $options['owner_table'] ) && $options['owner_table'] ) ? $options['owner_table'] : false;


		//default to true.
		$can_create = $can_edit = $can_view = $can_delete = true;

		if ( $options && isset( $options['bypass_security'] ) ) {
			// do nothing?
		} else if ( isset( $options ) && isset( $options['owner_table'] ) && $options['owner_table'] && isset( $options['title'] ) && $options['title'] ) {
			global $plugins;
			if ( isset( $plugins[ $options['owner_table'] ] ) ) {
				$can_create = $can_edit = $can_view = $can_delete = false; // default to false if permissions exist.
				$can_view   = $plugins[ $options['owner_table'] ]->can_i( 'view', $options['title'] );
				$can_edit   = $plugins[ $options['owner_table'] ]->can_i( 'edit', $options['title'] );
				$can_create = $plugins[ $options['owner_table'] ]->can_i( 'create', $options['title'] );
				$can_delete = $plugins[ $options['owner_table'] ]->can_i( 'delete', $options['title'] );
			}
		}

		if ( ! $can_view ) {
			return;
		}

		//$html = '';
		if ( $owner_id && $owner_table ) {
			// we have all that we need to display some groups!! yey!!
			// get a list of all groups.

			$fieldset_data      = array();
			$responsive_summary = array();
			$group_items        = self::get_groups( $owner_table );
			foreach ( $group_items as $key => $group_item ) {
				$group_id = (int) $group_item['group_id'];
				$sql      = "SELECT * FROM `" . _DB_PREFIX . "group_member` gm WHERE `group_id` = $group_id AND `owner_id` = '" . db_escape( $owner_id ) . "' AND owner_table = '" . db_escape( $owner_table ) . "'";
				$res      = query( $sql );
				if ( mysqli_num_rows( $res ) ) {
					$group_items[ $key ]['checked'] = true;
					$responsive_summary[]           = htmlspecialchars( $group_item['name'] );
				}
				mysqli_free_result( $res );
			}

			if ( isset( $options['title'] ) && $options['title'] ) {
				$fieldset_data['heading'] = array(
					'title'      => $options['title'],
					'type'       => 'h3',
					'responsive' => array(
						'summary' => implode( ', ', $responsive_summary ),
					),
				);
			}
			ob_start();
			//<div class="content_box_wheader">
			?>
			<table class="tableclass tableclass_full tableclass_form group_selection">
				<tbody>
				<?php
				if ( isset( $options['description'] ) && $options['description'] ) { ?>
					<tr>
						<td colspan="2">
							<?php echo $options['description']; ?>
						</td>
					</tr>
				<?php }
				foreach ( $group_items as $group_item ) {
					$group_id = $group_item['group_id'];
					?>
					<tr id="group_<?php echo $group_id; ?>">
						<th
							class="width1"<?php if ( self::can_i( 'edit', 'Groups' ) ) { ?> data-settings-url="<?php echo module_group::link_open( $group_id, false ); ?>" <?php } ?>>
							<?php if ( $can_edit ) { ?>
								<input type="hidden" name="group_module[<?php echo $owner_table; ?>][<?php echo $group_id; ?>]"
								       value="<?php echo htmlspecialchars( $owner_id ); ?>">
								<input type="checkbox" name="used_group_module[<?php echo $owner_table; ?>][<?php echo $group_id; ?>]"
								       id="groupchk<?php echo $owner_table . $group_id; ?>"
								       value="<?php echo htmlspecialchars( $owner_id ); ?>" <?php echo isset( $group_item['checked'] ) && $group_item['checked'] ? ' checked' : ''; ?>>
							<?php } else { ?>
								<input type="checkbox" name="group"
								       value="<?php echo htmlspecialchars( $owner_id ); ?>" <?php echo isset( $group_item['checked'] ) && $group_item['checked'] ? ' checked' : ''; ?>
								       disabled="">
							<?php } ?>
						</th>
						<td>
							<label
								for="groupchk<?php echo $owner_table . $group_id; ?>"><?php echo htmlspecialchars( $group_item['name'] ); ?></label>
						</td>
					</tr>
					<?php
				}
				if ( $can_create && module_security::is_page_editable() && get_display_mode() != 'mobile' ) {
					$group_id = 'new';
					?>
					<tr id="group_<?php echo $group_id; ?>">
						<th class="width1">
							<input type="hidden" name="group_module[<?php echo $owner_table; ?>][<?php echo $group_id; ?>]"
							       value="<?php echo htmlspecialchars( $owner_id ); ?>">
							<input type="checkbox" name="used_group_module[<?php echo $owner_table; ?>][<?php echo $group_id; ?>]"
							       id="groupchk<?php echo $owner_table . $group_id; ?>"
							       value="<?php echo htmlspecialchars( $owner_id ); ?>">
						</th>
						<td>
							<label for="groupchk<?php echo $owner_table . $group_id; ?>"></label>
							<input type="text" name="group_module_name[<?php echo $owner_table; ?>][<?php echo $group_id; ?>]"
							       autocomplete="off" placeholder="<?php _e( 'New Group' ); ?>"
							       onkeyup="$('#groupchk<?php echo $owner_table . $group_id; ?>').prop('checked',true);">
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
			<?php
			//</div>
			$fieldset_data['elements_before'] = ob_get_clean();
			echo module_form::generate_fieldset( $fieldset_data );
		}
		//print $html;
	}

	public static function save_groups( $owner_table, $owner_key, $owner_id ) {
		if ( isset( $_REQUEST[ 'group_' . $owner_table . '_field' ] ) && is_array( $_REQUEST[ 'group_' . $owner_table . '_field' ] ) ) {
			$owner_id = (int) $owner_id;
			if ( $owner_id <= 0 ) {
				if ( isset( $_REQUEST[ $owner_key ] ) ) {
					$owner_id = (int) $_REQUEST[ $owner_key ];
				}
			}
			if ( $owner_id <= 0 ) {
				return;
			} // failed for some reason?
			$existing_groups = self::get_groups( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
			foreach ( $_REQUEST[ 'group_' . $owner_table . '_field' ] as $group_id => $group_data ) {
				$key = trim( $group_data['key'] );
				if ( ! $key ) {
					unset( $_REQUEST[ 'group_' . $owner_table . '_field' ][ $group_id ] );
					continue;
				}
				$group_id = (int) $group_id;
				$group_db = array(
					'group_key'   => $group_data['key'],
					'group'       => $group_data['val'],
					'owner_table' => $owner_table,
					'owner_id'    => $owner_id,
				);
				$group_id = update_insert( 'group_id', $group_id, 'group', $group_db );
			}
			// work out which ones were not saved.
			foreach ( $existing_groups as $existing_group ) {
				if ( ! isset( $_REQUEST[ 'group_' . $owner_table . '_field' ][ $existing_group['group_id'] ] ) ) {
					// remove it.
					$sql = "DELETE FROM " . _DB_PREFIX . "group WHERE group_id = '" . (int) $existing_group['group_id'] . "' LIMIT 1";
					query( $sql );
				}
			}
		}
	}

	public static function delete_group( $group_id ) {
		$sql = "DELETE FROM `" . _DB_PREFIX . "group` WHERE `group_id` = " . (int) $group_id . "";
		query( $sql );
		$sql = "DELETE FROM `" . _DB_PREFIX . "group_member` WHERE `group_id` = " . (int) $group_id . "";
		query( $sql );

	}

	public static function delete_groups( $owner_table, $owner_key, $owner_id ) {
		$group_items = self::get_groups( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
		foreach ( $group_items as $group_item ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "group WHERE group_id = '" . (int) $group_item['group_id'] . "' LIMIT 1";
			query( $sql );
		}

	}

	public static $group_cache = array();

	public static function get_group( $group_id ) {
		if ( isset( self::$group_cache[ $group_id ] ) ) {
			return self::$group_cache[ $group_id ];
		}
		self::$group_cache[ $group_id ] = get_single( "group", "group_id", $group_id );

		return self::$group_cache[ $group_id ];
	}

	public static function get_groups( $owner_table = false ) {
		$sql = "SELECT g.*, g.group_id AS id ";
		$sql .= " , COUNT(gm.group_id) AS `count` ";
		$sql .= " FROM `" . _DB_PREFIX . "group` g ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm USING (group_id) ";
		if ( $owner_table ) {
			$sql .= " WHERE g.owner_table = '" . db_escape( $owner_table ) . "'";
		}
		$sql .= " GROUP BY g.group_id ";
		$sql .= " ORDER BY g.name";

		return qa( $sql );
	}

	public static function get_groups_search( $search = false ) {
		$groups = get_multiple( 'group_member', $search );
		foreach ( $groups as $group_id => $group ) {
			// find the group name for this group.
			$groups[ $group_id ] = array_merge( $group, self::get_group( $group['group_id'] ) );
		}

		return $groups;
	}

	/**
	 * @static
	 *
	 * @param $args
	 *
	 * @return array
	 *
	 * The newsletter system requests updated customer / user data from this group plugin.
	 * It does this when building the member list, and also
	 */
	public static function newsletter_callback( $args ) {
		if ( ! isset( $args['owner_table'] ) || ! $args['owner_table'] ) {
			return array();
		}
		switch ( $args['owner_table'] ) {
			case 'user':
				if ( (int) $args['owner_id'] > 0 ) {
					$sql  = "SELECT c.customer_name AS company_name, c.customer_name AS customer_name";
					$sql  .= " , pu.user_id ";
					$sql  .= " , c.customer_id ";
					$sql  .= " ,c.credit ";
					$sql  .= " , pu.name AS user_name, pu.name AS first_name, pu.last_name AS last_name, pu.phone AS phone, pu.`email` AS `email`, pu.`mobile` AS `mobile`";
					$sql  .= " , a.line_1, a.line_2, a.suburb, a.state, a.region, a.country, a.post_code ";
					$sql  .= ' FROM `' . _DB_PREFIX . "user` pu";
					$sql  .= " LEFT JOIN `" . _DB_PREFIX . "customer` c ON pu.customer_id = c.customer_id";
					$sql  .= ' LEFT JOIN `' . _DB_PREFIX . "address` a ON c.customer_id = a.owner_id AND a.owner_table = 'customer' AND a.address_type = 'physical'";
					$sql  .= " WHERE pu.user_id = " . (int) $args['owner_id'];
					$user = qa1( $sql );
					if ( ! is_array( $user ) || ! isset( $user['user_id'] ) || ! $user['user_id'] ) {
						return false;
					}
					if ( isset( $args['basic'] ) && $args['basic'] ) {
						return $user;
					}
					//                    $name_parts = explode(" ",preg_replace('/\s+/',' ',$user['user_name']));
					//                    $user['first_name'] = array_shift($name_parts);
					//                    $user['last_name'] = implode(' ',$name_parts);
					// get extras for the user.
					$extras = module_extra::get_extras( array( 'owner_table' => 'user', 'owner_id' => $user['user_id'] ) );
					foreach ( $extras as $extra ) {
						if ( ! strlen( trim( $extra['extra'] ) ) ) {
							continue;
						}
						$key = $extra['extra_key'];
						$x   = 1;
						while ( isset( $user[ $key ] ) ) {
							$key = $extra['extra_key'] . $x;
							$x ++;
						}
						$user[ $key ] = trim( $extra['extra'] );
					}
					// get extras for the customer.
					if ( isset( $user['customer_id'] ) && $user['customer_id'] > 0 ) {
						$extras = module_extra::get_extras( array(
							'owner_table' => 'customer',
							'owner_id'    => $user['customer_id']
						) );

						foreach ( $extras as $extra ) {
							if ( ! strlen( trim( $extra['extra'] ) ) ) {
								continue;
							}
							$key = $extra['extra_key'];
							$x   = 1;
							while ( isset( $user[ $key ] ) ) {
								$key = $extra['extra_key'] . $x;
								$x ++;
							}
							$user[ $key ] = trim( $extra['extra'] );
						}
					}
					if ( $user['customer_id'] ) {
						$user['_edit_link'] = module_user::link_open_contact( $user['user_id'], false, $user );
					} else {
						$user['_edit_link'] = module_user::link_open( $user['user_id'], false, $user );
					}

					return $user;
				}
				break;
			case 'customer':
				if ( module_config::c( 'newsletter_send_all_customer_contacts', 1 ) ) {
					// update - we use the above 'user' callback and return a listing for each contact in the array.
					// using the special _multi flag hack to tell our newsletter plugin that this result contains multiple entries.
					$users    = array(
						'_multi' => true,
					);
					$sql      = "SELECT u.user_id FROM `" . _DB_PREFIX . "user` u WHERE u.customer_id = " . (int) $args['owner_id'];
					$contacts = qa( $sql );
					foreach ( $contacts as $contact ) {
						$data_args                    = array(
							'owner_id'    => $contact['user_id'],
							'owner_table' => 'user',
						);
						$users[ $contact['user_id'] ] = self::newsletter_callback( $data_args );
						if ( $users[ $contact['user_id'] ] ) {
							$users[ $contact['user_id'] ]['data_args'] = json_encode( $data_args );
						}
					}

					return $users;
				} else {

					$sql  = "SELECT c.customer_name AS company_name, c.customer_name AS customer_name";
					$sql  .= " ,c.credit ";
					$sql  .= " , pu.user_id ";
					$sql  .= " , c.customer_id ";
					$sql  .= " , pu.name AS user_name, pu.name AS first_name, pu.last_name AS last_name, pu.phone AS phone, pu.`email` AS `email`, pu.`mobile` AS `mobile`";
					$sql  .= " , a.line_1, a.line_2, a.suburb, a.state, a.region, a.country, a.post_code ";
					$sql  .= " FROM `" . _DB_PREFIX . "customer` c ";
					$sql  .= ' LEFT JOIN `' . _DB_PREFIX . "address` a ON c.customer_id = a.owner_id AND a.owner_table = 'customer' AND a.address_type = 'physical'";
					$sql  .= ' LEFT JOIN `' . _DB_PREFIX . "user` pu ON c.primary_user_id = pu.user_id";
					$sql  .= " WHERE c.customer_id = " . (int) $args['owner_id'];
					$user = qa1( $sql );
					if ( ! $user || ! isset( $user['customer_id'] ) ) {
						return array();
					}
					//$name_parts = explode(" ",preg_replace('/\s+/',' ',$user['user_name']));
					//$user['first_name'] = array_shift($name_parts);
					//$user['last_name'] = implode(' ',$name_parts);

					if ( isset( $args['basic'] ) && $args['basic'] ) {
						return $user;
					}
					// get extras for the customer.
					$extras = module_extra::get_extras( array(
						'owner_table' => 'customer',
						'owner_id'    => $user['customer_id']
					) );
					foreach ( $extras as $extra ) {
						if ( ! strlen( trim( $extra['extra'] ) ) ) {
							continue;
						}
						$key = $extra['extra_key'];
						$x   = 1;
						while ( isset( $user[ $key ] ) ) {
							$key = $extra['extra_key'] . $x;
							$x ++;
						}
						$user[ $key ] = trim( $extra['extra'] );
					}
					if ( isset( $user['user_id'] ) && $user['user_id'] > 0 ) {
						// get extras for the user.
						$extras = module_extra::get_extras( array( 'owner_table' => 'user', 'owner_id' => $user['user_id'] ) );
						foreach ( $extras as $extra ) {
							if ( ! strlen( trim( $extra['extra'] ) ) ) {
								continue;
							}
							$key = $extra['extra_key'];
							$x   = 1;
							while ( isset( $user[ $key ] ) ) {
								$key = $extra['extra_key'] . $x;
								$x ++;
							}
							$user[ $key ] = trim( $extra['extra'] );
						}
					}
					$user['_edit_link'] = module_customer::link_open( $user['customer_id'], false, $user );

					return $user;
				}
			case 'website':
				$sql                = "SELECT c.customer_name AS company_name";
				$sql                .= " ,c.credit ";
				$sql                .= " ,w.name AS website_name";
				$sql                .= " ,w.url AS website_url";
				$sql                .= " , pu.user_id ";
				$sql                .= " , c.customer_id ";
				$sql                .= " , pu.name AS user_name, pu.phone AS phone, pu.`email` AS `email`, pu.`mobile` AS `mobile`";
				$sql                .= " , a.line_1, a.line_2, a.suburb, a.state, a.region, a.country, a.post_code ";
				$sql                .= " FROM `" . _DB_PREFIX . "website` w ";
				$sql                .= ' LEFT JOIN `' . _DB_PREFIX . "customer` c ON w.customer_id = c.customer_id";
				$sql                .= ' LEFT JOIN `' . _DB_PREFIX . "address` a ON c.customer_id = a.owner_id AND a.owner_table = 'customer' AND a.address_type = 'physical'";
				$sql                .= ' LEFT JOIN `' . _DB_PREFIX . "user` pu ON c.primary_user_id = pu.user_id";
				$sql                .= " WHERE w.website_id = " . (int) $args['owner_id'];
				$user               = qa1( $sql );
				$name_parts         = explode( " ", preg_replace( '/\s+/', ' ', $user['user_name'] ) );
				$user['first_name'] = array_shift( $name_parts );
				$user['last_name']  = implode( ' ', $name_parts );

				if ( isset( $args['basic'] ) && $args['basic'] ) {
					return $user;
				}
				// get extras for the website.
				$extras = module_extra::get_extras( array( 'owner_table' => 'website', 'owner_id' => $args['owner_id'] ) );
				foreach ( $extras as $extra ) {
					if ( ! strlen( trim( $extra['extra'] ) ) ) {
						continue;
					}
					$key = $extra['extra_key'];
					$x   = 1;
					while ( isset( $user[ $key ] ) ) {
						$key = $extra['extra_key'] . $x;
						$x ++;
					}
					$user[ $key ] = trim( $extra['extra'] );
				}
				// then get extras for the company
				$extras = module_extra::get_extras( array( 'owner_table' => 'customer', 'owner_id' => $user['customer_id'] ) );
				foreach ( $extras as $extra ) {
					if ( ! strlen( trim( $extra['extra'] ) ) ) {
						continue;
					}
					$key = $extra['extra_key'];
					$x   = 1;
					while ( isset( $user[ $key ] ) ) {
						$key = $extra['extra_key'] . $x;
						$x ++;
					}
					$user[ $key ] = trim( $extra['extra'] );
				}
				if ( isset( $user['user_id'] ) && $user['user_id'] > 0 ) {
					// get extras for the user.
					$extras = module_extra::get_extras( array( 'owner_table' => 'user', 'owner_id' => $user['user_id'] ) );
					foreach ( $extras as $extra ) {
						if ( ! strlen( trim( $extra['extra'] ) ) ) {
							continue;
						}
						$key = $extra['extra_key'];
						$x   = 1;
						while ( isset( $user[ $key ] ) ) {
							$key = $extra['extra_key'] . $x;
							$x ++;
						}
						$user[ $key ] = trim( $extra['extra'] );
					}
				}
				$user['_edit_link'] = module_customer::link_open( $user['customer_id'], false, $user );

				return $user;
			case 'ticket':
				//echo 'Getting ticket for '.$args['owner_id'] . ' and basic is '.var_export($args['basic'],true);exit;
				return module_ticket::get_newsletter_recipient( $args['owner_id'], isset( $args['basic'] ) && $args['basic'] );
			case 'member':
				return module_member::get_newsletter_recipient( $args['owner_id'], isset( $args['basic'] ) && $args['basic'] );
			case 'newsletter_subscription':
				return module_member::get_newsletter_recipient( $args['owner_id'], isset( $args['basic'] ) && $args['basic'] );

		}

		return array();
	}

	public static function get_members( $group_id ) {
		$sql = "SELECT gm.group_id, gm.owner_id, gm.owner_table ";
		$sql .= " FROM `" . _DB_PREFIX . "group_member` gm ";
		$sql .= " WHERE gm.group_id = " . (int) $group_id;

		return qa( $sql );
	}

	public static function get_member_groups( $owner_table, $owner_id ) {
		$sql = "SELECT * FROM `" . _DB_PREFIX . "group_member` WHERE ";
		$sql .= " `owner_id` = '" . (int) $owner_id . "' AND ";
		$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "'";

		return qa( $sql );
	}

	public static function delete_member( $owner_id, $owner_table ) {
		$sql = "DELETE FROM `" . _DB_PREFIX . "group_member` WHERE ";
		$sql .= " `owner_id` = '" . (int) $owner_id . "' AND ";
		$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "'";
		query( $sql );
	}

	public static function add_to_group( $group_id, $owner_id, $owner_table = false ) {
		if ( $group_id > 0 && $owner_id > 0 ) {
			if ( ! $owner_table ) {
				$group       = get_single( 'group', 'group_id', $group_id );
				$owner_table = $group['owner_table'];
			}
			$sql = "REPLACE INTO `" . _DB_PREFIX . "group_member` SET ";
			$sql .= " `group_id` = '" . (int) $group_id . "', ";
			$sql .= " `owner_id` = '" . (int) $owner_id . "', ";
			$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "'";
			query( $sql );
		}
	}


	static $pagination_options = array();

	public static function run_pagination_hook( &$rows ) {

		if ( isset( $_REQUEST['bulk_action_go'] ) && $_REQUEST['bulk_action_go'] == 'yes' ) {
			// we are posting back tot his script with a go!
			if ( $rows instanceof mysqli_result ) {
				$new_rows = array();
				while ( $row = mysqli_fetch_assoc( $rows ) ) {
					$new_rows[] = $row;
				}
				$rows = $new_rows;
			} else {
				// rows stays the same.
			}
			// add these items to the group.
			if ( is_array( $rows ) && count( $rows ) ) {
				if ( isset( $_REQUEST['bulk_action'] ) && is_array( $_REQUEST['bulk_action'] ) ) {
					foreach ( $_REQUEST['bulk_action'] as $bulk_action_id => $flag ) {
						if ( $bulk_action_id && $flag == 'yes' ) {
							// do this action! run the callback with $rows array
							if ( isset( self::$pagination_options['bulk_actions'][ $bulk_action_id ] ) && self::$pagination_options['bulk_actions'][ $bulk_action_id ]['callback'] ) {
								call_user_func( self::$pagination_options['bulk_actions'][ $bulk_action_id ]['callback'], $rows );
							}
						}
					}
				}
			}
		}
		if ( isset( $_REQUEST['add_group_go'] ) && $_REQUEST['add_group_go'] == 'yes' ) {
			// we are posting back tot his script with a go!
			if ( $rows instanceof mysqli_result ) {
				$new_rows = array();
				while ( $row = mysqli_fetch_assoc( $rows ) ) {
					$new_rows[] = $row;
				}
				$rows = $new_rows;
			} else {
				// rows stays the same.
			}
			// add these items to the group.
			if ( is_array( $rows ) && count( $rows ) ) {
				if ( isset( $_REQUEST['add_to_group'] ) && is_array( $_REQUEST['add_to_group'] ) ) {
					foreach ( $_REQUEST['add_to_group'] as $group_id => $flag ) {
						if ( (int) $group_id > 0 && $flag == 'yes' ) {
							// add these rows to this group
							foreach ( $rows as $row ) {
								$owner_id    = (int) $row[ self::$pagination_options['fields']['owner_id'] ];
								$owner_table = trim( (string) self::$pagination_options['fields']['owner_table'] );
								if ( $owner_id > 0 && strlen( $owner_table ) > 0 ) {
									$sql = "REPLACE INTO `" . _DB_PREFIX . "group_member` SET ";
									$sql .= " `group_id` = '" . (int) $group_id . "', ";
									$sql .= " `owner_id` = '" . (int) $owner_id . "', ";
									$sql .= " `owner_table` = '" . db_escape( $owner_table ) . "' ";
									// dont need this any more, doing a newsletter_callback which is much better.
									//$sql .= ", `db_fields` = '".db_escape(serialize(self::$pagination_options))."' ";
									query( $sql );
								}
							}
						}
					}
				}
			}
		}
	}

	public static function display_pagination_hook() {

		if ( isset( self::$pagination_options['fields'] ) ) {
			$owner_table = (string) self::$pagination_options['fields']['owner_table'];
			global $plugins;
			if ( isset( $plugins[ $owner_table ] ) && isset( self::$pagination_options['fields']['title'] ) && self::$pagination_options['fields']['title'] ) {
				$can_view = $plugins[ $owner_table ]->can_i( 'view', self::$pagination_options['fields']['title'] );
				if ( ! $can_view ) {
					return '';
				}
				/*$can_edit = $plugins[$owner_table]->can_i('edit',self::$pagination_options['fields']['title']);
				$can_create = $plugins[$owner_table]->can_i('create',self::$pagination_options['fields']['title']);
				$can_delete = $plugins[$owner_table]->can_i('delete',self::$pagination_options['fields']['title']);*/
			}

			?>
			<span>
            <a href="#"
               onclick="if($('#group_popdown').css('display')=='inline' || $('#group_popdown').css('display')=='block') $('#group_popdown').css('display','none'); else $('#group_popdown').css('display','inline'); return false;"><?php _e( '(group)' ); ?></a>
            <span id="group_popdown"
                  style="position: absolute; width: 200px; display: none; background: #EFEFEF; margin-left: -210px; margin-top: 30px; border: 1px solid #CCC; text-align: left; padding: 6px; z-index: 3;">
                <strong><?php _e( 'Add all these results to a group:' ); ?></strong><br/>
	            <?php
	            $groups = self::get_groups( trim( (string) self::$pagination_options['fields']['owner_table'] ) );
	            if ( ! count( $groups ) ) {
		            _e( 'Sorry, no groups exist. Please create a group first.' );
	            } else {
	            foreach ( $groups

		            as $group ){
		            $group_id = $group['group_id'];
		            ?>
	            <input type="checkbox" class="add_to_group" name="add_to_group[<?php echo $group['group_id']; ?>]"
	                   id="groupchk<?php echo $group_id; ?>" value="yes">
		            <label for="groupchk<?php echo $group_id; ?>"><?php echo htmlspecialchars( $group['name'] ); ?></label>
	            <br/>
                        <?php
	            }
	            ?>
                    <input type="hidden" name="add_group_go" id="add_group_go" value="">
		            <input type="button" name="add_group_button" id="add_group_button"
		                   value="<?php _e( 'Add to group' ); ?>">
		            <script type="text/javascript">
                        $(function () {
                            $('#add_group_button').click(function () {
                                $('#add_group_go').val('yes');
                                // todo: if no form, create one them submit.
                                $('#add_group_go').parents('form')[0].submit();
                            });
                        });
                    </script>
	            <?php } ?>
            </span>
            </span>
			<?php
		}
		if ( isset( self::$pagination_options['bulk_actions'] ) && count( self::$pagination_options['bulk_actions'] ) ) {

			?>
			<span>
            <a href="#"
               onclick="if($('#bulk_popdown').css('display')=='inline' || $('#bulk_popdown').css('display')=='block') $('#bulk_popdown').css('display','none'); else $('#bulk_popdown').css('display','inline'); return false;"><?php _e( '(bulk actions)' ); ?></a>
            <span id="bulk_popdown"
                  style="position: absolute; width: 200px; display: none; background: #EFEFEF; margin-left: -210px; margin-top: 30px; border: 1px solid #CCC; text-align: left; padding: 6px; z-index: 3;">
                <strong><?php _e( 'Bulk actions:' ); ?></strong><br/>
	            <?php
	            foreach ( self::$pagination_options['bulk_actions'] as $bulk_action_id => $bulk_action_data ) {
		            switch ( $bulk_action_data['type'] ) {
			            case 'delete':
				            ?>
				            <input type="checkbox" class="bulk_action" name="bulk_action[<?php echo $bulk_action_id; ?>]"
				                   id="bulkchk<?php echo $bulk_action_id; ?>" value="yes">
				            <label
					            for="bulkchk<?php echo $bulk_action_id; ?>"><?php _e( $bulk_action_data['label'] ); ?></label>
				            <br/>
				            <?php
				            break;
			            case 'form':
				            ?>
				            <input type="checkbox" class="bulk_action" name="bulk_action[<?php echo $bulk_action_id; ?>]"
				                   id="bulkchk<?php echo $bulk_action_id; ?>" value="yes">
				            <label
					            for="bulkchk<?php echo $bulk_action_id; ?>"><?php _e( $bulk_action_data['label'] ); ?></label>
				            <?php if ( isset( $bulk_action_data['elements'] ) ) {
				            foreach ( $bulk_action_data['elements'] as $element ) {
					            module_form::generate_form_element( $element );
				            }
			            } ?><br/>
				            <?php
				            break;
			            default:
				            ?>
				            <input type="checkbox" class="bulk_action" name="bulk_action[<?php echo $bulk_action_id; ?>]"
				                   id="bulkchk<?php echo $bulk_action_id; ?>" value="yes">
				            <label
					            for="bulkchk<?php echo $bulk_action_id; ?>"><?php _e( $bulk_action_data['label'] ); ?></label>
				            <br/>
				            <?php
				            break;
		            }
		            ?>

		            <?php
	            }
	            ?>
	            <input type="hidden" name="bulk_action_go" id="bulk_action_go" value="">
                    <input type="button" name="bulk_action_button" id="bulk_action_button"
                           value="<?php _e( 'Perform Bulk Actions' ); ?>">
                    <script type="text/javascript">
                        $(function () {
                            $('#bulk_action_button').click(function () {
                                $('#bulk_action_go').val('yes');
                                // todo: if no form, create one them submit.
                                $('#bulk_action_go').parents('form')[0].submit();
                            });
                        });
                    </script>
            </span>
            </span>
			<?php
		}
	}

	public static function enable_pagination_hook( $options = array() ) {
		$GLOBALS['pagination_group_hack'] = true;
		self::$pagination_options         = $options;
	}


	public function get_install_sql() {
		$sql = 'CREATE TABLE `' . _DB_PREFIX . 'group` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT \'\',
  `owner_table` varchar(100) NOT NULL DEFAULT \'\',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';

		$sql .= "\n";

		$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'group_member` (
  `group_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `owner_table` varchar(80) NOT NULL DEFAULT \'\',
  `db_fields` TEXT NOT NULL DEFAULT \'\',
  KEY `group_id` (`group_id`),
  KEY `owner_id` (`owner_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8; ';

		$sql .= "\n";

		$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'group_member` ADD UNIQUE (
        `group_id` ,
        `owner_id` ,
        `owner_table`
        ); ';

		return $sql;
	}

	public static function groups_enabled() {
		return get_display_mode() != 'mobile';
	}
}