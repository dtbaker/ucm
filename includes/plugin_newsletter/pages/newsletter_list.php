<?php

$search = ( isset( $_REQUEST['search'] ) && is_array( $_REQUEST['search'] ) ) ? $_REQUEST['search'] : array();
if ( isset( $show_draft ) ) {
	$search['draft'] = 1;
}
if ( isset( $show_pending ) ) {
	$search['pending'] = 1;
}
$newsletters = module_newsletter::get_newsletters( $search );


$header           = array(
	'title'  => isset( $show_draft ) && $show_draft ? _l( 'Newsletter Drafts (have not been sent yet)' ) : _l( 'Newsletters' ),
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
$header['button'] = array(
	'url'   => module_newsletter::link_open( 'new' ),
	'title' => _l( 'Add New newsletter' ),
	'type'  => 'add',
);
print_heading( $header );

?>

<form action="" method="post">

	<?php

	$search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Subject:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 15,
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );


	$table_manager                      = module_theme::new_table_manager();
	$columns                            = array();
	$columns['newsletter_subject']      = array(
		'title'      => 'Email Subject',
		'callback'   => function ( $newsletter ) {
			echo module_newsletter::link_open( $newsletter['newsletter_id'], true, $newsletter );
		},
		'cell_class' => 'row_action',
	);
	$columns['newsletter_from']         = array(
		'title'    => 'Sent From',
		'callback' => function ( $newsletter ) {
			?> &lt;<?php echo htmlspecialchars( $newsletter['from_name'] ); ?>&gt; <?php echo htmlspecialchars( $newsletter['from_email'] ); ?><?php
		},
	);
	$columns['newsletter_date']         = array(
		'title'    => 'Sent Date',
		'callback' => function ( $newsletter ) {
			if ( ! $newsletter['send_data'] || ! $newsletter['send_data']['finish_time'] ) {
				echo _l( 'Never sent' );
			} else {
				echo print_date( $newsletter['send_data']['finish_time'], true );
			}
		},
	);
	$columns['newsletter_to']           = array(
		'title'    => 'Sent To',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				echo _l( '%s of %s', (int) $newsletter['send_data']['total_sent_count'], (int) $newsletter['send_data']['total_member_count'] );
			}
		},
	);
	$columns['newsletter_views']        = array(
		'title'    => 'Views',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				echo (int) $newsletter['send_data']['total_open_count'];
				echo ' ';
				if ( $newsletter['send_data']['total_member_count'] > 0 ) {
					echo '(' . (int) ( ( $newsletter['send_data']['total_open_count'] / $newsletter['send_data']['total_member_count'] ) * 100 ) . '%)';
				}
			}
		},
	);
	$columns['newsletter_clicks']       = array(
		'title'    => 'Clicks',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				echo (int) $newsletter['send_data']['total_link_clicks'];
				echo ' ';
				if ( $newsletter['send_data']['total_member_count'] > 0 ) {
					echo '(' . (int) ( ( $newsletter['send_data']['total_link_clicks'] / $newsletter['send_data']['total_member_count'] ) * 100 ) . '%)';
				}
			}
		},
	);
	$columns['newsletter_unsubscribes'] = array(
		'title'    => 'Unsubscribes',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				echo (int) $newsletter['send_data']['total_unsubscribe_count'];
			}
		},
	);
	$columns['newsletter_bounces']      = array(
		'title'    => 'Bounces',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				echo (int) $newsletter['send_data']['total_bounce_count'];
			}
		},
	);
	$columns['newsletter_template']     = array(
		'title'    => 'Template',
		'callback' => function ( $newsletter ) {
			echo htmlspecialchars( $newsletter['newsletter_template_name'] );
		},
	);
	$columns['newsletter_action']       = array(
		'title'    => 'Action',
		'callback' => function ( $newsletter ) {
			if ( $newsletter['send_data'] ) {
				switch ( $newsletter['send_data']['status'] ) {
					case _NEWSLETTER_STATUS_SENT:
						?>
						<a
							href="<?php echo module_newsletter::link_statistics( $newsletter['newsletter_id'], $newsletter['send_id'] ); ?>"><?php _e( 'View Statistics' ); ?></a>
						<a
							href="<?php echo module_newsletter::view_online_url( $newsletter['newsletter_id'], 0, $newsletter['send_id'] ); ?>"><?php _e( 'Preview' ); ?></a>
						<?php
						break;
					case _NEWSLETTER_STATUS_PAUSED:
						?> <a
						href="<?php echo module_newsletter::link_queue_watch( $newsletter['newsletter_id'], $newsletter['send_id'] ); ?>"><?php _e( 'SENDING PAUSED' ); ?></a>  |

						<a
							href="<?php echo module_newsletter::link_preview( $newsletter['newsletter_id'] ); ?>"><?php _e( 'Preview' ); ?></a> <?php
						break;
					case _NEWSLETTER_STATUS_PENDING:
						?> <a
						href="<?php echo module_newsletter::link_queue_watch( $newsletter['newsletter_id'], $newsletter['send_id'] ); ?>"><?php _e( 'CURRENTLY SENDING' ); ?></a>  |

						<a
							href="<?php echo module_newsletter::link_preview( $newsletter['newsletter_id'] ); ?>"><?php _e( 'Preview' ); ?></a> <?php
						break;
					case _NEWSLETTER_STATUS_NEW:
						?> <a
						href="<?php echo module_newsletter::link_queue( $newsletter['newsletter_id'], $newsletter['send_id'] ); ?>"><?php _e( 'SEND' ); ?></a> |

						<a
							href="<?php echo module_newsletter::link_preview( $newsletter['newsletter_id'] ); ?>"><?php _e( 'Preview' ); ?></a> |
						<a
							href="<?php echo module_newsletter::link_open( $newsletter['newsletter_id'] ); ?>"><?php _e( 'Edit' ); ?></a>
						<?php
						break;
				}
			}
		},
	);
	$table_manager->set_columns( $columns );
	$table_manager->row_callback = function ( $newsletter ) {
		$newsletter['send_data'] = false;
		if ( $newsletter['send_id'] ) {
			$newsletter['send_data'] = module_newsletter::get_send( $newsletter['send_id'] );
			// special cache for old newsletter subject.
			if ( isset( $newsletter['send_data']['cache'] ) && strlen( $newsletter['send_data']['cache'] ) > 1 ) {
				$cache = unserialize( $newsletter['send_data']['cache'] );
				if ( $cache ) {
					$newsletter = array_merge( $newsletter, $cache );
				}
			}
		}

		return $newsletter;
	};
	$table_manager->set_rows( $newsletters );

	$table_manager->pagination = true;
	$table_manager->print_table();

	?>
</form>