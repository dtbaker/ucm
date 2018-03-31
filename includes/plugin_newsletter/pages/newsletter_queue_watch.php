<?php

$retry_failures = isset( $_REQUEST['retry_failures'] );
$retry_pending  = isset( $_REQUEST['retry_pending'] );

$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
if ( ! $newsletter_id ) {
	set_error( 'Sorry no newsletter id specified' );
	redirect_browser( module_newsletter::link_list( 0 ) );
}
$newsletter = module_newsletter::get_newsletter( $newsletter_id );
// great a new blank send table ready to go (only if user clicks confirm)
$send_id = isset( $_REQUEST['send_id'] ) ? (int) $_REQUEST['send_id'] : false;
if ( ! $send_id ) {
	set_error( 'Sorry no newsletter send id specified' );
	redirect_browser( module_newsletter::link_open( $newsletter_id ) );
}
$send = module_newsletter::get_send( $send_id );

if ( isset( $statistics ) && $statistics ) {
	if ( $send['status'] != _NEWSLETTER_STATUS_SENT ) {
		// hasnt sent yet, redirect to the pending watch page.
		redirect_browser( module_newsletter::link_queue_watch( $newsletter_id, $send_id ) );
	}
	$module->page_title = _l( 'Statistics' );
	print_heading( _l( 'Newsletter Statistics: %s', $newsletter['subject'] ) );
} else {
	$statistics = false;
	if ( $send['status'] == _NEWSLETTER_STATUS_SENT && ! $retry_failures ) {
		// all sent, redirect to the statistics page.
		redirect_browser( module_newsletter::link_statistics( $newsletter_id, $send_id ) );
	}
	$module->page_title = _l( 'Sending' );
	print_heading( _l( 'Sending Newsletter: %s', $newsletter['subject'] ) );
}
if ( $retry_failures && $send['total_fail_count'] <= 0 ) {
	$retry_failures = false;
}
$start_time = $send['start_time'];
?>

