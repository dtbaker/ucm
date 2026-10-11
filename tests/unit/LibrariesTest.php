<?php

use PHPUnit\Framework\TestCase;

/**
 * ucm_libraries decides where a third party library comes from.
 *
 * The rule that matters is the order: a Composer copy always wins over the old copy in
 * the plugin folder, so an install that has been upgraded runs the maintained code and
 * two incompatible copies of the same class are never loaded in one request.
 */
class LibrariesTest extends TestCase
{
	public function test_a_library_that_is_already_loaded_counts_as_composer() {
		ucm_libraries::register( 'test_already_loaded', array(
			'name'    => 'Something Built In',
			'class'   => 'ArrayObject',
			'bundled' => 'tests/unit/fixtures/library.php',
		) );

		$this->assertTrue( ucm_libraries::have( 'test_already_loaded' ) );
		$this->assertSame( 'composer', ucm_libraries::source( 'test_already_loaded' ) );
	}

	public function test_the_bundled_copy_is_used_when_nothing_else_provides_it() {
		ucm_libraries::register( 'test_bundled', array(
			'name'    => 'Fixture Library',
			'class'   => 'UcmFixtureBundledLibrary',
			'bundled' => 'tests/unit/fixtures/library.php',
		) );

		$this->assertTrue( ucm_libraries::have( 'test_bundled' ) );
		$this->assertSame( 'bundled', ucm_libraries::source( 'test_bundled' ) );
		$this->assertContains( 'test_bundled', ucm_libraries::stale() );
	}

	public function test_a_library_with_nowhere_to_come_from_is_missing() {
		ucm_libraries::register( 'test_missing', array(
			'name'  => 'Not Here',
			'class' => 'UcmLibraryThatDoesNotExist',
		) );

		$this->assertFalse( ucm_libraries::have( 'test_missing' ) );
		$this->assertFalse( ucm_libraries::source( 'test_missing' ) );
		$this->assertContains( 'test_missing', ucm_libraries::missing() );
	}

	public function test_a_bundled_path_that_does_not_exist_does_not_fatal() {
		ucm_libraries::register( 'test_bad_path', array(
			'name'    => 'Wrong Path',
			'class'   => 'UcmLibraryWithABadPath',
			'bundled' => 'includes/this/does/not/exist.php',
		) );

		$this->assertFalse( ucm_libraries::have( 'test_bad_path' ) );
	}

	public function test_an_unknown_library_is_simply_not_available() {
		$this->assertFalse( ucm_libraries::have( 'test_never_declared' ) );
		$this->assertFalse( ucm_libraries::source( 'test_never_declared' ) );
	}

	public function test_all_reports_the_status_of_each_declared_library() {
		ucm_libraries::register( 'test_listed', array(
			'name'  => 'Listed Library',
			'class' => 'ArrayObject',
		) );

		$all = ucm_libraries::all();
		$this->assertArrayHasKey( 'test_listed', $all );
		$this->assertSame( 'Listed Library', $all['test_listed']['name'] );
		$this->assertTrue( $all['test_listed']['available'] );
		$this->assertSame( 'test_listed', $all['test_listed']['id'] );
	}
}
