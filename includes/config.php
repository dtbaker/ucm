<?php

//Ultimate Client Manager - config file
if ( is_file( __DIR__ . '/config.local.php' ) ) {
	require_once __DIR__ . '/config.local.php';
} else {

	if ( ! defined( '_DB_SERVER' ) ) {
		define( '_DB_SERVER', 'localhost' );
		define( '_DB_NAME', 'ucm' );
		define( '_DB_USER', 'username' );
		define( '_DB_PASS', 'password' );
		define( '_DB_PREFIX', 'ucm_' );
	}

	// Optionally configure custom upload directories:
	//define('_UCM_FILE_STORAGE_DIR', realpath( __DIR__ .'/../temp/'));
	//define('_UCM_FILE_STORAGE_HREF','/path/to/temp/');

	define( '_UCM_VERSION', 2 );
	define( '_UCM_FOLDER', dirname( dirname( __FILE__ ) ) . '/' );
	if ( ! defined( '_UCM_SECRET' ) ) {
		define( '_UCM_SECRET', _UCM_FOLDER );
	}

	define( "_EXTERNAL_TUNNEL", 'ext.php' );
	define( "_EXTERNAL_TUNNEL_REWRITE", 'external/' );
	define( "_ENABLE_CACHE", true );
	define( "_DEBUG_MODE", false );
	define( "_DEBUG_SQL", false );
	define( "_DEMO_MODE", false );
	define( "_BLOCK_EMAILS", false );
	if ( ! defined( '_REWRITE_LINKS' ) ) {
		define( "_REWRITE_LINKS", true );
	}

	ini_set( "display_errors", false );
	ini_set( "error_reporting", 0 );
	define( 'COMPANY_UNIQUE_CONFIG', false );
}