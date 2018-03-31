<?php


if ( ! module_finance::can_i( 'view', 'Finance Upcoming' ) ) {
	redirect_browser( _BASE_HREF );
}

$module->page_title = 'Recurring';
$search             = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
if ( module_config::c( 'finance_recurring_show_finished', 0 ) ) {
	$search['show_finished'] = true;
}
if ( ! isset( $search['date_to'] ) ) {
	$search['date_to'] = print_date( strtotime( '+' . (int) module_config::c( 'finance_recurring_months', 6 ) . ' months' ) );
}
$balance = isset( $_REQUEST['balance'] ) ? (float) $_REQUEST['balance'] : module_config::c( 'finance_recurring_start_balance', 0 );
module_config::save_config( 'finance_recurring_start_balance', $balance );

$_SESSION['_finance_recurring_ids'] = array();
module_debug::log( array(
	'title' => 'calling get_recurrings',
	'data'  => '',
) );
$upcoming_finances_unsorted = module_finance::get_recurrings( $search );
module_debug::log( array(
	'title' => 'finished calling get_recurrings',
	'data'  => 'count: ' . count( $upcoming_finances_unsorted ),
) );
$upcoming_finances    = array();
$limit_timestamp      = isset( $search['date_to'] ) && ! empty( $search['date_to'] ) ? strtotime( input_date( $search['date_to'] ) ) : strtotime( '+' . (int) module_config::c( 'finance_recurring_months', 6 ) . ' months' );
$duplicate_limit      = 30;
$upcoming_finance_key = 0;
foreach ( $upcoming_finances_unsorted as $recurring ) {
	$time     = strtotime( $recurring['next_due_date'] );
	$original = true;
	$count    = 0;
	while ( $time < $limit_timestamp ) {
		$next_time = 0;
		if ( $count ++ > $duplicate_limit ) {
			break;
		}

		// we need a special case for the first one that hasn't had a last trasnaction

		// we need a specicl case for the last one that the due date is on the finish date.


		if ( $recurring['next_due_date'] == '0000-00-00' || ( ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) ) {
			// it's a once off..
			// add it to the list but dont calculate the next one.

		} else if ( ! $original ) {
			// work out when the next one will be.
			$next_time = $time;
			$next_time = strtotime( '+' . abs( (int) $recurring['days'] ) . ' days', $next_time );
			$next_time = strtotime( '+' . abs( (int) $recurring['months'] ) . ' months', $next_time );
			$next_time = strtotime( '+' . abs( (int) $recurring['years'] ) . ' years', $next_time );
			$time      = $next_time;
		} else {
			// it's the original one.
			$next_time = $time;
		}

		// make sure $time isn't past the recurring events normal time.
		$end_time = ( $recurring['end_date'] && $recurring['end_date'] != '0000-00-00' ) ? strtotime( $recurring['end_date'] ) : 0;
		if ( $end_time > 0 && $next_time > $end_time ) {
			// we've finished calculating the items here
			break;
		} else {
			// we have a recurring time item ready to add to the list.
			// modify the finance item and add it to our upcoming_finances listing.
		}
		$upcoming_finances[ $upcoming_finance_key ] = $recurring;
		if ( ! $original ) {
			// we have to modify the time in this item etc..
			$upcoming_finances[ $upcoming_finance_key ]['next_due_date']               = date( 'Y-m-d', $time );
			$upcoming_finances[ $upcoming_finance_key ]['last_transaction_finance_id'] = 0;
		}
		$original = false;
		if ( ! $next_time || ! $time ) {
			break;
		}
		$upcoming_finance_key ++;
	}
	$upcoming_finance_key ++;
}
unset( $upcoming_finances_unsorted );
// now we add any upcoming invoice payments to the finance listing.
// now we add any upcoming subscription payments to the finance listings.
if ( function_exists( 'hook_handle_callback' ) ) {
	// don't hook into others when searching on particular items.
	$block_hook_when_searching = array( 'finance_category_id', 'finance_account_id' );
	$block_hook                = false;
	foreach ( $block_hook_when_searching as $key ) {
		if ( isset( $search[ $key ] ) && strlen( $search[ $key ] ) > 0 ) {
			$block_hook = true;
		}
	}
	if ( ! $block_hook ) {

		$others = hook_handle_callback( 'finance_recurring_list', $search );
		if ( is_array( $others ) && count( $others ) ) {
			foreach ( $others as $other ) {
				if ( is_array( $other ) && count( $other ) ) {
					// this should be a list of compatible upcoming finance items.
					// these items wont have a "record" button
					// these items will have their own url to open them (ie: not take them to the normal recurring edit screen)
					foreach ( $other as $o ) {
						$upcoming_finances[] = $o;
					}
				}
			}
		}
	}
}
// sort finances by their next_due_date
function sort_recurring_finance( $a, $b ) {
	$t1 = strtotime( $a['next_due_date'] );
	$t2 = strtotime( $b['next_due_date'] );

	return $t1 > $t2;
}

uasort( $upcoming_finances, 'sort_recurring_finance' );

