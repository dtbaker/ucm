<?php

use PHPUnit\Framework\TestCase;

/**
 * ucm_form_element_ids(), which is how generate_fieldset() keys the elements whose
 * caller did not name one.
 *
 * The id is also what a fieldset stores so a role can be told to hide a field, so it has
 * to be two things at once. Unique, because the fieldset renders one element per key and
 * two titles landing on the same id meant one of the two fields silently never appeared -
 * that is what happened to "Additional Charge (as %)" and "Additional Charge (as $)" on
 * the payment settings pages. And stable, because an id already stored against a role
 * must not move when the fields are rendered in a different order.
 */
class FormElementIdTest extends TestCase
{
	private const PERCENT = 'Additional Charge (as %)';
	private const DOLLAR  = 'Additional Charge (as $)';

	public function testTheIdIsTheTitleWithoutPunctuation() {
		$ids = ucm_form_element_ids( array( 0 => 'Invoice Prefix' ) );

		$this->assertSame( array( 0 => 'InvoicePrefix' ), $ids );
	}

	public function testTitlesThatDifferOnlyByPunctuationGetDifferentIds() {
		$ids = ucm_form_element_ids( array( 0 => self::PERCENT, 1 => self::DOLLAR ) );

		$this->assertNotSame( $ids[0], $ids[1], 'one of the two fields would not be rendered' );
		foreach ( $ids as $id ) {
			$this->assertStringStartsWith( 'AdditionalChargeas', $id );
		}
	}

	public function testAnIdDoesNotMoveBecauseTheOrderChanged() {
		$one_way   = ucm_form_element_ids( array( 'a' => self::PERCENT, 'b' => self::DOLLAR ) );
		$the_other = ucm_form_element_ids( array( 'b' => self::DOLLAR, 'a' => self::PERCENT ) );

		$this->assertSame( $one_way['a'], $the_other['a'] );
		$this->assertSame( $one_way['b'], $the_other['b'] );
	}

	public function testATitleThatCollidesWithNothingKeepsTheIdItAlwaysHad() {
		// The id scheme is stored, so anything that was not already broken has to come
		// out of this unchanged.
		$titles = array( 'Invoice Prefix', 'Due Date', 'Tax Name', 'Additional Charge (as %)' );
		foreach ( ucm_form_element_ids( $titles ) as $key => $id ) {
			$this->assertSame( preg_replace( '#[^a-zA-Z0-9]#', '', $titles[ $key ] ), $id );
		}
	}

	public function testTheSameTitleTwiceStillCollapses() {
		// Two identical titles are the same field rendered twice, which has always
		// collapsed to one element and should keep doing so.
		$ids = ucm_form_element_ids( array( 0 => 'Invoice Prefix', 1 => 'Invoice Prefix' ) );

		$this->assertSame( $ids[0], $ids[1] );
	}

	public function testTitlesOfNothingButPunctuationStillGetUniqueIds() {
		$ids = ucm_form_element_ids( array( 0 => '%', 1 => '$' ) );

		$this->assertNotSame( $ids[0], $ids[1] );
	}

	public function testAnythingThatIsNotAnArrayGivesNothing() {
		$this->assertSame( array(), ucm_form_element_ids( false ) );
		$this->assertSame( array(), ucm_form_element_ids( 'Invoice Prefix' ) );
		$this->assertSame( array(), ucm_form_element_ids( array() ) );
	}
}
