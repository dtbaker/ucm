<?php

define( '_MODULE_DATA_NAME', 'Custom Data' );

define( '_CUSTOM_DATA_MENU_LOCATION_MAIN', 1 );
define( '_CUSTOM_DATA_MENU_LOCATION_CUSTOMER', 2 );
define( '_CUSTOM_DATA_MENU_LOCATION_NONE', 3 );

define( '_MIN_INPUT_HEIGHT', 18 );


class module_data extends module_base {

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

	public function init() {
		$this->links           = array();
		$this->user_types      = array();
		$this->module_name     = "data";
		$this->module_position = 18;
		$this->version         = 2.185;
		//2.185 - 2017-05-02 - file path configuration
		//2.184 - 2017-02-20 - custom data extra field improvement
		//2.183 - 2016-12-10 - ajax lookup from extra fields
		//2.182 - 2016-11-07 - new ajax lookup field
		//2.181 - 2016-09-09 - sorting custom data entries
		//2.180 - 2016-09-09 - staff select in custom data.
		//2.179 - 2016-09-02 - parent link fixes
		//2.178 - 2016-08-09 - pagination fix
		//2.177 - 2016-08-04 - search date fix
		//2.176 - 2016-07-18 - multiple fields separator
		//2.175 - 2016-07-10 - big update to mysqli
		//2.174 - 2016-06-09 - search date range
		//2.173 - 2016-05-02 - export/import data settings
		//2.172 - 2016-04-30 - multiple file upload fix
		//2.171 - 2016-04-30 - fix for new search bar
		//2.170 - 2016-04-29 - multiple file upload fix
		//2.169 - 2016-04-28 - pagination and csv export
		//2.168 - 2016-04-08 - multiple file upload support
		//2.167 - 2016-03-14 - auto incrementing field id
		//2.166 - 2016-03-14 - image preview in file uploads
		//2.165 - 2016-02-02 - tabs and print fixes
		//2.164 - 2016-01-22 - show warning when no "show in main listing" boxes are ticked
		//2.163 - 2015-10-27 - new hook locations in jobs
		//2.162 - 2015-10-16 - customer link in view all
		//2.161 - 2015-08-15 - email replace fields fix
		//2.16 - 2015-03-18 - fulltext db fix
		//2.159 - 2015-03-17 - db speed improvements
		//2.158 - 2015-03-05 - untick multiple option bug fix
		//2.157 - 2015-02-05 - custom data search spacing fix
		//2.156 - 2015-01-31 - search by created/update
		//2.155 - 2015-01-27 - file download name/extension fix
		//2.154 - 2015-01-25 - fixes, pdf template and single entry
		//2.153 - 2015-01-21 - link to customer and multiple fields

		//2.1 - 2014-01-05 - awesome custom data feature - initial release
		//2.11 - 2014-01-05 - awesome custom data feature - menu fix
		//2.12 - 2014-01-08 - custom data update - better delete options
		//2.13 - 2014-01-14 - custom data update - sub lists support
		//2.14 - 2014-01-17 - encrypted field support
		//2.141 - 2014-02-03 - icons in menu
		//2.142 - 2014-02-04 - drag and drop re-arrange items bug fix
		//2.143 - 2014-02-15 - drag and drop re-arrange items bug fix
		//2.144 - 2014-02-18 - easier create new buttons
		//2.145 - 2014-02-24 - search button added to top
		//2.146 - 2014-03-07 - file upload link fix
		//2.147 - 2014-03-10 - custom data search fixed
		//2.148 - 2014-04-25 - html output improvement
		//2.149 - 2014-07-14 - menu fix and new read only fields
		//2.15 - 2014-08-10 - menu position through advanced custom_data_menu_order_X
		//2.151 - 2014-08-18 - select drop down fix
		//2.152 - 2015-01-07 - file upload improvement

		module_config::register_css( 'data', 'data.css' );
		module_config::register_js( 'data', 'data.js' );

		//hook_handle_callback('custom_data_hook_location',_CUSTOM_DATA_HOOK_LOCATION_JOB_FOOTER, 'job', $job_id, $job);
		hook_add( 'custom_data_hook_location', 'module_data::hook_location' );
	}

