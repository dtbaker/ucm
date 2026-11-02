<?php

use PHPUnit\Framework\TestCase;

/**
 * Values read back from the database, the cache and temp files are untrusted: a bare
 * unserialize() will instantiate any class that is loaded. Everything UCM stores this way is
 * an array or a scalar, so every call in the hardened plugins passes allowed_classes => false.
 */
class StoredUnserializeTest extends TestCase
{
	private const ROOT_FILES = array( 'ext.php' );

	private const PLUGIN_DIRS = array(
		'includes/plugin_cache',
		'includes/plugin_data',
		'includes/plugin_extra',
		'includes/plugin_ticket',
	);

	/** @return string[] */
	private function product_files() {
		$root  = dirname( __DIR__, 2 ) . '/';
		$files = array();
		foreach ( self::ROOT_FILES as $file ) {
			$files[] = $root . $file;
		}
		foreach ( self::PLUGIN_DIRS as $dir ) {
			$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . $dir, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $iterator as $info ) {
				// Bundled third party code keeps its own rules.
				if ( 'php' === $info->getExtension() && false === strpos( $info->getPathname(), '/vendor/' ) ) {
					$files[] = $info->getPathname();
				}
			}
		}

		return $files;
	}

	/**
	 * Every unserialize() call in $code that lacks an allowed_classes option.
	 *
	 * @return int[] Line numbers.
	 */
	private function unsafe_calls( $code ) {
		$tokens = token_get_all( $code );
		$bad    = array();
		$count  = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( ! is_array( $tokens[ $i ] ) || T_STRING !== $tokens[ $i ][0] || 'unserialize' !== strtolower( $tokens[ $i ][1] ) ) {
				continue;
			}
			// Skip "function unserialize" and "->unserialize" style declarations and methods.
			$prev = $i - 1;
			while ( $prev >= 0 && is_array( $tokens[ $prev ] ) && T_WHITESPACE === $tokens[ $prev ][0] ) {
				$prev--;
			}
			if ( $prev >= 0 && is_array( $tokens[ $prev ] ) && in_array( $tokens[ $prev ][0], array( T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
				continue;
			}
			$next = $i + 1;
			while ( $next < $count && is_array( $tokens[ $next ] ) && T_WHITESPACE === $tokens[ $next ][0] ) {
				$next++;
			}
			if ( '(' !== $tokens[ $next ] ) {
				continue;
			}
			$depth = 0;
			$found = false;
			for ( $j = $next; $j < $count; $j++ ) {
				$t = $tokens[ $j ];
				if ( '(' === $t ) {
					$depth++;
				} elseif ( ')' === $t ) {
					$depth--;
					if ( 0 === $depth ) {
						break;
					}
				} elseif ( is_array( $t ) && T_CONSTANT_ENCAPSED_STRING === $t[0] && false !== strpos( $t[1], 'allowed_classes' ) ) {
					$found = true;
				}
			}
			if ( ! $found ) {
				$bad[] = $tokens[ $i ][2];
			}
		}

		return $bad;
	}

	public function test_every_unserialize_in_the_hardened_plugins_refuses_objects() {
		$problems = array();
		foreach ( $this->product_files() as $file ) {
			foreach ( $this->unsafe_calls( file_get_contents( $file ) ) as $line ) {
				$problems[] = basename( $file ) . ':' . $line;
			}
		}
		$this->assertSame( array(), $problems );
	}

	public function test_the_scanner_catches_a_bare_call() {
		$this->assertSame( array( 2 ), $this->unsafe_calls( "<?php\n\$a = unserialize( \$x );\n" ) );
		$this->assertSame( array( 2 ), $this->unsafe_calls( "<?php\n\$a = @unserialize( file_get_contents( \$f ) );\n" ) );
		$this->assertSame( array(), $this->unsafe_calls( "<?php\n\$a = unserialize( \$x, array( 'allowed_classes' => false ) );\n" ) );
	}

	public function test_stored_arrays_and_scalars_still_load() {
		$options = array( 'allowed_classes' => false );
		$array   = array( 'a' => 1, 'nested' => array( 'file' => 'x.pdf', 'name' => 'X' ), 2 => 'two' );
		$this->assertSame( $array, unserialize( serialize( $array ), $options ) );
		$this->assertSame( 'text', unserialize( serialize( 'text' ), $options ) );
		$this->assertSame( 12, unserialize( serialize( 12 ), $options ) );
		$this->assertSame( false, unserialize( serialize( false ), $options ) );
	}

	public function test_data_written_by_older_versions_still_reads() {
		// Literal strings as an older release stored them (cache envelope, file field, header cache).
		$cache = 'a:3:{s:1:"d";a:1:{i:0;s:3:"abc";}s:6:"create";i:1700000000;s:1:"e";i:1700003600;}';
		$data  = unserialize( $cache, array( 'allowed_classes' => false ) );
		$this->assertSame( array( 'abc' ), $data['d'] );
		$this->assertSame( 1700003600, $data['e'] );

		$file = 'a:2:{s:4:"file";s:12:"upload-1.pdf";s:4:"name";s:7:"Doc.pdf";}';
		$this->assertSame( array( 'file' => 'upload-1.pdf', 'name' => 'Doc.pdf' ), unserialize( $file, array( 'allowed_classes' => false ) ) );
	}

	public function test_an_object_payload_is_never_constructed() {
		$result = unserialize( serialize( new ArrayObject( array( 1 ) ) ), array( 'allowed_classes' => false ) );
		$this->assertInstanceOf( '__PHP_Incomplete_Class', $result );
		$this->assertNotInstanceOf( 'ArrayObject', $result );
	}
}
