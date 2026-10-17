<?php

/**
 * The update endpoint, as served to other UCM installations.
 *
 * This is the server half of the mechanism whose client half is class.upgrade.php. It runs
 * inside a normal UCM installation and answers with the files that installation has and the
 * caller does not. The copy that answers https://api.ultimateclientmanager.com/upgrade.php
 * is a wrapper around this file; the logic it used to hold inline now ships with the
 * application, so it is linted, tested, and updated by the same git pull that updates
 * everything else.
 *
 * It is off unless somebody turns it on. An ordinary installation has no business handing
 * its own source code to anonymous callers, so this refuses to run unless the including
 * script defines _UCM_UPGRADE_SERVER or the `upgrade_server_enabled` setting is on.
 *
 * The response shape is the old one with extra keys bolted on. See ucm_upgrade_server.
 */

if ( ! defined( '_UCM_FOLDER' ) ) {
	// Reached directly over HTTP rather than included by a wrapper that has already
	// booted. Start the application ourselves, with sessions off - this endpoint is
	// anonymous and has no use for one.
	chdir( dirname( __DIR__, 2 ) );
	$disable_sessions  = true;
	$noredirect        = true;
	$dont_init_plugins = false;
	require_once dirname( __DIR__, 2 ) . '/init.php';
}

require_once _UCM_FOLDER . 'includes/plugin_config/class.upgrade_server.php';

if ( ! defined( '_UCM_UPGRADE_SERVER' ) && ! module_config::c( 'upgrade_server_enabled', 0 ) ) {
	if ( ! headers_sent() ) {
		header( 'HTTP/1.1 404 Not Found' );
		header( 'Content-Type: text/plain; charset=utf-8' );
	}
	echo 'Not found.';
	exit;
}

ucm_upgrade_server_respond();

/**
 * Build and print the response. A function only so the dozens of locals it needs do not
 * leak into the global scope of whatever included this.
 */
