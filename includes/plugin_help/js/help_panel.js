/**
 * The help panel.
 *
 * This file is an enhancement and nothing more. Every help affordance on the page is
 * already an anchor with a real address on it, so if none of this runs the question mark
 * still opens the documentation in a new tab. All the code below does is offer to show
 * the article in a panel instead of sending somebody away from what they were doing.
 *
 * The panel's contents come from this installation's own index.php. The documentation site
 * is reached by PHP, never by this browser - see includes/plugin_help/docs_api.php.
 */
ucm = ucm || {};
ucm.help = {
    // Filled in by module_help::header_print_js().
    current_page: '',
    current_slug: '',
    docs_url: '',

    lang: {
        'loading': 'Loading',
        'help': 'Help'
    },

    init: function () {
        var t = this;

        $('body').append('<div id="help_popup" style="display:none;"><div class="help_popup_wrapper"></div></div>');

        // The themes render the header buttons as <a href="#">, which this cannot change
        // from here. Give it the real address so that a middle click, a ctrl click, or a
        // panel that fails to load all still reach the documentation.
        var $header = $('#header_help');
        if ($header.length && t.docs_url) {
            $header.attr('href', t.docs_url).attr('target', '_blank').attr('rel', 'noopener noreferrer');
        }

        $header.on('click', function (e) {
            if (t.modified_click(e)) {
                return true; // open it in a tab, as asked.
            }
            e.preventDefault();
            t.open(t.current_slug, $(this).attr('href'));
            return false;
        });

        $('body').on('click', 'a.ucm_help_link', function (e) {
            if (t.modified_click(e)) {
                return true;
            }
            e.preventDefault();
            t.open($(this).data('help-slug'), $(this).attr('href'));
            return false;
        });
    },

    /** Did the person ask for a new tab rather than for the panel? */
    modified_click: function (e) {
        return e.which > 1 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey;
    },

    /**
     * @param slug          the article to show, '' for whatever covers this screen
     * @param fallback_href where to send the reader if the panel cannot be built
     */
    open: function (slug, fallback_href) {
        var t = this;
        ucm.form.open_modal({
            type: 'inline',
            content: '#help_popup',
            title: t.lang.help,
            load_callback: function ($modal) {
                var $body = $modal.find('.help_popup_wrapper').text(t.lang.loading);
                // Same origin. index.php fetches the documentation, this browser does not.
                $.post('index.php?m=help&p=help_article', {
                    display_mode: 'ajax',
                    slug: slug || '',
                    page_key: t.current_page || '',
                    form_auth_key: ucm.form_auth_key
                }).done(function (html) {
                    $body.html(html);
                }).fail(function () {
                    // Do not strand anyone in an empty modal. The link always worked.
                    ucm.form.close_modal();
                    if (fallback_href) {
                        window.open(fallback_href, '_blank', 'noopener,noreferrer');
                    }
                });
            }
        });
    }
};

$(function () {
    ucm.help.init();
});
