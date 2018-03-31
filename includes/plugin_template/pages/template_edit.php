<?php

if ( ! module_config::can_i( 'view', 'Settings' ) || ! module_template::can_i( 'edit', 'Templates' ) ) {
	redirect_browser( _BASE_HREF );
}
$template_id = $_REQUEST['template_id'];
$template    = array();
if ( (int) $template_id && $template_id != 'new' ) {
	$template = module_template::get_template( $template_id );
}
if ( ! $template ) {
	$template_id = 'new';
	$template    = array(
		'template_id'  => 'new',
		'template_key' => '',
		'description'  => '',
		'content'      => '',
		'name'         => '',
		'default_text' => '',
		'wysiwyg'      => 1,
	);
	module_security::sanatise_data( 'template', $template );
}
?>
<form action="<?php echo module_template::link_open( false ); ?>" method="post" id="template_form">

	<?php
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);

	if ( $template ) {
		// is there a company template?
		if ( class_exists( 'module_company', false ) && defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG ) {
			if ( module_company::get_current_logged_in_company_id() ) {
				// we restrict this template editing to only this template.
				$company_id = module_company::get_current_logged_in_company_id();
			} else {
				$company_id = isset( $_REQUEST['company_id'] ) ? (int) $_REQUEST['company_id'] : module_company::get_current_logged_in_company_id();
			}
			$new_template = module_company::template_get_company( $template['template_id'], $template, $company_id );
			if ( $new_template ) {
				$template = $new_template;
			}
			module_company::template_edit_form( $template_id, $company_id );
		}
	}

	?>


	<input type="hidden" name="_process" value="save_template"/>
	<input type="hidden" name="template_id" value="<?php echo $template_id; ?>"/>
	<input type="hidden" name="return"
	       value="<?php echo isset( $_REQUEST['return'] ) ? htmlspecialchars( urldecode( $_REQUEST['return'] ) ) : ''; ?>"/>
	<!-- for popup editing -->

	<?php ob_start(); ?>

	<table class="tableclass tableclass_form tableclass_full">
		<tbody>
		<?php if ( class_exists( 'module_company', false ) && defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG && (int) $template_id > 0 && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) { ?>
			<tr>
				<th class="width2">
					<?php echo _l( 'Choose Company' ); ?>
				</th>
				<td>
					<?php
					$company_template_rel = array();
					foreach ( module_company::get_companys() as $company ) {
						// does this one have a custom template yet?
						$custom_template = module_company::template_get_company( $template_id, $template, $company['company_id'] );
						if ( $custom_template ) {
							$company_template_rel[ $company['company_id'] ] = $company['name'] . ' ' . _l( '(custom)' );
						} else {
							$company_template_rel[ $company['company_id'] ] = $company['name'];
						}
					}
					echo print_select_box( $company_template_rel, 'company_id', $company_id, '', _l( 'Default' ) ); ?>
					<script type="text/javascript">
              $(function () {
                  $('#company_id').change(function () {
                      change_detected = false;
                      window.location.href = '<?php echo module_template::link_open( $template_id );?>&company_id=' + $(this).val();
                  });
              });
					</script>
				</td>
			</tr>
		<?php }
		if ( class_exists( 'module_company', false ) && defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG && (int) $template_id > 0 && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() && isset( $company_id ) && $company_id ) {
			?>
			<tr>
				<th class="width2">
					<?php echo _l( 'Template Key' ); ?>
				</th>
				<td>
					<?php echo htmlspecialchars( $template['template_key'] ); ?>
				</td>
			</tr>
		<?php } else { ?>
			<tr>
				<th class="width2">
					<?php echo _l( 'Template Key' ); ?>
				</th>
				<td>
					<input type="text" name="template_key" style="width: 350px;" id="template_key"
					       value="<?php echo htmlspecialchars( $template['template_key'] ); ?>"/>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<th>
				<?php echo _l( 'Template Description' ); ?>
			</th>
			<td>
				<input type="text" name="description" style="width: 350px;" id="description"
				       value="<?php echo htmlspecialchars( $template['description'] ); ?>"/>
			</td>
		</tr>
		<tr id="wysiwyg">
			<th>
				<?php echo _l( 'WYSIWYG' ); ?>
			</th>
			<td>
				<?php echo print_select_box( get_yes_no(), 'wysiwyg', isset( $template['wysiwyg'] ) ? $template['wysiwyg'] : 0, '', false ); ?>
				(advanced setting, don't change unless you know what you're doing)
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Default Text' ); ?>
			</th>
			<td valign="top">
				<textarea name="content" id="template_content" rows="10" cols="30"
				          style="width:450px; height: 350px;"><?php echo htmlspecialchars( $template['content'] ); ?></textarea>

				<?php if ( isset( $template['wysiwyg'] ) && $template['wysiwyg'] ) { ?>

					<script type="text/javascript" src="<?php echo _BASE_HREF; ?>js/tiny_mce3.4.4/jquery.tinymce.js"></script>
					<script type="text/javascript">
              $().ready(function () {
                  $('#template_content').tinymce({
                      // Location of TinyMCE script
                      script_url: '<?php echo _BASE_HREF;?>js/tiny_mce3.4.4/tiny_mce.js',

                      relative_urls: false,
                      convert_urls: false,

                      // General options
                      theme: "advanced",
                      plugins: "<?php echo ( stripos( $template['content'], '<html' ) !== false || stripos( $template['content'], '<body' ) !== false ) ? 'fullpage,' : ''; ?>autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

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
                      width: '100%'

                  });
              });
					</script>

				<?php } ?>

			</td>
		</tr>
		<tr>
			<th></th>
			<td>
				<a href="#"
				   onclick="if(confirm('Really restore default template?')){ <?php if ( isset( $template['wysiwyg'] ) && $template['wysiwyg'] ) { ?> $('#template_content').tinymce().remove(); <?php } ?> $('#template_content').val(''); $('#template_form')[0].submit(); } return false;">Restore
					Default</a>
				<ul>
					<li><?php _e( 'Special Template Tags:' ); ?></li>
					<li><strong>{currency:XXXX}</strong>
						- <?php _e( 'This will format XXXX as a currency value, replace XXXX with a template tag below.' ); ?></li>
					<li><strong>{XXXX+123} or {XXXX-123}</strong>
						- <?php _e( 'This will perform basic arithmetic (plus, minus or multiplication) on the XXXX template tag.' ); ?>
					</li>
					<li><strong>{currency:XXXX*0.5}</strong>
						- <?php _e( 'This will show 50% of the XXXX template tag as a currency value.' ); ?></li>
					<li><strong>{l:Some Word}</strong> - <?php _e( 'This will display the translation for "Some Word".' ); ?></li>
					<li><strong>{if:XXXX} 123 {else} 456 {endif:XXXX}</strong>
						- <?php _e( 'If the template tag XXXX contains a value then 123 will be displayed, otherwise 456 will be displayed.' ); ?>
					</li>
					<li><strong>{if:XXXX=Some String} 123 {elseif:XXXX=Another String} 456 {else} 789 {endif:XXXX}</strong>
						- <?php _e( 'If the template tag XXXX equals "Some String" then 123 will be displayed, if XXXX equals "Another String" then 456 will be displayed, otherwise 789 will be displayed.' ); ?>
					</li>
					<li><strong>{XXXX+4d} or {XXXX+2m}</strong>
						- <?php _e( 'If XXXX is a date field, perform basic date arithmetic such as plus 4 days or plus 2 months.' ); ?>
					</li>
					<li><strong>{XXXX+4d} or {XXXX+2m}</strong>
						- <?php _e( 'If XXXX is a date field, perform basic date arithmetic such as plus 4 days or plus 2 months.' ); ?>
					</li>
					<li><strong>{XXXX-m}</strong>
						- <?php _e( 'If XXXX is a date field, only output the m/month portion of it. Possible values: ymdYMDjlSWFn' ); ?>
					</li>
					<li><strong>{DAY} or {MONTH} or {YEAR}</strong> - <?php _e( 'Output the current day, month or year.' ); ?>
					</li>
					<?php
					if ( isset( $template['tags'] ) && strlen( $template['tags'] ) && $tags = @unserialize( $template['tags'] ) ) {
						echo '<li>' . _l( 'Available Template Tags:' ) . '</li>';
						foreach ( $tags as $key => $val ) {
							echo '<li>';
							echo '<strong>{' . htmlspecialchars( $key ) . '}</strong>';
							if ( $val && ! is_array( $val ) ) {
								echo ' ' . htmlspecialchars( $val );
							}
							echo '</li>';
						}
					} else {
					} ?>
				</ul>
				<br/><br/>

			</td>
		</tr>


		</tbody>
	</table>

	<?php
	$fieldset_data = array(
		'heading'         => array(
			'type'  => 'h2',
			'main'  => true,
			'title' => 'Edit Template',
		),
		'elements_before' => ob_get_clean(),
	);

	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'ignore' => ! ( module_template::can_i( 'delete', 'Templates' ) && (int) $template_id > 0 ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . $module->link( 'template', array( 'template_id' => false ) ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );
	?>

</form>
