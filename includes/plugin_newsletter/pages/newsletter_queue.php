<?php
$module->page_title = _l( 'Sending' );

$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
if ( ! $newsletter_id ) {
	set_error( 'Sorry no newsletter id specified' );
	redirect_browser( module_newsletter::link_list( 0 ) );
}
$newsletter = module_newsletter::get_newsletter( $newsletter_id );
$past_sends = $newsletter['sends'];

print_heading( _l( 'Send Newsletter: %s', $newsletter['subject'] ) );

// great a new blank send table ready to go (only if user clicks confirm)
$send_id = isset( $_REQUEST['send_id'] ) ? (int) $_REQUEST['send_id'] : false;
if ( ! $send_id ) {
	set_error( 'Sorry no newsletter send id specified' );
	redirect_browser( module_newsletter::link_open( $newsletter_id ) );
}
foreach ( $past_sends as $key => $val ) {
	if ( $val['status'] == 1 ) {
		// hasn't been sent yet.
		unset( $past_sends[ $key ] );
	}
	if ( $val['send_id'] == $send_id ) {
		// this one
		unset( $past_sends[ $key ] );
	}
}
$send = module_newsletter::get_send( $send_id );
if ( $send['status'] == _NEWSLETTER_STATUS_PENDING || $send['status'] == _NEWSLETTER_STATUS_PAUSED ) {
	redirect_browser( module_newsletter::link_queue_watch( $newsletter_id, $send_id ) );
}
$start_time = $send['start_time'];
if ( ! $start_time ) {
	$start_time = time();
}


// check if this cache has changed - do a callback again.
// this might slow things down, but meh, best to be sure
// incase someone refreshes or doesn't add new members to the list, we check here.
module_newsletter::update_member_data_for_send( $send_id );
$send_members    = module_newsletter::get_send_members( $send_id );
$recipient_count = mysqli_num_rows( $send_members );
// what other fields are we pulling in here?
// hunt through the recipient listing and find the extra fields.
$extra_fields               = array();
$first_newsletter_member_id = false;
while ( $send_member = mysqli_fetch_assoc( $send_members ) ) {
	if ( ! $first_newsletter_member_id ) {
		$first_newsletter_member_id = $send_member['newsletter_member_id'];
	}
	if ( isset( $send_member['data_cache'] ) && strlen( $send_member['data_cache'] ) > 1 ) {
		$cache = unserialize( $send_member['data_cache'] );
		if ( $cache ) {
			// we have extra fields! woo!
			foreach ( $cache as $key => $val ) {
				if ( strpos( $key, '_id' ) ) {
					continue;
				} // skip ids for now.
				$extra_fields[ $key ] = true;
			}
		}
	}
}
//ksort($extra_fields);
if ( $recipient_count > 0 ) {
	mysqli_data_seek( $send_members, 0 );
}

// which extra fields are we going to display?
if ( isset( $_REQUEST['f'] ) && is_array( $_REQUEST['f'] ) ) {
	$display_extra_fields = array_flip( $_REQUEST['f'] );
} else {
	$display_extra_fields = array();
}

?>


<p><?php _e( 'Please review the send options below and click the Send button when you are happy.' ); ?></p>

