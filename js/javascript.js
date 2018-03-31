// JavaScript Document


// in a function so when we add multiple rows it can re-run to add calendars

var ucm = {

    social: {},
    messages: [],
    errors: [],
    lang:{
    },
    settings:{
        cookie_path: '/'
    },
    get_vars: function(){
        return ucm.settings;
    },
    get_var: function(key){
        return typeof ucm.settings[key] != 'undefined' ? ucm.settings[key] : false;
    },
    set_var: function(key, val){
        ucm.settings[key] = val;
    },
    add_message: function(message){
        this.messages.push(message);
    },
    add_error: function(message){
        this.errors.push(message);
    },
    display_messages: function(fadeout){
        var html = '';
        for(var i in this.messages){
            html += '<div class="ui-widget" style="padding-top:10px;"><div class="ui-state-highlight ui-corner-all" style="padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>';
            html += this.messages[i] + '<br/>';
            html += '</p> </div> </div>';
        }
        for(var i in this.errors){
            html += '<div class="ui-widget" style="padding-top:10px;"><div class="ui-state-error ui-corner-all" style="padding: 0 .7em;"><p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>';
            html += this.errors[i] + '<br/>';
            html += '</p> </div> </div>';
        }
        $('#message_popdown').html(html);
        $('#message_popdown').fadeIn();
        this.messages=new Array();
        this.errors=new Array();
        if(typeof fadeout != 'undefined' && fadeout){
            setTimeout(function(){
                $('#message_popdown').fadeOut();
            },4000);
        }
    },
    init_buttons: function(){
        $('.submit_button').button();
        $('.uibutton').button();
    },
    load_calendars: function(){
        /*if(typeof js_cal_format == 'undefined'){
            var js_cal_format = 'dd/mm/yy';
        }*/
        $('.date_field').datepicker( {
            /*dateFormat: js_cal_format,*/
            showButtonPanel: true,
            changeMonth: true,
            changeYear: true,
            showAnim: false,
            constrainInput: false/*,
            yearRange: '-90:+3'*/

        });
    },
    init_interface: function(){

        // ui stuff:
        ucm.init_buttons();
        ucm.load_calendars();
        // tables:
        $('.tableclass_rows').each(function(){
            // check if there's a row action here.
            $('.row_action',this).each(function(){
                var row_action = this;
                var alink = $('a',row_action)[0];
                if(typeof alink == 'undefined')return;
                var row = $(this).parents('tr')[0];
                $(row).hover(function(){$(this).addClass('hover')},function(){$(this).removeClass('hover')});
                $('a',row).click(function(){
                    row_clicking=true;
                });
                $('input',row).click(function(){
                    row_clicking=true;
                });
                $(row).click(function(){
                    if(row_clicking)return true;
                    row_clicking=true;
                    if(!move_checking){
                        move_checking = true; // so we only do it once.
                        $('body').mousemove(function(){
                            row_clicking = false;
                        });
                    }
                    if(typeof alink != 'undefined'){
                        //$(alink).click();
                        /*var foo = $(alink).attr('href');
                        console.debug(row_action);
                        console.debug(alink);
                        console.debug(foo);return;
                        if(foo != '' && foo != '#'){
                            window.location.href=foo;
                        }*/
                        if(typeof alink.href != 'undefined' && alink.href != '' && alink.href != '#'){
                            window.location.href=alink.href;
                        }
                    }
                });
            });
        });

        // ajax search.
        /*$('#ajax_search_text').val(ajax_search_ini);*/
        $('#ajax_search_text').keyup(function(e){

            if($(this).val() == ''){
                $('#quick_search_placeholder div').fadeIn('fast');
            }else{
                $('#quick_search_placeholder div').fadeOut('fast');
            }


            if(!e)e = window.event;
            if(e.keyCode == 27){
                $('#ajax_search_result').hide();
                return;
            }
            if($(this).val()=='')return;
            try{ajax_search_xhr.abort();}catch(err){}
            ajax_search_xhr = $.ajax({
                type: "POST",
                url: ajax_search_url,
                data: {
                    ajax_search_text:$(this).val()
                },
                success: function(result){
                    if(result == ''){
                        $('#ajax_search_result').hide();
                    }else{
                        $('#ajax_search_result').html(result).show();
                    }
                }
            });
        });

        $('.responsive-toggle-button,.box-responsive header').click(function(e){
            if(typeof e.target.parentElement != 'undefined' && e.target.parentElement.tagName == 'A' && $(e.target.parentElement).attr('href') != '#'){
                e.stopPropagation();
                return true;
            }
            if(typeof(e.target.tagName) != 'undefined' && e.target.tagName == 'A' && $(e.target).attr('href') != '#'){
                e.stopPropagation();
                return true;
            }
            var p = $(this).parents('.box-responsive').first();
            if($(p).hasClass('responsive-toggled')){
                $(p).removeClass('responsive-toggled');
            }else{
                $(p).addClass('responsive-toggled');
            }
            return false;
        });

        typeof ucm.form != 'undefined' && ucm.form.init();
    }
};

var load_calendars = ucm.load_calendars;
var init_interface = ucm.init_interface;

if (!window.console) console = {};
console.log = console.log || function(){};
var row_clicking = false,move_checking = false;



function open_shut(id){
	var bloc = document.getElementById('show_hide_'+id);
	if(bloc){
		if(bloc.style.display=='none'){
			bloc.style.display='';
		}else{
			bloc.style.display='none';
		}
	}
	return false;
}
