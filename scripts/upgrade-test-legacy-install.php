<?php
/**
 * A stand-in for the old one-file installer, for docs/local-upgrade-test.md.
 *
 * Before the CodeCanyon zip carried the whole product, a buyer could upload a single
 * install.php to an empty folder and open it in a browser. It asked the update server for
 * everything as a zip, unpacked it next to itself and sent the buyer on to the setup
 * wizard. The original script is not in either repository any more. This one makes the
 * same request that ucm-web/api/download_zip.php still makes, so it exercises the same
 * server path: return_as_zip + return_base_system_in_zip.
 *
 * Only for the local test stack. Copy it into an EMPTY document root as install.php, set
 * UCM_UT_SERVER in the container's environment to the local update server, and open it.
 */

header( 'Content-Type: text/html; charset=utf-8' );

$server = getenv( 'UCM_UT_SERVER' );
$code   = getenv( 'UCM_UT_CODE' );
$here   = __DIR__;

function ut_legacy_out( $message ) {
	echo '<p>' . htmlspecialchars( $message ) . "</p>\n";
	flush();
}

echo "<!doctype html><title>UCM install (local test)</title>\n";

if ( ! $server ) {
	ut_legacy_out( 'Set UCM_UT_SERVER to the local update server URL, for example http://172.31.250.10/upgrade.php' );
	exit;
}
$host = parse_url( $server, PHP_URL_HOST );
if ( in_array( strtolower( (string) $host ), array( 'ultimateclientmanager.com', 'www.ultimateclientmanager.com', 'api.ultimateclientmanager.com' ), true ) ) {
	ut_legacy_out( 'Refusing to talk to the production update server from a test install.' );
	exit;
}
if ( ! class_exists( 'ZipArchive' ) ) {
	ut_legacy_out( 'This PHP has no zip extension, so the package cannot be unpacked.' );
	exit;
}
if ( is_file( $here . '/init.php' ) && ! isset( $_GET['again'] ) ) {
	ut_legacy_out( 'This folder already has UCM in it. Add ?again=1 to fetch and unpack over the top.' );
	echo '<p><a href="index.php">Continue to the setup wizard</a></p>';
	exit;
}

// The same fields download_zip.php sends.
$fields = array(
	'installation_code'         => (string) $code,
	'current_version'           => 0,
	'installation_location'     => 'download_zip',
	'get_file_contents'         => 1,
	'return_as_zip'             => 1,
	'return_base_system_in_zip' => 1,
);

ut_legacy_out( 'Requesting the package from ' . $server . ' ...' );
$ch = curl_init( $server );
curl_setopt_array( $ch, array(
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_POST           => true,
	CURLOPT_POSTFIELDS     => $fields,
	CURLOPT_TIMEOUT        => 300,
	CURLOPT_FOLLOWLOCATION => false,
) );
$body   = curl_exec( $ch );
$status = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
$type   = (string) curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
curl_close( $ch );

if ( false === $body || 200 !== $status ) {
	ut_legacy_out( 'The server answered HTTP ' . $status . '. Nothing was unpacked.' );
	exit;
}
if ( 'PK' !== substr( (string) $body, 0, 2 ) ) {
	// Not a zip: the server answered with JSON, which is how it says no.
	$json    = json_decode( (string) $body, true );
	$message = is_array( $json ) && isset( $json['available_updates']['message'] ) ? $json['available_updates']['message'] : '';
	ut_legacy_out( 'The server did not send a zip (' . $type . '). Nothing was unpacked.' );
	ut_legacy_out( 'Server message: ' . ( strlen( (string) $message ) ? $message : substr( (string) $body, 0, 300 ) ) );
	exit;
}

$zip_file = tempnam( sys_get_temp_dir(), 'ucm-install' );
file_put_contents( $zip_file, $body );
$zip = new ZipArchive();
if ( true !== $zip->open( $zip_file ) ) {
	ut_legacy_out( 'The download is not a readable zip.' );
	exit;
}
// Refuse an entry that would land outside this folder.
for ( $i = 0; $i < $zip->numFiles; $i++ ) {
	$name = (string) $zip->getNameIndex( $i );
	if ( '' === $name || '/' === $name[0] || false !== strpos( $name, '..' ) || false !== strpos( $name, "\0" ) ) {
		ut_legacy_out( 'Refusing the package: it contains the path ' . $name );
		exit;
	}
}
$count = $zip->numFiles;
$zip->extractTo( $here );
$zip->close();
unlink( $zip_file );

ut_legacy_out( 'Unpacked ' . $count . ' entries into ' . $here . '.' );
echo '<p id="ut-legacy-done"><a href="index.php">Continue to the setup wizard</a></p>';
