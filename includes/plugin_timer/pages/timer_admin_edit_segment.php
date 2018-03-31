<?php

if ( ! isset( $this_timer_segment ) ) {
	die( 'fail' );
}

?>

<form action="<?php echo module_timer::link_open( $timer_id ); ?>" method="post">
	<input type="hidden" name="_process" value="save_timer_segment"/>
	<input type="hidden" name="timer_id" value="<?php echo (int) $timer_id; ?>"/>
	<input type="hidden" name="timer_segment_id" value="<?php echo (int) $this_timer_segment['timer_segment_id']; ?>"/>

	<?php
	module_form::print_form_auth();
	//module_form::set_default_field('timer_description');

	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => _l( 'Timer Segment' ),
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title' => _l( 'Start Time' ),
				'field' => array(
					'type'  => 'date_time',
					'name'  => 'start_time',
					'value' => $this_timer_segment['start_time'],
				),
			),
			array(
				'title' => _l( 'End Time' ),
				'field' => array(
					'type'  => 'date_time',
					'name'  => 'end_time',
					'value' => $this_timer_segment['end_time'],
				),
			),
		)
	);

	echo module_form::generate_fieldset( $fieldset_data );


	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => 'Save Segment',
			),
			/*array(
				'ignore' => !((int)$timer_id && module_timer::can_i('delete','Timers')),
				'type' => 'delete_button',
				'name' => 'butt_del',
				'value' => _l('Delete'),
			),*/
			array(
				'ignore'  => get_display_mode() == 'ajax',
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button cancel_button',
				'onclick' => "window.location.href='" . module_timer::link_open( $timer_id ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	?>


</form>
