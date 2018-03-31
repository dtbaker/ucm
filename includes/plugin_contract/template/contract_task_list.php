<?php

// UPDATE::: to edit the "quote task list" please go to Settings > Templates and look for the new "quote_task_list" entry.

if(!isset($quote)&&isset($quote_data))$quote = $quote_data;


ob_start();
?>
<table cellpadding="4" cellspacing="0" style="width:100%" class="table tableclass tableclass_rows">
	<thead>
		<tr class="task_header">
            <th style="width:5%; text-align:center">
				#
			</th>
			<th  style="width:47%; text-align:center">
				{l:Description}
			</th>
			<th style="width:10%; text-align:center">
                {TITLE_QTY_OR_HOURS}
			</th>
			<th style="width:14%; text-align:center">
                {TITLE_AMOUNT_OR_RATE}
			</th>
			<th style="width:14%; text-align:center">
				{l:Sub-Total}
			</th>
		</tr>
	</thead>
	<tbody>
        <tr class="{ITEM_ODD_OR_EVEN}" data-item-row="true">
            <td style="text-align:center">
                {ITEM_NUMBER}
            </td>
            <td>
                {ITEM_DESCRIPTION}
            </td>
            <td align="center">
                {ITEM_QTY_OR_HOURS}
            </td>
            <td style="text-align: right;">
                {ITEM_AMOUNT_OR_RATE}
            </td>
            <td style="text-align: right;">
                {ITEM_TOTAL}
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">&nbsp;</td>
        </tr>
        {QUOTE_SUMMARY}
    </tfoot>
</table>

<?php
module_template::init_template('quote_task_list',ob_get_clean(),'Used when displaying the quote tasks.','code');

$t = false;
if(isset($quote_template_suffix) && strlen($quote_template_suffix) > 0){
	$t = module_template::get_template_by_key('quote_task_list'.$quote_template_suffix);
	if(!$t->template_id){
		$t = false;
	}
}
if(!$t){
	$t = module_template::get_template_by_key('quote_task_list');
}


$replace = array();

$quote_tasks = module_quote::get_quote_items($quote_id,$quote);

$unit_measurement = false;
if(is_callable('module_product::sanitise_product_name')) {
	$fake_task = module_product::sanitise_product_name( array(), $quote['default_task_type'] );
	$unit_measurement = $fake_task['unitname'];
	foreach($quote_tasks as $quote_task_id => $task_data){
		if(isset($task_data['unitname']) && $task_data['unitname'] != $unit_measurement){
			$unit_measurement = false;
			break; // show nothing at title of quote page.
		}
	}
}
$replace['title_qty_or_hours'] = _l($unit_measurement ? $unit_measurement : module_config::c('task_default_name','Unit'));
$replace['title_amount_or_rate'] = _l(module_config::c('quote_amount_name','Amount'));



if(preg_match('#<tr[^>]+data-item-row="true">.*</tr>#imsU',$t->content,$matches)){
    $item_row_html = $matches[0];
    $colspan = substr_count($item_row_html,'<td') - 2;
    $t->content = str_replace($item_row_html, '{ITEM_ROW_CONTENT}', $t->content);
}else{
    set_error('Please ensure a TR with data-item-row="true" is in the quote_task_list template');
    $item_row_html = '';
    $colspan = 4;
}



