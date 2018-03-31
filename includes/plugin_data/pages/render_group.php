<?php

if ( ! isset( $rendered_field_groups ) ) {
	$rendered_field_groups = array();
}
if ( ! isset( $view_revision_id ) ) {
	$view_revision_id = false;
}
if ( ! isset( $current_revision ) ) {
	$current_revision = false;
}
if ( ! isset( $mode ) ) {
	$mode = 'view';
}

if ( $current_revision && $view_revision_id ) {
	// user wants a custom revision, we pull out the custom $data_field_groups
	// and we tell the form layout to use the serialized cached field layout information
	$data_field_groups = unserialize( $current_revision['field_group_cache'] );
	$data_field_group  = $data_field_groups[ $data_field_group_id ];
	$all_data_fields   = unserialize( $current_revision['field_cache'] );
	if ( isset( $all_data_fields[ $data_field_group_id ] ) ) {
		$data_fields = $all_data_fields[ $data_field_group_id ];
	} else {
		$data_fields = array();
	}
} else {
	$data_field_group = $module->get_data_field_group( $data_field_group_id );
	$data_fields      = $module->get_data_fields( $data_field_group_id );
}

$data_field_group['layout'] = 'table';

if ( ! isset( $rendered_field_groups[ $data_field_group_id ] ) ) {
	$rendered_field_groups[ $data_field_group_id ] = true;

	if ( ! isset( $display_field_group_heading ) ) {
		$display_field_group_heading = true;
	}

	if ( ! isset( $extra_group ) ) {
		$extra_group = array();
	}
	if ( ! isset( $hide_show_group_on_load ) ) {
		$hide_show_group_on_load = array();
	}

	// todo: change display_default based on php on page load, not just javascript after page load. 
	$display_group = true;
	/*if(!$data_field_group['display_default'] && !isset($hide_show_group_on_load[$data_field_group['data_field_group_id']])){
		$display_group = false;
	}*/

	?>

	<div class="data_group<?php echo ( ! $display_group ) ? ' data_group_hidden' : ''; ?>"
	     style="<?php echo ( ! $display_group ) ? 'display:none;' : ''; ?>"
	     id="data_group_<?php echo $data_field_group['data_field_group_id']; ?>">
		<?php

		if ( isset( $data_field_group['sub_data_type_id'] ) && $data_field_group['sub_data_type_id'] ) {

			if ( (int) $data_record_id > 0 ) {
				$data_type    = $module->get_data_type( $data_field_group['sub_data_type_id'] );
				$data_type_id = $data_type['data_type_id'];
				if ( $data_type_id ) {
					$allow_search          = false;
					$parent_data_record_id = $data_record_id;
					include( 'admin_data_list_type.php' );
				}
			}

		} else {

			ob_start(); // to pass this into our form printing method (so themes can style this output nicer)
			?>
			<div>

				<input type="hidden" name="save_data_group[<?php echo $data_field_group['data_field_group_id']; ?>]"
				       id="save_group_<?php echo $data_field_group['data_field_group_id']; ?>"
				       value="<?php echo ( $data_field_group['display_default'] ) ? 'true' : ''; ?>">

				<?php
				switch ( $data_field_group['layout'] ){
				case 'table':
				?>
				<table class="tableclass tableclass_form tableclass_full data_group_fields"
				       id="data_group_fields_<?php echo $data_field_group['data_field_group_id']; ?>"
				       rel="<?php echo $data_field_group['data_field_group_id']; ?>">
					<tbody> <?php
					break;
					case 'float':
					?>
					<ul class="data_group_fields" id="data_group_fields_<?php echo $data_field_group['data_field_group_id']; ?>"
					    rel="<?php echo $data_field_group['data_field_group_id']; ?>"> <?php
						break;
						}
						foreach ( $data_fields as $data_field ) {
                    $data_field_id = $data_field['data_field_id'];

                    // depending on the tyep of field we display different outputs:
                    switch($data_field['field_type']){
                        /*case 'group':
                            // include the sub fields
                            if((int)$data_field['field_data'] && !isset($rendered_field_groups[(int)$data_field['field_data']])){
                                $data_field_group_id = (int)$data_field['field_data'];
                                $display_field_group_heading = false;
                                //ob_start();
                                $style='';
                                if($data_field['width']){
                                    $style.='width:'.$data_field['width'].'px; ';
                                }
                                if($data_field['height']){
                                    $style.='height:'.$data_field['height'].'px; ';
                                }else if(_MIN_INPUT_HEIGHT){
                                    //$style.='height:'._MIN_INPUT_HEIGHT.'px; ';
                                }
                                ?>
                                <li class="data_field data_field_<?php echo $data_field['field_type'];?><?php if($highlight||isset($custom_highlight_fields[$data_field_id]))echo ' form_field_highlight';?>" id="data_field_<?php echo $data_field_id;?>" rel="<?php echo $data_field_id;?>" style="<?php echo $style;?>">
                                <?php
                                include('render_group.php');
                                echo '</li>';
                                $display_field_group_heading = true;
                                //$extra_group[$data_field_group['data_field_group_id']] = ob_get_clean();
                            }
                            break;*/
                        default:
                            // check if there is a value for this field, and use it
                            if(isset($data_items[$data_field_id])){
                                $data_field['value'] = $data_items[$data_field_id]['data_text']; // todo, could be data_number or data_varchar as well... hmmm
                            }
                            if(isset($_POST['data_field']) && is_array($_POST['data_field']) && isset($_POST['data_field'][$data_field['data_field_id']])){
                                // this field was posted before.
                                $data_field['value'] = $_POST['data_field'][$data_field['data_field_id']];
                            }
                            if(
                                $data_field['field_type'] == 'radio' &&
                                isset($_POST['other_data_field']) &&
                                is_array($_POST['other_data_field']) &&
                                isset($_POST['other_data_field'][$data_field['data_field_id']]) &&
                                strtolower($_POST['data_field'][$data_field['data_field_id']]) == 'other'
                            ){
                                // this field was posted before.
                                $data_field['value'] = $_POST['other_data_field'][$data_field['data_field_id']];
                            }
                            /*if(isset($custom_highlight_fields[$data_field_id])){
                                $data_field['class'] = 'form_field_highlight';
                            }*/
                            $highlight = $module->is_highlight($data_field);
                            $style='';
                            if($data_field['width']){
                                $style.='width:'.$data_field['width'].'px; ';
                            }
                            if(!$view_revision_id && $data_field['height']){
                                $style.='height:'.$data_field['height'].'px; ';
                            }else if(_MIN_INPUT_HEIGHT){
                                //$style.='height:'._MIN_INPUT_HEIGHT.'px; ';
                            }
                            if(preg_match('#style="([^"]+)"#',$data_field['field_data'],$matches)){
                                $style.= $matches[1];
                            }
                            switch($data_field_group['layout']){
                                case 'table':
                                    ?> <tr class="data_field data_field_<?php echo $data_field['field_type'];?><?php if($highlight||isset($custom_highlight_fields[$data_field_id])){echo ' form_field_highlight';}?>" id="container_data_field_<?php echo $data_field_id;?>" rel="<?php echo $data_field_id;?>" style="<?php echo $style;?>">
                                        <th class="width2">
                                    <?php
                                    break;
                                case 'float':
                                    ?> <li class="data_field data_field_<?php echo $data_field['field_type'];?><?php if($highlight||isset($custom_highlight_fields[$data_field_id])){echo ' form_field_highlight';}?>" id="container_data_field_<?php echo $data_field_id;?>" rel="<?php echo $data_field_id;?>" style="<?php echo $style;?>">
                                        <span class="data_field_title">
                                    <?php
                                    break;
                            }

                                if($mode=='admin'){ ?> <a href="<?php echo $module->link('',array(
                                "data_type_id"=>$data_field['data_type_id'],
                                "data_field_group_id"=>$data_field['data_field_group_id'],
                                "data_field_id"=>$data_field['data_field_id'],
                                ));?>" target="_blank"><?php echo $data_field['title'];?></a> <?php }else{ ?> <?php echo $data_field['title'];?> <?php }

                            switch($data_field_group['layout']){
                                case 'table':
                                    ?> </th><td>
                                    <?php
                                    break;
                                case 'float':
                                    ?> </span>
                                        <span class="data_field_input">
                                    <?php
                                    break;
                            }
                            $has_write_access = isset($embed_form) || $view_revision_id;
                            echo $module->get_form_element($data_field,$has_write_access,isset($data_record) ? $data_record : array());

                            switch($data_field_group['layout']){
                                case 'table':
                                    ?>
                                    <span class="data_field_action"></span></td>
                                    <?php
                                    break;
                                case 'float':
                                    ?>
                                    </span>
                                    <span class="data_field_action"></span>
                                    <?php
                                    break;
                            }


                            // now some javascript if this field is set to display other groups if it's selected.
                            if($data_field['display_group_if']){
                                ?>
                                <script type="text/javascript">
                                var field_value_<?php echo $data_field_id;?> = '<?php echo (isset($data_field['value'])) ? $data_field['value'] : '';?>';
                                function change_group_<?php echo $data_field_id;?>(){
                                    <?php
                                    foreach(explode(',',$data_field['display_group_if']) as $display_group_if){
                                        $foo = explode('|',$display_group_if);
                                        // work out if we're displaying this gruop on load or not.
                                        if(isset($data_field['value']) && $data_field['value'] == $foo[0]){
                                            $hide_show_group_on_load[$foo[1]] = true;
                                        }else if($data_field['value'] && $data_field['field_type'] == 'radio' && $foo[0] == 'Other'){
                                            // work out of the "Other" option is currently selected.
                                            foreach(explode("\n",trim($data_field['field_data'])) as $line){
                                                $line = trim($line);
                                                if(preg_match('/^attributes=/',$line)){
                                                    if(preg_match('/Other$/',$line)){
                                                        // we have an 'other' option!
                                                        $line = preg_replace('/^attributes=/','',$line);
                                                        $attributes = explode("|",$line);
                                                        // if this value isn't one of the attributes, then we assume it's an other
                                                        // and we mark this value as dispalyed!! for the next group .
                                                        $attribute_exists = false;
                                                        foreach($attributes as $attribute){
                                                            if($attribute == $data_field['value']){
                                                                $attribute_exists = true;
                                                            }
                                                        }
                                                        if(!$attribute_exists){
                                                            $hide_show_group_on_load[$foo[1]] = true;
                                                        }
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        if(field_value_<?php echo $data_field_id;?> == '<?php echo ($foo[0])?$foo[0]:'';?>'){
                                            $('#data_group_<?php echo $foo[1];?>').show();
                                            $('#data_group_<?php echo $foo[1];?>').removeClass('data_group_hidden');
                                            $('#save_group_<?php echo $foo[1];?>').val('true');
                                        }else{
                                            $('#data_group_<?php echo $foo[1];?>').hide();
                                            $('#data_group_<?php echo $foo[1];?>').addClass('data_group_hidden');
                                            $('#save_group_<?php echo $foo[1];?>').val('');
                                        }
                                    <?php
                                    }
                                ?>
                                }
                                $(function(){
                                    var has_radio = false;
                                    $('#data_field_<?php echo $data_field_id;?> input').change(function(){
                                        if($(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio'){
                                            has_radio = true;// to stop the other option taking over on change.
                                            if(this.checked){
                                                field_value_<?php echo $data_field_id;?> = this.value;
                                            }
                                        }else if(!has_radio){
                                            field_value_<?php echo $data_field_id;?> = this.value;
                                        }
                                        change_group_<?php echo $data_field_id;?>();
                                    });
                                    $('#data_field_<?php echo $data_field_id;?> input').each(function(){
                                        if($(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio'){
                                            if(this.checked){
                                                field_value_<?php echo $data_field_id;?> = this.value;
                                                change_group_<?php echo $data_field_id;?>();
                                            }
                                        }/*else{
                                            field_value_<?php echo $data_field_id;?> = this.value;
                                        }*/
                                    });
                                });
                                </script>
                            <?php
                            }// display group if

                            switch($data_field_group['layout']){
                                case 'table':
                                    ?>
                                    </tr>
                                    <?php
                                    break;
                                case 'float':
                                    ?>
                                    <br class="clear"/>
                                    </li>
                                    <?php
                                    break;
                            }

                    }

                }

						switch ( $data_field_group['layout'] ){
						case 'table':
						?>
					</tbody>
				</table>
			<?php
			break;
			case 'float':
				?>
				</ul>
				<?php
				break;
			}
			?>

			</div>
			<?php

			$fieldset_data = array(
				'heading'         => false,
				'elements_before' => ob_get_clean(),
			);
			if ( $display_field_group_heading ) {
				$fieldset_data['heading'] = array(
					'title' => htmlspecialchars( $data_field_group['title'] ),
					'type'  => 'h3',
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
		?>
	</div>

<?php } ?>