	public function pre_menu() {

		if ( $this->can_i( 'edit', _MODULE_DATA_NAME ) ) {
			$this->links['custom_data'] = array(
				"name"                => _MODULE_DATA_NAME,
				"p"                   => "data_type_admin",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'args'                => array(
					'data_field_group_id' => false,
					'data_type_id'        => false,
				),
			);
		}

		if ( $this->is_installed() ) {
			$data_types = $this->get_data_types();
			foreach ( $data_types as $data_type ) {
				if ( _DEMO_MODE ) {
					$data_field_groups = $this->get_data_field_groups( $data_type['data_type_id'] );
					if ( ! count( $data_field_groups ) ) {
						continue;
					}
				}
				switch ( $data_type['data_type_menu'] ) {
					case _CUSTOM_DATA_MENU_LOCATION_MAIN:
						if ( $this->can_i( 'view', $data_type['data_type_name'] ) ) {
							$this->links[] = array(
								"name"      => htmlspecialchars( $data_type['data_type_name'] ),
								"p"         => "admin_data",
								'args'      => array(
									'data_type_id'   => $data_type['data_type_id'],
									'data_record_id' => false
								),
								'icon_name' => isset( $data_type['data_type_icon'] ) ? htmlspecialchars( $data_type['data_type_icon'] ) : '',
								'current'   => isset( $_REQUEST['data_type_id'] ) && $_REQUEST['data_type_id'] == $data_type['data_type_id'],
								'order'     => module_config::c( 'custom_data_menu_order_' . $data_type['data_type_id'], $this->module_position ),
							);
						}
						break;
					case _CUSTOM_DATA_MENU_LOCATION_CUSTOMER:
						if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
							if ( $this->can_i( 'view', $data_type['data_type_name'] ) ) {
								$this->links[] = array(
									"name"                => htmlspecialchars( $data_type['data_type_name'] ),
									"p"                   => "admin_data",
									'args'                => array(
										'data_type_id'   => $data_type['data_type_id'],
										'data_record_id' => false
									),
									'holder_module'       => 'customer',
									// which parent module this link will sit under.
									'holder_module_page'  => 'customer_admin_open',
									// which page this link will be automatically added to.
									'menu_include_parent' => 0,
									'icon_name'           => isset( $data_type['data_type_icon'] ) ? htmlspecialchars( $data_type['data_type_icon'] ) : '',
									'current'             => isset( $_REQUEST['data_type_id'] ) && $_REQUEST['data_type_id'] == $data_type['data_type_id'],
									'order'               => module_config::c( 'custom_data_menu_order_' . $data_type['data_type_id'], $this->module_position ),
								);
							}
						}
						break;
				}

			}
		}
	}


	function link( $page = '', $args = array(), $module = false, $include_parent = - 1, $object_data = array(), $options = array() ) {
		$keys = $this->get_data_link_keys();
		//$keys[] = 'data_type_id'; // doesn't work cancelling out of settings window
		foreach ( $keys as $key ) {
			if ( isset( $_REQUEST[ $key ] ) && ! isset( $args[ $key ] ) ) {
				$args[ $key ] = $_REQUEST[ $key ];
			}
		}

		return parent::link( $page, $args, $module, $include_parent, $object_data, $options );
	}

	public static function link_generate( $data_id = false, $options = array(), $link_options = array() ) {


		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'data_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

		$data_data = false;
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
			// check if this still exists.
			// this is a hack incase the data is deleted, the invoices are still left behind.
			if ( ${$key} && $link_options ) {
				if ( isset( $options['page'] ) && $options['page'] == 'data_type_admin' ) {
					$data_data = self::get_data_type( ${$key} );
				} else {
					$data_data = self::get_data_record( ${$key} );
				}
				if ( ! $data_data || ! isset( $data_data[ $key ] ) || $data_data[ $key ] != ${$key} ) {
					$link = link_generate( $link_options );

					return $link;
				}
			}
		}
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $data_id > 0 ) {
					$data = $data_data ? $data_data : ( isset( $options['page'] ) && $options['page'] == 'data_type_admin' ? self::get_data_type( ${$key} ) : self::get_data_record( ${$key} ) );
				} else {
					$data = array();
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['data_name'] ) || ! trim( $data['data_name'] ) ) ? _l( 'N/A' ) : $data['data_name'];
			if ( ! $data || ! $data_id || isset( $data['_no_access'] ) ) {
				$link = $options['text'];

				return $link;
			}
		}
		//$options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'data_id' => $data_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = 'data_admin_' . ( ( $data_id || $data_id == 'new' ) ? 'open' : 'list' );
		$options['module'] = 'data';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! self::can_i( 'view', _MODULE_DATA_NAME ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				$link = '#';
			} else {
				$link = isset( $options['text'] ) ? $options['text'] : 'N/A';
			}

			return $link;
		}


		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		/*$bubble_to_module = array(
            'module' => 'people',
            'argument' => 'people_id',
        );*/
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			$link = link_generate( $link_options );
		}

		return $link;
	}


	/* for the actual form */
	public static function link_open( $data_id, $full = false, $data = array() ) {
		return self::link_generate( $data_id, array( 'full' => $full, 'data' => $data ) );
	}

	/* for the settings page */
	public static function link_open_data_type( $data_type_id, $full = false, $data = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.

		$options      = array( 'full' => $full, 'data' => $data );
		$link_options = array();

		if ( isset( $options['full'] ) && $options['full'] ) {
			if ( (int) $data_type_id > 0 ) {
				if ( ! $data ) {
					$data = self::get_data_type( $data_type_id );
				}
			}
			$options['data'] = $data;


			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['data_name'] ) || ! trim( $data['data_name'] ) ) ? _l( 'N/A' ) : $data['data_name'];
			if ( ! $data || ! $data_type_id || isset( $data['_no_access'] ) ) {
				$link = $options['text'];

				return $link;
			}
		}
		//$options['text'] = isset($options['text']) ? htmlspecialchars($options['text']) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'data_type_id' => $data_type_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = 'data_type_admin';
		$options['module'] = 'data';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! self::can_i( 'view', _MODULE_DATA_NAME ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				$link = '#';
			} else {
				$link = isset( $options['text'] ) ? $options['text'] : 'N/A';
			}

			return $link;
		}


		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'data_type_id',
		);
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			$link = link_generate( $link_options );
		}

		return $link;
	}

	function handle_hook( $hook ) {
		switch ( $hook ) {
		}
	}

	function process() {
		$errors = array();
		if ( $_REQUEST['_process'] == 'admin_leave' ) {
			$_SESSION['admin_mode'] = false;
			redirect_browser( $_SERVER['REQUEST_URI'] );
		}
		if ( $_REQUEST['_process'] == 'admin_enter' ) {
			$_SESSION['admin_mode'] = true;
			redirect_browser( $_SERVER['REQUEST_URI'] );
		}
		$data           = $_POST;
		$data_type_id   = isset( $data['data_type_id'] ) ? (int) $data['data_type_id'] : false;
		$data_record_id = isset( $data['data_record_id'] ) ? $data['data_record_id'] : false;

		if ( ! $this->can_i( 'edit', _MODULE_DATA_NAME ) ) {
			set_error( 'No permissions to edit ' . _MODULE_DATA_NAME . ' in Settings > User Roles' );

			return false;
		}

		if ( "save_data_record" == $_REQUEST['_process'] ) {
			// check hash
			$real_hash = $this->save_hash( $data_record_id, $data_type_id );
			if ( isset( $_REQUEST['data_save_hash'] ) && $_REQUEST['data_save_hash'] == $real_hash ) {
				$data_record_id = $this->save_data_record();
				if ( $data_record_id ) {
					//$_REQUEST['_redirect'] = $this->link('',array("data_type_id"=>$data_type_id,'data_record_id'=>$data_record_id,'notify'=>1));
					if ( isset( $_REQUEST['parent_data_record_id'] ) && (int) $_REQUEST['parent_data_record_id'] > 0 ) {
						$_REQUEST['_redirect'] = $this->link( '', array( "data_record_id" => (int) $_REQUEST['parent_data_record_id'] ) );
					} else {
						$_REQUEST['_redirect'] = $this->link( '', array( 'data_type_id' => $data_type_id ) );
					}
					set_message( _l( "Record saved successfully" ) );
				} else {
					$errors[] = 'Failed to save';
					set_error( _l( "Failed to save, please try again." ) );
				}
			} else {
				set_error( _l( "Sorry invalid hash" ) );
			}
		} else if ( "save_ajax" == $_REQUEST['_process'] ) {
			switch ( $_REQUEST['type'] ) {
				case 'input':
					$data_field_id = (int) $_REQUEST['data_field_id'];
					$width         = (int) $_REQUEST['width'];
					$height        = (int) $_REQUEST['height'];
					$data_field    = $this->get_data_field( $data_field_id );
					// save width and height as attributes in the 'field_data' field
					$new_field_data = '';
					foreach ( explode( "\n", trim( $data_field['field_data'] ) ) as $line ) {
						$line = trim( $line );
						if ( $width && preg_match( '/^width=/', $line ) ) {
							$line  = 'width=' . $width;
							$width = false;
						} else if ( $height && preg_match( '/^height=/', $line ) ) {
							$line   = 'height=' . $height;
							$height = false;
						}
						$new_field_data .= $line . "\n";
					}
					$new_field_data .= ( $width ) ? "width=$width\n" : '';
					$new_field_data .= ( $height ) ? "height=$height\n" : '';
					$data           = array(
						'field_data' => $new_field_data,
					);
					update_insert( "data_field_id", $data_field_id, "data_field", $data );
					break;
				case 'boundary':
					$data_field_id = (int) $_REQUEST['data_field_id'];
					$width         = (int) $_REQUEST['width'];
					$height        = (int) $_REQUEST['height'];
					$data_field    = $this->get_data_field( $data_field_id );
					$data          = array();
					if ( $width ) {
						$data['width'] = $width;
					}
					if ( $height ) {
						$data['height'] = $height;
					}
					update_insert( "data_field_id", $data_field_id, "data_field", $data );
					break;
				case 'position':
					$data_field_group_id  = (int) $_REQUEST['data_field_group_id'];
					$data_field_positions = isset( $_REQUEST['container_data_field'] ) ? $_REQUEST['container_data_field'] : $_REQUEST['data_field'];
					if ( is_array( $data_field_positions ) && count( $data_field_positions ) ) {
						$position = 1;
						foreach ( $data_field_positions as $data_field_id ) {
							echo "Update item $data_field_id with $position \n";
							update_insert( "data_field_id", $data_field_id, "data_field", array( 'order' => $position ) );
							$position ++;
						}
					}
					break;
			}
			echo 'done';
			exit;
		} else if ( "save_data_type" == $_REQUEST['_process'] ) {
			if ( isset( $_REQUEST['butt_del'] ) ) {
				$data_type_id = (int) $_REQUEST['data_type_id'];
				if ( module_form::confirm_delete( 'data_type_id', 'Really delete this entire custom data group? All it\'s records and revisions as well?', $this->link( '', array( "data_type_id" => $data_type_id ) ) ) ) {
					$this->delete_data_type( $data_type_id );
					$_REQUEST['_redirect'] = $this->link( '', array( "data_type_id" => false, 'data_field_group_id' => false ) );
					set_message( _l( "Data deleted successfully" ) );
				}
			} else {
				$data_type_id = $this->save_data_type();
				if ( isset( $_REQUEST['butt_export'] ) ) {
					// generate a json export file with all the current settings and sub data settings in it.
					$json = array();
					global $this_module;
					$this_module = $this;
					function export_data_type_id( $data_type_id, $json ) {
						global $this_module;
						foreach ( $json as $existing_data_type ) {
							if ( $existing_data_type['data_type_id'] == $data_type_id ) {
								return $json;
							}
						}
						$data_type           = $this_module->get_data_type( $data_type_id );
						$data_type['groups'] = array();
						foreach ( $this_module->get_data_field_groups( $data_type_id ) as $group ) {
							$data_type['groups'][] = $group;
						}
						foreach ( $data_type['groups'] as $id => $group ) {
							$data_type['groups'][ $id ]['fields'] = array();
							if ( ! empty( $group['sub_data_type_id'] ) ) {
								// check we haven't exported it already.
								$json = export_data_type_id( $group['sub_data_type_id'], $json );
							}
							foreach ( $this_module->get_data_fields( $group['data_field_group_id'] ) as $field ) {
								$data_type['groups'][ $id ]['fields'][] = $field;
							}
						}
						$json[] = $data_type;

						return $json;
					}

					$json = export_data_type_id( $data_type_id, $json );

					$str = json_encode( $json );
					@ob_end_clean();
					header( 'Content-Type: application/json' );
					header( 'Content-Disposition: attachment; filename=custom_data.json' );
					header( 'Pragma: no-cache' );

					echo $str;
					exit;
				}
				$_REQUEST['_redirect'] = $this->link( '', array( "data_type_id" => $data_type_id ) );
				set_message( _l( "Data saved successfully" ) );
			}
		} else if ( "save_data_field_group" == $_REQUEST['_process'] ) {
			$data_type_id = (int) $_REQUEST['data_type_id'];
			if ( isset( $_REQUEST['butt_del'] ) ) {
				$data_field_group_id = (int) $_REQUEST['data_field_group_id'];
				if ( module_form::confirm_delete( 'data_field_group_id', 'Really delete this entire data fieldset?', $this->link( '', array( "data_type_id"        => $data_type_id,
				                                                                                                                             'data_field_group_id' => $data_field_group_id
				) ) ) ) {
					$this->delete_data_fieldset( $data_field_group_id );
					$_REQUEST['_redirect'] = $this->link( '', array( "data_type_id"        => $data_type_id,
					                                                 'data_field_group_id' => false
					) );
					set_message( _l( "Data fieldset deleted successfully" ) );
				}
			} else {
				$data_field_group_id   = $this->save_field_group();
				$_REQUEST['_redirect'] = $this->link( '', array( "data_type_id"        => $data_type_id,
				                                                 'data_field_group_id' => $data_field_group_id
				) );
				set_message( _l( "Data fieldset saved successfully" ) );
			}
		} else if ( "save_data_field" == $_REQUEST['_process'] ) {
			$data_type_id        = (int) $_REQUEST['data_type_id'];
			$data_field_id       = (int) $_REQUEST['data_field_id'];
			$data_field_group_id = (int) $_REQUEST['data_field_group_id'];
			if ( isset( $_REQUEST['butt_dell'] ) && $_REQUEST['butt_dell'] ) {
				$sql = "DELETE FROM " . _DB_PREFIX . "data_field WHERE data_field_id = '" . $data_field_id . "' AND data_field_group_id = '" . $data_field_group_id . "' LIMIT 1";
				query( $sql );
				set_message( _l( "Data field deleted successfully" ) );
			} else {
				$data_field_id = $this->save_field();
				set_message( _l( "Data field saved successfully" ) );
			}
			$_REQUEST['_redirect'] = $this->link( '', array( "data_type_id"        => $data_type_id,
			                                                 'data_field_group_id' => $data_field_group_id,
			) );//'data_field_id'=>$data_field_id
		}
		if ( ! count( $errors ) ) {
			redirect_browser( isset( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : $this->link_generate( false ) );
		}
	}

	public static function is_admin_mode() {
		return isset( $_SESSION['admin_mode'] ) && $_SESSION['admin_mode'];
	}

	public function save_hash( $data_record_id, $data_type_id ) {
		return md5( "random $data_type_id hash for $data_record_id and $data_type_id" );
	}

	function save_data_type() {
		$data_type_id = (int) $_REQUEST['data_type_id'];
		// check if we're actually doing an import
		if ( ! empty( $_FILES['settings_file']['tmp_name'] ) && is_uploaded_file( $_FILES['settings_file']['tmp_name'] ) ) {
			$json                   = json_decode( file_get_contents( $_FILES['settings_file']['tmp_name'] ), true );
			$update_sub_id          = array();
			$imported_data_type_ids = array();
			foreach ( $json as $id => $data_type ) {
				$original_data_type_id = $data_type['data_type_id'];
				unset( $data_type['data_type_id'] );
				$new_data_type_id                                 = update_insert( "data_type_id", false, "data_type", $data_type );
				$json[ $id ]['data_type_id']                      = $new_data_type_id;
				$imported_data_type_ids[ $original_data_type_id ] = $new_data_type_id;
				// groups
				foreach ( $data_type['groups'] as $group ) {
					unset( $group['data_field_group_id'] );
					$group['data_type_id'] = $new_data_type_id;
					$data_field_group_id   = update_insert( 'data_field_group_id', false, 'data_field_group', $group );
					if ( ! empty( $group['sub_data_type_id'] ) ) {
						$update_sub_id[ $data_field_group_id ] = $group['sub_data_type_id'];
					} else {
						// normal
					}
					foreach ( $group['fields'] as $field ) {
						unset( $field['data_field_id'] );
						$field['data_field_group_id'] = $data_field_group_id;
						update_insert( 'data_field_id', false, 'data_field', $field );
					}
				}
			}
			foreach ( $update_sub_id as $data_field_group_imported_id => $original_imported_id ) {
				// check $original_data_type_id
				update_insert( 'data_field_group_id', $data_field_group_imported_id, 'data_field_group', array(
					'sub_data_type_id' => isset( $imported_data_type_ids[ $original_imported_id ] ) ? $imported_data_type_ids[ $original_imported_id ] : 0
				) );
			}
		} else {
			$data_type_id = update_insert( "data_type_id", $data_type_id, "data_type", $_POST );
			if ( $data_type_id ) {
				// save data notes
				//handle_hook("note_save",$this,"data",$data_id);
			}
		}

		return $data_type_id;
	}

	function save_field_group() {
		$data_field_group_id = $_REQUEST['data_field_group_id'];
		$data_field_group_id = update_insert( "data_field_group_id", $data_field_group_id, "data_field_group" );
		if ( $data_field_group_id ) {
			// save data notes
			//handle_hook("note_save",$this,"data",$data_id);
		}

		return $data_field_group_id;
	}

	function save_field() {
		$data_field_id = $_REQUEST['data_field_id'];
		$data          = $_POST;
		foreach ( array( 'width', 'height', 'required', 'reportable', 'show_list', 'searchable', 'multiple' ) as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				$data[ $key ] = 0;
			}
		}
		$data_field_id = update_insert( "data_field_id", $data_field_id, "data_field", $data );
		if ( $data_field_id ) {
			// save data notes
			//handle_hook("note_save",$this,"data",$data_id);
		}

		return $data_field_id;
	}

	function save_data_record() {

		$data = $_POST;


		$data_record_id = isset( $data['data_record_id'] ) ? $data['data_record_id'] : false;

		$data_type_id = (int) $data['data_type_id'];
		if ( ! $data_type_id ) {
			set_error( _l( 'Sorry no data type set' ) );

			return false;
		}
		$data_type = $this->get_data_type( $data_type_id );
		if ( ( ! $data_record_id || $data_record_id == 'new' ) && ! $this->can_i( 'create', $data_type['data_type_name'] ) ) {
			set_error( 'No permissions to create data' );

			return false;
		} else if ( (int) $data_record_id > 0 && ! $this->can_i( 'edit', $data_type['data_type_name'] ) ) {
			set_error( 'No permissions to edit data' );

			return false;
		} else if ( (int) $data_record_id > 0 && ! $this->can_i( 'delete', $data_type['data_type_name'] ) && isset( $_POST['butt_del'] ) ) {
			set_error( 'No permissions to delete data' );

			return false;
		} else if ( (int) $data_record_id > 0 && $this->can_i( 'delete', $data_type['data_type_name'] ) && isset( $_POST['butt_del'] ) ) {
			if ( module_form::confirm_delete(
				'data_record_id',
				"Really delete this entire data record?",
				$this->link( '', array( "data_record_id" => $data_record_id ) )
			) ) {
				$this->delete_data_record( $data_record_id );
				set_error( _l( "Data deleted successfully" ) );
				redirect_browser( $this->link() );
			}
		}
		if ( ! isset( $data['save_data_group'] ) || ! is_array( $data['save_data_group'] ) ) {
			// no information to save?? error
			set_error( _l( 'Sorry no group found to save' ) );

			return false;
		}

		if ( ( ! isset( $data['data_field'] ) || ! is_array( $data['data_field'] ) || ! count( $data['data_field'] ) ) && ! isset( $_FILES['data_field']['tmp_name'] ) ) {
			set_error( _l( 'Sorry, no data found to save' ) );

			return false;
		}

		if ( isset( $_REQUEST['form_id'] ) && $_REQUEST['form_id'] ) {
			$form_id = $_REQUEST['form_id'];
		} else {
			$form_id = 'default';
		}
		$_SESSION['_form_highlight'][ $form_id ] = array();

		//unset($data['data_type_id']);

		// first we check for required fields missing in the data field array.
		// return false on error, and set the error fields in session so they can be highligted on re-render
		$data_field_groups             = $this->get_data_field_groups( $data_type_id );
		$allowed_to_save               = array(); // an array of fields we are allowed to save in this save call.
		$missing_required_fields       = array();
		$missing_required_fields_names = array();
		$all_data_fields               = array(); // for history cache.
		foreach ( $data_field_groups as $data_field_group ) {
			// check if the user is posting data for this field.
			$data_field_group_id = $data_field_group['data_field_group_id'];
			if ( isset( $data['save_data_group'][ $data_field_group_id ] ) && $data['save_data_group'][ $data_field_group_id ] ) {
				$data_fields                             = $this->get_data_fields( $data_field_group_id );
				$all_data_fields[ $data_field_group_id ] = $data_fields;
				// loop over all fields, and ensure the ones that are required are present.
				foreach ( $data_fields as $data_field ) {
					$data_field_id = $data_field['data_field_id'];
					if ( $data_field['required'] ) {
						// depending on the type of field, there are different ways to 
						// check if the required field has been inserted.
						switch ( $data_field['field_type'] ) {
							case 'radio':
							case 'checkbox_list':
								if ( isset( $data['data_field'][ $data_field_id ] ) && strtolower( $data['data_field'][ $data_field_id ] ) == 'other' && ( ! isset( $data['other_data_field'][ $data_field_id ] ) || ! $data['other_data_field'][ $data_field_id ] ) ) {
									$missing_required_fields[ $data_field_id ]       = 'other';
									$missing_required_fields_names[ $data_field_id ] = $data_field['title'];
								} else if ( ! isset( $data['data_field'][ $data_field_id ] ) || ! $data['data_field'][ $data_field_id ] ) {
									$missing_required_fields[ $data_field_id ] = true;
								}
								break;
							case 'file':

								if ( ! is_array( $_FILES['data_field']['tmp_name'][ $data_field_id ] ) && ! is_array( $_FILES['data_field']['name'][ $data_field_id ] ) ) {
									$_FILES['data_field']['tmp_name'][ $data_field_id ] = array( $_FILES['data_field']['tmp_name'][ $data_field_id ] );
									$_FILES['data_field']['name'][ $data_field_id ]     = array( $_FILES['data_field']['name'][ $data_field_id ] );
								}
								$previous_files = isset( $previous_data_items[ $data_field_id ]['data_text'] ) ? @unserialize( $previous_data_items[ $data_field_id ]['data_text'] ) : array();
								if ( ! is_array( $previous_files ) ) {
									$previous_files = array();
								}

								foreach ( $_FILES['data_field']['tmp_name'][ $data_field_id ] as $file_upload_id => $file_tmp_name ) {
									if ( empty( $previous_files ) && ! is_uploaded_file( $file_tmp_name ) ) {
										$missing_required_fields[ $data_field_id ]       = true;
										$missing_required_fields_names[ $data_field_id ] = $data_field['title'];
									}
								}
								break;

							case 'created_date_time':
							case 'created_date':
							case 'created_time':
							case 'updated_date_time':
							case 'updated_date':
							case 'updated_time':
							case 'created_by':
							case 'updated_by':
								break;
							default:
								// normal text field etc..
								if ( ! isset( $data['data_field'][ $data_field_id ] ) || ! $data['data_field'][ $data_field_id ] ) {
									$missing_required_fields[ $data_field_id ]       = true;
									$missing_required_fields_names[ $data_field_id ] = $data_field['title'];
								}
								break;
						}
					}
					$allowed_to_save[ $data_field_id ] = true;
				}
			}
		}
		// we only want notes as required if the notes field is passed.
		/*if(isset($data['notes']) && !trim($data['notes'])){
			$missing_required_fields['notes']=true;
									$missing_required_fields_names[$data_field_id] = $data_field['name'];
		}*/
		if ( $missing_required_fields ) {
			set_error( _l( 'Required fields missing: %s', implode( ', ', $missing_required_fields_names ) ) );
			$_SESSION['_form_highlight'][ $form_id ] = $missing_required_fields;

			return false;
		}
		if ( ! count( $allowed_to_save ) ) {
			set_message( _l( 'Sorry, not fields found to save' ) );

			return false;
		}


		// check for 'other' option on radio boxes.

		// update the main data record to contain the latest information
		if ( $data_record_id && $data_record_id != 'new' ) {
			// updating a previous one
			$previous_data_record = $this->get_data_record( $data_record_id );
			$previous_data_items  = $this->get_data_items( $data_record_id );
			if ( ! $previous_data_record['status'] && ! $data['status'] ) {
				$data['status'] = 'new';
			}
		} else {
			if ( ! isset( $data['status'] ) || ! $data['status'] ) {
				$data['status'] = 'new';
			}
			$previous_data_record = false;
		}
		$data_record_id = update_insert( 'data_record_id', $data_record_id, 'data_record', $data );
		if ( ! $data_record_id ) {
			set_message( _l( 'Unable to save data record sorry' ) );

			return false;
		}
		// create a new revision to store this latest information, and link all the data field information to.
		$data['field_cache']       = serialize( $all_data_fields );
		$data['field_group_cache'] = serialize( $data_field_groups );
		$data['data_record_id']    = $data_record_id;
		$data_record_revision_id   = update_insert( 'data_record_revision_id', 'new', 'data_record_revision', $data );
		if ( ! $data_record_revision_id ) {
			set_message( _l( 'Unable to save data record revision sorry' ) );

			return false;
		}

		update_insert( 'data_record_id', $data_record_id, 'data_record', array( 'last_revision_id' => $data_record_revision_id ) );
		// save all the fields against this revision
		foreach ( $allowed_to_save as $data_field_id => $tf ) {
			$data_field = $this->get_data_field( $data_field_id );
			// incase admin updates during a save? probably will never fire.
			if ( $data_field['data_field_id'] != $data_field_id ) {
				continue; //skip to next field to save.
			}
			$data_field_data = false;
			switch ( $data_field['field_type'] ) {
				case 'radio':
				case 'checkbox_list':
					$data_field_data = isset( $data['data_field'][ $data_field_id ] ) ? $data['data_field'][ $data_field_id ] : false;
					if ( isset( $data['other_data_field'][ $data_field_id ] ) && $data['other_data_field'][ $data_field_id ] ) {
						$data_field_data = $data['other_data_field'][ $data_field_id ];
					}
					break;
				case 'file':
					// check the file has been uploaded.

					// handle multiple uploaded files.
					if ( ! is_array( $_FILES['data_field']['tmp_name'][ $data_field_id ] ) && ! is_array( $_FILES['data_field']['name'][ $data_field_id ] ) ) {
						$_FILES['data_field']['tmp_name'][ $data_field_id ] = array( $_FILES['data_field']['tmp_name'][ $data_field_id ] );
						$_FILES['data_field']['name'][ $data_field_id ]     = array( $_FILES['data_field']['name'][ $data_field_id ] );
					}
					$previous_files = isset( $previous_data_items[ $data_field_id ]['data_text'] ) ? @unserialize( $previous_data_items[ $data_field_id ]['data_text'] ) : array();
					if ( ! is_array( $previous_files ) ) {
						$previous_files = array();
					}
					if ( isset( $previous_files['name'] ) ) {
						$previous_files = array( $previous_files );
					}
					$data_field_data = array();
					$existing_files  = isset( $_POST['dynamic_file_existing'][ $data_field_id ] ) ? $_POST['dynamic_file_existing'][ $data_field_id ] : array();
					foreach ( $existing_files as $existing_file_id => $existing_file ) {
						if ( isset( $previous_files[ $existing_file_id ] ) ) {
							$data_field_data[] = $previous_files[ $existing_file_id ];
						}
					}

					foreach ( $_FILES['data_field']['tmp_name'][ $data_field_id ] as $file_upload_id => $file_tmp_name ) {
						if ( is_uploaded_file( $file_tmp_name ) && isset( $_FILES['data_field']['name'][ $data_field_id ][ $file_upload_id ] ) ) {
							$user_file = preg_replace( '/[^\w\.]+/', '', trim( basename( $_FILES['data_field']['name'][ $data_field_id ][ $file_upload_id ] ) ) );
							if ( strlen( $user_file ) ) {
								// move it into the upload folder and set a field data below.
								// not too worried about people uploading bad files here eg php scripts, cos it's all an internal project.
								$file_name = "$data_field_id-$data_record_id-$data_record_revision_id-$file_upload_id-custom";
								if ( ! is_dir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' ) ) {
									mkdir( _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/', 0777, true );
								}
								if ( move_uploaded_file( $file_tmp_name, _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' . $file_name ) ) {
									// upload success.
									$data_field_data[ $file_upload_id ] = array( 'file' => $file_name, 'name' => $user_file );
									//$data_field_data = serialize( array( 'file' => $file_name, 'name' => $user_file ) );
								}
							}
						}
					}
					//print_r($data_field_data); print_r($previous_files); print_r($_FILES); exit;

					if ( $data_field_data ) {
						if ( count( $data_field_data ) == 1 ) {
							$data_field_data = serialize( $data_field_data[0] );
						} else {
							$data_field_data = serialize( $data_field_data );
						}
					} else {
						$data_field_data = false;
					}
					//print_r($data_field_data);exit;
					break;
				default:
					$data_field_data = isset( $data['data_field'][ $data_field_id ] ) ? $data['data_field'][ $data_field_id ] : false;
			}
			// if the value has been posted, or we have a manual value set above (eg: a file)
			if ( $data_field_data !== false ) {
				if ( is_array( $data_field_data ) ) {
					$data_field_data = serialize( $data_field_data );
				}
				$store_data = array(
					'data_field_id'           => $data_field_id,
					'data_record_id'          => $data_record_id,
					'data_record_revision_id' => $data_record_revision_id,
					'data_text'               => $data_field_data,
					// todo - save number / varchar / etc.. pending on field type
					'data_number'             => 0, // float
					'data_varchar'            => '', // 255 string
					'data_field_settings'     => serialize( $data_field ), // current settings for this field.
				);
				//                print_r($store_data);
				// todo - check if there are any changes between this data VALUE and the previous revision value.
				// if there are no differences, then we dont bother saving it.
				$save_value = true;
				if ( $previous_data_record && isset( $previous_data_items[ $data_field_id ] ) ) {
					// check if any field attributes have changed.
					$save_value         = false;
					$previous_data_item = $previous_data_items[ $data_field_id ];
					//print_r($previous_data_item);exit;
					foreach ( array( 'data_text', 'data_number', 'data_varchar', 'data_field_settings' ) as $check_changes ) {
						if ( trim( $store_data[ $check_changes ] ) != trim( $previous_data_item[ $check_changes ] ) ) {
							//echo $store_data[$check_changes] . '<br> doesnt match <br>' ."\n" . $previous_data_item[$check_changes] . '<br><hr>';
							//exit;
							$save_value = true;
							break;
						}
					}
				}
				if ( $save_value ) {
					$data_store_id = update_insert( 'data_store_id', 'new', 'data_store', $store_data );
				}
			}

		}

		return $data_record_id;
	}

	function delete_data_record( $data_id ) {
		$data_id = (int) $data_id;
		if ( (int) $data_id > 0 && $this->can_i( 'delete', _MODULE_DATA_NAME ) ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "data_record WHERE data_record_id = '" . $data_id . "' LIMIT 1";
			query( $sql );
			$sql = "DELETE FROM " . _DB_PREFIX . "data_record_revision WHERE data_record_id = '" . $data_id . "'";
			query( $sql );
		}
	}

	function delete_data_fieldset( $data_field_group_id ) {
		$data_field_group_id = (int) $data_field_group_id;
		if ( (int) $data_field_group_id > 0 && $this->can_i( 'delete', _MODULE_DATA_NAME ) ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "data_field WHERE data_field_group_id = '" . $data_field_group_id . "'";
			query( $sql );
			$sql = "DELETE FROM " . _DB_PREFIX . "data_field_group WHERE data_field_group_id = '" . $data_field_group_id . "' LIMIT 1";
			query( $sql );
		}
	}

	function delete_data_type( $data_type_id ) {
		$data_type_id = (int) $data_type_id;
		if ( (int) $data_type_id > 0 && $this->can_i( 'delete', _MODULE_DATA_NAME ) ) {
			$records = get_multiple( 'data_record', array( 'data_type_id' => $data_type_id ) );
			foreach ( $records as $record ) {
				if ( $record['data_type_id'] == $data_type_id && $record['data_record_id'] > 0 ) {
					delete_from_db( 'data_record_revision', 'data_record_id', $record['data_record_id'] );
					delete_from_db( 'data_record', 'data_record_id', $record['data_record_id'] );
					delete_from_db( 'data_store', 'data_record_id', $record['data_record_id'] );
				}
			}
			delete_from_db( 'data_field', 'data_type_id', $data_type_id );
			delete_from_db( 'data_field_group', 'data_type_id', $data_type_id );
			$sql = "DELETE FROM " . _DB_PREFIX . "data_type WHERE data_type_id = '" . $data_type_id . "' LIMIT 1";
			query( $sql );
		}
	}

	public function record_access( $data_record_id ) {
		update_insert( 'data_access_id', 'new', 'data_access', array( 'data_record_id' => $data_record_id ) );
	}


	public static function get_data_items( $data_record_id, $data_record_revision_id = false ) {

		// first find all the data_field_id's that are to be used in this data_record
		$sql    = "SELECT DISTINCT data_field_id AS data_field_id FROM `" . _DB_PREFIX . "data_store` WHERE data_record_id = '" . (int) $data_record_id . "'";
		$fields = qa( $sql );
		$items  = array();
		foreach ( $fields as $field ) {
			$data_field_id = (int) $field['data_field_id'];
			if ( $data_field_id ) {
				$sql = "SELECT * FROM `" . _DB_PREFIX . "data_store` WHERE data_record_id = '" . (int) $data_record_id . "'";
				$sql .= " AND data_field_id = '" . (int) $data_field_id . "'";
				if ( $data_record_revision_id ) {
					$sql .= " AND data_record_revision_id <= '" . (int) $data_record_revision_id . "'";
				}
				$sql                     .= " ORDER BY data_record_revision_id DESC LIMIT 1";
				$items[ $data_field_id ] = qa1( $sql );
			}
		}
		//$search = array("data_record_id"=>$data_record_id); //,'data_record_revision_id'=>$data_record_revision_id);
		//$items = get_multiple("data_store",$search,false,'exact','date_created DESC');
		return $items;
	}

	function get_data_record( $data_record_id ) {
		$data = get_single( "data_record", "data_record_id", $data_record_id );
		if ( $data ) {
			// optional processing here later on.

		}

		return $data;
	}

	function get_data_record_revisions( $data_record_id ) {
		return get_multiple( 'data_record_revision', array( 'data_record_id' => $data_record_id ), 'data_record_revision_id', 'exact', 'date_created' );
	}

	public static function get_data_type( $data_type_id ) {
		$data = get_single( "data_type", "data_type_id", $data_type_id );
		if ( $data ) {
			// optional processing here later on.
			$sql           = "SELECT COUNT(*) as `count` FROM `" . _DB_PREFIX . "data_record` WHERE data_type_id = '" . (int) $data['data_type_id'] . "'";
			$res           = qa1( $sql );
			$data['count'] = $res['count'];
		}

		return $data;
	}

	public static function get_data_types( $search = array() ) {
		$data = get_multiple( "data_type", $search, "data_type_id", '', 'data_type_order' );
		if ( $data ) {
			// optional processing here later on.
		}

		return $data;
	}

	public static function get_menu_locations() {
		//todo - database this, or pass it out to other modules to get their available hook locations
		$default_locations = array(
			_CUSTOM_DATA_MENU_LOCATION_MAIN     => _l( 'Main Menu' ),
			_CUSTOM_DATA_MENU_LOCATION_CUSTOMER => _l( 'Customer Menu' ),
			_CUSTOM_DATA_MENU_LOCATION_NONE     => _l( 'Hidden' ),
		);
		$default_locations = hook_filter_var( 'custom_data_menu_locations', $default_locations );

		return $default_locations;
	}

	function get_data_field_groups( $data_type_id ) {
		$data = get_multiple( "data_field_group", array( 'data_type_id' => $data_type_id ), "data_field_group_id", 'exact', 'position' );
		if ( $data ) {
			// optional processing here later on.
		}

		return $data;
	}

	function get_data_field_group( & $data_field_group_id ) {
		$data = get_single( "data_field_group", "data_field_group_id", $data_field_group_id );
		if ( $data ) {
			// optional processing here later on.
		}

		return $data;
	}


	function get_data_field( $data_field_id ) {
		$data = get_single( "data_field", "data_field_id", $data_field_id );
		if ( $data ) {
			// optional processing here later on.
		}

		return $data;
	}

	function get_data_fields( $data_field_group_id ) {
		$data = get_multiple( "data_field", array( 'data_field_group_id' => $data_field_group_id ), "data_field_id", 'exact', '`order`' );
		if ( $data ) {
			// optional processing here later on.
		}

		return $data;
	}

	public function format_record_id( $data_type_id, $data_record_id ) {
		// find the date this was created, or use todays date.
		$data_record = $this->get_data_record( $data_record_id );
		if ( $data_record ) {
			$year = date( "Y", strtotime( $data_record['date_created'] ) );
		} else {
			$year = date( 'Y' );
		}

		return sprintf( _l( 'Y%2$s/%1$06d' ), $data_record_id, $year );
	}

	function next_record_id() {
		$sql = "SELECT LAST_INSERT_ID(data_field_id) AS `boob` FROM `" . _DB_PREFIX . "data_field` LIMIT 1";
		$res = qa1( $sql );

		return $res['boob'];
	}

	function is_highlight( $element ) {
		$highlight = false;
		if ( isset( $_SESSION['_form_highlight'] ) && isset( $GLOBALS['form_id'] ) && isset( $_SESSION['_form_highlight'][ $GLOBALS['form_id'] ] ) ) {
			$highlight = isset( $_SESSION['_form_highlight'][ $GLOBALS['form_id'] ][ $element['data_field_id'] ] );
			// there's an error on this form, we pull in any previously posted values so they re-present themselves here
			if ( isset( $_POST['data_field'] ) && is_array( $_POST['data_field'] ) && isset( $_POST['data_field'][ $element['data_field_id'] ] ) ) {
				// this field was posted before.
				$element['value'] = $_POST['data_field'][ $element['data_field_id'] ];
				if ( $element['value'] ) {
					$highlight = false;
				}
			}
			if ( ( $element['field_type'] == 'radio' || $element['field_type'] == 'checkbox_list' ) && isset( $_POST['other_data_field'] ) && is_array( $_POST['other_data_field'] ) && isset( $_POST['other_data_field'][ $element['data_field_id'] ] ) && strtolower( $_POST['data_field'][ $element['data_field_id'] ] ) == 'other' ) {
				// this field was posted before.
				$element['value'] = $_POST['other_data_field'][ $element['data_field_id'] ];
				if ( $element['value'] ) {
					$highlight = false;
				} else {
					$highlight = true;
				}
			}

		}
		//$element['highlight'] = $highlight;
		//return $element;
		return $highlight;
	}

	public static $hook_element_cache = array();

	function get_form_element( $element, $viewing_revision = false, $data_record = array() ) {

		$has_write_access = ! $viewing_revision;


		// convert our data field to an element.
		$element['name'] = isset( $element['name'] ) && ! empty( $element['name'] ) ? $element['name'] : 'data_field[' . $element['data_field_id'] . ']';
		$element['id']   = 'data_field_' . $element['data_field_id'] . '';
		$element['type'] = $element['field_type'];
		if ( ! isset( $element['value'] ) ) {
			$element['value'] = '';
		}

		if ( ! $has_write_access ) {
			//$element['disabled'] = 'disabled';
			$element['class'] = 'data_field_view';
		}

		$highlight = false;

		$this->ajax_edit = false;

		$input_name = $element['name'];
		if ( ! $input_name ) {
			return false;
		}
		if ( isset( $element['id'] ) && $element['id'] ) {
			$input_id = $element['id'];
		} else {
			$element['id'] = $input_name;
			$input_id      = $input_name;
		}


		//if(!$value && isset($_REQUEST[$input_name]))$value = $_REQUEST[$input_name];
		if ( ! $element['value'] ) {
			$element['value'] = $element['default'];
		}
		if ( ! is_array( $element['value'] ) ) {
			//$value=htmlspecialchars($value);
		}
		if ( ! isset( $element['class'] ) ) {
			$element['class'] = '';
		}
		$attr = $attr_other = '';

		if ( $has_write_access ) {

			if ( $element['type'] == 'radio' || $element['type'] == 'checkbox_list' ) {
				// hacky!
				if ( $element['required'] ) {
					$attr_other .= ' class="form_field form_field_required"';
				} else {
					$attr_other .= ' class="form_field"';
				}
			} else {
				$element['class'] .= " form_field";
				if ( $element['required'] ) {
					$element['class'] .= " form_field_required";
				}
			}


			switch ( $element['type'] ) {
				case 'date':
					$element['class'] .= " date_field";
					if ( ! isset( $element['size'] ) || ! $element['size'] ) {
						$element['size'] = 8;
					}
					if ( strtolower( $element['value'] ) == 'now' ) {
						$element['value'] = print_date( time() );
					}
					break;
				case 'datetime':
					$element['class'] .= " date_time_field";
					if ( ! isset( $element['size'] ) || ! $element['size'] ) {
						$element['size'] = 12;
					}
					if ( strtolower( $element['value'] ) == 'now' ) {
						$element['value'] = print_date( time(), true );
					}
					break;
			}


		}


		switch ( $element['type'] ) {
			case 'checkbox_list':
			case 'radio':
				$element['attributes'] = array();
				foreach ( explode( "\n", trim( $element['field_data'] ) ) as $line ) {
					$line = trim( $line );
					if ( preg_match( '/^attributes=/', $line ) ) {
						$line                  = preg_replace( '/^attributes=/', '', $line );
						$element['attributes'] = explode( "|", $line );
						break;
					}
				}
				break;
			case 'ajax':
				$element['source'] = array();
				foreach ( explode( "\n", trim( $element['field_data'] ) ) as $line ) {
					$line = trim( $line );
					if ( preg_match( '/^source=/', $line ) ) {
						$line              = preg_replace( '/^source=/', '', $line );
						$element['source'] = explode( "|", $line );
						break;
					}
				}
				break;
			case 'select':
				if ( ! isset( $element['attributes'] ) || ! is_array( $element['attributes'] ) || ! count( $element['attributes'] ) ) {
					$element['attributes'] = array();
					foreach ( explode( "\n", trim( $element['field_data'] ) ) as $line ) {
						$line = trim( $line );
						if ( preg_match( '/^attributes=/', $line ) ) {
							$line = preg_replace( '/^attributes=/', '', $line );
							if ( preg_match( '#hook:([\w_]+)$#', $line, $matches ) ) {
								// see if we get anything back from this hook.
								$attributes = array();
								$attributes = hook_filter_var( $matches[1], $attributes, $element );
								if ( is_array( $attributes ) ) {
									$element['attributes'] = $attributes;
								} else {
									$element['attributes'] = array( 'Unable to call hook: ' . $matches[1] );
								}
							} else {
								$element['attributes'] = explode( "|", $line );
							}
							break;
						}
					}
				}
				break;
		}

		if ( ! isset( $element['style'] ) ) {
			$element['style'] = '';
		}
		// we have to apply some custom width/height styles if they exist.
		$width = $height = false;
		foreach ( explode( "\n", trim( $element['field_data'] ) ) as $line ) {
			$line = trim( $line );
			if ( preg_match( '/^width=/', $line ) ) {
				$line  = preg_replace( '/^width=/', '', $line );
				$width = (int) $line;

			}
			if ( preg_match( '/^height=/', $line ) ) {
				$line   = preg_replace( '/^height=/', '', $line );
				$height = (int) $line;
			}
		}
		if ( ! $height && $height < _MIN_INPUT_HEIGHT ) {
			$height = _MIN_INPUT_HEIGHT;
		}
		switch ( $element['type'] ) {
			case 'text':
			case 'date':
			case 'datetime':
				if ( $width ) {
					$element['style'] .= 'width:' . $width . 'px; ';
				}
				break;
			case 'radio':
				if ( $width ) {
					$attr_other .= ' style="width:' . $width . 'px; "';
				}
				break;
			case 'textarea':
			case 'textbox':
				if ( $width ) {
					$element['style'] .= 'width:' . $width . 'px; ';
				}
				if ( $has_write_access && $height ) {
					$element['style'] .= 'height:' . $height . 'px; ';
				}
				break;
		}
		if ( isset( $element['width'] ) ) {
			unset( $element['width'] );
		}
		if ( isset( $element['height'] ) ) {
			unset( $element['height'] );
		}
		if ( isset( $element['multiple'] ) && $element['multiple'] ) {
			$element['multiple'] = true;
			// pass 'multiple' through to the UCM form builder which should handle everything
			$value_unserialize = @unserialize( $element['value'] );
			if ( is_array( $value_unserialize ) ) {
				if ( $element['type'] == 'file' && isset( $value_unserialize['file'] ) && $value_unserialize['name'] ) {
					$value_unserialize = array( $value_unserialize );
				}
				$element['values'] = $value_unserialize; // 'values' is used in the UCM field generation tool
			}
			$element['name'] = $element['name'] . '[]';
		} else {
			$element['multiple'] = false;
		}
		if ( ! $has_write_access && $element['multiple'] && isset( $element['values'] ) && is_array( $element['values'] ) ) {
			$element['value'] = $element['values'];
		}
		if ( is_array( $element['value'] ) && count( $element['value'] ) ) {
			$all_values = $element['value'];
		} else {
			$all_values = array( $element['value'] );
		}
		if ( $element['type'] == 'checkbox_list' ) {
			$test = @unserialize( $element['value'] );
			if ( is_array( $test ) && count( $test ) ) {
				$all_values = array( $test );
			} else {
				$all_values = array( $element['value'] );
			}
		}

		if ( $element['type'] == "cancel" && ! isset( $element['onclick'] ) ) {
			$element['onclick'] = "history.go(-1);";
		}

		if ( $highlight ) {
			$element['class'] .= " form_field_highlight";
		}

		/*if(!$has_write_access){
			$element['class'] .= ' form_disabled';
		}*/


		$attribute_keys = array(
			'class',
			'disabled',
			'onclick',
			'onfocus',
			'onmouseup',
			'onmousedown',
			'onchange',
			'size',
			'cols',
			'rows',
			'width',
			'style'
		);

		foreach ( $element as $key => $val ) {
			if ( ! is_array( $val ) && ! trim( $val ) ) {
				continue;
			}
			if ( in_array( strtolower( $key ), $attribute_keys ) ) {
				if ( in_array( strtolower( $key ), array( 'size', 'cols', 'rows', 'width', 'height' ) ) && (int) $val == 0 ) {
					continue;
				}
				$attr .= ' ' . $key . '="' . $val . '"';
			}
		}
		// check for default values, these are cleared when submitting the form
		if ( $element['default'] ) {
			$has_default = true;
		}

		$real_input_id   = $input_id;
		$real_input_name = $input_name;
		ob_start();


		foreach ( $all_values as $value_key => $value ) {

			if ( ! $has_write_access ) { // disabled.

				if ( $element['multiple'] ) {
					echo '<div class="data_multiple_value">';
				}
				if ( isset( $width ) && $width && $element['type'] != 'encrypted' ) {
					echo '<span ' . $attr;
					echo ' style="width:' . $width . 'px;"';
					echo '>&nbsp;';
				}
				// display value differently depending on value type.


				switch ( $element['type'] ) {
					case 'auto_id':
						echo $this->format_record_id( $data_record['data_type_id'], $data_record['data_record_id'] );
						break;
					case 'checkbox_list':
						$other = '';
						if ( is_array( $value ) ) {
							if ( isset( $value['other_val'] ) ) {
								$other = $value['other_val'];
								unset( $value['other_val'] );
							}
							echo implode( ', ', array_keys( $value ) );
						}
						echo ' ' . $other;
						break;
					case 'select':
						if ( isset( $element['attributes'] ) ) {
							$attributes = $element['attributes'];
						} else {
							$attributes = array();
						}
						if ( isset( $attributes[0] ) ) {
							$new_attributes = array();
							foreach ( $attributes as $aid => $a ) {
								$new_attributes[ $aid + 1 ] = $a;
							}
							$attributes = $new_attributes;
						}
						if ( isset( $attributes[ $value ] ) ) {
							echo $attributes[ $value ];
						}
						break;
					case 'textarea':
					case 'textbox':
						echo nl2br( htmlspecialchars( $value ) );
						break;
					case 'file':
						if ( $value ) {

							if ( is_array( $value ) ) {
								$file_data = $value;
							} else {
								$file_data = @unserialize( $value );
							}
							$file_link = _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' . basename( $file_data['file'] );
							if ( is_file( $file_link ) ) {
								$download_link = self::link_public_file_download( $data_record['data_record_id'], $data_record['data_type_id'], $element['data_field_group_id'], $element['data_field_id'], $value_key );
								if ( preg_match( '/\.(\w\w\w\w?)$/', $file_data['name'], $matches ) ) {
									switch ( strtolower( $matches[1] ) ) {
										case 'jpg':
										case 'jpeg':
										case 'gif':
										case 'png':
											if ( module_config::c( 'data_show_image_preview', 1 ) ) {
												$thumb_width = (int) module_config::c( 'file_image_preview_width', 100 );
												?>
												<div class="file_preview"
												     style="float:left; width:<?php echo $thumb_width + 10; ?>px; margin:3px; border:1px solid #CCC; text-align:center;">
													<div style="width:<?php echo $thumb_width + 10; ?>px; min-height:40px; ">
														<a href="<?php echo $download_link; ?>" class="file_image_preview"
														   data-featherlight="image">
															<img src="<?php
															// echo _BASE_HREF . nl2br(htmlspecialchars($file_item['file_path']));
															echo $download_link;
															?>" width="<?php echo $thumb_width; ?>" alt="download" border="0">
														</a>
													</div>
													<?php echo '<a href="' . $download_link . '" target="_blank">' . htmlspecialchars( $file_data['name'] ) . '</a>'; ?>
												</div>
												<?php
												break;
											}
										default:
											echo '<a href="' . $download_link . '" target="_blank">' . htmlspecialchars( $file_data['name'] ) . '</a>';
									}
								}

							} else {
								echo 'File Not Found';
							}
						}
						break;
					case 'wysiwyg':
						echo module_security::purify_html( $value );
						break;
					case 'encrypted':
						if ( class_exists( 'module_encrypt', false ) ) {
							ob_start();
							$element['type'] = 'text';
							module_form::generate_form_element( $element );
							$enc_html = ob_get_clean();
							echo module_encrypt::parse_html_input( 'custom_data', $enc_html, false );
						}
						break;
					case 'created_date_time':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], true ) : _l( 'N/A' );
						break;
					case 'created_date':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], false ) : _l( 'N/A' );
						break;
					case 'created_time':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_created'] ) ) : _l( 'N/A' );
						break;
					case 'updated_date_time':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_updated'], true ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], true ) : _l( 'N/A' ) );
						break;
					case 'updated_date':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_updated'], false ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], false ) : _l( 'N/A' ) );
						break;
					case 'updated_time':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_updated'] ) ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_created'] ) ) : _l( 'N/A' ) );
						break;
					case 'created_by':
						echo isset( $data_record['create_user_id'] ) && (int) $data_record['create_user_id'] > 0 ? module_user::link_open( $data_record['create_user_id'], true ) : _l( 'N/A' );
						break;
					case 'updated_by':
						echo isset( $data_record['update_user_id'] ) && (int) $data_record['update_user_id'] > 0 ? module_user::link_open( $data_record['update_user_id'], true ) : ( isset( $data_record['create_user_id'] ) && (int) $data_record['create_user_id'] > 0 ? module_user::link_open( $data_record['create_user_id'], true ) : _l( 'N/A' ) );
						break;
					case 'url':
						echo '<a href="' . htmlspecialchars( $value ) . '" target="_blank">' . htmlspecialchars( $value ) . '</a>';
						break;
					case 'ajax':
						echo 'ajax';
						break;
					default:
						echo htmlspecialchars( $value );
						break;
				}
				if ( isset( $width ) && $width && $element['type'] != 'encrypted' ) {
					echo '&nbsp;</span>';
				}

				if ( $element['multiple'] ) {
					echo '</div>';
				}

			} else {

				// update for UCM: use the ucm form generator
				if ( isset( $element['default'] ) && $element['default'] && ! $element['value'] ) {
					$element['value'] = $element['default'];
				}

				switch ( $element['type'] ) {
					case 'auto_id':
						echo $this->format_record_id( false, $data_record['data_record_id'] );
						break;
					case 'wysiwyg':
						$element['options']['inline'] = false;
						module_form::generate_form_element( $element );
						break;
					case "radio":
						$has_val = false;
						foreach ( $element['attributes'] as $attribute ) {
							$this_input_id = $input_id . preg_replace( '/[^a-zA-Z]/', '', $attribute );
							?>
							<span class="field_radio">
                            <input type="radio" name="<?php echo $input_name; ?>" id="<?php echo $this_input_id; ?>"
                                   value="<?php echo htmlspecialchars( $attribute ); ?>"<?php
                            if ( $attribute == $value || ( strtolower( $attribute ) == 'other' && ! $has_val ) ) {
	                            // assumes "OTHER" is always last... fix with a separate loop before hand checking all vals
	                            if ( strtolower( $attribute ) != 'other' ) {
		                            $has_val = true;
	                            }
	                            echo " checked";
                            }
                            echo ' ' . $attr;
                            if ( strtolower( $attribute ) == 'other' ) {
	                            echo ' onmouseup="if(this.checked)$(\'#other_' . $this_input_id . '\')[0].focus();"';
	                            echo ' onchange="if(this.checked)$(\'#other_' . $this_input_id . '\')[0].focus();"';
                            }
                            ?>>
                                <label for="<?php echo $this_input_id; ?>"><?php echo $attribute; ?></label>
								<?php if ( strtolower( $attribute ) == 'other' ) { ?>
									<span class="data_field_input">
                                    <input type="text" name="other_<?php echo $input_name; ?>"
                                           id="other_<?php echo $this_input_id; ?>" value="<?php if ( ! $has_val ) {
	                                    echo htmlspecialchars( $value );
                                    } ?>"
                                           onchange="$('input[type=radio]',$(this).parent())[0].checked = true;" <?php echo $attr . $attr_other; ?>>
                                    </span>
								<?php } ?>
                            </span>
						<?php }
						break;
					case "checkbox_list":
						$has_val = false;
						if ( ! is_array( $value ) ) {
							$value = array();
						}
						foreach ( $element['attributes'] as $attribute ) {
							$this_input_id = $input_id . preg_replace( '/[^a-zA-Z]/', '', $attribute );
							?>
							<span class="field_radio">
                            <input type="checkbox"
                                   name="<?php echo $input_name; ?>[<?php echo htmlspecialchars( $attribute ); ?>]"
                                   id="<?php echo $this_input_id; ?>" value="1"<?php
                            if ( isset( $value[ $attribute ] ) ) {
	                            if ( strtolower( $attribute ) != 'other' ) {
		                            $has_val = true;
	                            }
	                            echo " checked";
                            }
                            echo ' ' . $attr;
                            if ( strtolower( $attribute ) == 'other' ) {
	                            echo ' onmouseup="if(this.checked)$(\'#other_' . $this_input_id . '\')[0].focus();"';
	                            echo ' onchange="if(this.checked)$(\'#other_' . $this_input_id . '\')[0].focus();"';
                            }
                            ?>>
                                <label for="<?php echo $this_input_id; ?>"><?php echo $attribute; ?></label>
								<?php if ( strtolower( $attribute ) == 'other' ) { ?>
									<span class="data_field_input">
                                        <input type="text" name="<?php echo $input_name; ?>[other_val]"
                                               id="other_<?php echo $this_input_id; ?>" value="<?php
                                        echo isset( $value['other_val'] ) ? htmlspecialchars( $value['other_val'] ) : '';
                                        ?>"
                                               onchange="$('input[type=radio]',$(this).parent())[0].checked = true;" <?php echo $attr . $attr_other; ?>>
                                    </span>
								<?php } ?>
                            </span>
						<?php }
						break;
					case "file":
						$this->has_files = true;
						if ( isset( $element['values'] ) && count( $element['values'] ) ) {
							$element['multiple_pre'] = array();
							foreach ( $element['values'] as $multi_pre_key => $multi_pre_value ) {
								$element['multiple_pre'][ $multi_pre_key ] = '';
								if ( ! empty( $multi_pre_value['file'] ) ) {
									//$file_link = 'includes/plugin_data/upload/' . basename( $multi_pre_value['file'] );
									$file_link = _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' . basename( $multi_pre_value['file'] );
									if ( is_file( $file_link ) ) {
										$download_link                             = self::link_public_file_download( $data_record['data_record_id'], $data_record['data_type_id'], $element['data_field_group_id'], $element['data_field_id'], $multi_pre_key );
										$element['multiple_pre'][ $multi_pre_key ] = '<a href="' . $download_link . '" target="_blank" style="float:left;padding:7px;" class="dynamic_clear custom_data_file_name">' . htmlspecialchars( $multi_pre_value['name'] ) . '</a> <input type="hidden" name="dynamic_file_existing[' . $element['data_field_id'] . '][' . $multi_pre_key . ']" value="1">';
									}
								}
							}
						} else {
							if ( $value && ! is_array( $value ) ) {
								$value_temp = unserialize( $value );
							} else if ( is_array( $value ) ) {
								$value_temp = $value;
							} else {
								$value_temp = false;
							}
							if ( $value_temp ) {
								if ( ! empty( $value_temp['file'] ) ) {
									$file_link = _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' . basename( $value_temp['file'] );
									if ( is_file( $file_link ) ) {
										$download_link = self::link_public_file_download( $data_record['data_record_id'], $data_record['data_type_id'], $element['data_field_group_id'], $element['data_field_id'], 0 );
										echo '<a href="' . $download_link . '" target="_blank" style="float:left;padding:2px 7px;">' . htmlspecialchars( $value_temp['name'] ) . '</a>';
									}
								}
							}
						}
						module_form::generate_form_element( $element );
						/*?>
	                    <br><br><br><hr><br><br>
                        <input type="file" name="<?php echo $input_name;?>" id="<?php echo $input_id;?>" value="<?php echo $value;?>"<?php echo $attr;?>>
                        <?php
                        */
						break;
					case 'select':
						$attributes = isset( $element['attributes'] ) ? $element['attributes'] : array();
						if ( isset( $attributes[0] ) ) {
							$new_attributes = array();
							foreach ( $attributes as $aid => $a ) {
								$new_attributes[ $aid + 1 ] = $a;
							}
							$attributes = $new_attributes;
						}
						$element['options'] = $attributes;
						module_form::generate_form_element( $element );
						break;
					case 'ajax':
						$source = isset( $element['source'] ) ? $element['source'] : array();
						if ( ! empty( $source[0] ) ) {

							$element['type']   = 'type';
							$element['lookup'] = array(
								'key'         => $source[0] . '_id',
								'display_key' => ! empty( $source[1] ) ? $source[1] : $source[0] . '_id',
								'plugin'      => $source[0],
								'lookup'      => ! empty( $source[2] ) ? $source[2] : ( ! empty( $source[1] ) ? $source[1] : $source[0] . '_id' ),
								'display'     => '',
							);
							module_form::generate_form_element( $element );
						}

						break;
					case 'encrypted':
						if ( class_exists( 'module_encrypt', false ) ) {
							ob_start();
							$element['type'] = 'text';
							module_form::generate_form_element( $element );
							$enc_html = ob_get_clean();
							echo module_encrypt::parse_html_input( 'custom_data', $enc_html );
						}
						break;
					case 'created_date_time':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], true ) : _l( 'N/A' );
						break;
					case 'created_date':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], false ) : _l( 'N/A' );
						break;
					case 'created_time':
						echo isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_created'] ) ) : _l( 'N/A' );
						break;
					case 'updated_date_time':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_updated'], true ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], true ) : _l( 'N/A' ) );
						break;
					case 'updated_date':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_updated'], false ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? print_date( $data_record['date_created'], false ) : _l( 'N/A' ) );
						break;
					case 'updated_time':
						echo isset( $data_record['date_updated'] ) && $data_record['date_updated'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_updated'] ) ) : ( isset( $data_record['date_created'] ) && $data_record['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data_record['date_created'] ) ) : _l( 'N/A' ) );
						break;
					case 'created_by':
						echo isset( $data_record['create_user_id'] ) && (int) $data_record['create_user_id'] > 0 ? module_user::link_open( $data_record['create_user_id'], true ) : _l( 'N/A' );
						break;
					case 'updated_by':
						echo isset( $data_record['update_user_id'] ) && (int) $data_record['update_user_id'] > 0 ? module_user::link_open( $data_record['update_user_id'], true ) : ( isset( $data_record['create_user_id'] ) && (int) $data_record['create_user_id'] > 0 ? module_user::link_open( $data_record['create_user_id'], true ) : _l( 'N/A' ) );
						break;
					case 'url':
						$element['type'] = 'text';
						module_form::generate_form_element( $element );
						break;
					default:
						module_form::generate_form_element( $element );
				}

				if ( isset( $element['after_link'] ) && $element['after_link'] ) {
					if ( preg_match( '/^https?:\/\//', $value ) || $have_www = preg_match( '/^www\./', $value ) || $have_www = ( preg_match( '/\.com/', $value ) && ! preg_match( '/\w@\w/', $value ) ) ) {
						// we have a url
						if ( $have_www ) {
							$value = "http://" . $value;
						}
						?>
						<a href="<?php echo $value; ?>" target="_blank">&raquo;</a>
					<?php }
					if ( preg_match( '/\w@\w/', $value ) ) { ?>
						<a href="mailto:<?php echo $value; ?>">&raquo;</a>
					<?php }
				}


			} // editable
		} // foreach all values
		//${$element_id} = ob_get_clean();
		// we just print the html for now
		return ob_get_clean();
	}


	public static function link_public_file_download( $data_record_id, $data_type_id, $data_field_group_id, $data_field_id, $value_key = 0, $h = false ) {
		if ( $h ) {
			return md5( 'hash for Download file ' . _UCM_SECRET . ' ' . $data_record_id . ' ' . $data_type_id . ' ' . $data_field_group_id . ' ' . $data_field_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.data/h.download/dri.' . $data_record_id . '/dti.' . $data_type_id . '/dfgi.' . $data_field_group_id . '/dfi.' . $data_field_id . '/dfv.' . (int) $value_key . '/hash.' . self::link_public_file_download( $data_record_id, $data_type_id, $data_field_group_id, $data_field_id, $value_key, true ) );
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'download':
				$data_record_id      = isset( $_REQUEST['dri'] ) ? (int) $_REQUEST['dri'] : false;
				$data_type_id        = isset( $_REQUEST['dti'] ) ? (int) $_REQUEST['dti'] : false;
				$data_field_group_id = isset( $_REQUEST['dfgi'] ) ? (int) $_REQUEST['dfgi'] : false;
				$data_field_id       = isset( $_REQUEST['dfi'] ) ? (int) $_REQUEST['dfi'] : false;
				$value_key           = isset( $_REQUEST['dfv'] ) ? (int) $_REQUEST['dfv'] : false;
				$hash                = isset( $_REQUEST['hash'] ) ? $_REQUEST['hash'] : false;
				$real_hash           = self::link_public_file_download( $data_record_id, $data_type_id, $data_field_group_id, $data_field_id, $value_key, true );
				if ( $real_hash == $hash ) {
					$data = self::get_data_items( $data_record_id );
					if ( $data && isset( $data[ $data_field_id ] ) ) {
						$file_data = @unserialize( $data[ $data_field_id ]['data_text'] );
						if ( count( $file_data ) && ! isset( $file_data['file'] ) && isset( $file_data[ $value_key ]['file'] ) ) {
							$file_data = $file_data[ $value_key ];
						}
						if ( is_array( $file_data ) && isset( $file_data['file'] ) && $file_data['file'] ) {
							$file_link = _UCM_FILE_STORAGE_DIR . 'includes/plugin_data/upload/' . basename( $file_data['file'] );
							if ( is_file( $file_link ) ) {
								@ob_end_clean();
								header( "Pragma: public" );
								header( "Expires: 0" );
								header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
								header( "Cache-Control: private", false );
								//header("Content-Type: application/pdf");
								header( "Content-Disposition: attachment; filename=\"" . basename( $file_data['name'] ) . "\";" );
								header( "Content-Transfer-Encoding: binary" );
								$filesize = filesize( $file_link );
								if ( $filesize > 0 ) {
									header( "Content-Length: " . $filesize );
								}
								// some hosting providershave issues with readfile()
								$read = readfile( $file_link );
								if ( ! $read ) {
									echo file_get_contents( $file_link );
								}
								exit;
							}
						}
					}
				}
				echo 'File Not Found';
				exit;
				break;
		}
	}


	function get_data_statuses() {
		return get_col_vals( "data_record", "status" );
	}


	function get_datas( $search = false ) {
		return get_multiple( "data_record", $search, "data_record_id", "fuzzy" );
	}

	function get_data_link_keys() {
		return array( 'customer_id', 'job_id', 'invoice_id', 'quote_id', 'file_id' );
	}


	public static function hook_location( $call, $hook_location, $owner_table, $owner_id ) {
		if ( $owner_table && $owner_id ) {
			// find any custom data items registered to this display location.
			$data_types = self::get_data_types( array( 'data_type_menu' => $hook_location ) );
			foreach ( $data_types as $data_type ) {
				$data_type_id = $data_type['data_type_id'];
				include( 'includes/plugin_data/pages/admin_data_list_embed.php' );
			}
		}
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();


		// get a list of active customers
		if ( is_numeric( $search_options['lookup'] ) && ! empty( $search_options['search'] ) ) {
			$data_type = $this->get_data_type( $search_options['lookup'] );
			if ( $data_type && $data_type['data_type_id'] == $search_options['lookup'] ) {
				if ( $this->can_i( 'view', $data_type['data_type_name'] ) ) {

					$search                 = array();
					$search['data_type_id'] = $data_type['data_type_id'];
					if ( ! empty( $search_options['vars']['customer_id'] ) ) {
						$search['customer_id'] = $search_options['vars']['customer_id'];
					}
					// todo: we have to limit the data types to only those created by current user if they are not administration
					$datas = $this->get_datas( $search );
					if ( ! $datas && ! empty( $search['customer_id'] ) ) {
						$search['customer_id'] = 0;
						$datas                 = $this->get_datas( $search );
					}
					// todo: copy search stuff from admin_data_search.php and make it work here as well.
					// text search on any field.
					foreach ( $datas as $data ) {

						$data_items = $this->get_data_items( $data['data_record_id'] );
						$this_match = false;
						foreach ( $data_items as $data_item ) {
							if ( ! empty( $search_options['display_key'] ) ) {
								// we only search/display this key.
								if ( $data_item['data_field_id'] == $search_options['display_key'] ) {
									if ( stripos( $data_item['data_text'], $search_options['search'] ) !== false ) {
										$this_match = $data_item;
										break;
									}
								}
							} else if ( stripos( $data_item['data_text'], $search_options['search'] ) !== false ) {
								// otherweise we search all fields in this data record.
								$this_match = $data_item;
								break;
							}
						}
						if ( $this_match ) {
							$result[] = array(
								'key'   => $data['data_record_id'],
								'value' => $this_match['data_text'],
							);
						}
					}

				}
			}
		}


		// sort our results alphabetically.
		// this should probably be done in the parent autocomplete call.
		// so it happens for everything. oh well.
		usort( $result, array( $this, 'autocomplete_sort' ) );

		return $result;
	}

	public function autocomplete_sort( $a, $b ) {
		return strnatcasecmp( $a['value'], $b['value'] );
	}

	// used for working out the display value for a key.
	public function autocomplete_display( $data_record_id = 0, $search_options = array() ) {
		if ( $data_record_id ) {
			$data_items = $this->get_data_items( $data_record_id );
			// todo: permissions/
			if ( ! empty( $search_options['display_key'] ) && isset( $data_items[ $search_options['display_key'] ] ) ) {
				if ( ! empty( $search_options['return_more'] ) ) {
					// this is used in the 'extra' plugin to get more data through to the invoice
					$return = array();
					foreach ( $data_items as $data_item ) {
						$settings = @unserialize( $data_item['data_field_settings'] );
						if ( $settings && ! empty( $settings['title'] ) ) {
							$return[ $settings['title'] ] = $data_item['data_text'];
						}
					}
					if ( $return ) {
						return $return;
					}
				}

				return $data_items[ $search_options['display_key'] ]['data_text'];
			}
		}

		return '';
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_access` (
		`data_access_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_record_id` int(11) NOT NULL,
		`create_ip_address` varchar(15) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_access_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_field` (
		`data_field_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_type_id` int(11) NOT NULL,
		`data_field_group_id` int(11) DEFAULT '0',
		`title` varchar(255) NOT NULL,
		`order` int(11) DEFAULT '0',
		`field_type` varchar(20) NOT NULL,
		`field_data` text,
		`searchable` tinyint(1) NOT NULL DEFAULT '1',
		`default` varchar(255) DEFAULT NULL,
		`width` int(11) NOT NULL DEFAULT '0',
		`height` int(11) NOT NULL DEFAULT '0',
		`required` tinyint(1) NOT NULL,
		`reportable` tinyint(1) NOT NULL DEFAULT '1',
		`show_list` tinyint(1) NOT NULL,
		`multiple` tinyint(1) NOT NULL DEFAULT '0',
		`display_group_if` text,
		`create_user_id` int(11) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_field_id`),
		KEY `field_type` (`field_type`),
		KEY `data_type_id` (`data_type_id`),
		KEY `data_field_group_id` (`data_field_group_id`),
		KEY `order` (`order`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=70 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_field_group` (
		`data_field_group_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_type_id` int(11) NOT NULL,
		`data_field_id` int(11) DEFAULT NULL COMMENT 'theh holder of this field',
		`title` varchar(255) NOT NULL,
		`layout` varchar(10) NOT NULL DEFAULT '',
		`position` int(11) NOT NULL DEFAULT '0',
		`sub_data_type_id` int(11) NOT NULL DEFAULT '0',
		`display_default` tinyint(1) NOT NULL DEFAULT '1',
		`create_user_id` int(11) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_field_group_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_record` (
		`data_record_id` int(11) NOT NULL AUTO_INCREMENT,
		`parent_data_record_id` int(11) DEFAULT NULL,
		`last_revision_id` int(11) NOT NULL,
		`customer_id` int(11) NOT NULL DEFAULT '0',
		`job_id` int(11) NOT NULL DEFAULT '0',
		`invoice_id` int(11) NOT NULL DEFAULT '0',
		`quote_id` int(11) NOT NULL DEFAULT '0',
		`file_id` int(11) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL,
		`friendly_id` int(11) NOT NULL,
		`data_type_id` int(11) NOT NULL,
		`status` varchar(255) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`create_ip_address` varchar(15) DEFAULT NULL,
		`update_user_id` int(11) DEFAULT NULL,
		`update_ip_address` varchar(15) DEFAULT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_record_id`),
		KEY `friendly_id` (`friendly_id`),
		KEY `data_type_id` (`data_type_id`),
		KEY `status` (`status`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_record_revision` (
		`data_record_revision_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_record_id` int(11) NOT NULL,
		`notes` text NOT NULL,
		`status` varchar(20) NOT NULL,
		`field_group_cache` text NOT NULL,
		`field_cache` text NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`create_ip_address` varchar(15) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_record_revision_id`),
		KEY `data_record_id` (`data_record_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_store` (
		`data_store_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_field_id` int(11) NOT NULL,
		`data_record_id` int(11) NOT NULL,
		`data_record_revision_id` int(11) NOT NULL,
		`data_text` text,
		`data_number` float DEFAULT NULL,
		`data_varchar` varchar(255) DEFAULT NULL,
		`data_field_settings` text NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`create_ip_address` varchar(15) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_store_id`),
		UNIQUE KEY `data_field_id_2` (`data_field_id`,`data_record_revision_id`),
		KEY `data_field_id` (`data_field_id`),
		KEY `data_record_revision_id` (`data_record_revision_id`),
		KEY `data_record_id` (`data_record_id`),
		KEY `data_number` (`data_number`),
		KEY `data_varchar` (`data_varchar`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>data_type` (
		`data_type_id` int(11) NOT NULL AUTO_INCREMENT,
		`data_type_name` varchar(255) NOT NULL,
		`data_type_icon` varchar(255) NOT NULL,
		`data_type_order` int(11) DEFAULT NULL,
		`parent_data_type_id` int(11) DEFAULT NULL,
		`hook_location` varchar(20) NOT NULL DEFAULT '',
		`data_type_menu` int(11) NOT NULL DEFAULT '1',
		`create_user_id` int(11) NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`data_type_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;


		INSERT INTO `<?php echo _DB_PREFIX; ?>data_field` (`data_field_id`, `data_type_id`, `data_field_group_id`, `title`, `order`, `field_type`, `field_data`, `searchable`, `default`, `width`, `height`, `required`, `reportable`, `show_list`, `display_group_if`, `create_user_id`, `date_created`, `date_updated`) VALUES
		(54, 3, 9, 'Subject', 1, 'text', '', 1, '', 0, 0, 1, 0, 1, NULL, 1, '2014-01-05 12:55:29', '2014-01-05 13:23:35'),
		(55, 3, 9, 'Note', 2, 'wysiwyg', '', 1, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 12:55:58', '0000-00-00 00:00:00'),
		(56, 3, 9, 'Created Date', 3, 'date', '', 1, 'NOW', 0, 0, 0, 0, 1, NULL, 1, '2014-01-05 12:57:08', '2014-01-05 12:57:29'),
		(57, 3, 9, 'Created By', 4, 'text', '', 1, '', 0, 0, 0, 0, 1, NULL, 1, '2014-01-05 12:57:43', '0000-00-00 00:00:00'),
		(58, 3, 10, 'Yes/No', 1, 'radio', 'attributes=Yes|No|Maybe', 0, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 13:14:13', '0000-00-00 00:00:00'),
		(59, 3, 10, 'Select Box', 2, 'select', 'attributes=One|Two|Three', 0, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 13:14:46', '0000-00-00 00:00:00'),
		(60, 3, 10, 'Radio with Other', 3, 'radio', 'attributes=One|Two|Three|Other', 0, 'Two', 0, 0, 1, 0, 0, NULL, 1, '2014-01-05 13:15:23', '2014-01-05 13:23:46'),
		(61, 3, 10, 'Text Box', 4, 'textarea', 'width=459\nheight=119\n', 0, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 13:16:06', '2014-01-05 13:20:06'),
		(64, 3, 10, 'Text with Default', 5, 'text', 'width=459\r\n', 0, 'default value', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 13:17:47', '2014-01-05 13:21:58'),
		(67, 3, 10, 'Checkbox', 6, 'checkbox', '', 0, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 13:24:25', '0000-00-00 00:00:00'),
		(68, 3, 10, 'Checkbox list', 7, 'checkbox_list', 'attributes=One|Two|Three|Four|Other', 1, '', 0, 0, 0, 0, 1, NULL, 1, '2014-01-05 13:25:36', '2014-01-05 13:29:32'),
		(69, 3, 10, 'File Upload', 8, 'file', '', 0, '', 0, 0, 0, 0, 0, NULL, 1, '2014-01-05 14:01:34', '0000-00-00 00:00:00');

		INSERT INTO `<?php echo _DB_PREFIX; ?>data_field_group` (`data_field_group_id`, `data_type_id`, `data_field_id`, `title`, `layout`, `display_default`, `create_user_id`, `date_created`, `date_updated`) VALUES
		(9, 3, NULL, 'General Notes', '', 1, 1, '2014-01-05 12:55:08', '2014-01-05 12:55:13'),
		(10, 3, NULL, 'Example Fields', '', 1, 1, '2014-01-05 13:13:43', '2014-01-05 14:01:24');


		INSERT INTO `<?php echo _DB_PREFIX; ?>data_type` (`data_type_id`, `data_type_name`, `data_type_order`, `parent_data_type_id`, `hook_location`, `data_type_menu`, `create_user_id`, `date_created`, `date_updated`) VALUES
		(3, 'Notes Example', NULL, NULL, '', 3, 1, '2014-01-05 12:54:55', '2014-01-05 14:01:16');

		<?php
		return ob_get_clean();
	}

	public function is_installed() {
		return $this->db_table_exists( 'data_type' );
	}

	public function get_upgrade_sql() {
		$sql = '';

		$fields = get_fields( 'data_record' );
		if ( ! isset( $fields['job_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_record` ADD  `job_id` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['invoice_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_record` ADD  `invoice_id` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['quote_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_record` ADD  `quote_id` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['file_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_record` ADD  `file_id` int(11) NOT NULL DEFAULT \'0\';';
		}

		$fields = get_fields( 'data_field' );
		if ( ! isset( $fields['multiple'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_field` ADD  `multiple` int(11) NOT NULL DEFAULT \'0\';';
		}
		$fields = get_fields( 'data_field_group' );
		if ( ! isset( $fields['position'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_field_group` ADD  `position` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['sub_data_type_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_field_group` ADD  `sub_data_type_id` int(11) NOT NULL DEFAULT \'0\';';
		}
		$fields = get_fields( 'data_type' );
		if ( ! isset( $fields['data_type_icon'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_type` ADD  `data_type_icon` varchar(255) NOT NULL DEFAULT \'\' AFTER `data_type_name`;';
		}
		if ( ! isset( $fields['max_entries'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_type` ADD  `max_entries` int(11) NOT NULL DEFAULT \'0\' AFTER `data_type_name`;';
		}
		if ( ! isset( $fields['print_pdf_template'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_type` ADD  `print_pdf_template` varchar(255) NOT NULL DEFAULT \'\' AFTER `data_type_name`;';
		}
		if ( ! isset( $fields['email_template'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'data_type` ADD  `email_template` varchar(255) NOT NULL DEFAULT \'\' AFTER `data_type_name`;';
		}

		return $sql;
	}


}