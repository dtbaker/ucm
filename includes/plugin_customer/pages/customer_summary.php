<?php

// Rendered by module_customer::print_customer_summary( $customer_id, 'full' ).
//
// Nothing in the tree calls that method, so this partial is not reachable through the UI
// and cannot be covered by a spec; scripts/render-partials.php renders it instead.
//
// No fieldset id or heading: this is a read-only block embedded inside other pages, so it
// must not read or write per-user field display settings in the form table.

echo module_form::generate_fieldset( array(
	'elements' => array(
		array(
			'title'  => 'Customer Name',
			'fields' => array(
				htmlspecialchars( (string) $customer_data['customer_name'] )
				. ' <a href="' . htmlspecialchars( module_customer::link_open( $customer_id ) ) . '">&raquo;</a>',
			),
		),
	),
) );
