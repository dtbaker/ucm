<?php


class module_statistic extends module_base {

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public $version = 2.167;
	// 2.1 - initial release of finance staff report
	// 2.11 - added new "Job Report"
	// 2.12 - added new "Subscription Report" for those with subscription plugin
	// 2.13 - totals added to "Subscription Report".
	// 2.14 - menu fixes
	// 2.15 - new "Tax Report" added
	// 2.16 - 2013-10-08 - tax report shows totals by tax type
	// 2.161 - 2013-10-09 - tax report improvements
	// 2.162 - 2013-11-15 - working on new UI
	// 2.163 - 2014-06-12 - ticket report
	// 2.164 - 2014-06-23 - better tax report for multi currency
	// 2.165 - 2014-11-19 - fix for finance list when assigning customer credit / overpayments
	// 2.166 - 2016-07-10 - big update to mysqli
	// 2.167 - 2017-06-14 - tax report fixes

	function init() {
		$this->links           = array();
		$this->module_name     = "statistic";
		$this->module_position = 28;


	}

	public function pre_menu() {

		if ( class_exists( 'module_finance', false ) ) {
			if ( module_finance::is_enabled() ) {
				$holder_module      = 'finance';
				$holder_module_page = 'finance';
			} else {
				$this->links[]      = array(
					"name" => "Reports",
					"p"    => "statistic_list",
					"args" => array( 'statistic_id' => false ),
				);
				$holder_module      = 'statistic';
				$holder_module_page = 'statistic_list';
			}
			// the link within Finance
			if ( $this->can_i( 'view', 'Staff Report' ) ) {
				$this->links[] = array(
					"name"                => "Staff Report",
					"p"                   => "statistic_staff",
					"args"                => array( 'statistic_id' => false ),
					'holder_module'       => $holder_module, // which parent module this link will sit under.
					'holder_module_page'  => $holder_module_page,  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'position'            => 10,
					'allow_nesting'       => 1,
				);
			}
			if ( $this->can_i( 'view', 'Job Report' ) ) {
				$this->links[] = array(
					"name"                => "Job Report",
					"p"                   => "statistic_job",
					"args"                => array( 'statistic_id' => false ),
					'holder_module'       => $holder_module, // which parent module this link will sit under.
					'holder_module_page'  => $holder_module_page,  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'position'            => 11,
					'allow_nesting'       => 1,
				);
			}
			if ( $this->can_i( 'view', 'Tax Report' ) ) {
				$this->links[] = array(
					"name"                => "Tax Report",
					"p"                   => "statistic_tax",
					"args"                => array( 'statistic_id' => false ),
					'holder_module'       => $holder_module, // which parent module this link will sit under.
					'holder_module_page'  => $holder_module_page,  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'position'            => 12,
					'allow_nesting'       => 1,
				);
			}
			if ( class_exists( 'module_subscription', false ) && $this->can_i( 'view', 'Subscription Report' ) ) {
				$this->links[] = array(
					"name"                => "Subscription Report",
					"p"                   => "statistic_subscription",
					"args"                => array( 'statistic_id' => false ),
					'holder_module'       => $holder_module, // which parent module this link will sit under.
					'holder_module_page'  => $holder_module_page,  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'position'            => 13,
					'allow_nesting'       => 1,
				);
			}
		}
		if ( class_exists( 'module_ticket', false ) ) {

			if ( is_file( 'includes/plugin_statistic/pages/ticket_report_staff.php' ) && $this->can_i( 'view', 'Ticket Staff Report' ) ) {
				$this->links[] = array(
					"name"                => "Ticket Staff Report",
					'm'                   => 'ticket',
					'p'                   => 'ticket_report_staff',
					//'force_current_check' => true,
					'holder_module'       => 'ticket', // which parent module this link will sit under.
					'holder_module_page'  => 'ticket_settings',  // which page this link will be automatically added to.
					'order'               => 11,
					'menu_include_parent' => 1,
					'allow_nesting'       => 1,
					'args'                => array(
						'ticket_account_id' => false,
					)
				);
			}

		}


		/* if(module_security::has_feature_access(array(
		 'name' => 'Settings',
		 'module' => 'config',
		 'category' => 'Config',
		 'view' => 1,
		 'description' => 'view',
 )) && is_file('includes/plugin_statistic/pages/statistic.php')){
	 $this->links[] = array(
		 "name"=>"Statistic",
		 "p"=>"statistic_settings",
		 "icon"=>"icon.png",
		 "args"=>array('statistic_id'=>false),
		 'holder_module' => 'config', // which parent module this link will sit under.
		 'holder_module_page' => 'config_admin',  // which page this link will be automatically added to.
		 'menu_include_parent' => 0,
	 );
 }*/

	}


