<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
	<tbody>
	<tr>
		<th class="width1">
			<?php echo _l( 'Contact Name' ); ?>
		</th>
		<td>
			<?php echo $user_data['name']; ?>
			<a href="<?php echo $this->link_open_contact( $user_id ); ?>">&raquo;</a>
		</td>
	</tr>
	<tr>
		<th>
			<?php echo _l( 'Phone' ); ?>
		</th>
		<td>
			<?php echo $user_data['phone']; ?>
			&nbsp;&nbsp;<strong>Ext. <?php echo $user_data['phone_ext']; ?></strong>
		</td>
	</tr>
	<tr>
		<th>
			<?php echo _l( 'Mobile' ); ?>
		</th>
		<td>
			<?php echo $user_data['mobile']; ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php echo _l( 'Fax' ); ?>
		</th>
		<td>
			<?php echo $user_data['fax']; ?>
		</td>
	</tr>
	<tr>
		<th>
			<?php echo _l( 'Email' ); ?>
		</th>
		<td>
			<a href="mailto:<?php echo $user_data['email']; ?>"><?php echo $user_data['email']; ?></a>
		</td>
	</tr>
	</tbody>
</table>