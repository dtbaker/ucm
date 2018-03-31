<?php

class module_base {
	public $module_name = '';
	public $ajax_search_keys;
	public $page_title;
	public $links = array();
	public $module_position = 0;
	public $parent_module = false;
	public $parent_module_table_id = false;
	public $parent_module_page = false;
	public $link_include_parents = false;
	public $version = _UCM_VERSION;
	private $_pre_menu_done = false;

	public function pre_menu() {
	}

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		$class_name = $module;
		if ( ! $module ) {
			// php5.2 doesn't have get_called_class() :(
			if ( function_exists( 'get_called_class' ) ) {
				$class_name = get_called_class();
			} else if ( is_callable( 'self::get_class()' ) ) {
				eval( '$class_name = self::get_class();' );
			} else {
				// doesn't work in php5.2
				eval( '$class_name = static::get_class();' );
			}
			if ( ! $class_name ) {
				echo 'no class found - please upgrade to php5.3';
			}
		}

		if ( ! $name ) {
			$name = ucwords( str_replace( '_', ' ', str_replace( 'module_', '', $class_name ) ) );
		}
		if ( ! $name ) {
			return false;
		}
		if ( ! $category ) {
			$category = ucwords( str_replace( '_', ' ', str_replace( 'module_', '', $class_name ) ) );
		}
		$perms = array(
			'name'        => $name,
			'module'      => str_replace( 'module_', '', $class_name ),
			'category'    => $category,
			'description' => 'Permissions',
		);
		if ( ! is_array( $actions ) ) {
			$actions = array( $actions );
		}
		foreach ( $actions as $action ) {
			$perms[ $action ] = 1;
		}