ob_start();
/* copied from quote_admin_edit.php
todo: move this into a separate method or something so they can both share updates easier
*/
$rows = array();
// we hide quote tax if there is none
$hide_tax = true;
foreach($quote['taxes'] as $quote_tax){
    if($quote_tax['name'] || $quote_tax['percent']>0){
        $hide_tax=false;
        break;
    }
}
if($quote['total_sub_amount_unbillable']){
    $rows[]=array(
        'label'=>_l('Sub Total:'),
        'value'=>'<span class="currency">'.dollar($quote['total_sub_amount']+$quote['total_sub_amount_unbillable'],true,$quote['currency_id']).'</span>'
    );
    $rows[]=array(
        'label'=>_l('Unbillable:'),
        'value'=>'<span class="currency">'.dollar($quote['total_sub_amount_unbillable'],true,$quote['currency_id']).'</span>'
    );
}
if(isset($quote['discount_type'])){
    if($quote['discount_type']==_DISCOUNT_TYPE_BEFORE_TAX){
        $rows[]=array(
            'label'=>_l('Sub Total:'),
            'value'=>'<span class="currency">'.dollar($quote['total_sub_amount']+$quote['discount_amount'],true,$quote['currency_id']).'</span>'
        );
        if($quote['discount_amount']>0){
            $rows[]=array(
                'label'=> htmlspecialchars(_l($quote['discount_description'])),
                'value'=> '<span class="currency">'.dollar($quote['discount_amount'],true,$quote['currency_id']).'</span>'
            );
            $rows[]=array(
                'label'=>_l('Sub Total:'),
                'value'=>'<span class="currency">'.dollar($quote['total_sub_amount'],true,$quote['currency_id']).'</span>'
            );
        }
        if(!$hide_tax){
            foreach($quote['taxes'] as $quote_tax){
                $rows[]=array(
                    'label'=>$quote_tax['name'].' '.number_out($quote_tax['percent'], module_config::c('tax_trim_decimal', 1), module_config::c('tax_decimal_places',module_config::c('currency_decimal_places',2))).'%',
                    'value'=>'<span class="currency">'.dollar($quote_tax['amount'],true,$quote['currency_id']).'</span>',
                    //'extra'=>$quote_tax['name'] . ' = '.$quote_tax['rate'].'%',
                );
            }
        }

    }else if($quote['discount_type']==_DISCOUNT_TYPE_AFTER_TAX){
        $rows[]=array(
            'label'=>_l('Sub Total:'),
            'value'=>'<span class="currency">'.dollar($quote['total_sub_amount'],true,$quote['currency_id']).'</span>'
        );
        if(!$hide_tax){
            foreach($quote['taxes'] as $quote_tax){
                $rows[]=array(
                    'label'=>$quote_tax['name'].' '.number_out($quote_tax['percent'], module_config::c('tax_trim_decimal', 1), module_config::c('tax_decimal_places',module_config::c('currency_decimal_places',2))).'%',
                    'value'=>'<span class="currency">'.dollar($quote_tax['amount'],true,$quote['currency_id']).'</span>',
                    //'extra'=>$quote_tax['name'] . ' = '.$quote_tax['percent'].'%',
                );
            }
            $rows[]=array(
                'label'=>_l('Sub Total:'),
                'value'=>'<span class="currency">'.dollar($quote['total_sub_amount']+$quote['total_tax'],true,$quote['currency_id']).'</span>',
            );
        }
        if($quote['discount_amount']>0){ //if(($discounts_allowed || $quote['discount_amount']>0) &&  (!($quote_locked && module_security::is_page_editable()) || $quote['discount_amount']>0)){
            $rows[]=array(
                'label'=> htmlspecialchars(_l($quote['discount_description'])),
                'value'=> '<span class="currency">'.dollar($quote['discount_amount'],true,$quote['currency_id']).'</span>'
            );
        }
    }
}else{
    if(!$hide_tax){
        $rows[]=array(
            'label'=>_l('Sub Total:'),
            'value'=>'<span class="currency">'.dollar($quote['total_sub_amount'],true,$quote['currency_id']).'</span>',
        );
        foreach($quote['taxes'] as $quote_tax){
            $rows[]=array(
                'label'=>$quote_tax['name'].' '.$quote_tax['percent'].'%',
                'value'=>'<span class="currency">'.dollar($quote_tax['amount'],true,$quote['currency_id']).'</span>',
                'extra'=>$quote_tax['name'] . ' = '.$quote_tax['percent'].'%',
            );
        }
    }
}

$rows[]=array(
    'label'=>_l('Total:'),
    'value'=>'<span class="currency" style="text-decoration: underline; font-weight: bold;">'.dollar($quote['total_amount'],true,$quote['currency_id']).'</span>',
);

foreach($rows as $row){ ?>
<tr>
    <td colspan="<?php echo $colspan;?>">
        &nbsp;
    </td>
    <td>
        <?php echo $row['label'];?>
    </td>
    <td style="text-align: right;">
        <?php echo $row['value'];?>
    </td>
</tr>
<?php }

$replace['quote_summary'] = ob_get_clean();


/* START QUOTE LINE ITEMS */

$task_decimal_places = module_config::c('task_amount_decimal_places',-1);
if($task_decimal_places < 0){
    $task_decimal_places = false; // use default currency dec places.
}
$task_decimal_places_trim = module_config::c('task_amount_decimal_places_trim',0);


