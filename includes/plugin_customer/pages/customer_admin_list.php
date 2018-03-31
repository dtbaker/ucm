<?php

$page_type = 'Customers';
$page_type_single = 'Customer';

$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : array();
$current_customer_type_id = module_customer::get_current_customer_type_id();
$search['customer_type_id'] = $current_customer_type_id;
if($current_customer_type_id > 0){
	$customer_type = module_customer::get_customer_type($current_customer_type_id);
	if($customer_type && !empty($customer_type['type_name'])){
		$page_type = $customer_type['type_name_plural'];
		$page_type_single = $customer_type['type_name'];
	}
}
if(!module_customer::can_i('view',$page_type)){
    redirect_browser(_BASE_HREF);
}
$module->page_title = _l($page_type);

$staff_members = module_user::get_staff_members();
$staff_member_rel = array();
foreach($staff_members as $staff_member){
    $staff_member_rel[$staff_member['user_id']] = $staff_member['name'];
}

$reset_archived = false;
if(!isset($search['archived_status'])){
	$reset_archived = true;
	$search['archived_status'] = _ARCHIVED_SEARCH_NONARCHIVED;
}
$customers = module_customer::get_customers($search,array('as_resource'=>true));
if($reset_archived){
	unset($search['archived_status']);
}


$header_buttons = array();
if(module_customer::can_i('create',$page_type)){
    $header_buttons[] = array(
        'url' => module_customer::link_open('new',false),
        'title' => 'Create New '.$page_type_single,
        'type' => 'add',
    );
}
if(class_exists('module_import_export',false) && module_customer::can_i('view','Import '.$page_type)){
    $header_buttons[] = array(
        'url' => module_import_export::import_link(
                array(
                    'callback'=>'module_customer::handle_import',
                    'name'=>$page_type,
                    'return_url'=>$_SERVER['REQUEST_URI'],
                    'group'=>'customer',
                    'fields'=>array(
                        $page_type_single.' ID' => 'customer_id',
                        $page_type_single.' Name' => 'customer_name',
                        'Credit' => 'credit',
                        'Address Line 1' => 'line_1',
                        'Address Line 2' => 'line_2',
                        'Address Suburb' => 'suburb',
                        'Address Country' => 'country',
                        'Address State' => 'state',
                        'Address Region' => 'region',
                        'Address Post Code' => 'post_code',
                        'Primary Contact First Name' => 'primary_user_name',
                        'Primary Contact Last Name' => 'primary_user_last_name',
                        'Primary Phone' => 'primary_user_phone',
                        'Primary Email' => 'primary_user_email',
                        'Primary Fax' => 'primary_user_fax',
                        'Primary Mobile' => 'primary_user_mobile',
                        'Primary Language' => 'primary_user_language',
                        'Invoice Prefix' => 'default_invoice_prefix',
                        'Tax Name' => 'default_tax_name',
                        'Tax Rate' => 'default_tax',
                        'Password' => 'password',
                        'User Role Name' => 'role',
                        'Notes' => 'notes',
                        'Staff' => 'customer_staff',
                    ),
                    // do we try to import extra fields?
                    'extra' => array(
                        array(
                            'owner_table' => 'customer',
                            'owner_id' => 'customer_id',
                            'owner_table_child' => $current_customer_type_id,
                        ),
                        array(
                            'owner_table' => 'user',
                            'owner_id' => 'primary_user_id',
                        ),
                    ),

	                'options' => array(
		                array(
			                'label' => 'Customer Type',
			                'form_element' => array(
				                'type' => 'select',
				                'name' => 'customer_type_id',
				                'value' => $current_customer_type_id,
				                'options' => module_customer::get_customer_types(),
				                'options_array_id' => 'type_name'
			                )
		                )
                    )
                )
            ),
        'title' => 'Import '.$page_type,
        'type' => 'add',
    );
}
if(file_exists('includes/plugin_user/pages/contact_admin_list.php') && module_user::can_i('view','All '.$page_type_single.' Contacts','Customer','customer')){
    $header_buttons[] = array(
        'url' => module_user::link_open_contact(false),
        'title' => 'View All Contacts',
    );
}

