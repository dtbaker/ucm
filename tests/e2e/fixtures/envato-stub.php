<?php
/**
 * A stand-in for Envato's author API (https://api.envato.com/v3/market/author/sale), for the
 * update server in the development container. docker-compose.yml points UCM_ENVATO_API_URL
 * here and gives the server the token it checks. Never part of a release.
 *
 * 00000000-e2e0-4000-8000-000000000001  a sale of UCM
 * 00000000-e2e0-4000-8000-000000000002  a sale of some other item
 * 00000000-e2e0-4000-8000-0000000000ff  Envato is having an outage
 * anything else                          not a sale
 */
header( 'Content-Type: application/json' );

// Apache does not hand an Authorization header to $_SERVER by default.
$headers = function_exists( 'getallheaders' ) ? array_change_key_case( getallheaders(), CASE_LOWER ) : array();
$auth    = isset( $headers['authorization'] ) ? $headers['authorization'] : '';
if ( 'Bearer e2e-token' !== $auth ) {
	http_response_code( 401 );
	echo '{"error":401}';
	exit;
}

$code = isset( $_GET['code'] ) && is_string( $_GET['code'] ) ? strtolower( $_GET['code'] ) : '';
switch ( $code ) {
	case '00000000-e2e0-4000-8000-000000000001':
		echo json_encode( array( 'item' => array( 'id' => 2621629, 'name' => 'stub' ) ) );
		break;
	case '00000000-e2e0-4000-8000-000000000002':
		echo json_encode( array( 'item' => array( 'id' => 1, 'name' => 'another item' ) ) );
		break;
	case '00000000-e2e0-4000-8000-0000000000ff':
		http_response_code( 503 );
		echo '{"error":503}';
		break;
	default:
		http_response_code( 404 );
		echo '{"error":404}';
}
