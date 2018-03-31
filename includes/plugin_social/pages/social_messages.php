<?php

if ( ! module_social::can_i( 'view', 'Comments', 'Social', 'social' ) ) {
	die( 'No access' );
}

$module->page_title = _l( 'Inbox' );

$header = array(
	'title'  => _l( 'Social Inbox' ),
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
if ( module_social::can_i( 'create', 'Facebook Comments', 'Social', 'social' ) || module_social::can_i( 'create', 'Twitter Comments', 'Social', 'social' ) ) {
	$header['button'] = array(
		'url'   => module_social::link_open_compose(),
		'title' => _l( 'Compose' ),
		'type'  => 'add',
	);
}
print_heading( $header );

// grab a mysql resource from all available social plugins (hardcoded for now - todo: hook)
$search = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
if ( ! isset( $search['status'] ) ) {
	$search['status'] = _SOCIAL_MESSAGE_STATUS_UNANSWERED;
}
$order = array();

// retuin a combined copy of all available messages, based on search, as a MySQL resource
// so we can loop through them on the global messages combined page.
$message_managers = module_social::get_message_managers();
/* @var $message_manager ucm_facebook */
foreach ( $message_managers as $message_id => $message_manager ) {
	$message_manager->load_all_messages( $search, $order );
}

// filter through each mysql resource so we get the date views. output each row using their individual classes.
$all_messages   = array();
$loop_messages  = array();
$last_timestamp = false;
while ( true ) {
	// fill them up
	$has_messages = false;
	foreach ( $message_managers as $type => $message_manager ) {
		if ( ! isset( $loop_messages[ $type ] ) ) {
			$loop_messages[ $type ] = $message_manager->get_next_message();
			if ( $loop_messages[ $type ] ) {
				//echo "Got $type with date of ".print_date($loop_messages[$type]['message_time'],true)."<br>\n";
				$loop_messages[ $type ]['message_manager'] = $message_manager;
				$has_messages                              = true;
			} else {
				unset( $loop_messages[ $type ] );
			}
		}
	}
	if ( ! $has_messages && empty( $loop_messages ) ) {
		break;
	}
	// pick the lowest one and replenish its spot
	$next_type = false;
	foreach ( $loop_messages as $type => $message ) {
		if ( ! $next_type || $message['message_time'] > $last_timestamp ) {
			$next_type      = $type;
			$last_timestamp = $message['message_time'];
		}
	}
	//echo "Message $next_type : <br>\n";
	$all_messages[] = $loop_messages[ $next_type ];
	unset( $loop_messages[ $next_type ] );
	// repeat.

}
// todo - hack in here some sort of cache so pagination works nicer ?
//module_debug::log(array( 'title' => 'Finished social messages', 'data' => '', ));
//print_r($all_messages);

?>

<form action="" method="post">

	<?php $search_bar = array(
		'elements' => array(
			'name'   => array(
				'title' => _l( 'Message Content:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 15,
				)
			),
			'status' => array(
				'title' => _l( 'Status:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[status]',
					'blank'   => false,
					'value'   => isset( $search['status'] ) ? $search['status'] : '',
					'options' => array(
						_SOCIAL_MESSAGE_STATUS_UNANSWERED => 'Un-Archived',
						_SOCIAL_MESSAGE_STATUS_ANSWERED   => 'Archived',
					),
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );

	$table_manager                    = module_theme::new_table_manager();
	$columns                          = array();
	$columns['social_column_social']  = array(
		'title'      => 'Social Account',
		'cell_class' => 'row_action',
	);
	$columns['social_column_time']    = array(
		'title' => 'Date/Time',
	);
	$columns['social_column_from']    = array(
		'title' => 'From',
	);
	$columns['social_column_summary'] = array(
		'title' => 'Summary',
	);
	$columns['social_column_action']  = array(
		'title' => 'Action',
	);
	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( &$row_data ) {
		$row_data['message_manager']->output_row( $row_data, array() );

		return false; // prevents the table manager from outputting the row
	};
	$table_manager->set_rows( $all_messages );
	$table_manager->pagination = true;
	$table_manager->print_table();
	?>
</form>

<script type="text/javascript">
    $(function () {
        ucm.social.init();
			<?php foreach ( $message_managers as $message_id => $message_manager ) {
			$message_manager->init_js();
		} ?>
    });
</script>

<div id="social_modal_popup" title="">
	<div class="modal_inner" style="height:100%;"></div>
</div>
