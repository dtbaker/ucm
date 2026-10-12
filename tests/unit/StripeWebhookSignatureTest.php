<?php

use PHPUnit\Framework\TestCase;

/**
 * Stripe webhook signature verification.
 *
 * This exercises the SDK rather than UCM code, deliberately. The thing that goes wrong
 * with signature verification is never the HMAC - it is reading the wrong header, signing
 * the wrong string, or an SDK upgrade moving the call. Pinning the exact shape UCM depends
 * on is what catches that.
 *
 * The header UCM reads is $_SERVER['HTTP_STRIPE_SIGNATURE'], which is how PHP presents
 * the "Stripe-Signature" header. The signed string is "{timestamp}.{raw body}" - the raw
 * body, which is why the handler reads php://input and never $_POST.
 */
class StripeWebhookSignatureTest extends TestCase
{
	private const SECRET = 'whsec_test_secret_value';

	protected function setUp(): void {
		if ( ! class_exists( '\Stripe\Webhook' ) ) {
			$this->markTestSkipped( 'stripe/stripe-php is not installed; run composer install.' );
		}
	}

	private function body() {
		return '{"id":"evt_test_1","object":"event","type":"checkout.session.completed","data":{"object":{"id":"cs_test_1","object":"checkout.session"}}}';
	}

	private function sign( $body, $timestamp = null, $secret = self::SECRET ) {
		$timestamp = $timestamp === null ? time() : $timestamp;
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );

		return 't=' . $timestamp . ',v1=' . $signature;
	}

	public function test_a_correctly_signed_delivery_is_accepted() {
		$body  = $this->body();
		$event = \Stripe\Webhook::constructEvent( $body, $this->sign( $body ), self::SECRET );

		$this->assertSame( 'checkout.session.completed', $event->type );
		$this->assertSame( 'cs_test_1', $event->data->object->id );
	}

	public function test_a_tampered_body_is_rejected() {
		$body      = $this->body();
		$signature = $this->sign( $body );

		$this->expectException( \Stripe\Exception\SignatureVerificationException::class );
		\Stripe\Webhook::constructEvent( str_replace( 'cs_test_1', 'cs_test_2', $body ), $signature, self::SECRET );
	}

	public function test_a_signature_from_a_different_secret_is_rejected() {
		$body = $this->body();

		$this->expectException( \Stripe\Exception\SignatureVerificationException::class );
		\Stripe\Webhook::constructEvent( $body, $this->sign( $body, null, 'whsec_someone_elses_secret' ), self::SECRET );
	}

	public function test_a_missing_signature_header_is_rejected() {
		$this->expectException( \Stripe\Exception\SignatureVerificationException::class );
		\Stripe\Webhook::constructEvent( $this->body(), '', self::SECRET );
	}

	/**
	 * Replay protection. Stripe's default tolerance is five minutes.
	 */
	public function test_a_signature_from_an_hour_ago_is_rejected() {
		$body = $this->body();

		$this->expectException( \Stripe\Exception\SignatureVerificationException::class );
		\Stripe\Webhook::constructEvent( $body, $this->sign( $body, time() - 3600 ), self::SECRET );
	}

	/**
	 * v0 is the scheme Stripe uses for test-mode-only signatures from the CLI. Accepting
	 * it on a live endpoint would be a downgrade, and the SDK must not.
	 */
	public function test_a_v0_only_signature_is_rejected() {
		$body      = $this->body();
		$timestamp = time();
		$signature = 't=' . $timestamp . ',v0=' . hash_hmac( 'sha256', $timestamp . '.' . $body, self::SECRET );

		$this->expectException( \Stripe\Exception\SignatureVerificationException::class );
		\Stripe\Webhook::constructEvent( $body, $signature, self::SECRET );
	}
}
