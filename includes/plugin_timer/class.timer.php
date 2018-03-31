<?php

defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:


if ( class_exists( 'UCMBaseSingle' ) ) {

	class UCMTimer extends UCMBaseSingle {

		public $db_id = 'timer_id';
		public $db_table = 'timer';
		public $display_key = 'description';
		public $display_name = 'Timer';
		public $display_name_plural = 'Timers';
		public $db_fields = array(
			'customer_id'    => array(),
			'description'    => array(),
			'timer_status'   => array(),
			'duration_calc'  => array(),
			'end_time'       => array(),
			'invoice_id'     => array(),
			'owner_id'       => array(),
			'owner_child_id' => array(),
			'owner_table'    => array(),
			'start_time'     => array(),
			'user_id'        => array(),
			'billable'       => array(),
		);

		public function default_values() {

			// these vars come in from the 'start timer' popup
			// and are populated from inc/stopwatch.php for the current page.

			// this 'customer_id' is new, not sure if it's going to cause problems anywhere.
			if ( ! empty( $_POST['vars']['customer_id'] ) ) {
				$this->db_details['customer_id'] = (int) $_POST['vars']['customer_id'];
			}
			if ( ! empty( $_POST['vars']['timer_customer_id'] ) ) {
				$this->db_details['customer_id'] = (int) $_POST['vars']['timer_customer_id'];
			}
			if ( ! empty( $_POST['vars']['timer_owner_table'] ) ) {
				$this->db_details['owner_table'] = $_POST['vars']['timer_owner_table'];
			}
			if ( ! empty( $_POST['vars']['timer_owner_id'] ) ) {
				$this->db_details['owner_id'] = (int) $_POST['vars']['timer_owner_id'];
			}
			if ( ! empty( $_POST['vars']['timer_owner_child_id'] ) ) {
				$this->db_details['owner_child_id'] = (int) $_POST['vars']['timer_owner_child_id'];
			}

			$this->db_details['billable'] = module_customer::c( 'timer_default_billable', 1, ! empty( $this->db_details['customer_id'] ) ? $this->db_details['customer_id'] : 0 );

			parent::default_values();
		}


		// when we create a new timer we have to also create the associated child segment part.
		public function save_data( $post_data ) {
			$creating_new = ! $this->id;
			if ( $creating_new ) {
				$post_data['start_time'] = time();
				$post_data['user_id']    = module_security::get_loggedin_id();
			}
			parent::save_data( $post_data );
			if ( $creating_new ) {
				$this->new_segment();
			}

			return $this->id;
		}

		public function new_segment() {
			if ( $this->id ) {
				$segment = new UCMTimerSegment();
				$segment->create_new( array(
					'timer_id'     => $this->id,
					'start_time'   => time(),
					'timer_status' => _TIMER_STATUS_RUNNING,
				) );
				$this->update_status();
			}
		}

		public function get_latest() {
			// find the latest segment, return that status.
			if ( $this->id ) {
				$timer_segments = new UCMTimerSegments();
				$rows           = $timer_segments->get( array( 'timer_id' => $this->id ), array( 'timer_segment_id' => "DESC" ) );
				$last           = array_shift( $rows );

				return $last;
			}

			return false;

		}

		public function get_invoice() {
			return (int) $this->invoice_id;
		}

		public function get_selector() {
			return 'timer-' . (int) $this->customer_id . '-' . $this->owner_table . '-' . (int) $this->owner_id . '-' . (int) $this->owner_child_id;
		}

		public function get_total_time( $update_cache = false, $in_seconds = false ) {

			if ( ! $this->id ) {
				return;
			}
			$total_time = $this->duration_calc;

			if ( $update_cache || $this->timer_status == _TIMER_STATUS_RUNNING || $this->timer_status == _TIMER_STATUS_AUTOMATIC ) {
				$timer_segments = new UCMTimerSegments();
				$rows           = $timer_segments->get( array( 'timer_id' => $this->timer_id ), array( 'timer_segment_id' => 'DESC' ) );
				$total_time     = 0;
				foreach ( $rows as $row ) {
					if ( $row['duration_calc'] ) {
						$total_time += $row['duration_calc'];
					} else if ( $row['timer_status'] == _TIMER_STATUS_RUNNING ) {
						$total_time += ( time() - $row['start_time'] );
					}
				}
				$this->update( 'duration_calc', $total_time );
			}

			if ( $in_seconds ) {
				return $total_time;
			}

			return module_timer::format_seconds( $total_time );
		}


		public function closed() {
			if ( $this->id ) {
				$this->update( 'timer_status', _TIMER_STATUS_CLOSED );
				$timer_segments = new UCMTimerSegments();
				$rows           = $timer_segments->get( array( 'timer_id' => $this->timer_id ), array( 'timer_segment_id' => 'DESC' ) );
				foreach ( $rows as $row ) {
					if ( $row['timer_status'] == _TIMER_STATUS_RUNNING ) {
						$seg = new UCMTimerSegment( $row['timer_segment_id'] );
						$seg->update( 'timer_status', _TIMER_STATUS_PAUSED );
					}
				}
			}
		}

		public function update_status() {
			if ( $this->id ) {
				if ( $this->timer_status == _TIMER_STATUS_AUTOMATIC ) {
					$this->get_total_time( true );

					return;
				}
				if ( $this->timer_status == _TIMER_STATUS_CLOSED ) {
					return;
				}
				$timer_segments = new UCMTimerSegments();
				$rows           = $timer_segments->get( array( 'timer_id' => $this->timer_id ), array( 'timer_segment_id' => 'DESC' ) );
				foreach ( $rows as $row ) {
					if ( $row['timer_status'] == _TIMER_STATUS_RUNNING ) {
						$this->update( 'timer_status', _TIMER_STATUS_RUNNING );

						return;
					}
				}
				$this->update( 'timer_status', _TIMER_STATUS_PAUSED );
				$this->get_total_time( true );
			}

			return;

		}

		public function get_status_text() {
			if ( ! empty( $this->db_details['invoice_id'] ) ) {
				return _l( 'Invoiced' );
			} else {
				if ( ! empty( $this->db_details['timer_status'] ) ) {
					switch ( $this->db_details['timer_status'] ) {
						case _TIMER_STATUS_RUNNING:
							return _l( 'Running' );
						case _TIMER_STATUS_PAUSED:
							return _l( 'Paused' );
						case _TIMER_STATUS_CLOSED:
							return _l( 'Finished' );
						case _TIMER_STATUS_AUTOMATIC:
							return _l( 'Automatic' ) . ' ' . _hr( 'This starts timing when you open the page. It is a good way to record you work.' );
					}
				}
			}

			return _l( 'N/A' );
		}

		public function delete_children() {
			$conn = $this->get_db();
			if ( $conn && $this->id ) {

				$this->db->prepare( 'DELETE FROM `' . _DB_PREFIX . 'timer_segment` WHERE `timer_id` = :id' );

				$this->db->bind_param( 'id', $this->id, 'int' );

				if ( $this->db->execute() ) {

				}
			}
		}

	}

	class UCMTimerSegment extends UCMBaseSingle {

		public $db_id = 'timer_segment_id';
		public $db_table = 'timer_segment';
		public $display_key = 'start_time';
		public $display_name = 'Timer Segment';
		public $display_name_plural = 'Timer Segments';
		public $db_fields = array(
			'timer_id'      => array(),
			'end_time'      => array(),
			'start_time'    => array(),
			'duration_calc' => array(),
			'timer_status'  => array(),
		);

		public function paused() {
			$this->update( 'timer_status', _TIMER_STATUS_PAUSED );
			$this->update( 'end_time', time() );
			// calc duration
			$this->update( 'duration_calc', time() - $this->start_time );
		}

		public function get_status_text() {
			switch ( $this->db_details['timer_status'] ) {
				case _TIMER_STATUS_RUNNING:
					return _l( 'Running' );
				case _TIMER_STATUS_PAUSED:
					return _l( 'Finished' );
			}

			return _l( 'N/A' );
		}

		public function get_total_time() {

			$total_time = $this->duration_calc;

			$hours   = floor( $total_time / 3600 );
			$minutes = floor( ( $total_time / 60 ) % 60 );
			$seconds = floor( $total_time % 60 );

			return sprintf( "%02d:%02d:%02d", $hours, $minutes, $seconds );
		}

		public function handle_submit() {

			if ( module_form::check_secure_key() ) {
				$timer_segment_id = ! empty( $_POST['timer_segment_id'] ) ? (int) $_POST['timer_segment_id'] : false;
				$this->load( $timer_segment_id );
				if ( $this->check_permissions() ) {
					$timer = new UCMTimer( $_REQUEST['timer_id'] );

					if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && module_timer::can_i( 'delete', 'Timers' ) ) {

						$return = self::link_open( $_REQUEST['timer_id'] );
						$timer_segment->delete_with_confirm( false, $return );

					} else if ( module_timer::can_i( 'edit', 'Timers' ) ) {

						$seg_data = array();
						if ( ! empty( $_POST['start_time'] ) ) {
							$seg_data['start_time'] = (int) $_POST['start_time'];
							if ( ! empty( $_POST['end_time'] ) ) {
								$seg_data['end_time']      = max( (int) $_POST['start_time'], (int) $_POST['end_time'] );
								$seg_data['duration_calc'] = $seg_data['end_time'] - $seg_data['start_time'];
							}
						}
						//                        print_r($_POST);
						//                        print_r($seg_data);exit;
						if ( $seg_data ) {
							$this->save_data( $seg_data );
						}
						$timer->update_status();
						set_message( 'Timer saved successfully' );
						$return = $timer->link_open();
						redirect_browser( $return );
						exit;
					}
				}
			}

		}


	}

	class UCMTimers extends UCMBaseMulti {

		public $db_id = 'timer_id';
		public $db_table = 'timer';
		public $display_name = 'Timer';
		public $display_name_plural = 'Timers';

	}

	class UCMTimerSegments extends UCMBaseMulti {

		public $db_id = 'timer_segment_id';
		public $db_table = 'timer_segment';
		public $display_name = 'Timer Segment';
		public $display_name_plural = 'Timer Segments';

	}
}