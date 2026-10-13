<?php

require_once( dirname( __FILE__ ) . '/docs_api.php' );

/**
 * The help system.
 *
 * Most of this plugin has been here since 2014: a question mark button in the header, a
 * settings page, a permission check, a javascript namespace and twenty two *.help.json
 * files sitting next to the plugins they describe. What was missing was the part that
 * turns the screen you are looking at into the article that explains it, so the button
 * opened a modal containing one link and the JSON files were read by nothing at all.
 *
 * That is what this adds. The route is:
 *
 *   index.php works out a page identity   ->  invoice-invoice_admin_edit
 *   the plugin's invoice.help.json maps it ->  invoices/editing
 *   the article comes from, in order:         cache, the copy in the download, the web
 *
 * The documentation is fetched by PHP rather than by the browser. That is not an
 * implementation detail, it is the privacy design: a request the browser never makes
 * cannot carry a Referer, an Origin or a cookie, so this installation's address does not
 * travel with it. See docs_api.php, which holds the rules, and
 * tests/unit/HelpDocsTest.php, which holds them in place.
 */
class module_help extends module_base
{
	public $links;
	public $help_types;

	/**
	 * The page identity index.php built for this screen, eg: invoice-invoice_admin_edit.
	 *
	 * @var string
	 */
	private static $page_key = '';

	/** Decoded *.help.json, by plugin name. */
	private static $plugin_help = array();

	/** Decoded content/index.json. */
	private static $index = null;

	public function __construct() {
		$this->module_name     = 'help';
		$this->module_position = 230;
		$this->version         = 2.14;
	}

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->links      = array();
		$this->help_types = array();
		//2.14 - 2026-10-13 - documentation deep linking
		//2.13 - 2016-20-30 - modal improvements
		//2.12 - 2016-01-04 - help js
		//2.11 - 2014-04-05 - url help js
		//2.1 - 2014-03-14 - initial release of new help system

		if ( self::is_plugin_enabled() && self::visible_to_current_user() ) {
			// hook for help icon in top bar
			hook_add( 'header_buttons', 'module_help::hook_filter_var_header_buttons' );
			hook_add( 'header_print_js', 'module_help::header_print_js' );
			// index.php hands this hook the page identity, which is how the help button
			// knows which screen it is sitting on.
			hook_add( 'inner_content_start', 'module_help::hook_inner_content_start' );
			module_config::register_js( 'help', 'help_panel.js' );
			module_config::register_css( 'help', 'help_panel.css' );

			if ( module_config::can_i( 'view', 'Settings' ) ) {
				$this->links[] = array(
					"name"                => "Help",
					"p"                   => "help_settings",
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}

		}

	}

	/**
	 * May the person looking at this screen see help at all?
	 *
	 * help_only_for_admin now defaults to 0 where it used to default to 1. The old default
	 * meant that on a brand new installation only user id 1 ever saw the help button, so
	 * everyone else in the business got no documentation - which, for a product whose
	 * support burden is mostly "how do I do X", is the wrong way round.
	 *
	 * This changes nothing for existing installations. module_config::c() writes its
	 * default into the config table the first time it is read, so every site that has ever
	 * loaded an admin page already has a 1 stored and keeps it. Only new installations get
	 * the new behaviour, and an owner who wants the old one ticks the box.
	 *
	 * @return bool
	 */
	public static function visible_to_current_user() {
		if ( module_config::c( 'help_only_for_admin', 0 ) ) {
			return module_security::get_loggedin_id() == 1;
		}

		return self::can_i( 'view', 'Help' );
	}

	/**
	 * Remember which screen is being rendered.
	 *
	 * index.php calls this for every page it includes and a request can include more than
	 * one, so the first one wins - that is the page the person actually asked for.
	 *
	 * @param string $hook
	 * @param mixed  $module
	 * @param string $page_final
	 * @param string $page_unique_id eg: invoice-invoice_admin_edit
	 *
	 * @return void
	 */
	public static function hook_inner_content_start( $hook, $module = false, $page_final = '', $page_unique_id = '' ) {
		if ( ! self::$page_key && $page_unique_id ) {
			self::$page_key = ucm_help_docs::clean_page_key( $page_unique_id );
		}
	}

