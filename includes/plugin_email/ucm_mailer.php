<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Builds the PHPMailer message for one stored email, and tidies up after the old bundled
 * PHPMailer.
 *
 * It lives apart from the email plugin so it can be tested without the whole app: it needs
 * nothing but PHPMailer, and it never sends, never touches the database and never reads
 * module_config. The plugin reads the saved settings and hands them over as plain arrays.
 */
class ucm_mailer
{
	/**
	 * The files of the PHPMailer 5.2.21 that used to ship inside the plugin folder. The
	 * updater never deletes files, so an install that updates over the top keeps them
	 * unless something removes them.
	 *
	 * @return array paths relative to the plugin folder
	 */
	public static function legacy_files() {
		return array(
			'class.phpmailer.php',
			'class.smtp.php',
			'class.pop3.php',
			'language/phpmailer.lang-en.php',
		);
	}

	/**
	 * Delete the old bundled PHPMailer from a plugin folder.
	 *
	 * Deletes only the exact files in legacy_files(), and only when the real path of each
	 * sits inside $plugin_dir. A symlink pointing somewhere else is left alone. Failures
	 * (read-only hosting, permissions) are ignored: the files are dead weight once nothing
	 * loads them, and this is retried on the next upgrade.
	 *
	 * @param string $plugin_dir the plugin_email folder
	 *
	 * @return array the relative paths that were removed
	 */
	public static function remove_legacy_files( $plugin_dir ) {
		$removed = array();
		$root    = realpath( $plugin_dir );
		if ( $root === false || ! is_dir( $root ) ) {
			return $removed;
		}
		$root = rtrim( $root, '/\\' ) . DIRECTORY_SEPARATOR;
		foreach ( self::legacy_files() as $relative ) {
			$path = $root . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
			if ( is_link( $path ) || ! is_file( $path ) ) {
				continue;
			}
			$real = realpath( $path );
			if ( $real === false || strpos( $real, $root ) !== 0 ) {
				continue;
			}
			if ( @unlink( $real ) ) {
				$removed[] = $relative;
			}
		}
		// The language folder only ever held that one file. Remove it if it is now empty.
		$language_dir = $root . 'language';
		if ( is_dir( $language_dir ) && ! is_link( $language_dir ) && count( (array) scandir( $language_dir ) ) === 2 ) {
			@rmdir( $language_dir );
		}

		return $removed;
	}

	/**
	 * Turn one stored email into a PHPMailer message, ready for send() (or preSend() in a
	 * test). Does not send.
	 *
	 * $settings keys, all optional: smtp (bool), hostname, secure ('', 'ssl', 'tls'),
	 * authenticate (bool), username, password, ignore_certificate (bool).
	 *
	 * @param array $email_data   a row of the email table: message_id, subject, html_content, text_content
	 * @param array $headers      the unserialized headers: Sender, ReplyToEmail, ReplyToName,
	 *                            FromEmail, FromName, to, cc, bcc (lists of email/name pairs)
	 * @param array $attachments  file path => file name. A path starting with temp_ is a
	 *                            scheduled email's attachment and is found in $temp_folder.
	 * @param array $settings     see above
	 * @param string $temp_folder where scheduled attachments are kept
	 *
	 * @return PHPMailer
	 */
	public static function build( array $email_data, array $headers, array $attachments, array $settings, $temp_folder = '' ) {
		// Exceptions off, as before: a problem comes back from send() as false with the
		// reason in ErrorInfo, rather than escaping into the page.
		// PHPMailer 5 checked recipients with a lenient pattern (admin@localhost and
		// user@intranet were fine) and never checked From or the bounce address. PHPMailer 6
		// checks all three with PHP's stricter filter, which would stop working installs
		// sending. Keep the old leniency; newlines and anything that could split an SMTP
		// command are still refused.
		PHPMailer::$validator = array( __CLASS__, 'valid_address' );

		$mail          = new PHPMailer( false );
		$mail->CharSet = PHPMailer::CHARSET_UTF8;
		$mail->isHTML( true );

		if ( ! empty( $settings['smtp'] ) ) {
			$mail->isSMTP();
			$mail->SMTPSecure = isset( $settings['secure'] ) ? (string) $settings['secure'] : '';
			$mail->SMTPAuth   = ! empty( $settings['authenticate'] );
			$mail->Host       = isset( $settings['hostname'] ) ? (string) $settings['hostname'] : '';
			if ( $mail->SMTPAuth ) {
				$mail->Username = isset( $settings['username'] ) ? (string) $settings['username'] : '';
				$mail->Password = isset( $settings['password'] ) ? (string) $settings['password'] : '';
			}
			if ( ! empty( $settings['ignore_certificate'] ) ) {
				$mail->SMTPOptions = array(
					'ssl' => array(
						'verify_peer'       => false,
						'verify_peer_name'  => false,
						'allow_self_signed' => true,
					),
				);
			}
		} else {
			$mail->isMail();
		}

		$from_email = isset( $headers['FromEmail'] ) ? (string) $headers['FromEmail'] : '';

		$message_id = isset( $email_data['message_id'] ) ? trim( (string) $email_data['message_id'] ) : '';
		if ( $message_id !== '' ) {
			$mail->MessageID = self::message_id( $message_id, $from_email );
		}
		$mail->Subject = isset( $email_data['subject'] ) ? (string) $email_data['subject'] : '';
		$mail->Body    = isset( $email_data['html_content'] ) ? (string) $email_data['html_content'] : '';
		$mail->AltBody = isset( $email_data['text_content'] ) ? (string) $email_data['text_content'] : '';

		// The envelope sender, where bounces go.
		$mail->Sender = ! empty( $headers['Sender'] ) ? (string) $headers['Sender'] : '';
		if ( isset( $headers['ReplyToEmail'] ) ) {
			$mail->addReplyTo( (string) $headers['ReplyToEmail'], isset( $headers['ReplyToName'] ) ? (string) $headers['ReplyToName'] : '' );
		}
		// Set directly rather than through setFrom(): the address was accepted when the
		// email was queued, and setFrom() would also quietly replace the bounce address.
		$mail->From     = $from_email;
		$mail->FromName = isset( $headers['FromName'] ) ? (string) $headers['FromName'] : '';

		foreach ( array( 'to' => 'addAddress', 'cc' => 'addCC', 'bcc' => 'addBCC' ) as $field => $method ) {
			if ( ! empty( $headers[ $field ] ) && is_array( $headers[ $field ] ) ) {
				foreach ( $headers[ $field ] as $recipient ) {
					if ( ! is_array( $recipient ) || empty( $recipient['email'] ) ) {
						continue;
					}
					$mail->$method( (string) $recipient['email'], isset( $recipient['name'] ) ? (string) $recipient['name'] : '' );
				}
			}
		}

		foreach ( $attachments as $file_path => $file_name ) {
			$file_path = (string) $file_path;
			if ( strpos( $file_path, 'temp_' ) === 0 && $temp_folder !== '' && is_file( $temp_folder . basename( $file_path ) ) ) {
				$mail->addAttachment( $temp_folder . basename( $file_path ), (string) $file_name );
			} elseif ( is_file( $file_path ) ) {
				$mail->addAttachment( $file_path, (string) $file_name );
			}
		}

		return $mail;
	}

