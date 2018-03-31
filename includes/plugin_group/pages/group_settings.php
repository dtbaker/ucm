<?php

if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

if ( isset( $_REQUEST['group_id'] ) ) {
	include( 'group_edit.php' );
} else {

	$search = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
	$groups = $module->get_groups( $search );


	print_heading( array(
		'type'  => 'h2',
		'main'  => true,
		'title' => _l( 'Groups' ),
	) );

	/** START TABLE LAYOUT **/
	$table_manager   = module_theme::new_table_manager();
	$columns         = array();
	$columns['name'] = array(
		'title'      => 'Group Name',
		'callback'   => function ( $group ) {
			echo module_group::link_open( $group['group_id'], true );
		},
		'cell_class' => 'row_action',
	);

	$columns['owner_table'] = array(
		'title' => 'Available to',
	);
	$columns['count']       = array(
		'title' => 'Group Members',
	);

	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $groups );
	$table_manager->pagination = false;
	$table_manager->print_table();

}
