<?php
if ( ! $note_list_safe ) {
	die( 'fail' );
}
//print_r($note_items);exit;
if ( ! isset( $popup_links ) ) {
	$popup_links = true;
}
$link_options = $options;
if ( isset( $link_options['summary_owners'] ) ) {
	unset( $link_options['summary_owners'] );
}
if ( isset( $link_options['display_summary'] ) ) {
	unset( $link_options['display_summary'] );
}
//$link_options['title'] = '1';
//if(isset($link_options['title']))unset($link_options['title']);
$fieldset_data = array();
if ( isset( $options['title'] ) && $options['title'] ) {

	$fieldset_data['heading'] = array(
		'title' => $options['title'],
		'type'  => 'h3',
	);
	if ( $can_create ) {
		$fieldset_data['heading']['button'] = array(
			'title'      => _l( 'Add New Note' ),
			'url'        => module_note::link_open( 'new', false, $link_options ),
			'class'      => 'no_permissions',
			'ajax-modal' => array(
				'type'          => 'normal',
				'load_callback' => 'note_popup_opened',
				'buttons'       => array(
					_l( 'Save note' ) => 'save_note_callback',
					_l( 'Cancel' )    => 'close',
				),
			),
		);
	}
} else if ( $can_create ) {
	?>
	<a href="<?php echo module_note::link_open( 'new', false, $link_options ); ?>"
	   class="uibutton note_options_link note_add no_permissions"><?php _e( 'Add New Note' ); ?></a>
	<?php
	//<div class="content_box_wheader">
}

if ( get_display_mode() != 'mobile' ) {
	$pagination = process_pagination( $note_items, module_config::c( 'notes_per_page', 20 ), 0, 'note' . md5( serialize( $link_options ) ) );
} else {
	$pagination = array(
		'rows'         => $note_items,
		'links'        => '',
		'page_numbers' => 1,
	);
}
ob_start();

