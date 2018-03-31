<?php
$colspan          = 2;
$hours_prefix     = '';
$show_split_hours = false;

$task_decimal_places = module_config::c( 'task_amount_decimal_places', - 1 );
if ( $task_decimal_places < 0 ) {
	$task_decimal_places = false; // use default currency dec places.
}
$task_decimal_places_trim = module_config::c( 'task_amount_decimal_places_trim', 0 );

if ( module_job::job_task_has_split_hours( $job_id, $job, $task_id, $task_data ) ) {
	if ( $task_data['staff_split'] ) {
		// has saved this task - using database detauls

	} else {
		// use defaults above.
		$task_data['staff_hours']  = $task_data['hours'];
		$task_data['staff_amount'] = $task_data['amount'];

	}
	if ( module_job::can_i( 'view', 'Job Split Pricing' ) ) {
		$show_split_hours = true;
		// do we show the staff_ settings or default them to the job settings?
	} else {
		$hours_prefix = 'staff_';
	}
}
?>
<tr class="task_editting task_row_<?php echo $task_id; ?>">
	<?php if ( $show_task_numbers ) { ?>
		<td valign="top" style="padding:0.3em 0;">
			<input type="text" name="job_task[<?php echo $task_id; ?>][task_order]"
			       value="<?php echo $task_data['task_order']; ?>" size="3" class="edit_task_order">
		</td>
	<?php } ?>
	<td>
		<?php if ( $task_editable && module_job::can_i( 'delete', 'Job Tasks' ) ) { ?>
			<a href="#"
			   onclick="if(confirm('<?php _e( 'Delete Task?' ); ?>')){$(this).parent().find('input').val('<?php echo _TASK_DELETE_KEY; ?>'); $('#job_task_form')[0].submit();} return false;"
			   class="delete" style="display:inline-block; float:right;"><i class="fa fa-trash"></i></a>
		<?php } ?>
		<input type="text" class="edit_task_description" name="job_task[<?php echo $task_id; ?>][description]"
		       value="<?php echo htmlspecialchars( $task_data['description'] ); ?>" id="task_desc_<?php echo $task_id; ?>"
		       tabindex="10"><?php
		if ( class_exists( 'module_product', false ) ) {
			module_product::print_job_task_dropdown( $task_id, $task_data );
		} ?>
	</td>
	<td class="nowrap">
		<?php
		if ( $task_data[ $hours_prefix . 'hours' ] != 0 ) {
			if ( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_out' ) ) {
				$hours_value = decimal_time_out( $task_data[ $hours_prefix . 'hours' ] );
			} else {
				$hours_value = number_out( $task_data[ $hours_prefix . 'hours' ], true );
			}
		} else {
			$hours_value = false;
		}
		if ( $task_editable ) {

			if ( $task_data['hours'] == 0 && $task_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
				// no hour input
			} else if ( $task_data['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) { ?>
				<input type="text" name="job_task[<?php echo $task_id; ?>][<?php echo $hours_prefix; ?>hours]"
				       value="<?php echo $hours_value; ?>" size="3" style="width:30px;" tabindex="12">
			<?php } else {
				?>
				<input type="text" name="job_task[<?php echo $task_id; ?>][<?php echo $hours_prefix; ?>hours]"
				       value="<?php echo $hours_value; ?>" size="3" style="width:30px;"
				       onchange="setamount(this.value,'<?php echo $task_id; ?>');"
				       onkeyup="setamount(this.value,'<?php echo $task_id; ?>');" tabindex="12">
				<?php
			}
		} else {
			if ( $task_data[ $hours_prefix . 'hours' ] == 0 && $task_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
				// no hour input
			} else {
				echo $hours_value;
			}
		}
		if ( ! empty( $task_data['unitname'] ) && ! empty( $task_data['unitname_show'] ) ) {
			echo ' ' . $task_data['unitname'];
		}
		?>
	</td>
	<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
		<td nowrap="">
			<?php if ( $task_editable ) { ?>
				<?php echo currency( '<input type="text" name="job_task[' . $task_id . '][' . $hours_prefix . 'amount]" value="' . ( $task_data[ $hours_prefix . 'amount' ] != 0 ? number_out( $task_data[ $hours_prefix . 'amount' ], $task_decimal_places_trim, $task_decimal_places ) : number_out( $task_data[ $hours_prefix . 'hours' ] * $job[ $hours_prefix . 'hourly_rate' ], $task_decimal_places_trim, $task_decimal_places ) ) . '" id="' . $task_id . 'taskamount" class="currency" tabindex="13">' ); ?>
			<?php } else { ?>
				<?php echo $task_data[ $hours_prefix . 'amount' ] != 0 ? dollar( $task_data[ $hours_prefix . 'amount' ], true, $job['currency_id'], $task_decimal_places_trim, $task_decimal_places ) : dollar( $task_data[ $hours_prefix . 'hours' ] * $job[ $hours_prefix . 'hourly_rate' ], true, $job['currency_id'], $task_decimal_places_trim, $task_decimal_places ); ?>
			<?php } ?>
		</td>
	<?php } ?>
	<?php if ( module_config::c( 'job_show_due_date', 1 ) ) {
		$colspan ++; ?>
		<td>
			<input type="text" name="job_task[<?php echo $task_id; ?>][date_due]"
			       value="<?php echo print_date( $task_data['date_due'] ); ?>" class="date_field" tabindex="14">
		</td>
	<?php } ?>
	<?php if ( module_config::c( 'job_show_done_date', 1 ) ) {
		$colspan ++; ?>
		<td>
			<input type="text" name="job_task[<?php echo $task_id; ?>][date_done]"
			       value="<?php echo print_date( $task_data['date_done'] ); ?>" class="date_field" tabindex="14">
		</td>
	<?php } ?>
	<?php if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) {
		$colspan ++; ?>
		<td>
			<?php echo print_select_box( $staff_member_rel, 'job_task[' . $task_id . '][user_id]',
				isset( $staff_member_rel[ $task_data['user_id'] ] ) ? $task_data['user_id'] : false, 'job_task_staff_list', '' ); ?>
		</td>
	<?php } ?>
	<td colspan="2" class="percentage_edit">
		<?php
		// offer up a new way to set a manual task percentage completed.
		//_e('Completed:');
		?>
		<span class="manual_percent_input" style="<?php echo $task_data['manual_percent'] < 0 ? 'display:none;' : ''; ?>">
            <input type="text" name="job_task[<?php echo $task_id; ?>][manual_percent]" style="width:25px;"
                   value="<?php echo $task_data['manual_percent'] >= 0 ? $task_data['manual_percent'] : ''; ?>">%
            </span>
		<?php if ( $task_data['manual_percent'] < 0 ) { // button to show our input ?>
			<a href="#"
			   onclick="$(this).parent().find('.manual_percent_input input').val('<?php echo $percentage * 100; ?>'); $(this).parent().find('.manual_percent_input').show(); $(this).hide(); return false;"><?php echo $percentage * 100; ?>
				%</a>
		<?php } ?>
	</td>
	<?php if ( class_exists( 'module_signature', true ) && module_signature::signature_enabled( $job_id ) ) { ?>
		<td> <?php module_signature::signature_job_task_link( $job_id, $task_id ); ?> </td>
	<?php } ?>
