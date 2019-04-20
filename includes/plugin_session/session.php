<?php


class module_session extends module_base {

	public static $session_id = false;
	public static $member_id = false;
	private static $destroyed = false;
	private static $session_hash = false;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function __construct() {

		if(isset($_SESSION))return true;
		if ( self::is_db_sessions_enabled() ) {
			try {
				session_set_save_handler(
					array( $this, 'open' ),
					array( $this, 'close' ),
					array( $this, 'read' ),
					array( $this, 'write' ),
					array( $this, 'destroy' ),
					array( $this, 'gc' )
				);
				// the following prevents unexpected effects when using objects as save handlers
				register_shutdown_function( 'session_write_close' );
			} catch ( Exception $e ) {
				echo "Error Creating Session Handler: " . $e->getMessage();
			}
		}

		return true;
	}

	static $_db_sessions = null;

	public static function is_db_sessions_enabled() {
		if ( _DEMO_MODE ) {
			return false;
		}
		if ( isset( self::$_db_sessions ) ) {
			return self::$_db_sessions;
		}
		// dont run the c() call as it messes with our company integration
		//return module_config::c('sessions_in_database',1); //&& self::db_table_exists('session');
		if ( self::db_table_exists( 'config' ) ) {
			$sql = "SELECT `key`,`val` FROM `" . _DB_PREFIX . "config` WHERE `key` = 'sessions_in_database'";
			$res = qa1( $sql );
			if ( $res && $res['key'] == 'sessions_in_database' ) {
				self::$_db_sessions = $res['val'];

				return self::$_db_sessions;
			} else {
				$sql = "INSERT INTO `" . _DB_PREFIX . "config` SET `key` = 'sessions_in_database', `val` = 1";
				query( $sql );
			}
		}

		return true;
	}

	public function init() {
		$this->module_name     = "session";
		$this->module_position = 0;


		$this->version = 2.168;
		//2.168 - 2019-04-20 - fix database sessions
		//2.167 - 2019-04-06 - fix database sessions
		//2.166 - 2017-05-02 - file path configuration
		//2.165 - 2016-07-13 - big update to mysqli
		//2.164 - 2016-07-10 - big update to mysqli
		//2.163 - 2015-10-29 - database character set improvement
		//2.162 - 2015-01-19 - database session speed improvement
		//2.161 - 2014-02-12 - mb_detect_encoding fix
		//2.16 - 2014-01-30 - encoding issues with database sessions
		//2.15 - 2013-08-30 - added memcache support for huge speed improvements
		//2.14 - 2013-06-21 - session fix for custom company config variables
		//2.13 - 2013-04-30 - session fix when upgrade isn't finished correctly
		//2.12 - 2013-04-26 - session fix on installation
		//2.11 - 2013-04-11 - initial release

	}

	public static function open() {
		// already connected in init.php
		return true;
	}

	public static function read( $session_id ) {
		if ( self::$destroyed ) {
			return '';
		}
		self::$session_id = $session_id;
		if ( ! self::db_table_exists( 'session', true ) ) {
			return (string) @file_get_contents( _UCM_FILE_STORAGE_DIR . "temp/sess_$session_id" );
		}
		$sql = "SELECT `session_data` FROM `" . _DB_PREFIX . "session` WHERE `session_id` = '" . db_escape( self::$session_id ) . "'";
		$res = qa1( $sql );
		if ( $res && isset( $res['session_data'] ) ) {
			$foo = base64_decode( $res['session_data'], true );
			if ( ! $foo && preg_match( '#^!([^!]*)!#', $res['session_data'], $matches ) ) {
				$res['session_data'] = preg_replace( '#^' . preg_quote( $matches[0], '#' ) . '#', '', $res['session_data'] );
				if ( function_exists( 'mb_detect_encoding' ) && mb_detect_encoding( $res['session_data'] ) != $matches[1] ) {
					$res['session_data'] = iconv( mb_detect_encoding( $res['session_data'] ), $matches[1], $res['session_data'] );
				}
			} else if ( $foo ) {
				$res['session_data'] = $foo;
			}
			self::$session_hash = md5( $res['session_data'] );

			return !empty($res['session_data']) ? $res['session_data'] : '';
		}

		return '';
	}

