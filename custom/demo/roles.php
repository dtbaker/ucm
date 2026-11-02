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
		// "Vendors", and its contacts and notes, are the demo's own customer type for suppliers.
		// The old Vendors plugin, which is to be merged into Customers, is not given: see
		// roles-retired.php.
		'Customer'              => array( 'Customers', 'Customer', 'Contacts', 'All Customer Contacts', 'All Customer Notes', 'Customer Groups', 'Leads', 'Suppliers', 'Vendors', 'All Vendor Contacts', 'All Vendor Notes' ),
		'Customer Data Access'  => array( 'All customers in system' ),
		// Config > Users is what opens a user's page, including a staff member's own profile.
		// With "Only My Account" that is the only account they are shown.
		'Config'                => array( 'Users' ),
		'User Account Access'   => array( 'Only My Account' ),
		'Job'                   => array( 'Jobs', 'Job Tasks', 'Job Notes', 'Job Groups' ),
		'Job Data Access'       => array( 'All jobs in system' ),
		'Job Task Data Access'  => array( 'All tasks within a job' ),
		'Quote'                 => array( 'Quotes', 'Quote Tasks', 'Quote Notes' ),
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

	// A contact of the one customer it is linked to, who looks at that customer's own work:
	// invoices, jobs, quotes, tickets and files. Every Data Access category is the option
	// that is scoped to the customer, never "All ...", so the other customers' records are not
	// there to be seen, and it is view access like the rest. Nothing internal is listed: no
	// notes, no timers, no calendar, no vendors, suppliers or leads.
	'Demo Customer' => array(
		'Customer'             => array( 'Customers', 'Contacts' ),
		'Customer Data Access' => array( 'Only customer I am assigned to as a contact' ),
		'Invoice'              => array( 'Invoices' ),
		'Invoice Data Access'  => array( 'Invoices from customers I have access to' ),
		'Job'                  => array( 'Jobs', 'Job Tasks' ),
		'Job Data Access'      => array( 'Jobs from customers I have access to' ),
		'Job Task Data Access' => array( 'All tasks within a job' ),
		'Quote'                => array( 'Quotes', 'Quote Tasks' ),
		'Quote Data Access'    => array( 'Quotes from customers I have access to' ),
		'Quote Task Data Access' => array( 'All tasks within a quote' ),
		'Ticket'               => array( 'Tickets' ),
		'Ticket Access'        => array( 'Only tickets from my customer account' ),
		'File'                 => array( 'Files' ),
		'File Data Access'     => array( 'Only files from customers I have access to' ),
		'Website'              => array( 'Websites' ),
		'User Specific'        => array( 'Can User Login' ),
	),
);
