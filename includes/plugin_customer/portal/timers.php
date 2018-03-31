<?php


$table_manager = module_theme::new_table_manager();
$table_manager->table_class = 'public';
$table_manager->row_class = 'public';

$columns = array();
$columns['timer_name'] = array(
	'title' => 'Description',
	'callback' => function($timer){
		echo htmlspecialchars($timer['description']);
	},
	'cell_class' => 'row_action',
);

$columns['linked_data'] = array(
	'title' => 'Linked',
	'callback' => function($timer){
		// re-use the autocomplete code to show this information.
		global $plugins;
		if(!empty($timer['owner_table']) && !empty($timer['owner_id'])) {
			$data = $plugins['timer']->autocomplete_display( $timer['owner_id'], array(
				'owner_table' => $timer['owner_table'],
				'return_link' => true,
			) );
			if(!empty($data) && is_array($data)) {
				echo $data[2];
				echo ': <a href="' . htmlspecialchars( $data[1] ) . '">' . htmlspecialchars( $data[0] ) . '</a>';
				if ( ! empty( $timer['owner_child_id'] ) ) {
					echo ' ('. module_timer::get_child_id_link( $timer ) .')';
				}
			}
		}
	},
);

$columns['timer_status'] = array(
	'title' => 'Status',
	'callback' => function($timer){
		$ucmtimer = new UCMTimer($timer['timer_id']);
		echo $ucmtimer->get_status_text();
	},
);
$columns['start_time'] = array(
	'title' => 'Start Time',
	'callback' => function($timer){
		echo print_date($timer['start_time'],true);
	},
);
$columns['timer_duration'] = array(
	'title' => 'Duration',
	'callback' => function($timer){
		$ucmtimer = new UCMTimer($timer['timer_id']);
		echo $ucmtimer->get_total_time();
	},
);

$columns['invoice_id'] = array(
	'title' => 'Billable',
	'callback' => function($timer){
		if(!empty($timer['billable'])){
			_e('Yes');
		}else{
			_e('No');
		}
		if(!empty($timer['invoice_id'])){
			echo ' ' . module_invoice::link_public($timer['invoice_id']);
		}
	},
);



$table_manager->set_columns($columns);
$table_manager->set_rows($timers);
$table_manager->pagination = false;
$table_manager->print_table();