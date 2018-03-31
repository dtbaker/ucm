<?php

$embed_form = true;

if(!isset($data_type) || !$data_type) {
	if ( isset( $_REQUEST['data_type_id'] ) && (int) $_REQUEST['data_type_id'] > 0 ) {
		$data_type = module_data::get_data_type($_REQUEST['data_type_id']);
		if($data_type){
			$data_type_id = $data_type['data_type_id'];
		}
	}
	if ( ! isset( $data_type ) || ! $data_type ) {
		die( 'No $data_type' );
	}
}

if(!isset($module)){
	global $plugins;
	$module = $plugins['data'];
}

if(!$module->can_i('view',$data_type['data_type_name'])){
    return;
}

$allow_search = false;

$search = array();
foreach ( $module->get_data_link_keys() as $key ) {
    if ( isset( $_REQUEST[ $key ] ) ) {
        $search[ $key ] = $_REQUEST[ $key ];
    }else if ( "{$owner_table}_id" == $key ) {
        $search[ "{$owner_table}_id" ] = $owner_id;
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


$form_settings = array();
$form_settings['title'] = $data_type['data_type_name'];
$form_settings['data_type_id'] = $data_type['data_type_id'];
//$form_settings['data_record_id'] = $data_record_id;
//$form_settings['parent_data_record_id'] = $data_record['parent_data_record_id'];
$form_settings['hook_location'] = $owner_table;
$form_settings['owner_table'] = $owner_table;
$form_settings['owner_id'] = $hook_location;
$form_settings[$owner_table."_id"] = $owner_id;
foreach ( $module->get_data_link_keys() as $key ) {
	if ( isset( $_REQUEST[ $key ] ) ) {
		$form_settings[ $key ] = $_REQUEST[ $key ];
	}
}
?>
<script type="text/javascript">
	ucm.data.settings.url = '<?php echo _BASE_HREF;?>?m=data&p=admin_data&display_mode=iframe';
</script>
<div class="custom_data_embed_wrapper" data-settings="<?php echo htmlentities(json_encode($form_settings));?>">
<?php
ob_start();


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

    if ( $allow_search ) {
        $header_buttons[] = array(
            'url'   => $module->link( "", array(
                'search_form'  => 1,
                'data_type_id' => $data_type_id,
                'view_all' => isset($_REQUEST['view_all']) ? 1 : false,
            ) ),
            'title' => "Search",
        );
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


//	ob_start();
    include( 'admin_data_list_output.php' );
//	$fieldset_data = array(
//		'heading' =>  array(
//			'title'=>htmlspecialchars( $data_type['data_type_name'] ),
//			'type'=>'h3',
//			'button'=>$header_buttons
//		),
//		'elements_before' => ob_get_clean(),
//	);
//	echo module_form::generate_fieldset($fieldset_data);

}

$header_buttons =array(
	array(
		'url' => '#',
		'onclick' => 'ucm.data.popup(this); return false;',
		'title' => _l('Open')
	),
	array(
		'url' => '#',
		'onclick' => 'ucm.data.popup_new(this); return false;',
		'title' => _l('Add New')
	),
);
$fieldset_data = array(
	'heading' =>  array(
		'title'=>htmlspecialchars( $data_type['data_type_name'] ),
		'type'=>'h3',
		'button'=>$header_buttons
	),
	'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset($fieldset_data);
?>
</div>