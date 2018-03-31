<?php


class module_product extends module_base {

	public $links;
	public $product_types;
	public $product_id;

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
		$this->links           = array();
		$this->product_types   = array();
		$this->module_name     = "product";
		$this->module_position = 31;
		$this->version         = 2.167;
		// 2.167 - 2017-07-26 - starting on basic shop functionality
		// 2.166 - 2017-05-02 - big changes
		// 2.165 - 2017-04-19 - vendor support
		// 2.164 - 2017-01-10 - php error fix
		// 2.163 - 2016-11-16 - fontawesome icon fixes
		// 2.162 - 2016-09-29 - error fix on create
		// 2.161 - 2016-08-04 - wysiwyg product descriptions
		// 2.160 - 2016-07-10 - big update to mysqli
		// 2.159 - 2016-06-09 - search products
		// 2.158 - 2016-05-15 - unit of measurement
		// 2.157 - 2016-04-30 - basic inventory management
		// 2.156 - 2016-01-21 - extra fields for product details.
		// 2.155 - 2015-07-18 - product search
		// 2.154 - 2015-06-28 - started work on product API
		// 2.153 - 2015-02-12 - ui fix and product defaults (tax/bill/type)
		// 2.152 - 2014-01-23 - new quote feature
		// 2.151 - 2013-11-15 - working on new UI
		// 2.15 - 2013-10-02 - bulk product delete and product category import fix
		// 2.149 - 2013-09-08 - faq permission fix
		// 2.148 - 2013-08-07 - css improvement
		// 2.147 - 2013-06-16 - javascript fix
		// 2.146 - 2013-06-07 - further work on product categories
		// 2.145 - 2013-05-28 - further work on product categories
		// 2.144 - 2013-05-28 - started work on product categories
		// 2.143 - 2013-04-27 - css fix for large product list
		// 2.142 - 2013-04-16 - product fix in invoice
		// 2.141 - 2013-04-05 - product support in invoices
		// 2.14 - product import via CSV
		// 2.13 - permission fix
		// 2.12 - product permissions
		// 2.11 - initial release

		hook_add( 'api_callback_product', 'module_product::api_filter_product' );
		hook_add( 'invoice_saved', 'module_product::hook_invoice_saved' );
		hook_add( 'invoice_deleted', 'module_product::hook_invoice_deleted' );

		if ( module_security::is_logged_in() && self::can_i( 'view', 'Products' ) ) {


			if ( class_exists( 'module_template', false ) ) {
				module_template::init_template( 'inventory_low_stock_warning', 'Hello,<br>
<br>
Low stock warning for product: {PRODUCT_LINK}.<br><br>
Current Inventory Level: {PRODUCT_QUANTITY} <br/><br/>

', 'Low Stock Warning: {PRODUCT_NAME}', array(
					'PRODUCT_NAME' => 'Product Name',
				) );

			}
			module_config::register_css( 'product', 'product.css' );
			module_config::register_js( 'product', 'product.js' );

