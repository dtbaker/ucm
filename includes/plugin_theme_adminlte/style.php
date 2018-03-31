<?php
$styles = array();
/*
#wrap {
  background-color: #333;
}*/
$styles['.form-box .header,#login .bg-loginbtn'] = array(
	'd' => 'Login Color',
	'v' => array(
		'background-color' => '#3d9970',
		'color'            => '#FFFFFF',
	)
);

$styles['sidebar-position'] = array(
	'd'        => 'other-settings',
	'elements' => array(
		array(
			'title' => 'Color Style',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_colorstyle]',
				'options' => array(
					'light' => _l( 'Light' ),
					'dark'  => _l( 'Dark' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_colorstyle', 'dark' ),
				'help'    => 'Menu and Header color options.',
			)
		),
		array(
			'title' => 'Box Style',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_boxstyle]',
				'options' => array(
					'box-solid'   => _l( 'Solid White' ),
					'box-gray'    => _l( 'Gray Top Line' ),
					'box-primary' => _l( 'Blue Top Line' ),
					'box-success' => _l( 'Green Top Line' ),
					'box-danger'  => _l( 'Red Top Line' ),
					'box-warning' => _l( 'Orange Top Line' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_boxstyle', 'box-solid' ),
				'help'    => 'Different options for the content box colors.',
			)
		),
		array(
			'title' => 'Form Style',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_formstyle]',
				'options' => array(
					'table' => _l( 'Clean/Compact (table,tr,td)' ),
					'div'   => _l( 'Boxed (divs)' ),
					//'long' => _l('Long (divs)'),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_formstyle', 'table' ),
				'help'    => 'Change how form elements display on the page.',
			)
		),
		array(
			'title' => 'Title Width',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_titlewidth]',
				'options' => array(
					'width1' => _l( 'Small' ),
					'width2' => _l( 'Large' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_titlewidth', 'width1' ),
				'help'    => 'Change how wide the title.',
			)
		),
		array(
			'title' => 'Menu Style',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_menustyle]',
				'options' => array(
					'fixed'  => _l( 'Fixed' ),
					'normal' => _l( 'Normal' ),
					//'long' => _l('Long (divs)'),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_menustyle', 'fixed' ),
				'help'    => 'Change if the menu scrolls with the page or remains fixed.',
			)
		),
		array(
			'title' => 'Badge Color',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_badgecolor]',
				'options' => array(
					'bg-red'        => _l( 'red' ),
					'bg-yellow'     => _l( 'yellow' ),
					'bg-aqua'       => _l( 'aqua' ),
					'bg-blue'       => _l( 'blue' ),
					'bg-light-blue' => _l( 'light blue' ),
					'bg-green'      => _l( 'green' ),
					'bg-navy'       => _l( 'navy' ),
					'bg-teal'       => _l( 'teal' ),
					'bg-olive'      => _l( 'olive' ),
					'bg-lime'       => _l( 'lime' ),
					'bg-orange'     => _l( 'orange' ),
					'bg-fuchsia'    => _l( 'fuchsia' ),
					'bg-purple'     => _l( 'purple' ),
					'bg-maroon'     => _l( 'maroon' ),
					'bg-black'      => _l( 'black' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_badgecolor', 'bg-light-blue' ),
				'help'    => 'The color of the notification bubbles/badges in the menu items.',
			)
		),
		array(
			'title' => 'Table Borders',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_tableborder]',
				'options' => array(
					'1' => _l( "Yes" ),
					'0' => _l( "No" ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_tableborder', 0 ),
				'help'    => 'Verticle lines on table data. Change this and check the Customer list.',
			)
		),
		array(
			'title' => 'Table Striped',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_tablestripe]',
				'options' => array(
					'1' => _l( "Yes" ),
					'0' => _l( "No" ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_tablestripe', 1 ),
				'help'    => 'Alternating colors on table data. Change this and check the Customer list.',
			)
		),
		array(
			'title' => 'Table Full Width',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'adminlte_tablefullwidth]',
				'options' => array(
					'1' => _l( "Yes" ),
					'0' => _l( "No" ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'adminlte_tablefullwidth', 1 ),
				'help'    => 'Makes the table data stretch to the edge of the box. Change this and check the Customer list.',
			)
		),
	)
);
