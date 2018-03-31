<style type="text/css">
	div#credit-card {
		display: table;
		width: 540px;
		margin: 0 auto;

		border: 1px solid #c1c2c8;
		background-color: #eeeeee;
		-moz-border-radius: 4px;
		-webkit-border-radius: 4px;
		border-radius: 4px 4px;
	}

	div#credit-card > header {
		padding: 10px;
		border-bottom: 1px solid #c1c2c8;

		background: #ffffff; /* Old browsers */
		background: -moz-linear-gradient(top, #ffffff 0%, #dde0e6 100%); /* FF3.6+ */
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #ffffff), color-stop(100%, #dde0e6)); /* Chrome,Safari4+ */
		background: -webkit-linear-gradient(top, #ffffff 0%, #dde0e6 100%); /* Chrome10+,Safari5.1+ */
		background: -o-linear-gradient(top, #ffffff 0%, #dde0e6 100%); /* Opera 11.10+ */
		background: -ms-linear-gradient(top, #ffffff 0%, #dde0e6 100%); /* IE10+ */
		background: linear-gradient(to bottom, #ffffff 0%, #dde0e6 100%); /* W3C */
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#dde0e6', GradientType=0); /* IE6-9 */

		-webkit-border-top-left-radius: 3px;
		-webkit-border-top-right-radius: 3px;
		-moz-border-radius-topleft: 3px;
		-moz-border-radius-topright: 3px;
		border-top-left-radius: 3px;
		border-top-right-radius: 3px;
	}

	div#credit-card > header .title {
		color: #666;
		font-size: 13px;
		padding: 10px 0;
		padding-left: 55px;
		background: no-repeat 20px;
		line-height: 32px;
	}

	div#credit-card > header .close {
		display: table;
		float: right;
		opacity: 0.5;
	}

	div#credit-card > header .close:hover {
		display: table;
		float: right;
		opacity: 1;
	}

	div#credit-card > header .title strong {
		color: #333;
	}

	div#credit-card {
		padding: 30px;
	}

	div#credit-card .table {
		display: table;
		width: 100%;
	}

	div#credit-card .table > .row {
		display: table-row;
		width: 100%;
	}

	div#credit-card .table > .row > div {
		display: table-cell;
		padding-top: 15px;
	}

	div#credit-card .table > .row > div.label {
		min-width: 100px;
		width: 120px;
	}

	div#credit-card .form-fields .label {
		color: #333;
		font-weight: bold;
		font-size: 14px;
	}

	div#credit-card .form-fields .input {
		padding-left: 10px;
		width: 100%;
		color: #666;
		font-weight: bold;
		position: relative;
	}

	div#credit-card .form-fields .valid {
		width: 32px;
		text-align: right;
		padding-left: 10px;

		vertical-align: top;
	}

	div#credit-card .form-fields .valid img {
		display: block;
		margin-top: 2px;
	}

	div#credit-card .form-fields .full input, div#credit-card .form-fields .full select {
		width: 100%;
		padding: 10px;
	}

	div#credit-card .form-fields input, div#credit-card .form-fields select {
		background-color: #f7f7f7;
		border: 1px solid #d4d4d4;
		color: #717171;
		cursor: pointer;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px 5px;
		-moz-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
		-webkit-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
		box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
	}

	div#credit-card .form-fields input:hover, div#credit-card .form-fields select:hover {
		-moz-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.4);
		-webkit-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.4);
		box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.4);
	}

	div#credit-card .form-fields .size50 input, div#credit-card .form-fields .size50 select {
		display: inline;
		padding-left: 10px;
		width: 44%;
		padding: 10px;
		cursor: pointer;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px 5px;
	}

	div#credit-card .form-fields .size50 input:not(:only-child) {
		margin-right: 19px;
	}

	div#credit-card .form-fields .size50 input:last-child {
		margin-right: 0;
	}

	div#credit-card .form-fields .size50 input:only-child {
		margin-right: 5px;
	}

	div#credit-card .form-fields .error {
		display: block;
		color: #f34755;
		font-size: 11px;
		margin-top: 5px;
		font-weight: normal;
	}

	/* Style Select Boxes */
	span.customStyleSelectBox {
		-moz-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
		-webkit-box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
		box-shadow: 0 0 4px -1px rgba(0, 0, 0, 0.2);
		cursor: pointer;
		padding: 8px;
		background-color: #f7f7f7;
		border: 1px solid #d4d4d4;
		color: #717171;
		-moz-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px 5px;
		line-height: 11px;
		width: 100% !important
	}

	.size50 span.customStyleSelectBox {
		width: 49% !important;
	}

	.customStyleSelectBoxInner {
		padding: 7px 0;
		background: url(../images/arrow.png) no-repeat center right;
		width: 100% !important;
		height: 24px;
	}

	input[type=submit] {
		cursor: pointer;
		padding: 10px;
		border: 1px solid #0945b9;
		color: white;
		font-weight: bold;
		-moz-border-radius: 3px;
		-webkit-border-radius: 3px;
		border-radius: 3px 3px;
		-moz-box-shadow: inset 0 3px 0 -2px rgba(255, 255, 255, 0.6);
		-webkit-box-shadow: inset 0 3px 0 -2px rgba(255, 255, 255, 0.6);
		box-shadow: inset 0 3px 0 -2px rgba(255, 255, 255, 0.6);
		margin-bottom: 30px;

		background: #5e9af8; /* Old browsers */
		background: -moz-linear-gradient(top, #5e9af8 0%, #2f6af2 100%); /* FF3.6+ */
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #5e9af8), color-stop(100%, #2f6af2)); /* Chrome,Safari4+ */
		background: -webkit-linear-gradient(top, #5e9af8 0%, #2f6af2 100%); /* Chrome10+,Safari5.1+ */
		background: -o-linear-gradient(top, #5e9af8 0%, #2f6af2 100%); /* Opera 11.10+ */
		background: -ms-linear-gradient(top, #5e9af8 0%, #2f6af2 100%); /* IE10+ */
		background: linear-gradient(to bottom, #5e9af8 0%, #2f6af2 100%); /* W3C */
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#5e9af8', endColorstr='#2f6af2', GradientType=0); /* IE6-9 */
	}

	input[type=submit]:active {
		background: #2867ef;
	}
</style>
<h1>Payment for invoice #{INVOICE_NUMBER}</h1>
<div id="credit-card">
	<div class="table form-fields">
		<div class="row">
			<div class="label">Cardholder's First Name:</div>
			<div class="input full"><input type="text" name="first_name" id="first_name" value="{FIRST_NAME}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Cardholder's Last Name:</div>
			<div class="input full"><input type="text" name="last_name" id="last_name" value="{LAST_NAME}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Company:</div>
			<div class="input full"><input type="text" name="company" id="company" value="{CUSTOMER_NAME}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Card Number:</div>
			<div class="input full"><input type="text" name="number" id="number" value=""/></div>
			<div class="valid"></div>
		</div>
		<div class="row name">
			<div class="label">Expires On:</div>
			<div class="input size50">
				<select name="month" class="styled">
					<option value="">Select Month</option>
					<option value="1">January</option>
					<option value="2">February</option>
					<option value="3">March</option>
					<option value="4">April</option>
					<option value="5">May</option>
					<option value="6">June</option>
					<option value="7">July</option>
					<option value="8">August</option>
					<option value="9">September</option>
					<option value="10">October</option>
					<option value="11">November</option>
					<option value="12">December</option>
				</select>
				<select name="year" class="styled">
					<option value="">Select Year</option>
					<option value="13">2013</option>
					<option value="14">2014</option>
					<option value="15">2015</option>
					<option value="16">2016</option>
					<option value="17">2017</option>
					<option value="18">2018</option>
					<option value="19">2019</option>
					<option value="20">2020</option>
					<option value="21">2021</option>
					<option value="22">2022</option>
					<option value="23">2023</option>
					<option value="24">2024</option>
				</select>
			</div>
			<div class="valid"></div>
		</div>
		<div class="row name">
			<div class="label">CVV Number:</div>
			<div class="input size50"><input type="text" name="cvv" id="cvv" value=""/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Address:</div>
			<div class="input full"><input type="text" name="address" id="address" value="{ADDRESS_LINE_1}{ADDRESS_LINE_2}"/>
			</div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">City:</div>
			<div class="input full"><input type="text" name="city" id="city" value="{ADDRESS_SUBURB}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">State/Province:</div>
			<div class="input full"><input type="text" name="state" id="state" value="{ADDRESS_STATE}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Zip/Postal Code:</div>
			<div class="input full"><input type="text" name="zip" id="zip" value="{ADDRESS_POST_CODE}"/></div>
			<div class="valid"></div>
		</div>
		<div class="row">
			<div class="label">Country:</div>
			<div class="input full"><input type="text" name="country" id="country" value="{ADDRESS_COUNTRY}"/></div>
			<div class="valid"></div>
		</div>
	</div>
	<input type="submit" style="float:right" value="Pay {AMOUNT} with Credit Card"/>
</div>
<p>&nbsp;</p>
<p>
	<a href="{CANCEL_URL}">Cancel</a>
</p>