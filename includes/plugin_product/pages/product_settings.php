<?php

if ( ! $module->can_i( 'view', 'Products' ) || ! $module->can_i( 'edit', 'Products' ) ) {
	redirect_browser( _BASE_HREF );
}

$module->page_title = 'Product Settings';

$links = array(
	array(
		"name"                => 'Products',
		'm'                   => 'product',
		'p'                   => 'product_admin',
		'force_current_check' => true,
		'order'               => 1, // at start.
		'menu_include_parent' => 1,
		'allow_nesting'       => 1,
		'args'                => array( 'product_id' => false ),
	),
	array(
		"name"                => 'Categories',
		'm'                   => 'product',
		'p'                   => 'product_admin_category',
		'force_current_check' => true,
		'order'               => 2, // at start.
		'menu_include_parent' => 1,
		'allow_nesting'       => 1,
		'args'                => array( 'product_id' => false, 'product_category_id' => false ),
	),
);

