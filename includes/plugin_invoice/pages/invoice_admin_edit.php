<?php

// ensure no direct file access.
if ( ! defined( '_UCM_VERSION' ) ) {
	die( 'Nothing to see here' );
}

if ( ! $invoice_safe ) {
	die( 'failed' );
}

$invoice_id = (int) $_REQUEST['invoice_id'];

// old invoice data array access:
$invoice = module_invoice::get_invoice( $invoice_id );

// new invoice class system:
$UCMInvoice = UCMInvoice::singleton( $invoice_id );

if ( $invoice_id > 0 && $UCMInvoice->get( 'invoice_id' ) == $invoice_id ) {
	$module->page_title = _l( 'Invoice: #%s', htmlspecialchars( $UCMInvoice['name'] ) );

	if ( ! $UCMInvoice->check_permissions() ) {
		// new permission checking:
		echo 'Permission denied. Please login as super admin.';
		exit;
	}

	if ( class_exists( 'module_security', false ) ) {
		// old permission checking:
		if ( ! module_security::can_access_data( 'invoice', $invoice, $invoice_id ) ) {
			echo 'Data access denied. Sorry.';
			exit;
		}
		// old page permission checking. will regex out inputs and show read only version if neccessary.
		// remove this and move the permission checking into the new form output class.
		module_security::check_page( array(
			'category'  => 'Invoice',
			'page_name' => 'Invoices',
			'module'    => 'invoice',
			'feature'   => 'edit',
		) );
	}
} else {

	// we're creating a new invoice.
	$invoice_id = 0;
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'category'  => 'Invoice',
			'page_name' => 'Invoices',
			'module'    => 'invoice',
			'feature'   => 'create',
		) );
	}
	module_security::sanatise_data( 'invoice', $invoice );
}
$invoice_items  = module_invoice::get_invoice_items( $invoice_id, $invoice );
$invoice_locked = ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) || ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' );
if ( isset( $_REQUEST['as_deposit'] ) && isset( $_REQUEST['job_id'] ) ) {
	$invoice['deposit_job_id'] = (int) $_REQUEST['job_id'];
}


$customer_data = array();
if ( $invoice['customer_id'] ) {
	$customer_data = module_customer::get_customer( $invoice['customer_id'] );
}

$show_task_dates = module_config::c( 'invoice_task_list_show_date', 1 );
$colspan         = 2;
if ( $show_task_dates ) {
	$colspan ++;
}


if ( isset( $invoice['credit_note_id'] ) && $invoice['credit_note_id'] ) {
	// this invoice is a credit note.
	// display a slightly different layout.
	include( module_theme::include_ucm( "includes/plugin_invoice/pages/invoice_admin_credit.php" ) );

	return;
}

// find out all the payment methods.
$payment_methods        = handle_hook( 'get_payment_methods', $module );
$x                      = 1;
$default_payment_method = module_config::c( 'invoice_default_payment_method', 'paymethod_paypal' );

?>

<script type="text/javascript">
    $(function () {
        if (typeof ucm.invoice != 'undefined') {
            ucm.invoice.init();
        }
    });
</script>


