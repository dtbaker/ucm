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
	 * The list of every file in the release this installation was deployed from.
	 *
	 * scripts/build-release.sh writes it into Main Files after everything else is in place,
	 * vendor/ included, so it is the CodeCanyon zip's own file list. The old one-file
	 * installer's zip is built from it rather than from a hand-kept list of folders, which
	 * is how that zip came to be missing class.libraries.php, vendor/, n/ and every guard
	 * file without anybody noticing.
	 */
	const RELEASE_MANIFEST = 'includes/release-files.txt';

	/** Files that keep a private folder private, and so ship even though the folder does not. */
	const RELEASE_GUARDS = array( '.htaccess', 'index.php', 'index.html', '.gitkeep' );

	/**
	 * Everything outside the plugin folders that the application runs on, sent in the
	 * `corefiles` row.
	 *
	 * includes/config.php is not here and never will be: it holds the database settings of
	 * every install from before config.local.php. The client refuses it anyway.
	 */
	const CORE_FILES = array(
		'index.php',
		'ext.php',
		'design_menu.php',
		'design_header.php',
		'design_footer.php',
		'ajax.php',
		'cron.php',
		'init.php',
		'includes/plugin.php',
		'includes/functions.php',
		'includes/links.php',
		'includes/database.php',
		'includes/class.libraries.php',
		'pages/home.php',
		'pages/home.help.json',
		'pages/mobile/login.php',
		'pages/login.php',
		'css/desktop.css',
		'css/print.css',
		'css/styles.css',
		'css/smoothness/jquery-ui-1.9.2.custom.min.css',
		'js/javascript.js',
		'temp/.htaccess',
	);

	/** Folders the core files need, created before any of them is written. */
	const CORE_FOLDERS = array( 'temp', 'pages/mobile' );

	/** Composer's libraries, built into every release by scripts/build-release.sh. */
	const LIBRARY_FOLDER = 'vendor/';

	/** The plugin that holds the updater, which installs last. See install_order(). */
	const UPDATER_PLUGIN = 'config';

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
				if ( self::is_guard_file( $root, $relative ) ) {
					// In a folder that is listed, but whose other files never are.
					$files[] = $relative;
				} elseif ( ! is_link( $root . $relative ) && is_dir( $root . $relative ) ) {
					self::add_guards( $root, $relative, $files, $folders );
				}
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
	 * The guard files of a folder the listing skips, such as an upload folder.
	 *
	 * The folder holds customers' files and is never listed, but the .htaccess and
	 * index.php that keep it private ship with the release. Skipping them too meant an
	 * updated install never got the ones that block web access to its uploads. Only the
	 * folder's own guards are looked for by name: a folder of uploads can be enormous, and
	 * nothing in it is read.
	 *
	 * @param string   $root
	 * @param string   $directory Relative, with no trailing slash.
	 * @param string[] $files
	 * @param string[] $folders
	 */
	private static function add_guards( $root, $directory, array &$files, array &$folders ) {
		$found = false;
		foreach ( self::RELEASE_GUARDS as $guard ) {
			if ( self::is_guard_file( $root, $directory . '/' . $guard ) ) {
				$files[] = $directory . '/' . $guard;
				$found   = true;
			}
		}
		if ( $found ) {
			$folders[] = $directory . '/';
		}
	}

	/** Is this one of the files that keeps a private folder private? See add_guards(). */
	private static function is_guard_file( $root, $relative ) {
		return in_array( basename( $relative ), self::RELEASE_GUARDS, true )
			&& is_file( $root . $relative ) && ! is_link( $root . $relative )
			&& self::is_servable_release_path( $relative );
	}

	/**
	 * Read the release file list, or false if this installation has none.
	 *
	 * A git checkout has none: the list only exists in a built release.
	 *
	 * @return string[]|false
	 */
	public static function release_manifest( $root ) {
		$file = $root . self::RELEASE_MANIFEST;
		if ( ! is_file( $file ) ) {
			return false;
		}
		$lines = file( $file, FILE_IGNORE_NEW_LINES );
		if ( ! is_array( $lines ) ) {
			return false;
		}
		$paths = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line || '#' === $line[0] ) {
				continue;
			}
			$paths[] = $line;
		}

		return $paths ? $paths : false;
	}

	/**
	 * Whether a path from the release file list may go out in the installer zip.
	 *
	 * The list comes from the build and should never name anything private, but the server
	 * is a live installation with customers' files in it, so this does not take the list's
	 * word for it. The folders is_ignored_path() keeps out stay out, apart from their guard
	 * files, and the installation's own config.local.php never leaves.
	 */
	public static function is_servable_release_path( $path ) {
		$path = (string) $path;
		if ( '' === $path || '/' === $path[0] || false !== strpos( $path, '\\' ) || false !== strpos( $path, "\0" ) ) {
			return false;
		}
		if ( in_array( '..', explode( '/', $path ), true ) ) {
			return false;
		}
		if ( 'includes/config.local.php' === $path ) {
			return false;
		}
		if ( self::is_ignored_path( '/' . $path ) && ! in_array( basename( $path ), self::RELEASE_GUARDS, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Where a file is read from on this server.
	 *
	 * A deploy/<application id>/ copy wins over the stock file, which is how a branded build
	 * ships a different logo or template. The id is run through basename() so it cannot climb
	 * out of the deploy directory, and the resolved path is checked against the root for the
	 * same reason.
	 *
	 * @return string|false
	 */
	public static function source_path( $root, $application, $file ) {
		$candidates = array();
		if ( $application ) {
			$candidates[] = $root . 'deploy/' . basename( (string) $application ) . '/' . $file;
		}
		$candidates[] = $root . $file;

		$real_root = rtrim( str_replace( '\\', '/', (string) realpath( $root ) ), '/' ) . '/';
		foreach ( $candidates as $candidate ) {
			if ( ! is_file( $candidate ) ) {
				continue;
			}
			$real = realpath( $candidate );
			if ( false === $real || strpos( str_replace( '\\', '/', $real ), $real_root ) !== 0 ) {
				continue;
			}

			return $candidate;
		}

		return false;
	}

	/**
	 * Match every path in the release file list to the file on this server that serves it.
	 *
	 * `missing` is a path the list names that is not on the disk, and `refused` is one that
	 * is_servable_release_path() turned away. Either means the zip would not be the release,
	 * so the caller sends no zip at all rather than a site that half works.
	 *
	 * @param string[] $manifest
	 *
	 * @return array{files: array<string, string>, missing: string[], refused: string[]}
	 */
	public static function release_files( $root, $application, array $manifest ) {
		$files   = array();
		$missing = array();
		$refused = array();
		foreach ( $manifest as $path ) {
			if ( ! self::is_servable_release_path( $path ) ) {
				$refused[] = $path;
				continue;
			}
			$source = self::source_path( $root, $application, $path );
			if ( ! $source ) {
				$missing[] = $path;
				continue;
			}
			$files[ $path ] = $source;
		}

		return array( 'files' => $files, 'missing' => $missing, 'refused' => $refused );
	}

	/**
	 * Write a zip holding each file under its release path.
	 *
	 * @param array<string, string> $files Release path => file to read it from.
	 */
	public static function write_release_zip( $zip_path, array $files ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return false;
		}
		foreach ( $files as $path => $source ) {
			if ( ! $zip->addFile( $source, $path ) ) {
				$zip->close();

				return false;
			}
		}

		return $zip->close();
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

	/**
	 * The `corefiles` and `database` rows, in the order a client must install them.
	 *
	 * They go before every plugin. A client installs one row per request, and every request
	 * boots the whole application from whatever is on disk at that moment. With core last,
	 * a 3.x install ran new plugin code on its old core until the very end, and the first
	 * plugin that called a new core function took the site down halfway through (4.0.0:
	 * paymethod_paypal, db_identifier()). Core first means the plugins still waiting run on
	 * the new core instead, and the new core is written to cope with old plugins.
	 *
	 * `database` also carries vendor/ when the client's copy is not this server's, since
	 * the updater has no other way to deliver it. It is a row of its own so that a large
	 * download that fails cannot stop the core files arriving. An old client only treats
	 * `corefiles` and `database` as rows with nothing to process afterwards, so a new row
	 * name would show as a failure there.
	 *
	 * A file the server cannot send is left off rather than listed, because a client
	 * reports every listed file it gets no contents for as a failure.
	 *
	 * @param string           $root            Application root, with a trailing slash.
	 * @param int|string       $application     Branded build id, or 0.
	 * @param int|float|string $version         Version to show on both rows.
	 * @param bool             $recommended     Whether to tick them.
	 * @param bool             $send_libraries  Whether to add vendor/ to `database`.
	 * @param int              $client_protocol What the client said it speaks.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function core_rows( $root, $application, $version, $recommended, $send_libraries, $client_protocol ) {
		$files = self::files_for_client( $root, $application, self::CORE_FILES, $client_protocol );

		$database_files   = self::files_for_client( $root, $application, array( 'includes/database.php' ), $client_protocol );
		$database_folders = array();
		$description      = 'Database application update';
		if ( $send_libraries ) {
			$listing = self::list_directory( $root, self::LIBRARY_FOLDER );
			if ( $listing['files'] ) {
				$database_files   = array_merge( $database_files, self::files_for_client( $root, $application, $listing['files'], $client_protocol ) );
				$database_folders = $listing['folders'];
				$description      = 'Database application update and third party libraries';
			}
		}

		return array(
			'corefiles' => array(
				'key'               => 'corefiles',
				'installed_version' => 'N/A',
				'available_version' => $version,
				'description'       => 'Core application files',
				'files'             => $files,
				'folders'           => self::CORE_FOLDERS,
				'file_contents'     => array(),
				'recommended'       => $recommended,
				'force'             => 1,
			),
			'database'  => array(
				'key'               => 'database',
				'installed_version' => 2.2,
				'available_version' => $version,
				'description'       => $description,
				'files'             => $database_files,
				'folders'           => $database_folders,
				'file_contents'     => array(),
				'recommended'       => $recommended,
				'force'             => 1,
			),
		);
	}

	/**
	 * The order the client installs the rows in: core, then the plugins, then the updater.
	 *
	 * The client shows rows in the order they arrive and installs them in the order shown.
	 * Core goes first, see core_rows(). The config plugin, which holds the updater itself,
	 * goes last: the page running the upgrade keeps its own javascript to the end, and the
	 * updater that replaces it may expect something from that javascript the old one never
	 * sent. 4.0.0's wants a form token on every step, so with config in its alphabetical
	 * place a 3.x install had every row after it refused.
	 *
	 * @param array<string, array<string, mixed>> $plugin_rows
	 * @param array<string, array<string, mixed>> $core_rows
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function install_order( array $plugin_rows, array $core_rows ) {
		foreach ( $core_rows as $key => $ignored ) {
			unset( $plugin_rows[ $key ] );
		}
		$updater = array();
		if ( isset( $plugin_rows[ self::UPDATER_PLUGIN ] ) ) {
			$updater = array( self::UPDATER_PLUGIN => $plugin_rows[ self::UPDATER_PLUGIN ] );
			unset( $plugin_rows[ self::UPDATER_PLUGIN ] );
		}

		return $core_rows + $plugin_rows + $updater;
	}

	/**
	 * The files from a list that this server can actually send to this client.
	 *
	 * An empty file is left off for a client that does not speak protocol 2: the 3.x client
	 * writes with `! file_put_contents()`, so writing zero bytes reads as a failed write and
	 * marks the whole row as failed. Every empty file in the release is empty in 3.555 too.
	 *
	 * @param string[] $files
	 *
	 * @return string[]
	 */
	public static function files_for_client( $root, $application, array $files, $client_protocol ) {
		$sendable = array();
		foreach ( $files as $file ) {
			if ( ! self::is_servable_release_path( $file ) ) {
				continue;
			}
			$source = self::source_path( $root, $application, $file );
			if ( ! $source ) {
				continue;
			}
			if ( (int) $client_protocol < 2 && 0 === (int) filesize( $source ) ) {
				continue;
			}
			$sendable[] = $file;
		}

		return $sendable;
	}

	/**
	 * Which set of Composer libraries an installation has, or '' when it has none.
	 *
	 * Composer writes installed.json from the exact package versions it installed, so two
	 * installs with the same file have the same vendor/.
	 */
	public static function libraries_fingerprint( $root ) {
		$file = $root . self::LIBRARY_FOLDER . 'composer/installed.json';
		if ( ! is_file( $file ) ) {
			return '';
		}

		return (string) hash_file( 'sha256', $file );
	}

	/**
	 * Does the client need this server's vendor/?
	 *
	 * A client that sends no fingerprint is a 3.x install, which has no vendor/ at all.
	 *
	 * @param string $server_fingerprint libraries_fingerprint() of this server.
	 * @param mixed  $client_fingerprint The `libraries` field the client posted, or null.
	 */
	public static function client_needs_libraries( $server_fingerprint, $client_fingerprint ) {
		if ( '' === (string) $server_fingerprint ) {
			return false;
		}

		return ! is_string( $client_fingerprint ) || ! hash_equals( (string) $server_fingerprint, $client_fingerprint );
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
