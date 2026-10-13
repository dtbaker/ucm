<?php

/**
 * Everything the help system knows about talking to the documentation site.
 *
 * This is deliberately a plain class with no database, no configuration and no other
 * module behind it, so the rules below can be pinned by a unit test rather than by good
 * intentions. module_help holds the parts that need the application; this holds the parts
 * that decide what leaves the server.
 *
 * In 2016 the help button opened an iframe on a third party host and put this install's
 * purchase codes and its own address in the query string, on every admin page load. The
 * design here is shaped around making that impossible rather than merely discouraged:
 *
 *  - the URL is assembled here and nowhere else, from three named fields;
 *  - the slug is checked against the list of articles shipped in the download before a URL
 *    exists at all, so an unknown slug never becomes a request and a crafted one never
 *    becomes a request somewhere else;
 *  - the request is made by PHP, so there is no browser to attach a Referer, an Origin or
 *    a cookie to it.
 *
 * tests/unit/HelpDocsTest.php asserts the exact query string and the exact header list. If
 * you are here to add a field to the request, that test is where the conversation about it
 * should happen.
 */
class ucm_help_docs
{
	/** Where a human reads the documentation. */
	const DOCS_WEB = 'https://docs.ultimateclientmanager.com/';

	/** Where this code reads the documentation. Static JSON, served by a CDN. */
	const DOCS_API = 'https://docs.ultimateclientmanager.com/api/v1/';

	/** Fixed. No version suffix, no install address, nothing that varies per site. */
	const USER_AGENT = 'UCM-Help/1.0';

	/** Seconds to get a connection. The panel must never hold the interface up. */
	const CONNECT_TIMEOUT = 3;

	/** Seconds for the whole request. */
	const TOTAL_TIMEOUT = 5;

	/** How long a fetched article is considered current. Documentation does not change hourly. */
	const CACHE_TTL = 604800;

	/** Consecutive failures before the help system stops trying. */
	const BREAKER_LIMIT = 3;

	/** How long it stops trying for. */
	const BREAKER_COOLDOWN = 3600;

	/**
	 * A slug in the shape the documentation site uses, or nothing.
	 *
	 * Note that anything not of that shape is refused outright rather than stripped down
	 * to the characters that were acceptable. Stripping is the tempting version and it is
	 * the wrong one: it turns "https://evil.example.com/" into "httpsevilexamplecom",
	 * which is a perfectly ordinary looking slug, and it means the thing that gets checked
	 * against the shipped index is not the thing the caller asked for. Refusing keeps the
	 * two the same.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function clean_slug( $slug ) {
		if ( ! is_string( $slug ) ) {
			return '';
		}
		$slug = strtolower( trim( $slug ) );
		if ( ! strlen( $slug ) || strlen( $slug ) > 120 ) {
			return '';
		}
		// One or more segments of letters, digits, dash and underscore, joined by slashes.
		if ( ! preg_match( '#^[a-z0-9_\-]+(?:/[a-z0-9_\-]+)*$#', $slug ) ) {
			return '';
		}

		return $slug;
	}

	/**
	 * Turn a screen into a documentation slug using a plugin's shipped help map.
	 *
	 * The exact screen wins, then the article the whole plugin points at, then nothing -
	 * and nothing means the caller falls back to the documentation index. The map is the
	 * plugin's own *.help.json, which the documentation build writes.
	 *
	 * @param string $page_key eg: invoice-invoice_admin_edit
	 * @param array  $map      decoded *.help.json for the plugin that key belongs to
	 *
	 * @return string
	 */
	public static function slug_for_page( $page_key, $map ) {
		$page_key = self::clean_page_key( $page_key );
		if ( ! $page_key || ! is_array( $map ) ) {
			return '';
		}
		if ( isset( $map['pages'] ) && is_array( $map['pages'] ) && isset( $map['pages'][ $page_key ] ) ) {
			return self::clean_slug( $map['pages'][ $page_key ] );
		}
		if ( ! empty( $map['article'] ) ) {
			return self::clean_slug( $map['article'] );
		}

		return '';
	}

