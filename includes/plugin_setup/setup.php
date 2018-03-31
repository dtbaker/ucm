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

	public function init() {
		$this->module_name     = "setup";
		$this->module_position = 20;

		$this->version = 2.324;
		// 2.3 - 2014-07-07 - initial setup improvements
		// 2.31 - 2014-07-25 - initial setup improvements
		// 2.32 - 2014-09-18 - sql mode fix
		// 2.321 - 2015-03-18 - db prefix fix
		// 2.322 - 2016-07-10 - big update to mysqli
		// 2.323 - 2016-08-12 - mysqli fixes
		// 2.324 - 2017-05-02 - file path configuration

	}


}