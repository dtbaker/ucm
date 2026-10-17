<?php


$file_id = (int)$_REQUEST['file_id'];
$file = module_file::get_file($file_id);


?>



<?php module_form::print_form_open_tag( array(
	'action'  => '',
	'enctype' => 'multipart/form-data',
) ); ?>
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



					<?php
					$job = module_job::get_job( $file['job_id'] );
					echo module_form::generate_fieldset( array(
						'class'    => 'tableclass tableclass_form tableclass_full',
						'elements' => array(
							'file'   => array(
								'title'  => _l( 'File' ),
								'fields' => array(
									array(
										'type'  => 'file',
										'name'  => 'file_upload',
										'style' => 'width:150px;',
									),
									'<a href="' . htmlspecialchars( $module->link( 'file_edit', array(
										'_process' => 'download',
										'file_id'  => $file['file_id'],
									), 'file', false ) ) . '">'
									. nl2br( htmlspecialchars( $file['file_name'] ) ) . '</a>',
								),
							),
							'status' => array(
								'title'  => _l( 'Status' ),
								'fields' => array( htmlspecialchars( $file['status'] ) ),
							),
							'job'    => array(
								'title'  => _l( 'Job' ),
								'fields' => array( htmlspecialchars( $job['name'] ) ),
							),
						),
					) );
					?>
				</td>
				<td valign="top" width="50%">

					<h3><?php echo _l('File Description'); ?></h3>

					<?php echo module_form::generate_fieldset( array(
						'class'    => 'tableclass tableclass_form tableclass_full',
						'elements' => array(
							'description' => array(
								'field' => array(
									'type'  => 'textarea',
									'name'  => 'description',
									'rows'  => 4,
									'cols'  => 50,
									'style' => 'width:100%;',
									'value' => $file['description'],
								),
							),
						),
					) ); ?>
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


<?php module_form::print_form_close_tag();
