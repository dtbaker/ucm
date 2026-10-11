<?php
$module->page_title = _l( 'Editor' );
//print_heading('Newsletter Editor');

$newsletter_id                 = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
$current_newsletter_content_id = isset( $_REQUEST['newsletter_content_id'] ) && (int) $_REQUEST['newsletter_content_id'] > 0 ? (int) $_REQUEST['newsletter_content_id'] : false;

$newsletter          = module_newsletter::get_newsletter( $newsletter_id );
$newsletter_template = module_newsletter::get_newsletter_template( $newsletter['newsletter_template_id'] );
// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $newsletter_id > 0 && $newsletter['newsletter_id'] == $newsletter_id ) {
		module_security::check_page( array(
			'category'  => 'Newsletter',
			'page_name' => 'Newsletters',
			'module'    => 'newsletter',
			'feature'   => 'edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Newsletter',
			'page_name' => 'Newsletters',
			'module'    => 'newsletter',
			'feature'   => 'create',
		) );
	}
}

$templates = module_newsletter::get_templates();

//$input_method = 'wysiwyg';
$default_content = isset( $newsletter_template['default_inner'] ) ? $newsletter_template['default_inner'] : '';
if ( ! $default_content && $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'inside.html' ) ) {
	ob_start();
	include( $newsletter_template['directory'] . 'inside.html' );
	$default_content = ob_get_clean();
}
if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'settings.php' ) ) {
	include( $newsletter_template['directory'] . 'settings.php' );
}

$sends         = $newsletter['sends'];
$sends_warning = array();
foreach ( $sends as $send ) {
	if ( $send['status'] != _NEWSLETTER_STATUS_NEW ) {
		// this newsletter has been sent before, or a pending send has been done before.
		$sends_warning[] = $send;
	}
}
if ( count( $sends_warning ) ) {
	$sends_links = '<ul>';
	foreach ( $sends_warning as $send_warning ) {
		$sends_links .= '<li><a href="' . module_newsletter::link_statistics( $send_warning['newsletter_id'], $send_warning['send_id'] ) . '">';
		$sends_links .= print_date( $send_warning['start_time'], true );
		$sends_links .= ' - ';
		$sends_links .= _l( 'View Statistics' );
		$sends_links .= '</a></li> ';
	}
	$sends_links .= '</ul>';
	?>

	<div class="message_box">
		<h4><?php _e( 'Newsletter Sent Already' ); ?></h4>
		<?php echo _l( '<p>This newsletter has already been sent out <strong>%s</strong> times:</p>%s<p>Please press the <strong>duplicate</strong> button at the bottom to create a new newsletter based on this newsletter. You can also re-send this newsletter to a new group of people below if you want.</p>', count( $sends_warning ), $sends_links ); ?>
	</div>
	<?php

}
?>

