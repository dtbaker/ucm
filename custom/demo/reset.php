<?php
/**
 * Puts the public demo's database back to a known-good state, and keeps its sample
 * logins working.
 *
 * Run from a shell on the demo server, from the site's root folder:
 *
 *   php -q custom/demo/reset.php snapshot   save the database as it is now, as the copy to reset to
 *   php -q custom/demo/reset.php restore    put that copy back, then repair
 *   php -q custom/demo/reset.php repair     make the sample logins work
 *   php -q custom/demo/reset.php ensure     repair, then take the first snapshot if there is none
 *   php -q custom/demo/reset.php check      report what repair would change, changing nothing
 *
 * The "Reset demo" GitHub workflow runs restore on a schedule, and "Deploy to demo" runs
 * ensure after every deploy. See custom/README.md.
 *
 * What repair makes true, for each of admin@, user@ and staff@example.com, the logins the
 * demo's own login page advertises with the password "password":
 *
 *  - the account exists;
 *  - its password is stored as a real hash. The install data stores the literal word
 *    "password", and since 4.0.0 only the administrator may sign in with that, so the
 *    customer and staff logins were refused;
 *  - a non-admin account has a role that grants "Can User Login", or it is turned away
 *    whatever its password. A missing one is created with that permission alone. It is
 *    the least that lets the login work: widen it in Settings > Security Roles, then
 *    take a new snapshot, and repair will leave it alone.
 *
 * snapshot repairs first and refuses to save anything that is still broken, so a snapshot
 * always holds all three working logins.
 *
 * Exit codes: 0 fine, 1 failed, 2 a login still cannot work, 64 unknown command.
 */

// Command line only: this file sits inside the web root and replaces the database.
require_once __DIR__ . '/shell-only.php';
if ( ! ucm_demo_is_shell( PHP_SAPI, $_SERVER ) ) {
	http_response_code( 404 );
	exit( 1 );
}

// The CGI build has no STDERR constant and a 30 second time limit, and the app's own
// config turns error display off.
if ( ! defined( 'STDERR' ) ) {
	define( 'STDERR', fopen( 'php://stderr', 'w' ) );
}
@set_time_limit( 0 );

$root = dirname( __DIR__, 2 );
chdir( $root );
require $root . '/includes/config.php';
require_once $root . '/includes/functions.php';
error_reporting( E_ALL );
ini_set( 'display_errors', 'stderr' );

if ( ! defined( '_DEMO_MODE' ) || ! _DEMO_MODE ) {
	fwrite( STDERR, "Refusing to run: this is not the demo (_DEMO_MODE is off). It would replace the database.\n" );
	exit( 1 );
}

/**
 * The logins the demo's login page advertises, all with the password "password".
 *
 * 'row' is what a missing account is created with: the installer's own sample rows.
 * 'role' is the role a non-admin account gets if it has none that lets it sign in.
 */
