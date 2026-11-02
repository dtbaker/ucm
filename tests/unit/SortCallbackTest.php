<?php

use PHPUnit\Framework\TestCase;

/**
 * usort(), uasort() and uksort() want a callback that returns a negative number, zero or a
 * positive number. Much of UCM was written when "return $a > $b;" happened to work. On PHP 8
 * a boolean return prints "Returning bool from comparison function is deprecated" on every
 * page that sorts, and a callback that only ever returns 1 or 0 cannot say "$a comes first",
 * so any list longer than 16 rows comes back out of order.
 *
 * The callbacks are found by reading the source: every closure handed to one of those
 * functions, and every function or method named as the callback, wherever it is defined.
 */
class SortCallbackTest extends TestCase
{
	/** Bundled libraries we have not updated. Their code is not ours to fix here. */
	private const VENDORED = array(
		'/vendor/',
		'/node_modules/',
		'/tests/',
		'/includes/plugin_security/htmlpurifier/',
		'/includes/plugin_encrypt/phpseclib/',
		'/includes/plugin_calendar/wdCalendar/',
		'/includes/plugin_captcha/inc/',
		'/includes/plugin_file/upload/plupload/',
	);

	private const SORTS = array( 'usort', 'uasort', 'uksort' );

	private const COMPARISONS = array( '<', '>', T_IS_SMALLER_OR_EQUAL, T_IS_GREATER_OR_EQUAL, T_IS_EQUAL, T_IS_NOT_EQUAL, T_IS_IDENTICAL, T_IS_NOT_IDENTICAL, T_BOOLEAN_AND, T_BOOLEAN_OR, T_LOGICAL_AND, T_LOGICAL_OR, '!' );

	public function test_every_sort_callback_returns_a_number_that_can_be_negative() {
		$files     = $this->source_files();
		$callbacks = $this->callbacks( $files );
		$this->assertGreaterThan( 20, count( $callbacks ), 'the scan no longer finds the sort callbacks, so it would pass whatever they return' );

		$problems = array();
		foreach ( $callbacks as $where => $body ) {
			$can_be_negative = false;
			foreach ( $body as $i => $token ) {
				if ( $this->is( $token, T_SPACESHIP ) || $this->is( $token, '-' ) ) {
					$can_be_negative = true;
				} elseif ( $this->is( $token, T_STRING ) && str_ends_with( strtolower( $token[1] ), 'cmp' ) ) {
					$can_be_negative = true;
				} elseif ( $this->is( $token, T_RETURN ) && $this->returns_a_boolean( $body, $i ) ) {
					$problems[] = $where . ' returns a boolean';
				}
			}
			if ( ! $can_be_negative ) {
				$problems[] = $where . ' can never return a negative number';
			}
		}
		$this->assertSame( array(), $problems );
	}

	/**
	 * @param array<string, string> $files relative path => full path
	 * @return array<string, array> "file:line" => the callback's tokens
	 */
	private function callbacks( array $files ) {
		$named     = array();
		$callbacks = array();
		foreach ( $files as $file => $path ) {
			$tokens = token_get_all( file_get_contents( $path ) );
			foreach ( $tokens as $i => $token ) {
				if ( ! $this->is( $token, T_STRING ) || ! in_array( strtolower( $token[1] ), self::SORTS, true ) || $this->is( $this->previous( $tokens, $i ), T_FUNCTION ) ) {
					continue;
				}
				$argument = $this->second_argument( $tokens, $i );
				$first    = $argument[0] ?? null;
				if ( $this->is( $first, T_FUNCTION ) || $this->is( $first, T_FN ) || $this->is( $first, T_STATIC ) ) {
					$callbacks[ $file . ':' . $first[2] ] = $argument;
				} else {
					// 'function_name' or array( $this, 'method_name' ): the last string names it.
					foreach ( array_reverse( $argument ) as $part ) {
						if ( $this->is( $part, T_CONSTANT_ENCAPSED_STRING ) ) {
							$named[ strtolower( trim( $part[1], '\'"' ) ) ] = true;
							break;
						}
					}
				}
			}
		}

		foreach ( $files as $file => $path ) {
			$tokens = token_get_all( file_get_contents( $path ) );
			foreach ( $tokens as $i => $token ) {
				if ( ! $this->is( $token, T_FUNCTION ) ) {
					continue;
				}
				$name = $this->next( $tokens, $i );
				if ( $this->is( $name, T_STRING ) && isset( $named[ strtolower( $name[1] ) ] ) ) {
					$callbacks[ $file . ':' . $name[2] . ' ' . $name[1] . '()' ] = $this->function_body( $tokens, $i );
				}
			}
		}

		return $callbacks;
	}

