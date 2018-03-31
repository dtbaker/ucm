<?php

if ( isset( $_REQUEST['sort_order'] ) && is_array( $_REQUEST['sort_order'] ) && isset( $_REQUEST['auth'] ) && module_form::get_secure_key() == $_REQUEST['auth'] && module_security::is_logged_in() ) {
	module_config::save_config( 'dash_widgets_sort_' . module_security::get_loggedin_id(), json_encode( $_REQUEST['sort_order'] ) );
}

module_config::register_css( 'theme', 'morris.css', full_link( '/includes/plugin_theme_adminlte/css/morris.css' ), 12 );
module_config::register_css( 'theme', 'ionicons.min.css', full_link( '/includes/plugin_theme_adminlte/css/ionicons.min.css' ), 12 );
module_config::register_js( 'theme', 'raphael-min.js', full_link( '/includes/plugin_theme_adminlte/js/AdminLTE/raphael-min.js' ), 12 );
module_config::register_js( 'theme', 'morris.min.js', full_link( '/includes/plugin_theme_adminlte/js/AdminLTE/morris.min.js' ), 13 );
module_config::register_js( 'theme', 'dashboard.js', full_link( '/includes/plugin_theme_adminlte/js/AdminLTE/dashboard.js' ), 14 );

$calling_module = 'home';
$home_widgets   = handle_hook( 'dashboard_widgets', $calling_module );
$home_widgets2  = hook_handle_callback( 'dashboard_widgets' );
if ( is_array( $home_widgets2 ) ) {
	$home_widgets = array_merge( $home_widgets, $home_widgets2 );
}

