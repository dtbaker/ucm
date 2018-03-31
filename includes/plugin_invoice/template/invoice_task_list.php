<?php

// UPDATE::: to edit the "invoice task list" please go to Settings > Templates and look for the new "invoice_task_list" entry.

$invoice_tasks = module_invoice::get_invoice_items( $invoice_id, $invoice );

ob_start();
?>
	<table cellpadding="4" cellspacing="0" class="table tableclass tableclass_rows" style="width: 100%"
	       id="invoice_task_list">
		<thead>
		<tr class="task_header">
			<th style="width:5%; text-align:center">
				#
			</th>
			<th style="width:47%; text-align:center">
				{l:Description}
			</th>
			<th style="width:10%; text-align:center">
				{l:Job}
			</th>
			{if:MULTIPLE_JOBS}
			<th style="width:10%; text-align:center">
				{l:Website}
			</th>
			{endif:MULTIPLE_JOBS}
			<th style="width:10%; text-align:center">
				{l:Date}
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
			<td align="center">
				{ITEM_NUMBER}
			</td>
			<td>
				{ITEM_DESCRIPTION}
			</td>
			<td>
				{JOB_NAME}
			</td>
			{if:MULTIPLE_JOBS}
			<td>
				{WEBSITE_NAME}
			</td>
			{endif:MULTIPLE_JOBS}
			<td>
				{ITEM_DATE}
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
			<td colspan="7">&nbsp;</td>
		</tr>
		{INVOICE_SUMMARY}
		</tfoot>
	</table>
<?php
module_template::init_template( 'invoice_task_list', ob_get_clean(), 'Used when displaying the invoice tasks.', 'code' );

ob_start();
?>
	<table style="width: 100%" id="invoice_task_list">
		<tr style="background:#eee;">
			<td style="width:8%;"><b>#</b></td>
			<td><b>{l:Description}</b></td>
			<td style="width:15%;"><b>{l:Job}</b></td>
			{if:MULTIPLE_JOBS}
			<td style="width:15%;"><b>{l:Website}</b></td>
			{endif:MULTIPLE_JOBS}
			<td style="width:15%;"><b>{l:Date}</b></td>
			<td style="width:15%;"><b>{TITLE_QTY_OR_HOURS}</b></td>
			<td style="width:15%;"><b>{TITLE_AMOUNT_OR_RATE}</b></td>
			<td style="width:15%;"><b>{l:Sub-Total}</b></td>
		</tr>
	</table>
	<table>
		<tr class="{ITEM_ODD_OR_EVEN}" data-item-row="true">
			<td style="width:8%;">{ITEM_NUMBER}</td>
			<td style="text-align:left; padding-left:10px;">{ITEM_DESCRIPTION}</td>
			<td style="width:15%;">{JOB_NAME}</td>
			{if:MULTIPLE_JOBS}
			<td style="width:15%;">{WEBSITE_NAME}</td>
			{endif:MULTIPLE_JOBS}
			<td style="width:15%;">{ITEM_DATE}</td>
			<td class="mono" style="width:15%;">{ITEM_QTY_OR_HOURS}</td>
			<td style="width:15%;" class="mono">{ITEM_AMOUNT_OR_RATE}</td>
			<td style="width:15%;" class="mono">{ITEM_TOTAL}</td>
		</tr>
		<tr>
			<td colspan="5"></td>
			<td></td>
			<td></td>
		</tr>
		{INVOICE_SUMMARY}
	</table>
<?php
module_template::init_template( 'invoice_task_list_basic', ob_get_clean(), 'Used when displaying the invoice tasks when invoice_print_basic template is used.', 'code' );


$t = false;
if ( isset( $invoice_template_suffix ) && strlen( $invoice_template_suffix ) > 0 ) {
	$t = module_template::get_template_by_key( 'invoice_task_list' . $invoice_template_suffix );
	if ( ! $t->template_id ) {
		$t = false;
	}
}
if ( ! $t ) {
	$t = module_template::get_template_by_key( 'invoice_task_list' );
}


$replace = array();

$unit_measurement = false;
if ( is_callable( 'module_product::sanitise_product_name' ) ) {
	$fake_task        = module_product::sanitise_product_name( array(), $invoice['default_task_type'] );
	$unit_measurement = $fake_task['unitname'];
	foreach ( $invoice_tasks as $task_data ) {
		if ( isset( $task_data['unitname'] ) && $task_data['unitname'] != $unit_measurement ) {
			$unit_measurement = false;
			break; // show nothing at title of quote page.
		}
	}
}
$replace['title_qty_or_hours']   = _l( $unit_measurement ? $unit_measurement : module_config::c( 'task_default_name', 'Unit' ) );
$replace['title_amount_or_rate'] = _l( module_config::c( 'invoice_amount_name', 'Amount' ) );