</tr>
<?php if ( $show_split_hours ) { ?>
	<tr class="task_editting task_row_<?php echo $task_id; ?>">
		<td></td>
		<td style="text-align: right">
			<?php _e( 'Staff Settings for: %s', module_user::link_open( $task_data['user_id'], true ) ); ?>
			<input type="hidden" name="job_task[<?php echo $task_id; ?>][staff_split]" value="1">
		</td>
		<td>
			<?php
			if ( $task_data['staff_hours'] != 0 ) {
				if ( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_out' ) ) {
					$hours_value = decimal_time_out( $task_data['staff_hours'] );
				} else {
					$hours_value = number_out( $task_data['staff_hours'], true );
				}
			} else {
				$hours_value = false;
			}
			if ( $task_editable ) {

				if ( $task_data['staff_hours'] == 0 && $task_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
					// no hour input
				} else if ( $task_data['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) { ?>
					<input type="text" name="job_task[<?php echo $task_id; ?>][staff_hours]" value="<?php echo $hours_value; ?>"
					       size="3" style="width:30px;" tabindex="14">
				<?php } else {
					?>
					<input type="text" name="job_task[<?php echo $task_id; ?>][staff_hours]" value="<?php echo $hours_value; ?>"
					       size="3" style="width:30px;" tabindex="14">
					<?php
				} ?>
			<?php } else {
				if ( $task_data['staff_hours'] == 0 && $task_data['manual_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
					// no hour input
				} else {
					echo $hours_value;
				}
			} ?>
		</td>
		<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
			<td nowrap="">
				<?php if ( $task_editable ) { ?>
					<?php echo currency( '<input type="text" name="job_task[' . $task_id . '][staff_amount]" value="' . ( $task_data['staff_amount'] != 0 ? number_out( $task_data['staff_amount'] ) : number_out( $task_data['staff_hours'] * $job['staff_hourly_rate'] ) ) . '" id="' . $task_id . 'staff_taskamount" class="currency" tabindex="15">' ); ?>
				<?php } else { ?>
					<?php echo $task_data['staff_amount'] != 0 ? dollar( $task_data['staff_amount'], true, $job['currency_id'] ) : dollar( $task_data['staff_hours'] * $job['staff_hourly_rate'], true, $job['currency_id'] ); ?>
				<?php } ?>
			</td>
		<?php } ?>
		<td colspan="<?php echo $colspan; ?>">
			<?php _h( 'Here you can set a split amount for this staff member. This is what the staff member will be paid for the task.' ); ?>
		</td>
		<?php if ( class_exists( 'module_signature', true ) && module_signature::signature_enabled( $job_id ) ) { ?>
			<td></td>
		<?php } ?>
	</tr>
<?php } ?>
<tr class="task_editting task_row_<?php echo $task_id; ?>">
	<td></td>
	<td class="task_editting edit_task_long_description_box">
		<div style="position: relative;">
			<?php module_form::generate_form_element( array(
				'type'  => module_config::c( 'long_description_wysiwyg', 1 ) ? 'wysiwyg' : 'textarea',
				'name'  => 'job_task[' . $task_id . '][long_description]',
				'id'    => 'task_long_desc_' . $task_id,
				'class' => 'edit_task_long_description',
				'value' => $task_data['long_description'],
			) );
			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'job_task_after', $task_data['job_id'], $task_data['task_id'], $job, $task_data );
			}

			if ( $job_task_creation_permissions == _JOB_TASK_CREATION_WITHOUT_APPROVAL ) {
				// this user can create tasks without approval, and therefore approve other peoples tasks.
				if ( $task_data['approval_required'] ) { ?>
					<div class="alert alert-info job_task_approval_box" role="alert">
						<p><?php if ( $task_data['approval_required'] == 2 ) {
								_e( 'This task has been rejected already' );
							} else {
								_e( 'This task requires approval:' );
							} ?></p>
						<textarea name="job_task[<?php echo $task_id; ?>][approval_message]"
						          placeholder="<?php _e( 'Message to staff member' ); ?>"></textarea>
						<input type="hidden" name="job_task[<?php echo $task_id; ?>][approval_actioned]" value="0"
						       id="approve_task_action_<?php echo $task_id; ?>">
						<input type="hidden" name="job_task[<?php echo $task_id; ?>][approval_required]" value="1"
						       id="approve_task_<?php echo $task_id; ?>">
						<br/>
						<button name="approve" class="btn btn-xs btn-success"
						        onclick="$('#approve_task_action_<?php echo $task_id; ?>').val(1); $('#approve_task_<?php echo $task_id; ?>').val(0); this.form.submit();"
						        tabindex="19"><?php _e( 'Approve Task' ); ?></button>
						<button name="reject" class="btn btn-xs btn-danger"
						        onclick="$('#approve_task_action_<?php echo $task_id; ?>').val(1); $('#approve_task_<?php echo $task_id; ?>').val(2); this.form.submit();"
						        tabindex="19"><?php _e( 'Reject Task' ); ?></button>
					</div>

				<?php }
			} ?>
		</div>
	</td>
	<td colspan="<?php echo $colspan; ?>" valign="top">
		<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
			<div>
				<?php _e( 'Task Type:' ); ?><?php
				$types       = module_job::get_task_types();
				$types['-1'] = _l( 'Default (%s)', $types[ $job['default_task_type'] ] );
				module_form::generate_form_element( array(
					'type'    => 'select',
					'name'    => 'job_task[' . $task_id . '][manual_task_type]',
					'id'      => 'manual_task_type_' . $task_id,
					'options' => $types,
					'blank'   => false,
					'value'   => $task_data['manual_task_type_real'],
				) );
				?>
			</div>
		<?php } ?>
		<div>
			<?php if (
				( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) &&
				( module_config::c( 'job_task_log_all_hours', 1 ) || $task_data[ $hours_prefix . 'hours' ] != 0 )
			) {
				if ( function_exists( 'decimal_time_out' ) ) {
					$completed_value = decimal_time_out( $task_data['completed'] );
					$hours_value     = decimal_time_out( $task_data[ $hours_prefix . 'hours' ] );
				} else {
					$completed_value = number_out( $task_data['completed'], true );
					$hours_value     = number_out( $task_data[ $hours_prefix . 'hours' ], true );
				}

				?>
				<?php echo _l( '%s of %s hours have been logged:', $completed_value, $hours_value ); ?>
				<input type="hidden" name="job_task[<?php echo $task_id; ?>][completed]"
				       value="<?php echo $task_data['completed']; ?>">
				<br/>
				<?php
				// show a log of any existing hours against this task.
				$task_logs = module_job::get_task_log( $task_id );
				foreach ( $task_logs as $task_log ) {
					if ( function_exists( 'decimal_time_out' ) ) {
						$hours_value = decimal_time_out( $task_log['hours'] );
					} else {
						$hours_value = number_out( $task_log['hours'], true );
					}
					echo _l( '%s hrs <span class="text_shrink">%s</a> - <span class="text_shrink">%s</span>', $hours_value, print_date( $task_log['log_time'], true ), $staff_member_rel[ $task_log['create_user_id'] ] );
					?> <a href="#" class="error_text"
					      onclick="return delete_task_hours(<?php echo $task_id; ?>,<?php echo $task_log['task_log_id']; ?>);">x</a> <?php
					echo '<br/>';
				}
			} ?>
		</div>
		<div>

			<?php if (
				( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) &&
				( module_config::c( 'job_task_log_all_hours', 1 ) || $task_data[ $hours_prefix . 'hours' ] != 0 ) &&
				$task_editable ) { ?>
				<?php _e( 'Log' ); ?>
				<input type="text" name="job_task[<?php echo $task_id; ?>][log_hours]" value="<?php ?>" size="2"
				       style="width:35px"
				       id="complete_<?php echo $task_id; ?>" tabindex="16"> <?php _e( 'hours' ); ?>
			<?php } ?>
		</div>
	</td>
	<td colspan="2" class="edit_task_options">
		<div>
			<?php if ( $task_editable && module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
				<input type="hidden" name="job_task[<?php echo $task_id; ?>][billable_t]" value="1">
				<input type="checkbox" name="job_task[<?php echo $task_id; ?>][billable]" value="1"
				       id="billable_t_<?php echo $task_id; ?>" <?php echo $task_data['billable'] ? ' checked' : ''; ?>
				       tabindex="17"> <label for="billable_t_<?php echo $task_id; ?>"><?php _e( 'Task is billable' ); ?></label>
				<br/>
				<input type="hidden" name="job_task[<?php echo $task_id; ?>][taxable_t]" value="1">
				<input type="checkbox" name="job_task[<?php echo $task_id; ?>][taxable]" value="1"
				       id="taxable_t_<?php echo $task_id; ?>" <?php echo $task_data['taxable'] ? ' checked' : ''; ?>
				       tabindex="17"> <label for="taxable_t_<?php echo $task_id; ?>"><?php _e( 'Task is taxable' ); ?></label>
			<?php } else {
				if ( $task_data['billable'] ) {
					_e( 'Task is billable' );
				} else {
					_e( 'Task not billable' );
				}
				echo '<br/>';
				if ( $task_data['taxable'] ) {
					_e( 'Task is taxable' );
				} else {
					_e( 'Task not taxable' );
				}
			}
			if ( $task_data['invoiced'] && $task_data['invoice_id'] ) {
				echo '<br/>';
				echo _l( 'Invoice %s', module_invoice::link_open( $task_data['invoice_id'], true ) );
			}
			?>
		</div>

		<div>

			<?php
			if ( module_config::c( 'job_task_log_all_hours', 1 ) || $task_data['hours'] <= 0 ) { ?>
				<?php if ( ( ! $task_data['fully_completed'] && $task_data['invoiced'] ) || $task_editable ) { ?>
					<input type="hidden" name="job_task[<?php echo $task_id; ?>][fully_completed_t]" value="1">
					<input type="checkbox" name="job_task[<?php echo $task_id; ?>][fully_completed]" value="1"
					       class="task_completed_checkbox"
					       id="complete_t_<?php echo $task_id; ?>" <?php echo $task_data['fully_completed'] > 0 ? ' checked' : ''; ?>
					       tabindex="18">
					<label for="complete_t_<?php echo $task_id; ?>"
					       id="complete_t_label_<?php echo $task_id; ?>"><?php _e( 'Task completed' ); ?></label>
					<?php if ( module_config::c( 'job_send_task_completion_email_automatically', 0 ) ) { ?>
						<div style="display:none" class="task_email_auto_option">
							<input type="checkbox" name="confirm_job_task_email" value="1"
							       id="confirm_job_task_email<?php echo $task_id; ?>" tabindex="19">
							<label for="confirm_job_task_email<?php echo $task_id; ?>"><?php _e( 'Email Customer' ); ?></label>
						</div>
					<?php }
				} else {
					if ( $task_data['fully_completed'] == 1 ) {
						_e( 'Task completed' );
					} else {
						_e( 'Task not completed' );
					}
				} ?>
			<?php } ?>

		</div>

		<div class="edit_task_button">
			<button type="submit" name="ts" class="save_task small_button btn btn-xs btn-success" tabindex="20"
			        style="float:left;"><?php _e( 'Save' ); ?></button>
			<a href="#" class="delete" style="float:right;" title="<?php _e( 'Cancel' ); ?>"
			   onclick="refresh_task_preview(<?php echo $task_id; ?>,false); return false;"><i class="fa fa-ban"></i></a>
		</div>

	</td>
	<?php if ( class_exists( 'module_signature', true ) && module_signature::signature_enabled( $job_id ) ) { ?>
		<td></td>
	<?php } ?>
</tr>