	/**
	 * The page identity index.php builds, reduced to the characters it can actually contain.
	 *
	 * @param string $page_key
	 *
	 * @return string
	 */
	public static function clean_page_key( $page_key ) {
		if ( ! is_string( $page_key ) ) {
			return '';
		}
		$page_key = strtolower( trim( $page_key ) );
		if ( ! strlen( $page_key ) || strlen( $page_key ) > 120 ) {
			return '';
		}
		// Refused rather than stripped, for the reason given on clean_slug().
		if ( ! preg_match( '#^[a-z0-9_\-]+$#', $page_key ) ) {
			return '';
		}

		return $page_key;
	}

	/**
	 * The plugin a page key belongs to, eg: invoice-invoice_admin_edit gives invoice.
	 *
	 * @param string $page_key
	 *
	 * @return string
	 */
	public static function module_for_page( $page_key ) {
		$page_key = self::clean_page_key( $page_key );
		if ( ! $page_key ) {
			return '';
		}
		$parts = explode( '-', $page_key );

		return $parts[0];
	}

	/**
	 * The version to ask for documentation as, cut back to major.minor.
	 *
	 * Full build numbers are fine to send but they say more about an install than the
	 * documentation site has any use for, and a value that is specific enough to identify
	 * a site is an install identifier whatever it is called.
	 *
	 * @param string $version usually _SCRIPT_VERSION
	 *
	 * @return string
	 */
	public static function version_for_request( $version ) {
		if ( ! is_string( $version ) && ! is_numeric( $version ) ) {
			return '';
		}
		// Read the leading major[.minor] and discard the rest of the string, rather than
		// removing the characters we dislike from it - "3.5&purchase_code=abc123" should
		// become "3.5", not "3.5123".
		if ( ! preg_match( '#^\s*([0-9]+)(?:\.([0-9]+))?#', (string) $version, $matches ) ) {
			return '';
		}

		return isset( $matches[2] ) && strlen( $matches[2] ) ? $matches[1] . '.' . $matches[2] : $matches[1];
	}

	/**
	 * A language code, for when the documentation is translated. Two letters, nothing else.
	 *
	 * @param string $language
	 *
	 * @return string
	 */
	public static function language_for_request( $language ) {
		if ( ! is_string( $language ) ) {
			return 'en';
		}
		$language = strtolower( substr( preg_replace( '#[^a-zA-Z]#', '', $language ), 0, 2 ) );

		return strlen( $language ) === 2 ? $language : 'en';
	}

	/**
	 * The one URL this product fetches documentation from.
	 *
	 * Three fields, all of them derived from things shipped in the download: which article,
	 * which release of the software, which language. No purchase code, no licence key, no
	 * host name, no user, no record id, no install fingerprint.
	 *
	 * @param string $slug     must already be known to the shipped index - see is_known_slug()
	 * @param string $version
	 * @param string $language
	 *
	 * @return string empty when the slug is not usable, which means no request is made
	 */
	public static function api_url( $slug, $version, $language ) {
		$slug = self::clean_slug( $slug );
		if ( ! $slug ) {
			return '';
		}
		$query = array(
			'v'    => self::version_for_request( $version ),
			'lang' => self::language_for_request( $language ),
		);

		return self::DOCS_API . str_replace( '%2F', '/', rawurlencode( $slug ) ) . '.json?' . http_build_query( $query );
	}

	/**
	 * Where to send a person who clicks through to read the article in full.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function web_url( $slug ) {
		$slug = self::clean_slug( $slug );

		return self::DOCS_WEB . ( $slug ? str_replace( '%2F', '/', rawurlencode( $slug ) ) . '/' : '' );
	}

	/**
	 * Every header the outbound request carries.
	 *
	 * Returned rather than set inline so a test can read the whole list. Note what is not
	 * here: no Referer, no Origin, no Cookie, no Authorization, and a User-Agent that is
	 * the same string on all five thousand installations.
	 *
	 * @return array
	 */
	public static function request_headers() {
		return array(
			'User-Agent: ' . self::USER_AGENT,
			'Accept: application/json',
		);
	}

	/**
	 * Is this a slug we ship, and therefore one it is safe to build a URL from?
	 *
	 * Checking against the shipped index before the URL exists is what stops a crafted
	 * ?slug= from making the server fetch something of the caller's choosing. It also
	 * means an install that is asked for an article nobody wrote answers locally instead
	 * of going out to the network to be told the same thing.
	 *
	 * @param string $slug
	 * @param array  $index decoded content/index.json
	 *
	 * @return bool
	 */
	public static function is_known_slug( $slug, $index ) {
		$slug = self::clean_slug( $slug );
		if ( ! $slug || ! is_array( $index ) ) {
			return false;
		}

		return isset( $index[ $slug ] ) && is_array( $index[ $slug ] );
	}

