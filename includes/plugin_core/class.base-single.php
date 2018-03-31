<?php


class UCMBaseSingle implements ArrayAccess {


	public $id = 0;
	public $db_id = 'id'; // this can be an array of keys.
	public $db_table = 'table';
	public $module_name = '';
	public $display_key = 'name';
	public $display_name = 'Widget';
	public $display_name_plural = 'Widgets';
	public $db_fields = array();
	public $db_fields_all = array();
	public $db_details = array();


	public function __construct( $id = false ) {

		if ( ! $this->module_name ) {
			$this->module_name = $this->db_table;
		}

		if ( empty( $this->db_fields_all ) ) {
			$this->db_fields_all = get_fields( $this->db_table );
		}
		if ( empty( $this->db_fields ) ) {
			$this->db_fields = $this->db_fields_all;
		}
		$this->default_values();

		if ( $id ) {
			$this->load( $id );
		}
	}

	private static $instances = array();

	static function singleton( $id ) {
		if ( $id ) {
			if ( ! isset( self::$instances[ $id ] ) ) {
				self::$instances[ $id ] = new static( $id );
			}

			return self::$instances[ $id ];
		}

		return false;
	}

	public function default_values() {

		foreach ( array( 'customer_id', 'website_id', 'job_id', 'quote_id' ) as $key ) {
			if ( ! empty( $_REQUEST[ $key ] ) ) {
				$this->db_details[ $key ] = (int) $_REQUEST[ $key ];
			}
		}
	}

	/**
	 * @var UCMDatabase
	 */
	public $db;

	/**
	 * @return UCMDatabase
	 */
	public function get_db() {

		if ( ! $this->db ) {
			$this->db = class_exists( 'UCMDatabase' ) ? UCMDatabase::singleton() : false;
		}
		$this->db->reset();

		return $this->db;
	}

	public function sql_load( $id = false ) {
		return '';
	}

	public function load( $id = false ) {
		$this->id         = 0;
		$this->db_details = array();
		$conn             = $this->get_db();
		if ( $conn ) {
			if ( is_array( $this->db_id ) ) {
				// we're loading a composite key.
				if ( ! is_array( $this->db_id ) || ! is_array( $id ) ) {
					die( 'Failed to load composite key' );
				}
				$sql         = 'SELECT * FROM `' . _DB_PREFIX . $this->db_table . '` WHERE ';
				$has_all_ids = true;
				foreach ( $this->db_id as $db_idid => $db_id ) {
					if ( $db_idid > 0 ) {
						$sql .= ' AND ';
					}
					$sql .= '`' . $db_id . '` = :id' . $db_idid;
					// Bind the Id
					$bind_id = isset( $id[ $db_id ] ) ? $id[ $db_id ] : ( isset( $id[ $db_idid ] ) ? $id[ $db_idid ] : 0 );
					if ( ! $bind_id ) {
						$has_all_ids = false;
					}
					$this->db->bind_param( 'id' . $db_idid, $bind_id );
				}
				if ( $has_all_ids ) {
					$this->db->prepare( $sql );
					if ( $this->db->execute() ) {
						// Save returned row
						$temp_details = $this->db->single();
						if ( $this->check_permissions( $id, $temp_details ) ) {
							$this->db_details = $temp_details;
							$this->id         = array();
							foreach ( $this->db_id as $db_idid => $db_id ) {
								$this->id[ $db_id ] = $this->db_details[ $db_id ];
							}
						}
					}
				}

			} else if ( (int) $id ) {
				$id = (int) $id;

				$sql = $this->sql_load( $id );
				if ( ! $sql ) {
					$sql = 'SELECT * FROM `' . _DB_PREFIX . $this->db_table . '` WHERE ';
					$sql .= '`' . $this->db_id . '` = :id';
					$this->db->bind_param( 'id', $id );
				}

				$this->db->prepare( $sql );
				if ( $this->db->execute() ) {
					// Save returned row
					$temp_details = $this->db->single();
					if ( $this->check_permissions( $id, $temp_details ) ) {
						$this->db_details = $temp_details;
						$this->id         = $this->db_details[ $this->db_id ];
					}
				}
			}
		}

		return $this->id;
	}

