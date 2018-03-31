<?php

if ( get_display_mode() != 'mobile' ) {
	//module_config::register_css('theme','left_style.css',full_link('/includes/plugin_theme/themes/left/css/left_style.css'));
	module_config::register_css( 'theme', 'style.css', full_link( '/includes/plugin_theme/themes/whitelabel1/css/style.css' ), 10 );
	module_config::register_css( 'theme', 'theme.css', full_link( '/includes/plugin_theme/themes/whitelabel1/css/light/theme.css' ), 11 );

	//module_config::register_js('global','javascript.js',full_link('/js/javascript.js'));//hardcoded in header
	module_config::register_js( 'theme', 'functions.js', full_link( '/includes/plugin_theme/themes/whitelabel1/js/functions.js' ) );
	//module_config::register_js('theme','config.js',full_link('/includes/plugin_theme/themes/whitelabel1/js/config.js'));
	//module_config::register_js('theme','script.js',full_link('/includes/plugin_theme/themes/whitelabel1/js/script.js'));

	if ( module_security::is_logged_in() ) {
		function whitelabel1_header_js() {
			if ( module_config::c( 'whitelabel_full_width_input', 0 ) ) {
				?>
				<script type="text/javascript">
            $(function () {
                $('.tableclass_form input[type="text"]').each(function () {
                    if (!$(this).hasClass('currency')) {
                        $(this).addClass('full_width');
                        //$(this).css('width','98%');
                    }

                });
            });
				</script>
				<style type="text/css">
					input.full_width {
						width: 98%;
					}

					span.encrypt_popup,
					span.encrypt_create {
						position: relative;
						z-index: 1000;
					}

					span.required {
						margin-right: 6px;
						position: relative;
						z-index: 1000;
						float: right;
						margin-top: -15px;
					}
				</style>
				<?php
			}
		}

		hook_add( 'header_print_js', 'whitelabel1_header_js' );

		function whitelabel1_dashboard_widgets() {
			$widgets = array();
			if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Finance' ) ) {
				// now do the line chart.
				// start content for inside widget.
				ob_start();
				?>

				<script type="text/javascript">

            /*----------------------------------------------------------------------*/
            /* Charts
						 /*----------------------------------------------------------------------*/

            $(function () {
                $('.finance_chart').each(
                    function () {
                        $(this).wl_Chart({
                            onClick: function (value, legend, label, id) {
                                alert('Todo: show popup like other table with more info');
                                //$.msg("value is "+value+" from "+legend+" at "+label+" ("+id+")",{header:'Custom Callback'});
                            },
                            tooltipPattern: function (value, legend, label, id, itemobj) {
                                return legend + " in week of " + label + " was " + value;
                            }
                        });
                    });
            });

				</script>
				<?php


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

				foreach ( $home_summary as $home_sum ) {
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

					<table class="finance_chart">
						<thead>
						<tr>
							<th></th>
							<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
								if ( ! isset( $week_data['week'] ) ) {
									continue;
								}
								?>
								<th>
									<?php echo $week_data['week']; ?>
								</th>
							<?php } ?>
						</tr>
						</thead>
						<tbody>
						<tr>
							<th><?php _e( 'Hours' ); ?></th>
							<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
								if ( ! isset( $week_data['week'] ) ) {
									continue;
								} ?>
								<td><?php echo $week_data['chart_hours']; ?></td>
							<?php } ?>
						</tr>
						<tr>
							<th><?php _e( 'Invoiced' ); ?></th>
							<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
								if ( ! isset( $week_data['week'] ) ) {
									continue;
								} ?>
								<td><?php echo $week_data['chart_amount_invoiced']; ?></td>
							<?php } ?>
						</tr>
						<tr>
							<th><?php _e( 'Income' ); ?></th>
							<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
								if ( ! isset( $week_data['week'] ) ) {
									continue;
								} ?>
								<td><?php echo $week_data['chart_amount_paid']; ?></td>
							<?php } ?>
						</tr>
						<?php if ( module_finance::is_expense_enabled() ) { ?>
							<tr>
								<th><?php _e( 'Expense' ); ?></th>
								<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
									if ( ! isset( $week_data['week'] ) ) {
										continue;
									} ?>
									<td><?php echo $week_data['chart_amount_spent']; ?></td>
								<?php } ?>
							</tr>
						<?php } ?>
						<?php if ( class_exists( 'module_envato', false ) && module_config::c( 'envato_include_in_dashbaord', 1 ) ) { ?>
							<tr>
								<th><?php _e( 'Envato' ); ?></th>
								<?php foreach ( $finance_data['data'] as $week_name => $week_data ) {
									if ( ! isset( $week_data['week'] ) ) {
										continue;
									} ?>
									<td><?php echo $week_data['chart_envato_earnings']; ?></td>
								<?php } ?>
							</tr>
						<?php } ?>
						</tbody>
					</table>
					<?php
				}
				$widgets[] = array(
					'id'      => 'finance_chart',
					'title'   => _l( 'Weekly Finance Chart' ),
					'icon'    => 'piggy_bank',
					'content' => ob_get_clean(),
				);
			}

			return $widgets;
		} // end hook function
		hook_add( 'dashboard_widgets', 'whitelabel1_dashboard_widgets' );

	}

}
