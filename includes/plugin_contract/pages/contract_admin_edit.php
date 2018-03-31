<?php

if(!$contract_safe)die('denied');

$contract_id = (int)$_REQUEST['contract_id'];
$contract = module_contract::get_contract($contract_id);
$contract_id = (int)$contract['contract_id'];
$UCMContract = UCMContract::singleton($contract_id);

$staff_members = module_user::get_staff_members();
$staff_member_rel = array();
foreach($staff_members as $staff_member){
    $staff_member_rel[$staff_member['user_id']] = $staff_member['name'];
}

if($contract_id>0 && $contract['contract_id']==$contract_id){
    $module->page_title = _l('Contract: %s',$contract['name']);
	if(function_exists('hook_handle_callback')){
		hook_handle_callback( 'timer_display', 'contract', $contract_id );
	}
}else{
    $module->page_title = _l('Contract: %s',_l('New'));
}

// check permissions.
if(class_exists('module_security',false)){
	module_security::sanatise_data('contract',$contract);
}


?>

<script type="text/javascript">

    $(function(){
        if(typeof ucm.contract != 'undefined'){
            ucm.contract.init();
        }
    });

    $(function(){

    });
</script>

<form action="" method="post" id="contract_form">
    <input type="hidden" name="_process" value="save_contract" />
	<input type="hidden" name="_redirect" value="" id="form_redirect" />
    <input type="hidden" name="contract_id" value="<?php echo $contract_id; ?>" />
    <input type="hidden" name="customer_id" value="<?php echo $contract['customer_id']; ?>" />


	<?php
	hook_handle_callback('layout_column_half',1,'35');

    // check permissions.
    $do_perm_finish_check = false; // this is a hack to allow Contract Task edit without Contract edit permissions.
    if(class_exists('module_security',false)){
        if($contract_id>0 && $contract['contract_id']==$contract_id){
            if(!module_security::check_page(array(
                'category' => 'Contract',
                'page_name' => 'Contracts',
                'module' => 'contract',
                'feature' => 'edit',
            ))){
                // user does not have edit contract perms
                $do_perm_finish_check = true;
            }
        }else{

            if(!module_security::check_page(array(
                'category' => 'Contract',
                'page_name' => 'Contracts',
                'module' => 'contract',
                'feature' => 'create',
            ))){
                // user does not have create contract perms.
            }
        }
    }

    $fields = array(
    'fields' => array(
        'name' => 'Name',
    ));
    module_form::set_required(
        $fields
    );
    module_form::prevent_exit(array(
        'valid_exits' => array(
            // selectors for the valid ways to exit this form.
            '.submit_button',
            '.delete',
            '.exit_button',
            '.apply_discount',
        ))
    );


    /**** CONTRACT DETAILS ****/
    $fieldset_data = array(
	    'id' => 'contract_details', // used for css and hooks
        'heading' => array(
            'type' => 'h3',
            'title' => 'Contract Details',
        ),
        'class' => 'tableclass tableclass_form tableclass_full',
        'elements' => array(
            'name' => array(
                'title' => 'Contract Title',
                'field' => array(
                    'type' => 'text',
                    'name' => 'name',
                    'value' => $contract['name'],
                ),
            ),
            'type' => array(
                'title' => 'Type',
                'field' => array(
                    'type' => 'select',
                    'name' => 'type',
                    'value' => $contract['type'],
                    'blank' => false,
                    'options' => module_contract::get_types(),
                    'allow_new' => true,
                ),
            ),
            'date_create' => array(
                'title' => 'Start Date',
                'field' => array(
                    'type' => 'date',
                    'name' => 'date_create',
                    'value' => print_date($contract['date_create']),
                    'help' => 'This is the date the Contract is scheduled to start work. This can be a date in the future.',
                ),
            ),
            'date_terminate' => array(
                'title' => 'End Date',
                'field' => array(
                    'type' => 'date',
                    'name' => 'date_terminate',
                    'value' => print_date($contract['date_terminate']),
                    'help' => 'This is the date the Contract is scheduled to start work. This can be a date in the future.',
                ),
            ),
            'date_approved' => array(
                'title' => 'Approved Date',
                'field' => array(
                    'type' => 'date',
                    'name' => 'date_approved',
                    'value' => print_date($contract['date_approved']),
                    'help' => 'This is the date the Contract was accepted by the client. This date is automatically set if the client clicks "Approve"',
                ),
            ),
            'approved_by' => array(
                'title' => 'Approved By',
                'field' => array(
                    'type' => 'text',
                    'name' => 'approved_by',
                    'value' => $contract['approved_by'],
                ),
            ),
        ),
        'extra_settings' => array(
            'owner_table' => 'contract',
            'owner_key' => 'contract_id',
            'owner_id' => $contract['contract_id'],
            'layout' => 'table_row',
             'allow_new' => module_extra::can_i('create','Contracts'),
             'allow_edit' => module_extra::can_i('edit','Contracts'),
        ),
    );
    if(module_config::c('contract_allow_staff_assignment',1)){
        $fieldset_data['elements']['user_id'] = array(
            'title' => 'Staff Member',
            'field' => array(
                'type' => 'select',
                'options' => $staff_member_rel,
                'name' => 'user_id',
                'value' => $contract['user_id'],
                'help' => 'Assign a staff member to this contract. You can also assign individual tasks to different staff members. Staff members are users who have EDIT permissions on Contract Tasks.',
            ),
        );
    }

    $fieldset_data['elements']['currency'] = array(
        'title' => 'Currency',
        'field' => array(
            'type' => 'select',
            'options' => get_multiple('currency','','currency_id'),
            'name' => 'currency_id',
            'value' => $contract['currency_id'],
            'options_array_id' => 'code',
        ),
    );

    // files
    if($contract_id > 0) {
	    ob_start();
	    $files = module_file::get_files( array( 'contract_id' => $contract_id ), true );
	    if ( count( $files ) > 0 ) {
		    ?>
		    <a href="<?php

		    echo module_file::link_generate( false, array(
			    'arguments' => array(
				    'contract_id' => $contract['contract_id'],
			    ),
			    'data'      => array(
				    // we do this to stop the 'customer_id' coming through
				    // so we link to the full contract page, not the customer contract page.

				    'contract_id' => $contract['contract_id'],
			    ),
		    ) );?>"><?php echo _l( 'View all %d files in this contract', count( $files ) ); ?></a>
	    <?php
	    } else {
		    echo _l( "This contract has %d files", count( $files ) );
	    }
	    echo '<br/>';
	    ?>
	    <a href="<?php echo module_file::link_generate( 'new', array(
		    'arguments' => array(
			    'contract_id' => $contract['contract_id'],
		    )
	    ) ); ?>"><?php _e( 'Add New File' ); ?></a>
	    <?php
	    $fieldset_data['elements']['files'] = array(
		    'title'  => 'Files',
		    'fields' => array(
			    ob_get_clean(),
		    ),
	    );
    }
    echo module_form::generate_fieldset($fieldset_data);
    unset($fieldset_data);

    if( (int) $contract_id > 0 ){
        $note_summary_owners = array();
        // generate a list of all possible notes we can display for this contract.
        // display all the notes which are owned by all the sites we have access to

        if(class_exists('module_note',false) && module_note::is_plugin_enabled()){
            module_note::display_notes(array(
                'title' => 'Contract Notes',
                'owner_table' => 'contract',
                'owner_id' => $contract_id,
                'view_link' => module_contract::link_open($contract_id),
                )
            );
        }


       /* if(class_exists('module_contract',false) && module_contract::is_plugin_enabled()){
            if(module_contract::can_i('edit','Contracts')){
                module_email::display_emails(array(
                    'title' => 'Contract Emails',
                    'search' => array(
                        'contract_id' => $contract_id,
                    )
                ));
            }
        }*/

        if(class_exists('module_group',false) && module_group::is_plugin_enabled()){
            module_group::display_groups(array(
                'title' => 'Contract Groups',
                'owner_table' => 'contract',
                'owner_id' => $contract_id,
                'view_link' => $module->link_open($contract_id),

            ));
        }
    }

    // run the custom data hook to display items in this particular hook location
    hook_handle_callback('custom_data_hook_location',_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_SIDEBAR, 'contract', $contract_id, $contract);

    if(module_contract::can_i('view','Contract Advanced')) {

	    /***** CONTRACT ADVANCED *****/


	    $fieldset_data = $UCMContract->generate_advanced_fieldset();





        echo module_form::generate_fieldset($fieldset_data);
        unset($fieldset_data);

    }

    $form_actions = array(
        'class' => 'action_bar action_bar_left',
        'elements' => array(
            array(
                'type' => 'save_button',
                'name' => 'butt_save',
                'onclick' => "$('#form_redirect').val('".module_contract::link_open(false)."');",
                'value' => _l('Save and Return'),
            ),
            array(
                'type' => 'save_button',
                'name' => 'butt_save',
                'value' => _l('Save'),
            ),
	        array(
		        'type' => 'save_button',
		        'class' => 'archive_button',
		        'name' => 'butt_archive',
		        'value' => $UCMContract->is_archived() ? _l('Unarchive') : _l('Archive'),
	        ),
            array(
                'ignore' => !$contract_id || !function_exists('convert_html2pdf'),
                'type' => 'submit',
                'name' => 'butt_print',
                'value' => _l('Print PDF'),
            ),
            array(
                'ignore' => !((int)$contract_id && module_contract::can_i('create','Contracts')),
                'type' => 'submit',
                'name' => 'butt_duplicate',
                'value' => _l('Duplicate'),
            ),
            array(
                'ignore' => !((int)$contract_id && module_contract::can_i('delete','Contracts')),
                'type' => 'delete_button',
                'name' => 'butt_del',
                'value' => _l('Delete'),
            ),
            array(
                'type' => 'button',
                'name' => 'cancel',
                'value' => _l('Cancel'),
                'class' => 'submit_button',
                'onclick' => "window.location.href='".module_contract::link_open(false)."';",
            ),
        ),
    );
    echo module_form::generate_form_actions($form_actions);

	if($do_perm_finish_check){
        // we call our permission check
        // render finish method here instead of in index.php
        // this allows contract task edit permissions without contract edit permissions.
        // HOPE THIS WORKS! :)
        module_security::render_page_finished();
    }

    hook_handle_callback('layout_column_half',2,'65');


    if ( $contract['date_approved'] == '0000-00-00' ) {

        ob_start();
        ?>
        <div class="tableclass_form content">
            <div style="text-align: center">
                <?php
                $form_actions = array(
                    'class'    => 'custom_actions action_bar_single',
                    'elements' => array(
                        array(
                            'type'    => 'button',
                            'name'    => 'butt_convert_to_job',
                            'value'   => _l( 'Approve this Contract' ),
                            'onclick' => "window.location.href='" . module_contract::link_public( $contract_id ) . "'; return false;",
                        ),
                    ),
                );
                echo module_form::generate_form_actions( $form_actions );
                ?>
            </div>
        </div>
        <?php
        $fieldset_data = array(
            'heading'         => array(
                'title' => _l( 'Contract Requires Approval' ),
                'type'  => 'h3',
            ),
            'elements_before' => ob_get_clean(),
        );
        echo module_form::generate_fieldset( $fieldset_data );
        unset( $fieldset_data );
    }


    $fieldset_data = array(
        'heading' => array(
            'title' => _l('Contract Description'),
            'type' => 'h3',
        ),
        'class' => 'tableclass tableclass_form tableclass_full',

    );
    if(module_contract::can_i('edit','Contracts') && $contract['date_approved'] == '0000-00-00'){
        $fieldset_data['elements'] = array(
            array(
                'field' => array(
                    'type' => 'wysiwyg',
                    'name' => 'contract_text',
                    'value' => $contract['contract_text'],
                ),
            )
        );
    }else{
        $fieldset_data['elements'] = array(
            array(
                'fields' => array(
                    module_security::purify_html($contract['contract_text']),
                ),
            )
        );
    }
    echo module_form::generate_fieldset($fieldset_data);
    unset($fieldset_data);


	if(module_contract::can_i('edit','Contracts')){
        $fieldset_data = array(
            'heading' => array(
                'title' => _l('Associated Products (optional)'),
                'type' => 'h3',
                'help' => 'Customers will be able to purchase these products through the portal area as part of their contract agreement.',
            ),
            'class' => 'tableclass tableclass_form tableclass_full',

        );
        ob_start();


		/** START TABLE LAYOUT **/


		$contract_products = $UCMContract->get_products();
		$products = module_product::get_products(array());

		$table_manager = module_theme::new_table_manager();
		$columns = array();

        $columns['bulk_action'] = array(
            'title'    => ' ',
            'callback' => function ( $product ) use ($contract_products) {
                echo '<input type="checkbox" name="assign_product[' . $product['product_id'] . ']" value="yes"' . (!empty($contract_products[$product['product_id']]) ? ' checked' : '') .'>';
            }
        );
		$columns['product_name'] = array(
			'title' => _l('Product Name'),
			'callback' => function($product){
				echo module_product::link_open($product['product_id'],true,$product);
			},
		);
		$columns['product_category_name'] = array(
			'title' => _l('Category Name'),
		);
		$columns['quantity'] = array(
			'title' => _l('Hours/Quantity'),
		);
		$columns['amount'] = array(
			'title' => _l('Amount'),
			'callback' => function($product){
				echo dollar($product['amount']);
			}
		);


		$table_manager->set_columns($columns);
		$table_manager->row_callback = function($row_data){
			// load the full vendor data before displaying each row so we have access to more details
			return module_product::get_product($row_data['product_id']);
		};
		$table_manager->set_rows($products);
		$table_manager->pagination = false;
		$table_manager->print_table();


		$fieldset_data['elements_before'] = ob_get_clean();
		echo module_form::generate_fieldset($fieldset_data);
		unset($fieldset_data);
	}

    // run the custom data hook to display items in this particular hook location
    hook_handle_callback('custom_data_hook_location',_CUSTOM_DATA_HOOK_LOCATION_CONTRACT_FOOTER, 'contract', $contract_id, $contract);


hook_handle_callback('layout_column_half','end'); ?>
</form>
