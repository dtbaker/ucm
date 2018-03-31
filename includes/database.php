<?php

$dbcnx = false;

function db_connect() {
	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::db_connect' ) ) {
		return module_db::db_connect();
	}
	global $dbcnx;
	if ( ! $dbcnx ) {
		$dbcnx = false; //@mysql_pconnect(_DB_SERVER,_DB_USER,_DB_PASS); // or die(mysql_error());
		if ( ! $dbcnx ) {
			$dbcnx = @mysql_connect( _DB_SERVER, _DB_USER, _DB_PASS ) or die( mysql_error() );
		}
		if ( ! $dbcnx || mysql_errno() ) {
			set_error( mysql_error() );

			return false;
		}
		mysql_select_db( _DB_NAME, $dbcnx );
		query( "SET @@SESSION.sql_mode = ''" );
	}

	return $dbcnx;
}

function db_close( $db ) {
	mysql_close( $db );
}

function query( $sql, $debug_message = '' ) {

	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::query' ) ) {
		return module_db::query( $sql, $debug_message );
	}
	//echo ''.$sql.'<br>';
	if ( _DEBUG_MODE && defined( '_DEBUG_SQL' ) && _DEBUG_SQL ) {
		static $past_queries = array();
		if ( ! isset( $past_queries[ $sql ] ) ) {
			$past_queries[ $sql ] = 0;
		} else {
			$past_queries[ $sql ] ++;
		}
		$sql_debug = $sql;
		if ( strlen( $sql_debug ) > 60 ) {
			$sql_debug = htmlspecialchars( substr( $sql_debug, 0, 60 ) ) . '<a href="#" onclick="$(this).hide(); $(\'span\',$(this).parent()).show(); return false;">....</a><span style="display:none">' .
			             htmlspecialchars( substr( $sql, 60 ) ) . '</span>';
		} else {
			$sql_debug = htmlspecialchars( $sql );
		}
		if ( class_exists( 'module_debug', false ) ) {
			module_debug::log( array(
				'title'     => 'SQL Query',
				'file'      => 'includes/database.php',
				'data'      => '(' . ( $past_queries[ $sql ] > 0 ? '<span style="color:#FF0000; font-weight:bold;">' . $past_queries[ $sql ] . '</span>' : $past_queries[ $sql ] ) . ') ' . $debug_message . $sql_debug,
				'important' => $past_queries[ $sql ] > 0,
			) );
		}
	}

	$res = mysql_query( $sql ); //or die(mysql_error() . $sql);
	if ( mysql_errno() ) {
		set_error( _l( 'SQL Error: %s', mysql_error() . ' ' . $sql ) );
		set_error( _l( 'Try clicking the "Run Manual Upgrades" button to resolve SQL Errors.' ) );

		return false;
	}

	return $res;
}

/*function db_clean_deep(&$value){
    $value = is_array($value) ? array_map('db_clean_deep', $value) : stripslashes(htmlspecialchars($value));
    return $value;
}*/
function query_to_array( $res ) {
	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::query_to_array' ) ) {
		return module_db::query_to_array( $res );
	}
	$array = array();
	if ( ! $res ) {
		return $array;
	}
	while ( $row = mysql_fetch_assoc( $res ) ) {
		if ( isset( $row['id'] ) && $row['id'] ) {
			$array[ $row['id'] ] = $row;
		} else {
			$array[] = $row;
		}
	}

	return $array;
}

function qa( $sql ) {
	$res = query_to_array( query( $sql ) );

	return $res;
}

function qa1( $sql ) {
	$res = qa( $sql );
	if ( $res && is_array( $res ) ) {
		return array_shift( $res );
	}

	return false;
}