		return module_security::has_feature_access( $perms );
	}

	public static function is_plugin_enabled() {
		if ( ! _UCM_INSTALLED ) {
			return false;
		}
		$class_name = false;
		if ( function_exists( 'get_called_class' ) ) {
			$class_name = get_called_class();
		} else if ( is_callable( 'self::get_class()' ) ) {
			eval( '$class_name = self::get_class();' );
		} else if ( is_callable( 'static::get_class()' ) ) {
			// doesn't work in php5.2
			eval( '$class_name = static::get_class();' );
		}
		if ( $class_name ) {
			$class_name = str_replace( 'module_', '', $class_name );

			return module_config::c( 'plugin_enabled_' . $class_name, 1 );
		}

		return true;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		return array();
	}

	// used for working out the display value for a key.
	public function autocomplete_display( $key = 0, $search_options = array() ) {
		if ( ! empty( $search_options['plugin'] ) && self::db_table_exists( $search_options['plugin'] ) ) {
			$fields = get_fields( $search_options['plugin'] );
			if ( ! empty( $fields[ $search_options['key'] ] ) && ! empty( $fields[ $search_options['display_key'] ] ) ) {
				$row = get_single( $search_options['plugin'], $search_options['key'], $key );
				if ( ! empty( $row ) ) {
					if ( ! empty( $search_options['return_link'] ) ) {
						return array( $row[ $search_options['display_key'] ], static::link_open( $key, false ) );
					}

					return $row[ $search_options['display_key'] ];
				}
			}
		}

		return '';
	}

	function get_menu( $holding_module = false, $holding_page = false, $type = false ) {

		if ( ! self::is_plugin_enabled() ) {
			return array();
		}

		if ( ! $this->_pre_menu_done && $this->is_installed() ) {
			$this->pre_menu();
			$this->_pre_menu_done = true;
		}
		$links = $this->links;
		if ( ! is_array( $links ) ) {
			$links = array();
		}
		foreach ( $links as $link_id => $link ) {
			// moving from object 'parent' variables through to variables defined
			// in each link element
			if ( $holding_module ) {
				if ( isset( $link['holder_module'] ) && $link['holder_module'] == $holding_module ) {
					if ( isset( $link['holder_module_page'] ) && $link['holder_module_page'] && $holding_page != $link['holder_module_page'] ) {
						unset( $links[ $link_id ] );
						continue; // wrong type of link, dont include here.
					}
				} else {
					// we're trying to get a sub menu, but this link
					// isn't supported as a sum menu
					unset( $links[ $link_id ] );
					continue; // wrong type of link, dont include here.
				}
			} else {
				if ( isset( $link['holder_module'] ) && $link['holder_module'] ) {
					// this link is designed for a parent module, but we're not rendering one right now.
					// ignore:
					unset( $links[ $link_id ] );
					continue; // wrong type of link, dont include here.
				}
			}

			// check the link type to see if it can be included here.
			if ( $type && ( ! isset( $link['type'] ) || $link['type'] != $type ) ) {
				unset( $links[ $link_id ] );
				continue; // wrong type of link, dont include here.
			} else if ( $type === false && isset( $link['type'] ) ) {
				unset( $links[ $link_id ] );
				continue; // wrong type of link, dont include here.
			}
			$links[ $link_id ]['m'] = $this->module_name;
			//$links[$link_id]['plugin'] = &$this;
			// new menu configuration stored as ajax again module name and title.
			$name_id = preg_replace( '#[^a-z0-9-_]#', '', strtolower( ( isset( $link['holder_module'] ) ? $link['holder_module'] . '-' : '' ) . ( isset( $link['holder_page'] ) ? $link['holder_page'] . '-' : '' ) . $this->module_name . '-' . trim( strip_tags( preg_replace( '#<[^>]*>[^<]*<[^>]*>#', '', $link['name'] ) ) ) ) );
			$order   = ! empty( $links[ $link_id ]['order'] ) ? $links[ $link_id ]['order'] : module_config::c( '_menu_order_' . $this->module_name, false );
			if ( ! $order ) {
				$order = isset( $link['order'] ) ? $link['order'] : $this->module_position;
			}
			$icon = module_config::c( '_menu_icon_' . $this->module_name, false );
			if ( ! $icon && isset( $links[ $link_id ]['icon_name'] ) ) {
				$icon = $links[ $link_id ]['icon_name'];
			}
			$default            = array(
				'module'        => $this->module_name,
				'label'         => $link['name'],
				'order'         => $order,
				'icon'          => $icon,
				'parent'        => false,
				'holder_module' => isset( $link['holder_module'] ) ? $link['holder_module'] : false,
				'holder_page'   => isset( $link['holder_page'] ) ? $link['holder_page'] : false,
			);
			$menu_configuration = json_decode( module_config::c( '_menu_config_' . $name_id, json_encode( $default ) ), true );
			if ( ! $menu_configuration ) {
				$menu_configuration = $default;
			}
			$links[ $link_id ]['name_id']   = $name_id;
			$links[ $link_id ]['order']     = $menu_configuration['order'];
			$links[ $link_id ]['icon_name'] = $menu_configuration['icon'];
			$links[ $link_id ]['parent']    = $menu_configuration['parent'];

		}

		return $links;
	}

	function get_labels() {
		// check if the label file for current language exists.
		$labels = array();

		/*if(is_file("includes/plugin_".basename($this->module_name)."/lang/"._LANG.".php")){
			include("includes/plugin_".basename($this->module_name)."/lang/"._LANG.".php");
		}*/

		return $labels;
	}

	function link( $page = '', $args = array(), $module = false, $include_parent = - 1, $object_data = array(), $options = array() ) {


		$load_modules = ( isset( $_REQUEST['m'] ) ) ? ( is_array( $_REQUEST['m'] ) ? $_REQUEST['m'] : array( $_REQUEST['m'] ) ) : array();
		$load_pages   = ( isset( $_REQUEST['p'] ) ) ? ( is_array( $_REQUEST['p'] ) ? $_REQUEST['p'] : array( $_REQUEST['p'] ) ) : array();

		if ( ! isset( $options['parent_module'] ) && $this->parent_module ) {
			$options['parent_module'] = $this->parent_module;
		}
		if ( ! isset( $options['parent_module_table_id'] ) && $this->parent_module_table_id ) {
			$options['parent_module_table_id'] = $this->parent_module_table_id;
		}
		if ( ! isset( $options['parent_module_page'] ) && $this->parent_module_page ) {
			$options['parent_module_page'] = $this->parent_module_page;
		}

		// we check if we have to include any parent module parameters in here.
		if (
			$object_data &&
			isset( $options['parent_module'] ) &&
			$options['parent_module'] &&
			isset( $options['parent_module_table_id'] ) &&
			$options['parent_module_table_id'] &&
			isset( $object_data[ $options['parent_module_table_id'] ] )
		) {
			$args[ $options['parent_module_table_id'] ] = $object_data[ $options['parent_module_table_id'] ];
			// eg: this will add "customer_id=XX" to the site open links, for all site, because the site module is setup under the "customer" module.
		}
		// we also have to make sure the parent modules page matches the page this module specifies
		if (
			isset( $options['parent_module'] ) &&
			$options['parent_module'] &&
			isset( $options['parent_module_page'] ) &&
			$options['parent_module_page']
		) {
			if ( isset( $options['parent_module_page'] ) && $options['parent_module_page'] ) {
				$parent_module_position = false;
				foreach ( $load_modules as $key => $val ) {
					if ( $val == $options['parent_module'] ) {
						$parent_module_position = $key;
						$load_pages[ $key ]     = $options['parent_module_page'];
					}
				}
				if ( ! $parent_module_position ) {
					// we're linking from a different parent module.
					// delete the main parent module from the m list and replace it with
					// todo - handle multiple sub levels ..
					$load_modules[0] = $options['parent_module'];
					$load_pages[0]   = $options['parent_module_page'];
				}
			}
		}

		if ( $page && ! $module ) {
			$module = $this->module_name;
		}

		if ( $include_parent == - 1 ) {
			$include_parent = $this->link_include_parents;
		}
		//moved off toa  function.
		$allow_nesting = isset( $options['allow_nesting'] ) ? $options['allow_nesting'] : false;

		return generate_link( $page, $args, $module, $include_parent, $allow_nesting, $load_modules, $load_pages );

	}

	private static $_dbt_exists = array();

	public static function db_table_exists( $name, $force = false ) {
		if ( defined( '_UCM_INSTALLED' ) && ! _UCM_INSTALLED ) {
			return false;
		}
		if ( $force ) {
			if ( isset( self::$_dbt_exists[ $name ] ) && self::$_dbt_exists[ $name ] ) {
				return true;
			}
			$sql = "SHOW TABLES LIKE '" . _DB_PREFIX . $name . "'";
			$res = qa1( $sql );
			if ( $res != false && count( $res ) ) {
				self::$_dbt_exists[ $name ] = true;

				return true;
			} else {
				self::$_dbt_exists[ $name ] = false;

				return false;
			}
		}
		if ( count( self::$_dbt_exists ) ) {
			// we have queried db already.
			return isset( self::$_dbt_exists[ $name ] ) && self::$_dbt_exists[ $name ];
		}
		// query all db tables first time to speed things up.
		$sql = "SHOW TABLES";
		$all = qa( $sql );

		foreach ( $all as $a ) {
			$table_name = current( $a );
			if ( $table_name ) {
				self::$_dbt_exists[ preg_replace( '#^' . preg_quote( _DB_PREFIX, '#' ) . '#', '', $table_name ) ] = true;
			}
		}
		if ( isset( self::$_dbt_exists[ $name ] ) ) {
			return self::$_dbt_exists[ $name ];
		}

		return false;
	}

	function is_installed() {
		if ( $this->get_install_sql() === false ) {
			return true; // it's installed.
		}

		return self::db_table_exists( $this->module_name );
		// check the db table.
		/*$sql = "SHOW TABLES LIKE '"._DB_PREFIX.$this->module_name."'";
		$res = qa1($sql);
		if($res != false && count($res)){
				return true;
		}else{
				return false;
		}*/
	}

	function get_install_sql() {
		return false;
	}

	function get_upgrade_sql() {
		return false;
	}

	function get_page_title() {
		return ( $this->page_title ) ? _l( $this->page_title ) : _l( ucwords( str_replace( "_", " ", $this->module_name ) ) );
	}

	function set_insatlled_plugin_version( $version ) {
		module_config::save_config( '_plugin_version_' . $this->module_name, $version );
	}

	function get_installed_plugin_version() {
		return module_config::c( '_plugin_version_' . $this->module_name );
	}

	function get_plugin_version() {
		// save this version.
		return $this->version;
	}

	function install_upgrade() { //$do_upgrade,$current_db_version
		// get the SQL for this install, and run it!
		$this->module_name = ( str_replace( 'module_', '', get_class( $this ) ) );
		// return true on success, false (and set error) on failure.
		$current_plugin_version = $this->get_plugin_version();
		if ( $this->get_install_sql() === false ) {
			// nothing to install db wise.
			return $current_plugin_version;
		} else if ( $this->is_installed() ) {

			$installed_plugin_version = $this->get_installed_plugin_version();
			if ( ! $installed_plugin_version ) {
				$installed_plugin_version = $current_plugin_version;
			}
			// already installed, check if we need to upgrade.
			// do an upgrade


			$new_version = $installed_plugin_version;
			$upgrade_sql = $this->get_upgrade_sql( $installed_plugin_version, $current_plugin_version );
			if ( $installed_plugin_version && ( strlen( $upgrade_sql ) || $installed_plugin_version < $current_plugin_version ) ) {
				// already current version! don't need to upgrade.
				echo "<br/> &nbsp; Upgrading plugin from version $installed_plugin_version to $current_plugin_version <br/>";
				//$upgrade_sql = $this->get_upgrade_sql($installed_plugin_version,$current_plugin_version);
				if ( $upgrade_sql ) {
					foreach ( explode( ';', $upgrade_sql ) as $s ) {
						//                    foreach(preg_split('#;\s+[\n|\r]#',$upgrade_sql) as $s){
						$s = trim( $s );
						if ( ! $s ) {
							continue;
						}
						if ( ! query( $s ) ) {
							return false;
						}
					}
					// upgrade complete.
					$new_version = $current_plugin_version;
				} else {
					// no sql provided from plugin :( oh well.
					$new_version = $current_plugin_version;
				}
			}

			return $new_version;
		} else {
			// install scratch.
			$sql = $this->get_install_sql();
			if ( $sql ) {
				foreach ( explode( ';', $sql ) as $s ) {
					$s = trim( $s );
					if ( ! $s ) {
						continue;
					}
					if ( ! query( $s ) ) {
						return false;
					}
				}

				return $current_plugin_version;
			}
			set_error( 'No sql from ' . $this->module_name );

			return false;
		}
	}

	function ajax_search( $key ) {
		$return_results = array();
		if ( is_array( $this->ajax_search_keys ) ) {
			foreach ( $this->ajax_search_keys as $table => $fields ) {

				if ( isset( $fields['plugin'] ) ) {
					// we're doing the new search
					$sql = "SELECT * FROM `" . $table . "` WHERE ";
					foreach ( $fields['search_fields'] as $field ) {
						$sql .= " `" . $field . "` LIKE '%" . db_escape( $key ) . "%' OR ";
					}
					$sql = rtrim( $sql, ' OR ' );
					$sql .= "LIMIT 5";
					$res = qa( $sql );

					foreach ( $res as $r ) {
						$match = $r[ current( $fields['search_fields'] ) ];
						$match .= ' (';
						for ( $x = 1; $x < count( $fields['search_fields'] ); $x ++ ) {
							$field = $fields['search_fields'][ $x ];
							// see if this one matches.
							if ( preg_match( '/' . preg_quote( $key, '/' ) . '/i', $r[ $field ] ) ) {
								$this_match = $r[ $field ];
								$this_match = preg_replace( '/\n|\r/', '', $this_match );
								if ( strlen( $this_match ) > 20 ) {
									preg_match( '/(.{0,8})(' . preg_quote( $key, '/' ) . ')(.{0,8})/i', $this_match, $matches );
									$this_match = ( strlen( $matches[1] ) > 6 ) ? '...' . substr( $matches[1], - 6 ) : $matches[1];
									$this_match .= $matches[2];
									$this_match .= ( strlen( $matches[3] ) > 6 ) ? substr( $matches[3], 0, 6 ) . '...' : $matches[3];
								}
								$match .= $this_match . ", ";
							}
						}
						$match   = rtrim( $match, ', ' );
						$match   .= ')';
						$match   = preg_replace( '/\(\)$/', '', $match );
						$match   = preg_replace( '/' . preg_quote( $key, '/' ) . '/i', '<span style="background-color:#FFFA97">$0</span>', $match );
						$url_key = array();
						$pkey    = $fields['key'];
						if ( is_array( $fields['key'] ) ) {
							foreach ( $fields['key'] as $key ) {
								if ( ! $pkey ) {
									$pkey = $key;
								}
								$url_key[ $key ] = $r[ $key ];
							}
						}
						$return_results [] = '<a href="' . eval( 'return module_' . $fields['plugin'] . '::link_open($r[$pkey],false);' ) . '">' . $fields['title'] . $match . '</a>';
					}
				} else {
					// old search.
					$sql         = "SELECT * FROM `" . $table . "` WHERE ";
					$link_table  = array_shift( $fields );
					$primary_key = array_shift( $fields );
					foreach ( $fields as $field ) {
						$sql .= " `" . $field . "` LIKE '%" . db_escape( $key ) . "%' OR ";
					}
					$sql = rtrim( $sql, ' OR ' );
					$sql .= "LIMIT 5";
					$res = qa( $sql );

					foreach ( $res as $r ) {
						reset( $fields );
						$match = $r[ current( $fields ) ];
						$match .= ' (';
						for ( $x = 1; $x < count( $fields ); $x ++ ) {
							$field = $fields[ $x ];
							// see if this one matches.
							if ( preg_match( '/' . preg_quote( $key, '/' ) . '/i', $r[ $field ] ) ) {
								$this_match = $r[ $field ];
								$this_match = preg_replace( '/\n|\r/', '', $this_match );
								if ( strlen( $this_match ) > 20 ) {
									preg_match( '/(.{0,8})(' . preg_quote( $key, '/' ) . ')(.{0,8})/i', $this_match, $matches );
									$this_match = ( strlen( $matches[1] ) > 6 ) ? '...' . substr( $matches[1], - 6 ) : $matches[1];
									$this_match .= $matches[2];
									$this_match .= ( strlen( $matches[3] ) > 6 ) ? substr( $matches[3], 0, 6 ) . '...' : $matches[3];
								}
								$match .= $this_match . ", ";
							}
						}
						$match   = rtrim( $match, ', ' );
						$match   .= ')';
						$match   = preg_replace( '/\(\)$/', '', $match );
						$match   = preg_replace( '/' . preg_quote( $key, '/' ) . '/i', '<span style="background-color:#FFFA97">$0</span>', $match );
						$url_key = array();
						if ( is_array( $primary_key ) ) {
							foreach ( $primary_key as $key ) {
								$url_key[ $key ] = $r[ $key ];
							}
						} else {
							$url_key[ $primary_key ] = $r[ $primary_key ];
						}
						$return_results [] = '<a href="' . $this->link( $link_table, $url_key ) . '">' . $match . '</a>';
					}
				}
			}
		}

		return $return_results;
	}

	public static function add_table_index( $table_name, $column_name, $index_name = false ) {
		$sql_check = 'SHOW INDEX FROM `' . _DB_PREFIX . '' . $table_name . '`';
		$res       = qa( $sql_check, false );
		$add_index = true;
		foreach ( $res as $r ) {
			if ( isset( $r['Column_name'] ) && $r['Column_name'] == $column_name ) {
				$add_index = false;
			}
		}
		// check if this field exists.
		$fields = get_fields( $table_name );
		if ( ! isset( $fields[ $column_name ] ) ) {
			$add_index = false;
		}
		if ( $add_index ) {
			$sql = 'ALTER TABLE  `' . _DB_PREFIX . $table_name . '` ADD INDEX ( ' . $column_name . ' );';
			query( $sql );
		}

	}
}