	public static function link_generate( $statistic_id = false, $options = array(), $link_options = array() ) {

		$key = 'statistic_id';
		if ( $statistic_id === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}
		$bubble_to_module = false;
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'statistic';
		}
		if ( ! isset( $options['page'] ) ) {
			if ( $statistic_id && ! isset( $link_options['stop_bubble'] ) ) {
				$options['page'] = 'statistic_edit';
			} else {
				$options['page'] = 'statistic';
			}
		}

		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['statistic_id'] = $statistic_id;
		$options['module']                    = 'statistic';
		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		} else {
			$data = array();// self::get_statistic($statistic_id,false);
		}
		$options['data'] = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		if ( ( $options['page'] == 'recurring' || $options['page'] == 'statistic_edit' ) && ! isset( $link_options['stop_bubble'] ) ) {
			$link_options['stop_bubble'] = true;
			$bubble_to_module            = array(
				'module'   => 'statistic',
				'argument' => 'statistic_id',
			);
		}
		array_unshift( $link_options, $options );

		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );

		}
	}

	public static function link_open( $statistic_id, $full = false ) {
		return self::link_generate( $statistic_id, array( 'full' => $full ) );
	}


	public function process() {

	}

	public function handle_hook( $hook, $mod = false ) {

	}

	public static function get_statistics_staff( $search ) {
		$staff_members = module_user::get_staff_members();
		$statistics    = array();
		foreach ( $staff_members as $staff_member ) {
			$statistics[ $staff_member['user_id'] ] = array(
				'user_id'           => $staff_member['user_id'],
				'job_ids'           => array(),
				'job_count'         => 0,
				'task_count'        => 0,
				'task_ids'          => array(),
				'task_complete_ids' => array(),
				'tasks_complete'    => 0,
				'hours_logged'      => 0,
				'hours_billed'      => 0,
				'amount_billed'     => 0,
				'amount_invoiced'   => 0,
			);

			$sql = "SELECT COUNT(j.job_id) AS job_count ";
			$sql .= " FROM `" . _DB_PREFIX . "job` j";
			$sql .= " WHERE j.user_id = " . (int) $staff_member['user_id'];
			if ( isset( $search['date_from'] ) && $search['date_from'] ) {
				$sql .= " AND j.date_start >= '" . input_date( $search['date_from'] ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] ) {
				$sql .= " AND j.date_start <= '" . input_date( $search['date_to'] ) . "'";
			}
			$res                                                 = qa1( $sql );
			$statistics[ $staff_member['user_id'] ]['job_count'] = $res['job_count'];

			$sql = "SELECT COUNT(t.task_id) AS task_count ";
			$sql .= " FROM `" . _DB_PREFIX . "task` t";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id";
			$sql .= " WHERE 1";
			$sql .= " AND t.user_id = " . (int) $staff_member['user_id'];
			if ( isset( $search['date_from'] ) && $search['date_from'] ) {
				$sql .= " AND j.date_start >= '" . input_date( $search['date_from'] ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] ) {
				$sql .= " AND j.date_start <= '" . input_date( $search['date_to'] ) . "'";
			}
			$res                                                  = qa1( $sql );
			$statistics[ $staff_member['user_id'] ]['task_count'] = $res['task_count'];

			// tasks completed on this date:
			$sql = "SELECT COUNT(t.task_id) AS task_count ";
			$sql .= " FROM `" . _DB_PREFIX . "task` t";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id";
			$sql .= " WHERE 1";
			$sql .= " AND t.user_id = " . (int) $staff_member['user_id'];
			if ( isset( $search['date_from'] ) && $search['date_from'] ) {
				$sql .= " AND t.date_done >= '" . input_date( $search['date_from'] ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] ) {
				$sql .= " AND t.date_done <= '" . input_date( $search['date_to'] ) . "'";
			}
			$res                                                      = qa1( $sql );
			$statistics[ $staff_member['user_id'] ]['tasks_complete'] = $res['task_count'];


			$sql = "SELECT t.task_id, tl.date_created, t.hours AS task_hours, t.amount, tl.hours AS hours_logged, p.job_id, p.hourly_rate ";
			$sql .= ", tl.create_user_id AS logged_user_id";
			$sql .= " FROM `" . _DB_PREFIX . "task_log` tl ";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON tl.task_id = t.task_id ";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` p ON tl.job_id = p.job_id";
			$sql .= " WHERE 1 ";
			$sql .= " AND ( tl.create_user_id = " . (int) $staff_member['user_id'] . " )"; //t.user_id = ".(int)$staff_member['user_id'] . " OR
			if ( isset( $search['date_from'] ) && $search['date_from'] ) {
				$sql .= " AND tl.log_time >= '" . strtotime( input_date( $search['date_from'] ) . " 00:00:00" ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] ) {
				$sql .= " AND tl.log_time <= '" . strtotime( input_date( $search['date_to'] ) . " 23:59:59" ) . "'";
			}
			//echo $sql;
			$tasks = query( $sql );
			while ( $r = mysqli_fetch_assoc( $tasks ) ) {
				//print_r($r);
				$jobtasks                                                          = module_job::get_tasks( $r['job_id'] );
				$statistics[ $staff_member['user_id'] ]['job_ids'][ $r['job_id'] ] = true;
				$task                                                              = $jobtasks[ $r['task_id'] ];
				// this user has been assiged to this job individual task.
				if ( $task['fully_completed'] ) {
					$statistics[ $staff_member['user_id'] ]['task_complete_ids'][ $r['task_id'] ] = true;
					$statistics[ $staff_member['user_id'] ]['hours_billed']                       += $r['task_hours'];

					if ( $task['amount'] > 0 ) {
						$statistics[ $staff_member['user_id'] ]['amount_billed'] += $task['amount'];
					} else {
						$statistics[ $staff_member['user_id'] ]['amount_billed'] += ( $r['task_hours'] * $r['hourly_rate'] );
					}

					$sql          = "SELECT  * FROM `" . _DB_PREFIX . "invoice_item` ii WHERE ii.task_id = " . (int) $r['task_id'];
					$task_invoice = qa1( $sql );
					if ( $task_invoice && $task_invoice['task_id'] == $r['task_id'] ) {
						if ( $task_invoice['amount'] > 0 ) {
							$statistics[ $staff_member['user_id'] ]['amount_invoiced'] += $task_invoice['amount'];
						} else {
							$statistics[ $staff_member['user_id'] ]['amount_invoiced'] += ( $task_invoice['hours'] * $task_invoice['hourly_rate'] );
						}
					}
				}

				$statistics[ $staff_member['user_id'] ]['task_ids'][ $r['task_id'] ] = true;
				$statistics[ $staff_member['user_id'] ]['hours_logged']              += $r['hours_logged'];


			}
			//$statistics[$staff_member['user_id']]['job_count'] = count($statistics[$staff_member['user_id']]['job_ids']);

		}

		return $statistics;
	}

	public static function get_statistics_subscription( $search ) {


		$subscriptions = module_subscription::get_subscriptions();
		$return        = array();
		foreach ( $subscriptions as $subscription ) {
			$return[ $subscription['subscription_id'] ]                         = $subscription;
			$return[ $subscription['subscription_id'] ]['total_received']       = 0;
			$return[ $subscription['subscription_id'] ]['total_received_count'] = 0;
			$return[ $subscription['subscription_id'] ]['total_unpaid']         = 0;
			$return[ $subscription['subscription_id'] ]['total_unpaid_count']   = 0;
			$return[ $subscription['subscription_id'] ]['members']              = array();
			$return[ $subscription['subscription_id'] ]['customers']            = array();


			// find all subscription_history's between these days

			$sql = "SELECT * ";
			$sql .= " FROM `" . _DB_PREFIX . "invoice` i";
			$sql .= " RIGHT JOIN `" . _DB_PREFIX . "subscription_history` sh ON i.invoice_id = sh.invoice_id ";
			$sql .= " WHERE sh.subscription_id = " . (int) $subscription['subscription_id'];
			if ( isset( $search['date_from'] ) && $search['date_from'] ) {
				$sql .= " AND i.date_create >= '" . input_date( $search['date_from'] ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] ) {
				$sql .= " AND i.date_create <= '" . input_date( $search['date_to'] ) . "'";
			}
			$res = qa( $sql );
			// this is a list of invoices for these subscriptions from these date periods.
			//print_r($res); return;
			foreach ( $res as $r ) {
				$invoice                                                      = module_invoice::get_invoice( $r['invoice_id'] );
				$return[ $subscription['subscription_id'] ]['total_received'] += $invoice['total_amount_paid'];
				if ( $invoice['total_amount_paid'] > 0 ) {
					$return[ $subscription['subscription_id'] ]['total_received_count'] ++;
				}
				$return[ $subscription['subscription_id'] ]['total_unpaid'] += $invoice['total_amount_due'];
				if ( $invoice['total_amount_due'] > 0 ) {
					$return[ $subscription['subscription_id'] ]['total_unpaid_count'] ++;
				}

				if ( $r['customer_id'] ) {
					if ( ! isset( $return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ] ) ) {
						$return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ] = array(
							'customer_id'       => $r['customer_id'],
							'received_payments' => 0,
							'unpaid_payments'   => 0,
							'received_total'    => 0,
							'unpaid_total'      => 0,
						);
					}
					if ( $invoice['total_amount_paid'] > 0 ) {
						$return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ]['received_payments'] ++;
					}
					if ( $invoice['total_amount_due'] > 0 ) {
						$return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ]['unpaid_payments'] ++;
					}
					$return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ]['received_total'] += $invoice['total_amount_paid'];
					$return[ $subscription['subscription_id'] ]['customers'][ $r['customer_id'] ]['unpaid_total']   += $invoice['total_amount_due'];
				}
				if ( $r['member_id'] ) {
					if ( ! isset( $return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ] ) ) {
						$return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ] = array(
							'member_id'         => $r['member_id'],
							'received_payments' => 0,
							'unpaid_payments'   => 0,
							'received_total'    => 0,
							'unpaid_total'      => 0,
						);
					}
					if ( $invoice['total_amount_paid'] > 0 ) {
						$return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ]['received_payments'] ++;
					}
					if ( $invoice['total_amount_due'] > 0 ) {
						$return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ]['unpaid_payments'] ++;
					}
					$return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ]['received_total'] += $invoice['total_amount_paid'];
					$return[ $subscription['subscription_id'] ]['members'][ $r['member_id'] ]['unpaid_total']   += $invoice['total_amount_due'];
				}
			}


		}

		return $return;
	}

	public static function get_statistics_jobs( $search ) {

		$results = array();

		// any jobs that were created within this time period
		$sql = "SELECT * FROM `" . _DB_PREFIX . "job` j WHERE 1";
		if ( isset( $search['type'] ) && $search['type'] ) {
			$sql .= " AND j.`type` = '" . db_escape( $search['type'] ) . "'";
		}
		if ( isset( $search['date_from'] ) && $search['date_from'] ) {
			$sql .= " AND j.date_start >= '" . input_date( $search['date_from'] ) . "'";
		}
		if ( isset( $search['date_to'] ) && $search['date_to'] ) {
			$sql .= " AND j.date_start <= '" . input_date( $search['date_to'] ) . "'";
		}
		$results = qa( $sql );

		// find any jobs that are due to be renewed within this time period
		$sql = "SELECT * FROM `" . _DB_PREFIX . "job` j WHERE 1";
		$sql .= " AND j.date_renew != '0000-00-00' ";
		if ( isset( $search['type'] ) && $search['type'] ) {
			$sql .= " AND j.`type` = '" . db_escape( $search['type'] ) . "'";
		}
		if ( isset( $search['date_from'] ) && $search['date_from'] ) {
			$sql .= " AND j.date_renew >= '" . input_date( $search['date_from'] ) . "'";
		}
		if ( isset( $search['date_to'] ) && $search['date_to'] ) {
			$sql .= " AND j.date_renew <= '" . input_date( $search['date_to'] ) . "'";
		}
		$sql .= " AND (renew_job_id IS NULL OR renew_job_id = 0)";
		foreach ( qa( $sql ) as $renewed_job ) {
			$renewed_job['renew_from_job_id'] = $renewed_job['job_id'];
			$time_diff                        = strtotime( $renewed_job['date_renew'] ) - strtotime( $renewed_job['date_start'] );
			$date_renew                       = $renewed_job['date_renew'];
			$renewed_job['date_renew']        = date( 'Y-m-d', strtotime( $renewed_job['date_renew'] ) + $time_diff );
			$renewed_job['date_start']        = $date_renew;
			$results[]                        = $renewed_job;
		}

		// any jobs due for renewal before this time period, that haven't been renewed.
		// calculate their next renewal date(s) and see if one of them lands in time period.
		if ( isset( $search['date_from'] ) && $search['date_from'] && isset( $search['date_to'] ) && $search['date_to'] ) {
			$from_timestamp = strtotime( input_date( $search['date_from'] ) );
			$to_timestamp   = strtotime( input_date( $search['date_to'] ) );

			$sql = "SELECT * FROM `" . _DB_PREFIX . "job` j WHERE 1";
			if ( isset( $search['type'] ) && $search['type'] ) {
				$sql .= " AND j.`type` = '" . db_escape( $search['type'] ) . "'";
			}
			$sql .= " AND j.date_start != '0000-00-00' ";
			$sql .= " AND j.date_renew != '0000-00-00' ";
			//$sql .= " AND j.date_start < '".input_date($search['date_from'])."'";
			$sql .= " AND j.date_renew < '" . input_date( $search['date_to'] ) . "'";
			$sql .= " AND (j.renew_job_id IS NULL OR j.renew_job_id = 0)";
			foreach ( qa( $sql ) as $possible_renewed_job ) {
				$time_diff        = strtotime( $possible_renewed_job['date_renew'] ) - strtotime( $possible_renewed_job['date_start'] );
				$new_renewal_date = strtotime( $possible_renewed_job['date_renew'] );
				for ( $x = 0; $x < 5; $x ++ ) {
					$new_renewal_date = $new_renewal_date + $time_diff;
					if ( $new_renewal_date >= $from_timestamp ) {
						// this job will be renewed in our period! yay!
						if ( $to_timestamp == 0 || ( $to_timestamp > 0 && $new_renewal_date <= $to_timestamp ) ) {
							// this is within our bounds! yay!
							$possible_renewed_job['renew_from_job_id'] = $possible_renewed_job['job_id'];
							$possible_renewed_job['date_start']        = date( 'Y-m-d', $new_renewal_date );
							$possible_renewed_job['date_renew']        = date( 'Y-m-d', $new_renewal_date + $time_diff );
							$results[]                                 = $possible_renewed_job;
						} else {
							break;// gone too far
						}
					}
				}
			}
		}

		usort( $results, array( 'module_statistic', "get_statistics_jobs_sort" ) );

		return $results;
	}

	/* This is the static comparing function: */
	static function get_statistics_jobs_sort( $a, $b ) {
		$al = strtotime( $a['date_start'] );
		$bl = strtotime( $b['date_start'] );
		if ( $al == $bl ) {
			return 0;
		}

		return ( $al > $bl ) ? + 1 : - 1;
	}


	public static function get_statistics_tax( $search ) {

		// we grab a search on the transactions (the same as clicking search on the transactions page)
		// and filter out tax from those results here.

		$results = module_finance::get_finances( $search );


		return $results;
	}


	public function get_upgrade_sql() {

	}

	public function get_install_sql() {
		return false;
	}
}