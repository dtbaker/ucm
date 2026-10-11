<?php

use PHPUnit\Framework\TestCase;

/**
 * db_identifier() and db_order_by() exist because db_escape() was being used on table and
 * column names. mysqli_real_escape_string() does not escape a backtick, so that idiom
 * reads as safe while doing nothing at all for an identifier.
 */
class DbIdentifierTest extends TestCase
{
	public function test_a_plain_name_is_unchanged() {
		$this->assertSame( 'customer_name', db_identifier( 'customer_name' ) );
		$this->assertSame( 'Field2', db_identifier( 'Field2' ) );
	}

	public function test_a_backtick_cannot_escape_the_quoting() {
		$this->assertSame( 'name', db_identifier( '`name`' ) );
		$this->assertSame( 'nameFROMuser', db_identifier( 'name` FROM `user' ) );
	}

	public function test_sql_punctuation_is_removed() {
		$this->assertSame( 'nameDROPTABLEuser', db_identifier( 'name; DROP TABLE user; --' ) );
		$this->assertSame( 'nameOR11', db_identifier( "name' OR '1'='1" ) );
		$this->assertSame( 'userpassword', db_identifier( 'user.password' ) );
		$this->assertSame( '', db_identifier( '*' ) );
	}

	public function test_nothing_usable_gives_an_empty_name() {
		// Empty backticks are a syntax error, which is the failure we want.
		$this->assertSame( '', db_identifier( '' ) );
		$this->assertSame( '', db_identifier( '---' ) );
		$this->assertSame( '', db_identifier( array( 'name' ) ) );
	}

	public function test_an_order_by_column_is_quoted() {
		$this->assertSame( '`name`', db_order_by( 'name' ) );
		// Reserved words like `order` and `key` arrive already quoted.
		$this->assertSame( '`order`', db_order_by( '`order`' ) );
		$this->assertSame( '`key`', db_order_by( '`key`' ) );
		$this->assertSame( '`email_id` DESC', db_order_by( 'email_id DESC' ) );
		$this->assertSame( '`email_id` ASC', db_order_by( 'email_id asc' ) );
	}

	public function test_an_order_by_with_anything_else_is_refused() {
		$this->assertSame( '', db_order_by( 'name; DROP TABLE user' ) );
		$this->assertSame( '', db_order_by( '(SELECT 1)' ) );
		$this->assertSame( '', db_order_by( 'name DESC, password ASC' ) );
		$this->assertSame( '', db_order_by( 'name SIDEWAYS' ) );
		$this->assertSame( '', db_order_by( 'rand()' ) );
		$this->assertSame( '', db_order_by( '`name` FROM `user' ) );
		$this->assertSame( '', db_order_by( 'name`' ) );
		$this->assertSame( '', db_order_by( '' ) );
		$this->assertSame( '', db_order_by( false ) );
	}

	public function test_an_order_by_can_be_limited_to_known_columns() {
		$columns = array( 'name', 'email_id' );
		$this->assertSame( '`name`', db_order_by( 'name', $columns ) );
		$this->assertSame( '`email_id` DESC', db_order_by( 'email_id DESC', $columns ) );
		$this->assertSame( '', db_order_by( 'password', $columns ) );
	}
}
