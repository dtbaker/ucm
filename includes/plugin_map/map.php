<?php


class module_map extends module_base {

	public $links;
	public $map_types;

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
		$this->map_types       = array();
		$this->module_name     = "map";
		$this->module_position = 14;
		$this->version         = 2.21;
		//2.21 - 2015-09-10 - map marker fix
		//2.2 - 2015-09-09 - map marker fix
		//2.1 - 2015-06-10 - initial release

		// the link within Admin > Settings > Maps.
		if ( module_security::has_feature_access( array(
			'name'        => 'Settings',
			'module'      => 'config',
			'category'    => 'Config',
			'view'        => 1,
			'description' => 'view',
		) ) ) {
			$this->links[] = array(
				"name"                => "Maps",
				"p"                   => "map_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}


		if ( $this->can_i( 'view', 'Maps' ) && module_config::c( 'enable_customer_maps', 1 ) && module_map::is_plugin_enabled() ) {

			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many maps?
				$name          = 'Maps';
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "map_admin",
					'args'                => array( 'map_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'globe',
				);
			}
			$this->links[] = array(
				"name"      => 'Maps',
				"p"         => "map_admin",
				'args'      => array( 'map_id' => false ),
				'icon_name' => 'globe',
			);

		}

	}

	public function process() {
		if ( isset( $_REQUEST['_process'] ) && $_REQUEST['_process'] == 'ajax_save_map_coords' ) {

			$address_id = (int) $_REQUEST['address_id'];
			if ( $address_id && ! empty( $_REQUEST['address_hash'] ) && ! empty( $_REQUEST['lat'] ) && ! empty( $_REQUEST['lng'] ) ) {
				// existing?
				$existing = get_single( 'map', 'address_id', $address_id );
				update_insert( 'map_id', $existing ? $existing['map_id'] : false, 'map', array(
					'address_hash' => $_REQUEST['address_hash'],
					'address_id'   => $_REQUEST['address_id'],
					'lat'          => $_REQUEST['lat'],
					'lng'          => $_REQUEST['lng'],
				) );
			}
			echo 'Done';
			exit;
		}
	}

	public function get_install_sql() {
		ob_start();
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>map` (
		`map_id` int(11) NOT NULL auto_increment,
		`address_hash` varchar(255) NOT NULL DEFAULT '',
		`address_id` int(11) NOT NULL DEFAULT  '0',
		`lat` varchar(255) NOT NULL DEFAULT  '',
		`lng` varchar(255) NOT NULL DEFAULT  '',
		`date_created` date NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`map_id`),
		KEY (`address_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php

		return ob_get_clean();
	}

}