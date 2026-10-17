<?php
/**
 * Local upgrade test helper. Edits an unpacked release tree in place, so the update
 * server has something newer to offer. It refuses a git checkout, so it cannot edit the
 * repository by mistake.
 *
 *   php scripts/upgrade-test-tools.php bump        <root> <plugin> <note>        version + 0.001, with a changelog line
 *   php scripts/upgrade-test-tools.php set-version <root> <plugin> <version>     set an exact version (no changelog line)
 *   php scripts/upgrade-test-tools.php add-column  <root> <plugin> <table> <col> make the plugin's upgrade SQL add a column
 *   php scripts/upgrade-test-tools.php help-marker <root> <slug> <text>          put a marker at the start of an article's summary in the help index
 *   php scripts/upgrade-test-tools.php help-map    <root> <plugin> <page> <slug> point one screen in a plugin's screen map at another article
 *   php scripts/upgrade-test-tools.php version     <root> <plugin>               print the plugin's version
 *   php scripts/upgrade-test-tools.php plugins-json <root>                       every plugin's version, as the client sends it
 *   php scripts/upgrade-test-tools.php summarise   < response.json               the server's answer: message, then each offer in order
 */

$cmd  = isset( $argv[1] ) ? $argv[1] : '';
$root = rtrim( isset( $argv[2] ) ? $argv[2] : '', '/' );

function ut_fail( $message ) {
	fwrite( STDERR, $message . "\n" );
	exit( 1 );
}

if ( 'summarise' === $cmd ) {
	$raw = stream_get_contents( STDIN );
	if ( '' === trim( (string) $raw ) ) {
		// upgrade_server.php prints nothing at all when it has nothing to offer, as the old
		// server did, so an empty answer is the "no updates" answer rather than a failure.
		echo "message:     (none - the server sent an empty answer, which means nothing to update)\n";
		echo "offers:      0\n";
		exit( 0 );
	}
	$data = json_decode( (string) $raw, true );
	if ( ! is_array( $data ) || ! isset( $data['available_updates'] ) ) {
		ut_fail( 'not an update server response. First 300 bytes: ' . substr( (string) $raw, 0, 300 ) );
	}
	$updates = $data['available_updates'];
	echo 'protocol:    ', isset( $data['protocol'] ) ? $data['protocol'] : '(none)', "\n";
	echo 'message:     ', isset( $updates['message'] ) && strlen( (string) $updates['message'] ) ? $updates['message'] : '(none)', "\n";
	echo 'new_version: ', isset( $updates['new_version'] ) ? $updates['new_version'] : '', "\n";
	echo 'check_again: ', ! empty( $updates['check_again'] ) ? 'yes' : 'no', "\n";
	echo 'licences:    ', isset( $data['licence_codes'] ) ? json_encode( $data['licence_codes'] ) : '(none)', "\n";
	$plugins = isset( $updates['plugins'] ) && is_array( $updates['plugins'] ) ? $updates['plugins'] : array();
	echo 'offers:      ', count( $plugins ), " (in the order the client installs them)\n";
	$position = 0;
	foreach ( $plugins as $key => $offer ) {
		printf(
			"  %2d. %-24s %s -> %s  recommended=%s force=%s files=%d  %s\n",
			++$position,
			$key,
			isset( $offer['installed_version'] ) ? $offer['installed_version'] : '?',
			isset( $offer['available_version'] ) ? $offer['available_version'] : '?',
			! empty( $offer['recommended'] ) ? 'y' : 'n',
			! empty( $offer['force'] ) ? 'y' : 'n',
			isset( $offer['files'] ) && is_array( $offer['files'] ) ? count( $offer['files'] ) : 0,
			isset( $offer['description'] ) ? $offer['description'] : ''
		);
	}
	exit( 0 );
}

if ( ! is_file( $root . '/init.php' ) ) {
	ut_fail( "refusing: $root does not look like a UCM install (no init.php)" );
}
if ( file_exists( $root . '/.git' ) ) {
	ut_fail( "refusing: $root is a git checkout. Edit an unpacked release, never the repository." );
}

function ut_plugin_file( $root, $name ) {
	if ( ! preg_match( '#^[a-z0-9_]+$#', $name ) ) {
		ut_fail( "bad plugin name: $name" );
	}
	$file = "$root/includes/plugin_$name/$name.php";
	if ( ! is_file( $file ) ) {
		ut_fail( "no plugin file at $file" );
	}

	return $file;
}

/** Plugins declare their version as either $this->version = x; or public $version = x; */
const UT_VERSION_PATTERN = '/(\$this->version|public\s+\$version)\s*=\s*([0-9.]+)\s*;/';

function ut_version_of( $source, $file ) {
	if ( ! preg_match( UT_VERSION_PATTERN, $source, $match ) ) {
		ut_fail( "no version declaration in $file" );
	}

	return $match[2];
}

