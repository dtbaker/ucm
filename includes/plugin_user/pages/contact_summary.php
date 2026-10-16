<?php

// Contact detail block.
//
// Nothing in the tree renders this: module_user::print_contact_summary() has no 'full'
// branch to include it, and it was written for an object context - the $this call it
// used to open with fatals ("Using $this when not in object context") in every caller
// that could reach it. Kept, converted, and the call made static so a theme or custom
// page that includes it by path gets a block that works.
//
// No fieldset id or heading: this is a read-only block embedded inside other pages, so
// it must not read or write per-user field display settings in the form table.

echo module_form::generate_fieldset( array(
	'elements' => array(
		array(
			'title'  => 'Contact Name',
			'fields' => array(
				htmlspecialchars( (string) $user_data['name'] )
				. ' <a href="' . htmlspecialchars( module_user::link_open_contact( $user_id ) ) . '">&raquo;</a>',
			),
		),
		array(
			'title'  => 'Phone',
			// get_user() only returns phone_ext for staff, so the extension is appended
			// only when there is one. The original read the key unguarded and would have
			// warned on every contact, which nothing noticed while nothing rendered this.
			'fields' => array(
				htmlspecialchars( (string) $user_data['phone'] )
				. ( ! empty( $user_data['phone_ext'] )
					? '&nbsp;&nbsp;<strong>' . htmlspecialchars( _l( 'Ext.' ) ) . ' '
					  . htmlspecialchars( (string) $user_data['phone_ext'] ) . '</strong>'
					: '' ),
			),
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
	),
) );
