<?php

if ( count( $timers ) ) {
	ob_start();

	/** START TABLE LAYOUT **/
	$table_manager         = module_theme::new_table_manager();
	$columns               = array();
	$columns['timer_name'] = array(
		'title'      => 'Description',
		'callback'   => function ( $timer ) {
			echo module_timer::link_open( $timer['timer_id'], true, $timer );
		},
		'cell_class' => 'row_action',
	);

	$has_linked = false;
	foreach ( $timers as $timer ) {
		if ( ! empty( $timers['owner_child_id'] ) ) {
			$has_linked = true;
		}
	}

	if ( $has_linked ) {
		$columns['linked_data'] = array(
			'title'    => 'Linked',
			'callback' => function ( $timer ) {
				// re-use the autocomplete code to show this information.
				global $plugins;
				if ( ! empty( $timer['owner_table'] ) && ! empty( $timer['owner_id'] ) ) {
					$data = $plugins['timer']->autocomplete_display( $timer['owner_id'], array(
						'owner_table' => $timer['owner_table'],
						'return_link' => true,
					) );
					if ( ! empty( $data ) && is_array( $data ) ) {
						//echo '<a href="' . htmlspecialchars( $data[1] ) . '">' . htmlspecialchars( $data[0] ) . '</a>';
						if ( ! empty( $timer['owner_child_id'] ) ) {
							echo module_timer::get_child_id_link( $timer );
						}
					}
				}
			},
		);
	}

	$columns['timer_status'] = array(
		'title'    => 'Status',
		'callback' => function ( $timer ) {
			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			?>
			<span data-timer-id="<?php echo (int) $timer['timer_id']; ?>" data-timer-field="status">
                <?php echo $ucmtimer->get_status_text();; ?>
            </span>
			<?php

		},
	);

	$columns['timer_duration'] = array(
		'title'    => 'Duration',
		'callback' => function ( $timer ) {
			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			?>
			<span data-timer-id="<?php echo (int) $timer['timer_id']; ?>" data-timer-field="duration">
                <?php echo $ucmtimer->get_total_time();; ?>
            </span>
			<?php
		},
	);
	/*
	if(class_exists('module_group',false)){
		$columns['timer_group'] = array(
			'title' => 'Group',
			'callback' => function($timer){
				if(isset($timer['group_sort_timer'])){
					echo htmlspecialchars($timer['group_sort_timer']);
				}else{
					// find the groups for this timer.
					$groups = module_group::get_groups_search(array(
						'owner_table' => 'timer',
						'owner_id' => $timer['timer_id'],
					));
					$g=array();
					foreach($groups as $group){
						$g[] = $group['name'];
					}
					echo htmlspecialchars(implode(', ',$g));
				}
			}
		);
	}
	if(class_exists('module_extra',false)){
		$table_manager->display_extra('timer',function($timer){
			module_extra::print_table_data('timer',$timer['timer_id']);
		});
	}
	*/
	$columns['timer_action'] = array(
		'title'    => ' ',
		'callback' => function ( $timer ) {
			$ucmtimer = new UCMTimer( $timer['timer_id'] );
			if ( $ucmtimer->timer_status == _TIMER_STATUS_RUNNING && module_timer::can_i( 'edit', 'Timers' ) ) {
				$latest = $ucmtimer->get_latest();
				if ( $latest ) {
					?>
					<a
						href="<?php echo $ucmtimer->link_open( false ); ?>&_process=complete_timer&timer_segment_id=<?php echo (int) $latest['timer_segment_id']; ?>&form_auth_key=<?php echo module_form::get_secure_key(); ?>"
						title="<?php _e( 'Completed' ); ?>"><i
							class="fa fa-check-square"></i></a>
					<?php
				}
			}
			if ( $ucmtimer->timer_status == _TIMER_STATUS_CLOSED && module_timer::can_i( 'delete', 'Timers' ) ) {
				?>
				<a href="<?php echo $ucmtimer->link_open( false ); ?>&_process=delete_timer&butt_del=true&return=linked"
				   data-ajax-modal='{"type":"normal","title":"<?php _e( 'Delete' ); ?>"}'><i
						class="fa fa-times-circle-o"></i></a>
				<?php
			}
		},
	);


	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $timers );

	$total_time      = 0;
	$billable_timers = array();
	if ( ! module_invoice::can_i( 'create', 'Invoices' ) ) {
		$billable_timers = false;
	}
	foreach ( $timers as $timer ) {
		$ucmtimer = new UCMTimer( $timer['timer_id'] );
		if ( is_array( $billable_timers ) && ! $ucmtimer->get_invoice() ) {
			$billable_timers[] = $timer['timer_id'];
		}
		$total_time += $ucmtimer->get_total_time( false, true );
	}
	$footer_row = array(
		'timer_name' => array(
			'data' => _l( 'Total: ' ),
		),
	);
	if ( $has_linked ) {
		$footer_row['linked_data'] = array(
			'data' => '',
		);
	}
	$footer_row['timer_status']   = array(
		'data' => '',
	);
	$footer_row['timer_duration'] = array(
		'data' => '<span class="ongoing-total-time">' . module_timer::format_seconds( $total_time ) . '</span>',
	);
	$footer_row['timer_action']   = array(
		'data' => $billable_timers ? '<a href="' . module_timer::invoice_link( $billable_timers ) . '"class="btn btn-default btn-xs">' . _l( 'Invoice' ) . '</a>' : ''
	);
	$table_manager->set_footer_rows( array( $footer_row ) );

	$table_manager->print_table();

	$timer_table = ob_get_clean();

} else {
	$timer_table = '';
}

$fieldset_data = array();

$fieldset_data['heading']         = array(
	'type'   => 'h3',
	'title'  => $options['title'],
	'help'   => 'This will show a history timers recorded against this item.',
	'button' => array(
		'title'      => _l( 'Start Timer' ),
		'url'        => module_timer::link_open( 'new', false ) . '&return=linked',
		'class'      => 'no_permissions',
		'ajax-modal' => array(
			'type' => 'normal',
		),
	),
);
$fieldset_data['elements_before'] = $timer_table;
echo module_form::generate_fieldset( $fieldset_data );

?>

<script type="text/javascript">
    ucm.set_var('timer_customer_id', '<?php echo (int) $options['customer_id'];?>');
		<?php if(! empty( $options['owner_child_id'] )){ ?>
    ucm.set_var('timer_owner_child_id', '<?php echo (int) $options['owner_child_id'];?>');
		<?php } ?>
    $(function () {
        ucm.timer.load_page_timers('<?php echo $options['owner_table'];?>', <?php echo (int) $options['owner_id'];?> );
			<?php if(empty( $options['avoid_automatic'] )){ ?>
        ucm.timer.automatic_page_timer('<?php echo $options['owner_table'];?>', <?php echo (int) $options['owner_id'];?> );
			<?php } ?>
    });
</script>
