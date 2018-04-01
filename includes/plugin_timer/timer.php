<?php

define( '_TIMER_STATUS_RUNNING', 1 );
define( '_TIMER_STATUS_AUTOMATIC', 2 );
define( '_TIMER_STATUS_PAUSED', 3 );
define( '_TIMER_STATUS_CLOSED', 4 );

define( '_TIMER_ACCESS_ALL', 'All timers in system' ); // do not change string
define( '_TIMER_ACCESS_CUSTOMER', 'All timers from customers you have access to' ); // do not change string
define( '_TIMER_ACCESS_MINE', 'Only my timers' ); // do not change string

class module_timer extends module_base {


	public $version = 2.141;
	//2.141 - 2018-03-31 - timer + invoice link
	//2.140 - 2017-07-26 - bug fixes
	//2.139 - 2017-07-23 - timers linked to customers/websites
	//2.138 - 2017-05-02 - big changes
	//2.137 - 2017-02-27 - automatic timer fix
	//2.136 - 2017-02-01 - timer delete button fix
	//2.135 - 2016-11-21 - timer billable improvements
	//2.134 - 2016-11-16 - pause/resume job task timer fixes.
	//2.133 - 2016-10-29 - new timer features.
	//2.132 - 2016-02-02 - timer theme overriding support
	//2.131 - 2014-07-14 - only show timer on hourly tasks
	//2.13 - 2014-02-18 - timer js update
	//2.12 - one active timer at a time
	//2.11 - first Task Timer version

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
		$this->module_name     = "timer";
		$this->module_position = 26;

		if ( module_security::is_logged_in() && self::is_plugin_enabled() && self::can_i( 'view', 'Timers' ) && get_display_mode() != 'mobile' ) {
			module_config::register_css( 'timer', 'timer.css' );
			module_config::register_js( 'timer', 'timer.js' );
			hook_add( 'job_task_after', 'module_timer::hook_job_task_after' );
			hook_add( 'header_print_js', 'module_timer::hook_header_print_js' );
			hook_add( 'timer_display', 'module_timer::hook_timer_display' );
			hook_add( 'invoice_saved', 'module_timer::hook_invoice_saved' );
			hook_add( 'header_buttons', 'module_timer::hook_filter_var_header_buttons' );
		}
	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Timers' ) && self::is_plugin_enabled() ) {
			$this->links[] = array(
				"name"      => _l( 'Timers' ), // .' <ul id="active_timer_list"></ul><span id="active_timer_count"></span>',
				'id'        => 'timer_main_menu',
				"p"         => "timer_admin",
				'args'      => array( 'timer_id' => false ),
				'icon_name' => 'clock-o',
			);

