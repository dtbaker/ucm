<?php

$user_id = (int) $_SESSION['_user_id'];

if ( $user_id ) {
	$user = $module->get_user( $user_id );
	//$user_notes = $module->get_user_notes($user_id);
	?>

	<h2><?php echo _l( 'Your Details' ); ?></h2>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass">
		<tr>
			<td width="13%"><?php echo _l( 'Full Name' ); ?></td>
			<td width="37%"><?php echo( $user['name'] ); ?></td>
			<td width="13%">

			</td>
			<td width="37%"></td>
		</tr>
		<tr>
			<td><?php echo _l( 'Email Address' ); ?></td>
			<td><?php echo( $user['email'] ); ?></td>
			<td></td>
			<td></td>
		</tr>


		<tr>
			<td><?php echo _l( 'Phone' ); ?></td>
			<td><?php echo( $user['phone'] ); ?></td>
		</tr>
		<tr>
			<td><?php echo _l( 'Fax' ); ?></td>
			<td><?php echo( $user['fax'] ); ?></td>
		</tr>
		<tr>
			<td><?php echo _l( 'Mobile' ); ?></td>
			<td><?php echo( $user['mobile'] ); ?></td>
		</tr>

	</table>

<?php } ?>