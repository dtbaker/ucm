<?php
$module->page_title = _l( 'Send' );


// check permissions.
if ( class_exists( 'module_security', false ) ) {
	module_security::check_page( array(
		'category'  => 'Newsletter',
		'page_name' => 'Send Newsletter',
		'module'    => 'newsletter',
		'feature'   => 'view',
	) );
}


$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? (int) $_REQUEST['newsletter_id'] : false;
$newsletter    = module_newsletter::get_newsletter( $newsletter_id );
$past_sends    = $newsletter['sends'];


$send_id = isset( $_REQUEST['send_id'] ) ? (int) $_REQUEST['send_id'] : false;
if ( $send_id > 0 ) {
	$send = module_newsletter::get_send( $send_id );
	if ( $send['status'] == _NEWSLETTER_STATUS_PENDING || $send['status'] == _NEWSLETTER_STATUS_PAUSED ) {
		redirect_browser( module_newsletter::link_queue_watch( $newsletter_id, $send_id ) );
	}
	$send_members    = module_newsletter::get_send_members( $send_id );
	$recipient_count = mysqli_num_rows( $send_members );
	mysqli_free_result( $send_members );
	print_heading( _l( 'Add More Recipients (currently %s recipients): %s', $recipient_count, $newsletter['subject'] ) );
} else {
	print_heading( _l( 'Send Newsletter: %s', $newsletter['subject'] ) );
}

$sends_warning = array();
foreach ( $past_sends as $send ) {
	if ( $send['status'] != _NEWSLETTER_STATUS_NEW ) {
		// this newsletter has been sent before, or a pending send has been done before.
		$sends_warning[] = $send;
	}
}
$sends_links = '';
if ( count( $sends_warning ) ) {
	foreach ( $sends_warning as $send_warning ) {
		$sends_links .= ' <a href="' . module_newsletter::link_statistics( $send_warning['newsletter_id'], $send_warning['send_id'] ) . '">';
		$sends_links .= print_date( $send_warning['start_time'], true );
		$sends_links .= ' - ';
		$sends_links .= _l( 'View Statistics' );
		$sends_links .= '</a> ';
	}
}

?>

<?php module_form::print_form_open_tag( array( 'action' => '' ) ); ?>
	<input type="hidden" name="newsletter_id" value="<?php echo (int) $newsletter_id; ?>">
	<input type="hidden" name="send_id" value="<?php echo (int) $send_id; ?>">
	<input type="hidden" name="_process" value="enque_send">

	<p><?php _e( 'Please select the people you would like to send this newsletter to.' ); ?></p>

	<table width="100%" class="tableclass tableclass_full">
		<tbody>
		<tr>
			<td width="50%" valign="top">
				<?php print_heading( array(
					'type'  => 'h3',
					'title' => 'Select Recipients',
				) ); ?>
				<?php
				$recipient_elements = array(
					'intro' => array(
						'message' => _l( 'Please select the groups you would like to send this email to:' ),
					),
				);
				// grab a list of groups from the "group" plugin
				// group plugin allows us to group people by different categories throu out the application
				foreach ( module_group::get_groups() as $group ) {
					$group_id = (int) $group['group_id'];
					$recipient_elements[ 'group_' . $group_id ] = array(
						'title'  => $group['name'],
						'fields' => array(
							'<input type="checkbox" name="group[' . $group_id . ']"'
							. ' id="group_' . $group_id . '" value="yes">',
							'( ' . (int) $group['count'] . ' '
							. htmlspecialchars( _l( $group['owner_table'] . 's' ) ) . ')',
						),
					);
				}
				echo module_form::generate_fieldset( array(
					'class'    => 'tableclass tableclass_form tableclass_full',
					'elements' => $recipient_elements,
				) );
				?>
			</td>
			<td width="50%" valign="top">
				<?php print_heading( array(
					'type'  => 'h3',
					'title' => 'Send Options',
				) ); ?>
				<?php
				$option_elements = array(
					'start_time' => array(
						'title'  => _l( 'Schedule send' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'start_time',
								'class' => 'date_time_field',
								'value' => ( $send_id && $send && $send['start_time'] > time() )
									? date( 'Y-m-d H:i', $send['start_time'] ) : '',
							),
							'(YYYY-MM-DD HH:MM)<br/>' . _l( 'Current server time is %s', date( 'Y-m-d H:i' ) ),
						),
					),
				);
				// has this been sent before?
				if ( $sends_warning ) {
					$option_elements['allow_duplicates'] = array(
						'title'  => _l( 'Duplicates' ),
						'fields' => array(
							_l( 'This newsletter has been sent before (%s). Would you like to send it to people who have received this newsletter before?', $sends_links ),
							array(
								'type'    => 'select',
								'name'    => 'allow_duplicates',
								'blank'   => false,
								'options' => array( 0 => _l( 'No' ), 1 => _l( 'Yes' ) ),
								'value'   => 0,
							),
						),
					);
				}
				echo module_form::generate_fieldset( array(
					'class'    => 'tableclass tableclass_form tableclass_full',
					'elements' => $option_elements,
				) );
				?>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center">
				<input type="button" name="cancel" value="<?php _e( 'Cancel' ); ?>" class="submit_button"
				       onclick="window.location.href='<?php echo module_newsletter::link_open( $newsletter_id ); ?>';">
				<input type="submit" name="send" value="<?php _e( 'Next Step' ); ?>" class="save_button submit_button">
			</td>
		</tr>
		</tbody>
	</table>

<?php module_form::print_form_close_tag();
