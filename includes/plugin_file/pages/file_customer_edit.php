<?php


$file_id = (int)$_REQUEST['file_id'];
$file = module_file::get_file($file_id);


?>



<form action="" method="post" enctype="multipart/form-data">
	<?php module_form::print_form_auth(); ?>
	<input type="hidden" name="_process" value="save_file" />
    <input type="hidden" name="file_id" value="<?php echo $file_id; ?>" />


    <?php

    $fields = array(
    'fields' => array(
        'url' => 'Name',
    ));
    module_form::set_required(
        $fields
    );
    module_form::prevent_exit(array(
        'valid_exits' => array(
            // selectors for the valid ways to exit this form.
            '.submit_button',
        ))
    );


    ?>

	<table cellpadding="10" width="100%">
		<tbody>
			<tr>
				<td valign="top" width="50%">
					<h3><?php echo _l('File Details'); ?></h3>



					<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
						<tbody>

							<tr>
								<th class="width1">
									<?php echo _l('File'); ?>
								</th>
								<td>
									<input type="file" name="file_upload" style="width:150px;">
                                    <a href="<?php echo $module->link('file_edit',array('_process' => 'download','file_id' => $file['file_id']),'file',false);?>"><?php echo nl2br(htmlspecialchars($file['file_name']));?></a>
								</td>
							</tr>
							<tr>
								<th>
									<?php echo _l('Status'); ?>
								</th>
								<td>
									<?php echo htmlspecialchars($file['status']); ?>
								</td>
							</tr>
							<tr>
								<th>
									<?php echo _l('Job'); ?>
								</th>
								<td>
                                    <?php
                                    $job = module_job::get_job($file['job_id']);
                                    echo htmlspecialchars($job['name']);
                                    ?>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td valign="top" width="50%">

					<h3><?php echo _l('File Description'); ?></h3>

					<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
						<tbody>
							<tr>
								<td>
									<textarea name="description" rows="4" cols="50" style="width:100%;"><?php echo htmlspecialchars($file['description']);?></textarea>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l('Save file'); ?>" class="submit_button save_button" />
					<?php if ((int)$file_id) { ?>
					<input type="submit" name="butt_del" id="butt_del" value="<?php echo _l('Delete'); ?>" class="submit_button delete_button" />
					<?php } ?>
					<input type="button" name="cancel" value="<?php echo _l('Cancel'); ?>" onclick="window.location.href='<?php echo module_file::link_open(false); ?>';" class="submit_button" />
				</td>
			</tr>
		</tbody>
	</table>


</form>