			if ( isset( $_REQUEST['_products_ajax'] ) ) {
				switch ( $_REQUEST['_products_ajax'] ) {
					case 'products_ajax_search':

						//                        $sent = headers_sent($file, $line);
						//                        echo 'here';
						//                        print_r($sent);
						//                        print_r($file);
						//                        print_r($line);
						if ( self::$_product_count === false ) {
							self::$_product_count = count( self::get_products() );
						}
						$product_name = isset( $_REQUEST['product_name'] ) ? $_REQUEST['product_name'] : '';
						if ( self::$_product_count > 0 ) {


							$search = array();
							if ( strlen( $product_name ) > 2 ) {
								$search['general'] = $product_name;
							}
							$products = self::get_products( $search );
							if ( count( $products ) > 0 ) {
								// sort products by categories.
								$products_in_categories = array();
								foreach ( $products as $product_id => $product ) {
									if ( $product['product_category_id'] && $product['product_category_name'] ) {
										if ( ! isset( $products_in_categories[ $product['product_category_name'] ] ) ) {
											$products_in_categories[ $product['product_category_name'] ] = array();

										}
										$products_in_categories[ $product['product_category_name'] ][] = $product;
										unset( $products[ $product_id ] );
									} else {

									}
								}
								$cat_id = 1;
								?>
								<ul>
									<?php foreach ( $products_in_categories

									as $category_name => $cat_products ){ ?>
									<li>
										<a href="#" class="product_category_parent"><?php echo htmlspecialchars( $category_name ); ?></a>
										(<?php _e( '%s products', count( $cat_products ) ); ?>)
										<ul style="display:none;" id="product_category_<?php echo $cat_id ++; ?>">
											<?php foreach ( $cat_products as $product ) { ?>
												<li>
													<a href="#"
													   onclick="return ucm.product.select_product(<?php echo $product['product_id']; ?>);"> <?php echo htmlspecialchars( $product['name'] ); ?></a>
												</li>
											<?php } ?>
										</ul>

										<?php } ?>
										<?php foreach ( $products

										as $product ){ ?>
									<li>
										<a href="#"
										   onclick="return ucm.product.select_product(<?php echo $product['product_id']; ?>);"><?php
											/*if($product['product_category_name']){
													echo htmlspecialchars($product['product_category_name']);
													echo ' &raquo; ';
											}*/
											echo htmlspecialchars( $product['name'] ); ?></a>
									</li>
								<?php } ?>
								</ul>
								<?php
							}
						} else if ( ! strlen( $product_name ) ) {
							_e( 'Pleae create Products first by going to Settings > Products' );
						}

						exit;
					case 'products_ajax_get':
						$product_id = (int) $_REQUEST['product_id'];
						if ( $product_id ) {
							$product = self::get_product( $product_id );
						} else {
							$product = array();
						}
						if ( ! module_config::c( 'product_show_category_in_tasks', 1 ) ) {
							unset( $product['product_category_id'] );
						}
						echo json_encode( $product );
						exit;
				}
			}
		}


	}

	public static function get_replace_fields( $product_id ) {
		$product = self::get_product( $product_id );
		$data    = $product;

		// addition. find all extra keys for this customer and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'product' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'product', 'owner_id' => $product_id ) );
			foreach ( $extras as $e ) {
				$data[ $e['extra_key'] ] = $e['extra'];
			}
		}

		return $data;
	}

	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			//$sql = "SELECT * FROM `"._DB_PREFIX."customer` c WHERE ";
			//$sql .= " c.`customer_name` LIKE %$search_key%";
			//$results = qa($sql);
			$results = $this->get_products( array( 'general' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Product: ' );
					$match_string    .= _shl( $result['name'], $search_key );
					$ajax_results [] = '<a href="' . $this->link_open( $result['product_id'] ) . '">' . $match_string . '</a>';
					//$ajax_results [] = $this->link_open($result['customer_id'],true);
				}
			}
		}

		return $ajax_results;
	}


	public function pre_menu() {

		if ( $this->can_i( 'view', 'Products' ) && $this->can_i( 'edit', 'Products' ) ) {

			// how many products are there?
			$link_name = _l( 'Products' );

			if ( module_config::can_i( 'view', 'Settings' ) ) {
				$this->links['products'] = array(
					"name"                => $link_name,
					"p"                   => "product_settings",
					"args"                => array( 'product_id' => false ),
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			} else {
				$this->links['products'] = array(
					"name" => $link_name,
					"p"    => "product_settings",
					"args" => array( 'product_id' => false ),
				);
			}
		}

	}

	/** static stuff */


	public static function link_generate( $product_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'product_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

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

		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'product';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'product_settings';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['product_id'] = $product_id;
		$options['module']                  = 'product';

		if ( $options['page'] == 'product_admin' || $options['page'] == 'product_admin_category' ) {

			array_unshift( $link_options, $options );
			if ( $options['page'] == 'product_admin_category' ) {
				$options['data']         = self::get_product_category( $product_id );
				$options['data']['name'] = $options['data']['product_category_name'];
			}
			$options['page'] = 'product_settings';

			// bubble back onto ourselves for the link.
			return self::link_generate( $product_id, $options, $link_options );
		}
		// grab the data for this particular link, so that any parent bubbled link_generate() methods
		// can access data from a sub item (eg: an id)

		if ( isset( $options['full'] ) && $options['full'] ) {
			// only hit database if we need to print a full link with the name in it.
			if ( ! isset( $options['data'] ) || ! $options['data'] ) {
				if ( (int) $product_id > 0 ) {
					$data = self::get_product( $product_id );
				} else {
					$data = array();

					return _l( 'N/A' );
				}
				$options['data'] = $data;
			} else {
				$data = $options['data'];
			}
			// what text should we display in this link?
			$options['text'] = $data['name'];
		}
		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'product_id' => $product_id,
		);
		// generate the path (module & page) for this link
		$options['module'] = 'product';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! self::can_i( 'view', 'Products' ) ) {
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
			'argument' => 'product_id',
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


	public static function link_open( $product_id, $full = false, $data = array() ) {

		if ( $product_id === false ) {
			$product = new UCMProducts();

			return $product->link_open();
		} else {
			$product = new UCMProduct( $product_id );

			return $product->link_open( $full );
		}

		return self::link_generate( $product_id, array( 'full' => $full, 'data' => $data, 'page' => 'product_admin' ) );
	}

	public static function link_open_category( $product_category_id, $full = false, $data = array() ) {
		return self::link_generate( $product_category_id, array( 'full'      => $full,
		                                                         'data'      => $data,
		                                                         'page'      => 'product_admin_category',
		                                                         'arguments' => array( 'product_category_id' => $product_category_id )
		) );
	}


	public static function get_products( $search = array() ) {

		$sql = "SELECT * FROM `" . _DB_PREFIX . "product` p ";
		$sql .= " LEFT JOIN `" . _DB_PREFIX . "product_category` pc USING (product_category_id) ";
		$sql .= " WHERE 1 ";
		if ( isset( $search['general'] ) && strlen( trim( $search['general'] ) ) ) {
			$sql .= " AND ( p.name LIKE '%" . db_escape( $search['general'] ) . "%'";
			$sql .= " OR p.description LIKE '%" . db_escape( $search['general'] ) . "%'";
			$sql .= " OR pc.product_category_name LIKE '%" . db_escape( $search['general'] ) . "%'";
			$sql .= " )";
		}
		if ( isset( $search['name'] ) && strlen( trim( $search['name'] ) ) ) {
			$sql .= " AND p.name LIKE '%" . db_escape( $search['name'] ) . "%'";
		}
		if ( isset( $search['description'] ) && strlen( trim( $search['description'] ) ) ) {
			$sql .= " AND p.description LIKE '%" . db_escape( $search['description'] ) . "%'";
		}
		if ( isset( $search['product_category_name'] ) && strlen( trim( $search['product_category_name'] ) ) ) {
			$sql .= " AND pc.product_category_name LIKE '%" . db_escape( $search['product_category_name'] ) . "%'";
		}
		if ( isset( $search['product_id'] ) && (int) $search['product_id'] > 0 ) {
			$sql .= " AND p.product_id = " . (int) $search['product_id'];
		}
		if ( isset( $search['product_category_id'] ) && (int) $search['product_category_id'] > 0 ) {
			$sql .= " AND p.product_category_id = " . (int) $search['product_category_id'];
		}
		if ( isset( $search['inventory'] ) && (int) $search['inventory'] > 0 ) {
			switch ( $search['inventory'] ) {
				case 1: // in stock
					$sql .= " AND p.inventory_control = 1 AND p.inventory_level_current > 0";
					break;
				case 2: // out of stock
					$sql .= " AND p.inventory_control = 1 AND p.inventory_level_current <= 0";
					break;
				case 3: // no stock control
					$sql .= " AND p.inventory_control = 0";
					break;
			}
		}
		$sql .= " ORDER BY pc.product_category_name ASC, p.name ASC";

		return qa( $sql );

		//return get_multiple("product",$search,"product_id","fuzzy","name");
	}


	public static function get_product( $product_id ) {
		$product = get_single( 'product', 'product_id', $product_id );
		//echo $product_id;print_r($product);exit;
		if ( ! $product ) {
			$product = array(
				'name'                  => '',
				'product_category_id'   => '',
				'product_category_name' => '',
				'amount'                => '',
				'quantity'              => '',
				'currency_id'           => '',
				'description'           => '',
			);
		}
		if ( $product['product_category_id'] ) {
			$product_category                 = self::get_product_category( $product['product_category_id'] );
			$product['product_category_name'] = $product_category['product_category_name'];
		}

		return $product;
	}

	public static function get_product_categories( $search = array() ) {
		return get_multiple( "product_category", $search, "product_category_id", "fuzzy", "product_category_name" );
	}

	public static function get_product_category( $product_category_id ) {
		$product_category = get_single( 'product_category', 'product_category_id', $product_category_id );
		if ( ! $product_category ) {
			$product_category = array(
				'product_category_id'   => '',
				'product_category_name' => '',
			);
		}

		return $product_category;
	}


	public function handle_hook( $hook ) {
		switch ( $hook ) {
			case "home_alerts":
				$alerts = array();
				if ( module_config::c( 'product_alerts', 1 ) && self::can_i( 'view', 'Products' ) ) {
					$key = _l( 'Product Inventory Low' );
					if ( class_exists( 'module_dashboard', false ) ) {
						module_dashboard::register_group( $key, array(
							'columns' => array(
								'full_link'                 => _l( 'Product Name' ),
								'inventory_level_current'   => _l( 'Inventory Level' ),
								'inventory_low_stock_level' => _l( 'Low Stock Level' ),
							)
						) );
						$sql   = "SELECT * FROM `" . _DB_PREFIX . "product` p ";
						$sql   .= " WHERE p.inventory_low_stock_level > 0 AND p.inventory_level_current <= p.inventory_low_stock_level";
						$items = qa( $sql );
						foreach ( $items as $item ) {
							$alerts[] = array(
								'item'                      => $key,
								'date'                      => '',
								'link'                      => module_product::link_open( $item['product_id'], false ),
								'full_link'                 => module_product::link_open( $item['product_id'], true ),
								'inventory_level_current'   => $item['inventory_level_current'],
								'inventory_low_stock_level' => $item['inventory_low_stock_level'],
							);
						}
					}

				}

				return $alerts;
				break;
		}
	}

	public function process() {
		if ( "save_product" == $_REQUEST['_process'] ) {

			$product = new UCMProduct();
			$product->handle_submit();

		} else if ( "save_product_supplier" == $_REQUEST['_process'] ) {

			$product_supplier = new UCMProductSupplier();
			$product_supplier->handle_submit();

		} else if ( "save_product_category" == $_REQUEST['_process'] ) {
			if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['product_category_id'] ) {
				$data = self::get_product_category( $_REQUEST['product_category_id'] );
				if ( module_form::confirm_delete( 'product_category_id', _l( "Really delete product category: %s", $data['product_category_name'] ), self::link_open_category( $_REQUEST['product_category_id'] ) ) ) {
					$this->delete_product_category( $_REQUEST['product_category_id'] );
					set_message( "Product category deleted successfully" );
					redirect_browser( self::link_open_category( false ) );
				}
			}
			$product_category_id = $this->save_product_category( $_REQUEST['product_category_id'], $_POST );
			set_message( "Product category saved successfully" );
			redirect_browser( self::link_open_category( $product_category_id ) );
		}
	}


	public function save_product_category( $product_category_id, $data ) {
		$product_category_id = update_insert( "product_category_id", $product_category_id, "product_category", $data );

		//echo $product_category_id;print_r($data);exit;
		return $product_category_id;
	}


	public static function delete_product( $product_id ) {
		$product_id = (int) $product_id;
		$product    = self::get_product( $product_id );
		if ( $product && $product['product_id'] == $product_id ) {
			$sql = "DELETE FROM " . _DB_PREFIX . "product WHERE product_id = '" . $product_id . "' LIMIT 1";
			query( $sql );
			module_extra::delete_extras( 'product', 'product_id', $product_id );
		}
	}

	public function delete_product_category( $product_category_id ) {
		$product_category_id = (int) $product_category_id;
		delete_from_db( 'product_category', 'product_category_id', $product_category_id );
		$sql = "UPDATE `" . _DB_PREFIX . "product` SET product_category_id = 0 WHERE product_category_id = " . (int) $product_category_id;
		query( $sql );
	}

	public static function bulk_handle_delete() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['delete'] ) && $_REQUEST['bulk_action']['delete'] == 'yes' ) {
			// confirm deletion of these tickets:
			$product_ids = isset( $_REQUEST['bulk_operation'] ) && is_array( $_REQUEST['bulk_operation'] ) ? $_REQUEST['bulk_operation'] : array();
			foreach ( $product_ids as $product_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $product_ids[ $product_id ] );
				} else {
					$product_ids[ $product_id ] = self::link_open( $product_id, true );
				}
			}
			if ( count( $product_ids ) > 0 ) {
				if ( module_form::confirm_delete( 'product_id', "Really delete products: " . implode( ', ', $product_ids ), self::link_open( false ) ) ) {
					foreach ( $product_ids as $product_id => $product_number ) {
						self::delete_product( $product_id );
					}
					set_message( _l( "%s products deleted successfully", count( $product_ids ) ) );
					redirect_browser( self::link_open( false ) );
				}
			}
		}
	}


	private static $_product_count = false;

	public static function print_quote_task_dropdown( $quote_task_id = false, $quote_task_data = array() ) {
		if ( self::can_i( 'view', 'Products' ) ) {
			?>
			<span style="margin: 0 0 0 -23px; width: 20px; padding: 0; display: inline-block">
            <a href="#" onclick="return ucm.product.do_dropdown('<?php echo $quote_task_id; ?>',this);"
               class="product-dropdown"><i class="fa fa-chevron-circle-down"></i></a>
            <input type="hidden" name="quote_task[<?php echo $quote_task_id; ?>][product_id]"
                   id="task_product_id_<?php echo $quote_task_id; ?>" class="no_permissions"
                   value="<?php echo isset( $quote_task_data['product_id'] ) ? (int) $quote_task_data['product_id'] : '0'; ?>">
        </span>
			<?php
		}
	}

	public static function print_job_task_dropdown( $task_id = false, $task_data = array() ) {
		if ( self::can_i( 'view', 'Products' ) ) {
			?>
			<span style="margin: 0 0 0 -23px; width: 20px; padding: 0; display: inline-block">
            <a href="#" onclick="return ucm.product.do_dropdown('<?php echo $task_id; ?>',this);"
               class="product-dropdown"><i class="fa fa-chevron-circle-down"></i></a>
            <input type="hidden" name="job_task[<?php echo $task_id; ?>][product_id]"
                   id="task_product_id_<?php echo $task_id; ?>" class="no_permissions"
                   value="<?php echo isset( $task_data['product_id'] ) ? (int) $task_data['product_id'] : '0'; ?>">
        </span>
			<?php
		}
	}

	public static function print_invoice_task_dropdown( $task_id = false, $task_data = array() ) {
		if ( self::can_i( 'view', 'Products' ) ) {
			?>
			<span style="margin: 0 0 0 -23px; width: 20px; padding: 0; display: inline-block">
            <a href="#" onclick="return ucm.product.do_dropdown('<?php echo $task_id; ?>',this);"
               class="product-dropdown"><i class="fa fa-chevron-circle-down"></i></a>
            <input type="hidden" name="invoice_invoice_item[<?php echo $task_id; ?>][product_id]"
                   id="invoice_product_id_<?php echo $task_id; ?>" class="no_permissions"
                   value="<?php echo isset( $task_data['product_id'] ) ? (int) $task_data['product_id'] : '0'; ?>">
        </span>
			<?php
		}
	}

	public static function handle_import( $data, $add_to_group ) {

		// woo! we're doing an import.

		// our first loop we go through and find matching products by their "product_name" (required field)
		// and then we assign that product_id to the import data.
		// our second loop through if there is a product_id we overwrite that existing product with the import data (ignoring blanks).
		// if there is no product id we create a new product record :) awesome.

		foreach ( $data as $rowid => $row ) {
			if ( ! isset( $row['name'] ) || ! trim( $row['name'] ) ) {
				unset( $data[ $rowid ] );
				continue;
			}
			if ( ! isset( $row['product_id'] ) || ! $row['product_id'] ) {
				$data[ $rowid ]['product_id'] = 0;
			}
		}

		// now save the data.
		$count = 0;
		foreach ( $data as $rowid => $row ) {
			$row['product_id'] = update_insert( 'product_id', $row['product_id'], 'product', $row );
			if ( $row['product_id'] ) {
				// is there a category?
				if ( isset( $row['category_name'] ) && strlen( trim( $row['category_name'] ) ) ) {
					// find this category, if none exists then create it.
					$product_category = get_single( 'product_category', 'product_category_name', trim( $row['category_name'] ) );
					if ( ! $product_category ) {
						$product_category                        = array(
							'product_category_name' => trim( $row['category_name'] ),
						);
						$product_category['product_category_id'] = update_insert( 'product_category_id', false, 'product_category', $product_category );
					}
					if ( isset( $product_category['product_category_id'] ) && $product_category['product_category_id'] ) {
						$row['product_id'] = update_insert( 'product_id', $row['product_id'], 'product', array(
							'product_category_id' => $product_category['product_category_id'],
						) );
					}
				}
				$count ++;
			}
		}

		return $count;

	}


	public static function api_filter_product( $hook, $response, $endpoint, $method ) {
		$response['product'] = true;
		switch ( $method ) {
			case 'list':
				$search               = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
				$response['products'] = module_product::get_products( $search );
				break;
		}

		return $response;
	}


	// runs when invoice is saved or deleted
	public static function hook_invoice_saved( $hook, $invoice_id, $invoice_data = array(), $original_invoice_data = array() ) {
		$invoice_items = module_invoice::get_invoice_items( $invoice_id, $invoice_data );
		// we loop over this saved invoice and if we find any products we trigger an "update" of our cached inventory level value.
		// this inventory  level is calculated based on the original stock quantity levels minus the number of products that have been added to invoices. we cache it so we dont have to do this calcualtion each time.
		$products_ids = array();
		foreach ( $invoice_items as $invoice_item ) {
			if ( ! empty( $invoice_item['product_id'] ) ) {
				$products_ids[ $invoice_item['product_id'] ] = $invoice_item['product_id'];
			}
		}
		foreach ( $products_ids as $product_id ) {
			$data = self::get_product( $product_id );
			// we have to calculate the inventory level and save it into the inventory_level_current box.
			if ( ! empty( $data['inventory_control'] ) ) {
				$inventory_usage = 0;
				if ( (int) $product_id > 0 ) {
					// we calculate how much inventory has been currentl used,
					// add that ontop of the user supplied value so we can store our total used/current inventory
					// we do this so it's easier to add/remove qty as invoices are created/deleted


					$product = new UCMProduct( $product_id );
					$usages  = $product->get_product_usage();
					foreach ( $usages as $usage ) {
						if ( $usage['change_qty'] ) {
							$inventory_usage += $usage['count'];
						}
					}
					$new_qty = $data['inventory_level'] - $inventory_usage;
					if ( class_exists( 'module_log', false ) && module_log::is_plugin_enabled() && $data['inventory_level_current'] != $new_qty ) {
						module_log::log( 'inventory', $product_id, false, "Invoice Saved: " . $invoice_data['name'], "Quantity changed from " . $data['inventory_level_current'] . " to " . ( $new_qty ) . " after saving this invoice." );
					}
					update_insert( "product_id", $product_id, "product", array(
						'inventory_level_current' => $new_qty
					) );
					$product->trigger_low_stock_alerts();
				}
			}
		}
	}

	public static function hook_invoice_deleted( $hook, $invoice_id ) {
		$invoice_data  = module_invoice::get_invoice( $invoice_id );
		$invoice_items = module_invoice::get_invoice_items( $invoice_id, $invoice_data );
		// we loop over this saved invoice and if we find any products we trigger an "update" of our cached inventory level value.
		// this inventory  level is calculated based on the original stock quantity levels minus the number of products that have been added to invoices. we cache it so we dont have to do this calcualtion each time.
		$products_ids = array();
		foreach ( $invoice_items as $invoice_item ) {
			if ( ! empty( $invoice_item['product_id'] ) ) {
				// we store how many "qty" is been deleted, and hence return this number to the inventory pool
				$products_ids[ $invoice_item['product_id'] ] = $invoice_item['hours'];
			}
		}
		foreach ( $products_ids as $product_id => $qty_deleted ) {
			$data = self::get_product( $product_id );
			// we have to calculate the inventory level and save it into the inventory_level_current box.
			if ( ! empty( $data['inventory_control'] ) ) {
				$inventory_usage = 0;
				if ( (int) $product_id > 0 ) {
					// we calculate how much inventory has been currentl used,
					// add that ontop of the user supplied value so we can store our total used/current inventory
					// we do this so it's easier to add/remove qty as invoices are created/deleted
					$product = new UCMProduct( $product_id );
					$usages  = $product->get_product_usage();
					foreach ( $usages as $usage ) {
						if ( $usage['change_qty'] ) {
							$inventory_usage += $usage['count'];
						}
					}
					$inventory_usage -= $qty_deleted;
					if ( class_exists( 'module_log', false ) && module_log::is_plugin_enabled() ) {
						module_log::log( 'inventory', $product_id, false, "Invoice Deleted: " . $invoice_data['name'], "Quantity changed from " . $data['inventory_level_current'] . " to " . ( $data['inventory_level'] - $inventory_usage ) . " after removing " . $qty_deleted . " items from this deleted invoice." );
					}
					update_insert( "product_id", $product_id, "product", array(
						'inventory_level_current' => $data['inventory_level'] - $inventory_usage
					) );

				}
			}
		}
	}

	// handle quotes, jobs, invoices.
	public static function sanitise_product_name( $task_data, $default_task_type ) {

		$unit_measurement = false;
		$this_task_type   = false;
		if ( ! isset( $task_data['manual_task_type'] ) || ( isset( $task_data['manual_task_type'] ) && $task_data['manual_task_type'] < 0 ) ) {
			$this_task_type = $default_task_type;
		} else {
			$this_task_type = $task_data['manual_task_type'];
		}
		// use the default from quote/job/invoice
		$show_suffix = false;
		switch ( $this_task_type ) {
			case _TASK_TYPE_AMOUNT_ONLY:
				$unit_measurement = module_config::c( 'task_amount_name', 'Amount' );
				break;
			case _TASK_TYPE_QTY_AMOUNT:
				$unit_measurement = module_config::c( 'task_qty_name', 'Qty' );
				break;
			case _TASK_TYPE_HOURS_AMOUNT:
				$unit_measurement = module_config::c( 'task_hours_name', 'Hours' );
				$show_suffix      = true;
				break;
			default:
				$unit_measurement = module_config::c( 'task_hours_name', 'Unit' );
		}
		if ( ! isset( $task_data['unitname'] ) && ! empty( $task_data['product_id'] ) ) {
			$product_data = self::get_product( $task_data['product_id'] );
			if ( $product_data && ! empty( $product_data['unitname'] ) ) {
				$task_data['unitname'] = $product_data['unitname'];
			}
		}
		if ( empty( $task_data['unitname'] ) ) {
			$task_data['unitname'] = $unit_measurement;
		} else {
			$show_suffix = true;
		}
		// stops double handling.
		if ( isset( $task_data['unitname_show'] ) ) {
			$show_suffix = $task_data['unitname_show'];
		}
		$task_data['unitname_show'] = $show_suffix;

		return $task_data;
	}

	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'task' );
		if ( ! isset( $fields['product_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'task` ADD `product_id` INT(11) NOT NULL DEFAULT \'0\' AFTER `task_order`;';
		}
		$fields = get_fields( 'product' );
		if ( ! isset( $fields['default_task_type'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `default_task_type` INT(11) NOT NULL DEFAULT \'-1\' AFTER `currency_id`;';
		}
		if ( ! isset( $fields['billable'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `billable` INT(11) NOT NULL DEFAULT \'1\' AFTER `default_task_type`;';
		}
		if ( ! isset( $fields['taxable'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `taxable` INT(11) NOT NULL DEFAULT \'1\' AFTER `billable`;';
		}
		if ( ! isset( $fields['inventory_control'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `inventory_control` TINYINT(1) NOT NULL DEFAULT \'0\' AFTER `taxable`;';
		}
		if ( ! isset( $fields['inventory_level'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `inventory_level` double(10,4) NOT NULL DEFAULT \'0\' AFTER `inventory_control`;';
		}
		if ( ! isset( $fields['inventory_level_current'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `inventory_level_current` double(10,4) NOT NULL DEFAULT \'0\' AFTER `inventory_level`;';
		}
		if ( ! isset( $fields['purchase_price'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `purchase_price` double(10,4) NOT NULL DEFAULT \'0\' AFTER `inventory_level`;';
		}
		if ( ! isset( $fields['unitname'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `unitname` varchar(50) NOT NULL DEFAULT \'\' AFTER `purchase_price`;';
		}
		if ( ! isset( $fields['inventory_low_stock_level'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'product` ADD `inventory_low_stock_level` int(11) NOT NULL DEFAULT \'0\' AFTER `inventory_level_current`;';
		}
		self::add_table_index( 'product', 'inventory_low_stock_level' );

		if ( ! $this->db_table_exists( 'product_category' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'product_category` (
  `product_category_id` int(11) NOT NULL auto_increment,
  `product_category_name` varchar(255) NOT NULL DEFAULT \'\',
  `date_created` date NOT NULL,
  `date_updated` date NULL,
  PRIMARY KEY  (`product_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}
		if ( ! $this->db_table_exists( 'product_supplier' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'product_supplier` (
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `customer_id` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY  (`product_id`, `customer_id`),
    INDEX (`customer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;';
		}

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>product` (
		`product_id` int(11) NOT NULL auto_increment,
		`product_category_id` int(11) NOT NULL DEFAULT '0',
		`name` varchar(255) NOT NULL DEFAULT '',
		`description` TEXT NOT NULL DEFAULT '',
		`quantity` double(10,2) NOT NULL DEFAULT '0',
		`amount` double(10,2) NOT NULL DEFAULT '0',
		`currency_id` INT NOT NULL DEFAULT '1',
		`default_task_type` INT NOT NULL DEFAULT '-1',
		`billable` INT NOT NULL DEFAULT '1',
		`taxable` INT NOT NULL DEFAULT '1',
		`inventory_control` TINYINT(1) NOT NULL DEFAULT '0',
		`inventory_level` double(10,4) NOT NULL DEFAULT '0',
		`inventory_level_current` double(10,4) NOT NULL DEFAULT '0',
		`inventory_low_stock_level` int(11) NOT NULL DEFAULT '0',
		`purchase_price` double(10,4) NOT NULL DEFAULT '0',
		`unitname` varchar(50) NOT NULL DEFAULT '',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`product_id`),
		INDEX (`inventory_low_stock_level`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>product_category` (
		`product_category_id` int(11) NOT NULL auto_increment,
		`product_category_name` varchar(255) NOT NULL DEFAULT '',
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`product_category_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>product_supplier` (
		`product_id` int(11) NOT NULL DEFAULT '0',
		`customer_id` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY  (`product_id`, `customer_id`),
		INDEX (`customer_id`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;


		<?php
		return ob_get_clean();
	}


}


include_once 'class.product.php';