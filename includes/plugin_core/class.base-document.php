<?php

// this is the base class used by Quotes, Jobs and Invoices.
// it allows us to share functionality between plugins a bit nicer.

// should have done this from the get go, this is a recent addition and there will be lots of code to change


define( '_ARCHIVED_SEARCH_NONARCHIVED', 1 );
define( '_ARCHIVED_SEARCH_ARCHIVED', 2 );
define( '_ARCHIVED_SEARCH_BOTH', 3 );

class UCMBaseDocument extends UCMBaseSingle {

	public $document_type = 'invoice'; // quote, job
	public $enable_tax = true;
	public $enable_task_numbers = true;
	public $enable_tasks = true;

	public function get_billing_type() {
		// based on the customer billing type.
		// this is either a vendor/supplier or a normal customer.
		// this changes how finances are displayed in the system.
		/*
		 * eg: Supplier with a $20/month subscription. The invoice is generated from the supplier to us. We pay the supplier. We record it as an expense in our system.
		 * this needs to save the status of an invoice on a per invoice basis, incase the customer status is changed down the track.
		 * we base the initial value of this invoice off the customer type.
		 */
		return $this->get( 'billing_type' );
	}

	public function default_values() {
		parent::default_values();
	}


	public function is_archived() {
		return $this->get( 'archived' );
	}

	public function archive() {
		if ( $this->id ) {
			$this->update( 'archived', 1 );
		}
	}

	public function unarchive() {
		if ( $this->id ) {
			$this->update( 'archived', 0 );
		}
	}

	public $manual_prefix = '';

	public function set_manual_prefix( $prefix ) {
		$this->manual_prefix = $prefix;
	}

	public $manual_incrementing_group_name = '';

	public function set_incrementing_config_name( $group_name ) {
		$this->manual_incrementing_group_name = $group_name;
	}


