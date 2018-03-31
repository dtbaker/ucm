ucm = ucm || {};

ucm.data = {
    lang: {
        Save: 'Save',
        Cancel: 'Cancel'
    },
    settings: {
        url: '?m=data&p=admin_data&display_mode=iframe'
    },
    init: function(){
        var t = this;
        setTimeout(function(){
            $('.custom_data_embed_wrapper .tableclass_rows tr').each(function(){
                $(this).off('click');
            });
        },700);
        $('.custom_data_open').click(function(e){
            var btn = this;
            t.popup_init(btn);
            var url = $.param($(btn).data('settings'));
            e.preventDefault();
            $("#data_popup").dialog({
                autoOpen: true,
                height: 800,
                width: 800,
                modal: true,
                buttons: {
                    'Close': function() {
                        $(this).dialog('close');
                    }
                },
                open: function() {
                    $('#data_popup_inner iframe').attr('src',ucm.data.settings.url + '&' + url);
                }
            });
            return false;
        });

    },
    popup_init: function(btn){
        $('#data_popup').remove();
        var settings = $(btn).parents('.custom_data_embed_wrapper').first().data('settings');
        if(!settings)return;
        $('body').append('<div id="data_popup" title="' + (typeof settings.title != 'undefined' ? settings.title : '') + '"><div id="data_popup_inner" style="height: 100%; position: relative;"><iframe src="about:blank" style="width:100%; height:100%; border:0" frameborder="0"></iframe> </div></div>');
        return $.param(settings);
    },
    popup: function(btn){
        var url = this.popup_init(btn);
        $("#data_popup").dialog({
            autoOpen: true,
            height: 800,
            width: 800,
            modal: true,
            buttons: {
                'Close': function() {
                    $(this).dialog('close');
                }
            },
            open: function() {
                $('#data_popup_inner iframe').attr('src',ucm.data.settings.url + '&' + url);
            }
        });
        return false;
    },
    popup_new: function(btn){
        var url = this.popup_init(btn);
        $("#data_popup").dialog({
            autoOpen: true,
            height: 800,
            width: 800,
            modal: true,
            buttons: {
                'Close': function() {
                    $(this).dialog('close');
                }
            },
            open: function() {
                $('#data_popup_inner iframe').attr('src',ucm.data.settings.url + '&' + url + '&data_record_id=new');
            }
        });
        return false;
    }
};

$(function(){ucm.data.init();});