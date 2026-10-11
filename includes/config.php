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

// Local overrides first (config.local can define DB etc.)
if ( ! defined( '_UCM_CONFIG_LOCAL' ) ) {
	define( '_UCM_CONFIG_LOCAL', ucm_env( 'UCM_CONFIG_LOCAL', __DIR__ . '/config.local.php' ) );
}
if ( is_file( _UCM_CONFIG_LOCAL ) ) {
	require_once _UCM_CONFIG_LOCAL;
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
define( '_UCM_FOLDER', dirname( dirname( __FILE__ ) ) . '/' );
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
define( "_DEMO_MODE", false );
define( "_BLOCK_EMAILS", false );
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
