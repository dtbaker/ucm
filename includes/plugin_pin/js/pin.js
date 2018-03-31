$(function(){
    $('#top_menu_pin').hover(function(){
        $('#top_menu_pin_options').show();
    },function(){
        $('#top_menu_pin_options').hide();
    });
    $('#pin_current_page').click(function(){
        $('#pin_action').val('add');
        $('#pin_current_title').val(document.title);
        $('#pin_action_form')[0].submit();
        return false;
    });
    $('.top_menu_pin_delete').click(function(){
        $('#pin_action').val('delete');
        $('#pin_id').val($(this).parent().parent().attr('rel'));
        $('#pin_action_form')[0].submit();
        return false;
    });
    $('.top_menu_pin_edit').click(function(){
        var newtitle = prompt('New title:',$(this).parent().parent().find('.top_menu_pin_item').text());
        if(newtitle){
            $('#pin_action').val('modify');
            $('#pin_id').val($(this).parent().parent().attr('rel'));
            $('#pin_current_title').val(newtitle);
            $('#pin_action_form')[0].submit();
        }
        return false;
    });
});