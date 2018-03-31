<?php


if ( ! module_config::can_i( 'view', 'Settings' ) ) {
	redirect_browser( _BASE_HREF );
}

$subscription_id = (int) $_REQUEST['subscription_id'];
$subscription    = array();

$subscription = module_subscription::get_subscription( $subscription_id );

// check permissions.
if ( class_exists( 'module_security', false ) ) {
	if ( $subscription_id > 0 && $subscription['subscription_id'] == $subscription_id ) {
		// if they are not allowed to "edit" a page, but the "view" permission exists
		// then we automatically grab the page and regex all the crap out of it that they are not allowed to change
		// eg: form elements, submit buttons, etc..
		module_security::check_page( array(
			'category'  => 'Subscription',
			'page_name' => 'Subscriptions',
			'module'    => 'subscription',
			'feature'   => 'Edit',
		) );
	} else {
		module_security::check_page( array(
			'category'  => 'Subscription',
			'page_name' => 'Subscriptions',
			'module'    => 'subscription',
			'feature'   => 'Create',
		) );
	}
	module_security::sanatise_data( 'subscription', $subscription );
}

?>

<?php hook_handle_callback( 'layout_column_half', 1 ); ?>

	<form action="" method="post" id="subscription_form">
		<input type="hidden" name="_process" value="save_subscription"/>
		<input type="hidden" name="subscription_id" value="<?php echo $subscription_id; ?>"/>

		<?php
		module_form::set_required( array(
				'fields' => array(
					'name'   => 'Name',
					'amount' => 'Amount',
				)
			)
		);
		module_form::prevent_exit( array(
				'valid_exits' => array(
					// selectors for the valid ways to exit this form.
					'.submit_button',
				)
			)
		);

		$payment_methods = handle_hook( 'get_payment_methods', $module );

		$invoice_template_email = $invoice_template_print = array();
		foreach ( module_template::get_templates() as $possible_template ) {
			if ( strpos( $possible_template['template_key'], 'invoice_print' ) !== false ) {
				$invoice_template_print[ $possible_template['template_key'] ] = $possible_template['template_key'];
			} else if ( strpos( $possible_template['template_key'], 'invoice_email' ) !== false ) {
				$key = preg_replace( '#\_(due|paid|overdue)$#', '', $possible_template['template_key'] );
				if ( ! isset( $invoice_template_email[ $key ] ) ) {
					$invoice_template_email[ $key ] = array();
				}
				$invoice_template_email[ $key ][] = $possible_template['template_key'];
			}
		}
		foreach ( $invoice_template_email as $key => $val ) {
			$invoice_template_email[ $key ] = implode( ', ', $val );
		}

		$fieldset_data = array(
			'title'    => _l( 'Subscription Information' ),
			'class'    => 'tableclass tableclass_form tableclass_full',
			'elements' => array(
				'name'         => array(
					'title' => _l( 'Name' ),
					'field' => array(
						'type'  => 'text',
						'name'  => 'name',
						'value' => $subscription['name'],
						'size'  => 40,
					)
				),
				'amount'       => array(
					'title'  => _l( 'Amount' ),
					'fields' => array(
						array(
							'type'        => 'currency',
							'name'        => 'amount',
							'value'       => $subscription['amount'],
							'currency_id' => $subscription['currency_id'],
						),
						array(
							'type'             => 'select',
							'name'             => 'currency_id',
							'value'            => $subscription['currency_id'],
							'options'          => get_multiple( 'currency', '', 'currency_id' ),
							'options_array_id' => 'code',
							'blank'            => false,
						),
					)
				),
				'tax'          => array(
					'title'  => _l( 'Tax' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'settings[tax_name]',
							'value' => isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_name'] ) ? $subscription['settings']['tax_name'] : module_config::c( 'subscription_invoice_tax_name', 'TAX' ),
							'size'  => 4,
						),
						'@',
						array(
							'type'  => 'text',
							'name'  => 'settings[tax_amount]',
							'value' => isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_amount'] ) ? $subscription['settings']['tax_amount'] : module_config::c( 'subscription_invoice_tax_rate', '10' ),
							'size'  => 4,
						),
						'%  ',
						array(
							'type'    => 'select',
							'options' => array( '0' => _l( 'Tax Added' ), 1 => _l( 'Tax Included' ) ),
							'value'   => isset( $subscription['settings'] ) && isset( $subscription['settings']['tax_type'] ) ? $subscription['settings']['tax_type'] : module_config::c( 'invoice_tax_type', 0 ),
							'name'    => 'settings[tax_type]',
						)
					)
				),
				'repeat_every' => array(
					'title'  => _l( 'Repeat Every' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'days',
							'value' => $subscription['days'],
							'size'  => 4,
						),
						_l( 'Days' ) . '<br/>',
						array(
							'type'  => 'text',
							'name'  => 'months',
							'value' => $subscription['months'],
							'size'  => 4,
						),
						_l( 'Months' ) . '<br/>',
						array(
							'type'  => 'text',
							'name'  => 'years',
							'value' => $subscription['years'],
							'size'  => 4,
						),
						_l( 'Years' ) . '<br/>',
					)
				),
				array(
					'title' => _l( 'Automatic Renew' ),
					'field' => array(
						'type'  => 'checkbox',
						'name'  => 'automatic_renew',
						'value' => isset( $subscription['automatic_renew'] ) ? $subscription['automatic_renew'] : false,
						'help'  => 'If this option is selected then the CRON job will automatically renew this subscription and generate a new invoice. It will only renew if the previous invoice has been paid (to stop multiple outstanding invoices)',
					),
				),
				array(
					'title' => _l( 'Automatic Email' ),
					'field' => array(
						'type'  => 'checkbox',
						'name'  => 'automatic_email',
						'value' => isset( $subscription['automatic_email'] ) ? $subscription['automatic_email'] : false,
						'help'  => 'If this option is selected then the CRON job will automatically email the automatically generated invoice to the subscribed Customer/Member',
					),
				),
				array(
					'title'  => _l( 'Invoice Prior' ),
					'fields' => array(
						array(
							'type'  => 'text',
							'name'  => 'invoice_prior_days',
							'value' => isset( $subscription['invoice_prior_days'] ) ? $subscription['invoice_prior_days'] : 0,
							'help'  => 'How many days prior to the renewal date will an invoice be generated.',
							'size'  => 5,
						),
						_l( 'Days' ),
					),
				),
				array(
					'title'  => 'Allowed Payment Methods',
					'fields' => array(
						array(
							'type'  => 'hidden',
							'name'  => 'allowed_payment_method_check',
							'value' => 1,
						),
						function () use ( &$payment_methods, $subscription ) {
							$x = 0;
							foreach ( $payment_methods as &$payment_method ) {
								if ( $payment_method->is_enabled() ) {
									$enabled = isset( $subscription['settings']['payment_methods'][ $payment_method->module_name ] ) && $subscription['settings']['payment_methods'][ $payment_method->module_name ] ? true : ( isset( $subscription['settings']['payment_methods'] ) ? false : true );
									?>
									<input type="checkbox" name="settings[payment_methods][<?php echo $payment_method->module_name; ?>]"
									       value="1" id="paymethodallowed<?php echo $x; ?>" <?php echo $enabled ? 'checked' : ''; ?>>
									<label
										for="paymethodallowed<?php echo $x; ?>"><?php echo $payment_method->get_payment_method_name(); ?></label>
									<br/>
									<?php
									$x ++;
								}
							}
						}
					),
				),
				array(
					'title'  => _l( 'Trial Period' ) . ' (only for PayPal)',
					'fields' => array(
						_l( 'Adjust price by:' ),
						array(
							'type'  => 'currency',
							'name'  => 'settings[trial_price_adjust]',
							'value' => isset( $subscription['settings'] ) && isset( $subscription['settings']['trial_price_adjust'] ) ? $subscription['settings']['trial_price_adjust'] : 0,
							'size'  => 4,
						),
						/*_l('For the first:'),
							array(
									'type' => 'text',
									'name' => 'settings[trial_period]',
									'value' => isset($subscription['settings']) && isset($subscription['settings']['trial_period']) ? $subscription['settings']['trial_period'] : 0,
									'size' => 4,
							),
							_l('Invoices'),*/
						_hr( 'If you would like to give a $10 discount for the first subscription invoice then enter -10 into here. ' ),
					),
				),
				array(
					'title'  => _l( 'Invoice PDF Template' ),
					'fields' => array(
						array(
							'type'    => 'select',
							'name'    => 'settings[invoice_template_print]',
							'value'   => isset( $subscription['settings'] ) && isset( $subscription['settings']['invoice_template_print'] ) ? $subscription['settings']['invoice_template_print'] : '',
							'options' => $invoice_template_print,
							'help'    => 'Choose the default template for PDF printing and PDF emailing. Name your custom template invoice_print_SOMETHING for them to appear in this listing. Replace SOMETHING with a word of your choice. You can also create a different template to be sent for the first subscription, name it invoice_print_SOMETHING_1 '
						),
					),
				),
				array(
					'title'  => _l( 'Invoice Email Template' ),
					'fields' => array(
						array(
							'type'    => 'select',
							'name'    => 'settings[invoice_template_email]',
							'value'   => isset( $subscription['settings'] ) && isset( $subscription['settings']['invoice_template_email'] ) ? $subscription['settings']['invoice_template_email'] : '',
							'options' => $invoice_template_email,
							'help'    => 'Choose the default template for emailing these subscription invoices to customers. You must create 3 new custom templates. Name your custom templates invoice_email_SOMETHING_due, invoice_email_SOMETHING_overdue and invoice_email_SOMETHING_paid for them to appear in this listing. Replace SOMETHING with a word of your choice. You can also create a different template to be sent for the first subscription, name it invoice_email_SOMETHING_due_1 '
						),
					),
				),
			)
		);
		echo module_form::generate_fieldset( $fieldset_data );

		$form_actions = array(
			'elements' => array(
				array(
					'type'  => 'save_button',
					'name'  => 'butt_save',
					'value' => _l( 'Save' ),
				),
				array(
					'ignore' => (int) $subscription_id == 0,
					'type'   => 'delete_button',
					'name'   => 'butt_del',
					'value'  => _l( 'Delete' ),
				),
				array(
					'type'    => 'button',
					'name'    => 'cancel',
					'value'   => _l( 'Cancel' ),
					'class'   => 'submit_button',
					'onclick' => "window.location.href='" . $module->link_open( false ) . "';",
				),
			),
		);
		echo module_form::generate_form_actions( $form_actions );

		?>

	</form>

