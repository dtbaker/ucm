<?php

/**
 * The update server, as a class.
 *
 * UCM installs update themselves by POSTing an inventory of what they have to an endpoint
 * that is itself a UCM installation, and getting back the files they are missing. That
 * endpoint used to live as a loose script on the website, outside version control, and
 * shelled out to /usr/bin/find to list files. It lives here now so that it ships, gets
 * linted and gets tested along with the code it serves.
 *
 * Nothing here needs a database or a booted application: everything takes what it needs as
 * an argument. upgrade_server.php is the thin HTTP entry point that boots UCM and calls
 * this, and the unit tests call it directly.
 *
 * The response is deliberately a superset of what the old server sent. Five thousand
 * installs speak the old shape and an old client is how a site receives the update that
 * teaches it the new one, so nothing is ever removed - only added, under keys an old
 * client ignores.
 */
class ucm_upgrade_server
{
	/**
	 * Wire protocol the response advertises.
	 *
	 * 1 (implied by its absence) is the original: file bodies, no integrity check at all.
	 * 2 adds a sha256 of every file body under `file_checksums`, which a client that knows
	 * about it must verify before writing anything.
	 */
	const PROTOCOL = 2;

	/**
	 * Requests one IP may make inside RATE_WINDOW before being turned away.
	 *
	 * A full upgrade is two requests per plugin and there are around 65 plugins, so the
	 * floor for one honest install finishing one upgrade is ~130. This is set well above
	 * that: the point is to stop a scraper pulling the whole source tree in a loop, not to
	 * ration real upgrades, and a shared host can put many real installs behind one IP.
	 */
	const RATE_LIMIT = 600;

	/** Seconds the rate limit window covers. */
	const RATE_WINDOW = 600;

	/**
	 * Paths never served to a client.
	 *
	 * The server is a real installation with real data in it. `find` did not know that, so
	 * whatever the demo site happened to have under plugin_file/upload went out with the
	 * update. These are the same patterns the client already refuses to inventory, plus the
	 * usual working-copy noise.
	 */
	public static function is_ignored_path( $path ) {
		$path = str_replace( '\\', '/', (string) $path );

		$fragments = array(
			'plugin_file/upload',
			'plugin_data/upload',
			'/cache/',
			'/html2ps/',
			'backup/backups/backup_',
			'/attachments/',
			'/temp/',
			'/tmp/',
			'/.git/',
			'/.svn/',
			'/node_modules/',
		);
		foreach ( $fragments as $fragment ) {
			if ( strpos( $path, $fragment ) !== false ) {
				return true;
			}
		}

		$basename = basename( $path );
		if ( '.DS_Store' === $basename || '.git' === $basename || '.svn' === $basename || 'node_modules' === $basename ) {
			return true;
		}
		if ( substr( $path, - 5 ) === '.orig' ) {
			return true;
		}

		return false;
	}

	/**
	 * List a directory the way `find` did, without shelling out.
	 *
	 * exec('/usr/bin/find ...') assumed exec() was available, assumed find was at that
	 * exact path, and handed an unquoted string to a shell. Plenty of shared hosts fail at
	 * least one of those, and on them the old server silently returned an empty file list -
	 * which reads to a client as "no update for this plugin".
	 *
	 * Output matches find's: the directory itself comes first, paths stay relative to the
	 * application root, and there is no double slash.
	 *
	 * @param string $root      Absolute path of the application root, with a trailing slash.
	 * @param string $directory Directory relative to the root, with a trailing slash.
	 *
	 * @return array{files: string[], folders: string[]}
	 */
	public static function list_directory( $root, $directory ) {
		$files   = array();
		$folders = array();

		$directory = ltrim( str_replace( '\\', '/', (string) $directory ), '/' );
		if ( '' === $directory ) {
			return array( 'files' => $files, 'folders' => $folders );
		}
		if ( substr( $directory, - 1 ) !== '/' ) {
			$directory .= '/';
		}
		if ( ! is_dir( $root . $directory ) ) {
			return array( 'files' => $files, 'folders' => $folders );
		}

		$folders[] = $directory;
		self::walk( $root, $directory, $files, $folders );

		sort( $files );
		sort( $folders );

		return array( 'files' => $files, 'folders' => $folders );
	}

