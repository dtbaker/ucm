

ucm.ticket = {
    ticket_message_text_is_html: false,
    ticket_url: '',
    init_main: function(){
        // for the main page listing
        $('#bulk_operation_all').change(function(){
            $('.ticket_bulk_check').prop('checked', $(this).is(":checked") );
        });
    },
    init: function(){
        $("#ticket_container").attr({ scrollTop: $("#ticket_container").attr("scrollHeight") });
        //$("#ticket_container").animate({ scrollTop: $("#ticket_container").attr("scrollHeight") },1500);
        $('#show_previous_button').click(function(){
            $('#show_previous_box').html('Loading...');
            $.post( ucm.ticket.ticket_url, {show_only_hidden: 1}, function( data ) {
                $('#show_previous_box').html('');
              $( "#show_previous_box" ).after( data );
            });
            return false;
        });
        $('#save_saved').click(function () {
            $.ajax({
                url: ucm.ticket.ticket_url,
                type: 'POST',
                data: '_process=save_saved_response&saved_response_id=' + $('#canned_response_id').val() + '&value=' + escape($('#new_ticket_message').val()),
                dataType: 'json',
                success: function (r) {
                    alert('Saved successfully');
                }
            });
        });
        $('#insert_saved').click(function () {
            $.ajax({
                url: ucm.ticket.ticket_url,
                data: '_process=insert_saved_response&saved_response_id=' + $('#canned_response_id').val(),
                dataType: 'json',
                success: function (r) {
                    ucm.ticket.add_to_message(r.value);
                }
            });
        });
        $('#private_message').change(function(){
            if(this.checked){
                $(this).parents('.ticket_message').first().addClass('ticket_message_private');
                $('#change_status_id').val(5);
            }else{
                $(this).parents('.ticket_message').first().removeClass('ticket_message_private');
                $('#change_status_id').val($('#data_change_status_id').data('status'));
            }
        }).change();
        $('#change_to_me').click(function(){
            $(this).parent().hide();
            $('#change_assigned_user_id').val($(this).data('user-id'));
            return false;
        });
    },
    add_to_message: function(content){
        if(ucm.ticket.ticket_message_text_is_html) {
            content = content.replace(/\n/g, "<br/>\n");
            tinyMCE.activeEditor.execCommand('mceInsertContent', false, content);
        }else {
            $('#new_ticket_message').val(
                $('#new_ticket_message').val() + content
            );
        }
        return false;
    }
};

$(function(){
    ucm.ticket.init_main();

});