<table width="100%" class="tableclass tableclass_full">
	<tbody>
	<tr>
		<td width="70%" valign="top">

			<form action="" method="post">
				<input type="hidden" name="newsletter_id" value="<?php echo (int) $newsletter_id; ?>">
				<input type="hidden" name="send_id" value="<?php echo (int) $send_id; ?>">
				<input type="hidden" name="_process" value="send_send">


				<?php
				$heading = array(
					'type'  => 'h3',
					'title' => _l( 'Review Recipients (%s)', $recipient_count ),
				);
				/*if($extra_fields){
						$heading['button']=array(
								'title' => _l('Preview %s extra dynamic fields',count($extra_fields)),
								'url' => '#',
						);
				}*/
				print_heading( $heading );
				$send_members_pagination = process_pagination( $send_members );
				//echo $send_members_pagination['summary'];
				?>
				<div class="content_box_wheader">
					<table class="tableclass tableclass_rows tableclass_full">
						<thead>
						<tr>
							<th><?php _e( 'Company Name' ); ?></th>
							<th><?php _e( 'First Name' ); ?></th>
							<th><?php _e( 'Last Name' ); ?></th>
							<th><?php _e( 'Email' ); ?></th>
							<?php foreach ( array_keys( $extra_fields ) as $extra_field ) {
								if ( ! isset( $display_extra_fields[ strtoupper( $extra_field ) ] ) ) {
									continue;
								}
								?>
								<th><?php echo htmlspecialchars( $extra_field ); ?></th>
							<?php } ?>
							<th width="20">&nbsp;</th>
						</tr>
						</thead>
						<tbody>
						<?php
						$x = 0;
						// list all members from thsi send.
						foreach ( $send_members_pagination['rows'] as $send_member ) {
							$cache = unserialize( $send_member['data_cache'] );
							?>
							<tr class="<?php echo $x ++ % 2 ? 'odd' : 'even'; ?>">
								<td><?php echo htmlspecialchars( $send_member['company_name'] ); ?></td>
								<td><?php echo htmlspecialchars( $send_member['first_name'] ); ?></td>
								<td><?php echo htmlspecialchars( $send_member['last_name'] ); ?></td>
								<td><?php echo htmlspecialchars( $send_member['email'] ); ?></td>
								<?php
								foreach ( array_keys( $extra_fields ) as $extra_field ) {
									if ( ! isset( $display_extra_fields[ strtoupper( $extra_field ) ] ) ) {
										continue;
									}
									?>
									<td><?php echo isset( $cache[ $extra_field ] ) ? htmlspecialchars( $cache[ $extra_field ] ) : ''; ?></td>
								<?php } ?>
								<td>
									<?php if ( isset( $cache['_edit_link'] ) ) { ?>
										<a href="<?php echo $cache['_edit_link']; ?>" class="" title="<?php _e( 'Edit' ); ?>"
										   target="_blank"><i class="fa fa-pencil"></i></a>
									<?php } ?>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
					<?php echo $send_members_pagination['links']; ?>
				</div>

				<?php
				// show any probmeatic ones here (blocked due to bounce limit reached, or unsubscribed, or wont receive again due to previous receive.
				$problem_members = module_newsletter::get_problem_members( $send_id );
				if ( mysqli_num_rows( $problem_members ) > 0 ) {
					print_heading( array(
						'type'  => 'h3',
						'title' => _l( 'Will not send to %s recipients', mysqli_num_rows( $problem_members ) ),
					) );
					$problem_members_pagination = process_pagination( $problem_members );
					//echo $problem_members_pagination['summary'];
					?>
					<div class="content_box_wheader">
						<table class="tableclass tableclass_rows tableclass_full">
							<thead>
							<tr>
								<th><?php _e( 'Company Name' ); ?></th>
								<th><?php _e( 'Name' ); ?></th>
								<th><?php _e( 'Email' ); ?></th>
								<th><?php _e( 'Reason' ); ?></th>
							</tr>
							</thead>
							<tbody>
							<?php
							$x = 0;
							// list all members from thsi send.
							foreach ( $problem_members_pagination['rows'] as $problem_member ) {
								?>
								<tr class="<?php echo $x ++ % 2 ? 'odd' : 'even'; ?>">
									<td><?php echo htmlspecialchars( $problem_member['company_name'] ); ?></td>
									<td><?php echo htmlspecialchars( $problem_member['first_name'] . ' ' . $problem_member['last_name'] ); ?></td>
									<td><?php echo htmlspecialchars( $problem_member['email'] ); ?></td>
									<td><?php
										if ( ! $problem_member['receive_email'] ) {
											_e( 'Marked as do-not-email' );
										} else if ( $problem_member['unsubscribe_date'] && $problem_member['unsubscribe_date'] != '0000-00-00' ) {
											echo _l( 'Unsubscribed on %s', print_date( $problem_member['unsubscribe_date'] ) );
										} else if ( $problem_member['bounce_count'] >= module_config::c( 'newsletter_bounce_threshold', 3 ) ) {
											echo _l( 'Bounced %s times', $problem_member['bounce_count'] );
										} else if ( trim( $problem_member['email'] ) == '' ) {
											echo _l( 'No email address' );
										} else if ( isset( $problem_member['reason'] ) && $problem_member['reason'] == 'doubleoptin' ) {
											_e( 'Double opt-in incomplete on %s', print_date( $problem_member['time'] ) );
										} else if ( $problem_member['newsletter_blacklist_id'] ) {
											echo _l( 'Unsubscribed on %s', print_date( $problem_member['unsubscribe_time2'], true ) );
										} else {
											echo _l( 'Other error' );
											print_r( $problem_member );
										}
										?></td>
								</tr>
								<?php
							}
							?>
							</tbody>
						</table>
						<?php echo $problem_members_pagination['links']; ?>
					</div>
				<?php } ?>

				<p align="center">
					<input type="button" name="cancel" value="<?php _e( 'Back / Cancel' ); ?>" class="submit_button"
					       onclick="window.location.href='<?php echo module_newsletter::link_send( $newsletter_id ); ?>';">
					<input type="button" name="cancel" value="<?php _e( 'Add More Recipients' ); ?>" class="submit_button"
					       onclick="window.location.href='<?php echo module_newsletter::link_send( $newsletter_id, false, $send_id ); ?>';">
					<?php if ( $recipient_count > 0 ) { ?>
						<input type="submit" name="send" value="<?php _e( 'Queue Newsletter for Sending' ); ?>"
						       class="save_button submit_button">
					<?php } ?>
				</p>
			</form>

		</td>
		<td width="30%" valign="top">

			<form action="" method="POST">
				<input type="hidden" name="newsletter_id" value="<?php echo $newsletter_id; ?>">
				<input type="hidden" name="send_id" value="<?php echo $send_id; ?>">
				<?php print_heading( array(
					'type'  => 'h3',
					'title' => 'Send Options',
				) ); ?>
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1"><?php _e( 'Shedule send' ); ?></th>
						<td>
							<?php if ( $start_time <= time() ) {
								_e( 'Now' );
							} else {
								echo print_date( $start_time, true );
							} ?>
						</td>
					</tr>
					<?php // has this been sent before?
					if ( $past_sends ) {
						?>
						<tr>
							<th><?php _e( 'Duplicates' ); ?></th>
							<td>
								<?php echo $send['allow_duplicates'] ? _l( 'Yes, send duplicate emails' ) : _l( 'No, do not send duplicate emails' ); ?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>

				<?php
				print_heading( array(
					'type'  => 'h3',
					'title' => 'Preview Dynamic Fields:',
				) );
				?>
				<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tbl_fixed">
					<tbody>
					<?php
					$x      = 0;
					$fields = module_newsletter::get_replace_fields( $newsletter_id, $send_id, $first_newsletter_member_id );
					foreach ( $fields as $key => $val ) {
						?>
						<tr>
							<td width="30">
								<?php
								if ( $x > 4 ) {
									foreach ( array_keys( $extra_fields ) as $extra_field ) {
										if ( strtoupper( $extra_field ) == $key ) { ?>
											<input type="checkbox" name="f[]"
											       value="<?php echo htmlspecialchars( $key ); ?>" <?php echo isset( $display_extra_fields[ $key ] ) ? 'checked' : ''; ?>>
										<?php }
									}
								}
								?>
							</td>
							<td>
								<?php echo '{' . htmlspecialchars( $key ) . '}'; ?>
							</td>
							<td style="word-wrap: break-word;">
								<?php echo htmlspecialchars( ( trim( $val ) ? $val : '' ) ); ?>
							</td>
						</tr>
						<?php
						$x ++;
					} ?>
					<tr>
						<td colspan="3" align="center">
							<input type="submit" name="pp" value="<?php _e( 'Preview' ); ?>">
						</td>
					</tr>
					</tbody>
				</table>
			</form>

		</td>
	</tr>
	</tbody>
</table>
