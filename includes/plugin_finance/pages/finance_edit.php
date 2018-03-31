<?php

$locked = false;

$linked_staff_members = $linked_finances = $linked_invoice_payments = array();
foreach ( module_user::get_staff_members() as $staff ) {
	$linked_staff_members[ $staff['user_id'] ] = $staff['name'];
}

$finance_id = (int) $_REQUEST['finance_id'];
$finance    = module_finance::get_finance( $finance_id );
if ( ! isset( $finance['finance_id'] ) || $finance['finance_id'] != $finance_id ) {
	$finance_id = 0;
}
if ( $finance_id <= 0 ) {

	if ( isset( $_REQUEST['from_invoice_id'] ) ) {
		$invoice_data          = module_invoice::get_invoice( (int) $_REQUEST['from_invoice_id'], false );
		$finance['invoice_id'] = $invoice_data['invoice_id'];
		if ( $invoice_data['customer_id'] ) {
			$finance['customer_id'] = $invoice_data['customer_id'];
		}
	}
	if ( isset( $_REQUEST['invoice_payment_id'] ) ) {
		$invoice_payment_data = module_invoice::get_invoice_payment( $_REQUEST['invoice_payment_id'] );
		if ( $invoice_payment_data ) {
			// we make sure this NEW invoice payment record hasn't already been recorded somewhere.
			$existing = module_finance::get_finances( array( 'invoice_payment_id' => $invoice_payment_data['invoice_payment_id'] ) );
			if ( count( $existing ) ) {
				foreach ( $existing as $e ) {
					if ( isset( $e['finance_id'] ) && (int) $e['finance_id'] > 0 ) {
						$link = module_finance::link_open( $e['finance_id'] );
						if ( $link ) {
							redirect_browser( $link );
						}
					}
				}
			}
		}
		$linked_invoice_payments[] = $invoice_payment_data;
		$invoice_data              = module_invoice::get_invoice( $invoice_payment_data['invoice_id'] );
		$finance['customer_id']    = $invoice_data['customer_id'];
		if ( $invoice_data['job_ids'] ) {
			foreach ( $invoice_data['job_ids'] as $job_id ) {
				$finance['job_id'] = $job_id;// meh! pick last one.
			}
		}
		$locked = true;
	}
} else {
	$linked_invoice_payments = $finance['linked_invoice_payments'];
	$linked_finances         = $finance['linked_finances'];
	$module->page_title      = $finance['name'];
}

// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( ( $finance_id > 0 && $finance['finance_id'] == $finance_id ) || ( isset( $_REQUEST['invoice_payment_id'] ) && isset( $invoice_payment_data ) && $invoice_payment_data ) ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Finance',
			'page_name' => 'Finance',
			'module'    => 'finance',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Finance',
			'page_name' => 'Finance',
			'module'    => 'finance',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'finance', $finance );
}
if ( isset( $finance['invoice_payment_id'] ) && (int) $finance['invoice_payment_id'] > 0 ) {
	//$locked = true;
}

$finance_recurring_id = isset( $_REQUEST['finance_recurring_id'] ) ? (int) $_REQUEST['finance_recurring_id'] : false;
if ( $finance_id > 0 && $finance && isset( $finance['finance_recurring_id'] ) && $finance['finance_recurring_id'] ) {
	$finance_recurring_id = $finance['finance_recurring_id'];
}
if ( $finance_recurring_id > 0 ) {
	$finance_recurring = module_finance::get_recurring( $finance_recurring_id );
}
if ( ! $finance_id && $finance_recurring_id > 0 ) {
	$finance = array_merge( $finance, $finance_recurring );
	//print_r($finance_recurring);
	$finance['transaction_date'] = $finance_recurring['next_due_date'];
	/*$finance['name'] = $finance_recurring['name'];
	$finance['amount'] = $finance_recurring['amount'];
	$finance['description'] = _l('Recurring expense');*/
}


if ( $finance_id > 0 && ( count( $linked_invoice_payments ) || count( $linked_finances ) ) ) {
	$locked = true;
	if ( count( $linked_finances ) ) {
		echo _l( 'Transaction locked. Please unlink it if you would like to make changes to price, type or date.' );
	}
}

?>

<script type="text/javascript">
	<?php if(! $locked){ ?>
  $(function () {
      if (typeof ucm.finance != 'undefined') {
				<?php if($finance['amount'] > 0 && $finance['taxable_amount'] == 0){ ?>
          ucm.finance.changing = 'subtotal';
				<?php } ?>
          ucm.finance.init();
      }
  });
	<?php } ?>
