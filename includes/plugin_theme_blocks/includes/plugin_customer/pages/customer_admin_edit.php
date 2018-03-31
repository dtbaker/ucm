<?php


$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
if ( ! module_config::c( 'customer_widgets', 1 ) || ! (int) $customer_id || isset( $_REQUEST['edit'] ) || isset( $_REQUEST['email'] ) ) {
	include_once 'includes/plugin_customer/pages/customer_admin_edit.php';
} else {
	// show new summary as a bunch of widgets.

	$page_title = _l( 'Summary' );


	hook_add( 'page_widgets', 'blocks_customer_page_widgets' );

	function blocks_customer_page_widgets( $call, $page_widgets, $page_id ) {
		if ( $page_id == 'customer-customer_admin_open' ) {

			$customer_id = (int) $_REQUEST['customer_id'];

			if ( ! $customer_id ) {
				return $page_widgets;
			}


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

			$count_widgets = array();


			if ( class_exists( 'module_quote', false ) && module_quote::can_i( 'view', 'quotes' ) ) {
				// find out how many open quotes are left..
				$quotes = module_quote::get_quotes( array( 'customer_id' => $customer_id, 'accepted' => 3 ) );

				$count_widgets['quotes'] = array(
					'id'      => 'open_quotes',
					'columns' => 4,
					'counter' => true,
					'link'    => module_quote::link_open( false ),
					'count'   => count( $quotes ),
					//'hidden' => !count($quotes),
					'title'   => _l( 'Incomplete quotes' ),
				);
			}

			if ( class_exists( 'module_job', false ) && module_job::can_i( 'view', 'Jobs' ) ) {
				// find out how many open jobs are left..
				$jobs = module_job::get_jobs( array(
					'customer_id' => $customer_id,
					'completed'   => 3
				), array( 'columns' => 'u.job_id' ) );

				$count_widgets['jobs'] = array(
					'id'      => 'open_jobs',
					'columns' => 4,
					'counter' => true,
					'link'    => module_job::link_open( false ),
					'count'   => count( $jobs ),
					//'hidden' => !count($jobs),
					'title'   => _l( 'Incomplete Jobs' ),
				);
			}

			if ( class_exists( 'module_invoice', false ) && module_invoice::can_i( 'view', 'Invoices' ) ) {
				// find out how many open invoices are left..
				$count    = 0;
				$invoices = module_invoice::get_invoices( array( 'customer_id' => $customer_id ), array(
					'custom_where' => " AND u.date_due != '0000-00-00' AND u.date_due <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "' AND u.date_paid = '0000-00-00'",
				) );

				foreach ( $invoices as $invoice ) {
					// needs 'overdue' and stuff which are unfortunately calculated.
					$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					if ( ! $invoice || $invoice['invoice_id'] != $invoice['invoice_id'] ) {
						continue;
					}
					if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
						continue;
					}
					$count ++;
				}

				$count_widgets['invoices'] = array(
					'id'      => 'open_invoices',
					'columns' => 4,
					'counter' => true,
					'link'    => module_invoice::link_open( false ),
					'count'   => $count,
					//'hidden' => !$count,
					'color'   => 'red',
					'title'   => _l( 'Overdue Invoices' ),
				);
			}


			if ( class_exists( 'module_ticket', false ) && module_ticket::can_i( 'view', 'Tickets' ) ) {
				// find out how many open tickets are left..
				$ticket_count             = module_ticket::get_total_ticket_count( $customer_id );
				$count_widgets['tickets'] = array(
					'id'    => 'open_tickets',
					'link'  => module_ticket::link_open( false ),
					//'hidden' => !$ticket_count,
					'count' => $ticket_count,
					'title' => _l( 'Open Tickets' ),
				);
			}


			// now we find any reminders for this particular customer
			if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {

				// find out all the linked note data and query this in SQL to find any upcoming reminders

				$note_summary_owners = module_customer::get_note_summary_owners( $customer_id );
				$end_time            = strtotime( module_config::c( "customer_note_reminder_days", "+10 days" ) );
				$sql                 = "SELECT * FROM `" . _DB_PREFIX . "note` n ";
				$sql                 .= " WHERE n.`reminder` = 1  "; //AND n.note_time <= ".(int)$end_time."";
				$sql                 .= " AND ( ";
				$sql                 .= " ( n.owner_table = 'customer' AND n.owner_id = " . (int) $customer_id . ") ";
				foreach ( $note_summary_owners as $owner => $ids ) {
					foreach ( $ids as $id ) {
						$sql .= " OR ( n.owner_table = '" . $owner . "' AND n.owner_id = " . (int) $id . ") ";
					}
				}
				$sql                        .= " ) ORDER BY n.note_time ASC";
				$reminders                  = qa( $sql );
				$count_widgets['reminders'] = array(
					'id'    => 'note_reminders',
					'link'  => module_customer::link_generate( $customer_id, array(
						'full'      => false,
						'arguments' => array( 'edit' => 'yes' )
					) ),
					//'hidden' => !$ticket_count,
					'count' => count( $reminders ),
					'title' => _l( 'Reminders' ),
				);

			}

			$count_widgets = hook_filter_var( 'page_count_widgets', $count_widgets, $page_id );

			if ( $count_widgets ) {
				ob_start();
				$colors      = array( 'red', 'green', 'blue', 'yellow' );
				$color_count = 0;
				?>
				<div class="circle-stats">
					<div class="fake-table">
						<div class="fake-table-cell">
							<?php foreach ( $count_widgets as $counter_circle ) {
								if ( ! empty( $counter_circle['hidden'] ) ) {
									continue;
								}
								if ( empty( $counter_circle['color'] ) ) {
									$counter_circle['color'] = $colors[ $color_count ];
									$color_count ++;
									if ( $color_count >= count( $colors ) ) {
										$color_count = 0;
									}
								}
								?>
								<div class="circle <?php echo $counter_circle['color'] ?>">
									<a href="<?php echo $counter_circle['link']; ?>" class="fake-table">
										<div class="fake-table-cell">
											<p class="counter"><?php echo $counter_circle['count']; ?></p>
											<span><?php echo $counter_circle['title']; ?></span>
										</div>
									</a>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<?php
				$page_widgets[] = array(
					'id'      => 'counter_widget',
					'title'   => false,
					'icon'    => false,
					'columns' => 1,
					'content' => ob_get_clean(),
				);
			}


			ob_start();


			$show_previous_weeks = module_config::c( 'dashboard_graph_previous_weeks', 10 );
			$show_coming_weeks   = module_config::c( 'dashboard_graph_coming_weeks', 7 );
			$home_summary        = array(
				array(
					"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - ( ( $show_previous_weeks ) * 7 ) + 1, date( 'Y' ) ) ),
					// 7 weeks ago
					//"week_end" => date('Y-m-d', strtotime('-1 day',mktime(1, 0, 0, date('m'), date('d')+(6-date('N'))-(2*7)+2, date('Y')))), // 2 weeks ago
					"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) + 2, date( 'Y' ) ) ),
					// today
					'table_name' => 'Finance Chart',
					'array_name' => 'finance_chart',
					'multiplyer' => 7,
					'col1'       => 'week',
					'row_limit'  => $show_previous_weeks,
				),
			);

			$x = 0;
			foreach ( $home_summary as $home_sum ) {
				$x ++;
				extract( $home_sum ); // hacky, better than old code tho.
				$data = module_finance::get_finance_summary( $week_start, $week_end, $multiplyer, $row_limit, $customer_id );
				// return the bits that will be used in the output of the HTML table (and now in the calendar module output)
				$finance_data = array(
					'data'       => $data,
					'table_name' => $table_name,
					'col1'       => $col1,
				);
				//print_r($finance_data);
				?>

				<div id="finance_chart_<?php echo $x; ?>"></div>
				<script type="text/javascript">/* Morris.js Charts */
            // Sales chart
            var chart_data = [];
					<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
					if ( ! isset( $week_data['week'] ) ) {
						continue;
					}?>
            chart_data.push({
                y: '<?php echo $week_data['week'];?>',
                item1: <?php echo $week_data['chart_hours'];?>,
                item2: <?php echo $week_data['chart_amount_invoiced'];?>,
                item3: <?php echo $week_data['chart_amount_paid'];?>
							<?php if ( module_finance::is_expense_enabled() ) { ?>,
                item4: <?php echo $week_data['chart_amount_spent'];?>
							<?php } if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
							?>,
                item5: <?php echo $week_data['chart_envato_earnings'];?> <?php
							} ?>
            });
					<?php } ?>
            var area = new Morris.Line({
                element: 'finance_chart_<?php echo $x;?>',
                resize: true,
                data: chart_data,
                xkey: 'y',
                ykeys: ['item1', 'item2', 'item3'<?php if ( module_finance::is_expense_enabled() ) { ?>, 'item4'
									<?php } if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
									?>, 'item5' <?php
									} ?>],
                labels: ['<?php _e( 'Hours' ); ?>', '<?php _e( 'Invoiced' ); ?>', '<?php _e( 'Income' ); ?>'
									<?php if ( module_finance::is_expense_enabled() ) { ?>, '<?php _e( 'Expense' ); ?>'
									<?php }
									if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
									?>, '<?php _e( 'Envato' ); ?>' <?php
									}?>],
                lineColors: ['#a0d0e0', '#3c8dbc', '#0f720a', '#b8180c', '#59b80c'],
                hideHover: 'auto',
                parseTime: false
            });</script>

				<?php
			}
			$page_widgets[] = array(
				'id'      => 'finance_chart',
				'title'   => _l( 'Finance Chart' ),
				'icon'    => 'piggy_bank',
				'columns' => 1,
				'row_id'  => 3,
				'content' => ob_get_clean(),
			);


			$customer = module_customer::get_customer( $customer_id );

			$fieldset_data = array(
				'heading'  => false,
				'class'    => 'tableclass tableclass_form tableclass_full',
				'elements' => array(
					array(
						'title'  => _l( 'Name' ),
						'fields' => array(
							$customer['customer_name']
						),
					),
					array(
						'title'  => _l( 'Address' ),
						'fields' => array(
							implode( ' ', $customer['customer_address'] )
						),
					),
				)
			);
			if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() && module_extra::can_i( 'view', $page_type ) ) {
				$fieldset_data['extra_settings'] = array(
					'owner_table' => 'customer',
					'owner_key'   => 'customer_id',
					'owner_id'    => $customer_id,
					'layout'      => 'table_row',
					'allow_new'   => false,
					'allow_edit'  => false,
				);
			}
			$page_widgets[] = array(
				'id'      => 'customer_details',
				'title'   => 'Customer Details',
				'button'  => array(
					'title' => 'More',
					'url'   => module_customer::link_generate( $customer_id, array(
						'full'      => false,
						'data'      => $customer,
						'arguments' => array( 'edit' => 'yes' )
					) )
				),
				'icon'    => 'pencil',
				'columns' => 4,
				'content' => module_form::generate_fieldset( $fieldset_data ),
			);


			if ( $customer['primary_user_id'] ) {
				ob_start();
				module_user::print_contact_summary( $customer['primary_user_id'], 'html', array( 'name' ) );
				$contact_name = ob_get_clean();
				ob_start();
				module_user::print_contact_summary( $customer['primary_user_id'], 'html', array( 'phone' ) );
				$contact_phone = ob_get_clean();
				ob_start();
				module_user::print_contact_summary( $customer['primary_user_id'], 'html', array( 'email' ) );
				$contact_email  = ob_get_clean();
				$fieldset_data  = array(
					'heading'  => false,
					'class'    => 'tableclass tableclass_form tableclass_full',
					'elements' => array(
						array(
							'title'  => _l( 'Contact' ),
							'fields' => array(
								$contact_name
							),
						),
						array(
							'title'  => _l( 'Phone' ),
							'fields' => array(
								$contact_phone
							),
						),
						array(
							'title'  => _l( 'Email' ),
							'fields' => array(
								$contact_email
							),
						),
					)
				);
				$page_widgets[] = array(
					'id'      => 'customer_contact',
					'title'   => 'Contact Details',
					'button'  => array(
						'title' => 'More',
						'url'   => module_user::link_open_contact( $customer['primary_user_id'] )
					),
					'icon'    => 'pencil',
					'columns' => 4,
					'content' => module_form::generate_fieldset( $fieldset_data ),
				);
			}

			$emails = module_email::get_emails( array( 'customer_id' => $customer_id ) );
			if ( is_array( $emails ) ) {
				$emails = array_slice( $emails, 0, 6 );
			}

			$table_manager            = module_theme::new_table_manager();
			$columns                  = array();
			$columns['email_subject'] = array(
				'title'      => 'Email Subject',
				'callback'   => function ( $email ) {
					echo module_email::link_open( $email['email_id'], true );
				},
				'cell_class' => 'row_action',
			);
			$columns['email_date']    = array(
				'title'    => 'Sent Date',
				'callback' => function ( $email ) {
					echo print_date( $email['sent_time'] );
				},
			);
			$table_manager->set_columns( $columns );
			$table_manager->row_callback = function ( $row_data ) {
				// load the full email data before displaying each row so we have access to more details
				if ( isset( $row_data['email_id'] ) && (int) $row_data['email_id'] > 0 ) {
					// not needed in this case
					//return module_email::get_email($row_data['email_id']);
				}

				return array();
			};
			$table_manager->set_rows( $emails );
			$table_manager->pagination    = false;
			$table_manager->blank_message = 'No Recent Emails';
			ob_start();
			$table_manager->print_table();
			$email_html     = ob_get_clean();
			$page_widgets[] = array(
				'id'      => 'customer_email',
				'title'   => 'Recently Sent Emails',
				'button'  => array(
					array(
						'title' => 'All',
						'url'   => module_email::link_open( false )
					),
					array(
						'title' => 'New',
						'url'   => module_email::link_open( 'new' )
					)
				),
				'icon'    => 'pencil',
				'columns' => 4,
				'content' => $email_html,
			);

			if ( isset( $reminders ) ) {
				ob_start();
				$note_summary_owners = module_customer::get_note_summary_owners( $customer_id );
				module_note::display_notes( array(
						'title'           => 'Notes',
						'owner_table'     => 'customer',
						'owner_id'        => $customer_id,
						'view_link'       => module_customer::link_open( $customer_id ),
						'display_summary' => true,
						'summary_owners'  => $note_summary_owners
					)
				);
				$reminder_html  = ob_get_clean();
				$page_widgets[] = array(
					'id'      => 'customer_reminder',
					'title'   => false,
					'icon'    => 'pencil',
					'columns' => 4,
					'raw'     => true,
					'content' => $reminder_html,
				);
			}


		}

		return $page_widgets;
	}
}

?>