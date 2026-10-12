<?php

/**
 * The PayPal payment page.
 *
 * The order already exists - it was created server side before this page was rendered -
 * so the buttons only have to hand its id to PayPal and tell us when the payer approves.
 * The capture, and the decision about whether to record anything, happen on the server.
 *
 * Included from module_paymethod_paypal::start_orders_v2_payment(), which is where these
 * values come from. Spelt out rather than assumed, because a theme can override this file
 * and whoever does that needs to know what it is handed.
 */

$paypal_order_id    = isset( $paypal_order_id ) ? (string) $paypal_order_id : '';
$paypal_client_id   = isset( $paypal_client_id ) ? (string) $paypal_client_id : '';
$paypal_capture_url = isset( $paypal_capture_url ) ? (string) $paypal_capture_url : '';
$paypal_cancel_url  = isset( $paypal_cancel_url ) ? (string) $paypal_cancel_url : '';
$description        = isset( $description ) ? (string) $description : '';
$payment_amount     = isset( $payment_amount ) ? $payment_amount : 0;
$currency_id        = isset( $currency_id ) ? $currency_id : false;
$currency_code      = isset( $currency_code ) ? (string) $currency_code : 'USD';

?>
<h1><?php echo htmlspecialchars( $description ); ?></h1>

<p><?php echo htmlspecialchars( _l( 'Amount: %s', dollar( $payment_amount, true, $currency_id ) ) ); ?></p>

<div id="ucm_paypal_buttons"></div>

<p id="ucm_paypal_working" style="display:none;"><?php _e( 'Confirming your payment, please wait...' ); ?></p>
<p id="ucm_paypal_error" class="alert alert-warning" style="display:none;" role="alert"></p>

<p>&nbsp;</p>
<p><a href="<?php echo htmlspecialchars( $paypal_cancel_url ); ?>"><?php _e( 'Cancel' ); ?></a></p>

<script
	src="https://www.paypal.com/sdk/js?client-id=<?php echo rawurlencode( $paypal_client_id ); ?>&currency=<?php echo rawurlencode( $currency_code ); ?>&intent=capture"></script>
<script>
	(function () {
		var orderId = <?php echo json_encode( $paypal_order_id ); ?>;
		var captureUrl = <?php echo json_encode( $paypal_capture_url ); ?>;
		var errorBox = document.getElementById('ucm_paypal_error');
		var working = document.getElementById('ucm_paypal_working');

		function showError(message) {
			working.style.display = 'none';
			errorBox.textContent = message;
			errorBox.style.display = '';
		}

		if (typeof paypal === 'undefined') {
			showError(<?php echo json_encode( _l( 'PayPal could not be loaded. Please check your connection and reload this page.' ) ); ?>);
			return;
		}

		paypal.Buttons({
			createOrder: function () {
				return orderId;
			},
			onApprove: function () {
				working.style.display = '';
				return fetch(captureUrl, {method: 'POST', credentials: 'omit'})
					.then(function (response) {
						return response.json();
					})
					.then(function (result) {
						if (result && result.ok && result.redirect) {
							window.location = result.redirect;
							return;
						}
						showError((result && result.message) || <?php echo json_encode( _l( 'We could not confirm this payment. Please contact us before trying again.' ) ); ?>);
					})
					.catch(function () {
						showError(<?php echo json_encode( _l( 'We could not confirm this payment. Please contact us before trying again.' ) ); ?>);
					});
			},
			onError: function () {
				showError(<?php echo json_encode( _l( 'Something went wrong at PayPal. No payment has been taken. Please try again.' ) ); ?>);
			}
		}).render('#ucm_paypal_buttons');
	})();
</script>
