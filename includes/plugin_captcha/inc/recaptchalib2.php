<?php

function recaptcha_get_html ($pubkey, $error = null, $use_ssl = false)
{
	if ($pubkey == null || $pubkey == '') {
		die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
	}

	ob_start();
	?>
	<style type="text/css">
		.g-recaptcha > div{
			margin:0 auto;
		}
	</style>
	<script src='https://www.google.com/recaptcha/api.js'></script>
	<div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($pubkey);?>" data-theme="<?php echo htmlspecialchars(module_config::c('recaptcha_theme','light'));?>"></div>
	<?php
	return ob_get_clean();
}



/////////////////////////
/////////////////////////
/////////////////////////
/////////////////////////
/// Version 2 stuff below
/////////////////////////
/////////////////////////
/////////////////////////
/////////////////////////
/////////////////////////


/**
 * A ReCaptchaResponse2 is returned from checkAnswer().
 */
class ReCaptchaResponse2
{
	public $success;
	public $errorCodes;
}
class ReCaptcha
{
	private static $_signupUrl = "https://www.google.com/recaptcha/admin";
	private static $_siteVerifyUrl =
		"https://www.google.com/recaptcha/api/siteverify?";
	private $_secret;
	private static $_version = "php_1.0";
	/**
	 * Constructor.
	 *
	 * @param string $secret shared secret between site and ReCAPTCHA server.
	 */
	public function __construct($secret)
	{
		if ($secret == null || $secret == "") {
			die("To use reCAPTCHA you must get an API key from <a href='"
			    . self::$_signupUrl . "'>" . self::$_signupUrl . "</a>");
		}
		$this->_secret=$secret;
	}
	/**
	 * Encodes the given data into a query string format.
	 *
	 * @param array $data array of string elements to be encoded.
	 *
	 * @return string - encoded request.
	 */
	private function _encodeQS($data)
	{
		$req = "";
		foreach ($data as $key => $value) {
			$req .= $key . '=' . urlencode(stripslashes($value)) . '&';
		}
		// Cut the last '&'
		$req=substr($req, 0, strlen($req)-1);
		return $req;
	}
	/**
	 * Submits an HTTP GET to a reCAPTCHA server.
	 *
	 * @param string $path url path to recaptcha server.
	 * @param array  $data array of parameters to be sent.
	 *
	 * @return array response
	 */
	private function _submitHTTPGet($path, $data)
	{
		$req = $this->_encodeQS($data);

		$ch = curl_init($path . $req);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		$response = curl_exec($ch);

		//$response = file_get_contents($path . $req);
		return $response;
	}
	/**
	 * Calls the reCAPTCHA siteverify API to verify whether the user passes
	 * CAPTCHA test.
	 *
	 * @param string $remoteIp   IP address of end user.
	 * @param string $response   response string from recaptcha verification.
	 *
	 * @return ReCaptchaResponse2
	 */
	public function verifyResponse($remoteIp, $response)
	{
		// Discard empty solution submissions
		if ($response == null || strlen($response) == 0) {
			$recaptchaResponse2 = new ReCaptchaResponse2();
			$recaptchaResponse2->success = false;
			$recaptchaResponse2->errorCodes = 'missing-input';
			return $recaptchaResponse2;
		}
		$getResponse = $this->_submitHttpGet(
			self::$_siteVerifyUrl,
			array (
				'secret' => $this->_secret,
				'remoteip' => $remoteIp,
				'v' => self::$_version,
				'response' => $response
			)
		);
		$answers = json_decode($getResponse, true);
		$recaptchaResponse2 = new ReCaptchaResponse2();
		if (trim($answers ['success']) == true) {
			$recaptchaResponse2->success = true;
		} else {
			$recaptchaResponse2->success = false;
			$recaptchaResponse2->errorCodes = $answers [error-codes];
		}
		return $recaptchaResponse2;
	}
}

