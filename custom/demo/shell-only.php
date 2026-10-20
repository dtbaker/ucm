<?php
/**
 * Is this PHP being run from a shell, rather than answering a web request?
 *
 * custom/demo/reset.php sits inside the web root and replaces the database, so it must
 * never run for a visitor. "cli" is the obvious test, but not a sufficient one: on
 * SiteGround /usr/local/php82/bin/php is the CGI build, which reports cgi-fcgi when run
 * from a shell. A script run from a shell has none of the environment a web server hands
 * over with a request, and a request always has some of it, so that is what tells them
 * apart.
 *
 * This used to be PHP_SAPI !== 'cli'. On the server that refused every run, with a 404
 * page and an exit code of 0, so the deploy and reset workflows passed that step while
 * doing nothing at all.
 *
 * @param string $sapi   PHP_SAPI
 * @param array  $server $_SERVER
 *
 * @return bool
 */
function ucm_demo_is_shell( $sapi, array $server ) {
	if ( $sapi === 'cli' ) {
		return true;
	}
	if ( ! in_array( $sapi, array( 'cgi', 'cgi-fcgi' ), true ) ) {
		// apache2handler, fpm-fcgi, litespeed, cli-server: all of them serve requests.
		return false;
	}
	foreach ( array( 'REQUEST_METHOD', 'HTTP_HOST', 'REMOTE_ADDR', 'SERVER_PROTOCOL' ) as $key ) {
		if ( isset( $server[ $key ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Which command was asked for.
 *
 * Normally the first argument. Some builds of PHP do not set $argv outside the
 * command-line binary - the server's CGI build is one - so UCM_DEMO_ACTION in the
 * environment says the same thing, and failing that the process's own command line is
 * read, which is what makes a hand-typed "php -q reset.php snapshot" work there too.
 *
 * @param array       $argv    $argv, or an empty array when it is not set
 * @param string|bool $env     getenv( 'UCM_DEMO_ACTION' )
 * @param string|bool $cmdline the contents of /proc/self/cmdline: arguments separated by NUL
 * @param string      $script  the file being run, to find its place in $cmdline
 *
 * @return string empty when none was given
 */
function ucm_demo_command( array $argv, $env, $cmdline, $script ) {
	if ( isset( $argv[1] ) && $argv[1] !== '' ) {
		return (string) $argv[1];
	}
	if ( is_string( $env ) && $env !== '' ) {
		return $env;
	}
	if ( is_string( $cmdline ) && $cmdline !== '' ) {
		$parts = explode( "\0", rtrim( $cmdline, "\0" ) );
		foreach ( $parts as $i => $part ) {
			if ( basename( $part ) === basename( $script ) && isset( $parts[ $i + 1 ] ) ) {
				return $parts[ $i + 1 ];
			}
		}
	}

	return '';
}
