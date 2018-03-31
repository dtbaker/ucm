<?php

if ( ! isset( $_REQUEST['iframe'] ) ) {
	$link = module_faq::link_open_list( isset( $_REQUEST['faq_product_id'] ) ? $_REQUEST['faq_product_id'] : false );
	$link .= '&iframe&display_mode=iframe';
	echo '<iframe src="' . $link . '" style="width:100%; height:90%; border:0;" class="autosize" frameborder="0"></iframe>';
} else {

	$search = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
	if ( isset( $_REQUEST['faq_product_id'] ) && $_REQUEST['faq_product_id'] ) {
		$search['faq_product_id'] = $_REQUEST['faq_product_id'];
	}

	$faqs = module_faq::get_faqs( $search );


	$header = array(
		'title'  => _l( 'FAQs' ),
		'type'   => 'h2',
		'main'   => true,
		'button' => array(),
	);
	if ( module_faq::can_i( 'create', 'FAQ' ) ) {
		$header['button'] = array(
			'url'   => module_faq::link_open( 'new' ),
			'title' => _l( 'Add New FAQ' ),
			'type'  => 'add',
		);
	}
	print_heading( $header );

	?>


	<form action="" method="POST">

	<input type="hidden" name="customer_id"
	       value="<?php echo isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : ''; ?>">


	<?php $search_bar = array(
		'elements' => array(
			'name'   => array(
				'title' => _l( 'Question:' ),
				'field' => array(
					'type'  => 'text',
					'name'  => 'search[question]',
					'value' => isset( $search['question'] ) ? $search['question'] : '',
				)
			),
			'status' => array(
				'title' => _l( 'Product:' ),
				'field' => array(
					'type'    => 'select',
					'name'    => 'search[faq_product_id]',
					'value'   => isset( $search['faq_product_id'] ) ? $search['faq_product_id'] : '',
					'options' => module_faq::get_faq_products_rel(),
				)
			),
		)
	);
	echo module_form::search_bar( $search_bar );


	?>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
		<thead>
		<tr class="title">
			<th><?php echo _l( 'Question' ); ?></th>
			<th><?php echo _l( 'Linked FAQ Products' ); ?></th>
			<?php //if(module_faq::can_i('edit','FAQ')){ 
			?>
			<th><?php echo _l( 'Action' ); ?></th>
			<?php //} 
			?>
		</tr>
		</thead>
		<tbody>
		<?php
		$c        = 0;
		$products = module_faq::get_faq_products_rel();
		foreach ( $faqs as $faq_id => $data ) {
			$faq = module_faq::get_faq( $faq_id );
			?>
			<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
				<td nowrap="">
					<?php if ( module_faq::can_i( 'edit', 'FAQ' ) ) {
						echo module_faq::link_open( $faq_id, true );
					} else {
						?> <a
							href="<?php echo str_replace( 'display_mode=iframe', '', module_faq::link_open_public( $faq_id ) ); ?>"
							target="_blank"><?php echo htmlspecialchars( $faq['question'] ); ?></a> <?php
					}
					?>
				</td>
				<td>
					<?php foreach ( $faq['faq_product_ids'] as $faq_product_id ) {
						echo module_faq::link_open_faq_product( $faq_product_id, true ) . " ";
					} ?>
				</td>
				<?php //if(module_faq::can_i('edit','FAQ')){ 
				?>
				<td>
					<a
						href="<?php echo str_replace( 'display_mode=iframe', '', module_faq::link_open_public( $faq_id, false ) ); ?>"
						target="_blank"
						onclick="window.parent.jQuery('#new_ticket_message').val(window.parent.jQuery('#new_ticket_message').val() + $(this).attr('href')); window.parent.jQuery('.ui-dialog-content').dialog('close'); return false;"><?php _e( 'Insert Link' ); ?></a>
				</td>
				<?php //} 
				?>
			</tr>
		<?php } ?>
		</tbody>
	</table>

<?php } ?>