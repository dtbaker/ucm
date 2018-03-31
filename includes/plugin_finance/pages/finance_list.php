<?php


if(!module_finance::can_i('view','Finance')){
    redirect_browser(_BASE_HREF);
}

$search = isset($_REQUEST['search']) ? $_REQUEST['search'] : array();
if(!isset($search['date_from']) && module_config::c('finance_default_search_date',30)){
    $search['date_from'] = print_date(strtotime('-'.module_config::c('finance_default_search_date',30).' days'));
}
$recent_transactions = module_finance::get_finances($search);
/*
$total_debit = $total_credit = 0;
foreach($recent_transactions as $recent_transaction){
    $total_credit += $recent_transaction['credit'];
    $total_debit += $recent_transaction['debit'];
}
*/


if(class_exists('module_table_sort',false)){
    module_table_sort::enable_pagination_hook(
    // pass in the sortable options.
    /*="sort_date"><?php echo _l('Date'); ?></th>
                    <th id="sort_name"><?php echo _l('Name'); ?></th>
                    <th><?php echo _l('Description'); ?></th>
                    <th id="sort_credit"><?php echo _l('Credit'); ?></th>
                    <th id="sort_debit"><?php echo _l('Debit'); ?></th>
                    <th id="sort_account"><?p*/
        array(
            'table_id' => 'finance_list',
            'sortable'=>array(
                // these are the "ID" values of the <th> in our table.
                // we use jquery to add the up/down arrows after page loads.
                'sort_date' => array(
                    'field' => 'transaction_date',
                    'current' => 2, // 1 asc, 2 desc
                ),
                'sort_name' => array(
                    'field' => 'name',
                ),
                'sort_credit' => array(
                    'field' => 'credit',
                ),
                'sort_debit' => array(
                    'field' => 'debit',
                ),
            ),
        )
    );
}


