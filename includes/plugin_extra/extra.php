<?php

define( '_EXTRA_FIELD_DELIM', '$#%|' );
define( '_EXTRA_DISPLAY_TYPE_COLUMN', 1 );

function sort_extra_defaults( $a, $b ) {
	return $a['order'] > $b['order'];
}

class module_extra extends module_base {

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
		$this->version = 2.265;
		// 2.265 - 2017-02-20 - custom data extra field integration
		// 2.264 - 2016-12-20 - ajax lookup fields
		// 2.263 - 2016-10-25 - extra field update
		// 2.262 - 2016-07-10 - big update to mysqli
		// 2.261 - 2016-04-20 - extra field display on create
		// 2.260 - 2016-02-14 - create extra field bug fix
		// 2.259 - 2016-01-30 - dashboard widget improvement
		// 2.258 - 2016-01-25 - placeholder text
		// 2.257 - 2015-12-26 - extra field ordering fix
		// 2.256 - 2015-12-02 - file extra field type
		// 2.255 - 2015-06-07 - link to settings
		// 2.254 - 2015-04-12 - minor bug fix
		// 2.253 - 2015-03-08 - wysiwyg and dropdown/select extra fields
		// 2.252 - 2015-03-08 - wysiwyg and dropdown/select extra fields
		// 2.251 - 2015-02-05 - text/check extra field types
		// 2.25 - 2015-01-28 - sorting by extra fields
		// 2.249 - 2014-12-22 - extra field dates/delete/list/rename fixes
		// 2.248 - 2014-10-03 - hook_filter_var for better theme support
		// 2.247 - 2014-08-02 - responsive fixes

		// 2.16 - fix to disable editing when page isn't editable. this caused double ups on extra keys in table listings.
		// 2.17 - hooks for the encryption module to take over.
		// 2.18 - bug fix with new extra field types.
		// 2.19 - better saving of extra fields etc.. in sync with member external signup extra field feature
		// 2.2 - started work on sorting extra fields
		// 2.21 - bug fix
		// 2.22 - see Settings-Extra Fields for new options.
		// 2.23 - Extra bug fix
		// 2.24 - permission improvement
		// 2.241 - clickable links in extra fields
		// 2.242 - clickable links in extra fields
		// 2.243 - new delete button in Settings > Extra Fields
		// 2.244 - 2013-11-15 - working on new UI
		// 2.245 - 2013-12-19 - extra fields now available when creating a job
		// 2.246 - 2014-01-23 - searching by extra fields

