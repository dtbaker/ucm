<?php

if ( ! module_social::can_i( 'edit', 'Facebook', 'Social', 'social' ) ) {
	die( 'No access to Facebook accounts' );
}

$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
$facebook           = new ucm_facebook_account( $social_facebook_id );

$facebook_page_id = isset( $_REQUEST['facebook_page_id'] ) ? (int) $_REQUEST['facebook_page_id'] : 0;

/* @var $pages ucm_facebook_page[] */
$pages = $facebook->get( 'pages' );
if ( ! $facebook_page_id || ! $pages || ! isset( $pages[ $facebook_page_id ] ) ) {
	die( 'No pages found to refresh' );
}
?>
	Manually refreshing page data...
<?php

$pages[ $facebook_page_id ]->graph_load_latest_page_data( true );
