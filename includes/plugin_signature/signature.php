<?php

class module_signature extends module_base {

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	function init() {
		$this->links           = array();
		$this->module_name     = "signature";
		$this->module_position = 17.1; //17 is job

		$this->version = 2.21;
		// 2.1 - 2014-07-09 - initial release
		// 2.2 - 2014-07-12 - staff and customer signature from same login account support
		// 2.21 - 2014-07-12 - staff and customer signature from same login account support

		if ( self::is_plugin_enabled() ) {
			module_config::register_css( 'signature', 'signature.css' );
			module_config::register_js( 'signature', 'signature.js' );
			hook_add( 'header_print_js', 'module_signature::hook_header_print_js' );
		}
	}

	public static function hook_header_print_js() {
		if ( module_security::is_logged_in() ) {
			?>
			<script type="text/javascript">
          $(function () {
              if (typeof ucm.signature != 'undefined') {
                  ucm.signature.lang.title = '<?php _e( 'Signature' );?>';
                  ucm.signature.ajax_url = '<?php echo self::link_public_signature_job_task(); ?>';
                  ucm.signature.init();
              }
          });
			</script>
			<?php
		}
	}

	public static function signature_enabled( $job_id ) {
		return self::is_plugin_enabled() && module_config::c( 'signature_job_tasks_customers', 1 );
	}

	public static function signature_job_task_link( $job_id, $task_id ) {
		// check if staff or customer signature is required.
		$job_task = module_job::get_task( $job_id, $task_id );
		if ( $job_task && ! $job_task['fully_completed'] ) {
			echo _l( 'Pending Completion' );

			return;
		}
		$staff = false;
		if ( module_security::is_logged_in() ) {
			// are we a staff member on this job task?
			if ( $job_task['user_id'] == module_security::get_loggedin_id() ) {
				// yes! we are the staff member.
				$staff = true;
			} else {
				// assume this is the customer signature.
			}
		}
		$existing_job_task_signature = get_single( 'signature_job_task', array( 'job_id', 'task_id' ), array(
			$job_id,
			$task_id
		) );
		$existing_signature_id       = false;
		if ( ! $existing_job_task_signature || ! $existing_job_task_signature['staff_signature_id'] ) {
			$class = 'signature_staff';
			$text  = _l( 'Staff Required' );
		} else if ( $existing_job_task_signature && ! $existing_job_task_signature['customer_signature_id'] ) {
			$class = 'signature_customer';
			$text  = _l( 'Customer Required' );
		} else {
			$class = 'signature_completed';
			$text  = _l( 'Completed' );
		}
		?> <a href="" class="signature_popup_link <?php echo $class; ?>" data-ajax_job_id="<?php echo $job_id; ?>"
		      data-ajax_task_id="<?php echo $task_id; ?>"><?php echo $text; ?></a> <?php
	}

	public static function link_public_signature_job_task( $args = array(), $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for signature ' . _UCM_SECRET . ' ' . serialize( $args ) );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.signature/h.signature_job_task/i.' . base64_encode( json_encode( $args ) ) . '/hash.' . self::link_public_signature_job_task( $args, true ) );
	}

	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'signature_job_task':
				$args = json_decode( base64_decode( $_REQUEST['i'] ), true );
				include( 'pages/signature_job_task.php' );
				break;
		}
	}

	public function get_install_sql() {
		ob_start();
		?>
		CREATE TABLE `<?php echo _DB_PREFIX; ?>signature` (
		`signature_id` int(11) NOT NULL auto_increment,
		`signator` VARCHAR(255) NOT NULL DEFAULT  '',
		`sig_hash` VARCHAR(255) NOT NULL DEFAULT  '',
		`signature` TEXT NULL,
		`create_time` int(11) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` DATETIME NOT NULL,
		`date_updated` date NULL,
		`create_ip_address` VARCHAR(25) NOT NULL DEFAULT  '',
		`update_ip_address` VARCHAR(25) NOT NULL DEFAULT  '',
		PRIMARY KEY (`signature_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>signature_job_task` (
		`job_id` INT(11) NOT NULL,
		`task_id` INT(11) NOT NULL,
		`staff_signature_id` int(11) NOT NULL DEFAULT 0,
		`customer_signature_id` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`job_id`,`task_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		<?php
		return ob_get_clean();

	}
}


