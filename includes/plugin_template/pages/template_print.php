<?php

$content = $_REQUEST['content'];

?>
<iframe name="print_frame" id="print_frame" src="about:blank" style="display:none;"></iframe>
<form action="" method="post">

	<input type="hidden" name="_process" value="print_template"/>
	<?php
	$content_field = isset( $template['wysiwyg'] ) && $template['wysiwyg']
		? array(
			'type'  => 'wysiwyg',
			'name'  => 'content',
			'id'    => 'content',
			'value' => $template['content'],
		)
		: array(
			'type'  => 'textarea',
			'name'  => 'content',
			'id'    => 'content',
			'value' => $template['content'],
			'style' => 'width:450px; height: 350px;',
		);

	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => _l( 'Edit Template' ),
		),
		'class'    => 'tableclass tableclass_form',
		'elements' => array(
			'template_key' => array(
				'title' => _l( 'Template Key' ),
				'field' => array(
					'type'   => 'text',
					'name'   => 'template_key',
					'id'     => 'template_key',
					'value'  => $template['template_key'],
					'style'  => 'width: 350px;',
				),
			),
			'description' => array(
				'title' => _l( 'Template Description' ),
				'field' => array(
					'type'   => 'text',
					'name'   => 'description',
					'id'     => 'description',
					'value'  => $template['description'],
					'style'  => 'width: 350px;',
				),
			),
			'content' => array(
				'title' => _l( 'Default Text' ),
				'field' => $content_field,
			),
		),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center action_bar_single',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'id'    => 'butt_save',
				'value' => _l( 'Save' ),
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
