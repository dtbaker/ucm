<?php

define( '_JOB_TASK_CREATION_NOT_ALLOWED', 'Unable to create new tasks' );
define( '_JOB_TASK_CREATION_REQUIRES_APPROVAL', 'Created tasks require admin approval' );
define( '_JOB_TASK_CREATION_WITHOUT_APPROVAL', 'Created tasks do not require approval' );

define( '_JOB_TASK_ACCESS_ALL', 'All tasks within a job' );
define( '_JOB_TASK_ACCESS_ASSIGNED_ONLY', 'Only assigned tasks within a job' );

define( '_JOB_ACCESS_ALL', 'All jobs in system' );
define( '_JOB_ACCESS_ASSIGNED', 'Only jobs I am assigned to' );
define( '_JOB_ACCESS_CUSTOMER', 'Jobs from customers I have access to' );

define( '_TASK_DELETE_KEY', '-DELETE-' );


define( '_TASK_TYPE_AMOUNT_ONLY', 2 );
define( '_TASK_TYPE_QTY_AMOUNT', 1 );
define( '_TASK_TYPE_HOURS_AMOUNT', 0 );

define( '_CUSTOM_DATA_HOOK_LOCATION_JOB_SIDEBAR', 4 );
define( '_CUSTOM_DATA_HOOK_LOCATION_JOB_FOOTER', 5 );

//define('_TASK_TYPE_NORMAL',0);
//define('_TASK_TYPE_DEPOSIT',1);


class module_job extends module_base {

	public $links;
	public $job_types;

	public $version = 2.615;
	//2.615 - 2018-06-05 - fix for hourly rate when creating a job
	//2.614 - 2017-05-24 - quote_copy_job_name setting
	//2.613 - 2017-05-16 - ajax autocomplete option for jobs in extra fields
	//2.612 - 2017-05-02 - tax display at 0%
	//2.611 - 2017-05-02 - archive fix
	//2.610 - 2017-05-02 - big changes
	//2.609 - 2017-04-19 - start work on vendor jobs
	//2.608 - 2017-03-01 - archived jobs
	//2.607 - 2017-02-26 - modal job task popup
	//2.606 - 2017-01-02 - job number incrementing fix
	//2.605 - 2016-11-27 - job staff amount fixes
	//2.604 - 2016-11-23 - job quote linking fix
	//2.603 - 2016-11-16 - fontawesome icon fixes
	//2.602 - 2016-10-30 - modal improvements
	//2.601 - 2016-08-12 - job task wysiwyg fix
	//2.600 - 2016-07-10 - big update to mysqli
	//2.599 - 2016-06-23 - job wysigyg task description
	//2.598 - 2016-05-15 - unit of measurement
	//2.597 - 2016-04-30 - basic inventory management
	//2.596 - 2016-02-05 - customer assignment in jobs
	//2.595 - 2016-02-02 - edit task css fix
	//2.594 - 2016-01-23 - header button improvement
	//2.593 - 2015-12-28 - menu speed up
	//2.592 - 2015-12-28 - job extra permission fix
	//2.591 - 2015-12-08 - hook locations for custom data
	//2.59 - 2015-10-19 - hook locations for custom data
	//2.589 - 2015-09-27 - search and sort by extra fields
	//2.588 - 2015-09-09 - js bug fix
	//2.587 - 2015-07-19 - job tax discount fix
	//2.586 - 2015-05-08 - job time available on create page
	//2.585 - 2015-04-12 - job renewal task completion fix
	//2.584 - 2015-04-05 - job task approval/rejection email fix
	//2.583 - 2015-03-08 - job renewal tax fix
	//2.582 - 2015-03-07 - calendar task db error fix
	//2.581 - 2015-03-07 - job task approval fix
	//2.58 - 2015-02-12 - product defaults (tax/bill/type)
	//2.579 - 2015-02-12 - better task approval/rejection with email message
	//2.578 - 2015-02-05 - job ajax saving bug fix
	//2.577 - 2015-01-23 - hook for custom data integration
	//2.576 - 2015-01-19 - fix for assigned staff convert from quote
	//2.575 - 2015-01-16 - hooks added to table print out and data processing
	//2.574 - 2014-12-27 - bug fix on import/export job tasks
	//2.573 - 2014-12-22 - task decimal places
	//2.572 - 2014-11-05 - job_allow_quotes default change
	//2.571 - 2014-11-04 - only job assigned to permission bug fix
	//2.57 - 2014-10-15 - advanced setting calendar_show_jobs and calendar_show_job_tasks
	//2.569 - 2014-10-13 - blank job fix
	//2.568 - 2014-10-13 - search job by staff member
	//2.567 - 2014-10-08 - blank job fix
	//2.566 - 2014-10-06 - job_send_staff_task_email_automatically and job tasks added to calendar
	//2.565 - 2014-10-06 - job_send_staff_task_email_automatically added
	//2.564 - 2014-09-28 - better caching improvements
	//2.563 - 2014-09-18 - job_send_task_completion_email_automatically improvement
	//2.562 - 2014-09-16 - job file fix
	//2.561 - 2014-09-09 - job_task_completion_email template and job_send_task_completion_email_automatically flag
	//2.56 - 2014-09-03 - negative taxes
	//2.559 - 2014-08-25 - job translation fixes
	//2.558 - 2014-08-19 - tax_decimal_places and tax_trim_decimal
	//2.557 - 2014-08-06 - dashboard bug fix
	//2.556 - 2014-07-26 - staff split amounts
	//2.555 - 2014-07-26 - working on staff split amounts
	//2.554 - 2014-07-22 - job translation improvement Qty/Amount/Discount
	//2.553 - 2014-07-21 - working on staff split amounts
	//2.552 - 2014-07-18 - working on staff split amounts
	//2.551 - 2014-07-18 - cron job renewal fix
	//2.55 - 2014-07-18 - cron job renewal fix
	//2.549 - 2014-07-14 - job hourly rate improvements
	//2.548 - 2014-07-13 - calendar job improvements
	//2.547 - 2014-07-10 - job layout fixes
	//2.546 - 2014-07-08 - quote and job discounts
	//2.545 - 2014-06-27 - job default task fix
	//2.544 - 2014-06-24 - fix for negative quantites and amounts
	//2.543 - 2014-06-09 - hours:minutes task formatting
	//2.542 - 2014-05-26 - import matching job id fix
	//2.541 - 2014-04-09 - job page speed improvements
	//2.54 - 2014-04-03 - speed improvements
	//2.539 - 2014-03-31 - css fix on job create page
	//2.538 - 2014-03-26 - save and return button added
	//2.537 - 2014-03-26 - displaying jobs in calendar
	//2.536 - 2014-03-16 - multiple tax support in jobs
	//2.535 - 2014-02-18 - timer js fix
	//2.534 - 2014-02-17 - hide the job hours summary for non-hourly jobs
	//2.532 - 2014-02-14 - convert quote to job fix
	//2.531 - 2014-02-12 - convert quote to job fix
	//2.53 - 2014-02-06 - number_trim_decimals advanced settings
	//2.529 - 2014-02-05 - new quote feature
	//2.528 - 2014-01-20 - new quote feature
	//2.527 - 2013-12-30 - create invoice button permission fix
	//2.526 - 2013-12-19 - extra fields when creating a job
	//2.525 - 2013-12-15 - support for negative task amounts
	//2.524 - 2013-12-11 - permission improvements
	//2.523 - 2013-12-06 - job description box added
	//2.522 - 2013-12-01 - job description box added
	//2.521 - 2013-12-01 - bug fixing
	//2.52 - 2013-11-20 - create new invoice popup improvement
	//2.519 - 2013-11-15 - working on new UI
	//2.518 - 2013-11-13 - dashboard error fix
	//2.517 - 2013-11-11 - dashboard error fix
	//2.516 - 2013-10-21 - dashboard hours logged fix
	//2.515 - 2013-10-06 - feature to invoice incomplete job tasks
	//2.514 - 2013-10-05 - Settings > Invoice - option to choose what time of day for renewals/emails to occur
	//2.513 - 2013-10-04 - option to disable website plugin
	//2.512 - 2013-10-02 - disable website plugin fix
	//2.511 - 2013-09-15 - dashboard please generate invoice fix
	//2.51 - 2013-09-12 - dashboard speed improvement and better caching
	//2.509 - 2013-09-11 - dashboard speed improvement and better caching
	//2.508 - 2013-09-10 - dashboard speed improvement and better caching
	//2.507 - 2013-09-09 - dashboard alerts fix
	//2.506 - 2013-09-06 - easier to disable certain plugins
	//2.505 - 2013-09-03 - change job hourly rate fix
	//2.504 - 2013-09-03 - invoice_auto_renew_only_paid_invoices config variable added
	//2.503 - 2013-08-30 - saving renewable jobs fix
	//2.502 - 2013-08-30 - added memcache support for huge speed improvements
	//2.501 - 2013-08-27 - big new feature - automated job renewals and invoicing
	//2.499 - 2013-08-13 - customer permission improvement
	//2.498 - 2013-07-29 - new _UCM_SECRET hash in config.php
	//2.497 - 2013-07-15 - dashboard fixes
	//2.496 - 2013-06-21 - permission update
	//2.495 - 2013-06-17 - job improvement when invoice has discount
	//2.494 - 2013-06-14 - job improvement when invoice has discount
	//2.493 - 2013-05-28 - template tag improvements
	//2.492 - 2013-05-27 - dashboard alert improvements
	//2.491 - 2013-05-23 - dashboard alert fixes
	//2.49 - 2013-04-26 - job_public.php custom fix
	//2.489 - 2013-04-26 - deposit based on percent
	//2.488 - 2013-04-26 - deposit based on percent
	//2.487 - 2013-04-21 - number format improvements
	//2.486 - 2013-04-20 - new permissions: Access only assigned job tasks
	//2.485 - 2013-04-16 - fix for invoice tax generation
	//2.484 - 2013-04-16 - new advanced field task_taxable_default

	// old version information prior to 2013-04-16:
	//2.422 - create job with single customer auto select in drop down fix.
	//2.423 - fix for saving extra fields against renewed jobs.
	//2.424 - delete job from group
	//2.425 - permission tweak.
	//2.426 - label change, remove 'Assign '
	//2.43 - new theme layout.
	//2.431 - job emailing
	//2.432 - job discussion hook
	//2.433 - menu fix
	//2.434 - customise the Hours column header
	//2.435 - removed setting "New" status on incomplete jobs
	//2.436 - testing non-taxable items
	//2.437 - permission fix - allow job task edit without job edit. plus staff member listing changed to only with EDIT TASKS
	//2.438 - task defaults fix
	//2.439 - external link only visible to edit task permissions
	//2.44 - bit of a (hopeful) fix on job task edit permissions
	//2.441 - email to default contact first
	//2.442 - job date on renewals
	//2.443 - fix for logging hours against tasks
	//2.444 - creating new jobs - auto fill task name
	//2.445 - create invoice from job, better button!
	//2.446 - search by job type
	//2.447 - quick search on job name fixed
	//2.448 - job import and export fix
	//2.449 - additional sortable "invoice total" in jobs listing
	//2.450 - bug fix for job currency in "edit website" page
	//2.451 - incrementing job numbers (see advanced 'job_name_incrementing' option), added 'job_task_lock_invoiced_items' advanced option too
	//2.452 - more fields added to external_job template - same fields as invoice print (eg: customer name, customer group, extra fields, etc...)
	//2.453 - add manual task amount back to job invoice total only when invoice is not a merged invoice
	//2.454 - bug fix: non-billable items + non-taxable items causing issues with "tax amount" and "create invoice" amount
	//2.455 - bug fix: create invoice button
	//2.456 - email staff a copy of assigned jobs.
	//2.457 - if staff has no 'view' invoice permission, job prices are hidden.
	//2.458 - starting work on handling job deposits and customer credit
	//2.459 - bug fix - import job tasks.
	//2.460 - job status fix
	//2.461 - currency fixes and email features
	//2.462 - better support for Job Quotes, added Quote Date. Alerts to homepage.
	//2.463 - dashboard link permission fixes
	//2.464 - choose different templates upon emailing a job to staff/customer.
	//2.465 - job finance linking
	//2.466 - mobile updates
	//2.467 - starting work on Job Products
	//2.468 - extra fields update - show in main listing option
	//2.469 - update for job discussion
	//2.47 - speed improvements
	//2.471 - search by completed/not-completed/quoted status
	//2.472 - bug fix: assigning job to a new customer when already assigned to a website
	//2.473 - delete task defaults by saving an empty task list.
	//2.474 - js improvement on editing tasks
	//2.475 - job deposit fix
	//2.476 - permission improments for unasigned customer jobs
	//2.477 - big update - manual task percent, task type (hourly/qty/amount)
	//2.478 - invoice prefix added to template fields
	//2.479 - 2013-04-04 - fix for manual task percent
	//2.48 - 2013-04-10 - new customer permissions
	//2.481 - 2013-04-12 - translation fix
	//2.482 - 2013-04-12 - dashboard alert fix
	//2.483 - 2013-04-12 - start of new dashboard alerts layout


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public static function get_task_types() {
		return array(
			_TASK_TYPE_HOURS_AMOUNT => _l( 'Hourly Rate & Amount' ),
			_TASK_TYPE_QTY_AMOUNT   => _l( 'Quantity & Amount' ),
			_TASK_TYPE_AMOUNT_ONLY  => _l( 'Amount Only' ),
		);
	}

