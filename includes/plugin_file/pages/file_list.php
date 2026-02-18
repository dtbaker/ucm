<?php 

if(!isset($display_summary)){
	$display_summary = false;
}
//print_r($file_items);exit;

// we need a unique each js function.
// can have multiple per page.

if($editable){
?>


<div id="new_file_popup<?php echo $owner_table;?>_<?php echo $owner_id;?>" title="File Attachment">
	<div class="modal_inner"></div>
</div>


<script type="text/javascript">
	function link_file_edit<?php echo $owner_table;?>_<?php echo $owner_id;?>(){
		$('.file_edit<?php echo $owner_table;?>_<?php echo $owner_id;?>')
		.click(function(){
			edit_file_id = $(this).attr('rel');
			$('#new_file_popup<?php echo $owner_table;?>_<?php echo $owner_id;?>').dialog('open');
			return false;
		});
	}
	function new_file_added<?php echo $owner_table;?>_<?php echo $owner_id;?>(file_id,owner_table,owner_id,new_html){
		if($('.file_'+file_id).length>0){
			$('.file_'+file_id).replaceWith(new_html);
		}else{
			$('.file_'+owner_table+'_'+owner_id).append(new_html);
		}
		// close parent popup
		$('#new_file_popup<?php echo $owner_table;?>_<?php echo $owner_id;?>').dialog('close');
		link_file_edit<?php echo $owner_table;?>_<?php echo $owner_id;?>();
	}
	var edit_file_id = 'new';
	var edit_file_changed = false;
	$(function(){
		$("#new_file_popup<?php echo $owner_table;?>_<?php echo $owner_id;?>").dialog({
			autoOpen: false,
			width: 400,
			height: 300,
			modal: true,
			buttons: {
				'Save': function() {
					$('form',this)[0].submit();
				},
				Cancel: function() {
					$(this).dialog('close');
				}
			},
			open: function(){
				var t = this;
				$.ajax({
					type: "GET",
					url: '<?php echo $plugins['file']->link('file_edit',array(
                        'layout' => $layout_type,
                        'owner_table' => $owner_table,
                        'owner_id' => $owner_id,
                    ));?>&file_id='+edit_file_id+'&options=<?php echo base64_encode(serialize($options)) ?>',
					dataType: "html",
					success: function(d){
						$('.modal_inner',t).html(d);
						$('.redirect',t).val(window.location.href);
						load_calendars();
					}
				});
			},
			close: function() {
				$('.modal_inner',this).html('');
			}
		});
		$('.file_add<?php echo $owner_table;?>_<?php echo $owner_id;?>')
		.click(function(){
			edit_file_id = 'new';
			$('#new_file_popup<?php echo $owner_table;?>_<?php echo $owner_id;?>').dialog('open');
			return false;
		});
		link_file_edit<?php echo $owner_table;?>_<?php echo $owner_id;?>();
	});
</script>

<?php

}

switch($layout_type){
	case 'gallery':
        ?>

		<?php
		if($title){
			?>
			<h3>
				<?php if($editable){ ?>
				<span class="button">
					<a href="#" class="submit_button file_add<?php echo $owner_table;?>_<?php echo $owner_id;?>">Add new</a>
				</span>
				<?php } ?>
				<span class="title">
					<?php echo $title;?>
				</span>
			</h3>
			<?php
		}else{
	        if($editable){
			?>
                <input type="button" class="file_add<?php echo $owner_table;?>_<?php echo $owner_id;?> small_button" value="<?php _e('Add New');?>">
			<?php
			}
		}
		?>
        <div class="file_<?php echo $owner_table;?>_<?php echo $owner_id;?>">
	        <?php
	        foreach($file_items as $n){
		        echo $n['html'];
	        }
	        ?>
        </div>
		        
		<?php
        break;
	case 'list':
        if($editable){ ?>
            <input type="button" class="file_add<?php echo $owner_table;?>_<?php echo $owner_id;?> small_button" value="<?php _e('Upload');?>">
        <?php
		}
        ?>
        <div class="file_<?php echo $owner_table;?>_<?php echo $owner_id;?>">
	        <?php
	        foreach($file_items as $n){
		        echo $n['html'];
            echo '<br/>';
	        }
	        ?>
        </div>
		<?php
        break;
}
?>