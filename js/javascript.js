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

/**
 * Submit the form a button lives in as a POST that carries the form auth token.
 *
 * The list pages are GET forms so a search stays bookmarkable, and a hidden token field
 * in a GET form ends up in the query string, the browser history, the server access log
 * and the Referer header of any link on the results page. So the token rides on the
 * button instead (module_form::print_form_auth_attributes) and is added here, at click
 * time, on the one submit that actually needs it.
 */
ucm.submit_guarded = function (el) {
	var $el = jQuery(el);
	var $form = $el.closest('form');
	if (!$form.length) {
		return false;
	}
	return ucm.submit_guarded_form($form, $el.data('form-auth-key'));
};

/**
 * As above, for the cases where the button is not inside the form it submits - the CSV
 * export popdown hands its fields to whichever list form is on the page.
 */
ucm.submit_guarded_form = function ($form, key) {
	$form = jQuery($form);
	if (!$form.length || !$form[0]) {
		return false;
	}
	if (key) {
		$form.attr('method', 'post');
		var $field = $form.find('input[name="form_auth_key"]');
		if (!$field.length) {
			$field = jQuery('<input>').attr({type: 'hidden', name: 'form_auth_key'});
			$form.append($field);
		}
		$field.val(key);
	}
	$form[0].submit();
	return true;
};

/**
 * Collect the security role form's permission controls into a single posted field.
 *
 * The permission grid has one control per permission per action, and
 * module_security::has_feature_access() adds a permission row the first time any screen
 * asks for one, so the grid grows as the install is used. On a database with 1300
 * permissions it is already 2345 controls. PHP's max_input_vars defaults to 1000, and a
 * post that exceeds it is truncated during request startup with no flag the application
 * can read - so the far side of the grid was being dropped and the role saved with
 * whatever happened to fit.
 *
 * So the controls carry no name of their own. This reads them into one JSON field, which
 * keeps the post at a fixed size no matter how large the table grows.
 * module_security::process() decodes it.
 */
ucm.permission_bundle = function (form) {
	var $form = jQuery(form);
	var $field = $form.find('input[name="permission_data"]');
	if (!$form.length || !$field.length) {
		return;
	}

	var collect = function () {
		var bundle = {permission: {}, permission_drop_down: {}, group_access: {}};

		$form.find('input[data-permission]').each(function () {
			if (!this.checked) {
				return;
			}
			var id = jQuery(this).data('permission');
			var action = jQuery(this).data('permission-action');
			if (!bundle.permission[id]) {
				bundle.permission[id] = {};
			}
			bundle.permission[id][action] = 1;
		});

		// The drop downs name every permission id they cover, so the handler knows which
		// rows to clear before writing the one that was chosen.
		$form.find('select[data-permission-drop-down]').each(function () {
			bundle.permission_drop_down[jQuery(this).data('permission-drop-down')] = jQuery(this).val();
		});

		$form.find('input[data-group-access]').each(function () {
			if (this.checked) {
				bundle.group_access[jQuery(this).data('group-access')] = 1;
			}
		});

		$field.val(JSON.stringify(bundle));
	};

	// On change as well as on submit: the save button posts through
	// ucm.submit_guarded_form() in places, which calls form.submit() directly and so
	// never fires the submit handler.
	$form.on('change', 'input[data-permission], select[data-permission-drop-down], input[data-group-access]', collect);
	$form.on('submit', collect);
	collect();
};
