<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

if ( isset( $_REQUEST['ticket_data_key_id'] ) && $_REQUEST['ticket_data_key_id'] ) {
	$show_other_settings = false;
	$ticket_data_key_id  = (int) $_REQUEST['ticket_data_key_id'];
	if ( $ticket_data_key_id > 0 ) {
		$ticket_data_key = module_ticket::get_ticket_extras_key( $ticket_data_key_id );
	} else {
		$ticket_data_key = array(
			'ticket_account_id' => '',
			'key'               => '',
			'options'           => '',
			'type'              => '',
			'order'             => '',
		);
	}
	?>


	<form action="" method="post">
		<input type="hidden" name="_process" value="save_ticket_data_key">
		<input type="hidden" name="ticket_data_key_id" value="<?php echo $ticket_data_key_id; ?>"/>

		<?php ob_start();
		?>

		<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
			<tbody>
			<tr>
				<th class="width1">
					<?php echo _l( 'Name/Label' ); ?>
				</th>
				<td>
					<input type="text" name="key" value="<?php echo htmlspecialchars( $ticket_data_key['key'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th>
					<?php echo _l( 'Type' ); ?>
				</th>
				<td>
					<input type="radio" name="type"
					       value="text"<?php echo $ticket_data_key['type'] == 'text' ? ' checked' : ''; ?>><?php _e( 'Text' ); ?>
					<br/>
					<input type="radio" name="type"
					       value="textarea"<?php echo $ticket_data_key['type'] == 'textarea' ? ' checked' : ''; ?>><?php _e( 'Text Area' ); ?>
					<br/>
					<input type="radio" name="type"
					       value="select"<?php echo $ticket_data_key['type'] == 'select' ? ' checked' : ''; ?>><?php _e( 'Select' ); ?>
					<br/>
				</td>
			</tr>
			<tr>
				<th>
					<?php echo _l( 'Order' ); ?>
				</th>
				<td>
					<input type="text" name="order" value="<?php echo htmlspecialchars( $ticket_data_key['order'] ); ?>"/>
				</td>
			</tr>
			<?php if ( $ticket_data_key['type'] == 'select' ) {
				$options = isset( $ticket_data_key['options'] ) && $ticket_data_key['options'] ? unserialize( $ticket_data_key['options'] ) : array();
				?>
				<tr>
					<th>
						<?php _e( 'Drop Down Values:' ); ?>
					</th>
					<td>
                                <textarea rows="9" cols="30" name="options"><?php foreach ( $options as $key => $val ) {
		                                if ( ! strlen( $val ) ) {
			                                continue;
		                                }
		                                if ( ! is_numeric( $key ) && $key != $val ) {
			                                echo "$key|";
		                                }
		                                echo $val . "\n";
	                                } ?></textarea> <?php _h( 'Drop down values, one per line' ); ?>
					</td>
				</tr>
			<?php } ?>
			<?php if ( class_exists( 'module_encrypt', false ) && ( $ticket_data_key['type'] == 'text' || $ticket_data_key['type'] == 'textarea' ) ) { ?>
				<tr>
					<th>
						<?php echo _l( 'Encrypt Using Vault' ); ?>
					</th>
					<td>
						<?php
						$encryption_keys = module_encrypt::get_encrypt_keys();
						echo print_select_box( $encryption_keys, 'encrypt_key_id', isset( $ticket_data_key['encrypt_key_id'] ) ? $ticket_data_key['encrypt_key_id'] : false, '', true, 'encrypt_key_name', false ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<?php


		$fieldset_data = array(
			'heading'         => array(
				'type'  => 'h3',
				'main'  => true,
				'title' => 'Edit Ticket Data Key',
			),
			'elements_before' => ob_get_clean(),
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
					'type'    => 'delete_button',
					'name'    => 'butt_del',
					'value'   => _l( 'Delete' ),
					'onclick' => "return confirm('" . _l( 'Really delete this record?' ) . "');",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>

	</form>

	<?php
} else {


	print_heading( array(
		'title'  => 'Ticket Extra Fields',
		'type'   => 'h2',
		'main'   => true,
		'button' => array(
			'url'   => module_ticket::link_open_field( 'new' ),
			'title' => 'Add New Field',
			'type'  => 'add',
		),
	) );


	$ticket_data_keys = module_ticket::get_ticket_extras_keys();

	?>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
		<thead>
		<tr class="title">
			<th><?php echo _l( 'Ticket Extra Field' ); ?></th>
			<th><?php echo _l( 'Name' ); ?></th>
			<th><?php echo _l( 'Type' ); ?></th>
			<th><?php echo _l( 'Order' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$c = 0;
		foreach ( $ticket_data_keys as $ticket_data_key ) {
			?>
			<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
				<td class="row_action" nowrap="">
					<?php echo module_ticket::link_open_field( $ticket_data_key['ticket_data_key_id'], true ); ?>
				</td>
				<td>
					<?php echo htmlspecialchars( $ticket_data_key['key'] ); ?>
				</td>
				<td>
					<?php echo htmlspecialchars( $ticket_data_key['type'] ); ?>
				</td>
				<td>
					<?php echo htmlspecialchars( $ticket_data_key['order'] ); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>

<?php } ?>