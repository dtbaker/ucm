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

	public function test_a_demo_role_sees_only_the_all_option_of_each_data_access_category() {
		// The "Data Access" categories are choices, and the app takes the first one it finds.
		// Granting a narrower one next to "All" would make which applies a matter of order.
		foreach ( $this->roles() as $role => $categories ) {
			foreach ( $categories as $category => $names ) {
				if ( substr( $category, -11 ) === 'Data Access' || $category === 'Ticket Access' ) {
					$this->assertCount( 1, $names, "$role / $category grants more than one option" );
					$this->assertMatchesRegularExpression( '/^All /', $names[0], "$role / $category is not the 'All' option" );
				}
			}
		}
	}
}
