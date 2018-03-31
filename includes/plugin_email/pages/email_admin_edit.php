<?php

$email_id    = isset( $_REQUEST['email_id'] ) ? (int) $_REQUEST['email_id'] : false;
$customer_id = isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : false;

if ( ! $customer_id ) {
	echo 'please select a customer first';
	exit;
}
$email = module_email::get_email( $email_id );
if ( ! count( $email ) || $email['email_id'] != $email_id ) {
	$email_id = false;
	$email    = false;
}

$page_title = 'Email';

if ( ! $email_id ) {
	// creating a new email
	$can_edit_emails = true;
	$page_title      = 'Create Email';
} else {
	switch ( $email['status'] ) {
		case _MAIL_STATUS_SCHEDULED:
			$can_edit_emails = false;
			$page_title      = 'Scheduled Email';
			break;
		default:
			$can_edit_emails = false; // don't want to edit existing email
	}

}


$current_template     = isset( $_REQUEST['template_name'] ) ? $_REQUEST['template_name'] : 'email_template_blank';
$find_other_templates = 'email_template_';

$to  = module_user::get_contacts( array( 'customer_id' => $customer_id ) );
$bcc = module_config::c( 'admin_email_address', '' );
if ( $bcc_option = module_config::c( 'email_bcc', 1 ) ) {
	$bcc = strlen( $bcc_option > 4 ) ? $bcc_option : module_config::c( 'admin_email_address', '' );
} else {
	$bcc = '';
}

$headers = @unserialize( $email['headers'] );

if ( $current_template && ! $email_id ) {
	$template = module_template::get_template_by_key( $current_template );
	//todo: replace fields.
	//$replace = module_invoice::get_replace_fields($invoice_id,$invoice);
	if ( $email['customer_id'] ) {
		$customer_data = module_customer::get_customer( $email['customer_id'] );
		$replace       = module_customer::get_replace_fields( $email['customer_id'], false, $customer_data );
		$template->assign_values( $replace );
	}
	if ( $email['job_id'] ) {
		$job_data = module_job::get_job( $email['job_id'] );
		$replace  = module_job::get_replace_fields( $email['job_id'], $job_data );
		$template->assign_values( $replace );
	}
	if ( $email['website_id'] ) {
		$website_data = module_website::get_website( $email['website_id'] );
		$replace      = module_website::get_replace_fields( $email['website_id'], $website_data );
		$template->assign_values( $replace );
	}
	$email['text_content'] = $template->render( 'html' );
	$email['subject']      = $template->replace_description();
}

$options = array(
	'cancel_url'   => module_email::link_open( false ),
	'complete_url' => module_email::link_open( false ),
	'customer_id'  => $customer_id,
);
$options = module_email::get_email_compose_options( $options );

?>

