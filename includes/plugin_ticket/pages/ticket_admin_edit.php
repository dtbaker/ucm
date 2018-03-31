<?php

if ( ! $ticket_safe ) {
	die( 'failed' );
}


$ticket_id = (int) $_REQUEST['ticket_id'];
$ticket    = module_ticket::get_ticket( $ticket_id );

if ( $ticket['subject'] ) {
	$module->page_title = _l( 'Ticket: ' . htmlspecialchars( $ticket['subject'] ) );
}

if ( ! empty( $ticket['due_timestamp'] ) && module_config::c( 'ticket_allow_due_time', 1 ) ) {
	$limit_time = $ticket['due_timestamp'];
}

$admins_rel = module_ticket::get_ticket_staff_rel();
if ( isset( $admins_rel[ module_security::get_loggedin_id() ] ) ) {
	$admins_rel[ module_security::get_loggedin_id() ] .= ' (me)';
}

// work out if this user is an "administrator" or a "customer"
// a user will have "edit" capabilities for tickets if they are an administrator
// a user will only have "view" Capabilities for tickets if they are a "customer"
// this will decide what options they have on the page (ie: assigning tickets to people)


if ( $ticket_id > 0 && $ticket && $ticket['ticket_id'] == $ticket_id ) {
	if ( class_exists( 'module_security', false ) ) {
		/*module_security::check_page(array(
            'module' => $module->module_name,
            'feature' => 'edit',
		));*/
		// we want to do our own special type of form modification here
		// so we don't pass it off to "check_page" which will hide all input boxes.
		if ( ! module_ticket::can_i( 'edit', 'Tickets' ) && ! module_ticket::can_i( 'create', 'Tickets' ) ) {
			set_error( 'Access to editing or creating tickets is denied.' );
			redirect_browser( module_ticket::link_open( false ) );
		}
	}
} else {
	$ticket_id = false;
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'module'  => $module->module_name,
			'feature' => 'create',
		) );
	}
}

if ( module_ticket::can_edit_tickets() ) {
	module_ticket::mark_as_read( $ticket_id, true );
}
//$module->pre_menu(); // so the links are re-build and the correct "unread" count is at the top.


if ( ! module_security::can_access_data( 'ticket', $ticket ) ) {
	echo 'Ticket access denied';
	exit;
}

$ticket_messages = module_ticket::get_ticket_messages( $ticket['ticket_id'], true );

if ( ! isset( $logged_in_user ) || ! $logged_in_user ) {
	// we assume the user is on the public side.
	// use the creator id as the logged in id.
	$logged_in_user = module_security::get_loggedin_id();
}
$ticket_creator = $ticket['user_id'];
if ( $ticket_creator == $logged_in_user ) {
	// we are sending a reply back to the admin, from the end user.
	$to_user_id   = $ticket['assigned_user_id'] ? $ticket['assigned_user_id'] : 1;
	$from_user_id = $logged_in_user;
} else {
	// we are sending a reply back to the ticket user.
	$to_user_id   = $ticket['user_id'];
	$from_user_id = $logged_in_user;
}
$to_user_a   = module_user::get_user( $to_user_id, false );
$from_user_a = module_user::get_user( $from_user_id, false );

if ( isset( $ticket['ticket_account_id'] ) && $ticket['ticket_account_id'] ) {
	$ticket_account = module_ticket::get_ticket_account( $ticket['ticket_account_id'] );
} else {
	$ticket_account = false;
}


if ( $ticket_account && $ticket_account['email'] ) {
	$reply_to_address = $ticket_account['email'];
	$reply_to_name    = $ticket_account['name'];
} else {
	// reply to creator.
	$reply_to_address = $from_user_a['email'];
	$reply_to_name    = $from_user_a['name'];
}


if ( $ticket_creator == $logged_in_user ) {
	$send_as_name    = $from_user_a['name'];
	$send_as_address = $from_user_a['email'];
} else {
	$send_as_address = $reply_to_address;
	$send_as_name    = $reply_to_name;
}


