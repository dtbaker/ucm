<?php


$timer_id = (int) $_REQUEST['timer_id'];

$timer    = new UCMTimer( $timer_id );
$timer_id = $timer->get( 'timer_id' );

if ( ! empty( $_REQUEST['delete_segment'] ) && module_timer::can_i( 'delete', 'Timers' ) && module_form::check_secure_key() ) {

	$timer_segment = new UCMTimerSegment( $_REQUEST['delete_segment'] );
	if ( $timer_segment->timer_id == $timer->timer_id ) {
		$timer_segment->delete_with_confirm( false, $timer->link_open(), function () use ( $timer ) {
			$timer->get_total_time( true );
		} );
	}

}

if ( $timer_id > 0 && $timer->timer_id == $timer_id ) {
	$module->page_title = _l( 'Timer' );
} else {
	$module->page_title = _l( 'New Timer' );
}

if ( $timer_id > 0 && $timer ) {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'module'  => $module->module_name,
			'feature' => 'edit',
		) );
	}
} else {
	if ( class_exists( 'module_security', false ) ) {
		module_security::check_page( array(
			'module'  => $module->module_name,
			'feature' => 'create',
		) );
	}
	module_security::sanatise_data( 'timer', $timer );
}


$timer_segments      = new UCMTimerSegments();
$this_timer_segments = $timer_segments->get( array( 'timer_id' => $timer->timer_id ), array( 'timer_segment_id' => 'DESC' ) );
if ( ! empty( $_GET['timer_segment_id'] ) ) {
	foreach ( $this_timer_segments as $this_timer_segment ) {
		if ( $_GET['timer_segment_id'] == $this_timer_segment['timer_segment_id'] ) {
			// we've found a timer segment the user wishes to edit.
			// show that form.
			include module_theme::include_ucm( 'includes/plugin_timer/pages/timer_admin_edit_segment.php' );

			return;
		}
	}
}


?>


