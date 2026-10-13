<?php

// These are sections of the Files screen rather than pages of their own, and file_admin.php
// sets this before including them. Reaching one directly used to warn about the undefined
// variable first and then say "wrong page", so the warning was the only thing a person saw.
if ( empty( $file_safe ) ) {
	die( 'wrong page' );
}


if ($file_id > 0) {
    $module->page_title = _l('File Bucket: %s',$file['file_name']);
	$ucm_file->has_viewed();
} else {
    $module->page_title = _l('File Bucket: %s',_l('New'));
}

if (!isset($file['customer_id']) || !$file['customer_id']) { $file['customer_id'] = false; // helps with drop downs below.
}

$type = $ucm_file->get_type();

?>
	
<?php module_form::print_form_open_tag( array( 'action' => '' ) ); ?>
	<input type="hidden" name="_process" value="save_file" class="no_permissions" />
    <input type="hidden" name="file_id" value="<?php echo $file_id; ?>" class="no_permissions" />
    <input type="hidden" name="bucket" value="1" class="no_permissions" />

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



    hook_handle_callback('layout_column_half',1);

    $fieldset_data = array(
        'heading' => array(
            'type' => 'h3',
            'title' => 'File Bucket Details',
        ),
        'class' => 'tableclass tableclass_form tableclass_full file_drop',
        'elements' => array(),
        'extra_settings' => array(
            'owner_table' => 'file',
            'owner_key' => 'file_id',
            'owner_id' => $file_id,
            'layout' => 'table_row',
             'allow_new' => module_file::can_i('create','Files'),
             'allow_edit' => module_file::can_i('create','Files'),
        ),
    );
    $fieldset_data['elements'][] = array(
        'title' => 'Bucket Name',
        'field' => array(
            'type' => 'text',
            'name' => 'file_name',
            'value' => $file['file_name'],
            'help' => 'A File Bucket is a collection of files. The customer can easily upload files to this bucket.'
        ),
    );
    $fieldset_data['elements'][] = array(
	    'title' => 'Status',
	    'field' => array(
	        'type' => 'select',
	        'name' => 'status',
	        'value' => $file['status'],
		    'options' => module_file::get_statuses(),
		    'allow_new' => true,
	    ),
	);
    if (class_exists('module_customer',false)) {
	    $c = array();
        $res = module_customer::get_customers();
        foreach ($res as $row) {
            $c[$row['customer_id']] = $row['customer_name'];
        }
        if ($file['customer_id'] && !isset($c[$file['customer_id']])) {
            // this file is related to another job. from another customer.
            $related_customer = module_customer::get_customer($file['customer_id'],true);
            $c[$file['customer_id']] = $related_customer['customer_name'];
        }
	    $fieldset_data['elements'][] = array(
		    'title' => 'Customer',
		    'field' => array(
		        'type' => 'select',
		        'name' => 'customer_id',
		        'value' => $file['customer_id'],
			    'options' => $c,
		    ),
		);
    }
    if (class_exists('module_job',false) && class_exists('module_customer',false)) {
	    $c = array();
        $res = module_job::get_jobs(array('customer_id' => $file['customer_id']));
        foreach ($res as $row) {
            $c[$row['job_id']] = $row['name'];
        }
        if ($file['job_id'] && !isset($c[$file['job_id']])) {
            // this file is related to another job. from another customer.
            $related_job = module_job::get_job($file['job_id'],false,true);
            if ($related_job && $related_job['job_id'] == $file['job_id']) {
                $related_customer = module_customer::get_customer($related_job['customer_id'],true);
                if ($related_customer && $related_customer['customer_id'] == $related_job['customer_id']) {
                    $c[$file['job_id']] = _l('%s (from %s)',$related_job['name'],$related_customer['customer_name']);
                } else {
                    $file['job_id'] = false;
                }
            } else {
                $file['job_id'] = false;
            }
        }
	    $fieldset_data['elements'][] = array(
		    'title' => 'Job',
		    'fields' => array(
			    array(
			        'type' => 'select',
			        'name' => 'job_id',
			        'value' => $file['job_id'],
				    'options' => $c,
			    ),
			    function () use (&$file) {
				    if ($file['job_id']) {
                        echo ' ';
                        echo '<a href="' . module_job::link_open($file['job_id'],false) . '">' . _l('Open Job &raquo;') . '</a>';
                    }
			    }
		    ),
		);
    }
    if (class_exists('module_quote',false) && module_quote::is_plugin_enabled()) {
	    $c = array();
        $res = module_quote::get_quotes(array('customer_id' => $file['customer_id']));
        foreach ($res as $row) {
            $c[$row['quote_id']] = $row['name'];
        }
        if ($file['quote_id'] && !isset($c[$file['quote_id']])) {
            // this file is related to another quote. from another customer.
            $related_quote = module_quote::get_quote($file['quote_id'],false,true);
            if ($related_quote && $related_quote['quote_id'] == $file['quote_id']) {
                $related_customer = module_customer::get_customer($related_quote['customer_id'],true);
                if ($related_customer && $related_customer['customer_id'] == $related_quote['customer_id']) {
                    $c[$file['quote_id']] = _l('%s (from %s)',$related_quote['name'],$related_customer['customer_name']);
                } else {
                    $file['quote_id'] = false;
                }
            } else {
                $file['quote_id'] = false;
            }
        }
	    $fieldset_data['elements'][] = array(
		    'title' => 'Quote',
		    'fields' => array(
			    array(
			        'type' => 'select',
			        'name' => 'quote_id',
			        'value' => $file['quote_id'],
				    'options' => $c,
			    ),
			    function () use (&$file) {
				    if ($file['quote_id']) {
                        echo ' ';
                        echo '<a href="' . module_quote::link_open($file['quote_id'],false) . '">' . _l('Open Quote &raquo;') . '</a>';
                    }
			    }
		    ),
		);
    }

    $staff_members = module_user::get_staff_members();
	$staff_member_rel = array();
	foreach ($staff_members as $staff_member) {
	    $staff_member_rel[$staff_member['user_id']] = $staff_member['name'];
	}
	if (!isset($file['staff_ids']) || !is_array($file['staff_ids']) || !count($file['staff_ids'])) {
	    $file['staff_ids'] = array(false);
	    if (count($staff_member_rel) == 1) {
	        $file['staff_ids'] = array(key($staff_member_rel));
	    }
	}
    $fieldset_data['elements'][] = array(
	    'title' => 'Staff',
	    'fields' => array(
		    array(
		        'type' => 'hidden',
		        'name' => 'staff_ids_save',
		        'value' => 1,
		    ),
		    '<div id="staff_ids_holder" style="float:left;">',
		    array(
                'type' => 'select',
                'name' => 'staff_ids[]',
                'options' => $staff_member_rel,
                'multiple' => 'staff_ids_holder',
                'values' => $file['staff_ids'],
            ),
		    '</div>',
		    function () use (&$file) {
			    if ($file['quote_id']) {
                    echo ' ';
                    echo '<a href="' . module_quote::link_open($file['quote_id'],false) . '">' . _l('Open Quote &raquo;') . '</a>';
                }
		    },
		    array(
			    'type' => 'html',
			    'value' => '',
			    'help' => 'Assign a staff member to this file. Staff members are users who have EDIT permissions on Job Tasks. Click the plus sign to add more staff members. You can apply the "Only Assigned Staff" permission in User Role settings to restrict staff members to these files.<br><br>If there are assigned staff members then those members will be the only ones to receive notifications when a change is made to the file. If no staff are assigned to this file then anyone with the "Receive Alerts" permission will receive file change/comment alerts.',
		    )
	    ),
	);
    echo module_form::generate_fieldset($fieldset_data);
    unset($fieldset_data);


    hook_handle_callback('layout_column_half',2);



    $fieldset_data = array(
        'heading' =>  array(
            'title' => _l('Bucket Description'),
            'type' => 'h3',
        ),
        'elements' => array(
            array(
                'field' => array(
                    'type' => 'wysiwyg',
                    'name' => 'description',
                    'value' => $file['description'],
                )
            )
        )
    );
    echo module_form::generate_fieldset($fieldset_data);
    unset($fieldset_data);

    if ((int)$file_id > 0) {
        if ( module_file::can_i( 'edit', 'Files' ) ) {
            module_email::display_emails( array(
                'title'  => 'File Emails',
                'search' => array(
                    'file_id' => $file_id,
                )
            ) );
        }

        ob_start();
        $search                          = array();
        $search['bucket_parent_file_id'] = $file_id;
        $files                           = module_file::get_files( $search );

        $table_manager               = module_theme::new_table_manager();
        $columns                     = array();
        $columns['file_name']        = array(
            'title'      => 'File Name',
            'callback'   => function ( $file ) {
                echo module_file::link_open( $file['file_id'], true );
                if ( isset( $file['file_url'] ) && strlen( $file['file_url'] ) ) {
                    echo ' ';
                    echo '<a href="' . htmlspecialchars( $file['file_url'] ) . '">' . htmlspecialchars( $file['file_url'] ) . '</a>';
                }
            },
            'cell_class' => 'row_action',
        );
        $columns['file_description'] = array(
            'title'    => 'Description',
            'callback' => function ( $file ) {
                echo module_security::purify_html( $file['description'] );
            },
        );
        $columns['file_status']      = array(
            'title'    => 'Status',
            'callback' => function ( $file ) {
                echo nl2br( htmlspecialchars( $file['status'] ) );
            },
        );
        $columns['file_size']        = array(
            'title'    => 'File Size',
            'callback' => function ( $file ) {
                if ( file_exists( $file['file_path'] ) ) {
                    echo module_file::format_bytes( filesize( $file['file_path'] ) );
                }
            },
        );
        $columns['file_date_added']  = array(
            'title'    => 'Date Added',
            'callback' => function ( $file ) {
                echo _l( '%s by %s', print_date( $file['date_created'] ), module_user::link_open( $file['create_user_id'], true ) );
            },
        );

        if ( class_exists( 'module_extra', false ) ) {
            $table_manager->display_extra( 'file', function ( $file ) {
                module_extra::print_table_data( 'file', $file['file_id'] );
            } );
        }
        $table_manager->set_columns( $columns );
        $table_manager->row_callback = function ( $row_data ) {
            // load the full file data before displaying each row so we have access to more details
            if ( isset( $row_data['file_id'] ) && (int) $row_data['file_id'] > 0 ) {
                // not needed in this case
                //return module_file::get_file($row_data['file_id']);
            }

            return array();
        };
        $table_manager->set_rows( $files );

        $table_manager->pagination = false;
        $table_manager->print_table();

        $buttons = array();
        $buttons[] = array(
                'url'   => module_file::link_open( 'new', false, $file_id ),
                'title' => _l( 'Add New File' ),
                'type'  => 'add',
            );
        if (count($files)) {

            $buttons[] = array(
                'url'   => module_file::link_public_download_bucket( $file_id ),
                'title' => _l( 'Download All' ),
                'type'  => 'button',
            );
        }

        $fieldset_data = array(
            'heading'         => array(
                'title'  => _l( 'Bucket Files' ),
                'type'   => 'h3',
                'button' => $buttons
            ),
            'elements_before' => ob_get_clean()
        );
        echo module_form::generate_fieldset( $fieldset_data );
        unset( $fieldset_data );
    }

    hook_handle_callback('layout_column_half','end');

    $form_actions = array(
        'class' => 'action_bar action_bar_center action_bar_single',
        'elements' => array(
            array(
                'type' => 'save_button',
                'name' => 'butt_save',
                'id' => 'butt_save',
                'value' => _l('Save Bucket'),
            ),
            array(
                'ignore' => !((int)$file_id && module_file::can_i('delete','Files')),
                'type' => 'delete_button',
                'name' => 'butt_del',
                'value' => _l('Delete'),
            ),
            array(
                'type' => 'button',
                'name' => 'cancel',
                'value' => _l('Cancel'),
                'class' => 'submit_button',
                'onclick' => "window.location.href='" . module_file::link_open(false) . "';",
            ),
        ),
    );
    echo module_form::generate_form_actions($form_actions);


    if ((int)$file_id > 0 && isset($file['file_path']) && $file['file_path'] && is_file($file['file_path']) && module_file::can_i('view','File Comments')) {   ?>
        <h2><?php echo _l('File Comments'); ?></h2>

        <div>
            <div style="width:70%;float:left;border:1px solid #EFEFEF; overflow:auto;" id="file_preview">
                <?php
                echo module_file::generate_preview($file_id,$file['file_name'],$file);
                 ?>

            </div>
            <div style="width:29%; float:right;" id="file_notes">
                <div class="tableclass" style="margin:0 0 10px 0">
                    <?php if (module_file::can_i('create','File Comments')) { ?>
                    <textarea name="new_comment_text" style="width:98%;" class="no_permissions"></textarea>
                    <?php } ?>
                    <div style="text-align:right;">
                        <?php if (module_file::can_i('create','File Comments')) { ?>
                        <input type="submit" name="butt_save_note" id="butt_save_note" value="<?php echo _l('Add Comment'); ?>" class="submit_button no_permissions">
                        <?php } ?>
                        <input type="hidden" name="delete_file_comment_id" id="delete_file_comment_id" value="0" class="no_permissions">
                    </div>
                </div>
                <?php foreach (module_file::get_file_comments($file_id) as $item) {
                $note_text = forum_text($item['comment']);
                if (preg_match_all('/#(\d+)/',$note_text,$matches)) {
                    //
                    foreach ($matches[1] as $digit) {
                        $note_text = preg_replace('/#' . $digit . '([^\d]*)/','<span node_id=' . $digit . ' class="pointer-ids pointer-id-' . $digit . '">#' . $digit . '</span>$1',$note_text);
                    }
                }
                ?>
                <div style="border-top:1px dashed #CCCCCC; padding:3px; margin:3px 0;">
                    <?php echo $note_text; ?>
                    <div style="font-size:10px; text-align:right; color:#CCCCCC;">From <?php echo $item['create_user_id'] ? module_user::link_open($item['create_user_id'],true) : _l('Customer'); ?> on <?php echo print_date($item['date_created'],true); ?>
                        <?php if (module_file::can_i('delete','File Comments') || $item['create_user_id'] == module_security::get_loggedin_id()) { ?>
                        <a href="#" onclick="if(confirm('<?php echo _l('Really remove this comment?'); ?>')){$('#delete_file_comment_id').val('<?php echo $item['file_comment_id']; ?>'); $('#butt_save_note').click(); } return false;" style="color:#FF0000">x</a>
                        <?php } ?>
                    </div>
                </div>
                <?php
            }
                ?>
            </div>
            <div class="clear"></div>
        </div>
        <?php } ?>


</form>