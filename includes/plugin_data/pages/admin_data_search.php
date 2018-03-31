<?php

$data_types = $module->get_data_types();

$data_type_id = (isset($_REQUEST['data_type_id'])) ? (int)$_REQUEST['data_type_id'] : false;
if($data_type_id){
	
	if(isset($_REQUEST['search_go']) && $_REQUEST['search_go']){
		
		// run a search on the provided fields over the current data records, displaying the results
		// (similar to admin_data_list)
	
		$search = array();
		$search['data_type_id'] = $data_type_id;
		// we have to limit the data types to only those created by current user if they are not administration
	    $datas = $module->get_datas($search);

        $search_fields = isset($_REQUEST['search_do']) ? $_REQUEST['search_do'] : array();
	    
	    // do the search in php.
	    foreach($datas as $data_id=>$data){
	    	// check status
	    	if(isset($_REQUEST['status']) && $_REQUEST['status']){
	    		if($data['status'] != $_REQUEST['status']){
	    			unset($datas[$data_id]);
	    			continue;
	    		}
	    	}
	    	// check create user id
	    	if(isset($_REQUEST['create_user_id']) && $_REQUEST['create_user_id']){
	    		if($data['create_user_id'] != $_REQUEST['create_user_id']){
	    			unset($datas[$data_id]);
	    			continue;
	    		}
	    	}

            // check searchable fields.
            if(isset($_REQUEST['data_field']) && is_array($_REQUEST['data_field'])){
                $data_items = $module->get_data_items($data['data_record_id']);
	    		foreach($_REQUEST['data_field'] as $data_field_id => $data_field_value){
                    if(isset($search_fields[$data_field_id])){
                        $settings = isset($data_items[$data_field_id]) ? @unserialize($data_items[$data_field_id]['data_field_settings']) : false;
                        if(is_array($data_field_value)){
                            $array_search = true;
                            $array_match = false;
                            foreach($data_field_value as $data_field_value_id => $data_field_value_value){
                                $data_field_value_value = trim($data_field_value_value);
                                // check if there's an "other" value
                                if(strtolower($data_field_value_value) == 'other' && isset($_REQUEST['other_data_field'][$data_field_id])){
                                    $data_field_value_value = trim($_REQUEST['other_data_field'][$data_field_id]);
                                }

                                if($data_field_value_value){
                                    // search this field!
                                    $foo = @unserialize($data_items[$data_field_id]['data_text']);
                                    if($foo){
                                        $data_items[$data_field_id]['data_text'] = $foo;
                                    }
                                    if(isset($data_items[$data_field_id]) && is_array($data_items[$data_field_id]['data_text']) && isset($data_items[$data_field_id]['data_text'][$data_field_value_id])){
                                        $array_match = true;
                                    }
                                }
                            }
                            if(!$array_match){
                                unset($datas[$data_id]);
                                continue;
                            }
                        }else{
                            $data_field_value = trim($data_field_value);
                            // check if there's an "other" value
                            if(strtolower($data_field_value) == 'other' && isset($_REQUEST['other_data_field'][$data_field_id])){
                                $data_field_value = trim($_REQUEST['other_data_field'][$data_field_id]);
                            }
                            if($data_field_value){
                                // search this field!
                                switch($data_field_id){
                                    case 'data_record_id':
                                        if($data_field_value != $data['data_record_id']) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'created_date_time':
                                        if(input_date($data_field_value,true) != input_date($data['date_created'],true)) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'created_date':
                                        if(input_date($data_field_value) != input_date($data['date_created'])) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'created_time':
                                        echo 'Searching by time not supported yet.';
                                        break;
                                    case 'updated_date_time':
                                        if(input_date($data_field_value,true) != input_date($data['date_updated'],true)) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'updated_date':
                                        if(input_date($data_field_value) != input_date($data['date_updated'])) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'updated_time':
                                        echo 'Searching by time not supported yet.';
                                        break;
                                    case 'created_by':
                                        if($data_field_value != $data['create_user_id']) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    case 'updated_by':
                                        if($data_field_value != $data['update_user_id']) {
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                        break;
                                    default:
                                        // search default (text and stuff)
                                        if(isset($data_items[$data_field_id])) {
                                            $foo = @unserialize( $data_items[ $data_field_id ]['data_text'] );
                                            if(is_array($foo)){
                                                if(!in_array($data_field_value, $foo)){
                                                    unset( $datas[ $data_id ] );
                                                    continue;
                                                }
                                            }else {

                                                if ( $settings && $settings['field_type'] && $settings['field_type'] == 'text' ) {
                                                    if ( strpos( strtolower( trim( $data_items[ $data_field_id ]['data_text'] ) ), strtolower( $data_field_value ) ) === false ) {
                                                        unset( $datas[ $data_id ] );
                                                        continue;
                                                    }
                                                } else if ( strtolower( trim( $data_items[ $data_field_id ]['data_text'] ) ) != strtolower( $data_field_value ) ) {
                                                    unset( $datas[ $data_id ] );
                                                    continue;
                                                }
                                            }
                                        }else{
                                            unset( $datas[ $data_id ] );
                                            continue;
                                        }
                                }
                            }
                        }
                    }
	    		}
	    	}
	    }

        $header_buttons[] = array(
                'url' => $module->link("",array(
                    'search_form'=>1,
                    'data_type_id'=>$data_type_id,
                    'view_all' => isset($_REQUEST['view_all']) ? 1 : false,
                )),
                'title' => 'Return to Search',
        );

        print_heading(array(
            'type' => 'h2',
            'title' => 'Search Results',
            'main' => true,
            'button' => $header_buttons,
        ));


        include('admin_data_list_output.php');



	}else{
		// display the search options for this data type
		$data_type = $data_types[$data_type_id];

        print_heading(array(
            'type' => 'h2',
            'title' => htmlspecialchars(_l('Search %s',$data_type['data_type_name'])),
            'main' => true,
        ));

        // collect a list of create/update user ids for search
        $search = array();
		$search['data_type_id'] = $data_type_id;
	    $datas = $module->get_datas($search);
        $create_user_ids = array();
        $update_user_ids = array();
        foreach($datas as $data){
            if($data['create_user_id']){
                $create_user_ids[$data['create_user_id']] = true;
            }
            if($data['update_user_id']){
                $update_user_ids[$data['update_user_id']] = true;
            }
        }
        foreach($create_user_ids as $user_id => $tf){
            $user = module_user::get_user($user_id);
            if($user){
                $create_user_ids[$user_id] = $user['name'].' '.$user['last_name'];
            }else{
                $create_user_ids[$user_id] = 'Unknown';
            }
        }
        foreach($update_user_ids as $user_id => $tf){
            $user = module_user::get_user($user_id);
            if($user){
                $update_user_ids[$user_id] = $user['name'].' '.$user['last_name'];
            }else{
                $update_user_ids[$user_id] = 'Unknown';
            }
        }
                $update_user_ids[2] = 'Unknown';

		?>

		<form action="" method="POST">
		<input type="hidden" name="search_go" value="true">

            <script type="text/javascript">
                $(function(){
                    $('.search_do').each(function(){
                        $(this).change(function(){
                            if(this.checked){
                                $('#search_field_'+$(this).attr('rel')).show();
                            }else{
                                $('#search_field_'+$(this).attr('rel')).hide();
                            }
                        }).mouseup(function(){
                            if(this.checked){
                                $('#search_field_'+$(this).attr('rel')).show();
                            }else{
                                $('#search_field_'+$(this).attr('rel')).hide();
                            }
                        });
                    });
                });
            </script>


		<?php
		// which fields are searchable:
		$data_field_groups = $module->get_data_field_groups($data_type_id);
		foreach($data_field_groups as $data_field_group){
			$data_field_group_id = $data_field_group['data_field_group_id'];
			$data_field_group = $module->get_data_field_group($data_field_group_id); // needed?
			$data_fields = $module->get_data_fields($data_field_group_id);
			foreach($data_fields as $data_field_id => $data_field){
				if(!$data_field['searchable']){
					unset($data_fields[$data_field_id]);
				}
            }
            if($data_fields){

                ob_start();
                ?>
                <table class="tableclass tableclass_form">
                <?php
                foreach($data_fields as $data_field_id => $data_field){
                    $data_field['multiple'] = false;
                    switch($data_field['field_type']){
                        case 'auto_id':
                            $data_field['data_field_id'] = 'data_record_id';
                            $data_field['field_type'] = 'text';
                            break;
                        case 'created_date_time':
                            $data_field['data_field_id'] = 'created_date';
                            $data_field['field_type'] = 'date';
                            break;
                        case 'created_date':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'date';
                            break;
                        case 'created_time':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'time';
                            break;
                        case 'updated_date_time':
                            $data_field['data_field_id'] = 'updated_date';
                            $data_field['field_type'] = 'date';
                            break;
                        case 'updated_date':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'date';
                            break;
                        case 'updated_time':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'time';
                            break;
                        case 'created_by':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'select';
                            $data_field['attributes'] = $create_user_ids;
                            break;
                        case 'updated_by':
                            $data_field['data_field_id'] = $data_field['field_type'];
                            $data_field['field_type'] = 'select';
                            $data_field['attributes'] = $update_user_ids;
                            break;
                    }
                    ?>

                    <tr>
                        <th class="width2"><?php echo $data_field['title'];?></th>
                        <td>
                            <div style="float:left; padding:0 10px 0 0">
                            <input type="checkbox" name="search_do[<?php echo $data_field['data_field_id'];?>]" value="1"
                                   rel="<?php echo $data_field['data_field_id'];?>" class="search_do">
                                </div>
                            <div style="float:left">
                            <div id="search_field_<?php echo $data_field['data_field_id'];?>" style="display:none;">
                            <?php
                            echo $module->get_form_element($data_field); ?>
                            </div>
                            </div>
                        </td>
                    </tr>

                    <?php
                }
                ?>
                </table>
                <?php
                $fieldset_data = array(
                    'heading' => array(
                        'title' => htmlspecialchars($data_field_group['title']),
                        'type' => 'h3',
                    ),
                    'elements_before' => ob_get_clean(),
                );
                echo module_form::generate_fieldset($fieldset_data);
                unset($fieldset_data);

            }
		}
		?>
		
		<input type="submit" name="search" value="Search" class="submit_button save_button">
		<input type="button" name="cancel" value="Cancel" class="submit_button" onclick="window.location.href='<?php echo $module->link('',array('data_type_id'=>$data_type_id,
                'view_all' => isset($_REQUEST['view_all']) ? 1 : false,)); ?>';">
		</form>
		
		<?php
	
	}
}else{
	// let user select a data type
	
	?>
	<h2><?php echo _l('Select Type to Search'); ?></h2>
	
	<?php foreach($data_types as $data_type){
		?>
		
		<a class="uibutton" href="<?php echo $module->link('admin_data_search',array('data_type_id'=>$data_type['data_type_id']));?>"><?php echo $data_type['data_type_name'];?></a>
		
		<?php
	}
	
}
