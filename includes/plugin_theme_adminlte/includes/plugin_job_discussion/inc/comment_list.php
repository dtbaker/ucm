<?php

// Created by MannyE W3Corner.com

if ( module_config::c( 'job_discussion_adminlte_modal', 1 ) ) {
	// use new modal layout from w3corner
} else {
	// use default layout
	include 'includes/plugin_job_discussion/inc/comment_list.php';
	exit;
}
if ( ! isset( $comments ) ) {
	die( 'Wrong file' );
}
$comments = array_reverse( $comments, true );
?>

<div class="ResponsiveModal modal fade" id="job_discussion_<?php echo $job_id . '_' . $task_id ?>"
     data-jobid="<?php echo $job_id; ?>" data-taskid="<?php echo $task_id; ?>">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
						aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo $job_data['name']; ?>
					<small><?php _e( 'Discussions on %s', '<strong>' . htmlspecialchars( $task_data['description'] ) . '</strong>' ); ?></small>
				</h4>
			</div>
			<div class="modal-body">
				<?php

				if ( $job_data && isset( $job_data['job_discussion'] ) && $job_data['job_discussion'] == 2 ) {
					// disabled & shown.
					//return;
				} else {
					if ( $allow_new ) {
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
						?>
						<div class="panel panel-default">
							<div class="panel-heading">
								<h3
									class="panel-title"><?php echo $current_user_id ? module_user::link_open( $current_user_id, true, array(), true ) : 'Unknown'; ?>
									<small><?php echo print_date( time(), true ); ?></small>
								</h3>
							</div>
							<div class="panel-body">
								<div class="form-group">
									<textarea id="comment_<?php echo $job_id . '_' . $task_id; ?>" class="form-control" rows="4" cols="30"
									          name="new_comment" placeholder="Enter Comment"></textarea> <br/>
									<input type="hidden" name="discussion_job_id" value="<?php echo $job_id; ?>">
									<input type="hidden" name="discussion_task_id" value="<?php echo $task_id; ?>">
								</div>

								<?php
								if ( ! module_security::is_logged_in() ) {
									echo '<div class="hidden">';
								}
								?>
								<div class="col-sm-12" id="send_customer_list">
									<?php
									foreach ( $send_to_customer_ids as $user_id => $tf ) {
										// we are the admin, sending an email to customer
										?>
										<div class="form-group">
											<div class=" col-sm-10">
												<div class="checkbox">
													<label>
														<input type="checkbox" name="sendemail_customer[]"
														       value="<?php echo $user_id; ?>" <?php echo module_config::c( 'job_discussion_customer_checked', 1 ) && $user_id == $customer['primary_user_id'] ? 'checked="checked"' : ''; ?>
														       class="sendemail_customer">
														<?php _e( 'Yes, send email to customer contact %s', module_user::link_open( $user_id, true, array(), true ) ); ?> <?php echo $user_id == $customer['primary_user_id'] ? _l( '(primary)' ) : ''; ?>
													</label>
												</div>
											</div>
										</div>
										<?php
									}
									?>
								</div>
								<div class="col-sm-12" id="send_staff_list">
									<?php
									foreach ( $send_to_staff_ids as $staff_id => $checked ) {
										// we are the admin, sending an email to assigned staff member
										?>
										<div class="form-group">
											<div class="col-sm-10">
												<div class="checkbox">
													<label>
														<input type="checkbox" name="sendemail_staff[]"
														       value="<?php echo $staff_id; ?>" <?php echo $checked ? 'checked="checked"' : ''; ?>
														       class="sendemail_staff">
														<?php _e( 'Yes, send email to staff %s', module_user::link_open( $staff_id, true, array(), true ) ); ?>
													</label>
												</div>
											</div>
										</div>
										<?php
									}
									?>
								</div>
								<?php
								if ( ! module_security::is_logged_in() ) {
									echo '</div>';
								}

								?>
								<div class="pull-right">
									<input type="button" name="add" value="<?php _e( 'Add Comment' ); ?>"
									       class="btn btn-primary btn-sm task_job_discussion_add_adminlte small_button"
									       data-jobid="<?php echo $job_id; ?>" data-taskid="<?php echo $task_id; ?>">
								</div>
							</div>
						</div>
						<?php
					}
				}
				$x = 0;
				foreach ( $comments as $comment ) {
					?>
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3
								class="panel-title"><?php echo $comment['user_id'] ? module_user::link_open( $comment['user_id'], true, array(), true ) : 'Unknown'; ?>
								<small><?php echo print_date( $comment['date_created'], true ); ?></small>
							</h3>
						</div>
						<div class="panel-body">
							<?php echo forum_text( $comment['note'] ); ?>
						</div>
					</div>
					<?php
					$x ++;
				}
				?>
			</div>
			<div class="modal-footer">
				<?php
				if ( $x > 5 ) {
					?>
					<a href="#comment_<?php echo $job_id . '_' . $task_id; ?>"
					   class="btn btn-warning btn-xs"><?php _e( 'Jump To Comment' ); ?></a>
					<?php
				}
				?>
				<button type="button" class="btn btn-default" data-dismiss="modal"
				        id="job_discussion_<?php echo $job_id . '_' . $task_id ?>_close"><?php _e( 'Close' ); ?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->