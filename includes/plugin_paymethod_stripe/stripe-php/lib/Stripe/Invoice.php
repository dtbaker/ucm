<?php

class Stripe_Invoice extends Stripe_ApiResource {
	public static function constructFrom( $values, $apiKey = null ) {
		$class = __CLASS__;

		return self::scopedConstructFrom( $class, $values, $apiKey );
	}

	public static function create( $params = null, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedCreate( $class, $params, $apiKey );
	}

	public static function retrieve( $id, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedRetrieve( $class, $id, $apiKey );
	}

	public static function all( $params = null, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedAll( $class, $params, $apiKey );
	}

	public static function upcoming( $params = null, $apiKey = null ) {
		$requestor = new Stripe_ApiRequestor( $apiKey );
		$url       = self::classUrl( __CLASS__ ) . '/upcoming';
		list( $response, $apiKey ) = $requestor->request( 'get', $url, $params );

		return Stripe_Util::convertToStripeObject( $response, $apiKey );
	}

	public function save() {
		$class = __CLASS__;

		return self::_scopedSave( $class );
	}

	public function pay() {
		$requestor = new Stripe_ApiRequestor( $this->_apiKey );
		$url       = $this->instanceUrl() . '/pay';
		list( $response, $apiKey ) = $requestor->request( 'post', $url );
		$this->refreshFrom( $response, $apiKey );

		return $this;
	}
}