<form action="<?php echo module_timer::link_open( $timer_id ); ?>" method="post">
	<input type="hidden" name="_process" value="save_timer"/>
	<input type="hidden" name="timer_id" value="<?php echo $timer_id; ?>"/>
	<input type="hidden" name="return"
	       value="<?php echo ! empty( $_REQUEST['return'] ) ? htmlspecialchars( $_REQUEST['return'] ) : ''; ?>"/>


	<?php

	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.form_save',
			)
		)
	);
	module_form::print_form_auth();
	//module_form::set_default_field('timer_description');


	hook_handle_callback( 'layout_column_half', 1, '35' );

	$fieldset_data = array(
		'heading'        => array(
			'type'  => 'h3',
			'title' => _l( 'Timer Details' ),
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(
			array(
				'title' => _l( 'Description' ),
				'field' => array(
					'type'  => 'text',
					'id'    => 'timer_description',
					'name'  => 'description',
					'value' => $timer['description'],
				),
			),
			array(
				'title' => _l( 'Customer' ),
				'field' => array(
					'type'   => 'text',
					'name'   => 'customer_id',
					'lookup' => array(
						'key'         => 'customer_id',
						'display_key' => 'customer_name',
						'plugin'      => 'customer',
						'lookup'      => 'customer_name',
						'return_link' => true,
						'display'     => ! empty( $timer['customer_name'] ) ? $timer['customer_name'] : '',
					),
					'value'  => $timer['customer_id'],
				),
			),
			array(
				'title' => _l( 'Billable' ),
				'field' => array(
					'type'    => 'check',
					'name'    => 'billable',
					'value'   => 1,
					'checked' => $timer['billable'],
					'help'    => 'If this is ticked then the system will remind you to generate an invoice for this timer when it is completed.',
				),
			),
			array(
				'title'  => _l( 'Link To' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'owner_table',
						'class'   => 'timer_owner_table_change',
						'options' => module_timer::get_linked_tables(),
						'value'   => $timer['owner_table'],
						'blank'   => _l( ' - None - ' ),
					),
					array(
						'type'   => 'text',
						'name'   => 'owner_id',
						'lookup' => array(
							'key'         => 'owner_id',
							'display_key' => 'owner_name',
							'plugin'      => 'timer',
							'lookup'      => 'owner_value',
							'onfocus'     => 'ucm.timer.link_to_dropdown',
							'return_link' => true,
							'owner_table' => $timer['owner_table'],
							// so our form.php file can render display value correctly. see timer.php autocomplete_display()
							'display'     => '',
						),
						'value'  => $timer['owner_id'],
					),
				),
			),
		),
		'extra_settings' => array(
			'owner_table' => 'timer',
			'owner_key'   => 'timer_id',
			'owner_id'    => $timer['timer_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_timer::can_i( 'create', 'Timers' ),
			'allow_edit'  => module_timer::can_i( 'create', 'Timers' ),
		)
	);

	if ( ! empty( $timer['owner_table'] ) && ! empty( $timer['owner_id'] ) && ! empty( $timer['owner_child_id'] ) ) {
		$fieldset_data['elements']['child_id'] = array(
			'title'  => _l( 'Link Item' ),
			'fields' => array(
				module_timer::get_child_id_link( $timer ),
				array(
					'type'  => 'hidden',
					'name'  => 'owner_child_id',
					'value' => (int) $timer['owner_child_id'],
				)
			)
		);
	}

	if ( (int) $timer_id > 0 ) {

		$fieldset_data['elements']['status'] = array(
			'title'  => _l( 'Status' ),
			'fields' => array(
				/*array(
			'type' => 'select',
			'name' => 'timer_status',
			'value' => $timer['timer_status'],
			'options' => module_timer::get_statuses(),
			'allow_new' => false,
			'blank' => false,
		),*/
				$timer->get_status_text()
			)
		);
		if ( ! empty( $timer['user_id'] ) ) {
			$fieldset_data['elements']['user'] = array(
				'title'  => _l( 'User' ),
				'fields' => array(
					module_user::link_open( $timer['user_id'], true )
				)
			);
		}
	}

	echo module_form::generate_fieldset( $fieldset_data );


	if ( (int) $timer_id > 0 && class_exists( 'module_group', false ) ) {
		module_group::display_groups( array(
			'title'       => 'Timer Groups',
			'owner_table' => 'timer',
			'owner_id'    => $timer_id,
			'view_link'   => module_timer::link_open( $timer_id ),

		) );
	}


	if ( module_config::c( 'timer_enable_billing', 1 ) && $timer_id > 0 && class_exists( 'module_invoice', false ) && module_invoice::is_plugin_enabled() ) {

		if ( $timer['billable'] || $timer['invoice_id'] ) {
			$fieldset_data               = array(
				'heading'  => array(
					'type'  => 'h3',
					'title' => _l( 'Timer Billing' ),
				),
				'class'    => 'tableclass tableclass_form tableclass_full',
				'elements' => array()
			);
			$fieldset_data['elements'][] = array(
				'title'  => _l( 'Linked Invoice' ),
				'fields' => array(
					array(
						'type'   => 'text',
						'name'   => 'invoice_id',
						'value'  => $timer['invoice_id'],
						'lookup' => array(
							'key'         => 'invoice_id',
							'display_key' => 'name',
							'plugin'      => 'invoice',
							'lookup'      => 'name',
							'return_link' => true,
						),
					),
					' ',
					array(
						'type'    => 'button',
						'name'    => 'new_invoice',
						'value'   => _l( 'New Invoice' ),
						'onclick' => "window.location.href='" . module_timer::invoice_link( array( $timer_id ) ) . "';",
					),
				)
			);

			echo module_form::generate_fieldset( $fieldset_data );
		}
	}


	if ( class_exists( 'module_note', false ) && $timer_id > 0 ) {
		module_note::display_notes(
			array(
				'title'       => 'Timer Notes',
				'owner_table' => 'timer',
				'owner_id'    => $timer_id,
				'view_link'   => module_timer::link_open( $timer_id ),
			)
		);
	}


	hook_handle_callback( 'layout_column_half', 2, 65 );


	if ( (int) $timer_id > 0 ) {


		// timer segments.
		ob_start();

		include module_theme::include_ucm( 'includes/plugin_timer/inc/stopwatch.php' );

		$fieldset_data = array(
			'heading'         => array(
				'type'  => 'h3',
				'title' => _l( 'Timer Counter' ),
			),
			'class'           => 'tableclass tableclass_form tableclass_full',
			'elements'        => array(),
			'elements_before' => '<div id="timer_status">' . ob_get_clean() . '</div>',
		);

		echo module_form::generate_fieldset( $fieldset_data );


		ob_start();


		/** START TABLE LAYOUT **/
		$table_manager         = module_theme::new_table_manager();
		$columns               = array();
		$columns['start_time'] = array(
			'title'      => 'Start Time',
			'callback'   => function ( $timer ) {
				echo print_date( $timer['start_time'], true );
			},
			'cell_class' => 'row_action',
		);
		$columns['status']     = array(
			'title'    => 'Status',
			'callback' => function ( $timer ) {
				$timer_object = new UCMTimerSegment( $timer['timer_segment_id'] );
				echo $timer_object->get_status_text();
			},
		);
		$columns['duration']   = array(
			'title'    => 'Duration',
			'callback' => function ( $timer ) {

				if ( $timer['end_time'] ) {
					$timer_object = new UCMTimerSegment( $timer['timer_segment_id'] );
					echo $timer_object->get_total_time();
				} else {
					echo '<span class="ongoing-timer-segment">Ongoing</span>';
				}
			},
		);
		$columns['action']     = array(
			'title'    => 'Action',
			'callback' => function ( $timer_segment ) use ( $timer ) {
				if ( $timer_segment['timer_status'] == _TIMER_STATUS_PAUSED ) {
					if ( module_timer::can_i( 'delete', 'Timers' ) ) {
						?>
						<a
							href="<?php echo $timer->link_open( false ); ?>&delete_segment=<?php echo (int) $timer_segment['timer_segment_id']; ?>"
							data-ajax-modal='{"type":"normal","title":"<?php _e( 'Delete' ); ?>"}'><i
								class="fa fa-times-circle-o"></i></a>
						&nbsp; &nbsp;  <a
							href="<?php echo $timer->link_open( false ); ?>&timer_segment_id=<?php echo (int) $timer_segment['timer_segment_id']; ?>"
							data-ajax-modal='{"type":"normal","title":"<?php _e( 'Edit Segment' ); ?>"}'
							title="<?php _e( 'Edit Segment' ); ?>"><i
								class="fa fa-pencil"></i></a>
						<?php
					}
				} else {
					if ( module_timer::can_i( 'edit', 'Timers' ) ) {
						?>
						<a
							href="<?php echo $timer->link_open( false ); ?>&_process=complete_timer&timer_segment_id=<?php echo (int) $timer_segment['timer_segment_id']; ?>&form_auth_key=<?php echo module_form::get_secure_key(); ?>"
							title="<?php _e( 'Completed' ); ?>"><i
								class="fa fa-check-square"></i></a>
						<?php
					}
				}
			},
		);


		$table_manager->set_columns( $columns );
		$table_manager->set_rows( $this_timer_segments );
		$total_time    = 0;
		$footer_rows   = array();
		$footer_rows[] = array(
			'start_time' => array(
				'data' => _l( 'Total: ' ),
			),
			'status'     => array(
				'data' => '',
			),
			'duration'   => array(
				'data' => '<span class="ongoing-total-time">' . $timer->get_total_time() . '</span>',
			),
			'action'     => array(
				'data' => '',
			),
		);
		$table_manager->set_footer_rows( $footer_rows );

		$table_manager->print_table();

		$fieldset_data = array(
			'heading'         => array(
				'type'  => 'h3',
				'title' => _l( 'Timer Segments' ),
			),
			'class'           => 'tableclass tableclass_form tableclass_full',
			'elements'        => array(),
			'elements_before' => ob_get_clean(),
		);

		echo module_form::generate_fieldset( $fieldset_data );

	}


	hook_handle_callback( 'layout_column_half', 'end' );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => ! (int) $timer_id ? _l( 'Start Timer' ) : _l( 'Save Timer' ),
			),
			array(
				'ignore' => ! ( (int) $timer_id && module_timer::can_i( 'delete', 'Timers' ) ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'ignore'  => get_display_mode() == 'ajax',
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button cancel_button',
				'onclick' => "window.location.href='" . module_timer::link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	?>


</form>
