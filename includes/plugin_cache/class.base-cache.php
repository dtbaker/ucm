<?php


class UCMBaseCache{


	public $cache_key = 'cache';
	public $unique_by_user_id = true;
	// hooks that we use to clear this cache entry from the database.
	public $invalidate_hooks = array();
	// if the cache is invalidated we generate it again:
	public $regenerate_after_invalidate = true;


	/**
	 * @var UCMDatabase
	 */
	public $db;

	/**
	 * @return UCMDatabase
	 */
	public function get_db(){

		if(!$this->db){
			$this->db = class_exists('UCMDatabase') ? UCMDatabase::singleton() : false;
		}
		$this->db->reset();
		return $this->db;
	}


	public function create_new($data){
		$conn = $this->get_db();
		if($conn){

			$this->db_details;
			$fields = array();
			foreach($data as $field => $value) {
				if ( isset( $this->db_fields[ $field ] ) ) {
					$this->db_details[ $field ] = $value;
				}
			}
			foreach($this->db_details as $field => $value) {
				if ( isset( $this->db_fields[ $field ] ) ) {
					$fields[] = ' `' . $field . '` = :' . $field;
					$this->db->bind_param( $field, $value );
				}
			}

			$this->db->prepare('INSERT INTO `' . _DB_PREFIX . $this->db_table . '` SET '.implode(', ',$fields).'');

			if($this->db->execute()){
				$insert_id = $this->db->insert_id();
				$this->load($insert_id);
				return $insert_id;
			}
		}
		return false;
	}


}