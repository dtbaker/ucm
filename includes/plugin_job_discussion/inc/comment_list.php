<?php

if ( ! isset( $comments ) ) {
	die( 'Wrong file' );
}

foreach ( $comments as $comment ) {
	?>
	<div class="task_job_discussion_comments">
		<div class="info">
			<?php echo $comment['user_id'] ? module_user::link_open( $comment['user_id'], true, array(), true ) : 'Unknown'; ?>
			<?php echo print_date( $comment['date_created'], true ); ?>
		</div>
		<?php echo forum_text( $comment['note'] ); ?>
	</div>
	<?php
}
if ( $job_data && isset( $job_data['job_discussion'] ) && $job_data['job_discussion'] == 2 ) {
	// disabled & shown.
	return;
}
if ( $allow_new ) {
	?>
	<div class="task_job_discussion_comments">
		<div class="info">
			<?php echo $current_user_id ? module_user::link_open( $current_user_id, true, array(), true ) : 'Unknown'; ?>
			<?php echo print_date( time(), true ); ?>
		</div>
		<textarea rows="4" cols="30" name="new_comment"></textarea> <br/>
		<input type="button" name="add" value="<?php _e( 'Add Comment' ); ?>" class="task_job_discussion_add small_button"
		       data-jobid="<?php echo $job_id; ?>" data-taskid="<?php echo $task_id; ?>">
		<?php

		$send_to_customer_ids = array();
		$send_to_staff_ids    = array();
		if ( module_security::get_loggedin_id() && $job_data['customer_id'] && $customer['primary_user_id'] && $customer['primary_user_id'] != $current_user_id ) {
			$send_to_customer_ids[ $customer['primary_user_id'] ] = 1;
			// put the other customer contacts in here too.
			$customer_contacts = module_user::get_contacts( array( 'customer_id' => $job_data['customer_id'] ) );
			foreach ( $customer_contacts as $contact ) {
				$send_to_customer_ids[ $contact['user_id'] ] = 1;
			}
		}
		if ( $job_data['user_id'] && $job_data['user_id'] != $current_user_id && $job_data['user_id'] != $customer['primary_user_id'] ) {
			$send_to_staff_ids[ $job_data['user_id'] ] = module_config::c( 'job_discussion_staff_checked', 1 );
		}
		if ( $task_data['user_id'] && $task_data['user_id'] != $current_user_id && $task_data['user_id'] != $customer['primary_user_id'] ) {
			$send_to_staff_ids[ $task_data['user_id'] ] = module_config::c( 'job_discussion_staff_checked', 1 );
		}

		if ( ! module_security::is_logged_in() ) {
			echo '<div style="display:none;">';
		}
		foreach ( $send_to_customer_ids as $user_id => $tf ) {
			// we are the admin, sending an email to customer
			?>
			<br/>
			<input type="checkbox" name="sendemail_customer[]"
			       value="<?php echo $user_id; ?>" <?php echo module_config::c( 'job_discussion_customer_checked', 1 ) && $user_id == $customer['primary_user_id'] ? 'checked="checked"' : ''; ?>
			       class="sendemail_customer"> <?php _e( 'Yes, send email to customer contact %s', module_user::link_open( $user_id, true, array(), true ) ); ?>
			<?php echo $user_id == $customer['primary_user_id'] ? _l( '(primary)' ) : ''; ?>
			<?php
		}

		foreach ( $send_to_staff_ids as $staff_id => $checked ) {
			// we are the admin, sending an email to assigned staff member
			?>
			<br/>
			<input type="checkbox" name="sendemail_staff[]"
			       value="<?php echo $staff_id; ?>" <?php echo $checked ? 'checked="checked"' : ''; ?>
			       class="sendemail_staff"> <?php _e( 'Yes, send email to staff %s', module_user::link_open( $staff_id, true, array(), true ) ); ?>
			<?php
		}
		if ( ! module_security::is_logged_in() ) {
			echo '</div>';
		}

		?>
	</div>
	<?php
}