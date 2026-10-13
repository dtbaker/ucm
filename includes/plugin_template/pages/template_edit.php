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


	<?php module_form::print_form_auth(); ?>
	<input type="hidden" name="_process" value="save_template"/>
	<input type="hidden" name="template_id" value="<?php echo htmlspecialchars( (string) $template_id, ENT_QUOTES, 'UTF-8' ); ?>"/>
	<input type="hidden" name="return"
	       value="<?php echo isset( $_REQUEST['return'] ) ? htmlspecialchars( urldecode( $_REQUEST['return'] ) ) : ''; ?>"/>

	<?php
	$show_company_select = class_exists( 'module_company', false ) && defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG && (int) $template_id > 0 && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled();
	$show_template_key_readonly = $show_company_select && isset( $company_id ) && $company_id;

	$company_template_rel = array();
	if ( $show_company_select ) {
		foreach ( module_company::get_companys() as $company ) {
			$custom_template = module_company::template_get_company( $template_id, $template, $company['company_id'] );
			if ( $custom_template ) {
				$company_template_rel[ $company['company_id'] ] = $company['name'] . ' ' . _l( '(custom)' );
			} else {
				$company_template_rel[ $company['company_id'] ] = $company['name'];
			}
		}
	}

	$restore_default_html = '<a href="#" onclick="if(confirm(\'' . htmlspecialchars( _l( 'Really restore default template?' ) ) . '\')){ if(typeof tinymce !== \'undefined\' && tinymce.get(\'template_content\')){ tinymce.get(\'template_content\').setContent(\'\'); } else { var el = document.getElementById(\'template_content\'); if(el) el.value=\'\'; } document.getElementById(\'template_form\').submit(); } return false;">' . _l( 'Restore Default' ) . '</a>';
	$restore_default_html .= '<ul><li>' . _l( 'Special Template Tags:' ) . '</li>';
	$restore_default_html .= '<li><strong>{currency:XXXX}</strong> - ' . _l( 'This will format XXXX as a currency value, replace XXXX with a template tag below.' ) . '</li>';
	$restore_default_html .= '<li><strong>{XXXX+123} or {XXXX-123}</strong> - ' . _l( 'This will perform basic arithmetic (plus, minus or multiplication) on the XXXX template tag.' ) . '</li>';
	$restore_default_html .= '<li><strong>{currency:XXXX*0.5}</strong> - ' . _l( 'This will show 50% of the XXXX template tag as a currency value.' ) . '</li>';
	$restore_default_html .= '<li><strong>{l:Some Word}</strong> - ' . _l( 'This will display the translation for "Some Word".' ) . '</li>';
	$restore_default_html .= '<li><strong>{if:XXXX} 123 {else} 456 {endif:XXXX}</strong> - ' . _l( 'If the template tag XXXX contains a value then 123 will be displayed, otherwise 456 will be displayed.' ) . '</li>';
	$restore_default_html .= '<li><strong>{if:XXXX=Some String} 123 {elseif:XXXX=Another String} 456 {else} 789 {endif:XXXX}</strong> - ' . _l( 'If the template tag XXXX equals "Some String" then 123 will be displayed, if XXXX equals "Another String" then 456 will be displayed, otherwise 789 will be displayed.' ) . '</li>';
	$restore_default_html .= '<li><strong>{XXXX+4d} or {XXXX+2m}</strong> - ' . _l( 'If XXXX is a date field, perform basic date arithmetic such as plus 4 days or plus 2 months.' ) . '</li>';
	$restore_default_html .= '<li><strong>{XXXX-m}</strong> - ' . _l( 'If XXXX is a date field, only output the m/month portion of it. Possible values: ymdYMDjlSWFn' ) . '</li>';
	$restore_default_html .= '<li><strong>{DAY} or {MONTH} or {YEAR}</strong> - ' . _l( 'Output the current day, month or year.' ) . '</li>';
	if ( isset( $template['tags'] ) && strlen( $template['tags'] ) && $tags = @unserialize( $template['tags'] ) ) {
		$restore_default_html .= '<li>' . _l( 'Available Template Tags:' ) . '</li>';
		foreach ( $tags as $key => $val ) {
			$restore_default_html .= '<li><strong>{' . htmlspecialchars( $key ) . '}</strong>';
			if ( $val && ! is_array( $val ) ) {
				$restore_default_html .= ' ' . htmlspecialchars( $val );
			}
			$restore_default_html .= '</li>';
		}
	}
	$restore_default_html .= '</ul><br/><br/>';

	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h2',
			'main'  => true,
			'title' => 'Edit Template',
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(),
	);

	if ( $show_company_select ) {
		$fieldset_data['elements']['company_id'] = array(
			'title'           => _l( 'Choose Company' ),
			'row_title_class' => 'width2',
			'field'           => array(
				'type'    => 'select',
				'name'    => 'company_id',
				'id'      => 'company_id',
				'value'   => isset( $company_id ) ? $company_id : '',
				'options' => $company_template_rel,
				'blank'   => _l( 'Default' ),
			),
		);
	}

	if ( $show_template_key_readonly ) {
		$fieldset_data['elements']['template_key'] = array(
			'title'           => _l( 'Template Key' ),
			'row_title_class' => 'width2',
			'fields'          => array( htmlspecialchars( $template['template_key'] ) ),
		);
	} else {
		$fieldset_data['elements']['template_key'] = array(
			'title'           => _l( 'Template Key' ),
			'row_title_class' => 'width2',
			'field'           => array(
				'type'   => 'text',
				'name'   => 'template_key',
				'id'     => 'template_key',
				'value'  => $template['template_key'],
				'style'  => 'width: 350px;',
			),
		);
	}

	$fieldset_data['elements']['description'] = array(
		'title' => _l( 'Template Description' ),
		'field' => array(
			'type'   => 'text',
			'name'   => 'description',
			'id'     => 'description',
			'value'  => $template['description'],
			'style'  => 'width: 350px;',
		),
	);

	$fieldset_data['elements']['wysiwyg'] = array(
		'title'  => _l( 'WYSIWYG' ),
		'fields' => array(
			array(
				'type'    => 'select',
				'name'    => 'wysiwyg',
				'value'   => isset( $template['wysiwyg'] ) ? $template['wysiwyg'] : 0,
				'options' => get_yes_no(),
				'blank'   => false,
			),
			' ' . _l( '(advanced setting, don\'t change unless you know what you\'re doing)' ),
		),
	);

	$content_field = isset( $template['wysiwyg'] ) && $template['wysiwyg']
		? array(
			'type'  => 'wysiwyg',
			'name'  => 'content',
			'id'    => 'template_content',
			'value' => $template['content'],
		)
		: array(
			'type'  => 'textarea',
			'name'  => 'content',
			'id'    => 'template_content',
			'value' => $template['content'],
			'style' => 'width:450px; height: 350px;',
		);

	$fieldset_data['elements']['content'] = array(
		'title'  => _l( 'Default Text' ),
		'fields' => array( $content_field ),
		'row_data_class' => 'valign_top',
	);

	$fieldset_data['elements']['restore_default'] = array(
		'title'  => '',
		'fields' => array( $restore_default_html ),
		'row_title_class' => '',
	);

	$fieldset_data['elements_after'] = '';
	if ( $show_company_select ) {
		$fieldset_data['elements_after'] .= '<script type="text/javascript">$(function(){ $("#company_id").change(function(){ change_detected = false; window.location.href = "' . module_template::link_open( $template_id ) . '&company_id=" + $(this).val(); }); });</script>';
	}

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