			$this->links[] = array(
				"name"               => _l( 'Timers' ),
				// .' <ul id="active_timer_list"></ul><span id="active_timer_count"></span>',
				'id'                 => 'timer_customer_menu',
				"p"                  => "timer_admin",
				'args'               => array( 'timer_id' => false ),
				'holder_module'      => 'customer',
				// which parent module this link will sit under.
				'holder_module_page' => 'customer_admin_open',
				// which page this link will be automatically added to.
				'icon_name'          => 'clock-o',
			);


		}
	}

	public static function get_statuses() {
		return array(
			_TIMER_STATUS_RUNNING => _l( 'Running' ),
			_TIMER_STATUS_PAUSED  => _l( 'Paused' ),
			_TIMER_STATUS_CLOSED  => _l( 'Finished' ),
		);
	}

	public static function hook_header_print_js() {
		?>
		<script type="text/javascript">
        $(function () {
            ucm.timer.timer_ajax_url = '<?php echo self::link_open( false );?>';
            ucm.timer.mode = <?php echo (int) module_config::c( 'timer_mode', 1 );?>;
            $('#header_timer_start').on('click.timerstart', function (e) {
                e.preventDefault();
                ucm.form.open_modal({
                    title: '<?php _e( 'Start Timer' );?>',
                    href: '<?php echo self::link_open( 'new' );
											if ( ! empty( $_REQUEST['customer_id'] ) ) {
												echo '&customer_id=' . (int) $_REQUEST['customer_id'];
											}
											?>',
                    load_callback: function () {
                    }
                });
                return false;
            });
        });
		</script>
		<?php
	}

	public static function hook_timer_display( $callback, $owner_table, $owner_id ) {
		// this hook is called from a page (e.g. the job task page) when timer elements need to be rendered and attached to existing dom items.
		if ( ! empty( $owner_table ) && ! empty( $owner_id ) ) {
			?>
			<script type="text/javascript">
          $(function () {
              ucm.timer.load_page_timers('<?php echo $owner_table;?>', <?php echo (int) $owner_id;?> );
          });
			</script>
			<?php
		}
	}

	public static function hook_invoice_saved( $callback_name, $invoice_id, $invoice_data, $original_invoice_data ) {

		$invoice_items = module_invoice::get_invoice_items( $invoice_id, $invoice_data );
		foreach ( $invoice_items as $invoice_item ) {
			if ( ! empty( $invoice_item['timer_id'] ) ) {
				// this timer has been saved against an invoice!
				// punch this back into our db.
				$timer = new UCMTimer( $invoice_item['timer_id'] );
				if ( $timer->timer_id == $invoice_item['timer_id'] ) {
					$timer->update( 'invoice_id', $invoice_id );
					if ( $timer->timer_status != _TIMER_STATUS_CLOSED ) {
						$timer->closed();
					}
				}
			}
		}

	}

	public static function hook_filter_var_header_buttons( $callback, $header_buttons ) {
		$header_buttons['timer'] = array(
			'fa-icon' => 'clock-o',
			'title'   => 'Timer',
			'id'      => 'header_timer_start',
		);

		return $header_buttons;
	}

	public function handle_hook( $hook_name ) {
		/*if($hook_name=='top_menu_end' && module_config::c('timer_enabled',1) && module_security::is_logged_in() && self::can_i('view','Task Timer') && get_display_mode() != 'mobile'){

			include module_theme::include_ucm('includes/plugin_timer/inc/timer_menu.php');
		}*/
	}

	public static function hook_job_task_after( $hook, $job_id, $task_id, $job_data, $task_data ) {
		if ( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && self::can_i( 'create', 'Timers' ) && module_config::c( 'timer_enable_jobs', 1 ) ) {
			//'timer-' . (int)$this->customer_id . '-' . $this->owner_table . '-' . (int)$this->owner_id . '-' . (int)$this->owner_child_id
			$selector = 'timer-' . (int) $job_data['customer_id'] . '-job-' . (int) $job_id . '-' . (int) $task_id;
			?>
			<a href="#" class="timer_task" id="<?php echo $selector; ?>" data-task-timer='<?php echo json_encode( array(
				'selector'       => '#' . $selector,
				'description'    => _l( 'Job Task' ),
				'customer_id'    => (int) $job_data['customer_id'],
				'owner_id'       => (int) $job_id,
				'owner_table'    => 'job',
				'owner_child_id' => (int) $task_id,
			) ); ?>' title="<?php _e( 'Task Timer' ); ?>"><i class="fa fa-clock-o"></i></a>
			<?php
		}
	}

	public static function display_timers( $options ) {

		if ( self::can_i( 'create', 'Timers' ) && ! empty( $options['owner_id'] ) ) {
			if ( module_config::c( 'timer_automatic', 1 ) && module_config::c( 'timer_automatic_' . $options['owner_table'], 1 ) && empty( $options['avoid_automatic'] ) ) {
				self::extend_automatic_timer( $options['owner_table'], $options['owner_id'] );
			}

			$ucmtimers = new UCMTimers();
			$timers    = $ucmtimers->get( array(
				'owner_table'    => $options['owner_table'],
				'owner_id'       => $options['owner_id'],
				'owner_child_id' => ! empty( $options['owner_child_id'] ) ? $options['owner_child_id'] : false,
			), array( 'timer_id' => 'DESC' ) );


			include module_theme::include_ucm( 'includes/plugin_timer/inc/sidebar_widget.php' );
		}
	}

	public static function get_child_id_link( $timer_data ) {
		if ( ! empty( $timer_data['owner_child_id'] ) ) {
			switch ( $timer_data['owner_table'] ) {
				case 'job':
					// show task data.
					$job_task = module_job::get_task( $timer_data['owner_id'], $timer_data['owner_child_id'] );

					return htmlspecialchars( ! empty( $job_task['description'] ) ? $job_task['description'] : '' );
					break;
			}
		}
	}


	public static function link_open( $timer_id, $full = false ) {
		if ( $timer_id === false ) {
			$timers = new UCMTimers();

			return $timers->link_open();
		} else {
			$timer = new UCMTimer( $timer_id );

			return $timer->link_open( $full );
		}
	}

	public static function invoice_link( $billable_timers = array() ) {
		$url = module_invoice::link_open( 'new', false ) . '&timer_ids=' . implode( ',', $billable_timers );
		// we also have to pass any ticket_ids through
		$ticket_ids = array();
		foreach ( $billable_timers as $timer_id ) {
			$timer = new UCMTimer( $timer_id );
			if ( $timer->owner_table && $timer->owner_id && $timer->owner_table == 'ticket' ) {
				$ticket_ids[] = (int) $timer->owner_id;
			}
		}
		$other_ids = array( 'ticket_ids' => $ticket_ids );
		$url       .= '&' . http_build_query( $other_ids );

		return $url;
	}


	public function process() {
		$errors = array();
		if ( module_form::check_secure_key() ) {
			if ( "load_page_timers" == $_REQUEST['_process'] && ! empty( $_POST['load_page_timers'] ) ) {
				header( "Content-type: text/json" );
				$result = array();
				if ( self::can_i( 'view', 'Timers' ) ) {
					if ( ! empty( $_POST['load_page_timers']['owner_table'] ) && ! empty( $_POST['load_page_timers']['owner_id'] ) ) {
						$ucmtimers = new UCMTimers();
						// get paused or running timers for this page
						$page_timers = $ucmtimers->get(
							array(
								'owner_table'  => $_POST['load_page_timers']['owner_table'],
								'owner_id'     => $_POST['load_page_timers']['owner_id'],
								'user_id'      => module_security::get_loggedin_id(),
								'timer_status' => array(
									'condition' => 'or',
									'values'    => array(
										_TIMER_STATUS_PAUSED,
										_TIMER_STATUS_RUNNING,
										// _TIMER_STATUS_CLOSED,
									)
								)
							)
						);
						foreach ( $page_timers as $page_timer ) {

							$page_timer         = self::timer_js_format( $page_timer['timer_id'] );
							$result['timers'][] = $page_timer;
						}
					}
				}
				echo json_encode( $result );
				exit;


			} else if ( "automatic_page_timer" == $_REQUEST['_process'] ) {

				// javascript will poll this endpoint every 5 seconds
				// we look for any non-closed timers that match this owner_table / owner_id
				// we look for any existing timer segments ( finished in the last 20 seconds or so )
				// if we find an existing segment we increase the time, otherwise we start a new segment.

				header( "Content-type: text/json" );

				$result = array();

				if ( self::can_i( 'create', 'Timers' ) && module_config::c( 'timer_automatic', 1 ) ) {

					$result = array(
						'timers' => array()
					);

					if ( ! empty( $_POST['owner_table'] ) && ! empty( $_POST['owner_id'] ) ) {
						$timer_id           = self::extend_automatic_timer( $_POST['owner_table'], $_POST['owner_id'] );
						$result['timers'][] = self::timer_js_format( $timer_id );
					}

				}

				echo json_encode( $result );


				exit;

			} else if ( "task_timer_clicked" == $_REQUEST['_process'] ) {

				header( "Content-type: text/json" );

				if ( self::can_i( 'create', 'Timers' ) ) {

					$result = array(
						'timer' => array()
					);

					if ( ! empty( $_POST['timer_id'] ) ) {

						// the user is trying to modify an existing timer.
						$timer = new UCMTimer( $_POST['timer_id'] );
						if ( ! empty( $_POST['delete_completely'] ) && self::can_i( 'delete', 'Timers' ) ) {
							$timer->delete();
							echo json_encode( array( 'deleted' => 1 ) );
							exit;
						} else if ( ! empty( $_POST['finished'] ) ) {
							$timer->closed();
						} else if ( $timer->timer_status != _TIMER_STATUS_CLOSED ) {
							$latest = $timer->get_latest();
							if ( $latest ) {
								switch ( $latest['timer_status'] ) {
									case _TIMER_STATUS_RUNNING:
										// pause active timer.
										$timer_segment = new UCMTimerSegment( $latest['timer_segment_id'] );
										$timer_segment->paused();
										break;
									default:
										// start a new timer segment.
										// dont start a new one if they're finishing it.
										$timer->new_segment();
								}
							} else {
								$timer->new_segment();
							}
						}

					} else {

						// the user is trying to start a new timer.
						$timer = new UCMTimer();
						$timer->create_new( array(
							'start_time'     => time(),
							'time_status'    => _TIMER_STATUS_RUNNING,
							'user_id'        => module_security::get_loggedin_id(),
							'description'    => ! empty( $_POST['description'] ) ? $_POST['description'] : '',
							'owner_table'    => ! empty( $_POST['owner_table'] ) ? $_POST['owner_table'] : 0,
							'owner_id'       => ! empty( $_POST['owner_id'] ) ? $_POST['owner_id'] : 0,
							'owner_child_id' => ! empty( $_POST['owner_child_id'] ) ? $_POST['owner_child_id'] : 0,
							'customer_id'    => ! empty( $_POST['customer_id'] ) ? $_POST['customer_id'] : 0,
						) );
						$timer->new_segment();

					}

					$timer->update_status();

					$result['timer'] = self::timer_js_format( $timer->timer_id, $timer );

					echo json_encode( $result );
				}

				exit;
			} else if ( "complete_timer" == $_REQUEST['_process'] ) {

				if ( self::can_i( 'edit', 'Timers' ) ) {
					$timer         = new UCMTimer( $_REQUEST['timer_id'] );
					$timer_segment = new UCMTimerSegment( $_REQUEST['timer_segment_id'] );
					if ( $timer_segment->timer_id == $timer->timer_id ) {
						$timer_segment->paused();
					}
					$timer->update_status();
					set_message( 'Timer saved successfully' );
					redirect_browser( $timer->link_open() );
					exit;
				}

			} else if ( "save_timer_segment" == $_REQUEST['_process'] ) {

				$timer_segment = new UCMTimerSegment( $_REQUEST['timer_segment_id'] );
				$timer_segment->handle_submit();

			} else if ( "save_timer" == $_REQUEST['_process'] ) {

				if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && self::can_i( 'delete', 'Timers' ) && module_form::check_secure_key() ) {


					$timer  = new UCMTimer( $_REQUEST['timer_id'] );
					$return = self::link_open( false );
					if ( ! empty( $_REQUEST['return'] ) ) {
						if ( $_REQUEST['return'] == 'linked' ) {

							$data = $this->autocomplete_display( $timer['owner_id'], array(
								'owner_table' => $timer['owner_table'],
								'return_link' => true,
							) );
							if ( ! empty( $data ) && is_array( $data ) && ! empty( $data[1] ) ) {
								$return = $data[1];
							}
						} else {
							// raw url?
						}
					}
					$timer->delete_with_confirm( false, $return );

				} else if ( self::can_i( 'edit', 'Timers' ) ) {
					$timer     = new UCMTimer( $_REQUEST['timer_id'] );
					$save_data = $_POST;
					$timer->save_data( $save_data );

					// process buttons.
					if ( isset( $_POST['close_timer'] ) ) {

						$timer->closed();

					} else if ( isset( $_POST['start_pause'] ) ) {
						// we're starting or pausing the timer.
						$latest = $timer->get_latest();
						switch ( $latest['timer_status'] ) {
							case _TIMER_STATUS_RUNNING:
								// pause active timer.
								$timer_segment = new UCMTimerSegment( $latest['timer_segment_id'] );
								$timer_segment->paused();
								break;
							default:
								// start a new timer segment.
								$timer->new_segment();

						}
					}
					$timer->update_status();
					set_message( 'Timer saved successfully' );
					$return = $timer->link_open();
					if ( ! empty( $_POST['return'] ) ) {
						if ( $_POST['return'] == 'linked' ) {

							$data = $this->autocomplete_display( $timer['owner_id'], array(
								'owner_table' => $timer['owner_table'],
								'return_link' => true,
							) );
							if ( ! empty( $data ) && is_array( $data ) && ! empty( $data[1] ) ) {
								$return = $data[1];
							}
						} else {
							// raw url?
						}
					}
					redirect_browser( $return );
					exit;
				}
			}
		}
		print_error( $errors, true );
	}


	public static function get_timer_data_access() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Timer Data Access', array(
				_TIMER_ACCESS_ALL,
				_TIMER_ACCESS_CUSTOMER,
				_TIMER_ACCESS_MINE
			) );
		} else {
			return true;
		}
	}

	public static function timer_js_format( $timer_id, $timer = false ) {

		if ( ! $timer_id ) {
			return array();
		}
		if ( ! $timer ) {
			$timer = new UCMTimer( $timer_id );
		}
		if ( $timer->timer_id != $timer_id ) {
			return array();
		}

		$timer_data = $timer->db_details;

		$timer_data['selector']      = '#' . $timer->get_selector();
		$timer_data['timer_length']  = $timer->get_total_time( false, true );
		$timer_data['timer_display'] = $timer->get_total_time( false, false );
		$timer_data['closed']        = false;
		$timer_data['paused']        = false;
		$timer_data['running']       = false;
		switch ( $timer_data['timer_status'] ) {
			case _TIMER_STATUS_CLOSED:
				$timer_data['closed'] = true;
				break;
			case _TIMER_STATUS_PAUSED:
				$timer_data['paused'] = true;
				break;
			case _TIMER_STATUS_RUNNING:
				$timer_data['running'] = true;
				break;
		}

		return $timer_data;
	}

	public static function get_linked_tables() {
		return array(
			//			'invoice' => _l('Invoice'),
			'quote'   => _l( 'Quote' ),
			'job'     => _l( 'Job' ),
			'ticket'  => _l( 'Ticket' ),
			'website' => module_config::c( 'project_name_single', 'Website' ),
		);
	}

	public static function extend_automatic_timer( $owner_table, $owner_id ) {

		// todo: confirm the user has access to this particular owner_table and owner_id

		$ucmtimers = new UCMTimers();
		$timers    = $ucmtimers->get( array(
			'owner_table'  => $owner_table,
			'owner_id'     => $owner_id,
			'user_id'      => module_security::get_loggedin_id(),
			'timer_status' => _TIMER_STATUS_AUTOMATIC,
		) );
		$timer     = false;
		$t         = array_shift( $timers );
		if ( ! empty( $t['timer_id'] ) ) {
			$timer = new UCMTimer( $t['timer_id'] );
		}

		$last_segment = false;

		if ( ! $timer ) {
			$timer = new UCMTimer();
			$timer->create_new( array(
				'description'  => 'Opened ' . ucwords( $owner_table ),
				'owner_table'  => $owner_table,
				'owner_id'     => $owner_id,
				'user_id'      => module_security::get_loggedin_id(),
				'start_time'   => time(),
				'timer_status' => _TIMER_STATUS_AUTOMATIC,
			) );
			$timer->new_segment();
			// pause this new segment so we can continue recording times against it as below
			$last = $timer->get_latest();
			if ( ! empty( $last ) ) {
				$last_segment = new UCMTimerSegment( $last['timer_segment_id'] );
			}
		}
		if ( ! $last_segment ) {
			$last = $timer->get_latest();
			if ( ! empty( $last ) && $last['end_time'] > time() - 10 ) {
				$last_segment = new UCMTimerSegment( $last['timer_segment_id'] );
			} else {
				$timer->new_segment();
				$last = $timer->get_latest();
				if ( ! empty( $last ) ) {
					$last_segment = new UCMTimerSegment( $last['timer_segment_id'] );
				}
			}
		}
		if ( $last_segment ) {
			// this updates the times etc..
			$last_segment->paused();
		}

		return $timer->timer_id;
	}

	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( ! empty( $search_options['vars']['timer_owner_table'] ) ) {
			$customer_id = false;
			if ( ! empty( $search_options['vars']['lookup_customer_id'] ) ) {
				$customer_id = $search_options['vars']['lookup_customer_id'];
			}
			switch ( $search_options['vars']['timer_owner_table'] ) {
				case 'ticket':
					$res = module_ticket::get_tickets( array(
						'generic'     => $search_string,
						'customer_id' => $customer_id,
					) );
					foreach ( $res as $row ) {
						$result[] = array(
							'key'   => $row['ticket_id'],
							'value' => $row['subject']
						);
						if ( count( $result ) > 20 ) {
							break;
						}
					}
					break;
				case 'invoice':
					$res = module_invoice::get_invoices( array(
						'generic'     => $search_string,
						'customer_id' => $customer_id,
					) );
					foreach ( $res as $row ) {
						$result[] = array(
							'key'   => $row['invoice_id'],
							'value' => $row['name']
						);
						if ( count( $result ) > 20 ) {
							break;
						}
					}
					break;
				case 'quote':
					$res = module_quote::get_quotes( array(
						'generic'     => $search_string,
						'customer_id' => $customer_id,
					) );
					foreach ( $res as $row ) {
						$result[] = array(
							'key'   => $row['quote_id'],
							'value' => $row['name']
						);
						if ( count( $result ) > 20 ) {
							break;
						}
					}
					break;
				case 'job':
					$res = module_job::get_jobs( array(
						'generic'     => $search_string,
						'customer_id' => $customer_id,
					) );
					foreach ( $res as $row ) {
						$result[] = array(
							'key'   => $row['job_id'],
							'value' => $row['name']
						);
						if ( count( $result ) > 20 ) {
							break;
						}
					}
					break;
				case 'website':
					$res = module_website::get_websites( array(
						'generic'     => $search_string,
						'customer_id' => $customer_id,
					) );
					foreach ( $res as $row ) {
						$result[] = array(
							'key'   => $row['website_id'],
							'value' => $row['name']
						);
						if ( count( $result ) > 20 ) {
							break;
						}
					}
					break;
			}
		}else if ( module_timer::can_i( 'view', 'Timers' ) ) {

			$ucmtimers = new UCMTimers();

			$search_array = array(
				'description' => '%' . $search_string,
			);
			if ( ! empty( $_REQUEST['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['customer_id'];
			}
			if ( ! empty( $_REQUEST['vars']['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['vars']['customer_id'];
			}

			$res    = $ucmtimers->get( $search_array, array( 'timer_id' => 'DESC' ) );
			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['timer_id'],
					'value' => $row['description']
				);
				if ( count( $result ) > 20 ) {
					break;
				}
			}
		}

		return $result;
	}

	// used for working out the display value for a key.
	public function autocomplete_display( $key = 0, $search_options = array() ) {
		if ( ! empty( $search_options['owner_table'] ) && $key ) {
			switch ( $search_options['owner_table'] ) {
				case 'ticket':
					$res = module_ticket::get_ticket( $key );
					if ( ! empty( $search_options['return_link'] ) ) {
						return array( $res['subject'], module_ticket::link_open( $key, false ), _l( 'Ticket' ) );
					}

					return $res['subject'];
					break;
				case 'quote':
					$res = module_quote::get_quote( $key );
					if ( ! empty( $search_options['return_link'] ) ) {
						return array( $res['name'], module_quote::link_open( $key, false ), _l( 'Quote' ) );
					}

					return $res['name'];
					break;
				case 'invoice':
					$res = module_invoice::get_invoice( $key );
					if ( ! empty( $search_options['return_link'] ) ) {
						return array( $res['name'], module_invoice::link_open( $key, false ), _l( 'Invoice' ) );
					}

					return $res['name'];
					break;
				case 'job':
					$res = module_job::get_job( $key );
					if ( ! empty( $search_options['return_link'] ) ) {
						return array( $res['name'], module_job::link_open( $key, false ), _l( 'Job' ) );
					}

					return $res['name'];
					break;
				case 'website':
					$res = module_website::get_website( $key );
					if ( ! empty( $search_options['return_link'] ) ) {
						return array(
							$res['name'],
							module_website::link_open( $key, false ),
							module_config::c( 'project_name_single', 'Website' )
						);
					}

					return $res['name'];
					break;
			}
		}

		return '';
	}

	public static function format_seconds( $total_time ) {
		$hours   = floor( $total_time / 3600 );
		$minutes = floor( ( $total_time / 60 ) % 60 );
		$seconds = floor( $total_time % 60 );

		return sprintf( "%02d:%02d:%02d", $hours, $minutes, $seconds );
	}

	public function get_upgrade_sql() {

		$sql = '';
		if ( ! self::db_table_exists( 'timer' ) ) {
			$sql .= $this->get_install_sql();
		}

		return $sql;
	}


	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>timer` (
		`timer_id` int(11) NOT NULL auto_increment,
		`owner_table` varchar(40) NULL,
		`owner_id` int(11) NULL,
		`owner_child_id` int(11) NULL,
		`customer_id` int(11) NOT NULL DEFAULT '0',
		`description` varchar(255) NOT NULL DEFAULT '',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`start_time` int(11) NOT NULL DEFAULT '0',
		`end_time` int(11) NOT NULL DEFAULT '0',
		`invoice_id` int(11) NOT NULL DEFAULT '0',
		`timer_status` tinyint(2) NOT NULL DEFAULT '0',
		`duration_calc` int(11) NOT NULL DEFAULT '0',
		`billable` tinyint(2) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`timer_id`),
		KEY (`owner_table`, `owner_id`),
		KEY (`customer_id`),
		KEY (`user_id`),
		KEY (`timer_status`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>timer_segment` (
		`timer_segment_id` int(11) NOT NULL auto_increment,
		`timer_id` int(11) NOT NULL DEFAULT '0',
		`timer_status` tinyint(2) NOT NULL DEFAULT '0',
		`start_time` int(11) NOT NULL DEFAULT '0',
		`end_time` int(11) NOT NULL DEFAULT '0',
		`duration_calc` int(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`timer_segment_id`),
		KEY (`timer_status`),
		KEY (`timer_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		<?php
		return ob_get_clean();
	}


}

include_once 'class.timer.php';

