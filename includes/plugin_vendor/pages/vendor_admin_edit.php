<?php


$page_type        = 'Vendors';
$page_type_single = 'Vendor';

if ( ! module_vendor::can_i( 'view', $page_type ) ) {
	redirect_browser( _BASE_HREF );
}


$vendor_id = (int) $_REQUEST['vendor_id'];
$vendor    = array();

$vendor = module_vendor::get_vendor( $vendor_id );

if ( $vendor_id > 0 && $vendor['vendor_id'] == $vendor_id ) {
	$module->page_title = _l( $page_type_single . ': %s', $vendor['vendor_name'] );
} else {
	$module->page_title = _l( $page_type_single . ': %s', _l( 'New' ) );
}
// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $vendor_id > 0 && $vendor['vendor_id'] == $vendor_id ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Vendor',
			'page_name' => $page_type,
			'module'    => 'vendor',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Vendor',
			'page_name' => $page_type,
			'module'    => 'vendor',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'vendor', $vendor );
}


?>
<form action="" method="post" id="vendor_form">
	<input type="hidden" name="_process" value="save_vendor"/>
	<input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>"/>
	<input type="hidden" name="_redirect" value="" id="form_redirect"/>

	<?php
	module_form::set_required( array(
			'fields' => array(
				'vendor_name' => 'Name',
				'name'        => 'Contact Name',
			)
		)
	);
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);

	module_form::print_form_auth();

	//!(int)$vendor['vendor_id'] &&
	if ( isset( $_REQUEST['move_user_id'] ) && (int) $_REQUEST['move_user_id'] > 0 && module_vendor::can_i( 'create', 'Vendors' ) ) {
		// we have to move this contact over to this vendor as a new primary user id
		$vendor['primary_user_id'] = (int) $_REQUEST['move_user_id'];
		?>
		<input type="hidden" name="move_user_id" value="<?php echo $vendor['primary_user_id']; ?>">
		<?php
	}

	hook_handle_callback( 'layout_column_half', 1 );

	/** COMPANY INFORMATION **/

	if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
		$heading = array(
			'type'  => 'h3',
			'title' => 'Company Information',
		);
		if ( module_company::can_i( 'edit', 'Company' ) ) {
			$help_text         = addcslashes( _l( "Here you can select which Company this Vendor belongs to. This is handy if you are running multiple companies through this system and you would like to separate vendors between different companies." ), "'" );
			$heading['button'] = array(
				'url'     => '#',
				'onclick' => "alert('$help_text'); return false;",
				'title'   => 'help',
			);
		}
		//print_heading($heading);
		$company_fields = array();
		$companys       = module_company::get_companys();
		foreach ( $companys as $company ) {
			$company_fields[] = array(
				'type'  => 'hidden',
				'name'  => "available_vendor_company[" . $company['company_id'] . "]",
				'value' => 1,
			);
			$company_fields[] = array(
				'type'    => 'check',
				'name'    => "vendor_company[" . $company['company_id'] . "]",
				'value'   => $company['company_id'],
				'checked' => isset( $vendor['company_ids'][ $company['company_id'] ] ) || ( ! $vendor_id && ! module_company::can_i( 'edit', 'Company' ) ),
				'label'   => htmlspecialchars( $company['name'] ),
			);
		}
		$fieldset_data = array(
			'heading'  => $heading,
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(
				'company' => array(
					'title'  => _l( 'Company' ),
					'fields' => $company_fields,
				),
			)
		);
		echo module_form::generate_fieldset( $fieldset_data );
	}

	/** VENDOR INFORMATION **/

	$fieldset_data = array(
		'heading'        => array(
			'type'  => 'h3',
			'title' => $page_type_single . ' Information',
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(
			'name' => array(
				'title' => _l( 'Name' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'vendor_name',
					'value' => $vendor['vendor_name'],
				),
			),
		),
		'extra_settings' => array(
			'owner_table' => 'vendor',
			'owner_key'   => 'vendor_id',
			'owner_id'    => $vendor_id,
			'layout'      => 'table_row',
			'allow_new'   => module_vendor::can_i( 'create', $page_type ),
			'allow_edit'  => module_vendor::can_i( 'create', $page_type ),
		),
	);
	if ( $vendor_id && $vendor_id != 'new' && class_exists( 'module_file' ) && module_file::is_plugin_enabled() ) {
		ob_start();
		module_file::display_files( array(
				//'title' => 'Certificate Files',
				'owner_table' => 'vendor',
				'owner_id'    => $vendor_id,
				//'layout' => 'list',
				'layout'      => 'gallery',
				'editable'    => module_security::is_page_editable(),
			)
		);
		$fieldset_data['elements']['logo'] = array(
			'title' => _l( 'Logo' ),
			'field' => ob_get_clean(),
		);
	}

	echo module_form::generate_fieldset( $fieldset_data );


	/** PRIMARY CONTACT DETAILS **/

	// we use the "user" module to find the user details
	// for the currently selected primary contact id
	if ( $vendor['primary_user_id'] ) {

		if ( ! module_user::can_i( 'view', 'All ' . $page_type_single . ' Contacts', 'Vendor', 'vendor' ) && $vendor['primary_user_id'] != module_security::get_loggedin_id() ) {
			ob_start();
			echo '<div class="content_box_wheader"><table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form"><tbody><tr><td>';
			_e( 'Details hidden' );
			echo '</td></tr></tbody></table></div>';
			$fieldset_data = array(
				'heading'         => array(
					'type'  => 'h3',
					'title' => 'Primary Contact Details',
				),
				'class'           => 'tableclass tableclass_form tableclass_full',
				'elements_before' => ob_get_clean(),
			);
			if ( $vendor['primary_user_id'] ) {
				$fieldset_data['heading']['button'] = array(
					'title' => 'More',
					'url'   => module_user::link_open_contact( $vendor['primary_user_id'], false )
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		} else if ( ! module_user::can_i( 'edit', 'All ' . $page_type_single . ' Contacts', 'Vendor', 'vendor' ) && $vendor['primary_user_id'] != module_security::get_loggedin_id() ) {
			ob_start();
			// no permissions to edit.
			echo '<div class="content_box_wheader"><table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form"><tbody><tr><td>';
			module_user::print_contact_summary( $vendor['primary_user_id'], 'text', array( 'name', 'last_name', 'email' ) );
			echo '</td></tr></tbody></table></div>';
			$fieldset_data = array(
				'heading'         => array(
					'type'  => 'h3',
					'title' => 'Primary Contact Details',
				),
				'class'           => 'tableclass tableclass_form tableclass_full',
				'elements_before' => ob_get_clean(),
			);
			if ( $vendor['primary_user_id'] ) {
				$fieldset_data['heading']['button'] = array(
					'title' => 'More',
					'url'   => module_user::link_open_contact( $vendor['primary_user_id'], false )
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		} else {
			module_user::print_contact_form( $vendor['primary_user_id'] );
		}
	} else {
		// hack to create new contact details.
		module_user::print_contact_form( false );
	}


	/*** ADDRESS **/
	if ( class_exists( 'module_address', false ) ) {
		module_address::print_address_form( $vendor_id, 'vendor', 'physical', 'Address' );
	}

	/** ADVANCED AREA **/
	/*
			$fieldset_data = array(
					'heading' => array(
							'type' => 'h3',
							'title' => 'Advanced',
					),
					'class' => 'tableclass tableclass_form tableclass_full',
					'elements' => array(),
			);
	
			if(module_vendor::can_i('edit','Vendor Credit')){
					$fieldset_data['elements']['credit'] = array(
							'title' => _l('Credit'),
							'fields' => array(
									array(
											'type' => 'currency',
											'name' => 'credit',
											'value' => number_out($vendor['credit']),
											'help' => 'If the vendor is given a credit here you will have an option to apply this credit to an invoice. If a vendor over pays an invoice you will be prompted to add that overpayment as credit onto their account.',
									),
							)
					);
			}
			if(module_invoice::can_i('edit','Invoices')){
					if(isset($vendor['default_tax'])){
							$fieldset_data['elements']['default_tax'] = array(
									'title' => _l('Default Tax'),
									'fields' => array(
											array(
													'type' => 'check',
													'name' => 'default_tax_system',
													'checked' => $vendor['default_tax']<0,
													'value' => 1,
											),
											_l('Use system default (%s @ %s%%)',module_config::c('tax_name','TAX'),module_config::c('tax_percent',10)),
											'<br/>',
											_l('Or custom tax:'),
											array(
													'type' => 'text',
													'name' => 'default_tax_name',
													'value' => $vendor['default_tax_name'],
													'style' => 'width:30px;',
											),
											' @ ',
											array(
													'type' => 'text',
													'name' => 'default_tax',
													'value' => $vendor['default_tax']>=0 ? $vendor['default_tax'] : '',
													'style' => 'width:35px;',
											),
											'%',
											_hr('If your vendor needs a deafult tax rate that is different from the system default please enter it here.')
									)
							);
					}
					if(isset($vendor['default_invoice_prefix'])){
							$fieldset_data['elements']['invoice_prefix'] = array(
									'title' => _l('Invoice Prefix'),
									'fields' => array(
											array(
													'type' => 'text',
													'name' => 'default_invoice_prefix',
													'value' => $vendor['default_invoice_prefix'],
													'help' => 'Every time an invoice is generated for this vendor the INVOICE NUMBER will be prefixed with this value.',
													'size' => 5,
											),
									)
							);
					}
			}
	
			echo module_form::generate_fieldset($fieldset_data);
	*/

	hook_handle_callback( 'layout_column_half', 2 );


	if ( $vendor_id && $vendor_id != 'new' ) {

		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			module_group::display_groups( array(
				'title'       => $page_type_single . ' Groups',
				'owner_table' => 'vendor',
				'owner_id'    => $vendor_id,
				'view_link'   => $module->link_open( $vendor_id ),

			) );
		}

		$note_summary_owners = array();
		// generate a list of all possible notes we can display for this vendor.
		// display all the notes which are owned by all the sites we have access to

		// display all the notes which are owned by all the users we have access to
		foreach ( module_user::get_contacts( array( 'vendor_id' => $vendor_id ) ) as $val ) {
			$note_summary_owners['user'][] = $val['user_id'];
		}
		/*if(class_exists('module_website',false) && module_website::is_plugin_enabled()){
				foreach(module_website::get_websites(array('vendor_id'=>$vendor_id)) as $val){
						$note_summary_owners['website'][] = $val['website_id'];
				}
		}
		if(class_exists('module_job',false) && module_job::is_plugin_enabled()){
				foreach(module_job::get_jobs(array('vendor_id'=>$vendor_id)) as $val){
						$note_summary_owners['job'][] = $val['job_id'];
						foreach(module_invoice::get_invoices(array('job_id'=>$val['job_id'])) as $val){
								$note_summary_owners['invoice'][$val['invoice_id']] = $val['invoice_id'];
						}
				}
		}
		if(class_exists('module_invoice',false) && module_invoice::is_plugin_enabled()){
				foreach(module_invoice::get_invoices(array('vendor_id'=>$vendor_id)) as $val){
						$note_summary_owners['invoice'][$val['invoice_id']] = $val['invoice_id'];
				}
		}*/
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::display_notes( array(
					'title'           => 'All ' . $page_type_single . ' Notes',
					'owner_table'     => 'vendor',
					'owner_id'        => $vendor_id,
					'view_link'       => $module->link_open( $vendor_id ),
					'display_summary' => true,
					'summary_owners'  => $note_summary_owners
				)
			);
		}


	}
	hook_handle_callback( 'vendor_edit', $vendor_id );

	hook_handle_callback( 'layout_column_half', 'end' );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'    => 'save_button',
				'name'    => 'butt_save',
				'onclick' => "$('#form_redirect').val('" . $module->link_open( false ) . "');",
				'value'   => _l( 'Save and Return' ),
			),
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'ignore' => ! ( module_vendor::can_i( 'delete', 'Vendors' ) && $vendor_id > 0 ),
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

