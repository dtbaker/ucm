<?php

/**
 * Bootstrap for the PHPUnit suite.
 *
 * UCM has no autoloader and most of it needs a database, so these tests deliberately
 * only load the standalone helpers in includes/functions.php. Anything needing the full
 * application is covered by the Playwright suite in tests/e2e instead.
 */

require_once dirname( __DIR__, 2 ) . '/includes/functions.php';
