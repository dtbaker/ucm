<?php


class module_import_export extends module_base {

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
		$this->links           = array();
		$this->module_name     = "import_export";
		$this->module_position = 8882;

		$this->version = 2.242;
		// 2.2 - exporting groups to CSV (started with customer export)
		// 2.21 - clear 'eys' value from form after generation - support for a non parent() form submission (eg: from finance list)
		// 2.22 - import options moved to a hidden form submit, rather than a long GET url. better!
		// 2.23 - import extra fields (only customer supported at this stage, have to update other callback methods to handle extra saving correctly).
		// 2.231 - fix for download sample file.
		// 2.232 - fix for large file imports
		// 2.233 - error checking with sohusin max_fields setting
		// 2.234 - better handle large imports, option to edit/delete import rows
		// 2.235 - 2013-05-28 - advanced setting import_export_base64 to help with some hosting providers
		// 2.236 - 2014-03-26 - support for summary at bottom of csv file
		// 2.237 - 2014-03-31 - better extra field export support
		// 2.238 - 2014-07-07 - import/export javascript fix
		// 2.239 - 2014-07-14 - better import extra field support
		// 2.24 - 2016-01-06 - extra field export order
		// 2.241 - 2016-07-10 - big update to mysqli
		// 2.242 - 2016-10-25 - extra field update