$all_item_row_html = '';
$item_count = 0;// changed from 1
foreach($quote_tasks as $quote_item_id => $quote_item_data){

    $row_replace = array(
        'item_odd_or_even' => $item_count++%2 ? 'odd' : 'even',
        'item_number' => '',
        'item_description' => '',
        //'item_date' => '',
        'item_tax' => 0,
        'item_tax_rate' => '',
    );

    if(isset($quote_item_data['task_order']) && $quote_item_data['task_order']>0){
        $row_replace['item_number'] = $quote_item_data['task_order'];
    }else{
        $row_replace['item_number'] = $item_count;
    }
    $row_replace['item_description'] .= htmlspecialchars($quote_item_data['description']);
    if(module_config::c('quote_show_long_desc',1)){
        $long_description =$quote_item_data['long_description'];
        if($long_description!=''){
	        // backwards compat for non-html code:
	        if(!is_text_html($long_description)){
		        // plain text. html it
		        $long_description = forum_text($long_description, false);
	        }
            $row_replace['item_description'] .= '<br/><em>'.module_security::purify_html($long_description).'</em>';
        }
    }

    /*if(isset($quote_item_data['date_done']) && $quote_item_data['date_done'] != '0000-00-00'){
        $row_replace['item_date'] .= print_date($quote_item_data['date_done']);
    }else{
        // check if this is linked to a task.
        if($quote_item_data['quote_task_id']){
            $task = get_single('quote_task','quote_task_id',$quote_item_data['quote_task_id']);
            if($task && isset($task['date_done']) && $task['date_done'] != '0000-00-00'){
                $row_replace['item_date'] .= print_date($task['date_done']);
            }else{
                // check if quote has a date.
                if(isset($quote['date_create']) && $quote['date_create'] != '0000-00-00'){
                    $row_replace['item_date'] .= print_date($quote['date_create']);
                }
            }
        }
    }*/
    if($quote_item_data['manual_task_type']==_TASK_TYPE_AMOUNT_ONLY){
        $row_replace['item_qty_or_hours'] = $quote_item_data['hours'] ? $quote_item_data['hours'] : '-';
    }else{
	    if($quote_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists('decimal_time_out')){
            $hours_value = decimal_time_out($quote_item_data['hours']);
        }else {
            $hours_value = number_out( $quote_item_data['hours'], true );
        }
        $row_replace['item_qty_or_hours'] = $hours_value ? $hours_value . ($quote_item_data['unitname_show'] ? ' ' .$quote_item_data['unitname'] : '') : '-';
    }
    if($quote_item_data['task_hourly_rate']!=0){
        $row_replace['item_amount_or_rate'] = dollar($quote_item_data['task_hourly_rate'],true,$quote['currency_id'],$task_decimal_places_trim,$task_decimal_places);
    }else{
        $row_replace['item_amount_or_rate'] = '-';
    }
    $row_replace['item_total'] = dollar($quote_item_data['quote_item_amount'],true,$quote['currency_id']);

    // taxes per item
    if(isset($quote_item_data['taxes']) && is_array($quote_item_data['taxes']) && $quote_item_data['taxable'] && class_exists('module_finance',false)){
        // this passes off the tax calculation to the 'finance' class, which modifies 'amount' to match the amount of tax applied here.
        $this_taxes = module_finance::sanatise_taxes($quote_item_data['taxes'],$quote_item_data['quote_item_amount']);
        $this_taxes_amounts = array();
        $this_taxes_rates = array();
        if(!count($this_taxes)){
            $this_taxes = array(
                'amount' => 0,
                'percent' => 0,
            );
        }
        foreach($this_taxes as $this_tax){
            $this_taxes_amounts[] = dollar($this_tax['amount'],true,$quote['currency_id']);
            $this_taxes_rates[] = $this_tax['percent'].'%';
        }
        $row_replace['item_tax'] = implode(', ',$this_taxes_amounts);
        $row_replace['item_tax_rate'] = implode(', ',$this_taxes_rates);
    }

    $product_data = module_product::get_replace_fields(!empty($quote_item_data['product_id']) ? $quote_item_data['product_id'] : 0);
    foreach($product_data as $key=>$val){
        if(is_string($val)){
            $row_replace[strtolower('product_'.$key)] = $val;
        }
    }

    $this_item_row_html = $item_row_html;
    $this_item_row_html = str_replace(' data-item-row="true"','',$this_item_row_html);
	// we pass this through the template system so we can make use of things like arithmatic.
	$temp_template = new module_template();
	$temp_template->assign_values($row_replace);
	$temp_template->content = $this_item_row_html;
	$this_item_row_html = $temp_template->replace_content();

    /*foreach($row_replace as $key=>$val){
        $this_item_row_html = str_replace('{'.strtoupper($key).'}', $val, $this_item_row_html);
    }*/
    $all_item_row_html .= $this_item_row_html;
}


$replace['ITEM_ROW_CONTENT'] = $all_item_row_html;
$t->assign_values($replace);
echo $t->render();

if(isset($row_replace) && count($row_replace)){
    module_template::add_tags('quote_task_list',$row_replace);
}