function ucm_upgrade_server_respond() {
	global $plugins;

	@set_time_limit( 0 );
	@ini_set( 'memory_limit', '256M' );

	$client_ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && strlen( $_SERVER['HTTP_X_FORWARDED_FOR'] ) > 6
		? trim( current( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) )
		: '';
	if ( ! $client_ip ) {
		$client_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	}

	$rate_limit_directory = module_config::c( 'upgrade_server_rate_limit_dir', '' );
	if ( ! $rate_limit_directory ) {
		$rate_limit_directory = rtrim( sys_get_temp_dir(), '/' ) . '/ucm-upgrade-rate';
	}
	if ( ! ucm_upgrade_server::rate_limit_ok( $client_ip, $rate_limit_directory ) ) {
		if ( ! headers_sent() ) {
			header( 'HTTP/1.1 429 Too Many Requests' );
			header( 'Retry-After: ' . ucm_upgrade_server::RATE_WINDOW );
			header( 'Content-Type: application/json; charset=utf-8' );
		}
		// An old client shows `message` to the user and stops, which is what we want.
		echo json_encode( array(
			'available_updates' => array(
				'message'     => 'Too many update requests from this address. Please try again shortly.',
				'new_version' => 0,
				'plugins'     => array(),
				'check_again' => 0,
			),
			'licence_codes'     => array(),
		) );

		return;
	}

	// Every one of these used to be read straight out of $_REQUEST. Two of them with no
	// isset() at all, so a bare GET of the endpoint emitted warnings into the response
	// body and broke the client's json_decode().
	$root            = _UCM_FOLDER;
	$application     = ! empty( $_REQUEST['application'] ) ? (int) $_REQUEST['application'] : 0;
	$current_version = isset( $_REQUEST['current_version'] ) && is_scalar( $_REQUEST['current_version'] )
		? $_REQUEST['current_version'] : 0;
	$client_protocol = isset( $_REQUEST['client_protocol'] ) ? (int) $_REQUEST['client_protocol'] : 1;

	$current_plugins = isset( $_REQUEST['current_plugins'] ) && is_string( $_REQUEST['current_plugins'] )
		? json_decode( $_REQUEST['current_plugins'], true ) : array();
	if ( ! is_array( $current_plugins ) ) {
		$current_plugins = array();
	}
	$current_files = isset( $_REQUEST['current_files'] ) && is_string( $_REQUEST['current_files'] )
		? json_decode( $_REQUEST['current_files'], true ) : array();
	if ( ! is_array( $current_files ) ) {
		$current_files = array();
	}

	$get_file_contents         = ! empty( $_REQUEST['get_file_contents'] );
	$return_as_zip             = ! empty( $_REQUEST['return_as_zip'] );
	$return_base_system_in_zip = ! empty( $_REQUEST['return_base_system_in_zip'] );
	$requested_plugin          = isset( $_REQUEST['requested_plugin'] ) && is_string( $_REQUEST['requested_plugin'] )
		? $_REQUEST['requested_plugin'] : false;
	// The release the client runs, from a client new enough to say. See
	// ucm_upgrade_server::offers_core_files().
	$client_release = ucm_upgrade_server::client_release( isset( $_REQUEST['release_version'] ) ? $_REQUEST['release_version'] : null );
	$server_release = defined( '_SCRIPT_VERSION' ) ? (string) _SCRIPT_VERSION : '';

	// The old one-file installer, and ucm-web/api/download_zip.php: the whole product in one
	// zip, so a site installed from it is the same as one unzipped from CodeCanyon. It is
	// built from the release's own file list, and nothing below applies to it.
	if ( $return_base_system_in_zip ) {
		ucm_upgrade_server_send_release_zip( $root, $application );

		return;
	}

	$message                             = '';
	$new_version                         = $current_version;
	$do_upgrade                          = false;
	$update_plugins                      = array();
	$check_again_for_updates             = false;
	$we_are_recommending_an_item_upgrade = false;

	// The release file list, when this server was deployed from a built release. Plugin
	// rows are built from it so that nothing the server wrote into a plugin folder at run
	// time - dompdf's font caches, say - is offered to clients as part of the plugin.
	$release_manifest = ucm_upgrade_server::release_manifest( $root );

	$plugin_list = is_array( $plugins ) ? $plugins : array();
	uasort( $plugin_list, function ( $a, $b ) {
		return strnatcasecmp( $a->module_name, $b->module_name );
	} );

	foreach ( $plugin_list as $plugin_name => $plugin ) {

		if ( $requested_plugin && $plugin_name != $requested_plugin ) {
			continue;
		}

		// The files are what gets served, so the version in the files is the version on
		// offer. See ucm_upgrade_server::offered_version().
		$new_plugin_version = ucm_upgrade_server::offered_version(
			$plugin->get_plugin_version(),
			$plugin->get_installed_plugin_version()
		);

		$install_this_plugin = false;
		$recommended_install = false;
		if ( preg_match( '#social#', $plugin_name ) && ! isset( $current_plugins[ $plugin_name ] ) && ! $return_as_zip ) {
			$install_this_plugin = true;
		} elseif (
			! isset( $current_plugins[ $plugin_name ] ) ||
			$current_plugins[ $plugin_name ] < $new_plugin_version ||
			( $requested_plugin && $plugin_name == $requested_plugin )
		) {
			$install_this_plugin = true;
			$recommended_install = true;
		}

		$new_version = max( $new_version, $new_plugin_version );

		$listing = ucm_upgrade_server::listing( $root, 'includes/plugin_' . $plugin_name . '/', $release_manifest );
		$files   = $listing['files'];
		$folders = $listing['folders'];

		$update_plugins[ $plugin_name ] = array(
			'key'               => $plugin_name,
			'installed_version' => isset( $current_plugins[ $plugin_name ] ) ? $current_plugins[ $plugin_name ] : 'N/A',
			'available_version' => $new_plugin_version,
			'file_contents'     => array(),
			'licence_type'      => 'everyone',
		);

		// mPDF is enormous and most installs do not have it. Only offer those files to a
		// client that already has them.
		if ( 'pdf' === $plugin_name && ! $return_as_zip ) {
			foreach ( $files as $file_id => $file ) {
				if ( strpos( $file, 'mpdf/' ) === false ) {
					continue;
				}
				if ( empty( $current_files[ $plugin_name ][ $file ] ) ) {
					unset( $files[ $file_id ] );
				}
			}
			$files = array_values( $files );
		}

		if ( ! $install_this_plugin || ( ! $folders && ! $files ) ) {
			unset( $update_plugins[ $plugin_name ] );
			continue;
		}

		$do_upgrade = true;
		if ( $recommended_install ) {
			$we_are_recommending_an_item_upgrade = true;
		}

		$additional_info = '';
		$plugin_file     = $root . 'includes/plugin_' . $plugin_name . '/' . $plugin_name . '.php';
		if ( is_file( $plugin_file ) ) {
			$line = ucm_upgrade_server::changelog_line( (string) file_get_contents( $plugin_file ), $new_plugin_version );
			if ( strlen( $line ) ) {
				$additional_info = ' ( ' . $line . ')';
			}
		}

		$update_plugins[ $plugin_name ]['description'] = 'Update to plugin: ' . $plugin_name . $additional_info;
		$update_plugins[ $plugin_name ]['files']       = ucm_upgrade_server::files_for_client( $root, $application, $files, $client_protocol );
		$update_plugins[ $plugin_name ]['folders']     = $folders;
		$update_plugins[ $plugin_name ]['recommended'] = $recommended_install;
	}

	// An install too far behind gets the plumbing first and everything else next time
	// round, because the later updates assume the earlier ones are in place.
	$backlog = array(
		array(
			'behind' => ( ! isset( $current_plugins['backup'] ) || $current_plugins['backup'] < 2.111
			              || ! isset( $current_plugins['config'] ) || $current_plugins['config'] < 2.385 ),
			'first'  => array( 'config', 'backup', 'core', 'theme', 'form', 'db' ),
			'demote' => true,
		),
		array(
			'behind' => ( ! isset( $current_plugins['db'] ) || $current_plugins['db'] < 2.13
			              || ! isset( $current_plugins['language'] ) || $current_plugins['language'] < 2.179 ),
			'first'  => array( 'db', 'language', 'session' ),
			'demote' => false,
		),
	);
	foreach ( $backlog as $stage ) {
		if ( ! $stage['behind'] ) {
			continue;
		}
		$applies = false;
		foreach ( $stage['first'] as $name ) {
			if ( isset( $update_plugins[ $name ] ) ) {
				$applies                 = true;
				$check_again_for_updates = true;
			}
		}
		if ( ! $applies ) {
			continue;
		}
		if ( $stage['demote'] ) {
			foreach ( $update_plugins as $name => $ignored ) {
				if ( ! in_array( $name, $stage['first'], true ) ) {
					$update_plugins[ $name ]['recommended'] = 0;
					$update_plugins[ $name ]['force']       = 0;
				}
			}
		} else {
			$move = array();
			foreach ( $stage['first'] as $name ) {
				if ( isset( $update_plugins[ $name ] ) ) {
					$move[ $name ] = $update_plugins[ $name ];
					unset( $update_plugins[ $name ] );
				}
			}
			$update_plugins = $move + $update_plugins;
		}
	}

	if ( ucm_upgrade_server::offers_core_files( $do_upgrade, $requested_plugin, $server_release, $client_release ) ) {
		$do_upgrade  = true;
		$new_version = max( $new_version, 2.3 );
		if ( ucm_upgrade_server::release_is_newer( $server_release, $client_release ) ) {
			$we_are_recommending_an_item_upgrade = true;
		}

		$send_libraries = ucm_upgrade_server::client_needs_libraries(
			ucm_upgrade_server::libraries_fingerprint( $root ),
			isset( $_REQUEST['libraries'] ) && is_string( $_REQUEST['libraries'] ) ? $_REQUEST['libraries'] : null
		);
		$core_rows      = ucm_upgrade_server::core_rows( $root, $application, $new_version, $we_are_recommending_an_item_upgrade, $send_libraries, $client_protocol );
		$init_file      = ucm_upgrade_server::source_path( $root, $application, 'init.php' );
		$core_rows      = ucm_upgrade_server::label_release( $core_rows, $server_release, $client_release, $init_file ? file_get_contents( $init_file ) : false );
		$update_plugins = ucm_upgrade_server::install_order( $update_plugins, $core_rows );
	}

	// Read the bodies, and hash every one of them on the way past.
	//
	// Only the response that actually carries file contents carries checksums, because
	// hashing is reading: doing it for the plain "what updates are there" call would mean
	// reading the whole source tree on every check, for a number the client has no use for
	// until it is about to write something.
	if ( $get_file_contents ) {
		foreach ( $update_plugins as $update_plugin_id => $update_plugin ) {
			if ( $requested_plugin && $update_plugin_id != $requested_plugin ) {
				continue;
			}
			$update_plugins[ $update_plugin_id ]['file_checksums'] = array();
			if ( empty( $update_plugin['files'] ) || ! is_array( $update_plugin['files'] ) ) {
				continue;
			}
			foreach ( $update_plugin['files'] as $file ) {
				$file_to_get = ucm_upgrade_server_source_path( $root, $application, $file );
				if ( ! $file_to_get ) {
					continue;
				}
				$contents = (string) file_get_contents( $file_to_get );
				$update_plugins[ $update_plugin_id ]['file_contents'][ $file ] = base64_encode( $contents );
				$checksum = ucm_upgrade_server::checksum( $contents );
				if ( false !== $checksum ) {
					$update_plugins[ $update_plugin_id ]['file_checksums'][ $file ] = $checksum;
				}
			}
		}
	}

	if ( ! $do_upgrade ) {
		// Matches the old server, which printed nothing at all when there was no update.
		return;
	}

	if ( $return_as_zip && $get_file_contents ) {
		ucm_upgrade_server_send_zip( $update_plugins );

		return;
	}

	$result = array(
		'protocol'          => ucm_upgrade_server::PROTOCOL,
		'available_updates' => array(
			'message'     => $message,
			'new_version' => $new_version,
			'plugins'     => $update_plugins,
			'check_again' => $check_again_for_updates,
		),
		'licence_codes'     => array(),
	);

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/json; charset=utf-8' );
	}
	echo json_encode( $result );
}

