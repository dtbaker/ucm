<?php

$search = isset( $_REQUEST['search'] ) ? $_REQUEST['search'] : array();
if ( isset( $_REQUEST['faq_product_id'] ) && $_REQUEST['faq_product_id'] && ! isset( $search['faq_product_id'] ) ) {
	$search['faq_product_id'] = $_REQUEST['faq_product_id'];
}

$faqs = module_faq::get_faqs( $search );

$show_search  = isset( $_REQUEST['show_search'] ) ? $_REQUEST['show_search'] : true;
$show_header  = isset( $_REQUEST['show_header'] ) ? $_REQUEST['show_header'] : true;
$show_product = isset( $_REQUEST['show_product'] ) ? $_REQUEST['show_product'] : true;
?>


<?php if ( $show_search ) { ?>
	<form action="" method="<?php echo _DEFAULT_FORM_METHOD; ?>">

	<input type="hidden" name="customer_id"
	       value="<?php echo isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : ''; ?>">


	<table class="search_bar" width="100%">
		<tr>
			<td>
				<?php echo _l( 'Search Questions:' ); ?>
			</td>
			<td>
				<input type="text" name="search[question]"
				       value="<?php echo isset( $search['question'] ) ? htmlspecialchars( $search['question'] ) : ''; ?>">
			</td>
			<td>
				<?php echo _l( 'Search Products:' ); ?>
			</td>
			<td>
				<?php echo print_select_box( module_faq::get_faq_products_rel(), 'search[faq_product_id]', isset( $search['faq_product_id'] ) ? $search['faq_product_id'] : '' ); ?>
			</td>
			<td class="search_action">
				<?php echo create_link( "Reset", "reset", module_faq::link_open_public( - 1 ) ); ?>
				<?php echo create_link( "Search", "submit" ); ?>
			</td>
		</tr>
	</table>
<?php } ?>


	<table width="100%" border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_rows">
		<?php if ( $show_header ) { ?>
			<thead>
			<tr class="title">
				<th><?php echo _l( 'Question' ); ?></th>
				<?php if ( $show_product ) { ?>
					<th><?php echo _l( 'Products' ); ?></th>
				<?php } ?>
			</tr>
			</thead>
		<?php } ?>
		<tbody>
		<?php
		$c        = 0;
		$products = module_faq::get_faq_products_rel();
		foreach ( $faqs as $data ) {
			$faq = module_faq::get_faq( $data['faq_id'] );
			?>
			<tr class="<?php echo ( $c ++ % 2 ) ? "odd" : "even"; ?>">
				<td nowrap="">
					<a
						href="<?php echo module_faq::link_open_public( $data['faq_id'], false ); ?>&faq_product_id=<?php echo isset( $search['faq_product_id'] ) ? (int) $search['faq_product_id'] : ''; ?>"><?php echo htmlspecialchars( $faq['question'] ); ?></a>
				</td>
				<?php if ( $show_product ) { ?>
					<td>
						<?php
						$items = array();
						foreach ( $faq['faq_product_ids'] as $faq_product_id ) {
							if ( module_faq::can_i( 'edit', 'FAQ' ) ) {
								$items[] = module_faq::link_open_faq_product( $faq_product_id, true );
							} else {
								$items[] = $products[ $faq_product_id ];
							}
						}
						echo implode( ', ', $items );
						?>
					</td>
				<?php } ?>
			</tr>
		<?php } ?>
		</tbody>
	</table>


<?php if ( $show_search ) { ?>
	</form>
<?php } ?>