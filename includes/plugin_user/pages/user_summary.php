<?php

// Rendered by module_user::print_user_summary( $user_id, 'full' ).
//
// No fieldset id or heading: this is a read-only block embedded inside other pages, so
// it must not read or write per-user field display settings in the form table.

echo module_form::generate_fieldset( array(
	'elements' => array(
		array(
			'title'  => 'Contact Name',
			'fields' => array(
				htmlspecialchars( (string) $user_data['name'] )
				. ' <a href="' . htmlspecialchars( $plugins['user']->link_open( $user_id ) ) . '">&raquo;</a>',
			),
		),
		array(
			'title'  => 'Phone',
			'fields' => array( htmlspecialchars( (string) $user_data['phone'] ) ),
		),
		array(
			'title'  => 'Mobile',
			'fields' => array( htmlspecialchars( (string) $user_data['mobile'] ) ),
		),
		array(
			'title'  => 'Fax',
			'fields' => array( htmlspecialchars( (string) $user_data['fax'] ) ),
		),
		array(
			'title'  => 'Email',
			'fields' => array(
				'<a href="mailto:' . htmlspecialchars( (string) $user_data['email'] ) . '">'
				. htmlspecialchars( (string) $user_data['email'] ) . '</a>',
			),
		),
		array(
			'title'  => '',
			'fields' => array(
				'<a href="' . htmlspecialchars( $plugins['user']->login_link( $user_id ) ) . '">'
				. htmlspecialchars( _l( 'Login as this Administrator' ) ) . ' &raquo;</a>',
			),
		),
	),
) );
