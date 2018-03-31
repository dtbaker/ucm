<?php



?>
<style type="text/css">
#ucmsignup fieldset {
    padding: 0;
    margin-bottom: 10px;
    border:none;
    border-top: 1px solid #CCC;
}
#ucmsignup legend {
  padding: 0 2px;
  font-weight: bold;
}
#ucmsignup label {
  display: inline-block;
  line-height: 1.8;
  vertical-align: top;
}
#ucmsignup fieldset ol {
  margin: 0;
  padding: 0;
}
#ucmsignup fieldset li {
  list-style: none;
  padding: 5px;
  margin: 0;
}
#ucmsignup fieldset fieldset {
  border: none;
  margin: 3px 0 0;
}
#ucmsignup fieldset fieldset legend {
  padding: 0 0 5px;
  font-weight: normal;
}
#ucmsignup fieldset fieldset label {
  display: block;
  width: auto;
}
#ucmsignup em {
  font-weight: bold;
  font-style: normal;
  color: #f00;
}
#ucmsignup label {
  width: 120px; /* Width of labels */
}
#ucmsignup fieldset fieldset label {
  margin-left: 123px; /* Width plus 3 (html space) */
}
#ucmsignup .required{
    color:#CCC;
}
#ucmsignup input,
#ucmsignup select,
#ucmsignup textarea {
    background-color: #F8F8F8;
    border: 1px solid #CCC;
    font-family: inherit;
    font-size: 12px;
    padding: 1px;
    margin: 0;
}
</style>
<form action="<?php echo module_customer::link_public_signup();?>" enctype="multipart/form-data" method="post">
<div id="ucmsignup">
<fieldset>
<legend>Customer Information</legend>
	<input type="hidden" name="customer[customer_type_id]" value="0">
<ol>
    <li>
        <label for="customer[customer_name]">Company Name <span class="required">*</span></label>
        <input type="text" id="customer[customer_name]" name="customer[customer_name]" />
        <input type="hidden" name="customer[customer_name_required]" value="Customer Name" />
    </li>
    <?php
    $x=1;
    foreach(module_extra::get_defaults('customer') as $default){ ?>
    <li>
        <label for="customer_extra_<?php echo $x;?>"><?php echo htmlspecialchars($default['key']);?></label>
        <input type="text" id="customer_extra_<?php echo $x;?>" name="customer[extra][<?php echo htmlspecialchars($default['key']);?>]" />
    </li>
    <?php $x++;
    }
    $x=1;
    foreach(module_group::get_groups('customer') as $group_data){  ?>
    <li>
        <label for="customer_group_<?php echo $x;?>"><?php echo htmlspecialchars($group_data['name']);?></label>
        <input id="customer_group_<?php echo $x;?>" name="customer[group_ids][<?php echo htmlspecialchars($group_data['group_id']);?>]" type="checkbox" value="<?php echo htmlspecialchars($group_data['group_id']);?>" /> Yes
    </li>
    <?php $x++;
    } ?>
