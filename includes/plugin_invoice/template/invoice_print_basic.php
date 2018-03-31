<html>
<head>
	<title>Invoice</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			font-family: Arial;
			font-size: 10pt;
			color: #000;
		}

		body {
			width: 100%;
			font-family: Arial;
			font-size: 10pt;
			margin: 0;
			padding: 0;
		}

		p {
			margin: 0;
			padding: 0;
		}

		#wrapper {
			width: auto;
			margin: 0 15mm;
		}

		.page {
			page-break-after: always;
		}

		table {
			border-left: 1px solid #ccc;
			border-top: 1px solid #ccc;
			width: 100%;
			border-spacing: 0;
			border-collapse: collapse;

		}

		table td {
			border-right: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
			padding: 2mm;
		}

		table.heading {
			height: 50mm;
		}

		h1.heading {
			font-size: 14pt;
			color: #000;
			font-weight: normal;
		}

		h2.heading {
			font-size: 9pt;
			color: #000;
			font-weight: normal;
		}

		hr {
			color: #ccc;
			background: #ccc;
		}

		#invoice_body {
		}

		#invoice_body, #invoice_total {
			width: 100%;
		}

		#invoice_body table, #invoice_total table {
			width: 100%;
			border-left: 1px solid #ccc;
			border-top: 1px solid #ccc;

			border-spacing: 0;
			border-collapse: collapse;

			margin-top: 5mm;
		}

		#invoice_body table td, #invoice_total table td {
			text-align: center;
			font-size: 9pt;
			border-right: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
			padding: 2mm 0;
		}

		#invoice_body table td.mono, #invoice_total table td.mono {
			font-family: monospace;
			text-align: right;
			padding-right: 3mm;
			font-size: 10pt;
		}

		#footer {
			width: auto;
			margin: 0 15mm;
			padding-bottom: 3mm;
		}

		#footer table {
			width: 100%;
			border-left: 1px solid #ccc;
			border-top: 1px solid #ccc;

			background: #eee;

			border-spacing: 0;
			border-collapse: collapse;
		}

		#footer table td {
			width: 25%;
			text-align: center;
			font-size: 9pt;
			border-right: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
		}
	</style>
</head>
<body>
<div id="wrapper">

	<p style="text-align:center; font-weight:bold; padding-top:5mm;">TAX INVOICE</p>
	<br/>
	<table class="heading" style="width:100%;">
		<tr>
			<td style="width:80mm;">
				<h1 class="heading">Your Company Here</h1>
				<h2 class="heading">
					Your Address Here<br/>
					Your Address Here<br/>
					Your Address Here<br/>

					Website : www.website.com<br/>
					E-mail : info@website.com<br/>
					Phone : +1 - 123456789
				</h2>
			</td>
			<td rowspan="2" valign="top" align="right" style="padding:3mm;">
				<table>
					<tr>
						<td>Invoice No :</td>
						<td>{INVOICE_NUMBER}</td>
					</tr>
					<tr>
						<td>Invoice Date :</td>
						<td>{DATE_CREATE}</td>
					</tr>
					<tr>
						<td>Due Date :</td>
						<td>{DATE_DUE}</td>
					</tr>
					<tr>
						<td>Paid Date :</td>
						<td>{DATE_PAID}</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>
				<b>Buyer</b> :<br/>
				{CUSTOMER_NAME}<br/>
				{CUSTOMER_ADDRESS}<br/>
				{CUSTOMER_PHONE}<br/>
				{CUSTOMER_EMAIL}
			</td>
		</tr>
	</table>


	<div id="content">

		<div id="invoice_body">
			{TASK_LIST}
		</div>

		<table style="width:100%; ">
			<tr>
				<td valign="top">
					{PAYMENT_METHODS}<br/>
					{PAYMENT_HISTORY}
				</td>
			</tr>
		</table>
	</div>

	<br/>

</div>

<htmlpagefooter name="footer">
	<hr/>
	<div id="footer">
		<table>
			<tr>
				<td>Software Solutions</td>
				<td>Mobile Solutions</td>
				<td>Web Solutions</td>
			</tr>
		</table>
	</div>
</htmlpagefooter>
<sethtmlpagefooter name="footer" value="on"/>

</body>
</html>