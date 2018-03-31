ucm.social.twitter = {
    api_url: '',
    init: function(){

        $('body').delegate('.twitter_reply_button','click',function(){
            var f = $(this).parents('.twitter_comment').first().next('.twitter_comment_replies').find('.twitter_comment_reply_box');
            f.show();
            f.find('textarea')[0].focus();
        }).delegate('.twitter_comment_reply textarea','keyup',function(){
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
        }).delegate('.twitter_comment_reply button','click',function(){
            // send a message!
            var p = $(this).parent();
            var txt = $(p).find('textarea');
            if(txt.length > 0){
            var message = txt.val();
            if(message.length > 0){
                //txt[0].disabled = true;
                // show a loading message in place of the box..
                $.ajax({
                    url: ucm.social.twitter.api_url,
                    type: 'POST',
                    data: {
                        action: 'send-message-reply',
                        social_twitter_message_id: $(this).data('id'),
                        social_twitter_id: $(this).data('account-id'),
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
                            p.html("Unknown error, please try reconnecting to Twitter in settings. "+r);
                        }
                    }
                });
                    p.html('Sending... Please wait...');
            }
            }
            return false;
        }).delegate('.socialtwitter_message_action','click',
            ucm.social.twitter.message_action
        ).delegate('.twitter_comment_clickable','click',function(){
            ucm.social.open_modal($(this).data('link'), $(this).data('title'));
        });
        $('.twitter_message_summary a').click(function(){
            var p = $(this).parents('tr').first().find('.socialtwitter_message_open').click();
            return false;
        });
        /*$('.pagination_links a').click(function(){
            $(this).parents('.ui-tabs-panel').first().load($(this).attr('href'));
            return false;
        });*/


        jQuery('.twitter_compose_message').change(this.twitter_txt_change).keyup(this.twitter_txt_change).change();
        this.twitter_change_post_type();
        jQuery('[name=post_type]').change(this.twitter_change_post_type);

    },
    message_action: function(link){
        $.ajax({
            url: ucm.social.twitter.api_url,
            type: 'POST',
            data: {
                action: $(this).data('action'),
                social_twitter_message_id: jQuery(this).data('id'),
                social_twitter_id: $(this).data('social_twitter_id'),
                form_auth_key: ucm.form_auth_key
            },
            dataType: 'script',
            success: function(r){
                ucm.social.close_modal();
            }
        });
        return false;
    },
    twitter_limit: 140,
    twitter_set_limit: function(limit){
        ucm.social.twitter.twitter_limit = limit;
        jQuery('.twitter_compose_message').change();
    },
    charactersleft: function(tweet, limit) {
        var url, i, lenUrlArr;
        var virtualTweet = tweet;
        var filler = "0123456789012345678912";
        var extractedUrls = twttr.txt.extractUrlsWithIndices(tweet);
        var remaining = limit;
        lenUrlArr = extractedUrls.length;
        if ( lenUrlArr > 0 ) {
            for (i = 0; i < lenUrlArr; i++) {
                url = extractedUrls[i].url;
                virtualTweet = virtualTweet.replace(url,filler);
            }
        }
        remaining = remaining - virtualTweet.length;
        return remaining;
    },
    twitter_txt_change: function(){
        var remaining = ucm.social.twitter.charactersleft(jQuery(this).val(), ucm.social.twitter.twitter_limit);
        jQuery(this).parent().find('.twitter_characters_remain').first().find('span').text(remaining);
    },
    twitter_change_post_type: function(){
        var currenttype = jQuery('[name=post_type]:checked').val();
        jQuery('.twitter-type-option').each(function(){
            jQuery(this).parents('tr').first().hide();
        });
        jQuery('.twitter-type-'+currenttype).each(function(){
            jQuery(this).parents('tr').first().show();
        });
        if(currenttype == 'picture'){
            ucm.social.twitter.twitter_set_limit(118);
        }else{
            ucm.social.twitter.twitter_set_limit(140);
        }
    }
};