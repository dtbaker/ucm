<?php


if(!module_config::can_i('view','API Settings')){
    redirect_browser(_BASE_HREF);
}

print_heading('API Settings');

module_config::print_settings_form(
    array(
        array(
            'key'=>'api_key',
            'html'=>module_api::get_api_key(false),
            'type'=>'html',
            'description'=>'Your Unique API Key',
	        'help' => 'This is your API Key. Use it to access the system via the API.'
        ),
        array(
            'key'=>'api_url',
            'html'=>htmlspecialchars(module_api::get_api_url()),
            'type'=>'html',
            'description'=>'API URL',
	        'help' => 'This is your API URL. Use this to access the API from another system.'
        ),
    )
);
