<?php if ( module_config::c( 'ticket_allow_priority', 0 ) || $ticket['priority'] == _TICKET_PRIORITY_STATUS_ID ) {

	$responsive_summary = array();
	ob_start();
	?>
	<table class="tableclass tableclass_form tableclass_full">
		<tbody>
		<tr>
			<td>
				<?php
				if ( $ticket['priority'] != _TICKET_PRIORITY_STATUS_ID ) {
					echo '<p>';
					_e( "Current ticket position is: <strong>%s of %s</strong>.", ordinal( $ticket['position'] ), $ticket['total_pending'] );
					echo '<br/>';
					$x = module_ticket::get_ticket_count( $ticket['faq_product_id'] );
					_e( 'Priority Support will change this to: <strong>%s of %s</strong>.', ordinal( $x['priority'] + 1 ), $ticket['total_pending'] );
					echo '<br/>';
					_e( 'This means your question will be answered faster.' );
					echo '</p>';
				}
				if ( $ticket['invoice_id'] ) {
					$invoice_data = module_invoice::get_invoice( $ticket['invoice_id'] );
					if ( ! $invoice_data || $invoice_data['invoice_id'] != $ticket['invoice_id'] ) {
						// ticket invoice has been deleted.
						// unlink it from this ticket.
						if ( $ticket['ticket_id'] ) {
							update_insert( 'ticket_id', $ticket['ticket_id'], 'ticket', array( 'invoice_id' => 0 ) );
						}
						echo 'invoice removed... please refresh';
					} else if ( $invoice_data['total_amount_due'] > 0 ) {
						$responsive_summary[] = _l( "Please pay <strong>%s</strong>.", dollar( $invoice_data['total_amount_due'], true, $invoice_data['currency_id'] ) );
						echo '<p>';
						echo _l( "Please pay <strong>%s</strong> to receive Priority Support. To make payment please click the button below.", dollar( $invoice_data['total_amount_due'], true, $invoice_data['currency_id'] ) );
						echo '</p>';
						echo '<p align="center">';
						echo '<a href="' . module_invoice::link_public( $ticket['invoice_id'] ) . '" target="_blank" class="uibutton small_button">' . _l( 'Pay Now' ) . '</a>';
						echo '</p>';
					} else {
						$responsive_summary[] = '<strong>' . _l( "This is a Priotity Ticket" ) . '</strong>';
						echo '<p>';
						_e( "Thank you for purchasing Priority Support. We will answer your question shortly." );
						echo '</p>';
						echo '<p align="center">';
						if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
							echo '<a href="' . module_invoice::link_open( $ticket['invoice_id'] ) . '" target="_blank">' . _l( 'View Invoice' ) . '</a>';
						} else {
							echo '<a href="' . module_invoice::link_public( $ticket['invoice_id'] ) . '" target="_blank">' . _l( 'View Invoice' ) . '</a>';
						}
						echo '</p>';
					}
				} else {
					echo '<p>';
					echo _l( "Priority Support costs <strong>%s</strong>. To make payment please click the button below.", dollar( module_config::c( 'ticket_priority_cost', 10 ), true, module_config::c( 'ticket_priority_currency', 1 ) ) );
					echo '</p>';
					echo '<p align="center">';
					echo '<input type="submit" name="generate_priority_invoice" value="' . _l( 'Pay Now' ) . '" class="submit_button small_button">';
					echo '</p>';
				}

				?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php

	$fieldset_data = array(
		'heading'         => array(
			'title'      => _l( 'Priority Support' ),
			'type'       => 'h3',
			'responsive' => array(
				'summary' => implode( ', ', $responsive_summary ),
			),
		),
		'elements_before' => ob_get_clean(),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );
}
