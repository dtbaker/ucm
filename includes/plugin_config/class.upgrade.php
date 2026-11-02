<?php

class ucm_upgrade_manager
{
	/**
	 * @var array
	 * These are files we don't want to overwrite during the upgrade process.
	 */
	public $held_files = array();

	/**
	 * Files an update is never allowed to replace, whatever the server says.
	 *
	 * The database credentials are the obvious one: an install that lets its update server
	 * rewrite includes/config.php has handed over the database. upgrade_ignore.txt is how
	 * the site owner says "leave my customisations alone", so an update that could rewrite
	 * it could quietly undo that instruction first.
	 */
	public static $never_overwrite = array(
		'includes/config.php',
		'includes/config.local.php',
		'upgrade_ignore.txt',
	);

	public function __construct() {

		if ( is_file( _UCM_FOLDER . 'upgrade_ignore.txt' ) ) {
			foreach ( file( _UCM_FOLDER . 'upgrade_ignore.txt' ) as $held_file ) {
				$this->held_files[] = trim( $held_file );
			}
		}
	}

	public function check_for_plugin_updates( $plugin_name ) {

	}

	/**
	 * Is this a path an update is allowed to write?
	 *
	 * Every path in an update response is a string from the network that gets joined onto
	 * _UCM_FOLDER and handed to file_put_contents(). Nothing checked it, so a response
	 * carrying `../../../../home/user/.ssh/authorized_keys` - or an absolute path, which
	 * wins outright when concatenated onto a prefix on Windows - wrote wherever it liked.
	 *
	 * Accepted: a relative path under the installation, made of ordinary path segments.
	 * Nothing else. The path does not have to exist yet; new files are normal in an update.
	 *
	 * @param string $file Path as the server sent it, relative to the installation root.
	 *
	 * @return bool
	 */
	public static function is_safe_relative_path( $file ) {
		if ( ! is_string( $file ) || ! strlen( $file ) ) {
			return false;
		}
		if ( strpos( $file, "\0" ) !== false ) {
			return false;
		}
		// Treat a backslash as a separator rather than as part of a name, so a Windows
		// style traversal cannot slip past a check that only looks for forward slashes.
		$normalised = str_replace( '\\', '/', $file );
		if ( $normalised !== $file ) {
			return false;
		}
		if ( substr( $file, 0, 1 ) === '/' ) {
			return false;
		}
		// Drive letter or UNC path.
		if ( preg_match( '#^[A-Za-z]:#', $file ) ) {
			return false;
		}
		// A scheme would make this a stream wrapper rather than a path.
		if ( preg_match( '#^[A-Za-z][A-Za-z0-9+.-]*://#', $file ) ) {
			return false;
		}
		// Folder entries arrive with a trailing slash, the way find printed them. One is
		// allowed; two in a row is an empty segment like any other.
		$trimmed = ( substr( $file, - 1 ) === '/' ) ? substr( $file, 0, - 1 ) : $file;
		if ( ! strlen( $trimmed ) ) {
			return false;
		}
		foreach ( explode( '/', $trimmed ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}
		if ( in_array( $trimmed, self::$never_overwrite, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Does the body the server sent hash to the checksum the server sent with it?
	 *
	 * This is the whole point of protocol 2. Before it, a response - from a compromised
	 * server, a hostile network, or an http:// URL somebody typed into the settings - was
	 * written into the installation as PHP and executed on the next request, with nothing
	 * in between to say it was what the server meant to send.
	 *
	 * A server that speaks protocol 2 must send a checksum for every file. A missing one
	 * is a failure, not a pass: otherwise stripping the checksums is all an attacker has to
	 * do. A server still speaking protocol 1 is accepted only until this install has seen a
	 * protocol 2 response; after that the client refuses to be talked back down.
	 *
	 * @param string $file      Path, for the error message.
	 * @param string $base64    File body as sent.
	 * @param array  $checksums file_checksums from the response.
	 * @param bool   $required  Whether a missing checksum is fatal.
	 *
	 * @return true|string True, or the reason it was refused.
	 */
	public static function verify_file_contents( $file, $base64, $checksums, $required ) {
		if ( ! is_string( $base64 ) ) {
			return 'the server sent no contents for this file';
		}
		$contents = base64_decode( $base64, true );
		if ( false === $contents ) {
			return 'the server sent contents that are not valid base64';
		}
		$expected = is_array( $checksums ) && isset( $checksums[ $file ] ) && is_string( $checksums[ $file ] )
			? strtolower( trim( $checksums[ $file ] ) ) : '';

		if ( '' === $expected ) {
			if ( $required ) {
				return 'the server sent no checksum for this file';
			}

			return true;
		}
		if ( ! hash_equals( $expected, hash( 'sha256', $contents ) ) ) {
			return 'the file did not match the checksum the server sent for it';
		}

		return true;
	}

	/**
	 * Write a downloaded file into place.
	 *
	 * Through a temporary file in the same directory and a rename, so a write that dies
	 * half way - disk full, process killed, request timed out - leaves the old file intact
	 * rather than a truncated PHP file the application then tries to run.
	 *
	 * @return bool
	 */
	public static function write_file( $absolute_path, $contents ) {
		$directory = dirname( $absolute_path );
		if ( ! is_dir( $directory ) ) {
			return false;
		}
		$temporary = @tempnam( $directory, '.ucmup' );
		if ( ! $temporary ) {
			// Nowhere to stage it; the plain write is still better than nothing.
			if ( false === @file_put_contents( $absolute_path, $contents ) ) {
				return false;
			}
			self::forget_compiled_copy( $absolute_path );

			return true;
		}
		if ( false === @file_put_contents( $temporary, $contents ) ) {
			@unlink( $temporary );

			return false;
		}
		// tempnam() makes the file 0600. Match whatever was there before, or the default.
		$mode = is_file( $absolute_path ) ? ( @fileperms( $absolute_path ) & 0777 ) : 0644;
		@chmod( $temporary, $mode ? $mode : 0644 );
		if ( ! @rename( $temporary, $absolute_path ) ) {
			@unlink( $temporary );

			return false;
		}
		self::forget_compiled_copy( $absolute_path );

		return true;
	}

	/**
	 * Tell PHP's opcache that a file it may have compiled has just been replaced.
	 *
	 * Opcache only looks at a file's timestamp every opcache.revalidate_freq seconds (2 by
	 * default, often more on shared hosting, and never with validate_timestamps off). The
	 * updater writes a plugin's files in one request and runs its database step in the
	 * next, so without this the database step usually ran the old class: it saw no version
	 * change, skipped the plugin's upgrade SQL and still reported success.
	 *
	 * @param string $absolute_path The file just written.
	 *
	 * @return bool True if opcache was told; false if it is missing, off or restricted.
	 */
	public static function forget_compiled_copy( $absolute_path ) {
		if ( ! function_exists( 'opcache_invalidate' ) || ! filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			return false;
		}

		// opcache.restrict_api turns this into a warning. If it is refused, the version
		// check in complete_plugin_installation() is what stops a false "success".
		return @opcache_invalidate( $absolute_path, true );
	}

	/**
	 * Is the database step about to run the plugin code from before the update?
	 *
	 * The file step remembers the version that was loaded when it started and the version
	 * the server offered. If the server offered something newer and the database step still
	 * has the version from before, PHP is running a stale compiled copy, and running
	 * install_upgrade() would record nothing, skip the upgrade SQL and call it a success.
	 *
	 * Compared as numbers, the way plugin::install_upgrade() and the update server do.
	 *
	 * @param mixed $version_before  What was loaded when the files were written.
	 * @param mixed $version_offered What the update server said it was sending.
	 * @param mixed $version_loaded  What the database step has loaded now.
	 *
	 * @return bool
	 */
	public static function is_running_old_code( $version_before, $version_offered, $version_loaded ) {
		if ( ! is_numeric( $version_before ) || ! is_numeric( $version_offered ) || ! is_numeric( $version_loaded ) ) {
			return false;
		}

		return (float) $version_offered > (float) $version_before && (float) $version_loaded <= (float) $version_before;
	}

	/**
	 * Should a missing checksum be fatal for this response?
	 *
	 * Yes if the response says it speaks protocol 2, and yes from then on: the protocol
	 * level is remembered, so an install that has once been answered by a server with
	 * checksums will not accept one without them afterwards.
	 *
	 * @param array $response The decoded response.
	 *
	 * @return bool
	 */
	public static function checksums_required( $response ) {
		$seen = isset( $response['protocol'] ) ? (int) $response['protocol'] : 1;
		$ever = (int) module_config::c( 'upgrade_server_protocol', 1 );
		if ( $seen > $ever ) {
			module_config::save_config( 'upgrade_server_protocol', $seen );
			$ever = $seen;
		}

		return $seen >= 2 || $ever >= 2;
	}

	public function install_plugin_update_files( $plugin_name ) {
		global $plugins;
		$result = array(
			'message'     => '',
			'plugin_name' => $plugin_name,
		);
		$errors = array();

		{
			/*$available_updates = module_config::check_for_upgrades($plugin_name);
		    if($available_updates && isset($available_updates['message']) && strlen(trim($available_updates['message']))>2){
		        $result['message'] = $available_updates['message'];
		    }else{*/
			$completed_updates = array();
			// we useto process multiple plugin updates at a time, not any more..
			$upgrade_plugins = array( $plugin_name ); // hack so we can 'continue/break' out of loop below.
			foreach ( $upgrade_plugins as $plugin_name ) {
				$this_update = array(
					'key' => $plugin_name,
				);
				/*$this_update = false;
				foreach($available_updates['plugins'] as $available_update){
						if($available_update['key'] == $plugin_name){
								$this_update = $available_update;
								break;
						}
				}*/
				if ( $this_update && strlen( trim( $this_update['key'] ) ) ) {
					$result['message'] .= "Downloading files... <br/>\n";
					if ( $update = module_config::download_update( $this_update['key'] ) ) {
						$require_checksums = self::checksums_required( $update );
						foreach ( $update['plugins'] as $available_update ) {
							if (
								$available_update['key'] != $this_update['key']
								//(!isset($available_update['linked_key']) && $available_update['key'] != $this_update['key']) ||
								//(isset($available_update['linked_key']) && $available_update['linked_key'] != $this_update['key'])
							) {
								// core update bug fix.
								continue;
							}
							// have we done this yet?
							if ( isset( $completed_updates[ $available_update['key'] ] ) ) {
								continue;
							}
							$completed_updates[ $available_update['key'] ] = true;

							$checksums = isset( $available_update['file_checksums'] ) && is_array( $available_update['file_checksums'] )
								? $available_update['file_checksums'] : array();
							$errors_before = count( $errors );

							foreach ( $available_update['folders'] as $file ) {
								// Folder names come off the network too, and mkdir() with
								// a traversal in it makes directories outside the install.
								if ( ! self::is_safe_relative_path( $file ) ) {
									$error             = '<strong>REFUSED:</strong> the server asked for a folder outside this installation: ' . htmlspecialchars( (string) $file );
									$errors[]          = $error;
									$result['message'] .= $error . '<br/>';
									continue;
								}
								if ( ! is_dir( _UCM_FOLDER . $file ) ) {
									$result['message'] .= 'Folder: ' . htmlspecialchars( $file ) . " <br/>\n";
									$result['message'] .= '<span class="small">';
									// check if writable
									if ( mkdir( _UCM_FOLDER . $file, 0777, true ) ) {
										$result['message'] .= 'this new folder has been <strong>created</strong>';
									} else {
										$error             = '<strong>WARNING:</strong> failed to create new folder: ' . htmlspecialchars( $file );
										$errors[]          = $error;
										$result['message'] .= $error;
									}
									$result['message'] .= "</span><br/>";
								}
							}
							foreach ( $available_update['files'] as $file ) {
								if ( ! self::is_safe_relative_path( $file ) ) {
									$error             = '<strong>REFUSED:</strong> the server asked to write outside this installation: ' . htmlspecialchars( (string) $file );
									$errors[]          = $error;
									$result['message'] .= $error . '<br/>';
									continue;
								}
								$safe_name = htmlspecialchars( $file );
								$result['message'] .= 'File: ' . $safe_name . " <br/>\n";
								$result['message'] .= '<span class="small">';
								$verified = isset( $available_update['file_contents'][ $file ] )
									? self::verify_file_contents( $file, $available_update['file_contents'][ $file ], $checksums, $require_checksums )
									: 'the server sent no contents for this file';
								if ( in_array( $file, $this->held_files ) ) {
									$error             = '<strong>Custom:</strong> file not upgraded: ' . $safe_name;
									$errors[]          = $error;
									$result['message'] .= $error;
								} elseif ( true !== $verified ) {
									$error             = '<strong>REFUSED:</strong> ' . htmlspecialchars( (string) $verified ) . ': ' . $safe_name;
									$errors[]          = $error;
									$result['message'] .= $error;
								} elseif ( ! self::write_file( _UCM_FOLDER . $file, (string) base64_decode( $available_update['file_contents'][ $file ] ) ) ) {
									$error             = '<strong>WARNING:</strong> failed to install the file: ' . $safe_name;
									$errors[]          = $error;
									$result['message'] .= $error;
								} else {
									$result['message'] .= 'this file has been <strong>installed</strong> successfully';
								}
								$result['message'] .= "</span><br/>";
							}
							// Hand the database step, which runs in the next request, what it
							// should expect to find loaded. Only when every file went in: after
							// a failed write the old version is the honest answer.
							if ( count( $errors ) === $errors_before && isset( $plugins[ $available_update['key'] ], $available_update['available_version'] ) ) {
								$_SESSION['_ucm_upgrade_pending'][ $available_update['key'] ] = array(
									'before'  => $plugins[ $available_update['key'] ]->get_plugin_version(),
									'offered' => $available_update['available_version'],
								);
							}
						}
						//exit;
					} else {
						$error             = "failed to download files ($plugin_name):";
						$errors[]          = $error;
						$result['message'] .= '<span class="error_text">' . $error . '</span><br/> ';
					}
					//$_REQUEST['run_upgrade'] = true; // so we do the DB update again down the bottom.
				} else {
					$error             = "Failed to start update ($plugin_name):";
					$errors[]          = $error;
					$result['message'] .= $error . '<br/>';
				}
			}
			/*}*/
		}

		$result['errors'] = $errors;
		if ( ! count( $errors ) ) {
			$result['success'] = 1;
		}

		return $result;
	}

	public function complete_plugin_installation( $plugin_name ) {
		global $plugins;
		$result             = array(
			'message' => ''
		);
		$new_system_version = module_config::current_version();
		$fail               = false;
		$pending = isset( $_SESSION['_ucm_upgrade_pending'][ $plugin_name ] ) ? $_SESSION['_ucm_upgrade_pending'][ $plugin_name ] : null;
		unset( $_SESSION['_ucm_upgrade_pending'][ $plugin_name ] );
		if ( isset( $plugins[ $plugin_name ] ) ) {
			$loaded_version     = $plugins[ $plugin_name ]->get_plugin_version();
			$result['message'] .= "Processing update: <span style='text-decoration:underline;'>" . $plugin_name . "</span> - Current Version: " . $loaded_version . ".... ";
			ob_start();
			if ( $pending && self::is_running_old_code( $pending['before'], $pending['offered'], $loaded_version ) ) {
				// Running install_upgrade() now would record the old version and skip the
				// new upgrade SQL. Leave the database alone and say so.
				$fail               = true;
				$result['message'] .= '<span class="error_text">' . htmlspecialchars( _l( 'not finished: version %s was installed, but PHP is still running the old copy of this plugin. Nothing was changed in the database. Wait a few seconds, then click "Run Manual Upgrades" on this page to finish.', (string) $pending['offered'] ) ) . '</span> ';
			} elseif ( $version = $plugins[ $plugin_name ]->install_upgrade() ) {
				$result['message']  .= '<span class="success_text">all good</span>';
				$new_system_version = max( $version, $new_system_version );
				$plugins[ $plugin_name ]->init();
				// lol typo - oh well.
				$plugins[ $plugin_name ]->set_installed_plugin_version( $version );
			} else {
				$fail              = true;
				$result['message'] .= '<span class="error_text">failed</span> ';
			}
			$result['message'] .= ob_get_clean() . '<br/>';
			$result['message'] .= '<br/>';
			if ( $fail ) {
				$result['message'] .= _l( 'Some things failed. Please go back and try again.' );
			} else {
				$result['message'] .= '<strong>' . _l( 'Success! Everything worked.' ) . '</strong>';
				module_config::set_system_version( $new_system_version );
				module_config::save_config( 'last_update', time() );
			}
			if ( isset( $_SESSION['_message'] ) && count( $_SESSION['_message'] ) ) {
				$result['message'] .= '<br/>';
				$result['message'] .= implode( '<br/>', $_SESSION['_message'] );
				unset( $_SESSION['_errors'] );
			}
			if ( isset( $_SESSION['_errors'] ) && count( $_SESSION['_errors'] ) ) {
				$result['message'] .= '<br/>';
				$result['message'] .= implode( '<br/>', $_SESSION['_errors'] );
				unset( $_SESSION['_errors'] );
			}
		} elseif ( $plugin_name == 'corefiles' || $plugin_name == 'database' ) {

		} else {
			$fail = true;
		}
		// hack to clear db field cache:
		module_cache::clear( 'db' );
		if ( ! $fail ) {
			$result['success'] = 1;
		}

		return $result;
	}
}
