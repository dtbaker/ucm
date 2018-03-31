<?php

// used in main listing and search listing.

if ( ! isset( $datas ) ) {
	die( 'No data found' );
}

$data_field_groups = $module->get_data_field_groups( $data_type_id );

if ( empty( $embed_form ) && $allow_search ) {
	?>


	<form action="" method="post" id="custom_data_form">

	<?php

	// collect a list of create/update user ids for search
	//$search = array();
	//$search['data_type_id'] = $data_type_id;
	//$datas = $module->get_datas($search);
	$create_user_ids = array();
	$update_user_ids = array();
	foreach ( $datas as $data ) {
		if ( $data['create_user_id'] ) {
			$create_user_ids[ $data['create_user_id'] ] = true;
		}
		if ( $data['update_user_id'] ) {
			$update_user_ids[ $data['update_user_id'] ] = true;
		}
	}
	foreach ( $create_user_ids as $user_id => $tf ) {
		$user = module_user::get_user( $user_id );
		if ( $user ) {
			$create_user_ids[ $user_id ] = $user['name'] . ' ' . $user['last_name'];
		} else {
			$create_user_ids[ $user_id ] = 'Unknown';
		}
	}
	foreach ( $update_user_ids as $user_id => $tf ) {
		$user = module_user::get_user( $user_id );
		if ( $user ) {
			$update_user_ids[ $user_id ] = $user['name'] . ' ' . $user['last_name'];
		} else {
			$update_user_ids[ $user_id ] = 'Unknown';
		}
	}
	$update_user_ids[2] = 'Unknown';

	foreach ( $data_field_groups as $data_field_group ) {
		$data_field_group_id = $data_field_group['data_field_group_id'];
		$data_field_group    = $module->get_data_field_group( $data_field_group_id ); // needed?
		$data_fields         = $module->get_data_fields( $data_field_group_id );
		foreach ( $data_fields as $data_field_id => $data_field ) {
			if ( ! $data_field['searchable'] ) {
				unset( $data_fields[ $data_field_id ] );
			}
		}
		if ( $data_fields ) {

			$search_bar = array(
				'elements' => array()
			);
			foreach ( $data_fields as $data_field_id => $data_field ) {
				$data_field['multiple'] = false;
				switch ( $data_field['field_type'] ) {
					case 'auto_id':
						$data_field['data_field_id'] = 'data_record_id';
						$data_field['field_type']    = 'text';
						break;
					case 'created_date_time':
						$data_field['data_field_id'] = 'created_date';
						$data_field['field_type']    = 'date';
						break;
					case 'created_date':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'date';
						break;
					case 'created_time':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'time';
						break;
					case 'updated_date_time':
						$data_field['data_field_id'] = 'updated_date';
						$data_field['field_type']    = 'date';
						break;
					case 'updated_date':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'date';
						break;
					case 'updated_time':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'time';
						break;
					case 'created_by':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'select';
						$data_field['attributes']    = $create_user_ids;
						break;
					case 'updated_by':
						$data_field['data_field_id'] = $data_field['field_type'];
						$data_field['field_type']    = 'select';
						$data_field['attributes']    = $update_user_ids;
						break;
				}
				$data_field['type']    = $data_field['field_type'];
				$data_field['options'] = isset( $data_field['attributes'] ) ? $data_field['attributes'] : array();
				$data_field['name']    = 'search_field[' . $data_field['data_field_id'] . ']';
				$data_field['value']   = isset( $_REQUEST['search_field'][ $data_field['data_field_id'] ] ) ? $_REQUEST['search_field'][ $data_field['data_field_id'] ] : false;
				if ( $data_field['type'] == 'select' && ( ! is_array( $data_field['options'] || ! count( $data_field['options'] ) ) ) ) {
					// copied from data.php
					$data_field['options'] = array();
					foreach ( explode( "\n", trim( $data_field['field_data'] ) ) as $line ) {
						$line = trim( $line );
						if ( preg_match( '/^attributes=/', $line ) ) {
							$line = preg_replace( '/^attributes=/', '', $line );
							if ( preg_match( '#hook:([\w_]+)$#', $line, $matches ) ) {
								// see if we get anything back from this hook.
								$attributes = array();
								$attributes = hook_filter_var( $matches[1], $attributes, $element );
								if ( is_array( $attributes ) ) {
									$data_field['options'] = $attributes;
								} else {
									$data_field['options'] = array( 'Unable to call hook: ' . $matches[1] );
								}
							} else {
								$data_field['options'] = explode( "|", $line );
								if ( isset( $data_field['options'][0] ) ) {
									$new_attributes = array();
									foreach ( $data_field['options'] as $aid => $a ) {
										$new_attributes[ $aid + 1 ] = $a;
									}
									$data_field['options'] = $new_attributes;
								}
							}
							break;
						}
					}
				} else if ( $data_field['type'] == 'wysiwyg' ) {
					$data_field['type'] = 'text';
				} else {

				}
				if ( $data_field['type'] == 'date' ) {
					$data_field1              = $data_field2 = $data_field;
					$data_field1['name']      = $data_field1['name'] . "[from]";
					$data_field2['name']      = $data_field2['name'] . "[to]";
					$data_field1['id']        = $data_field1['name'] . 'from';
					$data_field2['id']        = $data_field2['name'] . 'to';
					$data_field1['value']     = ! empty( $data_field['value']['from'] ) ? $data_field['value']['from'] : '';
					$data_field2['value']     = ! empty( $data_field['value']['to'] ) ? $data_field['value']['to'] : '';
					$search_bar['elements'][] = array(
						'title'  => $data_field['title'],
						'fields' => array(
							$data_field1,
							_l( 'to' ),
							$data_field2
						)
					);
				} else if ( $data_field['type'] != 'file' ) {
					$search_bar['elements'][] = array(
						'title' => $data_field['title'],
						'field' => $data_field
					);
				}
			}
			echo module_form::search_bar( $search_bar );
		}
	}
}

