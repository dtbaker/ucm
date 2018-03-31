<?php

list( $db_table, $db_key, $db_val, $config ) = explode( "|", $_REQUEST['hash'] );
$config = unserialize( base64_decode( $config ) );

if ( isset( $config['db_search'] ) ) {
	$search = $config['db_search'];
} else {
	$search = array();
}

$allow_delete = isset( $config['allow_delete'] ) ? $config['allow_delete'] : false;

if ( isset( $config['fields'] ) ) {
	$fields = $config['fields'];
} else {
	$fields = array(
		"$db_key" => array(
			'size'  => 5,
			'title' => "Key",
		),
		"$db_val" => array(
			'title' => "Value",
		),
	);
}
?>
	<iframe src="about:blank" name="config_popup_frame" id="config_popup_frame" style="display:none;"></iframe>
	<form action="<?php echo $module->link(); ?>" method="post" target="config_popup_frame">
		<input type="hidden" name="_process" value="save_select_box_popup">
		<input type="hidden" name="hash" value="<?php echo htmlspecialchars( $_REQUEST['hash'] ); ?>">

		<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
			<thead>
			<tr>
				<?php foreach ( $fields as $key => $data ) {
					?>
					<th style="<?php echo ( isset( $data['hidden'] ) && $data['hidden'] ) ? 'display:none;' : ''; ?>">
						<?php echo ( isset( $data['title'] ) ) ? $data['title'] : $key; ?>
					</th>
				<?php } ?>
				<?php if ( $allow_delete ) { ?>
					<th></th> <?php } ?>
			</tr>
			</thead>
			<tbody>
			<?php
			$data_rows = get_multiple( $db_table, $search );
			foreach ( $data_rows as $row ) {
				$primary_key = $row[ $db_key ];
				?>
				<tr>
					<?php foreach ( $fields as $key => $data ) {
						$value = $row[ $key ];
						if ( ! $value && isset( $data['default'] ) ) {
							$value = $data['default'];
						}
						if ( ( isset( $data['hidden'] ) && $data['hidden'] ) ) { ?>
							<td style="display:none;"><input type="hidden"
							                                 name="data[<?php echo $primary_key; ?>][<?php echo $key; ?>]"
							                                 value="<?php echo $value; ?>">
							</td>
						<?php } else { ?>
							<td>
								<?php if ( isset( $data['attributes'] ) ) {
									echo print_select_box( $data['attributes'], 'data[' . $primary_key . '][' . $key . ']', $value );
								} else { ?>
									<input type="text" name="data[<?php echo $primary_key; ?>][<?php echo $key; ?>]"
									       value="<?php echo $value; ?>" size="<?php
									echo( isset( $data['size'] ) ? $data['size'] : '20' ); ?>">
								<?php } ?>
							</td>
						<?php }
					}
					if ( $allow_delete ) { ?>
						<td>
							<input type="hidden" name="data[<?php echo $primary_key; ?>][_delete_key_]" value=""
							       id="delete_<?php echo $primary_key; ?>">
							<a href="#"
							   onclick="$('#delete_<?php echo $primary_key; ?>').val('yes'); $(this).parents('tr').first().hide(); return false;">[x]</a>
						</td>
					<?php } ?>
				</tr>
			<?php } ?>
			<tr>
				<?php
				$primary_key = 'new';
				foreach ( $fields as $key => $data ) {
					$value = '';
					if ( isset( $data['default'] ) ) {
						$value = $data['default'];
					}
					if ( ( isset( $data['hidden'] ) && $data['hidden'] ) ) { ?>
						<td style="display:none;"><input type="hidden" name="data[<?php echo $primary_key; ?>][<?php echo $key; ?>]"
						                                 value="<?php echo $value; ?>">
						</td>
					<?php } else { ?>
						<td>
							<?php if ( isset( $data['attributes'] ) ) {
								echo print_select_box( $data['attributes'], 'data[' . $primary_key . '][' . $key . ']', $value );
							} else { ?>
								<input type="text" name="data[<?php echo $primary_key; ?>][<?php echo $key; ?>]"
								       value="<?php echo $value; ?>" size="<?php
								echo( isset( $data['size'] ) ? $data['size'] : '20' ); ?>">
							<?php } ?>
						</td>
					<?php }
				}
				if ( $allow_delete ) { ?>
					<td>

					</td> <?php } ?>
			</tr>
			</tbody>
		</table>

	</form>

<?php


module_debug::push_to_parent();
exit;