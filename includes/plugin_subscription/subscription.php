<?php


class module_subscription extends module_base {

	public $links;
	public $subscription_types;
	public $subscription_id;


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->links              = array();
		$this->subscription_types = array();
		$this->module_name        = "subscription";
		$this->module_position    = 30;
		$this->version            = 2.204;
		// 2.204 - 2017-02-26 - add website details to subscription
		// 2.203 - 2017-01-04 - subscription pdf template customer option
		// 2.202 - 2016-12-01 - ajax lookup for subscribers
		// 2.201 - 2016-11-23 - ajax lookup for subscribers
		// 2.200 - 2016-11-07 - ajax lookup for subscribers
		// 2.199 - 2016-10-18 - sending invoice fix
		// 2.198 - 2016-09-12 - merged subscription invoices
		// 2.197 - 2016-07-10 - big update to mysqli
		// 2.196 - 2015-07-29 - currency bug fix
		// 2.195 - 2015-06-08 - quick settings link
		// 2.194 - 2015-05-03 - responsive improvements
		// 2.193 - 2015-03-24 - blank invoice template fix
		// 2.192 - 2015-02-24 - subscription email/pdf template suffix
		// 2.191 - 2015-01-20 - database speed improvements
		// 2.19 - 2014-12-22 - subscription dashboard alerts permission fix
		// 2.189 - 2014-11-27 - payment notes follow through to finances
		// 2.188 - 2014-10-13 - select custom pdf/email template per subscription
		// 2.187 - 2014-10-06 - improved debugging
		// 2.186 - 2014-09-09 - text fix when max renewals is set
		// 2.185 - 2014-09-05 - paypal trial period feature
		// 2.184 - 2014-08-12 - bug fix subscription listing
		// 2.183 - 2014-07-25 - subscription date in task list
		// 2.182 - 2014-07-12 - subscription credit and ordering fix
		// 2.181 - 2014-07-02 - permission improvement
		// 2.18 - 2014-07-01 - cron job debuging
		// 2.179 - 2014-06-27 - invoice sidebar fix
		// 2.178 - 2014-04-10 - speed improvements
		// 2.177 - 2014-04-05 - subscription recurring limit
		// 2.176 - 2014-03-19 - sort subscriptions alphabetically on member/customer pages
		// 2.175 - 2014-03-04 - subscription payments as invoice credit
		// 2.174 - 2014-02-25 - dashboard subscription listing bug fix
		// 2.173 - 2014-01-18 - starting work on automatic recurring payments
		// 2.172 - 2013-11-15 - working on new UI
		// 2.171 - 2013-11-11 - subscriptions with tax included option
		// 2.169 - 2013-10-05 - Settings > Invoice - option to choose what time of day for renewals/emails to occur
		// 2.168 - 2013-10-02 - subscription dashboard link fix
		// 2.167 - 2013-09-27 - finance list shows customer
		// 2.166 - 2013-09-26 - invoice finances
		// 2.165 - 2013-09-13 - dashboard link fix when only 1 notification is displayed
		// 2.164 - 2013-09-11 - improved delete/re-add subscription
		// 2.163 - 2013-09-11 - template date range fix on emails
		// 2.162 - 2013-09-11 - only checks for automatic invoices if changes have been made
		// 2.161 - 2013-09-10 - send subscription invoice X days prior to renewal date
		// 2.159 - 2013-09-07 - invoice_auto_renew_only_paid_invoices fix
		// 2.158 - 2013-09-06 - duplicate entry bug fix
		// 2.157 - 2013-09-05 - checkbox fix and subscription_send_invoice_straight_away option added
		// 2.156 - 2013-09-03 - subscriptions added to customer signup form
		// 2.155 - 2013-09-03 - invoice_auto_renew_only_paid_invoices config variable added
		// 2.154 - 2013-07-30 - installation fix
		// 2.153 - 2013-07-29 - automated website subscriptions layout improvement
		// 2.152 - 2013-07-29 - automated website subscriptions layout improvement
		// 2.151 - 2013-07-28 - support for automated website subscriptions
		// 2.149 - 2013-07-28 - automatic subscription renewal and dashboard improvement
		// 2.148 - 2013-04-16 - improved subscription start date
		// 2.147 - 2013-04-16 - fix for updated invoice system

		// old history:
		// 2.13 - initial release
		// 2.131 - better integration with invoicing sysetem. eg: eamiling an invoice to a member. adding a member_id field to invoice.
		// 2.132 - delete fix.
		// 2.134 - permission fix.
		// 2.135 - submit_small in create
		// 2.136 - Delete member bug fix
		// 2.137 - hook into finance module to display nicer in finance listing
		// 2.138 - subscription support for customers.
		// 2.139 - permission fix
		// 2.140 - bug fixing
		// 2.141 - fix for subscription in finance upcoming items
		// 2.142 - customer subscription bug fix
		// 2.143 - dashboard alerts bug fix
		// 2.144 - subscription invoice number improvement
		// 2.145 - subscription next due date manual editing
		// 2.146 - 2013-04-13 - subscription next date better calculations + subscription_calc_type advanced setting


		module_config::register_css( 'subscription', 'subscription.css' );
		module_config::register_js( 'subscription', 'subscription.js' );;
		hook_add( 'invoice_sidebar', 'module_subscription::hook_invoice_sidebar' );
		hook_add( 'invoice_deleted', 'module_subscription::hook_invoice_deleted' );
		hook_add( 'invoice_replace_fields', 'module_subscription::hook_invoice_replace_fields' );

		hook_add( 'member_edit', 'module_subscription::hook_member_edit_form' );
		hook_add( 'member_save', 'module_subscription::hook_member_edit_form_save' );
		hook_add( 'member_deleted', 'module_subscription::hook_member_deleted' );

		hook_add( 'customer_edit', 'module_subscription::hook_customer_edit_form' );
		hook_add( 'customer_save', 'module_subscription::hook_customer_edit_form_save' );
		hook_add( 'customer_deleted', 'module_subscription::hook_customer_deleted' );

		hook_add( 'website_main', 'module_subscription::hook_website_edit_form' );
		hook_add( 'website_save', 'module_subscription::hook_website_edit_form_save' );
		hook_add( 'website_deleted', 'module_subscription::hook_website_deleted' );

		hook_add( 'finance_recurring_list', 'module_subscription::get_finance_recurring_items' );
		hook_add( 'finance_invoice_listing', 'module_subscription::get_invoice_listing' );

		hook_add( 'invoice_email_template', 'module_subscription::hook_filter_var_invoice_email_template' );