	/**
	 * @param string   $root
	 * @param string   $directory Relative, with a trailing slash.
	 * @param string[] $files
	 * @param string[] $folders
	 */
	private static function walk( $root, $directory, array &$files, array &$folders ) {
		$handle = @opendir( $root . $directory );
		if ( ! $handle ) {
			return;
		}
		while ( false !== ( $entry = readdir( $handle ) ) ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}
			$relative = $directory . $entry;
			if ( self::is_ignored_path( '/' . $relative ) ) {
				continue;
			}
			if ( is_link( $root . $relative ) ) {
				// A symlink out of the tree would serve a file from anywhere on the disk.
				continue;
			}
			if ( is_dir( $root . $relative ) ) {
				$folders[] = $relative . '/';
				self::walk( $root, $relative . '/', $files, $folders );
			} elseif ( is_file( $root . $relative ) ) {
				$files[] = $relative;
			}
		}
		closedir( $handle );
	}

	/**
	 * The checksum a client verifies a downloaded file against.
	 *
	 * sha256 of the file exactly as it is on disk - the same bytes base64_decode() gives
	 * back from `file_contents`. Not a hash of the base64, and not the licence-header
	 * stripped md5 the client sends in `current_files`: that one answers "is this file the
	 * same as yours", which is a different question from "did this arrive intact".
	 *
	 * @return string|false
	 */
	public static function checksum( $contents ) {
		if ( ! is_string( $contents ) ) {
			return false;
		}

		return hash( 'sha256', $contents );
	}

	/**
	 * Pull the one changelog line for a version out of a plugin's source.
	 *
	 * Plugins carry their history as comments: `// 2.809 - 2022-12-27 - what changed`.
	 */
	public static function changelog_line( $plugin_source, $version ) {
		if ( ! is_string( $plugin_source ) || ! strlen( (string) $version ) ) {
			return '';
		}
		if ( ! preg_match( '#//\s*' . preg_quote( (string) $version, '#' ) . '(.*)#', $plugin_source, $matches ) ) {
			return '';
		}
		$info = trim( $matches[1], " -\t\r\n" );
		if ( preg_match( '#(\d\d\d\d-\d\d-\d\d)(.*)#', $info, $date_match ) ) {
			$info = trim( $date_match[2], ' -' ) . ' (on ' . $date_match[1] . ')';
		}

		return $info;
	}

	/**
	 * Which version of a plugin this server is offering.
	 *
	 * The old server asked the database. That made deploying an update a two step job: push
	 * the files, then remember to go and click "Run Manual Upgrades" on the demo site, or
	 * the new files sat there with the old version number beside them and nobody was
	 * offered the update. There is a comment at the top of the old script reminding the
	 * owner of exactly that, which is a fair sign of how often it got forgotten.
	 *
	 * The files are what we serve, so the version in the files is the honest answer. The
	 * database version is still taken into account and the higher of the two wins, so this
	 * can never offer less than the old behaviour did.
	 *
	 * @param int|float|string|null $code_version      Version declared in the plugin source.
	 * @param int|float|string|null $database_version  Version recorded in this install's database.
	 *
	 * @return int|float|string
	 */
	public static function offered_version( $code_version, $database_version ) {
		$code     = is_numeric( $code_version ) ? (float) $code_version : 0;
		$database = is_numeric( $database_version ) ? (float) $database_version : 0;
		if ( $code >= $database ) {
			return is_numeric( $code_version ) ? $code_version : $database_version;
		}

		return $database_version;
	}

	/**
	 * Count this request against an IP's allowance.
	 *
	 * A counter file per IP per window, which is all a single shared host can rely on - no
	 * database, no APCu, no cron to tidy up. It fails open: a server that cannot write to
	 * its temp directory keeps serving updates rather than locking everybody out.
	 *
	 * @return bool True if the request may proceed.
	 */
	public static function rate_limit_ok( $ip, $directory, $limit = self::RATE_LIMIT, $window = self::RATE_WINDOW ) {
		if ( ! strlen( (string) $ip ) || ! strlen( (string) $directory ) ) {
			return true;
		}
		if ( ! is_dir( $directory ) && ! @mkdir( $directory, 0777, true ) && ! is_dir( $directory ) ) {
			return true;
		}

		$now    = time();
		$bucket = (int) floor( $now / $window );
		$file   = rtrim( $directory, '/' ) . '/' . md5( (string) $ip ) . '-' . $bucket . '.txt';

		$handle = @fopen( $file, 'c+' );
		if ( ! $handle ) {
			return true;
		}
		$allowed = true;
		if ( flock( $handle, LOCK_EX ) ) {
			$count = (int) stream_get_contents( $handle );
			$count++;
			$allowed = ( $count <= $limit );
			rewind( $handle );
			ftruncate( $handle, 0 );
			fwrite( $handle, (string) $count );
			fflush( $handle );
			flock( $handle, LOCK_UN );
		}
		fclose( $handle );

		self::tidy_rate_limit_files( $directory, $now - ( $window * 3 ) );

		return $allowed;
	}

	/** Drop counter files from windows that have long since closed. */
	private static function tidy_rate_limit_files( $directory, $older_than ) {
		// Only sweep occasionally: this runs on a request that is already doing real work.
		if ( mt_rand( 1, 50 ) !== 1 ) {
			return;
		}
		$old = @glob( rtrim( $directory, '/' ) . '/*.txt' );
		if ( ! is_array( $old ) ) {
			return;
		}
		foreach ( $old as $file ) {
			if ( @filemtime( $file ) < $older_than ) {
				@unlink( $file );
			}
		}
	}
}
