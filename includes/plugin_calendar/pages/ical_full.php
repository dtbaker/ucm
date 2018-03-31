<?php

header( 'Content-type: text/calendar; charset=utf-8' );
header( 'Content-Disposition: inline; filename="calfull.ics"' );

$calendar_entries = array();
$login            = false;
// is this a calendar event for a specific staff member?
if ( isset( $options['staff_id'] ) && (int) $options['staff_id'] > 0 && isset( $options['staff_hash'] ) && ! empty( $options['staff_hash'] ) ) {
	// check hash matches again. even though this is already done in the external hook part of calednar.php
	if ( $options['staff_hash'] == module_calendar::staff_hash( $options['staff_id'] ) ) {
		// correct! log this user in, temporarily for the query and then log them out again.
		module_security::user_id_temp_set( $options['staff_id'] );
		$login = true;
	}
}

// get 4 months either side of todays date.
for ( $x = - 4; $x <= 4; $x ++ ) {
	$ret = listCalendar( date( 'm/d/Y', strtotime( '+' . $x . ' months' ) ), 'month' );
	if ( is_array( $ret ) && isset( $ret['events'] ) && count( $ret['events'] ) ) {
		$calendar_entries = array_merge( $calendar_entries, $ret['events'] );
	}
}

if ( $login ) {
	module_security::user_id_temp_restore();
}

echo 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Ultimate Client Manager/Calendar Plugin v1.0//EN
CALSCALE:GREGORIAN
X-WR-CALNAME:' . _l( 'CRM Calendar' ) . '
X-WR-TIMEZONE:' . module_config::c( 'timezone', 'America/New_York' ) . '
';


//$local_timezone_string = date('e');
//$local_timezone = new DateTimeZone($local_timezone_string);
//$local_time = new DateTime("now", $local_timezone);
$timezone_hours = module_config::c( 'timezone_hours', 0 );


if ( count( $calendar_entries ) ) {
	$x = 0;
	foreach ( $calendar_entries as $alert ) {

		if ( ! isset( $alert['start_time'] ) && isset( $alert[2] ) ) {
			$bits = explode( ' ', $alert[2] );
			if ( count( $bits ) == 2 ) {
				$dates = explode( '/', $bits[0] );
				if ( count( $dates ) == 3 ) {
					$time                = $dates[2] . '-' . $dates[0] . '-' . $dates[1] . ' ' . $bits[1];
					$alert['start_time'] = strtotime( $time ); // m/d/Y H:i
				}

			}
		}
		if ( ! isset( $alert['end_time'] ) && isset( $alert[3] ) && $alert[2] != $alert[3] ) {
			$bits = explode( ' ', $alert[3] );
			if ( count( $bits ) == 2 ) {
				$dates = explode( '/', $bits[0] );
				if ( count( $dates ) == 3 ) {
					$time              = $dates[2] . '-' . $dates[0] . '-' . $dates[1] . ' ' . $bits[1];
					$alert['end_time'] = strtotime( $time ); // m/d/Y H:i
				}

			}
		}

		if ( ! isset( $alert['start_time'] ) ) {
			continue;
		}

		if ( ! isset( $alert['subject'] ) ) {
			$alert['subject'] = $alert[1];
		}
		if ( ! isset( $alert['link'] ) ) {
			$alert['link'] = $alert[12];
		}
		if ( ! isset( $alert['other_details'] ) ) {
			$alert['other_details'] = $alert[11];
		}

		//	    print_r($alert);

		$time = strtotime( $timezone_hours . ' hours', $alert['start_time'] );
		// work out the UTC time for this event, based on the timezome we have set in the configuration options

		/*DTSTART;VALUE=DATE:'.date('Ymd',$time).'
DTEND;VALUE=DATE:'.date('Ymd',strtotime('+1 day',$time)).'*/

		if ( ! empty( $alert['customer_id'] ) ) {
			// grab customers address.
			$address           = module_address::get_address( $alert['customer_id'], 'customer', 'physical', true );
			$alert['location'] = implode( ', ', $address );
		}

		if ( 0 < $alert['start_time'] ) {


			if ( ( isset( $alert['all_day'] ) && $alert['all_day'] ) || $alert[4] ) {
				$end = isset( $alert['end_time'] ) ? date( 'Ymd', strtotime( '+1 day', $alert['end_time'] ) ) : date( 'Ymd', strtotime( '+1 day', $alert['start_time'] ) );
				// it's an all day event.
				echo 'BEGIN:VEVENT
UID:' . md5( $alert['subject'] . $alert['link'] . $alert['other_details'] ) . '@ultimateclientmanager.com
DTSTAMP:' . date( 'Ymd\THis', time() ) . '
DTSTART;VALUE=DATE:' . date( 'Ymd', $alert['start_time'] ) . '
DTEND;VALUE=DATE:' . $end . '
SUMMARY:' . $alert['subject'] . '
LOCATION:' . ( isset( $alert['location'] ) ? $alert['location'] : '' ) . '
DESCRIPTION: Customer:' . $alert['link'] . "<br />" . $alert['other_details'] . '
END:VEVENT
';
			} else {
				$end = isset( $alert['end_time'] ) ? date( 'Ymd\THis', $alert['end_time'] ) : date( 'Ymd\THis', $alert['start_time'] );
				// should have a start/end time.
				echo 'BEGIN:VEVENT
UID:' . md5( $alert['subject'] . $alert['link'] . $alert['other_details'] ) . '@ultimateclientmanager.com
DTSTAMP:' . date( 'Ymd\THis', time() ) . '
DTSTART:' . date( 'Ymd\THis', $alert['start_time'] ) . '
DTEND:' . $end . '
SUMMARY:' . $alert['subject'] . '
LOCATION:' . ( isset( $alert['location'] ) ? $alert['location'] : '' ) . '
DESCRIPTION: Customer:' . $alert['link'] . "<br />" . $alert['other_details'] . '
END:VEVENT
';
			}


		}
	}
}


echo 'END:VCALENDAR';

