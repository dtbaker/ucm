<?php

if ( ! $job_safe ) {
	die( 'denied' );
}

$job_task_creation_permissions = module_job::get_job_task_creation_permissions();

$job_id = (int) $_REQUEST['job_id'];
$job    = module_job::get_job( $job_id );
$job_id = (int) $job['job_id'];
$UCMJob = UCMJob::singleton( $job_id );

if ( ! $job['hourly_rate'] ) {
	$job['hourly_rate'] = 0;
}
$staff_members    = module_user::get_staff_members();
$staff_member_rel = array();
foreach ( $staff_members as $staff_member ) {
	$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
}

if ( $job_id > 0 && $job['job_id'] == $job_id ) {
	$module->page_title = _l( 'Job: %s', $job['name'] );
	if ( function_exists( 'hook_handle_callback' ) ) {
		hook_handle_callback( 'timer_display', 'job', $job_id );
	}
} else {
	$module->page_title = _l( 'Job: %s', _l( 'New' ) );
}

// check permissions.
if ( class_exists( 'module_security', false ) ) {
	module_security::sanatise_data( 'job', $job );
}

$job_tasks = module_job::get_tasks( $job_id );

if ( class_exists( 'module_import_export', false ) ) {
	if ( module_job::can_i( 'view', 'Export Job Tasks' ) ) {
		module_import_export::enable_pagination_hook(
		// what fields do we pass to the import_export module from this job tasks?
			array(
				'name'   => 'Job Tasks Export',
				'fields' => array(
					'Job Name'          => 'job_name',
					'Task ID'           => 'task_id',
					'Order'             => 'task_order',
					'Short Description' => 'description',
					'Long Description'  => 'long_description',
					'Hours'             => 'hours',
					'Hours Completed'   => 'completed',
					'Amount'            => 'amount',
					'Billable'          => 'billable',
					'Fully Completed'   => 'fully_completed',
					'Date Due'          => 'date_due',
					'Invoice #'         => 'invoice_number',
					'Staff Member'      => 'user_name',
					'Approval Required' => 'approval_required',
				),
			)
		);
		if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
			// do the task export.
			module_import_export::run_pagination_hook( $job_tasks );
		}
	}
	if ( module_job::can_i( 'view', 'Import Job Tasks' ) ) {
		$import_tasks_link = module_import_export::import_link(
			array(
				'callback'   => 'module_job::handle_import_tasks',
				'name'       => 'Job Tasks',
				'job_id'     => $job_id,
				'return_url' => $_SERVER['REQUEST_URI'],
				'fields'     => array(
					//'Job Name' => 'job_name',
					'Task ID'           => array(
						'task_id',
						false,
						'The existing system ID for this task. Will overwrite existing task ID. Leave blank to create new task.'
					),
					'Order'             => array( 'task_order', false, 'The numerical order the tasks will appear in.' ),
					'Short Description' => array( 'description', true ),
					'Long Description'  => 'long_description',
					'Hours'             => 'hours',
					'Hours Completed'   => 'completed',
					'Amount'            => 'amount',
					'Billable'          => array( 'billable', false, '1 for billable, 0 for non-billable' ),
					'Fully Completed'   => array( 'fully_completed', false, '1 for fully completed, 0 for not completed' ),
					'Date Due'          => array( 'date_due', false, 'When this task is due for completion' ),
					//'Invoice #' => 'invoice_number',
					'Staff Member'      => array( 'user_name', false, 'One of: ' . implode( ', ', $staff_member_rel ) ),
					'Approval Required' => array(
						'approval_required',
						false,
						'1 if the administrator needs to approve this task, 0 if it does not require approval'
					),
				),
			)
		);
	}
}

?>