if ( preg_match( '#<tr[^>]+data-item-row="true">.*</tr>#imsU', $t->content, $matches ) ) {
	$item_row_html = $matches[0];
	$colspan       = substr_count( $item_row_html, '<td' ) - 2;
	$t->content    = str_replace( $item_row_html, '{ITEM_ROW_CONTENT}', $t->content );
} else {
	set_error( 'Please ensure a TR with data-item-row="true" is in the invoice_task_list template' );
	$item_row_html = '';
	$colspan       = 4;
}


ob_start();
/* copied from invoice_admin_edit.php
todo: move this into a separate method or something so they can both share updates easier
*/
$rows = array();
// we hide invoice tax if there is none
$hide_tax = true;
foreach ( $invoice['taxes'] as $invoice_tax ) {
	if ( $invoice_tax['name'] || $invoice_tax['percent'] > 0 ) {
		$hide_tax = false;
		break;
	}
}
if ( $invoice['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
	$rows[] = array(
		'label' => _l( 'Sub Total:' ),
		'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'] + $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>'
	);
	if ( $invoice['discount_amount'] > 0 ) {
		$rows[] = array(
			'label' => htmlspecialchars( _l( $invoice['discount_description'] ) ),
			'value' => '<span class="currency">' . dollar( $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>'
		);
		$rows[] = array(
			'label' => _l( 'Sub Total:' ),
			'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'], true, $invoice['currency_id'] ) . '</span>'
		);
	}
	if ( ! $hide_tax ) {
		foreach ( $invoice['taxes'] as $invoice_tax ) {
			$rows[] = array(
				'label' => $invoice_tax['name'] . ' ' . number_out( $invoice_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
				'value' => '<span class="currency">' . dollar( $invoice_tax['amount'], true, $invoice['currency_id'] ) . '</span>',
				//'extra'=>$invoice_tax['name'] . ' = '.$invoice_tax['rate'].'%',
			);
		}
	}

} else if ( $invoice['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
	$rows[] = array(
		'label' => _l( 'Sub Total:' ),
		'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'], true, $invoice['currency_id'] ) . '</span>'
	);
	if ( ! $hide_tax ) {
		foreach ( $invoice['taxes'] as $invoice_tax ) {
			$rows[] = array(
				'label' => $invoice_tax['name'] . ' ' . number_out( $invoice_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
				'value' => '<span class="currency">' . dollar( $invoice_tax['amount'], true, $invoice['currency_id'] ) . '</span>',
				//'extra'=>$invoice_tax['name'] . ' = '.number_out($invoice_tax['percent'], module_config::c('tax_trim_decimal', 1), module_config::c('tax_decimal_places',module_config::c('currency_decimal_places',2))).'%',
			);
		}
		$rows[] = array(
			'label' => _l( 'Sub Total:' ),
			'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'] + $invoice['total_tax'], true, $invoice['currency_id'] ) . '</span>',
		);
	}
	if ( $invoice['discount_amount'] > 0 ) { //if(($discounts_allowed || $invoice['discount_amount']>0) &&  (!($invoice_locked && module_security::is_page_editable()) || $invoice['discount_amount']>0)){
		$rows[] = array(
			'label' => htmlspecialchars( _l( $invoice['discount_description'] ) ),
			'value' => '<span class="currency">' . dollar( $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>'
		);
	}
}
// any fees?
if ( count( $invoice['fees'] ) ) {
	foreach ( $invoice['fees'] as $fee ) {
		$rows[] = array(
			'label' => $fee['description'],
			'value' => '<span class="currency">' . dollar( $fee['total'], true, $invoice['currency_id'] ) . '</span>'
		);
	}
}
$rows[] = array(
	'label' => _l( 'Total:' ),
	'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $invoice['total_amount'] + ( $invoice['total_amount_deposits'] + $invoice['total_amount_deposits_tax'] ), true, $invoice['currency_id'] ) . '</span>',
);

