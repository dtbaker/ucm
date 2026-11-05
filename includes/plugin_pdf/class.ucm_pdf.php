<?php

/**
 * Turns HTML into a PDF with dompdf, and looks after dompdf's working files.
 *
 * dompdf comes from Composer (vendor/), not from a copy inside this plugin, so it keeps
 * receiving security fixes. Everything that decides how it is configured lives here
 * rather than in module_pdf, so it can be tested without the rest of the application.
 */
class ucm_pdf
{
	/**
	 * The ucm_libraries declaration for dompdf. Composer only: there is no bundled copy to
	 * fall back to any more, so a missing vendor/ shows the normal "component not
	 * installed" message instead of quietly running something old.
	 *
	 * @return array
	 */
	public static function library() {
		return array(
			'name'       => 'dompdf',
			'package'    => 'dompdf/dompdf',
			'constraint' => '^3.1',
			'class'      => 'Dompdf\\Dompdf',
			'bundled'    => '',
			'feature'    => 'PDF generation for invoices, quotes and contracts',
		);
	}

	/**
	 * A writable folder for dompdf's font metrics cache and temporary image files, created
	 * the first time it is needed.
	 *
	 * The fonts dompdf ships are read straight from vendor/ and never written to; this
	 * folder only holds what dompdf generates (font metrics for TrueType fonts, converted
	 * images), so vendor/ can stay read-only. It sits inside temp/, whose .htaccess already
	 * refuses to serve anything but stylesheets and scripts, and it is created with
	 * ordinary 0755 permissions. If temp/ cannot be written (some shared hosts), the
	 * system temp folder is tried, in a name only this install uses.
	 *
	 * @return string|false Absolute path without trailing slash, or false when nowhere is writable.
	 */
	public static function work_dir() {
		$storage    = defined( '_UCM_FILE_STORAGE_DIR' ) ? _UCM_FILE_STORAGE_DIR : _UCM_FOLDER;
		$candidates = array(
			rtrim( $storage, '/\\' ) . '/temp/dompdf',
			rtrim( sys_get_temp_dir(), '/\\' ) . '/ucm-dompdf-' . md5( _UCM_FOLDER ),
		);
		foreach ( $candidates as $dir ) {
			if ( ! is_dir( $dir ) ) {
				@mkdir( $dir, 0755, true );
			}
			if ( is_dir( $dir ) && is_writable( $dir ) ) {
				return $dir;
			}
		}

		return false;
	}

	/**
	 * The folders dompdf may read local files from (images, stylesheets, fonts named in the
	 * HTML). The install folder, plus the file storage folder when that has been moved
	 * elsewhere. Nothing else on the server is reachable from a template.
	 *
	 * @return string[]
	 */
	public static function chroot() {
		$roots = array();
		$dirs  = array( _UCM_FOLDER );
		if ( defined( '_UCM_FILE_STORAGE_DIR' ) ) {
			$dirs[] = _UCM_FILE_STORAGE_DIR;
		}
		foreach ( $dirs as $dir ) {
			$real = realpath( $dir );
			if ( false !== $real && ! in_array( $real, $roots, true ) ) {
				$roots[] = $real;
			}
		}

		return $roots;
	}

	/**
	 * The dompdf options UCM runs with.
	 *
	 * Remote fetching is off: an invoice template has no need to make the server download
	 * anything, and turning it on would let template text trigger outgoing requests. PHP in
	 * templates is off for the same reason. Images are embedded (data: URIs) or read from
	 * inside the chroot.
	 *
	 * @return \Dompdf\Options
	 */
	public static function options() {
		$options = new \Dompdf\Options();
		$options->setChroot( self::chroot() );
		$options->setIsRemoteEnabled( false );
		$options->setIsPhpEnabled( false );
		$options->setIsJavascriptEnabled( false );
		$options->setAllowedProtocols( array( 'data://', 'file://' ) );
		$work_dir = self::work_dir();
		if ( false !== $work_dir ) {
			$options->setFontDir( $work_dir );
			$options->setFontCache( $work_dir );
			$options->setTempDir( $work_dir );
		}

		return $options;
	}

	/**
	 * Render an HTML string to PDF bytes.
	 *
	 * @param string $html   UTF-8 HTML
	 * @param string $paper  paper size name, A4 by default
	 *
	 * @return string|false the PDF file contents, or false if dompdf is not installed
	 */
	public static function render( $html, $paper = 'A4' ) {
		if ( ! ucm_libraries::load( 'dompdf' ) ) {
			return false;
		}
		$dompdf = new \Dompdf\Dompdf( self::options() );
		$dompdf->loadHtml( $html, 'UTF-8' );
		$dompdf->setPaper( $paper );
		$dompdf->render();

		return $dompdf->output();
	}

	/**
	 * Delete the copy of dompdf 1.0.2 that older releases shipped in includes/plugin_pdf/dompdf.
	 *
	 * Updating never removes files, so every install that updates keeps that folder, with
	 * its known vulnerabilities, even though nothing loads it any more. Called from the
	 * plugin's install_upgrade().
	 *
	 * Only that one folder can be deleted: it is resolved with realpath() and must be
	 * exactly includes/plugin_pdf/dompdf under the install folder. A symbolic link there
	 * is left alone, and links inside it are removed as links, never followed. Failures
	 * (read-only hosting, permissions) are ignored; the next upgrade tries again.
	 *
	 * @param string|null $root install folder with trailing slash, _UCM_FOLDER by default
	 *
	 * @return bool true when the folder is gone afterwards
	 */
	public static function remove_bundled_dompdf( $root = null ) {
		if ( null === $root ) {
			$root = _UCM_FOLDER;
		}
		$real_root  = realpath( $root );
		$plugin_dir = realpath( rtrim( $root, '/\\' ) . '/includes/plugin_pdf' );
		if ( false === $real_root || false === $plugin_dir ) {
			return false;
		}
		// If includes/ or includes/plugin_pdf is a link, the real path leads somewhere else
		// and nothing under it is ours to delete.
		if ( $plugin_dir !== $real_root . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin_pdf' ) {
			return false;
		}
		$target = $plugin_dir . DIRECTORY_SEPARATOR . 'dompdf';
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
