<?php
if(!isset($options) && isset($_REQUEST['options'])){
    $options = unserialize(base64_decode($_REQUEST['options']));
}
if(!isset($options)){
    $options=array();
}
$options = module_email::get_email_compose_options($options);
extract($options);
?>

<form action="" method="post" id="template_change_form">
    <input type="hidden" name="template_name" value="" id="template_name_change">
</form>

<form action="<?php echo full_link('email.email_compose_basic');?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="_process" value="send_email">
    <input type="hidden" name="options" value="<?php echo base64_encode(serialize($options));?>">
    <?php module_form::print_form_auth();

    $fieldset_data = array(
	    'title' => isset($title) ? $title : false,
	    'heading' => isset($heading) ? $heading : false,
	    'class' => 'tableclass tableclass_form tableclass_full',
	    'elements' => array(
	    ),
    );
    if(isset($find_other_templates) && strlen($find_other_templates) && isset($current_template) && strlen($current_template)) {
	    $other_templates = array();
	    foreach ( module_template::get_templates() as $possible_template ) {
		    if ( strpos( $possible_template['template_key'], $find_other_templates ) !== false ) {
			    // found another one!
			    $other_templates[ $possible_template['template_key'] ] = '(' . $possible_template['template_key'] . ') ' . $possible_template['description'];
		    }
	    }
	    if ( count( $other_templates ) > 1 ) {
		    $fieldset_data['elements'][] = array(
			    'title' => _l( 'Email Template:' ),
			    'field' => function () use ( $other_templates, $current_template ) {
				    ?>
                    <select name="template_name" id="template_name">
					    <?php foreach ( $other_templates as $other_template_key => $other_template_name ) { ?>
                            <option value="<?php echo htmlspecialchars( $other_template_key ); ?>"<?php echo $current_template == $other_template_key ? ' selected' : ''; ?>><?php echo htmlspecialchars( $other_template_name ); ?></option>
					    <?php } ?>
                    </select>
                    <script type="text/javascript">
                        $(function () {
                            $('#template_name').change(function () {
                                $('#template_name_change').val($(this).val());
                                $('#template_change_form')[0].submit();
                            });
                        });
                    </script>
				    <?php
				    _h( 'Changing this will clear any message below. Create new templates in Settings > Templates. Name them email_template_SOMETHING and they will appear in this list.' );
			    },
		    );
	    }
    }

    $fieldset_data['elements'][] = array(
	    'title' => _l('From:'),
	    'field' => function() use ($options, $headers, $from_name, $from_email){

//		    $from_name = !empty($headers['FromName']) ? $headers['FromName'] : $options['from_name'];
//		    $from_email = !empty($headers['FromEmail']) ? $headers['FromEmail'] : $options['from_email'];
            ?>
                <div id="email_from_view">
				    <?php echo htmlspecialchars($from_name . ' <'.$from_email.'>'); ?> <a href="#" onclick="$(this).parent().hide(); $('#email_from_edit').show(); return false;"><?php _e('edit');?></a>
                </div>
                <div id="email_from_edit" style="display:none;">
                    <input type="text" name="from_name" value="<?php echo htmlspecialchars($from_name);?>">
                    &lt;<input type="text" name="from_email" value="<?php echo htmlspecialchars($from_email);?>">&gt
                </div>
            <?php
	    },
    );

    $fieldset_data['elements'][] = array(
	    'title' => _l('To:'),
	    'field' => function() use ($to, $options, $headers, $to_select){
            // drop down with various options, or a blank inbox box with an email address.
            if(count($to) > 1){
                ?>
                <select name="custom_to">
                    <!-- <option value=""><?php _e('Please select');?></option> -->
                    <?php foreach($to as $t){ ?>
                        <option value="<?php echo htmlspecialchars($t['email']);?>||<?php echo htmlspecialchars($t['name']) . (isset($t['last_name']) && module_config::c('email_to_full_name',1)?' '.htmlspecialchars($t['last_name']):'');?>||<?php echo htmlspecialchars($t['user_id']);?>"<?php if(isset($to_select)&&$to_select==$t['email'])echo ' selected';?>><?php echo htmlspecialchars($t['email']) . ' - ' . htmlspecialchars($t['name']);?></option>
                    <?php } ?>
                </select>
            <?php }else if(count($to)){
                $t = array_shift($to);
                ?>

                <?php echo htmlspecialchars($t['email']) . ' - ' . htmlspecialchars($t['name']).(isset($t['last_name']) && module_config::c('email_to_full_name',1)?' '.htmlspecialchars($t['last_name']):'');?>

            <?php }else{ ?>
                <div id="email_to_holder">
                    <div class="dynamic_block">
                        <input type="email" name="custom_to[]" value="<?php echo isset($to_select) ? htmlspecialchars($to_select) : '';?>">
                        <a href="#" class="add_addit" onclick="return seladd(this);">+</a> <a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
                    </div>
                </div>
                <script type="text/javascript"> set_add_del("email_to_holder"); </script>
            <?php }
	    },
    );

    if (count($to) > 1) {
	    $fieldset_data['elements'][] = array(
		    'title' => _l( 'CC:' ),
		    'field' => function () use ( $to, $options, $headers ) {
			    // drop down with various options, or a blank inbox box with an email address.
			    ?>
                <div id="email_cc_holder">
                    <div class="dynamic_block">
                        <select name="custom_cc[]">
                            <option value=""><?php _e('N/A');?></option>
						    <?php foreach($to as $t){ ?>
                                <option value="<?php echo htmlspecialchars($t['email']);?>||<?php echo htmlspecialchars($t['name']) . (isset($t['last_name']) && module_config::c('email_to_full_name',1)?' '.htmlspecialchars($t['last_name']):'');?>||<?php echo htmlspecialchars($t['user_id']);?>"><?php echo htmlspecialchars($t['email']) . ' - ' . htmlspecialchars($t['name']);?></option>
						    <?php } ?>
                        </select>
                        <a href="#" class="add_addit" onclick="return seladd(this);">+</a> <a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
                    </div>
                </div>
                <script type="text/javascript"> set_add_del("email_cc_holder"); </script>
                <?php
		    },
	    );
    }

    $fieldset_data['elements'][] = array(
	    'title' => _l( 'BCC:' ),
	    'field' => array(
            'type'=>'text',
            'name'=>'bcc',
            'value' => $bcc,
        )
    );
    $fieldset_data['elements'][] = array(
	    'title' => _l( 'Subject:' ),
	    'field' => array(
            'type'=>'text',
            'name'=>'subject',
            'value' => $subject,
        )
    );

    $fieldset_data['elements'][] = array(
	    'title' => _l( 'Attachment:' ),
	    'field' => function () use ( $attachments, $options, $headers ) {
		    // drop down with various options, or a blank inbox box with an email address.
		    // uploado an attachment here, or generate one from a pdf on send.
		    // (eg: sending an invoice pdf)
		    foreach($attachments as $attachment){
			    if($attachment['preview']){
				    echo '<a href="'.$attachment['preview'].'">';
			    }
			    echo $attachment['name'];
			    if($attachment['preview']){
				    echo '</a>';
			    }
			    echo '<br/>';
		    }
		    ?>
            <div id="email_attach_holder">
                <div class="dynamic_block">
                    <input type="file" name="manual_attachment[]" value="">
                    <a href="#" class="add_addit" onclick="return seladd(this);">+</a>
                    <a href="#" class="remove_addit" onclick="return selrem(this);">-</a>
                </div>
            </div>
            <script type="text/javascript">
                set_add_del('email_attach_holder');
            </script>
		    <?php
	    },
    );

    $fieldset_data['elements'][] = array(
	    'title' => _l('Message:'),
	    'field' => array(
		    'type' => 'wysiwyg',
		    'name' => 'html_content',
		    'options' => array(
			    'inline' => false,
		    ),
		    'style' => 'height: 400px;',
		    'value' => $content
	    ),
    );

    echo module_form::generate_fieldset($fieldset_data);



    $form_actions = array(
        'class' => 'action_bar action_bar_center action_bar_single',
        'elements' => array(
            array(
                'type' => 'save_button',
                'name' => 'send',
                'value' => _l('Send email'),
            ),
        ),
    );
    if($cancel_url){
        $form_actions['elements'][] = array(
            'type' => 'button',
            'name' => 'cancel',
            'value' => _l('Cancel'),
            'class' => 'submit_button',
            'onclick' => "window.location.href='".htmlspecialchars($cancel_url)."';",
        );
    }
    echo module_form::generate_form_actions($form_actions);
?>


</form>

