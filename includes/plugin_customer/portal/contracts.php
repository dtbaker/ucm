<?php

$table_manager                   = module_theme::new_table_manager();
$table_manager->table_class = 'public';
$table_manager->row_class = 'public';
$columns                         = array();
$columns['contract_title']          = array(
	'title'      => 'Contract Title',
	'callback'   => function ( $contract ) {
		?> <a href="<?php echo module_contract::link_public( $contract['contract_id'] );?>"><?php echo htmlspecialchars($contract['name']);?></a> <?php
	},
	'cell_class' => 'row_action',
);


$columns['contract_type'] = array(
	'title'    => 'Type',
	'callback' => function ( $contract ) {
		echo htmlspecialchars( $contract['type'] );
	},
);

$columns['contract_start_date']     = array(
	'title'    => 'Create Date',
	'callback' => function ( $contract ) {
		echo print_date( $contract['date_create'] );
	},
);
$columns['contract_completed_date'] = array(
	'title'    => 'Accepted Date',
	'callback' => function ( $contract ) {
		echo print_date( $contract['date_approved'] );
	},
);
$columns['contract_terminate_date'] = array(
	'title'    => 'Terminate Date',
	'callback' => function ( $contract ) {
		echo print_date( $contract['date_terminate'] );
	},
);
if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
	$columns['contract_website'] = array(
		'title'    => module_config::c( 'project_name_single', 'Website' ),
		'callback' => function ( $contract ) {
			$website = module_website::get_website($contract['website_id']);
			echo htmlspecialchars($website['name']);
		},
	);
}


$table_manager->set_columns( $columns );
$table_manager->row_callback = function ( $row_data ) {
	// load the full vendor data before displaying each row so we have access to more details
	return module_contract::get_contract( $row_data['contract_id'] );
};
$table_manager->set_rows( $contracts );
$table_manager->pagination = false;
$table_manager->print_table();