<?php
defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:


class UCMCustomer extends UCMBaseSingle {

	public $db_id = 'customer_id';
	public $db_table = 'customer';
	public $display_key = 'name';
	public $display_name = 'Customer';
	public $display_name_plural = 'Customers';
	public $db_fields = array();

	public function get_billing_type() {
		// based on the customer billing type.
		// this is either a vendor/supplier or a normal customer.
		// this changes how finances are displayed in the system.
		/*
		 * eg: Supplier with a $20/month subscription. The invoice is generated from the supplier to us. We pay the supplier. We record it as an expense in our system.
		 * this needs to save the status of an invoice on a per invoice basis, incase the customer status is changed down the track.
		 * we base the initial value of this invoice off the customer type.
		 */
		// todo: query customer_type table based on `customer_type_id` and find the billing type in there.

		return $this->get( 'billing_type' );
	}

	public function get_portal_sections() {
		return array(
			'Contracts',
			'Quotes',
			'Jobs',
			'Invoices & Payments',
			'Websites',
			'Tickets',
			'Timers',
			'Shop',
		);
	}

	public function is_archived() {
		return $this->get( 'archived' );
	}

	public function archive() {
		if ( $this->id ) {
			$this->update( 'archived', 1 );
			hook_handle_callback( 'customer_archived', $this->id );
		}
	}

	public function unarchive() {
		if ( $this->id ) {
			$this->update( 'archived', 0 );
			hook_handle_callback( 'customer_unarchived', $this->id );
		}
	}
}

class UCMCustomers extends UCMBaseMulti {

	public $db_id = 'customer_id';
	public $db_table = 'customer';
	public $display_name = 'Customer';
	public $display_name_plural = 'Customers';


}

