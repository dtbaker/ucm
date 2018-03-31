<?php

set_time_limit( 0 );

// this page is shown in an iframe to send out a newsletter manually
// if they dont have a cron job setup etc..
ob_end_clean();

$retry_failures = isset( $_REQUEST['retry_failures'] ) && $_REQUEST['retry_failures'] == 'yes';
$retry_pending  = isset( $_REQUEST['retry_pending'] ) && $_REQUEST['retry_pending'] == 'yes';

$output_messages = array();
$update_members  = array();
// ajax update parent.
$result = array();
/*$result = array(
    'messages'=>array('afsadf'),
    '#sent_to'=>'sent to',
    '#open_rate'=>'open rate',
    'update_members'=>array(
        1=>array(
            '.status'=>'new status',
            '.sent_time'=>'new sent time',
        ),
    )
);
echo json_encode($result);
exit;*/


$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
if ( ! $newsletter_id ) {
	$output_messages[] = _l( 'Sorry no newsletter id specified' );
} else {

	$newsletter = module_newsletter::get_newsletter( $newsletter_id );
	// great a new blank send table ready to go (only if user clicks confirm)
	$send_id = isset( $_REQUEST['send_id'] ) ? (int) $_REQUEST['send_id'] : false;
	if ( ! $send_id ) {
		$output_messages[] = _l( 'Sorry no newsletter send id specified' );
	} else {
		$send       = module_newsletter::get_send( $send_id );
		$start_time = $send['start_time'];
		if ( $start_time > time() ) {
			$output_messages[] = _l( 'Sorry not starting send yet until %s (current time is %s)', print_date( $start_time, true ), print_date( time(), true ) );

		} else {

			if ( $retry_failures ) {
				$output_messages[] = _l( 'Retrying the send to the %s failed emails', $send['total_fail_count'] );
			}
			if ( $retry_pending ) {
				$output_messages[] = _l( 'Retrying the send to the pending emails' );
			}

			$newsletter_send_burst_count = module_config::c( 'newsletter_send_burst_count', 40 );
			$newsletter_send_burst_break = module_config::c( 'newsletter_send_burst_break', 2 );
			//$send_this_count = 1; // send 1 at a time manually so we get the status back and can repoert it back to the user via ajax.

			//for($x=0;$x<$newsletter_send_burst_count;$x++){
			$send_result = module_newsletter::process_send( $newsletter_id, $send_id, $newsletter_send_burst_count, $retry_failures, $retry_pending );
			if ( ! isset( $send_result['send_members'] ) || ! count( $send_result['send_members'] ) ) {
				//$output_messages[] = _l('All done');
				//break;
			} else {
				foreach ( $send_result['send_members'] as $send_member_result ) {
					$update_members[ $send_member_result['newsletter_member_id'] ] = array();
					switch ( $send_member_result['status'] ) {
						case _MAIL_STATUS_SENT:
							$update_members[ $send_member_result['newsletter_member_id'] ]['.sent_time'] = print_date( time(), true );
							$update_members[ $send_member_result['newsletter_member_id'] ]['.status']    = _l( 'sent' );
							$output_messages[]                                                           = _l( 'Sent successfully: %s', $send_member_result['email'] );
							break;
						case _MAIL_STATUS_OVER_QUOTA:
							$output_messages[]                                                        = _l( 'Email quota exceeded while sending to: %s. Please wait.', $send_member_result['email'] );
							$update_members[ $send_member_result['newsletter_member_id'] ]['.status'] = _l( 'over quota' );
							// todo - update the main newsletter status to over quota?


							$result['timeout'] = - 1;
							$result['done']    = 1;

							break 2;
						case _MAIL_STATUS_FAILED:
						default:
							$output_messages[]                                                        = _l( 'FAILED: %s Reason: %s', $send_member_result['email'], $send_member_result['error'] );
							$update_members[ $send_member_result['newsletter_member_id'] ]['.status'] = _l( 'failed' );
							break;
					}
				}
			}
			//break;
			//}

			// get an update:
			$send                    = module_newsletter::get_send( $send_id );
			$result['#sent_to']      = _l( '%s of %s', (int) $send['total_sent_count'], (int) $send['total_member_count'] );
			$result['#open_rate']    = _l( '%s (%s%%)', (int) $send['total_open_count'], round( ( $send['total_open_count'] / $send['total_member_count'] ) * 100 ) );
			$result['#bounces']      = _l( '%s (%s%%)', (int) $send['total_bounce_count'], round( ( $send['total_bounce_count'] / $send['total_member_count'] ) * 100 ) );
			$result['#failures']     = _l( '%s (%s%%)', (int) $send['total_fail_count'], round( ( $send['total_fail_count'] / $send['total_member_count'] ) * 100 ) );
			$result['#unsubscribed'] = _l( '%s (%s%%)', (int) $send['total_unsubscribe_count'], round( ( $send['total_unsubscribe_count'] / $send['total_member_count'] ) * 100 ) );
			switch ( $send['status'] ) {
				case _NEWSLETTER_STATUS_NEW:
					$result['#current_status'] = _l( 'Not started' );
					break;
				case _NEWSLETTER_STATUS_PAUSED:
					$result['#current_status'] = _l( 'Paused' );
					break;
				case _NEWSLETTER_STATUS_PENDING:
					$result['#current_status'] = _l( 'Currently sending' );
					break;
				case _NEWSLETTER_STATUS_SENT:
					$result['#current_status'] = _l( 'All sent' );
					break;
				default:
					$result['#current_status'] = _l( 'Unknown??' );
			}

			$remain = (int) $send['total_member_count'] - (int) $send['total_sent_count'];
			if ( $remain > 0 ) {
				$output_messages[] = _l( '%s people remain', $remain );
				$output_messages[] = _l( 'Waiting %s seconds before next batch of %s emails', $newsletter_send_burst_break, $newsletter_send_burst_count );
				if ( ! isset( $result['timeout'] ) ) {
					$result['timeout'] = $newsletter_send_burst_break;
				}
			} else {
				//$output_messages[] = _l('Finished sending, %s people remain',$remain);
				//$output_messages[] = _l('Please <a href="%s">click here</a> to refresh the page.',module_newsletter::link_queue_watch($newsletter_id,$send_id));
				$output_messages[] = _l( 'Done' );
				if ( ! isset( $result['timeout'] ) ) {
					$result['timeout'] = - 1;
				}
				if ( ! isset( $result['done'] ) ) {
					$result['done'] = 1;
				}
				if ( ! $send['finish_time'] ) {
					// just to make sure we set the finish time.
					$send_result = module_newsletter::process_send( $newsletter_id, $send_id );
				}
			}
		}
	}
}
$result['messages']       = $output_messages;
$result['update_members'] = $update_members;

// put at end of array. bad js hack
if ( isset( $result['done'] ) ) {
	unset( $result['done'] );
	$result['done'] = 1;
}

echo json_encode( $result );

exit;