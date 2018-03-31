<?php

if ( ! module_finance::can_i( 'view', 'Finance Upcoming' ) ) {
	redirect_browser( _BASE_HREF );
}
$locked = false;

$linked_finances = $linked_invoice_payments = array();

$finance_recurring_id = (int) $_REQUEST['finance_recurring_id'];
$recurring            = module_finance::get_recurring( $finance_recurring_id );
$show_record_button   = true;

if ( ! $finance_recurring_id ) {
	$show_record_button = false;
	$module->page_title = _l( 'Recurring' );
} else {
	$module->page_title = _l( 'Recurring: %s', htmlspecialchars( $recurring['name'] ) );
}
?>
<form action="" method="post">

	<?php

	module_form::set_required( array(
		'fields' => array(
			'name'       => 'Name',
			'amount'     => 'Amount',
			'start_date' => 'Start Date',
		)
	) );
	module_form::prevent_exit( array(
			'valid_exits' => array(
				// selectors for the valid ways to exit this form.
				'.submit_button',
			)
		)
	);
	?>


	<input type="hidden" name="_process" value="save_recurring"/>
	<input type="hidden" name="finance_recurring_id" value="<?php echo $finance_recurring_id; ?>"/>

	<?php ob_start(); ?>

	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
		<tbody>
		<tr>
			<th class="width2">
				<?php echo _l( 'Name' ); ?>
			</th>
			<td>
				<input type="text" name="name" value="<?php echo htmlspecialchars( $recurring['name'] ); ?>"/>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Description' ); ?>
			</th>
			<td>
				<textarea name="description" rows="4" cols="30"
				          style="width:350px; height: 80px;"><?php echo htmlspecialchars( $recurring['description'] ); ?></textarea>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Amount' ); ?>
			</th>
			<td valign="top">
				<?php echo currency( '', true, $recurring['currency_id'] ); ?>
				<input type="text" name="amount" value="<?php echo htmlspecialchars( $recurring['amount'] ); ?>"
				       class="currency">
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Currency' ); ?>
			</th>
			<td valign="top">
				<?php echo print_select_box( get_multiple( 'currency', '', 'currency_id' ), 'currency_id', $recurring['currency_id'], '', false, 'code' ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Income/Expense' ); ?>
			</th>
			<td valign="top">
				<input type="radio" name="type" id="income" value="i"<?php echo $recurring['type'] == 'i' ? ' checked' : ''; ?>>
				<label for="income"><?php _e( 'Income/Credit' ); ?></label> <br/>
				<input type="radio" name="type" id="expense"
				       value="e"<?php echo $recurring['type'] == 'e' ? ' checked' : ''; ?>> <label
					for="expense"><?php _e( 'Expense/Debit' ); ?></label> <br/>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Default Account' ); ?>
			</th>
			<td valign="top">
				<?php echo print_select_box( module_finance::get_accounts(), 'finance_account_id', isset( $recurring['finance_account_id'] ) ? $recurring['finance_account_id'] : '', '', true, 'name', true ); ?>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Default Categories' ); ?>
			</th>
			<td valign="top">
				<?php
				$categories = module_finance::get_categories();
				foreach ( $categories as $category ) { ?>
					<input type="checkbox" name="finance_category_id[]" value="<?php echo $category['finance_category_id']; ?>"
					       id="category_<?php echo $category['finance_category_id']; ?>" <?php echo isset( $recurring['category_ids'][ $category['finance_category_id'] ] ) ? ' checked' : ''; ?>>
					<label
						for="category_<?php echo $category['finance_category_id']; ?>"><?php echo htmlspecialchars( $category['name'] ); ?></label>
					<br/>
				<?php }
				?>
				<input type="checkbox" name="finance_category_new_checked" value="new">
				<input type="text" name="finance_category_new" value="">

			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Recurring or Once Off' ); ?>
			</th>
			<td valign="top">
				<script type="text/javascript">
            function change_recurring_type() {
                if ($('#recurring_type').val() == 'onceoff') {
                    $('#days').val('0');
                    $('#months').val('0');
                    $('#years').val('0');
                    $('#end_date').val('');
                    $('#recurring_bits').hide();
                } else {
                    $('#recurring_bits').show();
                }
            }
				</script>
				<select name="recurringtype" onchange="change_recurring_type();" id="recurring_type">
					<option value="recurring"><?php _e( 'Recurring' ); ?></option>
					<option
						value="onceoff"<?php if ( $finance_recurring_id && ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) {
						echo ' selected';
					} ?>><?php _e( 'Once Off' ); ?></option>
				</select>
			</td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<th>
				<?php echo _l( 'Start Date' ); ?>
			</th>
			<td valign="top">
				<input type="text" name="start_date"
				       value="<?php echo ( $recurring['start_date'] && $recurring['start_date'] != '0000-00-00' ) ? print_date( $recurring['start_date'] ) : print_date( time() ); ?>"
				       class="date_field"/>
			</td>
		</tr>
		</tbody>
		<tbody
			id="recurring_bits" <?php if ( $finance_recurring_id && ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) {
			echo ' style="display:none;"';
		} ?>>
		<tr>
			<th>
				<?php echo _l( 'End Date' ); ?>
			</th>
			<td valign="top">
				<input type="text" name="end_date" id="end_date" value="<?php echo print_date( $recurring['end_date'] ); ?>"
				       class="date_field"/>
			</td>
		</tr>
		<tr>
			<th>
				<?php echo _l( 'Repeat Every' ); ?>
			</th>
			<td valign="top">
				<input type="text" name="days" id="days" value="<?php echo $recurring['days']; ?>"
				       style="width:30px;"/> <?php _e( 'Days' ); ?><br/>
				<input type="text" name="months" id="months" value="<?php echo $recurring['months']; ?>"
				       style="width:30px;"/> <?php _e( 'Months' ); ?><br/>
				<input type="text" name="years" id="years" value="<?php echo $recurring['years']; ?>"
				       style="width:30px;"/> <?php _e( 'Years' ); ?><br/>
			</td>
		</tr>
		</tbody>
		<tbody>
		<tr>
			<th>
				<?php echo _l( 'Last Transaction' ); ?>
			</th>
			<td valign="top">
				<?php
				if ( ! isset( $recurring['last_transaction_finance_id'] ) || ! $recurring['last_transaction_finance_id'] ) {
					_e( 'Never' );
				} else {
					?> <a href="<?php echo module_finance::link_open( $recurring['last_transaction_finance_id'] ); ?>"><?php
						echo _l( '%s on %s', currency( $recurring['last_amount'] ), print_date( $recurring['last_transaction_date'] ) );
						?></a>
					(<a
						href="<?php echo module_finance::link_open( false ); ?>?search[finance_recurring_id]=<?php echo $finance_recurring_id; ?>"><?php _e( 'view all' ); ?></a>)
					<?php
				}
				?>
			</td>
		</tr>
		<tr>
			<th>
				<?php _e( 'Next Due Date' ); ?>
			</th>
			<td>
				<?php
				if ( isset( $recurring['next_due_date'] ) ) {
					$next_due = strtotime( $recurring['next_due_date'] );
					if ( ! $recurring['next_due_date'] || $recurring['next_due_date'] == '0000-00-00' || ( $recurring['end_date'] && $recurring['end_date'] != '0000-00-00' && $next_due > strtotime( $recurring['end_date'] ) ) ) {
						echo _l( '(recurring finished)' );
						$show_record_button = false;
					} else {
						?>
						<input type="text" name="set_next_due_date" value="<?php echo print_date( $recurring['next_due_date'] ); ?>"
						       class="date_field">
						<?php
						if ( $next_due < time() ) {
							echo ' <span class="important">';
							echo _e( 'OVERDUE' );
							echo '</span> ';
						} else {
							//echo print_date($recurring['next_due_date']);
						}
					}
					if ( $show_record_button ) {
						$days = ceil( ( $next_due - time() ) / 86400 );
						if ( abs( $days ) == 0 ) {
							_e( '(today)' );
						} else {
							_e( ' (%s days)', $days );
						}
					}
					if ( $recurring['next_due_date_custom'] ) {
						echo ' ';
						$next_due_date_real = module_finance::calculate_recurring_date( $finance_recurring_id, true, false );
						if ( $next_due_date_real != $recurring['next_due_date'] ) {
							echo '<em>';
							_e( 'this is a custom date, it should be %s', print_date( $next_due_date_real ) );
							echo '</em>';
						}
					}
				}
				?>

			</td>
		</tr>
		<?php if ( $show_record_button ) { ?>
			<tr>
				<th>
					<?php _e( 'Record' ); ?>
				</th>
				<td>
					<a href="<?php echo module_finance::link_open_record_recurring( $recurring['finance_recurring_id'] ); ?>"
					   class="uibutton"<?php if ( $days > 10 ) { ?> onclick="return confirm('<?php echo addcslashes( _l( 'This transaction is not due for %s days, are you sure you want to record it?', $days ), "'" ); ?>');" <?php } ?>><?php _e( 'Record this transaction' ); ?></a>
					<?php _e( '(this will schedule the next reminder)' ); ?>
				</td>
			</tr>
		<?php } ?>

		</tbody>
	</table>
	<?php

	$fieldset_data = array(
		'heading'         => array(
			'title' => _l( 'Recurring Transaction' ),
			'type'  => 'h2',
			'main'  => true,
		),
		'elements_before' => ob_get_clean(),
	);
	echo module_form::generate_fieldset( $fieldset_data );
	unset( $fieldset_data );

	$form_actions = array(
		'class'    => 'action_bar action_bar_left',
		'elements' => array(
			array(
				'type'  => 'save_button',
				'name'  => 'butt_save',
				'value' => _l( 'Save' ),
			),
			array(
				'type'    => 'delete_button',
				'name'    => 'butt_del',
				'value'   => _l( 'Delete' ),
				'onclick' => "return confirm('" . _l( 'Really delete this record?' ) . "');",
			),
			array(
				'type'    => 'button',
				'name'    => 'cancel',
				'value'   => _l( 'Cancel' ),
				'class'   => 'submit_button',
				'onclick' => "window.location.href='" . module_finance::link_open_recurring( false ) . "';",
			),
		),
	);
	echo module_form::generate_form_actions( $form_actions );
	?>


</form>
