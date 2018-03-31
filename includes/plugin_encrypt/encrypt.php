<?php

/**
 * This class intercepts all input forms and allows the user to choose which input forms are "encryptable".
 * The decryption happens client side with the sjcl library: https://github.com/bitwiseshiftleft/sjcl
 * The encryption happens client side as well.
 * This keeps all keys away from the web server.
 * No this isn't 100% secure but it's better than keeping keys stored in temporary sessions or cookie
 */

class module_encrypt extends module_base{

    public static $encrypt = array();
    public static $show_encrypt = false;
    public static $start_time = 0;
    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
    public static function get_class() {
        return __CLASS__;
    }
    public function init(){
        $this->module_name = "encrypt";
        $this->module_position = 1;
        $this->version = 2.270;
        // 2.2 - initial release
        // 2.21 - better support for new 'extra' fields
        // 2.22 - permissions.
        // 2.221 - typo.
        // 2.23 - a better "e" value in RSA to support easier server side public key encryption.
        // 2.24 - sidebar encryption in ticket extra fields.
        // 2.25 - link fix, no htmlspecialchars
        // 2.26 - save decrypted
        // 2.261 - remove console .log
        // 2.262 - fix for encrypted ticket submission + attachments
        // 2.263 - larger popup
        // 2.264 - 2013-04-07 - fix for special characters in encryption
        // 2.265 - 2013-11-23 - working on new ui
        // 2.266 - 2014-01-17 - compatibility update for custom data plugin
        // 2.267 - 2014-01-21 - updated js encryption library
        // 2.268 - 2014-10-13 - hook_filter_var for better theme support
        // 2.269 - 2015-04-12 - fix dollar sign in extra field bug
        // 2.270 - 2016-11-25 - modal popup fix



        module_config::register_js('encrypt','sjcl.js');
        module_config::register_js('encrypt','encrypt.js');
        module_config::register_css('encrypt','encrypt.css');
        hook_add('extra_fields_output','module_encrypt::extra_fields_output_callback');
    }

    public function process(){
        switch($_REQUEST['_process']){
            case 'save_encrypt':


                $data = $_REQUEST;
                if(isset($data['encrypt_key_id']) && !$data['encrypt_key_id']){
                    unset($data['encrypt_key_id']);
                }else if(isset($data['encrypt_key_id']) && $data['encrypt_key_id'] && isset($data['encrypt_field_id']) && $data['encrypt_field_id']){
                    // change our key over to this new one.
                    // only really used in dev. if someone did this irl they would loose all encryption.
                    //update_insert('encrypt_field_id',(int)$data['encrypt_field_id'],'encrypt_field',array('encrypt_key_id'=>$data['encrypt_key_id']));
                }
                $encrypt_id = update_insert('encrypt_id',(int)$_REQUEST['encrypt_id'],'encrypt',$data);
                echo json_encode(array('encrypt_id'=>$encrypt_id));
                exit;

                break;
            case 'save_encrypt_key':

                $encrypt_key_id = update_insert('encrypt_key_id',(int)$_REQUEST['encrypt_key_id'],'encrypt_key',$_REQUEST);
                // update the field info to say we are using this key.
                if(isset($_REQUEST['encrypt_field_id']) && (int)$_REQUEST['encrypt_field_id']>0){
                    update_insert('encrypt_field_id',(int)$_REQUEST['encrypt_field_id'],'encrypt_field',array('encrypt_key_id'=>$encrypt_key_id));
                }

                echo 'Saved!';
                exit;

                break;
            case 'encrypt_successful':

                $encrypt_field_id = isset($_REQUEST['encrypt_field_id']) ? (int)$_REQUEST['encrypt_field_id'] : 0;
                $encrypt_id = isset($_REQUEST['encrypt_id']) ? (int)$_REQUEST['encrypt_id'] : 0;
                if($encrypt_id && $encrypt_field_id){
                    module_encrypt::log_access($encrypt_id,'success');
                }

                echo 'Saved!';
                exit;

                break;
        }
    }

    public static function extra_fields_output_callback($callback,$html,$owner_table,$owner_id){
        return self::parse_html_input($owner_table,$html);
    }


