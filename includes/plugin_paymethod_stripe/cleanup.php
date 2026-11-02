<?php

/**
 * One-off clean-up of files an older release shipped. Standalone, so it can be tested without
 * loading the whole application.
 */
class ucm_stripe_cleanup
{
	/**
	 * Delete includes/plugin_paymethod_stripe/stripe-php, the dead 2014 copy of stripe-php.
	 *
	 * Only that one folder, and only when it really is a folder inside this plugin: the real
	 * path of the plugin folder must be exactly <root>/includes/plugin_paymethod_stripe (so a
	 * symlinked includes/ or plugin folder is left alone), and a symlink at the target is never
	 * followed. Failures are ignored; a folder that cannot be removed is merely dead weight.
	 *
	 * @param string|null $root Install root, defaults to this install. Tests pass a temp dir.
	 *
	 * @return bool True when the folder is gone afterwards.
	 */
	public static function remove_bundled_stripe_php( $root = null ) {
		if ( null === $root ) {
			$root = _UCM_FOLDER;
		}
		$real_root  = realpath( $root );
		$plugin_dir = realpath( rtrim( $root, '/\\' ) . '/includes/plugin_paymethod_stripe' );
		if ( false === $real_root || false === $plugin_dir ) {
			return false;
		}
		if ( $plugin_dir !== $real_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin_paymethod_stripe' ) {
			return false;
		}
		$target = $plugin_dir . DIRECTORY_SEPARATOR . 'stripe-php';
		if ( is_link( $target ) ) {
			return false;
		}
		if ( ! file_exists( $target ) ) {
			return true;
		}
		if ( ! is_dir( $target ) || realpath( $target ) !== $target ) {
			return false;
		}
		self::remove_tree( $target );
		clearstatcache();

		return ! file_exists( $target );
	}

	/**
	 * Delete includes/plugin_paymethod_stripe/pages/stripe_form.php, the old Charges form.
	 *
	 * It needs the stripe-php copy deleted above, so left behind it would answer with a blank
	 * server error. Same containment rules as the folder: the plugin folder must really be
	 * <root>/includes/plugin_paymethod_stripe, only a plain file is removed, never a link.
	 *
	 * @param string|null $root Install root, defaults to this install. Tests pass a temp dir.
	 *
	 * @return bool True when the file is gone afterwards.
	 */
	public static function remove_old_charges_form( $root = null ) {
		if ( null === $root ) {
			$root = _UCM_FOLDER;
		}
		$real_root  = realpath( $root );
		$plugin_dir = realpath( rtrim( $root, '/\\' ) . '/includes/plugin_paymethod_stripe' );
		if ( false === $real_root || false === $plugin_dir ) {
			return false;
		}
		if ( $plugin_dir !== $real_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin_paymethod_stripe' ) {
			return false;
		}
		$target = $plugin_dir . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . 'stripe_form.php';
		if ( is_link( $target ) ) {
			return false;
		}
		if ( is_file( $target ) ) {
			@unlink( $target );
			clearstatcache();
		}

		return ! file_exists( $target );
	}

	/**
	 * Delete a folder and everything in it without following symbolic links.
	 *
	 * @param string $dir absolute folder path, already checked by the caller
	 */
	private static function remove_tree( $dir ) {
		$entries = @scandir( $dir );
		if ( false === $entries ) {
			return;
		}
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			if ( is_link( $path ) || ! is_dir( $path ) ) {
				@unlink( $path );
			} else {
				self::remove_tree( $path );
			}
		}
		@rmdir( $dir );
	}
}