// we have to search in PHP because our filters return results from all over the place
if ( isset( $search ) && is_array( $search ) ) {
	foreach ( $upcoming_finances as $recurring_id => $recurring ) {
		if ( $recurring['next_due_date'] && $recurring['next_due_date'] != '0000-00-00' ) {
			$recurring_date = strtotime( $recurring['next_due_date'] );
			if ( isset( $search['date_from'] ) && strlen( $search['date_from'] ) ) {
				$search_from = strtotime( input_date( $search['date_from'] ) );
				if ( $recurring_date < $search_from ) {
					unset( $upcoming_finances[ $recurring_id ] );
					continue;
				}
			}
			if ( isset( $search['date_to'] ) && strlen( $search['date_to'] ) ) {
				$search_to = strtotime( input_date( $search['date_to'] ) );
				if ( $recurring_date > $search_to ) {
					unset( $upcoming_finances[ $recurring_id ] );
					continue;
				}
			}
		}
		if ( isset( $search['generic'] ) && strlen( $search['generic'] ) > 0 ) {
			$name = strip_tags( isset( $recurring['url'] ) && $recurring['url'] ? $recurring['url'] : module_finance::link_open_recurring( $recurring['finance_recurring_id'], true, $recurring ) );
			if ( stripos( $name, $search['generic'] ) === false ) {
				unset( $upcoming_finances[ $recurring_id ] );
				continue;
			}
		}
		if ( isset( $search['amount_from'] ) && strlen( $search['amount_from'] ) ) {
			$amount = number_in( $search['amount_from'] );
			if ( $amount > 0 && $recurring['amount'] < $amount ) {
				unset( $upcoming_finances[ $recurring_id ] );
				continue;
			}
		}
		if ( isset( $search['amount_to'] ) && strlen( $search['amount_to'] ) ) {
			$amount = number_in( $search['amount_to'] );
			if ( $amount > 0 && $recurring['amount'] > $amount ) {
				unset( $upcoming_finances[ $recurring_id ] );
				continue;
			}
		}
	}
}

?>

<script type="text/javascript">
    function set_starting_balance() {
        var balance = prompt('<?php _e( 'Please enter starting balance' );?>', 0);
        window.location.href = '<?php $url = module_finance::link_open_recurring( false, false ); echo $url . ( strpos( $url, '?' ) ? '&' : '?' );?>balance=' + balance;
    }
</script>

<?php
$heading = array(
	'title_final' => _l( 'Upcoming Transactions' ),
	'type'        => 'h2',
	'main'        => true,
	'button'      => array(),
);
if ( module_finance::can_i( 'create', 'Finance Upcoming' ) ) {
	$heading['button'][] = array(
		'title' => "Add New",
		'type'  => 'add',
		'url'   => module_finance::link_open_recurring( 'new' ),
	);
	$heading['button'][] = array(
		'title' => _l( "Set Starting Balance (currently %s)", dollar( $balance ) ),
		'url'   => 'javascript:set_starting_balance();',
	);
}
print_heading( $heading );
?>

