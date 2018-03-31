<?php


hook_add( 'print_heading', 'blocks_print_heading' );
// copied from theme.php
function blocks_print_heading( $callback, $options ) {
	if ( ! is_array( $options ) ) {
		$options = array(
			'type'  => 'h2',
			'title' => $options,
		);
	}
	$buttons = array();
	if ( isset( $options['button'] ) && is_array( $options['button'] ) && count( $options['button'] ) ) {
		$buttons = $options['button'];
		if ( isset( $buttons['url'] ) ) {
			$buttons = array( $buttons );
		}
	}

	if ( ! isset( $options['type'] ) ) {
		$options['type'] = 'h2';
	}
	if ( ( isset( $options['main'] ) && $options['main'] ) ) { //} || !isset($GLOBALS['blocks_main_title']) || !$GLOBALS['blocks_main_title']){
		// save this one for use in the main header area of the theme
		ob_start();

		?>
		<div class="header_buttons right">
			<a href="#" class="btn right btn-blocks-5 btn-success btn-sm btn-search">
				<i class="fa fa-search"></i>
				<span><?php echo _l( 'Search' ); ?></span>
			</a>
			<?php foreach ( $buttons as $button ) { ?>
				<a href="<?php echo $button['url']; ?>"
				   class="btn right btn-blocks-5 btn-success btn-sm<?php echo isset( $button['class'] ) ? ' ' . $button['class'] : ''; ?>"<?php
				if ( isset( $button['id'] ) ) {
					echo ' id="' . $button['id'] . '"';
				}
				if ( isset( $button['onclick'] ) ) {
					echo ' onclick="' . $button['onclick'] . '"';
				}
				if ( isset( $button['ajax-modal'] ) ) {
					echo " data-ajax-modal='" . json_encode( $button['ajax-modal'] ) . "'";
				}
				?>>
					<?php if ( isset( $button['type'] ) && $button['type'] == 'add' ) { ?> <i class="fa fa-plus"></i> <?php } ?>

					<span><?php echo _l( $button['title'] ); ?></span>
				</a>
			<?php } ?>
			<?php if ( isset( $options['help'] ) ) { ?>
				<span class="button">
                    <?php _h( $options['help'] ); ?>
                </span>
			<?php } ?>
		</div>
		<?php if ( get_display_mode() == 'iframe' ) { ?>
			<h3 class="title">
				<?php echo isset( $options['title_final'] ) ? $options['title_final'] : _l( $options['title'] ); ?>
			</h3>
		<?php } else { ?>
			<ul class="breadcrumbs left">
				<li>
					<?php if ( isset( $options['icon_name'] ) && $options['icon_name'] ) {
						?> <i class="fa fa-<?php echo $options['icon_name']; ?>"></i> <?php
					} ?>
					<span class="title">
		            <?php echo isset( $options['title_final'] ) ? $options['title_final'] : _l( $options['title'] ); ?>
		        </span>
				</li>
			</ul>
			<?php
		}
		$GLOBALS['blocks_main_title'] = ob_get_clean();
		if ( get_display_mode() == 'iframe' ) {
			echo $GLOBALS['blocks_main_title'];
		}
	} else {
		?>
		<<?php echo $options['type']; ?> class="<?php echo isset( $options['class'] ) ? $options['class'] : ''; ?>">
		<?php foreach ( $buttons as $button ) { ?>
			<span class="button">
	                <a href="<?php echo $button['url']; ?>"
	                   class="btn btn-default btn-xs<?php echo isset( $button['class'] ) ? ' ' . $button['class'] : ''; ?>"<?php
	                if ( isset( $button['id'] ) ) {
		                echo ' id="' . $button['id'] . '"';
	                }
	                if ( isset( $button['onclick'] ) ) {
		                echo ' onclick="' . $button['onclick'] . '"';
	                }
	                if ( isset( $button['ajax-modal'] ) ) {
		                echo " data-ajax-modal='" . json_encode( $button['ajax-modal'] ) . "'";
	                }
	                ?>>
	                    <?php if ( isset( $button['type'] ) && $button['type'] == 'add' ) { ?> <img
		                    src="<?php echo _BASE_HREF; ?>images/add.png" width="10" height="10" alt="add"
		                    border="0"/> <?php } ?>
		                <span><?php echo _l( $button['title'] ); ?></span>
	                </a>
	            </span>
		<?php } ?>
		<?php if ( isset( $options['help'] ) ) { ?>
			<span class="button">
                    <?php _h( $options['help'] ); ?>
                </span>
		<?php } ?>
		<?php if ( isset( $options['responsive'] ) && is_array( $options['responsive'] ) && isset( $options['responsive']['summary'] ) ) { ?>
			<span class="button responsive-toggle-button">
			        <a href="#" class="btn btn-default btn-xs no_permissions"><span
					        class="responsive-hidden fa fa-plus-square"></span><span
					        class="responsive-shown fa fa-minus-square"></span> </a>
		        </span>
		<?php } ?>
		<?php if ( isset( $options['responsive'] ) && is_array( $options['responsive'] ) && isset( $options['responsive']['title'] ) ) { ?>
			<span class="title has-responsive">
                <span
	                class="main-title"><?php echo isset( $options['title_final'] ) ? $options['title_final'] : _l( $options['title'] ); ?></span>
			    <span class="responsive-title"> <?php _e( $options['responsive']['title'] ); ?> </span>
            </span>
		<?php } else { ?>
			<span class="title">
                <?php echo isset( $options['title_final'] ) ? $options['title_final'] : _l( $options['title'] ); ?>
            </span>
		<?php } ?>
		<?php if ( isset( $options['responsive'] ) && is_array( $options['responsive'] ) && isset( $options['responsive']['summary'] ) ) { ?>
			<span class="responsive-summary"><?php echo $options['responsive']['summary']; ?></span>
		<?php } ?>
		</<?php echo $options['type']; ?>>
		<?php
	}

	return true;

}

