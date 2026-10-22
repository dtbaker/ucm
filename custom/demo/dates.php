<?php
/**
 * Every column that holds a date or a time the demo's sample data is measured in. Read by
 * custom/demo/reset.php, which moves them all forward together, so the data made on one day
 * keeps reading as it did then: an invoice sent a month ago is still sent a month ago, and
 * the one that is due on Friday is still due this week.
 *
 * table => column => 'date' (YYYY-MM-DD) or 'time' (a unix timestamp). A zero, or a date of
 * 0000-00-00, means "not set" and is left alone. A table or column this database does not
 * have is skipped, so a release that drops one does not stop a reset.
 *
 * Not listed: date_created and date_updated, which the app stamps on every row and never
 * shows as a business date, and anything the sample data does not touch.
 *
 * @return array
 */
return array(
	'invoice'         => array( 'date_create' => 'date', 'date_sent' => 'date', 'date_due' => 'date', 'date_paid' => 'date', 'date_renew' => 'date', 'date_cancel' => 'date' ),
	'invoice_item'    => array( 'date_done' => 'date', 'date_due' => 'date' ),
	'invoice_payment' => array( 'date_paid' => 'date' ),
	'quote'           => array( 'date_create' => 'date', 'date_approved' => 'date' ),
	'job'             => array( 'date_quote' => 'date', 'date_start' => 'date', 'date_due' => 'date', 'date_done' => 'date', 'date_completed' => 'date', 'date_renew' => 'date' ),
	'task'            => array( 'date_due' => 'date', 'date_done' => 'date' ),
	'task_log'        => array( 'log_time' => 'time' ),
	'timer'           => array( 'start_time' => 'time', 'end_time' => 'time' ),
	'timer_segment'   => array( 'start_time' => 'time', 'end_time' => 'time' ),
	'ticket'          => array( 'last_message_timestamp' => 'time', 'due_timestamp' => 'time', 'date_completed' => 'date' ),
	'ticket_message'  => array( 'message_time' => 'time' ),
	'calendar'        => array( 'start' => 'time', 'end' => 'time' ),
	'note'            => array( 'note_time' => 'time' ),
	'file'            => array( 'file_time' => 'time', 'approved_time' => 'time' ),
	'email'           => array( 'create_time' => 'time', 'sent_time' => 'time', 'schedule_time' => 'time' ),
);
