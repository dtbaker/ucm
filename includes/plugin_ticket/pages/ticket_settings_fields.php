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


	<?php
	module_form::print_form_open_tag( array(
		'process' => 'save_ticket_data_key',
		'hidden'  => array( 'ticket_data_key_id' => $ticket_data_key_id ),
	) );
	?>

		<?php
		$options_value = '';
		if ( $ticket_data_key['type'] == 'select' && isset( $ticket_data_key['options'] ) && $ticket_data_key['options'] ) {
			$options_arr = unserialize( $ticket_data_key['options'], array( 'allowed_classes' => false ) );
			if ( is_array( $options_arr ) ) {
				foreach ( $options_arr as $key => $val ) {
					if ( ! strlen( $val ) ) {
						continue;
					}
					if ( ! is_numeric( $key ) && $key != $val ) {
						$options_value .= $key . '|';
					}
					$options_value .= $val . "\n";
				}
			}
		}

		$fieldset_data = array(
			'heading'  => array(
				'type'  => 'h3',
				'main'  => true,
				'title' => 'Edit Ticket Data Key',
			),
			'class'    => 'tableclass tableclass_form',
			'elements' => array(
				'key'   => array(
					'title'           => _l( 'Name/Label' ),
					'row_title_class' => 'width1',
					'field'           => array(
						'type'  => 'text',
						'name'  => 'key',
						'value' => $ticket_data_key['key'],
					),
				),
				'type'  => array(
					'title' => _l( 'Type' ),
					'field' => array(
						'type'    => 'select',
						'name'    => 'type',
						'value'   => $ticket_data_key['type'],
						'options' => array(
							'text'     => _l( 'Text' ),
							'textarea' => _l( 'Text Area' ),
							'select'   => _l( 'Select' ),
						),
						'blank'   => false,
					),
				),
				'order' => array(
					'title' => _l( 'Order' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'order',
						'value' => $ticket_data_key['order'],
					),
				),
				'options' => array(
					'title'  => _l( 'Drop Down Values:' ),
					'fields' => array(
						array(
							'type'  => 'textarea',
							'name'  => 'options',
							'value' => $options_value,
							'style' => 'min-height: 150px;',
						),
						' ',
						function () {
							_h( 'Drop down values, one per line' );
						},
					),
				),
			),
		);

		if ( class_exists( 'module_encrypt', false ) ) {
			$fieldset_data['elements']['encrypt_key_id'] = array(
				'title' => _l( 'Encrypt Using Vault' ),
				'field' => array(
					'type'             => 'select',
					'name'             => 'encrypt_key_id',
					'value'            => isset( $ticket_data_key['encrypt_key_id'] ) ? $ticket_data_key['encrypt_key_id'] : false,
					'options'          => module_encrypt::get_encrypt_keys(),
					'options_array_id' => 'encrypt_key_name',
					'blank'            => true,
				),
			);
		}

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

	<?php module_form::print_form_close_tag(); ?>

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
			<tr class="<?php echo ( $c++ % 2 ) ? "odd" : "even"; ?>">
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