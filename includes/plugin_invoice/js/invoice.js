ucm.invoice = {
    init: function(){
         this.update_invoice_tax();
        var c = function(e){
            var chk = $(e.target);
            if(chk.hasClass('payment_method_online')){
                $('.payment_type_online').show();
                $('.payment_type_offline').hide();
            }else{
                $('#payment_type_offline_info').html($('#text_'+chk.attr('id')).val());
                $('.payment_type_offline').show();
                $('.payment_type_online').hide();
            }
        };
        c({target:$('.payment_method:checked')[0]});
        $('.payment_method').change(c).mouseup(c);
    },
    update_invoice_tax: function(){
        if($('#invoice_tax_holder .dynamic_block').length > 1)$('.invoice_tax_increment').show(); else $('.invoice_tax_increment').hide();
    }
};