<?php

$script = '<script type="text/javascript" language="javascript" src="'.full_link('includes/plugin_change_request/js/public.js').'"></script>' . "\n";
$script .= '<script type="text/javascript" language="javascript"> dtbaker_public_change_request.init("'.module_change_request::link_script($website_id).'"); </script>';

$change_history = module_change_request::get_remaining_changes($website_id);

$fieldset_data = array(
    'heading' => array(
        'type' => 'h3',
        'title' => _l('Change Request Settings'),
    ),
    'class' => 'tableclass tableclass_form tableclass_full',
    'elements' => array(
        'enable' => array(
            'title' => _l('Enable Changes'),
            'field' => array(
                'type' => 'select',
                'options' => get_yes_no(),
                'name' => 'change_request[enabled]',
                'value' => isset($change_request_website['enabled'])?$change_request_website['enabled']:'0',
                'help' => 'Allow change requests. This SUPER COOL feature allows your customer to highlight something on their website and request a change. The "change request" will come through here and you can invoice for that change.'
            ),
        ),
    )
);

if(isset($change_request_website['enabled']) && $change_request_website['enabled']){
    $fieldset_data['elements']['code'] = array(
            'title' => _l('Website Code'),
            'fields' => array(
                /*array(
                    'type' => 'text',
                    'name' => 'c',
                    'value' => $script,
                ),*/
                '<small>'.nl2br(htmlspecialchars($script)).'</small>',
                _hr('Add this code to EVERY PAGE of your customers website (eg: Same as Google Analytics or in WordPress theme footer.php file). Make sure this is loaded AFTER jQuery is loaded. (Advanced users can copy this public.js file to clients website to improve load times). <br><br> Or paste this code into the browser console for testing: <br><br>%s',htmlspecialchars("var s = document.createElement('script'); s.src='".full_link('includes/plugin_change_request/js/public.js')."';  s.onload = function() { dtbaker_public_change_request.inject_js('".full_link('includes/plugin_change_request/fancybox/jquery.fancybox-1.3.4.js')."');
            dtbaker_public_change_request.inject_css('".full_link('includes/plugin_change_request/fancybox/jquery.fancybox-1.3.4.css')."',dtbaker_public_change_request.init('".module_change_request::link_script($website_id)."')); }; (document.head||document.documentElement).appendChild(s);"))
            ),
        );
    $fieldset_data['elements']['limit'] = array(
            'title' => _l('Customer Limit'),
            'fields' => array(
                array(
                    'type' => 'text',
                    'name' => 'change_request[limit_number]',
                    'value' => isset($change_request_website['limit_number'])?$change_request_website['limit_number']:5,
                    'size' => 4
                ),
                array(
                    'type' => 'select',
                    'name' => 'change_request[limit_per]',
                    'value' => isset($change_request_website['limit_per'])?$change_request_website['limit_per']:0,
                    'options' => array(
                        '1' => _l('Week'),
                        '2' => _l('Month'),
                        '3' => _l('Year'),
                        '0' => _l('All Time'),
                    ),
                ),
                _hr('You can limit your customer to a certain number of change requests (eg: if you are charging them a monthly maintenance fee)'),
                '<br/> ',
                _l('<strong>%s</strong> of %s changes remaining', max(0,$change_history[1] - $change_history[0]) , $change_history[1]),
            ),
        );

    $fieldset_data['elements']['link'] = array(
        'title' => _l('Request Link'),
            'fields' => array(
                '<a href="'.module_change_request::link_public($website_id).'" target="_blank">'. _l('Request Change Link') .'</a> ',
                _hr('This is the special link you can email to your customer. Using this link the customer can request a change on their website.')
            ),
        );

}

echo module_form::generate_fieldset($fieldset_data);

?>