<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}


if ( isset( $_POST['bulk_remove_priority'] ) && $_POST['bulk_remove_priority'] == 'go' ) {

	// get a list of unpaid ticket invoices
	$sql = "SELECT t.ticket_id, i.invoice_id FROM ";
	$sql .= " `" . _DB_PREFIX . "ticket` t ";
	$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice` i ON t.invoice_id = i.invoice_id ";
	$sql .= " WHERE i.invoice_id IS NOT NULL ";
	$sql .= " AND i.date_paid = '0000-00-00' ";
	$sql .= " AND i.date_create < '" . date( 'Y-m-d', strtotime( '-10 days' ) ) . "'  ";
	module_debug::log( array(
		'title' => 'Finding invoices...',
		'data'  => '',
	) );
	$invoices = qa( $sql );
	module_debug::log( array(
		'title' => 'Found ' . count( $invoices ) . ' invoices...',
		'data'  => '',
	) );
	foreach ( $invoices as $invoice ) {
		if ( ! $invoice['invoice_id'] ) {
			continue;
		}
		module_debug::log( array(
			'title' => 'Removing invoice: ',
			'data'  => $invoice['invoice_id'],
		) );
		$invoice_check = module_invoice::get_invoice( $invoice['invoice_id'] );
		module_debug::log( array(
			'title' => 'Removing invoice: (2) ',
			'data'  => $invoice['invoice_id'],
		) );
		if ( $invoice_check['invoice_id'] != $invoice['invoice_id'] ) {
			continue;
		}
		if ( $invoice_check['total_amount_paid'] <= 0 ) {
			// remove this invoice
			module_debug::log( array(
				'title' => 'Removing invoice: (3) ',
				'data'  => $invoice['invoice_id'],
			) );
			echo "Deleting invoice " . $invoice_check['name'] . " for " . dollar( $invoice_check['total_amount_due'], true, $invoice_check['currency_id'] ) . " from " . print_date( $invoice_check['date_create'] ) . " and ticket  " . module_ticket::link_open( $invoice['ticket_id'], true, $invoice_check ) . " <br>\n";
			module_invoice::delete_invoice( $invoice['invoice_id'] );
			module_debug::log( array(
				'title' => 'Removing invoice: (done)',
				'data'  => $invoice['invoice_id'],
			) );
		}
	}
	echo "Done";

} else if ( isset( $_POST['bulk_process_go'] ) && $_POST['bulk_process_go'] == 'true' ) {

	$bulk_at_a_time = 10;

	//    @apache_setenv('no-gzip', 1);
	//    @ini_set('zlib.output_compression', 0);
	//    @ini_set('implicit_flush', 1);
	for ( $i = 0; $i < ob_get_level(); $i ++ ) {
		ob_end_clean();
	}
	//    ob_implicit_flush(1);

	if ( count( $_SESSION['ticket_bulk_ticket_ids'] ) ) {

		for ( $x = 0; $x < $bulk_at_a_time; $x ++ ) {
			$ticket_id = (int) array_shift( $_SESSION['ticket_bulk_ticket_ids'] );
			if ( $ticket_id > 0 ) {
				// found a ticket to process! sweet!
				// update via js that we're working on this
				?>
				<script type="text/javascript">
            window.parent.document.getElementById('ticket_<?php echo $ticket_id;?>').innerHTML = 'Processing...';
				</script>
				<?php
				echo( str_repeat( ' ', 356 ) );
				@flush();

				// do the ticket processing.
				// assign a new status?
				if ( (int) $_SESSION['ticket_bulk_status_id'] > 0 ) {
					update_insert( 'ticket_id', $ticket_id, 'ticket', array( 'status_id' => $_SESSION['ticket_bulk_status_id'] ) );
				}
				if ( $_SESSION['ticket_bulk_send_message'] && $_SESSION['ticket_bulk_send_message_content'] ) {
					// send our reply! tricky!
					// who from? just like the admin is writing it I guess.
					// hack: so that the tickets do not loose their positions in the queue we want to keep the same 'last message' timestamp on the thread.
					$ticket_data  = module_ticket::get_ticket( $ticket_id );
					$from_user_id = $ticket_data['assigned_user_id'] ? $ticket_data['assigned_user_id'] : 1;

					// the <br> is a hack so that our script knows this is html.
					$message = $_SESSION['ticket_bulk_send_message_content'] . '<br><br>';
					// replace our values.
					$to_user = module_user::get_user( $ticket_data['user_id'], false );
					$replace = array(
						'name'                  => $to_user['name'],
						'ticket_id'             => module_ticket::ticket_number( $ticket_id ),
						'ticket_url'            => module_ticket::link_public( $ticket_id ),
						'ticket_url_cancel'     => module_ticket::link_public_status( $ticket_id, 7 ),
						'ticket_url_resolved'   => module_ticket::link_public_status( $ticket_id, 6 ),
						'ticket_url_inprogress' => module_ticket::link_public_status( $ticket_id, 5 ),
					);
					foreach ( $replace as $key => $val ) {
						$message = str_replace( '{' . strtoupper( $key ) . '}', $val, $message );
						$message = str_replace( '{' . ( $key ) . '}', $val, $message );
					}
					$ticket_message_id = module_ticket::send_reply( $ticket_id, $message, $from_user_id, $ticket_data['user_id'], 'admin' );
					if ( $ticket_message_id ) {
						// success!
						// do the timestamp.
						update_insert( 'ticket_message_id', $ticket_message_id, 'ticket_message', array(
							'message_time' => $ticket_data['last_message_timestamp'] + 1,
						) );
						update_insert( 'ticket_id', $ticket_id, 'ticket', array(
							'last_message_timestamp' => $ticket_data['last_message_timestamp'] + 1,
						) );
					}
				}

				?>
				<script type="text/javascript">
            window.parent.document.getElementById('ticket_<?php echo $ticket_id;?>').innerHTML = 'Done!';
				</script>
				<?php
				echo( str_repeat( ' ', 356 ) );
				@flush();


			}
		}

		?>
		<script type="text/javascript">
        window.parent.document.getElementById('process_button_form').submit();
		</script>
		<?php
		echo( str_repeat( ' ', 356 ) );
		@flush();

	} else {
		// all finished processing!

		?>
		<script type="text/javascript">
        alert('done');
		</script>
		<?php
		echo( str_repeat( ' ', 356 ) );
		@flush();
	}
	exit;

} else if ( isset( $_POST['bulk'] ) && $_POST['bulk'] == 'go' ) {

	// get the list of tickets in this group.
	$group_id = (int) $_REQUEST['group_id'];
	if ( ! $group_id ) {
		die( 'no group selected' );
	}
	$_SESSION['ticket_bulk_status_id']            = (int) $_REQUEST['status_id'];
	$_SESSION['ticket_bulk_send_message']         = isset( $_REQUEST['send_message'] ) ? (int) $_REQUEST['send_message'] : false;
	$_SESSION['ticket_bulk_send_message_content'] = $_REQUEST['send_message_content'];


	$sql     = "SELECT * FROM `" . _DB_PREFIX . "ticket` t ";
	$sql     .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (t.ticket_id = gm.owner_id)";
	$sql     .= " WHERE (gm.group_id = '$group_id' AND gm.owner_table = 'ticket')";
	$tickets = query( $sql );

	// store our actions in a session, load up all affected tickets in a table, iterate over tickets using
	// ajax and apply bulk action one after the other.
	// update interface so user can see progress.
	$ticket_count = mysqli_num_rows( $tickets );

	$ticket_ids = array();
	print_heading( 'Tickets' );
	?>

	<iframe src="about:blank" name="bulk_process" id="bulk_process" style="display:none;"></iframe>
	<form action="" method="post" target="bulk_process" id="process_button_form">
		<input type="hidden" name="bulk_process_go" value="true">
		<input type="submit" name="go" id="process_button" value="<?php _e( 'Start Processing Below Tickets' ); ?>"
		       onclick="this.value='<?php _e( 'Please wait...' ); ?>';">
	</form>

	<table class="tableclass tableclass_full tableclass_rows">
		<thead>
		<tr>
			<th> <?php _e( 'Ticket ID' ); ?> </th>
			<th> <?php _e( 'Subject' ); ?> </th>
			<th> <?php _e( 'Date' ); ?> </th>
			<th> <?php _e( 'Status' ); ?> </th>
			<th> <?php _e( 'Group' ); ?> </th>
			<th> <?php _e( 'Status' ); ?> </th>
		</tr>
		</thead>
		<tbody>
		<tbody>
		<?php
		$c                  = 0;
		$time               = time();
		$today              = strtotime( date( 'Y-m-d' ) );
		$seconds_into_today = $time - $today;
		$limit_time         = strtotime( '-' . module_config::c( 'ticket_turn_around_days', 5 ) . ' days', time() );
		while ( $ticket = mysqli_fetch_assoc( $tickets ) ) {
			$ticket       = module_ticket::get_ticket( $ticket['ticket_id'] );
			$ticket_ids[] = $ticket['ticket_id'];
			?>
			<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
				<td class="row_action" nowrap="">
					<?php echo module_ticket::link_open( $ticket['ticket_id'], true, $ticket ); ?>
					(<?php echo $ticket['message_count']; ?>)
				</td>
				<td>
					<?php
					// todo, pass off to envato module as a hook
					$ticket['subject'] = preg_replace( '#Message sent via your Den#', '', $ticket['subject'] );
					if ( $ticket['priority'] ) {

					}
					if ( $ticket['unread'] ) {
						echo '<strong>';
						echo ' ' . _l( '* ' ) . ' ';
						echo htmlspecialchars( $ticket['subject'] );
						echo '</strong>';
					} else {
						echo htmlspecialchars( $ticket['subject'] );
					}
					?>
				</td>
				<td>
					<?php
					if ( $ticket['last_message_timestamp'] > 0 ) {
						if ( $ticket['last_message_timestamp'] < $limit_time ) {
							echo '<span class="important">';
						}
						echo print_date( $ticket['last_message_timestamp'], true );
						// how many days ago was this?
						echo ' ';
						//echo '<br>'.$seconds_into_today ."<br>".($ticket['last_message_timestamp']+1).'<br>';
						if ( $ticket['last_message_timestamp'] >= $today ) {
							echo '<span class="success_text">';
							_e( '(today)' );
							echo '</span>';
						} else {
							$days = ceil( ( $today - $ticket['last_message_timestamp'] ) / 86400 );

							_e( ' (%s days)', abs( $days ) );
						}
						if ( $ticket['last_message_timestamp'] < $limit_time ) {
							echo '</span>';
						}
					}
					?>
				</td>
				<td>
					<?php echo htmlspecialchars( module_ticket::$ticket_statuses[ $ticket['status_id'] ] ); ?>
				</td>
				<?php if ( class_exists( 'module_group', false ) && module_config::c( 'ticket_enable_groups', 1 ) && module_group::groups_enabled() ) { ?>
					<td><?php
						// find the groups for this customer.
						$groups = module_group::get_groups_search( array(
							'owner_table' => 'ticket',
							'owner_id'    => $ticket['ticket_id'],
						) );
						$g      = array();
						foreach ( $groups as $group ) {
							$g[] = $group['name'];
						}
						echo implode( ', ', $g );
						?></td>
				<?php } ?>
				<td id="ticket_<?php echo $ticket['ticket_id']; ?>" class="ticket_status">
					<?php _e( 'Pending' ); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php $_SESSION['ticket_bulk_ticket_ids'] = $ticket_ids; ?>

	<?php

} else {
	?>


	<form action="" method="post">
		<input type="hidden" name="bulk" value="go">
		<h3><?php echo _l( 'Perform Bulk Actions on Tickets (BETA!)' ); ?></h3>

		<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
			<tbody>
			<tr>
				<th class="width2">
					<?php echo _l( 'Select Ticket Group' ); ?>
				</th>
				<td>
					<?php echo print_select_box( module_group::get_groups( 'ticket' ), 'group_id', false, '', true, 'name' ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<?php echo _l( 'Change Ticket Status' ); ?>
				</th>
				<td>
					<?php echo print_select_box( module_ticket::get_statuses(), 'status_id', '' ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<?php echo _l( 'Send Ticket Reply Message' ); ?>
				</th>
				<td>
					<input type="checkbox" name="send_message"
					       value="1"> <?php _e( 'Yes, send below message to each ticket in group:' ); ?>
					<div>
						<textarea name="send_message_content" id="send_message_content" rows="10" cols="30"
						          style="width:450px; height: 350px;"></textarea>

						<script type="text/javascript" src="<?php echo _BASE_HREF; ?>js/tiny_mce3.4.4/jquery.tinymce.js"></script>
						<script type="text/javascript">
                $().ready(function () {
                    $('#send_message_content').tinymce({
                        // Location of TinyMCE script
                        script_url: '<?php echo _BASE_HREF;?>js/tiny_mce3.4.4/tiny_mce.js',

                        relative_urls: false,
                        convert_urls: false,

                        // General options
                        theme: "advanced",
                        plugins: "autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist",

                        // Theme options
                        theme_advanced_buttons1: "undo,redo,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,formatselect,fontselect,fontsizeselect",
                        theme_advanced_buttons2: "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,link,unlink,anchor,image,cleanup,code,|,forecolor,backcolor",
                        theme_advanced_buttons3: "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell",
                        /*theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
												theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
												theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
												theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",*/
                        theme_advanced_toolbar_location: "top",
                        theme_advanced_toolbar_align: "left",
                        theme_advanced_statusbar_location: "bottom",
                        theme_advanced_resizing: true,

                        height: '300px',
                        width: '100%'

                    });
                });
						</script>
					</div>
					<pre>example message:

						<?php echo htmlspecialchars( "<p>Hello {NAME},</p>
<p>This ticket has been automatically closed, to re-open this ticket please click the button below.</p>
<p><span style=\"font-size: medium;\"><strong><a href=\"{TICKET_URL_INPROGRESS}\">Yes, please re-open this ticket.</a></strong></span></p>
<p><span style=\"font-size: xx-small;\">(if that doesn't work, try this link: {TICKET_URL_INPROGRESS})</span></p>
<p>To view your support ticket please <a href=\"{TICKET_URL}\">click here</a>.</p>
<p>Thanks for your patience,<br /> dtbaker</p>" ); ?></pre>
				</td>
			</tr>
			<tr>
				<td align="center" colspan="2">
					<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l( 'Perform Bulk Actions' ); ?>"
					       class="submit_button save_button"/>

				</td>
			</tr>
			</tbody>
		</table>


	</form>


	<form action="" method="post">
		<input type="hidden" name="bulk_remove_priority" value="go">
		<h3><?php echo _l( 'Remove old Unpaid Priority Support Invoices' ); ?></h3>
		<table cellpadding="10" width="100%" class="tableclass tableclass_form">
			<tr>
				<td valign="top" align="center">
					<input type="submit" name="buttremove" id="buttremove"
					       value="<?php echo _l( 'Remove Unpaid Support Invoices' ); ?>" class="submit_button save_button"/>
				</td>
			</tr>
		</table>
	</form>

<?php } ?>