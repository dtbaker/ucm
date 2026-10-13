<?php
if ( ! module_data::can_i( 'edit', _MODULE_DATA_NAME ) ) {
	die( "access denied" );
}


// Reaching this page without all three is normal - the menu links here with only the
// type - and reading them unguarded was a warning on every one of those visits.
$data_type_id        = isset( $_REQUEST['data_type_id'] ) ? (int) $_REQUEST['data_type_id'] : 0;
$data_field_group_id = isset( $_REQUEST['data_field_group_id'] ) ? (int) $_REQUEST['data_field_group_id'] : 0;
// Not cast: "new" opens the create form.
$data_field_id       = isset( $_REQUEST['data_field_id'] ) ? $_REQUEST['data_field_id'] : '';

if ( $data_type_id ) {
	$data_type = $module->get_data_type( $data_type_id );
} else {
	die( "No type defined" );
}

if ( $data_field_group_id ) {
	$data_field_group = $module->get_data_field_group( $data_field_group_id );
} else {
	die( "No type defined" );
}


if ( $data_field_id && $data_field_id != 'new' ) {
	$data_field = $module->get_data_field( $data_field_id );
} else {
	$data_field = array(
		'field_type' => 'text',
		'show_list'  => 1,
	);
	if ( isset( $_REQUEST['order'] ) ) {
		$data_field['order'] = $_REQUEST['order'];
	}
}


print_heading( array(
	'main'  => true,
	'type'  => 'h2',
	'title' => 'Data Field Settings',
) );
?>

