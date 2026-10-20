<?php

use PHPUnit\Framework\TestCase;

/**
 * How passwords are stored: the algorithm, the salt, when a hash is replaced, and what
 * happens to the old unsalted MD5 passwords.
 *
 * PasswordVerifyTest covers who may sign in. This covers the hashes themselves.
 */
class PasswordHashingTest extends TestCase {

	private function argon2id_is_available() {
		return in_array( 'argon2id', password_algos(), true );
	}

	public function test_every_hash_is_salted() {
		$first  = ucm_password_hash( 'correct horse battery staple' );
		$second = ucm_password_hash( 'correct horse battery staple' );

		$this->assertNotSame( $first, $second, 'the same password must not give the same hash twice' );
		$this->assertTrue( ucm_password_verify( 'correct horse battery staple', $first ) );
		$this->assertTrue( ucm_password_verify( 'correct horse battery staple', $second ) );
	}

	public function test_new_hashes_are_argon2id_where_php_has_it_and_bcrypt_where_not() {
		$hash = ucm_password_hash( 'a password' );

		if ( $this->argon2id_is_available() ) {
			$this->assertStringStartsWith( '$argon2id$', $hash );
			$info = password_get_info( $hash );
			$this->assertSame( 47104, $info['options']['memory_cost'], 'OWASP: 46 MiB' );
			$this->assertSame( 1, $info['options']['time_cost'] );
		} else {
			$this->assertStringStartsWith( '$2y$12$', $hash );
		}
	}

	public function test_bcrypt_can_be_kept_for_a_host_that_may_move_its_database() {
		// The constant is read on every call, so it cannot be defined here without leaking
		// into other tests. Check the algorithm list the fallback depends on instead.
		$this->assertContains( ucm_password_algorithm(), array( 'argon2id', '2y' ) );
		$this->assertSame( array( 'cost' => 12 ), ucm_password_options( '2y' ) );
	}

	public function test_a_password_longer_than_bcrypt_can_use_is_checked_to_the_end() {
		if ( ! $this->argon2id_is_available() ) {
			$this->markTestSkipped( 'this PHP has no Argon2id, so new hashes are bcrypt, which stops at 72 bytes' );
		}
		$long = str_repeat( 'a', 80 );
		$hash = ucm_password_hash( $long );

		$this->assertTrue( ucm_password_verify( $long, $hash ) );
		$this->assertFalse(
			ucm_password_verify( str_repeat( 'a', 72 ) . 'something else entirely', $hash ),
			'bcrypt ignores everything after byte 72, so this would have verified before'
		);
	}

	public function test_the_hash_is_not_accepted_as_the_password() {
		$hash = ucm_password_hash( 'secret' );

		$this->assertFalse( ucm_password_verify( $hash, $hash ) );
	}

	// ---- when a stored hash is replaced ----

	public function test_a_hash_made_with_the_current_settings_is_left_alone() {
		$this->assertFalse( ucm_password_needs_rehash( ucm_password_hash( 'x' ) ) );
	}

	public function test_a_weaker_bcrypt_hash_is_replaced() {
		$old = password_hash( 'x', '2y', array( 'cost' => 10 ) );

		$this->assertTrue( ucm_password_needs_rehash( $old ), 'PHP before 8.4 made cost 10 hashes' );
		$this->assertTrue( ucm_password_verify( 'x', $old ), 'and they must still sign in until then' );
	}

	public function test_a_bcrypt_hash_moves_to_argon2id_where_available() {
		$bcrypt = password_hash( 'x', '2y', array( 'cost' => 12 ) );

		$this->assertSame( $this->argon2id_is_available(), ucm_password_needs_rehash( $bcrypt ) );
	}

	public function test_an_argon2id_hash_with_weaker_settings_is_replaced() {
		if ( ! $this->argon2id_is_available() ) {
			$this->markTestSkipped( 'no Argon2id in this PHP' );
		}
		$weak = password_hash( 'x', 'argon2id', array( 'memory_cost' => 1024, 'time_cost' => 1, 'threads' => 1 ) );

		$this->assertTrue( ucm_password_needs_rehash( $weak ) );
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

	public function test_a_wrapped_md5_is_salted_and_no_longer_an_md5() {
		$md5 = md5( 'old password' );
		$one = ucm_password_wrap_legacy( $md5 );
		$two = ucm_password_wrap_legacy( $md5 );

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

	public function test_a_hash_that_only_contains_the_algorithm_name_is_not_one() {
		// The old check accepted '$argon2' anywhere in the value.
		$this->assertFalse( ucm_password_is_native_hash( 'x$argon2id$y' ) );
		$this->assertFalse( ucm_password_verify( 'anything', 'x$argon2id$y' ) );
		$this->assertTrue( ucm_password_is_native_hash( password_hash( 'x', '2y' ) ) );
	}

	public function test_uppercase_hex_is_still_an_md5() {
		$this->assertTrue( ucm_password_verify( 'old password', strtoupper( md5( 'old password' ) ) ) );
	}
}
