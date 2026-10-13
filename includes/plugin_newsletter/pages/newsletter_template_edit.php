<?php
$module->page_title = _l( 'Template Edit' );
//print_heading('Newsletter Editor');

$newsletter_template_id = isset( $_REQUEST['newsletter_template_id'] ) ? (int) $_REQUEST['newsletter_template_id'] : false;
$newsletter_template    = module_newsletter::get_newsletter_template( $newsletter_template_id );

?>

<?php module_form::print_form_open_tag( array( 'action' => '' ) ); ?>
	<input type="hidden" name="_process" value="save_newsletter_template">
	<?php
	module_form::set_required( array(
		'fields' => array(
			'newsletter_template_name' => 'Name',
		),
	) );
	module_form::prevent_exit( array(
		'valid_exits' => array(
			'.submit_button',
			'.valid_exit',
		),
	) );

	$name_element = array(
		'title'           => _l( 'Name' ),
		'row_title_class' => 'width1',
	);

	if ( (int) $newsletter_template_id > 0 && $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) ) {
		$name_element['fields'] = array(
			'<input type="hidden" name="newsletter_template_name" value="' . htmlspecialchars( $newsletter_template['newsletter_template_name'] ) . '">',
			htmlspecialchars( $newsletter_template['newsletter_template_name'] ),
		);
	} else {
		$name_element['field'] = array(
			'type'  => 'text',
			'name'  => 'newsletter_template_name',
			'value' => $newsletter_template['newsletter_template_name'],
		);
	}

	$details_elements = array( 'name' => $name_element );

	if ( (int) $newsletter_template_id > 0 ) {
		if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) ) {
			$details_elements['template_folder'] = array(
				'title'  => _l( 'Template Folder' ),
				'fields' => array( htmlspecialchars( $newsletter_template['directory'] ) . ' ' . _l( '(you can create advanced template features from this folder)' ) ),
			);
		}

		ob_start();
		if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'preview.jpg' ) ) {
			echo '<img src="' . full_link( $newsletter_template['directory'] . 'preview.jpg' ) . '">';
		} else {
			module_file::display_files( array(
				'owner_table' => 'newsletter_template',
				'owner_id'    => $newsletter_template_id,
				'layout'      => 'gallery',
			) );
		}
		$preview_html = ob_get_clean();

		$details_elements['preview_image'] = array(
			'title'  => _l( 'Preview Image' ),
			'fields' => array( $preview_html ),
		);

		if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'render.php' ) ) {
			$description = 'N/A';
			$contents    = file_get_contents( $newsletter_template['directory'] . 'render.php' );
			if ( preg_match( '#Description:(.*)#i', $contents, $matches ) ) {
				$description = htmlspecialchars( trim( $matches[1] ) );
			}
			$details_elements['custom_rendering'] = array(
				'title'  => _l( 'Custom Rendering' ),
				'fields' => array(
					_l( 'Controlled by file: <em>%s</em> %s<br/>Description: <em>%s</em> ', $newsletter_template['directory'] . 'render.php', _hr( 'You can expand on the default functionality of the newsletter rendering system by editing this custom PHP file. You can include custom PHP calls to databases to output your own HTML for sending. Pretty nifty!' ), $description ),
				),
			);
		}
	}

	$details_fieldset = array(
		'heading'  => array( 'type' => 'h3', 'title' => _l( 'Newsletter Template Details' ) ),
		'class'    => 'tableclass tableclass_form',
		'elements' => $details_elements,
	);
	echo module_form::generate_fieldset( $details_fieldset );
	?>
	<?php
	$body_warnings = '';
	if ( strpos( $newsletter_template['body'], '{BODY}' ) === false ) {
		$body_warnings .= _l( 'Please make sure you include a single {BODY} tag in the template.' ) . ' ';
	}
	if ( strpos( $newsletter_template['body'], '{UNSUBSCRIBE}' ) === false ) {
		$body_warnings .= _l( 'It is probably best to include the {UNSUBSCRIBE} link in the template. Please add it so users can unsubscribe easily.' );
	}

	$body_fieldset = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => _l( 'Template HTML Code:' ),
			'help'  => 'The outer HTML code for this template. That is, all the parts of the template that will not change each time you send a newsletter (header, menu, footer). You can include the dynamic fields which will be replaced each time the newsletter is sent.<br>When you insert images or links please use full addresses (including http:// at the start).',
		),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'body' => array(
				'title'  => '',
				'fields' => array(
					$body_warnings,
					array(
						'type'  => 'textarea',
						'name'  => 'body',
						'id'    => 'template_body',
						'value' => $newsletter_template['body'],
						'style' => 'width:96%; height:500px; margin:5px;',
					),
				),
				'row_title_class' => '',
			),
		),
	);

	$default_inner_fieldset = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => _l( 'Template Default Content:' ),
			'help'  => 'When this template is selected, the below default content will appear in the newsletter for editing.',
		),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'default_inner' => array(
				'title'  => '',
				'fields' => array(
					array(
						'type'  => 'textarea',
						'name'  => 'default_inner',
						'id'    => 'default_inner',
						'value' => isset( $newsletter_template['default_inner'] ) ? $newsletter_template['default_inner'] : '',
						'style' => 'width:96%; height:300px; margin:5px;',
					),
				),
				'row_title_class' => '',
			),
		),
	);

	ob_start();
	?>
	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tbl_fixed">
		<tbody>
		<tr>
			<td class="width1">
				<a href="#" onmousedown="$('#template_body').val($('#template_body').val() + '{BODY}'); return false;" onclick="return false;">{BODY}</a>
			</td>
			<td><?php _e( 'content from "create newsletter"' ); ?></td>
		</tr>
		<tr>
			<td>
				<a href="#" onmousedown="$('#template_body').val($('#template_body').val() + '&lt;?php ?&gt;'); return false;" onclick="return false;">&lt;?php ?&gt;</a>
			</td>
			<td><?php _e( 'code evaluated at send time' ); ?></td>
		</tr>
		<?php
		$fields = module_newsletter::get_replace_fields();
		foreach ( $fields as $key => $val ) {
			?>
			<tr>
				<td>
					<a href="#" onmousedown="$('#template_body').val($('#template_body').val() + <?php echo json_encode( '{' . $key . '}' ); ?>); return false;" onclick="return false;"><?php echo '{' . htmlspecialchars( $key ) . '}'; ?></a>
				</td>
				<td><?php echo htmlspecialchars( $val ); ?></td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td colspan="2" align="center"><?php _e( 'You will have more custom fields here<br/> once you pick your recipients.' ); ?></td>
		</tr>
		</tbody>
	</table>
	<?php
	$dynamic_table_html = ob_get_clean();

	$dynamic_fieldset = array(
		'heading'         => array( 'type' => 'h3', 'title' => _l( 'Dynamic Fields:' ) ),
		'class'           => 'tableclass tableclass_form tbl_fixed',
		'elements_before' => $dynamic_table_html,
		'elements'        => array(),
	);
	?>
	<table width="100%" cellpadding="5">
		<tbody>
		<tr>
			<td valign="top" width="70%">
				<?php echo module_form::generate_fieldset( $body_fieldset ); ?>
				<?php echo module_form::generate_fieldset( $default_inner_fieldset ); ?>
			</td>
			<td valign="top">
				<?php echo module_form::generate_fieldset( $dynamic_fieldset ); ?>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<?php
				$form_actions_elements = array(
					array( 'type' => 'save_button', 'name' => 'butt_save', 'id' => 'butt_save', 'value' => _l( 'Save' ) ),
					array(
						'type'    => 'button',
						'name'    => 'cancel',
						'value'   => _l( 'Cancel' ),
						'class'   => 'submit_button',
						'onclick' => "window.location.href='" . module_newsletter::link_list( false ) . "';",
					),
				);
				if ( (int) $newsletter_template_id > 0 ) {
					$form_actions_elements[] = array( 'type' => 'delete_button', 'name' => 'butt_del', 'id' => 'butt_del', 'value' => _l( 'Delete' ) );
				}
				$form_actions = array(
					'class'    => 'action_bar action_bar_center',
					'elements' => $form_actions_elements,
				);
				echo module_form::generate_form_actions( $form_actions );
				?>
			</td>
		</tr>
		</tbody>
	</table>
</form>

