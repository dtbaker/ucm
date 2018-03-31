<?php
$module->page_title = _l( 'Template Edit' );
//print_heading('Newsletter Editor');

$newsletter_template_id = isset( $_REQUEST['newsletter_template_id'] ) ? (int) $_REQUEST['newsletter_template_id'] : false;
$newsletter_template    = module_newsletter::get_newsletter_template( $newsletter_template_id );

?>

<form action="" method="post">
	<input type="hidden" name="_process" value="save_newsletter_template">
	<?php
	module_form::set_required( array(
			'fields' => array(
				'newsletter_template_name' => 'Name',
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

	<table width="100%" cellpadding="5">
		<tbody>
		<tr>
			<td valign="top">
				<h3><?php echo _l( 'Newsletter Template Details' ); ?></h3>

				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
					<tbody>
					<tr>
						<th class="width1">
							<?php echo _l( 'Name' ); ?>
						</th>
						<td>
							<?php if ( (int) $newsletter_template_id > 0 && $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) ) { ?>
								<input type="hidden" name="newsletter_template_name"
								       value="<?php echo htmlspecialchars( $newsletter_template['newsletter_template_name'] ); ?>"/>
								<?php echo htmlspecialchars( $newsletter_template['newsletter_template_name'] ); ?>
							<?php } else { ?>
								<input type="text" name="newsletter_template_name"
								       value="<?php echo htmlspecialchars( $newsletter_template['newsletter_template_name'] ); ?>"/>
							<?php } ?>
						</td>
					</tr>
					<?php if ( false ) { ?>
						<tr>
							<th>
								<?php echo _l( 'Input Type' ); ?>
							</th>
							<td>
								<input type="radio" name="wizard"
								       value="0"<?php echo ! $newsletter_template['wizard'] ? ' checked' : ''; ?>> <?php _e( 'Single Body Area' ); ?>
								<input type="radio" name="wizard"
								       value="1"<?php echo $newsletter_template['wizard'] ? ' checked' : ''; ?>> <?php _e( 'Multiple Body Areas' ); ?>
							</td>
						</tr>
					<?php } ?>
					<?php if ( (int) $newsletter_template_id > 0 ) { ?>
						<?php if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) ) { ?>
							<tr>
								<th>
									<?php echo _l( 'Template Folder' ); ?>
								</th>
								<td>
									<?php
									echo $newsletter_template['directory'];
									echo ' ';
									_e( '(you can create advanced template features from this folder)' );
									?>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<th>
								<?php echo _l( 'Preview Image' ); ?>
							</th>
							<td>
								<?php
								if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) && is_file( $newsletter_template['directory'] . 'preview.jpg' ) ) {
									?>
									<img src="<?php echo full_link( $newsletter_template['directory'] . 'preview.jpg' ); ?>">
									<?php
								} else {
									module_file::display_files( array(
											//'title' => 'Certificate Files',
											'owner_table' => 'newsletter_template',
											'owner_id'    => $newsletter_template_id,
											'layout'      => 'gallery',
										)
									);
								}
								?>
							</td>
						</tr>
						<?php
						if ( $newsletter_template['directory'] && is_dir( $newsletter_template['directory'] ) ) {
							if ( is_file( $newsletter_template['directory'] . 'render.php' ) ) {
								$description = 'N/A';
								$contents    = file_get_contents( $newsletter_template['directory'] . 'render.php' );
								if ( preg_match( '#Description:(.*)#i', $contents, $matches ) ) {
									$description = htmlspecialchars( $matches[1] );
								}
								?>
								<tr>
									<th>
										<?php _e( 'Custom Rendering' ); ?>
									</th>
									<td>
										<?php _e( 'Controlled by file: <em>%s</em> %s<br/>Description: <em>%s</em> ', $newsletter_template['directory'] . 'render.php', _hr( 'You can expand on the default functionality of the newsletter rendering system by editing this custom PHP file. You can include custom PHP calls to databases to output your own HTML for sending. Pretty nifty!' ), $description ); ?>
									</td>
								</tr>
								<?php
							}
						} ?>
					<?php } ?>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
	<table width="100%" cellpadding="5">
		<tbody>
		<tr>
			<td valign="top" width="70%">
				<?php
				print_heading( array(
					'type'  => 'h3',
					'title' => 'Template HTML Code:',
					'help'  => 'The outer HTML code for this template. That is, all the parts of the template that will not change each time you send a newsletter (header, menu, footer). You can include the dynamic fields which will be replaced each time the newsletter is sent.<br>When you insert images or links please use full addresses (including http:// at the start).',
				) );
				?>
				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
					<tbody>
					<tr>
						<td>
							<?php
							if ( strpos( $newsletter_template['body'], '{BODY}' ) === false ) {
								_e( 'Please make sure you include a single {BODY} tag in the template.' );
							}
							if ( strpos( $newsletter_template['body'], '{UNSUBSCRIBE}' ) === false ) {
								_e( 'It is probably best to include the {UNSUBSCRIBE} link in the template. Please add it so users can unsubscribe easily.' );
							} ?>
							<textarea rows="20" cols="20" name="body" id="template_body"
							          style="width:96%; height:500px; margin:5px;"><?php echo htmlspecialchars( $newsletter_template['body'] ); ?></textarea>
						</td>
					</tr>
					</tbody>
				</table>
				<?php
				print_heading( array(
					'type'  => 'h3',
					'title' => 'Template Default Content:',
					'help'  => 'When this template is selected, the below default content will appear in the newsletter for editing.',
				) );
				?>
				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
					<tbody>
					<tr>
						<td>
							<textarea rows="20" cols="20" name="default_inner" id="default_inner"
							          style="width:96%; height:300px; margin:5px;"><?php echo htmlspecialchars( isset( $newsletter_template['default_inner'] ) ? $newsletter_template['default_inner'] : '' ); ?></textarea>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
			<td valign="top">
				<?php
				print_heading( array(
					'type'  => 'h3',
					'title' => 'Dynamic Fields:',
				) );
				?>

				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tbl_fixed">
					<tbody>
					<tr>
						<td class="width1">
							<a href="#" onmousedown="$('#template_body').val($('#template_body').val() + '{BODY}'); return false;"
							   onclick="return false;">{BODY}</a>
						</td>
						<td>
							content from "create newsletter"
						</td>
					</tr>
					<tr>
						<td>
							<a href="#"
							   onmousedown="$('#template_body').val($('#template_body').val() + '<?php echo '<?php ?>'; ?>'); return false;"
							   onclick="return false;">&lt;?php ?&gt;</a>
						</td>
						<td>
							code evaluated at send time
						</td>
					</tr>
					<?php
					$x      = 0;
					$fields = module_newsletter::get_replace_fields();
					foreach ( $fields as $key => $val ) {
						?>
						<tr>
							<td>
								<a href="#"
								   onmousedown="$('#template_body').val($('#template_body').val() + '{<?php echo $key; ?>}'); return false;"
								   onclick="return false;"><?php echo '{' . htmlspecialchars( $key ) . '}'; ?></a>
							</td>
							<td>
								<?php echo htmlspecialchars( $val ); ?>
							</td>
						</tr>
						<?php
						$x ++;
					} ?>
					<tr>
						<td colspan="2" align="center">
							<?php _e( 'You will have more custom fields here<br/> once you pick your recipients.' ); ?>
						</td>
					</tr>
					</tbody>
				</table>


			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l( 'Save' ); ?>"
				       class="submit_button save_button"/>
				<input type="button" name="cancel" value="<?php echo _l( 'Cancel' ); ?>"
				       onclick="window.location.href='<?php echo module_newsletter::link_list( false ); ?>';"
				       class="submit_button"/>
				<?php if ( (int) $newsletter_template_id > 0 ) { ?>
					<input type="submit" name="butt_del" id="butt_del" value="<?php echo _l( 'Delete' ); ?>"
					       class="submit_button delete_button"/>
				<?php } ?>

			</td>
		</tr>
		</tbody>
	</table>


</form>

