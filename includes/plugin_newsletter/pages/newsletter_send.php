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

<form action="" method="post">
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
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<td colspan="3">
							<?php _e( 'Please select the groups you would like to send this email to:' ); ?>
						</td>
					</tr>
					<?php
					// grab a list of groups from the "group" plugin
					// group plugin allows us to group people by different categories throu out the application
					$groups = module_group::get_groups();
					foreach ( $groups as $group ) {
						?>
						<tr>
							<td>
								<input type="checkbox" name="group[<?php echo $group['group_id']; ?>]"
								       id="group_<?php echo $group['group_id']; ?>" value="yes">
							</td>
							<td>
								<label
									for="group_<?php echo $group['group_id']; ?>"><?php echo htmlspecialchars( $group['name'] ); ?></label>
							</td>
							<td>
								( <?php echo $group['count']; ?> <?php echo htmlspecialchars( _l( $group['owner_table'] . 's' ) ); ?> <?php
								// work out how many people in this group are valid
								?>)
							</td>
						</tr>
						<?php
					}
					?>
					<?php /*if(class_exists('module_company',false) && module_company::can_i('view','Company') && module_company::is_enabled()){
                        $companys = module_company::get_companys();
                        if(count($companys)>0){
                            ?>
                            <tr>
                                <td colspan="3">
                                    <?php _e('Please select the groups you would like to send this email to:');?>
                                </td>
                            </tr>
                            <?php
                            foreach($companys as $company){ ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="company[<?php echo $company['company_id'];?>]" id="customer_company_<?php echo $company['company_id'];?>" value="yes">
                                    </td>
                                    <td>
                                        <label for="customer_company_<?php echo $company['company_id'];?>"><?php echo htmlspecialchars($company['name']);?></label>
                                    </td>
                                    <td>

                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php }*/ ?>
					</tbody>
				</table>
			</td>
			<td width="50%" valign="top">
				<?php print_heading( array(
					'type'  => 'h3',
					'title' => 'Send Options',
				) ); ?>
				<table class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1"><?php _e( 'Schedule send' ); ?></th>
						<td>
							<input type="text" name="start_time" value="<?php
							if ( $send_id && $send && $send['start_time'] > time() ) {
								echo date( 'Y-m-d H:i', $send['start_time'] );
							}
							//echo date('Y-m-d H:i');?>" class="date_time_field"> (YYYY-MM-DD HH:MM)
							<br/>
							<?php echo _l( 'Current server time is %s', date( 'Y-m-d H:i' ) ); ?>
						</td>
					</tr>
					<?php // has this been sent before?
					if ( $sends_warning ) {
						?>
						<tr>
							<th><?php _e( 'Duplicates' ); ?></th>
							<td>
								<?php _e( 'This newsletter has been sent before (%s). Would you like to send it to people who have received this newsletter before?', $sends_links ); ?>
								<select name="allow_duplicates">
									<option value="0"><?php _e( 'No' ); ?></option>
									<option value="1"><?php _e( 'Yes' ); ?></option>
								</select>
							</td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
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

</form>