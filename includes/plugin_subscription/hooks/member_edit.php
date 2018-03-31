<?php
if ( ! $owner_table ) {
	die( 'hook must define owner table' );
}

$responsive_summary = array();

ob_start();
?>

	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
		<tbody>
		<tr>
			<th class="width1">
				<?php echo _l( 'Subscriptions' ); ?>
			</th>
			<td>
				<?php if ( module_subscription::can_i( 'edit', 'Subscriptions' ) ) { ?>
					<input type="hidden" name="member_subscriptions_save" value="1">
					<input type="hidden" name="subscription_add_payment" value="" id="subscription_add_payment">
					<input type="hidden" name="subscription_add_payment_date" value="" id="subscription_add_payment_date">
					<input type="hidden" name="subscription_add_payment_amount" value="" id="subscription_add_payment_amount">
					<?php
				}

				if ( module_config::c( 'subscription_allow_credit', 1 ) ) {
					$subscription_credit = module_subscription::get_available_credit( $owner_table, $member_id );
				}

				global $members_subscriptions;
				$members_subscriptions = module_subscription::get_subscriptions_by( $owner_table, $member_id );

				$sorted_subscriptions = module_subscription::get_subscriptions();
				if ( module_config::c( 'subscription_sort_selected_at_top', '0' ) ) {
					if ( ! function_exists( 'sort_subscriptions' ) ) {
						function sort_subscriptions( $a, $b ) {
							global $members_subscriptions;
							if ( isset( $members_subscriptions[ $a['subscription_id'] ] ) && isset( $members_subscriptions[ $b['subscription_id'] ] ) ) {
								return 0;
							} else if ( isset( $members_subscriptions[ $a['subscription_id'] ] ) && ! isset( $members_subscriptions[ $b['subscription_id'] ] ) ) {
								return - 1;
							} else {
								return 1;
							}
						}
					}
					uasort( $sorted_subscriptions, 'sort_subscriptions' );
				}

				foreach ( $sorted_subscriptions as $subscription ) {

					if ( ! module_subscription::can_i( 'edit', 'Subscriptions' ) && ! isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ) {
						continue;
					}

					$next_due_time     = time();
					$next_due_time_pre = false;
					$history           = module_subscription::get_subscription_history( $subscription['subscription_id'], $owner_table, $member_id );

					if ( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ) {
						$responsive_summary[] = htmlspecialchars( $subscription['name'] );
					}
					?>
					<div
						class="subscription<?php echo isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ? ' active' : ''; ?>" <?php if ( module_subscription::can_i( 'edit', 'Subscriptions' ) ) { ?> data-settings-url="<?php echo module_subscription::link_open( $subscription['subscription_id'], false, $subscription ); ?>" data-settings-class="data-settings-right" <?php } ?>>
						<input type="checkbox" name="subscription[<?php echo $subscription['subscription_id']; ?>]" value="1"
						       id="subscription_<?php echo $subscription['subscription_id']; ?>" <?php if ( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ) {
							echo 'checked';
						} ?> onchange="$(this).parent().find('.subscription_start_date').show();">
						<label
							for="subscription_<?php echo $subscription['subscription_id']; ?>"><?php echo htmlspecialchars( $subscription['name'] ); ?></label>
						- <?php echo dollar( $subscription['amount'], true, $subscription['currency_id'] );

						if ( ! $subscription['days'] && ! $subscription['months'] && ! $subscription['years'] ) {
							//echo _l('Once off');
						} else {
							$bits = array();
							if ( $subscription['days'] > 0 ) {
								$bits[] = _l( '%s days', $subscription['days'] );
							}
							if ( $subscription['months'] > 0 ) {
								$bits[] = _l( '%s months', $subscription['months'] );
							}
							if ( $subscription['years'] > 0 ) {
								$bits[] = _l( '%s years', $subscription['years'] );
							}
							echo ' ';
							echo _l( 'Every %s', implode( ', ', $bits ) );
						}
						?>
						<span class="subscription_start_date"
						      style="<?php echo isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ? '' : 'display:none;'; ?>">
                        <?php
                        echo _l( ' starting from ' );
                        ?>
							<input type="text" name="subscription_start_date[<?php echo $subscription['subscription_id']; ?>]"
							       value="<?php echo print_date( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ? $members_subscriptions[ $subscription['subscription_id'] ]['start_date'] : time() ); ?>"
							       class="date_field">
                        </span>
						<br/>
						<?php


						// and if it is active, when the next one is due.
						if ( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) ) {
							// do we use a bucket?
							if ( module_config::c( 'subscription_allow_credit', 1 ) ) {
								?>
								<input type="checkbox" value="1"
								       name="subscription_credit[<?php echo $subscription['subscription_id']; ?>]" <?php echo isset( $members_subscriptions[ $subscription['subscription_id'] ] ) && $members_subscriptions[ $subscription['subscription_id'] ]['use_as_credit_bucket'] ? ' checked' : ''; ?>> <?php _e( 'Add these payments to credit that can be used for invoice payments.' ); ?>
								<br/>
								<?php
								if ( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) && $members_subscriptions[ $subscription['subscription_id'] ]['use_as_credit_bucket'] ) {

									?>
									<ul>
										<li>Total Amount Received:
											<strong><?php echo dollar( $subscription_credit[ $subscription['subscription_id'] ]['total'] ); ?></strong>
										</li>
										<li>Total Used As Invoice Credit:
											<strong><?php echo dollar( $subscription_credit[ $subscription['subscription_id'] ]['used'] ); ?></strong>
											<?php if ( count( $subscription_credit[ $subscription['subscription_id'] ]['paid_invoices'] ) ) { ?>
												(<?php foreach ( $subscription_credit[ $subscription['subscription_id'] ]['paid_invoices'] as $invoice_id ) {
													echo module_invoice::link_open( $invoice_id, true ) . ' ';
												} ?>)
											<?php } ?>
										</li>
										<li>Remaining Amount Of Credit:
											<strong><?php echo dollar( $subscription_credit[ $subscription['subscription_id'] ]['remain'] ); ?></strong>
										</li>
									</ul>
									<?php
								}
							}
							$remaining_invoices = - 1;
							if ( module_config::c( 'subscription_allow_limits', 1 ) ) {
								_e( 'Maximum renewals:' ); ?> <input type="text"
								                                     value="<?php echo isset( $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] ) && $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] > 0 ? (int) $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] : ''; ?>"
								                                     name="subscription_recur_limits[<?php echo $subscription['subscription_id']; ?>]"
								                                     style="width:21px">
								<?php if ( isset( $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] ) && $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] > 0 ) {
									$remaining_invoices = max( 0, $members_subscriptions[ $subscription['subscription_id'] ]['recur_limit'] - count( $history ) );
									_e( '(%s remain)', $remaining_invoices );
								}
								_h( 'Here you can enter a number (eg: 12) and the system will only generate up to 12 subscription renewals. Leave empty or 0 to continue generating subscription renewals forever.' );
								echo '<br/>';
							}
							if ( $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] && $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] != '0000-00-00' && $remaining_invoices != 0 ) {
								?>
								<strong>
									<?php _e( 'Next due date is: %s', '<span class="next_due_date_change" data-id="' . $subscription['subscription_id'] . '">' . print_date( $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] ) . '</span>' );
									?>
								</strong>
								<?php
								$next_due_time = strtotime( $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] );
								$days          = ceil( ( $next_due_time - time() ) / 86400 );
								if ( $next_due_time < time() ) {
									echo ' <span class="important">';
									if ( abs( $days ) == 0 ) {
										_e( 'DUE TODAY' );
									} else {
										_e( 'OVERDUE' );
									}
									echo '</span> ';
								} else {
									//echo print_date($recurring['next_due_date']);
								}
								if ( abs( $days ) == 0 ) {
									//_e('(today)');
								} else {
									_e( ' (in %s days)', $days );
								}
								if ( isset( $members_subscriptions[ $subscription['subscription_id'] ]['next_generation_date'] ) && $members_subscriptions[ $subscription['subscription_id'] ]['next_generation_date'] != $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] ) {
									echo '<br/><strong>';
									_e( 'Invoice will be created %s days prior on %s ', isset( $subscription['invoice_prior_days'] ) ? $subscription['invoice_prior_days'] : 0, print_date( $members_subscriptions[ $subscription['subscription_id'] ]['next_generation_date'] ) );
									echo '</strong>';
									$next_due_time_pre = strtotime( $members_subscriptions[ $subscription['subscription_id'] ]['next_generation_date'] );
									$days              = ceil( ( $next_due_time_pre - time() ) / 86400 );
									if ( $next_due_time_pre < time() ) {
										echo ' <span class="important">';
										if ( abs( $days ) == 0 ) {
											_e( 'DUE TODAY' );
										} else {
											_e( 'OVERDUE' );
										}
										echo '</span> ';
									} else {
										//echo print_date($recurring['next_due_date']);
									}
									if ( abs( $days ) == 0 ) {
										//_e('(today)');
									} else {
										_e( ' (in %s days)', $days );
									}
								}

								echo '<br/>';
							}
						}

						$invoice_history_html = '';

						// we have to look up the history for this subscription and show the last payment made,
						$next_due_time_invoice_created = false;
						if ( count( $history ) > 0 ) {
							foreach ( $history as $h ) {
								if ( ! $h['invoice_id'] ) {
									$invoice_history_html .= 'ERROR! NO invoice id specified for subscription history. Please report this bug.';
								} else {
									$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
									if ( $invoice_data['date_cancel'] != '0000-00-00' ) {
										continue;
									}
									if ( isset( $h['from_next_due_date'] ) && $h['from_next_due_date'] && $h['from_next_due_date'] != '0000-00-00' && isset( $members_subscriptions[ $subscription['subscription_id'] ] ) && isset( $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] ) && $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] != '0000-00-00' && print_date( $members_subscriptions[ $subscription['subscription_id'] ]['next_due_date'] ) == print_date( $h['from_next_due_date'] ) ) {
										// this invoice is for the next due date (calculated using the new method of storing date in db)
										$next_due_time_invoice_created = $invoice_data;

									} else if ( print_date( $next_due_time ) == print_date( $invoice_data['date_create'] ) || ( $next_due_time_pre && print_date( $next_due_time_pre ) == print_date( $invoice_data['date_create'] ) ) ) {
										// this invoice is for the next due date.
										$next_due_time_invoice_created = $invoice_data;
									}
									$invoice_history_html .= '<li>';
									$invoice_history_html .= _l( 'Invoice #%s for %s on %s (paid on %s)',
										module_invoice::link_open( $h['invoice_id'], true, $invoice_data ),
										dollar( $invoice_data['total_amount'], true, $invoice_data['currency_id'] ),
										print_date( $invoice_data['date_create'] ),
										$invoice_data['date_paid'] != '0000-00-00' ? print_date( $invoice_data['date_paid'] ) : '<span class="important">' . _l( 'UNPAID' ) . '</span>'
									);
									$invoice_history_html .= '</li>';
								}
							}
						}


						if ( isset( $members_subscriptions[ $subscription['subscription_id'] ] ) && module_security::is_page_editable() && $remaining_invoices != 0 ) {
							//echo '<li>';
							if ( $next_due_time_invoice_created ) {
								_e( 'The next invoice has been created for %s. Please mark it as paid.', '<a href="' . module_invoice::link_open( $next_due_time_invoice_created['invoice_id'], false, $next_due_time_invoice_created ) . '">' . print_date( $next_due_time ) . '</a>' );
								echo ' <a href="#" onclick="$(\'#next_invoice_' . $subscription['subscription_id'] . '\').show(); $(this).hide();">New</a>';;
								echo '<span id="next_invoice_' . $subscription['subscription_id'] . '" style="display:none;"><br/>';
							}
							if ( isset( $subscription['automatic_renew'] ) && $subscription['automatic_renew'] ) {
								_e( 'This Subscription will renew on %s', print_date( $next_due_time_pre ? $next_due_time_pre : $next_due_time ) );
								echo ' ';
								if ( isset( $subscription['automatic_email'] ) && $subscription['automatic_email'] ) {
									_e( 'and will be automatically emailed' );
								}
							} else {
								_e( 'New Invoice for' ); ?>
								<?php echo currency( '<input type="text" name="foo" id="amount_' . $subscription['subscription_id'] . '" value="' . $subscription['amount'] . '" class="currency">', true, $subscription['currency_id'] ); ?>
								<?php _e( 'dated' ); ?> <input type="text" name="foo"
								                               id="date_<?php echo $subscription['subscription_id']; ?>"
								                               value="<?php echo print_date( $next_due_time ); ?>" class="date_field">
								<input type="button" name="gen_invoice" value="<?php _e( 'Create Invoice' ); ?>"
								       onclick="$('#subscription_add_payment').val(<?php echo $subscription['subscription_id']; ?>); $('#subscription_add_payment_amount').val($('#amount_<?php echo $subscription['subscription_id']; ?>').val()); $('#subscription_add_payment_date').val($('#date_<?php echo $subscription['subscription_id']; ?>').val()); this.form.submit();"
								       class="submit_small">
								<?php
							}

							if ( $next_due_time_invoice_created ) {
								echo '</span>';
							}
							//echo '</li>';
						}
						echo '<ul>';
						echo $invoice_history_html;
						echo '</ul>';

						// todo - handle if one of these invoices has been deleted.
						// remove the payment, and remove the subscriptino history entry.


						?>
					</div>

				<?php } ?>
			</td>
		</tr>
		</tbody>
	</table>

<?php

$fieldset_data = array(
	'heading'         => array(
		'title'      => _l( 'Subscriptions &amp; Payments' ),
		'type'       => 'h3',
		'responsive' => array(
			'title'   => 'Subscriptions',
			'summary' => htmlspecialchars( implode( ', ', $responsive_summary ) ),
		)
	),
	'elements_before' => ob_get_clean(),
);
echo module_form::generate_fieldset( $fieldset_data );