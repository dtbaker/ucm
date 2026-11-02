<?php

/**
 * Is this licence code a real CodeCanyon purchase of UCM?
 *
 * The server half of "automated updates need a licence code". It runs only inside the update
 * endpoint (upgrade_server.php), never in an ordinary installation. A code is checked with
 * Envato's author API, which needs a personal token with the "View your items' sales history"
 * permission. The token is server configuration, not a setting: define _UCM_ENVATO_TOKEN in the
 * includes/config.php of the installation that answers updates, or set UCM_ENVATO_TOKEN in
 * the environment. Without one nothing can be verified, so no update is served.
 *
 * Answers are cached in a file per code, named by its hash, so the server asks Envato about a
 * code once a day, not on every request. Envato being unreachable is not the same as a code
 * being wrong: a code that was good within the last thirty days keeps working through an
 * outage, and a code that was never seen gets "try again shortly" rather than "invalid".
 *
 * Nothing about the buyer is kept. The cache holds the answer and the time, not the name, email
 * or site that asked.
 */
class ucm_licence_check
{
	/** CodeCanyon items whose purchase codes entitle an installation to updates. */
	const ITEM_IDS = array( 2621629 );

	const ITEM_NAME = 'Ultimate Client Manager';
	const ITEM_URL  = 'https://codecanyon.net/item/freelance-project-invoice-crm/2621629';
	const API_URL   = 'https://api.envato.com/v3/market/author/sale';

	const CACHE_VALID_FOR   = 86400;
	const CACHE_INVALID_FOR = 3600;
	const OUTAGE_GRACE      = 2592000;
	const MAX_CODES         = 5;

	const VALID       = 'valid';
	const INVALID     = 'invalid';
	const NONE        = 'none';
	const UNAVAILABLE = 'unavailable';