<form action="" method="post" id="invoice_form">
	<input type="hidden" name="_process" value="save_invoice" class="no_permissions"/>
	<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>"/>
	<?php if ( $invoice['customer_id'] && ! isset( $_REQUEST['change_customer'] ) ) { ?>
		<input type="hidden" name="customer_id" value="<?php echo $invoice['customer_id']; ?>"/>
	<?php } ?>
	<input type="hidden" name="job_id" value="<?php echo isset( $invoice['job_id'] ) ? (int) $invoice['job_id'] : 0; ?>"/>
	<?php if ( isset( $_REQUEST['as_deposit'] ) ) {
		?>
		<input type="hidden" name="deposit_job_id"
		       value="<?php echo isset( $invoice['job_id'] ) ? (int) $invoice['job_id'] : 0; ?>"/>
	<?php } ?>
	<input type="hidden" name="hourly_rate" value="<?php echo htmlspecialchars( $invoice['hourly_rate'] ); ?>"/>

	<input type="hidden" name="_redirect" value="" id="form_redirect"/>

	<?php

	$fields = array(
		'fields' => array(
			'name' => 'Name',
		)
	);
	module_form::set_required(
		$fields
	);
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.save_invoice_item',
				'.save_invoice_payment',
				'.delete',
				'.apply_discount',
			)
		)
	);


	hook_handle_callback( 'layout_column_half', 1, '35' );


	/* INVOICE DETAILS */
	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => 'Invoice Details',
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title' => 'Invoice #',
				'field' => array(
					'type'  => 'text',
					'name'  => 'name',
					'id'    => 'invoice_number',
					'value' => $invoice['name'],
				),
			),
			array(
				'title' => 'Status',
				'field' => array(
					'type'      => 'select',
					'name'      => 'status',
					'value'     => $invoice['status'],
					'blank'     => false,
					'options'   => module_invoice::get_statuses(),
					'allow_new' => true,
				),
			),
			array(
				'title' => 'Created Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_create',
					'value' => print_date( $invoice['date_create'] ),
				),
			),
			array(
				'title'  => 'Due Date',
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => 'date_due',
						'value' => print_date( $invoice['date_due'] ),
					),
					array(
						'type'  => 'checkbox',
						'name'  => 'overdue_email_auto',
						'value' => isset( $invoice['overdue_email_auto'] ) && $invoice['overdue_email_auto'],
						'label' => 'Auto Overdue Email',
						'help'  => 'When this Invoice becomes overdue an email will be automatically sent to the customer. Settings > Invoice for more options.',
					)
				),
			),
			array(
				'title'  => 'Sent Date',
				'hidden' => ! ( (int) $invoice_id ),
				'field'  => array(
					'type'  => 'date',
					'name'  => 'date_sent',
					'id'    => 'date_sent',
					'value' => print_date( $invoice['date_sent'] ),
				),
			),
			array(
				'title'  => 'Paid Date',
				'hidden' => ! ( (int) $invoice_id ),
				'field'  => array(
					'type'  => 'date',
					'name'  => 'date_paid',
					'value' => print_date( $invoice['date_paid'] ),
					'help'  => 'To mark an invoice as paid please record a full payment against this invoice. Once that is done you can adjust the date here.',
				),
			),
		),
	);

	if ( class_exists( 'module_extra', false ) ) {
		$fieldset_data['extra_settings'] = array(
			'owner_table' => 'invoice',
			'owner_key'   => 'invoice_id',
			'owner_id'    => $invoice['invoice_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_extra::can_i( 'create', 'Invoices' ),
			'allow_edit'  => module_extra::can_i( 'edit', 'Invoices' ),
		);
	}
	$incrementing = false;
	if ( ! isset( $invoice['taxes'] ) || ! count( $invoice['taxes'] ) ) {
		$invoice['taxes'][] = array(); // at least have 1?
	}
	foreach ( $invoice['taxes'] as $tax ) {
		if ( isset( $tax['increment'] ) && $tax['increment'] ) {
			$incrementing = true;
		}
	}
	ob_start();
	?>
	<span class="invoice_tax_increment">
        <input type="checkbox" name="tax_increment_checkbox" id="tax_increment_checkbox"
               value="1" <?php echo $incrementing ? ' checked' : ''; ?>> <?php _e( 'incremental' ); ?>
    </span>
	<div id="invoice_tax_holder">
		<?php
		foreach ( $invoice['taxes'] as $id => $tax ) { ?>
			<div class="dynamic_block">
				<input type="hidden" name="tax_ids[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['invoice_tax_id'] ) ? (int) $tax['invoice_tax_id'] : 0; ?>">
				<input type="text" name="tax_names[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['name'] ) ? htmlspecialchars( $tax['name'] ) : ''; ?>" style="width:30px;">
				@
				<input type="text" name="tax_percents[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['percent'] ) ? htmlspecialchars( number_out( $tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) ) : ''; ?>"
				       style="width:35px;">%
				<a href="#" class="add_addit">+</a>
				<a href="#" class="remove_addit">-</a>
			</div>
		<?php } ?>
	</div>
	<script type="text/javascript">
      ucm.form.dynamic('invoice_tax_holder', function () {
          ucm.invoice.update_invoice_tax();
      });
	</script>
	<?php
	$fieldset_data['elements']['tax'] = array(
		'title'  => 'Tax',
		'fields' => array(
			ob_get_clean(),
		),
	);
	$fieldset_data['elements'][]      = array(
		'title' => 'Currency',
		'field' => array(
			'type'             => 'select',
			'options'          => get_multiple( 'currency', '', 'currency_id' ),
			'name'             => 'currency_id',
			'value'            => $invoice['currency_id'],
			'options_array_id' => 'code',
		),
	);
	$fieldset_data['elements'][]      = array(
		'title'  => 'Hourly Rate',
		'fields' => array(
			array(
				'type'        => 'currency',
				'name'        => 'hourly_rate',
				'value'       => number_out( $invoice['hourly_rate'] ),
				'currency_id' => $invoice['currency_id'],
				'help'        => 'This hourly rate will be applied to all manual tasks (tasks that did not come from jobs) in this invoice',
			),
			function () use ( &$invoice_items, &$invoice ) {
				$other_rates = array();
				foreach ( $invoice_items as $invoice_item ) {
					if ( $invoice_item['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && isset( $invoice_item['hourly_rate'] ) && $invoice_item['hourly_rate'] != $invoice['hourly_rate'] && $invoice_item['hourly_rate'] > 0 ) {
						$other_rates[ dollar( $invoice_item['hourly_rate'] ) ] = true;
					}
				}
				if ( count( $other_rates ) ) {
					_e( "(and %s)", implode( ', ', array_keys( $other_rates ) ) );
				}
			}
		),
	);
	if ( count( $invoice['job_ids'] ) ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Job',
			'fields' => array(
				function () use ( &$invoice ) {
					foreach ( $invoice['job_ids'] as $job_id ) {
						if ( (int) $job_id > 0 ) {
							echo module_job::link_open( $job_id, true );
							echo "<br/>\n";
						}
					}
				},
			),
		);
	}

	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );


	if ( (int) $invoice_id > 0 ) {
		hook_handle_callback( 'invoice_sidebar', $invoice_id );
	}

	if ( $invoice_id && $invoice_id != 'new' ) {
		$note_summary_owners = array();
		// generate a list of all possible notes we can display for this invoice.
		// display all the notes which are owned by all the sites we have access to

		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::display_notes( array(
					'title'       => 'Invoice Notes',
					'owner_table' => 'invoice',
					'owner_id'    => $invoice_id,
					'view_link'   => module_invoice::link_open( $invoice_id ),
					'public'      => array(
						'enabled' => true,
						'title'   => 'Public',
						'text'    => 'Yes, show this note in invoice',
						'help'    => 'If this is ticked then this note will be available to the customer and will be included in the {INVOICE_NOTES} shortcode in the invoice template.',
					)
				)
			);
		}
		if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
			module_email::display_emails( array(
				'title'  => 'Invoice Emails',
				'search' => array(
					'invoice_id' => $invoice_id,
				)
			) );
		}
	}

	if ( (int) $invoice_id > 0 && ( ! $invoice['date_sent'] || $invoice['date_sent'] == '0000-00-00' ) && module_security::is_page_editable() ) {

		ob_start();

		?>

		<div class="tableclass_form content">

			<p style="text-align: center;">
				<input type="submit" name="butt_email" id="butt_email2" value="<?php echo _l( 'Email Invoice' ); ?>"
				       class="submit_button btn"/>
				<?php _h( 'Click this button to email the invoice to the customer from the system' ); ?>
			</p>
			<p style="text-align: center;">

				<button type="button" name="buttmarksent" class="submit_button no_permissions btn"
				        onclick="$('#date_sent').val('<?php echo print_date( date( 'Y-m-d' ) ); ?>'); $('#invoice_form')[0].submit(); return false;"><?php _e( 'Mark invoice as sent' ); ?></button>
				<?php _h( 'This invoice has not been sent yet. When this invoice has been sent to the customer please click this button or enter a "sent date" into the form above.' ); ?>
			</p>
		</div>

		<?php

		$fieldset_data = array(
			'heading'         => array(
				'title_final' => '<span class="error_text">' . _l( 'Send Invoice' ) . '</span>',
				'type'        => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}

	if ( (int) $invoice_id > 0 ) {

		/* MAKE PAYMENT */
		ob_start();
		?>
		<table class="tableclass tableclass_form tableclass_full" cellpadding="0" cellspacing="0">
			<tbody>
			<tr>
				<th class="width1">
					<?php _e( 'Payment Method' ); ?>
				</th>
				<td>
					<?php

					$default_payment_method_text = '';
					foreach ( $payment_methods as $payment_method ) {

						if ( $default_payment_method == $payment_method->module_name ) {
							$default_payment_method_text = $payment_method->get_payment_method_name();
						}

						if ( $payment_method->is_method( 'online' ) && $payment_method->is_enabled() && $payment_method->is_allowed_for_invoice( $invoice_id ) ) { ?>
							<input type="radio" name="payment_method" value="<?php echo $payment_method->module_name; ?>"
							       id="paymethod<?php echo $x; ?>"
							       class="no_permissions payment_method payment_method_online" <?php echo $default_payment_method == $payment_method->module_name ? 'checked' : ''; ?>>
							<label for="paymethod<?php echo $x; ?>"><?php echo $payment_method->get_payment_method_name(); ?></label>
							<br/>
							<?php
							$x ++;
						}
					}
					foreach ( $payment_methods as $payment_method ) {

						if ( $default_payment_method == $payment_method->module_name ) {
							$default_payment_method_text = $payment_method->get_payment_method_name();
						}

						if ( $payment_method->is_method( 'offline' ) && $payment_method->is_enabled() && $payment_method->is_allowed_for_invoice( $invoice_id ) ) { ?>
							<input type="radio" name="payment_method" value="<?php echo $payment_method->module_name; ?>"
							       id="paymethod<?php echo $x; ?>"
							       class="no_permissions payment_method payment_method_offline" <?php echo $default_payment_method == $payment_method->module_name ? 'checked' : ''; ?>>
							<label for="paymethod<?php echo $x; ?>"><?php echo $payment_method->get_payment_method_name(); ?></label>
							<br/>
							<input type="hidden" id="text_paymethod<?php echo $x; ?>"
							       value="<?php echo htmlspecialchars( $payment_method->get_invoice_payment_description( $invoice_id ) ); ?>">
							<?php
							$x ++;
						}
					}
					?>
				</td>
			</tr>
			<tr class="payment_type_online">
				<th>
					<?php _e( 'Payment Amount' ); ?>
				</th>
				<td>
					<?php
					if ( module_config::c( 'invoice_allow_payment_amount_adjustment', 1 ) ) {
						echo currency( '<input type="text" name="payment_amount" value="' . number_out( $invoice['total_amount_due'] ) . '" class="currency no_permissions">', true, $invoice['currency_id'] );
					} else {
						echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
						echo '<input type="hidden" name="payment_amount" value="' . number_out( $invoice['total_amount_due'] ) . '" class="no_permissions">';
					}
					?>
				</td>
			</tr>
			<tr class="payment_type_online">
				<td align="center" colspan="2">
					<input type="hidden" name="butt_makepayment" id="butt_makepayment" value="" class="no_permissions">
					<button type="button" name="buttpay" class="submit_button no_permissions btn btn-primary"
					        onclick="$('#butt_makepayment').val('yes'); this.form.submit();"><?php _e( 'Make Payment' ); ?></button>
				</td>
			</tr>
			<tr class="payment_type_offline">
				<td align="left" colspan="2" id="payment_type_offline_info">

				</td>
			</tr>
			</tbody>
		</table>
		<?php


		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Make a Payment' ),
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}


	if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {

		/*** INVOICE ADVANCED **/

		$fieldset_data = $UCMInvoice->generate_advanced_fieldset();

		$fieldset_data['elements'][] = array(
			'title'  => 'Allowed Payments',
			'fields' => array(
				array(
					'type'  => 'hidden',
					'name'  => 'allowed_payment_method_check',
					'value' => 1,
				),
				function () use ( &$payment_methods, $invoice_id ) {
					if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
						$x = 0;
						foreach ( $payment_methods as &$payment_method ) {
							if ( $payment_method->is_enabled() ) {
								?>
								<input type="checkbox" name="allowed_payment_method[<?php echo $payment_method->module_name; ?>]"
								       value="1"
								       id="paymethodallowed<?php echo $x; ?>" <?php echo $payment_method->is_allowed_for_invoice( $invoice_id ) ? 'checked' : ''; ?>>
								<label
									for="paymethodallowed<?php echo $x; ?>"><?php echo $payment_method->get_payment_method_name(); ?></label>
								<br/>
								<?php
								$x ++;
							}
						}
					}
				}
			),
		);


		if ( file_exists( 'includes/plugin_invoice/pages/invoice_recurring.php' ) ) {
			if ( (int) $invoice_id > 0 ) {
				// see if this invoice was renewed from anywhere
				$invoice_history = module_invoice::get_invoices( array( 'renew_invoice_id' => $invoice_id ) );
				if ( count( $invoice_history ) ) {
					foreach ( $invoice_history as $invoice_h ) {
						$fieldset_data['elements'][] = array(
							'title'  => 'Renewal History',
							'fields' => array(
								_l( 'This invoice was renewed from %s on %s', module_invoice::link_open( $invoice_h['invoice_id'], true ), print_date( $invoice_h['date_renew'] ) )
							),
						);
					}
				}
			}
			$fieldset_data['elements'][] = array(
				'title'  => 'Renewal Date',
				'fields' => array(
					function () use ( &$invoice ) {
						if ( isset( $invoice['renew_invoice_id'] ) && $invoice['renew_invoice_id'] ) {
							echo _l( 'This invoice was renewed on %s.', print_date( $invoice['date_renew'] ) );
							echo '<br/>';
							echo _l( 'A new invoice was created, please click <a href="%s">here</a> to view it.', module_invoice::link_open( $invoice['renew_invoice_id'] ) );
						} else {
							$has_renewal = false;
							foreach ( $invoice['job_ids'] as $job_id ) {
								if ( (int) $job_id > 0 ) {
									$job_data = module_job::get_job( $job_id, false );
									if ( $job_data['date_renew'] && $job_data['date_renew'] != '0000-00-00' ) {
										$has_renewal = true;
										_e( 'This invoice will renew as part of job %s on %s', module_job::link_open( $job_id, true ), print_date( $job_data['date_renew'] ) );
									}
								}
							}
							if ( $has_renewal ) {

							} else {
								if ( $invoice['date_renew'] != '0000-00-00' && ( ! $invoice['date_create'] || $invoice['date_create'] == '0000-00-00' ) ) {
									echo '<p>Warning: Please set an Invoice "Create Date" for renewals to work correctly</p>';
								}
								?>
								<input type="text" name="date_renew" class="date_field"
								       value="<?php echo print_date( $invoice['date_renew'] ); ?>">
								<?php
								if ( $invoice['date_renew'] && $invoice['date_renew'] != '0000-00-00' && strtotime( $invoice['date_renew'] ) <= strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) && ! $invoice['renew_auto'] ) {
									// we are allowed to generate this renewal.
									?>
									<input type="button" name="generate_renewal_btn" value="<?php echo _l( 'Generate Renewal' ); ?>"
									       class="submit_button" onclick="$('#generate_renewal_gogo').val(1); this.form.submit();">
									<input type="hidden" name="generate_renewal" id="generate_renewal_gogo" value="0">

									<?php
									_h( 'A renewal is available for this invoice. Clicking this button will create a new invoice based on this invoice, and set the renewal reminder up again for the next date.' );
								} else if ( isset( $invoice['renew_auto'] ) && $invoice['renew_auto'] ) {
									_h( 'This invoice will be automatically renewed on this date.' );

								} else {
									_h( 'You will be reminded to renew this invoice on this date. You will be given the option to renew this invoice closer to the renewal date (a new button will appear).' );
								}

								echo '<br/>';
								$element = array(
									'type'  => 'checkbox',
									'name'  => 'renew_auto',
									'value' => isset( $invoice['renew_auto'] ) && $invoice['renew_auto'],
									'label' => 'Automatically Renew',
									'help'  => 'This Invoice will be automatically renewed on this date. A new Invoice will be created as a copy from this Invoice. This invoice will only be renewed once it is paid.',
								);
								module_form::generate_form_element( $element );
								echo '<br/>';
								$element = array(
									'type'  => 'checkbox',
									'name'  => 'renew_email',
									'value' => isset( $invoice['renew_email'] ) && $invoice['renew_email'],
									'label' => 'Automatically Email',
									'help'  => 'When this Invoice is renewed it will be automatically emailed to the customer.',
								);
								module_form::generate_form_element( $element );
							}
						}
					}
				),
			);
		} else {
			$fieldset_data['elements'][] = array(
				'title'  => 'Renewal Date',
				'fields' => array(
					'(recurring invoices available in <a href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}

		$fieldset_data['elements'][] = array(
			'title'  => 'Cancel Date',
			'hidden' => ! ( (int) $invoice_id > 0 ),
			'fields' => array(
				array(
					'type'  => 'date',
					'name'  => 'date_cancel',
					'value' => print_date( $invoice['date_cancel'] ),
					'help'  => 'If the invoice has been cancelled set the date here. Payment reminders for this invoice will no longer be generated.',
				),
			),
		);


		if ( class_exists( 'module_company', false ) && module_company::is_enabled() && defined( 'COMPANY_UNIQUE_CONFIG' ) && COMPANY_UNIQUE_CONFIG && module_company::can_i( 'view', 'Company' ) && $invoice['customer_id'] > 0 ) {
			$company_list = module_company::get_companys_by_customer( $invoice['customer_id'] );
			if ( count( $company_list ) > 1 ) {
				$fieldset_data['elements'][] = array(
					'title'  => 'Company',
					'fields' => array(
						array(
							'type'             => 'select',
							'name'             => 'set_manual_company_id',
							'options'          => $company_list,
							'blank'            => _l( 'Default' ),
							'options_array_id' => 'name',
							'value'            => isset( $invoice['company_id'] ) ? $invoice['company_id'] : 0,
						),
					),
				);
			}
		}

		if ( class_exists( 'module_ticket' ) && module_ticket::is_plugin_enabled() ) {

			$linked_tickets = array();
			foreach ( module_invoice::get_invoice_tickets( $invoice['invoice_id'] ) as $ticket ) {
				$linked_tickets[] = $ticket['ticket_id'];
			}
			if ( ! $linked_tickets ) {
				$linked_tickets = array( false );
			} // so autoocomplete works. tood: move this into form.php
			$fieldset_data['elements'][] = array(
				'title'  => 'Linked Tickets',
				'fields' => array(
					'<div id="ticket_rel_ids_holder">',
					array(
						'type'     => 'text',
						'name'     => 'invoice_ticket_ids[]',
						'lookup'   => array(
							'key'         => 'ticket_id',
							'display_key' => 'subject',
							'plugin'      => 'ticket',
							'lookup'      => 'subject',
							'return_link' => true,
							'display'     => '',
						),
						'multiple' => 'ticket_rel_ids_holder',
						'values'   => $linked_tickets,
					),
					'</div>'
				),
			);
		}

		if ( class_exists( 'module_timer' ) && module_timer::is_plugin_enabled() && (int)$invoice['invoice_id'] > 0) {

			$linked_timers = array();
			$ucmtimers = new UCMTimers();
			$timers    = $ucmtimers->get( array(
				'invoice_id' => $invoice['invoice_id']
			), array( 'timer_id' => 'DESC' ) );
			foreach($timers as $timer){
				$UCMTimer = new UCMTimer($timer['timer_id']);
				$linked_timers[] = array(
					'description' => $UCMTimer->get('description'),
					'link' => $UCMTimer->link_open(),
					'time' => $UCMTimer->get_total_time(),
					'seconds' => $UCMTimer->get('duration_calc'),
				);
			}
			if ( $linked_timers ) {
				$fieldset_data['elements'][] = array(
					'title'  => 'Linked Timers',
					'fields' => array(
						'<div id="timer_rel_ids_holder">',
						function() use ($linked_timers){
							$total_time = 0;
							foreach($linked_timers as $linked_timer){
								echo '<a href="' . $linked_timer['link'] . '">' . htmlspecialchars($linked_timer['description']).'</a> (' . $linked_timer['time'] .' ) <br>';
								$total_time += $linked_timer['seconds'];
							}
							if(count($linked_timers)>1){
								echo 'Total Time: '.module_timer::format_seconds($total_time);
							}
						},
						'</div>'
					),
				);
			}
		}


		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );
	}


	hook_handle_callback( 'layout_column_half', 2, '65' );

	if ( ( $invoice['date_cancel'] && $invoice['date_cancel'] != '0000-00-00' ) ) {

		/**** INVOICE CANCELLED ***/
		ob_start();
		?>
		<div class="tableclass_form content">
			<p align="center"><?php echo _l( 'This invoice has been cancelled!' ); ?></p>
			<?php // we do a bit of a hack here to handle credit notes.
			// if this invoice has been cancelled (date_cancel) then we do a quick search for another invoice with a matching "credit_note_id" to this invoice.
			$other_invoice = module_invoice::get_invoices( array( 'credit_note_id' => $invoice_id ) );
			if ( count( $other_invoice ) ) {
				foreach ( $other_invoice as $other_i ) {
					// this invoice has been cancelled and there is another invoice that is a credit note against this invoice.
					?>
					<p
						align="center"><?php _e( 'A Credit Note has been generated against this cancelled invoice: %s', module_invoice::link_open( $other_i['invoice_id'], true, $other_i ) ); ?></p>
					<?php
				}
			} else if ( module_invoice::can_i( 'create', 'Invoices' ) ) {
				?>
				<p style="text-align: center">
					<input type="submit" name="butt_generate_credit" value="<?php _e( 'Generate Credit Note' ); ?>"
					       class="submit_button">
				</p>
				<?php
			}

			?>
		</div>
		<?php
		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Invoice Cancelled' ),
				'type'  => 'h3',
				'class' => 'error_text',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	} else if ( $invoice['overdue'] ) {

		/***** INVOICE OVERDUE ****/
		ob_start();
		?>
		<div class="tableclass_form content">
			<p
				align="center"><?php echo _l( 'This invoice has not been paid by the due date and %s is now overdue.', dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ) ); ?></p>
			<?php if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
				$secs = date( "U" ) - date( "U", strtotime( $invoice['date_sent'] ) );
				$days = $secs / 86400;
				$days = floor( $days );
				?>
				<p
					align="center"><?php echo _l( 'This invoice was last sent %s days ago on %s.', $days, print_date( $invoice['date_sent'] ) ); ?></p>
				<p align="center">
					<input type="submit" name="butt_email" id="butt_email_overdue"
					       value="<?php echo _l( 'Email Overdue Notice' ); ?>" class="submit_button"/>
				</p>
			<?php } else { ?>
				<p
					align="center"><?php _e( 'This invoice is overdue, but it has not been sent yet. Please pick a "Sent Date" or click below to send invoice.' ); ?></p>
				<p align="center">
					<input type="submit" name="butt_email" id="butt_email_overdue" value="<?php echo _l( 'Email Invoice' ); ?>"
					       class="submit_button btn"/>
				</p>
			<?php } ?>
		</div>
		<?php
		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Invoice Overdue' ),
				'type'  => 'h3',
				'class' => 'error_text',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}

	if ( isset( $invoice['deposit_job_id'] ) && $invoice['deposit_job_id'] > 0 ) {
		/**** DEPOSIT INVOICE *****/
		ob_start();
		?>
		<div class="tableclass_form content">
			<p align="center">
				<?php echo _l( 'This invoice is a deposit for the job: %s.', module_job::link_open( $invoice['deposit_job_id'], true ) ); ?>
				<br/>
				<?php _e( 'The deposit will apply to this job once this invoice is paid.' ); ?>
			</p>
		</div>
		<?php
		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Deposit Invoice' ),
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	} else if ( $invoice_id > 0 && $invoice['total_amount_due'] > 0 && module_security::is_page_editable() && module_invoice::can_i( 'create', 'Invoice Payments' ) ) {
		foreach ( $invoice['job_ids'] as $possible_job_id ) {
			// find any deposit invoices matching this job.
			$possible_invoices = module_invoice::get_invoices( array( 'deposit_job_id' => $possible_job_id ) );
			if ( $possible_invoices ) {
				foreach ( $possible_invoices as $possible_invoice ) {
					if ( $possible_invoice['invoice_id'] == $invoice_id ) {
						continue;
					} // skip me
					// see if this deposit invoice has been paid.
					$possible_invoice = module_invoice::get_invoice( $possible_invoice['invoice_id'] );
					if ( isset( $possible_invoice['deposit_remaining'] ) && $possible_invoice['deposit_remaining'] > 0 ) {
						// we have some cash that can be applied to this invoice from the deposit! woo!
						$this_take = min( $invoice['total_amount_due'], $possible_invoice['deposit_remaining'] );
						if ( $this_take > 0 ) {

							/** PREVIOUS DEPOSIT  */
							ob_start();
							?>
							<div class="tableclass_form content">
								<p align="center">
									<?php _e( 'The customer has paid a deposit on this Job: %s.', module_job::link_open( $possible_job_id, true ) ); ?>
									<br/>
									<?php _e( 'Deposit Invoice: %s paid on %s', module_invoice::link_open( $possible_invoice['invoice_id'], true, $possible_invoice ), print_date( $possible_invoice['date_paid'] ) ); ?>
									<br/>
									<?php _e( 'Please click the button below to apply this deposit.' ); ?>
									<br/>
									<a href="#"
									   onclick="$('#newinvoice_payment_type').val(<?php echo _INVOICE_PAYMENT_TYPE_DEPOSIT; ?>); $('#newinvoice_payment_other_id').val('<?php echo $possible_invoice['invoice_id']; ?>'); $('#newinvoice_paymentamount').val('<?php echo $this_take; ?>'); $('#newinvoice_payment_date').val('<?php echo print_date( $possible_invoice['date_paid'] ); ?>'); $('#add_payment_btn').click(); $('#add_payment').val('go'); $('#invoice_form')[0].submit(); return false;"
									   class="uibutton"><?php _e( 'Apply %s deposit to this invoice', dollar( $this_take, true, $invoice['currency_id'] ) ); ?></a>
								</p>
							</div>
							<?php
							$fieldset_data = array(
								'heading'         => array(
									'title' => _l( 'Apply Previous Deposit' ),
									'type'  => 'h3',
								),
								'elements_before' => ob_get_clean(),
							);
							echo module_form::generate_fieldset( $fieldset_data );
							unset( $fieldset_data );
						}
					}
				}
			}
		}
	}

	// check if there is any subscription credit available for this customer
	$subscription_credits = array();
	if ( (int) $invoice_id > 0 && ! $invoice_locked && class_exists( 'module_subscription' ) && module_subscription::is_plugin_enabled() ) {
		if ( $customer_data && $customer_data['customer_id'] ) {
			$customer_credit = module_subscription::get_available_credit( 'customer', $customer_data['customer_id'] );
			foreach ( $customer_credit as $subscription_id => $c ) {
				if ( $c['remain'] > 0 ) {
					$subscription_credits[ $subscription_id ] = $c;
				}
			}
		}
		if ( count( $invoice['job_ids'] ) ) {
			foreach ( $invoice['job_ids'] as $job_id ) {
				// linked website?
				$job_data = module_job::get_job( $job_id, false );
				if ( $job_data && $job_data['website_id'] ) {
					$website_credit = module_subscription::get_available_credit( 'website', $job_data['website_id'] );
					foreach ( $website_credit as $subscription_id => $c ) {
						if ( $c['remain'] > 0 ) {
							$subscription_credits[ $subscription_id ] = $c;
						}
					}
				}
			}
		}
	}
	if ( (int) $invoice_id > 0 && module_invoice::can_i( 'edit', 'Invoices' ) && $invoice['total_amount_due'] > 0 && $customer_data && ( count( $subscription_credits ) || $customer_data['credit'] > 0 ) && ( ! $invoice['date_cancel'] || $invoice['date_cancel'] == '0000-00-00' ) ) {

		/** CREDIT  */
		ob_start();
		?>
		<div class="tableclass_form content">
			<?php if ( count( $subscription_credits ) ) {
				?>
				<input type="hidden" name="apply_credit_from_subscription_bucket" id="apply_credit_from_subscription_bucket"
				       value="0">
				<input type="hidden" name="apply_credit_from_subscription_id" id="apply_credit_from_subscription_id" value="0">
				<?php
				foreach ( $subscription_credits as $subscription_id => $subscription_credit ) {
					if ( $subscription_credit['remain'] > 0 ) {
						$apply_credit = min( $invoice['total_amount_due'], $subscription_credit['remain'] );
						?>
						<p align="center">
							<?php _e( 'The customer has %s credit available in their %s subscription.', dollar( $subscription_credit['remain'] ), module_subscription::link_open( $subscription_credit['subscription_id'], true ) ); ?>
							<br/>
							<?php _e( 'Please click the button below to apply the credit to this invoice.' ); ?>
							<br/>
							<a href="#"
							   onclick="$('#apply_credit_from_subscription_bucket').val('do'); $('#apply_credit_from_subscription_id').val('<?php echo $subscription_credit['subscription_owner_id']; ?>'); $('#invoice_form')[0].submit(); return false;"
							   class="uibutton"><?php _e( 'Apply %s credit to this invoice', dollar( $apply_credit ) ); ?></a>
						</p>
					<?php }
				}
			}
			if ( $customer_data['credit'] > 0 ) {
				$apply_credit = min( $invoice['total_amount_due'], $customer_data['credit'] );
				?>
				<p align="center">
					<?php _e( 'The customer has a %s credit on their account.', dollar( $customer_data['credit'], true, $invoice['currency_id'] ) ); ?>
					<br/>
					<?php _e( 'Please click the button below to apply the credit to this invoice.' ); ?>
					<br/>
					<!-- <a href="#" onclick="$('#newinvoice_payment_type').val(<?php echo _INVOICE_PAYMENT_TYPE_CREDIT; ?>); $('#newinvoice_paymentamount').val('<?php echo $apply_credit; ?>'); $('#newinvoice_payment_date').val('<?php echo print_date( time() ); ?>'); $('#add_payment_btn').click(); $('#add_payment').val('go'); $('#invoice_form')[0].submit(); return false;" class="uibutton"><?php _e( 'Apply %s credit to this invoice', dollar( $apply_credit, true, $invoice['currency_id'] ) ); ?></a> -->
					<a href="#"
					   onclick="$('#apply_credit_from_customer').val('do'); $('#invoice_form')[0].submit(); return false;"
					   class="uibutton"><?php _e( 'Apply %s credit to this invoice', dollar( $apply_credit, true, $invoice['currency_id'] ) ); ?></a>
				</p>
				<input type="hidden" name="apply_credit_from_customer" id="apply_credit_from_customer" value="0">
			<?php } ?>
		</div>
		<?php

		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Customer Credit Available' ),
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	} ?>

	<script type="text/javascript">
      function setamount(a, invoice_item_id, rate) {
          if (!rate) rate = $('#' + invoice_item_id + 'invoice_itemrate').val();
          var amount = 0;
          if (a.match(/:/)) {
              var bits = a.split(':');
              var hours = bits[0].length > 0 ? parseInt(bits[0]) : 0;
              var minutes = 0;
              if (typeof bits[1] != 'undefined' && bits[1].length > 0) {
                  if (bits[1].length == 1) {
                      // it's a 0 or a 123456789
                      if (bits[1] == "0") {
                          minutes = 0;
                      } else {
                          minutes = parseInt(bits[1] + "0");
                      }
                  } else {
                      minutes = parseInt(bits[1]);
                  }
              }
              if (hours > 0 || minutes > 0) {
                  amount = rate * hours;
                  amount += rate * (minutes / 60);
              }
          } else {
              var bits = a.split('<?php echo module_config::c( 'currency_decimal_separator', '.' );?>');
              var number = bits[0].length > 0 ? parseInt(bits[0]) : 0;
              number += typeof bits[1] != 'undefined' && parseInt(bits[1]) > 0 ? parseFloat("." + bits[1]) : 0;
              amount = rate * number;
          }
          var places = <?php echo str_pad( '1', (int) module_config::c( 'currency_decimal_places', 2 ) + 1, '0', STR_PAD_RIGHT );?>;
          amount = Math.round(amount * places) / places;
          $('#' + invoice_item_id + 'invoice_itemamount').html(amount);
      }

      function setamount2(a, invoice_item_id, rate) {
          var ee = parseFloat(a);
          if (!rate) rate = $('#' + invoice_item_id + 'invoice_itemrate').val();
          if (typeof a != 'undefined' && a.length > 0 && ee >= 0) {
              $('#' + invoice_item_id + 'invoice_itemamount').html(ee * rate);
          } else {
              $('#' + invoice_item_id + 'invoice_itemamount').html(rate);
          }
      }

      function editinvoice_item(invoice_item_id, hours) {
          $('#invoice_item_preview_' + invoice_item_id).hide();
          $('#invoice_item_edit_' + invoice_item_id).show();
          if (hours > 0) {
              $('#complete_' + invoice_item_id).val(hours);
              if (typeof $('#complete_t_' + invoice_item_id)[0] != 'undefined') {
                  $('#complete_t_' + invoice_item_id)[0].checked = true;
              }
          } else {
              $('#invoice_item_desc_' + invoice_item_id)[0].focus();
          }
      }

      $(function () {
          $('#invoice_task_items').delegate('.task_toggle_long_description', 'click', function (event) {
              event.preventDefault();
              $(this).parent().find('.task_long_description').slideToggle(function () {
                  if ($('textarea.edit_task_long_description').length > 0) {
                      $('textarea.edit_task_long_description')[0].focus();
                  }
              });
              return false;
          });
      });
	</script>

	<?php
	// we check for duplicate invoice numbers.
	if ( $invoice_id > 0 && $duplicates = $UCMInvoice->find_duplicate_numbers() ) {

		$new_invoice_number = $UCMInvoice->get_new_document_number();

		/**** MERGE INVOICES ****/
		ob_start();
		?>
		<div class="content_box_wheader" style="padding-bottom: 20px">
			<p>
				<?php _e( 'We found %s other invoice with the same invoice number.', count( $duplicates ) ); ?>
			</p>
			<ul>
				<?php foreach ( $duplicates as $duplicate_invoice ) {
					$other_invoice = module_invoice::get_invoice( $duplicate_invoice['invoice_id'] );
					?>
					<li>
						<?php
						echo module_invoice::link_open( $other_invoice['invoice_id'], true );
						?>
					</li>
				<?php }
				?>
			</ul>
			<input type="button" name="butt_rename_invoice"
			       value="<?php _e( 'Rename This Invoice to %s', $new_invoice_number ); ?>" class="submit_button"
			       onclick="$('#invoice_number').val('<?php echo $new_invoice_number; ?>'); this.form.submit();">
		</div>
		<?php

		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Duplicate Invoice Numbers' ),
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );
	}

	// here we check if this invoice can be merged with any other invoices.
	if ( $invoice_id > 0 && ! $invoice_locked && module_invoice::can_i( 'edit', 'Invoices' ) && ( ! isset( $invoice['deposit_job_id'] ) || ! $invoice['deposit_job_id'] ) && ( ! $invoice['date_cancel'] || $invoice['date_cancel'] == '0000-00-00' ) && ! $invoice['total_amount_paid'] && ( $invoice['customer_id'] > 0 || module_config::c( 'invoice_allow_merge_no_customer', 0 ) ) ) {
		$merge_invoice_ids = module_invoice::check_invoice_merge( $invoice_id );
		if ( $merge_invoice_ids ) {

			/**** MERGE INVOICES ****/
			ob_start();
			?>
			<div class="content_box_wheader" style="padding-bottom: 20px">
				<p>
					<?php _e( 'We found %s other invoices from this customer that can be merged.', count( $merge_invoice_ids ) ); ?>
					<?php _h( 'You can generate invoices from multiple jobs (eg: a Hosting Setup job and a Web Development job) then you can combine them together here and send them as a single invoice to the customer, rather than sending multiple invoices.' ); ?>
				</p>
				<ul>
					<?php foreach ( $merge_invoice_ids as $merge_invoice ) {
						$merge_invoice = module_invoice::get_invoice( $merge_invoice['invoice_id'] );
						?>
						<li>
							<?php if ( $merge_invoice['total_amount_paid'] ) {
								echo module_invoice::link_open( $merge_invoice['invoice_id'], true );
								echo ' ';
								_e( '(cannot merge, invoice already has payment)' );
							} else {
								?>
								<input type="checkbox" name="merge_invoice[<?php echo $merge_invoice['invoice_id']; ?>]" value="1">
								<?php echo module_invoice::link_open( $merge_invoice['invoice_id'], true ); ?>
								<?php echo dollar( $merge_invoice['total_amount'], true, $invoice['currency_id'] ); ?>
								<?php if ( $merge_invoice['discount_amount'] > 0 ) {
									_e( '(You will have to apply the %s discount to this invoice again manually.)', dollar( $merge_invoice['discount_amount'], true, $invoice['currency_id'] ) );
								} ?>
							<?php } ?>
						</li>
					<?php }
					?>
				</ul>
				<input type="hidden" name="butt_merge" value="" id="butt_merge">
				<input type="button" name="butt_merge_do" value="<?php _e( 'Merge selected invoices into this invoice' ); ?>"
				       class="submit_button" onclick="$('#butt_merge').val(1); this.form.submit();">
			</div>
			<?php

			$fieldset_data = array(
				'heading'         => array(
					'title' => _l( 'Merge Customer Invoices' ),
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
	}

	/***** INVOICE ITEMS! *****/
	ob_start();
	?>
	<div class="content_box_wheader">
		<table border="0" cellspacing="0" cellpadding="2" id="invoice_task_items"
		       class="tableclass tableclass_rows tableclass_full">
			<thead>
			<tr>
				<?php if ( module_config::c( 'invoice_task_numbers', 1 ) ) { ?>
					<th width="10">#</th>
				<?php } ?>
				<th class="invoice_item_column"><?php _e( 'Description' ); ?></th>
				<?php if ( $show_task_dates ) { ?>
					<th width="10%"><?php _e( 'Date' ); ?></th>
				<?php } ?>
				<th width="10%">
					<?php
					$unit_measurement = false;
					if ( is_callable( 'module_product::sanitise_product_name' ) ) {
						$fake_task        = module_product::sanitise_product_name( array(), $invoice['default_task_type'] );
						$unit_measurement = $fake_task['unitname'];
						foreach ( $invoice_items as $task_data ) {
							if ( isset( $task_data['unitname'] ) && $task_data['unitname'] != $unit_measurement ) {
								$unit_measurement = false;
								break; // show nothing at title of quote page.
							}
						}
					}
					echo _l( $unit_measurement ? $unit_measurement : module_config::c( 'task_default_name', 'Unit' ) );
					?>
				</th>
				<th width="10%">
					<?php _e( module_config::c( 'invoice_amount_name', 'Amount' ) ); ?>
				<th width="10%"><?php _e( 'Sub-Total' ); ?></th>
				<th width="80"></th>
			</tr>
			</thead>
			<?php if ( ! $invoice_locked && module_invoice::can_i( 'edit', 'Invoices' ) ) { ?>
				<tbody>
				<tr>
					<?php if ( module_config::c( 'invoice_task_numbers', 1 ) ) { ?>
						<td>
							<input type="text" name="invoice_invoice_item[new][task_order]" value="" id="next_task_number" size="3"
							       class="edit_task_order">
						</td>
					<?php } ?>
					<td>
						<input type="text" name="invoice_invoice_item[new][description]" value="" style="width:90%;"
						       class="edit_task_description" id="invoice_item_desc_new" data-id="new"><?php
						if ( class_exists( 'module_product', false ) ) {
							// looks for class edit_task_description
							module_product::print_invoice_task_dropdown( 'new' );
						} ?>
						<a href="#" class="task_toggle_long_description"><i class="fa fa-plus"></i></a>
						<div class="task_long_description">
							<?php module_form::generate_form_element( array(
								'type'  => module_config::c( 'long_description_wysiwyg', 1 ) ? 'wysiwyg' : 'textarea',
								'name'  => 'invoice_invoice_item[new][long_description]',
								'id'    => 'new_task_long_description',
								'class' => 'edit_task_long_description no_permissions',
								'value' => '',
							) ); ?>
						</div>
					</td>
					<?php if ( $show_task_dates ) { ?>
						<td>
							<input type="text" name="invoice_invoice_item[new][date_done]" value="" class="date_field">
						</td>
					<?php } ?>
					<td>
						<?php if ( $invoice['default_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
							?>
							-
							<?php
						} else if ( $invoice['default_task_type'] == _TASK_TYPE_QTY_AMOUNT || $invoice['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
							?>
							<input type="text" name="invoice_invoice_item[new][hours]" value="" id="newinvoice_itemqty" size="3"
							       style="width:30px;" onchange="setamount(this.value,'new');" onkeyup="setamount(this.value,'new');">
							<?php
						} ?>

					</td>
					<td>
						<input type="text" name="invoice_invoice_item[new][hourly_rate]"
						       value="<?php echo $invoice['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ? $invoice['hourly_rate'] : 0; ?>"
						       id="newinvoice_itemrate" size="3" style="width:35px;"
						       onchange="setamount($('#newinvoice_itemqty').val(),'new');"
						       onkeyup="setamount($('#newinvoice_itemqty').val(),'new');">
					</td>
					<td nowrap="">
                                <span class="currency">
                                <?php
                                //name="invoice_invoice_item[new][amount]"
                                echo currency( '<span value="" id="newinvoice_itemamount" class="">0</span>', true, $invoice['currency_id'] ); ?>
                                </span>
					</td>
					<td align="center">
						<input type="submit" name="save" value="<?php _e( 'Add Item' ); ?>" class="save_invoice_item small_button">
						<input type="hidden" name="invoice_invoice_item[new][taxable_t]" value="1">
						<input type="hidden" name="invoice_invoice_item[new][taxable]" id="invoice_taxable_item_new"
						       value="<?php echo module_config::c( 'task_taxable_default', 1 ) ? 1 : 0; ?>">
						<input type="hidden" name="invoice_invoice_item[new][manual_task_type]" id="manual_task_type_new"
						       value="-1">
					</td>
				</tr>
				</tbody>
			<?php }
			?>
			<?php
			$c = 0;
			/*[new3] => Array
                        (
                            [task_id] => 46
                            [timer_id] => 46
                            [job_id] => 15
                            [hours] => 0.00    ***********
                            [amount] => 20.00    ***********
                            [hourly_rate] => 60.00    ***********
                            [taxable] => 1
                            [billable] => 1
                            [fully_completed] => 1
                            [description] => test with fixed price ($20 one) sdgsdfg
                            [long_description] =>
                            [date_due] => 2012-05-18
                            [date_done] => 2012-07-16
                            [invoice_id] =>
                            [user_id] => 1
                            [approval_required] => 0
                            [task_order] => 8
                            [create_user_id] => 1
                            [update_user_id] => 1
                            [date_created] => 2012-07-16
                            [date_updated] => 2012-07-28
                            [id] => 46
                            [completed] =>
                            [custom_description] =>
                        )*/

			$task_decimal_places = module_config::c( 'task_amount_decimal_places', - 1 );
			if ( $task_decimal_places < 0 ) {
				$task_decimal_places = false; // use default currency dec places.
			}
			$task_decimal_places_trim = module_config::c( 'task_amount_decimal_places_trim', 0 );

			$show_invoice_job_names = module_config::c( 'show_invoice_job_names', 0 );
			// 0 = only show on combined invoices.
			// 1 = always show job names.
			if ( ! $show_invoice_job_names ) {
				if ( count( $invoice['job_ids'] ) ) {
					$show_invoice_job_names = 1;
				}
			}


			foreach ( $invoice_items as $invoice_item_id => $invoice_item_data ) {
				?>
				<?php if ( ! $invoice_locked ) { ?>
					<tbody id="invoice_item_edit_<?php echo $invoice_item_id; ?>" style="display:none;">
					<tr>
						<?php if ( module_config::c( 'invoice_task_numbers', 1 ) ) { ?>
							<td>
								<input type="text" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][task_order]" value="<?php
								if ( isset( $invoice_item_data['custom_task_order'] ) && (int) $invoice_item_data['custom_task_order'] > 0 ) {
									echo $invoice_item_data['custom_task_order'];
								} else if ( isset( $invoice_item_data['task_order'] ) && $invoice_item_data['task_order'] > 0 ) {
									echo $invoice_item_data['task_order'];
								}
								?>" size="3" class="edit_task_order">
							</td>
						<?php } ?>
						<td>
							<input type="hidden" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][task_id]"
							       value="<?php echo htmlspecialchars( $invoice_item_data['task_id'] ); ?>">
							<input type="hidden" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][timer_id]"
							       value="<?php echo htmlspecialchars( $invoice_item_data['timer_id'] ); ?>">

							<input type="text" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][description]"
							       value="<?php echo htmlspecialchars( $invoice_item_data['custom_description'] ? $invoice_item_data['custom_description'] : $invoice_item_data['description'] ); ?>"
							       style="width:90%;" class="edit_task_description"
							       id="invoice_item_desc_<?php echo $invoice_item_id; ?>"
							       data-id="<?php echo $invoice_item_id; ?>"><?php
							if ( class_exists( 'module_product', false ) ) {
								// looks for class edit_task_description
								module_product::print_invoice_task_dropdown( $invoice_item_id, $invoice_item_data );
							} ?>
							<br/>
							<?php
							module_form::generate_form_element( array(
								'type'  => module_config::c( 'long_description_wysiwyg', 1 ) ? 'wysiwyg' : 'textarea',
								'name'  => 'invoice_invoice_item[' . $invoice_item_id . '][long_description]',
								'id'    => 'task_long_desc_' . $invoice_item_id,
								'class' => 'edit_task_long_description',
								'value' => $invoice_item_data['custom_long_description'] ? $invoice_item_data['custom_long_description'] : $invoice_item_data['long_description'],
							) );

							if ( $invoice_item_data['task_id'] ) {
								// echo htmlspecialchars($invoice_item_data['custom_description'] ? $invoice_item_data['custom_description'] : $invoice_item_data['description']);
								echo '<br/>';
								echo _l( '(linked to job: %s)', module_job::link_open( $invoice_item_data['job_id'], true ) );
							} else {
							} ?>
							<a href="#"
							   onclick="if(confirm('<?php _e( 'Delete invoice item?' ); ?>')){$(this).parent().find('input').val(''); $('#invoice_form')[0].submit();} return false;"
							   class="delete" style="display:inline-block; float:right;"><i class="fa fa-trash"></i></a>
						</td>
						<?php if ( $show_task_dates ) { ?>
							<td>
								<input type="text" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][date_done]"
								       value="<?php echo print_date( $invoice_item_data['date_done'] ); ?>" class="date_field">
							</td>
						<?php } ?>
						<td class="nowrap">
							<?php if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
								echo '-';
							} else {
								if ( $invoice_item_data['hours'] != 0 ) {
									if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_out' ) ) {
										$hours_value = isset( $invoice_item_data['hours_mins'] ) && ! empty( $invoice_item_data['hours_mins'] ) ? $invoice_item_data['hours_mins'] : decimal_time_out( $invoice_item_data['hours'] );
									} else {
										$hours_value = number_out( $invoice_item_data['hours'], true );
									}
								} else {
									$hours_value = false;
								}
								?>
								<input type="text" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][hours]"
								       value="<?php echo $hours_value; ?>" size="3" style="width:30px;"
								       onchange="setamount(this.value,'<?php echo $invoice_item_id; ?>',<?php echo $invoice_item_data['task_hourly_rate']; ?>);"
								       id="<?php echo $invoice_item_id; ?>invoice_itemqty"
								       onkeyup="setamount(this.value,'<?php echo $invoice_item_id; ?>',<?php echo $invoice_item_data['task_hourly_rate']; ?>);">
								<?php
								if ( ! empty( $invoice_item_data['unitname'] ) && ! empty( $invoice_item_data['unitname_show'] ) ) {
									echo ' ' . $invoice_item_data['unitname'];
								}
							} ?>
						</td>
						<td>
							<input type="text" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][hourly_rate]"
							       value="<?php echo number_out( $invoice_item_data['task_hourly_rate'], $task_decimal_places_trim, $task_decimal_places ); ?>"
							       id="<?php echo $invoice_item_id; ?>invoice_itemrate" size="3" style="width:35px;"
							       onchange="setamount($('#<?php echo $invoice_item_id; ?>invoice_itemqty').val(),<?php echo $invoice_item_id; ?>);"
							       onkeyup="setamount($('#<?php echo $invoice_item_id; ?>invoice_itemqty').val(),<?php echo $invoice_item_id; ?>);">
						</td>
						<td nowrap="">
                                        <span class="currency">
                                <?php
                                //name="invoice_invoice_item[new][amount]"
                                echo currency( '<span value="" id="' . $invoice_item_id . 'invoice_itemamount" class="">' . $invoice_item_data['invoice_item_amount'] . '</span>', true, $invoice['currency_id'] ); ?>
                                </span>

							<?php
							//echo currency('<input type="text" name="invoice_invoice_item['.$invoice_item_id.'][amount]" value="'.$invoice_item_data['invoice_item_amount'].'" id="'.$invoice_item_id.'invoice_itemamount" class="currency">',true,$invoice['currency_id']);?>
						</td>
						<td nowrap="nowrap" align="center">
							<input type="submit" name="ts" class="save_invoice_item small_button" value="<?php _e( 'Save' ); ?>">
						</td>
					</tr>
					<tr>
						<?php if ( module_config::c( 'invoice_task_numbers', 1 ) ) { ?>
							<td>
							</td>
						<?php } ?>
						<td>
						</td>
						<?php if ( $show_task_dates ) { ?>
							<td>
							</td>
						<?php } ?>
						<td colspan="2">
							<?php $types = module_job::get_task_types();
							$types['-1'] = _l( 'Default (%s)', $types[ $invoice['default_task_type'] ] );
							module_form::generate_form_element( array(
								'type'    => 'select',
								'name'    => 'invoice_invoice_item[' . $invoice_item_id . '][manual_task_type]',
								'id'      => 'manual_task_type_' . $invoice_item_id,
								'options' => $types,
								'blank'   => false,
								'value'   => $invoice_item_data['manual_task_type_real'],
							) );
							?>
						</td>
						<td colspan="2">
							<input type="hidden" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][taxable_t]" value="1">
							<input type="checkbox" name="invoice_invoice_item[<?php echo $invoice_item_id; ?>][taxable]"
							       id="invoice_taxable_item_<?php echo $invoice_item_id; ?>"
							       value="1" <?php echo $invoice_item_data['taxable'] ? ' checked' : ''; ?> tabindex="17"> <label
								for="invoice_taxable_item_<?php echo $invoice_item_id; ?>"><?php _e( 'Item is taxable' ); ?></label>
						</td>

					</tr>
					</tbody>
				<?php } ?>
				<tbody id="invoice_item_preview_<?php echo $invoice_item_id; ?>">
				<tr class="<?php echo $c ++ % 2 ? 'odd' : 'even'; ?>">
					<?php if ( module_config::c( 'invoice_task_numbers', 1 ) ) { ?>
						<td>
							<?php
							if ( isset( $invoice_item_data['custom_task_order'] ) && (int) $invoice_item_data['custom_task_order'] > 0 ) {
								echo $invoice_item_data['custom_task_order'];
							} else if ( isset( $invoice_item_data['task_order'] ) && $invoice_item_data['task_order'] > 0 ) {
								echo $invoice_item_data['task_order'];
							}
							?>
						</td>
					<?php } ?>
					<td>
						<?php
						if ( $show_invoice_job_names == 1 && ! empty( $invoice_item_data['job_id'] ) ) {
							?>
							<span class="invoice_job_name">(<?php echo module_job::link_open( $invoice_item_data['job_id'], true ); ?>
								)</span>
							<?php
						}
						$desc = $invoice_item_data['custom_description'] ? htmlspecialchars( $invoice_item_data['custom_description'] ) : htmlspecialchars( $invoice_item_data['description'] );
						if ( $invoice_locked ) {
							echo $desc;
						} else { ?>
							<a href="#"
							   onclick="editinvoice_item('<?php echo $invoice_item_id; ?>',0); return false;"><?php echo ( ! trim( $desc ) ) ? 'N/A' : $desc; ?></a>
						<?php }
						$long_description = trim( $invoice_item_data['custom_long_description'] ? $invoice_item_data['custom_long_description'] : $invoice_item_data['long_description'] );
						if ( $long_description != '' ) { ?>
							<a href="#" class="task_toggle_long_description">&raquo;</a>
							<div
								class="task_long_description" <?php if ( module_config::c( 'invoice_show_long_desc', 1 ) ) { ?> style="display:block;" <?php } ?>><?php
								// backwards compat for non-html code:
								if ( ! is_text_html( $long_description ) ) {
									// plain text. html it
									$long_description = forum_text( $long_description, false );
								}
								echo module_security::purify_html( $long_description ); ?></div>
						<?php } else { ?>
							&nbsp;
						<?php } ?>
					</td>
					<?php if ( $show_task_dates ) { ?>
						<td>
							<?php echo print_date( $invoice_item_data['date_done'] ); ?>
						</td>
					<?php } ?>
					<td class="nowrap">
						<?php
						if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
							echo $invoice_item_data['hours'] > 0 ? $invoice_item_data['hours'] : '-';
						} else {
							if ( $invoice_item_data['hours'] != 0 ) {
								if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_out' ) ) {
									//$hours_value = decimal_time_out($invoice_item_data['hours']);
									$hours_value = isset( $invoice_item_data['hours_mins'] ) && ! empty( $invoice_item_data['hours_mins'] ) ? $invoice_item_data['hours_mins'] : decimal_time_out( $invoice_item_data['hours'] );
								} else {
									$hours_value = number_out( $invoice_item_data['hours'], true );
								}
							} else {
								$hours_value = false;
							}
							echo $hours_value ? $hours_value . ( ! empty( $invoice_item_data['unitname'] ) && ! empty( $invoice_item_data['unitname_show'] ) ? ' ' . $invoice_item_data['unitname'] : '' ) : '-';
						}
						?>
					</td>
					<td>
						<?php
						if ( $invoice_item_data['task_hourly_rate'] != 0 ) {
							echo dollar( $invoice_item_data['task_hourly_rate'], true, $invoice['currency_id'], $task_decimal_places_trim, $task_decimal_places );
						} else {
							echo '-';
						}
						?>
					</td>
					<td>
                                        <span class="currency">
                                        <?php
                                        echo dollar( $invoice_item_data['invoice_item_amount'], true, $invoice['currency_id'] );
                                        ?>
                                        </span>
					</td>
					<td align="center">
						&nbsp;
					</td>
				</tr>
				</tbody>
			<?php }

			$rows = array();
			// we hide invoice tax if there is none
			$hide_tax = true;
			foreach ( $invoice['taxes'] as $invoice_tax ) {
				if ( isset( $invoice_tax['percent'] ) && $invoice_tax['percent'] > 0 ) {
					$hide_tax = false;
					break;
				}
			}
			if ( $invoice['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
				$rows[] = array(
					'label' => _l( 'Sub:' ),
					'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'] + $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>'
				);
				if ( $invoice['discount_amount'] > 0 ) {
					$rows[] = array(
						'label' => htmlspecialchars( _l( $invoice['discount_description'] ) ),
						'value' => '<span class="currency">' . dollar( $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>'
					);
					$rows[] = array(
						'label' => _l( 'Sub:' ),
						'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'], true, $invoice['currency_id'] ) . '</span>'
					);
				}
				if ( ! $hide_tax ) {
					foreach ( $invoice['taxes'] as $invoice_tax ) {
						$rows[] = array(
							'label' => _l( 'Tax:' ),
							'value' => '<span class="currency">' . dollar( $invoice_tax['amount'], true, $invoice['currency_id'] ) . '</span>',
							'extra' => $invoice_tax['name'] . ' = ' . number_out( $invoice_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
						);
					}
				}

			} else if ( $invoice['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
				$rows[] = array(
					'label' => _l( 'Sub:' ),
					'value' => '<span class="currency">' . dollar( $invoice['total_sub_amount'], true, $invoice['currency_id'] ) . '</span>'
				);
				if ( ! $hide_tax ) {
					foreach ( $invoice['taxes'] as $invoice_tax ) {
						$rows[] = array(
							'label' => _l( 'Tax:' ),
							'value' => '<span class="currency">' . dollar( $invoice_tax['amount'], true, $invoice['currency_id'] ) . '</span>',
							'extra' => $invoice_tax['name'] . ' = ' . number_out( $invoice_tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) . '%',
						);
					}
					$rows[] = array(
						'label' => _l( 'Sub:' ),
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
			?>
			<tfoot style="border-top:1px solid #CCC;">
			<?php foreach ( $rows as $row ) { ?>
				<tr>
					<td colspan="<?php echo $colspan; ?>">
						&nbsp;
					</td>
					<td>
						<?php echo $row['label']; ?>
					</td>
					<td>
						<?php echo $row['value']; ?>
					</td>
					<td colspan="2">
						<?php echo isset( $row['extra'] ) ? $row['extra'] : '&nbsp;'; ?>
					</td>
				</tr>
			<?php } ?>

			<tr>
				<td colspan="<?php echo $colspan + 4; ?>">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="<?php echo $colspan; ?>" align="right">

				</td>
				<td>
					<?php _e( 'Paid:' ); ?>
				</td>
				<td>
                                <span class="currency success_text">
                                    <?php echo dollar( $invoice['total_amount_paid'], true, $invoice['currency_id'] ); ?>
                                </span>
				</td>
				<td colspan="2">
					<?php _h( 'This is how much the customer has paid against the invoice. When they have paid the due amount the invoice will be marked as paid.' ); ?>
				</td>
			</tr>
			<tr>
				<td colspan="<?php echo $colspan; ?>" align="right">

				</td>
				<td>
					<?php _e( 'Due:' ); ?>
				</td>
				<td>
                                <span class="currency error_text">
                                    <?php echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
                                </span>
				</td>
				<td colspan="2">
					&nbsp;
				</td>
			</tr>
			<?php if ( $invoice['total_amount_credit'] > 0 ) { ?>
				<tr>
					<td colspan="<?php echo $colspan; ?>" align="center">
						<a
							href="<?php echo module_invoice::link_open( $invoice_id ); ?>&_process=assign_credit_to_customer"><?php _e( 'This customer has overpaid this invoice. Click here to assign this as credit to their account for a future invoice.' ); ?></a>
					</td>
					<td>
						<?php _e( 'Credit:' ); ?>
					</td>
					<td>
                                <span class="currency success_text">
                                    <?php echo dollar( $invoice['total_amount_credit'], true, $invoice['currency_id'] ); ?>
                                </span>
					</td>
					<td colspan="2">

					</td>
				</tr>
			<?php } ?>
			</tfoot>
		</table>
	</div> <!-- content box -->
	<?php

	$fieldset_data = array(
		'heading'         => array(
			'title' => _l( 'Invoice Items' ),
			'type'  => 'h3',
		),
		'elements_before' => ob_get_clean(),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );


	if ( $invoice_id ) { ?>

		<script type="text/javascript">
        function editinvoice_payment(invoice_payment_id, hours) {
            $('#invoice_payment_preview_' + invoice_payment_id).hide();
            $('#invoice_payment_edit_' + invoice_payment_id).show();

        }
		</script>

	<?php

	/*** PAYMENT HISTORY ****/
	ob_start();
	?>

		<div class="content_box_wheader">

			<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full">
				<thead>
				<tr>
					<th><?php _e( 'Payment Date' ); ?></th>
					<th><?php _e( 'Payment Method' ); ?></th>
					<th><?php _e( 'Amount' ); ?></th>
					<th><?php _e( 'Details' ); ?></th>
					<th width="80"></th>
				</tr>
				</thead>
				<?php if ( module_security::is_page_editable() && module_invoice::can_i( 'create', 'Invoice Payments' ) ) { //

					$payment_date = time(); // today.
					if ( isset( $invoice['date_due'] ) && $invoice['date_due'] != '' && $invoice['date_due'] != '0000-00-00' && strtotime( $invoice['date_due'] ) < $payment_date ) {
						$payment_date = strtotime( $invoice['date_due'] );
					}

					?>
					<tbody>
					<tr>
						<td>
							<input type="text" name="invoice_invoice_payment[new][date_paid]" id="newinvoice_payment_date"
							       value="<?php echo print_date( $payment_date ); ?>" class="date_field">
							<input type="hidden" name="invoice_invoice_payment[new][payment_type]" id="newinvoice_payment_type"
							       value="<?php echo _INVOICE_PAYMENT_TYPE_NORMAL; ?>">
							<input type="hidden" name="invoice_invoice_payment[new][other_id]" id="newinvoice_payment_other_id"
							       value="">

						</td>
						<td>

							<?php
							echo print_select_box( module_invoice::get_payment_methods(), 'invoice_invoice_payment[new][method]', $default_payment_method_text, '', true, false, true ); ?>
						</td>
						<td nowrap="">
							<?php echo '<input type="text" name="invoice_invoice_payment[new][amount]" value="' . number_out( $invoice['total_amount_due'] ) . '" id="newinvoice_paymentamount" class="currency">'; ?>
							<?php echo print_select_box( get_multiple( 'currency', '', 'currency_id' ), 'invoice_invoice_payment[new][currency_id]', $invoice['currency_id'], '', false, 'code' ); ?>
						</td>
						<td>
							<input type="text" name="invoice_invoice_payment[new][custom_notes]" value="" size="20">
						</td>
						<td align="center">
							<input type="hidden" name="add_payment" value="0" id="add_payment">
							<input type="button" name="add_payment_btn" value="<?php _e( 'Add payment' ); ?>"
							       class="save_invoice_payment small_button"
							       onclick="$('#add_payment').val('go'); $('#invoice_form')[0].submit(); return false;">
						</td>
					</tr>
					</tbody>
				<?php } ?>
				<tbody>
				<?php foreach ( module_invoice::get_invoice_payments( $invoice_id ) as $invoice_payment_id => $invoice_payment_data ) {

					if ( module_invoice::can_i( 'edit', 'Invoice Payments' ) && module_security::is_page_editable() ) {
						?>
						<tr id="invoice_payment_edit_<?php echo $invoice_payment_id; ?>" style="display:none;">
							<td>
								<input type="text" name="invoice_invoice_payment[<?php echo $invoice_payment_id; ?>][date_paid]"
								       value="<?php echo print_date( $invoice_payment_data['date_paid'] ); ?>" class="date_field"
								       id="invoice_payment_desc_<?php echo $invoice_payment_id; ?>">
								<?php if ( module_invoice::can_i( 'delete', 'Invoice Payments' ) ) { ?>
									<a href="#"
									   onclick="if(confirm('<?php _e( 'Delete invoice payment?' ); ?>')){$('#<?php echo $invoice_payment_id; ?>invoice_paymentamount').val(''); $('#invoice_form')[0].submit();} return false;"
									   class="delete" style="display:inline-block;"><i class="fa fa-trash"></i></a>
								<?php } ?>
							</td>
							<td>
								<input type="text" name="invoice_invoice_payment[<?php echo $invoice_payment_id; ?>][method]"
								       value="<?php echo htmlspecialchars( $invoice_payment_data['method'] ); ?>" size="20">
							</td>
							<td nowrap="">
								<?php echo '<input type="text" name="invoice_invoice_payment[' . $invoice_payment_id . '][amount]" value="' . number_out( $invoice_payment_data['amount'] ) . '" id="' . $invoice_payment_id . 'invoice_paymentamount" class="currency">'; ?>
								<?php echo print_select_box( get_multiple( 'currency', '', 'currency_id' ), 'invoice_invoice_payment[' . $invoice_payment_id . '][currency_id]', $invoice_payment_data['currency_id'], '', false, 'code' ); ?>
							</td>
							<td>
								<?php
								$notes   = '';
								$details = false;
								if ( isset( $invoice_payment_data['data'] ) && $invoice_payment_data['data'] ) {
									$details = @unserialize( $invoice_payment_data['data'] );
									if ( $details && isset( $details['custom_notes'] ) ) {
										$notes = $details['custom_notes'];
									}
								}
								?>
								<input type="text" name="invoice_invoice_payment[<?php echo $invoice_payment_id; ?>][custom_notes]"
								       value="<?php echo htmlspecialchars( $notes ); ?>" size="20">
								<?php if ( $details ) {
									if ( isset( $details['log'] ) ) {
										?>
										<ul>
											<?php foreach ( $details['log'] as $log ) {
												echo '<li>' . $log . '</li>';
											} ?>
										</ul>
										<?php
									}
								} ?>
							</td>
							<td style="white-space: nowrap">
								<input type="submit" name="ts" class="save_invoice_payment small_button" value="<?php _e( 'Save' ); ?>">
								<?php if ( class_exists( 'module_finance', false ) && module_finance::is_plugin_enabled() && module_finance::can_i( 'view', 'Finance' ) && module_finance::is_enabled() ) {
									?> | <?php
									// check if this finance has been added to the finance section yet.
									$existing_finance = get_single( 'finance', 'invoice_payment_id', $invoice_payment_data['invoice_payment_id'] );
									if ( $existing_finance ) {
										?> <a
											href="<?php echo module_finance::link_open( $existing_finance['finance_id'] ); ?>"><?php _e( 'More' ); ?></a> | <?php
									} else {
										?> <a
											href="<?php echo module_finance::link_open( 'new', false ) . '&invoice_payment_id=' . $invoice_payment_data['invoice_payment_id']; ?>"><?php _e( 'More' ); ?></a> | <?php
									}
								}
								?>
								<a href="<?php echo module_invoice::link_receipt( $invoice_payment_data['invoice_payment_id'] ); ?>"
								   target="_blank"><?php _e( 'Receipt' ); ?></a>
							</td>
						</tr>
					<?php } ?>
					<tr id="invoice_payment_preview_<?php echo $invoice_payment_id; ?>">
						<td>
							<?php if ( module_invoice::can_i( 'edit', 'Invoice Payments' ) && module_security::is_page_editable() ) { ?>
								<?php echo ( ! trim( $invoice_payment_data['date_paid'] ) || $invoice_payment_data['date_paid'] == '0000-00-00' ) ? _l( 'Pending on %s', print_date( $invoice_payment_data['date_created'] ) ) : print_date( $invoice_payment_data['date_paid'] ); ?>
							<?php } else { ?>
								<?php echo print_date( $invoice_payment_data['date_paid'] ); ?>
							<?php }
							if ( $invoice_payment_data['date_paid'] == '0000-00-00' ) {
								?> (<a
									href="<?php echo htmlspecialchars( $_SERVER['REQUEST_URI'] ) . '&_process=make_payment&invoice_payment_id=' . $invoice_payment_id; ?>"><?php _e( 'complete' ); ?></a>) <?php
							}
							?>
						</td>
						<td>
							<?php
							switch ( $invoice_payment_data['payment_type'] ) {
								case _INVOICE_PAYMENT_TYPE_NORMAL:
									echo htmlspecialchars( $invoice_payment_data['method'] );
									break;
								case _INVOICE_PAYMENT_TYPE_CREDIT:
									_e( 'Credit from customer %s', module_customer::link_open( $invoice_payment_data['other_id'], true ) );
									break;
								case _INVOICE_PAYMENT_TYPE_DEPOSIT:
									_e( 'Deposit from invoice %s', module_invoice::link_open( $invoice_payment_data['other_id'], true ) );
									break;
								case _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT:
									_e( 'Assigning Credit to: %s', module_customer::link_open( $invoice['customer_id'], true ) );
									break;
								case _INVOICE_PAYMENT_TYPE_REFUND:
									_e( 'Refund: %s', htmlspecialchars( $invoice_payment_data['method'] ) );
									break;
								case _INVOICE_PAYMENT_TYPE_SUBSCRIPTION_CREDIT:
									$subscription_owner = module_subscription::get_subscription_owner( $invoice_payment_data['other_id'] );
									_e( 'Subscription credit from %s', module_subscription::link_open( $subscription_owner['subscription_id'], true ) );
									break;

							}
							?>
						</td>
						<td>
                                        <span class="currency">
                                        <?php /* echo $invoice_payment_data['amount']>0 ? dollar($invoice_payment_data['amount'],true,$invoice['currency_id']) : dollar($invoice_payment_data['hours']*$invoice['hourly_rate'],true,$invoice['currency_id']); */ ?>
                                        <?php echo dollar( $invoice_payment_data['amount'], true, $invoice_payment_data['currency_id'] );
                                        // is there a fee?
                                        if ( $invoice_payment_data['fee_total'] != 0 ) {
	                                        echo ' ';
	                                        _e( '(includes %s %s)', dollar( $invoice_payment_data['fee_total'], true, $invoice_payment_data['currency_id'] ), htmlspecialchars( $invoice_payment_data['fee_description'] ) );
                                        }
                                        ?>
                                        </span>
						</td>
						<td>
							<?php if ( isset( $invoice_payment_data['data'] ) && $invoice_payment_data['data'] ) {
								$details = @unserialize( $invoice_payment_data['data'] );
								if ( $details && ! empty( $details['custom_notes'] ) ) {
									echo htmlspecialchars( $details['custom_notes'] ) . '<br>';
								}
								if ( $details && isset( $details['log'] ) ) {
									?>
									<a href="#"
									   onclick="$('#details_<?php echo $invoice_payment_data['invoice_payment_id']; ?>').show(); $(this).hide(); return false;"><?php _e( 'Show...' ); ?></a>
									<div id="details_<?php echo $invoice_payment_data['invoice_payment_id']; ?>" style="display:none;">
										<ul>
											<?php foreach ( $details['log'] as $log ) {
												echo '<li>' . htmlspecialchars( $log ) . '</li>';
											} ?>
										</ul>
									</div>
									<?php
								}
							} ?>
						</td>
						<td align="center" style="white-space: nowrap">
							<?php if ( module_invoice::can_i( 'edit', 'Invoice Payments' ) && module_security::is_page_editable() ) { ?>
								<a href="#"
								   onclick="editinvoice_payment('<?php echo $invoice_payment_id; ?>',0); return false;"><?php _e( 'Edit' ); ?></a> |
								<?php
							}
							// more details to the finance section
							if ( class_exists( 'module_finance', false ) && module_finance::is_plugin_enabled() && module_finance::can_i( 'view', 'Finance' ) && module_finance::is_enabled() ) {
								// check if this finance has been added to the finance section yet.
								$existing_finance = get_single( 'finance', 'invoice_payment_id', $invoice_payment_data['invoice_payment_id'] );
								if ( $existing_finance ) {
									?> <a
										href="<?php echo module_finance::link_open( $existing_finance['finance_id'] ); ?>"><?php _e( 'More' ); ?></a> | <?php
								} else {
									?> <a
										href="<?php echo module_finance::link_open( 'new', false ) . '&invoice_payment_id=' . $invoice_payment_data['invoice_payment_id']; ?>"><?php _e( 'More' ); ?></a> | <?php
								}
							}
							?>
							<a href="<?php echo module_invoice::link_receipt( $invoice_payment_data['invoice_payment_id'] ); ?>"
							   target="_blank"><?php _e( 'Receipt' ); ?></a>
						</td>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>

		<?php


		$fieldset_data = array(
			'heading'         => array(
				'title' => _l( 'Invoice Payment History' ),
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

		if ( class_exists( 'module_finance', false ) && module_finance::is_plugin_enabled() && module_finance::can_i( 'view', 'Finance' ) && module_finance::is_enabled() && is_file( 'includes/plugin_finance/pages/finance_invoice_edit.php' ) && module_config::c( 'invoice_show_finances', 1 ) ) {
			include( 'includes/plugin_finance/pages/finance_invoice_edit.php' );
		}

	} // invoice_id check


	hook_handle_callback( 'layout_column_half', 'end' );


	$form_actions = array(
		'class'    => 'action_bar action_bar_left',
		'elements' => array(
			array(
				'type'    => 'save_button',
				'name'    => 'butt_save',
				'onclick' => "$('#form_redirect').val('" . ( ! $invoice_id && isset( $_REQUEST['job_id'] ) && (int) $_REQUEST['job_id'] > 0 ? module_job::link_open( $_REQUEST['job_id'] ) : module_invoice::link_open( false ) ) . "');",
				'value'   => _l( 'Save and Return' ),
			),
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'type'  => 'save_button',
				'class' => 'archive_button',
				'name'  => 'butt_archive',
				'value' => $UCMInvoice->is_archived() ? _l( 'Unarchive' ) : _l( 'Archive' ),
			),
		),
	);
	if ( (int) $invoice_id ) {
		if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) {
			$form_actions['elements'][] = array(
				'type'  => 'save_button',
				'class' => 'submit_button',
				'name'  => 'butt_email',
				'value' => _l( 'Email Receipt' ),
			);
		} else {
			$form_actions['elements'][] = array(
				'type'  => 'submit',
				'class' => 'submit_button',
				'name'  => 'butt_email',
				'value' => _l( 'Email Invoice' ),
			);
		}
		if ( function_exists( 'convert_html2pdf' ) ) {
			if ( ! module_invoice::can_i( 'edit', 'Invoices' ) ) {

				$form_actions['elements'][] = array(
					'type'    => 'button',
					'class'   => 'submit_button no_permissions',
					'name'    => 'butt_print',
					'value'   => _l( 'Print PDF' ),
					'onclick' => "window.location.href='" . module_invoice::link_public_print( $invoice_id ) . "';",
				);
			} else {
				$form_actions['elements'][] = array(
					'type'  => 'submit',
					'class' => 'submit_button',
					'name'  => 'butt_print',
					'value' => _l( 'Print PDF' ),
				);
			}
		}
	}
	if ( (int) $invoice_id && module_invoice::can_i( 'delete', 'Invoices' ) ) {
		$form_actions['elements'][] = array(
			'type'  => 'delete_button',
			'name'  => 'butt_del',
			'value' => _l( 'Delete' ),
		);
	}
	$form_actions['elements'][] = array(
		'type'    => 'button',
		'name'    => 'cancel',
		'value'   => _l( 'Cancel' ),
		'class'   => 'submit_button',
		'onclick' => "window.location.href='" . module_invoice::link_open( false ) . "';",
	);
	echo module_form::generate_form_actions( $form_actions );


	?>


</form>
