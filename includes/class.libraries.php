<?php

/**
 * Third party library registry.
 *
 * UCM has always shipped its dependencies by copying them into includes/. Those copies
 * receive no security updates, and nothing in the app can tell whether a library is
 * present until it tries to use it and fatals.
 *
 * This is the other half of the arrangement: every plugin declares what it needs, this
 * class works out whether it is there, and it prefers a Composer copy over the old
 * in-tree one. A developer who has run `composer install` gets the maintained library; an
 * install that has not been upgraded keeps using the copy in its own plugin folder and
 * notices nothing.
 *
 * Nothing in here ever fatals. A missing library degrades to false, the feature that
 * needs it can hide itself, and the admin gets a plain message instead of a blank page.
 */
class ucm_libraries
{
	/** @var array library id => declaration, collected from every loaded plugin */
	private static $registry = array();

	/** @var array library id => bool, resolved once per request */
	private static $resolved = array();

	/** @var array library id => 'composer'|'bundled', how it was loaded */
	private static $source = array();

	/** @var bool have we tried vendor/autoload.php yet */
	private static $autoloader_tried = false;

	/**
	 * Collect declarations from every initialised plugin.
	 * Called once from init.php after the $plugins array is built.
	 */
	public static function collect() {
		global $plugins;
		if ( ! is_array( $plugins ) ) {
			return;
		}
		foreach ( $plugins as $plugin ) {
			if ( ! is_object( $plugin ) || ! method_exists( $plugin, 'third_party_libraries' ) ) {
				continue;
			}
			$declared = $plugin->third_party_libraries();
			if ( ! is_array( $declared ) ) {
				continue;
			}
			foreach ( $declared as $library_id => $library ) {
				$library['plugin']             = isset( $plugin->module_name ) ? $plugin->module_name : '';
				self::$registry[ $library_id ] = $library;
			}
		}
	}

	/**
	 * Declare a library without a plugin, for the few places that need one before the
	 * plugins are built.
	 *
	 * @param string $library_id
	 * @param array  $library
	 */
	public static function register( $library_id, $library ) {
		self::$registry[ $library_id ] = $library;
		unset( self::$resolved[ $library_id ], self::$source[ $library_id ] );
	}

