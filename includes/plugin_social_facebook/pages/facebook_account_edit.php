<?php

if ( ! module_social::can_i( 'edit', 'Facebook', 'Social', 'social' ) ) {
	die( 'No access to Facebook accounts' );
}

$social_facebook_id = isset( $_REQUEST['social_facebook_id'] ) ? (int) $_REQUEST['social_facebook_id'] : 0;
$facebook           = new ucm_facebook_account( $social_facebook_id );

$heading = array(
	'type'  => 'h3',
	'title' => 'Facebook Account',
);
?>

<form action="" method="post">
	<input type="hidden" name="_process" value="save_facebook">
	<input type="hidden" name="social_facebook_id" value="<?php echo $facebook->get( 'social_facebook_id' ); ?>">

	<?php
	module_form::print_form_auth();

	$fieldset_data = array(
		'heading'  => $heading,
		'class'    => 'tableclass tableclass_form tableclass_full',
		'elements' => array(
			array(
				'title'  => _l( 'Setup Instructions' ),
				'fields' => array(
					function () {
						?>
						<p>Setup Instructions:</p>
						<ul>
							<li>Go to <a href="https://developers.facebook.com/apps" target="_blank">https://developers.facebook.com/apps</a>
								and click Create New App, choose "Website Option"
							</li>
							<li>Enter an App Name (e.g. MyBusinessName) and click "Create New Facebook App ID" and choose category
								"Apps for Pages".
							</li>
							<li>Click Skip Quick Start (top right)</li>
							<li>Copy the "App ID" and "App Secret" into the boxes below.</li>
							<li>Click on the Facebook App "Settings" tab and add <?php echo $_SERVER['HTTP_HOST']; ?>
								into the "App Domains" box and enter your email into the "Contact Email" box
							</li>
							<li>Then click "Add Platform" and choose "Website" and enter
								http://<?php echo $_SERVER['HTTP_HOST']; ?></li>
							<li>Then click "Status &amp; Review" and change the app Live status from No to Yes (toggle
								button at top).
							</li>
							<li>Ignore any errors about invalid permissions or submitting the app for review. If you are
								the Admin of the App and Admin of the Page it will be fine.
							</li>
							<li>If all else fails, leave the App ID and App Secret blank and it will use the defaults.
							</li>
						</ul>
						<?php
					}
				),
			),
			array(
				'title' => _l( 'Account Name' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'facebook_name',
					'value' => $facebook->get( 'facebook_name' ),
					'help'  => 'Choose a name for this account. This name will be shown here in the system.',
				),
			),
			array(
				'title' => _l( 'Facebook App ID' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'facebook_app_id',
					'value' => $facebook->get( 'facebook_app_id' ),
					'help'  => 'The App ID (see instructions above). Leave blank to use defaults.',
				),
			),
			array(
				'title' => _l( 'Facebook App Secret' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'facebook_app_secret',
					'value' => $facebook->get( 'facebook_app_secret' ),
					'help'  => 'The App Secret (see instructions above). Leave blank to use defaults.',
				),
			),
		)
	);
	// check if this is active, if not prmopt the user to re-connect.
	if ( $facebook->is_active() ) {
		$fieldset_data['elements'][] = array(
			'title'  => _l( 'Last Checked' ),
			'fields' => array(
				print_date( $facebook->get( 'last_checked' ), true ),
			),
		);
		$pages                       = array(
			'title'  => _l( 'Available Pages' ),
			'fields' => array(
				'<input type="hidden" name="save_facebook_pages" value="yep">',
			),
		);
		$data                        = @json_decode( $facebook->get( 'facebook_data' ), true );
		if ( $data && isset( $data['pages'] ) && is_array( $data['pages'] ) && count( $data['pages'] ) > 0 ) {
			$pages['fields'][] = '<strong>Choose which Facebook Pages you would like to manage:</strong><br>';
			foreach ( $data['pages'] as $page_id => $page_data ) {
				$pages['fields'][] = '<div>';
				$pages['fields'][] = array(
					'type'    => 'check',
					'name'    => 'facebook_page[' . $page_id . ']',
					'value'   => 1,
					'label'   => $page_data['name'],
					'checked' => $facebook->is_page_active( $page_id ),
				);
				if ( $facebook->is_page_active( $page_id ) ) {
					$pages['fields'][] = '(<a href="' . module_social_facebook::link_open_facebook_page_refresh( $social_facebook_id, $page_id, false, false ) . '" target="_blank">manually re-load page data</a>)';
				}
				$pages['fields'][] = '</div>';
			}
		} else {
			$pages['fields'][] = 'No Facebook Pages Found to Manage';
		}
		$fieldset_data['elements'][] = $pages;
	} else {

	}
	echo module_form::generate_fieldset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_center',
		'elements' => array(),
	);
	echo module_form::generate_form_actions( $form_actions );

	if ( ! $facebook->is_active() ) {
		// show a 'save' and button as normal
		$form_actions['elements'][] = array(
			'type'  => 'save_button',
			'name'  => 'butt_save_connect',
			'value' => _l( 'Save & Connect to Facebook' ),
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
			'value' => _l( 'Re-Connect to Facebook' ),
		);
	}
	if ( $facebook->get( 'social_facebook_id' ) ) {
		// show delete if we have an id.

		$form_actions['elements'][] = array(
			'ignore' => ! ( module_social::can_i( 'delete', 'Facebook', 'Social', 'social' ) ),
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