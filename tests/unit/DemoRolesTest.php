<?php

use PHPUnit\Framework\TestCase;

/**
 * The permissions the demo's staff and customer logins are given, in custom/demo/roles.php.
 *
 * It is an allowlist of view access, and these tests are what keep it that way: a login on
 * the public demo is open to anyone, so nothing in it may grant more than looking, or reach
 * the administrator's settings, however the list is edited later.
 */
class DemoRolesTest extends TestCase {

	private function roles() {
		return require dirname( __DIR__, 2 ) . '/custom/demo/roles.php';
	}

	public function test_both_demo_roles_are_defined_and_can_sign_in() {
		$roles = $this->roles();

		foreach ( array( 'Demo Staff', 'Demo Customer' ) as $role ) {
			$this->assertArrayHasKey( $role, $roles );
			$this->assertContains( 'Can User Login', $roles[ $role ]['User Specific'], "$role has to be able to sign in" );
		}
	}

	public function test_every_entry_is_a_category_with_a_list_of_distinct_names() {
		foreach ( $this->roles() as $role => $categories ) {
			foreach ( $categories as $category => $names ) {
				$this->assertIsString( $category );
				$this->assertNotEmpty( $names, "$role / $category lists nothing" );
				$this->assertSame( array_values( array_unique( $names ) ), array_values( $names ), "$role / $category lists a name twice" );
				foreach ( $names as $name ) {
					$this->assertIsString( $name );
					$this->assertNotSame( '', trim( $name ) );
				}
			}
		}
	}

	public function test_no_demo_role_reaches_the_administrators_areas() {
		// Whole categories that hold settings, security, backups, money, reports and bulk
		// import and export. Config is checked separately, below.
		$forbidden = array( 'Security', 'Backup', 'Finance', 'Statistic', 'Newsletter', 'Log', 'Encrypt', 'Template', 'Language', 'Group', 'Subscription', 'Member' );

		foreach ( $this->roles() as $role => $categories ) {
			foreach ( $forbidden as $category ) {
				$this->assertArrayNotHasKey( $category, $categories, "$role must not be given $category permissions" );
			}
			foreach ( $categories as $category => $names ) {
				foreach ( $names as $name ) {
					$this->assertDoesNotMatchRegularExpression( '/^(Import|Export) /', $name, "$role / $name moves data in or out in bulk" );
					$this->assertDoesNotMatchRegularExpression( '/Settings|Config$|Advanced|Passwords|Permissions/', $name, "$role / $name is an administrator's setting" );
				}
			}
		}
	}

	public function test_the_only_thing_under_config_is_viewing_users() {
		// Config holds every setting. Users is what opens a person's own profile page, and
		// the "Only My Account" permission narrows it to that one account.
		foreach ( $this->roles() as $role => $categories ) {
			if ( isset( $categories['Config'] ) ) {
				$this->assertSame( array( 'Users' ), $categories['Config'], "$role is given more than viewing users under Config" );
				$this->assertContains( 'Only My Account', $categories['User Account Access'], "$role could see every user, not just its own" );
			}
		}
	}

	public function test_the_staff_role_sees_only_the_all_option_of_each_data_access_category() {
		// The "Data Access" categories are choices, and the app takes the first one it finds.
		// Granting a narrower one next to "All" would make which applies a matter of order.
		foreach ( $this->roles()['Demo Staff'] as $category => $names ) {
			if ( $this->is_data_access( $category ) ) {
				$this->assertCount( 1, $names, "Demo Staff / $category grants more than one option" );
				$this->assertMatchesRegularExpression( '/^All /', $names[0], "Demo Staff / $category is not the 'All' option" );
			}
		}
	}

	public function test_the_customer_role_sees_only_what_is_scoped_to_its_own_customer() {
		// The customer login is open to anyone, and is a contact of one made-up customer. Every
		// data access it has must be an option the app narrows to that customer, one option each,
		// and never "All ...", or it would see every other customer's invoices, jobs and tickets.
		$scoped = array(
			'Customer Data Access' => 'Only customer I am assigned to as a contact',
			'Invoice Data Access'  => 'Invoices from customers I have access to',
			'Job Data Access'      => 'Jobs from customers I have access to',
			'Quote Data Access'    => 'Quotes from customers I have access to',
			'Ticket Access'        => 'Only tickets from my customer account',
			'File Data Access'     => 'Only files from customers I have access to',
			// A task is only reached through a job or quote, which are already narrowed.
			'Job Task Data Access'   => 'All tasks within a job',
			'Quote Task Data Access' => 'All tasks within a quote',
		);
		$role   = $this->roles()['Demo Customer'];

		foreach ( $role as $category => $names ) {
			if ( ! $this->is_data_access( $category ) ) {
				continue;
			}
			$this->assertArrayHasKey( $category, $scoped, "Demo Customer has a data access category, $category, this test does not know is scoped to the customer" );
			$this->assertSame( array( $scoped[ $category ] ), $names, "Demo Customer / $category is not the option scoped to the customer" );
		}
		// Each record type it can open is narrowed: no list without its data access.
		foreach ( array( 'Customer', 'Invoice', 'Job', 'Quote', 'Ticket', 'File' ) as $module ) {
			$this->assertArrayHasKey( $module, $role, "Demo Customer should be able to view its $module records" );
			$access = 'Ticket' === $module ? 'Ticket Access' : $module . ' Data Access';
			$this->assertArrayHasKey( $access, $role, "Demo Customer can open $module records with nothing narrowing them to its own" );
		}
	}

	public function test_the_customer_role_is_limited_to_what_a_portal_contact_looks_at() {
		// Allowlist of categories. Anything internal to the agency (notes, timers, the calendar,
		// vendors, suppliers, leads, finance) is a category this does not list, so adding one
		// to the role fails here and has to be looked at.
		$allowed = array(
			'Customer', 'Customer Data Access', 'Invoice', 'Invoice Data Access', 'Job', 'Job Data Access',
			'Job Task Data Access', 'Quote', 'Quote Data Access', 'Quote Task Data Access', 'Ticket',
			'Ticket Access', 'File', 'File Data Access', 'Website', 'User Specific',
		);
		$role    = $this->roles()['Demo Customer'];

		$this->assertSame( array(), array_values( array_diff( array_keys( $role ), $allowed ) ), 'Demo Customer is given a category a portal contact does not need' );
		$this->assertSame( array( 'Can User Login' ), $role['User Specific'], 'Demo Customer is given more than signing in under User Specific' );
		// Notes on a customer, an invoice, a job or a ticket are the agency's own.
		foreach ( $role as $category => $names ) {
			foreach ( $names as $name ) {
				$this->assertDoesNotMatchRegularExpression( '/Notes|Groups|Advanced|Timers|Leads|Suppliers|^All Customer Contacts$/', $name, "Demo Customer / $name is internal to the agency" );
			}
		}
		$this->assertSame( array( 'Customers', 'Contacts' ), $role['Customer'], 'Demo Customer is given more than its customer and its contacts' );
	}

	private function is_data_access( $category ) {
		return substr( $category, -11 ) === 'Data Access' || 'Ticket Access' === $category;
	}
}