	/**
	 * PHPMailer 5 wrote the stored message id into the Message-ID header exactly as given,
	 * so the newsletter bounce ids ("Newsletter-1-2-3-<hash>") went out with no angle
	 * brackets and no host. PHPMailer 6 throws away anything that is not <local@host> and
	 * invents its own, which would stop newsletter bounces being matched. Wrap the id so
	 * it survives, keeping the original text at the start of the header.
	 *
	 * @param string $message_id
	 * @param string $from_email used to pick a host name
	 *
	 * @return string
	 */
	public static function message_id( $message_id, $from_email = '' ) {
		$message_id = trim( $message_id );
		if ( preg_match( '/^<[^<>\s@]+@[^<>\s@]+>$/', $message_id ) ) {
			return $message_id;
		}
		$message_id = trim( $message_id, '<>' );
		$host       = 'localhost.localdomain';
		$at         = strrpos( $from_email, '@' );
		if ( $at !== false && preg_match( '/^[A-Za-z0-9.-]+$/', substr( $from_email, $at + 1 ) ) ) {
			$host = substr( $from_email, $at + 1 );
		}
		if ( strpos( $message_id, '@' ) !== false ) {
			return '<' . $message_id . '>';
		}

		return '<' . $message_id . '@' . $host . '>';
	}

	/**
	 * Is this an address worth handing to the mail server? Everything the lenient PHPMailer
	 * 5 pattern accepted, plus plain local@host shapes such as webmaster@localhost:8090 that
	 * it never looked at (it did not validate From). No whitespace, control characters,
	 * angle brackets, quotes or commas outside the quoted-local form the pattern allows.
	 *
	 * @param string $address
	 *
	 * @return bool
	 */
	public static function valid_address( $address ) {
		$address = (string) $address;
		if ( strpbrk( $address, "\r\n\0" ) !== false ) {
			return false;
		}
		if ( PHPMailer::validateAddress( $address, 'pcre8' ) ) {
			return true;
		}

		return (bool) preg_match( '/^[^\s<>",;:\\\\\x00-\x1f\x7f@]+@[^\s<>",;\\\\\x00-\x1f\x7f@\[\]]+$/D', $address );
	}

	/**
	 * May this value, which arrives from the browser in the compose form's options, be
	 * called once the email has gone? Only the "Class::method" name of a module's own
	 * email_sent hook (module_invoice::email_sent, module_file::email_sent) that really
	 * exists. A code string, a function name, a closure or an array is refused.
	 *
	 * @param mixed $callback
	 *
	 * @return bool
	 */
	public static function is_success_callback( $callback ) {
		return is_string( $callback )
		       && preg_match( '/^module_[a-z0-9_]+::[a-z0-9_]*email_sent$/i', $callback ) === 1
		       && is_callable( $callback );
	}
}
