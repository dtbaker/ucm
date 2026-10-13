<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Regression tests for ucm_password_verify().
 *
 * The important one is testStoredHashIsNotAcceptedAsThePassword: the old code accepted
 * the stored MD5 as the password itself, so anything that could read the user table
 * could log in as any user.
 */
class PasswordVerifyTest extends TestCase
{
	public function testStoredMd5HashIsNotAcceptedAsThePassword() {
		$password = 'correct horse battery staple';
		$hash     = md5( $password );

		$this->assertTrue( ucm_password_verify( $password, $hash ), 'the real password should still work' );
		$this->assertFalse( ucm_password_verify( $hash, $hash ), 'pass-the-hash must be rejected' );
	}

	public function testStoredBcryptHashIsNotAcceptedAsThePassword() {
		$password = 'correct horse battery staple';
		$hash     = ucm_password_hash( $password );

		$this->assertTrue( ucm_password_verify( $password, $hash ) );
		$this->assertFalse( ucm_password_verify( $hash, $hash ) );
	}

	public function testWrongPasswordIsRejected() {
		$this->assertFalse( ucm_password_verify( 'nope', md5( 'yes' ) ) );
		$this->assertFalse( ucm_password_verify( 'nope', ucm_password_hash( 'yes' ) ) );
	}

	#[DataProvider( 'emptyHashProvider' )]
	public function testEmptyOrMissingHashNeverVerifies( $hash ) {
		$this->assertFalse( ucm_password_verify( 'anything', $hash ) );
		$this->assertFalse( ucm_password_verify( '', $hash ) );
	}

	public static function emptyHashProvider() {
		return array(
			'empty string' => array( '' ),
			'null'         => array( null ),
			'zero'         => array( 0 ),
			'false'        => array( false ),
		);
	}

	public function testUnrecognisedHashFormatIsRejected() {
		// Not bcrypt, not 32 hex chars - nothing should match it.
		$this->assertFalse( ucm_password_verify( 'sha1hash', sha1( 'sha1hash' ) ) );
		$this->assertFalse( ucm_password_verify( 'plaintext', 'plaintext', 1 ) );
	}

	public function testDefaultPasswordWorksForTheAdministratorOnly() {
		// A fresh install stores the literal string for user 1 so the first login works
		// before anybody has set a password.
		$this->assertTrue( ucm_password_verify( 'password', 'password', 1 ) );
		$this->assertFalse( ucm_password_verify( 'password2', 'password', 1 ) );

		// The sample contact, user and staff accounts ship with the same value in older
		// databases, and nobody is meant to sign in as them.
		foreach ( array( 2, 3, 4, 5, 99 ) as $user_id ) {
			$this->assertFalse(
				ucm_password_verify( 'password', 'password', $user_id ),
				"user $user_id should not be able to log in with the shipped default"
			);
		}
	}

	public function testDefaultPasswordNeedsAnAccountToBeNamed() {
		// A caller that does not say which account it is checking gets no default-password
		// path, so the lax branch cannot be reached by accident from new code.
		$this->assertFalse( ucm_password_verify( 'password', 'password' ) );
		$this->assertFalse( ucm_password_verify( 'password', 'password', null ) );
	}

	public function testTheDefaultPasswordIsRecognisable() {
		$this->assertTrue( ucm_password_is_default( 'password' ) );
		$this->assertFalse( ucm_password_is_default( ucm_password_hash( 'password' ) ) );
		$this->assertFalse( ucm_password_is_default( md5( 'password' ) ) );
		$this->assertFalse( ucm_password_is_default( '' ) );
		$this->assertFalse( ucm_password_is_default( null ) );
	}

	public function testLegacyMd5HashIsFlaggedForRehash() {
		$this->assertTrue( ucm_password_needs_rehash( md5( 'x' ) ) );
		$this->assertFalse( ucm_password_needs_rehash( ucm_password_hash( 'x' ) ) );
		$this->assertFalse( ucm_password_needs_rehash( '' ) );
	}
}
