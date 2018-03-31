<?php

if ( module_social::can_i( 'view', 'Facebook Comments', 'Social', 'social' ) ) {
	$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
	$facebook           = new ucm_facebook_account( $social_facebook_id );
	if ( $social_facebook_id && $facebook->get( 'social_facebook_id' ) == $social_facebook_id ) {

		$module->page_title = $facebook->get( 'facebook_name' );

		$header = array(
			'title'  => $facebook->get( 'facebook_name' ),
			'type'   => 'h2',
			'main'   => true,
			'button' => array(),
		);
		if ( module_social::can_i( 'create', 'Facebook Comments', 'Social', 'social' ) ) {
			$header['button'] = array(
				'url'   => module_social_facebook::link_open_facebook_message( $social_facebook_id, false ),
				'title' => _l( 'Compose Post' ),
				'type'  => 'add',
				'class' => 'socialfacebook_message_open social_modal',
				'id'    => 'socialfacebook_message_compose',
			);
		}
		print_heading( $header );

		$search = isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
		if ( ! isset( $search['status'] ) ) {
			$search['status'] = _SOCIAL_MESSAGE_STATUS_UNANSWERED;
		}

		/* @var $pages ucm_facebook_page[] */
		$pages        = $facebook->get( 'pages' );
		$all_messages = array();
		foreach ( $pages as $page ) {
			$page_messages = $page->get_messages(
				$search
			);
			$page_messages = query_to_array( $page_messages );
			foreach ( $page_messages as $id => $page_message ) {
				$page_messages[ $id ]['page'] = $page;
			}
			$all_messages = array_merge( $all_messages, $page_messages );
		}
		function socialfb_sort_messages( $a, $b ) {
			return $a['last_active'] < $b['last_active'];
		}

		uasort( $all_messages, 'socialfb_sort_messages' );

		?>

		<script type="text/javascript">
        $(function () {
            $('#socialfacebook_message_compose').attr('data-modal-title', '<?php _e( 'Compose Post' );?>');
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
					?> <img src="<?php echo _BASE_HREF; ?>includes/plugin_social_facebook/images/facebook.png"
					        class="facebook_icon">
					<a href="<?php echo $message['facebook_message']->get_link(); ?>"
					   target="_blank"><?php echo htmlspecialchars( $message['page']->get( 'page_name' ) ); ?></a> <br/>
					<?php echo htmlspecialchars( $message['facebook_message']->get_type_pretty() );
				},
			);
			$columns['social_column_time']    = array(
				'title'    => 'Date/Time',
				'callback' => function ( &$message ) {
					echo print_date( $message['facebook_message']->get( 'last_active' ), true );
				},
			);
			$columns['social_column_from']    = array(
				'title'    => 'From',
				'callback' => function ( &$message ) {
					// work out who this is from.
					$from = $message['facebook_message']->get_from();
					?>
					<div class="social_from_holder social_facebook">
						<div class="social_from_full">
							<?php
							foreach ( $from as $id => $name ) {
								?>
								<div>
									<a href="http://facebook.com/<?php echo $id; ?>" target="_blank"><img
											src="http://graph.facebook.com/<?php echo $id; ?>/picture"
											class="social_from_picture"></a> <?php echo htmlspecialchars( $name ); ?>
								</div>
								<?php
							} ?>
						</div>
						<?php
						reset( $from );
						echo '<a href="http://facebook.com/' . key( $from ) . '" target="_blank">' . '<img src="http://graph.facebook.com/' . key( $from ) . '/picture" class="social_from_picture"></a> ';
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
					?> <span style="float:right;">
					    <?php echo count( $message['comments'] ) > 0 ? '(' . count( $message['comments'] ) . ')' : ''; ?>
				    </span>
					<div class="facebook_message_summary"><a href="#"><?php
							echo $message['facebook_message']->get_summary();
							?></a>
					</div> <?php
				},
			);
			$columns['social_column_action']  = array(
				'title'    => 'Action',
				'callback' => function ( &$message ) {
					if ( module_social::can_i( 'view', 'Facebook Comments', 'Social', 'social' ) ) {
						?>
						<a
							href="<?php echo module_social_facebook::link_open_facebook_message( $message['social_facebook_id'], $message['social_facebook_message_id'] ); ?>"
							class="socialfacebook_message_open social_modal btn btn-default btn-xs"
							data-modal-title="<?php echo htmlspecialchars( $message['facebook_message']->get_summary() ); ?>"><?php _e( 'Open' ); ?></a>
					<?php } ?>
					<?php if ( module_social::can_i( 'edit', 'Facebook Comments', 'Social', 'social' ) ) { ?>
						<?php if ( $message['facebook_message']->get( 'status' ) == _SOCIAL_MESSAGE_STATUS_ANSWERED ) { ?>
							<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
							   data-action="set-unanswered"
							   data-id="<?php echo (int) $message['facebook_message']->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Un-Archive' ); ?></a>
						<?php } else { ?>
							<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
							   data-action="set-answered"
							   data-id="<?php echo (int) $message['facebook_message']->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Archive' ); ?></a>
						<?php } ?>
					<?php }
				},
			);
			$table_manager->set_columns( $columns );
			$table_manager->row_callback = function ( $message ) use ( &$facebook, &$table_manager ) {
				$facebook_message              = new ucm_facebook_message( $facebook, $message['page'], $message['social_facebook_message_id'] );
				$table_manager->row_class      = 'facebook_message_row' . ( ! isset( $message['read_time'] ) || ! $message['read_time'] ? ' message_row_unread' : '' );
				$table_manager->row_attributes = array(
					'data-id' => (int) $message['social_facebook_message_id'],
				);

				return array(
					'facebook_message' => $facebook_message,
					'comments'         => $facebook_message->get_comments(),
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
            ucm.social.facebook.api_url = '<?php echo module_social_facebook::link_social_ajax_functions( $social_facebook_id );?>';
            ucm.social.facebook.init();
        });
		</script>

		<div id="social_modal_popup" title="">
			<div class="modal_inner" style="height:100%;"></div>
		</div>

		<?php

	}
}