// group the widgets into columsn.
// the default columns is 3, but each widget can specify which column group they want to appear in.
// layout the default widget structure in the order we want it to display on the page:
$widget_columns    = array();
$widget_columns[4] = array( 1 => array(), 2 => array(), 3 => array(), 4 => array() );
$widget_columns[1] = array( 1 => array() );
$widget_columns[3] = array( 1 => array(), 2 => array(), 3 => array() );
$widget_columns[2] = array( 1 => array(), 2 => array() );

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
$widget_sort_json = @json_decode( module_config::c( 'dash_widgets_sort_' . module_security::get_loggedin_id() ), true );
if ( ! is_array( $widget_sort_json ) ) {
	$widget_sort_json = array();
}
$widget_sort_order      = array();
$widget_sort_page_order = 1;
foreach ( $widget_sort_json as $id => $vals ) {
	$bits = explode( '|', $vals );
	if ( count( $bits ) == 3 || count( $bits ) == 4 ) {
		$widget_sort_order[ $bits[2] ] = array(
			'column'        => $bits[0],
			'column_number' => $bits[1],
			'page_order'    => $widget_sort_page_order ++,
			'deleted'       => ! empty( $bits[3] ),
		);
	}
}
$widget_sort_id = 1;
// then display the alerts list.
if ( module_config::c( 'dashboard_new_layout', 1 ) && class_exists( 'module_dashboard', false ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Alerts' ) ) {
	ob_start();
	module_dashboard::output_dashboard_alerts( module_config::c( 'dashboard_alerts_ajax', 1 ) );
	array_unshift( $home_widgets, array(
		'columns'       => isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ? $widget_sort_order[ $widget_sort_id ]['column'] : 1,
		'column_number' => isset( $widget_sort_order[ $widget_sort_id ]['column_number'] ) ? $widget_sort_order[ $widget_sort_id ]['column_number'] : false,
		'page_order'    => isset( $widget_sort_order[ $widget_sort_id ]['page_order'] ) ? $widget_sort_order[ $widget_sort_id ]['page_order'] : false,
		'deleted'       => isset( $widget_sort_order[ $widget_sort_id ]['deleted'] ) ? $widget_sort_order[ $widget_sort_id ]['deleted'] : false,
		'sort_id'       => $widget_sort_id ++,
		'title'         => _l( 'Alerts' ),
		'content'       => ob_get_clean(),
	) );
}
array_unshift( $home_widgets, array(
	'columns'       => isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ? $widget_sort_order[ $widget_sort_id ]['column'] : 1,
	'column_number' => isset( $widget_sort_order[ $widget_sort_id ]['column_number'] ) ? $widget_sort_order[ $widget_sort_id ]['column_number'] : false,
	'page_order'    => isset( $widget_sort_order[ $widget_sort_id ]['page_order'] ) ? $widget_sort_order[ $widget_sort_id ]['page_order'] : false,
	'deleted'       => isset( $widget_sort_order[ $widget_sort_id ]['deleted'] ) ? $widget_sort_order[ $widget_sort_id ]['deleted'] : false,
	'sort_id'       => $widget_sort_id ++,
	'title'         => _l( 'Home Page' ),
	'content'       => _DEMO_MODE ? strip_tags( $template->replace_content() ) : $template->replace_content(),
) );
// now grab the widgets from the various modules and add those in:
$widget_columns_counter = array();
foreach ( $home_widgets as $module_widgets ) {
	if ( isset( $module_widgets['id'] ) || isset( $module_widgets['sort_id'] ) ) {
		$module_widgets = array( $module_widgets );
	}
	foreach ( $module_widgets as $module_widget ) {
		if ( isset( $widget_sort_order[ $widget_sort_id ]['column'] ) ) {
			$module_widget['columns'] = $widget_sort_order[ $widget_sort_id ]['column'];
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
		if ( ! isset( $widget_columns[ $module_widget['columns'] ] ) ) {
			// start the widget group for this number of columns.
			$widget_columns[ $module_widget['columns'] ] = array();
			for ( $x = 1; $x < $module_widget['columns']; $x ++ ) {
				$widget_columns[ $module_widget['columns'] ][ $x ] = array();
			}
		}
		if ( ! isset( $widget_columns_counter[ $module_widget['columns'] ] ) ) {
			$widget_columns_counter[ $module_widget['columns'] ] = 1;
		}
		$col_num = $module_widget['column_number'] ?: $widget_columns_counter[ $module_widget['columns'] ];
		if ( empty( $module_widget['deleted'] ) ) {
			$widget_columns[ $module_widget['columns'] ][ $col_num ][] = $module_widget;
		}
		$widget_columns_counter[ $module_widget['columns'] ] ++;
		if ( $widget_columns_counter[ $module_widget['columns'] ] > $module_widget['columns'] ) {
			$widget_columns_counter[ $module_widget['columns'] ] = 1;
		}
	}
}
unset( $home_widgets );

// now display our widgets in the columns they want
foreach ( $widget_columns as $column_count => $widgets ) {
	?>
	<div class="row">
		<?php foreach ( $widgets as $column_number => $column_widgets ) { ?>
			<section data-cols="<?php echo (int) $column_count; ?>" data-col-number="<?php echo (int) $column_number; ?>"
			         class="<?php switch ( $column_count ) {
				         case 4:
					         echo 'col-lg-3 col-xs-6';
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
			         } ?> connectedSortable">
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
							'id'              => 'widg_' . ( $column_widget['sort_id'] ),
							'heading'         => array(
								'type'  => 'h3',
								'title' => $column_widget['title'],
							),
							'class'           => 'tableclass tableclass_form tableclass_full',
							'elements_before' => $column_widget['content'],
						);
						$widget_html   = module_form::generate_fieldset( $fieldset_data );
						unset( $fieldset_data );
					}
					$widget_html = preg_replace( '#^\s+<\w+\s#imsU', '$0 data-sort-id="' . $column_widget['sort_id'] . '"', $widget_html, 1 );
					echo '<!-- asdf -->' . $widget_html;
				} ?>
			</section>
		<?php } ?>
	</div>
	<?php
}
?>


