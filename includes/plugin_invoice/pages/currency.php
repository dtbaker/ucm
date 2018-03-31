<?php

if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}
if ( ! module_config::can_i( 'edit', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

if ( isset( $_REQUEST['currency_id'] ) ) {
	$currency_id = (int) $_REQUEST['currency_id'];

	$currency = get_single( 'currency', 'currency_id', $currency_id );

	if ( isset( $_REQUEST['butdelete_currency'] ) ) {

		if ( module_form::confirm_delete( 'currency_id', 'Really delete currency: ' . htmlspecialchars( $currency['code'] ) ) ) {
			delete_from_db( 'currency', 'currency_id', $currency_id );
			set_message( _l( 'Currency deleted successfully' ) );
			redirect_browser( $_SERVER['REQUEST_URI'] . ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false ? '?' : '&' ) . 'deleted=true' );
		}

	} else if ( isset( $_REQUEST['save'] ) ) {
		update_insert( 'currency_id', $currency_id, 'currency', $_POST );
		set_message( 'Currency saved successfully' );
		//redirect_browser('?saved=true');
		redirect_browser( $_SERVER['REQUEST_URI'] . ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false ? '?' : '&' ) . 'saved=true' );
	}

	$currency = get_single( 'currency', 'currency_id', $currency_id );

	print_heading( array(
		'title' => 'Edit Currency',
		'type'  => 'h2',
		'main'  => true,
	) );

	?>

	<form action="" method="post">
		<input type="hidden" name="currency_id" value="<?php echo $currency_id; ?>">
		<input type="hidden" name="save" value="true">

		<?php
		$fieldset_data = array(
			'elements' => array(
				array(
					'title' => _l( 'Code' ),
					'field' => array(
						'name'  => 'code',
						'value' => isset( $currency['code'] ) ? $currency['code'] : '',
						'type'  => 'text',
						'help'  => 'Example: USD or AUD',
					)
				),
				array(
					'title' => _l( 'Symbol' ),
					'field' => array(
						'name'  => 'symbol',
						'value' => isset( $currency['symbol'] ) ? $currency['symbol'] : '',
						'type'  => 'text',
						'help'  => 'Example: $ or &pound;',
					)
				),
				array(
					'title' => _l( 'Position' ),
					'field' => array(
						'name'    => 'location',
						'value'   => isset( $currency['location'] ) ? $currency['location'] : '',
						'type'    => 'select',
						'options' => array(
							1 => 'before',
							0 => 'after',
						),
					)
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
					'name'  => 'save',
					'value' => _l( 'Save' ),
				),
				array(
					'ignore' => ! ( $currency_id > 0 ),
					'type'   => 'delete_button',
					'name'   => 'butdelete_currency',
					'value'  => _l( 'Delete' ),
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>
	</form>

	<?php

} else {

	print_heading( array(
		'title'  => 'Currency',
		'type'   => 'h2',
		'main'   => true,
		'button' => array(
			'url'   => $_SERVER['REQUEST_URI'] . ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false ? '?' : '&' ) . 'currency_id=new',
			'title' => 'Add New',
			'type'  => 'add',
		),
	) );

	?>

	<form action="" method="post">

		<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
			<thead>
			<tr class="title">
				<th><?php echo _l( 'Code' ); ?></th>
				<th><?php echo _l( 'Symbol' ); ?></th>
				<th><?php echo _l( 'Example' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php
			$c = 0;
			foreach ( get_multiple( 'currency' ) as $currency ) { ?>
				<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
					<td class="row_action">
						<a
							href="<?php echo $_SERVER['REQUEST_URI'] . ( strpos( $_SERVER['REQUEST_URI'], '?' ) === false ? '?' : '&' ); ?>currency_id=<?php echo $currency['currency_id']; ?>"><?php echo htmlspecialchars( $currency['code'] ); ?></a>
						<?php if ( $currency['currency_id'] == module_config::c( 'default_currency_id', 1 ) ) {
							_e( '(default)' );
						} ?>
					</td>
					<td>
						<?php echo htmlspecialchars( $currency['symbol'] ); ?>
					</td>
					<td>
						<?php echo dollar( 1234.56, true, $currency['currency_id'] ); ?>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</form>

	<?php


	$currencies = array();
	foreach ( get_multiple( 'currency', '', 'currency_id' ) as $currency ) {
		$currencies[ $currency['currency_id'] ] = $currency['code'] . ' ' . $currency['symbol'];
	}

	$settings = array(
		array(
			'key'         => 'default_currency_id',
			'default'     => '1',
			'type'        => 'select',
			'description' => 'Default currency to use throughout the system',
			'options'     => $currencies,
		),
	);

	module_config::print_settings_form(
		$settings
	);

}