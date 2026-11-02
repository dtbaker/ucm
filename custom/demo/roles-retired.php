<?php
/**
 * What a demo role was given by an earlier version of custom/demo/roles.php, and no longer
 * should have. Read by custom/demo/reset.php, which takes these away from the role, because
 * repair only ever adds to a role and so would never notice they had gone from the list.
 *
 * The same shape as roles.php: role name => category => permission names, matched on both.
 * Anything not listed here is left alone, so a permission added by hand in the demo stays. A
 * permission may not be in both files.
 *
 * Only add to this when a permission is taken out of roles.php. Once every demo has been
 * through a repair, an entry here does nothing and can be dropped.
 *
 * @return array role name => category => permission names
 */
return array(

	// The old Vendors plugin, which says at the top of its page that it will be merged into
	// the Customers area. The demo keeps its suppliers in Customers, as a type called
	// Vendors, so staff are shown that and not an empty second Vendors menu.
	'Demo Staff' => array(
		'Vendor'             => array( 'Vendors', 'Contacts', 'All Vendor Contacts' ),
		'Vendor Data Access' => array( 'All vendors in system' ),
	),
);
