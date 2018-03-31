<?php

if ( ! $note_edit_safe ) {
	die( 'failed' );
}


?>

<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
	<tbody>
	<tr>
		<th class="width1">
			<?php echo _l( 'Date' ); ?>
		</th>
		<td>
			<input type="text" name="note_time" id="form_note_time" class="date_time_field"
			       value="<?php echo ! empty( $note['note_time'] ) ? $note['note_time'] : 0; ?>"/>
		</td>
	</tr>
	<?php if ( module_config::c( 'allow_note_reminders', 1 ) ) { ?>
		<tr>
			<th>
				<?php echo _l( 'Reminder' ); ?>
			</th>
			<td>
				<input type="checkbox" name="reminder" id="form_reminder"
				       value="1" <?php echo $note['reminder'] ? ' checked' : ''; ?> />
				<?php _e( 'for' ); ?>
				<?php
				// we use the same staff listing we have in jobs.
				$staff_members    = module_user::get_staff_members();
				$staff_member_rel = array();
				foreach ( $staff_members as $staff_member ) {
					$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
				}
				echo print_select_box( $staff_member_rel, 'user_id', $note['user_id'], 'form_user_id', _l( 'All Staff' ) );
				?>
				<?php _h( 'Sets a dashboard reminder for the above date. This will appear on the selected users dashboard.<br><br>Untick to remove reminder.<br><br>Staff are users who have Job Task EDIT permissions.' ); ?>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<th>
			<?php echo _l( 'Note' ); ?>
		</th>
		<td>
			<textarea rows="5" cols="40" name="note"
			          id="form_note_data"><?php echo htmlspecialchars( $note['note'] ); ?></textarea>
		</td>
	</tr>
	<?php
	// support for the 'public' flag on notes. at the moment this is only used in invoices.
	// later on we could mark notes as public so the customer can see them when they log in, but not see other non-public ones.
	if ( ! empty( $options['public'] ) && ! empty( $_SESSION['note_public'] ) ) {
		$options['public'] = $_SESSION['note_public'];
	}
	if ( isset( $options['public'] ) && is_array( $options['public'] ) && isset( $options['public']['enabled'] ) && $options['public']['enabled'] ) {
		?>
		<tr>
			<th>
				<?php if ( isset( $options['public']['title'] ) && $options['public']['title'] ) {
					echo $options['public']['title'];
				} ?>
			</th>
			<td>
				<input type="hidden" name="public_chk" value="1">
				<input type="checkbox" name="public" id="form_public"
				       value="1" <?php echo $note['public'] ? ' checked' : ''; ?> />
				<?php
				if ( isset( $options['public']['text'] ) && $options['public']['text'] ) {
					echo $options['public']['text'];
				}
				if ( isset( $options['public']['help'] ) && $options['public']['help'] ) {
					_h( $options['public']['help'] );
				}
				?>
			</td>
		</tr>
		<?php
	}
	?>
	<?php if ( $note_id && $note_id != 'new' ) { ?>
		<tr>
			<th>
				<?php echo _l( 'Creator' ); ?>
			</th>
			<td>
				<?php $user_data = module_user::get_user( $note['create_user_id'] );
				echo $user_data['name'];
				echo ' on ';
				echo print_date( $note['date_created'], true );
				?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Updated' ); ?>
			</th>
			<td>
				<?php
				if ( $note['update_user_id'] ) {
					$user_data = module_user::get_user( $note['update_user_id'] );
					echo $user_data['name'];
					echo ' on ';
					echo print_date( $note['date_updated'], true );
				} else {
					echo 'never';
				}
				?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>

