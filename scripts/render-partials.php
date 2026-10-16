<?php

// Renders the user summary partials outside a page request, so a change to them can be
// diffed even though nothing in the tree renders them.
//
// module_user::print_user_summary() and module_customer::print_customer_summary() have no
// callers, and print_contact_summary() has no 'full' branch to include contact_summary.php
// with, so none of these partials can be reached by a Playwright spec. This boots UCM the
// way cron.php does and renders each one against real rows, which is enough to compare the
// text and the links across a refactor:
//
//     docker exec -w /var/www/html ucm-app php scripts/render-partials.php > after.html
//
// Run it from the repo as mounted in the container - it locates the webroot relative to
// itself, so a copy somewhere else cannot find init.php.
//
// Run it before and after the change and diff the two. Expect no PHP diagnostics on
// stderr; contact_summary.php used to fatal here with "Using $this when not in object
// context", which is how that was found.

chdir( dirname( __DIR__ ) );

$_SERVER['REMOTE_ADDR'] = false;
$_SERVER['HTTP_HOST']   = false;
$_SERVER['REQUEST_URI'] = false;

$noredirect       = true;
$disable_sessions = true;
require_once( 'init.php' );

$contact = qa1( "SELECT user_id FROM `" . _DB_PREFIX . "user` WHERE customer_id > 0 ORDER BY user_id LIMIT 1" );
$staff   = qa1( "SELECT user_id FROM `" . _DB_PREFIX . "user` WHERE ( customer_id = 0 OR customer_id IS NULL ) ORDER BY user_id LIMIT 1" );

echo '===== user_summary user_id=' . ( $staff ? $staff['user_id'] : 'none' ) . " =====\n";
if ( $staff ) {
	module_user::print_user_summary( $staff['user_id'], 'full' );
}

echo "\n" . '===== contact_summary user_id=' . ( $contact ? $contact['user_id'] : 'none' ) . " =====\n";
if ( $contact ) {
	// What a caller would set up before including it; there is no method that does so.
	$user_id   = $contact['user_id'];
	$user_data = module_user::get_user( $user_id );
	include( 'includes/plugin_user/pages/contact_summary.php' );
}

$customer = qa1( "SELECT customer_id FROM `" . _DB_PREFIX . "customer` ORDER BY customer_id LIMIT 1" );

echo "\n" . '===== customer_summary customer_id=' . ( $customer ? $customer['customer_id'] : 'none' ) . " =====\n";
if ( $customer ) {
	module_customer::print_customer_summary( $customer['customer_id'], 'full' );
}

// module_ticket::hook_invoice_sidebar() renders only for an invoice that a priority
// support ticket generated, which no spec in the suite sets up.
$ticket = qa1( "SELECT ticket_id, invoice_id FROM `" . _DB_PREFIX . "ticket` WHERE invoice_id > 0 ORDER BY ticket_id LIMIT 1" );

echo "\n" . '===== ticket_invoice_sidebar invoice_id=' . ( $ticket ? $ticket['invoice_id'] : 'none' ) . " =====\n";
if ( $ticket ) {
	module_ticket::hook_invoice_sidebar( false, $ticket['invoice_id'] );
}

// module_subscription::hook_invoice_sidebar() needs an invoice that a subscription payment
// created, which no spec in the suite sets up either.
$sub_invoice = qa1( "SELECT invoice_id FROM `" . _DB_PREFIX . "subscription_history` WHERE invoice_id > 0 ORDER BY subscription_history_id LIMIT 1" );

echo "\n" . '===== subscription_invoice_sidebar invoice_id=' . ( $sub_invoice ? $sub_invoice['invoice_id'] : 'none' ) . " =====\n";
if ( $sub_invoice ) {
	module_subscription::hook_invoice_sidebar( false, $sub_invoice['invoice_id'] );
}

echo "\n===== done =====\n";