	public function get_new_document_number() {

		$default_number = '';

		if ( function_exists( 'custom_' . $this->document_type . '_number' ) and $this->get( 'customer_id' ) ) {
			$default_number = call_user_func( 'custom_' . $this->document_type . '_number', $this->get( 'customer_id' ) );
		}

		if ( ! strlen( $default_number ) ) {


			$number_prefix = '';
			if ( $this->manual_prefix ) {
				$number_prefix = $this->manual_prefix;
			} else {
				if ( $this->get( 'customer_id' ) > 0 ) {
					// old way of doing customer default configs:
					if ( $this->document_type == 'invoice' ) {
						$customer_data = module_customer::get_customer( $this->get( 'customer_id' ) );
						if ( $customer_data && ! empty( $customer_data['default_invoice_prefix'] ) ) {
							$number_prefix = $customer_data['default_invoice_prefix'];
						} else {
							$number_prefix = module_config::c( 'default_' . $this->document_type . '_prefix', '' );
						}
					} else {
						$number_prefix = module_customer::c( 'default_' . $this->document_type . '_prefix', '', $this->get( 'customer_id' ) );
					}
				} else {
					$number_prefix = module_config::c( 'default_' . $this->document_type . '_prefix', '' );
				}
			}


			// old code:
			if ( $this->document_type == 'invoice' && module_config::c( 'invoice_name_match_job', 0 ) && isset( $_REQUEST['job_id'] ) && (int) $_REQUEST['job_id'] > 0 ) {
				$job = module_job::get_job( $_REQUEST['job_id'] );

				// todo: confirm tis isn't a data leak risk oh well.
				return $number_prefix . $job['name'];
			}

			/*if(module_config::c('invoice_name_match_job',0) && isset($_REQUEST['job_id']) && (int)$_REQUEST['job_id']>0){
                $job = module_job::get_job($_REQUEST['job_id']);
                // todo: confirm tis isn't a data leak risk oh well.
                $invoice_number = $invoice_prefix.$job['name'];
            }*/

			if ( $this->manual_incrementing_group_name ) {
				$config_key = $this->manual_incrementing_group_name;
			} else {
				if ( $this->document_type == 'invoice' ) {
					$config_key = 'invoice_incrementing';
				} else {
					$config_key = $this->document_type . '_name_incrementing';
				}
			}

			$default_number = module_customer::c( $this->document_type . '_default_new_name', '', $this->get( 'customer_id' ) );
			if ( module_customer::c( $config_key, 1, $this->get( 'customer_id' ) ) ) {
				$document_number        = module_customer::c( $config_key . '_next', 1, $this->get( 'customer_id' ) );
				$document_number_format = module_customer::c( $config_key . '_format', '', $this->get( 'customer_id' ) );
				// $document_number could be something like ABC00001 - we try to strip the characters out so we can increment the number value.

				// see if there is an quote number matching this one.
				$this_document_number = $document_number;
				do {

					$this_document_number_formatted = $this_document_number;
					if ( $document_number_format ) {
						$this_document_number_formatted = sprintf( $document_number_format, $this_document_number );
					}


					$documents = get_multiple( $this->db_table, array( 'name' => $number_prefix . $this_document_number_formatted . $default_number ) ); //'customer_id'=>$customer_id,

					if ( ! count( $documents ) ) {
						$document_number = $this_document_number;
					} else {

						// an invoice exists with this same number.
						// is it from last year?
						if ( module_config::c( $this->document_type . '_increment_date_check', '' ) == 'Y' ) {
							$has_year_match = false;
							foreach ( $documents as $document ) {
								if ( date( 'Y' ) == date( 'Y', strtotime( $document['date_create'] && $document['date_create'] != '0000-00-00' ? $document['date_create'] : $document['date_created'] ) ) ) {
									$has_year_match = true;
									break;
								}
							}
							if ( ! $has_year_match ) {
								// this invoice number is from last year, we can use it.
								$document_number = $this_document_number;
								break;
							}
						}

						$this_document_number ++;
					}
				} while ( count( $documents ) );

				// next auto worst idea ever: removing.
				//				if(module_config::c($config_key.'_next_auto',0)) {
				//					module_customer::save_config( $config_key . '_next', $document_number + 1, $this->get( 'customer_id' ) );
				//				}else{
				module_customer::save_config( $config_key . '_next', $document_number, $this->get( 'customer_id' ) );
				//				}

				if ( $document_number_format ) {
					$document_number = sprintf( $document_number_format, $document_number );
				}
				$default_number = $document_number . $default_number;
			} else {
				// we base the number on a date string:
				$document_number = date( 'ymd', module_invoice::$new_invoice_number_date ? strtotime( module_invoice::$new_invoice_number_date ) : time() );

				//$invoice_number = $invoice_prefix . date('ymd');
				// check if this invoice number exists for this customer
				// if it does exist we create a suffix a, b, c, d etc..
				// this isn't atomic - if two invoices are created for the same customer at the same time then
				// this probably wont work. but for this system it's fine.
				$this_document_number = $document_number;
				$suffix_ascii         = 65; // 65 is A
				$suffix_ascii2        = 0; // 65 is A
				do {
					if ( $suffix_ascii == 91 ) {
						// we've exhausted all invoices for today.
						$suffix_ascii = 65; // reset to A
						if ( ! $suffix_ascii2 ) {
							// first loop, start with A
							$suffix_ascii2 = 65; // set 2nd suffix to A, work with this.
						} else {
							$suffix_ascii2 ++; // move from A to B
						}

					}
					$invoices = get_multiple( $this->db_table, array( 'name' => $number_prefix . $this_document_number . $default_number ) ); //'customer_id'=>$customer_id,
					if ( ! count( $invoices ) ) {
						$document_number = $this_document_number;
						break;
					} else {
						$this_document_number = $document_number . ( $suffix_ascii2 ? chr( $suffix_ascii2 ) : '' ) . chr( $suffix_ascii );
					}
					$suffix_ascii ++;
				} while ( count( $invoices ) && $suffix_ascii <= 91 && $suffix_ascii2 <= 90 ); //90 is Z
				$default_number = $document_number . $default_number;
			}

			$default_number = $number_prefix . $default_number;
		}

		return $default_number;


	}

	public function is_document_locked() {
		return false;
	}