<?php module_form::print_form_open_tag( array( 'action' => '' ) ); ?>
	<input type="hidden" name="_process" value="save_data_field"/>
	<input type="hidden" name="_redirect" value=""/>
	<input type="hidden" name="data_field_id" value="<?php echo htmlspecialchars( (string) $data_field_id, ENT_QUOTES, 'UTF-8' ); ?>"/>
	<input type="hidden" name="data_field_group_id" value="<?php echo (int) $data_field_group_id; ?>"/>
	<input type="hidden" name="data_type_id" value="<?php echo (int) $data_type_id; ?>"/>
	<?php
	$field_types = array(
		'text'              => 'Text Box',
		'textarea'          => 'Multiline Text Area',
		'select'            => 'Select Box (set attributes= below)',
		'ajax'              => 'Ajax Lookup (set source= below)',
		'radio'             => 'Radio Buttons (set attributes= below)',
		'currency'          => 'Currency',
		'url'               => 'URL',
		'number'            => 'Number',
		'date'              => 'Date Select',
		'time'              => 'Time Select',
		'checkbox'          => 'Checkbox',
		'checkbox_list'     => 'Checkbox List (set attributes= below)',
		'file'              => 'File',
		'wysiwyg'           => 'WYSIWYG Rich Text',
		'encrypted'         => 'Encrypted',
		'auto_id'           => '(automatic) Unique ID Number',
		'created_date_time' => '(info) Created Date/Time',
		'created_date'      => '(info) Created Date',
		'created_time'      => '(info) Created Time',
		'created_by'        => '(info) Created By Username',
		'updated_date_time' => '(info) Updated Date/Time',
		'updated_date'      => '(info) Updated Date',
		'updated_time'      => '(info) Updated Time',
		'updated_by'        => '(info) Updated By Username',
	);

	// A closure renders _h() in place, which prints rather than returns.
	$help = static function ( $text ) {
		return static function () use ( $text ) {
			_h( $text );
		};
	};

	$elements = array(
		'title'      => array(
			'title' => _l( 'Title' ),
			'field' => array(
				'type'  => 'text',
				'name'  => 'title',
				'value' => isset( $data_field['title'] ) ? $data_field['title'] : '',
			),
		),
		'order'      => array(
			'title'  => _l( 'Position' ),
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'order',
					'size'  => 4,
					'value' => isset( $data_field['order'] ) ? $data_field['order'] : '',
				),
				'(1, 2, 3, etc.. - go to visual editor for drag &amp; drop re-arrange)',
			),
		),
		'field_type' => array(
			'title' => _l( 'Type' ),
			'field' => array(
				'type'    => 'select',
				'name'    => 'field_type',
				'options' => $field_types,
				'blank'   => ' - select - ',
				'value'   => isset( $data_field['field_type'] ) ? $data_field['field_type'] : '',
			),
		),
		'field_data' => array(
			'title'  => _l( 'Type Data' ),
			'fields' => array(
				array(
					'type'  => 'textarea',
					'name'  => 'field_data',
					'rows'  => 3,
					'cols'  => 40,
					'value' => isset( $data_field['field_data'] ) ? $data_field['field_data'] : '',
				),
				$help( 'This area is used to store settings for this particular field element. Here are some examples of the data that can be put in here:<br><Br>For select/radio elements: <br><strong>attributes=One|Two|Three|Other</strong><br>will be used in the drop down or radio listing. \'Other\' will prompt for a text input.<br><br>For other input elements:<br><strong>width=110<br>height=23</strong><br>will be used to control the width/height of the actual input box (use visual editor to set this easier).<br><br>For all elements:<br><strong>style="clear:left;"</strong><br>add any style to the surrounding element box, eg: clear:left will place this item on a new line.' ),
			),
		),
		'default'    => array(
			'title'  => _l( 'Default' ),
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'default',
					'size'  => 15,
					'value' => isset( $data_field['default'] ) ? $data_field['default'] : '',
				),
				$help( 'The default value for this element' ),
			),
		),
		'multiple'   => array(
			'title'  => _l( 'Multiple' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'multiple',
					'value' => ! empty( $data_field['multiple'] ),
				),
				_l( 'Yes' ),
				$help( 'Allow multiple inputs with a little + and - button. Only available for text, textarea, select and radio fields' ),
			),
		),
		'searchable' => array(
			'title'  => _l( 'Searchable' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'searchable',
					// Was `? ' checked' : 1`, which printed a stray "1" attribute rather
					// than ticking the box. Unticked is what it rendered, so unticked is
					// what it stays - changing it here would alter what a new field saves.
					'value' => ! empty( $data_field['searchable'] ),
				),
				_l( 'Yes' ),
			),
		),
		'required'   => array(
			'title'  => _l( 'Required' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'required',
					'value' => ! empty( $data_field['required'] ),
				),
				_l( 'Yes' ),
			),
		),
		'show_list'  => array(
			'title'  => _l( 'Show in main listing' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'show_list',
					// Same stray "1" as searchable above. A new field arrives with
					// show_list already set to 1, so this still ticks for a new one.
					'value' => ! empty( $data_field['show_list'] ),
				),
				_l( 'Yes' ),
			),
		),
	);

	if ( (int) $data_field_id ) {
		$elements['extra_lookup'] = array(
			'title'  => _l( 'Extra Lookup' ),
			'fields' => array(
				'<code>data|' . (int) $data_field_id . '|'
				. htmlspecialchars( (string) $data_field['data_type_id'] ) . '</code>',
				$help( 'You can create an extra field (e.g. on a Customer page) and then in Settings > Extra Fields you can change its type to "Ajax Lookup". In the Ajax Lookup field place this string and you will be able to search/select items from here.' ),
			),
		);
	}

	echo module_form::generate_fieldset( array(
		'class'    => 'tableclass tableclass_full tableclass_form',
		'elements' => $elements,
	) );
	?>
	<?php
	$form_actions = array(
		'class'    => 'action_bar action_bar_center action_bar_single',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save Settings' ),
			),
			array(
				'hidden'  => ! ( $data_field_id && $data_field_id != 'new' ),
				'type'    => 'delete_button',
				'name'    => 'butt_dell',
				'value'   => _l( 'Delete' ),
				'onclick' => "return confirm('Really delete?');",
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . $module->link( "", array( 'data_type_id'        => $data_type_id,
				                                                                  'data_field_group_id' => $data_field_group_id
					) ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );
	?>

<?php module_form::print_form_close_tag();
