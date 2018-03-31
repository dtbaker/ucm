<?php


class module_help extends module_base {

	public $links;
	public $help_types;

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
		$this->help_types      = array();
		$this->module_name     = "help";
		$this->module_position = 16;
		$this->version         = 2.13;
		//2.13 - 2016-20-30 - modal improvements
		//2.12 - 2016-01-04 - help js
		//2.11 - 2014-04-05 - url help js
		//2.1 - 2014-03-14 - initial release of new help system

		if ( module_help::is_plugin_enabled() &&
		     (
			     ( module_config::c( 'help_only_for_admin', 1 ) && module_security::get_loggedin_id() == 1 ) ||
			     ( ! module_config::c( 'help_only_for_admin', 1 ) && module_help::can_i( 'view', 'Help' ) )
		     )
		) {
			// hook for help icon in top bar
			hook_add( 'header_buttons', 'module_help::hook_filter_var_header_buttons' );
			hook_add( 'header_print_js', 'module_help::header_print_js' );
			module_config::register_js( 'help', 'help.js' );
			module_config::register_css( 'help', 'help.css' );

			if ( module_config::can_i( 'view', 'Settings' ) ) {
				$this->links[] = array(
					"name"                => "Help",
					"p"                   => "help_settings",
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}

		}

	}


	public static function header_print_js() {
		$pages   = isset( $_REQUEST['p'] ) ? ( is_array( $_REQUEST['p'] ) ? $_REQUEST['p'] : array( $_REQUEST['p'] ) ) : array();
		$modules = isset( $_REQUEST['m'] ) ? ( is_array( $_REQUEST['m'] ) ? $_REQUEST['m'] : array( $_REQUEST['m'] ) ) : array();
		foreach ( $pages as $pid => $p ) {
			$pages[ $pid ] = preg_replace( '#[^a-z_]#', '', $p );
		}
		foreach ( $modules as $pid => $p ) {
			$modules[ $pid ] = preg_replace( '#[^a-z_]#', '', $p );
		}
		?>
		<script type="text/javascript">
        ucm.help.current_modules = '<?php echo implode( '/', $modules );?>';
        ucm.help.current_pages = '<?php echo implode( '/', $pages );?>';
        ucm.help.lang.help = '<?php _e( 'Help' ); ?>';
        ucm.help.url_extras = '&codes=<?php echo base64_encode( module_config::c( '_installation_code' ) );?>&host=<?php echo urlencode( htmlspecialchars( full_link( '/' ) ) );?>';
		</script>
		<?php
	}

	public static function hook_filter_var_header_buttons( $callback, $header_buttons ) {
		$header_buttons['help'] = array(
			'fa-icon' => 'question',
			'title'   => 'Help',
			'id'      => 'header_help',
		);

		return $header_buttons;
	}


}