<?php


class module_cache extends module_base {

	private static $_use_memcache = false;
	private static $_memcache_instance = false;
	private static $_memcache_prefix = '';
	private static $_memcache_version = 1;
	private static $_memcache_timeout = 60;

	private static $cache_store = array();

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	private static $_cache_expiry = array();
	private static $_db_cache = array();

	public function init() {
		$this->module_name     = "cache";
		$this->module_position = 1;
		$this->version         = 2.232;
		// 2.232 - 2016-12-20 - speed improvements
		// 2.231 - 2016-12-18 - speed improvements
		// 2.230 - 2016-07-10 - big update to mysqli
		// 2.229 - 2014-07-28 - menu generation speed improvement
		// 2.228 - 2014-05-12 - cache bug fixing
		// 2.227 - 2013-10-02 - cache bug fixing
		// 2.226 - 2013-09-12 - hopefully a BIG new speed improvement - change advanced cache_enabled to 1
		// 2.225 - 2013-09-12 - debug_cache advanced option added
		// 2.224 - 2013-09-10 - dashboard speed fix
		// 2.223 - 2013-09-08 - cache size fix
		// 2.222 - 2013-09-06 - cache_objects configuration variable (not cache_object)
		// 2.221 - 2013-09-03 - cache improvements and bug fixing
		// 2.22 - 2013-08-31 - cache and speed improvements
		// 2.21 - 2013-08-30 - better memcache support
		// 2.2 - 2013-08-30 - starting work on memcache support
		// version bug fix? maybe?

		if ( module_security::get_loggedin_id() ) {
			// use memcache if it exists
			if ( class_exists( 'Memcache', false ) && module_config::c( 'cache_enabled', 1 ) && module_config::c( 'memcache_active', 0 ) ) {
				self::$_memcache_instance = new Memcache;
				if ( self::$_memcache_instance->connect( module_config::c( 'memcache_address', '127.0.0.1' ), module_config::c( 'memcache_port', '11211' ) ) ) { //module_config::c('memcache_address','localhost'), )){
					self::$_memcache_prefix = md5( session_id() . _UCM_SECRET . _DB_PREFIX . _DB_NAME . _DB_USER . _DB_PASS );
					// what version of information are we requesting (inremented each time something is deleted, inserted or updated)
					// bad, but cannot think of any other easy quick way to invalidate caches upon updates
					self::$_memcache_version = self::$_memcache_instance->get( self::$_memcache_prefix . 'version' );
					if ( ! self::$_memcache_version ) {
						self::$_memcache_version = 1;
					}
					self::$_use_memcache = true;
				}
			} else {
				if ( module_config::c( 'cache_enabled', 1 ) && $this->db_table_exists( 'cache_store' ) ) {
					$sql         = "SELECT * FROM `" . _DB_PREFIX . "cache_store` WHERE expire_time > " . time();
					$sql         .= " AND create_user_id = " . (int) module_security::get_loggedin_id();
					$cache_items = qa( $sql );
					if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
						module_debug::log( array(
							'title' => 'Cached items',
							'data'  => count( $cache_items ),
						) );
					}
					foreach ( $cache_items as $res ) {
						if ( ! isset( self::$_db_cache[ $res['cache_group'] ] ) ) {
							self::$_db_cache[ $res['cache_group'] ] = array();
						}
						self::$_db_cache[ $res['cache_group'] ][ $res['cache_key'] ] = $res['cache_data'];
					}
					register_shutdown_function( 'module_cache::shutdown_write_cached_data' );
				}
			}

			// change to low number by default
			if ( module_config::c( 'cache_objects', 120 ) == 3600 ) {
				module_config::save_config( 'cache_objects', 120 );
			}

			if ( module_config::c( 'cache_enabled', 1 ) && $this->db_table_exists( 'cache' ) ) {
				$sql          = "SELECT * FROM `" . _DB_PREFIX . "cache` ";
				$cache_expiry = qa( $sql );
				foreach ( $cache_expiry as $r ) {
					self::$_cache_expiry[ $r['cache_group'] ] = $r['expire_time'];
				}
				if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
					module_debug::log( array(
						'title' => 'Cached expiry items',
						'data'  => count( $cache_expiry ),
					) );
				}
			}
		}


	}

	public static function shutdown_write_cached_data() {
		if ( self::db_table_exists( 'cache_store' ) && module_security::get_loggedin_id() ) {
			$sql = "DELETE FROM `" . _DB_PREFIX . "cache_store` WHERE expire_time < " . (int) time();
			query( $sql );
			foreach ( self::$_db_cache as $cache_group => $data ) {
				foreach ( $data as $cache_key => $cache_data ) {
					if ( is_array( $cache_data ) ) {
						// this means it was modified during this execution run, write it to db.
						$sql = "UPDATE `" . _DB_PREFIX . "cache_store` SET cache_data = '" . db_escape( serialize( $cache_data ) ) . "', `create_user_id` = " . module_security::get_loggedin_id() . ", expire_time = " . (int) $cache_data['e'] . " WHERE `cache_group` = '" . db_escape( $cache_group ) . "' AND `cache_key` = '" . db_escape( $cache_key ) . "'";
						$res = query( $sql );
						if ( $res === false || ! mysqli_affected_rows( module_db::$dbcnx ) ) {
							$sql = "INSERT INTO `" . _DB_PREFIX . "cache_store` SET `cache_group` = '" . db_escape( $cache_group ) . "', `cache_key` = '" . db_escape( $cache_key ) . "', cache_data = '" . db_escape( serialize( $cache_data ) ) . "', `create_user_id` = " . module_security::get_loggedin_id() . ", expire_time = " . (int) $cache_data['e'] . " ON DUPLICATE KEY UPDATE expire_time = " . (int) $cache_data['e'];
							query( $sql );
						}
					}
				}
			}

		}
	}

	public static function get( $group, $cache_key ) {
		if ( ! module_config::c( 'cache_enabled', 1 ) ) {
			return false;
		}
		$cache_key      = module_security::get_loggedin_id() . '/' . $cache_key;
		$full_cache_key = $group . '||' . $cache_key;

		// old code, remove one day:
		if ( isset( $_SESSION['_cache_time_save'] ) ) {
			unset( $_SESSION['_cache_time_save'] );
		}
		if ( isset( $_SESSION['_c'] ) ) {
			unset( $_SESSION['_c'] ); // not saving in session any more
		}
		if ( self::$_use_memcache ) {
			$data = self::$_memcache_instance->get( self::$_memcache_prefix . $full_cache_key );
			if ( $data ) {
				if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
					module_debug::log( array(
						'title' => 'Return memcache',
						'data'  => "For: $full_cache_key ",
					) );
				}
				// check this data hasn't expired.
				if ( isset( $data['create'] ) && isset( $data['d'] ) ) {
					if ( isset( self::$_cache_expiry[ $group ] ) && $data['create'] <= self::$_cache_expiry[ $group ] ) {
						// this item has expired according to db rules.
						if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
							module_debug::log( array(
								'title' => ' cache expired, not returning',
								'data'  => "For: $full_cache_key ",
							) );
						}

						return false;
					}

					return $data['d'];
				} else {
					return $data;
				}
			}
		} else {
			$limit = time();

			if ( ! isset( self::$_db_cache[ $group ] ) || ! isset( self::$_db_cache[ $group ][ $cache_key ] ) ) {
				return false;
			}
			$data = is_array( self::$_db_cache[ $group ][ $cache_key ] ) ? self::$_db_cache[ $group ][ $cache_key ] : unserialize( self::$_db_cache[ $group ][ $cache_key ] );
			if ( $data ) {
				if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
					module_debug::log( array(
						'title' => 'Return Cache',
						'data'  => "For: $full_cache_key ",
					) );
				}
				if ( isset( $data['create'] ) && isset( $data['d'] ) ) {
					if ( isset( self::$_cache_expiry[ $group ] ) && $data['create'] <= self::$_cache_expiry[ $group ] ) {
						// this item has expired according to db rules.
						if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
							module_debug::log( array(
								'title' => ' cache expired, not returning',
								'data'  => "For: $full_cache_key ",
							) );
						}

						return false;
					}

					return $data['d'];
				} else {
					return $data;
				}
			}
		}

		return false;
	}

	public static function put( $group, $cache_key, $data, $seconds = false ) {
		if ( ! module_config::c( 'cache_enabled', 1 ) ) {
			return false;
		}
		if ( ! $seconds ) {
			$seconds = module_config::c( 'cache_objects', 60 );
		}
		$cache_key      = module_security::get_loggedin_id() . '/' . $cache_key;
		$full_cache_key = $group . '||' . $cache_key;
		//if(!isset($_SESSION['_cache_keys']))$_SESSION['_cache_keys'] = array();
		//if(!isset($_SESSION['_cache_keys'][$group]))$_SESSION['_cache_keys'][$group] = array();
		//$_SESSION['_cache_keys'][$group][$cache_key] = true;
		//self::time_save($group.'||'.$cache_key,$data,$timeout);
		$data = array(
			'create' => time(),
			'd'      => $data,
			'e'      => time() + $seconds,
		);
		if ( self::$_use_memcache ) {
			// version this as well

			if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
				module_debug::log( array(
					'title' => 'MemCache Time Save',
					'data'  => "For: $full_cache_key = Storing for $seconds",
				) );
			}
			if ( ! self::$_memcache_instance->replace( self::$_memcache_prefix . $full_cache_key, $data, 0, $seconds ) ) {
				self::$_memcache_instance->set( self::$_memcache_prefix . $full_cache_key, $data, 0, $seconds );
			}
		} else {
			if ( module_security::is_logged_in() ) {

				self::$_db_cache[ $group ][ $cache_key ] = $data;
				//$data = serialize($data);

				if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
					module_debug::log( array(
						'title' => 'Cache Time Save',
						'data'  => "For: $group - $cache_key = Storing for $seconds",// (".strlen($data).")",
					) );
				}
				//if(strlen($data)>3000)return; // can have issues with sessions stored in db?
				/*if(self::db_table_exists('cache_store')){
						$sql = "REPLACE INTO `"._DB_PREFIX."cache_store` SET `cache_group` = '".db_escape($group)."', `cache_key` = '".db_escape($cache_key)."', cache_data = '".db_escape($data)."', `create_user_id` = ".module_security::get_loggedin_id().", expire_time = ".((int)time()+$seconds);
						query($sql);
				}*/
			}
			// just save in session for amount of time.
			/*if(!isset($_SESSION['_c'])){
					$_SESSION['_c'] = array();
			}
			$_SESSION['_c'][$full_cache_key] = array(
					'e' => time()+$seconds,
					'd'=>$data,
			);*/
		}
	}

	public static function clear( $group, $cache_key = false ) {
		if ( ! module_config::c( 'cache_enabled', 1 ) ) {
			return false;
		}

		if ( $cache_key ) {
			// cache keys should be unique per user, so not worried about cache poisoning just yet
			$cache_key      = module_security::get_loggedin_id() . '/' . $cache_key;
			$full_cache_key = $group . '||' . $cache_key;

			if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
				module_debug::log( array(
					'title' => 'Cache Time Clear',
					'data'  => "For: $full_cache_key ",
				) );
			}
			if ( self::$_use_memcache ) {
				// version this as well
				self::$_memcache_instance->delete( self::$_memcache_prefix . $full_cache_key );
			} else {
				if ( self::db_table_exists( 'cache_store' ) ) {
					$sql = "DELETE FROM `" . _DB_PREFIX . "cache_store` WHERE `cache_group` = '" . db_escape( $group ) . "' AND `cache_key` = '" . db_escape( $cache_key ) . "'";
					query( $sql );
				}
				if ( isset( self::$_db_cache[ $group ] ) && isset( self::$_db_cache[ $group ][ $cache_key ] ) ) {
					unset( self::$_db_cache[ $group ][ $cache_key ] );// so we don't write this to db on page finish.
				}
			}
			//if(isset($_SESSION['_cache_keys'][$group][$cache_key])){
			//unset($_SESSION['_cache_keys'][$group][$cache_key]);
			//}
		} else { // if(isset($_SESSION['_cache_keys']) && isset($_SESSION['_cache_keys'][$group])){
			// we have to clear this entire group, not just the individual key
			// add in an expiry time for this cache group
			self::$_cache_expiry[ $group ] = time();
			// for sessions and memcache.
			if ( self::$_use_memcache ) {
				// no way to delete all entries from a memcache based on "group", so we use the 'cache' expiry table above to invalidate entries across the system.
				$sql = "REPLACE INTO `" . _DB_PREFIX . "cache` SET `cache_group` = '" . db_escape( $group ) . "', `expire_time` = " . time();
				query( $sql );
			} else {
				// session mode, remove all cache elements from this item.
				if ( self::db_table_exists( 'cache_store' ) ) {
					$sql = "DELETE FROM `" . _DB_PREFIX . "cache_store` WHERE `cache_group` = '" . db_escape( $group ) . "'";// AND `create_user_id` = ".module_security::get_loggedin_id();
					query( $sql );
				}
				if ( isset( self::$_db_cache[ $group ] ) ) {
					unset( self::$_db_cache[ $group ] );// so we don't write this to db on page finish.
				}
			}
			//foreach($_SESSION['_cache_keys'][$group] as $key => $val){
			//self::time_clear($group.'||'.$key);
			//unset($_SESSION['_cache_keys'][$group][$key]);
			//}
		}
		if ( $group != 'global' ) {
			self::clear( 'global' );
		} // global for menu stuff as well.
	}


	public static function clear_cache( $cache_key = false ) {
		if ( ! module_config::c( 'cache_enabled', 1 ) ) {
			return;
		}
		if ( _DEBUG_MODE && module_config::c( 'cache_debug', 0 ) ) {
			module_debug::log( array(
				'title' => 'Clear Cache',
				'data'  => "Key: $cache_key",
			) );
		}
		if ( $cache_key ) {
			if ( isset( self::$cache_store[ $cache_key ] ) ) {
				unset( self::$cache_store[ $cache_key ] );
			}
		} else {
			// clear all
			self::$cache_store = array();
		}
	}

	/*
	public static function get_cached_item($cache_key,$cache_item='') {
        // todo - based on curretn session id so we can clear cache by logging out and back in again
        if(!module_config::c('cache_enabled',1))return false;
        if(isset(self::$cache_store[$cache_key])){
            if(_DEBUG_MODE && module_config::c('cache_debug',0)){
                module_debug::log(array(
                    'title' => 'Return cache',
                    'data' => "For: $cache_key = ".substr($cache_item,0,50).'...',
                 ));
            }
			return self::$cache_store[$cache_key];
		}
	}
	public static function save_cached_item($cache_key,$data) {
        if(module_config::c('cache_enabled',1)){
            self::$cache_store[$cache_key] = $data;
        }
	}

    // basic session timeout storing:
    public static function time_get($cache_key){

    }
    public static function time_save($cache_key,$data,$seconds=30){


    }
    public static function time_clear($cache_key){

    }

	public static function get_perm_cache($cache_key,$time_limit=3600) { // 1 hour
        $cache_file = _UCM_FOLDER . "temp/cache_".basename($cache_key);
        if(is_file($cache_file) && filemtime($cache_file) > time()-$time_limit){
            return unserialize(file_get_contents($cache_file));
        }
        return false;
	}
	public static function save_perm_cache($cache_key,$data) {
        $cache_file = _UCM_FOLDER . "temp/cache_".basename($cache_key);
        file_put_contents($cache_file,serialize($data)); // fixed
	}
	*/

	public function get_upgrade_sql() {
		$sql = '';
		if ( ! self::db_table_exists( 'cache_store' ) ) {
			$sql .= 'CREATE TABLE  `' . _DB_PREFIX . 'cache_store` (
            `cache_group` VARCHAR( 25 ) NOT NULL ,
            `cache_key` VARCHAR( 255 ) NOT NULL ,
            `cache_data` MEDIUMBLOB NULL ,
            `expire_time` INT(11) NOT NULL DEFAULT \'0\',
            `create_user_id` INT(11) NOT NULL DEFAULT \'0\',
            KEY (  `expire_time` ),
            KEY (  `create_user_id` ),
            PRIMARY KEY (  `cache_group`, `cache_key` )
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE  `<?php echo _DB_PREFIX; ?>cache` (
		`cache_group` VARCHAR( 255 ) NOT NULL ,
		`expire_time` INT(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (  `cache_group` )
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE  `<?php echo _DB_PREFIX; ?>cache_store` (
		`cache_group` VARCHAR( 255 ) NOT NULL ,
		`cache_key` VARCHAR( 255 ) NOT NULL ,
		`cache_data` MEDIUMBLOB NULL ,
		`expire_time` INT(11) NOT NULL DEFAULT '0',
		`create_user_id` INT(11) NOT NULL DEFAULT '0',
		KEY (  `expire_time` ),
		KEY (  `create_user_id` ),
		PRIMARY KEY (  `cache_group`, `cache_key` )
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		<?php
		return ob_get_clean();
	}

}
