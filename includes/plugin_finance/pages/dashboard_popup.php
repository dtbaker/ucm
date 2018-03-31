<?php

// this is shown in a lightbox, editing a particular payment by payment id
$type = $_REQUEST['w'];
$date = explode('|',$_REQUEST['date']);
$start_date = input_date($date[0]);
if(isset($date[1])){
	$end_date = input_date($date[1]);
	$end_date_str = ' to '.print_date($end_date);
}else{
	$end_date = $start_date;
	$end_date_str = '';
}

switch($type){
    case 'amount_spent':
        // pass this off to it's own file because it's getting a bit messy in here.
        include('dashboard_popup_amount_spent.php');
        return false;
    case 'amount_paid':
        // pass this off to it's own file because it's getting a bit messy in here.
        include('dashboard_popup_amount_paid.php');
        return false;
        // find all payments made this week.
        /*$sql = "SELECT pay.*, i.job_id, p.website_id ";
        $sql .= " FROM `"._DB_PREFIX."invoice_payment` pay ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."invoice` i ON pay.invoice_id = i.invoice_id ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."job` p ON i.job_id = p.job_id ";
        $sql .= " WHERE (pay.date_paid >= '$start_date' AND pay.date_paid <= '$end_date')";
        $res = qa($sql);
        $title = _l('Amount Paid');*/
        break;
    case 'hours':
        // pass this off to it's own file because it's getting a bit messy in here.
        include('dashboard_popup_hours_logged.php');
        return false;
    case 'amount_invoiced':
        if($end_date == $start_date){
            $end_date = date('Y-m-d',strtotime('+1 day',strtotime($end_date)));
        }
        // find invoices sent this week.
        $sql = "SELECT i.*, p.website_id ";
        $sql .= " FROM `"._DB_PREFIX."invoice` i ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."invoice_item` ii ON i.invoice_id = ii.invoice_id ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."task` t ON ii.task_id = t.task_id ";
        $sql .= " LEFT JOIN `"._DB_PREFIX."job` p ON t.job_id = p.job_id ";
        $sql .= " WHERE (i.date_create >= '$start_date' AND i.date_create < '$end_date')";
        $sql .= " GROUP BY i.invoice_id";
        $res = qa($sql);
        $title = _l('Amount Invoiced');
        break;
    default:
        echo 'popup error';
        exit;
}


$total = 0;
ob_start();
?>

