<?php

if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}


$show_theme_selector = false;
$themes              = module_theme::get_available_themes();
if ( count( $themes ) > 1 ) {
	$show_theme_selector = true;
}
if ( $show_theme_selector ) {
	$settings = array(
		array(
			'key'              => 'theme_name',
			'default'          => 'default',
			'type'             => 'select',
			'options'          => $themes,
			'options_array_id' => 'name',
			'description'      => 'Default theme to use',
		),
		array(
			'key'         => _THEME_CONFIG_PREFIX . 'theme_logo',
			'default'     => _BASE_HREF . 'images/logo.png',
			'type'        => 'text',
			'description' => 'URL for header logo',
		),
		array(
			'key'         => _THEME_CONFIG_PREFIX . 'theme_favicon',
			'default'     => '',
			'type'        => 'text',
			'description' => 'URL for favicon',
			'help'        => 'Please google for "How to make a favicon". It should be a small PNG or ICO image.'
		),
		array(
			'key'         => _THEME_CONFIG_PREFIX . 'theme_custom_css',
			'default'     => '',
			'type'        => 'textarea',
			'description' => 'Custom CSS Code',
			'help'        => 'Add your own custom CSS code here and it will be included in all pages of the website. You may have to clear your browser cache in order to see these changes. This code is added to the "ext.php?m=theme&amp;h=css" file if you are looking at the page source code.'
		),
		array(
			'key'         => _THEME_CONFIG_PREFIX . 'theme_custom_head',
			'default'     => '',
			'type'        => 'textarea',
			'description' => 'Custom Header Code',
			'help'        => 'Add your own custom HTML/JS code here and it will be included in all pages of the website. You may have to clear your browser cache in order to see these changes. '
		),
	);
	if ( module_security::is_logged_in() && module_config::c( 'theme_per_user', 0 ) ) {
		$default_theme = is_dir( 'includes/plugin_theme/themes/metis/' ) ? 'metis' : 'default';
		$settings[]    = array(
			'key'              => 'theme_name_' . module_security::get_loggedin_id(),
			'default'          => module_config::c( 'theme_name', $default_theme ),
			'options'          => $themes,
			'options_array_id' => 'name',
			'type'             => 'select',
			'description'      => 'Theme to use when logged into your account'
		);
	}

	module_config::print_settings_form(
		array(
			'title'    => _l( 'Theme Settings' ),
			'settings' => $settings,
		)
	);
}

?>

<form action="" method="post">
	<input type="hidden" name="_config_settings_hook" value="save_config">

	<?php
	module_form::print_form_auth();
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);
	?>

	<?php ob_start(); ?>
	<p><?php _e( 'This is just a basic CSS editor. Paste in CSS compatible rules over the top of defaults. Click the default value to return to that value.' ); ?>
		<br/><?php _e( 'More advanced changes can be made like normal in the /css/styles.css and /css/desktop.css files. (use Chrome or Firebug to locate the styles you wish to change)' ); ?>
	</p>

	<table class="tableclass tableclass_rows">
		<thead>
		<tr>
			<th>
				<?php _e( 'Description' ); ?>
			</th>
			<th>
				<?php _e( 'CSS Property' ); ?>
			</th>
			<th>
				<?php _e( 'Value' ); ?>
			</th>
			<th>
				<?php _e( 'Default' ); ?>
			</th>
		</tr>
		</thead>
		<tbody>
		<?php
		$r = 1;
		$x = 1;
		foreach ( module_theme::get_theme_styles( module_theme::$current_theme ) as $style ) {
			$c = 0;
			if ( isset( $style['v'] ) && is_array( $style['v'] ) ) {
				foreach ( $style['v'] as $k => $v ) {
					$c ++;
					?>
					<tr class="<?php echo $x % 2 ? 'odd' : 'even'; ?>">
						<?php if ( $c == 1 ) { ?>
							<td rowspan="<?php echo count( $style['v'] ); ?>"><?php echo $style['d']; ?></td>
						<?php } ?>
						<td>
							<?php echo $k; ?>
						</td>
						<td>
							<?php switch ( $k ) {
								default;
									?>
									<input type="text"
									       name="config[_theme_<?php echo htmlspecialchars( module_theme::$current_theme . $style['r'] . '_' . $k ); ?>]"
									       value="<?php echo htmlspecialchars( $v[0] ); ?>" size="60" id="s<?php echo $r; ?>">
									<?php
									break;
							} ?>
						</td>
						<td<?php if ( $v[0] != $v[1] ) {
							echo ' style="font-weight:bold"';
						} ?>>
							<a href="#"
							   onclick="$('#s<?php echo $r; ?>').val('<?php echo htmlspecialchars( $v[1] ); ?>');return false;"><?php echo htmlspecialchars( $v[1] ); ?></a>
						</td>
					</tr>
					<?php
					$r ++;
				}
			} else if ( isset( $style['elements'] ) && is_array( $style['elements'] ) ) {
				?>

				<tr class="<?php echo $x % 2 ? 'odd' : 'even'; ?>">
					<td colspan="4">
						<?php
						echo module_form::generate_fieldset( array(
							'elements' => $style['elements'],
						) ); ?>
					</td>
				</tr>
				<?php
			}
			$x ++;
		} ?>

		</tbody>
	</table>

	<?php

	$form_actions = array(
		'class'    => 'action_bar action_bar_center action_bar_single',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'save',
				'value' => _l( 'Save settings' ),
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );

	$fieldset_data['title']           = _l( 'Theme Styles' );
	$fieldset_data['elements_before'] = ob_get_clean();
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );
	?>

</form>

