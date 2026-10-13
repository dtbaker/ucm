<?php

/**
 * Sends requests to the Authorize.Net gateways.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetRequest
 */
abstract class AuthorizeNetRequest {

	protected $_api_login;
	protected $_transaction_key;
	protected $_post_string;
	// Kept because module_paymethod_authorize sets it from a configuration value. It no
	// longer turns certificate verification off - see _sendRequest() below.
	public $VERIFY_PEER = true;
	protected $_sandbox = true;
	protected $_log_file = false;

	/**
	 * Set the _post_string
	 */
	abstract protected function _setPostString();

	/**
	 * Handle the response string
	 */
	abstract protected function _handleResponse( $string );

	/**
	 * Get the post url. We need this because until 5.3 you
	 * you could not access child constants in a parent class.
	 */
	abstract protected function _getPostUrl();

	/**
	 * Constructor.
	 *
	 * @param string $api_login_id The Merchant's API Login ID.
	 * @param string $transaction_key The Merchant's Transaction Key.
	 */
	public function __construct( $api_login_id = false, $transaction_key = false ) {
		$this->_api_login       = ( $api_login_id ? $api_login_id : ( defined( 'AUTHORIZENET_API_LOGIN_ID' ) ? AUTHORIZENET_API_LOGIN_ID : "" ) );
		$this->_transaction_key = ( $transaction_key ? $transaction_key : ( defined( 'AUTHORIZENET_TRANSACTION_KEY' ) ? AUTHORIZENET_TRANSACTION_KEY : "" ) );
		$this->_sandbox         = ( defined( 'AUTHORIZENET_SANDBOX' ) ? AUTHORIZENET_SANDBOX : true );
		$this->_log_file        = ( defined( 'AUTHORIZENET_LOG_FILE' ) ? AUTHORIZENET_LOG_FILE : false );
	}

	/**
	 * Alter the gateway url.
	 *
	 * @param bool $bool Use the Sandbox.
	 */
	public function setSandbox( $bool ) {
		$this->_sandbox = $bool;
	}

	/**
	 * Set a log file.
	 *
	 * @param string $filepath Path to log file.
	 */
	public function setLogFile( $filepath ) {
		$this->_log_file = $filepath;
	}

	/**
	 * Return the post string.
	 *
	 * @return string
	 */
	public function getPostString() {
		return $this->_post_string;
	}

	/**
	 * Posts the request to AuthorizeNet & returns response.
	 *
	 * @return AuthorizeNetARB_Response The response.
	 */
	protected function _sendRequest() {
		$this->_setPostString();
		$post_url     = $this->_getPostUrl();
		$curl_request = curl_init( $post_url );
		curl_setopt( $curl_request, CURLOPT_POSTFIELDS, $this->_post_string );
		curl_setopt( $curl_request, CURLOPT_HEADER, 0 );
		curl_setopt( $curl_request, CURLOPT_TIMEOUT, 45 );
		curl_setopt( $curl_request, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl_request, CURLOPT_SSL_VERIFYHOST, 2 );
		// This request carries a card number, so the certificate is always checked.
		//
		// The library shipped a pinned CA bundle at lib/ssl/cert.pem and, when that bundle
		// stopped matching, told the merchant to set VERIFY_PEER to false - which posts the
		// card number over a connection nothing has authenticated. The bundle is from 2012
		// and all but one of its roots have already expired, so it was going to stop
		// matching. The host's own certificate store is used instead, which is what every
		// other gateway in this application already does.
		curl_setopt( $curl_request, CURLOPT_SSL_VERIFYPEER, true );

		if ( preg_match( '/xml/', $post_url ) ) {
			curl_setopt( $curl_request, CURLOPT_HTTPHEADER, Array( "Content-Type: text/xml" ) );
		}

		$response = curl_exec( $curl_request );

		if ( $this->_log_file ) {

			if ( $curl_error = curl_error( $curl_request ) ) {
				file_put_contents( $this->_log_file, "----CURL ERROR----\n$curl_error\n\n", FILE_APPEND );
			}
			// Do not log requests that could contain CC info.
			// file_put_contents($this->_log_file, "----Request----\n{$this->_post_string}\n", FILE_APPEND);

			file_put_contents( $this->_log_file, "----Response----\n$response\n\n", FILE_APPEND );
		}
		curl_close( $curl_request );

		return $this->_handleResponse( $response );
	}

}