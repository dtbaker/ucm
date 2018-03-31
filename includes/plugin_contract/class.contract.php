<?php

defined( '_UCM_VERSION' ) || die( '-5' );

// (slowly) moving everything over to better OOP classes:


if ( class_exists( 'UCMBaseDocument' ) ) {

	class UCMContract extends UCMBaseDocument {

		public $db_id = 'contract_id';
		public $db_table = 'contract';
		public $display_key = 'name';
		public $display_name = 'Contract';
		public $display_name_plural = 'Contracts';
		public $db_fields = array();
		public $document_type = 'contract'; // job, invoice

		public $enable_tax = false;
		public $enable_task_numbers = false;
		public $enable_tasks = false;


		private static $contractinstances = array();

		static function singleton( $id ) {
			if ( $id ) {
				if ( ! isset( self::$contractinstances[ $id ] ) ) {
					self::$contractinstances[ $id ] = new static( $id );
				}

				return self::$contractinstances[ $id ];
			}

			return new static();
		}

		public function find_duplicate_numbers() {
			$other_contracts = new UCMContracts();
			$rows            = $other_contracts->get( array( 'name' => $this->get( 'name' ) ) );
			foreach ( $rows as $row_id => $row ) {
				if ( $row[ $this->db_id ] === $this->id ) {
					unset( $rows[ $row_id ] );
				}
			}

			return $rows;
		}

		public function link_public( $h = false ) {
			if ( $h ) {
				return md5( 's3cret7hash for contract ' . _UCM_SECRET . ' ' . $this->id );
			}

			return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.contract/h.public/i.' . $this->id . '/hash.' . $this->link_public( true ) );
		}

		public function is_active() {
			return $this->get( 'date_approved' ) && $this->get( 'date_approved' ) !== '0000-00-00' && (
				(
					( ! $this->get( 'date_terminate' ) || $this->get( 'date_terminate' ) == '0000-00-00' ) ||
					( $this->get( 'date_terminate' ) && $this->get( 'date_terminate' ) != '0000-00-00' && strtotime( $this->get( 'date_terminate' ) ) >= time() ) )
				);
		}

		public function get_products() {
			$UCMContractProducts = new UCMContractProducts();
			$contract_products   = array();
			if ( $this->id > 0 ) {
				foreach ( $UCMContractProducts->get( array( 'contract_id' => $this->id ) ) as $key => $val ) {
					if ( $val['contract_id'] == $this->id ) {
						$contract_products[ $val['product_id'] ] = $val['contract_id'];
					}
				}
			}

			return $contract_products;
		}

	}

	class UCMContracts extends UCMBaseMulti {

		public $db_id = 'contract_id';
		public $db_table = 'contract';
		public $display_name = 'Contract';
		public $display_name_plural = 'Contracts';


	}

	class UCMContractProduct extends UCMBaseSingle {

		public $db_id = array( 'contract_id', 'product_id' );
		public $db_table = 'contract_product';
		public $display_key = 'contract_id';
		public $display_name = 'Contract Product';
		public $display_name_plural = 'Contract Products';
		public $db_fields = array();

	}

	class UCMContractProducts extends UCMBaseMulti {

		public $db_id = array( 'contract_id', 'product_id' );
		public $db_table = 'contract_product';
		public $display_name = 'Contract';
		public $display_name_plural = 'Contracts';


	}


}