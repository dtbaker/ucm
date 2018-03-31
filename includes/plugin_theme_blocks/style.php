<?php
$styles = array();
/*
#wrap {
  background-color: #333;
}*/

$styles['sidebar-position'] = array(
	'd'        => 'other-settings',
	'elements' => array(
		array(
			'title' => 'Gravatar',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[blocks_enable_gravitar]',
				'options' => array(
					1 => _l( 'Shown' ),
					0 => _l( 'Hidden' ),
				),
				'blank'   => false,
				'value'   => module_config::c( 'blocks_enable_gravitar', 1 ),
				'help'    => 'Show the avatar in the top corner of the design. Configure your avatar from the http://www.gravatar.com website.',
			)
		),
		array(
			'title' => 'Menu Background',
			'field' => array(
				'type'  => 'text',
				'name'  => 'config[' . _THEME_CONFIG_PREFIX . 'blocks_menu_bg]',
				'value' => module_theme::get_config( 'blocks_menu_bg', '#aef6fa' ),
				'help'  => 'Color code for background of menu. Default color is #aef6fa ',
			)
		),
		array(
			'title' => 'Menu Font',
			'field' => array(
				'type'  => 'text',
				'name'  => 'config[' . _THEME_CONFIG_PREFIX . 'blocks_menu_fg]',
				'value' => module_theme::get_config( 'blocks_menu_fg', '#1f3334' ),
				'help'  => 'Color code for text and icons in menu. Default color is #1f3334 ',
			)
		),
		array(
			'title' => 'Menu Collapse',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'blocks_menu_collapse]',
				'options' => array(
					'allow'  => _l( 'Show Collapse Button' ),
					'hidden' => _l( 'Hide Collapse Button' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'blocks_menu_collapse', 'allow' ),
				'help'    => 'If the menu collapse button should be shown on the left.',
			)
		),
		array(
			'title' => 'Box Sizing',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'blocks_boxstyle]',
				'options' => array(
					'box-large' => _l( 'Large Sizing' ),
					'box-small' => _l( 'Small Sizing' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'blocks_boxstyle', 'box-large' ),
				'help'    => 'Sizing of all page elements. Large or small.',
			)
		),
		array(
			'title' => 'Content Padding',
			'field' => array(
				'type'    => 'select',
				'name'    => 'config[' . _THEME_CONFIG_PREFIX . 'blocks_content_padding]',
				'options' => array(
					'with-content-padding' => _l( 'With Padding' ),
					'no-content-padding'   => _l( 'No Padding' ),
				),
				'blank'   => false,
				'value'   => module_theme::get_config( 'blocks_content_padding', 'with-content-padding' ),
				'help'    => 'If page content should have padding. Default is with padding.',
			)
		),
		/*array(
				'title' => 'Color Style',
				'field' => array(
						'type' => 'select',
						'name' => 'config['._THEME_CONFIG_PREFIX.'blocks_colorstyle]',
						'options' => array(
								'light' => _l('Light'),
								'dark' => _l('Dark'),
						),
					'blank' => false,
						'value' => module_theme::get_config('blocks_colorstyle','dark'),
						'help' => 'Different overall color options.',
				)
		),*/
	)
);
