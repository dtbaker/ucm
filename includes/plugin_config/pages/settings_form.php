<form action="" method="post">

	<input type="hidden" name="_config_settings_hook" value="save_config">

	<?php
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);

	module_form::print_form_auth();

	$fieldset_data = array(
		'elements' => array(),
	);

	if ( isset( $settings['settings'] ) && is_array( $settings['settings'] ) ) {
		if ( isset( $settings['title'] ) ) {
			$fieldset_data['heading'] = array(
				'title' => $settings['title'],
				'type'  => 'h3',
			);
		} else if ( isset( $settings['heading'] ) ) {
			$fieldset_data['heading'] = $settings['heading'];
		}
		$settings = $settings['settings'];
	}

	foreach ( $settings as $setting_id => $setting ) {
		if ( isset( $setting['key'] ) ) {
			$setting['name'] = 'config[' . $setting['key'] . ']';
		}
		if ( $setting['type'] == 'html' && isset( $setting['html'] ) && empty( $setting['value'] ) ) {
			$setting['value'] = $setting['html'];
		} else {
			$setting['value'] = module_config::c( $setting['key'], isset( $setting['default'] ) ? $setting['default'] : false );
		}
		$fieldset_data['elements'][ $setting_id ] = array(
			'title' => $setting['description'],
			'field' => $setting,
		);
	}

	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );


	$form_actions = array(
		'class'    => 'action_bar action_bar_center action_bar_single',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'save',
				'value' => _l( 'Save settings' ),
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );


	?>

</form>