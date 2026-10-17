<?php
/**
 * Puts the public demo's database back to a known-good state, and keeps its sample
 * logins working.
 *
 * Run from the command line on the demo server, from the site's root folder:
 *
 *   php custom/demo/reset.php snapshot   save the database as it is now, as the copy to reset to
 *   php custom/demo/reset.php restore    put that copy back, then repair
 *   php custom/demo/reset.php repair     make the sample logins work, and check they can sign in
 *   php custom/demo/reset.php check      the same checks as repair, changing nothing
 *
 * The "Reset demo" GitHub workflow runs restore on a schedule, and "Deploy to demo" runs
 * repair after every deploy. See custom/README.md for the one-off setup.
 *
 * Why repair exists: the install data stores the literal word "password" for the sample
 * accounts, and since 4.0.0 ucm_password_verify() honours that only for the administrator,
 * so the customer and staff logins the demo advertises were refused. A password stored as
 * a real hash is an ordinary password, so repair stores "password" that way. Those logins
 * are published on the demo's own login page, so this gives nothing away.
 *
 * A non-admin account also needs a role that grants "Can User Login", or it is turned away
 * whatever its password. That is product configuration, not something to invent here, so
 * repair only checks for it and says what to do when it is missing.
 */

// Never over the web. This file sits inside the web root on the demo.
if ( PHP_SAPI !== 'cli' ) {
	http_response_code( 404 );
	exit;
}

$root = dirname( __DIR__, 2 );
chdir( $root );
require $root . '/includes/config.php';

if ( ! defined( '_DEMO_MODE' ) || ! _DEMO_MODE ) {
	fwrite( STDERR, "Refusing to run: this is not the demo (_DEMO_MODE is off). It would replace the database.\n" );
	exit( 1 );
}

/** The logins the demo's login page advertises, all with the password "password". */
const UCM_DEMO_ACCOUNTS = array(
	'admin@example.com' => 'Administrator',
	'user@example.com'  => 'Customer',
	'staff@example.com' => 'Staff',
);
const UCM_DEMO_PASSWORD = 'password';

/**
 * Not strict. The app stores dates as '0000-00-00', which a strict server refuses to
 * insert, and a restore that stops on one has already dropped the table. Keep zero ids as
 * zero rather than letting AUTO_INCREMENT renumber them, the way mysqldump does.
 */
const UCM_DEMO_SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

/**
 * Where the snapshot lives: beside the web root, not in it, so a deploy's rsync --delete
 * never touches it and it is never served.
 */
function ucm_demo_snapshot_path( $root ) {
	$path = getenv( 'UCM_DEMO_SNAPSHOT' );

	return ( $path !== false && $path !== '' ) ? $path : dirname( $root ) . '/demo-snapshot/ucm-demo.sql.gz';
}

function ucm_demo_db() {
	mysqli_report( MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT );
	$db = new mysqli( _DB_SERVER, _DB_USER, _DB_PASS, _DB_NAME );
	$db->set_charset( 'utf8mb4' );
	$db->query( "SET SESSION sql_mode = '" . UCM_DEMO_SQL_MODE . "'" );

	return $db;
}

/** The tables this install owns, by prefix. */
function ucm_demo_tables( mysqli $db ) {
	$like   = $db->real_escape_string( str_replace( array( '\\', '_', '%' ), array( '\\\\', '\\_', '\\%' ), _DB_PREFIX ) ) . '%';
	$tables = array();
	foreach ( $db->query( "SHOW TABLES LIKE '" . $like . "'" )->fetch_all() as $row ) {
		$tables[] = $row[0];
	}

	return $tables;
}

/**
 * Write every table to a gzipped file, one statement per line.
 *
 * real_escape_string() escapes newlines, so a statement never spans a line, and restore
 * can read the file back a line at a time without parsing SQL.
 */
