ucm.settings_popup = {
    init: function(){
        // hunt for any elements that contain data-settings-url attributes and insert a new icon url into the container?
        $("[data-settings-url!=''][data-settings-url]").each(function(){
            $(this).prepend('<span class="data-settings-button ' + $(this).data('settings-class') + '"><a href="' + $(this).data('settings-url') + '" target="_blank">Settings</a></span>');
        });
    }
};
$(function(){
  ucm.settings_popup.init();
});
