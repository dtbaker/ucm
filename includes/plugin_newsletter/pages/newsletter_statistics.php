<?php

$statistics = true;
include( 'newsletter_queue_watch.php' );

return;

/*

$module->page_title = _l('Statistics');
//print_heading('Newsletter Editor');

$newsletter_id = isset($_REQUEST['newsletter_id']) ? (int)$_REQUEST['newsletter_id'] : false;
if(!$newsletter_id){
    set_error('Sorry no newsletter id specified');
    redirect_browser(module_newsletter::link_list(0));
}
$newsletter = module_newsletter::get_newsletter($newsletter_id);
// great a new blank send table ready to go (only if user clicks confirm)
$send_id = isset($_REQUEST['send_id']) ? (int)$_REQUEST['send_id'] : false;
if(!$send_id){
    set_error('Sorry no newsletter send id specified');
    redirect_browser(module_newsletter::link_open($newsletter_id));
}
$send = module_newsletter::get_send($send_id);
if($send['status']!=_NEWSLETTER_STATUS_SENT){
    // hasnt sent yet, redirect to the pending watch page.
    redirect_browser(module_newsletter::link_queue_watch($newsletter_id,$send_id));
}
$start_time = $send['start_time'];


print_heading('Newsletter Statistics');
?>

<table width="100%" class="tableclass tableclass_full">
        <tbody>
        <tr>
            <td width="70%" valign="top">
                <?php print_heading(array(
                            'type'=>'h3',
                            'title'=>'Recipient Status ',
                )); ?>
                <table class="tableclass tableclass_rows tableclass_full">
                    <thead>
                    <tr>
                        <th><?php _e('Company Name');?></th>
                        <th><?php _e('Name');?></th>
                        <th><?php _e('Email');?></th>
                        <th><?php _e('Sent');?></th>
                        <th><?php _e('Opened');?></th>
                        <th><?php _e('Bounced');?></th>
                        <th>&nbsp;</th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php
                        // list all members from thsi send.
                        $send_members = module_newsletter::get_send_members($send_id,true);
                        $open_count=0;
                        $x=0;
                        $send_members_count = mysqli_num_rows($send_members);
                        $sent_to_members = 0;
                        while($send_member = mysqli_fetch_assoc($send_members)){
                            if($send_member['open_time']){
                                $open_count++;
                            }
                            ?>
                            <tr class="<?php echo $x++%2?'odd':'even';?>" id="newsletter_member_<?php echo $send_member['newsletter_member_id'];?>">
                                <td><?php echo htmlspecialchars($send_member['company_name']);?></td>
                                <td><?php echo htmlspecialchars($send_member['first_name'] . ' ' . $send_member['last_name']);?></td>
                                <td><?php echo htmlspecialchars($send_member['email']);?></td>
                                <td class="sent_time"><?php echo $send_member['sent_time'] ? print_date($send_member['sent_time'],true) : _l('Not Yet');?></td>
                                <td><?php echo $send_member['open_time'] ? print_date($send_member['open_time'],true) : _l('Not Yet');?></td>
                                <td><?php echo $send_member['bounce_time'] ? print_date($send_member['bounce_time'],true) : _l('No');?></td>
                                <td class="status">
                                    <?php
                                    switch($send_member['status']){
                                        case _NEWSLETTER_STATUS_NEW:
                                            // hasnt been processed yet
                                            break;
                                        case _NEWSLETTER_STATUS_SENT;
                                            // sent!
                                            _e('sent');
                                            break;
                                        case _NEWSLETTER_STATUS_PENDING;
                                        case _NEWSLETTER_STATUS_PAUSED;
                                            // pending send..
                                            _e('pending');
                                            break;
                                        case _NEWSLETTER_STATUS_FAILED:
                                            _e('failed');
                                            break;
                                        default:
                                            echo '?';
                                    }?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                <?php
                // show any probmeatic ones here (blocked due to bounce limit reached, or unsubscribed, or wont receive again due to previous receive.
                ?>
            </td>
            <td width="50%" valign="top">
                <?php print_heading(array(
                            'type'=>'h3',
                            'title'=>'Send Statistics',
                )); ?>
                <table class="tableclass tableclass_form tableclass_full">
                    <tbody>
                    <tr>
                        <th class="width1"><?php _e('Started sending');?></th>
                        <td>
                            <?php echo print_date($start_time,true); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Finished sending');?></th>
                        <td>
                            <?php echo print_date($send['finish_time'],true); ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Sent to');?></th>
                        <td>
                            <span id="send_count">0</span> <?php echo _l('of') . ' ' . $send_members_count;?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Open rate');?></th>
                        <td>
                            <?php echo _l('%s people opened (%s%%)',$open_count,round(($open_count/$send_members_count)*100));?>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>
*/