<?php

/**
 * The help panel's contents.
 *
 * Reached as index.php?m=help&p=help_article&slug=... with display_mode=ajax, so it goes
 * through the application's own routing and its own login check - a logged out request
 * gets a 401 here rather than a login form rendered inside a modal.
 *
 * Everything this prints has already been through ucm_help_docs::clean_article() and,
 * where the text came over the network, HTMLPurifier as well.
 */

if ( ! module_help::is_plugin_enabled() || ! module_help::visible_to_current_user() ) {
	echo '<p>' . _l( 'You do not have permission to view the documentation.' ) . '</p>';

	return;
}

$help_slug = isset( $_REQUEST['slug'] ) && is_string( $_REQUEST['slug'] ) ? $_REQUEST['slug'] : '';
$help_slug = ucm_help_docs::clean_slug( $help_slug );
if ( ! $help_slug && isset( $_REQUEST['page_key'] ) && is_string( $_REQUEST['page_key'] ) ) {
	$help_slug = module_help::slug_for_page( $_REQUEST['page_key'] );
}

$help_article  = $help_slug ? module_help::get_article( $help_slug ) : false;
$help_web_url  = module_help::docs_url( $help_article ? $help_slug : '' );
$help_index    = module_help::load_index();

?>
<div class="help_panel">

	<?php if ( ! $help_article ) { ?>

		<?php
		// Either no article covers this screen yet, or the slug asked for is not one this
		// release ships. Both end up here, and both get the documentation index rather
		// than an error - there is always somewhere useful to send someone.
		?>
		<p><?php echo _l( 'There is no specific article for this screen yet.' ); ?></p>
		<p>
			<a href="<?php echo htmlspecialchars( $help_web_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo _l( 'Browse the documentation' ); ?>
			</a>
		</p>

	<?php } else { ?>

		<h3 class="help_panel_title"><?php echo htmlspecialchars( $help_article['title'] ); ?></h3>

		<?php if ( ! empty( $help_article['summary'] ) ) { ?>
			<p class="help_panel_summary"><?php echo htmlspecialchars( $help_article['summary'] ); ?></p>
		<?php } ?>

		<?php if ( ! empty( $help_article['stale'] ) ) { ?>
			<p class="help_panel_stale"><?php echo _l( 'This copy may be out of date.' ); ?></p>
		<?php } ?>

		<?php if ( ! empty( $help_article['video'] ) ) { ?>
			<?php
			// A link, never an embed. Embedding would put a third party frame inside the
			// admin interface, which is the thing this whole panel exists instead of.
			?>
			<p class="help_panel_video">
				<a href="<?php echo htmlspecialchars( ucm_help_docs::video_url( $help_article['video'] ) ); ?>"
				   target="_blank" rel="noopener noreferrer">
					<i class="fa fa-play-circle"></i>
					<?php echo _l( 'Watch the video' ); ?>
				</a>
				<span class="help_panel_video_note"><?php echo _l( '(opens YouTube)' ); ?></span>
			</p>
		<?php } ?>

		<?php if ( ! empty( $help_article['html'] ) ) { ?>
			<div class="help_panel_body"><?php echo $help_article['html']; ?></div>
		<?php } ?>

		<p class="help_panel_more">
			<a href="<?php echo htmlspecialchars( $help_web_url ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo _l( 'Read this on the documentation site' ); ?>
			</a>
		</p>

	<?php } ?>

	<?php if ( count( $help_index ) ) { ?>
		<ul class="help_panel_related">
			<?php
			$help_shown = 0;
			foreach ( $help_index as $help_other_slug => $help_other ) {
				if ( $help_other_slug === $help_slug || $help_shown >= 6 ) {
					continue;
				}
				if ( empty( $help_other['featured'] ) ) {
					continue;
				}
				$help_shown++;
				?>
				<li>
					<a href="<?php echo htmlspecialchars( module_help::docs_url( $help_other_slug ) ); ?>"
					   class="ucm_help_link" data-help-slug="<?php echo htmlspecialchars( $help_other_slug ); ?>"
					   target="_blank" rel="noopener noreferrer">
						<?php echo htmlspecialchars( isset( $help_other['title'] ) ? $help_other['title'] : $help_other_slug ); ?>
					</a>
				</li>
			<?php } ?>
		</ul>
	<?php } ?>

</div>
