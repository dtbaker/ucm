<?php

class Stripe_Error extends Exception {
	// Declared rather than created on the fly: PHP 8.2 deprecated dynamic properties,
	// and on a host with display_errors on the notice printed onto the payment page.
	public $http_status;
	public $http_body;
	public $json_body;

	public function __construct( $message = null, $http_status = null, $http_body = null, $json_body = null ) {
		parent::__construct( $message );
		$this->http_status = $http_status;
		$this->http_body   = $http_body;
		$this->json_body   = $json_body;
	}

	public function getHttpStatus() {
		return $this->http_status;
	}

	public function getHttpBody() {
		return $this->http_body;
	}

	public function getJsonBody() {
		return $this->json_body;
	}
}
