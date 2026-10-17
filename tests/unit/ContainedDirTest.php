<?php

use PHPUnit\Framework\TestCase;

/**
 * ucm_contained_dir() exists because basename() reads as a containment check and is not
 * one. basename( '..' ) is '..', so "root/" . basename( $name ) . "/" with an is_dir()
 * check in front of it accepts the parent of the root - the parent exists, so is_dir()
 * says yes. The newsletter templates resolved a directory that way and then include()d
 * PHP out of it.
 */
class ContainedDirTest extends TestCase
{
	private $root;

	protected function setUp(): void {
		$this->root = _UCM_FOLDER . 'includes/plugin_newsletter/email_template';
	}

	public function test_a_real_template_folder_comes_back() {
		$this->assertSame( 'Basic1', ucm_contained_dir( $this->root, 'Basic1' ) );
	}

	public function test_dot_dot_does_not_escape_the_root() {
		$this->assertFalse( ucm_contained_dir( $this->root, '..' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, '../' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, '../../' ) );
	}

	public function test_a_traversal_path_cannot_reach_another_directory() {
		// basename() reduces these to a single segment; the point is that the segment it
		// leaves behind is then checked rather than trusted.
		$this->assertFalse( ucm_contained_dir( $this->root, '../../../etc' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, '/etc/passwd' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, 'includes/plugin_newsletter' ) );
	}

	public function test_a_name_that_is_not_a_directory_is_refused() {
		$this->assertFalse( ucm_contained_dir( $this->root, 'no_such_template_here' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, '' ) );
		$this->assertFalse( ucm_contained_dir( $this->root, 'a' ) );
	}

	public function test_a_root_that_does_not_exist_yields_nothing() {
		$this->assertFalse( ucm_contained_dir( _UCM_FOLDER . 'no/such/root', 'Basic1' ) );
	}
}
