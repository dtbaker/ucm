<?php

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/plugin_help/docs_api.php';

/**
 * What the help system is allowed to send, and to where.
 *
 * The point of this file is the first group of tests. In 2016 the help button opened an
 * iframe on a third party host carrying this install's purchase codes and its own address,
 * on every admin page load. The fix is not a policy, it is that the URL is built in one
 * place from three named fields and this test asserts the resulting string character for
 * character. A commit that adds a fourth field - for analytics, for support, for anything -
 * fails here and has to argue its case.
 *
 * The second group is the other half of the same rule: the slug is checked against the list
 * of articles shipped in the download before a URL exists, so a crafted ?slug= cannot make
 * the server fetch somebody else's host.
 */
class HelpDocsTest extends TestCase
{
	/** A stand-in for content/index.json. */
	private function index() {
		return array(
			'customers'          => array( 'title' => 'Customers' ),
			'customers/editing'  => array( 'title' => 'Editing a customer' ),
			'invoices'           => array( 'title' => 'Invoices' ),
		);
	}

	// ---------------------------------------------------------------- the request

	public function test_the_request_url_is_exactly_this_and_nothing_more() {
		$this->assertSame(
			'https://docs.ultimateclientmanager.com/api/v1/customers.json?v=3.556&lang=en',
			ucm_help_docs::api_url( 'customers', '3.556', 'en' )
		);
	}

	public function test_a_nested_slug_keeps_its_slash() {
		$this->assertSame(
			'https://docs.ultimateclientmanager.com/api/v1/customers/editing.json?v=3.556&lang=en',
			ucm_help_docs::api_url( 'customers/editing', '3.556', 'en' )
		);
	}

	/**
	 * Read this as a list of everything that must never appear in the query string. If a
	 * field is added to api_url() this test is the one that notices.
	 */
	public function test_the_query_string_carries_three_fields_and_they_are_these() {
		$url = ucm_help_docs::api_url( 'invoices', '3.556', 'en' );
		$this->assertStringContainsString( '?', $url );
		parse_str( substr( $url, strpos( $url, '?' ) + 1 ), $query );

		$this->assertSame( array( 'v', 'lang' ), array_keys( $query ) );
		$this->assertSame( '3.556', $query['v'] );
		$this->assertSame( 'en', $query['lang'] );
	}

	public function test_the_version_is_cut_back_to_major_and_minor() {
		$this->assertSame( '3.556', ucm_help_docs::version_for_request( '3.556' ) );
		$this->assertSame( '3.5', ucm_help_docs::version_for_request( '3.5.56.1' ) );
		$this->assertSame( '3', ucm_help_docs::version_for_request( '3' ) );
	}

	public function test_a_version_cannot_smuggle_anything_into_the_query_string() {
		$url = ucm_help_docs::api_url( 'customers', '3.5&purchase_code=abc123', 'en' );
		parse_str( substr( $url, strpos( $url, '?' ) + 1 ), $query );

		$this->assertSame( array( 'v', 'lang' ), array_keys( $query ) );
		$this->assertSame( '3.5', $query['v'] );
	}

	public function test_a_language_cannot_smuggle_anything_into_the_query_string() {
		$url = ucm_help_docs::api_url( 'customers', '3.5', 'en&host=example.com' );
		parse_str( substr( $url, strpos( $url, '?' ) + 1 ), $query );

		$this->assertSame( array( 'v', 'lang' ), array_keys( $query ) );
		$this->assertSame( 'en', $query['lang'] );
	}

	/**
	 * The headers, in full. No Referer, no Origin, no Cookie, and a User-Agent that reads
	 * the same on every installation in the world.
	 */
	public function test_the_request_headers_are_exactly_these_two() {
		$this->assertSame(
			array(
				'User-Agent: UCM-Help/1.0',
				'Accept: application/json',
			),
			ucm_help_docs::request_headers()
		);
	}

	public function test_no_header_mentions_a_host_a_licence_or_a_user() {
		foreach ( ucm_help_docs::request_headers() as $header ) {
			foreach ( array( 'referer', 'origin', 'cookie', 'authorization', 'purchase', 'licence', 'license', 'install' ) as $forbidden ) {
				$this->assertStringNotContainsStringIgnoringCase( $forbidden, $header );
			}
		}
	}

	public function test_the_request_only_ever_goes_to_the_documentation_host() {
		$host = parse_url( ucm_help_docs::api_url( 'customers', '3.5', 'en' ), PHP_URL_HOST );

		$this->assertSame( 'docs.ultimateclientmanager.com', $host );
		$this->assertSame( 'https', parse_url( ucm_help_docs::api_url( 'customers', '3.5', 'en' ), PHP_URL_SCHEME ) );
	}

	// ---------------------------------------------------------------- the whitelist

	public function test_a_slug_we_ship_is_recognised() {
		$this->assertTrue( ucm_help_docs::is_known_slug( 'customers', $this->index() ) );
		$this->assertTrue( ucm_help_docs::is_known_slug( 'customers/editing', $this->index() ) );
	}

	public function test_a_slug_we_do_not_ship_is_refused() {
		$this->assertFalse( ucm_help_docs::is_known_slug( 'made-up-article', $this->index() ) );
		$this->assertFalse( ucm_help_docs::is_known_slug( '', $this->index() ) );
	}

