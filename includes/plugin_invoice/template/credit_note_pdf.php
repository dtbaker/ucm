<html>
<head>
	<title>Credit Note Print Out</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<style type="text/css">
		body {
			font-family: arial;
			font-size: 17px;
		}

		.table,
		.table2 {
			border-collapse: collapse;
		}

		.table td,
		.table2 td.border {
			border: 1px solid #EFEFEF;
			border-collapse: collapse;
			padding: 4px;
		}

		.task_header {
			background-color: #000000;
			color: #FFFFFF;
		}
	</style>
</head>
<body>

<table width="100%" cellpadding="0" cellspacing="0">
	<tbody>
	<tr>
		<td width="10%">&nbsp;</td>
		<td width="80%">


			<table cellpadding="4" cellspacing="0" width="100%">
				<tbody>
				<tr>
					<td width="450" align="left" valign="top">
						<p>
							<font style="font-size: 1.6em;">
								<strong>Credit Note #:</strong> {INVOICE_NUMBER}<br/>
							</font> <br/>
							<font style="font-size: 1.6em;">
								<strong>Invoice #:</strong> {CREDIT_INVOICE_NUMBER}<br/>
							</font> <br/>
						</p>
					</td>
					<td align="right" valign="top">
						<p>
							<font style="font-size: 1.6em;"><strong>{TITLE}</strong></font>
							<br/>
							<font style="color: #333333;">
								[our company details]
							</font>
						</p>
					</td>
				</tr>
				<tr>
					<td align="left" valign="top">
						<strong>INVOICE TO:</strong><br/>
						{CUSTOMER_NAME} <br/>
						{CUSTOMER_ADDRESS} <br/>
						{CONTACT_NAME} {CONTACT_EMAIL} <br/>
					</td>
					<td align="right" valign="top">
						&nbsp;<br/>
						{PROJECT_TYPE}: <strong>{PROJECT_NAME}</strong> <br/>
						Job: <strong>{JOB_NAME}</strong>
					</td>
				</tr>
				</tbody>
			</table>
			<br/>
			{TASK_LIST}
			<br/>

		</td>
		<td width="10%">&nbsp;</td>
	</tr>
	</tbody>
</table>


</body>
</html>