function get_fields( $table, $ignore = array(), $hidden = array(), $from_cache = false ) {
	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::get_fields' ) ) {
		return module_db::get_fields( $table, $ignore, $hidden, $from_cache );
	}
	if ( is_array( $table ) || ! trim( $table ) ) {
		return array();
	}
	static $fieldscache = array();
	if ( isset( $fieldscache[ $table ] ) ) {
		return $fieldscache[ $table ];
	}
	$res = $db_cache = array();
	if ( $from_cache ) {
		$db_cache = module_cache::get( 'db', 'db_fields_' . $table );
		if ( ! is_array( $db_cache ) ) {
			$db_cache = array();
		}
		if ( isset( $db_cache[ $table ] ) ) {
			$res = $db_cache[ $table ];
		}
	}
	if ( ! count( $res ) ) {
		$sql = "SHOW FIELDS FROM `" . _DB_PREFIX . "$table`";
		$res = qa( $sql );
		if ( ! is_array( $db_cache ) ) {
			$db_cache = array();
		}
		$db_cache[ $table ] = $res;
		module_cache::put( 'db', 'db_fields_' . $table, $db_cache, 172800 );
	}
	$fields = array();
	foreach ( $res as $r ) {
		$format = "";
		$type   = 'text';
		if ( count( $ignore ) && in_array( $r['Field'], $ignore ) ) {
			continue;
		}
		if ( count( $hidden ) && in_array( $r['Field'], $hidden ) ) {
			$type = "hidden";
			// new field for file.
		} else if ( preg_match( "/^file_/", $r['Field'] ) && preg_match( "/varchar\((\d+)\)/", $r['Type'], $matches ) ) {
			$type      = "file";
			$size      = 50;
			$maxlength = 255;
		} else if ( preg_match( "/varchar\((\d+)\)/", $r['Type'], $matches ) ) {
			$type      = "text";
			$size      = max( "10", min( "30", $matches[1] ) );
			$maxlength = $matches[1];
		} else if ( preg_match( "/int/i", $r['Type'] ) || preg_match( "/float/i", $r['Type'] ) ) {
			$format    = array( "/^\d+$/", "Integer" );
			$type      = "number";
			$maxlength = $size = 20;
		} else if ( $r['Type'] == "text" ) {
			$type = "textarea";
			$size = 0;
		} else if ( $r['Type'] == "date" || $r['Type'] == "datetime" ) {
			$format    = array( "/^\d\d\d\d-\d\d-\d\d$/", "YYYY-MM-DD" );
			$type      = "date";
			$maxlength = $size = 20;
		} else if ( preg_match( "/decimal/", $r['Type'] ) || preg_match( "/double/", $r['Type'] ) ) {
			$format    = array( "/^\d+\.?[\d+]?$/", "Decimal" );
			$type      = "decimal";
			$maxlength = $size = 20;
		}
		$required = false;
		if ( $r['Null'] == "NO" ) {
			$required = true;
		}
		$fields[ $r['Field'] ] = array( "name"      => $r['Field'],
		                                "type"      => $type,
		                                "dbtype"    => $r['Type'],
		                                "size"      => $size,
		                                "maxlength" => $maxlength,
		                                "required"  => $required,
		                                "format"    => $format
		);
	}
	$fieldscache[ $table ] = $fields;

	return $fields;
}


function delete_from_db( $table, $key, $val ) {
	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::delete_from_db' ) ) {
		return module_db::delete_from_db( $table, $key, $val );
	}
	$table = db_escape( $table );
	if ( ! is_array( $key ) ) {
		$key = array( $key );
	}
	if ( ! is_array( $val ) ) {
		$val = array( $val );
	}

	$sql = "DELETE FROM `" . _DB_PREFIX . "$table` WHERE ";
	foreach ( $key as $kid => $k ) {
		$sql .= "`" . db_escape( $k ) . "` = '" . db_escape( $val[ $kid ] ) . "' AND ";
	}
	$sql = rtrim( $sql, " AND " );
	if ( isset( $fields['system_id'] ) && defined( '_SYSTEM_ID' ) ) {
		$sql .= " AND system_id = '" . _SYSTEM_ID . "'";
	}
	if ( query( $sql ) ) {
		return true;
	} else {
		return false;
	}
}