if ( $invoice['total_amount_deposits'] > 0 ) {
	$rows[] = array(
		'label' => _l( 'Deposit:' ),
		'value' => '<span class="currency">' . dollar( $invoice['total_amount_deposits'] + $invoice['total_amount_deposits_tax'], true, $invoice['currency_id'] ) . '</span>'
	);
	$rows[] = array(
		'label' => _l( 'Total:' ),
		'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $invoice['total_amount'], true, $invoice['currency_id'] ) . '</span>',
	);
}
foreach ( $rows as $row ) { ?>
	<tr>
		<td colspan="<?php echo $colspan; ?>">
			&nbsp;
		</td>
		<td>
			<?php echo $row['label']; ?>
		</td>
		<td style="text-align: right;">
			<?php echo $row['value']; ?>
		</td>
	</tr>
<?php }
if ( isset( $invoice['credit_note_id'] ) && $invoice['credit_note_id'] ) { ?>

<?php } else { ?>
	<tr>
		<td colspan="<?php echo $colspan + 2; ?>">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="<?php echo $colspan; ?>" align="right">

		</td>
		<td>
			<?php _e( 'Paid:' ); ?>
		</td>
		<td style="text-align: right;">
			<span
				class="currency"><?php echo dollar( $invoice['total_amount_paid'], true, $invoice['currency_id'] ); ?></span>
		</td>
	</tr>
	<tr>
		<td colspan="<?php echo $colspan; ?>" align="right">

		</td>
		<td>
			<?php _e( 'Due:' ); ?>
		</td>
		<td style="text-align: right;">
            <span class="currency" style="text-decoration: underline; font-weight: bold; color:#FF0000;">
                <?php echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
            </span>
		</td>
	</tr>
<?php }
$replace['invoice_summary'] = ob_get_clean();


/* START INVOICE LINE ITEMS */

$task_decimal_places = module_config::c( 'task_amount_decimal_places', - 1 );
if ( $task_decimal_places < 0 ) {
	$task_decimal_places = false; // use default currency dec places.
}
$task_decimal_places_trim = module_config::c( 'task_amount_decimal_places_trim', 0 );
$all_item_row_html        = '';
$item_count               = 0;// changed from 1


$show_invoice_job_names = module_config::c( 'show_invoice_job_names', 0 );
// 0 = only show on combined invoices.
// 1 = always show job names.
if ( ! $show_invoice_job_names ) {
	if ( count( $invoice['job_ids'] ) ) {
		$show_invoice_job_names = 1;
	}
}

