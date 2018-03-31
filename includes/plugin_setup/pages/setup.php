<div class="blob">
	<?php

	$step = ( isset( $_REQUEST['step'] ) ) ? (int) $_REQUEST['step'] : 0;

	//print_heading('Setup Wizard (step '.$step.' of 4)');?>


	<p>
		<?php echo _l( 'Hello, Welcome to the setup wizard. You are currently on step %s of 5.', $step ); ?>
	</p>

	<?php
	include( 'setup' . $step . '.php' );
	?>

</div>