const UCM_DEMO_ACCOUNTS = array(
	'admin@example.com' => array(
		'label' => 'Administrator',
		'role'  => '',
		'row'   => array( 'name' => 'Administrator', 'customer_id' => 0, 'vendor_id' => 0, 'is_staff' => -1, 'split_hours' => 0, 'hourly_rate' => 0 ),
	),
	'user@example.com'  => array(
		'label' => 'Customer',
		'role'  => 'Demo Customer',
		'row'   => array( 'name' => 'User', 'customer_id' => 0, 'vendor_id' => 0, 'is_staff' => -1, 'split_hours' => 0, 'hourly_rate' => 0 ),
	),
	'staff@example.com' => array(
		'label' => 'Staff',
		'role'  => 'Demo Staff',
		'row'   => array( 'name' => 'Staff Member', 'customer_id' => 0, 'vendor_id' => 1, 'is_staff' => 1, 'split_hours' => 1, 'hourly_rate' => 50 ),
	),
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
 * Insert a row, naming only the columns this table has and filling any other required
 * column with a neutral value. The demo's database is years older than the install data
 * this file was written against, and a missing or extra column should not stop a repair.
 *
 * @return int the new row's auto-increment id, 0 if the table has none
 */
function ucm_demo_insert( mysqli $db, $table, array $values ) {
	$table   = _DB_PREFIX . $table;
	$columns = array();
	foreach ( $db->query( 'SHOW COLUMNS FROM `' . $table . '`' )->fetch_all( MYSQLI_ASSOC ) as $column ) {
		$columns[ $column['Field'] ] = $column;
	}
	$row = array();
	foreach ( $columns as $name => $column ) {
		if ( array_key_exists( $name, $values ) ) {
			$row[ $name ] = $values[ $name ];
		} elseif ( $column['Null'] === 'NO' && $column['Default'] === null && stripos( $column['Extra'], 'auto_increment' ) === false ) {
			$type         = strtolower( $column['Type'] );
			$row[ $name ] = strpos( $type, 'date' ) === 0 ? date( 'Y-m-d' ) : ( preg_match( '/int|decimal|float|double/', $type ) ? 0 : '' );
		}
	}
	$names        = array();
	$placeholders = array();
	$params       = array();
	foreach ( $row as $name => $value ) {
		$names[]        = '`' . $name . '`';
		$placeholders[] = '?';
		$params[]       = $value === null ? null : (string) $value;
	}
	$statement = $db->prepare( 'INSERT INTO `' . $table . '` (' . implode( ',', $names ) . ') VALUES (' . implode( ',', $placeholders ) . ')' );
	$statement->bind_param( str_repeat( 's', count( $params ) ), ...$params );
	$statement->execute();

	return (int) $db->insert_id;
}

/** One value from a query with bound parameters, or null when there is no row. */
function ucm_demo_value( mysqli $db, $sql, $types = '', array $params = array() ) {
	$statement = $db->prepare( $sql );
	if ( $params ) {
		$statement->bind_param( $types, ...$params );
	}
	$statement->execute();
	$row = $statement->get_result()->fetch_row();

	return $row ? $row[0] : null;
}

/**
 * The ids of the "Can User Login" permission, creating it if the table has none.
 *
 * Matched on category and module as well as the name, the way the app looks it up. The
 * table can hold more than one row for a name, and the app uses whichever it finds, so a
 * role has to carry all of them to be sure.
 *
 * @return int[]
 */
function ucm_demo_login_permissions( mysqli $db, $write ) {
	$p   = _DB_PREFIX;
	$ids = array();
	$q   = $db->prepare( "SELECT security_permission_id FROM `{$p}security_permission` WHERE name = 'Can User Login' AND category = 'User Specific' AND module = 'config'" );
	$q->execute();
	foreach ( $q->get_result()->fetch_all() as $row ) {
		$ids[] = (int) $row[0];
	}
	if ( ! $ids && $write ) {
		$ids[] = ucm_demo_insert( $db, 'security_permission', array(
			'name'            => 'Can User Login',
			'category'        => 'User Specific',
			'module'          => 'config',
			'description'     => 'checkbox',
			'available_perms' => serialize( array( 'view' => true ) ),
		) );
		echo "  Added the missing \"Can User Login\" permission.\n";
	}

	return $ids;
}

/** Does this user have a role that grants every "Can User Login" row? */
function ucm_demo_can_login( mysqli $db, $user_id, array $permission_ids ) {
	if ( ! $permission_ids ) {
		return false;
	}
	$p = _DB_PREFIX;
	foreach ( $permission_ids as $permission_id ) {
		$granted = ucm_demo_value(
			$db,
			"SELECT COUNT(*) FROM `{$p}user_role` ur JOIN `{$p}security_role_perm` rp ON rp.security_role_id = ur.security_role_id"
			. ' WHERE ur.user_id = ? AND rp.security_permission_id = ? AND rp.view = 1',
			'ii',
			array( $user_id, $permission_id )
		);
		if ( ! (int) $granted ) {
			return false;
		}
	}

	return true;
}

/** Give a user a role that lets them sign in, creating the role if it does not exist. */
function ucm_demo_grant_login( mysqli $db, $user_id, $role_name, array $permission_ids ) {
	$p       = _DB_PREFIX;
	$role_id = ucm_demo_value( $db, "SELECT security_role_id FROM `{$p}security_role` WHERE name = ? ORDER BY security_role_id LIMIT 1", 's', array( $role_name ) );
	if ( $role_id === null ) {
		$role_id = ucm_demo_insert( $db, 'security_role', array( 'name' => $role_name, 'description' => 'Can sign in to the demo', 'create_user_id' => 1 ) );
	}
	$role_id = (int) $role_id;
	foreach ( $permission_ids as $permission_id ) {
		$has = ucm_demo_value( $db, "SELECT COUNT(*) FROM `{$p}security_role_perm` WHERE security_role_id = ? AND security_permission_id = ?", 'ii', array( $role_id, $permission_id ) );
		if ( (int) $has ) {
			$update = $db->prepare( "UPDATE `{$p}security_role_perm` SET view = 1 WHERE security_role_id = ? AND security_permission_id = ?" );
			$update->bind_param( 'ii', $role_id, $permission_id );
			$update->execute();
		} else {
			ucm_demo_insert( $db, 'security_role_perm', array( 'security_role_id' => $role_id, 'security_permission_id' => $permission_id, 'view' => 1, 'create_user_id' => 1 ) );
		}
	}
	if ( ! (int) ucm_demo_value( $db, "SELECT COUNT(*) FROM `{$p}user_role` WHERE user_id = ? AND security_role_id = ?", 'ii', array( $user_id, $role_id ) ) ) {
		ucm_demo_insert( $db, 'user_role', array( 'user_id' => $user_id, 'security_role_id' => $role_id ) );
	}
}

/** Is this stored value a password hash, rather than plain text or an unsalted MD5? */
function ucm_demo_is_hash( $stored ) {
	return (bool) preg_match( '/^\$(2[aby]|argon2(id|i)?)\$/', (string) $stored );
}

/**
 * Make each sample login usable, or say why it cannot be.
 *
 * @param bool $write false only reports
 *
 * @return string[] what is wrong and could not be put right; empty when every account can sign in
 */
function ucm_demo_repair( mysqli $db, $write ) {
	$problems = array();
	$p        = _DB_PREFIX;
	$had_permission = (bool) ucm_demo_login_permissions( $db, false );
	$permission     = ucm_demo_login_permissions( $db, $write );
	$changed        = $write && ! $had_permission && $permission;
	foreach ( UCM_DEMO_ACCOUNTS as $email => $account ) {
		$label = $account['label'] . ' (' . $email . ')';
		$user  = null;
		$find  = $db->prepare( "SELECT user_id, password FROM `{$p}user` WHERE email = ? ORDER BY user_id LIMIT 1" );
		$find->bind_param( 's', $email );
		$find->execute();
		$user = $find->get_result()->fetch_assoc();

		if ( ! $user ) {
			if ( ! $write ) {
				$problems[] = "$label: there is no such user.";
				continue;
			}
			$row                = $account['row'];
			$row['email']       = $email;
			$row['language']    = 'en';
			$row['status_id']   = 1;
			$row['date_created'] = date( 'Y-m-d' );
			$user_id            = ucm_demo_insert( $db, 'user', $row );
			$user               = array( 'user_id' => $user_id, 'password' => '' );
			$changed            = true;
			echo "  $label: created.\n";
		}
		$user_id = (int) $user['user_id'];

		$stored = (string) $user['password'];
		$works  = ucm_demo_is_hash( $stored )
			&& password_verify( UCM_DEMO_PASSWORD, $stored )
			&& ! ucm_password_needs_rehash( $stored );
		if ( ! $works ) {
			if ( $write ) {
				$hash   = ucm_password_hash( UCM_DEMO_PASSWORD );
				$update = $db->prepare( "UPDATE `{$p}user` SET password = ? WHERE user_id = ?" );
				$update->bind_param( 'si', $hash, $user_id );
				$update->execute();
				$changed = true;
				echo "  $label: password stored as a hash.\n";
			} else {
				$problems[] = "$label: the password is not stored as a hash of \"" . UCM_DEMO_PASSWORD . '", so it will be refused.';
			}
		}

		// The administrator is user 1, which is never asked for "Can User Login".
		if ( $user_id === 1 || ucm_demo_can_login( $db, $user_id, $permission ) ) {
			continue;
		}
		if ( $write ) {
			if ( ! $permission ) {
				$permission = ucm_demo_login_permissions( $db, true );
			}
			ucm_demo_grant_login( $db, $user_id, $account['role'], $permission );
			$changed = true;
			echo "  $label: given the role \"" . $account['role'] . "\", which can sign in.\n";
			if ( ! ucm_demo_can_login( $db, $user_id, $permission ) ) {
				$problems[] = "$label: still has no role that grants \"Can User Login\" after giving it one.";
			}
		} else {
			$problems[] = "$label: no role that grants \"Can User Login\".";
		}
	}
	if ( $changed ) {
		// A cached permission list would hide the repair.
		ucm_demo_clear_caches( $db, dirname( __DIR__, 2 ) );
	}

	return $problems;
}

/**
 * Repair, then save the snapshot, unless a login still cannot work: a snapshot that cannot
 * sign in would hand the next reset broken logins.
 *
 * @return string[] problems; empty when the snapshot was saved
 */
function ucm_demo_take_snapshot( mysqli $db, $file ) {
	echo "Repairing the demo logins:\n";
	$problems = ucm_demo_repair( $db, true );
	if ( $problems ) {
		return $problems;
	}
	$rows = ucm_demo_snapshot( $db, $file );
	echo "Snapshot saved: $rows rows to $file\n";

	return array();
}

$command = ucm_demo_command(
	isset( $argv ) ? $argv : ( isset( $_SERVER['argv'] ) ? $_SERVER['argv'] : array() ),
	getenv( 'UCM_DEMO_ACTION' ),
	@file_get_contents( '/proc/self/cmdline' ),
	__FILE__
);
$snapshot = ucm_demo_snapshot_path( $root );
$problems = array();

try {
	$db = ucm_demo_db();
	switch ( $command ) {
		case 'snapshot':
			$problems = ucm_demo_take_snapshot( $db, $snapshot );
			break;

		case 'restore':
			if ( ! is_file( $snapshot ) ) {
				// Nothing to reset to yet, so this run makes the first one from the demo as
				// it is: repaired, with working logins. A later run has something to return to.
				echo "No snapshot at $snapshot yet, so the demo is saved as it is now.\n";
				$problems = ucm_demo_take_snapshot( $db, $snapshot );
				break;
			}
			$statements = ucm_demo_restore( $db, $snapshot );
			echo "Restored $statements statements from $snapshot\n";
			ucm_demo_clear_caches( $db, $root );
			// The snapshot may predate a password change.
			echo "Repairing the demo logins:\n";
			$problems = ucm_demo_repair( $db, true );
			break;

		case 'repair':
			echo "Repairing the demo logins:\n";
			$problems = ucm_demo_repair( $db, true );
			break;

		case 'ensure':
			if ( ! is_file( $snapshot ) ) {
				echo "No snapshot yet, so the demo is saved as it is now.\n";
				$problems = ucm_demo_take_snapshot( $db, $snapshot );
			} else {
				echo "Repairing the demo logins:\n";
				$problems = ucm_demo_repair( $db, true );
			}
			break;

		case 'check':
			$problems = ucm_demo_repair( $db, false );
			break;

		default:
			fwrite( STDERR, "Usage: php -q custom/demo/reset.php snapshot|restore|repair|ensure|check\n" );
			exit( 64 );
	}
} catch ( Throwable $e ) {
	fwrite( STDERR, 'Failed: ' . $e->getMessage() . "\n" );
	exit( 1 );
}

if ( $problems ) {
	fwrite( STDERR, "These demo logins will not work:\n  " . implode( "\n  ", $problems ) . "\n" );
	exit( 2 );
}
echo "Every demo login can sign in.\n";