// calculate totals at the bottom
$show_excluded_payment_info = false;
if(module_config::c('finance_list_show_totals',1)){
    $finance_credit_total = array();
    $finance_debit_total = array();
    foreach($recent_transactions as $finance){
	    if(isset($finance['payment_type'])){
		    if($finance['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT || $finance['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT){
			    // ADD THIS CODE TO get_finance_summary() in finance.php too!
			    // ADD THIS CODE TO ststistic_tax.php too!
			    // dont add these ones to the totals at thebottom, mark then with asterix so people know.
			    $show_excluded_payment_info = true;
			    continue;
		    }
	    }
        if(!isset($finance_credit_total[$finance['currency_id']])){
            $finance_credit_total[$finance['currency_id']] = 0;
        }
        $finance_credit_total[$finance['currency_id']] += $finance['credit'];
        if(!isset($finance_debit_total[$finance['currency_id']])){
            $finance_debit_total[$finance['currency_id']] = 0;
        }
        $finance_debit_total[$finance['currency_id']] += $finance['debit'];
    }
}


// hack to add a "export" option to the pagination results.
if(class_exists('module_import_export',false) && module_finance::can_i('view','Export Finance')){
    $export_settings =         array(
            'name' => 'Finance Export',
            'parent_form' => 'finance_form',
            'fields'=>array(
                'Date' => 'transaction_date',
                'Name' => 'name',
                'URL' => 'url',
                'Description' => 'description',
                'Credit' => 'credit',
                'Debit' => 'debit',
                'Account' => 'account_name',
                'Categories' => 'categories',
            ),
            'summary' => array(
    
            )
        );
    
    if(module_config::c('finance_list_show_totals',1)){
        foreach($finance_debit_total + $finance_credit_total as $currency_id => $foo){
        $currency = get_single('currency','currency_id',$currency_id);
            $export_settings['summary'][] = array(
                'description'=>_l('%s Totals:',$currency && isset($currency['code']) ? $currency['code'] : ''),
                'credit' => dollar(isset($finance_credit_total[$currency_id]) ? $finance_credit_total[$currency_id] : 0,true,$currency_id),
                'debit' => dollar(isset($finance_debit_total[$currency_id]) ? $finance_debit_total[$currency_id] : 0,true,$currency_id),
            );
         }
    }

    module_import_export::enable_pagination_hook(
    // what fields do we pass to the import_export module from this customers?
    $export_settings
    );
}

$upcoming_finances = array();

print_heading(array(
    'title' => _l('Financial Transactions'),
    'type' => 'h2',
    'main' => true,
    'button' => array(
        'title' => _l('Add New'),
        'url' => module_finance::link_open('new'),
        'type' => 'add',
    )
));
?>


<form action="" method="post" id="finance_form" class="search_form">

    <?php
    $categories_rel = array();
    foreach(module_finance::get_categories() as $category){
        $categories_rel[$category['finance_category_id']] = $category['name'];
    }
    $accounts_rel = array();
    foreach(module_finance::get_accounts() as $account){
        $accounts_rel[$account['finance_account_id']] = $account['name'];
    }
    $search_bar = array(
        'elements' => array(
            'name' => array(
                'title' => _l('Name:'),
                'field' => array(
                    'type' => 'text',
                    'name' => 'search[generic]',
                    'value' => isset($search['generic'])?$search['generic']:'',
                    'size' => 15,
                )
            ),
            'due_date' => array(
                'title' => _l('Date:'),
                'fields' => array(
                    array(
                        'type' => 'date',
                        'name' => 'search[date_from]',
                        'value' => isset($search['date_from'])?$search['date_from']:'',
                    ),
                    _l('to'),
                    array(
                        'type' => 'date',
                        'name' => 'search[date_to]',
                        'value' => isset($search['date_to'])?$search['date_to']:'',
                    ),
                )
            ),
            'amount' => array(
                'title' => _l('Amount:'),
                'fields' => array(
                    array(
                        'type' => 'currency',
                        'name' => 'search[amount_from]',
                        'value' => isset($search['amount_from'])?$search['amount_from']:'',
                    ),
                    _l('to'),
                    array(
                        'type' => 'currency',
                        'name' => 'search[amount_to]',
                        'value' => isset($search['amount_to'])?$search['amount_to']:'',
                    ),
                )
            ),
            'account' => array(
                'title' => false,
                'field' => array(
                    'type' => 'select',
                    'name' => 'search[finance_account_id][]',
                    'values' => isset($search['finance_account_id'])?$search['finance_account_id']:'',
                    'value' => '',
                    'options' => $accounts_rel,
                    'blank' => _l(' - Account - '),
                    'multiple' => true,
                )
            ),
            'category' => array(
                'title' => false,
                'field' => array(
                    'type' => 'select',
                    'name' => 'search[finance_category_id][]',
                    'values' => isset($search['finance_category_id'])?$search['finance_category_id']:'',
                    'value' => '',
                    'options' => $categories_rel,
                    'blank' => _l(' - Category - '),
                    'multiple' => true,
                )
            ),
        )
    );
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

    if(class_exists('module_extra',false)){
	    $search_bar['extra_fields'] = array(
		    'owner_table' => 'finance',
	    );
    }
    echo module_form::search_bar($search_bar); ?>


</form>

<script type="text/javascript">
    function link_it(t){
        // select all others of this same credit/debit price
        $('.link_box').show();
        $('.link_box').each(function(){
            if(t && $(this).val() != t){
                $(this).hide();
            }
        });
    }
    $(function(){
        $('.link_box').each(function(){
            $(this).change(function(){
                link_it( $(this)[0].checked ? $(this).val() : false );
            });
            $(this).mouseup(function(){
                link_it( $(this)[0].checked ? $(this).val() : false );
            });
        });
    });
</script>


<form action="" method="post" id="quick_add_form">
    <input type="hidden" name="_process" value="quick_save_finance">
    <input type="hidden" name="finance_id" value="new">
        <?php module_form::set_default_field('new_transaction_name');



$table_manager = module_theme::new_table_manager();
$columns = array();
$columns['sort_date'] = array(
    'title' => 'Date',
    'callback' => function(&$finance){
	    if(!isset($finance['transaction_date']))return false;
        // loop over all finance records and print the values out, only if they differ.
        // only print dates if they differ from the others.
        $dates = array();
        //$links = array();
        $dates[print_date($finance['transaction_date'])]=true;
        //$links[$finance['url']]=!trim($finance['name']) ? 'N/A' :    htmlspecialchars($finance['name']);
        if($finance['finance_record']){
	        if(isset($finance['finance_record']['linked_finances'])){
	            foreach($finance['finance_record']['linked_finances'] as $f){
	                $dates[print_date($f['transaction_date'])]=true;
	            }
            }
	        if(isset($finance['finance_record']['linked_invoice_payments'])){
	            foreach($finance['finance_record']['linked_invoice_payments'] as $f){
	                $dates[print_date($f['transaction_date'])]=true;
	            }
            }
        }
        echo implode(', ',array_keys($dates));
    },
    'cell_class' => 'row_action',
);
$columns['sort_name'] = array(
    'title' => 'Name',
    'callback' => function($finance){
	    if(!isset($finance['transaction_date']))return false;
	    ?> <a href="<?php echo $finance['url'];?>"><?php echo !trim($finance['name']) ? 'N/A' :    htmlspecialchars($finance['name']);?></a> <?php
    },
);
$columns['finance_description'] = array(
    'title' => 'Description',
    'callback' => function($finance){
	    if(!isset($finance['transaction_date']))return false;
		$descriptions = array();
        if(strlen($finance['description'])){
            $descriptions[preg_replace('#\s+#','',strip_tags($finance['description']))] = $finance['description'];
        }
        if($finance['finance_record']){
	        if(isset($finance['finance_record']['linked_finances'])){
	            foreach($finance['finance_record']['linked_finances'] as $f){
	                if(strlen($f['description'])){
	                    $descriptions[preg_replace('#\s+#','',strip_tags($f['description']))] = $f['description'];
	                }
	            }
            }
	        if(isset($finance['finance_record']['linked_invoice_payments'])){
	            foreach($finance['finance_record']['linked_invoice_payments'] as $f){
	                if(strlen($f['description'])){
	                    $descriptions[preg_replace('#\s+#','',strip_tags($f['description']))] = $f['description'];
	                }
	            }
            }
        }
        echo implode('<br>',$descriptions);
    },
);
$columns['finance_customer'] = array(
    'title' => 'Customer',
    'callback' => function($finance){
	    if(!isset($finance['transaction_date']))return false;
		echo isset($finance['customer_id']) && $finance['customer_id'] ? module_customer::link_open($finance['customer_id'],true) : '';
    },
);
$columns['sort_credit'] = array(
	'title'    => 'Credit',
	'callback' => function ( $finance ) {
		$info = '';
		if($finance['credit'] > 0 && isset($finance['payment_type'])){
		    if($finance['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT || $finance['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT){
			    // dont add these ones to the totals at thebottom, mark then with asterix so people know.
			    $info = ' *';
		    }
	    }
	    if(!isset($finance['transaction_date']))return false;
		?> <span class="success_text"><?php echo $finance['credit'] > 0 ? '+'.dollar($finance['credit'],true,$finance['currency_id']) : ''; echo $info;?></span> <?php
	},
);
$columns['sort_debit'] = array(
	'title'    => 'Debit',
	'callback' => function ( $finance ) {
		$info = '';
		if($finance['debit'] > 0 && isset($finance['payment_type'])){
		    if($finance['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT || $finance['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT){
			    // dont add these ones to the totals at thebottom, mark then with asterix so people know.
			    $info = ' *';
		    }
	    }
	    if(!isset($finance['transaction_date']))return false;
		?> <span class="error_text"><?php echo $finance['debit'] > 0 ? '-'.dollar($finance['debit'],true,$finance['currency_id']) : ''; echo $info;?></span> <?php
	},
);
$columns['sort_account'] = array(
	'title'    => 'Account',
	'callback' => function ( $finance ) {
	    if(!isset($finance['transaction_date']))return false;
		echo isset($finance['account_name']) ? htmlspecialchars($finance['account_name']) : '';
	},
);
$columns['finance_categories'] = array(
	'title'    => 'Categories',
	'callback' => function ( $finance ) {
	    if(!isset($finance['transaction_date']))return false;
		echo isset($finance['categories']) ? $finance['categories'] : '';
	},
);
$columns['finance_tick'] = array(
	'title'    => ' ',
	'callback' => function ( $finance ) {
	    if(!isset($finance['transaction_date']))return false;
		if(isset($finance['invoice_payment_id']) && $finance['invoice_payment_id'] && isset($finance['invoice_id']) && $finance['invoice_id']){ ?>
            <input type="checkbox" name="link_invoice_payment_ids[<?php echo $finance['invoice_payment_id'];?>]" value="<?php echo number_format($finance['credit'],2).'_'.number_format($finance['debit'],2);?>" class="link_box">
        <?php }else if(isset($finance['finance_id'])){ ?>
            <input type="checkbox" name="link_finance_ids[<?php echo $finance['finance_id'];?>]" value="<?php echo number_format($finance['credit'],2).'_'.number_format($finance['debit'],2);?>" class="link_box">
        <?php }
	},
);
if(class_exists('module_extra',false)){
    $table_manager->display_extra('finance',function($finance){
        module_extra::print_table_data('finance',isset($finance['finance_id']) ? $finance['finance_id'] : 0);
    });
}
$table_manager->set_columns($columns);
$table_manager->row_callback = function($finance){
    if(isset($finance['finance_id']) && $finance['finance_id']){
        $finance['finance_record'] = module_finance::get_finance($finance['finance_id']);
    }else{
        $finance['finance_record'] = false;
    }
    return $finance;
};
if(module_finance::can_i('create','Finance')){
	$header_rows = array();
	ob_start();
	?>
	<div style="height:18px; width:89px; overflow: hidden; position: absolute; background: #FFFFFF;" onmouseover="$(this).height('auto');$(this).width('auto');" onmouseout="$(this).height('18px');$(this).width('89px');">
                    <?php
                    $categories = module_finance::get_categories();
                    foreach($categories as $category){ ?>
                        <input type="checkbox" name="finance_category_id[]" value="<?php echo $category['finance_category_id'];?>" id="category_<?php echo $category['finance_category_id'];?>" <?php echo isset($finance['category_ids'][$category['finance_category_id']]) ? ' checked' : '';?>>
                        <label for="category_<?php echo $category['finance_category_id'];?>"><?php echo htmlspecialchars($category['name']);?></label> <br/>
                        <?php }
                    ?>
                    <input type="checkbox" name="finance_category_new_checked" value="new">
                    <input type="text" name="finance_category_new" value="">
                </div> &nbsp;
	<?php
	$header_cats = ob_get_clean();
    $header_rows[] = array(
        'sort_date' => array(
            'data' => '<input type="text" name="transaction_date" class="date_field" value="'.print_date(time()).'"> ',
        ),
	    'sort_name' => array(
		    'data' => '<input type="text" name="name" id="new_transaction_name">',
	    ),
	    'finance_description' => array(
		    'data' => '<input type="text" name="description">',
	    ),
        'finance_customer' => array(
            'data' => '',
        ),
        'sort_credit' => array(
            'data' =>  currency('') .'<input type="text" name="credit" class="currency">',
            'cell_class' => 'success_text',
        ),
        'sort_debit' => array(
            'data' =>  currency('') .'<input type="text" name="debit" class="currency">',
            'cell_class' => 'error_text',
        ),
        'sort_account' => array(
            'data' => print_select_box(module_finance::get_accounts(),'finance_account_id','','',true,'name',array(
	            'table' => 'finance_account',
	            'column' => 'name',
	            'index' => 'finance_account_id',
	            'rel' => array('finance','finance_recurring')
	            )),
        ),
        'finance_categories' => array(
            'data' => $header_cats,
        ),
        'finance_tick' => array(
            'data' => '<input type="submit" name="addnew" value="'. _l('Quick Add').'" class="small_button">',
	        //'cell_colspan' => $extra_cols+1,
        ),
    );

    $table_manager->set_header_rows($header_rows);
}
$table_manager->set_rows($recent_transactions);
if(module_config::c('finance_list_show_totals',1)){
	$footer_rows = array();
    foreach($finance_credit_total + $finance_debit_total as $currency_id => $foo){
        $currency = get_single('currency','currency_id',$currency_id);
        $footer_rows[] = array(
            'sort_date' => array(
                'data' => ' ',
                'cell_colspan' => 3,
            ),
            'finance_customer' => array(
                'data' => '<strong>'._l('%s Totals:',$currency && isset($currency['code']) ? $currency['code'] : '').'</strong>',
                'cell_class' => 'text-right',
            ),
            'sort_credit' => array(
                'data' => '<strong>'.dollar(isset($finance_credit_total[$currency_id]) ? $finance_credit_total[$currency_id] : 0,true,$currency_id).'</strong>',
            ),
            'sort_debit' => array(
                'data' => '<strong>'.dollar(isset($finance_debit_total[$currency_id]) ? $finance_debit_total[$currency_id] : 0,true,$currency_id).'</strong>',
            ),
            'sort_account' => array(
                'data' => ' ',
                'cell_colspan' => 4
            ),
        );
    }
	$footer_rows[] = array(
        'sort_date' => array(
            'data' => ' ',
            'cell_colspan' => 8,
        ),
		'finance_tick' => array(
			'data' => '<input type="button" name="link" value="'. _l('Link').'" class="small_button" onclick="$(\'#link_go\').val(\'go\'); $(\'#quick_add_form\')[0].submit();">
						<input type="hidden" name="link_go" value="0" id="link_go">
                        ' . _hr('Combine transactions together. eg: an invoice payment history with corresponding bank statement transaction. Transactions need to be the same dollar amount to link successfully.'),
			'cell_colspan' => 4,
		),
	);
    $table_manager->set_footer_rows($footer_rows);
}
$table_manager->pagination = true;
$table_manager->print_table();

?>
</form>
<?php

if($show_excluded_payment_info){
	?>
	<p>
		<?php _e('* credits do not count towards totals'); ?>
	</p>
<?php
}