<?php

class Stripe_InvoiceItem extends Stripe_ApiResource {
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

	public static function create( $params = null, $apiKey = null ) {
		$class = __CLASS__;

		return self::_scopedCreate( $class, $params, $apiKey );
	}

	public function save() {
		$class = __CLASS__;

		return self::_scopedSave( $class );
	}

	public function delete( $params = null ) {
		$class = __CLASS__;

		return self::_scopedDelete( $class, $params );
	}
}