    public static function encrypt_value($encrypt_key_id,$value){
        $encrypt_key = self::get_encrypt_key($encrypt_key_id);
        if($encrypt_key && $encrypt_key['public_key']){

            $dir = getcwd();
            chdir('includes/plugin_encrypt/phpseclib/');
            require_once('Crypt/RSA.php');
            chdir($dir);
// if encrypt fails return plain tet
            $rsa = new Crypt_RSA();
            //echo "Public Key: '".$encrypt_key['public_key']."'\n\n";
            $rsa->setPublicKeyFormat(CRYPT_RSA_PUBLIC_FORMAT_RAW);
            $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
            $public_key = array(
                'n' => new Math_BigInteger($encrypt_key['public_key'], 16),
                'e' => new Math_BigInteger($encrypt_key['e'], 16), // same as our javascript frontend.
            );
            $rsa->loadKey($public_key,CRYPT_RSA_PUBLIC_FORMAT_RAW);
            $ciphertext = $rsa->encrypt($value);
            return bin2hex($ciphertext);
        }
        return false;
    }


    public static function get_encrypt_key($encrypt_key_id){
        return get_single('encrypt_key','encrypt_key_id',$encrypt_key_id);
    }

    /**
     * This is where all the magic happens.
     * We take a page name (ie: the name of the 'plugin_extra' block like 'customer') and the html_data for that page (or block, or single input element)
     * We check each of the input elements (there may be more than 1 in the html data!) to see if any of them are marked for encryption (see encrypt_field table)
     *
     * @static
     * @param $page_name
     * @param $html_data
     */
    public static function parse_html_input($page_name,$html_data,$edit=true){

        // find out what field names are encrypted against thie 'page_name'
        $fields = get_multiple('encrypt_field',array('page_name'=>$page_name),'field_name');
        // convert any of these encrypted fields into a ****** [lock] block.
        // first look for all text input fields (could also be date input fields!)
        if(preg_match_all('#<input[^>]*type="text"[^>]*>#imsU',$html_data,$matches)){
	        foreach($matches[0] as $match){

	            if(preg_match('#display\s*:\s*none#',$match))continue;
                if(preg_match('#\[new\]#',$match))continue;

                // find the id or the name of each element.
                $encrypt_field_id = false;
                $encrypt_field_name = '';
                if(preg_match('#id="([^"]*)"#',$match,$name_matches)){
                    $encrypt_field_name = $name_matches[1];
                }else if(preg_match('#name="([^"]*)"#',$match,$name_matches)){
                    $encrypt_field_name = $name_matches[1];
                }

                if(isset($fields[$encrypt_field_name])){
                    // this is an encryptable field!
                    $encrypt_field_id = $fields[$encrypt_field_name]['encrypt_field_id'];
                }
                $existing_value = '';
                if(preg_match('#value="([^"]+)#',$match,$value_matches)){
                    if(strpos($value_matches[1],'encrypt:')===false){
                        $existing_value = $value_matches[1];
                    }
                }

                if($encrypt_field_id){
                    // find the "vlaue" of this input box.
                    // the value will be the id in the encrypt table.
                    // it should look something like this:
                    //    encrypt:123
                    $encrypt_id = 0; // new value
                    $replace = ''; // what we are going to replace this with.
                    if(preg_match('#value="encrypt:([^"]*)"#',$match,$value_matches)){
                        $encrypt_id = (int)$value_matches[1];
                        // remove the value from the input box and display our empty box on the page.
                        // rename our real box to a hidden input and give it an id so we can load our
                        // encrypt:123 value into it for submission.
                        $dummy_input = preg_replace('#value="[^"]+"#','value="*********"',$match);
                        $dummy_input = preg_replace('#id="[^"]+"#','',$dummy_input);
                        $dummy_input = preg_replace('#name="[^"]+"#','name="nothing" disabled="disabled"',$dummy_input);
                        $replace .= $dummy_input;
                        // add our hidden input back so normal form submits work.
                        // give our hidden input an id that we can work with, so we can update it's value after saving.
                        $hidden_input = preg_replace('#id="[^"]+"#','',$match);
                        $hidden_input = preg_replace('#type="text"#i','type="hidden" id="encrypt_hidden_'.$encrypt_field_id.'"',$hidden_input);
                        $replace .= $hidden_input;
                    }else{
                        // no encrypted value within this field yet.
                        // we leave the box as is, but give the user the option to encrypt whatever is in it.

                        //$dummy_input = preg_replace('#value="[^"]+"#','value=""',$match);
                        // remove any id from the box.
                        $dummy_input = preg_replace('#id="[^"]+"#','',$match);
                        $dummy_input = preg_replace('#type="text"#i','type="text" id="encrypt_hidden_'.$encrypt_field_id.'"',$dummy_input);
                        $replace .= $dummy_input;
                    }

                    if(!$edit){
                        $replace = '*********';
                    }


                    // put our hidden field in here.
                    if(self::can_i('view','Encrypts')){
                        $link = link_generate(array(
                            'raw' => true, // no htmlspecialchars
                            array(
                                'full' => true,
                                'text' => '<img src="'.full_link('includes/plugin_encrypt/images/'.(!$encrypt_id?'un':'').'lock.png').'" style="vertical-align:top;" border="0"> ',
                                'module' => 'encrypt',
                                'page' => 'encrypt_popup',
                                'arguments' => array(
                                    'encrypt_id'=>$encrypt_id,
                                    'encrypt_field_id'=>$encrypt_field_id,
                                    'page_name'=>$page_name,
                                    'value'=>base64_encode($existing_value), // incase a value already exists in the input box, we can pass it to the popup for encryption.
                                    'callback_id'=>'encrypt_hidden_'.$encrypt_field_id,
                                    'editable'=>$edit,
                                )
                            )
                        ));
                        $button = '<span class="encrypt_popup">'.popup_link($link,array(
                            'width' => 600,
                            'height' => 400,
                            'force' => true,
                            'hide_close' => true,
                                'title' => _l('Encryption'),
                        )).'</span>';
                        $replace .= ' &nbsp; '.$button;
                    }
                    $html_data = preg_replace('#'.preg_quote($match,'#').'#msU',str_replace('$','\$',$replace),$html_data);
                }else if(self::can_i('create','Encrypts') && $edit){
                    // no encrypt field for this one.
                    $element_id = '';
                    if(preg_match('#id="([^"]+)"#',$match,$id_matches)){
                        $element_id = $id_matches[1];
                    }

                    // give them an option to encrypt this field.
                    $link = link_generate(array(
                        'raw' => true, // no htmlspecialchars
                        array(
                            'full' => true,
                            'text' => '<img src="'.full_link('includes/plugin_encrypt/images/unlock.png').'" style="vertical-align:top;" border="0"> ',
                            'module' => 'encrypt',
                            'page' => 'encrypt_popup',
                            'arguments' => array(
                                'encrypt_field_name'=>$encrypt_field_name,
                                'page_name'=>$page_name,
                                'value'=>base64_encode($existing_value),
                                'callback_id'=>$element_id,
                                'editable' => $edit,
                            )
                        )
                    ));

                    $button = '<span class="encrypt_create">'.popup_link($link,array(
                        'width' => 600,
                        'height' => 300,
                        'force' => true,
                        'hide_close' => false,
                    )).'</span>';
                    $html_data = preg_replace('#'.preg_quote($match,'#').'#msU',str_replace('$','\$',$match).' &nbsp; '.$button,$html_data);
                }
            }
        }

        // now all textareas:

        // now all select fields:
	    return $html_data;
    }

