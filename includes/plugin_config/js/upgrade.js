ucm.upgrade = {
    upgrade_url: '',
    complete_callback: false,
    upgrade_plugins: [],
    upgrade_post_data: {
        install_upgrade: 'yes',
        via_ajax: 'yes'
    },
    lang:{
        processing:'Processing, please wait...',
        completed:'Completed, click here to continue'
    },
    init: function(){
        $('#upgrade_start').click(ucm.upgrade.start_selected_upgrades);
    },
    start_selected_upgrades: function(){
        ucm.upgrade.upgrade_plugins = [];
        $('.update_checkbox input').each(function(){
            var tr = $(this).parents('tr').first();
            if($(this)[0].checked) {
                $('.update_progress',tr).slideDown();
                ucm.upgrade.upgrade_plugins.push({
                    plugin_name: tr.data('plugin'),
                    html: tr.find('.update_progress')
                });
            }
            $(this).attr('disabled','disabled');
        });
        if(ucm.upgrade.upgrade_plugins.length > 0){
            $('#upgrade_start').val(ucm.upgrade.lang.processing);
            ucm.upgrade.run_next();
        }else{
            alert('Please select at least one update.');
        }
    },
    check_for_updates: function(){
        // todo
        ucm.upgrade.upgrade_plugins = [];
        // check each of the plugins for updates via ajax and report back.
        $('tr[data-type="ajax_plugin"]').each(function(){
            ucm.upgrade.upgrade_plugins.push({
                plugin_name: $(this).data('plugin'),
                check: 1,
                html: $(this).find('.checking_progress')
            });
        });
        if(ucm.upgrade.upgrade_plugins.length > 0){
            ucm.upgrade.run_next();
        }else{
            alert('Failed, please report error to support.');
            return;
        }
        // check for any newly available plugins and add those to the list.

    },
    current_upgrade:0,
    all_done:false,
    run_next: function(){
        //if(ucm.upgrade.current_upgrade>0)return;
        if(typeof ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade] == 'undefined') {
            // completed all upgrades!
            if(typeof ucm.upgrade.complete_callback ==  'function'){
                ucm.upgrade.complete_callback();
                return;
            }
            $('#upgrade_start').val(ucm.upgrade.lang.completed);
            if(ucm.upgrade.all_done){
                // if they click the complete button at the bottom again.. redirect.
                window.location.href=window.location.href + (window.location.href.search(/\?/) ? '&' : '?') + 'done';
            }
            ucm.upgrade.all_done = true;
        }else {
            var post_data = {};
            for (var i in ucm.upgrade.upgrade_post_data) {
                if (ucm.upgrade.upgrade_post_data.hasOwnProperty(i)) {
                    post_data[i] = ucm.upgrade.upgrade_post_data[i];
                }
            }
            post_data.plugin_name = ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].plugin_name;
            //post_data.doupdate = [ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].plugin_name];
            $.ajax({
                url: ucm.upgrade.upgrade_url,
                type: 'POST',
                dataType: 'json',
                data: post_data,
                success: function (d) {
                    // did it work? update the status..
                    if (typeof d.success != 'undefined') {
                        $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).html(d.message).addClass('success');
                    } else {
                        $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).html(d.message).addClass('error');
                    }
                    // do the processing...
                    //process_upgrade
                    if(d.plugin_name) {
                        $.ajax({
                            url: ucm.upgrade.upgrade_url,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                process_upgrade: 'yes',
                                plugin_name: d.plugin_name,
                                via_ajax: 1
                            },
                            success: function (d) {
                                // did it work? update the status..
                                if (typeof d.success != 'undefined') {
                                    $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).append(d.message).addClass('success');
                                } else {
                                    $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).append(d.message).addClass('error');
                                }
                                // do the processing...
                                //process_upgrade

                                ucm.upgrade.current_upgrade++;
                                ucm.upgrade.run_next();
                            },
                            error: function (d) {
                                alert('Failed to process ' + ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].plugin_name + '. Please try again.');
                                $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).append('Failed Final Processing').addClass('error');
                                ucm.upgrade.current_upgrade++;
                                ucm.upgrade.run_next();
                            }
                        });
                    }else{
                        ucm.upgrade.current_upgrade++;
                        ucm.upgrade.run_next();
                    }
                },
                error: function (d) {
                    alert('Failed to upgrade ' + ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].plugin_name + '. Please try again.');
                    $(ucm.upgrade.upgrade_plugins[ucm.upgrade.current_upgrade].html).html('Failed').addClass('error');
                    ucm.upgrade.current_upgrade++;
                    ucm.upgrade.run_next();
                }
            });
        }
    }
};