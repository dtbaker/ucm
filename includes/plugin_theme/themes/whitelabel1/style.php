<?php

if ( get_display_mode() !== 'mobile' ) {
	$styles = array();

	$styles['body']                               = array(
		'd' => 'Body',
		'v' => array(
			'color'     => '#6f6f6f',
			'font-size' => '12px',
		)
	);
	$styles['h1,h2,h3']                           = array(
		'd' => 'Headings',
		'v' => array(
			'text-shadow'      => '0 1px 0 #ffffff',
			'color'            => '#777777',
			'border-top-color' => '#ffffff',
			'font-size'        => '16px',
		)
	);
	$styles['a, a:hover, a:visited, a:link']      = array(
		'd' => 'Link color',
		'v' => array(
			'color' => '#3f3f3f',
		)
	);
	$styles ['.search_bar']                       = array(
		'd' => 'Search bar',
		'v' => array(
			'background-color' => '#F3F3F3',
		),
	);
	$styles ['#header_logo']                      = array(
		'd' => 'Logo padding',
		'v' => array(
			'padding' => '10px 0 10px 12px',
		),
	);
	$styles ['header']                            = array(
		'd' => 'Header height',
		'v' => array(
			'min-height' => '76px',
		),
	);
	$styles ['table thead tr, table thead tr th'] = array(
		'd' => 'Table Header',
		'v' => array(
			'background-color' => '#e8e8e8',
		),
	);
	$styles ['tr.even']                           = array(
		'd' => 'Table Row (even)',
		'v' => array(
			'background-color' => '#FFFFFF',
		),
	);
	$styles ['tr.odd']                            = array(
		'd' => 'Table Row (odd)',
		'v' => array(
			'background-color' => '#f6f6f6',
		),
	);
	$styles ['tr.hover']                          = array(
		'd' => 'Table Row (hover)',
		'v' => array(
			'background-color' => '#efefef',
		),
	);
	$styles ['input,textarea,select']             = array(
		'd' => 'Input Boxes',
		'v' => array(
			'background-color' => '#ffffff',
			'border-color'     => '#bbbbbb',
		),
	);

	return;
	//todo-change main styles, don't overwrite?
	$styles['body'] = array(
		'd' => 'Overall page settings',
		'v' => array(
			'color'            => '#000000',
			'background-image' => 'none',
			'font-family'      => 'Arial, Helvetica, sans-serif',
			'font-size'        => '12px',
		),
	);
	/*$styles['#header,body,#page_middle,.nav,.content'] = array(
			'd' => 'Page Background',
			'v'=>array(
					'background-color' => '#414141',
			),
	);*/
	$styles['.final_content_wrap'] = array(
		'd' => 'Content Background',
		'v' => array(
			//'background-color' => '#FFF',
			'padding' => '20px 0 0 10px',
			//'border-radius' => '10px',
		),
	);
	unset( $styles['#header,#page_middle,#main_menu'] );
	/*$styles ['#header'] = array(
			'd' => 'Header settings',
			'v'=>array(
					'background-color' => '#414141',
			),
	);*/
	unset( $styles ['body,#profile_info a'] );
	$styles['#profile_info,#profile_info a'] = array(
		'd' => 'Header font color',
		'v' => array(
			'color' => '#BABABA',
		),
	);
	// changing:
	unset( $styles ['#page_middle>.content,.nav>ul>li>a,#page_middle .nav,#quick_search_box'] );
	$styles ['.nav > ul > li.link_current a, .nav > ul > li.link_current a:link, .nav > ul > li.link_current a:visited'] = array(
		'd' => 'Current menu color',
		'v' => array(
			'background-color' => '#FFF',
		),
	);

	$styles ['.nav>ul>li>a,#quick_search_box']['v']['color']            = '#BABABA';
	$styles ['.nav>ul>li>a,#quick_search_box']['v']['background-color'] = '#555';

	$styles ['h2']['v']['color']            = '#414141';
	$styles ['h2']['v']['background-color'] = '#F3F3F3';
	$styles ['h2']['v']['border']           = '1px solid #cbcbcb';
	$styles ['h3']['v']['color']            = '#414141';
	$styles ['h3']['v']['background-color'] = '#DFDFDF';
	$styles ['.search_bar']                 = array(
		'd' => 'Search bar',
		'v' => array(
			'background-color' => '#F3F3F3',
		),
	);
	$styles ['#header']['v']['height']      = '96px';

}