switch ( $cmd ) {
	case 'version':
		$file = ut_plugin_file( $root, $argv[3] );
		echo ut_version_of( file_get_contents( $file ), $file ), "\n";
		break;

	case 'plugins-json':
		// The same name => version map module_config::check_for_upgrades() posts, read from
		// the files rather than by booting the install.
		$versions = array();
		foreach ( glob( "$root/includes/plugin_*", GLOB_ONLYDIR ) as $directory ) {
			$name = substr( basename( $directory ), strlen( 'plugin_' ) );
			$file = "$directory/$name.php";
			if ( is_file( $file ) && preg_match( UT_VERSION_PATTERN, file_get_contents( $file ), $match ) ) {
				$versions[ $name ] = $match[2];
			}
		}
		ksort( $versions );
		echo json_encode( $versions ), "\n";
		break;

	case 'bump':
	case 'set-version':
		$name   = $argv[3];
		$file   = ut_plugin_file( $root, $name );
		$source = file_get_contents( $file );
		$old    = ut_version_of( $source, $file );
		if ( 'bump' === $cmd ) {
			$new  = (string) round( (float) $old + 0.001, 3 );
			$note = isset( $argv[4] ) && strlen( $argv[4] ) ? $argv[4] : 'local upgrade test';
			// The update server reads the description for a version out of a "//<version> - <date> - <text>"
			// comment anywhere in the plugin file, so the changelog line rides on the version line.
			$line = '{decl} = ' . $new . '; //' . $new . ' - ' . date( 'Y-m-d' ) . ' - ' . $note;
		} else {
			if ( ! isset( $argv[4] ) || ! is_numeric( $argv[4] ) ) {
				ut_fail( 'set-version needs a numeric version' );
			}
			$new  = $argv[4];
			$line = '{decl} = ' . $new . ';';
		}
		$source = preg_replace_callback(
			UT_VERSION_PATTERN,
			function ( $match ) use ( $line ) {
				// Keep whichever form of declaration the plugin uses.
				return str_replace( '{decl}', $match[1], $line );
			},
			$source,
			1
		);
		file_put_contents( $file, $source );
		echo "$name: $old -> $new\n";
		break;

	case 'add-column':
		list( , , , $name, $table, $column ) = $argv + array_fill( 0, 6, '' );
		if ( ! preg_match( '#^[a-z0-9_]+$#', $table ) || ! preg_match( '#^[a-z0-9_]+$#', $column ) ) {
			ut_fail( 'table and column must be lower case letters, digits and underscores' );
		}
		$file   = ut_plugin_file( $root, $name );
		$source = file_get_contents( $file );
		$start  = strpos( $source, 'function get_upgrade_sql(' );
		if ( false === $start ) {
			ut_fail( "$name has no get_upgrade_sql() - pick a plugin that has one, eg faq" );
		}
		// Add to whatever the function returns, so the SQL goes through the real upgrade path
		// rather than being run behind its back.
		if ( ! preg_match( '/return\s+(\$[a-z_]+)\s*;/', $source, $match, PREG_OFFSET_CAPTURE, $start ) ) {
			ut_fail( "could not find the return statement of $name::get_upgrade_sql()" );
		}
		$variable = $match[1][0];
		$at       = $match[0][1];
		$inject   = "// local upgrade test\n\t\t\$ut_fields = get_fields( '$table' );\n"
		            . "\t\tif ( ! isset( \$ut_fields['$column'] ) ) {\n"
		            . "\t\t\t$variable .= 'ALTER TABLE `' . _DB_PREFIX . '$table` ADD `$column` INT(11) NOT NULL DEFAULT 0;';\n"
		            . "\t\t}\n\t\t";
		$source   = substr( $source, 0, $at ) . $inject . substr( $source, $at );
		file_put_contents( $file, $source );
		echo "$name: get_upgrade_sql() now adds $table.$column\n";
		break;

	case 'help-marker':
		// Only the index of articles ships with the help plugin; the article text is read
		// from the documentation site. So the marker goes into the summary, which is what the
		// help panel shows when it cannot reach the site.
		$slug = $argv[3];
		$text = isset( $argv[4] ) ? $argv[4] : 'local upgrade test marker';
		$file = "$root/includes/plugin_help/content/_index.json";
		$index = is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
		if ( ! is_array( $index ) || ! isset( $index[ $slug ] ) ) {
			ut_fail( "no article $slug in $file" );
		}
		$summary                  = isset( $index[ $slug ]['summary'] ) ? (string) $index[ $slug ]['summary'] : '';
		$index[ $slug ]['summary'] = $text . ' ' . $summary;
		file_put_contents( $file, json_encode( $index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
		echo "help index $slug: marker added to the summary\n";
		break;

	case 'help-map':
		// A plugin's screen map says which article each of its screens opens. It lives in the
		// plugin's own folder, so it reaches an install with that plugin's update.
		list( , , , $name, $page, $slug ) = $argv + array_fill( 0, 6, '' );
		$file = "$root/includes/plugin_$name/$name.help.json";
		$map  = is_file( $file ) ? json_decode( file_get_contents( $file ), true ) : null;
		if ( ! is_array( $map ) || ! isset( $map['pages'] ) || ! is_array( $map['pages'] ) ) {
			ut_fail( "no screen map at $file" );
		}
		$index = json_decode( (string) @file_get_contents( "$root/includes/plugin_help/content/_index.json" ), true );
		if ( ! is_array( $index ) || ! isset( $index[ $slug ] ) ) {
			ut_fail( "$slug is not in the help index, so the app would refuse it" );
		}
		$old                   = isset( $map['pages'][ $page ] ) ? $map['pages'][ $page ] : '(none)';
		$map['pages'][ $page ] = $slug;
		file_put_contents( $file, json_encode( $map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
		echo "$name screen map: $page $old -> $slug\n";
		break;

	default:
		ut_fail( 'usage: see the comment at the top of scripts/upgrade-test-tools.php' );
}