		module_config::register_css( 'import_export', 'import_export.css' );

	}


	static $pagination_options = array();

	public static function run_pagination_hook( &$rows ) {

		if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
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
			// add these items to the import_export.

			if ( is_array( $rows ) && count( $rows ) ) {
				$fields = self::$pagination_options['fields'];
				// export as CSV file:
				ob_end_clean();

				ob_start();

				foreach ( $fields as $key => $val ) {
					echo '"' . str_replace( '"', '""', $key ) . '",';
				}
				// check for extra fields.
				if ( class_exists( 'module_extra', false ) && isset( self::$pagination_options['extra'] ) && count( self::$pagination_options['extra'] ) ) {
					if ( isset( self::$pagination_options['extra']['owner_table'] ) ) {
						self::$pagination_options['extra'] = array( self::$pagination_options['extra'] );
					}
					foreach ( self::$pagination_options['extra'] as $extra_field_id => $extra_field_settings ) {
						// get the column defaults ordered correctly.
						$sql                                                                  = "SELECT `extra_default_id`,`extra_key`, `order`, `display_type`, `owner_table`, `searchable`, `field_type`, `options` FROM `" . _DB_PREFIX . "extra_default` e  WHERE e.owner_table = '" . db_escape( $extra_field_settings['owner_table'] ) . "' ORDER BY e.`order` ASC";
						self::$pagination_options['extra'][ $extra_field_id ]['extra_fields'] = qa( $sql );
						foreach ( self::$pagination_options['extra'][ $extra_field_id ]['extra_fields'] as $extra_field ) {
							echo '"' . str_replace( '"', '""', $extra_field['extra_key'] ) . '",';
						}
					}
				}
				// check for group fields.
				if ( class_exists( 'module_group', false ) && isset( self::$pagination_options['group'] ) && self::$pagination_options['group'] ) {
					// find groups for this entry
					foreach ( self::$pagination_options['group'] as $group_search ) {
						echo '"' . str_replace( '"', '""', $group_search['title'] ) . '",';
					}
				}
				echo "\n";
				foreach ( $rows as $row ) {
					foreach ( $fields as $key => $val ) {
						echo '"' . str_replace( '"', '""', isset( $row[ $val ] ) ? $row[ $val ] : '' ) . '",';
					}
					// check for extra fields.
					if ( class_exists( 'module_extra', false ) && isset( self::$pagination_options['extra'] ) && count( self::$pagination_options['extra'] ) ) {

						foreach ( self::$pagination_options['extra'] as $extra_field_id => $extra_field_settings ) {
							$extra_vals = array();
							if ( isset( $row[ $extra_field_settings['owner_id'] ] ) && $row[ $extra_field_settings['owner_id'] ] > 0 ) {
								$sql        = "SELECT `extra_key` AS `id`, `extra` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = '" . db_escape( $extra_field_settings['owner_table'] ) . "' AND `owner_id` = '" . (int) $row[ $extra_field_settings['owner_id'] ] . "'";
								$extra_vals = qa( $sql );
							}
							foreach ( $extra_field_settings['extra_fields'] as $extra_field ) {
								echo '"';
								switch ( $extra_field['field_type'] ) {
									case 'reference':

										$reference = @json_decode( $extra_field['options'], true );
										if ( ! is_array( $reference ) ) {
											$reference = array();
										}
										$val = module_extra::get_reference( $extra_field_settings['owner_table'], $row[ $extra_field_settings['owner_id'] ], $extra_field, ! empty( $reference['reference'] ) ? $reference['reference'] : array() );
										echo str_replace( '"', '""', $val );

										break;
									default:
										echo isset( $extra_vals[ $extra_field['extra_key'] ] ) ? str_replace( '"', '""', $extra_vals[ $extra_field['extra_key'] ]['extra'] ) : '';
								}

								echo '",';
							}
						}
					}
					// check for group fields.
					if ( class_exists( 'module_group', false ) && isset( self::$pagination_options['group'] ) && self::$pagination_options['group'] ) {
						// find groups for this entry
						foreach ( self::$pagination_options['group'] as $group_search ) {
							$g      = array();
							$groups = module_group::get_groups_search( array(
								'owner_table' => $group_search['owner_table'],
								'owner_id'    => isset( $row[ $group_search['owner_id'] ] ) ? $row[ $group_search['owner_id'] ] : 0,
							) );
							foreach ( $groups as $group ) {
								$g[] = $group['name'];
							}
							echo '"' . str_replace( '"', '""', implode( ', ', $g ) ) . '",';
						}
					}
					echo "\n";
				}
				// is there a summary to add at the end of the export?
				if ( isset( self::$pagination_options['summary'] ) && is_array( self::$pagination_options['summary'] ) ) {
					foreach ( self::$pagination_options['summary'] as $summary_row ) {
						foreach ( $fields as $key => $val ) {
							echo '"';
							if ( isset( $summary_row[ $val ] ) ) {
								echo $summary_row[ $val ];
							}
							echo '",';
						}
						echo "\n";
					}
				}

				$csv = ob_get_clean();
				if ( module_config::c( 'export_csv_debug', 0 ) ) {
					echo '<pre>' . $csv . '</pre>';
					exit;
				}
				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				header( "Content-Type: text/csv" );
				//todo: correct file name
				header( "Content-Disposition: attachment; filename=\"" . ( isset( self::$pagination_options['name'] ) ? self::$pagination_options['name'] . '.csv' : 'Export.csv' ) . "\";" );
				header( "Content-Transfer-Encoding: binary" );
				// todo: calculate file size with ob buffering
				header( "Content-Length: " . strlen( $csv ) );
				echo $csv;

				exit;
			}
		}
	}

	public static function display_pagination_hook() {

		?>
		<span>
        <a href="#"
           onclick="if($('#import_export_popdown').css('display')=='inline' || $('#import_export_popdown').css('display')=='block') $('#import_export_popdown').css('display','none'); else $('#import_export_popdown').css('display','inline'); return false;">(<?php _e( 'export' ); ?>
	        )</a>
        <span id="import_export_popdown"
              style="position: absolute; width: 200px; display: none; background: #EFEFEF; margin-left: -210px; margin-top: 30px; border: 1px solid #CCC; text-align: left; padding: 6px; z-index: 3;">
            <strong><?php _e( 'Export all these results:' ); ?></strong><br/>
            <input type="hidden" name="import_export_go" id="import_export_go" value="">
            <input type="button" name="import_export_button" id="import_export_button"
                   value="<?php _e( 'Export CSV File' ); ?>">
            <script type="text/javascript">
                $(function () {
                    $('#import_export_button').click(function () {
                        $('#import_export_go').val('yes');
			                <?php if(isset( self::$pagination_options['parent_form'] ) && self::$pagination_options['parent_form']){ ?>
                        $('#<?php echo self::$pagination_options['parent_form'];?>').append($('#import_export_go').clone());
                        $('#<?php echo self::$pagination_options['parent_form'];?>')[0].submit();
			                <?php }else{ ?>
                        $('#import_export_go').parents('form')[0].submit();
			                <?php } ?>

                        $('#import_export_popdown').css('display', 'none');
                        $('#import_export_go').val('');
                    });
                });
            </script>
        </span>
        </span>
		<?php
	}

	public static function enable_pagination_hook( $options = array() ) {
		$GLOBALS['pagination_import_export_hack'] = true;
		self::$pagination_options                 = $options;
	}

	public static function import_link( $options = array() ) {
		$m = get_display_mode();
		if ( $m == 'mobile' || $m == 'iframe' ) {
			return false;
		}

		if ( module_config::c( 'import_method', 1 ) ) {
			?>
			<form action="<?php echo link_generate( array(
				array(
					'page'   => 'import',
					'module' => 'import_export'
				)
			) ); ?>" method="post" style="display:none;" id="import_form"><input type="submit" name="buttongo"
			                                                                     value="go"><input type="hidden"
			                                                                                       name="import_options"
			                                                                                       value='<?php
			                                                                                       echo module_config::c( 'import_export_base64', 1 ) ? base64_encode( json_encode( $options ) ) : addcslashes( json_encode( $options ), "'" ); ?>'>
			</form> <?php
			$url = 'javascript:document.forms.import_form.submit();';
		} else {
			//oldway:
			$url = link_generate( array(
				array(
					'arguments' => array(
						'import_options' => base64_encode( json_encode( $options ) ),
					),
					'page'      => 'import',
					'module'    => 'import_export'
				)
			) );

		}

		return $url;
	}
}