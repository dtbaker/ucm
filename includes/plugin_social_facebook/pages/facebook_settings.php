<?php


if ( _DEMO_MODE ) {
	?>
	<p>Demo Mode Notice: <strong>This is a public demo. Please only use TEST accounts here as others will see
			them.</strong></p>
	<?php
}


if ( isset( $_REQUEST['social_facebook_id'] ) && ! empty( $_REQUEST['social_facebook_id'] ) ) {
	$social_facebook_id = (int) $_REQUEST['social_facebook_id'];
	$social_facebook    = module_social_facebook::get( $social_facebook_id );
	include( 'facebook_account_edit.php' );
} else {
	include( 'facebook_account_list.php' );
}
