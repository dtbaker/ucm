<?php

defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:


if ( class_exists( 'UCMBaseDocument' ) ) {

	class UCMQuote extends UCMBaseDocument {

		public $db_id = 'quote_id';
		public $db_table = 'quote';
		public $display_key = 'name';
		public $display_name = 'Quote';
		public $display_name_plural = 'Quotes';
		public $db_fields = array();
		public $document_type = 'quote'; // job, invoice


		private static $quoteinstances = array();

		static function singleton( $id ) {
			if ( $id ) {
				if ( ! isset( self::$quoteinstances[ $id ] ) ) {
					self::$quoteinstances[ $id ] = new static( $id );
				}

				return self::$quoteinstances[ $id ];
			}

			return new static();
		}

		public function find_duplicate_numbers() {
			$other_quotes = new UCMQuotes();
			$rows         = $other_quotes->get( array( 'name' => $this->get( 'name' ) ) );
			foreach ( $rows as $row_id => $row ) {
				if ( $row[ $this->db_id ] === $this->id ) {
					unset( $rows[ $row_id ] );
				}
			}

			return $rows;
		}

		public function link_public( $h = false ) {
			if ( $h ) {
				return md5( 's3cret7hash for quote ' . _UCM_SECRET . ' ' . $this->id );
			}

			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.quote/h.public/i.' . $this->id . '/hash.' . $this->link_public( true ) );
		}

	}

	class UCMQuotes extends UCMBaseMulti {

		public $db_id = 'quote_id';
		public $db_table = 'quote';
		public $display_name = 'Quote';
		public $display_name_plural = 'Quotes';


	}


}