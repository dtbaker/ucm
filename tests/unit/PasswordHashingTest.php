<?php

use PHPUnit\Framework\TestCase;

/**
 * How passwords are stored: bcrypt, the salt, when a hash is replaced, and what happens to
 * the old unsalted MD5 passwords.
 *
 * PasswordVerifyTest covers who may sign in. This covers the hashes themselves.
 */
class PasswordHashingTest extends TestCase {

	/** The cost of a bcrypt hash, read from the hash itself. */
	private function cost_of( $hash ) {
		preg_match( '/^\$2[aby]\$(\d\d)\$/', $hash, $match );

		return (int) $match[1];
	}

	public function test_every_hash_is_salted() {
		$first  = ucm_password_hash( 'correct horse battery staple' );
		$second = ucm_password_hash( 'correct horse battery staple' );

		$this->assertNotSame( $first, $second, 'the same password must not give the same hash twice' );
		$this->assertTrue( ucm_password_verify( 'correct horse battery staple', $first ) );
		$this->assertTrue( ucm_password_verify( 'correct horse battery staple', $second ) );
	}

	public function test_new_hashes_are_bcrypt_at_phps_own_cost_whatever_else_php_offers() {
		// Argon2 is available on most builds, and PHP may one day change PASSWORD_DEFAULT. Neither
		// may leak into what is stored: a hash from an algorithm this host lacks cannot be checked
		// after the database moves, and the cost is left to PHP so a login is no dearer than it
		// has always been on a small shared host.
		$hash = ucm_password_hash( 'a password' );

		$this->assertStringStartsWith( '$2y$', $hash );
		$this->assertSame( PASSWORD_BCRYPT_DEFAULT_COST, $this->cost_of( $hash ) );
	}

	public function test_the_hash_is_not_accepted_as_the_password() {
		$hash = ucm_password_hash( 'secret' );

		$this->assertFalse( ucm_password_verify( $hash, $hash ) );
	}

	// ---- when a stored hash is replaced ----

	public function test_a_hash_made_with_the_current_settings_is_left_alone() {
		$this->assertFalse( ucm_password_needs_rehash( ucm_password_hash( 'x' ) ) );
	}

	public function test_a_bcrypt_hash_at_a_lower_cost_is_replaced() {
		// What a host on PHP before 8.4 made, once it moves to 8.4.
		$old = password_hash( 'x', PASSWORD_BCRYPT, array( 'cost' => PASSWORD_BCRYPT_DEFAULT_COST - 1 ) );

		$this->assertTrue( ucm_password_needs_rehash( $old ) );
		$this->assertTrue( ucm_password_verify( 'x', $old ), 'and it must still sign in until then' );
	}

	public function test_a_bcrypt_hash_is_never_replaced_by_a_weaker_one() {
		// A database restored from a host with a faster CPU holds higher costs than this one's
		// default. Rehashing those at sign in would lower them.
		$strong = password_hash( 'x', PASSWORD_BCRYPT, array( 'cost' => PASSWORD_BCRYPT_DEFAULT_COST + 1 ) );

		$this->assertFalse( ucm_password_needs_rehash( $strong ) );
	}

	public function test_a_bcrypt_hash_from_another_tool_is_judged_by_its_cost_too() {
		// $2a$ and $2b$ are the same algorithm under other names.
		$cost = str_pad( (string) ( PASSWORD_BCRYPT_DEFAULT_COST - 1 ), 2, '0', STR_PAD_LEFT );
		$this->assertTrue( ucm_password_needs_rehash( '$2b$' . $cost . '$' . str_repeat( 'a', 53 ) ) );
		$this->assertFalse( ucm_password_needs_rehash( '$2b$' . PASSWORD_BCRYPT_DEFAULT_COST . '$' . str_repeat( 'a', 53 ) ) );
	}

	public function test_an_argon2_hash_is_left_alone() {
		if ( ! in_array( 'argon2id', password_algos(), true ) ) {
			$this->markTestSkipped( 'no Argon2id in this PHP' );
		}
		// Nothing here writes one, but a database may hold one, and it must not be swapped for bcrypt.
		$argon = password_hash( 'x', 'argon2id' );

		$this->assertFalse( ucm_password_needs_rehash( $argon ) );
		$this->assertTrue( ucm_password_verify( 'x', $argon ) );
	}

