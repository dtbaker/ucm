<?php
$social_facebook_id         = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
$social_facebook_message_id = isset( $_REQUEST['social_facebook_message_id'] ) ? (int) $_REQUEST['social_facebook_message_id'] : 0;

if ( $social_facebook_id && $social_facebook_message_id && module_social::can_i( 'view', 'Facebook Comments', 'Social', 'social' ) ) {
	$facebook = new ucm_facebook_account( $social_facebook_id );
	if ( $social_facebook_id && $facebook->get( 'social_facebook_id' ) == $social_facebook_id ) {
		$facebook_message = new ucm_facebook_message( $facebook, false, $social_facebook_message_id );
		if ( $social_facebook_message_id && $facebook_message->get( 'social_facebook_message_id' ) == $social_facebook_message_id && $facebook_message->get( 'social_facebook_id' ) == $social_facebook_id ) {

			$module->page_title = $facebook->get( 'facebook_name' );
			$comments           = $facebook_message->get_comments();
			$facebook_message->mark_as_read();

			?>

			<form action="" method="post" id="facebook_edit_form">
				<div id="facebook_message_header">
					<div style="float:right; text-align: right; margin-top:-4px;">
						<small><?php echo print_date( $facebook_message->get( 'last_active' ), true ); ?> </small>
						<br/>
						<?php if ( module_social::can_i( 'edit', 'Facebook Comments', 'Social', 'social' ) ) { ?>
							<?php if ( $facebook_message->get( 'status' ) == _SOCIAL_MESSAGE_STATUS_ANSWERED ) { ?>
								<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
								   data-action="set-unanswered"
								   data-id="<?php echo (int) $facebook_message->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Un-Archive' ); ?></a>
							<?php } else { ?>
								<a href="#" class="socialfacebook_message_action  btn btn-default btn-xs"
								   data-action="set-answered"
								   data-id="<?php echo (int) $facebook_message->get( 'social_facebook_message_id' ); ?>"><?php _e( 'Archive' ); ?></a>
							<?php } ?>
						<?php } ?>
					</div>
					<img src="<?php echo _BASE_HREF; ?>includes/plugin_social_facebook/images/facebook.png" class="facebook_icon">
					<strong><?php _e( 'Account:' ); ?></strong> <a href="<?php echo $facebook_message->get_link(); ?>"
					                                               target="_blank"><?php echo htmlspecialchars( $facebook_message->get( 'facebook_page' )->get( 'page_name' ) ); ?></a>
					<br/>
					<strong><?php _e( 'Type:' ); ?></strong> <?php echo htmlspecialchars( $facebook_message->get_type_pretty() ); ?>
				</div>
				<div id="facebook_message_holder">
					<?php
					$facebook_message->full_message_output( module_social::can_i( 'create', 'Facebook Comments', 'Social', 'social' ) );
					?>
				</div>
			</form>

		<?php }
	}
}