function get_single( $table, $key, $val ) {

	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::get_single' ) ) {
		return module_db::get_single( $table, $key, $val );
	}
	$table = db_escape( $table );
	if ( ! is_array( $key ) ) {
		$key = array( $key );
	}
	if ( ! is_array( $val ) ) {
		$val = array( $val );
	}

	if ( is_array( $table ) ) {
		echo 'error, please let us know about this.';

		return array();
	}

	//$fields = get_fields($table,array("date_created","date_updated"));

	$sql = "SELECT * FROM `" . _DB_PREFIX . "$table` WHERE ";
	foreach ( $key as $kid => $k ) {
		$sql .= "`" . db_escape( $k ) . "` = '" . db_escape( $val[ $kid ] ) . "' AND ";
	}
	$sql = rtrim( $sql, " AND " );
	//if(isset($fields['system_id']) && defined('_SYSTEM_ID')){
	//	$sql .= " AND system_id = '"._SYSTEM_ID."'";
	//}
	$res = qa( $sql );
	if ( count( $res ) ) {
		$res = array_shift( $res );
		// set correct types on data.
		//print_r($fields);exit;
		/*foreach($fields as $field){
			if(!isset($res[$field['name']]))continue;
			//if($field['type']=='number'){
            if(isset($field['format']) && $field['format']){
                $format = current($field['format']);
                if($format){
				    //$res[$field['name']] = preg_replace($format,'',$res[$field['name']]);
                }
			}
		}*/
		$return = $res;
		/*if(!module_security::can_access_data($table,$res)){
			$return = array();
		}else{
			$return = $res;
		}*/
	} else {
		$return = array();
	}

	return $return;
}

function get_single_row( $table, $keys, $vals ) {
	return get_single( $table, $keys, $vals );
}