	public function test_the_shipped_default_is_never_rewritten() {
		// It is the record that nobody has set a password yet, which is what makes the
		// sign in ask for one. Rewriting it as a hash would quietly clear that.
		$this->assertFalse( ucm_password_needs_rehash( 'password' ) );
		$this->assertTrue( ucm_password_is_default( 'password' ) );
	}

	public function test_nothing_to_rehash_for_an_empty_or_unrecognised_value() {
		$this->assertFalse( ucm_password_needs_rehash( '' ) );
		$this->assertFalse( ucm_password_needs_rehash( null ) );
		$this->assertFalse( ucm_password_needs_rehash( 'not a hash' ) );
	}

	// ---- the old unsalted MD5 ----

	public function test_an_old_md5_still_signs_in_and_is_marked_for_replacement() {
		$md5 = md5( 'old password' );

		$this->assertTrue( ucm_password_verify( 'old password', $md5 ) );
		$this->assertTrue( ucm_password_needs_rehash( $md5 ) );
	}

	public function test_a_wrapped_md5_signs_in_with_the_original_password() {
		$wrapped = ucm_password_wrap_legacy( md5( 'old password' ) );

		$this->assertTrue( ucm_password_is_wrapped_md5( $wrapped ) );
		$this->assertTrue( ucm_password_verify( 'old password', $wrapped ) );
		$this->assertFalse( ucm_password_verify( 'wrong password', $wrapped ) );
	}

	public function test_a_wrapped_md5_is_bcrypt_salted_and_no_longer_an_md5() {
		$md5 = md5( 'old password' );
		$one = ucm_password_wrap_legacy( $md5 );
		$two = ucm_password_wrap_legacy( $md5 );

		$this->assertStringStartsWith( 'md5+$2y$', $one );
		$this->assertNotSame( $one, $two, 'two users with the same old password must not share a hash' );
		$this->assertFalse( ucm_password_is_legacy_md5( $one ) );
		$this->assertStringNotContainsString( $md5, $one, 'the MD5 must not be recoverable by reading the value' );
		$this->assertLessThanOrEqual( 255, strlen( $one ), 'it has to fit the password column' );
	}

	public function test_a_wrapped_md5_is_replaced_by_a_plain_hash_at_the_next_sign_in() {
		$this->assertTrue( ucm_password_needs_rehash( ucm_password_wrap_legacy( md5( 'x' ) ) ) );
	}

	public function test_a_wrapped_md5_cannot_be_replayed() {
		$md5     = md5( 'old password' );
		$wrapped = ucm_password_wrap_legacy( $md5 );

		$this->assertFalse( ucm_password_verify( $md5, $wrapped ), 'typing the MD5 is not the password' );
		$this->assertFalse( ucm_password_verify( $wrapped, $wrapped ), 'nor is typing the stored value' );
	}

	public function test_a_marker_in_front_of_something_else_is_not_a_wrapped_md5() {
		$this->assertFalse( ucm_password_is_wrapped_md5( 'md5+' ) );
		$this->assertFalse( ucm_password_is_wrapped_md5( 'md5+not a hash' ) );
		$this->assertFalse( ucm_password_verify( 'md5+not a hash', 'md5+not a hash' ) );
	}

	public function test_a_value_that_only_mentions_a_hash_algorithm_is_not_a_hash() {
		// The old check accepted '$argon2' anywhere in the value.
		$this->assertFalse( ucm_password_is_native_hash( 'x$argon2id$y' ) );
		$this->assertFalse( ucm_password_verify( 'anything', 'x$argon2id$y' ) );
		$this->assertTrue( ucm_password_is_native_hash( password_hash( 'x', PASSWORD_BCRYPT ) ) );
	}

	public function test_uppercase_hex_is_still_an_md5() {
		$this->assertTrue( ucm_password_verify( 'old password', strtoupper( md5( 'old password' ) ) ) );
	}
}
