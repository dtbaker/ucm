<?php

//Ultimate Client Manager - config file
// Local overrides first (config.local can define DB etc.)
if ( is_file( __DIR__ . '/config.local.php' ) ) {
	require_once __DIR__ . '/config.local.php';
}
// Defaults for anything not set (Docker: use 'db' when in container, no config.local needed)
$in_docker = file_exists( '/.dockerenv' ) || getenv( 'DOCKER' );
if ( ! defined( '_DB_SERVER' ) ) {
	define( '_DB_SERVER', $in_docker ? 'db' : 'localhost' );
	define( '_DB_NAME', $in_docker ? 'ucm' : 'ucm' );
	define( '_DB_USER', $in_docker ? 'ucm' : 'username' );
	define( '_DB_PASS', $in_docker ? 'ucm' : 'password' );
	define( '_DB_PREFIX', 'ucm_' );
}

define( '_UCM_VERSION', 2 );
define( '_UCM_FOLDER', dirname( dirname( __FILE__ ) ) . '/' );
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