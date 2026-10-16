<?php

if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() && module_user::can_i( 'edit', 'User' ) ) {
	$heading = array(
		'type'  => 'h3',
		'title' => 'Assigned Company',
	);
	if ( module_company::can_i( 'edit', 'Company' ) ) {
		$help_text         = addcslashes( _l( "Here you can select which Company this Staff belongs to. This is handy if you are running multiple companies through this system and you would like to separate customers between different companies. Staff can be restricted to assigned companies from the User Role" ), "'" );
		$heading['button'] = array(
			'url'     => '#',
			'onclick' => "alert('$help_text'); return false;",
			'title'   => 'help',
		);
	}
	ob_start();
	$companys = module_company::get_companys();
	foreach ( $companys as $company ) { ?>
		<?php if ( module_company::can_i( 'edit', 'Company' ) ) { ?>
			<input type="hidden" name="available_user_company[<?php echo $company['company_id']; ?>]" value="1">
			<input type="checkbox" name="user_company[<?php echo $company['company_id']; ?>]"
			       id="customer_company_<?php echo $company['company_id']; ?>"
			       value="<?php echo $company['company_id']; ?>" <?php echo isset( $user['company_ids'] ) && isset( $user['company_ids'][ $company['company_id'] ] ) ? ' checked="checked" ' : ''; ?>>
		<?php } ?>
		<label
			for="customer_company_<?php echo $company['company_id']; ?>"><?php echo htmlspecialchars( $company['name'] ); ?></label>
	<?php } ?>
	<?php
	$company_checkboxes = ob_get_clean();

	$fieldset_data = array(
		'heading'  => $heading,
		// tableclass_full is not in the fieldset default class, so it is spelled out here.
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			'company' => array(
				'title'  => 'Company',
				'fields' => array( $company_checkboxes ),
			),
		),
	);

	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

}
