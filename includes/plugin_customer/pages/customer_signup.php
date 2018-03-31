<?php
$module->page_title=_l('Customer Signup');
print_heading(array(
	'title' => 'Customer Signup Form',
	'type' => 'h2',
	'main' => true,
));

$roles = array();
foreach(module_security::get_roles() as $r){
    $roles[$r['security_role_id']] = $r['name'];
}


ob_start();
$invoice_templates = array();
$invoice_templates['customer_signup_thank_you_page'] = 1;
$invoice_templates['customer_signup_email_welcome'] = 1;
$invoice_templates['customer_signup_email_admin'] = 1;
$invoice_templates['customer_signup_form_wrapper'] = 1;
foreach($invoice_templates as $template_key => $tf){
	module_template::link_open_popup($template_key);
}
$template_html = ob_get_clean();

module_config::print_settings_form(array(
    array(
        'key'=>'customer_signup_allowed',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('Not allowed'),
            1 => _l('Allowed'),
        ),
        'description'=>'Enable customer signup form',
    ),
    array(
        'key'=>'customer_signup_always_new',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('Allow Update of Existing Customer Entries'),
            1 => _l('Always Create New Customer Entries'),
        ),
        'description'=>'Matching email address action',
        'help' =>'If a customer fills in this form and the email address already exists in the system then it can update the existing entry instead of creating a new customer entry. If updating existing entry then the new customer name will be applied, which could differ from existing company name. Set this option to "Always Create New Customer Entry" if you do not want a customer to be able to update their existing details.',
    ),
    array(
        'key'=>'customer_signup_password',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('No Password, Admin creates password for each new account (most secure option)'),
            1 => _l('Allow Primary User to pick a Password on Signup'),
            //2 => _l('Generate a Random Password'),
        ),
        'description'=>'User Passwords',
        'help' =>'If a user has a password they can login to the system and see all their details.',
    ),
    array(
        'key'=>'customer_signup_role',
        'default'=>0,
        'type'=>'select',
        'options' => $roles,
        'description'=>'User Role',
        'help' =>'Assign this User Role to all newly created contacts.',
    ),
    array(
        'key'=>'captcha_on_signup_form',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('No'),
            1 => _l('Yes'),
        ),
        'description'=>'Use CAPTCHA on signup form',
    ),
    array(
        'key'=>'customer_signup_redirect',
        'default'=>'',
        'type'=>'text',
        'description'=>'Redirect URL',
        'help'=>'If no redirect URL is set then the standard thank you message will be displayed. This can be changed in Settings > Templates > customer_signup_thank_you_page',
    ),
    array(
        'key'=>'customer_signup_admin_email',
        'default'=>module_config::c('admin_email_address'),
        'type'=>'text',
        'description'=>'What email address will signup notifications be sent to',
    ),
    array(
        'key'=>'customer_signup_contact_count',
        'default'=>1,
        'type'=>'text',
        'description'=>'Number of customer contacts',
    ),
    array(
        'key'=>'customer_signup_password',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('No'),
            1 => _l('Yes'),
        ),
        'description'=>'Ask for Password',
    ),
    array(
        'key'=>'customer_signup_on_login',
        'default'=>0,
        'type'=>'select',
        'options' => array(
            0 => _l('No'),
            1 => _l('Yes'),
        ),
        'description'=>'Show Signup Link on Login Page',
    ),
    array(
        'key'=>'customer_signup_on_login_url',
        'default'=>'',
        'type'=>'text',
        'description'=>'Signup Page URL',
        'help'=>'If you have placed the signup form on a page of your website, put that website URL here (e.g. http://www.yourwebsite.com/signup.html)',
    ),
    array(
        'key'=>'customer_signup_subscription_start',
        'default'=>'',
        'type'=>'text',
        'description'=>'Subscription Start Date Modification',
        'help'=>'How much to modify the subscription start date by (if they choose a Subscription during signup). Examples are: +5 days or +2 weeks',
    ),
	array(
		'type'=>'html',
		'description'=>'Templates',
		'html' => $template_html,
	),
));

$form_html = module_customer::get_customer_signup_form_html();
?>



<table width="100%">
    <tbody>
    <tr>
        <td valign="top" width="50%">
            <?php echo $form_html;?>
        </td>
        <td valign="top">
            <p>
                On the left is an example signup form - your customers can complete this form to input their details directly into your system - handy! <br> You can copy &amp; paste the HTML code onto your website.
                You can adjust this HTML to suit your needs - you can even remove all the fields except the required ones.
                As long as the field names are kept the same as they are now you will be fine.
            </p>
	        <p>
		        Or if you are happy with the default signup form then you can use it at this address here: <a href="<?php echo module_customer::link_public_signup_form();?>" target="_blank"><?php echo module_customer::link_public_signup_form();?></a>.
	        </p>
            <p>
                The best way to see how this works is to fill in the example form then have a look through your system to see how that information is inserted. Below is the information that is inserted into the system from this form:

            </p>
            <ul>
                <li>A Customer with the customer name from the form (new or existing customer, depending on the 'matching' setting above)</li>
                <li>A Customer Contact with the name, email and phone number from the form</li>
                <li>A new Website linked to the Customer, the "notes" will be added to this website entry.</li>
                <li>A new Job linked to the Customer for each "service" that is ticked in the form.</li>
                <li>Any files will be uploaded and linked to the Customer account.</li>
                <li>If there are any "custom" fields for the Customer or Website you will see them here in the form. These also get added to the system respectively. After adding new custom fields please come back here to regenerate the HTML code for your website.</li>
                <li>An email will be sent to the customer (see Settings > Template to configure this email)</li>
                <li>An email will be sent to the administrator with details of the signup (see Settings > Template to configure this email)</li>
                <li>A thank you message will be displayed after submitting the form (see Settings > Template to configure this message)</li>
            </ul>
            <p>
                Below is the HTML code for the form on the left, including some sample styles which you can adjust to match your own website.
            </p>
            <p>
                If you want to track different customer types please add the corresponding hidden input field to the page as well:<br>
	            <?php foreach(module_customer::get_customer_types() as $type){ ?>
                &lt;input type="hidden" name="customer[customer_type_id]" value="<?php echo $type['customer_type_id'];?>"&gt;  (for <?php echo htmlspecialchars($type['type_name_plural']);?>)<br/>
	            <?php } ?>
            </p>
	        <p>
		        If you want to submit this form via ajax using jQuery, set a field called 'via_ajax' and any errors will be returned via json array.
	        </p>
            <textarea rows="3" cols="3" style="width:90%; height: 500px"><?php echo htmlspecialchars($form_html);?></textarea>
        </td>
    </tr>
    </tbody>
</table>