print_heading(array(
    'main' => true,
    'type' => 'h2',
    'title' => $page_type,
    'button' => $header_buttons,
));

?>


<form action="" method="post">

    <?php $search_bar = array(
        'elements' => array(
            'name' => array(
                'title' => _l('Names, Phone or Email:'),
                'field' => array(
                    'type' => 'text',
                    'name' => 'search[generic]',
                    'value' => isset($search['generic'])?$search['generic']:'',
                    'size' => 15,
                )
            ),
            'address' => array(
                'title' => _l('Address:'),
                'field' => array(
                    'type' => 'text',
                    'name' => 'search[address]',
                    'value' => isset($search['address'])?$search['address']:'',
                    'size' => 15,
                )
            ),
            'staff' => array(
                'title' => false,
                'field' => array(
                    'type' => 'select',
                    'name' => 'search[staff_id]',
                    'value' => isset($search['staff_id'])?$search['staff_id']:'',
                    'options' => $staff_member_rel,
                    'blank' => ' - '.htmlspecialchars(module_config::c('customer_staff_name','Staff')).' - ',
                )
            ),
        )
    );
    if(class_exists('module_extra',false)){
        $search_bar['extra_fields'] = array(
	        'owner_table' => 'customer',
	        'owner_table_child' => $current_customer_type_id,
        );
    }
    if(class_exists('module_group',false) && module_customer::can_i('view',$page_type_single.' Groups')){
        $search_bar['elements']['group_id'] = array(
            'title' => false,
            'field' => array(
                'type' => 'select',
                'name' => 'search[group_id]',
                'value' => isset($search['group_id'])?$search['group_id']:'',
                'options' => module_group::get_groups('customer'),
                'options_array_id' => 'name',
                'blank' => _l(' - Group - '),
            )
        );
    }
    if(class_exists('module_company',false) && module_company::can_i('view','Company') && module_company::is_enabled()){
        $companys = module_company::get_companys();
        $companys_rel = array();
        foreach($companys as $company){
            $companys_rel[$company['company_id']] = $company['name'];
        }
        $search_bar['elements']['company'] = array(
            'title' => false,
            'field' => array(
                'type' => 'select',
                'name' => 'search[company_id]',
                'value' => isset($search['company_id'])?$search['company_id']:'',
                'options' => $companys_rel,
                'blank' => _l(' - Company - '),
            )
        );
    }

    $search_bar['elements']['archived'] = array(
	    'title' => false,
	    'field' => array(
		    'type' => 'select',
		    'name' => 'search[archived_status]',
		    'value' => isset($search['archived_status'])?$search['archived_status']:'',
		    'options' => array(
			    _ARCHIVED_SEARCH_NONARCHIVED => 'Only Unarchived Customers',
			    _ARCHIVED_SEARCH_ARCHIVED => 'Only Archived Customers',
			    _ARCHIVED_SEARCH_BOTH => 'Both Unarchived and Archived',
		    ),
		    'blank' => _l(' - Archived - '),
	    )
    );
    echo module_form::search_bar($search_bar);


    /** START TABLE LAYOUT **/
    $table_manager = module_theme::new_table_manager();
    $columns = array();
    if(module_config::c('customer_have_id_numbers',0)){
	    $columns['customer_number'] = array(
		    'title' => 'ID',
	    );
    }
    if(class_exists('module_company',false) && module_company::can_i('view','Company') && module_company::is_enabled()){
        $columns['company_name'] = array(
            'title' => 'Company',
            'callback' => function($customer){
                if(isset($customer['company_ids']) && is_array($customer['company_ids']) && count($customer['company_ids'])){
                    foreach($customer['company_ids'] as $company_id=>$company_name){ ?>
                    <a href="<?php echo module_customer::link_open($customer['customer_id'],false);?>"><?php echo htmlspecialchars($company_name);?></a>
                    <?php }
                }else{
                    _e('N/A');
                }
            }
        );
    }
    $columns['customer_name'] = array(
            'title' => $page_type_single.' Name',
            'callback' => function($customer){
                echo module_customer::link_open($customer['customer_id'],true,$customer);
            },
            'cell_class' => 'row_action',
        );
    $columns['primary_contact_name'] = array(
            'title' => 'Primary Contact',
            'callback' => function($customer){
                if($customer['primary_user_id']){
					echo module_user::link_open_contact($customer['primary_user_id'],true);
				}else{
					echo '';
				}
            }
        );
    $columns['phone_number'] = array(
            'title' => 'Phone Number',
            'callback' => function($customer){
                if($customer['primary_user_id']){
					module_user::print_contact_summary($customer['primary_user_id'],'html',array('phone|mobile'));
				}else{
					echo '';
				}
            }
        );
    $columns['primary_contact_email'] = array(
            'title' => 'Email Address',
            'callback' => function($customer){
                if($customer['primary_user_id']){
					module_user::print_contact_summary($customer['primary_user_id'],'html',array('email'));
				}else{
					echo '';
				}
            }
        );
    $columns['address'] = array(
            'title' => 'Address',
            'callback' => function($customer){
                module_address::print_address($customer['customer_id'],'customer','physical');
            }
        );
    if(class_exists('module_group',false) && module_customer::can_i('view',$page_type_single.' Groups')){
        $columns['customer_group'] = array(
                'title' => 'Group',
                'callback' => function($customer){
                    if(isset($customer['group_sort_customer'])){
                        echo htmlspecialchars($customer['group_sort_customer']);
                    }else{
                        // find the groups for this customer.
                        $groups = module_group::get_groups_search(array(
                                                                      'owner_table' => 'customer',
                                                                      'owner_id' => $customer['customer_id'],
                                                                  ));
                        $g=array();
                        foreach($groups as $group){
                            $g[] = $group['name'];
                        }
                        echo htmlspecialchars(implode(', ',$g));
                    }
                }
            );
    }
    if(class_exists('module_invoice',false) && module_invoice::can_i('view','Invoices') && module_config::c('customer_list_show_invoices',1)){
        $columns['customer_invoices'] = array(
            'title' => 'Invoices',
            'callback' => function($customer){
                $invoices = module_invoice::get_invoices(array('customer_id'=>$customer['customer_id']));
                if(count($invoices)){
                    $total_due = 0;
                    $total_paid = 0;
                    foreach($invoices as $invoice){
                        $invoice = module_invoice::get_invoice($invoice['invoice_id']);
                        $total_due += $invoice['total_amount_due'];
                        $total_paid += $invoice['total_amount_paid'];
                    }
                    $old_customer_id = isset($_REQUEST['customer_id']) ? $_REQUEST['customer_id'] : false;
                    $_REQUEST['customer_id'] = $customer['customer_id'];
                    echo '<a href="'.module_invoice::link_open(false).'">'._l('%s invoice%s: %s',count($invoices),count($invoices)>1?'s':'',
                        (
                            $total_due>0
                                ?
                            '<span class="error_text">'._l('%s due',dollar($total_due,true,$invoice['currency_id'])).' </span>'
                                :
                            ''
                        ).(
                            $total_paid>0
                                ?
                            '<span class="success_text">'._l('%s paid',dollar($total_paid,true,$invoice['currency_id'])).' </span>'
                                :
                            ''
                        )
                    ).'</a>';
                    if($old_customer_id){
                        $_REQUEST['customer_id'] = $old_customer_id;
                    }else{
                        unset($_REQUEST['customer_id']);
                    }
                }
            }
        );
    }
    if(module_config::c('customer_staff_list',1)){
        $columns['customer_staff'] = array(
            'title' => htmlspecialchars(module_config::c('customer_staff_name','Staff')),
            'callback' => function($customer){
                if(isset($customer['staff_ids']) && is_array($customer['staff_ids'])){
                    foreach($customer['staff_ids'] as $staff_id){
                        echo module_user::link_open($staff_id,true);
                    }
                }
            }
        );
    }
    $table_manager->set_id('customer_list');
    $table_manager->enable_group_option(array(
                'fields'=>array(
                    'owner_id' => 'customer_id',
                    'owner_table' => 'customer',
                    'title' => $page_type_single.' Groups',
                    'name' => 'customer_name',
                    'email' => 'primary_user_email'
                ),
            )
    );

    if(class_exists('module_extra',false)){
        // do extra before "table sorting" so that it can hook in with the table sort call
        $table_manager->display_extra('customer',function($customer) use ($current_customer_type_id){
            module_extra::print_table_data('customer',$customer['customer_id'],$current_customer_type_id);
        },'customer_id');
        $table_manager->display_extra('user',function($customer) use ($current_customer_type_id){
            module_extra::print_table_data('user',$customer['primary_user_id'],$current_customer_type_id);
        },'primary_user_id');
    }
    $table_manager->enable_table_sorting(array(
            'table_id' => 'customer_list',
            'sortable'=>array(
                // these are the "ID" values of the <th> in our table.
                // we use jquery to add the up/down arrows after page loads.
                'customer_name' => array(
                    'field' => 'customer_name',
                    //'current' => 1, // 1 asc, 2 desc
                ),
                'primary_contact_name' => array(
                    'field' => 'primary_user_name',
                ),
                'primary_contact_email' => array(
                    'field' => 'primary_user_email',
                ),
                // special case for group sorting.
                'customer_group' => array(
                    'group_sort' => true,
                    'owner_table' => 'customer',
                    'owner_id' => 'customer_id',
                ),
                /*// special case for extra field sorting.
                'extra_customer' => array(
                    'extra_sort' => true,
                    'owner_table' => 'customer',
                    'owner_id' => 'customer_id',
                ),
                'extra_user' => array(
                    'extra_sort' => true,
                    'owner_table' => 'user',
                    'owner_id' => 'primary_user_id',
                ),*/
            ),
        )
    );
    if(module_customer::can_i('view','Export '.$page_type)) {
        $table_manager->enable_export( array(
                'name'   => $page_type_single . ' Export',
                'fields' => array(
                    $page_type_single . ' ID'    => 'customer_id',
                    $page_type_single . ' Name'  => 'customer_name',
                    'Credit'                     => 'credit',
                    'Address Line 1'             => 'line_1',
                    'Address Line 2'             => 'line_2',
                    'Address Suburb'             => 'suburb',
                    'Address Country'            => 'country',
                    'Address State'              => 'state',
                    'Address Region'             => 'region',
                    'Address Post Code'          => 'post_code',
                    'Primary Contact First Name' => 'primary_user_name',
                    'Primary Contact Last Name'  => 'primary_user_last_name',
                    'Primary Phone'              => 'primary_user_phone',
                    'Primary Email'              => 'primary_user_email',
                    'Primary Fax'                => 'primary_user_fax',
                    'Primary Mobile'             => 'primary_user_mobile',
                    'Primary Language'           => 'primary_user_language',
                    'Invoice Prefix'             => 'default_invoice_prefix',
                    'Tax Name'                   => 'default_tax_name',
                    'Tax Rate'                   => 'default_tax',
                    'Staff'                      => 'customer_staff',
                ),
                // do we look for extra fields?
                'extra'  => array(
                    array(
                        'owner_table' => 'customer',
                        'owner_table_child' => $current_customer_type_id,
                        'owner_id'    => 'customer_id',
                    ),
                    array(
                        'owner_table' => 'user',
                        'owner_id'    => 'primary_user_id',
                    ),
                ),
                'group'  => array(
                    array(
                        'title'       => $page_type_single . ' Group',
                        'owner_table' => 'customer',
                        'owner_id'    => 'customer_id',
                    )
                ),
            )
        );
    }
    $table_manager->set_columns($columns);
    $table_manager->row_callback = function($row_data){
        // load the full customer data before displaying each row so we have access to more details
        return module_customer::get_customer($row_data['customer_id']);
    };
    $table_manager->set_rows($customers);
    $table_manager->pagination = true;
    $table_manager->print_table();
    /** END TABLE LAYOUT **/

?>
</form>