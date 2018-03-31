<?php


if(!module_config::can_i('view','Settings')){
    redirect_browser(_BASE_HREF);
}

module_config::print_settings_form(array(
    'heading' => array(
        'title' => 'Dashboard Finance Settings',
        'main' => true,
        'type' => 'h2',
    ),
    'settings' => array(
         array(
            'key'=>'dashboard_income_summary',
            'default'=>1,
             'type'=>'checkbox',
             'description'=>'Show income summary on the dashboard.',
         ),
    )
)
);
