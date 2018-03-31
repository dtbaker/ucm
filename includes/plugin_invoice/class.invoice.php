<?php
defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:

if ( class_exists( 'UCMBaseDocument' ) ) {

	class UCMInvoice extends UCMBaseDocument {

		public $db_id = 'invoice_id';
		public $db_table = 'invoice';
		public $display_key = 'name';
		public $display_name = 'Invoice';
		public $display_name_plural = 'Invoices';
		public $db_fields = array();
		public $document_type = 'invoice'; // quote, job


		private static $invinstances = array();

		static function singleton( $id ) {
			if ( $id ) {
				if ( ! isset( self::$invinstances[ $id ] ) ) {
					self::$invinstances[ $id ] = new static( $id );
				}

				return self::$invinstances[ $id ];
			}

			return new static();
		}


		public function find_duplicate_numbers() {
			$other_invoices = new UCMInvoices();
			$rows           = $other_invoices->get( array( 'name' => $this->get( 'name' ) ) );
			foreach ( $rows as $row_id => $row ) {
				if ( $row[ $this->db_id ] === $this->id ) {
					unset( $rows[ $row_id ] );
				}
				// we have to remove any from previous years if the year check flag is set
				if ( module_config::c( $this->document_type . '_increment_date_check', '' ) == 'Y' && date( 'Y', strtotime( $this->get( 'date_create' ) ) ) != date( 'Y', strtotime( $row['date_create'] ) ) ) {
					// the same invoice number for the same year.
					unset( $rows[ $row_id ] );
				}
			}

			return $rows;
		}


		public function link_public( $h = false ) {
			if ( $h ) {
				return md5( 's3cret7hash for invoice ' . _UCM_SECRET . ' ' . $this->id );
			}

			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.invoice/h.public/i.' . $this->id . '/hash.' . $this->link_public( true ) );
		}

	}

	class UCMInvoices extends UCMBaseMulti {

		public $db_id = 'invoice_id';
		public $db_table = 'invoice';
		public $display_name = 'Invoice';
		public $display_name_plural = 'Invoices';


	}


}