	public function generate_calendar_fieldset() {

		// the options set here hook into the legacy module_job::hook_calendar_events() method for calendar display.


		if ( class_exists( 'module_calendar' ) && module_calendar::is_plugin_enabled() ) {

			$fieldset_data = array(
				'heading'  => array(
					'type'  => 'h3',
					'title' => 'Calendar',
				),
				'class'    => 'tableclass tableclass_form tableclass_full',
				'elements' => array(),
			);

			$fieldset_data['elements']['calendar_show'] = array(
				'title' => 'Calendar',
				'field' => array(
					'name'    => 'calendar_show',
					'type'    => 'select',
					'blank'   => ' - Default - ',
					'options' => array(
						1 => 'Show On Calendar',
						2 => 'Hide From Calendar',
					),
					'value'   => $this->get( 'calendar_show' ),
				),
			);
			// todo: let the user choose what date to display on the calendar: Start Date, Due Date, Finished date, etc..

			$fieldset_data['elements']['time_start'] = array(
				'title'  => 'Start Time',
				'ignore' => ! ( class_exists( 'module_calendar', false ) && module_config::c( $this->document_type . '_show_times', 1 ) ),
				'field'  => array(
					'type'  => 'time',
					'name'  => 'time_start',
					'value' => $this->get( 'time_start' ),
					'help'  => 'This is the time the ' . $this->document_type . ' is scheduled to start.  If you have the Calendar, this is the time that will be used for the Calendar event.',
				),
			);
			$fieldset_data['elements']['time_end']   = array(
				'title'  => 'End Time',
				'ignore' => ! ( class_exists( 'module_calendar', false ) && module_config::c( $this->document_type . '_show_times', 1 ) ),
				'field'  => array(
					'type'  => 'time',
					'name'  => 'time_end',
					'value' => $this->get( 'time_end' ),
					'help'  => 'This is the time the ' . $this->document_type . ' is scheduled to finish.  If you have the Calendar, this is the time that will be used for the Calendar event.',
				),
			);

			// todo: permissions on who can view this calendar entry (customers, staff, everyone)
			return $fieldset_data;
		}

		return false;
	}


