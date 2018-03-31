ucm.social.facebook = {
    api_url: '',
    init: function(){

        $('body').delegate('.facebook_reply_button','click',function(){
            var f = $(this).parents('.facebook_comment').first().next('.facebook_comment_replies').find('.facebook_comment_reply_box');
            f.show();
            f.find('textarea')[0].focus();
        }).delegate('.facebook_comment_reply textarea','keyup',function(){
            var a = this;
            if (!$(a).prop('scrollTop')) {
                do {
                    var b = $(a).prop('scrollHeight');
                    var h = $(a).height();
                    $(a).height(h - 5);
                }
                while (b && (b != $(a).prop('scrollHeight')));
            }
            $(a).height($(a).prop('scrollHeight') + 10);
        }).delegate('.facebook_comment_reply button','click',function(){
            // send a message!
            var p = $(this).parent();
            var txt = $(p).find('textarea');
            var message = txt.val();
            if(message.length > 0){
                //txt[0].disabled = true;
                // show a loading message in place of the box..
                $.ajax({
                    url: ucm.social.facebook.api_url,
                    type: 'POST',
                    data: {
                        action: 'send-message-reply',
                        id: $(this).data('id'),
                        facebook_id: $(this).data('facebook-id'),
                        message: message,
                        debug: $(p).find('.reply-debug')[0].checked ? 1 : 0,
                        form_auth_key: ucm.form_auth_key
                    },
                    dataType: 'json',
                    success: function(r){
                        if(r && typeof r.redirect != 'undefined'){
                            window.location = r.redirect;
                        }else if(r && typeof r.message != 'undefined'){
                            p.html("Info: "+ r.message);
                        }else{
                            p.html("Unknown error, please try reconnecting to Facebook in settings. "+r);
                        }
                    }
                });
                p.html('Sending...');
            }
            return false;
        }).delegate('.socialfacebook_message_action','click',ucm.social.facebook.message_action);
        $('.facebook_message_summary a').click(function(){
            var p = $(this).parents('tr').first().find('.socialfacebook_message_open').click();
            return false;
        });
        /*$('.pagination_links a').click(function(){
            $(this).parents('.ui-tabs-panel').first().load($(this).attr('href'));
            return false;
        });*/
    },
    message_action: function(link){
        $.ajax({
            url: ucm.social.facebook.api_url,
            type: 'POST',
            data: {
                action: $(this).data('action'),
                id: $(this).data('id'),
                form_auth_key: ucm.form_auth_key
            },
            dataType: 'script',
            success: function(r){
                ucm.social.close_modal();
            }
        });
        return false;
    }
};