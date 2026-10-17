<?php

$cron_start_time = time();

chdir( dirname( __FILE__ ) ); //

require_once( 'includes/config.php' );

if ( ! defined( '_UCM_SECRET' ) ) {
	// Unreachable in practice - includes/config.php is loaded above and always defines
	// this. Kept in step with it so there is no second, weaker definition to find.
	define( '_UCM_SECRET', ucm_env( 'UCM_SECRET', _UCM_FOLDER ) );
}

// if we are runing this from a web browser then we have to have a correct hash.
if ( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] ) {
	// check hash.
	$correct_hash = md5( _UCM_SECRET . ' secret hash ' );
	if ( ! isset( $_REQUEST['hash'] ) || $_REQUEST['hash'] != $correct_hash ) {
		echo 'failed - please check cron.php link in settings';
		exit;
	}
}

$_SERVER['REMOTE_ADDR'] = false;
$_SERVER['HTTP_HOST']   = false;
$_SERVER['REQUEST_URI'] = false;

$noredirect       = true;
$disable_sessions = true;
require_once( "init.php" );

$cron_debug = module_config::c( 'debug_cron_jobs', 0 );

// stop running cron multiple times
$cron_minimum_delay = 180; // 180 seconds = 3 mins.
$last_cron_run_time = module_config::c( 'cron_last_run', 0 );
if ( ! $cron_debug && $last_cron_run_time > 0 && $last_cron_run_time + $cron_minimum_delay >= $cron_start_time ) {
	// the last cron job ran less than 3 minutes ago, don't run it again.
	exit;
}

// One cron run at a time, whatever the delay above says.
//
// That delay does not stop two runs overlapping. It is read at the start of a run and
// written at the end, so a run that takes longer than the interval between ticks is
// overlapped by the next one, which reads the *previous* run's start time, finds it more
// than three minutes old, and proceeds. Two requests arriving in the same instant both
// read the old value and both pass it as well.
//
// Overlapping runs bill people twice. Reproduced on both of the jobs that generate
// money: two simultaneous requests here renewed one invoice into two copies, both with
// the same invoice number, and generated two renewal invoices for one subscription
// period. Neither job checks, at the moment it writes, that the work it decided to do
// twenty seconds ago is still outstanding.
//
// A MySQL named lock is held by the connection, so it goes when the request does -
// however the request ends, and including a run that dies half way. There is no flag
// left set to block every future run. GET_LOCK answers 1 for acquired, 0 for held by
// somebody else and NULL on error, and only a definite 0 stops this run: a database that
// cannot do named locks goes back to the old behaviour rather than silently never
// running the scheduled work again.
$cron_lock_name = 'ucm_cron_' . md5( _DB_NAME . '|' . _DB_PREFIX );
$cron_have_lock = false;
try {
	$cron_lock = qa1( "SELECT GET_LOCK('" . db_escape( $cron_lock_name ) . "', 0) AS got_lock" );
	if ( is_array( $cron_lock ) && isset( $cron_lock['got_lock'] ) && (string) $cron_lock['got_lock'] === '0' ) {
		if ( $cron_debug ) {
			echo "Another cron run is already in progress - skipping this one.<br>\n";
		}
		exit;
	}
	$cron_have_lock = is_array( $cron_lock ) && isset( $cron_lock['got_lock'] ) && (string) $cron_lock['got_lock'] === '1';
} catch ( Exception $e ) {
	$cron_have_lock = false;
}

try {
	foreach ( $plugins as $plugin_name => &$plugin ) {
		if ( method_exists( $plugin, 'run_cron' ) ) {
			if ( $cron_debug ) {
				if ( isset( $_REQUEST['cron_plugin'] ) && $plugin_name !== $_REQUEST['cron_plugin'] ) {
					continue;
				}
				echo "Running $plugin_name cron job <br>\n";
			}
			$plugin->run_cron( $cron_debug );
			if ( $cron_debug ) {
				echo "\n\n<br>Finished $plugin_name cron job <br>\n";
			}
		}
	}

	module_config::save_config( 'cron_last_run', $cron_start_time );
} finally {
	if ( $cron_have_lock ) {
		query( "SELECT RELEASE_LOCK('" . db_escape( $cron_lock_name ) . "')" );
	}
}
