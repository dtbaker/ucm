<?php

class Stripe_Account extends Stripe_SingletonApiResource {
	public static function constructFrom( $values, $apiKey = null ) {
		$class = __CLASS__;

		return self::scopedConstructFrom( $class, $values, $apiKey );
	}

	public static function retrieve( $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedSingletonRetrieve( $class, $apiKey );
	}
}
