<?php

if ( module_security::is_logged_in() ) {
	switch ( $display_mode ) {
		case 'iframe':
			?>
			</div> <!-- /#iframe -->
			</div> <!-- /#inner-content -->

			</body>
			</html>
			<?php
			module_debug::push_to_parent();
			break;
		case 'ajax':

			break;
		case 'normal':
		default:
			/*
			<div id="footer">
				<p>&copy; <?php echo module_config::s('admin_system_name','Ultimate Client Manager'); ?>
				  - <?php echo date("Y"); ?>
				  - <?php _e('Version:');?> <?php echo module_config::current_version(); ?>
				  - <?php _e('Time:');?> <?php echo round(microtime(true)-$start_time,5);?>
				</p>
			</div>
	*/
			if ( function_exists( 'hook_handle_callback' ) ) {
				hook_handle_callback( 'content_footer' );
			}
			?>


			</div> <!-- /#content -->
			</section><!-- /.content -->
			</aside><!-- /.right-side -->
			</div><!-- ./wrapper -->


			</body>
			</html>
			<?php
			break;
	}
} else {
	?>
	</body></html>
	<?php
}