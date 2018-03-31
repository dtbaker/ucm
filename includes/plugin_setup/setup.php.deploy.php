<?php


class module_setup extends module_base {
	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public $version = 1;

	public function init() {
		$this->module_name     = "setup";
		$this->module_position = 20;


	}


}