<form action="" method="post" id="newsletter_form">
	<?php module_form::print_form_auth(); ?>
	<input type="hidden" name="_process" value="save_newsletter">

	<?php
	module_form::set_required( array(
			'fields' => array(
				'subject'      => 'Subject',
				'from_name'    => 'Sender Name',
				'from_email'   => 'Sender Email',
				'bounce_email' => 'Bounce Email',
				'to_name'      => 'To Name',
			)
		)
	);
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.valid_exit',
			)
		)
	);
	?>

	<?php
	$email_details_fieldset = array(
		'heading'  => array( 'type' => 'h3', 'title' => _l( 'Email Details' ) ),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'subject'     => array(
				'title'           => _l( 'Subject' ),
				'row_title_class' => 'width1',
				'field'           => array(
					'type'   => 'text',
					'name'   => 'subject',
					'id'     => 'subject',
					'value'  => $newsletter['subject'],
					'style'  => 'width:250px;',
				),
			),
			'from_name'   => array(
				'title' => _l( 'Sender Name' ),
				'fields' => array(
					array(
						'type' => 'text',
						'name' => 'from_name',
						'id' => 'from_name',
						'value' => $newsletter['from_name'],
						'style' => 'width:250px;'
					),
					' ',
					function () {
						_h( 'This is the name this newsletter will be sent "from". Giving this a name your customers will recognise is probably best.' );
					},
				),
			),
			'from_email'  => array(
				'title' => _l( 'Sender Email' ),
				'fields' => array(
					array( 'type' => 'text', 'name' => 'from_email', 'id' => 'from_email', 'value' => $newsletter['from_email'], 'style' => 'width:250px;' ),
					' ',
					function () {
						 _h( 'This is the email address the newsletter will be sent "from". We HIGHLY recommend this email address is setup or linked to your hosting account to help with spam issues (or google about SPF records - can be complex but worth it)' );
						},
				),
			),
			'bounce_email' => array(
				'title' => _l( 'Bounce Email' ),
				'fields' => array(
					array( 'type' => 'text', 'name' => 'bounce_email', 'id' => 'bounce_email', 'value' => $newsletter['bounce_email'], 'style' => 'width:250px;' ),
					' ',
					function () {
						_h( 'This is the email address any bounces are set to. We HIGHLY recommend you setup a new empty email address (eg: bounce@yourwebsite.com) for this. In the newsletter settings you can specify the bounce address settings so the system can automatically track bounces.' );
					},
				),
			),
			'to_name'     => array(
				'title' => _l( 'To Name' ),
				'fields' => array(
					array( 'type' => 'text', 'name' => 'to_name', 'value' => $newsletter['to_name'], 'style' => 'width:250px;' ),
					' ',
					function () {
						_h( 'Format for the recipients name. This is more of an advanced thing, the default here should be fine for everyone, but you could get fancy and use {COMPANY_NAME} if you like.' );
					},
				),
			),
		),
	);

	ob_start();
	?>
	<input type="hidden" name="newsletter_template_id" id="newsletter_template_id" value="<?php echo $newsletter['newsletter_template_id']; ?>">
	<?php
	foreach ( $templates as $template_item ) {
		$template_item = module_newsletter::get_newsletter_template( $template_item['newsletter_template_id'] );
		?>
		<div class="template<?php echo ( $newsletter['newsletter_template_id'] == $template_item['newsletter_template_id'] ) ? ' selected' : ''; ?>"
		     onclick="$('#newsletter_template_id').val(<?php echo $template_item['newsletter_template_id']; ?>); $('.template').removeClass('selected'); $(this).addClass('selected'); $('#butt_save').click();"
		     style="cursor: pointer;">
			<div style="height:94px; overflow: hidden;">
				<?php
				if ( $template_item['directory'] && is_dir( $template_item['directory'] ) && is_file( $template_item['directory'] . 'preview.jpg' ) ) {
					?><img src="<?php echo full_link( $template_item['directory'] . 'preview.jpg' ); ?>"><?php
				} else {
					module_file::display_files( array(
						'owner_table' => 'newsletter_template',
						'owner_id'    => $template_item['newsletter_template_id'],
						'layout'      => 'preview',
					) );
				}
				?>
			</div>
			<div style="clear:both; padding:5px;"><?php echo $template_item['newsletter_template_name']; ?></div>
		</div>
	<?php }
	$choose_template_html = ob_get_clean();

	$choose_template_fieldset = array(
		'heading'  => array( 'type' => 'h3', 'title' => _l( 'Choose Template' ) ),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'templates' => array(
				'title'  => '',
				'fields' => array( '<div align="center" style="text-align: center">' . $choose_template_html . '</div>' ),
				'row_title_class' => '',
			),
		),
	);

	$quick_preview_fieldset = array(
		'heading'  => array( 'type' => 'h3', 'title' => _l( 'Quick Preview' ) ),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'preview' => array(
				'title'  => '',
				'fields' => array(
					'<p style="line-height: 24px;">',
					array( 'type' => 'text', 'name' => 'quick_email', 'value' => $newsletter['from_email'], 'size' => 35 ),
					' <br/>',
					array( 'type' => 'submit', 'name' => 'butt_preview_email', 'value' => _l( 'Email Preview' ), 'class' => 'valid_exit small_button' ),
					'<br/>',
					array( 'type' => 'submit', 'name' => 'butt_preview', 'value' => _l( 'Browser Preview' ), 'class' => 'valid_exit small_button' ),
					'</p>',
				),
				'row_title_class' => '',
			),
		),
	);
	?>
	<table width="100%" cellpadding="5">
		<tbody>
		<tr>
			<td width="450" valign="top">
				<?php echo module_form::generate_fieldset( $email_details_fieldset ); ?>
			</td>
			<td valign="top">
				<?php echo module_form::generate_fieldset( $choose_template_fieldset ); ?>
			</td>
			<td valign="top">
				<?php echo module_form::generate_fieldset( $quick_preview_fieldset ); ?>
			</td>
		</tr>
		</tbody>
	</table>


	<?php
	$help_text = addcslashes( _l( "Here you can copy and paste from Word, or simply type out your newsletter. The above template will be applied to this content. Note: if you copy and paste from another document or website, please use the 'Paste from Word' button or even better the 'Paste as Plain Text' button (otherwise your newsletter layout may break when you go to send it)." ), "'" );
	?>
	<div class="content_box_wheader">
		<?php /* <input type="radio" name="input_method" value="wizard" <?php if(!$input_method||$input_method=='wizard') echo ' checked';?>> Wizard <input type="radio" name="input_method" value="manual"<?php if($input_method=='manual') echo ' checked';?>> Manual HTML */ ?>


		<div class="newsletter_box">
			<a name="editor"></a>


			<table cellpadding="5" width="100%">
				<tr>
					<td valign="top">
						<?php
						// check if this template comes with a wizard.
						if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'wizard_ui.php' ) ) {
							include( $newsletter_template['directory'] . 'wizard_ui.php' );
						} else {
							// simple plain text box.
							// work out which content we are displaying here.
							if ( isset( $newsletter_template['wizard'] ) && $newsletter_template['wizard'] ) {
								$content = '';
								if ( $current_newsletter_content_id && isset( $newsletter['extra_content'][ $current_newsletter_content_id ] ) ) {
									$content = $newsletter['extra_content'][ $current_newsletter_content_id ]['content_full'];

									// display some extra editing bits here for the additonal entry bit:
									?>

									<?php
									$extra_details_fieldset = array(
										'heading'  => array( 'type' => 'h3', 'title' => _l( 'Extra Details' ) ),
										'class'    => 'tableclass tableclass_form',
										'elements' => array(
											'group_title' => array(
												'title'           => _l( 'Content Area Title' ),
												'row_title_class' => 'width2',
												'field'           => array(
													'type'   => 'text',
													'name'   => 'subject2',
													'value'  => $newsletter['extra_content'][ $current_newsletter_content_id ]['group_title'],
													'style'  => 'width:250px;',
												),
											),
											'group_position' => array(
												'title'           => _l( 'Content Area Position' ),
												'row_title_class' => 'width2',
												'field'           => array(
													'type'   => 'text',
													'name'   => 'subject2',
													'value'  => $newsletter['extra_content'][ $current_newsletter_content_id ]['group_title'],
													'style'  => 'width:250px;',
												),
											),
										),
									);
									echo module_form::generate_fieldset( $extra_details_fieldset );
									?>
									<?php
								} else {
									// we are editing a non existant content area
									// or we are just editing the main content area.
									$content = $newsletter['content'];
								}

							} else {
								$content = $newsletter['content'];
							}

							$newsletter_content_fieldset = array(
								'heading'  => array(
									'type'   => 'h3',
									'title'  => _l( 'Newsletter Content' ),
									'button' => array(
										'url'     => '#',
										'onclick' => "alert('$help_text'); return false;",
										'title'   => 'help',
									),
								),
								'class'    => 'tableclass tableclass_form tableclass_full',
								'elements' => array(
									'content' => array(
										'title'  => '',
										'fields' => array(
											array(
												'type'  => 'wysiwyg',
												'name'  => 'content',
												'id'    => 'newsletter_editor_content',
												'value' => $content,
											),
											trim( $default_content ) ? '<p><a href="#" onclick="set_default();return false;">' . _l( 'click here' ) . '</a> ' . _l( 'to load default content from template.' ) . '</p>' : '',
										),
										'row_title_class' => '',
									),
								),
							);
							echo module_form::generate_fieldset( $newsletter_content_fieldset );
							?>
						<?php } ?>
					</td>
					<td valign="top" width="360">
						<?php if ( isset( $newsletter_template['wizard'] ) && $newsletter_template['wizard'] ) { ?>
							<?php
							print_heading( array(
								'type'   => 'h3',
								'title'  => 'Content Areas',
								'button' => array(
									'title' => 'Add New',
									'url'   => 'asdf',
								),
							) );
							?>
							<ul>
								<li><a href="#"
								       onclick="return newsletter.edit_content(0);"><?php _e( 'Main Content' ); ?><?php if ( ! $current_newsletter_content_id ) {
											echo ' <strong>' . _l( '(Current)' ) . '</strong>';
										}; ?></a></li>
								<?php foreach ( $newsletter['extra_content'] as $extra_newsletter_content_id => $extra_newsletter_content_data ) { ?>
									<li><a href="#"
									       onclick="return newsletter.edit_content(<?php echo $extra_newsletter_content_id; ?>);"><?php _e( 'Main Content' ); ?><?php if ( $current_newsletter_content_id == $extra_newsletter_content_id ) {
												echo ' <strong>' . _l( '(Current)' ) . '</strong>';
											}; ?></a></li>
								<?php } ?>
							</ul>
						<?php } ?>


						<?php if ( ! $newsletter_id || $newsletter_id == 'new' ) {
							?>
							<p>
								<?php _e( 'Please save newsletter in order to insert images and attachments.' ); ?>
							</p>
							<?php
						} else { ?>
							<div id="image_insert">
								<?php
								ob_start();
								module_file::display_files( array(
									'owner_table'    => 'newsletter',
									'owner_id'       => $newsletter_id,
									'layout'         => 'gallery',
									'click_callback' => 'newsletter_insert_image',
								) );
								$image_insert_html = ob_get_clean();

								$image_insert_fieldset = array(
									'heading'  => array(
										'type'  => 'h3',
										'title' => _l( 'Insert Image:' ),
										'help'  => 'Upload newsletter images below by clicking the add button. Click image to insert into newsletter. Resize image before upload for best results.',
									),
									'class'    => 'tableclass tableclass_form',
									'elements' => array(
										'images' => array(
											'title'  => '',
											'fields' => array( $image_insert_html ),
											'row_title_class' => '',
										),
									),
								);
								echo module_form::generate_fieldset( $image_insert_fieldset );
								?>
								<?php
								/* <tr>
                                            <th>
                                                <?php echo _l('Image:');?>
                                            </th>
                                            <td>
                                               <select name="image_url" id="image_url" style="width:150px;">
                                                <option value=""><?php echo _l('- Select -');?></option>
                                                <?php
                                                foreach(module_newsletter::get_images($newsletter_id) as $attachment){
                                                ?>
                                                <option value="<?php echo $attachment['link'];?>"><?php echo $attachment['name'];?></option>
                                                <?php } ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php echo _l('Size:');?>
                                            </th>
                                            <td>
                                               <select name="image_size" id="image_size" onchange="$('#image_alt')[0].focus().select();">
                                                <option value=""><?php echo _l('- Select -');?></option>
                                                <option value="replace"><?php echo _l('Replace Existing');?></option>
                                                <option value="100x100"><?php echo _l('Thumbnail #1 - 100x100');?></option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php echo _l('Description:');?>
                                            </th>
                                            <td>
                                               <input type="text" name="image_alt" id="image_alt" value=""">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>

                                            </th>
                                            <td>
                                                <input type="button" name="image_insert" onclick="insert_image();" value="<?php echo _l('Insert Image');?>">
                                            </td>
                                        </tr> */ ?>
							</div>

							<?php /* <div id="image_upload" style="display:none;">
                                    <?php
                                    print_heading(array(
                                          'type' => 'h3',
                                          'title' => 'Upload:',
                                          'button' => array(
                                              'url' => '#',
                                              'onclick' => "$('#image_upload').hide(); $('#image_insert').show(); return false;",
                                              'title' => 'Insert Existing Image',
                                          )
                                      ));
                                    ?>
                                    <table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
                                    <tbody>
                                        <tr>
                                            <td align="center">
                                                <input type="file" name="image" value="" size="6"> <br/>
                                                <input type="submit" name="attach" value="<?php echo _l('Upload Image');?>">
                                            </td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </div> */ ?>

							<?php
							ob_start();
							module_file::display_files( array(
								'owner_table' => 'newsletter_files',
								'owner_id'    => $newsletter_id,
								'layout'      => 'list',
							) );
							$attachments_html = ob_get_clean();

							$attachments_fieldset = array(
								'heading'  => array(
									'type'  => 'h3',
									'title' => _l( 'Attachments:' ),
									'help'  => 'Attachments will be sent out with emails. This will use a LOT of your bandwidth! (eg: a 1MB PDF sent to 1000 people = 1GB)',
								),
								'class'    => 'tableclass tableclass_form',
								'elements' => array(
									'attachments' => array(
										'title'  => '',
										'fields' => array( $attachments_html ),
										'row_title_class' => '',
									),
								),
							);
							echo module_form::generate_fieldset( $attachments_fieldset );
							?>
						<?php } ?>

						<?php
						ob_start();
						?>
						<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tbl_fixed">
							<tbody>
							<?php
							$x      = 0;
							$fields = module_newsletter::get_replace_fields( $newsletter_id );
							foreach ( $fields as $key => $val ) {
								if ( $x == 4 ) {
									?>
									</tbody>
									<tbody id="dynamic_more" style="display:none;">
									<?php
								}
								?>
								<tr>
									<td>
										<a href="#" onmousedown="var ed=typeof tinymce!='undefined'?tinymce.get('newsletter_editor_content'):null;if(ed)ed.insertContent(<?php echo json_encode( '{' . $key . '}' ); ?>); return false;" onclick="return false;"><?php echo '{' . htmlspecialchars( $key ) . '}'; ?></a>
									</td>
									<td>
										<?php echo htmlspecialchars( $val ); ?>
									</td>
								</tr>
								<?php
								$x++;
							}
							?>
							<tr>
								<td colspan="2" align="center">
									<?php _e( 'You will have more fields available<br/> once recipients are chosen.' ); ?>
								</td>
							</tr>
							</tbody>
						</table>
						<?php
						$dynamic_table_html = ob_get_clean();

						$dynamic_fields_fieldset = array(
							'heading'      => array(
								'type'   => 'h3',
								'title'  => _l( 'Dynamic Fields:' ),
								'button' => array(
									'url'     => '#',
									'onclick' => "$('#dynamic_more').show(); return false;",
									'title'   => 'View All',
								),
							),
							'elements_before' => $dynamic_table_html,
							'elements'        => array(),
						);
						echo module_form::generate_fieldset( $dynamic_fields_fieldset );
						?>

					</td>
				</tr>
			</table>
		</div>
	</div>


	<?php
	$form_actions_elements = array(
		array( 'type' => 'save_button', 'name' => 'butt_save', 'id' => 'butt_save', 'value' => _l( 'Save' ) ),
		array( 'type' => 'submit', 'name' => 'butt_send', 'id' => 'butt_send', 'value' => _l( 'Send Newsletter' ), 'class' => 'submit_button' ),
	);
	if ( module_newsletter::can_i( 'create', 'Newsletters' ) ) {
		$form_actions_elements[] = array( 'type' => 'submit', 'name' => 'butt_duplicate', 'id' => 'butt_duplicate', 'value' => _l( 'Duplicate' ), 'class' => 'submit_button' );
	}
	$form_actions_elements[] = array(
		'type'    => 'button',
		'name'    => 'cancel',
		'value'   => _l( 'Cancel' ),
		'class'   => 'submit_button',
		'onclick' => "window.location.href='" . module_newsletter::link_list( false ) . "';",
	);
	if ( (int) $newsletter_id > 0 && module_newsletter::can_i( 'delete', 'Newsletters' ) ) {
		$form_actions_elements[] = array( 'type' => 'delete_button', 'name' => 'butt_del', 'id' => 'butt_del', 'value' => _l( 'Delete' ) );
	}

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => $form_actions_elements,
	);
	echo module_form::generate_form_actions( $form_actions );
	?>
</form>


<script language="javascript" type="text/javascript">
    var image_width = 0;
    var image_height = 0;
</script>

<script type="text/javascript">

    function newsletter_insert_image(file_id, file_url, file_name) {
        if (!file_url || file_url == '') return false;
        var imghtml = '<img src="' + file_url + '" alt="' + escape(file_name) + '"';
        image_width = 300; //todo: read from user input.
        if (image_width) imghtml += ' width="' + image_width + '"';
        if (image_height) imghtml += ' height="' + image_height + '"';
        imghtml += ' />';
        var ed = typeof tinymce !== 'undefined' ? tinymce.get('newsletter_editor_content') : null;
        if (ed) ed.insertContent(imghtml);
    }

    function set_default() {
        var default_html = '<?php echo addcslashes( preg_replace( '/\s+/', ' ', $default_content ), "'" );?>';
        var ed = typeof tinymce !== 'undefined' ? tinymce.get('newsletter_editor_content') : null;
        if (ed) ed.setContent(default_html);
        return false;
    }
</script>