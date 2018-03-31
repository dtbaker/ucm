<?php
class iimysqli_result {
	public $stmt, $nCols;
}
if( ! function_exists('mysqli_stmt_get_result')) {


	function iimysqli_stmt_get_result( $stmt ) {

		$metadata = mysqli_stmt_result_metadata( $stmt );
		$ret      = new iimysqli_result;
		if ( ! $ret ) {
			return null;
		}

		$ret->nCols = mysqli_num_fields( $metadata );
		$ret->stmt  = $stmt;

		mysqli_free_result( $metadata );

		return $ret;
	}


	function iimysqli_result_fetch_array( &$result ) {

		$stmt = $result->stmt;
		$stmt->store_result();
		$resultkeys = array();
		$thisName   = "";
		for ( $i = 0; $i < $stmt->num_rows; $i ++ ) {
			$metadata = $stmt->result_metadata();
			while ( $field = $metadata->fetch_field() ) {
				$thisName     = $field->name;
				$resultkeys[] = $thisName;
			}
		}

		$ret  = array();
		$code = "return mysqli_stmt_bind_result(\$result->stmt ";
		for ( $i = 0; $i < $result->nCols; $i ++ ) {
			$ret[ $i ] = null;
			if ( isset( $resultkeys[ $i ] ) ) {
				$theValue = $resultkeys[ $i ];
			} else {
				$theValue = $i;
			}
			$code .= ", \$ret['$theValue']";
		}


		$code .= ");";
		if ( ! eval( $code ) ) {
			return null;
		};

		// This should advance the "$stmt" cursor.
		if ( ! mysqli_stmt_fetch( $result->stmt ) ) {
			return null;
		};

		// Return the array we built.
		return $ret;
	}

}


class UCMDatabase{

	private static $instance;

	static function singleton() {
		if(!isset(self::$instance))
			self::$instance = new self();
        self::$instance->reset();
		return self::$instance;
	}

	private $db_link = false;

	public function __construct() {
		$this->db_link = db_connect();
		$this->reset();
	}

	private $sql = '';
	public $params = array();

	public function reset(){
		$this->sql = '';
		$this->params = array();
	}
	public function prepare( $sql_statement ){
		// support for named parameters
		$this->sql = $sql_statement;
		if(preg_match_all('#:(\w+)(?=$|\W)#', $sql_statement, $params)){
			foreach($params[1] as $param){
				if( empty($this->params[$param] ) ) {
					$this->params[ $param ] = array(
						'type'  => 'string',// default to string.
						'value' => '',
					);
				}
			}
		}
	}

	public function bind_param( $param, $value, $type = false){
		$this->params[$param]['value'] = $value;
		if($type){
			$this->params[$param]['type'] = $type;
		}
		if(!$type && empty($this->params[$param]['type'])){
			// find the type from the db.
			$this->params[$param]['type'] = 'string';
		}

	}


	private $stmt;
	private $stmt_result;

	public function execute( ){

		$refarg = array(false, '');

		// hack for php5.3.2
		$i = 0;
		foreach ($this->params as $key => $settings) {
			$bind_name = 'bind' . $i;

			if(!empty($settings['type']) && !empty($settings['value'])){
				switch($settings['type']){
					case 'date':
						$settings['value'] = input_date($settings['value']);
						break;
				}
			}

			$$bind_name = $settings['value'];
			$refarg[] = &$$bind_name;
			$i++;
		}

		foreach($this->params as $key=>$settings){
			$this->sql = preg_replace('#:'.$key.'(?=$|\W)#', '?', $this->sql);
			//$refarg[] = &$settings['value'];
			switch($settings['type']){
				case 'int':
				case 'number':
				case 'decimal':
					$refarg[1] .= 'd';
					break;
				case 'string':
				default:
					$refarg[1] .= 's';
					break;
			}
		}

		$this->stmt = mysqli_prepare( $this->db_link, $this->sql );
		if(!$this->stmt){
			set_error(mysqli_error($this->db_link) . " - " . $this->sql);
			return false;
		}else {
			if($this->params) {
				$refarg[0] = $this->stmt;
				call_user_func_array( "mysqli_stmt_bind_param", $refarg );
			}
			if ( $this->stmt_result = mysqli_stmt_execute( $this->stmt ) ) {
				return true;
			} else {
				set_error(mysqli_stmt_error($this->stmt));
				return false;
			}
		}
	}
	public function resultset( ){
		if($this->stmt_result){

			if( ! function_exists('mysqli_stmt_get_result')) {
				$result = iimysqli_stmt_get_result( $this->stmt );
				$return = array();
				while ( $row = iimysqli_result_fetch_array( $result ) ) {
					$return[] = $row;
				}
			}else {
				$result = mysqli_stmt_get_result( $this->stmt );
				$return = array();
				while ( $row = mysqli_fetch_array( $result, MYSQLI_ASSOC ) ) {
					$return[] = $row;
				}
			}
			$this->close();
			return $return;
		}
		return false;
	}
	public function close(){
		mysqli_stmt_close($this->stmt);
	}
	public function single( ){

		if($this->stmt_result){
			if( ! function_exists('mysqli_stmt_get_result')) {
				$result = iimysqli_stmt_get_result( $this->stmt );
				$rows   = iimysqli_result_fetch_array( $result );
			}else {
				$result = mysqli_stmt_get_result( $this->stmt );
				$rows   = mysqli_fetch_array( $result, MYSQLI_ASSOC );
			}
			$this->close();
			return $rows;
		}
		return false;

	}

	public function insert_id(){
		return mysqli_insert_id($this->db_link);
	}


}