<form action="" method="post" enctype="multipart/form-data">
	<input type="hidden" name="_process" value="send_public_ticket">
	<table cellpadding="10" width="100%">
		<tbody>
		<tr>
			<td valign="top" width="35%">

				<?php ob_start(); ?>
				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
					<tbody>
					<tr>
						<th class="width1">
							<?php echo _l( 'Ticket Number' ); ?>
						</th>
						<td>
							<?php echo module_ticket::ticket_number( $ticket['ticket_id'] ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e( 'Subject' ); ?>
						</th>
						<td>
							<?php if ( $ticket['subject'] ) {
								echo htmlspecialchars( $ticket['subject'] );
							} else { ?>
								<input type="text" name="subject" id="subject"
								       value="<?php echo htmlspecialchars( $ticket['subject'] ); ?>"/>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Assigned User' ); ?>
						</th>
						<td>
							<?php
							echo isset( $admins_rel[ $ticket['assigned_user_id'] ] ) ? $admins_rel[ $ticket['assigned_user_id'] ] : 'N/A';
							?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Type/Department' ); ?>
						</th>
						<td>
							<?php
							$types = module_ticket::get_types();
							echo h( isset( $types[ $ticket['ticket_type_id'] ] ) ? $types[ $ticket['ticket_type_id'] ]['name'] : '' ); ?>
						</td>
					</tr>
					<tr>
						<th>
							<?php echo _l( 'Status' ); ?>
						</th>
						<td>
							<?php
							//echo print_select_box(module_ticket::get_statuses(),'status_id',$ticket['status_id'],'',true,false,true);
							$s = module_ticket::get_statuses();
							echo $s[ $ticket['status_id'] ];
							if ( $ticket['status_id'] == 2 || $ticket['status_id'] == 3 || $ticket['status_id'] == 5 ) {
								if ( module_config::c( 'ticket_show_position', 1 ) ) {
									echo ' ';
									echo _l( '(%s out of %s tickets)', ordinal( $ticket['position'] ), module_ticket::ticket_count( 'pending' ) );
								}
							}
							?>
						</td>
					</tr>
					</tbody>
				</table>

				<?php

				$fieldset_data = array(
					'heading'         => array(
						'title' => _l( 'Ticket Details' ),
						'type'  => 'h3',
					),
					'elements_before' => ob_get_clean(),
				);
				echo module_form::generate_fieldset( $fieldset_data );
				unset( $fieldset_data );


				if ( file_exists( dirname( __FILE__ ) . '/../inc/ticket_priority_sidebar.php' ) ) {
					include( dirname( __FILE__ ) . '/../inc/ticket_priority_sidebar.php' );
				}
				if ( file_exists( dirname( __FILE__ ) . '/../inc/ticket_extras_sidebar.php' ) ) {
					$extras_editable = false;
					include( dirname( __FILE__ ) . '/../inc/ticket_extras_sidebar.php' );
				}

				ob_start();
				?>

				<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
					<tbody>

					<tr>
						<th class="width1">
							<?php echo _l( 'Customer' ); ?>
						</th>
						<td>
							<?php
							$c   = array();
							$res = module_customer::get_customers();
							while ( $row = array_shift( $res ) ) {
								$c[ $row['customer_id'] ] = $row['customer_name'];
							}
							//echo print_select_box($c,'customer_id',$ticket['customer_id']);
							echo isset( $c[ $ticket['customer_id'] ] ) ? $c[ $ticket['customer_id'] ] : 'N/A';
							?>
						</td>
					</tr>
					<?php if ( $ticket['customer_id'] ) { ?>
						<tr>
							<th>
								<?php echo _l( 'Contact' ); ?>
							</th>
							<td>
								<?php
								$c   = array();
								$res = module_user::get_contacts( array( 'customer_id' => $ticket['customer_id'] ) );
								while ( $row = array_shift( $res ) ) {
									$c[ $row['user_id'] ] = $row['name'];
								}
								//echo print_select_box($c,'user_id',$ticket['user_id']);
								echo isset( $c[ $ticket['user_id'] ] ) ? $c[ $ticket['user_id'] ] : 'N/A';
								?>
							</td>
						</tr>
						<?php if ( $ticket['website_id'] ) { ?>
							<tr>
								<th>
									<?php echo _l( '' . module_config::c( 'project_name_single', 'Website' ) ); ?>
								</th>
								<td>
									<?php
									$c   = array();
									$res = module_website::get_websites( array( 'customer_id' => $ticket['customer_id'] ) );
									while ( $row = array_shift( $res ) ) {
										$c[ $row['website_id'] ] = $row['name'];
									}
									//echo print_select_box($c,'website_id',$ticket['website_id']);
									echo isset( $c[ $ticket['website_id'] ] ) ? $c[ $ticket['website_id'] ] : 'N/A';
									?>
								</td>
							</tr>
						<?php } ?>
					<?php } ?>
					</tbody>
				</table>
				<?php $fieldset_data = array(
					'heading'         => array(
						'title' => _l( 'Related To' ),
						'type'  => 'h3',
					),
					'elements_before' => ob_get_clean(),
				);
				echo module_form::generate_fieldset( $fieldset_data );
				unset( $fieldset_data );

				handle_hook( 'ticket_sidebar', $ticket_id ); ?>

			</td>
			<td valign="top">
				<h3><?php echo _l( 'Ticket Messages' ); ?></h3>

				<div id="ticket_container"
				     style="<?php echo module_config::c( 'ticket_scroll', 0 ) ? ' max-height: 400px; overflow-y:auto;' : ''; ?>">
					<?php
					$ticket_messages        = module_ticket::get_ticket_messages( $ticket_id );
					$reply__ine_default     = '----- (Please reply above this line) -----'; // incase they change it
					$reply__ine             = module_config::s( 'ticket_reply_line', $reply__ine_default );
					$ticket_message_count   = count( $ticket_messages );
					$ticket_message_counter = 0;

					foreach ( $ticket_messages as $ticket_message ) {
						if ( $ticket_message['private_message'] ) {
							continue;
						}
						$ticket_message_counter ++;
						$attachments = module_ticket::get_ticket_message_attachments( $ticket_message['ticket_message_id'] );
						?>
						<div class="ticket_message ticket_message_<?php
						//echo $ticket['user_id'] == $ticket_message['from_user_id'] ? 'creator' : 'admin';

						echo ! isset( $admins_rel[ $ticket_message['from_user_id'] ] ) ? 'creator' : 'admin';
						?>">
							<div class="ticket_message_title">
								<div class="ticket_message_title_summary">
									<strong><?php
										if ( isset( $logged_in_user ) && $logged_in_user == $ticket_message['from_user_id'] ) {
											// this message was from me !
											echo _l( 'Me:' );
										} else {
											// this message was from someone else.
											// eg, the Customer, or the Response from admin.
											//if($ticket['user_id'] == $ticket_message['from_user_id']){
											if ( ! isset( $admins_rel[ $ticket_message['from_user_id'] ] ) ) {
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
                                                            </span>
									<span>
                                                                <?php _e( 'From:' ); ?> <strong><?php
											if ( $header_cache && isset( $header_cache['from_email'] ) ) {
												echo htmlspecialchars( $header_cache['from_email'] );
											} else {
												$from_temp = module_user::get_user( $ticket_message['from_user_id'], false );
												echo htmlspecialchars( $from_temp['name'] ); ?> &lt;<?php echo htmlspecialchars( $from_temp['email'] ); ?>&gt;
											<?php } ?>
                                                                </strong>
                                                            </span>
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
											if ( isset( $to_temp['email'] ) ) { ?>
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
								</div>
								<?php
								if ( count( $attachments ) ) {
									echo '<span>';
									_e( 'Attachments:' );
									foreach ( $attachments as $attachment ) {
										?>
										<a
											href="<?php echo module_ticket::link_open_attachment( $ticket_id, $attachment['ticket_message_attachment_id'] ); ?>"><?php echo htmlspecialchars( $attachment['file_name'] ); ?>
											(<?php echo file_exists( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ? frndlyfilesize( filesize( 'includes/plugin_ticket/attachments/' . $attachment['ticket_message_attachment_id'] ) ) : _l( 'File Not Found' ); ?>
											)</a>
										<?php
									}
									echo '</span>';
								}
								?>
							</div>
							<div class="ticket_message_text">
								<?php /*//echo nl2br(htmlspecialchars($ticket_message['content'])); ?>
                                                        <?php
                                                        $ticket_message['content'] = preg_replace("#<br[^>]*>#",'',$ticket_message['content']);
                                                        switch(module_config::c('ticket_utf8_method',1)){
                                                            case 1:
                                                                $text = forum_text($ticket_message['content']);
                                                                break;
                                                            case 2:
                                                                $text = forum_text(utf8_encode($ticket_message['content']));
                                                                break;
                                                            case 3:
                                                                $text = forum_text(utf8_encode(utf8_decode($ticket_message['content'])));
                                                                break;
                                                        }

                                                        if($ticket_message['cache']=='autoreply' && strlen($ticket_message['htmlcontent'])>2){
                                                            $text = $ticket_message['htmlcontent'];
                                                        }

                                                        if(true){
                                                            $lines = explode("\n",$text);
                                                            $hide__ines = $print__ines = array();
                                                            foreach($lines as $line_number => $line){
                                                                // hide anything after
                                                                $line = trim($line);
                                                                if(
                                                                    count($hide__ines) ||
                                                                    preg_match('#^>#',$line) ||
                                                                    preg_match('#'.preg_quote($reply__ine,'#').'.*$#ims',$line) ||
                                                                    preg_match('#'.preg_quote($reply__ine_default,'#').'.*$#ims',$line)
                                                                ){
                                                                    if(!count($hide__ines)){
                                                                        // move the line before if it exists.
                                                                        if(isset($print__ines[$line_number-1])){
                                                                            if(trim(preg_replace('#<br[^>]*>#i','',$print__ines[$line_number-1]))){
                                                                                $hide__ines[$line_number-1] = $print__ines[$line_number-1];
                                                                            }
                                                                            unset($print__ines[$line_number-1]);
                                                                        }
                                                                        // move the line before if it exists.
                                                                        if(isset($print__ines[$line_number-2])){
                                                                            if(trim(preg_replace('#<br[^>]*>#i','',$print__ines[$line_number-2]))){
                                                                                $hide__ines[$line_number-2] = $print__ines[$line_number-2];
                                                                            }
                                                                            unset($print__ines[$line_number-2]);
                                                                        }
                                                                        // move the line before if it exists.
                                                                        if(isset($print__ines[$line_number-3]) && preg_match('#^On #',trim($print__ines[$line_number-3]))){
                                                                            if(trim(preg_replace('#<br[^>]*>#i','',$print__ines[$line_number-3]))){
                                                                                $hide__ines[$line_number-3] = $print__ines[$line_number-3];
                                                                            }
                                                                            unset($print__ines[$line_number-3]);
                                                                        }
                                                                    }
                                                                    $hide__ines [$line_number] = $line;
                                                                    unset($print__ines[$line_number]);
                                                                }else{
                                                                    // not hidden yet.
                                                                    $print__ines[$line_number] = $line;
                                                                }
                                                            }
                                                            ksort($hide__ines);
                                                            ksort($print__ines);
                                                            echo implode("\n",$print__ines);
                                                            //print_r($print__ines);
                                                            if(count($hide__ines)){
                                                                echo '<a href="#" onclick="jQuery(this).parent().find(\'div\').toggle(); return false;">'._l('- show quoted text -').'</a> ';
                                                                echo '<div style="display:none;">';
                                                                echo implode("\n",$hide__ines);
                                                                echo '</div>';
                                                                //print_r($hide__ines);
                                                            }
                                                        }else{
                                                            echo $text;
                                                        }*/
								?>
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
					<?php } ?>


					<?php
					if ( true ){ //$logged_in_user || is_user_logged_in()){
					?>

					<?php if ( count( $ticket_messages ) ){ ?>
					<div id="ticket_reply_button">
						<input type="button" name="reply"
						       onclick="jQuery('#ticket_reply_button').hide(); jQuery('#ticket_reply_holder').show(); jQuery('#new_ticket_message')[0].focus(); return false;"
						       value="<?php echo _l( 'Reply to ticket' ); ?>" class="submit_button">
					</div>
					<div style="display: none;" class="ticket_reply" id="ticket_reply_holder">
						<?php }else{ ?>
						<div id="ticket_reply_holder" class="ticket_reply">
							<?php } ?>

							<div class="ticket_message ticket_message_<?php
							//echo $ticket['user_id'] == module_security::get_loggedin_id() ? 'creator' : 'admin';
							echo isset( $admins_rel[ module_security::get_loggedin_id() ] ) ? 'admin' : 'creatorf';
							?>">
								<div class="ticket_message_title" style="text-align: left;">
									<strong><?php echo _l( 'Enter Your Message:' ); ?></strong>
								</div>
								<div class="ticket_message_text">


									<textarea rows="6" cols="20" name="new_ticket_message" id="new_ticket_message"></textarea>
									<?php if ( module_config::c( 'ticket_allow_attachment', 1 ) ) { ?>
										<div style="line-height: 25px; padding:10px;">
											<?php _e( 'Attachment' ); ?>
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
											<br class="clear">
										</div>
									<?php } ?>
									<div style="line-height: 25px; padding:10px;">
										<input type="hidden" name="creator_id" value="<?php echo module_security::get_loggedin_id(); ?>">
										<input type="hidden" name="creator_hash"
										       value="<?php echo module_ticket::creator_hash( module_security::get_loggedin_id() ); ?>">
										<?php _e( 'Send message as:' ); ?>
										<strong>
											<?php echo htmlspecialchars( $send_as_name ); ?>
											&lt;<?php echo htmlspecialchars( $send_as_address ); ?>&gt;
										</strong>
										<!-- <?php _e( 'Reply To:' ); ?> <strong><?php echo htmlspecialchars( $to_user_a['email'] ); ?></strong> -->
										<br/>
										<input type="submit" name="newmsg" value="<?php _e( 'Send Reply Message' ); ?>"
										       class="submit_button save_button">
									</div>


								</div>
							</div>
						</div>
						<?php } ?>
					</div>


			</td>
		</tr>
		<tr>
			<td align="center" colspan="2">

			</td>
		</tr>
		</tbody>
	</table>

</form>