<form action="" method="post">
	<input type="hidden" name="newsletter_id" value="<?php echo (int) $newsletter_id; ?>">
	<input type="hidden" name="send_id" value="<?php echo (int) $send_id; ?>">
	<input type="hidden" name="_process" value="modify_send">


	<table width="100%" class="tableclass tableclass_full">
		<tbody>
		<tr>
			<td width="70%" valign="top">
				<?php print_heading( array(
					'type'   => 'h3',
					'title'  => 'Recipient Status (refresh to see updated status)',
					'button' => array(
						'url'   => htmlspecialchars( $_SERVER['REQUEST_URI'] ),
						'id'    => 'refresh',
						'title' => 'Refresh',
					),
				) ); ?>
				<div class="content_box_wheader">
					<table class="tableclass tableclass_rows tableclass_full">
						<thead>
						<tr>
							<th><?php _e( 'Company Name' ); ?></th>
							<th><?php _e( 'Name' ); ?></th>
							<th><?php _e( 'Email' ); ?></th>
							<th><?php _e( 'Sent' ); ?></th>
							<th><?php _e( 'Viewed' ); ?></th>
							<th><?php _e( 'Clicks' ); ?></th>
							<th><?php _e( 'Bounced' ); ?></th>
							<th><?php _e( 'Unsubscribed' ); ?></th>
							<th>&nbsp;</th>
						</tr>
						</thead>
						<tbody>
						<?php
						// list all members from thsi send.
						$send_members       = module_newsletter::get_send_members( $send_id, $statistics );
						$open_count         = 0;
						$x                  = 0;
						$send_members_count = mysqli_num_rows( $send_members );
						$sent_to_members    = 0;
						while ( $send_member = mysqli_fetch_assoc( $send_members ) ) {
							if ( $send_member['open_time'] ) {
								$open_count ++;
							}
							?>
							<tr class="<?php echo $x ++ % 2 ? 'odd' : 'even'; ?>"
							    id="newsletter_member_<?php echo $send_member['newsletter_member_id']; ?>">
								<td><?php echo htmlspecialchars( $send_member['company_name'] ); ?></td>
								<td><?php echo htmlspecialchars( $send_member['first_name'] . ' ' . $send_member['last_name'] ); ?></td>
								<td><?php echo htmlspecialchars( $send_member['email'] ); ?></td>
								<td
									class="sent_time"><?php echo $send_member['sent_time'] ? print_date( $send_member['sent_time'], true ) : _l( 'Not Yet' ); ?></td>
								<td><?php echo $send_member['open_time'] ? print_date( $send_member['open_time'], true ) : _l( 'Not Yet' ); ?></td>
								<td><?php echo $send_member['links_clicked']; ?></td>
								<td><?php echo $send_member['bounce_time'] ? print_date( $send_member['bounce_time'], true ) : _l( 'No' ); ?></td>
								<td><?php

									if ( module_config::c( 'newsletter_doubleoptin_bypass', 0 ) && isset( $send_member['blacklist_reason'] ) && $send_member['blacklist_reason'] == 'doubleoptin' ) {
										_e( 'No' );
									} else if ( $send_member['unsubscribe_time'] ) {
										echo print_date( $send_member['unsubscribe_time'], true );
									} else if ( $send_member['unsubscribe_time2'] ) {
										echo print_date( $send_member['unsubscribe_time2'], true );
									} else {
										_e( 'No' );
									}
									?></td>
								<td class="status">
									<?php
									switch ( $send_member['status'] ) {
										case _NEWSLETTER_STATUS_NEW:
											// hasnt been processed yet
											break;
										case _NEWSLETTER_STATUS_SENT;
											// sent!
											_e( 'sent' );
											break;
										case _NEWSLETTER_STATUS_PENDING;
										case _NEWSLETTER_STATUS_PAUSED;
											// pending send..
											_e( 'pending' );
											break;
										case _NEWSLETTER_STATUS_FAILED:
											_e( 'failed' );
											break;
										default:
											echo '?';
									} ?>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
				// show any probmeatic ones here (blocked due to bounce limit reached, or unsubscribed, or wont receive again due to previous receive.
				?>

				<?php
				/*$problem_members = module_newsletter::get_problem_members($send_id);
				if(mysqli_num_rows($problem_members) > 0){
						print_heading(array(
								'type'=>'h3',
								'title'=>'Problem Members',
						)); ?>
						<table class="tableclass tableclass_rows tableclass_full">
								<thead>
								<tr>
										<th><?php _e('Company Name');?></th>
										<th><?php _e('Name');?></th>
										<th><?php _e('Email');?></th>
										<th><?php _e('Sent');?></th>
										<th><?php _e('Opened');?></th>
										<th><?php _e('Bounced');?></th>
										<th><?php _e('Unsubscribed');?></th>
										<th>&nbsp;</th>
								</tr>
								</thead>
								<tbody>
								<?php
								// list all members from thsi send.
								while($send_member = mysqli_fetch_assoc($problem_members)){
										if($send_member['open_time']){
												$open_count++;
										}
										?>
								<tr class="<?php echo $x++%2?'odd':'even';?>" id="newsletter_member_<?php echo $send_member['newsletter_member_id'];?>">
										<td><?php echo htmlspecialchars($send_member['company_name']);?></td>
										<td><?php echo htmlspecialchars($send_member['first_name'] . ' ' . $send_member['last_name']);?></td>
										<td><?php echo htmlspecialchars($send_member['email']);?></td>
										<td class="sent_time"><?php echo $send_member['sent_time'] ? print_date($send_member['sent_time'],true) : _l('Not Yet');?></td>
										<td><?php echo $send_member['open_time'] ? print_date($send_member['open_time'],true) : _l('Not Yet');?></td>
										<td><?php echo $send_member['bounce_time'] ? print_date($send_member['bounce_time'],true) : _l('No');?></td>
										<td><?php
												if($send_member['unsubscribe_time']){
														echo print_date($send_member['unsubscribe_time'],true);
												}else if($send_member['unsubscribe_time2']){
														echo print_date($send_member['unsubscribe_time2'],true);
												}else{
														echo _l('No');
												}
												?></td>
										<td class="status">
												<?php
												switch($send_member['status']){
														case _NEWSLETTER_STATUS_NEW:
																// hasnt been processed yet
																break;
														case _NEWSLETTER_STATUS_SENT;
																// sent!
																_e('sent');
																break;
														case _NEWSLETTER_STATUS_PENDING;
														case _NEWSLETTER_STATUS_PAUSED;
																// pending send..
																_e('pending');
																break;
														case _NEWSLETTER_STATUS_FAILED:
																_e('failed');
																break;
														default:
																echo '?';
												}?>
										</td>
								</tr>
										<?php
								}
								?>
								</tbody>
						</table>
				<?php }*/ ?>
			</td>
			<td width="30%" valign="top">
				<?php print_heading( array(
					'type'  => 'h3',
					'title' => 'Send Statistics',
				) ); ?>
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1"><?php _e( 'Start sending' ); ?></th>
						<td>
							<?php if ( $start_time <= time() ) {
								_e( 'Now' );
							} else {
								echo print_date( $start_time, true );
							} ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Sent To' ); ?></th>
						<td id="sent_to">
							<?php echo _l( '%s of %s', (int) $send['total_sent_count'], (int) $send['total_member_count'] ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Viewed By' ); ?></th>
						<td id="open_rate">
							<?php echo _l( '%s (%s%%)', (int) $send['total_open_count'], round( ( $send['total_open_count'] / $send['total_member_count'] ) * 100 ) ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Clicks By' ); ?></th>
						<td id="total_link_clicks">
							<?php echo _l( '%s (%s%%)', (int) $send['total_link_clicks'], round( ( $send['total_link_clicks'] / $send['total_member_count'] ) * 100 ) ); ?>
							<?php if ( $statistics ) { ?>
								<a
									href="<?php echo module_newsletter::link_statistics_link_clicks( $newsletter_id, $send_id, false, $send ); ?>"><?php _e( 'View Link Clicks' ); ?></a>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Bounces' ); ?></th>
						<td id="bounces">
							<?php echo _l( '%s (%s%%)', (int) $send['total_bounce_count'], round( ( $send['total_bounce_count'] / $send['total_member_count'] ) * 100 ) ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Failures' ); ?></th>
						<td id="failures">
							<?php echo _l( '%s (%s%%)', (int) $send['total_fail_count'], round( ( $send['total_fail_count'] / $send['total_member_count'] ) * 100 ) ); ?>
							<?php if ( isset( $statistics ) && $statistics && (int) $send['total_fail_count'] > 0 && ! isset( $_REQUEST['retry_failures'] ) ) { ?>
								<a
									href="<?php echo module_newsletter::link_queue_watch( $newsletter_id, $send_id ); ?>&retry_failures=1"><?php _e( 'Retry failed emails' ); ?></a>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Unsubscribed' ); ?></th>
						<td id="unsubscribed">
							<?php echo _l( '%s (%s%%)', (int) $send['total_unsubscribe_count'], round( ( $send['total_unsubscribe_count'] / $send['total_member_count'] ) * 100 ) ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Current status' ); ?></th>
						<td id="current_status">
							<?php
							switch ( $send['status'] ) {
								case _NEWSLETTER_STATUS_NEW:
									_e( 'Not started' );
									break;
								case _NEWSLETTER_STATUS_PAUSED:
									_e( 'Paused' );
									?>
									<br>
									<input type="button" name="delete" value="<?php echo _l( 'Delete Send' ); ?>"
									       class="submit_button delete_button"
									       onclick="$('#status').val('delete'); $(this).parents('form')[0].submit();">
									<?php
									break;
								case _NEWSLETTER_STATUS_PENDING:
									_e( 'Currently sending (waiting from CRON job)' );
									break;
								case _NEWSLETTER_STATUS_SENT:
									_e( 'All sent' );
									break;
								default:
									_e( 'Unknown??' );
							}
							?>
						</td>
					</tr>
					<tr id="change_status_buttons">
						<td colspan="2" align="center">
							<input type="hidden" name="status" id="status" value="<?php echo (int) $send['status']; ?>">
							<?php if ( $send['status'] == _NEWSLETTER_STATUS_PENDING ) {
								// sending is in progress... offer a pause button.
								?>
								<input type="button" name="cancel" value="<?php echo _l( 'Pause Sending' ); ?>" class="submit_button"
								       onclick="$('#status').val(<?php echo _NEWSLETTER_STATUS_PAUSED; ?>); $(this).parents('form')[0].submit();">
							<?php } else if ( $send['status'] == _NEWSLETTER_STATUS_NEW ) {
								// sending hasn't started yet - offer a start button.
								?>
								<input type="button" name="resume" value="<?php echo _l( 'Start Sending' ); ?>" class="submit_button"
								       onclick="$('#status').val(<?php echo _NEWSLETTER_STATUS_PENDING; ?>); $(this).parents('form')[0].submit();">
							<?php } else if ( $send['status'] == _NEWSLETTER_STATUS_PAUSED ) {
								// sending paused.. offer a resume button
								?>
								<input type="button" name="cancel" value="<?php echo _l( 'Resume Sending' ); ?>" class="submit_button"
								       onclick="$('#status').val(<?php echo _NEWSLETTER_STATUS_PENDING; ?>); $(this).parents('form')[0].submit();">
							<?php } else { ?>
								<!-- send complete -->
							<?php } ?>
						</td>
					</tr>
					</tbody>
				</table>

				<div id="manual_send_block"
				     style="<?php echo ( $send['status'] != _NEWSLETTER_STATUS_PENDING && ! $retry_failures ) ? 'display:none;' : ''; ?>">
					<?php print_heading( array(
						'type'  => 'h3',
						'title' => ( $retry_pending ) ? 'Retrying Pending Emails' : ( ( $retry_failures ) ? 'Resending Bounces' : 'Manual Send' ),
					) ); ?>
					<script type="text/javascript">
              var run_manual_send = false;
							<?php if($retry_failures || $retry_pending){ ?>
              $(function () {
                  start_send_manual();
              });
							<?php } ?>
              function cancel_send_manual() {
                  run_manual_send = false;
                  $('#manual_send_running').hide();
                  $('#manual_send_paused').show();
                  $('#manual_send_status').prepend('<li><?php _e( 'Stopped manual send' );?></li>');
              }

              function start_send_manual() {
                  run_manual_send = true;
                  $('#manual_send_paused').hide();
                  $('#manual_send_running').show();
                  $('#manual_send_status').prepend('<li><?php _e( 'Starting manual send' );?></li>');
                  do_manual_send();
              }

              function finished_send_manual() {
                  run_manual_send = false;
                  $('#manual_send_paused').hide();
                  $('#manual_send_running').hide();
                  $('#manual_send_status').prepend('<li><?php _e( 'Sending complete. Please <a href="%s">click here</a> to refresh the page.', module_newsletter::link_queue_watch( $newsletter_id, $send_id ) );?></li>');
                  $('#change_status_buttons').hide();
              }

              function do_manual_send() {
                  if (!run_manual_send) {
                      return;
                  }
                  // do an ajax call to the newsletter_queue_manual.php script
                  // parse the result and update the corresponding recipient in the listing
                  $('#manual_send_status').prepend('<li><?php _e( 'Telling server to send in batches of %s, please wait...', module_config::c( 'newsletter_send_burst_count', 40 ) );?></li>');
                  $.ajax({
                      type: 'POST',
                      url: '<?php echo module_newsletter::link_queue_manual( $newsletter_id, $send_id );?>',
                      data: 'retry_failures=<?php echo $retry_failures ? 'yes' : '';?>&retry_pending=<?php echo $retry_pending ? 'yes' : '';?>',
                      dataType: 'json',
                      success: function (d) {
                          for (var x in d) {
                              if (x == 'timeout') {
                                  if (d[x] > 0) {
                                      setTimeout(function () {
                                          do_manual_send();
                                      }, d[x] * 1000);
                                  } else {
                                      // not used any more.
                                  }
                              } else if (x == 'messages') {
                                  for (var i in d.messages) {
                                      $('#manual_send_status').prepend('<li>' + d.messages[i] + '</li>');
                                  }
                              } else if (x == 'update_members') {
                                  for (var m in d.update_members) {
                                      for (var mm in d.update_members[m]) {
                                          $('#newsletter_member_' + m + ' ' + mm).html(d.update_members[m][mm]);
                                          //$('#newsletter_member_'+m+' '+mm).highlight();
                                      }
                                  }
                              } else if (x == 'done') {
                                  // send is finished..
                                  finished_send_manual();
                              } else {
                                  $(x).html(d[x]);
                              }
                          }
                      }
                  });
              }
					</script>
					<div class="tableclass tableclass_form" style="padding:5px;">
						<p id="manual_send_paused">
							<?php _e( '<a href="%s" class="uibutton">Start Sending Now</a><br/>Click the above button to start sending now (rather than wait for it to be processed in the background by the CRON job).', 'javascript:start_send_manual();' ); ?>
						</p>
						<div id="manual_send_running" style="display:none">
							<div style="text-align: center">
								<img src="<?php echo _BASE_HREF; ?>images/loadingAnimation.gif" border="0">
							</div>
							<p>
								<?php _e( 'Manual send running. Progress will be displayed here, please stay on this page until it is complete. <a href="%s">Click here</a> to stop manual sending.', 'javascript:cancel_send_manual();' ); ?>
							</p>
						</div>
						<ul id="manual_send_status" style="max-heigh:700px; overflow-y: auto;">

						</ul>
					</div>
				</div>
			</td>
		</tr>
		<tr>
		</tbody>
	</table>

</form>