</ol>
</fieldset>
<?php for($x=0;$x<module_config::c('customer_signup_contact_count',1);$x++){ ?>
<fieldset>
<legend><?php echo $x==0 ? 'Primary ':'Additional ';?>Contact Information</legend>
<ol>
    <li>
        <label for="contact[name][<?=$x;?>]">First Name <span class="required">*</span></label>
        <input type="text" id="contact[name][<?=$x;?>]" name="contact[name][<?=$x;?>]" />
        <input type="hidden" name="contact[name][<?=$x;?>_required]" value="<?php echo $x==0 ? 'Primary ':'Additional ';?>Contact First Name" />
    </li>
    <li>
        <label for="contact[last_name][<?=$x;?>]">Last Name</label>
        <input type="text" id="contact[last_name][<?=$x;?>]" name="contact[last_name][<?=$x;?>]" />
    </li>
    <li>
        <label for="contact[email][<?=$x;?>]">Email Address <span class="required">*</span></label>
        <input type="text" id="contact[email][<?=$x;?>]" name="contact[email][<?=$x;?>]" />
        <input type="hidden" name="contact[email][<?=$x;?>_required]" value="<?php echo $x==0 ? 'Primary ':'Additional ';?>Contact Email Address" />
    </li>
    <li>
        <label for="contact[phone][<?=$x;?>]">Phone Number</label>
        <input type="text" id="contact[phone][<?=$x;?>]" name="contact[phone][<?=$x;?>]" />
    </li>
	<?php if($x==0 && module_config::c('customer_signup_password',0) == 1){ ?>
    <li>
        <label for="contact[password][<?=$x;?>]">Password</label>
        <input type="text" id="contact[password][<?=$x;?>]" name="contact[password][<?=$x;?>]" />
    </li>
	<?php } ?>
	<?php
    $xx=1;
    foreach(module_extra::get_defaults('user') as $default){ ?>
    <li>
        <label for="contact_extra_<?=$x;?>_<?php echo $xx;?>"><?php echo htmlspecialchars($default['key']);?></label>
        <input type="text" id="contact_extra_<?=$x;?>_<?php echo $xx;?>" name="contact[extra][<?=$x;?>][<?php echo htmlspecialchars($default['key']);?>]" />
    </li>
    <?php $xx++;
    }
    $xx=1;
    foreach(module_group::get_groups('user') as $group_data){  ?>
    <li>
        <label for="contact_group_<?=$x;?>_<?php echo $xx;?>"><?php echo htmlspecialchars($group_data['name']);?></label>
        <input id="contact_group_<?=$x;?>_<?php echo $xx;?>" name="contact[group_ids][<?=$x;?>][<?php echo htmlspecialchars($group_data['group_id']);?>]" type="checkbox" value="<?php echo htmlspecialchars($group_data['group_id']);?>" /> Yes
    </li>
    <?php $xx++;
    } ?>
</ol>
</fieldset>
<?php } ?>
<fieldset>
<legend>Address</legend>
<ol>
    <li>
        <label for="address[line_1]">Address (Line 1) </label>
        <input type="text" id="address[line_1]" name="address[line_1]" />
    </li>
    <li>
        <label for="address[line_2]">Address (Line 2) </label>
        <input type="text" id="address[line_2]" name="address[line_2]" />
    </li>
    <li>
        <label for="address[suburb]">Suburb</label>
        <input type="text" id="address[suburb]" name="address[suburb]" />
    </li>
    <li>
        <label for="address[state]">State</label>
        <input type="text" id="address[state]" name="address[state]" />
    </li>
    <li>
        <label for="address[region]">Region</label>
        <input type="text" id="address[region]" name="address[region]" />
    </li>
    <li>
        <label for="address[post_code]">Post Code</label>
        <input type="text" id="address[post_code]" name="address[post_code]" />
    </li>
    <li>
        <label for="address[country]">Country</label>
        <input type="text" id="address[country]" name="address[country]" />
    </li>
