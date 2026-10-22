<?php

use PHPUnit\Framework\TestCase;

/**
 * The columns custom/demo/reset.php moves forward so the demo's sample data keeps its age.
 *
 * A column that is misspelt, or that the app no longer has, is skipped at reset time rather
 * than stopping it, which is the right thing for a daily job and the wrong thing to find out
 * from: the demo would quietly start showing last month's invoices as due today. So the names
 * are checked here against the tables the app creates.
 */
class DemoDatesTest extends TestCase {

	private function dates() {
		return require dirname( __DIR__, 2 ) . '/custom/demo/dates.php';
	}

	/** Everything the plugins write into the database, as one string per file. */
	private function plugin_sources() {
		$sources = array();
		foreach ( glob( dirname( __DIR__, 2 ) . '/includes/plugin_*/*.php' ) as $file ) {
			$sources[ $file ] = file_get_contents( $file );
		}

		return $sources;
	}

	public function test_every_entry_is_a_table_of_columns_that_are_dates_or_times() {
		foreach ( $this->dates() as $table => $columns ) {
			$this->assertMatchesRegularExpression( '/^[a-z_]+$/', $table );
			$this->assertNotEmpty( $columns, "$table lists no columns" );
			foreach ( $columns as $column => $kind ) {
				$this->assertMatchesRegularExpression( '/^[a-z_]+$/', $column );
				$this->assertContains( $kind, array( 'date', 'time' ), "$table.$column is neither a date nor a time" );
			}
		}
	}

	public function test_every_table_and_column_exists_in_the_app() {
		$sources = $this->plugin_sources();

		foreach ( $this->dates() as $table => $columns ) {
			// The file that creates the table, and so names its columns.
			$file = null;
			// The three ways the plugins write a prefixed table name into a statement.
			$create = '/CREATE TABLE (?:IF NOT EXISTS )?`(?:[\'"]\s*\.\s*_DB_PREFIX\s*\.\s*[\'"]|<\?php echo _DB_PREFIX; \?>)' . $table . '`/';
			foreach ( $sources as $path => $source ) {
				if ( preg_match( $create, $source ) ) {
					$file = $path;
					break;
				}
			}
			$this->assertNotNull( $file, "No plugin creates a table called $table" );
			foreach ( array_keys( $columns ) as $column ) {
				$this->assertStringContainsString( '`' . $column . '`', $sources[ $file ], "$table has no column called $column" );
			}
		}
	}

	public function test_the_columns_the_sample_data_is_made_of_are_all_listed() {
		// The business dates of the records scripts/demo-seed.mjs makes. Missing one would leave
		// it behind as everything else moved on.
		$dates = $this->dates();
		$must  = array(
			'invoice'         => array( 'date_create', 'date_sent', 'date_due', 'date_paid' ),
			'invoice_payment' => array( 'date_paid' ),
			'quote'           => array( 'date_create', 'date_approved' ),
			'job'             => array( 'date_start', 'date_due', 'date_completed' ),
			'task'            => array( 'date_due', 'date_done' ),
			'task_log'        => array( 'log_time' ),
			'timer'           => array( 'start_time', 'end_time' ),
			'timer_segment'   => array( 'start_time', 'end_time' ),
			'ticket'          => array( 'last_message_timestamp', 'due_timestamp' ),
			'ticket_message'  => array( 'message_time' ),
			'calendar'        => array( 'start', 'end' ),
			'note'            => array( 'note_time' ),
		);
		foreach ( $must as $table => $columns ) {
			$this->assertArrayHasKey( $table, $dates );
			foreach ( $columns as $column ) {
				$this->assertArrayHasKey( $column, $dates[ $table ], "$table.$column is not moved forward with the rest" );
			}
		}
	}
}
