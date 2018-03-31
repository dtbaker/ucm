<?php


class module_debug extends module_base {

	public static $debug = array();
	public static $show_debug = false;
	public static $start_time = 0;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		if ( self::$start_time == 0 ) {
			self::$start_time = microtime( true );
		}
		$this->module_name     = "debug";
		$this->module_position = 1;
		$this->version         = 2.121;
		// 2.1 - 2013-08-30 - debug mode enhancements
		// 2.11 - 2013-09-15 - ID in debug column output
		// 2.12 - 2014-07-28 - more info in debug output and ?debug_only_bad url
		// 2.121 - 2014-10-07 - memory_get_usage fix
	}

	public static function log( $data ) {
		if ( $data && _DEBUG_MODE ) {
			$data['time'] = substr( ( ( isset( $data['time'] ) ? $data['time'] : microtime( true ) ) - self::$start_time ), 0, 5 );
			/*$data['trace'] = debug_backtrace();
			if(isset($data['trace'][0])){
					unset($data['trace'][0]);
			}*/
			$data['mem']   = isset( $data['mem'] ) ? $data['mem'] : ( function_exists( 'memory_get_usage' ) ? round( memory_get_usage() / 1048576, 4 ) : '' );
			self::$debug[] = $data;
		}
	}

	public static function push_to_parent() {
		if ( _DEBUG_MODE ) {
			?>
			<script type="text/javascript">
          var tbl = $('#system_debug_data tbody');
          if (typeof tbl[0] != 'undefined') {
              $(tbl).append('<tr><td colspan="5"><strong>Debug from: <?php
								echo substr( $_SERVER['REQUEST_URI'], 0, 40 ) . '...';
								;?></strong></td></tr>');
              $(tbl).append('<?php
								ob_start();
								self::debug_list();
								$html = ob_get_clean();
								$html = preg_replace( '/\s+/', ' ', $html );
								echo addcslashes( $html, "'" );
								;?>');
          }
			</script>
			<?php
		}
	}

	public static function debug_list() {
		$hash = md5( microtime( true ) );
		$id   = 1;
		ob_start();
		$time_limit   = 0.01;
		$memory_limit = 0.2;
		$show_next    = false;
		foreach ( self::$debug as $debug_id => $debug ) {

			// how long did this particular action take?
			// we calculate this based off the time of the next recorded timestamp.
			$next_time = isset( self::$debug[ $debug_id + 1 ]['time'] ) ? self::$debug[ $debug_id + 1 ]['time'] : 0;
			$this_time = 0;
			if ( $next_time > 0 && isset( $debug['time'] ) ) {
				$this_time = $next_time - $debug['time'];
			}
			$next_memory = isset( self::$debug[ $debug_id + 1 ]['mem'] ) ? self::$debug[ $debug_id + 1 ]['mem'] : 0;
			$this_memory = 0;
			if ( $next_memory > 0 && isset( $debug['mem'] ) ) {
				$this_memory = $next_memory - $debug['mem'];
			}

			if ( isset( $_REQUEST['debug_only_bad'] ) ) {
				if ( isset( $debug['important'] ) && $debug['important'] ) {
					// keep this one.
				} else if ( $this_memory > $memory_limit ) {
					$show_next = true;

				} else if ( $this_time > $time_limit ) {
					$show_next = true;
					// show this one!
				} else if ( $show_next ) {
					$show_next = false;
				} else {
					continue;
				}
			}

			?>
			<tr>
				<td>
					<?php echo isset( $debug['time'] ) ? $debug['time'] : '??'; ?>
				</td>
				<td>
					<?php
					if ( isset( $debug['time'] ) ) {
						if ( $this_time > $time_limit ) {
							echo '<span style="color:#ff0000; font-weight:bold;">' . $this_time . '</span>';
						} else {
							echo $this_time;
						}
					}
					?>
				</td>
				<td>
					<?php
					echo isset( $debug['mem'] ) ? $debug['mem'] : 'NA';
					if ( $this_memory > $memory_limit ) {
						echo ' <span style="color:#ff0000; font-weight:bold;">(+' . $this_memory . ')</span>';
					}
					?>
				</td>
				<td>
					<?php echo $id ++; ?>
				</td>
				<td>
					<?php echo isset( $debug['title'] ) ? $debug['title'] : 'NA'; ?>
				</td>
				<td>
					<?php echo isset( $debug['data'] ) ? $debug['data'] : 'NA'; ?>
				</td>
				<td>
					<?php echo isset( $debug['file'] ) ? $debug['file'] : 'NA'; ?>
				</td>
				<td>
					<?php if ( module_config::c( 'debug_show_data', 0 ) ) { ?>
						<a href="#" onclick="$('#trace_<?php echo $hash . $x; ?>').toggle(); return false;">Show &raquo;</a>
						<div id="trace_<?php echo $hash . $x; ?>"
						     style="display:none; position:absolute; background-color:#CCC; font-size:10px;">
							<pre><?php echo nl2br( var_export( $debug['trace'], true ) ); ?></pre>
						</div>
					<?php } ?>
				</td>
			</tr>
			<?php
		}
		echo preg_replace( '#\s+#', ' ', ob_get_clean() );
	}

	public static function print_heading() {
		if ( self::$show_debug ) {
			?>
			<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/styles.css?ver=3" type="text/css"/>
			<link type="text/css" href="<?php echo _BASE_HREF; ?>css/smoothness/jquery-ui-1.9.2.custom.min.css"
			      rel="stylesheet"/>
			<script type="text/javascript" src="<?php echo _BASE_HREF; ?>js/jquery-1.8.3.min.js"></script>
			<?php
		}
		?>
		<div id="system_debug" style="position:absolute; z-index:90000; background:#FFF; border:1px solid #CCC;">
			<a href="#" onclick="$('#system_debug_data').toggle(); return false;">View Debug &raquo;</a>
		</div>
		<?php
	}

	public static function print_footer() {
		?>
		<div id="system_debug_data"
		     style="<?php echo ( ! self::$show_debug ) ? 'display:none;' : ''; ?>position: absolute; top:15px;z-index:90000"
		     class="tableclass tableclass_rows">
			<h3>Debug Information:</h3>
			<table width="100%" cellpadding="4">
				<thead>
				<tr>
					<th>Time</th>
					<th>Delay</th>
					<th>MB</th>
					<th>ID</th>
					<th>Title</th>
					<th>Data</th>
					<th>File</th>
					<th>Trace</th>
				</tr>
				</thead>
				<tbody>
				<?php
				self::debug_list();
				?>
				</tbody>
			</table>
		</div>
		<?php
	}

}

