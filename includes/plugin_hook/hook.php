<?php

class module_hook extends module_base
{
	public static $hook = array();
	public static $show_hook = false;
	public static $start_time = 0;

	public function __construct() {
		$this->module_name     = 'hook';
		$this->module_position = 2;
		$this->version         = 2.101;
	}

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
		// 2.101 - 2026-10-17 - 4.0.0 relaunch: PHP 8.4 compatibility and code style
		// 2.1 - 2016-06-15 - initial release
	}


	public static function run_hook( $hook_module, $key, $data = array() ) {

		$data = array(
			'invoice' => array(
				array(
					'action'     => 'email_sent',
					'conditions' => array(
						'template_type' => 'due',
					),
				),
			),
		);


	}
}