if ( isset( $_REQUEST['search_field'] ) ) {
	// do the search in php.
	// because all the data is serialized/etc.. complicated.
	foreach ( $datas as $data_id => $data ) {
		// check status
		if ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] ) {
			if ( $data['status'] != $_REQUEST['status'] ) {
				unset( $datas[ $data_id ] );
				continue;
			}
		}
		// check create user id
		if ( isset( $_REQUEST['create_user_id'] ) && $_REQUEST['create_user_id'] ) {
			if ( $data['create_user_id'] != $_REQUEST['create_user_id'] ) {
				unset( $datas[ $data_id ] );
				continue;
			}
		}

		$search_fields = array(); // choose which fields to search? similar to old search form.

		// check searchable fields.
		if ( isset( $_REQUEST['search_field'] ) && is_array( $_REQUEST['search_field'] ) ) {
			$data_items = $module->get_data_items( $data['data_record_id'] );
			foreach ( $_REQUEST['search_field'] as $data_field_id => $data_field_value ) {
				if ( true || isset( $search_fields[ $data_field_id ] ) ) { // todo- choose which fields to search.
					$settings = isset( $data_items[ $data_field_id ] ) ? @unserialize( $data_items[ $data_field_id ]['data_field_settings'] ) : false;
					if ( is_array( $data_field_value ) ) {
						if ( ! empty( $settings['field_type'] ) && $settings['field_type'] == 'date' ) {
							// search based on date from/to

							$from_date = ! empty( $data_field_value['from'] ) ? strtotime( input_date( $data_field_value['from'] ) ) : false;
							$to_date   = ! empty( $data_field_value['to'] ) ? strtotime( input_date( $data_field_value['to'] ) ) : false;
							if ( $from_date || $to_date ) {
								$match = false;
								if ( ! empty( $data_items[ $data_field_id ]['data_text'] ) ) {
									$search_date = strtotime( input_date( $data_items[ $data_field_id ]['data_text'] ) );
									if ( $search_date ) {
										if ( $from_date && $to_date ) {
											if ( $search_date >= $from_date && $search_date <= $to_date ) {
												$match = true;
											}
										} else if ( $from_date ) {
											if ( $search_date >= $from_date ) {
												$match = true;
											}
										} else if ( $to_date ) {
											if ( $search_date <= $to_date ) {
												$match = true;
											}
										}
									}
								}
								if ( ! $match ) {
									unset( $datas[ $data_id ] );
								}
								continue;

							}
						}
						$array_search = false;
						$array_match  = false;
						foreach ( $data_field_value as $data_field_value_id => $data_field_value_value ) {
							$data_field_value_value = trim( $data_field_value_value );
							// check if there's an "other" value
							if ( strtolower( $data_field_value_value ) == 'other' && isset( $_REQUEST['other_data_field'][ $data_field_id ] ) ) {
								$data_field_value_value = trim( $_REQUEST['other_data_field'][ $data_field_id ] );
							}

							if ( $data_field_value_value ) {
								$array_search = true;
								// search this field!
								$foo = @unserialize( $data_items[ $data_field_id ]['data_text'] );
								if ( $foo ) {
									$data_items[ $data_field_id ]['data_text'] = $foo;
								}
								if ( isset( $data_items[ $data_field_id ] ) && is_array( $data_items[ $data_field_id ]['data_text'] ) && isset( $data_items[ $data_field_id ]['data_text'][ $data_field_value_id ] ) ) {
									$array_match = true;
								}
							}
						}
						if ( $array_search && ! $array_match ) {
							unset( $datas[ $data_id ] );
							continue;
						}
					} else {
						$data_field_value = trim( $data_field_value );
						// check if there's an "other" value
						if ( strtolower( $data_field_value ) == 'other' && isset( $_REQUEST['other_data_field'][ $data_field_id ] ) ) {
							$data_field_value = trim( $_REQUEST['other_data_field'][ $data_field_id ] );
						}
						if ( $data_field_value ) {
							// search this field!
							switch ( $data_field_id ) {
								case 'data_record_id':
									if ( $data_field_value != $data['data_record_id'] ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'created_date_time':
									if ( input_date( $data_field_value, true ) != input_date( $data['date_created'], true ) ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'created_date':
									if ( input_date( $data_field_value ) != input_date( $data['date_created'] ) ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'created_time':
									echo 'Searching by time not supported yet.';
									break;
								case 'updated_date_time':
									if ( input_date( $data_field_value, true ) != input_date( $data['date_updated'], true ) ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'updated_date':
									if ( input_date( $data_field_value ) != input_date( $data['date_updated'] ) ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'updated_time':
									echo 'Searching by time not supported yet.';
									break;
								case 'created_by':
									if ( $data_field_value != $data['create_user_id'] ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								case 'updated_by':
									if ( $data_field_value != $data['update_user_id'] ) {
										unset( $datas[ $data_id ] );
										continue;
									}
									break;
								default:
									// search default (text and stuff)
									if ( isset( $data_items[ $data_field_id ] ) ) {
										$foo = @unserialize( $data_items[ $data_field_id ]['data_text'] );
										if ( is_array( $foo ) ) {

											if ( ! in_array( $data_field_value, $foo ) ) {
												unset( $datas[ $data_id ] );
												continue;
											}
										} else {

											if ( $settings && $settings['field_type'] && $settings['field_type'] == 'text' ) {
												if ( strpos( strtolower( trim( $data_items[ $data_field_id ]['data_text'] ) ), strtolower( $data_field_value ) ) === false ) {
													unset( $datas[ $data_id ] );
													continue;
												}
											} else if ( strtolower( trim( $data_items[ $data_field_id ]['data_text'] ) ) != strtolower( $data_field_value ) ) {
												unset( $datas[ $data_id ] );
												continue;
											}
										}
									} else {
										unset( $datas[ $data_id ] );
										continue;
									}
							}
						}
					}
				}
			}
		}
	}
}


$table_manager = module_theme::new_table_manager();
$table_manager->set_id( 'custom_data' );
$columns         = array();
$sortable_fields = array();
if ( isset( $_REQUEST['view_all'] ) ) {
	$columns['parent']         = array(
		'title'      => 'Parent',
		'callback'   => function ( $data ) use ( $module ) {
			foreach ( $module->get_data_link_keys() as $key ) {
				if ( isset( $data['row_data'][ $key ] ) && (int) $data['row_data'][ $key ] > 0 ) {
					switch ( $key ) {
						case 'customer_id':
							echo module_customer::link_open( $data['row_data'][ $key ], true );
							break;
						case 'job_id':
							echo module_job::link_open( $data['row_data'][ $key ], true );
							break;
						case 'invoice_id':
							echo module_invoice::link_open( $data['row_data'][ $key ], true );
							break;
						case 'quote_id':
							echo module_quote::link_open( $data['row_data'][ $key ], true );
							break;
						case 'file_id':
							echo module_file::link_open( $data['row_data'][ $key ], true );
							break;
					}
				}
			}
		},
		'cell_class' => 'row_action',
	);
	$sortable_fields['parent'] = array(
		'field' => 'parent',
	);
}

$list_fields = array();
foreach ( $data_field_groups as $data_field_group ) {
	$data_fields = $module->get_data_fields( $data_field_group['data_field_group_id'] );
	foreach ( $data_fields as $data_field ) {
		if ( $data_field['show_list'] ) {
			$list_fields[ $data_field['data_field_id'] ] = $data_field;
		}
	}
}
$export_fields = array();
$first         = true;
foreach ( $list_fields as $data_field_id => $list_field ) {
	$export_fields[ $list_field['title'] ] = $data_field_id;
	$columns[ 'data' . $data_field_id ]    = array(
		'title'      => $list_field['title'],
		'callback'   => function ( $data ) use ( $module, $first, $data_field_id ) {

			$value    = isset( $data[ $data_field_id ] ) ? $data[ $data_field_id ] : 'N/A';
			$row_data = $data['row_data'];
			//$value = isset($settings['field_type']) && $settings['field_type'] == 'encrypted' ? '*******' : (isset($list_data_items[$list_field['data_field_id']]) ? ($list_data_items[$list_field['data_field_id']]['data_text']) : _l('N/A'));
			// todo: if(isset($list_data_items[$list_field['data_field_id']])) unserialize and check for array.
			if ( $first ) {
				?>
				<a href="<?php echo $module->link( '', array(
					"data_record_id" => $row_data['data_record_id'],
					"data_type_id"   => $row_data['data_type_id'],
					'customer_id'    => $row_data['customer_id']
				) ); ?>" class="<?php echo isset( $embed_form ) && $embed_form ? 'custom_data_open' : ''; ?>"
				   data-settings="<?php echo htmlentities( json_encode( array(
					   "data_record_id" => $row_data['data_record_id'],
					   "data_type_id"   => $row_data['data_type_id'],
					   'customer_id'    => $row_data['customer_id']
				   ) ) ); ?>"><?php echo $value; ?></a>
				<?php
			} else {
				echo $value;
			}
		},
		'cell_class' => 'row_action',
	);

	$sortable_fields[ 'data' . $data_field_id ] = array(
		'field' => $data_field_id,
	);
	$first                                      = false;
}
$table_data_output = array();
foreach ( $datas as $table_row_id => $data ) {
	$table_data_output[ $table_row_id ] = array();
	foreach ( $list_fields as $data_field_id => $list_field ) {
		$list_data_items = $module->get_data_items( $data['data_record_id'] );
		$settings        = @unserialize( $list_data_items[ $list_field['data_field_id'] ]['data_field_settings'] );
		if ( ! isset( $settings['field_type'] ) ) {
			$settings['field_type'] = isset( $list_field['field_type'] ) ? $list_field['field_type'] : false;
		}
		$value = false;
		if ( isset( $list_data_items[ $list_field['data_field_id'] ] ) ) {
			$value = $list_data_items[ $list_field['data_field_id'] ]['data_text'];
		} else {
			switch ( $settings['field_type'] ) {
				default:
					$value = _l( 'N/A' );
					break;
			}
		}
		$foo = @unserialize( $value );
		if ( is_array( $foo ) ) {
			$value = $foo;
		}
		if ( ! isset( $settings['data_field_id'] ) ) {
			switch ( $settings['field_type'] ) {
				case 'auto_id':
					$value = $module->format_record_id( $data['data_type_id'], $data['data_record_id'] );
					break;
				case 'created_date_time':
					$value = isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_created'] ), true ) : _l( 'N/A' );
					break;
				case 'created_date':
					$value = isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_created'] ), false ) : _l( 'N/A' );
					break;
				case 'created_time':
					$value = isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data['date_created'] ) ) : _l( 'N/A' );
					break;
				case 'updated_date_time':
					$value = isset( $data['date_updated'] ) && $data['date_updated'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_updated'] ), true ) : ( isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_created'] ), true ) : _l( 'N/A' ) );
					break;
				case 'updated_date':
					$value = isset( $data['date_updated'] ) && $data['date_updated'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_updated'] ), false ) : ( isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? print_date( strtotime( $data['date_created'] ), false ) : _l( 'N/A' ) );
					break;
				case 'updated_time':
					$value = isset( $data['date_updated'] ) && $data['date_updated'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data['date_updated'] ) ) : ( isset( $data['date_created'] ) && $data['date_created'] != '0000-00-00 00:00:00' ? date( module_config::c( 'time_format', 'g:ia' ), strtotime( $data['date_created'] ) ) : _l( 'N/A' ) );
					break;
				case 'created_by':
					$value = isset( $data['create_user_id'] ) && (int) $data['create_user_id'] > 0 ? module_user::link_open( $data['create_user_id'], true ) : _l( 'N/A' );
					break;
				case 'updated_by':
					$value = isset( $data['update_user_id'] ) && (int) $data['update_user_id'] > 0 ? module_user::link_open( $data['update_user_id'], true ) : ( isset( $data['create_user_id'] ) && (int) $data['create_user_id'] > 0 ? module_user::link_open( $data['create_user_id'], true ) : _l( 'N/A' ) );
					break;
			}
		} else {
			switch ( $settings['field_type'] ) {
				case 'encrypted':
					$value = '*******';
					break;
				case 'wysiwyg':
					$value = module_security::purify_html( $value );
					break;
				case 'select':
					// todo - do this for the other field types as well..
					$settings['value'] = $value;
					$value             = $module->get_form_element( $settings, true, $data );
					break;
				case 'url':
					if ( ! is_array( $value ) ) {
						$value = array( $value );
					}
					$foo = array();
					foreach ( $value as $v ) {
						$foo [] = '<a href="' . htmlspecialchars( $v ) . '" target="_blank">' . htmlspecialchars( $v ) . '</a>';
					}
					$value = implode( ', ', $foo );
					break;
				case 'file':
					if ( is_array( $value ) && count( $value ) ) {
						$value = $value['name'];
					}
					$value = htmlspecialchars( $value );
					break;
				default:
					if ( is_array( $value ) && count( $value ) ) {
						$foo = array();
						foreach ( $value as $key => $val ) {
							if ( $val ) {
								$foo[] = $val == 1 ? $key : $val;
							}
						}
						$value = implode( ', ', $foo );
					}
					$value = htmlspecialchars( $value );
					break;
			}
		}
		$table_data_output[ $table_row_id ][ $data_field_id ] = $value;
		$table_data_output[ $table_row_id ]['row_data']       = $data;
	}
}

$table_manager->enable_table_sorting(
	array(
		'table_id' => 'custom_data' . (int) $data_type_id,
		'sortable' => $sortable_fields,
	)
);

if ( module_data::can_i( 'view', $data_type['data_type_name'] ) ) {
	$table_manager->enable_export( array(
		'name'   => $data_type['data_type_name'] . ' Export',
		'fields' => $export_fields,
	) );
}
$table_manager->set_columns( $columns );
$table_manager->set_rows( $table_data_output );
$table_manager->pagination = true;
$table_manager->print_table();


if ( empty( $embed_form ) && $allow_search ) {
	?> </form> <?php
}