<?php if ( $can_edit_emails ){ ?>
	<form action="" method="post" id="template_change_form">
		<input type="hidden" name="template_name" value="" id="template_name_change">
	</form>

<form action="" method="post" enctype="multipart/form-data" id="email_send_form">
	<input type="hidden" name="_process" id="process_tag" value="send_email">
	<input type="hidden" name="email_id" id="email_id" value="<?php echo (int) $email_id; ?>">
	<input type="hidden" name="options" value="<?php echo base64_encode( serialize( $options ) ); ?>">

	<?php
	module_form::print_form_auth();
	}


	$fieldset_data = array(
		'id'             => 'email_message',
		'heading'        => array(
			'type'       => 'h3',
			'title'      => $page_title,
			'responsive' => array(
				'title' => 'Email',
			),
		),
		'class'          => 'tableclass tableclass_form tableclass_full',
		'elements'       => array(),
		'extra_settings' => array(
			'owner_table' => 'email',
			'owner_key'   => 'email_id',
			'owner_id'    => (int) $email_id,
			'layout'      => 'table_row',
			'allow_new'   => $can_edit_emails && module_extra::can_i( 'create', 'Emails' ),
			'allow_edit'  => $can_edit_emails && module_extra::can_i( 'edit', 'Emails' ),
		)
	);

	// other templates:
	if ( $can_edit_emails && isset( $find_other_templates ) && strlen( $find_other_templates ) && isset( $current_template ) && strlen( $current_template ) ) {
		$other_templates = array();
		foreach ( module_template::get_templates() as $possible_template ) {
			if ( strpos( $possible_template['template_key'], $find_other_templates ) !== false ) {
				// found another one!
				$other_templates[ $possible_template['template_key'] ] = $possible_template['description'];
			}
		}
		if ( count( $other_templates ) > 0 ) {
			$fieldset_data['elements'][] = array(
				'title' => _l( 'Email Template:' ),
				'field' => function () use ( $other_templates, $current_template ) {
					?>
					<select name="template_name" id="template_name">
						<option value="email_template_blank"><?php _e( 'Blank' ); ?></option>
						<?php foreach ( $other_templates as $other_template_key => $other_template_name ) { ?>
							<option
								value="<?php echo htmlspecialchars( $other_template_key ); ?>"<?php echo $current_template == $other_template_key ? ' selected' : ''; ?>><?php echo htmlspecialchars( $other_template_name ); ?></option>
						<?php } ?>
					</select>
					<script type="text/javascript">
              $(function () {
                  $('#template_name').change(function () {
                      //$('#template_name_change').val($(this).val());
                      //$('#template_change_form')[0].submit();
                      $('#process_tag').val('change_email_template');
                      $('#email_send_form')[0].submit();
                  });
              });
					</script>
					<?php
					_h( 'Changing this will clear any message below. Create new templates in Settings > Templates. Name them email_template_SOMETHING and they will appear in this list.' );
				},
			);
		}
	}

	$fieldset_data['elements'][] = array(
		'title' => _l( 'From:' ),
		'field' => function () use ( $can_edit_emails, $options, $headers ) {

			$from_name  = ! empty( $headers['FromName'] ) ? $headers['FromName'] : $options['from_name'];
			$from_email = ! empty( $headers['FromEmail'] ) ? $headers['FromEmail'] : $options['from_email'];

			if ( $can_edit_emails ) { ?>
				<div id="email_from_view">
					<?php echo htmlspecialchars( $from_name . ' <' . $from_email . '>' ); ?> <a href="#"
					                                                                            onclick="$(this).parent().hide(); $('#email_from_edit').show(); return false;"><?php _e( 'edit' ); ?></a>
				</div>
				<div id="email_from_edit" style="display:none;">
					<input type="text" name="from_name" value="<?php echo htmlspecialchars( $from_name ); ?>">
					&lt;<input type="text" name="from_email" value="<?php echo htmlspecialchars( $from_email ); ?>">&gt
				</div>
			<?php } else {
				echo htmlspecialchars( $from_name . ' <' . $from_email . '>' );
			}
		},
	);

	$fieldset_data['elements'][] = array(
		'title' => _l( 'To:' ),
		'field' => function () use ( $can_edit_emails, $to, $options, $headers ) {
			if ( $can_edit_emails ) { ?>
				<?php
				// drop down with various options, or a blank inbox box with an email address.
				if ( count( $to ) > 1 ) {
					?>
					<select name="custom_to">
						<!-- <option value=""><?php _e( 'Please select' ); ?></option> -->
						<?php foreach ( $to as $t ) { ?>
							<option
								value="<?php echo htmlspecialchars( $t['email'] ); ?>||<?php echo htmlspecialchars( $t['name'] ); ?>||<?php echo (int) $t['user_id']; ?>"<?php if ( isset( $options['to_select'] ) && isset( $_REQUEST['custom_to'] ) && $_REQUEST['custom_to'] == $t['email'] ) {
								echo ' selected';
							} ?>><?php echo htmlspecialchars( $t['email'] ) . ' - ' . htmlspecialchars( $t['name'] ); ?></option>
						<?php } ?>
					</select>
				<?php } else {
					$t = array_shift( $to );
					?>
					<input type="hidden" name="custom_to"
					       value="<?php echo htmlspecialchars( $t['email'] ); ?>||<?php echo htmlspecialchars( $t['name'] ); ?>">
					<?php echo htmlspecialchars( $t['email'] ) . ' - ' . htmlspecialchars( $t['name'] ); ?>

				<?php } ?>
			<?php } else {
				if ( isset( $headers['to'] ) && is_array( $headers['to'] ) ) {
					foreach ( $headers['to'] as $to ) {
						echo $to['email'] . ' ';
					}
				}
			}
		},
	);
	$fieldset_data['elements'][] = array(
		'title' => _l( 'BCC:' ),
		'field' => function () use ( $can_edit_emails, $bcc, $headers ) {
			if ( $can_edit_emails ) { ?>
				<input type="text" name="bcc" value="<?php echo htmlspecialchars( $bcc ); ?>" style="width:400px">
			<?php } else {
				if ( isset( $headers['bcc'] ) && is_array( $headers['bcc'] ) ) {
					foreach ( $headers['bcc'] as $to ) {
						echo $to['email'] . ' ';
					}
				}
			}
		},
	);
	if ( class_exists( 'module_website', false ) && module_website::is_plugin_enabled() ) {
		$fieldset_data['elements'][] = array(
			'title' => _l( 'Related %s:', module_config::c( 'project_name_single', 'Website' ) ),
			'field' => function () use ( $customer_id, $email, $can_edit_emails ) {
				$websites = module_website::get_websites( array( 'customer_id' => $customer_id ) );
				if ( $can_edit_emails ) {
					echo print_select_box( $websites, 'website_id', isset( $email['website_id'] ) ? $email['website_id'] : false, '', true, 'name' );
				} else if ( isset( $email['website_id'] ) && $email['website_id'] ) {
					echo isset( $websites[ $email['website_id'] ] ) ? htmlspecialchars( $websites[ $email['website_id'] ]['name'] ) : _l( 'Deleted' );
				} else {
					_e( 'N/A' );
				}
			},
		);
	}
	if ( class_exists( 'module_job', false ) && module_job::is_plugin_enabled() ) {
		$fieldset_data['elements'][] = array(
			'title' => _l( 'Related Job:' ),
			'field' => function () use ( $customer_id, $email, $can_edit_emails ) {
				$jobs = module_job::get_jobs( array( 'customer_id' => $customer_id ) );
				if ( $can_edit_emails ) {
					echo print_select_box( $jobs, 'job_id', isset( $email['job_id'] ) ? $email['job_id'] : false, '', true, 'name' );
				} else if ( isset( $email['job_id'] ) && $email['job_id'] ) {
					echo isset( $jobs[ $email['job_id'] ] ) ? htmlspecialchars( $jobs[ $email['job_id'] ]['name'] ) : _l( 'Deleted' );
				} else {
					_e( 'N/A' );
				}
			},
		);
	}
	if ( class_exists( 'module_data', false ) && module_config::c( 'custom_data_in_email', 1 ) && $customer_id ) {

		$data_types = $plugins['data']->get_data_types();
		foreach ( $data_types as $data_type ) {
			switch ( $data_type['data_type_menu'] ) {
				case _CUSTOM_DATA_MENU_LOCATION_CUSTOMER:
					if ( $plugins['data']->can_i( 'view', $data_type['data_type_name'] ) ) {
						$search = array(
							'customer_id'  => $customer_id,
							'data_type_id' => $data_type['data_type_id']
						);
						// we have to limit the data types to only those created by current user if they are not administration
						$datas = $plugins['data']->get_datas( $search );
						if ( $datas ) {
							$list_fields       = array();
							$data_field_groups = $plugins['data']->get_data_field_groups( $data_type['data_type_id'] );
							foreach ( $data_field_groups as $data_field_group ) {
								$data_fields = $plugins['data']->get_data_fields( $data_field_group['data_field_group_id'] );
								foreach ( $data_fields as $data_field ) {
									if ( $data_field['show_list'] ) {
										$list_fields[ $data_field['data_field_id'] ] = $data_field;
									}
								}
							}

							$options = array();

							foreach ( $datas as $data ) {
								$list_data_items = $plugins['data']->get_data_items( $data['data_record_id'] );
								foreach ( $list_fields as $list_field ) {
									$settings = @unserialize( $list_data_items[ $list_field['data_field_id'] ]['data_field_settings'] );
									if ( ! isset( $settings['field_type'] ) ) {
										$settings['field_type'] = isset( $list_field['field_type'] ) ? $list_field['field_type'] : false;
									}
									$value = false;
									if ( isset( $list_data_items[ $list_field['data_field_id'] ] ) ) {
										$value = $list_data_items[ $list_field['data_field_id'] ]['data_text'];
									}
									if ( $value ) {
										$options[ $data['data_record_id'] ] = $value;
										break;
									}
								}
							}

							if ( $options ) {

								$json = array();

								$json['data_type_name'] = $data_type['data_type_name'];
								//$json['link'] = $plugins['data']->link('',array("data_type_id"=>$data['data_type_id'],'customer_id'=>$customer_id)); //"data_record_id"=>$data['data_record_id']
								// other details here?

								$fieldset_data['elements'][] = array(
									'title' => _l( 'Related %s:', htmlspecialchars( $data_type['data_type_name'] ) ),
									'field' => function () use ( $can_edit_emails, $data_type, $json, $options, $email ) {
										if ( $can_edit_emails ) { ?>
											<input type="hidden"
											       name="custom_data_info[<?php echo $data_type['data_type_id']; ?>]"
											       value="<?php echo htmlentities( json_encode( $json ) ); ?>">
											<?php
											echo print_select_box( $options, 'custom_data_related[' . $data_type['data_type_id'] . ']', false, '', true );
										} else {
											if ( ! empty( $email['custom_data'] ) ) {
												$email_custom_data = json_decode( $email['custom_data'], true );
												if ( is_array( $email_custom_data ) && isset( $email_custom_data[ $data_type['data_type_id'] ]['key'] ) ) {
													echo htmlspecialchars( $email_custom_data[ $data_type['data_type_id'] ]['key'] );
												} else {
													_e( 'N/A' );
												}
											} else {
												_e( 'N/A' );
											}
										}
									},
								);
							}
						}
					}
					break;
			}
		}
	}
	$fieldset_data['elements'][] = array(
		'title' => _l( 'Subject:' ),
		'field' => function () use ( $can_edit_emails, $email ) {
			if ( $can_edit_emails ) { ?>
				<input type="text" name="subject" value="<?php echo htmlspecialchars( $email['subject'] ); ?>"
				       style="width:400px;">
			<?php } else {
				echo htmlspecialchars( $email['subject'] );
			}
		},
	);
	if ( $can_edit_emails || ( $email_id && $email['schedule_time'] ) ) {
		// also allow the editing of scheduled emails
		$schedule_time               = ( ! empty( $email['schedule_time'] ) ? $email['schedule_time'] : time() );
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Schedule:' ),
			'fields' => array(
				array(
					'type'    => 'check',
					'name'    => 'schedule[enabled]',
					'class'   => 'schedule_email_check',
					'checked' => ! empty( $options['schedule']['enabled'] ) || ! empty( $email['schedule_time'] ),
					'value'   => 1,
				),
				'<span class="schedule_email_date">',
				_l( 'Date/Time:' ),
				array(
					'type'  => $can_edit_emails ? 'date_time' : 'html',
					'name'  => 'schedule[time]',
					'value' => $can_edit_emails ? $schedule_time : print_date( $schedule_time, true ),
				),
			),
		);
	}
	$fieldset_data['elements'][] = array(
		'title' => _l( 'Attachment:' ),
		'field' => function () use ( $can_edit_emails, $email ) {
		foreach ( $email['attachments'] as $uri => $attachment ){
			if ( is_array( $attachment ) ) {
				if ( $attachment['preview'] ) {
					echo '<a href="' . $attachment['preview'] . '">';
				}
				echo htmlspecialchars( $attachment['name'] );
				if ( $attachment['preview'] ) {
					echo '</a>';
				}
			} else {
				echo htmlspecialchars( $attachment );
			}
		if ( $can_edit_emails ){
			?>
		<input type="hidden" name="existing_attachments[]" value="<?php echo htmlspecialchars( $uri ); ?>">
		<?php
		}
		echo '<br/>';
		}
		if ( $can_edit_emails ){
		?>
			<div id="email_attach_holder">
				<div class="dynamic_block">
					<input type="file" name="manual_attachment[]" value="">
					<a href="#" class="add_addit" onclick="return seladd(this);">+</a>
					<a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
				</div>
			</div>
			<script type="text/javascript">
          set_add_del('email_attach_holder');
			</script>
		<?php }
		},
	);
	if ( ! $can_edit_emails ) {
		$message = '';
		if ( strlen( $email['html_content'] ) ) {
			$message = module_security::purify_html( $email['html_content'] );
		} else {
			$message = forum_text( $email['text_content'] );
		}
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Message:' ),
			'fields' => array(
				$message
			),
		);
	} else {
		$fieldset_data['elements'][] = array(
			'title' => _l( 'Message:' ),
			'field' => array(
				'type'    => 'wysiwyg',
				'name'    => 'html_content',
				'options' => array(
					'inline' => false,
				),
				'style'   => 'height: 400px;',
				'value'   => ! empty( $email['html_content'] ) ? $email['html_content'] : $email['text_content']
			),
		);
	}
	echo module_form::generate_fieldset( $fieldset_data );

	if ( $can_edit_emails ) {
		$form_actions = array(
			'class'    => 'action_bar action_bar_center',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'send',
					'value' => _l( 'Submit' ),
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );
	}


	if ( $can_edit_emails ){ ?>
</form>
<?php } else { ?>

	<input type="button" name="print" onclick="pop_and_print_email();" value="Print" class="submit_button">
	<script type="text/javascript">
      function pop_and_print_email() {
          var w = window.open();

          var headers = $("#headers").html();
          var field = $("#field1").html();
          var field2 = $("#field2").html();

          var html = "<!DOCTYPE HTML>";
          html += '<html lang="en-us">';
          html += '<head><style></style></head>';
          html += "<body>";
          html += $('#email_message .tableclass')[0].outerHTML;
          html += "</body>";
          w.document.write(html);
          w.window.print();
          w.document.close();
      }
	</script>
<?php } ?>
