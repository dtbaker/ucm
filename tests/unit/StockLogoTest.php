<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ucm_is_stock_logo_url() decides whether a stored logo setting may be replaced by the
 * logo this release ships. Getting it wrong in one direction leaves upgraded installs on the
 * old logo; in the other it throws away a logo somebody chose. These pin both directions.
 */
class StockLogoTest extends TestCase
{
	#[DataProvider( 'stock_values' )]
	public function test_the_stock_value_is_recognised( $stored, $base_href, $host ) {
		$this->assertTrue( ucm_is_stock_logo_url( $stored, $base_href, $host ) );
	}

	public static function stock_values() {
		return array(
			'root install, path only'         => array( '/images/logo.png', '/', 'example.com' ),
			'subfolder install, path only'    => array( '/ucm/images/logo.png', '/ucm/', 'example.com' ),
			'full URL on this host'           => array( 'https://example.com/ucm/images/logo.png', '/ucm/', 'example.com' ),
			'host differs only in case'       => array( 'https://Example.COM/images/logo.png', '/', 'example.com' ),
			'base href is a full URL'         => array( 'https://example.com/ucm/images/logo.png', 'https://example.com/ucm/', '' ),
			'surrounding whitespace'          => array( "  /images/logo.png\n", '/', 'example.com' ),
		);
	}

	#[DataProvider( 'custom_values' )]
	public function test_a_chosen_logo_is_left_alone( $stored, $base_href, $host ) {
		$this->assertFalse( ucm_is_stock_logo_url( $stored, $base_href, $host ) );
	}

	public static function custom_values() {
		return array(
			'an uploaded logo'                   => array( '/ucm/includes/plugin_file/upload/my-logo.png', '/ucm/', 'example.com' ),
			'same file name on another host'     => array( 'https://cdn.example.net/images/logo.png', '/', 'example.com' ),
			'stock path but a different folder'  => array( '/images/logo.png', '/ucm/', 'example.com' ),
			'the old gif'                        => array( '/images/logo.gif', '/', 'example.com' ),
			'a deliberately empty setting'       => array( '', '/', 'example.com' ),
			'not a string'                       => array( null, '/', 'example.com' ),
		);
	}
}
