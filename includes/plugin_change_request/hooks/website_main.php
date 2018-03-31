<?php

if(!count($change_requests))return;
$jobs = module_job::get_jobs(array('website_id'=>$website_id));
// pull out jobs that don't have an invoice.
foreach($jobs as $job_id=>$job){
    $invoices = module_invoice::get_invoices(array('job_id'=>$job['job_id']));
    if(count($invoices)){
        unset($jobs[$job_id]);
    }
}
$h = array(
    'type'=>'h3',
    'title'=>'Customer Change Requests',
);
// find out how many changes are incomplete
$link_toggle = module_website::link_open($website_id);
$show_completed = isset($_REQUEST['show_completed_change_requests']) ? $_REQUEST['show_completed_change_requests'] : false;
$num_completed = 0;
foreach($change_requests as $change_request){
    if($change_request['status']==_CHANGE_REQUEST_STATUS_COMPLETE){
        $num_completed++;
    }
}
if($num_completed){
    if($show_completed){
        $h['button']=array(
            'title'=>_l('Hide %s completed changes',$num_completed),
            'url' =>$link_toggle.='&show_completed_change_requests=0',
        );
    }else{
        $h['button']=array(
            'title'=>_l('Show %s completed changes',$num_completed),
            'url' =>$link_toggle.='&show_completed_change_requests=1',
        );
    }
}
//print_heading($h);

ob_start();

?>


<input type="hidden" name="add_change_request_to_website" id="add_change_request_to_website" value="0">
<input type="hidden" name="add_change_request_to_website_job_id" id="add_change_request_to_website_job_id" value="0">
<input type="hidden" name="add_change_request_to_website_hours" id="add_change_request_to_website_hours" value="0">

    <?php /* <script type="text/javascript">
        function add_change_to_job(job_id,description){
            $.ajax({
                method: 'POST',
                url: '<?php echo module_job::link_open(false);?>',
                dataType: 'html',
                data: {
                    '_process': 'save_job_tasks_ajax',
                    'job_id': job_id,
                    'job_task[new][task_id]': 'new',
                    'job_task[new][description]': description,
                    'job_task[new][hours]': '<?php echo module_config::c('change_request_job_hours',1);?>'
                },
                success: function(res){
                    alert(res);
                }
            });
        }
    </script> */ ?>

    <table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows tableclass_full">
        <thead>
        <tr>
            <th><?php _e('URL');?></th>
            <th><?php _e('Date/Time');?></th>
            <th><?php _e('Text');?></th>
            <th><?php _e('Status');?></th>
            <th><?php _e('Linked Job');?></th>
            <th><?php _e('Action');?></th>
        </tr>
        </thead>
        <tbody>
    <?php
    $c=0;
    foreach($change_requests as $change_request){
        if(!$show_completed && $change_request['status']==_CHANGE_REQUEST_STATUS_COMPLETE)continue;
        ?>
        <tr class="<?php echo $c++%2?'odd':'even';?>">
            <td>
                <?php echo htmlspecialchars($change_request['url']);?>
            </td>
            <td><?php echo print_date($change_request['date_created']);?></td>
            <td>
                <?php if(strlen($change_request['request'])>40){ ?>
                    <a href="#" onclick="$(this).next().show();$(this).hide();return false;"><?php echo substr($change_request['request'],0,40);?>....</a>
                    <div style="display:none;"><?php echo forum_text($change_request['request']);?></div>
                <?php }else{ ?>
                    <?php echo forum_text($change_request['request']);?>
                <?php } ?>
            </td>
            <td>
                <?php switch($change_request['status']){
                case _CHANGE_REQUEST_STATUS_NEW: _e('Incomplete'); break;
                case _CHANGE_REQUEST_STATUS_COMPLETE: _e('Completed'); break;
                } ?>
            </td>
            <td>
                <?php if($change_request['job_id']){
                    // check if this task still existing in this job.
                    // if not we do a quick hack to remove it.
                    $tasks = module_job::get_tasks($job_id);
                    if(!$change_request['task_id'] || !isset($tasks[$change_request['task_id']])){
                        $change_request['job_id'] = 0;
                        $change_request['task_id'] = 0;
                        update_insert('change_request_id',$change_request['change_request_id'],'change_request',array('job_id'=>0,'task_id'=>0));
                    }
                }
                if($change_request['job_id']){
                    $job_data = module_job::get_job($change_request['job_id']);
                    echo module_job::link_open($change_request['job_id'],true,$job_data);
                    echo ' ';
                    $task = $tasks[$change_request['task_id']];
                    _e('%s hrs = %s',$task['hours'],currency($task['amount']>0?$task['amount']:($task['hours']*$job_data['hourly_rate'])),true,$job_data['currency_id']);
                }else if(module_job::can_i('edit','Job Tasks')){
                    if(count($jobs)){
                        echo print_select_box($jobs,'change_request_job_id','','',_l('select a job'),'name');
                        ?>
                        @
                        <input type="text" name="add_job_hours" value="<?php echo module_config::c('change_request_job_hours',1);?>" class="add_job_hours" style="width:15px;"><?php _e('hrs');?>
                        <input type="button" name="add_job" class="form_save" value="<?php _e('Add');?>" onclick="$('#add_change_request_to_website_job_id').val($(this).parent().find('select').first().val());$('#add_change_request_to_website_hours').val($(this).prev('.add_job_hours').val()); $('#add_change_request_to_website').val(<?php echo $change_request['change_request_id'];?>); this.form.submit(); ">
                    <?php
                    }else{
                        _h('Please create an empty job first');
                    }
                }else{
                    _e('N/A');
                }
                ?>
            </td>
            <td>
                <a href="<?php echo module_change_request::link_public_change($website_id,$change_request['change_request_id']);?>" target="_blank"><?php _e('View');?></a>
                |
                <a href="<?php echo module_change_request::link_open($change_request['change_request_id']);?>"><?php _e('Email');?></a>
                <?php if(module_change_request::can_i('delete','Change Requests') && !$change_request['job_id']){ ?>
                |
                <a href="<?php echo module_change_request::link_open_delete($change_request['change_request_id']);?>"><?php _e('Delete');?></a>
                <?php } ?>

                <?php if($change_request['status']==1){ ?>
                <?php }else if($change_request['status']==2){
                    // popup asking them what job to add this change request to.
                    ?>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
<?php

$fieldset_data = array(
    'heading' => $h,
    'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset($fieldset_data);