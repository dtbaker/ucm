<?php



class module_db extends module_base{

    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
	public static function get_class() {
        return __CLASS__;
    }
	public function init(){
		$this->module_name = "db";
		$this->module_position = 0;

        $this->version = 2.140;
        //2.140 - 2017-05-30 - upgrade fix
        //2.139 - 2017-05-02 - speed fixes
        //2.138 - 2017-05-02 - big changes
        //2.137 - 2017-01-12 - db fix
        //2.136 - 2015-12-14 - encoding fixes
        //2.135 - 2015-11-29 - db improvements
        //2.134 - 2015-11-02 - new db layout
        //2.133 - 2015-07-25 - config.php parse fixing
        //2.132 - 2015-07-19 - config.php parse fixing
        //2.131 - 2015-07-10 - mysqli fixes
        //2.13 - 2015-07-10 - finally upgraded to MySQLi
        //2.12 - 2015-01-20 - cache speed improvement
        //2.11 - 2014-12-22 - decimal currency improvement
        //2.1 - 2013-07-18 - initial release
	}

    private static $fieldscache=array();
    public static function get_fields($table,$ignore=array(),$hidden=array(), $from_cache=false){
        if(is_array($table)||!trim($table))return array();
        if(isset(self::$fieldscache[$table])){
            return self::$fieldscache[$table];
        }
        $res = $db_cache = array();
        if($from_cache){
            $db_cache = module_cache::get('db','db_fields_'.$table);
            if(!is_array($db_cache))$db_cache=array();
            if(isset($db_cache[$table])){
                $res = $db_cache[$table];
            }
        }
        if(!count($res)) {
            $sql = "SHOW FIELDS FROM `" . _DB_PREFIX . "$table`";
            $res = qa( $sql );
            if(!is_array($db_cache)){
                $db_cache = array();
            }
            $db_cache[$table] = $res;
            module_cache::put('db','db_fields_'.$table,$db_cache,172800);
        }
        $fields = array();
        foreach($res as $r){
            $format = "";
            $type = 'text';
            if(count($ignore) && in_array($r['Field'],$ignore))continue;
            if(count($hidden) && in_array($r['Field'],$hidden)){
                $type = "hidden";
            // new field for file.
            }else if(preg_match("/^file_/",$r['Field']) && preg_match("/varchar\((\d+)\)/",$r['Type'],$matches)){
                $type = "file";
                $size = 50; $maxlength = 255;
            }else if(preg_match("/varchar\((\d+)\)/",$r['Type'],$matches)){
                $type = "text";
                $size = max("10",min("30",$matches[1]));
                $maxlength = $matches[1];
            }else if(preg_match("/int/i",$r['Type']) || preg_match("/float/i",$r['Type'])){
                $format = array("/^\d+$/","Integer");
                $type = "number";
                $maxlength = $size = 20;
            }else if($r['Type'] == "text"){
                $type = "textarea";
                $size = 0;
            }else if($r['Type'] == "date" || $r['Type'] == "datetime"){
                $format = array("/^\d\d\d\d-\d\d-\d\d$/","YYYY-MM-DD");
                $type = "date";
                $maxlength = $size = 20;
            }else if(preg_match("/decimal/",$r['Type']) || preg_match("/double/",$r['Type'])){
                $format = array("/^\d+\.?[\d+]?$/","Decimal");
                $type = "decimal";
                $maxlength = $size = 20;
            }
            $required = false;
            if($r['Null']=="NO")$required = true;
            $fields[$r['Field']] = array("name"=>$r['Field'],"type"=>$type,"dbtype"=>$r['Type'],"size" =>$size ,"maxlength"=>$maxlength,"required"=>$required,"format"=>$format);
        }
        self::$fieldscache[$table] = $fields;
        return $fields;
    }

