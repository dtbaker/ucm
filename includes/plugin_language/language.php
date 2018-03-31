<?php

$labels = array();
global $labels;

class module_language extends module_base {

	public $version = 2.181;
	// 2.14 default system language in advanced settings.
	// 2.15 - added a default german translation (needs work!)
	// 2.16 - French translation added - thanks Amar Bou!
	// 2.161 - 2013-04-04 - fix for translation in User Roles
	// 2.162 - 2013-04-12 - language preference fix for users with little permissions
	// 2.163 - 2013-05-08 - spanish translation file - thanks pibe!
	// 2.164 - 2014-07-05 - database translations (Settings > i8n)
	// 2.165 - 2014-07-07 - database translations (Settings > Language)
	// 2.166 - 2014-07-07 - better database translations (Settings > Language)
	// 2.167 - 2014-07-14 - better database translations (Settings > Language)
	// 2.168 - 2014-07-15 - reset button added to Settings > Language
	// 2.169 - 2014-07-15 - Settings > Language export CSV includes old translations
	// 2.17 - 2014-07-23 - Settings > Language duplicate word removal
	// 2.171 - 2014-07-24 - Settings > Language case sensitive word fix
	// 2.172 - 2014-07-24 - Settings > Language case sensitive word fix
	// 2.173 - 2014-10-08 - Settings > Language speed improvements and better duplication checking
	// 2.174 - 2014-12-05 - Settings > Language speed improvements and better duplication checking
	// 2.175 - 2015-01-07 - Settings > Language CSV import bug fix
	// 2.176 - 2015-01-19 - Big speed improvement for language translations
	// 2.177 - 2015-01-20 - more speed improvements
	// 2.178 - 2016-03-16 - language table layout fix
	// 2.179 - 2016-07-10 - big update to mysqli
	// 2.181 - 2017-05-02 - php error fix

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {

		$this->module_name = "language";
		$language_code     = basename( module_config::c( 'default_language' ) );

		if ( module_security::is_logged_in() ) {
			$user = module_user::get_user( module_security::get_loggedin_id(), false );
			if ( $user && $user['user_id'] && isset( $user['language'] ) && $user['language'] ) {
				$language_code = basename( $user['language'] );
			}
		}
		// language code, like en, gb, etc..
		self::set_ui_language( $language_code );

	}

	public function pre_menu() {

		if ( module_config::can_i( 'view', 'Settings' ) && module_language::can_i( 'edit', 'language' ) && self::is_language_db_enabled() ) {
			$this->links[] = array(
				"name"                => "Language",
				"p"                   => "language_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
				'order'               => 9000,
				'args'                => array(
					'language_id' => false,
				),
			);
		}
	}

