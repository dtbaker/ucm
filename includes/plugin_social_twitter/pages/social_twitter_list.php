<?php

if ( module_social::can_i( 'view', 'Twitter Comments', 'Social', 'social' ) ) {
	$social_twitter_id = isset( $_REQUEST['social_twitter_id'] ) ? (int) $_REQUEST['social_twitter_id'] : 0;
	$twitter           = new ucm_twitter_account( $social_twitter_id );
	if ( $social_twitter_id && $twitter->get( 'social_twitter_id' ) == $social_twitter_id ) {

		$module->page_title = $twitter->get( 'account_name' );

		$header = array(
			'title'  => $twitter->get( 'account_name' ),
			'type'   => 'h2',
			'main'   => true,
			'button' => array(),
		);
		if ( module_social::can_i( 'create', 'Twitter Comments', 'Social', 'social' ) ) {
			$header['button'] = array(
				'url'   => module_social_twitter::link_open_twitter_message( $social_twitter_id, false ),
				'title' => _l( 'Compose Tweet' ),
				'type'  => 'add',
				'class' => 'socialtwitter_message_open social_modal',
				'id'    => 'socialtwitter_message_compose',
			);
		}
		print_heading( $header );

		$search = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
		if ( ! isset( $search['status'] ) ) {
			$search['status'] = _SOCIAL_MESSAGE_STATUS_UNANSWERED;
		}

		$all_messages = $twitter->get_messages( $search );

		?>
		<script type="text/javascript">
        $(function () {
            $('#socialtwitter_message_compose').attr('data-modal-title', '<?php _e( 'Compose Tweet' );?>');
        });
		</script>

		<form action="" method="post">

			<?php $search_bar = array(
				'elements' => array(
					'name'   => array(
						'title' => _l( 'Message Content:' ),
						'field' => array(
							'type'  => 'text',
							'name'  => 'search[generic]',
							'value' => isset( $search['generic'] ) ? $search['generic'] : '',
							'size'  => 15,
						)
					),
					'status' => array(
						'title' => _l( 'Status:' ),
						'field' => array(
							'type'    => 'select',
							'name'    => 'search[status]',
							'blank'   => false,
							'value'   => isset( $search['status'] ) ? $search['status'] : '',
							'options' => array(
								_SOCIAL_MESSAGE_STATUS_UNANSWERED => 'Un-Archived',
								_SOCIAL_MESSAGE_STATUS_ANSWERED   => 'Archived',
							),
						)
					),
				)
			);
			echo module_form::search_bar( $search_bar );

			$table_manager                    = module_theme::new_table_manager();
			$columns                          = array();
			$columns['social_column_social']  = array(
				'title'      => 'Social Account',
				'cell_class' => 'row_action',
				'callback'   => function ( &$message ) {
					?> <img src="<?php echo _BASE_HREF; ?>includes/plugin_social_twitter/images/twitter-logo.png"
					        class="twitter_icon">
					<a href="<?php echo $message['twitter_message']->get_link(); ?>"
					   target="_blank"><?php echo htmlspecialchars( $message['twitter_message']->get( 'twitter_account' )->get( 'account_name' ) ); ?></a>
					<br/>
					<?php echo htmlspecialchars( $message['twitter_message']->get_type_pretty() );
				},
			);
			$columns['social_column_time']    = array(
				'title'    => 'Date/Time',
				'callback' => function ( &$message ) {
					echo print_date( $message['message_time'], true );
				},
			);
			$columns['social_column_from']    = array(
				'title'    => 'From',
				'callback' => function ( &$message ) {
					// work out who this is from.
					$from = $message['twitter_message']->get_from();
					?>
					<div class="social_from_holder social_twitter">
						<div class="social_from_full">
							<?php
							foreach ( $from as $id => $from_data ) {
								?>
								<div>
									<a href="http://twitter.com/<?php echo htmlspecialchars( $from_data['screen_name'] ); ?>"
									   target="_blank"><img src="<?php echo $from_data['image']; ?>"
									                        class="social_from_picture"></a> <?php echo htmlspecialchars( $from_data['screen_name'] ); ?>
								</div>
								<?php
							} ?>
						</div>
						<?php
						reset( $from );
						$current = current( $from );
						echo '<a href="http://twitter.com/' . htmlspecialchars( $current['screen_name'] ) . '" target="_blank">' . '<img src="' . $current['image'] . '" class="social_from_picture"></a> ';
						echo '<span class="social_from_count">';
						if ( count( $from ) > 1 ) {
							echo '+' . ( count( $from ) - 1 );
						}
						echo '</span>';
						?>
					</div> <?php
				},
			);
			$columns['social_column_summary'] = array(
				'title'    => 'Summary',
				'callback' => function ( &$message ) {
					?>
					<div class="twitter_message_summary"><a href="#"><?php
							echo $message['twitter_message']->get_summary();
							?></a>
					</div> <?php
				},
			);
			$columns['social_column_action']  = array(
				'title'    => 'Action',
				'callback' => function ( &$message ) {
					if ( module_social::can_i( 'view', 'Twitter Comments', 'Social', 'social' ) ) { ?>
						<a
							href="<?php echo module_social_twitter::link_open_twitter_message( $message['social_twitter_id'], $message['social_twitter_message_id'] ); ?>"
							class="socialtwitter_message_open social_modal btn btn-default btn-xs"
							data-modal-title="<?php echo _l( 'Tweet' ); ?>"><?php _e( 'Open' ); ?></a>

					<?php } ?>
					<?php if ( module_social::can_i( 'edit', 'Twitter Comments', 'Social', 'social' ) ) { ?>
						<?php if ( $message['twitter_message']->get( 'status' ) == _SOCIAL_MESSAGE_STATUS_ANSWERED ) { ?>
							<a href="#" class="socialtwitter_message_action btn btn-default btn-xs"
							   data-action="set-unanswered"
							   data-id="<?php echo (int) $message['twitter_message']->get( 'social_twitter_message_id' ); ?>"
							   data-social_twitter_id="<?php echo (int) $message['twitter_message']->get( 'social_twitter_id' ); ?>"><?php _e( 'Un-Archive' ); ?></a>
						<?php } else { ?>
							<a href="#" class="socialtwitter_message_action btn btn-default btn-xs"
							   data-action="set-answered"
							   data-id="<?php echo (int) $message['twitter_message']->get( 'social_twitter_message_id' ); ?>"
							   data-social_twitter_id="<?php echo (int) $message['twitter_message']->get( 'social_twitter_id' ); ?>"><?php _e( 'Archive' ); ?></a>
						<?php } ?>
					<?php }
				},
			);
			$table_manager->set_columns( $columns );
			$table_manager->row_callback = function ( $message ) use ( &$twitter, &$table_manager ) {
				$twitter_message               = new ucm_twitter_message( $twitter, $message['social_twitter_message_id'] );
				$table_manager->row_class      = 'twitter_message_row' . ( ! isset( $message['read_time'] ) || ! $message['read_time'] ? ' message_row_unread' : '' );
				$table_manager->row_attributes = array(
					'data-id'                => (int) $message['social_twitter_message_id'],
					'data-social_twitter_id' => (int) $message['social_twitter_id'],
				);

				return array(
					'twitter_message' => $twitter_message,
				);
			};
			$table_manager->set_rows( $all_messages );
			$table_manager->pagination = true;
			$table_manager->print_table();

			?>
		</form>
		<script type="text/javascript">
        $(function () {
            ucm.social.init();
            ucm.social.twitter.api_url = '<?php echo module_social_twitter::link_social_ajax_functions();?>';
            ucm.social.twitter.init();
        });
		</script>

		<div id="social_modal_popup" title="">
			<div class="modal_inner" style="height:100%;"></div>
		</div>

		<?php

	}
}