function ucm_demo_snapshot( mysqli $db, $file ) {
	ucm_demo_drop_leftovers( $db );
	if ( ! is_dir( dirname( $file ) ) && ! mkdir( dirname( $file ), 0700, true ) ) {
		throw new RuntimeException( 'Cannot create ' . dirname( $file ) );
	}
	$partial = $file . '.partial';
	$out     = gzopen( $partial, 'wb6' );
	if ( ! $out ) {
		throw new RuntimeException( 'Cannot write ' . $partial );
	}
	gzwrite( $out, "SET FOREIGN_KEY_CHECKS=0;\n" );
	gzwrite( $out, "SET SESSION sql_mode='" . UCM_DEMO_SQL_MODE . "';\n" );
	$rows = 0;
	foreach ( ucm_demo_tables( $db ) as $table ) {
		$create = $db->query( 'SHOW CREATE TABLE `' . $table . '`' )->fetch_row()[1];
		gzwrite( $out, 'DROP TABLE IF EXISTS `' . $table . "`;\n" );
		gzwrite( $out, str_replace( array( "\r", "\n" ), ' ', $create ) . ";\n" );
		$result = $db->query( 'SELECT * FROM `' . $table . '`', MYSQLI_USE_RESULT );
		$batch  = array();
		while ( $row = $result->fetch_row() ) {
			$values = array();
			foreach ( $row as $value ) {
				$values[] = $value === null ? 'NULL' : "'" . $db->real_escape_string( $value ) . "'";
			}
			$batch[] = '(' . implode( ',', $values ) . ')';
			if ( count( $batch ) >= 200 ) {
				gzwrite( $out, 'INSERT INTO `' . $table . '` VALUES ' . implode( ',', $batch ) . ";\n" );
				$batch = array();
			}
			$rows++;
		}
		$result->free();
		if ( $batch ) {
			gzwrite( $out, 'INSERT INTO `' . $table . '` VALUES ' . implode( ',', $batch ) . ";\n" );
		}
	}
	gzwrite( $out, "SET FOREIGN_KEY_CHECKS=1;\n" );
	gzclose( $out );
	chmod( $partial, 0600 );
	// Only now replace the last good copy, so a failed snapshot never leaves a broken one.
	rename( $partial, $file );

	return $rows;
}

/** Suffixes for the tables a restore loads into, and the ones it swaps out. */
const UCM_DEMO_STAGED  = '__restore';
const UCM_DEMO_RETIRED = '__retired';

/**
 * Load the snapshot beside the live tables, then swap it in with one RENAME TABLE.
 *
 * This runs unattended every day. Loading straight over the live tables would leave the
 * demo half-empty if anything failed partway, so every table is built under a staging
 * name first, and the live ones are only touched once all of them have loaded. MySQL
 * applies a multi-table RENAME as a single step, so visitors see the old data or the new
 * data, never a mix. A failure drops the staging tables and leaves the demo as it was.
 *
 * Only reads files this script wrote: one statement per line, and every table name
 * appears straight after DROP TABLE IF EXISTS, CREATE TABLE or INSERT INTO.
 */
