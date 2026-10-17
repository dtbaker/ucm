<?php

//Ultimate Client Manager - config file

/**
 * Read a setting from the environment, falling back to the given default.
 *
 * Nothing in a normal install sets these, so this changes nothing for an existing site.
 * They exist so containers and CI can point a copy of the code at a different database or
 * a different local config file without editing any tracked file.
 */
function ucm_env( $name, $default ) {
	$value = getenv( $name );

	return ( $value === false || $value === '' ) ? $default : $value;
}

/**
 * An environment variable read as a switch.
 *
 * "0", "false", "off" and "no" are off, because a variable set to the string "0" reads as
 * true to a bare cast and that is the shape a docker-compose default takes.
 */
function ucm_truthy_env( $name ) {
	$value = strtolower( trim( (string) ucm_env( $name, '' ) ) );

	return ! in_array( $value, array( '', '0', 'false', 'off', 'no' ), true );
}

// Local overrides first (config.local can define DB etc.)
if ( ! defined( '_UCM_CONFIG_LOCAL' ) ) {
	define( '_UCM_CONFIG_LOCAL', ucm_env( 'UCM_CONFIG_LOCAL', __DIR__ . '/config.local.php' ) );
}
if ( is_file( _UCM_CONFIG_LOCAL ) ) {
	require_once _UCM_CONFIG_LOCAL;
}
// Has this copy been set up yet? The setup wizard writes config.local.php once it has
// connected to a database, so until that file exists the database settings below are
// placeholders, and a failed connection means "not installed yet" rather than "broken".
// The database layer reads this to choose between the setup wizard and an error page.
//
// The environment variables do not count. They are placeholders too until something
// proves they work, and a container pointed at a database it cannot reach is better off
// on the wizard than on a blank page.
//
// Literal values in two steps, for the same reason as _UCM_SECRET below.
if ( ! defined( '_UCM_CONFIGURED' ) && is_file( _UCM_CONFIG_LOCAL ) ) {
	define( '_UCM_CONFIGURED', true );
}
if ( ! defined( '_UCM_CONFIGURED' ) ) {
	define( '_UCM_CONFIGURED', false );
}
// Defaults for anything not set (Docker: use 'db' when in container, no config.local needed)
$in_docker = file_exists( '/.dockerenv' ) || getenv( 'DOCKER' );
if ( ! defined( '_DB_SERVER' ) ) {
	define( '_DB_SERVER', ucm_env( 'UCM_DB_SERVER', $in_docker ? 'db' : 'localhost' ) );
	define( '_DB_NAME', ucm_env( 'UCM_DB_NAME', 'ucm' ) );
	define( '_DB_USER', ucm_env( 'UCM_DB_USER', $in_docker ? 'ucm' : 'username' ) );
	define( '_DB_PASS', ucm_env( 'UCM_DB_PASS', $in_docker ? 'ucm' : 'password' ) );
	define( '_DB_PREFIX', ucm_env( 'UCM_DB_PREFIX', 'ucm_' ) );
}

define( '_UCM_VERSION', 2 );
// Guarded so this file can be included more than once, and so a test can load it for the
// helpers above without warning about a constant the bootstrap already set.
if ( ! defined( '_UCM_FOLDER' ) ) {
	define( '_UCM_FOLDER', dirname( dirname( __FILE__ ) ) . '/' );
}
// The installer writes a random secret into config.local.php, so a normal install never
// reaches either branch below. They are here for containers and CI, which point at a
// database through the environment and may have no local config file at all.
//
// The fallback is _UCM_FOLDER, the install path - guessable on shared hosting, and every
// capability hash in the app is built from this value, so prefer the environment.
//
// Two steps rather than one define() with a function call in it, so static analysis can
// still see that the constant is always defined.
$ucm_secret_env = ucm_env( 'UCM_SECRET', '' );
if ( ! defined( '_UCM_SECRET' ) && $ucm_secret_env !== '' ) {
	define( '_UCM_SECRET', $ucm_secret_env );
}
unset( $ucm_secret_env );
if ( ! defined( '_UCM_SECRET' ) ) {
	define( '_UCM_SECRET', _UCM_FOLDER );
}

define( "_EXTERNAL_TUNNEL", 'ext.php' );
define( "_EXTERNAL_TUNNEL_REWRITE", 'external/' );
define( "_ENABLE_CACHE", true );
// Demo mode. Off everywhere except the public demo, which sets UCM_DEMO_MODE=1.
//
// It is read from the environment rather than edited into this file because this file
// ships to buyers: a demo that needs a code change to turn on is a demo that eventually
// gets turned on in somebody's install. config.local.php can still define it first, which
// is how a host that cannot set environment variables does it.
// Defined as a literal either way, which is the same shape _UCM_SECRET uses above and for
// the same reason: PHPStan works a constant out by evaluating its define(), so a value it
// cannot evaluate - anything reading the environment - leaves it reporting "Constant
// _DEMO_MODE not found" at all 44 places the application tests it.
if ( ! defined( '_DEMO_MODE' ) && ucm_truthy_env( 'UCM_DEMO_MODE' ) ) {
	define( '_DEMO_MODE', true );
}
if ( ! defined( '_DEMO_MODE' ) ) {
	define( '_DEMO_MODE', false );
}
// Outbound email. A public demo must not send any: anyone can put any address on a
// customer and press "Email Invoice", which makes the demo a relay. So demo mode turns
// this on by default, and UCM_BLOCK_EMAILS can set it either way for anything else.
$ucm_block_emails = strtolower( trim( (string) ucm_env( 'UCM_BLOCK_EMAILS', '' ) ) );
$ucm_block_emails = $ucm_block_emails === ''
	? _DEMO_MODE
	: ! in_array( $ucm_block_emails, array( '0', 'false', 'off', 'no' ), true );
if ( ! defined( '_BLOCK_EMAILS' ) && $ucm_block_emails ) {
	define( '_BLOCK_EMAILS', true );
}
unset( $ucm_block_emails );
if ( ! defined( '_BLOCK_EMAILS' ) ) {
	define( '_BLOCK_EMAILS', false );
}
if ( ! defined( '_REWRITE_LINKS' ) ) {
	define( "_REWRITE_LINKS", true );
}

if ( ! defined( 'COMPANY_UNIQUE_CONFIG' ) ) {
	define( 'COMPANY_UNIQUE_CONFIG', false );
}

if ( ! defined( '_DEBUG_MODE' ) ) {
	define( "_DEBUG_MODE", false );
}
if ( ! defined( '_DEBUG_SQL' ) ) {
	define( "_DEBUG_SQL", false );
}

// Error display - UCM_DEBUG env (Docker) overrides config
$debug_mode = _DEBUG_MODE || getenv( 'UCM_DEBUG' );
ini_set( "display_errors", $debug_mode ? '1' : '0' );
ini_set( "display_startup_errors", $debug_mode ? '1' : '0' );
ini_set( "error_reporting", $debug_mode ? E_ALL : 0 );
