<?php

$module->page_title = _l( 'Member Statistics' );

$member_id            = isset( $_REQUEST['member_id'] ) ? (int) $_REQUEST['member_id'] : false;
$newsletter_member_id = isset( $_REQUEST['newsletter_member_id'] ) ? (int) $_REQUEST['newsletter_member_id'] : false;
if ( $member_id ) {
	$member_data = module_newsletter::get_member( $member_id );
} else if ( $newsletter_member_id ) {
	$member_data = module_newsletter::get_newsletter_member( $newsletter_member_id );
} else {
	set_error( 'Sorry no member id specified' );
	redirect_browser( module_member::link_list( 0 ) );
}

// quick hack to save settings.
$redirect = false;
foreach ( $member_data as $member_d ) {
	if ( isset( $_REQUEST['receive_email'] ) && $member_d['newsletter_member_id'] && isset( $_REQUEST['receive_email'][ $member_d['newsletter_member_id'] ] ) ) {
		module_newsletter::save_member( $member_d['newsletter_member_id'], array( 'receive_email' => (int) $_REQUEST['receive_email'][ $member_d['newsletter_member_id'] ] ) );
		$redirect = true;
	}
}
if ( $redirect ) {
	redirect_browser( $_SERVER['REQUEST_URI'] );
}

print_heading( 'Member Newsletter History/Statistics' );

?>

<table width="100%" class="tableclass tableclass_full">
	<tbody>
	<tr>
		<td width="70%" valign="top">
			<?php print_heading( array(
				'type'  => 'h3',
				'title' => 'Newsletters Sent to this Member',
			) ); ?>
			<table class="tableclass tableclass_rows tableclass_full">
				<thead>
				<tr>
					<th><?php _e( 'Newsletter Subject' ); ?></th>
					<th><?php _e( 'Sent Date' ); ?></th>
					<th><?php _e( 'Opened' ); ?></th>
					<th><?php _e( 'Bounced' ); ?></th>
					<th><?php _e( 'Status' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php
				// list all members from thsi send.
				$send_members       = module_newsletter::get_member_sends( $member_id );
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
						<td>
							<?php echo module_newsletter::link_statistics( $send_member['newsletter_id'], $send_member['send_id'], true, $send_member ); ?>
						</td>
						<td
							class="sent_time"><?php echo $send_member['sent_time'] ? print_date( $send_member['sent_time'], true ) : _l( 'Not Yet' ); ?></td>
						<td><?php echo $send_member['open_time'] ? print_date( $send_member['open_time'], true ) : _l( 'Not Yet' ); ?></td>
						<td><?php echo $send_member['bounce_time'] ? print_date( $send_member['bounce_time'], true ) : _l( 'No' ); ?></td>
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
			<?php
			// show any probmeatic ones here (blocked due to bounce limit reached, or unsubscribed, or wont receive again due to previous receive.
			?>
		</td>
		<td width="50%" valign="top">

			<form action="" method="post">
				<?php
				foreach ( $member_data

				as $member ){
				print_heading( array(
					'type'  => 'h3',
					'title' => 'Member Statistics',
				) ); ?>
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1"><?php _e( 'Email' ); ?></th>
						<td>
							<?php echo htmlspecialchars( $member['email'] ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Receive Email' ); ?></th>
						<td>
							<?php echo print_select_box( get_yes_no(), 'receive_email[' . $member['newsletter_member_id'] . ']', isset( $member['receive_email'] ) ? $member['receive_email'] : 1, '', false ); ?>
							<input type="submit" name="save" value="<?php _e( 'Save' ); ?>" class="submit_button">
						</td>
					</tr>
					<tr>
						<th class="width1"><?php _e( 'Member Subscribed' ); ?></th>
						<td>
							<?php echo print_date( $member['join_date'] ); ?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Member Unsubscribed' ); ?></th>
						<td>
							<?php
							$newsletter_member_id = module_newsletter::member_from_email( $member, false );
							if ( $newsletter_member_id ) {
								if ( $res = module_newsletter::is_member_unsubscribed( $newsletter_member_id, $member ) ) {
									if ( isset( $res['unsubscribe_send_id'] ) && $res['unsubscribe_send_id'] ) {
										// they unsubscribed from a send.
										$send_data = module_newsletter::get_send( $res['unsubscribe_send_id'] );
										_e( 'Unsubscribed on %s from newsletter %s', print_date( $res['time'] ), module_newsletter::link_statistics( $send_data['newsletter_id'], $send_data['send_id'], true ) );
									} else if ( isset( $res['reason'] ) && $res['reason'] == 'no_email' ) {
										_e( 'Manually marked as no receive email' );
									} else if ( isset( $res['reason'] ) && $res['reason'] == 'doubleoptin' ) {
										_e( 'Waiting for double opt in confirmation, sent at %s', print_date( $res['time'] ) );
									} else {
										_e( 'Unsubscribed on %s', print_date( $res['time'] ) );
									}
								}
							}
							/*$unsub = module_newsletter::email_blacklisted($member['email']); echo print_date($unsub['time']);
							echo $unsub['reason'];*/
							?>
						</td>
					</tr>
					<tr>
						<th><?php _e( 'Total opened' ); ?></th>
						<td>
							<?php echo _l( '%s of %s', $open_count, $send_members_count ); ?>
						</td>
					</tr>
					</tbody>
				</table>
			</form>
			<?php } ?>
		</td>
	</tr>
	</tbody>
</table>