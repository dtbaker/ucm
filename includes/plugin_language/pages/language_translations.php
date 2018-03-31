<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}
if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}
if ( ! module_language::can_i( 'edit', 'Language' ) ) {
	redirect_browser( _BASE_HREF );
}

$language_id = (int) $_REQUEST['language_id'];

$language = module_language::get_language( $language_id );
if ( ! $language || $language['language_id'] != $language_id ) {
	$language_id = 0;
	$language    = array(
		'language_name' => '',
		'language_code' => '',
	);
}

$translations       = module_language::get_translations( $language_id );
$file_system_labels = array();
if ( $language['language_code'] ) {
	$old_labels = isset( $labels ) ? $labels : false;
	if ( is_file( 'includes/plugin_language/custom/' . basename( $language['language_code'] ) . '.php' ) ) {
		include( 'includes/plugin_language/custom/' . basename( $language['language_code'] ) . '.php' );
	} else if ( is_file( 'includes/plugin_language/labels/' . basename( $language['language_code'] ) . '.php' ) ) {
		include( 'includes/plugin_language/labels/' . basename( $language['language_code'] ) . '.php' );
	}
	$file_system_labels = isset( $labels ) ? $labels : array();
	if ( $old_labels !== false ) {
		$labels = $old_labels;
	}
}
if ( isset( $_REQUEST['export'] ) ) {
	header( "Pragma: public" );
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Cache-Control: private", false );
	header( "Content-Type: text/csv" );
	//todo: correct file name
	header( "Content-Disposition: attachment; filename=\"Language_Export_" . basename( strtolower( $language['language_code'] ) ) . ".csv\";" );
	header( "Content-Transfer-Encoding: binary" );
	// todo: calculate file size with ob buffering
	echo '"Word","Translation"' . "\n";
	while ( $translation = mysqli_fetch_assoc( $translations ) ) {
		if ( empty( $translation['translation'] ) && isset( $file_system_labels[ $translation['word'] ] ) ) {
			$translation['translation'] = $file_system_labels[ $translation['word'] ];
		}
		echo '"' . str_replace( '"', '""', $translation['word'] ) . '",';
		echo '"' . str_replace( '"', '""', strlen( $translation['translation'] ) ? $translation['translation'] : $translation['word'] ) . '"';
		echo "\n";
	}
	exit;
}

