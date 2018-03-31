<?php


class module_captcha extends module_base{

    // 2.2 - fix for ssl captcha
	private static $captcha_store = array();
    public static function can_i($actions,$name=false,$category=false,$module=false){
        if(!$module)$module=__CLASS__;
        return parent::can_i($actions,$name,$category,$module);
    }
	public static function get_class() {
        return __CLASS__;
    }
	public function init(){
        $this->version = 2.24;
		$this->module_name = "captcha";
		$this->module_position = 65;

        // 2.21 - 2013-04-12 - fix for auto login
        // 2.22 - 2014-12-19 - php fix
        // 2.23 - 2016-10-06 - new captcha
        // 2.24 - 2016-10-25 - new captcha fixes

	}

    public static function display_captcha_form(){

        $publickey = module_config::c('recaptcha_public_key','6Leym88SAAAAAK6APyjTzJwtwY0zAdcU8yIXwgvR');

	    if(module_config::c('recaptcha_version','2') == '2'){
		    require_once('inc/recaptchalib2.php');
	    }else{
		    require_once('inc/recaptchalib.php');
	    }
	    echo recaptcha_get_html($publickey, null, true);
    }
    public static function check_captcha_form(){

        $privatekey = module_config::c('recaptcha_private_key','6Leym88SAAAAANbBjtrjNfeu6NXDSCXGBSbKzqnN');

	    if(module_config::c('recaptcha_version','2') == '2'){
		    require_once('inc/recaptchalib2.php');

		    $response = null;
		    $reCaptcha = new ReCaptcha($privatekey);

		    if ( ! empty( $_POST["g-recaptcha-response"] ) ) {
			    $response = $reCaptcha->verifyResponse(
				    $_SERVER["REMOTE_ADDR"],
				    $_POST["g-recaptcha-response"]
			    );
			    if ($response != null && $response->success) {
			    	return true;
			    }
		    }

		    return false;

	    }else{
		    require_once('inc/recaptchalib.php');

		    $resp = recaptcha_check_answer ($privatekey,
			    $_SERVER["REMOTE_ADDR"],
			    isset($_POST["recaptcha_challenge_field"]) ? $_POST["recaptcha_challenge_field"] : '',
			    isset($_POST["recaptcha_response_field"]) ? $_POST["recaptcha_response_field"] : ''
		    );

		    if (!$resp->is_valid) {
			    // What happens when the CAPTCHA was entered incorrectly
			    return false;
		    } else {
			    return true;
		    }
	    }

    }

}