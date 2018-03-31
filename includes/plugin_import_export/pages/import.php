<?php
$import_options = module_config::c( 'import_export_base64', 1 ) ? json_decode( base64_decode( $_REQUEST['import_options'] ), true ) : json_decode( $_REQUEST['import_options'], true );
if ( ! $import_options || ! is_array( $import_options ) ) {
	echo 'Sorry import failed. Please try again';
	exit;
}

$extra_fields = array();
if ( class_exists( 'module_extra', false ) && isset( $import_options['extra'] ) && $import_options['extra'] && is_array( $import_options['extra'] ) ) {
	// support for multiple extra fields.
	if ( isset( $import_options['extra']['owner_table'] ) ) {
		$import_options['extra'] = array( $import_options['extra'] );
	}
	foreach ( $import_options['extra'] as $extra_option ) {
		$sql            = "SELECT `extra_key` FROM `" . _DB_PREFIX . "extra` WHERE owner_table = '" . db_escape( $extra_option['owner_table'] ) . "' AND `extra_key` != '' GROUP BY `extra_key` ORDER BY `extra_key`";
		$extra_fields[] = qa( $sql );
	}
}

define( '_CSV_JS_DELIM', '|||-|||' );


if ( isset( $_REQUEST['download'] ) ) {
	ob_end_clean();
	header( "Pragma: public" );
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Cache-Control: private", false );
	header( "Content-Type: text/csv" );
	//todo: correct file name
	header( "Content-Disposition: attachment; filename=\"SampleImportFile.csv\";" );
	header( "Content-Transfer-Encoding: binary" );
	foreach ( $import_options['fields'] as $key => $val ) {
		echo '"' . str_replace( '"', '""', $key ) . '",';
	}
	foreach ( $extra_fields as $fields ) {
		foreach ( $fields as $key => $val ) {
			echo '"' . str_replace( '"', '""', $val['extra_key'] ) . '",';
		}
	}
	echo "\n";
	exit;
}

print_heading( _l( 'Import Data: %s', $import_options['name'] ) );

$add_to_group = array();