    public function get_upgrade_sql(){
        $sql = '';
        $fields = get_fields('encrypt');
        if(!isset($fields['encrypt_field_id'])){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'encrypt` ADD  `encrypt_field_id` int(11) NOT NULL DEFAULT \'0\' AFTER  `encrypt_key_id`;';
        }
        $fields = get_fields('encrypt_key');
        if(!isset($fields['e'])){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'encrypt_key` ADD  `e` VARCHAR( 10 ) NOT NULL DEFAULT  \'65537\' AFTER  `public_key`;';
        }
        return $sql;
    }

    public function get_install_sql(){
        $sql = '';
        $sql .= 'CREATE TABLE `'._DB_PREFIX.'encrypt` (
  `encrypt_id` int(11) NOT NULL AUTO_INCREMENT,
  `encrypt_key_id` int(11) NOT NULL,
  `encrypt_field_id` int(11) NOT NULL DEFAULT \'0\',
  `data` blob NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`encrypt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';

        // verification data will contain an encrypted string saying "success"
        // if the password is correct this string will be decrypted.
        $sql .= 'CREATE TABLE `'._DB_PREFIX.'encrypt_key` (
  `encrypt_key_id` int(11) NOT NULL AUTO_INCREMENT,
  `encrypt_key_name` varchar(60) NOT NULL,
  `public_key` text NOT NULL,
  `e` VARCHAR( 10 ) NOT NULL DEFAULT  \'65537\',
  `secured_private_key` text NOT NULL,
  `verification_data` text NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`encrypt_key_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';

        $sql .= 'CREATE TABLE `'._DB_PREFIX.'encrypt_field` (
  `encrypt_field_id` int(11) NOT NULL AUTO_INCREMENT,
  `page_name` varchar(40) NOT NULL,
  `field_name` varchar(40) NOT NULL,
  `encrypt_key_id` INT NOT NULL,
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`encrypt_field_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';

        $sql .= 'CREATE TABLE `'._DB_PREFIX.'encrypt_access` (
  `encrypt_access_id` int(11) NOT NULL AUTO_INCREMENT,
  `encrypt_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT \'0\',
  `date_created` datetime NOT NULL,
  `date_updated` datetime NULL,
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `create_ip_address` varchar(15) NOT NULL,
  `update_ip_address` varchar(15) NULL,
  PRIMARY KEY (`encrypt_access_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;';

        return $sql;
    }

    public static function get_encrypt($encrypt_id) {
        $encrypt_id = (int)$encrypt_id;
        $encrypt = get_single('encrypt','encrypt_id',$encrypt_id);
        if(!$encrypt || $encrypt['encrypt_id'] != $encrypt_id){
            $encrypt = array(
                'encrypt_id' => 0,
                'encrypt_key_id' => 0,
                'data' => '',
            );
        }
        return $encrypt;
    }

    public static function get_encrypt_keys() {
        return get_multiple('encrypt_key',false,'encrypt_key_id');
    }

    public static function get_encrypt_field($encrypt_field_id) {
        $encrypt_field_id = (int)$encrypt_field_id;
        return get_single('encrypt_field','encrypt_field_id',$encrypt_field_id);
    }

    public static function log_access($encrypt_id, $access_type) {
        switch($access_type){
            case 'attempt':
                update_insert('encrypt_access_id',0,'encrypt_access',array(
                    'encrypt_id' => $encrypt_id,
                    'status' => 1
                ));
                break;
            case 'success':
                update_insert('encrypt_access_id',0,'encrypt_access',array(
                    'encrypt_id' => $encrypt_id,
                    'status' => 2
                ));
                break;
        }
    }

    public static function save_encrypt_value($encrypt_key_id, $raw_value, $page_name, $field_name, $existing_encrypt_id = 0) {
        // find matching field, if none exists create it.
        $encrypt_field = get_single('encrypt_field',array('page_name','field_name'),array($page_name,$field_name));
        $encrypt_field_id = false;
        if($encrypt_field && $encrypt_field['encrypt_field_id']){
            $encrypt_field_id = $encrypt_field['encrypt_field_id'];
        }
        if(!$encrypt_field_id){
            $encrypt_field_id = update_insert('encrypt_field_id',0,'encrypt_field',array(
                'page_name' => $page_name,
                'field_name' => $field_name,
                'encrypt_key_id' => $encrypt_key_id,
            ));
        }
        $encrypted_value = self::encrypt_value($encrypt_key_id,$raw_value);

        $encrypt_id = update_insert('encrypt_id',$existing_encrypt_id,'encrypt',array(
            'encrypt_key_id' => $encrypt_key_id,
            'data' => $encrypted_value,
            'encrypt_field_id' => $encrypt_field_id,
        ));

        return 'encrypt:'.$encrypt_id;

    }


}

