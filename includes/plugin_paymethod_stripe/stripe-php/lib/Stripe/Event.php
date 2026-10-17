<?php

class Stripe_Event extends Stripe_ApiResource {
	public static function constructFrom( $values, $apiKey = null ) {
		$class = __CLASS__;

		return self::scopedConstructFrom( $class, $values, $apiKey );
	}

	public static function retrieve( $id, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedRetrieve( $class, $id, $apiKey );
	}

	public static function all( $params = null, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedAll( $class, $params, $apiKey );
	}
}
