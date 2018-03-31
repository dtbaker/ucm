<?php

if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Finance' ) ) {

	// now do the line chart.
	// start content for inside widget.
	ob_start();


	$show_previous_weeks = module_config::c( 'dashboard_graph_previous_weeks', 10 );
	$show_coming_weeks   = module_config::c( 'dashboard_graph_coming_weeks', 7 );
	$home_summary        = array(
		array(
			"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - ( ( $show_previous_weeks ) * 7 ) + 1, date( 'Y' ) ) ),
			// 7 weeks ago
			//"week_end" => date('Y-m-d', strtotime('-1 day',mktime(1, 0, 0, date('m'), date('d')+(6-date('N'))-(2*7)+2, date('Y')))), // 2 weeks ago
			"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) + 2, date( 'Y' ) ) ),
			// today
			'table_name' => 'Finance Chart',
			'array_name' => 'finance_chart',
			'multiplyer' => 7,
			'col1'       => 'week',
			'row_limit'  => $show_previous_weeks,
		),
	);

	$x = 0;
	foreach ( $home_summary as $home_sum ) {
		$x ++;
		extract( $home_sum ); // hacky, better than old code tho.
		$data = module_finance::get_finance_summary( $week_start, $week_end, $multiplyer, $row_limit );
		// return the bits that will be used in the output of the HTML table (and now in the calendar module output)
		$finance_data = array(
			'data'       => $data,
			'table_name' => $table_name,
			'col1'       => $col1,
		);
		//print_r($finance_data);
		?>

		<div id="finance_chart_<?php echo $x; ?>"></div>
		<script type="text/javascript">/* Morris.js Charts */
        // Sales chart
        var chart_data = [];
			<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
			if ( ! isset( $week_data['week'] ) ) {
				continue;
			}?>
        chart_data.push({
            y: '<?php echo $week_data['week'];?>',
            item1: <?php echo $week_data['chart_hours'];?>,
            item2: <?php echo $week_data['chart_amount_invoiced'];?>,
            item3: <?php echo $week_data['chart_amount_paid'];?>
					<?php if ( module_finance::is_expense_enabled() ) { ?>,
            item4: <?php echo $week_data['chart_amount_spent'];?>
					<?php } if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
					?>,
            item5: <?php echo $week_data['chart_envato_earnings'];?> <?php
					} ?>
        });
			<?php } ?>
        var area = new Morris.Line({
            element: 'finance_chart_<?php echo $x;?>',
            resize: true,
            data: chart_data,
            xkey: 'y',
            ykeys: ['item1', 'item2', 'item3'<?php if ( module_finance::is_expense_enabled() ) { ?>, 'item4'
							<?php } if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
							?>, 'item5' <?php
							} ?>],
            labels: ['<?php _e( 'Hours' ); ?>', '<?php _e( 'Invoiced' ); ?>', '<?php _e( 'Income' ); ?>'
							<?php if ( module_finance::is_expense_enabled() ) { ?>, '<?php _e( 'Expense' ); ?>'
							<?php }
							if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) {
							?>, '<?php _e( 'Envato' ); ?>' <?php
							}?>],
            lineColors: ['#a0d0e0', '#3c8dbc', '#0f720a', '#b8180c', '#59b80c'],
            hideHover: 'auto',
            parseTime: false
        });</script>

		<?php
	}
	$widgets[] = array(
		'id'      => 'finance_chart',
		'title'   => _l( 'Weekly Finance Chart' ),
		'icon'    => 'piggy_bank',
		'columns' => 2,
		'content' => ob_get_clean(),
	);
}