<?php

if ( empty( $note_edit_safe ) ) {
	die( 'failed' );
}


?>

<?php
$note_elements = array(
	'note_time' => array(
		'title' => _l( 'Date' ),
		'field' => array(
			'type'  => 'text',
			'name'  => 'note_time',
			'id'    => 'form_note_time',
			'class' => 'date_time_field',
			'value' => ! empty( $note['note_time'] ) ? $note['note_time'] : 0,
		),
	),
);

if ( module_config::c( 'allow_note_reminders', 1 ) ) {
	ob_start();
	?>
	<input type="checkbox" name="reminder" id="form_reminder"
	       value="1" <?php echo $note['reminder'] ? ' checked' : ''; ?> />
	<?php _e( 'for' ); ?>
	<?php
	// we use the same staff listing we have in jobs.
	$staff_members    = module_user::get_staff_members();
	$staff_member_rel = array();
	foreach ( $staff_members as $staff_member ) {
		$staff_member_rel[ $staff_member['user_id'] ] = $staff_member['name'];
	}
	echo print_select_box( $staff_member_rel, 'user_id', $note['user_id'], 'form_user_id', _l( 'All Staff' ) );
	_h( 'Sets a dashboard reminder for the above date. This will appear on the selected users dashboard.<br><br>Untick to remove reminder.<br><br>Staff are users who have Job Task EDIT permissions.' );
	$note_elements['reminder'] = array(
		'title'  => _l( 'Reminder' ),
		'fields' => array( ob_get_clean() ),
	);
}

$note_elements['note'] = array(
	'title' => _l( 'Note' ),
	'field' => array(
		'type'  => 'textarea',
		'name'  => 'note',
		'id'    => 'form_note_data',
		'rows'  => 5,
		'cols'  => 40,
		'value' => $note['note'],
	),
);

// support for the 'public' flag on notes. at the moment this is only used in invoices.
// later on we could mark notes as public so the customer can see them when they log in, but not see other non-public ones.
if ( ! empty( $options['public'] ) && ! empty( $_SESSION['note_public'] ) ) {
	$options['public'] = $_SESSION['note_public'];
}
if ( isset( $options['public'] ) && is_array( $options['public'] ) && isset( $options['public']['enabled'] ) && $options['public']['enabled'] ) {
	ob_start();
	?>
	<input type="hidden" name="public_chk" value="1">
	<input type="checkbox" name="public" id="form_public"
	       value="1" <?php echo $note['public'] ? ' checked' : ''; ?> />
	<?php
	if ( isset( $options['public']['text'] ) && $options['public']['text'] ) {
		echo $options['public']['text'];
	}
	if ( isset( $options['public']['help'] ) && $options['public']['help'] ) {
		_h( $options['public']['help'] );
	}
	$note_elements['public'] = array(
		'title'  => ( isset( $options['public']['title'] ) && $options['public']['title'] )
			? $options['public']['title'] : '',
		'fields' => array( ob_get_clean() ),
	);
}

if ( $note_id && $note_id != 'new' ) {
	$creator = module_user::get_user( $note['create_user_id'] );
	$note_elements['creator'] = array(
		'title'  => _l( 'Creator' ),
		'fields' => array( $creator['name'] . ' on ' . print_date( $note['date_created'], true ) ),
	);

	if ( $note['update_user_id'] ) {
		$updater = module_user::get_user( $note['update_user_id'] );
		$updated = $updater['name'] . ' on ' . print_date( $note['date_updated'], true );
	} else {
		$updated = 'never';
	}
	$note_elements['updated'] = array(
		'title'  => _l( 'Updated' ),
		'fields' => array( $updated ),
	);
}

echo module_form::generate_fieldset( array(
	'class'    => 'tableclass tableclass_form',
	'elements' => $note_elements,
) );
