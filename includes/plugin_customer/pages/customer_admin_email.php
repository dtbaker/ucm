<?php


$page_type        = 'Customers';
$page_type_single = 'Customer';

$current_customer_type_id = module_customer::get_current_customer_type_id();
if ( $current_customer_type_id > 0 ) {
	$customer_type = module_customer::get_customer_type( $current_customer_type_id );
	if ( $customer_type && ! empty( $customer_type['type_name'] ) ) {
		$page_type        = $customer_type['type_name_plural'];
		$page_type_single = $customer_type['type_name'];
	}
}
if ( ! module_customer::can_i( 'view', $page_type ) ) {
	redirect_browser( _BASE_HREF );
}


$customer_id = (int) $_REQUEST['customer_id'];
$customer    = array();

$customer = module_customer::get_customer( $customer_id );

if ( $customer_id > 0 && $customer['customer_id'] == $customer_id ) {
	$module->page_title = _l( $page_type_single . ': %s', $customer['customer_name'] );
} else {
	$module->page_title = _l( $page_type_single . ': %s', _l( 'New' ) );
}
// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $customer_id > 0 && $customer['customer_id'] == $customer_id ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Customer',
			'page_name' => $page_type,
			'module'    => 'customer',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Customer',
			'page_name' => $page_type,
			'module'    => 'customer',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'customer', $customer );
}