function update_insert( $pkey, $pid, $table, $data = false, $do_replace = false ) {

	if ( class_exists( 'module_db', false ) && is_callable( 'module_db::update_insert' ) ) {
		return module_db::update_insert( $pkey, $pid, $table, $data, $do_replace );
	}
	if ( $data === false ) {
		$data = $_REQUEST;
	}
	$fields = get_fields( $table, array( "date_created", "date_updated" ), array(), true ); //
	if ( isset( $fields['system_id'] ) && defined( '_SYSTEM_ID' ) ) {
		$data['system_id'] = _SYSTEM_ID;
	}
	if ( isset( $fields['date_created'] ) ) {
		unset( $fields['date_created'] );
	}

	$now_string = db_escape( date( 'Y-m-d H:i:s' ) );
	if ( $do_replace || ! is_numeric( $pid ) || ! $pid ) {
		$pid = 'new';
		if ( $do_replace ) {
			$sql = "REPLACE INTO ";
		} else {
			$sql = "INSERT INTO ";
		}
		$sql .= "`" . _DB_PREFIX . "$table` SET date_created = '$now_string', ";
		if ( isset( $fields['create_user_id'] ) && isset( $_SESSION['_user_id'] ) && $_SESSION['_user_id'] ) {
			$sql .= "`create_user_id` = '" . (int) $_SESSION['_user_id'] . "', ";
			unset( $fields['create_user_id'] );
		}
		if ( isset( $fields['create_ip_address'] ) ) {
			$sql .= "`create_ip_address` = '" . db_escape( $_SERVER['REMOTE_ADDR'] ) . "', ";
			unset( $fields['create_ip_address'] );
		}
		// check there's a valid site id
		if ( isset( $fields['site_id'] ) && ( ! isset( $data['site_id'] ) || ! $data['site_id'] ) && isset( $_SESSION['_site_id'] ) ) {
			$data['site_id'] = $_SESSION['_site_id'];
		}
		$where = "";
		//module_security::sanatise_data($table,$data);
		// todo - sanatise data here before we go through teh loop.
		// if sanatisation fails or data access fails then we stop the update/insert.
		if ( ! $data ) {
			// dont do this becuase $email->new_email() fails.
			// return false;
		}
	} else {
		// TODO - security hook here, check if we can access this data.
		/*$security_dummy=array();
		if(!module_security::can_access_data($table,$security_dummy,$pid)){
			echo 'Security warning - unable to save data';
			exit;
			return false;
		}*/
		$updated = false;
		if ( isset( $data['date_updated'] ) ) {
			$updated = "'" . db_escape( input_date( $data['date_updated'], true ) ) . "'";
		}
		if ( ! $updated ) {
			$updated = "'$now_string'";
		}
		$sql = "UPDATE `" . _DB_PREFIX . "$table` SET date_updated = $updated,";
		if ( isset( $fields['update_user_id'] ) && isset( $_SESSION['_user_id'] ) && $_SESSION['_user_id'] ) {
			$sql .= "`update_user_id` = '" . (int) $_SESSION['_user_id'] . "', ";
			unset( $fields['update_user_id'] );
		}
		if ( isset( $fields['update_ip_address'] ) ) {
			$sql .= "`update_ip_address` = '" . db_escape( $_SERVER['REMOTE_ADDR'] ) . "', ";
			unset( $fields['update_ip_address'] );
		}
		$where = " WHERE `$pkey` = '" . db_escape( $pid ) . "'";
		if ( isset( $fields['system_id'] ) && defined( '_SYSTEM_ID' ) ) {
			$where .= " AND system_id = '" . _SYSTEM_ID . "'";
		}
	}

	//print_r($fields);exit;
	//print_r($data);exit;

	if ( ! $do_replace && isset( $data[ $pkey ] ) ) {
		unset( $data[ $pkey ] );
	}

	foreach ( $fields as $field ) {
		if ( ! isset( $data[ $field['name'] ] ) || $data[ $field['name'] ] === false ) {
			continue;
		}

		// special format for date fields.
		if ( $field['type'] == 'date' ) {
			$data[ $field['name'] ] = input_date( $data[ $field['name'] ] );
		}
		// special format for int / double fields.
		if ( ( $field['type'] == 'decimal' || $field['type'] == 'double' ) && function_exists( 'number_in' ) ) {
			$data[ $field['name'] ] = number_in( $data[ $field['name'] ] );
		}

		if ( is_array( $data[ $field['name'] ] ) ) {
			$val = serialize( $data[ $field['name'] ] );
		} else {
			$val = $data[ $field['name'] ];
		}
		$sql .= " `" . $field['name'] . "` = '" . db_escape( $val ) . "', ";
	}
	$sql = rtrim( $sql, ', ' );
	$sql .= $where;
	query( $sql );
	if ( $pid == "new" ) {
		$pid = mysql_insert_id();
	}

	return $pid;
}

function get_col_vals( $table, $key, $val = false, $order = false ) {
	if ( ! $order ) {
		$order = $val;
	}
	if ( ! $val ) {
		$val = $key;
	}
	if ( strpos( $val, '{' ) === false ) {
		$val = '{' . $val . '}';
	}
	if ( preg_match_all( '/\{([^\}]+)\}/', $val, $matches ) ) {
		$dbval = '';
		foreach ( $matches[1] as $v ) {
			$dbval .= ', `' . db_escape( $v ) . '`';
		}
	} else {
		echo 'Fail on get_col_Vals';
		exit;
	}
	$key    = db_escape( $key );
	$sql    = "SELECT `$key` as k $dbval";
	$sql    .= " FROM `" . _DB_PREFIX . "$table`";
	$fields = get_fields( $table, array(), array(), true );
	if ( isset( $fields['system_id'] ) && defined( '_SYSTEM_ID' ) ) {
		$sql .= " WHERE `system_id`  = '" . _SYSTEM_ID . "'";
	}
	$sql .= " GROUP BY k";
	if ( $order ) {
		$sql .= " ORDER BY " . db_escape( $order ) . "";
	}
	$res    = qa( $sql );
	$return = array();
	foreach ( $res as $r ) {
		$v = $val;
		foreach ( $matches[1] as $dbv ) {
			$v = preg_replace( '/\{' . preg_quote( $dbv, '/' ) . '\}/', $r[ $dbv ], $v );
		}
		$return[ $r['k'] ] = $v;
	}

	//module_security::filter_data_set($table,$return);
	return $return;
}