/**
 * Where a file for this response is read from. See ucm_upgrade_server::source_path().
 *
 * @return string|false
 */
function ucm_upgrade_server_source_path( $root, $application, $file ) {
	if ( ! ucm_upgrade_server::is_servable_release_path( $file ) ) {
		return false;
	}

	return ucm_upgrade_server::source_path( $root, $application, $file );
}

/**
 * Send the whole release as one zip, for the old one-file installer.
 *
 * Every file comes from the list scripts/build-release.sh wrote into the release, so the
 * zip is the CodeCanyon zip's Main Files: the real includes/config.php (the setup wizard
 * writes config.local.php beside it), vendor/, and the guard files in temp/ and the upload
 * folders, but never what a customer has put in those folders. If this server cannot
 * produce exactly that, it sends no zip at all, because an install that half works is
 * worse than one that says why it stopped.
 */
function ucm_upgrade_server_send_release_zip( $root, $application ) {
	$manifest = ucm_upgrade_server::release_manifest( $root );
	if ( ! $manifest ) {
		error_log( 'UCM update server: no ' . ucm_upgrade_server::RELEASE_MANIFEST . ', so no install zip. Deploy this server from scripts/build-release.sh.' );
		ucm_upgrade_server_refuse_zip();

		return;
	}

	$release = ucm_upgrade_server::release_files( $root, $application, $manifest );
	if ( $release['missing'] || $release['refused'] ) {
		$problems = array();
		foreach ( array( 'missing' => 'Missing', 'refused' => 'Refused' ) as $key => $label ) {
			if ( $release[ $key ] ) {
				$problems[] = $label . ': ' . implode( ', ', array_slice( $release[ $key ], 0, 20 ) )
					. ( count( $release[ $key ] ) > 20 ? ' and ' . ( count( $release[ $key ] ) - 20 ) . ' more' : '' ) . '.';
			}
		}
		error_log( 'UCM update server: the install zip would not match the release. ' . implode( ' ', $problems ) );
		ucm_upgrade_server_refuse_zip();

		return;
	}

	$zip_file_name = tempnam( sys_get_temp_dir(), 'UCMZip' );
	if ( ! $zip_file_name || ! ucm_upgrade_server::write_release_zip( $zip_file_name, $release['files'] ) ) {
		error_log( 'UCM update server: could not write the install zip to ' . sys_get_temp_dir() . '.' );
		if ( $zip_file_name ) {
			@unlink( $zip_file_name );
		}
		ucm_upgrade_server_refuse_zip();

		return;
	}

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/zip' );
		header( 'Content-Length: ' . filesize( $zip_file_name ) );
		header( 'Content-Disposition: attachment; filename="ucm-package.zip"' );
	}
	readfile( $zip_file_name );
	unlink( $zip_file_name );
}

