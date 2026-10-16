<?php

// Renders the user summary partials outside a page request, so a change to them can be
// diffed even though nothing in the tree renders them.
//
// module_user::print_user_summary() has no callers, and print_contact_summary() has no
// 'full' branch to include contact_summary.php with, so neither partial can be reached by
// a Playwright spec. This boots UCM the way cron.php does and renders each one against
// real rows, which is enough to compare the text and the links across a refactor:
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

echo "\n===== done =====\n";