<form action="" method="post" id="finance_recurring_form">

	<?php

	$categories_rel = array();
	foreach ( module_finance::get_categories() as $category ) {
		$categories_rel[ $category['finance_category_id'] ] = $category['name'];
	}
	$accounts_rel = array();
	foreach ( module_finance::get_accounts() as $account ) {
		$accounts_rel[ $account['finance_account_id'] ] = $account['name'];
	}
	$search_bar = array(
		'elements' => array(
			'due_date' => array(
				'title'  => _l( 'Due Date:' ),
				'fields' => array(
					array(
						'type'  => 'date',
						'name'  => 'search[date_from]',
						'value' => isset( $search['date_from'] ) ? $search['date_from'] : '',
					),
					_l( 'to' ),
					array(
						'type'  => 'date',
						'name'  => 'search[date_to]',
						'value' => isset( $search['date_to'] ) ? $search['date_to'] : '',
					),
				)
			),
			'name'     => array(
				'title' => _l( 'Name:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[generic]',
					'value' => isset( $search['generic'] ) ? $search['generic'] : '',
					'size'  => 20,
				)
			),
			'amount'   => array(
				'title'  => _l( 'Amount:' ),
				'fields' => array(
					array(
						'type'  => 'currency',
						'name'  => 'search[amount_from]',
						'value' => isset( $search['amount_from'] ) ? $search['amount_from'] : '',
					),
					_l( 'to' ),
					array(
						'type'  => 'currency',
						'name'  => 'search[amount_to]',
						'value' => isset( $search['amount_to'] ) ? $search['amount_to'] : '',
					),
				)
			),
			'account'  => array(
				'title' => _l( 'Account:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[finance_account_id]',
					'value'   => isset( $search['finance_account_id'] ) ? $search['finance_account_id'] : '',
					'options' => $accounts_rel,
				)
			),
			'category' => array(
				'title' => _l( 'Category:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[finance_category_id]',
					'value'   => isset( $search['finance_category_id'] ) ? $search['finance_category_id'] : '',
					'options' => $categories_rel,
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );
	?>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
		<thead>
		<tr class="title">
			<th><?php echo _l( 'Next Due Date' ); ?></th>
			<th><?php echo _l( 'Name' ); ?></th>
			<th><?php echo _l( 'Credit' ); ?></th>
			<th><?php echo _l( 'Debit' ); ?></th>
			<th><?php echo _l( 'Recurring Period' ); ?></th>
			<th><?php echo _l( 'Last Transaction' ); ?></th>
			<th><?php echo _l( 'Account' ); ?></th>
			<th><?php echo _l( 'Categories' ); ?></th>
			<th><?php echo _l( 'Balance' ); ?></th>
			<th><?php echo _l( 'Record' ); ?><?php _h( 'Click the "record" button once an transaction has been completed, this will schedule the next reminder.' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php
		$c = 0;
		foreach ( $upcoming_finances as $recurring ) {
			$show_record_button = true;
			$days               = 0;
			?>
			<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
				<td>
					<?php
					$next_due = strtotime( $recurring['next_due_date'] );
					if ( ! $recurring['next_due_date'] || $recurring['next_due_date'] == '0000-00-00' ) {
						echo _l( '(recurring finished)' );
						$show_record_button = false;
					} else if ( $next_due < time() ) {
						echo '<span class="important">';
						echo print_date( $recurring['next_due_date'] );
						echo '</span>';
					} else {
						echo print_date( $recurring['next_due_date'] );
					}
					if ( $show_record_button ) {
						$days = ceil( ( ( $next_due + 1 ) - time() ) / 86400 );
						if ( abs( $days ) == 0 ) {
							_e( '(today)' );
						} else {
							_e( ' (%s days)', $days );
						}
					}

					if ( isset( $recurring['url'] ) && $recurring['url'] ) {
						$show_record_button = false;
					}
					?>
				</td>
				<td class="row_action">
					<?php echo isset( $recurring['url'] ) && $recurring['url'] ? $recurring['url'] : module_finance::link_open_recurring( $recurring['finance_recurring_id'], true, $recurring ); ?>
				</td>
				<td>
					<?php if ( $recurring['type'] == 'i' ) {
						// todo - calculate balance during the data gathering stage incase a search is performed that returns a subset of available transactions
						$balance += $recurring['amount']; ?><span class="success_text">
						+<?php echo dollar( $recurring['amount'], true, $recurring['currency_id'] ); ?></span><?php } ?>
				</td>
				<td>
					<?php if ( $recurring['type'] == 'e' ) {
						// todo - calculate balance during the data gathering stage incase a search is performed that returns a subset of available transactions
						$balance -= $recurring['amount']; ?><span class="error_text">
						-<?php echo dollar( $recurring['amount'], true, $recurring['currency_id'] ); ?></span><?php } ?>
				</td>
				<td>
					<?php
					if ( isset( $recurring['recurring_text'] ) && strlen( $recurring['recurring_text'] ) ) {
						echo $recurring['recurring_text'];
					} else if ( ! $recurring['days'] && ! $recurring['months'] && ! $recurring['years'] ) {
						echo _l( 'Once off' );
					} else {
						$bits = array();
						if ( $recurring['days'] > 0 ) {
							$bits[] = _l( '%s days', $recurring['days'] );
						}
						if ( $recurring['months'] > 0 ) {
							$bits[] = _l( '%s months', $recurring['months'] );
						}
						if ( $recurring['years'] > 0 ) {
							$bits[] = _l( '%s years', $recurring['years'] );
						}
						echo _l( 'Every %s between %s and %s', implode( ', ', $bits ), ( $recurring['start_date'] && $recurring['start_date'] != '0000-00-00' ) ? print_date( $recurring['start_date'] ) : 'now', ( $recurring['end_date'] && $recurring['end_date'] != '0000-00-00' ) ? print_date( $recurring['end_date'] ) : 'forever' );
					}
					?>
				</td>
				<td>
					<?php
					if ( isset( $recurring['last_transaction_text'] ) && $recurring['last_transaction_text'] ) {
						echo $recurring['last_transaction_text'];
					} else if ( ! $recurring['last_transaction_finance_id'] ) {
						_e( 'Never' );
					} else {
						echo _l( '%s on %s', currency( $recurring['last_amount'] ), print_date( $recurring['last_transaction_date'] ) );
					}
					?>
				</td>
				<td>
					<?php echo htmlspecialchars( $recurring['account_name'] ); ?>
				</td>
				<td>
					<?php echo $recurring['categories']; ?>
				</td>
				<td>
					<?php echo dollar( $balance ); ?>
				</td>
				<td>
					<?php if ( $show_record_button ) {
						$link                                 = module_finance::link_open_record_recurring( $recurring['finance_recurring_id'] );
						$_SESSION['_finance_recurring_ids'][] = array( $recurring['finance_recurring_id'], $link );
						?>
						<a href="<?php echo $link; ?>"
						   class="uibutton"<?php if ( $days > 10 ) { ?> onclick="return confirm('<?php echo addcslashes( _l( 'This transaction is not due for %s days, are you sure you want to record it?', $days ), "'" ); ?>');" <?php } ?>><?php _e( 'Record' ); ?></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
</form>
<!-- end -->