	public function check_permissions( $id = false, $db_details = false ) {

		return true;
	}

	public function get( $field = false ) {
		if ( ! $field ) {
			return $this->db_details;
		}

		return isset( $this->db_details[ $field ] ) ? $this->db_details[ $field ] : null;
	}

	public function link_open( $full = false, $link_options = array() ) {

		$arguments = array();
		if ( is_array( $this->db_id ) ) {
			$id = $this->id;
			foreach ( $this->db_id as $db_idid => $db_id ) {
				$arguments[ $db_id ] = ! empty( $id[ $db_id ] ) ? $id[ $db_id ] : ( ! empty( $id[ $db_idid ] ) ? $id[ $db_idid ] : ( ! empty( $this->db_details[ $db_id ] ) ? $this->db_details[ $db_id ] : 0 ) );
			}
		} else {
			$arguments[ $this->db_id ] = $this->id;
		}

		if ( $this->id ) {
			$link_options[] = array(
				'full'      => $full,
				'type'      => $this->db_table,
				'module'    => $this->module_name,
				'page'      => $this->db_table . '_admin',
				'arguments' => $arguments,
				'data'      => $this->db_details,
				'text'      => $this->db_details[ $this->display_key ]
			);
		} else {
			$link_options[] = array(
				'full'      => $full,
				'type'      => $this->db_table,
				'module'    => $this->module_name,
				'page'      => $this->db_table . '_admin',
				'arguments' => $arguments,
				'data'      => array(),
				'text'      => $this->display_name
			);
		}

		return link_generate( $link_options );
	}

	public function delete_with_confirm( $confirm_message = false, $redirect_link = false, $success_callback = false ) {
		if ( $this->id ) {
			if ( ! $confirm_message ) {
				$confirm_message = _l( 'Really Delete ' . $this->display_name, $this->db_details[ $this->display_key ] );
			}
			if ( ! $redirect_link ) {
				$redirect_link = $this->link_open();
			}
			if ( module_form::confirm_delete( $this->db_id, $confirm_message, $redirect_link ) ) {
				$this->delete();
				if ( $success_callback instanceof Closure ) {
					$success_callback( $this );
				}
				set_message( $this->display_name . ' Deleted Successfully' );
				redirect_browser( $redirect_link );
			}
		}
	}