	/**
	 * @return string
	 */
	public static function page_key() {
		return self::$page_key;
	}

	/**
	 * The article slug for the screen being rendered, or '' for the documentation index.
	 *
	 * @return string
	 */
	public static function slug_for_current_page() {
		return self::slug_for_page( self::$page_key );
	}

	/**
	 * Map a screen to a documentation article.
	 *
	 * @param string $page_key eg: invoice-invoice_admin_edit
	 *
	 * @return string
	 */
	public static function slug_for_page( $page_key ) {
		$module = ucm_help_docs::module_for_page( $page_key );
		if ( ! $module ) {
			return '';
		}

		return ucm_help_docs::slug_for_page( $page_key, self::load_plugin_help( $module ) );
	}

	/**
	 * A plugin's *.help.json, which is written by the documentation build.
	 *
	 * The file lives next to the plugin it describes, which is where these files have
	 * always lived - this is the code that finally reads them.
	 *
	 * @param string $module
	 *
	 * @return array
	 */
	public static function load_plugin_help( $module ) {
		$module = preg_replace( '#[^a-z0-9_]#', '', strtolower( (string) $module ) );
		if ( ! $module ) {
			return array();
		}
		if ( isset( self::$plugin_help[ $module ] ) ) {
			return self::$plugin_help[ $module ];
		}
		self::$plugin_help[ $module ] = array();

		if ( 'pages' === $module ) {
			$file = 'pages/home.help.json';
		} else {
			$file = 'includes/plugin_' . $module . '/' . $module . '.help.json';
		}
		if ( class_exists( 'module_theme', false ) ) {
			$file = module_theme::include_ucm( $file );
		}
		if ( is_file( $file ) ) {
			$data = json_decode( (string) file_get_contents( $file ), true );
			if ( is_array( $data ) ) {
				self::$plugin_help[ $module ] = $data;
			}
		}

		return self::$plugin_help[ $module ];
	}

	/**
	 * Every article that ships inside this download, as slug => title and summary.
	 *
	 * This doubles as the list of slugs it is permissible to build a URL out of. A slug
	 * that is not in here is answered locally and never becomes a request - which is what
	 * stops a crafted ?slug= turning the help system into a way of making this server
	 * fetch somebody else's address.
	 *
	 * @return array
	 */
	public static function load_index() {
		if ( is_array( self::$index ) ) {
			return self::$index;
		}
		self::$index = array();
		// _index.json, not index.json: the documentation's own front page has the slug
		// "index", and its article would otherwise be written over the catalogue.
		$file = dirname( __FILE__ ) . '/content/_index.json';
		if ( is_file( $file ) ) {
			$data = json_decode( (string) file_get_contents( $file ), true );
			if ( is_array( $data ) ) {
				self::$index = $data;
			}
		}

		return self::$index;
	}

	/**
	 * The article for a slug, however we can get it.
	 *
	 * In order: a recently fetched copy, the copy in the download, the web, an old copy.
	 * The caller always gets something printable, or false when the slug is not one this
	 * release knows about.
	 *
	 * The shipped copy is deliberately ahead of the network. Plenty of these installations
	 * sit on an intranet with no route out, and help that spins for five seconds before
	 * giving up is worse than help that is a few weeks out of date.
	 *
	 * @param string $slug
	 *
	 * @return array|false
	 */
	public static function get_article( $slug ) {
		$slug  = ucm_help_docs::clean_slug( $slug );
		$index = self::load_index();
		if ( ! ucm_help_docs::is_known_slug( $slug, $index ) ) {
			return false;
		}

		$cached = self::cache_get( $slug );
		if ( $cached && isset( $cached['fetched'] ) && $cached['fetched'] > time() - ucm_help_docs::CACHE_TTL ) {
			return $cached['article'];
		}

		$bundled = self::load_bundled( $slug );

		if ( self::remote_allowed() ) {
			$remote = self::fetch_remote( $slug );
			if ( $remote ) {
				self::cache_set( $slug, $remote );
				self::breaker_reset();

				return $remote;
			}
			self::breaker_trip();
		}

		if ( $bundled ) {
			return $bundled;
		}
		if ( $cached && isset( $cached['article'] ) ) {
			$cached['article']['stale'] = true;

			return $cached['article'];
		}

		// Nothing but the title and a link out. Still better than a spinner.
		$entry = $index[ $slug ];

		return array(
			'title'   => isset( $entry['title'] ) ? $entry['title'] : $slug,
			'summary' => isset( $entry['summary'] ) ? $entry['summary'] : '',
			'html'    => '',
			'video'   => isset( $entry['video'] ) ? $entry['video'] : '',
			'updated' => '',
			'slug'    => $slug,
			'stale'   => false,
		);
	}

