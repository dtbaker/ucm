var buttons = {};
buttons[ucm.form.lang.cancel] = function(){
    $(this).dialog('close');
};

function edit_items(){
    load_select_popup();
    $("#dynamic_select_popup").dialog({
        autoOpen: false,
        height: 600,
        width: 600,
        modal: true,
        buttons: buttons,
        open: function () {
            $.ajax({
                type: "POST",
                url: ucm.form.settings.dynamic_select_edit_url,
                data: current_options,
                dataType: "html",
                success: function (d) {
                    if ($('#dynamic_select_form', d).length < 1) {
                        alert('Failed to load data. Please report this error.');
                        //$(this).dialog('close');
                        return false;
                    }
                    $('#dynamic_select_popup_inner').html(d);
                    $('.edit_dynamic_select_option').click(function(e){

                        e.preventDefault();

                        edit_individual_item($(this).data('item'))

                        return false;

                    });

                }
            });
        }
    }).dialog('open');

}
function edit_individual_item(item_data){
    load_select_popup();

    $("#dynamic_select_popup").dialog({
        autoOpen: false,
        height: 600,
        width: 600,
        modal: true,
        buttons: buttons,
        open: function () {
            $.ajax({
                type: "POST",
                url: ucm.form.settings.dynamic_select_edit_url,
                data: item_data,
                dataType: "html",
                success: function (d) {
                    if ($('#dynamic_select_form', d).length < 1) {
                        alert('Failed to load data. Please report this error.');
                        //$(this).dialog('close');
                        return false;
                    }
                    $('#dynamic_select_popup_inner').html(d);


                }
            });
        }
    }).dialog('open');
}
function load_select_popup(){
    $('#dynamic_select_popup').remove();
    $('body').append('<div id="dynamic_select_popup" title="' + ucm.form.lang.dynamic_select_edit_title + '"><div id="dynamic_select_popup_inner"></div></div>');
}

edit_items();