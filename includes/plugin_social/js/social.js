ucm.social = {

    modal_url: '',
    init: function(){
        var p = $('#social_modal_popup');
        p.dialog({
            autoOpen: false,
            width: 700,
            height: 600,
            modal: true,
            buttons: {
                Close: function() {
                    $(this).dialog('close');
                }
            },
            open: function(){
                var t = this;
                $.ajax({
                    type: "GET",
                    url: ucm.social.modal_url+(ucm.social.modal_url.match(/\?/) ? '&' : '?')+'display_mode=ajax',
                    dataType: "html",
                    success: function(d){
                        $('.modal_inner',t).html(d);
                        $('input[name=_redirect]',t).val(window.location.href);
                        init_interface();
                        $('.modal_inner iframe.autosize',t).height($('.modal_inner',t).height()-41); // for firefox
                    }
                });
            },
            close: function() {
                $('.modal_inner',this).html('');
            }
        });
        $('body').delegate('.social_modal','click',function(){
            ucm.social.open_modal($(this).attr('href'), $(this).data('modal-title'));
            return false;
        });
    },
    close_modal: function(){
        var p = $('#social_modal_popup');
        p.dialog('close');
    },
    open_modal: function(url, title){
        var p = $('#social_modal_popup');
        p.dialog('close');
        ucm.social.modal_url = url;
        p.dialog('option', 'title', title);
        p.dialog('open');
    }

};