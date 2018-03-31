<?php

if ( ! module_social::can_i( 'edit', 'Twitter', 'Social', 'social' ) ) {
	die( 'No access to Twitter accounts' );
}

$social_twitter_id = isset( $_REQUEST['social_twitter_id'] ) ? (int) $_REQUEST['social_twitter_id'] : 0;
$twitter           = new ucm_twitter_account( $social_twitter_id );

$heading = array(
	'type'  => 'h3',
	'title' => 'Twitter Account',
);
?>

<form action="" method="post">
	<input type="hidden" name="_process" value="save_twitter">
	<input type="hidden" name="social_twitter_id" value="<?php echo $twitter->get( 'social_twitter_id' ); ?>">

	<?php
	module_form::print_form_auth();

	$fieldset_data = array(
		'heading'  => $heading,
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title' => _l( 'Account Name' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'account_name',
					'value' => $twitter->get( 'account_name' ),
					'help'  => 'Choose a name for this account. This name will be shown here in the system.',
				),
			),
		)
	);
	// check if this is active, if not prmopt the user to re-connect.
	if ( $twitter->is_active() ) {
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Last Checked' ),
			'fields' => array(
				print_date( $twitter->get( 'last_checked' ), true ),
				'(<a href="' . module_social_twitter::link_open_twitter_account_refresh( $social_twitter_id ) . '" target="_blank">' . _l( 'Refresh' ) . '</a>)',
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Twitter Name' ),
			'fields' => array(
				htmlspecialchars( $twitter->get( 'twitter_name' ) ),
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Twitter ID' ),
			'fields' => array(
				htmlspecialchars( $twitter->get( 'twitter_id' ) ),
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Import DM\'s' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'value' => $twitter->get( 'import_dm' ),
					'name'  => 'import_dm',
					'help'  => 'Enable this to import Direct Messages from this twitter account',
				)
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Import Mentions' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'value' => $twitter->get( 'import_mentions' ),
					'name'  => 'import_mentions',
					'help'  => 'Enable this to import any tweets that mention your name',
				)
			),
		);
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Import Tweets' ),
			'fields' => array(
				array(
					'type'  => 'checkbox',
					'name'  => 'import_tweets',
					'value' => $twitter->get( 'import_tweets' ),
					'help'  => 'Enable this to import any tweets that originated from this account',
				)
			),
		);

	} else {

	}
	echo module_form::generate_fieldset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(),
	);
	echo module_form::generate_form_actions( $form_actions );

	if ( ! $twitter->is_active() ) {
		// show a 'save' and button as normal
		$form_actions['elements'][] = array(
			'type'  => 'save_button',
			'name'  => 'butt_save_connect',
			'value' => _l( 'Save & Connect to Twitter' ),
		);
	} else {
		$form_actions['elements'][] = array(
			'type'  => 'save_button',
			'name'  => 'butt_save',
			'value' => _l( 'Save' ),
		);
		$form_actions['elements'][] = array(
			'type'  => 'submit',
			'name'  => 'butt_save_connect',
			'value' => _l( 'Re-Connect to Twitter' ),
		);
	}
	if ( $twitter->get( 'social_twitter_id' ) ) {
		// show delete if we have an id.

		$form_actions['elements'][] = array(
			'ignore' => ! ( module_social::can_i( 'delete', 'Twitter', 'Social', 'social' ) ),
			'type'   => 'delete_button',
			'name'   => 'butt_del',
			'value'  => _l( 'Delete' ),
		);
	}
	// always show a cancel button
	$form_actions['elements'][] = array(
		'type'    => 'button',
		'name'    => 'cancel',
		'value'   => _l( 'Cancel' ),
		'class'   => 'submit_button',
		'onclick' => "window.location.href='" . $module->link_open( false ) . "';",
	);

	echo module_form::generate_form_actions( $form_actions );
	?>


</form>