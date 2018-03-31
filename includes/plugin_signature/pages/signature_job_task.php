<?php


$job_id = $task_id = false;

if ( $args ) {
	if ( isset( $args['job_id'] ) ) {
		$job_id = $args['job_id'];
	}
	if ( isset( $args['task_id'] ) ) {
		$task_id = $args['task_id'];
	}

}

$job_id  = $job_id ? $job_id : ( isset( $_REQUEST['job_id'] ) ? (int) $_REQUEST['job_id'] : false );
$task_id = $task_id ? $task_id : ( isset( $_REQUEST['task_id'] ) ? (int) $_REQUEST['task_id'] : false );
if ( ! $job_id || ! $task_id ) {
	die( 'Bad link, try again' );
}

$job_data = module_job::get_job( $job_id, false, true );
$job_task = module_job::get_task( $job_id, $task_id );
if ( ! $job_data || $job_data['job_id'] != $job_id || ! $job_task || $job_task['task_id'] != $task_id ) {
	die( 'Incorrect link, please report this issue.' );
}

$require_staff    = true;
$require_customer = true;
$staff            = false;
/*
$staff = false;
if(module_security::is_logged_in()){
	// are we a staff member on this job task?
	if($job_task['user_id'] == module_security::get_loggedin_id()){
		// yes! we are the staff member.
		$staff = true;
	}else{
		// assume this is the customer signature.

	}
}
*/
// has this person already signed for this job task before?
$existing_job_task_signature = get_single( 'signature_job_task', array( 'job_id', 'task_id' ), array(
	$job_id,
	$task_id
) );
$existing_signature_id       = false;
if ( $existing_job_task_signature ) {

	if ( $args && isset( $args['show_signature'] ) ) {
		if ( $args['show_signature'] == $existing_job_task_signature['staff_signature_id'] ) {
			// showing the staff signature.
			$show_signature = get_single( 'signature', 'signature_id', $existing_job_task_signature['staff_signature_id'] );
		} else if ( $args['show_signature'] == $existing_job_task_signature['customer_signature_id'] ) {
			// showing the customer signature.
			$show_signature = get_single( 'signature', 'signature_id', $existing_job_task_signature['customer_signature_id'] );
		} else {
			exit;
		}
		if ( $show_signature['signature'] ) {
			$image = sigJsonToImage( $show_signature['signature'] );
			if ( $image ) {
				header( 'Content-type: image/jpeg' );
				imagejpeg( $image, null, 100 );
			}
		}
		exit;
	}

	/*if($staff && $existing_job_task_signature['staff_signature_id']){
		$existing_signature_id = $existing_job_task_signature['staff_signature_id'];
	}else if(!$staff && $existing_job_task_signature['customer_signature_id']){
		$existing_signature_id = $existing_job_task_signature['customer_signature_id'];
	}*/
	if ( $existing_job_task_signature['staff_signature_id'] ) {
		$require_staff = false;
	}
	if ( $existing_job_task_signature['customer_signature_id'] ) {
		$require_customer = false;
	}

}
/*if(!$staff && (!$existing_job_task_signature || !$existing_job_task_signature['staff_signature_id'])){
	_e('Waiting on Staff signature. Please check back later.');
	exit;
}*/
/*
if($existing_signature_id){
	$existing_signature = get_single('signature','signature_id',$existing_signature_id);
}else{
	$existing_signature = false;
}
*/
if ( isset( $_REQUEST['save_signature'] ) ) {
	// saving yey!
	$result = array();
	if ( isset( $_REQUEST['name'] ) && strlen( $_REQUEST['name'] ) && isset( $_REQUEST['output'] ) && strlen( $_REQUEST['output'] ) > 3 ) {
		$sig = json_encode( json_decode( $_REQUEST['output'] ) );
		if ( strlen( $sig ) ) {
			$existing_signature_id = update_insert( 'signature_id', $existing_signature_id, 'signature', array(
				'signator'    => $_REQUEST['name'],
				'signature'   => $sig,
				'sig_hash'    => sha1( $sig ),
				'create_time' => time(),
			) );
			if ( $existing_signature_id ) {
				if ( $existing_job_task_signature ) {
					$sql = "UPDATE `" . _DB_PREFIX . "signature_job_task` SET ";
					$sql .= $require_staff ? '`staff_signature_id` = ' : '`customer_signature_id` = ';
					$sql .= (int) $existing_signature_id;
					$sql .= " WHERE `job_id` = " . (int) $job_id . " AND `task_id` = " . (int) $task_id;
					query( $sql );
				} else {
					$sql = "INSERT INTO `" . _DB_PREFIX . "signature_job_task` SET ";
					$sql .= $require_staff ? '`staff_signature_id` = ' : '`customer_signature_id` = ';
					$sql .= (int) $existing_signature_id;
					$sql .= ", `job_id` = " . (int) $job_id . ", `task_id` = " . (int) $task_id;
					query( $sql );
				}
				$result['message'] = _l( 'Signature saved, thank you' );
				$result['error']   = 0;
			} else {
				$result['message'] = _l( 'Failed saving to database' );
				$result['error']   = 1;
			}
		} else {
			$result['message'] = _l( 'Error reading signature, please try again.' );
			$result['error']   = 1;
		}
	} else {
		$result['message'] = _l( 'Required fields missing, please try again' );
		$result['error']   = 1;

	}
	header( 'Content-type: text/javascript' );
	echo json_encode( $result );
	exit;

} else {
	// display:


	if ( $existing_job_task_signature ) {
		if ( $existing_job_task_signature['staff_signature_id'] ) {
			$existing_signature_id = $existing_job_task_signature['staff_signature_id'];
			$existing_signature    = get_single( 'signature', 'signature_id', $existing_signature_id );
			if ( $existing_signature ) {
				if ( $existing_signature['update_user_id'] ) {
					$user_string = _l( 'by user %s (%s)', module_user::link_open( $existing_signature['update_user_id'], true ), htmlspecialchars( $existing_signature['signator'] ) );
				} else if ( $existing_signature['create_user_id'] ) {
					$user_string = _l( 'by user %s (%s)', module_user::link_open( $existing_signature['create_user_id'], true ), htmlspecialchars( $existing_signature['signator'] ) );
				} else {
					$user_string = _l( 'by the customer (%s)', htmlspecialchars( $existing_signature['signator'] ) );
				}
				echo '<p>' . _l( 'Staff signature received at %s from IP %s %s', print_date( $existing_signature['create_time'], true ), $existing_signature['update_ip_address'] ? $existing_signature['update_ip_address'] : $existing_signature['create_ip_address'], $user_string );
				echo '<br>';
				echo '<img src="' . module_signature::link_public_signature_job_task( array(
						'show_signature' => $existing_signature_id,
						'job_id'         => $job_id,
						'task_id'        => $task_id
					) ) . '" alt="Signature"/>';
				echo '</p>';
			}
		}
		if ( $existing_job_task_signature['customer_signature_id'] ) {
			$existing_signature_id = $existing_job_task_signature['customer_signature_id'];
			$existing_signature    = get_single( 'signature', 'signature_id', $existing_signature_id );
			if ( $existing_signature ) {
				if ( $existing_signature['update_user_id'] ) {
					$user_string = _l( 'by user %s (%s)', module_user::link_open( $existing_signature['update_user_id'], true ), htmlspecialchars( $existing_signature['signator'] ) );
				} else if ( $existing_signature['create_user_id'] ) {
					$user_string = _l( 'by user %s (%s)', module_user::link_open( $existing_signature['create_user_id'], true ), htmlspecialchars( $existing_signature['signator'] ) );
				} else {
					$user_string = _l( 'by the customer (%s)', htmlspecialchars( $existing_signature['signator'] ) );
				}
				echo '<p>' . _l( 'Customer signature received at %s from IP %s %s', print_date( $existing_signature['create_time'], true ), $existing_signature['update_ip_address'] ? $existing_signature['update_ip_address'] : $existing_signature['create_ip_address'], $user_string );
				echo '<br>';
				echo '<img src="' . module_signature::link_public_signature_job_task( array(
						'show_signature' => $existing_signature_id,
						'job_id'         => $job_id,
						'task_id'        => $task_id
					) ) . '" alt="Signature"/>';
				echo '</p>';
			}
		}
		/*if($staff && $existing_job_task_signature['staff_signature_id'] && !$existing_job_task_signature['customer_signature_id']){
			echo '<p>' . _l( 'Waiting on Customer Signature') .'</p>';
		}*/
	}

	/*$existing_signature_id = false;
	if ( $existing_job_task_signature ) {
		if ( $staff && $existing_job_task_signature['staff_signature_id'] ) {
			$existing_signature_id = $existing_job_task_signature['staff_signature_id'];
		} else if ( !$staff && $existing_job_task_signature['customer_signature_id'] ) {
			$existing_signature_id = $existing_job_task_signature['customer_signature_id'];
		}
	}*/
	//if ( !$existing_signature_id ) {
	if ( $require_staff || $require_customer ) {
		echo '<p>' . _l( '%s Approval for Task: %s', ( $require_staff ? _l( 'Staff' ) : _l( 'Customer' ) ), $job_task['description'] ) . '</p>';

		?>
		<script type="text/javascript"
		        src="<?php echo _BASE_HREF . "includes/plugin_signature/js/jquery.signaturepad.min.js"; ?>"></script>
		<link rel="stylesheet"
		      href="<?php echo _BASE_HREF . "includes/plugin_signature/css/jquery.signaturepad.css"; ?>"
		      type="text/css"/>
		<form method="post" action="" class="sigPad">
			<input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
			<input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
			<input type="hidden" name="save_signature" value="1">

			<p class="nameDescription"><?php _e( $require_staff ? 'Enter staff name:' : 'Enter customer name:' ); ?> </p>
			<input type="text" name="name" id="name" class="name"
			       value="">

			<p class="drawItDesc"><?php _e( $require_staff ? 'Draw staff signature:' : 'Draw customer signature:' ); ?> <a
					href="#clear" class="clearButton"><?php _e( 'Clear' ); ?></a></p>

			<div class="sig sigWrapper">
				<div class="typed"></div>
				<canvas class="pad" width="350" height="100"></canvas>
				<input type="hidden" name="output" class="output">
			</div>
		</form>
		<script type="text/javascript">
        setTimeout(function () {
					<?php if(false && $existing_signature){ ?>
            $('.sigPad').signaturePad({
                drawOnly: true,
                lineTop: 80
            }).regenerate(<?php echo json_encode( json_decode( $existing_signature['signature'] ) );?>);
					<?php }else{ ?>
            $('.sigPad').signaturePad({drawOnly: true, lineTop: 80});
					<?php } ?>
        }, 300);
		</script>
		<?php
	}
}