<?php
if ( count( $subscriptions ) ) {
	ob_start();
	?>
	<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
		<tbody>
		<?php
		foreach ( $subscriptions as $subscription ) {
			if ( $subscription['subscription_owner_id'] ) {
				$subscription_owner = get_single( 'subscription_owner', 'subscription_owner_id', $subscription['subscription_owner_id'] );
				if ( count( $subscription_owner ) ) {
					?>
					<tr>
						<td>
							<?php
							switch ( $subscription_owner['owner_table'] ) {
								case 'member':
									$member_name = module_member::link_open( $subscription_owner['owner_id'], true );
									break;
								case 'website':
									$member_name = module_website::link_open( $subscription_owner['owner_id'], true );
									break;
								case 'customer':
									$member_name = module_customer::link_open( $subscription_owner['owner_id'], true );
									break;
							}
							$subscription_name = module_subscription::link_open( $subscription['subscription_id'], true );
							_e( 'This is an invoice for %s %s on the subscription: %s', $subscription_owner['owner_table'], $member_name, $subscription_name ); ?>
						</td>
					</tr>
					<?php
				}
			}
		}
		?>
		</tbody>
	</table>
	<?php
	$fieldset_data = array(
		'heading'         => array(
			'title' => _l( 'Subscriptions' ),
			'type'  => 'h3',
		),
		'elements_before' => ob_get_clean(),
	);
	echo module_form::generate_fieldset( $fieldset_data );
}