<?php hook_handle_callback( 'layout_column_half', 2 ); ?>

<?php if ( (int) $subscription_id > 0 ) {

	// show subscribed members / customers
	print_heading( array( 'type' => 'h3', 'title' => 'Subscribers' ) );
	$subscribed_owners = module_subscription::get_subscribed_owners( $subscription_id );

	?>

	<table class="tableclass tableclass_rows tableclass_full">
		<thead>
		<tr>
			<th><?php _e( 'Name' ); ?></th>
			<th><?php _e( 'Start Date' ); ?></th>
			<th><?php _e( 'Next Date' ); ?></th>
			<th><?php _e( 'Invoices' ); ?></th>
			<th><?php _e( 'Total Received' ); ?></th>
			<th><?php _e( 'Total Unpaid' ); ?></th>
		</tr>
		</thead>
		<?php foreach ( $subscribed_owners as $subscribed_customer ) {
			$history    = module_subscription::get_subscription_history( $subscription_id, $subscribed_customer['owner_table'], $subscribed_customer['owner_id'] );
			$total_paid = $total_unpaid = array();
			foreach ( $history as $h_id => $h ) {
				if ( $h['invoice_id'] ) {
					$invoice = module_invoice::get_invoice( $h['invoice_id'], true );
					if ( $invoice['date_cancel'] && $invoice['date_cancel'] != '0000-00-00' ) {
						// invoice cancelled, ignore from listing
						unset( $history[ $h_id ] );
						continue;
					}
					if ( $h['paid_date'] && $h['paid_date'] != '0000-00-00' ) {
						if ( ! isset( $total_paid[ $invoice['currency_id'] ] ) ) {
							$total_paid[ $invoice['currency_id'] ] = 0;
						}
						$total_paid[ $invoice['currency_id'] ] += $h['amount'];
					}
				}
				if ( ! $h['paid_date'] || $h['paid_date'] == '0000-00-00' ) {
					if ( ! isset( $total_unpaid[ $subscription['currency_id'] ] ) ) {
						$total_unpaid[ $subscription['currency_id'] ] = 0;
					}
					$total_unpaid[ $subscription['currency_id'] ] += $h['amount'];
				}
			}
			foreach ( $total_paid as $id => $t ) {
				$total_paid[ $id ] = dollar( $t, true, $id );
			}
			foreach ( $total_unpaid as $id => $t ) {
				$total_unpaid[ $id ] = dollar( $t, true, $id );
			}
			?>
			<tr>
				<td><?php
					switch ( $subscribed_customer['owner_table'] ) {
						case 'customer':
							echo module_customer::link_open( $subscribed_customer['owner_id'], true );
							break;
						case 'website':
							echo module_website::link_open( $subscribed_customer['owner_id'], true );
							break;
						case 'member':
							echo module_member::link_open( $subscribed_customer['owner_id'], true );
							break;
					} ?></td>
				<td><?php echo print_date( $subscribed_customer['start_date'] ); ?></td>
				<td><?php echo print_date( $subscribed_customer['next_due_date'] ); ?></td>
				<td>
					<?php
					echo count( $history );
					?>
				</td>
				<td><?php echo implode( ', ', $total_paid ); ?></td>
				<td><?php echo implode( ', ', $total_unpaid ); ?></td>
			</tr>
		<?php } ?>
	</table>


<?php } ?>

<?php hook_handle_callback( 'layout_column_half', 'end' ); ?>