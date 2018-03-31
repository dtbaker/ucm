<?php

class UCMBaseMulti{

	public $db_id = 'table_id';
	public $db_table = 'table';
	public $db_fields = array();
	public $db_fields_all = array();
	public $display_name = 'Widget';
	public $display_name_plural = 'Widgets';

	public function __construct(){

		if(empty($this->db_fields_all)){
			$this->db_fields_all = get_fields($this->db_table);
		}
		if(empty($this->db_fields)){
			$this->db_fields = $this->db_fields_all;
		}
	}

	/*private static $instance;
	static function singleton() {
		if(!isset(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}*/

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


	public function get($search=array(), $order_by = array()){
		$conn = $this->get_db();
		if($conn){
			$sql = 'SELECT t.* FROM `' . _DB_PREFIX . $this->db_table . '` t ';
			$where = '';
			if(count($search)){
				$where .= " WHERE 1 ";
			}
			$key_index=1;
			foreach($search as $key=>$val){
				if( !is_array($val) && strlen($val) && isset( $this->db_fields_all[$key] ) ) {
					$where .= " AND `$key` = :$key ";
					$this->db->bind_param( $key, $val );
				}else if(is_array($val) && !empty($val['condition']) && !empty($val['values']) && isset($this->db_fields_all[$key])){
					$where .= ' AND ( ';
					switch(strtolower($val['condition'])){
						case 'or':
							$where_or = array();
							foreach($val['values'] as $or_key => $or_value){
								if( strlen($or_value)) {
									$where_or []= " `$key` = :" . $key . $key_index;
									$this->db->bind_param( $key . $key_index, $or_value );
									$key_index++;
								}
							}
							if(count($where_or)){
								$where .= implode(' OR ', $where_or);
							}
							break;
						// others here?
					}
					$where .= ' ) ';
				}
			}

			// search based on group id
			if( !empty($search['group_id']) && $search['group_id']){
				if( !is_array($search['group_id']) ){
					$new_search = array();
					$new_search[ $this->db_table ] = $search['group_id'];
					$search['group_id'] = $new_search;
					unset($new_search);
				}
				foreach($search['group_id'] as $group_owner_table => $group_owner_id){
					$group_owner_table = preg_replace('#[^a-z_]#', '', $group_owner_table);
					if( $group_owner_table ) {
						$group_owner_id = (int) $group_owner_id;
						if ( $group_owner_id ) {
							// this is all escaped properly above.
							$sql .= " LEFT JOIN `" . _DB_PREFIX . "group_member` g$group_owner_table ON (t." . $this->db_id . " = g$group_owner_table.owner_id)";
							$where .= " AND (g$group_owner_table.group_id = $group_owner_id AND g$group_owner_table.owner_table = '" . $group_owner_table . "')";
						}
					}
				}
			}

			if($order_by){
				$order_by_string = array();
				foreach($order_by as $field => $direction){
					if( isset($this->db_fields_all[$field]) ){
						switch(strtolower($direction)){
							case 'asc':
								$order_by_string[] =  '`'.$field.'` ASC';
								break;
							default:
								$order_by_string[] = '`'.$field.'` DESC';
								break;
						}
					}
				}
				if(count($order_by_string)){
					$where .= ' ORDER BY ';
					$where .= implode(' , ', $order_by_string);
				}
			}

			$this->db->prepare($sql . $where);

			if($this->db->execute()) {
				// Save returned row
				return $this->db->resultset();
			}
		}
		return array();
	}

	public function link_open( $link_options = array() ){

		$link_options[] = array(
			'full' => false,
			'type' => $this->db_table,
			'module' => $this->db_table,
			'page' => $this->db_table . '_admin',
			'arguments' => array(),
			'data' => array(),
			'text' => $this->display_name
		);
		return link_generate($link_options);
	}



}