<?php


class module_theme_adminlte extends module_base {

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
		$this->module_name     = "theme_adminlte";
		$this->module_position = 0;

		$this->version = 2.159;
		//2.159 - 2022-12-27 - php8.1 fixes
		//2.158 - 2017-01-12 - fieldset settings
		//2.157 - 2017-01-05 - php warning fix
		//2.156 - 2016-12-20 - ajax lookup forms
		//2.155 - 2015-11-25 - title width config
		//2.154 - 2015-11-23 - ajax lookup fixes
		//2.153 - 2015-11-06 - javascript theme improvement
		//2.152 - 2016-10-30 - modal improvements
		//2.151 - 2016-10-20 - overdue dashboard widget fix
		//2.150 - 2016-07-10 - big update to mysqli
		//2.149 - 2016-02-11 - login page javascript fix
		//2.148 - 2016-02-02 - nested menu support
		//2.147 - 2016-02-02 - layout fix
		//2.146 - 2016-01-30 - header button update
		//2.145 - 2015-12-27 - fontawesome update
		//2.144 - 2015-10-27 - iframe popup fix
		//2.143 - 2015-10-12 - fix for error reporting on widgets
		//2.142 - 2015-09-25 - ability to create more customer types
		//2.141 - 2015-06-11 - fontawesome update
		//2.14 - 2015-06-07 - new extra field settings button
		//2.139 - 2015-05-13 - theme settings fix
		//2.138 - 2015-05-03 - responsive improvements
		//2.137 - 2015-04-27 - responsive improvements
		//2.136 - 2015-03-24 - pagination fix
		//2.135 - 2015-03-15 - table manager update
		//2.134 - 2015-03-14 - new help system
		//2.133 - 2015-03-08 - timer menu fix
		//2.132 - 2015-02-24 - menu fixed/normal setting added
		//2.131 - 2015-02-08 - job discussion improvement (thanks w3corner!)
		//2.13 - 2015-01-26 - dashboard widgets save position
		//2.129 - 2015-01-21 - leads dashboard link fix
		//2.128 - 2014-12-17 - signup form on login
		//2.127 - 2014-11-26 - improved form framework
		//2.126 - 2014-11-19 - content padding fix
		//2.125 - 2014-11-05 - welcome_message_role_X template support
		//2.124 - 2014-10-13 - date and encrypt field fixes
		//2.123 - 2014-09-09 - job task message saving fix
		//2.122 - 2014-08-20 - css fixes
		//2.121 - 2014-08-18 - fix for quick pin menu item
		//2.12 - 2014-08-18 - missing javascript file
		//2.11 - 2014-08-14 - dashboard widget permissions
		//2.1 - 2014-07-31 - initial release

		hook_add( 'get_themes', 'module_theme_adminlte::hook_get_themes' );
		if ( module_theme::get_current_theme() == 'theme_adminlte' ) {
			hook_add( 'get_table_manager', 'module_theme_adminlte::hook_get_table_manager' );
		}
	}

	public static function hook_get_themes() {
		return array(
			'id'        => 'theme_adminlte',
			'name'      => _l( 'AdminLTE' ),
			'base_dir'  => 'includes/plugin_theme_adminlte/',
			'init_file' => 'includes/plugin_theme_adminlte/init.php', // this starts the magic
		);
	}

	public static function hook_get_table_manager() {
		require_once( module_theme::include_ucm( 'includes/plugin_theme_adminlte/class.table_manager.php' ) );

		return new ucm_adminlte_table_manager();
	}
}


