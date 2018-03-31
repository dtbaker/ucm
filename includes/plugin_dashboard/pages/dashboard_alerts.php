<script type="text/javascript">
	<?php if(module_config::c( 'dashboard_alerts_as_tabs', 1 )){ ?>
  $(function () {
      $('#dashboard_tabs').tabs({
          collapsible: true
      });
      $('#dashboard_tabs').tabs("option", "active", Get_Cookie("dashboard_tab"));
      $("#dashboard_tabs").click(function () {
          Set_Cookie("dashboard_tab", $("#dashboard_tabs").tabs('option', 'active'));
      });
  });
	<?php } ?>
  function hide_item(key) {
      $('#hide_item').val(key);
      $('#hide_item_form')[0].submit();
  }
</script>
<form action="" method="post" id="hide_item_form">
	<input type="hidden" name="hide_item" value="" id="hide_item">
</form>
<div id="dashboard_tabs">
	<?php if ( module_config::c( 'dashboard_alerts_as_tabs', 1 ) ) { ?>
		<ul>
			<?php
			$x = 1;
			foreach ( $dashboard_alerts as $key => $val ) { ?>
				<li><a href="#tabs-<?php echo $x; ?>"><?php echo $key; ?> (<?php echo count( $val ); ?>)</a></li>
				<?php
				$x ++;
			} ?>
		</ul>
		<?php
	}
	$x = 1;
	foreach ( $dashboard_alerts as $key => $alerts ) {
		if ( ! module_config::c( 'dashboard_alerts_as_tabs', 1 ) ) { ?>
			<h3><?php echo $key; ?> (<?php echo count( $alerts ); ?>)</h3>
		<?php } ?>
		<div id="tabs-<?php echo $x; ?>">
			<?php
			// is this a new registered dashboard alert group?
			if ( isset( module_dashboard::$group_settings[ $key ] ) ) {
				?>
				<table class="tableclass tableclass_rows tableclass_full"
				       id="alert_table_<?php echo strtolower( str_replace( ' ', '', $key ) ); ?>">
					<thead>
					<?php foreach ( module_dashboard::$group_settings[ $key ]['columns'] as $column_key => $column_title ) { ?>
						<th class="alert_column_<?php echo $column_key; ?>"><?php echo $column_title; ?></th>
					<?php } ?>
					<th width="10" class="alert_column_delete"></th>
					</thead>
					<tbody>
					<?php
					if ( count( $alerts ) ) {
						$y = 0;
						foreach ( $alerts as $alert ) {
							?>
							<tr class="<?php echo ( $y ++ % 2 ) ? 'even' : 'odd'; ?>">
								<?php foreach ( module_dashboard::$group_settings[ $key ]['columns'] as $column_key => $column_title ) { ?>
									<td><?php echo isset( $alert[ $column_key ] ) ? $alert[ $column_key ] : ''; ?></td>
								<?php } ?>
								<?php if ( isset( $alert['hide_key'] ) && $alert['hide_key'] ) { ?>
									<td width="10">
										<a href="#" class="" onclick="return hide_item('<?php echo $alert['hide_key']; ?>');"><i
												class="fa fa-trash"></i></a>
									</td>
								<?php } ?>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td class="odd" colspan="4"><?php _e( 'Yay! No alerts!' ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
				<?php
			} else {
				// old method of output for unregistered alerts:
				// will remove once all have been converted.
				?>
				<table class="tableclass tableclass_rows tableclass_full tbl_fixed"
				       id="alert_table_<?php echo strtolower( str_replace( ' ', '', $key ) ); ?>">
					<tbody>
					<?php
					if ( count( $alerts ) ) {
						$y = 0;
						foreach ( $alerts as $alert ) {
							?>
							<tr class="<?php echo ( $y ++ % 2 ) ? 'even' : 'odd'; ?>">
								<td class="row_action">
									<a href="<?php echo $alert['link']; ?>"><?php echo htmlspecialchars( $alert['item'] ); ?></a>
								</td>
								<td>
									<?php echo isset( $alert['name'] ) ? htmlspecialchars( $alert['name'] ) : ''; ?>
								</td>
								<td width="16%">
									<?php echo ( $alert['warning'] ) ? '<span class="important">' : ''; ?>
									<?php echo $alert['days']; ?>
									<?php echo ( $alert['warning'] ) ? '</span>' : ''; ?>
								</td>
								<td width="16%">
									<?php echo ( $alert['warning'] ) ? '<span class="important">' : ''; ?>
									<?php echo print_date( $alert['date'] ); ?>
									<?php echo ( $alert['warning'] ) ? '</span>' : ''; ?>
								</td>
								<?php if ( isset( $alert['hide_key'] ) && $alert['hide_key'] ) { ?>
									<td width="10">
										<a href="#" class="" onclick="return hide_item('<?php echo $alert['hide_key']; ?>');"><i
												class="fa fa-trash"></i></a>
									</td>
								<?php } ?>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td class="odd" colspan="4"><?php _e( 'Yay! No alerts!' ); ?></td>
						</tr>
					<?php } ?>
					</tbody>
				</table>
			<?php } // end old method 
			?>
		</div>
		<?php
		$x ++;
	} ?>
</div>