	public static function write( $session_id, $data ) {
		if ( self::$destroyed ) {
			return false;
		}
		if ( self::$session_hash && md5( $data ) == self::$session_hash ) {
			return true;
		} // session data hasn't changed, dont re-write it
		if ( ! self::db_table_exists( 'session', true ) ) {
			return file_put_contents( _UCM_FILE_STORAGE_DIR . "temp/sess_$session_id", $data ) === false ? false : true;
		}
		/*if(function_exists('mb_detect_encoding') && mb_detect_encoding($data)!='ASCII'){
				$data = '!'.mb_detect_encoding($data).'!'.$data;
		}*/
		try {
			$user_id   = module_security::get_loggedin_id();
			$logged_in = module_security::is_logged_in();
			// are we creating or reading?
			$sql = "SELECT session_id FROM `" . _DB_PREFIX . "session` WHERE `session_id` = '" . db_escape( self::$session_id ) . "'";
			$res = query( $sql );
			if ( ( $res instanceOf mysqli_result && mysqli_num_rows( $res ) ) || ( is_resource( $res ) && mysql_num_rows( $res ) ) ) {
				// we have a session! woo!
				// update existing
				$data = base64_encode( $data );
				$sql  = "UPDATE `" . _DB_PREFIX . "session` SET ";
				$sql  .= " `last_access` = " . (int) time() . ", `session_data` = '" . db_escape( $data ) . "', `logged_in` = " . (int) $logged_in . ", ip_address = '" . db_escape( $_SERVER['REMOTE_ADDR'] ) . "' ";
				if ( $logged_in ) {
					// only write the user id if we're logged in
					// this keeps user id active for closed user sessions
					$sql .= ", `user_id` = " . (int) $user_id . " ";
				}
				$sql .= " WHERE `session_id` = '" . db_escape( $session_id ) . "'";
				query( $sql );
			} else {
				// create new! only difference is set set a created timestamo
				$sql = "INSERT INTO `" . _DB_PREFIX . "session` SET `created` = " . (int) time() . ",`last_access` = " . (int) time() . ", `session_data` = '" . db_escape( $data ) . "', `user_id` = " . (int) $user_id . ", `logged_in` = " . (int) $logged_in . ", ip_address = '" . db_escape( $_SERVER['REMOTE_ADDR'] ) . "', `session_id` = '" . db_escape( $session_id ) . "'";
				query( $sql );
			}
		} catch ( Exception $e ) {
			echo $e->getMessage();
		}

		return true;
	}

	public static function destroy( $session_id ) {
		self::$destroyed = true;
		if ( ! self::db_table_exists( 'session', true ) ) {
			$file = _UCM_FILE_STORAGE_DIR . "temp/sess_$session_id";
			if ( file_exists( $file ) ) {
				unlink( $file );
			}

			return false;
		}
		$sql = "DELETE FROM `" . _DB_PREFIX . "session` WHERE `session_id` = '" . db_escape( $session_id ) . "'";
		query( $sql );
	}

	public static function gc() {
		if ( ! self::db_table_exists( 'session', true ) ) {
			return false;
		}
		$life = get_cfg_var( "session.gc_maxlifetime" );
		if ( ! $life ) {
			$life = '1440';
		}
		$last_access = time() - $life;
		$sql         = "DELETE FROM `" . _DB_PREFIX . "session` WHERE `user_id` = 0 AND `last_access` < '" . (int) $last_access . "'";
		query( $sql );

		return true;
	}

	public static function close() {
		return true;
	}


	public function get_upgrade_sql() {
		$sql = '';

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>session` (
		`session_id` varchar(255) NOT NULL DEFAULT '',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`logged_in` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`ip_address` varchar(20) NOT NULL DEFAULT '',
		`session_data` TEXT NOT NULL DEFAULT '',
		`created` INT NOT NULL DEFAULT '0',
		`last_access` INT NOT NULL DEFAULT '0',
		PRIMARY KEY  (`session_id`),
		KEY `user_id` (`user_id`),
		KEY `logged_in` (`logged_in`),
		KEY `last_access` (`last_access`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		<?php
		return ob_get_clean();
	}


}