/**
 * Say no to an install zip, in the shape an old client already knows how to show.
 *
 * The reason goes to the server's error log rather than to the caller.
 */
function ucm_upgrade_server_refuse_zip() {
	if ( ! headers_sent() ) {
		header( 'HTTP/1.1 503 Service Unavailable' );
		header( 'Content-Type: application/json; charset=utf-8' );
	}
	echo json_encode( array(
		'available_updates' => array(
			'message'     => 'The install package is not available from the update server right now. Please download the product from CodeCanyon instead.',
			'new_version' => 0,
			'plugins'     => array(),
			'check_again' => 0,
		),
		'licence_codes'     => array(),
	) );
}

/** Stream the selected files back as a zip rather than as JSON. */
function ucm_upgrade_server_send_zip( array $update_plugins ) {
	$zip_file_name = tempnam( sys_get_temp_dir(), 'UCMZip' );
	if ( ! $zip_file_name ) {
		return;
	}
	$zip = new ZipArchive();
	$zip->open( $zip_file_name, ZipArchive::OVERWRITE );
	foreach ( $update_plugins as $update_plugin ) {
		if ( ! empty( $update_plugin['folders'] ) && is_array( $update_plugin['folders'] ) ) {
			foreach ( $update_plugin['folders'] as $folder ) {
				$zip->addEmptyDir( $folder );
			}
		}
		if ( ! empty( $update_plugin['file_contents'] ) && is_array( $update_plugin['file_contents'] ) ) {
			foreach ( $update_plugin['file_contents'] as $filename => $contents ) {
				$zip->addFromString( $filename, (string) base64_decode( $contents ) );
			}
		}
	}
	$zip->close();

	if ( ! headers_sent() ) {
		header( 'Content-Type: application/zip' );
		header( 'Content-Length: ' . filesize( $zip_file_name ) );
		header( 'Content-Disposition: attachment; filename="ucm-package.zip"' );
	}
	readfile( $zip_file_name );
	unlink( $zip_file_name );
}
