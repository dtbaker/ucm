<?php


class module_pin extends module_base {

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
		$this->module_name     = "pin";
		$this->module_position = 0;

		module_config::register_css( 'pin', 'pin.css' );
		module_config::register_js( 'pin', 'pin.js' );

		$this->version = 2.12;
		//2.12 - 2016-02-02 - pin theme overriding support
		//2.11 css tweak

		if ( isset( $_REQUEST['pin_process'] ) && module_security::is_logged_in() && module_pin::can_i( 'edit', 'Header Pin' ) ) {
			switch ( $_REQUEST['pin_process'] ) {
				case 'pin_save':
					switch ( $_REQUEST['pin_action'] ) {
						case 'modify':
							if ( $_REQUEST['pin_id'] && $_REQUEST['current_title'] ) {
								$this->update_pin( $_REQUEST['pin_id'], false, $_REQUEST['current_title'] );
								set_message( 'Pin modified successfully' );
								redirect_browser( $_REQUEST['current_url'] );
							}
							break;
						case 'delete':
							if ( $_REQUEST['pin_id'] ) {
								$this->delete_pin( $_REQUEST['pin_id'] );
								set_message( 'Pin deleted successfully' );
								redirect_browser( $_REQUEST['current_url'] );
							}
							break;
						case 'add':
							if ( $_REQUEST['current_url'] && $_REQUEST['current_title'] ) {
								$pin_id = $this->add_pin( $_REQUEST['current_url'], $_REQUEST['current_title'] );
								if ( $pin_id ) {
									set_message( 'Pin added successfully' );
								} else {
									set_message( 'Pin already exists' );
								}
								redirect_browser( $_REQUEST['current_url'] );
							}
							break;
					}
					break;
			}
		}
	}


	public function handle_hook( $hook_name ) {
		if ( $hook_name == 'top_menu_end' && module_pin::can_i( 'view', 'Header Pin' ) && module_config::c( 'pin_show_in_menu', 1 ) && module_security::is_logged_in() ) {

			include module_theme::include_ucm( 'includes/plugin_pin/inc/pin_menu.php' );
		}
	}

	public function get_pins() {
		$pins = array();
		// use the 'extra' module for now.
		$extra_fields = module_extra::get_extras( array(
			'owner_table' => 'pin',
			'owner_id'    => module_security::get_loggedin_id()
		) );
		foreach ( $extra_fields as $extra ) {
			$pins[ $extra['extra_id'] ] = unserialize( $extra['extra'] );
		}

		return $pins;
	}

	public function add_pin( $href, $title ) {
		$pins = $this->get_pins();
		foreach ( $pins as $pin ) {
			if ( $pin[0] == $href ) {
				return false;
			}
		}
		$extra_db = array(
			'extra'       => serialize( array( $href, $title ) ),
			'extra_key'   => 'pin',
			'owner_table' => 'pin',
			'owner_id'    => module_security::get_loggedin_id(),
		);
		$extra_id = update_insert( 'extra_id', 0, 'extra', $extra_db );

		return $extra_id;
	}

	public function update_pin( $pin_id, $href, $title ) {
		$pins   = $this->get_pins();
		$pin    = $pins[ $pin_id ];
		$pin[1] = $title;
		if ( $href ) {
			$pin[0] = $href;
		}
		$extra_db = array(
			'extra'       => serialize( $pin ),
			'extra_key'   => 'pin',
			'owner_table' => 'pin',
			'owner_id'    => module_security::get_loggedin_id(),
		);
		$extra_id = update_insert( 'extra_id', $pin_id, 'extra', $extra_db );

		return $extra_id;
	}

	public function delete_pin( $pin_id ) {
		delete_from_db( 'extra', array( 'owner_table', 'extra_id', 'owner_id' ), array(
			'pin',
			$pin_id,
			module_security::get_loggedin_id()
		) );
	}
}