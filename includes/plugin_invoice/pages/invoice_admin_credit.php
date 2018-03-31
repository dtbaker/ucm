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
$ucminvoice = new UCMInvoice( $invoice_id );

if ( $invoice_id > 0 && $ucminvoice->get( 'invoice_id' ) == $invoice_id ) {
	$module->page_title = _l( 'Credit Note: #%s', htmlspecialchars( $ucminvoice['name'] ) );

	if ( ! $ucminvoice->check_permissions() ) {
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
	// load up the defaults:
	$invoice = module_invoice::get_invoice( $invoice_id );
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

$customer_data = array();
if ( $invoice['customer_id'] ) {
	$customer_data = module_customer::get_customer( $invoice['customer_id'] );
}

$show_task_dates = module_config::c( 'invoice_task_list_show_date', 1 );
$colspan         = 2;
if ( $show_task_dates ) {
	$colspan ++;
}


// find out all the payment methods.
$x = 1;

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
			'title' => 'Credit Note Details',
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title' => 'Credit Note #',
				'field' => array(
					'type'  => 'text',
					'name'  => 'name',
					'id'    => 'invoice_number',
					'value' => $invoice['name'],
				),
			),
			array(
				'title'  => 'Invoice #',
				'fields' => array(
					module_invoice::link_open( $invoice['credit_note_id'], true )
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
				'title'  => 'Sent Date',
				'hidden' => ! ( (int) $invoice_id ),
				'field'  => array(
					'type'  => 'date',
					'name'  => 'date_sent',
					'id'    => 'date_sent',
					'value' => print_date( $invoice['date_sent'] ),
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
				<a href="#" class="add_addit" onclick="seladd(this); ucm.invoice.update_invoice_tax(); return false;">+</a>
				<a href="#" class="remove_addit" onclick="selrem(this); ucm.invoice.update_invoice_tax(); return false;">-</a>
			</div>
		<?php } ?>
	</div>
	<script type="text/javascript">
      set_add_del('invoice_tax_holder');
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


	if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
		$fieldset_data['elements'][]                   = array(
			'title' => 'Customer',
			'field' => array(
				'type'   => 'text',
				'name'   => 'customer_id',
				'value'  => $invoice['customer_id'],
				'lookup' => array(
					'key'         => 'customer_id',
					'display_key' => 'customer_name',
					'plugin'      => 'customer',
					'lookup'      => 'customer_name',
					'return_link' => true,
					'display'     => '',
				),
			),
		);
		$fieldset_data['elements']['customer_contact'] = array(
			'title'  => _l( 'Contact' ),
			'fields' => array(
				array(
					'type'   => 'text',
					'name'   => 'contact_user_id',
					'value'  => $invoice['contact_user_id'],
					'lookup' => array(
						'key'         => 'user_id',
						'display_key' => 'name',
						'plugin'      => 'user',
						'lookup'      => 'contact_name',
						'return_link' => true,
					),
				)
			),
		);
	} else if ( $invoice['customer_id'] && module_customer::can_i( 'view', 'Customers' ) ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Customer',
			'fields' => array(
				module_customer::link_open( $invoice['customer_id'], true )
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
					'title'       => 'Notes',
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
				'title'  => 'Credit Note Emails',
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
				<input type="submit" name="butt_email" id="butt_email2" value="<?php echo _l( 'Email Credit Note' ); ?>"
				       class="submit_button btn"/>
				<?php _h( 'Click this button to email the invoice to the customer from the system' ); ?>
			</p>
			<p style="text-align: center;">

				<button type="button" name="buttmarksent" class="submit_button no_permissions btn"
				        onclick="$('#date_sent').val('<?php echo print_date( date( 'Y-m-d' ) ); ?>'); $('#invoice_form')[0].submit(); return false;"><?php _e( 'Mark as sent' ); ?></button>
				<?php _h( 'This invoice has not been sent yet. When this invoice has been sent to the customer please click this button or enter a "sent date" into the form above.' ); ?>
			</p>
		</div>

		<?php

		$fieldset_data = array(
			'heading'         => array(
				'title_final' => '<span class="error_text">' . _l( 'Send Credit Note' ) . '</span>',
				'type'        => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}


	if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {

		/*** INVOICE ADVANCED **/
		$fieldset_data = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Advanced',
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(
				array(
					'title'  => 'Customer Link',
					'hidden' => ! ( (int) $invoice_id > 0 ),
					'field'  => array(
						'type'  => 'html',
						'value' => '<a href="' . module_invoice::link_public( $invoice_id ) . '" target="_blank">' . _l( 'Click to view external link' ) . '</a>',
						'help'  => 'You can send this link to your customer and they can preview the invoice, pay for the invoice as well as optionally download the invoice as a PDF',
					),
				),
			),
		);

		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
			$fieldset_data['elements'][] = array(
				'title'  => module_config::c( 'project_name_single', 'Website' ),
				'fields' => array(
					function () use ( &$invoice ) {
						$website_ids = isset( $invoice['website_ids'] ) && is_array( $invoice['website_ids'] ) ? $invoice['website_ids'] : ( isset( $invoice['website_ids'] ) ? explode( ',', $invoice['website_ids'] ) : array() );
						if ( ! $invoice['website_id'] ) {
							$invoice['website_id'] = array_shift( $website_ids );
						}
						if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
							$c = array();
							// change between websites within this customer?
							// or websites all together?
							$res = module_website::get_websites( array( 'customer_id' => ( isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : ( $invoice['customer_id'] ? $invoice['customer_id'] : false ) ) ) );
							//$res = module_website::get_websites();
							while ( $row = array_shift( $res ) ) {
								$c[ $row['website_id'] ] = $row['name'];
							}
							echo print_select_box( $c, 'website_id', $invoice['website_id'] );
						} else {
							if ( $invoice['website_id'] ) {
								echo module_website::link_open( $invoice['website_id'], true );
							} else {
								_e( 'N/A' );
							}
						}
						foreach ( $website_ids as $website_id ) {
							if ( $website_id ) {
								echo ' ' . module_website::link_open( $website_id, true );
							}
						}
					}
				),
			);
		} else if ( ! class_exists( 'module_website', false ) && module_config::c( 'show_ucm_ads', 1 ) ) {

			$fieldset_data['elements'][] = array(
				'title'  => module_config::c( 'project_name_single', 'Website' ),
				'fields' => array(
					'(website option available in <a href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}
		$fieldset_data['elements'][] = array(
			'title' => 'Tax Type',
			'field' => array(
				'type'    => 'select',
				'blank'   => false,
				'options' => array( '0' => _l( 'Tax Added' ), 1 => _l( 'Tax Included' ) ),
				'name'    => 'tax_type',
				'value'   => $invoice['tax_type'],
			),
		);
		if ( $discounts_allowed ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Discount Amount',
				'fields' => array(
					function () use ( $invoice_locked, $invoice_id, &$invoice ) {
						echo ( $invoice_locked || ! module_security::is_page_editable() ) ?
							'<span class="currency">' . dollar( $invoice['discount_amount'], true, $invoice['currency_id'] ) . '</span>' :
							currency( '<input type="text" name="discount_amount" value="' . number_out( $invoice['discount_amount'] ) . '" class="currency">' );
						echo ' ';
					},
					array(
						'type'  => 'html',
						'value' => '',
						'help'  => 'Here you can apply a before tax discount to this invoice. You can name this anything, eg: DISCOUNT, CREDIT, REFUND, etc..',
					)
				),
			);
			$fieldset_data['elements'][] = array(
				'title'  => 'Discount Name',
				'fields' => array(
					function () use ( $invoice_id, &$invoice ) {
						echo ( ! module_security::is_page_editable() ) ?
							htmlspecialchars( _l( $invoice['discount_description'] ) ) :
							'<input type="text" name="discount_description" value="' . htmlspecialchars( _l( $invoice['discount_description'] ) ) . '" style="width:80px;">';
					}
				),
			);
			$fieldset_data['elements'][] = array(
				'title' => 'Discount Type',
				'field' => array(
					'type'    => 'select',
					'blank'   => false,
					'options' => array( '0' => _l( 'Before Tax' ), 1 => _l( 'After Tax' ) ),
					'name'    => 'discount_type',
					'value'   => $invoice['discount_type'],
				),
			);
		}

		$fieldset_data['elements'][] = array(
			'title' => 'Task Type',
			'field' => array(
				'type'    => 'select',
				'blank'   => false,
				'options' => module_job::get_task_types(),
				'name'    => 'default_task_type',
				'value'   => isset( $invoice['default_task_type'] ) ? $invoice['default_task_type'] : 0,
				'help'    => 'The default is hourly rate + amount. This will show the "Hours" column along with an "Amount" column. Inputing a number of hours will auto complete the price based on the job hourly rate. <br>Quantity and Amount will allow you to input a Quantity (eg: 2) and an Amount (eg: $100) and the final price will be $200 (Quantity x Amount). The last option "Amount Only" will just have the amount column for manual input of price. Change the advanced setting "default_task_type" between 0, 1 and 2 to change the default here.',
			),
		);


		$find_other_templates = 'invoice_print';
		$current_template     = isset( $invoice['invoice_template_print'] ) && strlen( $invoice['invoice_template_print'] ) ? $invoice['invoice_template_print'] : module_config::c( 'invoice_template_print_default', 'invoice_print' );
		if ( function_exists( 'convert_html2pdf' ) && isset( $find_other_templates ) && strlen( $find_other_templates ) && isset( $current_template ) && strlen( $current_template ) ) {
			$other_templates = array();
			foreach ( module_template::get_templates() as $possible_template ) {
				if ( strpos( $possible_template['template_key'], $find_other_templates ) !== false ) {
					// found another one!
					$other_templates[ $possible_template['template_key'] ] = $possible_template['template_key']; //$possible_template['description'];
				}
			}
			if ( count( $other_templates ) > 1 ) {
				$fieldset_data['elements'][] = array(
					'title' => 'PDF Template',
					'field' => array(
						'type'    => 'select',
						'options' => $other_templates,
						'name'    => 'invoice_template_print',
						'value'   => $current_template,
						'help'    => 'Choose the default template for PDF printing and PDF emailing. Name your custom templates invoice_print_SOMETHING for them to appear in this listing.',
					),
				);
			}
		}
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


	?>

	<script type="text/javascript">
      function setamount(a, invoice_item_id, rate) {
          console.log(a);
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
			'title' => _l( 'Credit Note Items' ),
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
		),
	);
	if ( (int) $invoice_id ) {
		$form_actions['elements'][] = array(
			'type'  => 'submit',
			'class' => 'submit_button',
			'name'  => 'butt_email',
			'value' => _l( 'Email Credit Note' ),
		);
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