	public function save_data( $post_data ) {
		$conn = $this->get_db();
		if ( $conn ) {

			$doing_update = false;

			// support composite keys:
			if ( is_array( $this->db_id ) ) {
				$update_keys = array();
				$id          = $this->id;
				foreach ( $this->db_id as $db_idid => $db_id ) {
					$update_key = ! empty( $id[ $db_id ] ) ? $id[ $db_id ] : ( ! empty( $id[ $db_idid ] ) ? $id[ $db_idid ] : ( ! empty( $this->db_details[ $db_id ] ) ? $this->db_details[ $db_id ] : 0 ) );
					if ( $update_key ) {
						$update_keys[ $db_id ] = $update_key;
					}
				}


				if ( count( $update_keys ) === count( $this->db_id ) ) {
					$doing_update = true;
					// we're updating based on matching keys.
					$sql = 'UPDATE `' . _DB_PREFIX . $this->db_table . '` SET ';
				} else {
					// we're creating new data. ignore $this->id keys.
					$sql = 'INSERT INTO `' . _DB_PREFIX . $this->db_table . '` SET ';

				}

			} else {
				if ( $this->id ) {
					$doing_update = true;
					$sql          = 'UPDATE `' . _DB_PREFIX . $this->db_table . '` SET ';
				} else {
					$sql = 'INSERT INTO `' . _DB_PREFIX . $this->db_table . '` SET ';
				}
			}
			foreach ( $this->db_fields as $db_field => $db_field_settings ) {
				if ( isset( $post_data[ 'default_' . $db_field ] ) && ! isset( $post_data[ $db_field ] ) ) {
					// a hack for our empty checkboxes
					$post_data[ $db_field ] = 0;
				}
				if ( isset( $post_data[ $db_field ] ) ) {
					if ( is_array( $post_data[ $db_field ] ) ) {
						$post_data[ $db_field ] = serialize( $post_data[ $db_field ] );
					}
					// hmm: not sure if this will break other things.
					// uncommented so the product category can be deselected on product settings page.
					//					if ( strlen( $post_data[ $db_field ] ) ) {
					$sql .= '`' . $db_field . '` = :' . $db_field . ' , ';
					$this->db->bind_param( $db_field, $post_data[ $db_field ], ! empty( $db_field_settings['type'] ) ? $db_field_settings['type'] : false );
					//					}
				}
			}
			if ( $doing_update ) {
				if ( isset( $this->db_fields_all['date_updated'] ) ) {
					$sql .= " `date_updated` = :date_updated, ";
					$this->db->bind_param( 'date_updated', date( 'Y-m-d H:i:s' ) );
				}
				if ( isset( $this->db_fields_all['update_user_id'] ) ) {
					$sql .= " `update_user_id` = :update_user_id, ";
					$this->db->bind_param( 'update_user_id', module_security::get_loggedin_id() );
				}
				$sql = rtrim( $sql, ', ' );
				// support composite keys:
				if ( is_array( $this->db_id ) ) {
					if ( ! count( $update_keys ) ) {
						die( 'No composite key update found' );
					}
					$first = true;
					$sql   .= ' WHERE ';
					foreach ( $update_keys as $db_id => $db_val ) {
						if ( $first ) {
							$first = false;
						} else {
							$sql .= ' AND ';
						}
						$sql .= '`' . $db_id . '` = :id' . $db_id;
						$this->db->bind_param( 'id' . $db_id, $db_val );
					}
					$sql .= ' LIMIT 1';
				} else {
					$sql .= ' WHERE `' . $this->db_id . '` = :id LIMIT 1';
					$this->db->bind_param( 'id', $this->id );
				}
			} else {
				// support composite keys:
				if ( is_array( $this->db_id ) ) {
					// add the keys to the initial import.

				}
				if ( isset( $this->db_fields_all['date_created'] ) ) {
					$sql .= " `date_created` = :date_created, ";
					$this->db->bind_param( 'date_created', date( 'Y-m-d H:i:s' ) );
				}
				if ( isset( $this->db_fields_all['create_user_id'] ) ) {
					$sql .= " `create_user_id` = :create_user_id, ";
					$this->db->bind_param( 'create_user_id', module_security::get_loggedin_id() );
				}
				$sql = rtrim( $sql, ', ' );
			}
			//			print_r($_REQUEST); print_r($this->db->params); echo $sql; exit;
			$this->db->prepare( $sql );

			if ( $this->db->execute() ) {
				if ( ! $this->id ) {

					// support composite keys:
					if ( is_array( $this->db_id ) ) {
						$this->id = array();
						$array_id = array();
						foreach ( $this->db_id as $db_idid => $db_id ) {
							$update_key = ! empty( $post_data[ $db_id ] ) ? $post_data[ $db_id ] : 0;
							if ( $update_key ) {
								$array_id[ $db_id ] = $update_key;
							}
						}
						$this->db->close();
						$this->load( $array_id );
					} else {
						$this->id = $this->db->insert_id();
						$this->db->close();
						$this->load( $this->id );
					}
				} else {
					$this->db->close();
				}

				if ( $this->id ) {
					// we have to save gruops and extra fields and anything else.
					if ( class_exists( 'module_extra' ) ) {
						module_extra::save_extras( $this->db_table, $this->db_id, $this->id );
					}
					if ( class_exists( 'module_group' ) ) {
						module_group::save_groups( $this->db_table, $this->db_id, $this->id );
					}
				}

				return $this->id;
			}
		}

		return false;
	}

