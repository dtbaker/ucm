<?php

if ( ! module_social::can_i( 'edit', 'Twitter', 'Social', 'social' ) ) {
	die( 'No access to Twitter accounts' );
}

$social_twitter_id = isset( $_REQUEST['social_twitter_id'] ) ? (int) $_REQUEST['social_twitter_id'] : 0;
$twitter_account   = new ucm_twitter_account( $social_twitter_id );


?>
	Manually refreshing twitter data...
<?php

$twitter_account->import_data( true );