	/** A return whose value is a bare comparison, true or false, rather than a number. */
	private function returns_a_boolean( array $body, $i ) {
		$depth       = 0;
		$compares    = false;
		$conditional = false;
		for ( $j = $i + 1; $j < count( $body ); $j++ ) {
			$token = $body[ $j ];
			if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			if ( $depth === 0 && ( $this->is( $token, ';' ) || $this->is( $token, '}' ) ) ) {
				break;
			}
			if ( in_array( $token, array( '(', '[' ), true ) || $this->is( $token, T_CURLY_OPEN ) ) {
				$depth++;
			} elseif ( in_array( $token, array( ')', ']', '}' ), true ) ) {
				$depth--;
			} elseif ( $depth === 0 && $this->is( $token, '?' ) ) {
				$conditional = true;
			} elseif ( $depth === 0 && ( $this->is_any( $token, self::COMPARISONS ) || ( $this->is( $token, T_STRING ) && in_array( strtolower( $token[1] ), array( 'true', 'false' ), true ) ) ) ) {
				$compares = true;
			}
		}

		return $compares && ! $conditional;
	}

	private function second_argument( array $tokens, $i ) {
		$depth    = 0;
		$argument = array();
		$started  = false;
		for ( $j = $i + 1; $j < count( $tokens ); $j++ ) {
			$token = $tokens[ $j ];
			if ( in_array( $token, array( '(', '[', '{' ), true ) || $this->is( $token, T_CURLY_OPEN ) || $this->is( $token, T_DOLLAR_OPEN_CURLY_BRACES ) ) {
				$depth++;
			} elseif ( in_array( $token, array( ')', ']', '}' ), true ) ) {
				$depth--;
				if ( $depth === 0 ) {
					break;
				}
			} elseif ( $depth === 1 && $token === ',' ) {
				if ( $started ) {
					break;
				}
				$started = true;
				continue;
			}
			if ( $started && ! ( is_array( $token ) && $token[0] === T_WHITESPACE ) ) {
				$argument[] = $token;
			}
		}

		return $argument;
	}

	private function function_body( array $tokens, $i ) {
		$depth = 0;
		$body  = array();
		for ( $j = $i; $j < count( $tokens ); $j++ ) {
			$token  = $tokens[ $j ];
			$body[] = $token;
			if ( $token === '{' || $this->is( $token, T_CURLY_OPEN ) || $this->is( $token, T_DOLLAR_OPEN_CURLY_BRACES ) ) {
				$depth++;
			} elseif ( $token === '}' ) {
				$depth--;
				if ( $depth === 0 ) {
					break;
				}
			} elseif ( $token === ';' && $depth === 0 ) {
				break; // an abstract or interface method has no body
			}
		}

		return $body;
	}

	private function source_files() {
		$root  = dirname( __DIR__, 2 );
		$files = array();
		$walk  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $walk as $path => $info ) {
			$relative = substr( $path, strlen( $root ) );
			if ( $info->getExtension() !== 'php' || str_starts_with( $relative, '/.' ) || str_starts_with( $relative, '/temp/' ) ) {
				continue;
			}
			foreach ( self::VENDORED as $vendored ) {
				if ( str_contains( $relative, $vendored ) ) {
					continue 2;
				}
			}
			$files[ ltrim( $relative, '/' ) ] = $path;
		}

		return $files;
	}

	private function previous( array $tokens, $i ) {
		for ( $j = $i - 1; $j >= 0; $j-- ) {
			if ( ! ( is_array( $tokens[ $j ] ) && $tokens[ $j ][0] === T_WHITESPACE ) ) {
				return $tokens[ $j ];
			}
		}

		return null;
	}

	private function next( array $tokens, $i ) {
		for ( $j = $i + 1; $j < count( $tokens ); $j++ ) {
			if ( ! ( is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG ), true ) ) && $tokens[ $j ] !== '&' ) {
				return $tokens[ $j ];
			}
		}

		return null;
	}

	private function is( $token, $kind ) {
		return is_array( $token ) ? $token[0] === $kind : $token === $kind;
	}

	private function is_any( $token, array $kinds ) {
		foreach ( $kinds as $kind ) {
			if ( $this->is( $token, $kind ) ) {
				return true;
			}
		}

		return false;
	}
}
