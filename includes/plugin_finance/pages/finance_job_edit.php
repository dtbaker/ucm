<?php
$transactions = module_finance::get_finances(array('job_id'=>$job_id));
$job_finance_totals = array();
foreach($transactions as $transaction){
    if(!isset($job_finance_totals[$transaction['currency_id']])){
	    $job_finance_totals[$transaction['currency_id']] = array(
	            'credit' => 0,
	            'debit' => 0,
        );
    }
	$job_finance_totals[$transaction['currency_id']]['credit'] += $transaction['credit'];
	$job_finance_totals[$transaction['currency_id']]['debit'] += $transaction['debit'];
}


/** START TABLE LAYOUT **/
$table_manager = module_theme::new_table_manager();
$columns = array();
$columns['transaction_date'] = array(
	'title' => 'Date',
	'callback' => function($transaction){
		?> <a href="<?php echo $transaction['url'];?>"><?php echo print_date($transaction['transaction_date']);?></a> <?php
	}
);
$columns['url'] = array(
	'title' => 'Name',
	'callback' => function($transaction){
		?> <a href="<?php echo $transaction['url'];?>"><?php echo !trim($transaction['name']) ? 'N/A' :    htmlspecialchars($transaction['name']);?></a> <?php
	}
);
$columns['description'] = array(
	'title' => 'Description'
);
$columns['credit'] = array(
	'title' => 'Credit',
	'callback' => function($transaction){
		?> <span class="success_text"><?php echo $transaction['credit'] > 0 ? '+'.dollar($transaction['credit'],true,$transaction['currency_id']) : '';?></span> <?php
	}
);
$columns['debit'] = array(
	'title' => 'Debit',
	'callback' => function($transaction){
		?> <span class="error_text"><?php echo $transaction['debit'] > 0 ? '-'.dollar($transaction['debit'],true,$transaction['currency_id']) : '';?></span> <?php
	}
);

$table_manager->set_id('job_finance_list');
$table_manager->set_columns($columns);
$table_manager->set_rows($transactions);
$table_manager->pagination = false;

if(count($transactions) > 1) {
	$footer_rows = array();
	foreach ( $job_finance_totals as $currency_id => $totals ) {
		$currency      = get_single( 'currency', 'currency_id', $currency_id );
		$footer_rows[] = array(
			'transaction_date' => array(
				'data'         => '<strong>' . _l( '%s Sub-Total:', $currency && isset( $currency['code'] ) ? $currency['code'] : '' ) . '</strong>',
				'cell_colspan' => 3,
				'cell_class'   => 'text-right',
			),
			'credit'           => array(
				'data' => '<span class="success_text">' . ( ! empty( $totals['credit'] ) ? '+' . dollar( $totals['credit'] ) : '' ) . '</span>',
			),
			'debit'            => array(
				'data' => '<span class="error_text">' . ( ! empty( $totals['debit'] ) ? '-' . dollar( $totals['debit'] ) : '' ) . '</span>',
			),
		);
		$total_total = $totals['credit'] - $totals['debit'];
		$footer_rows[] = array(
			'transaction_date' => array(
				'data'         => '<strong>' . _l( '%s Total:', $currency && isset( $currency['code'] ) ? $currency['code'] : '' ) . '</strong>',
				'cell_colspan' => 3,
				'cell_class'   => 'text-right',
			),
			'credit'           => array(
				'data' => '<span class="success_text">' . ( $total_total > 0 ? '+' . dollar( $total_total ) : '' ) . '</span>',
			),
			'debit'            => array(
				'data' => '<span class="error_text">' . ( $total_total < 0 ? '-' . dollar( abs($total_total) ) : '' ) . '</span>',
			),
		);
	}
	$table_manager->set_footer_rows( $footer_rows );
}

ob_start();
$table_manager->print_table();
/** END TABLE LAYOUT **/


$fieldset_data = array(
	'heading' =>  array(
		'title'=>'Job Finances:',
		'type'=>'h3',
		'button'=>array(
			'title'=>_l('Add New'),
			'url'=>module_finance::link_open('new').'&from_job_id='.$job_id,
		)
	) ,
	'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset($fieldset_data);

