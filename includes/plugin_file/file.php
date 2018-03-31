<?php

define( '_FILE_UPLOAD_ALERT_STRING', 'Receive File Upload Alerts' );
define( '_FILE_COMMENT_ALERT_STRING', 'Receive File Comment Alerts' );
define( '_FILE_NOTIFICATION_TYPE_UPLOADED', 1 );
define( '_FILE_NOTIFICATION_TYPE_UPDATED', 2 );
define( '_FILE_NOTIFICATION_TYPE_COMMENTED', 3 );


define( '_FILE_ACCESS_ALL', 'All files in system' ); // do not change string
define( '_FILE_ACCESS_JOBS', 'Only files I from jobs I have access to' ); // do not change string
define( '_FILE_ACCESS_CUSTOMERS', 'Only files from customers I have access to' ); // do not change string
define( '_FILE_ACCESS_ME', 'Only files I have uploaded' ); // do not change string
define( '_FILE_ACCESS_ASSIGNED', 'Only files I have been assigned to' ); // do not change string

define( '_FILE_UPLOAD_PATH', ( defined( '_UCM_FILE_STORAGE_DIR' ) ? _UCM_FILE_STORAGE_DIR : '' ) . 'includes/plugin_file/upload/' );


class module_file extends module_base {

	var $links;

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
		$this->links           = array();
		$this->module_name     = "file";
		$this->module_position = 21;

		$this->version = 2.574;
		// 2.574 - 2017-07-26 - fixes for php error messages
		// 2.573 - 2017-05-02 - file path configuration
		// 2.572 - 2017-01-03 - extra field searching
		// 2.571 - 2016-07-10 - big update to mysqli
		// 2.570 - 2016-03-14 - thumbnail preview size
		// 2.569 - 2016-01-21 - file_enable_preview advanced settings
		// 2.568 - 2015-12-28 - menu speed up
		// 2.567 - 2015-07-18 - bucket download zip
		// 2.566 - 2015-03-30 - pdf preview bug fix
		// 2.565 - 2015-03-08 - file approval email goes to admin as well
		// 2.564 - 2015-01-27 - file upload dashboard alert bug fix
		// 2.563 - 2015-01-26 - file tag bug fix
		// 2.562 - 2014-12-19 - file bucket feature
		// 2.561 - 2014-12-08 - file delete fix
		// 2.56 - 2014-11-19 - multiple file and drag-drop uploads
		// 2.559 - 2014-11-19 - multiple file upload and bulk delete
		// 2.558 - 2014-10-08 - file pointer css fix
		// 2.557 - 2014-08-03 - responsive improvements and new uploader version
		// 2.556 - 2014-07-21 - extra fields available in template
		// 2.555 - 2014-07-02 - file permission/alert improvements
		// 2.554 - 2014-02-23 - newsletter image fix
		// 2.553 - 2014-02-09 - file description fix
		// 2.552 - 2014-01-23 - new quote feature
		// 2.551 - 2014-01-10 - permission fix
		// 2.55 - 2014-01-05 - email fix when adding new file. default staff selected in drop down
		// 2.549 - 2014-01-03 - emailing a file to customer for approval
		// 2.548 - 2013-12-11 - new file user role settings
		// 2.547 - 2013-11-24 - upload fix when using progress bar
		// 2.546 - 2013-11-23 - file download improvement for some hosting accounts
		// 2.545 - 2013-11-15 - working on new UI
		// 2.544 - 2013-09-13 - page title translation fix

