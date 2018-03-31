<?php

if ( ! $ticket_safe ) {
	die( 'failed' );
}


/** TICKET MESSAGES */
?>

<div id="ticket_container"
     style="<?php echo module_config::c( 'ticket_scroll', 0 ) ? ' max-height: 400px; overflow-y:auto;' : ''; ?>">
	<?php
	$reply__ine_default = '----- (Please reply above this line) -----'; // incase they change it
	$reply__ine         = module_config::s( 'ticket_reply_line', $reply__ine_default );
	//$ticket_message_count = count($ticket_messages);
	$ticket_message_count   = ( $ticket_messages === false ) ? 0 : mysqli_num_rows( $ticket_messages );
	$ticket_message_counter = 0;

	$ticket_message_html_output = array(); // for the reverse feature...

	$show_messages_after = 0;
	if ( $ticket_message_count > module_config::c( 'ticket_hide_previous_messages', 5 ) && ! isset( $_REQUEST['show_all'] ) ) {
		// we want to show the last "ticket_hide_previous_messages" messages on the page, or if the latest messages are all from the user in a row we show all those.
		if ( module_config::c( 'ticket_hide_previous_messages_except_admin', 1 ) ) {
			$last_admin_message_count = 0;
			$x                        = 0;
			//foreach($ticket_messages as $ticket_message){
			while ( $ticket_message_count && $ticket_message = mysqli_fetch_assoc( $ticket_messages ) ) {
				$x ++;
				if ( ( $ticket_message['cache'] != 'autoreply' && $ticket_message['message_type_id'] != _TICKET_MESSAGE_TYPE_AUTOREPLY ) && isset( $admins_rel[ $ticket_message['from_user_id'] ] ) ) {
					// the admin created this message.
					$last_admin_message_count = $x;
				}
			}
			$show_messages_after = min( $ticket_message_count - module_config::c( 'ticket_hide_previous_messages', 5 ) + 1, $last_admin_message_count );
		} else {
			$show_messages_after = $ticket_message_count - module_config::c( 'ticket_hide_previous_messages', 5 ) + 1;
		}

		if ( $show_messages_after > 0 ) {
			ob_start();
			?>
			<div style="text-align: center;" id="show_previous_box">
				<a href="<?php echo htmlspecialchars( $_SERVER['REQUEST_URI'] ); ?>&show_all" id="show_previous_button"
				   class="uibutton"><?php _e( 'Show Previous %s Messages', $ticket_message_count - module_config::c( 'ticket_hide_previous_messages', 5 ) ); ?></a>
			</div>
			<?php
			$ticket_message_html_output[] = ob_get_clean();
		}
	}

	if ( isset( $_REQUEST['show_only_hidden'] ) ) {
		ob_end_clean();
		$ticket_message_html_output = array();
	}

	//foreach($ticket_messages as $ticket_message){
	if ( $ticket_message_count > 0 ) {
		mysqli_data_seek( $ticket_messages, 0 );
		while ( $ticket_message = mysqli_fetch_assoc( $ticket_messages ) ) {
			$ticket_message_counter ++;
			if ( isset( $_REQUEST['show_only_hidden'] ) ) {
				if ( $ticket_message_counter < $show_messages_after ) {
					// we want to show this message!!
				} else {
					// we want to skip this message, because it's already shown in the parent ajax loading screen.
					continue;
				}
			} else if ( $ticket_message_counter < $show_messages_after ) {
				// we want to skip this message.
				continue;
			}
			$attachments = module_ticket::get_ticket_message_attachments( $ticket_message['ticket_message_id'] );

			$last_response_from = isset( $admins_rel[ $ticket_message['from_user_id'] ] ) ? 'admin' : 'customer';

			ob_start();
			if ( $ticket_message['cache'] == 'autoreply' || $ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_AUTOREPLY ) {
				$last_response_from = 'autoreply';
				?>
				<a href="#" onclick="$(this).next('.ticket_message').show(); $(this).hide(); return false;"
				   class="show_auto_reply_button"><?php _e( 'Show auto reply &raquo;' ); ?></a>
			<?php } ?>
			<div class="ticket_message ticket_message_<?php
			echo isset( $admins_rel[ $ticket_message['from_user_id'] ] ) || $ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_ADMIN ? 'admin' : 'creator';
			//echo $ticket['user_id'] == $ticket_message['from_user_id'] ? 'creator' : 'admin';
			//echo $ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_CREATOR ? 'creator' : 'admin';
			echo $ticket_message['private_message'] ? ' ticket_message_private' : '';
			?>"<?php if ( $ticket_message['cache'] == 'autoreply' || $ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_AUTOREPLY ) {
				echo ' style="display:none;"';
			} ?>>
				<div class="ticket_message_title">
					<div class="ticket_message_title_summary">
						<strong><?php
							if ( module_security::get_loggedin_id() == $ticket_message['from_user_id'] ) {
								// this message was from me !
								echo _l( 'Me:' );
							} else {
								// this message was from someone else.
								// eg, the Customer, or the Response from admin.
								//if($ticket['user_id'] == $ticket_message['from_user_id']){
								if ( ! isset( $admins_rel[ $ticket_message['from_user_id'] ] ) && $ticket_message['message_type_id'] != _TICKET_MESSAGE_TYPE_ADMIN ) {
									echo _l( 'Customer:' );
								} else {
									echo _l( 'Support:' );
								}
							}
							?></strong>
						<?php echo print_date( $ticket_message['message_time'] ); ?>
						<a href="#"
						   onclick="jQuery(this).parent().hide(); jQuery(this).parent().parent().find('.ticket_message_title_full').show(); return false;"><?php echo _l( 'more &raquo;' ); ?></a>
					</div>
					<div class="ticket_message_title_full">
						<?php
						$header_cache = @unserialize( $ticket_message['cache'] ); ?>

						<span>
                            <?php _e( 'Date:' ); ?> <strong>
          <?php echo print_date( $ticket_message['message_time'], true ); ?></strong>
                        </span><br/>
						<span>
                            <?php _e( 'From:' ); ?> <strong><?php
								if ( $header_cache && isset( $header_cache['from_email'] ) ) {
									echo htmlspecialchars( $header_cache['from_email'] );
								} else {
									$from_temp = module_user::get_user( $ticket_message['from_user_id'], false );
									echo htmlspecialchars( $from_temp['name'] ); ?> &lt;<?php echo htmlspecialchars( $from_temp['email'] ); ?>&gt;
								<?php } ?>
                                </strong>
                        </span><br/>
						<?php if ( ! isset( $ticket_message['private_message'] ) || ! $ticket_message['private_message'] ) { ?>
							<span>
                            <?php _e( 'To:' ); ?>
								<strong><?php
									$to_temp = array();
									if ( $ticket_message['to_user_id'] ) {
										$to_temp = module_user::get_user( $ticket_message['to_user_id'], false );
									} else {
										if ( $header_cache && isset( $header_cache['to_email'] ) ) {
											$to_temp['email'] = $header_cache['to_email'];
										}
									}
									if ( isset( $to_temp['name'] ) ) {
										echo htmlspecialchars( $to_temp['name'] );
									}
									if ( isset( $to_temp['email'] ) ) {
										?>
										&lt;<?php echo htmlspecialchars( $to_temp['email'] ); ?>&gt;
									<?php } ?>
                                </strong><?php
								// hack support for other to fields.
								if ( $header_cache && isset( $header_cache['to_emails'] ) && is_array( $header_cache['to_emails'] ) ) {
									foreach ( $header_cache['to_emails'] as $to_email_additional ) {
										if ( isset( $to_email_additional['address'] ) && strlen( $to_email_additional['address'] ) && $to_email_additional['address'] != $to_temp['email'] ) {
											echo ', <strong>';
											if ( isset( $to_email_additional['name'] ) ) {
												echo htmlspecialchars( $to_email_additional['name'] );
											}
											?> &lt;<?php echo htmlspecialchars( $to_email_additional['address'] ); ?>&gt; <?php
											echo '</strong>';
										}
									}
								}
								?>
                        </span><br/>
							<?php
						} else {
							?>
							<span>
                            <?php _e( 'Private Message' ); ?>
                            </span><br/>
							<?php
						}
						// hack support for other to fields.
						if ( $header_cache && isset( $header_cache['cc_emails'] ) && is_array( $header_cache['cc_emails'] ) && count( $header_cache['cc_emails'] ) ) {
							?>
							<span>
                                <?php _e( 'CC:' ); ?>
								<?php
								$donecc = false;
								foreach ( $header_cache['cc_emails'] as $cc_email_additional ) {
									if ( isset( $cc_email_additional['address'] ) && strlen( $cc_email_additional['address'] ) ) {
										if ( $donecc ) {
											echo ', ';
										}
										$donecc = true;
										echo '<strong>';
										if ( isset( $cc_email_additional['name'] ) ) {
											echo htmlspecialchars( $cc_email_additional['name'] );
										}
										?> &lt;<?php echo htmlspecialchars( $cc_email_additional['address'] ); ?>&gt; <?php
										echo '</strong>';
									}
								}
								?>
                            </span>  <br/>
							<?php
						}
						// hack support for other to fields.
						if ( $header_cache && isset( $header_cache['bcc_emails'] ) && is_array( $header_cache['bcc_emails'] ) && count( $header_cache['bcc_emails'] ) ) {
							?>
							<span>
                                <?php _e( 'BCC:' ); ?>
								<?php
								$donebcc = false;
								foreach ( $header_cache['bcc_emails'] as $bcc_email_additional ) {
									if ( isset( $bcc_email_additional['address'] ) && strlen( $bcc_email_additional['address'] ) ) {
										if ( $donebcc ) {
											echo ', ';
										}
										$donebcc = true;
										echo '<strong>';
										if ( isset( $bcc_email_additional['name'] ) ) {
											echo htmlspecialchars( $bcc_email_additional['name'] );
										}
										?> &lt;<?php echo htmlspecialchars( $bcc_email_additional['address'] ); ?>&gt; <?php
										echo '</strong>';
									}
								}
								?>
                            </span>  <br/>
							<?php
						}
						?>
						<?php if ( module_config::c( 'ticket_show_user_details', 1 ) && module_ticket::can_edit_tickets() ) { ?>
							<span>
                                <?php
                                if ( isset( $ticket_message['create_user_id'] ) && (int) $ticket_message['create_user_id'] > 0 ) { ?>
	                                <strong><?php echo module_user::link_open( $ticket_message['create_user_id'], true ); ?></strong>
	                                <?php
                                }
                                if ( isset( $ticket_message['status_id'] ) && $ticket_message['status_id'] > 0 ) {
	                                echo ' ';
	                                _e( 'changed ticket status to %s', '<strong>' . friendly_key( module_ticket::get_statuses(), $ticket_message['status_id'] ) . '</strong>' );
                                }
                                ?>
                            </span><br/>
						<?php } ?>
					</div>
					<?php
					if ( count( $attachments ) ) {
						echo '<span>';
						_e( 'Attachments:' );
						foreach ( $attachments as $attachment ) {
							if ( preg_match( '/\.(\w\w\w\w?)$/', $attachment['file_name'], $matches ) ) {
								switch ( strtolower( $matches[1] ) ) {
									case 'jpg':
									case 'jpeg':
									case 'gif':
									case 'png':
										if ( module_config::c( 'ticket_show_attachment_image_preview', 1 ) ) {
											$thumb_width = (int) module_config::c( 'file_image_preview_width', 100 );
											?>
											<div class="file_preview"
											     style="float:right; width:<?php echo $thumb_width + 10; ?>px; margin:3px; border:1px solid #CCC; text-align:center;">
												<div style="width:<?php echo $thumb_width + 10; ?>px; min-height:40px; ">
													<a
														href="<?php echo module_ticket::link_open_attachment( $ticket_id, $attachment['ticket_message_attachment_id'] ); ?>"
														class="file_image_preview"
														data-featherlight="image">
														<img src="<?php
														// echo _BASE_HREF . nl2br(htmlspecialchars($file_item['file_path']));
														echo module_ticket::link_open_attachment( $ticket_id, $attachment['ticket_message_attachment_id'] );;
														?>" width="<?php echo $thumb_width; ?>" alt="download" border="0">
													</a>
												</div>
												<a
													href="<?php echo module_ticket::link_open_attachment( $ticket_id, $attachment['ticket_message_attachment_id'] ); ?>"
													class="attachment_link"><?php echo htmlspecialchars( $attachment['file_name'] ); ?>
													(<?php echo file_exists( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ? frndlyfilesize( filesize( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ) : _l( 'File Not Found' ); ?>
													)</a>
											</div>
											<?php
											break;
										}
									default:
										?>
										<a
											href="<?php echo module_ticket::link_open_attachment( $ticket_id, $attachment['ticket_message_attachment_id'] ); ?>"
											class="attachment_link"><?php echo htmlspecialchars( $attachment['file_name'] ); ?>
											(<?php echo file_exists( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ? frndlyfilesize( filesize( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ) : _l( 'File Not Found' ); ?>
											)</a>
									<?php
								}
							}

						}
						echo '</span>';
					}
					?>
				</div>
				<div class="ticket_message_text">
					<?php

					// copied to ticket.php in autoresponder:
					// todo: move this out to a function in ticket.php
					/*if(preg_match('#<br[^>]*>#i',$ticket_message['content'])){
							$ticket_message['content'] = preg_replace("#\r?\n#",'',$ticket_message['content']);
					}*/
					/*if(isset($_REQUEST['ticket_page_debug'])){
							echo "UTF8 method: ".module_config::c('ticket_utf8_method',1). "<br>\n";
							echo "Cache: ".$ticket_message['cache']."<br>\n";
							echo "<hr> Raw Content: <hr>";
							echo $ticket_message['content'];
							echo "<hr> HTML Content: <hr>";
							echo $ticket_message['htmlcontent'];
							echo "<hr> Content: <hr>";
							echo htmlspecialchars($ticket_message['content']);
							echo "<hr>";

					}*/

					// do we use html or plain text?
					$text = '';
					if ( module_config::c( 'ticket_message_text_or_html', 'html' ) == 'html' ) {
						$text = $ticket_message['htmlcontent'];
						// linkify the text, without messing with existing <a> links. todo: move this into a global method for elsewhere (eg: eamils)
						//$text = preg_replace('@(?!(?!.*?<a)[^<]*<\/a>)(?:(?:https?|ftp|file)://|www\.|ftp\.)[-A-Z0-9+&#/%=~_|$?!:,.]*[A-Z0-9+&#/%=~_|$]@i','<a href="\0" target="_blank">\0</a>', $text );
					}
					if ( ! strlen( trim( $text ) ) ) {
						$text = $ticket_message['content'];
						$text = preg_replace( "#<br[^>]*>#i", '', $text );
						$text = preg_replace( '#(\r?\n\s*){2,}#', "\n\n", $text );
						switch ( module_config::c( 'ticket_utf8_method', 1 ) ) {
							case 1:
								$text = forum_text( $text, true );
								break;
							case 2:
								$text = forum_text( utf8_encode( $text ), true );
								break;
							case 3:
								$text = forum_text( utf8_encode( utf8_decode( $text ) ), true );
								break;
						}
					}


					if ( ( $ticket_message['cache'] == 'autoreply' || $ticket_message['message_type_id'] == _TICKET_MESSAGE_TYPE_AUTOREPLY ) && strlen( $ticket_message['htmlcontent'] ) > 2 ) {
						$text = $ticket_message['htmlcontent']; // override for autoreplies, always show as html.
					}

					if ( ( ! $text || ! strlen( $text ) ) && strlen( $ticket_message['content'] ) ) {
						$text = nl2br( $ticket_message['content'] );
					}

					$text       = preg_replace( "#<br[^>]>#i", "$0\n", $text );
					$text       = preg_replace( "#</p>#i", "$0\n", $text );
					$text       = preg_replace( "#</div>#i", "$0\n", $text );
					$lines      = explode( "\n", $text );
					$do_we_hide = count( $lines ) > 4 && module_config::c( 'ticket_hide_messages', 1 ) && $ticket_message_counter < $ticket_message_count && $ticket_message_count != 2;

					if ( $do_we_hide ){
					?>
					<div class="ticket_message_hider">
						<?php
						}

						//$blank_line_limit = module_config::c('ticket_message_max_blank_lines',1);
						if ( true ) {
							$hide__ines       = $print__ines = array();
							$blank_line_count = 0;
							foreach ( $lines as $line_number => $line ) {
								// hide anything after
								$line = trim( $line );
								//if(preg_replace('#[\r\n\s]*#','',$line)==='')$blank_line_count++;
								//else $blank_line_count=0;

								//if($blank_line_limit>0 && $blank_line_count>$blank_line_limit)continue;

								if (
									count( $hide__ines ) ||
									preg_match( '#^>#', $line ) ||
									preg_match( '#' . preg_quote( $reply__ine, '#' ) . '#ims', $line ) ||
									preg_match( '#' . preg_quote( $reply__ine_default, '#' ) . '#ims', $line )
								) {
									if ( ! count( $hide__ines ) && module_config::c( 'ticket_message_text_or_html', 'html' ) != 'html' ) {
										// move the line before if it exists.
										if ( isset( $print__ines[ $line_number - 1 ] ) ) {
											if ( trim( preg_replace( '#<br[^>]*>#i', '', $print__ines[ $line_number - 1 ] ) ) ) {
												$hide__ines[ $line_number - 1 ] = $print__ines[ $line_number - 1 ];
											}
											unset( $print__ines[ $line_number - 1 ] );
										}
										// move the line before if it exists.
										if ( isset( $print__ines[ $line_number - 2 ] ) ) {
											if ( trim( preg_replace( '#<br[^>]*>#i', '', $print__ines[ $line_number - 2 ] ) ) ) {
												$hide__ines[ $line_number - 2 ] = $print__ines[ $line_number - 2 ];
											}
											unset( $print__ines[ $line_number - 2 ] );
										}
										// move the line before if it exists.
										if ( isset( $print__ines[ $line_number - 3 ] ) && preg_match( '#^On #', trim( $print__ines[ $line_number - 3 ] ) ) ) {
											if ( trim( preg_replace( '#<br[^>]*>#i', '', $print__ines[ $line_number - 3 ] ) ) ) {
												$hide__ines[ $line_number - 3 ] = $print__ines[ $line_number - 3 ];
											}
											unset( $print__ines[ $line_number - 3 ] );
										}
									}
									$hide__ines [ $line_number ] = $line;
									unset( $print__ines[ $line_number ] );
								} else {
									// not hidden yet.
									$print__ines[ $line_number ] = $line;
								}
							}
							ksort( $hide__ines );
							ksort( $print__ines );
							//echo module_security::purify_html(implode("\n",$hide__ines)); echo '<hr>';
							echo module_security::purify_html( implode( "\n", $print__ines ) );
							//print_r($print__ines);
							if ( count( $hide__ines ) ) {
								echo '<a href="#" onclick="jQuery(this).parent().find(\'div\').show(); jQuery(this).hide(); return false;">' . _l( '- show quoted text -' ) . '</a> ';
								echo '<div style="display:none;">';
								echo module_security::purify_html( implode( "\n", $hide__ines ) );
								echo '</div>';
								//print_r($hide__ines);
							}
						} else {
							echo $text;
						}
						/*if($ticket_message['cache']=='autoreply'){
								?>
								</div>
								<?php
						}else */
						if ( $do_we_hide ){
						?>
					</div>
					<div>
                            <span class="shower">
                                <a href="#"
                                   onclick="jQuery(this).parent().parent().parent().find('.ticket_message_hider').addClass('ticket_message_hider_show'); jQuery(this).parent().parent().find('.hider').show(); jQuery(this).parent().hide();return false;"><?php _e( 'Show entire message &raquo;' ); ?></a>
                            </span>
						<span class="hider" style="display:none;">
                                <a href="#"
                                   onclick="jQuery(this).parent().parent().parent().find('.ticket_message_hider').removeClass('ticket_message_hider_show'); jQuery(this).parent().parent().find('.shower').show(); jQuery(this).parent().hide(); return false;"><?php _e( '&laquo; Hide message' ); ?></a>
                            </span>
					</div>
				<?php
				}
				?>
				</div>
			</div>
			<?php

			$ticket_message_html_output [] = ob_get_clean();

		}
	}
	if ( isset( $_REQUEST['show_only_hidden'] ) ) {
		if ( module_config::c( 'ticket_messages_in_reverse', 0 ) ) {
			$ticket_message_html_output = array_reverse( $ticket_message_html_output );
		}
		echo implode( '', $ticket_message_html_output );
		exit;
	}


	// can this user write a reply?
	if ( $ticket['assigned_user_id'] || $ticket['user_id'] == module_security::get_loggedin_id() ) {

		ob_start();
		/*if(false && count($ticket_messages)){ ?>
		<div id="ticket_reply_button">
				<input type="button" name="reply" onclick="jQuery('#ticket_reply_button').hide(); jQuery('#ticket_reply_holder').show(); jQuery('#new_ticket_message')[0].focus(); return false;" value="<?php echo _l('Reply to ticket');?>" class="submit_button">
		</div>
		<div style="display: none;" class="ticket_reply" id="ticket_reply_holder">
		<?php }else{*/
		?>
		<div id="ticket_reply_holder" class="ticket_reply">

			<?php /* } */ ?>

			<div class="ticket_message ticket_message_<?php
			echo $ticket['user_id'] == module_security::get_loggedin_id() ? 'creator' : 'admin';
			?>">
				<div class="ticket_message_title" style="text-align: left;">
					<?php if ( module_ticket::can_edit_tickets() ) { ?>
						<div style="float:right; margin: -3px 5px 0 0;">
							<div id="canned_response"> <!-- style="display:none;"  -->
								<?php
								$canned_responses = module_ticket::get_saved_responses();
								echo print_select_box( $canned_responses, 'canned_response_id', '', '', true, '', true );
								?>
								<input type="button" name="s" id="save_saved" value="<?php _e( 'Save' ); ?>"
								       class="small_button">
								<input type="button" name="i" id="insert_saved" value="<?php _e( 'Insert' ); ?>"
								       class="small_button">
							</div>
							<!-- <a href="#" onclick="$('#canned_response').show(); $(this).hide(); return false;"><?php _e( 'Saved Response' ); ?></a> -->
						</div>
					<?php } ?>
					<strong><?php echo _l( 'Enter Your Message:' ); ?></strong>
					<?php if ( module_config::c( 'ticket_allow_private_messages', 1 ) && module_ticket::can_edit_tickets() ) { ?>
						<input type="checkbox" name="private_message" id="private_message" value="1"> <label
							for="private_message"><?php _e( 'Private' ); ?></label> <?php _h( 'If this message is private only staff members will be able to see it. This message will not be sent or visible to the customer.' ); ?>
					<?php } ?>
				</div>
				<div class="ticket_message_text">


					<script type="text/javascript">
              var done_auto_insert = false;

              function tinymce_focus() {
                  // if the user has entered a default reply, insert it here.
								<?php
								//module_template::init_template('ticket_reply_default','','Default reply text to appear when admin replies to a ticket');
								$template = module_template::get_template_by_key( 'ticket_reply_default_' . module_security::get_loggedin_id() );
								if ( ! $template->template_id ) {
									$template = module_template::get_template_by_key( 'ticket_reply_default' );
								}
								if($template->template_id){ ?>
                  if (!done_auto_insert) {
                      done_auto_insert = true;
                      ucm.ticket.add_to_message("<?php echo preg_replace( "#[\r\n]+#", '', addcslashes( $template->content, '"' ) );?>");
                  }
								<?php } ?>

              }

              function tinymce_blur() {

              }
					</script>

					<?php module_form::generate_form_element( array(
						'type'    => 'wysiwyg',
						'name'    => 'new_ticket_message',
						'value'   => '',
						'options' => array(
							'inline' => false
						),
					) ); ?>
					<table class="tableclass tableclass_full tableclass_form">
						<tbody>

						<?php if ( module_config::c( 'ticket_allow_attachment', 1 ) ) { ?>
							<tr>
								<th>
									<?php _e( 'Add Attachment' ); ?>
								</th>
								<td align="left">
									<div id="file_attachment_holder">
										<div class="dynamic_block">
											<div style="float:left;">
												<input type="file" name="attachment[]">
											</div>
											<div style="float:left; padding: 4px 0 0 10px;">
												<a href="#" class="add_addit" onclick="return seladd(this);">+</a>
												<a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
											</div>
										</div>
									</div>
									<script type="text/javascript">
                      set_add_del('file_attachment_holder');
									</script>

								</td>
							</tr>
						<?php } ?>

						<?php if ( module_ticket::can_edit_tickets() ) { ?>

							<tr>
								<th>
									<?php _e( 'Change status %s to:', friendly_key( module_ticket::get_statuses(), $ticket['status_id'] ) ); ?>
								</th>
								<td align="left">
									<?php
									$current_status = $ticket['status_id'];
									//if ( count( $ticket_messages ) ) {
									if ( $ticket_message_count ) {
										if ( $current_status <= 2 ) {
											//$current_status = 3; // change to replied
											$current_status = module_config::c( 'ticket_reply_status_id', _TICKET_STATUS_RESOLVED_ID ); // resolved
										} else {
											//$current_status = 5; // change to in progress
											$current_status = module_config::c( 'ticket_reply_status_id', _TICKET_STATUS_RESOLVED_ID ); // resolved
										}
									} else {
										$current_status = _TICKET_STATUS_NEW_ID;
									}
									echo print_select_box( module_ticket::get_statuses(), 'change_status_id', $current_status );
									?>
									<span id="data_change_status_id" data-status="<?php echo (int) $current_status; ?>"></span>
								</td>
							</tr>
							<?php if ( module_config::c( 'ticket_show_change_staff', 0 ) && $ticket['assigned_user_id'] != module_security::get_loggedin_id() && isset( $admins_rel[ module_security::get_loggedin_id() ] ) ) {
								?>
								<tr>
									<td colspan="2">
										<?php _e( 'This ticket is currently assigned to %s, you can change it below:', isset( $admins_rel[ $ticket['assigned_user_id'] ] ) ? $admins_rel[ $ticket['assigned_user_id'] ] : _l( 'Nobody' ) ); ?>
									</td>
								</tr>
								<tr>
									<th>
										<?php _e( 'Change staff:' ); ?>
									</th>
									<td align="left">
										<?php
										echo print_select_box( $admins_rel, 'change_assigned_user_id', $ticket['assigned_user_id'] );
										if ( module_security::get_loggedin_id() != $ticket['assigned_user_id'] ) {
											?>
											<span>
							            <a href="#" id="change_to_me"
							               data-user-id="<?php echo module_security::get_loggedin_id(); ?>"><?php _e( 'Change to Me' ); ?></a>
						            </span>
											<?php
										}
										?>
									</td>
								</tr>
							<?php } ?>

						<?php } ?>

						</tbody>
						<?php if ( module_ticket::can_edit_tickets() && module_config::c( 'ticket_allow_cc_bcc', 1 ) ) { ?>
							<tbody id="ticket_cc_bcc" style="display:none;">
							<tr>
								<th>
									<?php _e( 'Email Staff' ); ?>
								</th>
								<td align="left">
									<?php _e( 'Send a copy of this message to other staff members:' ); ?>
									<br/>
									<?php foreach ( $admins_rel as $staff_id => $staff_name ) { ?>
										<input type="checkbox" name="ticket_cc_staff[<?php echo $staff_id; ?>]" value="1"
										       id="ticket_cc_staff_<?php echo $staff_id; ?>"> <label
											for="ticket_cc_staff_<?php echo $staff_id; ?>"><?php echo htmlspecialchars( $staff_name ); ?></label>
										<br/>
									<?php } ?>
								</td>
							</tr>
							<tr>
								<th>
									<?php _e( 'Email CC' ); ?>
								</th>
								<td align="left">
									<input type="text" name="ticket_cc"
									       class="email_input"> <?php _h( 'Enter a list of email addresses here (comma separated) and this ticket message will be carbon copied to these recipients.  These recipients can reply to the ticket and their reply will appeear here in the ticketing system if you have POP3/IMAP setup correctly.' ); ?>
								</td>
							</tr>
							<tr>
								<th>
									<?php _e( 'Email BCC' ); ?>
								</th>
								<td align="left">
									<input type="text" name="ticket_bcc"
									       class="email_input"> <?php _h( 'Enter a list of email addresses here (comma separated) and this ticket message will be blind carbon copied to these recipients. These recipients can reply to the ticket and their reply will appeear here in the ticketing system if you have POP3/IMAP setup correctly.' ); ?>
								</td>
							</tr>
							</tbody>
						<?php } ?>
						<tbody>
						<?php /* <tr>
                            <td align="right">
                                 <?php _e('Send message as:');?>
                            </td>
                            <td align="left">
                                <input type="hidden" name="creator_id" value="<?php echo module_security::get_loggedin_id();?>">
                                <input type="hidden" name="creator_hash" value="<?php echo module_ticket::creator_hash(module_security::get_loggedin_id());?>">
                                <strong>
                                <?php echo htmlspecialchars($send_as_name);?>
                                &lt;<?php echo htmlspecialchars($send_as_address);?>&gt;
                                </strong>
                                <?php _e('Reply To:');?> <strong><?php echo htmlspecialchars($to_user_a['email']);?></strong>
                            </td>
                        </tr> */
						?>
						</tbody>
					</table>

					<?php
					$form_actions = array(
						'class'    => 'action_bar action_bar_center action_bar_single',
						'elements' => array(),
					);
					if ( module_ticket::can_edit_tickets() && module_config::c( 'ticket_allow_cc_bcc', 1 ) ) {
						$form_actions['elements'][] = array(
							'type'    => 'button',
							'name'    => 'show_cc_bcc',
							'value'   => _l( 'Add CC/BCC' ),
							'onclick' => "$('#ticket_cc_bcc').show(); $(this).hide();",
						);
					}
					if ( $next_ticket ) {
						$form_actions['elements'][] = array(
							'type'  => 'submit',
							'class' => 'submit_button',
							'name'  => 'newmsg',
							'value' => _l( 'Submit Message' ),
						);
						$form_actions['elements'][] = array(
							'type'  => 'save_button',
							'name'  => 'newmsg_next',
							'value' => _l( 'Submit Message & Go To Next Ticket' ),
						);
						$form_actions['elements'][] = array(
							'type'  => 'hidden',
							'name'  => 'next_ticket_id',
							'value' => $next_ticket,
						);
					} else if ( $prev_ticket ) {
						$form_actions['elements'][] = array(
							'type'  => 'save_button',
							'name'  => 'newmsg',
							'value' => _l( 'Submit Message' ),
						);
						$form_actions['elements'][] = array(
							'type'  => 'save_button',
							'name'  => 'newmsg_next',
							'value' => _l( 'Submit Message & Go To Prev Ticket' ),
						);
						$form_actions['elements'][] = array(
							'type'  => 'hidden',
							'name'  => 'next_ticket_id',
							'value' => $prev_ticket,
						);
					} else {
						$form_actions['elements'][] = array(
							'type'  => 'save_button',
							'name'  => 'newmsg',
							'value' => _l( 'Submit Message' ),
						);
					}
					echo module_form::generate_form_actions( $form_actions );

					?>


				</div>
			</div>
		</div>
		<?php

		$ticket_message_html_output [] = ob_get_clean();
	}

	if ( $tickets_in_reverse ) {
		$ticket_message_html_output = array_reverse( $ticket_message_html_output );
	}

	$fieldset_data = array(
		'heading'         => array(
			'title' => _l( 'Ticket Messages' ),
			'type'  => 'h3',
		),
		'elements_before' => implode( '', $ticket_message_html_output ),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

	?>
</div>