<script type="text/javascript">

    $(function () {
        if (typeof ucm.job != 'undefined') {
            ucm.job.ajax_task_url = '<?php echo module_job::link_ajax_task( $job_id, false ); ?>';
					<?php if(module_invoice::can_i( 'create', 'Invoices' )){ ?>
            ucm.job.create_invoice_popup_url = '<?php echo module_job::link_create_job_invoice( $job_id, false ); ?>';
            ucm.job.create_invoice_url = '<?php echo module_invoice::link_generate( 'new', array( 'arguments' => array( 'job_id' => $job_id, ) ) ); ?>';
					<?php } ?>
            ucm.job.init();
        }
    });
    var completed_tasks_hidden = false; // set with session variable / cookie
    var editing_task_id = false;
    var loading_task_html = '<tr class="task_edit_loading"><td colspan="9" align="center"><?php _e( 'Loading...' );?></td></tr>';

    function show_completed_tasks() {
        $('.tasks_completed').show();
        $('#show_completed_tasks').hide();
        $('#hide_completed_tasks').show();
        set_task_numbers();
        Set_Cookie('job_tasks_hide', 'no');
        return true;
    }

    function hide_completed_tasks() {
        $('.tasks_completed').hide();
        $('#show_completed_tasks').show();
        $('#hide_completed_tasks').hide();
        set_task_numbers();
        Set_Cookie('job_tasks_hide', 'yes');
        return true;
    }

    function setamount(a, task_id) {
        var amount = 0;
        if (a.match(/:/)) {
            var bits = a.split(':');
            var hours = bits[0].length > 0 ? parseInt(bits[0]) : 0;
            var minutes = 0;
            if (typeof bits[1] != 'undefined' && bits[1].length > 0) {
                if (bits[1].length == 1) {
                    // it's a 0 or a 123456789
                    if (bits[1] == "0") {
                        minutes = 0;
                    } else {
                        minutes = parseInt(bits[1] + "0");
                    }
                } else {
                    minutes = parseInt(bits[1]);
                }
            }
            if (hours > 0 || minutes > 0) {
                amount = <?php echo $job['hourly_rate'];?> *
                hours;
                amount += <?php echo $job['hourly_rate'];?> *
                (minutes / 60);
            }
        } else {
            var bits = a.split('<?php echo module_config::c( 'currency_decimal_separator', '.' );?>');
            var number = bits[0].length > 0 ? parseInt(bits[0]) : 0;
            number += typeof bits[1] != 'undefined' && parseInt(bits[1]) > 0 ? parseFloat("." + bits[1]) : 0;
            amount = <?php echo $job['hourly_rate'];?> *
            number;
        }
        var places = <?php echo str_pad( '1', (int) module_config::c( 'currency_decimal_places', 2 ) + 1, '0', STR_PAD_RIGHT );?>;
        amount = Math.round(amount * places) / places;
        $('#' + task_id + 'taskamount').val(amount);
        $('#' + task_id + 'complete_hour').val(a);
    }

    function canceledittask() {
        if (editing_task_id) {
            //$('#task_edit_'+editing_task_id).html(loading_task_html);
            // we have to load the task preview back in for this task.
            refresh_task_preview(editing_task_id);
            editing_task_id = false;
        }
        //$('.task_edit').hide();
        //$('.task_preview').show();

    }

    function clear_create_form() {
        $('#task_long_desc_new').val('');
        $('#task_hours_new').val('');
        $('#newtaskamount').val('');
        $('#task_product_id_new').val('');
        $('#task_desc_new').val('');
        $('#task_desc_new')[0].focus();
    }

    function refresh_task_preview(task_id, html) {

        var loading_placeholder = $(loading_task_html);
        loading_placeholder.addClass($('.task_row_' + task_id + ':first').hasClass('odd') ? 'odd' : 'even');
        var h = 0;
        $('.task_row_' + task_id + '').each(function () {
            h += $(this).height();
        });
        loading_placeholder.height(h);
        loading_placeholder.addClass('task_row_' + task_id);
        var existing_rows = $('.task_row_' + task_id + '');
        $('.task_row_' + task_id + ':last').after(loading_placeholder);
        existing_rows.remove();
        if (html) {
            // already provided by iframe callback
            existing_rows = $('.task_row_' + task_id + '');
            $('.task_row_' + task_id + ':last').after(html);
            existing_rows.remove();
            set_task_numbers();

					<?php if(module_config::c( 'job_tasks_allow_sort', 1 )){ ?>
            $('#job_task_listing tbody.job_task_wrapper').sortable('enable');
					<?php } ?>
        } else {
            // do ajax cal to grab the updated task html
            $.ajax({
                url: '<?php echo module_job::link_ajax_task( $job_id, false ); ?>',
                data: {task_id: task_id, get_preview: 1},
                type: 'POST',
                dataType: 'json',
                success: function (r) {
                    var existing_rows = $('.task_row_' + r.task_id + '');
                    // case for adding this at the very first row.
                    if (r.after_task_id == 0) {
                        $('.job_task_wrapper').prepend(r.html);
                    } else {
                        $('.task_row_' + r.after_task_id + ':last').after(r.html);
                    }
                    existing_rows.remove();
                    set_task_numbers();
									<?php if(module_config::c( 'job_tasks_allow_sort', 1 )){ ?>
                    $('#job_task_listing tbody.job_task_wrapper').sortable('enable');
									<?php } ?>
                    // update the job summary
                    $('#job_summary').html(r.summary_html);
                    $('#create_invoice_button').html(r.create_invoice_button);
                    ucm.init_buttons();
                }
            });
        }

        /// close the new modal popup.
        ucm.form.close_modal();

    }

    function edittask(task_id, hours, callback) {
        if (editing_task_id != task_id) {
            canceledittask();
        }
        editing_task_id = task_id;

			<?php if(module_config::c( 'job_tasks_allow_sort', 1 )){ ?>
        $('#job_task_listing tbody.job_task_wrapper').sortable('disable');
			<?php } ?>

        // load in the edit task bit via ajax.
        var loading_placeholder = $(loading_task_html);
        loading_placeholder.addClass($('.task_row_' + task_id + ':first').hasClass('odd') ? 'odd' : 'even');
        loading_placeholder.height($('.task_row_' + task_id + ':first').height());
        loading_placeholder.addClass('task_row_' + task_id);
        var existing_rows = $('.task_row_' + task_id + '');
        $('.task_row_' + task_id + ':last').after(loading_placeholder);
        existing_rows.remove();
        $.ajax({
            url: '<?php echo module_job::link_ajax_task( $job_id, false ); ?>',
            data: {task_id: editing_task_id, hours: hours},
            type: 'POST',
            dataType: 'json',
            success: function (r) {
                var existing_rows = $('.task_row_' + task_id + '');
                $('.task_row_' + r.task_id + ':last').after($(r.html).addClass($('.task_row_' + task_id + ':first').hasClass('odd') ? 'odd' : 'even')); // this inserts two rows!
                existing_rows.remove();
                load_calendars();
                if (r.hours > 0) {
									<?php if(module_config::c( 'job_task_log_all_hours', 1 )){
									// dont want to set hours. just tick the box.
								}else{ ?>
                    $('#complete_' + r.task_id).val(r.hours);
									<?php } ?>
                    if (typeof $('#complete_t_' + r.task_id)[0] != 'undefined') {
                        //$('#complete_t_'+r.task_id)[0].checked = true;
                        //$('#complete_t_label_'+r.task_id).css('font-weight','bold');
                    } else {
                        $('#complete_' + r.task_id)[0].select();
                    }
                } else { // if(r.hours == 0){
                    $('#task_desc_' + r.task_id)[0].focus();
                    //$('#task_desc_'+r.task_id)[0].select();
                }
                /*else{
																		if(typeof $('#complete_'+r.task_id)[0] != 'undefined'){
																				$('#complete_'+r.task_id)[0].focus();
																		}
																}*/
                if (typeof callback == 'function') {
                    callback();
                }
            }
        });

        return false;
    }

    function delete_task_hours(task_id, task_log_id) {
        if (confirm('<?php _e( 'Really delete task hours?' );?>') && task_id && task_log_id) {
            $.ajax({
                url: '<?php echo module_job::link_ajax_task( $job_id, false ); ?>',
                data: {task_id: task_id, delete_task_log_id: task_log_id},
                type: 'POST',
                dataType: 'text',
                success: function (r) {
                    refresh_task_preview(task_id, false);
                }
            });
        }
    }

    function set_task_numbers() {
        // iterate through the tasks in the list
        // set the values from 1 counting up in each cell
        // if one of the values doesn't match what we are dispalying
        // we update the number via ajax in the system.
        // we also set the odd/even classes in the tables so that it looks pretty after updating.
        var task_number = 1;
        var odd_even = 1;
        var do_update = false;
        var update_task_orders = {
            update_task_order: 1
        };
        $('tr.task_preview').each(function () {
            $(this).removeClass('odd');
            $(this).removeClass('even');
            if ($(this).is(':visible')) {
                $(this).addClass(odd_even++ % 2 ? 'odd' : 'even');
            }
            var current_order = parseInt($('.task_order', this).html());
					<?php if($job['auto_task_numbers'] == 0){ ?>
            // automatic task numbers.
            if (current_order != task_number) {
                do_update = true;
                update_task_orders['task_order[' + $(this).attr('rel') + ']'] = task_number;
            }
            $('.task_order', this).html(task_number);
            task_number++;
					<?php }else if($job['auto_task_numbers'] == 1){ ?>
            // manual task numbers.
            task_number = Math.max(current_order, task_number) + 1;
					<?php } ?>
        });
        if (do_update) {
            $.ajax({
                url: '<?php echo module_job::link_ajax_task( $job_id, false ); ?>',
                type: 'POST',
                data: update_task_orders
            });
        }
        // todo - later on we call this as we dynamically re-arrange the cells in this table..

        // then we set the next available task number in the create new task number area.
        $('#next_task_number').val(task_number);


			<?php if(module_config::c( 'job_tasks_allow_sort', 1 )){ ?>
        $('#job_task_listing tbody.job_task_wrapper').sortable('refresh');
			<?php } ?>
    }

    $(function () {
        /*$('.task_editable').click(function(event){
            event.preventDefault();
            edittask($(this).attr('rel'),-1);
            return false;
        });*/
			<?php if(module_config::c( 'job_tasks_allow_sort', 1 )){ ?>
        $("#job_task_listing tbody.job_task_wrapper").sortable({
            items: ".task_preview",
            handle: '.task_drag_handle',
            axis: 'y',
            stop: function () {
                set_task_numbers();
            },
            helper: function (e, tr) {
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    // Set helper cell sizes to match the original sizes
                    $(this).width($originals.eq(index).width())
                });
                return $helper;
            }
        });
        set_task_numbers();
			<?php } ?>
        $('body').delegate('.task_toggle_long_description', 'click', function (event) {
            event.preventDefault();
            $(this).parent().find('.task_long_description').slideToggle(function () {
                if ($('textarea.edit_task_long_description').length > 0) {
                    $('textarea.edit_task_long_description')[0].focus();
                }
            });
            return false;
        });
        if (Get_Cookie('job_tasks_hide') == 'yes') {
            hide_completed_tasks();
        }

        $('#save_saved').click(function () {
            // set a flag and submit our form.
            if ($('#default_task_list_id').val() == '') {
                alert('<?php echo addcslashes( _l( 'Please enter a name for this saved task listing' ), "'" );?>');
                return false;
            }
            if (confirm('<?php echo addcslashes( _l( 'Really save these tasks as a default task listing?' ), "'" );?>')) {
                $('#default_tasks_action').val('save_default');
                $('#job_form')[0].submit();
            }
        });
        $('#insert_saved').click(function () {
            // set a flag and submit our form.
            $('#default_tasks_action').val('insert_default');
            $('#job_form')[0].submit();
        });
    });
