ucm.job = {

    ajax_task_url: '',
    create_invoice_popup_url: '',
    create_invoice_url: '',

    // init called from the job edit page
    init: function(){
        var t = this;
        $('body').delegate('.task_percentage_toggle','click',function(){
            var task_id = $(this).data('task-id');
            $.ajax({
                url: t.ajax_task_url,
                data: {
                    task_id:task_id,
                    toggle_completed: true
                },
                type: 'POST',
                dataType: 'json',
                success: function(r){
                    if(typeof r.message != 'undefined'){
                        ucm.add_message(r.message);
                        ucm.display_messages(true);
                    }
                    refresh_task_preview(task_id);
                }
            });
        }).delegate('.task_completed_checkbox','change',function(){
            $(this).parent().find('.task_email_auto_option').show();
        });
        $('#job_generate_invoice_button').click(function(){
            t.generate_invoice($(this).text());
            return false;
        });
        t.update_job_tax();
    },
    toggle_task_complete: function(task_id){

    },
    generate_invoice_done: false,
    generate_invoice: function(title){
        var t = this;

        $('#create_invoice_options_inner').load(t.create_invoice_popup_url,function(){
            $('#create_invoice_options').dialog({
                autoOpen: true,
                height: 560,
                width: 350,
                modal: true,
                title: title,
                buttons: {
                    Create: function() {
                        var url = t.create_invoice_url;
                        var items = $('.invoice_create_task:checked');
                        if(items.length>0){
                            items.each(function(){
                                url += '&task_id[]=' + $(this).data('taskid');
                            });
                            window.location.href=url;
                        }else{
                            $(this).dialog('close');
                        }
                    }
                }
            });
        });
    },

    update_job_tax: function(){
        if($('#job_tax_holder .dynamic_block').length > 1)$('.job_tax_increment').show(); else $('.job_tax_increment').hide();
    }
};


// this is called via the ajax callback from job_admin_task_edit.php:
function job_task_ajax_saved( ajax_response, $form, ajax_post_data ){
    if(ajax_response && ajax_response.data.task_id) {
        if(ajax_response.data.message){
            ucm.add_message(ajax_response.data.message);
            ucm.display_messages(true);
        }
        ucm.form.close_modal();
        refresh_task_preview(ajax_response.data.task_id, false);
    }
}