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
		'includes/plugin_email',
		'includes/plugin_extra',
		'includes/plugin_ticket',
		'includes/plugin_newsletter',
		'includes/plugin_security',
		'includes/plugin_invoice',
		'includes/plugin_template',
		'includes/plugin_job',
		'includes/plugin_finance',
		'includes/plugin_quote',
		'includes/plugin_pin',
		'includes/plugin_subscription',
		'includes/plugin_user',
		'includes/plugin_paymethod_banktransfer',
		'includes/plugin_paymethod_check',
		'includes/plugin_paymethod_other',
		'includes/plugin_paymethod_paypal',
		'includes/plugin_paymethod_square',
		'includes/plugin_file',
		'includes/plugin_form',
		'includes/plugin_note',
		'includes/plugin_calendar',
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
				$path = $info->getPathname();
				if ( 'php' === $info->getExtension() && false === strpos( $path, '/vendor/' ) && false === strpos( $path, '/htmlpurifier/' ) ) {
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

	public function test_values_stored_by_the_plugins_read_back_identically() {
		$options = array( 'allowed_classes' => false );
		$stored  = array(
			// invoice_payment.data: gateway log lines and ids, as module_invoice::add_payment_data() writes them.
			'payment data'        => array( 'log' => array( 'Payment recorded from stripe transaction pi_1.', 'Worth a look: late.' ), 'paypal_order_id' => array( '5O190127TN364715T' ), 'custom_notes' => 'Paid by cheque' ),
			// security_permission.available_perms
			'available perms'     => array( 'view' => true, 'edit' => true, 'create' => true, 'delete' => true ),
			// template.tags
			'template tags'       => array( 'CUSTOMER_NAME' => 'Customer name', 'LINK' => 'Link to the page' ),
			// extra.extra for job / quote default tasks and pins
			'task defaults'       => array( 0 => array( 'description' => 'Design', 'hours' => 1.5, 'amount' => '120.00', 'taxable' => 1 ), 1 => array( 'description' => 'Build', 'hours' => 0, 'amount' => '', 'taxable' => 0 ) ),
			'pin'                 => array( 'index.php?m=job&p=job_admin&job_id=3', 'Job 3: A "quoted" title' ),
			// job / finance c_staff_total_grouped
			'staff total grouped' => array( 7 => array( 'hours' => 3.25, 'amount' => 195.0 ), 12 => array( 'hours' => 0, 'amount' => 0 ) ),
			// newsletter_send.cache and newsletter_member.data_cache
			'newsletter cache'    => array( 'sends' => array( 4 => array( 'send_id' => 4, 'status' => 2, 'cache' => '' ) ), 'extra_fields' => array( 'company' => true ), 'unicode' => "Caf\u{e9} \u{2603}" ),
			'member data cache'   => array( 'first_name' => 'Sam', 'email' => 'sam@example.com', 'member_id' => 9 ),
		);
		foreach ( $stored as $label => $value ) {
			$this->assertSame( $value, unserialize( serialize( $value ), $options ), $label );
		}
	}

	public function test_values_written_by_older_releases_still_read() {
		$options = array( 'allowed_classes' => false );
		// Literal strings in the shape the app has always stored them.
		$perms = 'a:4:{s:4:"view";b:1;s:4:"edit";b:1;s:6:"create";b:1;s:6:"delete";b:1;}';
		$this->assertSame( array( 'view' => true, 'edit' => true, 'create' => true, 'delete' => true ), unserialize( $perms, $options ) );
		$payment = 'a:2:{s:3:"log";a:1:{i:0;s:12:"Paid in full";}s:12:"custom_notes";s:6:"Cheque";}';
		$this->assertSame( array( 'log' => array( 'Paid in full' ), 'custom_notes' => 'Cheque' ), unserialize( $payment, $options ) );
		$pin = 'a:2:{i:0;s:9:"index.php";i:1;s:5:"Title";}';
		$this->assertSame( array( 'index.php', 'Title' ), unserialize( $pin, $options ) );
	}

	public function test_a_stored_object_in_one_of_these_values_comes_back_inert() {
		$hostile = serialize( array( 'log' => new ArrayObject( array( 'x' ) ), 'custom_notes' => 'n' ) );
		$data    = unserialize( $hostile, array( 'allowed_classes' => false ) );
		$this->assertSame( 'n', $data['custom_notes'] );
		$this->assertNotInstanceOf( 'ArrayObject', $data['log'] );
		// The callers all test isset( $details['custom_notes'] ) or is_array(), and carry on.
		$this->assertFalse( is_array( $data['log'] ) );
	}

	public function test_an_object_payload_is_never_constructed() {
		$result = unserialize( serialize( new ArrayObject( array( 1 ) ) ), array( 'allowed_classes' => false ) );
		$this->assertInstanceOf( '__PHP_Incomplete_Class', $result );
		$this->assertNotInstanceOf( 'ArrayObject', $result );
	}
}
