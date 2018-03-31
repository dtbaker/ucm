<!-- show a list of tabs for all the different social methods, as menu hooks -->

<?php

$module->page_title = _l( 'Social' );


$links = array();
if ( module_social::can_i( 'view', 'Combined Comments', 'Social', 'social' ) ) {
	$links [] = array(
		"name"                => _l( 'Inbox' ),
		'm'                   => 'social',
		'p'                   => 'social_messages',
		'args'                => array(
			'combined'           => 1,
			'social_twitter_id'  => false,
			'social_facebook_id' => false,
		),
		'force_current_check' => true,
		//'current' => isset($_GET['combined']),
		'order'               => 1, // at start
		'menu_include_parent' => 0,
		'allow_nesting'       => 1,
	);

	//if(isset($_GET['combined'])){
	//	include('social_messages.php');
	//}
}