	public function generate_advanced_fieldset() {

		$fieldset_data = array(
			'id'       => $this->document_type . '_advanced', // used for css and hooks
			'heading'  => array(
				'type'  => 'h3',
				'title' => 'Advanced',
			),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(),
		);

		// external link
		if ( $this->id ) {
			$fieldset_data['elements']['customer_link'] = array(
				'title' => 'Customer Link',
				'field' => array(
					'type'  => 'html',
					'value' => '<a href="' . $this->link_public() . '" target="_blank">' . _l( 'Click to view external link' ) . '</a>',
					'help'  => 'You can send this link to your customer and they can preview the document without logging in.',
				),
			);
		}


		//// Print/PDF Template

		foreach (
			array(
				array( 'print', 'PDF Template' ),
				array( 'email', 'Email Template' ),
				array( 'external', 'External Template' ),
			) as $data
		) {

			$template_type  = $data[0];
			$template_title = $data[1];

			$find_other_templates = $this->document_type . '_' . $template_type; // invoice_print, job_print etc..

			$current_template = $this->get( $this->document_type . '_template_' . $template_type );
			if ( function_exists( 'convert_html2pdf' ) && $find_other_templates ) {
				$other_templates = array();
				foreach ( module_template::get_templates() as $possible_template ) {
					if ( strpos( $possible_template['template_key'], $find_other_templates ) !== false ) {
						// found another one!
						$other_templates[ $possible_template['template_key'] ] = $possible_template['template_key']; //$possible_template['description'];
					}
				}
				if ( count( $other_templates ) > 1 ) {
					$fieldset_data['elements'][ $this->document_type . '_template_' . $template_type ] = array(
						'title' => $template_title,
						'field' => array(
							'type'    => 'select',
							'options' => $other_templates,
							'name'    => $this->document_type . '_template_' . $template_type,
							'value'   => $current_template,
							'blank'   => ' - Default - ',
							'help'    => 'Choose the default ' . $template_title . '. Name your custom templates ' . $this->document_type . '_' . $template_type . '_SOMETHING for them to appear in this listing.',
						),
					);
				}
			}
		}


		if ( module_customer::can_i( 'view', 'Customers' ) ) {


			$fieldset_data['elements']['customer_id']     = array(
				'title' => 'Customer',
				'field' => array(
					'type'   => 'text',
					'name'   => 'customer_id',
					'value'  => $this->get( 'customer_id' ),
					'lookup' => array(
						'key'         => 'customer_id',
						'display_key' => 'customer_name',
						'plugin'      => 'customer',
						'lookup'      => 'customer_name',
						'return_link' => true,
						'display'     => '',
					),
				),
			);
			$fieldset_data['elements']['contact_user_id'] = array(
				'title'  => _l( 'Contact' ),
				'fields' => array(
					array(
						'type'   => 'text',
						'name'   => 'contact_user_id',
						'value'  => $this->get( 'contact_user_id' ),
						'lookup' => array(
							'key'         => 'user_id',
							'display_key' => 'name',
							'plugin'      => 'user',
							'lookup'      => 'contact_name',
							'return_link' => true,
						),
					)
				),
			);


			/*if($this->get('customer_id')){
				$c = array();
				$res = module_user::get_contacts(array('customer_id'=>$this->get('customer_id')),false,false);
				$primary_contact = false;
				while($row = mysqli_fetch_assoc($res)){
					$c[$row['user_id']] = $row['name'].' '.$row['last_name'];
					if($row['primary_user_id'] == $row['user_id']){
						$primary_contact = $row;
					}
				}
				$c[-1] = _l('Primary (%s)',$primary_contact ? htmlspecialchars($primary_contact['name'].' '.$primary_contact['last_name']) : _l('N/A'));
				$contact_user_id = $this->get('contact_user_id');

				if($contact_user_id > 0 && !isset($c[$contact_user_id])){
					// this option isn't in the listing. add it in.
					$user_temp = module_user::get_user($contact_user_id,false);
					$c[$contact_user_id] = $user_temp['name'].' '.$user_temp['last_name'] . ' '._l('(under different customer)');
				}
				$fieldset_data['elements'][] = array(
					'title'  => 'Contact',
					'field' => array(
						'type' => 'select',
						'name' => 'contact_user_id',
						'value' => $contact_user_id,
						'options' => $c,
						'blank' => false,
					),
				);
			}*/
		}


		if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() && module_website::can_i( 'view', 'Websites' ) ) {

			$fieldset_data['elements']['website_id'] = array(
				'title' => module_config::c( 'project_name_single', 'Website' ),
				'field' => array(
					'type'   => 'text',
					'name'   => 'website_id',
					'value'  => $this->get( 'website_id' ),
					'lookup' => array(
						'key'         => 'website_id',
						'display_key' => 'name',
						'plugin'      => 'website',
						'lookup'      => 'name',
						'return_link' => true,
						'display'     => '',
					),
				),
			);

			/*
			$website_ids = $this->get('website_ids'); // todo: this wont work for invoices yet, copy code from old core get_website()
			if($website_ids && !is_array($website_ids)){
				$website_ids = explode(',',$website_ids);
			}
			$website_id = $this->get('website_id');
			if(!$website_id && $website_ids){
				$website_id = array_shift($website_ids);
			}
			$fieldset_data['elements'][] = array(
				'title'  => module_config::c( 'project_name_single', 'Website' ),
				'fields' => array(
					function () use ( &$invoice ) {
						$website_ids = isset($invoice['website_ids']) && is_array($invoice['website_ids']) ? $invoice['website_ids'] : (isset($invoice['website_ids']) ? explode(',',$invoice['website_ids']) : array());
						if(!$invoice['website_id']){
							$invoice['website_id'] = array_shift($website_ids);
						}
						if ( module_invoice::can_i( 'edit', 'Invoices' ) ) {
							$c = array();
							// change between websites within this customer?
							// or websites all together?
							$res = module_website::get_websites( array( 'customer_id' => ( isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : ($invoice['customer_id'] ? $invoice['customer_id'] : false) ) ) );
							//$res = module_website::get_websites();
							while ( $row = array_shift( $res ) ) {
								$c[ $row['website_id'] ] = $row['name'];
							}
							echo print_select_box( $c, 'website_id', $invoice['website_id'] );
						} else {
							if ( $invoice['website_id'] ) {
								echo module_website::link_open( $invoice['website_id'], true );
							} else {
								_e( 'N/A' );
							}
						}
						foreach($website_ids as $website_id){
							if($website_id){
								echo ' '.module_website::link_open( $website_id, true );
							}
						}
					}
				),
			);*/
		} else if ( ! class_exists( 'module_website', false ) && module_config::c( 'show_ucm_ads', 1 ) ) {

			$fieldset_data['elements']['website_id'] = array(
				'title'  => module_config::c( 'project_name_single', 'Website' ),
				'fields' => array(
					'(website option available in <a href="https://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker" target="_blank">UCM Pro Edition</a>)'
				),
			);
		}