$last_response_from = 'admin'; // or customer.

// find the prev/next tickets.
$temp_prev    = $prev_ticket = $next_ticket = false;
$temp_tickets = isset( $_SESSION['_ticket_nextprev'] ) ? $_SESSION['_ticket_nextprev'] : array();
foreach ( $temp_tickets as $key => $val ) {
	if ( $prev_ticket && ! $next_ticket ) {
		$next_ticket = $val;
	}
	if ( $val == $ticket_id ) {
		$prev_ticket = ( $temp_prev ) ? $temp_prev : true;
	}
	$temp_prev = $val;
}


$form_actions = array(
	'class'    => 'action_bar action_bar_center action_bar_single',
	'elements' => array(
		array(
			'type'  => 'save_button',
			'name'  => 'butt_save',
			'value' => _l( 'Save details' ),
		),
		array(
			'ignore' => ! ( (int) $ticket_id && module_ticket::can_i( 'delete', 'Tickets' ) ),
			'type'   => 'delete_button',
			'name'   => 'butt_del',
			'value'  => _l( 'Delete' ),
		),
		array(
			'type'    => 'button',
			'name'    => 'cancel',
			'value'   => _l( 'Cancel' ),
			'class'   => 'submit_button',
			'onclick' => "window.location.href='" . module_ticket::link_open( false ) . "';",
		),
	),
);
if ( (int) $ticket_id && module_ticket::can_edit_tickets() ) {
	$form_actions['elements'][] = array(
		'type'  => 'submit',
		'name'  => 'mark_as_unread',
		'value' => _l( 'Mark as unread' ),
	);
}
if ( $prev_ticket && $prev_ticket !== true ) {
	array_unshift( $form_actions['elements'], array(
		'type'    => 'button',
		'onclick' => "window.location.href='" . module_ticket::link_open( $prev_ticket ) . "';",
		'name'    => 'prev_ticket',
		'value'   => _l( 'Prev Ticket' ),
	) );
}
if ( $next_ticket ) {
	$form_actions['elements'][] = array(
		'type'    => 'button',
		'onclick' => "window.location.href='" . module_ticket::link_open( $next_ticket ) . "';",
		'name'    => 'next_ticket',
		'value'   => _l( 'Next Ticket' ),
	);
}
$action_buttons = module_form::generate_form_actions( $form_actions );
?>


	<script type="text/javascript">
      ucm.ticket.ticket_message_text_is_html = <?php echo module_config::c( 'ticket_message_text_or_html', 'html' ) == 'html' ? 'true' : 'false'; ?>;
      ucm.ticket.ticket_url = '<?php echo module_ticket::link_open( $ticket_id, false );?>';
      $(function () {
          ucm.ticket.init();
      });
	</script>
	<form action="" method="post" id="ticket_form" enctype="multipart/form-data">
		<input type="hidden" name="_process" value="save_ticket"/>
		<input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>"/>

		<?php

		echo $action_buttons;

		$fields = array(
			'fields' => array(
				'subject' => 'Subject',
			)
		);
		module_form::set_required(
			$fields
		);
		module_form::prevent_exit( array(
				'valid_exits' => array(
					// selectors for the valid ways to exit this form.
					'.submit_button',
					'.save_task',
					'.notify',
					'.delete',
					'.attachment_link',
				)
			)
		);


		hook_handle_callback( 'layout_column_half', 1, '35' );

		/** TICKET DETAILS */
		$responsive_summary   = array();
		$responsive_summary[] = '#' . module_ticket::ticket_number( $ticket['ticket_id'] );
		if ( ( $ticket['status_id'] == 2 || $ticket['status_id'] == 3 || $ticket['status_id'] == 5 ) ) {
			$responsive_summary[] = ordinal( $ticket['position'] );
		}
		$responsive_summary[] = htmlspecialchars( $ticket['subject'] );
		$fieldset_data        = array(
			'heading'  => array(
				'title'      => _l( 'Ticket Details' ),
				'type'       => 'h3',
				'responsive' => array(
					'summary' => implode( ', ', $responsive_summary ),
				),
			),
			'elements' => array(
				array(
					'title'  => _l( 'Ticket Number' ),
					'fields' => array(
						function () use ( $ticket ) {
							?>
							<span
								class="ticket_status_<?php echo (int) $ticket['status_id']; ?>"><?php echo module_ticket::ticket_number( $ticket['ticket_id'] ); ?></span>
							<?php
							if ( $ticket['status_id'] == 2 || $ticket['status_id'] == 3 || $ticket['status_id'] == 5 ) {
								echo _l( '(%s out of %s tickets)', ordinal( $ticket['position'] ), $ticket['total_pending'] );
							}
							?>
							<input type="hidden" name="status_id" value="<?php echo $ticket['status_id']; ?>">
							<?php
						}
					),
				),
			),
		);
		if ( $ticket['last_message_timestamp'] ) {
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Date/Time' ),
				'fields' => array(
					function () use ( $ticket, $limit_time ) {
						if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
							//echo '<span class="important">';
						}
						echo print_date( $ticket['last_message_timestamp'], true );
						// how many days ago was this?
						echo ' (';
						echo fuzzy_date( $ticket['last_message_timestamp'] );
						echo ')';
						if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
							//echo '</span>';
						}
					}
				)
			);
		}
		if ( module_config::c( 'ticket_allow_due_time', 1 ) && array_key_exists( 'due_timestamp', $ticket ) ) {

			if ( module_ticket::can_edit_tickets() ) {
				$fieldset_data['elements'][] = array(
					'title'  => _l( 'Due Date/Time' ),
					'fields' => array(
						array(
							'type'  => 'date_time',
							'name'  => 'due_timestamp',
							'value' => empty( $ticket['due_timestamp'] ) ? '' : $ticket['due_timestamp'],
						),
						function () use ( $ticket, $limit_time ) {
							if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
								echo '<span class="important">';
							}
							// how many days ago was this?
							if ( $ticket['due_timestamp'] ) {
								echo ' (';
								echo fuzzy_date( $ticket['due_timestamp'] );
								echo ')';
							}
							if ( module_ticket::is_ticket_overdue( $ticket['ticket_id'], $ticket ) ) {
								echo '</span>';
							}
						}
					)
				);
			} else if ( ! empty( $ticket['due_timestamp'] ) ) {

				$fieldset_data['elements'][] = array(
					'title'  => _l( 'Due Date/Time' ),
					'fields' => array(
						function () use ( $ticket, $limit_time ) {
							echo print_date( $ticket['due_timestamp'], true );
							if ( time() < $limit_time ) {
								echo '<span class="important">';
							}
							// how many days ago was this?
							if ( $ticket['due_timestamp'] ) {
								echo ' (';
								echo fuzzy_date( $ticket['due_timestamp'] );
								echo ')';
							}
							if ( time() < $limit_time ) {
								echo '</span>';
							}
						}
					)
				);
			}
		}
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Subject' ),
			'fields' => array(
				function () use ( $ticket ) {
					if ( $ticket['subject'] ) {
						echo htmlspecialchars( $ticket['subject'] );
					} else { ?>
						<input type="text" name="subject" id="subject"
						       value="<?php echo htmlspecialchars( $ticket['subject'] ); ?>"/>
					<?php }
				}
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Assigned Staff' ),
			'fields' => array(
				function () use ( $ticket, $admins_rel ) {
					if ( module_ticket::can_edit_tickets() ) {
						echo print_select_box( $admins_rel, 'assigned_user_id', $ticket['assigned_user_id'] );
						echo _h( 'This is anyone with ticket EDIT permissions.' );
						?>

						<input type="submit" name="butt_notify_staff" value="<?php _e( 'Notify' ); ?>" class="notify small_button">
						<?php
					} else {
						echo friendly_key( $admins_rel, $ticket['assigned_user_id'] );
					}
				}
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Assigned Contact' ),
			'fields' => array(
				function () use ( $ticket, $ticket_id ) {
					$create_user = module_user::get_user( $ticket['user_id'], false );
					if ( module_ticket::can_edit_tickets() && ! (int) $ticket_id ) {
						$c = array();
						if ( $ticket['customer_id'] ) {
							$res = module_user::get_contacts( array( 'customer_id' => $ticket['customer_id'] ) );
						} else {
							$res = array();
						}
						while ( $row = array_shift( $res ) ) {
							$c[ $row['user_id'] ] = $row['name'] . ' ' . $row['last_name'];
						}
						if ( $ticket['user_id'] && ! isset( $c[ $ticket['user_id'] ] ) ) {
							// this option isn't in the listing. add it in.
							$c[ $ticket['user_id'] ] = $create_user['name'] . ' ' . $create_user['last_name'];
							if ( $create_user['customer_id'] >= 0 ) {
								$c[ $ticket['user_id'] ] .= ' ' . _l( '(under different customer)' );
							} else {
								// user not assigned to a customer.
							}
						}
						echo print_select_box( $c, 'change_user_id', $ticket['user_id'] );
					} else {
						//
						if ( $create_user['customer_id'] ) {
							echo module_user::link_open_contact( $ticket['user_id'], true, array(), true );
						} else {
							echo module_user::link_open( $ticket['user_id'], true, array(), true );
						}
						echo ' ' . htmlspecialchars( $create_user['email'] );
					}
				}
			)
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Type/Department' ),
			'fields' => array(
				array(
					'type'             => 'select',
					'name'             => 'ticket_type_id',
					'value'            => $ticket['ticket_type_id'],
					'options'          => module_ticket::get_types(),
					'blank'            => module_ticket::can_edit_tickets(),
					'options_array_id' => 'name',
				)
			)
		);
		if ( class_exists( 'module_faq', false ) && module_config::c( 'ticket_faq_link', 1 ) && module_faq::get_faq_products() > 0 ) {
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Product' ),
				'fields' => array(
					function () use ( $ticket, $ticket_id ) {
						if ( module_ticket::can_edit_tickets() ) {
							echo print_select_box( module_faq::get_faq_products_rel(), 'faq_product_id', $ticket['faq_product_id'] );
							_h( 'Use this to link a ticket to a product. Set products in Settings > FAQ. This allows you to have different FAQ items for different products. Users are shown the FAQ items before submitting a support ticket.' );
						} else {
							echo friendly_key( module_faq::get_faq_products_rel(), $ticket['faq_product_id'] );
						}
						// show a button that does a jquery popup with the list of faq items and an option to create new one.
						//if(module_faq::can_i('edit','FAQ')){                                                                            echo ' ';
						echo popup_link( '<a href="' . module_faq::link_open_list( $ticket['faq_product_id'] ) . '">' . _l( 'FAQ' ) . '</a>', array(
							'force'  => true,
							'width'  => 1100,
							'height' => 600,
						) );
						//}
					}
				)
			);
		}
		if ( module_config::c( 'ticket_support_accounts', 1 ) && module_ticket::get_accounts_rel() ) {
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Account' ),
				'fields' => array(
					array(
						'type'    => module_ticket::can_edit_tickets() ? 'select' : 'html',
						'name'    => 'ticket_account_id',
						'value'   => module_ticket::can_edit_tickets() ? $ticket['ticket_account_id'] : friendly_key( module_ticket::get_accounts_rel(), $ticket['ticket_account_id'] ),
						'options' => module_ticket::get_accounts_rel(),
					)
				)
			);
		}
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Status' ),
			'fields' => array(
				array(
					'type'    => module_ticket::can_edit_tickets() ? 'select' : 'html',
					'name'    => 'status_id',
					'value'   => module_ticket::can_edit_tickets() ? $ticket['status_id'] : friendly_key( module_ticket::get_statuses(), $ticket['status_id'] ),
					'options' => module_ticket::get_statuses(),
				)
			)
		);
		if ( module_ticket::can_edit_tickets() || module_config::c( 'ticket_allow_priority_selection', 0 ) ) {

			$priorities = module_ticket::get_ticket_priorities();
			if ( ! module_ticket::can_edit_tickets() && isset( $priorities[ _TICKET_PRIORITY_STATUS_ID ] ) && $ticket['priority'] != _TICKET_PRIORITY_STATUS_ID ) {
				unset( $priorities[ _TICKET_PRIORITY_STATUS_ID ] );
			}
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Priority' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'priority',
						'value'   => $ticket['priority'],
						'blank'   => false,
						'options' => $priorities,
					)
				)
			);
		}
		$fieldset_data['extra_settings'] = array(
			'owner_table' => 'ticket',
			'owner_key'   => 'ticket_id',
			'owner_id'    => $ticket['ticket_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_extra::can_i( 'create', 'Tickets' ),
			'allow_edit'  => module_extra::can_i( 'edit', 'Tickets' ),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

		$done_messages = false;
		if ( class_exists( 'module_mobile', false ) ) {
			if ( module_mobile::is_mobile_browser() ) {
				// we're on mobile, display the ticket messages here:
				$tickets_in_reverse = false;
				include module_theme::include_ucm( 'includes/plugin_ticket/pages/ticket_admin_edit_messages.php' );
				$done_messages = true;
			}
		}

		if ( $ticket['user_id'] ) {
			if ( module_config::c( 'ticket_other_list_by', 'user' ) == 'user' ) {
				$other_tickets = module_ticket::get_tickets( array( 'user_id' => $ticket['user_id'] ) );
			} else if ( module_config::c( 'ticket_other_list_by', 'user' ) == 'customer' && $ticket['customer_id'] ) {
				$other_tickets = module_ticket::get_tickets( array( 'customer_id' => $ticket['customer_id'] ) );
			} else {
				$other_tickets = false;
			}
			if ( $other_tickets !== false && mysqli_num_rows( $other_tickets ) > 1 ) {
				$other_status = array();
				ob_start();
				?>
				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full tbl_fixed">
					<tbody>
					<?php while ( $other_ticket = mysqli_fetch_assoc( $other_tickets ) ) { ?>
						<tr>
							<td style="width:55px;">
								<?php echo $other_ticket['ticket_id'] == $ticket_id ? '&raquo;' : ''; ?>
								<?php echo module_ticket::link_open( $other_ticket['ticket_id'], true ); ?>
							</td>
							<td>
								<?php if ( $other_ticket['priority'] == _TICKET_PRIORITY_STATUS_ID ) {
									echo '$';
								} ?>
								<?php echo htmlspecialchars( $other_ticket['subject'] ); ?>
							</td>
							<td style="width:100px;">
								<?php echo htmlspecialchars( module_ticket::$ticket_statuses[ $other_ticket['status_id'] ] );
								if ( ! isset( $other_status[ module_ticket::$ticket_statuses[ $other_ticket['status_id'] ] ] ) ) {
									$other_status[ module_ticket::$ticket_statuses[ $other_ticket['status_id'] ] ] = 0;
								}
								$other_status[ module_ticket::$ticket_statuses[ $other_ticket['status_id'] ] ] ++;
								?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php
				$responsive_summary = array();
				foreach ( $other_status as $status => $count ) {
					$responsive_summary[] = $count . ' ' . $status;
				}
				$fieldset_data = array(
					'heading'         => array(
						'title'      => _l( '%s Other Support Tickets', mysqli_num_rows( $other_tickets ) ),
						'type'       => 'h3',
						'responsive' => array(
							'summary' => htmlspecialchars( implode( ', ', $responsive_summary ) ),
						)
					),
					'elements_before' => ob_get_clean(),
				);
				echo module_form::generate_fieldset( $fieldset_data );
				unset( $fieldset_data );
			}
		}

		//(int)$ticket_id > 0 &&
		if ( file_exists( dirname( __FILE__ ) . '/../inc/ticket_extras_sidebar.php' ) ) {
			include( dirname( __FILE__ ) . '/../inc/ticket_extras_sidebar.php' );
		}
		if ( file_exists( dirname( __FILE__ ) . '/../inc/ticket_billing.php' ) ) {
			include( dirname( __FILE__ ) . '/../inc/ticket_billing.php' );
		}
		if ( (int) $ticket_id > 0 && file_exists( dirname( __FILE__ ) . '/../inc/ticket_priority_sidebar.php' ) ) {
			//if($ticket['priority'] == _TICKET_PRIORITY_STATUS_ID || (isset($ticket['invoice_id']) && $ticket['invoice_id'])){
			include( dirname( __FILE__ ) . '/../inc/ticket_priority_sidebar.php' );
			// }
		}

		if ( isset( $ticket['ticket_id'] ) && (int) $ticket['ticket_id'] > 0 && module_ticket::can_edit_tickets() ) {
			if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() && module_config::c( 'ticket_enable_notes', 1 ) ) {
				module_note::display_notes( array(
						'title'       => 'Ticket Notes',
						'owner_table' => 'ticket',
						'owner_id'    => $ticket_id,
						'view_link'   => module_ticket::link_open( $ticket['ticket_id'] ),
					)
				);
			}
			if ( class_exists( 'module_timer', false ) && module_timer::is_plugin_enabled() && module_config::c( 'timer_enable_tickets', 1 ) ) {
				module_timer::display_timers( array(
						'title'       => 'Ticket Timers',
						'owner_table' => 'ticket',
						'owner_id'    => $ticket_id,
						'customer_id' => ! empty( $ticket['customer_id'] ) ? (int) $ticket['customer_id'] : 0,
					)
				);
			}
			if ( class_exists( 'module_group', false ) && module_config::c( 'ticket_enable_groups', 1 ) ) {
				module_group::display_groups( array(
					'title'       => 'Ticket Groups',
					'owner_table' => 'ticket',
					'owner_id'    => $ticket['ticket_id'],
					'view_link'   => module_ticket::link_open( $ticket['ticket_id'] ),

				) );
			}

		}


		if ( module_ticket::can_edit_tickets() ) {

			/** RELATED TO */
			$responsive_summary   = array();
			$responsive_summary[] = module_customer::link_open( $ticket['customer_id'], true );
			$fieldset_data        = array(
				'heading'  => array(
					'title'      => _l( 'Related to' ),
					'type'       => 'h3',
					'responsive' => array(
						'summary' => implode( ', ', $responsive_summary ),
					),
				),
				'elements' => array(),
			);
			if ( module_ticket::can_edit_tickets() ) {
				$fieldset_data['elements']['customer'] = array(
					'title'  => _l( 'Customer' ),
					'fields' => array(
						array(
							'type'   => 'text',
							'name'   => 'customer_id',
							'lookup' => array(
								'key'         => 'customer_id',
								'display_key' => 'customer_name',
								'plugin'      => 'customer',
								'lookup'      => 'customer_name',
								'return_link' => true,
								'display'     => ! empty( $ticket['customer_name'] ) ? $ticket['customer_name'] : '',
							),
							'value'  => $ticket['customer_id'],
						)
					),
				);
				if ( module_customer::can_i( 'create', 'Customers' ) && $ticket['user_id'] && (int) $ticket_id > 0 ) {
					// is this a user, or a staff member. don't allow moving of staff members. (or maybe later we will)
					ob_start();
					?>
					<input type="button" name="new_customer" value="<?php _e( 'New' ); ?>"
					       onclick="window.location.href='<?php echo module_customer::link_open( 'new', false ); ?>&move_user_id=<?php echo $ticket['user_id']; ?>';"
					       class="small_button"><?php _h( 'Create a new customer and move this "Assigned Contact" to this new customer.' ); ?>
					<?php

					$fieldset_data['elements']['customer']['fields'][] = ob_get_clean();
				}

				$fieldset_data['elements']['customer_contact'] = array(
					'title'  => _l( 'Contact' ),
					'fields' => array(
						array(
							'type'   => 'text',
							'name'   => 'change_user_id',
							'lookup' => array(
								'key'         => 'user_id',
								'display_key' => 'name',
								'plugin'      => 'user',
								'lookup'      => 'contact_name',
								'return_link' => true,
								'display'     => ! empty( $ticket['contact_name'] ) ? $ticket['contact_name'] : '',
							),
							'value'  => $ticket['user_id'],
						)
					),
				);
			}

			$res = module_website::get_websites( array( 'customer_id' => $ticket['customer_id'] ) );
			if ( count( $res ) ) {
				$fieldset_data['elements'][] = array(
					'title'  => _l( '' . module_config::c( 'project_name_single', 'Website' ) ),
					'fields' => array(
						function () use ( $res, $ticket ) {
							$c = array();
							while ( $row = array_shift( $res ) ) {
								$c[ $row['website_id'] ] = $row['name'];
							}
							echo print_select_box( $c, 'website_id', $ticket['website_id'] );
						}
					)
				);
			}
			if ( (int) $ticket_id > 0 ) {
				$fieldset_data['elements'][] = array(
					'title'  => _l( 'Public link' ),
					'fields' => array(
						function () use ( $ticket_id ) {
							?> <a href="<?php echo module_ticket::link_public( $ticket_id ); ?>"
							      target="_blank"><?php _e( 'click here' ); ?></a> <?php
						}
					)
				);
			}

			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );

			handle_hook( 'ticket_sidebar', $ticket_id );

		} // end can edit


		hook_handle_callback( 'layout_column_half', 2, '65' );

		if ( $ticket_id > 0 && module_ticket::can_edit_tickets() && ! $ticket['assigned_user_id'] ) {
			ob_start();
			?>
			<div class="content_box_wheader" style="padding-bottom: 20px">
				<p>
					<?php _e( 'This ticket is not assigned to anyone.' ); ?><br/>
					<?php _e( 'If you are able to solve this ticket please assign it to yourself.' ); ?>
				</p>
				<input type="button" name="butt_assign_me" value="<?php _e( 'Assign this ticket to me' ); ?>"
				       class="submit_button btn btn-success"
				       onclick="$('#assigned_user_id').val(<?php echo module_security::get_loggedin_id(); ?>); this.form.submit();">
				<p>
					<?php _e( 'If you cannot solve this ticket please assign it to someone else in the drop down list.' ); ?>
				</p>
			</div>
			<?php

			$fieldset_data = array(
				'heading'         => array(
					'title' => _l( 'Unassigned Ticket' ),
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}

		/** TICKET MESSAGES */
		if ( ! $done_messages ) {
			$tickets_in_reverse = module_config::c( 'ticket_messages_in_reverse', 0 );
			include module_theme::include_ucm( 'includes/plugin_ticket/pages/ticket_admin_edit_messages.php' );
		}

		hook_handle_callback( 'layout_column_half', 'end' );
		echo $action_buttons;
		?>

	</form>

<?php

if ( ( $last_response_from == 'customer' || $last_response_from == 'autoreply' ) && $ticket['status_id'] != _TICKET_STATUS_RESOLVED_ID ) { // don't do this for resolved tickets
	// only set the default field if the last respose was from the customer.
	module_form::set_default_field( 'new_ticket_message' );
}

?>