	public function update( $field, $value ) {
		$conn = $this->get_db();
		if ( $conn && $this->id && isset( $this->db_fields[ $field ] ) ) {

			$this->db->prepare( 'UPDATE `' . _DB_PREFIX . $this->db_table . '` SET `' . $field . '` = :value WHERE `' . $this->db_id . '` = :id LIMIT 1' );

			if ( is_array( $value ) ) {
				$this->db->bind_param( 'value', serialize( $value ) );
			} else {
				$this->db->bind_param( 'value', $value );
			}

			$this->db->bind_param( 'id', $this->id );
			if ( $this->db->execute() ) {
				$this->db_details[ $field ] = $value;
			}
			$this->db->close();
		}
	}

	public function delete() {
		$conn = $this->get_db();
		if ( $conn && $this->id ) {

			hook_handle_callback( 'deleting_' . $this->db_table, $this->id );

			$sql = 'DELETE FROM `' . _DB_PREFIX . $this->db_table . '` ';

			if ( is_array( $this->db_id ) ) {

				$delete_keys = array();
				$id          = $this->id;
				foreach ( $this->db_id as $db_idid => $db_id ) {
					$key_val = ! empty( $id[ $db_id ] ) ? $id[ $db_id ] : ( ! empty( $id[ $db_idid ] ) ? $id[ $db_idid ] : ( ! empty( $this->db_details[ $db_id ] ) ? $this->db_details[ $db_id ] : 0 ) );
					if ( $key_val ) {
						$delete_keys[ $db_id ] = $key_val;
					}
				}

				$first = true;
				$sql   .= ' WHERE ';
				foreach ( $delete_keys as $db_id => $db_val ) {
					if ( $first ) {
						$first = false;
					} else {
						$sql .= ' AND ';
					}
					$sql .= '`' . $db_id . '` = :id' . $db_id;
					$this->db->bind_param( 'id' . $db_id, $db_val );
				}
				$sql .= ' LIMIT 1';
			} else {
				$sql .= ' WHERE `' . $this->db_id . '` = :id LIMIT 1';
				$this->db->bind_param( 'id', $this->id );
			}

			$this->db->prepare( $sql );

			if ( $this->db->execute() ) {

				$this->delete_children();

				return true;
			}
		}

		return false;
	}

	public function delete_children() {

	}

	public function create_new( $data ) {
		$conn = $this->get_db();
		if ( $conn ) {

			$this->db_details;
			$fields = array();
			foreach ( $data as $field => $value ) {
				if ( isset( $this->db_fields[ $field ] ) ) {
					$this->db_details[ $field ] = $value;
				}
			}
			foreach ( $this->db_details as $field => $value ) {
				if ( isset( $this->db_fields[ $field ] ) ) {
					$fields[] = ' `' . $field . '` = :' . $field;
					$this->db->bind_param( $field, $value );
				}
			}

			$this->db->prepare( 'INSERT INTO `' . _DB_PREFIX . $this->db_table . '` SET ' . implode( ', ', $fields ) . '' );

			if ( $this->db->execute() ) {
				// support composite keys:
				if ( is_array( $this->db_id ) ) {
					$this->id = array();
					$array_id = array();
					foreach ( $this->db_id as $db_idid => $db_id ) {
						$update_key = ! empty( $post_data[ $db_id ] ) ? $post_data[ $db_id ] : 0;
						if ( $update_key ) {
							$array_id[ $db_id ] = $update_key;
						}
					}
					$this->db->close();
					$this->load( $array_id );

					return $array_id;
				} else {
					$this->id = $this->db->insert_id();
					$this->db->close();
					$this->load( $this->id );

					return $this->id;
				}
			}

		}

		return false;
	}


	public function &__get( $key ) {
		return $this->db_details[ $key ];
	}

	public function __set( $key, $value ) {
		$this->db_details[ $key ] = $value;
	}

	public function __isset( $key ) {
		return isset( $this->db_details[ $key ] );
	}

	public function __unset( $key ) {
		unset( $this->db_details[ $key ] );
	}

	public function offsetSet( $offset, $value ) {
		$this->db_details[ $offset ] = $value;
	}

	public function offsetExists( $offset ) {
		return isset( $this->db_details[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		unset( $this->db_details[ $offset ] );
	}

	public function offsetGet( $offset ) {
		return isset( $this->db_details[ $offset ] ) ? $this->db_details[ $offset ] : null;
	}


}