function ucm_demo_restore( mysqli $db, $file ) {
	// For the whole connection: the staged tables load in name order, not dependency order,
	// and the retired ones are dropped while others still point at them.
	$db->query( 'SET FOREIGN_KEY_CHECKS=0' );
	ucm_demo_drop_leftovers( $db );
	$in = gzopen( $file, 'rb' );
	if ( ! $in ) {
		throw new RuntimeException( 'Cannot read ' . $file );
	}
	$tables     = array();
	$statements = 0;
	try {
		while ( ( $line = gzgets( $in ) ) !== false ) {
			$line = rtrim( $line, "\r\n" );
			if ( $line === '' ) {
				continue;
			}
			$line = preg_replace_callback(
				'/^(DROP TABLE IF EXISTS|CREATE TABLE|INSERT INTO) `([^`]+)`/',
				function ( $m ) use ( &$tables ) {
					$tables[ $m[2] ] = true;

					return $m[1] . ' `' . $m[2] . UCM_DEMO_STAGED . '`';
				},
				$line,
				1
			);
			if ( strpos( $line, 'CREATE TABLE ' ) === 0 ) {
				// Foreign key names are unique across the whole database, so the staged copy
				// cannot reuse the live table's. Unnamed, MySQL names them after the staged
				// table and renames them with it in the swap. They also have to point at the
				// staged tables, or the swap would leave them on the retired ones.
				$line = preg_replace( '/CONSTRAINT `[^`]+` FOREIGN KEY/', 'FOREIGN KEY', $line );
				$line = preg_replace_callback(
					'/REFERENCES `(' . preg_quote( _DB_PREFIX, '/' ) . '[^`]*)`/',
					function ( $m ) {
						return 'REFERENCES `' . $m[1] . UCM_DEMO_STAGED . '`';
					},
					$line
				);
			}
			$db->query( $line );
			$statements++;
		}
	} catch ( Throwable $e ) {
		gzclose( $in );
		ucm_demo_drop_leftovers( $db );
		throw new RuntimeException( 'The snapshot did not load, so the demo was left as it was: ' . $e->getMessage(), 0, $e );
	}
	gzclose( $in );
	if ( ! $tables ) {
		throw new RuntimeException( 'The snapshot has no tables in it, so the demo was left as it was.' );
	}

	$live    = ucm_demo_tables( $db );
	$renames = array();
	foreach ( array_keys( $tables ) as $table ) {
		if ( in_array( $table, $live, true ) ) {
			$renames[] = '`' . $table . '` TO `' . $table . UCM_DEMO_RETIRED . '`';
		}
		$renames[] = '`' . $table . UCM_DEMO_STAGED . '` TO `' . $table . '`';
	}
	$db->query( 'RENAME TABLE ' . implode( ', ', $renames ) );
	ucm_demo_drop_leftovers( $db );

	return $statements;
}

/** Staging or retired tables from this run, or from one that stopped partway. */
function ucm_demo_drop_leftovers( mysqli $db ) {
	// Retired tables still point at each other, and the snapshot's last line turns these
	// checks back on, so turn them off here rather than relying on the caller.
	$db->query( 'SET FOREIGN_KEY_CHECKS=0' );
	foreach ( ucm_demo_tables( $db ) as $table ) {
		foreach ( array( UCM_DEMO_STAGED, UCM_DEMO_RETIRED ) as $suffix ) {
			if ( substr( $table, -strlen( $suffix ) ) === $suffix ) {
				$db->query( 'DROP TABLE IF EXISTS `' . $table . '`' );
			}
		}
	}
}

/** Cached menus and permissions would otherwise outlive the data they were built from. */
function ucm_demo_clear_caches( mysqli $db, $root ) {
	foreach ( array( 'cache', 'cache_store' ) as $table ) {
		if ( in_array( _DB_PREFIX . $table, ucm_demo_tables( $db ), true ) ) {
			$db->query( 'DELETE FROM `' . _DB_PREFIX . $table . '`' );
		}
	}
	foreach ( glob( $root . '/temp/cache-*' ) ?: array() as $file ) {
		@unlink( $file );
	}
}

/**
 * Make each sample login usable, or say why it cannot be.
 *
 * @return string[] problems; empty when every account can sign in
 */
