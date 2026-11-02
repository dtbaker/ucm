<?php

use PHPUnit\Framework\TestCase;

/**
 * A newsletter template body is stored in the database and edited in the admin. It is merged
 * with {TAGS} and never run as PHP: an old eval() of the body sat in a comment, and has been
 * removed so nothing can switch it back on by accident.
 */
class NewsletterTemplateTest extends TestCase
{
	private function root() {
		return dirname( __DIR__, 2 ) . '/';
	}

	/** @return string[] */
	private function newsletter_php_files() {
		$files    = array();
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $this->root() . 'includes/plugin_newsletter', FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $info ) {
			if ( 'php' === $info->getExtension() ) {
				$files[] = $info->getPathname();
			}
		}

		return $files;
	}

	public function test_no_eval_in_the_newsletter_plugin() {
		$problems = array();
		foreach ( $this->newsletter_php_files() as $file ) {
			$code = file_get_contents( $file );
			foreach ( token_get_all( $code ) as $token ) {
				if ( is_array( $token ) && T_EVAL === $token[0] ) {
					$problems[] = basename( $file ) . ':' . $token[2];
				}
			}
			// Comments count too: a reviewer's grep does not know the difference.
			if ( preg_match( '/\beval\s*\(/i', $code ) ) {
				$problems[] = basename( $file ) . ' mentions eval(';
			}
		}
		$this->assertSame( array(), $problems );
	}

	public function test_render_merges_tags_with_str_replace_and_never_runs_the_body() {
		$code = file_get_contents( $this->root() . 'includes/plugin_newsletter/newsletter.php' );
		$this->assertStringContainsString( "\$template_html = \$template['body'];", $code );
		$this->assertStringContainsString( "str_replace( '{' . strtoupper( \$key ) . '}', \$val, \$final_newsletter_html )", $code );
		// The body is not passed to include/require/eval/file_put_contents on the way.
		$this->assertDoesNotMatchRegularExpression( '/(include|require)(_once)?\s*\(?\s*\$template\[.body.\]/', $code );
	}

	public function test_php_in_a_template_body_is_left_as_plain_text_by_the_merge() {
		// The merge step in render() is str_replace over {TAG} keys; reproduce it on a hostile body.
		$body    = "<p>Hi {FIRST_NAME}</p><?php echo 'ran'; ?><?= 'ran2' ?>{BODY}";
		$replace = array( 'FIRST_NAME' => 'Sam', 'BODY' => 'content' );
		$html    = $body;
		for ( $x = 0; $x < 2; $x++ ) {
			foreach ( $replace as $key => $val ) {
				$html = str_replace( '{' . strtoupper( $key ) . '}', $val, $html );
			}
		}
		$this->assertSame( "<p>Hi Sam</p><?php echo 'ran'; ?><?= 'ran2' ?>content", $html );
	}

	public function test_shipped_templates_use_only_tags_and_files_in_their_own_folder() {
		$dir = $this->root() . 'includes/plugin_newsletter/email_template/';
		foreach ( glob( $dir . '*/template.html' ) as $file ) {
			$this->assertStringNotContainsString( '<?', file_get_contents( $file ), $file . ' embeds PHP in the template body' );
		}
		$this->assertNotEmpty( glob( $dir . '*/template.html' ) );
	}
}
