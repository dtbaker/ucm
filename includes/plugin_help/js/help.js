ucm = ucm || {};
ucm.help = {
    current_pages: '',
    current_modules: '',
    url_extras: '',
    url_help: 'https://ultimateclientmanager.com/api/help.php?',
    lang: {
        'loading': 'Loading',
        'help': 'Help'
    },
    init: function(){
        var t = this;
        $('body').append('<div id="help_popup" style="display:none;"> <div class="help_popup_wrapper">' + t.lang.loading + ' </div></div>');

        $('#header_help').click(function(){
            ucm.form.open_modal({
                type: "inline",
                content: "#help_popup",
                title: ucm.help.lang.help,
                load_callback: function($modal){
                    $modal.find('.help_popup_wrapper').html('<iframe src="' + ucm.help.url_help + 'pages=' + ucm.help.current_pages + '&modules=' + ucm.help.current_modules + ucm.help.url_extras + '" id="ghelp_iframe" frameborder="0"  ></iframe>');
                }

            });
            return false;
        });
    }
};


$(function(){ucm.help.init();});