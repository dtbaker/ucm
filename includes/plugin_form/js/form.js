ucm = ucm || {};


function dtbaker_loading_button(btn){

    var $button = jQuery(btn);
    if($button.data('done-loading') == 'yes')return false;
    $button.data('done-loading','yes');
    // $button.prop('disabled',true);
    var _modifier = $button.is('input')  ? 'val' : 'text';
    var existing_text = $button[_modifier]();
    var existing_width = $button.outerWidth();
    var loading_text = '⡀⡀⡀⡀⡀⡀⡀⡀⡀⡀⠄⠂⠁⠁⠂⠄';
    var completed = false;

    $button.css('width',existing_width);
    $button.addClass('dtbaker_loading_button_current');

    function delay_anim_text(){

        $button.prop('disabled',true);
        $button[_modifier](loading_text);

        var anim_index = [0,1,2];

        // animate the text indent
        function moo() {
            if (completed)return;
            var current_text = '';
            // increase each index up to the loading length
            for(var i = 0; i < anim_index.length; i++){
                anim_index[i] = anim_index[i]+1;
                if(anim_index[i] >= loading_text.length)anim_index[i] = 0;
                current_text += loading_text.charAt(anim_index[i]);
            }
            $button[_modifier](current_text);
            setTimeout(function(){ moo();},60);
        }

        moo();
    }
    setTimeout(delay_anim_text,100);

    function button_complete(){
        completed = true;
        $button[_modifier](existing_text);
        $button.removeClass('dtbaker_loading_button_current');
        $button.prop('disabled',false);
        $button.css('width','auto');
        $button.data('done-loading','finished');
    }
    setTimeout(button_complete,4000);
    /*setTimeout(function(){
        $button.prop('disabled',false);
    },4000);*/

    return {
        done: function(){
            button_complete()
        }
    }

}

