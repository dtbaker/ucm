<?php

use PHPUnit\Framework\TestCase;

/**
 * A plugin's declared library version has to match the one composer.json installs.
 *
 * Each plugin's third_party_libraries() names the Composer package it needs and the
 * version range it was written against. Nothing checks that range at runtime - the
 * registry only looks for the class - so when Dependabot moves composer.json to a new
 * major version, the plugin keeps claiming the old one and nobody notices. This makes
 * that a failing test, so a major bump cannot merge until someone has read what changed
 * and updated the plugin to say so.
 */
class LibraryConstraintTest extends TestCase {

	public function test_every_declared_composer_package_matches_composer_json() {
		$root     = dirname( __DIR__, 2 );
		$composer = json_decode( (string) file_get_contents( $root . '/composer.json' ), true );
		$required = isset( $composer['require'] ) ? $composer['require'] : array();

		$checked = 0;
		foreach ( glob( $root . '/includes/plugin_*/*.php' ) as $file ) {
			$source = (string) file_get_contents( $file );
			if ( ! preg_match_all( "/'package'\s*=>\s*'([^']+)',\s*'constraint'\s*=>\s*'([^']+)'/", $source, $found, PREG_SET_ORDER ) ) {
				continue;
			}
			foreach ( $found as $declaration ) {
				list( , $package, $constraint ) = $declaration;
				if ( ! isset( $required[ $package ] ) ) {
					// Declared for a library that is only bundled in-tree, not installed by Composer.
					continue;
				}
				$this->assertSame(
					$required[ $package ],
					$constraint,
					basename( dirname( $file ) ) . ' declares ' . $package . ' ' . $constraint
					. ' but composer.json installs ' . $required[ $package ]
					. '. Read the library\'s changelog, update the plugin, then update its declaration.'
				);
				$checked++;
			}
		}

		$this->assertGreaterThan( 0, $checked, 'no plugin declared a Composer package, so this test checked nothing' );
	}
}
