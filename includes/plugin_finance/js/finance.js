ucm.finance = {
    init: function(){
        var t = this;

        // options for editing the tax of a finance item.

        // if the user is changing the sub amount total amount manually:
        $('#finance_sub_amount').on('change',function(){
            if(!t.changing_in_progress)t.changing='total';
            t.update_finance_total();
        }).on('keyup',function(){
            if(!t.changing_in_progress)t.changing='total';
            t.update_finance_total();
        });
        // if the user is changing the sub taxable total amount manually:
        $('#finance_taxable_amount').on('change',function(){
            if(!t.changing_in_progress){
                t.changing='total';
                var this_value = number_in($(this).val());
                var finance_sub_amount = number_in($('#finance_sub_amount').val());
                if(this_value > finance_sub_amount){
                    // dont let them pick a higher taxable amount than sub amount
                    $('#finance_sub_amount').val(number_out(this_value));
                }
            }
            t.update_finance_total();
        }).on('keyup',function(){
            if(!t.changing_in_progress){
                t.changing='total';
                var this_value = number_in($(this).val());
                var finance_sub_amount = number_in($('#finance_sub_amount').val());
                if(this_value > finance_sub_amount){
                    // dont let them pick a higher taxable amount than sub amount
                    $('#finance_sub_amount').val(number_out(this_value));
                }
            }
            t.update_finance_total();
        });
        // if the user is changing the total amount manually:
        $('#finance_total_amount').on('change',function(){
            if(!t.changing_in_progress)t.changing='subtotal';
            t.update_finance_total();
        }).on('keyup',function(){
            if(!t.changing_in_progress)t.changing='subtotal';
            t.update_finance_total();
        });

        $('#finance_tax_holder').on('change','.tax_percent',function(){
            t.update_finance_total();
        }).on('keyup','.tax_percent',function(){
            t.update_finance_total();
        });
        $('#tax_increment_checkbox').on('change',function(){
            t.update_finance_total();
        });
        t.update_finance_total();
    },
    // we are either updating the 'total' or we are updating the 'sub total'
    // depending on which one was 'changed' last.
    changing: 'total',
    changing_in_progress: false,
    update_finance_total: function(){
        var t = this;
        if($('#finance_tax_holder .dynamic_block').length > 1)$('.finance_tax_increment').show(); else $('.finance_tax_increment').hide();
        t.changing_in_progress = true;
        var sub_amount = number_in($('#finance_sub_amount').val());
        var taxable_amount = number_in($('#finance_taxable_amount').val());
        var original_taxable_amount = taxable_amount;
        var amount = number_in($('#finance_total_amount').val());
        if(
            (t.changing == 'total' && (!isNaN(taxable_amount) || taxable_amount>0)) ||
            (t.changing == 'subtotal' && (!isNaN(amount) || amount>0))
        ){
            var incremental = $('#tax_increment_checkbox')[0].checked;
            var tax_amount = parseFloat(0);
            var tax_percents = parseFloat(0);
            var madness = function(){
                var tax = number_in($(this).find('.tax_percent').val());
                if(!isNaN(tax) && tax>0){
                    if(incremental){
                        // incrementing tax along the way. to amount
                        if(t.changing == 'total'){
                            // user wants the 'total' to be updated based on the current 'subtotal' amount
                            var this_tax = (taxable_amount * (tax/100));
                            var this_tax_display = Math.round(this_tax*1000)/1000;
                            $(this).find('.tax_amount').html(number_out(this_tax_display));
                            $(this).find('.tax_amount_input').val(number_out(this_tax_display));
                            taxable_amount += this_tax; //(taxable_amount * (tax/100));
                        }else{
                            // user wants the 'subtotal' to be updated based on the current 'total' amount
                            var new_amount = amount / (1 + (tax / 100));
                            var this_tax = amount-new_amount;
                            var this_tax_display = Math.round(this_tax*1000)/1000;
                            $(this).find('.tax_amount').html(number_out(this_tax_display));
                            $(this).find('.tax_amount_input').val(number_out(this_tax_display));

                            amount = new_amount;
                        }
                    }else{
                        if(t.changing == 'total'){
                            // user wants the 'total' to be updated based on the current 'subtotal' amount
                            var this_tax = (taxable_amount * (tax/100));
                            var this_tax_display = Math.round(this_tax*1000)/1000;
                            $(this).find('.tax_amount').html(number_out(this_tax_display));
                            $(this).find('.tax_amount_input').val(number_out(this_tax_display));
                            tax_amount += this_tax; //(taxable_amount * (tax/100));
                        }else{
                            // todo - this doesn't work.
                            var this_tax = 0;
                            var this_tax_display = Math.round(this_tax*1000)/1000;
                            $(this).find('.tax_amount').html(number_out(this_tax_display));
                            $(this).find('.tax_amount_input').val(number_out(this_tax_display));

                            tax_percents += (tax/100);
                        }
                    }
                }
            };

            if(t.changing == 'total'){
                // user wants the 'total' to be updated based on the current 'subtotal' amount
                $('#finance_tax_holder .dynamic_block').each(madness);
                $('#finance_total_amount').val( number_out( Math.round((sub_amount + (taxable_amount-original_taxable_amount) + tax_amount)*100)/100 ) );
                // update the sub total if these were the same before.
            }else{
                // user wants the 'subtotal' to be updated based on the current 'total' amount
                $($('#finance_tax_holder .dynamic_block').get().reverse()).each(madness);
                $('#finance_taxable_amount').val( number_out( Math.round((amount / (1 + (tax_percents)))*100)/100 ) );
                $('#finance_sub_amount').val( number_out( Math.round((amount / (1 + (tax_percents)))*100)/100 ));
            }
        }
        t.changing_in_progress = false;
    }
};