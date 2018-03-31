<?php

function sort_notes( $a, $b ) {
	return $a['note_time'] < $b['note_time'];
}

class module_note extends module_base {

	var $links;
	public $version = 2.278;
	// 2.278 - 2016-11-16 - fontawesome icon fixes
	// 2.277 - 2016-11-06 - timepicker improvement
	// 2.276 - 2016-10-22 - modal popup improvements
	// 2.275 - 2016-07-18 - note permission fix
	// 2.274 - 2016-07-10 - big update to mysqli
	// 2.273 - 2016-03-03 - user permissions
	// 2.272 - 2016-02-12 - note url length fix
	// 2.271 - 2016-01-30 - note button fix
	// 2.27 - 2015-12-08 - note reminders on calendar
	// 2.269 - 2015-10-17 - show note creator
	// 2.268 - 2015-06-08 - note quote link fix
	// 2.267 - 2015-01-07 - note bug fix with + symbol
	// 2.266 - 2014-11-19 - note dashboard reminders in date order
	// 2.265 - 2014-11-10 - note dashboard reminders in date order
	// 2.264 - 2014-08-19 - adding new note fix
	// 2.263 - 2014-08-14 - note SQL bug fix
	// 2.262 - 2014-08-10 - better utf8 support in notes
	// 2.261 - 2014-07-31 - responsive improvements

	// 2.23 - note delete
	// 2.231 - note delete bug fix
	// 2.232 - regenerate note links based on owner table/id rather than using rel_data for home page alerts.
	// 2.233 - bug fix for "reminder" notes applied to customer contacts.
	// 2.234 - php5/6 fix
	// 2.235 - new dashboard layout!.
	// 2.236 - speed improvements
	// 2.237 - some layout fixes
	// 2.238 - support for public invoice notes
	// 2.239 - bug fix
	// 2.24 - note 'public' bug fix
	// 2.241 - view permission fix to see full notes
	// 2.242 - javascript fix
	// 2.243 - permission improvement (create/edit/delete notes without having edit permission on parent page)
	// 2.244 - 2013-05-28 - dashboard note reminder permission fix
	// 2.245 - 2013-07-15 - bug fix
	// 2.246 - 2013-09-03 - dashboard improvement
	// 2.247 - 2013-09-05 - dashboard note alerts improvement
	// 2.248 - 2013-11-15 - working on new UI
	// 2.249 - 2013-12-01 - working on new UI
	// 2.25 - 2014-06-22 - fix for note loading
	// 2.26 - 2014-07-16 - vendor support

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
		$this->user_types      = array();
		$this->module_name     = "note";
		$this->module_position = 8882;
		module_config::register_css( 'note', 'notes.css' );