function get_multiple( $table, $search = false, $id = false, $search_type = "exact", $order = false ) {


	$sql = "SELECT *";
	if ( $id ) {
		$sql .= ",`$id` AS id";
	}
	$sql    .= " FROM `" . _DB_PREFIX . "$table`";
	$fields = get_fields( $table, array(), array(), true );
	// we force the system id searching if it exists.
	if ( isset( $fields['system_id'] ) && defined( '_SYSTEM_ID' ) ) {
		$search['system_id'] = _SYSTEM_ID;
	}
	if ( is_array( $search ) ) {
		$sql .= " WHERE 1";
		foreach ( $search as $key => $val ) {
			$this_search_type = $search_type;
			$spesh            = false;
			if ( trim( $val ) == '' || $val === false ) {
				continue;
			}
			// switch types if searching on numbers..
			// this allows easy fuzzy and exact matches
			// when we have forms that allow user input and drop down id input.
			if ( isset( $fields[ $key ] ) && $fields[ $key ]['type'] == 'number' ) {
				$this_search_type = 'exact';
			}
			if ( isset( $fields[ $key ] ) && $fields[ $key ]['type'] == 'date' ) {
				// we need to format the user input to the database friendly date
				$val = input_date( $val );
			}
			// check the operator type
			$operator = "=";
			switch ( $key[0] ) {
				case "<":
					$operator = "<=";
					$spesh    = true;
					$key      = substr( $key, 1 );
					break;
				case ">":
					$operator = ">=";
					$spesh    = true;
					$key      = substr( $key, 1 );
					break;
			}
			$foo = explode( "|", $key );
			$sql .= " AND (";
			foreach ( $foo as $k ) {
				if ( ! isset( $fields[ $k ] ) ) {
					continue;
				}
				if ( $spesh ) {
					$sql .= " `$k` $operator '" . db_escape( $val ) . "'";
				} else if ( $this_search_type == "fuzzy" ) {
					$sql .= " `$k` LIKE '%" . db_escape( $val ) . "%'";
				} else if ( $this_search_type == "exact" ) {
					$sql .= " `$k` = '" . db_escape( $val ) . "'";
				}
				$sql .= " OR ";
			}
			$sql = rtrim( $sql, " OR " );
			$sql .= ") ";
			$sql = str_replace( ' AND () ', '', $sql ); // incase any of them have incorrect fields.
		}
	}
	if ( $order ) {
		if ( strpos( $order, ' ' ) === false && strpos( $order, '`' ) === false ) {
			$order = '`' . $order . '`';
		}
		$sql .= " ORDER BY " . db_escape( $order ) . "";
	}
	$result = qa( $sql );

	//module_security::filter_data_set($table,$result);
	return $result;
}


// filter results based on current users permissions.
// pass this off to the individual modules to filter their results out.
// MOVED TO SECURITY MODULE
/*function filter_results($result,$table_name,$type='single'){
	$fake = (object)'na';
	$filtered_results = handle_hook("restrict_result",$fake,$result,$table_name,$type);
	if(is_array($filtered_results)){
		// we have a set of filtered results from the modules!
		// if there's only 1 result this means only 1 module has applied filtering, we use that one

		if(count($filtered_results) == 1){
			$result = array_shift($filtered_results);
		}else if(count($filtered_results) > 1){
			// todo find out the combinations of all these filtered results.
			echo 'todo filter';
			$result = array();
		}
	}
	return $result;
}*/

