<?php

if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$company_id = (int) $_REQUEST['company_id'];
$company    = array();
if ( $company_id > 0 ) {

	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'category'  => 'Company',
			'page_name' => 'Company',
			'module'    => 'company',
			'feature'   => 'edit',
		) );
	}
	$company = module_company::get_company( $company_id );
} else {
}
if ( ! $company ) {
	$company_id = 'new';
	$company    = array(
		'company_id' => 'new',
		'name'       => '',
	);
	module_security::sanatise_data( 'company', $company );
}
?>
<form action="" method="post">

	<input type="hidden" name="_process" value="save_company"/>
	<input type="hidden" name="company_id" value="<?php echo $company_id; ?>"/>

	<?php
	module_form::print_form_auth();
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);


	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => 'Company Details',
		),
		'elements' => array(
			array(
				'title' => _l( 'Company Name' ),
				'field' => array(
					'name'  => 'name',
					'value' => $company['name'],
					'type'  => 'text',
				)
			)
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
				'value' => _l( 'Save' ),
			),
			array(
				'ignore' => ! ( (int) $company_id > 0 ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . $module->link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );
	?>


</form>
