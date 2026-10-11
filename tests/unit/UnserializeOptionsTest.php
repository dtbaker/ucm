<?php

use PHPUnit\Framework\TestCase;

/**
 * Regression tests for ucm_unserialize_options().
 *
 * These option blobs arrive straight from the request. Handing them to a bare
 * unserialize() let an attacker instantiate any class loaded at the time, so the
 * important test here is that an object payload never comes back as an object.
 */
class UnserializeOptionsTest extends TestCase
{
	public function testObjectPayloadIsRejected() {
		$payload = base64_encode( serialize( new stdClass() ) );
		$result  = ucm_unserialize_options( $payload );

		$this->assertSame( array(), $result );
	}

	public function testObjectNestedInsideAnArrayIsNeverConstructed() {
		$payload = base64_encode( serialize( array( 'x' => new stdClass() ) ) );
		$result  = ucm_unserialize_options( $payload );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'x', $result );
		// Inert placeholder, not a real object - no constructor or destructor runs.
		$this->assertInstanceOf( '__PHP_Incomplete_Class', $result['x'] );
		$this->assertNotInstanceOf( 'stdClass', $result['x'] );
	}

	public function testObjectOfAClassThatDoesNotExistIsRejected() {
		// A real payload names a class with a destructor. The name is irrelevant: no
		// object should ever be constructed.
		$payload = base64_encode( 'O:21:"SomeGadgetChainClass":0:{}' );
		$result  = ucm_unserialize_options( $payload );

		$this->assertSame( array(), $result );
	}

	public function testNormalArrayPayloadStillDecodes() {
		$options = array(
			'id'     => 5,
			'name'   => 'Test Option',
			'nested' => array( 1, 2, 3 ),
		);
		$this->assertSame( $options, ucm_unserialize_options( base64_encode( serialize( $options ) ) ) );
	}

	public function testGarbageInputReturnsAnEmptyArray() {
		$this->assertSame( array(), ucm_unserialize_options( '' ) );
		$this->assertSame( array(), ucm_unserialize_options( null ) );
		$this->assertSame( array(), ucm_unserialize_options( array() ) );
		$this->assertSame( array(), ucm_unserialize_options( 'not base64 !!!' ) );
		$this->assertSame( array(), ucm_unserialize_options( base64_encode( 'not serialized' ) ) );
	}

	public function testNonArrayPayloadsReturnAnEmptyArray() {
		// Callers always treat the result as an array, so anything else is discarded.
		$this->assertSame( array(), ucm_unserialize_options( base64_encode( serialize( 'hello' ) ) ) );
		$this->assertSame( array(), ucm_unserialize_options( base64_encode( serialize( 42 ) ) ) );
		$this->assertSame( array(), ucm_unserialize_options( base64_encode( serialize( false ) ) ) );
		$this->assertSame( array(), ucm_unserialize_options( base64_encode( serialize( null ) ) ) );
	}
}
