<?php


$search  = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
$members = module_member::get_members( $search );

// hack to add a "group" option to the pagination results.
if ( class_exists( 'module_group', false ) ) {
	$hook = array(
		'fields'       => array(
			'owner_id'    => 'member_id',
			'owner_table' => 'member',
		),
		'bulk_actions' => array(
			'delete' => array(
				'label'    => 'Delete these members',
				'type'     => 'delete',
				'callback' => 'module_member::handle_bulk_delete',
			),
		),
	);
	if ( class_exists( 'module_newsletter', false ) ) {
		$hook['bulk_actions']['delete_double_optin'] = array(
			'label'    => 'Delete failed double-opt-in members',
			'type'     => 'delete',
			'callback' => 'module_member::handle_bulk_delete_double_optin',
		);
	}
	module_group::enable_pagination_hook(
	// what fields do we pass to the group module from this members?
		$hook
	);
}
// hack to add a "export" option to the pagination results.
if ( class_exists( 'module_import_export', false ) && module_member::can_i( 'view', 'Export Members' ) ) {
	module_import_export::enable_pagination_hook(
	// what fields do we pass to the import_export module from this members?
		array(
			'name'   => 'Member Export',
			'fields' => array(
				'Member ID'     => 'member_id',
				'First Name'    => 'first_name',
				'Last Name'     => 'last_name',
				'Business Name' => 'business',
				'Email'         => 'email',
				'Phone'         => 'phone',
				'Mobile'        => 'mobile',
			),
			// do we look for extra fields?
			'extra'  => array(
				'owner_table' => 'member',
				'owner_id'    => 'member_id',
			),
		)
	);
}


$header = array(
	'title'  => _l( 'Members' ),
	'type'   => 'h2',
	'main'   => true,
	'button' => array(),
);
if ( module_member::can_i( 'create', 'Members' ) ) {
	$header['button'] = array(
		array(
			'url'   => module_member::link_open( 'new' ),
			'title' => _l( 'Create New Member' ),
			'type'  => 'add',
		)
	);
}
if ( class_exists( 'module_import_export', false ) && module_member::can_i( 'view', 'Import Members' ) ) {
	$link               = module_import_export::import_link(
		array(
			'callback'   => 'module_member::handle_import',
			'name'       => 'Members',
			'return_url' => $_SERVER['REQUEST_URI'],
			'group'      => array( 'member', 'newsletter_subscription' ),
			'fields'     => array(
				'Member ID'     => 'member_id',
				'First Name'    => 'first_name',
				'Last Name'     => 'last_name',
				'Business Name' => 'business',
				'Email'         => 'email',
				'Phone'         => 'phone',
				'Mobile'        => 'mobile',
			),
			// do we try to import extra fields?
			'extra'      => array(
				'owner_table' => 'member',
				'owner_id'    => 'member_id',
			),
		)
	);
	$header['button'][] = array(
		'url'   => $link,
		'title' => 'Import members',
		'type'  => 'add',
	);
}
print_heading( $header );


?>

