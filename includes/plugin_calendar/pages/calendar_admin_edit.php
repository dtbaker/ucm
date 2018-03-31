<?php
$calendar_id = isset( $_GET['calendar_id'] ) ? (int) $_GET['calendar_id'] : 0;
$calendar    = module_calendar::get_calendar( $calendar_id );
if ( ! $calendar_id || ! isset( $calendar['calendar_id'] ) || $calendar['calendar_id'] != $calendar_id ) {
	$calendar_id = $calendar['calendar_id'] = 0;
	if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] ) {
		$calendar['customer_id'] = (int) $_REQUEST['customer_id'];
	}
	// if the user only has access to a single customer, add that customer id in here by default.

	if ( isset( $_POST['start_date_time'] ) && $_POST['start_date_time'] ) {
		$start_time        = js2PhpTime( $_POST['start_date_time'] );
		$calendar['start'] = $start_time;
	}
	if ( isset( $_POST['end_date_time'] ) && $_POST['end_date_time'] ) {
		$end_time        = js2PhpTime( $_POST['end_date_time'] );
		$calendar['end'] = $end_time;
	}
	if ( isset( $_POST['is_all_day'] ) && $_POST['is_all_day'] ) {
		$calendar['is_all_day'] = $_POST['is_all_day'];
	}
	if ( isset( $_POST['title'] ) && $_POST['title'] ) {
		$calendar['subject'] = $_POST['title'];
	}
}
if (
	( $calendar_id && module_calendar::can_i( 'edit', 'Calendar' ) ) ||
	( ! $calendar_id && module_calendar::can_i( 'create', 'Calendar' ) )
) {
	// perms are good to go!
} else {
	die( 'Permission denied' );
}
?>
<form action="" method="post" id="calendar_event_form">
	<?php
	module_form::set_required( array(
			'fields' => array(
				'subject' => 'Subject',
				'start'   => 'Start Date',
				'end'     => 'End Date',
			)
		)
	);

	$customer_list = array();
	$customers     = module_customer::get_customers();
	foreach ( $customers as $customer ) {
		$customer_list[ $customer['customer_id'] ] = $customer['customer_name'];
	}
	$staff_members    = module_user::get_staff_members();
	$staff_member_rel = array();
	foreach ( $staff_members as $staff_member ) {
		$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
	}
	if ( ! isset( $calendar['staff_ids'] ) || ! is_array( $calendar['staff_ids'] ) || ! count( $calendar['staff_ids'] ) ) {
		$calendar['staff_ids'] = array( false );
	}
	// output our event information using the standard UCM form processor:
	$fieldset_data = array(
		'heading'  => false,
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title'  => _l( 'Subject' ),
				'fields' => array(
					'<div id="calendarcolor" style="float:right"></div><input id="colorvalue" name="color" type="hidden" value="' . ( isset( $calendar['color'] ) ? htmlspecialchars( $calendar['color'] ) : '' ) . '" />',
					array(
						'type'  => 'text',
						'name'  => "subject",
						'value' => isset( $calendar['subject'] ) ? $calendar['subject'] : '',
					),

				)
			),
			array(
				'title'  => _l( 'Start' ),
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => "start",
						'value' => isset( $calendar['start'] ) ? print_date( $calendar['start'] ) : '',
					),
					'<span class="calendar_time">@</span>',
					array(
						'type'  => 'time',
						'name'  => "start_time",
						'value' => isset( $calendar['start'] ) ? date( 'g:ia', $calendar['start'] ) : '',
						'class' => 'calendar_time',
					),
					array(
						'type'    => 'check',
						'id'      => "is_all_day",
						'value'   => 1,
						'name'    => "is_all_day",
						'checked' => isset( $calendar['is_all_day'] ) && $calendar['is_all_day'] ? true : false,
						'label'   => _l( 'All Day Event' ),
					),
				),
			),
			array(
				'title'  => _l( 'End' ),
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => "end",
						'value' => isset( $calendar['end'] ) ? print_date( $calendar['end'] ) : '',
					),
					'<span class="calendar_time">@</span>',
					array(
						'type'  => 'time',
						'name'  => "end_time",
						'value' => isset( $calendar['end'] ) ? date( 'g:ia', $calendar['end'] ) : '',
						'class' => 'calendar_time',
					),
				),
			),
			array(
				'title'  => _l( 'Customer' ),
				'fields' => array(
					array(
						'type'    => 'select',
						'name'    => 'customer_id',
						'options' => $customer_list,
						'value'   => isset( $calendar['customer_id'] ) ? $calendar['customer_id'] : 0,
					),
					( isset( $calendar['customer_id'] ) && $calendar['customer_id'] ? '<a href="' . module_customer::link_open( $calendar['customer_id'], false ) . '" target="_blank">' . _l( 'Open' ) . '</a>' : '' ),
				),
			),
			array(
				'title'  => module_config::c( 'customer_staff_name', 'Staff' ),
				'fields' => array(
					'<div id="staff_ids_holder" style="float:left;">',
					array(
						'type'     => 'select',
						'name'     => 'staff_ids[]',
						'options'  => $staff_member_rel,
						'multiple' => 'staff_ids_holder',
						'values'   => $calendar['staff_ids'],
					),
					'</div>',
					_hr( 'Assign a staff member to this calendar event. Click the plus sign to add more staff members.' ),
				)
			),
			array(
				'title' => _l( 'Description' ),
				'field' => array(
					'type'  => 'textarea',
					'name'  => "description",
					'value' => isset( $calendar['description'] ) ? $calendar['description'] : '',
				),
			),
		)
	);
	echo module_form::generate_fieldset( $fieldset_data );
	/*
	$form_actions = array(
			'class' => 'action_bar action_bar_center',
			'elements' => array(
					array(
							'type' => 'save_button',
							'name' => 'butt_save',
							'value' => _l('Save'),
					),
					array(
							'ignore' => !(module_calendar::can_i('delete','Calendar') && $calendar_id > 0),
							'type' => 'delete_button',
							'name' => 'butt_del',
							'value' => _l('Delete'),
					),
					array(
							'type' => 'button',
							'name' => 'cancel',
							'value' => _l('Cancel'),
							'class' => 'submit_button',
							'onclick' => "alert('Close Modal');",
					),
			),
	);
	echo module_form::generate_form_actions($form_actions);*/
	?>
</form>

<?php
$base_path = _BASE_HREF . 'includes/plugin_calendar/wdCalendar/';
?>
<link href="<?php echo $base_path; ?>css/colorselect.css" rel="stylesheet"/>
<script src="<?php echo $base_path; ?>src/Plugins/Common.js" type="text/javascript"></script>
<script src="<?php echo $base_path; ?>src/Plugins/jquery.colorselect.js" type="text/javascript"></script>
<script type="text/javascript">
    var cv = $("#colorvalue").val();
    if (cv == "") {
        cv = "-1";
    }
    $("#calendarcolor").colorselect({title: "Color", index: cv, hiddenid: "colorvalue"});

    function toggle_is_all_day() {
        if ($('#is_all_day')[0].checked) {
            $('.calendar_time').hide();
        } else {
            $('.calendar_time').show();
        }
    }

    $(function () {
        $('#is_all_day').change(toggle_is_all_day);
        toggle_is_all_day();
    });
</script>
