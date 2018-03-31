<?php

defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:


if ( class_exists( 'UCMBaseSingle' ) ) {

	class UCMProduct extends UCMBaseSingle {

		public $db_id = 'product_id';
		public $db_table = 'product';
		public $display_key = 'name';
		public $display_name = 'Product';
		public $display_name_plural = 'Products';
		public $db_fields = array(
			'product_category_id'       => array(),
			'name'                      => array(),
			'description'               => array(),
			'quantity'                  => array(),
			'amount'                    => array(),
			'currency_id'               => array(),
			'default_task_type'         => array(),
			'billable'                  => array(),
			'taxable'                   => array(),
			'inventory_control'         => array(),
			'inventory_level'           => array(),
			'inventory_level_current'   => array(),
			'inventory_low_stock_level' => array(),
			'purchase_price'            => array(),
			'unitname'                  => array(),

		);


		public function link_open( $full = false, $link_options = array() ) {
			// we add the config.config_admin/product.product_settings link prefix:

			$link_options[] = array(
				'full'      => $full,
				'type'      => 'config',
				'module'    => 'config',
				'page'      => 'config_admin',
				'arguments' => array(),
				'data'      => array(),
				'text'      => false,
			);
			$link_options[] = array(
				'full'      => $full,
				'type'      => 'product',
				'module'    => 'product',
				'page'      => 'product_settings',
				'arguments' => array(),
				'data'      => array(),
				'text'      => false,
			);

			return parent::link_open( $full, $link_options );
		}


		public function handle_submit() {


			if ( module_product::can_i( 'edit', 'Products' ) && module_form::check_secure_key() ) {
				$product_id = (int) $_REQUEST['product_id'];
				if ( $product_id ) {
					$this->load( $product_id );
				}

				if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['product_id'] && module_product::can_i( 'delete', 'Products' ) ) {

					$products = new UCMProducts();
					$this->delete_with_confirm( false, $products->link_open() );

				}

				$this->save_data( $_POST );
				set_message( 'Product saved successfully' );
				$return = $this->link_open();
				redirect_browser( $return );
			}


		}

		public function save_data( $post_data ) {
			$creating_new = ! $this->id;
			if ( $creating_new ) {
			}
			// we have to calculate the inventory level and save it into the inventory_level_current box.
			if ( ! empty( $post_data['inventory_control'] ) ) {
				$inventory_usage = 0;
				if ( (int) $this->id > 0 ) {
					// we calculate how much inventory has been currentl used,
					// add that ontop of the user supplied value so we can store our total used/current inventory
					// we do this so it's easier to add/remove qty as invoices are created/deleted
					$usages = $this->get_product_usage();
					foreach ( $usages as $usage ) {
						if ( $usage['change_qty'] ) {
							$inventory_usage += $usage['count'];
						}

					}
				}
				$post_data['inventory_level'] = ( ! empty( $post_data['inventory_level_current'] ) ? $post_data['inventory_level_current'] : 0 ) + $inventory_usage;
			}
			parent::save_data( $post_data );

			return $this->id;
		}

		public function trigger_low_stock_alerts() {
			if ( $this->get( 'inventory_low_stock_level' ) > 0 && $this->get( 'inventory_level_current' ) <= $this->get( 'inventory_low_stock_level' ) ) {
				// yay. send email.
				// just to admin for now.

				$values = array(
					'product_name'     => $this->get( 'name' ),
					'product_link'     => $this->link_open( true ),
					'product_quantity' => $this->get( 'inventory_level_current' ),
				);

				$template = module_template::get_template_by_key( 'inventory_low_stock_warning' );
				$template->assign_values( $values );
				$html = $template->render( 'html' );

				$email                 = module_email::new_email();
				$email->replace_values = $values;
				$email->set_to_manual( module_config::c( 'admin_email_address' ), module_config::c( 'admin_system_name' ) );
				$email->set_subject( $template->description );
				// do we send images inline?
				$email->set_html( $html );

				if ( $email->send() ) {
					set_message( 'Low stock warning for ' . $this->get( 'name' ) );
				} else {
					set_error( 'Failed to send low stock warning for ' . $this->get( 'name' ) );
				}

			}
		}

		public function get_product_usage() {

			$product_id = $this->id;

			// quotes are store in quote_task table with (newly added) product_id key
			$return = array();
			if ( class_exists( 'module_invoice', false ) && module_invoice::is_plugin_enabled() ) {
				$tasks = get_multiple( 'invoice_item', array( 'product_id' => $product_id ), 'invoice_item_id' );
				if ( count( $tasks ) ) {
					$invoice_tasks = array(
						'title'      => 'invoices',
						'change_qty' => true,
						'count'      => 0,
						'items'      => array(),
					);
					foreach ( $tasks as $task ) {
						if ( ! isset( $invoice_tasks['items'][ $task['invoice_id'] ] ) ) {
							$data                                          = module_invoice::get_invoice( $task['invoice_id'] );
							$invoice_tasks['items'][ $task['invoice_id'] ] = array(
								'text'  => $data['date_paid'] != '0000-00-00' ? _l( 'Paid %s', print_date( $data['date_paid'] ) ) : _l( 'Not Paid' ),
								'link'  => module_invoice::link_open( $task['invoice_id'], true ),
								'date'  => $task['date_updated'] != '0000-00-00' ? strtotime( $task['date_updated'] ) : strtotime( $task['date_created'] ),
								'count' => 0,
							);
						}
						$qty                                                    = $task['hours'];
						$invoice_tasks['items'][ $task['invoice_id'] ]['count'] += $qty;
						$invoice_tasks['count']                                 += $qty;
					}
					$return['invoices'] = $invoice_tasks;
				}
			}
			if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
				$tasks = get_multiple( 'task', array( 'product_id' => $product_id ), 'task_id' );
				if ( count( $tasks ) ) {
					$job_tasks = array(
						'title'      => 'Jobs',
						'change_qty' => false,
						'count'      => 0,
						'items'      => array(),
					);
					foreach ( $tasks as $task ) {
						if ( ! isset( $job_tasks['items'][ $task['job_id'] ] ) ) {
							$data                                  = module_job::get_job( $task['job_id'] );
							$job_tasks['items'][ $task['job_id'] ] = array(
								'text'  => $data['date_completed'] != '0000-00-00' ? _l( 'Completed %s', print_date( $data['date_completed'] ) ) : _l( 'Not Completed' ),
								'link'  => module_job::link_open( $task['job_id'], true ),
								'date'  => $task['date_updated'] != '0000-00-00' ? strtotime( $task['date_updated'] ) : strtotime( $task['date_created'] ),
								'count' => 0,
							);
						}
						$qty                                            = $task['hours'];
						$job_tasks['items'][ $task['job_id'] ]['count'] += $qty;
						$job_tasks['count']                             += $qty;
					}
					$return['jobs'] = $job_tasks;
				}
			}
			if ( class_exists( 'module_quote', false ) && module_quote::is_plugin_enabled() ) {
				$tasks = get_multiple( 'quote_task', array( 'product_id' => $product_id ), 'quote_task_id' );
				if ( count( $tasks ) ) {
					$quote_tasks = array(
						'title'      => 'Quotes',
						'change_qty' => false,
						'count'      => 0,
						'items'      => array(),
					);
					foreach ( $tasks as $task ) {
						if ( ! isset( $quote_tasks['items'][ $task['quote_id'] ] ) ) {
							$data                                      = module_quote::get_quote( $task['quote_id'] );
							$quote_tasks['items'][ $task['quote_id'] ] = array(
								'text'  => $data['date_approved'] != '0000-00-00' ? _l( 'Approved %s', print_date( $data['date_approved'] ) ) : _l( 'Not Approved' ),
								'link'  => module_quote::link_open( $task['quote_id'], true ),
								'date'  => $task['date_updated'] != '0000-00-00' ? strtotime( $task['date_updated'] ) : strtotime( $task['date_created'] ),
								'count' => 0,
							);
						}
						$qty                                                = $task['hours'];
						$quote_tasks['items'][ $task['quote_id'] ]['count'] += $qty;
						$quote_tasks['count']                               += $qty;
					}
					$return['quotes'] = $quote_tasks;
				}
			}

			return $return;

		}

		public function get_suppliers() {
			if ( $this->id ) {
				$suppliers = new UCMProductSuppliers();

				return $suppliers->get( array( 'product_id' => $this->id ) );
			}

			return array();

		}

		public function delete_children() {
			$conn = $this->get_db();
			if ( $conn && $this->id ) {

				$this->db->prepare( 'DELETE FROM `' . _DB_PREFIX . 'product_supplier` WHERE `product_id` = :id' );

				$this->db->bind_param( 'id', $this->id, 'int' );

				if ( $this->db->execute() ) {

				}
			}
		}


	}

	class UCMProductSupplier extends UCMBaseSingle {

		// support composite primary keys
		public $db_id = array( 'product_id', 'customer_id' );
		public $db_table = 'product_supplier';
		public $display_key = 'customer_id';
		public $module_name = 'product';
		public $display_name = 'Product Supplier';
		public $display_name_plural = 'Product Suppliers';
		public $db_fields = array(
			'product_id'  => array(),
			'customer_id' => array(),
		);


		public function default_values() {
			if ( ! empty( $_REQUEST['product_id'] ) ) {
				$this->db_details['product_id'] = (int) $_REQUEST['product_id'];
			}
			parent::default_values();
		}


		public function handle_submit() {

			if ( module_form::check_secure_key() ) {

				$product_id  = (int) $_REQUEST['product_id'];
				$customer_id = (int) $_REQUEST['customer_id'];
				$product     = new UCMProduct( $product_id );
				if ( $product->product_id == $product_id ) {
					$this->load( array( 'product_id' => $product_id, 'customer_id' => $customer_id ) );

					if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $product_id && $customer_id && module_product::can_i( 'delete', 'Products' ) ) {

						$this->delete_with_confirm( false, $product->link_open() );

					}

					$save_data = $_POST;
					if ( ! empty( $save_data['new_customer_id'] ) ) {
						$save_data['customer_id'] = $save_data['new_customer_id'];
						$this->save_data( $save_data );
						set_message( 'Product Supplier saved successfully' );
					}
					$return = $product->link_open();
					redirect_browser( $return );
				}
			}

		}


	}

	class UCMProducts extends UCMBaseMulti {

		public $db_id = 'product_id';
		public $db_table = 'product';
		public $display_name = 'Product';
		public $display_name_plural = 'Products';

		public function link_open( $link_options = array() ) {
			// we add the config.config_admin/product.product_settings link prefix:

			$link_options[] = array(
				'full'      => false,
				'type'      => 'config',
				'module'    => 'config',
				'page'      => 'config_admin',
				'arguments' => array(),
				'data'      => array(),
				'text'      => false,
			);
			$link_options[] = array(
				'full'      => false,
				'type'      => 'product',
				'module'    => 'product',
				'page'      => 'product_settings',
				'arguments' => array(),
				'data'      => array(),
				'text'      => false,
			);

			return parent::link_open( $link_options );
		}


	}

	class UCMProductSuppliers extends UCMBaseMulti {

		// support composite primary keys
		public $db_id = array( 'product_id', 'customer_id' );
		public $db_table = 'product_supplier';
		public $display_name = 'Product Supplier';
		public $display_name_plural = 'Product Suppliers';

	}
}