/** START TABLE LAYOUT **/
$table_manager          = module_theme::new_table_manager();
$columns                = array();
$columns['date']        = array(
	'title'    => 'Date',
	'width'    => 60,
	'callback' => function ( $note_item ) {
		if ( $note_item['reminder'] ) {
			echo '<strong>';
		}
		echo print_date( $note_item['note_time'] );
		if ( $note_item['reminder'] ) {
			echo '</strong>';
		}
	}
);
$columns['description'] = array(
	'title'    => 'Description',
	'callback' => function ( $note_item ) {
		if ( isset( $note_item['public'] ) && $note_item['public'] ) {
			echo '* ';
		}
		if ( $note_item['can_edit'] ) {
			if ( function_exists( 'mb_substr' ) && function_exists( 'mb_strlen' ) ) {
				$note_text = nl2br( htmlspecialchars( mb_substr( $note_item['note'], 0, module_config::c( 'note_trim_length', 35 ) ) ) );
				$note_text .= mb_strlen( $note_item['note'] ) > module_config::c( 'note_trim_length', 35 ) ? '...' : '';
			} else {
				$note_text = nl2br( htmlspecialchars( substr( $note_item['note'], 0, module_config::c( 'note_trim_length', 35 ) ) ) );
				$note_text .= strlen( $note_item['note'] ) > module_config::c( 'note_trim_length', 35 ) ? '...' : '';
			}
			// IMPORTANT. also update note.php with this same code:
			?>
			<a href="<?php echo module_note::link_open( $note_item['note_id'], false, $note_item['options'] ); ?>"
			   rel="<?php echo $note_item['note_id']; ?>"
			   data-ajax-modal='{"type":"normal","title":"<?php _e( 'Note' ); ?>","load_callback":"note_popup_opened","buttons":{"<?php _e( 'Save note' ); ?>":"save_note_callback","<?php _e( 'Cancel' ); ?>":"close"}}'> <?php echo $note_text; ?> </a>
		<?php } else {
			echo forum_text( $note_item['note'] );
		}
	}
);
$columns['info']        = array(
	'title'    => 'Info',
	'width'    => 40,
	'callback' => function ( $note_item ) {
		if ( module_config::c( 'note_show_creator', 1 ) ) {
			$user_data = module_user::get_user( $note_item['create_user_id'] );
			echo htmlspecialchars( $user_data['name'] );
		}
		if ( $note_item['display_summary'] && $note_item['rel_data'] && $note_item['owner_id'] ) {
			global $plugins;
			if ( module_config::c( 'note_show_creator', 1 ) ) {
				echo ' / ';
			}
			echo $plugins[ $note_item['owner_table'] ]->link_open( $note_item['owner_id'], true );
		}
	}
);
if ( $can_delete ) {
	$columns['del'] = array(
		'title'    => ' ',
		'callback' => function ( $note_item ) {
			if ( $note_item['can_delete'] ) {
				?> <a
					href="<?php echo module_note::link_open( $note_item['note_id'], false, array_merge( $note_item['options'], array(
						'do_delete' => 'yes',
						'note_id'   => $note_item['note_id']
					) ) ); ?>"
					data-options="<?php echo htmlspecialchars( base64_encode( serialize( array_merge( $note_item['options'], array(
						'do_delete' => 'yes',
						'note_id'   => $note_item['note_id']
					) ) ) ) ); ?>" rel="<?php echo $note_item['note_id']; ?>"
					onclick="if(confirm('<?php _e( 'Really Delete Note?' ); ?>'))return true; else return false;"
					class="note_delete note_options_link delete"><i class="fa fa-trash"></i></a> <?php
			}
		}
	);
}
$table_manager->set_columns( $columns );
$table_manager->set_rows( $note_items );
$table_manager->table_id                    = 'note_' . $owner_table . '_' . $owner_id;
$table_manager->inline_table                = true;
$table_manager->pagination                  = true;
$table_manager->pagination_per_page         = module_config::c( 'notes_per_page', 20 );
$table_manager->pagination_hide_single_page = true;
$table_manager->row_callback                = function ( $row_data, &$row_object ) use ( $display_summary, $can_edit, $can_delete, &$options ) {
	$row_data['display_summary'] = $display_summary;
	$row_data['can_edit']        = $can_edit;
	$row_data['can_delete']      = $can_delete;
	$row_data['options']         = $options;
	$row_object->row_id          = 'note_' . $row_data['note_id'];

	return $row_data;
};
$table_manager->print_table();
//if(!count($note_items))echo ' display:none; '; todo
if ( ! count( $note_items ) ) {
	?>
	<style type="text/css">
		#<?php echo $table_manager->table_id;?>{ display:none; }
	</style>
	<?php
}

$fieldset_data['elements_before'] = ob_get_clean();
echo module_form::generate_fieldset( $fieldset_data );

if ( $popup_links ) { ?>
	<script type="text/javascript">
      var edit_note_id = 0;
      var edit_note_changed = false;

      function note_popup_opened($modal) {
          edit_note_id = parseInt($modal.find('#note_id').val());
				<?php if($rel_data){ ?>
          if (!edit_note_id) {
              $('#form_rel_data').val('<?php echo $rel_data;?>');
          }
				<?php } ?>
      }

      function save_note_callback($modal) {
          var $form = $modal.find('form').first();
          $.ajax({
              type: "POST",
              url: $form.attr('action'),
              data: $form.serialize() + '&display_mode=ajax',
              success: function (h) {
                  // wohoo, this works!
                  $('#note_<?php echo $owner_table;?>_<?php echo $owner_id;?>').show();
                  if (!edit_note_id) {
                      $('#note_<?php echo $owner_table;?>_<?php echo $owner_id;?> tbody').prepend(h);
                  } else {
                      $('#note_' + edit_note_id + '').replaceWith(h);
                  }
                  edit_note_changed = false;
                  ucm.form.close_modal();
              }
          });
      }

	</script>

<?php } ?>

<a name="t_note_<?php echo $owner_table; ?>_<?php echo $owner_id; ?>"></a>
