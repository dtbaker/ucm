<?php

class module_import_export extends module_base
{
	public $links;

	public function __construct() {
		$this->module_name     = 'import_export';
		$this->module_position = 240;
		$this->version         = 2.244;
	}

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
		// 2.244 - 2026-10-22 - running an import checks the permission to create and edit what it imports
		$this->links = array();
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
		// 2.243 - 2026-10-17 - 4.0.0 relaunch: security token before an import writes rows, only module import handlers callable


		module_config::register_css( 'import_export', 'import_export.css' );

	}


	public static $pagination_options = array();

	public static function run_pagination_hook( &$rows ) {

		if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
			// Exporting dumps every row on screen into a CSV, so it is submitted from the
			// list page with the form auth key published next to the hidden go field below.
			if ( ! module_form::check_secure_key() ) {
				module_form::reject_invalid_form_auth();
			}
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
                   value="<?php _e( 'Export CSV File' ); ?>"<?php module_form::print_form_auth_attributes(); ?>>
            <script type="text/javascript">
                $(function () {
                    $('#import_export_button').click(function () {
                        $('#import_export_go').val('yes');
			                <?php if (isset( self::$pagination_options['parent_form'] ) && self::$pagination_options['parent_form']) { ?>
                        var $target = $('#<?php echo self::$pagination_options['parent_form'];?>');
                        $target.append($('#import_export_go').clone());
                        ucm.submit_guarded_form($target, $(this).data('form-auth-key'));
			                <?php } else { ?>
                        ucm.submit_guarded(this);
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

	/**
	 * The import options travel out to the browser as a JSON blob and come back in the
	 * request, so by the time the import page reads the callback name out of them it is
	 * request data, not ours. Every caller of import_link() names a module import
	 * handler, so insist on that shape before calling it - the same guard
	 * module_email::send_email() puts on its success callback.
	 *
	 * @param mixed $callback
	 *
	 * @return bool
	 */
	public static function is_import_callback( $callback ) {
		return is_string( $callback )
		       && preg_match( '#^module_[a-z0-9_]+::[a-z0-9_]+$#i', $callback )
		       && is_callable( $callback );
	}

	/**
	 * Refuses an import the signed-in user's role does not allow.
	 *
	 * An import creates records and overwrites the ones that match, as whoever is signed
	 * in, so it needs both "create" and "edit" on what it imports - the permissions of the
	 * screens it writes for. The list pages only offer the import link to a role that may
	 * view "Import <things>", but the link is a form posting an `options` field that comes
	 * back from the browser, so the callback it names is checked here against the imports
	 * this application ships, rather than trusted.
	 *
	 * @param string $callback       the import handler the post names
	 * @param array  $import_options the import's decoded options
	 */
	public static function require_can_import( $callback, $import_options ) {
		$checks = array(
			'module_vendor::handle_import'        => array( 'module_vendor', 'Vendors' ),
			'module_job::handle_import'           => array( 'module_job', 'Jobs' ),
			'module_job::handle_import_tasks'     => array( 'module_job', 'Job Tasks' ),
			'module_quote::handle_import'         => array( 'module_quote', 'Quotes' ),
			'module_quote::handle_import_tasks'   => array( 'module_quote', 'Quote Tasks' ),
			'module_website::handle_import'       => array( 'module_website', 'Websites' ),
			'module_product::handle_import'       => array( 'module_product', 'Products' ),
			'module_member::handle_import'        => array( 'module_member', 'Members' ),
			'module_contract::handle_import'      => array( 'module_contract', 'Contracts' ),
		);
		$denied = 'Sorry, you do not have permission to import this.';
		if ( 'module_customer::handle_import' === $callback ) {
			// Customers and each kind of lead are held under their own name.
			$name  = isset( $import_options['name'] ) && is_string( $import_options['name'] ) ? $import_options['name'] : 'Customers';
			$known = false;
			foreach ( module_customer::get_customer_types() as $customer_type ) {
				if ( $customer_type['type_name_plural'] === $name ) {
					$known = true;
				}
			}
			if ( ! $known || ! module_customer::can_i( 'create', $name ) || ! module_customer::can_i( 'edit', $name ) ) {
				self::refuse_write( $denied, _BASE_HREF );
			}

			return;
		}
		if ( 'module_language::handle_import' === $callback ) {
			if ( ! module_config::can_i( 'edit', 'Settings' ) || ! module_language::can_i( 'edit', 'Language' ) ) {
				self::refuse_write( $denied, _BASE_HREF );
			}

			return;
		}
		if ( ! isset( $checks[ $callback ] ) ) {
			self::refuse_write( $denied, _BASE_HREF );
		}
		list( $class, $name ) = $checks[ $callback ];
		if ( ! class_exists( $class, false ) || ! $class::can_i( 'create', $name ) || ! $class::can_i( 'edit', $name ) ) {
			self::refuse_write( $denied, _BASE_HREF );
		}
		// Tasks are imported into one job or quote, which has to be one the user can open.
		if ( 'module_job::handle_import_tasks' === $callback ) {
			$job = module_job::get_job( isset( $import_options['job_id'] ) ? (int) $import_options['job_id'] : 0 );
			if ( ! $job || ! isset( $job['job_id'] ) || $job['job_id'] != (int) $import_options['job_id'] ) {
				self::refuse_write( $denied, _BASE_HREF );
			}
		} elseif ( 'module_quote::handle_import_tasks' === $callback ) {
			$quote = module_quote::get_quote( isset( $import_options['quote_id'] ) ? (int) $import_options['quote_id'] : 0 );
			if ( ! $quote || ! isset( $quote['quote_id'] ) || $quote['quote_id'] != (int) $import_options['quote_id'] ) {
				self::refuse_write( $denied, _BASE_HREF );
			}
		}
	}

	public static function import_link( $options = array() ) {
		$m = get_display_mode();
		if ( $m == 'mobile' || $m == 'iframe' ) {
			return false;
		}

		if ( module_config::c( 'import_method', 1 ) ) {
			module_form::print_form_open_tag( array(
				'action' => link_generate( array(
					array(
						'page'   => 'import',
						'module' => 'import_export'
					)
				) ),
				'style'  => 'display:none;',
				'id'     => 'import_form',
				'hidden' => array(
					'import_options' => module_config::c( 'import_export_base64', 1 ) ? base64_encode( json_encode( $options ) ) : json_encode( $options ),
				),
			) );
			?>
			<input type="submit" name="buttongo" value="go"><?php
			module_form::print_form_close_tag();
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