if ( isset( $_REQUEST['preview_email'] ) ) {

	$template_name = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'customer_statement_email';
	$template      = module_template::get_template_by_key( $template_name );

	$to        = module_user::get_contacts( array( 'customer_id' => $customer['customer_id'] ) );
	$to_select = false;
	if ( $customer['primary_user_id'] ) {
		$primary = module_user::get_user( $customer['primary_user_id'] );
		if ( $primary ) {
			$to_select = $primary['email'];
		}
	}

	$template->assign_values( module_customer::get_replace_fields( $customer_id ) );

	$email_details = '';


	if ( isset( $_REQUEST['email'] ) && is_array( $_REQUEST['email'] ) ) {

		if ( isset( $_REQUEST['email']['customer_details'] ) ) {
			$fieldset_data               = array(
				'heading'  => array(
					'type'  => 'h3',
					'title' => _l( 'Customer Details' ),
				),
				'elements' => array(),
			);
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Name' ),
				'fields' => array( $customer['customer_name'] ),
			);
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Address' ),
				'fields' => array( implode( ' ', $customer['customer_address'] ) ),
			);
			if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				$owner_table    = 'customer';
				$owner_id       = $customer_id;
				$allow_new      = false;
				$default_fields = module_extra::get_defaults( $owner_table );
				$extra_items    = module_extra::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
				$extra_items    = module_extra::sort_extras( $extra_items, $default_fields, $allow_new );

				foreach ( $extra_items as $extra_item ) {

					if ( ! empty( $extra_item ) ) {
						$fieldset_data['elements'][] = array(
							'title'  => $extra_item['extra_key'],
							'fields' => array( strpos( $extra_item['extra'], 'encrypt:' ) !== false ? _l( '(encrypted)' ) : forum_text( $extra_item['extra'] ) ),
						);
					}
				}

			}
			$email_details .= module_form::generate_fieldset( $fieldset_data );
		}
		if ( isset( $_REQUEST['email']['customer_contacts'] ) ) {
			$search = array( 'customer_id' => $customer_id );
			$users  = module_user::get_contacts( $search, true, false );

			$fieldset_data    = array(
				'heading'  => array(
					'type'  => 'h3',
					'title' => _l( 'Contact Details' ),
				),
				'elements' => array(),
			);
			$table_manager    = module_theme::new_table_manager();
			$columns          = array();
			$columns['name']  = array(
				'title'      => 'Name',
				'callback'   => function ( $user ) {
					echo htmlspecialchars( $user['name'] . ' ' . $user['last_name'] );
					if ( $user['is_primary'] == $user['user_id'] ) {
						echo ' *';
					}
				},
				'cell_class' => 'row_action',
			);
			$columns['phone'] = array(
				'title'    => 'Phone Number',
				'callback' => function ( $user ) {
					module_user::print_contact_summary( $user['user_id'], 'html', array( 'phone|mobile' ) );
				}
			);
			$columns['email'] = array(
				'title'    => 'Email Address',
				'callback' => function ( $user ) {
					module_user::print_contact_summary( $user['user_id'], 'html', array( 'email' ) );
				}
			);
			$table_manager->set_columns( $columns );
			$table_manager->row_callback = function ( $row_data ) {
				// load the full vendor data before displaying each row so we have access to more details
				return $row_data; //module_user::get_user($row_data['user_id']);
			};
			$table_manager->set_rows( $users );
			if ( class_exists( 'module_extra', false ) ) {
				$table_manager->display_extra( 'user', function ( $user ) {
					module_extra::print_table_data( 'user', $user['user_id'] );
				} );
			}
			ob_start();
			$table_manager->pagination = false;
			$table_manager->print_table();

			$fieldset_data['elements_before'] = ob_get_clean();
			$email_details                    .= module_form::generate_fieldset( $fieldset_data );
		}
		if ( ! function_exists( 'customer_admin_email_generate_invoice_list' ) ) {
			function customer_admin_email_generate_invoice_list( $invoices, $customer_id ) {
				ob_start();
				$colspan           = 9;
				$colspan2          = 0;
				$invoice_total     = array();
				$invoice_total_due = array();
				foreach ( $invoices as $invoice ) {
					if ( ! isset( $invoice_total[ $invoice['currency_id'] ] ) ) {
						$invoice_total[ $invoice['currency_id'] ] = 0;
					}
					if ( $invoice['c_total_amount'] == 0 ) {
						$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					}
					$invoice_total[ $invoice['currency_id'] ] += $invoice['c_total_amount'];
					if ( ! isset( $invoice_total_due[ $invoice['currency_id'] ] ) ) {
						$invoice_total_due[ $invoice['currency_id'] ] = 0;
					}
					$invoice_total_due[ $invoice['currency_id'] ] += $invoice['c_total_amount_due'];
				}
				$table_manager                  = module_theme::new_table_manager();
				$columns                        = array();
				$columns['invoice_number']      = array(
					'title'      => 'Invoice Number',
					'callback'   => function ( $invoice ) {
						//echo module_invoice::link_open($invoice['invoice_id'],true,$invoice);
						echo '<a href="' . module_invoice::link_public( $invoice['invoice_id'] ) . '">' . htmlspecialchars( $invoice['name'] ) . '</a>';
					},
					'cell_class' => 'row_action',
				);
				$columns['invoice_status']      = array(
					'title'    => 'Status',
					'callback' => function ( $invoice ) {
						echo htmlspecialchars( $invoice['status'] );
					},
				);
				$columns['invoice_create_date'] = array(
					'title'    => 'Create Date',
					'callback' => function ( $invoice ) {
						if ( ( ! $invoice['date_create'] || $invoice['date_create'] == '0000-00-00' ) ) {
							//echo print_date($invoice['date_created']);
						} else {
							echo print_date( $invoice['date_create'] );
						}
					},
				);
				$columns['invoice_due_date']    = array(
					'title'    => 'Due Date',
					'callback' => function ( $invoice ) {
						if ( ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < time() ) {
							echo '<span class="error_text">';
							echo print_date( $invoice['date_due'] );
							echo '</span>';
						} else {
							echo print_date( $invoice['date_due'] );
						}
					},
				);
				$columns['invoice_sent_date']   = array(
					'title'    => 'Sent Date',
					'callback' => function ( $invoice ) {
						if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) { ?>
							<?php echo print_date( $invoice['date_sent'] ); ?>
						<?php } else { ?>
							<span class="error_text"><?php _e( 'Not sent' ); ?></span>
						<?php }
					},
				);
				$columns['invoice_paid_date']   = array(
					'title'    => 'Paid Date',
					'callback' => function ( $invoice ) {
						if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) { ?>
							<?php echo print_date( $invoice['date_paid'] ); ?>
						<?php } else if ( ( $invoice['date_cancel'] && $invoice['date_cancel'] != '0000-00-00' ) ) { ?>
							<span class="error_text"><?php _e( 'Cancelled' ); ?></span>
						<?php } else if ( $invoice['overdue'] ) { ?>
							<span class="error_text"
							      style="font-weight: bold; text-decoration: underline;"><?php _e( 'Overdue' ); ?></span>
						<?php } else { ?>
							<span class="error_text"><?php _e( 'Not paid' ); ?></span>
						<?php }
					},
				);
				if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() && module_website::can_i( 'view', module_config::c( 'project_name_plural', 'Websites' ) ) ) {
					$colspan ++;
					$columns['invoice_website'] = array(
						'title'    => module_config::c( 'project_name_single', 'Website' ),
						'callback' => function ( $invoice ) {
							if ( isset( $invoice['website_ids'] ) ) {
								foreach ( $invoice['website_ids'] as $website_id ) {
									if ( (int) $website_id > 0 ) {
										echo module_website::link_open( $website_id, true );
										echo '<br/>';
									}
								}
							}
						},
					);
				}
				$columns['invoice_job'] = array(
					'title'    => 'Job',
					'callback' => function ( $invoice ) {
						foreach ( $invoice['job_ids'] as $job_id ) {
							if ( (int) $job_id > 0 ) {
								//echo module_job::link_open($job_id,true);
								$job_data = module_job::get_job( $job_id );
								echo '<a href="' . module_job::link_public( $job_id ) . '">' . htmlspecialchars( $job_data['name'] ) . '</a>';
								if ( $job_data['date_start'] && $job_data['date_start'] != '0000-00-00' && $job_data['date_renew'] && $job_data['date_renew'] != '0000-00-00' ) {
									_e( ' (%s to %s)', print_date( $job_data['date_start'] ), print_date( strtotime( "-1 day", strtotime( $job_data['date_renew'] ) ) ) );
								}
								echo "<br/>\n";
							}
						}
						hook_handle_callback( 'invoice_admin_list_job', $invoice['invoice_id'] );
					},
				);
				if ( ! isset( $_REQUEST['customer_id'] ) && module_customer::can_i( 'view', 'Customers' ) ) {
					$colspan ++;
					$columns['invoice_customer'] = array(
						'title'    => 'Customer',
						'callback' => function ( $invoice ) {
							echo module_customer::link_open( $invoice['customer_id'], true );
						},
					);
				}
				$columns['c_invoice_total']     = array(
					'title'    => 'Invoice Total',
					'callback' => function ( $invoice ) {
						echo dollar( $invoice['total_amount'], true, $invoice['currency_id'] );
					},
				);
				$columns['c_invoice_total_due'] = array(
					'title'    => 'Amount Due',
					'callback' => function ( $invoice ) {
						echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
						<?php if ( $invoice['total_amount_credit'] > 0 ) { ?>
							<span
								class="success_text"><?php echo _l( 'Credit: %s', dollar( $invoice['total_amount_credit'], true, $invoice['currency_id'] ) ); ?></span>
							<?php
						}
					},
				);
				if ( class_exists( 'module_extra', false ) ) {
					ob_start();
					$colspan2 += module_extra::print_table_header( 'invoice' ); // used in the footer calc.
					ob_end_clean();
					$table_manager->display_extra( 'invoice', function ( $invoice ) {
						module_extra::print_table_data( 'invoice', $invoice['invoice_id'] );
					} );
				}
				$table_manager->set_columns( $columns );
				$table_manager->row_callback = function ( $row_data ) {
					// load the full vendor data before displaying each row so we have access to more details
					if ( isset( $row_data['invoice_id'] ) && (int) $row_data['invoice_id'] > 0 ) {
						return module_invoice::get_invoice( $row_data['invoice_id'] );
					}

					return array();
				};
				$table_manager->set_rows( $invoices );
				if ( module_config::c( 'invoice_list_show_totals', 1 ) ) {
					$footer_rows = array();
					foreach ( $invoice_total + $invoice_total_due as $currency_id => $foo ) {
						$currency      = get_single( 'currency', 'currency_id', $currency_id );
						$footer_rows[] = array(
							'invoice_number'      => array(
								'data'         => '<strong>' . _l( '%s Totals:', $currency && isset( $currency['code'] ) ? $currency['code'] : '' ) . '</strong>',
								'cell_colspan' => $colspan - 2,
								'cell_class'   => 'text-right',
							),
							'c_invoice_total'     => array(
								'data' => '<strong>' . dollar( isset( $invoice_total[ $currency_id ] ) ? $invoice_total[ $currency_id ] : 0, true, $currency_id ) . '</strong>',
							),
							'c_invoice_total_due' => array(
								'data' => '<strong>' . dollar( isset( $invoice_total_due[ $currency_id ] ) ? $invoice_total_due[ $currency_id ] : 0, true, $currency_id ) . '</strong>',
							),
							'row_bulk_action'     => array(
								'data'         => ' ',
								'cell_colspan' => $colspan2
							),
						);
					}
					$table_manager->set_footer_rows( $footer_rows );
				}

				$table_manager->pagination = false;
				$table_manager->print_table();

				return ob_get_clean();
			}
		}
		if ( isset( $_REQUEST['email']['invoice_create'] ) ) {
			// find all invoices created between these dates...
			$date_from = input_date( $_REQUEST['email']['invoice_create_date_from'] );
			$date_to   = input_date( $_REQUEST['email']['invoice_create_date_to'] );
			$invoices  = module_invoice::get_invoices( array(
				'customer_id' => $customer['customer_id'],
				'date_from'   => print_date( $date_from ),
				'date_to'     => print_date( $date_to ),
			) );
			// remove cancelled invoices
			foreach ( $invoices as $invoice_id => $invoice ) {
				if ( $invoice['date_cancel'] != '0000-00-00' ) {
					unset( $invoices[ $invoice_id ] );
				}
			}
			if ( count( $invoices ) ) {
				$fieldset_data                    = array(
					'heading' => array(
						'type'  => 'h3',
						'title' => _l( 'Invoices Created Between %s and %s', print_date( $date_from ), print_date( $date_to ) ),
					),
				);
				$fieldset_data['elements_before'] = customer_admin_email_generate_invoice_list( $invoices, $customer_id );
				$email_details                    .= module_form::generate_fieldset( $fieldset_data );
			}
		}
		if ( isset( $_REQUEST['email']['invoice_paid'] ) ) {
			// find all invoices paid between these dates...
			$date_from = input_date( $_REQUEST['email']['invoice_paid_date_from'] );
			$date_to   = input_date( $_REQUEST['email']['invoice_paid_date_to'] );
			$invoices  = module_invoice::get_invoices( array(
				'customer_id'    => $customer['customer_id'],
				'date_paid_from' => print_date( $date_from ),
				'date_paid_to'   => print_date( $date_to ),
			) );
			// remove cancelled invoices
			foreach ( $invoices as $invoice_id => $invoice ) {
				if ( $invoice['date_cancel'] != '0000-00-00' ) {
					unset( $invoices[ $invoice_id ] );
				}
			}
			if ( count( $invoices ) ) {
				$fieldset_data                    = array(
					'heading' => array(
						'type'  => 'h3',
						'title' => _l( 'Invoices Paid Between %s and %s', print_date( $date_from ), print_date( $date_to ) ),
					),
				);
				$fieldset_data['elements_before'] = customer_admin_email_generate_invoice_list( $invoices, $customer_id );
				$email_details                    .= module_form::generate_fieldset( $fieldset_data );
			}
		}
		if ( isset( $_REQUEST['email']['invoice_unpaid'] ) ) {
			// find all unpaid invoices
			$date_from = input_date( $_REQUEST['email']['invoice_paid_date_from'] );
			$date_to   = input_date( $_REQUEST['email']['invoice_paid_date_to'] );
			$invoices  = module_invoice::get_invoices( array(
				'customer_id' => $customer['customer_id'],
				'date_paid'   => '0000-00-00',
			) );
			// remove cancelled invoices
			foreach ( $invoices as $invoice_id => $invoice ) {
				if ( $invoice['date_cancel'] != '0000-00-00' ) {
					unset( $invoices[ $invoice_id ] );
				}
			}
			if ( count( $invoices ) ) {
				$fieldset_data                    = array(
					'heading' => array(
						'type'  => 'h3',
						'title' => _l( 'Unpaid Invoices' ),
					),
				);
				$fieldset_data['elements_before'] = customer_admin_email_generate_invoice_list( $invoices, $customer_id );
				$email_details                    .= module_form::generate_fieldset( $fieldset_data );
			}
		}
	}

	$template->assign_values( array(
		'email_details' => $email_details,
	) );

	module_email::print_compose(
		array(
			'title'                => _l( 'Email Customer: %s', $customer['customer_name'] ),
			'find_other_templates' => 'customer_statement_email', // find others based on this name, eg: job_email*
			'current_template'     => $template_name,
			'customer_id'          => $customer['customer_id'],
			'debug_message'        => 'Sending customer statement email',
			'to'                   => $to,
			'to_select'            => $to_select,
			'bcc'                  => module_config::c( 'admin_email_address', '' ),
			'content'              => $template->render( 'html' ),
			'subject'              => $template->replace_description(),
			'success_url'          => module_customer::link_open( $customer['customer_id'] ),
			//'success_callback'=>'module_job::email_sent('.$job_id.',"'.$template_name.'");',
			'cancel_url'           => module_customer::link_open( $customer['customer_id'] ),
		)
	);


} else {

	?>
	<form action="" method="post" id="customer_form">
		<input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>"/>
		<input type="hidden" name="email" value="1"/>
		<input type="hidden" name="preview_email" value="1"/>

		<?php
		module_form::prevent_exit( array(
				'valid_exits' => array(
					// selectors for the valid ways to exit this form.
					'.submit_button',
				)
			)
		);

		module_form::print_form_auth();

		$elements      = array();
		$elements []   = array(
			'title'  => _l( 'General' ),
			'fields' => array(
				array(
					'type'    => 'check',
					'name'    => 'email[customer_details]',
					'value'   => '1',
					'label'   => _l( 'Customer Information' ),
					'checked' => 1,
				),
				'<br/>',
				array(
					'type'    => 'check',
					'name'    => 'email[customer_contacts]',
					'value'   => '1',
					'label'   => _l( 'Customer Contacts' ),
					'checked' => 1,
				),
				'<br/>',
				array(
					'type'    => 'check',
					'name'    => 'email[customer_notes]',
					'value'   => '1',
					'label'   => _l( 'Customer Notes' ) . ' (coming soon)',
					'checked' => 0,
				),
				'<br/>',
				array(
					'type'    => 'check',
					'name'    => 'email[customer_subscriptions]',
					'value'   => '1',
					'label'   => _l( 'Customer Subscriptions' ) . ' (coming soon)',
					'checked' => 0,
				),
			),
		);
		$elements []   = array(
			'title'  => _l( 'Quotes' ),
			'fields' => array(
				_l( 'Coming Soon' ),
			),
		);
		$elements []   = array(
			'title'  => _l( 'Jobs' ),
			'fields' => array(
				_l( 'Coming Soon' ),
			),
		);
		$elements []   = array(
			'title'  => _l( 'Invoices' ),
			'fields' => array(
				array(
					'type'    => 'check',
					'name'    => 'email[invoice_create]',
					'value'   => '1',
					'label'   => _l( 'Invoice Created' ),
					'checked' => false,
				),
				_l( 'From:' ) . ' ',
				array(
					'type'  => 'date',
					'name'  => 'email[invoice_create_date_from]',
					'value' => '',
				),
				' ' . _l( 'To:' ) . ' ',
				array(
					'type'  => 'date',
					'name'  => 'email[invoice_create_date_to]',
					'value' => '',
				),
				'<br/>',
				array(
					'type'    => 'check',
					'name'    => 'email[invoice_paid]',
					'value'   => '1',
					'label'   => _l( 'Invoice Paid' ),
					'checked' => false,
				),
				_l( 'From:' ) . ' ',
				array(
					'type'  => 'date',
					'name'  => 'email[invoice_paid_date_from]',
					'value' => '',
				),
				' ' . _l( 'To:' ) . ' ',
				array(
					'type'  => 'date',
					'name'  => 'email[invoice_paid_date_to]',
					'value' => '',
				),
				'<br/>',
				array(
					'type'    => 'check',
					'name'    => 'email[invoice_unpaid]',
					'value'   => '1',
					'label'   => _l( 'All Unpaid Invoices' ),
					'checked' => false,
				),
				'<br/>',
			),
		);
		$elements []   = array(
			'title'  => _l( 'Websites' ),
			'fields' => array(
				_l( 'Coming Soon' ),
			),
		);
		$elements []   = array(
			'title'  => _l( 'Tickets' ),
			'fields' => array(
				_l( 'Coming Soon' ),
			),
		);
		$elements []   = array(
			'title'  => _l( 'Files' ),
			'fields' => array(
				_l( 'Coming Soon' ),
			),
		);
		$fieldset_data = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Choose what to include in email',
				'help'  => 'Send an email containing a summary of all the information in the system regarding this Customer'
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => $elements
		);
		echo module_form::generate_fieldset( $fieldset_data );


		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_email',
					'value' => _l( 'Preview Email' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $module->link_open( $customer_id ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>


	</form>


<?php }