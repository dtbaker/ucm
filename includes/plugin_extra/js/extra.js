function extra_process_url(){
    var u = $(this).val().match(/https?:\/\/.*$/);
    if(u){
        $(this).parent().find('.extra_link_click').remove();
        $(this).after(' <a href="'+u+'" target="_blank" class="extra_link_click">open &raquo;</a>');
    }else{

    }
}
function extra_show_fields(e){
    e.preventDefault();
    $('.extra_fields_show_more').hide();
    $('.extra_field_row_hidden').show();
    return false;
}
$(function(){
    $(document).on('click','.extra_fields_show_button',extra_show_fields);
    $(document).on('change','.extra_value_input',extra_process_url);
    $('.extra_value_input').each(extra_process_url);
});