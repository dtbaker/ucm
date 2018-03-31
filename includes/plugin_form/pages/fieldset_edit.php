<?php

$fieldset_id = ! empty( $_POST['vars']['fieldset_id'] ) ? $_POST['vars']['fieldset_id'] : false;
if ( ! $fieldset_id ) {
	echo 'Invalid fieldset';
	exit;
}

module_form::print_form_open_tag( array(
	'id'      => 'edit-fieldset-settings',
	'action'  => generate_link( 'fieldset_edit', array( 'display_mode' => 'ajax' ), 'form' ),
	'ajax'    => true,
	'process' => 'save_fieldset_options',
	'hidden'  => array(
		'fieldset_id' => $fieldset_id,
	),
) );
$settings = module_form::get_fieldset_settings( $fieldset_id );

$roles            = module_security::get_roles();
$roles_attributes = array();
foreach ( $roles as $role ) {
	$roles_attributes[ $role['security_role_id'] ] = $role['name'];
}
?>
	<script type="text/javascript">
      $(function () {
          var fieldset_settings = <?php echo json_encode( $settings ); ?>;

          function fieldset_user_role_change() {
              var role_id = $('#fieldset_change_role_id').val();
              if (role_id) {
                  $("#editfieldset_fieldset").show();
                  $("#editfieldset_visibility").show();

                  $('#editfieldset :checkbox').prop('checked', true);

                  // any settings for this?
                  if (typeof fieldset_settings.roles != 'undefined' && typeof fieldset_settings.roles[role_id] != 'undefined') {
                      if (typeof fieldset_settings.roles[role_id].hidden_fieldset != 'undefined' && fieldset_settings.roles[role_id].hidden_fieldset) {
                          $('#editfieldset_visible').prop('checked', false);
                      }
                      if (typeof fieldset_settings.roles[role_id].hidden_elements != 'undefined') {
                          for (var element in fieldset_settings.roles[role_id].hidden_elements) {
                              if (fieldset_settings.roles[role_id].hidden_elements.hasOwnProperty(element) && fieldset_settings.roles[role_id].hidden_elements[element]) {
                                  $('#editfieldset_visibility_' + element).prop('checked', false);
                              }
                          }

                      }
                  }

              } else {
                  $("#editfieldset_fieldset").hide();
                  $("#editfieldset_visibility").hide();
              }
          }

          $('#fieldset_change_role_id').change(fieldset_user_role_change);
          fieldset_user_role_change();
      });
	</script>
<?php

$fieldset_data = array(
	'heading'  => array(
		'type'  => 'h3',
		'title' => 'Fieldset Settings',
	),
	'id'       => 'editfieldset',
	'editable' => false,
	'class'    => 'tableclass tableclass_form tableclass_full',
	'elements' => array(
		'user_role'  => array(
			'title'  => _l( 'User Role' ),
			'fields' => array(
				array(
					'type'    => 'select',
					'id'      => 'fieldset_change_role_id',
					'name'    => 'fieldset_role_id',
					'value'   => '',
					'options' => $roles_attributes
				)
			),
		),
		'fieldset'   => array(
			'title'  => _l( 'Entire Fieldset' ),
			'fields' => array(
				array(
					'type'    => 'flipswitch',
					'id'      => 'editfieldset_visible',
					'name'    => 'fieldset_visible',
					'label'   => 'Visible',
					'checked' => true,
				)
			),
		),
		'visibility' => array(
			'title'  => _l( 'Field Visibility' ),
			'fields' => array()
		),
	)
);
if ( ! empty( $settings['elements'] ) ) {
	foreach ( $settings['elements'] as $element_id => $label ) {
		$fieldset_data['elements']['visibility']['fields'][] = array(
			'type'    => 'flipswitch',
			'id'      => 'editfieldset_visibility_' . $element_id,
			'name'    => 'field_visibility[' . $element_id . ']',
			'label'   => $label,
			'checked' => ! empty( $settings['hidden'][ $element_id ] ) ? false : true,
		);
	}
}

echo module_form::generate_fieldset( $fieldset_data );

module_form::print_form_close_tag();