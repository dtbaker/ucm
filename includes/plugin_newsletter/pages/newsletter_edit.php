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

	<table width="100%" cellpadding="5">
		<tbody>
		<tr>
			<td width="450" valign="top">
				<h3><?php echo _l( 'Email Details' ); ?></h3>

				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
					<tbody>
					<tr>
						<th class="width1">
							<?php echo _l( 'Subject' ); ?>
						</th>
						<td>
							<input type="text" name="subject" id="subject" style="width:250px;"
							       value="<?php echo htmlspecialchars( $newsletter['subject'] ); ?>"/>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Sender Name' ); ?>
						</th>
						<td>
							<input type="text" name="from_name" id="from_name" style="width:250px;"
							       value="<?php echo htmlspecialchars( $newsletter['from_name'] ); ?>"/>
							<?php _h( 'This is the name this newsletter will be sent "from". Giving this a name your customers will recognise is probably best.' ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Sender Email' ); ?>
						</th>
						<td>
							<input type="text" name="from_email" id="from_email" style="width:250px;"
							       value="<?php echo htmlspecialchars( $newsletter['from_email'] ); ?>"/>
							<?php _h( 'This is the email address the newsletter will be sent "from". We HIGHLY recommend this email address is setup or linked to your hosting account to help with spam issues (or google about SPF records - can be complex but worth it)' ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Bounce Email' ); ?>
						</th>
						<td>
							<input type="text" name="bounce_email" id="bounce_email" style="width:250px;"
							       value="<?php echo htmlspecialchars( $newsletter['bounce_email'] ); ?>"/>
							<?php _h( 'This is the email address any bounces are set to. We HIGHLY recommend you setup a new empty email address (eg: bounce@yourwebsite.com) for this. In the newsletter settings you can specify the bounce address settings so the system can automatically track bounces.' ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'To Name' ); ?>
						</th>
						<td>
							<input type="text" name="to_name" style="width:250px;"
							       value="<?php echo htmlspecialchars( $newsletter['to_name'] ); ?>"/>
							<?php _h( 'Format for the recipients name. This is more of an advanced thing, the default here should be fine for everyone, but you could get fancy and use {COMPANY_NAME} if you like.' ); ?>
						</td>
					</tr>
					</tbody>
				</table>
			</td>
			<td valign="top">
				<h3><?php echo _l( 'Choose Template' ); ?></h3>

				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form" width="100%">
					<tbody>
					<!--<tr>
                        <td>
                            <p><?php /*_e('A template controls the outer look &amp; feel of your email.');*/ ?></p>
                        </td>
                    </tr>-->
					<tr>
						<td align="center" style="text-align: center">

							<input type="hidden" name="newsletter_template_id" id="newsletter_template_id"
							       value="<?php echo $newsletter['newsletter_template_id']; ?>">

							<?php
							foreach ( $templates as $template ) {
								$template = module_newsletter::get_newsletter_template( $template['newsletter_template_id'] );
								?>
								<div
									class="template<?php echo ( $newsletter['newsletter_template_id'] == $template['newsletter_template_id'] ) ? ' selected' : ''; ?>"
									onclick="$('#newsletter_template_id').val(<?php echo $template['newsletter_template_id']; ?>); $('.template').removeClass('selected'); $(this).addClass('selected'); $('#butt_save').click(); "
									style="cursor: pointer;">
									<div style="height:94px; overflow: hidden;">
										<?php
										// find the image:
										if ( $template['directory'] && is_dir( $template['directory'] ) && is_file( $template['directory'] . 'preview.jpg' ) ) {
											?>
											<img src="<?php echo full_link( $template['directory'] . 'preview.jpg' ); ?>">
											<?php
										} else {
											module_file::display_files( array(
													//'title' => 'Certificate Files',
													'owner_table' => 'newsletter_template',
													'owner_id'    => $template['newsletter_template_id'],
													'layout'      => 'preview',
												)
											);
										}
										?>

									</div>
									<div style="clear:both; padding:5px;"><?php echo $template['newsletter_template_name']; ?></div>
								</div>

							<?php } ?>
						</td>
					</tr>
					</tbody>
				</table>

			</td>
			<td valign="top">

				<h3><?php echo _l( 'Quick Preview' ); ?></h3>
				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
					<tbody>
					<tr>
						<td style="text-align: center">
							<p style="line-height: 24px;">
								<input type="text" name="quick_email"
								       value="<?php echo htmlspecialchars( $newsletter['from_email'] ); ?>" size="35"> <br/>
								<input type="submit" name="butt_preview_email" value="<?php echo _l( 'Email Preview' ); ?>"
								       class="valid_exit small_button">
								<br/>
								<input type="submit" name="butt_preview" value="<?php echo _l( 'Browser Preview' ); ?>"
								       class="valid_exit small_button"/>
							</p>
						</td>
					</tr>
					</tbody>
				</table>


			</td>
		</tr>
		</tbody>
	</table>


	<?php
	$help_text = addcslashes( _l( "Here you can copy and paste from Word, or simply type out your newsletter. The above template will be applied to this content. Note: if you copy and paste from another document or website, please use the 'Paste from Word' button or even better the 'Paste as Plain Text' button (otherwise your newsletter layout may break when you go to send it)." ), "'" );
	print_heading( array(
		'type'   => 'h3',
		'title'  => 'Newsletter Content',
		'button' => array(
			'url'     => '#',
			'onclick' => "alert('$help_text'); return false;",
			'title'   => 'help',
		)
	) );
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

									<h3><?php echo _l( 'Extra Details' ); ?></h3>

									<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
										<tbody>
										<tr>
											<th class="width2">
												<?php echo _l( 'Content Area Title' ); ?>
											</th>
											<td>
												<input type="text" name="subject2" style="width:250px;"
												       value="<?php echo htmlspecialchars( $newsletter['extra_content'][ $current_newsletter_content_id ]['group_title'] ); ?>"/>
											</td>
										</tr>
										<tr>
											<th class="width2">
												<?php echo _l( 'Content Area Position' ); ?>
											</th>
											<td>
												<input type="text" name="subject2" style="width:250px;"
												       value="<?php echo htmlspecialchars( $newsletter['extra_content'][ $current_newsletter_content_id ]['group_title'] ); ?>"/>
											</td>
										</tr>
										</tbody>
									</table>
									<?php
								} else {
									// we are editing a non existant content area
									// or we are just editing the main content area.
									$content = $newsletter['content'];
								}

							} else {
								$content = $newsletter['content'];
							}
							?>
							<textarea name="content"
							          id="newsletter_editor_content"><?php echo htmlspecialchars( $content ); ?></textarea>
							<?php if ( trim( $default_content ) ) { ?>
								<p><a href="#" onclick="set_default();return false;">click here</a> to load default content from
									template.</p>
							<?php } ?>
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
								print_heading( array(
									'type'  => 'h3',
									'title' => 'Insert Image:',
									'help'  => 'Upload newsletter images below by clicking the add button. Click image to insert into newsletter. Resize image before upload for best results.',
									/*'button' => array(
											'url' => '#',
											'onclick' => "$('#image_insert').hide(); $('#image_upload').show(); return false;",
											'title' => 'Upload New Image',
									)*/
								) );
								?>
								<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
									<tbody>
									<tr>
										<td>
											<?php
											module_file::display_files( array(
													//'title' => 'Certificate Files',
													'owner_table'    => 'newsletter',
													'owner_id'       => $newsletter_id,
													'layout'         => 'gallery',
													'click_callback' => 'newsletter_insert_image',
												)
											);
											?>
										</td>
									</tr>
									<?php /* <tr>
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
									</tbody>
								</table>

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
							print_heading( array(
								'type'  => 'h3',
								'title' => 'Attachments:',
								'help'  => 'Attachments will be sent out with emails. This will use a LOT of your bandwidth! (eg: a 1MB PDF sent to 1000 people = 1GB)',
							) );
							?>
							<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
								<tbody>
								<tr>
									<td>
										<?php
										module_file::display_files( array(
												'owner_table' => 'newsletter_files',
												'owner_id'    => $newsletter_id,
												'layout'      => 'list',
											)
										);
										?>
									</td>
								</tr>
								</tbody>
							</table>

						<?php } ?>

						<?php
						print_heading( array(
							'type'   => 'h3',
							'title'  => 'Dynamic Fields:',
							'button' => array(
								'url'     => '#',
								'onclick' => "$('#dynamic_more').show(); return false;",
								'title'   => 'View All',
							)
						) );
						?>
						<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tbl_fixed">
							<tbody>
							<?php
							$x      = 0;
							$fields = module_newsletter::get_replace_fields( $newsletter_id );
							foreach ( $fields

							as $key => $val ){
							if ( $x == 4 ){
							?>
							</tbody>
							<tbody id="dynamic_more" style="display:none;">
							<?php
							}
							?>
							<tr>
								<td>
									<a href="#"
									   onmousedown="tinyMCE.execCommand('mceInsertContent',false,'{<?php echo $key; ?>}'); return false;"
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
									<?php _e( 'You will have more fields available<br/> once recipients are chosen.' ); ?>
								</td>
							</tr>
							</tbody>
						</table>

					</td>
				</tr>
			</table>
		</div>
	</div>


	<div align="center">
		<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l( 'Save' ); ?>"
		       class="submit_button save_button"/>
		<input type="submit" name="butt_send" id="butt_send" value="<?php echo _l( 'Send Newsletter' ); ?>"
		       class="submit_button"/>
		<?php if ( module_newsletter::can_i( 'create', 'Newsletters' ) ) { ?>
			<input type="submit" name="butt_duplicate" id="butt_duplicate" value="<?php echo _l( 'Duplicate' ); ?>"
			       class="submit_button"/>
		<?php } ?>
		<input type="button" name="cancel" value="<?php echo _l( 'Cancel' ); ?>"
		       onclick="window.location.href='<?php echo module_newsletter::link_list( false ); ?>';"
		       class="submit_button"/>
		<?php if ( (int) $newsletter_id > 0 && module_newsletter::can_i( 'delete', 'Newsletters' ) ) { ?>
			<input type="submit" name="butt_del" id="butt_del" value="<?php echo _l( 'Delete' ); ?>"
			       class="submit_button delete_button"/>
		<?php } ?>
	</div>


</form>


<script language="javascript" type="text/javascript">
    var image_width = 0;
    var image_height = 0;
</script>

<script type="text/javascript" src="<?php echo _BASE_HREF; ?>js/tiny_mce3.4.4/jquery.tinymce.js"></script>
<script type="text/javascript">
    $().ready(function () {
        $('#newsletter_editor_content').tinymce({
            // Location of TinyMCE script
            script_url: '<?php echo _BASE_HREF;?>js/tiny_mce3.4.4/tiny_mce.js',

            relative_urls: false,
            convert_urls: false,

            // General options
            theme: "advanced",
            plugins: "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

            // Theme options
            theme_advanced_buttons1: "undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
            theme_advanced_buttons2: "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor",
            theme_advanced_buttons3: "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell",
            /*theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
						theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
						theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
						theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",*/
            theme_advanced_toolbar_location: "top",
            theme_advanced_toolbar_align: "left",
            theme_advanced_statusbar_location: "bottom",
            theme_advanced_resizing: true,


            height: '600px',
            width: '100%',

            setup: function (ed) {
                ed.onInit.add(function (ed) {
									<?php if(isset( $editor_bg_color )){ ?>
                    $('body', $('#' + ed.id + '_ifr')[0].contentWindow.document).css('backgroundColor', '<?php echo $editor_bg_color;?>');
									<?php } ?>
									<?php if(isset( $text_color )){ ?>
                    $('body', $('#' + ed.id + '_ifr')[0].contentWindow.document).css('color', '<?php echo $text_color;?>');
                    $('a', $('#' + ed.id + '_ifr')[0].contentWindow.document).css('color', '<?php echo $text_color;?>');
                    //document.getElementById(ed.id+'_ifr').contentWindow.document.body.style.color='<?php echo $text_color;?>';
									<?php } ?>
                });

            }

        });
    });
