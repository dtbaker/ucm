<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}


if ( isset( $_REQUEST['ticket_account_id'] ) && $_REQUEST['ticket_account_id'] ) {
	$show_other_settings = false;
	$ticket_account_id   = (int) $_REQUEST['ticket_account_id'];
	if ( $ticket_account_id > 0 ) {
		$ticket_account = module_ticket::get_ticket_account( $ticket_account_id );
	} else {
		$ticket_account = array(
			'name'                => '',
			'email'               => '',
			'username'            => '',
			'password'            => '',
			'host'                => '',
			'port'                => '110',
			'delete'              => '0',
			'default_customer_id' => '0',
			'default_user_id'     => '0',
			'default_type'        => 0,
			'subject_regex'       => '',
			'body_regex'          => '',
			'to_regex'            => '',
			'start_date'          => '',
			'search_string'       => '',
			'mailbox'             => 'INBOX',
			'imap'                => 0,
			'secure'              => 0,
		);
	}
	?>
	<!-- updated -->

	<?php
	module_form::print_form_open_tag( array(
		'process' => 'save_ticket_account',
		'hidden'  => array( 'ticket_account_id' => $ticket_account_id ),
	) );
	?>

		<?php
		$c = array();
		foreach ( module_customer::get_customers() as $row ) {
			$c[ $row['customer_id'] ] = $row['customer_name'];
		}

		$fieldset_data = array(
			'heading'  => array(
				'type'  => 'h3',
				'main'  => true,
				'title' => 'Edit Ticket Account',
			),
			'class'    => 'tableclass tableclass_form',
			'elements' => array(
				'name'     => array(
					'title' => _l( 'Name/Label' ),
					'field'  => array(
						'type'  => 'text',
						'name'  => 'name',
						'value' => $ticket_account['name'],
					),
				),
				'email'    => array(
					'title' => _l( 'Email Address' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'email',
						'value' => $ticket_account['email'],
					),
				),
				'imap'     => array(
					'title' => _l( 'Connection Type' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'imap',
						'value'   => $ticket_account['imap'],
						'options' => array( 0 => _l( 'POP3' ), 1 => _l( 'IMAP' ) ),
						'blank'   => false,
					),
				),
				'secure'   => array(
					'title' => _l( 'Secure/SSL' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'secure',
						'value'   => $ticket_account['secure'],
						'options' => array( 0 => _l( 'No' ), 1 => _l( 'Yes' ) ),
						'blank'   => false,
					),
				),
				'username' => array(
					'title' => _l( 'Username' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'username',
						'value' => $ticket_account['username'],
					),
				),
				'password' => array(
					'title' => _l( 'Password' ),
					'field' => array(
						'type'  => 'password',
						'name'  => 'password',
						'value' => $ticket_account['password'],
					),
				),
				'host'     => array(
					'title' => _l( 'Hostname' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'host',
						'value' => $ticket_account['host'],
					),
				),
				'port'     => array(
					'title' => _l( 'Port' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'port',
						'value' => $ticket_account['port'],
					),
				),
				'delete'   => array(
					'title' => _l( 'Delete' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'delete',
						'value'   => $ticket_account['delete'],
						'options' => array(
							1 => _l( 'Yes, delete emails from account after importing' ),
							0 => _l( 'No, leave copy of email in account after importing' ),
						),
						'blank'   => false,
					),
				),
				'default_customer_id' => array(
					'title' => _l( 'Default Customer' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'default_customer_id',
						'value'   => $ticket_account['default_customer_id'],
						'options' => $c,
						'blank'   => true,
					),
				),
				'default_user_id' => array(
					'title' => _l( 'Default Assigned Staff Members' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'default_user_id',
						'value'   => $ticket_account['default_user_id'],
						'options' => module_ticket::get_ticket_staff_rel(),
						'blank'   => true,
					),
				),
				'default_type' => array(
					'title'  => _l( 'Default Type' ),
					'fields' => array(
						array(
							'type'             => 'select',
							'name'             => 'default_type',
							'value'            => $ticket_account['default_type'],
							'options'          => module_ticket::get_types(),
							'options_array_id' => 'name',
							'blank'            => true,
						),
						' ',
						function () {
							_h( 'If a Ticket Type is selected, and it has a Default Staff member assigned in the Settings area, it will overwrite the default Staff Member from this page' );
						},
					),
				),
				'imap_header' => array(
					'title' => _l( 'IMAP Settings:' ),
					'fields' => array( '' ),
				),
				'search_string' => array(
					'title' => _l( 'IMAP Search String' ),
					'fields' => array(
						array(
							'type'   => 'text',
							'name'   => 'search_string',
							'value'  => $ticket_account['search_string'],
							'style'  => 'width:80%',
						),
						'<br/>' . _l( '(eg: TO "you@site.com" SINCE 10-May-2011 - see' ) . ' <a href="http://php.net/imap_search" target="_blank">http://php.net/imap_search</a> ' . _l( 'for more details)' ),
					),
				),
				'advanced_header' => array(
					'title' => _l( 'Advanced Settings:' ),
					'fields' => array( '' ),
				),
				'mailbox' => array(
					'title' => _l( 'Mailbox' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'mailbox',
							'value' => $ticket_account['mailbox'],
						),
						' ' . _l( '(which mailbox to read / gmail label to read. Default is INBOX)' ),
					),
				),
				'subject_regex' => array(
					'title' => _l( 'Regex Subject Match' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'subject_regex',
							'value' => $ticket_account['subject_regex'],
						),
						' ' . _l( '(advanced: read about regex' ) . ' <a href="http://en.wikipedia.org/wiki/Regular_expression" target="_blank">here</a>, ' . _l( 'example: #support email#i)' ),
					),
				),
				'body_regex' => array(
					'title' => _l( 'Regex Body Match' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'body_regex',
							'value' => $ticket_account['body_regex'],
						),
						' ' . _l( '(advanced: read about regex' ) . ' <a href="http://en.wikipedia.org/wiki/Regular_expression" target="_blank">here</a>, ' . _l( 'example: #support email#i)' ),
					),
				),
				'to_regex' => array(
					'title' => _l( 'Regex To Match' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'to_regex',
							'value' => $ticket_account['to_regex'],
						),
						' ' . _l( '(advanced: read about regex' ) . ' <a href="http://en.wikipedia.org/wiki/Regular_expression" target="_blank">here</a>, ' . _l( 'example: #foo@bar\.com#i)' ),
					),
				),
				'start_date' => array(
					'title' => _l( 'Start Date' ),
					'fields' => array(
						array(
							'type'   => 'date',
							'name'   => 'start_date',
							'value'  => print_date( $ticket_account['start_date'] ),
						),
						' ' . _l( '(only return emails after this date - SLOW! - better to use imap method with a SINCE search string)' ),
					),
				),
			),
		);

		echo module_form::generate_fieldset( $fieldset_data );
		unset( $fieldset_data );


		$form_actions = array(
			'class'    => 'action_bar action_bar_center action_bar_single',
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_save',
					'value' => _l( 'Save' ),
				),
				array(
					'type'    => 'submit',
					'name'    => 'butt_save_test',
					'value'   => 'Save & Test Search',
					'onclick' => "alert('This may take some time, please wait...');;",
				),
				array(
					'type'    => 'delete_button',
					'name'    => 'butt_del',
					'value'   => _l( 'Delete' ),
					'onclick' => "return confirm('" . _l( 'Really delete this record?' ) . "');",
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . module_ticket::link_open_account( false ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>


	<?php module_form::print_form_close_tag(); ?>

	<?php
} else {


	print_heading( array(
		'title'  => 'Ticket Email Accounts',
		'type'   => 'h2',
		'main'   => true,
		'button' => array(
			'url'   => module_ticket::link_open_account( 'new' ),
			'title' => 'Add New Account',
			'type'  => 'add',
		),
	) );


	$ticket_accounts = module_ticket::get_accounts();

	?>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
		<thead>
		<tr class="title">
			<th><?php echo _l( 'Ticket Account' ); ?></th>
			<th><?php echo _l( 'Email Address' ); ?></th>
			<th><?php echo _l( 'Default Type' ); ?></th>
			<th><?php echo _l( 'Default Customer' ); ?></th>
			<th><?php echo _l( 'Default User' ); ?></th>
			<th><?php echo _l( 'Last Checked' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$c     = 0;
		$types = module_ticket::get_types();
		foreach ( $ticket_accounts as $ticket_account ) {
			?>
			<tr class="<?php echo ( $c++ % 2 ) ? "odd" : "even"; ?>">
				<td class="row_action" nowrap="">
					<?php echo module_ticket::link_open_account( $ticket_account['ticket_account_id'], true ); ?>
				</td>
				<td>
					<?php echo htmlspecialchars( $ticket_account['email'] ); ?>
				</td>
				<td>
					<?php echo htmlspecialchars( isset( $types[ $ticket_account['default_type'] ] ) ? $types[ $ticket_account['default_type'] ]['name'] : $ticket_account['default_type'] ); ?>
				</td>
				<td>
					<?php echo module_customer::link_open( $ticket_account['default_customer_id'], true ); ?>
				</td>
				<td>
					<?php echo module_user::link_open( $ticket_account['default_user_id'], true ); ?>
				</td>
				<td>
					<?php echo print_date( $ticket_account['last_checked'] ); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

<?php } ?>