<?php

if ( ! isset( $_GET['p'] ) ) {
	redirect_browser( 'index.php?p=home' );
}

?>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/plugins.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/wl_Widget.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/wl_Store.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/flot.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/elfinder.js'; ?>"></script>
<script type="text/javascript"
        src="<?php echo _BASE_HREF . 'includes/plugin_theme/themes/whitelabel1/js/wl_Chart.js'; ?>"></script>
<?php
$calling_module = 'home';
$home_widgets   = handle_hook( 'dashboard_widgets', $calling_module );
$home_widgets2  = hook_handle_callback( 'dashboard_widgets' );
if ( is_array( $home_widgets2 ) ) {
	$home_widgets = array_merge( $home_widgets, $home_widgets2 );
}
?>
<script type="text/javascript">

    var config = {
        tooltip: {
            gravity: 'nw',
            fade: false,
            opacity: 1,
            offset: 0
        }
    };
</script>
<div class="final_content_wrap">
	<div class="g6 widgets">
		<div class="widget" id="widgethome">
			<h3 class="handle"><?php echo _l( 'Home Page' ); ?></h3>
			<div>
				<?php

				module_template::init_template( 'welcome_message', '<p>
   Hi {USER_NAME}, and Welcome to {SYSTEM_NAME}
</p>', 'Welcome message on Dashboard', array(
					'USER_NAME'   => 'Current user name',
					'SYSTEM_NAME' => 'System name from settings area',
				) );
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
				if ( _DEMO_MODE ) {
					echo strip_tags( $template->replace_content() );
				} else {
					echo $template->replace_content();
				}
				?>
			</div>
		</div>
	</div>
	<div class="g6 widgets">
		<?php if ( _DEMO_MODE ) { ?>
			<div class="widget" id="widgetdemo">
				<h3 class="handle">UCM Demonstration Website</h3>
				<div style="text-align: center">
					<p>Welcome to the live demo for <a
							href="http://ultimateclientmanager.com/?utm_source=Demo&utm_medium=HomePage&utm_campaign=WhiteLabel&utm_content=WhiteLabel"
							target="_blank" title="UCM: Open Source CRM, Website and Project Management" style="font-weight: bold">Ultimate
							Client Manager Pro Edition</a> using the lovely <a
							href="http://themeforest.net/item/ucm-theme-white-label/4120556?ref=dtbaker" target="_blank"
							style="font-weight: bold;">White Label</a> theme. <br/>
						This demo resets every now and then, so please feel free to test it out by entering or changing any of the
						data. <br/>
						The Ultimate Client Manager can be purchased and downloaded from CodeCanyon. <br/>
						<em>Please note: This White Label theme needs to be purchased separately to UCM <br/>To try the other themes
							please click <strong>Change Theme</strong> at the top.</em> <br/>
						<a
							href="http://codecanyon.net/item/ultimate-client-manager-pro-edition/2621629?ref=dtbaker&utm_source=Demo&utm_medium=HomePage&utm_campaign=WhiteLabel&utm_content=WhiteLabel"
							target="_blank" title="UCM Website and Project Management">Please click here for more details</a>.</p>
				</div>
			</div>
		<?php } ?>
	</div>
	<div class="g12 widgets">
		<?php /*

        <div class="widget" id="graph_widget" data-icon="graph">
					<h3 class="handle">Graphs Widget</h3>
					<div>
						<table class="chart" data-fill="true" data-legend="false" >
							<thead>
								<tr>
									<th></th>
									<th>1</th>
									<th>2</th>
									<th>3</th>
									<th>4</th>
									<th>5</th>
									<th>6</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<th>First Label</th>
									<td>48</td>
									<td>125</td>
									<td>159</td>
									<td>147</td>
									<td>154</td>
									<td>114</td>
								</tr>
								<tr>
									<th>Second Label</th>
									<td>8</td>
									<td>27</td>
									<td>25</td>
									<td>79</td>
									<td>47</td>
									<td>59</td>
								</tr>
							</tbody>
						</table>
				</div>
				</div>
 */ ?>

		<?php

		if ( module_config::c( 'dashboard_new_layout', 1 ) && class_exists( 'module_dashboard', false ) && module_security::can_user( module_security::get_loggedin_id(), 'Show Dashboard Alerts' ) ) {

			?>
			<div class="widget" id="widget_tabs" data-icon="alert">
				<?php if ( get_display_mode() != 'mobile' ) { ?>
					<h3 class="handle"><?php _e( 'Alerts' ); ?></h3>
					<?php
				}
				module_dashboard::output_dashboard_alerts();
				?>
			</div>
			<?php

		}

		?> </div> <?php


	$widget_columns = array(
		1 => array(),
		2 => array(),
		3 => array(),
	);
	$x              = 1;
	foreach ( $home_widgets as $module_widgets ) {
		foreach ( $module_widgets as $module_widget ) {
			if ( ! isset( $widget_columns[ $x ] ) ) {
				$x = 1;
			}
			$widget_columns[ $x ][] = $module_widget;
			$x ++;
		}
	}
	unset( $home_widgets );
	foreach ( $widget_columns as $column_number => $column_widgets ) {
		?>
		<div class="g4 widgets">
			<?php foreach ( $column_widgets as $column_widget ) { ?>
				<div
					class="widget"<?php echo isset( $column_widget['icon'] ) ? ' data-icon="' . $column_widget['icon'] . '"' : ''; ?><?php echo isset( $column_widget['id'] ) ? ' id="' . $column_widget['id'] . '"' : ''; ?>>
					<h3 class="handle"><?php echo $column_widget['title']; ?></h3>
					<div>
						<?php echo $column_widget['content']; ?>
					</div>
				</div>
			<?php } ?>
		</div>
	<?php } ?>

	<?php if ( get_display_mode() == 'mobile' ) { ?>
		<!-- end page -->
		<p>
			<a href="?display_mode=desktop"><?php _e( 'Switch to desktop mode' ); ?></a>
		</p>
	<?php } ?>
</div>