		/*if(self::db_table_exists('subscription_owner') && self::db_table_exists('subscription_member') && self::db_table_exists('subscription_customer')){
            // hack to do an upgrade of subscription data once this table exists.
            $sql = "SELECT * FROM `"._DB_PREFIX."subscription_member`";
            $members = qa($sql);
            foreach($members as $r){
                if(!$r['member_id'])continue;
                // found some old entries to update.
                $subscription_owner = get_single('subscription_owner',array(
                    'subscription_id',
                    'owner_table',
                    'owner_id',
                ),array(
                    $r['subscription_id'],
                    'member',
                    $r['member_id'],
                ),true);
                $subscription_owner_id = $subscription_owner && isset($subscription_owner['subscription_owner_id']) ? $subscription_owner['subscription_owner_id'] : false;
                if(!$subscription_owner_id){
                    $subscription_owner_id = update_insert('subscription_owner_id',false,'subscription_owner',array(
                        'subscription_id' => $r['subscription_id'],
                        'owner_table' => 'member',
                        'owner_id' => $r['member_id'],
                        'deleted' => $r['deleted'],
                        'start_date' => $r['start_date'],
                        'next_due_date' => $r['next_due_date'],
                        'manual_next_due_date' => $r['manual_next_due_date'],
                    ));
                }
                if($subscription_owner_id){
                    // add this subscription_owner_id to any existing subscription_history entries
                    $sql = "UPDATE `"._DB_PREFIX."subscription_history` SET subscription_owner_id = ".(int)$subscription_owner_id." WHERE member_id = ".(int)$r['member_id']." AND subscription_id = ".(int)$r['subscription_id']."";
                    query($sql);
                    delete_from_db('subscription_member',array('subscription_id','member_id'),array($r['subscription_id'],$r['member_id']));
                }else{
                    set_error('Failed to insert subscription owner for some reason, id: '.$r['subscription_history_id']);
                }

            }
            $sql = "SELECT * FROM `"._DB_PREFIX."subscription_customer`";
            $customers = qa($sql);
            foreach($customers as $r){
                if(!$r['customer_id'])continue;
                // found some old entries to update.
                $subscription_owner = get_single('subscription_owner',array(
                    'subscription_id',
                    'owner_table',
                    'owner_id',
                ),array(
                    $r['subscription_id'],
                    'customer',
                    $r['customer_id'],
                ),true);
                $subscription_owner_id = $subscription_owner && isset($subscription_owner['subscription_owner_id']) ? $subscription_owner['subscription_owner_id'] : false;
                if(!$subscription_owner_id){
                    $subscription_owner_id = update_insert('subscription_owner_id',false,'subscription_owner',array(
                        'subscription_id' => $r['subscription_id'],
                        'owner_table' => 'customer',
                        'owner_id' => $r['customer_id'],
                        'deleted' => $r['deleted'],
                        'start_date' => $r['start_date'],
                        'next_due_date' => $r['next_due_date'],
                        'manual_next_due_date' => $r['manual_next_due_date'],
                    ));
                }
                if($subscription_owner_id){
                    // add this subscription_owner_id to any existing subscription_history entries
                    $sql = "UPDATE `"._DB_PREFIX."subscription_history` SET subscription_owner_id = ".(int)$subscription_owner_id." WHERE customer_id = ".(int)$r['customer_id']." AND subscription_id = ".(int)$r['subscription_id']."";
                    query($sql);
                    delete_from_db('subscription_customer',array('subscription_id','customer_id'),array($r['subscription_id'],$r['customer_id']));
                }else{
                    set_error('Failed to insert subscription owner for some reason, id: '.$r['subscription_history_id']);
                }

            }
        }*/

	}

	public function pre_menu() {

		if ( $this->can_i( 'view', 'Subscriptions' ) && $this->can_i( 'edit', 'Subscriptions' ) && module_config::can_i( 'view', 'Settings' ) ) {


			// how many subscriptions are there?
			$link_name = _l( 'Subscriptions' );

			$this->links['subscriptions'] = array(
				"name"                => $link_name,
				"p"                   => "subscription_admin",
				"args"                => array( 'subscription_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);

			if ( isset( $_GET['subscription_bug_close'] ) ) {
				module_config::save_config( 'subscription_check_unsent', 0 );
			}
			if ( module_config::c( 'subscription_check_unsent', 1 ) ) {
				hook_add( 'inner_content_start', 'module_subscription::inner_content_start' );
			}
		}

	}

	public static function inner_content_start() {
		$sql             = "SELECT * FROM `" . _DB_PREFIX . "subscription_history` p ";
		$sql             .= " LEFT JOIN `" . _DB_PREFIX . "invoice` i ";
		$sql             .= " ON p.invoice_id = i.invoice_id";
		$sql             .= " WHERE p.paid_date = '0000-00-00'";
		$sql             .= " AND p.date_created >= '2016-09-10'";
		$sql             .= " AND p.date_created <= '2016-10-21'";
		$sql             .= " AND i.date_sent = '0000-00-00'";
		$invoice_items   = qa( $sql );
		$unsent_invoices = array();
		foreach ( $invoice_items as $invoice_item ) {
			// check if these invoices are unsent.
			$unsent_invoices[ $invoice_item['invoice_id'] ] = module_invoice::link_open( $invoice_item['invoice_id'], true );
		}
		if ( count( $unsent_invoices ) ) {
			?>
			<div style="padding:10px;margin: 10px; border:1px solid #FF0000;">
				Notice: there are <?php echo count( $unsent_invoices ); ?> unsent subscription invoices due to a recent bug.
				Please check all your recent invoices and manually send them to the customer if
				required: <?php echo implode( ', ', $unsent_invoices ); ?>.
				&nbsp;
				&nbsp;
				&nbsp;
				&nbsp;
				<a href="<?php echo _BASE_HREF; ?>?subscription_bug_close">Hide This Notice</a>
			</div>
			<?php
		}
	}

	/** static stuff */


	public static function link_generate( $subscription_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'subscription_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

		// we check if we're bubbling from a sub link, and find the item id from a sub link
		if ( ${$key} === false && $link_options ) {
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
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $subscription_id > 0 ) {
					$data = self::get_subscription( $subscription_id );
				} else {
					$data = array();

					return _l( 'N/A' );
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = isset( $data['name'] ) ? $data['name'] : _l( 'Unknown Subscription' );
		}
		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'subscription_id' => $subscription_id,
		);
		// generate the path (module & page) for this link
		$options['page']   = 'subscription_admin';
		$options['module'] = 'subscription';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! self::can_i( 'view', 'Subscriptions' ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				return '#';
			} else {
				return isset( $options['text'] ) ? $options['text'] : _l( 'N/A' );
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		$bubble_to_module = array(
			'module'   => 'config',
			'argument' => 'subscription_id',
		);
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


	public static function link_open( $subscription_id, $full = false, $data = array() ) {
		return self::link_generate( $subscription_id, array( 'full' => $full, 'data' => $data ) );
	}


	public static function get_subscriptions( $search = array() ) {

		$sql = "SELECT s.*, s.subscription_id AS `id`";
		//$sql .= ", COUNT(sm.subscription_id) AS member_count ";
		$sql .= ", (SELECT COUNT(so1.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so1 WHERE s.subscription_id = so1.subscription_id AND so1.owner_table = 'member' AND (so1.`deleted` = 0 OR so1.`deleted` IS NULL)) AS member_count";
		$sql .= ", (SELECT COUNT(so2.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so2 WHERE s.subscription_id = so2.subscription_id AND so2.owner_table = 'customer' AND (so2.`deleted` = 0 OR so2.`deleted` IS NULL)) AS customer_count";
		$sql .= ", (SELECT COUNT(so3.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so3 WHERE s.subscription_id = so3.subscription_id AND so3.owner_table = 'website' AND (so3.`deleted` = 0 OR so3.`deleted` IS NULL)) AS website_count";
		//$sql .= ", COUNT(sc.subscription_id) AS customer_count ";
		//        $sql .= ", (SELECT COUNT(sc.subscription_id) FROM `"._DB_PREFIX."subscription_customer` sc WHERE s.subscription_id = sc.subscription_id) AS customer_count";
		$sql .= " FROM `" . _DB_PREFIX . "subscription` s ";
		//$sql .= " LEFT JOIN `"._DB_PREFIX."subscription_member` sm ON s.subscription_id = sm.subscription_id";
		//$sql .= " LEFT JOIN `"._DB_PREFIX."subscription_customer` sc ON s.subscription_id = sc.subscription_id";
		$sql .= " GROUP BY s.subscription_id";
		$sql .= " ORDER BY s.name";

		return qa( $sql );
		//return get_multiple("subscription",$search,"subscription_id","fuzzy","name");
	}

	/*public static function get_subscribed_members($subscription_id,$include_deleted=false){

        $sql = "SELECT s.*, sm.* ";
        $sql .= " FROM `"._DB_PREFIX."subscription_member` sm ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE 1 ";
        if(!$include_deleted){
            $sql .=  " AND sm.`deleted` = 0";
        }
        $sql .=  " AND sm.`subscription_id` = ".(int)$subscription_id;
        return qa($sql);
	}
	public static function get_subscribed_customers($subscription_id,$include_deleted=false){

        $sql = "SELECT s.*, sm.* ";
        $sql .= " FROM `"._DB_PREFIX."subscription_customer` sm ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE 1 ";
        if(!$include_deleted){
            $sql .=  " AND sm.`deleted` = 0";
        }
        $sql .=  " AND sm.`subscription_id` = ".(int)$subscription_id;
        return qa($sql);
	}*/
	public static function get_subscribed_owners( $subscription_id, $owner_table = false, $include_deleted = false ) {

		$sql = "SELECT s.*, so.* "; //, (SELECT COUNT(*) FROM `"._DB_PREFIX."subscription_history` sh WHERE sh.subscription_owner_id = so.subscription_owner_id ) AS history_count ";
		$sql .= " FROM `" . _DB_PREFIX . "subscription_owner` so ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "subscription` s USING (subscription_id)";
		$sql .= " WHERE 1 ";
		if ( ! $include_deleted ) {
			$sql .= " AND so.`deleted` = 0";
		}
		if ( $owner_table ) {
			$sql .= " AND so.`owner_table` = '" . db_escape( $owner_table ) . "'";
		}
		$sql .= " AND so.`subscription_id` = " . (int) $subscription_id;
		$sql .= " GROUP BY so.`subscription_owner_id`";
		$res = qa( $sql );
		// we manually modify the 'next_due_date' to include any invoice_prior_days setting
		foreach ( $res as $rid => $r ) {
			// todo - move this to a DATE_SUB mysql function
			if ( isset( $r['invoice_prior_days'] ) && $r['invoice_prior_days'] > 0 && $r['next_due_date'] != '0000-00-00' ) {
				$res[ $rid ]['next_generation_date'] = date( 'Y-m-d', strtotime( '-' . $r['invoice_prior_days'] . ' days', strtotime( $r['next_due_date'] ) ) );
			} else {
				$res[ $rid ]['next_generation_date'] = $r['next_due_date'];
			}
		}

		return $res;
	}

	/*public static function get_subscriptions_by_member($member_id,$subscription_id=false){

        $sql = "SELECT s.*, sm.*, s.subscription_id AS id ";
        $sql .= " FROM `"._DB_PREFIX."subscription_member` sm ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE sm.member_id = ".(int)$member_id;
        $sql .=  " AND sm.`deleted` = 0";
        if($subscription_id){
            $sql .=  " AND sm.`subscription_id` = ".(int)$subscription_id;
        }
        return qa($sql);
		//return get_multiple("subscription",$search,"subscription_id","fuzzy","name");
	}
	public static function get_subscriptions_by_customer($customer_id,$subscription_id=false){

        $sql = "SELECT s.*, sc.*, s.subscription_id AS id ";
        $sql .= " FROM `"._DB_PREFIX."subscription_customer` sc ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE sc.customer_id = ".(int)$customer_id;
        $sql .=  " AND sc.`deleted` = 0";
        if($subscription_id){
            $sql .=  " AND sc.`subscription_id` = ".(int)$subscription_id;
        }
        return qa($sql);
		//return get_multiple("subscription",$search,"subscription_id","fuzzy","name");
	}*/
	public static function get_available_credit( $owner_table, $owner_id ) {
		// used from invoice page, and also subscription select page.
		$subscriptions = self::get_subscriptions_by( $owner_table, $owner_id );
		foreach ( $subscriptions as $subscription_id => $subscription ) {
			$subscriptions[ $subscription_id ]['total']         = 0;
			$subscriptions[ $subscription_id ]['used']          = 0;
			$subscriptions[ $subscription_id ]['paid_invoices'] = array();
			$subscriptions[ $subscription_id ]['remain']        = 0;
			if ( $subscription['use_as_credit_bucket'] ) {
				$history = module_subscription::get_subscription_history( $subscription['subscription_id'], $owner_table, $owner_id );
				foreach ( $history as $h ) {
					if ( $h['invoice_id'] && $h['paid_date'] ) {
						$invoice_data                               = module_invoice::get_invoice( $h['invoice_id'] );
						$subscriptions[ $subscription_id ]['total'] += $invoice_data['total_amount_paid'];
					}
				}
				// find any invoice payments linked to this particular subscription_owner entry.
				/*update_insert('invoice_payment_id',false,'invoice_payment',array(
                            'invoice_id' => $invoice_id,
                            'payment_type'=>_INVOICE_PAYMENT_TYPE_SUBSCRIPTION_CREDIT,
                            'method' => 'Credit',
                            'amount' => $apply_credit,
                            'currency_id' => $invoice_data['currency_id'],
                            'other_id' => $subscription_owner['subscription_owner_id'],
                            'date_paid' => date('Y-m-d'),
                        ));*/
				$existing_payments = get_multiple( 'invoice_payment', array(
					'payment_type' => _INVOICE_PAYMENT_TYPE_SUBSCRIPTION_CREDIT,
					'other_id'     => $subscription['subscription_owner_id']
				) );
				foreach ( $existing_payments as $existing_payment ) {
					$subscriptions[ $subscription_id ]['used']            += $existing_payment['amount'];
					$subscriptions[ $subscription_id ]['paid_invoices'][] = $existing_payment['invoice_id'];
				}
				$subscriptions[ $subscription_id ]['remain'] = $subscriptions[ $subscription_id ]['total'] - $subscriptions[ $subscription_id ]['used'];
			}
		}

		return $subscriptions;
	}

	public static function get_subscriptions_by( $owner_table, $owner_id, $subscription_id = false, $include_deleted = false ) {

		$sql = "SELECT s.*, so.* ";
		if ( ! $include_deleted ) {
			$sql .= ", s.subscription_id AS id ";
		}
		$sql .= " FROM `" . _DB_PREFIX . "subscription_owner` so ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "subscription` s USING (subscription_id)";
		$sql .= " WHERE so.owner_id = " . (int) $owner_id;
		$sql .= " AND so.`owner_table` = '" . db_escape( $owner_table ) . "'";
		if ( ! $include_deleted ) {
			$sql .= " AND so.`deleted` = 0";
		}
		if ( $subscription_id ) {
			$sql .= " AND so.`subscription_id` = " . (int) $subscription_id;
		}
		$res = qa( $sql );
		// we manually modify the 'next_due_date' to include any invoice_prior_days setting
		foreach ( $res as $rid => $r ) {
			// todo - move this to a DATE_SUB mysql function
			if ( isset( $r['invoice_prior_days'] ) && $r['invoice_prior_days'] > 0 && $r['next_due_date'] != '0000-00-00' ) {
				$res[ $rid ]['next_generation_date'] = date( 'Y-m-d', strtotime( '-' . $r['invoice_prior_days'] . ' days', strtotime( $r['next_due_date'] ) ) );
			} else {
				$res[ $rid ]['next_generation_date'] = $r['next_due_date'];
			}
		}
		if ( ! $include_deleted ) {
			// return an array indexed by subscription_id
			return $res;
		}
		// if we're here we are "saving" a list of subscriptions for this owner.
		// a hack here to fix up a previous sql error.
		// we have to merge multiple subscription_owner entries.
		$existing_owners = array();
		$return          = array();
		foreach ( $res as $r ) {
			if ( isset( $existing_owners[ $r['owner_table'] . '|' . $r['owner_id'] . '|' . $r['subscription_id'] ] ) ) {
				// crap, got a duplicate.
				// modify subscription_history entries to point to the old owner thingey, and remove this owner entry
				$sql = "UPDATE `" . _DB_PREFIX . "subscription_history` SET subscription_owner_id = '" . (int) $existing_owners[ $r['owner_table'] . '|' . $r['owner_id'] . '|' . $r['subscription_id'] ] . "' WHERE subscription_owner_id = '" . (int) $r['subscription_owner_id'] . "'";
				query( $sql );
				$sql = "DELETE FROM`" . _DB_PREFIX . "subscription_owner` WHERE subscription_owner_id = '" . (int) $r['subscription_owner_id'] . "'";
				query( $sql );
			} else {
				$existing_owners[ $r['owner_table'] . '|' . $r['owner_id'] . '|' . $r['subscription_id'] ] = $r['subscription_owner_id'];
				$return[ $r['subscription_id'] ]                                                           = $r;
			}
		}

		return $return;
		//return get_multiple("subscription",$search,"subscription_id","fuzzy","name");
	}

	public static function get_subscription_history( $subscription_id, $owner_table, $owner_id ) {

		$sql = "SELECT sh.* ";
		$sql .= " FROM `" . _DB_PREFIX . "subscription_history` sh ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "subscription_owner` so USING (subscription_owner_id) ";
		$sql .= " WHERE sh.subscription_id = " . (int) $subscription_id;
		$sql .= " AND so.owner_table = '" . db_escape( $owner_table ) . "'";
		$sql .= " AND so.owner_id = '" . (int) $owner_id . "'";
		/*if($member_id>0){
            $sql .= " AND sh.member_id = ".(int)$member_id;
        }
        if($customer_id>0){
            $sql .= " AND sh.customer_id = ".(int)$customer_id;
        }*/
		$sql .= " ORDER BY sh.`paid_date` ASC, `sh`.subscription_history_id DESC"; // asc needed for next due date calculations.

		return qa( $sql );
		//return get_multiple("subscription",$search,"subscription_id","fuzzy","name");
	}

	public static function get_subscription_owner( $subscription_owner_id ) {
		return get_single( 'subscription_owner', 'subscription_owner_id', $subscription_owner_id );
	}

	public static function get_subscription( $subscription_id ) {
		$subscription_id = (int) $subscription_id;
		$subscription    = false;
		if ( $subscription_id > 0 ) {
			$sql = "SELECT s.* "; // COUNT(sm.subscription_id) AS member_count, COUNT(sc.subscription_id) AS customer_count ";

			$sql .= ", (SELECT COUNT(so1.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so1 WHERE s.subscription_id = so1.subscription_id AND so1.owner_table = 'member' AND (so1.`deleted` = 0 OR so1.`deleted` IS NULL)) AS member_count";
			$sql .= ", (SELECT COUNT(so2.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so2 WHERE s.subscription_id = so2.subscription_id AND so2.owner_table = 'customer' AND (so2.`deleted` = 0 OR so2.`deleted` IS NULL)) AS customer_count";
			$sql .= ", (SELECT COUNT(so3.subscription_id) FROM `" . _DB_PREFIX . "subscription_owner` so3 WHERE s.subscription_id = so3.subscription_id AND so3.owner_table = 'website' AND (so3.`deleted` = 0 OR so3.`deleted` IS NULL)) AS website_count";

			$sql .= " FROM `" . _DB_PREFIX . "subscription` s ";
			//            $sql .= " LEFT JOIN `"._DB_PREFIX."subscription_member` sm ON s.subscription_id = sm.subscription_id";
			//            $sql .= " LEFT JOIN `"._DB_PREFIX."subscription_customer` sc ON s.subscription_id = sc.subscription_id";
			$sql .= " WHERE s.subscription_id = " . (int) $subscription_id . "";
			//            $sql .=  " AND (sm.`deleted` = 0 OR sm.`deleted` IS NULL)";
			//            $sql .=  " AND (sc.`deleted` = 0 OR sc.`deleted` IS NULL)";
			$sql                      .= " GROUP BY s.subscription_id";
			$subscription             = qa1( $sql );
			$subscription['settings'] = isset( $subscription['settings'] ) ? @json_decode( $subscription['settings'], true ) : array();
			if ( ! is_array( $subscription['settings'] ) ) {
				$subscription['settings'] = array();
			}
		}
		if ( ! $subscription ) {
			$subscription = array(
				'subscription_id' => '0',
				'name'            => '',
				'days'            => '',
				'months'          => '',
				'years'           => '',
				'amount'          => '',
				'currency_id'     => '',
				'member_count'    => 0,
				'customer_count'  => 0,
				'website_count'   => 0,
				'settings'        => array(),
			);
		}

		return $subscription;
	}


	public function process() {
		if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['subscription_id'] ) {
			$data = self::get_subscription( $_REQUEST['subscription_id'] );
			if ( module_form::confirm_delete( 'subscription_id', "Really delete subscription: " . $data['name'], self::link_open( $_REQUEST['subscription_id'] ) ) ) {
				$this->delete_subscription( $_REQUEST['subscription_id'] );
				set_message( "Subscription deleted successfully" );
				redirect_browser( self::link_open( false ) );
			}
		} else if ( "save_subscription" == $_REQUEST['_process'] ) {
			$subscription_id = $this->save_subscription( $_REQUEST['subscription_id'], $_POST );
			set_message( "Subscription saved successfully" );
			redirect_browser( self::link_open( $subscription_id ) );
		}
	}


	public function save_subscription( $subscription_id, $data ) {
		if ( isset( $data['settings'] ) ) {
			$data['settings'] = json_encode( $data['settings'] );
		}
		if ( isset( $data['default_automatic_renew'] ) && ! isset( $data['automatic_renew'] ) ) {
			$data['automatic_renew'] = 0;
		}
		if ( isset( $data['default_automatic_email'] ) && ! isset( $data['automatic_email'] ) ) {
			$data['automatic_email'] = 0;
		}
		$subscription_id = update_insert( "subscription_id", $subscription_id, "subscription", $data );

		module_extra::save_extras( 'subscription', 'subscription_id', $subscription_id );

		return $subscription_id;
	}


	public function delete_subscription( $subscription_id ) {
		$subscription_id = (int) $subscription_id;
		$subscription    = self::get_subscription( $subscription_id );
		if ( $subscription && $subscription['subscription_id'] == $subscription_id ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "subscription WHERE subscription_id = '" . $subscription_id . "' LIMIT 1";
			query( $sql );
			module_extra::delete_extras( 'subscription', 'subscription_id', $subscription_id );
		}
	}

	public static function hook_customer_edit_form_save( $callback_name, $customer_id ) {
		if ( module_config::c( 'subscription_allow_in_customers', 1 ) ) {
			self::member_edit_form_save( $callback_name, 'customer', $customer_id );
		}
	}

	public static function hook_member_edit_form_save( $callback_name, $member_id ) {
		if ( module_config::c( 'subscription_allow_in_members', 1 ) ) {
			self::member_edit_form_save( $callback_name, 'member', $member_id );
		}
	}

	public static function hook_website_edit_form_save( $callback_name, $website_id ) {
		if ( module_config::c( 'subscription_allow_in_websites', 1 ) ) {
			self::member_edit_form_save( $callback_name, 'website', $website_id );
		}
	}

	private static function member_edit_form_save( $callback_name, $owner_table, $owner_id ) {
		$changes_made = false;
		if ( isset( $_REQUEST['member_subscriptions_save'] ) ) {
			$members_subscriptions = module_subscription::get_subscriptions_by( $owner_table, $owner_id, false, true );
			/*if($customer_hack){
                $members_subscriptions = module_subscription::get_subscriptions_by_customer($member_id);
            }else{
                $members_subscriptions = module_subscription::get_subscriptions_by_member($member_id);
            }*/
			// check if any are deleted.
			// check if any are added.
			if ( isset( $_REQUEST['subscription'] ) && is_array( $_REQUEST['subscription'] ) ) {
				foreach ( $_REQUEST['subscription'] as $subscription_id => $tf ) {
					if ( isset( $members_subscriptions[ $subscription_id ] ) ) {
						unset( $members_subscriptions[ $subscription_id ] );
						// this one already exists as a member.
						// option to update the start date for this one.
						if ( isset( $_REQUEST['subscription_start_date'] ) && isset( $_REQUEST['subscription_start_date'][ $subscription_id ] ) ) {
							$date = input_date( $_REQUEST['subscription_start_date'][ $subscription_id ] );
							if ( $date ) {
								// todo - if we support multiple subscriptions per owner table then we want to change this from subscription_id to subscription_owner_id
								$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `deleted` = 0, `start_date` = '" . db_escape( $date ) . "' WHERE `owner_id` = " . (int) $owner_id . " AND `owner_table` = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
								/*if($customer_hack){
                                    $sql = "UPDATE `"._DB_PREFIX."subscription_customer` SET `start_date` = '".db_escape($date)."' WHERE `customer_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                                }else{
                                    $sql = "UPDATE `"._DB_PREFIX."subscription_member` SET `start_date` = '".db_escape($date)."' WHERE `member_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                                }*/
								query( $sql );
								$changes_made = true;
							}
						}
						// this input box is set from subscription.js when adjusting the next due date manually.
						if ( isset( $_REQUEST['subscription_next_due_date_change'] ) && isset( $_REQUEST['subscription_next_due_date_change'][ $subscription_id ] ) ) {
							$date = input_date( $_REQUEST['subscription_next_due_date_change'][ $subscription_id ] );
							if ( $date ) {
								// todo - if we support multiple subscriptions per owner table then we want to change this from subscription_id to subscription_owner_id
								$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `deleted` = 0, `next_due_date` = '" . db_escape( $date ) . "',  manual_next_due_date = 1 WHERE `owner_id` = " . (int) $owner_id . " AND `owner_table` = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
								/*if($customer_hack){
                                    $sql = "UPDATE `"._DB_PREFIX."subscription_customer` SET `next_due_date` = '".db_escape($date)."', manual_next_due_date = 1 WHERE `customer_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                                }else{
                                    $sql = "UPDATE `"._DB_PREFIX."subscription_member` SET `next_due_date` = '".db_escape($date)."', manual_next_due_date = 1 WHERE `member_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                                }*/
								query( $sql );
								$changes_made = true;
							}
						} else {
							self::update_next_due_date( $subscription_id, $owner_table, $owner_id, false );
							$changes_made = true;
						}

						if ( module_config::c( 'subscription_allow_credit', 1 ) ) {
							$credit = 0;
							if ( isset( $_REQUEST['subscription_credit'][ $subscription_id ] ) ) {
								$credit = (int) $_REQUEST['subscription_credit'][ $subscription_id ];
							}
							$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `use_as_credit_bucket` = $credit WHERE `owner_id` = " . (int) $owner_id . " AND `owner_table` = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
							query( $sql );
							$changes_made = true;
						}
						if ( module_config::c( 'subscription_allow_limits', 1 ) ) {
							if ( isset( $_REQUEST['subscription_recur_limits'][ $subscription_id ] ) ) {
								$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `recur_limit` = " . (int) $_REQUEST['subscription_recur_limits'][ $subscription_id ] . " WHERE `owner_id` = " . (int) $owner_id . " AND `owner_table` = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
								query( $sql );
								$changes_made = true;
							}

						}

					} else {
						// adding a new subscription to this user.
						$start_date = input_date( $_REQUEST['subscription_start_date'][ $subscription_id ] );
						/*// find history. to modify start date based on first payment.
                        $history = self::get_subscription_history($subscription_id,$member_id);
                        if(count($history)>0){
                            foreach($history as $h){
                                if($h['paid_date']!='0000-00-00'){
                                    $start_date = $h['paid_date'];
                                    break;
                                }
                            }
                        }*/
						// add this new one to this member.
						$sql = "INSERT INTO `" . _DB_PREFIX . "subscription_owner` SET ";
						$sql .= " owner_id = '" . (int) $owner_id . "'";
						$sql .= ", owner_table = '" . db_escape( $owner_table ) . "'";
						$sql .= ", subscription_id = '" . (int) $subscription_id . "'";
						$sql .= ", start_date = '$start_date'";
						/*if($customer_hack){
                            $sql = "REPLACE INTO `"._DB_PREFIX."subscription_customer` SET ";
                            $sql .= " customer_id = '".(int)$member_id."'";
                            $sql .= ", subscription_id = '".(int)$subscription_id."'";
                            $sql .= ", start_date = '$start_date'";
                        }else{
                            $sql = "REPLACE INTO `"._DB_PREFIX."subscription_member` SET ";
                            $sql .= " member_id = '".(int)$member_id."'";
                            $sql .= ", subscription_id = '".(int)$subscription_id."'";
                            $sql .= ", start_date = '$start_date'";
                        }*/
						query( $sql );

						self::update_next_due_date( $subscription_id, $owner_table, $owner_id, true );
						$changes_made = true;
					}
				}
			}
			// remove any left in subscription history.
			foreach ( $members_subscriptions as $subscription_id => $subscription ) {
				$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `deleted` = 1, next_due_date = '0000-00-00' WHERE `owner_id` = " . (int) $owner_id . " AND owner_table = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
				/*if($customer_hack){
                    $sql = "UPDATE `"._DB_PREFIX."subscription_customer` SET `deleted` = 1 WHERE `customer_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                }else{
                    $sql = "UPDATE `"._DB_PREFIX."subscription_member` SET `deleted` = 1 WHERE `member_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
                }*/
				query( $sql );
				$changes_made = true;
			}
		}
		// handle the payment adding. invoice creation. etc.!!
		// similar to premium ticket creation.
		if ( isset( $_REQUEST['subscription_add_payment_amount'] ) && $_REQUEST['subscription_add_payment_amount'] > 0 ) {

			$date            = input_date( $_REQUEST['subscription_add_payment_date'] );
			$amount          = number_in( $_REQUEST['subscription_add_payment_amount'] );
			$subscription_id = (int) $_REQUEST['subscription_add_payment'];
			$invoice_id      = self::generate_subscription_invoice( $subscription_id, $owner_table, $owner_id, $date, $amount );
			$changes_made    = true;

			redirect_browser( module_invoice::link_open( $invoice_id ) );

		}

		// run the cron job so that any invoices are automatically sent
		// this code is also in customer.php
		if ( $changes_made && module_config::c( 'subscription_send_invoice_straight_away', 0 ) ) {
			self::run_cron();
		}
	}

	public static function generate_subscription_invoice( $subscription_id, $owner_table, $owner_id, $date, $amount ) {
		$subscription = self::get_subscription( $subscription_id );
		if ( ! $subscription || $subscription['subscription_id'] != $subscription_id ) {
			return false;
		}
		$members_subscriptions = module_subscription::get_subscriptions_by( $owner_table, $owner_id );
		/*if($customer_hack){
            $members_subscriptions = module_subscription::get_subscriptions_by_customer($member_id);
        }else{
            $members_subscriptions = module_subscription::get_subscriptions_by_member($member_id);
        }*/
		// we have an ammount! create an invoice for this amount/
		// assign it to a subscription (but not necessary!)
		if ( $subscription_id && ! isset( $members_subscriptions[ $subscription_id ] ) ) {
			die( 'Shouldnt happen' );
		}


		$history = module_subscription::get_subscription_history( $subscription_id, $owner_table, $owner_id );
		// we grab the history of this subscription. if this is the first subscription for this member and the $date is in the past then we update the date to today.
		if ( strtotime( $date ) < strtotime( date( 'Y-m-d' ) ) ) {
			$has_history = false;
			foreach ( $history as $h ) {
				if ( ! $h['invoice_id'] ) {

				} else {
					$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
					if ( $invoice_data['date_cancel'] != '0000-00-00' ) {
						continue;
					}
					$has_history = true;
					break;
				}
			}
			if ( ! $has_history ) {
				$date = date( 'Y-m-d' );
			}
		}

		// does this one have a discount/trial ?

		$number_of_past_invoices = 0;
		foreach ( $history as $h ) {
			if ( ! $h['invoice_id'] ) {

			} else {
				$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
				if ( $invoice_data['date_cancel'] != '0000-00-00' ) {
					continue;
				}
				$number_of_past_invoices ++;
			}
		}
		//if(isset($subscription['settings']) && isset($subscription['settings']['trial_period']) && $subscription['settings']['trial_period'] > 0 && $number_of_past_invoices < $subscription['settings']['trial_period']){
		//echo $number_of_past_invoices;print_r($subscription['settings']);print_r($history);exit;
		if ( $number_of_past_invoices <= 0 && isset( $subscription['settings']['trial_price_adjust'] ) && $subscription['settings']['trial_price_adjust'] != 0 ) {
			$amount += $subscription['settings']['trial_price_adjust'];
		}

		//}


		//$next_time = self::_calculate_next_time(strtotime($date), $subscription);
		if ( isset( $members_subscriptions[ $subscription_id ]['next_due_date'] ) && $members_subscriptions[ $subscription_id ]['next_due_date'] != '0000-00-00' && $members_subscriptions[ $subscription_id ]['next_due_date'] != $date ) {
			$time_period = self::_calculate_next_time( strtotime( $members_subscriptions[ $subscription_id ]['next_due_date'] ), $subscription, true );
		} else {
			$time_period = self::_calculate_next_time( strtotime( $date ), $subscription, true );
		}

		$subscription_owner_id = $members_subscriptions[ $subscription_id ]['subscription_owner_id'];

		$amount_currency = $subscription['currency_id']; //module_config::c('subscription_currency',1);

		$data = array(
			'subscription_id'       => $subscription_id,
			'subscription_owner_id' => $subscription_owner_id,
			'amount'                => $amount,
			'currency_id'           => $amount_currency,
			'invoice_id'            => 0,
			'from_next_due_date'    => $members_subscriptions[ $subscription_id ]['next_due_date'],
		);
		/*if($customer_hack){
            unset($data['member_id']);
            $data['customer_id'] = $member_id;
        }*/
		$subscription_history_id = update_insert( 'subscription_history_id', 0, 'subscription_history', $data );

		$invoice_task_description = '';

		$customer_id = 0;
		switch ( $owner_table ) {
			case 'website':
				$website_data = module_website::get_website( $owner_id );
				$customer_id  = $website_data['customer_id'];
				if ( module_config::c( 'subscription_add_website_details', 1 ) ) {
					$invoice_task_description .= ' ' . $website_data['name'];
				}
				break;
			case 'customer':
				$customer_id = $owner_id;
				break;
		}

		$customer_id = (int) $customer_id;

		module_invoice::$new_invoice_number_date = $date;
		// we have to seed the customer id if it exists.
		$_REQUEST['customer_id']     = $customer_id;
		$invoice_data                = module_invoice::get_invoice( 'new', true );
		$invoice_data['customer_id'] = $customer_id;
		// customer_id, website_id, member_id
		$invoice_data[ $owner_table . '_id' ] = $owner_id;
		/*if($customer_hack){
            $invoice_data['member_id'] = 0;
            $invoice_data['customer_id'] = $member_id;
        }else{
            $invoice_data['member_id'] = $member_id; // added in version 2.31 for invoice integration. eg: emailing invoice
            $invoice_data['customer_id'] = 0;
        }*/

		$invoice_data['user_id']           = 0;
		$invoice_data['currency_id']       = $amount_currency;
		$invoice_data['date_sent']         = '0000-00-00';
		$invoice_data['date_cancel']       = '0000-00-00';
		$invoice_data['date_create']       = $date;
		$invoice_data['default_task_type'] = _TASK_TYPE_AMOUNT_ONLY;
		// todo - option this out to the subscription settings area.
		$invoice_data['date_due'] = date( 'Y-m-d', strtotime( "+" . module_config::c( 'subscription_invoice_due_date', 0 ) . " days", strtotime( $date ) ) );
		if ( strtotime( $invoice_data['date_due'] ) < time() ) {
			// due date in the past? hmm, update it from today instead.
			$invoice_data['date_due'] = date( 'Y-m-d', strtotime( "+" . module_config::c( 'subscription_invoice_due_date', 0 ) . " days", time() ) );
		}
		$invoice_data['name'] = ( ! $invoice_data['name'] || module_config::c( 'subscription_invoice_numeric', 0 ) ) ? 'S' . str_pad( $subscription_history_id, 6, '0', STR_PAD_LEFT ) : $invoice_data['name'];
		// pick a tax rate for this automatic invoice.
		$invoice_data['total_tax_name'] = isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_name'] ) ? $subscription['settings']['tax_name'] : '';
		$invoice_data['total_tax_rate'] = isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_amount'] ) ? $subscription['settings']['tax_amount'] : '';
		$invoice_data['tax_type']       = isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_type'] ) ? $subscription['settings']['tax_type'] : module_config::c( 'invoice_tax_type', 0 );

		$invoice_data['invoice_template_email'] = isset( $subscription['settings'] ) && ! empty( $subscription['settings']['invoice_template_email'] ) ? $subscription['settings']['invoice_template_email'] : '';
		$invoice_data['invoice_template_print'] = isset( $subscription['settings'] ) && ! empty( $subscription['settings']['invoice_template_print'] ) ? $subscription['settings']['invoice_template_print'] : module_customer::c( 'invoice_template_print_default', false, $customer_id );


		$invoice_data['invoice_invoice_item'] = array(
			'new' => array(
				'description'      => $members_subscriptions[ $subscription_id ]['name'] . $time_period . $invoice_task_description,
				'hourly_rate'      => $amount,
				//'amount' => $amount,
				'completed'        => 1, // not needed?
				'manual_task_type' => _TASK_TYPE_AMOUNT_ONLY,
				'date_done'        => $date,
			)
		);
		$invoice_id                           = module_invoice::save_invoice( 'new', $invoice_data );
		if ( $invoice_id ) {
			// limit payment methods if this has been set in the options area:
			$payment_methods = handle_hook( 'get_payment_methods' );

			foreach ( $payment_methods as &$payment_method ) {
				if ( $payment_method->is_enabled() ) {
					$enabled = isset( $subscription['settings']['payment_methods'][ $payment_method->module_name ] ) && $subscription['settings']['payment_methods'][ $payment_method->module_name ] ? true : ( isset( $subscription['settings']['payment_methods'] ) ? false : true );
					if ( $enabled ) {
						$payment_method->set_allowed_for_invoice( $invoice_id, 1 );
					} else {
						$payment_method->set_allowed_for_invoice( $invoice_id, 0 );
					}
				}
			}

			update_insert( 'subscription_history_id', $subscription_history_id, 'subscription_history', array(
				'invoice_id' => $invoice_id,
			) );
			module_invoice::add_history( $invoice_id, 'Created invoice from subscription #' . str_pad( $subscription_history_id, 6, '0', STR_PAD_LEFT ) . ' from ' . $owner_table . ' ID# ' . $owner_id );
			self::update_next_due_date( $subscription_id, $owner_table, $owner_id );
		} else {
			set_error( 'failed to create subscription invoice' );
		}

		return $invoice_id;

	}

	public static function hook_filter_var_invoice_email_template( $callback, $template_name, $invoice_id, $invoice_data ) {
		// we check if this invoice is part of a subscription
		if ( $template_name ) {
			$number_of_past_invoices   = 0;
			$subscription_history_item = get_single( 'subscription_history', 'invoice_id', $invoice_id );
			if ( $subscription_history_item && $subscription_history_item['subscription_owner_id'] ) {
				// we have an invoice that is on a subscription!
				$subscription_owner = module_subscription::get_subscription_owner( $subscription_history_item['subscription_owner_id'] );
				// check if there are unpaid invoices that were generated after this invoice.
				if ( $subscription_owner['subscription_owner_id'] == $subscription_history_item['subscription_owner_id'] ) {
					$history = get_multiple( 'subscription_history', array( 'subscription_owner_id' => $subscription_owner['subscription_owner_id'] ) );
					foreach ( $history as $h ) {
						if ( ! $h['invoice_id'] ) {

						} else {
							$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
							if ( $invoice_data['date_cancel'] != '0000-00-00' ) {
								continue;
							}
							$number_of_past_invoices ++;
						}
					}
				}
				$template_test = module_template::get_template_by_key( $template_name . '_' . $number_of_past_invoices );
				if ( $template_test->template_id > 0 ) {
					return $template_test->template_key;
				}
			}
		}

		return $template_name;
	}


	// oldstyle hook handling, before hook registration
	public function handle_hook( $hook ) {
		switch ( $hook ) {
			case "invoice_paid":
				$foo        = func_get_args();
				$invoice_id = (int) $foo[1];
				if ( $invoice_id > 0 ) {
					// see if any subscriptions match this invoice.
					//module_cache::clear_cache();
					$invoice      = module_invoice::get_invoice( $invoice_id );
					$subscription = get_single( 'subscription_history', 'invoice_id', $invoice_id );
					if ( $subscription ) {
						// mark subscription as paid and move onto the next date.
						update_insert( 'subscription_history_id', $subscription['subscription_history_id'], 'subscription_history', array(
							'paid_date' => $invoice['date_paid'],
						) );
						$subscription_owner = get_single( 'subscription_owner', 'subscription_owner_id', $subscription['subscription_owner_id'] );
						$this->update_next_due_date( $subscription['subscription_id'], $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
						/*if($subscription['customer_id']){
                            $this->update_next_due_date($subscription['subscription_id'],$subscription['customer_id'],true);
                        }else{
                            $this->update_next_due_date($subscription['subscription_id'],$subscription['member_id'],false);
                        }*/
					}
				}

				break;

			case "home_alerts":
				$alerts = array();
				if ( module_config::c( 'subscription_alerts', 1 ) && self::can_i( 'view', 'Subscriptions' ) ) {

					// find renewals due in a certain time.
					$time = date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) );

					$key = _l( 'Subscription Due' );
					if ( class_exists( 'module_dashboard', false ) ) {
						module_dashboard::register_group( $key, array(
							'columns' => array(
								'full_link'         => _l( 'Name' ),
								'type'              => _l( 'Type' ),
								'subscription_name' => _l( 'Subscription' ),
								'automatic_renew'   => _l( 'Automatic Renew' ),
								'automatic_email'   => _l( 'Automatic Email' ),
								'next_due_date'     => _l( 'Next Due Date' ),
								'days'              => _l( 'Day Count' ),
							)
						) );
					}

					$db_fields = get_fields( 'subscription' );
					$sql       = "SELECT s.*, so.* ";
					if ( isset( $db_fields['invoice_prior_days'] ) ) {
						$sql .= ", DATE_SUB(so.next_due_date, INTERVAL `invoice_prior_days` DAY) AS next_generation_date ";
					}
					$sql .= " FROM `" . _DB_PREFIX . "subscription_owner` so ";
					$sql .= " LEFT JOIN `" . _DB_PREFIX . "subscription` s USING (subscription_id)";
					if ( isset( $db_fields['invoice_prior_days'] ) ) {
						$sql .= " WHERE DATE_SUB(so.next_due_date, INTERVAL `invoice_prior_days` DAY) <= '" . $time . "'";
					} else {
						$sql .= " WHERE so.next_due_date <= '" . $time . "'";
					}
					$sql .= " AND so.`deleted` = 0";
					//                    echo $sql;
					$items = qa( $sql );
					foreach ( $items as $item ) {
						//                        echo '<hr>';print_r($item);echo '<hr>';
						$alert_res = process_alert( isset( $item['next_generation_date'] ) ? $item['next_generation_date'] : $item['next_due_date'], $key );
						if ( $alert_res ) {
							switch ( $item['owner_table'] ) {
								case 'member':
									$permission_check = module_member::get_member( $item['owner_id'] );
									if ( ! $permission_check || $permission_check['member_id'] != $item['owner_id'] || ! module_member::can_i( 'view', 'Members' ) ) {
										continue 2;
									}
									$alert_res['full_link'] = module_member::link_open( $item['owner_id'], true );
									break;
								case 'website':
									$permission_check = module_website::get_website( $item['owner_id'] );
									if ( ! $permission_check || $permission_check['website_id'] != $item['owner_id'] || ! module_website::can_i( 'view', 'Websites' ) ) {
										continue 2;
									}
									$alert_res['full_link'] = module_website::link_open( $item['owner_id'], true );
									break;
								case 'customer':
									$permission_check = module_customer::get_customer( $item['owner_id'] );
									if ( ! $permission_check || $permission_check['customer_id'] != $item['owner_id'] || ! module_customer::can_i( 'view', 'Customers' ) ) {
										continue 2;
									}
									$alert_res['full_link'] = module_customer::link_open( $item['owner_id'], true );
									break;
							}
							$alert_res['name'] = $item['name'];
							$alert_res['link'] = '#';
							if ( preg_match( '@href="([^"]+)"@', $alert_res['full_link'], $link_match ) ) {
								$alert_res['link'] = $link_match[1];
							}
							$alert_res['type']              = $item['owner_table'];
							$alert_res['subscription_name'] = module_subscription::link_open( $item['subscription_id'], true );
							$alert_res['next_due_date']     = isset( $item['next_generation_date'] ) ? print_date( $item['next_generation_date'] ) : print_date( $item['next_due_date'] );
							$alert_res['automatic_renew']   = $item['automatic_renew'] ? _l( 'Yes' ) : _l( 'No' );
							$alert_res['automatic_email']   = $item['automatic_email'] ? _l( 'Yes' ) : _l( 'No' );
							$alerts[]                       = $alert_res;
						}
					}

				}

				return $alerts;

				break;
		}
	}

	public static function update_next_due_date( $subscription_id, $owner_table, $owner_id, $overwrite_any_manual_next_date = true ) {
		// todo
		$subscription = self::get_subscription( $subscription_id );
		$history      = self::get_subscription_history( $subscription_id, $owner_table, $owner_id );
		$res          = self::get_subscriptions_by( $owner_table, $owner_id, $subscription_id );
		$link         = array_shift( $res );
		/*if($customer_hack){
            $history = self::get_subscription_history($subscription_id,false,$member_id);
            $res = self::get_subscriptions_by_customer($member_id,$subscription_id);
            $link = array_shift($res);
        }else{
            $history = self::get_subscription_history($subscription_id,$member_id,false);
            $res = self::get_subscriptions_by_member($member_id,$subscription_id);
            $link = array_shift($res);
        }*/
		if ( ! $link ) {
			return;
		}

		if ( ! $overwrite_any_manual_next_date && isset( $link['manual_next_due_date'] ) && $link['manual_next_due_date'] ) {
			// we have manually set a next due date, an we don't want to overwrite it
			return;
		}

		if ( module_config::c( 'subscription_calc_type', 'start_date' ) == 'start_date' ) {
			$next_time   = $link['next_due_date'] && $link['next_due_date'] != '0000-00-00' ? strtotime( $link['next_due_date'] ) : strtotime( $link['start_date'] );
			$has_history = false;
			foreach ( $history as $h ) {
				if ( $h['invoice_id'] ) {
					$invoice = module_invoice::get_invoice( $h['invoice_id'] );
					if ( isset( $h['from_next_due_date'] ) && $h['from_next_due_date'] != '0000-00-00' ) {
						$t = strtotime( $h['from_next_due_date'] );
						if ( $t >= $next_time ) {
							$next_time   = $t;
							$has_history = true;
						}
					} else if ( $invoice['date_create'] && $invoice['date_create'] != '0000-00-00' ) {
						$t = strtotime( $invoice['date_create'] );
						if ( $t >= $next_time ) {
							$next_time   = $t;
							$has_history = true;
						}
					}
				}
			}
		} else {
			// calculate based off last paid date.

			$last_paid_time = strtotime( $link['start_date'] );

			$has_history = false;
			foreach ( $history as $h ) {
				if ( $h['paid_date'] != '0000-00-00' ) {
					if ( strtotime( $h['paid_date'] ) >= $last_paid_time ) {
						$last_paid_time = strtotime( $h['paid_date'] );
						$has_history    = true;
						// find out when this invoice was due.
						// this is the date we go off.
						if ( $h['invoice_id'] ) {
							$invoice        = module_invoice::get_invoice( $h['invoice_id'] );
							$last_paid_time = strtotime( $invoice['date_due'] );
						}
					}
				}
			}

			$next_time = $last_paid_time;
		}
		if ( $has_history ) {
			$next_time = self::_calculate_next_time( $next_time, $subscription );
			/*$next_time = strtotime('+'.abs((int)$subscription['days']).' days',$next_time);
            $next_time = strtotime('+'.abs((int)$subscription['months']).' months',$next_time);
            $next_time = strtotime('+'.abs((int)$subscription['years']).' years',$next_time);*/
		}

		$sql = "UPDATE `" . _DB_PREFIX . "subscription_owner` SET `next_due_date` = '" . date( 'Y-m-d', $next_time ) . "', manual_next_due_date = 0 WHERE `owner_id` = " . (int) $owner_id . " AND `owner_table` = '" . db_escape( $owner_table ) . "' AND subscription_id = '" . (int) $subscription_id . "' LIMIT 1";
		/*if($customer_hack){
            $sql = "UPDATE `"._DB_PREFIX."subscription_customer` SET `next_due_date` = '".date('Y-m-d',$next_time)."', manual_next_due_date = 0 WHERE `customer_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
        }else{
            $sql = "UPDATE `"._DB_PREFIX."subscription_member` SET `next_due_date` = '".date('Y-m-d',$next_time)."', manual_next_due_date = 0 WHERE `member_id` = ".(int)$member_id." AND subscription_id = '".(int)$subscription_id."' LIMIT 1";
        }*/
		query( $sql );
	}

	public static function hook_member_edit_form( $callback_name, $member_id ) {
		if ( self::can_i( 'view', 'Subscriptions' ) && module_config::c( 'subscription_allow_in_members', 1 ) ) {
			$owner_table = 'member';
			include( 'hooks/member_edit.php' );
		}
	}

	public static function hook_customer_edit_form( $callback_name, $member_id ) {
		if ( self::can_i( 'view', 'Subscriptions' ) && module_config::c( 'subscription_allow_in_customers', 1 ) ) {
			$owner_table = 'customer';
			include( 'hooks/member_edit.php' );
		}
	}

	public static function hook_website_edit_form( $callback_name, $member_id ) {
		if ( self::can_i( 'view', 'Subscriptions' ) && module_config::c( 'subscription_allow_in_websites', 1 ) ) {
			$owner_table = 'website';
			include( 'hooks/member_edit.php' );
		}
	}

	public static function hook_invoice_sidebar( $callback_name, $invoice_id ) {
		if ( (int) $invoice_id > 0 ) {
			// check if this invoice is linked to any subscription payments.
			$subscriptions = get_multiple( 'subscription_history', array( 'invoice_id' => $invoice_id ) );
			if ( $subscriptions ) {
				include( 'hooks/invoice_sidebar.php' );
			}

		}
	}

	public static function hook_customer_deleted( $callback_name, $customer_id ) {
		if ( (int) $customer_id > 0 ) {
			// check if this customer is linked to any subscription payments.
			$members_subscriptions = module_subscription::get_subscriptions_by( 'customer', $customer_id, false, true );
			foreach ( $members_subscriptions as $members_subscription ) {
				delete_from_db( 'subscription_history', 'subscription_owner_id', $members_subscription['subscription_owner_id'] );
			}
			delete_from_db( 'subscription_owner', array( 'owner_id', 'owner_table' ), array( $customer_id, 'customer' ) );
		}
	}

	public static function hook_member_deleted( $callback_name, $member_id ) {
		if ( (int) $member_id > 0 ) {
			// check if this member is linked to any subscription payments.
			$members_subscriptions = module_subscription::get_subscriptions_by( 'member', $member_id, false, true );
			foreach ( $members_subscriptions as $members_subscription ) {
				delete_from_db( 'subscription_history', 'subscription_owner_id', $members_subscription['subscription_owner_id'] );
			}
			delete_from_db( 'subscription_owner', array( 'owner_id', 'owner_table' ), array( $member_id, 'member' ) );
		}
	}

	public static function hook_website_deleted( $callback_name, $website_id ) {
		if ( (int) $website_id > 0 ) {
			// check if this website is linked to any subscription payments.
			$members_subscriptions = module_subscription::get_subscriptions_by( 'website', $website_id, false, true );
			foreach ( $members_subscriptions as $members_subscription ) {
				delete_from_db( 'subscription_history', 'subscription_owner_id', $members_subscription['subscription_owner_id'] );
			}
			delete_from_db( 'subscription_owner', array( 'owner_id', 'owner_table' ), array( $website_id, 'website' ) );
		}
	}

	public static function hook_invoice_deleted( $callback_name, $invoice_id ) {
		if ( (int) $invoice_id > 0 ) {
			// check if this invoice is linked to any subscription payments.
			$subscriptions = get_multiple( 'subscription_history', array( 'invoice_id' => $invoice_id ) );
			if ( $subscriptions ) {
				foreach ( $subscriptions as $subscription ) {
					if ( $subscription && $subscription['subscription_owner_id'] ) {
						$subscription_owner = get_single( 'subscription_owner', 'subscription_owner_id', $subscription['subscription_owner_id'] );
						if ( $subscription_owner ) {
							// remove this subscription payment from the subscription history
							delete_from_db( 'subscription_history', 'subscription_history_id', $subscription['subscription_history_id'] );
							self::update_next_due_date( $subscription['subscription_id'], $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
						}
					}
				}
			}
		}
	}

	public static function hook_invoice_replace_fields( $callback_name, $invoice_id, $existing_data ) {
		$new_data                      = array();
		$new_data['subscription_name'] = '';
		if ( (int) $invoice_id > 0 ) {
			// check if this invoice is linked to any subscription payments.
			$subscription_history = get_single( 'subscription_history', 'invoice_id', $invoice_id );
			if ( $subscription_history ) {
				//$subscription_member_history = self::get_subscription_history($subscription_history['subscription_id'], $subscription_history['member_id'], $subscription_history['customer_id']);
				$subscription_owner = get_single( 'subscription_owner', 'subscription_owner_id', $subscription_history['subscription_owner_id'] );
				$subscriptions      = module_subscription::get_subscriptions_by( $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
				/*if(isset($subscription_history['member_id']) && $subscription_history['member_id']){
                    $subscriptions = module_subscription::get_subscriptions_by_member($subscription_history['member_id']);
                }else if(isset($subscription_history['customer_id']) && $subscription_history['customer_id']){
                    $subscriptions = module_subscription::get_subscriptions_by_customer($subscription_history['customer_id']);
                }*/
				if ( isset( $subscriptions[ $subscription_history['subscription_id'] ] ) ) {
					$subscription = self::get_subscription( $subscription_history['subscription_id'] );
					$invoice_data = module_invoice::get_invoice( $invoice_id, true );

					$new_data['subscription_name'] = $subscription['name'];
					// it might not be 'date_create' it might be days in the future
					if ( isset( $subscription_history['from_next_due_date'] ) && $subscription_history['from_next_due_date'] && $subscription_history['from_next_due_date'] != '0000-00-00' ) {
						$new_data['invoice_date_range'] = self::_calculate_next_time( strtotime( $subscription_history['from_next_due_date'] ), $subscription, true );
					} else {
						$new_data['invoice_date_range'] = self::_calculate_next_time( strtotime( $invoice_data['date_create'] ), $subscription, true );
					}

					// is this linked to a website? pull in website data into the invoice replace fields (code copied from invoice.php)
					if ( $subscription_owner['owner_table'] == 'website' ) {
						$website_data = module_website::get_website( $subscription_owner['owner_id'] );
						if ( $website_data && $website_data['website_id'] == $subscription_owner['owner_id'] ) {
							$website_url = $project_names = $project_names_and_url = array();
							if ( isset( $website_data['url'] ) && $website_data['url'] ) {
								$website_url[ $website_data['website_id'] ] = module_website::urlify( $website_data['url'] );
								$website_data['name_url']                   = $website_data['name'] . ' (' . module_website::urlify( $website_data['url'] ) . ')';
							} else {
								$website_data['name_url'] = $website_data['name'];
							}
							$project_names[ $website_data['website_id'] ]         = $website_data['name'];
							$project_names_and_url[ $website_data['website_id'] ] = $website_data['name_url'];
							if ( ! $existing_data['website_name'] ) {
								$new_data['website_name'] = $new_data['project_name'] = forum_text( count( $project_names ) ? implode( ', ', $project_names ) : '' );
							}
							if ( ! $existing_data['website_name_url'] ) {
								$new_data['website_name_url'] = forum_text( count( $project_names_and_url ) ? implode( ', ', $project_names_and_url ) : '' );
							}
							if ( ! $existing_data['website_url'] ) {
								$new_data['website_url'] = forum_text( count( $website_url ) ? implode( ', ', $website_url ) : '' );
							}
						}
					}
				}

			}

		}

		return $new_data;
	}

	/*public static function hook_member_deleted($callback_name, $member_id){
        if((int)$member_id>0){
            // check if this member is linked to any subscription payments.
            delete_from_db('subscription_history','member_id',$member_id);
            delete_from_db('subscription_member','member_id',$member_id);
        }
    }*/

	public static function get_invoice_listing( $hook, $invoice_id, $full_finance_item ) {
		// check if this invoice id is a subscription payment
		if ( $full_finance_item && isset( $full_finance_item['finance_id'] ) && (int) $full_finance_item['finance_id'] > 0 ) {
			// already have saved a finance item against this invoice id, so we don't try to assume this is a subscription payment
		} else if ( $full_finance_item && isset( $full_finance_item['invoice_payment_id'] ) && (int) $full_finance_item['invoice_payment_id'] > 0 ) {
			$subscription = get_single( 'subscription_history', 'invoice_id', $invoice_id );
			if ( $subscription ) {
				$subscription_owner = get_single( 'subscription_owner', 'subscription_owner_id', $subscription['subscription_owner_id'] );
				$customer_id        = 0;
				switch ( $subscription_owner['owner_table'] ) {
					case 'customer':
						$member_name = module_customer::link_open( $subscription_owner['owner_id'], true );
						$customer_id = $subscription_owner['owner_id'];
						break;
					case 'website':
						$member_name  = module_website::link_open( $subscription_owner['owner_id'], true );
						$website_data = module_website::get_website( $subscription_owner['owner_id'] );
						$customer_id  = isset( $website_data['customer_id'] ) ? $website_data['customer_id'] : false;
						break;
					case 'member':
						$member_name = module_member::link_open( $subscription_owner['owner_id'], true );
						break;
					default:
						$member_name = 'Unknown';
				}
				$subscription_name = module_subscription::link_open( $subscription['subscription_id'], true );
				// pull in any custom payment notes from the invoice page
				$notes = '';
				if ( isset( $full_finance_item['data'] ) && strlen( $full_finance_item['data'] ) ) {
					$details = @unserialize( $full_finance_item['data'] );
					if ( $details && isset( $details['custom_notes'] ) && strlen( $details['custom_notes'] ) ) {
						$notes = " \n(" . $details['custom_notes'] . ')';
					}
				}
				$new_finance = array(
					'name'        => _l( 'Subscription Payment' ),
					'description' => _l( 'Payment against invoice #%s on subscription %s', module_invoice::link_open( $invoice_id, true ), $subscription_name ) . $notes,
					// . var_export($full_finance_item,true),
					'customer_id' => $customer_id,
				);

				return $new_finance;
			}
		}
	}

	public static function get_finance_recurring_items( $hook, $search ) {
		/**
		 * next_due_date
		 * url
		 * type (i or e)
		 * amount
		 * currency_id
		 * days
		 * months
		 * years
		 * last_transaction_finance_id
		 * account_name
		 * categories
		 * finance_recurring_id
		 */
		// find list of all members.
		// then go through and fine list of all upcoming subscription payments.
		// add these ones (and future ones up to (int)module_config::c('finance_recurring_months',6) months from todays date.

		$end_date = isset( $search['date_to'] ) && ! empty( $search['date_to'] ) ? strtotime( input_date( $search['date_to'] ) ) : strtotime( "+" . (int) module_config::c( 'finance_recurring_months', 6 ) . ' months' );


		/*$sql = "SELECT s.*, sm.*";
        $sql .= " FROM `"._DB_PREFIX."subscription_member` sm ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE sm.`deleted` = 0";
        $members =  qa($sql);
        $sql = "SELECT s.*, sc.*";
        $sql .= " FROM `"._DB_PREFIX."subscription_customer` sc ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."subscription` s USING (subscription_id)";
        $sql .= " WHERE sc.`deleted` = 0";
        $customers =  qa($sql);
        $items = array_merge($members,$customers);*/
		//$members = module_member::ge
		$sql   = "SELECT s.*, so.*";
		$sql   .= " FROM `" . _DB_PREFIX . "subscription_owner` so ";
		$sql   .= " LEFT JOIN `" . _DB_PREFIX . "subscription` s USING (subscription_id)";
		$sql   .= " WHERE so.`deleted` = 0";
		$sql   .= " GROUP BY `owner_table`, `owner_id`";
		$items = qa( $sql );
		//$members = module_member::get_members(array());
		$return = array();

		foreach ( $items as $member ) {

			$subscriptions = module_subscription::get_subscriptions_by( $member['owner_table'], $member['owner_id'] );
			/*if(isset($member['member_id']) && $member['member_id']){

            }else if(isset($member['customer_id']) && $member['customer_id']){
                $subscriptions = module_subscription::get_subscriptions_by_customer($member['customer_id']);
            }else{
                $subscriptions = array();
            }*/
			foreach ( $subscriptions as $subscription ) {

				$time = strtotime( $subscription['next_generation_date'] ? $subscription['next_generation_date'] : $subscription['next_due_date'] );
				if ( ! $time ) {
					continue;
				}

				switch ( $member['owner_table'] ) {
					case 'customer':
						$type                  = 'customer';
						$member_name           = module_customer::link_open( $member['owner_id'], true );
						$subscription_invoices = self::get_subscription_history( $subscription['subscription_id'], $member['owner_table'], $member['owner_id'] );
						break;
					case 'website':
						$type                  = 'website';
						$member_name           = module_website::link_open( $member['owner_id'], true );
						$subscription_invoices = self::get_subscription_history( $subscription['subscription_id'], $member['owner_table'], $member['owner_id'] );
						break;
					case 'member':
						$type                  = 'member';
						$member_name           = module_member::link_open( $member['owner_id'], true );
						$subscription_invoices = self::get_subscription_history( $subscription['subscription_id'], $member['owner_table'], $member['owner_id'] );
						break;
					default:
						$subscription_invoices = array();
						$member_name           = 'unknown2';
						$type                  = 'unknown2';
				}

				$subscription_name = module_subscription::link_open( $subscription['subscription_id'], true );
				foreach ( $subscription_invoices as $subscription_invoice_id => $subscription_invoice ) {
					if ( $subscription_invoice['invoice_id'] ) {
						$subscription_invoices[ $subscription_invoice_id ] = array_merge( $subscription_invoice, module_invoice::get_invoice( $subscription_invoice['invoice_id'], 2 ) );
					}
				}


				$original = true;
				$c        = 0;
				while ( $time < $end_date ) {
					if ( $c ++ > 200 ) {
						break;
					}
					$next_time = 0;
					if ( ! $subscription['days'] && ! $subscription['months'] && ! $subscription['years'] ) {
						// it's a once off..
						// add it to the list but dont calculate the next one.

					} else if ( ! $original ) {
						// work out when the next one will be.
						$next_time = self::_calculate_next_time( $time, $subscription );
						$time      = $next_time;
					} else {
						$original = false;
						// it's the original one.
						$next_time = $time;
					}

					if ( $next_time ) {


						// don't show it here if an invoice has already been generated.
						// because invoice will already be in the list as outstanding
						foreach ( $subscription_invoices as $subscription_invoice ) {
							if ( isset( $subscription_invoice['date_create'] ) && $subscription_invoice['date_create'] == date( 'Y-m-d', $next_time ) ) {
								//echo 'match';
								continue 2;
							}
						}

						$return[] = array(
							'next_due_date'               => date( 'Y-m-d', $next_time ), //$subscription['next_due_date'],
							'url'                         => _l( 'Subscription: %s', $member_name ),
							'type'                        => 'i',
							'amount'                      => $subscription['amount'],
							'currency_id'                 => $subscription['currency_id'],
							'days'                        => $subscription['days'],
							'months'                      => $subscription['months'],
							'years'                       => $subscription['years'],
							'last_transaction_finance_id' => 0,
							'account_name'                => '',
							'categories'                  => '',
							'finance_recurring_id'        => 0,
							'last_transaction_text'       => '(see member page)',
							'end_date'                    => '0000-00-00',
							'start_date'                  => $subscription['start_date'],
							'recurring_text'              => _l( 'Payment from %s %s on subscription %s', $type, $member_name, $subscription_name ),
						);
					}
				}

			}
		}


		return $return;
	}

	public static function run_cron( $debug = false ) {

		// we only want to perform these cron actions if we're after a certain time of day
		// because we dont want to be generating these renewals and sending them at midnight, can get confusing
		$after_time  = module_config::c( 'invoice_automatic_after_time', 7 );
		$time_of_day = date( 'G' );
		if ( $time_of_day < $after_time ) {
			if ( $debug ) {
				echo "Not performing automatic subscription operations until after $after_time:00 - it is currently $time_of_day:" . date( 'i' ) . "<br>\n";
			}

			return;
		}

		// find all automatic subscriptions and renew them (if applicable)
		$sql           = "SELECT * FROM `" . _DB_PREFIX . "subscription` s ";
		$sql           .= " WHERE s.automatic_renew = 1";
		$subscriptions = qa( $sql );

		// keep a record of these so we can combine them later on:
		$subscription_invoices_by_customer = array();


		foreach ( $subscriptions as $subscription ) {
			if ( $subscription['automatic_renew'] ) {
				if ( $debug ) {
					echo "<br>\nProcessing subscription renewals for subscription " . module_subscription::link_open( $subscription['subscription_id'], true ) . "<br>\n<br>\n";
				}

				// find all the members/customers from this subscription
				//$members = module_subscription::get_subscribed_members($subscription['subscription_id']);
				//$customers = module_subscription::get_subscribed_customers($subscription['subscription_id']);
				$owners = module_subscription::get_subscribed_owners( $subscription['subscription_id'] );
				foreach ( $owners as $member ) {
					if ( ! $member['next_generation_date'] || $member['next_generation_date'] == '0000-00-00' ) {
						continue;
					}
					if ( ! $member['next_due_date'] || $member['next_due_date'] == '0000-00-00' ) {
						continue;
					}
					if ( $debug ) {
						echo "Doing: " . $member['owner_table'] . " " . $member['owner_id'] . "<br>\n";
					}
					// check permissions for logged in users, dont want the cron to run when someone is logged in and no access to this account.
					if ( module_security::is_logged_in() ) {
						switch ( $member['owner_table'] ) {
							case 'website':
								$website_perm_check = module_website::get_website( $member['owner_id'] );
								if ( ! $website_perm_check || $website_perm_check['website_id'] != $member['owner_id'] ) {
									continue 2;
								}
								if ( $debug ) {
									echo "permission pass for website: " . $website_perm_check['website_id'];
								}
								break;
							case 'customer':
								$customer_perm_check = module_customer::get_customer( $member['owner_id'] );
								if ( ! $customer_perm_check || $customer_perm_check['customer_id'] != $member['owner_id'] ) {
									continue 2;
								}
								if ( $debug ) {
									echo "permission pass for customer: " . $customer_perm_check['customer_id'];
								}
								break;
						}
					}
					// is the last invoice unpaid?
					$history                       = self::get_subscription_history( $subscription['subscription_id'], $member['owner_table'], $member['owner_id'] );
					$next_due_time_invoice_created = false;
					$invoice_unpaid                = false;
					if ( isset( $member['recur_limit'] ) && (int) $member['recur_limit'] > 0 && count( $history ) >= (int) $member['recur_limit'] ) {
						if ( $debug ) {
							echo " - not renewing this one because it has hit our recur limit of " . $member['recur_limit'] . "<br>\n";
						}
						continue;
					}
					foreach ( $history as $h ) {
						$last_invoice = module_invoice::get_invoice( $h['invoice_id'] );
						if ( ! $last_invoice || $last_invoice['date_cancel'] != '0000-00-00' ) {
							continue;
						}
						// check the new 'next_due_date' entry in the db table
						if ( isset( $h['from_next_due_date'] ) && $h['from_next_due_date'] && $h['from_next_due_date'] != '0000-00-00' ) {
							// we're using the new method of checking when an invoice was generated, rather than the confusing invoice 'date_create' check below
							if ( $debug ) {
								echo " - checking if next_due_date " . print_date( $member['next_due_date'] ) . " matches subscription history from_next_due_date for invoice " . module_invoice::link_open( $h['invoice_id'], true, $last_invoice ) . " from_next_due_date: " . print_date( $h['from_next_due_date'] ) . " (invoice create_date: " . print_date( $last_invoice['date_create'] ) . ")<br>\n";
							}
							if ( print_date( $member['next_due_date'] ) == print_date( $h['from_next_due_date'] ) ) { //print_date($last_invoice['date_create'])){
								// this invoice is for the next due date.
								$next_due_time_invoice_created = $last_invoice;
							}
						} else {
							if ( $debug ) {
								echo " - checking if next_generation_date (" . print_date( $member['next_generation_date'] ) . ") or next_due_date (" . print_date( $member['next_due_date'] ) . ") matches invoice " . module_invoice::link_open( $h['invoice_id'], true, $last_invoice ) . " created date (" . print_date( $last_invoice['date_create'] ) . ") <br>\n";
							}
							if (
								( print_date( $member['next_generation_date'] ) == print_date( $last_invoice['date_create'] ) )
								||
								( print_date( $member['next_due_date'] ) == print_date( $last_invoice['date_create'] ) )
							) { //print_date($last_invoice['date_create'])){
								// this invoice is for the next due date.
								$next_due_time_invoice_created = $last_invoice;
							}
						}
						if ( $last_invoice['total_amount_due'] > 0 ) {
							$invoice_unpaid = true;
						}
					}
					//self::generate_subscription_invoice($subscription_id, $customer_hack, $member_id, $date, $amount)
					$next_due_time = strtotime( $member['next_generation_date'] );
					if ( $debug ) {
						echo " - next subscription time is " . $member['next_generation_date'] . " <br>\n";
					}
					if ( $next_due_time <= strtotime( date( 'Y-m-d' ) ) && ! $next_due_time_invoice_created ) {

						if ( $debug ) {
							echo " - Yes its time to generate an invoice!<br>\n";
						}

						if ( module_config::c( 'invoice_auto_renew_only_paid_invoices', 1 ) && $invoice_unpaid ) {
							if ( $debug ) {
								echo " - skipping generating renewal for " . $member['owner_table'] . " " . $member['owner_id'] . " because a previous subscription is unpaid <br>\n";
							}
							continue;
						}

						// time to generate! woo!
						if ( $debug ) {
							echo " - generating subscription renewal for " . $member['owner_table'] . " " . $member['owner_id'] . "<br>\n";
						}
						$invoice_id = self::generate_subscription_invoice( $subscription['subscription_id'], $member['owner_table'], $member['owner_id'], $member['next_generation_date'], $subscription['amount'] );
						if ( $debug ) {
							echo " - generated invoice " . module_invoice::link_open( $invoice_id, true ) . " for subscription <br>\n";
						}
						if ( $invoice_id ) {
							$new_invoice_data = module_invoice::get_invoice( $invoice_id );
							if ( $new_invoice_data['customer_id'] ) {
								if ( ! isset( $subscription_invoices_by_customer[ $new_invoice_data['customer_id'] ] ) ) {
									$subscription_invoices_by_customer[ $new_invoice_data['customer_id'] ] = array();
								}
								$tax_hash = '';
								foreach ( $new_invoice_data['taxes'] as $new_invoice_tax ) {
									$tax_hash .= $new_invoice_tax['percent'] . ',' . $new_invoice_tax['name'];
								}
								$hash = md5( serialize( array(
									$new_invoice_data['tax_type'],
									$tax_hash,
									$new_invoice_data['currency_id']
								) ) );
								if ( ! isset( $subscription_invoices_by_customer[ $new_invoice_data['customer_id'] ][ $hash ] ) ) {
									$subscription_invoices_by_customer[ $new_invoice_data['customer_id'] ][ $hash ] = array();
								}
								if ( $debug ) {
									echo " - added invoice to customer id " . $new_invoice_data['customer_id'] . " list for later merging <br>\n";
								}
								$subscription_invoices_by_customer[ $new_invoice_data['customer_id'] ][ $hash ][] = array(
									'invoice_id'      => $invoice_id,
									'automatic_email' => $subscription['automatic_email'],
								);
							}
						}
						if ( ! module_config::c( 'subscription_merge_invoices', 1 ) && $subscription['automatic_email'] ) {
							if ( $debug ) {
								echo " - emailing invoice to " . $member['owner_table'] . "... <br>\n";
							}
							if ( module_invoice::email_invoice_to_customer( $invoice_id, $debug ) ) {
								if ( $debug ) {
									echo "send successfully <br>\n";
								}
							} else {
								echo " - failed to send invoice " . module_invoice::link_open( $invoice_id, true ) . " to " . $member['owner_table'] . " <br>\n";
							}
						}
					} else {
						if ( $debug ) {
							echo " - skipping generating renewal for " . $member['owner_table'] . " " . $member['owner_id'] . " because the due date has already been generated <br>\n";
						}
					}
				}
			}
		}

		if ( module_config::c( 'subscription_merge_invoices', 1 ) && count( $subscription_invoices_by_customer ) ) {
			if ( $debug ) {
				echo " \n\n<br><br>Merging invoices together and sending as one <br>\n";
			}
			//print_r($subscription_invoices_by_customer);
			foreach ( $subscription_invoices_by_customer as $customer_id => $invoice_hashes ) {
				foreach ( $invoice_hashes as $invoice_hash => $invoice_details ) {

					if ( count( $invoice_details ) >= 1 ) {
						$automatic_email = false;
						// got a hash. merging these together.
						if ( $debug ) {
							echo " - Merging " . count( $invoice_details ) . " invoices together that match hash $invoice_hash <br>\n";
						}
						$parent_invoice_id = false;
						foreach ( $invoice_details as $invoice_detail ) {
							if ( ! $parent_invoice_id ) {
								$parent_invoice_id = $invoice_detail['invoice_id'];
							} else {
								// we're merging this invoice into the parent one.
								// copy the merge code from invoice.php
								if ( $debug ) {
									echo " - Merging Invoice " . module_invoice::link_open( $invoice_detail['invoice_id'], true ) . " into invoice " . module_invoice::link_open( $parent_invoice_id, true ) . " <br>\n";
								}
								$sql = "UPDATE `" . _DB_PREFIX . "invoice_item` SET invoice_id = " . (int) $parent_invoice_id . " WHERE invoice_id = " . (int) $invoice_detail['invoice_id'] . " ";
								query( $sql );
								$sql = "UPDATE `" . _DB_PREFIX . "subscription_history` SET invoice_id = " . (int) $parent_invoice_id . " WHERE invoice_id = " . (int) $invoice_detail['invoice_id'] . " ";
								query( $sql );
								if ( $debug ) {
									echo " - Deleting Invoice " . module_invoice::link_open( $invoice_detail['invoice_id'], true ) . "  <br>\n";
								}
								module_security::user_id_temp_set( 1 );
								module_invoice::delete_invoice( $invoice_detail['invoice_id'] );
								module_security::user_id_temp_restore();
							}

							if ( $invoice_detail['automatic_email'] ) {
								$automatic_email = true;
							}
						}
						if ( $parent_invoice_id && $automatic_email ) {
							// send this merged invoice to the customer.
							if ( $debug ) {
								echo " - emailing invoice " . module_invoice::link_open( $parent_invoice_id, true ) . "... <br>\n";
							}
							if ( module_invoice::email_invoice_to_customer( $parent_invoice_id, $debug ) ) {
								if ( $debug ) {
									echo "send successfully <br>\n";
								}
							} else {
								echo " - failed to send invoice " . module_invoice::link_open( $parent_invoice_id, true ) . "<br>\n";
							}
						}

					}
				}
			}
		}
	}

	private static function _calculate_next_time( $time, $subscription, $as_time_period = false ) {
		$next_time = $time;
		$next_time = strtotime( '+' . abs( (int) $subscription['days'] ) . ' days', $next_time );
		$next_time = strtotime( '+' . abs( (int) $subscription['months'] ) . ' months', $next_time );
		$next_time = strtotime( '+' . abs( (int) $subscription['years'] ) . ' years', $next_time );
		if ( $as_time_period ) {
			return ' (' . _l( '%s to %s', print_date( $time ), print_date( strtotime( "-1 day", $next_time ) ) ) . ')';
		}

		return $next_time;
	}

	public static function print_table_header( $owner_table, $options = array() ) {
		if ( self::can_i( 'view', 'Subscriptions' ) && module_config::c( 'subscription_show_in_table', 1 ) ) {
			?>
			<th>
				<?php _e( 'Subscription' ); ?>
			</th>
			<?php
		}
	}

	public static function print_table_data( $owner_table, $owner_id ) {
		if ( self::can_i( 'view', 'Subscriptions' ) && module_config::c( 'subscription_show_in_table', 1 ) ) {
			$extra_data = get_multiple( 'subscription_owner', array(
				'owner_table' => $owner_table,
				'owner_id'    => $owner_id,
				'deleted'     => 0
			), 'subscription_owner_id' );
			?>
			<td>
				<?php if ( $extra_data ) {
					foreach ( $extra_data as $e ) {
						$subscription = get_single( 'subscription', 'subscription_id', $e['subscription_id'] );
						if ( isset( $subscription['name'] ) ) {
							echo htmlspecialchars( $subscription['name'] ) . '<br/>';
						}
					}
				} ?>
			</td>
			<?php
		}
	}


	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( self::can_i( 'view', 'Subscriptions' ) ) {
			// get a list of active customers
			if ( is_numeric( $search_options['lookup'] ) ) {
				// we default o looking up the active subscriptions
				$subscription_id = (int) $search_options['lookup'];
				// grab the active ones
				$subscribed_owners = module_subscription::get_subscribed_owners( $subscription_id );
				foreach ( $subscribed_owners as $subscribed_owner ) {

					$history          = module_subscription::get_subscription_history( $subscription_id, $subscribed_owner['owner_table'], $subscribed_owner['owner_id'] );
					$has_paid_invoice = false;
					foreach ( $history as $h_id => $h ) {
						if ( $h['invoice_id'] ) {
							$invoice = module_invoice::get_invoice( $h['invoice_id'], true );
							if ( $invoice['date_cancel'] && $invoice['date_cancel'] != '0000-00-00' ) {
								// invoice cancelled, ignore from listing
								unset( $history[ $h_id ] );
								continue;
							}
						}
						if ( $h['paid_date'] && $h['paid_date'] != '0000-00-00' ) {
							$has_paid_invoice = true;
							break;
						}
					}
					if ( $has_paid_invoice ) {

						if ( ! empty( $subscribed_owner['next_due_date'] ) ) {
							$next_due_time = strtotime( $subscribed_owner['next_due_date'] );
							if ( $next_due_time >= time() ) {
								$key = $subscribed_owner['subscription_owner_id'];
								$val = '';
								switch ( $subscribed_owner['owner_table'] ) {
									case 'customer':
										$data = module_customer::get_customer( $subscribed_owner['owner_id'] );
										$val  = $data['customer_name'];
										break;
									case 'website':
										$data = module_website::get_website( $subscribed_owner['owner_id'] );
										$val  = $data['name'];
										break;
									case 'member':
										$data = module_member::get_member( $subscribed_owner['owner_id'] );
										$val  = $data['first_name'] . ' ' . $data['last_name'];
										break;
								}
								$result[] = array(
									'key'   => $key,
									'value' => $val
								);
							}
						}
					}
				}

			}
			switch ( $search_options['lookup'] ) {
				case 'active_subscriptions':
				default:

					break;
			}
		}

		// sort our results alphabetically.
		// this should probably be done in the parent autocomplete call.
		// so it happens for everything. oh well.
		usort( $result, array( $this, 'autocomplete_sort' ) );

		return $result;
	}

	public function autocomplete_sort( $a, $b ) {
		return strnatcasecmp( $a['value'], $b['value'] );
	}


	public function autocomplete_display( $key = 0, $search_options = array() ) {
		if ( self::can_i( 'view', 'Subscriptions' ) ) {
			if ( (int) $key > 0 ) {
				$subscribed_owner = get_single( 'subscription_owner', 'subscription_owner_id', $key );
				if ( $subscribed_owner ) {
					switch ( $subscribed_owner['owner_table'] ) {
						case 'customer':
							$data = module_customer::get_customer( $subscribed_owner['owner_id'] );

							return $data['customer_name'];
							break;
						case 'website':
							$data = module_website::get_website( $subscribed_owner['owner_id'] );

							return $data['name'];
							break;
						case 'member':
							$data = module_member::get_member( $subscribed_owner['owner_id'] );

							return $data['first_name'] . ' ' . $data['last_name'];
							break;
					}
				}
			}
		}

		return '';
	}


	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'subscription_history' );
		/*if(!isset($fields['member_id'])){
            $sql .= 'ALTER TABLE `'._DB_PREFIX.'subscription_history` ADD `member_id` INT(11) NOT NULL DEFAULT \'0\' AFTER `subscription_id`;';
        }
        if(!isset($fields['customer_id'])){
            $sql .= 'ALTER TABLE `'._DB_PREFIX.'subscription_history` ADD `customer_id` INT(11) NOT NULL DEFAULT \'0\' AFTER `member_id`;';
        }*/
		if ( ! isset( $fields['subscription_owner_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription_history` ADD `subscription_owner_id` INT(11) NOT NULL DEFAULT \'0\' AFTER `subscription_id`;';
		} else {
			self::add_table_index( 'subscription_history', 'subscription_owner_id' );
		}
		if ( ! isset( $fields['from_next_due_date'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription_history` ADD `from_next_due_date` DATE NOT NULL COMMENT \'what date was this invoice generated from\' AFTER `paid_date`;';
		}
		if ( ! self::db_table_exists( 'subscription_owner' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'subscription_owner` (
 `subscription_owner_id` int(11) NOT NULL auto_increment,
 `subscription_id` int(11) NOT NULL ,
  `owner_table` varchar(30) NOT NULL ,
  `owner_id` int(11) NOT NULL,
  `deleted` INT NOT NULL DEFAULT  \'0\',
`start_date` date NOT NULL,
`next_due_date` date NOT NULL COMMENT \'calculated in php when saving\',
`manual_next_due_date` tinyint(1) NOT NULL DEFAULT \'0\',
`use_as_credit_bucket` TINYINT NOT NULL DEFAULT  \'0\',
`recur_limit` INT(11) NOT NULL DEFAULT  \'0\',
  `date_created` date NOT NULL,
  `date_updated` date NULL,
  PRIMARY KEY  (`subscription_owner_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		} else {
			// make sure subscription_owner_id is auto incrementing, issue in old sql that stopped this
			$hack_sql = "SHOW FIELDS FROM `" . _DB_PREFIX . "subscription_owner`";
			$res      = qa( $hack_sql );
			$has_ai   = false;
			foreach ( $res as $r ) {
				if ( $r['Field'] == 'subscription_owner_id' ) {
					if ( isset( $r['Extra'] ) && $r['Extra'] == 'auto_increment' ) {
						$has_ai = true;
					}
				}
			}
			if ( ! $has_ai ) {
				$sql .= "UPDATE `" . _DB_PREFIX . "subscription_owner` SET  `subscription_owner_id` = 1 WHERE `subscription_owner_id` = 0; ";
				$sql .= "ALTER TABLE  `" . _DB_PREFIX . "subscription_owner` CHANGE  `subscription_owner_id`  `subscription_owner_id` INT( 11 ) NOT NULL AUTO_INCREMENT; ";
				//query($hack_sql);
			}
			$fields = get_fields( 'subscription_owner' );
			if ( ! isset( $fields['use_as_credit_bucket'] ) ) {
				$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription_owner` ADD `use_as_credit_bucket` TINYINT NOT NULL DEFAULT  \'0\' AFTER `manual_next_due_date`;';
			}
			if ( ! isset( $fields['recur_limit'] ) ) {
				$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription_owner` ADD `recur_limit` INT(11) NOT NULL DEFAULT  \'0\' AFTER `use_as_credit_bucket`;';
			}
		}
		/*$fields = get_fields('subscription_customer');
        if(!isset($fields['manual_next_due_date'])){
            $sql .= 'ALTER TABLE `'._DB_PREFIX.'subscription_customer` ADD `manual_next_due_date` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `next_due_date`;';
        }
        $fields = get_fields('subscription_member');
        if(!isset($fields['manual_next_due_date'])){
            $sql .= 'ALTER TABLE `'._DB_PREFIX.'subscription_member` ADD `manual_next_due_date` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `next_due_date`;';
        }*/

		$fields = get_fields( 'subscription' );
		if ( ! isset( $fields['automatic_renew'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription` ADD `automatic_renew` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `currency_id`;';
		}
		if ( ! isset( $fields['automatic_email'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription` ADD `automatic_email` tinyint(1) NOT NULL DEFAULT \'0\' AFTER `automatic_renew`;';
		}
		if ( ! isset( $fields['settings'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription` ADD `settings` TEXT NOT NULL DEFAULT \'\' AFTER `automatic_email`;';
		}
		if ( ! isset( $fields['invoice_prior_days'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'subscription` ADD `invoice_prior_days` INT(11) NOT NULL DEFAULT \'0\' AFTER `settings`;';
		}

		self::add_table_index( 'subscription', 'owner_id' );
		self::add_table_index( 'subscription', 'owner_table' );
		self::add_table_index( 'subscription_history', 'subscription_id' );
		self::add_table_index( 'subscription_history', 'subscription_owner_id' );
		self::add_table_index( 'subscription_history', 'invoice_id' );

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>subscription` (
		`subscription_id` int(11) NOT NULL auto_increment,
		`name` varchar(255) NOT NULL DEFAULT '',
		`days` int(11) NOT NULL DEFAULT '0',
		`months` int(11) NOT NULL DEFAULT '0',
		`years` int(11) NOT NULL DEFAULT '0',
		`amount` double(10,2) NOT NULL DEFAULT '0',
		`currency_id` INT NOT NULL DEFAULT '1',
		`automatic_renew` TINYINT(1) NOT NULL DEFAULT '0',
		`automatic_email` TINYINT(1) NOT NULL DEFAULT '0',
		`settings` TEXT NOT NULL DEFAULT '',
		`invoice_prior_days` INT(11) NOT NULL DEFAULT '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`subscription_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>subscription_owner` (
		`subscription_owner_id` int(11) NOT NULL auto_increment,
		`subscription_id` int(11) NOT NULL ,
		`owner_id` int(11) NOT NULL,
		`owner_table` varchar(30) NOT NULL,
		`deleted` INT NOT NULL DEFAULT  '0',
		`start_date` date NOT NULL,
		`next_due_date` date NOT NULL COMMENT 'calculated in php when saving',
		`manual_next_due_date` tinyint(1) NOT NULL DEFAULT '0',
		`use_as_credit_bucket` TINYINT NOT NULL DEFAULT  '0',
		`recur_limit` INT(11) NOT NULL DEFAULT  '0',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`subscription_owner_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>subscription_history` (
		`subscription_history_id` int(11) NOT NULL AUTO_INCREMENT,
		`subscription_id` int(11) NOT NULL DEFAULT '0',
		`subscription_owner_id` int(11) NOT NULL DEFAULT '0',
		`invoice_id` int(11) NOT NULL DEFAULT '0',
		`amount` double(10,2) NOT NULL DEFAULT '0',
		`currency_id` INT NOT NULL DEFAULT '1',
		`paid_date` DATE NOT NULL,
		`from_next_due_date` DATE NOT NULL COMMENT 'what date was this invoice generated from',
		`date_created` date NOT NULL,
		`date_updated` date DEFAULT NULL,
		PRIMARY KEY (`subscription_history_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


		<?php
		return ob_get_clean();
	}


}
