
if(typeof jQuery=='undefined') {
    alert('Please add jQuery to this website');
}else{
// if this script is loading them we know the user wants to request changes on their website.

// we have jquery loaded.

var dtbaker_changerequest = {
    active: true,
    popup_url : '<?php echo module_change_request::link_popup($website_id);?>',
    change_id : 0,
    x : 0,
    y : 0,
    selecting: false,
    init_done : false,
    min_height : '60px',
    min_width: '80px',
    max_width : '250px',
    show_postits: true,
    options_minheight: '20px',
    options_maxheight: '110px',
    init: function(){
        var t = this;
        if(t.init_done)return;

        // add the fancybox js/css etc..
        dtbaker_public_change_request.inject_css('<?php echo full_link("/includes/plugin_change_request/css/change_request.css");?>');
        if(typeof jQuery.fancybox == 'undefined'){
            dtbaker_public_change_request.inject_js('<?php echo full_link("/includes/plugin_change_request/fancybox/jquery.fancybox-1.3.4.js");?>',dtbaker_changerequest.fancybox_loaded);
            dtbaker_public_change_request.inject_css('<?php echo full_link("/includes/plugin_change_request/fancybox/jquery.fancybox-1.3.4.css");?>');
        }else{
            this.fancybox_loaded();
        }
        

        // use delegate in jQuery 1.9+
        var b=jQuery('body');
        b.delegate('.wp3changerequest_cancel','click',function(){
            t.active = false;
            t.close_popup();
            t.hide_options();
            jQuery('.wp3changerequest_change').hide();
            dtbaker_public_change_request.cancel();
        });
        b.delegate('.wp3changerequest_this','click',function(){
            //t.close_popup();
            t.step2();
        });
        b.delegate('.wp3changerequest_back','click',function(){
            jQuery('#wp3changerequest_helper').remove(); // incase it exists already.
            t.close_popup();
            //t.step1();
        });
        b.delegate('.wp3changerequest_another','click',function(){
            alert('<?php _e("Please navigate to the page you wish to change.");?>');
            t.close_popup();
        });
        b.delegate('.wp3changerequest_gogo','click',function(){
            // hide the modal popup, and attach a big arrow thingey showing the user what to do.
            t.element_selection_start();
        });
        b.delegate('#dtbaker_change_request_adjust','click',function(){
            t.step2();
        });
        t.init_done = true;
        // check cookie to see if we show or hide them?
        //if(dtbaker_getCookie('wp3changehide')==1)t.show_postits = false;
    },
    fancybox_loaded: function(){
        var t=dtbaker_changerequest;

        jQuery('body').append('<div id="wp3changerequest_options"><div id="wp3changerequest_options_header"><?php _e("Request a Change");?></div></div>');
        //jQuery('body').append('<a href="#" style="display:none;" id="wp3changerequest_open_link">open</a>');
        jQuery('body').append('<div id="dtbaker_changerequest_inlinewizard" style="display:none;"></div>');


        jQuery('#wp3changerequest_options').append('<div id="wp3changerequest_options_buttons"></div>');
        jQuery('#wp3changerequest_options_buttons').append('<input type="button" name="wp3changerequest_btn1" id="wp3changerequest_start" class="wp3changerequest_button wp3changerequest_button_arrow" value="<?php _e("Request a Change");?>">');
        jQuery('#wp3changerequest_options_buttons').append('<input type="button" name="wp3changerequest_btn1" id="wp3changerequest_end" class="wp3changerequest_cancel wp3changerequest_button wp3changerequest_button_cancel" value="<?php _e("Finish");?>">');
        jQuery('#wp3changerequest_options').append('<div id="wp3changerequest_options_help"><?php _e("Please click the button above to request a change on this website.");?></div>');
        jQuery('#wp3changerequest_options').height(t.options_minheight);
        jQuery('#wp3changerequest_start').click(function(){
            t.step1();
            return false;
        });
        jQuery('#wp3changerequest_options').hover(function(){
            jQuery(this).stop().animate({height:t.options_maxheight},200);
        },function(){
            jQuery(this).stop().animate({height:t.options_minheight},200);

        });
        t.show_options();
        //t.step1();
        //t.open_popup();
        /*jQuery(".wp3changerequest_open").parents('a').click(function(){
            t.change_id = 0;
            jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=1',function(){
                t.open_popup();
            });
            return false;
        });*/
        /*jQuery(".wp3changerequest_toggle").parents('a').click(function(){
            // toggle with a cookie.
            t.show_postits = !t.show_postits;
            if(t.show_postits){
                jQuery('.wp3changerequest_change').show();
                dtbaker_setCookie('wp3changehide',0,3);
            }else{
                jQuery('.wp3changerequest_change').hide();
                dtbaker_setCookie('wp3changehide',1,3);
            }
            return false;
        });*/
    },
    show_options: function(){
        jQuery('#wp3changerequest_options').show();
    },
    hide_options: function(){
        jQuery('#wp3changerequest_options').hide();
    },
    opened:false,
    open_popup: function(){
        var t = this;
        if(t.opened)return;
        t.opened=true;
        jQuery.fancybox({
            'zoomSpeedIn'		: 600,
            'zoomSpeedOut'		: 100,
            'autoDimensions'	: true,
            'href'              : '#dtbaker_changerequest_inlinewizard',
            'type'              : 'inline',
            //'content':'<div></div>',
            'easingIn'			: 'easeOutBack',
            'easingOut'			: 'easeInBack',
            'hideOnContentClick': false,
            'padding'			: 15,
            'onStart' : function(){
                jQuery('#dtbaker_changerequest_inlinewizard').show();
            },
            'onClosed' : function(){
                jQuery('#dtbaker_changerequest_inlinewizard').html('');
                jQuery('#dtbaker_changerequest_inlinewizard').hide();
                t.opened=false;
                if(t.active && !t.selecting){
                    jQuery('#wp3changerequest_helper').remove(); // incase it exists already.
                    t.show_options();
                }
            }
        });
        /*if(typeof(jQuery('#wp3changerequest_open_link')[0].click) != 'undefined'){
            jQuery('#wp3changerequest_open_link')[0].click();
        }else{
            jQuery('#wp3changerequest_open_link').click();
        }*/
    },
    close_popup: function(){
        this.opened = false;
        jQuery.fancybox.close();
    },
    element_selection_start: function(){
        var t = this;
        t.selecting = true;
        t.close_popup();
        jQuery('#wp3changerequest_helper').remove(); // incase it exists already.
        jQuery('body').prepend('<div id="wp3changerequest_helper" style="display:none;">&nbsp;</div>');
        t.mousetrack();
        t.mouseclick();
    },
    element_selection_end: function(x,y){
        var t = dtbaker_changerequest;
        //jQuery('#wp3changerequest_helper').remove();
        jQuery('body').unbind('click',t.mouseclick_action);
        jQuery('body').unbind('mousemove',t.mousetrack_action);
        /*if(typeof dtbaker_end_callback == 'function'){
            dtbaker_end_callback();
        }*/
        // open the lightbox again
        // change the lightbox content to this newly selected stuff
        t.x=x;
        t.y=y;
        t.selecting = false;
        t.step3(true);
    },
    edit: function(change_id){
        var t = dtbaker_changerequest;
        t.init();
        t.change_id = change_id;
        jQuery('#dtbaker_change_'+change_id).removeClass('wp3changerequest_change_active');// makes it sit behind fancybox
        t.step3();
    },
    /*step0: function(){
        var t = this;
        t.change_id = 0;
        jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=0',function(){
            t.open_popup();
        });
    },*/
    step1: function(){
        var t = this;
        t.hide_options();
        t.change_id = 0;
        dtbaker_public_change_request.inject_js(t.popup_url+'&type=popupjs&step=1',function(){
            t.open_popup();
            //jQuery.fancybox.resize();
        });
        /*jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=1',function(){
            t.open_popup();
            //jQuery.fancybox.resize();
        });*/
    },
    step2: function(){
        var t = this;
        dtbaker_public_change_request.inject_js(t.popup_url+'&type=popupjs&step=2&change_id='+t.change_id,function(){
            jQuery.fancybox.resize();
        });
        /*jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=2&change_id='+t.change_id,function(){
            //t.open_popup();
            jQuery.fancybox.resize();
        });*/
    },
    step3: function(setvals){
        var t = this;
        dtbaker_public_change_request.inject_js(t.popup_url+'&type=popupjs&step=3&change_id='+t.change_id,function(){
        //jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=3&change_id='+t.change_id,function(){
            set_add_del('dtbaker_change_request_attachments');
            // set the form varialbes.
            //jQuery('#change_request_submit_form').attr('action',t.popup_url+'&type=save');
            if(setvals){
                jQuery('#change_request_submit_form input[name="change_id"]').val(t.change_id);
                jQuery('#change_request_submit_form input[name="window_width"]').val(jQuery(window).width());
                jQuery('#change_request_submit_form input[name="url"]').val(escape(window.location.href));
                jQuery('#change_request_submit_form input[name="x"]').val(t.x);
                jQuery('#change_request_submit_form input[name="y"]').val(t.y);
            }
            /*append('<input type="hidden" name="change_id" value="'+t.change_id+'">');
            jQuery('#change_request_submit_form').append('<input type="hidden" name="x" value="'+t.x+'">');
            jQuery('#change_request_submit_form').append('<input type="hidden" name="y" value="'+t.y+'">');
            jQuery('#change_request_submit_form').append('<input type="hidden" name="window_width" value="'+jQuery(window).width()+'">');
            jQuery('#change_request_submit_form').append('<input type="hidden" name="url" value="'+escape(window.location.href)+'">');*/
            t.open_popup();
            //jQuery.fancybox.resize();
        });
        //if ( jQuery('#fancy_content:empty').length > 0){

        //}
    },
    mousetrack_action: function(e){
        //var pageCoords = "( " + e.pageX + ", " + e.pageY + " )";
        //var clientCoords = "( " + e.clientX + ", " + e.clientY + " )";
        //jQuery('#wp3changerequest_helper').text("( e.pageX, e.pageY ) - " + pageCoords + " ( e.clientX, e.clientY ) - " + clientCoords);
        jQuery('#wp3changerequest_helper').show();
        jQuery('#wp3changerequest_helper').css('top',e.pageY-75);
        jQuery('#wp3changerequest_helper').css('left',e.pageX-75);
    },
    mousetrack: function(){
        var t = this;
        jQuery("body").bind('mousemove',t.mousetrack_action);

    },
    mouseclick_action: function(e){
        var t = dtbaker_changerequest;
        // record the position of the event.
        dtbaker_changerequest.element_selection_end(e.pageX,e.pageY);
        return false;
    },
    mouseclick: function(){
        var t=this;
        jQuery('body').bind('click',t.mouseclick_action);
    },
    /*preview_change: function(change_id){
        var t = this;
        t.change_id = change_id;
        // the popup is still open, we just change the contents o f it.
        jQuery('#dtbaker_changerequest_inlinewizard').load(t.popup_url+'&type=popup&step=3&change_id='+change_id,function(){
            jQuery('#change_request_submit_form').attr('action',t.popup_url+'&type=publish&change_id='+change_id);
        });
    },*/
    remove_change: function(change_id){
        jQuery('#dtbaker_change_'+change_id).remove();
    },
    display_change: function(change_id){
        var t = this;
        t.remove_change(change_id);// remove any existing ones? ie: saving over top of old one.

        dtbaker_public_change_request.inject_js(t.popup_url+'&type=display_change&change_id='+change_id,function(){

        });
        /*jQuery.ajax({
            url: t.popup_url+'&type=display_change&change_id='+change_id,
            type: "GET",
            dataType: "json",
            success: function(msg){
                jQuery('body').prepend('<div class="wp3changerequest_change" id="dtbaker_change_'+change_id+'" style="'+((!t.show_postits) ? 'display:none;':'')+'"></div>');
                var box = jQuery('#dtbaker_change_'+change_id);
                box.html(msg.html);
                if(msg.status == 0){
                    box.addClass('wp3changerequest_change_pending');
                }else if(msg.status == 2){
                    box.addClass('wp3changerequest_change_complete');
                }else if(msg.status == 3){
                    box.addClass('wp3changerequest_change_deleted');
                }
                box.css('top',msg.y+'px');
                box.data('window_width',msg.window_width);
                box.data('left',msg.x);
                t.set_left(change_id);
                with({i:change_id}){
                    jQuery(window).resize(function () {
                        t.set_left(i);
                    });
                }
                box.data('original_height',box.height());
                box.css('overflow','hidden');
                jQuery('.title',box).slideUp();
                box.stop(true, true).animate({
                    height: t.min_height,
                    width: t.min_width
                },500);
                box.hover(function(){
                    jQuery(this).addClass('wp3changerequest_change_active');
                    jQuery('.title',this).stop(true, true).slideDown();
                    jQuery(this).stop().animate({
                        width: t.max_width,
                        height: jQuery(this).data('original_height'),
                        opacity: 1
                    },500);
                },function(){
                    jQuery('.title',this).stop(true, true).slideUp();
                    jQuery(this).stop().animate({
                        width: t.min_width,
                        height: t.min_height,
                        opacity: 0.7
                    },500,function(){
                        jQuery(this).removeClass('wp3changerequest_change_active');
                    });
                })

            }
        });*/
    },
    set_left: function(change_id){
        var window_diff = jQuery('#dtbaker_change_'+change_id).data('window_width');
        var left = jQuery('#dtbaker_change_'+change_id).data('left');
        // work out the actual width this time round.
        // it is msg.x from the left when window is msg.window_width wide.
        window_diff = window_diff - jQuery(window).width();
        // are we a centered design?
        window_diff = window_diff / 2;
        left = left - window_diff;
        jQuery('#dtbaker_change_'+change_id).css('left',left+'px');
    },
    highlight: function(change_id){
        var t = this;
        setTimeout(function(){
            var box = jQuery('#dtbaker_change_'+change_id);
            box.addClass('wp3changerequest_change_active');
            jQuery('.title',box).stop(true, true).slideDown();
            box.stop(true, true).animate({
                width: t.max_width,
                height: box.data('original_height'),
                opacity: 1
            },500, function(){
                //box.effect("highlight", {}, 3000);
            });
        },1000);
    }
};

jQuery(function(){
    // init the change request popup.
    dtbaker_changerequest.init();
});

function set_add_del(id){
    jQuery("#"+id+' .remove_addit').show();
    jQuery("#"+id+' .add_addit').hide();
    jQuery("#"+id+' .add_addit:last').show();
    if(jQuery("#"+id+" .dynamic_block").length==1){
        jQuery("#"+id+' .remove_addit').hide();
    }
}
function selrem(clickety,id){
    jQuery(clickety).parents('.dynamic_block').remove();
    set_add_del(id);
    return false;
}
function seladd(clickety,id){
    var box = jQuery('#'+id+' .dynamic_block:last').clone(true);
    jQuery('input',box).val('');
    jQuery('#'+id+' .dynamic_block:last').after(box);
    set_add_del(id);
    return false;
}
}