	/**
	 * The server-side fetch is the thing that makes an in-app panel possible at all, and
	 * it is also the thing that would make the server fetch an arbitrary URL if the slug
	 * were trusted. These are the shapes that would try it.
	 */
	public function test_a_slug_cannot_escape_to_another_host() {
		$attempts = array(
			'//evil.example.com/a',
			'https://evil.example.com/a',
			'../../etc/passwd',
			'customers/../../../secret',
			'customers.json?x=1&y=2',
			'customers#fragment',
			"customers\r\nX-Injected: yes",
			'169.254.169.254/latest/meta-data',
		);
		foreach ( $attempts as $attempt ) {
			$this->assertFalse(
				ucm_help_docs::is_known_slug( $attempt, $this->index() ),
				'should not be treated as a shipped article: ' . $attempt
			);
			$url = ucm_help_docs::api_url( $attempt, '3.5', 'en' );
			if ( $url ) {
				$this->assertSame(
					'docs.ultimateclientmanager.com',
					parse_url( $url, PHP_URL_HOST ),
					'built a URL pointing somewhere else from: ' . $attempt
				);
			}
		}
	}

	public function test_an_unusable_slug_produces_no_url_at_all() {
		$this->assertSame( '', ucm_help_docs::api_url( '', '3.5', 'en' ) );
		$this->assertSame( '', ucm_help_docs::api_url( '!!!', '3.5', 'en' ) );
		$this->assertSame( '', ucm_help_docs::api_url( str_repeat( 'a', 200 ), '3.5', 'en' ) );
	}

	public function test_the_web_url_for_no_slug_is_the_documentation_index() {
		$this->assertSame( 'https://docs.ultimateclientmanager.com/', ucm_help_docs::web_url( '' ) );
		$this->assertSame( 'https://docs.ultimateclientmanager.com/customers/editing/', ucm_help_docs::web_url( 'customers/editing' ) );
	}

	// ---------------------------------------------------------------- page to article

	public function test_an_exact_screen_wins() {
		$map = array(
			'article' => 'customers',
			'pages'   => array( 'customer-customer_admin_edit' => 'customers/editing' ),
		);

		$this->assertSame( 'customers/editing', ucm_help_docs::slug_for_page( 'customer-customer_admin_edit', $map ) );
	}

	public function test_an_unmapped_screen_falls_back_to_the_plugin_article() {
		$map = array(
			'article' => 'customers',
			'pages'   => array( 'customer-customer_admin_edit' => 'customers/editing' ),
		);

		$this->assertSame( 'customers', ucm_help_docs::slug_for_page( 'customer-customer_admin_list', $map ) );
	}

	public function test_a_plugin_with_no_article_resolves_to_nothing() {
		$this->assertSame( '', ucm_help_docs::slug_for_page( 'customer-customer_admin_list', array() ) );
	}

	public function test_the_plugin_is_read_off_the_front_of_the_page_key() {
		$this->assertSame( 'invoice', ucm_help_docs::module_for_page( 'invoice-invoice_admin_edit' ) );
		$this->assertSame( 'pages', ucm_help_docs::module_for_page( 'pages-home' ) );
		$this->assertSame( '', ucm_help_docs::module_for_page( '../../etc' ) );
	}

	// ---------------------------------------------------------------- what we render

	public function test_script_in_a_fetched_article_does_not_survive() {
		$article = ucm_help_docs::clean_article( array(
			'title' => 'Customers',
			'html'  => '<p>Before</p><script>alert(1)</script><p onclick="alert(2)">After</p>',
		) );

		$this->assertStringNotContainsString( '<script', $article['html'] );
		$this->assertStringNotContainsString( 'alert(1)', $article['html'] );
		$this->assertStringNotContainsString( 'onclick', $article['html'] );
		$this->assertStringContainsString( 'Before', $article['html'] );
		$this->assertStringContainsString( 'After', $article['html'] );
	}

	public function test_a_javascript_link_in_a_fetched_article_is_defused() {
		$article = ucm_help_docs::clean_article( array(
			'title' => 'Customers',
			'html'  => '<a href="javascript:alert(1)">go</a><img src="data:text/html,<script>x</script>">',
		) );

		$this->assertStringNotContainsString( 'javascript:', $article['html'] );
		$this->assertStringNotContainsString( 'data:text/html', $article['html'] );
	}

	/**
	 * An iframe in the article body would be a third party frame inside the admin area,
	 * which is exactly what the in-app panel exists instead of.
	 */
	public function test_an_iframe_in_a_fetched_article_does_not_survive() {
		$article = ucm_help_docs::clean_article( array(
			'title' => 'Customers',
			'html'  => '<p>a</p><iframe src="https://example.com/"></iframe><p>b</p>',
		) );

		$this->assertStringNotContainsString( '<iframe', $article['html'] );
	}

	public function test_fields_we_do_not_render_are_dropped_rather_than_carried() {
		$article = ucm_help_docs::clean_article( array(
			'title'          => 'Customers',
			'html'           => '<p>a</p>',
			'tracking_pixel' => 'https://example.com/p.gif',
			'install_id'     => 'abc123',
		) );

		$this->assertSame(
			array( 'title', 'summary', 'html', 'video', 'updated', 'slug', 'stale' ),
			array_keys( $article )
		);
	}

	public function test_an_empty_response_is_not_an_article() {
		$this->assertFalse( ucm_help_docs::clean_article( array() ) );
		$this->assertFalse( ucm_help_docs::clean_article( 'not an array' ) );
		$this->assertFalse( ucm_help_docs::clean_article( array( 'title' => '', 'html' => '' ) ) );
	}

	public function test_a_video_is_linked_at_the_no_cookie_host() {
		$this->assertSame(
			'https://www.youtube-nocookie.com/watch?v=dQw4w9WgXcQ',
			ucm_help_docs::video_url( 'dQw4w9WgXcQ' )
		);
		$this->assertSame( '', ucm_help_docs::video_url( '' ) );
		$this->assertSame( '', ucm_help_docs::video_url( 'https://evil.example.com/' ) );
	}
}
