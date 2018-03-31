<?php
if ( isset( $_REQUEST['go'] ) ) {
	ob_end_clean();
	echo '<pre>';
	_e( "Checking for bounces, please wait..." );
	echo "\n\n";
	module_newsletter::check_bounces( true );
	echo "\n\n";
	_e( "done." );
	echo '</pre>';

	exit;
}

$module->page_title = _l( 'Newsletter Bounce Checking' );
print_heading( 'Newsletter Bounce Checking' );

?>
<p><?php _e( 'Bounces are checked automatically using the CRON job, however if you want to check for bounces manually (ie: to see any error) please click the button below.' ); ?></p>
<form action="" method="post">
	<input type="submit" name="go" value="<?php _e( 'Check for bounces' ); ?>">
</form>