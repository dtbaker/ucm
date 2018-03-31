<?php


define( '_SOCIAL_MESSAGE_STATUS_UNANSWERED', 0 );
define( '_SOCIAL_MESSAGE_STATUS_ANSWERED', 1 );
define( '_SOCIAL_MESSAGE_STATUS_PENDINGSEND', 3 );
define( '_SOCIAL_MESSAGE_STATUS_SENDING', 4 );


class module_social extends module_base {

	var $links;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->links           = array();
		$this->module_name     = "social";
		$this->module_position = 25.1;

		$this->version = 2.132;
		// 2.132 - 2015-12-28 - menu speed up
		// 2.131 - 2014-08-04 - responsive improvements
		// 2.13 - 2014-05-23 - social fixes
		// 2.12 - 2014-04-05 - ability to disable social plugin
		// 2.11 - 2014-04-05 - better message archiving
		// 2.1 - 2014-03-25 - initial release
		if ( self::is_plugin_enabled() ) {
			module_config::register_css( 'social', 'social.css', true, 5 );
			module_config::register_js( 'social', 'social.js', true, 5 );
		}
	}

	public function pre_menu() {

		if ( self::is_plugin_enabled() ) {

			if ( module_security::has_feature_access( array(
				'name'        => 'Settings',
				'module'      => 'config',
				'category'    => 'Config',
				'view'        => 1,
				'description' => 'view',
			) ) ) {
				$this->links[] = array(
					"name"                => "Social",
					"p"                   => "social_settings",
					"args"                => array( 'social_id' => false ),
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}

			if ( $this->can_i( 'view', 'Social' ) && class_exists( 'ucm_twitter', false ) && class_exists( 'ucm_facebook', false ) ) {
				$link_name = _l( 'Social' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$twitter   = new ucm_twitter();
					$facebook  = new ucm_facebook();
					$unread    = $facebook->get_unread_count() + $twitter->get_unread_count();
					$link_name .= ( $unread > 0 ? " <span class='menu_label'>" . $unread . "</span>" : '' );
				}
				$this->links['social'] = array(
					"name"      => $link_name,
					"p"         => "social_admin",
					'icon_name' => 'comment-o',
				);
			}
		}
	}

	public function process() {

		if ( "ajax_social" == $_REQUEST['_process'] && module_social::can_i( 'view', 'social' ) ) {
			// ajax functions from wdsocial. copied from the datafeed.php sample files.
			header( 'Content-type: text/javascript' );
			$ret        = array();
			$action     = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : false;
			$message_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : 0;
			switch ( $action ) {
				case "set-answered":
					break;
			}
			echo json_encode( $ret );
			exit;
		}
	}

	public static function link_generate( $social_id = false, $options = array(), $link_options = array() ) {
		// we accept link options from a bubbled link call.
		// so we have to prepent our options to the start of the link_options array incase
		// anything bubbled up to this method.
		// build our options into the $options variable and array_unshift this onto the link_options at the end.
		$key = 'social_id'; // the key we look for in data arrays, on in _REQUEST variables. for sub link building.

		// we check if we're bubbling from a sub link, and find the item id from a sub link
		if ( ${$key} === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}

		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'social_settings';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['social_id'] = $social_id;
		$options['module']                 = 'social';


		$options['text'] = isset( $options['text'] ) ? htmlspecialchars( $options['text'] ) : '';
		// generate the arguments for this link
		$options['arguments'] = array(
			'social_id' => $social_id,
		);
		// generate the path (module & page) for this link
		$options['module'] = 'social';

		// append this to our link options array, which is eventually passed to the
		// global link generate function which takes all these arguments and builds a link out of them.

		if ( ! module_social::can_i( 'view', 'Social' ) ) {
			if ( ! isset( $options['full'] ) || ! $options['full'] ) {
				return '#';
			} else {
				return isset( $options['text'] ) ? $options['text'] : _l( 'N/A' );
			}
		}

		// optionally bubble this link up to a parent link_generate() method, so we can nest modules easily
		// change this variable to the one we are going to bubble up to:
		$bubble_to_module = false;
		if ( isset( $options['config'] ) && $options['config'] ) {
			$bubble_to_module = array(
				'module'   => 'config',
				'argument' => 'social_id',
			);
		}
		array_unshift( $link_options, $options );
		if ( $bubble_to_module ) {
			global $plugins;

			return $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			return link_generate( $link_options );
		}
	}


	public static function link_open_compose() {
		return self::link_generate( false, array( 'full'      => false,
		                                          'arguments' => array(),
		                                          'page'      => 'social_message_compose'
		) );
	}

	public static function link_social_ajax_functions( $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for ajax social ' . _UCM_SECRET );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=social&h=ajax&hash=' . self::link_social_ajax_functions( true ) . '' );
	}

	public static function get_message_managers() {
		static $message_managers = false;
		if ( ! $message_managers && class_exists( 'ucm_twitter', false ) && class_exists( 'ucm_facebook', false ) ) {
			$message_managers = array(
				'facebook' => new ucm_facebook(),
				'twitter'  => new ucm_twitter(),
			);
		}

		return $message_managers;
	}

}
