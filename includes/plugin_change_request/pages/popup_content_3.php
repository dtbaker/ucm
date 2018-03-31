<h2><?php _e('3. Describe the change you would like to make');?></h2>
<p><?php _e('Please explain clearly the change you would like to make.');?></p>
<form action="<?php echo module_change_request::link_popup($website_id);?>&type=save" method="post" enctype="multipart/form-data" id="change_request_submit_form">


    <input type="hidden" name="change_id" value="<?php echo isset($change_request['change_request_id']) ? $change_request['change_request_id'] : 0;?>">
    <input type="hidden" name="x" value="<?php echo isset($change_request['x']) ? $change_request['x'] : 0;?>">
    <input type="hidden" name="y" value="<?php echo isset($change_request['y']) ? $change_request['y'] : 0;?>">
    <input type="hidden" name="window_width" value="<?php echo isset($change_request['window_width']) ? $change_request['window_width'] : 0;?>">
    <input type="hidden" name="url" value="<?php echo isset($change_request['url']) ? htmlspecialchars($change_request['url']) : '';?>">

    <table class="wp3changerequest_table" width="100%">
        <tr>
            <th nowrap="nowrap" class="tour_4">
                <?php _e('Your Request:');?>
            </th>
            <td>
	            <?php if(isset($change_request['job_id']) && $change_request['job_id']){
		            echo htmlspecialchars($change_request['request']);
	            }else{ ?>
                    <textarea rows="5" cols="30" name="request" style="width:100%;" class="wp3changerequest_input"><?php echo htmlspecialchars($change_request['request']);?></textarea>
	            <?php } ?>
            </td>
        </tr>
        <!-- <tr>
            <th>
                <?php _e('Attachments:');?>
            </th>
            <td>
                <ul>
                    <?php foreach($change_request['attachments'] as $attachment){ ?>
                    <li class="dtbaker_change_request_attachment">
                        <input type="hidden" name="existing_attachments[<?php echo $attachment['file_id'];?>]" value="yes">
                        <a href="#"><?php echo htmlspecialchars($attachment->name);?></a> - <a href="#" onclick="jQuery(this).parent().remove(); return false;"><?php _e('Remove');?></a>
                    </li>
                    <?php } ?>
                </ul>
                <div id="dtbaker_change_request_attachments">
                    <div class="dynamic_block">
                        <input type="file" name="attach[]" class="wp3changerequest_input">
                        <a href="#" onclick="selrem(this,'dtbaker_change_request_attachments'); return false;" class="remove_addit"><?php _e('Remove');?></a>
                        <a href="#" onclick="seladd(this,'dtbaker_change_request_attachments'); return false;" class="add_addit"><?php _e('+ Add another');?></a>
                    </div>
                </div>
            </td>
        </tr> -->
        <tr>
            <th class="tour_4b">
                <?php _e('Your Name:');?>
            </th>
            <td>
	            <?php if(isset($change_request['job_id']) && $change_request['job_id']){
		            echo htmlspecialchars($change_request['name']);
	            }else{ ?>
                <input type="text" name="name" class="wp3changerequest_input" value="<?php echo htmlspecialchars($change_request['name']);?>">
	            <?php } ?>
            </td>
        </tr>
    </table>

    <?php if($change_id && module_security::is_logged_in() && module_change_request::can_i('edit','Change Requests')){ ?>
    <div class="wp3changerequest_message_box">
        <table>
            <tr>
                <th>
                    <?php _e('Completed?');?>
                </th>
                <td>
                    <input type="hidden" name="completed_test" value="1">
                    <input type="checkbox" id="change_completed" name="completed" value="1">
                    <label for="change_completed"><?php _e('Yes, this change has been completed.');?></label>
                    <br/>
                    <input type="checkbox" id="change_completed_email" name="completed_send_email" value="1">
                    <label for="change_completed_email"><?php _e('Email client letting them know this change has been completed.');?></label>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <?php } ?>
    <p>
        <?php if($change_id){ ?>
	        <?php if(!isset($change_request['job_id']) || !$change_request['job_id']){ ?>
        <input type="submit" name="delete_request" value="<?php _e('Delete');?>" class="wp3changerequest_button wp3changerequest_button_cancel" onclick="return confirm('<?php _e('Really delete this?');?>');">
		        <?php } ?> <input type="submit" name="submit_request" value="<?php _e('Save Changes &raquo;');?>" class="wp3changerequest_button tour_5">
        <?php }else{ ?>
        <input type="submit" name="submit_request" value="<?php _e('Submit Request &raquo;');?>" class="wp3changerequest_button tour_5">
        <?php } ?>
        <input type="button" name="wp3changerequest_back" class="wp3changerequest_back wp3changerequest_button wp3changerequest_button_cancel" value="<?php _e('Cancel');?>">
    </p>
</form>
<p><em><?php _e("Don't worry if you make a mistake, you can cancel or change this request later.");?></em></p>
