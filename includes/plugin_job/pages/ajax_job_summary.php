<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) {


	$rows = array();


	if ( module_job::is_staff_view( $job ) ) {

		// we assume this is a staff member view.

		$rows[] = array(
			'label' => _l( 'Total:' ),
			'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $job['staff_total_amount'], true, $job['currency_id'] ) . '</span>',
		);
	} else {

		// we assume this is admin view.

		// we hide job tax if there is none
		$hide_tax = true;
		foreach ( $job['taxes'] as $job_tax ) {
			if ( isset( $job_tax['percent'] ) && $job_tax['percent'] > 0 ) {
				$hide_tax = false;
				break;
			}
		}
		if ( $job['total_sub_amount_unbillable'] ) {
			$rows[] = array(
				'label' => _l( 'Sub Total:' ),
				'value' => '<span class="currency">' . dollar( $job['total_sub_amount'] + $job['total_sub_amount_unbillable'] + $job['discount_amount'], true, $job['currency_id'] ) . '</span>'
			);
			$rows[] = array(
				'label' => _l( 'Unbillable:' ),
				'value' => '<span class="currency">' . dollar( $job['total_sub_amount_unbillable'], true, $job['currency_id'] ) . '</span>'
			);
		}
		if ( $job['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
			$rows[] = array(
				'label' => _l( 'Sub Total:' ),
				'value' => '<span class="currency">' . dollar( $job['total_sub_amount'] + $job['discount_amount'], true, $job['currency_id'] ) . '</span>'
			);
			if ( $job['discount_amount'] > 0 ) {
				$rows[] = array(
					'label' => htmlspecialchars( _l( $job['discount_description'] ) ),
					'value' => '<span class="currency">' . dollar( $job['discount_amount'], true, $job['currency_id'] ) . '</span>'
				);
				$rows[] = array(
					'label' => _l( 'Sub Total:' ),
					'value' => '<span class="currency">' . dollar( $job['total_sub_amount'], true, $job['currency_id'] ) . '</span>'
				);
			}
			if ( ! $hide_tax ) {
				if ( true || $job['total_sub_amount'] != $job['total_sub_amount_taxable'] ) {
					$rows[] = array(
						'label' => _l( 'Taxable Amount:' ),
						'value' => '<span class="currency">' . dollar( $job['total_sub_amount_taxable'], true, $job['currency_id'] ) . '</span>'
					);
				}
				foreach ( $job['taxes'] as $job_tax ) {
					$rows[] = array(
						'label' => _l( 'Tax:' ),
						'value' => '<span class="currency">' . dollar( $job_tax['amount'], true, $job['currency_id'] ) . '</span>',
						'extra' => $job_tax['name'] . ' = ' . number_out( $job_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
					);
				}
			}

		} else if ( $job['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
			$rows[] = array(
				'label' => _l( 'Sub Total:' ),
				'value' => '<span class="currency">' . dollar( $job['total_sub_amount'], true, $job['currency_id'] ) . '</span>'
			);
			if ( ! $hide_tax ) {
				if ( $job['total_sub_amount'] != $job['total_sub_amount_taxable'] ) {
					$rows[] = array(
						'label' => _l( 'Taxable Amount:' ),
						'value' => '<span class="currency">' . dollar( $job['total_sub_amount_taxable'], true, $job['currency_id'] ) . '</span>'
					);
				}
				foreach ( $job['taxes'] as $job_tax ) {
					$rows[] = array(
						'label' => _l( 'Tax:' ),
						'value' => '<span class="currency">' . dollar( $job_tax['amount'], true, $job['currency_id'] ) . '</span>',
						'extra' => $job_tax['name'] . ' = ' . number_out( $job_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
					);
				}
				if ( $job['discount_amount'] > 0 || ( isset( $job['fees'] ) && count( $job['fees'] ) ) ) {
					$rows[] = array(
						'label' => _l( 'Sub Total:' ),
						'value' => '<span class="currency">' . dollar( $job['total_sub_amount'] + $job['total_tax'], true, $job['currency_id'] ) . '</span>',
					);
				}
			}
			if ( $job['discount_amount'] > 0 ) { //if(($discounts_allowed || $job['discount_amount']>0) &&  (!($job_locked && module_security::is_page_editable()) || $job['discount_amount']>0)){
				$rows[] = array(
					'label' => htmlspecialchars( _l( $job['discount_description'] ) ),
					'value' => '<span class="currency">' . dollar( $job['discount_amount'], true, $job['currency_id'] ) . '</span>'
				);
			}
		}

		// any fees?
		if ( isset( $job['fees'] ) && count( $job['fees'] ) ) {
			foreach ( $job['fees'] as $fee ) {
				$rows[] = array(
					'label' => $fee['description'],
					'value' => '<span class="currency">' . dollar( $fee['total'], true, $job['currency_id'] ) . '</span>'
				);
			}
		}

		$rows[] = array(
			'label' => _l( 'Total:' ),
			'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $job['total_amount'], true, $job['currency_id'] ) . '</span>',
		);
		if ( module_job::can_i( 'view', 'Job Split Pricing' ) && $job['staff_total_amount'] > 0 ) {
			$rows[] = array(
				'label' => _l( 'Staff Expense:' ),
				'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $job['staff_total_amount'], true, $job['currency_id'] ) . '</span>',
			);
			$rows[] = array(
				'label' => _l( 'Net Total:' ),
				'value' => '<span class="currency" style="text-decoration: underline; font-weight: bold;">' . dollar( $job['total_net_amount'], true, $job['currency_id'] ) . '</span>',
				'extra' => _hr( 'This is after any staff expenses are taken away.' ),
			);
		}
	}

	/*<tr>
        <td>

        </td>
        <td>
            <?php echo ($job['invoice_discount_amount']>0) ? _l('Sub Total:') : _l('Total:');?>
        </td>
        <td>
            <span class="currency" style="text-decoration: underline; font-weight: bold;">
                <?php echo dollar($job['total_amount'],true,$job['currency_id']);?>
            </span>
        </td>
        <td colspan="2">
            &nbsp;
        </td>
    </tr>
    <?php if($job['invoice_discount_amount']>0){ ?>
    <tr>
        <td>
        </td>
        <td>
            <?php _e('Invoice Discounts:');?>
        </td>
        <td>
            <span class="currency">
                <?php echo dollar($job['invoice_discount_amount']+$job['invoice_discount_amount_on_tax'],true,$job['currency_id']);?>
            </span>
        </td>
        <td colspan="2">
            <?php if($job['invoice_discount_amount_on_tax']>0){ _h(_l('This value includes %s of discounted invoice tax.',dollar($job['invoice_discount_amount_on_tax'],true,$job['currency_id']))); } ?>
        </td>
    </tr>
    <tr>
        <td>
        </td>
        <td>
            <?php _e('Discounted Total:');?>
        </td>
        <td>
            <span class="currency" style="text-decoration: underline; font-weight: bold;">
                <?php echo dollar($job['total_amount_discounted'],true,$job['currency_id']);?>
            </span>
        </td>
        <td colspan="2">
            &nbsp;
        </td>
    </tr>
    <?php } ?>*/

	?>
	<table class="tableclass tableclass_full">
		<tbody>
		<?php foreach ( $rows as $row ) { ?>
			<tr>
				<?php if ( $show_task_numbers ) { ?>
					<td>&nbsp;</td>
				<?php } ?>
				<td style="width:50%;">

				</td>
				<td>
					<?php echo $row['label']; ?>
				</td>
				<td>
					<?php echo $row['value']; ?>
				</td>
				<td>
					<?php echo isset( $row['extra'] ) ? $row['extra'] : '&nbsp;'; ?>
				</td>
				<?php if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) { ?>
					<td></td>
				<?php } ?>
			</tr>
		<?php }

		if ( $job['staff_total_amount'] > 0 && ! module_job::can_i( 'view', 'Job Split Pricing' ) ) {

			// staff?
		} else {

			?>

			<tr>
				<td colspan="5">&nbsp;</td>
			</tr>
			<tr>
				<?php if ( $show_task_numbers ) { ?>
					<td>&nbsp;</td>
				<?php } ?>
				<td>
					<?php if ( $show_hours_summary ) { ?>
						<?php echo _l( '%s Hours Total', function_exists( 'decimal_time_out' ) ? decimal_time_out( $job['total_hours'] ) : $job['total_hours'] ); ?>
						<?php if ( $job['total_hours_overworked'] > 0 ) { ?>
							<?php echo _l( '(%s Hours Over)', function_exists( 'decimal_time_out' ) ? decimal_time_out( $job['total_hours_overworked'] ) : $job['total_hours_overworked'] ); ?>
						<?php } else if ( $job['total_hours_overworked'] < 0 ) { ?>
							<?php echo _l( '(%s Hours Under)', function_exists( 'decimal_time_out' ) ? decimal_time_out( $job['total_hours_overworked'] ) : $job['total_hours_overworked'] ); ?>
						<?php } ?>
					<?php } ?>
				</td>
				<td>
					<?php _e( 'Invoiced:' ); ?>
				</td>
				<td>
            <span class="currency">
                <?php echo dollar( $job['total_amount_invoiced'], true, $job['currency_id'] ); // $job['total_amount_invoiced_deposit'] 
                ?>
            </span>
					<?php if ( isset( $job['total_amount_invoiced_deposit'] ) && $job['total_amount_invoiced_deposit'] > 0 ) { ?>
						<br/>
						<span class="currency">
                (<?php echo dollar( $job['total_amount_invoiced_deposit'], true, $job['currency_id'] ); ?> <?php _e( 'deposit' ); ?>
							)
            </span>
					<?php } ?>
				</td>
				<td>

				</td>
			</tr>
			<tr>
				<?php if ( $show_task_numbers ) { ?>
					<td>&nbsp;</td>
				<?php } ?>
				<td>
					<?php if ( $show_hours_summary ) { ?>
						<?php echo _l( '%s Hours Done', function_exists( 'decimal_time_out' ) ? decimal_time_out( $job['total_hours_completed'] ) : $job['total_hours_completed'] ); ?>
						<?php if ( $job['total_amount_invoicable'] > 0 ) { ?>
							<span
								class="success_text">(<?php echo _l( 'Pending %s Invoice', dollar( $job['total_amount_invoicable'], true, $job['currency_id'] ) ); ?>
								)</span>
						<?php } ?>
					<?php } ?>
				</td>
				<td>
					<?php _e( 'Paid:' ); ?>
				</td>
				<td>
            <span class="currency success_text">
                <?php echo dollar( $job['total_amount_paid'], true, $job['currency_id'] ); ?>
            </span>
				</td>
				<td>
					&nbsp;
				</td>
			</tr>
			<tr>
				<?php if ( $show_task_numbers ) { ?>
					<td>&nbsp;</td>
				<?php } ?>
				<td>
					<?php if ( $show_hours_summary ) { ?>
						<?php echo _l( '%s Hours / %s Tasks Remain', function_exists( 'decimal_time_out' ) ? decimal_time_out( $job['total_hours_remain'] ) : $job['total_hours_remain'], $job['total_tasks_remain'] ); ?>
						<?php if ( $job['total_amount_todo'] > 0 ) { ?>
							<span class="error_text">
                    (<?php echo dollar( $job['total_amount_todo'], true, $job['currency_id'] ); ?>)
                </span>
						<?php } ?>
					<?php } ?>
				</td>
				<td>
					<?php _e( 'Unpaid:' ); ?>
				</td>
				<td>
            <span class="currency error_text">
                <?php echo dollar( $job['total_amount_outstanding'], true, $job['currency_id'] ); ?>
            </span>
				</td>
				<td>
					&nbsp;
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php


} else {
	if ( module_job::is_staff_view( $job ) ) {

	} else {
		?>
		<?php echo _l( '%s Hours Total', $job['total_hours'] ); ?>
		<?php if ( $job['total_hours_overworked'] ) { ?>
			<?php echo _l( '(%s Hours Over)', $job['total_hours_overworked'] ); ?>
		<?php } ?>
		<br/>
		<?php echo _l( '%s Hours Done', $job['total_hours_completed'] ); ?>
		<br>
		<?php echo _l( '%s Hours / %s Tasks Remain', $job['total_hours_remain'], $job['total_tasks_remain'] ); ?>

	<?php }
} ?>