    public static function update_insert($pkey,$pid,$table,$data=false,$do_replace=false){

        if($data===false){
            $data = $_REQUEST;
        }
        $fields = self::get_fields($table,array("date_created","date_updated")); //
        if(isset($fields['system_id']) && defined('_SYSTEM_ID')){
            $data['system_id'] = _SYSTEM_ID;
        }
        if(isset($fields['date_created'])){
            unset($fields['date_created']);
        }

        $now_string = db_escape(date('Y-m-d H:i:s'));
        if($do_replace || !is_numeric($pid) || !$pid){
            $pid = 'new';
            if($do_replace){
                $sql = "REPLACE INTO ";
            }else{
                $sql = "INSERT INTO ";
            }
            $sql .= "`"._DB_PREFIX."$table` SET date_created = '$now_string', ";
            if(isset($fields['create_user_id']) && isset($_SESSION['_user_id']) && $_SESSION['_user_id']){
                $sql .= "`create_user_id` = '".(int)$_SESSION['_user_id']."', ";
                unset($fields['create_user_id']);
            }
            if(isset($fields['create_ip_address'])){
                $sql .= "`create_ip_address` = '".db_escape($_SERVER['REMOTE_ADDR'])."', ";
                unset($fields['create_ip_address']);
            }
            // check there's a valid site id
            if(isset($fields['site_id']) && (!isset($data['site_id']) || !$data['site_id']) && isset($_SESSION['_site_id'])){
                $data['site_id'] = $_SESSION['_site_id'];
            }
            $where = "";
            //module_security::sanatise_data($table,$data);
            // todo - sanatise data here before we go through teh loop.
            // if sanatisation fails or data access fails then we stop the update/insert.
            if(!$data){
                // dont do this becuase $email->new_email() fails.
               // return false;
            }
        }else{
            // TODO - security hook here, check if we can access this data.
            /*$security_dummy=array();
            if(!module_security::can_access_data($table,$security_dummy,$pid)){
                echo 'Security warning - unable to save data';
                exit;
                return false;
            }*/
            $updated = false;
            if(isset($data['date_updated'])){
                $updated = "'".db_escape(input_date($data['date_updated'],true))."'";
            }
            if(!$updated){
                $updated = "'$now_string'";
            }
            $sql = "UPDATE `"._DB_PREFIX."$table` SET date_updated = $updated,";
            if(isset($fields['update_user_id']) && isset($_SESSION['_user_id']) && $_SESSION['_user_id']){
                $sql .= "`update_user_id` = '".(int)$_SESSION['_user_id']."', ";
                unset($fields['update_user_id']);
            }
            if(isset($fields['update_ip_address'])){
                $sql .= "`update_ip_address` = '".db_escape($_SERVER['REMOTE_ADDR'])."', ";
                unset($fields['update_ip_address']);
            }
            $where = " WHERE `$pkey` = '".db_escape($pid)."'";
            if(isset($fields['system_id']) && defined('_SYSTEM_ID')){
                $where .= " AND system_id = '"._SYSTEM_ID."'";
            }
        }

        //print_r($fields);exit;
        //print_r($data);exit;

        if(!$do_replace && isset($data[$pkey])){
            unset($data[$pkey]);
        }

        foreach($fields as $field){
            if(!isset($data[$field['name']]) || $data[$field['name']] === false){
                continue;
            }

            // special format for date fields.
            if($field['type']=='date'){
                $data[$field['name']] = input_date($data[$field['name']]);
            }
            // special format for int / double fields.
            if(($field['type']=='decimal'||$field['type']=='double') && function_exists('number_in')){
                // how many decimals are we rounding this number to?
                if(preg_match('#\(\d+,(\d+)\)#',$field['dbtype'],$matches)){
                    $data[$field['name']] = number_in($data[$field['name']],$matches[1]);
                }else{
                    $data[$field['name']] = number_in($data[$field['name']]);
                }
            }

            if(is_array($data[$field['name']]))
                $val = serialize($data[$field['name']]);
            else
                $val = $data[$field['name']];
            $sql .= " `".$field['name']."` = '".db_escape($val)."', ";
        }
        $sql = rtrim($sql,', ');
        $sql .= $where;
        query($sql);
        if($pid == "new"){
            $pid = db_insert_id();
        }
        return $pid;
    }