if ( $social_facebook_id && ! $social_facebook_message_id && module_social::can_i( 'create', 'Facebook Comments', 'Social', 'social' ) ) {
	$facebook = new ucm_facebook_account( $social_facebook_id );
	if ( $social_facebook_id && $facebook->get( 'social_facebook_id' ) == $social_facebook_id ) {
		$module->page_title = $facebook->get( 'facebook_name' );

		/* @var $pages ucm_facebook_page[] */
		$pages = $facebook->get( 'pages' );
		//print_r($pages);
		?>
		<form action="" method="post" enctype="multipart/form-data">
			<input type="hidden" name="_process" value="send_facebook_message">
			<?php module_form::print_form_auth(); ?>
			<?php
			$fieldset_data = array(
				'heading'  => isset( $_REQUEST['display_mode'] ) && $_REQUEST['display_mode'] == 'ajax' ? false : array(
					'type'  => 'h3',
					'title' => 'Compose Message',
				),
				'class'    => 'tableclass tableclass_form tableclass_full',
				'elements' => array(
					'facebook_page'    => array(
						'title'  => _l( 'Facebook Page' ),
						'fields' => array(),
					),
					'message'          => array(
						'title' => _l( 'Message' ),
						'field' => array(
							'type'  => 'textarea',
							'name'  => 'message',
							'id'    => 'facebook_compose_message',
							'value' => '',
						),
					),
					'type'             => array(
						'title'  => _l( 'Type' ),
						'fields' => array(
							'<input type="radio" name="post_type" id="post_type_wall" value="wall" checked> ',
							'<label for="post_type_wall">',
							_l( 'Wall Post' ),
							'</label>',
							'<input type="radio" name="post_type" id="post_type_link" value="link"> ',
							'<label for="post_type_link">',
							_l( 'Link Post' ),
							'</label>',
							'<input type="radio" name="post_type" id="post_type_picture" value="picture"> ',
							'<label for="post_type_picture">',
							_l( 'Picture Post' ),
							'</label>',
						),
					),
					'link'             => array(
						'title'  => _l( 'Link' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'link',
								'id'    => 'message_link_url',
								'value' => '',
							),
							'<div id="facebook_link_loading_message"></div>',
							'<span class="facebook-type-link facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					'link_picture'     => array(
						'title'  => _l( 'Link Picture' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'link_picture',
								'value' => '',
							),
							_hr( 'Full URL (eg: http://) to the picture to use for this link preview' ),
							'<span class="facebook-type-link facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					'link_name'        => array(
						'title'  => _l( 'Link Title' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'link_name',
								'value' => '',
							),
							_hr( 'Title to use instead of the automatically generated one from the Link page' ),
							'<span class="facebook-type-link facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					'link_caption'     => array(
						'title'  => _l( 'Link Caption' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'link_caption',
								'value' => '',
							),
							_hr( 'Caption to use instead of the automatically generated one from the Link page' ),
							'<span class="facebook-type-link facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					'link_description' => array(
						'title'  => _l( 'Link Description' ),
						'fields' => array(
							array(
								'type'  => 'text',
								'name'  => 'link_description',
								'value' => '',
							),
							_hr( 'Description to use instead of the automatically generated one from the Link page' ),
							'<span class="facebook-type-link facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					/*'track' => array(
						'title' => _l('Track clicks'),
						'field' => array(
							'type' => 'check',
							'name' => 'track_links',
							'value' => '1',
							'help' => 'If this is selected, the links will be automatically shortened so we can track how many clicks are received.',
							'checked' => false,
						),
					),*/
					'picture'          => array(
						'title'  => _l( 'Picture' ),
						'fields' => array(
							'<input type="file" name="picture" value="">',
							'<span class="facebook-type-picture facebook-type-option"></span>', // flag for our JS hide/show hack
						),
					),
					'schedule'         => array(
						'title'  => _l( 'Schedule' ),
						'fields' => array(
							array(
								'type'  => 'date',
								'name'  => 'schedule_date',
								'value' => '',
							),
							array(
								'type'  => 'time',
								'name'  => 'schedule_time',
								'value' => '',
							),
							' ',
							_l( 'Currently: %s', date( 'c' ) ),
							_hr( 'Leave blank to send now. Pick a date in the future to send this message.' ),
						),
					),
					'debug'            => array(
						'title' => _l( 'Debug' ),
						'field' => array(
							'type'    => 'check',
							'name'    => 'debug',
							'value'   => '1',
							'checked' => false,
							'help'    => 'Show debug output while posting the message',
						),
					),
				)
			);
			foreach ( $pages as $facebook_page_id => $page ) {
				$fieldset_data['elements']['facebook_page']['fields'][] =
					'<div id="facebook_compose_page_select">' .
					'<input type="checkbox" name="compose_page_id[' . $facebook_page_id . ']" value="1" checked> ' .
					'<img src="http://graph.facebook.com/' . $facebook_page_id . '/picture"> ' .
					htmlspecialchars( $page->get( 'page_name' ) ) .
					'</div>';
			}
			echo module_form::generate_fieldset( $fieldset_data );

			$form_actions = array(
				'class'    => 'action_bar action_bar_center',
				'elements' => array(),
			);
			echo module_form::generate_form_actions( $form_actions );

			$form_actions['elements'][] = array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Send' ),
			);

			// always show a cancel button
			/*$form_actions['elements'][] = array(
			    'type' => 'button',
			    'name' => 'cancel',
			    'value' => _l('Cancel'),
			    'class' => 'submit_button',
			    'onclick' => "window.location.href='".$module->link_open_message_view($social_facebook_id)."';",
			);*/
			echo module_form::generate_form_actions( $form_actions );
			?>
		</form>

		<script type="text/javascript">
        function change_post_type() {
            var currenttype = $('[name=post_type]:checked').val();
            $('.facebook-type-option').each(function () {
                $(this).parents('tr').first().hide();
            });
            $('.facebook-type-' + currenttype).each(function () {
                $(this).parents('tr').first().show();
            });

        }

        $(function () {
            $('[name=post_type]').change(change_post_type);
            $('#message_link_url').change(function () {
                $('#facebook_link_loading_message').html('<?php _e( 'Loading URL information...' );?>');
                $.ajax({
                    url: '<?php echo module_social_facebook::link_open_message_view( $social_facebook_id );?>',
                    data: {_process: 'ajax_facebook_url_info', url: $(this).val()},
                    dataType: 'json',
                    success: function (res) {
                        $('.facebook-type-link').each(function () {
                            var elm = $(this).parent().find('input');
                            if (res && typeof res[elm.attr('name')] != 'undefined') {
                                elm.val(res[elm.attr('name')]);
                            }
                        });
                    },
                    complete: function () {
                        $('#facebook_link_loading_message').html('');
                    }
                });
            });
            change_post_type();
        })
		</script>

		<?php
	}
}