if ( isset( $_REQUEST['check_duplicates'] ) ) {

	$heading = array(
		'title' => _l( 'Translation Errors for: %s', htmlspecialchars( $language['language_name'] ? $language['language_name'] : _l( 'New' ) ) ),
		'type'  => 'h2',
		'main'  => true,
	);
	print_heading( $heading );

	?>


	<form action="" method="post">

		<input type="hidden" name="_process" value="remove_duplicates">

		<?php
		module_form::print_form_auth();
		?>

		<style type="text/css">
			.edit_translation {
				border: 1px solid #EFEFEF;
				padding: 2px;
				min-width: 50px;
				display: inline-block;
				cursor: pointer;
			}

			.transaction_success {
				border: 1px solid #00FF00;
			}

			.edit_translation_txt {
				width: 90%;
			}
		</style>

		<p><?php _e( 'Below are a list of words that might be incorrect. You can remove these from here. Make a translation backup first.' ); ?></p>

		<table class="tableclass tableclass_rows">
			<thead>
			<tr>
				<th>
					<?php echo _l( 'English Word' ); ?>
				</th>
				<th>
					<?php echo _l( 'Translation' ); ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php
			$duplicate_word_ids = array();
			while ( $translation = mysqli_fetch_assoc( $translations ) ) {
				if ( empty( $translation['translation'] ) && isset( $file_system_labels[ $translation['word'] ] ) ) {
					$translation['translation'] = $file_system_labels[ $translation['word'] ];
				}
				if ( strlen( $translation['translation'] ) && $translation['translation'] != $translation['word'] && isset( $labels[ $translation['translation'] ] ) ) {
					$sql = "SELECT * FROM `" . _DB_PREFIX . "language_word` WHERE `word` = '" . db_escape( $translation['translation'] ) . "'";
					$res = qa( $sql );
					if ( count( $res ) ) {
						//echo $sql.'<br>';
					}
					foreach ( $res as $r ) {
						$duplicate_word_ids[ $r['language_word_id'] ] = $translation['translation'];
					}
				}
				if ( module_language::ignore_word( $translation['word'] ) ) {
					$duplicate_word_ids[ $translation['language_word_id'] ] = $translation['translation'];
				}
			}
			foreach ( $duplicate_word_ids as $duplicate_word_id => $tf ) {
				$sql         = "SELECT * FROM `" . _DB_PREFIX . "language_word` WHERE `language_word_id` = " . (int) $duplicate_word_id;
				$translation = qa1( $sql );
				?>
				<tr>
					<td>

						<?php echo htmlspecialchars( $translation['word'] ); ?>
					</td>
					<td>
						<?php echo htmlspecialchars( $tf ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<input type="hidden" name="duplicate_ids" value="<?php echo json_encode( array_keys( $duplicate_word_ids ) ); ?>">
		<?php
		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'delete_button',
					'name'  => 'delete_duplicates',
					'value' => _l( 'Remove These Incorrect Words' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'onclick' => "window.location.href='" . str_replace( 'check_duplicates', '', htmlspecialchars( $_SERVER['REQUEST_URI'] ) ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
		?>
	</form>
	<?php

} else {

	$heading = array(
		'title'  => _l( 'Translation for: %s', htmlspecialchars( $language['language_name'] ? $language['language_name'] : _l( 'New' ) ) ),
		'type'   => 'h2',
		'main'   => true,
		'button' => array(
			array(
				'url'   => htmlspecialchars( $_SERVER['REQUEST_URI'] ) . ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false ? '?' : '&' ) . 'export=true',
				'title' => 'Export CSV',
			),
		),
	);
	if ( class_exists( 'module_import_export', false ) ) {
		$heading['button'][] = array(
			'url'   => module_import_export::import_link(
				array(
					'callback'   => 'module_language::handle_import',
					'name'       => _l( 'Language' ),
					'return_url' => $_SERVER['REQUEST_URI'],
					'options'    => array(
						'new_words' => array(
							'label'        => _l( 'New Words' ),
							'form_element' => array(
								'name'    => 'new_words',
								'type'    => 'select',
								'blank'   => false,
								'value'   => 'ignore',
								'options' => array(
									'add_new' => _l( 'Add missing words from CSV into Database' ),
									'ignore'  => _l( 'Skip missing words, only add those already in Database' )
								),
							),
						),
						array(
							'label'        => '',
							'form_element' => array(
								'name'  => 'language_id',
								'type'  => 'hidden',
								'value' => $language_id,
							),
						)
					),
					'fields'     => array(
						'Word'        => 'word',
						'Translation' => 'translation',
					),
				)
			),
			'title' => 'Import CSV',
		);
	}
	print_heading( $heading );


	?>


	<form action="" method="post">

		<input type="hidden" name="_process" value="save_language_translation">

		<?php
		module_form::print_form_auth();
		module_form::prevent_exit( array(
				'valid_exits' => array(
					// selectors for the valid ways to exit this form.
					'.submit_button',
				)
			)
		);


		$fieldset_data = array(
			'elements' => array(
				array(
					'title' => _l( 'Language Code' ),
					'field' => array(
						'name'  => 'language_code',
						'value' => isset( $language['language_code'] ) ? $language['language_code'] : '',
						'type'  => 'text',
						'help'  => 'Example: es or de',
						'size'  => 5,
					)
				),
				array(
					'title' => _l( 'Language Name' ),
					'field' => array(
						'name'  => 'language_name',
						'value' => isset( $language['language_name'] ) ? $language['language_name'] : '',
						'type'  => 'text',
						'help'  => 'Example: Spanish or German;',
					)
				),
			),
		);

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

		?>

		<style type="text/css">
			.edit_translation {
				border: 1px solid #EFEFEF;
				padding: 2px;
				min-width: 50px;
				display: inline-block;
				cursor: pointer;
			}

			.transaction_success {
				border: 1px solid #00FF00;
			}

			.edit_translation_txt {
				width: 90%;
			}

			#language_table th,
			#language_table td {
				word-wrap: break-word;
				white-space: normal;
			}
		</style>

		<p><?php _e( 'If you find a word that does not translate correctly please report it to support.' ); ?></p>
		<p><?php _e( '%s means "string" and is replaced with another value when displayed on the screen.', '%s' ); ?></p>
		<p><?php _e( 'If you have created or improved on a translation please click Export above and share it with support.' ); ?></p>

		<table class="tableclass tableclass_rows tbl_fixed" id="language_table">
			<thead>
			<tr>
				<th style="width:50%">
					<?php echo _l( 'English Word' ); ?>
				</th>
				<th style="width:50%">
					<?php echo _l( 'Translation' ); ?>
				</th>
			</tr>
			</thead>
			<tbody>
			<?php
			while ( $translation = mysqli_fetch_assoc( $translations ) ) {
				if ( empty( $translation['translation'] ) && isset( $file_system_labels[ $translation['word'] ] ) ) {
					$translation['translation'] = $file_system_labels[ $translation['word'] ];
				}
				?>
				<tr>
					<th>
						<?php echo htmlspecialchars( $translation['word'] ); ?>
					</th>
					<td>
						<?php if ( strlen( $translation['translation'] ) && $translation['translation'] != $translation['word'] ) { ?>
							<span data-name="translation[<?php echo (int) $translation['language_word_id']; ?>]"
							      data-value="<?php echo htmlspecialchars( strlen( $translation['translation'] ) ? $translation['translation'] : $translation['word'], ENT_QUOTES ); ?>"
							      class="edit_translation transaction_success"><?php echo htmlspecialchars( strlen( $translation['translation'] ) ? $translation['translation'] : $translation['word'] ); ?></span>
						<?php } else { ?>
							<span data-name="translation[<?php echo (int) $translation['language_word_id']; ?>]"
							      data-value="<?php echo htmlspecialchars( strlen( $translation['translation'] ) ? $translation['translation'] : $translation['word'], ENT_QUOTES ); ?>"
							      class="edit_translation"><?php echo htmlspecialchars( strlen( $translation['translation'] ) ? $translation['translation'] : $translation['word'] ); ?></span>
						<?php } ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<?php
		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'save',
					'value' => _l( 'Save' ),
				),
				array(
					'type'  => 'submit',
					'name'  => 'check_duplicates',
					'value' => _l( 'Check Errors' ),
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
		?>
	</form>

	<script type="text/javascript">
      $(function () {
          $('.edit_translation').click(function () {
              var value = $(this).data('value');
              if (value.length > 40) {
                  var txt = $('<textarea name="" rows="3" class="edit_translation_txt"></textarea>');
              } else {
                  var txt = $('<input type="text" name="" value="" class="edit_translation_txt"/>');
              }
              var name = $(this).data('name');
              var td = $(this).parent('td');
              td.html('');
              td.append(txt);
              //alert(value);
              txt.val(value);
              txt.attr('name', name);
              txt[0].focus();
              txt[0].select();
          });
      });
	</script>

<?php }