// work out if there are multiple jobs or websites for this invoice
// set a template tag so we can show those details in the listing.
$job_ids      = array();
$website_ids  = array();
$website_data = array();
if ( $invoice['website_id'] ) {
	$website_ids[ $invoice['website_id'] ] = true;
	$website_data                          = module_website::get_website( $invoice['website_id'] );
}
foreach ( $invoice_tasks as $invoice_item_id => $invoice_item_data ) {
	if ( $invoice_item_data['job_id'] ) {
		$job_data = module_job::get_job( $invoice_item_data['job_id'], false, true );
		if ( $job_data ) {
			if ( $job_data['website_id'] ) {
				$website_ids[ $job_data['website_id'] ] = true;
			}
		}
		$job_ids[ $invoice_item_data['job_id'] ] = true;

	}
}
$replace['multiple_jobs'] = count( $website_ids ) > 1 || count( $job_ids ) > 1;
foreach ( $invoice_tasks as $invoice_item_id => $invoice_item_data ) {

	$row_replace                     = $invoice_item_data;
	$row_replace['item_odd_or_even'] = $item_count ++ % 2 ? 'odd' : 'even';
	$row_replace['item_number']      = '';
	$row_replace['item_description'] = '';
	$row_replace['item_date']        = '';
	$row_replace['item_tax']         = 0;
	$row_replace['item_tax_rate']    = '';
	$row_replace['job_name']         = '';
	$row_replace['website_name']     = $website_data ? htmlspecialchars( $website_data['name'] ) : '';
	$row_replace['multiple_jobs']    = $replace['multiple_jobs'];


	if ( $invoice_item_data['job_id'] ) {
		$job_data = module_job::get_job( $invoice_item_data['job_id'], false, true );
		if ( $job_data ) {
			$row_replace['job_name'] = htmlspecialchars( $job_data['name'] );
			if ( $job_data['website_id'] ) {
				$website_data                = module_website::get_website( $job_data['website_id'] );
				$row_replace['website_name'] = htmlspecialchars( $website_data['name'] );
			}
		}
	}

	if ( isset( $invoice_item_data['custom_task_order'] ) && (int) $invoice_item_data['custom_task_order'] > 0 ) {
		$row_replace['item_number'] = $invoice_item_data['custom_task_order'];
	} else if ( isset( $invoice_item_data['task_order'] ) && $invoice_item_data['task_order'] > 0 ) {
		$row_replace['item_number'] = $invoice_item_data['task_order'];
	} else {
		$row_replace['item_number'] = $item_count;
	}
	$row_replace['item_description'] .= $invoice_item_data['custom_description'] ? htmlspecialchars( $invoice_item_data['custom_description'] ) : htmlspecialchars( $invoice_item_data['description'] );
	if ( module_config::c( 'invoice_show_long_desc', 1 ) ) {
		$long_description = trim( $invoice_item_data['custom_long_description'] ? $invoice_item_data['custom_long_description'] : $invoice_item_data['long_description'] );
		if ( $long_description != '' ) {
			// backwards compat for non-html code:
			if ( ! is_text_html( $long_description ) ) {
				// plain text. html it
				$long_description = forum_text( $long_description, false );
			}
			$row_replace['item_description'] .= '<br/><em>' . module_security::purify_html( $long_description ) . '</em>';
		}
	}

	if ( isset( $invoice_item_data['date_done'] ) && $invoice_item_data['date_done'] != '0000-00-00' ) {
		$row_replace['item_date'] .= print_date( $invoice_item_data['date_done'] );
	} else {
		// check if this is linked to a task.
		if ( $invoice_item_data['task_id'] ) {
			$task = get_single( 'task', 'task_id', $invoice_item_data['task_id'] );
			if ( $task && isset( $task['date_done'] ) && $task['date_done'] != '0000-00-00' ) {
				$row_replace['item_date'] .= print_date( $task['date_done'] );
			} else {
				// check if invoice has a date.
				if ( isset( $invoice['date_create'] ) && $invoice['date_create'] != '0000-00-00' ) {
					$row_replace['item_date'] .= print_date( $invoice['date_create'] );
				}
			}
		}
	}
	if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
		$row_replace['item_qty_or_hours'] = '-';
	} else {
		if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_out' ) ) {
			$hours_value = decimal_time_out( $invoice_item_data['hours'] );
		} else {
			$hours_value = number_out( $invoice_item_data['hours'], true );
		}
		$row_replace['item_qty_or_hours'] = $hours_value ? $hours_value . ( ! empty( $invoice_item_data['unitname'] ) && ! empty( $invoice_item_data['unitname_show'] ) ? ' ' . $invoice_item_data['unitname'] : '' ) : '-';
	}
	if ( $invoice_item_data['task_hourly_rate'] != 0 ) {
		$row_replace['item_amount_or_rate'] = dollar( $invoice_item_data['task_hourly_rate'], true, $invoice['currency_id'], $task_decimal_places_trim, $task_decimal_places );
	} else {
		$row_replace['item_amount_or_rate'] = '-';
	}
	$row_replace['item_total'] = dollar( $invoice_item_data['invoice_item_amount'], true, $invoice['currency_id'] );

	// taxes per item
	if ( isset( $invoice_item_data['taxes'] ) && is_array( $invoice_item_data['taxes'] ) && $invoice_item_data['taxable'] && class_exists( 'module_finance', false ) ) {
		// this passes off the tax calculation to the 'finance' class, which modifies 'amount' to match the amount of tax applied here.
		foreach ( $invoice_item_data['taxes'] as $key => $val ) {
			if ( isset( $val['amount'] ) ) {
				unset( $invoice_item_data['taxes'][ $key ]['amount'] );
			}
		}
		$this_taxes         = module_finance::sanatise_taxes( $invoice_item_data['taxes'], $invoice_item_data['invoice_item_amount'] );
		$this_taxes_amounts = array();
		$this_taxes_rates   = array();
		if ( ! count( $this_taxes ) ) {
			$this_taxes = array(
				'amount'  => 0,
				'percent' => 0,
			);
		}
		foreach ( $this_taxes as $this_tax ) {
			$this_taxes_amounts[] = dollar( $this_tax['amount'], true, $invoice['currency_id'] );
			$this_taxes_rates[]   = $this_tax['percent'] . '%';
		}
		$row_replace['item_tax']      = implode( ', ', $this_taxes_amounts );
		$row_replace['item_tax_rate'] = implode( ', ', $this_taxes_rates );
	}

	if ( class_exists( 'module_product' ) ) {
		$product_data = module_product::get_replace_fields( ! empty( $invoice_item_data['product_id'] ) ? $invoice_item_data['product_id'] : 0 );
		foreach ( $product_data as $key => $val ) {
			if ( is_string( $val ) ) {
				$row_replace[ strtolower( 'product_' . $key ) ] = $val;
			}
		}
	}

	$this_item_row_html = $item_row_html;
	$this_item_row_html = str_replace( ' data-item-row="true"', '', $this_item_row_html );
	// we pass this through the template system so we can make use of things like arithmatic.
	$temp_template = new module_template();
	$temp_template->assign_values( $row_replace );
	$temp_template->content = $this_item_row_html;
	$this_item_row_html     = $temp_template->replace_content();

	/*foreach($row_replace as $key=>$val){
		if(!is_array($val)) {
			$this_item_row_html = str_replace( '{' . strtoupper( $key ) . '}', $val, $this_item_row_html );
		}
	}*/
	$all_item_row_html .= $this_item_row_html;
}


$replace['ITEM_ROW_CONTENT'] = $all_item_row_html;
$t->assign_values( $replace );
echo $t->render();

if ( isset( $row_replace ) && count( $row_replace ) ) {
	module_template::add_tags( 'invoice_task_list', $row_replace );
}