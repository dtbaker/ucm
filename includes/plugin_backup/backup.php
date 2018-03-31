<?php

define( '_BACKUP_BASE_DIR', ( defined( '_UCM_FILE_STORAGE_DIR' ) ? _UCM_FILE_STORAGE_DIR : '' ) . 'includes/plugin_backup/backups/' );

class module_backup extends module_base {

	public $links;

	public $version = 2.117;
	// 2.117 - 2017-05-02 - backup fix
	// 2.116 - 2017-05-02 - backup fix
	// 2.115 - 2016-07-10 - big update to mysqli
	// 2.114 - 2015-07-29 - bug fix
	// 2.113 - 2015-03-17 - backup_post_delay
	// 2.112 - 2014-11-26 - typo fix
	// 2.111 - 2014-08-09 - backup javascript fix
	// 2.11 - 2014-08-08 - backup feature
	// 2.1 - 2014-08-06 - initial release


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->links           = array();
		$this->module_name     = "backup";
		$this->module_position = 30;

		module_config::register_js( 'backup', 'backup.js' );
		module_config::register_css( 'backup', 'backup.css' );
	}

	public function pre_menu() {

		if ( $this->is_installed() ) {
			if ( self::can_i( 'view', 'Backups' ) && module_config::can_i( 'view', 'Settings' ) ) {
				$this->links[] = array(
					"name"                => "Backups",
					"p"                   => "backup_settings",
					"args"                => array(
						'backup_id' => false,
					),
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}
		}
	}

	public static function link_generate( $backup_id = false, $options = array(), $link_options = array() ) {

		$key = 'backup_id';
		if ( $backup_id === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}
		$bubble_to_module = false;
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'backup';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'backup_settings';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['backup_id'] = $backup_id;
		$options['module']                 = 'backup';
		// what text should we display in this link?

		if ( isset( $options['data'] ) && $options['data'] ) {
			//$options['data'] = $options['data'];
		} else {
			$data            = self::get_backup( $backup_id );
			$options['data'] = $data;
		}
		$options['text'] = isset( $options['data']['question'] ) ? $options['data']['question'] : _l( 'N/A' );
		array_unshift( $link_options, $options );
		if ( self::can_i( 'view', 'Backups' ) ) {
			if ( $options['page'] == 'backup_settings' ) {
				$bubble_to_module = array(
					'module' => 'config',
				);
			}
		}
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $backup_id, $full = false ) {
		return self::link_generate( $backup_id, array( 'full'      => $full,
		                                               'arguments' => array( 'backup_id' => $backup_id )
		) );
	}


	public function process() {
		if ( 'save_backup' == $_REQUEST['_process'] ) {

			if ( ! module_backup::can_i( 'edit', 'Backups' ) ) {
				die( 'No perms to save backup.' );
			}
			if ( ! module_form::check_secure_key() ) {
				die( 'Invalid auth' );
			}
			if ( defined( '_UCM_HIDE_BACKUPS' ) ) {
				die( 'Invalid auth' );
			}
			if ( _DEMO_MODE ) {
				die( 'Sorry, cannot make backups in demo mode. ' );
			}

			$backup_id = update_insert( 'backup_id', $_REQUEST['backup_id'], 'backup', $_POST );


			if ( isset( $_REQUEST['butt_del'] ) && self::can_i( 'delete', 'Backups' ) ) {
				// and the file.
				$backup = $this->get_backup( $backup_id );
				if ( $backup && $backup['backup_id'] == $backup_id && module_form::confirm_delete( 'backup_id', _l( 'Really delete this backup?' ), self::link_open( $backup_id ) ) ) {

					if ( isset( $backup['backup_file'] ) && strlen( $backup['backup_file'] ) ) {
						if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql' ) ) {
							@unlink( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql' );
						}
						if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz' ) ) {
							@unlink( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.sql.gz' );
						}
						if ( file_exists( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip' ) ) {
							@unlink( _BACKUP_BASE_DIR . basename( $backup['backup_file'] ) . '.zip' );
						}
					}
					delete_from_db( 'backup', 'backup_id', $backup['backup_id'] );
					set_message( 'Backup deleted successfully.' );
					redirect_browser( $this->link_open( false ) );
				}
			}
			set_message( 'Backup saved successfully' );
			redirect_browser( $this->link_open( $backup_id ) );


		}
	}

	public static function link_external_backup( $backup_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for a backup ' . _UCM_SECRET . ' ' . $backup_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.backup/h.do_backup/i.' . $backup_id . '/hash.' . self::link_external_backup( $backup_id, true ) . '?plight' );
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'do_backup':
				$result          = array();
				$result['error'] = 'Backup failure';
				header( "Content-type: text/javascript" );
				$backup_id         = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash              = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				$backup_file_check = ( isset( $_POST['backup_file'] ) ) ? trim( $_POST['backup_file'] ) : false;
				$backup_type       = ( isset( $_POST['backup_type'] ) ) ? trim( $_POST['backup_type'] ) : false;
				if ( $backup_id > 0 && $hash && $backup_file_check && $backup_type ) {
					$correct_hash = $this->link_external_backup( $backup_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$backup_data = $this->get_backup( $backup_id );
						if ( $backup_data && $backup_data['backup_id'] == $backup_id && $backup_data['backup_file'] && $backup_data['backup_file'] == $backup_file_check ) {

							if ( _DEMO_MODE ) {
								$result['error'] = 'Sorry, cannot make backups in demo mode.';
							} else {
								// is a backup in progress?
								$backup_in_progress = module_config::c( 'backup_in_progress', 0 );
								if ( $backup_in_progress > ( time() - 120 ) ) {
									// another backup process is running, tell the javascript to retry...
									$result['retry'] = 1;
									unset( $result['error'] );
								} else {
									module_config::save_config( 'backup_in_progress', time() );
									@set_time_limit( 0 );
									// create the backup.
									switch ( $backup_type ) {
										case 'file':
											$path = isset( $_POST['path'] ) ? $_POST['path'] : false;
											if ( $path ) {
												$recurisive = isset( $_POST['recurisive'] ) ? $_POST['recurisive'] : false;
												// backup this path and add it to the includes file.
												$zip_file_name = _BACKUP_BASE_DIR . basename( $backup_data['backup_file'] ) . '.zip';
												$backup_result = $this->backup_system_files( dirname( __FILE__ ) . '/../../', $path, $recurisive, $zip_file_name );
												if ( is_array( $backup_result ) && $backup_result[1] ) {
													// we successfully backed up some files.
													$result['count'] = $backup_result[0];
													unset( $result['error'] );
												} else {
													$result['error'] = 'Failed to backup';
												}
											}
											break;
										case 'database';
											//
											$table_name = isset( $_POST['name'] ) ? $_POST['name'] : false;
											if ( $table_name ) {
												$sql_file_name = _BACKUP_BASE_DIR . basename( $backup_data['backup_file'] ) . '.sql';
												$count         = $this->backup_database_tables( $table_name, $sql_file_name );
												if ( $count !== false ) {
													$result['count'] = $count;
													unset( $result['error'] );
												}
											}
											break;
									}
									module_config::save_config( 'backup_in_progress', 0 );
								}
							}

						}
					}
				}
				echo json_encode( $result );
				exit;

				break;
		}
	}


	public static function get_backups( $search = array() ) {
		return get_multiple( 'backup', $search, 'backup_id', 'exact' );
	}

	public static function get_backup( $backup_id ) {
		$backup = get_single( 'backup', 'backup_id', $backup_id );

		return $backup;
	}


	public function backup_database_tables( $db_table_limit, $sql_file ) {

		$tables = array();
		$result = query( 'SHOW TABLES' );
		while ( $row = mysqli_fetch_row( $result ) ) {
			if ( ! strlen( _DB_PREFIX ) || strpos( $row[0], _DB_PREFIX ) === 0 ) {
				$tables[] = $row[0];
			}
		}
		if ( ! count( $tables ) || ! in_array( $db_table_limit, $tables ) ) {
			return false;
		}

		if ( module_config::c( 'backup_gz', 1 ) && function_exists( 'gzopen' ) && function_exists( 'gzwrite' ) ) {
			$handle = gzopen( $sql_file . '.gz', 'a9' );
		} else {
			$handle = fopen( $sql_file, 'a' );
		}

		$record_count = 0;
		//cycle through
		foreach ( $tables as $table ) {
			if ( $db_table_limit && $db_table_limit != $table ) {
				continue;
			}
			$sql        = "\n\n\n";
			$result     = query( 'SELECT * FROM `' . $table . '`' );
			$num_fields = mysqli_num_fields( $result );
			$sql        .= 'DROP TABLE IF EXISTS `' . $table . '`;';
			$row2       = mysqli_fetch_row( query( 'SHOW CREATE TABLE `' . $table . '`' ) );
			$sql        .= "\n" . $row2[1] . ";\n";
			if ( module_config::c( 'backup_gz', 1 ) && function_exists( 'gzopen' ) && function_exists( 'gzwrite' ) ) {
				gzwrite( $handle, $sql );
			} else {
				fwrite( $handle, $sql );
			}

			while ( $row = mysqli_fetch_row( $result ) ) {
				if ( strpos( $table, 'config' ) !== false ) {
					if ( $row[0] == 'system_base_href' ) {
						continue;
					}
					if ( $row[0] == 'system_base_dir' ) {
						continue;
					}
				}
				$sql = "";
				$sql .= 'INSERT INTO `' . $table . '` VALUES(';
				for ( $j = 0; $j < $num_fields; $j ++ ) {
					//$row[ $j ] = addslashes( $row[ $j ] );
					//$row[ $j ] = preg_replace( "#n#", "n", $row[ $j ] );
					if ( isset( $row[ $j ] ) ) {
						$sql .= '"' . addslashes( $row[ $j ] ) . '"';
					} else {
						$sql .= '""';
					}
					if ( $j < ( $num_fields - 1 ) ) {
						$sql .= ',';
					}
				}
				$sql .= ");\n";
				$record_count ++;
				if ( module_config::c( 'backup_gz', 1 ) && function_exists( 'gzopen' ) && function_exists( 'gzwrite' ) ) {
					gzwrite( $handle, $sql );
				} else {
					fwrite( $handle, $sql );
				}
			}
		}

		if ( module_config::c( 'backup_gz', 1 ) && function_exists( 'gzopen' ) && function_exists( 'gzwrite' ) ) {
			gzclose( $handle );
		} else {
			fclose( $handle );
		}

		return $record_count;

	}

	public function backup_system_files( $source, $limit_folder, $recurisive, $destination ) {
		if ( extension_loaded( 'zip' ) ) {
			if ( file_exists( $source ) ) {
				$source       = realpath( $source );
				$source       = rtrim( $source, '/' ) . '/';
				$limit_folder = realpath( $source . $limit_folder );
				$limit_folder = rtrim( $limit_folder, '/' ) . '/';
				if ( is_dir( $source ) && is_dir( $source . 'includes/plugin_backup/' ) && is_file( $source . 'init.php' ) ) {
					// we are backing up a folder now.
					if ( $limit_folder == $source || strpos( $limit_folder, $source ) === 0 ) {
						$zip        = new ZipArchive();
						$file_count = 0;
						if ( $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {
							if ( $recurisive ) {
								$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $limit_folder ), RecursiveIteratorIterator::SELF_FIRST );
							} else {
								$files = new DirectoryIterator( $limit_folder );
							}
							/*echo "Limit: $limit_folder \n";
							echo "Source: $source \n";
							var_dump($files);
							foreach ($files as $file) {
								$name = basename($file);
								if(!$recurisive){
									$file = $limit_folder.$file;
								}
								echo "Adding $file ($name) ";
								$file = realpath($file);
								echo " ($file)\n<br>";
								if($name == '.' || $name == '..')continue;
								echo " - as ".str_replace($source, '', $file . '/')."\n<br>";
							}
							return false;*/
							$zip->addEmptyDir( 'files/' );
							//echo "Source is $source \n<br>";
							$exists = array();
							foreach ( $files as $file ) {
								$name = basename( $file );
								if ( $name == '.' || $name == '..' || $name == '.hg' || $name == '.git' || strpos( $file, '.old' ) !== false ) {
									continue;
								}
								if ( ! $recurisive ) {
									$file = $limit_folder . $file;
								}

								//if($file->isDot()) continue;
								$file = realpath( $file );
								//echo "Adding $file\n<br>";
								//echo " - as ".str_replace($source, '', $file . '/')."\n<br>";
								if ( is_dir( $file ) ) {
									$zip_dest = str_replace( $source, '', $file . '/' );
									if ( ! isset( $exists[ $zip_dest ] ) ) {
										$exists[ $zip_dest ] = true;
										$zip->addEmptyDir( 'files/' . $zip_dest );
									}
								} else if ( is_file( $file ) && ! strpos( $file, 'backup/backups/' ) ) {
									//$zip->addFromString('files/'.str_replace($source . '/', '', $file), file_get_contents($file));
									//$zip->addFile($file,'files/'.str_replace($source . '/', '', $file));
									$zip_dest = str_replace( $source, '', $file );
									if ( ! isset( $exists[ $zip_dest ] ) ) {
										$exists[ $zip_dest ] = true;
										$zip->addFile( $file, 'files/' . $zip_dest );
										$file_count ++;
									}
								}
								//if($x++>100)break;
							}//foreach
							return array( $file_count, $zip->close() );
						} else {
							// failed to create zip
						}
					} else {
						// invalid folder.
					}
				} else {
					// invalid folder
				}
			} else {
				// source doesn't exist.
			}
		} else {
			// no zip extension loaded.
		}

		return false;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>backup` (
		`backup_id` int(11) NOT NULL AUTO_INCREMENT,
		`backup_file` varchar(255) NOT NULL DEFAULT '',
		`create_user_id` int(11) NOT NULL DEFAULT '0',
		`update_user_id` int(11) NOT NULL DEFAULT '0',
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		PRIMARY KEY (`backup_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php

		$sql = ob_get_clean();

		return $sql;
	}

}


