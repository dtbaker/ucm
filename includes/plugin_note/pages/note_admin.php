<?php


if ( isset( $_REQUEST['note_id'] ) ) {

	// re-check permissions sent in from ajax.
	// there could be a way to trick this, if someone can find it my congratulations to them!
	$options = unserialize( base64_decode( $_REQUEST['options'] ) );
	if ( ! $options ) {
		return;
	}

	$can_view = $can_edit = $can_create = $can_delete = false;
	if ( isset( $options ) && isset( $options['owner_table'] ) && $options['owner_table'] && isset( $options['title'] ) && $options['title'] ) {
		global $plugins;
		$can_view   = $plugins[ $options['owner_table'] ]->can_i( 'view', $options['title'] );
		$can_edit   = $plugins[ $options['owner_table'] ]->can_i( 'edit', $options['title'] );
		$can_create = $plugins[ $options['owner_table'] ]->can_i( 'create', $options['title'] );
		$can_delete = $plugins[ $options['owner_table'] ]->can_i( 'delete', $options['title'] );
	} else {
		return;
	}

	if ( ! $can_view && ! $can_edit ) {
		return;
	}

	$note_id = (int) $_REQUEST['note_id'];
	if ( $note_id > 0 ) {
		$note        = module_note::get_note( $note_id );
		$owner_table = $note['owner_table'];
		$owner_id    = $note['owner_id'];

		if ( $can_delete && isset( $options['do_delete'] ) && $options['do_delete'] == 'yes' && isset( $options['note_id'] ) && $options['note_id'] ) {
			module_note::note_delete( $owner_table, $owner_id, $options['note_id'] );
			set_message( 'Note deleted successfully' );
			redirect_browser( $note['rel_data'] );
		}

	} else {
		$owner_table = isset( $options['owner_table'] ) ? $options['owner_table'] : ( isset( $_REQUEST['owner_table'] ) ? htmlspecialchars( $_REQUEST['owner_table'] ) : '' );
		$owner_id    = isset( $options['owner_id'] ) ? $options['owner_id'] : ( isset( $_REQUEST['owner_id'] ) ? htmlspecialchars( $_REQUEST['owner_id'] ) : '' );
		$note        = array(
			"note_time" => time(),
			"note"      => '',
			"reminder"  => '',
			"user_id"   => '',
			'rel_data'  => ( isset( $rel_data ) ) ? serialize( $rel_data ) : '',
			'public'    => 0,
		);
	}
	$note_edit_safe = true;
	if ( get_display_mode() != 'ajax' ) {
		print_heading( 'Note' );
	}
	//print_r($options);
	if ( isset( $options['view_link'] ) ) {
		$note['rel_data'] = $options['view_link'];
	}
	?>
	<form action="<?php
	echo $plugins['note']->link( 'note_edit', array(
		'_process'    => 'save_note',
		'owner_table' => $owner_table,
		'owner_id'    => $owner_id,
		'note_id'     => $note_id,
	) );
	?>" method="post">
		<input type="hidden" name="note_id" value="<?php echo (int) $note_id; ?>" id="note_id">
		<input type="hidden" name="options" value="<?php echo base64_encode( serialize( $options ) ); ?>">
		<input type="hidden" name="rel_data" id="form_rel_data" value="<?php echo $note['rel_data']; ?>">

		<?php include( 'note_edit.php' ); ?>

		<?php
		if ( get_display_mode() != 'ajax' ) { ?>
			<input type="submit" name="save" value="<?php _e( 'Save Note' ); ?>" class="submit_button save_button">
			<?php if ( $note['rel_data'] ) {
				?>
				<input type="button" name="cancel" value="<?php _e( 'Cancel' ); ?>"
				       onclick="window.location.href='<?php echo htmlspecialchars( $note['rel_data'] ); ?>';"
				       class="submit_button">
				<?php
			}
		}
		?>
	</form>
	<?php
}