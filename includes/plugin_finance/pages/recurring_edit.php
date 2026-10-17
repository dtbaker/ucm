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
module_form::print_form_open_tag( array(
	'process' => 'save_recurring',
	'hidden'  => array(
		'finance_recurring_id' => $finance_recurring_id,
	),
) );

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

	<?php ob_start(); ?>

	<?php
	// The once-off toggle used to hide a <tbody id="recurring_bits">. A fieldset has no row
	// groups, so the two rows carry row_class="recurring_bits" and the script below hides
	// them by class instead - which behaves the same and does not depend on the markup a
	// particular theme's fieldset renderer produces.
	$hide_recurring_bits = ( $finance_recurring_id && ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] );
	?>
	<script type="text/javascript">
      function change_recurring_type() {
          if ($('#recurring_type').val() == 'onceoff') {
              $('#days').val('0');
              $('#months').val('0');
              $('#years').val('0');
              $('#end_date').val('');
              $('.recurring_bits').hide();
          } else {
              $('.recurring_bits').show();
          }
      }
	</script>
	<?php
	$cell = array();

	ob_start();
	echo currency( '', true, $recurring['currency_id'] );
	?>
	<input type="text" name="amount" value="<?php echo htmlspecialchars( $recurring['amount'] ); ?>" class="currency">
	<?php
	$cell['amount'] = ob_get_clean();

	ob_start();
	?>
	<input type="radio" name="type" id="income" value="i"<?php echo $recurring['type'] == 'i' ? ' checked' : ''; ?>>
	<label for="income"><?php _e( 'Income/Credit' ); ?></label> <br/>
	<input type="radio" name="type" id="expense" value="e"<?php echo $recurring['type'] == 'e' ? ' checked' : ''; ?>>
	<label for="expense"><?php _e( 'Expense/Debit' ); ?></label> <br/>
	<?php
	$cell['type'] = ob_get_clean();

	ob_start();
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
	<?php
	$cell['categories'] = ob_get_clean();

	ob_start();
	?>
	<select name="recurringtype" onchange="change_recurring_type();" id="recurring_type">
		<option value="recurring"><?php _e( 'Recurring' ); ?></option>
		<option value="onceoff"<?php echo $hide_recurring_bits ? ' selected' : ''; ?>><?php _e( 'Once Off' ); ?></option>
	</select>
	<?php
	$cell['recurringtype'] = ob_get_clean();

	ob_start();
	?>
	<input type="text" name="days" id="days" value="<?php echo $recurring['days']; ?>"
	       style="width:30px;"/> <?php _e( 'Days' ); ?><br/>
	<input type="text" name="months" id="months" value="<?php echo $recurring['months']; ?>"
	       style="width:30px;"/> <?php _e( 'Months' ); ?><br/>
	<input type="text" name="years" id="years" value="<?php echo $recurring['years']; ?>"
	       style="width:30px;"/> <?php _e( 'Years' ); ?><br/>
	<?php
	$cell['repeat'] = ob_get_clean();

	ob_start();
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
	$cell['last_transaction'] = ob_get_clean();

	ob_start();
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
	$cell['next_due_date'] = ob_get_clean();

	$recurring_elements = array(
		'name'             => array(
			'title' => _l( 'Name' ),
			'field' => array(
				'type'  => 'text',
				'name'  => 'name',
				'value' => $recurring['name'],
			),
		),
		'description'      => array(
			'title' => _l( 'Description' ),
			'field' => array(
				'type'  => 'textarea',
				'name'  => 'description',
				'rows'  => 4,
				'cols'  => 30,
				'style' => 'width:350px; height: 80px;',
				'value' => $recurring['description'],
			),
		),
		'amount'           => array(
			'title'  => _l( 'Amount' ),
			'fields' => array( $cell['amount'] ),
		),
		'currency_id'      => array(
			'title' => _l( 'Currency' ),
			'field' => array(
				'type'    => 'select',
				'name'    => 'currency_id',
				'options' => get_multiple( 'currency', '', 'currency_id' ),
				'blank'   => false,
				'value'   => $recurring['currency_id'],
			),
		),
		'type'             => array(
			'title'  => _l( 'Income/Expense' ),
			'fields' => array( $cell['type'] ),
		),
		'finance_account'  => array(
			'title'  => _l( 'Default Account' ),
			'fields' => array(
				static function () use ( $recurring ) {
					echo print_select_box( module_finance::get_accounts(), 'finance_account_id', isset( $recurring['finance_account_id'] ) ? $recurring['finance_account_id'] : '', '', true, 'name', true );
				},
			),
		),
		'categories'       => array(
			'title'  => _l( 'Default Categories' ),
			'fields' => array( $cell['categories'] ),
		),
		'recurringtype'    => array(
			'title'  => _l( 'Recurring or Once Off' ),
			'fields' => array( $cell['recurringtype'] ),
		),
		'start_date'       => array(
			'title' => _l( 'Start Date' ),
			'field' => array(
				'type'  => 'text',
				'name'  => 'start_date',
				'class' => 'date_field',
				'value' => ( $recurring['start_date'] && $recurring['start_date'] != '0000-00-00' )
					? print_date( $recurring['start_date'] ) : print_date( time() ),
			),
		),
		'end_date'         => array(
			'title'     => _l( 'End Date' ),
			'row_class' => 'recurring_bits',
			'row_style' => $hide_recurring_bits ? 'display:none;' : '',
			'field'     => array(
				'type'  => 'text',
				'name'  => 'end_date',
				'id'    => 'end_date',
				'class' => 'date_field',
				'value' => print_date( $recurring['end_date'] ),
			),
		),
		'repeat'           => array(
			'title'     => _l( 'Repeat Every' ),
			'row_class' => 'recurring_bits',
			'row_style' => $hide_recurring_bits ? 'display:none;' : '',
			'fields'    => array( $cell['repeat'] ),
		),
		'last_transaction' => array(
			'title'  => _l( 'Last Transaction' ),
			'fields' => array( $cell['last_transaction'] ),
		),
		'next_due_date'    => array(
			'title'  => _l( 'Next Due Date' ),
			'fields' => array( $cell['next_due_date'] ),
		),
	);

	if ( $show_record_button ) {
		ob_start();
		?>
		<a href="<?php echo module_finance::link_open_record_recurring( $recurring['finance_recurring_id'] ); ?>"
		   class="uibutton"<?php if ( $days > 10 ) { ?> onclick="return confirm('<?php echo addcslashes( _l( 'This transaction is not due for %s days, are you sure you want to record it?', $days ), "'" ); ?>');" <?php } ?>><?php _e( 'Record this transaction' ); ?></a>
		<?php _e( '(this will schedule the next reminder)' ); ?>
		<?php
		$recurring_elements['record'] = array(
			'title'  => _l( 'Record' ),
			'fields' => array( ob_get_clean() ),
		);
	}

	echo module_form::generate_fieldset( array(
		'class'    => 'tableclass tableclass_form',
		'elements' => $recurring_elements,
	) );
	?>
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

	module_form::print_form_close_tag();
