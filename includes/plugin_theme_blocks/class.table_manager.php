<?php

/**
 * Class ucm_blocks_table_manager
 *
 * This controls the table data layout on pages within the UCM system.
 * If you want to change this file please copy it to a new folder: custom/includes/plugin_theme_blocks/class.table_manager.php
 * this way it wont get overwritten during system updates.
 */
class ucm_blocks_table_manager extends ucm_table_manager {

	public $table_class = 'table tableclass_rows table-hover table-condensed datatable adm-table dataTable no-footer';

	public function print_table() {
		$this->process_data();
		$colspan = 0;
		if ( ! $this->inline_table ) {
			?>
			<div class="box <?php echo module_theme::get_config( 'blocks_boxstyle', 'box-solid' ); ?>">
			<?php
			/*if($this->pagination){
			?>
			<div class="box-header clearfix">
					<div class="row"><div class="col-xs-6">

					</div><div class="col-xs-6 text-right">
					<?php  echo $this->rows['summary']; ?>
					</div></div>
			</div>
			<?php
			}*/
			?>
			<div class="box-body table-responsive <?php echo module_theme::get_config( 'blocks_tablefullwidth', 1 ) ? 'no-padding' : ''; ?>">
		<?php } ?>
		<table class="<?php echo $this->table_class; ?><?php
		echo module_theme::get_config( 'blocks_tablestripe', '1' ) ? ' table-striped' : '';
		echo module_theme::get_config( 'blocks_tableborder', '0' ) ? ' table-bordered' : '';
		?>"<?php echo $this->table_id ? ' id="' . $this->table_id . '"' : ''; ?>>
			<thead>
			<tr class="title">
				<?php foreach ( $this->columns as $column_id => $column_data ) {
					$title = is_array( $column_data ) ? $column_data['title'] : $column_data;
					$colspan ++;
					?>
					<th id="<?php echo $column_id; ?>"><?php echo _l( $title ); ?></th>
				<?php }
				if ( class_exists( 'module_extra', false ) && count( $this->extra_fields ) ) {
					foreach ( $this->extra_fields as $extra_field ) {
						$colspan += module_extra::print_table_header( $extra_field['type'] );
					}
				}
				if ( class_exists( 'module_subscription', false ) && count( $this->subscription_fields ) ) {
					foreach ( $this->subscription_fields as $extra_field ) {
						module_subscription::print_table_header( $extra_field['type'] );
						$colspan ++;
					}
				}
				?>
			</tr>
			</thead>
			<?php if ( count( $this->header_rows ) ) { ?>
				<thead class="summary">
				<?php
				foreach ( $this->header_rows as $row ) {
					$this->print_footer_row( $row );
				}
				?>
				</thead>
			<?php } ?>
			<tbody>
			<?php
			if ( ! count( $this->rows['rows'] ) ) {
				if ( $this->blank_message ) {
					?>
					<tr>
						<td colspan="<?php echo $colspan; ?>" class="blank_message"
						    style="text-align: center"><?php _e( $this->blank_message ); ?></td>
					</tr>
					<?php
				}
			} else {
				foreach ( $this->rows['rows'] as $row ) {
					$this->print_row( $row );
				}
			}
			?>
			</tbody>
			<?php if ( count( $this->footer_rows ) ) { ?>
				<tfoot class="summary">
				<?php
				foreach ( $this->footer_rows as $row ) {
					$this->print_footer_row( $row );
				}
				?>
				</tfoot>
			<?php } ?>
		</table>
		<?php if ( ! $this->inline_table ){ ?>
		</div>
		<?php
	}
		if ( $this->pagination && ( ! $this->pagination_hide_single_page || ( $this->pagination_hide_single_page && $this->rows['page_numbers'] > 1 ) ) ) {
			?>
			<div class="box-footer clearfix">
				<div class="row">
					<div class="col-xs-6">
						<?php
						// regex and change the default pagination html output to something nicer, default is:
						/* <div class="pagination_links"><span>			    « Prev |
						</span>					<span><a href="/ucm/demo/customer.customer_admin_list/?leads=1&amp;pgtable=0#t_table" rel="0" class="current">1</a></span>
																				<span><a href="/ucm/demo/customer.customer_admin_list/?leads=1&amp;pgtable=1#t_table" rel="1" class="">2</a></span>
																				<span><a href="/ucm/demo/customer.customer_admin_list/?leads=1&amp;pgtable=2#t_table" rel="2" class="">3</a></span>
																				<span><a href="/ucm/demo/customer.customer_admin_list/?leads=1&amp;pgtable=3#t_table" rel="3" class="">4</a></span>
																		| <span><a href="/ucm/demo/customer.customer_admin_list/?leads=1&amp;pgtable=1#t_table" rel="1">Next »</a><span>
						</span></span></div> */
						$this->rows['links'] = str_replace( '<div class="pagination_links">', '<div class="pagination_links paging_bootstrap pagination-sm no-margin"><ul class="pagination pagination-sm">', $this->rows['links'] );
						$this->rows['links'] = str_replace( '</div>', '</ul></div>', $this->rows['links'] );
						//$this->rows['links'] = str_replace('pagination_links','pagination_links paging_bootstrap',$this->rows['links']);
						$this->rows['links'] = str_replace( '|', '', $this->rows['links'] );
						$this->rows['links'] = str_replace( '...', '<li class="disabled"><a href="#">...</a></li>', $this->rows['links'] );
						if ( preg_match_all( '#<span>(.*)</span>#imsU', $this->rows['links'], $matches ) ) {
							foreach ( $matches[0] as $key => $val ) {
								$li_class = strpos( $val, 'current' ) ? 'active' : '';
								if ( strpos( $matches[1][ $key ], '<a' ) === false ) {
									$li_class           = 'disabled';
									$matches[1][ $key ] = '<a href="#">' . $matches[1][ $key ] . '</a>';
								}
								$this->rows['links'] = str_replace( $val, '<li class="' . $li_class . '">' . $matches[1][ $key ] . '</li>', $this->rows['links'] );
							}
						}
						echo $this->rows['links'];
						?>
					</div>
					<div class="col-xs-6 text-right">
						<?php echo $this->rows['summary']; ?>
					</div>
				</div>
			</div>
			<?php
		}
		if ( ! $this->inline_table ) {
			?>
			</div>
			<?php
		}
	}


}