<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<title><?php _e( 'Ticket :' ); ?><?php echo module_ticket::ticket_number( $ticket_id ); ?></title>

	<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/styles.css?ver=5" type="text/css"/>
	<link rel="stylesheet" href="<?php echo _BASE_HREF; ?>css/desktop.css?ver=5" type="text/css"/>
	<?php module_config::print_css(); ?>


	<script language="javascript" type="text/javascript">
		<?php
		switch ( strtolower( module_config::s( 'date_format', 'd/m/Y' ) ) ) {
			case 'd/m/y':
				$js_cal_format = 'dd/mm/yy';
				break;
			case 'y/m/d':
				$js_cal_format = 'yy/mm/dd';
				break;
			case 'm/d/y':
				$js_cal_format = 'mm/dd/yy';
				break;
			default:
				$js_cal_format = 'yy-mm-dd';
		}
		?>
    var js_cal_format = '<?php echo $js_cal_format;?>';
	</script>
	<?php module_config::print_js(); ?>

	<script type="text/javascript">
      $(function () {
          init_interface();
      });
	</script>
</head>
<body bgcolor="#FFF">


<div id="holder" style="background: #FFF">
	<div id="page_middle_flex">

		<div class="content">
			<div id="ticket_new_holder_public">

				<?php if ( isset( $errors ) && count( $errors ) ) { ?>
					<div class="ui-state-error error_text">
						<ul>
							<?php foreach ( $errors as $error ) {
								echo '<li>' . $error . '</li>';
							}
							?>
						</ul>
					</div>
				<?php } ?>

				<?php module_form::print_form_open_tag( array(
					'action'  => module_ticket::link_public_new(),
					'id'      => 'ticket_form',
					'enctype' => 'multipart/form-data',
				) ); ?>
					<input type="hidden" name="_process" value="save_public_ticket"/>


					<?php
					/*<input type="hidden" name="tac" value="<?php echo $ticket_account['ticket_account_id'];?>" />*/
					$form_fields = array(
						'fields' => array(
							'name'               => _l( 'Your Name' ),
							'email'              => _l( 'Your Email' ),
							'type'               => _l( 'Department' ),
							'subject'            => _l( 'Subject' ),
							'new_ticket_message' => _l( 'Your Message' ),
						),
						'emails' => array(
							'email' => _l( 'Your Email' ),
						),
					);
					if ( class_exists( 'module_faq', false ) && module_config::c( 'ticket_submit_product_required', 0 ) ) {
						$form_fields['fields']['faq_product_id'] = _l( 'Product' );
					}
					module_form::set_required( $form_fields );
					?>

					<table cellpadding="10" class="wpetss" width="100%">
						<tbody>
						<tr>
							<td class="public_header">
								<?php echo module_config::c( 'ticket_public_header', 'Submit a support ticket' ); ?>
							</td>
						</tr>
						<tr>
							<td class="public_welcome">
								<?php echo module_config::c( 'ticket_public_welcome', 'Hello, here you can request a support ticket. <br/> Simply fill in the fields below and press "Submit Ticket".' ); ?>
							</td>
						</tr>
						<tr>
							<td valign="top" style="padding:10px;">
								<h3><?php echo _l( 'Your Details' ); ?></h3>
								<?php echo module_form::generate_fieldset( array(
									'class'    => 'tableclass tableclass_form tableclass_full',
									'elements' => array(
										'name'  => array(
											'title' => _l( 'Your Name' ),
											'field' => array(
												'type'  => 'text',
												'name'  => 'name',
												'style' => 'width:90%',
												'value' => isset( $_POST['name'] ) ? $_POST['name'] : '',
											),
										),
										'email' => array(
											'title' => _l( 'Your Email' ),
											'field' => array(
												'type'  => 'text',
												'name'  => 'email',
												'style' => 'width:90%',
												'value' => isset( $_POST['email'] ) ? $_POST['email'] : '',
											),
										),
									),
								) ); ?>


								<?php handle_hook( 'ticket_create', $ticket_id ); ?>


								<h3><?php echo _l( 'Ticket Details' ); ?></h3>

								<?php
								$detail_elements = array();

								if ( module_config::c( 'ticket_public_show_type_dropdown', 1 ) ) {
									$types = module_ticket::get_types( true );
									// The key is a ticket_type_id, so the cast is what the lookup already means.
									// This form is the one page of the ticket plugin an anonymous visitor can
									// reach, so the value that comes back out of it is worth pinning to a number.
									$selected_type = isset( $_REQUEST['ticket_type_id'] ) ? (int) $_REQUEST['ticket_type_id'] : 0;
									if ( ! isset( $types[ $selected_type ] ) ) {
										$selected_type = $ticket_account['default_type'];
									}
									$detail_elements['ticket_type_id'] = array(
										'title'  => _l( 'Type/Department' ),
										'fields' => array(
											static function () use ( $types, $selected_type ) {
												echo print_select_box( $types, 'ticket_type_id', $selected_type, '', false, 'name' );
											},
										),
									);
								}

								if ( module_config::c( 'ticket_allow_priority_selection', 0 ) ) {
									$priorities = module_ticket::get_ticket_priorities();
									if ( isset( $priorities[ _TICKET_PRIORITY_STATUS_ID ] ) ) {
										unset( $priorities[ _TICKET_PRIORITY_STATUS_ID ] );
									}
									$detail_elements['priority'] = array(
										'title'  => _l( 'Priority' ),
										'fields' => array(
											static function () use ( $priorities ) {
												echo print_select_box( $priorities, 'priority', '', '', false );
											},
										),
									);
								}

								$detail_elements['subject'] = array(
									'title' => _l( 'Subject' ),
									'field' => array(
										'type'  => 'text',
										'name'  => 'subject',
										'id'    => 'subject',
										'style' => 'width:90%',
										'value' => isset( $_POST['subject'] ) ? $_POST['subject'] : '',
									),
								);
								$detail_elements['new_ticket_message'] = array(
									'title' => _l( 'Your Message' ),
									'field' => array(
										'type'  => 'textarea',
										'name'  => 'new_ticket_message',
										'rows'  => 10,
										'cols'  => 20,
										'style' => 'width:90%',
										'value' => isset( $_POST['new_ticket_message'] ) ? $_POST['new_ticket_message'] : '',
									),
								);

								if ( module_config::c( 'ticket_allow_extra_data', 1 ) ) {
									$extras = module_ticket::get_ticket_extras_keys( $ticket_account['ticket_account_id'] );
									if ( count( $extras ) ) {
										$detail_elements['extras_intro'] = array(
											'title'  => '',
											'fields' => array( _l( 'Please fill out as many of the below fields as possible:' ) ),
										);
										foreach ( $extras as $extra ) {
											$detail_elements[ 'extra_' . $extra['ticket_data_key_id'] ] = array(
												'title'  => $extra['key'],
												'fields' => array(
													static function () use ( $extra ) {
														module_form::generate_form_element( array(
															'type'    => $extra['type'],
															'name'    => 'ticket_extra[' . $extra['ticket_data_key_id'] . ']',
															'value'   => isset( $_POST['ticket_extra'] ) && isset( $_POST['ticket_extra'][ $extra['ticket_data_key_id'] ] ) ? $_POST['ticket_extra'][ $extra['ticket_data_key_id'] ] : '',
															'options' => isset( $extra['options'] ) && $extra['options'] ? unserialize( $extra['options'] ) : array(),
														) );

														if (
															class_exists( 'module_encrypt', false ) &&
															module_config::c( 'ticket_show_encrypt_fields', 1 ) &&
															isset( $extra['encrypt_key_id'] ) && $extra['encrypt_key_id']
															&&
															( $extra['type'] == 'text' || $extra['type'] == 'textarea' )
														) {
															// hack to show the encrypt key icon if this is an encryptable key.
															echo '<img src="' . full_link( 'includes/plugin_encrypt/images/lock.png' ) . '" style="vertical-align:top;" border="0">';
															if ( module_config::c( 'ticket_show_encrypt_fields_help_popup', 1 ) ) {
																_h( 'This field is encrypted in the database using industry standard RSA cryptography.' );
															}
														}
													},
												),
											);
										}
									}
								}

								if ( module_config::c( 'ticket_allow_attachment', 1 ) ) {
									$detail_elements['attachment'] = array(
										'title'  => _l( 'Attachment' ),
										'fields' => array(
											'<div id="file_attachment_holder">'
											. '<div class="dynamic_block" style="clear:left;">'
											. '<div style="float:left;"><input type="file" name="attachment[]"></div>'
											. '<div style="float:left; padding: 4px 0 0 10px;">'
											. '<a href="#" class="add_addit" onclick="return seladd(this);">+</a> '
											. '<a href="#" class="remove_addit" onclick="return selrem(this);">-</a>'
											. '</div></div></div>'
											. '<script type="text/javascript">set_add_del(\'file_attachment_holder\');</script>',
										),
									);
								}

								if ( module_config::c( 'ticket_recaptcha', 1 ) ) {
									$detail_elements['captcha'] = array(
										'title'  => _l( 'Spam Protection' ),
										'fields' => array( module_captcha::display_captcha_form() ),
									);
								}

								echo module_form::generate_fieldset( array(
									'class'    => 'tableclass tableclass_form tableclass_full',
									'elements' => $detail_elements,
								) );
								?>


								<?php if (
									module_config::c( 'ticket_show_position', 1 ) ||
									module_config::c( 'ticket_allow_priority', 0 ) ||
									module_config::c( 'ticket_turn_around_rate_show', 1 ) ||
									module_config::c( 'ticket_turn_around_days_show', 1 )
								) { ?>

									<h3><?php echo _l( 'Ticket Information' ); ?></h3>

									<?php
									// Read-only rows, but they live inside the form and wear the form table's
									// class, so they are elements like the rest of it.
									$info_elements = array();

									if ( module_config::c( 'ticket_show_position', 1 ) ) {
										$info_elements['position'] = array(
											'title'          => _l( 'Position' ),
											'row_data_class' => '',
											'fields'         => array(
												_l( '%s out of %s other support tickets',
													ordinal( $ticket['position'] ), $ticket['total_pending'] ),
											),
										);
									}

									if ( module_config::c( 'ticket_allow_priority', 0 ) ) {
										$info_elements['priority_support'] = array(
											'title'  => _l( 'Priority Support' ),
											'fields' => array(
												static function () use ( $ticket ) {
													module_template::init_template( 'ticket_priority_support', '<em>New!</em> Need a <strong>fast</strong> reply? Priority support will place your ticket at the front of the support queue. <br/>Priority Support Cost: {COST}<br/>{CHECKBOX} <label for="do_priority">Yes, upgrade to <strong>priority support</strong> and move my ticket to position: {TICKET_POSITION}</label>', 'Displayed at the bottom of ticket support signup', 'code', array(
														'cost'            => 'Cost as defined by ticket_priority_code advanced setting',
														'TICKET_POSITION' => 'displays the string (example) "1st out of 44"',
													) );
													$template = module_template::get_template_by_key( 'ticket_priority_support' );
													$template->assign_values( array(
														'cost'            => dollar( module_config::c( 'ticket_priority_cost', 10 ), true, module_config::c( 'ticket_priority_currency', 1 ) ),
														'TICKET_POSITION' => _l( '%s out of %s', ordinal( module_ticket::ticket_count( 'priority' ) + 1 ), $ticket['total_pending'] ),
														'CHECKBOX'        => '<input type="checkbox" name="do_priority" id="do_priority" value="1">',
													) );
													echo $template->replace_content();
												},
											),
										);
									}

									if ( module_config::c( 'ticket_turn_around_days_show', 1 ) ) {
										$info_elements['reply'] = array(
											'title'  => _l( 'Reply' ),
											'fields' => array(
												_l( 'We will reply between %s and %s %s',
													module_config::c( 'ticket_turn_around_days_min', 2 ),
													module_config::c( 'ticket_turn_around_days', 5 ),
													module_config::c( 'ticket_turn_around_period', 'days' ) ),
											),
										);
									}

									if ( module_config::c( 'ticket_turn_around_rate_show', 1 ) ) {
										$rate = module_ticket::get_reply_rate();
										$info_elements['rate'] = array(
											'title'  => _l( 'Rate' ),
											'fields' => array(
												_l( 'We are currently processing about %s tickets every 24 hours', $rate['daily'] ),
											),
										);
									}

									echo module_form::generate_fieldset( array(
										'class'    => 'tableclass tableclass_form tableclass_full',
										'elements' => $info_elements,
									) );
									?>

								<?php } ?>


								<p align="center">
									<em>
										<small><?php echo _l( '* required fields' ); ?> </small>
									</em> <br/>
									<input type="submit" name="butt_save" id="butt_save" value="<?php echo _l( 'Submit Ticket' ); ?>"
									       class="submit_button save_button"/>
								</p>
							</td>
						</tr>
						</tbody>
					</table>


				<?php module_form::print_form_close_tag(); ?>
			</div>
    