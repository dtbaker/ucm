<?php

$table_manager = module_theme::new_table_manager();
$table_manager->table_class = 'public';
$table_manager->row_class = 'public';
$columns = array();
$columns['job_title'] = array(
	'title' => 'Job Title',
	'callback' => function($job){
		?> <a href="<?php echo module_job::link_public( $job['job_id'] );?>" target="_blank"><?php echo htmlspecialchars($job['name']);?></a> <?php
	},
	'cell_class' => 'row_action',
);
$columns['job_start_date'] = array(
	'title' => 'Started',
	'callback' => function($job){
		echo print_date($job['date_start']);
		//is there a renewal date?
		if(isset($job['date_renew']) && $job['date_renew'] && $job['date_renew'] != '0000-00-00'){
			_e(' to %s',print_date(strtotime("-1 day",strtotime($job['date_renew']))));
		}
	},
);
$columns['job_due_date'] = array(
	'title' => 'Due',
	'callback' => function($job){
		if($job['total_percent_complete']!=1 && strtotime($job['date_due']) < time()){
			echo '<span class="error_text">';
			echo print_date($job['date_due']);
			echo '</span>';
		}else{
			echo print_date($job['date_due']);
		}
	},
);
$columns['job_completed_date'] = array(
	'title' => 'Completed',
	'callback' => function($job){
		echo print_date($job['date_completed']);
	},
);
if(class_exists('module_website',false) && module_website::is_plugin_enabled() ){
	$columns['job_website'] = array(
		'title' => module_config::c('project_name_single','Website'),
		'callback' => function($job){
			$website = module_website::get_website($job['website_id']);
			echo htmlspecialchars($website['name']);
		},
	);
}

$columns['job_type'] = array(
	'title' => 'Type',
	'callback' => function($job){
		echo htmlspecialchars($job['type']);
	},
);
$columns['job_progress'] = array(
	'title' => 'Progress',
	'type' => 'progress_bar',
	'callback' => function($job){
		?> <span data-percent="<?php echo ($job['total_percent_complete']*100);?>" class="progress_bar <?php echo $job['total_percent_complete'] >= 1 ? 'success_text' : ''; ?>">
            <?php echo ($job['total_percent_complete']*100).'%';?>
        </span> <?php
	},
);
$columns['job_total'] = array(
	'title' => 'Total',
	'callback' => function($job){

		if( module_job::is_staff_view($job) ){

		}else {
			?><span class="currency">
			<?php echo dollar( $job['total_amount'], true, $job['currency_id'] ); ?>
			</span>
			<?php
			if ( $job['total_amount_invoiced'] > 0 && $job['total_amount'] != ( $job['total_amount_invoiced'] ) ) { //+$job['total_amount_invoiced_deposit']
				?>
				<br/>
				<span class="currency">
            (<?php echo dollar( $job['total_amount_invoiced'], true, $job['currency_id'] ); ?>)
            </span>
			<?php }
		}
	},
);
$columns['job_total_amount_invoiced'] = array(
	'title' => 'Invoice',
	'callback' => function($job){
		$invoiced = false;
		foreach(module_invoice::get_invoices(array('job_id'=>$job['job_id'])) as $invoice){
			$invoice = module_invoice::get_invoice($invoice['invoice_id']);
			if(!$invoice)continue;
//				echo module_invoice::link_open($invoice['invoice_id'],true);
			?> <a href="<?php echo module_invoice::link_public( $invoice['invoice_id'] );?>" target="_blank"><?php echo htmlspecialchars($invoice['name']);?></a> <?php
			echo " ";
			echo '<span class="';
			if($invoice['total_amount_due']>0){
				echo 'error_text';
			}else{
				echo 'success_text';
			}
			echo '">';
			if($invoice['total_amount_due']>0){
				echo dollar($invoice['total_amount_due'],true,$invoice['currency_id']);
				echo ' '._l('due');
			}else{
				echo _l('%s paid',dollar($invoice['total_amount'],true,$invoice['currency_id']));
			}
			$invoiced = true;
			echo '</span>';
			echo "<br>";
		}
		if(!$invoiced){
			_e('N/A');
		}
	},
);

$table_manager->set_id('job_list');
$table_manager->set_columns($columns);
$table_manager->row_callback = function($row_data){
	// load the full vendor data before displaying each row so we have access to more details
	return module_job::get_job($row_data['job_id']);
};
$table_manager->set_rows($jobs);
$table_manager->pagination = false;
$table_manager->print_table();