	/**
	 * The copy of an article that shipped in the download.
	 *
	 * The whole corpus is a few hundred kilobytes of JSON, which is less than one
	 * screenshot, so there is no reason for an installation ever to be unable to show its
	 * own documentation.
	 *
	 * @param string $slug
	 *
	 * @return array|false
	 */
	public static function load_bundled( $slug ) {
		$slug = ucm_help_docs::clean_slug( $slug );
		if ( ! $slug ) {
			return false;
		}
		$file = dirname( __FILE__ ) . '/content/' . str_replace( '/', '--', $slug ) . '.json';
		if ( ! is_file( $file ) ) {
			return false;
		}
		$data = json_decode( (string) file_get_contents( $file ), true );

		return ucm_help_docs::clean_article( $data );
	}

	/**
	 * Is the help system allowed to go out to the network right now?
	 *
	 * Two gates. The owner may have switched remote help off entirely, in which case this
	 * installation makes no outbound request in its lifetime. And the circuit breaker may
	 * have given up after three consecutive failures, which is what keeps a machine with
	 * no route out from retrying on every single click.
	 *
	 * @return bool
	 */
	public static function remote_allowed() {
		if ( ! module_config::c( 'help_allow_remote', 1 ) ) {
			return false;
		}
		$paused_until = (int) module_config::c( 'help_remote_paused_until', 0 );

		return $paused_until <= time();
	}

