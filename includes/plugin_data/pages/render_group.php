<?php

if ( ! isset( $rendered_field_groups ) ) {
	$rendered_field_groups = array();
}
if ( ! isset( $view_revision_id ) ) {
	$view_revision_id = false;
}
if ( ! isset( $current_revision ) ) {
	$current_revision = false;
}
if ( ! isset( $mode ) ) {
	$mode = 'view';
}

if ( $current_revision && $view_revision_id ) {
	// user wants a custom revision, we pull out the custom $data_field_groups
	// and we tell the form layout to use the serialized cached field layout information
	$data_field_groups = unserialize( $current_revision['field_group_cache'], array( 'allowed_classes' => false ) );
	$data_field_group  = $data_field_groups[ $data_field_group_id ];
	$all_data_fields   = unserialize( $current_revision['field_cache'], array( 'allowed_classes' => false ) );
	if ( isset( $all_data_fields[ $data_field_group_id ] ) ) {
		$data_fields = $all_data_fields[ $data_field_group_id ];
	} else {
		$data_fields = array();
	}
} else {
	$data_field_group = $module->get_data_field_group( $data_field_group_id );
	$data_fields      = $module->get_data_fields( $data_field_group_id );
}

if ( ! isset( $rendered_field_groups[ $data_field_group_id ] ) ) {
	$rendered_field_groups[ $data_field_group_id ] = true;

	if ( ! isset( $display_field_group_heading ) ) {
		$display_field_group_heading = true;
	}

	if ( ! isset( $extra_group ) ) {
		$extra_group = array();
	}
	if ( ! isset( $hide_show_group_on_load ) ) {
		$hide_show_group_on_load = array();
	}

	// todo: change display_default based on php on page load, not just javascript after page load.
	$display_group = true;
	/*if(!$data_field_group['display_default'] && !isset($hide_show_group_on_load[$data_field_group['data_field_group_id']])){
		$display_group = false;
	}*/

	?>

	<div class="data_group<?php echo ( ! $display_group ) ? ' data_group_hidden' : ''; ?>"
	     style="<?php echo ( ! $display_group ) ? 'display:none;' : ''; ?>"
	     id="data_group_<?php echo $data_field_group['data_field_group_id']; ?>">
		<?php

		if ( isset( $data_field_group['sub_data_type_id'] ) && $data_field_group['sub_data_type_id'] ) {

			if ( (int) $data_record_id > 0 ) {
				$data_type    = $module->get_data_type( $data_field_group['sub_data_type_id'] );
				$data_type_id = $data_type['data_type_id'];
				if ( $data_type_id ) {
					$allow_search          = false;
					$parent_data_record_id = $data_record_id;
					include( 'admin_data_list_type.php' );
				}
			}

		} else {

			ob_start(); // to pass this into our form printing method (so themes can style this output nicer)
			?>
			<div>

				<input type="hidden" name="save_data_group[<?php echo $data_field_group['data_field_group_id']; ?>]"
				       id="save_group_<?php echo $data_field_group['data_field_group_id']; ?>"
				       value="<?php echo ( $data_field_group['display_default'] ) ? 'true' : ''; ?>">

			</div>
			<?php

			// This used to branch on $data_field_group['layout'] in six places - open the table,
			// open the row, open the header cell, open the data cell, close the row, close the
			// table - streaming rows out between them, which is why it could not be a fieldset.
			// Every one of those switches had a 'table' case and a 'float' case, and this file
			// opened by setting layout to 'table' unconditionally while nothing anywhere set
			// 'float', so the float half was unreachable. With it gone the rows are a plain
			// list, which is what a fieldset takes, and nothing here reads layout any more.
			// The switch on $data_field['field_type'] went the same way: its only other case
			// was commented out.
			$group_elements = array();

			foreach ( $data_fields as $data_field ) {
				$data_field_id = $data_field['data_field_id'];

				// check if there is a value for this field, and use it
				if ( isset( $data_items[ $data_field_id ] ) ) {
					$data_field['value'] = $data_items[ $data_field_id ]['data_text']; // todo, could be data_number or data_varchar as well... hmmm
				}
				if ( isset( $_POST['data_field'] ) && is_array( $_POST['data_field'] ) && isset( $_POST['data_field'][ $data_field['data_field_id'] ] ) ) {
					// this field was posted before.
					$data_field['value'] = $_POST['data_field'][ $data_field['data_field_id'] ];
				}
				if (
					$data_field['field_type'] == 'radio' &&
					isset( $_POST['other_data_field'] ) &&
					is_array( $_POST['other_data_field'] ) &&
					isset( $_POST['other_data_field'][ $data_field['data_field_id'] ] ) &&
					strtolower( $_POST['data_field'][ $data_field['data_field_id'] ] ) == 'other'
				) {
					// this field was posted before.
					$data_field['value'] = $_POST['other_data_field'][ $data_field['data_field_id'] ];
				}

				$highlight = $module->is_highlight( $data_field );
				$style     = '';
				if ( $data_field['width'] ) {
					$style .= 'width:' . $data_field['width'] . 'px; ';
				}
				if ( ! $view_revision_id && $data_field['height'] ) {
					$style .= 'height:' . $data_field['height'] . 'px; ';
				}
				if ( preg_match( '#style="([^"]+)"#', $data_field['field_data'], $matches ) ) {
					$style .= $matches[1];
				}

				// The field title is a link to the field editor in admin mode, so it is raw
				// markup and goes through title_fields rather than 'title'.
				ob_start();
				if ( $mode == 'admin' ) {
					?><a href="<?php echo $module->link( '', array(
						"data_type_id"        => $data_field['data_type_id'],
						"data_field_group_id" => $data_field['data_field_group_id'],
						"data_field_id"       => $data_field['data_field_id'],
					) ); ?>" target="_blank"><?php echo $data_field['title']; ?></a><?php
				} else {
					echo $data_field['title'];
				}
				$field_title = ob_get_clean();

				ob_start();
				$has_write_access = isset( $embed_form ) || $view_revision_id;
				echo $module->get_form_element( $data_field, $has_write_access, isset( $data_record ) ? $data_record : array() );
				?>
				<span class="data_field_action"></span>
				<?php

				// now some javascript if this field is set to display other groups if it's selected.
				// This used to sit between </td> and </tr>; inside the cell is where it belongs, and
				// it does the same thing from there.
				if ( $data_field['display_group_if'] ) {
					?>
					<script type="text/javascript">
						var field_value_<?php echo $data_field_id; ?> = '<?php echo ( isset( $data_field['value'] ) ) ? $data_field['value'] : ''; ?>';

						function change_group_<?php echo $data_field_id; ?>() {
							<?php
							foreach ( explode( ',', $data_field['display_group_if'] ) as $display_group_if ) {
								$foo = explode( '|', $display_group_if );
								// work out if we're displaying this gruop on load or not.
								if ( isset( $data_field['value'] ) && $data_field['value'] == $foo[0] ) {
									$hide_show_group_on_load[ $foo[1] ] = true;
								} elseif ( $data_field['value'] && $data_field['field_type'] == 'radio' && $foo[0] == 'Other' ) {
									// work out of the "Other" option is currently selected.
									foreach ( explode( "\n", trim( $data_field['field_data'] ) ) as $line ) {
										$line = trim( $line );
										if ( preg_match( '/^attributes=/', $line ) ) {
											if ( preg_match( '/Other$/', $line ) ) {
												// we have an 'other' option!
												$line       = preg_replace( '/^attributes=/', '', $line );
												$attributes = explode( "|", $line );
												// if this value isn't one of the attributes, then we assume it's an other
												// and we mark this value as dispalyed!! for the next group .
												$attribute_exists = false;
												foreach ( $attributes as $attribute ) {
													if ( $attribute == $data_field['value'] ) {
														$attribute_exists = true;
													}
												}
												if ( ! $attribute_exists ) {
													$hide_show_group_on_load[ $foo[1] ] = true;
												}
											}
											break;
										}
									}
								}
								?>
							if (field_value_<?php echo $data_field_id; ?> == '<?php echo ( $foo[0] ) ? $foo[0] : ''; ?>') {
								$('#data_group_<?php echo $foo[1]; ?>').show();
								$('#data_group_<?php echo $foo[1]; ?>').removeClass('data_group_hidden');
								$('#save_group_<?php echo $foo[1]; ?>').val('true');
							} else {
								$('#data_group_<?php echo $foo[1]; ?>').hide();
								$('#data_group_<?php echo $foo[1]; ?>').addClass('data_group_hidden');
								$('#save_group_<?php echo $foo[1]; ?>').val('');
							}
								<?php
							}
							?>
						}

						$(function () {
							var has_radio = false;
							$('#data_field_<?php echo $data_field_id; ?> input').change(function () {
								if ($(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio') {
									has_radio = true;// to stop the other option taking over on change.
									if (this.checked) {
										field_value_<?php echo $data_field_id; ?> = this.value;
									}
								} else if (!has_radio) {
									field_value_<?php echo $data_field_id; ?> = this.value;
								}
								change_group_<?php echo $data_field_id; ?>();
							});
							$('#data_field_<?php echo $data_field_id; ?> input').each(function () {
								if ($(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio') {
									if (this.checked) {
										field_value_<?php echo $data_field_id; ?> = this.value;
										change_group_<?php echo $data_field_id; ?>();
									}
								}
							});
						});
					</script>
					<?php
				}// display group if
				$field_cell = ob_get_clean();

				$row_class = 'data_field data_field_' . $data_field['field_type'];
				if ( $highlight || isset( $custom_highlight_fields[ $data_field_id ] ) ) {
					$row_class .= ' form_field_highlight';
				}

				$group_elements[ 'data_field_' . $data_field_id ] = array(
					// jQuery UI's sortable('serialize') builds container_data_field[] out of this
					// id, and module_data reads $_REQUEST['container_data_field'] to save the new
					// order, so the id is the reorder feature rather than decoration. The resize
					// handlers in data_type_admin_group_open.php read rel off the same row.
					'row_attributes'  => array(
						'id'  => 'container_data_field_' . $data_field_id,
						'rel' => $data_field_id,
					),
					'row_class'       => $row_class,
					'row_style'       => $style,
					'row_title_class' => 'width2',
					'title_fields'    => array( $field_title ),
					'fields'          => array( $field_cell ),
				);
			}

			$fieldset_data = array(
				'heading'         => false,
				'class'           => 'tableclass tableclass_form tableclass_full data_group_fields',
				'elements_before' => ob_get_clean(),
				'elements'        => $group_elements,
			);
			if ( $display_field_group_heading ) {
				$fieldset_data['heading'] = array(
					'title' => htmlspecialchars( $data_field_group['title'] ),
					'type'  => 'h3',
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
		?>
	</div>

<?php } ?>