<?php

$module->page_title = _l( 'Statistics' );

$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
if ( ! $newsletter_id ) {
	set_error( 'Sorry no newsletter id specified' );
	redirect_browser( module_newsletter::link_list( 0 ) );
}
$newsletter = module_newsletter::get_newsletter( $newsletter_id );
// great a new blank send table ready to go (only if user clicks confirm)
$send_id = isset( $_REQUEST['send_id'] ) ? (int) $_REQUEST['send_id'] : false;
if ( ! $send_id ) {
	set_error( 'Sorry no newsletter send id specified' );
	redirect_browser( module_newsletter::link_open( $newsletter_id ) );
}
$send = module_newsletter::get_send( $send_id );
if ( $send['status'] != _NEWSLETTER_STATUS_SENT ) {
	// hasnt sent yet, redirect to the pending watch page.
	redirect_browser( module_newsletter::link_queue_watch( $newsletter_id, $send_id ) );
}
$start_time = $send['start_time'];


if ( isset( $_REQUEST['show'] ) ) {
	// render the newsletter and display it on screen with nothing else.

	$content = module_newsletter::render( $newsletter_id, $send_id, false, 'preview' );
	// do the link click overview here:

	ob_end_clean();

	// grab all the links for this send
	$send_links       = get_multiple( 'newsletter_link', array( 'send_id' => $send_id ) );
	$links_to_process = array();
	$old_links_by_url = array();
	foreach ( $send_links as $send_link ) {
		// we have to do this because the link processing part puts a unique member id into these unsubscribe/view online links.
		$parsed_url = preg_replace( '#\&nm=\d+#', '&nm=', $send_link['link_url'] );
		$parsed_url = preg_replace( '#\&hash=\w+#', '&nm=', $parsed_url );
		// how many opens did this one have?
		$sql = "SELECT COUNT(*) AS `open_count` FROM `" . _DB_PREFIX . "newsletter_link_open` no ";
		$sql .= " WHERE no.send_id = " . (int) $send_id . " AND no.link_id = " . (int) $send_link['link_id'];
		$res = qa1( $sql );
		if ( ! isset( $old_links_by_url[ $parsed_url ] ) ) {
			$old_links_by_url[ $parsed_url ] = array();
		}
		$links_to_process[ $send_link['link_id'] ]                = (int) $res['open_count'];
		$old_links_by_url[ $parsed_url ][ $send_link['link_id'] ] = (int) $res['open_count'];
	}
	// this code is copied from newsletter::render
	$page_index = 1;
	foreach ( array( "href" ) as $type ) {
		$parts   = preg_split( '/(<a[^>]+' . $type . '=["\'][^"\']+["\'])/', $content, - 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
		$content = '';
		foreach ( $parts as $part_id => $content ) {
			preg_match_all( '/<a[^>]+' . $type . '=(["\'])([^"\']+)\1/', $content, $links );
			if ( is_array( $links[2] ) ) {
				foreach ( $links[2] as $link_match_id => $l ) {
					if ( ! preg_match( '/^\{/', $l ) && ! preg_match( '/^#/', $l ) && ! ( preg_match( '/^\w+:/', $l ) && ! preg_match( '/^http/', $l ) ) ) {
						$search  = preg_quote( $links[0][ $link_match_id ], "/" );
						$l       = preg_replace( "/[\?|&]phpsessid=([\w\d]+)/i", '', $l );
						$l       = ltrim( $l, '/' );
						$newlink = ( ( ! preg_match( '/^http/', $l ) ) ? full_link( '' ) : '' ) . $l;
						$newlink = preg_replace( '#\&nm=\d+#', '&nm=', $newlink );
						$newlink = preg_replace( '#\&hash=\w+#', '&nm=', $newlink );
						//echo "Found link: $newlink<br>\n";
						// search for this link in the DB
						$sql       = "SELECT * FROM `" . _DB_PREFIX . "newsletter_link` WHERE send_id = " . (int) $send_id . " AND link_url = '" . db_escape( $newlink ) . "' AND (page_index = " . (int) $page_index . " OR page_index = 0)";
						$new_count = 0;
						foreach ( qa( $sql ) as $db_link ) {
							$new_count += isset( $links_to_process[ $db_link['link_id'] ] ) ? $links_to_process[ $db_link['link_id'] ] : 0;
						}
						$old_count = isset( $old_links_by_url[ $newlink ] ) ? array_sum( $old_links_by_url[ $newlink ] ) : 0;

						// hack to support non-ssl links when viewing from an ssl account
						if ( ! $new_count && ! $old_count && isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] != '' && $_SERVER['HTTPS'] != 'off' ) {
							$newlink_nonssl = preg_replace( '#^https:#', 'http:', $newlink );
							if ( $newlink_nonssl != $newlink ) {
								$sql       = "SELECT * FROM `" . _DB_PREFIX . "newsletter_link` WHERE send_id = " . (int) $send_id . " AND link_url = '" . db_escape( $newlink_nonssl ) . "' AND (page_index = " . (int) $page_index . " OR page_index = 0)";
								$new_count = 0;
								foreach ( qa( $sql ) as $db_link ) {
									$new_count += isset( $links_to_process[ $db_link['link_id'] ] ) ? $links_to_process[ $db_link['link_id'] ] : 0;
								}
							}
						}


						$content = preg_replace( '/' . preg_quote( $links[0][ $link_match_id ], '/' ) . '/', '<span class="newsletter-click-span">' . ( $new_count ? $new_count : $old_count ) . ' clicks</span>' . $links[0][ $link_match_id ], $content );

						$parts[ $part_id ] = $content;

						$page_index ++;
					}
				}
			}
		}
		$content = implode( '', $parts );
	}

	if ( preg_match_all( '#<a href=["\'].*ext\.php\?t=lnk&id=(\d+)&#', $content, $matches ) ) {
		$processed_links = array();
		foreach ( $matches[0] as $key => $val ) {
			$link_match_id = (int) $matches[1][ $key ];
			if ( isset( $processed_links[ $link_match_id ] ) ) {
				continue;
			}
			$link = $newsletter->get_link( $db, $link_match_id );
			//open_rates
			$template_html                     = preg_replace( '/' . preg_quote( $val, '/' ) . '/', '<span class="newsletter-click-span">' . count( $link['open_rates'] ) . ' clicks</span>' . $val, $content );
			$processed_links[ $link_match_id ] = true;
		}
	}

	ob_start();
	?>

	<style type="text/css">
		span.newsletter-click-span {
			background-color: #FFFFFF !important;
			border: 1px solid #000000 !important;
			color: #000000 !important;
			font-size: 10px !important;
			padding: 2px !important;
			text-decoration: none !important;
			font-weight: normal !important;
			position: absolute !important;
			margin-left: 0px !important;
			filter: alpha(opacity=90);
			-moz-opacity: 0.9;
			-khtml-opacity: 0.9;
			opacity: 0.9;
			/*display: inline-block;*/
		}

		span.newsletter-click-span:hover {
			filter: alpha(opacity=10);
			-moz-opacity: 0.1;
			-khtml-opacity: 0.1;
			opacity: 0.1;
		}
	</style>
	<?php
	$style_tag = ob_get_clean();
	if ( preg_match( '#<head>#i', $content ) ) {
		$content = preg_replace( '#<head>#i', '<head>' . $style_tag, $content );
	} else {
		echo $style_tag;
	}
	echo $content;

	exit;
}

print_heading( array(
	'type'   => 'h2',
	'title'  => 'Newsletter Link Clicks',
	'button' => array(
		'url'   => module_newsletter::link_statistics( $newsletter_id, $send_id ),
		'id'    => 'refresh',
		'title' => 'Back to Statistics',
	),
) );

?>


<iframe src="<?php echo module_newsletter::link_statistics_link_clicks( $newsletter_id, $send_id ); ?>&show=true"
        frameborder="0" style="width:100%; height:700px; border:0;" background="transparent"></iframe>


