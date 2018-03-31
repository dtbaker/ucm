<?php


if(!module_config::can_i('view','Settings')){
    redirect_browser(_BASE_HREF);
}

if(class_exists('module_security',false)){
    // if they are not allowed to "edit" a page, but the "view" permission exists
    // then we automatically grab the page and regex all the crap out of it that they are not allowed to change
    // eg: form elements, submit buttons, etc..
    module_security::check_page(array(
        'category' => 'Config',
        'page_name' => 'Settings',
        'module' => 'config',
        'feature' => 'Edit',
    ));
}
$module->page_title = 'System Settings';

print_heading(array(
    'title' => 'Basic System Settings',
    'type'=> 'h2',
    'main' => true,
)
);


$settings = array(
         /*array(
            'key'=>'_installation_code',
            'default'=>'',
             'type'=>'text',
             'description'=>'Your license code',
             'help' => 'You can find your unique license code in the "license" file from CodeCanyon.net after you purchase this item. It looks like this: 30d91230-a8df-4545-1237-467abcd5b920 ',
         ),*/
         array(
            'key'=>'system_base_dir',
            'default'=>'/',
             'type'=>'text',
             'description'=>'Base URL for your system (eg: / or /admin/)',
         ),
         array(
            'key'=>'system_base_href',
            'default'=>'',
             'type'=>'text',
             'description'=>'URL for your system (eg: http://foo.com)',
         ),
         array(
            'key'=>'admin_system_name',
            'default'=>'Ultimate Client Manager',
             'type'=>'text',
             'description'=>'Name your system',
         ),
         array(
            'key'=>'header_title',
            'default'=>'UCM',
             'type'=>'text',
             'description'=>'Text to appear in header',
         ),
         'date_format'=>array(
            'key'=>'date_format',
            'default'=>'d/m/Y',
             'type'=>'text',
             'description'=>'Date format for system',
            // 'help'=>'Use the php date values available here: http://php.net/date <br><br>Best to use one of these:<br>d/m/Y<br>m/d/Y<br>Y/m/d',
         ),
         'date_input'=>array(
            'key'=>'date_input',
            'default'=>'1',
             'type'=>'select',
             'description'=>'Date format',
             'options' => array(
                 1=>'d/m/Y',
                 2=>'Y/m/d',
                 3=>'m/d/Y',
                 4=>'d.m.Y',
             ),
             //'help' =>'Set this to match the above date format.'
         ),
         array(
            'key'=>'timezone',
            'default'=>'America/New_York',
             'type'=>'text',
             'description'=>'Your timezone',
	         'help' =>'A list of valid timezones is located here: <a href="http://php.net/manual/en/timezones.php" target="_blank">http://php.net/manual/en/timezones.php</a>'
         ),
         array(
            'key'=>'alert_days_in_future',
            'default'=>'5',
             'type'=>'text',
             'description'=>'Days to alert due tasks in future (for dashboard)',
         ),
         array(
            'key'=>'hide_extra',
            'default'=>'1',
             'type'=>'checkbox',
             'description'=>'Hide "extra" form fields by default',
         ),
         array(
            'key'=>'hourly_rate',
            'default'=>'60',
             'type'=>'text',
             'description'=>'Default hourly rate',
         ),
         array(
            'key'=>'job_type_default',
            'default'=>'Website Design',
             'type'=>'text',
             'description'=>'Default type of job',
         ),
         array(
            'key'=>'tax_name',
            'default'=>'TAX',
             'type'=>'text',
             'description'=>'What is your TAX called? (eg: GST)',
         ),
         array(
            'key'=>'tax_percent',
            'default'=>'10',
             'type'=>'text',
             'description'=>'Percentage tax to calculate by default? (eg: 10)',
         ),
         array(
            'key'=>'todo_list_limit',
            'default'=>'6',
             'type'=>'text',
             'description'=>'Number of TODO items to show',
         ),
         array(
            'key'=>'admin_email_address',
            'default'=>'info@example.com',
             'type'=>'text',
             'description'=>'The admins email address',
         ),
         /*array(
            'key'=>'envato_show_summary_what',
            'default'=>1,
             'type'=>'select',
             'options'=>array(
                 1=>'Show todays sales',
                 2=>'Show total balance (can be slower)',
             ),
             'description'=>'What to display in menu.',
         ),*/
		array(
			'key'=>'menu_show_summary',
			'default'=>'0',
			'type'=>'checkbox',
			'description'=>'Show count next to some menu icons',
			'help'=>'This will slow down your page load times. Next to some icons (e.g. Files and Contacts) it will show you how many entries are under that particular menu. ',
		),
);

if(in_array(module_config::c('date_format','d/m/Y'),$settings['date_input']['options'])){
   unset($settings['date_format']);
    // hack to save the 'date_format' based on the date input
    $current_format = $settings['date_input']['options'][module_config::c('date_input',1)];
    if($current_format){
        module_config::save_config('date_format',$current_format);
    }
}

if(class_exists('module_security',false)){
    $roles = array();
    foreach(module_security::get_roles() as $r){
        $roles[$r['security_role_id']] = $r['name'];
    }

    $settings[] = array(
        'key'=>'contact_default_role',
        'default'=>'',
         'type'=>'select',
         'options'=>$roles,
         'description'=>'When creating a new contact, assign this role<br>(don\'t give them too many permissions!)',
     );
    $settings[] = array(
        'key'=>'user_default_role',
        'default'=>'',
         'type'=>'select',
         'options'=>$roles,
         'description'=>'When creating a new user, assign this role',
     );
}

module_config::print_settings_form(
    array(
        //'title' => _l('Basic System Settings'),
        'settings' => $settings,
    )
);