</script>
<!-- /TinyMCE -->


<?php
/*
require_once(_UCM_FOLDER."js/tiny_mce/tiny_mce_gzip.php");
TinyMCE_Compressor::renderTag(array(
    "url" => _BASE_HREF."js/tiny_mce/tiny_mce_gzip.php",
    "plugins" => "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,insertdatetime,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,inlinepopups",
    "themes" => "advanced",
    "languages" => "en",
));
?>

<!--<script language="javascript" type="text/javascript" src="layout/js/tiny_mce/tiny_mce.js"></script>-->
<script type="text/javascript">
tinyMCE.init({
    mode: "exact",
    elements : "content",
    theme : "advanced",
    plugins : "safari,pagebreak,style,layer,table,advhr,advimage,advlink,iespell,insertdatetime,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,visualchars,nonbreaking,xhtmlxtras,inlinepopups",
    height : '400px',
    width : '650px',
    // Theme options
    theme_advanced_buttons1 : "undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor",
    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    valid_elements : "*[*]",
    setup : function(ed){
        ed.onNodeChange.add(function(ed, cm, n, co) {
            if(n.nodeName == 'IMG'){
                // selected an image, load the alt tag and size over on the right.
                $('#image_size').val('replace');
                $('#image_alt').val(ed.dom.getAttrib(n, 'alt'));
                image_width = $(n).width();
                image_height = $(n).height();
            }else{
                $('#image_size').val('');
                $('#image_alt').val('Image Description');
                image_width = image_height = 0;
            }
      });
        ed.onInit.add(function(ed) {
            <?php if(isset($editor_bg_color)){ ?>
                $('body',$('#'+ed.id+'_ifr')[0].contentWindow.document).css('backgroundColor','<?php echo $editor_bg_color;?>');
            <?php } ?>
            <?php if(isset($text_color)){ ?>
                $('body',$('#'+ed.id+'_ifr')[0].contentWindow.document).css('color','<?php echo $text_color;?>');
                $('a',$('#'+ed.id+'_ifr')[0].contentWindow.document).css('color','<?php echo $text_color;?>');
                //document.getElementById(ed.id+'_ifr').contentWindow.document.body.style.color='<?php echo $text_color;?>';
            <?php } ?>
        });

    }

});
</script>
*/
?>
<script type="text/javascript">

    function newsletter_insert_image(file_id, file_url, file_name) {
        if (!file_url || file_url == '') return false;
        var imghtml = '<img src="' + file_url + '" alt="' + escape(file_name) + '"';
        image_width = 300; //todo: read from user input.
        if (image_width) imghtml += ' width="' + image_width + '"';
        if (image_height) imghtml += ' height="' + image_height + '"';
        imghtml += ' />';
        tinyMCE.execCommand('mceInsertRawHTML', false, imghtml);
    }

    function set_default() {
        var default_html = '<?php echo addcslashes( preg_replace( '/\s+/', ' ', $default_content ), "'" );?>';
        tinyMCE.execCommand('mceInsertContent', false, default_html);
        return false;
    }
</script>