</script>
<form action="" method="post">

	<?php
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);

	?>


	<input type="hidden" name="_process" value="save_finance"/>
	<input type="hidden" name="finance_id" value="<?php echo $finance_id; ?>"/>
	<?php if ( isset( $_REQUEST['invoice_payment_id'] ) ) { ?>
		<input type="hidden" name="invoice_payment_id"
		       value="<?php echo isset( $_REQUEST['invoice_payment_id'] ) ? (int) $_REQUEST['invoice_payment_id'] : ''; ?>"/>
	<?php } ?>
	<input type="hidden" name="finance_recurring_id" value="<?php echo $finance_recurring_id; ?>"/>
	<input type="hidden" name="_redirect"
	       value="<?php echo $finance['invoice_id'] ? module_invoice::link_open( $finance['invoice_id'], false ) : ''; ?>"
	       id="form_redirect"/>

	<?php

	$fieldset_data = array(
		'heading'        => array(
			'title' => _l( 'Edit Transaction' ),
			'type'  => 'h2',
			'main'  => true,
		),
		'elements'       => array(),
		'extra_settings' => array(
			'owner_table' => 'finance',
			'owner_key'   => 'finance_id',
			'owner_id'    => isset( $finance['finance_id'] ) ? $finance['finance_id'] : false,
			'layout'      => 'table_row',
			'allow_new'   => module_finance::can_i( 'create', 'Finance' ),
			'allow_edit'  => module_finance::can_i( 'edit', 'Finance' ),
		),
	);
	if ( $finance_id > 0 ) {
		$fieldset_data['heading']['title'] = _l( 'Edit Transaction' );
	} else {
		if ( $finance_recurring_id ) {
			$fieldset_data['heading']['title'] = _l( 'Record Recurring Transaction' );
		} else {
			$fieldset_data['heading']['title'] = _l( 'Create Transaction' );
		}
	}

	$fieldset_data['elements'][] = array(
		'title'  => 'Date',
		'fields' => array(
			function () use ( $locked, &$finance, $finance_id ) {
				if ( $locked ) {
					echo print_date( $finance['transaction_date'] );
				} else { ?>
					<input type="text" name="transaction_date" id="transaction_date"
					       value="<?php echo print_date( $finance['transaction_date'] ); ?>" class="date_field"/>
				<?php } ?>
				<?php if ( ! (int) $finance_id && isset( $finance_recurring['next_due_date'] ) ) { ?>
					<?php _e( '(recurring on <a href="%s">%s</a>)', 'javascript:$(\'#transaction_date\').val(\'' . print_date( $finance_recurring['next_due_date'] ) . '\'); return false;', print_date( $finance_recurring['next_due_date'] ) ); ?>
				<?php }
			}
		),
	);
	if ( count( $linked_invoice_payments ) ) {
		$is_refunds                  = $refunded = array();
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Invoices',
			'fields' => array(
				function () use ( &$is_refunds, &$linked_invoice_payments, &$refunded ) {
					foreach ( $linked_invoice_payments as $linked_invoice_payment ) {
						if ( isset( $linked_invoice_payment['is_refund'] ) && $linked_invoice_payment['is_refund'] && isset( $linked_invoice_payment['refund_invoice_payments'] ) ) {
							$is_refunds = array_merge( $is_refunds, $linked_invoice_payment['refund_invoice_payments'] );
						} else if ( isset( $linked_invoice_payment['refunded'] ) && $linked_invoice_payment['refunded'] && isset( $linked_invoice_payment['refund_invoice_payments'] ) ) {
							$refunded = array_merge( $refunded, $linked_invoice_payment['refund_invoice_payments'] );
						}
						echo module_invoice::link_open( $linked_invoice_payment['invoice_id'], true );
						echo '<br>';
					}
				}
			),
		);
		if ( count( $is_refunds ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Refunds',
				'fields' => array(
					function () use ( &$is_refunds ) {
						_e( 'This is a refund for a past payment!' )
						?>
						<?php foreach ( $is_refunds as $r ) { ?>
							<a
								href="<?php echo module_finance::link_open( 'new', false ) . '&invoice_payment_id=' . $r['invoice_payment_id']; ?>"
								class="success_text"><?php _e( '%s on %s', '' . dollar( $r['amount'], true, $r['currency_id'] ), print_date( $r['date_paid'] ) ); ?></a>
						<?php }
					}
				),
			);
		}
		if ( count( $refunded ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Refunds',
				'fields' => array(
					function () use ( &$refunded ) {
						_e( 'This invoice payment was refunded!' )
						?>
						<?php foreach ( $refunded as $r ) { ?>
							<a
								href="<?php echo module_finance::link_open( 'new', false ) . '&invoice_payment_id=' . $r['invoice_payment_id']; ?>"
								class="error_text"><?php _e( '%s on %s', '-' . dollar( $r['amount'], true, $r['currency_id'] ), print_date( $r['date_paid'] ) ); ?></a>
						<?php }
					}
				),
			);
		}
	}
	if ( count( $linked_finances ) ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Transactions',
			'fields' => array(
				function () use ( &$linked_finances ) {
					foreach ( $linked_finances as $linked_finance ) {
						echo module_finance::link_open( $linked_finance['finance_id'], true );
						echo ' ';
						echo dollar( $linked_finance['amount'] );
						echo ' ';
						echo print_date( $linked_finance['transaction_date'] );
						echo '<br>';
					}
				}
			),
		);
	}
	if ( $finance_recurring_id > 0 ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Recurring',
			'fields' => array(
				function () use ( &$finance_recurring, $finance_recurring_id ) {
					?> <a
						href="<?php echo module_finance::link_open_recurring( $finance_recurring_id ); ?>"><?php if ( ! $finance_recurring['days'] && ! $finance_recurring['months'] && ! $finance_recurring['years'] ) {
							echo _l( 'Once off' );
						} else {
							echo _l( 'Every %s days, %s months and %s years between %s and %s', $finance_recurring['days'], $finance_recurring['months'], $finance_recurring['years'], ( $finance_recurring['start_date'] && $finance_recurring['start_date'] != '0000-00-00' ) ? print_date( $finance_recurring['start_date'] ) : 'now', ( $finance_recurring['end_date'] && $finance_recurring['end_date'] != '0000-00-00' ) ? print_date( $finance_recurring['end_date'] ) : 'forever' );
						} ?></a>
					<?php
					// see if we can find previous transactions from this recurring schedule.
					// copied from recurring_edit
					if ( isset( $finance_recurring['last_transaction_finance_id'] ) && $finance_recurring['last_transaction_finance_id'] ) {
						?> <a
							href="<?php echo module_finance::link_open( $finance_recurring['last_transaction_finance_id'] ); ?>"><?php
							echo _l( 'Last transaction: %s on %s', currency( $finance_recurring['last_amount'] ), print_date( $finance_recurring['last_transaction_date'] ) );
							?></a>
						(<a
							href="<?php echo module_finance::link_open( false ); ?>?search[finance_recurring_id]=<?php echo $finance_recurring_id; ?>"><?php _e( 'view all' ); ?></a>)
						<?php
					}
				}
			),
		);
	}
	$fieldset_data['elements'][] = array(
		'title' => 'Name',
		'field' => array(
			'type'  => 'text',
			'name'  => 'name',
			'value' => $finance['name'],
		),
	);
	$fieldset_data['elements'][] = array(
		'title' => 'Description',
		'field' => array(
			'type'  => 'textarea',
			'name'  => 'description',
			'value' => $finance['description'],
			//'style' => 'width:350px; height: 100px;', // todo: move this to stylesheet
		),
	);
	$fieldset_data['elements'][] = array(
		'title'  => 'Type',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				if ( $locked ) {
					echo $finance['type'] == 'i' ? _l( 'Income' ) : _l( 'Expense' );
				} else { ?>
					<input type="radio" name="type" id="income" value="i"<?php echo $finance['type'] == 'i' ? ' checked' : ''; ?>>
					<label for="income"><?php _e( 'Income/Credit' ); ?></label> <br/>
					<input type="radio" name="type" id="expense"
					       value="e"<?php echo $finance['type'] == 'e' ? ' checked' : ''; ?>> <label
						for="expense"><?php _e( 'Expense/Debit' ); ?></label> <br/>
				<?php }
			}
		),
	);
	$fieldset_data['elements'][] = array(
		'title'  => 'Sub Total',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				if ( $locked ) {
					echo dollar( $finance['sub_amount'], true, $finance['currency_id'] );
				} else { ?>
					<?php echo currency( '', true, $finance['currency_id'] ); ?>
					<input type="text" name="sub_amount" id="finance_sub_amount"
					       value="<?php echo htmlspecialchars( number_out( $finance['sub_amount'] ) ); ?>" class="currency"
					       autocomplete="off">
				<?php }
			}
		),
	);
	$fieldset_data['elements'][] = array(
		'title'  => 'Taxable Total',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				if ( $locked ) {
					echo dollar( $finance['taxable_amount'], true, $finance['currency_id'] );
				} else { ?>
					<?php echo currency( '' ); ?>
					<input type="text" name="taxable_amount" id="finance_taxable_amount"
					       value="<?php echo htmlspecialchars( number_out( $finance['taxable_amount'] ) ); ?>" class="currency"
					       autocomplete="off">
				<?php } ?>
				<?php
			},
			array(
				'type'  => 'html',
				'value' => '',
				'help'  => 'This can be different to the Sub Total above. eg: if part of this item included tax but part did not. Taxable total should always be less than or equal to the sub total.',
			)
		),
	);
	$fieldset_data['elements'][] = array(
		'title'  => 'Tax',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				if ( $locked ) {
				foreach ( $finance['taxes'] as $id => $tax ) { ?>
					<?php echo isset( $tax['name'] ) ? htmlspecialchars( $tax['name'] ) : ''; ?>
					@
					<?php echo isset( $tax['percent'] ) ? htmlspecialchars( number_out( $tax['percent'] ) ) : ''; ?>%
					(<?php echo dollar( $tax['amount'], true, $finance['currency_id'] ); ?>)
				<br/>
				<?php }
				}else{

				$incrementing = false;
				//echo print_select_box(module_finance::get_tax_modes(),'tax_mode',isset($finance['tax_mode'])?$finance['tax_mode']:0,'',false);
				if ( ! isset( $finance['taxes'] ) || ! count( $finance['taxes'] ) ) {
					$finance['taxes'][] = array(
						'name'   => module_config::c( 'tax_name', 'TAX' ),
						'amount' => 0,
					); // at least have 1?
				} else {
					// we turn on 'incrementing' if any of the taxes have this option enabled.
					foreach ( $finance['taxes'] as $tax ) {
						if ( isset( $tax['increment'] ) && $tax['increment'] ) {
							$incrementing = true;
						}
					}
				}
				?>
					<span class="finance_tax_increment">
                                <input type="checkbox" name="tax_increment_checkbox" id="tax_increment_checkbox"
                                       value="1" <?php echo $incrementing ? ' checked' : ''; ?>> <?php _e( 'incremental' ); ?>
                            </span>
					<div id="finance_tax_holder">
						<?php
						foreach ( $finance['taxes'] as $id => $tax ) { ?>
							<div class="dynamic_block">
								<input type="hidden" name="tax_ids[]" class="dynamic_clear"
								       value="<?php echo isset( $tax['finance_tax_id'] ) ? (int) $tax['finance_tax_id'] : 0; ?>">
								<input type="text" name="tax_names[]" class="dynamic_clear"
								       value="<?php echo isset( $tax['name'] ) ? htmlspecialchars( $tax['name'] ) : ''; ?>"
								       style="width:30px;" autocomplete="off">
								@
								<input type="text" name="tax_percents[]" class="dynamic_clear tax_percent"
								       value="<?php echo isset( $tax['percent'] ) ? htmlspecialchars( number_out( $tax['percent'] ) ) : ''; ?>"
								       style="width:35px;" autocomplete="off">%
								<input type="hidden" name="tax_amount[]" class="dynamic_clear tax_amount_input"
								       value="<?php echo isset( $tax['amount'] ) ? htmlspecialchars( number_out( $tax['amount'] ) ) : ''; ?>">
								(<?php echo currency( '<span class="tax_amount">' . dollar( $tax['amount'], false, $finance['currency_id'] ) . '</span>', true, $finance['currency_id'] ); ?>
								)
								<a href="#" class="add_addit" onclick="seladd(this); ucm.finance.update_finance_total(); return false;">+</a>
								<a href="#" class="remove_addit"
								   onclick="selrem(this); ucm.finance.update_finance_total(); return false;">-</a>
							</div>
						<?php } ?>
					</div>
					<script type="text/javascript">
              set_add_del('finance_tax_holder');
					</script>
				<?php }
			},
		),
	);
	if ( $locked ) {
		$element_fields = array(
			'type'   => 'html',
			'value'  => dollar( $finance['amount'], true, $finance['currency_id'] ),
			'ignore' => ! $locked,
		);
	} else {
		$element_fields = array(
			'type'        => 'currency',
			'currency_id' => $finance['currency_id'],
			'name'        => 'amount',
			'id'          => 'finance_total_amount',
			'value'       => number_out( $finance['amount'] ),
		);
	}
	$fieldset_data['elements'][] = array(
		'title' => 'Total Amount',
		'field' => $element_fields,
	);
	$fieldset_data['elements'][] = array(
		'title' => 'Currency',
		'field' => array(
			'type'             => 'select',
			'name'             => 'currency_id',
			'options'          => get_multiple( 'currency', '', 'currency_id' ),
			'options_array_id' => 'code',
			'value'            => $finance['currency_id'],
		),
	);
	$fieldset_data['elements'][] = array(
		'title' => 'Account',
		'field' => array(
			'type'             => 'select',
			'name'             => 'finance_account_id',
			'options'          => module_finance::get_accounts(),
			'options_array_id' => 'name',
			'allow_new'        => true,
			'value'            => isset( $finance['finance_account_id'] ) ? $finance['finance_account_id'] : '',
		),
	);
	$fieldset_data['elements'][] = array(
		'title'  => 'Categories',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				$categories = module_finance::get_categories();
				foreach ( $categories as $category ) { ?>
					<input type="checkbox" name="finance_category_id[]" value="<?php echo $category['finance_category_id']; ?>"
					       id="category_<?php echo $category['finance_category_id']; ?>" <?php echo isset( $finance['category_ids'][ $category['finance_category_id'] ] ) ? ' checked' : ''; ?>>
					<label
						for="category_<?php echo $category['finance_category_id']; ?>"><?php echo htmlspecialchars( $category['name'] ); ?></label>
					<br/>
				<?php }
				?>
				<input type="checkbox" name="finance_category_new_checked" value="new">
				<input type="text" name="finance_category_new" value="">
				<?php
			},
		),
	);
	if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
		$companys     = module_company::get_companys();
		$companys_rel = array();
		foreach ( $companys as $company ) {
			$companys_rel[ $company['company_id'] ] = $company['name'];
		}
		$fieldset_data['elements'][] = array(
			'title' => 'Company',
			'field' => array(
				'type'    => 'select',
				'name'    => 'company_id',
				'value'   => isset( $finance['company_id'] ) ? $finance['company_id'] : '',
				'options' => $companys_rel,
				'blank'   => _l( ' - Default - ' ),
				'help'    => 'Link this finance item with an individual company. It is better to select a Customer below and assign the Customer to a Company.',
			),
		);
	}
	if ( module_config::c( 'finance_link_to_jobs', 1 ) && module_job::can_i( 'view', 'Jobs' ) ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Customer',
			'fields' => array(
				function () use ( &$finance, $locked ) {
					echo print_select_box( module_customer::get_customers(), 'customer_id', $finance['customer_id'], '', _l( ' - None - ' ), 'customer_name' ); ?>
					<script type="text/javascript">
              $(function () {
                  $('#customer_id').change(function () {
                      // change our customer id.
                      var new_customer_id = $(this).val();
                      $.ajax({
                          type: 'POST',
                          url: '<?php echo module_job::link_open( false );?>',
                          data: {
                              '_process': 'ajax_job_list',
                              'customer_id': new_customer_id
                          },
                          dataType: 'json',
                          success: function (newOptions) {
                              $('#job_id').find('option:gt(0)').remove();
                              $.each(newOptions, function (value, key) {
                                  $('#job_id').append($("<option></option>")
                                      .attr("value", value).text(key));
                              });
                          },
                          fail: function () {
                              alert('Changing customer failed, please refresh and try again.');
                          }
                      });
                  });
              });
					</script>
					<?php
				},
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Job',
			'fields' => array(
				function () use ( &$finance, $locked ) {
					$d = array();
					if ( $finance['customer_id'] ) {
						$jobs = module_job::get_jobs( array( 'customer_id' => $finance['customer_id'] ) );
						foreach ( $jobs as $job ) {
							$d[ $job['job_id'] ] = $job['name'];
						}
					}

					echo print_select_box( $d, 'job_id', isset( $finance['job_id'] ) ? $finance['job_id'] : 0, '', _l( ' - None - ' ) );
					if ( isset( $finance['job_id'] ) && $finance['job_id'] ) {
						echo ' <a href="' . module_job::link_open( $finance['job_id'], false ) . '">' . _l( 'Open Job' ) . '</a>';
					}
				},
			),
		);
	}
	$fieldset_data['elements'][] = array(
		'title'  => 'Linked Invoice',
		'fields' => array(
			function () use ( &$finance, $locked ) {
				$d = array();
				if ( $finance['customer_id'] ) {
					$invoices = module_invoice::get_invoices( array( 'customer_id' => $finance['customer_id'] ) );
					foreach ( $invoices as $invoice ) {
						$d[ $invoice['invoice_id'] ] = $invoice['name'];
					}
				}
				echo print_select_box( $d, 'invoice_id', $finance['invoice_id'], '', _l( ' - None - ' ) );
				if ( $finance['invoice_id'] ) {
					echo ' <a href="' . module_invoice::link_open( $finance['invoice_id'], false ) . '">' . _l( 'Open Invoice' ) . '</a>';
				}
			},
		),
	);
	if ( count( $linked_staff_members ) ) {
		$fieldset_data['elements'][] = array(
			'title'  => 'Linked Staff',
			'fields' => array(
				function () use ( &$finance, $locked, $linked_staff_members ) {
					?> <input type="hidden" name="job_staff_expense"
					          value="<?php echo isset( $finance['job_staff_expense'] ) ? (int) $finance['job_staff_expense'] : ''; ?>">
					<?php
					echo print_select_box( $linked_staff_members, 'user_id', isset( $finance['user_id'] ) ? $finance['user_id'] : 0, '', _l( ' - None - ' ) );
					if ( isset( $finance['user_id'] ) && $finance['user_id'] ) {
						echo ' <a href="' . module_user::link_open( $finance['user_id'], false ) . '">' . _l( 'Open User' ) . '</a>';
					}
				},
			),
		);
	}
	$fieldset_data['elements'][] = array(
		'title'  => 'Attachment',
		'fields' => array(
			function () use ( &$finance, $locked, $finance_id ) {
				if ( (int) $finance_id > 0 ) {
					module_file::display_files( array(
							'owner_table' => 'finance',
							'owner_id'    => $finance_id,
							//'layout' => 'list',
							'layout'      => 'gallery',
							'editable'    => module_security::is_page_editable() && module_finance::can_i( 'edit', 'Finance' ),
						)
					);
				} else {
					_e( 'Please press save first' );
				}
			},
		),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_left',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save_return',
				'value' => _l( 'Save and Return' ),
			),
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
		),
	);
	if ( (int) $finance_recurring_id > 0 && isset( $_SESSION['_finance_recurring_ids'] ) ) {
		// find if there is a next recurring id
		$next = 0;
		foreach ( $_SESSION['_finance_recurring_ids'] as $next_data ) {
			if ( $next == - 1 ) {
				$next                       = 1; // done.
				$form_actions['elements'][] = array(
					'type'  => 'hidden',
					'name'  => 'recurring_next',
					'id'    => 'recurring_next',
					'value' => '',
				);
				$form_actions['elements'][] = array(
					'type'    => 'save_button',
					'name'    => 'butt_save',
					'value'   => _l( 'Save & Next Transaction' ),
					'onclick' => "$('#recurring_next').val('" . htmlspecialchars( $next_data[1] ) . "');",
				);
				break;
			} else if ( $next == 0 && $next_data[0] == $finance_recurring_id ) {
				$next = - 1;
			}
		}
	}
	if ( (int) $finance_id > 0 ) {
		$form_actions['elements'][] = array(
			'type'    => 'delete_button',
			'name'    => 'butt_del',
			'value'   => _l( 'Delete' ),
			'onclick' => "return confirm('" . _l( 'Really delete this record?' ) . "');",
		);
	}
	if ( count( $linked_finances ) ) { // || count($linked_invoice_payments))
		$form_actions['elements'][] = array(
			'type'  => 'submit',
			'name'  => 'butt_unlink',
			'value' => _l( 'Unlink' ),
		);
	}
	$form_actions['elements'][] = array(
		'type'    => 'button',
		'name'    => 'cancel',
		'value'   => _l( 'Cancel' ),
		'class'   => 'submit_button',
		'onclick' => "window.location.href='" . ( $finance['invoice_id'] ? module_invoice::link_open( $finance['invoice_id'], false ) : $module->link( 'finance', array( 'finance_id' => false ) ) ) . "';",
	);
	echo module_form::generate_form_actions( $form_actions );

	?>


</form>
