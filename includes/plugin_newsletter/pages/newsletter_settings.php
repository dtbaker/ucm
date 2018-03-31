<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}


$module->page_title = _l( 'Newsletter Settings' );


$templates = array();
foreach ( module_newsletter::get_templates() as $template ) {
	$templates[ $template['newsletter_template_id'] ] = $template['newsletter_template_name'];
}

$settings = array(
	'heading'  => array(
		'title' => 'Newsletter Settings',
		'main'  => true,
		'type'  => 'h2',
	),
	'settings' => array(
		array(
			'key'         => 'newsletter_default_from_name',
			'default'     => module_config::c( 'admin_system_name' ),
			'type'        => 'text',
			'description' => 'What sender name your newsletters will come from',
		),
		array(
			'key'         => 'newsletter_default_from_email',
			'default'     => module_config::c( 'admin_email_address' ),
			'type'        => 'text',
			'description' => 'What email address your newsletters will come from',
		),
		array(
			'key'         => 'newsletter_default_template',
			'default'     => 1,
			'type'        => 'select',
			'options'     => $templates,
			'description' => 'Default template to use',
		),
		array(
			'key'         => 'newsletter_convert_links',
			'default'     => 1,
			'type'        => 'select',
			'options'     => get_yes_no(),
			'description' => 'Convert all links into trackable click links',
			'help'        => 'When a user clicks a link in your newsletter it will record who, when and which link they clicked',
		),
		array(
			'key'         => 'newsletter_convert_images',
			'default'     => 1,
			'type'        => 'select',
			'options'     => get_yes_no(),
			'description' => 'Convert all images into trackable images',
			'help'        => 'If this option is enabled you can tell when users see the images in your newsletters',
		),
		array(
			'key'         => 'newsletter_default_bounce',
			'default'     => module_config::c( 'admin_email_address' ),
			'type'        => 'text',
			'description' => 'What email address your bounced newsletters will go to (eg: bounce@yourwebsite.com)',
			'help'        => 'Please setup a NEW email address for bounces. Do not use an existing email address.',
		),
		array(
			'key'         => 'newsletter_bounce_host',
			'default'     => $_SERVER['HTTP_HOST'],
			'type'        => 'text',
			'description' => 'POP3 incoming server address to access the bounce email account (eg: mail.yourwebsite.com)',
			'help'        => 'You can put the port number in like this:  mail.yourwebsite.com',
		),
		array(
			'key'         => 'newsletter_bounce_port',
			'default'     => '110',
			'type'        => 'text',
			'description' => 'POP3 incoming server port (eg: 110 or 995)',
			'help'        => '110 is normal, 995 is ssl',
		),
		array(
			'key'         => 'newsletter_bounce_ssl',
			'default'     => '/ssl',
			'type'        => 'text',
			'description' => 'SSL or TLS setting',
			'help'        => 'Set this to blank, or /ssl, or /tls or other options from php.net/imap_connect',
		),
		array(
			'key'         => 'newsletter_bounce_username',
			'default'     => '',
			'type'        => 'text',
			'description' => 'POP3 username to access the bounce email account',
		),
		array(
			'key'         => 'newsletter_bounce_password',
			'default'     => '',
			'type'        => 'text',
			'description' => 'POP3 password to access the bounce email account',
		),
		array(
			'key'         => 'newsletter_bounce_threshold',
			'default'     => 3,
			'type'        => 'number',
			'description' => 'Bounce Threshold',
			'help'        => 'How many bounces before we unsubscribe this member',
		),
		/*array(
				'key'=>'newsletter_inline_images',
				'default'=>0,
				'type'=>'select',
				'options'=>array(
						1=>'Yes',
						0=>'No',
				),
				'description'=>'Enable inline images?',
				'help'=>'Send images inline? This will use a LOT more bandwidth and take longer to send. But users will have a better chance of seeing your images.',
		),*/
		/*array(
				'key'=>'newsletter_double_opt_in',
				'default'=>1,
				'type'=>'select',
				'options'=>array(
						1=>'Yes',
						0=>'No',
				),
				'description'=>'Enable double opt-in?',
				'help'=>'When a user subscribes via the embedded form (see below), an email will be sent to them asking them to confirm their registration',
		),
		array(
				'key'=>'newsletter_double_opt_in_subject',
				'default'=>'Please confirm your newsletter subscription',
				'type'=>'text',
				'description'=>'Double opt-in email subject',
		),*/
		array(
			'key'         => 'newsletter_send_burst_count',
			'default'     => 40,
			'type'        => 'number',
			'description' => 'Burst Count',
			'help'        => 'How many emails the server will try to sent at the same time. Setting this too high may get you banned by your hosting provider.',
		),
		array(
			'key'         => 'newsletter_send_burst_break',
			'default'     => 2,
			'type'        => 'number',
			'description' => 'Burst Wait',
			'help'        => 'Seconds to wait between sending batches of newsletters',
		),
		/*array(
				'key'=>'newsletter_notify_email',
				'default'=>'',
				'type'=>'text',
				'description'=>'Subscribe Notification',
				'help'=>'Email address to send subscribe notifications (ie: when a new member subscribes via your website)',
		),*/
		array(
			'key'         => 'newsletter_subscribe_redirect_double',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Subscribe Redirect (double opt-in)',
			'help'        => 'Full URL (including http://) to where the user is taken after confirming their double opt-in',
		),
		array(
			'key'         => 'newsletter_subscribe_redirect',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Subscribe Redirect',
			'help'        => 'Full URL (including http://) to where the user is taken after subscribing',
		),
		array(
			'key'         => 'newsletter_unsubscribe_redirect',
			'default'     => '',
			'type'        => 'text',
			'description' => 'Un-Subscribe Redirect URL',
			'help'        => 'Full URL (including http://) to where the user is taken after confirming their unsubscription',
		),
	),
);

module_config::print_settings_form(
	$settings
);

print_heading( 'Subscription Form' );

?>
<p><?php _e( 'Here is a link to the newsletter subscription form. When people fill this out they will appear in the "Members" area:' ); ?>
	<a href="<?php echo module_member::link_public_subscribe(); ?>"
	   target="_blank"><?php echo module_member::link_public_subscribe(); ?></a> <?php _e( '(this form can be edited from Settings > Templates)' ); ?>
</p>
<?php
print_heading( 'Un-Subscription Form' );

?>
<p><?php _e( 'Here is a link to the newsletter un-subscription form. People can click this and enter their email to be manually removed from receiving emails:' ); ?>
	<a href="<?php echo module_newsletter::unsubscribe_url(); ?>"
	   target="_blank"><?php echo module_newsletter::unsubscribe_url(); ?></a> <?php _e( '(this form can be edited from Settings > Templates)' ); ?>
</p>
<?php
print_heading( 'Other Forms' );

?>
<p><?php _e( 'Please see the other forms/messages under Settings > Templates. Look for the "newsletter" ones.' ); ?></p>

<?php
print_heading( 'Bounce Checking' );

?>
<p><?php _e( 'Please <a href="%s">click here</a> to check bounce emails manually.', module_newsletter::link_generate( false, array( 'page' => 'newsletter_settings_bounces' ) ) ); ?></p>