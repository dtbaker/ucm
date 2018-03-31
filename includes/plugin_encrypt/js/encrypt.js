$(function(){
    $('span.encrypt_create').each(function(){
        var r = $(this);
        r.hide();
        r.parent('td').first().hover(function(){r.show();},function(){r.hide();});
        r.parent('.form-control').first().hover(function(){r.show();},function(){r.hide();});
    });
    $('span.encrypt_popup').each(function(){
        var r = $(this);
        r.hide();
        r.parent('td').first().hover(function(){r.show();},function(){r.hide();});
        r.parent('.form-control').first().hover(function(){r.show();},function(){r.hide();});
    });
});