</script>


<?php


hook_handle_callback( 'layout_column_half', 1, '35' );

?>

<form action="" method="post" id="job_form">
	<input type="hidden" name="_process" value="save_job"/>
	<input type="hidden" name="job_id" value="<?php echo $job_id; ?>"/>
	<input type="hidden" name="customer_id" value="<?php echo $job['customer_id']; ?>"/>
	<input type="hidden" name="_redirect" value="" id="form_redirect"/>

	<?php

	// check permissions.
	$do_perm_finish_check = false; // this is a hack to allow Job Task edit without Job edit permissions.
	if ( class_exists( 'module_security', false ) ) {
		if ( $job_id > 0 && $job['job_id'] == $job_id ) {
			if ( ! module_security::check_page( array(
				'category'  => 'Job',
				'page_name' => 'Jobs',
				'module'    => 'job',
				'feature'   => 'edit',
			) ) ) {
				// user does not have edit job perms
				$do_perm_finish_check = true;
			}
		} else {

			if ( ! module_security::check_page( array(
				'category'  => 'Job',
				'page_name' => 'Jobs',
				'module'    => 'job',
				'feature'   => 'create',
			) ) ) {
				// user does not have create job perms.
			}
		}
	}

	$fields = array(
		'fields' => array(
			'name' => 'Name',
		)
	);
	module_form::set_required(
		$fields
	);
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.save_task',
				'.delete',
				'.task_defaults',
				'.exit_button',
			)
		)
	);


	/**** JOB DETAILS ****/
	$fieldset_data = array(
		'heading'  => array(
			'type'  => 'h3',
			'title' => 'Job Details',
		),
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			'name'           => array(
				'title' => 'Job Title',
				'field' => array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $job['name'],
				),
			),
			'type'           => array(
				'title' => 'Type',
				'field' => array(
					'type'      => 'select',
					'name'      => 'type',
					'value'     => $job['type'],
					'blank'     => false,
					'options'   => module_job::get_types(),
					'allow_new' => true,
				),
			),
			'hourly_rate'    => array(
				'title'  => 'Hourly Rate',
				'ignore' => ! module_invoice::can_i( 'view', 'Invoices' ),
				'field'  => array(
					'type'  => 'currency',
					'name'  => 'hourly_rate',
					'value' => number_out( $job['hourly_rate'] ), // todo: is number_out($job['hourly_rate']) needed?
				),
			),
			'status'         => array(
				'title' => 'Status',
				'field' => array(
					'type'      => 'select',
					'name'      => 'status',
					'value'     => $job['status'],
					'blank'     => false,
					'options'   => module_job::get_statuses(),
					'allow_new' => true,
				),
			),
			'date_quote'     => array(
				'title'  => 'Quote Date',
				'ignore' => ! module_config::c( 'job_allow_quotes', 0 ),
				'field'  => array(
					'type'  => 'date',
					'name'  => 'date_quote',
					'value' => print_date( $job['date_quote'] ),
					'help'  => 'This is the date the Job was quoted to the Customer. Once this Job Quote is approved, the Start Date will be set below.',
				),
			),
			'date_start'     => array(
				'title' => 'Start Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_start',
					'value' => print_date( $job['date_start'] ),
					'help'  => 'This is the date the Job is scheduled to start work. This can be a date in the future.',
				),
			),
			'date_due'       => array(
				'title' => 'Due Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_due',
					'value' => print_date( $job['date_due'] ),
				),
			),
			'date_completed' => array(
				'title' => 'Finished Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_completed',
					'value' => print_date( $job['date_completed'] ),
				),
			),
			'user_id'        => array(
				'title'  => 'Staff Member',
				'ignore' => ! module_config::c( 'job_allow_staff_assignment', 1 ),
				'field'  => array(
					'type'    => 'select',
					'options' => $staff_member_rel,
					'name'    => 'user_id',
					'value'   => $job['user_id'],
					'blank'   => false,
					'help'    => 'Assign a staff member to this job. You can also assign individual tasks to different staff members. Staff members are users who have EDIT permissions on Job Tasks.',
				),
			),
		),
	);
	if ( class_exists( 'module_extra', false ) ) {
		$fieldset_data['extra_settings'] = array(
			'owner_table' => 'job',
			'owner_key'   => 'job_id',
			'owner_id'    => $job['job_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_extra::can_i( 'create', 'Jobs' ),
			'allow_edit'  => module_extra::can_i( 'edit', 'Jobs' ),
		);
	}
	$incrementing = false;
	if ( ! isset( $job['taxes'] ) || ! count( $job['taxes'] ) ) {
		$job['taxes'][] = array(); // at least have 1?
	}
	foreach ( $job['taxes'] as $tax ) {
		if ( isset( $tax['increment'] ) && $tax['increment'] ) {
			$incrementing = true;
		}
	}
	ob_start();
	?>
	<span class="job_tax_increment">
        <input type="checkbox" name="tax_increment_checkbox" id="tax_increment_checkbox"
               value="1" <?php echo $incrementing ? ' checked' : ''; ?>> <?php _e( 'incremental' ); ?>
    </span>
	<div id="job_tax_holder">
		<?php
		foreach ( $job['taxes'] as $id => $tax ) { ?>
			<div class="dynamic_block">
				<input type="hidden" name="tax_ids[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['job_tax_id'] ) ? (int) $tax['job_tax_id'] : 0; ?>">
				<input type="text" name="tax_names[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['name'] ) ? htmlspecialchars( $tax['name'] ) : ''; ?>" style="width:30px;">
				@
				<input type="text" name="tax_percents[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['percent'] ) ? htmlspecialchars( number_out( $tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) ) : ''; ?>"
				       style="width:35px;">%
				<a href="#" class="add_addit">+</a>
				<a href="#" class="remove_addit">-</a>
			</div>
		<?php } ?>
	</div>
	<script type="text/javascript">
      ucm.form.dynamic('job_tax_holder', function () {
          ucm.job.update_job_tax();
      });
	</script>
	<?php
	$fieldset_data['elements']['tax']      = array(
		'title'  => 'Tax',
		'fields' => array(
			ob_get_clean(),
		),
	);
	$fieldset_data['elements']['currency'] = array(
		'title' => 'Currency',
		'field' => array(
			'type'             => 'select',
			'options'          => get_multiple( 'currency', '', 'currency_id' ),
			'name'             => 'currency_id',
			'value'            => $job['currency_id'],
			'options_array_id' => 'code',
		),
	);

	// files
	if ( class_exists( 'module_file', false ) ) {
		ob_start();
		$files = module_file::get_files( array( 'job_id' => $job['job_id'] ), true );
		if ( count( $files ) > 0 ) {
			?>
			<a href="<?php

			echo module_file::link_generate( false, array(
				'arguments' => array(
					'job_id' => $job['job_id'],
				),
				'data'      => array(
					// we do this to stop the 'customer_id' coming through
					// so we link to the full job page, not the customer job page.

					'job_id' => $job['job_id'],
				),
			) ); ?>"><?php echo _l( 'View all %d files in this job', count( $files ) ); ?></a>
			<?php
		} else {
			echo _l( "This job has %d files", count( $files ) );
		}
		echo '<br/>';
		?>
		<a href="<?php echo module_file::link_generate( 'new', array(
			'arguments' => array(
				'job_id' => $job['job_id'],
			)
		) ); ?>"><?php _e( 'Add New File' ); ?></a>
		<?php
		$fieldset_data['elements']['files'] = array(
			'title'  => 'Files',
			'fields' => array(
				ob_get_clean(),
			),
		);
	}
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );


	if ( module_config::c( 'job_enable_description', 1 ) ) {

		if ( ! module_job::can_i( 'edit', 'Jobs' ) && ! $job['description'] ) {
			// no description, no ability to edit description, don't show anything.
		} else {
			// can edit description
			$fieldset_data = array(
				'heading' => array(
					'title' => _l( 'Job Description' ),
					'type'  => 'h3',
				),
				'class'   => 'tableclass tableclass_form tableclass_full',

			);
			if ( module_job::can_i( 'edit', 'Jobs' ) ) {
				$fieldset_data['elements'] = array(
					array(
						'field' => array(
							'type'  => 'wysiwyg',
							'name'  => 'description',
							'value' => $job['description'],
						),
					)
				);
			} else {
				$fieldset_data['elements'] = array(
					array(
						'fields' => array(
							module_security::purify_html( $job['description'] ),
						),
					)
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
	}

	if ( $job_id && $job_id != 'new' ) {
		$note_summary_owners = array();
		// generate a list of all possible notes we can display for this job.
		// display all the notes which are owned by all the sites we have access to

		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::display_notes( array(
					'title'       => 'Job Notes',
					'owner_table' => 'job',
					'owner_id'    => $job_id,
					'view_link'   => module_job::link_open( $job_id ),
				)
			);
		}

		if ( class_exists( 'module_timer', false ) && module_timer::is_plugin_enabled() && module_config::c( 'timer_enable_jobs', 1 ) ) {
			module_timer::display_timers( array(
					'title'       => 'Job Timers',
					'owner_table' => 'job',
					'owner_id'    => $job_id,
					'customer_id' => ! empty( $ticket['customer_id'] ) ? (int) $ticket['customer_id'] : 0,
				)
			);
		}

		if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
			if ( module_job::can_i( 'edit', 'Jobs' ) ) {
				module_email::display_emails( array(
					'title'  => 'Job Emails',
					'search' => array(
						'job_id' => $job_id,
					)
				) );
			}
		}

		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			module_group::display_groups( array(
				'title'       => 'Job Groups',
				'owner_table' => 'job',
				'owner_id'    => $job_id,
				'view_link'   => $module->link_open( $job_id ),

			) );
		}
	}

	// run the custom data hook to display items in this particular hook location
	hook_handle_callback( 'custom_data_hook_location', _CUSTOM_DATA_HOOK_LOCATION_JOB_SIDEBAR, 'job', $job_id, $job );


	$fieldset_data = $UCMJob->generate_calendar_fieldset();
	if ( $fieldset_data ) {
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );
	}

	if ( module_job::can_i( 'view', 'Job Advanced' ) ) {

		/***** JOB ADVANCED *****/
		$fieldset_data = $UCMJob->generate_advanced_fieldset();

		if ( (int) $job_id > 0 && module_job::can_i( 'edit', 'Jobs' ) ) {
			$fieldset_data['elements'][] = array(
				'title' => 'Email Job',
				'field' => array(
					'type'  => 'html',
					'value' => '<a href="' . module_job::link_generate( $job_id, array( 'arguments' => array( 'email' => 1 ) ) ) . '">' . _l( 'Email this Job to Customer' ) . '</a>',
					'help'  => 'You can email the customer a copy of this job. This can be a progress report or as an initial quote. ',
				),
			);
			$fieldset_data['elements'][] = array(
				'title'  => 'Email Staff',
				'fields' => array(
					function () use ( &$job_tasks, $job_id ) {
						$allocated_staff_members = array();
						foreach ( $job_tasks as $job_task ) {
							if ( ! isset( $allocated_staff_members[ $job_task['user_id'] ] ) ) {
								$allocated_staff_members[ $job_task['user_id'] ] = 0;
							}
							$allocated_staff_members[ $job_task['user_id'] ] ++;
						}
						foreach ( $allocated_staff_members as $staff_id => $count ) {
							$staff = module_user::get_user( $staff_id );
							?>
							<a href="<?php echo module_job::link_generate( $job_id, array(
								'arguments' => array(
									'email_staff' => 1,
									'staff_id'    => $staff_id
								)
							) ); ?>"><?php _e( 'Email staff (%s - %s tasks)', $staff['name'], $count ); ?></a> <br/>
							<?php
						}
					}
				),
			);
		}


		if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() && module_quote::can_i( 'view', 'Quotes' ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Assign Quote',
				'fields' => array(
					array(
						'type'   => 'text',
						'name'   => 'quote_id',
						'lookup' => array(
							'key'         => 'quote_id',
							'display_key' => 'name',
							'plugin'      => 'quote',
							'lookup'      => 'name',
							'return_link' => true,
							'display'     => '',
						),
						'value'  => $job['quote_id'],
					)
				),
			);
		}
		if ( file_exists( 'includes/plugin_invoice/pages/invoice_recurring.php' ) ) {
			if ( (int) $job_id > 0 ) {
				// see if this job was renewed from anywhere
				$job_history = module_job::get_jobs( array( 'renew_job_id' => $job_id ) );
				if ( count( $job_history ) ) {
					foreach ( $job_history as $job_h ) {
						$fieldset_data['elements'][] = array(
							'title'  => 'Renewal History',
							'fields' => array(
								_l( 'This job was renewed from %s on %s', module_job::link_open( $job_h['job_id'], true ), print_date( $job_h['date_renew'] ) )
							),
						);
					}
				}
			}
			$fieldset_data['elements'][] = array(
				'title'  => 'Renewal Date',
				'fields' => array(
					function () use ( &$job ) {
						if ( $job['renew_job_id'] ) {
							echo _l( 'This job was renewed on %s.', print_date( $job['date_renew'] ) );
							echo '<br/>';
							echo _l( 'A new job was created, please click <a href="%s">here</a> to view it.', module_job::link_open( $job['renew_job_id'] ) );
						} else {
							if ( $job['date_renew'] != '0000-00-00' && ( ! $job['date_start'] || $job['date_start'] == '0000-00-00' ) ) {
								echo '<p>Warning: Please set a Job "Start Date" for renewals to work correctly</p>';
							}
							?>
							<input type="text" name="date_renew" class="date_field"
							       value="<?php echo print_date( $job['date_renew'] ); ?>">
							<?php
							if ( $job['date_renew'] && $job['date_renew'] != '0000-00-00' && strtotime( $job['date_renew'] ) <= strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) && ! $job['renew_auto'] ) {
								// we are allowed to generate this renewal.
								?>
								<br/>
								<input type="button" name="generate_renewal_btn" value="<?php echo _l( 'Generate Renewal' ); ?>"
								       class="submit_button" onclick="$('#generate_renewal_gogo').val(1); this.form.submit();">
								<input type="hidden" name="generate_renewal" id="generate_renewal_gogo" value="0">

								<?php
								_h( 'A renewal is available for this job. Clicking this button will create a new job based on this job, and set the renewal reminder up again for the next date.' );
							} else if ( isset( $job['renew_auto'] ) && $job['renew_auto'] ) {
								_h( 'This job will be automatically renewed on this date.' );
							} else {
								_h( 'You will be reminded to renew this job on this date. You will be given the option to renew this job closer to the renewal date (a new button will appear).' );
							}
							echo '<br/>';
							$element = array(
								'type'  => 'checkbox',
								'name'  => 'renew_auto',
								'value' => isset( $job['renew_auto'] ) && $job['renew_auto'],
								'label' => 'Automatically Renew',
								'help'  => 'This Job will be automatically renewed on this date. A new Job will be created as a copy from this Job.',
							);
							module_form::generate_form_element( $element );
							echo '<br/>';
							$element = array(
								'type'  => 'checkbox',
								'name'  => 'renew_invoice',
								'value' => isset( $job['renew_invoice'] ) && $job['renew_invoice'],
								'label' => 'Automatically Invoice',
								'help'  => 'When this Job is renewed the tasks will be automatically completed and an invoice will be automatically created and emailed to the customer.',
							);
							module_form::generate_form_element( $element );
						}
					}
				),
			);
		} else {
			$fieldset_data['elements'][] = array(
				'title'  => 'Renewal Date',
				'fields' => array(
					'(recurring jobs available in <a href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}


		if ( (int) $job_id > 0 && class_exists( 'module_import_export', false ) && module_import_export::is_plugin_enabled() && isset( $import_tasks_link ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task CSV Data',
				'fields' => array(
					function () use ( $job_id, $import_tasks_link ) {
						// hack to add a "export" link to this page
						if ( module_job::can_i( 'view', 'Export Job Tasks' ) ) {
							?>
							<a href="<?php echo module_job::link_open( $job_id, false ) . '&import_export_go=yes'; ?>"
							   class=""><?php _e( 'Export Tasks' ); ?></a>
							<?php
						}
						if ( module_job::can_i( 'view', 'Import Job Tasks' ) && module_job::can_i( 'view', 'Export Job Tasks' ) ) {
							echo ' / ';
						}
						if ( module_job::can_i( 'view', 'Import Job Tasks' ) ) {
							?>
							<a href="<?php echo $import_tasks_link; ?>" class=""><?php _e( 'Import Tasks' ); ?></a>
							<?php
						}
					}
				),
			);
		} else if ( (int) $job_id > 0 && ! class_exists( 'module_import_export', false ) && module_config::c( 'show_ucm_ads', 1 ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task CSV Data',
				'fields' => array(
					'(import/export available in <a href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() && module_config::c( 'job_enable_default_tasks', 1 ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task Defaults',
				'fields' => array(
					function () use ( $job_id, &$job ) {
						$job_default_tasks = module_job::get_default_tasks();
						echo print_select_box( $job_default_tasks, 'default_task_list_id', '', '', true, '', true );
						?>
						<?php if ( (int) $job_id > 0 ) { ?>
							<input type="button" name="s" id="save_saved" value="<?php _e( 'Save' ); ?>"
							       class="task_defaults small_button">
						<?php } ?>
						<input type="button" name="i" id="insert_saved" value="<?php _e( 'Insert' ); ?>"
						       class="task_defaults small_button">
						<input type="hidden" name="default_tasks_action" id="default_tasks_action" value="0">
						<?php _h( 'Here you can save the current tasks as defaults to be used later, or insert a previously saved set of defaults.' );
					}
				),
			);
		}
		//


		if ( class_exists( 'module_job_discussion', false ) && isset( $job['job_discussion'] ) && module_job_discussion::is_plugin_enabled() ) {
			$fieldset_data['elements'][] = array(
				'title' => 'Job Discussion',
				'field' => array(
					'type'    => 'select',
					'options' => array(
						0 => _l( 'Allowed' ),
						1 => _l( 'Disabled & Hidden' ),
						2 => _l( 'Disabled & Shown' ),
					),
					'name'    => 'job_discussion',
					'value'   => isset( $job['job_discussion'] ) ? $job['job_discussion'] : 0,
				),
			);
		}

		if ( (int) $job_id > 0 && module_invoice::can_i( 'create', 'Invoices' ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Job Deposit',
				'fields' => array(
					array(
						'type'  => 'currency',
						'name'  => 'job_deposit',
						'value' => '',
					),
					array(
						'type'  => 'submit',
						'name'  => 'butt_create_deposit',
						'value' => 'Create Deposit Invoice',
						'class' => 'exit_button small_button',
						'help'  => 'Enter a dollar value here to create a deposit invoice for this job. Also supports entering a percentage (eg: 20%%)',
					),
				),
			);
		}
		$fieldset_data['elements'][] = array(
			'title'  => 'Job Completed',
			'fields' => array(
				array(
					'type'  => 'text',
					'name'  => 'total_percent_complete_override',
					'value' => $job['total_percent_complete_manual'] ? $job['total_percent_complete'] * 100 : '',
					'style' => 'width:30px;',
				),
				'%',
				$job['total_percent_complete_manual'] ? _l( '(calculated: %s%%)', $job['total_percent_complete_calculated'] * 100 ) : '',
				array(
					'type'  => 'html',
					'value' => '',
					'help'  => 'Enter a manual job "percent completed" here and this will be used instead of the automatically calculated value.',
				)
			),
		);
		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}

	$form_actions = array(
		'class'    => 'action_bar action_bar_left',
		'elements' => array(
			array(
				'type'    => 'save_button',
				'name'    => 'butt_save',
				'onclick' => "$('#form_redirect').val('" . module_job::link_open( false ) . "');",
				'value'   => _l( 'Save and Return' ),
			),
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'type'  => 'save_button',
				'class' => 'archive_button',
				'name'  => 'butt_archive',
				'value' => $UCMJob->is_archived() ? _l( 'Unarchive' ) : _l( 'Archive' ),
			),
			array(
				'ignore' => ! ( (int) $job_id && module_job::can_i( 'delete', 'Jobs' ) ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . module_job::link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	?>

</form>

<?php if ( $do_perm_finish_check ) {
	// we call our permission check
	// render finish method here instead of in index.php
	// this allows job task edit permissions without job edit permissions.
	// HOPE THIS WORKS! :)
	module_security::render_page_finished();
}

hook_handle_callback( 'layout_column_half', 2, '65' );

?>


<?php if ( module_config::c( 'job_ajax_tasks', 1 ) ) { ?>
	<iframe name="job_task_ajax_submit" id="job_task_ajax_submit" src="about:blank"
	        style="display:none; width:0; height:0;" frameborder="0"></iframe>
<?php } ?>

<form action="" method="post"
      id="job_task_form" <?php if ( module_config::c( 'job_ajax_tasks', 1 ) ) { ?> target="job_task_ajax_submit"<?php } ?>>
	<input type="hidden" name="_process" value="save_job<?php if ( module_config::c( 'job_ajax_tasks', 1 ) ) {
		echo '_tasks_ajax';
	} ?>"/>
	<input type="hidden" name="job_id" value="<?php echo $job_id; ?>"/>
	<input type="hidden" name="customer_id" value="<?php echo $job['customer_id']; ?>"/>

	<?php

	module_form::set_default_field( 'task_desc_new' );
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.save_task',
				'.delete',
			)
		)
	);


	?>


	<?php if ( module_job::can_i( 'edit', 'Job Tasks' ) || module_job::can_i( 'view', 'Job Tasks' ) ) {

		$header = array(
			'title_final' => _l( 'Job Tasks %s', ( $job['total_percent_complete'] > 0 ? _l( '(%s%% completed)', $job['total_percent_complete'] * 100 ) : '' ) ),
			'button'      => array(),
			'type'        => 'h3',
		);
		if ( get_display_mode() != 'mobile' ) {
			$header['button'][] = array(
				'class'   => 'toggle_completed_tasks show_completed',
				'url'     => '#',
				'onclick' => "show_completed_tasks(); return false;",
				'title'   => _l( 'Show Completed Tasks' ),
			);
			$header['button'][] = array(
				'class'   => 'toggle_completed_tasks hide_completed',
				'url'     => '#',
				'onclick' => "hide_completed_tasks(); return false;",
				'title'   => _l( 'Hide Completed Tasks' ),
			);
		}
		ob_start();
		?>
		<div class="content_box_wheader">

			<?php
			$show_task_numbers = ( module_config::c( 'job_show_task_numbers', 1 ) && $job['auto_task_numbers'] != 2 );
			?>

			<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full"
			       id="job_task_listing">
				<thead>
				<tr>
					<?php if ( $show_task_numbers ) { ?>
						<th width="10">#</th>
					<?php } ?>
					<th class="task_column task_width"><?php _e( 'Description' ); ?></th>
					<th width="15" class="task_type_label">
						<?php
						$unit_measurement = false;
						if ( is_callable( 'module_product::sanitise_product_name' ) ) {
							$fake_task        = module_product::sanitise_product_name( array(), $job['default_task_type'] );
							$unit_measurement = $fake_task['unitname'];
							foreach ( $job_tasks as $task_data ) {
								if ( isset( $task_data['unitname'] ) && $task_data['unitname'] != $unit_measurement ) {
									$unit_measurement = false;
									break; // show nothing at title of quote page.
								}
							}
						}
						echo _l( $unit_measurement ? $unit_measurement : module_config::c( 'task_default_name', 'Unit' ) );
						?>
					</th>
					<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
						<th width="79"><?php _e( module_config::c( 'invoice_amount_name', 'Amount' ) ); ?></th>
					<?php } ?>
					<?php if ( module_config::c( 'job_show_due_date', 1 ) ) { ?>
						<th width="70"><?php _e( 'Due Date' ); ?></th>
					<?php } ?>
					<?php if ( module_config::c( 'job_show_done_date', 1 ) ) { ?>
						<th width="70"><?php _e( 'Done Date' ); ?></th>
					<?php } ?>
					<?php if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) { ?>
						<th width="78"><?php _e( 'Staff' ); ?></th>
					<?php } ?>
					<th width="32" nowrap="nowrap">%</th>
					<?php if ( class_exists( 'module_signature', true ) && module_signature::signature_enabled( $job_id ) ) { ?>
						<th width="78"><?php _e( 'Signature' ); ?></th>
					<?php } ?>
					<th width="60"></th>
				</tr>
				</thead>
				<?php
				//module_security::is_page_editable() &&
				if ( module_job::can_i( 'create', 'Job Tasks' ) && $job_task_creation_permissions != _JOB_TASK_CREATION_NOT_ALLOWED ) { ?>
					<tbody>
					<tr>
						<?php if ( $show_task_numbers ) { ?>
							<td valign="top" style="padding:0.3em 0;">
								<input type="text" name="job_task[new][task_order]" value="" id="next_task_number" size="3"
								       class="edit_task_order no_permissions">
							</td>
						<?php } ?>
						<td valign="top">
							<input type="text" name="job_task[new][description]" id="task_desc_new"
							       class="edit_task_description no_permissions" value=""><?php
							if ( class_exists( 'module_product', false ) ) {
								module_product::print_job_task_dropdown( 'new' );
							} ?><a href="#" class="task_toggle_long_description"><i class="fa fa-plus"></i></a>
							<div class="task_long_description">
								<?php module_form::generate_form_element( array(
									'type'  => module_config::c( 'long_description_wysiwyg', 1 ) ? 'wysiwyg' : 'textarea',
									'name'  => 'job_task[new][long_description]',
									'id'    => 'task_long_desc_new',
									'class' => 'edit_task_long_description no_permissions',
									'value' => '',
								) ); ?>
							</div>
						</td>
						<td valign="top">
							<?php if ( $job['default_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
								// no hour input
							} else if ( $job['default_task_type'] == _TASK_TYPE_QTY_AMOUNT ) { ?>
								<input type="text" name="job_task[new][hours]" value="" size="3" style="width:25px;"
								       class="no_permissions" id="task_hours_new">
							<?php } else if ( $job['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
								?>
								<input type="text" name="job_task[new][hours]" value="" size="3" style="width:25px;"
								       onchange="setamount(this.value,'new');" onkeyup="setamount(this.value,'new');"
								       class="no_permissions" id="task_hours_new">
								<?php
							} ?>
						</td>
						<?php if ( module_invoice::can_i( 'view', 'Invoices' ) ) { ?>
							<td valign="top" nowrap="">
								<?php echo currency( '<input type="text" name="job_task[new][amount]" value="" id="newtaskamount" class="currency no_permissions">' ); ?>
							</td>
						<?php } ?>
						<?php if ( module_config::c( 'job_show_due_date', 1 ) ) { ?>
							<td valign="top">
								<input type="text" name="job_task[new][date_due]" value="<?php echo print_date( $job['date_due'] ); ?>"
								       class="date_field no_permissions">
							</td>
						<?php } ?>
						<?php if ( module_config::c( 'job_show_done_date', 1 ) ) { ?>
							<td valign="top">
								<input type="text" name="job_task[new][date_done]" value="" class="date_field no_permissions">
							</td>
						<?php } ?>
						<?php if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) { ?>
							<td valign="top">
								<?php echo print_select_box( $staff_member_rel, 'job_task[new][user_id]',
									isset( $staff_member_rel[ module_security::get_loggedin_id() ] ) ? module_security::get_loggedin_id() : false, 'job_task_staff_list no_permissions', '' ); ?>
							</td>
						<?php } ?>
						<td valign="top">
							<input type="checkbox" name="job_task[new][new_fully_completed]" value="1" class="no_permissions">
						</td>
						<?php if ( class_exists( 'module_signature', true ) && module_signature::signature_enabled( $job_id ) ) { ?>
							<td></td>
						<?php } ?>
						<td align="center" valign="top">
							<input type="submit" name="save" value="<?php _e( 'New Task' ); ?>"
							       class="save_task no_permissions small_button">
							<!-- these are overridden from the products selection -->
							<input type="hidden" name="job_task[new][billable_t]" value="1">
							<input type="hidden" name="job_task[new][billable]" value="1" id="billable_t_new">
							<input type="hidden" name="job_task[new][taxable_t]" value="1">
							<input type="hidden" name="job_task[new][taxable]"
							       value="<?php echo module_config::c( 'task_taxable_default', 1 ) ? 1 : 0; ?>" id="taxable_t_new">
							<input type="hidden" name="job_task[new][manual_task_type]" value="-1" id="manual_task_type_new">
						</td>
					</tr>
					</tbody>
				<?php } ?>
				<tbody class="job_task_wrapper">
				<?php
				$c                  = 0;
				$task_number        = 0;
				$show_hours_summary = false;
				foreach ( $job_tasks as $task_id => $task_data ) {

					if ( $task_data['manual_task_type'] < 0 ) {
						$job_tasks[ $task_id ]['manual_task_type'] = $job['default_task_type'];
					}
					if ( $job_tasks[ $task_id ]['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
						$show_hours_summary = true;
					}

					$task_number ++;
					//module_security::is_page_editable() &&
					if ( module_job::can_i( 'edit', 'Job Tasks' ) ) {
						$task_editable = true;
					} else {
						$task_editable = false;
					}
					echo module_job::generate_task_preview( $job_id, $job, $task_id, $task_data, $task_editable, array(
						'unit_measurement' => $unit_measurement
					) );
				} ?>
				</tbody>
			</table>
			<?php if ( (int) $job_id > 0 ) {
				?>
				<div
					id="job_summary"> <?php echo module_job::generate_job_summary( $job_id, $job, $show_hours_summary ); ?> </div> <?php
			} ?>
		</div>


		<?php

		$fieldset_data = array(
			'heading'         => $header,
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );


	}  // end can i view job tasks

	if ( module_invoice::can_i( 'view', 'Invoices' ) && (int) $job_id > 0 ) { ?>

		<div id="create_invoice_options">
			<div id="create_invoice_options_inner"></div>
		</div>
		<?php ob_start(); ?>
		<div class="content_box_wheader">
			<?php
			$job_invoices = module_invoice::get_invoices( array( 'job_id' => $job_id ) );
			if ( ! count( $job_invoices ) ) { ?>
				<p align="center">
					<?php _e( 'There are no invoices for this job yet.' ); ?>
				</p>
			<?php } else { ?>

				<?php //$invoice_safe = true; $invoice_from_job_page = $job_id; include('includes/plugin_invoice/pages/invoice_admin_list.php'); 
				?>
				<table class="tableclass tableclass_rows tableclass_full">
					<thead>
					<tr class="title">
						<th><?php echo _l( 'Invoice Number' ); ?></th>
						<th><?php echo _l( 'Status' ); ?></th>
						<th><?php echo _l( 'Due Date' ); ?></th>
						<th><?php echo _l( 'Sent Date' ); ?></th>
						<th><?php echo _l( 'Paid Date' ); ?></th>
						<th><?php echo _l( 'Invoice Total' ); ?></th>
						<th><?php echo _l( 'Amount Due' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					$c = 0;
					foreach ( $job_invoices as $invoice ) {
						$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
						?>
						<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
							<td class="row_action">
								<?php echo module_invoice::link_open( $invoice['invoice_id'], true, $invoice ); ?>
							</td>
							<td>
								<?php echo htmlspecialchars( $invoice['status'] ); ?>
							</td>
							<td>
								<?php
								if ( ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < time() ) {
									echo '<span class="error_text">';
									echo print_date( $invoice['date_due'] );
									echo '</span>';
								} else {
									echo print_date( $invoice['date_due'] );
								}
								?>
							</td>
							<td>
								<?php echo print_date( $invoice['date_sent'] ); ?>
							</td>
							<td>
								<?php echo $invoice['date_cancel'] != '0000-00-00' ? 'Cancelled' : print_date( $invoice['date_paid'] ); ?>
							</td>
							<td>
								<?php echo dollar( $invoice['total_amount'], true, $invoice['currency_id'] ); ?>
							</td>
							<td>
								<?php echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] ); ?>
								<?php if ( $invoice['total_amount_credit'] > 0 ) {
									?>
									<span
										class="success_text"><?php echo _l( 'Credit: %s', dollar( $invoice['total_amount_credit'], true, $invoice['currency_id'] ) ); ?></span>
									<?php
								} ?>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			<?php } ?>
		</div>
		<?php

		$fieldset_data = array(
			'heading'         => array(
				'title' => 'Job Invoices:',
				'type'  => 'h3',
			),
			'elements_before' => ob_get_clean(),
		);
		if ( module_invoice::can_i( 'create', 'Invoices' ) ) {
			$fieldset_data['heading']['button'] = array(
				'title' => _l( 'Create New Invoice' ),
				'url'   => '#',
				'id'    => 'job_generate_invoice_button',
			);
		}
		echo module_form::generate_fieldset( $fieldset_data );
	}

	if ( class_exists( 'module_finance', false ) && module_finance::is_plugin_enabled() && module_finance::can_i( 'view', 'Finance' ) && (int) $job_id > 0 && module_finance::is_enabled() && is_file( 'includes/plugin_finance/pages/finance_job_edit.php' ) ) {
		include( 'includes/plugin_finance/pages/finance_job_edit.php' );
	}

	?>

</form>
<?php
// run the custom data hook to display items in this particular hook location
hook_handle_callback( 'custom_data_hook_location', _CUSTOM_DATA_HOOK_LOCATION_JOB_FOOTER, 'job', $job_id, $job );

hook_handle_callback( 'layout_column_half', 'end' ); ?>

