ucm.subscription = {
    init: function(){
        $('.next_due_date_change').click(function(){
            $(this).after('<input type="text" name="subscription_next_due_date_change['+$(this).data('id')+']" value="'+$(this).text()+'" class="date_field">');
            $(this).hide();
            ucm.load_calendars();
        });
    }
};

jQuery(function(){
    ucm.subscription.init();
});