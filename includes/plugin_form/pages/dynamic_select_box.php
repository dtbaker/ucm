<form id="dynamic_select_form">
	<?php
	// we pull up a list of entries for this particular form
	$table      = isset( $_POST['table'] ) ? $_POST['table'] : false;
	$column     = isset( $_POST['column'] ) ? $_POST['column'] : false;
	$index      = isset( $_POST['index'] ) ? $_POST['index'] : false;
	$data_value = isset( $_POST['data_value'] ) ? $_POST['data_value'] : false;
	$hash       = isset( $_POST['hash'] ) ? $_POST['hash'] : false;
	$options    = $_POST;
	if ( isset( $options['index_id'] ) ) {
		$options['index_id'] = (int) $options['index_id'];
	}
	if ( isset( $options['entry_count'] ) ) {
		$options['entry_count'] = (int) $options['entry_count'];
	}
	unset( $options['hash'] );
	if ( $table && $column && $hash && md5( serialize( $options ) . _UCM_SECRET ) == $hash ) {
		// we can edit this table.

		if ( $data_value ) {
			// we are editing a value

			$fieldset_data = array(
				'heading'  => array(
					'type'  => 'h3',
					'title' => 'Edit Value',
				),
				'class'    => 'tableclass tableclass_form tableclass_full',
				'elements' => array(
					array(
						'title'  => _l( 'Value' ),
						'fields' => array(
							'type'  => 'text',
							'name'  => 'value',
							'value' => $data_value,
						),
					),
				)
			);
			echo module_form::generate_fieldset( $fieldset_data );


		} else {

			$data_items = array();
			if ( module_db::db_table_exists( $table ) ) {
				$table_layout = module_db::get_fields( $table );
				if ( $table_layout[ $column ] ) {
					foreach ( get_multiple( $table ) as $key => $val ) {
						$this_val = $val[ $column ];
						if ( $this_val ) {
							if ( ! isset( $data_items[ $this_val ] ) ) {
								$data_items[ $this_val ]                = $options;
								$data_items[ $this_val ]['data_value']  = $this_val;
								$data_items[ $this_val ]['entry_count'] = 0;
								$data_items[ $this_val ]['index_id']    = $index && ! empty( $val[ $index ] ) ? (int) $val[ $index ] : false;
							}
							$data_items[ $this_val ]['entry_count'] ++;
						}
					}
					// now we find out how many 'rel' entries are related to this item.
					if ( ! empty( $_POST['rel'] ) ) {
						foreach ( $data_items as $entry_value => $entry_data ) {
							if ( $entry_data['index'] && $entry_data['index_id'] ) {
								foreach ( $_POST['rel'] as $other_table ) {
									if ( module_db::db_table_exists( $other_table ) ) {
										$sql = "SELECT COUNT(*) AS `c` FROM `" . _DB_PREFIX . "$other_table` WHERE `" . db_escape( $entry_data['index'] ) . "` = '" . db_escape( $entry_data['index_id'] ) . "'";
										$qa  = qa1( $sql );
										if ( ! empty( $qa['c'] ) ) {
											$data_items[ $entry_value ]['entry_count'] += $qa['c'];
										}
									}
								}
							}
						}
					}
				}
			}

			/** START TABLE LAYOUT **/
			$table_manager = module_theme::new_table_manager();
			$columns       = array();
			/*$columns['index'] = array(
				'title' => 'Index',
				'callback' => function($data){
					echo 'sdf';
				}
			);*/
			$columns['data_value']  = array(
				'title'    => 'Value',
				'callback' => function ( $data ) {
					echo htmlspecialchars( $data['data_value'] );
				}
			);
			$columns['entry_count'] = array(
				'title'    => 'Entry Count',
				'callback' => function ( $data ) {
					echo htmlspecialchars( $data['entry_count'] );
				}
			);
			$columns['action']      = array(
				'title'    => 'Action',
				'width'    => 20,
				'callback' => function ( $data ) {
					$data['hash'] = md5( serialize( $data ) . _UCM_SECRET );
					?>
					<button class="edit_dynamic_select_option"
					        data-item="<?php echo htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' ); ?>"><?php _e( 'Edit' ); ?></button>
					<?php
				}
			);
			$table_manager->set_columns( $columns );
			$table_manager->set_rows( $data_items );
			$table_manager->table_id     = 'dynamic_select_box';
			$table_manager->inline_table = true;
			$table_manager->pagination   = false;
			$table_manager->print_table();
		}
	} else {
		echo 'Invalid permission';
	}
	?>
</form>