	public function process() {
		if ( 'language_reset' == $_REQUEST['_process'] && $_REQUEST['really'] == 'yes' ) {
			if ( ! module_form::check_secure_key() ) {
				return;
			}

			// delete all language words and translations
			$sql = "DELETE FROM `" . _DB_PREFIX . "language_word` WHERE 1";
			query( $sql );
			$sql = "DELETE FROM `" . _DB_PREFIX . "language_translation` WHERE 1";
			query( $sql );
			set_message( 'Translation reset successfully' );
			redirect_browser( $_SERVER['REQUEST_URI'] );
		}
		if ( 'remove_duplicates' == $_REQUEST['_process'] && isset( $_REQUEST['duplicate_ids'] ) ) {
			if ( ! module_form::check_secure_key() ) {
				return;
			}

			$duplicate_ids = json_decode( $_REQUEST['duplicate_ids'], true );
			foreach ( $duplicate_ids as $duplicate_id ) {
				$sql = "DELETE FROM `" . _DB_PREFIX . "language_word` WHERE language_word_id = '" . (int) $duplicate_id . "' LIMIT 1";
				query( $sql );
			}
			set_message( 'Translation errors removed successfully' );
			redirect_browser( $_SERVER['REQUEST_URI'] );

		} else if ( 'language_duplicate_remove' == $_REQUEST['_process'] && $_REQUEST['really'] == 'yep' ) {
			if ( ! module_form::check_secure_key() ) {
				return;
			}

			// delete all language words and translations
			$sql = "SELECT `word`, COUNT(*) as cc FROM `" . _DB_PREFIX . "language_word`  GROUP BY `word` HAVING cc > 1";
			$res = qa( $sql );
			foreach ( $res as $r ) {
				if ( $r['word'] && $r['cc'] > 1 ) {
					// remove duplicates.
					$sql        = "SELECT * FROM `" . _DB_PREFIX . "language_word` WHERE `word` = '" . db_escape( $r['word'] ) . "' ";
					$duplicates = qa( $sql );
					// doing this due to incorrect collate in earlier version of UCM
					$words_casesensitive = array();
					foreach ( $duplicates as $duplicate ) {
						$words_casesensitive[ $duplicate['word'] ][ $duplicate['language_word_id'] ] = $duplicate['language_word_id'];
					}
					//print_r($words_casesensitive);exit;
					foreach ( $words_casesensitive as $word => $duplicate_ids ) {
						if ( count( $duplicate_ids ) > 1 ) {
							$first = false;
							foreach ( $duplicate_ids as $language_word_id ) {
								if ( $first === false ) {
									$first = $language_word_id;
								} else if ( $first ) {
									// remove this one and replace any translations with the first one.
									$sql = "DELETE FROM `" . _DB_PREFIX . "language_word` WHERE language_word_id = '" . (int) $language_word_id . "' LIMIT 1";
									query( $sql );

									$sql = "UPDATE `" . _DB_PREFIX . "language_translation` SET language_word_id = '" . (int) $first . "' WHERE language_word_id = '" . (int) $language_word_id . "'";
									query( $sql );

								}
							}
						}
					}

				}
			}
			$sql = "DELETE FROM `" . _DB_PREFIX . "language_word` WHERE `word` LIKE 'SQL Error%'";
			query( $sql );

			// merge languages - error if case of language changes, keeps creating new language entries - eg FR fr
			$sql = "SELECT `language_id`, `language_code`, COUNT(*) as cc FROM `" . _DB_PREFIX . "language`  GROUP BY `language_code`";
			$res = query( $sql );
			while ( $row = mysqli_fetch_assoc( $res ) ) {
				if ( $row['cc'] > 1 ) {
					// merge these!
					$sql      = "SELECT language_id FROM `" . _DB_PREFIX . "language` WHERE `language_code` = '" . db_escape( $row['language_code'] ) . "' AND language_id != " . (int) $row['language_id'] . "";
					$to_merge = query( $sql );
					while ( $merge = mysqli_fetch_assoc( $to_merge ) ) {
						$sql = "UPDATE `" . _DB_PREFIX . "language_translation` SET language_id = " . (int) $row['language_id'] . " WHERE language_id = " . (int) $merge['language_id'] . "";
						query( $sql );
						// remove any that didn't update correctly (duplicate entries)
						$sql = "DELETE FROM `" . _DB_PREFIX . "language_translation` WHERE language_id = " . (int) $merge['language_id'] . "";
						query( $sql );
						$sql = "DELETE FROM `" . _DB_PREFIX . "language` WHERE language_id = " . (int) $merge['language_id'] . " LIMIT 1";
						query( $sql );
					}
				}
			}


			set_message( 'Translation duplicates removed successfully' );
			redirect_browser( $_SERVER['REQUEST_URI'] );
		}
		if ( 'save_language_translation' == $_REQUEST['_process'] ) {

			if ( ! module_form::check_secure_key() ) {
				return;
			}
			if ( ! module_config::can_i( 'view', 'Settings' ) ) {
				redirect_browser( _BASE_HREF );
			}
			if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
				redirect_browser( _BASE_HREF );
			}
			if ( ! module_language::can_i( 'edit', 'Language' ) ) {
				redirect_browser( _BASE_HREF );
			}
			$language_id = (int) $_REQUEST['language_id'];
			$language    = module_language::get_language( $language_id );
			if ( ! $language_id || ! $language || $language['language_id'] != $language_id ) {
				$language_id = false;
				$language    = array();
			}
			$language_id = update_insert( 'language_id', $language_id, 'language', $_POST );
			if ( isset( $_POST['translation'] ) && is_array( $_POST['translation'] ) ) {
				// save these values to the translation table for this particular langauge.
				foreach ( $_POST['translation'] as $language_word_id => $translation ) {
					if ( _DEMO_MODE ) {
						if ( ! isset( $_SESSION['temp_translation'] ) ) {
							$_SESSION['temp_translation'] = array();
						}
						if ( ! isset( $_SESSION['temp_translation'][ $language_id ] ) ) {
							$_SESSION['temp_translation'][ $language_id ] = array();
						}
						$_SESSION['temp_translation'][ $language_id ][ $language_word_id ] = $translation;
					} else {
						if ( strlen( $translation ) ) {
							$sql = "REPLACE INTO `" . _DB_PREFIX . "language_translation` SET `language_id` = " . (int) $language_id . ", ";
							$sql .= "`language_word_id` = " . (int) $language_word_id . ", `translation` = '" . db_escape( $translation ) . "'";
							query( $sql );
						} else {
							$sql = "DELETE FROM `" . _DB_PREFIX . "language_translation` WHERE `language_id` = " . (int) $language_id . " AND ";
							$sql .= "`language_word_id` = " . (int) $language_word_id . "";
							query( $sql );
						}
					}
				}
			}
			if ( isset( $_REQUEST['check_duplicates'] ) ) {
				// redirect to duplicate check page.
				redirect_browser( $_SERVER['REQUEST_URI'] . '&check_duplicates' );
			} else {
				set_message( 'Translation saved successfully' );
				redirect_browser( str_replace( 'language_id', 'done', $_SERVER['REQUEST_URI'] ) );
			}
		}
	}

	public static function ignore_word( $word ) {
		return preg_match( '#\'menu_label[^\']*\'>\d#', $word ) || preg_match( '#\'badge[^\']*\'>\d#', $word ) || preg_match( '#\"badge[^"]*">\d#', $word ) || preg_match( '#\'label label[^\']*\'>\d#', $word ) || preg_match( '#"label label[^"]*">\d#', $word ) || ( preg_match( '#Really delete.*:#', $word ) && ! strpos( $word, '%s' ) ) || strpos( $word, "Ticket: " ) === 0 || strpos( $word, "Ticket #" ) === 0 || strpos( $word, "SQL Error: " ) === 0;
	}

	// language code, like en, gb, etc..
	public static function set_ui_language( $language_code ) {

		$language_code = basename( strtolower( $language_code ) );
		if ( self::is_language_db_enabled() ) {
			$language_db = get_single( 'language', 'language_code', $language_code );
			if ( ! $language_db || strtolower( $language_db['language_code'] ) != strtolower( $language_code ) ) {
				$language_id = update_insert( 'language_id', false, 'language', array(
					'language_code' => $language_code,
					'language_name' => $language_code,
				) );
			} else {
				$language_id = $language_db['language_id'];
			}
		}

		global $labels;

		//        if(@include('custom/'.$language_code.'.php')){
		//define('_UCM_LANG',$language);
		//        }else if(@include('labels/'.$language_code.'.php')){
		//define('_UCM_LANG',$language);
		//        }

		if ( ! is_array( $labels ) ) {
			$labels = array();
		}

		if ( self::is_language_db_enabled() && $language_id ) {
			// hack to move the old labels into the database.
			//if ( count( $labels ) ) {
			$sql      = "SELECT `language_word_id`,`word` FROM `" . _DB_PREFIX . "language_word` ";
			$res      = query( $sql );
			$db_words = array();
			while ( $word = mysqli_fetch_assoc( $res ) ) {
				$db_words[ $word['language_word_id'] ] = $word['word'];
				if ( ! isset( $labels[ $word['word'] ] ) ) {
					$labels[ $word['word'] ] = $word['word'];
				}
			}
			foreach ( $labels as $label => $translation ) {
				if ( ! in_array( $label, $db_words ) ) {
					self::missing_word( $label, '', $translation );
				}
			}
			//}

			$sql = "SELECT lt.`language_word_id`, lt.`translation` FROM `" . _DB_PREFIX . "language_translation` lt WHERE lt.language_id = " . (int) $language_id;
			$res = query( $sql );
			while ( $row = mysqli_fetch_assoc( $res ) ) {
				if ( ! isset( $db_words[ $row['language_word_id'] ] ) ) {
					continue;
				}
				$row['word'] = $db_words[ $row['language_word_id'] ];
				if ( _DEMO_MODE && isset( $_SESSION['temp_translation'][ $language_id ][ $row['language_word_id'] ] ) ) {
					$labels[ $row['word'] ] = $_SESSION['temp_translation'][ $language_id ][ $row['language_word_id'] ];
				} else if ( strlen( $row['word'] ) > 0 ) {
					if ( isset( $labels[ $row['word'] ] ) && ( ! $row['translation'] || $row['translation'] == $row['word'] ) ) {
						// we already have a trasnaltion from the raw php file.
						// do we use this? or a database translation?

					} else {
						$labels[ $row['word'] ] = strlen( $row['translation'] ) && $row['translation'] != $row['word'] ? $row['translation'] : '';
					}
				}
			}

		}
	}

	public static function get_language( $language_id ) {
		return get_single( 'language', 'language_id', $language_id );
	}

	public static function get_translations( $language_id ) {
		$sql = "SELECT  lw.language_word_id, lw.`word`, lt.`translation` ";
		$sql .= " FROM ";
		$sql .= " `" . _DB_PREFIX . "language_word` lw ";
		$sql .= " LEFT JOIN ";
		$sql .= " `" . _DB_PREFIX . "language_translation` lt ON lw.language_word_id = lt.language_word_id  ";
		$sql .= " AND ( lt.language_id IS NULL OR lt.language_id = " . (int) $language_id . " )";
		$sql .= " ORDER BY lw.word ASC ";

		return query( $sql );
	}

	public static function missing_word( $text, $url = '', $translation = false ) {
		if ( ! strlen( trim( $text ) ) || self::ignore_word( $text ) ) {
			return;
		}
		// word not found in the current translation $labels
		// add it to the database.
		global $labels;
		if ( ! isset( $labels[ $text ] ) ) {
			$labels[ $text ] = ( $translation !== false ? $translation : $text );
		}
		if ( self::is_language_db_enabled() ) {
			// check if word doesn't exist.
			$sql    = "SELECT * FROM `" . _DB_PREFIX . "language_word` WHERE `word` = '" . db_escape( $text ) . "'";
			$res    = qa( $sql );
			$exists = false;
			if ( $res ) {
				foreach ( $res as $r ) {
					if ( $r['word'] == $text ) {
						$exists = true;
					}
				}
			}
			if ( ! $exists ) {
				update_insert( 'language_word_id', false, 'language_word', array(
					'word' => $text,
					'url'  => $url,
				) );
			}
		}
	}

	public static function handle_import( $data, $group = false, $extra_options ) {

		$imported = 0;
		if ( ! _DEMO_MODE && isset( $extra_options['language_id'] ) && $extra_options['language_id'] > 0 && count( $data ) > 1 ) {
			foreach ( $data as $row ) {
				if ( isset( $row['word'] ) && strlen( $row['word'] ) && isset( $row['translation'] ) && strlen( $row['translation'] ) ) {
					// ready to import this word!
					$sql              = "SELECT  lw.language_word_id, lw.`word` ";
					$sql              .= " FROM ";
					$sql              .= " `" . _DB_PREFIX . "language_word` lw ";
					$sql              .= " WHERE lw.`word` = '" . db_escape( $row['word'] ) . "'";
					$res              = qa( $sql );
					$language_word_id = false;
					if ( is_array( $res ) ) {
						foreach ( $res as $r ) {
							if ( $r['word'] == $row['word'] ) {
								$language_word_id = $r['language_word_id'];
							}
						}
					}
					/*if($row['word'] == 'Dashboard'){
						echo 'dash';
						echo $sql;
						print_r($res);
						exit;
					}*/
					if ( ! $language_word_id ) {
						// create a new one, unless our ignore option is setup
						if ( isset( $extra_options['new_words'] ) && $extra_options['new_words'] == 'ignore' ) {
							continue; // skip this word
						}
						$language_word_id = update_insert( 'language_word_id', false, 'language_word', array( 'word' => $row['word'] ) );
					}
					$sql = "REPLACE INTO `" . _DB_PREFIX . "language_translation` SET `language_id` = " . (int) $extra_options['language_id'] . ", ";
					$sql .= "`language_word_id` = " . (int) $language_word_id . ", `translation` = '" . db_escape( $row['translation'] ) . "'";
					query( $sql );

					// add this translation to the file.
					$imported ++;
				}
			}
		} else if ( _DEMO_MODE ) {
			set_error( 'Import disabled in demo mode' );
		}

		return $imported;

	}

	private static $is_db_enabled = false;

	public static function is_language_db_enabled() {
		if ( self::$is_db_enabled === false ) {
			if ( self::db_table_exists( 'language' ) && module_config::c( 'language_database_enabled', 1 ) ) {
				self::$is_db_enabled = 1;
			} else {
				self::$is_db_enabled = 0;
			}
		}

		return self::$is_db_enabled;
	}

	public static function get_languages_attributes() {
		$all            = array();
		$language_files = glob( _UCM_FOLDER . 'includes/plugin_language/custom/*.php' );
		if ( is_array( $language_files ) ) {
			foreach ( $language_files as $language ) {
				$language = strtolower( str_replace( '.php', '', basename( $language ) ) );
				if ( $language[0] == '_' ) {
					continue;
				}
				$all[ $language ] = array( 'language_name' => $language, 'language_code' => $language );
			}
		}
		$language_files = glob( _UCM_FOLDER . 'includes/plugin_language/labels/*.php' );
		if ( is_array( $language_files ) ) {
			foreach ( $language_files as $language ) {
				$language = strtolower( str_replace( '.php', '', basename( $language ) ) );
				if ( $language[0] == '_' ) {
					continue;
				}
				$all[ $language ] = array( 'language_name' => $language, 'language_code' => $language );
			}
		}
		if ( self::is_language_db_enabled() ) {
			foreach ( $all as $language_code => $language ) {
				// does this language code exist in the database?
				$language_db = get_single( 'language', 'language_code', $language_code );
				if ( ! $language_db || $language_db['language_code'] != $language_code ) {
					update_insert( 'language_id', false, 'language', array(
						'language_code' => $language['language_code'],
						'language_name' => $language['language_name'],
					) );
				}
			}
			// now we get any language attributes from the database and overwrite the old file based ones with those.
			foreach ( get_multiple( 'language', false, 'language_id', 'exact', 'language_code' ) as $language ) {
				if ( isset( $all[ strtolower( $language['language_code'] ) ] ) ) {
					// this language exists in the old file based method.
					$all[ strtolower( $language['language_code'] ) ] = $language;
				} else {
					// this is a language that only exists in the new database translation method.
					$all[ strtolower( $language['language_code'] ) ] = $language;
				}
				// todo - well, not sure about the above. maybe we do some update here and remove the old files ??? move everything to the database or something?? meh..
			}
		}

		return $all;
	}

	private static $label_replace = array();

	public static function l( $text ) {
		// read in from the global label array
		//return 'L';
		global $labels;
		$argv = func_get_args();
		// see if the first one is a lang label
		if ( isset( $labels[ $text ] ) && strlen( trim( $labels[ $text ] ) ) ) {
			$argv[0] = $labels[ $text ];
		} else if ( ! isset( $labels[ $text ] ) && ! isset( self::$label_replace[ $text ] ) ) {
			self::missing_word( $text, $_SERVER['REQUEST_URI'] );
		}
		if ( count( $argv ) == 1 ) {
			self::$label_replace[ $argv[0] ] = true;

			return $argv[0];
		}
		//$argv[0] = 'xxx';
		$result = call_user_func_array( 'sprintf', $argv );
		//if(count($argv)>1){
		// cache result so we can check that we don't try to double translate something
		// this happens when we do stuff like: print_heading(_l('Import Data: %s',$import_options['name']));
		// end up with 'Import Data: %s' and 'Import Data: Customers' in the language database.
		self::$label_replace[ $result ] = true;
		//}


		/*if(stripos($text,'lead')!==false){
				echo "<br>Translating $text into $result <br>";
				print_r(self::$label_replace);
		}*/

		return $result;
	}


	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE `<?php echo _DB_PREFIX; ?>language` (
		`language_id` int(11) NOT NULL AUTO_INCREMENT,
		`language_code` varchar(2) NOT NULL,
		`language_name` varchar(20) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`language_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>language_translation` (
		`language_id` int(11) NOT NULL,
		`language_word_id` int(11) NOT NULL,
		`translation` text NOT NULL,
		PRIMARY KEY (`language_id`,`language_word_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>language_word` (
		`language_word_id` int(11) NOT NULL AUTO_INCREMENT,
		`word` text NOT NULL,
		`url` varchar(255) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`language_word_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		<?php
		return ob_get_clean();
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text ) {
		$argv = func_get_args();
		print call_user_func_array( '_l', $argv );;
	}
}
if ( ! function_exists( '_l' ) ) {
	function _l( $text ) {
		$argv = func_get_args();

		return call_user_func_array( 'module_language::l', $argv );
	}
}