/**
 *  Signature to Image: A supplemental script for Signature Pad that
 *  generates an image of the signature’s JSON output server-side using PHP.
 *
 * @project ca.thomasjbradley.applications.signaturetoimage
 * @author Thomas J Bradley <hey@thomasjbradley.ca>
 * @link http://thomasjbradley.ca/lab/signature-to-image
 * @link http://github.com/thomasjbradley/signature-to-image
 * @copyright Copyright MMXI–, Thomas J Bradley
 * @license New BSD License
 * @version 1.1.0
 */

/**
 *  Accepts a signature created by signature pad in Json format
 *  Converts it to an image resource
 *  The image resource can then be changed into png, jpg whatever PHP GD supports
 *
 *  To create a nicely anti-aliased graphic the signature is drawn 12 times it's original size then shrunken
 *
 * @param string|array $json
 * @param array        $options OPTIONAL; the options for image creation
 *    imageSize => array(width, height)
 *    bgColour => array(red, green, blue) | transparent
 *    penWidth => int
 *    penColour => array(red, green, blue)
 *    drawMultiplier => int
 *
 * @return object
 */
function sigJsonToImage( $json, $options = array() ) {
	$defaultOptions = array(
		'imageSize'      => array( 350, 100 )
	,
		'bgColour'       => array( 0xff, 0xff, 0xff )
	,
		'penWidth'       => 2
	,
		'penColour'      => array( 0x14, 0x53, 0x94 )
	,
		'drawMultiplier' => 12
	);

	$options = array_merge( $defaultOptions, $options );

	$img = imagecreatetruecolor( $options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][1] * $options['drawMultiplier'] );

	if ( $options['bgColour'] == 'transparent' ) {
		imagesavealpha( $img, true );
		$bg = imagecolorallocatealpha( $img, 0, 0, 0, 127 );
	} else {
		$bg = imagecolorallocate( $img, $options['bgColour'][0], $options['bgColour'][1], $options['bgColour'][2] );
	}

	$pen = imagecolorallocate( $img, $options['penColour'][0], $options['penColour'][1], $options['penColour'][2] );
	imagefill( $img, 0, 0, $bg );

	if ( is_string( $json ) ) {
		$json = json_decode( stripslashes( $json ) );
	}

	foreach ( $json as $v ) {
		drawThickLine( $img, $v->lx * $options['drawMultiplier'], $v->ly * $options['drawMultiplier'], $v->mx * $options['drawMultiplier'], $v->my * $options['drawMultiplier'], $pen, $options['penWidth'] * ( $options['drawMultiplier'] / 2 ) );
	}

	$imgDest = imagecreatetruecolor( $options['imageSize'][0], $options['imageSize'][1] );

	if ( $options['bgColour'] == 'transparent' ) {
		imagealphablending( $imgDest, false );
		imagesavealpha( $imgDest, true );
	}

	imagecopyresampled( $imgDest, $img, 0, 0, 0, 0, $options['imageSize'][0], $options['imageSize'][0], $options['imageSize'][0] * $options['drawMultiplier'], $options['imageSize'][0] * $options['drawMultiplier'] );
	imagedestroy( $img );

	return $imgDest;
}

/**
 *  Draws a thick line
 *  Changing the thickness of a line using imagesetthickness doesn't produce as nice of result
 *
 * @param object $img
 * @param int    $startX
 * @param int    $startY
 * @param int    $endX
 * @param int    $endY
 * @param object $colour
 * @param int    $thickness
 *
 * @return void
 */
function drawThickLine( $img, $startX, $startY, $endX, $endY, $colour, $thickness ) {
	$angle = ( atan2( ( $startY - $endY ), ( $endX - $startX ) ) );

	$dist_x = $thickness * ( sin( $angle ) );
	$dist_y = $thickness * ( cos( $angle ) );

	$p1x = ceil( ( $startX + $dist_x ) );
	$p1y = ceil( ( $startY + $dist_y ) );
	$p2x = ceil( ( $endX + $dist_x ) );
	$p2y = ceil( ( $endY + $dist_y ) );
	$p3x = ceil( ( $endX - $dist_x ) );
	$p3y = ceil( ( $endY - $dist_y ) );
	$p4x = ceil( ( $startX - $dist_x ) );
	$p4y = ceil( ( $startY - $dist_y ) );

	$array = array( 0 => $p1x, $p1y, $p2x, $p2y, $p3x, $p3y, $p4x, $p4y );
	imagefilledpolygon( $img, $array, ( count( $array ) / 2 ), $colour );
}