		// fix for files linked to multiple jobs
		// 2.42 - extra protection for assigning files to different customers.
		// 2.421 - job name displaying. htmlspecialchars removing.
		// 2.422 - bug fix.
		// 2.423 - bug fix creating new file under a customer.
		// 2.424 - files shared between customer accounts
		// 2.425 - bug fix for output appreaing before downloading images.
		// 2.5 - file previews.
		// 2.51 - fix ob error when no ob is present.
		// 2.511 - Delete file comments working
		// 2.512 - mime type fix for download (changed from old pdf to dynamic)
		// 2.513 - short open tags.
		// 2.514 - moves files tab to main menu, with a configuration variable. also put perms on the commenting system.
		// 2.52 - better file comment perms
		// 2.521 - file in menu perms.
		// 2.522 - file comment create perm bug fix
		// 2.523 - link fix for no customer perms
		// 2.524 - fix for mobile layout
		// 2.525 - adding files by url
		// 2.526 - swap to url bug fix
		// 2.527 - when new files are added an alert is sent (email/dashboard). alert will stay in place until that user views file.
		// 2.528 - customer contact alert email fix
		// 2.529 - newsletter system fixes
		// 2.53 - sql bug fix
		// 2.531 - email file notification fix
		// 2.532 - extra fields update - show in main listing option
		// 2.533 - better document support
		// 2.534 - improved quick search
		// 2.535 - 2013-04-10 - new customer permissions
		// 2.536 - 2013-05-11 - file upload progress indicator (swap back with 'file_upload_old' setting)
		// 2.537 - 2013-06-07 - file saving fix
		// 2.538 - 2013-06-21 - permission update
		// 2.539 - 2013-07-17 - progress bar
		// 2.54 - 2013-07-25 - pdf preview in file tab area (beta)
		// 2.541 - 2013-07-26 - pdf download fix
		// 2.542 - 2013-07-29 - new _UCM_SECRET hash in config.php
		// 2.543 - 2013-08-31 - pdf embed preview fix

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'file_upload_alert_email', 'Dear {TO_NAME},<br>
<br>
A file has been uploaded/updated by {FROM_NAME} called {FILE_NAME}.<br><br>
View this file by going here: {FILE_LINK}<br><br>
Customer: {CUSTOMER_NAME}<br/>
Description: {DESCRIPTION}
', 'File Updated: {FILE_NAME} {CUSTOMER_NAME}', array(
				'to_name'       => 'Recipient name',
				'from_name'     => 'Uploader name',
				'file_link'     => 'Link to file',
				'customer_name' => 'Customer Name',
				'description'   => 'File notes',
			) );
			module_template::init_template( 'file_comment_alert_email', 'Dear {TO_NAME},<br>
<br>
A file has been commented on by {FROM_NAME} called {FILE_NAME}.<br><br>
View this file by going here: {FILE_LINK}<br><br>
Customer: {CUSTOMER_NAME}<br/>
Comment: {COMMENT}
', 'File Comment: {FILE_NAME} {CUSTOMER_NAME}', array(
				'to_name'       => 'Recipient name',
				'from_name'     => 'Uploader name',
				'file_link'     => 'Link to file',
				'customer_name' => 'Customer Name',
				'comment'       => 'File comment',
			) );
		}

		hook_add( 'customer_deleted', 'module_file::hook_customer_deleted' );

		module_config::register_css( 'file', 'file.css' );
		module_config::register_css( 'file', 'featherlight.css' );
		module_config::register_js( 'file', 'featherlight.js' );

	}

	public function pre_menu() {

		if ( $this->can_i( 'edit', 'Files' ) || $this->can_i( 'view', 'Files' ) ) {
			/*$this->ajax_search_keys = array(
                _DB_PREFIX.'file' => array(
                    'plugin' => 'file',
                    'search_fields' => array(
                        'file_name',
                        'description',
                    ),
                    'key' => 'file_id',
                    'title' => _l('File: '),
                ),
            );*/

			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many files?
				$name = _l( 'Files' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$files = $this->get_files( array(
						'customer_id'           => $_REQUEST['customer_id'],
						'bucket_parent_file_id' => 0
					) );
					if ( count( $files ) ) {
						$name .= " <span class='menu_label'>" . count( $files ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "file_admin",
					'args'                => array( 'file_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'file-o',
				);
			}
			/*$this->links[] = array(
                "name"=>"Files",
                "p"=>"file_admin",
                'args'=>array('file_id'=>false),
            );*/

		}

		if ( module_config::c( 'files_on_main_menu', 1 ) && ( $this->can_i( 'edit', 'Files' ) || $this->can_i( 'view', 'Files' ) ) ) {
			// find out how many for this contact.
			$link_name = _l( 'Files' );
			if ( module_config::c( 'menu_show_summary', 0 ) ) {
				$files     = $this->get_files( array( 'bucket_parent_file_id' => 0 ) );
				$link_name .= " <span class='menu_label'>" . count( $files ) . "</span> ";
			}
			$this->links[] = array(
				"name"      => $link_name,
				"p"         => "file_admin",
				'args'      => array( 'file_id' => false ),
				'icon_name' => 'file-o',
			);

			/*$customer_ids = module_security::get_customer_restrictions();
            if($customer_ids){
                $files = array();
                foreach($customer_ids as $customer_id){
                    $files = $files + $this->get_files(array('customer_id'=>$customer_id));
                }
                $this->links[] = array(
                    "name"=>_l('Files')." <span class='menu_label'>".count($files)."</span> ",
                    "p"=>"file_admin",
                    'args'=>array('file_id'=>false),
                );
            }*/
		}
	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."file` c WHERE ";
			//$sql .= " c.`file_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_files( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					// what part of this matched?
					/*if(
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['last_name']) ||
                        preg_match('#'.preg_quote($search_key,'#').'#i',$result['phone'])
                    ){
                        // we matched the file contact details.
                        $match_string = _l('File Contact: ');
                        $match_string .= _shl($result['file_name'],$search_key);
                        $match_string .= ' - ';
                        $match_string .= _shl($result['name'],$search_key);
                        // hack
                        $_REQUEST['file_id'] = $result['file_id'];
                        $ajax_results [] = '<a href="'.module_user::link_open_contact($result['user_id']) . '">' . $match_string . '</a>';
                    }else{*/
					$match_string    = _l( 'File: ' );
					$match_string    .= _shl( $result['file_name'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['file_id'] ) . '">' . $match_string . '</a>';
					//$ajax_results [] = $this->link_open($result['file_id'],true);
					/*}*/
				}
			}
		}

		return $ajax_results;
	}


	public static function link_generate( $file_id = false, $options = array(), $link_options = array() ) {

		$key = 'file_id';
		if ( $file_id === false && $link_options ) {
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
			$options['type'] = 'file';
		}
		$options['page'] = isset( $options['page'] ) ? $options['page'] : 'file_admin';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['file_id'] = $file_id;
		$options['module']               = 'file';
		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		} else {
			$data = array();
			if ( $file_id > 0 ) {
				$data = self::get_file( $file_id );
			}
			$options['data'] = $data;
		}
		if ( ! isset( $data['customer_id'] ) && isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] ) {
			$data['customer_id'] = (int) $_REQUEST['customer_id'];
		}
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['file_name'] ) || ! trim( $data['file_name'] ) ) ? 'N/A' : $data['file_name'];
		if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
			$bubble_to_module = array(
				'module'   => 'customer',
				'argument' => 'customer_id',
			);
		}
		array_unshift( $link_options, $options );

		if ( ! module_security::has_feature_access( array(
			'name'        => 'Customers',
			'module'      => 'customer',
			'category'    => 'Customer',
			'view'        => 1,
			'description' => 'view',
		) )
		) {
			$bubble_to_module = false;
			/*if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : 'N/A';
            }*/

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

	public static function link_open( $file_id, $full = false, $bucket_parent_file_id = false ) {
		return self::link_generate( $file_id, array(
			'full'      => $full,
			'arguments' => array( 'bucket_parent_file_id' => $bucket_parent_file_id )
		) );
	}

	public static function link_open_bucket( $file_id, $full = false ) {
		return self::link_generate( $file_id, array( 'full' => $full, 'arguments' => array( 'bucket' => 1 ) ) );
	}

	public static function link_open_email( $file_id, $full = false ) {
		return self::link_generate( $file_id, array( 'full' => $full, 'arguments' => array( 'email' => 1 ) ) );
	}


	function handle_hook( $hook, &$calling_module = false, $owner_table = false, $key_name = false, $key_value = false ) {
		switch ( $hook ) {
			case 'home_alerts':
				$alerts = array();
				if ( self::can_i( 'view', 'Files' ) && module_config::c( 'file_upload_alerts', 1 ) &&
				     (
					     module_security::can_user( module_security::get_loggedin_id(), _FILE_UPLOAD_ALERT_STRING ) ||
					     module_security::can_user( module_security::get_loggedin_id(), _FILE_COMMENT_ALERT_STRING )
				     )
				) {
					$sql   = "SELECT * FROM `" . _DB_PREFIX . "file_notification` fn ";
					$sql   .= " WHERE fn.user_id = " . (int) module_security::get_loggedin_id() . " AND fn.view_time = 0";
					$sql   .= " GROUP BY fn.file_id";
					$files = qa( $sql );
					foreach ( $files as $file ) {
						$file_data     = self::get_file( $file['file_id'] );
						$customer_data = array();
						if ( $file_data['customer_id'] ) {
							$customer_data = module_customer::get_customer( $file_data['customer_id'] );
							if ( ! $customer_data || $customer_data['customer_id'] != $file_data['customer_id'] ) {
								continue;// current user doesn't have permission to view this customer.
							}
						}
						switch ( $file['notification_type'] ) {
							case _FILE_NOTIFICATION_TYPE_COMMENTED:
								$status = _l( 'File Comment' );
								break;
							case _FILE_NOTIFICATION_TYPE_UPDATED:
								$status = _l( 'File Updated' );
								break;
							case _FILE_NOTIFICATION_TYPE_UPLOADED:
								$status = _l( 'New File Uploaded' );
								break;
							default:
								$status = _l( 'File' );
						}
						$alert_res = process_alert( $file['date_created'], $status );
						if ( $alert_res ) {
							$alert_res['link'] = $this->link_open( $file['file_id'], false );
							$alert_res['name'] = '';
							if ( $customer_data ) {
								$alert_res['name'] .= $customer_data['customer_name'] . ' ';
							}
							$alert_res['name']                   .= _l( '(File: %s)', $file_data['file_name'] );
							$alerts[ 'file' . $file['file_id'] ] = $alert_res;
						}
					}
				}

				return $alerts;
				break;
			case 'file_list':
			case 'file_delete':
				// find the key we are saving this address against.
				$owner_id = (int) $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}
				$file_hash = md5( $owner_id . '|' . $owner_table ); // just for posting unique arrays.
				break;
		}
		switch ( $hook ) {
			case "file_list":
				if ( $owner_id && $owner_id != 'new' ) {

					$file_items = $this->get_files( array( "owner_table" => $owner_table, "owner_id" => $owner_id ) );
					foreach ( $file_items as &$file_item ) {
						// do it in loop here because of $this issues in static method below.
						// instead of include file below.
						$file_item['html'] = $this->print_file( $file_item['file_id'] );
					}
					include( "pages/file_list.php" );
				} else {
					echo 'Please save first before creating files.';
				}
				break;
			case "file_delete":

				if ( $owner_table && $owner_id ) {
					$this->delete_files( $owner_table, $owner_id );
				}
				break;

		}
	}


	public static function hook_customer_deleted( $callback_name, $customer_id, $remove_linked_data ) {
		if ( (int) $customer_id > 0 && $remove_linked_data ) {
			$files = get_multiple( 'file', array( 'customer_id' => $customer_id ) );
			foreach ( $files as $file ) {
				$ucm_file = new ucm_file( $file['file_id'] );
				$ucm_file->delete();
			}
		}
	}

	public static function display_files( $options ) {

		$owner_id    = ( isset( $options['owner_id'] ) && $options['owner_id'] ) ? (int) $options['owner_id'] : false;
		$owner_table = ( isset( $options['owner_table'] ) && $options['owner_table'] ) ? $options['owner_table'] : false;
		if ( $owner_id && $owner_table ) {
			// we have all that we need to display some files!! yey!!
			// do we display a summary or not?
			global $plugins;
			$file_items = $plugins['file']->get_files( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
			if ( isset( $options['summary_owners'] ) && is_array( $options['summary_owners'] ) ) {
				// generate a list of other files we have to display int eh list.
				foreach ( $options['summary_owners'] as $summary_owner_table => $summary_owner_ids ) {
					if ( is_array( $summary_owner_ids ) ) {
						foreach ( $summary_owner_ids as $summary_owner_id ) {
							$file_items = array_merge( $file_items, $plugins['file']->get_files( array(
								'owner_table' => $summary_owner_table,
								'owner_id'    => $summary_owner_id
							) ) );
						}
					}
				}
			}
			$layout_type = ( isset( $options['layout'] ) && $options['layout'] ) ? $options['layout'] : 'gallery';
			$editable    = ( ! isset( $options['editable'] ) || $options['editable'] );
			foreach ( $file_items as &$file_item ) {
				$file_item['html'] = $plugins['file']->print_file( $file_item['file_id'], $layout_type, $editable, $options );
			}

			if ( get_display_mode() == 'mobile' ) {
				$editable = false;
			}
			$title = ( isset( $options['title'] ) && $options['title'] ) ? $options['title'] : false;
			if ( ! is_file( 'pages/file_list_' . basename( $layout_type ) . '.php' ) || ! @include( 'pages/file_list_' . basename( $layout_type ) . '.php' ) ) {
				include( "pages/file_list.php" );
			}
		}
	}

	public function print_file( $file_id, $layout_type = 'gallery', $editable = true, $options = array() ) {
		$file_item = $this->get_file( $file_id );
		ob_start();
		switch ( $layout_type ) {
			case 'gallery':
				$thumb_width = (int) module_config::c( 'file_image_preview_width', 100 );
				?>

				<div class="file_<?php echo $file_item['file_id']; ?>"
				     style="float:left; width:<?php echo $thumb_width + 10; ?>px; margin:3px; border:1px solid #CCC; text-align:center;">
					<div style="width:<?php echo $thumb_width + 10; ?>px; min-height:40px; ">
						<?php
						$link = $this->link( '', array( '_process' => 'download', 'file_id' => $file_id ), 'file', false );
						if ( isset( $options['click_callback'] ) ) {
							$link = 'javascript:' . $options['click_callback'] . '(' . $file_id . ',\'' . htmlspecialchars( $this->link_public_view( $file_id ) ) . '\',\'' . htmlspecialchars( addcslashes( $file_item['file_name'], "'" ) ) . '\')';
						}
						// /display a thumb if its supported.
						if ( preg_match( '/\.(\w\w\w\w?)$/', $file_item['file_name'], $matches ) ) {
							switch ( strtolower( $matches[1] ) ) {
								case 'jpg':
								case 'jpeg':
								case 'gif':
								case 'png':
									?>
									<a href="<?php echo $link; ?>" class="file_image_preview" data-featherlight="image">
										<img src="<?php
										// echo _BASE_HREF . nl2br(htmlspecialchars($file_item['file_path']));
										echo $this->link_public_view( $file_id );
										?>" width="<?php echo $thumb_width; ?>" alt="download" border="0">
									</a>
									<?php
									break;
								default:
									?>
									<a href="<?php echo $link; ?>" class="file_image_preview" data-featherlight="image">
										<img src="<?php echo full_link( 'includes/plugin_file/images/file_icon.png' ); ?>" width="100"
										     alt="<?php _e( 'Download' ); ?>">
									</a>
								<?php
								//echo 'Download';
							}
						}
						?>
					</div>
					<?php if ( $editable ) { ?>
						<a href="#" class="file_edit<?php echo $file_item['owner_table']; ?>_<?php echo $file_item['owner_id']; ?>"
						   rel="<?php echo $file_item['file_id']; ?>"><?php echo nl2br( wordwrap( htmlspecialchars( $file_item['file_name'] ), 15, '<wbr>', true ) ); ?></a>
					<?php } else { ?>
						<a href="<?php echo $this->link( '', array(
							'_process' => 'download',
							'file_id'  => $file_item['file_id']
						), 'file', false ); ?>"><?php echo nl2br( wordwrap( htmlspecialchars( $file_item['file_name'] ), 15, '<wbr>', true ) ); ?></a>
					<?php } ?>
				</div>
				<?php
				break;
			case 'list':
				?>
				<span class="file_<?php echo $file_item['file_id']; ?>">
				<?php if ( $editable ) { ?>
					<a href="#" class="file_edit<?php echo $file_item['owner_table']; ?>_<?php echo $file_item['owner_id']; ?>"
					   rel="<?php echo $file_item['file_id']; ?>"><?php echo nl2br( htmlspecialchars( $file_item['file_name'] ) ); ?></a>
				<?php } else { ?>
					<a href="<?php echo $this->link( '', array(
						'_process' => 'download',
						'file_id'  => $file_item['file_id']
					), 'file', false ); ?>"><?php echo nl2br( htmlspecialchars( $file_item['file_name'] ) ); ?></a>
				<?php } ?>
			</span>
				<?php
				break;
		}

		return ob_get_clean();
	}

	function process() {
		if ( 'plupload' == $_REQUEST['_process'] ) {


			if ( ! self::can_i( 'edit', 'Files' ) && ! self::can_i( 'create', 'Files' ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Permission error."}, "id" : "id"}' );
			}

			@ob_end_clean();

			// HTTP headers for no cache etc
			header( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
			header( "Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . " GMT" );
			header( "Cache-Control: no-store, no-cache, must-revalidate" );
			header( "Cache-Control: post-check=0, pre-check=0", false );
			header( "Pragma: no-cache" );

			// Settings
			$targetDir = _FILE_UPLOAD_PATH . "plupload";
			//$targetDir = 'uploads';

			$cleanupTargetDir = true; // Remove old files
			$maxFileAge       = 5 * 3600; // Temp file age in seconds

			// 5 minutes execution time
			@set_time_limit( 5 * 60 );

			// Uncomment this one to fake upload time
			// usleep(5000);

			// Get parameters
			$chunk    = isset( $_REQUEST["chunk"] ) ? intval( $_REQUEST["chunk"] ) : 0;
			$chunks   = isset( $_REQUEST["chunks"] ) ? intval( $_REQUEST["chunks"] ) : 0;
			$fileName = isset( $_REQUEST["plupload_key"] ) ? $_REQUEST["plupload_key"] : '';
			$fileName .= isset( $_REQUEST["fileid"] ) ? '-' . $_REQUEST["fileid"] : '';
			$fileName = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $fileName );
			if ( ! $fileName ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "No plupload_key defined."}, "id" : "id"}' );
			}

			// Make sure the fileName is unique but only if chunking is disabled
			if ( $chunks < 2 && file_exists( $targetDir . DIRECTORY_SEPARATOR . $fileName ) ) {
				$ext        = strrpos( $fileName, '.' );
				$fileName_a = substr( $fileName, 0, $ext );
				$fileName_b = substr( $fileName, $ext );

				$count = 1;
				while ( file_exists( $targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b ) ) {
					$count ++;
				}

				$fileName = $fileName_a . '_' . $count . $fileName_b;
			}

			$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

			// Create target dir
			if ( ! file_exists( $targetDir ) ) {
				@mkdir( $targetDir );
			}

			// Remove old temp files
			if ( $cleanupTargetDir ) {
				if ( ! is_dir( $targetDir ) || ! $dir = opendir( $targetDir ) ) {
					die( '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}' );
				}
				while ( ( $file = readdir( $dir ) ) !== false ) {
					$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
					// If temp file is current file proceed to the next
					if ( $tmpfilePath == "{$filePath}.part" ) {
						continue;
					}
					// Remove temp file if it is older than the max age and is not the current file
					if ( preg_match( '/\.part$/', $file ) && ( filemtime( $tmpfilePath ) < time() - $maxFileAge ) ) {
						@unlink( $tmpfilePath );
					}
				}
				closedir( $dir );
			}

			/// Open temp file
			if ( ! $out = @fopen( "{$filePath}.part", $chunks ? "ab" : "wb" ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}' );
			}
			if ( ! empty( $_FILES ) ) {
				if ( $_FILES["file"]["error"] || ! is_uploaded_file( $_FILES["file"]["tmp_name"] ) ) {
					die( '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}' );
				}
				// Read binary input stream and append it to temp file
				if ( ! $in = @fopen( $_FILES["file"]["tmp_name"], "rb" ) ) {
					die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
				}
			} else {
				if ( ! $in = @fopen( "php://input", "rb" ) ) {
					die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
				}
			}
			while ( $buff = fread( $in, 4096 ) ) {
				fwrite( $out, $buff );
			}
			@fclose( $out );
			@fclose( $in );
			// Check if file has been uploaded
			if ( ! $chunks || $chunk == $chunks - 1 ) {
				// Strip the temp .part suffix off
				rename( "{$filePath}.part", $filePath );
			}


			die( '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}' );


		} else if ( 'download' == $_REQUEST['_process'] ) {
			@ob_end_clean();
			$file_id   = (int) $_REQUEST['file_id'];
			$file_data = $this->get_file( $file_id );
			if ( isset( $file_data['file_url'] ) && strlen( $file_data['file_url'] ) ) {
				redirect_browser( $file_data['file_url'] );
			} else if ( is_file( $file_data['file_path'] ) ) {
				header( "Pragma: public" );
				header( "Expires: 0" );
				header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
				header( "Cache-Control: private", false );
				//header("Content-Type: application/pdf");
				header( "Content-type: " . dtbaker_mime_type( $file_data['file_name'], $file_data['file_path'] ) );
				header( "Content-Disposition: attachment; filename=\"" . $file_data['file_name'] . "\";" );
				header( "Content-Transfer-Encoding: binary" );
				header( "Content-Length: " . filesize( $file_data['file_path'] ) );
				//readfile($file_data['file_path']);
				$size = @readfile( $file_data['file_path'] );
				if ( ! $size ) {
					echo file_get_contents( $file_data['file_path'] );
				}
			} else {
				echo 'Not found';
			}
			exit;
		} else if ( 'save_file_popup' == $_REQUEST['_process'] ) {
			$file_id = $_REQUEST['file_id'];

			$file_path = false;
			$file_name = false;

			$options = unserialize( base64_decode( $_REQUEST['options'] ) );

			// have we uploaded anything
			if ( isset( $_FILES['file_upload'] ) && is_uploaded_file( $_FILES['file_upload']['tmp_name'] ) ) {
				// copy to file area.
				$file_name = basename( $_FILES['file_upload']['name'] );
				if ( $file_name ) {
					$file_path = _FILE_UPLOAD_PATH . md5( time() . $file_name );
					if ( move_uploaded_file( $_FILES['file_upload']['tmp_name'], $file_path ) ) {
						// it worked. umm.. do something.
					} else {
						?>
						<script type="text/javascript">
                alert('Unable to save file. Please check permissions.');
						</script>
						<?php
						// it didnt work. todo: display error.
						$file_path = false;
						$file_name = false;
						//set_error('Unable to save file');
					}
				}
			}

			if ( isset( $_REQUEST['file_name'] ) && $_REQUEST['file_name'] ) {
				$file_name = $_REQUEST['file_name'];
			}

			if ( ! $file_path && ! $file_name ) {
				return false;
			}

			if ( ! $file_id || $file_id == 'new' ) {
				$file_data = array(
					'file_id'     => $file_id,
					'owner_id'    => (int) $_REQUEST['owner_id'],
					'owner_table' => $_REQUEST['owner_table'],
					'file_time'   => time(), // allow UI to set a file time? nah.
					'file_name'   => $file_name,
					'file_path'   => $file_path,
				);
			} else {
				// some fields we dont want to overwrite on existing files:
				$file_data = array(
					'file_id'   => $file_id,
					'file_path' => $file_path,
					'file_name' => $file_name,
				);
			}
			// make sure we're saving a file we have access too.
			module_security::sanatise_data( 'file', $file_data );
			$file_id   = update_insert( 'file_id', $file_id, 'file', $file_data );
			$file_data = $this->get_file( $file_id );
			// we've updated from a popup.
			// this means we have to replace an existing file id with the updated output.
			// or if none exists on the page, we add a new one to the holder.
			$layout_type = ( isset( $_REQUEST['layout'] ) && $_REQUEST['layout'] ) ? $_REQUEST['layout'] : 'gallery';
			?>
			<script type="text/javascript">
          // check if it exists in parent window
          var new_html = '<?php echo addcslashes( preg_replace( '/\s+/', ' ', $this->print_file( $file_id, $layout_type, true, $options ) ), "'" );?>';
          parent.new_file_added<?php echo $file_data['owner_table'];?>_<?php echo $file_data['owner_id'];?>(<?php echo $file_id;?>, '<?php echo $file_data['owner_table'];?>',<?php echo $file_data['owner_id'];?>, new_html);
			</script>
			<?php
			exit;
		} else if ( 'save_file' == $_REQUEST['_process'] ) {
			$file_id = (int) $_REQUEST['file_id'];

			$file_path = false;
			$file_name = false;
			$file_url  = '';

			if ( isset( $_REQUEST['butt_del'] ) && self::can_i( 'delete', 'Files' ) ) {
				if ( module_form::confirm_delete( 'file_id', 'Really delete this file?' ) ) {
					$ucm_file = new ucm_file( $file_id );
					$ucm_file->delete();
					set_message( 'File removed successfully' );
				}
				redirect_browser( module_file::link_open( false ) );
			} else {

				$files_to_save = array(); // pump data in to here for multiple file uploads.

				// todo: stop people changing the "file_id" to another file they don't own.
				if ( self::can_i( 'edit', 'Files' ) || self::can_i( 'create', 'Files' ) ) {
					// have we uploaded anything
					$file_changed = false;

					if ( ! is_dir( _FILE_UPLOAD_PATH . 'plupload/' ) ) {
						mkdir( _FILE_UPLOAD_PATH . 'plupload/', 0777, true );
					}


					if ( isset( $_REQUEST['plupload_key'] ) && isset( $_REQUEST['plupload_file_name'] ) && is_array( $_REQUEST['plupload_file_name'] ) && strlen( preg_replace( '/[^a-zA-Z0-9-_]+/', '', basename( $_REQUEST['plupload_key'] ) ) ) ) {
						$plupload_key = preg_replace( '/[^a-zA-Z0-9-_]+/', '', basename( $_REQUEST['plupload_key'] ) );
						foreach ( $_REQUEST['plupload_file_name'] as $plupload_file_name_key => $file_name ) {
							$plupload_file_name_key = preg_replace( '/[^a-zA-Z0-9-_]+/', '', basename( $plupload_file_name_key ) );
							if ( $plupload_key && $plupload_file_name_key && $file_name && is_file( _FILE_UPLOAD_PATH . 'plupload' . DIRECTORY_SEPARATOR . $plupload_key . '-' . $plupload_file_name_key ) ) {
								$file_path = _FILE_UPLOAD_PATH . time() . '-' . md5( time() . $file_name );
								if ( rename( _FILE_UPLOAD_PATH . 'plupload' . DIRECTORY_SEPARATOR . $plupload_key . '-' . $plupload_file_name_key, $file_path ) ) {
									// it worked. umm.. do something.
									$file_changed    = true;
									$files_to_save[] = array(
										'file_path' => $file_path,
										'file_name' => $file_name,
									);
								} else {
									// it didnt work. todo: display error.
									$file_path = false;
									$file_name = false;
									set_error( 'Unable to save file via plupload.' );
								}
							}
						}

					}
					// the old file upload method, no plupload:
					if ( ! $file_changed && isset( $_FILES['file_upload'] ) && is_uploaded_file( $_FILES['file_upload']['tmp_name'] ) ) {
						// copy to file area.
						$file_name = basename( $_FILES['file_upload']['name'] );
						if ( $file_name ) {
							$file_path = _FILE_UPLOAD_PATH . time() . '-' . md5( time() . $file_name );
							if ( move_uploaded_file( $_FILES['file_upload']['tmp_name'], $file_path ) ) {
								// it worked. umm.. do something.
								$file_changed    = true;
								$files_to_save[] = array(
									'file_path' => $file_path,
									'file_name' => $file_name,
								);
							} else {
								// it didnt work. todo: display error.
								$file_path = false;
								$file_name = false;
								set_error( 'Unable to save file' );
							}
						}
					}
					if ( ! $file_path && isset( $_REQUEST['file_url'] ) && isset( $_REQUEST['file_name'] ) ) {
						$files_to_save[] = array(
							'file_path' => '',
							'file_url'  => $_REQUEST['file_url'],
							'file_name' => $_REQUEST['file_name'],
						);
					}
					if ( ! $file_path && isset( $_REQUEST['bucket'] ) ) {
						$files_to_save[] = array(
							'file_name' => $_REQUEST['file_name'],
							'bucket'    => 1,
						);
					}

					// make sure we have a valid customer_id and job_id selected.
					$possible_customers = $possible_jobs = array();
					if ( class_exists( 'module_customer', false ) ) {
						$possible_customers = module_customer::get_customers();
					}
					if ( class_exists( 'module_job', false ) ) {
						$possible_jobs = module_job::get_jobs();
					}

					$original_file_data = array();
					if ( $file_id > 0 ) {
						$original_file_data = self::get_file( $file_id );
						if ( ! $original_file_data || $original_file_data['file_id'] != $file_id ) {
							die( 'No permissions to update this file' );
						}
					}

					$new_file = false;
					if ( ! $file_id ) {
						$file_data = array(
							'file_id'               => $file_id,
							'bucket_parent_file_id' => isset( $_REQUEST['bucket_parent_file_id'] ) ? (int) $_REQUEST['bucket_parent_file_id'] : false,
							'customer_id'           => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false,
							'job_id'                => isset( $_REQUEST['job_id'] ) ? (int) $_REQUEST['job_id'] : false,
							'quote_id'              => isset( $_REQUEST['quote_id'] ) ? (int) $_REQUEST['quote_id'] : false,
							'website_id'            => isset( $_REQUEST['website_id'] ) ? (int) $_REQUEST['website_id'] : false,
							'status'                => isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : false,
							'pointers'              => isset( $_REQUEST['pointers'] ) ? $_REQUEST['pointers'] : false,
							'description'           => isset( $_REQUEST['description'] ) ? $_REQUEST['description'] : false,
							'file_time'             => time(), // allow UI to set a file time? nah.
							//'file_name' => $file_name,
							//'file_path' => $file_path,
							//'file_url' => $file_url,
						);
						if ( ! isset( $possible_customers[ $file_data['customer_id'] ] ) ) {
							$file_data['customer_id'] = 0;
						}
						if ( ! isset( $possible_jobs[ $file_data['job_id'] ] ) ) {
							$file_data['job_id'] = 0;
						}
						$new_file = true;
					} else {
						// some fields we dont want to overwrite on existing files:
						$file_data = array(
							'file_id'               => $file_id,
							'bucket_parent_file_id' => isset( $_REQUEST['bucket_parent_file_id'] ) ? (int) $_REQUEST['bucket_parent_file_id'] : false,
							//'file_path' => $file_path,
							//'file_name' => $file_name,
							//'file_url' => $file_url,
							'pointers'              => isset( $_REQUEST['pointers'] ) ? $_REQUEST['pointers'] : false,
							'customer_id'           => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false,
							'job_id'                => isset( $_REQUEST['job_id'] ) ? (int) $_REQUEST['job_id'] : false,
							'quote_id'              => isset( $_REQUEST['quote_id'] ) ? (int) $_REQUEST['quote_id'] : false,
							'website_id'            => isset( $_REQUEST['website_id'] ) ? (int) $_REQUEST['website_id'] : false,
							'status'                => isset( $_REQUEST['status'] ) ? $_REQUEST['status'] : false,
							'description'           => isset( $_REQUEST['description'] ) ? $_REQUEST['description'] : false,
						);
						if ( ! isset( $possible_customers[ $file_data['customer_id'] ] ) && $file_data['customer_id'] != $original_file_data['customer_id'] ) {
							$file_data['customer_id'] = $original_file_data['customer_id'];
						}
						if ( $file_data['job_id'] && ! isset( $possible_jobs[ $file_data['job_id'] ] ) && $file_data['job_id'] != $original_file_data['job_id'] ) {
							$file_data['job_id'] = $original_file_data['job_id'];
						}
					}

					$sub_bucket_fields = array( 'customer_id', 'job_id', 'quote_id', 'website_id' );

					if ( $file_data['bucket_parent_file_id'] ) {
						// we're saving a sub bucket file, pull in the file data from the parent file.
						$parent_file      = new ucm_file( $file_data['bucket_parent_file_id'] );
						$parent_file_data = $parent_file->get_data();
						foreach ( $sub_bucket_fields as $sub_bucket_field ) {
							$file_data[ $sub_bucket_field ] = $parent_file_data[ $sub_bucket_field ];
						}
					}

					if ( ! count( $files_to_save ) ) {
						$files_to_save[] = array();
					}

					foreach ( $files_to_save as $id => $file_to_save ) {
						$file_data_to_save               = array_merge( $file_data, $file_to_save );
						$files_to_save[ $id ]['file_id'] = update_insert( 'file_id', $file_data['file_id'], 'file', $file_data_to_save );
						$file_data['file_id']            = 0; // incease we're uploading multiple files
						if ( isset( $_POST['staff_ids_save'] ) && (int) $files_to_save[ $id ]['file_id'] > 0 ) {
							delete_from_db( 'file_user_rel', array( 'file_id' ), array( $files_to_save[ $id ]['file_id'] ) );
							if ( isset( $_POST['staff_ids'] ) && is_array( $_POST['staff_ids'] ) ) {
								foreach ( $_POST['staff_ids'] as $staff_id ) {
									$sql = "REPLACE INTO `" . _DB_PREFIX . "file_user_rel` SET ";
									$sql .= " `user_id` = " . (int) $staff_id;
									$sql .= ", `file_id` = " . (int) $files_to_save[ $id ]['file_id'];
									query( $sql );
								}
							}
						}
						if ( $files_to_save[ $id ]['file_id'] > 0 && isset( $file_data_to_save['bucket'] ) && $file_data_to_save['bucket'] ) {
							// update certain fields of all the child files to match the parent bucket.
							$search    = array(
								'bucket_parent_file_id' => $files_to_save[ $id ]['file_id']
							);
							$sub_files = module_file::get_files( $search );
							$vals      = array();
							foreach ( $sub_bucket_fields as $field ) {
								$vals[ $field ] = isset( $file_data_to_save[ $field ] ) ? $file_data_to_save[ $field ] : false;
							}
							foreach ( $sub_files as $sub_file ) {
								update_insert( 'file_id', $sub_file['file_id'], 'file', $vals );
								// and save the staff assignment manually too
								if ( isset( $_POST['staff_ids_save'] ) && (int) $sub_file['file_id'] > 0 ) {
									delete_from_db( 'file_user_rel', array( 'file_id' ), array( $sub_file['file_id'] ) );
									if ( isset( $_POST['staff_ids'] ) && is_array( $_POST['staff_ids'] ) ) {
										foreach ( $_POST['staff_ids'] as $staff_id ) {
											$sql = "REPLACE INTO `" . _DB_PREFIX . "file_user_rel` SET ";
											$sql .= " `user_id` = " . (int) $staff_id;
											$sql .= ", `file_id` = " . (int) $sub_file['file_id'];
											query( $sql );
										}
									}
								}
							}
						}

						module_extra::save_extras( 'file', 'file_id', $files_to_save[ $id ]['file_id'] );

						if ( $file_changed ) {
							$this->send_file_changed_notice( $files_to_save[ $id ]['file_id'], $new_file );
						}// file changed
					}


				}

				if ( module_file::can_i( 'create', 'File Comments' ) ) {
					$this->save_file_comments( $file_id );
				}


				if ( isset( $_REQUEST['delete_file_comment_id'] ) && $_REQUEST['delete_file_comment_id'] ) {
					$file_comment_id = (int) $_REQUEST['delete_file_comment_id'];
					$comment         = get_single( 'file_comment', 'file_comment_id', $file_comment_id );
					if ( $comment['create_user_id'] == module_security::get_loggedin_id() || module_file::can_i( 'delete', 'File Comments' ) ) {
						$sql = "DELETE FROM `" . _DB_PREFIX . "file_comment` WHERE file_id = '" . (int) $file_id . "' AND file_comment_id = '$file_comment_id' ";
						$sql .= " LIMIT 1";
						query( $sql );
					}
				}
				if ( isset( $_REQUEST['butt_email'] ) && $_REQUEST['butt_email'] && module_file::can_i( 'edit', 'File Approval' ) ) {
					redirect_browser( $this->link_open_email( $file_id ) );
				}
				if ( count( $files_to_save ) ) {
					if ( count( $files_to_save ) > 1 ) {
						$file_id = false;
						set_message( _l( '%s Files saved successfully', count( $files_to_save ) ) );
					} else {
						set_message( _l( 'File saved successfully' ) );
						$file_id = $files_to_save[0]['file_id'];
					}
				}
				redirect_browser( $this->link_open( $file_id ) );
			}
		} else if ( 'delete_file_popup' == $_REQUEST['_process'] ) {
			$file_id = (int) $_REQUEST['file_id'];

			if ( ! $file_id || $file_id == 'new' ) {
				// cant delete a new file.. do nothing.
			} else {
				$file_data = $this->get_file( $file_id );
				if ( true ) { //module_security::can_access_data('file',$file_data,$file_id)){
					// delete the physical file.
					if ( is_file( $file_data['file_path'] ) ) {
						unlink( $file_data['file_path'] );
					}
					// delete the db entry.
					delete_from_db( 'file', 'file_id', $file_id );
					// update ui with changes.
					?>
					<script type="text/javascript">
              var new_html = '';
              parent.new_file_added<?php echo $file_data['owner_table'];?>_<?php echo $file_data['owner_id'];?>(<?php echo $file_id;?>, '<?php echo $file_data['owner_table'];?>',<?php echo $file_data['owner_id'];?>, new_html);
					</script>
					<?php
				}
			}
			exit;
		}

	}

	function send_file_changed_notice( $file_id, $new_file = false, $send_to_admin = false ) {

		$file_data = $this->get_file( $file_id );
		// do we schedule an alert for this file upload?
		if ( module_security::can_user( module_security::get_loggedin_id(), _FILE_UPLOAD_ALERT_STRING ) ) {
			// the current user is one who receives file alerts.
			// so for now we don't schedule this alert.
			// hmm - this might not work with a team environment, we'll send alerts no matter what :)
		}
		// if there are assigned staff members then only those members will receive notifications.
		$alert_users = module_user::get_users_by_permission(
			array(
				'category' => _LABEL_USER_SPECIFIC,
				'name'     => _FILE_UPLOAD_ALERT_STRING,
				'module'   => 'config',
				'view'     => 1,
			)
		);
		if ( count( $file_data['staff_ids'] ) > 0 && ! ( count( $file_data['staff_ids'] ) == 1 && $file_data['staff_ids'][0] == 0 ) ) {
			foreach ( $alert_users as $user_id => $alert_user ) {
				if ( ! in_array( $user_id, $file_data['staff_ids'] ) ) {
					// this user has permissions to receive alerts, but they're not assigned.
					unset( $alert_users[ $user_id ] );
				}
			}
		} else {
			if ( ! $send_to_admin && isset( $alert_users[1] ) ) {
				unset( $alert_users[1] ); // skip admin for now until we can control that option
			}
		}
		// dont set a notification to ourselves.
		if ( isset( $alert_users[ module_security::get_loggedin_id() ] ) ) {
			unset( $alert_users[ module_security::get_loggedin_id() ] );
		}
		$file_data['customer_name'] = '';
		$file_data['customer_link'] = '';
		if ( isset( $file_data['customer_id'] ) && $file_data['customer_id'] ) {
			$customer_data              = module_customer::get_customer( $file_data['customer_id'] );
			$file_data['customer_name'] = $customer_data['customer_name'];
			$file_data['customer_link'] = module_customer::link_open( $file_data['customer_id'] );
		}
		$file_data['file_link'] = self::link_open( $file_id );
		foreach ( $alert_users as $alert_user ) {
			// check if this user has access to this file.
			if ( ! $alert_user['user_id'] ) {
				continue;
			}
			if ( is_callable( 'module_security::user_id_temp_set' ) ) {
				module_security::user_id_temp_set( $alert_user['user_id'] );
				$file_test = self::get_file( $file_id );
				module_security::user_id_temp_restore();
				if ( ! $file_test || $file_test['file_id'] != $file_id ) {
					//echo 'user '.$alert_user['user_id'].' has no permissions <br>';
					continue; // no permissions
				}
			}

			if ( isset( $alert_user['customer_id'] ) && $alert_user['customer_id'] > 0 ) {
				// only send this user an alert of the file is from this customer account.
				if ( ! isset( $file_data['customer_id'] ) || $file_data['customer_id'] != $alert_user['customer_id'] ) {
					//echo 'skipping '.$alert_user['user_id'].' - no customer match <br>';continue;
					continue; // skip this user
				}
			}
			//echo 'sending to '.$alert_user['user_id'].'<br>';continue;
			$notification_data = array(
				'email_id'          => 0,
				'view_time'         => 0,
				'notification_type' => $new_file ? _FILE_NOTIFICATION_TYPE_UPLOADED : _FILE_NOTIFICATION_TYPE_UPDATED,
				'file_id'           => $file_id,
				'user_id'           => $alert_user['user_id'],
			);

			$template = module_template::get_template_by_key( 'file_upload_alert_email' );
			$template->assign_values( $file_data );
			$html = $template->render( 'html' );
			// send an email to this user.
			$email                 = module_email::new_email();
			$email->file_id        = $file_id;
			$email->replace_values = $file_data;
			$email->set_to( 'user', $alert_user['user_id'] );
			$email->set_from( 'user', module_security::get_loggedin_id() );
			$email->set_subject( $template->description );
			// do we send images inline?
			$email->set_html( $html );

			if ( $email->send() ) {
				// it worked successfully!!
				// sweet.
				$notification_data['email_id'] = $email->email_id;
			} else {
				/// log err?
				set_error( 'Failed to send notification email to user id ' . $alert_user['user_id'] );
			}

			update_insert( 'file_notification_id', 'new', 'file_notification', $notification_data );
		}
	}

	function save_file_comments( $file_id ) {
		if ( isset( $_REQUEST['new_comment_text'] ) && strlen( $_REQUEST['new_comment_text'] ) ) {
			$file_data = $this->get_file( $file_id );
			$item_data = array(
				"file_id"        => $file_id,
				"create_user_id" => module_security::get_loggedin_id(),
				"comment"        => $_REQUEST['new_comment_text'],
			);
			update_insert( "file_comment_id", "new", "file_comment", $item_data );

			$file_data['comment'] = $_REQUEST['new_comment_text'];


			// do we schedule an alert for this file upload?
			if ( module_security::can_user( module_security::get_loggedin_id(), _FILE_COMMENT_ALERT_STRING ) ) {
				// the current user is one who receives file alerts.
				// so for now we don't schedule this alert.
				// hmm - this might not work with a team environment, we'll send alerts no matter what :)
			}
			$alert_users = module_user::get_users_by_permission(
				array(
					'category' => _LABEL_USER_SPECIFIC,
					'name'     => _FILE_COMMENT_ALERT_STRING,
					'module'   => 'config',
					'view'     => 1,
				)
			);

			if ( count( $file_data['staff_ids'] ) ) {
				foreach ( $alert_users as $user_id => $alert_user ) {
					if ( ! in_array( $user_id, $file_data['staff_ids'] ) ) {
						// this user has permissions to receive alerts, but they're not assigned.
						unset( $alert_users[ $user_id ] );
					}
				}
			} else {
				if ( isset( $alert_users[1] ) ) {
					unset( $alert_users[1] ); // skip admin for now until we can control that option
				}
			}
			// dont set a notification to ourselves.
			if ( isset( $alert_users[ module_security::get_loggedin_id() ] ) ) {
				unset( $alert_users[ module_security::get_loggedin_id() ] );
			}
			$file_data['customer_name'] = '';
			$file_data['customer_link'] = '';
			if ( isset( $file_data['customer_id'] ) && $file_data['customer_id'] ) {
				$customer_data              = module_customer::get_customer( $file_data['customer_id'] );
				$file_data['customer_name'] = $customer_data['customer_name'];
				$file_data['customer_link'] = module_customer::link_open( $file_data['customer_id'] );
			}
			$file_data['file_link'] = self::link_open( $file_id );
			foreach ( $alert_users as $alert_user ) {

				if ( isset( $alert_user['customer_id'] ) && $alert_user['customer_id'] > 0 ) {
					// only send this user an alert of the file is from this customer account.
					if ( ! isset( $file_data['customer_id'] ) || $file_data['customer_id'] != $alert_user['customer_id'] ) {
						continue; // skip this user
					}
				}

				$notification_data = array(
					'email_id'          => 0,
					'view_time'         => 0,
					'notification_type' => _FILE_NOTIFICATION_TYPE_COMMENTED,
					'file_id'           => $file_id,
					'user_id'           => $alert_user['user_id'],
				);

				$template = module_template::get_template_by_key( 'file_comment_alert_email' );
				$template->assign_values( $file_data );
				$html = $template->render( 'html' );
				// send an email to this user.
				$email                 = module_email::new_email();
				$email->file_id        = $file_id;
				$email->replace_values = $file_data;
				$email->set_to( 'user', $alert_user['user_id'] );
				$email->set_from( 'user', module_security::get_loggedin_id() );
				$email->set_subject( $template->description );
				// do we send images inline?
				$email->set_html( $html );

				if ( $email->send() ) {
					// it worked successfully!!
					// sweet.
					$notification_data['email_id'] = $email->email_id;
				} else {
					/// log err?
					set_error( 'Failed to send notification email to user id ' . $alert_users['user_id'] );
				}

				update_insert( 'file_notification_id', 'new', 'file_notification', $notification_data );
			}
		}
	}

	public static function get_file_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'File Data Access', array(
				_FILE_ACCESS_ALL,
				_FILE_ACCESS_CUSTOMERS,
				_FILE_ACCESS_JOBS,
				_FILE_ACCESS_ME,
				_FILE_ACCESS_ASSIGNED,
			) );
		} else {
			return true;
		}
	}

	public static function get_file_comments( $file_id ) {
		$search = array( "file_id" => $file_id );
		$items  = get_multiple( "file_comment", $search, 'file_comment_id', 'exact', 'date_created DESC' );

		return $items;
	}

	function delete( $file_id ) {
		$file_id = (int) $file_id;
		$sql     = "DELETE FROM " . _DB_PREFIX . "file WHERE file_id = $file_id LIMIT 1";
		$res     = query( $sql );
	}

	public static function get_file( $file_id, $perms = true ) {
		$file = new ucm_file( $file_id );
		$file->do_permission_check( $perms );

		return $file->get_data();
	}

	public static function get_files( $search = false, $skip_permissions = false ) {

		// build up a custom search sql query based on the provided search fields
		$sql  = "SELECT f.* ";
		$from = " FROM `" . _DB_PREFIX . "file` f ";
		if ( class_exists( 'module_customer', false ) ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "customer` c USING (customer_id)";
		}
		$where = " WHERE 1 ";
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " f.file_name LIKE '%$str%' ";
			//$where .= "OR  u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
		/*if(isset($search['job']) && $search['job']){
            $str = db_escape($search['job']);
            $from .= " LEFT JOIN `"._DB_PREFIX."job` j USING (job_id)";
            $where .= " AND ( ";
            $where .= " j.name LIKE '%$str%' ";
            $where .= ' ) ';
        }*/
		// tricky job searching, by name or by job id.
		// but we don't want to restrict it to customer if they are searching for a job.
		/*
         * this is the logic we have to follow:
         *
        $customer_access = module_customer::get_customer($file['customer_id']);
        $job_access = module_job::get_job($file['job_id']);
        if(
            ($customer_access && $customer_access['customer_id'] == $file['customer_id']) ||
            ($job_access && $job_access['job_id'] == $file['job_id'])
        ){
         */
		foreach ( array( 'file_id', 'owner_id', 'owner_table', 'status', 'bucket_parent_file_id' ) as $key ) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND f.`$key` = '$str'";
			}
		}

		if ( isset( $search['extra_fields'] ) && is_array( $search['extra_fields'] ) && class_exists( 'module_extra', false ) ) {
			$extra_fields = array();
			foreach ( $search['extra_fields'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$extra_fields[ $key ] = trim( $val );
				}
			}
			if ( count( $extra_fields ) ) {
				$from  .= " LEFT JOIN `" . _DB_PREFIX . "extra` ext ON (ext.owner_id = f.file_id)"; //AND ext.owner_table = 'customer'
				$where .= " AND (ext.owner_table = 'file' AND ( ";
				foreach ( $extra_fields as $key => $val ) {
					$val   = db_escape( $val );
					$key   = db_escape( $key );
					$where .= "( ext.`extra` LIKE '%$val%' AND ext.`extra_key` = '$key') OR ";
				}
				$where = rtrim( $where, ' OR' );
				$where .= ' ) )';
			}
		}

		// permissions from customer module.
		// tie in with customer permissions to only get jobs from customers we can access.
		if ( ! $skip_permissions ) {
			switch ( self::get_file_data_access() ) {
				case _FILE_ACCESS_ALL:
					// all files, no limits on SQL here
					break;
				case _FILE_ACCESS_JOBS:
					$jobs  = module_job::get_jobs( array(), array(
						'columns' => 'u.job_id AS job_id',
					) );
					$where .= " AND f.job_id IN ( ";
					if ( count( $jobs ) ) {
						foreach ( $jobs as $valid_job_id ) {
							$where .= (int) $valid_job_id['job_id'] . ',';
						}
						$where = rtrim( $where, ',' );
					} else {
						$where .= ' -1 ';
					}
					$where .= ' ) ';
					break;
				case _FILE_ACCESS_ME:
					$where .= " AND f.create_user_id = " . (int) module_security::get_loggedin_id();
					break;
				case _FILE_ACCESS_ASSIGNED:
					$from  .= " LEFT JOIN `" . _DB_PREFIX . "file_user_rel` cur ON f.file_id = cur.file_id";
					$where .= " AND (cur.user_id = " . (int) module_security::get_loggedin_id() . ")";
					break;
				case _FILE_ACCESS_CUSTOMERS:
				default:
					if ( class_exists( 'module_customer', false ) ) { //added for compat in newsletter system that doesn't have customer module
						switch ( module_customer::get_customer_data_access() ) {
							case _CUSTOMER_ACCESS_ALL:
								// all customers! so this means all files!
								break;
							case _CUSTOMER_ACCESS_ALL_COMPANY:
							case _CUSTOMER_ACCESS_CONTACTS:
							case _CUSTOMER_ACCESS_TASKS:
							case _CUSTOMER_ACCESS_STAFF:
								$valid_customer_ids = module_security::get_customer_restrictions();
								if ( count( $valid_customer_ids ) ) {
									$where .= " AND ( ";
									foreach ( $valid_customer_ids as $valid_customer_id ) {
										if ( isset( $search['owner_table'] ) ) {
											$where .= " (f.owner_table = 'customer' AND f.owner_id = '" . (int) $valid_customer_id . "') OR ";
										} else {
											$where .= " (f.customer_id = '" . (int) $valid_customer_id . "') OR ";
											if ( isset( $search['customer_id'] ) && $search['customer_id'] && $search['customer_id'] == $valid_customer_id ) {
												unset( $search['customer_id'] );
											}
										}
									}
									$where = rtrim( $where, 'OR ' );
									$where .= ' ) ';
								}
								break;

						}
					}
			}// file data access switch
		}


		if ( class_exists( 'module_job', false ) ) {
			if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
				// check if we have permissions to view this job.
				$job = module_job::get_job( $search['job_id'] );
				if ( ! $job || $job['job_id'] != $search['job_id'] ) {
					$search['job_id'] = false;
				}
			}
		}
		if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
			$where .= " AND f.job_id = " . (int) $search['job_id'];
		} else if ( isset( $search['quote_id'] ) && (int) $search['quote_id'] > 0 ) {
			$where .= " AND f.quote_id = " . (int) $search['quote_id'];
		} else if ( isset( $search['customer_id'] ) && (int) $search['customer_id'] ) {
			$where .= " AND f.customer_id = " . (int) $search['customer_id'];
		}


		$group_order = ' GROUP BY f.file_id ORDER BY f.file_name'; // stop when multiple company sites have same region
		$sql         = $sql . $from . $where . $group_order;
		//echo $sql;
		$result = qa( $sql );

		//module_security::filter_data_set("invoice",$result);
		return $result;
		//return get_multiple("file",$search,"file_id","exact","file_id");
	}


	public static function format_bytes( $size ) {
		$units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
		for ( $i = 0; $size >= 1024 && $i < 4; $i ++ ) {
			$size /= 1024;
		}

		return round( $size, 2 ) . $units[ $i ];
	}


	public static function get_statuses() {

		$sql      = "SELECT `status` FROM `" . _DB_PREFIX . "file` GROUP BY `status` ORDER BY `status`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['status'] ] = $r['status'];
		}

		return $statuses;
	}


	public static function link_public_view( $file_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash ' . _UCM_SECRET . ' ' . $file_id );
		}
		if ( _REWRITE_LINKS ) {
			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.file/h.download/i.' . $file_id . '/hash.' . self::link_public_view( $file_id, true ) );
		} else {
			return full_link( _EXTERNAL_TUNNEL . '?m=file&h=download&i=' . $file_id . '&hash=' . self::link_public_view( $file_id, true ) );
		}
	}

	public static function link_public_download_bucket( $file_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash ' . _UCM_SECRET . ' ' . $file_id );
		}
		if ( _REWRITE_LINKS ) {
			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.file/h.download_bucket/i.' . $file_id . '/hash.' . self::link_public_download_bucket( $file_id, true ) );
		} else {
			return full_link( _EXTERNAL_TUNNEL . '?m=file&h=download_bucket&i=' . $file_id . '&hash=' . self::link_public_download_bucket( $file_id, true ) );
		}
	}

	public static function link_public( $file_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash files ' . _UCM_SECRET . ' ' . $file_id );
		}
		if ( _REWRITE_LINKS ) {
			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.file/h.view/i.' . $file_id . '/hash.' . self::link_public( $file_id, true ) );
		} else {
			return full_link( _EXTERNAL_TUNNEL . '?m=file&h=view&i=' . $file_id . '&hash=' . self::link_public( $file_id, true ) );
		}
	}

	public static function generate_preview( $file_id, $file_name, $file_data ) {
		ob_start();
		if ( $file_data['file_name'] && preg_match( '/\.(\w\w\w\w?)$/', $file_name, $matches ) ) {
			$show_output = false;

			$file_extension = strtolower( $matches[1] );
			if ( ! module_config::c( 'file_enable_preview', 1 ) ) {
				$file_extension = 'download';
			}
			switch ( $file_extension ) {
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
					$show_output = true;
					?>
					<div style="width:100%; overflow:auto;" id="file_preview">
						<div id="pointer_template"
						     style="padding:3px; background-color:#EFEFEF; border:1px solid #CCC; position:absolute; display:none;">
							#<span class="pointer_id">0</span>
						</div>
						<div id="pointer_holder" style="position: relative; float: left; height: 0pt;"></div>

						<img src="<?php echo module_file::link_public_view( $file_id ); ?>"
						     alt="<?php _e( 'Click to add a comment' ); ?>" style="cursor:pointer;"><!-- style="max-width: 100%" -->
					</div>
					<?php if ( ! module_security::is_logged_in() || module_file::can_i( 'create', 'File Comments' ) ) { ?>
					<input type="hidden" name="pointers" id="pointer_save" value="<?php echo $file_data['pointers']; ?>"
					       class="no_permissions">
				<?php } ?>

					<style type="text/css">
						.pointer-ids {
							background-color: #EFEFEF;
							border: 1px solid #CCC;
							cursor: pointer;
						}
					</style>
					<script language="javascript">
              var pointers = new Array();
              var pointer_id;
							<?php
							$pointers = explode( '|', $file_data['pointers'] );
							if ( ! is_array( $pointers ) ) {
								$pointers = array();
							}
							$pointer_id = 0;
							foreach($pointers as $pointer){
							if ( ! trim( $pointer ) ) {
								continue;
							}
							$p = explode( ",", $pointer );
							$pointer_id = max( $p[0], $pointer_id );
							?>
              pointers.push({
                  id:<?php echo $p[0]; ?>,
                  x:<?php echo $p[1]; ?>,
                  y:<?php echo $p[2]; ?>,
                  printed: false
              });
							<?php
							}
							?>
              pointer_id = <?php echo $pointer_id; ?>;
              var add_pointer = true;

              function print_pointers() {
                  var pointer_save = '';
                  for (var i in pointers) {
                      pointer_save += pointers[i].id + ',' + pointers[i].x + ',' + pointers[i].y + '|';
                      if (pointers[i].printed) continue;
                      pointers[i].dom = $('#pointer_template').clone(true).attr('id', '').show();
                      $('.pointer_id', pointers[i].dom).html(pointers[i].id);
                      $(pointers[i].dom).css('marginTop', pointers[i].y + 'px');
                      $(pointers[i].dom).css('marginLeft', pointers[i].x + 'px');
                      $(pointers[i].dom).attr('node_id', pointers[i].id);
                      $(pointers[i].dom).data('id', i);
                      $(pointers[i].dom).attr('class', 'pointer-ids pointer-id-' + pointers[i].id);
                      $(pointers[i].dom).click(function () {
                          add_pointer = false;
                          var id = $(this).data('id');
                          var node_id = $(this).attr('node_id');
                          if (confirm('Really remove pointer ' + node_id + '?')) {
                              $(pointers[id].dom).remove();
                              delete(pointers[id]);
                              print_pointers();
                          }
                      });
                      $('#pointer_holder').prepend(pointers[i].dom);
                      pointers[i].printed = true;
                      pointer_hover_node(pointers[i].dom);
                  }
                  $('#pointer_save').val(pointer_save);
              }

              function pointer_hover_node(node) {
                  $(node).hover(
                      function () {
                          var id = $(node).attr('node_id');
                          $('.pointer-id-' + id).css('backgroundColor', '#FFC707');
                      },
                      function () {
                          var id = $(node).attr('node_id');
                          $('.pointer-id-' + id).css('backgroundColor', '#EFEFEF');
                      }
                  );
              }

              $(document).ready(function () {
								<?php if(module_file::can_i( 'create', 'File Comments' )){ ?>
                  $("#file_preview").click(function (e) {
                      if (!add_pointer) {
                          add_pointer = true;
                          return;
                      }
                      pointer_id++;
                      var offset = $(this).offset();
                      pointers.push({
                          id: pointer_id,
                          x: (e.pageX - offset.left) + this.scrollLeft,
                          y: e.pageY - offset.top,
                          printed: false
                      });
                      print_pointers();
                  });
								<?php } ?>
                  print_pointers();
                  $('#file_notes .pointer-ids').each(function () {
                      pointer_hover_node(this);
                  });
              });

					</script>

					<?php
					break;
				case 'pdf':
					$embed_url = module_file::link_public_view( $file_id );
					$embed_url  .= strpos( $embed_url, '?' ) === false ? '?' : '&';
					$embed_url  .= 'embed=true';
					?>
					<iframe src="//docs.google.com/gview?url=<?php echo urlencode( $embed_url ); ?>&embedded=true"
					        style="width:100%; height:900px;" frameborder="0"></iframe>
					<!-- <object width="100%" height="900" type="application/pdf" data="<?php echo module_file::link_public_view( $file_id ); ?>" id="pdf_content">
                        <a href="<?php echo module_file::link_public_view( $file_id ); ?>">Download File</a>

                      </object> -->
					<a href="<?php echo module_file::link_public_view( $file_id ); ?>">Download PDF File</a>
					<?php
					break;
				default:
					// file download link
					?>
					<div style="text-align:center; padding:20px;">
						<a href="<?php echo module_file::link_public_view( $file_id ); ?>">Download File</a>
					</div>
				<?php
			}
		}

		return ob_get_clean();
	}

	public static function email_sent( $args ) {
		if ( $args && is_array( $args ) && isset( $args['file_id'] ) && $args['file_id'] ) {
			update_insert( 'file_id', $args['file_id'], 'file', array( 'approved_time' => - 1 ) );
		}
	}

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'view':
				$file_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $file_id && $hash ) {
					$correct_hash = $this->link_public( $file_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$file_data = $this->get_file( $file_id, false );

						if ( $file_data && $file_data['file_id'] == $file_id ) {
							if ( isset( $_POST['save_file_comments'] ) ) {
								if ( isset( $_POST['file_approve'] ) && isset( $_POST['file_approve_go'] ) && isset( $_POST['file_approve_name'] ) && strlen( $_POST['file_approve_name'] ) > 0 ) {
									update_insert( 'file_id', $file_id, 'file', array(
										'approved_time' => time(),
										'approved_by'   => $_POST['file_approve_name'],
									) );
									// send email, same 'updated' email as before.
									$this->send_file_changed_notice( $file_id, false, true );
									//redirect_browser($this->link_public($file_id));
									$_REQUEST['new_comment_text'] = _l( 'File was approved at %s by %s', print_date( time(), true ), htmlspecialchars( $_POST['file_approve_name'] ) );
								}
								if ( isset( $_POST['pointers'] ) ) {
									update_insert( 'file_id', $file_id, 'file', array( 'pointers' => $_POST['pointers'] ) );
								}
								$this->save_file_comments( $file_id );
								redirect_browser( $this->link_public( $file_id ) );
							}


							module_template::init_template( 'file_approval_view', '<h2>File Details</h2>
    File Name: <strong>{FILE_NAME}</strong> <br/>
    Download: <strong><a href="{FILE_DOWNLOAD_URL}">Click Here</a></strong> <br/>
    Status: <strong>{STATUS}</strong> <br/>
    Customer: <strong>{CUSTOMER_NAME}</strong> <br/>
    {if:JOB_NAME}Job: <strong>{JOB_NAME}</strong> <br/>{endif:JOB_NAME}
    {if:FILE_APPROVAL_PENDING}
    <h2>File Approval Pending</h2>
    <p>If you would like to approve this file please complete the form below:</p>
    <p>Your Name: <input type="text" name="file_approve_name"> </p>
    <p><input type="checkbox" name="file_approve_go" value="yes"> Yes, I approve this file. </p>
    <p><input type="submit" name="file_approve" value="Approve File" class="submit_button save_button"></p>
    {endif:FILE_APPROVAL_PENDING}
    {if:FILE_APPROVED}
    <h2>File Has Been Approved</h2>
    <p>Thank you, the file was approved by <strong>{APPROVED_BY}</strong> on <strong>{APPROVED_TIME}</strong>.</p>
    {endif:FILE_APPROVED}
    <h2>File Comments</h2>
    <p>Please feel free to add comments to this file using the form below.</p>
    {FILE_COMMENTS}
    {if:FILE_PREVIEW}
    <h2>File Preview</h2>
    <div style="overflow:scroll;">{FILE_PREVIEW}</div>
    {endif:FILE_PREVIEW}
    ', 'Used when displaying the file to a customer for approval.', 'code' );
							$template = module_template::get_template_by_key( 'file_approval_view' );
							// generate the html for the task output
							$job_data = $file_data['job_id'] ? module_job::get_replace_fields( $file_data['job_id'] ) : array();
							if ( class_exists( 'module_quote', false ) ) {
								$quote_data = $file_data['quote_id'] ? module_quote::get_replace_fields( $file_data['quote_id'] ) : array();
							}
							$customer_data                  = $file_data['customer_id'] ? module_customer::get_replace_fields( $file_data['customer_id'] ) : array();
							$file_data['file_preview']      = module_file::generate_preview( $file_id, $file_data['file_name'], $file_data );
							$file_data['FILE_DOWNLOAD_URL'] = module_file::link_public_view( $file_id );
							if ( isset( $file_data['approved_time'] ) ) {
								switch ( $file_data['approved_time'] ) {
									case - 1:
										$file_data['FILE_APPROVAL_PENDING'] = 1;
										break;
									case 0:
										break;
									default:
										$file_data['FILE_APPROVED'] = 1;
										$file_data['APPROVED_TIME'] = print_date( $file_data['approved_time'], true );
								}
							}

							if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
								$all_extra_fields = module_extra::get_defaults( 'file' );
								foreach ( $all_extra_fields as $e ) {
									$file_data[ $e['key'] ] = _l( 'N/A' );
								}
								// and find the ones with values:
								$extras = module_extra::get_extras( array( 'owner_table' => 'file', 'owner_id' => $file_id ) );
								foreach ( $extras as $e ) {
									$file_data[ $e['extra_key'] ] = $e['extra'];
								}
							}

							ob_start();
							?>
							<div id="file_notes">
								<div style="border-top:1px dashed #CCCCCC; padding:3px; margin:3px 0;">
									<textarea name="new_comment_text" style="width:100%;" class="no_permissions"></textarea>
									<div style="text-align: right;">
										<input type="submit" name="butt_save_note" id="butt_save_note"
										       value="<?php echo _l( 'Add Comment' ); ?>" class="submit_button no_permissions">
									</div>
								</div>
								<?php
								foreach ( module_file::get_file_comments( $file_id ) as $item ) {
									$note_text = forum_text( $item['comment'] );
									if ( preg_match_all( '/#(\d+)/', $note_text, $matches ) ) {
										//
										foreach ( $matches[1] as $digit ) {
											$note_text = preg_replace( '/#' . $digit . '([^\d]*)/', '<span node_id=' . $digit . ' class="pointer-ids pointer-id-' . $digit . '">#' . $digit . '</span>$1', $note_text );
										}
									}
									?>
									<div style="border-top:1px dashed #CCCCCC; padding:3px; margin:3px 0;">
										<?php echo $note_text; ?>
										<div style="font-size:10px; text-align:right; color:#CCCCCC;">
											From <?php echo $item['create_user_id'] ? module_user::link_open( $item['create_user_id'], true ) : _l( 'Customer' ); ?>
											on <?php echo print_date( $item['date_created'], true ); ?>
										</div>
									</div>

									<?php
								}
								?>
							</div>
							<?php
							$file_data['file_comments'] = ob_get_clean();
							$template->assign_values( $file_data );
							$template->assign_values( $customer_data );
							$template->assign_values( $job_data );
							if ( class_exists( 'module_quote', false ) ) {
								$quote_data['quote_approved_by']   = $quote_data['approved_by'];
								$quote_data['quote_date_approved'] = $quote_data['date_approved'];
								unset( $quote_data['approved_by'] );
								unset( $quote_data['date_approved'] );
								$template->assign_values( $quote_data );
							}
							$template->page_title = $file_data['file_name'];
							$template->content    = '<form action="" method="post"><input type="hidden" name="save_file_comments" value="1">' . $template->content . '</form>';
							echo $template->render( 'pretty_html' );
						}

					}
				}
				break;
			case 'download_bucket':
				@ob_end_clean();
				$file_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash    = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $file_id && $hash ) {
					$correct_hash = $this->link_public_download_bucket( $file_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$file_data = $this->get_file( $file_id, false );

						@ignore_user_abort( true );

						$search                          = array();
						$search['bucket_parent_file_id'] = $file_id;
						$files                           = module_file::get_files( $search );

						//Create ZIP
						$zip     = new ZipArchive();
						$zipName = "bucket-" . $file_id . "-" . md5( $file_id . _UCM_SECRET ) . ".zip";

						if ( $zip->open( _FILE_UPLOAD_PATH . $zipName, ZIPARCHIVE::CREATE ) !== true ) {
							echo 'Failed to create bucket zip file';
							exit;
						}
						foreach ( $files as $file ) {
							if ( is_file( $file['file_path'] ) ) {
								$zip->addFromString( $file['file_name'], file_get_contents( $file['file_path'] ) );
							}
						}

						$zip->close();

						//Set headers
						header( "Pragma: public" );
						header( "Expires: 0" );
						header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
						header( "Cache-Control: public" );
						header( "Content-Description: File Transfer" );
						header( "Content-type: application/octet-stream" );
						//header("Content-Disposition: attachment; filename='" . $zipName . "'");
						header( "Content-Disposition: attachment; filename=\"" . preg_replace( "#[^a-zA-Z0-9]+#", "-", $file_data['file_name'] ) . ".zip\";" );
						header( "Content-Transfer-Encoding: binary" );
						header( "Content-Length: " . filesize( _FILE_UPLOAD_PATH . $zipName ) );

						@clearstatcache(); //Make sure the file size isn't cached
						$size = @readfile( _FILE_UPLOAD_PATH . $zipName );
						if ( ! $size ) {
							echo file_get_contents( _FILE_UPLOAD_PATH . $zipName );
						}
						@unlink( _FILE_UPLOAD_PATH . $zipName );
					}
				}
				exit;
				break;
			case 'download':
				@ob_end_clean();
				$file_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash    = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $file_id && $hash ) {
					$correct_hash = $this->link_public_view( $file_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$file_data = $this->get_file( $file_id, false );
						if ( isset( $file_data['file_url'] ) && strlen( $file_data['file_url'] ) ) {
							redirect_browser( $file_data['file_url'] );
						} else if ( is_file( $file_data['file_path'] ) ) {
							header( "Pragma: public" );
							header( "Expires: 0" );
							header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
							header( "Cache-Control: private", false );
							header( "Content-type: " . dtbaker_mime_type( $file_data['file_name'], $file_data['file_path'] ) );
							if ( ! isset( $_REQUEST['embed'] ) ) {
								header( "Content-Disposition: attachment; filename=\"" . $file_data['file_name'] . "\";" );
								header( "Content-Transfer-Encoding: binary" );
							}
							header( "Content-Length: " . filesize( $file_data['file_path'] ) );
							//readfile($file_data['file_path']);
							$size = @readfile( $file_data['file_path'] );
							if ( ! $size ) {
								echo file_get_contents( $file_data['file_path'] );
							}
						} else {
							echo 'Not found';
						}
					}
				}
				exit;
				break;
		}
	}

	public static function customer_id_changed( $old_customer_id, $new_customer_id ) {
		$old_customer_id = (int) $old_customer_id;
		$new_customer_id = (int) $new_customer_id;
		if ( $old_customer_id > 0 && $new_customer_id > 0 ) {
			$sql = "UPDATE `" . _DB_PREFIX . "file` SET customer_id = " . $new_customer_id . " WHERE customer_id = " . $old_customer_id;
			query( $sql );
		}
	}

	public static function delete_files( $owner_table, $owner_id ) {
		$files = get_multiple( 'file', array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
		foreach ( $files as $file ) {
			$ucm_file = new ucm_file( $file['file_id'] );
			$ucm_file->delete();
		}
	}

	public static function bulk_handle_delete() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['delete'] ) && $_REQUEST['bulk_action']['delete'] == 'yes' && self::can_i( 'delete', 'Files' ) ) {
			// confirm deletion of these files:
			$file_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $file_ids as $file_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $file_ids[ $file_id ] );
				} else {
					$ucm_file = new ucm_file( $file_id );
					if ( ! $ucm_file->can_i_access() ) {
						unset( $file_ids[ $file_id ] );
					} else {
						$file_data            = $ucm_file->get_data();
						$file_ids[ $file_id ] = $file_data['file_name'];
					}
				}
			}
			if ( count( $file_ids ) > 0 ) {
				if ( module_form::confirm_delete( 'file_id', _l( "Really delete files: %s", implode( ', ', $file_ids ) ), self::link_open( false ) ) ) {
					foreach ( $file_ids as $file_id => $file_number ) {
						$ucm_file = new ucm_file( $file_id );
						$ucm_file->delete();
					}
					set_message( _l( "%s files deleted successfully", count( $file_ids ) ) );
					redirect_browser( self::link_open( false ) );
				}
			}
		}
	}

	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'file' );
		if ( ! empty( $fields['file_path']['dbtype'] ) && $fields['file_path']['dbtype'] == 'varchar(100)' ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` MODIFY `file_path` varchar(255);';
		}
		if ( ! isset( $fields['pointers'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `pointers` varchar(255) NULL;';
		}
		if ( ! isset( $fields['file_url'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `file_url` varchar(255) NOT NULL DEFAULT \'\';';
		}
		if ( ! isset( $fields['approved_time'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `approved_time` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['approved_by'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `approved_by` varchar(255) NOT NULL DEFAULT \'\';';
		}
		if ( ! isset( $fields['quote_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `quote_id` int(11) NOT NULL DEFAULT \'0\';';
		}
		if ( ! isset( $fields['bucket'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `bucket` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `status`;';
		}
		if ( ! isset( $fields['bucket_parent_file_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'file` ADD `bucket_parent_file_id` int(11) NOT NULL DEFAULT \'0\' AFTER `bucket`;';
		} else {
			self::add_table_index( 'file', 'bucket_parent_file_id' );
		}

		if ( ! self::db_table_exists( 'file_comment' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'file_comment` (
  `file_comment_id` int(11) NOT NULL auto_increment,
    `file_id` int(11) NOT NULL,
    `comment` text NOT NULL,
    `date_created` datetime NOT NULL,
    `date_updated` datetime NOT NULL,
    `create_user_id` int(11) NOT NULL,
    PRIMARY KEY  (`file_comment_id`)
    )  ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		}
		if ( ! self::db_table_exists( 'file_notification' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'file_notification` (
    `file_notification_id` int(11) NOT NULL auto_increment,
    `file_id` int(11) NOT NULL DEFAULT \'0\',
    `user_id` int(11) NOT NULL DEFAULT \'0\',
    `email_id` int(11) NOT NULL DEFAULT \'0\',
    `view_time` int(11) NOT NULL DEFAULT \'0\',
    `notification_type` int(11) NOT NULL DEFAULT \'0\',
    `date_created` datetime NOT NULL,
    `date_updated` datetime NOT NULL,
    `create_user_id` int(11) NOT NULL,
    PRIMARY KEY  (`file_notification_id`),
    INDEX (  `file_id` ,  `user_id` ,  `view_time` )
    )  ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
		}

		if ( ! self::db_table_exists( 'file_user_rel' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'file_user_rel` (
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY  (`file_id`, `user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>file` (
		`file_id` int(11) NOT NULL AUTO_INCREMENT,
		`customer_id` int(11) NULL,
		`job_id` int(11) NULL,
		`quote_id` int(11) NULL,
		`owner_id` int(11) NULL,
		`owner_table` varchar(80) NULL,
		`file_path` varchar(255) NULL,
		`file_url` varchar(255) NOT NULL DEFAULT '',
		`file_name` varchar(100) NULL,
		`file_time` int(11) NULL,
		`status` varchar(100) NULL,
		`bucket` tinyint(1) NOT NULL DEFAULT '0',
		`bucket_parent_file_id` int(11) NOT NULL DEFAULT '0',
		`approved_time` int(11) NOT NULL DEFAULT '0',
		`approved_by` varchar(255) NOT NULL DEFAULT '0',
		`pointers` varchar(255) NULL,
		`description` TEXT NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`create_ip_address` varchar(15) NOT NULL,
		`update_ip_address` varchar(15) NULL,
		PRIMARY KEY (`file_id`),
		KEY `group_id` (`owner_id`),
		KEY `group_key` (`owner_table`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>file_notification` (
		`file_notification_id` int(11) NOT NULL auto_increment,
		`file_id` int(11) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`email_id` int(11) NOT NULL DEFAULT '0',
		`view_time` int(11) NOT NULL DEFAULT '0',
		`notification_type` int(11) NOT NULL DEFAULT '0',
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`create_user_id` int(11) NOT NULL,
		PRIMARY KEY  (`file_notification_id`),
		INDEX (  `file_id` ,  `user_id` ,  `view_time` )
		)  ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>file_comment` (
		`file_comment_id` int(11) NOT NULL auto_increment,
		`file_id` int(11) NOT NULL,
		`comment` text NOT NULL,
		`date_created` datetime NOT NULL,
		`date_updated` datetime NOT NULL,
		`create_user_id` int(11) NOT NULL,
		PRIMARY KEY  (`file_comment_id`)
		)  ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>file_user_rel` (
		`file_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		PRIMARY KEY  (`file_id`, `user_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		<?php
		return ob_get_clean();
	}

}


// moving to a better OOP layout for stuff.
// this is a representation of a "file" in ucm. mapped to the ucm_file database table.
class ucm_file {

	protected $file_id = 0;
	protected $do_permissions = true;

	public function __construct( $file_id ) {
		$this->file_id = (int) $file_id;
	}

	public function do_permission_check( $yesno ) {
		$this->do_permissions = $yesno;
	}

	private $_get_data_cache = array();

	public function get_data() {
		if ( count( $this->_get_data_cache ) ) {
			return $this->_get_data_cache;
		}
		$file = false;
		if ( $this->file_id > 0 ) {
			$file = get_single( "file", "file_id", $this->file_id );
		}
		// check user has permissions to view this file.
		// for now we just base this on the customer id check
		if ( $file ) {
			// staff listing
			$staff             = get_multiple( 'file_user_rel', array( 'file_id' => $file['file_id'] ), 'user_id' );
			$file['staff_ids'] = array_keys( $staff );
			$file['type']      = ( isset( $file['file_url'] ) && $file['file_url'] ) ? 'remote' : ( isset( $file['bucket'] ) && $file['bucket'] ? 'bucket' : 'upload' );

			if ( $this->do_permissions ) {
				switch ( module_file::get_file_data_access() ) {
					case _FILE_ACCESS_ALL:
						// all files, no limits on SQL here
						break;
					case _FILE_ACCESS_JOBS:
						$jobs = module_job::get_jobs( array(), array(
							'columns' => 'u.job_id AS id',
						) );
						if ( ! $file['job_id'] || ! isset( $jobs[ $file['job_id'] ] ) ) {
							$file = false;
						}
						break;
					case _FILE_ACCESS_ME:
						if ( $file['create_user_id'] != module_security::get_loggedin_id() ) {
							$file = false;
						}
						break;
					case _FILE_ACCESS_ASSIGNED:
						if ( ! in_array( module_security::get_loggedin_id(), $file['staff_ids'] ) ) {
							$file = false;
						}
						break;
					case _FILE_ACCESS_CUSTOMERS:
					default:
						if ( class_exists( 'module_customer', false ) ) { //added for compat in newsletter system that doesn't have customer module
							$customer_permission_check = module_customer::get_customer( $file['customer_id'] );
							if ( $customer_permission_check['customer_id'] != $file['customer_id'] ) {
								$file = false;
							}
						}
				}
				// file data access switch
			}
		}
		if ( ! $file ) {
			$file = array(
				'new'                   => true,
				'type'                  => 'upload',
				'file_id'               => 0,
				'customer_id'           => isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : 0,
				'job_id'                => isset( $_REQUEST['job_id'] ) ? $_REQUEST['job_id'] : 0,
				'quote_id'              => isset( $_REQUEST['quote_id'] ) ? $_REQUEST['quote_id'] : 0,
				'description'           => '',
				'status'                => module_config::c( 'file_default_status', 'Uploaded' ),
				'file_name'             => '',
				'file_url'              => '',
				'staff_ids'             => array(),
				'bucket'                => 0,
				'bucket_parent_file_id' => 0,
				'approved_time'         => 0,
			);
		}
		$this->_get_data_cache = $file;

		return $file;
	}


	public function check_page_permissions() {
		$data = $this->get_data();
		if ( $this->file_id > 0 && ( ! $data || isset( $data['new'] ) || $data['file_id'] != $this->file_id ) ) {
			$this->file_id = 0;
			die( 'Failed to access file. No permissions to view this file, please check with the administrator.' );
		} else if ( $this->file_id > 0 ) {
			if ( class_exists( 'module_security', false ) ) {
				if ( ! module_security::check_page( array(
					'module'  => 'file',
					'feature' => 'Edit',
				) ) ) {
					$this->file_id = 0;
				}
			}
		} else {
			if ( class_exists( 'module_security', false ) ) {
				if ( ! module_security::check_page( array(
					'module'  => 'file',
					'feature' => 'Create',
				) ) ) {
					$this->file_id = 0;
				}
			}
		}
	}

	public function has_viewed() {
		// close off any notifications here.
		if ( $this->file_id > 0 ) {
			$sql = "UPDATE `" . _DB_PREFIX . "file_notification` SET `view_time` = '" . time() . "' WHERE `view_time` = 0 AND `user_id` = " . module_security::get_loggedin_id() . " AND file_id = " . (int) $this->file_id;
			query( $sql );
		}
	}

	public function get_type() {
		$file = $this->get_data();
		if ( isset( $_REQUEST['file_type'] ) ) {
			return $_REQUEST['file_type'];
		} else {
			return $file['type'];
		}
	}

	public function can_i_access() {
		$file = $this->get_data();
		if ( ! $file || $file['file_id'] != $this->file_id || ! $this->file_id ) {
			return false;
		}

		return true;
	}

	public function delete() {
		if ( $this->file_id && module_file::can_i( 'delete', 'Files' ) ) {

			$file_data = $this->get_data();
			if ( $this->can_i_access() ) {
				// delete any sub files of buckets first.
				// todo: recurisive testing.
				if ( $file_data['bucket'] ) {
					$sub_files = module_file::get_files( array(
						'bucket_parent_file_id' => $file_data['file_id']
					) );
					foreach ( $sub_files as $sub_file ) {
						if ( $sub_file['file_id'] && $sub_file['bucket_parent_file_id'] == $this->file_id ) {
							$sub_file_ucm = new ucm_file( $sub_file['file_id'] );
							$sub_file_ucm->delete();
						}
					}
				}
				// delete the physical file.
				if ( $file_data['file_path'] && is_file( $file_data['file_path'] ) ) {
					unlink( $file_data['file_path'] );
				}
				// delete the db entry.
				delete_from_db( 'file', 'file_id', $this->file_id );
				// delete any comments.
				delete_from_db( 'file_comment', 'file_id', $this->file_id );
				// delete any staff rel.
				delete_from_db( 'file_user_rel', 'file_id', $this->file_id );
				// delete any notifications
				delete_from_db( 'file_notification', 'file_id', $this->file_id );

			}
		}
	}
}