function ucm_demo_repair( mysqli $db, $write ) {
	$problems = array();
	$p        = _DB_PREFIX;
	foreach ( UCM_DEMO_ACCOUNTS as $email => $label ) {
		$find = $db->prepare( "SELECT user_id, password FROM `{$p}user` WHERE email = ? ORDER BY user_id LIMIT 1" );
		$find->bind_param( 's', $email );
		$find->execute();
		$user = $find->get_result()->fetch_assoc();
		if ( ! $user ) {
			$problems[] = "$label ($email): there is no such user.";
			continue;
		}
		$user_id = (int) $user['user_id'];

		$stored  = (string) $user['password'];
		$hashed  = (bool) preg_match( '/^\$(2[aby]|argon2)/', $stored );
		$works   = $hashed && password_verify( UCM_DEMO_PASSWORD, $stored );
		if ( ! $works ) {
			if ( $write ) {
				$hash   = password_hash( UCM_DEMO_PASSWORD, PASSWORD_DEFAULT );
				$update = $db->prepare( "UPDATE `{$p}user` SET password = ? WHERE user_id = ?" );
				$update->bind_param( 'si', $hash, $user_id );
				$update->execute();
				echo "  $label ($email): password set.\n";
			} else {
				$problems[] = "$label ($email): the password is not stored as a hash, so it will be refused.";
			}
		}

		// The administrator is user 1, which is never asked for "Can User Login".
		if ( $user_id === 1 ) {
			continue;
		}
		$can = $db->prepare(
			"SELECT COUNT(*) FROM `{$p}user_role` ur"
			. " JOIN `{$p}security_role_perm` rp ON rp.security_role_id = ur.security_role_id"
			. " JOIN `{$p}security_permission` sp ON sp.security_permission_id = rp.security_permission_id"
			. " WHERE ur.user_id = ? AND sp.name = 'Can User Login' AND rp.view = 1"
		);
		$can->bind_param( 'i', $user_id );
		$can->execute();
		if ( ! (int) $can->get_result()->fetch_row()[0] ) {
			$problems[] = "$label ($email): no role that grants \"Can User Login\". Give this user a role that does"
				. ' (Settings > Security Roles), check the login works, then run: php custom/demo/reset.php snapshot';
		}
	}

	return $problems;
}

$command  = isset( $argv[1] ) ? $argv[1] : '';
$snapshot = ucm_demo_snapshot_path( $root );

try {
	$db = ucm_demo_db();
	switch ( $command ) {
		case 'snapshot':
			// A snapshot that cannot sign in would hand the next reset broken logins.
			$problems = ucm_demo_repair( $db, true );
			if ( $problems ) {
				fwrite( STDERR, "Not taking a snapshot until every demo login works:\n  " . implode( "\n  ", $problems ) . "\n" );
				exit( 2 );
			}
			$rows = ucm_demo_snapshot( $db, $snapshot );
			echo "Snapshot saved: $rows rows to $snapshot\n";
			break;

		case 'restore':
			if ( ! is_file( $snapshot ) ) {
				// Nothing to reset to yet. Repair is still worth doing, and a missing
				// snapshot is a setup step, not a reason for the daily run to fail.
				echo "No snapshot at $snapshot yet, so nothing was restored. Take one with: php custom/demo/reset.php snapshot\n";
			} else {
				$statements = ucm_demo_restore( $db, $snapshot );
				echo "Restored $statements statements from $snapshot\n";
			}
			ucm_demo_clear_caches( $db, $root );
			// Fall through to repair: the snapshot may predate a password change.
		case 'repair':
			echo "Repairing the demo logins:\n";
			$problems = ucm_demo_repair( $db, true );
			break;

		case 'check':
			$problems = ucm_demo_repair( $db, false );
			break;

		default:
			fwrite( STDERR, "Usage: php custom/demo/reset.php snapshot|restore|repair|check\n" );
			exit( 64 );
	}
} catch ( Throwable $e ) {
	fwrite( STDERR, 'Failed: ' . $e->getMessage() . "\n" );
	exit( 1 );
}

if ( ! empty( $problems ) ) {
	fwrite( STDERR, "These demo logins will not work:\n  " . implode( "\n  ", $problems ) . "\n" );
	exit( 2 );
}
if ( $command !== 'snapshot' ) {
	echo "Every demo login can sign in.\n";
}
