<?php


class module_theme_blocks extends module_base {

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
		$this->module_name     = "theme_blocks";
		$this->module_position = 0;

		$this->version = 2.129;
		//2.129 - 2017-01-12 - fieldset settings
		//2.128 - 2016-12-20 - ajax lookup forms
		//2.127 - 2015-11-06 - javascript theme improvement
		//2.126 - 2016-10-30 - modal improvements
		//2.125 - 2016-07-10 - big update to mysqli
		//2.124 - 2016-06-09 - search bar fix
		//2.123 - 2016-03-14 - iframe fix
		//2.122 - 2016-03-03 - new menu styling options
		//2.121 - 2016-02-11 - login page javascript fix
		//2.12 - 2016-02-02 - nested menu support
		//2.1 - 2016-01-23 - initial release

		hook_add( 'get_themes', 'module_theme_blocks::hook_get_themes' );
		if ( module_theme::get_current_theme() == 'theme_blocks' ) {
			hook_add( 'get_table_manager', 'module_theme_blocks::hook_get_table_manager' );
		}
	}

	public static function hook_get_themes() {
		return array(
			'id'        => 'theme_blocks',
			'name'      => _l( 'Blocks' ),
			'base_dir'  => 'includes/plugin_theme_blocks/',
			'init_file' => 'includes/plugin_theme_blocks/init.php', // this starts the magic
		);
	}

	public static function hook_get_table_manager() {
		require_once( module_theme::include_ucm( 'includes/plugin_theme_blocks/class.table_manager.php' ) );

		return new ucm_blocks_table_manager();
	}
}