ucm.form = {
    settings: {
        dynamic_select_edit_url: '',
        fieldset_editing_url: '',
        time_format: 'hh:mm tt'
    },
    lang: {
        edit_fieldset_title: 'Edit Fieldset',
        dynamic_select_edit_title: 'Edit Entries',
        cancel: 'Close',
        ok: 'OK',
        mins: 'mins',
        hr: 'hr',
        hrs: 'hrs',
        am: 'am',
        AM: 'AM',
        pm: 'pm',
        PM: 'PM'
    },
    close_modal: function(){
        $('.ucm-modal-popup.in').last().modal('hide');
    },
    destroy_modal: function(){
        $('.ucm-modal-popup').remove();
    },
    open_modal: function( ajax_modal_settings ){
        // find the button url
        // load that inner content via ajax
        // display in a modal
        // any submits in this modal form will refresh the page.
        //$('#form-modal-template-inserted').remove();

        var modal_html = $('#form-modal-template').html();
        modal_html = modal_html.replace(/\{modal-header\}/, ajax_modal_settings.title );
        // loading.
        var $modal = $(modal_html);
        $('body').append($modal);
        if(typeof ajax_modal_settings.buttons == 'undefined' || !ajax_modal_settings.buttons){
            $modal.addClass('no-buttons');
        }else{
            // add/generate buttons.
            // todo.
            if(typeof ajax_modal_settings.buttons == 'object'){
                for(var i in ajax_modal_settings.buttons){
                    if(ajax_modal_settings.buttons.hasOwnProperty(i)){
                        switch(ajax_modal_settings.buttons[i]){
                            case 'close':
                            case 'cancel':
                                $modal.find('.modal-footer').append('<button type="button" class="btn btn-secondary close-modal">' + i + '</button>');
                                break;
                            default:
                                (function(){
                                    var $button = $('<button type="button" class="btn btn-primary submit_button">' + i + '</button>');
                                    var func = window[ajax_modal_settings.buttons[i]];
                                    $button.click(function(){
                                        if(typeof func == 'function'){
                                            func($modal);
                                        }
                                    });
                                    $modal.find('.modal-footer').append($button);
                                })();
                        }
                    }
                }
            }else{
                $modal.find('.modal-footer').append('<button type="button" class="btn btn-secondary close-modal">Close</button>');
            }

        }
        $modal.on('shown.bs.modal', function () {
            ucm.init_interface();
            $modal.on('click', '.close-modal', function (e) {
                e.preventDefault();
                $(this).closest('.modal').modal('hide');
                return false;
            });
        });
        $modal.on('hide.bs.modal', function () {
            $modal.find('[data-tinymce]').each(function(){

                $(this).tinymce().remove();
            });

        });
        if(typeof ajax_modal_settings.type != 'undefined' && ajax_modal_settings.type == 'inline' ){
            $modal.removeClass('loading').find('.modal-body').html( $(ajax_modal_settings.content).html() );
            ucm.init_interface();


            if(typeof ajax_modal_settings.load_callback != 'undefined'){
                if(typeof ajax_modal_settings.load_callback == 'function'){
                    ajax_modal_settings.load_callback($modal);
                }else if(typeof ajax_modal_settings.load_callback == 'string') {
                    var func = window[ajax_modal_settings.load_callback];
                    if (typeof func == 'function') {
                        func($modal);
                    }
                }
            }

        }else {
            $.post(ajax_modal_settings.href, {
                display_mode: "ajax",
                form_auth_key: ucm.form_auth_key,
                vars: ucm.get_vars(),
                modal: "true"
            }, function (data) {
                $modal.removeClass('loading').find('.modal-body').html(data);
                ucm.init_interface();


                if(typeof ajax_modal_settings.load_callback != 'undefined'){
                    if(typeof ajax_modal_settings.load_callback == 'function'){
                        ajax_modal_settings.load_callback($modal);
                    }else if(typeof ajax_modal_settings.load_callback == 'string') {
                        var func = window[ajax_modal_settings.load_callback];
                        if (typeof func == 'function') {
                            func($modal);
                        }
                    }
                }

            });
        }
        $modal.modal().draggable({
            handle: ".modal-header"
        });
    },
    init: function(){
        $('.submit_button').off('click.loading').on( 'click.loading', function(e) {
            var loading_button = dtbaker_loading_button(this);
            if (!loading_button) {
                e.preventDefault();
                return false;
            }
            return true;
        });
        // the new date-time picker stuff:
        if(typeof jQuery.fn.timepicker != 'undefined'){
            $('.time_field').timepicker({
                timeFormat: ucm.settings.time_picker_format,
                timeInput: true,
                controlType: 'select',
                parse: 'loose'
            });
            if (!Date.now) {
                Date.now = function() { return new Date().getTime(); }
            }
            $('.date_time_field').each(function(){
                // create a new hidden field for our epoch time storage.
                // copy the name of this field over to the hidden field.
                var $thistxt = $(this);
                if(!$thistxt.hasClass('hasDatepicker')) {
                    var currenttime = $thistxt.val();
                    var $hidden = $('<input/>').attr('type', 'hidden').attr('name', $thistxt.attr('name')).val($thistxt.val());
                    $thistxt.attr('name', 'ignore').after($hidden);
                    $thistxt.datetimepicker({
                        timeFormat: ucm.settings.time_picker_format,
                        timeInput: true,
                        controlType: 'select',
                        parse: 'loose',
                        onSelect: function (datetimeText, datepickerInstance) {
                            if (typeof datepickerInstance.hour != 'undefined') {
                                var date = datepickerInstance['$input'].datepicker("getDate");
                                var timestamp = Math.round(new Date(date).getTime() / 1000);
                                // console.log(timestamp);
                                $hidden.val(timestamp);
                            } else if (typeof datepickerInstance.input != 'undefined') {
                                var date = datepickerInstance.input.datepicker("getDate");
                                var timestamp = Math.round(new Date(date).getTime() / 1000);
                                // console.log(timestamp);
                                $hidden.val(timestamp);
                            }
                        }
                    });
                    if (parseInt(currenttime) > 0) {
                        var newtime = new Date(currenttime * 1000);
                        $thistxt.datepicker('setTime', newtime);
                        $thistxt.datepicker('setDate', newtime);
                    }
                }
            });
        }

        $('body').off('click.modal').on( 'click.modal', '[data-ajax-modal]', function(e) {
            e.preventDefault();
            var $butt = $(this);
            var ajax_modal_settings = $butt.data('ajax-modal');
            if(typeof ajax_modal_settings.title == 'undefined'){
                ajax_modal_settings.title = $butt.text();
            }
            if(typeof ajax_modal_settings.href == 'undefined'){
                ajax_modal_settings.href = $butt.attr('href');
            }

            ucm.form.open_modal( ajax_modal_settings );

            return false;
        });
        $('[data-lookup]').autocomplete({
            create: function( event, ui ) {
                // we need to clone the current input id and make it hidden
                // this is what gets the
                var $textbox = $(event.target);
                var lookup = $textbox.data('lookup');
                var $hidden = $('<input type="hidden">').attr('name', $textbox.attr('name')).val( $textbox.val() );
                $textbox.attr('name','autocomplete_value');
                ucm.set_var('lookup_' + lookup.key, $textbox.val() );
                $textbox.val(lookup.display);
                $textbox.on('focus', function(){
                    if( typeof lookup['onfocus'] != 'undefined'){
                        eval(lookup['onfocus'] + '($textbox);');
                    }
                    if( $textbox.val() == ''){
                        $textbox.autocomplete('search', '');
                    }
                } );
                $textbox.after($hidden);
                $textbox.data('hidden-field', $hidden);
            },
            change: function( event, ui ) {
                var $textbox = $(event.target);
                if( ! $textbox.val().length ){
                    // user has cleared the input
                    var lookup = $elem.data('lookup');
                    ucm.set_var('lookup_' + lookup.key, 0 );
                    $textbox.data('hidden-field').val( 0 );
                }
            },
            select: function( event, ui ) {

                var $textbox = $(event.target);
                $textbox.val( ui.item.value );
                var lookup = $elem.data('lookup');
                ucm.set_var('lookup_' + lookup.key, ui.item.key );
                $textbox.data('hidden-field').val( ui.item.key );

                return false;
            },
            source: function( request, response ){
                $elem = this.element;
                //console.log($elem.val());
                var lookup = $elem.data('lookup');
                lookup.autocomplete = 'autocomplete';
                lookup.search = request.term;
                lookup.form_auth_key = ucm.form_auth_key;
                lookup.vars = ucm.get_vars();
                $.post(
                    ajax_search_url,
                    lookup,
                    function(data){
                        response(data);
                    }
                );
            },
            autoFocus: false,
            minLength: 0
        });

        $('body').on('click','[data-ajax-button]', function(e){
            e.preventDefault();
            var action = $(this).data('ajax-button');
            switch(action){
                case 'refresh':
                case 'reload':
                    window.location.href = window.location.href + (window.location.href.match('/\?/') ? '&' : '?') + 'reload';
                    break;
                default:
                    alert('Unknown button action. Please update and clear browser cache.');
            }
            return false;
        });

        $("form[data-ajax-form]").each(function () {
            var $f = $(this);
            if($f.data('completed-ajax-binding'))return;
            $f.data('completed-ajax-binding',true);
            var ajax_data = $f.data('ajax-form');
            // todo: check for file fields and handle that submit differently via iframe or something.
            (function(){
                $f.find('input[type=submit],button').click(function(){
                    $(this).attr('clicked','true');
                });
                var xhr;
                var _orgAjax = jQuery.ajaxSettings.xhr;
                jQuery.ajaxSettings.xhr = function () {
                    xhr = _orgAjax();
                    return xhr;
                };
                $f.submit(function(e) {
                    e.preventDefault(); // avoid to execute the actual submit of the form.
                    // find out which button clicked the form and send that in post as well, just like a normal submit.
                    var submitval = $f.find('[clicked=true]').attr('name');
                    $f.parent().find('.form-ajax-message').remove();
                    var submiterror = false;
                    $.ajax({
                        type: "POST",
                        url: $f.attr('action'),
                        data: $f.serialize() + '&x_ucm_ajax=1' + ( submitval ? '&' + submitval + '=1' : '' ), // serializes the form's elements.
                        dataType: 'json',
                        error: function(error, status, xhr){
                            console.log('Ajax error');
                            submiterror = true;
                            $f.before('<div class="form-ajax-message error">Ajax Error. Try to logout and back in again.</div>');
                        },
                        complete: function(xhrfinal){
                            // handle non json redirect (e.g. deleting an item)
                            if(submiterror && xhr.responseURL){
                                $f.parent().find('.form-ajax-message').remove();
                                window.location.href=xhr.responseURL;
                            }
                        },
                        success: function(data)
                        {
                            if(typeof ajax_data.callback !== 'undefined'){
                                var func = window[ajax_data.callback];
                                if (typeof func == 'function') {
                                    func(data, $f, ajax_data);
                                }
                            }else{
                                // default action is to hide the form and display a success message.
                                if( data && typeof data.success != 'undefined' && data.success) {
                                    $f.before('<div class="form-ajax-message success">' + data.message + '</div>');
                                    if( typeof data.buttons != 'undefined' ) {
                                        var $butts = $('<div class="form-ajax-buttons"></div>')
                                        for(var i in data.buttons){
                                            $butts.append('<button type="button" class="btn btn-primary submit_button" data-ajax-button="' + i + '">' + data.buttons[i] + '</button>');
                                        }
                                        $f.before($butts);
                                    }
                                    $f.addClass('hidden');
                                }else{
                                    $f.before('<div class="form-ajax-message error">Error: ' + (data && typeof data.message != 'undefined' ? data.message : data ) + '</div>');
                                }
                            }
                        }
                    });
                    return false;
                });
            })();
        });

        // the fieldset editing button
        if(ucm.form.settings.fieldset_editing_url) {
            $("[data-fieldset-id!=''][data-fieldset-id]").each(function () {
                var $f = $(this);
                if($f.data('completed-fieldset-settings'))return;
                $f.data('completed-fieldset-settings',true);
                var $fsettings = $f.data('fieldset-settings');
                if(typeof $fsettings.editable != 'undefined' && !$fsettings.editable){
                    // disabled.
                }else {
                    var settingsbutton = $('<span class="fieldset-settings-button"></span>').append($('<a href="#"></a>').click(function (e) {
                        e.preventDefault();
                        // show popup for this fieldset.
                        ucm.set_var('fieldset_id', $f.data('fieldset-id'));
                        ucm.form.destroy_modal();
                        ucm.form.open_modal({
                            href: ucm.form.settings.fieldset_editing_url,
                            title: ucm.form.lang.edit_fieldset_title,
                            buttons: {
                                'Cancel': 'cancel',
                                'Save': 'save_fieldset_settings'
                            },
                            load_callback: function ($modal) {

                            }

                        });
                        return false;
                    }).text('Settings'));

                    // find previous title.
                    var $title = $(this).prev('h3');
                    if(!$title.length){
                        $title = $(this).find('header');
                    }
                    if(!$title.length){
                        $title = $(this).find('.box-title');
                    }

                    $($title).hover(function () {
                        settingsbutton.data('hover', true).addClass('hover')
                    }, function () {
                        settingsbutton.data('hover', false);
                        setTimeout(function () {
                            if (!settingsbutton.data('hover')) {
                                settingsbutton.removeClass('hover').data('hover', false);
                            }
                        }, 500);

                    });
                    $title.find('.title').before( $('<span class="button"></span>').append( settingsbutton ) );
                }
            });
        }

    },
    dynamic: function(object_id,callback){

        var $object = $("#"+object_id);

        function set_add_remove_buttons(){
            $object.find('.remove_addit').show();
            $object.find('.add_addit').hide();
            $object.find('.add_addit:last').show();
            $object.find('.dynamic_block:only-child > .remove_addit').hide();
        }

        function selrem(e){
            e.preventDefault();
            var clickety = this;
            $(clickety).parents('.dynamic_block').remove();
            set_add_remove_buttons();
            if(callback && typeof callback == 'function'){
                callback();
            }
            return false;
        }
        function seladd(e){
            e.preventDefault();
            var clickety = this;
            //var box = $('#'+id+' .dynamic_block:last').clone(true);
            var x=0,old_names=[];
            // these pointless looking loops are because IE doesn't handle
            // cloning the name="" part of dynamic input boxes very well... ?
            $('input',$(clickety).parents('.dynamic_block')).each(function(){
                old_names[x++] = $(this).attr('name');
            });
            $('select',$(clickety).parents('.dynamic_block')).each(function(){
                old_names[x++] = $(this).attr('name');
            });
            if($(clickety).parents('.dynamic_block .clone_template').length){
                //var box = $(clickety).parents('.dynamic_block .clone_template > div').clone(true); // todo - figure out if we need "true"
                alert('Under dev...');
            }else{
                var box = $(clickety).parents('.dynamic_block').clone(); // todo - figure out if we need "true"

            }
            x = 0;
            $('input',box).each(function(){
                if( $(this).autocomplete( "instance" ) ){
                   // $(this).autocomplete( "destroy" )
                }
                if(typeof old_names[x] == 'string'){
                    $(this).attr('name', old_names[x]);
                }
                x++;
            });
            $('select',box).each(function(){
                if(typeof old_names[x] == 'string'){
                    $(this).attr('name', old_names[x]);
                }
                x++;
            });
            $('input,select',box).each(function(){
                $(this).val('');
                if($(this).hasClass('date_field')) {
                    $(this).removeClass('hasDatepicker');
                    $(this).datepicker('destroy');
                    // unique id for this date field/
                    $(this).attr('id', 'input_' + Math.floor(Math.random() * 1000));
                }

            });
            $('.dynamic_clear:input',box).val('');
            $('.dynamic_clear',box).html('');
            //$(clickety).after(box);
            $object.find('.dynamic_block:last').after( box);
            ucm.form.init();
            set_add_remove_buttons();
            load_calendars();
            if(callback && typeof callback == 'function'){
                callback();
            }
            return false;
        }

        $object.on('click','.add_addit',seladd);
        $object.on('click','.remove_addit',selrem);
        set_add_remove_buttons();

        return {

        };
    },

    dynamic_select_box: function(element){
        if($(element).val()=='create_new_item'){
            var current_val = $(element).val();
            if(current_val=='create_new_item')current_val = '';
            var id = $(element).attr('id');
            if(typeof id == 'object')id = $(element).prop('id');
            var name = $(element).attr('name');
            if(typeof name == 'object')name = $(element).prop('name');
            var html = '<input type="text" name="'+name+'" id="'+id+'" value="'+current_val+'">';
            // add a new input box.
            $(element).after('<span id="dynamic_select_box_placeholder"></span>');
            $(element).remove();
            var box = $(html);
            $('#dynamic_select_box_placeholder').after(box).remove();
            box[0].focus();
            box[0].select();
        }else if($(element).val()=='_manage_items') {


            var current_options = $(element).find('option:selected').data('items');
            $(element).find("option:selected").prop("selected", false);
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



        }
    }

};

