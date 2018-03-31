<?php
/*
?>
<html xmlns="http://www.w3.org/1999/html">
<head>
    <title>Change Request</title>
    <link rel="stylesheet" href="<?php echo full_link('/includes/plugin_change_request/css/change_request.css');?>">
</head>
<body>
<?php */ ?>
	<div id="change_request_popup">
		<?php if ( $change_history[1] > 0 ) { ?>
			<div class="change_request_remain">
				<strong><?php echo max( 0, $change_history[1] - $change_history[0] ); ?></strong>
				of <?php echo $change_history[1]; ?> <?php _e( 'changes remaining' ); ?>
			</div>
		<?php } ?>
		<h1><?php _e( 'Website Change Request' ); ?></h1>
		<?php if ( $step >= 1 ) { ?>
			<ol id="change_request_steps">
				<li class="<?php echo $step == 1 ? 'current' : ''; ?>"><span><?php _e( 'Step' ); ?> <span>1</span></span></li>
				<li class="<?php echo $step == 2 ? 'current' : ''; ?>"><span><?php _e( 'Step' ); ?> <span>2</span></span></li>
				<li class="<?php echo $step == 3 ? 'current' : ''; ?>" style="margin:0"><span><?php _e( 'Step' ); ?>
						<span>3</span></span></li>
			</ol>
		<?php } ?>
		<?php
		include( 'popup_content_' . $step . '.php' );
		?>
	</div>
<?php /*
</body>
</html> */ ?>