	/**
	 * The purchase codes in a request, as the client sends them: joined with "|".
	 *
	 * Envato purchase codes are UUIDs. Anything else is dropped here so that it never reaches
	 * the API or the cache directory.
	 *
	 * @param mixed $raw The installation_code request field.
	 *
	 * @return string[] Lower case, de-duplicated, at most MAX_CODES.
	 */
	public static function codes_from_request( $raw ) {
		if ( ! is_scalar( $raw ) ) {
			return array();
		}
		$codes = array();
		foreach ( explode( '|', strtolower( (string) $raw ) ) as $code ) {
			$code = trim( $code );
			if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $code ) ) {
				$codes[ $code ] = $code;
			}
		}

		return array_slice( array_values( $codes ), 0, self::MAX_CODES );
	}

	/**
	 * Decide whether a request may have updates.
	 *
	 * @param string[]      $codes     From codes_from_request().
	 * @param callable|null $fetch     function ( $code ): array( 'status' => int HTTP status, 0 for no answer, 'body' => string )
	 *                                 Null means "ask Envato", and "unavailable" if no token is configured.
	 * @param string        $directory Cache directory. Empty means no cache.
	 * @param int|null      $now       Clock, for tests.
	 *
	 * @return array{status: string, codes: array<string,string>} `codes` is the licence_codes map
	 *                                 the client keeps to show "this code is for ...".
	 */
	public static function check( array $codes, $fetch, $directory, $now = null ) {
		$now = null === $now ? time() : (int) $now;
		if ( ! count( $codes ) ) {
			return array( 'status' => self::NONE, 'codes' => array() );
		}
		if ( null === $fetch ) {
			$fetch = self::envato_fetcher();
		}
		$status = self::INVALID;
		$known  = array();
		foreach ( $codes as $code ) {
			$one = self::check_one( $code, $fetch, $directory, $now );
			if ( self::VALID === $one ) {
				$status = self::VALID;
				// The same shape the old server used: link and name, joined with "|".
				$known[ $code ] = self::ITEM_URL . '|' . self::ITEM_NAME;
			} elseif ( self::UNAVAILABLE === $one && self::VALID !== $status ) {
				$status = self::UNAVAILABLE;
			}
		}

		return array( 'status' => $status, 'codes' => $known );
	}

	/**
	 * @return string One of VALID, INVALID, UNAVAILABLE.
	 */
	public static function check_one( $code, $fetch, $directory, $now ) {
		$cached = self::read_cache( $directory, $code );
		if ( $cached ) {
			$age = $now - $cached['t'];
			if ( self::VALID === $cached['s'] && $age < self::CACHE_VALID_FOR ) {
				return self::VALID;
			}
			if ( self::INVALID === $cached['s'] && $age < self::CACHE_INVALID_FOR ) {
				return self::INVALID;
			}
		}

		$answer = call_user_func( $fetch, $code );
		$http   = isset( $answer['status'] ) ? (int) $answer['status'] : 0;
		$body   = isset( $answer['body'] ) && is_string( $answer['body'] ) ? $answer['body'] : '';

		if ( 200 === $http ) {
			$data = json_decode( $body, true );
			$item = is_array( $data ) && isset( $data['item'] ) && is_array( $data['item'] ) ? $data['item'] : array();
			$id   = isset( $item['id'] ) && is_scalar( $item['id'] ) ? (int) $item['id'] : 0;
			$ok   = in_array( $id, self::ITEM_IDS, true );
			self::write_cache( $directory, $code, $ok ? self::VALID : self::INVALID, $now );

			return $ok ? self::VALID : self::INVALID;
		}
		if ( 400 === $http || 404 === $http || 422 === $http ) {
			// Envato does not know the code.
			self::write_cache( $directory, $code, self::INVALID, $now );

			return self::INVALID;
		}

		// No token, a rejected token, a rate limit, a timeout or an outage. Nothing is cached,
		// and a code that was recently good stays good.
		if ( $cached && self::VALID === $cached['s'] && $now - $cached['t'] < self::OUTAGE_GRACE ) {
			return self::VALID;
		}

		return self::UNAVAILABLE;
	}

	/**
	 * What to tell an installation that is not getting updates, in words it can show.
	 */
	public static function message_for( $status ) {
		switch ( $status ) {
			case self::VALID:
				return '';
			case self::UNAVAILABLE:
				return 'Your licence code could not be checked just now. Please try again in a few minutes.';
			case self::INVALID:
				return 'This licence code was not recognised as a purchase of Ultimate Client Manager, so automated updates are not available. Please check the code on your CodeCanyon downloads page. You can still download the latest version from CodeCanyon and upload it by hand.';
			default:
				return 'Automated updates need a licence code. Enter the purchase code from your CodeCanyon downloads page in the licence box on this page to receive them. You can still download the latest version from CodeCanyon and upload it by hand.';
		}
	}

	/**
	 * The response a client gets instead of an update. The same shape as "nothing to update",
	 * so every client version shows the message and stops rather than reporting an error.
	 */
	public static function refusal( $status ) {
		return array(
			'available_updates' => array(
				'message'          => self::message_for( $status ),
				'new_version'      => 0,
				'plugins'          => array(),
				'check_again'      => 0,
				'licence_required' => true,
				'licence_status'   => $status,
			),
			'licence_codes'     => array(),
		);
	}

	/**
	 * The Envato token, or an empty string.
	 */
	public static function token() {
		$token = getenv( 'UCM_ENVATO_TOKEN' );
		if ( ! is_string( $token ) || ! strlen( $token ) ) {
			$token = defined( '_UCM_ENVATO_TOKEN' ) ? _UCM_ENVATO_TOKEN : '';
		}

		return is_string( $token ) ? trim( $token ) : '';
	}

	/**
	 * Where the author API lives. Only server configuration can change it, which is how the
	 * test suite points it at a stub.
	 */
	public static function api_url() {
		$url = getenv( 'UCM_ENVATO_API_URL' );
		if ( ! is_string( $url ) || ! strlen( $url ) ) {
			$url = defined( '_UCM_ENVATO_API_URL' ) ? _UCM_ENVATO_API_URL : self::API_URL;
		}

		return $url;
	}

	/**
	 * A fetcher that asks Envato's author API about one code.
	 *
	 * @return callable
	 */
	public static function envato_fetcher() {
		$token = self::token();
		$url   = self::api_url();

		return function ( $code ) use ( $token, $url ) {
			if ( ! strlen( $token ) || ! function_exists( 'curl_init' ) ) {
				return array( 'status' => 0, 'body' => '' );
			}
			$ch = curl_init( $url . '?code=' . rawurlencode( $code ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Authorization: Bearer ' . $token, 'Accept: application/json' ) );
			curl_setopt( $ch, CURLOPT_USERAGENT, 'UCM update server' );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
			curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
			$body   = curl_exec( $ch );
			$status = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
			curl_close( $ch );

			return array( 'status' => $status, 'body' => is_string( $body ) ? $body : '' );
		};
	}

	private static function cache_file( $directory, $code ) {
		return rtrim( $directory, '/' ) . '/' . hash( 'sha256', $code ) . '.json';
	}

	/**
	 * @return array{s: string, t: int}|null
	 */
	private static function read_cache( $directory, $code ) {
		if ( ! strlen( (string) $directory ) ) {
			return null;
		}
		$raw  = @file_get_contents( self::cache_file( $directory, $code ) );
		$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
		if ( ! is_array( $data ) || ! isset( $data['s'], $data['t'] ) || ! in_array( $data['s'], array( self::VALID, self::INVALID ), true ) ) {
			return null;
		}

		return array( 's' => $data['s'], 't' => (int) $data['t'] );
	}

	private static function write_cache( $directory, $code, $status, $now ) {
		if ( ! strlen( (string) $directory ) ) {
			return;
		}
		if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0700, true ) && ! is_dir( $directory ) ) {
			return;
		}
		@file_put_contents( self::cache_file( $directory, $code ), json_encode( array( 's' => $status, 't' => $now ) ), LOCK_EX );
	}
}
