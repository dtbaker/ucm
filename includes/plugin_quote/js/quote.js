ucm.quote = {

    ajax_task_url: '',
    create_invoice_popup_url: '',
    create_invoice_url: '',

    // init called from the quote edit page
    init: function(){
        var t = this;
        t.update_quote_tax();
    },
    toggle_task_complete: function(task_id){

    },
    update_quote_tax: function(){
        if($('#quote_tax_holder .dynamic_block').length > 1)$('.quote_tax_increment').show(); else $('.quote_tax_increment').hide();
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
    }
};

$(function(){
    ucm.quote.init();
});