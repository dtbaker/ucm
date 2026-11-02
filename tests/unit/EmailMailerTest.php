<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

/**
 * The email plugin builds its messages with PHPMailer 6 from Composer.
 *
 * Nothing here sends: preSend() assembles the message exactly as send() would, and
 * getSentMIMEMessage() hands back the bytes that would go on the wire. The one test that
 * does try to send points at a port nothing listens on, to prove a failure comes back as
 * false with a reason rather than as an exception or a fatal.
 */
class EmailMailerTest extends TestCase
{
	/** @var string */
	private $dir = '';

	public static function setUpBeforeClass(): void {
		require_once _UCM_FOLDER . 'vendor/autoload.php';
		require_once _UCM_FOLDER . 'includes/plugin_email/ucm_mailer.php';
	}

	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/ucm-mailer-test-' . bin2hex( random_bytes( 6 ) );
		mkdir( $this->dir, 0777, true );
	}

	protected function tearDown(): void {
		$this->remove_tree( $this->dir );
	}

	private function remove_tree( $path ) {
		if ( is_link( $path ) || is_file( $path ) ) {
			@unlink( $path );

			return;
		}
		if ( is_dir( $path ) ) {
			foreach ( scandir( $path ) as $entry ) {
				if ( $entry !== '.' && $entry !== '..' ) {
					$this->remove_tree( $path . '/' . $entry );
				}
			}
			@rmdir( $path );
		}
	}

	private function email_data( array $override = array() ) {
		return $override + array(
			'message_id'   => '',
			'subject'      => 'Invoice I-1001',
			'html_content' => '<p>Hello <b>Pat</b></p>',
			'text_content' => 'Hello Pat',
		);
	}

	private function headers( array $override = array() ) {
		return $override + array(
			'Sender'       => 'bounces@example.com',
			'FromEmail'    => 'billing@example.com',
			'FromName'     => 'Example Billing',
			'ReplyToEmail' => 'support@example.com',
			'ReplyToName'  => 'Example Support',
			'to'           => array( array( 'email' => 'pat@example.net', 'name' => 'Pat Customer' ) ),
			'cc'           => array( array( 'email' => 'cc@example.net', 'name' => '' ) ),
			'bcc'          => array( array( 'email' => 'bcc@example.net', 'name' => '' ) ),
		);
	}

	private function mime( PHPMailer $mail ) {
		$this->assertTrue( $mail->preSend(), 'preSend failed: ' . $mail->ErrorInfo );

		return $mail->getSentMIMEMessage();
	}

	/**
	 * The header block of a raw message, with folded lines joined back together.
	 */
	private function header_block( $mime ) {
		$parts = preg_split( '/\r?\n\r?\n/', $mime, 2 );

		return preg_replace( '/\r?\n[ \t]+/', ' ', $parts[0] );
	}

	public function test_composer_provides_phpmailer_6_and_the_bundled_copy_is_gone() {
		$this->assertTrue( version_compare( PHPMailer::VERSION, '6.9.0', '>=' ), 'PHPMailer ' . PHPMailer::VERSION );
		foreach ( ucm_mailer::legacy_files() as $relative ) {
			$this->assertFileDoesNotExist( _UCM_FOLDER . 'includes/plugin_email/' . $relative );
		}

		ucm_libraries::register( 'phpmailer', array(
			'name'    => 'PHPMailer',
			'class'   => 'PHPMailer\\PHPMailer\\PHPMailer',
			'bundled' => '',
		) );
		$this->assertTrue( ucm_libraries::have( 'phpmailer' ) );
		$this->assertSame( 'composer', ucm_libraries::source( 'phpmailer' ) );
	}

	public function test_html_with_a_text_alternative_is_multipart_alternative() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array() );
		$mime = $this->mime( $mail );
		$head = $this->header_block( $mime );

		$this->assertMatchesRegularExpression( '/^Content-Type: multipart\/alternative;/mi', $head );
		$this->assertMatchesRegularExpression( '/^MIME-Version: 1\.0/mi', $head );
		$this->assertMatchesRegularExpression( '/Content-Type: text\/plain; charset=(UTF-8|us-ascii)/i', $mime );
		$this->assertMatchesRegularExpression( '/Content-Type: text\/html; charset=(UTF-8|us-ascii)/i', $mime );
		$this->assertStringContainsString( '<p>Hello <b>Pat</b></p>', $mime );
		$this->assertStringContainsString( 'Hello Pat', $mime );
	}

	public function test_html_without_text_is_a_single_html_part() {
		$mail = ucm_mailer::build( $this->email_data( array( 'text_content' => '' ) ), $this->headers(), array(), array() );
		$head = $this->header_block( $this->mime( $mail ) );

		$this->assertMatchesRegularExpression( '/^Content-Type: text\/html; charset=(UTF-8|us-ascii)/mi', $head );
		$this->assertStringNotContainsString( 'multipart', $head );
	}

	public function test_from_reply_to_cc_and_recipients_are_in_the_right_places() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array( 'smtp' => true, 'hostname' => 'localhost' ) );
		$head = $this->header_block( $this->mime( $mail ) );

		$this->assertMatchesRegularExpression( '/^From: Example Billing <billing@example\.com>/mi', $head );
		$this->assertMatchesRegularExpression( '/^Reply-To: Example Support <support@example\.com>/mi', $head );
		$this->assertMatchesRegularExpression( '/^To: Pat Customer <pat@example\.net>/mi', $head );
		$this->assertMatchesRegularExpression( '/^Cc: cc@example\.net/mi', $head );
		$this->assertMatchesRegularExpression( '/^Subject: Invoice I-1001/mi', $head );
		// A blind copy is a recipient on the envelope, never a header the others can read.
		$this->assertStringNotContainsString( 'bcc@example.net', $head );
		$this->assertSame( array( 'bcc@example.net' ), array_column( $mail->getBccAddresses(), 0 ) );
	}

	public function test_the_bounce_address_is_the_envelope_sender_not_the_from() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array() );
		$this->mime( $mail );

		$this->assertSame( 'bounces@example.com', $mail->Sender );
		$this->assertSame( 'billing@example.com', $mail->From );
	}

	public function test_no_bounce_address_leaves_the_sender_empty() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers( array( 'Sender' => false ) ), array(), array() );

		$this->assertSame( '', $mail->Sender );
	}

	public function test_non_ascii_text_survives() {
		$mail = ucm_mailer::build(
			$this->email_data( array( 'subject' => 'Rechnung für Zoë – 日本', 'html_content' => '<p>Grüße 日本語</p>', 'text_content' => 'Grüße' ) ),
			$this->headers( array( 'FromName' => 'Zoë Ünal' ) ),
			array(),
			array()
		);
		$head = $this->header_block( $this->mime( $mail ) );

		$this->assertMatchesRegularExpression( '/^Subject: =\?UTF-8\?/mi', $head );
		$this->assertMatchesRegularExpression( '/^From: =\?UTF-8\?/mi', $head );
		$this->assertSame( 'utf-8', strtolower( $mail->CharSet ) );
		$this->assertStringContainsString( 'Grüße 日本語', $mail->Body );
	}

	public function test_an_attachment_makes_the_message_multipart_mixed_with_the_filename() {
		$file = $this->dir . '/Invoice_abc.pdf';
		file_put_contents( $file, "%PDF-1.4\nfake pdf body\n" );

		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array( $file => 'Invoice I-1001.pdf' ), array() );
		$mime = $this->mime( $mail );
		$head = $this->header_block( $mime );

		$this->assertMatchesRegularExpression( '/^Content-Type: multipart\/mixed;/mi', $head );
		// The body inside is still the HTML + text alternative.
		$this->assertStringContainsString( 'multipart/alternative', $mime );
		$this->assertMatchesRegularExpression( '/Content-Disposition: attachment; filename="Invoice I-1001\.pdf"/i', $mime );
		$this->assertMatchesRegularExpression( '/Content-Type: application\/pdf; name="Invoice I-1001\.pdf"/i', $mime );
		$this->assertStringContainsString( base64_encode( "%PDF-1.4\nfake pdf body\n" ), str_replace( array( "\r", "\n" ), '', $mime ) );
		$this->assertCount( 1, $mail->getAttachments() );
	}

	public function test_a_scheduled_emails_temp_attachment_is_found_in_the_temp_folder() {
		file_put_contents( $this->dir . '/temp_7_abc', 'kept for later' );

		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array( 'temp_7_abc' => 'notes.txt', 'temp_7_gone' => 'gone.txt' ), array(), $this->dir . '/' );
		$this->mime( $mail );

		$attached = $mail->getAttachments();
		$this->assertCount( 1, $attached );
		$this->assertSame( 'notes.txt', $attached[0][2] );
	}

	public function test_a_missing_attachment_is_skipped_not_fatal() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array( $this->dir . '/nope.pdf' => 'nope.pdf' ), array() );
		$this->mime( $mail );

		$this->assertCount( 0, $mail->getAttachments() );
	}

	public function test_smtp_settings_are_applied() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array(
			'smtp'               => true,
			'hostname'           => 'mail.example.com:465',
			'secure'             => 'ssl',
			'authenticate'       => '1',
			'username'           => 'user',
			'password'           => 'secret',
			'ignore_certificate' => '1',
		) );

		$this->assertSame( 'smtp', $mail->Mailer );
		$this->assertSame( 'mail.example.com:465', $mail->Host );
		$this->assertSame( 'ssl', $mail->SMTPSecure );
		$this->assertTrue( $mail->SMTPAuth );
		$this->assertSame( 'user', $mail->Username );
		$this->assertSame( 'secret', $mail->Password );
		$this->assertFalse( $mail->SMTPOptions['ssl']['verify_peer'] );
		$this->assertTrue( $mail->SMTPAutoTLS );
	}

	public function test_smtp_without_auth_or_security_does_not_send_credentials() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array(
			'smtp'     => true,
			'hostname' => 'localhost:1025',
			'secure'   => '',
			'username' => 'ignored',
			'password' => 'ignored',
		) );

		$this->assertFalse( $mail->SMTPAuth );
		$this->assertSame( '', $mail->SMTPSecure );
		$this->assertSame( '', $mail->Username );
		$this->assertSame( '', $mail->Password );
		$this->assertSame( array(), $mail->SMTPOptions );
	}

	public function test_without_smtp_it_uses_the_php_mail_function() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array( 'smtp' => false ) );

		$this->assertSame( 'mail', $mail->Mailer );
	}

	public function test_a_newsletter_bounce_message_id_still_reaches_the_header() {
		$id   = 'Newsletter-4-9-123-' . md5( 'x' );
		$mail = ucm_mailer::build( $this->email_data( array( 'message_id' => $id ) ), $this->headers(), array(), array() );
		$head = $this->header_block( $this->mime( $mail ) );

		// Matches the pattern the bounce checker looks for in returned mail.
		$this->assertMatchesRegularExpression( '/Message-ID:\s*<?Newsletter-(\d+)-(\d+)-(\d+)-([A-Fa-f0-9]{32})/i', $head );
		$this->assertMatchesRegularExpression( '/^Message-ID: <' . preg_quote( $id, '/' ) . '@example\.com>/mi', $head );
	}

	public function test_message_id_forms() {
		$this->assertSame( '<abc@host.test>', ucm_mailer::message_id( '<abc@host.test>', 'a@b.test' ) );
		$this->assertSame( '<abc@host.test>', ucm_mailer::message_id( 'abc@host.test', 'a@b.test' ) );
		$this->assertSame( '<abc@b.test>', ucm_mailer::message_id( 'abc', 'a@b.test' ) );
		$this->assertSame( '<abc@localhost.localdomain>', ucm_mailer::message_id( 'abc', '' ) );
		$this->assertSame( '<abc@localhost.localdomain>', ucm_mailer::message_id( 'abc', 'a@bad host' ) );
	}

	public function test_no_message_id_gets_a_generated_one() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array() );
		$head = $this->header_block( $this->mime( $mail ) );

		$this->assertMatchesRegularExpression( '/^Message-ID: <[^>]+@[^>]+>/mi', $head );
	}

	public function test_junk_in_the_stored_recipient_lists_is_ignored() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers( array(
			'to' => array( 'not-an-array', array( 'name' => 'No Address' ), array( 'email' => 'ok@example.net' ) ),
		) ), array(), array() );
		$this->mime( $mail );

		$this->assertSame( array( 'ok@example.net' ), array_column( $mail->getToAddresses(), 0 ) );
	}

	public function test_a_message_with_no_recipient_fails_cleanly() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers( array( 'to' => array(), 'cc' => array(), 'bcc' => array() ) ), array(), array() );

		$this->assertFalse( $mail->send() );
		$this->assertNotSame( '', $mail->ErrorInfo );
	}

	public function test_an_unreachable_smtp_server_fails_with_a_reason_and_no_exception() {
		$mail          = ucm_mailer::build( $this->email_data(), $this->headers(), array(), array( 'smtp' => true, 'hostname' => '127.0.0.1:1' ) );
		$mail->Timeout = 3;

		$this->assertFalse( $mail->send() );
		$this->assertStringContainsString( 'SMTP', $mail->ErrorInfo );
	}

	public function test_addresses_the_old_phpmailer_let_through_still_work() {
		// A dotless host, and a From that is not strictly an address at all. PHPMailer 5
		// never checked the From; PHPMailer 6 would refuse both without ucm_mailer's validator.
		$mail = ucm_mailer::build(
			$this->email_data(),
			$this->headers( array(
				'FromEmail' => 'info@localhost:8090',
				'Sender'    => 'bounces@intranet',
				'to'        => array( array( 'email' => 'admin@localhost', 'name' => '' ) ),
			) ),
			array(),
			array()
		);
		$head = $this->header_block( $this->mime( $mail ) );

		$this->assertMatchesRegularExpression( '/^From: Example Billing <info@localhost:8090>/mi', $head );
		$this->assertMatchesRegularExpression( '/^To: admin@localhost/mi', $head );
	}

	public function test_addresses_that_could_split_a_header_or_smtp_command_are_refused() {
		foreach ( array( "a@b.com\r\nBcc: victim@example.net", "a@b.com\nX: y", 'a b@example.net', '<a@b.com>', 'a@b.com,c@d.com', 'a@b.com>', '@b.com', 'a@' ) as $bad ) {
			$this->assertFalse( ucm_mailer::valid_address( $bad ), json_encode( $bad ) );
		}
		foreach ( array( 'pat@example.net', 'pat+tag@example.net', 'admin@localhost', 'a@192.168.0.1' ) as $good ) {
			$this->assertTrue( ucm_mailer::valid_address( $good ), $good );
		}

		$mail = ucm_mailer::build( $this->email_data(), $this->headers( array( 'FromEmail' => "a@b.com\r\nBcc: victim@example.net" ) ), array(), array() );
		$this->assertFalse( $mail->preSend() );
		$this->assertStringContainsString( 'Invalid address', $mail->ErrorInfo );
	}

	public function test_an_invalid_recipient_address_is_rejected_without_sending() {
		$mail = ucm_mailer::build( $this->email_data(), $this->headers( array( 'to' => array( array( 'email' => 'not an address', 'name' => '' ) ), 'cc' => array(), 'bcc' => array() ) ), array(), array( 'smtp' => true, 'hostname' => '127.0.0.1:1' ) );

		$this->assertFalse( $mail->send() );
		$this->assertCount( 0, $mail->getToAddresses() );
	}

	// ---- the clean-up of the old bundled PHPMailer -------------------------------------

	private function make_old_install( $root ) {
		mkdir( $root . '/language', 0777, true );
		foreach ( ucm_mailer::legacy_files() as $relative ) {
			file_put_contents( $root . '/' . $relative, "<?php // old\n" );
		}
		file_put_contents( $root . '/email.php', "<?php // plugin\n" );
		file_put_contents( $root . '/class.keep_me.php', "<?php // not ours\n" );
	}

	public function test_cleanup_deletes_exactly_the_old_files() {
		$root = $this->dir . '/plugin_email';
		$this->make_old_install( $root );

		$removed = ucm_mailer::remove_legacy_files( $root );

		$this->assertEqualsCanonicalizing( ucm_mailer::legacy_files(), $removed );
		foreach ( ucm_mailer::legacy_files() as $relative ) {
			$this->assertFileDoesNotExist( $root . '/' . $relative );
		}
		$this->assertFileExists( $root . '/email.php' );
		$this->assertFileExists( $root . '/class.keep_me.php' );
		$this->assertDirectoryDoesNotExist( $root . '/language' );
	}

	public function test_cleanup_keeps_a_language_folder_that_holds_other_files() {
		$root = $this->dir . '/plugin_email';
		$this->make_old_install( $root );
		file_put_contents( $root . '/language/custom.php', "<?php // a buyer's own\n" );

		ucm_mailer::remove_legacy_files( $root );

		$this->assertFileExists( $root . '/language/custom.php' );
	}

	public function test_cleanup_is_safe_to_repeat_and_on_a_missing_folder() {
		$root = $this->dir . '/plugin_email';
		$this->make_old_install( $root );

		$this->assertNotEmpty( ucm_mailer::remove_legacy_files( $root ) );
		$this->assertSame( array(), ucm_mailer::remove_legacy_files( $root ) );
		$this->assertSame( array(), ucm_mailer::remove_legacy_files( $this->dir . '/does-not-exist' ) );
	}

	public function test_cleanup_never_follows_a_link_out_of_the_plugin_folder() {
		$root    = $this->dir . '/plugin_email';
		$outside = $this->dir . '/elsewhere';
		mkdir( $root, 0777, true );
		mkdir( $outside, 0777, true );
		file_put_contents( $outside . '/precious.php', "<?php // do not delete\n" );
		if ( ! @symlink( $outside . '/precious.php', $root . '/class.phpmailer.php' ) ) {
			$this->markTestSkipped( 'Symlinks are not available here.' );
		}
		// And a language folder that is really somewhere else.
		symlink( $outside, $root . '/language' );
		file_put_contents( $outside . '/phpmailer.lang-en.php', "<?php // also precious\n" );

		$removed = ucm_mailer::remove_legacy_files( $root );

		$this->assertSame( array(), $removed );
		$this->assertFileExists( $outside . '/precious.php' );
		$this->assertFileExists( $outside . '/phpmailer.lang-en.php' );
	}

	public function test_cleanup_ignores_a_file_it_cannot_delete() {
		if ( function_exists( 'posix_geteuid' ) && posix_geteuid() === 0 ) {
			$this->markTestSkipped( 'Root can delete from a read-only folder.' );
		}
		$root = $this->dir . '/plugin_email';
		$this->make_old_install( $root );
		chmod( $root, 0555 );
		chmod( $root . '/language', 0555 );

		try {
			$removed = ucm_mailer::remove_legacy_files( $root );
		} finally {
			chmod( $root, 0777 );
			chmod( $root . '/language', 0777 );
		}

		$this->assertSame( array(), $removed );
		$this->assertFileExists( $root . '/class.phpmailer.php' );
	}
}
