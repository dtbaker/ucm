<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form">
	<tbody>
	<tr>
		<th class="width1">
			<?php echo _l( 'Customer Name' ); ?>
		</th>
		<td>
			<?php echo $customer_data['customer_name']; ?>
			<a href="<?php echo module_customer::link_open( $customer_id ); ?>">&raquo;</a>
		</td>
	</tr>
	</tbody>
</table>