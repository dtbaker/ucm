$(function(){
    $('body').on('hide.bs.modal', '.modal', function () {
        $(this).removeData('bs.modal');
        $(this).remove();
        $('.task_job_discussion_holder').hide();
    });

    $('body').delegate('.task_job_discussion','click',function(){
        var holder = $(this).parents('td:first').find('.task_job_discussion_holder');
        holder.load($(this).attr('href'),function(){
            if(holder.is(':visible')){
        }else{
            $('.modal-backdrop').remove();
                holder.find('.modal').modal('show').on('shown.bs.modal', function () {
                    holder.find('textarea').focus();
                });

            }
            holder.toggle();
        });
        return false;
    });

    $('body').delegate('.task_job_discussion_add_adminlte','click',function(){
        $(this).addClass('disabled');
            var job_id = $(this).data('jobid');
            var task_id = $(this).data('taskid');
            var txt = $('textarea#comment_'+job_id+'_'+task_id);
        txt.addClass('disabled');

        var holder = $(this).parents('.task_job_discussion_holder').first();
        var sendemail_customer = [];
        var sendemail_staff = [];
        if(typeof holder.find('.sendemail_customer')[0] != 'undefined'){
                holder.find('.sendemail_customer').each(function(){
                        if($(this)[0].checked){
                                sendemail_customer.push($(this).val());
                        }
                });
        }
        if(typeof holder.find('.sendemail_staff')[0] != 'undefined'){
                holder.find('.sendemail_staff').each(function(){
                        if($(this)[0].checked){
                                sendemail_staff.push($(this).val());
                        }
                });
        }
        var myComment = txt.val();
        if(myComment == undefined || myComment == "")
        {
            txt.parent().addClass('has-error');
            txt.removeClass('disabled');
            return false;
        }
        else
        {
            txt.parent().removeClass('has-error').addClass('has-success');
        }
        $.ajax({
                type: 'POST',
                url: window.location.href,
                data: {
                    'note': myComment,
                    'job_discussion_add_job_id': job_id,
                    'job_discussion_add_task_id': task_id,
                    'sendemail_customer': sendemail_customer,
                    'sendemail_staff': sendemail_staff
                },
                dataType: 'json',
                success: function(h){
                    $('.task_job_discussion_holder').hide();
                    $('#job_discussion_'+job_id+'_'+task_id).modal('hide');
                    var btn = $('#discuss'+task_id);
                    if(btn.length>0)
                    {
                        btn.find('span').html(h.count);
                        btn.click();
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