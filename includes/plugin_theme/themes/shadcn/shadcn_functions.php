<?php

// Core's two-column layout helpers emit a layout <table>. Use the Bootstrap grid instead so the
// columns stack on narrow screens (the same approach the Metis theme takes).
hook_remove( 'layout_column_half', 'module_theme::hook_handle_layout_column' );
hook_remove( 'layout_column_thirds', 'module_theme::hook_handle_layout_column' );
hook_add( 'layout_column_half', 'shadcn_layout_column' );
hook_add( 'layout_column_thirds', 'shadcn_layout_column' );

function shadcn_layout_column( $column_type, $column_option = '', $column_width = false ) {

	if ( ! empty( $_POST['modal'] ) ) {
		return;
	}
	switch ( $column_type ) {
		case 'layout_column_half':
			switch ( $column_option ) {
				case 1:
					$column_width = $column_width ? floor( 12 / ( 100 / $column_width ) ) : 6;
					echo '<div class="row"><div class="col-md-' . $column_width . '">';
					break;
				case 2:
					$column_width = $column_width ? ceil( 12 / ( 100 / $column_width ) ) : 6;
					echo '</div><div class="col-md-' . $column_width . '">';
					break;
				case 'end':
					echo '</div></div>';
					break;
			}
			break;
		case 'layout_column_thirds':
			$column_width = $column_width ? round( 12 / ( 100 / $column_width ) ) : 4;
			switch ( $column_option ) {
				case 'start':
					echo '<div class="row">';
					break;
				case 'col_start':
					echo '<div class="col-md-' . $column_width . '">';
					break;
				case 'col_end':
				case 'end':
					echo '</div>';
					break;
			}
			break;
	}
}
