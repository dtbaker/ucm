<?php

if(!isset($data_type) || !$data_type)die('No $data_type');

if(!$module->can_i('view',$data_type['data_type_name'])){
    return;
}

$search = array();
foreach ( $module->get_data_link_keys() as $key ) {
    if ( isset( $_REQUEST[ $key ] ) ) {
        $search[ $key ] = $_REQUEST[ $key ];
    }
}
if ( $allow_search ) {
    $search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : $search;
}
$search['data_type_id'] = $data_type_id;
if ( isset( $parent_data_record_id ) && $parent_data_record_id ) {
    $search['parent_data_record_id'] = $parent_data_record_id;
}
// we have to limit the data types to only those created by current user if they are not administration
$datas = $module->get_datas( $search );


if(isset($data_type['max_entries']) && $data_type['max_entries'] == 1 && count($datas) <= 1){
    $foo = current($datas);
    if(!$foo || !$foo['data_record_id']){
        $_REQUEST['data_record_id'] = 'new';
        $_REQUEST['mode'] = 'edit'; // don't shoot me please
        include('admin_data_open.php');
    }else{
        $_REQUEST['data_record_id'] = $foo['data_record_id'];
        include('admin_data_open.php');
    }
}else {


    $header_buttons = array();
	if(empty($view_revision_id)) {
		if ( module_data::can_i( 'edit', _MODULE_DATA_NAME ) ) {
			$header_buttons[] = array(
				'url'   => $module->link_open_data_type( $data_type['data_type_id'] ),
				'title' => 'Settings',
			);
		}
		if ( $module->can_i( 'create', $data_type['data_type_name'] ) ) { // todo: perms for each data type
			$header_buttons[] = array(
				'url'   => $module->link( '', array(
					'data_type_id'          => $data_type['data_type_id'],
					'data_record_id'        => 'new',
					'mode'                  => 'edit',
					'parent_data_record_id' => isset( $parent_data_record_id ) ? $parent_data_record_id : false,
				) ),
				'title' => "Create New " . htmlspecialchars( $data_type['data_type_name'] ),
			);
		}

		if ( false && $allow_search ) {
			// search button has been moved over to header bar
			$header_buttons[] = array(
				'url'   => $module->link( "", array(
					'search_form'  => 1,
					'data_type_id' => $data_type_id,
					'view_all'     => isset( $_REQUEST['view_all'] ) ? 1 : false,
				) ),
				'title' => "Search",
			);
		}

	}
    if ( _DEMO_MODE ) {
        ?>
        <div style="padding:20px; text-align: center">This is a demo of the new Custom Data Forms
            feature. <?php if ( module_data::can_i( 'edit', _MODULE_DATA_NAME ) ) {
                ?> Please feel free to change the <a
                    href="<?php echo $module->link_open_data_type( $data_type['data_type_id'] ); ?>">Settings</a> for this Custom Data Form. <?php
            } ?>More details are <a
                href="http://ultimateclientmanager.com/support/documentation-wiki/custom-data-forms/" target="_blank">located
                here in the Documentation</a>.
        </div> <?php
    }

    print_heading( array(
        'main'   => isset( $parent_data_record_id ) && $parent_data_record_id ? false : true,
        'type'   => 'h2',
        'title'  => get_display_mode() == 'iframe' ? '' : htmlspecialchars( $data_type['data_type_name'] ),
        'button' => $header_buttons,
    ) );


    include( 'admin_data_list_output.php' );

}