	public static $dbcnx = false;
	public static function db_connect(){
		if(!self::$dbcnx){
			if(function_exists('mysql_connect')){
				if(isset($_POST['install_upgrade'])) {
					// we still use oldschool connect during the upgrade process so things don't break .
					@mysql_connect( _DB_SERVER, _DB_USER, _DB_PASS ) or die( mysql_error() );
				}
			}
			if(function_exists('mysqli_connect')){
				// todo; parse out port number or :/tmp/socket listing from _DB_SERVER and add to correct params
				$server = _DB_SERVER;
				$port_number = null;
				$socket = null;
				if(preg_match('#:(\d+)$#',$server,$matches)){
					$server = str_replace(':'.$matches[1], '', $server);
					$port_number = $matches[1];
				}
				if(preg_match('#:([/\w\.]+)$#',$server,$matches)){
					$server = str_replace(':'.$matches[1], '', $server);
					$socket = $matches[1];
				}
				self::$dbcnx = mysqli_connect($server,_DB_USER,_DB_PASS, _DB_NAME, $port_number, $socket);
				if (mysqli_connect_errno()) {
					printf("Connect failed: %s\n", mysqli_connect_error());
					exit();
				}
				if(module_config::c('database_utf8',1)){
					self::set_charset();
				}
			}else{
				die('MySQLi not available. Please contact hosting provider and ask them to upgrade your version of PHP.');
			}
			query("SET @@SESSION.sql_mode = ''");
		}
		return self::$dbcnx;
	}

	public static function query($sql,$debug_message=''){

		//echo ''.$sql.'<br>';
		if(_DEBUG_MODE && defined('_DEBUG_SQL') && _DEBUG_SQL){
			static $past_queries = array();
			if(!isset($past_queries[$sql]))$past_queries[$sql]=0;
			else $past_queries[$sql]++;
			$sql_debug = $sql;
			if(strlen($sql_debug)>60){
				$sql_debug = htmlspecialchars(substr($sql_debug,0,60)).'<a href="#" onclick="$(this).hide(); $(\'span\',$(this).parent()).show(); return false;">....</a><span style="display:none">'.
				             htmlspecialchars(substr($sql,60)).'</span>';
			}else{
				$sql_debug = htmlspecialchars($sql);
			}
			if(class_exists('module_debug',false)){
				module_debug::log(array(
					'title' => 'SQL Query',
					'file' => 'includes/database.php',
					'data' => '('.($past_queries[$sql]>0 ? '<span style="color:#FF0000; font-weight:bold;">'.$past_queries[$sql].'</span>':$past_queries[$sql]).') '.$debug_message.$sql_debug,
					'important' => $past_queries[$sql]>0,
				));
			}
		}
		$res = false;
		if(self::$dbcnx && !$res = mysqli_query(self::$dbcnx, $sql)){
			set_error(_l('SQL Error: %s',self::last_error(). ' ' . $sql));
			set_error(_l('Try clicking the "Run Manual Upgrades" button to resolve SQL Errors.'));
			return false;
		}
		return $res;
	}

	public static function query_to_array($res){
		$array = array();
		if(!$res)return $array;
		while($row = mysqli_fetch_assoc($res)){
			if(isset($row['id']) && $row['id'])
				$array[$row['id']] = $row;
			else
				$array[] = $row;
		}
		return $array;
	}

	public static function set_charset(){
		mysqli_query( self::$dbcnx, "SET CHARACTER SET 'utf8'" );
		mysqli_query( self::$dbcnx, "SET NAMES 'utf8'" );
		mysqli_set_charset(self::$dbcnx, 'utf8');
		//trying this out too
		if ( function_exists( 'mb_internal_encoding' ) ) {
			mb_internal_encoding( 'UTF-8' );
		}
	}

	public static function escape($string){
		return function_exists('mysqli_real_escape_string') ? mysqli_real_escape_string(self::$dbcnx, $string) : mysql_real_escape_string($string);
	}
	public static function db_insert_id(){
		return mysqli_insert_id(self::$dbcnx);
	}

	public static function last_error(){
		return mysqli_error(self::$dbcnx);
	}

}

/* placeholder module to contain various functions used through out the system */
@include_once 'includes/database.php'; // so we don't re-create old functions.

require_once 'includes/plugin_db/class.database.php';


if(!function_exists('update_insert')){
    function update_insert($pkey,$pid,$table,$data=false,$do_replace=false){
        return module_db::update_insert($pkey,$pid,$table,$data,$do_replace);
    }
}

if(!function_exists('db_escape')){
    function db_escape($string){
        return module_db::escape($string);
    }
}

if(!function_exists('db_insert_id')){
    function db_insert_id(){
        return module_db::db_insert_id();
    }
}