		if ( $this->enable_tasks && $this->enable_task_numbers && module_config::c( $this->document_type . '_show_task_numbers', 1 ) ) {
			$fieldset_data['elements'][] = array(
				'title' => 'Task Numbers',
				'field' => array(
					'type'    => 'select',
					'options' => array(
						0 => _l( 'Automatic' ),
						1 => _l( 'Manual' ),
						2 => _l( 'Hidden' ),
					),
					'name'    => 'auto_task_numbers',
					'value'   => $this->get( 'auto_task_numbers' ),
				),
			);
		}


		if ( $this->enable_tax ) {

			$fieldset_data['elements']['tax_type'] = array(
				'title' => 'Tax Type',
				'field' => array(
					'type'    => 'select',
					'blank'   => false,
					'options' => array( '0' => _l( 'Tax Added' ), 1 => _l( 'Tax Included' ) ),
					'name'    => 'tax_type',
					'value'   => $this->get( 'tax_type' ),
				),
			);
		}

		$discounts_allowed = true;
		if ( $this->document_type == 'job' && $this->get( 'deposit_job_id' ) ) {
			$discounts_allowed = false;
		}
		if ( $this->document_type == 'contract' ) {
			$discounts_allowed = false;
		}

		if ( $discounts_allowed ) {

			$self                                         = $this;
			$fieldset_data['elements']['discount_amount'] = array(
				'title'  => 'Discount Amount',
				'fields' => array(
					function () use ( $self ) {
						echo $self->is_document_locked() ?
							'<span class="currency">' . dollar( $self->get( 'discount_amount' ), true, $self->get( 'currency_id' ) ) . '</span>' :
							currency( '<input type="text" name="discount_amount" value="' . number_out( $self->get( 'discount_amount' ) ) . '" class="currency">' );
						echo ' ';
					},
					array(
						'type'  => 'html',
						'value' => '',
						'help'  => 'Here you can apply a before tax discount to this invoice. You can name this anything, eg: DISCOUNT, CREDIT, REFUND, etc..',
					)
				),
			);
			$fieldset_data['elements']['discount_name']   = array(
				'title'  => 'Discount Name',
				'fields' => array(
					function () use ( $self ) {
						echo $self->is_document_locked() ?
							htmlspecialchars( $self->get( 'discount_description' ) ) :
							'<input type="text" name="discount_description" value="' . htmlspecialchars( $self->get( 'discount_description' ) ) . '" style="width:80px;">';
					}
				),
			);
			$fieldset_data['elements']['discount_type']   = array(
				'title' => 'Discount Type',
				'field' => array(
					'type'    => 'select',
					'blank'   => false,
					'options' => array( '0' => _l( 'Before Tax' ), 1 => _l( 'After Tax' ) ),
					'name'    => 'discount_type',
					'value'   => $this->get( 'discount_type' ),
				),
			);
		}


		if ( $this->enable_tasks ) {

			$fieldset_data['elements'][] = array(
				'title' => 'Task Type',
				'field' => array(
					'type'    => 'select',
					'blank'   => false,
					'options' => module_job::get_task_types(),
					'name'    => 'default_task_type',
					'value'   => $this->get( 'default_task_type' ),
					'help'    => 'The default is hourly rate + amount. This will show the "Hours" column along with an "Amount" column. Inputing a number of hours will auto complete the price based on the job hourly rate. <br>Quantity and Amount will allow you to input a Quantity (eg: 2) and an Amount (eg: $100) and the final price will be $200 (Quantity x Amount). The last option "Amount Only" will just have the amount column for manual input of price. Change the advanced setting "default_task_type" between 0, 1 and 2 to change the default here.',
				),
			);
		}


		return $fieldset_data;

	}

	public function get_task( $task_id ) {
		// return an object for this particular job task.

	}

}

class UCMBaseTask extends UCMBaseSingle {

	public function __construct( $document = false, $id = false ) {

		$this->document = $document;

		parent::__construct( $id );

	}

	public function load( $id = false ) {
		$id = (int) $id;

		parent::load( $id );
		if ( $id && $this->id && $this->db_details ) {
			if ( is_callable( 'module_product::sanitise_product_name' ) && isset( $this->db_details['default_task_type'] ) ) {
				$this->db_details = module_product::sanitise_product_name( $this->db_details, $this->db_details['default_task_type'] );
			}
		}
	}