</ol>
</fieldset>
<fieldset>
<legend>Project Details</legend>
<ol>
    <li>
        <label for="website[url]">Website Address</label>
        <input type="text" id="website[url]" name="website[url]" />
    </li>
    <li>
        <fieldset>
            <legend>Which of the below services do you require? <span class="required">*</span></legend>
            <?php
            $has_type = false;
            foreach(module_job::get_types() as $type_id => $type){ $has_type = true; ?>
            <label><input type="checkbox" name="job[type][<?php echo htmlspecialchars($type_id);?>]" value="<?php echo htmlspecialchars($type);?>" /> <?php echo htmlspecialchars($type);?></label>
            <?php }
            if($has_type){ ?>
            <input type="hidden" name="job[type_required]" value="Select a Service" />
            <?php } ?>
        </fieldset>
    </li>
	<?php
    $x=1;
    foreach(module_extra::get_defaults('job') as $default){ ?>
    <li>
        <label for="job_extra_<?php echo $x;?>"><?php echo htmlspecialchars($default['key']);?></label>
        <input type="text" id="job_extra_<?php echo $x;?>" name="job[extra][<?php echo htmlspecialchars($default['key']);?>]" />
    </li>
    <?php $x++;
    }
	?>
    <li>
        <fieldset>
            <legend>Please upload any attachments for this project below:</legend>
            <label><input type="file" name="customerfiles[]" value=""></label>
            <label><input type="file" name="customerfiles[]" value=""></label>
            <label><input type="file" name="customerfiles[]" value=""></label>
            <label><input type="file" name="customerfiles[]" value=""></label>
            <!-- add more files here by simply duplicating a line above -->
        </fieldset>
    </li>
    <?php
    $x=1;
    foreach(module_extra::get_defaults('website') as $default){ ?>
    <li>
        <label for="website_extra_<?php echo $x;?>"><?php echo htmlspecialchars($default['key']);?></label>
        <input type="text" id="website_extra_<?php echo $x;?>" name="website[extra][<?php echo htmlspecialchars($default['key']);?>]" />
    </li>
    <?php $x++;
    }
    $x=1;
    foreach(module_group::get_groups('website') as $group_data){  ?>
    <li>
        <label for="website_group_<?php echo $x;?>"><?php echo htmlspecialchars($group_data['name']);?></label>
        <input type="text" id="website_group_<?php echo $x;?>" name="website[group_ids][<?php echo htmlspecialchars($group_data['group_id']);?>]" type="checkbox" value="<?php echo htmlspecialchars($group_data['group_id']);?>" /> Yes
    </li>
    <?php $x++;
    }

    /*$x=1;
    foreach(module_extra::get_defaults('job') as $default){ ?>
    <li>
        <label for="job_extra_<?php echo $x;?>"><?php echo htmlspecialchars($default['key']);?></label>
        <input id="job_extra_<?php echo $x;?>" name="job[extra][<?php echo htmlspecialchars($default['key']);?>]" />
    </li>
    <?php $x++;
    }*/ ?>
    <li>
        <label for="website[notes]">Comments</label>
        <textarea id="website[notes]" name="website[notes]" rows="7" cols="25"></textarea>
    </li>
</ol>
</fieldset>
<?php if(class_exists('module_subscription',false)){ ?>
<fieldset>
<legend>Subscription Details</legend>
    <input type="hidden" name="subscription[for]" value="website"> <!-- values here are website or customer -->
<ol>
    <li>
        <fieldset>
            <legend>Which of the below subscriptions would you like?</legend>
            <?php
            $sorted_subscriptions = module_subscription::get_subscriptions();
            foreach($sorted_subscriptions as $subscription){
            ?>
            <label for="subscription_<?php echo $subscription['subscription_id'];?>">
                <input type="checkbox" name="subscription[subscriptions][<?php echo $subscription['subscription_id'];?>]" value="1" id="subscription_<?php echo $subscription['subscription_id'];?>">
            <?php echo htmlspecialchars($subscription['name']);?> - <?php echo dollar($subscription['amount']);
            if(!$subscription['days']&&!$subscription['months']&&!$subscription['years']){
                //echo _l('Once off');
            }else{
                $bits = array();
                if($subscription['days']>0){
                    $bits[] = _l('%s days',$subscription['days']);
                }
                if($subscription['months']>0){
                    $bits[] = _l('%s months',$subscription['months']);
                }
                if($subscription['years']>0){
                    $bits[] = _l('%s years',$subscription['years']);
                }
                echo ' ';
                echo _l('Every %s',implode(', ',$bits));
            }
            ?>
            </label>
            <?php } ?>
        </fieldset>
    </li>
</ol>
</fieldset>
<?php } ?>
    <?php if(module_config::c('captcha_on_signup_form',0)){ ?>
    <fieldset>
        <legend>Spam Prevention</legend>
        <?php module_captcha::display_captcha_form(); ?>
    </fieldset>
    <?php } ?>
    <p><input type="submit" value="Signup Now" /></p>
</div>
</form>
