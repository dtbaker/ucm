<?php

$table_manager                   = module_theme::new_table_manager();
$table_manager->table_class = 'public';
$table_manager->row_class = 'public';
$columns                         = array();
$columns['quote_title']          = array(
	'title'      => 'Quote Title',
	'callback'   => function ( $quote ) {
		?> <a href="<?php echo module_quote::link_public( $quote['quote_id'] );?>" target="_blank"><?php echo htmlspecialchars($quote['name']);?></a> <?php
	},
	'cell_class' => 'row_action',
);
$columns['quote_start_date']     = array(
	'title'    => 'Create Date',
	'callback' => function ( $quote ) {
		echo print_date( $quote['date_create'] );
	},
);
$columns['quote_completed_date'] = array(
	'title'    => 'Accepted Date',
	'callback' => function ( $quote ) {
		echo print_date( $quote['date_approved'] );
	},
);
if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
	$columns['quote_website'] = array(
		'title'    => module_config::c( 'project_name_single', 'Website' ),
		'callback' => function ( $quote ) {
			$website = module_website::get_website($quote['website_id']);
			echo htmlspecialchars($website['name']);
		},
	);
}

$columns['quote_status'] = array(
	'title'    => 'Status',
	'callback' => function ( $quote ) {
		echo htmlspecialchars( $quote['status'] );
	},
);
$job_ids                 = array();
$columns['job']          = array(
	'title'    => 'Job',
	'callback' => function ( $quote ) use ( &$job_ids ) {
		$job_ids = array();
		foreach ( module_job::get_jobs( array( 'quote_id' => $quote['quote_id'] ) ) as $job ) {
			$job = module_job::get_job( $job['job_id'] );
			if ( ! $job ) {
				continue;
			}
//				echo module_job::link_open( $job['job_id'], true );
			?> <a href="<?php echo module_job::link_public( $job['job_id'] );?>" target="_blank"><?php echo htmlspecialchars($job['name']);?></a> <?php
			$job_ids[] = $job['job_id'];
			echo " ";
			echo '<span class="';
			if ( $job['total_amount_due'] > 0 ) {
				echo 'error_text';
			} else {
				echo 'success_text';
			}
			echo '">';
			if ( $job['total_amount'] > 0 ) {
				echo dollar( $job['total_amount'], true, $job['currency_id'] );
			}
			echo '</span>';
			echo "<br>";
		}
		if(!$job_ids){
			echo _l('N/A');
		}
	},
);
//if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
	$columns['invoice'] = array(
		'title'    => 'Invoice',
		'callback' => function ( $quote ) use ( &$job_ids ) {
			$invoices = false;
			foreach ( $job_ids as $job_id ) {
				foreach ( module_invoice::get_invoices( array( 'job_id' => $job_id ) ) as $invoice ) {
					$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					if ( ! $invoice ) {
						continue;
					}
					//echo module_invoice::link_open( $invoice['invoice_id'], true );
					?> <a href="<?php echo module_invoice::link_public( $invoice['invoice_id'] );?>" target="_blank"><?php echo htmlspecialchars($invoice['name']);?></a> <?php
					echo " ";
					echo '<span class="';
					if ( $invoice['total_amount_due'] > 0 ) {
						echo 'error_text';
					} else {
						echo 'success_text';
					}
					echo '">';
					if ( $invoice['total_amount_due'] > 0 ) {
						echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
						echo ' ' . _l( 'due' );
					} else {
						echo _l( '%s paid', dollar( $invoice['total_amount'], true, $invoice['currency_id'] ) );
					}
					$invoices = true;
					echo '</span>';
					echo "<br>";
				}
			}
			if(!$invoices){
				echo _l('N/A');
			}
		},
	);
//}

$table_manager->set_columns( $columns );
$table_manager->row_callback = function ( $row_data ) {
	// load the full vendor data before displaying each row so we have access to more details
	return module_quote::get_quote( $row_data['quote_id'] );
};
$table_manager->set_rows( $quotes );
$table_manager->pagination = false;
$table_manager->print_table();