<form action="" method="post">

	<?php

	$search_bar = array(
		'elements' => array(
			'name' => array(
				'title' => _l( 'Names, Phone or Email:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
				)
			),
		)
	);
	if ( class_exists( 'module_group', false ) && module_member::can_i( 'view', 'Member Groups' ) ) {
		$search_bar['elements']['group'] = array(
			'title' => _l( 'Group:' ),
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[group_id]',
				'value'            => isset( $search['group_id'] ) ? $search['group_id'] : '',
				'options'          => module_group::get_groups( 'member' ),
				'options_array_id' => 'name',
			)
		);
	}
	if ( class_exists( 'module_newsletter', false ) ) {
		$search_bar['elements']['group'] = array(
			'title' => _l( 'Newsletter:' ),
			'field' => array(
				'type'             => 'select',
				'name'             => 'search[group_id2]',
				'value'            => isset( $search['group_id2'] ) ? $search['group_id2'] : '',
				'options'          => module_group::get_groups( 'newsletter_subscription' ),
				'options_array_id' => 'name',
			)
		);
	}
	echo module_form::search_bar( $search_bar );


	$table_manager              = module_theme::new_table_manager();
	$columns                    = array();
	$columns['member_name']     = array(
		'title'      => 'Member Name',
		'callback'   => function ( $member ) {
			echo module_member::link_open( $member['member_id'], true );
		},
		'cell_class' => 'row_action',
	);
	$columns['member_business'] = array(
		'title'    => 'Business',
		'callback' => function ( $member ) {
			echo htmlspecialchars( $member['business'] );
		},
	);
	$columns['member_phone']    = array(
		'title'    => 'Phone',
		'callback' => function ( $member ) {
			echo htmlspecialchars( $member['phone'] );
		},
	);
	$columns['member_mobile']   = array(
		'title'    => 'Mobile',
		'callback' => function ( $member ) {
			echo htmlspecialchars( $member['mobile'] );
		},
	);
	$columns['member_email']    = array(
		'title'    => 'Email Address',
		'callback' => function ( $member ) {
			echo htmlspecialchars( $member['email'] );
		},
	);
	if ( class_exists( 'module_subscription', false ) ) {
		$columns['member_subscription'] = array(
			'title'    => 'Subscription',
			'callback' => function ( $member ) {
				foreach ( module_subscription::get_subscriptions_by( 'member', $member['member_id'] ) as $subscription ) {
					echo dollar( $subscription['amount'], true, $subscription['currency_id'] );
					echo ' ';
					echo htmlspecialchars( $subscription['name'] );
					echo ' ';
					$next_due = strtotime( $subscription['next_due_date'] );
					if ( $next_due < time() ) {
						echo ' <span class="important">';
						echo _e( 'Overdue: ' );
						echo '</span> ';
					} else {
						_e( 'Due: ' );
					}
					echo print_date( $next_due );
					$days = ceil( ( $next_due - time() ) / 86400 );
					if ( abs( $days ) == 0 ) {
						_e( ' (today)' );
					} else {
						_e( ' (%s days)', $days );
					}
					// todo - work out if overdue - or when next due.
				}
			},
		);
	}
	if ( class_exists( 'module_group', false ) ) {
		$columns['member_group'] = array(
			'title'    => 'Group',
			'callback' => function ( $member ) {
				// find the groups for this member.
				$g      = array();
				$groups = module_group::get_groups_search( array(
					'owner_table' => 'member',
					'owner_id'    => $member['member_id'],
				) );
				foreach ( $groups as $group ) {
					$g[] = $group['name'];
				}
				echo implode( ', ', $g );
			},
		);
		if ( class_exists( 'module_newsletter', false ) ) {
			$columns['member_newsletter'] = array(
				'title'    => 'Newsletter',
				'callback' => function ( $member ) {
					// find the groups for this member.
					$g      = array();
					$groups = module_group::get_groups_search( array(
						'owner_table' => 'newsletter_subscription',
						'owner_id'    => $member['member_id'],
					) );;
					foreach ( $groups as $group ) {
						$g[] = $group['name'];
					}
					echo implode( ', ', $g );
					echo ' ';
					$newsletter_member_id = module_newsletter::member_from_email( $member, false );
					if ( $newsletter_member_id ) {
						if ( $res = module_newsletter::is_member_unsubscribed( $newsletter_member_id, $member ) ) {
							if ( isset( $res['unsubscribe_send_id'] ) && $res['unsubscribe_send_id'] ) {
								// they unsubscribed from a send.
								$send_data = module_newsletter::get_send( $res['unsubscribe_send_id'] );
								_e( '(unsubscribed %s)', print_date( $res['time'] ) );
							} else if ( isset( $res['reason'] ) && $res['reason'] == 'no_email' ) {
								_e( '(do not send)' );
							} else if ( isset( $res['reason'] ) && $res['reason'] == 'doubleoptin' ) {
								_e( '(double opt-in incomplete)', print_date( $res['time'] ) );
							} else {
								_e( '(unsubscribed %s)', print_date( $res['time'] ) );
							}
						}
					}
				},
			);
		}
	}

	if ( class_exists( 'module_extra', false ) ) {
		$table_manager->display_extra( 'member', function ( $member ) {
			module_extra::print_table_data( 'member', $member['member_id'] );
		} );
	}
	$table_manager->set_columns( $columns );
	$table_manager->set_rows( $members );

	$table_manager->pagination = true;
	$table_manager->print_table();

	$pagination = process_pagination( $members );
	?>

</form>