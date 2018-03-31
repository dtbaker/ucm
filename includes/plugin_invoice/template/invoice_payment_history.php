<?php

// UPDATE:::: to modify this layout please now go to Settings > Templates and look for "invoice_payment_history"

if ( module_config::c( 'invoice_show_payment_history', 1 ) ) {

	$payment_historyies = module_invoice::get_invoice_payments( $invoice_id );
	foreach ( $payment_historyies as $invoice_payment_id => $invoice_payment_data ) {
		if ( module_config::c( 'invoice_hide_pending_payments', 1 ) ) {
			if ( ! trim( $invoice_payment_data['date_paid'] ) || $invoice_payment_data['date_paid'] == '0000-00-00' ) {
				unset( $payment_historyies[ $invoice_payment_id ] );
			}
		}
	}
	if ( count( $payment_historyies ) ) {

		ob_start();
		?>
		<table cellpadding="4" cellspacing="0" class="table tableclass tableclass_rows" style="width: 100%"
		       id="invoice_payment_history">
			<thead>
			<tr class="task_header">
				<th>{l:Payment Date}</th>
				<th>{l:Payment Method}</th>
				<th>{l:Details}</th>
				<th>{l:Amount}</th>
				<th></th>
			</tr>
			</thead>
			<tbody>
			<tr class="{ITEM_ODD_OR_EVEN}" data-item-row="true">
				<td>
					{ITEM_PAYMENT_DATE}
				</td>
				<td>
					{ITEM_PAYMENT_METHOD}
				</td>
				<td>
					{ITEM_PAYMENT_DETAILS}
				</td>
				<td>
					{ITEM_PAYMENT_AMOUNT}
				</td>
				<td align="center">
					<a href="{ITEM_PAYMENT_RECEIPT_URL}" target="_blank">{l:View Receipt}</a>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
		module_template::init_template( 'invoice_payment_history', ob_get_clean(), 'Used when displaying the invoice payment history.', 'code' );

		ob_start();
		?>
		<table id="invoice_payment_history" style="width:100%;">
			<tr style="background:#eee;">
				<td>{l:Payment Date}</td>
				<td>{l:Payment Mathod}</td>
				<td>{l:Details}</td>
				<td>{l:Amount}</td>
				<td>{l:Receipt}</td>
			</tr>
			<tr class="{ITEM_ODD_OR_EVEN}" data-item-row="true">
				<td>
					{ITEM_PAYMENT_DATE}
				</td>
				<td>
					{ITEM_PAYMENT_METHOD}
				</td>
				<td>
					{ITEM_PAYMENT_DETAILS}
				</td>
				<td>
					{ITEM_PAYMENT_AMOUNT}
				</td>
				<td align="center">
					<a href="{ITEM_PAYMENT_RECEIPT_URL}" target="_blank">{l:View Receipt}</a>
				</td>
			</tr>
		</table>
		<?php
		module_template::init_template( 'invoice_payment_history_basic', ob_get_clean(), 'Used when displaying the invoice payment history in the basic invoice template.', 'code' );

		$t = false;
		if ( isset( $invoice_template_suffix ) && strlen( $invoice_template_suffix ) > 0 ) {
			$t = module_template::get_template_by_key( 'invoice_payment_history' . $invoice_template_suffix );
			if ( ! $t->template_id ) {
				$t = false;
			}
		}
		if ( ! $t ) {
			$t = module_template::get_template_by_key( 'invoice_payment_history' );
		}

		$replace = array();

		if ( ! isset( $mode ) || $mode == 'html' ) {
			$replace['title'] = '<h3>' . _l( 'Payment History:' ) . '</h3>';
		} else {
			$replace['title'] = '<strong>' . _l( 'Payment History:' ) . '</strong><br/>';

		}

		if ( preg_match( '#<tr[^>]+data-item-row="true">.*</tr>#imsU', $t->content, $matches ) ) {
			$item_row_html = $matches[0];
			$t->content    = str_replace( $item_row_html, '{ITEM_ROW_CONTENT}', $t->content );
		} else {
			set_error( 'Please ensure a TR with data-item-row="true" is in the invoice_payment_history template' );
			$item_row_html = '';
		}

		$all_item_row_html = '';
		$item_count        = 0;// changed from 1

		foreach ( $payment_historyies as $invoice_payment_id => $invoice_payment_data ) {

			$row_replace = array(
				'ITEM_ODD_OR_EVEN'         => $item_count ++ % 2 ? 'odd' : 'even',
				'ITEM_PAYMENT_DATE'        => ( ! trim( $invoice_payment_data['date_paid'] ) || $invoice_payment_data['date_paid'] == '0000-00-00' ) ? _l( 'Pending on %s', print_date( $invoice_payment_data['date_created'] ) ) : print_date( $invoice_payment_data['date_paid'] ),
				'ITEM_PAYMENT_METHOD'      => htmlspecialchars( $invoice_payment_data['method'] ),
				'ITEM_PAYMENT_DETAILS'     => '',
				'ITEM_PAYMENT_AMOUNT'      => dollar( $invoice_payment_data['amount'], true, $invoice_payment_data['currency_id'] ),
				'ITEM_PAYMENT_RECEIPT_URL' => module_invoice::link_receipt( $invoice_payment_data['invoice_payment_id'] ),
			);
			if ( isset( $invoice_payment_data['data'] ) && $invoice_payment_data['data'] ) {
				$details = unserialize( $invoice_payment_data['data'] );
				if ( isset( $details['custom_notes'] ) ) {
					$row_replace['ITEM_PAYMENT_DETAILS'] = htmlspecialchars( $details['custom_notes'] );
				}
			}
			$this_item_row_html = $item_row_html;
			$this_item_row_html = str_replace( ' data-item-row="true"', '', $this_item_row_html );
			foreach ( $row_replace as $key => $val ) {
				$this_item_row_html = str_replace( '{' . strtoupper( $key ) . '}', $val, $this_item_row_html );
			}
			$all_item_row_html .= $this_item_row_html;

		}

		$replace['ITEM_ROW_CONTENT'] = $all_item_row_html;

		$t->assign_values( $replace );

		echo $t->render();

	} ?>
<?php } ?>
