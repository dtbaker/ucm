var ucm = ucm || {};
ucm.customer = {
    settings: {
        ajax_url: '',
        loading: 'Loading...',
        choose: ' - Choose - '
    },
    init: function(){
        $(".dynamic_customer_selection").each(function(){
            var $t = $(this);
            var $cid = $t.find('.change_customer_id_input');
            var old_customer_id = $cid.val();
            $t.find('.choose_new_customer').click(function(){
                $t.addClass('selecting');
            });
            $t.find('.dynamic_choose_customer_type').change(function(){
                // ajax call to find list of available customer types.
                var customer_type_id = $(this).val();
                $t.find('.choose_customer_select').html(ucm.customer.settings.loading);
                if(customer_type_id == '')return;
                if(!ucm.customer.settings.ajax_url){
                    alert('Failed to find customer ajax url. Please report this issue.');
                    return;
                }
                $.ajax({
                    type: 'POST',
                    url: ucm.customer.settings.ajax_url,
                    data: {
                        '_process': 'ajax_customer_list',
                        'customer_id': $cid.val(),
                        form_auth_key: ucm.form_auth_key,
                        'search': {
                            'customer_type_id': customer_type_id
                        }
                    },
                    dataType: 'json',
                    success: function(newOptions){
                        var $newSelect = $('<select></select>');
                        $newSelect.append($("<option></option>")
                            .attr("value", '').text(ucm.customer.settings.choose));
                        $.each(newOptions, function(value, key) {
                            $newSelect.append($("<option></option>")
                                .attr("value", value).text(key));
                        });
                        $t.find('.choose_customer_select').html('');
                        $newSelect.appendTo($t.find('.choose_customer_select'));
                        $newSelect.change(function(){
                            $cid.val($(this).val());
                            $( "body" ).trigger( "customer_id_changed", {
                                changer:$t,
                                old_customer_id:old_customer_id,
                                customer_id:$(this).val()
                            });
                        });
                    },
                    fail: function(){
                        alert('Changing customer failed, please refresh and try again.');
                    }
                });
            });
        });
    }
};
$(function(){
    ucm.customer.init();
});
