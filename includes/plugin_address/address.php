<?php


class module_address extends module_base {

	public $links;
	public $address_types;


	public $version = 2.225;
	// 2.225 - 2016-07-10 - big update to mysqli
	// 2.224 - 2016-01-25 - remove address fields if empty title
	// 2.223 - 2015-07-06 - hook bug fix
	// 2.222 - 2015-05-03 - responsive improvements
	// 2.221 - 2015-02-12 - dev override support
	// 2.22 - 2014-08-10 - responsive fixes
	// 2.21 - php5/6 fix

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->links           = array();
		$this->address_types   = array();
		$this->module_name     = "address";
		$this->module_position = 101;

		$this->link_include_parents = true; // set defaullt

	}

	function handle_hook( $hook, &$calling_module = false, $address_type = false, $owner_table = false, $key_name = false, $key_value = false ) {

		switch ( $hook ) {
			case "address_block_save":
				if ( ! $address_type ) {
					$address_type = "main";
				}
				// find the key we are saving this address against.
				$owner_id = $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}
				$address_hash = md5( $owner_table . '|' . $address_type ); // just for posting unique arrays.
				// we have to use this 3 key to find an address because
				// we could be saving a new record with no existing address_id
				$address           = $this->get_address( $owner_id, $owner_table, $address_type );
				$address_id        = $address['address_id'];
				$address_post_data = isset( $_POST['address'][ $address_hash ] ) ? $_POST['address'][ $address_hash ] : array();
				if ( $address_post_data ) {
					$address_post_data['owner_id'] = $owner_id; // incase on new save.
					// we have post data to save, write it to the table!!
					$this->save_address( $address_id, $address_post_data );
				}
				break;
			case "address_block_delete":
				if ( ! $address_type ) {
					$address_type = "main";
				}
				// find the key we are saving this address against.
				$owner_id = $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}
				// we have to use this 3 key to find an address because
				// we could be saving a new record with no existing address_id
				$address    = $this->get_address( $owner_id, $owner_table, $address_type );
				$address_id = (int) $address['address_id'];
				if ( $address_id ) {
					$sql = "DELETE FROM `" . _DB_PREFIX . "address` WHERE address_id = '$address_id' LIMIT 1";
					query( $sql );
				}
				break;
			case "address_block":
				if ( ! $address_type ) {
					$address_type = "main";
				}
				// find the key we are saving this address against.
				$owner_id = $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}
				$address    = $this->get_address( $owner_id, $owner_table, $address_type );
				$address_id = $address['address_id'];
				//module_address::print_address_form($address_id,$address);
				//include("pages/address_block.php");
				include( module_theme::include_ucm( "includes/plugin_address/pages/address_block.php" ) );
				break;
			case "address_delete":
				if ( ! $address_type ) {
					$address_type = "main";
				}
				// find the key we are saving this address against.
				$owner_id = $key_value;
				if ( ! $owner_id || $owner_id == 'new' ) {
					// find one in the post data.
					if ( isset( $_REQUEST[ $key_name ] ) ) {
						$owner_id = $_REQUEST[ $key_name ];
					}
				}

				if ( $owner_table && $owner_id ) {
					$sql = "DELETE FROM `" . _DB_PREFIX . "address` WHERE owner_table = '" . db_escape( $owner_table ) . "'
					AND owner_id = '" . db_escape( $owner_id ) . "'";
					$res = query( $sql );
				}
				break;

		}
	}

	public function process() {
		$errors = array();
		if ( "save_from_popup" == $_REQUEST['_process'] ) {
			// dont use the normal hook to save, its gay way of saving.
			// look at post data.
			if ( isset( $_POST['address'] ) && is_array( $_POST['address'] ) ) {
				foreach ( $_POST['address'] as $address_hash => $address_data ) {
					if ( isset( $address_data['address_id'] ) && (int) $address_data['address_id'] ) {
						$this->save_address( $address_data['address_id'], $address_data );
					}
				}
			}
		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}

	public static function save_address( $address_id, $data ) {
		return update_insert( 'address_id', $address_id, 'address', $data );
	}

	public static function print_address_form( $owner_id, $owner_table, $address_type, $title = '' ) {
		$address        = self::get_address( $owner_id, $owner_table, $address_type );
		$address_id     = isset( $address['address_id'] ) ? $address['address_id'] : false;
		$display_pretty = true; // we use the new fieldset builder
		$address_hash   = md5( $owner_table . '|' . $address_type ); // just for posting unique arrays.
		include( module_theme::include_ucm( "includes/plugin_address/pages/address_block.php" ) );
	}

	public static function print_address( $owner_id, $owner_table, $address_type, $output = 'html', $restrict = array() ) {
		global $plugins;
		$address = $plugins['address']->get_address( $owner_id, $owner_table, $address_type );
		if ( ! $restrict ) {
			$restrict = array( 'line_1', 'suburb', 'state', 'post_code' );
		}
		$hash = md5( implode( ',', $restrict ) . $owner_id );
		switch ( $output ) {
			case 'html':
				$address_output = '';
				?>
				<span class="address">
					<?php
					foreach ( $restrict as $key ) {
						if ( isset( $address[ $key ] ) && $address[ $key ] ) {
							$address_output .= $address[ $key ] . ', ';
						}
					}
					$address_output = rtrim( $address_output, ', ' );
					if ( $address_output != '' ) {
						echo htmlspecialchars( $address_output );
						?>
						<a href="#" id="address_popup_<?php echo $hash; ?>_go">&raquo;</a>
						<div id="address_popup_<?php echo $hash; ?>" title="<?php _e( 'Edit Address' ); ?>">
							<div class="modal_inner"></div>
						</div>
						<?php
					}
					?>
				</span>

				<?php if ( $address_output != '' ) { ?>
				<script type="text/javascript">
            $(function () {
                $("#address_popup_<?php echo $hash;?>").dialog({
                    autoOpen: false,
                    width: 400,
                    height: 350,
                    modal: true,
                    buttons: {
                        '<?php _e( 'Save Address' );?>': function () {
                            $('form', this)[0].submit();
                        },
                        '<?php _e( 'Cancel' );?>': function () {
                            $(this).dialog('close');
                        }
                    },
                    open: function () {
                        var t = this;
                        $.ajax({
                            type: "GET",
                            url: '<?php echo $plugins['address']->link(
															'address_popup',
															array( 'address_id' => $address['address_id'] ),
															'address',
															false
														);?>',
                            dataType: "html",
                            success: function (d) {
                                $('.modal_inner', t).html(d);
                                $('.redirect', t).val(window.location.href);
                                load_calendars();
                            }
                        });
                    },
                    close: function () {
                        $('.modal_inner', this).html('');
                    }
                });
                $('#address_popup_<?php echo $hash;?>_go').click(function () {
                    $("#address_popup_<?php echo $hash;?>").dialog('open');
                    return false;
                });
            });
				</script>
				<?php
			}
				break;
		}
	}

	public static function get_address( $owner_id, $owner_table, $address_type, $only_address_fields = false ) {
		if ( ! $owner_id ) {
			return array();
		}
		$sql = "SELECT a.*, address_id AS id ";
		//$sql .= " ,s.`state`, r.`region`, c.`country` ";
		$sql .= " FROM `" . _DB_PREFIX . "address` a ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."address_state` s ON a.state_id = s.state_id ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."address_region` r ON a.region_id = r.region_id ";
		//        $sql .= " LEFT JOIN `"._DB_PREFIX."address_country` c ON a.country_id = c.country_id ";
		$sql .= "WHERE";
		$sql .= " a.`owner_id` = " . (int) $owner_id . "";
		$sql .= " AND a.`owner_table` = '" . db_escape( $owner_table ) . "'";
		$sql .= " AND a.`address_type` = '" . db_escape( $address_type ) . "'";
		$res = qa( $sql );
		if ( $res && is_array( $res ) ) {
			$res = array_shift( $res );
		} else {
			$res = false;
		}
		if ( $only_address_fields ) {
			$address = array();
			foreach ( array( 'line_1', 'line_2', 'suburb', 'state', 'region', 'country', 'post_code' ) as $key ) {
				$address[ $key ] = $res[ $key ];
			}

			return $address;
		}

		return $res;
		//		return array_shift(get_multiple("address",array('owner_id'=>$owner_id,'owner_table'=>$owner_table,'address_type'=>$address_type),"owner_id"));
	}

	public static function get_address_by_id( $address_id ) {

		return get_single( "address", 'address_id', $address_id );
	}

	public function get_upgrade_sql() {
		$sql    = '';
		$fields = get_fields( 'address' );
		if ( isset( $fields['post_code'] ) && $fields['post_code']['maxlength'] < 10 ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'address`  CHANGE  `post_code` `post_code` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'\';';
		}
		// check for indexes
		/*$sql_check = 'SHOW INDEX FROM `'._DB_PREFIX.'address';
		$res = qa($sql_check);
		//print_r($res);exit;
		$add_index=true;
		foreach($res as $r){
				if(isset($r['Column_name']) && $r['Column_name'] == 'owner_id' && $r['Key_name'] == 'owner_id_single'){
						$add_index=false;
				}
		}
		if($add_index){
				$sql .= 'ALTER TABLE  `'._DB_PREFIX.'address` ADD INDEX `owner_id_single` ( `owner_id` )';
		}
		$add_index=true;
		foreach($res as $r){
				if(isset($r['Column_name']) && $r['Column_name'] == 'owner_table' && $r['Key_name'] == 'owner_table'){
						$add_index=false;
				}
		}
		if($add_index){
				$sql .= 'ALTER TABLE  `'._DB_PREFIX.'address` ADD INDEX `owner_table` ( `owner_table` )';
		}*/

		return $sql;
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE IF NOT EXISTS `<?php echo _DB_PREFIX; ?>address` (
		`address_id` int(11) NOT NULL AUTO_INCREMENT,
		`owner_id` int(11) NOT NULL,
		`owner_table` varchar(30) NOT NULL,
		`address_type` varchar(255) NOT NULL,
		`line_1` varchar(50) NOT NULL DEFAULT '',
		`line_2` varchar(50) NOT NULL DEFAULT '',
		`suburb` varchar(40) NOT NULL DEFAULT '',
		`state` varchar(40) NOT NULL DEFAULT '',
		`region` varchar(40) NOT NULL DEFAULT '',
		`country` varchar(40) NOT NULL DEFAULT '',
		`post_code` varchar(10) NOT NULL DEFAULT '',
		`date_created` date NOT NULL,
		`date_updated` date DEFAULT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NOT NULL DEFAULT '0',
		`create_ip_address` varchar(15) NOT NULL,
		`update_ip_address` varchar(15) NOT NULL DEFAULT '',
		PRIMARY KEY (`address_id`),
		UNIQUE KEY `owner_id` (`owner_id`,`owner_table`,`address_type`),
		KEY `owner_id_single` (`owner_id`),
		KEY `owner_table` (`owner_table`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

		<?php
		return ob_get_clean();
	}

}