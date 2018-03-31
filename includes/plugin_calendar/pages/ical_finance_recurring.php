<?php

header( 'Content-type: text/calendar; charset=utf-8' );
header( 'Content-Disposition: inline; filename="cal.ics"' );

ob_start();
$search = array(); // todo - pass any ical options through to search

// how many months in the future are we limitingi this output to?
$months          = isset( $options['months'] ) ? (int) $options['months'] : (int) module_config::c( 'calendar_recurring_months', 6 );
$limit_timestamp = strtotime( '+' . $months . ' months' );
// don't show any transactions after our timestamp here.

$search['show_finished'] = false;
$upcoming_finances       = module_finance::get_recurrings( $search );

// loop over each upcoming finance
// calculate the iterations of upcoming transactions date for each finance.
// print out those dates as long as they are before our limiting timestamp.


echo 'BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//Ultimate Client Manager/Calendar Plugin v1.0//EN
CALSCALE:GREGORIAN
X-WR-CALNAME:Recurring ' . ( isset( $options['credit'] ) && $options['credit'] ? _l( 'Credit' ) . ' ' : '' ) . ( isset( $options['debit'] ) && $options['debit'] ? _l( 'Debit' ) . ' ' : '' ) . _l( 'Transactions' ) . '
X-WR-TIMEZONE:UTC
';

//$local_timezone_string = date('e');
//$local_timezone = new DateTimeZone($local_timezone_string);
//$local_time = new DateTime("now", $local_timezone);
$timezone_hours = module_config::c( 'timezone_hours', 0 );

foreach ( $upcoming_finances as $recurring ) {

	if ( $recurring['amount'] <= 0 ) {
		continue; // skip empty ones.
	}
	if ( ! isset( $options['credit'] ) ) {
		// we dont want to show credit items.
		if ( $recurring['type'] == 'i' ) {
			continue;
		}
	}
	if ( ! isset( $options['debit'] ) ) {
		// we dont want to show credit items.
		if ( $recurring['type'] == 'e' ) {
			continue;
		}
	}
	if ( strlen( $recurring['name'] ) ) {
		$recurring['name'] = '(' . $recurring['name'] . ")";
	}

	// start looping up to our cutout.
	$time = strtotime( $timezone_hours . ' hours', strtotime( $recurring['next_due_date'] ) );
	while ( $time < $limit_timestamp ) {

		echo 'BEGIN:VEVENT
UID:' . md5( mt_rand( 1, 100 ) ) . '@ultimateclientmanager.com
';

		// work out the UTC time for this event, based on the timezome we have set in the configuration options

		echo 'DTSTAMP:' . date( 'Ymd' ) . 'T090000Z
DTSTART;VALUE=DATE:' . date( 'Ymd', $time ) . '
DTEND;VALUE=DATE:' . date( 'Ymd', strtotime( '+1 day', $time ) ) . '
SUMMARY:' . ( $recurring['type'] == 'i' ? '+' . dollar( $recurring['amount'] ) : '' ) . ( $recurring['type'] == 'e' ? '-' . dollar( $recurring['amount'] ) : '' ) . " " . $recurring['name'] . '
DESCRIPTION:' . preg_replace( '#[\r\n]+#', "<br>", $recurring['description'] ) . ' <br><a href="' . module_finance::link_open_recurring( $recurring['finance_recurring_id'] ) . '">' . _( 'Open Link' ) . '</a>
END:VEVENT
';


		// increase the time to the next recurring event.
		if ( $recurring['next_due_date'] == '0000-00-00' || ( ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) ) {
			// it's a once off..
			break; // ignore anym ore in this loop.
		} else {
			// work out when the next one will be.
			$next_time = $time;
			$next_time = strtotime( '+' . abs( (int) $recurring['days'] ) . ' days', $next_time );
			$next_time = strtotime( '+' . abs( (int) $recurring['months'] ) . ' months', $next_time );
			$next_time = strtotime( '+' . abs( (int) $recurring['years'] ) . ' years', $next_time );
			$time      = $next_time;
		}

		// make sure $time isn't past the recurring events normal time.
		$end_time = ( $recurring['end_date'] && $recurring['end_date'] != '0000-00-00' ) ? strtotime( $recurring['end_date'] ) : 0;
		if ( $end_time > 0 && $time > $end_time ) {
			// we've finished this loop
			break;
		} else {
			// we are safe to recurr at $time.
		}

	}
}
echo 'END:VCALENDAR';

$content = ob_get_clean();

echo preg_replace( '#[\r\n]+#', "\r\n", $content );