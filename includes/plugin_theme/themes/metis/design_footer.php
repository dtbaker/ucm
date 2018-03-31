<?php
switch ( $display_mode ) {
	case 'iframe':
		?>
		</div> <!-- end .inner -->
		</div> <!-- end .outer -->
		</div> <!-- end .content -->
		</body>
		</html>
		<?php
		module_debug::push_to_parent();
		break;
	case 'ajax':

		break;
	case 'normal':
	default:


		if ( function_exists( 'hook_handle_callback' ) ) {
			hook_handle_callback( 'content_footer' );
		}
		?>

		</div> <!-- end .inner -->
		</div> <!-- end .outer -->
		</div> <!-- end .content -->

		</div>
		<!-- /#wrap -->


		<div id="footer">
			<p>&copy; <?php echo module_config::s( 'admin_system_name', 'Ultimate Client Manager' ); ?>
				- <?php echo date( "Y" ); ?>
				- Version: <?php echo module_config::current_version(); ?>
				- Time: <?php echo round( microtime( true ) - $start_time, 5 ); ?>
			</p>
		</div>

		</body>
		</html>
		<?php
		break;
}