hook_add( 'extra_fields_search_bar', 'blocks_print_extra_search_bar' );
function blocks_print_extra_search_bar( $callback, $owner_table, $options = array() ) {
	ob_start();
	// let the themes override this search bar function.
	if ( module_extra::can_i( 'view', 'Extra Fields' ) ) {
		$defaults          = module_extra::get_defaults( $owner_table );
		$searchable_fields = array();
		foreach ( $defaults as $default ) {
			if ( isset( $default['searchable'] ) && $default['searchable'] ) {
				$searchable_fields[ $default['key'] ] = $default;
			}
		}
		foreach ( $searchable_fields as $searchable_field ) {
			?>
			<div class="form-group search_title">
				<?php echo htmlspecialchars( $searchable_field['key'] ); ?>:
			</div>
			<div class="form-group search_input">
				<?php
				module_form::generate_form_element( array(
					'type'  => 'text',
					'name'  => 'search[extra_fields][' . htmlspecialchars( $searchable_field['key'] ) . ']',
					'class' => ' form-control input-sm',
				) ); ?>
			</div>
			<?php
		}
	}

	return ob_get_clean();
}

hook_add( 'search_bar', 'blocks_search_bar' );
// copied from form.php
function blocks_search_bar( $callback, $options ) {

	$defaults = array(
		'type'     => 'table',
		'title'    => _l( 'Filter By:' ),
		'elements' => array(),
		'actions'  => array(
			'search' => '<button type="submit" class="btn btn-default btn-sm">' . _l( 'Search' ) . '</button>',
			// create_link("Search","submit"),
		),
	);
	$options  = array_merge( $defaults, $options );

	$id = 'filter-bar-' . md5( serialize( $options ) );
	ob_start();
	?>

	<div class="search_bar" role="search" id="<?php echo $id; ?>">
		<div class="search_bar_header">
			<?php if ( $options['title'] ) { ?>
				<a class="" href="#"><?php echo $options['title']; ?></a>
			<?php } ?>
		</div>
		<div class="search_bar_form">
			<?php /*if($options['title']){ ?>
	             <div class="form-group search_header"><strong><?php echo $options['title']; ?> </strong></div>
	        <?php }*/ ?>
			<?php foreach ( $options['elements'] as $element ) {
				if ( isset( $element['field'] ) && ! isset( $element['fields'] ) ) {
					$element['fields'] = array( $element['field'] );
				}
				if ( isset( $element['title'] ) && $element['title'] ) { ?>
					<div class="form-group search_title">
						<?php echo $element['title']; ?>
					</div>
				<?php } ?>
				<div class="form-group search_input">
					<?php if ( isset( $element['fields'] ) ) { ?>

						<?php if ( is_array( $element['fields'] ) ) {
							foreach ( $element['fields'] as $dataid => $field ) {
								if ( is_array( $field ) ) {
									// treat this as a call to the form generate option
									if ( ! isset( $field['placeholder'] ) && isset( $element['title'] ) && $element['title'] ) {
										//$field['placeholder'] = $element['title'];
									}
									if ( ! isset( $field['class'] ) ) {
										$field['class'] = '';
									}
									$field['class'] .= ' form-control input-sm';
									module_form::generate_form_element( $field );
									echo ' ';
								} else if ( is_closure( $field ) ) {
									$field();
								} else {
									echo $field . ' ';
								}
							}
						} else {
							echo $element['fields'];
						}
						?>
						<?php
					}
					?> </div> <?php

			}
			if ( class_exists( 'module_extra', false ) && isset( $options['extra_fields'] ) && $options['extra_fields'] ) {
				// find out if any extra fields are searchable
				module_extra::print_search_bar( $options['extra_fields'] );
			}
			if ( $options['actions'] ) {
				?>
				<div class="form-group search_action pull-right">
					<?php
					foreach ( $options['actions'] as $action_id => $action ) {
						echo $action . ' ';
					}
					?>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

hook_add( 'extra_fields_output', 'blocks_extra_fields_output' );
function blocks_extra_fields_output( $callback, $html, $owner_table, $owner_id ) {
	// regex out the table rows if needed.
	$html = str_replace( 'tbody', 'div', $html );
	if ( preg_match_all( '#<tr([^>]*)>(.*)</tr>#imsU', $html, $matches ) ) {
		// convert these into <divs>
		/*<div class="form-group">
						<div class="input-group">
										<span class="input-group-addon"><span class="width1">Name</span></span>

		<input type="text" name="customer_name" value="1 New Theme Test" class="form-control  plugin_form_required">
																		</div> <!-- /.input-group -->
		</div>*/
		foreach ( $matches[0] as $key => $val ) {
			preg_match( '#<th([^>]*)>(.*)</th>#imsU', $matches[2][ $key ], $title_match );
			preg_match( '#<td[^>]*>(.*)</td>#imsU', $matches[2][ $key ], $body_match );
			// check if existing class is in that match
			$classes = 'form-group extra-fields';
			if ( preg_match( '#class="([^"]+)"#', $matches[1][ $key ], $class_match ) ) {
				$classes            .= ' ' . $class_match[1];
				$matches[1][ $key ] = str_replace( $class_match[0], '', $matches[1][ $key ] );
			}
			$html = str_replace( $val, '<div class="' . $classes . '" ' . $matches[1][ $key ] . '><div class="input-group"><span class="input-group-addon table-row-title" ' . $title_match[1] . '><span class="width1">' . $title_match[2] . '</span></span>' .
			                           '<div class="form-control">' . $body_match[1] . '</div></div></div>'
				, $html );
		}
	}

	return $html;
}

hook_add( 'generate_fieldset', 'blocks_generate_fieldset' );
// copied from form.php
function blocks_generate_fieldset( $callback, $options ) {
	$defaults = array(
		'id'              => false,
		'type'            => 'table',
		'title'           => false,
		'title_type'      => 'h3',
		'heading'         => false,
		'row_title_class' => 'width1',
		'row_data_class'  => '',
		'elements'        => array(),
		'class'           => 'tableclass tableclass_form',
		'extra_settings'  => array(),
		'elements_before' => '',
		'elements_after'  => '',
	);
	$options  = array_merge( $defaults, $options );
	if ( function_exists( 'hook_filter_var' ) ) {
		$options = hook_filter_var( 'generate_fieldset_options', $options );
	}
	$fieldset_id = $options['id'] ? $options['id'] : 'fieldset_' . substr( md5( serialize( $options['heading'] ) ), 0, 5 );
	// read out the custom field display options for this form (e.g. hidden fields)
	$user_display_settings = module_form::get_fieldset_settings( $fieldset_id );
	$fieldset_settings     = array();
	if ( isset( $options['editable'] ) && ! $options['editable'] ) {
		$fieldset_settings['editable'] = 0;
	}
	$fieldset_settings['fields'] = count( $options['elements'] );
	ob_start();
	?>

	<div class="box <?php echo isset( $options['heading']['responsive'] ) ? ' box-responsive' : ''; ?>"
	     data-fieldset-settings="<?php echo htmlspecialchars( json_encode( $fieldset_settings ), ENT_QUOTES, 'UTF-8' ); ?>"
	     data-fieldset-id="<?php echo $fieldset_id; ?>" id="<?php echo $fieldset_id; ?>">
		<div class="box-header">
			<?php if ( $options['heading'] ){
				if ( ! isset( $options['heading']['type'] ) || $options['heading']['type'] != 'h3' ) {
					$options['heading']['type'] = 'h3';
				}
				$options['heading']['class'] = 'box-title';
				print_heading( $options['heading'] );
			}else if ( $options['title'] ){ ?>
			<<?php echo $options['title_type']; ?>
			class="box-title"><?php _e( $options['title'] ); ?></<?php echo $options['title_type']; ?>>
		<?php } ?>
	</div>
	<!-- .block -->
	<div class="box-body">
		<?php echo $options['elements_before']; ?>
		<?php if ( $options['elements'] ) { ?>
			<div class="<?php echo $options['class']; ?>">
				<?php
				foreach ( $options['elements'] as $element_id => $element ) {
					if ( isset( $element['ignore'] ) && $element['ignore'] ) {
						continue;
					}
					if ( isset( $element['field'] ) && ! isset( $element['fields'] ) ) {
						$element['fields'] = array( $element['field'] );
						unset( $element['field'] );
					}
					?>
					<div class="form-group" id="<?php echo $fieldset_id . '_' . $element_id; ?>" <?php
					if ( ! empty( $element['dependency'] ) && is_array( $element['dependency'] ) ) {
						if ( empty( $element['dependency']['fieldset_id'] ) ) {
							$element['dependency']['fieldset_id'] = $fieldset_id;
						}
						?> data-dependency="<?php echo htmlspecialchars( json_encode( $element['dependency'] ), ENT_QUOTES, 'UTF-8' ); ?>" <?php
					}
					echo ' data-element-id="' . $element_id . '"';
					if ( ! empty( $user_display_settings['hidden'][ $element_id ] ) ) {
						echo ' class="fieldset_element_hidden"';
					} ?>>
						<?php if ( ( isset( $element['message'] ) && $element['message'] ) || ( isset( $element['warning'] ) && isset( $element['warning'] ) ) ) { ?>
							<div class="text-center">
								<?php if ( isset( $element['message'] ) ) { ?>
									<?php echo $element['message']; ?>
								<?php } else if ( isset( $element['warning'] ) ) { ?>
									<span class="error_text"><?php echo $element['warning']; ?></span>
								<?php } ?>
							</div>
						<?php } else { ?>
							<div class="input-group<?php echo ! isset( $element['title'] ) ? '-notitle' : ''; ?>">
								<?php if ( isset( $element['title'] ) ) { ?>
									<span class="input-group-addon table-row-title"><span
											class="<?php echo isset( $element['row_title_class'] ) ? $element['row_title_class'] : $options['row_title_class']; ?>"><?php echo htmlspecialchars( _l( $element['title'] ) ); ?></span></span>
								<?php }
								if ( isset( $element['fields'] ) ) { ?>

									<?php if ( is_array( $element['fields'] ) ) {
										// if there is only one element we put it up in the form-control so that it displays nicely.
										// if there are more than one elements we wrap them in a div form-control.
										$do_wrap = true;
										if ( count( $element['fields'] ) == 1 ) {
											$field = current( $element['fields'] );
											if ( is_array( $field ) && $field['type'] != 'wysiwyg' && $field['type'] != 'check' && $field['type'] != 'checkbox' ) {
												$do_wrap  = false;
												$currency = false;
												if ( $field['type'] == 'currency' ) {
													$field['type'] = 'text';
													$currency      = true;
													//$field['class'] = (isset($field['class']) ? $field['class'] : '') .' currency ';
												}
												$field['class'] = ( isset( $field['class'] ) ? $field['class'] : '' ) . ' form-control ' . ( isset( $element['row_data_class'] ) ? $element['row_data_class'] : $options['row_data_class'] );
												$help_text      = false;
												if ( isset( $field['help'] ) ) {
													// we put the help element outside in its own <span class="input-group-addon"></span>
													// don't let the generatE_form_element produce it.
													$help_text = $field['help'];
													unset( $field['help'] );
												}
												module_form::generate_form_element( $field );
												if ( $currency ) {
													?>
													<span
														class="input-group-addon"><?php echo currency( '', true, isset( $field['currency_id'] ) ? $field['currency_id'] : false ); ?></span>
													<?php
												}
												if ( $help_text ) {
													?>
													<span class="input-group-addon"><?php _h( $help_text ); ?></span>
													<?php
												}
											}
										}
										if ( $do_wrap ) {
											?>
											<div
												class="form-control<?php echo ! isset( $element['title'] ) ? '-notitle' : ''; ?> <?php echo isset( $element['row_data_class'] ) ? $element['row_data_class'] : $options['row_data_class']; ?>"> <?php
												$help_text = false;
												foreach ( $element['fields'] as $dataid => $field ) {
													if ( is_array( $field ) && isset( $field['help'] ) ) {
														// this element has a help text.
														if ( $help_text ) {
															// already a help text (shouldn't happen, ditch it.. and display multiple as normal)
															$help_text = false;
															break;
														} else {
															$help_text = $field['help'];
														}
													}
												}
												foreach ( $element['fields'] as $dataid => $field ) {
													if ( is_array( $field ) ) {
														if ( isset( $field['help'] ) && $help_text ) {
															unset( $field['help'] );
														}
														// treat this as a call to the form generate option
														module_form::generate_form_element( $field );
														echo ' ';
													} else if ( is_closure( $field ) ) {
														$field();
													} else {
														echo $field . ' ';
													}
												}
												?> </div> <?php
											if ( $help_text ) {
												?>
												<span class="input-group-addon"><?php _h( $help_text ); ?></span>
												<?php
											}
										}

									} else {
										?>
										<div
											class="form-control <?php echo isset( $element['row_data_class'] ) ? $element['row_data_class'] : $options['row_data_class']; ?>"> <?php
											echo $element['fields'];
											?> </div> <?php
									}
									?>
								<?php } // fields ?>
							</div> <!-- /.input-group -->
							<?php
						}//else
						?>
					</div> <!-- /.form-group -->
					<?php
				}
				if ( class_exists( 'module_extra' ) && module_extra::is_plugin_enabled() && $options['extra_settings'] ) {
					module_extra::display_extras( $options['extra_settings'] );
				}
				?>
			</div> <!-- /.elements -->
			<?php
		}
		echo $options['elements_after']; ?>
		<!-- /.block -->
	</div>
	</div>


	<?php

	return ob_get_clean();
}

hook_add( 'generate_form_actions', 'blocks_generate_form_actions' );
function blocks_generate_form_actions( $callback, $options ) {

	$defaults = array(
		'type'     => 'action_bar',
		'class'    => 'action_bar',
		'elements' => array(),
	);
	$options  = array_merge( $defaults, $options );
	//todo - hook in here for themes.
	ob_start();
	?>
	<div class="box-footer">
		<div class="action_bar_duplicate <?php echo $options['class']; ?>">
			<?php
			foreach ( $options['elements'] as $element ) {
				if ( is_array( $element ) && ! is_array( current( $element ) ) ) {
					$element = array( $element );
				}
				$element['fields'] = $element;
				?>
				<span class="action">
                    <?php if ( isset( $element['fields'] ) ) { ?>
	                    <span class="action_element">
                        <?php if ( is_array( $element['fields'] ) ) {
	                        foreach ( $element['fields'] as $dataid => $field ) {
		                        if ( is_array( $field ) ) {
			                        // treat this as a call to the form generate option
			                        switch ( $field['type'] ) {
				                        case 'save_button':
					                        $field['type']  = 'submit';
					                        $field['class'] = ( isset( $field['class'] ) ? $field['class'] . ' ' : '' ) . 'submit_button btn btn-success';
					                        break;
				                        case 'submit':
					                        $field['type']  = 'submit';
					                        $field['class'] = ( isset( $field['class'] ) ? $field['class'] . ' ' : '' ) . 'submit_button btn btn-default';
					                        break;
				                        case 'delete_button':
					                        $field['type']  = 'submit';
					                        $field['class'] = ( isset( $field['class'] ) ? $field['class'] . ' ' : '' ) . 'submit_button btn btn-danger';
					                        break;
				                        case 'button':
					                        $field['type']  = 'button';
					                        $field['class'] = ( isset( $field['class'] ) ? $field['class'] . ' ' : '' ) . 'submit_button btn btn-default';
					                        break;
			                        }
			                        module_form::generate_form_element( $field );
			                        echo ' ';
		                        } else {
			                        echo $field . ' ';
		                        }
	                        }
                        } else {
	                        echo $element['fields'];
                        }
                        ?>
                    </span>
	                    <?php
                    }
                    ?>
                </span>
			<?php } ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}

//hook_add('plugins_loaded','blocks_plugins_loaded');
//function blocks_plugins_loaded(){
hook_remove( 'layout_column_half', 'module_theme::hook_handle_layout_column' );
hook_remove( 'layout_column_thirds', 'module_theme::hook_handle_layout_column' );
hook_add( 'layout_column_half', 'blocks_layout_column' );
hook_add( 'layout_column_thirds', 'blocks_layout_column' );
//}

function blocks_layout_column( $column_type, $column_option = '', $column_width = false ) {
	if ( ! empty( $_POST['modal'] ) ) {
		return;
	}
	switch ( $column_type ) {
		case 'layout_column_half':
			switch ( $column_option ) {
				case 1:
					$column_width = $column_width ? floor( 12 / ( 100 / $column_width ) ) : 6;
					echo '<div class="row"><div class="col-md-' . $column_width . '">';
					break;
				case 2:
					$column_width = $column_width ? ceil( 12 / ( 100 / $column_width ) ) : 6;
					echo '</div><div class="col-md-' . $column_width . '">';
					break;
				case 'end':
					echo '</div></div>';
					break;
			}
			break;
		case 'layout_column_thirds':
			if ( ! $column_width ) {
				$column_width = 33;
			}
			$column_width = $column_width ? round( 12 / ( 100 / $column_width ) ) : 4;
			switch ( $column_option ) {
				case 'start':
					echo '<div class="row">';
					break;
				case 'col_start':
					echo '<div class="col-md-' . $column_width . '">';
					break;
				case 'col_end':
					echo '</div>';
					break;
				case 'end':
					echo '</div>';
					break;
			}
			break;
	}
}