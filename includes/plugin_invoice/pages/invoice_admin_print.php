<?php

if ( ! $invoice_safe ) {
	die( 'failed' );
}

$invoice_id = isset( $_REQUEST['invoice_id'] ) ? (int) $_REQUEST['invoice_id'] : false;
if ( isset( $_REQUEST['go'] ) ) {
	$invoice = module_invoice::get_invoice( $invoice_id );
	// confirm customer access.
	if ( ! $invoice || $invoice['invoice_id'] != $invoice_id ) {
		echo 'invalid invoice id';
		exit;
	}
	if ( $invoice && $invoice['customer_id'] ) {
		$customer_test = module_customer::get_customer( $invoice['customer_id'] );
		if ( ! $customer_test || $customer_test['customer_id'] != $invoice['customer_id'] ) {
			echo 'invalid customer id';
			exit;
		}
	}
	if ( isset( $_REQUEST['htmlonly'] ) ) {
		echo module_invoice::invoice_html( $invoice_id, $invoice, 'pdf' );
		exit;
	}
	// send the actual invoice.
	// step1, generate the PDF for the invoice...
	$pdf_file = module_invoice::generate_pdf( $invoice_id );

	if ( $pdf_file && is_file( $pdf_file ) ) {
		// copied from public_print hook
		@ob_end_clean();
		@ob_end_clean();

		// send pdf headers and prompt the user to download the PDF

		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Cache-Control: private", false );
		header( "Content-Type: application/pdf" );
		header( "Content-Disposition: attachment; filename=\"" . basename( $pdf_file ) . "\";" );
		header( "Content-Transfer-Encoding: binary" );
		$filesize = filesize( $pdf_file );
		if ( $filesize > 0 ) {
			header( "Content-Length: " . $filesize );
		}
		// some hosting providershave issues with readfile()
		$read = readfile( $pdf_file );
		if ( ! $read ) {
			echo file_get_contents( $pdf_file );
		}

	}
	exit;

} else {

	// hack for multi print
	if ( isset( $_REQUEST['invoice_ids'] ) && $_REQUEST['invoice_ids'] ) {
		?>
		<?php print_heading( 'Print Multiple PDFs' ); ?>
		<form action="" method="post" id="printform">
			<input type="hidden" name="invoice_id" id="print_invoice_id" value="0">
			<input type="hidden" name="go" value="yes">
			<input type="hidden" name="print" value="1">
		</form>
		<script type="text/javascript">
        function generate(invoice_id, l) {
            $(l).html('Generating .... please wait');
            $('#print_invoice_id').val(invoice_id);
            $('#printform')[0].submit();
            return false;
        }
		</script>
		<p>Click on each link below to save the invoice as PDF</p>
		<ul>
			<?php
			foreach ( explode( ',', $_REQUEST['invoice_ids'] ) as $invoice_id ) {
				$invoice = module_invoice::get_invoice( $invoice_id );
				// todo: confirm invoice pemrissions, possible data slip
				?>
				<li><a href="#" onclick="return generate(<?php echo $invoice_id; ?>,this);"><?php echo $invoice['name']; ?></a>
				</li>
				<?php
			}
			?> </ul> <?php
	} else {

		?>

		<?php print_heading( 'Generating PDF' ); ?>

		<p><?php _e( 'Please wait...' ); ?></p>

		<?php if ( get_display_mode() == 'mobile' ) { ?>

			<script type="text/javascript">
          window.onload = function () {
              window.location.href = '<?php echo $module->link_generate( $invoice_id, array(
								'arguments' => array(
									'go'    => 1,
									'print' => 1
								),
								'page'      => 'invoice_admin',
								'full'      => false
							) );?>';
          }
			</script>

		<?php } else {

			?>

			<iframe src="<?php echo $module->link_generate( $invoice_id, array(
				'arguments' => array( 'go' => 1, 'print' => 1 ),
				'page'      => 'invoice_admin',
				'full'      => false
			) ); ?>" style="display:none;"></iframe>


		<?php } ?>

		<p><?php echo _l( 'After printing is complete you can <a href="%s">click here</a> return to invoice %s', module_invoice::link_open( $invoice_id ), module_invoice::link_open( $invoice_id, true ) ); ?></p>
		<p>&nbsp;</p>
		<p><?php echo _l( '<em>New:</em> Please <a href="%s" target="_blank">click here</a> to view the HTML invoice (this HTML page can be "Printed to PDF" using <a href="http://www.primopdf.com/" target="_blank">PrimoPDF</a> for better results).', $module->link_generate( $invoice_id, array(
				'arguments' => array(
					'go'       => 1,
					'print'    => 1,
					'htmlonly' => 1
				),
				'page'      => 'invoice_admin',
				'full'      => false
			) ) ); ?></p>

	<?php } ?>

<?php } ?>