		hook_add( 'calendar_events', 'module_note::hook_calendar_events' );
	}

	public static function hook_calendar_events( $callback, $start_time, $end_time ) {
		// find all notes with reminder with a date between these two times.
		$calendar_events = array();
		if ( module_config::c( 'notes_show_reminders_on_calendar', 1 ) ) {

			$sql = "SELECT * FROM `" . _DB_PREFIX . "note` n ";
			$sql .= " WHERE n.`reminder` = 1 AND n.note_time >= " . (int) $start_time . " AND n.note_time <= " . (int) $end_time . "";
			//$sql .= " AND ( n.`user_id` = 0 OR n.`user_id` = ".module_security::get_loggedin_id().")";
			$sql   .= " ORDER BY n.note_time ASC";
			$notes = qa( $sql );

			foreach ( $notes as $note ) {
				$alert_res = self::process_note_permissions( array(), $note );
				if ( $alert_res ) {
					$calendar_events[] = array(
						'subject'       => $note['note'],
						'customer_id'   => $note['owner_table'] == 'customer' ? $note['owner_id'] : 0,
						'start_time'    => $note['note_time'],
						'end_time'      => $note['note_time'],
						'all_day'       => true,
						'user_id'       => $note['user_id'],
						'description'   => 'Test Description',
						'link'          => '',
						'other_details' => ucwords( $note['owner_table'] ) . ': ' . ( ! empty( $alert_res['type'] ) ? $alert_res['type'] . ': ' : '' ) . '' . ( ! empty( $alert_res['full_link'] ) ? $alert_res['full_link'] : '' ),
					);
				}
			}
		}

		return $calendar_events;
	}

	function handle_hook( $hook, $calling_module = false, $owner_table = false, $key_name = false, $key_value = false, $rel_data = false ) {
		switch ( $hook ) {
			case "home_alerts":
				$alerts = array();
				if ( module_config::c( 'allow_note_reminders', 1 ) ) {
					// find any jobs that are past the due date and dont have a finished date.

					$key = _l( 'Note Reminder' );
					if ( class_exists( 'module_dashboard', false ) ) {
						module_dashboard::register_group( $key, array(
							'columns' => array(
								'name'      => _l( 'Reminder' ),
								'type'      => _l( 'Type' ),
								'full_link' => _l( 'Link' ),
								'date'      => _l( 'Date' ),
								'days'      => _l( 'Date' ),
							)
						) );
					}
					$sql   = "SELECT * FROM `" . _DB_PREFIX . "note` n ";
					$sql   .= " WHERE n.`reminder` = 1 AND n.note_time < " . (int) strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) . "";
					$sql   .= " AND ( n.`user_id` = 0 OR n.`user_id` = " . module_security::get_loggedin_id() . ")";
					$sql   .= " ORDER BY n.note_time ASC";
					$tasks = qa( $sql );
					foreach ( $tasks as $task ) {
						$alert_res = process_alert( date( 'Y-m-d', $task['note_time'] ), $key );
						if ( $alert_res ) {
							$alert_res['link'] = $task['rel_data'];
							// fix for linking when changing folder.
							$alert_res['type'] = _l( ucwords( $task['owner_table'] ) );
							$alert_res         = self::process_note_permissions( $alert_res, $task );
							if ( $alert_res ) {
								$alert_res['name'] = $task['note'];
								$alert_res['date'] = print_date( $alert_res['date'] );
								$alert_res['time'] = $task['note_time'];
								$alerts[]          = $alert_res;
							}
						}
					}
				}

				return $alerts;
				break;
			/*case "note_list":
				if($owner_id && $owner_id != 'new'){

					$note_items = $this->get_notes(array("owner_table"=>$owner_table,"owner_id"=>$owner_id));
					foreach($note_items as &$note_item){
						// do it in loop here because of $this issues in static method below.
						// instead of include file below.
						$note_item['html'] = $this->print_note($note_item['note_id']);
					}
					include("pages/note_list.php");
				}else{
					echo 'Please save first before creating notes.';
				}
				break;*/
			case "note_delete":
				// find the key we are saving this address against.
				$owner_id = (int) $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}
				$note_hash = md5( $owner_id . '|' . $owner_table ); // just for posting unique arrays.
				if ( $owner_table && $owner_id ) {
					$this->note_delete( $owner_table, $owner_id );
				}
				break;

		}
	}

	public static function process_note_permissions( $alert_res, $task ) {
		switch ( $task['owner_table'] ) {
			case 'user':
				$user = module_user::get_user( $task['owner_id'] );
				if ( $user['customer_id'] || $user['vendor_id'] ) {
					$alert_res['link']      = module_user::link_open_contact( $task['owner_id'], false, $user );
					$alert_res['full_link'] = module_user::link_open_contact( $task['owner_id'], true, $user );
					$alert_res['type']      = _l( 'Contact' );
				} else {
					$alert_res['link']      = module_user::link_open( $task['owner_id'], false, $user );
					$alert_res['full_link'] = module_user::link_open( $task['owner_id'], true, $user );
				}
				break;
			case 'invoice':
				$invoice_data = module_invoice::get_invoice( $task['owner_id'], true );
				if ( ! $invoice_data || ! isset( $invoice_data['invoice_id'] ) || $invoice_data['invoice_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_invoice::link_open( $task['owner_id'], false, $invoice_data );
				$alert_res['full_link'] = module_invoice::link_open( $task['owner_id'], true, $invoice_data );
				break;
			case 'quote':
				$quote_data = module_quote::get_quote( $task['owner_id'], true );
				if ( ! $quote_data || ! isset( $quote_data['quote_id'] ) || $quote_data['quote_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_quote::link_open( $task['owner_id'], false, $quote_data );
				$alert_res['full_link'] = module_quote::link_open( $task['owner_id'], true, $quote_data );
				break;
			case 'website':
				$website_data = module_website::get_website( $task['owner_id'] );
				if ( ! $website_data || ! isset( $website_data['website_id'] ) || $website_data['website_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_website::link_open( $task['owner_id'], false );
				$alert_res['full_link'] = module_website::link_open( $task['owner_id'], true );
				break;
			case 'customer':
				$customer_data = module_customer::get_customer( $task['owner_id'] );
				if ( ! $customer_data || ! isset( $customer_data['customer_id'] ) || $customer_data['customer_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_customer::link_open( $task['owner_id'], false, $customer_data );
				$alert_res['full_link'] = module_customer::link_open( $task['owner_id'], true, $customer_data );
				break;
			case 'vendor':
				$vendor_data = module_vendor::get_vendor( $task['owner_id'] );
				if ( ! $vendor_data || ! isset( $vendor_data['vendor_id'] ) || $vendor_data['vendor_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_vendor::link_open( $task['owner_id'], false, $vendor_data );
				$alert_res['full_link'] = module_vendor::link_open( $task['owner_id'], true, $vendor_data );
				break;
			case 'job';
				$job_data = module_job::get_job( $task['owner_id'] );
				if ( ! $job_data || ! isset( $job_data['job_id'] ) || $job_data['job_id'] != $task['owner_id'] ) {
					return false;
				}
				$alert_res['link']      = module_job::link_open( $task['owner_id'], false, $job_data );
				$alert_res['full_link'] = module_job::link_open( $task['owner_id'], true, $job_data );
				break;
			// todo - add others.
		}

		return $alert_res;
	}

	public static function note_delete( $owner_table, $owner_id, $note_id = false ) {
		$sql = "DELETE FROM `" . _DB_PREFIX . "note` WHERE owner_table = '" . db_escape( $owner_table ) . "' AND owner_id = '" . db_escape( $owner_id ) . "'";
		if ( $note_id ) {
			$sql .= " AND note_id = " . (int) $note_id;
		}
		query( $sql );
	}

	public static function display_notes( $options ) {

		$owner_table = ( isset( $options['owner_table'] ) && $options['owner_table'] ) ? $options['owner_table'] : false;
		global $plugins;

		// permission checking.
		$can_create = $can_edit = $can_view = $can_delete = true;
		if ( $options && isset( $options['bypass_security'] ) ) {
			// do nothing?
		} else if ( isset( $options ) && isset( $options['owner_table'] ) && $options['owner_table'] && isset( $options['title'] ) && $options['title'] ) {
			$can_view   = $plugins[ $options['owner_table'] ]->can_i( 'view', $options['title'] );
			$can_edit   = $plugins[ $options['owner_table'] ]->can_i( 'edit', $options['title'] );
			$can_create = $plugins[ $options['owner_table'] ]->can_i( 'create', $options['title'] );
			$can_delete = $plugins[ $options['owner_table'] ]->can_i( 'delete', $options['title'] );
		}
		/*if(!module_security::is_page_editable()){
				$can_edit=$can_create=$can_delete=false;
		}*/
		if ( ! $can_view ) {
			return '';
		}
		// display links in a popup?
		$popup_links = get_display_mode() != 'mobile'; // disable popups in mobile version.

		$owner_id = ( isset( $options['owner_id'] ) && $options['owner_id'] ) ? (int) $options['owner_id'] : false;

		if ( $owner_id && $owner_table ) {
			// we have all that we need to display some notes!! yey!!
			// do we display a summary or not?

			$note_items      = self::get_notes( array( 'owner_table' => $owner_table, 'owner_id' => $owner_id ) );
			$display_summary = ( isset( $options['display_summary'] ) && $options['display_summary'] );
			if ( isset( $options['summary_owners'] ) && is_array( $options['summary_owners'] ) ) {
				// generate a list of other notes we have to display int eh list.
				foreach ( $options['summary_owners'] as $summary_owner_table => $summary_owner_ids ) {
					if ( is_array( $summary_owner_ids ) && count( $summary_owner_ids ) > 0 ) {
						//$sql = "SELECT *, note_id AS id FROM `"._DB_PREFIX."note` n WHERE owner_table = '".db_escape($summary_owner_table)."' AND ( ";
						$sql = "SELECT *, note_id AS id FROM `" . _DB_PREFIX . "note` n WHERE n.`owner_table` = '" . db_escape( $summary_owner_table ) . "' AND n.`owner_id` IN ( ";
						/*foreach($summary_owner_ids as $summary_owner_id){
							//$note_items = array_merge($note_items,self::get_notes(array('owner_table'=>$summary_owner_table,'owner_id'=>$summary_owner_id)));
                            $sql .= " `owner_id` = '".(int)$summary_owner_id."' OR ";
						}
                        $sql = rtrim($sql,'OR ');*/
						foreach ( $summary_owner_ids as $k => $summary_owner_id ) {
							$summary_owner_ids[ $k ] = (int) $summary_owner_id;
						}
						$sql        .= implode( ',', $summary_owner_ids );
						$sql        .= " )";
						$note_items = array_merge( $note_items, qa( $sql ) );
					}
				}
			}
			// moved to 'note_list.php'
			/*foreach($note_items as $key=>$note_item){
				$note_items[$key]['html'] = self::print_note($note_item['note_id'],$note_item,$display_summary,$can_edit,$can_delete,$options);
			}*/
			uasort( $note_items, 'sort_notes' );
			if ( isset( $options['view_link'] ) ) {
				if ( ! empty( $options['owner_table'] ) && ! empty( $options['owner_id'] ) ) {
					global $plugins;
					$test = $plugins[ $options['owner_table'] ]->link_open( $options['owner_id'] );
					if ( $test == $options['view_link'] ) {
						$options['view_link'] = 'o';
					}
				}
				if ( $options['view_link'] != 'o' ) {
					$blah = parse_url( $options['view_link'] );
					if ( $blah && isset( $blah['path'] ) && isset( $blah['query'] ) ) {
						$options['view_link'] = $blah['path'] . '?' . $blah['query'];
					}
				}
				$rel_data = $options['view_link'];
			}
			// move public options (used in invoices) into session to prevent long url.
			// todo: move all these options into a json array attached to dom and generate the note UI in javascript.
			if ( ! empty( $options['public'] ) ) {
				$_SESSION['note_public'] = $options['public'];
				$options['public']       = 1;
			}
			$note_list_safe = true;
			include( "pages/note_list.php" );
		}
	}

	public static function link_generate( $note_id = false, $options = array(), $link_options = array() ) {

		$key = 'note_id';
		if ( $note_id === false && $link_options ) {
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
		$options['page']  = 'note_admin';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['note_id'] = $note_id;
		$options['module']               = 'note';
		$data                            = array(); //self::get_note($note_id);
		$options['data']                 = $data;
		// what text should we display in this link?
		$options['text'] = 'note'; //(!isset($data['name'])||!trim($data['name'])) ? 'N/A' : $data['name'];
		array_unshift( $link_options, $options );

		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $note_id, $full = false, $options = array() ) {
		return self::link_generate( $note_id, array(
			'full'      => $full,
			'arguments' => array(
				'options' => $options ? base64_encode( serialize( $options ) ) : '',
			)
		) );
	}

	public static function print_note( $note_id, $note_item, $display_summary = false, $can_edit = true, $can_delete = true, $options = array() ) {
		if ( ! $note_item ) {
			$note_item = self::get_note( $note_id );
		}
		static $x = 0;


		global $plugins;
		$can_view = $can_edit = $can_create = $can_delete = false;
		// re-check permissions...
		if ( isset( $options ) && isset( $options['owner_table'] ) && $options['owner_table'] && isset( $options['title'] ) && $options['title'] ) {
			$can_view   = $plugins[ $options['owner_table'] ]->can_i( 'view', $options['title'] );
			$can_edit   = $plugins[ $options['owner_table'] ]->can_i( 'edit', $options['title'] );
			$can_create = $plugins[ $options['owner_table'] ]->can_i( 'create', $options['title'] );
			$can_delete = $plugins[ $options['owner_table'] ]->can_i( 'delete', $options['title'] );
		} else {

		}

		if ( ! module_security::is_page_editable() ) {
			//$can_edit=$can_create=$can_delete=false;
		}


		if ( isset( $options['summary_owners'] ) ) {
			unset( $options['summary_owners'] );
		}
		if ( isset( $options['display_summary'] ) ) {
			unset( $options['display_summary'] );
		}

		//
		if ( ! trim( $note_item['note'] ) ) {
			$note_item['note'] = 'none';
		}


		ob_start();

		/** START TABLE LAYOUT **/
		$table_manager          = module_theme::new_table_manager();
		$columns                = array();
		$columns['date']        = array(
			'title'    => 'Date',
			'width'    => 60,
			'callback' => function ( $note_item ) {
				if ( $note_item['reminder'] ) {
					echo '<strong>';
				}
				echo print_date( $note_item['note_time'] );
				if ( $note_item['reminder'] ) {
					echo '</strong>';
				}
			}
		);
		$columns['description'] = array(
			'title'    => 'Description',
			'callback' => function ( $note_item ) {
				if ( isset( $note_item['public'] ) && $note_item['public'] ) {
					echo '* ';
				}
				if ( $note_item['can_edit'] ) {
					$note_text = nl2br( htmlspecialchars( substr( $note_item['note'], 0, module_config::c( 'note_trim_length', 35 ) ) ) );
					$note_text .= strlen( $note_item['note'] ) > module_config::c( 'note_trim_length', 35 ) ? '...' : '';
					// IMPORTANT. also update note_list.php with this same code:
					?>
					<a href="<?php echo module_note::link_open( $note_item['note_id'], false, $note_item['options'] ); ?>"
					   data-ajax-modal='{"type":"normal","title":"<?php _e( 'Note' ); ?>","load_callback":"note_popup_opened","buttons":{"<?php _e( 'Save note' ); ?>":"save_note_callback","<?php _e( 'Cancel' ); ?>":"close"}}'
					   rel="<?php echo $note_item['note_id']; ?>"> <?php echo $note_text; ?> </a>
				<?php } else {
					echo forum_text( $note_item['note'] );
				}
			}
		);
		$columns['info']        = array(
			'title'    => 'Info',
			'width'    => 40,
			'callback' => function ( $note_item ) {
				if ( $note_item['display_summary'] ) {
					if ( $note_item['rel_data'] ) {
						global $plugins;
						echo $plugins[ $note_item['owner_table'] ]->link_open( $note_item['owner_id'], true );
					}
				} else {
					// find the user name who made thsi note.
					$user_data = module_user::get_user( $note_item['create_user_id'] );
					echo $user_data['name'];
				}
			}
		);
		if ( $can_delete ) {
			$columns['del'] = array(
				'title'    => ' ',
				'callback' => function ( $note_item ) {
					if ( $note_item['can_delete'] ) {
						?> <a
							href="<?php echo module_note::link_open( $note_item['note_id'], false, array_merge( $note_item['options'], array(
								'do_delete' => 'yes',
								'note_id'   => $note_item['note_id']
							) ) ); ?>"
							data-options="<?php echo htmlspecialchars( base64_encode( serialize( array_merge( $note_item['options'], array(
								'do_delete' => 'yes',
								'note_id'   => $note_item['note_id']
							) ) ) ) ); ?>" rel="<?php echo $note_item['note_id']; ?>"
							onclick="if(confirm('<?php _e( 'Really Delete Note?' ); ?>'))return true; else return false;"
							class="note_delete note_options_link delete"><i class="fa fa-trash"></i></a> <?php
					}
				}
			);
		}
		$table_manager->set_columns( $columns );
		$table_manager->inline_table = true;
		$table_manager->row_callback = function ( $row_data, &$row_object ) use ( $display_summary, $can_edit, $can_delete, &$options ) {
			$row_data['display_summary'] = $display_summary;
			$row_data['can_edit']        = $can_edit;
			$row_data['can_delete']      = $can_delete;
			$row_data['options']         = $options;
			$row_object->row_id          = 'note_' . $row_data['note_id'];

			return $row_data;
		};
		$table_manager->print_row( $note_item );

		/*?>
		<tr id="note_<?php echo $note_item['note_id'];?>" class="<?php echo ($x++%2)?'odd':'even';?>">
			<td>
                <?php
                if($note_item['reminder'])echo '<strong>';
                echo print_date($note_item['note_time']);
                if($note_item['reminder'])echo '</strong>';
                ?>
			</td>
			<td>
                <?php
                if(isset($note_item['public']) && $note_item['public'])echo '* ';
                if($can_edit){
                    $note_text = nl2br(htmlspecialchars(substr($note_item['note'],0,module_config::c('note_trim_length',35))));
                    $note_text .= strlen($note_item['note']) > module_config::c('note_trim_length',35) ? '...' : '';
                    ?>
                <a href="<?php echo self::link_open($note_item['note_id'],false,$options);?>" data-options="<?php echo htmlspecialchars(base64_encode(serialize($options)));?>" class="note_edit note_options_link" rel="<?php echo $note_item['note_id'];?>"> <?php echo $note_text; ?> </a>
                <?php }else{
                    echo forum_text($note_item['note']);
                } ?>
			</td>
			<td nowrap="nowrap">
				<?php
				if($display_summary){
					if($note_item['rel_data']){
                        echo $plugins[$note_item['owner_table']]->link_open($note_item['owner_id'],true);
					}
				}else{
					// find the user name who made thsi note.
					$user_data = module_user::get_user($note_item['create_user_id']);
					echo $user_data['name'];
				}
				?>
			</td>
            <?php if($can_delete){ ?>
            <td><a href="<?php echo self::link_open($note_item['note_id'],false,array_merge($options,array('do_delete'=>'yes','note_id'=>$note_item['note_id'])));?>" data-options="<?php echo htmlspecialchars(base64_encode(serialize(array_merge($options,array('do_delete'=>'yes','note_id'=>$note_item['note_id'])))));?>" rel="<?php echo $note_item['note_id'];?>" onclick="if(confirm('<?php _e('Really Delete Note?');?>'))return true; else return false;" class="note_delete note_options_link delete ui-state-default ui-corner-all ui-icon ui-icon-trash">[x]</a></td>
            <?php } ?>
		</tr>
		<?php
		*/

		return ob_get_clean();
	}

	function process() {
		if ( 'save_note' == $_REQUEST['_process'] ) {
			$note_id = $_REQUEST['note_id'];
			$options = unserialize( base64_decode( $_REQUEST['options'] ) );

			if ( ! $options ) {
				return;
			}
			if ( ! $note_id || $note_id == 'new' ) {
				$note_data = array(
					'note_id'     => $note_id,
					'owner_id'    => $options['owner_id'],
					'owner_table' => $options['owner_table'],
					'note_time'   => $_REQUEST['note_time'], //strtotime(input_date(urldecode($_REQUEST['note_time']),true)),
					'note'        => urldecode( $_REQUEST['note'] ),
					'rel_data'    => isset( $_REQUEST['rel_data'] ) ? $_REQUEST['rel_data'] : '',
					'reminder'    => isset( $_REQUEST['reminder'] ) ? $_REQUEST['reminder'] : 0,
					'user_id'     => isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : 0,
				);
			} else {
				// some fields we dont want to overwrite on existing notes:
				$note_data = array(
					'note_id'   => $note_id,
					'note_time' => $_REQUEST['note_time'], //strtotime(input_date(urldecode($_REQUEST['note_time']),true)),
					'note'      => urldecode( $_REQUEST['note'] ),
					'reminder'  => isset( $_REQUEST['reminder'] ) ? $_REQUEST['reminder'] : 0,
					'user_id'   => isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : 0,
				);
			}
			if ( isset( $_REQUEST['public_chk'] ) && $_REQUEST['public_chk'] ) {
				$note_data['public'] = isset( $_REQUEST['public'] ) ? $_REQUEST['public'] : 0;
			}
			// TODO - sanatise this note data with security module.
			// make sure we're saving a note we have access too.
			//module_security::sanatise_data('note',$note_data);
			// sanatise broke our update code.
			$note_id = update_insert( 'note_id', $note_id, 'note', $note_data );
			if ( get_display_mode() != 'ajax' ) {
				set_message( 'Note saved successfully' );
				redirect_browser( $this->link_open( $note_id, false, $options ) );
			}
			echo $this->print_note( $note_id, false, ( isset( $options['display_summary'] ) && $options['display_summary'] ), false, false, $options );
			exit;
		}
	}

	public static function save_note( $data = array() ) {
		//$this->note_id = isset($this->note_id) ? (int)$this->note_id : false;
		$note_id = update_insert( 'note_id', 'new', 'note', $data );

		return $note_id;
	}


	function delete( $note_id ) {
		$note_id = (int) $note_id;
		$sql     = "DELETE FROM " . _DB_PREFIX . "note WHERE note_id = '" . $note_id . "' LIMIT 1";
		query( $sql );
	}

	public static function get_note( $note_id ) {
		$note = get_single( "note", "note_id", $note_id );
		if ( $note ) {
			// optional processing here later on.

		}

		return $note;
	}

	public static function get_notes( $search = false ) {
		return get_multiple( "note", $search, "note_id", "exact", "note_id" );
	}


	public function get_install_sql() {
		return 'CREATE TABLE `' . _DB_PREFIX . 'note` (
  `note_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `owner_table` varchar(80) NOT NULL,
  `note` text NOT NULL,
  `note_time` int(11) NOT NULL,
  `rel_data` text NULL,
  `reminder` TINYINT( 1 ) NOT NULL DEFAULT  \'0\',
  `user_id` int(11) NOT NULL DEFAULT  \'0\',
  `public` TINYINT( 1 ) NOT NULL DEFAULT  \'0\',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`note_id`),
  KEY `owner_id` (`owner_id`),
  KEY `owner_table` (`owner_table`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';
	}

	public function get_upgrade_sql() {
		$sql = '';

		// if the installed version is less than 2.275 we have to remove all the incorrectly added "1" user role permissions.
		if ( version_compare( $this->get_installed_plugin_version(), '2.275', '<' ) ) {
			$broken_perms = get_multiple( 'security_permission', array(
				'name' => '1'
			) );
			print_r( $broken_perms );
			foreach ( $broken_perms as $broken_perm ) {
				if ( (int) $broken_perm['security_permission_id'] > 0 && $broken_perm['name'] === "1" ) {
					delete_from_db( 'security_permission', 'security_permission_id', (int) $broken_perm['security_permission_id'] );
					delete_from_db( 'security_role_perm', 'security_permission_id', (int) $broken_perm['security_permission_id'] );
				}
			}
		}


		/*$installed_version = (string)$installed_version;
	$new_version = (string)$new_version;*/
		/*$options = array(
				'2.1' => array(
						'2.2' => 'ALTER TABLE  `'._DB_PREFIX.'note` ADD `user_id` INT( 11 ) NOT NULL DEFAULT \'0\' AFTER `rel_data`;',
				),
		);
		if(isset($options[$installed_version]) && isset($options[$installed_version][$new_version])){
				$sql = $options[$installed_version][$new_version];
		}*/
		$fields = get_fields( 'note' );
		if ( ! isset( $fields['reminder'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'note` ADD `reminder` INT( 11 ) NOT NULL DEFAULT \'0\' AFTER `rel_data`;';
		}
		if ( ! isset( $fields['user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'note` ADD `user_id` INT( 11 ) NOT NULL DEFAULT \'0\' AFTER `rel_data`;';
		}
		if ( ! isset( $fields['public'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'note` ADD `public` TINYINT( 1 ) NOT NULL DEFAULT \'0\' AFTER `user_id`;';
		}

		return $sql;
	}
}