	/**
	 * Reduce whatever came back over the wire to the fields the panel renders.
	 *
	 * Unknown keys are dropped rather than carried, and the body is stripped of the things
	 * that would let the documentation host run script inside somebody's admin area.
	 * module_help runs the result through HTMLPurifier as well where that is available;
	 * this is the part that works without it.
	 *
	 * @param array $data
	 *
	 * @return array|false
	 */
	public static function clean_article( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}
		$article = array(
			'title'   => isset( $data['title'] ) && is_string( $data['title'] ) ? self::clean_text( $data['title'] ) : '',
			'summary' => isset( $data['summary'] ) && is_string( $data['summary'] ) ? self::clean_text( $data['summary'] ) : '',
			'html'    => isset( $data['html'] ) && is_string( $data['html'] ) ? self::clean_html( $data['html'] ) : '',
			'video'   => isset( $data['video'] ) && is_string( $data['video'] ) ? self::clean_video_id( $data['video'] ) : '',
			'updated' => isset( $data['updated'] ) && is_string( $data['updated'] ) ? self::clean_text( $data['updated'] ) : '',
			'slug'    => isset( $data['slug'] ) ? self::clean_slug( $data['slug'] ) : '',
			'stale'   => false,
		);
		if ( ! strlen( $article['title'] ) && ! strlen( $article['html'] ) ) {
			return false;
		}

		return $article;
	}

	/**
	 * @param string $text
	 *
	 * @return string
	 */
	public static function clean_text( $text ) {
		$text = strip_tags( (string) $text );

		return trim( preg_replace( '#\s+#', ' ', $text ) );
	}

	/**
	 * Strip the parts of a document that can act rather than be read.
	 *
	 * Not a substitute for a real sanitiser - module_help adds HTMLPurifier on top - but
	 * it works with nothing else loaded, which is what the offline bundle and the unit
	 * tests need.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public static function clean_html( $html ) {
		$html = (string) $html;
		// Whole elements that exist to execute or to load something else.
		$html = preg_replace( '#<\s*(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html );
		$html = preg_replace( '#<\s*/?\s*(script|style|iframe|object|embed|form|link|meta|base)\b[^>]*>#i', '', $html );
		// Event handlers, in any of the three ways an attribute can be quoted.
		$html = preg_replace( '#\son[a-z]+\s*=\s*"[^"]*"#i', '', $html );
		$html = preg_replace( "#\son[a-z]+\s*=\s*'[^']*'#i", '', $html );
		$html = preg_replace( '#\son[a-z]+\s*=\s*[^\s>]+#i', '', $html );
		// Scheme handlers that run instead of fetching.
		$html = preg_replace( '#(href|src|action|formaction)\s*=\s*(["\']?)\s*(javascript|vbscript|data)\s*:#i', '$1=$2#', $html );

		return trim( $html );
	}

	/**
	 * A YouTube id, or nothing. Eleven characters from a known alphabet.
	 *
	 * @param string $video
	 *
	 * @return string
	 */
	public static function clean_video_id( $video ) {
		if ( ! is_string( $video ) ) {
			return '';
		}
		$video = trim( $video );
		// A YouTube id is eleven characters of this alphabet. Anything else is refused
		// rather than filtered - a URL with the punctuation taken out is not an id.
		if ( ! preg_match( '#^[a-zA-Z0-9_\-]{11}$#', $video ) ) {
			return '';
		}

		return $video;
	}

	/**
	 * Where a video link points.
	 *
	 * youtube-nocookie is the no-tracking-until-you-press-play host. The in-app panel only
	 * ever links to this, never embeds it - an embed would put a third party iframe inside
	 * the admin interface, which is the pattern this whole design exists to avoid.
	 *
	 * @param string $video
	 *
	 * @return string
	 */
	public static function video_url( $video ) {
		$video = self::clean_video_id( $video );

		return $video ? 'https://www.youtube-nocookie.com/watch?v=' . $video : '';
	}
}