if ( isset( $_REQUEST['run_import'] ) && $_REQUEST['run_import'] == 'true' ) {


	if ( isset( $_REQUEST['add_to_group'] ) ) {
		$add_to_group = json_decode( base64_decode( $_REQUEST['add_to_group'] ), true );
	}

	// we get all the import data from the posted form, then we hand it back to the callback function specified in the import options.
	$columns  = $_POST['c'];
	$rows     = isset( $_POST['r'] ) ? $_POST['r'] : array();
	$fullrows = isset( $_POST['fullrow'] ) ? $_POST['fullrow'] : array();
	// sort them into a big matrix of matching data.
	$data = array();
	foreach ( $fullrows as $rowid => $fulldata ) {
		$rowdata = explode( _CSV_JS_DELIM, $fulldata );
		$newrow  = array();
		foreach ( $rowdata as $column => $value ) {
			if ( isset( $columns[ $column ] ) && $columns[ $column ] ) {
				// if we selected this as a column:
				$newrow[ $columns[ $column ] ] = $value;
			}
		}
		$data[ $rowid ] = $newrow;
		unset( $fullrows[ $rowid ] );
	}
	foreach ( $rows as $rowid => $rowdata ) {
		$newrow = array();
		foreach ( $rowdata as $column => $value ) {
			if ( isset( $columns[ $column ] ) && $columns[ $column ] ) {
				// if we selected this as a column:
				$newrow[ $columns[ $column ] ] = $value;
			}
		}
		$data[ $rowid ] = $newrow;
		unset( $rows[ $rowid ] );
	}

	if ( $import_options['callback'] ) {
		$extra_options = array();
		if ( isset( $import_options['options'] ) && is_array( $import_options['options'] ) ) {
			foreach ( $import_options['options'] as $option_id => $option_values ) {
				if ( isset( $_POST[ $option_values['form_element']['name'] ] ) ) {
					$extra_options[ $option_values['form_element']['name'] ] = $_POST[ $option_values['form_element']['name'] ];
				}
			}
		}
		$result = call_user_func( $import_options['callback'], $data, $add_to_group, $extra_options );
		if ( $result === 0 || (int) $result > 0 ) {
			_e( 'Successfully imported %s records. Thanks!', $result );
		} else {
			_e( 'Successfully imported %s records. Thanks!', count( $data ) );
		}

	} else {
		echo 'failed..';
	}
	if ( $import_options['return_url'] ) { ?>
		<input type="button" name="cancel" value="<?php _e( 'Continue' ); ?>" class="submit_button"
		       onclick="window.location.href='<?php echo htmlspecialchars( $import_options['return_url'] ); ?>';">
	<?php }

} else if ( isset( $_REQUEST['upload_import'] ) && $_REQUEST['upload_import'] == 'true' ) {

	$csv_file = $_FILES['csv']['tmp_name'];
	if ( ! $csv_file ) {
		echo 'Upload failed. Please try again.';
		exit;
	}
	// for windows hosting:
	/*if(isset($_FILES['csv']) && is_uploaded_file($_FILES['csv']['tmp_name'])){
			$csv_file = "temp/import_file";
			move_uploaded_file($_FILES['csv']['tmp_name'],$csv_file);
	}else{
			echo 'failed';
	}*/
	$fd   = fopen( $csv_file, 'r' );
	$rows = array();
	while ( $row = fgetcsv( $fd ) ) {
		$rows[] = $row;
	}
	if ( count( $rows ) <= 1 ) {
		echo 'There are less than 1 rows in this import file. Please try again with more rows.';
		exit;
	}
	$provided_header = array_shift( $rows );
	foreach ( $provided_header as $key => $val ) {
		if ( ! trim( $val ) ) {
			unset( $provided_header[ $key ] );
		}
	}
	$column_count = count( $provided_header );
	// check sohusin error
	$post_field_count = count( $rows ); //$column_count *
	$sohusin1         = ini_get( 'suhosin.post.max_vars' );
	$sohusin2         = ini_get( 'suhosin.request.max_vars' );
	if (
		( (int) $sohusin1 > 0 && $post_field_count >= $sohusin1 ) ||
		( (int) $sohusin2 > 0 && $post_field_count >= $sohusin2 )
	) {
		?>
		<div class="warning">
			Warning, the hosting provider is using the suhosin patch for PHP, which limit the maximum number of fields to post
			in a form:
			<?php echo $sohusin1; ?> for suhosin.post.max_vars. <br/>
			<?php echo $sohusin2; ?> for suhosin.request.max_vars. <br/>
			Please ask your hosting provider to increase the suhosin post and request limit
			to <?php echo $post_field_count; ?> at least. Or import less rows at a time (eg: 20 records at a time).
		</div>
	<?php }
	$max_input = ini_get( 'max_input_vars' );
	if (
	( (int) $max_input > 0 && $post_field_count >= $max_input )
	) {
		?>
		<div class="warning">
			Warning, the hosting provider has PHP setting 'max_input_vars' at <?php echo $max_input; ?>.<br/>
			Please ask your hosting provider to increase the 'max_input_vars' setting to <?php echo $post_field_count; ?> or
			higher. Or import less rows at a time (eg: 20 records at a time).
		</div>
	<?php } ?>

	<form action="" method="post" enctype="multipart/form-data">
		<input type="hidden" name="run_import" value="true">
		<?php if ( ! isset( $_GET['import_options'] ) ) { ?> <input type="hidden" name="import_options"
		                                                            value="<?php echo htmlspecialchars( $_REQUEST['import_options'] ); ?>"> <?php } ?>
		<input type="hidden" name="add_to_group"
		       value="<?php echo base64_encode( json_encode( isset( $_REQUEST['add_to_group'] ) ? $_REQUEST['add_to_group'] : array() ) ); ?>">
		<?php if ( isset( $import_options['options'] ) && is_array( $import_options['options'] ) ) {
			foreach ( $import_options['options'] as $option_id => $option_values ) {
				?>
				<input type="hidden" name="<?php echo htmlspecialchars( $option_values['form_element']['name'] ); ?>"
				       value="<?php echo htmlspecialchars( isset( $_REQUEST[ $option_values['form_element']['name'] ] ) ? $_REQUEST[ $option_values['form_element']['name'] ] : '' ); ?>">
			<?php }
		} ?>

		<p><?php _e( 'We have detected %s records to import. Please confirm your import data below, once you are happy with the columns please press the process button. If the import format does not look correct, please go back and try again.', count( $rows ) ); ?></p>
		<?php if ( count( $rows ) > 600 ) { ?>
			<p
				style="color:#FF0000"><?php _e( 'We highly recommend splitting the import file up into smaller files!' ); ?></p>
		<?php } ?>

		<div style="overflow-x: auto;">
			<table class="tableclass tableclass_rows tableclass_full tbl_fixed">
				<thead>
				<tr>
					<?php
					$column_matches = array();

					for ( $column = 0; $column < $column_count; $column ++ ) {
						$display_key = isset( $provided_header[ $column ] ) ? $provided_header[ $column ] : '';
						?>
						<th><select name="c[<?php echo $column; ?>]">
								<option value=""> - ignore -</option>
								<?php foreach ( $import_options['fields'] as $key2 => $val2 ) {
									if ( ! is_array( $val2 ) ) {
										$val2 = array( $val2 );
									}
									$this_key = $val2[0];
									?>
									<option
										value="<?php echo $this_key; ?>"<?php if ( strtolower( $display_key ) == strtolower( $key2 ) ) {
										echo ' selected';
										$column_matches[ $column ] = $this_key;
									} ?>><?php echo htmlspecialchars( $key2 ); ?></option>
								<?php }
								foreach ( $extra_fields as $fields ) {
									foreach ( $fields as $key => $val ) {
										$this_key = 'extra:' . $val['extra_key'];
										$key2     = $val['extra_key'];
										?>
										<option
											value="<?php echo $this_key; ?>"<?php echo strtolower( $display_key ) == strtolower( $key2 ) ? ' selected' : ''; ?>><?php echo htmlspecialchars( $key2 ); ?></option>
										<?php
									}
								}
								?>
							</select>
						</th>
						<?php
					}
					if ( isset( $import_options['callback_preview'] ) && strlen( $import_options['callback_preview'] ) ) {
						?>
						<th><?php _e( 'Info' ); ?></th>
						<?php
					}
					?>
					<th width="20"></th>
				</tr>
				</thead>
				<tbody>
				<?php
				$rowid = 0;
				foreach ( $rows as $row ) { ?>
					<tr class="<?php echo $rowid % 2 ? 'odd' : 'even'; ?>" rel="<?php echo $rowid; ?>">
						<?php
						for ( $column = 0; $column < $column_count; $column ++ ) { ?>
							<td class="e">
								<?php echo htmlspecialchars( $row[ $column ] ); ?>
								<?php /*
                            if(preg_match('/[\n\r][^\s]/',$row[$column])){ ?>
                                <textarea rows="3" cols="3" name="r[<?php echo $rowid;?>][<?php echo $column;?>]" class="i"><?php echo htmlspecialchars($row[$column]);?></textarea>
                            <?php }else{ ?>
                                <input type="text" name="r[<?php echo $rowid;?>][<?php echo $column;?>]" value="<?php echo htmlspecialchars($row[$column]);?>" class="i">
                            <?php } */ ?>
							</td>
							<?php
						}
						if ( isset( $import_options['callback_preview'] ) && $import_options['callback_preview'] ) {
							$extra_options = array();
							if ( isset( $import_options['options'] ) && is_array( $import_options['options'] ) ) {
								foreach ( $import_options['options'] as $option_id => $option_values ) {
									if ( isset( $_POST[ $option_values['form_element']['name'] ] ) ) {
										$extra_options[ $option_values['form_element']['name'] ] = $_POST[ $option_values['form_element']['name'] ];
									}
								}
							}
							?>
							<td>
								<?php
								$row_formatted = array();
								foreach ( $column_matches as $column_match_id => $column_match ) {
									$row_formatted[ $column_match ] = $row[ $column_match_id ];
								}
								call_user_func( $import_options['callback_preview'], $row_formatted, $add_to_group, $extra_options ); ?>
							</td>
						<?php } ?>
						<td>
							<a href="#" class="editrow"><?php _e( "edit" ); ?></a>
							<input type="hidden" name="fullrow[<?php echo $rowid; ?>]"
							       value="<?php echo htmlspecialchars( implode( _CSV_JS_DELIM, $row ) ); ?>">
						</td>
					</tr>
					<?php
					$rowid ++;
				} ?>
				</tbody>
			</table>
			<script type="text/javascript">
          $(function () {
              $('body').delegate('.delrow', 'click', function () {
                  if (confirm('Really delete row?')) {
                      $(this).parents('tr').remove();
                  }
                  return false;
              }).delegate('.editrow', 'click', function () {
                  var rowdata = $(this).next('input').val().split('<?php echo _CSV_JS_DELIM;?>');
                  $(this).next('input').remove();
                  var colid = 0;
                  var rowid = $(this).parents('tr').attr('rel');
                  $(this).parents('tr').find('td.e').each(function () {
                      $(this).html('');
                      $(this).append('<input type="text" name="r[' + rowid + '][' + colid + ']" class="i">');
                      $(this).find('input').val(rowdata.shift());
                      colid++;
                  });

                  // delete
                  $(this).html('<?php _e( 'del' );?>');
                  $(this).attr('class', 'delrow');
                  return false;
              });
          });
			</script>
		</div>
		<p><?php echo _e( '(%s records)', count( $rows ) ); ?></p>
		<input type="submit" name="save" value="<?php _e( 'Process Import' ); ?>" class="submit_button save_button">
		<?php if ( $import_options['return_url'] ) { ?>
			<input type="button" name="cancel" value="<?php _e( 'Cancel' ); ?>" class="submit_button"
			       onclick="window.location.href='<?php echo htmlspecialchars( $import_options['return_url'] ); ?>';">
		<?php } ?>

		<p><?php _e( 'For your reference, the fields are:' ); ?></p>
		<ul>
			<?php
			foreach ( $import_options['fields'] as $key => $val ) {
				if ( ! is_array( $val ) ) {
					$val = array( $val );
				}
				echo '<li>';
				echo htmlspecialchars( $key );
				if ( isset( $val[1] ) && $val[1] ) {
					echo ' <span class="required">(required field)</span>';
				}
				if ( isset( $val[2] ) && $val[2] ) {
					_h( $val[2] );
				}
				echo '</li>';
			}
			foreach ( $extra_fields as $fields ) {
				foreach ( $fields as $key => $val ) {
					echo '<li>';
					echo htmlspecialchars( $val['extra_key'] );
					echo '</li>';
				}
			}
			?>
		</ul>
	</form>
	<?php
} else {
	?>

	<script type="text/javascript">
      function do_sample() {
          $('#sample_download')[0].submit();
      }
	</script>
	<form action="" method="post" id="sample_download">
		<input type="hidden" name="download" value="true">
		<input type="hidden" name="import_options" value="<?php echo htmlspecialchars( $_REQUEST['import_options'] ); ?>">
	</form>

	<p><?php _e( 'Please make sure your data is in the below format. The <strong>first line</strong> of your CSV file should be the column headers as below. You can <a href="%s">click here</a> to download a sample CSV template ready for import. Please use UTF8 file format. %s', 'javascript:do_sample();', _hr( 'Please try to save your import CSV file in UTF8 format for best results (search google for a howto). Once your import CSV file is ready to upload please use the form below. (Please ensure this is a CSV file, not an excel file.)<br> We recommend <a href="http://www.libreoffice.org/download/" target="_blank">LibreOffice</a> for best CSV file generation.' ) ); ?></p>


	<div style="overflow-x: auto; overflow-y: hidden;">
		<table class="tableclass tableclass_rows">
			<thead>
			<tr>
				<?php foreach ( $import_options['fields'] as $key => $val ) {
					if ( ! is_array( $val ) ) {
						$val = array( $val );
					}
					$display_name = $val[0];
					?>
					<th><?php echo htmlspecialchars( $key );
						if ( isset( $val[1] ) && $val[1] ) {
							echo ' <span class="required">*</span>';
						}
						if ( isset( $val[2] ) && $val[2] ) {
							_h( $val[2] );
						}
						?></th>
				<?php }

				// check for extra fields.
				foreach ( $extra_fields as $fields ) {
					foreach ( $fields as $extra_field ) {
						?>
						<th><?php echo htmlspecialchars( $extra_field['extra_key'] ); ?></th>
						<?php
					}
				}

				?>
			</tr>
			</thead>
			<tbody>
			<?php
			$extra_colspan = 0;
			for ( $x = 1; $x < 3; $x ++ ) { ?>
				<tr>
					<?php foreach ( $import_options['fields'] as $key => $val ) { ?>
						<td><?php _e( 'Record %s', $x ); ?></td>
					<?php } ?>
					<?php
					foreach ( $extra_fields as $fields ) {
						$extra_colspan += count( $fields );
						foreach ( $fields as $key => $val ) { ?>
							<td><?php _e( 'Record %s', $x ); ?></td>
							<?php
						}
					} ?>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="<?php echo count( $import_options['fields'] ) + $extra_colspan; ?>">
					<?php _e( 'etc...' ); ?>
				</td>
			</tr>
			</tbody>
		</table>
	</div>


	<form action="" method="post" enctype="multipart/form-data">
		<?php if ( ! isset( $_GET['import_options'] ) ) { ?> <input type="hidden" name="import_options"
		                                                            value="<?php echo htmlspecialchars( $_REQUEST['import_options'] ); ?>"> <?php } ?>
		<input type="hidden" name="upload_import" value="true">
		<h3><?php _e( 'Import' ); ?></h3>
		<table class="tableclass tableclass_form tableclass_full">
			<tbody>
			<tr>
				<th class="width2"><?php _e( 'Your CSV file (formatted to the above specification):' ); ?></th>
				<td>
					<input type="file" name="csv">
				</td>
			</tr>
			<?php if ( isset( $import_options['options'] ) && is_array( $import_options['options'] ) ) {
				foreach ( $import_options['options'] as $option_id => $option_values ) {
					?>
					<tr>
						<th>
							<?php echo $option_values['label']; ?>
						</th>
						<td>
							<?php module_form::generate_form_element( $option_values['form_element'] ); ?>
						</td>
					</tr>
				<?php }
			} ?>
			<?php if ( class_exists( 'module_group', false ) && isset( $import_options['group'] ) && $import_options['group'] ) {
				// hack to support multiple groups (for members)
				if ( ! is_array( $import_options['group'] ) ) {
					$import_options['group'] = array( $import_options['group'] );
				}
				foreach ( $import_options['group'] as $group_option ) {
					?>
					<tr>
						<th>
							<?php _e( 'Add imported records to group:' ); ?>
						</th>
						<td>
							<?php $groups = module_group::get_groups( $group_option );
							if ( ! count( $groups ) ) {
								_e( 'Sorry, no groups exist. Please create a %s group first.', $group_option );
							}
							foreach ( $groups as $group ) {
								$group_id = $group['group_id'];
								?>
								<input type="checkbox" class="add_to_group" name="add_to_group[<?php echo $group['group_id']; ?>]"
								       id="groupchk<?php echo $group_id; ?>" value="yes">
								<label for="groupchk<?php echo $group_id; ?>"><?php echo htmlspecialchars( $group['name'] ); ?></label>
								<br/>
								<?php
							} ?>
						</td>
					</tr>
					<?php
				}
			} ?>
			<tr>
				<th></th>
				<td>
					<input type="submit" name="go" value="<?php _e( 'Upload' ); ?>" class="submit_button save_button">
					<?php if ( $import_options['return_url'] ) { ?>
						<input type="button" name="cancel" value="<?php _e( 'Cancel' ); ?>" class="submit_button"
						       onclick="window.location.href='<?php echo htmlspecialchars( $import_options['return_url'] ); ?>';">
					<?php } ?>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
<?php } ?>