		$this->links           = array();
		$this->module_name     = "extra";
		$this->module_position = 8882;
		module_config::register_css( 'extra', 'extra.css' );
		module_config::register_js( 'extra', 'extra.js' );
	}

	public function pre_menu() {
		if ( $this->is_installed() && module_config::can_i( 'edit', 'Settings' ) && $this->can_i( 'edit', 'Extra Fields' ) ) {
			$this->links['extra_settings'] = array(
				"name"                => "Extra Fields",
				"p"                   => "extra_settings",
				'args'                => array( 'extra_default_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}
	}

	public function process() {
		if ( 'save_extra_default' == $_REQUEST['_process'] ) {

			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				die( 'No perms to save extra field settings.' );
			}
			if ( isset( $_REQUEST['butt_del'] ) ) {
				if ( module_form::confirm_delete(
					'extra_default_id',
					_l( "Really delete this extra field and ALL extra data linked to this field?" ),
					$_SERVER['REQUEST_URI'] )
				) {
					$extra_default = module_extra::get_extra_default( $_REQUEST['extra_default_id'] );
					if ( $extra_default && $extra_default['extra_default_id'] == $_REQUEST['extra_default_id'] && $extra_default['owner_table'] && $extra_default['extra_key'] ) {
						$extra_values = get_multiple( 'extra', array(
							'owner_table' => $extra_default['owner_table'],
							'extra_key'   => $extra_default['extra_key']
						), 'extra_id', 'exact', 'owner_id' );
						if ( $extra_values ) {
							foreach ( $extra_values as $extra_value ) {
								if ( $extra_value['owner_table'] == $extra_default['owner_table'] && $extra_value['extra_key'] == $extra_default['extra_key'] ) {
									delete_from_db( 'extra', 'extra_id', $extra_value['extra_id'] );
								}
							}
						}
					}
					delete_from_db( 'extra_default', 'extra_default_id', $_REQUEST['extra_default_id'] );
					set_message( 'Extra field deleted successfully.' );
					redirect_browser( str_replace( 'extra_default_id', 'extra_default_id_deleted', $_SERVER['REQUEST_URI'] ) );
				}
			}
			if ( (int) $_REQUEST['extra_default_id'] > 0 ) {
				$extra_default = module_extra::get_extra_default( $_REQUEST['extra_default_id'] );
				if ( $extra_default && $extra_default['extra_default_id'] == $_REQUEST['extra_default_id'] && $extra_default['owner_table'] && $extra_default['extra_key'] ) {
					if ( isset( $_POST['extra_key'] ) && ! empty( $_POST['extra_key'] ) && $_POST['extra_key'] != $extra_default['extra_key'] ) {
						// they have renamed the key, rename all the existing ones in the system.
						$extra_values = get_multiple( 'extra', array(
							'owner_table' => $extra_default['owner_table'],
							'extra_key'   => $extra_default['extra_key']
						), 'extra_id', 'exact', 'owner_id' );
						if ( $extra_values ) {
							foreach ( $extra_values as $extra_value ) {
								if ( $extra_value['owner_table'] == $extra_default['owner_table'] && $extra_value['extra_key'] == $extra_default['extra_key'] ) {
									update_insert( 'extra_id', $extra_value['extra_id'], 'extra', array(
										'extra_key' => $_POST['extra_key'],
									) );
								}
							}
						}
					}
				}
			}

			$data = $_POST;
			if ( isset( $data['options'] ) && is_array( $data['options'] ) ) {
				$data['options'] = json_encode( $data['options'] );
			}
			update_insert( 'extra_default_id', $_REQUEST['extra_default_id'], 'extra_default', $data );
			set_message( 'Extra field saved successfully' );
			redirect_browser( $_SERVER['REQUEST_URI'] );


		}
	}


	public static function link_generate( $extra_default_id = false, $options = array(), $link_options = array() ) {

		$key = 'extra_default_id';
		if ( $extra_default_id === false && $link_options ) {
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
			$options['type'] = 'extra';
		}
		$options['page'] = 'extra_settings';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['extra_default_id'] = $extra_default_id;
		$options['module']                        = 'extra';
		$data                                     = self::get_extra_default( $extra_default_id );
		$options['data']                          = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['extra_key'] ) || ! trim( $data['extra_key'] ) ) ? 'N/A' : htmlspecialchars( $data['extra_key'] );
		//if(isset($data['extra_default_id']) && $data['extra_default_id']>0){
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'extra_default_id',
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

	public static function link_open_extra_default( $extra_default_id, $full = false ) {
		return self::link_generate( $extra_default_id, array( 'full' => $full ) );
	}


	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			$searchable_fields = get_multiple( 'extra_default', array( 'searchable' => 1 ) );
			if ( count( $searchable_fields ) ) {
				$sql = "SELECT * FROM `" . _DB_PREFIX . "extra` ext WHERE ";
				foreach ( $searchable_fields as $searchable_field ) {
					$sql .= ' (ext.`owner_table` = "' . db_escape( $searchable_field['owner_table'] ) . '" AND ext.`extra_key` = "' . db_escape( $searchable_field['extra_key'] ) . '" AND ext.`extra` LIKE "%' . db_escape( $search_key ) . '%") OR ';
				}
				$sql     = rtrim( $sql, ' OR' );
				$results = qa( $sql );
				foreach ( $results as $result ) {
					$match_string          = _shl( htmlspecialchars( $result['extra'] ), $search_key );
					$link                  = '#';
					$result['owner_table'] = preg_replace( '#[^a-z]#', '', $result['owner_table'] );
					if ( is_callable( 'module_' . $result['owner_table'] . '::link_open' ) ) {
						eval( '$link = module_' . $result['owner_table'] . '::link_open(' . $result['owner_id'] . ');' );
					}
					$ajax_results [] = '<a href="' . $link . '">' . ucwords( $result['owner_table'] ) . ' ' . htmlspecialchars( $result['extra_key'] ) . ': ' . $match_string . '</a>';
				}
			}
		}

		return $ajax_results;
	}

	public static function get_display_value( $extra_data ) {
		$value = ! empty( $extra_data['extra'] ) ? $extra_data['extra'] : '';
		// what sort of field is this?
		$default = get_single( 'extra_default', array( 'owner_table', 'extra_key' ), array(
			$extra_data['owner_table'],
			$extra_data['extra_key']
		) );
		if ( $default && $value && ! empty( $default['field_type'] ) ) {
			switch ( $default['field_type'] ) {
				case 'ajax':
					$lookup = @json_decode( $default['options'], true );
					if ( $lookup && ! empty( $lookup['lookup'] ) ) {
						$bits           = explode( '|', $lookup['lookup'] );
						$search_options = array(
							'plugin'      => $bits[0],
							'return_more' => true,
							// return an array of more details so invoice can display extra details. currently only used in 'data' module
							'key'         => ! empty( $bits[2] ) ? $bits[2] : $bits[1],
							'display_key' => $bits[1],
						);
						global $plugins;
						if ( ! empty( $plugins[ $search_options['plugin'] ] ) && $autocomplete_result = $plugins[ $search_options['plugin'] ]->autocomplete_display( $value, $search_options ) ) {
							return $autocomplete_result;
						}
					}
					break;
			}
		}

		return $value;
	}

	public static function display_extras( $options ) {
		$owner_id          = ( isset( $options['owner_id'] ) && $options['owner_id'] ) ? (int) $options['owner_id'] : false;
		$owner_table       = ( isset( $options['owner_table'] ) && $options['owner_table'] ) ? $options['owner_table'] : false;
		$owner_table_child = isset( $options['owner_table_child'] ) ? $options['owner_table_child'] : false;
		$layout            = ( isset( $options['layout'] ) && $options['layout'] ) ? $options['layout'] : false;
		$allow_new         = true;
		if ( isset( $options['allow_new'] ) && ! $options['allow_new'] ) {
			$allow_new = false;
		}
		$allow_edit = ( ! isset( $options['allow_edit'] ) || ( isset( $options['allow_edit'] ) && $options['allow_edit'] ) );
		if ( ! module_security::is_page_editable() ) {
			$allow_edit = false;
		}
		$can_edit_settings = module_config::can_i( 'edit', 'Settings' ) && self::can_i( 'edit', 'Extra Fields' );
		// todo ^^ flow this permission check through to the "save" section.
		$html = '';
		if ( $owner_table ) {
			$default_fields = self::get_defaults( $owner_table, $owner_table_child );
			// we have all that we need to display some extras!! yey!!
			if ( $owner_id ) {
				$extra_items = self::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
				$extra_items = self::sort_extras( $extra_items, $default_fields, $allow_new );
			} else {
				$extra_items = array();
				$extra_items = self::sort_extras( $extra_items, $default_fields, $allow_new );
			}
			$more_fields_available = false;
			foreach ( $extra_items as $extra_item ) {
				$extra_id = $extra_item['extra_id'];
				$id       = 'extra_' . preg_replace( '#\W+#', '_', $extra_item['extra_key'] );
				if ( ! empty( $extra_item['hidden'] ) ) {
					$more_fields_available = true;
				}
				ob_start();
				?>
				<tr id="extra_<?php echo $extra_id; ?>"
				    class="extra_field_row<?php echo ! empty( $extra_item['hidden'] ) ? ' extra_field_row_hidden' : ''; ?>">
					<th <?php if ( $can_edit_settings && isset( $default_fields[ $extra_item['extra_key'] ]['field_type'] ) ) {
						echo ' data-settings-url="' . module_extra::link_open_extra_default( $default_fields[ $extra_item['extra_key'] ]['extra_default_id'], false ) . '"';
					} ?>>
						<?php if ( $allow_new && $allow_edit ) { ?>
							<span class="extra_field_key"
							      onclick="$(this).hide(); $(this).parent().find('input').show();"><?php echo htmlspecialchars( $extra_item['extra_key'] ); ?></span>
							<input type="text" name="extra_<?php echo $owner_table; ?>_field[<?php echo $extra_id; ?>][key]"
							       value="<?php echo htmlspecialchars( $extra_item['extra_key'] ); ?>" class="extra_field"
							       style="display:none;">
						<?php } else {
							echo htmlspecialchars( $extra_item['extra_key'] ); ?>
							<input type="hidden" name="extra_<?php echo $owner_table; ?>_field[<?php echo $extra_id; ?>][key]"
							       value="<?php echo htmlspecialchars( $extra_item['extra_key'] ); ?>">
						<?php } ?>
					</th>
					<td>
						<?php
						if ( $allow_edit ) {
							$field_type = 'text';
							if ( isset( $default_fields[ $extra_item['extra_key'] ]['field_type'] ) ) {
								$field_type = $default_fields[ $extra_item['extra_key'] ]['field_type'];
							}
							if ( ! $field_type ) {
								$field_type = 'text';
							}
							if ( $field_type == 'file' && class_exists( 'module_file', false ) && module_file::is_plugin_enabled() ) {
								module_file::display_files( array(
										//'title' => 'Certificate Files',
										'owner_table' => 'extra_' . $owner_table . '_' . $extra_item['extra_default_id'],
										'owner_id'    => $owner_id,
										//'layout' => 'list',
										'layout'      => 'gallery',
										'editable'    => true,
									)
								);
							}
							$form_element = array(
								'type'  => $field_type,
								'name'  => 'extra_' . $owner_table . '_field[' . $extra_id . '][val]',
								'value' => $extra_item['extra'],
								'class' => 'extra_value_input',
								'id'    => $id,
							);
							if ( $field_type == 'reference' && ! empty( $extra_item['options']['reference'] ) ) {
								echo self::get_reference( $owner_table, $owner_id, $extra_item, $extra_item['options']['reference'] );

							} else {
								if ( $field_type == 'ajax' ) {

									if ( isset( $default_fields[ $extra_item['extra_key'] ]['options'] ) && is_array( $default_fields[ $extra_item['extra_key'] ]['options'] ) && isset( $default_fields[ $extra_item['extra_key'] ]['options']['lookup'] ) ) {
										$source = explode( '|', $default_fields[ $extra_item['extra_key'] ]['options']['lookup'] );

										if ( ! empty( $source[0] ) ) {

											$form_element['type']   = 'type';
											$form_element['lookup'] = array(
												'key'         => $source[0] . '_id',
												'display_key' => ! empty( $source[1] ) ? $source[1] : $source[0] . '_id',
												'plugin'      => $source[0],
												'lookup'      => ! empty( $source[2] ) ? $source[2] : ( ! empty( $source[1] ) ? $source[1] : $source[0] . '_id' ),
												'display'     => '',
											);
										}
									}
								} else if ( $field_type == 'select' ) {
									$form_element['options'] = array();
									if ( isset( $default_fields[ $extra_item['extra_key'] ]['options'] ) && is_array( $default_fields[ $extra_item['extra_key'] ]['options'] ) && isset( $default_fields[ $extra_item['extra_key'] ]['options']['select'] ) ) {
										foreach ( explode( "\n", $default_fields[ $extra_item['extra_key'] ]['options']['select'] ) as $val ) {
											$val = trim( $val );
											if ( $val === '' ) {
												continue;
											}
											$form_element['options'][ $val ] = $val;
										}
									}
								}
								module_form::generate_form_element( $form_element );
							}
						} else {
							echo forum_text( $extra_item['extra'] );
						}
						/* <input type="text" name="extra_<?php echo $owner_table;?>_field[<?php echo $extra_id;?>][val]" id="<?php echo $id;?>" class="extra_value_input" value="<?php echo htmlspecialchars($extra_item['extra']);?>"> */
						?>
					</td>
				</tr>
				<?php
				$html .= ob_get_clean();
			}
			if ( $more_fields_available || $allow_new ) {
				$extra_id = 'new';
				ob_start();
				?>
				<tr id="extra_<?php echo $owner_table; ?>_options_<?php echo $extra_id; ?>" class="extra_fields_show_more">
					<th></th>
					<td>
						<a href="#" class="extra_fields_show_button"
						   onclick="$('#extra_<?php echo $owner_table; ?>_options_<?php echo $extra_id; ?>').hide();$('#extra_<?php echo $owner_table; ?>_holder_<?php echo $extra_id; ?>').show(); return false;"><?php _e( 'more fields &raquo;' ); ?></a>
					</td>
				</tr>
				<?php
				$html .= ob_get_clean();
				if ( $allow_new ) {
					$extra_id = 'new';
					ob_start();
					?>
					<tr id="extra_<?php echo $extra_id; ?>" class="extra_field_row_hidden">
						<th>
							<input type="text" name="extra_<?php echo $owner_table; ?>_field[<?php echo $extra_id; ?>][key]"
							       value="<?php ?>" class="extra_field" placeholder="<?php _e( 'New Field' ); ?>">
						</th>
						<td>
							<input type="text" name="extra_<?php echo $owner_table; ?>_field[<?php echo $extra_id; ?>][val]"
							       value="<?php ?>" placeholder="<?php _e( 'New Value' ); ?>">
							<?php _h( 'Enter anything you like in this blank field. eg: Passwords, Links, Notes, etc..' ); ?>
						</td>
					</tr>
					<?php
					$html .= ob_get_clean();
				}
			}
		}

		// pass it out for a hook
		// this is really only used in the security module.
		if ( function_exists( 'hook_filter_var' ) ) {
			$html = hook_filter_var( 'extra_fields_output', $html, $owner_table, $owner_id );
		} else {
			$result = hook_handle_callback( 'extra_fields_output', $html, $owner_table, $owner_id );
			if ( $result && count( $result ) ) {
				foreach ( $result as $r ) {
					$html = $r; // bad. handle multiple hooks.
				}
			}
		}

		print $html;
	}

	public static function get_reference( $owner_table, $owner_id, $extra_item, $reference ) {

		$bits = explode( '.', strtolower( trim( $reference ) ) );

		$owner_id = (int) $owner_id;

		if ( $owner_id ) {

			switch ( $bits[0] ) {
				case 'customer':
					// we find a matching 'customer_id' for this entry.
					$ref_id = $bits[0] . '_id';
					$entry  = get_single( $owner_table, $owner_table . '_id', $owner_id );
					if ( ! empty( $entry[ $ref_id ] ) ) {
						$ref_entry = get_single( $bits[0], $bits[0] . '_id', $entry[ $ref_id ] );

						$replace_fields = module_customer::get_replace_fields( $entry[ $ref_id ] );
						foreach ( $replace_fields as $key => $val ) {
							if ( $key && strtolower( str_replace( ' ', '_', $key ) ) == $bits[1] ) {
								return $val;
							}
						}
					}
					break;
			}
		}

		return '';
	}


	public static $config = array();

	public static function save_extras( $owner_table, $owner_key, $owner_id, $allow_new_keys = true, $allow_new_values = true ) {

		// hack to add extra configuration
		if ( isset( self::$config['allow_new_keys'] ) ) {
			$allow_new_keys = self::$config['allow_new_keys'];
		}
		if ( isset( self::$config['allow_new_values'] ) ) {
			$allow_new_keys = self::$config['allow_new_values'];
		}

		if ( isset( $_REQUEST[ 'extra_' . $owner_table . '_field' ] ) && is_array( $_REQUEST[ 'extra_' . $owner_table . '_field' ] ) ) {
			$owner_id = (int) $owner_id;
			if ( $owner_id <= 0 ) {
				if ( isset( $_REQUEST[ $owner_key ] ) ) {
					$owner_id = (int) $_REQUEST[ $owner_key ];
				}
			}
			if ( $owner_id <= 0 ) {
				return;
			} // failed for some reason?
			$existing_extras = self::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
			$default_keys    = self::get_defaults( $owner_table );
			foreach ( $_REQUEST[ 'extra_' . $owner_table . '_field' ] as $extra_id => $extra_data ) {
				$key = trim( $extra_data['key'] );
				$val = trim( isset( $extra_data['val'] ) ? $extra_data['val'] : '' );
				if ( ! $key || $val == '' ) {
					unset( $_REQUEST[ 'extra_' . $owner_table . '_field' ][ $extra_id ] );
					continue;
				}
				// check if this key exists in the system.
				if ( ! $allow_new_keys ) {
					$exists = false;
					foreach ( $default_keys as $default_key ) {
						if ( $default_key['key'] == $key ) {
							$exists = true;
						}
					}
					if ( ! $exists ) {
						unset( $_REQUEST[ 'extra_' . $owner_table . '_field' ][ $extra_id ] );
						continue;
					}
				}
				$extra_db = array(
					'extra_key'   => $key,
					'extra'       => $val,
					'owner_table' => $owner_table,
					'owner_id'    => $owner_id,
				);
				$extra_id = (int) $extra_id;
				// security checking.
				if ( $extra_id > 0 ) {
					// check if this extra is an existing one.
					if ( ! isset( $existing_extras[ $extra_id ] ) ) {
						$extra_id = 0; // not updating an existing one against this owner
					}
				}
				if ( ! $extra_id && ! $allow_new_values ) {
					// we are not allowed to create new values, only update existing values.
					// disallow this.
					unset( $_REQUEST[ 'extra_' . $owner_table . '_field' ][ $extra_id ] );
					continue;
				}
				$extra_id = update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
			}
			// work out which ones were not saved.
			foreach ( $existing_extras as $existing_extra ) {
				// we don't want to delete extra fields when saving a public customer signup form.
				// customer signup (and other parts down the track) will set these flags for us.
				if (
					( ! isset( self::$config['delete_existing_empties'] ) || ( isset( self::$config['delete_existing_empties'] ) && self::$config['delete_existing_empties'] ) )
					&&
					! isset( $_REQUEST[ 'extra_' . $owner_table . '_field' ][ $existing_extra['extra_id'] ] )
				) {
					// remove it.
					$sql = "DELETE FROM " . _DB_PREFIX . "extra WHERE extra_id = '" . (int) $existing_extra['extra_id'] . "' AND `owner_table` = '" . db_escape( $owner_table ) . "' AND `owner_id` = '" . (int) $owner_id . "' LIMIT 1";
					query( $sql );
				}
			}
		}
	}

	public static function delete_extras( $owner_table, $owner_key, $owner_id ) {
		$extra_items = self::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
		foreach ( $extra_items as $extra_item ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "extra WHERE extra_id = '" . (int) $extra_item['extra_id'] . "' LIMIT 1";
			query( $sql );
		}

	}

	public static function get_extra( $extra_id ) {
		$extra = get_single( "extra", "extra_id", $extra_id );
		if ( $extra ) {
			// optional processing here later on.
		}

		return $extra;
	}

	public static function get_extras( $search = false ) {
		return get_multiple( "extra", $search, "extra_id", "exact", "extra_id" );
	}


	/**
	 * @static
	 *
	 * @param $owner_table
	 *
	 * @return array
	 *
	 * search the extra fields for default keys
	 * (ie: keys that have been used on this owner_table before)
	 *
	 */
	public static function sort_extras( $extra_items, $default_items, $allow_new ) {
		// hack to sort our extra list based on the provided default list.
		foreach ( $extra_items as $extra_id => $extra_item ) {
			if ( isset( $default_items[ $extra_item['extra_key'] ] ) ) {
				$extra_items[ $extra_id ]['extra_default_id'] = $default_items[ $extra_item['extra_key'] ]['extra_default_id'];
				$extra_items[ $extra_id ]['order']            = $default_items[ $extra_item['extra_key'] ]['order'];
				$extra_items[ $extra_id ]['options']          = ! empty( $default_items[ $extra_item['extra_key'] ]['options'] ) ? $default_items[ $extra_item['extra_key'] ]['options'] : array();
				$extra_items[ $extra_id ]['field_type']       = $default_items[ $extra_item['extra_key'] ]['field_type'];
				unset( $default_items[ $extra_item['extra_key'] ] );
			} else {
				$extra_items[ $extra_id ]['order'] = 0;
			}
		}
		if ( module_security::is_page_editable() ) {
			// add the blank defaults to the existing extra items list.
			$extra_items = array_merge( $extra_items, $default_items );
		}
		uasort( $extra_items, 'sort_extra_defaults' );
		$new_id = 0;
		foreach ( $extra_items as $key => $val ) {
			if ( empty( $val['extra_id'] ) ) {
				$extra_items[ $key ]['extra_id']  = 'new' . $new_id;
				$extra_items[ $key ]['extra_key'] = $val['key'];
				$extra_items[ $key ]['extra']     = ''; // default value here.
				// we hide all empty default options by default (if hide_Extra is set to 1)
				// howeer we never hide a file type, because at this stage we cannot find out if a file exists or not, as that's handled by a 3rd party module.
				if ( $val['field_type'] == 'reference' ) {
					$extra_items[ $key ]['hidden'] = false;
				} else {
					$extra_items[ $key ]['hidden'] = ! $allow_new ? true : ( $val['field_type'] == 'file' ? false : module_config::c( 'hide_extra', 1 ) );
				}
				$new_id ++;
			}
		}

		return $extra_items;
	}

	public static function get_defaults( $owner_table = false, $owner_table_child = false ) {

		$defaults  = array();
		$nextorder = array();
		if ( $owner_table && strlen( $owner_table ) ) {
			$where                     = " WHERE e.owner_table = '" . db_escape( $owner_table ) . "' ";
			$defaults[ $owner_table ]  = array();
			$nextorder[ $owner_table ] = 0;
		} else {
			$where = '';
		}
		$sql = "SELECT `extra_default_id`,`extra_key`, `order`, `display_type`, `owner_table`, `owner_table_child`, `searchable`, `field_type`, `options` FROM `" . _DB_PREFIX . "extra_default` e $where ORDER BY e.`order` ASC";
		foreach ( qa( $sql ) as $r ) {
			if ( ! isset( $defaults[ $r['owner_table'] ] ) ) {
				$defaults[ $r['owner_table'] ] = array();
			}
			if ( ! isset( $nextorder[ $r['owner_table'] ] ) ) {
				$nextorder[ $r['owner_table'] ] = 0;
			}
			$defaults[ $r['owner_table'] ][ $r['extra_key'] ] = array(
				'key'               => $r['extra_key'],
				'order'             => $r['order'],
				'owner_table_child' => $r['owner_table_child'],
				'extra_default_id'  => $r['extra_default_id'],
				'display_type'      => $r['display_type'],
				'searchable'        => $r['searchable'],
				'field_type'        => $r['field_type'],
				'options'           => isset( $r['options'] ) ? @json_decode( $r['options'], true ) : array(),
			);
			$nextorder[ $r['owner_table'] ]                   = max( $r['order'], $nextorder[ $r['owner_table'] ] );
		}
		// search database for keys.
		$sql = "SELECT `extra_key`,`owner_table` FROM `" . _DB_PREFIX . "extra` e $where GROUP BY e.extra_key";
		foreach ( qa( $sql ) as $r ) {
			if ( ! isset( $nextorder[ $r['owner_table'] ] ) ) {
				$nextorder[ $r['owner_table'] ] = 0;
			}
			if ( ! isset( $defaults[ $r['owner_table'] ] ) || ! isset( $defaults[ $r['owner_table'] ][ $r['extra_key'] ] ) ) {
				$nextorder[ $r['owner_table'] ] ++;
				$extra_default_id                                                     = update_insert( 'extra_default_id', false, 'extra_default', array(
					'owner_table'       => $r['owner_table'],
					'owner_table_child' => $owner_table_child,
					'extra_key'         => $r['extra_key'],
					'order'             => $nextorder[ $r['owner_table'] ],
					'display_type'      => 0,
				) );
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]                     = array();
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['key']              = $r['extra_key'];
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['order']            = $nextorder[ $r['owner_table'] ];
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['extra_default_id'] = $extra_default_id;
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['display_type']     = 0;
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['field_type']       = '';
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['options']          = array();
				module_cache::clear_cache( false );
			}
			if ( ! isset( $defaults[ $r['owner_table'] ][ $r['extra_key'] ]['order'] ) ) {
				$defaults[ $r['owner_table'] ][ $r['extra_key'] ]['order'] = 0;
			}
			/*$defaults[$r['owner_table']][$r['extra_key']] = array(
			'key' => $r['extra_key'],
			'order'=> isset($defaults[$r['extra_key']]) ? $defaults[$r['extra_key']]['order'] : 0,
		);*/
		}

		if ( $owner_table_child !== false ) {
			foreach ( $defaults as $owner_table => $extra_fields ) {
				foreach ( $extra_fields as $extra_field_id => $extra_field ) {
					if ( isset( $extra_field['owner_table_child'] ) ) {
						if ( ! strlen( $extra_field['owner_table_child'] ) ) {
							// thsi is the default 'all' type, so we dont restrict
						} else {
							if ( $owner_table_child != $extra_field['owner_table_child'] ) {
								unset( $defaults[ $owner_table ][ $extra_field_id ] );
							}
						}
					}
				}
			}
		}
		if ( $owner_table ) {
			uasort( $defaults[ $owner_table ], 'sort_extra_defaults' );

			return $defaults[ $owner_table ];
		} else {
			return $defaults;//return all for settings area
		}

		/*        switch($owner_table){
								case 'website':
										$defaults = array(
												array('key' => 'FTP Username',),
												array('key' => 'FTP Password',),
												array('key' => 'FTP Provider',),
												array('key' => 'Host Username',),
												array('key' => 'Host Password',),
												array('key' => 'Host Provider',),
												array('key' => 'WordPress User',),
												array('key' => 'WordPress Pass',),
												array('key' => 'Analytics Account',),
												array('key' => 'Webmaster Account',),
										);
										break;
						}*/
	}

	public static function get_extra_default( $extra_default_id ) {
		$extra_default_id = (int) $extra_default_id;
		$extra_data_key   = false;
		if ( $extra_default_id > 0 ) {
			$extra_data_key = get_single( 'extra_default', 'extra_default_id', $extra_default_id );
			if ( $extra_data_key && isset( $extra_data_key['options'] ) ) {
				$extra_data_key['options'] = @json_decode( $extra_data_key['options'], true );
			}
		}
		if ( ! $extra_data_key ) {
			$extra_data_key = array(
				'extra_default_id' => '',
				'owner_table'      => '',
				'extra_key'        => '',
				'display_type'     => '',
				'order'            => '',
				'field_type'       => '',
				'reminder'         => '0',
				'options'          => array(),
			);
		}

		return $extra_data_key;
	}


	public static function get_display_types() {
		return array(
			0                          => 'Default',
			_EXTRA_DISPLAY_TYPE_COLUMN => 'Public + In Columns',
			//2 => 'Private By Permissions',
		);
	}

	public static function print_search_bar( $owner_table ) {
		// let the themes override this search bar function.
		if ( self::can_i( 'view', 'Extra Fields' ) ) {

			if ( is_array( $owner_table ) && isset( $owner_table['owner_table'] ) ) {
				$options     = $owner_table;
				$owner_table = $owner_table['owner_table'];
			} else {
				$options = array();
			}


			$result = hook_handle_callback( 'extra_fields_search_bar', $owner_table, $options );
			if ( is_array( $result ) ) {
				// has been handed by a theme.
				echo current( $result );
			} else {
				$defaults          = self::get_defaults( $owner_table );
				$searchable_fields = array();
				foreach ( $defaults as $default ) {
					if ( isset( $default['searchable'] ) && $default['searchable'] ) {
						$searchable_fields[ $default['key'] ] = $default;
					}
				}
				foreach ( $searchable_fields as $searchable_field ) {
					?>
					<td class="search_title">
						<?php echo htmlspecialchars( $searchable_field['key'] ); ?>:
					</td>
					<td class="search_input">
						<?php
						module_form::generate_form_element( array(
							'type' => 'text',
							'name' => 'search[extra_fields][' . htmlspecialchars( $searchable_field['key'] ) . ']',
						) ); ?>
					</td>
					<?php
				}
			}
		}
	}

	static $column_headers = array();

	public static function print_table_header( $owner_table, $options = array() ) {
		$cols = 0;
		if ( self::can_i( 'view', 'Extra Fields' ) ) {
			if ( isset( self::$column_headers[ $owner_table ] ) ) {
				$column_headers = self::$column_headers[ $owner_table ];
			} else {
				$defaults       = self::get_defaults( $owner_table );
				$column_headers = array();
				foreach ( $defaults as $default ) {
					if ( isset( $default['display_type'] ) && $default['display_type'] == _EXTRA_DISPLAY_TYPE_COLUMN ) {
						$column_headers[ $default['key'] ] = $default;
					}
				}
				self::$column_headers[ $owner_table ] = $column_headers;
			}
			foreach ( $column_headers as $column_header ) {
				$this_options = array();
				if ( isset( $options[0] ) ) {
					$this_options = $options[0];
				}
				if ( isset( $options[ $column_header['key'] ] ) ) {
					$this_options = $options[ $column_header['key'] ];
				}
				?>
				<th class="extra_column"
				    id="extra_header_<?php echo $column_header['extra_default_id']; ?>" <?php echo isset( $this_options['style'] ) ? ' style="' . $this_options['style'] . '"' : ''; ?>>
					<?php echo $column_header['key']; ?>
				</th>
				<?php
				$cols ++;
			}
		}

		return $cols;
	}

	public static function print_table_data( $owner_table, $owner_id, $owner_table_child = false ) {
		if ( self::can_i( 'view', 'Extra Fields' ) && isset( self::$column_headers[ $owner_table ] ) ) {
			$extra_data = get_multiple( 'extra', array(
				'owner_table' => $owner_table,
				'owner_id'    => $owner_id
			), 'extra_key' );
			foreach ( self::$column_headers[ $owner_table ] as $column_header ) {
				?>
				<td>
					<?php
					if ( ! empty( $column_header['field_type'] ) && $column_header['field_type'] == 'reference' && ! empty( $column_header['options']['reference'] ) ) {
						echo self::get_reference( $owner_table, $owner_id, $column_header, $column_header['options']['reference'] );
					} else if ( isset( $extra_data[ $column_header['key'] ] ) ) {
						echo forum_text( $extra_data[ $column_header['key'] ]['extra'] );
					} else {
						echo '';
					}
					?>
				</td>
				<?php
			}
		}
	}


	public function get_upgrade_sql() {
		$sql = '';
		if ( ! self::db_table_exists( 'extra' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'extra` (
  `extra_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `owner_table` varchar(80) NOT NULL,
  `extra_key` varchar(100) NOT NULL,
  `extra` longtext NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`extra_id`),
  KEY `owner_id` (`owner_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		}
		if ( ! self::db_table_exists( 'extra_default' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'extra_default` (
  `extra_default_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_table` varchar(80) NOT NULL,
  `extra_key` varchar(100) NOT NULL,
  `order` int(11) NOT NULL DEFAULT \'0\',
  `display_type` tinyint(2) NOT NULL DEFAULT \'0\',
  `searchable` tinyint(2) NOT NULL DEFAULT \'0\',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  PRIMARY KEY (`extra_default_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		} else {
			$fields = get_fields( 'extra_default' );
			if ( ! isset( $fields['display_type'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `display_type` tinyint(2) NOT NULL DEFAULT \'0\' AFTER `order`;';
			}
			if ( ! isset( $fields['searchable'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `searchable` tinyint(2) NOT NULL DEFAULT \'0\' AFTER `display_type`;';
			}
			if ( ! isset( $fields['field_type'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `field_type` varchar(20) NOT NULL DEFAULT \'\' AFTER `searchable`;';
			}
			if ( ! isset( $fields['reminder'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `reminder` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `field_type`;';
			}
			if ( ! isset( $fields['options'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `options` TEXT NOT NULL AFTER `reminder`;';
			}
			if ( ! isset( $fields['owner_table_child'] ) ) {
				$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'extra_default` ADD  `owner_table_child` varchar(80) NOT NULL DEFAULT \'\' AFTER `owner_table`;';
			}
		}

		/*if(!self::db_table_exists('extra_key')){
				$sqlnow = 'CREATE TABLE `'._DB_PREFIX.'extra_key` (
`extra_key_id` int(11) NOT NULL AUTO_INCREMENT,
`owner_table` varchar(80) NOT NULL,
`extra_key` varchar(100) NOT NULL,
`date_created` datetime NOT NULL,
`date_updated` datetime NULL,
`create_user_id` int(11) NOT NULL,
`update_user_id` int(11) NULL,
`create_ip_address` varchar(15) NOT NULL,
`update_ip_address` varchar(15) NULL,
PRIMARY KEY (`extra_key_id`),
KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
				query($sqlnow);// so it's ready for below:
		}
		$fields = get_fields('extra');
		if(!isset($fields['extra_key_id'])){
				$sql_now = 'ALTER TABLE  `'._DB_PREFIX.'extra` ADD  `extra_key_id` int(11) NOT NULL DEFAULT \'0\' AFTER `extra_id`;';
				query($sql_now);
				$sql_now = 'ALTER TABLE  `'._DB_PREFIX.'extra` ADD INDEX (  `extra_key_id` )';
				query($sql_now);
				$sql_update = "SELECT * FROM `"._DB_PREFIX."extra` GROUP BY owner_table,extra_key";
				$existing_extras = qa($sql_update);
				if(class_exists('module_cache',false))module_cache::clear_cache();
				foreach($existing_extras as $existing_extra){
						$extra_key = trim($existing_extra['extra_key']);
						if(strlen($extra_key)){
								// find if it exists.
								$existing_in_db = get_single('extra_key','extra_key',$extra_key,true);
								if(!$existing_in_db || !$existing_in_db['extra_key_id']){
										// doesn't exists. woot.
										$existing_in_db = array();
										$existing_in_db['extra_key_id'] = update_insert('extra_key_id','new','extra_key',array('extra_key'=>$extra_key,'owner_table'=>$existing_extra['owner_table']));
								}
								if($existing_in_db['extra_key_id']){
										$sql_update_keys = "UPDATE `"._DB_PREFIX."extra` SET `extra_key_id` = ".(int)$existing_in_db['extra_key_id']." WHERE extra_key = '".db_escape($extra_key)."' AND owner_table = '".db_escape($existing_extra['owner_table'])."'";
										query($sql_update_keys);
								}
						}
				}
		}*/

		return $sql;
	}

	public function get_install_sql() {
		return 'CREATE TABLE `' . _DB_PREFIX . 'extra` (
  `extra_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `owner_table` varchar(80) NOT NULL,
  `extra_key` varchar(100) NOT NULL,
  `extra` longtext NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`extra_id`),
  KEY `owner_id` (`owner_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


CREATE TABLE `' . _DB_PREFIX . 'extra_default` (
  `extra_default_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_table` varchar(80) NOT NULL,
  `owner_table_child` varchar(80) NOT NULL DEFAULT \'\',
  `extra_key` varchar(100) NOT NULL,
  `order` int(11) NOT NULL DEFAULT \'0\',
  `display_type` tinyint(2) NOT NULL DEFAULT \'0\',
  `searchable` tinyint(2) NOT NULL DEFAULT \'0\',
  `field_type` varchar(20) NOT NULL DEFAULT \'\',
  `reminder` tinyint(1) NOT NULL DEFAULT \'0\',
  `options` TEXT NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  PRIMARY KEY (`extra_default_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


';

		//`extra_key_id` int(11) NOT NULL DEFAULT \'0\',
		/*
CREATE TABLE `'._DB_PREFIX.'extra_key` (
`extra_key_id` int(11) NOT NULL AUTO_INCREMENT,
`owner_table` varchar(80) NOT NULL,
`extra_key` varchar(100) NOT NULL,
`date_created` datetime NOT NULL,
`date_updated` datetime NULL,
`create_user_id` int(11) NOT NULL,
`update_user_id` int(11) NULL,
`create_ip_address` varchar(15) NOT NULL,
`update_ip_address` varchar(15) NULL,
PRIMARY KEY (`extra_key_id`),
KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;*/
	}

}