	public function get_fieldset_data() {

		$self = $this;


		$hours_unit = $this->get( 'unitname' );
		if ( ! $hours_unit ) {
			$hours_unit = 'Hours';
		}

		$fieldset_data = array(
			'id'       => 'job_task',
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(
				'description'      => array(
					'title'  => _l( 'Description' ),
					'fields' => array(
						array(
							'type'         => 'text',
							'name'         => 'description',
							'class'        => 'edit_task_description',
							'id'           => 'task_desc_' . $self->get( $self->db_id ),
							'autocomplete' => 'off',
							'value'        => $this->description,
						),
						function () use ( $self ) {
							if ( class_exists( 'module_product', false ) ) {
								module_product::print_job_task_dropdown( $self->get( $self->db_id ), $self->get() );
							}
						}
					),
				),
				'long_description' => array(
					'title' => _l( 'Details' ),
					'field' => array(
						'type'  => 'wysiwyg',
						'name'  => 'long_description',
						'id'    => 'task_long_desc_' . $self->get( $self->db_id ),
						'value' => $this->long_description,
					),
				),
				'hours'            => array(
					'title' => $hours_unit,
					'field' => array(
						'type'  => 'number',
						'name'  => 'hours',
						'class' => 'task_dynamic_hours',
						'id'    => 'task_hours_' . $self->get( $self->db_id ),
						'value' => $this->hours,
					),
				),
				'amount'           => array(
					'title' => _l( 'Amount' ),
					'field' => array(
						'type'  => 'number',
						'id'    => '' . $self->get( $self->db_id ) . 'taskamount',
						'class' => 'task_dynamic_amount',
						'name'  => 'amount',
						'value' => $this->amount,
					),
				),
				'total'            => array(
					'title' => _l( 'Total' ),
					'field' => array(
						'type'  => 'html',
						'id'    => '' . $self->get( $self->db_id ) . 'tasktotal',
						'class' => 'task_dynamic_total',
						'name'  => 'total',
						'value' => dollar( '0' ),
						//	                    'help' => 'This is the calculated total of this line item. Quantity x Amount.'
					),
				),
				'date_due'         => array(
					'title' => _l( 'Date Due' ),
					'field' => array(
						'type'  => 'date',
						'name'  => 'date_due',
						'value' => print_date( $this->date_due ),
					),
				),
				'date_done'        => array(
					'title' => _l( 'Date Done' ),
					'field' => array(
						'type'  => 'date',
						'name'  => 'date_done',
						'value' => print_date( $this->date_done ),
					),
				),

			)
		);

		if ( module_config::c( 'job_allow_staff_assignment', 1 ) ) {


			$staff_members    = module_user::get_staff_members();
			$staff_member_rel = array();
			foreach ( $staff_members as $staff_member ) {
				$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
			}


			$fieldset_data['elements']['staff_ids'] = array(
				'title' => _l( 'Staff' ),
				'field' => array(
					//                    'multiple' => true,
					'type'    => 'select',
					'name'    => 'user_id',
					'value'   => $this->user_id,
					'options' => $staff_member_rel,
				),
			);
		}
		$fieldset_data['elements']['billable']        = array(
			'title' => _l( 'Billable' ),
			'field' => array(
				'type'  => 'checkbox',
				'name'  => 'billable',
				'id'    => 'billable_t_' . $self->get( $self->db_id ),
				'value' => $this->billable,
			),
		);
		$fieldset_data['elements']['taxable']         = array(
			'title' => _l( 'Taxable' ),
			'field' => array(
				'type'  => 'checkbox',
				'name'  => 'taxable',
				'id'    => 'taxable_t_' . $self->get( $self->db_id ),
				'value' => $this->taxable,
			),
		);
		$fieldset_data['elements']['fully_completed'] = array(
			'title' => _l( 'Completed' ),
			'field' => array(
				'type'  => 'checkbox',
				'name'  => 'fully_completed',
				'value' => $this->fully_completed,
			),
		);

		$types                                         = module_job::get_task_types();
		$types['-1']                                   = _l( 'Default (%s)', $types[ $this->document->default_task_type ] );
		$fieldset_data['elements']['manual_task_type'] = array(
			'title' => _l( 'Task Type' ),
			'field' => array(
				'type'    => 'select',
				'name'    => 'manual_task_type',
				'id'      => 'manual_task_type_' . $self->get( $self->db_id ),
				'options' => $types,
				'blank'   => false,
				'value'   => $this->manual_task_type, // or manual_task_type_real ?
			),
		);

		return $fieldset_data;

	}

	public function generate_edit_fieldset() {

		return module_form::generate_fieldset( $this->get_fieldset_data() );

	}

}