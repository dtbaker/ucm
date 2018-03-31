<?php
$hash      = $_REQUEST['hash'];
$form_data = unserialize( base64_decode( $_SESSION['_delete_data'][ $hash ] ) );
if ( ! $form_data ) {
	echo 'Error, please go back and try again';
	exit;
}

$module->page_title = _l( 'Delete Confirmation' );

//$data = array($message,$post_data,$post_uri,$cancel_url);

ob_start();
?>

	<form action="<?php echo $form_data[2]; ?>" method="post">
		<input type="hidden" name="_confirm_delete" value="<?php echo htmlspecialchars( $hash ); ?>">
		<?php foreach ( $form_data[1] as $key => $val ) {
			if ( is_array( $val ) ) {
				foreach ( $val as $key2 => $val2 ) {
					if ( is_array( $val2 ) ) {
						continue;
					}
					?>
					<input type="hidden" name="<?php echo htmlspecialchars( $key ); ?>[<?php echo htmlspecialchars( $key2 ); ?>]"
					       value="<?php echo htmlspecialchars( $val2 ); ?>">
					<?php
				}
			} else {
				?>
				<input type="hidden" name="<?php echo htmlspecialchars( $key ); ?>"
				       value="<?php echo htmlspecialchars( $val ); ?>">
			<?php } ?>
		<?php } ?>
		<?php if ( isset( $form_data[5] ) && is_array( $form_data[5] ) && isset( $form_data[5]['options'] ) ) {
			foreach ( $form_data[5]['options'] as $option ) {
				echo htmlspecialchars( $option['label'] );
				module_form::generate_form_element( $option );
				echo '<br/>';
			}
			?>
		<?php } ?>

		<input type="hidden" name="really_confirm_delete" id="really_confirm_delete" value="">
		<?php $form_actions = array(
			'class'    => 'action_bar action_bar_center action_bar_single',
			'elements' => array(
				array(
					'type'    => 'delete_button',
					'name'    => 'butt_del',
					'value'   => _l( 'Confirm Delete' ),
					'onclick' => "$('#really_confirm_delete').val('yep');",
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $form_data[3] . "'; return false;",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions ); ?>
	</form>

<?php
$fieldset_data = array(
	'heading'         => is_array( $form_data[0] ) ? $form_data[0] : array(
		'title' => $form_data[0],
		'type'  => 'h3',
	),
	'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset( $fieldset_data );
unset( $fieldset_data );