$(function(){
    //ucm.form.init();
    // moved to ucm.init_interface
});
// backwards compat:
function dynamic_select_box(element){
    ucm.form.dynamic_select_box(element);
}
function seladd(){
    console.log('deprecated call to seladd()');
}
function selrem(){
    console.log('deprecated call to selrem()');
}
function set_add_del(object_id){
    console.log('deprecated call to set_add_del() - use ucm.form.dynamic() instead');
    new ucm.form.dynamic(object_id);
}

function save_fieldset_settings(){
    // this should trigger the ajax form submission on the fieldset_edit.php page.
    $('#edit-fieldset-settings').submit();
}


function number_out( database_provided_number ){
    var decimal_separator = typeof ucm.settings.decimal_separator != 'undefined'  ? ucm.settings.decimal_separator : '.';
    var thousand_separator = typeof ucm.settings.thousand_separator != 'undefined' ? ucm.settings.thousand_separator : ',';
    var decimal_places = parseInt(typeof ucm.settings.decimal_places != 'undefined' ? ucm.settings.decimal_places : '2');

    number = (database_provided_number + '').replace(/[^0-9+\-Ee.]/g, '')
    var n = !isFinite(+number) ? 0 : +number
    var prec = !isFinite(+decimal_places) ? 0 : Math.abs(decimal_places)
    var sep = thousand_separator
    var dec = decimal_separator
    var s = ''
    var toFixedFix = function (n, prec) {
        var k = Math.pow(10, prec)
        return '' + (Math.round(n * k) / k)
                .toFixed(prec)
    }
    // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.')
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || ''
        s[1] += new Array(prec - s[1].length + 1).join('0')
    }
    return s.join(dec);
}
function number_in( user_provided_number ){
    var decimal_separator = typeof ucm.settings.decimal_separator != 'undefined'  ? ucm.settings.decimal_separator : '.';
    var thousand_separator = typeof ucm.settings.thousand_separator != 'undefined' ? ucm.settings.thousand_separator : ',';
    var decimal_places = parseInt(typeof ucm.settings.decimal_places != 'undefined' ? ucm.settings.decimal_places : '2');

    if ( thousand_separator === '.' && user_provided_number.match(/\d\.\d\d\d/) ){
        user_provided_number = user_provided_number.replace( thousand_separator, '' );
    }
    if ( decimal_separator !== '.' ) {
        user_provided_number = user_provided_number.replace( decimal_separator, '.' );
    }
    return parseFloat(user_provided_number);
}