	/**
	 * Is this library loadable? Loads it on first call.
	 *
	 * Order of precedence, deliberately:
	 *   1. already in memory - something else got there first
	 *   2. composer vendor/ - the maintained copy
	 *   3. the old copy inside the plugin folder
	 *
	 * Composer wins so that an install which has been upgraded runs the new code, and so
	 * that two incompatible copies of the same class are never loaded in one request.
	 *
	 * @param string $library_id
	 *
	 * @return bool
	 */
	public static function have( $library_id ) {
		if ( isset( self::$resolved[ $library_id ] ) ) {
			return self::$resolved[ $library_id ];
		}
		self::$resolved[ $library_id ] = false;

		if ( ! isset( self::$registry[ $library_id ] ) ) {
			return false;
		}
		$library = self::$registry[ $library_id ];

		if ( self::detect( $library ) ) {
			self::$resolved[ $library_id ] = true;
			self::$source[ $library_id ]   = 'composer';

			return true;
		}

		self::load_composer_autoloader();
		if ( self::detect( $library ) ) {
			self::$resolved[ $library_id ] = true;
			self::$source[ $library_id ]   = 'composer';

			return true;
		}

		if ( ! empty( $library['bundled'] ) ) {
			$bundled = _UCM_FOLDER . ltrim( $library['bundled'], '/' );
			if ( is_file( $bundled ) ) {
				require_once $bundled;
				if ( self::detect( $library ) ) {
					self::$resolved[ $library_id ] = true;
					self::$source[ $library_id ]   = 'bundled';

					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Load the library and say whether it worked. Reads better at a call site than have().
	 *
	 * @param string $library_id
	 *
	 * @return bool
	 */
	public static function load( $library_id ) {
		return self::have( $library_id );
	}

	/**
	 * Where did this library come from: 'composer', 'bundled', or false.
	 *
	 * @param string $library_id
	 *
	 * @return string|bool
	 */
	public static function source( $library_id ) {
		self::have( $library_id );

		return isset( self::$source[ $library_id ] ) ? self::$source[ $library_id ] : false;
	}

	/**
	 * Every declared library with its current status. Drives any settings page.
	 *
	 * @return array
	 */
	public static function all() {
		$all = array();
		foreach ( self::$registry as $library_id => $library ) {
			$library['id']        = $library_id;
			$library['available'] = self::have( $library_id );
			$library['source']    = self::source( $library_id );
			$all[ $library_id ]   = $library;
		}

		return $all;
	}

	/**
	 * Library ids that are not available at all.
	 *
	 * @return array
	 */
	public static function missing() {
		$missing = array();
		foreach ( array_keys( self::$registry ) as $library_id ) {
			if ( ! self::have( $library_id ) ) {
				$missing[] = $library_id;
			}
		}

		return $missing;
	}

	/**
	 * Library ids still being served from the old in-tree copy. These work; they are just
	 * not receiving security updates.
	 *
	 * @return array
	 */
	public static function stale() {
		$stale = array();
		foreach ( array_keys( self::$registry ) as $library_id ) {
			if ( self::have( $library_id ) && self::source( $library_id ) === 'bundled' ) {
				$stale[] = $library_id;
			}
		}

		return $stale;
	}

	/**
	 * Show a plain message in place of a feature that cannot run.
	 * Never throws, never exits - the caller decides what to do next.
	 *
	 * @param string|array $library_ids
	 */
	public static function print_missing_notice( $library_ids ) {
		if ( ! is_array( $library_ids ) ) {
			$library_ids = array( $library_ids );
		}
		$missing = array();
		foreach ( $library_ids as $library_id ) {
			if ( ! self::have( $library_id ) ) {
				$missing[ $library_id ] = isset( self::$registry[ $library_id ] )
					? self::$registry[ $library_id ]
					: array( 'name' => $library_id );
			}
		}
		if ( ! $missing ) {
			return;
		}
		?>
		<div class="ucm_library_notice">
			<h3><?php _e( 'A required component is not installed' ); ?></h3>
			<p>
				<?php _e( 'This feature needs one or more third party libraries that are not present in this installation:' ); ?>
			</p>
			<ul>
				<?php foreach ( $missing as $library ) { ?>
					<li>
						<strong><?php echo htmlspecialchars( isset( $library['name'] ) ? $library['name'] : '' ); ?></strong>
						<?php if ( ! empty( $library['feature'] ) ) { ?>
							&mdash; <?php echo htmlspecialchars( _l( $library['feature'] ) ); ?>
						<?php } ?>
					</li>
				<?php } ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Does this library's detection target exist yet?
	 *
	 * class_exists() with autoloading left on is the only check that works the same way
	 * for a Composer package, a classmap and an old require-based tree. Checking for a
	 * file goes stale the moment a path changes.
	 *
	 * @param array $library
	 *
	 * @return bool
	 */
	private static function detect( $library ) {
		if ( ! empty( $library['class'] ) ) {
			return class_exists( $library['class'] );
		}
		if ( ! empty( $library['function'] ) ) {
			return function_exists( $library['function'] );
		}
		if ( ! empty( $library['constant'] ) ) {
			return defined( $library['constant'] );
		}

		return false;
	}

	/**
	 * Require vendor/autoload.php once, if it is there. It is absent on every install that
	 * has not run `composer install`, which is most of them - that is fine.
	 */
	private static function load_composer_autoloader() {
		if ( self::$autoloader_tried ) {
			return;
		}
		self::$autoloader_tried = true;
		$autoload               = _UCM_FOLDER . 'vendor/autoload.php';
		if ( is_file( $autoload ) && is_readable( $autoload ) ) {
			require_once $autoload;
		}
	}
}
