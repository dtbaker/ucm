<?php


if ( ! class_exists( 'module_subscription', false ) || ! module_statistic::can_i( 'view', 'Staff Report' ) ) {
	redirect_browser( _BASE_HREF );
}


$page_title = _l( 'Staff Report' );

$search               = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array(
	'date_from' => print_date( date( 'Y-m-d', strtotime( '-1 month' ) ) ),
	'date_to'   => print_date( date( 'Y-m-d' ) )
);
$subscription_reports = module_statistic::get_statistics_subscription( $search );


if ( class_exists( 'module_table_sort', false ) ) {
	module_table_sort::enable_pagination_hook(
	// pass in the sortable options.
	/*="sort_date"><?php _e('Date'); ?></th>
									<th id="sort_name"><?php _e('Name'); ?></th>
									<th><?php _e('Description'); ?></th>
									<th id="sort_credit"><?php _e('Credit'); ?></th>
									<th id="sort_debit"><?php _e('Debit'); ?></th>
									<th id="sort_account"><?p*/
		array(
			'table_id' => 'statistic_list',
			'sortable' => array(
				// these are the "ID" values of the <th> in our table.
				// we use jquery to add the up/down arrows after page loads.
				'sort_date'   => array(
					'field'   => 'transaction_date',
					'current' => 2, // 1 asc, 2 desc
				),
				'sort_name'   => array(
					'field' => 'name',
				),
				'sort_credit' => array(
					'field' => 'credit',
				),
				'sort_debit'  => array(
					'field' => 'debit',
				),
			),
		)
	);
}
/*

// hack to add a "export" option to the pagination results.
if(class_exists('module_import_export',false) && module_statistic::can_i('view','Export Statistic')){
    module_import_export::enable_pagination_hook(
    // what fields do we pass to the import_export module from this customers?
        array(
            'name' => 'Statistic Export',
            'parent_form' => 'statistic_form',
            'fields'=>array(
                'Date' => 'transaction_date',
                'Name' => 'name',
                'URL' => 'url',
                'Description' => 'description',
                'Credit' => 'credit',
                'Debit' => 'debit',
                'Account' => 'account_name',
                'Categories' => 'categories',
            ),
        )
    );
}
*/


print_heading( array(
	'title' => 'Subscription Report',
	'type'  => 'h2',
	'main'  => true,
) );

?>


<form action="" method="post" id="statistic_form">


	<?php
	$search_bar = array(
		'elements' => array(
			'date' => array(
				'title'  => _l( 'Date:' ),
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => 'search[date_from]',
						'value' => isset( $search['date_from'] ) ? $search['date_from'] : '',
					),
					_l( 'to' ),
					array(
						'type'  => 'date',
						'name'  => 'search[date_to]',
						'value' => isset( $search['date_to'] ) ? $search['date_to'] : '',
					),
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar ); ?>

</form>

<p>&nbsp;</p>

<table class="tableclass tableclass_rows tableclass_full">
	<thead>
	<tr class="title">
		<th id="sort_subscription"><?php _e( 'Subscription Name' ); ?></th>
		<th id="sort_total"><?php _e( 'Total' ); ?></th>
		<th id="sort_totalreceived"><?php _e( 'Invoices Paid' ); ?></th>
		<th id="sort_totalunpaid"><?php _e( 'Invoices Unpaid' ); ?></th>
		<th id="sort_membercount"><?php _e( 'Allocated Members' ); ?></th>
		<th id="sort_customercount"><?php _e( 'Allocated Customers' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$c               = 0;
	$total_total     = array( 0, 0 );
	$total_received  = array( 0, 0 );
	$total_unpaid    = array( 0, 0 );
	$total_members   = 0;
	$total_customers = 0;
	foreach ( $subscription_reports as $subscription_report ) {
		?>
		<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
			<td>
				<?php echo module_subscription::link_open( $subscription_report['subscription_id'], true, $subscription_report ); ?>
			</td>
			<td>
				<?php
				$total_total[0] += ( $subscription_report['total_received_count'] + $subscription_report['total_unpaid_count'] );
				$total_total[1] += ( $subscription_report['total_received'] + $subscription_report['total_unpaid'] ); // todo - multicurrency
				echo $subscription_report['total_received_count'] + $subscription_report['total_unpaid_count']; ?> =
				<?php echo dollar( $subscription_report['total_received'] + $subscription_report['total_unpaid'], true, $subscription_report['currency_id'] ); ?>
			</td>
			<td>
				<?php
				$total_received[0] += ( $subscription_report['total_received_count'] );
				$total_received[1] += ( $subscription_report['total_received'] ); // todo - multicurrency
				echo $subscription_report['total_received_count']; ?> =
				<?php echo dollar( $subscription_report['total_received'], true, $subscription_report['currency_id'] ); ?>
			</td>
			<td>
				<?php
				$total_unpaid[0] += ( $subscription_report['total_unpaid_count'] );
				$total_unpaid[1] += ( $subscription_report['total_unpaid'] ); // todo - multicurrency
				echo $subscription_report['total_unpaid_count']; ?> =
				<?php echo dollar( $subscription_report['total_unpaid'], true, $subscription_report['currency_id'] ); ?>
			</td>
			<td>
				<?php
				$total_members += count( $subscription_report['members'] );
				echo count( $subscription_report['members'] ); ?> <br/>
				<ul>
					<?php foreach ( $subscription_report['members'] as $member_id => $member_data ) { ?>
						<li>
							<?php echo module_member::link_open( $member_id, true ); ?>
							(<?php echo $member_data['received_payments'] . ' = ' . dollar( $member_data['received_total'] ); ?>)
							<?php if ( $member_data['unpaid_payments'] > 0 ) { ?>
								<strong><?php echo $member_data['unpaid_payments']; ?> UNPAID!
									= <?php echo dollar( $member_data['unpaid_total'] ); ?></strong>
							<?php } ?>
						</li>
					<?php } ?>
				</ul>
			</td>
			<td>
				<?php
				$total_customers += count( $subscription_report['customers'] );
				echo count( $subscription_report['customers'] ); ?> <br/>
				<ul>
					<?php foreach ( $subscription_report['customers'] as $customer_id => $customer_data ) { ?>
						<li>
							<?php echo module_customer::link_open( $customer_id, true ); ?>
							(<?php echo $customer_data['received_payments'] . ' = ' . dollar( $customer_data['received_total'] ); ?>)
							<?php if ( $customer_data['unpaid_payments'] > 0 ) { ?>
								<strong><?php echo $customer_data['unpaid_payments']; ?> UNPAID!
									= <?php echo dollar( $customer_data['unpaid_total'] ); ?></strong>
							<?php } ?>
						</li>
					<?php } ?>
				</ul>
			</td>
		</tr>
	<?php } ?>
	<tr>
		<td>
			<?php _e( 'Total:' ); ?>
		</td>
		<td>
			<?php echo $total_total[0] . ' = ' . dollar( $total_total[1] ); ?>
		</td>
		<td>
			<?php echo $total_received[0] . ' = ' . dollar( $total_received[1] ); ?>
		</td>
		<td>
			<?php echo $total_unpaid[0] . ' = ' . dollar( $total_unpaid[1] ); ?>
		</td>
		<td>
			<?php echo $total_members; ?>
		</td>
		<td>
			<?php echo $total_customers; ?>
		</td>
	</tr>
	</tbody>
</table>
