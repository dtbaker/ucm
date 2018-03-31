<?php


class module_finance extends module_base {


	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public $version = 2.311;
	// 2.311 - 2017-06-14 - tax report fixes
	// 2.310 - 2017-05-02 - big changes
	// 2.309 - 2017-01-12 - job finance totals
	// 2.308 - 2017-01-04 - searching extra fields
	// 2.307 - 2016-10-30 - dashboard fix
	// 2.306 - 2016-08-04 - speed fix
	// 2.305 - 2016-07-15 - finance recurring fix
	// 2.304 - 2016-07-10 - big update to mysqli
	// 2.303 - 2016-03-14 - edit dynamic select boxes
	// 2.302 - 2016-03-14 - date format
	// 2.301 - 2016-02-01 - customer summary
	// 2.300 - 2016-01-28 - dashboard popup fix
	// 2.299 - 2015-11-25 - dashboard bug fix
	// 2.298 - 2015-07-18 - finance tax report search fix
	// 2.297 - 2015-06-16 - finance menu fix
	// 2.296 - 2015-05-12 - added finance_date_type advanced field
	// 2.295 - 2015-03-15 - finance quick add
	// 2.294 - 2015-03-15 - finance quick add
	// 2.293 - 2014-12-22 - extra fields on finance transactions
	// 2.292 - 2014-11-27 - payment notes appear on finance
	// 2.291 - 2014-11-19 - fix for finance list when assigning customer credit / overpayments
	// 2.29 - 2014-09-28 - dashboard speed improvements with job value caching
	// 2.289 - 2014-09-02 - finance tax calculation javascript fix
	// 2.288 - 2014-08-18 - translation improvement
	// 2.287 - 2014-08-11 - dashboard widget improvements
	// 2.286 - 2014-08-05 - responsive improvements
	// 2.285 - 2014-07-26 - job split amounts
	// 2.284 - 2014-07-21 - dashboard alert permission fix
	// 2.283 - 2014-06-23 - tax report improvements
	// 2.282 - 2014-04-10 - speed improvements
	// 2.281 - 2014-04-03 - speed improvements
	// 2.28 - 2014-03-26 - summary of finances underneath finance listing and in CSV export
	// 2.279 - 2014-01-14 - search multiple account/categories
	// 2.278 - 2013-12-27 - extra fields display when creating finance records
	// 2.277 - 2013-12-01 - bug fixing
	// 2.276 - 2013-11-15 - working on new UI
	// 2.275 - 2013-11-11 - started work on invoice refunds
	// 2.274 - 2013-10-23 - dashboard invoiced column now based on create date
	// 2.273 - 2013-10-21 - dashboard hours logged fix
	// 2.272 - 2013-10-02 - deleting invoice payment improvements
	// 2.271 - 2013-10-02 - finance tax fix for linked transactions
	// 2.27 - 2013-10-01 - finance tax update
	// 2.269 - 2013-09-29 - finance tax update
	// 2.268 - 2013-09-29 - finance tax update
	// 2.267 - 2013-09-27 - finance customers and companies
	// 2.266 - 2013-09-26 - invoice finances
	// 2.265 - 2013-08-30 - better caching support

	// 2.2 - adding currency to finance options.
	// 2.21 - finance table sorting capability
	// 2.22 - dashbarods summary date translations
	// 2.23 - finance exporting and date searching
	// 2.24 - perms fix
	// 2.241 - another perms fix
	// 2.242 - added a hook to get a nicer subscription invoice printout
	// 2.243 - finance mobile fixes for dashboard
	// 2.244 - dashboard summary fixes
	// 2.245 - bug fix
	// 2.246 - link button fix
	// 2.247 - recurring alert fix when end date has passed.
	// 2.248 - save & next button on recording recurring payments
	// 2.249 - starting work on handling job deposits and customer credit
	// 2.250 - speed improvements
	// 2.251 - better finance / job integration
	// 2.252 - uploading images to finance items (eg: scanned receipts)
	// 2.253 - extra fields for finance items
	// 2.254 - extra fields update - show in main listing option
	// 2.255 - update for extra information on homepage
	// 2.256 - permissino fix on finance tab
	// 2.257 - 2013-04-10 - new customer permissions
	// 2.258 - 2013-05-02 - search upcoming
	// 2.259 - 2013-05-06 - date translation fix
	// 2.261 - 2013-06-21 - permission update
	// 2.262 - 2013-06-24 - search transaction list fix
	// 2.263 - 2013-08-08 - fix for hours on dashboard
	// 2.264 - 2013-08-12 - better searching in upcoming payments

	function init() {
		$this->links           = array();
		$this->module_name     = "finance";
		$this->module_position = 28;

		module_config::register_css( 'finance', 'finance.css' );
		module_config::register_js( 'finance', 'finance.js' );

		hook_add( 'invoice_payment_deleted', 'module_finance::hook_invoice_payment_deleted' );

	}

