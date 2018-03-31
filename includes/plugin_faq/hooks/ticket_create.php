<?php

$products               = module_faq::get_faq_products_rel();
$current_faq_product_id = false;
if ( isset( $_REQUEST['faq_product_id'] ) ) {
	$current_faq_product_id = (int) $_REQUEST['faq_product_id'];
}
?>
<h3><?php echo _l( 'Product Details' ); ?></h3>
<table border="0" cellspacing="0" cellpadding="2" class="tableclass tableclass_form tableclass_full">
	<tbody>
	<tr>
		<th class="width1">
			<?php echo _l( 'Product' ); ?>
		</th>
		<td>
			<?php echo print_select_box( $products, 'faq_product_id', $current_faq_product_id, '', true ); ?>
			<?php _e( 'Please select which product you are enquiring about' ); ?>
		</td>
	</tr>
	</tbody>
	<tbody id="faq_product_area">
	</tbody>
</table>


<script type="text/javascript">
    var ajax_faq_xhr = false;
    var dofaqcheck = function () {
        // post to server for authentication.
        try {
            ajax_faq_xhr.abort();
        } catch (err) {
        }
        ajax_faq_xhr = $.ajax({
            url: '<?php echo module_faq::link_external_faq_ticket();?>',
            data: {
                faq_product_id: this.value
            },
            type: "POST",
            dataType: 'script'
        });
    };
    $(function () {
        $('#faq_product_id').change(dofaqcheck).keyup(dofaqcheck);
			<?php if($current_faq_product_id){ ?>
        $('#faq_product_id').change();
			<?php } ?>
    });
</script>
