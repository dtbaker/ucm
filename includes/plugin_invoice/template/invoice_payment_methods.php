<?php


if ( $invoice_data['total_amount_due'] > 0 ) {

	if ( module_invoice::is_automatic_paying_invoice( $invoice_id ) ) {
		// auto payment pending...
		?>
		<p align="center">
			<?php
			$template_print = module_template::get_template_by_key( 'invoice_payment_pending' );
			echo $template_print->content; ?>
		</p>
		<?php
	}


	// find all payment methods that are available for invoice payment.
	$payment_methods = handle_hook( 'get_payment_methods' );
	$methods_count   = count( $payment_methods );

	// work out the payment methods that are allowed for this invoice.
	$payment_methods_online  = array();
	$payment_methods_offline = array();
	$default_payment_method  = module_config::c( 'invoice_default_payment_method', 'paymethod_paypal' );
	foreach ( $payment_methods as $payment_method_id => $payment_method ) {
		if ( $payment_methods[ $payment_method_id ]->is_enabled() && $payment_methods[ $payment_method_id ]->is_allowed_for_invoice( $invoice_id ) ) {
			if ( $payment_methods[ $payment_method_id ]->is_method( 'online' ) ) {
				$payment_methods_online[] = array(
					'name'        => $payment_methods[ $payment_method_id ]->get_payment_method_name(),
					'key'         => $payment_methods[ $payment_method_id ]->module_name,
					'description' => $payment_methods[ $payment_method_id ]->get_invoice_payment_description( $invoice_id ),
				);
			} else {
				$payment_methods_offline[] = array(
					'name'        => $payment_methods[ $payment_method_id ]->get_payment_method_name(),
					'key'         => $payment_methods[ $payment_method_id ]->module_name,
					'description' => $payment_methods[ $payment_method_id ]->get_invoice_payment_description( $invoice_id ),
				);
			}
		}
	}

	ob_start();
	if ( count( $payment_methods_online ) ) {
		$template_print = module_template::get_template_by_key( 'invoice_payment_methods_online' );
		echo $template_print->content;
		if ( ! isset( $mode ) || $mode == 'html' ) { ?>

			<form action="<?php echo module_invoice::link_public_pay( $invoice_id ); ?>" method="post">
				<input type="hidden" name="payment" value="go">
				<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
				<table class="" cellpadding="0" cellspacing="0">
					<tbody>
					<tr>
						<th class="width1">
							<?php _e( 'Payment Method' ); ?>
						</th>
						<td>
							<?php
							// find out all the payment methods.
							$x = 1;
							//todo
							$default_payment_method = module_config::c( 'invoice_default_payment_method', 'paymethod_paypal' );
							foreach ( $payment_methods_online as $payment_methods_on ) {
								?>
								<input type="radio" name="payment_method"
								       value="<?php echo $payment_methods_on['key']; ?>"
								       id="paymethod<?php echo $x; ?>" <?php echo $default_payment_method == $payment_methods_on['key'] ? 'checked' : ''; ?>>
								<label
									for="paymethod<?php echo $x; ?>"><?php echo $payment_methods_on['name']; ?></label>
								<br/>
								<?php
								$x ++;
							}
							?>
						</td>
					</tr>
					<tr>
						<th>
							<?php _e( 'Payment Amount' ); ?>
						</th>
						<td>
							<?php
							if ( module_config::c( 'invoice_allow_payment_amount_adjustment', 1 ) ) {
								echo currency( '<input type="text" name="payment_amount" value="' . number_out( $invoice['total_amount_due'] ) . '" class="currency">', true, $invoice['currency_id'] );
							} else {
								echo dollar( $invoice['total_amount_due'], true, $invoice['currency_id'] );
								echo '<input type="hidden" name="payment_amount" value="' . number_out( $invoice['total_amount_due'] ) . '">';
							}
							?>
						</td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>
							<input type="submit" name="pay" value="<?php _e( 'Make Payment' ); ?>"
							       class="submit_button save_button">
						</td>
					</tr>
					</tbody>
				</table>
			</form>

			<?php
		} else {
			ob_start();
			?>
			<ul>
				<?php
				foreach ( $payment_methods_online as $payment_methods_on ) {
					?>
					<li>
						<strong><?php echo $payment_methods_on['name']; ?></strong><br/>
						<?php echo $payment_methods_on['description']; ?>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
			$template_print = module_template::get_template_by_key( 'invoice_payment_methods_online_footer' );
			$template_print->assign_values( array(
				'payment_methods' => ob_get_clean(),
				'link'            => module_invoice::link_public( $invoice_id ),
			) );
			echo $template_print->replace_content();
		}
	} // count( $payment_methods_online )
	$payment_methods_online_html = ob_get_clean();


	ob_start();
	if ( count( $payment_methods_offline ) ) {
		$template_print = module_template::get_template_by_key( 'invoice_payment_methods_offline' );
		echo $template_print->content;
		?>
		<ul>
			<?php
			foreach ( $payment_methods_offline as $payment_methods_of ) {
				?>
				<li>
					<strong><?php echo $payment_methods_of['name']; ?></strong><br/>
					<?php echo $payment_methods_of['description']; ?>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}
	$payment_methods_offline_html = ob_get_clean();


	$template_invoice_payment_methods = module_template::get_template_by_key( 'invoice_payment_methods' );
	$template_invoice_payment_methods->assign_values( array(
		'PAYMENT_METHODS_ONLINE'  => $payment_methods_online_html,
		'PAYMENT_METHODS_OFFLINE' => $payment_methods_offline_html,
	) );
	$template_invoice_payment_methods->assign_values( module_invoice::get_replace_fields( $invoice_id, $invoice_data ) );
	echo $template_invoice_payment_methods->replace_content();


} else { ?>

	<p align="center">
		<?php
		$template_print = module_template::get_template_by_key( 'invoice_payment_in_full' );
		echo $template_print->content; ?>
	</p>

	<?php
}
