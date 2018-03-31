<?php
if ( ! module_data::can_i( 'edit', _MODULE_DATA_NAME ) ) {
	die( "access denied" );
}


$data_type_id        = (int) $_REQUEST['data_type_id'];
$data_field_group_id = (int) $_REQUEST['data_field_group_id'];
$data_field_id       = $_REQUEST['data_field_id'];

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

<form action="" method="post">
	<input type="hidden" name="_process" value="save_data_field"/>
	<input type="hidden" name="_redirect" value=""/>
	<input type="hidden" name="data_field_id" value="<?php echo $data_field_id; ?>"/>
	<input type="hidden" name="data_field_group_id" value="<?php echo $data_field_group_id; ?>"/>
	<input type="hidden" name="data_type_id" value="<?php echo $data_type_id; ?>"/>
	<table border="0" cellspacing="0" cellpadding="5" class="tableclass tableclass_full tableclass_form">
		<tr>
			<th class="width2">
				<?php echo _l( 'Title' ); ?>
			</th>
			<td>
				<input type="text"
				       name="title" id="title"
				       value="<?php echo ( isset( $data_field['title'] ) ) ? htmlspecialchars( $data_field['title'] ) : ''; ?>"
				/>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Position' ); ?>
			</th>
			<td>
				<input type="text"
				       name="order" id="order"
				       size="4"
				       value="<?php echo ( isset( $data_field['order'] ) ) ? htmlspecialchars( $data_field['order'] ) : ''; ?>"
				/>
				(1, 2, 3, etc.. - go to visual editor for drag &amp; drop re-arrange)
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Type' ); ?>
			</th>
			<td>
				<?php echo print_select_box( array(
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
				), 'field_type', isset( $data_field['field_type'] ) ? $data_field['field_type'] : '', '', ' - select - ' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Type Data' ); ?>
			</th>
			<td>
			<textarea
				name="field_data" id="field_data"
				rows="3" cols="40"
			><?php echo ( isset( $data_field['field_data'] ) ) ? htmlspecialchars( $data_field['field_data'] ) : ''; ?></textarea>
				<?php _h( 'This area is used to store settings for this particular field element. Here are some examples of the data that can be put in here:<br><Br>For select/radio elements: <br><strong>attributes=One|Two|Three|Other</strong><br>will be used in the drop down or radio listing. \'Other\' will prompt for a text input.<br><br>For other input elements:<br><strong>width=110<br>height=23</strong><br>will be used to control the width/height of the actual input box (use visual editor to set this easier).<br><br>For all elements:<br><strong>style="clear:left;"</strong><br>add any style to the surrounding element box, eg: clear:left will place this item on a new line.' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Default' ); ?>
			</th>
			<td>
				<input type="text"
				       name="default" id="default"
				       size="15"
				       value="<?php echo ( isset( $data_field['default'] ) ) ? htmlspecialchars( $data_field['default'] ) : ''; ?>"
				/>
				<?php _h( 'The default value for this element' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Multiple' ); ?>
			</th>
			<td>
				<input type="checkbox"
				       name="multiple" id="multiple"
				       value="1"
					<?php echo ( isset( $data_field['multiple'] ) && $data_field['multiple'] ) ? ' checked' : ''; ?>>
				Yes
				<?php _h( 'Allow multiple inputs with a little + and - button. Only available for text, textarea, select and radio fields' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Searchable' ); ?>
			</th>
			<td>
				<input type="checkbox"
				       name="searchable" id="searchable"
				       value="1"
					<?php echo ( isset( $data_field['searchable'] ) && $data_field['searchable'] ) ? ' checked' : 1; ?>>
				Yes
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Required' ); ?>
			</th>
			<td>
				<input type="checkbox"
				       name="required" id="required"
				       value="1"
					<?php echo ( isset( $data_field['required'] ) && $data_field['required'] ) ? ' checked' : ''; ?>>
				Yes
			</td>
		</tr>
		<!--<tr>
		<th>
			<?php /*echo _l('Reportable'); */ ?>
		</th>
		<td>
			<input type="checkbox" 
			name="reportable" id="reportable" 
			value="1" 
			<?php /*echo (isset($data_field['reportable']) && $data_field['reportable']) ? ' checked':''; */ ?>>
			Yes
		</td>
	</tr>-->
		<tr>
			<th>
				<?php echo _l( 'Show in main listing' ); ?>
			</th>
			<td>
				<input type="checkbox"
				       name="show_list" id="show_list"
				       value="1"
					<?php echo ( isset( $data_field['show_list'] ) && $data_field['show_list'] ) ? ' checked' : 1; ?>>
				Yes
			</td>
		</tr>
		<?php if ( (int) $data_field_id ) { ?>
			<tr>
				<th>
					<?php echo _l( 'Extra Lookup' ); ?>
				</th>
				<td>
					<code>data|<?php echo (int) $data_field_id; ?>|<?php echo $data_field['data_type_id']; ?></code>
					<?php _h( 'You can create an extra field (e.g. on a Customer page) and then in Settings > Extra Fields you can change its type to "Ajax Lookup". In the Ajax Lookup field place this string and you will be able to search/select items from here.' ); ?>
				</td>
			</tr>
		<?php } ?>
		<!--<tr>
		<th>
			<?php /*echo _l('Size'); */ ?>
		</th>
		<td>
			<input type="text" 
				name="width" id="width" 
				size="2"
				value="<?php /*echo (isset($data_field['width']))?htmlspecialchars($data_field['width']):''; */ ?>"
			/>
			x
			<input type="text" 
				name="height" id="height" 
				size="2"
				value="<?php /*echo (isset($data_field['height']))?htmlspecialchars($data_field['height']):''; */ ?>"
			/>
		</td>
	</tr>-->
		<!-- <tr>
		<th>
			<?php echo _l( 'Display Group If' ); ?>
		</th>
		<td>
			<input type="text" 
				name="display_group_if" id="display_group_if" 
				size="15"
				value="<?php echo ( isset( $data_field['display_group_if'] ) ) ? htmlspecialchars( $data_field['display_group_if'] ) : ''; ?>" 
			/>
		</td>
	</tr> -->
	</table>
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

</form>	
