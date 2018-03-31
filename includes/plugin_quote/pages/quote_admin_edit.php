<?php

if ( ! $quote_safe ) {
	die( 'denied' );
}

$quote_id = (int) $_REQUEST['quote_id'];
$quote    = module_quote::get_quote( $quote_id );
$quote_id = (int) $quote['quote_id'];
$UCMQuote = UCMQuote::singleton( $quote_id );


$staff_members    = module_user::get_staff_members();
$staff_member_rel = array();
foreach ( $staff_members as $staff_member ) {
	$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
}

if ( $quote_id > 0 && $quote['quote_id'] == $quote_id ) {
	$module->page_title = _l( 'Quote: %s', $quote['name'] );
	if ( function_exists( 'hook_handle_callback' ) ) {
		hook_handle_callback( 'timer_display', 'quote', $quote_id );
	}
} else {
	$module->page_title = _l( 'Quote: %s', _l( 'New' ) );
}

// check permissions.
if ( class_exists( 'module_security', false ) ) {
	module_security::sanatise_data( 'quote', $quote );
}

$quote_tasks = module_quote::get_quote_items( $quote_id, $quote );

if ( class_exists( 'module_import_export', false ) ) {
	if ( module_quote::can_i( 'view', 'Export Quote Tasks' ) ) {
		module_import_export::enable_pagination_hook(
		// what fields do we pass to the import_export module from this quote tasks?
			array(
				'name'   => 'Quote Tasks Export',
				'fields' => array(
					'Quote Name'        => 'quote_name',
					'Task ID'           => 'quote_task_id',
					'Order'             => 'task_order',
					'Short Description' => 'description',
					'Long Description'  => 'long_description',
					'Hours'             => 'hours',
					'Amount'            => 'amount',
					'Billable'          => 'billable',
					'Staff Member'      => 'user_name',
				),
			)
		);
		if ( isset( $_REQUEST['import_export_go'] ) && $_REQUEST['import_export_go'] == 'yes' ) {
			// do the task export.
			module_import_export::run_pagination_hook( $quote_tasks );
		}
	}
	if ( module_quote::can_i( 'view', 'Import Quote Tasks' ) ) {
		$import_tasks_link = module_import_export::import_link(
			array(
				'callback'   => 'module_quote::handle_import_tasks',
				'name'       => 'Quote Tasks',
				'quote_id'   => $quote_id,
				'return_url' => $_SERVER['REQUEST_URI'],
				'fields'     => array(
					//'Quote Name' => 'quote_name',
					'Task ID'           => array(
						'quote_task_id',
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
					//'Invoice #' => 'invoice_number',
					'Staff Member'      => array( 'user_name', false, 'One of: ' . implode( ', ', $staff_member_rel ) ),
				),
			)
		);
	}
}

?>

<script type="text/javascript">

    $(function () {
        if (typeof ucm.quote != 'undefined') {
            ucm.quote.ajax_task_url = '<?php echo module_quote::link_ajax_task( $quote_id, false ); ?>';
					<?php if(module_invoice::can_i( 'create', 'Invoices' )){ ?>
            ucm.quote.create_invoice_popup_url = '<?php echo module_quote::link_create_quote_invoice( $quote_id, false ); ?>';
            ucm.quote.create_invoice_url = '<?php echo module_invoice::link_generate( 'new', array( 'arguments' => array( 'quote_id' => $quote_id, ) ) ); ?>';
					<?php } ?>
            ucm.quote.init();
        }
    });
    var completed_tasks_hidden = false; // set with session variable / cookie
    var editing_quote_task_id = false;
    var loading_task_html = '<tr class="task_edit_loading"><td colspan="9" align="center"><?php _e( 'Loading...' );?></td></tr>';

    function show_completed_tasks() {
        $('.tasks_completed').show();
        $('#show_completed_tasks').hide();
        $('#hide_completed_tasks').show();
        set_task_numbers();
        Set_Cookie('quote_tasks_hide', 'no');
        return true;
    }

    function hide_completed_tasks() {
        $('.tasks_completed').hide();
        $('#show_completed_tasks').show();
        $('#hide_completed_tasks').hide();
        set_task_numbers();
        Set_Cookie('quote_tasks_hide', 'yes');
        return true;
    }

    function setamount(a, quote_task_id) {
        var hourly_rate = parseInt($('#main_hourly_rate').val());
        if (hourly_rate > 0) {
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
                    amount = hourly_rate * hours;
                    amount += hourly_rate * (minutes / 60);
                }
            } else {
                var bits = a.split('<?php echo module_config::c( 'currency_decimal_separator', '.' );?>');
                var number = bits[0].length > 0 ? parseInt(bits[0]) : 0;
                number += typeof bits[1] != 'undefined' && parseInt(bits[1]) > 0 ? parseFloat("." + bits[1]) : 0;
                amount = hourly_rate * number;
            }
            $('#' + quote_task_id + 'taskamount').val(amount);
            $('#' + quote_task_id + 'complete_hour').val(a);
        }
    }

    function canceledittask() {
        if (editing_quote_task_id) {
            //$('#task_edit_'+editing_quote_task_id).html(loading_task_html);
            // we have to load the task preview back in for this task.
            refresh_task_preview(editing_quote_task_id);
            editing_quote_task_id = false;
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

    function refresh_task_preview(quote_task_id, html) {

        var loading_placeholder = $(loading_task_html);
        loading_placeholder.addClass($('.task_row_' + quote_task_id + ':first').hasClass('odd') ? 'odd' : 'even');
        var h = 0;
        $('.task_row_' + quote_task_id + '').each(function () {
            h += $(this).height();
        });
        loading_placeholder.height(h);
        loading_placeholder.addClass('task_row_' + quote_task_id);
        var existing_rows = $('.task_row_' + quote_task_id + '');
        $('.task_row_' + quote_task_id + ':last').after(loading_placeholder);
        existing_rows.remove();
        if (html) {
            // already provided by iframe callback
            existing_rows = $('.task_row_' + quote_task_id + '');
            $('.task_row_' + quote_task_id + ':last').after(html);
            existing_rows.remove();
            set_task_numbers();

					<?php if(module_config::c( 'quote_tasks_allow_sort', 1 )){ ?>
            $('#quote_task_listing tbody.quote_task_wrapper').sortable('enable');
					<?php } ?>
        } else {
            // do ajax cal to grab the updated task html
            $.ajax({
                url: '<?php echo module_quote::link_ajax_task( $quote_id, false ); ?>',
                data: {quote_task_id: quote_task_id, get_preview: 1},
                type: 'POST',
                dataType: 'json',
                success: function (r) {
                    var existing_rows = $('.task_row_' + r.quote_task_id + '');
                    // case for adding this at the very first row.
                    if (r.after_quote_task_id == 0) {
                        $('.quote_task_wrapper').prepend(r.html);
                    } else {
                        $('.task_row_' + r.after_quote_task_id + ':last').after(r.html);
                    }
                    existing_rows.remove();
                    set_task_numbers();
									<?php if(module_config::c( 'quote_tasks_allow_sort', 1 )){ ?>
                    $('#quote_task_listing tbody.quote_task_wrapper').sortable('enable');
									<?php } ?>
                    // update the quote summary
                    $('#quote_summary').html(r.summary_html);
                    $('#create_invoice_button').html(r.create_invoice_button);
                    ucm.init_buttons();
                }
            });
        }

    }

    function edittask(quote_task_id, hours) {
        if (editing_quote_task_id != quote_task_id) {
            canceledittask();
        }
        editing_quote_task_id = quote_task_id;

			<?php if(module_config::c( 'quote_tasks_allow_sort', 1 )){ ?>
        $('#quote_task_listing tbody.quote_task_wrapper').sortable('disable');
			<?php } ?>

        // load in the edit task bit via ajax.
        var loading_placeholder = $(loading_task_html);
        loading_placeholder.addClass($('.task_row_' + quote_task_id + ':first').hasClass('odd') ? 'odd' : 'even');
        loading_placeholder.height($('.task_row_' + quote_task_id + ':first').height());
        loading_placeholder.addClass('task_row_' + quote_task_id);
        var existing_rows = $('.task_row_' + quote_task_id + '');
        $('.task_row_' + quote_task_id + ':last').after(loading_placeholder);
        existing_rows.remove();
        $.ajax({
            url: '<?php echo module_quote::link_ajax_task( $quote_id, false ); ?>',
            data: {quote_task_id: editing_quote_task_id, hours: hours},
            type: 'POST',
            dataType: 'json',
            success: function (r) {
                var existing_rows = $('.task_row_' + quote_task_id + '');
                $('.task_row_' + r.quote_task_id + ':last').after($(r.html).addClass($('.task_row_' + quote_task_id + ':first').hasClass('odd') ? 'odd' : 'even')); // this inserts two rows!
                existing_rows.remove();
                load_calendars();
                if (r.hours > 0) {
									<?php if(module_config::c( 'quote_task_log_all_hours', 1 )){
									// dont want to set hours. just tick the box.
								}else{ ?>
                    $('#complete_' + r.quote_task_id).val(r.hours);
									<?php } ?>
                    if (typeof $('#complete_t_' + r.quote_task_id)[0] != 'undefined') {
                        //$('#complete_t_'+r.quote_task_id)[0].checked = true;
                        //$('#complete_t_label_'+r.quote_task_id).css('font-weight','bold');
                    } else {
                        $('#complete_' + r.quote_task_id)[0].select();
                    }
                } else { // if(r.hours == 0){
                    $('#task_desc_' + r.quote_task_id)[0].focus();
                    //$('#task_desc_'+r.quote_task_id)[0].select();
                }
                /*else{
																		if(typeof $('#complete_'+r.quote_task_id)[0] != 'undefined'){
																				$('#complete_'+r.quote_task_id)[0].focus();
																		}
																}*/
            }
        });

        return false;
    }

    function delete_task_hours(quote_task_id, task_log_id) {
        if (confirm('<?php _e( 'Really delete task hours?' );?>') && quote_task_id && task_log_id) {
            $.ajax({
                url: '<?php echo module_quote::link_ajax_task( $quote_id, false ); ?>',
                data: {quote_task_id: quote_task_id, delete_task_log_id: task_log_id},
                type: 'POST',
                dataType: 'text',
                success: function (r) {
                    refresh_task_preview(quote_task_id, false);
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
					<?php if($quote['auto_task_numbers'] == 0){ ?>
            // automatic task numbers.
            if (current_order != task_number) {
                do_update = true;
                update_task_orders['task_order[' + $(this).attr('rel') + ']'] = task_number;
            }
            $('.task_order', this).html(task_number);
            task_number++;
					<?php }else if($quote['auto_task_numbers'] == 1){ ?>
            // manual task numbers.
            task_number = Math.max(current_order, task_number) + 1;
					<?php } ?>
        });
        if (do_update) {
            $.ajax({
                url: '<?php echo module_quote::link_ajax_task( $quote_id, false ); ?>',
                type: 'POST',
                data: update_task_orders
            });
        }
        // todo - later on we call this as we dynamically re-arrange the cells in this table..

        // then we set the next available task number in the create new task number area.
        $('#next_task_number').val(task_number);


			<?php if(module_config::c( 'quote_tasks_allow_sort', 1 )){ ?>
        $('#quote_task_listing tbody.quote_task_wrapper').sortable('refresh');
			<?php } ?>
    }

    $(function () {
        /*$('.task_editable').click(function(event){
            event.preventDefault();
            edittask($(this).attr('rel'),-1);
            return false;
        });*/
			<?php if(module_config::c( 'quote_tasks_allow_sort', 1 )){ ?>
        $("#quote_task_listing tbody.quote_task_wrapper").sortable({
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
            $(this).parent().find('.task_long_description').slideToggle('fast', function () {
                if ($('textarea.edit_task_long_description').length > 0) {
                    $('textarea.edit_task_long_description')[0].focus();
                }
            });
            return false;
        });
        if (Get_Cookie('quote_tasks_hide') == 'yes') {
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
                $('#quote_form')[0].submit();
            }
        });
        $('#insert_saved').click(function () {
            // set a flag and submit our form.
            $('#default_tasks_action').val('insert_default');
            $('#quote_form')[0].submit();
        });
    });
</script>

<iframe id="ajax_task_save" name="ajax_task_save" src="about:blank" style="width:0;height:0;visibility: hidden;"
        frameborder="0"></iframe>
<form action="" method="post" id="quote_form">
	<input type="hidden" name="_process" value="save_quote"/>
	<input type="hidden" name="_redirect" value="" id="form_redirect"/>
	<input type="hidden" name="quote_id" value="<?php echo $quote_id; ?>"/>
	<input type="hidden" name="customer_id" value="<?php echo $quote['customer_id']; ?>"/>


	<?php
	hook_handle_callback( 'layout_column_half', 1, '35' );

	// check permissions.
	$do_perm_finish_check = false; // this is a hack to allow Quote Task edit without Quote edit permissions.
	if ( class_exists( 'module_security', false ) ) {
		if ( $quote_id > 0 && $quote['quote_id'] == $quote_id ) {
			if ( ! module_security::check_page( array(
				'category'  => 'Quote',
				'page_name' => 'Quotes',
				'module'    => 'quote',
				'feature'   => 'edit',
			) ) ) {
				// user does not have edit quote perms
				$do_perm_finish_check = true;
			}
		} else {

			if ( ! module_security::check_page( array(
				'category'  => 'Quote',
				'page_name' => 'Quotes',
				'module'    => 'quote',
				'feature'   => 'create',
			) ) ) {
				// user does not have create quote perms.
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
	module_form::set_default_field( 'task_desc_new' );
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
				'.save_task',
				'.delete',
				'.task_defaults',
				'.exit_button',
				'.apply_discount',
			)
		)
	);


	/**** QUOTE DETAILS ****/
	$fieldset_data = array(
		'id'             => 'quote_details', // used for css and hooks
		'heading'        => array(
			'type'  => 'h3',
			'title' => 'Quote Details',
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(
			'name'          => array(
				'title' => 'Quote Title',
				'field' => array(
					'type'  => 'text',
					'name'  => 'name',
					'value' => $quote['name'],
				),
			),
			'type'          => array(
				'title' => 'Type',
				'field' => array(
					'type'      => 'select',
					'name'      => 'type',
					'value'     => $quote['type'],
					'blank'     => false,
					'options'   => module_quote::get_types(),
					'allow_new' => true,
				),
			),
			'hourly_rate'   => array(
				'title' => 'Hourly Rate',
				'field' => array(
					'type'  => 'currency',
					'id'    => 'main_hourly_rate',
					'name'  => 'hourly_rate',
					'value' => number_out( $quote['hourly_rate'] ), // todo: is number_out($quote['hourly_rate']) needed?
				),
			),
			'status'        => array(
				'title' => 'Status',
				'field' => array(
					'type'      => 'select',
					'name'      => 'status',
					'value'     => $quote['status'],
					'blank'     => false,
					'options'   => module_quote::get_statuses(),
					'allow_new' => true,
				),
			),
			'date_create'   => array(
				'title' => 'Create Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_create',
					'value' => print_date( $quote['date_create'] ),
					'help'  => 'This is the date the Quote is scheduled to start work. This can be a date in the future.',
				),
			),
			'date_approved' => array(
				'title' => 'Approved Date',
				'field' => array(
					'type'  => 'date',
					'name'  => 'date_approved',
					'value' => print_date( $quote['date_approved'] ),
					'help'  => 'This is the date the Quote was accepted by the client. This date is automatically set if the client clicks "Approve"',
				),
			),
			'approved_by'   => array(
				'title' => 'Approved By',
				'field' => array(
					'type'  => 'text',
					'name'  => 'approved_by',
					'value' => $quote['approved_by'],
				),
			),
		),
		'extra_settings' => array(
			'owner_table' => 'quote',
			'owner_key'   => 'quote_id',
			'owner_id'    => $quote['quote_id'],
			'layout'      => 'table_row',
			'allow_new'   => module_extra::can_i( 'create', 'Quotes' ),
			'allow_edit'  => module_extra::can_i( 'edit', 'Quotes' ),
		),
	);
	if ( module_config::c( 'quote_allow_staff_assignment', 1 ) ) {
		$fieldset_data['elements']['user_id'] = array(
			'title' => 'Staff Member',
			'field' => array(
				'type'    => 'select',
				'options' => $staff_member_rel,
				'name'    => 'user_id',
				'value'   => $quote['user_id'],
				'help'    => 'Assign a staff member to this quote. You can also assign individual tasks to different staff members. Staff members are users who have EDIT permissions on Quote Tasks.',
			),
		);
	}
	$incrementing = false;
	if ( ! isset( $quote['taxes'] ) || ! count( $quote['taxes'] ) ) {
		$quote['taxes'][] = array(); // at least have 1?
	}
	foreach ( $quote['taxes'] as $tax ) {
		if ( isset( $tax['increment'] ) && $tax['increment'] ) {
			$incrementing = true;
		}
	}
	ob_start();
	?>
	<span class="quote_tax_increment">
        <input type="checkbox" name="tax_increment_checkbox" id="tax_increment_checkbox"
               value="1" <?php echo $incrementing ? ' checked' : ''; ?>> <?php _e( 'incremental' ); ?>
    </span>
	<div id="quote_tax_holder">
		<?php
		foreach ( $quote['taxes'] as $id => $tax ) { ?>
			<div class="dynamic_block">
				<input type="hidden" name="tax_ids[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['quote_tax_id'] ) ? (int) $tax['quote_tax_id'] : 0; ?>">
				<input type="text" name="tax_names[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['name'] ) ? htmlspecialchars( $tax['name'] ) : ''; ?>" style="width:30px;">
				@
				<input type="text" name="tax_percents[]" class="dynamic_clear"
				       value="<?php echo isset( $tax['percent'] ) ? htmlspecialchars( number_out( $tax['percent'], module_config::c( 'tax_trim_decimal', 1 ), module_config::c( 'tax_decimal_places', module_config::c( 'currency_decimal_places', 2 ) ) ) ) : ''; ?>"
				       style="width:35px;">%
				<a href="#" class="add_addit"><i class="fa fa-plus"></i></a>
				<a href="#" class="remove_addit"><i class="fa fa-minus"></i></a>
			</div>
		<?php } ?>
	</div>
	<script type="text/javascript">
      ucm.form.dynamic('quote_tax_holder', function () {
          ucm.quote.update_quote_tax();
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
			'value'            => $quote['currency_id'],
			'options_array_id' => 'code',
		),
	);

	// files
	if ( $quote_id > 0 ) {
		ob_start();
		$files = module_file::get_files( array( 'quote_id' => $quote_id ), true );
		if ( count( $files ) > 0 ) {
			?>
			<a href="<?php

			echo module_file::link_generate( false, array(
				'arguments' => array(
					'quote_id' => $quote['quote_id'],
				),
				'data'      => array(
					// we do this to stop the 'customer_id' coming through
					// so we link to the full quote page, not the customer quote page.

					'quote_id' => $quote['quote_id'],
				),
			) ); ?>"><?php echo _l( 'View all %d files in this quote', count( $files ) ); ?></a>
			<?php
		} else {
			echo _l( "This quote has %d files", count( $files ) );
		}
		echo '<br/>';
		?>
		<a href="<?php echo module_file::link_generate( 'new', array(
			'arguments' => array(
				'quote_id' => $quote['quote_id'],
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


	if ( module_config::c( 'quote_enable_description', 1 ) ) {

		if ( ! module_quote::can_i( 'edit', 'Quotes' ) && ! $quote['description'] ) {
			// no description, no ability to edit description, don't show anything.
		} else {
			// can edit description
			$fieldset_data = array(
				'heading' => array(
					'title' => _l( 'Quote Description' ),
					'type'  => 'h3',
				),
				'class'   => 'tableclass tableclass_form tableclass_full',

			);
			if ( module_quote::can_i( 'edit', 'Quotes' ) ) {
				$fieldset_data['elements'] = array(
					array(
						'field' => array(
							'type'  => 'wysiwyg',
							'name'  => 'description',
							'value' => $quote['description'],
						),
					)
				);
			} else {
				$fieldset_data['elements'] = array(
					array(
						'fields' => array(
							module_security::purify_html( $quote['description'] ),
						),
					)
				);
			}
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
	}

	if ( (int) $quote_id > 0 ) {
		$note_summary_owners = array();
		// generate a list of all possible notes we can display for this quote.
		// display all the notes which are owned by all the sites we have access to

		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::display_notes( array(
					'title'       => 'Quote Notes',
					'owner_table' => 'quote',
					'owner_id'    => $quote_id,
					'view_link'   => module_quote::link_open( $quote_id ),
				)
			);
		}

		if ( class_exists( 'module_timer', false ) && module_timer::is_plugin_enabled() && module_config::c( 'timer_enable_quote', 1 ) ) {
			module_timer::display_timers( array(
					'title'       => 'Quote Timers',
					'owner_table' => 'quote',
					'owner_id'    => $quote_id,
					'customer_id' => ! empty( $ticket['customer_id'] ) ? (int) $ticket['customer_id'] : 0,
				)
			);
		}

		if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() ) {
			if ( module_quote::can_i( 'edit', 'Quotes' ) ) {
				module_email::display_emails( array(
					'title'  => 'Quote Emails',
					'search' => array(
						'quote_id' => $quote_id,
					)
				) );
			}
		}

		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			module_group::display_groups( array(
				'title'       => 'Quote Groups',
				'owner_table' => 'quote',
				'owner_id'    => $quote_id,
				'view_link'   => $module->link_open( $quote_id ),

			) );
		}
	}

	// run the custom data hook to display items in this particular hook location
	hook_handle_callback( 'custom_data_hook_location', _CUSTOM_DATA_HOOK_LOCATION_QUOTE_SIDEBAR, 'quote', $quote_id, $quote );

	if ( module_quote::can_i( 'view', 'Quote Advanced' ) ) {

		/***** QUOTE ADVANCED *****/


		$fieldset_data = $UCMQuote->generate_advanced_fieldset();


		if ( (int) $quote_id > 0 && module_quote::can_i( 'edit', 'Quotes' ) ) {

			$fieldset_data['elements'][] = array(
				'title'  => 'Email Staff',
				'fields' => array(
					function () use ( &$quote_tasks, $quote_id ) {
						$allocated_staff_members = array();
						foreach ( $quote_tasks as $quote_task ) {
							if ( ! isset( $allocated_staff_members[ $quote_task['user_id'] ] ) ) {
								$allocated_staff_members[ $quote_task['user_id'] ] = 0;
							}
							$allocated_staff_members[ $quote_task['user_id'] ] ++;
						}
						foreach ( $allocated_staff_members as $staff_id => $count ) {
							$staff = module_user::get_user( $staff_id );
							?>
							<a href="<?php echo module_quote::link_generate( $quote_id, array(
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


		if ( (int) $quote_id > 0 && class_exists( 'module_import_export', false ) && module_import_export::is_plugin_enabled() ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task CSV Data',
				'fields' => array(
					function () use ( $quote_id, $import_tasks_link ) {
						// hack to add a "export" link to this page
						if ( module_quote::can_i( 'view', 'Export Quote Tasks' ) ) {
							?>
							<a href="<?php echo module_quote::link_open( $quote_id, false ) . '&import_export_go=yes'; ?>"
							   class=""><?php _e( 'Export Tasks' ); ?></a>
							<?php
						}
						if ( module_quote::can_i( 'view', 'Import Quote Tasks' ) && module_quote::can_i( 'view', 'Export Quote Tasks' ) ) {
							echo ' / ';
						}
						if ( module_quote::can_i( 'view', 'Import Quote Tasks' ) ) {
							?>
							<a href="<?php echo $import_tasks_link; ?>" class=""><?php _e( 'Import Tasks' ); ?></a>
							<?php
						}
					}
				),
			);
		} else if ( (int) $quote_id > 0 && ! class_exists( 'module_import_export', false ) && module_config::c( 'show_ucm_ads', 1 ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task CSV Data',
				'fields' => array(
					'(import/export available in <a href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}


		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() && module_config::c( 'quote_enable_default_tasks', 1 ) ) {
			$fieldset_data['elements'][] = array(
				'title'  => 'Task Defaults',
				'fields' => array(
					function () use ( $quote_id, &$quote ) {
						$quote_default_tasks = module_quote::get_default_tasks();
						echo print_select_box( $quote_default_tasks, 'default_task_list_id', '', '', true, '', $quote_id > 0 );
						?>
						<?php if ( (int) $quote_id > 0 ) { ?>
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


		if ( class_exists( 'module_quote_discussion', false ) && isset( $quote['quote_discussion'] ) && module_quote_discussion::is_plugin_enabled() ) {
			$fieldset_data['elements'][] = array(
				'title' => 'Quote Discussion',
				'field' => array(
					'type'    => 'select',
					'options' => array(
						0 => _l( 'Allowed' ),
						1 => _l( 'Disabled & Hidden' ),
						2 => _l( 'Disabled & Shown' ),
					),
					'name'    => 'quote_discussion',
					'value'   => isset( $quote['quote_discussion'] ) ? $quote['quote_discussion'] : 0,
				),
			);
		}

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );

	}

	$form_actions = array(
		'class'    => 'action_bar action_bar_left',
		'elements' => array(
			array(
				'type'    => 'save_button',
				'name'    => 'butt_save',
				'onclick' => "$('#form_redirect').val('" . module_quote::link_open( false ) . "');",
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
				'value' => $UCMQuote->is_archived() ? _l( 'Unarchive' ) : _l( 'Archive' ),
			),
			array(
				'ignore' => ! $quote_id || ! function_exists( 'convert_html2pdf' ),
				'type'   => 'submit',
				'name'   => 'butt_print',
				'value'  => _l( 'Print PDF' ),
			),
			array(
				'ignore' => ! $quote_id,
				'type'   => 'submit',
				'name'   => 'butt_email',
				'value'  => _l( 'Email' ),
			),
			array(
				'ignore' => ! ( (int) $quote_id && module_quote::can_i( 'create', 'Quotes' ) ),
				'type'   => 'submit',
				'name'   => 'butt_duplicate',
				'value'  => _l( 'Duplicate' ),
			),
			array(
				'ignore' => ! ( (int) $quote_id && module_quote::can_i( 'delete', 'Quotes' ) ),
				'type'   => 'delete_button',
				'name'   => 'butt_del',
				'value'  => _l( 'Delete' ),
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . module_quote::link_open( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	if ( $do_perm_finish_check ) {
		// we call our permission check
		// render finish method here instead of in index.php
		// this allows quote task edit permissions without quote edit permissions.
		// HOPE THIS WORKS! :)
		module_security::render_page_finished();
	}

	hook_handle_callback( 'layout_column_half', 2, '65' );


	/**** DEPOSIT INVOICE *****/
	if ( $quote_id > 0 ) {
		$jobs = module_job::get_jobs( array( 'quote_id' => $quote_id ) );
		if ( count( $jobs ) ) {
			ob_start();
			?>
			<div class="tableclass_form content">
				<?php foreach ( $jobs as $j ) { ?>
					<p align="center">
						<?php echo _l( 'This Quote has been converted into a Job: %s.', module_job::link_open( $j['job_id'], true, $j ) ); ?>
						<br/>
					</p>
				<?php } ?>
			</div>
			<?php
			$fieldset_data = array(
				'heading'         => array(
					'title' => _l( 'Quote Jobs' ),
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
		if ( $quote['date_approved'] != '0000-00-00' && module_job::can_i( 'create', 'Jobs' ) && ( ! count( $jobs ) || module_config::c( 'quote_allow_multi_jobs', 0 ) ) ) {

			ob_start();
			?>
			<div class="tableclass_form content">
				<div style="text-align: center">
					<?php
					$form_actions = array(
						'class'    => 'custom_actions action_bar_single',
						'elements' => array(
							array(
								'type'    => 'button',
								'name'    => 'butt_convert_to_job',
								'value'   => _l( 'Convert this Quote into a Job' ),
								'onclick' => "window.location.href='" . module_job::link_open( 'new', false ) . "&from_quote_id=$quote_id'; return false;",
							),
						),
					);
					echo module_form::generate_form_actions( $form_actions );
					?>
				</div>
			</div>
			<?php
			$fieldset_data = array(
				'heading'         => array(
					'title' => _l( 'Quote Has Been Approved' ),
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
		if ( $quote['date_approved'] == '0000-00-00' ) {

			ob_start();
			?>
			<div class="tableclass_form content">
				<div style="text-align: center">
					<?php
					$form_actions = array(
						'class'    => 'custom_actions action_bar_single',
						'elements' => array(
							array(
								'type'    => 'button',
								'name'    => 'butt_convert_to_job',
								'value'   => _l( 'Approve this Quote' ),
								'onclick' => "window.location.href='" . module_quote::link_public( $quote_id ) . "'; return false;",
							),
						),
					);
					echo module_form::generate_form_actions( $form_actions );
					?>
				</div>
			</div>
			<?php
			$fieldset_data = array(
				'heading'         => array(
					'title' => _l( 'Quote Requires Approval' ),
					'type'  => 'h3',
				),
				'elements_before' => ob_get_clean(),
			);
			echo module_form::generate_fieldset( $fieldset_data );
			unset( $fieldset_data );
		}
	}


	if ( module_quote::can_i( 'edit', 'Quote Tasks' ) || module_quote::can_i( 'view', 'Quote Tasks' ) ) {

		$header = array(
			'title_final' => _l( 'Quote Tasks %s', ( $quote['total_percent_complete'] > 0 ? _l( '(%s%% completed)', $quote['total_percent_complete'] * 100 ) : '' ) ),
			'button'      => array(),
			'type'        => 'h3',
		);
		ob_start();
		?>
		<div class="content_box_wheader">

			<?php
			$show_task_numbers = ( module_config::c( 'quote_show_task_numbers', 1 ) && $quote['auto_task_numbers'] != 2 );
			?>

			<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full"
			       id="quote_task_listing">
				<thead>
				<tr>
					<?php if ( $show_task_numbers ) { ?>
						<th width="10">#</th>
					<?php } ?>
					<th class="task_column task_width"><?php _e( 'Description' ); ?></th>
					<th width="15" class="task_type_label">
						<?php
						// work out what unit of measurement we are to show here.
						// if all the products are the same unit of measurement then we show that here
						// if the tasks are all different then we show blank here and have the unit of measurement below.
						$unit_measurement = false;
						if ( is_callable( 'module_product::sanitise_product_name' ) ) {
							$fake_task        = module_product::sanitise_product_name( array(), $quote['default_task_type'] );
							$unit_measurement = $fake_task['unitname'];
							foreach ( $quote_tasks as $quote_task_id => $task_data ) {
								if ( isset( $task_data['unitname'] ) && $task_data['unitname'] != $unit_measurement ) {
									$unit_measurement = false;
									break; // show nothing at title of quote page.
								}
							}
						}
						echo _l( $unit_measurement ? $unit_measurement : module_config::c( 'task_default_name', 'Unit' ) );
						?>
					</th>
					<th width="79"><?php _e( module_config::c( 'quote_amount_name', 'Amount' ) ); ?></th>
					<?php if ( module_config::c( 'quote_allow_staff_assignment', 1 ) ) { ?>
						<th width="78"><?php _e( 'Staff' ); ?></th>
					<?php } ?>
					<th width="60"></th>
				</tr>
				</thead>
				<?php
				//module_security::is_page_editable() &&
				if ( module_quote::can_i( 'create', 'Quote Tasks' ) && $quote['date_approved'] == '0000-00-00' ) { ?>
					<tbody>
					<tr>
						<?php if ( $show_task_numbers ) { ?>
							<td valign="top">
								<input type="text" name="quote_task[new][task_order]" value="" id="next_task_number" size="3"
								       class="edit_task_order no_permissions">
							</td>
						<?php } ?>
						<td valign="top">
							<input type="text" name="quote_task[new][description]" id="task_desc_new"
							       class="edit_task_description no_permissions" value=""><?php
							if ( class_exists( 'module_product', false ) ) {
								module_product::print_quote_task_dropdown( 'new' );
							} ?><a href="#" class="task_toggle_long_description"><i class="fa fa-plus"></i></a>
							<div class="task_long_description">
								<?php
								module_form::generate_form_element( array(
									'type'  => module_config::c( 'long_description_wysiwyg', 1 ) ? 'wysiwyg' : 'textarea',
									'name'  => 'quote_task[new][long_description]',
									'id'    => 'task_long_desc_new',
									'class' => 'edit_task_long_description no_permissions',
									'value' => '',
								) );
								?>
							</div>
						</td>
						<td valign="top" class="nowrap">
							<?php if ( $quote['default_task_type'] == _TASK_TYPE_AMOUNT_ONLY ) {
								// no hour input
							} else if ( $quote['default_task_type'] == _TASK_TYPE_QTY_AMOUNT ) { ?>
								<input type="text" name="quote_task[new][hours]" value="" size="3" style="width:25px;"
								       class="no_permissions" id="task_hours_new">
							<?php } else if ( $quote['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
								?>
								<input type="text" name="quote_task[new][hours]" value="" size="3" style="width:25px;"
								       onchange="setamount(this.value,'new');" onkeyup="setamount(this.value,'new');"
								       class="no_permissions" id="task_hours_new">
								<?php
							} ?>
						</td>
						<td valign="top" nowrap="">
							<?php echo currency( '<input type="text" name="quote_task[new][amount]" value="" id="newtaskamount" class="currency no_permissions">' ); ?>
						</td>
						<?php if ( module_config::c( 'quote_allow_staff_assignment', 1 ) ) { ?>
							<td valign="top">
								<?php echo print_select_box( $staff_member_rel, 'quote_task[new][user_id]',
									isset( $staff_member_rel[ module_security::get_loggedin_id() ] ) ? module_security::get_loggedin_id() : false, 'quote_task_staff_list no_permissions', '' ); ?>
							</td>
						<?php } ?>
						<td align="center" valign="top">
							<input type="submit" name="save_ajax_task" value="<?php _e( 'New Task' ); ?>" formtarget="ajax_task_save"
							       class="save_task no_permissions small_button">
							<!-- these are overridden from the products selection -->
							<input type="hidden" name="quote_task[new][billable_t]" value="1">
							<input type="hidden" name="quote_task[new][billable]" value="1" id="billable_t_new">
							<input type="hidden" name="quote_task[new][taxable_t]" value="1">
							<input type="hidden" name="quote_task[new][taxable]"
							       value="<?php echo module_config::c( 'task_taxable_default', 1 ) ? 1 : 0; ?>" id="taxable_t_new">
							<input type="hidden" name="quote_task[new][manual_task_type]" value="-1" id="manual_task_type_new">
						</td>
					</tr>
					</tbody>
				<?php } ?>
				<tbody class="quote_task_wrapper">
				<?php
				$c           = 0;
				$task_number = 0;
				foreach ( $quote_tasks as $quote_task_id => $task_data ) {

					$task_number ++;
					//module_security::is_page_editable() &&
					if ( module_quote::can_i( 'edit', 'Quote Tasks' ) && $quote['date_approved'] == '0000-00-00' ) {
						$task_editable = true;
					} else {
						$task_editable = false;
					}
					echo module_quote::generate_task_preview( $quote_id, $quote, $quote_task_id, $task_data, $task_editable, $unit_measurement );
				} ?>
				</tbody>
			</table>
			<?php if ( (int) $quote_id > 0 ) {
				?>
				<div id="quote_summary"> <?php echo module_quote::generate_quote_summary( $quote_id, $quote ); ?> </div> <?php
			} ?>
		</div>


		<?php

		$fieldset_data = array(
			'heading'         => $header,
			'elements_before' => ob_get_clean(),
		);
		echo module_form::generate_fieldset( $fieldset_data );


	}  // end can i view quote tasks

	// run the custom data hook to display items in this particular hook location
	hook_handle_callback( 'custom_data_hook_location', _CUSTOM_DATA_HOOK_LOCATION_QUOTE_FOOTER, 'quote', $quote_id, $quote );


	hook_handle_callback( 'layout_column_half', 'end' ); ?>
</form>