	public function pre_menu() {

		// the link within Admin > Settings > finances.
		if ( $this->can_i( 'view', 'Finance' ) && self::is_enabled() ) {
			$this->links[] = array(
				"name"      => "Finance",
				"p"         => "finance",
				"args"      => array( 'finance_id' => false ),
				'icon_name' => 'money',
			);
		}


		if ( module_security::has_feature_access( array(
				'name'        => 'Settings',
				'module'      => 'config',
				'category'    => 'Config',
				'view'        => 1,
				'description' => 'view',
			) ) && self::is_enabled() ) {
			$this->links[] = array(
				"name"                => "Finance",
				"p"                   => "finance_settings",
				"icon"                => "icon.png",
				"args"                => array( 'finance_id' => false ),
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}

	}

	public static function is_enabled() {
		return is_file( 'includes/plugin_finance/pages/finance.php' );
	}


	public static function link_generate( $finance_id = false, $options = array(), $link_options = array() ) {

		$key = 'finance_id';
		if ( $finance_id === false && $link_options ) {
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
			$options['type'] = 'finance';
		}
		if ( ! isset( $options['page'] ) ) {
			if ( $finance_id && ! isset( $link_options['stop_bubble'] ) ) {
				$options['page'] = 'finance_edit';
			} else {
				$options['page'] = 'finance';
			}
		}

		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['finance_id'] = $finance_id;
		$options['module']                  = 'finance';
		if ( isset( $options['data'] ) ) {
			$data = $options['data'];
		} else {
			$data = self::get_finance( $finance_id, false );
		}
		$options['data'] = $data;
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		if ( ( $options['page'] == 'recurring' || $options['page'] == 'finance_edit' ) && ! isset( $link_options['stop_bubble'] ) ) {
			$link_options['stop_bubble'] = true;
			$bubble_to_module            = array(
				'module'   => 'finance',
				'argument' => 'finance_id',
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

	public static function link_open( $finance_id, $full = false ) {
		return self::link_generate( $finance_id, array( 'full' => $full ) );
	}

	public static function link_open_recurring( $finance_recurring_id, $full = false, $data = array() ) {
		return self::link_generate( false, array(
			'full'      => $full,
			'page'      => 'recurring',
			'arguments' => array(
				'finance_recurring_id' => $finance_recurring_id,
			),
			'data'      => $data
		) );
	}

	public static function link_open_record_recurring( $finance_recurring_id, $full = false, $data = array() ) {
		return self::link_generate( 'new', array(
			'full'      => $full,
			'page'      => 'recurring',
			'arguments' => array(
				'record_new'           => 1,
				'finance_recurring_id' => $finance_recurring_id,
			),
			'data'      => $data
		) );
	}


	public function process() {
		switch ( $_REQUEST['_process'] ) {
			case 'quick_save_finance':


				if ( isset( $_REQUEST['link_go'] ) && $_REQUEST['link_go'] == 'go' ) {
					module_finance::handle_link_transactions();
				} else {
					// check for date / name at least.
					$date = trim( $_REQUEST['transaction_date'] );
					$name = trim( $_REQUEST['name'] );
					if ( ! $date || ! $name ) {
						redirect_browser( module_finance::link_open( false ) );
					}
					$credit = trim( $_REQUEST['credit'] );
					$debit  = trim( $_REQUEST['debit'] );
					if ( $credit > 0 ) {
						$_POST['type']   = 'i';
						$_POST['amount'] = $credit;
					} else {
						$_POST['type']   = 'e';
						$_POST['amount'] = $debit;
					}
				}

			case 'save_finance':
				if ( isset( $_REQUEST['butt_del'] ) ) {
					$this->delete( $_REQUEST['finance_id'] );
					redirect_browser( self::link_open( false ) );
				}
				if ( isset( $_REQUEST['butt_unlink'] ) ) {
					// unlink this finance_id from other finance_ids.
					$sql = "UPDATE `" . _DB_PREFIX . "finance` SET parent_finance_id = 0 WHERE parent_finance_id = '" . (int) $_REQUEST['finance_id'] . "'";
					query( $sql );
					$sql = "UPDATE `" . _DB_PREFIX . "invoice_payment` SET parent_finance_id = 0 WHERE parent_finance_id = '" . (int) $_REQUEST['finance_id'] . "'";
					query( $sql );
					redirect_browser( self::link_open( false ) );
				}
				$temp_data = $this->get_finance( $_REQUEST['finance_id'] );
				$data      = $_POST + $temp_data;
				// save the finance categories and account.
				$account_id = $_REQUEST['finance_account_id'];
				if ( (string) (int) $account_id != (string) $account_id && strlen( $account_id ) > 2 ) {
					// we have a new account to create.
					$account_id = update_insert( 'finance_account_id', 'new', 'finance_account', array( 'name' => $account_id ) );
				}
				$data['finance_account_id'] = $account_id;
				$finance_id                 = update_insert( 'finance_id', isset( $_REQUEST['finance_id'] ) ? $_REQUEST['finance_id'] : 'new', 'finance', $data );

				module_extra::save_extras( 'finance', 'finance_id', $finance_id );

				if ( ! isset( $data['tax_ids'] ) && isset( $data['taxes'] ) && is_array( $data['taxes'] ) ) {
					// default data when saving a new invoice payment to finance area
					$data['tax_ids']                = array();
					$data['tax_names']              = array();
					$data['tax_percents']           = array();
					$data['tax_increment_checkbox'] = 0;
					foreach ( $data['taxes'] as $tax ) {
						$data['tax_ids'][]      = false;
						$data['tax_names'][]    = $tax['name'];
						$data['tax_percents'][] = $tax['percent'];
						$data['tax_amount'][]   = $tax['amount'];
						if ( $tax['increment'] ) {
							$data['tax_increment_checkbox'] = 1;
						}
					}

				}
				// save the finance tax rates (copied from invoice.php)
				if ( isset( $data['tax_ids'] ) && isset( $data['tax_names'] ) && $data['tax_percents'] ) {
					$existing_taxes = get_multiple( 'finance_tax', array( 'finance_id' => $finance_id ), 'finance_tax_id', 'exact', 'order' );
					$order          = 1;
					foreach ( $data['tax_ids'] as $key => $val ) {
						if ( (int) $val > 0 && isset( $existing_taxes[ $val ] ) ) {
							// this means we are trying to update an existing record on the finance_tax table, we confirm this id matches this finance.
							$finance_tax_id = $val;
							unset( $existing_taxes[ $finance_tax_id ] ); // so we know which ones to remove from the end.
						} else {
							$finance_tax_id = false; // create new record
						}
						$finance_tax_data = array(
							'finance_id' => $finance_id,
							'percent'    => isset( $data['tax_percents'][ $key ] ) ? $data['tax_percents'][ $key ] : 0,
							'amount'     => isset( $data['tax_amount'][ $key ] ) ? $data['tax_amount'][ $key ] : 0,
							// calculated in js frontend.
							'name'       => isset( $data['tax_names'][ $key ] ) ? $data['tax_names'][ $key ] : 'TAX',
							'order'      => $order ++,
							'increment'  => isset( $data['tax_increment_checkbox'] ) && $data['tax_increment_checkbox'] ? 1 : 0,
						);
						$finance_tax_id   = update_insert( 'finance_tax_id', $finance_tax_id, 'finance_tax', $finance_tax_data );
					}
					foreach ( $existing_taxes as $existing_tax ) {
						delete_from_db( 'finance_tax', array( 'finance_id', 'finance_tax_id' ), array(
							$finance_id,
							$existing_tax['finance_tax_id']
						) );
					}
				}

				$category_ids = isset( $_REQUEST['finance_category_id'] ) && is_array( $_REQUEST['finance_category_id'] ) ? $_REQUEST['finance_category_id'] : array();
				$sql          = "DELETE FROM `" . _DB_PREFIX . "finance_category_rel` WHERE finance_id = $finance_id";
				query( $sql );
				foreach ( $category_ids as $category_id ) {
					$category_id = (int) $category_id;
					if ( $category_id <= 0 ) {
						continue;
					}
					$sql = "REPLACE INTO `" . _DB_PREFIX . "finance_category_rel` SET finance_id = $finance_id, finance_category_id = $category_id";
					query( $sql );
				}
				if ( isset( $_REQUEST['finance_category_new'] ) && strlen( trim( $_REQUEST['finance_category_new'] ) ) > 0 ) {
					$category_name = trim( $_REQUEST['finance_category_new'] );
					$category_id   = update_insert( 'finance_category_id', 'new', 'finance_category', array( 'name' => $category_name ) );
					if ( isset( $_REQUEST['finance_category_new_checked'] ) ) {
						$sql = "REPLACE INTO `" . _DB_PREFIX . "finance_category_rel` SET finance_id = $finance_id, finance_category_id = $category_id";
						query( $sql );
					}
				}

				if ( isset( $_REQUEST['invoice_payment_id'] ) && (int) $_REQUEST['invoice_payment_id'] > 0 ) {
					// link this as a child invoice payment to this one.
					update_insert( 'invoice_payment_id', $_REQUEST['invoice_payment_id'], 'invoice_payment', array( 'parent_finance_id' => $finance_id ) );
				}
				if ( isset( $_REQUEST['finance_recurring_id'] ) && (int) $_REQUEST['finance_recurring_id'] > 0 ) {
					// if we have set a custom "next recurring date" then we don't recalculate this date unless we are saving a new finance id.
					$recurring = self::get_recurring( $_REQUEST['finance_recurring_id'] );
					if ( ! (int) $_REQUEST['finance_id'] || ! $recurring['next_due_date_custom'] ) {
						self::calculate_recurring_date( (int) $_REQUEST['finance_recurring_id'], true );
					}
					// we also have to adjust the starting balance of our recurring amount by this amount.
					// just a little helpful feature.
					if ( ! (int) $_REQUEST['finance_id'] ) {
						$balance = module_config::c( 'finance_recurring_start_balance', 0 );
						if ( $balance != 0 ) {
							if ( $data['type'] == 'e' ) {
								$balance -= $data['amount'];
							} else if ( $data['type'] == 'i' ) {
								$balance += $data['amount'];
							}
							module_config::save_config( 'finance_recurring_start_balance', $balance );
						}
					}

					// redirect back to recurring listing.
					set_message( 'Recurring transaction saved successfully' );
					if ( isset( $_REQUEST['recurring_next'] ) && $_REQUEST['recurring_next'] ) {
						redirect_browser( $_REQUEST['recurring_next'] );
					}
					redirect_browser( self::link_open_recurring( false ) );
				}

				set_message( _l( 'Transaction saved successfully: %s', module_finance::link_open( $finance_id, true ) ) );
				if ( isset( $_REQUEST['job_id'] ) && (int) $_REQUEST['job_id'] > 0 ) {
					redirect_browser( module_job::link_open( (int) $_REQUEST['job_id'] ) );
				}
				if ( isset( $_REQUEST['butt_save_return'] ) ) {
					if ( isset( $_REQUEST['_redirect'] ) && strlen( $_REQUEST['_redirect'] ) ) {
						redirect_browser( $_REQUEST['_redirect'] );
					}
					redirect_browser( self::link_open( false, false ) );
				}
				if ( $_REQUEST['_process'] == 'quick_save_finance' ) {
					redirect_browser( self::link_open( false, false ) );
				}
				redirect_browser( self::link_open( $finance_id, false ) );
				break;
			case 'save_recurring':
				if ( isset( $_REQUEST['butt_del'] ) ) {
					$this->delete_recurring( $_REQUEST['finance_recurring_id'] );
					redirect_browser( self::link_open_recurring( false ) );
				}
				$data = $_POST;
				// save the finance categories and account.
				$account_id = $_REQUEST['finance_account_id'];
				if ( (string) (int) $account_id != (string) $account_id && strlen( $account_id ) > 2 ) {
					// we have a new account to create.
					$account_id = update_insert( 'finance_account_id', 'new', 'finance_account', array( 'name' => $account_id ) );
				}
				if ( isset( $_REQUEST['finance_recurring_id'] ) && (int) $_REQUEST['finance_recurring_id'] ) {
					$original_finance_recurring = self::get_recurring( $_REQUEST['finance_recurring_id'] );
				} else {
					$original_finance_recurring = array();
				}

				$data['finance_account_id'] = $account_id;
				$finance_recurring_id       = update_insert( 'finance_recurring_id', isset( $_REQUEST['finance_recurring_id'] ) ? $_REQUEST['finance_recurring_id'] : 'new', 'finance_recurring', $data );

				if ( (int) $finance_recurring_id > 0 ) {
					$category_ids = isset( $_REQUEST['finance_category_id'] ) && is_array( $_REQUEST['finance_category_id'] ) ? $_REQUEST['finance_category_id'] : array();
					$sql          = "DELETE FROM `" . _DB_PREFIX . "finance_recurring_catrel` WHERE finance_recurring_id = $finance_recurring_id";
					query( $sql );
					foreach ( $category_ids as $category_id ) {
						$category_id = (int) $category_id;
						if ( $category_id <= 0 ) {
							continue;
						}
						$sql = "REPLACE INTO `" . _DB_PREFIX . "finance_recurring_catrel` SET finance_recurring_id = $finance_recurring_id, finance_category_id = $category_id";
						query( $sql );
					}
					if ( isset( $_REQUEST['finance_category_new'] ) && strlen( trim( $_REQUEST['finance_category_new'] ) ) > 0 ) {
						$category_name = trim( $_REQUEST['finance_category_new'] );
						$category_id   = update_insert( 'finance_category_id', 'new', 'finance_category', array( 'name' => $category_name ) );
						if ( isset( $_REQUEST['finance_category_new_checked'] ) ) {
							$sql = "REPLACE INTO `" . _DB_PREFIX . "finance_recurring_catrel` SET finance_recurring_id = $finance_recurring_id, finance_category_id = $category_id";
							query( $sql );
						}
					}
					$calculated_next_date = self::calculate_recurring_date( $finance_recurring_id );

					if ( isset( $data['set_next_due_date'] ) && $data['set_next_due_date'] ) {
						$next_date          = input_date( $data['set_next_due_date'] );
						$next_due_date_real = module_finance::calculate_recurring_date( $finance_recurring_id, true, false );
						if ( $next_date != $next_due_date_real ) {
							// we have accustom date.
							update_insert( 'finance_recurring_id', $finance_recurring_id, 'finance_recurring', array(
									'next_due_date'        => $next_date,
									'next_due_date_custom' => 1,
								)
							);
						} else {
							// date is the same. not doing a custom date any more
							update_insert( 'finance_recurring_id', $finance_recurring_id, 'finance_recurring', array(
									'next_due_date'        => $next_due_date_real,
									'next_due_date_custom' => 0,
								)
							);
						}
					}
					/*
                    $finance_recurring = self::get_recurring($finance_recurring_id);
                    if($finance_recurring['next_due_date_custom']){
                        $next_due_date_real = module_finance::calculate_recurring_date($finance_recurring_id,true,false);
                        // unset the "custom" flag if we've picked the same date as what it should be.
                        if($next_due_date_real == $finance_recurring['next_due_date']){
                            module_finance::calculate_recurring_date($finance_recurring_id,true,true);
                        }
                    }*/
				}


				set_message( 'Recurring transaction saved successfully' );
				//redirect_browser(self::link_open($finance_id,false));
				redirect_browser( self::link_open_recurring( false, false ) );
				break;
		}

	}

	public static function delete( $finance_id ) {
		$finance_id = (int) $finance_id;
		if ( $finance_id > 0 ) {
			$finance = self::get_finance( $finance_id );
			if ( $finance && $finance['finance_id'] == $finance_id ) {
				$sql = "DELETE FROM " . _DB_PREFIX . "finance WHERE finance_id = '" . $finance_id . "' LIMIT 1";
				query( $sql );
				$sql = "DELETE FROM " . _DB_PREFIX . "finance_category_rel WHERE finance_id = '" . $finance_id . "'";
				query( $sql );
				$sql = "UPDATE " . _DB_PREFIX . "finance SET parent_finance_id = 0 WHERE parent_finance_id = '" . $finance_id . "'";
				query( $sql );
				$sql = "UPDATE " . _DB_PREFIX . "invoice_payment SET parent_finance_id = 0 WHERE parent_finance_id = '" . $finance_id . "'";
				query( $sql );
				if ( isset( $finance['finance_recurring_id'] ) && $finance['finance_recurring_id'] ) {
					self::calculate_recurring_date( $finance['finance_recurring_id'], true );
				}
			}
		}
	}


	public static function get_finance( $finance_id, $full = true, $invoice_payment_id = false ) {
		if ( ! $invoice_payment_id ) {
			$invoice_payment_id = isset( $_REQUEST['invoice_payment_id'] ) && (int) $_REQUEST['invoice_payment_id'] > 0 ? (int) $_REQUEST['invoice_payment_id'] : false;
		}
		$finance_id = (int) $finance_id;
		if ( $finance_id > 0 ) {
			if ( ! $full ) {
				return get_single( "finance", "finance_id", $finance_id );
			}

			$sql        = "SELECT f.* ";
			$sql        .= " , fa.name AS account_name ";
			$sql        .= " , GROUP_CONCAT(fc.`name` ORDER BY fc.`name` ASC SEPARATOR ', ') AS categories ";
			$sql        .= " FROM `" . _DB_PREFIX . "finance` f ";
			$sql        .= " LEFT JOIN `" . _DB_PREFIX . "finance_account` fa USING (finance_account_id) ";
			$sql        .= " LEFT JOIN `" . _DB_PREFIX . "finance_category_rel` fcr ON f.finance_id = fcr.finance_id ";
			$sql        .= " LEFT JOIN `" . _DB_PREFIX . "finance_category` fc ON fcr.finance_category_id = fc.finance_category_id ";
			$sql        .= " WHERE f.finance_id = $finance_id ";
			$sql        .= " GROUP BY f.finance_id ";
			$sql        .= " ORDER BY f.transaction_date DESC ";
			$finance    = qa1( $sql );
			$finance_id = $finance['finance_id'];

			// get the categories.
			$finance['category_ids'] = get_multiple( 'finance_category_rel', array( 'finance_id' => $finance_id ), 'finance_category_id' );
			$finance['taxes']        = get_multiple( 'finance_tax', array( 'finance_id' => $finance_id ), 'finance_tax_id', 'exact', 'order' );

			// get any linked items.

			$linked_finances = $linked_invoice_payments = array();
			// find any child / linked transactions to this one.
			if ( (int) $finance_id > 0 && isset( $finance['parent_finance_id'] ) && $finance['parent_finance_id'] > 0 ) {
				// todo - this could cause problems! 
				$foo = module_finance::get_finance( $finance['parent_finance_id'], false );
				if ( $foo['finance_id'] != $finance_id ) {
					// copied from get_finances() method
					$foo['url']    = module_finance::link_open( $foo['finance_id'], false );
					$foo['credit'] = $foo['type'] == 'i' ? $foo['amount'] : 0;
					$foo['debit']  = $foo['type'] == 'e' ? $foo['amount'] : 0;
					if ( ! isset( $foo['categories'] ) ) {
						$foo['categories'] = '';
					}
					if ( ! isset( $foo['account_name'] ) ) {
						$foo['account_name'] = '';
					}
					$linked_finances[] = $foo;
				}
				// find any child finances that are also linked to this parent finance.
				foreach ( module_finance::get_finances_simple( array( 'parent_finance_id' => $finance['parent_finance_id'] ) ) as $foo ) {
					if ( $foo['finance_id'] != $finance_id ) {
						// copied from get_finances() method
						$foo['url']    = module_finance::link_open( $foo['finance_id'], false );
						$foo['credit'] = $foo['type'] == 'i' ? $foo['amount'] : 0;
						$foo['debit']  = $foo['type'] == 'e' ? $foo['amount'] : 0;
						if ( ! isset( $foo['categories'] ) ) {
							$foo['categories'] = '';
						}
						if ( ! isset( $foo['account_name'] ) ) {
							$foo['account_name'] = '';
						}
						$linked_finances[] = $foo;
					}
				}
				// find any child invoice payments that are also linked to this parent finance
				foreach ( get_multiple( 'invoice_payment', array( 'parent_finance_id' => $finance['parent_finance_id'] ) ) as $invoice_payments ) {

					if (
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_NORMAL ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_REFUND
					) {
						$invoice_payments = module_invoice::get_invoice_payment( $invoice_payments['invoice_payment_id'] );
						// copied from get_finances() method
						$invoice_payments                                                    = self::_format_invoice_payment( $invoice_payments, $finance );
						$linked_invoice_payments [ $invoice_payments['invoice_payment_id'] ] = $invoice_payments;
					}
				}
			}
			if ( (int) $finance_id > 0 ) {
				// find any child finances that are linked to this finance.
				foreach ( module_finance::get_finances_simple( array( 'parent_finance_id' => $finance_id ) ) as $foo ) {
					if ( $foo['finance_id'] != $finance_id ) {
						// copied from get_finances() method
						$foo['url']    = module_finance::link_open( $foo['finance_id'], false );
						$foo['credit'] = $foo['type'] == 'i' ? $foo['amount'] : 0;
						$foo['debit']  = $foo['type'] == 'e' ? $foo['amount'] : 0;
						if ( ! isset( $foo['categories'] ) ) {
							$foo['categories'] = '';
						}
						if ( ! isset( $foo['account_name'] ) ) {
							$foo['account_name'] = '';
						}
						$linked_finances[] = $foo;
					}
				}
				// find any child invoice payments that are also linked to this parent finance
				foreach ( get_multiple( 'invoice_payment', array( 'parent_finance_id' => $finance_id ) ) as $invoice_payments ) {
					if (
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_NORMAL ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT ||
						$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_REFUND
					) {
						// copied from get_finances() method
						$invoice_payments = module_invoice::get_invoice_payment( $invoice_payments['invoice_payment_id'] );
						$invoice_payments = self::_format_invoice_payment( $invoice_payments, $finance );
						// hack to pull tax information from a linked invoice payment to replace current items tax if none is defined
						if ( ! $finance['taxes'] && count( $invoice_payments['taxes'] ) && $invoice_payments['amount'] == $finance['amount'] ) {
							$finance['taxes']          = $invoice_payments['taxes'];
							$finance['taxable_amount'] = $invoice_payments['taxable_amount'];
							$finance['sub_amount']     = $invoice_payments['sub_amount'];
						}
						$linked_invoice_payments [ $invoice_payments['invoice_payment_id'] ] = $invoice_payments;
					}
				}
				if ( isset( $finance['invoice_payment_id'] ) && $finance['invoice_payment_id'] > 0 ) {
					$invoice_payments = module_invoice::get_invoice_payment( $finance['invoice_payment_id'] );
					if (
						$invoice_payments &&
						(
							$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_NORMAL ||
							$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT ||
							$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT ||
							$invoice_payments['payment_type'] == _INVOICE_PAYMENT_TYPE_REFUND
						)
					) {
						$invoice_payments = self::_format_invoice_payment( $invoice_payments, $finance );
						// hack to pull tax information from a linked invoice payment to replace current items tax if none is defined
						if ( ! $finance['taxes'] && count( $invoice_payments['taxes'] ) && $invoice_payments['amount'] == $finance['amount'] ) {
							$finance['taxes']          = $invoice_payments['taxes'];
							$finance['taxable_amount'] = $invoice_payments['taxable_amount'];
							$finance['sub_amount']     = $invoice_payments['sub_amount'];
						}
						$linked_invoice_payments [ $invoice_payments['invoice_payment_id'] ] = $invoice_payments;
					} else if ( ! $invoice_payments ) {
						// todo: this shou;ldnt happen, fix!
					}
				}
			}

			$finance['linked_invoice_payments'] = $linked_invoice_payments;
			$finance['linked_finances']         = $linked_finances;


		}
		if ( $finance_id <= 0 ) {

			$finance = array(
				'finance_id'        => 0,
				'parent_finance_id' => 0,
				'transaction_date'  => print_date( time() ),
				'name'              => '',
				'description'       => '',
				'type'              => 'e',
				'sub_amount'        => 0,
				'taxable_amount'    => 0,
				'tax_mode'          => module_config::c( 'finance_default_tax_mode', 0 ),
				'taxes'             => array(),
				'amount'            => 0,
				'currency_id'       => module_config::c( 'default_currency_id', 1 ),
				'category_ids'      => array(),
				'customer_id'       => 0,
				'job_id'            => 0,
				'invoice_id'        => 0,
				'job_staff_expense' => 0,
				'user_id'           => 0,
			);
			if ( isset( $_REQUEST['from_job_id'] ) ) {
				$job_data          = module_job::get_job( (int) $_REQUEST['from_job_id'] );
				$finance['job_id'] = $job_data['job_id'];
				if ( $job_data['customer_id'] ) {
					$finance['customer_id'] = $job_data['customer_id'];
				}
				if ( isset( $_REQUEST['job_staff_expense'] ) && (int) $_REQUEST['job_staff_expense'] > 0 ) {
					// we have a job staff expense, load up the job tasks for this staff member and find out the cost.
					if ( isset( $job_data['staff_total_grouped'][ $_REQUEST['job_staff_expense'] ] ) ) {
						$staff_member = module_user::get_user( $_REQUEST['job_staff_expense'] );
						if ( $staff_member && $staff_member['user_id'] == $_REQUEST['job_staff_expense'] ) {
							// valid job found, load in the defaults.
							$finance['name']                 = $job_data['name'];
							$finance['description']          = _l( 'Job Expense For Staff Member: %s', $staff_member['name'] . ' ' . $staff_member['last_name'] );
							$finance['type']                 = 'e';
							$finance['amount']               = $job_data['staff_total_grouped'][ $_REQUEST['job_staff_expense'] ];
							$finance['taxes']                = array();
							$finance['job_staff_expense_id'] = $job_data['job_id'];
							$finance['job_id']               = $job_data['job_id'];
							$finance['currency_id']          = $job_data['currency_id'];
							$finance['transaction_date']     = print_date( $job_data['date_completed'] );
							$finance['user_id']              = $staff_member['user_id'];
							$finance['job_staff_expense']    = $staff_member['user_id'];
						}
					}
				}
			}
			if ( ! $full ) {
				return $finance;
			}
			if ( $invoice_payment_id && $invoice_payment_id > 0 ) {
				$invoice_payment_data = module_invoice::get_invoice_payment( $invoice_payment_id );
				if ( $invoice_payment_data && $invoice_payment_data['invoice_id'] ) {
					$finance = self::_format_invoice_payment( $invoice_payment_data, $finance );

					$finance['invoice_id']  = $invoice_payment_data['invoice_id'];
					$finance['currency_id'] = $invoice_payment_data['currency_id'];

				}
			}
		}
		if ( isset( $finance['invoice_id'] ) && $finance['invoice_id'] ) {
			$new_finance = hook_handle_callback( 'finance_invoice_listing', $finance['invoice_id'], $finance );
			if ( is_array( $new_finance ) && count( $new_finance ) ) {
				foreach ( $new_finance as $n ) {
					$finance = array_merge( $finance, $n );
				}
			}
		}
		$finance['taxes'] = self::sanatise_taxes( isset( $finance['taxes'] ) ? $finance['taxes'] : array(), isset( $finance['taxable_amount'] ) ? $finance['taxable_amount'] : 0 );

		return $finance;
	}

	private static function _format_invoice_payment( $invoice_payment_data, $finance_data ) {

		if ( isset( $invoice_payment_data['invoice_payment_id'] ) && $invoice_payment_data['invoice_payment_id'] > 0 && isset( $invoice_payment_data['invoice_id'] ) && $invoice_payment_data['invoice_id'] > 0 ) {
			$invoice_data                = module_invoice::get_invoice( $invoice_payment_data['invoice_id'] );
			$invoice_payment_data['url'] = module_finance::link_open( 'new', false ) . '&invoice_payment_id=' . $invoice_payment_data['invoice_payment_id'];
			if ( $invoice_payment_data['amount'] < 0 && ( isset( $invoice_payment_data['payment_type'] ) && $invoice_payment_data['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT ) ) {
				$invoice_payment_data['name']        = ! isset( $invoice_payment_data['name'] ) ? _l( 'Assigning Credit' ) : $invoice_payment_data['name'];
				$invoice_payment_data['description'] = ! isset( $invoice_payment_data['description'] ) ? _l( 'Assigning Overpayment Credit from invoice <a href="%s">#%s</a>', module_invoice::link_open( $invoice_payment_data['invoice_id'], false ), $invoice_data['name'], $invoice_payment_data['method'] ) : $invoice_payment_data['description'];
				// refund
				$invoice_payment_data['amount']         = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['debit']          = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['sub_amount']     = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['taxable_amount'] = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['credit']         = 0;
				$invoice_payment_data['type']           = 'e';
			} else if ( $invoice_payment_data['amount'] < 0 || ( isset( $invoice_payment_data['payment_type'] ) && $invoice_payment_data['payment_type'] == _INVOICE_PAYMENT_TYPE_REFUND ) ) {
				$invoice_payment_data['name']        = ! isset( $invoice_payment_data['name'] ) ? _l( 'Invoice Refund' ) : $invoice_payment_data['name'];
				$invoice_payment_data['description'] = ! isset( $invoice_payment_data['description'] ) ? _l( 'Refund against invoice <a href="%s">#%s</a> via "%s" method', module_invoice::link_open( $invoice_payment_data['invoice_id'], false ), $invoice_data['name'], $invoice_payment_data['method'] ) : $invoice_payment_data['description'];
				// refund
				$invoice_payment_data['amount']         = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['debit']          = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['sub_amount']     = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['taxable_amount'] = abs( $invoice_payment_data['amount'] );
				$invoice_payment_data['credit']         = 0;
				$invoice_payment_data['type']           = 'e';
			} else {
				$invoice_payment_data['name']           = ! isset( $invoice_payment_data['name'] ) ? _l( 'Invoice Payment' ) : $invoice_payment_data['name'];
				$invoice_payment_data['description']    = ! isset( $invoice_payment_data['description'] ) ? _l( 'Payment against invoice <a href="%s">#%s</a> via "%s" method', module_invoice::link_open( $invoice_payment_data['invoice_id'], false ), $invoice_data['name'], $invoice_payment_data['method'] ) : $invoice_payment_data['description'];
				$invoice_payment_data['credit']         = $invoice_payment_data['amount'];
				$invoice_payment_data['sub_amount']     = $invoice_payment_data['amount'];
				$invoice_payment_data['taxable_amount'] = $invoice_payment_data['amount'];
				$invoice_payment_data['debit']          = 0;
				$invoice_payment_data['type']           = 'i';
			}

			if ( isset( $invoice_payment_data['data'] ) && strlen( $invoice_payment_data['data'] ) ) {
				$details = @unserialize( $invoice_payment_data['data'] );
				if ( $details && isset( $details['custom_notes'] ) && strlen( $details['custom_notes'] ) ) {
					$invoice_payment_data['description'] .= " \n(" . $details['custom_notes'] . ')';
				}
			}

			$invoice_payment_data['account_name'] = '';
			$invoice_payment_data['categories']   = '';
			if ( module_config::c( 'finance_date_type', 'payment' ) == 'invoice' ) {
				$invoice_payment_data['transaction_date'] = $invoice_data['date_create'];
			} else {
				$invoice_payment_data['transaction_date'] = $invoice_payment_data['date_paid'];
			}

			$invoice_payment_data['invoice_name'] = $invoice_data['name'];
			$invoice_payment_data['taxes']        = $invoice_data['taxes'];

			// calculate the sub amount based on taxes.
			if ( $invoice_payment_data['amount'] > $invoice_data['total_amount'] ) {
				// user overpaid this invoice amount.
				// check if there hasn't been any refunds or anything or assigning deposits.

			} else if ( $invoice_payment_data['amount'] == $invoice_data['total_amount'] ) {
				// then we can work out any sub non taxable items.
				if ( $invoice_data['total_tax'] > 0 ) {
					//$finance['sub_amount'] = $finance['amount'] - $invoice_data['total_tax'];
					// todo: cache these and do a get_invoice basic above so we don't calculate each time.
					$invoice_payment_data['sub_amount']     = $invoice_data['total_sub_amount'];
					$invoice_payment_data['taxable_amount'] = $invoice_data['total_sub_amount_taxable'];
				}
			} else {
				// todo: average out the difference between invoice payments and the total amount? spread the tax over all payments maybe?
				if ( count( $invoice_payment_data['taxes'] ) ) {
					$tax_percents = 0;
					$increment    = false;
					foreach ( $invoice_payment_data['taxes'] as $tax_id => $tax ) {
						if ( $tax['increment'] ) {
							$increment = true;
						}
					}
					foreach ( $invoice_payment_data['taxes'] as $tax_id => $tax ) {
						// the 'amount' of tax here will be incorrect, because this is a part payment against an invoice
						// the 'amount' in here is the FULL amount of tax that has been charged against the invoice
						$invoice_payment_data['taxes'][ $tax_id ]['amount'] = 0;
						if ( $increment ) {
							$invoice_payment_data['taxable_amount'] = $invoice_payment_data['taxable_amount'] / ( 1 + ( $tax['percent'] / 100 ) );
						} else {
							$tax_percents += ( $tax['percent'] / 100 );
						}
					}
					$invoice_payment_data['taxable_amount'] = round( ( $invoice_payment_data['taxable_amount'] / ( 1 + ( $tax_percents ) ) ) * 100, 2 ) / 100;
					$invoice_payment_data['sub_amount']     = $invoice_payment_data['taxable_amount'];
				}
			}
			$new_finance = hook_handle_callback( 'finance_invoice_listing', $invoice_payment_data['invoice_id'], $finance_data );
			if ( is_array( $new_finance ) && count( $new_finance ) ) {
				foreach ( $new_finance as $n ) {
					$invoice_payment_data = array_merge( $invoice_payment_data, $n );
				}
			}
		}

		return $invoice_payment_data;
	}

	public static function sanatise_taxes( $taxes, $taxable_amount ) {
		if ( ! $taxes ) {
			return array();
		}
		if ( ! $taxable_amount ) {
			return $taxes;
		} // not sure about this.

		$increment = false; // incremental or not.
		foreach ( $taxes as $tax ) {
			if ( isset( $tax['increment'] ) && $tax['increment'] ) {
				$increment = true;
			}
		}
		$tax_percents = 0;
		foreach ( $taxes as $tax_id => $tax_data ) {
			// make sure there is an 'amount' for each tax data
			if ( ! isset( $tax_data['amount'] ) || $tax_data['amount'] <= 0 ) {
				$taxes[ $tax_id ]['amount'] = 0;
				if ( $tax_data['percent'] > 0 ) {
					$taxes[ $tax_id ]['amount'] = $taxable_amount * ( $tax_data['percent'] / 100 );
					if ( $increment ) {
						$taxable_amount = $taxable_amount / ( 1 + ( $tax_data['percent'] / 100 ) );
					} else {

					}
				}
			}
		}

		return $taxes;
	}

	public static function get_recurrings( $search ) {
		$sql = "SELECT r.*  ";
		$sql .= ", f.amount AS last_amount ";
		$sql .= ", f.transaction_date AS last_transaction_date ";
		$sql .= ", f.finance_id AS last_transaction_finance_id ";
		$sql .= " , fa.name AS account_name ";
		$sql .= " , (SELECT GROUP_CONCAT(fc.`name` ORDER BY fc.`name` ASC SEPARATOR ', ') FROM `" . _DB_PREFIX . "finance_recurring_catrel` fcr LEFT JOIN `" . _DB_PREFIX . "finance_category` fc ON fcr.finance_category_id = fc.finance_category_id WHERE fcr.finance_recurring_id = r.finance_recurring_id) AS categories";
		$sql .= " FROM `" . _DB_PREFIX . "finance_recurring` r ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "finance` f ON r.finance_recurring_id = f.finance_recurring_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "finance_account` fa ON r.finance_account_id = fa.finance_account_id ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "finance_recurring_catrel` fcr2 ON r.finance_recurring_id = fcr2.finance_recurring_id ";
		$sql .= " WHERE 1";
		$sql .= " AND ( f.finance_id IS NULL or f.finance_id = (SELECT ff.finance_id FROM `" . _DB_PREFIX . "finance` ff WHERE ff.finance_recurring_id = r.finance_recurring_id ORDER BY transaction_date DESC LIMIT 1) )";
		if ( isset( $search['finance_account_id'] ) && $search['finance_account_id'] > 0 ) {
			$sql .= " AND r.finance_account_id = " . (int) $search['finance_account_id'];
		}
		if ( isset( $search['finance_category_id'] ) && $search['finance_category_id'] > 0 ) {
			$sql .= " AND fcr2.finance_category_id = " . (int) $search['finance_category_id'];
		}
		if ( isset( $search['show_finished'] ) && $search['show_finished'] ) {
			$sql .= " ";
		} else {
			$sql .= " AND r.next_due_date IS NOT NULL AND r.next_due_date != '0000-00-00' ";
		}
		$sql .= " GROUP BY r.finance_recurring_id ";
		$sql .= " ORDER BY next_due_date ASC ";

		return qa( $sql );
		//return get_multiple('finance_recurring',$search,'finance_recurring_id');
	}

	public static function get_recurring( $finance_recurring_id ) {
		// show last transaction etc..
		$finance_recurring_id = (int) $finance_recurring_id;
		if ( $finance_recurring_id > 0 ) {
			//return get_single('finance_recurring','finance_recurring_id',$finance_recurring_id);

			$sql                       = "SELECT r.*  ";
			$sql                       .= ", f.amount AS last_amount ";
			$sql                       .= ", f.transaction_date AS last_transaction_date ";
			$sql                       .= ", f.finance_id AS last_transaction_finance_id ";
			$sql                       .= " , fa.name AS account_name ";
			$sql                       .= " , (SELECT GROUP_CONCAT(fc.`name` ORDER BY fc.`name` ASC SEPARATOR ', ') FROM `" . _DB_PREFIX . "finance_recurring_catrel` fcr LEFT JOIN `" . _DB_PREFIX . "finance_category` fc ON fcr.finance_category_id = fc.finance_category_id WHERE fcr.finance_recurring_id = r.finance_recurring_id) AS categories";
			$sql                       .= " FROM `" . _DB_PREFIX . "finance_recurring` r ";
			$sql                       .= " LEFT JOIN `" . _DB_PREFIX . "finance` f ON r.finance_recurring_id = f.finance_recurring_id ";
			$sql                       .= " LEFT JOIN `" . _DB_PREFIX . "finance_account` fa ON r.finance_account_id = fa.finance_account_id ";
			$sql                       .= " WHERE 1";
			$sql                       .= " AND ( f.finance_id IS NULL or f.finance_id = (SELECT ff.finance_id FROM `" . _DB_PREFIX . "finance` ff WHERE ff.finance_recurring_id = r.finance_recurring_id ORDER BY transaction_date DESC LIMIT 1) )";
			$sql                       .= " AND r.finance_recurring_id = $finance_recurring_id";
			$recurring                 = qa1( $sql );
			$recurring['category_ids'] = get_multiple( 'finance_recurring_catrel', array( 'finance_recurring_id' => $finance_recurring_id ), 'finance_category_id' );

			return $recurring;
		} else {
			return array(
				'name'               => '',
				'description'        => '',
				'finance_account_id' => '',
				'start_date'         => '',
				'end_date'           => '',
				'amount'             => '',
				'currency_id'        => module_config::c( 'default_currency_id', 1 ),
				'days'               => '0',
				'months'             => '0',
				'years'              => '0',
				'type'               => 'e',
				'category_ids'       => array(),
			);
		}

	}

	public static function get_finances_simple( $search ) {
		return get_multiple( 'finance', $search, 'finance_id' );
	}

	public static function get_finances( $search = array() ) {
		// we have to search for recent transactions. this involves combining the "finance" table with the "invoice_payment" table
		// then sort the results by date
		$hide_invoice_payments = false;
		$sql                   = "SELECT f.* ";
		$sql                   .= " , fa.name AS account_name ";
		$sql                   .= " , GROUP_CONCAT(fc.`name` ORDER BY fc.`name` ASC SEPARATOR ', ') AS categories ";
		$sql                   .= " FROM `" . _DB_PREFIX . "finance` f ";
		$sql                   .= " LEFT JOIN `" . _DB_PREFIX . "finance_account` fa USING (finance_account_id) ";
		$sql                   .= " LEFT JOIN `" . _DB_PREFIX . "finance_category_rel` fcr ON f.finance_id = fcr.finance_id ";
		$sql                   .= " LEFT JOIN `" . _DB_PREFIX . "finance_category` fc ON fcr.finance_category_id = fc.finance_category_id ";
		$where                 = " WHERE 1 ";

		if ( isset( $search['finance_account_id'] ) && is_array( $search['finance_account_id'] ) ) {
			$fo = array();
			foreach ( $search['finance_account_id'] as $val ) {
				if ( (int) $val > 0 ) {
					$fo[ (int) $val ] = true;
				}
			}
			if ( count( $fo ) > 0 ) {
				$where .= " AND ( ";
				foreach ( $fo as $f => $ff ) {
					$where .= " f.finance_account_id = " . $f . ' OR';
				}
				$where                 = rtrim( $where, 'OR' );
				$where                 .= ' )';
				$hide_invoice_payments = true;
			}
		}
		if ( isset( $search['finance_recurring_id'] ) && $search['finance_recurring_id'] ) {
			$where                 .= " AND f.finance_recurring_id = '" . (int) $search['finance_recurring_id'] . "'";
			$hide_invoice_payments = true;
		}
		if ( isset( $search['finance_category_id'] ) && is_array( $search['finance_category_id'] ) ) {
			$fo = array();
			foreach ( $search['finance_category_id'] as $val ) {
				if ( (int) $val > 0 ) {
					$fo[ (int) $val ] = true;
				}
			}
			if ( count( $fo ) > 0 ) {
				$where .= " AND EXISTS ( SELECT * FROM `" . _DB_PREFIX . "finance_category_rel` fcr2 WHERE fcr2.finance_id = f.finance_id AND ( ";
				foreach ( $fo as $f => $ff ) {
					$where .= " fcr2.finance_category_id = " . $f . ' OR';
				}
				$where                 = rtrim( $where, 'OR' );
				$where                 .= ' )';
				$where                 .= ' )';
				$hide_invoice_payments = true;
			}
		}
		if ( isset( $search['invoice_payment_id'] ) && $search['invoice_payment_id'] ) {
			$where                 .= " AND f.invoice_payment_id = '" . (int) $search['invoice_payment_id'] . "'";
			$hide_invoice_payments = true;
		}

		// below 6 searches are repeated again below in invoice payments
		if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
			$where .= " AND f.`job_id` = " . (int) $search['job_id'];
		}
		if ( isset( $search['invoice_id'] ) && (int) $search['invoice_id'] > 0 ) {
			$where .= " AND f.`invoice_id` = " . (int) $search['invoice_id'];
		}
		if ( isset( $search['customer_id'] ) && (int) $search['customer_id'] > 0 ) {
			$where .= " AND f.`customer_id` = " . (int) $search['customer_id'];
		}
		if ( isset( $search['company_id'] ) && (int) $search['company_id'] > 0 ) {
			// check this user can view this company id or not
			if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
				$companys = module_company::get_companys();
				if ( isset( $companys[ $search['company_id'] ] ) ) {
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON f.customer_id = cc.customer_id ";
					$where .= " AND ( cc.`company_id` = " . (int) $search['company_id'] . " OR  f.`company_id` = " . (int) $search['company_id'] . " )";
				}
			}
		}
		if ( isset( $search['generic'] ) && strlen( trim( $search['generic'] ) ) ) {
			$name  = db_escape( trim( $search['generic'] ) );
			$where .= " AND (f.`name` LIKE '%$name%' OR f.description LIKE '%$name%' )";
		}
		if ( isset( $search['date_from'] ) && $search['date_from'] != '' ) {
			$where .= " AND f.transaction_date >= '" . input_date( $search['date_from'] ) . "'";
		}
		if ( isset( $search['date_to'] ) && $search['date_to'] != '' ) {
			$where .= " AND f.transaction_date <= '" . input_date( $search['date_to'] ) . "'";
		}
		if ( isset( $search['amount_from'] ) && $search['amount_from'] != '' ) {
			$where .= " AND f.amount >= '" . db_escape( $search['amount_from'] ) . "'";
		}
		if ( isset( $search['amount_to'] ) && $search['amount_to'] != '' ) {
			$where .= " AND f.amount <= '" . db_escape( $search['amount_to'] ) . "'";
		}

		if ( isset( $search['type'] ) && $search['type'] != '' && $search['type'] != 'ie' ) {
			$where .= " AND f.type = '" . db_escape( $search['type'] ) . "'";
		}

		if ( isset( $search['extra_fields'] ) && is_array( $search['extra_fields'] ) && class_exists( 'module_extra', false ) ) {
			$extra_fields = array();
			foreach ( $search['extra_fields'] as $key => $val ) {
				if ( strlen( trim( $val ) ) ) {
					$extra_fields[ $key ] = trim( $val );
				}
			}
			if ( count( $extra_fields ) ) {
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "extra` ext ON (ext.owner_id = f.finance_id)"; //AND ext.owner_table = 'customer'
				$where .= " AND (ext.owner_table = 'finance' AND ( ";
				foreach ( $extra_fields as $key => $val ) {
					$val   = db_escape( $val );
					$key   = db_escape( $key );
					$where .= "( ext.`extra` LIKE '%$val%' AND ext.`extra_key` = '$key') OR ";
				}
				$where                 = rtrim( $where, ' OR' );
				$where                 .= ' ) )';
				$hide_invoice_payments = true;
			}
		}

		// permissions from job module.
		/*switch(module_job::get_job_access_permissions()){
            case _JOB_ACCESS_ALL:

                break;
            case _JOB_ACCESS_ASSIGNED:
                // only assigned jobs!
                //$from .= " LEFT JOIN `"._DB_PREFIX."task` t ON u.job_id = t.job_id ";
                //u.user_id = ".(int)module_security::get_loggedin_id()." OR
                $where .= " AND (t.user_id = ".(int)module_security::get_loggedin_id().")";
                break;
            case _JOB_ACCESS_CUSTOMER:
                break;
        }*/

		// permissions from customer module.
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
					$where .= " AND f.customer_id IN ( ";
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
					$where .= " )";
				}

		}


		$where                          .= " GROUP BY f.finance_id ";
		$where                          .= " ORDER BY f.transaction_date DESC ";
		$sql                            .= $where;
		$finances_from_finance_db_table = qa( $sql );
		// invoice payments:
		$finance_from_invoice_payments   = array();
		$finance_from_job_staff_expenses = array();


		if ( ! $hide_invoice_payments && ( ! isset( $search['invoice_id'] ) || ! (int) $search['invoice_id'] > 0 ) ) {

			$sql   = "SELECT j.*, f.finance_id AS existing_finance_id ";
			$sql   .= " FROM `" . _DB_PREFIX . "job` j ";
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "finance` f ON j.job_id = f.job_id AND f.job_staff_expense > 0 ";
			$where = " WHERE 1 ";
			//j.date_completed != '0000-00-00' ";
			$where .= " AND j.`c_staff_total_amount` > 0 ";
			if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
				$where .= " AND (j.`job_id` = " . (int) $search['job_id'] . " ) ";
			}
			if ( isset( $search['customer_id'] ) && (int) $search['customer_id'] > 0 ) {
				$where .= " AND j.`customer_id` = " . (int) $search['customer_id'];
			}
			/*if(isset($search['generic']) && strlen(trim($search['generic']))){
                $name = db_escape(trim($search['generic']));
                $where .= " AND (i.`name` LIKE '%$name%' OR p.method LIKE '%$name%' )";
            }*/
			if ( isset( $search['company_id'] ) && (int) $search['company_id'] > 0 ) {
				// check this user can view this company id or not
				if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
					$companys = module_company::get_companys();
					if ( isset( $companys[ $search['company_id'] ] ) ) {
						$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON j.customer_id = cc.customer_id ";
						$where .= " AND cc.`company_id` = " . (int) $search['company_id'];
					}
				}
			}
			if ( isset( $search['date_from'] ) && $search['date_from'] != '' ) {
				$where .= " AND j.date_completed >= '" . input_date( $search['date_from'] ) . "'";
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] != '' ) {
				$where .= " AND j.date_completed <= '" . input_date( $search['date_to'] ) . "'";
			}
			if ( isset( $search['amount_from'] ) && $search['amount_from'] != '' ) {
				$where .= " AND j.c_staff_total_amount >= '" . db_escape( $search['amount_from'] ) . "'";
			}
			if ( isset( $search['amount_to'] ) && $search['amount_to'] != '' ) {
				$where .= " AND j.c_staff_total_amount <= '" . db_escape( $search['amount_to'] ) . "'";
			}
			switch ( module_job::get_job_access_permissions() ) {
				case _JOB_ACCESS_ALL:

					break;
				case _JOB_ACCESS_ASSIGNED:
					// only assigned jobs!
					$sql   .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON j.job_id = t.job_id ";
					$where .= " AND (j.user_id = " . (int) module_security::get_loggedin_id() . " OR t.user_id = " . (int) module_security::get_loggedin_id() . ")";
					break;
				case _JOB_ACCESS_CUSTOMER:
					// tie in with customer permissions to only get jobs from customers we can access.
					$valid_customer_ids = module_security::get_customer_restrictions();
					if ( count( $valid_customer_ids ) ) {
						$where .= " AND j.customer_id IN ( ";
						foreach ( $valid_customer_ids as $valid_customer_id ) {
							$where .= (int) $valid_customer_id . ", ";
						}
						$where = rtrim( $where, ', ' );
						$where .= " )";
					}
					break;
			}
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
						$where .= " AND j.customer_id IN ( ";
						foreach ( $valid_customer_ids as $valid_customer_id ) {
							$where .= (int) $valid_customer_id . ", ";
						}
						$where = rtrim( $where, ', ' );
						$where .= " )";
					}

			}

			$sql .= $where . " GROUP BY j.job_id ORDER BY j.date_completed DESC ";
			//echo $sql;
			$finance_from_job_staff_expenses = array();
			$res                             = qa( $sql );
			foreach ( $res as $finance ) {
				// we have a job with staff expenses. split this up into gruops based on staff members.
				$staff_total_grouped = false;
				if ( isset( $finance['c_staff_total_grouped'] ) && strlen( $finance['c_staff_total_grouped'] ) ) {
					$staff_total_grouped = @unserialize( $finance['c_staff_total_grouped'] );
				}
				if ( $staff_total_grouped === false ) {
					//	                echo 'here: ';
					//	                var_dump($finance);
					//	                var_dump($staff_total_grouped);
					$job_data            = module_job::get_job( $finance['job_id'] );
					$staff_total_grouped = $job_data['staff_total_grouped'];
				}
				if ( is_array( $staff_total_grouped ) ) {
					foreach ( $staff_total_grouped as $staff_id => $staff_total ) {
						$staff_member = module_user::get_user( $staff_id );
						if ( $staff_member && $staff_member['user_id'] == $staff_id ) {
							// make sure this entry doesn't already exist in the database table for this job
							// there MAY be an existing entry if 'existing_finance_id' is set
							if ( $finance['existing_finance_id'] > 0 ) {
								// check if it exists for this staff member.
								$existing = get_single( 'finance', array(
									'job_id',
									'job_staff_expense',
									'amount'
								), array( $finance['job_id'], $staff_id, $staff_total ) );
								if ( $existing ) {
									// match exists already, skip adding this one to the list.
									continue;
								}
							}
							//$finance = self::_format_invoice_payment($finance, $finance);
							//$finance['url'] = module_job::link_open($finance['job_id'],false,$finance);
							$finance['url']                    = module_finance::link_open( 'new', false ) . '&job_staff_expense=' . $staff_id . '&from_job_id=' . $finance['job_id'];
							$finance['transaction_date']       = $finance['date_completed'];
							$finance['description']            = _l( 'Job Expense For Staff Member: %s', $staff_member['name'] . ' ' . $staff_member['last_name'] ); //"Exiting: ".$finance['existing_finance_id'].": ".
							$finance['amount']                 = $staff_total;
							$finance['debit']                  = $staff_total;
							$finance['sub_amount']             = $staff_total;
							$finance['taxable_amount']         = $staff_total;
							$finance['credit']                 = 0;
							$finance['type']                   = 'e';
							$finance_from_job_staff_expenses[] = $finance;
						}
					}
				}
			}

		}

		if ( ! $hide_invoice_payments ) {


			$sql = "SELECT p.*, i.customer_id ";
			if ( module_config::c( 'finance_date_type', 'payment' ) == 'invoice' ) {
				// show entries by invoice create date, not payment date.
				$sql .= " , i.date_create AS transaction_date ";
			} else {
				// default, show by paid date.
				$sql .= " , p.date_paid AS transaction_date ";
			}
			$sql   .= " FROM `" . _DB_PREFIX . "invoice_payment` p ";
			$sql   .= " LEFT JOIN `" . _DB_PREFIX . "invoice` i ON p.invoice_id = i.invoice_id ";
			$where = " WHERE p.date_paid != '0000-00-00' ";
			$where .= " AND p.`amount` != 0 ";
			$where .= " AND ( p.`payment_type` = " . _INVOICE_PAYMENT_TYPE_NORMAL . " OR p.`payment_type` = " . _INVOICE_PAYMENT_TYPE_REFUND . ' OR p.`payment_type` = ' . _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT . ' OR p.`payment_type` = ' . _INVOICE_PAYMENT_TYPE_CREDIT . ')';
			if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` ii ON i.invoice_id = ii.invoice_id";
				$sql   .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON ii.task_id = t.task_id";
				$where .= " AND (t.`job_id` = " . (int) $search['job_id'] . " OR i.`deposit_job_id` = " . (int) $search['job_id'] . " ) ";
			}
			if ( isset( $search['invoice_id'] ) && (int) $search['invoice_id'] > 0 ) {
				$where .= " AND p.`invoice_id` = " . (int) $search['invoice_id'];
			}
			if ( isset( $search['customer_id'] ) && (int) $search['customer_id'] > 0 ) {
				$where .= " AND i.`customer_id` = " . (int) $search['customer_id'];
			}
			/*if(isset($search['generic']) && strlen(trim($search['generic']))){
                $name = db_escape(trim($search['generic']));
                $where .= " AND (i.`name` LIKE '%$name%' OR p.method LIKE '%$name%' )";
            }*/
			if ( isset( $search['company_id'] ) && (int) $search['company_id'] > 0 ) {
				// check this user can view this company id or not
				if ( class_exists( 'module_company', false ) && module_company::can_i( 'view', 'Company' ) && module_company::is_enabled() ) {
					$companys = module_company::get_companys();
					if ( isset( $companys[ $search['company_id'] ] ) ) {
						$sql   .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` cc ON i.customer_id = cc.customer_id ";
						$where .= " AND cc.`company_id` = " . (int) $search['company_id'];
					}
				}
			}
			if ( isset( $search['date_from'] ) && $search['date_from'] != '' ) {
				if ( module_config::c( 'finance_date_type', 'payment' ) == 'invoice' ) {
					$where .= " AND i.date_create >= '" . input_date( $search['date_from'] ) . "'";
				} else {
					$where .= " AND p.date_paid >= '" . input_date( $search['date_from'] ) . "'";
				}
			}
			if ( isset( $search['date_to'] ) && $search['date_to'] != '' ) {
				if ( module_config::c( 'finance_date_type', 'payment' ) == 'invoice' ) {
					$where .= " AND i.date_create <= '" . input_date( $search['date_to'] ) . "'";
				} else {
					$where .= " AND p.date_paid <= '" . input_date( $search['date_to'] ) . "'";
				}
			}
			if ( isset( $search['amount_from'] ) && $search['amount_from'] != '' ) {
				$where .= " AND p.amount >= '" . db_escape( $search['amount_from'] ) . "'";
			}
			if ( isset( $search['amount_to'] ) && $search['amount_to'] != '' ) {
				$where .= " AND p.amount <= '" . db_escape( $search['amount_to'] ) . "'";
			}
			if ( isset( $search['type'] ) && $search['type'] != '' && $search['type'] != 'ie' ) {
				if ( $search['type'] == 'i' ) {
					$where .= " AND p.amount > 0";
				} else if ( $search['type'] == 'e' ) {
					$where .= " AND p.amount < 0";
				}
			}
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
						$where .= " AND i.customer_id IN ( ";
						foreach ( $valid_customer_ids as $valid_customer_id ) {
							$where .= (int) $valid_customer_id . ", ";
						}
						$where = rtrim( $where, ', ' );
						$where .= " )";
					}

			}

			$sql .= $where . " ORDER BY p.date_paid DESC ";
			//echo $sql;
			$finance_from_invoice_payments = qa( $sql );
			foreach ( $finance_from_invoice_payments as $finance_id => $finance ) {
				// doesn't have an finance / account reference just yet.
				// but they can create one and this will become a child entry to it.

				$finance = self::_format_invoice_payment( $finance, $finance );

				/*if(!isset($finance['customer_id']) || !$finance['customer_id']){
                    $invoice_data = module_invoice::get_invoice($finance['invoice_id'],2);
                    $finance['customer_id'] = $invoice_data['customer_id'];
                }*/

				// grab a new name/descriptino/etc.. from other plugins (at the moment only subscription)
				/*$new_finance = hook_handle_callback('finance_invoice_listing',$finance['invoice_id'],$finance);
                if(is_array($new_finance) && count($new_finance)){
                    foreach($new_finance as $n){
                        $finance = array_merge($finance,$n);
                    }
                }*/
				$finance_from_invoice_payments[ $finance_id ] = $finance;
			}
			if ( isset( $search['generic'] ) && strlen( trim( $search['generic'] ) ) ) {
				$name = db_escape( trim( $search['generic'] ) );
				//                $where .= " AND (i.`name` LIKE '%$name%' OR p.method LIKE '%$name%' )";
				// we have to do a PHP search here because
				foreach ( $finance_from_invoice_payments as $finance_id => $finance ) {
					if ( stripos( $finance['name'], $name ) === false && stripos( $finance['description'], $name ) === false ) {
						unset( $finance_from_invoice_payments[ $finance_id ] );
					}
				}
			}
		}
		$finances = array_merge( $finances_from_finance_db_table, $finance_from_invoice_payments, $finance_from_job_staff_expenses );

		unset( $finances_from_finance_db_table );
		unset( $finance_from_invoice_payments );
		unset( $finance_from_job_staff_expenses );
		// sort this
		if ( ! function_exists( 'sort_finance' ) ) {
			function sort_finance( $a, $b ) {
				$t1 = strtotime( $a['transaction_date'] );
				$t2 = strtotime( $b['transaction_date'] );
				if ( $t1 == $t2 ) {
					// sort by finance id, putting ones with a finance id first before others. then amount.
					if ( isset( $a['finance_id'] ) && ! isset( $b['finance_id'] ) ) {
						// put $a before $b
						return - 1;
					} else if ( ! isset( $a['finance_id'] ) && isset( $b['finance_id'] ) ) {
						// put $b before $a
						return 1;
					} else {
						return $a['amount'] > $b['amount'];
					}
				} else {
					return $t1 < $t2;
				}
			}
		}
		uasort( $finances, 'sort_finance' );

		foreach ( $finances as $finance_id => $finance ) {
			// we load each of these transactions
			// transaction can be a "transaction" or an "invoice_payment"

			// find out if this transaction is a child transaction to another transaction.
			// if it is a child transaction and we haven't already dispayed it in this listing
			// then we find the parent transaction and display it along with all it's children in this place.
			// this wont be perfect all the time but will be awesome in 99% of cases.

			if ( isset( $finance['finance_id'] ) && $finance['finance_id'] ) {
				// displayed before already?
				if ( isset( $displayed_finance_ids[ $finance['finance_id'] ] ) ) {
					$finances[ $displayed_finance_ids[ $finance['finance_id'] ] ]['link_count'] ++;
					unset( $finances[ $finance_id ] );
					continue;
				}
				$displayed_finance_ids[ $finance['finance_id'] ] = $finance_id;
				if ( isset( $finance['invoice_payment_id'] ) && $finance['invoice_payment_id'] ) {
					$displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] = $finance_id; // so we dont display again.
				}
			} else if ( isset( $finance['invoice_payment_id'] ) && $finance['invoice_payment_id'] && isset( $finance['invoice_id'] ) && $finance['invoice_id'] ) {
				// this is an invoice payment (incoming payment)
				// displayed before already?
				if ( isset( $displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] ) && isset( $finances[ $displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] ] ) ) {
					$finances[ $displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] ] = array_merge( $finance, $finances[ $displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] ] );
					$finances[ $displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] ]['link_count'] ++;
					unset( $finances[ $finance_id ] );
					continue;
				}
				$displayed_invoice_payment_ids[ $finance['invoice_payment_id'] ] = $finance_id; // so we dont display again.
			} else if ( isset( $finance['c_staff_total_amount'] ) ) {
				// staff expense.
			} else {
				// nfi?
				unset( $finances[ $finance_id ] );
				continue;
			}


			if ( isset( $finance['parent_finance_id'] ) && $finance['parent_finance_id'] ) {
				// check if it's parent finance id has been displayed already somewhere.
				if ( isset( $displayed_finance_ids[ $finance['parent_finance_id'] ] ) ) {

					$finances[ $displayed_finance_ids[ $finance['parent_finance_id'] ] ]['link_count'] ++;
					unset( $finances[ $finance_id ] );
					continue; // already done it on this page.
				}
				$displayed_finance_ids[ $finance['parent_finance_id'] ] = $finance_id;
				// we haven't displayed the parent one yet.
				// display the parent one in this listing.
				$finance = self::get_finance( $finance['parent_finance_id'] );
			}

			/*if(isset($finance['invoice_payment_id']) && $finance['invoice_payment_id'] && isset($finance['invoice_id']) && $finance['invoice_id']){
                // moved to above.
            }else*/
			if ( isset( $finance['finance_id'] ) && $finance['finance_id'] ) {
				$finance['url']    = self::link_open( $finance['finance_id'], false );
				$finance['credit'] = $finance['type'] == 'i' ? $finance['amount'] : 0;
				$finance['debit']  = $finance['type'] == 'e' ? $finance['amount'] : 0;
				if ( ! isset( $finance['categories'] ) ) {
					$finance['categories'] = '';
				}
				if ( ! isset( $finance['account_name'] ) ) {
					$finance['account_name'] = '';
				}
			}

			if ( isset( $finance['taxes'] ) && ! isset( $finance['sub_amount'] ) ) {
				$finance['sub_amount'] = $finance['amount'];
				foreach ( $finance['taxes'] as $tax ) {
					if ( isset( $tax['amount'] ) ) {
						$finance['sub_amount'] -= $tax['amount'];
					}
				}
			}

			$finance['link_count'] = 0;

			$finances[ $finance_id ] = $finance;
		}

		return $finances;
	}


	public static function get_accounts() {
		return get_multiple( 'finance_account', false, 'finance_account_id', 'exact', 'name' );
	}

	public static function get_categories() {
		return get_multiple( 'finance_category', false, 'finance_category_id', 'exact', 'name' );

	}

	public static function handle_link_transactions() {
		$link_invoice_payment_ids = ( isset( $_REQUEST['link_invoice_payment_ids'] ) && is_array( $_REQUEST['link_invoice_payment_ids'] ) ) ? $_REQUEST['link_invoice_payment_ids'] : array();
		$link_finance_ids         = ( isset( $_REQUEST['link_finance_ids'] ) && is_array( $_REQUEST['link_finance_ids'] ) ) ? $_REQUEST['link_finance_ids'] : array();
		if ( count( $link_invoice_payment_ids ) || count( $link_finance_ids ) ) {
			// success we can link!
			if ( ! count( $link_finance_ids ) ) {
				set_error( 'Please select at least one transaction that is not an invoice payment.' );
				redirect_browser( self::link_open( false ) );
			}
			$parent_finance_id = (int) key( $link_finance_ids );

			if ( $parent_finance_id > 0 ) {
				// we have a parent! woo!
				unset( $link_finance_ids[ $parent_finance_id ] );
				foreach ( $link_finance_ids as $link_finance_id => $tf ) {
					$link_finance_id = (int) $link_finance_id;
					if ( strlen( $tf ) && $link_finance_id > 0 ) {
						// create this link.
						$sql = "UPDATE `" . _DB_PREFIX . "finance` SET parent_finance_id = $parent_finance_id WHERE finance_id = $link_finance_id LIMIT 1";
						query( $sql );
					}
				}
				foreach ( $link_invoice_payment_ids as $link_invoice_payment_id => $tf ) {
					$link_invoice_payment_id = (int) $link_invoice_payment_id;
					if ( strlen( $tf ) && $link_invoice_payment_id > 0 ) {
						// create this link.
						$sql = "UPDATE `" . _DB_PREFIX . "invoice_payment` SET parent_finance_id = $parent_finance_id WHERE invoice_payment_id = $link_invoice_payment_id LIMIT 1";
						query( $sql );
					}
				}
			}
		}
		set_message( 'Linking success' );
		redirect_browser( self::link_open( false ) );
	}

	private function delete_recurring( $finance_recurring_id ) {
		$finance_recurring_id = (int) $finance_recurring_id;
		$sql                  = "DELETE FROM `" . _DB_PREFIX . "finance_recurring` WHERE finance_recurring_id = '" . $finance_recurring_id . "' LIMIT 1";
		query( $sql );
		$sql = "UPDATE `" . _DB_PREFIX . "finance` SET finance_recurring_id = 0 WHERE finance_recurring_id = '$finance_recurring_id'";
		query( $sql );

	}

	public static function hook_invoice_payment_deleted( $callback_name, $invoice_payment_id, $invoice_id ) {
		//find any finance items that are linked with this invoice payment id
		if ( $invoice_id > 0 && $invoice_payment_id > 0 ) {
			$finance_items = get_multiple( 'finance', array( 'invoice_payment_id' => $invoice_payment_id ) );
			foreach ( $finance_items as $finance_item ) {
				if ( $finance_item['finance_id'] && $finance_item['invoice_payment_id'] == $invoice_payment_id ) {
					self::delete( $finance_item['finance_id'] );
					//                    $sql = "DELETE FROM `"._DB_PREFIX."finance` WHERE invoice_payment_id = '$invoice_payment_id' LIMIT 1";
					//                    query($sql);
				}
			}
		}
	}

	public static function calculate_recurring_date( $finance_recurring_id, $force = false, $update_db = true ) {

		$recurring = self::get_recurring( $finance_recurring_id );
		if ( $recurring['next_due_date_custom'] && ! $force ) {
			return $recurring['next_due_date'];
		}

		$data                         = array();
		$data['next_due_date']        = '';
		$data['next_due_date_custom'] = '0';
		// work out next due date from the start date or from last transaction date.
		$last_transaction = $recurring['last_transaction_date'];
		if ( ! $last_transaction || $last_transaction == '0000-00-00' || $last_transaction == '0000-00-00 00:00:00' ) {
			// no last transaction date!
			// use the start date?
			$last_transaction = $recurring['start_date'];
			if ( ! $last_transaction || $last_transaction == '0000-00-00' ) {
				// default to todays date.
				$last_transaction = date( 'Y-m-d' );
			}
			$next_time = strtotime( $last_transaction );
		} else {
			// check if the start date has increased past the last transaction date.
			$start_time            = strtotime( $recurring['start_date'] );
			$last_transaction_time = strtotime( $last_transaction );
			if ( isset( $_REQUEST['reset_start'] ) && $start_time > $last_transaction_time ) {
				// todo - set this as a flag - a button they click to reset the counter from "this date" onwards
				// without doing this then recording a paymetn early will not set the correct recurring date from that time.
				$next_time = $start_time;
			} else {
				// there was a previous one - base our time off that.
				// only if it's not a once off..
				if ( ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) {
					// it's a once off..
					$next_time             = 9999999999;
					$recurring['end_date'] = '1970-01-02';
				} else {
					// work out when the next one will be.
					$next_time = strtotime( $last_transaction );
					$next_time = strtotime( '+' . abs( (int) $recurring['days'] ) . ' days', $next_time );
					$next_time = strtotime( '+' . abs( (int) $recurring['months'] ) . ' months', $next_time );
					$next_time = strtotime( '+' . abs( (int) $recurring['years'] ) . ' years', $next_time );
				}
			}
		}
		$end_time = ( $recurring['end_date'] && $recurring['end_date'] != '0000-00-00' ) ? strtotime( $recurring['end_date'] ) : 0;
		if ( $end_time > 0 && $next_time > $end_time ) {
			$data['next_due_date'] = '0000-00-00';
		} else {
			$data['next_due_date'] = date( 'Y-m-d', $next_time );
		}
		if ( $update_db ) {
			update_insert( 'finance_recurring_id', $finance_recurring_id, 'finance_recurring', $data );
		}

		return $data['next_due_date'];
	}

	public function handle_hook( $hook, $mod = false ) {
		switch ( $hook ) {
			case 'dashboard_widgets':

				$widgets = array();
				include( 'pages/dashboard_summary_widgets.php' );

				return $widgets;
				break;
			case 'dashboard':
				include( 'pages/dashboard_summary.php' );
				// not in lite edition:
				if ( is_file( dirname( __FILE__ ) . '/pages/finance_quick.php' ) ) {
					include( 'pages/finance_quick.php' );
				}

				return false;
				break;
			case "home_alerts":
				$alerts = array();
				if ( $mod != 'calendar' && module_config::c( 'finance_alerts', 1 ) && module_finance::can_i( 'view', 'Finance Upcoming' ) ) {
					// find any jobs that are past the due date and dont have a finished date.
					$sql               = "SELECT * FROM `" . _DB_PREFIX . "finance_recurring` r ";
					$sql               .= " WHERE r.next_due_date != '0000-00-00' AND r.next_due_date <= '" .
					                      date( 'Y-m-d', strtotime( '+' . module_config::c( 'finance_alert_days_in_future', 14 ) . ' days' ) ) . "'";
					$sql               .= " AND (r.end_date = '0000-00-00' OR r.next_due_date < r.end_date)";
					$upcoming_finances = qa( $sql );
					foreach ( $upcoming_finances as $finance ) {
						$alert_res = process_alert( $finance['next_due_date'], _l( 'Upcoming Transaction Due' ), module_config::c( 'finance_alert_days_in_future', 14 ) );
						if ( $alert_res ) {
							$alert_res['link'] = $this->link_open_recurring( $finance['finance_recurring_id'] );
							$alert_res['name'] = ( $finance['type'] == 'i' ? '+' . dollar( $finance['amount'] ) : '' ) . ( $finance['type'] == 'e' ? '-' . dollar( $finance['amount'] ) : '' ) . ' (' . $finance['name'] . ')';
							$alerts[]          = $alert_res;
						}
					}
				}

				return $alerts;
				break;
		}
	}

	public static function get_finance_summary( $week_start, $week_end, $multiplyer = 1, $row_limit = 7, $customer_id = false ) {

		$cache_key = 'finance_sum_' . md5( module_security::get_loggedin_id() . '_' . serialize( func_get_args() ) );;
		$cache_timeout = module_config::c( 'cache_objects', 60 );
		if ( $cached_item = module_cache::get( 'finance', $cache_key ) ) {
			return $cached_item;
		}

		$base_href = module_finance::link_generate( false, array(
			'full'      => false,
			'page'      => 'dashboard_popup',
			'arguments' => array(
				'display_mode' => 'ajax',
			)
		), array( 'foo' ) );
		$base_href .= '&';
		/*$base_href .= (strpos($base_href,'?')!==false) ? '&' : '?';
        $base_href .= 'display_mode=ajax&';
        $base_href .= 'home_page_stats=true&';*/

		// init structure:
		if ( $multiplyer > 1 ) {
			$row_limit ++;
		}
		for ( $x = 0; $x < $row_limit; $x ++ ) {
			//$time = strtotime("+$x days",strtotime($week_start));
			$time                         = strtotime( "+" . ( $x * $multiplyer ) . " days", strtotime( $week_start ) );
			$data[ date( "Ymd", $time ) ] = array(
				"day"             => $time,
				"hours"           => 0,
				"amount"          => 0,
				"amount_invoiced" => 0,
				"amount_paid"     => 0,
				"amount_spent"    => 0,
			);
			if ( class_exists( 'module_envato', false ) ) {
				$data[ date( "Ymd", $time ) ]['envato_earnings'] = 0;
			}
		}
		$data['total'] = array(
			'day'             => _l( 'Totals:' ),
			'week'            => _l( 'Totals:' ),
			'hours'           => 0,
			'amount'          => 0,
			'amount_invoiced' => 0,
			'amount_paid'     => 0,
			'amount_spent'    => 0,
		);
		if ( class_exists( 'module_envato', false ) ) {
			$data['total']['envato_earnings'] = 0;
		}
		if ( class_exists( 'module_job', false ) ) {
			module_debug::log( array(
				'title' => 'Finance Dashboard Job',
				'data'  => '',
			) );

			// find all task LOGS completed within these dayes
			$sql = "SELECT t.task_id, tl.date_created, t.hours AS task_hours, t.amount, tl.hours AS hours_logged, p.job_id, p.hourly_rate, t.date_done ";
			//            $sql .= " FROM `"._DB_PREFIX."task_log` tl ";
			//            $sql .= " LEFT JOIN `"._DB_PREFIX."task` t ON tl.task_id = t.task_id ";
			$sql .= " FROM `" . _DB_PREFIX . "task` t";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "task_log` tl ON t.task_id = tl.task_id ";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` p ON t.job_id = p.job_id";
			$sql .= " WHERE ( (tl.date_created >= '$week_start' AND tl.date_created < '$week_end') OR (t.fully_completed = 1 AND t.date_done >= '$week_start' AND t.date_done < '$week_end') )";
			if ( $customer_id ) {
				$sql .= " AND p.customer_id = " . (int) $customer_id;
			}
			$sql           .= " AND t.job_id IN ( ";
			$valid_job_ids = module_job::get_valid_job_ids();
			if ( count( $valid_job_ids ) ) {
				foreach ( $valid_job_ids as $valid_job_id ) {
					$sql .= (int) $valid_job_id['job_id'] . ", ";
				}
				$sql = rtrim( $sql, ', ' );
			} else {
				$sql .= ' NULL ';
			}
			$sql .= " ) ";
			//            echo $sql;
			$tasks        = query( $sql );
			$logged_tasks = array();
			while ( $r = mysqli_fetch_assoc( $tasks ) ) {
				if ( ! $r['date_created'] ) {
					$r['date_created'] = $r['date_done'];
				}
				if ( $multiplyer > 1 ) {
					$week_day          = date( 'w', strtotime( $r['date_created'] ) ) - 1;
					$r['date_created'] = date( 'Y-m-d', strtotime( '-' . $week_day . ' days', strtotime( $r['date_created'] ) ) );
				}
				$key = date( "Ymd", strtotime( $r['date_created'] ) );
				if ( ! isset( $data[ $key ] ) ) {
					// for some reason we're getting results here that shouldn't be in the list
					// for now we just skip these results until I figure out why (only had 1 guy report this error, maybe misconfig)
					continue;
				}

				// copied from dashboard_popup_hours_logged.php

				// needed get_tasks call to do the _JOB_TASK_ACCESS_ASSIGNED_ONLY permission check
				$jobtasks = module_job::get_tasks( $r['job_id'] );
				$task     = isset( $jobtasks[ $r['task_id'] ] ) ? $jobtasks[ $r['task_id'] ] : false;
				if ( ! $task ) {
					continue;
				}
				if ( ! isset( $task['manual_task_type'] ) || $task['manual_task_type'] < 0 ) {
					$task['manual_task_type'] = $task['default_task_type'];
				}
				if ( isset( $r['hours_logged'] ) && $r['hours_logged'] > 0 ) {
					if ( $r['hours_logged'] == $task['completed'] ) {
						// this listing is the only logged hours for this task.
						if ( $task['fully_completed'] ) {
							// task complete, we show the final amount and hours.
							if ( $task['amount'] > 0 ) {
								if ( $task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) {
									$display_amount = $task['amount'] * $task['hours'];
								} else {
									$display_amount = $task['amount'];
								}
							} else {
								$display_amount = $r['task_hours'] * $r['hourly_rate'];
							}
						} else {
							// task isn't fully completed yet, just use hourly rate for now.
							$display_amount = $r['hours_logged'] * $r['hourly_rate'];
						}
					} else {
						// this is part of a bigger log of hours for this single task.
						$display_amount = $r['hours_logged'] * $r['hourly_rate'];
					}
					$hours_logged = ( $r['task_hours'] > 0 ? $r['hours_logged'] : 0 );
				} else {
					// there are no logged hours for this particular task, but it is set to completed.
					// we just assume it is completed on this day.
					if ( $task['amount'] > 0 ) {
						if ( $task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) {
							$display_amount = $task['amount'] * $task['hours'];
						} else {
							$display_amount = $task['amount'];
						}
					} else {
						$display_amount = $r['task_hours'] * $r['hourly_rate'];
					}
					$hours_logged = $task['hours'];
				}
				$data[ $key ]['amount']  += $display_amount;
				$data['total']['amount'] += $display_amount;


				$data[ $key ]['hours']  += $hours_logged;
				$data['total']['hours'] += $hours_logged;
				/*$hourly_rate = $r['hourly_rate'];
                if($hours_logged > 0 && $r['amount'] > 0 && $hourly_rate > 0){
                    // there is a custom amount assigned to thsi task.
                    // only calculate this amount if the full hours is complete.
                    $hourly_rate = $r['amount'] / $r['task_hours'];
                }
                if($hours_logged > 0 && $hourly_rate > 0){
                    $data[$key]['amount'] += ($hours_logged * $hourly_rate);
                    $data['total']['amount'] += ($hours_logged * $hourly_rate);
                }*/
			}
		}

		module_debug::log( array(
			'title' => 'Finance Dashboard Invoices',
			'data'  => '',
		) );
		// find invoices sent this week.
		$sql = "SELECT i.* ";
		$sql .= " FROM `" . _DB_PREFIX . "invoice` i ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` ii ON i.invoice_id = ii.invoice_id ";
		if ( class_exists( 'module_job', false ) ) {
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON ii.task_id = t.task_id ";
			$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` p ON t.job_id = p.job_id ";
		}
		$sql .= " WHERE (i.date_create >= '$week_start' AND i.date_create <= '$week_end')";
		if ( $customer_id ) {
			$sql .= " AND i.customer_id = " . (int) $customer_id;
		}
		$sql .= " GROUP BY i.invoice_id";
		// todo - sql in here to limit what they can see.
		$invoices = query( $sql );
		// group invoices into days of the week.
		while ( $invoice_data = mysqli_fetch_assoc( $invoices ) ) {
			//$invoice_data = module_invoice::get_invoice($i['invoice_id']);
			if ( $invoice_data ) {
				if ( $multiplyer > 1 ) {
					$week_day                    = date( 'w', strtotime( $invoice_data['date_create'] ) ) - 1;
					$invoice_data['date_create'] = date( 'Y-m-d', strtotime( '-' . $week_day . ' days', strtotime( $invoice_data['date_create'] ) ) );
				}
				$key = date( "Ymd", strtotime( $invoice_data['date_create'] ) );
				if ( ! isset( $data[ $key ] ) ) {
					// for some reason we're getting results here that shouldn't be in the list
					// for now we just skip these results until I figure out why (only had 1 guy report this error, maybe misconfig)
					continue;
				}


				if ( isset( $data[ $key ] ) ) {
					$data[ $key ]['amount_invoiced']  += $invoice_data['c_total_amount'];
					$data['total']['amount_invoiced'] += $invoice_data['c_total_amount'];
				}
			}
		}

		module_debug::log( array(
			'title' => 'Finance Dashboard Finances',
			'data'  => '',
		) );
		// find all payments made this week.
		// we also have to search for entries in the new "finance" table and make sure we dont double up here.
		$finance_search = array( 'date_from' => $week_start, 'date_to' => $week_end );
		if ( $customer_id ) {
			$finance_search['customer_id'] = $customer_id;
		}
		$finance_records = module_finance::get_finances( $finance_search );
		foreach ( $finance_records as $finance_record ) {
			if ( isset( $finance_record['payment_type'] ) && ( $finance_record['payment_type'] == _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT || $finance_record['payment_type'] == _INVOICE_PAYMENT_TYPE_CREDIT ) ) {
				// CODE COPIED FROM FINANCE_LIST.PHP
				// dont add these ones to the totals on the dashboard
				continue;
			}
			if ( $finance_record['credit'] > 0 ) {
				if ( $multiplyer > 1 ) {
					$week_day                           = date( 'w', strtotime( $finance_record['transaction_date'] ) ) - 1;
					$finance_record['transaction_date'] = date( 'Y-m-d', strtotime( '-' . $week_day . ' days', strtotime( $finance_record['transaction_date'] ) ) );
				}
				$key = date( "Ymd", strtotime( $finance_record['transaction_date'] ) );
				if ( isset( $data[ $key ] ) ) {
					$data[ $key ]['amount_paid']  += $finance_record['amount'];
					$data['total']['amount_paid'] += $finance_record['amount'];
				}
			}
			if ( $finance_record['debit'] > 0 ) {
				if ( $multiplyer > 1 ) {
					$week_day                           = date( 'w', strtotime( $finance_record['transaction_date'] ) ) - 1;
					$finance_record['transaction_date'] = date( 'Y-m-d', strtotime( '-' . $week_day . ' days', strtotime( $finance_record['transaction_date'] ) ) );
				}
				$key = date( "Ymd", strtotime( $finance_record['transaction_date'] ) );
				if ( isset( $data[ $key ] ) ) {
					$data[ $key ]['amount_spent']  += $finance_record['amount'];
					$data['total']['amount_spent'] += $finance_record['amount'];
				}
			}
		}

		module_debug::log( array(
			'title' => 'Finance Dashboard DONE!',
			'data'  => '',
		) );
		/*$sql = "SELECT p.* ";
        $sql .= " FROM `"._DB_PREFIX."invoice_payment` p ";
        $sql .= " WHERE (p.date_paid >= '$week_start' AND p.date_paid <= '$week_end')";
        // todo - sql in here to limit what they can see.
        $payments = query($sql);
        // group invoices into days of the week.
        while($payment = mysqli_fetch_assoc($payments)){
            //$invoice_data = module_invoice::get_invoice($i['invoice_id']);
            if($multiplyer > 1){
                $week_day = date('w',strtotime($payment['date_paid'])) - 1;
                $payment['date_paid'] = date('Y-m-d',strtotime('-'.$week_day.' days',strtotime($payment['date_paid'])));
            }
            $key = date("Ymd",strtotime($payment['date_paid']));
            if(isset($data[$key])){
                $data[$key]['amount_paid'] += $payment['amount'];
                $data['total']['amount_paid'] += $payment['amount'];
            }
        }*/


		if ( class_exists( 'module_envato', false ) ) {

			/*$envato_currency = "USD";
	        require_once 'includes/plugin_envato/envato_api.php';
            $envato = new envato_api();
            $local_currency = $envato->read_setting("local_currency","AUD");
            $currency_convert_multiplier = $envato->currency_convert($envato_currency,$local_currency);

            // find summary of earnings between these dates in the envato statement.
            $week_start_time = strtotime($week_start);
            $week_end_time = strtotime($week_end);
            $sql = "SELECT * FROM `"._DB_PREFIX."envato_statement` s WHERE `time` >= '$week_start_time' AND `time` <= $week_end_time";
            $sql .= " AND ( `type` = 'sale' OR `type` = 'referral_cut' )";
            foreach(qa($sql) as $sale){
                $sale_time = $sale['time'];
                if($multiplyer > 1){
                    $week_day = date('w',$sale_time) - 1;
                    $sale_time = strtotime('-'.$week_day.' days',$sale_time);
                }
                $key = date("Ymd",$sale_time);
	            if(!isset($data[$key]))continue;
                $data[$key]['envato_earnings'] += round($currency_convert_multiplier * $sale['earnt'],2);
                $data['total']['envato_earnings'] += round($currency_convert_multiplier * $sale['earnt'],2);

            }*/

		}

		if ( $multiplyer > 1 ) {
			// dont want totals on previous weeks listing
			unset( $data['total'] );
		}

		foreach ( $data as $data_id => $row ) {
			//$row['amount'] = dollar($row['amount']);
			$row['chart_amount']          = $row['amount'];
			$row['amount']                = currency( (int) $row['amount'] );
			$row['chart_amount_invoiced'] = $row['amount_invoiced'];
			$row['amount_invoiced']       = currency( (int) $row['amount_invoiced'] );
			$row['chart_amount_paid']     = $row['amount_paid'];
			$row['amount_paid']           = currency( (int) $row['amount_paid'] );
			$row['chart_amount_spent']    = $row['amount_spent'];
			$row['amount_spent']          = currency( (int) $row['amount_spent'] );
			if ( class_exists( 'module_envato', false ) ) {
				$row['chart_envato_earnings'] = $row['envato_earnings'];
				$row['envato_earnings']       = currency( (int) $row['envato_earnings'] );
			}
			// combine together
			$row['chart_hours'] = $row['hours'];
			$row['hours']       = sprintf( '%s (%s)', $row['hours'], $row['amount'] );
			if ( is_numeric( $row['day'] ) ) {
				$time        = $row['day'];
				$date        = date( 'Y-m-d', $time );
				$row['date'] = $date;
				if ( $multiplyer > 1 ) {
					$date .= '|' . date( 'Y-m-d', strtotime( '+' . $multiplyer . ' days', $time ) );
				}

				$row['day'] = sprintf( _l( '%1$s %2$s%3$s' ), _l( date( 'D', $time ) ), date( 'j', $time ), _l( date( 'S', $time ) ) );
				//$row['hours'] = '<a href="'.$base_href.'w=hours&date='.$date.'" class="summary_popup">'. _l('%s hours',$row['hours']) . '</a>';
				$row['hours_link']           = '<a href="' . $base_href . 'w=hours&date=' . $date . '" data-ajax-modal=\'{"type":"normal","buttons":"close","title":"' . _l( 'Hours' ) . '"}\'>' . $row['hours'] . '</a>';
				$row['amount_link']          = '<a href="' . $base_href . 'w=hours&date=' . $date . '" data-ajax-modal=\'{"type":"normal","buttons":"close","title":"' . _l( 'Invoiced' ) . '"}\'>' . $row['amount'] . '</a>';
				$row['amount_invoiced_link'] = '<a href="' . $base_href . 'w=amount_invoiced&date=' . $date . '" data-ajax-modal=\'{"type":"normal","buttons":"close","title":"' . _l( 'Invoiced' ) . '"}\'>' . $row['amount_invoiced'] . '</a>';
				$row['amount_paid_link']     = '<a href="' . $base_href . 'w=amount_paid&date=' . $date . '" data-ajax-modal=\'{"type":"normal","buttons":"close","title":"' . _l( 'Income' ) . '"}\'>' . $row['amount_paid'] . '</a>';
				$row['amount_spent_link']    = '<a href="' . $base_href . 'w=amount_spent&date=' . $date . '" data-ajax-modal=\'{"type":"normal","buttons":"close","title":"' . _l( 'Expense' ) . '"}\'>' . $row['amount_spent'] . '</a>';

				$row['week'] = sprintf( _l( '%1$s %2$s%3$s' ), _l( date( 'M', $time ) ), date( 'j', $time ), _l( date( 'S', $time ) ) );
				// if it's today.
				if ( $time == strtotime( date( "Y-m-d" ) ) ) {
					$row['highlight'] = true;
				}
			} else {

			}

			$data[ $data_id ] = $row;
		}

		module_cache::put( 'finance', $cache_key, $data, $cache_timeout );

		return $data;
	}

	public static function get_dashboard_data() {

		$dashboard_data_mode = module_config::c( 'dashboard_income_mode', 'weeks' );
		switch ( $dashboard_data_mode ) {
			case 'months':
				$show_previous_months = module_config::c( 'dashboard_income_previous_months', 7 );
				$home_summary         = array(
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - ( ( $show_previous_weeks + 2 ) * 7 ) + 1, date( 'Y' ) ) ),
						// 7 weeks ago
						"week_end"   => date( 'Y-m-d', strtotime( '-1 day', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) - ( 2 * 7 ) + 2, date( 'Y' ) ) ) ),
						// 2 weeks ago
						'table_name' => 'Previous Months',
						'array_name' => 'previous_weeks_data',
						'multiplyer' => 7,
						'col1'       => 'week',
						'row_limit'  => $show_previous_months,
					),
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - 6, date( 'Y' ) ) ),
						// sunday midnight
						"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) - 5, date( 'Y' ) ) ),
						'table_name' => 'Last Month',
						'array_name' => 'last_week_data',
						'multiplyer' => 1,
						'col1'       => 'day',
						'row_limit'  => 7,
					),
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) + 1, date( 'Y' ) ) ),
						// sunday midnight
						"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) + 2, date( 'Y' ) ) ),
						'table_name' => 'This Month',
						'array_name' => 'this_week_data',
						'multiplyer' => 1,
						'col1'       => 'day',
						'row_limit'  => 7,
					),
				);
				break;
			case 'weeks':
			default:
				$show_previous_weeks = module_config::c( 'dashboard_income_previous_weeks', 7 );
				$home_summary        = array(
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - ( ( $show_previous_weeks + 2 ) * 7 ) + 1, date( 'Y' ) ) ),
						// 7 weeks ago
						"week_end"   => date( 'Y-m-d', strtotime( '-1 day', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) - ( 2 * 7 ) + 2, date( 'Y' ) ) ) ),
						// 2 weeks ago
						'table_name' => _l( 'Previous Weeks' ),
						'array_name' => 'previous_weeks_data',
						'multiplyer' => 7,
						'col1'       => 'week',
						'row_limit'  => $show_previous_weeks,
					),
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) - 6, date( 'Y' ) ) ),
						// sunday midnight
						"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) - 5, date( 'Y' ) ) ),
						'table_name' => _l( 'Last Week' ),
						'array_name' => 'last_week_data',
						'multiplyer' => 1,
						'col1'       => 'day',
						'row_limit'  => 7,
					),
					array(
						"week_start" => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) - date( 'N' ) + 1, date( 'Y' ) ) ),
						// sunday midnight
						"week_end"   => date( 'Y-m-d', mktime( 1, 0, 0, date( 'm' ), date( 'd' ) + ( 6 - date( 'N' ) ) + 2, date( 'Y' ) ) ),
						'table_name' => _l( 'This Week' ),
						'array_name' => 'this_week_data',
						'multiplyer' => 1,
						'col1'       => 'day',
						'row_limit'  => 7,
					),
				);
				break;
		}


		$return = array();


		foreach ( $home_summary as $home_sum ) {
			extract( $home_sum ); // hacky, better than old code tho.
			$data = self::get_finance_summary( $week_start, $week_end, $multiplyer, $row_limit );


			// return the bits that will be used in the output of the HTML table (and now in the calendar module output)
			$return [] = array(
				'data'       => $data,
				'table_name' => $table_name,
				'col1'       => $col1,
			);

		}

		return $return;
	}

	public static function get_tax_modes() {
		return array(
			0 => _l( 'No Tax' ),
			1 => _l( 'Taxed' ),
		);
	}

	public function get_upgrade_sql() {
		$sql = '';

		// error with creating finance recurring table.

		if ( ! self::db_table_exists( 'finance_recurring' ) ) {
			$sql_table = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "finance_recurring` (
  `finance_recurring_id` int(11) NOT NULL AUTO_INCREMENT,
  `days` int(11) NOT NULL DEFAULT '0',
  `months` int(11) NOT NULL DEFAULT '0',
  `years` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency_id` INT(11) NOT NULL DEFAULT '1',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL COMMENT 'calculated in php when a recurring is saved',
    `next_due_date_custom` TINYINT( 1 ) NOT NULL DEFAULT  '0',
  `type` enum('i','e') NOT NULL DEFAULT 'e',
  `finance_account_id` int(11) NOT NULL DEFAULT '0',
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NOT NULL,
  `date_created` date NOT NULL,
  `date_updated` date NOT NULL,
  PRIMARY KEY (`finance_recurring_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
			query( $sql_table );
		}


		$fields = get_fields( 'finance_recurring' );
		if ( ! isset( $fields['next_due_date_custom'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance_recurring` ADD  `next_due_date_custom` TINYINT( 1 ) NOT NULL DEFAULT  '0' AFTER  `next_due_date`;";
		}
		if ( ! isset( $fields['currency_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance_recurring` ADD  `currency_id` int( 11 ) NOT NULL DEFAULT  '" . module_config::c( 'default_currency_id', 1 ) . "' AFTER  `amount`;";
		}
		$fields = get_fields( 'finance' );
		if ( ! isset( $fields['currency_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `currency_id` int( 11 ) NOT NULL DEFAULT  '" . module_config::c( 'default_currency_id', 1 ) . "' AFTER  `type`;";
		}
		if ( ! isset( $fields['customer_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `customer_id` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `finance_recurring_id`;";
		}
		if ( ! isset( $fields['job_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `job_id` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `customer_id`;";
		}
		if ( ! isset( $fields['invoice_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `invoice_id` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `job_id`;";
		}
		if ( ! isset( $fields['company_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `company_id` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `invoice_id`;";
		}
		if ( ! isset( $fields['sub_amount'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `sub_amount` decimal(10,2) NOT NULL DEFAULT  '0' AFTER  `description`;";
			$sql .= "UPDATE `" . _DB_PREFIX . "finance` SET  `sub_amount` = `amount` WHERE `sub_amount` = 0 AND `amount` > 0;";
		}
		if ( ! isset( $fields['user_id'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `user_id` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `company_id`;";
		}
		if ( ! isset( $fields['job_staff_expense'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `job_staff_expense` int( 11 ) NOT NULL DEFAULT  '0' AFTER  `user_id`;";
		}
		if ( ! isset( $fields['taxable_amount'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `taxable_amount` decimal(10,2) NOT NULL DEFAULT  '0' AFTER  `sub_amount`;";
			if ( isset( $fields['taxible_amount'] ) ) { // typo - damnnn
				$sql .= "UPDATE `" . _DB_PREFIX . "finance` SET  `taxable_amount` = `taxible_amount` WHERE `taxable_amount` = 0 AND `taxible_amount` > 0;";
			} else {
				$sql .= "UPDATE `" . _DB_PREFIX . "finance` SET  `taxable_amount` = `sub_amount` WHERE `taxable_amount` = 0 AND `sub_amount` > 0;";
			}
		}
		if ( ! isset( $fields['tax_mode'] ) ) {
			$sql .= "ALTER TABLE `" . _DB_PREFIX . "finance` ADD  `tax_mode` tinyint(1) NOT NULL DEFAULT '0' AFTER  `sub_amount`;";
		}
		self::add_table_index( 'finance', 'job_id' );
		self::add_table_index( 'finance', 'invoice_id' );
		self::add_table_index( 'finance', 'customer_id' );
		self::add_table_index( 'finance', 'type' );
		self::add_table_index( 'finance', 'company_id' );
		self::add_table_index( 'finance', 'amount' );
		self::add_table_index( 'finance', 'user_id' );
		self::add_table_index( 'finance', 'job_staff_expense' );

		if ( ! self::db_table_exists( 'finance_tax' ) ) {
			$sql .= "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX . "finance_tax` (
  `finance_tax_id` int(11) NOT NULL AUTO_INCREMENT,
  `finance_id` int(11) NOT NULL,
  `percent` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `name` varchar(50) NOT NULL,
  `order` int(4) NOT NULL DEFAULT '0',
  `increment` tinyint(1) NOT NULL DEFAULT '0',
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) DEFAULT NULL,
  `date_created` date NOT NULL,
  `date_updated` date DEFAULT NULL,
  PRIMARY KEY (`finance_tax_id`),
  KEY (`finance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance` (
		`finance_id` int(11) NOT NULL AUTO_INCREMENT,
		`finance_account_id` int(11) NOT NULL DEFAULT '0',
		`parent_finance_id` int(11) DEFAULT NULL,
		`invoice_payment_id` int(11) DEFAULT NULL,
		`transaction_date` date NOT NULL,
		`name` varchar(255) NOT NULL,
		`description` text NOT NULL,
		`sub_amount` decimal(10,2) NOT NULL DEFAULT '0',
		`taxable_amount` decimal(10,2) NOT NULL DEFAULT '0',
		`tax_mode` tinyint(1) NOT NULL DEFAULT '0',
		`amount` decimal(10,2) NOT NULL DEFAULT '0',
		`type` enum('e','i') NOT NULL,
		`currency_id` int(11) NOT NULL DEFAULT '1',
		`finance_recurring_id` int(11) NOT NULL DEFAULT '0',
		`customer_id` int(11) NOT NULL DEFAULT '0',
		`job_id` int(11) NOT NULL DEFAULT '0',
		`invoice_id` int(11) NOT NULL DEFAULT '0',
		`company_id` int(11) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`job_staff_expense` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`finance_id`),
		KEY `transaction_date` (`transaction_date`),
		KEY `finance_account_id` (`finance_account_id`),
		KEY `parent_finance_id` (`parent_finance_id`),
		KEY `invoice_payment_id` (`invoice_payment_id`),
		KEY `finance_recurring_id` (`finance_recurring_id`),
		KEY `amount` (`amount`),
		KEY `user_id` (`user_id`),
		KEY `job_staff_expense` (`job_staff_expense`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_tax` (
		`finance_tax_id` int(11) NOT NULL AUTO_INCREMENT,
		`finance_id` int(11) NOT NULL,
		`percent` decimal(10,2) NOT NULL,
		`amount` decimal(10,2) NOT NULL,
		`name` varchar(50) NOT NULL,
		`order` int(4) NOT NULL DEFAULT '0',
		`increment` tinyint(1) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) DEFAULT NULL,
		`date_created` date NOT NULL,
		`date_updated` date DEFAULT NULL,
		PRIMARY KEY (`finance_tax_id`),
		KEY (`finance_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_account` (
		`finance_account_id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`finance_account_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_category` (
		`finance_category_id` int(11) NOT NULL AUTO_INCREMENT,
		`name` varchar(255) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`finance_category_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_category_rel` (
		`finance_id` int(11) NOT NULL,
		`finance_category_id` int(11) NOT NULL,
		UNIQUE KEY `finance_id` (`finance_id`,`finance_category_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_recurring` (
		`finance_recurring_id` int(11) NOT NULL AUTO_INCREMENT,
		`days` int(11) NOT NULL DEFAULT '0',
		`months` int(11) NOT NULL DEFAULT '0',
		`years` int(11) NOT NULL DEFAULT '0',
		`name` varchar(255) NOT NULL,
		`description` text NOT NULL,
		`amount` decimal(10,2) NOT NULL,
		`currency_id` INT(11) NOT NULL DEFAULT '1',
		`start_date` date DEFAULT NULL,
		`end_date` date DEFAULT NULL,
		`next_due_date` date DEFAULT NULL COMMENT 'calculated in php when a recurring is saved',
		`next_due_date_custom` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`type` enum('i','e') NOT NULL DEFAULT 'e',
		`finance_account_id` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL,
		`date_created` date NOT NULL,
		`date_updated` date NOT NULL,
		PRIMARY KEY (`finance_recurring_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>finance_recurring_catrel` (
		`finance_recurring_id` int(11) NOT NULL,
		`finance_category_id` int(11) NOT NULL,
		UNIQUE KEY `finance_id` (`finance_recurring_id`,`finance_category_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		<?php

		return ob_get_clean();
	}

	public static function is_expense_enabled() {
		// we dont have the finance_edit.php file if expenses are disabled (ie: lite version)
		return is_file( dirname( __FILE__ ) . '/pages/finance_edit.php' );
	}
}