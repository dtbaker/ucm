<?php

/**
 * Bootstrap for the PHPUnit suite.
 *
 * UCM has no autoloader and most of it needs a database, so these tests deliberately only
 * load the few files that stand on their own: the helpers in includes/functions.php and
 * the library registry. Anything needing the full application is covered by the Playwright
 * suite in tests/e2e instead.
 */

if ( ! defined( '_UCM_FOLDER' ) ) {
	define( '_UCM_FOLDER', dirname( __DIR__, 2 ) . '/' );
}

require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
require_once dirname( __DIR__, 2 ) . '/includes/class.libraries.php';
