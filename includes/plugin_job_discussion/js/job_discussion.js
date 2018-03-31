$(function(){
    $('body').delegate('.task_job_discussion','click',function(){
        var holder = $(this).parents('td:first').find('.task_job_discussion_holder');
        holder.load($(this).attr('href'),function(){
            holder.toggle();
        });
        return false;
    });
    $('body').delegate('.task_job_discussion_add','click',function(){

        var job_id = $(this).data('jobid');
        var task_id = $(this).data('taskid');
        var holder = $(this).parents('td:first').find('.task_job_discussion_holder');
        var sendemail_customer = [];
        var sendemail_staff = [];
        if(typeof $(this).parent('div').find('.sendemail_customer')[0] != 'undefined'){
            $(this).parent('div').find('.sendemail_customer').each(function(){
                if($(this)[0].checked){
                    sendemail_customer.push($(this).val());
                }
            });
        }
        if(typeof $(this).parent('div').find('.sendemail_staff')[0] != 'undefined'){
            $(this).parent('div').find('.sendemail_staff').each(function(){
                if($(this)[0].checked){
                    sendemail_staff.push($(this).val());
                }
            });
        }
        $.ajax({
            type: 'POST',
            url: window.location.href, //$(this).attr('post_url'),
            data: {
                'note': $(this).parent('div').find('textarea').val(),
                'job_discussion_add_job_id': job_id,
                'job_discussion_add_task_id': task_id,
                'sendemail_customer': sendemail_customer,
                'sendemail_staff': sendemail_staff
            },
            dataType: 'json',
            success: function(h){
               var btn = $(holder).parents('td:first').find('.task_job_discussion');
                if(btn.length>0){
                    btn.click();
                    /*var count = parseInt(btn.html());
                    if(!count)count = 0;
                    count = count + 1;*/
                    btn.find('span').html(h.count);
                }
            },
            fail: function(){
                alert('Something went wrong, try again');
            }
        });
        holder.html('Loading...');
        return false;
    });
});