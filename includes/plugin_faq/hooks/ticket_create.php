<?php

$products               = module_faq::get_faq_products_rel();
$current_faq_product_id = false;
if ( isset( $_REQUEST['faq_product_id'] ) ) {
	$current_faq_product_id = (int) $_REQUEST['faq_product_id'];
}

ob_start();
echo print_select_box( $products, 'faq_product_id', $current_faq_product_id, '', true );
_e( 'Please select which product you are enquiring about' );
$product_selector = ob_get_clean();

echo module_form::generate_fieldset( array(
	// No fieldset id or heading: this renders on a public page, so it must not read or
	// write per-user field display settings in the form table. The title is printed here
	// rather than passed as 'heading' for the same reason - a heading derives an id.
	'title'         => 'Product Details',
	'class'         => 'tableclass tableclass_form tableclass_full',
	'elements'      => array(
		'product' => array(
			'title'  => 'Product',
			'fields' => array( $product_selector ),
		),
	),
	// includes/plugin_faq/faq.php answers a product change with
	// $('#faq_product_area').html('<tr>...</tr>'), so the target has to stay a tbody -
	// table rows need a row parent or the browser hoists them out of the table. A fieldset
	// emits one tbody and has no option for a second, so the target gets a table of its
	// own, directly below and in the same class so the two read as one block. Nothing in
	// the injected markup is a form control; it is FAQ links and a line of guidance.
	'elements_after' => '<table border="0" cellspacing="0" cellpadding="2"'
	                    . ' class="tableclass tableclass_form tableclass_full">'
	                    . '<tbody id="faq_product_area"></tbody></table>',
) );
?>


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
			<?php if ($current_faq_product_id) { ?>
        $('#faq_product_id').change();
			<?php } ?>
    });
</script>
