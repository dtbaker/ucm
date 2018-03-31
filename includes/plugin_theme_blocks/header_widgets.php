<?php

// we display widgets at the top of every page.

// give the user the option to hide widgets if they don't want them
// give the user the option to configure how many rows of widgets, and the number of columns on each row
// save the position of each widget with ajax/jqueryui drag similar to dashboard layout


// This code is also used on the home page to display those widgets.

// We call a widgets filter var to find out if there are any available widgets for the current page.


class theme_widget_manager {

	public $page_unique_id = false;
	public $widget_structures = array();
	public $widgets = array();

	public function __construct( $page_unique_id ) {
		$this->page_unique_id    = $page_unique_id;
		$this->widget_structures = array(
			'pages-home' => array(
				array( 1 => array(), ),
				array( 1 => array(), 2 => array(), 3 => array(), ),
				array( 1 => array(), 2 => array(), ),
			),
			'default'    => array(
				array( 1 => array(), ),
				array( 1 => array(), 2 => array(), 3 => array(), 4 => array(), ),
				array( 1 => array(), 2 => array(), 3 => array(), ),
				array( 1 => array(), ),
			),
		);
		if ( function_exists( 'hook_filter_var' ) ) {
			$this->widget_structures = hook_filter_var( 'widget_structures', $this->widget_structures );
		}
		$this->handle_save();
		$this->load_scripts();
		$this->load_widgets();
	}

	public function get_structure() {
		return isset( $this->widget_structures[ $this->page_unique_id ] ) ? $this->widget_structures[ $this->page_unique_id ] : $this->widget_structures['default'];
	}

	public function handle_save() {
		// ajax saving of order.
		if ( isset( $_REQUEST['sort_order'] ) && is_array( $_REQUEST['sort_order'] ) && isset( $_REQUEST['auth'] ) && module_form::get_secure_key() == $_REQUEST['auth'] && module_security::is_logged_in() ) {
			module_config::save_config( 'ws_' . $this->page_unique_id . '-' . module_security::get_loggedin_id(), json_encode( $_REQUEST['sort_order'] ) );
			exit;
		}
	}

	public function load_scripts() {

		module_config::register_css( 'theme', 'morris.css', full_link( '/includes/plugin_theme_blocks/css/morris.css' ), 12 );
		module_config::register_js( 'theme', 'raphael-min.js', full_link( '/includes/plugin_theme_blocks/js/raphael-min.js' ), 12 );
		module_config::register_js( 'theme', 'morris.min.js', full_link( '/includes/plugin_theme_blocks/js/morris.min.js' ), 13 );
		module_config::register_js( 'theme', 'dashboard.js', full_link( '/includes/plugin_theme_blocks/js/dashboard.js' ), 14 );

	}

	public function load_widgets() {

		$this->widgets = array();

		if ( $this->page_unique_id == 'pages-home' ) {
			// for the home page we need to use the hold 'dashboard_widgets' hook:
			$calling_module = 'home';
			$home_widgets   = handle_hook( 'dashboard_widgets', $calling_module );
			$home_widgets2  = hook_handle_callback( 'dashboard_widgets' );
			if ( is_array( $home_widgets2 ) ) {
				$home_widgets = array_merge( $home_widgets, $home_widgets2 );
			}
			$this->widgets = $home_widgets;

		}
		$this->widgets = hook_filter_var( 'page_widgets', $this->widgets, $this->page_unique_id );

	}


