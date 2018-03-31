ucm = ucm || {};
ucm.metis = {
    init: function(){

        // do the action button duplication.
        $('.action_bar_duplicate').each(function(){
            if(!$(this).hasClass('action_bar_single')){
                $(this).clone(true).addClass('hidden-xs').prependTo($(this).parents('form').first());
            }
        });
        // current selected icon to header area.
        if($('.head .main-bar h3 .fa').length == 0){
            //$('#menu li.active i.fa').each(function(){
            //    $(this).clone(true).addClass('cloned').prependTo('.head .main-bar h3');
            //});
            $('#menu li.active i.fa').first().clone(true).addClass('cloned').prependTo('.head .main-bar h3');
        }
        $('.submit_button').each(function(){
            if(!$(this).hasClass('btn')){
                $(this).addClass('btn');
            }
        });



    }
};