	/**
	 * The only outbound request this plugin makes, and only ever on a click.
	 *
	 * Never on page load, never prefetched, never from header_print_js - the 2016 leak
	 * happened on every admin page view, and a click-only rule means an installation whose
	 * staff never press the help button makes no request at all, ever.
	 *
	 * What it carries is decided in ucm_help_docs::api_url() and asserted in
	 * tests/unit/HelpDocsTest.php. $slug has already been checked against the list of
	 * articles shipped in this download, so it cannot point anywhere else.
	 *
	 * @param string $slug
	 *
	 * @return array|false
	 */
	public static function fetch_remote( $slug ) {
		$url = ucm_help_docs::api_url( $slug, _SCRIPT_VERSION, self::docs_language() );
		if ( ! $url ) {
			return false;
		}

		$result = false;
		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init( $url );
			if ( $ch ) {
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_HEADER, false );
				curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, ucm_help_docs::CONNECT_TIMEOUT );
				curl_setopt( $ch, CURLOPT_TIMEOUT, ucm_help_docs::TOTAL_TIMEOUT );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, ucm_help_docs::request_headers() );
				// Said explicitly so that a later edit has to remove a line rather than
				// forget to add one. None of this may travel with a help request.
				curl_setopt( $ch, CURLOPT_REFERER, '' );
				curl_setopt( $ch, CURLOPT_COOKIE, '' );
				curl_setopt( $ch, CURLOPT_COOKIEFILE, '' );
				curl_setopt( $ch, CURLOPT_COOKIEJAR, '' );
				// A redirect we followed could be to anywhere, which would undo the
				// whitelist in api_url(). Documentation does not need redirects.
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
				curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
				$body = curl_exec( $ch );
				$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				curl_close( $ch );
				if ( 200 === $code && is_string( $body ) ) {
					$result = $body;
				}
			}
		} else {
			$context = stream_context_create( array(
				'http' => array(
					'method'          => 'GET',
					'header'          => implode( "\r\n", ucm_help_docs::request_headers() ),
					'timeout'         => ucm_help_docs::TOTAL_TIMEOUT,
					'follow_location' => 0,
					'ignore_errors'   => true,
				),
			) );
			$body    = @file_get_contents( $url, false, $context );
			// $http_response_header is set by the http stream wrapper. ignore_errors is on
			// above so that a 404 gives us a body and a status line rather than a warning,
			// which means the status line is the only thing that says whether this worked.
			if ( is_string( $body ) && ! empty( $http_response_header[0] )
			     && strpos( (string) $http_response_header[0], ' 200' ) !== false ) {
				$result = $body;
			}
		}

		if ( ! is_string( $result ) || ! strlen( $result ) ) {
			return false;
		}
		$data = json_decode( $result, true );
		if ( ! is_array( $data ) ) {
			return false;
		}
		$data['slug'] = $slug;

		return self::purify_article( ucm_help_docs::clean_article( $data ) );
	}

	/**
	 * Second pass over an article body, using the sanitiser the product already bundles.
	 *
	 * ucm_help_docs::clean_html() runs with nothing else loaded, which is what the unit
	 * tests and the offline bundle need. HTMLPurifier is better, and is right here, so
	 * anything arriving over the wire goes through both.
	 *
	 * @param array|false $article
	 *
	 * @return array|false
	 */
	public static function purify_article( $article ) {
		if ( ! is_array( $article ) ) {
			return false;
		}
		if ( ! empty( $article['html'] ) && is_callable( 'module_security::purify_html' ) ) {
			$article['html'] = module_security::purify_html( $article['html'] );
		}

		return $article;
	}

	/**
	 * @return string
	 */
	public static function docs_language() {
		$language = module_config::c( 'default_language', 'en' );

		return ucm_help_docs::language_for_request( is_string( $language ) ? $language : 'en' );
	}

	/**
	 * Where to send a reader for the whole article.
	 *
	 * The one place in the product that knows the documentation address. The hard coded
	 * links to the old wiki scattered through other plugins should come through here too -
	 * see the handoff note in the implementation log.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public static function docs_url( $slug = '' ) {
		return ucm_help_docs::web_url( $slug );
	}

	// ---------------------------------------------------------------- the cache

	/**
	 * Where fetched articles are kept.
	 *
	 * Not the config table, despite that being the obvious place in this codebase.
	 * module_config::_init_vars() does "SELECT key, val FROM config" with no WHERE on
	 * every single request, so a few hundred kilobytes of article HTML in there would be
	 * read out of the database on every page load of every installation forever. Files in
	 * the temp directory are read only when somebody opens the help panel.
	 *
	 * @return string
	 */
	private static function cache_dir() {
		$dir = _UCM_FILE_STORAGE_DIR . 'temp/help/';
		if ( ! is_dir( $dir ) ) {
			@mkdir( $dir, 0777, true );
		}

		return $dir;
	}

	/**
	 * @param string $slug
	 *
	 * @return string
	 */
	private static function cache_file( $slug ) {
		return self::cache_dir() . 'article-' . md5( $slug ) . '.json';
	}

	/**
	 * @param string $slug
	 *
	 * @return array|false
	 */
	public static function cache_get( $slug ) {
		$slug = ucm_help_docs::clean_slug( $slug );
		if ( ! $slug ) {
			return false;
		}
		$file = self::cache_file( $slug );
		if ( ! is_file( $file ) ) {
			return false;
		}
		$data = json_decode( (string) @file_get_contents( $file ), true );
		if ( ! is_array( $data ) || ! isset( $data['article'] ) || ! is_array( $data['article'] ) ) {
			return false;
		}
		$article = ucm_help_docs::clean_article( $data['article'] );
		if ( ! $article ) {
			return false;
		}

		return array(
			'fetched' => isset( $data['fetched'] ) ? (int) $data['fetched'] : 0,
			'article' => $article,
		);
	}

	/**
	 * Only ever called with a successful fetch, so a bad day on the documentation host
	 * never throws away a good copy.
	 *
	 * @param string $slug
	 * @param array  $article
	 *
	 * @return void
	 */
	public static function cache_set( $slug, $article ) {
		$slug = ucm_help_docs::clean_slug( $slug );
		if ( ! $slug || ! is_array( $article ) ) {
			return;
		}
		$dir = self::cache_dir();
		if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
			// Nothing to do about it and nothing to tell the user - they still got their
			// article, it will just be fetched again next time.
			return;
		}
		@file_put_contents( self::cache_file( $slug ), json_encode( array(
			'fetched' => time(),
			'article' => $article,
		) ) );
	}

	// ---------------------------------------------------------------- the circuit breaker

	/**
	 * Three failures in a row and the help system stops trying for an hour.
	 *
	 * An installation on a closed network with remote help left switched on should cost
	 * three failed connections once, and then nothing. Without this it costs a five second
	 * wait on every click, forever, and the owner concludes the help system is broken
	 * rather than that the network is.
	 *
	 * @return void
	 */
	public static function breaker_trip() {
		$failures = (int) module_config::c( 'help_remote_failures', 0 ) + 1;
		module_config::save_config( 'help_remote_failures', $failures );
		if ( $failures >= ucm_help_docs::BREAKER_LIMIT ) {
			module_config::save_config( 'help_remote_paused_until', time() + ucm_help_docs::BREAKER_COOLDOWN );
			module_config::save_config( 'help_remote_failures', 0 );
		}
	}

	/**
	 * @return void
	 */
	public static function breaker_reset() {
		if ( (int) module_config::c( 'help_remote_failures', 0 ) ) {
			module_config::save_config( 'help_remote_failures', 0 );
		}
		if ( (int) module_config::c( 'help_remote_paused_until', 0 ) ) {
			module_config::save_config( 'help_remote_paused_until', 0 );
		}
	}

	// ---------------------------------------------------------------- what a page prints

	/**
	 * A help link, as a real anchor with a real address.
	 *
	 * The panel is an enhancement layered on top of this by help_panel.js. If the
	 * javascript does not run, if the request fails, if somebody middle clicks it, the
	 * anchor still opens the documentation. That is the floor, and the floor is the part
	 * that is not allowed to break.
	 *
	 * rel="noreferrer" is not decoration: without it the reader's own click announces this
	 * installation's address to the documentation host, which is the same leak the server
	 * side fetch exists to prevent, arriving by the other door.
	 *
	 * Named help_link() rather than link() because module_base already has a link(), which
	 * builds a URL to a page of this plugin. Two different jobs, one short name.
	 *
	 * @param string $slug  empty means the article for the current screen
	 * @param string $label
	 *
	 * @return string
	 */
	public static function help_link( $slug = '', $label = '' ) {
		if ( ! self::is_plugin_enabled() || ! self::visible_to_current_user() ) {
			return '';
		}
		$slug = ucm_help_docs::clean_slug( $slug );
		if ( ! $slug ) {
			$slug = self::slug_for_current_page();
		}

		return '<a href="' . htmlspecialchars( self::docs_url( $slug ) ) . '"'
		       . ' class="ucm_help_link" data-help-slug="' . htmlspecialchars( $slug ) . '"'
		       . ' target="_blank" rel="noopener noreferrer"'
		       . ' title="' . htmlspecialchars( _l( 'Documentation' ) ) . '">'
		       . '<i class="fa fa-question-circle"></i>'
		       . ( $label ? ' ' . htmlspecialchars( $label ) : '' )
		       . '</a>';
	}

	public static function header_print_js() {
		$slug = self::slug_for_current_page();
		?>
		<script type="text/javascript">
        ucm.help.current_page = '<?php echo htmlspecialchars( self::page_key(), ENT_QUOTES ); ?>';
        ucm.help.current_slug = '<?php echo htmlspecialchars( $slug, ENT_QUOTES ); ?>';
        ucm.help.docs_url = '<?php echo htmlspecialchars( self::docs_url( $slug ), ENT_QUOTES ); ?>';
        ucm.help.lang.help = '<?php echo htmlspecialchars( _l( 'Help' ), ENT_QUOTES ); ?>';
        ucm.help.lang.loading = '<?php echo htmlspecialchars( _l( 'Loading' ), ENT_QUOTES ); ?>';
		</script>
		<?php
		// url_extras used to be set here. It carried this install's purchase codes and
		// its address to a third-party help site on every admin page load. Nothing is
		// requested here now - the documentation is fetched by PHP, and only on a click.
	}

	public static function hook_filter_var_header_buttons( $callback, $header_buttons ) {
		$header_buttons['help'] = array(
			'fa-icon' => 'question',
			'title'   => 'Help',
			'id'      => 'header_help',
		);

		return $header_buttons;
	}
}
