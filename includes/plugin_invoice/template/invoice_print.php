<html>
<head>
	<title>Invoice</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<style type="text/css">
		body {
			font-family: Helvetica, sans-serif;
			padding: 0;
			margin: 0;
		}

		td {
			font-family: Helvetica, sans-serif;
			padding: 2px;
		}

		h3 {
			font-size: 22px;
			font-weight: bold;
			margin: 10px 0 10px 0;
			padding: 0 0 5px 0;
			border-bottom: 1px solid #6f6f6f;
			width: 100%;
		}

		.style11 {
			font-size: 24px;
			font-weight: bold;
			margin: 0;
			padding: 0;
		}

		.task_header,
		.task_header th {
			background-color: #e8e8e8;
			color: #6f6f6f;
			font-weight: bold;
		}

		tr.odd {
			background-color: #f9f9f9;
		}
	</style>
</head>
<body>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
	<tbody>
	<tr>
		<td colspan="2" align="left" valign="top"><img title="Logo"
		                                               src="http://ultimateclientmanager.com/images/logo_ucm.png" alt="Logo"
		                                               width="202" height="60"/></td>
		<td colspan="2" align="left" valign="top"><span class="style11">TAX INVOICE</span>
			<p>{if:IS_INVOICE_PAID}<span style="font-size: 1.6em;"><strong>INVOICE PAID</strong></span>{endif:IS_INVOICE_PAID}
			</p>
		</td>
	</tr>
	<tr>
		<td width="12%">&nbsp;</td>
		<td width="43%">&nbsp;</td>
		<td width="14%"><strong>Invoice No:</strong></td>
		<td width="31%">{INVOICE_NUMBER}</td>
	</tr>
	<tr>
		<td width="12%"><strong>ABN:</strong></td>
		<td width="43%">12 345 678 912</td>
		<td><strong>Invoice Date:</strong></td>
		<td>{DATE_CREATE}</td>
	</tr>
	<tr>
		<td><strong>Email: </strong></td>
		<td>your@company.com</td>
		<td><strong>Due Date:</strong></td>
		<td>{DATE_DUE}</td>
	</tr>
	<tr>
		<td><strong>Web: </strong></td>
		<td>www.company.com</td>
		<td><strong>Paid Date:</strong></td>
		<td>{DATE_PAID}</td>
	</tr>
	</tbody>
</table>
<h3>RECIPIENT</h3>
<table style="width: 100%;" border="0" cellspacing="0" cellpadding="1" align="center">
	<tbody>
	<tr>
		<td width="12%" valign="top"><strong>Company:</strong></td>
		<td width="43%" valign="top">{CUSTOMER_NAME}</td>
		<td width="14%" valign="top"><strong>Email:</strong></td>
		<td width="31%" valign="top">{CONTACT_EMAIL}</td>
	</tr>
	<tr>
		<td valign="top"><strong>Contact:</strong></td>
		<td valign="top">{CONTACT_NAME}</td>
		<td valign="top"><strong>{PROJECT_TYPE}:</strong>&nbsp;</td>
		<td valign="top">{PROJECT_NAME}&nbsp;</td>
	</tr>
	<tr>
		<td valign="top"><strong>Phone:</strong></td>
		<td valign="top">{CONTACT_PHONE}</td>
		<td valign="top"><strong>Job:</strong></td>
		<td valign="top">{JOB_NAME} {SUBSCRIPTION_NAME}&nbsp;</td>
	</tr>
	</tbody>
</table>
<h3>INVOICE DETAILS</h3>
<div>{TASK_LIST}</div>
<div style="width: 100%;page-break-inside:avoid;">
	<h3>PAYMENT</h3>
	<div>
		{PAYMENT_METHODS} {PAYMENT_HISTORY}
	</div>
</div>
</body>
</html>