<table class="tableclass tableclass_rows tableclass_full">
    <thead>
        <tr>
            <?php
            switch($type){
                case 'hours': ?>
                <th><?php _e('Date');?></th>
                <?php
                break;
                case 'amount_paid':
                case 'amount_invoiced': ?>
                <th><?php _e('Date');?></th>
                <?php
                break;
            }
            ?>
            <th><?php _e(module_config::c('project_name_single','Website'));?></th>
            <?php
            switch($type){
                case 'hours': ?>
                <th><?php _e('Job');?></th>
                <th><?php _e('Task');?></th>
                <?php
                break;
                default: ?>
                <th><?php _e('Job');?></th>
                <?php
                break;
            }
            ?>
            <?php
            switch($type){
                case 'hours': ?>
                <th><?php _e('Hours');?></th>
                <th><?php _e('Amount');?></th>
                <?php
                break;
                case 'amount_paid':
                case 'amount_invoiced': ?>
                <th><?php _e('Invoice');?></th>
                <th><?php _e('Amount');?></th>
                <?php
                break;
            }
            ?>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach($res as $r){
            $invoice_data = array();
            if(isset($r['invoice_id']) && $r['invoice_id'] > 0){
                $invoice_data = module_invoice::get_invoice($r['invoice_id']);
            }
            ?>
            <tr>
                <?php
                switch($type){
                    case 'hours': ?>
                    <td>
                        <?php echo print_date($r['date_created']); ?>
                    </td>
                    <?php
                    break;
                    case 'amount_paid':?>
                    <td>
                        <?php echo print_date($r['date_paid']); ?>
                    </td>
                    <?php
                    break;
                    case 'amount_invoiced': ?>
                    <td>
                        <?php echo print_date($r['date_create']); ?>
                    </td>
                    <?php
                    break;
                }
                ?>
                <td>
                    <?php
                    if(isset($r['website_id']) && $r['website_id'] > 0){
                        echo module_website::link_open($r['website_id'],true);
                    }
                    ?>
                </td>
                <?php
                switch($type){
                    case 'hours':
                        $foo = $r['task_name'];
                        if(strlen($foo)>20){
                            $foo = substr($foo,0,20) . '...';
                        }
                        ?>
                        <td>
                            <?php if(isset($r['job_id']) && $r['job_id']){
                                echo module_job::link_open($r['job_id'],true);
                                }else if(isset($r['job_ids']) && is_array($r['job_ids'])){
                                foreach($r['job_ids'] as $job_id){
                                    echo module_job::link_open($job_id,true);
                                }
                            }?>
                        </td>
                        <td>
                            <?php if(isset($r['job_id']) && $r['job_id']){
                                ?>  <a href="<?php echo module_job::link_open($r['job_id']);?>"><?php echo $foo; ?></a> <?php
                            }else if(isset($r['job_ids']) && is_array($r['job_ids'])){
                                foreach($r['job_ids'] as $job_id){
                                    ?>  <a href="<?php echo module_job::link_open($job_id);?>"><?php echo $foo; ?></a> <?php
                                }
                            }?>

                        </td>
                    <?php
                    break;
                    default: ?>
                        <td>
                            <?php if(isset($invoice_data['job_id']) && $invoice_data['job_id']){
                                echo module_job::link_open($invoice_data['job_id'],true);
                            }else if(isset($invoice_data['job_ids']) && is_array($invoice_data['job_ids'])){
                                foreach($invoice_data['job_ids'] as $job_id){
                                    echo module_job::link_open($job_id,true);
                                }
                            }?>
                        </td>
                    <?php
                    break;
                }
                ?>
                <?php
                switch($type){
                    case 'hours': ?>
                        <td>
                            <?php
                            echo $r['hours_logged'];
                            ?>
                            <?php echo _l('hrs'); ?>
                        </td>
                        <td>
                            <?php // amount
                            $hours_logged = ($r['task_hours'] > 0 ? $r['hours_logged'] : 0);
                            $hourly_rate = $r['hourly_rate'];
                            if($hours_logged > 0 && $r['amount'] > 0 && $hourly_rate > 0){
                                // there is a custom amount assigned to thsi task.
                                // only calculate this amount if the full hours is complete.
                                $hourly_rate = $r['amount'] / $r['task_hours'];
                            }
                            if($hours_logged > 0 && $hourly_rate > 0){
                                $total += ($hours_logged * $hourly_rate);
                                echo dollar($hours_logged * $hourly_rate);
                            }
                            ?>
                        </td>
                        <?php
                        break;
                    case 'amount_paid':
                        ?>
                        <td>
                            <?php
                            echo module_invoice::link_open($r['invoice_id'],true);
                            ?>
                        </td>
                        <td>
                            <?php
                            $total += $r['amount'];
                            echo dollar($r['amount']);
                            ?>
                        </td>
                        <?php
                        break;
                    case 'amount_invoiced': ?>
                        <td>
                            <?php
                            echo module_invoice::link_open($r['invoice_id'],true);
                            ?>
                        </td>
                        <td>
                            <?php
                            $total += $invoice_data['total_amount'];
                            echo dollar($invoice_data['total_amount']);
                            ?>
                        </td>
                        <?php
                        break;
                }
                ?>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8" align="right">
                <?php _e('Total:'); ?>
                <span style="font-weight: bold;"><?php echo dollar($total); ?></span>
            </td>
        </tr>
    </tfoot>
</table>

<?php


$fieldset_data = array(
	'id' => 'dashboard_popup',
	'heading' => array(
		'type' => 'h3',
		'title' => "$title for " . print_date($start_date) . $end_date_str,
	),
	'class' => 'tableclass tableclass_rows tableclass_full',
	'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset($fieldset_data);
unset($fieldset_data);
