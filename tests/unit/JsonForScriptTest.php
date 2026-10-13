<?php

use PHPUnit\Framework\TestCase;

/**
 * ucm_json_for_script() exists because json_encode() is only safe in a JSON response, not
 * inline in a page. By default it leaves `<` and `>` alone, so a stored value containing
 * `</script>` ends the block early and everything after it is parsed as markup - which is
 * script injection through a function that reads like it is already doing the escaping.
 */
class JsonForScriptTest extends TestCase
{
	public function test_a_closing_script_tag_cannot_end_the_block() {
		$out = ucm_json_for_script( array( 'title' => '</script><img src=x onerror=alert(1)>' ) );

		$this->assertStringNotContainsString( '</script', $out );
		$this->assertStringNotContainsString( '<', $out );
		$this->assertStringNotContainsString( '>', $out );
	}

	public function test_the_value_survives_the_round_trip() {
		$value = array(
			'title'  => '</script> & "quoted" \'single\'',
			'nested' => array( 'a' => 1, 'b' => array( true, null, 2.5 ) ),
		);

		$this->assertSame( $value, json_decode( ucm_json_for_script( $value ), true ) );
	}

	public function test_ampersand_and_quotes_are_escaped_too() {
		$out = ucm_json_for_script( 'a & b "c" \'d\'' );

		$this->assertStringNotContainsString( '&', $out );
		$this->assertStringNotContainsString( "'", $out );
		// The only quotes left are the pair JSON wraps the string in.
		$this->assertSame( 2, substr_count( $out, '"' ) );
	}

	public function test_plain_values_encode_as_json_still() {
		$this->assertSame( '123', ucm_json_for_script( 123 ) );
		$this->assertSame( 'true', ucm_json_for_script( true ) );
		$this->assertSame( 'null', ucm_json_for_script( null ) );
		$this->assertSame( '[1,2,3]', ucm_json_for_script( array( 1, 2, 3 ) ) );
	}
}