	public function init() {
		$this->links           = array();
		$this->job_types       = array();
		$this->module_name     = "job";
		$this->module_position = 17;

		module_config::register_css( 'job', 'tasks.css' );
		module_config::register_js( 'job', 'tasks.js' );
		hook_add( 'calendar_events', 'module_job::hook_calendar_events' );
		hook_add( 'job_list', 'module_job::hook_filter_var_job_list' );
		hook_add( 'custom_data_menu_locations', 'module_job::hook_filter_custom_data_menu_locations' );
		hook_add( 'header_buttons', 'module_job::hook_filter_var_header_buttons' );
		hook_add( 'customer_archived', 'module_job::customer_archived' );
		hook_add( 'customer_unarchived', 'module_job::customer_unarchived' );

		if ( class_exists( 'module_template', false ) ) {
			module_template::init_template( 'job_task_completion_email', '<h2>Job Task Completed</h2>
We thought you would like to know that your job <strong>{JOB_NAME}</strong> task <strong>{TASK_DESCRIPTION}</strong> has been completed.
', 'Job Task Completed: {JOB_NAME}.', 'code' );
			module_template::init_template( 'job_staff_email', 'Dear {STAFF_NAME},<br>
<br>
Please find below your {TASK_COUNT} assigned tasks for job: {JOB_NAME}.<br><br>
You can view this job by <a href="{JOB_URL}">clicking here</a>.<br><br>
{JOB_TASKS}<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Assigned Job Tasks: {JOB_NAME}', array(
				'STAFF_NAME' => 'Customers Name',
				'JOB_NAME'   => 'Job Name',
				'TASK_COUNT' => 'Number of assigned tasks',
				'JOB_URL'    => 'Link to job for customer',
				'JOB_TASKS'  => 'Output of job tasks for this staff member',
			) );

			module_template::init_template( 'job_task_approval', 'Dear {STAFF_NAME},<br>
<br>
The task has been <strong>{APPROVED_OR_REJECTED}</strong> for the job: {JOB_NAME}.<br><br>
The job can be viewed by <a href="{JOB_URL}">clicking here</a>.<br><br>
Job Task: {JOB_TASK}<br><br>
Message: {MESSAGE}<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Job Task {APPROVED_OR_REJECTED}: {JOB_NAME}', array(
				'APPROVED_OR_REJECTED' => 'Will be Approved or Rejected',
				'JOB_NAME'             => 'Job Name',
				'JOB_URL'              => 'Link to job for customer',
				'JOB_TASK'             => 'Output of job task for this staff member',
				'MESSAGE'              => 'Approved or Rejected message',
			) );
		}
	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Jobs' ) ) {


			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many jobs?
				$name = _l( 'Jobs' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$jobs = $this->get_jobs( array( 'customer_id' => $_REQUEST['customer_id'] ) );
					if ( count( $jobs ) ) {
						$name .= " <span class='menu_label'>" . count( $jobs ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "job_admin",
					'args'                => array( 'job_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'check',
				);
			}
			$this->links[] = array(
				"name"      => "Jobs",
				"p"         => "job_admin",
				'args'      => array( 'job_id' => false ),
				'icon_name' => 'check',
			);
		}

	}


	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			$results = $this->get_jobs( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Job: ' );
					$match_string    .= _shl( $result['name'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['job_id'] ) . '">' . $match_string . '</a>';
				}
			}
		}

		return $ajax_results;
	}


	public function handle_hook( $hook, &$calling_module = false, $show_all = false ) {
		switch ( $hook ) {
			case 'dashboard_widgets':
				// see finance for example of widget usage.
				break;
			case "home_alerts":

				$cache_timeout = module_config::c( 'cache_objects', 60 );
				$cache_key     = 'home_alerts_' . module_security::get_loggedin_id();

				$alerts = array();
				/*if(module_config::c('job_task_alerts',1)){
                    // find out any overdue tasks or jobs.
                    $sql = "SELECT t.*,p.name AS job_name FROM `"._DB_PREFIX."task` t ";
                    $sql .= " LEFT JOIN `"._DB_PREFIX."job` p USING (job_id) ";
                    $sql .= " WHERE t.date_due != '0000-00-00' AND t.date_due <= '".date('Y-m-d',strtotime('+'.module_config::c('alert_days_in_future',5).' days'))."' AND ((t.hours = 0 AND t.completed = 0) OR t.completed < t.hours)";
                    $tasks = qa($sql);
                    foreach($tasks as $task){
                        $alert_res = process_alert($task['date_due'], _l('Job: %s',$task['job_name']));
                        if($alert_res){
                            $alert_res['link'] = $this->link_open($task['job_id']);
                            $alert_res['name'] = $task['description'];
                            $alerts[] = $alert_res;
                        }
                    }
                }*/
				if ( $show_all || module_config::c( 'job_alerts', 1 ) ) {
					// find any jobs that are past the due date and dont have a finished date.

					$key = _l( 'Incomplete Job' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							'progress'       => _l( 'Job Progress' ),
							'assigned_staff' => _l( 'Staff' ),
							'date'           => _l( 'Due Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns,
							'sort'    => array(
								'time' => 'DESC',
							)
						) );
					}
					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {
						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						//                        $sql = "SELECT * FROM `"._DB_PREFIX."job` p ";
						//                        $sql .= " WHERE p.date_due != '0000-00-00' AND p.date_due <= '".date('Y-m-d',strtotime('+'.module_config::c('alert_days_in_future',5).' days'))."' AND p.date_completed = '0000-00-00'";
						//                        $tasks = qa($sql);
						$jobs = self::get_jobs( array(), array(
							'custom_where' => " AND u.date_due != '0000-00-00' AND u.date_due <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "' AND u.date_completed = '0000-00-00'"
						) );
						foreach ( $jobs as $job_data ) {
							// permission check:
							//                            $job_data = self::get_job($task['job_id']);
							//                            if(!$job_data || $job_data['job_id']!=$task['job_id'])continue;
							$alert_res = process_alert( $job_data['date_due'], 'temp' );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $job_data['job_id'], false, $job_data );
								$alert_res['name'] = $job_data['name'];

								// new dashboard alert layout here:
								$alert_res['time']           = strtotime( $alert_res['date'] );
								$alert_res['group']          = $key;
								$alert_res['job']            = $this->link_open( $job_data['job_id'], true, $job_data );
								$alert_res['customer']       = $job_data['customer_id'] ? module_customer::link_open( $job_data['customer_id'], true ) : _l( 'N/A' );
								$alert_res['website']        = $job_data['website_id'] ? module_website::link_open( $job_data['website_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $job_data['user_id'] ? module_user::link_open( $job_data['user_id'], true ) : _l( 'N/A' );
								$alert_res['progress']       = ( $job_data['total_percent_complete'] * 100 ) . '%';
								$alert_res['date']           = print_date( $alert_res['date'] );
								$alert_res['days']           = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[ 'jobincomplete' . $job_data['job_id'] ] = $alert_res;
							}
						}
						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key . ' #2',
						) );
						// find any jobs that haven't started yet (ie: have a start date, but no completed tasks)
						//                        $sql = "SELECT * FROM `"._DB_PREFIX."job` p ";
						//                        $sql .= " WHERE p.date_completed = '0000-00-00' AND p.date_start != '0000-00-00' AND p.date_start <= '".date('Y-m-d',strtotime('+'.module_config::c('alert_days_in_future',5).' days'))."'";
						//                        $jobs = qa($sql);
						$jobs = self::get_jobs( array(), array(
							'custom_where' => " AND u.date_completed = '0000-00-00' AND u.date_due = '0000-00-00' AND u.date_start != '0000-00-00' AND u.date_start <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "'"
						) );
						foreach ( $jobs as $job_data ) {
							//$job_data = self::get_job($job['job_id']);
							//if(!$job_data || $job_data['job_id']!=$job['job_id'])continue;
							/*$job_started=true;
                            if(module_config::c('job_start_alerts_old',0)){
                                $tasks = self::get_tasks($job['job_id']);
                                $job_started = false;
                                foreach($tasks as $task){
                                    if($task['fully_completed']){
                                        $job_started = true;
                                        break;
                                    }
                                }
                            }
                            if(!$job_started){

                                $alert_res = process_alert($job['date_start'], _l('Job Not Started'));
                                if($alert_res){
                                    $alert_res['link'] = $this->link_open($job['job_id'],false,$job);
                                    $alert_res['name'] = $job['name'];
                                    $alerts[] = $alert_res;
                                }
                            }else{*/
							// do the same alert as above.
							if ( ! isset( $this_alerts[ 'jobincomplete' . $job_data['job_id'] ] ) ) {
								$alert_res = process_alert( $job_data['date_start'], $key );
								if ( $alert_res ) {
									$alert_res['link'] = $this->link_open( $job_data['job_id'], false, $job_data );
									$alert_res['name'] = $job_data['name'];

									// new dashboard alert layout here:
									$alert_res['time']           = strtotime( $alert_res['date'] );
									$alert_res['group']          = $key;
									$alert_res['job']            = $this->link_open( $job_data['job_id'], true, $job_data );
									$alert_res['customer']       = $job_data['customer_id'] ? module_customer::link_open( $job_data['customer_id'], true ) : _l( 'N/A' );
									$alert_res['website']        = $job_data['website_id'] ? module_website::link_open( $job_data['website_id'], true ) : _l( 'N/A' );
									$alert_res['assigned_staff'] = $job_data['user_id'] ? module_user::link_open( $job_data['user_id'], true ) : _l( 'N/A' );
									$alert_res['progress']       = ( $job_data['total_percent_complete'] * 100 ) . '%';
									$alert_res['date']           = print_date( $alert_res['date'] );
									$alert_res['days']           = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

									$this_alerts[ 'jobincomplete' . $job_data['job_id'] ] = $alert_res;
								}
							}
							/* }*/
						}
						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( module_config::c( 'job_allow_quotes', 0 ) && ( $show_all || module_config::c( 'job_quote_alerts', 1 ) ) ) {
					// find any jobs that dont have a start date yet.

					$key = _l( 'Pending Job Quote' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							//'progress'=>_l('Job Progress'),
							'assigned_staff' => _l( 'Staff' ),
							'date'           => _l( 'Quoted Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns
						) );
					}
					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {
						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						//$sql = "SELECT * FROM `"._DB_PREFIX."job` p ";
						//$sql .= " WHERE p.date_quote != '0000-00-00' AND p.date_start = '0000-00-00'";
						//$tasks = qa($sql);
						$jobs = self::get_jobs( array( 'date_start' => '0000-00-00', 'date_quote' => '!0000-00-00' ) );
						foreach ( $jobs as $job_data ) {
							//$job_data = self::get_job($task['job_id']);
							//if(!$job_data || $job_data['job_id']!=$task['job_id'])continue;
							$alert_res = process_alert( $job_data['date_quote'], $key );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $job_data['job_id'], false, $job_data );
								$alert_res['name'] = $job_data['name'];

								// new dashboard alert layout here:
								$alert_res['time']           = strtotime( $job_data['date_quote'] );
								$alert_res['group']          = $key;
								$alert_res['job']            = $this->link_open( $job_data['job_id'], true, $job_data );
								$alert_res['customer']       = $job_data['customer_id'] ? module_customer::link_open( $job_data['customer_id'], true ) : _l( 'N/A' );
								$alert_res['website']        = $job_data['website_id'] ? module_website::link_open( $job_data['website_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $job_data['user_id'] ? module_user::link_open( $job_data['user_id'], true ) : _l( 'N/A' );
								//$alert_res['progress'] = ($job_data['total_percent_complete'] * 100).'%';
								$alert_res['date'] = print_date( $alert_res['date'] );
								$alert_res['days'] = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}

						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( $show_all || module_config::c( 'job_invoice_alerts', 1 ) ) {
					// find any completed jobs that don't have an invoice.

					$key = _l( 'Please Generate Invoice' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'               => _l( 'Job Title' ),
							'customer'          => _l( 'Customer' ),
							'website'           => module_config::c( 'project_name_single', 'Website' ),
							'assigned_staff'    => _l( 'Staff' ),
							'invoicable_amount' => _l( 'Invoiceable Amount' ),
							'date'              => _l( 'Completed Date' ),
							'days'              => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns
						) );
					}
					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {

						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();

						$sql   = "SELECT j.* FROM `" . _DB_PREFIX . "job` j ";
						$from  = " LEFT JOIN `" . _DB_PREFIX . "task` t USING (job_id) ";
						$from  .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` ii ON t.task_id = ii.task_id ";
						$from  .= " LEFT JOIN `" . _DB_PREFIX . "invoice` i ON ii.invoice_id = i.invoice_id  ";
						$where = " WHERE i.invoice_id IS NULL AND (j.date_completed != '0000-00-00')";

						switch ( self::get_job_access_permissions() ) {
							case _JOB_ACCESS_ALL:
								break;
							case _JOB_ACCESS_ASSIGNED:
								// only assigned jobs!
								//$from .= " LEFT JOIN `"._DB_PREFIX."task` t ON u.job_id = t.job_id ";
								$where .= " AND (j.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
								break;
							case _JOB_ACCESS_CUSTOMER:
								// tie in with customer permissions to only get jobs from customers we can access.
								$customers = module_customer::get_customers();
								if ( count( $customers ) ) {
									$where .= " AND j.customer_id IN ( ";
									foreach ( $customers as $customer ) {
										$where .= $customer['customer_id'] . ', ';
									}
									$where = rtrim( $where, ', ' );
									$where .= " ) ";
								}
								break;
						}
						// tie in with customer permissions to only get jobs from customers we can access.
						switch ( module_customer::get_customer_data_access() ) {
							case _CUSTOMER_ACCESS_ALL:
								// all customers! so this means all jobs!
								break;
							case _CUSTOMER_ACCESS_ALL_COMPANY:
							case _CUSTOMER_ACCESS_CONTACTS:
							case _CUSTOMER_ACCESS_TASKS:
							case _CUSTOMER_ACCESS_STAFF:
								$valid_customer_ids = module_security::get_customer_restrictions();
								if ( count( $valid_customer_ids ) ) {
									$where .= " AND ( j.customer_id = 0 OR j.customer_id IN ( ";
									foreach ( $valid_customer_ids as $valid_customer_id ) {
										$where .= (int) $valid_customer_id . ", ";
									}
									$where = rtrim( $where, ', ' );
									$where .= " )";
									$where .= " )";
								}

						}
						$res = qa( $sql . $from . $where . " GROUP BY j.job_id" );

						foreach ( $res as $job ) {
							if ( ! isset( $job['c_total_amount_invoicable'] ) || $job['c_total_amount_invoicable'] < 0 ) {
								$this->update_job_completion_status( $job['job_id'] );// seed the cache
								$job = $this->get_job( $job['job_id'] );
							}
							//$job = $this->get_job($r['job_id']);
							//if($job && $job['job_id'] == $r['job_id'] && $job['total_amount_invoicable'] > 0 && module_invoice::can_i('create','Invoices')){
							if ( isset( $job['c_total_amount_invoicable'] ) && $job['c_total_amount_invoicable'] > 0 && module_invoice::can_i( 'create', 'Invoices' ) ) {
								$alert_res = process_alert( $job['date_completed'], $key );
								if ( $alert_res ) {
									$alert_res['link'] = $this->link_open( $job['job_id'], false, $job );
									$alert_res['name'] = $job['name'];

									// new dashboard alert layout here:
									$alert_res['time']              = strtotime( $job['date_completed'] );
									$alert_res['group']             = $key;
									$alert_res['job']               = $this->link_open( $job['job_id'], true, $job );
									$alert_res['customer']          = $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : _l( 'N/A' );
									$alert_res['website']           = $job['website_id'] ? module_website::link_open( $job['website_id'], true ) : _l( 'N/A' );
									$alert_res['assigned_staff']    = $job['user_id'] ? module_user::link_open( $job['user_id'], true ) : _l( 'N/A' );
									$alert_res['invoicable_amount'] = currency( $job['c_total_amount_invoicable'], true, $job['currency_id'] );
									//$alert_res['progress'] = ($job['total_percent_complete'] * 100).'%';
									$alert_res['date'] = print_date( $alert_res['date'] );
									$alert_res['days'] = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];
									$this_alerts[]     = $alert_res;
								}
							}
						}
						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( $show_all || module_config::c( 'job_renew_alerts', 1 ) ) {
					// find any jobs that have a renew date soon and have not been renewed.


					$key      = _l( 'Job Renewal Pending' );
					$key_auto = _l( 'Automatic Job Renewal Pending' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							'assigned_staff' => _l( 'Staff' ),
							'renewal_period' => _l( 'Period' ),
							'date_create'    => _l( 'Created Date' ),
							'date'           => _l( 'Renewal Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns
						) );
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							'assigned_staff' => _l( 'Staff' ),
							'renewal_period' => _l( 'Period' ),
							'date_create'    => _l( 'Created Date' ),
							'date'           => _l( 'Renewal Date' ),
							'renew_invoice'  => _l( 'Automatic Invoice' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key_auto, array(
							'columns' => $columns
						) );
					}


					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {

						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						/*$sql = "SELECT p.* FROM `"._DB_PREFIX."job` p ";
                        $sql .= " WHERE p.date_renew != '0000-00-00'";
                        $sql .= " AND p.date_renew <= '".date('Y-m-d',strtotime('+'.module_config::c('alert_days_in_future',5).' days'))."'";
                        $sql .= " AND (p.renew_job_id IS NULL OR p.renew_job_id = 0)";
                        $res = qa($sql);*/

						$res = self::get_jobs( array(), array(
							'custom_where' => " AND  u.date_renew != '0000-00-00' AND u.date_renew <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "' AND (u.renew_job_id IS NULL OR u.renew_job_id = 0)"
						) );

						foreach ( $res as $job ) {
							//$job = self::get_job($r['job_id']);
							//if(!$job || $job['job_id']!=$r['job_id'])continue;
							if ( $job['renew_auto'] ) {
								$alert_res = process_alert( $job['date_renew'], $key_auto );
							} else {
								$alert_res = process_alert( $job['date_renew'], $key );
							}
							if ( $alert_res ) {
								$alert_res['link']           = $this->link_open( $job['job_id'], false, $job );
								$alert_res['name']           = $job['name'];
								$alert_res['renewal_period'] = _l( 'N/A' );
								// work out renewal period
								if ( $job['date_start'] && $job['date_start'] != '0000-00-00' ) {
									$time_diff = strtotime( $job['date_renew'] ) - strtotime( $job['date_start'] );
									if ( $time_diff > 0 ) {
										$diff_type = 'day';
										$days      = round( $time_diff / 86400 );
										if ( $days >= 365 ) {
											$time_diff = round( $days / 365, 1 );
											$diff_type = 'year';
										} else {
											$time_diff = $days;
										}
										$alert_res['renewal_period'] = $time_diff . ' ' . $diff_type;
									}
								}

								// new dashboard alert layout here:
								$alert_res['time'] = strtotime( $job['date_renew'] );
								if ( $job['renew_auto'] ) {
									$alert_res['group']         = $key_auto;
									$alert_res['renew_invoice'] = $job['renew_invoice'] ? _l( 'Yes' ) : _l( 'No' );
								} else {
									$alert_res['group'] = $key;
								}
								$alert_res['job']            = $this->link_open( $job['job_id'], true, $job );
								$alert_res['customer']       = $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : _l( 'N/A' );
								$alert_res['website']        = $job['website_id'] ? module_website::link_open( $job['website_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $job['user_id'] ? module_user::link_open( $job['user_id'], true ) : _l( 'N/A' );
								//$alert_res['progress'] = ($job['total_percent_complete'] * 100).'%';
								$alert_res['date_create'] = print_date( $job['date_start'] );
								$alert_res['date']        = print_date( $job['date_renew'] );
								$alert_res['days']        = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}
						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( $show_all || module_config::c( 'job_approval_alerts', 1 ) ) {
					$job_task_creation_permissions = self::get_job_task_creation_permissions();
					if ( $job_task_creation_permissions == _JOB_TASK_CREATION_WITHOUT_APPROVAL ) {

						// find any jobs that have tasks requiring approval


						$key = _l( 'Tasks Require Approval' );
						if ( class_exists( 'module_dashboard', false ) ) {
							$columns = array(
								'job'            => _l( 'Job Title' ),
								'customer'       => _l( 'Customer' ),
								'website'        => module_config::c( 'project_name_single', 'Website' ),
								'assigned_staff' => _l( 'Staff' ),
								'task_count'     => _l( 'Tasks to Approve' ),
								'date'           => _l( 'Task Date' ),
								'days'           => _l( 'Day Count' ),
							);
							if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
								unset( $columns['website'] );
							}
							if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
								unset( $columns['customer'] );
							}
							module_dashboard::register_group( $key, array(
								'columns' => $columns
							) );
						}


						if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
							$alerts = array_merge( $alerts, $cached_alerts );
						} else {

							module_debug::log( array(
								'title' => 'Job Home Alerts: ',
								'data'  => " starting: " . $key,
							) );
							$this_alerts = array();
							$sql         = "SELECT p.job_id,p.name, t.date_updated, t.date_created, COUNT(t.task_id) AS approval_count FROM `" . _DB_PREFIX . "job` p ";
							$sql         .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON p.job_id = t.job_id";
							$sql         .= " WHERE t.approval_required = 1";
							$sql         .= " GROUP BY p.job_id ";
							$res         = qa( $sql );

							foreach ( $res as $r ) {
								$job = self::get_job( $r['job_id'] );
								if ( ! $job || $job['job_id'] != $r['job_id'] ) {
									continue;
								}
								$alert_res = process_alert( $r['date_updated'] && $r['date_updated'] != '0000-00-00' ? $r['date_updated'] : $r['date_created'], $key );
								if ( $alert_res ) {
									$alert_res['link'] = $this->link_open( $r['job_id'], false, $r );
									$alert_res['name'] = $r['name'];


									// new dashboard alert layout here:
									$alert_res['time']           = strtotime( $r['date_updated'] && $r['date_updated'] != '0000-00-00' ? $r['date_updated'] : $r['date_created'] );
									$alert_res['group']          = $key;
									$alert_res['job']            = $this->link_open( $job['job_id'], true, $job );
									$alert_res['customer']       = $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : _l( 'N/A' );
									$alert_res['website']        = $job['website_id'] ? module_website::link_open( $job['website_id'], true ) : _l( 'N/A' );
									$alert_res['assigned_staff'] = $job['user_id'] ? module_user::link_open( $job['user_id'], true ) : _l( 'N/A' );
									//$alert_res['progress'] = ($job['total_percent_complete'] * 100).'%';
									$alert_res['task_count'] = $r['approval_count'];
									$alert_res['date']       = print_date( $alert_res['time'] );
									$alert_res['days']       = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

									$this_alerts[] = $alert_res;
								}
							}
							module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
							$alerts = array_merge( $alerts, $this_alerts );
						}
					}
					// find any rejected tasks
					$key = _l( 'Tasks Have Been Rejected' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer' ),
							'website'        => module_config::c( 'project_name_single', 'Website' ),
							'assigned_staff' => _l( 'Staff' ),
							'task_count'     => _l( 'Tasks Rejected' ),
							'date'           => _l( 'Task Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! class_exists( 'module_website', false ) || ! module_website::is_plugin_enabled() ) {
							unset( $columns['website'] );
						}
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns
						) );
					}


					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {

						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						$sql         = "SELECT p.job_id,p.name, t.date_updated, t.date_created, COUNT(t.task_id) AS approval_count FROM `" . _DB_PREFIX . "job` p ";
						$sql         .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON p.job_id = t.job_id";
						$sql         .= " WHERE t.approval_required = 2";
						$sql         .= " GROUP BY p.job_id ";
						$res         = qa( $sql );

						foreach ( $res as $r ) {
							$job = self::get_job( $r['job_id'] );
							if ( ! $job || $job['job_id'] != $r['job_id'] ) {
								continue;
							}
							$alert_res = process_alert( $r['date_updated'] && $r['date_updated'] != '0000-00-00' ? $r['date_updated'] : $r['date_created'], $key );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $r['job_id'], false, $r );
								$alert_res['name'] = $r['name'];


								// new dashboard alert layout here:
								$alert_res['time']           = strtotime( $r['date_updated'] && $r['date_updated'] != '0000-00-00' ? $r['date_updated'] : $r['date_created'] );
								$alert_res['group']          = $key;
								$alert_res['job']            = $this->link_open( $job['job_id'], true, $job );
								$alert_res['customer']       = $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : _l( 'N/A' );
								$alert_res['website']        = $job['website_id'] ? module_website::link_open( $job['website_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $job['user_id'] ? module_user::link_open( $job['user_id'], true ) : _l( 'N/A' );
								//$alert_res['progress'] = ($job['total_percent_complete'] * 100).'%';
								$alert_res['task_count'] = $r['approval_count'];
								$alert_res['date']       = print_date( $alert_res['time'] );
								$alert_res['days']       = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}
						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Todo List' ) ) {

					$key = _l( 'Job Todo' );
					if ( class_exists( 'module_dashboard', false ) ) {
						$columns = array(
							'job'            => _l( 'Job Title' ),
							'customer'       => _l( 'Customer Name' ),
							'progress'       => _l( 'Task Progress' ),
							'task'           => _l( 'Task Description' ),
							'assigned_staff' => _l( 'Staff' ),
							'date'           => _l( 'Due Date' ),
							'days'           => _l( 'Day Count' ),
						);
						if ( ! module_customer::can_i( 'view', 'Customers' ) ) {
							unset( $columns['customer'] );
						}
						module_dashboard::register_group( $key, array(
							'columns' => $columns
						) );
					}

					if ( $cached_alerts = module_cache::get( 'job', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {

						module_debug::log( array(
							'title' => 'Job Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						$todo_list   = self::get_tasks_todo();
						$x           = 0;
						foreach ( $todo_list as $todo_item ) {
							if ( $todo_item['hours_completed'] > 0 ) {
								if ( $todo_item['hours'] > 0 ) {
									$percentage = round( $todo_item['hours_completed'] / $todo_item['hours'], 2 );
									$percentage = min( 1, $percentage );
								} else {
									$percentage = 1;
								}
							} else {
								$percentage = 0;
							}
							$job_data  = module_job::get_job( $todo_item['job_id'], false );
							$alert_res = process_alert( $todo_item['date_due'], 'temp' );
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $todo_item['job_id'], false, $job_data );
								$alert_res['name'] = ( $percentage * 100 ) . '% ' . $todo_item['description'];
								$alert_res['item'] = $job_data['name'];
								// new dashboard alert layout here:
								$alert_res['time']           = strtotime( $alert_res['date'] );
								$alert_res['group']          = $key;
								$alert_res['job']            = $this->link_open( $todo_item['job_id'], true, $job_data );
								$alert_res['customer']       = $job_data['customer_id'] ? module_customer::link_open( $job_data['customer_id'], true ) : _l( 'N/A' );
								$alert_res['assigned_staff'] = $todo_item['user_id'] ? module_user::link_open( $todo_item['user_id'], true ) : _l( 'N/A' );
								$alert_res['progress']       = ( $percentage * 100 ) . '%';
								$alert_res['task']           = htmlspecialchars( $todo_item['description'] );
								$alert_res['date']           = ( $alert_res['warning'] ) ? '<span class="important">' . print_date( $alert_res['date'] ) . '</span>' : print_date( $alert_res['date'] );
								$alert_res['days']           = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}
						module_cache::put( 'job', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}

				return $alerts;
				break;
		}

		return false;
	}

	public static function hook_calendar_events( $callback, $start_time, $end_time ) {
		// find all jobs with a date between these two times.
		$calendar_events = array();
		if ( module_config::c( 'calendar_show_jobs', 1 ) ) {
			$jobs = self::get_jobs( array(
				'date_start_after'  => print_date( $start_time ),
				'date_start_before' => print_date( $end_time ),
			) );
			foreach ( $jobs as $job ) {
				$calendar_events[] = array(
					'subject'       => $job['name'],
					'customer_id'   => $job['customer_id'],
					'start_time'    => strtotime( $job['date_start'] . ( ! empty( $job['time_start'] ) ? ' ' . $job['time_start'] : '' ) ),
					'end_time'      => strtotime( $job['date_start'] . ( ! empty( $job['time_end'] ) ? ' ' . $job['time_end'] : '' ) ),
					'all_day'       => empty( $job['time_start'] ),
					'user_id'       => $job['user_id'],
					'description'   => 'Test Description',
					'link'          => ( $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : '' ),
					'other_details' => _l( 'Job: %s', module_job::link_open( $job['job_id'], true, $job ) ),
					'staff_ids'     => array( $job['user_id'] ), // todo - add job task staff members here.
				);
			}
		}
		if ( module_config::c( 'calendar_show_job_tasks', 1 ) ) {
			$jobs = self::get_jobs( array(
				'task_due_after'  => print_date( $start_time ),
				'task_due_before' => print_date( $end_time ),
			), array(
				'columns'         => 'u.*,ts.task_id AS id, u.name AS job_name, c.customer_name, us.name AS staff_member, ts.*',
				'custom_group_by' => '',
			) );
			foreach ( $jobs as $job ) {
				$job_tasks = self::get_tasks( $job['job_id'] );
				if ( isset( $job_tasks[ $job['task_id'] ] ) && ! $job['fully_completed'] ) {
					$calendar_events[] = array(
						'subject'       => _l( 'Job Task: %s', $job['description'] ),
						'customer_id'   => $job['customer_id'],
						'start_time'    => strtotime( $job['date_due'] ),
						'end_time'      => strtotime( $job['date_due'] ),
						'all_day'       => 1, //empty($job['time_start']),
						'user_id'       => $job['user_id'],
						'description'   => 'Test Task Description',
						'link'          => ( $job['customer_id'] ? module_customer::link_open( $job['customer_id'], true ) : '' ),
						'other_details' => _l( 'Job Task: %s', module_job::link_open( $job['job_id'], true, $job ) ),
						'staff_ids'     => array( $job['user_id'] ), // todo - add job task staff members here.
					);
				}
			}
		}

		return $calendar_events;
	}

	public static function link_generate( $job_id = false, $options = array(), $link_options = array() ) {

		// link generation can be cached and save a few db calls.
		$cache_options = $options;
		if ( isset( $cache_options['data'] ) ) {
			unset( $cache_options['data'] );
			$cache_options['data_name'] = isset( $options['data'] ) && isset( $options['data']['name'] ) ? $options['data']['name'] : '';
		}
		$cache_options['customer_id']  = isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : false;
		$cache_options['current_user'] = module_security::get_loggedin_id();
		$link_cache_key                = 'job_link_' . $job_id . '_' . md5( serialize( $cache_options ) );
		if ( $cached_link = module_cache::get( 'job', $link_cache_key ) ) {
			return $cached_link;
		}
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );

		$key = 'job_id';
		if ( $job_id === false && $link_options ) {
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
			$options['type'] = 'job';
		}
		$options['page'] = 'job_admin';
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['module'] = 'job';

		$data = array();
		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		}

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $job_id > 0 ) {
					$data = self::get_job( $job_id, false, true );
				} else {
					$data = array();
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? _l( 'N/A' ) : $data['name'];
			if ( ! $data || ! $job_id || isset( $data['_no_access'] ) ) {
				$link = $options['text'];
				module_cache::put( 'job', $link_cache_key, $link, $link_cache_timeout );

				return $link;
			}
		} else {
			if ( isset( $_REQUEST['customer_id'] ) && (int) $_REQUEST['customer_id'] > 0 ) {
				$data['customer_id'] = (int) $_REQUEST['customer_id'];
			}
		}
		$options['text'] = isset( $options['text'] ) ? ( $options['text'] ) : ''; // htmlspecialchars is done in link_generatE() function
		// generate the arguments for this link
		$options['arguments']['job_id'] = $job_id;

		if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
			$bubble_to_module = array(
				'module'   => 'customer',
				'argument' => 'customer_id',
			);
		}
		array_unshift( $link_options, $options );

		if ( ! module_security::has_feature_access( array(
			'name'        => 'Customers',
			'module'      => 'customer',
			'category'    => 'Customer',
			'view'        => 1,
			'description' => 'view',
		) ) ) {

			$bubble_to_module = false;
			/*
            if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : _l('N/A');
            }
            */

		}
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			$link = link_generate( $link_options );

		}
		module_cache::put( 'job', $link_cache_key, $link, $link_cache_timeout );

		return $link;
	}

	public static function link_open( $job_id, $full = false, $data = array() ) {
		return self::link_generate( $job_id, array( 'full' => $full, 'data' => $data ) );
	}

	public static function link_ajax_task( $job_id, $full = false ) {
		return self::link_generate( $job_id, array( 'full' => $full, 'arguments' => array( '_process' => 'ajax_task' ) ) );
	}

	public static function link_create_job_invoice( $job_id, $full = false ) {
		return self::link_generate( $job_id, array(
			'full'      => $full,
			'arguments' => array( '_process' => 'ajax_create_invoice' )
		) );
	}


	public static function link_public( $job_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for job ' . _UCM_SECRET . ' ' . $job_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.job/h.public/i.' . $job_id . '/hash.' . self::link_public( $job_id, true ) );
	}

	public static function job_task_has_split_hours( $job_id, $job_data = array(), $task_id, $task_data ) {
		if ( ! $job_data ) {
			$job_data = self::get_job( $job_id );
		}
		// for now if a user has split pricing set in their profile we assume they only see split prices.
		if ( isset( $task_data['user_id'] ) && (int) $task_data['user_id'] > 0 ) {
			$user_data = module_user::get_user( $task_data['user_id'], false );
			if ( $user_data && isset( $user_data['is_staff'] ) && $user_data['is_staff'] && $user_data['split_hours'] ) {
				return true;
			}
		}
		if ( isset( $job_data['user_id'] ) && (int) $job_data['user_id'] > 0 ) {
			$user_data = module_user::get_user( $job_data['user_id'], false );
			if ( $user_data && isset( $user_data['is_staff'] ) && $user_data['is_staff'] && $user_data['split_hours'] ) {
				return true;
			}
		}

		return false;
	}

	// this works out if the current user is a staff member and if they should only see their set split pricing.
	public static function job_task_only_show_split_hours( $job_id, $job_data = array(), $task_id, $task_data ) {
		if ( ! $job_data ) {
			$job_data = self::get_job( $job_id );
		}
		if ( isset( $task_data['user_id'] ) && (int) $task_data['user_id'] > 0 && $task_data['user_id'] == module_security::get_loggedin_id() && ! module_job::can_i( 'view', 'Job Split Pricing' ) ) {
			$user_data = module_user::get_user( $task_data['user_id'], false );
			if ( $user_data['is_staff'] && $user_data['split_hours'] ) {
				return true;
			}
		}
		if ( isset( $job_data['user_id'] ) && (int) $job_data['user_id'] > 0 && $job_data['user_id'] == module_security::get_loggedin_id() && ! module_job::can_i( 'view', 'Job Split Pricing' ) ) {
			$user_data = module_user::get_user( $job_data['user_id'], false );
			if ( $user_data['is_staff'] && $user_data['split_hours'] ) {
				return true;
			}
		}

		return false;
	}

	public static function get_replace_fields( $job_id, $job_data = false ) {

		if ( ! $job_data ) {
			$job_data = self::get_job( $job_id );
		}

		$data = array(
			'job_number'   => htmlspecialchars( $job_data['name'] ),
			'project_type' => _l( module_config::c( 'project_name_single', 'Website' ) ),
			'print_link'   => self::link_public( $job_id ),

			'title'    => module_config::s( 'admin_system_name' ),
			'due_date' => print_date( $job_data['date_due'] ),
		);

		//        $customer_data = $job_data['customer_id'] ? module_customer::get_replace_fields($job_data['customer_id']) : array();
		//        $website_data = $job_data['website_id'] ? module_website::get_replace_fields($job_data['website_id']) : array();
		//        $data = array_merge($data,$customer_data,$website_data,$job_data);
		$data = array_merge( $data, $job_data );

		if ( class_exists( 'module_group', false ) && module_group::is_plugin_enabled() ) {
			// get the job groups
			$wg = array();
			$g  = array();
			if ( $job_id > 0 ) {
				$job_data = module_job::get_job( $job_id );
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'job',
						'owner_id'    => $job_id,
					) ) as $group
				) {
					$g[ $group['group_id'] ] = $group['name'];
				}
				/*// get the website groups
                foreach(module_group::get_groups_search(array(
                    'owner_table' => 'website',
                    'owner_id' => $job_data['website_id'],
                )) as $group){
                    $wg[$group['group_id']] = $group['name'];
                }*/
			}
			$data['job_group'] = implode( ', ', $g );
			/*$data['website_group'] = implode(', ',$wg);*/
		}

		// addition. find all extra keys for this job and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'job' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'job', 'owner_id' => $job_id ) );
			foreach ( $extras as $e ) {
				$data[ $e['extra_key'] ] = $e['extra'];
			}
		}
		// also do this for customer fields
		/*if($job_data['customer_id']){
            $all_extra_fields = module_extra::get_defaults('customer');
            foreach($all_extra_fields as $e){
                $data[$e['key']] = _l('N/A');
            }
            $extras = module_extra::get_extras(array('owner_table'=>'customer','owner_id'=>$job_data['customer_id']));
            foreach($extras as $e){
                $data[$e['extra_key']] = $e['extra'];
            }
        }*/


		return $data;
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'public':
				$job_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash   = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $job_id && $hash ) {
					$correct_hash = $this->link_public( $job_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$job_data = $this->get_job( $job_id );

						if ( $job_data ) {
							$job_data      = self::get_replace_fields( $job_id, $job_data );
							$customer_data = $job_data['customer_id'] ? module_customer::get_replace_fields( $job_data['customer_id'] ) : array();
							$website_data  = $job_data['website_id'] ? module_website::get_replace_fields( $job_data['website_id'] ) : array();

							// old template rename change
							// todo: copy this to jobs and quotes as well.
							$sql          = "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE 'external_job%'";
							$oldtemplates = qa( $sql );
							foreach ( $oldtemplates as $oldtemplate ) {
								$new_key = str_replace( 'external_job', 'job_external', $oldtemplate['template_key'] );
								if ( $new_key ) {
									$existingnew = qa( "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE '" . module_db::escape( $new_key ) . "'" );
									if ( ! $existingnew ) {
										update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => $new_key ) );
									} else {
										update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => 'old_' . $oldtemplate['template_key'] ) );
									}
								}
							}


							module_template::init_template( 'job_external', '{HEADER}<h2>Job Overview</h2>
Job Name: <strong>{JOB_NAME}</strong> <br/>
{PROJECT_TYPE} Name: <strong>{PROJECT_NAME}</strong> <br/>

<br/>
<h3>Task List: {TASK_PERCENT_COMPLETED}</h3> <br/>
{TASK_LIST}
<br/><br/>
{JOB_INVOICES}
', 'Used when displaying the external view of a job.', 'code' );
							// correct!
							// load up the receipt template.

							$template = false;
							if ( ! empty( $job_data['job_template_external'] ) ) {
								$template = module_template::get_template_by_key( $job_data['job_template_external'] );
								if ( ! $template->template_id ) {
									$template = false;
								}
							}
							if ( ! $template ) {
								$template = module_template::get_template_by_key( 'job_external' );
							}

							// generate the html for the task output
							ob_start();
							include( module_theme::include_ucm( 'includes/plugin_job/pages/job_public.php' ) );
							$public_html           = ob_get_clean();
							$job_data['task_list'] = $public_html;
							// do we link the job name?
							$job_data['header'] = '';
							if ( module_security::is_logged_in() && $this->can_i( 'edit', 'Jobs' ) ) {
								$job_data['header'] = '<div style="text-align: center; padding: 0 0 10px 0; font-style: italic;">' . _l( 'You can send this page to your customer as a quote or progress update (this message will be hidden).' ) . '</div>';
							}
							// is this a job or a quote?
							if ( $job_data['date_quote'] != '0000-00-00' && ( $job_data['date_start'] == '0000-00-00' && $job_data['date_completed'] == '0000-00-00' ) ) {
								$job_data['job_or_quote'] = _l( 'Quote' );
							} else {
								$job_data['job_or_quote'] = _l( 'Job' );
							}

							//$job_data['job_name'] = $job_data['name'];
							$job_data['job_name'] = self::link_open( $job_id, true );
							// format some dates:
							$job_data['date_quote']             = $job_data['date_quote'] == '0000-00-00' ? _l( 'N/A' ) : print_date( $job_data['date_quote'] );
							$job_data['date_start']             = $job_data['date_start'] == '0000-00-00' ? _l( 'N/A' ) : print_date( $job_data['date_start'] );
							$job_data['date_due']               = $job_data['date_due'] == '0000-00-00' ? _l( 'N/A' ) : print_date( $job_data['date_due'] );
							$job_data['date_completed']         = $job_data['date_completed'] == '0000-00-00' ? _l( 'N/A' ) : print_date( $job_data['date_completed'] );
							$job_data['TASK_PERCENT_COMPLETED'] = ( $job_data['total_percent_complete'] > 0 ? _l( '(%s%% completed)', $job_data['total_percent_complete'] * 100 ) : '' );


							$job_data['job_invoices'] = '';
							$invoices                 = module_invoice::get_invoices( array( 'job_id' => $job_id ) );
							$job_data['project_type'] = _l( module_config::c( 'project_name_single', 'Website' ) );
							//$website_data = $job_data['website_id'] ? module_website::get_website($job_data['website_id']) : array();
							$job_data['project_name'] = isset( $website_data['name'] ) && strlen( $website_data['name'] ) ? $website_data['name'] : _l( 'N/A' );
							if ( count( $invoices ) ) {
								$job_data['job_invoices'] .= '<h3>' . _l( 'Job Invoices:' ) . '</h3>';
								$job_data['job_invoices'] .= '<ul>';
								foreach ( $invoices as $invoice ) {
									$job_data['job_invoices'] .= '<li>';
									$invoice                  = module_invoice::get_invoice( $invoice['invoice_id'] );
									$job_data['job_invoices'] .= module_invoice::link_open( $invoice['invoice_id'], true );
									$job_data['job_invoices'] .= "<br/>";
									$job_data['job_invoices'] .= _l( 'Total: ' ) . dollar( $invoice['total_amount'], true, $invoice['currency_id'] );
									$job_data['job_invoices'] .= "<br/>";
									$job_data['job_invoices'] .= '<span class="';
									if ( $invoice['total_amount_due'] > 0 ) {
										$job_data['job_invoices'] .= 'error_text';
									} else {
										$job_data['job_invoices'] .= 'success_text';
									}
									$job_data['job_invoices'] .= '">';
									if ( $invoice['total_amount_due'] > 0 ) {
										$job_data['job_invoices'] .= dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
										$job_data['job_invoices'] .= ' ' . _l( 'due' );
									} else {
										$job_data['job_invoices'] .= _l( 'All paid' );
									}
									$job_data['job_invoices'] .= '</span>';
									$job_data['job_invoices'] .= "<br>";
									// view receipts:
									$payments = module_invoice::get_invoice_payments( $invoice['invoice_id'] );
									if ( count( $payments ) ) {
										$job_data['job_invoices'] .= "<ul>";
										foreach ( $payments as $invoice_payment_id => $invoice_payment_data ) {
											$job_data['job_invoices'] .= "<li>";
											$job_data['job_invoices'] .= '<a href="' . module_invoice::link_receipt( $invoice_payment_data['invoice_payment_id'] ) . '" target="_blank">' . _l( 'View Receipt for payment of %s', dollar( $invoice_payment_data['amount'], true, $invoice_payment_data['currency_id'] ) ) . '</a>';
											$job_data['job_invoices'] .= "</li>";
										}
										$job_data['job_invoices'] .= "</ul>";
									}
									$job_data['job_invoices'] .= '</li>';
								}
								$job_data['job_invoices'] .= '</ul>';
							}
							$template->assign_values( $customer_data );
							$template->assign_values( $website_data );
							$template->assign_values( $job_data );
							$template->page_title = $job_data['name'];
							echo $template->render( 'pretty_html' );
						}
					}
				}
				break;
		}
	}


	public function process() {
		$errors = array();

		if ( 'save_job_task' === $_REQUEST['_process'] ) {

			$jobtask = new UCMJobTask();
			$jobtask->handle_submit();

		} else if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['job_id'] ) {
			$data = self::get_job( $_REQUEST['job_id'] );
			if ( module_form::confirm_delete( 'job_id', _l( "Really delete job: %s", $data['name'] ), self::link_open( $_REQUEST['job_id'] ) ) ) {
				$this->delete_job( $_REQUEST['job_id'] );
				set_message( "job deleted successfully" );
				redirect_browser( $this->link_open( false ) );
			}

		} else if ( "ajax_job_list" == $_REQUEST['_process'] ) {

			$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0;
			$res         = module_job::get_jobs( array( 'customer_id' => $customer_id ) );
			$options     = array();
			foreach ( $res as $row ) {
				$options[ $row['job_id'] ] = $row['name'];
			}
			echo json_encode( $options );
			exit;

		} else if ( "ajax_create_invoice" == $_REQUEST['_process'] ) {

			$job_id    = (int) $_REQUEST['job_id'];
			$job       = self::get_job( $job_id, true );
			$job_tasks = self::get_tasks( $job_id );

			if ( ! $job || $job['job_id'] != $job_id ) {
				exit;
			} // no permissions.
			if ( ! module_invoice::can_i( 'create', 'Invoices' ) ) {
				exit;
			} // no permissions

			ob_start();
			?>
			<p><?php _e( 'Please select which tasks to generate an invoice for:' ); ?></p>
			<ul>
				<?php foreach ( $job['uninvoiced_task_ids'] as $task_id ) {
					if ( isset( $job_tasks[ $task_id ] ) ) {
						?>
						<li>
							<input type="checkbox" id="invoice_create_task_<?php echo $task_id; ?>"
							       data-taskid="<?php echo $task_id; ?>" class="invoice_create_task"
							       name="invoice_task_id[<?php echo $task_id; ?>]"
							       value="1" <?php echo $job_tasks[ $task_id ]['fully_completed'] ? 'checked' : ''; ?>>
							<label for="invoice_create_task_<?php echo $task_id; ?>">
								(#<?php echo $job_tasks[ $task_id ]['task_order']; ?>)
								<?php echo htmlspecialchars( $job_tasks[ $task_id ]['description'] ); ?>
							</label>
						</li>
					<?php }
				} ?>
			</ul>
			<?php


			$html = ob_get_clean();

			echo $html;
			exit;


		} else if ( "ajax_task" == $_REQUEST['_process'] ) {

			// we are requesting editing a task.
			$job_id    = (int) $_REQUEST['job_id'];
			$job       = self::get_job( $job_id, true );
			$job_tasks = self::get_tasks( $job_id );

			if ( ! $job || $job['job_id'] != $job_id ) {
				exit;
			} // no permissions.
			if ( ! self::can_i( 'edit', 'Job Tasks' ) ) {
				exit;
			} // no permissions

			if ( isset( $_REQUEST['toggle_completed'] ) ) {

				$task_id   = (int) $_REQUEST['task_id'];
				$task_data = $job_tasks[ $task_id ];
				$result    = array();
				if ( $task_data && $task_data['job_id'] == $job_id ) {
					if ( $task_data['invoiced'] && $task_data['fully_completed'] ) {
						// dont allow to 'uncompleted' fully completed invoice tasks
					} else {
						// it is editable.
						$task_data['fully_completed_t'] = 1;
						$task_data['fully_completed']   = $task_data['fully_completed'] ? 0 : 1;
						// save a single job task
						$this->save_job_tasks( $job_id, array( 'job_task' => array( $task_id => $task_data ) ) );
						$result['success'] = 1;
						$result['job_id']  = $job_id;
						$result['task_id'] = $task_id;
						$result['message'] = $task_data['fully_completed'] ? _l( 'Task marked as complete' ) : _l( 'Task marked as incomplete' );
						$email_status      = self::send_job_task_email( $job_id, $result['task_id'], 'toggle' );
						if ( $email_status !== false ) {
							$result['message'] .= is_array( $email_status ) && isset( $email_status['message'] ) ? $email_status['message'] : _l( ' and email sent to customer' );
						}

					}
				}
				echo json_encode( $result );
				exit;

			} else if ( isset( $_REQUEST['delete_task_log_id'] ) && (int) $_REQUEST['delete_task_log_id'] > 0 ) {

				$task_id     = (int) $_REQUEST['task_id'];
				$task_log_id = (int) $_REQUEST['delete_task_log_id'];
				$sql         = "DELETE FROM `" . _DB_PREFIX . "task_log` WHERE task_id = '$task_id' AND task_log_id = '$task_log_id' LIMIT 1";
				query( $sql );
				echo 'done';


			} else if ( isset( $_REQUEST['update_task_order'] ) ) {

				// updating the task orders for this task..
				$task_order = (array) $_REQUEST['task_order'];
				foreach ( $task_order as $task_id => $new_order ) {
					if ( (int) $new_order > 0 && isset( $job_tasks[ $task_id ] ) ) {
						update_insert( 'task_id', $task_id, 'task', array(
							'task_order' => (int) $new_order,
						) );
					}
				}
				echo 'done';
			} else {

				$task_id       = (int) $_REQUEST['task_id'];
				$task_data     = $job_tasks[ $task_id ];
				$task_editable = ! ( $task_data['invoiced'] );

				$job_task_creation_permissions = module_job::get_job_task_creation_permissions();

				// todo - load this select box in via javascript from existing one on page.
				$staff_members    = module_user::get_staff_members();
				$staff_member_rel = array();
				foreach ( $staff_members as $staff_member ) {
					$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
				}

				// new different formats for job data.
				$task_data['manual_task_type_real'] = $task_data['manual_task_type'];
				if ( ( ! isset( $task_data['manual_task_type'] ) || $task_data['manual_task_type'] < 0 ) && isset( $job['default_task_type'] ) ) {
					// use the job task type
					$task_data['manual_task_type'] = $job['default_task_type'];
				}
				if ( is_callable( 'module_product::sanitise_product_name' ) ) {
					$task_data = module_product::sanitise_product_name( $task_data, $job['default_task_type'] );
				}


				$percentage = self::get_percentage( $task_data );

				if ( isset( $_REQUEST['get_preview'] ) ) {
					$after_task_id      = $task_id; // this will put it right back where it started.
					$previous_task_id   = 0;
					$job_tasks          = self::get_tasks( $job_id );
					$show_hours_summary = false;
					foreach ( $job_tasks as $k => $v ) {
						if ( $v['manual_task_type'] < 0 ) {
							$job_tasks[ $k ]['manual_task_type'] = $job['default_task_type'];
						}
						if ( $job_tasks[ $k ]['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
							$show_hours_summary = true;
						}
					}
					foreach ( $job_tasks as $k => $v ) {
						// find out where this new task position is!
						if ( $k == $task_id ) {
							$after_task_id = $previous_task_id;
							break;
						}
						$previous_task_id = $k;
					}
					$create_invoice_button = '';
					//if($job['total_amount_invoicable'] > 0 && module_invoice::can_i('create','Invoices')){
					if ( count( $job['uninvoiced_task_ids'] ) && module_invoice::can_i( 'create', 'Invoices' ) ) {
						//href="'.module_invoice::link_generate('new',array('arguments'=>array( 'job_id' => $job_id, ))).'"
						$create_invoice_button = '<a class="submit_button save_button uibutton job_generate_invoice_button" onclick="return ucm.job.generate_invoice();">' . _l( 'Create Invoice' ) . '</a>';
					}
					$result = array(
						'task_id'               => $task_id,
						'after_task_id'         => $after_task_id,
						'html'                  => self::generate_task_preview( $job_id, $job, $task_id, $task_data ),
						'summary_html'          => self::generate_job_summary( $job_id, $job, $show_hours_summary ),
						'create_invoice_button' => $create_invoice_button,
					);
					echo json_encode( $result );
				} else {
					$show_task_numbers = ( module_config::c( 'job_show_task_numbers', 1 ) && $job['auto_task_numbers'] != 2 );
					ob_start();
					include( 'pages/ajax_task_edit.php' );
					$result = array(
						'task_id' => $task_id,
						'hours'   => isset( $_REQUEST['hours'] ) ? (float) $_REQUEST['hours'] : 0,
						'html'    => ob_get_clean(),
						//'summary_html' => self::generate_job_summary($job_id,$job),
					);
					echo json_encode( $result );
				}
			}

			exit;
		} else if ( "save_job_tasks_ajax" == $_REQUEST['_process'] ) {

			// do everything via ajax. trickery!
			// dont bother saving the job. it's already created.

			$job_id   = (int) $_REQUEST['job_id'];
			$job_data = self::get_job( $job_id );
			if ( ! $job_id || ! $job_data || $job_data['job_id'] != $job_id ) {
				set_error( 'Permission denied' );
				exit;
			}
			$result   = $this->save_job_tasks( $job_id, $_POST );
			$job_data = self::get_job( $job_id, false );
			//if(!$job_data || $job_data['job_id'] != $job_id)
			$new_status = self::update_job_completion_status( $job_id );
			$new_status = addcslashes( htmlspecialchars( $new_status ), "'" );
			//module_cache::clear_cache();
			$new_job_data = self::get_job( $job_id, false );


			// we now have to edit the parent DOM to reflect these changes.
			// what were we doing? adding a new task? editing an existing task?
			switch ( $result['status'] ) {
				case 'created':
					// we added a new task.
					// add a new task to the bottom (OR MID WAY!) through the task list.
					if ( (int) $result['task_id'] > 0 ) {
						// support for job task completion email.
						$email_status = self::send_job_task_email( $job_id, $result['task_id'], 'created' );
						?>
						<script type="text/javascript">
                parent.refresh_task_preview(<?php echo (int) $result['task_id'];?>);
                parent.clear_create_form();
                parent.ucm.add_message('<?php _e( 'New task created successfully' );

									echo is_array( $email_status ) && isset( $email_status['message'] ) ? $email_status['message'] : ( $email_status ? _l( ' and email sent to customer' ) : '' );

									?>');
                parent.ucm.display_messages(true);
								<?php if($job_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();<?php } ?>
								<?php if($new_job_data['date_completed'] != $job_data['date_completed']){ ?>parent.jQuery('#date_completed').val('<?php echo print_date( $new_job_data['date_completed'] );?>').change();<?php } ?>
						</script>
					<?php } else {
						set_error( 'New task creation failed.' );
						?>
						<script type="text/javascript">
                top.location.href = '<?php echo $this->link_open( $_REQUEST['job_id'] );?>&added=true';
						</script>
						<?php
					}
					break;
				case 'deleted':
					// we deleted a task.
					set_message( 'Task removed successfully' );
					?>
					<script type="text/javascript">
              top.location.href = '<?php echo $this->link_open( $_REQUEST['job_id'] );?>';
							<?php if($job_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();<?php } ?>
					</script>
					<?php
					break;
				case 'error':
					set_error( 'Something happened while trying to save a task. Unknown error.' );
					// something happened, refresh the parent browser frame
					?>
					<script type="text/javascript">
              top.location.href = '<?php echo $this->link_open( $_REQUEST['job_id'] );?>';
					</script>
					<?php
					break;
				case 'edited':
					// we changed a task (ie: completed?);
					// update this task above.
					if ( (int) $result['task_id'] > 0 ) {
						$email_status = self::send_job_task_email( $job_id, $result['task_id'], 'edited' );
						?>
						<script type="text/javascript">
                parent.canceledittask();
                //parent.refresh_task_preview(<?php echo (int) $result['task_id'];?>);
                parent.ucm.add_message('<?php _e( 'Task saved successfully' );
									echo is_array( $email_status ) && isset( $email_status['message'] ) ? $email_status['message'] : ( $email_status ? _l( ' and email sent to customer' ) : '' );
									?>');
                parent.ucm.display_messages(true);
								<?php if($job_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();<?php } ?>
								<?php if($new_job_data['date_completed'] != $job_data['date_completed']){ ?>parent.jQuery('#date_completed').val('<?php echo print_date( $new_job_data['date_completed'] );?>').change();<?php } ?>
						</script>
						<?php
					} else {
						?>
						<script type="text/javascript">
                parent.canceledittask();
                parent.ucm.add_error('<?php _e( 'Unable to save task' );?>');
                parent.ucm.display_messages(true);
								<?php if($job_data['status'] != $new_status){ ?>parent.jQuery('#status').val('<?php echo $new_status;?>').change();<?php } ?>
						</script>
						<?php
					}
					break;
				default:
					?>
					<script type="text/javascript">
              parent.ucm.add_error('<?php _e( 'Unable to save task. Please check required fields.' );?>');
              parent.ucm.display_messages(true);
					</script>
					<?php
					break;
			}

			exit;
		} else if ( "save_job" == $_REQUEST['_process'] ) {


			$job_id = (int) $_REQUEST['job_id'];
			if ( (int) $job_id > 0 ) {
				$original_job_data = self::get_job( $job_id, false );
				if ( ! $original_job_data || $original_job_data['job_id'] != $job_id ) {
					$original_job_data = array();
					$job_id            = false;
				}
			} else {
				$original_job_data = array();
				$job_id            = false;
			}

			// check create permissions.
			if ( ! $job_id && ! self::can_i( 'create', 'Jobs' ) ) {
				// user not allowed to create jobs.
				set_error( 'Unable to create new Jobs' );
				redirect_browser( self::link_open( false ) );
			} else if ( $job_id && ! self::can_i( 'edit', 'Jobs' ) ) {
				// user not allowed to create jobs.
				set_error( 'Unable to edit Jobs' );
				redirect_browser( self::link_open( false ) );
			}

			$job_id = $this->save_job( $job_id, $_POST );

			// look for the new tasks flag.
			if ( isset( $_REQUEST['default_task_list_id'] ) && isset( $_REQUEST['default_tasks_action'] ) ) {
				switch ( $_REQUEST['default_tasks_action'] ) {
					case 'insert_default':
						if ( (int) $_REQUEST['default_task_list_id'] > 0 ) {
							$default       = self::get_default_task( $_REQUEST['default_task_list_id'] );
							$task_data     = $default['task_data'];
							$new_task_data = array( 'job_task' => array() );
							foreach ( $task_data as $task ) {
								$task['job_id'] = $job_id;
								if ( $task['date_due'] && $task['date_due'] != '0000-00-00' ) {
									$diff_time        = strtotime( $task['date_due'] ) - $task['saved_time'];
									$task['date_due'] = date( 'Y-m-d', time() + $diff_time );
								}
								$new_task_data['job_task'][] = $task;
							}
							$this->save_job_tasks( $job_id, $new_task_data );
						}
						break;
					case 'save_default':
						$new_default_name = trim( $_REQUEST['default_task_list_id'] );
						if ( $new_default_name != '' ) {
							// time to save it!
							$task_data        = self::get_tasks( $job_id );
							$cached_task_data = array();
							foreach ( $task_data as $task ) {
								unset( $task['task_id'] );
								unset( $task['date_done'] );
								unset( $task['invoice_id'] );
								unset( $task['task_order'] );
								unset( $task['create_user_id'] );
								unset( $task['update_user_id'] );
								unset( $task['date_created'] );
								unset( $task['date_updated'] );
								$task['saved_time'] = time();
								$cached_task_data[] = $task;

								/*$cached_task_data[] = array(
                                    'hours' => $task['hours'],
                                    'amount' => $task['amount'],
                                    'billable' => $task['billable'],
                                    'fully_completed' => $task['fully_completed'],
                                    'description' => $task['description'],
                                    'long_description' => $task['long_description'],
                                    'date_due' => $task['date_due'],
                                    'user_id' => $task['user_id'],
                                    'approval_required' => $task['approval_required'],
                                    'task_order' => $task['task_order'],
                                    'saved_time' => time(),
                                );*/
							}
							self::save_default_tasks( (int) $_REQUEST['default_task_list_id'], $new_default_name, $cached_task_data );
							unset( $task_data );
						}
						break;
				}
			}

			// check if we are generating any renewals
			if ( isset( $_REQUEST['generate_renewal'] ) && $_REQUEST['generate_renewal'] > 0 ) {
				$new_job_id = $this->renew_job( $job_id );
				set_message( "Job renewed successfully" );
				redirect_browser( $this->link_open( $new_job_id ) );
			}

			if ( isset( $_REQUEST['butt_create_deposit'] ) && isset( $_REQUEST['job_deposit'] ) && $_REQUEST['job_deposit'] > 0 ) {


				if ( strpos( $_REQUEST['job_deposit'], '%' ) !== false ) {
					$job_data                = module_job::get_job( $job_id );
					$percent                 = (int) str_replace( '%', '', $_REQUEST['job_deposit'] );
					$_REQUEST['job_deposit'] = number_out( $job_data['total_amount'] * ( $percent / 100 ) );
				}
				// create an invoice for this job.
				$url = module_invoice::link_generate( 'new', array(
					'arguments' => array(
						'job_id'      => $job_id,
						'as_deposit'  => 1,
						'amount_due'  => number_in( $_REQUEST['job_deposit'] ),
						'description' => str_replace( '{JOB_NAME}', $_POST['name'], module_config::c( 'job_deposit_text', 'Deposit for job: {JOB_NAME}' ) ),
					)
				) );
				redirect_browser( $url );
			}


			if ( ! empty( $_REQUEST['butt_archive'] ) ) {
				$ucmjob = new UCMJob( $job_id );
				if ( $ucmjob->is_archived() ) {
					$ucmjob->unarchive();
					set_message( "Job unarchived successfully" );
				} else {
					$ucmjob->archive();
					set_message( "Job archived successfully" );
				}
				//redirect_browser($this->link_open(false));
			} else {
				set_message( "Job saved successfully" );
			}
			redirect_browser( isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : $this->link_open( $job_id ) );


		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}

	public function renew_job( $job_id, $from_cron = false ) {
		$job = $this->get_job( $job_id, true, true );
		if ( strtotime( $job['date_renew'] ) <= strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) {
			// /we are allowed to renew.
			unset( $job['job_id'] );
			// work out the difference in start date and end date and add that new renewl date to the new order.
			$time_diff = strtotime( $job['date_renew'] ) - strtotime( $job['date_start'] );
			if ( $time_diff > 0 ) {
				// our renewal date is something in the future.
				if ( ! $job['date_start'] || $job['date_start'] == '0000-00-00' ) {
					if ( $from_cron ) {
						echo "Unable to renew job $job_id because no start date has been set ";
					} else {
						set_message( 'Please set a job start date before renewing' );
						redirect_browser( $this->link_open( $job_id ) );
					}
				}
				// work out the next renewal date.
				$new_renewal_date = date( 'Y-m-d', strtotime( $job['date_renew'] ) + $time_diff );

				$job['date_quote']     = $job['date_renew'];
				$job['date_start']     = $job['date_renew'];
				$job['date_due']       = $job['date_renew'];
				$job['date_renew']     = $new_renewal_date;
				$job['status']         = module_config::s( 'job_status_default', 'New' );
				$job['date_completed'] = '';
				// todo: copy the "more" listings over to the new job
				// todo: copy any notes across to the new listing.


				// hack to copy the 'extra' fields across to the new invoice.
				// save_invoice() does the extra handling, and if we don't do this
				// then it will move the extra fields from the original invoice to this new invoice.
				if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
					$owner_table = 'job';
					// get extra fields from this job
					$extra_fields = module_extra::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $job_id ) );
					$x            = 1;
					foreach ( $extra_fields as $extra_field ) {
						$_REQUEST[ 'extra_' . $owner_table . '_field' ][ 'new' . $x ] = array(
							'key' => $extra_field['extra_key'],
							'val' => $extra_field['extra'],
						);
						$x ++;
					}
				}

				// taxes copy across
				if ( isset( $job['taxes'] ) && is_array( $job['taxes'] ) ) {
					$job['tax_ids']      = array();
					$job['tax_names']    = array();
					$job['tax_percents'] = array();
					foreach ( $job['taxes'] as $tax ) {
						$job['tax_ids'][]      = 0;
						$job['tax_names'][]    = $tax['name'];
						$job['tax_percents'][] = $tax['percent'];
						if ( $tax['increment'] ) {
							$job['tax_increment_checkbox'] = 1;
						}
					}
				}

				$new_job_id = $this->save_job( 'new', $job );
				if ( $new_job_id ) {
					// now we create the tasks
					$tasks = $this->get_tasks( $job_id );
					foreach ( $tasks as $task ) {
						unset( $task['task_id'] );
						//$task['completed'] = 0;
						$task['job_id']          = $new_job_id;
						$task['date_due']        = $job['date_due'];
						$task['fully_completed'] = 0;
						update_insert( 'task_id', 'new', 'task', $task );
					}
					// link this up with the old one.
					update_insert( 'job_id', $job_id, 'job', array( 'renew_job_id' => $new_job_id ) );
				}
				module_cache::clear( 'job' );

				return $new_job_id;
			}
		}

		return false;
	}


	public static function get_valid_job_ids() {
		return self::get_jobs( array(), array( 'columns' => 'u.job_id' ) );
	}

	public static function get_jobs( $search = array(), $return_options = array() ) {
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
		$cache_key = 'get_jobs_' . md5( serialize( array( $search, $return_options ) ) );
		if ( $cached_item = module_cache::get( 'job', $cache_key ) ) {
			return $cached_item;
		}
		$cache_timeout = module_config::c( 'cache_objects', 60 );

		// build up a custom search sql query based on the provided search fields
		$sql = "SELECT ";
		if ( isset( $return_options['columns'] ) ) {
			$sql .= $return_options['columns'];
		} else {
			$sql .= "u.*,u.job_id AS id ";
			$sql .= ", u.name AS name ";
			$sql .= ", c.customer_name ";
			if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
				$sql .= ", w.name AS website_name";// for export
			}
			$sql .= ", us.name AS staff_member";// for export
		}
		$from = " FROM `" . _DB_PREFIX . "job` u ";
		$from .= " LEFT JOIN `" . _DB_PREFIX . "customer` c USING (customer_id)";
		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "website` w ON u.website_id = w.website_id"; // for export
		}
		$from  .= " LEFT JOIN `" . _DB_PREFIX . "user` us ON u.user_id = us.user_id"; // for export
		$where = " WHERE 1 ";
		if ( is_array( $return_options ) && isset( $return_options['custom_where'] ) ) {
			// put in return options so harder to push through from user end.
			$where .= $return_options['custom_where'];
		}
		if ( ! empty( $search['archived_status'] ) ) {
			switch ( $search['archived_status'] ) {
				case _ARCHIVED_SEARCH_NONARCHIVED:
					$where .= ' AND u.archived = 0 ';
					break;
				case _ARCHIVED_SEARCH_ARCHIVED:
					$where .= ' AND u.archived = 1 ';
					break;
				case _ARCHIVED_SEARCH_BOTH:
					//                    $where .= ' AND u.archived = 0 ';
					break;
			}
		}
		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' "; //OR ";
			//$where .= " u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_start_after'] ) && $search['date_start_after'] !== '' && $search['date_start_after'] !== false ) {
			$date  = input_date( $search['date_start_after'] );
			$where .= " AND u.`date_start` >= '" . db_escape( $date ) . "'";
		}
		if ( isset( $search['date_start_before'] ) && $search['date_start_before'] !== '' && $search['date_start_before'] !== false ) {
			$date  = input_date( $search['date_start_before'] );
			$where .= " AND u.`date_start` != '0000-00-00' AND u.`date_start` <= '" . db_escape( $date ) . "'";
		}
		if ( isset( $search['task_due_after'] ) && $search['task_due_after'] !== '' && $search['task_due_after'] !== false ) {
			$date = input_date( $search['task_due_after'] );
			if ( ! strpos( $from, 'task`' ) ) {
				$from .= " LEFT JOIN `" . _DB_PREFIX . "task` ts ON u.job_id = ts.job_id ";
			}
			$where .= " AND ts.`date_due` >= '" . db_escape( $date ) . "'";
		}
		if ( isset( $search['task_due_before'] ) && $search['task_due_before'] !== '' && $search['task_due_before'] !== false ) {
			$date = input_date( $search['task_due_before'] );
			if ( ! strpos( $from, 'task`' ) ) {
				$from .= " LEFT JOIN `" . _DB_PREFIX . "task` ts ON u.job_id = ts.job_id ";
			}
			$where .= " AND ts.`date_due` != '0000-00-00' AND ts.`date_due` <= '" . db_escape( $date ) . "'";
		}
		if ( isset( $search['user_id'] ) && $search['user_id'] !== '' && $search['user_id'] !== false && (int) $search['user_id'] > 0 ) {
			$user_id = (int) $search['user_id'];
			if ( ! strpos( $from, 'task`' ) ) {
				$from .= " LEFT JOIN `" . _DB_PREFIX . "task` ts ON u.job_id = ts.job_id ";
			}
			$where .= " AND ( u.`user_id` = $user_id OR `ts`.`user_id` = $user_id ) ";
		}
		if ( strpos( $sql, 'ts.' ) && ! strpos( $from, 'task' ) ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "task` ts ON u.job_id = ts.job_id ";
		}
		if ( isset( $search['group_id'] ) && trim( $search['group_id'] ) ) {
			$str   = (int) $search['group_id'];
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (u.job_id = gm.owner_id)";
			$where .= " AND (gm.group_id = '$str' AND gm.owner_table = 'job')";
		}
		if ( isset( $search['extra_fields'] ) && is_array( $search['extra_fields'] ) && class_exists( 'module_extra', false ) ) {
			$extra_fields = array();
			foreach ( $search['extra_fields'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$extra_fields[ $key ] = trim( $val );
				}
			}
			if ( count( $extra_fields ) ) {
				$from  .= " LEFT JOIN `" . _DB_PREFIX . "extra` ext ON (ext.owner_id = u.job_id)"; //AND ext.owner_table = 'customer'
				$where .= " AND (ext.owner_table = 'job' AND ( ";
				foreach ( $extra_fields as $key => $val ) {
					$val   = db_escape( $val );
					$key   = db_escape( $key );
					$where .= "( ext.`extra` LIKE '%$val%' AND ext.`extra_key` = '$key') OR ";
				}
				$where = rtrim( $where, ' OR' );
				$where .= ' ) )';
			}
		}
		foreach (
			array(
				'customer_id',
				'website_id',
				'renew_job_id',
				'status',
				'type',
				'date_start',
				'date_quote',
				'quote_id'
			) as $key
		) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str = db_escape( $search[ $key ] );
				if ( $str[0] == '!' ) {
					// hack for != sql searching.
					$str   = ltrim( $str, '!' );
					$where .= " AND u.`$key` != '$str'";
				} else {
					$where .= " AND u.`$key` = '$str'";
				}
			}
		}
		if ( isset( $search['completed'] ) && (int) $search['completed'] > 0 ) {
			switch ( $search['completed'] ) {
				case 1:
					// both complete and not complete jobs, dont modify query
					break;
				case 2:
					// only completed jobs.
					$where .= " AND u.date_completed != '0000-00-00'";
					break;
				case 3:
					// only non-completed jobs.
					$where .= " AND u.date_completed = '0000-00-00'";
					break;
				case 4:
					// only quoted jobs
					$where .= " AND u.date_start = '0000-00-00' AND u.date_quote != '0000-00-00'";
					break;
				case 5:
					// only not started jobs
					$where .= " AND u.date_start = '0000-00-00'";
					break;
			}
		}
		if ( isset( $return_options['custom_group_by'] ) ) {
			$group_order = $return_options['custom_group_by'];
		} else {
			$group_order = ' GROUP BY u.job_id ORDER BY u.name';
		}


		switch ( self::get_job_access_permissions() ) {
			case _JOB_ACCESS_ALL:

				break;
			case _JOB_ACCESS_ASSIGNED:
				// only assigned jobs!
				$from  .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON u.job_id = t.job_id ";
				$where .= " AND (u.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
				break;
			case _JOB_ACCESS_CUSTOMER:
				// tie in with customer permissions to only get jobs from customers we can access.
				$customers = module_customer::get_customers();
				if ( count( $customers ) ) {
					$where .= " AND u.customer_id IN ( ";
					foreach ( $customers as $customer ) {
						$where .= $customer['customer_id'] . ', ';
					}
					$where = rtrim( $where, ', ' );
					$where .= " ) ";
				}
				break;
		}

		// tie in with customer permissions to only get jobs from customers we can access.
		switch ( module_customer::get_customer_data_access() ) {
			case _CUSTOMER_ACCESS_ALL:
				// all customers! so this means all jobs!
				break;
			case _CUSTOMER_ACCESS_ALL_COMPANY:
			case _CUSTOMER_ACCESS_CONTACTS:
			case _CUSTOMER_ACCESS_TASKS:
			case _CUSTOMER_ACCESS_STAFF:
				$valid_customer_ids = module_security::get_customer_restrictions();
				if ( count( $valid_customer_ids ) ) {
					$where .= " AND ( u.customer_id = 0 OR u.customer_id IN ( ";
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
					$where .= " )";
					$where .= " )";
				}

		}

		$sql = $sql . $from . $where . $group_order;
		//        echo $sql;print_r(debug_backtrace());exit;
		$result = qa( $sql );
		//module_security::filter_data_set("job",$result);
		module_cache::put( 'job', $cache_key, $result, $cache_timeout );

		return $result;
		//		return get_multiple("job",$search,"job_id","fuzzy","name");

	}

	public static function get_task( $job_id, $task_id ) {
		return get_single( 'task', array( 'job_id', 'task_id' ), array( $job_id, $task_id ) );
	}

	private static $job_tasks_cache = array();

	public static function get_tasks( $job_id, $order_by = 'task' ) {
		if ( (int) $job_id <= 0 ) {
			// are we creating a job from a quote?
			if ( isset( $_REQUEST['from_quote_id'] ) && (int) $_REQUEST['from_quote_id'] > 0 ) {
				$quote_id    = (int) $_REQUEST['from_quote_id'];
				$quote_data  = module_quote::get_quote( $quote_id );
				$quote_tasks = module_quote::get_quote_items( $quote_id, $quote_data );
				foreach ( $quote_tasks as $quote_task_id => $quote_task ) {
					$quote_tasks[ $quote_task_id ]['invoiced']          = 0;
					$quote_tasks[ $quote_task_id ]['task_id']           = 0;
					$quote_tasks[ $quote_task_id ]['job_id']            = 0;
					$quote_tasks[ $quote_task_id ]['completed']         = 0;
					$quote_tasks[ $quote_task_id ]['date_due']          = false;
					$quote_tasks[ $quote_task_id ]['approval_required'] = false;
				}

				return $quote_tasks;
			}

			return array();
		}
		if ( isset( self::$job_tasks_cache[ $job_id ] ) ) {
			return self::$job_tasks_cache[ $job_id ];
		}
		$sql = "SELECT t.*, t.task_id AS id, i.invoice_item_id AS invoiced, i.invoice_id AS invoice_id ";
		$sql .= ", SUM(tl.hours) AS `completed` ";
		$sql .= ", inv.name AS invoice_number";
		$sql .= ", u.name AS user_name";
		$sql .= ", j.name AS job_name";
		$sql .= ", j.default_task_type";
		$sql .= ", j.user_id AS job_user_id";
		$sql .= " FROM `" . _DB_PREFIX . "task` t ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "task_log` tl ON t.task_id = tl.task_id";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` i ON t.task_id = i.task_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice` inv ON i.invoice_id = inv.invoice_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "user` u ON t.user_id = u.user_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id";
		$sql .= " WHERE t.`job_id` = " . (int) $job_id;
		// permissions
		$job_task_permissions = self::get_job_task_access_permissions();
		switch ( $job_task_permissions ) {
			case _JOB_TASK_ACCESS_ASSIGNED_ONLY:
				$sql .= " AND t.`user_id` = " . (int) module_security::get_loggedin_id();
				break;
		}
		$sql .= " GROUP BY t.task_id ";
		switch ( $order_by ) {
			case 'task':
				$sql .= " ORDER BY t.task_order, t.date_due ASC ";
				break;
			case 'date':
				$sql .= " ORDER BY t.date_due ASC ";
				break;
		}
		$res = qa( $sql, false );
		// permission hack to
		foreach ( $res as $rid => $r ) {
			$job_data = array( 'user_id' => $r['job_user_id'] );
			if ( $r['staff_split'] && module_job::job_task_only_show_split_hours( $job_id, $job_data, $r['task_id'], $r ) ) {
				//                $res[$rid]['hours'] = $r['staff_hours'];
				//                $res[$rid]['amount'] = $r['staff_amount'];
				// others here
			}
			if ( is_callable( 'module_product::sanitise_product_name' ) ) {
				$res[ $rid ] = module_product::sanitise_product_name( $r, $r['default_task_type'] );
			}
		}

		self::$job_tasks_cache[ $job_id ] = $res;

		return self::$job_tasks_cache[ $job_id ];
		//return get_multiple("task",array('job_id'=>$job_id),"task_id","exact","task_id");

	}

	public static function get_invoicable_tasks( $job_id ) {

		$job = self::get_job( $job_id, false );

		$manually_selected_task_ids = isset( $_REQUEST['task_id'] ) && is_array( $_REQUEST['task_id'] ) ? $_REQUEST['task_id'] : array();

		$sql = "SELECT t.*, t.task_id AS id ";
		$sql .= " ,SUM(tl.hours) AS `completed` ";
		$sql .= " FROM `" . _DB_PREFIX . "task` t ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "task_log` tl ON t.task_id = tl.task_id";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` i ON t.task_id = i.task_id";
		$sql .= " WHERE t.`job_id` = " . (int) $job_id;
		$sql .= " AND i.invoice_id IS NULL ";
		//$sql .= " AND `completed` > 0 ";
		//$sql .= " AND t.`billable` != 0 ";
		//$sql .= " AND `completed` >= t.`hours` ";
		if ( count( $manually_selected_task_ids ) ) {
			$sql .= " AND ( ";
			foreach ( $manually_selected_task_ids as $task_id ) {
				$sql .= " t.task_id = " . (int) $task_id . " OR ";
			}
			$sql = rtrim( $sql, ' OR' );
			$sql .= " )";
		} else {
			if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
				$sql .= " AND `fully_completed` = 1";
			}
		}
		$sql .= " GROUP BY t.task_id ";
		$sql .= " ORDER BY t.task_order ASC ";
		$res = qa( $sql );
		foreach ( $res as $rid => $r ) {
			// todo: are we billing the hours worked, or the hours quoted.

			if ( $r['manual_task_type'] < 0 ) {
				$res[ $rid ]['manual_task_type'] = $job['default_task_type'];
			}
			if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
				// we have to have a "fully_completed" flag before invoicing.
				if ( ! $r['billable'] ) {
					// unbillable - pass onto invoice as a blank.
					// todo: better ! pass through hours/amount so customer can see.
					$res[ $rid ]['hours']  = 0;
					$res[ $rid ]['amount'] = 0;
				}
			} else {
				// old way, only completed hour tasks or "fully_completed" tasks come through.
				if ( ! $r['billable'] ) {
					// unbillable - pass onto invoice as a blank.
					// todo: better ! pass through hours/amount so customer can see.
					$res[ $rid ]['hours']           = 0;
					$res[ $rid ]['amount']          = 0;
					$res[ $rid ]['fully_completed'] = 1;
				} else if ( $r['hours'] <= 0 && $r['amount'] <= 0 && ! $r['fully_completed'] ) {
					// no hours, no amount, and not fully completed. skip this one.
					unset( $res[ $rid ] );
				} else if ( $r['hours'] <= 0 && $r['amount'] > 0 && ! $r['fully_completed'] ) {
					// no hours set. but we have an amount. and we are not completed.
					// skip.
					unset( $res[ $rid ] );
				} else if ( $r['hours'] <= 0 && $r['fully_completed'] ) {
					// no hours, but we are fully completed.
					// keep this one
				} else if ( $r['hours'] > 0 && ( $r['completed'] <= 0 || $r['completed'] < $r['hours'] ) ) {
					// we haven't yet completed this task based on the hours.
					unset( $res[ $rid ] );
				}
			}

			if ( module_config::c( 'job_invoice_show_date_range', 1 ) ) {
				// check if this job is a renewable job.
				if ( $job['date_renew'] != '0000-00-00' ) {
					$res[ $rid ]['custom_description'] = $r['description'] . ' ' . _l( '(%s to %s)', print_date( $job['date_start'] ), print_date( strtotime( "-1 day", strtotime( $job['date_renew'] ) ) ) );
				}
			}
		}

		//        print_r($res);exit;
		return $res;
		//return get_multiple("task",array('job_id'=>$job_id),"task_id","exact","task_id");

	}

	public static function get_tasks_todo() {

		$tasks = array();

		// find all the tasks that are due for completion
		// sorted by due date.
		$sql = "SELECT ";
		$sql .= " SUM(tl.hours) AS `hours_completed` ";
		$sql .= " ,t.* ";
		$sql .= " FROM `" . _DB_PREFIX . "task` t ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "task_log` tl ON t.task_id = tl.task_id";
		$sql .= " WHERE t.date_due != '0000-00-00' ";
		$sql .= " AND j.date_start != '0000-00-00' ";
		// from bharrison - task items based on logged in user
		$sql .= " AND ( t.`user_id` = 0 OR t.`user_id` = " . (int) module_security::get_loggedin_id() . ")";
		//$sql .= " AND ((t.hours = 0 AND `completed` = 0) OR `completed` < t.hours)";
		if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
			// tasks have to have a 'fully_completed' before they are done.
			$sql .= " AND t.fully_completed = 0";
		}
		$sql .= " AND (t.approval_required = 0 OR t.approval_required = 2)";
		$sql .= " GROUP BY t.task_id ";
		$sql .= " ORDER BY t.date_due ASC ";
		//$sql .= " LIMIT ".(int)module_config::c('todo_list_limit',6);
		$tasks_search = qa( $sql );
		foreach ( $tasks_search as $task_id => $task ) {


			// we don't bother checking job access because this task is assigned to this user and only this user.
			//$job = self::get_job($task['job_id']);
			//if(!$job || $job['job_id']!=$task['job_id'])continue;

			if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
				// tasks have to have a 'fully_completed' before they are done.

			} else {
				// old way. based on logged hours:
				if ( ( $task['hours'] <= 0 && $task['fully_completed'] == 0 ) || ( $task['hours'] > 0 && $task['hours_completed'] < $task['hours'] ) ) {
					//keep
				} else {
					continue;
				}
			}
			$tasks[ $task_id ] = $task;
			if ( count( $tasks ) > module_config::c( 'todo_list_limit', 20 ) ) {
				break;
			}
		}

		return $tasks;

	}

	public static function get_task_log( $task_id ) {
		return get_multiple( "task_log", array( 'task_id' => $task_id ), "task_log_id", "exact", "task_log_id" );

	}

	private static function _job_cache_key( $job_id, $args = array() ) {
		return 'job_' . $job_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_job( $job_id, $full = true, $skip_permissions = false ) {
		$job_id = (int) $job_id;
		if ( $job_id <= 0 ) {
			$job = array();
		} else {

			$cache_key = self::_job_cache_key( $job_id, array( $job_id, $full, $skip_permissions ) );
			if ( $cached_item = module_cache::get( 'job', $cache_key ) ) {
				return $cached_item;
			}
			$cache_key_full = self::_job_cache_key( $job_id, array( $job_id, true, $skip_permissions ) );
			if ( $cache_key_full != $cache_key && $cached_item = module_cache::get( 'job', $cache_key_full ) ) {
				return $cached_item;
			}
			$cache_timeout = module_config::c( 'cache_objects', 60 );


			$job = get_single( "job", "job_id", $job_id );
		}
		// check permissions
		if ( $job && isset( $job['job_id'] ) && $job['job_id'] == $job_id ) {
			switch ( self::get_job_access_permissions() ) {
				case _JOB_ACCESS_ALL:

					break;
				case _JOB_ACCESS_ASSIGNED:
					// only assigned jobs!
					$has_job_access = false;
					if ( $job['user_id'] == module_security::get_loggedin_id() ) {
						$has_job_access = true;
						break;
					}
					$tasks = module_job::get_tasks( $job['job_id'] );
					foreach ( $tasks as $task ) {
						if ( $task['user_id'] == module_security::get_loggedin_id() ) {
							$has_job_access = true;
							break;
						}
					}
					unset( $tasks );
					if ( ! $has_job_access ) {
						if ( $skip_permissions ) {
							$job['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
						} else {
							$job = false;
						}
					}
					break;
				case _JOB_ACCESS_CUSTOMER:
					// tie in with customer permissions to only get jobs from customers we can access.
					$customers      = module_customer::get_customers();
					$has_job_access = false;
					if ( isset( $customers[ $job['customer_id'] ] ) ) {
						$has_job_access = true;
					}
					/*foreach($customers as $customer){
                        // todo, if($job['customer_id'] == 0) // ignore this permission
                        if($customer['customer_id']==$job['customer_id']){
                            $has_job_access = true;
                            break;
                        }
                    }*/
					unset( $customers );
					if ( ! $has_job_access ) {
						if ( $skip_permissions ) {
							$job['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
						} else {
							$job = false;
						}
					}
					break;
			}
			if ( $job ) {
				$job['taxes'] = get_multiple( 'job_tax', array( 'job_id' => $job_id ), 'job_tax_id', 'exact', 'order' );
			}
		}
		if ( ! $full ) {
			// unserialize our cached staff_total_grouped key (and other cache keys?)
			// this is used in finance.php line 1053
			$job['staff_total_grouped'] = array();
			if ( isset( $job['c_staff_total_grouped'] ) && strlen( $job['c_staff_total_grouped'] ) ) {
				$job['staff_total_grouped'] = @unserialize( $job['c_staff_total_grouped'] );
			}
			if ( isset( $cache_key ) ) {
				module_cache::put( 'job', $cache_key, $job, $cache_timeout );
			}

			return $job;
		}
		if ( ! $job ) {
			$customer_id = 0;
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] ) {
				//
				$customer_id = (int) $_REQUEST['customer_id'];
				// find default website id to use.
				if ( isset( $_REQUEST['website_id'] ) ) {
					$website_id = (int) $_REQUEST['website_id'];
				} else {

				}
			}

			$ucmjob              = new UCMJob();
			$ucmjob->customer_id = $customer_id;
			$default_job_name    = $ucmjob->get_new_document_number();

			$job = array(
				'job_id'               => 'new',
				'customer_id'          => $customer_id,
				'website_id'           => ( isset( $_REQUEST['website_id'] ) ? $_REQUEST['website_id'] : 0 ),
				'hourly_rate'          => 0, // default to customer hourly rate. module_customer::c( 'hourly_rate', 60, $customer_id ),
				'name'                 => $default_job_name,
				'date_quote'           => date( 'Y-m-d' ),
				'date_start'           => module_config::c( 'job_allow_quotes', 0 ) ? '' : date( 'Y-m-d' ),
				'date_due'             => '',
				'date_completed'       => '',
				'date_renew'           => '',
				'user_id'              => module_security::get_loggedin_id(),
				'renew_job_id'         => '',
				'status'               => module_config::s( 'job_status_default', 'New' ),
				'type'                 => module_config::s( 'job_type_default', 'Website Design' ),
				'currency_id'          => module_config::c( 'default_currency_id', 1 ),
				'auto_task_numbers'    => '0',
				'default_task_type'    => module_config::c( 'default_task_type', _TASK_TYPE_HOURS_AMOUNT ), //
				'description'          => '',
				'quote_id'             => 0,
				'discount_description' => _l( 'Discount:' ),
				'discount_amount'      => 0,
				'discount_type'        => module_config::c( 'invoice_discount_type', _DISCOUNT_TYPE_BEFORE_TAX ),
			);
			if ( isset( $_REQUEST['from_quote_id'] ) && (int) $_REQUEST['from_quote_id'] ) {
				$quote = module_quote::get_quote( $_REQUEST['from_quote_id'] );
				$job   = array_merge( $job, $quote );
				if ( ! module_config::c( 'quote_copy_job_name', 0 ) ) {
					// we want to use the original job name, not the quote name.
					$job['name'] = $default_job_name;
				}
				$job['date_quote'] = $quote['date_create'];
				$job['date_start'] = date( 'Y-m-d' );
				$job['quote_id']   = (int) $_REQUEST['from_quote_id'];
			}
			// some defaults from the db.
			$job['total_tax_rate'] = module_config::c( 'tax_percent', 10 );
			$job['total_tax_name'] = module_config::c( 'tax_name', 'TAX' );
			if ( $customer_id > 0 ) {
				$customer_data = module_customer::get_customer( $customer_id, false, true );
				if ( $customer_data && isset( $customer_data['default_tax'] ) && $customer_data['default_tax'] >= 0 ) {
					$job['total_tax_rate'] = $customer_data['default_tax'];
					$job['total_tax_name'] = $customer_data['default_tax_name'];
				}
			}
		}
		// new support for multiple taxes
		if ( ! isset( $job['taxes'] ) || ( ! count( $job['taxes'] ) && $job['total_tax_rate'] > 0 ) ) {
			$job['taxes'] = array();
			$tax_rates    = explode( ',', $job['total_tax_rate'] );
			$tax_names    = explode( ',', $job['total_tax_name'] );
			foreach ( $tax_rates as $tax_rate_id => $tax_rate_amount ) {
				if ( $tax_rate_amount > 0 ) {
					$job['taxes'][] = array(
						'order'     => 0,
						'percent'   => $tax_rate_amount,
						'name'      => isset( $tax_names[ $tax_rate_id ] ) ? $tax_names[ $tax_rate_id ] : $job['total_tax_name'],
						'total'     => 0,
						// original value that tax was calculated againt
						'amount'    => 0,
						// final amount of calculated tax
						'discount'  => 0,
						// if any discounts are applied to taxes, add them here. this is used in a complicated hack back in job.php to work out new job prices.
						'increment' => module_config::c( 'tax_multiple_increment', 0 ),
						//todo: db this option
					);
				}
			}
		}


		if ( $job ) {
			// work out total hours etc..
			$job['total_hours']                         = 0;
			$job['total_hours_completed']               = 0;
			$job['total_hours_overworked']              = 0;
			$job['total_sub_amount']                    = 0;
			$job['total_sub_amount_taxable']            = 0;
			$job['total_sub_amount_unbillable']         = 0;
			$job['total_sub_amount_invoicable']         = 0;
			$job['total_sub_amount_invoicable_taxable'] = 0;
			$job['total_amount_invoicable']             = 0;
			$job['total_tasks_remain']                  = 0;

			$job['total_amount']                  = 0;
			$job['total_amount_paid']             = 0;
			$job['total_amount_invoiced']         = 0;
			$job['total_amount_invoiced_deposit'] = 0;
			$job['total_amount_todo']             = 0;
			$job['total_amount_outstanding']      = 0;
			$job['total_amount_due']              = 0;
			$job['total_hours_remain']            = 0;
			$job['total_percent_complete']        = isset( $job['total_percent_complete'] ) ? $job['total_percent_complete'] : 0;

			$job['total_tax']            = 0;
			$job['total_tax_invoicable'] = 0;

			$job['invoice_discount_amount']        = 0;
			$job['invoice_discount_amount_on_tax'] = 0;
			$job['total_amount_discounted']        = 0;

			// new feature to invoice incompleted tasks
			$job['uninvoiced_task_ids'] = array();

			// new staff expenses/totals
			$job['staff_hourly_rate']                 = $job['hourly_rate'];
			$job['staff_total_hours']                 = 0;
			$job['staff_total_hours_completed']       = 0;
			$job['staff_total_hours_overworked']      = 0;
			$job['staff_total_sub_amount']            = 0;
			$job['staff_total_sub_amount_unbillable'] = 0;
			$job['staff_total_amount']                = 0;
			$job['staff_total_grouped']               = array(); // total staff expenses grouped by individual staff members.
			$job['total_net_amount']                  = 0; // after the staff expense is taken away.


			if ( $job_id > 0 ) {
				$non_hourly_job_count = $non_hourly_job_completed = 0;
				$tasks                = self::get_tasks( $job['job_id'] );

				$job_percentage_complete_averages = array();

				foreach ( $tasks as $task_id => $task ) {

					// new support for different task types
					if ( ! isset( $task['manual_task_type'] ) || $task['manual_task_type'] < 0 ) {
						$task['manual_task_type'] = $job['default_task_type'];
					}

					if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
						// jobs have to be marked fully_completd.
						if ( ! $task['fully_completed'] ) {
							$job['total_tasks_remain'] ++;
						}
					} else {
						if ( $task['amount'] != 0 && $task['completed'] <= 0 ) {
							$job['total_tasks_remain'] ++;
						} else if ( $task['hours'] > 0 && $task['completed'] < $task['hours'] ) {
							$job['total_tasks_remain'] ++;
						}
					}
					$tasks[ $task_id ]['sum_amount'] = 0;
					if ( $task['amount'] != 0 ) {
						// we have a custom amount for this task.
						// do we multiply it by qty (stored in hours?)
						if ( $task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) {
							$tasks[ $task_id ]['sum_amount'] = $task['amount'] * $task['hours'];
						} else {
							$tasks[ $task_id ]['sum_amount'] = $task['amount'];
						}
					}
					if ( $task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT && $task['hours'] > 0 && $task['amount'] == 0 ) {
						$tasks[ $task_id ]['sum_amount'] = ( $task['hours'] * $job['hourly_rate'] );
					}
					if ( $task['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT && $task['hours'] > 0 ) {
						$job['total_hours']   += $task['hours'];
						$task_completed_hours = min( $task['hours'], $task['completed'] );
						if ( $task['fully_completed'] ) {
							// hack to record that we have worked 100% of this task.
							$task_completed_hours = $task['hours'];
						}
						$job['total_hours_completed'] += $task_completed_hours;
						if ( $task['completed'] > $task['hours'] ) {
							$job['total_hours_overworked'] += ( $task['completed'] - $task['hours'] );
						} else if ( $task['completed'] > 0 ) {
							// underworked hours
							$job['total_hours_overworked'] += ( $task['completed'] - $task['hours'] );
						}
						if ( $task['amount'] <= 0 ) {
							$tasks[ $task_id ]['sum_amount'] = ( $task['hours'] * $job['hourly_rate'] );
						}
					} else {
						// it's a non-hourly task.
						// work out if it's completed or not.
						$non_hourly_job_count ++;
						if ( $task['fully_completed'] ) {
							$non_hourly_job_completed ++;
						}
					}
					if ( ! $task['invoiced'] && $task['billable'] ) {
						$job['uninvoiced_task_ids'][] = $task_id;
					}
					if ( ! $task['invoiced'] && $task['billable'] &&
					     (
						     module_config::c( 'job_task_log_all_hours', 1 )
						     ||
						     ( $task['hours'] > 0 && $task['completed'] > 0 && $task['completed'] >= $task['hours'] )
						     ||
						     ( $task['hours'] <= 0 && $task['fully_completed'] )
					     )
					) {
						/*if(module_config::c('job_task_log_all_hours',1)){*/
						// a task has to be marked "fully_completeD" before it will be invoiced.
						if ( $task['fully_completed'] ) {
							$job['total_sub_amount_invoicable'] += $tasks[ $task_id ]['sum_amount'];
							if ( $task['taxable'] ) {
								if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
									foreach ( $job['taxes'] as $job_tax_id => $job_tax ) {
										$job['total_tax_invoicable'] += round( ( $tasks[ $task_id ]['sum_amount'] * ( $job_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
									}
								} else {
									$job['total_sub_amount_invoicable_taxable'] += $tasks[ $task_id ]['sum_amount'];
								}
							}
						}
						/*}else{
                            $job['total_sub_amount_invoicable'] += $tasks[$task_id]['sum_amount'];
                            if($task['taxable']){
                                if(module_config::c('tax_calculate_mode',_TAX_CALCULATE_AT_END)==_TAX_CALCULATE_INCREMENTAL){
                                    $job['total_tax_invoicable'] += round(($tasks[$task_id]['sum_amount'] * ($job['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
                                }else{
                                    $job['total_sub_amount_invoicable_taxable'] += $tasks[$task_id]['sum_amount'];
                                }
                            }
                            //(min($task['hours'],$task['completed']) * $job['hourly_rate']);
                        }*/
					}

					if ( $task['taxable'] && $task['billable'] ) {
						$job['total_sub_amount_taxable'] += $tasks[ $task_id ]['sum_amount'];
						if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
							//$job['total_tax'] += round(($tasks[$task_id]['sum_amount'] * ($job['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
							// todo - incremental multi-tax calculation
							foreach ( $job['taxes'] as $job_tax_id => $job_tax ) {
								if ( ! isset( $job['taxes'][ $job_tax_id ]['total'] ) ) {
									$job['taxes'][ $job_tax_id ]['total'] = 0;
								}
								$job['taxes'][ $job_tax_id ]['total']  += $tasks[ $task_id ]['sum_amount'];
								$job['taxes'][ $job_tax_id ]['amount'] += round( ( $tasks[ $task_id ]['sum_amount'] * ( $job_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
							}
						}
					}
					if ( $task['billable'] ) {
						$job['total_sub_amount'] += $tasks[ $task_id ]['sum_amount'];
					} else {
						$job['total_sub_amount_unbillable'] += $tasks[ $task_id ]['sum_amount'];
					}

					$job_percentage_complete_averages[] = self::get_percentage( $tasks[ $task_id ] );


					// new staff expenses calculations

					if ( self::job_task_has_split_hours( $job_id, $job, $task_id, $task ) ) {
						$tasks[ $task_id ]['staff_sum_amount'] = 0;
						switch ( $task['manual_task_type'] ) {
							case _TASK_TYPE_QTY_AMOUNT:
								$tasks[ $task_id ]['staff_sum_amount'] = $task['staff_amount'] * $task['staff_hours'];
								break;
							case _TASK_TYPE_AMOUNT_ONLY:
								$tasks[ $task_id ]['staff_sum_amount'] = $task['staff_amount'];
								break;
							case _TASK_TYPE_HOURS_AMOUNT:
								$tasks[ $task_id ]['staff_sum_amount'] = ( $task['staff_amount'] == 0 ? $task['staff_hours'] * $job['staff_hourly_rate'] : $task['staff_amount'] * $task['staff_hours'] );
								break;
						}
						if ( $task['billable'] ) {
							$job['staff_total_sub_amount'] += $tasks[ $task_id ]['staff_sum_amount'];
							if ( ! isset( $job['staff_total_grouped'][ $task['user_id'] ] ) ) {
								$job['staff_total_grouped'][ $task['user_id'] ] = 0;
							}
							$job['staff_total_grouped'][ $task['user_id'] ] += $tasks[ $task_id ]['staff_sum_amount'];

						} else {
							$job['staff_total_sub_amount_unbillable'] += $tasks[ $task_id ]['staff_sum_amount'];
						}
					}

				} // end task loop

				$job['total_hours_remain'] = $job['total_hours'] - $job['total_hours_completed'];


				// add any discounts.
				if ( $job['discount_amount'] != 0 ) {
					if ( $job['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
						// after tax discount ::::::::::
						// handled below.
						//$job['final_modification'] = -$job['discount_amount'];
					} else if ( $job['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
						// before tax discount:::::
						//$job['final_modification'] = -$job['discount_amount'];
						// problem : this 'discount_amount_on_tax' calculation may not match the correct final discount calculation as per below
						if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
							// tax calculated along the way.
							// we have discounted the 'total amount taxable' so that means we need to reduce the tax amount by that much as well.
							foreach ( $job['taxes'] as $job_tax_id => $job_tax ) {
								$this_tax_discount             = round( ( $job['discount_amount'] * ( $job['taxes'][ $job_tax_id ]['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
								$job['discount_amount_on_tax'] += $this_tax_discount;
								if ( ! isset( $job['taxes'][ $job_tax_id ]['total'] ) ) {
									$job['taxes'][ $job_tax_id ]['total'] = 0;
								}
								$job['taxes'][ $job_tax_id ]['total']    -= $job['discount_amount'];
								$job['taxes'][ $job_tax_id ]['amount']   -= $this_tax_discount;
								$job['taxes'][ $job_tax_id ]['discount'] = $this_tax_discount;
							}
						} else {

							// we work out what the tax would have been if there was no applied discount
							// this is used in job.php
							$job['taxes_backup']                    = $job['taxes'];
							$job['total_sub_amount_taxable_backup'] = $job['total_sub_amount_taxable'];
							$total_tax_before_discount              = 0;
							foreach ( $job['taxes'] as $job_tax_id => $job_tax ) {
								$job['taxes'][ $job_tax_id ]['total']  = $job['total_sub_amount_taxable'];
								$job['taxes'][ $job_tax_id ]['amount'] = round( ( $job['total_sub_amount_taxable'] * ( $job_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
								// here we adjust the 'total_sub_amount_taxable' to include the value from the previous calculation.
								// this is for multiple taxes that addup as they go (eg: Canada)
								if ( isset( $job_tax['increment'] ) && $job_tax['increment'] ) {
									$job['total_sub_amount_taxable'] += $job['taxes'][ $job_tax_id ]['amount'];
								}
								$total_tax_before_discount += $job['taxes'][ $job_tax_id ]['amount'];
							}
							$job['taxes']                    = $job['taxes_backup'];
							$job['total_sub_amount_taxable'] = $job['total_sub_amount_taxable_backup'];
						}
						// remove the discount amount from the 'sub total' and the 'taxable total' but don't go negative on it.
						// remove the discount from any non-taxable portion first.
						$non_taxable_amount   = $job['total_sub_amount'] - $job['total_sub_amount_taxable'];
						$non_taxable_discount = min( $job['discount_amount'], $non_taxable_amount );
						$taxable_discount     = $job['discount_amount'] - $non_taxable_discount;

						//echo "non tax $non_taxable_amount \n nontax discount: $non_taxable_discount \n tax discount: $taxable_discount \n";print_r($job);exit;
						$job['total_sub_amount']         -= $job['discount_amount'];
						$job['total_sub_amount_taxable'] -= $taxable_discount;
					}
				}


				if ( count( $job_percentage_complete_averages ) > 0 ) {
					if ( ! isset( $job['total_percent_complete_manual'] ) || ! $job['total_percent_complete_manual'] ) {
						$job['total_percent_complete'] = round( array_sum( $job_percentage_complete_averages ) / count( $job_percentage_complete_averages ), 2 );
					} else {
						$job['total_percent_complete_calculated'] = round( array_sum( $job_percentage_complete_averages ) / count( $job_percentage_complete_averages ), 2 );
					}
				}
				/*if($job['total_hours'] > 0){
                    // total hours completed. work out job task based on hours completed.
                    $job['total_percent_complete'] = round($job['total_hours_completed'] / $job['total_hours'],2);
                }else if($non_hourly_job_count>0){
                    // work out job completed rate based on $non_hourly_job_completed and $non_hourly_job_count
                    $job['total_percent_complete'] = round($non_hourly_job_completed/$non_hourly_job_count,2);
                }*/


				// find any invoices
				$invoices = module_invoice::get_invoices( array( 'job_id' => $job_id ) );
				foreach ( $invoices as $invoice ) {
					$invoice = module_invoice::get_invoice( $invoice['invoice_id'] );
					if ( ! $invoice ) {
						continue;
					}
					//print_r($invoice);
					// we only ad up the invoiced tasks that are from this job
					// an invoice could have added manually more items to it, so this would throw the price out.
					$this_invoice         = 0;
					$this_invoice_taxable = 0;
					$invoice_items        = module_invoice::get_invoice_items( $invoice['invoice_id'] );
					// first loop will find out of this is a merged invoice or not.
					$merged_invoice = false;
					foreach ( $invoice_items as $invoice_item ) {
						if ( $invoice_item['task_id'] && ! isset( $tasks[ $invoice_item['task_id'] ] ) ) {
							$merged_invoice = true;
						}
					}
					// if it's a merged invoice we don't add non-task-id items to the total.
					// if its a normal non-merged invoice then we can add the non-task linked items to the total.
					if ( ! $merged_invoice ) {
						$this_invoice = $invoice['total_amount'];
					} else {
						foreach ( $invoice_items as $invoice_item ) {
							if ( $invoice_item['task_id'] && isset( $tasks[ $invoice_item['task_id'] ] ) && $tasks[ $invoice_item['task_id'] ]['billable'] ) {
								$this_invoice += $tasks[ $invoice_item['task_id'] ]['sum_amount'];
								if ( $invoice_item['taxable'] ) {
									$this_invoice_taxable += $tasks[ $invoice_item['task_id'] ]['sum_amount'];
									if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
										foreach ( $invoice_item['taxes'] as $invoice_item_tax ) {
											$this_invoice += round( $tasks[ $invoice_item['task_id'] ]['sum_amount'] * ( $invoice_item_tax['percent'] / 100 ), module_config::c( 'currency_decimal_places', 2 ) );
										}
									}
								}
							}
						}
					}
					// any discounts ?
					$job['invoice_discount_amount']        += $invoice['discount_amount'];
					$job['invoice_discount_amount_on_tax'] += $invoice['discount_amount_on_tax'];

					// todo - move all this tax calculation back to
					if ( $merged_invoice && module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_AT_END && $this_invoice_taxable > 0 ) {
						$this_invoice_tax = 0;
						foreach ( $invoice['taxes'] as $invoice_tax ) {
							// todo - incremental or what not in here.
							$this_invoice_tax = ( $this_invoice_tax + ( $this_invoice_taxable * ( $invoice_tax['percent'] / 100 ) ) );
						}
						$this_invoice += $this_invoice_tax;
						//$this_invoice = ($this_invoice + ($this_invoice_taxable * ($invoice['total_tax_rate'] / 100)));
					}
					//print_r($invoice);

					if ( $invoice['deposit_job_id'] == $job_id ) {
						$job['total_amount_invoiced_deposit'] += $this_invoice;
					} else {
					}
					$job['total_amount_invoiced'] += $this_invoice;
					$job['total_amount_paid']     += min( $invoice['total_amount_paid'], $this_invoice );

					$job['total_amount_outstanding'] += min( $invoice['total_amount_due'], $this_invoice );

				}

				// todo: save these two values in the database so that future changes do not affect them.
				if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_AT_END ) {
					$job['total_tax']            = 0;
					$job['total_tax_invoicable'] = 0;
					$previous_tax_id             = false;
					foreach ( $job['taxes'] as $job_tax_id => $job_tax ) {
						if ( ! isset( $job['taxes'][ $job_tax_id ]['total'] ) ) {
							$job['taxes'][ $job_tax_id ]['total'] = 0;
						}
						if ( ! isset( $job['taxes'][ $job_tax_id ]['total_invoicable'] ) ) {
							$job['taxes'][ $job_tax_id ]['total_invoicable'] = 0;
						}
						if ( ! isset( $job['taxes'][ $job_tax_id ]['amount_invoicable'] ) ) {
							$job['taxes'][ $job_tax_id ]['amount_invoicable'] = 0;
						}
						$job['taxes'][ $job_tax_id ]['total']            += $job['total_sub_amount_taxable'];
						$job['taxes'][ $job_tax_id ]['total_invoicable'] += $job['total_sub_amount_invoicable_taxable'];
						if ( isset( $job_tax['increment'] ) && $job_tax['increment'] && $previous_tax_id ) {
							$job['taxes'][ $job_tax_id ]['total']            += $job['taxes'][ $previous_tax_id ]['amount'];
							$job['taxes'][ $job_tax_id ]['total_invoicable'] += $job['taxes'][ $previous_tax_id ]['amount_invoicable'];
						}
						$t                                     = round( ( ( $job['taxes'][ $job_tax_id ]['total'] ) * ( $job_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
						$job['taxes'][ $job_tax_id ]['amount'] += $t;
						$job['total_tax']                      += $t;

						$t                                                = round( ( $job['taxes'][ $job_tax_id ]['total_invoicable'] * ( $job_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
						$job['taxes'][ $job_tax_id ]['amount_invoicable'] += $t;
						$job['total_tax_invoicable']                      += $t;
						$previous_tax_id                                  = $job_tax_id;
					}
					//$job['total_tax'] = ( ($job['total_sub_amount_taxable']) * ($job['total_tax_rate'] / 100));
					//$job['total_tax_invoicable'] =$job['total_sub_amount_invoicable_taxable'] > 0 ? ($job['total_sub_amount_invoicable_taxable'] * ($job['total_tax_rate'] / 100)) : 0;
				}
				$job['total_amount'] = round( $job['total_sub_amount'] + $job['total_tax'], module_config::c( 'currency_decimal_places', 2 ) );
				if ( $job['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
					$job['total_amount']                -= $job['discount_amount'];
					$job['total_sub_amount_invoicable'] -= $job['discount_amount'];
				}
				$job['total_amount_invoicable'] = $job['total_sub_amount_invoicable'] + $job['total_tax_invoicable']; // + ($job['total_sub_amount_invoicable'] * ($job['total_tax_rate'] / 100));

				$job['total_amount_due'] = $job['total_amount'] - $job['total_amount_paid']; //todo: chekc if this is wrong with non-invoicable tasks.
				//$job['total_amount_outstanding'] = $job['total_amount_invoiced'] - $job['total_amount_paid'];
				$job['total_amount_discounted'] = $job['total_amount'] - $job['invoice_discount_amount'] - $job['invoice_discount_amount_on_tax'];
				//$job['total_amount_invoicable'] = $job['total_amount_invoicable'] - $job['invoice_discounts']-$job['invoice_discounts_tax'];

				$job['total_amount_todo'] = $job['total_amount_discounted'] - $job['total_amount_invoiced'] - $job['total_amount_invoicable'];//$job['total_amount_paid'] -

				// staff calculations
				if ( $job['staff_total_sub_amount'] > 0 ) {
					// tax for staff??
					$job['staff_total_amount'] = $job['staff_total_sub_amount'];
				}
				$job['total_net_amount'] = $job['total_amount'] - $job['staff_total_amount'];
			}


		}
		if ( isset( $cache_key ) ) {
			module_cache::put( 'job', $cache_key, $job, $cache_timeout );
		}
		self::save_job_cache( $job_id, $job );

		return $job;
	}

	public static function save_job( $job_id, $data ) {

		if ( isset( $data['default_renew_auto'] ) && ! isset( $data['renew_auto'] ) ) {
			$data['renew_auto'] = 0;
		}
		if ( isset( $data['default_renew_invoice'] ) && ! isset( $data['renew_invoice'] ) ) {
			$data['renew_invoice'] = 0;
		}
		if ( isset( $data['total_percent_complete_override'] ) && $data['total_percent_complete_override'] != '' && $data['total_percent_complete_override'] <= 100 ) {
			$data['total_percent_complete_manual'] = 1;
			$data['total_percent_complete']        = $data['total_percent_complete_override'] / 100;
		} else {
			$data['total_percent_complete_manual'] = 0;
		}

		if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
			// check we have access to this customer from this job.
			$customer_check = module_customer::get_customer( $data['customer_id'] );
			if ( ! $customer_check || $customer_check['customer_id'] != $data['customer_id'] ) {
				unset( $data['customer_id'] );
			}
		}
		if ( isset( $data['website_id'] ) && $data['website_id'] ) {
			$website = module_website::get_website( $data['website_id'] );
			if ( $website && (int) $website['website_id'] > 0 && $website['website_id'] == $data['website_id'] ) {
				// website exists.
				// make this one match the website customer_id, or set teh website customer_id if it doesn't have any.
				if ( (int) $website['customer_id'] > 0 ) {
					if ( $data['customer_id'] > 0 && $data['customer_id'] != $website['customer_id'] ) {
						set_message( 'Changed this Job to match the Website customer' );
					}
					$data['customer_id'] = $website['customer_id'];
				} else if ( isset( $data['customer_id'] ) && $data['customer_id'] > 0 ) {
					// set the website customer id to this as well.
					update_insert( 'website_id', $website['website_id'], 'website', array( 'customer_id' => $data['customer_id'] ) );
				}
			}
		}
		if ( (int) $job_id > 0 ) {
			$original_job_data = self::get_job( $job_id, false );
			if ( ! $original_job_data || $original_job_data['job_id'] != $job_id ) {
				$original_job_data = array();
				$job_id            = false;
			}
		} else {
			$original_job_data = array();
			$job_id            = false;
		}

		// fix for default hourly rate.
		if(empty($data['hourly_rate'])){
			$customer_hourly_rate = module_customer::c( 'hourly_rate', 60, (int) $data['customer_id'] );
			if($customer_hourly_rate > 0){
				$data['hourly_rate'] = $customer_hourly_rate;
			}
		}

		$job_id = update_insert( "job_id", $job_id, "job", $data );
		if ( $job_id ) {

			// save the job tax rates (copied from invoice.php)
			if ( isset( $data['tax_ids'] ) && isset( $data['tax_names'] ) && $data['tax_percents'] ) {
				$existing_taxes = get_multiple( 'job_tax', array( 'job_id' => $job_id ), 'job_tax_id', 'exact', 'order' );
				$order          = 1;
				foreach ( $data['tax_ids'] as $key => $val ) {
					// if(isset($data['tax_percents'][$key]) && $data['tax_percents'][$key] == 0){
					// we are not saving this particular tax item because it has a 0% tax rate
					// }else{
					if ( (int) $val > 0 && isset( $existing_taxes[ $val ] ) ) {
						// this means we are trying to update an existing record on the job_tax table, we confirm this id matches this job.
						$job_tax_id = $val;
						unset( $existing_taxes[ $job_tax_id ] ); // so we know which ones to remove from the end.
					} else {
						$job_tax_id = false; // create new record
					}
					$job_tax_data = array(
						'job_id'    => $job_id,
						'percent'   => isset( $data['tax_percents'][ $key ] ) ? $data['tax_percents'][ $key ] : 0,
						'amount'    => 0, // calculate this where? nfi? maybe on final job get or something.
						'name'      => isset( $data['tax_names'][ $key ] ) ? $data['tax_names'][ $key ] : 'TAX',
						'order'     => $order ++,
						'increment' => isset( $data['tax_increment_checkbox'] ) && $data['tax_increment_checkbox'] ? 1 : 0,
					);
					$job_tax_id   = update_insert( 'job_tax_id', $job_tax_id, 'job_tax', $job_tax_data );
					// }
				}
				foreach ( $existing_taxes as $existing_tax ) {
					delete_from_db( 'job_tax', array( 'job_id', 'job_tax_id' ), array( $job_id, $existing_tax['job_tax_id'] ) );
				}
			}

			module_cache::clear( 'job' );
			$result          = self::save_job_tasks( $job_id, $data );
			$check_completed = true;
			switch ( $result['status'] ) {
				case 'created':
					// we added a new task.

					break;
				case 'deleted':
					// we deleted a task.

					break;
				case 'edited':
					// we changed a task (ie: completed?);

					break;
				default:
					// nothing changed.
					// $check_completed = false;
					break;
			}
			if ( $check_completed ) {
				self::update_job_completion_status( $job_id );
			}
			if ( $original_job_data ) {
				// we check if the hourly rate has changed
				if ( isset( $data['hourly_rate'] ) && $data['hourly_rate'] != $original_job_data['hourly_rate'] ) {
					// update all the task hours, but only for hourly tasks:
					$sql = "UPDATE `" . _DB_PREFIX . "task` SET `amount` = 0 WHERE `hours` > 0 AND job_id = " . (int) $job_id . " AND ( manual_task_type = " . _TASK_TYPE_HOURS_AMOUNT;
					if ( $data['default_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
						$sql .= " OR manual_task_type = -1 ";
					}
					$sql .= " )";
					query( $sql );

				}
				// check if the job assigned user id has changed.
				if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) {
					if ( isset( $data['user_id'] ) ) { // && $data['user_id'] != $original_job_data['user_id']){
						// user id has changed! update any that were the old user id.
						$sql = "UPDATE `" . _DB_PREFIX . "task` SET `user_id` = " . (int) $data['user_id'] .
						       " WHERE (`user_id` = " . (int) $original_job_data['user_id'] . " OR user_id = 0) AND job_id = " . (int) $job_id;
						query( $sql );
					}
				}
				// check if the due date has changed.
				if (
					isset( $original_job_data['date_due'] ) && $original_job_data['date_due'] &&
					isset( $data['date_due'] ) && $data['date_due'] && $data['date_due'] != '0000-00-00' &&
					$original_job_data['date_due'] != $data['date_due']
				) {
					// the date has changed.
					// update all the tasks with this new date.
					$tasks = self::get_tasks( $job_id );
					foreach ( $tasks as $task ) {
						if ( ! $task['date_due'] || $task['date_due'] == '0000-00-00' ) {
							// no previously set task date. set it
							update_insert( 'task_id', $task['task_id'], 'task', array( 'date_due' => $data['date_due'] ) );
						} else if ( $task['date_due'] == $original_job_data['date_due'] ) {
							// the date was the old date. do we change it?
							// only change it on incompleted tasks.
							$percentage = self::get_percentage( $task );
							if ( $percentage < 1 || ( module_config::c( 'job_tasks_overwrite_completed_due_dates', 0 ) && $percentage == 1 ) ) {
								update_insert( 'task_id', $task['task_id'], 'task', array( 'date_due' => $data['date_due'] ) );
							}
						} else {
							// there's a new date
							if ( module_config::c( 'job_tasks_overwrite_diff_due_date', 0 ) ) {
								update_insert( 'task_id', $task['task_id'], 'task', array( 'date_due' => $data['date_due'] ) );
							}
						}
					}
				}
			}

		}
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::save_extras( 'job', 'job_id', $job_id );
		}
		module_cache::clear( 'job' );

		return $job_id;
	}

	public static function email_sent( $job_id, $template_name ) {
		// add sent date if it doesn't exist
		self::add_history( $job_id, _l( 'Job emailed to customer successfully' ) );
	}

	public static function staff_email_sent( $options ) {
		$job_id = (int) $options['job_id'];
		// add sent date if it doesn't exist
		self::add_history( $job_id, _l( 'Job emailed to staff successfully' ) );
	}

	public static function add_history( $job_id, $message ) {
		module_note::save_note( array(
			'owner_table' => 'job',
			'owner_id'    => $job_id,
			'note'        => $message,
			'rel_data'    => self::link_open( $job_id ),
			'note_time'   => time(),
		) );
	}

	// called whena  job task is saved. check if we send an email to the customer letting them know the tasj is completed.
	// todo: add a button to manually send job task email.
	public static function send_job_task_email( $job_id, $task_id, $reason ) {
		$return_messages = array();
		if ( module_config::c( 'job_send_staff_task_email_automatically', 0 ) && $reason == 'created' ) {
			// send the same emial as if going to job_admin_email_staff.php
			$task_data = self::get_task( $job_id, $task_id );
			$job_data  = self::get_job( $job_id );
			if ( $task_data['user_id'] > 0 && $task_data['user_id'] != module_security::get_loggedin_id() ) {
				$staff = module_user::get_user( $task_data['user_id'] );
				if ( $staff && $staff['user_id'] == $task_data['user_id'] && ! ( module_config::c( 'job_staff_email_skip_complete', 0 ) && $task_data['fully_completed'] ) ) {
					$template               = module_template::get_template_by_key( 'job_staff_email' );
					$job_data['job_name']   = $job_data['name'];
					$job_data['staff_name'] = $staff['name'];
					$job_data['job_url']    = module_job::link_open( $job_id );

					$job_data['job_tasks']  = '<ul>';
					$job_data['task_count'] = 0;
					//foreach($job_tasks as $job_task){
					$job_task = $task_data;
					//if($job_task['user_id']!=$staff_id)continue;
					//if(module_config::c('job_staff_email_skip_complete',0)&&$job_task['fully_completed'])continue;
					$job_data['job_tasks'] .= '<li><strong>' . $job_task['description'] . '</strong>';
					if ( $job_task['fully_completed'] ) {
						$job_data['job_tasks'] .= ' <span style="color: #99cc00; font-weight:bold;">(' . _l( 'complete' ) . ')</span>';
					}
					$job_data['job_tasks'] .= ' <br/>';
					if ( $job_task['long_description'] ) {
						$job_data['job_tasks'] .= _l( 'Notes:' ) . ' <em>' . $job_task['long_description'] . '</em><br/>';
					}
					if ( $job_task['date_due'] && $job_task['date_due'] != '0000-00-00' ) {
						$job_data['job_tasks'] .= _l( 'Date Due:' ) . ' ' . print_date( $job_task['date_due'] ) . '<br/>';
					}
					if ( $job_task['hours'] ) {
						$job_data['job_tasks'] .= _l( 'Assigned Hours:' ) . ' ' . $job_task['hours'] . '<br/>';
					}
					if ( $job_task['completed'] ) {
						$job_data['job_tasks'] .= _l( 'Completed Hours:' ) . ' ' . $job_task['completed'] . '<br/>';
					}
					$job_data['job_tasks'] .= '</li>';
					$job_data['task_count'] ++;
					//}
					$job_data['job_tasks'] .= '</ul>';

					// find available "to" recipients.
					// customer contacts.
					$to   = array();
					$to[] = array(
						'name'  => $staff['name'],
						'email' => $staff['email'],
					);
					$html = $template->render( 'html' );
					// send an email to this user.
					$email                 = module_email::new_email();
					$email->replace_values = $job_data;
					$email->set_to( 'user', $staff['user_id'] );
					$email->set_bcc_manual( module_config::c( 'admin_email_address', '' ), '' );
					//$email->set_from('user',); // nfi
					$email->set_subject( $template->description );
					// do we send images inline?
					$email->set_html( $html );
					$email->job_id = $job_id;

					$email->prevent_duplicates = true;

					if ( $email->send( false ) ) {

						self::add_history( $job_id, _l( 'Job task emailed to staff successfully' ) );
						$return_messages[] = _l( ' and email sent to staff %s', $staff['name'] );

					} else {
						/// log err?
					}

				}
			}
		}
		if ( module_config::c( 'job_send_task_completion_email_automatically', 0 ) && isset( $_POST['confirm_job_task_email'] ) ) {
			$task_data = self::get_task( $job_id, $task_id );
			$job_data  = self::get_job( $job_id );
			if ( $task_data['fully_completed'] && $job_data['customer_id'] ) {
				$template_name = 'job_task_completion_email';
				/*if(class_exists('module_company',false) && isset($invoice_data['company_id']) && (int)$invoice_data['company_id']>0){
					module_company::set_current_company_id($invoice_data['company_id']);
				}*/
				$template  = module_template::get_template_by_key( $template_name );
				$replace   = module_job::get_replace_fields( $job_id, $job_data );
				$to_select = false;
				if ( $job_data['customer_id'] ) {
					$customer                 = module_customer::get_customer( $job_data['customer_id'] );
					$replace['customer_name'] = $customer['customer_name'];
					$to                       = module_user::get_contacts( array( 'customer_id' => $job_data['customer_id'] ) );
					if ( $customer['primary_user_id'] ) {
						$primary = module_user::get_user( $customer['primary_user_id'] );
						if ( $primary ) {
							$to_select = $primary['email'];
						}
					}
				} else {
					$to = array();
				}
				$replace['job_name']         = $job_data['name'];
				$replace['task_description'] = $task_data['description'];

				$template->assign_values( $replace );
				$html = $template->render( 'html' );
				// send an email to this user.
				$email                 = module_email::new_email();
				$email->replace_values = $replace;
				// todo: send to all customer contacts ?
				if ( $to_select ) {
					$email->set_to_manual( $to_select );
				} else {
					foreach ( $to as $t ) {
						$email->set_to_manual( $t['email'] );
						break;// only 1? todo: all?
					}
				}
				$email->set_bcc_manual( module_config::c( 'admin_email_address', '' ), '' );
				//$email->set_from('user',); // nfi
				$email->set_subject( $template->description );
				// do we send images inline?
				$email->set_html( $html );
				$email->job_id      = $job_id;
				$email->customer_id = $job_data['customer_id'];

				$email->prevent_duplicates = true;

				if ( $email->send( false ) ) {
					// it worked successfully!!
					// record a log on the invoice when it's done.
					/*self::email_sent(array(
					'invoice_id' => $invoice_id,
					'template_name' => $template_name,
				));*/
					self::add_history( $job_id, _l( 'Job task emailed to customer successfully' ) );
					$return_messages[] = _l( ' and email sent to customer' );
				} else {
					// log err?
				}
			}
		}
		// if we are approving or rejecting job tasks with a message.
		if ( isset( $_POST['job_task'][ $task_id ]['approval_actioned'] ) && $_POST['job_task'][ $task_id ]['approval_actioned'] ) {
			$task_data = self::get_task( $job_id, $task_id );
			$job_data  = self::get_job( $job_id );
			if ( $task_data['user_id'] > 0 ) {
				$staff = module_user::get_user( $task_data['user_id'] );
				if ( $staff && $staff['user_id'] == $task_data['user_id'] && ! ( module_config::c( 'job_staff_email_skip_complete', 0 ) && $task_data['fully_completed'] ) ) {
					$template                         = module_template::get_template_by_key( 'job_task_approval' );
					$job_data['job_name']             = $job_data['name'];
					$job_data['staff_name']           = $staff['name'];
					$job_data['job_url']              = module_job::link_open( $job_id );
					$job_data['approved_or_rejected'] = $_POST['job_task'][ $task_id ]['approval_required'] == 2 ? _l( 'Rejected' ) : _l( 'Approved' );
					$job_data['message']              = isset( $_POST['job_task'][ $task_id ]['approval_message'] ) ? $_POST['job_task'][ $task_id ]['approval_message'] : _l( 'N/A' );

					$job_data['job_task']   = '<ul>';
					$job_data['task_count'] = 0;
					//foreach($job_tasks as $job_task){
					$job_task = $task_data;
					//if($job_task['user_id']!=$staff_id)continue;
					//if(module_config::c('job_staff_email_skip_complete',0)&&$job_task['fully_completed'])continue;
					$job_data['job_task'] .= '<li><strong>' . $job_task['description'] . '</strong>';
					if ( $job_task['fully_completed'] ) {
						$job_data['job_task'] .= ' <span style="color: #99cc00; font-weight:bold;">(' . _l( 'complete' ) . ')</span>';
					}
					$job_data['job_task'] .= ' <br/>';
					if ( $job_task['long_description'] ) {
						$job_data['job_task'] .= _l( 'Notes:' ) . ' <em>' . $job_task['long_description'] . '</em><br/>';
					}
					if ( $job_task['date_due'] && $job_task['date_due'] != '0000-00-00' ) {
						$job_data['job_task'] .= _l( 'Date Due:' ) . ' ' . print_date( $job_task['date_due'] ) . '<br/>';
					}
					if ( $job_task['hours'] ) {
						$job_data['job_task'] .= _l( 'Assigned Hours:' ) . ' ' . $job_task['hours'] . '<br/>';
					}
					if ( isset( $job_task['completed'] ) && $job_task['completed'] ) {
						$job_data['job_task'] .= _l( 'Completed Hours:' ) . ' ' . ( isset( $job_task['completed'] ) ? $job_task['completed'] : '' ) . '<br/>';
					}
					$job_data['job_task'] .= '</li>';
					$job_data['task_count'] ++;
					//}
					$job_data['job_task'] .= '</ul>';

					// find available "to" recipients.
					// customer contacts.
					$to   = array();
					$to[] = array(
						'name'  => $staff['name'],
						'email' => $staff['email'],
					);
					$template->assign_values( $job_data );
					$html = $template->render( 'html' );
					// send an email to this user.
					$email                 = module_email::new_email();
					$email->replace_values = $job_data;
					$email->set_to( 'user', $staff['user_id'] );
					$email->set_bcc_manual( module_config::c( 'admin_email_address', '' ), '' );
					//$email->set_from('user',); // nfi
					$email->set_subject( $template->description );
					// do we send images inline?
					$email->set_html( $html );
					$email->job_id = $job_id;

					$email->prevent_duplicates = true;

					if ( $email->send( false ) ) {

						self::add_history( $job_id, _l( 'Job task emailed to staff successfully' ) );
						$return_messages[] = _l( ' and email sent to staff %s', $staff['name'] );

					} else {
						/// log err?
					}

				}
			}
		}

		if ( count( $return_messages ) ) {
			return array(
				'message' => implode( ' ', $return_messages ),
			);
		}

		return false;
	}

	private static function save_job_tasks( $job_id, $data ) {

		$result          = array(
			'status' => false,
		);
		$check_completed = false;

		$job_data = false;

		$job_task_creation_permissions = self::get_job_task_creation_permissions();
		// check for new tasks or changed tasks.
		$tasks = self::get_tasks( $job_id );
		if ( isset( $data['job_task'] ) && is_array( $data['job_task'] ) ) {
			foreach ( $data['job_task'] as $task_id => $task_data ) {

				if ( isset( $task_data['manual_percent'] ) && strlen( $task_data['manual_percent'] ) == 0 ) {
					unset( $task_data['manual_percent'] );
				}

				$original_task_id = $task_id;
				$task_id          = (int) $task_id;
				if ( ! is_array( $task_data ) ) {
					continue;
				}
				if ( $task_id > 0 && ! isset( $tasks[ $task_id ] ) ) {
					$task_id = 0; // creating a new task on this job.
				}
				if ( ! isset( $task_data['description'] ) || $task_data['description'] == '' || $task_data['description'] == _TASK_DELETE_KEY ) {
					if ( $task_id > 0 && $task_data['description'] == _TASK_DELETE_KEY ) {
						// remove task.
						// but onyl remove it if it hasn't been invoiced.
						if ( isset( $tasks[ $task_id ] ) && $tasks[ $task_id ]['invoiced'] ) {
							// it has been invoiced! dont remove it.
							set_error( 'Unable to remove an invoiced task' );
							$result['status'] = 'error';
							break; // break out of loop saving tasks.
						} else {
							$sql = "DELETE FROM `" . _DB_PREFIX . "task` WHERE task_id = '$task_id' AND job_id = $job_id LIMIT 1";
							query( $sql );
							$sql = "DELETE FROM `" . _DB_PREFIX . "task_log` WHERE task_id = '$task_id'";
							query( $sql );
							$result['status']  = 'deleted';
							$result['task_id'] = $task_id;
						}
					}
					continue;
				}
				// add / save this task.
				$task_data['job_id'] = $job_id;
				if ( module_job::job_task_only_show_split_hours( $job_id, $job_data, $task_id, $task_data ) ) {
					if ( isset( $task_data['hours'] ) && ! isset( $task_data['staff_hours'] ) ) {
						$task_data['staff_hours']  = $task_data['hours'];
						$task_data['staff_amount'] = $task_data['amount'];
					}
					if ( isset( $task_data['hours'] ) ) {
						unset( $task_data['hours'] );
						unset( $task_data['amount'] );
					}
				}
				if ( isset( $task_data['hours'] ) ) {
					$task_data['hours'] = function_exists( 'decimal_time_in' ) ? decimal_time_in( $task_data['hours'] ) : $task_data['hours'];
				}
				if ( isset( $task_data['staff_hours'] ) ) {
					$task_data['staff_hours'] = function_exists( 'decimal_time_in' ) ? decimal_time_in( $task_data['staff_hours'] ) : $task_data['staff_hours'];
				}
				if ( isset( $task_data['log_hours'] ) ) {
					$task_data['log_hours'] = function_exists( 'decimal_time_in' ) ? decimal_time_in( $task_data['log_hours'] ) : $task_data['log_hours'];
				}
				// remove the amount of it equals the hourly rate.
				if ( isset( $task_data['amount'] ) && $task_data['amount'] != 0 && isset( $task_data['hours'] ) && $task_data['hours'] > 0 ) {
					if ( isset( $data['hourly_rate'] ) && ( $task_data['amount'] - ( $task_data['hours'] * $data['hourly_rate'] ) == 0 ) ) {
						unset( $task_data['amount'] );
					}
				}
				if ( isset( $task_data['staff_amount'] ) && $task_data['staff_amount'] != 0 && isset( $task_data['staff_hours'] ) && $task_data['staff_hours'] > 0 ) {
					if ( isset( $data['staff_hourly_rate'] ) && ( $task_data['staff_amount'] - ( $task_data['staff_hours'] * $data['staff_hourly_rate'] ) == 0 ) ) {
						unset( $task_data['staff_amount'] );
					}
				}
				// check if we haven't unticked a non-hourly task
				if ( isset( $task_data['fully_completed_t'] ) && $task_data['fully_completed_t'] ) {
					if ( ! isset( $task_data['fully_completed'] ) || ! $task_data['fully_completed'] ) {
						// we have unchecked that tickbox
						$task_data['fully_completed'] = 0;
					} else if ( isset( $tasks[ $task_id ] ) && ! $tasks[ $task_id ]['fully_completed'] ) {
						// we completed a preveiously incomplete task.

						// chekc if this task has a custom percentage filled in, we remove this custom percentage.
						if ( isset( $task_data['manual_percent'] ) && $task_data['manual_percent'] >= 0 ) {
							$task_data['manual_percent'] = - 1;
						}

						// hack: if we haven't logged any hours for this, we log the number of hours.
						// if we have logged some hours already then we don't log anything extra.
						// this is so they can log 0.5hours for a 1 hour completed task etc..
						if ( isset( $task_data['hours'] ) && $task_data['hours'] > 0 && ( ! isset( $task_data['log_hours'] ) || ! $task_data['log_hours'] ) ) {
							$logged_hours = 0;
							foreach ( get_multiple( 'task_log', array( 'job_id' => $job_id, 'task_id' => $task_id ) ) as $task_log ) {
								$logged_hours += $task_log['hours'];
							}
							if ( $logged_hours == 0 ) {
								$task_data['log_hours'] = $task_data['hours'];
							}
						}
					}
					$check_completed = true;
				}
				// check if we haven't unticked a billable task
				if ( isset( $task_data['billable_t'] ) && $task_data['billable_t'] && ! isset( $task_data['billable'] ) ) {
					$task_data['billable'] = 0;
				}
				// set default taxable status
				if ( ! $task_id && ! isset( $task_data['taxable_t'] ) ) {
					// we're creating a new task.
					$task_data['taxable'] = module_config::c( 'task_taxable_default', 1 );
				}
				if ( isset( $task_data['taxable_t'] ) && $task_data['taxable_t'] && ! isset( $task_data['taxable'] ) ) {
					$task_data['taxable'] = 0;
				}
				if ( isset( $task_data['completed'] ) && $task_data['completed'] > 0 ) {
					// check the completed date of all our tasks.
					$check_completed = true;
				}
				if ( ! $task_id && isset( $task_data['new_fully_completed'] ) && $task_data['new_fully_completed'] ) {
					$task_data['fully_completed'] = 1; // is this bad for set amount tasks?
					if ( ! isset( $task_data['date_done'] ) || ! $task_data['date_done'] ) {
						$task_data['date_done'] = print_date( time() );
					}
					if ( isset( $task_data['hours'] ) ) {
						$task_data['log_hours'] = $task_data['hours'];
					}
					$check_completed = true;
				}

				// todo: move the task creation code into a public method so that the public user can add tasks to their jobs.
				if ( ! $task_id && module_security::is_logged_in() && ! module_job::can_i( 'create', 'Job Tasks' ) ) {
					continue; // dont allow new tasks.
				}

				// check if the user is allowed to create new tasks.

				// check the approval status of jobs
				switch ( $job_task_creation_permissions ) {
					case _JOB_TASK_CREATION_NOT_ALLOWED:
						if ( ! $task_id ) {
							continue; // dont allow new tasks.
						}
						break;
					case _JOB_TASK_CREATION_REQUIRES_APPROVAL:
						$task_data['approval_required'] = 1;
						break;
					case _JOB_TASK_CREATION_WITHOUT_APPROVAL:
						// no action required .
						break;
				}
				if ( isset( $tasks[ $task_id ] ) && $tasks[ $task_id ]['approval_required'] == 2 ) {
					// task has been rejected, saving it again for approval.
					$task_data['approval_required'] = 1;

				}

				$task_id           = update_insert( 'task_id', $task_id, 'task', $task_data ); // todo - fix cross task job boundary issue. meh.
				$result['task_id'] = $task_id;
				if ( $task_id != $original_task_id ) {
					$result['status'] = 'created';
				} else {
					$result['status'] = 'edited';
				}

				if ( $task_id && isset( $task_data['log_hours'] ) && (float) $task_data['log_hours'] > 0 ) {
					// we are increasing the task complete hours by the amount specified in log hours.
					// log a new task record, and incrase the "completed" column.
					//$original_task_data = $tasks[$task_id];
					//$task_data['completed'] = $task_data['completed'] + $task_data['log_hours'];
					// only log hours if it's an hourly task.


					if ( ! isset( $task_data['manual_task_type'] ) || $task_data['manual_task_type'] < 0 ) {
						if ( ! $job_data ) {
							$job_data = self::get_job( $job_id );
						}
						$task_data['manual_task_type'] = $job_data['default_task_type'];
					}
					if ( $task_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {

						update_insert( 'task_log_id', 'new', 'task_log', array(
							'task_id'  => $task_id,
							'job_id'   => $job_id,
							'hours'    => (float) $task_data['log_hours'],
							'log_time' => time(),
						) );
						$result['log_hours'] = $task_data['log_hours'];
					}
				}
			}
		}

		if ( $check_completed ) {
			self::update_job_completion_status( $job_id );
		}
		module_cache::clear( 'job' );

		return $result;
	}

	public static function delete_job( $job_id ) {
		$job_id = (int) $job_id;
		if ( _DEMO_MODE && $job_id == 1 ) {
			return;
		}

		if ( (int) $job_id > 0 ) {
			$original_job_data = self::get_job( $job_id );
			if ( ! $original_job_data || $original_job_data['job_id'] != $job_id ) {
				return false;
			}
		}

		if ( ! self::can_i( 'delete', 'Jobs' ) ) {
			return false;
		}

		$sql = "DELETE FROM " . _DB_PREFIX . "job WHERE job_id = '" . $job_id . "' LIMIT 1";
		$res = query( $sql );
		$sql = "DELETE FROM " . _DB_PREFIX . "task WHERE job_id = '" . $job_id . "'";
		$res = query( $sql );
		$sql = "DELETE FROM " . _DB_PREFIX . "task_log WHERE job_id = '" . $job_id . "'";
		$res = query( $sql );
		$sql = "UPDATE " . _DB_PREFIX . "job SET renew_job_id = NULL WHERE renew_job_id = '" . $job_id . "'";
		$res = query( $sql );
		if ( class_exists( 'module_file', false ) ) {
			$sql = "UPDATE " . _DB_PREFIX . "file SET job_id = 0 WHERE job_id = '" . $job_id . "'";
			query( $sql );
		}

		if ( class_exists( 'module_group', false ) ) {
			module_group::delete_member( $job_id, 'job' );
		}
		foreach ( module_invoice::get_invoices( array( 'job_id' => $job_id ) ) as $val ) {
			// only delete this invoice if it has no tasks left
			// it could be a combined invoice with other jobs now.
			$invoice_items = module_invoice::get_invoice_items( $val['invoice_id'] );
			if ( ! count( $invoice_items ) ) {
				module_invoice::delete_invoice( $val['invoice_id'] );
			}

		}
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::note_delete( "job", $job_id );
		}
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			module_extra::delete_extras( 'job', 'job_id', $job_id );
		}

		hook_handle_callback( 'job_delete', $job_id );
		module_cache::clear( 'job' );
	}

	public function login_link( $job_id ) {
		return module_security::generate_auto_login_link( $job_id );
	}

	public static function get_statuses() {
		$sql      = "SELECT `status` FROM `" . _DB_PREFIX . "job` GROUP BY `status` ORDER BY `status`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['status'] ] = $r['status'];
		}

		return $statuses;
	}

	public static function get_types() {
		$sql      = "SELECT `type` FROM `" . _DB_PREFIX . "job` GROUP BY `type` ORDER BY `type`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['type'] ] = $r['type'];
		}

		return $statuses;
	}


	public static function customer_id_changed( $old_customer_id, $new_customer_id ) {
		$old_customer_id = (int) $old_customer_id;
		$new_customer_id = (int) $new_customer_id;
		if ( $old_customer_id > 0 && $new_customer_id > 0 ) {
			$sql = "UPDATE `" . _DB_PREFIX . "job` SET customer_id = " . $new_customer_id . " WHERE customer_id = " . $old_customer_id;
			query( $sql );
			module_invoice::customer_id_changed( $old_customer_id, $new_customer_id );
			module_file::customer_id_changed( $old_customer_id, $new_customer_id );
		}
	}

	public static function get_job_task_creation_permissions() {

		if ( ! module_security::is_logged_in() ) {
			//todo - option to allow guests to create tasks with approval? or not to create tasks at all.
			return _JOB_TASK_CREATION_REQUIRES_APPROVAL;
		} else if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Job Task Creation', array(
				_JOB_TASK_CREATION_WITHOUT_APPROVAL,
				_JOB_TASK_CREATION_REQUIRES_APPROVAL,
				_JOB_TASK_CREATION_NOT_ALLOWED,
			) );
		} else {
			return _JOB_TASK_CREATION_WITHOUT_APPROVAL; // default to all permissions.
		}
	}

	public static function get_job_access_permissions() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Job Data Access', array(
				_JOB_ACCESS_ALL,
				_JOB_ACCESS_ASSIGNED,
				_JOB_ACCESS_CUSTOMER,
			) );
		} else {
			return _JOB_ACCESS_ALL; // default to all permissions.
		}
	}

	public static function get_job_task_access_permissions() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Job Task Data Access', array(
				_JOB_TASK_ACCESS_ALL,
				_JOB_TASK_ACCESS_ASSIGNED_ONLY,
			) );
		} else {
			return _JOB_TASK_ACCESS_ALL; // default to all permissions.
		}
	}


	public static function handle_import_row_debug( $row, $add_to_group, $extra_options ) {
		return self::handle_import_row( $row, true, $add_to_group, $extra_options );
	}

	/* Job Title	Hourly Rate	Start Date	Due Date	Completed Date	Website Name	Customer Name	Type	Status	Staff Member	Tax Name	Tax Percent	Renewal Date */
	public static function handle_import_row( $row, $debug, $add_to_group, $extra_options ) {

		$debug_string = '';

		if ( isset( $row['job_id'] ) && (int) $row['job_id'] > 0 ) {
			// check if this ID exists.
			$job = self::get_job( $row['job_id'] );
			if ( ! $job || $job['job_id'] != $row['job_id'] ) {
				$row['job_id'] = 0;
			}
		}
		if ( ! isset( $row['job_id'] ) || ! $row['job_id'] ) {
			$row['job_id'] = 0;
		}
		if ( ! isset( $row['name'] ) || ! strlen( $row['name'] ) ) {
			$debug_string .= _l( 'No job data to import' );
			if ( $debug ) {
				echo $debug_string;
			}

			return false;
		}
		// duplicates.
		//print_r($extra_options);exit;
		if ( isset( $extra_options['duplicates'] ) && $extra_options['duplicates'] == 'ignore' && (int) $row['job_id'] > 0 ) {
			if ( $debug ) {
				$debug_string .= _l( 'Skipping import, duplicate of job %s', self::link_open( $row['job_id'], true ) );
				echo $debug_string;
			}

			// don't import duplicates
			return false;
		}
		$row['customer_id'] = 0; // todo - support importing of this id? nah
		if ( isset( $row['customer_name'] ) && strlen( trim( $row['customer_name'] ) ) > 0 ) {
			// check if this customer exists.
			$customer = get_single( 'customer', 'customer_name', $row['customer_name'] );
			if ( $customer && $customer['customer_id'] > 0 ) {
				$row['customer_id'] = $customer['customer_id'];
				$debug_string       .= _l( 'Linked to customer %s', module_customer::link_open( $row['customer_id'], true ) ) . ' ';
			} else {
				$debug_string .= _l( 'Create new customer: %s', htmlspecialchars( $row['customer_name'] ) ) . ' ';
			}
		} else {
			$debug_string .= _l( 'No customer' ) . ' ';
		}
		if ( $row['job_id'] ) {
			$debug_string .= _l( 'Replace existing job: %s', self::link_open( $row['job_id'], true ) ) . ' ';
		} else {
			$debug_string .= _l( 'Insert new job: %s', htmlspecialchars( $row['name'] ) ) . ' ';
		}

		if ( $debug ) {
			echo $debug_string;

			return true;
		}
		if ( isset( $extra_options['duplicates'] ) && $extra_options['duplicates'] == 'ignore' && $row['customer_id'] > 0 ) {
			// don't update customer record with new one.

		} else if ( ( isset( $row['customer_name'] ) && strlen( trim( $row['customer_name'] ) ) > 0 ) || $row['customer_id'] > 0 ) {
			// update customer record with new one.
			$row['customer_id'] = update_insert( 'customer_id', $row['customer_id'], 'customer', $row );

		}
		$job_id = (int) $row['job_id'];
		// check if this ID exists.
		$job = self::get_job( $job_id );
		if ( ! $job || $job['job_id'] != $job_id ) {
			$job_id = 0;
		}
		$job_id = update_insert( "job_id", $job_id, "job", $row );

		// handle any extra fields.
		$extra = array();
		foreach ( $row as $key => $val ) {
			if ( ! strlen( trim( $val ) ) ) {
				continue;
			}
			if ( strpos( $key, 'extra:' ) !== false ) {
				$extra_key = str_replace( 'extra:', '', $key );
				if ( strlen( $extra_key ) ) {
					$extra[ $extra_key ] = $val;
				}
			}
		}
		if ( $extra ) {
			foreach ( $extra as $extra_key => $extra_val ) {
				// does this one exist?
				$existing_extra = module_extra::get_extras( array(
					'owner_table' => 'job',
					'owner_id'    => $job_id,
					'extra_key'   => $extra_key
				) );
				$extra_id       = false;
				foreach ( $existing_extra as $key => $val ) {
					if ( $val['extra_key'] == $extra_key ) {
						$extra_id = $val['extra_id'];
					}
				}
				$extra_db = array(
					'extra_key'   => $extra_key,
					'extra'       => $extra_val,
					'owner_table' => 'job',
					'owner_id'    => $job_id,
				);
				$extra_id = (int) $extra_id;
				update_insert( 'extra_id', $extra_id, 'extra', $extra_db );
			}
		}

		foreach ( $add_to_group as $group_id => $tf ) {
			module_group::add_to_group( $group_id, $job_id, 'job' );
		}

		return $job_id;

	}

	public static function handle_import( $data, $add_to_group, $extra_options ) {

		// woo! we're doing an import.
		$count = 0;
		// first we find any matching existing jobs. skipping duplicates if option is set.
		foreach ( $data as $rowid => $row ) {
			if ( self::handle_import_row( $row, false, $add_to_group, $extra_options ) ) {
				$count ++;
			}
		}

		return $count;


	}

	public static function handle_import_tasks( $data, $add_to_group ) {

		$import_options = json_decode( base64_decode( $_REQUEST['import_options'] ), true );
		$job_id         = (int) $import_options['job_id'];
		if ( ! $import_options || ! is_array( $import_options ) || $job_id <= 0 ) {
			echo 'Sorry import failed. Please try again';
			exit;
		}
		$existing_tasks = self::get_tasks( $job_id );
		$existing_staff = module_user::get_staff_members();


		// woo! we're doing an import.
		// make sure we have a job id


		foreach ( $data as $rowid => $row ) {
			$row['job_id'] = $job_id;
			// check for required fields
			if ( ! isset( $row['description'] ) || ! trim( $row['description'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['task_id'] ) || ! $row['task_id'] ) {
				$data[ $rowid ]['task_id'] = 0;
			}
			// make sure this task id exists in the system against this job.
			if ( $data[ $rowid ]['task_id'] > 0 ) {
				if ( ! isset( $existing_tasks[ $data[ $rowid ]['task_id'] ] ) ) {
					$data[ $rowid ]['task_id'] = 0; // create a new task.
					// this stops them updating a task in another job.
				}
			}
			if ( ! $data[ $rowid ]['task_id'] && $row['description'] ) {
				// search for a task based on this name. dont want duplicates in the system.
				$existing_task = get_single( 'task', array( 'job_id', 'description' ), array( $job_id, $row['description'] ) );
				if ( $existing_task ) {
					$data[ $rowid ]['task_id'] = $existing_task['task_id'];
				}
			}

			// we have to save the user_name specially.
			/*if(isset($row['user_name']) && $row['user_name']){
                // see if this staff member exists.
                foreach($existing_staff as $staff_member){
                    if(strtolower($staff_member['name']) == strtolower($row['user_name'])){
                        $data[$rowid]['user_id'] = $staff_member['user_id'];
                    }
                }
            }*/

		}
		$c         = 0;
		$task_data = array();
		foreach ( $data as $rowid => $row ) {
			// now save the data.

			// we specify a "log_hours" value if we are logging more hours on a specific task.
			if ( isset( $row['completed'] ) && $row['completed'] > 0 && isset( $row['hours'] ) && $row['hours'] > 0 ) {
				if ( $row['task_id'] == 0 ) {
					// we are logging hours against a new task
					$row['log_hours'] = $row['completed'];
				} else if ( $row['task_id'] > 0 ) {
					// we are adjusting hours on an existing task.
					$existing_completed_hours = $existing_tasks[ $row['task_id'] ]['completed'];
					if ( $row['completed'] > $existing_completed_hours ) {
						// we are logging additional hours against the job.
						$row['log_hours'] = $row['completed'] - $existing_completed_hours;
					} else if ( $row['completed'] < $existing_completed_hours ) {
						// we are removing hours on this task!
						// tricky!!
						$sql = "DELETE FROM `" . _DB_PREFIX . "task_log` WHERE task_id = " . (int) $row['task_id'];
						query( $sql );
						$row['log_hours'] = $row['completed'];
					}
				}
			}

			if ( $row['task_id'] > 0 ) {
				$task_id = $row['task_id'];
			} else {
				$task_id = 'new' . $c . 'new';
				$c ++;
			}

			$task_data[ $task_id ] = $row;

			/*foreach($add_to_group as $group_id => $tf){
                module_group::add_to_group($group_id,$task_id,'task');
            }*/

		}

		self::save_job( $job_id, array(
			'job_id'   => $job_id,
			'job_task' => $task_data,
		) );


	}

	public static function generate_task_preview( $job_id, $job, $task_id, $task_data, $task_editable = true, $options = array() ) {

		$UCMJob     = UCMJob::singleton( $job_id );
		$UCMJobTask = $UCMJob->get_task( $task_id );

		ob_start();
		// can we edit this task?
		// if its been invoiced we cannot edit it.
		if ( $task_editable && $task_data['invoiced'] && module_config::c( 'job_task_lock_invoiced_items', 1 ) && $task_data['fully_completed'] ) {
			$task_editable = false;// don't allow editable invoiced tasks
		}

		// todo-move this into a method so we can update it via ajax.


		$percentage = self::get_percentage( $task_data );

		/*if($task_data['hours'] <= 0 && $task_data['fully_completed']){
            $percentage = 1;
        }else if ($task_data['completed'] > 0) {
            if($task_data['hours'] > 0){
                $percentage = round($task_data['completed'] / $task_data['hours'],2);
                $percentage = min(1,$percentage);
            }else{
                $percentage = 1;
            }
        }else{
            $percentage = 0;
        }*/

		$task_due_time = strtotime( $task_data['date_due'] );

		$show_task_numbers = ( module_config::c( 'job_show_task_numbers', 1 ) && $job['auto_task_numbers'] != 2 );


		$staff_members    = module_user::get_staff_members();
		$staff_member_rel = array();
		foreach ( $staff_members as $staff_member ) {
			$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
		}

		// hack to set the done_date if none exists.
		if ( $percentage >= 1 ) {
			if ( $task_data['task_id'] && isset( $task_data['date_done'] ) && ( ! $task_data['date_done'] || $task_data['date_done'] == '0000-00-00' ) ) {
				$task_logs = module_job::get_task_log( $task_id );
				$done_date = $task_data['date_updated'];
				foreach ( $task_logs as $task_log ) {
					if ( $task_log['log_time'] ) {
						$done_date = date( 'Y-m-d', $task_log['log_time'] );
					}
				}
				if ( $done_date ) {
					update_insert( 'task_id', $task_data['task_id'], 'task', array( 'date_done' => $done_date ) );
					$task_data['date_done'] = $done_date;
				}
			}
		} else {
			if ( $task_data['task_id'] && isset( $task_data['date_done'] ) && $task_data['date_done'] && $task_data['date_done'] != '0000-00-00' ) {
				$done_date = '0000-00-00';
				update_insert( 'task_id', $task_data['task_id'], 'task', array( 'date_done' => $done_date ) );
				$task_data['date_done'] = $done_date;
			}
		}

		// new different formats for job data.
		if ( ( ! isset( $task_data['manual_task_type'] ) || $task_data['manual_task_type'] < 0 ) && isset( $job['default_task_type'] ) ) {
			// use the job task type
			$task_data['manual_task_type'] = $job['default_task_type'];
			// if this task has been invoiced then we lock the manual_task_type to wahtever the job default currently is
			// this helps with the upgrade.
			if ( $task_data['invoiced'] && $task_data['invoice_id'] ) {
				update_insert( 'task_id', $task_data['task_id'], 'task', array( 'manual_task_type' => $job['default_task_type'] ) );
			}
		}
		if ( is_callable( 'module_product::sanitise_product_name' ) ) {
			$task_data = module_product::sanitise_product_name( $task_data, $job['default_task_type'] );
		}


		include( 'pages/ajax_task_preview.php' );

		return ob_get_clean();
	}

	public static function get_default_tasks() {
		// we use the extra module for saving default task lists for now
		// why not? meh - use a new table later (similar to ticket default responses)
		$extra_fields = module_extra::get_extras( array( 'owner_table' => 'job_task_defaults', 'owner_id' => 1 ) );
		$responses    = array();
		foreach ( $extra_fields as $extra ) {
			$responses[ $extra['extra_id'] ] = $extra['extra_key'];
		}

		return $responses;
	}

	public static function get_default_task( $default_task_list_id ) {
		$extra = module_extra::get_extra( $default_task_list_id );

		return array(
			'default_task_list_id' => $extra['extra_id'],
			'name'                 => $extra['extra_key'],
			'task_data'            => unserialize( $extra['extra'] ),
		);
	}

	public static function save_default_tasks( $default_task_list_id, $name, $task_data ) {
		if ( (int) $default_task_list_id > 0 && ! count( $task_data ) ) {
			// deleting a task.
			delete_from_db( 'extra', array( 'extra_id', 'owner_table' ), array(
				$default_task_list_id,
				'job_task_defaults'
			) );

			return false;
		} else {
			$extra_db = array(
				'extra'       => serialize( $task_data ),
				'owner_table' => 'job_task_defaults',
				'owner_id'    => 1,
			);
			if ( ! (int) $default_task_list_id ) {
				$extra_db['extra_key'] = $name; // don't update names of previous ones.
			}
			$extra_id = update_insert( 'extra_id', $default_task_list_id, 'extra', $extra_db );

			return $extra_id;
		}
	}

	public static function get_percentage( $task_data ) {

		if ( ! $task_data['task_id'] ) {
			return 0;
		}
		$percentage = 0;
		if ( isset( $task_data['manual_percent'] ) && $task_data['manual_percent'] >= 0 ) {
			return $task_data['manual_percent'] / 100; // manual percent is stored like 40, instead of 0.4
		}
		if ( module_config::c( 'job_task_log_all_hours', 1 ) ) {
			if ( $task_data['fully_completed'] ) {
				$percentage = 1;
			} else {
				// work out percentage based on hours.
				// default to 99% if not fully_completed is ticked yet.
				if ( $task_data['completed'] > 0 ) {
					if ( $task_data['hours'] > 0 ) {
						$percentage = round( $task_data['completed'] / $task_data['hours'], 2 );
						$percentage = min( 1, $percentage );
					}
				}
				if ( $percentage >= 1 ) {
					// hack for invoiced tasks. mark this as fully completed.
					if ( $task_data['invoiced'] ) {
						update_insert( 'task_id', $task_data['task_id'], 'task', array( 'fully_completed' => 1 ) );
						$percentage = 1;
					} else {
						$percentage = 0.99;
					}
				}
			}
		} else {
			if ( $task_data['hours'] <= 0 && $task_data['fully_completed'] ) {
				$percentage = 1;
			} else if ( $task_data['completed'] > 0 ) {
				if ( $task_data['hours'] > 0 ) {
					$percentage = round( $task_data['completed'] / $task_data['hours'], 2 );
					$percentage = min( 1, $percentage );
				} else {
					$percentage = 1;
				}
			}
		}

		return $percentage;
	}

	public static function generate_job_summary( $job_id, $job, $show_hours_summary = true ) {
		$show_task_numbers = ( module_config::c( 'job_show_task_numbers', 1 ) && $job['auto_task_numbers'] != 2 );
		ob_start();
		include( module_theme::include_ucm( 'includes/plugin_job/pages/ajax_job_summary.php' ) );

		return ob_get_clean();
	}

	private static function save_job_cache( $job_id, $data = array() ) {
		if ( ! $data ) {
			$data = self::get_job( $job_id );
		}
		if ( $job_id > 0 && $data['job_id'] == $job_id ) {
			/*
			'total_percent_complete', // in database as total_percent_complete
			'total_amount_invoicable', // in datbase as c_total_amount_invoicable
			'staff_total_amount', // in datbase as c_staff_total_amount
			'total_net_amount', // in datbase as c_total_net_amount
			'staff_total_grouped', // in datbase as c_staff_total_grouped
			*/
			update_insert( 'job_id', $job_id, 'job', array(
				'total_percent_complete'    => $data['total_percent_complete'],
				"c_total_amount_invoicable" => $data['total_amount_invoicable'],
				"c_staff_total_amount"      => $data['staff_total_amount'],
				"c_total_net_amount"        => $data['total_net_amount'],
				"c_staff_total_grouped"     => serialize( $data['staff_total_grouped'] ),
			) );
		}

		return $data;
	}

	public static function update_job_completion_status( $job_id ) {
		module_cache::clear( 'job' );
		//module_cache::clear_cache();
		$data          = self::save_job_cache( $job_id );
		$return_status = $data['status'];
		$tasks         = self::get_tasks( $job_id );
		$all_completed = ( count( $tasks ) > 0 );
		foreach ( $tasks as $task ) {
			if (
				(
					// tasks have to have a 'fully_completed' before they are done.
					module_config::c( 'job_task_log_all_hours', 1 ) && $task['fully_completed']
				)
				||
				(
					! module_config::c( 'job_task_log_all_hours', 1 ) &&
					(
						$task['fully_completed']
						||
						( $task['hours'] > 0 && ( $task['completed'] >= $task['hours'] ) )
						||
						( $task['hours'] <= 0 && $task['completed'] > 0 )
					)
				)
			) {
				// this one is done!
			} else {
				$all_completed = false;
				break;
			}
		}
		if ( $all_completed ) {
			if ( ! isset( $data['date_completed'] ) || ! $data['date_completed'] || $data['date_completed'] == '0000-00-00' ) {
				// update, dont complete if no tasks.
				//if(count($tasks)){
				$return_status = ( $data['status'] == module_config::s( 'job_status_default', 'New' ) ? _l( 'Completed' ) : $data['status'] );
				update_insert( "job_id", $job_id, "job", array(
					'date_completed' => date( 'Y-m-d' ),
					'status'         => $return_status,
				) );
				//}
			}
		} else {
			// not completed. remove compelted date and reset the job status
			$return_status = ( $data['status'] == _l( 'Completed' ) ? module_config::s( 'job_status_default', 'New' ) : $data['status'] );
			update_insert( "job_id", $job_id, "job", array(
				'date_completed' => '0000-00-00',
				'status'         => $return_status, //module_config::s('job_status_default','New'),
			) );
		}
		module_cache::clear( 'job' );

		return $return_status;
	}

	public function run_cron( $debug = false ) {

		// we only want to perform these cron actions if we're after a certain time of day
		// because we dont want to be generating these renewals and sending them at midnight, can get confusing
		$after_time  = module_config::c( 'invoice_automatic_after_time', 7 );
		$time_of_day = date( 'G' );
		if ( $time_of_day < $after_time ) {
			if ( $debug ) {
				echo "Not performing automatic invoice operations until after $after_time:00 - it is currently $time_of_day:" . date( 'i' ) . "<br>\n";
			}

			return;
		}

		// find automatic job renewals
		$sql        = "SELECT p.* FROM `" . _DB_PREFIX . "job` p ";
		$sql        .= " WHERE p.date_renew != '0000-00-00'";
		$sql        .= " AND p.date_start != '0000-00-00'";
		$sql        .= " AND p.date_renew <= '" . date( 'Y-m-d' ) . "'";
		$sql        .= " AND (p.renew_job_id IS NULL OR p.renew_job_id = 0)";
		$sql        .= " AND (p.renew_auto = 1)";
		$renew_jobs = qa( $sql );
		foreach ( $renew_jobs as $renew_job ) {
			// time to automatically renew this job! woo!
			if ( $debug ) {
				echo "Automatically Renewing Job " . module_job::link_open( $renew_job['job_id'], true ) . "<br>\n";
			}
			//$job_details = $this->get_job($renew_job['job_id']);
			$job_invoices   = module_invoice::get_invoices( array( 'job_id' => $renew_job['job_id'] ) );
			$unpaid_invoice = false;
			foreach ( $job_invoices as $job_invoice ) {
				$job_invoice = module_invoice::get_invoice( $job_invoice['invoice_id'] );
				if ( $job_invoice['total_amount_due'] > 0 ) {
					$unpaid_invoice = true;
				}
			}
			if ( module_config::c( 'invoice_auto_renew_only_paid_invoices', 1 ) && $unpaid_invoice ) {
				if ( $debug ) {
					echo "Not automatically renewing this job because it has unpaid invoices. <br>\n";
				}
			} else {
				$new_job_id = $this->renew_job( $renew_job['job_id'], true );
				if ( $new_job_id ) {
					//module_cache::clear_cache();
					if ( $debug ) {
						echo "Job Automatically Renewed: " . module_job::link_open( $new_job_id, true ) . "<br>\n";
					}
					if ( $renew_job['renew_invoice'] ) {
						// we want to tick all these tasks off and invoice this job, then send this invoice to the customer.
						$job_tasks = module_job::get_tasks( $new_job_id );
						foreach ( $job_tasks as $job_task_id => $job_task ) {
							$job_tasks[ $job_task_id ]['fully_completed_t'] = 1;
							$job_tasks[ $job_task_id ]['fully_completed']   = 1;
						}
						$this->save_job_tasks( $new_job_id, array( 'job_task' => $job_tasks ) );
						//module_cache::clear_cache();
						// generate an invoice for this job.

						$_REQUEST['job_id']                  = $new_job_id;
						$new_invoice                         = module_invoice::get_invoice( 'new' );
						$new_invoice['date_create']          = $renew_job['date_renew'];
						$new_invoice['invoice_invoice_item'] = module_invoice::get_invoice_items( 'new', $new_invoice );
						$new_invoice_id                      = module_invoice::save_invoice( 'new', $new_invoice );
						//module_cache::clear_cache();
						if ( $debug ) {
							echo "Generated new invoice for renewed job: " . module_invoice::link_open( $new_invoice_id, true ) . "<br/>";
						}
						if ( $debug ) {
							echo "Emailing invoice to customer...";
						}

						if ( module_invoice::email_invoice_to_customer( $new_invoice_id ) ) {
							if ( $debug ) {
								echo "send successfully";
							}
						} else {
							if ( $debug ) {
								echo "send failed";
							}
						}
						if ( $debug ) {
							echo "<br>\n";
						}

					}
				}
			}
		}

	}


	public static function hook_filter_var_job_list( $call, $attributes ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}
		foreach (
			module_job::get_jobs( array(
				'customer_id' => isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false,
			), array( 'columns' => 'u.job_id, u.name' ) ) as $job
		) {
			$attributes[ $job['job_id'] ] = $job['name'];
		}

		return $attributes;
	}

	public static function hook_filter_custom_data_menu_locations( $call, $menu_locations ) {
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_JOB_FOOTER ]  = _l( 'Job Footer' );
		$menu_locations[ _CUSTOM_DATA_HOOK_LOCATION_JOB_SIDEBAR ] = _l( 'Job Sidebar' );

		return $menu_locations;
	}

	public static function hook_filter_var_header_buttons( $callback, $header_buttons ) {

		if ( self::can_i( 'view', 'Jobs' ) ) {
			// todo - cache?
			$drop_down_tasks = array();
			$todo_list       = module_job::get_tasks_todo();
			$x               = 0;
			if ( count( $todo_list ) > 0 ) {
				foreach ( $todo_list as $todo_item ) {
					if ( $todo_item['hours_completed'] > 0 ) {
						if ( $todo_item['hours'] > 0 ) {
							$percentage = round( $todo_item['hours_completed'] / $todo_item['hours'], 2 );
							$percentage = min( 1, $percentage );
						} else {
							$percentage = 1;
						}
					} else {
						$percentage = 0;
					}
					$job_data = module_job::get_job( $todo_item['job_id'], false );
					if ( $job_data && $job_data['job_id'] == $todo_item['job_id'] ) {
						if ( $job_data['customer_id'] ) {
							$customer_data = module_customer::get_customer( $job_data['customer_id'] );
							if ( ! $customer_data || $customer_data['customer_id'] != $job_data['customer_id'] ) {
								continue;
							}
						} else {
							$customer_data = array();
						}
					}
					$drop_down_tasks[] = array(
						'link'            => module_job::link_open( $todo_item['job_id'], false, $job_data ),
						'title'           => isset( $customer_data['customer_name'] ) ? $customer_data['customer_name'] : '',
						'description'     => isset( $todo_item['description'] ) ? $todo_item['description'] : '',
						'sub-description' => round( $percentage * 100 ) . '%',
						'percentage'      => $percentage,
					);
				}

				if ( count( $drop_down_tasks ) ) {

					$header_buttons['jobs-todo'] = array(
						'fa-icon'  => 'tasks',
						'title'    => 'Tasks',
						'id'       => 'header_job_tasks',
						'header'   => _l( 'You have %s tasks', count( $drop_down_tasks ) ),
						'footer'   => '<a href="' . module_job::link_open( false ) . '">' . _l( 'View All Jobs' ) . '</a>',
						'dropdown' => $drop_down_tasks,
					);
				}
			}
		}

		return $header_buttons;
	}

	public static function is_staff_view( $job_data ) {
		return module_job::can_i( 'edit', 'Job Tasks' ) && $job_data['staff_total_amount'] > 0 && ! module_job::can_i( 'view', 'Job Split Pricing' );
	}


	public static function customer_archived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'job` SET `archived` = 1 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}

	public static function customer_unarchived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'job` SET `archived` = 0 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}


	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_job::can_i( 'view', 'Jobs' ) ) {
			$search_array = array(
				'generic' => $search_string,
			);
			if ( ! empty( $_REQUEST['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['customer_id'];
			}
			if ( ! empty( $_REQUEST['vars']['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['vars']['customer_id'];
			}
			$res = module_job::get_jobs( $search_array );
			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['job_id'],
					'value' => $row['name']
				);
			}
		}

		return $result;
	}

	public function get_upgrade_sql() {
		$sql = '';


		/*$installed_version = (string)$installed_version;
        $new_version = (string)$new_version;
        $options = array(
            '2' => array(
                '2.1' =>   'ALTER TABLE  `'._DB_PREFIX.'task` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;' .
                    'ALTER TABLE  `'._DB_PREFIX.'task_log` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;',
                '2.2' =>   'ALTER TABLE  `'._DB_PREFIX.'task` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;' .
                    'ALTER TABLE  `'._DB_PREFIX.'task_log` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;' .
                    'ALTER TABLE  `'._DB_PREFIX.'invoice` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;',
            ),
            '2.1' => array(
                '2.2' =>   'ALTER TABLE  `'._DB_PREFIX.'invoice` CHANGE  `project_id`  `job_id` INT( 11 ) NOT NULL;',
            ),

        );
        if(isset($options[$installed_version]) && isset($options[$installed_version][$new_version])){
            $sql = $options[$installed_version][$new_version];
        }*/


		$fields = get_fields( 'job' );
		if ( ! isset( $fields['auto_task_numbers'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['job_discussion'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `job_discussion` TINYINT( 1 ) NOT NULL DEFAULT  \'0\' AFTER `auto_task_numbers`;';
		}
		if ( ! isset( $fields['currency_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `currency_id` int(11) NOT NULL DEFAULT  \'1\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['quote_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `quote_id` int(11) NOT NULL DEFAULT  \'0\' AFTER  `website_id`;';
		}
		if ( ! isset( $fields['default_task_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `default_task_type` int(3) NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['date_quote'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `date_quote` date NOT NULL AFTER `total_tax_rate`;';
			$sql .= 'UPDATE `' . _DB_PREFIX . 'job` SET `date_quote` = `date_created`;';
		}
		if ( ! isset( $fields['renew_auto'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `renew_auto` TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `currency_id`;';
		}
		if ( ! isset( $fields['renew_invoice'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `renew_invoice` TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `renew_auto`;';
		}
		if ( ! isset( $fields['total_percent_complete'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `total_percent_complete` DECIMAL(6,4) NOT NULL DEFAULT \'0\' AFTER `renew_invoice`;';
		}
		if ( ! isset( $fields['total_percent_complete_manual'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `total_percent_complete_manual` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `total_percent_complete`;';
		}
		if ( ! isset( $fields['c_total_amount_invoicable'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `c_total_amount_invoicable` DECIMAL( 10,2 ) NOT NULL DEFAULT  \'-1\' AFTER `total_percent_complete`;';
		}
		if ( ! isset( $fields['c_staff_total_amount'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `c_staff_total_amount` DECIMAL( 10,2 ) NOT NULL DEFAULT  \'-1\' AFTER `c_total_amount_invoicable`;';
		}
		if ( ! isset( $fields['c_total_net_amount'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `c_total_net_amount` DECIMAL( 10,2 ) NOT NULL DEFAULT  \'-1\' AFTER `c_staff_total_amount`;';
		}
		if ( ! isset( $fields['c_staff_total_grouped'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `c_staff_total_grouped` TEXT NOT NULL DEFAULT  \'\' AFTER `c_total_net_amount`;';
		}
		if ( ! isset( $fields['description'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD  `description` TEXT NOT NULL DEFAULT  \'\' AFTER `c_total_net_amount`;';
		}
		if ( ! isset( $fields['discount_amount'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT \'0\' AFTER `description`;';
		}
		if ( ! isset( $fields['discount_description'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `discount_description` varchar(255) NOT NULL DEFAULT \'\' AFTER `discount_amount`;';
		}
		if ( ! isset( $fields['discount_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `discount_type` INT NOT NULL DEFAULT \'0\' AFTER `discount_description`;';
		}
		if ( ! isset( $fields['time_start'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `time_start` varchar(10) NOT NULL DEFAULT \'\' AFTER `date_start`;';
		}
		if ( ! isset( $fields['time_end'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `time_end` varchar(10) NOT NULL DEFAULT \'\' AFTER `time_start`;';
		}
		if ( ! isset( $fields['archived'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `archived` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `time_end`;';
		}
		if ( ! isset( $fields['billing_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `billing_type` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `archived`;';
		}


		if ( ! isset( $fields['job_template_print'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'job` ADD `job_template_print` varchar(50) NOT NULL DEFAULT \'\' AFTER `billing_type`;';
		}
		if ( ! isset( $fields['job_template_email'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'job` ADD `job_template_email` varchar(50) NOT NULL DEFAULT \'\' AFTER `job_template_print`;';
		}
		if ( ! isset( $fields['job_template_external'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'job` ADD `job_template_external` varchar(50) NOT NULL DEFAULT \'\' AFTER `job_template_email`;';
		}
		if ( ! isset( $fields['calendar_show'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'job` ADD  `calendar_show` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `job_template_external`;';
		}
		if ( ! isset( $fields['contact_user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'job` ADD `contact_user_id` INT NOT NULL DEFAULT \'-1\' AFTER `user_id`;';
		}


		$fields = get_fields( 'task' );
		if ( ! isset( $fields['long_description'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD `long_description` LONGTEXT NULL;';
		}
		if ( ! isset( $fields['task_order'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `task_order` int(11) NOT NULL DEFAULT  \'0\' AFTER `approval_required`;';
		}
		if ( ! isset( $fields['date_done'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `date_done` date NOT NULL AFTER `date_due`;';
		}
		if ( ! isset( $fields['taxable'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `taxable` tinyint(1) NOT NULL DEFAULT \'1\' AFTER `amount`;';
		}
		if ( ! isset( $fields['manual_percent'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `manual_percent` int(4) NOT NULL DEFAULT \'-1\' AFTER `taxable`;';
		}
		if ( ! isset( $fields['manual_task_type'] ) ) { // if -1 then we use job default_task_type
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `manual_task_type` tinyint(2) NOT NULL DEFAULT \'-1\' AFTER `manual_percent`;';
		}
		if ( ! isset( $fields['hours_mins'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `hours_mins`  DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `hours`;';
		}
		if ( ! isset( $fields['product_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `product_id`  INT NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		/*
    `billable` tinyint(2) NOT NULL DEFAULT '1',
    `staff_hours` decimal(10,2) NOT NULL DEFAULT '0',
    `staff_hours_mins` decimal(10,2) NOT NULL DEFAULT '0',
    `staff_amount` decimal(10,2) NOT NULL DEFAULT '0',
    `staff_taxable` tinyint(1) NOT NULL DEFAULT '1',
    `staff_billable` tinyint(2) NOT NULL DEFAULT '1',
    */
		if ( ! isset( $fields['staff_split'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_split` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER  `billable`;';
		}
		if ( ! isset( $fields['staff_hours'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_hours`  DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `staff_split`;';
		}
		if ( ! isset( $fields['staff_hours_mins'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_hours_mins`  DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `staff_hours`;';
		}
		if ( ! isset( $fields['staff_amount'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_amount`  DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `staff_hours_mins`;';
		}
		if ( ! isset( $fields['staff_taxable'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_taxable`  tinyint(1)  NOT NULL DEFAULT  \'1\' AFTER  `staff_amount`;';
		}
		if ( ! isset( $fields['staff_billable'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'task` ADD  `staff_billable`  tinyint(2)  NOT NULL DEFAULT  \'1\' AFTER  `staff_taxable`;';
		}

		/*if(!isset($fields['task_type'])){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'task` ADD  `task_type` tinyint(2) NOT NULL DEFAULT \'0\' AFTER `task_order`;';
        }*/

		if ( ! self::db_table_exists( 'job_tax' ) ) {
			$sql .= "CREATE TABLE `" . _DB_PREFIX . "job_tax` (
    `job_tax_id` int(11) NOT NULL AUTO_INCREMENT,
    `job_id` int(11) NOT NULL,
    `percent` decimal(10,2) NOT NULL DEFAULT  '0',
    `amount` decimal(10,2) NOT NULL DEFAULT  '0',
    `name` varchar(50) NOT NULL DEFAULT  '',
    `order` INT( 4 ) NOT NULL DEFAULT  '0',
    `increment` TINYINT( 1 ) NOT NULL DEFAULT  '0',
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY (`job_tax_id`),
    KEY (`job_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}

		self::add_table_index( 'job', 'customer_id' );
		self::add_table_index( 'job', 'user_id' );
		self::add_table_index( 'job', 'website_id' );
		self::add_table_index( 'job', 'quote_id' );
		self::add_table_index( 'job', 'archived' );
		self::add_table_index( 'task', 'job_id' );
		self::add_table_index( 'task', 'user_id' );
		self::add_table_index( 'task', 'invoice_id' );
		self::add_table_index( 'task', 'product_id' );
		self::add_table_index( 'task_log', 'task_id' );
		self::add_table_index( 'task_log', 'job_Id' );

		$bug_check = "SELECT * FROM `" . _DB_PREFIX . "job` WHERE (customer_id IS NULL OR customer_id = 0) AND (website_id IS NULL OR website_id = 0)  AND `name` = '' AND `type` = '' AND `date_start` = '0000-00-00' AND c_total_net_amount = 0 AND `user_id` = 0 AND (`discount_description` = '' OR `discount_description` IS NULL)";
		$count     = qa( $bug_check );
		if ( count( $count ) ) {
			$sql .= "DELETE FROM `" . _DB_PREFIX . "job` WHERE (customer_id IS NULL OR customer_id = 0) AND (website_id IS NULL OR website_id = 0)  AND `name` = '' AND `type` = '' AND `date_start` = '0000-00-00' AND c_total_net_amount = 0 AND `user_id` = 0 AND (`discount_description` = '' OR `discount_description` IS NULL);";
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>job` (
		`job_id` int(11) NOT NULL auto_increment,
		`customer_id` INT(11)  NOT NULL DEFAULT '0',
		`website_id` INT(11)  NOT NULL DEFAULT '0',
		`quote_id` INT(11) NOT NULL DEFAULT '0',
		`hourly_rate` DECIMAL(10,2) NULL,
		`name` varchar(255) NOT NULL DEFAULT  '',
		`type` varchar(255) NOT NULL DEFAULT  '',
		`status` varchar(255) NOT NULL DEFAULT  '',
		`total_tax_name` varchar(20) NOT NULL DEFAULT  '',
		`total_tax_rate` DECIMAL(10,2) NULL,
		`date_quote` date NOT NULL,
		`date_start` date NOT NULL,
		`time_start` varchar(10) NOT NULL DEFAULT '',
		`time_end` varchar(10) NOT NULL DEFAULT '',
		`date_due` date NOT NULL,
		`date_done` date NOT NULL,
		`date_completed` date NOT NULL,
		`date_renew` date NOT NULL,
		`renew_job_id` INT(11) NULL,
		`user_id` INT NOT NULL DEFAULT  '0',
		`default_task_type` int(3) NOT NULL DEFAULT  '0',
		`auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`job_discussion` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`currency_id` INT NOT NULL DEFAULT  '1',
		`renew_auto` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`renew_invoice` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`total_percent_complete` DECIMAL( 6,4 ) NOT NULL DEFAULT  '0',
		`total_percent_complete_manual` tinyint(1) NOT NULL DEFAULT  '0',
		`c_total_amount_invoicable` DECIMAL( 10,2 ) NOT NULL DEFAULT  '-1',
		`c_staff_total_amount` DECIMAL( 10,2 ) NOT NULL DEFAULT  '-1',
		`c_total_net_amount` DECIMAL( 10,2 ) NOT NULL DEFAULT  '-1',
		`c_staff_total_grouped` TEXT NOT NULL DEFAULT  '',
		`description` TEXT NOT NULL DEFAULT  '',
		`discount_amount` DECIMAL(10,2) NULL,
		`discount_description` varchar(255) NULL,
		`discount_type` INT NOT NULL DEFAULT '0',
		`archived` tinyint(1) NOT NULL DEFAULT '0',
		`billing_type` tinyint(1) NOT NULL DEFAULT '0',
		`job_template_print` varchar(50) NOT NULL DEFAULT '',
		`job_template_email` varchar(50) NOT NULL DEFAULT '',
		`job_template_external` varchar(50) NOT NULL DEFAULT '',
		`calendar_show` tinyint(1) NOT NULL DEFAULT  '0',
		`contact_user_id` int(11) NOT NULL DEFAULT  '-1',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`job_id`),
		KEY `customer_id` (`customer_id`),
		KEY `user_id` (`user_id`),
		KEY `quote_id` (`quote_id`),
		KEY `c_staff_total_amount` (`c_staff_total_amount`),
		KEY `c_total_net_amount` (`c_total_net_amount`),
		KEY `website_id` (`website_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE `<?php echo _DB_PREFIX; ?>job_tax` (
		`job_tax_id` int(11) NOT NULL AUTO_INCREMENT,
		`job_id` int(11) NOT NULL,
		`percent` decimal(10,2) NOT NULL DEFAULT  '0',
		`amount` decimal(10,2) NOT NULL DEFAULT  '0',
		`name` varchar(50) NOT NULL DEFAULT  '',
		`order` INT( 4 ) NOT NULL DEFAULT  '0',
		`increment` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`job_tax_id`),
		KEY (`job_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE `<?php echo _DB_PREFIX; ?>task` (
		`task_id` int(11) NOT NULL AUTO_INCREMENT,
		`job_id` int(11) NULL,
		`hours` decimal(10,2) NOT NULL DEFAULT '0',
		`hours_mins` decimal(10,2) NOT NULL DEFAULT '0',
		`amount` decimal(10,2) NOT NULL DEFAULT '0',
		`taxable` tinyint(1) NOT NULL DEFAULT '1',
		`billable` tinyint(2) NOT NULL DEFAULT '1',
		`staff_split` tinyint(1) NOT NULL DEFAULT '0',
		`staff_hours` decimal(10,2) NOT NULL DEFAULT '0',
		`staff_hours_mins` decimal(10,2) NOT NULL DEFAULT '0',
		`staff_amount` decimal(10,2) NOT NULL DEFAULT '0',
		`staff_taxable` tinyint(1) NOT NULL DEFAULT '1',
		`staff_billable` tinyint(2) NOT NULL DEFAULT '1',
		`fully_completed` tinyint(2) NOT NULL DEFAULT '0',
		`description` text NULL,
		`long_description` LONGTEXT NULL,
		`date_due` date NOT NULL,
		`date_done` date NOT NULL,
		`manual_percent` int(4) NOT NULL DEFAULT '-1',
		`manual_task_type` tinyint(2) NOT NULL DEFAULT '-1',
		`invoice_id` int(11) NULL,
		`user_id` INT NOT NULL DEFAULT  '0',
		`product_id` INT NOT NULL DEFAULT  '0',
		`approval_required` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`task_order` INT NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`task_id`),
		KEY `job_id` (`job_id`),
		KEY `user_id` (`user_id`),
		KEY `product_id` (`product_id`),
		KEY `invoice_id` (`invoice_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>task_log` (
		`task_log_id` int(11) NOT NULL AUTO_INCREMENT,
		`task_id` int(11) NOT NULL,
		`job_id` int(11) NOT NULL,
		`hours` decimal(10,2) NOT NULL DEFAULT '0',
		`log_time` int(11) NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`task_log_id`),
		KEY `task_id` (`task_id`),
		KEY `job_id` (`job_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php
		// todo: add default admin permissions.

		// `task_type` tinyint(2) NOT NULL DEFAULT  '0',

		return ob_get_clean();
	}

}

include_once 'class.job.php';