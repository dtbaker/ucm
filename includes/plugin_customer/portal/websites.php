<?php

$table_manager = module_theme::new_table_manager();
$table_manager->table_class = 'public';
$table_manager->row_class = 'public';
$columns = array();
$columns['website_title'] = array(
	'title' => 'Website Name',
	'callback' => function($website){
	    echo '<a href="' . htmlspecialchars($website['url']).'" target="_blank">';
		echo htmlspecialchars($website['name']);
		echo '</a>';
	},
	'cell_class' => 'row_action',
);

$columns['website_status'] = array(
	'title' => 'Status',
	'callback' => function($website){
		echo htmlspecialchars($website['status']);
	},
);
if(class_exists('module_subscription',false)){
	$table_manager->display_subscription('website',function($website){
		module_subscription::print_table_data('website',$website['website_id']);
	});
}



$table_manager->set_id('website_list');
$table_manager->set_columns($columns);
$table_manager->row_callback = function($row_data){
	// load the full vendor data before displaying each row so we have access to more details
	return module_website::get_website($row_data['website_id']);
};
$table_manager->set_rows($websites);
$table_manager->pagination = false;
$table_manager->print_table();