ucm = ucm || {};
ucm.help = {
    current_pages: '',
    current_modules: '',
    lang: {
        'loading': 'Loading',
        'help': 'Help'
    },
    // The Help button used to open an iframe on a third party site, with this install's
    // purchase codes and address in the query string. That site is gone, so the popup
    // now just points at the documentation.
    url_docs: 'https://github.com/dtbaker/ucm',
    init: function(){
        var t = this;
        $('body').append('<div id="help_popup" style="display:none;"> <div class="help_popup_wrapper">' + t.lang.loading + ' </div></div>');

        $('#header_help').click(function(){
            ucm.form.open_modal({
                type: "inline",
                content: "#help_popup",
                title: ucm.help.lang.help,
                load_callback: function($modal){
                    $modal.find('.help_popup_wrapper').html(
                        '<p>Documentation and support for Ultimate Client Manager: ' +
                        '<a href="' + t.url_docs + '" target="_blank" rel="noopener">' + t.url_docs + '</a></p>'
                    );
                }

            });
            return false;
        });
    }
};


$(function(){ucm.help.init();});
