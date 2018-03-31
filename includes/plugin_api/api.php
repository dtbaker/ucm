<?php


class module_api extends module_base {

	public $links;
	public $api_types;

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
		$this->api_types       = array();
		$this->module_name     = "api";
		$this->module_position = 26;
		$this->version         = 2.1;
		//2.1 - 2014-05-26 - initial release of new api system

		if ( module_api::is_plugin_enabled() ) {
			// hook for api icon in top bar

			if ( module_config::can_i( 'view', 'Settings' ) ) {
				$this->links[] = array(
					"name"                => "API",
					"p"                   => "api_settings",
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}

		}

	}

	public static function get_api_url() {
		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.api/h.v1' );
	}

	public static function get_api_key( $user_id = false ) {
		if ( ! $user_id ) {
			$user_id = module_security::get_loggedin_id();
		}

		return module_config::c( 'api_key', md5( _UCM_SECRET . time() ) ) . ':' . $user_id . ':' . md5( module_config::c( 'api_key' ) . ' api key for user ' . $user_id );
	}

	public static $api_endpoints = array();

	public function external_hook( $hook ) {
		switch ( $hook ) {
			case 'v1':
				$response            = array();
				$response['version'] = '1';
				$endpoint            = isset( $_REQUEST['endpoint'] ) ? $_REQUEST['endpoint'] : false;
				$method              = isset( $_REQUEST['method'] ) ? $_REQUEST['method'] : false;

				// check if the API key is valid
				$headers = apache_request_headers();
				//$headers['Authorization'] = module_api::get_api_key(1); // for testing
				$auth = false;
				if ( ! empty( $headers['Authorization'] ) ) {
					$auth = $headers['Authorization'];
				} else if ( ! empty( $_POST['auth'] ) ) {
					$auth = $_POST['auth'];
				}
				if ( $auth ) {
					$bits = explode( ':', $auth );
					if ( count( $bits ) == 3 ) {
						$user_id = (int) trim( $bits[1] );
						if ( $user_id ) {
							$correct_hash = module_api::get_api_key( $bits[1] );
							if ( $correct_hash == $auth ) {
								module_security::user_id_temp_set( $user_id );
								$response['user_id'] = $user_id;
								$response            = hook_filter_var( 'api_callback_' . $endpoint, $response, $endpoint, $method );
								module_security::user_id_temp_restore();
							}
						}
					}
				}

				if ( isset( $_REQUEST['pretty'] ) ) {
					echo '<pre>';
					print_r( $response );
					echo '</pre>';
				} else {
					echo json_encode( $response );
				}
				exit;
		}
	}
}