	public function display() {

		$widget_sort_json = @json_decode( module_config::c( 'ws_' . $this->page_unique_id . '-' . module_security::get_loggedin_id() ), true );
		if ( ! is_array( $widget_sort_json ) ) {
			$widget_sort_json = array();
		}
		$widget_sort_order      = array();
		$widget_sort_page_order = 1;
		foreach ( $widget_sort_json as $id => $vals ) {
			$bits = explode( '|', $vals );
			if ( count( $bits ) == 3 ) {
				// $bits[2] is the sort_id
				$widget_sort_order[ $bits[2] ] = array(
					'row_id'        => $bits[0],
					'column_number' => $bits[1],
					'page_order'    => $widget_sort_page_order ++,
				);
			}
		}


		// now we have to apply a unique sort id to those available widgets:

		$widget_sort_id = 1;

		if ( $this->page_unique_id == 'pages-home' ) {

			// then display the welcome message:
			module_template::init_template( 'welcome_message', '<p>
   Hi {USER_NAME}, and Welcome to {SYSTEM_NAME}
</p>', 'Welcome message on Dashboard', array(
				'USER_NAME'   => 'Current user name',
				'SYSTEM_NAME' => 'System name from settings area',
			) );
			// check if there is a template for this user role.
			$my_account    = module_user::get_user( module_security::get_loggedin_id() );
			$security_role = current( $my_account['roles'] );
			$template      = false;
			if ( $security_role && isset( $security_role['security_role_id'] ) ) {
				$template = module_template::get_template_by_key( 'welcome_message_role_' . $security_role['security_role_id'] );
			}
			if ( ! $template || ! $template->template_key ) {
				$template = module_template::get_template_by_key( 'welcome_message' );
			}
			$template->assign_values( array(
				'user_name'   => htmlspecialchars( $_SESSION['_user_name'] ),
				'system_name' => htmlspecialchars( module_config::s( 'admin_system_name' ) ),
			) );

			// then display the alerts list.
			if ( class_exists( 'module_dashboard', false ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Alerts' ) ) {
				ob_start();
				module_dashboard::output_dashboard_alerts( module_config::c( 'dashboard_alerts_ajax', 1 ) );
				array_unshift( $this->widgets, array(
					'row_id'        => isset( $widget_sort_order[ $widget_sort_id ]['row_id'] ) ? $widget_sort_order[ $widget_sort_id ]['row_id'] : false,
					'columns'       => isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ? $widget_sort_order[ $widget_sort_id ]['column'] : 1,
					'column_number' => isset( $widget_sort_order[ $widget_sort_id ]['column_number'] ) ? $widget_sort_order[ $widget_sort_id ]['column_number'] : false,
					'page_order'    => isset( $widget_sort_order[ $widget_sort_id ]['page_order'] ) ? $widget_sort_order[ $widget_sort_id ]['page_order'] : false,
					'sort_id'       => $widget_sort_id ++,
					'title'         => _l( 'Alerts' ),
					'content'       => ob_get_clean(),
				) );
			}
			array_unshift( $this->widgets, array(
				'row_id'        => isset( $widget_sort_order[ $widget_sort_id ]['row_id'] ) ? $widget_sort_order[ $widget_sort_id ]['row_id'] : false,
				'columns'       => isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ? $widget_sort_order[ $widget_sort_id ]['column'] : 1,
				'column_number' => isset( $widget_sort_order[ $widget_sort_id ]['column_number'] ) ? $widget_sort_order[ $widget_sort_id ]['column_number'] : false,
				'page_order'    => isset( $widget_sort_order[ $widget_sort_id ]['page_order'] ) ? $widget_sort_order[ $widget_sort_id ]['page_order'] : false,
				'sort_id'       => $widget_sort_id ++,
				'title'         => _l( 'Home Page' ),
				'content'       => _DEMO_MODE ? strip_tags( $template->replace_content() ) : $template->replace_content(),
			) );
		}


		$widget_structure = $this->get_structure();
		// now we apply any saved sorting options to override our current widgets.
		$widget_columns_counter = array();
		foreach ( $this->widgets as $widget_id => $module_widgets ) {
			if ( isset( $module_widgets['id'] ) || isset( $module_widgets['sort_id'] ) ) {
				$module_widgets = array( $module_widgets );
			}
			foreach ( $module_widgets as $module_widget ) {

				if ( isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ) {
					$module_widget['columns'] = $widget_sort_order[ $widget_sort_id ]['column'];
				}
				if ( isset( $widget_sort_order[ $widget_sort_id ]['row_id'] ) ) {
					$module_widget['row_id'] = $widget_sort_order[ $widget_sort_id ]['row_id'];
				}
				if ( isset( $widget_sort_order[ $widget_sort_id ]['column_number'] ) ) {
					$module_widget['column_number'] = $widget_sort_order[ $widget_sort_id ]['column_number'];
				} else {
					$module_widget['column_number'] = false;
				}
				if ( isset( $widget_sort_order[ $widget_sort_id ]['page_order'] ) ) {
					$module_widget['page_order'] = $widget_sort_order[ $widget_sort_id ]['page_order'];
				} else {
					$module_widget['page_order'] = false;
				}
				$module_widget['sort_id'] = $widget_sort_id ++;
				if ( ! isset( $module_widget['columns'] ) || ! $module_widget['columns'] ) {
					$module_widget['columns'] = 3;
				}

				if ( ! isset( $widget_columns_counter[ $module_widget['columns'] ] ) ) {
					$widget_columns_counter[ $module_widget['columns'] ] = 1;
				}
				$col_num = $module_widget['column_number'] ?: $widget_columns_counter[ $module_widget['columns'] ];
				// now we find a row/column to put this widget in.
				$found_id = 0;
				if ( isset( $module_widget['row_id'] ) && isset( $widget_structure[ $module_widget['row_id'] ] ) ) {
					$found_id = $module_widget['row_id'];
				} else {
					foreach ( $widget_structure as $widget_row_id => $widget_column ) {
						if ( count( $widget_column ) == $module_widget['columns'] ) {
							// we found one that can house this widget.
							$found_id = $widget_row_id;
							break;
						}
					}
				}
				$widget_structure[ $found_id ][ $col_num ][] = $module_widget;
				$widget_columns_counter[ $module_widget['columns'] ] ++;
				if ( $widget_columns_counter[ $module_widget['columns'] ] > $module_widget['columns'] ) {
					$widget_columns_counter[ $module_widget['columns'] ] = 1;
				}
			}
		}


		// now display our widgets in the columns they want
		foreach ( $widget_structure as $row_id => $widgets ) {
			$column_count = count( $widgets );
			?>
			<div class="row columns-<?php echo (int) $column_count; ?> clearfix dashboard-widget">
				<?php foreach ( $widgets as $column_number => $column_widgets ) { ?>
					<section data-row-id="<?php echo (int) $row_id; ?>" data-count="<?php echo count( $column_widgets ); ?>"
					         data-cols="<?php echo (int) $column_count; ?>" data-col-number="<?php echo (int) $column_number; ?>"
					         class="<?php switch ( $column_count ) {
						         case 4:
							         echo 'col-lg-3 col-xs-12';
							         break;
						         case 3:
							         echo 'col-lg-4 col-xs-12';
							         break;
						         case 2:
							         echo 'col-lg-6 col-xs-12';
							         break;
						         case 1:
						         default:
							         echo 'col-lg-12';
							         break;
					         }
					         ?> connectedSortable">
						<?php

						uasort( $column_widgets, function ( $a, $b ) {
							return $a['page_order'] > $b['page_order'];
						} );
						foreach ( $column_widgets as $column_widget ) {
							if ( isset( $column_widget['raw'] ) && $column_widget['raw'] ) {
								$widget_html = $column_widget['content'];
							} else {
								// wrap it in a widget block:
								$fieldset_data = array(
									'id'              => ! empty( $column_widget['id'] ) ? $column_widget['id'] : 'widg_' . ( $column_widget['sort_id'] ),
									'heading'         => $column_widget['title'] ? array(
										'type'   => 'h3',
										'title'  => $column_widget['title'],
										'button' => isset( $column_widget['button'] ) ? $column_widget['button'] : false,
									) : false,
									'class'           => 'tableclass tableclass_form tableclass_full',
									'elements_before' => $column_widget['content'],
								);
								$widget_html   = module_form::generate_fieldset( $fieldset_data );
								unset( $fieldset_data );
							}
							$widget_html = preg_replace( '#^\s+<\w+\s#imsU', '$0 data-sort-id="' . $column_widget['sort_id'] . '"', $widget_html, 1 );
							echo '<!-- widget -->' . $widget_html;
						} ?>
					</section>
				<?php } ?>
			</div>
			<?php
		}

	}
}

