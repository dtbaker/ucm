<?php

$content = $_REQUEST['content'];

?>
<iframe name="print_frame" id="print_frame" src="about:bl"
<form action="" method="post">

	<input type="hidden" name="_process" value="print_template"/>
	<table cellpadding="10" width="100%">
		<tr>
			<td valign="top">
				<h3><?php echo _l( 'Edit Template' ); ?></h3>

				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass">
					<tbody>
					<tr>
						<th>
							<?php echo _l( 'Template Key' ); ?>
						</th>
						<td>
							<input type="text" name="template_key" style="width: 350px;" id="template_key"
							       value="<?php echo htmlspecialchars( $template['template_key'] ); ?>"/>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Template Description' ); ?>
						</th>
						<td>
							<input type="text" name="description" style="width: 350px;" id="description"
							       value="<?php echo htmlspecialchars( $template['description'] ); ?>"/>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Default Text' ); ?>
						</th>
						<td valign="top">
							<textarea name="content" id="content" rows="10" cols="30"
							          style="width:450px; height: 350px;"><?php echo htmlspecialchars( $template['content'] ); ?></textarea>

							<?php if ( $template['wysiwyg'] ) { ?>

								<script type="text/javascript"
								        src="<?php echo _BASE_HREF; ?>js/tiny_mce3.4.4/jquery.tinymce.js"></script>
								<script type="text/javascript">
                    $().ready(function () {
                        $('#content').tinymce({
                            // Location of TinyMCE script
                            script_url: '<?php echo _BASE_HREF;?>js/tiny_mce3.4.4/tiny_mce.js',

                            relative_urls: false,
                            convert_urls: false,

                            // General options
                            theme: "advanced",
                            plugins: "fullpage,autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

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
                            width: '650px'

                        });
                    });
								</script>

							<?php } ?>

						</td>
					</tr>

					</tbody>
				</table>

			</td>
		</tr>
		<tr>
			<td align="center">
				<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l( 'Save' ); ?>" class="submit_button"/>
				<!-- <input type="submit" name="butt_del" id="butt_del" value="<?php echo _l( 'Delete' ); ?>" onclick="return confirm('<?php echo _l( 'Really delete this record?' ); ?>');" class="submit_button" /> -->
				<input type="button" name="cancel" value="<?php echo _l( 'Cancel' ); ?>"
				       onclick="window.location.href='<?php echo $module->link( 'template', array( 'template_id' => false ) ); ?>';"
				       class="submit_button"/>

			</td>
		</tr>
	</table>

</form>
