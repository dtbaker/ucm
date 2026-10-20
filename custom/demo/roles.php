<?php
/**
 * What each demo role may see. Read by custom/demo/reset.php, which makes sure the role has
 * every permission listed here.
 *
 * An allowlist, deliberately: category => permission names, matched on both, so a permission
 * the app adds later is not granted by accident. Every entry is view access: nothing here
 * grants create, edit or delete, and no setting, security role, backup, finance figure,
 * newsletter or report is listed, so those pages and menus stay the administrator's. The one
 * entry under Config, Users, is view access, narrowed to the staff member's own account.
 *
 * Repair only ever adds to a role. A permission added to a role in the demo, by hand, stays.
 *
 * The names are the app's own, as shown on Settings > Security Roles. One that is not in the
 * database yet is skipped and reported: the app registers a permission the first time a page
 * checks it, so a long-lived demo has them all.
 *
 * @return array role name => category => permission names
 */
return array(

	// A member of the agency's own team: the customers, work, quotes, invoices and tickets
	// the administrator sees, to look at.
	'Demo Staff'    => array(
		'Customer'              => array( 'Customers', 'Customer', 'Contacts', 'All Customer Contacts', 'All Customer Notes', 'Customer Groups', 'Leads', 'Suppliers' ),
		'Customer Data Access'  => array( 'All customers in system' ),
		'Vendor'                => array( 'Vendors', 'Contacts', 'All Vendor Contacts' ),
		'Vendor Data Access'    => array( 'All vendors in system' ),
		// Config > Users is what opens a user's page, including a staff member's own profile.
		// With "Only My Account" that is the only account they are shown.
		'Config'                => array( 'Users' ),
		'User Account Access'   => array( 'Only My Account' ),
		'Job'                   => array( 'Jobs', 'Job Notes', 'Job Groups' ),
		'Job Data Access'       => array( 'All jobs in system' ),
		'Job Task Data Access'  => array( 'All tasks within a job' ),
		'Quote'                 => array( 'Quotes', 'Quote Notes' ),
		'Quote Data Access'     => array( 'All quotes in system' ),
		'Quote Task Data Access' => array( 'All tasks within a quote' ),
		'Invoice'               => array( 'Invoices', 'Invoice Notes' ),
		'Invoice Data Access'   => array( 'All invoices in system' ),
		'Ticket'                => array( 'Tickets', 'Ticket Notes', 'Ticket Groups' ),
		'Ticket Access'         => array( 'All support tickets' ),
		'Timer'                 => array( 'Timers', 'Timer Notes' ),
		'Calendar'              => array( 'Calendar' ),
		'Calendar Data Access'  => array( 'All calendar entries in system' ),
		'Website'               => array( 'Websites', 'Website Notes' ),
		'File'                  => array( 'Files' ),
		'File Data Access'      => array( 'All files in system' ),
		'Contract'              => array( 'Contracts' ),
		'Contract Data Access'  => array( 'All contracts in system' ),
		'Faq'                   => array( 'FAQ' ),
		'User Specific'         => array( 'Can User Login', 'Show Dashboard Alerts', 'Show Dashboard Widgets', 'Show Dashboard Todo List', 'Show Quick Search' ),
	),

	// Sign in only, for now: the customer login needs a customer to be linked to, and demo
	// data worth seeing through it.
	'Demo Customer' => array(
		'User Specific' => array( 'Can User Login' ),
	),
);
