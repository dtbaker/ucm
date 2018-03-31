<?php /* <div class="wp3changerequest_message_box">
        <p><strong><?php _e('Hello, welcome to the change request wizard.');?></strong> <br/>
        <?php _e('This is a simple 3 step process that will allow you to request a website change from your web developer.');?>
        <?php if($change_history[1]>0){ ?>
            <?php _e('Your web developer has allocated you <strong>%d changes per month</strong>, you have already made %d changes in the past month.',$change_history[1],$change_history[0]); ?>
        <?php } ?></p>
    </div> */ ?>
<?php /*if($change_history[1] > 0 && $change_history[0] >= $change_history[1]){
            ?>
        <h2>1. Monthly limit reached</h2>
        <p>Sorry you have already used up your <strong><?php echo $change_history[1];?></strong> allocated monthly change requests.</p>
        <p>Please contact your web developer if you would like to increase this limit so you can request more changes.</p>
        <input type="button" name="wp3changerequest" class="wp3changerequest_button wp3changerequest_button_cancel" value="Close" onclick="dtbaker_changerequest.close_popup();">
        <?php
    }*/
if ( $change_history[1] == 0 || $change_history[0] < $change_history[1] ) {
	?>
	<div id="wp3changerequest_step1">
	<h2><?php _e( '2. Choose what you would like to change' ); ?></h2>
	<p><?php _e( 'Please click the button below, a red circle will appear. ' ); ?><br/>
		<?php _e( 'Move the red circle to the item you wish to change and click.' ); ?></p>
	<input type="button" name="wp3changerequest_gogo"
	       class="wp3changerequest_gogo wp3changerequest_button wp3changerequest_button_arrow"
	       value="<?php _e( 'Choose what to change' ); ?>">
	<input type="button" name="wp3changerequest_back"
	       class="wp3changerequest_back wp3changerequest_button wp3changerequest_button_cancel"
	       value="<?php _e( 'Cancel' ); ?>">
	<p><em><?php _e( "Don't worry if you make a mistake, you can cancel or change this at any time." ); ?></em></p>
	<?php /*if(_DEMO_MODE){ ?>
    <p><em><strong>DEMO MODE NOTICE:</strong> <br/>
        Information is re-set every few hours. IP Addresses (<?php echo $_SERVER['REMOTE_ADDR'];?>) are logged.</em><br/>
    <em>The monthly change limit of <?php echo $change_history[1];?> is not applied in demo mode. <a href="#" onclick="jQuery('#demotoggle').slideToggle(); jQuery('#wp3changerequest_step1').slideUp(); return false;">Click here</a> to see what normally happens.</em></p>
        </div>
    <?php }*/ ?>
<?php } ?>