<?php

// find all task LOGS completed within these dayes
if($end_date == $start_date){
    $end_date = date('Y-m-d',strtotime('+1 day',strtotime($end_date)));
}
$sql = "SELECT tl.date_created, t.amount, tl.hours AS hours_logged, p.hourly_rate ";
$sql .= ", t.description AS task_name ";
$sql .= ", p.job_id ";
$sql .= ", p.website_id ";
$sql .= ", t.task_id ";
$sql .= ", t.hours ";
$sql .= " FROM `"._DB_PREFIX."task` t";
$sql .= " LEFT JOIN `"._DB_PREFIX."task_log` tl ON t.task_id = tl.task_id ";
//$sql .= " FROM `"._DB_PREFIX."task_log` tl ";
//$sql .= " LEFT JOIN `"._DB_PREFIX."task` t ON tl.task_id = t.task_id ";
$sql .= " LEFT JOIN `"._DB_PREFIX."job` p ON t.job_id = p.job_id";
//$sql .= " WHERE tl.date_created >= '$start_date' AND tl.date_created < '$end_date'";
$sql .= " WHERE ( (tl.date_created >= '$start_date' AND tl.date_created < '$end_date') OR (t.fully_completed = 1 AND t.date_done >= '$start_date' AND t.date_done < '$end_date') )";
$sql .= " AND t.job_id IN ( ";
    $valid_job_ids = module_job::get_valid_job_ids();
    if(count($valid_job_ids)){
        foreach($valid_job_ids as $valid_job_id){
            $sql .= (int)$valid_job_id['job_id'].", ";
        }
        $sql = rtrim($sql,', ');
    }else{
        $sql .= ' NULL ';
    }
$sql .= " ) ";
// order by ?
$res = qa($sql);
$title = _l('Hours Logged');

ob_start();
$total = 0;
?>

<table class="tableclass tableclass_rows tableclass_full">
    <thead>
    <tr>
        <th><?php _e('Date');?></th>
        <th><?php _e(module_config::c('project_name_single','Website'));?></th>
        <th><?php _e('Job');?></th>
        <th><?php _e('Task');?></th>
        <th><?php _e('Hours');?></th>
        <th><?php _e('Task %%');?></th>
        <th><?php _e('Amount');?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach($res as $r){

        $tasks = module_job::get_tasks($r['job_id']);
        $task = $tasks[$r['task_id']];
        if(!$task)continue;
        if(!isset($task['manual_task_type'])||$task['manual_task_type']<0){
            $task['manual_task_type'] = $task['default_task_type'];
        }

        $foo = $r['task_name'];
        if(strlen($foo)>20){
            $foo = substr($foo,0,20) . '...';
        }
        // we have to find if the user has logged more or less or exactly the same amount of hours
        // for this task.
        // eg: they could log 2 hours against a 1 hour task
        // eg: they could log 0.1 hours against a 1 hour task
        // eg: they could log 1 hours against a 1 hour task.
        if(isset($r['hours_logged']) && $r['hours_logged'] > 0){
            if($r['hours_logged'] == $task['completed']){
                // this listing is the only logged hours for this task.
                if($task['fully_completed']){
                    // task complete, we show the final amount and hours.
                    if($task['amount']>0){
                        if($task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT){
                            $display_amount = $task['amount'] * $task['hours'];
                        }else{
                            $display_amount = $task['amount'];
                        }
                    }else{
                        $display_amount = $r['hours'] * $r['hourly_rate'];
                    }
                }else{
                    // task isn't fully completed yet, just use hourly rate for now.
                    $display_amount = $r['hours_logged'] * $r['hourly_rate'];
                }
            }else{
                // this is part of a bigger log of hours for this single task.
                $display_amount = $r['hours_logged'] * $r['hourly_rate'];
            }
            $hours_logged = (!empty($r['task_hours']) && $r['task_hours'] > 0 ? $r['hours_logged'] : 0);
        }else{
            if($task['amount']>0){
                if($task['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT){
                    $display_amount = $task['amount'] * $task['hours'];
                }else{
                    $display_amount = $task['amount'];
                }
            }else if(isset($r['task_hours'])){
                $display_amount = $r['task_hours'] * $r['hourly_rate'];
            }else{
                $display_amount = 0;
            }
            $hours_logged = $task['hours'];
        }
        /*
        $calc_hours = 0;
        $hourly_rate = $r['hourly_rate'];
        if($r['hours_logged'] <= $r['hours']){
            // good! we're logging less or euql to the planned hours
            $calc_hours = $r['hours_logged'];
        }else{
            // logging more than our planned hours.
            // todo: calculate total logged hours and work out some magic to display a better listing
            // eg: if we log 1 hour yesterday and 1 hour today we don't want to show todays overworked hour
            // as adding to the total income $.
            $calc_hours = $r['hours'];
        }
        $display_amount = $hourly_rate * $calc_hours;
        if($r['amount']>0 && $r['amount'] != $r['hours'] * $r['hourly_rate']){
            $display_amount = $r['amount'];
        }
        */
        $total+= $display_amount;
        ?>
        <tr>
            <td>
                <?php echo print_date($r['date_created']); ?>
            </td>
            <td>
                <?php
                if(isset($r['website_id']) && $r['website_id'] > 0){
                    echo module_website::link_open($r['website_id'],true);
                }
                ?>
            </td>
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
            <td>
                <?php
                echo $hours_logged;
                if($hours_logged != $r['hours']){
                    _e(' of %s',$r['hours']);
                }
                echo _l('hrs'); ?>
            </td>
            <td>
                <?php
                $percentage = module_job::get_percentage($task);
                echo ($percentage*100).'%';
                ?>
            </td>
            <td>
                <?php

                echo dollar($display_amount);
                ?>
            </td>
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
