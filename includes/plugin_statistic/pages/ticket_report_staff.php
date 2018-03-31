<?php


$page_title = _l( 'Ticket Staff Report' );

$search = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array(
	'date_from' => print_date( date( 'Y-m-d', strtotime( '-1 month' ) ) ),
	'date_to'   => print_date( date( 'Y-m-d' ) )
);

if ( ! module_statistic::can_i( 'view', 'Ticket Staff Report' ) ) {
	redirect_browser( _BASE_HREF );
}
$staff_members = module_ticket::get_ticket_staff();
// we get all the ticket messages that were sent within this time period
// (without autoresponders?)
$sql      = "SELECT * FROM `" . _DB_PREFIX . "ticket_message` WHERE message_time >= " . (int) strtotime( input_date( $search['date_from'] ) ) . " AND message_time <= " . (int) strtotime( input_date( $search['date_to'] ) );
$messages = qa( $sql );

?>

<form action="" method="post" id="statistic_form">
	<?php
	$search_bar = array(
		'elements' => array(
			'date' => array(
				'title'  => _l( 'Date:' ),
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
		)
	);
	echo module_form::search_bar( $search_bar ); ?>

</form>

<p>&nbsp;</p>


<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
	<thead>
	<tr class="title">
		<th><?php echo _l( 'Staff Member' ); ?></th>
		<th><?php echo _l( 'Messages Sent' ); ?></th>
		<th><?php echo _l( 'Private Messages Sent' ); ?></th>
		<th><?php echo _l( 'Total Tickets' ); ?></th>
		<th><?php echo _l( 'Resolved Tickets' ); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
	$c = 0;
	foreach ( $staff_members as $staff_member ) {
		$staff_messages         = array();
		$staff_private_messages = array();
		$staff_tickets          = array();
		foreach ( $messages as $message ) {
			if ( $message['create_user_id'] == $staff_member['user_id'] ) {
				if ( isset( $message['private_message'] ) && $message['private_message'] ) {
					$staff_private_messages[] = $message;
				} else {
					$staff_messages[] = $message;
				}
				if ( ! isset( $staff_tickets[ $message['ticket_id'] ] ) ) {
					$staff_tickets[ $message['ticket_id'] ] = array();
				}
				$staff_tickets[ $message['ticket_id'] ][] = $message;
			}
		}
		?>
		<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
			<td class="row_action" nowrap="">
				<?php echo module_user::link_open( $staff_member['user_id'], true ); ?>
			</td>
			<td>
				<?php echo count( $staff_messages ); ?> in <?php echo count( $staff_tickets ); ?> tickets
			</td>
			<td>
				<?php
				echo count( $staff_private_messages );
				?>
			</td>
			<td>
				<?php
				$sql     = "SELECT * FROM `" . _DB_PREFIX . "ticket` WHERE last_message_timestamp >= " . (int) strtotime( input_date( $search['date_from'] ) ) . " AND last_message_timestamp <= " . (int) strtotime( input_date( $search['date_to'] ) ) . " AND assigned_user_id = " . (int) $staff_member['user_id'];
				$tickets = qa( $sql );
				echo count( $tickets );
				?>
			</td>
			<td>
				<?php
				$r = 0;
				foreach ( $tickets as $ticket ) {
					if ( $ticket['status_id'] == _TICKET_STATUS_RESOLVED_ID ) {
						$r ++;
					}
				}
				echo $r;
				?>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>