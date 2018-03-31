<?php


$website_id = (int) $_REQUEST['website_id'];
$website    = module_website::get_website( $website_id );


if ( $website_id > 0 && $website['website_id'] == $website_id ) {
	$module->page_title = module_config::c( 'project_name_single', 'Website' ) . ': ' . $website['name'];
	if ( function_exists( 'hook_handle_callback' ) ) {
		hook_handle_callback( 'timer_display', 'website', $website_id );
	}
} else {
	$module->page_title = module_config::c( 'project_name_single', 'Website' ) . ': ' . _l( 'New' );
}

if ( $website_id > 0 && $website ) {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'module'  => $module->module_name,
			'feature' => 'edit',
		) );
	}
} else {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'module'  => $module->module_name,
			'feature' => 'create',
		) );
	}
	module_security::sanatise_data( 'website', $website );
}


?>


<form action="" method="post">
	<input type="hidden" name="_process" value="save_website"/>
	<input type="hidden" name="website_id" value="<?php echo $website_id; ?>"/>


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
				'.form_save',
			)
		)
	);


	hook_handle_callback( 'layout_column_half', 1, '35' );

	$fieldset_data = array(
		'heading'        => array(
			'type'  => 'h3',
			'title' => _l( module_config::c( 'project_name_single', 'Website' ) . ' Details' ),
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(
			'name' => array(
				'title' => _l( 'Name' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $website['name'],
				),
			),
		),
		'extra_settings' => array(
			'owner_table' => 'website',
			'owner_key'   => 'website_id',
			'owner_id'    => $website['website_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_extra::can_i( 'create', 'Websites' ),
			'allow_edit'  => module_extra::can_i( 'edit', 'Websites' ),
		)
	,
	);
	if ( module_config::c( 'project_display_url', 1 ) ) {
		if ( ! isset( $website['url'] ) || ! strlen( $website['url'] ) ) {
			$website['url'] = module_config::c( 'project_default_url', 'http://' );
		}
		$fieldset_data['elements']['url'] = array(
			'title'  => _l( 'URL' ),
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'url',
					'value' => $website['url'],
					'style' => 'width:200px;',
				),
				$website['url'] ? ' <a href="' . module_website::urlify( $website['url'] ) . '" target="_blank">' . _l( 'open &raquo;' ) . '</a>' : '',
			)
		);
	}
	$fieldset_data['elements']['Status'] = array(
		'title'  => _l( 'Status' ),
		'fields' => array(
			array(
				'type'      => 'select',
				'name'      => 'status',
				'value'     => $website['status'],
				'options'   => module_website::get_statuses(),
				'allow_new' => true,
			),
		)
	);

	echo module_form::generate_fieldset( $fieldset_data );

	/*** ADVANCED ****/
	if ( module_website::can_i( 'edit', 'Websites' ) ) {
		$c   = array();
		$res = module_customer::get_customers();
		foreach ( $res as $row ) {
			$c[ $row['customer_id'] ] = $row['customer_name'];
		}
		$fieldset_data = array(
			'heading'  => array(
				'type'  => 'h3',
				'title' => _l( 'Advanced' ),
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array()
		);
		if ( count( $res ) <= 1 && $website['customer_id'] && isset( $c[ $website['customer_id'] ] ) ) {
			$fieldset_data['elements']['change'] = array(
				'title'  => _l( 'Change Customer' ),
				'fields' => array(
					htmlspecialchars( $c[ $website['customer_id'] ] ),
					array(
						'type'  => 'hidden',
						'name'  => 'customer_id',
						'value' => $website['customer_id'],
					)
				)
			);
		} else {
			$fieldset_data['elements']['change'] = array(
				'title'  => _l( 'Change Customer' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'customer_id',
						'options' => $c,
						'value'   => $website['customer_id'],
						'help'    => 'Changing a customer will also change all the current linked jobs and invoices across to this new customer.',
					)
				)
			);
		}

		echo module_form::generate_fieldset( $fieldset_data );
	}


	if ( (int) $website_id > 0 ) {
		if ( class_exists( 'module_group', false ) ) {
			module_group::display_groups( array(
				'title'       => module_config::c( 'project_name_single', 'Website' ) . ' Groups',
				'owner_table' => 'website',
				'owner_id'    => $website_id,
				'view_link'   => module_website::link_open( $website_id ),

			) );
		}

		// and a hook for our new change request plugin
		hook_handle_callback( 'website_sidebar', $website_id );

	}

	hook_handle_callback( 'layout_column_half', 2, 65 );


	if ( (int) $website_id > 0 ) {

		if ( class_exists( 'module_note', false ) ) {
			$note_summary_owners = array();
			// generate a list of all possible notes we can display for this website.
			// display all the notes which are owned by all the sites we have access to
			$note_summary_owners['job']     = array();
			$note_summary_owners['invoice'] = array();
			if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
				foreach ( module_job::get_jobs( array( 'website_id' => $website_id ) ) as $val ) {
					$note_summary_owners['job'][] = $val['job_id'];
					foreach ( module_invoice::get_invoices( array( 'job_id' => $val['job_id'] ) ) as $val2 ) {
						$note_summary_owners['invoice'][ $val2['invoice_id'] ] = $val2['invoice_id'];
					}
				}
			}
			// now find any subscription invoices that are linked to this website.
			if ( class_exists( 'module_subscription', false ) ) {
				$members_subscriptions = module_subscription::get_subscriptions_by( 'website', $website_id );
				foreach ( $members_subscriptions as $subscription_id => $subscription_info ) {
					$history = module_subscription::get_subscription_history( $subscription_id, 'website', $website_id );
					foreach ( $history as $h ) {
						if ( is_array( $h ) && isset( $h['invoice_id'] ) && $h['invoice_id'] ) {
							$note_summary_owners['invoice'][ $h['invoice_id'] ] = $h['invoice_id'];
						}
					}
				}
			}
			module_note::display_notes( array(
					'title'           => module_config::c( 'project_name_single', 'Website' ) . ' Notes',
					'owner_table'     => 'website',
					'owner_id'        => $website_id,
					'view_link'       => module_website::link_open( $website_id ),
					'display_summary' => true,
					'summary_owners'  => $note_summary_owners
				)
			);
		}

		if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() && module_quote::can_i( 'view', 'Quotes' ) ) {
			// show the jobs linked to this website.
			$quotes = module_quote::get_quotes( array( 'website_id' => $website_id ) );
			if ( count( $quotes ) || module_quote::can_i( 'create', 'Quotes' ) ) {

				$h = array(
					'type'  => 'h3',
					'title' => module_config::c( 'project_name_single', 'Website' ) . ' Quotes',
				);

				if ( module_quote::can_i( 'create', 'Quotes' ) ) {
					$h['button'] = array(
						'title' => 'New Quote',
						'url'   => module_quote::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => $website_id,
							)
						) ),
					);
				}

				$fieldset_data = array(
					'heading' => $h,
				);

				if ( count( $quotes ) ) {
					$c = 0;
					ob_start();
					?>
					<div class="content_box_wheader">
						<table border="0" cellspacing="0" cellpadding="2"
						       class="tableclass tableclass_rows tableclass_full">
							<thead>
							<tr>
								<th>
									<?php _e( 'Quote Title' ); ?>
								</th>
								<th>
									<?php _e( 'Create Date' ); ?>
								</th>
								<th>
									<?php _e( 'Approved Date' ); ?>
								</th>
								<th>
									<?php _e( 'Approved By' ); ?>
								</th>
								<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
									<th>
										<?php _e( 'Amount' ); ?>
									</th>
								<?php } ?>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $quotes as $quote ) {
								$quote = module_quote::get_quote( $quote['quote_id'] );
								?>
								<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
									<td class="row_action">
										<?php echo module_quote::link_open( $quote['quote_id'], true ); ?>
									</td>
									<td>
										<?php
										echo print_date( $quote['date_create'] );
										?>
									</td>
									<td>
										<?php
										echo print_date( $quote['date_approved'] );
										?>
									</td>
									<td>
										<?php
										if ( $quote['approved_by'] ) {
											echo htmlspecialchars( $quote['approved_by'] );
										}
										?>
									</td>
									<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
									<td>
                            <span class="currency">
                            <?php echo dollar( $quote['total_amount'], true, $quote['currency_id'] ); ?>
                            </span>

										<?php } ?>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
					<?php
					$fieldset_data['elements_before'] = ob_get_clean();
				}

				echo module_form::generate_fieldset( $fieldset_data );
			}
		}

		// show the jobs linked to this website.
		if ( module_job::is_plugin_enabled() && module_job::can_i( 'view', 'Jobs' ) ) {
			$jobs = module_job::get_jobs( array( 'website_id' => $website_id ) );
			if ( count( $jobs ) || module_job::can_i( 'create', 'Jobs' ) ) {
				$h = array(
					'type'  => 'h3',
					'title' => module_config::c( 'project_name_single', 'Website' ) . ' Jobs',
				);
				if ( module_job::can_i( 'create', 'Jobs' ) ) {
					$h['button'] = array(
						'title' => 'New Job',
						'url'   => module_job::link_generate( 'new', array(
							'arguments' => array(
								'website_id' => $website_id,
							)
						) ),
					);
				}


				$fieldset_data = array(
					'heading' => $h,
				);

				if ( count( $jobs ) ) {
					$c = 0;
					ob_start();
					?>
					<div class="content_box_wheader">
						<table border="0" cellspacing="0" cellpadding="2"
						       class="tableclass tableclass_rows tableclass_full">
							<thead>
							<tr>
								<th>
									<?php _e( 'Job Title' ); ?>
								</th>
								<th>
									<?php _e( 'Date' ); ?>
								</th>
								<th>
									<?php _e( 'Due Date' ); ?>
								</th>
								<th>
									<?php _e( 'Complete' ); ?>
								</th>
								<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
									<th>
										<?php _e( 'Amount' ); ?>
									</th>
									<th>
										<?php _e( 'Invoice' ); ?>
									</th>
								<?php } ?>
							</tr>
							</thead>
							<tbody>
							<?php foreach ( $jobs as $job ) {
								$job = module_job::get_job( $job['job_id'] );
								?>
								<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
									<td class="row_action">
										<?php echo module_job::link_open( $job['job_id'], true ); ?>
									</td>
									<td>
										<?php
										echo print_date( $job['date_start'] );
										//is there a renewal date?
										if ( isset( $job['date_renew'] ) && $job['date_renew'] && $job['date_renew'] != '0000-00-00' ) {
											_e( ' to %s', print_date( strtotime( "-1 day", strtotime( $job['date_renew'] ) ) ) );
										}
										?>
									</td>
									<td>
										<?php
										if ( $job['total_percent_complete'] != 1 && strtotime( $job['date_due'] ) < time() ) {
											echo '<span class="error_text">';
											echo print_date( $job['date_due'] );
											echo '</span>';
										} else {
											echo print_date( $job['date_due'] );
										}
										?>
									</td>
									<td>
                            <span class="<?php
                            echo $job['total_percent_complete'] >= 1 ? 'success_text' : '';
                            ?>">
                                <?php echo ( $job['total_percent_complete'] * 100 ) . '%'; ?>
                            </span>
									</td>
									<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
										<td>
                            <span class="currency">
                            <?php echo dollar( $job['total_amount'], true, $job['currency_id'] ); ?>
                            </span>
											<?php if ( $job['total_amount_invoiced'] > 0 && $job['total_amount'] != $job['total_amount_invoiced'] ) { ?>
												<br/>
												<span
													class="currency">(<?php echo dollar( $job['total_amount_invoiced'], true, $job['currency_id'] ); ?>
													)</span>
											<?php } ?>
										</td>
										<td>
											<?php
											foreach ( module_invoice::get_invoices( array( 'job_id' => $job['job_id'] ) ) as $invoice ) {
												$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
												echo module_invoice::link_open( $invoice['invoice_id'], true );
												echo " ";
												echo '<span class="';
												if ( $invoice['total_amount_due'] > 0 ) {
													echo 'error_text';
												} else {
													echo 'success_text';
												}
												echo '">';
												if ( $invoice['total_amount_due'] > 0 ) {
													echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
													echo ' ' . _l( 'due' );
												} else {
													echo _l( '%s paid', dollar( $invoice['total_amount'], true, $invoice['currency_id'] ) );
												}
												echo '</span>';
												echo "<br>";
											} ?>
										</td>
									<?php } ?>
								</tr>
							<?php } ?>
							</tbody>
						</table>
					</div>
					<?php
					$fieldset_data['elements_before'] = ob_get_clean();
				}

				echo module_form::generate_fieldset( $fieldset_data );

			}
		}

		if ( module_invoice::is_plugin_enabled() && module_invoice::can_i( 'view', 'Invoices' ) && (int) $website_id > 0 ) {

			ob_start(); ?>
			<div class="content_box_wheader">
				<?php
				$website_invoices = module_invoice::get_invoices( array( 'website_id' => $website_id ) );
				if ( count( $website_invoices ) ) { ?>
					<?php //$invoice_safe = true; $invoice_from_job_page = $job_id; include('includes/plugin_invoice/pages/invoice_admin_list.php');
					?>
					<table class="tableclass tableclass_rows tableclass_full">
						<thead>
						<tr class="title">
							<th><?php echo _l( 'Invoice Number' ); ?></th>
							<th><?php echo _l( 'Status' ); ?></th>
							<th><?php echo _l( 'Due Date' ); ?></th>
							<th><?php echo _l( 'Sent Date' ); ?></th>
							<th><?php echo _l( 'Paid Date' ); ?></th>
							<th><?php echo _l( 'Invoice Total' ); ?></th>
							<th><?php echo _l( 'Amount Due' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php
						$c = 0;
						foreach ( $website_invoices as $invoice ) {
							$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
							?>
							<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
								<td class="row_action">
									<?php echo module_invoice::link_open( $invoice['invoice_id'], true, $invoice ); ?>
								</td>
								<td>
									<?php echo htmlspecialchars( $invoice['status'] ); ?>
								</td>
								<td>
									<?php
									if ( ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < time() ) {
										echo '<span class="error_text">';
										echo print_date( $invoice['date_due'] );
										echo '</span>';
									} else {
										echo print_date( $invoice['date_due'] );
									}
									?>
								</td>
								<td>
									<?php echo print_date( $invoice['date_sent'] ); ?>
								</td>
								<td>
									<?php echo $invoice['date_cancel'] != '0000-00-00' ? 'Cancelled' : print_date( $invoice['date_paid'] ); ?>
								</td>
								<td>
									<?php echo dollar( $invoice['total_amount'], true, $invoice['currency_id'] ); ?>
								</td>
								<td>
									<?php echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
									<?php if ( $invoice['total_amount_credit'] > 0 ) {
										?>
										<span
											class="success_text"><?php echo _l( 'Credit: %s', dollar( $invoice['total_amount_credit'], true, $invoice['currency_id'] ) ); ?></span>
										<?php
									} ?>
								</td>
							</tr>
						<?php } ?>
						</tbody>
					</table>
				<?php } ?>
			</div>
			<?php

			$fieldset_data = array(
				'heading'         => array(
					'title' => 'Website Invoices',
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			if ( module_invoice::can_i( 'create', 'Invoices' ) ) {
				$fieldset_data['heading']['button'] = array(
					'title' => 'New Invoice',
					'url'   => module_invoice::link_generate( 'new', array(
						'arguments' => array(
							'website_id' => $website_id,
						)
					) ),
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
		}

		if ( class_exists( 'module_timer' ) && module_timer::is_plugin_enabled() && module_timer::can_i( 'view', 'Timers' ) && (int) $website_id > 0 ) {


			$ucmtimers = new UCMTimers();
			$timers    = $ucmtimers->get( array(
				'owner_table' => 'website',
				'owner_id'    => $website_id
			), array( 'timer_id' => 'DESC' ) );
			/** START TABLE LAYOUT **/
			$table_manager         = module_theme::new_table_manager();
			$columns               = array();
			$columns['timer_name'] = array(
				'title'      => 'Description',
				'callback'   => function ( $timer ) {
					echo module_timer::link_open( $timer['timer_id'], true, $timer );
				},
				'cell_class' => 'row_action',
			);

			$columns['timer_status']   = array(
				'title'    => 'Status',
				'callback' => function ( $timer ) {
					$ucmtimer = new UCMTimer( $timer['timer_id'] );
					echo $ucmtimer->get_status_text();
				},
			);
			$columns['start_time']     = array(
				'title'    => 'Start Time',
				'callback' => function ( $timer ) {
					echo print_date( $timer['start_time'], true );
				},
			);
			$columns['timer_duration'] = array(
				'title'    => 'Duration',
				'callback' => function ( $timer ) {
					$ucmtimer = new UCMTimer( $timer['timer_id'] );
					echo $ucmtimer->get_total_time();
				},
			);

			$columns['user_id']    = array(
				'title'    => 'User',
				'callback' => function ( $timer ) {
					if ( ! empty( $timer['user_id'] ) ) {
						echo module_user::link_open( $timer['user_id'], true );
					}
				},
			);
			$columns['invoice_id'] = array(
				'title'    => 'Billable',
				'callback' => function ( $timer ) {
					if ( ! empty( $timer['billable'] ) ) {
						_e( 'Yes' );
					} else {
						_e( 'No' );
					}
					if ( ! empty( $timer['invoice_id'] ) ) {
						echo ' ' . module_invoice::link_open( $timer['invoice_id'], true );
					}
				},
			);


			if ( class_exists( 'module_group', false ) ) {
				$columns['timer_group'] = array(
					'title'    => 'Group',
					'callback' => function ( $timer ) {
						if ( isset( $timer['group_sort_timer'] ) ) {
							echo htmlspecialchars( $timer['group_sort_timer'] );
						} else {
							// find the groups for this timer.
							$groups = module_group::get_groups_search( array(
								'owner_table' => 'timer',
								'owner_id'    => $timer['timer_id'],
							) );
							$g      = array();
							foreach ( $groups as $group ) {
								$g[] = $group['name'];
							}
							echo htmlspecialchars( implode( ', ', $g ) );
						}
					}
				);
			}


			$table_manager->set_columns( $columns );
			$table_manager->set_rows( $timers );
			$table_manager->pagination = false;

			ob_start();
			$table_manager->print_table();
			$fieldset_data = array(
				'heading'         => array(
					'title' => 'Website Timers',
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			if ( module_timer::can_i( 'create', 'Timers' ) ) {
				$fieldset_data['heading']['button'] = array(
					'url'        => module_timer::link_open( 'new' ),
					'type'       => 'add',
					'title'      => _l( 'Start New Timer' ),
					'ajax-modal' => array(
						'type' => 'normal',
					),
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );

		}


		// and a hook for our new change request plugin
		hook_handle_callback( 'website_main', $website_id );
	}


	hook_handle_callback( 'layout_column_half', 'end' );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save ' . module_config::c( 'project_name_single', 'Website' ) ),
			),
			array(
				'ignore' => ! ( (int) $website_id && module_website::can_i( 'delete', 'Websites' ) ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . module_website::link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	?>


</form>
