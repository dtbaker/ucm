<?php

define( '_INVOICE_ACCESS_ALL', 'All invoices in system' );
define( '_INVOICE_ACCESS_JOB', 'Invoices from Jobs I have access to' );
define( '_INVOICE_ACCESS_STAFF', 'Only my Staff Invoices' );
define( '_INVOICE_ACCESS_CUSTOMER', 'Invoices from customers I have access to' );

define( '_INVOICE_PAYMENT_TYPE_NORMAL', 0 );
define( '_INVOICE_PAYMENT_TYPE_DEPOSIT', 1 );
define( '_INVOICE_PAYMENT_TYPE_CREDIT', 2 );
define( '_INVOICE_PAYMENT_TYPE_REFUND', 3 );
define( '_INVOICE_PAYMENT_TYPE_SUBSCRIPTION_CREDIT', 4 );
define( '_INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT', 5 );

define( '_TAX_CALCULATE_AT_END', 0 );
define( '_TAX_CALCULATE_INCREMENTAL', 1 );

define( '_DISCOUNT_TYPE_BEFORE_TAX', 0 );
define( '_DISCOUNT_TYPE_AFTER_TAX', 1 );

define( '_INVOICE_SUBSCRIPTION_PENDING', 1 );
define( '_INVOICE_SUBSCRIPTION_ACTIVE', 2 );
define( '_INVOICE_SUBSCRIPTION_FAILED', 3 );

class module_invoice extends module_base {

	public $links;
	public $invoice_types;

	public static function can_i( $actions, $name = false, $category = false, $module = false ) {
		if ( ! $module ) {
			$module = __CLASS__;
		}

		return parent::can_i( $actions, $name, $category, $module );
	}

	public static function get_class() {
		return __CLASS__;
	}

	public function init() {
		$this->links           = array();
		$this->invoice_types   = array();
		$this->module_name     = "invoice";
		$this->module_position = 18;

		$this->version = 2.809;
		//2.809 - 2018-03-31 - timer + invoice link
		//2.808 - 2017-07-23 - adding website invoies to website page.
		//2.807 - 2017-05-30 - invoice api
		//2.806 - 2017-05-28 - invoice api
		//2.805 - 2017-05-25 - invoice prefix fix
		//2.804 - 2017-05-22 - invoice email fix
		//2.803 - 2017-05-18 - invoice email fix
		//2.802 - 2017-05-02 - file path configuration
		//2.801 - 2017-05-02 - tax display at 0%
		//2.799 - 2017-05-02 - archive fix
		//2.798 - 2017-05-02 - big changes
		//2.797 - 2017-04-19 - start work on vendor invoices
		//2.796 - 2017-03-01 - archived feature
		//2.795 - 2017-02-27 - duplicate invoice number fix
		//2.794 - 2017-01-05 - fix invoice payment metadata removal bug
		//2.793 - 2017-01-04 - invoice_template_print improvement
		//2.792 - 2017-01-02 - invoice number incrementing fix
		//2.791 - 2016-12-18 - duplicate invoice number notification
		//2.790 - 2016-12-14 - invoice pdf fix
		//2.789 - 2016-11-29 - invoice default payment method
		//2.788 - 2016-11-23 - product fields in task list
		//2.787 - 2016-11-16 - fix icons
		//2.786 - 2016-11-01 - invoices directly from ticket
		//2.785 - 2016-09-29 - product extra fields in task template
		//2.784 - 2016-08-12 - quote task wysiwyg fix
		//2.783 - 2016-08-04 - show jobs in linked invoices
		//2.782 - 2016-07-19 - credit note improvements
		//2.781 - 2016-07-18 - wysiwyg editor for invoice line items
		//2.780 - 2016-07-10 - big update to mysqli
		//2.779 - 2016-05-15 - unit of measurement
		//2.778 - 2016-05-05 - basic inventory management
		//2.777 - 2016-02-02 - style improvement
		//2.776 - 2016-01-21 - search invoice based on company
		//2.775 - 2015-12-28 - menu speed up
		//2.774 - 2015-12-28 - invoice extra field permission fix
		//2.773 - 2015-12-28 - invoice task ordering fix
		//2.772 - 2015-12-27 - quote fields on invoice templates
		//2.771 - 2015-10-17 - item_tax template tag fix
		//2.770 - 2015-07-19 - invoice tax discount fix
		//2.769 - 2015-07-18 - hide job if disabled
		//2.768 - 2015-04-13 - credit note pdf fix
		//2.767 - 2015-03-24 - invoice renewal tax fix
		//2.766 - 2015-03-24 - more invoice settings
		//2.765 - 2015-03-24 - blank invoice template fix
		//2.764 - 2015-03-14 - better invoice payment method defaults/overrides
		//2.763 - 2015-03-08 - arithmetic in invoice_task_list template
		//2.762 - 2015-02-24 - invoice email template fix
		//2.761 - 2015-02-12 - product defaults (tax/bill/type)
		//2.76 - 2015-02-08 - invoice_increment_date_check for unique incrementing invoice numbers per year
		//2.759 - 2014-12-31 - hourly rate fix
		//2.758 - 2014-12-22 - invoice task decimal places
		//2.757 - 2014-12-22 - sends invoice due to early subscription payment
		//2.756 - 2014-11-27 - invoice payment improvements
		//2.755 - 2014-11-26 - more work on vendor staff billing feature
		//2.754 - 2014-11-19 - fix for finance list when assigning customer credit / overpayments
		//2.753 - 2014-11-07 - hours:mins bug fix
		//2.752 - 2014-11-05 - link manual invoice to website
		//2.751 - 2014-10-13 - custom subscription invoice templates
		//2.75 - 2014-09-28 - added more tags to invoice_task_list template
		//2.749 - 2014-09-18 - overdue_email_auto_days date calculation fix
		//2.748 - 2014-09-03 - negative taxes
		//2.747 - 2014-09-02 - mark invoice as sent fix and invoice_renew_discounts
		//2.746 - 2014-08-27 - invoice search by date paid
		//2.745 - 2014-08-25 - delete invoice translation fix
		//2.744 - 2014-08-20 - invoice_payment_methods template
		//2.743 - 2014-08-19 - tax_decimal_places and tax_trim_decimal
		//2.742 - 2014-08-18 - pdf invoice printing improvement
		//2.741 - 2014-08-14 - website integration fix
		//2.74 - 2014-08-12 - external_invoice_SUFFIX
		//2.739 - 2014-08-01 - responsive improvements
		//2.738 - 2014-07-22 - invoice translation improvement Qty/Amount/Discount
		//2.737 - 2014-07-14 - improvement with hours:minutes formatting
		//2.736 - 2014-07-08 - quote and job discounts through to invoice
		//2.735 - 2014-07-02 - permission improvement
		//2.734 - 2014-07-01 - automatic invoice payment receipt option
		//2.733 - 2014-07-01 - invoice renewal monthly fix
		//2.732 - 2014-07-01 - invoice_template_print default for customer
		//2.731 - 2014-07-01 - invoice_template_print default for customer
		//2.73 - 2014-06-30 - sending overdue invoice bug fix
		//2.729 - 2014-06-27 - invoice default task fix
		//2.728 - 2014-06-24 - invoice fee calculation improvement
		//2.727 - 2014-06-24 - fix for negative quantites and amounts
		//2.726 - 2014-06-23 - invoice fee calculation improvement
		//2.725 - 2014-06-09 - hours:minutes task formatting
		//2.724 - 2014-05-23 - new invoice pdf template called invoice_print_basic (see blog)
		//2.723 - 2014-05-21 - new invoice pdf template and faster pdf generation
		//2.722 - 2014-04-10 - date_sent added to template tags
		//2.721 - 2014-04-10 - blank invoice subscription bug fix (see Settings-Invoices)
		//2.72 - 2014-04-10 - blank invoice subscription bug fix (see Settings-Invoices)
		//2.719 - 2014-04-09 - invoice page speed improvements
		//2.718 - 2014-04-03 - speed improvements
		//2.717 - 2014-03-31 - duplicate invoice bug fix
		//2.716 - 2014-03-26 - save and return button added
		//2.715 - 2014-03-20 - translation fix in invoice payments and credits
		//2.714 - 2014-03-16 - multiple tax support in jobs
		//2.713 - 2014-03-15 - comma separated default list for multiple taxes
		//2.712 - 2014-03-10 - support for payment gateway fees and discounts
		//2.711 - 2014-03-08 - {if:IS_INVOICE_PAID} template flag
		//2.71 - 2014-03-04 - subscription payments as invoice credit
		//2.699 - 2014-03-04 - allowed payment methods currency restriction fix
		//2.698 - 2014-02-24 - change total amount due to 0 for cancelled invoices
		//2.697 - 2014-02-18 - show offline payment details in main invoice page
		//2.696 - 2014-02-15 - added {RECEIPT_DETAILS} to receipt template
		//2.695 - 2014-02-06 - number_trim_decimals advanced settings
		//2.694 - 2014-02-05 - sorting by total amount due
		//2.693 - 2014-01-30 - thankyou page shown after making an invoice payment
		//2.692 - 2014-01-27 - replace fields from jobs show within invoices as well
		//2.691 - 2014-01-21 - new jquery version fix
		//2.69 - 2014-01-20 - contact email bug fix
		//2.689 - 2014-01-18 - starting work on automatic recurring payments
		//2.688 - 2014-01-11 - invoice in upcoming payments bug fix
		//2.687 - 2014-01-10 - invoice_allow_payment_amount_adjustment added
		//2.686 - 2014-01-03 - invoices with renewal date now appear in finance upcomming
		//2.685 - 2013-12-19 - invoice inrementing bug fix
		//2.684 - 2013-12-15 - support for negative invoice amounts
		//2.683 - 2013-12-11 - permission improvements
		//2.682 - 2013-11-21 - working on new UI
		//2.681 - 2013-11-15 - working on new UI
		//2.68 - 2013-11-13 - advanced option invoice_file_prefix and credit_note_file_prefix for PDFs
		//2.679 - 2013-11-12 - bug fix in new invoice refunds feature
		//2.678 - 2013-11-11 - started on invoice refunds
		//2.677 - 2013-10-30 - sql fix for clients
		//2.676 - 2013-10-11 - fix duplicate tax when saving.
		//2.675 - 2013-10-06 - feature to invoice incomplete job tasks
		//2.674 - 2013-10-05 - Settings > Invoice - option to choose what time of day for renewals/emails to occur
		//2.673 - 2013-10-04 - ability to show tax per line item in invoice PDF
		//2.672 - 2013-10-04 - option to disable website plugin
		//2.671 - 2013-10-04 - merge invoice fix
		//2.67 - 2013-10-02 - print html option
		//2.669 - 2013-10-02 - deleting invoice payment fix
		//2.668 - 2013-10-02 - assign credit currency fix
		//2.667 - 2013-09-29 - ability to disable all payment methods for an invoice
		//2.666 - 2013-09-28 - ability to disable all payment methods for an invoice
		//2.665 - 2013-09-26 - invoice expenses added below invoice
		//2.664 - 2013-09-25 - illegal offset fix
		//2.663 - 2013-09-24 - assign credit button fix
		//2.662 - 2013-09-23 - overdue emails on cancelled invoices bug fix
		//2.661 - 2013-09-20 - invoice paid date correction on split-day payments
		//2.66 - 2013-09-20 - invoice tax included fixes
		//2.659 - 2013-09-13 - ability to assign an invoice to company when customer has more than 1 company
		//2.658 - 2013-09-12 - dashboard speed improvement and better caching
		//2.657 - 2013-09-11 - update customer status after invoice delete
		//2.656 - 2013-09-10 - dashboard alerts cache fix
		//2.655 - 2013-09-09 - invoice primary contact assignment
		//2.654 - 2013-09-09 - invoice renewal redirect/date fix
		//2.653 - 2013-09-07 - bulk delete invoices
		//2.652 - 2013-09-06 - easier to disable plugins
		//2.651 - 2013-09-05 - multiple tax improvement
		//2.649 - 2013-09-03 - hide tax on invoices if none is present
		//2.648 - 2013-09-03 - support for multiple tax rates. Paid date bug fix.
		//2.647 - 2013-09-03 - invoice_auto_renew_only_paid_invoices config variable added
		//2.645 - 2013-08-30 - fix for saving renewable invoices
		//2.644 - 2013-08-30 - added memcache support for huge speed improvements
		//2.643 - 2013-08-28 - big feature - automated customer/member subscriptions
		//2.642 - 2013-08-27 - big feature - automated invoicing + automated overdue emails
		//2.641 - 2013-08-20 - invoices now overdue day after due date
		//2.64 - 2013-08-13 - fix for discount on pdf printout
		//2.639 - 2013-08-08 - fix for hours on dashboard
		//2.638 - 2013-07-29 - new _UCM_SECRET hash in config.php
		//2.637 - 2013-07-25 - invoice bug fixing
		//2.636 - 2013-07-16 - dashboard link fix
		//2.635 - 2013-07-15 - dashboard fix
		//2.634 - 2013-07-15 - bug fix
		//2.633 - 2013-07-01 - add multiple taxes to invoice
		//2.632 - 2013-06-21 - permission update - new invoice permissions
		//2.631 - 2013-06-17 - job improvement when invoice has discount
		//2.63 - 2013-06-14 - customer color coding
		//2.629 - 2013-05-28 - email template field improvements
		//2.628 - 2013-05-28 - email template field improvements
		//2.627 - 2013-05-27 - dashboard alert improvements.
		//2.626 - 2013-05-08 - invoice hours fix
		//2.625 - 2013-04-28 - rounding improvements for different currency formats
		//2.624 - 2013-04-27 - rounding improvements for different currency formats
		//2.623 - 2013-04-26 - task type selection +others on invoice creation
		//2.622 - 2013-04-26 - fix for $0 invoices
		//2.621 - 2013-04-26 - fix for deposit invoices from jobs
		//2.619 - 2013-04-21 - number formatting improvements
		//2.618 - 2013-04-21 - invoice pdf print fix
		//2.617 - 2013-04-20 - invoice merge improvement
		//2.616 - 2013-04-18 - fix for pdf downloads
		//2.615 - 2013-04-16 - PLEASE CHECK YOUR INVOICES AFTER THIS UPDATE TO ENSURE THEY ARE STILL CORRECT!
		//2.614 - 2013-04-16 - PLEASE CHECK YOUR INVOICES AFTER THIS UPDATE TO ENSURE THEY ARE STILL CORRECT!
		//2.613 - 2013-04-16 - new advanced field task_taxable_default

		// old version information prior to 2013-04-16:
		//2.421 - fix for invoice currency in printout.
		//2.422 - fix for assigning credit
		//2.423 - assigning contacts to invoices.
		//2.424 - fix for invoice prefix number.
		//2.5 - recurring invoices. task hourly rates. payment methods per invoice. payment methods text moved to template area.
		//2.51 - date fix for recurring invoices.
		//2.52 - fix for saving extra fields on renewd invoices.
		//2.521 - added currency to invoice hourly amount.
		//2.522 - blank extra fields come through as N/A in invoices now.
		//2.523 - paid date not clearing properly when renewing invoice.
		//2.524 - added member_id for better subscription integration (eg: sending an email).
		//2.525 - multiple currency fixes
		//2.53 - new theme layout
		//2.531 - date done moved into invoice layout.
		//2.532 - bug fix in invoice task list - hourly rate
		//2.533 - permission fix for viewing invoices without customer access.
		//2.534 - customise the Hours column header
		//2.535 - upgrade fix
		//2.536 - replace fields in email template
		//2.537 - CUSTOMER_GROUP, WEBSITE_GROUP and JOB_GROUP added to invoice templates
		//2.538 - testing non-taxable items in invoices
		//2.539 - perm fix
		//2.54 - invoice empty items
		//2.541 - printing from mobile
		//2.542 - invoice qty/amount fix
		//2.543 - another invoice qty/amount fix
		//2.544 - send to primary contact
		//2.545 - discount type
		//2.546 - tax fix. calculate individually on each item.
		//2.547 - date renewl on invoices, -1 day
		//2.548 - mobile fix
		//2.549 - external invoice fix
		//2.55 - fix for invoice re-generation and dates.
		//2.551 - fix for 100% discounted invoices
		//2.552 - extra fields in invoice print from customer section
		//2.553 - custom details in invoice payment area.
		//2.554 - invoice numbers A to Z, then AA to AZ, etc..
		//2.555 - support for incrementing invoice numbers - see advanced invoice_incrementing settings
		//2.556 - better support for multi-job and multi-website invoice prints
		//2.557 - before tax/after tax invoice fix
		//2.558 - 'invoice_send_alerts' advanced setting added
		//2.559 - invoice line numbers are now editable
		//2.56 - quick search based on invoice amount or invoice payment
		//2.561 - remove discount on renewed invoices
		//2.562 - support for negative invoice line items
		//2.563 - invoice bug, possible duplication fix?
		//2.564 - bug fix for incrementing invoice numbers
		//2.565 - option to use invoice name as job name (see invoice_name_match_job option)
		//2.566 - task "long description" added to invoice items like it is in job tasks
		//2.567 - quicker way to print multiple pdf's
		//2.568 - starting work on handling job deposits and customer credit
		//2.569 - added 'contact_first_name' and 'contact_last_name' to template fields.
		//2.570 - speed improvements.
		//2.571 - currency fixes and email features
		//2.572 - currency fixes and email features
		//2.573 - invoice email bug fix
		//2.574 - {INVOICE_DATE_RANGE} template tag added to invoice emails.
		//2.575 - job/invoice deposits made easier.
		//2.576 - deposits and customer credits working nicely now.
		//2.577 - choose different templates when sending an invoice to customer.
		//2.578 - cancel invoice so no more payment reminder
		//2.579 - fix for subscription in finance upcoming items
		//2.58 - invoice credit fixing.
		//2.581 - customer subscription fixes
		//2.582 - support for public invoices notes
		//2.583 - support for multiple invoice pdf print templates
		//2.584 - search by customer group
		//2.585 - speed improvements
		//2.586 - item hourly rate/qty improvements
		//2.587 - bug fix for invoice subscription renewals
		//2.588 - date fix on dashboard invoice alerts
		//2.589 - initial work on a credit note feature
		//2.59 - extra fields update - show in main listing option
		//2.591 - job deposit fix
		//2.592 - customer invoice payment fix
		//2.593 - tax added/included option
		//2.594 - big update - manual task percent, task type (hourly/qty/amount)
		//2.595 - invoice task list improvement
		//2.596 - total_amount_due made available in email template
		//2.597 - bug fix in finance
		//2.598 - 2013-04-04 - fix for 0 invoice amounts.
		//2.599 - 2013-04-04 - new 'invoice_payment_methods_online_footer' template for pdfs
		//2.61 - 2013-04-05 - support for products in invoices
		//2.611 - 2013-04-07 - invoice PDF print fix
		//2.612 - 2013-04-10 - new customer permissions
		// new version information starting from top ^^


		// todo: add completed date as a configurable column
		// todo: move invoice layout to a template system.

		module_config::register_css( 'invoice', 'invoice.css' );
		module_config::register_js( 'invoice', 'invoice.js' );

		hook_add( 'finance_recurring_list', 'module_invoice::get_finance_recurring_items' );
		hook_add( 'customer_archived', 'module_invoice::customer_archived' );
		hook_add( 'customer_unarchived', 'module_invoice::customer_unarchived' );
		hook_add( 'api_callback_invoice', 'module_invoice::api_filter_invoice' );


		if ( class_exists( 'module_template', false ) && module_security::is_logged_in() ) {

			module_template::init_template( 'invoice_payment_methods', '<table width="100%" class="tableclass" id="invoice_payment_methods">
    <tbody>
    <tr>
        <td valign="top" style="width:50%">
	        {PAYMENT_METHODS_ONLINE}
        </td>
        <td valign="top" style="width:50%">
	        {PAYMENT_METHODS_OFFLINE}
        </td>
    </tr>
    </tbody>
</table><br/>
	        ', 'Holds the invoice payment methods at the bottom.', 'code' );
			module_template::init_template( 'invoice_payment_methods_online', '<strong>Option #1: Pay Online</strong>
	        <br/>
	        We support the following secure payment methods:
	        <br/>
	        ', 'Displayed on the invoice.', 'code' );
			module_template::init_template( 'invoice_payment_methods_online_footer', '{PAYMENT_METHODS}
	        <br/>
	        Please <a href="{LINK}">click here</a> to pay online.
	        ', 'Displayed on the invoice.', 'code', array(
				'LINK'            => 'URL link to invoice page',
				'PAYMENT_METHODS' => 'List of supported payment methods',
			) );

			module_template::init_template( 'invoice_payment_methods_offline', '<strong>Option #2: Pay Offline</strong>
	        <br/>
	        We support the following offline payment methods:
	        <br/>
	        ', 'Displayed on the external invoice.', 'code' );

			module_template::init_template( 'invoice_payment_in_full', 'Invoice has been paid in full. <br/><br/>
	        Thank you for your business.
	        ', 'Displayed on the external invoice.', 'code' );

			module_template::init_template( 'invoice_payment_pending', 'Automatic Invoice Payment Pending. <br/><br/>
	        Thank you for your business.
	        ', 'Displayed when a user has an automatic payment pending on an invoice.', 'code' );

			//module_template::init_template('invoice_payment_methods_title','','Displayed as the title on invoice for payment methods area','code');

			module_template::init_template( 'invoice_payment_thankyou', '<h2>Invoice Payment</h2>
	<p>Invoice Number: <strong>{INVOICE_NUMBER}</strong> </p>
	<p>Thank you for making payment on this Invoice. We will email you a receipt shortly.</p>
	', 'This page is displayed after a payment is made on an invoice.', 'code' );

			module_template::init_template( 'invoice_payment_subscription', '<h2>Payment for Invoice {INVOICE_NUMBER}</h2>
<p>Please choose from the available payment options below:</p>
<form action="{PAYMENT_URL}" method="post">
<input type="hidden" name="invoice_payment_id" value="{INVOICE_PAYMENT_ID}">
<input type="hidden" name="payment_method" value="{PAYMENT_METHOD}">
<input type="hidden" name="payment_amount" value="{PAYMENT_AMOUNT}">
<p><input type="submit" name="payment_single" value="Pay a Once Off amount of {PRETTY_PAYMENT_AMOUNT}"></p>
<p><input type="submit" name="payment_subscription" value="Setup Automatic Payments of {PRETTY_PAYMENT_AMOUNT} every {SUBSCRIPTION_PERIOD}"></p>
</form>
', 'Used when a customer tries to pay an invoice that has a subscription option.', 'code' );


			module_template::init_template( 'invoice_email_due', 'Dear {CUSTOMER_NAME},<br>
<br>
Please find attached your invoice {INVOICE_NUMBER}.<br><br>
The {TOTAL_AMOUNT} is due on {DATE_DUE}.<br><br>
You can also view this invoice online by <a href="{INVOICE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Invoice Owing: {INVOICE_NUMBER}', array(
				'CUSTOMER_NAME'  => 'Customers Name',
				'INVOICE_NUMBER' => 'Invoice Number',
				'TOTAL_AMOUNT'   => 'Total amount of invoice',
				'DATE_DUE'       => 'Due Date',
				'FROM_NAME'      => 'Your name',
				'INVOICE_URL'    => 'Link to invoice for customer',
			) );

			module_template::init_template( 'credit_note_email', 'Dear {CUSTOMER_NAME},<br>
<br>
Please find attached your Credit Note {INVOICE_NUMBER} for Invoice {CREDIT_INVOICE_NUMBER}.<br><br>
Total amount: {TOTAL_AMOUNT}<br><br>
You can view this invoice online by <a href="{INVOICE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Credit Note: {INVOICE_NUMBER}', array(
				'CUSTOMER_NAME'         => 'Customers Name',
				'INVOICE_NUMBER'        => 'Credit Note Number',
				'CREDIT_INVOICE_NUMBER' => 'Original Invoice Number',
				'TOTAL_AMOUNT'          => 'Total amount of invoice',
				'FROM_NAME'             => 'Your name',
				'INVOICE_URL'           => 'Link to invoice for customer',
			) );


			module_template::init_template( 'invoice_email_overdue', 'Dear {CUSTOMER_NAME},<br>
<br>
The attached invoice {INVOICE_NUMBER} is now <span style="font-weight:bold; color:#FF0000;">overdue</span>.<br><br>
The {TOTAL_AMOUNT} was due on {DATE_DUE}.<br><br>
You can also view this invoice online by <a href="{INVOICE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Invoice Overdue: {INVOICE_NUMBER}', array(
				'CUSTOMER_NAME'  => 'Customers Name',
				'INVOICE_NUMBER' => 'Invoice Number',
				'TOTAL_AMOUNT'   => 'Total amount of invoice',
				'DATE_DUE'       => 'Due Date',
				'FROM_NAME'      => 'Your name',
				'INVOICE_URL'    => 'Link to invoice for customer',
			) );


			module_template::init_template( 'invoice_email_paid', 'Dear {CUSTOMER_NAME},<br>
<br>
Thank you for your {TOTAL_AMOUNT} payment on invoice {INVOICE_NUMBER}.<br><br>
This invoice was paid in full on {DATE_PAID}.<br><br>
Please find attached the receipt for this invoice payment. <br>
You can also view this invoice online by <a href="{INVOICE_URL}">clicking here</a>.<br><br>
Thank you,<br><br>
{FROM_NAME}
', 'Invoice Paid: {INVOICE_NUMBER}', array(
				'CUSTOMER_NAME'  => 'Customers Name',
				'INVOICE_NUMBER' => 'Invoice Number',
				'TOTAL_AMOUNT'   => 'Total amount of invoice',
				'DATE_PAID'      => 'Paid date',
				'FROM_NAME'      => 'Your name',
				'INVOICE_URL'    => 'Link to invoice for customer',
			) );


		}
	}

	public function pre_menu() {

		// the link within Admin > Settings > Emails.
		if ( $this->can_i( 'edit', 'Invoice Settings', 'Config' ) ) {
			$this->links[] = array(
				"name"                => "Invoices",
				"p"                   => "invoice_settings",
				'holder_module'       => 'config', // which parent module this link will sit under.
				'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
				'menu_include_parent' => 0,
			);
		}

		if ( $this->can_i( 'view', 'Invoices' ) ) {
			// only display if a customer has been created.
			if ( isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] && $_REQUEST['customer_id'] != 'new' ) {
				// how many invoices?
				$name = _l( 'Invoices' );
				if ( module_config::c( 'menu_show_summary', 0 ) ) {
					$invoices = $this->get_invoices( array( 'customer_id' => $_REQUEST['customer_id'] ) );
					if ( count( $invoices ) ) {
						$name .= " <span class='menu_label'>" . count( $invoices ) . "</span> ";
					}
				}
				$this->links[] = array(
					"name"                => $name,
					"p"                   => "invoice_admin",
					'args'                => array( 'invoice_id' => false ),
					'holder_module'       => 'customer', // which parent module this link will sit under.
					'holder_module_page'  => 'customer_admin_open',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
					'icon_name'           => 'dollar',
				);
			}
			$this->links[] = array(
				"name"      => "Invoices",
				"p"         => "invoice_admin",
				'args'      => array( 'invoice_id' => false ),
				'icon_name' => 'dollar',
			);

			if ( module_config::can_i( 'view', 'Settings' ) ) {
				$this->links[] = array(
					"name"                => "Currency",
					"p"                   => "currency",
					'args'                => array( 'currency_id' => false ),
					'holder_module'       => 'config', // which parent module this link will sit under.
					'holder_module_page'  => 'config_admin',  // which page this link will be automatically added to.
					'menu_include_parent' => 0,
				);
			}
		}
		/*else{
            if(module_security::is_contact()){
                // find out how many for this contact.
                $customer_ids = module_security::get_customer_restrictions();
                if($customer_ids){
                    $invoices = array();
                    foreach($customer_ids as $customer_id){
                        $invoices = $invoices + $this->get_invoices(array('customer_id'=>$customer_id));
                    }
                    $name = _l('Invoices');
                    if(count($invoices)){
                        $name .= " <span class='menu_label'>".count($invoices)."</span> ";
                    }
                    $this->links[] = array(
                        "name"=>$name,
                        "p"=>"invoice_admin",
                        'args'=>array('invoice_id'=>false),
                    );
                }
            }
        }*/
	}


	public function ajax_search( $search_key ) {
		// return results based on an ajax search.
		$ajax_results = array();
		$search_key   = trim( $search_key );
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) ) {
			$results = $this->get_invoices( array( 'generic' => $search_key ) );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Invoice: ' );
					$match_string    .= _shl( $result['name'], $search_key );
					$match_string    .= ' for ';
					$match_string    .= dollar( $result['cached_total'], true, $result['currency_id'] );
					$match_string    .= ' (' . ( $result['date_paid'] && $result['date_paid'] != '0000-00-00' ? _l( 'Paid' ) : _l( 'Unpaid' ) ) . ')';
					$ajax_results [] = '<a href="' . $this->link_open( $result['invoice_id'] ) . '">' . $match_string . '</a>';
				}
			}
		}
		if ( strlen( $search_key ) > module_config::c( 'search_ajax_min_length', 2 ) && is_numeric( $search_key ) ) {
			$sql     = "SELECT * FROM `" . _DB_PREFIX . "invoice_payment` WHERE `amount` = '" . db_escape( $search_key ) . "' ORDER BY date_paid DESC LIMIT 5";
			$results = qa( $sql );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Invoice Payment: ' );
					$match_string    .= dollar( $result['amount'], true, $result['currency_id'] ) . ' on ' . print_date( $result['date_paid'] );
					$ajax_results [] = '<a href="' . $this->link_open( $result['invoice_id'] ) . '">' . $match_string . '</a>';
				}
			}
			$sql     = "SELECT * FROM `" . _DB_PREFIX . "invoice` WHERE `cached_total` = '" . db_escape( $search_key ) . "' ORDER BY date_create DESC LIMIT 5";
			$results = qa( $sql );
			if ( count( $results ) ) {
				foreach ( $results as $result ) {
					$match_string    = _l( 'Invoice: ' );
					$match_string    .= htmlspecialchars( $result['name'] );
					$match_string    .= ' for ';
					$match_string    .= dollar( $result['cached_total'], true, $result['currency_id'] );
					$match_string    .= ' (' . ( $result['date_paid'] && $result['date_paid'] != '0000-00-00' ? _l( 'Paid' ) : _l( 'Unpaid' ) ) . ')';
					$ajax_results [] = '<a href="' . $this->link_open( $result['invoice_id'] ) . '">' . $match_string . '</a>';
				}
			}
		}

		return $ajax_results;
	}

	public function handle_hook( $hook, &$calling_module = false ) {
		switch ( $hook ) {
			case "home_alerts":
				$cache_key     = "home_alerts_" . module_security::get_loggedin_id();
				$cache_timeout = module_config::c( 'cache_objects', 60 );
				$alerts        = array();
				if ( $this->can_i( 'edit', 'Invoices' ) && module_config::c( 'invoice_alerts', 1 ) ) {
					// find any invoices that are past the due date and dont have a paid date.

					$key         = _l( 'Invoice Payment Due' );
					$key_overdue = _l( 'Invoice Payment Overdue' );
					if ( class_exists( 'module_dashboard', false ) ) {
						module_dashboard::register_group( $key, array(
							'columns' => array(
								'invoice'   => _l( 'Invoice #' ),
								'customer'  => _l( 'Customer' ),
								'job'       => _l( 'Job Title' ),
								'website'   => module_config::c( 'project_name_single', 'Website' ),
								'last_sent' => _l( 'Last Sent' ),
								'date'      => _l( 'Due Date' ),
								'days'      => _l( 'Day Count' ),
							)
						) );
						module_dashboard::register_group( $key_overdue, array(
							'columns' => array(
								'invoice'         => _l( 'Invoice #' ),
								'customer'        => _l( 'Customer' ),
								'job'             => _l( 'Job Title' ),
								'website'         => module_config::c( 'project_name_single', 'Website' ),
								'last_sent'       => _l( 'Last Sent' ),
								'date'            => _l( 'Due Date' ),
								'auto_email_date' => _l( 'Automatic Email' ),
								'days'            => _l( 'Day Count' ),
							)
						) );
					}

					if ( $cached_alerts = module_cache::get( 'invoice', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {
						$this_alerts = array();
						/*$sql = "SELECT * FROM `"._DB_PREFIX."invoice` p ";
                        $sql .= " WHERE p.date_due != '0000-00-00' AND p.date_due <= '".date('Y-m-d',strtotime('+'.module_config::c('alert_days_in_future',5).' days'))."' AND p.date_paid = '0000-00-00'";
                        $invoice_items = qa($sql);*/
						module_debug::log( array(
							'title' => 'Invoice Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$invoices = self::get_invoices( array(), array(
							'custom_where' => " AND u.date_due != '0000-00-00' AND u.date_due <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "' AND u.date_paid = '0000-00-00'",
						) );

						foreach ( $invoices as $invoice ) {
							// needs 'overdue' and stuff which are unfortunately calculated.
							$invoice = self::get_invoice( $invoice['invoice_id'] );
							if ( ! $invoice || $invoice['invoice_id'] != $invoice['invoice_id'] ) {
								continue;
							}
							if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
								continue;
							}
							// is this invoice overdue?
							if ( $invoice['overdue'] ) {
								$alert_res = process_alert( $invoice['date_due'], $key_overdue );
							} else {
								$alert_res = process_alert( $invoice['date_due'], $key );
							}
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $invoice['invoice_id'], false, $invoice );
								$alert_res['name'] = $invoice['name'];

								if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
									$secs                   = date( "U" ) - date( "U", strtotime( $invoice['date_sent'] ) );
									$days                   = $secs / 86400;
									$days                   = floor( $days );
									$alert_res['last_sent'] = _l( '%s days ago', $days );
								}
								// new dashboard alert layout here:
								$alert_res['time'] = strtotime( $invoice['date_due'] );
								if ( $invoice['overdue'] ) {
									$alert_res['group'] = $key_overdue;
									// work out when to send invoice overdue email
									if ( $invoice['overdue_email_auto'] ) {
										// if you change this calculation make sure it is changed in the cron job below too
										if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' && strtotime( $invoice['date_sent'] ) > strtotime( $invoice['date_due'] ) ) {
											// we have sent a reminder already (todo: this isn't correct logic, fix it up so it can tell for sure if we have sent a reminder already or not (eg: look through email history table)
											$last_invoice_sent = strtotime( $invoice['date_sent'] );
											if ( module_config::c( 'overdue_email_auto_days_repeat', 7 ) <= 0 ) {
												continue; // skip sendin repeat reminders.
											}
											$send_email_on                = strtotime( '+' . module_config::c( 'overdue_email_auto_days_repeat', 7 ) . ' days', $last_invoice_sent );
											$alert_res['auto_email_date'] = print_date( $send_email_on );
										} else if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
											$invoice_is_due               = strtotime( $invoice['date_due'] );
											$send_email_on                = strtotime( '+' . module_config::c( 'overdue_email_auto_days', 3 ) . ' days', $invoice_is_due );
											$alert_res['auto_email_date'] = print_date( $send_email_on );
										} else {
											$alert_res['auto_email_date'] = _l( 'N/A' );
										}

									} else {
										$alert_res['auto_email_date'] = _l( 'N/A' );
									}
								} else {
									$alert_res['group'] = $key;
								}
								$alert_res['invoice'] = $this->link_open( $invoice['invoice_id'], true, $invoice );
								$alert_res['job']     = '';
								$alert_res['website'] = '';
								foreach ( $invoice['job_ids'] as $job_id ) {
									$job                  = module_job::get_job( $job_id );
									$alert_res['job']     .= module_job::link_open( $job_id, true, $job ) . ' ';
									$alert_res['website'] .= $job['website_id'] ? module_website::link_open( $job['website_id'], true ) . ' ' : '';
								}
								if ( isset( $invoice['website_id'] ) ) {
									$alert_res['website'] .= $invoice['website_id'] ? module_website::link_open( $invoice['website_id'], true ) . ' ' : '';
								}
								$alert_res['customer'] = $invoice['customer_id'] ? module_customer::link_open( $invoice['customer_id'], true ) : _l( 'N/A' );
								$alert_res['date']     = print_date( $alert_res['time'] );
								$alert_res['days']     = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}
						module_cache::put( 'invoice', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}
				if ( module_config::c( 'invoice_send_alerts', 1 ) ) {
					if ( $this->can_i( 'edit', 'Invoices' ) ) {
						// find any invoices that haven't been sent

						$key = _l( 'Invoice Not Sent' );
						if ( class_exists( 'module_dashboard', false ) ) {
							module_dashboard::register_group( $key, array(
								'columns' => array(
									'invoice'  => _l( 'Invoice #' ),
									'customer' => _l( 'Customer' ),
									'job'      => _l( 'Job Title' ),
									'website'  => module_config::c( 'project_name_single', 'Website' ),
									'date'     => _l( 'Invoice Date' ),
									'days'     => _l( 'Day Count' ),
								)
							) );
						}

						if ( $cached_alerts = module_cache::get( 'invoice', $cache_key . $key ) ) {
							$alerts = array_merge( $alerts, $cached_alerts );
						} else {
							module_debug::log( array(
								'title' => 'Invoice Home Alerts: ',
								'data'  => " starting: " . $key,
							) );
							$this_alerts   = array();
							$sql           = "SELECT * FROM `" . _DB_PREFIX . "invoice` p ";
							$sql           .= " WHERE p.date_sent = '0000-00-00' AND p.date_paid = '0000-00-00'";
							$invoice_items = qa( $sql );
							foreach ( $invoice_items as $invoice_item ) {
								$invoice = self::get_invoice( $invoice_item['invoice_id'] );
								if ( ! $invoice || $invoice['invoice_id'] != $invoice_item['invoice_id'] ) {
									continue;
								}
								$alert_res = process_alert( $invoice['date_create'] != '0000-00-00' ? $invoice['date_create'] : date( 'Y-m-d' ), $key );
								if ( $alert_res ) {
									$alert_res['link'] = $this->link_open( $invoice_item['invoice_id'] );
									$alert_res['name'] = $invoice_item['name'];

									// new dashboard alert layout here:
									$alert_res['time']    = strtotime( $invoice_item['date_create'] );
									$alert_res['group']   = $key;
									$alert_res['invoice'] = $this->link_open( $invoice_item['invoice_id'], true, $invoice );
									$alert_res['job']     = '';
									$alert_res['website'] = '';
									foreach ( $invoice['job_ids'] as $job_id ) {
										$job                  = module_job::get_job( $job_id );
										$alert_res['job']     .= module_job::link_open( $job_id, true, $job ) . ' ';
										$alert_res['website'] .= $job['website_id'] ? module_website::link_open( $job['website_id'], true ) . ' ' : '';
									}
									if ( isset( $invoice['website_id'] ) ) {
										$alert_res['website'] .= $invoice['website_id'] ? module_website::link_open( $invoice['website_id'], true ) . ' ' : '';
									}
									$alert_res['customer'] = $invoice['customer_id'] ? module_customer::link_open( $invoice['customer_id'], true ) : _l( 'N/A' );
									$alert_res['date']     = print_date( $alert_res['time'] );
									$alert_res['days']     = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

									$this_alerts[] = $alert_res;
								}
							}
							module_cache::put( 'invoice', $cache_key . $key, $this_alerts, $cache_timeout );
							$alerts = array_merge( $alerts, $this_alerts );
						}
					}
				}

				if ( $this->can_i( 'edit', 'Invoices' ) && module_config::c( 'invoice_renew_alerts', 1 ) ) {
					// find any invoices that have a renew date soon and have not been renewed.

					$key      = _l( 'Invoice Renewal Pending' );
					$key_auto = _l( 'Automatic Invoice Renewal Pending' );
					if ( class_exists( 'module_dashboard', false ) ) {
						module_dashboard::register_group( $key, array(
							'columns' => array(
								'invoice'     => _l( 'Invoice #' ),
								'customer'    => _l( 'Customer' ),
								'job'         => _l( 'Job Title' ),
								'website'     => module_config::c( 'project_name_single', 'Website' ),
								'period'      => _l( 'Period' ),
								'date_create' => _l( 'Created Date' ),
								'date'        => _l( 'Renewal Date' ),
								'days'        => _l( 'Day Count' ),
							)
						) );
						module_dashboard::register_group( $key_auto, array(
							'columns' => array(
								'invoice'     => _l( 'Invoice #' ),
								'customer'    => _l( 'Customer' ),
								'job'         => _l( 'Job Title' ),
								'website'     => module_config::c( 'project_name_single', 'Website' ),
								'period'      => _l( 'Period' ),
								'date_create' => _l( 'Created Date' ),
								'date'        => _l( 'Renewal Date' ),
								'auto_email'  => _l( 'Automatic Email' ),
								'days'        => _l( 'Day Count' ),
							)
						) );
					}

					if ( $cached_alerts = module_cache::get( 'invoice', $cache_key . $key ) ) {
						$alerts = array_merge( $alerts, $cached_alerts );
					} else {
						module_debug::log( array(
							'title' => 'Invoice Home Alerts: ',
							'data'  => " starting: " . $key,
						) );
						$this_alerts = array();
						$sql         = "SELECT p.* FROM `" . _DB_PREFIX . "invoice` p ";
						$sql         .= " WHERE p.date_renew != '0000-00-00'";
						$sql         .= " AND p.date_renew <= '" . date( 'Y-m-d', strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) . "'";
						$sql         .= " AND (p.renew_invoice_id IS NULL OR p.renew_invoice_id = 0)";
						$res         = qa( $sql );
						foreach ( $res as $r ) {
							$invoice = self::get_invoice( $r['invoice_id'] );
							if ( ! $invoice || $invoice['invoice_id'] != $r['invoice_id'] ) {
								continue;
							}
							if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
								continue;
							}
							if ( $invoice['renew_auto'] ) {
								// todo - ignore unpaid invoices because they will not be automatically renewed by the cron
								$alert_res = process_alert( $r['date_renew'], $key_auto );
							} else {
								$alert_res = process_alert( $r['date_renew'], $key );
							}
							if ( $alert_res ) {
								$alert_res['link'] = $this->link_open( $r['invoice_id'] );
								$alert_res['name'] = $r['name'];
								// work out renewal period
								$alert_res['period'] = _l( 'N/A' );
								if ( $r['date_create'] && $r['date_create'] != '0000-00-00' ) {
									$time_diff = strtotime( $r['date_renew'] ) - strtotime( $r['date_create'] );
									if ( $time_diff > 0 ) {
										$diff_type = 'day';
										$days      = round( $time_diff / 86400 );
										if ( $days >= 365 ) {
											$time_diff = round( $days / 365, 1 );
											$diff_type = 'year';
										} else {
											$time_diff = $days;
										}
										$alert_res['period'] = ' ' . _l( '%s %s renewal', $time_diff, $diff_type );
									}
								}
								// new dashboard alert layout here:
								$alert_res['time'] = strtotime( $invoice['date_renew'] );
								if ( $invoice['renew_auto'] ) {
									$alert_res['group']      = $key_auto;
									$alert_res['auto_email'] = $r['renew_email'] ? _l( 'Yes' ) : _l( 'No' );
								} else {
									$alert_res['group'] = $key;
								}
								$alert_res['invoice'] = $this->link_open( $invoice['invoice_id'], true, $invoice );
								$alert_res['job']     = '';
								$alert_res['website'] = '';
								foreach ( $invoice['job_ids'] as $job_id ) {
									$job                  = module_job::get_job( $job_id );
									$alert_res['job']     .= module_job::link_open( $job_id, true, $job ) . ' ';
									$alert_res['website'] .= $job['website_id'] ? module_website::link_open( $job['website_id'], true ) . ' ' : '';
								}
								if ( isset( $invoice['website_id'] ) ) {
									$alert_res['website'] .= $invoice['website_id'] ? module_website::link_open( $invoice['website_id'], true ) . ' ' : '';
								}
								$alert_res['customer']    = $invoice['customer_id'] ? module_customer::link_open( $invoice['customer_id'], true ) : _l( 'N/A' );
								$alert_res['date_create'] = print_date( $invoice['date_create'] );
								$alert_res['date']        = print_date( $invoice['date_renew'] );
								$alert_res['days']        = ( $alert_res['warning'] ) ? '<span class="important">' . $alert_res['days'] . '</span>' : $alert_res['days'];

								$this_alerts[] = $alert_res;
							}
						}
						module_cache::put( 'invoice', $cache_key . $key, $this_alerts, $cache_timeout );
						$alerts = array_merge( $alerts, $this_alerts );
					}
				}

				return $alerts;
				break;
		}

		return false;
	}


	public static function link_generate( $invoice_id = false, $options = array(), $link_options = array() ) {

		// link generation can be cached and save a few db calls.
		$link_cache_key     = 'invoice_link3_' . md5( module_security::get_loggedin_id() . '_' . serialize( func_get_args() ) ) . '_' . ( isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : false );
		$link_cache_timeout = module_config::c( 'cache_link_timeout', 3600 );
		if ( $cached_link = module_cache::get( 'invoice', $link_cache_key ) ) {
			return $cached_link;
		}

		$key = 'invoice_id';
		if ( $invoice_id === false && $link_options ) {
			foreach ( $link_options as $link_option ) {
				if ( isset( $link_option['data'] ) && isset( $link_option['data'][ $key ] ) ) {
					${$key} = $link_option['data'][ $key ];
					break;
				}
			}
			if ( ! ${$key} && isset( $_REQUEST[ $key ] ) ) {
				${$key} = $_REQUEST[ $key ];
			}
		}
		$bubble_to_module = false;
		if ( ! isset( $options['type'] ) ) {
			$options['type'] = 'invoice';
		}
		if ( ! isset( $options['page'] ) ) {
			$options['page'] = 'invoice_admin';
		}
		if ( ! isset( $options['arguments'] ) ) {
			$options['arguments'] = array();
		}
		$options['arguments']['invoice_id'] = $invoice_id;
		$options['module']                  = 'invoice';
		if ( ! isset( $options['data'] ) || ! $options['data'] ) {
			if ( (int) $invoice_id > 0 ) {
				$data = self::get_invoice( $invoice_id, 2 );
			} else {
				$data = array();
			}
			$options['data'] = $data;
		} else {
			$data = $options['data'];
		}
		// todo - read total_amoutn_due from new c_ cached field
		if ( ! isset( $data['total_amount_due'] ) ) {

		} else if ( isset( $data['date_cancel'] ) && $data['date_cancel'] != '0000-00-00' ) {
			$link_options['class'] = 'invoice_cancel';
		} else if ( $data['total_amount_due'] <= 0 ) {
			$link_options['class'] = 'success_text';
		} else {
			$link_options['class'] = 'error_text';
		}
		// what text should we display in this link?
		$options['text'] = ( ! isset( $data['name'] ) || ! trim( $data['name'] ) ) ? 'N/A' : $data['name'];
		if (
			// only bubble for admins:
			self::can_i( 'edit', 'Invoices' ) &&
			(
				isset( $data['customer_id'] ) && $data['customer_id'] > 0 ||
				isset( $_REQUEST['customer_id'] ) && $_REQUEST['customer_id'] > 0
			)
		) {
			$bubble_to_module = array(
				'module'   => 'customer',
				'argument' => 'customer_id',
			);
		}
		array_unshift( $link_options, $options );


		if ( ! module_security::has_feature_access( array(
			'name'        => 'Customers',
			'module'      => 'customer',
			'category'    => 'Customer',
			'view'        => 1,
			'description' => 'view',
		) )
			// only apply this restriction to administrators, not contacts.
			//&& self::can_i('edit','Invoices')

		) {
			$bubble_to_module = false;
			/*
            if(!isset($options['full']) || !$options['full']){
                return '#';
            }else{
                return isset($options['text']) ? $options['text'] : 'N/A';
            }*/

		}
		if ( $bubble_to_module ) {
			global $plugins;
			$link = $plugins[ $bubble_to_module['module'] ]->link_generate( false, array(), $link_options );
		} else {
			// return the link as-is, no more bubbling or anything.
			// pass this off to the global link_generate() function
			//print_r($link_options);
			$link = link_generate( $link_options );

		}
		module_cache::put( 'invoice', $link_cache_key, $link, $link_cache_timeout );

		return $link;
	}

	public static function link_open( $invoice_id, $full = false, $data = array() ) {
		return self::link_generate( $invoice_id, array( 'full' => $full, 'data' => $data ) );
	}


	public static function link_receipt( $invoice_payment_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash ' . _UCM_SECRET . ' ' . $invoice_payment_id );
		}

		return full_link( _EXTERNAL_TUNNEL . '?m=invoice&h=receipt&i=' . $invoice_payment_id . '&hash=' . self::link_receipt( $invoice_payment_id, true ) );
	}


	public static function link_public( $invoice_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for invoice ' . _UCM_SECRET . ' ' . $invoice_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.invoice/h.public/i.' . $invoice_id . '/hash.' . self::link_public( $invoice_id, true ) );
	}

	public static function link_public_payment_complete( $invoice_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for complete payment on invoice ' . _UCM_SECRET . ' ' . $invoice_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.invoice/h.payment_complete/i.' . $invoice_id . '/hash.' . self::link_public_payment_complete( $invoice_id, true ) );
	}

	public static function link_public_pay( $invoice_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for invoice ' . _UCM_SECRET . ' ' . $invoice_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.invoice/h.public_pay/i.' . $invoice_id . '/hash.' . self::link_public_pay( $invoice_id, true ) );
	}

	public static function link_public_print( $invoice_id, $h = false ) {
		if ( $h ) {
			return md5( 's3cret7hash for invoice ' . _UCM_SECRET . ' ' . $invoice_id );
		}

		return full_link( _EXTERNAL_TUNNEL_REWRITE . 'm.invoice/h.public_print/i.' . $invoice_id . '/hash.' . self::link_public_print( $invoice_id, true ) );
	}


	public function external_hook( $hook ) {

		switch ( $hook ) {
			case 'public_print':
				ob_start();

				$invoice_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash       = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $invoice_id && $hash ) {
					$correct_hash = $this->link_public_print( $invoice_id, true );
					if ( $correct_hash == $hash ) {
						// check invoice still exists.
						$invoice_data = $this->get_invoice( $invoice_id );
						if ( ! $invoice_data || $invoice_data['invoice_id'] != $invoice_id ) {
							echo 'Invoice no longer exists';
							exit;
						}
						$pdf_file = $this->generate_pdf( $invoice_id );

						if ( $pdf_file && is_file( $pdf_file ) ) {
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

						} else {
							echo _l( 'Sorry PDF is not currently available.' );
						}
					}
				}

				exit;

				break;
			case 'public_pay':
				$invoice_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash       = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $invoice_id && $hash ) {
					$correct_hash = $this->link_public_pay( $invoice_id, true );
					if ( $correct_hash == $hash ) {
						// check invoice still exists.
						$invoice_data = $this->get_invoice( $invoice_id );
						if ( ! $invoice_data || $invoice_data['invoice_id'] != $invoice_id ) {
							echo 'Invoice no longer exists';
							exit;
						}
						$_REQUEST['invoice_id'] = $invoice_id;
						$this->handle_payment();
					}
				}
				exit;
				break;
			case 'payment_complete':
				$invoice_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash       = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $invoice_id && $hash ) {
					$correct_hash = $this->link_public_payment_complete( $invoice_id, true );
					if ( $correct_hash == $hash ) {
						// check invoice still exists.
						$invoice_data = $this->get_invoice( $invoice_id );
						if ( ! $invoice_data || $invoice_data['invoice_id'] != $invoice_id ) {
							echo 'Invoice no longer exists';
							exit;
						}

						// are we processing this payment?
						/*if(isset($_REQUEST['payment'])&&$_REQUEST['payment']=='go'){
                            $this->handle_payment();
                        }*/

						// all good to print a receipt for this payment.
						$invoice = $invoice_data = $this->get_invoice( $invoice_id );


						$template = module_template::get_template_by_key( 'invoice_payment_thankyou' );

						$data = $this->get_replace_fields( $invoice_id, $invoice_data );

						$template->page_title = htmlspecialchars( $invoice_data['name'] );

						$template->assign_values( $data );
						echo $template->render( 'pretty_html' );

					}
				}
				break;
			case 'public':
				$invoice_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash       = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $invoice_id && $hash ) {
					$correct_hash = $this->link_public( $invoice_id, true );
					if ( $correct_hash == $hash ) {


						// check invoice still exists.
						$invoice_data = $this->get_invoice( $invoice_id );
						if ( ! $invoice_data || $invoice_data['invoice_id'] != $invoice_id ) {
							echo 'Invoice no longer exists';
							exit;
						}

						// are we processing this payment?
						/*if(isset($_REQUEST['payment'])&&$_REQUEST['payment']=='go'){
                            $this->handle_payment();
                        }*/

						// all good to print a receipt for this payment.
						$invoice = $invoice_data = $this->get_invoice( $invoice_id );


						// old template rename change
						// todo: copy this to jobs and quotes as well.
						$sql          = "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE 'external_invoice%'";
						$oldtemplates = qa( $sql );
						foreach ( $oldtemplates as $oldtemplate ) {
							$new_key = str_replace( 'external_invoice', 'invoice_external', $oldtemplate['template_key'] );
							if ( $new_key ) {
								$existingnew = qa( "SELECT * FROM `" . _DB_PREFIX . "template` WHERE `template_key` LIKE '" . module_db::escape( $new_key ) . "'" );
								if ( ! $existingnew ) {
									update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => $new_key ) );
								} else {
									update_insert( 'template_id', $oldtemplate['template_id'], 'template', array( 'template_key' => 'old_' . $oldtemplate['template_key'] ) );
								}
							}
						}


						module_template::init_template( 'invoice_external', '<h2>Invoice</h2>
Invoice Number: <strong>{INVOICE_NUMBER}</strong> <br/>
Due Date: <strong>{DUE_DATE}</strong> <br/>
Customer: <strong>{CUSTOMER_NAME}</strong> <br/>
Address: <strong>{CUSTOMER_ADDRESS}</strong> <br/>
Contact: <strong>{CONTACT_NAME} {CONTACT_EMAIL}</strong> <br/>
{PROJECT_TYPE} Name: <strong>{PROJECT_NAME}</strong> <br/>
Job: <strong>{JOB_NAME}</strong> <br/>
<a href="{PRINT_LINK}">Print PDF Invoice</a> <br/>
<br/>
{TASK_LIST}
{PAYMENT_METHODS}
{PAYMENT_HISTORY}
', 'Used when displaying the external view of an invoice.', 'code' );

						module_template::init_template( 'credit_note_external', '<h2>Credit Note</h2>
Credit Note Number: <strong>{INVOICE_NUMBER}</strong> <br/>
Original Invoice Number: <strong>{CREDIT_INVOICE_NUMBER}</strong> <br/>
Create Date: <strong>{DATE_CREATE}</strong> <br/>
Customer: <strong>{CUSTOMER_NAME}</strong> <br/>
Address: <strong>{CUSTOMER_ADDRESS}</strong> <br/>
Contact: <strong>{CONTACT_NAME} {CONTACT_EMAIL}</strong> <br/>
{PROJECT_TYPE} Name: <strong>{PROJECT_NAME}</strong> <br/>
Job: <strong>{JOB_NAME}</strong> <br/>
<a href="{PRINT_LINK}">Print PDF</a> <br/>
<br/>
{TASK_LIST}
', 'Used when displaying the external view of a credit note.', 'code' );
						// correct!
						// load up the receipt template.
						if ( class_exists( 'module_company', false ) && isset( $invoice['company_id'] ) && (int) $invoice['company_id'] > 0 ) {
							module_company::set_current_company_id( $invoice['company_id'] );
						}
						if ( isset( $invoice['credit_note_id'] ) && $invoice['credit_note_id'] ) {
							$template = module_template::get_template_by_key( 'credit_note_external' );
						} else {
							//$template = module_template::get_template_by_key('external_invoice');
							$template = false;
							if ( ! empty( $invoice['invoice_template_external'] ) ) {
								$template = module_template::get_template_by_key( $invoice['invoice_template_external'] );
								if ( ! $template->template_id ) {
									$template = false;
								}
							}
							if ( ! $template ) {
								$invoice_template        = isset( $invoice['invoice_template_print'] ) && strlen( $invoice['invoice_template_print'] ) ? $invoice['invoice_template_print'] : module_customer::c( 'invoice_template_print_default', 'invoice_print', $invoice['customer_id'] );
								$invoice_template_suffix = '';
								if ( $invoice_template != 'invoice_print' ) {
									$invoice_template_suffix = str_replace( 'invoice_print', '', $invoice_template );
								}
								if ( isset( $invoice_template_suffix ) && strlen( $invoice_template_suffix ) > 0 ) {
									$template = module_template::get_template_by_key( 'invoice_external' . $invoice_template_suffix );
									if ( ! $template->template_id ) {
										$template = false;
									}
								}
								if ( ! $template ) {
									$template = module_template::get_template_by_key( 'invoice_external' );
								}
							}
						}


						ob_start();
						include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_task_list.php' ) );
						$task_list_html = ob_get_clean();
						ob_start();
						include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_payment_history.php' ) );
						$invoice_payment_history = ob_get_clean();
						ob_start();
						include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_payment_methods.php' ) );
						$invoice_payment_methods = ob_get_clean();

						$data                    = $this->get_replace_fields( $invoice_id, $invoice_data );
						$data['task_list']       = $task_list_html;
						$data['payment_methods'] = $invoice_payment_methods;
						$data['payment_history'] = $invoice_payment_history;

						$template->page_title = htmlspecialchars( $invoice_data['name'] );

						$template->assign_values( $data );
						echo $template->render( 'pretty_html' );
						exit;
					}
				}
				break;
			case 'receipt':
				$invoice_payment_id = ( isset( $_REQUEST['i'] ) ) ? (int) $_REQUEST['i'] : false;
				$hash               = ( isset( $_REQUEST['hash'] ) ) ? trim( $_REQUEST['hash'] ) : false;
				if ( $invoice_payment_id && $hash ) {
					$correct_hash = $this->link_receipt( $invoice_payment_id, true );
					if ( $correct_hash == $hash ) {
						// all good to print a receipt for this payment.
						$invoice_payment_data = $this->get_invoice_payment( $invoice_payment_id );
						if ( $invoice_payment_data ) {
							$invoice_data = $this->get_invoice( $invoice_payment_data['invoice_id'] );
							if ( $invoice_payment_data && $invoice_data ) {
								// correct!

								// check if we've actually paid yet.
								if ( ! trim( $invoice_payment_data['date_paid'] ) || $invoice_payment_data['date_paid'] == '0000-00-00' ) {
									echo _l( 'Payment pending...' );
									exit;
								}

								module_template::init_template( 'invoice_payment_receipt', '<table style="background-color: #effedd; border: 1px solid #28e06e;" border="0" cellpadding="10">
<tbody>
<tr>
<td style="padding-left: 30px;">
<p>&nbsp;</p>
<p><img class="alignnone size-full wp-image-2842" src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEAYABgAAD//gArT3B0aW1pemVkIGJ5IEpQRUdtaW5pIDMuOC44LjJMIDB4NzJlOTkxZDL/2wBDAAIDAwIDAQICAgICAgICAwUDAwMDAwYECQcFBwYPDwcGBwcICgwTCBEVEQcHCxsLFRcNGRkZDxMPEBsNHQwZGQ3/2wBDAQICAgMDAwYDAwYNCAcIDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ0NDQ3/wAARCABtAG4DASIAAhEBAxEB/8QAHQAAAQQDAQEAAAAAAAAAAAAAAAQFBwgBAwYCCf/EAFAQAAAFAgIEBwgMCgsAAAAAAAACAwQFAQYSEwcRFCIjMTJBQlJiFSFRYXFygpEkMzRDRFOBkqGiscIWRVRjg7PB0dLwCCU1N1Vkc5OUleL/xAAcAQABBAMBAAAAAAAAAAAAAAAAAwUGBwECBAj/xAA6EQABAgMEBggDCAMBAAAAAAABAgMABAUGERIhEzFBUWFxByKBkaGx0fAUMsEjJDNCQ1Ji4RVygsL/2gAMAwEAAhEDEQA/APuwAABB4a4AAGs6iSTcyqyhEkico5zcQwSALzBGwAaSOJB3/Y8fmI/lbvgSejzmCjuRLql9mXCqj2GTcqf1jYgm2p54XyzSljfkB3qIv7L41BUr8NJPl4/SFwA31t0leO47o/5v/kbaQayfue45z9OZNb7oUEtUr+tL5cFJ9R5xnA/tR4iFYAkq2nm/5HLp9n2Of9wEnCKixkuEQcE5aCxcBqDUqUhWB5JQdx+hzB7CYLyDcoXGFYAAG8ZgAAAIIAAHkxilSMc+6Qm8YGrMwQkdOkGjDPWxdRNMnGY3VKBtHGV/rW4sHB8KkzMbcSL1j9aoxGobS6/CF/up/AE1Ogl8Z5aiq+kC+XMzKObcgXB0bebny11U/fzfwiM2gtDJUaRFUqQxYvwmv3n9yuG3PJIuNxUQA11GosybPxMxnf8AKnfxPvLnEkXPpZiY5ZaNtFsnNvU9wztTdSL5vXFZZS99IEuspt1yyCKJ/g7NTZS0+YOPygZQ8y2l6RbQVpwl98ob2IQSlIHG7NX/AETFZ1Kvz86rrruTuGQ/vthEodystmOF11z9ZRTEHRnK3FHrkUi5yWYHJych4cg0ZQMoQhpx5pelbWQreCQe+GVONKsSTcecTjBaWbwj10kLgKjcjDpGPwSlPTL+0Wrh5y07wt7aIxxicN946JuDVRMPnFUhSkxG3ShyiJN9GzzabgHx27pubcVS5+z5BbdkelqrU4iXqpMzLnIhWagN4UdfJV/AjXErpNqpuXIbmjpG9t+scj6+EfRjE4ayRWEhv5vuZ1zH7PlC8cra9xRd5aOjZ6ZEpFvwb5rzlP8AGED6iZYjhZg73nTTp9cnROPScu+w8y3Nyi8bDovQrb/qeIz155EHMXmymnEOIS60b0K1H6H3zhaAAB0wrAGd6XaHbCGL+M1eG/0Cd8/8Pyh4Dax4XSm/PX8XME0i/pDa/uhB5AdU3LH9RQT2a1d6QY0WMRCN5u9fCOF0kzporRulBx6mS+neC3Og3Lyv4RSzKE26QXR32nGQT3smLIRml9pvpMYRdlDzN0n1dyq19439Ro6NI2AJyPeq8926K1tJMqmp5e5PVHZ6mGPKBlB8ygZQr34eGHQwx5Q0K5KDNRdc+WklvGMOiqlqL3xJ+j+yyXLchbtuFtmWlEL4Y5moXvOXBPfD9Yha+uvm70hsxZSarc+mSlhrzJ2JG0n3ryjup1KcnHwy32ncIabM0YPrqTb3JfG2RNoKcIxiCGylHRPjFTcaZfpr2ely1zs4xrpiuJhDsWsbGMHWyN2zZLLKUqRKF+6PowPn/crdRPTheKKxcKndZdX0VN4n0HFxdJ9j5GiWdl5aRR+oMSvzKOFWs99w1RLbR0hmTkG22B+bM7TkYRWpMr27pMYyhDG2M5sh6n1kTC80ngo0YzaG+RDCVQxekgp/JTD5/wCULr2ov3V/o2M0V+EVTaKx58XY7xfowjn6HKk64zNUNZyw6RHBQIv7zhN3A740sjMKKXJM7sQ5j2I6wAbI5baLVj3NeUqgWpvLq7/2VDmLjZdS62l1OogHviYpUFJChtgDXD/3kXbr/wAn+rqHQII6mXpKmNfw1mgsX0NdAJH36VVsCz4trHmRB+q2eP8A5IiplxEMfS3cxj/4o4/WVHNZIlG7GRm+l6Z18lyrtJfT745RvDtpaTRj31x/gwzUNwrkqOM1eyXmL51R5gqdIccrL0qSAouKF5IA+Y6ychFcTEmpU2pvbiOvnHBKOMU2jDxLJ1Nzjr2hgyTzDVDnVrKNZZ3EzzDuXNR+HaW2YVTViLrLvF5XKF2rcti07at/ItlgkntJcS745s5RbtKK9L7BF+k2HKRiyvtsTeii7HK4edqY24p6NTfWr1RZVV6Ivg6KuaQvG+nrED5cO0Dfvv4aofpmy2ilC4DesZ8LogWNhHNxaQo+1Wh1EE3XsiRcp+9NC8s3lryKeOovkzaMmEAzi41sm0YR6JW7dFPiKQoizR5B9zdHZp14lhmLqwvFMXGRv7wl6t/ymD7dFyEhovZGOBWadE4P82XrmE8sZSZSy1CNQn+qtYClHb/FA48N54Q90mVapsnpnsicz9BGbkuyOgUNmTLt8yoXcbFN3i9pQVQlX76ZuheXktn2tfCU2SngpuhSrnrvVXLlVRddc2NRRTerUw1ZQpO2Fsp+vu4XDhZBvSgeZ3nwGyIlVam/OquVkjYPXeYYckWq0b0NTRE8IboyauH/AGyiueULR2gjsGg5Nwpu52c8/n5oeuiGWKa4XtiW1E+AjqsuyUzmPYEn6Rm361ro5ja18ClPUpUdKGKGTqlYcYmamquTQ+rzq6/2h9FvUhJTIMJVrCE+QiWSwIZQDuHlAG9Suz3JGSXEQpqtF69hTirX5aFDgNSqaazNRBWmJNUtSGp4qjtcCiL0awQRzBvHjCygSMtccNfEXVVs0nESazN6bO41U6Na7ta+utBCOULWtDkeQzmHk6UVXRTylqG6adeSpQQZLwziLmzJGpU7VStaoLauMvgqK06RLPaR0VuVH2bnzfxUMs+d13PmIj9bkcSvi2xkrXwPvxjXBz8lDuCIGMZ3FYt9sbm8wWETUiZm1FPaJGLkUTIrJKFxUMQ3KTOUVfyx00LKOYiazC4lGS3uhDw9ryhKxNtnqaoSU8cTByzzKOX8d47RuOtKqi2CGnc0eX9RN0xKN4e2DujYTrm4Nsj1j/uFXHKjp5LLvnipl3Lk+NQ5h2c3IKSt0KOd7ZEeDbJ+Ag5vLDdby0q6xOaNo/Yt5J4nart2bhzMI1idM07hT8g1esMuUDKD1ljNEtZhA/h4Z9FCNlHrPp9qwQLwjlTB5BZKUKRrYTeDZ7hnuXHIeZ0zerEGq2oTubHnlJDCR8uTpdBMKU1O6Fwnl/gSBchh4+ur8ovKytAVSqWrTC5+Zyu2pRtPA3HvKREtp0l8MwcXzueA9/SHQhcCGWPYACXgAC4Q6QAAAEEIl0TnOm4bKbO+bd9FXV6ympz08QXEOxlotWOkm5SOiU4ZuavFXrkrz+X1jATKoJLYKmxEVSriTVTrhMWviqMIJbKrgFJV8yTqV6HzGR2EYF4vuzB1jfHAyNsPmyh1WGJ817PKoOMMiYqmA5DEMXomE5keSrbdcod1UPjUdw/zOIw8KyNquN2SyEVOq+b5VfrCGVKxdKfUXJR7QE/lXq7Df5FUNj9Ml1m9tWA7j79Yg3LBliZao6Pzb21Qv/YYfvjYU9hNt8riD3fz+cGYdH6wb3JtkJ34v6HnHN/iP3OJu5xFDWMkHq+Bm1UU7fNQSrF2+yji7c/Omu7T3sRuSQLKzbYyeVDR7yR6pipZBKekYIToPXymObXIdHosW/I9PriUUegUqnKDzP3l0ajqbB335juxHgI7paUl2TiR11eHvvjy4cKzDjZ22NKAJ7atzrdkviDuUpSplIQuEpeSM0pqJhLulGRIEoWVl544lq1nyAGwDYO03kkx2gG8qUbyYAAAFY2gAAAIIAAACCAeDFIdPCoQpy+A1B7AAi/IwQ3GYRRjazxkeevhM2INpGjBI1KosWaNeapESlCwAQEqyDiCBfyEaaNAzAgAAAXjeAAAAggAAAII/9k=" alt="light green check mark" width="110" height="109" /></p>
</td>
<td>
<p><span style="font-size: medium;"><strong><br />Payment Successful!</strong></span></p>
<hr />
<p><span style="font-size: small;"><strong>Your payment has been processed. Here are the details of this transaction for your reference:</strong></span></p>
<ul>
<li><span style="font-size: small;">Invoice Number:&nbsp;<strong>{INVOICE_NUMBER}</strong></span></li>
<li><span style="font-size: small;">Amount Paid:&nbsp;<strong>{AMOUNT}</strong></span></li>
<li><span style="font-size: small;">Confirmation Number:&nbsp;<strong>{RECEIPT_NUMBER}</strong></span></li>
<li><span style="font-size: small;">Payment Method:&nbsp;<strong>{METHOD}</strong></span></li>
<li><span style="font-size: small;">Payment Date:&nbsp;<strong>{PAYMENT_DATE}</strong></span></li>
</ul>
</td>
</tr>
</tbody>
</table>', 'Receipts for invoice payments.', array(
									'NAME'              => 'Invoice Number',
									'DATE_SENT'         => 'Date invoice was sent',
									'DATE_DUE'          => 'Date invoice was due',
									'DATE_PAID'         => 'Date invoice was paid',
									'HOURLY_RATE'       => 'Hourly rate of the invoice',
									'TOTAL_AMOUNT'      => 'Total amount of invoice',
									'TOTAL_AMOUNT_DUE'  => 'Total due on invoice',
									'TOTAL_AMOUNT_PAID' => 'Total paid on invoice',
									'RECEIPT_NUMBER'    => 'Our Receipt Number',
									'RECEIPT_DETAILS'   => 'Any custom comments added to payment',
									'PAY_STATUS'        => 'Paid or not',
									'PAYMENT_DATE'      => 'Date payment was made',
									'AMOUNT'            => 'Amount that was paid',
									'METHOD'            => 'What payment method was used',
								) );

								$details                                 = @unserialize( $invoice_payment_data['data'] );
								$invoice_payment_data['receipt_details'] = isset( $details['custom_notes'] ) ? htmlspecialchars( $details['custom_notes'] ) : '';

								// load up the receipt template.
								if ( $invoice_payment_data['date_paid'] == '0000-00-00' ) {
									$custom_data = array(
										'receipt_number' => 'N/A',
										'pay_status'     => _l( 'Not Paid Yet' ),
										'payment_date'   => 'Not Yet',
									);
								} else {
									$custom_data = array(
										'receipt_number' => $invoice_payment_data['invoice_payment_id'],
										'pay_status'     => _l( 'Payment Completed' ),
										'payment_date'   => print_date( $invoice_payment_data['date_paid'] ),
									);
								}
								$invoice_payment_data['amount'] = dollar( $invoice_payment_data['amount'], true, $invoice_payment_data['currency_id'] );
								if ( class_exists( 'module_company', false ) && isset( $invoice_data['company_id'] ) && (int) $invoice_data['company_id'] > 0 ) {
									module_company::set_current_company_id( $invoice_data['company_id'] );
								}
								if ( class_exists( 'module_company', false ) && isset( $invoice_data['company_id'] ) && (int) $invoice_data['company_id'] > 0 ) {
									module_company::set_current_company_id( $invoice_data['company_id'] );
								}
								$template = module_template::get_template_by_key( 'invoice_payment_receipt' );


								$data = $this->get_replace_fields( $invoice_payment_data['invoice_id'], $invoice_data );


								$template->assign_values( $data + $invoice_payment_data + $invoice_data + $custom_data );
								echo $template->render( 'pretty_html' );
							}
						}
					}
				}
				break;
		}
	}

	/**
	 * @param $invoice_id
	 * @param $invoice_data
	 *
	 * @return array
	 *
	 * todo: make this method call the other 'get_replace_fields' available in website/job/etc..
	 */
	public static function get_replace_fields( $invoice_id, $invoice_data = false ) {

		if ( ! $invoice_data ) {
			$invoice_data = self::get_invoice( $invoice_id );
		}
		$customer_data = array();
		if ( $invoice_data['customer_id'] ) {
			$customer_data = module_customer::get_replace_fields( $invoice_data['customer_id'], $invoice_data['contact_user_id'] );
		}
		if ( ! is_array( $customer_data ) ) {
			$customer_data = array();
		}

		$data = array_merge( $customer_data, $invoice_data ); // so we get total_amount_due and stuff.

		$data = array_merge( $data, array(
			'invoice_number' => htmlspecialchars( $invoice_data['name'] ),
			'project_type'   => _l( module_config::c( 'project_name_single', 'Website' ) ),
			'print_link'     => self::link_public_print( $invoice_id ),

			'title'           => module_config::s( 'admin_system_name' ),
			'invoice_paid'    => ( $invoice_data['total_amount_due'] <= 0 ) ? '<p> <font style="font-size: 1.6em;"><strong>' . _l( 'INVOICE PAID' ) . '</strong></font> </p>' : '',
			'is_invoice_paid' => ( $invoice_data['total_amount_due'] <= 0 ) ? 1 : '',
			'date_create'     => print_date( $invoice_data['date_create'] ),
			'due_date'        => print_date( $invoice_data['date_due'] ),

		) );

		$data['total_amount']      = dollar( $invoice_data['total_amount'], true, $invoice_data['currency_id'] );
		$data['total_amount_due']  = dollar( $invoice_data['total_amount_due'], true, $invoice_data['currency_id'] );
		$data['total_amount_paid'] = dollar( $invoice_data['total_amount_paid'], true, $invoice_data['currency_id'] );
		$data['date_paid']         = print_date( $invoice_data['date_paid'] );
		$data['date_due']          = print_date( $invoice_data['date_due'] );
		$data['date_sent']         = print_date( $invoice_data['date_sent'] );
		$data['invoice_number']    = $invoice_data['name'];
		$data['invoice_url']       = module_invoice::link_public( $invoice_id );

		$data['invoice_date_range'] = '';
		if ( $invoice_data['date_renew'] != '0000-00-00' ) {
			$data['invoice_date_range'] = _l( '%s to %s', print_date( $invoice_data['date_create'] ), print_date( strtotime( "-1 day", strtotime( $invoice_data['date_renew'] ) ) ) );
		}

		if ( isset( $invoice_data['credit_note_id'] ) && $invoice_data['credit_note_id'] ) {
			$credit_invoice                = module_invoice::get_invoice( $invoice_data['credit_note_id'], true );
			$data['credit_invoice_number'] = htmlspecialchars( $credit_invoice['name'] );
		}

		$data['invoice_notes'] = '';
		// grab any public notes
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			$notes = module_note::get_notes( array( 'public' => 1, 'owner_table' => 'invoice', 'owner_id' => $invoice_id ) );
			if ( count( $notes ) > 1 ) {
				$data['invoice_notes'] .= '<ul>';
				foreach ( $notes as $note ) {
					if ( $note['public'] ) {
						$data['invoice_notes'] .= '<li>';
						$data['invoice_notes'] .= htmlspecialchars( $note['note'] );
						$data['invoice_notes'] .= '</li>';
					}
				}
				$data['invoice_notes'] .= '</ul>';
			} else {
				$note                  = array_shift( $notes );
				$data['invoice_notes'] .= htmlspecialchars( $note['note'] );
			}
		}

		$job_names = $website_url = $project_names = $project_names_and_url = array();
		foreach ( $invoice_data['job_ids'] as $job_id ) {
			$job_data = module_job::get_job( $job_id );
			if ( $job_data && $job_data['job_id'] == $job_id ) {
				$job_names[ $job_data['job_id'] ] = $job_data['name'];
				if ( module_config::c( 'job_invoice_show_date_range', 1 ) ) {
					// check if this job is a renewable job.
					if ( $job_data['date_renew'] != '0000-00-00' ) {
						$data['invoice_date_range'] = _l( '%s to %s', print_date( $job_data['date_start'] ), print_date( strtotime( "-1 day", strtotime( $job_data['date_renew'] ) ) ) );
					}
				}
				$fields = module_job::get_replace_fields( $job_id, $job_data );
				foreach ( $fields as $key => $val ) {
					if ( ! isset( $data[ $key ] ) || ( ! $data[ $key ] && $val ) ) {
						$data[ $key ] = $val;
					}
				}
				if ( ! empty( $job_data['quote_id'] ) ) {
					$quote_fields = module_quote::get_replace_fields( $job_data['quote_id'] );
					foreach ( $quote_fields as $key => $val ) {
						if ( ! isset( $data[ $key ] ) || ( ! $data[ $key ] && $val ) ) {
							$data[ $key ] = $val;
						}
					}
				}
				if ( $job_data['website_id'] ) {
					$website_data = module_website::get_website( $job_data['website_id'] );
					if ( $website_data && $website_data['website_id'] == $job_data['website_id'] ) {
						if ( isset( $website_data['url'] ) && $website_data['url'] ) {
							$website_url[ $website_data['website_id'] ] = module_website::urlify( $website_data['url'] );
							$website_data['name_url']                   = $website_data['name'] . ' (' . module_website::urlify( $website_data['url'] ) . ')';
						} else {
							$website_data['name_url'] = $website_data['name'];
						}
						$project_names[ $website_data['website_id'] ]         = $website_data['name'];
						$project_names_and_url[ $website_data['website_id'] ] = $website_data['name_url'];
						$fields                                               = module_website::get_replace_fields( $website_data['website_id'], $website_data );
						foreach ( $fields as $key => $val ) {
							if ( ! isset( $data[ $key ] ) || ( ! $data[ $key ] && $val ) ) {
								$data[ $key ] = $val;
							}
						}
					}
				}
			}
		}
		if ( isset( $invoice_data['website_id'] ) && $invoice_data['website_id'] ) {
			$website_data = module_website::get_website( $invoice_data['website_id'] );
			if ( $website_data && $website_data['website_id'] == $invoice_data['website_id'] ) {
				if ( isset( $website_data['url'] ) && $website_data['url'] ) {
					$website_url[ $website_data['website_id'] ] = module_website::urlify( $website_data['url'] );
					$website_data['name_url']                   = $website_data['name'] . ' (' . module_website::urlify( $website_data['url'] ) . ')';
				} else {
					$website_data['name_url'] = $website_data['name'];
				}
				$project_names[ $website_data['website_id'] ]         = $website_data['name'];
				$project_names_and_url[ $website_data['website_id'] ] = $website_data['name_url'];
				$fields                                               = module_website::get_replace_fields( $website_data['website_id'], $website_data );
				foreach ( $fields as $key => $val ) {
					if ( ! isset( $data[ $key ] ) || ( ! $data[ $key ] && $val ) ) {
						$data[ $key ] = $val;
					}
				}
			}
		}
		$data['website_name']     = $data['project_name'] = forum_text( count( $project_names ) ? implode( ', ', $project_names ) : '' );
		$data['website_name_url'] = forum_text( count( $project_names_and_url ) ? implode( ', ', $project_names_and_url ) : '' );
		$data['website_url']      = forum_text( count( $website_url ) ? implode( ', ', $website_url ) : '' );
		$data['job_name']         = forum_text( $job_names ? implode( ', ', $job_names ) : '' );


		if ( class_exists( 'module_group', false ) ) {
			// get the job groups
			$wg = array();
			$g  = array();
			foreach ( $invoice_data['job_ids'] as $group_job_id ) {
				$group_job_id = (int) trim( $group_job_id );
				if ( $group_job_id > 0 ) {
					$job_data = module_job::get_job( $group_job_id );
					foreach (
						module_group::get_groups_search( array(
							'owner_table' => 'job',
							'owner_id'    => $group_job_id,
						) ) as $group
					) {
						$g[ $group['group_id'] ] = $group['name'];
					}
					// get the website groups
					if ( $job_data['website_id'] ) {
						foreach (
							module_group::get_groups_search( array(
								'owner_table' => 'website',
								'owner_id'    => $job_data['website_id'],
							) ) as $group
						) {
							$wg[ $group['group_id'] ] = $group['name'];
						}
					}
				}
			}
			if ( isset( $invoice_data['website_id'] ) && $invoice_data['website_id'] ) {
				foreach (
					module_group::get_groups_search( array(
						'owner_table' => 'website',
						'owner_id'    => $invoice_data['website_id'],
					) ) as $group
				) {
					$wg[ $group['group_id'] ] = $group['name'];
				}
			}
			$data['job_group']     = implode( ', ', $g );
			$data['website_group'] = implode( ', ', $wg );
		}

		// addition. find all extra keys for this invoice and add them in.
		// we also have to find any EMPTY extra fields, and add those in as well.
		if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
			$all_extra_fields = module_extra::get_defaults( 'invoice' );
			foreach ( $all_extra_fields as $e ) {
				$data[ $e['key'] ] = _l( 'N/A' );
			}
			// and find the ones with values:
			$extras = module_extra::get_extras( array( 'owner_table' => 'invoice', 'owner_id' => $invoice_id ) );
			foreach ( $extras as $e ) {
				$data[ $e['extra_key'] ] = $e['extra'];
			}
		}

		$new_data = hook_handle_callback( 'invoice_replace_fields', $invoice_id, $data );
		if ( is_array( $new_data ) ) {
			foreach ( $new_data as $new_d ) {
				$data = array_merge( $data, $new_d );
			}
		}

		return $data;
	}


	public function process() {
		$errors = array();
		if ( $_REQUEST['_process'] == 'make_payment' ) {
			$this->handle_payment();
		} else if ( isset( $_REQUEST['butt_del'] ) && $_REQUEST['butt_del'] && $_REQUEST['invoice_id'] && module_invoice::can_i( 'delete', 'Invoices' ) ) {
			$data = self::get_invoice( $_REQUEST['invoice_id'] );
			if ( module_form::confirm_delete( 'invoice_id', _l( "Really delete invoice: %s", htmlspecialchars( $data['name'] ) ), self::link_open( $_REQUEST['invoice_id'] ) ) ) {
				$invoice_data = self::get_invoice( $_REQUEST['invoice_id'], true );
				$this->delete_invoice( $_REQUEST['invoice_id'] );
				set_message( "Invoice deleted successfully" );
				if ( isset( $invoice_data['job_ids'] ) && $invoice_data['job_ids'] ) {
					redirect_browser( module_job::link_open( current( $invoice_data['job_ids'] ) ) );
				} else {
					redirect_browser( self::link_open( false ) );
				}
			}
		} else if ( "assign_credit_to_customer" == $_REQUEST['_process'] ) {
			$invoice_id = (int) $_REQUEST['invoice_id'];
			if ( $invoice_id > 0 ) {
				$invoice_data = $this->get_invoice( $invoice_id );
				$credit       = $invoice_data['total_amount_credit'];
				if ( $credit > 0 ) {
					if ( $invoice_data['customer_id'] ) {
						// assign to customer.
						module_customer::add_credit( $invoice_data['customer_id'], $credit );
						// assign this as a negative payment, and also give it to the customer account.
						$this->add_history( $invoice_id, 'Added ' . dollar( $credit ) . ' credit to customers account from this invoice overpayment' );
						update_insert( 'invoice_payment_id', 'new', 'invoice_payment', array(
							'invoice_id'   => $invoice_id,
							'amount'       => - $credit,
							'payment_type' => _INVOICE_PAYMENT_TYPE_OVERPAYMENT_CREDIT,
							'currency_id'  => $invoice_data['currency_id'],
							'method'       => _l( 'Assigning Credit' ),
							'date_paid'    => date( 'Y-m-d' ),
						) );

						module_cache::clear( 'invoice' );
					}
				}
				redirect_browser( $this->link_open( $invoice_id ) );
			}
		} else if ( "save_invoice" == $_REQUEST['_process'] ) {

			$invoice_id = isset( $_REQUEST['invoice_id'] ) ? (int) $_REQUEST['invoice_id'] : false;
			// check the user has permissions to edit this page.
			if ( $invoice_id > 0 ) {
				$invoice = $this->get_invoice( $invoice_id );
				if ( ! module_security::can_access_data( 'invoice', $invoice, $invoice_id ) ) {
					echo 'Data access denied. Sorry.';
					exit;
				}

			}

			if ( ! $this->can_i( 'edit', 'Invoices' ) ) {
				// bug fix, customer making a payment displays this edit access denied.
				if ( isset( $_REQUEST['butt_makepayment'] ) && $_REQUEST['butt_makepayment'] == 'yes' ) {
					//redirect_browser(self::link_public_pay($invoice_id));
					self::handle_payment();

					return;
				} else {
					echo 'Edit access denied. Sorry.';
					exit;
				}
			}
			$data = $_POST;

			if ( isset( $data['default_renew_auto'] ) && ! isset( $data['renew_auto'] ) ) {
				$data['renew_auto'] = 0;
			}
			if ( isset( $data['default_renew_email'] ) && ! isset( $data['renew_email'] ) ) {
				$data['renew_email'] = 0;
			}
			if ( isset( $data['default_overdue_email_auto'] ) && ! isset( $data['overdue_email_auto'] ) ) {
				$data['overdue_email_auto'] = 0;
			}
			if ( isset( $data['set_manual_company_id'] ) ) {
				$data['company_id'] = $data['set_manual_company_id'];
			}

			if ( isset( $data['customer_id'] ) && $data['customer_id'] && ( ! isset( $data['contact_user_id'] ) || ! $data['contact_user_id'] ) ) {
				// find the primary contact for this invoice and set that there?
				// no - we don't! we leave it as blank so we can update the customer primary contact when needed.
				/*
                $customer_data = module_customer::get_customer($data['customer_id']);
                if($customer_data && $customer_data['customer_id'] == $data['customer_id']){
                    if($customer_data['primary_user_id']){
                        $data['user_id'] = $customer_data['primary_user_id'];
                    }else{
                        $customer_contacts = module_user::get_contacts(array('customer_id'=>$data['customer_id']));
                        foreach($customer_contacts as $contact){
                            // todo - search roles or something to find the accountant.
                            $data['user_id'] = $contact['user_id'];
                            break;
                        }
                    }
                }*/

			}


			// check for credit assessment.
			if ( isset( $_POST['apply_credit_from_customer'] ) && $_POST['apply_credit_from_customer'] == 'do' ) {
				$invoice_data  = $this->get_invoice( $invoice_id );
				$customer_data = module_customer::get_customer( $invoice_data['customer_id'] );
				if ( $customer_data['credit'] > 0 ) {
					$apply_credit = min( $invoice_data['total_amount_due'], $customer_data['credit'] );
					//$invoice_data['discount_amount'] += $customer_data['credit'];
					//$this->save_invoice($invoice_id,array('discount_amount'=>$invoice_data['discount_amount'],'discount_description'=>_l('Credit:')));
					update_insert( 'invoice_payment_id', false, 'invoice_payment', array(
						'invoice_id'   => $invoice_id,
						'payment_type' => _INVOICE_PAYMENT_TYPE_CREDIT,
						'method'       => _l( 'Credit' ),
						'amount'       => $apply_credit,
						'currency_id'  => $invoice_data['currency_id'],
						'other_id'     => $invoice_data['customer_id'],
						'date_paid'    => date( 'Y-m-d' ),
					) );
					$this->add_history( $invoice_id, _l( 'Applying %s customer credit to this invoice.', dollar( $apply_credit ) ) );
					module_cache::clear( 'invoice' );
					module_customer::remove_credit( $customer_data['customer_id'], $apply_credit );
				}
			}
			// check for subsscription credit assessment.
			if ( isset( $_POST['apply_credit_from_subscription_bucket'] ) && $_POST['apply_credit_from_subscription_bucket'] == 'do' && (int) $_POST['apply_credit_from_subscription_id'] > 0 ) {
				$invoice_data                  = $this->get_invoice( $invoice_id );
				$subscription_owner            = module_subscription::get_subscription_owner( $_POST['apply_credit_from_subscription_id'] );
				$available_subscription_credit = module_subscription::get_available_credit( $subscription_owner['owner_table'], $subscription_owner['owner_id'] );
				//print_r($subscription_owner); print_r($available_subscription_credit);exit;
				if ( $subscription_owner['subscription_owner_id'] && $available_subscription_credit[ $subscription_owner['subscription_id'] ]['remain'] > 0 ) {
					$apply_credit = min( $invoice_data['total_amount_due'], $available_subscription_credit[ $subscription_owner['subscription_id'] ]['remain'] );
					//$invoice_data['discount_amount'] += $customer_data['credit'];
					//$this->save_invoice($invoice_id,array('discount_amount'=>$invoice_data['discount_amount'],'discount_description'=>_l('Credit:')));
					update_insert( 'invoice_payment_id', false, 'invoice_payment', array(
						'invoice_id'   => $invoice_id,
						'payment_type' => _INVOICE_PAYMENT_TYPE_SUBSCRIPTION_CREDIT,
						'method'       => _l( 'Credit' ),
						'amount'       => $apply_credit,
						'currency_id'  => $invoice_data['currency_id'],
						'other_id'     => $subscription_owner['subscription_owner_id'],
						'date_paid'    => date( 'Y-m-d' ),
					) );
					$this->add_history( $invoice_id, _l( 'Applying %s subscription credit to this invoice.', dollar( $apply_credit ) ) );
					module_cache::clear( 'invoice' );
				}
			}
			$invoice_id = $this->save_invoice( $invoice_id, $data );

			if ( isset( $_REQUEST['allowed_payment_method_check'] ) ) {
				// todo - ability to disable ALL payment methods. - array wont be set if none are ticked
				$payment_methods = handle_hook( 'get_payment_methods' );
				foreach ( $payment_methods as &$payment_method ) {
					if ( $payment_method->is_enabled() ) {
						// is this one already enabled for this invoice?
						//$is_already_allowed = $payment_method->is_allowed_for_invoice($invoice_id);
						if ( isset( $_REQUEST['allowed_payment_method'] ) && isset( $_REQUEST['allowed_payment_method'][ $payment_method->module_name ] ) ) {
							$payment_method->set_allowed_for_invoice( $invoice_id, 1 );
						} else {
							$payment_method->set_allowed_for_invoice( $invoice_id, 0 );
						}
					}
				}
			}

			// check if we are generating any renewals
			if ( isset( $_REQUEST['generate_renewal'] ) && $_REQUEST['generate_renewal'] > 0 ) {
				$new_invoice_id = $this->renew_invoice( $invoice_id );
				if ( $new_invoice_id ) {
					set_message( "Invoice renewed successfully" );
					redirect_browser( module_invoice::link_open( $new_invoice_id, false ) );
				} else {

				}
			}


			if ( isset( $_REQUEST['butt_makepayment'] ) && $_REQUEST['butt_makepayment'] == 'yes' ) {
				//redirect_browser(self::link_public_pay($invoice_id));
				self::handle_payment();
			} else if ( isset( $_REQUEST['butt_print'] ) && $_REQUEST['butt_print'] ) {
				$_REQUEST['_redirect'] = self::link_generate( $invoice_id, array( 'arguments' => array( 'print' => 1 ) ) );;
			} else if ( isset( $_REQUEST['butt_merge'] ) && $_REQUEST['butt_merge'] && isset( $_REQUEST['merge_invoice'] ) && is_array( $_REQUEST['merge_invoice'] ) ) {
				$merge_invoice_ids = self::check_invoice_merge( $invoice_id );
				foreach ( $merge_invoice_ids as $merge_invoice ) {
					if ( isset( $_REQUEST['merge_invoice'][ $merge_invoice['invoice_id'] ] ) ) {
						// copy all the tasks from that invoice over to this invoice.
						$sql = "UPDATE `" . _DB_PREFIX . "invoice_item` SET invoice_id = " . (int) $invoice_id . " WHERE invoice_id = " . (int) $merge_invoice['invoice_id'] . " ";
						query( $sql );
						$this->delete_invoice( $merge_invoice['invoice_id'] );
					}
				}
				$_REQUEST['_redirect'] = $this->link_open( $invoice_id );
				set_message( 'Invoices merged successfully' );
			} else if ( isset( $_REQUEST['butt_email'] ) && $_REQUEST['butt_email'] ) {
				$_REQUEST['_redirect'] = self::link_generate( $invoice_id, array( 'arguments' => array( 'email' => 1 ) ) );;
			} else if ( ! empty( $_REQUEST['butt_archive'] ) ) {
				$UCMInvoice = new UCMInvoice( $invoice_id );
				if ( $UCMInvoice->is_archived() ) {
					$UCMInvoice->unarchive();
					set_message( "Invoice unarchived successfully" );
				} else {
					$UCMInvoice->archive();
					set_message( "Invoice archived successfully" );
				}
				$_REQUEST['_redirect'] = self::link_generate( $invoice_id );
			} else if ( isset( $_REQUEST['butt_generate_credit'] ) && $_REQUEST['butt_generate_credit'] ) {
				// generate a credit note against this invioce.
				// to do this we duplicate the invoice, remove the cancel date, remove the sent date,
				// set a new create date, set the credit_note_id variable, remove the paid date,
				// (copied from the generate renewal code above)
				$invoice = $this->get_invoice( $invoice_id );
				unset( $invoice['invoice_id'] );
				unset( $invoice['date_renew'] );
				unset( $invoice['date_sent'] );
				unset( $invoice['date_paid'] );
				unset( $invoice['date_cancel'] );
				unset( $invoice['renew_invoice_id'] );
				unset( $invoice['deposit_job_id'] );


				$ucminvoice                      = new UCMInvoice();
				$ucminvoice->customer_id         = $invoice['customer_id'];
				$invoice['name']                 = $ucminvoice->get_new_document_number();
				$invoice['credit_note_id']       = $invoice_id;
				$invoice['date_create']          = date( 'Y-m-d' );
				$invoice['discount_amount']      = 0;
				$invoice['discount_description'] = _l( 'Discount:' );
				$invoice['discount_type']        = module_config::c( 'invoice_discount_type', _DISCOUNT_TYPE_BEFORE_TAX ); // 1 = After Tax
				$invoice['tax_type']             = module_config::c( 'invoice_tax_type', 0 );
				$invoice['date_due']             = false;
				$invoice['status']               = module_config::s( 'invoice_status_default', 'New' );
				$new_invoice_id                  = $this->save_invoice( 'new', $invoice );
				if ( $new_invoice_id ) {
					// now we create the tasks
					$tasks = $this->get_invoice_items( $invoice_id, $invoice );
					foreach ( $tasks as $task ) {
						unset( $task['invoice_item_id'] );
						if ( $task['custom_description'] ) {
							$task['description'] = $task['custom_description'];
						}
						if ( $task['custom_long_description'] ) {
							$task['long_description'] = $task['custom_long_description'];
						}
						$task['invoice_id'] = $new_invoice_id;
						$task['date_done']  = $invoice['date_create'];
						update_insert( 'invoice_item_id', 'new', 'invoice_item', $task );
					}
					set_message( "Credit note generated successfully" );
					module_cache::clear( 'invoice' );
					redirect_browser( $this->link_open( $new_invoice_id ) );
				} else {
					set_error( 'Generating credit note failed' );
					redirect_browser( $this->link_open( $invoice_id ) );
				}
			} else {
				$_REQUEST['_redirect'] = isset( $_REQUEST['_redirect'] ) && ! empty( $_REQUEST['_redirect'] ) ? $_REQUEST['_redirect'] : $this->link_open( $invoice_id );
				set_message( "Invoice saved successfully" );
			}
		}
		if ( ! count( $errors ) ) {
			redirect_browser( $_REQUEST['_redirect'] );
			exit;
		}
		print_error( $errors, true );
	}


	public static function get_invoices( $search = array(), $return_options = array() ) {
		// limit based on customer id
		/*if(!isset($_REQUEST['customer_id']) || !(int)$_REQUEST['customer_id']){
			return array();
		}*/
		// build up a custom search sql query based on the provided search fields
		$sql  = "SELECT u.*,u.invoice_id AS id ";
		$sql  .= ", u.name AS name ";
		$sql  .= ", c.customer_name ";
		$from = " FROM `" . _DB_PREFIX . "invoice` u ";
		$from .= " LEFT JOIN `" . _DB_PREFIX . "customer` c USING (customer_id)";
		$from .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` ii ON u.invoice_id = ii.invoice_id ";

		$from .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON ii.task_id = t.task_id";
		if ( ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) || ( isset( $search['website_id'] ) && (int) $search['website_id'] > 0 ) ) {
			$from .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id";
		}
		if ( class_exists( 'module_subscription', false ) ) {
			$sql  .= ", GROUP_CONCAT(DISTINCT subh.subscription_id ORDER BY subh.subscription_id) AS invoice_subscription_ids ";
			$from .= " LEFT JOIN `" . _DB_PREFIX . "subscription_history` subh ON u.invoice_id = subh.invoice_id ";
		}

		$where = " WHERE 1 ";
		if ( is_array( $return_options ) && isset( $return_options['custom_where'] ) ) {
			// put in return options so harder to push through from user end.
			$where .= $return_options['custom_where'];
		}


		if ( ! empty( $search['archived_status'] ) ) {
			switch ( $search['archived_status'] ) {
				case _ARCHIVED_SEARCH_NONARCHIVED:
					$where .= ' AND u.archived = 0 ';
					break;
				case _ARCHIVED_SEARCH_ARCHIVED:
					$where .= ' AND u.archived = 1 ';
					break;
				case _ARCHIVED_SEARCH_BOTH:
					//                    $where .= ' AND u.archived = 0 ';
					break;
			}
		}

		if ( isset( $search['generic'] ) && $search['generic'] ) {
			$str   = db_escape( $search['generic'] );
			$where .= " AND ( ";
			$where .= " u.name LIKE '%$str%' ";
			//$where .= "OR  u.url LIKE '%$str%'  ";
			$where .= ' ) ';
		}
		foreach (
			array(
				'customer_id',
				'status',
				'name',
				'date_paid',
				'date_due',
				'renew_invoice_id',
				'credit_note_id'
			) as $key
		) {
			if ( isset( $search[ $key ] ) && $search[ $key ] !== '' && $search[ $key ] !== false ) {
				$str   = db_escape( $search[ $key ] );
				$where .= " AND u.`$key` = '$str'";
			}
		}
		if ( isset( $search['date_from'] ) && $search['date_from'] ) {
			$str   = db_escape( input_date( $search['date_from'] ) );
			$where .= " AND ( ";
			$where .= " u.date_create >= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_to'] ) && $search['date_to'] ) {
			$str   = db_escape( input_date( $search['date_to'] ) );
			$where .= " AND ( ";
			$where .= " u.date_create <= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_paid_from'] ) && $search['date_paid_from'] ) {
			$str   = db_escape( input_date( $search['date_paid_from'] ) );
			$where .= " AND ( ";
			$where .= " u.date_paid >= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['date_paid_to'] ) && $search['date_paid_to'] ) {
			$str   = db_escape( input_date( $search['date_paid_to'] ) );
			$where .= " AND ( ";
			$where .= " u.date_paid <= '$str' ";
			$where .= ' ) ';
		}
		if ( isset( $search['job_id'] ) && (int) $search['job_id'] > 0 ) {
			$where .= " AND ( t.`job_id` = " . (int) $search['job_id'] . ' OR ';
			$where .= "  u.deposit_job_id = " . (int) $search['job_id'];
			$where .= ' ) ';
		}
		if ( isset( $search['website_id'] ) && (int) $search['website_id'] > 0 ) {
			$where .= " AND ( u.`website_id` =  " . (int) $search['website_id'] . " OR  j.`website_id` = " . (int) $search['website_id'] . ' )';
		}
		if ( isset( $search['deposit_job_id'] ) && (int) $search['deposit_job_id'] > 0 ) {
			$where .= " AND ( u.deposit_job_id = " . (int) $search['deposit_job_id'];
			$where .= ' ) ';
		}
		if ( isset( $search['customer_group_id'] ) && (int) $search['customer_group_id'] > 0 ) {
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "group_member` gm ON (c.customer_id = gm.owner_id)";
			$where .= " AND (gm.group_id = '" . (int) $search['customer_group_id'] . "' AND gm.owner_table = 'customer')";
		}
		if ( isset( $search['renewing'] ) && $search['renewing'] ) {
			$where .= " AND u.date_renew != '0000-00-00' AND (u.renew_invoice_id IS NULL OR u.renew_invoice_id = 0) ";
		}

		if ( isset( $search['company_id'] ) && trim( $search['company_id'] ) ) {
			$str = (int) $search['company_id'];
			// search all the customer site addresses.
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "company_customer` ccr ON (c.customer_id = ccr.customer_id)";
			$where .= " AND (ccr.company_id = '$str')";
		}

		if ( isset( $search['ticket_id'] ) && (int) $search['ticket_id'] > 0 ) {
			// join on the ticket_quote_rel tab.e
			$from  .= " LEFT JOIN `" . _DB_PREFIX . "ticket_invoice_rel` tir ON u.invoice_id = tir.invoice_id";
			$where .= " AND tir.ticket_id = " . (int) $search['ticket_id'];

		}


		switch ( self::get_invoice_access_permissions() ) {
			case _INVOICE_ACCESS_ALL:
				break;
			case _INVOICE_ACCESS_STAFF:
				$where .= " AND u.vendor_user_id = " . (int) module_security::get_loggedin_id();
				break;
			case _INVOICE_ACCESS_JOB:
				$valid_job_ids = module_job::get_jobs();
				$where         .= " AND ( t.`job_id` IN ( ";
				if ( count( $valid_job_ids ) ) {
					foreach ( $valid_job_ids as $valid_job_id ) {
						$where .= (int) $valid_job_id['job_id'] . ", ";
					}
					$where = rtrim( $where, ', ' );
				} else {
					$where .= ' NULL ';
				}
				$where .= ' ) ';
				$where .= " OR ";
				$where .= "  u.deposit_job_id IN ( ";
				if ( count( $valid_job_ids ) ) {
					foreach ( $valid_job_ids as $valid_job_id ) {
						$where .= (int) $valid_job_id['job_id'] . ", ";
					}
					$where = rtrim( $where, ', ' );
				} else {
					$where .= ' NULL ';
				}
				$where .= ' ) ';
				$where .= " )";
				break;
			case _INVOICE_ACCESS_CUSTOMER:
				$valid_customer_ids = module_security::get_customer_restrictions();
				$where              .= " AND u.customer_id IN ( ";
				if ( count( $valid_customer_ids ) ) {
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
				} else {
					$where .= ' NULL ';
				}
				$where .= " )";
		}

		// permissions from customer module.
		// tie in with customer permissions to only get jobs from customers we can access.
		switch ( module_customer::get_customer_data_access() ) {
			case _CUSTOMER_ACCESS_ALL:
				// all customers! so this means all jobs!
				break;
			case _CUSTOMER_ACCESS_ALL_COMPANY:
			case _CUSTOMER_ACCESS_CONTACTS:
			case _CUSTOMER_ACCESS_TASKS:
			case _CUSTOMER_ACCESS_STAFF:
				$valid_customer_ids = module_security::get_customer_restrictions();
				$where              .= " AND u.customer_id IN ( ";
				if ( count( $valid_customer_ids ) ) {
					foreach ( $valid_customer_ids as $valid_customer_id ) {
						$where .= (int) $valid_customer_id . ", ";
					}
					$where = rtrim( $where, ', ' );
				} else {
					$where .= ' NULL ';
				}
				$where .= " )";
		}


		$group_order = ' GROUP BY u.invoice_id ORDER BY u.date_create DESC'; // stop when multiple company sites have same region
		$sql         = $sql . $from . $where . $group_order;
		$result      = qa( $sql );

		//module_security::filter_data_set("invoice",$result);
		return $result;
		//		return get_multiple("invoice",$search,"invoice_id","fuzzy","name");

	}

	public static function get_invoice_items( $invoice_id, $invoice = array() ) {
		$invoice_id    = (int) $invoice_id;
		$invoice_items = array();


		if ( ! $invoice_id && ! empty( $_REQUEST['timer_ids'] ) ) {


			$invoice_items = array();
			$timer_ids     = explode( ',', $_REQUEST['timer_ids'] );
			$new_id        = 0;
			foreach ( $timer_ids as $timer_id ) {
				$timer_id = (int) $timer_id;
				if ( $timer_id ) {
					$ucmtimer        = new UCMTimer( $timer_id );
					$time_in_seconds = $ucmtimer->get_total_time( true, true );
					$hours           = round( $time_in_seconds / 3600, 2 );

					$invoice_items[ 'new' . $new_id ++ ] = array(
						'description'             => $ucmtimer->description,
						'custom_description'      => '',
						'long_description'        => '',
						'custom_long_description' => '',
						'amount'                  => 0,
						'manual_task_type'        => _TASK_TYPE_HOURS_AMOUNT,
						'hours'                   => $hours,
						'taxable'                 => module_customer::c( 'task_taxable_default', 1, ! empty( $invoice['customer_id'] ) ? $invoice['customer_id'] : 0 ),
						'task_id'                 => 0,
						'timer_id'                => $timer_id,
					);
				}
			}

		} else if ( ! $invoice_id && isset( $_REQUEST['job_id'] ) && (int) $_REQUEST['job_id'] > 0 ) {

			// hack for half completed invoices
			if ( isset( $_REQUEST['amount_due'] ) && $_REQUEST['amount_due'] > 0 ) {

				$amount = (float) $_REQUEST['amount_due'];


				$invoice_items = array(
					'new0' => array(
						'description'             => isset( $_REQUEST['description'] ) ? $_REQUEST['description'] : _l( 'Invoice Item' ),
						'custom_description'      => '',
						'long_description'        => '',
						'custom_long_description' => '',
						'amount'                  => $amount,
						'manual_task_type'        => _TASK_TYPE_AMOUNT_ONLY,
						'hours'                   => 0,
						'taxable'                 => module_config::c( 'deposit_task_taxable_default', 0 ),
						'task_id'                 => 0,
					),
				);


			} else {

				$job_id = (int) $_REQUEST['job_id'];
				if ( $job_id > 0 ) {
					// we return the items from the job rather than the items from the invoice.
					// for new invoice creation.
					$tasks                  = module_job::get_invoicable_tasks( $job_id );
					$x                      = 0;
					$job                    = module_job::get_job( $job_id, false );
					$invoice['hourly_rate'] = $job['hourly_rate'];
					foreach ( $tasks as $task ) {
						if ( ! isset( $task['custom_description'] ) ) {
							$task['custom_description'] = '';
						}
						if ( ! isset( $task['custom_long_description'] ) ) {
							$task['custom_long_description'] = '';
						}
						//$task['task_id'] = 'new'.$x;
						// the 'hourly_rate' column will hold either
						// = for hours/amount the default hourly rate from the job
						// = for qty/amount the raw amount that will multiplu hours by
						// = for amount only will be the raw amount.
						$invoice_task_type = isset( $task['manual_task_type'] ) && $task['manual_task_type'] >= 0 ? $task['manual_task_type'] : $job['default_task_type'];
						if ( $invoice_task_type == _TASK_TYPE_QTY_AMOUNT ) {
							$task['hourly_rate'] = $task['amount'];
							$task['amount']      = 0; // this forces our calc below to calculate teh amount for us.
						} else {
							$task['hourly_rate'] = $job['hourly_rate'];
						}
						$invoice_items[ 'new' . $x ] = $task;
						$x ++;
					}
					//print_r($tasks);exit;
				}
			}
		} else if ( $invoice_id ) {

			if ( ! $invoice ) {
				$invoice = self::get_invoice( $invoice_id, true );
			}
			$sql           = "SELECT ii.invoice_item_id AS id, ii.*, t.job_id, t.description AS description, ii.description as custom_description, ii.long_description as custom_long_description, t.task_order, ii.task_order AS custom_task_order "; // , j.hourly_rate
			$sql           .= ", t.date_done AS task_date_done ";
			$sql           .= ", t.task_id ";
			$sql           .= " FROM `" . _DB_PREFIX . "invoice_item` ii ";
			$sql           .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON ii.task_id = t.task_id ";
			$sql           .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id ";
			$sql           .= " WHERE ii.invoice_id = $invoice_id";
			$sql           .= " ORDER BY custom_task_order, t.task_order ";
			$invoice_items = qa( $sql );
		}
		//        print_r($invoice_items);
		// DAVE READ THIS: tasks come in with 'hours' and 'amount' and 'manual_task_type'
		// calculate the 'task_hourly_rate' and 'invoite_item_amount' based on this.
		// 'amount' is NOT used in invoice items. only 'invoice_item_amount'
		//echo '<pre>';print_r($invoice_items);echo '</pre>';
		foreach ( $invoice_items as $invoice_item_id => $invoice_item_data ) {

			$invoice_item_data['task_hours']           = '';
			$invoice_item_data['task_hours_completed'] = '';
			if ( isset( $invoice_item_data['job_id'] ) && $invoice_item_data['task_id'] && $invoice_item_data['job_id'] && $invoice_item_data['task_id'] ) {
				$job_tasks = module_job::get_tasks( $invoice_item_data['job_id'] );
				if ( isset( $job_tasks[ $invoice_item_data['task_id'] ] ) ) {
					// copied from ajax_task_edit.php:
					if ( function_exists( 'decimal_time_out' ) ) {
						$completed_value = decimal_time_out( $job_tasks[ $invoice_item_data['task_id'] ]['completed'] );
						$hours_value     = decimal_time_out( $job_tasks[ $invoice_item_data['task_id'] ]['hours'] );
					} else {
						$completed_value = number_out( $job_tasks[ $invoice_item_data['task_id'] ]['completed'], true );
						$hours_value     = number_out( $job_tasks[ $invoice_item_data['task_id'] ]['hours'], true );
					}
					$invoice_item_data['task_hours']           = $hours_value;
					$invoice_item_data['task_hours_completed'] = $completed_value;

				}
			}
			// new feature, task type.
			$invoice_item_data['manual_task_type_real'] = $invoice_item_data['manual_task_type'];
			if ( $invoice_item_data['manual_task_type'] < 0 && isset( $invoice['default_task_type'] ) ) {
				$invoice_item_data['manual_task_type'] = $invoice['default_task_type'];
			}

			if ( is_callable( 'module_product::sanitise_product_name' ) ) {
				$invoice_item_data = module_product::sanitise_product_name( $invoice_item_data, $invoice['default_task_type'] );
			}

			if ( isset( $invoice_item_data['hours_mins'] ) ) {
				if ( $invoice_item_data['hours_mins'] == 0 ) {
					$invoice_item_data['hours_mins'] = 0;
				} else {
					$invoice_item_data['hours_mins'] = str_replace( ".", ":", $invoice_item_data['hours_mins'] );
				}
			}

			// if there are no hours logged against this task
			if ( ! $invoice_item_data['hours'] ) {
				//$invoice_item_data['task_hourly_rate']=0;
			}
			// task_hourly_rate is used for calculations, if the hourly_rate is -1 then we use the default invoice hourly rate
			$invoice_item_data['task_hourly_rate'] = isset( $invoice_item_data['hourly_rate'] ) && $invoice_item_data['hourly_rate'] != 0 && $invoice_item_data['hourly_rate'] != - 1 ? $invoice_item_data['hourly_rate'] : $invoice['hourly_rate'];
			// if we have a custom price for this task
			if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_HOURS_AMOUNT ) {
				if ( $invoice_item_data['amount'] != 0 ) {
					$invoice_item_data['invoice_item_amount'] = $invoice_item_data['amount'];
					if ( $invoice_item_data['hours'] == 0 ) {
						// hack to fix $0 invoices
						$invoice_item_data['hours']            = 1;
						$invoice_item_data['task_hourly_rate'] = $invoice_item_data['amount'];
					}
					//echo '<pre>';print_r($invoice_items);echo '</pre>';
					if ( isset( $invoice_item_data['hours_mins'] ) && ! empty( $invoice_item_data['hours_mins'] ) && function_exists( 'decimal_time_in' ) ) {
						$invoice_item_data['hours'] = decimal_time_in( $invoice_item_data['hours_mins'] );
					}
					if ( $invoice_item_data['task_hourly_rate'] * $invoice_item_data['hours'] != $invoice_item_data['amount'] ) {
						// check the rounding, just to be sure.
						if ( round( $invoice_item_data['task_hourly_rate'] * $invoice_item_data['hours'], 2 ) == round( $invoice_item_data['amount'], 2 ) ) {
							// all good
						} else {
							// hack to fix manual amount with non-matching hours.
							$invoice_item_data['task_hourly_rate'] = $invoice_item_data['amount'] / $invoice_item_data['hours'];
						}
					}
				} else {
					$invoice_item_data['invoice_item_amount'] = $invoice_item_data['task_hourly_rate'] * $invoice_item_data['hours'];
				}
			} else if ( $invoice_item_data['manual_task_type'] == _TASK_TYPE_QTY_AMOUNT ) {
				if ( $invoice_item_data['amount'] != 0 ) {
					$invoice_item_data['invoice_item_amount'] = $invoice_item_data['amount'];
				} else {
					$invoice_item_data['invoice_item_amount'] = $invoice_item_data['task_hourly_rate'] * $invoice_item_data['hours'];
				}
				//$invoice_item_data['amount'] = $invoice_item_data['hourly_rate'] * $invoice_item_data['hours'];
				//$invoice_item_data['invoice_item_amount']  = $invoice_item_data['amount'];
				//$invoice_item_data['task_hourly_rate'] = $invoice_item_data['hourly_rate'];
				/*if($invoice_item_data['hours']>0){
                    $invoice_item_data['task_hourly_rate'] = round($invoice_item_data['invoice_item_amount']/$invoice_item_data['hours'],module_config::c('currency_decimal_places',2));
                }else{
                }*/
			} else {

				// this item is an 'amount only' column.
				// no calculations based on quantity and hours.
				if ( $invoice_item_data['amount'] != 0 ) {
					$invoice_item_data['task_hourly_rate']    = $invoice_item_data['amount'];
					$invoice_item_data['invoice_item_amount'] = $invoice_item_data['amount'];
				} else {
					$invoice_item_data['task_hourly_rate']    = 0;
					$invoice_item_data['invoice_item_amount'] = 0;

				}
				/*

                $invoice_item_data['task_hourly_rate'] = isset($invoice_item_data['hourly_rate']) && $invoice_item_data['hourly_rate']>0 ? $invoice_item_data['hourly_rate'] : $invoice['hourly_rate'];

                if($invoice_item_data['amount']!=0 && $invoice_item_data['amount'] != ($invoice_item_data['hours']*$invoice_item_data['task_hourly_rate'])){
                    $invoice_item_data['invoice_item_amount'] = $invoice_item_data['amount'];
                    if(module_config::c('invoice_calculate_item_price_auto',1) && $invoice_item_data['hours'] > 0){
                        $invoice_item_data['task_hourly_rate'] = round($invoice_item_data['invoice_item_amount']/$invoice_item_data['hours'],module_config::c('currency_decimal_places',2));
                    }else{
                        $invoice_item_data['task_hourly_rate'] = false;
                    }
                }else if($invoice_item_data['hours']>0){
                    $invoice_item_data['invoice_item_amount'] = $invoice_item_data['hours']*$invoice_item_data['task_hourly_rate'];
                }else{
                    $invoice_item_data['invoice_item_amount'] = 0;
                    $invoice_item_data['task_hourly_rate'] = false;
                }*/
			}
			/*$invoice_item_amount = $invoice_item_data['amount'] > 0 ? $invoice_item_data['amount'] : $invoice_item_data['hours']*$task_hourly_rate;
            if($invoice_item_data['amount']>0 && !$invoice_item_data['hours']){
                $invoice_item_amount = $invoice_item_data['amount'];
                $invoice_item_data['hours'] = 1;
                $task_hourly_rate = $invoice_item_data['amount']; // not sure if this will be buggy
            }else{
                $invoice_item_amount = $invoice_item_data['hours']*$task_hourly_rate;
            }*/

			// new feature, date done.
			if ( isset( $invoice_item_data['date_done'] ) && $invoice_item_data['date_done'] != '0000-00-00' ) {
				// $invoice_item_data['date_done'] is ok to print!
			} else {
				$invoice_item_data['date_done'] = '0000-00-00';
				// check if this is linked to a task.
				if ( $invoice_item_data['task_id'] ) {
					if ( isset( $invoice_item_data['task_date_done'] ) ) {
						// moved it into SQL above, instead of doing a get_single() call below for each invoice line item
						if ( $invoice_item_data['task_date_done'] && $invoice_item_data['task_date_done'] != '0000-00-00' ) {
							$invoice_item_data['date_done'] = $invoice_item_data['task_date_done'];
						} else if ( isset( $invoice['date_create'] ) && $invoice['date_create'] != '0000-00-00' ) {
							$invoice_item_data['date_done'] = $invoice['date_create'];
						}
					} else {
						$task = get_single( 'task', 'task_id', $invoice_item_data['task_id'] );
						if ( $task && isset( $task['date_done'] ) && $task['date_done'] != '0000-00-00' ) {
							$invoice_item_data['date_done'] = $task['date_done']; // move it over ready for printing below
						} else {
							if ( isset( $invoice['date_create'] ) && $invoice['date_create'] != '0000-00-00' ) {
								$invoice_item_data['date_done'] = $invoice['date_create'];
							}
						}
					}
				}
			}

			// set a default taxes to match the invoice taxes if none defined
			if ( ( ! isset( $invoice_item_data['taxes'] ) || ! count( $invoice_item_data['taxes'] ) ) && isset( $invoice_item_data['taxable'] ) && $invoice_item_data['taxable'] && isset( $invoice['taxes'] ) && count( $invoice['taxes'] ) ) {
				$invoice_item_data['taxes'] = $invoice['taxes'];
			}
			if ( ! isset( $invoice_item_data['taxes'] ) ) {
				$invoice_item_data['taxes'] = array();
			}

			$invoice_items[ $invoice_item_id ] = $invoice_item_data;

		}

		//print_r($invoice_items);exit;
		return $invoice_items;
	}

	public static function get_invoice_payments( $invoice_id ) {
		$invoice_id = (int) $invoice_id;

		return get_multiple( "invoice_payment", array( 'invoice_id' => $invoice_id ), "invoice_payment_id", "exact", "invoice_payment_id", true );
	}

	public static function get_invoice_payment( $invoice_payment_id ) {
		$invoice_payment_id = (int) $invoice_payment_id;

		$sql            = "SELECT *, invoice_payment_id AS id FROM `" . _DB_PREFIX . "invoice_payment` WHERE invoice_id IN (SELECT invoice_id FROM `" . _DB_PREFIX . "invoice_payment` WHERE invoice_payment_id = $invoice_payment_id)";
		$other_payments = qa( $sql );
		if ( isset( $other_payments[ $invoice_payment_id ] ) ) {
			$payment = $other_payments[ $invoice_payment_id ];
			unset( $other_payments[ $invoice_payment_id ] );
			if ( $payment['amount'] > 0 && count( $other_payments ) > 0 ) {
				// has this one been refunded?
				$amount_left_to_refund = $payment['amount'];
				foreach ( $other_payments as $other_payment ) {
					if ( $other_payment['amount'] < 0 && $amount_left_to_refund > 0 ) {
						$amount_left_to_refund += $other_payment['amount'];
						if ( ! isset( $payment['refund_invoice_payments'] ) ) {
							$payment['refund_invoice_payments'] = array();
						}
						$payment['refunded']                  = true;
						$payment['refund_invoice_payments'][] = $other_payment;
					}
				}
			} else if ( $payment['amount'] < 0 && count( $other_payments ) > 0 ) {
				// this is a refund of another payment.
				$amount_left_to_refund = $payment['amount'];
				foreach ( $other_payments as $other_payment ) {
					if ( $other_payment['amount'] > 0 && $amount_left_to_refund < 0 ) {
						$amount_left_to_refund += $other_payment['amount'];
						if ( ! isset( $payment['refund_invoice_payments'] ) ) {
							$payment['refund_invoice_payments'] = array();
						}
						$payment['is_refund']                 = true;
						$payment['refund_invoice_payments'][] = $other_payment;
					}
				}

			}
		} else {
			$payment = array();
		}

		return $payment;
	}

	public static $new_invoice_number_date = false;//flag for creating invoice numbers off a different date to today.

	public static function new_invoice_number( $customer_id ) {

		set_error( 'Deprecated call to new_invoice_number()' );
		$invoice_number = '';

		if ( function_exists( 'custom_invoice_number' ) ) {
			$invoice_number = custom_invoice_number( $customer_id );
		}

		$invoice_prefix = '';
		if ( $customer_id > 0 ) {
			$customer_data = module_customer::get_customer( $customer_id );
			if ( $customer_data && isset( $customer_data['default_invoice_prefix'] ) ) {
				$invoice_prefix = $customer_data['default_invoice_prefix'];
			}
		}

		if ( ! $invoice_number ) {

			if ( module_config::c( 'invoice_name_match_job', 0 ) && isset( $_REQUEST['job_id'] ) && (int) $_REQUEST['job_id'] > 0 ) {
				$job = module_job::get_job( $_REQUEST['job_id'] );
				// todo: confirm tis isn't a data leak risk oh well.
				$invoice_number = $invoice_prefix . $job['name'];
			} else if ( module_config::c( 'invoice_incrementing', 0 ) ) {
				$invoice_number = module_config::c( 'invoice_incrementing_next', 1 );
				// see if there is an invoice number matching this one.
				$this_invoice_number = $invoice_number;
				do {
					$invoices = get_multiple( 'invoice', array( 'name' => $invoice_prefix . $this_invoice_number ) );
					//self::get_invoices(array('name'=>$invoice_prefix.$this_invoice_number)); //'customer_id'=>$customer_id,
					if ( ! $invoices ) {
						$invoice_number = $this_invoice_number;
						break;
					} else {
						// an invoice exists with this same number.
						// is it from last year?
						if ( module_config::c( 'invoice_increment_date_check', 'Y' ) == 'Y' ) {
							$has_year_match = false;
							foreach ( $invoices as $invoice ) {
								if ( date( 'Y' ) == date( 'Y', strtotime( $invoice['date_create'] ) ) ) {
									$has_year_match = true;
								}
							}
							if ( ! $has_year_match ) {
								// this invoice number is from last year, we can use it.
								$invoice_number = $this_invoice_number;
								break;
							}
						}
						$this_invoice_number ++;
					}
				} while ( count( $invoices ) );
				module_config::save_config( 'invoice_incrementing_next', $invoice_number );
				$invoice_number = $invoice_prefix . $invoice_number;
			} else {
				$invoice_number = $invoice_prefix . date( 'ymd', self::$new_invoice_number_date ? strtotime( self::$new_invoice_number_date ) : time() );

				//$invoice_number = $invoice_prefix . date('ymd');
				// check if this invoice number exists for this customer
				// if it does exist we create a suffix a, b, c, d etc..
				// this isn't atomic - if two invoices are created for the same customer at the same time then
				// this probably wont work. but for this system it's fine.
				$this_invoice_number = $invoice_number;
				$suffix_ascii        = 65; // 65 is A
				$suffix_ascii2       = 0; // 65 is A
				do {
					if ( $suffix_ascii == 91 ) {
						// we've exhausted all invoices for today.
						$suffix_ascii = 65; // reset to A
						if ( ! $suffix_ascii2 ) {
							// first loop, start with A
							$suffix_ascii2 = 65; // set 2nd suffix to A, work with this.
						} else {
							$suffix_ascii2 ++; // move from A to B
						}

					}
					$invoices = self::get_invoices( array( 'name' => $this_invoice_number ) ); //'customer_id'=>$customer_id,
					if ( ! count( $invoices ) ) {
						$invoice_number = $this_invoice_number;
					} else {
						$this_invoice_number = $invoice_number . ( $suffix_ascii2 ? chr( $suffix_ascii2 ) : '' ) . chr( $suffix_ascii );
					}
					$suffix_ascii ++;
				} while ( count( $invoices ) && $suffix_ascii <= 91 && $suffix_ascii2 <= 90 ); //90 is Z
			}
		}

		return $invoice_number;

	}

	public static function get_invoice_access_permissions() {
		if ( class_exists( 'module_security', false ) ) {
			return module_security::can_user_with_options( module_security::get_loggedin_id(), 'Invoice Data Access', array(
				_INVOICE_ACCESS_ALL,
				_INVOICE_ACCESS_JOB,
				_INVOICE_ACCESS_STAFF,
				_INVOICE_ACCESS_CUSTOMER,
			) );
		} else {
			return _INVOICE_ACCESS_ALL; // default to all permissions.
		}
	}

	private static function _invoice_cache_key( $invoice_id, $args = array() ) {
		return 'invoice_' . $invoice_id . '_' . md5( module_security::get_loggedin_id() . '_' . serialize( $args ) );
	}

	public static function get_invoice( $invoice_id, $basic = false, $skip_permissions = false ) {
		$invoice    = array();
		$invoice_id = (int) $invoice_id;
		if ( (int) $invoice_id > 0 ) {

			// we check the cache to see if the 'full' copy of this invoice exists anywhere yet.
			// if it does
			$cache_key = self::_invoice_cache_key( $invoice_id, array(
				$invoice_id,
				$basic,
				$skip_permissions,
				( isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : 0 ),
				( isset( $_REQUEST['job_id'] ) ? $_REQUEST['job_id'] : 0 )
			) );
			if ( $cached_item = module_cache::get( 'invoice', $cache_key ) ) {
				return $cached_item;
			}
			$cache_key_full = self::_invoice_cache_key( $invoice_id, array(
				$invoice_id,
				false,
				$skip_permissions,
				( isset( $_REQUEST['customer_id'] ) ? $_REQUEST['customer_id'] : 0 ),
				( isset( $_REQUEST['job_id'] ) ? $_REQUEST['job_id'] : 0 )
			) );
			if ( $cache_key_full != $cache_key && $cached_item = module_cache::get( 'invoice', $cache_key_full ) ) {
				return $cached_item;
			}
			$cache_timeout = module_config::c( 'cache_objects', 60 );


			if ( $basic === 2 ) { // used in links. just want the invoice name really.
				// todo - cache. meh
				return get_single( 'invoice', 'invoice_id', $invoice_id );
			} else {
				$sql = "SELECT i.*";
				$sql .= ", c.primary_user_id  "; // AS user_id // DONE - change this to the invoice table. drop down to select invoice contact. auto select based on contacts role?
				$sql .= ", c.customer_name AS customer_name ";
				$sql .= ", GROUP_CONCAT(DISTINCT j.`website_id` SEPARATOR ',') AS website_ids"; // the website id(s)
				$sql .= ", GROUP_CONCAT(DISTINCT j.`job_id` SEPARATOR ',') AS job_ids"; // the job id(s)
				$sql .= ", j.customer_id AS new_customer_id ";
				$sql .= " FROM `" . _DB_PREFIX . "invoice` i ";
				$sql .= " LEFT JOIN `" . _DB_PREFIX . "invoice_item` ii USING (invoice_id) ";
				$sql .= " LEFT JOIN `" . _DB_PREFIX . "task` t ON ii.task_id = t.task_id";
				$sql .= " LEFT JOIN `" . _DB_PREFIX . "job` j ON t.job_id = j.job_id";
				$sql .= " LEFT JOIN `" . _DB_PREFIX . "customer` c ON i.customer_id = c.customer_id ";
				//$sql .= " LEFT JOIN `"._DB_PREFIX."user` u ON c.primary_user_id = u.user_id ";
				$sql     .= " WHERE i.invoice_id = " . (int) $invoice_id;
				$sql     .= " GROUP BY i.invoice_id";
				$invoice = qa1( $sql );
				if ( isset( $invoice['website_id'] ) && $invoice['website_id'] ) {
					$website_ids = explode( ',', $invoice['website_ids'] );
					if ( ! in_array( $invoice['website_id'], $website_ids ) ) {
						$website_ids[]          = $invoice['website_id'];
						$invoice['website_ids'] = implode( ',', $website_ids );
					}
				}
			}

			if ( isset( $invoice['job_ids'] ) && strlen( trim( $invoice['job_ids'] ) ) > 0 ) {
				$invoice['job_ids'] = explode( ',', $invoice['job_ids'] );
			} else {
				$invoice['job_ids'] = array();
			}

			// check permissions
			if ( $invoice && isset( $invoice['invoice_id'] ) && $invoice['invoice_id'] == $invoice_id ) {
				switch ( self::get_invoice_access_permissions() ) {
					case _INVOICE_ACCESS_ALL:

						break;
					case _INVOICE_ACCESS_STAFF:
						if ( $invoice['vendor_user_id'] != module_security::get_loggedin_id() ) {
							if ( $skip_permissions ) {
								$invoice['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
							} else {
								$invoice = false;
							}
						}
						break;
					case _INVOICE_ACCESS_JOB:
						// only invoices from jobs!
						$has_invoice_access = false;
						$jobs               = module_job::get_jobs();
						foreach ( $invoice['job_ids'] as $invoice_job_id ) {
							if ( isset( $jobs[ $invoice_job_id ] ) ) {
								$has_invoice_access = true;
							}
						}
						unset( $jobs );
						if ( ! $has_invoice_access ) {
							if ( $skip_permissions ) {
								$invoice['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
							} else {
								$invoice = false;
							}
						}
						break;
					case _INVOICE_ACCESS_CUSTOMER:
						// tie in with customer permissions to only get invoices from customers we can access.
						$customers          = module_customer::get_customers();
						$has_invoice_access = false;
						if ( isset( $customers[ $invoice['customer_id'] ] ) ) {
							$has_invoice_access = true;
						}
						unset( $customers );
						/*foreach($customers as $customer){
                            // todo, if($invoice['customer_id'] == 0) // ignore this permission
                            if($customer['customer_id']==$invoice['customer_id']){
                                $has_invoice_access = true;
                                break;
                            }
                        }*/
						if ( ! $has_invoice_access ) {
							if ( $skip_permissions ) {
								$invoice['_no_access'] = true; // set a flag for custom processing. we check for this when calling get_customer with the skip permissions argument. (eg: in the ticket file listing link)
							} else {
								$invoice = false;
							}
						}
						break;
				}

				//            print_r($invoice);exit;
				if ( ! $invoice ) {
					return array();
				}

				$original_invoice = $invoice;

				$invoice['taxes'] = get_multiple( 'invoice_tax', array( 'invoice_id' => $invoice_id ), 'invoice_tax_id', 'exact', 'order' );

				// set the job id of the first job just for kicks
				if ( isset( $invoice['deposit_job_id'] ) && (int) $invoice['deposit_job_id'] > 0 ) {
					$invoice['job_ids'][] = $invoice['deposit_job_id'];
				}
				if ( isset( $invoice['website_ids'] ) ) {
					$invoice['website_ids'] = explode( ',', $invoice['website_ids'] );
				} else {
					$invoice['website_ids'] = array();
				}
				// incase teh customer id on this invoice changes:
				if ( isset( $invoice['new_customer_id'] ) && $invoice['new_customer_id'] > 0 && $invoice['new_customer_id'] != $invoice['customer_id'] ) {
					$invoice['customer_id'] = $invoice['new_customer_id'];
					update_insert( 'invoice_id', $invoice_id, 'invoice', array( 'customer_id' => $invoice['new_customer_id'] ) );
				}

				if ( $invoice['customer_id'] > 0 ) {
					$customer_data = module_customer::get_customer( $invoice['customer_id'] );
					if ( $customer_data && class_exists( 'module_company', false ) && isset( $invoice['company_id'] ) && ! $invoice['company_id'] && isset( $customer_data['company_ids'] ) && count( $customer_data['company_ids'] ) == 1 ) {
						// check if this customer has a company.
						$invoice['company_id'] = key( $customer_data['company_ids'] );
					}
				}

				if ( $basic === true ) {
					module_cache::put( 'invoice', $cache_key, $invoice, $cache_timeout );

					return $invoice;
				}
			}
		}
		// not sure why this code was here, commenting it out for now until we need it.
		/*if(isset($invoice['customer_id']) && isset($invoice['job_id']) && $invoice['customer_id'] <= 0 && $invoice['job_id'] > 0){
            $job_data = module_job::get_job($invoice['job_id'],false);
            $invoice['customer_id'] = $job_data['customer_id'];
        }*/
		if ( ! $invoice || ! is_array( $invoice ) || ! isset( $invoice['invoice_id'] ) || ! $invoice['invoice_id'] ) {
			$customer_id = ( isset( $_REQUEST['customer_id'] ) ? (int) $_REQUEST['customer_id'] : 0 );
			$job_id      = ( isset( $_REQUEST['job_id'] ) ? $_REQUEST['job_id'] : 0 );
			$currency_id = module_config::c( 'default_currency_id', 1 );
			if ( $customer_id > 0 ) {
				// find a default website to use ?
			} else if ( $job_id > 0 ) {
				// only a job, no customer. set the customer id.
				$job_data    = module_job::get_job( $job_id, false );
				$customer_id = $job_data['customer_id'];
				$currency_id = $job_data['currency_id'];
			}
			// work out an invoice number

			// new class based system.
			$ucminvoice              = new UCMInvoice();
			$ucminvoice->customer_id = $customer_id;
			$invoice_number          = $ucminvoice->get_new_document_number();

			$invoice                   = array(
				'invoice_id'                => 'new',
				'customer_id'               => $customer_id,
				'job_id'                    => $job_id,
				// this is  needed as a once off for creating new invoices.
				'job_ids'                   => $job_id > 0 ? array( $job_id ) : array(),
				'currency_id'               => $currency_id,
				'name'                      => $invoice_number,
				'cached_total'              => 0,
				'discount_description'      => $job_id > 0 && isset( $job_data['discount_description'] ) ? $job_data['discount_description'] : _l( 'Discount:' ),
				'discount_amount'           => $job_id > 0 && isset( $job_data['discount_amount'] ) ? $job_data['discount_amount'] : 0,
				'discount_type'             => $job_id > 0 && isset( $job_data['discount_type'] ) ? $job_data['discount_type'] : module_config::c( 'invoice_discount_type', _DISCOUNT_TYPE_BEFORE_TAX ),
				// 1 = After Tax
				'tax_type'                  => module_config::c( 'invoice_tax_type', 0 ),
				// 0 = added, 1 = included
				'date_create'               => date( 'Y-m-d' ),
				'date_sent'                 => '',
				'date_due'                  => date( 'Y-m-d', strtotime( '+' . module_config::c( 'invoice_due_days', 30 ) . ' days' ) ),
				'date_paid'                 => '',
				'hourly_rate'               => module_customer::c( 'hourly_rate', 60, $customer_id ),
				// hit up the customer module to get this setting, this way the customer can override this setting.
				'status'                    => module_config::s( 'invoice_status_default', 'New' ),
				'contact_user_id'           => '',
				'user_id'                   => '',
				'date_renew'                => '',
				'renew_invoice_id'          => '',
				'deposit_job_id'            => 0,
				'date_cancel'               => '0000-00-00',
				'total_amount_deposits'     => 0,
				'total_amount_deposits_tax' => 0,
				'default_task_type'         => module_customer::c( 'default_task_type', _TASK_TYPE_HOURS_AMOUNT, $customer_id ),
				//
				'overdue_email_auto'        => module_customer::c( 'overdue_email_auto', 0, $customer_id ),
				'renew_auto'                => 0,
				'renew_email'               => 0,
				'overdue'                   => false,
				'invoice_template_print'    => module_customer::c( 'invoice_template_print_default', 'invoice_print', $customer_id ),
				'website_id'                => isset( $_REQUEST['website_id'] ) ? (int) $_REQUEST['website_id'] : 0,
				'website_ids'               => '',
			);
			$invoice['total_tax_rate'] = module_config::c( 'tax_percent', 10 );
			$invoice['total_tax_name'] = module_config::c( 'tax_name', 'TAX' );
			$customer_data             = false;
			if ( $customer_id > 0 ) {
				$customer_data = module_customer::get_customer( $customer_id );
			}
			if ( $customer_data && $customer_data['customer_id'] && $customer_data['customer_id'] == $customer_id ) {
				// is there a default invoice template for this customer?
				if ( class_exists( 'module_extra', false ) ) {
					$extras = module_extra::get_extras( array( 'owner_table' => 'customer', 'owner_id' => $customer_id ) );
					foreach ( $extras as $e ) {
						if ( $e['extra_key'] == 'invoice_template_print' ) {
							$invoice['invoice_template_print'] = $e['extra'];
						}
					}
				}
				if ( $customer_data['primary_user_id'] ) {
					$invoice['primary_user_id'] = $customer_data['primary_user_id'];
				}
				if ( isset( $customer_data['default_tax'] ) && $customer_data['default_tax'] >= 0 ) {
					$invoice['total_tax_rate'] = $customer_data['default_tax'];
					$invoice['total_tax_name'] = $customer_data['default_tax_name'];
				}
			}
		}

		// drag some details from the related job
		$first_job_id = 0;
		if ( ! (int) $invoice_id ) {
			if ( isset( $invoice['job_ids'] ) && $invoice['job_ids'] ) {
				$first_job_id = current( $invoice['job_ids'] );
			} else if ( isset( $invoice['job_id'] ) && $invoice['job_id'] ) {
				$first_job_id = $invoice['job_id']; // abckwards compatibility
			} else {
				$first_job_id = 0;
			}
			if ( $first_job_id > 0 ) {
				$job_data               = module_job::get_job( $first_job_id, false );
				$invoice['hourly_rate'] = $job_data['hourly_rate'];
				$invoice['taxes']       = $job_data['taxes'];
				//$invoice['total_tax_rate'] = $job_data['total_tax_rate'];
				//$invoice['total_tax_name'] = $job_data['total_tax_name'];
			}
		}

		// new support for multiple taxes
		if ( ! isset( $invoice['taxes'] ) || ( ! count( $invoice['taxes'] ) && $invoice['total_tax_rate'] > 0 ) ) {
			$invoice['taxes'] = array();
			if ( $first_job_id > 0 && ! (int) $invoice_id ) {
				// taxes set above from job
			} else {
				$tax_rates = explode( ',', $invoice['total_tax_rate'] );
				$tax_names = explode( ',', $invoice['total_tax_name'] );
				foreach ( $tax_rates as $tax_rate_id => $tax_rate_amount ) {
					if ( $tax_rate_amount > 0 ) {
						$invoice['taxes'][] = array(
							'order'     => 0,
							'percent'   => $tax_rate_amount,
							'name'      => isset( $tax_names[ $tax_rate_id ] ) ? $tax_names[ $tax_rate_id ] : $invoice['total_tax_name'],
							'total'     => 0,
							// original value that tax was calculated againt
							'amount'    => 0,
							// final amount of calculated tax
							'discount'  => 0,
							// if any discounts are applied to taxes, add them here. this is used in a complicated hack back in job.php to work out new job prices.
							'increment' => module_config::c( 'tax_multiple_increment', 0 ),
							//todo: db this option
						);
					}
				}
			}
		}

		// work out total hours etc..
		//$invoice['total_hours'] = 0;
		//$invoice['total_hours_completed'] = 0;
		//$invoice['total_hours_overworked'] = 0;
		$invoice['discount_amount_on_tax']   = 0; // used in job.php
		$invoice['total_sub_amount']         = 0;
		$invoice['total_sub_amount_taxable'] = 0;
		$invoice_items                       = self::get_invoice_items( (int) $invoice['invoice_id'], $invoice );
		foreach ( $invoice_items as $invoice_item ) {
			if ( $invoice_item['invoice_item_amount'] != 0 ) {
				// we have a custom amount for this invoice_item
				$invoice['total_sub_amount'] += $invoice_item['invoice_item_amount'];
				if ( $invoice_item['taxable'] ) {
					$invoice['total_sub_amount_taxable'] += $invoice_item['invoice_item_amount'];
					if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
						// tax calculated along the way (this isn't the recommended way, but was included as a feature request)
						// we add tax to each of the tax array items
						//$invoice['total_tax'] += round(($invoice_item['invoice_item_amount'] * ($invoice['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
						foreach ( $invoice['taxes'] as $invoice_tax_id => $invoice_tax ) {
							if ( ! isset( $invoice['taxes'][ $invoice_tax_id ]['total'] ) ) {
								$invoice['taxes'][ $invoice_tax_id ]['total'] = 0;
							}
							$invoice['taxes'][ $invoice_tax_id ]['total']  += $invoice_item['invoice_item_amount'];
							$invoice['taxes'][ $invoice_tax_id ]['amount'] += round( ( $invoice_item['invoice_item_amount'] * ( $invoice_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
						}
					}
				}
			}
		}

		//$invoice['final_modification'] = 0; // hack for discount modes - change this to just 'discount_amount' cos that is all that uses this variable. HERE

		// add any discounts.
		if ( $invoice['discount_amount'] != 0 ) {
			if ( $invoice['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
				// after tax discount ::::::::::
				// handled below.
				//$invoice['final_modification'] = -$invoice['discount_amount'];
			} else if ( $invoice['discount_type'] == _DISCOUNT_TYPE_BEFORE_TAX ) {
				// before tax discount:::::
				//$invoice['final_modification'] = -$invoice['discount_amount'];
				// problem : this 'discount_amount_on_tax' calculation may not match the correct final discount calculation as per below
				if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL ) {
					// tax calculated along the way.
					// we have discounted the 'total amount taxable' so that means we need to reduce the tax amount by that much as well.
					foreach ( $invoice['taxes'] as $invoice_tax_id => $invoice_tax ) {
						$this_tax_discount                 = round( ( $invoice['discount_amount'] * ( $invoice['taxes'][ $invoice_tax_id ]['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
						$invoice['discount_amount_on_tax'] += $this_tax_discount;
						if ( ! isset( $invoice['taxes'][ $invoice_tax_id ]['total'] ) ) {
							$invoice['taxes'][ $invoice_tax_id ]['total'] = 0;
						}
						$invoice['taxes'][ $invoice_tax_id ]['total']    -= $invoice['discount_amount'];
						$invoice['taxes'][ $invoice_tax_id ]['amount']   -= $this_tax_discount;
						$invoice['taxes'][ $invoice_tax_id ]['discount'] = $this_tax_discount;
					}
				} else {

					// we work out what the tax would have been if there was no applied discount
					// this is used in job.php
					$invoice['taxes_backup']                    = $invoice['taxes'];
					$invoice['total_sub_amount_taxable_backup'] = $invoice['total_sub_amount_taxable'];
					$total_tax_before_discount                  = 0;
					foreach ( $invoice['taxes'] as $invoice_tax_id => $invoice_tax ) {
						$invoice['taxes'][ $invoice_tax_id ]['total']  = $invoice['total_sub_amount_taxable'];
						$invoice['taxes'][ $invoice_tax_id ]['amount'] = round( ( $invoice['total_sub_amount_taxable'] * ( $invoice_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
						// here we adjust the 'total_sub_amount_taxable' to include the value from the previous calculation.
						// this is for multiple taxes that addup as they go (eg: Canada)
						if ( isset( $invoice_tax['increment'] ) && $invoice_tax['increment'] ) {
							$invoice['total_sub_amount_taxable'] += $invoice['taxes'][ $invoice_tax_id ]['amount'];
						}
						$total_tax_before_discount += $invoice['taxes'][ $invoice_tax_id ]['amount'];
					}
					$invoice['taxes']                    = $invoice['taxes_backup'];
					$invoice['total_sub_amount_taxable'] = $invoice['total_sub_amount_taxable_backup'];
				}
				// remove the discount amount from the 'sub total' and the 'taxable total' but don't go negative on it.
				// remove the discount from any non-taxable portion first.
				$non_taxable_amount   = $invoice['total_sub_amount'] - $invoice['total_sub_amount_taxable'];
				$non_taxable_discount = min( $invoice['discount_amount'], $non_taxable_amount );
				$taxable_discount     = $invoice['discount_amount'] - $non_taxable_discount;

				//echo "non tax $non_taxable_amount \n nontax discount: $non_taxable_discount \n tax discount: $taxable_discount \n";print_r($invoice);exit;
				$invoice['total_sub_amount']         -= $invoice['discount_amount'];
				$invoice['total_sub_amount_taxable'] -= $taxable_discount;

				//                $invoice['total_sub_amount']-=$invoice['discount_amount'];
				//                $invoice['total_sub_amount_taxable']-=$invoice['discount_amount'];
			}
		}

		//$invoice['total_hours_remain'] = $invoice['total_hours'] - $invoice['total_hours_completed'];
		//$invoice['total_percent_complete'] = $invoice['total_hours'] > 0 ? round($invoice['total_hours_remain'] / $invoice['total_hours'],2) : 0;
		//if(isset($invoice['total_tax_rate'])){
		if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_INCREMENTAL && isset( $invoice['total_tax'] ) && $invoice['total_tax'] > 0 ) {
			// tax already calculated above.

		} else if ( module_config::c( 'tax_calculate_mode', _TAX_CALCULATE_AT_END ) == _TAX_CALCULATE_AT_END ) {
			// tax needs to be calculated based on the total_sub_amount_taxable
			$previous_invoice_tax_id = false;
			foreach ( $invoice['taxes'] as $invoice_tax_id => $invoice_tax ) {
				$invoice['taxes'][ $invoice_tax_id ]['total'] = $invoice['total_sub_amount_taxable'];
				if ( isset( $invoice_tax['increment'] ) && $invoice_tax['increment'] && $previous_invoice_tax_id ) {
					$invoice['taxes'][ $invoice_tax_id ]['total'] += $invoice['taxes'][ $previous_invoice_tax_id ]['amount'];
				}
				$invoice['taxes'][ $invoice_tax_id ]['amount'] = round( ( $invoice['taxes'][ $invoice_tax_id ]['total'] * ( $invoice_tax['percent'] / 100 ) ), module_config::c( 'currency_decimal_places', 2 ) );
				// here we adjust the 'total_sub_amount_taxable' to include the value from the previous calculation.
				// this is for multiple taxes that addup as they go (eg: Canada)
				$previous_invoice_tax_id = $invoice_tax_id;
			}
			//$invoice['total_tax'] = round(($invoice['total_sub_amount_taxable'] * ($invoice['total_tax_rate'] / 100)),module_config::c('currency_decimal_places',2));
		} else {
			//$invoice['total_tax'] = 0;
		}
		if ( isset( $invoice['tax_type'] ) && $invoice['tax_type'] == 1 ) {
			// hack! not completely correct, oh well.
			// todo - make this work with more than 1 tax rate.
			// $amount / 1.05  ( this is 1 + tax %)
			// this will only work if a single tax has been included.
			if ( is_array( $invoice['taxes'] ) && count( $invoice['taxes'] ) > 1 ) {
				set_error( 'Included tax calculation only works with 1 tax rate' );
			} else if ( is_array( $invoice['taxes'] ) && count( $invoice['taxes'] ) ) {
				reset( $invoice['taxes'] );
				$invoice_tax_id = key( $invoice['taxes'] );
				if ( isset( $invoice['taxes'][ $invoice_tax_id ] ) ) {
					$taxable_amount                                = $invoice['total_sub_amount_taxable'] / ( 1 + ( $invoice['taxes'][ $invoice_tax_id ]['percent'] / 100 ) );
					$invoice['taxes'][ $invoice_tax_id ]['amount'] = $invoice['total_sub_amount_taxable'] - $taxable_amount;
					$invoice['total_sub_amount']                   = $invoice['total_sub_amount'] - $invoice['taxes'][ $invoice_tax_id ]['amount'];
				}

			}
		}
		$invoice['total_tax'] = 0;
		foreach ( $invoice['taxes'] as $invoice_tax_id => $invoice_tax ) {
			$invoice['total_tax'] += $invoice_tax['amount'];
		}
		if ( isset( $total_tax_before_discount ) ) {
			$invoice['discount_amount_on_tax'] += ( $total_tax_before_discount - $invoice['total_tax'] );
		}
		$invoice['total_amount'] = $invoice['total_sub_amount'] + $invoice['total_tax'];
		if ( $invoice['discount_type'] == _DISCOUNT_TYPE_AFTER_TAX ) {
			$invoice['total_amount'] -= $invoice['discount_amount'];
		}
		$invoice['total_amount'] = round( $invoice['total_amount'], module_config::c( 'currency_decimal_places', 2 ) );

		$invoice['overdue'] = ( $invoice['date_due'] && $invoice['date_due'] != '0000-00-00' ) && ( ! $invoice['date_paid'] || $invoice['date_paid'] == '0000-00-00' ) && strtotime( $invoice['date_due'] ) < strtotime( date( 'Y-m-d' ) );

		if ( $basic === 1 ) {
			// so we don't go clearning cache and working out how much has been paid.
			// used in the finance module while displaying dashboard summary.
			return $invoice;
		}

		// find the user id if none exists.
		/*if($invoice['customer_id'] && !$invoice['user_id']){
            $customer_data = module_customer::get_customer($invoice['customer_id']);
            if($customer_data && $customer_data['customer_id'] == $invoice['customer_id']){
                if($customer_data['primary_user_id']){
                    $invoice['user_id'] = $customer_data['primary_user_id'];
                }else{
                    $customer_contacts = module_user::get_contacts(array('customer_id'=>$invoice['customer_id']));
                    foreach($customer_contacts as $contact){
                        // todo - search roles or something to find the accountant.
                        $invoice['user_id'] = $contact['user_id'];
                        break;
                    }
                }
            }
        }*/

		$paid = 0;

		/* START DEPOSITS */
		$invoice['total_amount_deposits']     = 0; // calculate deposits separately.
		$invoice['total_amount_deposits_tax'] = 0; // calculate deposits separately.
		//module_cache::clear_cache(); // no longer clearnig cache, it does it in get_invoice_payments.
		//module_cache::clear('invoice');
		foreach ( self::get_invoice_payments( $invoice_id ) as $payment ) {
			if ( $payment['date_paid'] && $payment['date_paid'] != '0000-00-00' ) {
				if ( $payment['payment_type'] == _INVOICE_PAYMENT_TYPE_DEPOSIT ) {
					// what invoice did this payment come from?
					$deposit_invoice = module_invoice::get_invoice( $payment['other_id'] );
					if ( $deposit_invoice && $deposit_invoice['invoice_id'] == $payment['other_id'] ) {
						$invoice['total_amount_deposits']     += min( $deposit_invoice['total_amount'] - $deposit_invoice['total_tax'], $payment['amount'] - $deposit_invoice['total_tax'] );
						$invoice['total_amount_deposits_tax'] += $deposit_invoice['total_tax'];
					}
				} else {
					$paid += $payment['amount'];
				}
			}
		}
		if ( $invoice['total_amount_deposits'] > 0 ) {
			// we need to reduce the 'total_amount' of this invoice so it doesn't double up with the other paid deposit invoice
			$invoice['total_amount'] -= $invoice['total_amount_deposits'];
		}
		if ( $invoice['total_amount_deposits_tax'] > 0 ) {
			//$invoice['total_tax'] -= $invoice['total_amount_deposits_tax'];
			// we need to reduce the 'total_amount' of this invoice so it doesn't double up with the other paid deposit invoice
			$invoice['total_amount'] -= $invoice['total_amount_deposits_tax'];
		}
		/* END DEPOSITS */

		// any extra fees (eG: paypap fee?)
		$invoice['fees'] = self::get_fees( $invoice_id, $invoice );
		foreach ( $invoice['fees'] as $fee ) {
			$invoice['total_amount'] += $fee['total'];
		}

		// dont go negative on payments:
		$invoice['total_amount_paid']   = max( 0, min( $invoice['total_amount'], $paid ) );
		$invoice['total_amount_credit'] = 0;
		if ( $invoice['total_amount'] > 0 && $paid > $invoice['total_amount'] ) {
			// raise a credit against this customer for the difference.
			$invoice['total_amount_credit'] = round( $paid - $invoice['total_amount'], 2 );
			//echo $invoice['total_amount_overpaid'];exit;
		}
		if ( $invoice['total_amount'] != $invoice['cached_total'] ) {
			if ( (int) $invoice_id > 0 ) {
				update_insert( 'invoice_id', $invoice_id, 'invoice', array( 'cached_total' => $invoice['total_amount'] ) );
			}
			$invoice['cached_total'] = $invoice['total_amount'];
		}
		$invoice['total_amount_due'] = round( $invoice['total_amount'] - $invoice['total_amount_paid'], module_config::c( 'currency_decimal_places', 2 ) );

		if ( $invoice['date_cancel'] != '0000-00-00' ) {
			$invoice['total_amount_due'] = 0;
		}
		// a special addition for deposit invoices.
		if ( isset( $invoice['deposit_job_id'] ) && $invoice['deposit_job_id'] ) {
			// we find out how much deposit has actually been paid
			// and how much is remaining that hasn't been allocated to any other invoices
			$invoice['deposit_remaining'] = 0;
			if ( $invoice['total_amount_paid'] > 0 ) {
				$invoice['deposit_remaining'] = $invoice['total_amount_paid'];
				$payments                     = get_multiple( 'invoice_payment', array(
					'payment_type' => _INVOICE_PAYMENT_TYPE_DEPOSIT,
					'other_id'     => $invoice['invoice_id'],
				) );
				foreach ( $payments as $payment ) {
					$invoice['deposit_remaining'] = $invoice['deposit_remaining'] - $payment['amount'];
				}
			}
		}
		// save our database cache values:
		if ( (int) $invoice_id > 0 ) {
			foreach (
				array(
					'total_amount', // in datbase as c_total_amount
					'total_amount_due', // in datbase as c_total_amount_due
				) as $cacheable_item
			) {
				if ( isset( $invoice[ $cacheable_item ] ) && ( ! isset( $original_invoice ) || ! isset( $original_invoice[ 'c_' . $cacheable_item ] ) || $original_invoice[ 'c_' . $cacheable_item ] != $invoice[ $cacheable_item ] ) ) {
					// cacheable items can be the same name or prefixed with c_
					update_insert( 'invoice_id', $invoice_id, 'invoice', array(
						"c_$cacheable_item" => $invoice[ $cacheable_item ],
					) );
					$invoice["c_$cacheable_item"] = $invoice[ $cacheable_item ];
				}
			}
		}
		if ( isset( $cache_key ) ) {
			module_cache::put( 'invoice', $cache_key, $invoice, $cache_timeout );
		}

		return $invoice;
	}

	public static function save_invoice( $invoice_id, $data ) {
		if ( ! (int) $invoice_id && isset( $data['job_id'] ) && $data['job_id'] ) {
			$linkedjob           = module_job::get_job( $data['job_id'] );
			$data['currency_id'] = $linkedjob['currency_id'];
			$data['customer_id'] = $linkedjob['customer_id'];
		}
		if ( $invoice_id ) {
			// used when working out the hourly rate fix below
			$original_invoice_data = self::get_invoice( $invoice_id );
		} else {
			$original_invoice_data = 0;
		}
		$invoice_id = update_insert( "invoice_id", $invoice_id, "invoice", $data );
		if ( $invoice_id ) {
			module_cache::clear( 'invoice' );

			// save the invoice tax rates (copied to finance.php)
			if ( isset( $data['tax_ids'] ) && isset( $data['tax_names'] ) && $data['tax_percents'] ) {
				$existing_taxes = get_multiple( 'invoice_tax', array( 'invoice_id' => $invoice_id ), 'invoice_tax_id', 'exact', 'order' );
				$order          = 1;
				foreach ( $data['tax_ids'] as $key => $val ) {
					// if(isset($data['tax_percents'][$key]) && $data['tax_percents'][$key] == 0){
					// we are not saving this particular tax item because it has a 0% tax rate
					//  }else{
					if ( (int) $val > 0 && isset( $existing_taxes[ $val ] ) ) {
						// this means we are trying to update an existing record on the invoice_tax table, we confirm this id matches this invoice.
						$invoice_tax_id = $val;
						unset( $existing_taxes[ $invoice_tax_id ] ); // so we know which ones to remove from the end.
					} else {
						$invoice_tax_id = false; // create new record
					}
					$invoice_tax_data = array(
						'invoice_id' => $invoice_id,
						'percent'    => isset( $data['tax_percents'][ $key ] ) ? $data['tax_percents'][ $key ] : 0,
						'amount'     => 0, // calculate this where? nfi? maybe on final invoice get or something.
						'name'       => isset( $data['tax_names'][ $key ] ) ? $data['tax_names'][ $key ] : 'TAX',
						'order'      => $order ++,
						'increment'  => isset( $data['tax_increment_checkbox'] ) && $data['tax_increment_checkbox'] ? 1 : 0,
					);
					$invoice_tax_id   = update_insert( 'invoice_tax_id', $invoice_tax_id, 'invoice_tax', $invoice_tax_data );
					//  }
				}
				foreach ( $existing_taxes as $existing_tax ) {
					delete_from_db( 'invoice_tax', array( 'invoice_id', 'invoice_tax_id' ), array(
						$invoice_id,
						$existing_tax['invoice_tax_id']
					) );
				}
			}

			$invoice_data = self::get_invoice( $invoice_id );
			if ( ! $invoice_data ) {
				set_error( 'No permissions to access invoice.' );

				return $invoice_id;
			}
			// check for new invoice_items or changed invoice_items.
			$invoice_items = self::get_invoice_items( $invoice_id, $invoice_data );
			if ( isset( $data['invoice_invoice_item'] ) && is_array( $data['invoice_invoice_item'] ) ) {
				foreach ( $data['invoice_invoice_item'] as $invoice_item_id => $invoice_item_data ) {
					$invoice_item_id = (int) $invoice_item_id;
					if ( ! is_array( $invoice_item_data ) ) {
						continue;
					}
					if ( $invoice_item_id > 0 && ! isset( $invoice_items[ $invoice_item_id ] ) ) {
						continue;
					} // wrong invoice_item save - will never happen.
					if ( ! isset( $invoice_item_data['description'] ) || $invoice_item_data['description'] == '' ) {
						if ( $invoice_item_id > 0 ) {
							// remove invoice_item.
							$sql = "DELETE FROM `" . _DB_PREFIX . "invoice_item` WHERE invoice_item_id = '$invoice_item_id' AND invoice_id = $invoice_id LIMIT 1";
							query( $sql );
						}
						continue;
					}
					// add / save this invoice_item.
					$invoice_item_data['invoice_id'] = $invoice_id;
					// what type of task is this?
					$invoice_task_type               = isset( $invoice_item_data['manual_task_type'] ) && $invoice_item_data['manual_task_type'] >= 0 ? $invoice_item_data['manual_task_type'] : $invoice_data['default_task_type'];
					$invoice_item_data['hours_mins'] = 0;
					if ( isset( $invoice_item_data['hours'] ) && $invoice_task_type == _TASK_TYPE_HOURS_AMOUNT ) {

					}
					if ( isset( $invoice_item_data['hours'] ) && $invoice_task_type == _TASK_TYPE_HOURS_AMOUNT && function_exists( 'decimal_time_in' ) ) {
						$invoice_item_data['hours'] = decimal_time_in( $invoice_item_data['hours'] );
						if ( strpos( $invoice_item_data['hours'], ':' ) !== false ) {
							$invoice_item_data['hours_mins'] = str_replace( ":", ".", $invoice_item_data['hours'] );
						}
					} else if ( isset( $invoice_item_data['hours'] ) && strlen( $invoice_item_data['hours'] ) ) {
						$invoice_item_data['hours'] = number_in( $invoice_item_data['hours'] );
					} else {
						$invoice_item_data['hours'] = 0;
					}

					// number formatting
					//print_r($invoice_item_data);
					if ( isset( $invoice_item_data['hourly_rate'] ) && strlen( $invoice_item_data['hourly_rate'] ) ) {
						$invoice_item_data['hourly_rate'] = number_in( $invoice_item_data['hourly_rate'], module_config::c( 'task_amount_decimal_places', - 1 ) );
					}
					//print_r($invoice_item_data);exit;

					// somenew hacks here to support out new method of creating an item.
					// the 'amount' column is never edited any more
					// this column is now always automatically calculated based on
					// 'hours' and 'hourly_rate'
					if ( ! isset( $invoice_item_data['amount'] ) ) {

						if ( $invoice_task_type == _TASK_TYPE_AMOUNT_ONLY ) {

							// ignore the quantity field all together.
							$invoice_item_data['amount']      = $invoice_item_data['hourly_rate'];
							$invoice_item_data['hourly_rate'] = 0;

						} else {

							if ( isset( $invoice_item_data['hourly_rate'] ) && strlen( $invoice_item_data['hourly_rate'] ) > 0 ) {
								// if we have inputted an hourly rate (ie: not left empty)

								if ( isset( $invoice_item_data['hours'] ) && strlen( $invoice_item_data['hours'] ) == 0 ) {
									// no hours entered (eg: empty) so we treat whatever was in 'hourly_rate' as the amount
									$invoice_item_data['amount'] = $invoice_item_data['hourly_rate'];
								} else if ( isset( $invoice_item_data['hours'] ) && strlen( $invoice_item_data['hours'] ) > 0 ) {
									// hours inputted, along with hourly rate. work out the new amount.

									$invoice_item_data['amount'] = round( $invoice_item_data['hours'] * $invoice_item_data['hourly_rate'], module_config::c( 'currency_decimal_places', 2 ) );
								}
							}
						}

					}
					if ( $invoice_task_type == _TASK_TYPE_HOURS_AMOUNT ) {
						if ( $invoice_item_data['hourly_rate'] == $invoice_data['hourly_rate'] || ( isset( $original_invoice_data['hourly_rate'] ) && $invoice_item_data['hourly_rate'] == $original_invoice_data['hourly_rate'] ) ) {
							$invoice_item_data['hourly_rate'] = - 1;
						}
					}
					// remove the amount of it equals the hourly rate.
					/*if(isset($invoice_item_data['amount']) && isset($invoice_item_data['hours']) && $invoice_item_data['amount'] > 0 && $invoice_item_data['hours'] > 0){
                        if($invoice_item_data['amount'] - ($invoice_item_data['hours'] * $data['hourly_rate']) == 0){
                            unset($invoice_item_data['amount']);
                        }
                    }*/
					// check if we haven't unticked a non-hourly invoice_item
					/*if(isset($invoice_item_data['completed_t']) && $invoice_item_data['completed_t'] && !isset($invoice_item_data['completed'])){
                        $invoice_item_data['completed'] = 0;
                    }*/
					if ( ! isset( $invoice_item_data['taxable_t'] ) ) {
						$invoice_item_data['taxable'] = module_config::c( 'task_taxable_default', 1 );
					} else if ( isset( $invoice_item_data['taxable_t'] ) && $invoice_item_data['taxable_t'] && ! isset( $invoice_item_data['taxable'] ) ) {
						$invoice_item_data['taxable'] = 0;
					}
					if ( ! strlen( $invoice_item_data['hours'] ) ) {
						$invoice_item_data['hours'] = 0;
					}
					$invoice_item_data['hourly_rate'] = number_in( $invoice_item_data['hourly_rate'], module_config::c( 'task_amount_decimal_places', - 1 ) );
					$invoice_item_data['hours']       = number_in( $invoice_item_data['hours'] );
					$invoice_item_data['amount']      = number_in( $invoice_item_data['amount'] );
					update_insert( 'invoice_item_id', $invoice_item_id, 'invoice_item', $invoice_item_data );
				}
			}


			$last_payment_time = 0;
			if ( isset( $data['invoice_invoice_payment'] ) && is_array( $data['invoice_invoice_payment'] ) ) {
				foreach ( $data['invoice_invoice_payment'] as $invoice_payment_id => $invoice_payment_data ) {
					$invoice_payment_id = (int) $invoice_payment_id;
					if ( ! is_array( $invoice_payment_data ) ) {
						continue;
					}
					if ( isset( $invoice_payment_data['amount'] ) ) {
						$invoice_payment_data['amount'] = number_in( $invoice_payment_data['amount'] );
						// toggle between 'normal' and 'refund' payment types
						if ( isset( $invoice_payment_data['payment_type'] ) ) {
							if ( $invoice_payment_data['amount'] < 0 && $invoice_payment_data['payment_type'] == _INVOICE_PAYMENT_TYPE_NORMAL ) {
								// this is a refund.
								$invoice_payment_data['payment_type'] = _INVOICE_PAYMENT_TYPE_REFUND;
							} else if ( $invoice_payment_data['payment_type'] == _INVOICE_PAYMENT_TYPE_REFUND ) {
								$invoice_payment_data['payment_type'] = _INVOICE_PAYMENT_TYPE_NORMAL;
							}
						}
					}
					// check this invoice payment actually matches this invoice.
					$invoice_payment_data_existing = false;
					if ( $invoice_payment_id > 0 ) {
						$invoice_payment_data_existing = get_single( 'invoice_payment', array(
							'invoice_payment_id',
							'invoice_id'
						), array( $invoice_payment_id, $invoice_id ) );
						if ( ! $invoice_payment_data_existing || $invoice_payment_data_existing['invoice_payment_id'] != $invoice_payment_id || $invoice_payment_data_existing['invoice_id'] != $invoice_id ) {
							$invoice_payment_id            = 0;
							$invoice_payment_data_existing = false;
						} else {
							$invoice_payment_data['data'] = @unserialize( $invoice_payment_data_existing['data'] );
							if ( ! is_array( $invoice_payment_data['data'] ) ) {
								$invoice_payment_data['data'] = array();
							}
						}
					}
					if ( ! isset( $invoice_payment_data['amount'] ) || $invoice_payment_data['amount'] == '' || $invoice_payment_data['amount'] == 0 ) { // || $invoice_payment_data['amount'] <= 0
						if ( $invoice_payment_id > 0 ) {
							// if this is a customer credit payment, return that back to the customer account.
							if ( $invoice_payment_data_existing && $invoice_data['customer_id'] ) {
								switch ( $invoice_payment_data_existing['payment_type'] ) {
									case _INVOICE_PAYMENT_TYPE_CREDIT:
										module_customer::add_credit( $invoice_data['customer_id'], $invoice_payment_data_existing['amount'], 'Refunded credit from invoice payment' );
										break;
								}
							}
							// remove invoice_payment.
							$sql = "DELETE FROM `" . _DB_PREFIX . "invoice_payment` WHERE invoice_payment_id = '$invoice_payment_id' AND invoice_id = $invoice_id LIMIT 1";
							query( $sql );
							// delete any existing transactions from the system as well.
							hook_handle_callback( 'invoice_payment_deleted', $invoice_payment_id, $invoice_id );

						}
						continue;
					}
					if ( ! $invoice_payment_id && ( ! isset( $_REQUEST['add_payment'] ) || $_REQUEST['add_payment'] != 'go' ) ) {
						continue; // not saving a new one.
					}
					// add / save this invoice_payment.
					$invoice_payment_data['invoice_id'] = $invoice_id;
					// $invoice_payment_data['currency_id'] = $invoice_data['currency_id'];
					$last_payment_time = max( $last_payment_time, strtotime( input_date( $invoice_payment_data['date_paid'] ) ) );
					if ( isset( $invoice_payment_data['custom_notes'] ) ) {
						$details = ! empty( $invoice_payment_data['data'] ) ? $invoice_payment_data['data'] : array();
						if ( ! is_array( $details ) ) {
							$details = array();
						}
						$details['custom_notes']      = $invoice_payment_data['custom_notes'];
						$invoice_payment_data['data'] = $details;
					}

					$invoice_payment_data['amount'] = number_in( $invoice_payment_data['amount'] );
					update_insert( 'invoice_payment_id', $invoice_payment_id, 'invoice_payment', $invoice_payment_data );
				}
			}
			if ( ! $last_payment_time ) {
				$last_payment_time = strtotime( date( 'Y-m-d' ) );
			}
			// check if the invoice has been paid


			module_cache::clear( 'invoice' );
			//module_cache::clear_cache(); // this helps fix the bug where part payments are not caulcated a correct paid date.
			$invoice_data = self::get_invoice( $invoice_id );
			if ( ! $invoice_data ) {
				set_error( 'No permissions to access invoice.' );

				return $invoice_id;
			}
			if (
				empty( $invoice_data['credit_note_id'] ) &&
				( ( ! $invoice_data['date_paid'] || $invoice_data['date_paid'] == '0000-00-00' ) ) &&
				$invoice_data['total_amount_due'] <= 0 &&
				( $invoice_data['total_amount_paid'] > 0 || $invoice_data['discount_amount'] > 0 ) &&
				( ! $invoice_data['date_cancel'] || $invoice_data['date_cancel'] == '0000-00-00' )
			) {
				// find the date of the last payment history.

				// if the sent date is null also update that.
				$date_sent = $invoice_data['date_sent'];
				if ( ! $date_sent || $date_sent == '0000-00-00' ) {
					$date_sent = date( 'Y-m-d', $last_payment_time );
				}
				update_insert( "invoice_id", $invoice_id, "invoice", array(
					'date_paid' => date( 'Y-m-d', $last_payment_time ),
					'date_sent' => $date_sent,
					'status'    => _l( 'Paid' ),
				) );
				// hook for our ticketing plugin to mark a priority support ticket as paid.
				// or anything else down the track.
				module_cache::clear( 'invoice' );
				handle_hook( 'invoice_paid', $invoice_id );
				if ( module_customer::c( 'invoice_automatic_receipt', 1, $invoice_data['customer_id'] ) ) {
					// send receipt to customer.
					self::email_invoice_to_customer( $invoice_id );
				}
			}
			if ( $invoice_data['total_amount_due'] > 0 && empty( $invoice_data['credit_note_id'] ) ) {
				// update the status to unpaid.
				update_insert( "invoice_id", $invoice_id, "invoice", array(
					'date_paid' => '',
					'status'    => $invoice_data['status'] == _l( 'Paid' ) ? module_config::s( 'invoice_status_default', 'New' ) : $invoice_data['status'],
				) );
			}
			if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				module_extra::save_extras( 'invoice', 'invoice_id', $invoice_id );
			}
			if ( $invoice_data['customer_id'] ) {
				//module_cache::clear_cache();
				module_cache::clear( 'invoice' );
				module_customer::update_customer_status( $invoice_data['customer_id'] );
			}
			hook_handle_callback( 'invoice_saved', $invoice_id, $invoice_data, $original_invoice_data );
		}
		module_cache::clear( 'invoice' );
		module_cache::clear( 'job' );

		return $invoice_id;
	}

	public static function delete_invoice( $invoice_id ) {
		$invoice_id = (int) $invoice_id;
		if ( (int) $invoice_id > 0 && self::can_i( 'delete', 'Invoices' ) ) {
			hook_handle_callback( 'invoice_deleted', $invoice_id );
			$invoice_data = self::get_invoice( $invoice_id );
			$sql          = "DELETE FROM " . _DB_PREFIX . "invoice WHERE invoice_id = '" . $invoice_id . "' LIMIT 1";
			$res          = query( $sql );
			$sql          = "DELETE FROM " . _DB_PREFIX . "invoice_item WHERE invoice_id = '" . $invoice_id . "'";
			$res          = query( $sql );
			$sql          = "DELETE FROM " . _DB_PREFIX . "invoice_tax WHERE invoice_id = '" . $invoice_id . "'";
			$res          = query( $sql );
			$sql          = "DELETE FROM " . _DB_PREFIX . "invoice_payment WHERE invoice_id = '" . $invoice_id . "'";
			$res          = query( $sql );
			$sql          = "UPDATE " . _DB_PREFIX . "invoice SET renew_invoice_id = 0 WHERE renew_invoice_id = '" . $invoice_id . "'";
			$res          = query( $sql );
			if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
				module_note::note_delete( "invoice", $invoice_id );
			}
			if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
				module_extra::delete_extras( 'invoice', 'invoice_id', $invoice_id );
			}
			module_cache::clear( 'invoice' );
			module_cache::clear( 'job' );
			if ( $invoice_data && $invoice_data['customer_id'] ) {
				module_customer::update_customer_status( $invoice_data['customer_id'] );
			}
		}
	}

	public function login_link( $invoice_id ) {
		return module_security::generate_auto_login_link( $invoice_id );
	}

	public static function get_statuses() {
		$sql      = "SELECT `status` FROM `" . _DB_PREFIX . "invoice` GROUP BY `status` ORDER BY `status`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['status'] ] = $r['status'];
		}

		return $statuses;
	}

	public static function get_invoice_payment_methods( $invoice_id ) {
		return get_multiple( 'invoice_payment_method', array( 'invoice_id' => $invoice_id ), 'payment_method' );
	}

	public static function get_payment_methods() {
		$sql      = "SELECT `method` FROM `" . _DB_PREFIX . "invoice_payment` GROUP BY `method` ORDER BY `method`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['method'] ] = $r['method'];
		}
		// add in our other methods too.
		$payment_methods = handle_hook( 'get_payment_methods' );
		foreach ( $payment_methods as $payment_method ) {
			if ( $payment_method->is_enabled() ) {
				$statuses[ $payment_method->get_payment_method_name() ] = $payment_method->get_payment_method_name();
			}
		}
		ksort( $statuses );

		return $statuses;
	}

	public static function get_types() {
		$sql      = "SELECT `type` FROM `" . _DB_PREFIX . "invoice` GROUP BY `type` ORDER BY `type`";
		$statuses = array();
		foreach ( qa( $sql ) as $r ) {
			$statuses[ $r['type'] ] = $r['type'];
		}

		return $statuses;
	}

	public function handle_payment() {
		// handle a payment request via post data from
		$invoice_id = (int) $_REQUEST['invoice_id'];

		if ( self::is_automatic_paying_invoice( $invoice_id ) ) {

		}
		// resume a failed past payment.
		if ( isset( $_REQUEST['invoice_payment_id'] ) && (int) $_REQUEST['invoice_payment_id'] > 0 ) {
			$invoice_payment_data = module_invoice::get_invoice_payment( $_REQUEST['invoice_payment_id'] );
			if ( $invoice_payment_data['invoice_id'] == $invoice_id && $invoice_payment_data['date_paid'] == '0000-00-00' && $invoice_payment_data['invoice_payment_id'] == $_REQUEST['invoice_payment_id'] ) {
				// we can resume this incomplete payment.
				// hack to find out which payment method plugin we are using, this is bad!
				$payment_methods = handle_hook( 'get_payment_methods', $this );
				foreach ( $payment_methods as &$payment_method ) {
					if ( $payment_method->is_enabled() && $payment_method->is_method( 'online' ) && $payment_method->get_payment_method_name() == $invoice_payment_data['method'] ) {
						$payment_method_name = $payment_method->module_name;
						global $plugins;
						if ( isset( $plugins[ $payment_method_name ] ) ) {
							$plugins[ '' . $payment_method_name ]->start_payment( $invoice_id, $invoice_payment_data['amount'] - $invoice_payment_data['fee_total'], $invoice_payment_data['invoice_payment_id'] );
						}
					}
				}
			}
		} else if ( isset( $_REQUEST['payment_method'] ) && $invoice_id && isset( $_REQUEST['payment_amount'] ) ) {
			$payment_method = $_REQUEST['payment_method'];
			$payment_amount = number_in( $_REQUEST['payment_amount'] );
			$invoice_data   = $this->get_invoice( $invoice_id );

			//&& module_security::can_access_data('invoice',$invoice_data,$invoice_id)
			if ( $invoice_id && $payment_method && $payment_amount > 0 && $invoice_data ) {
				// pass this off to the payment module for handling.
				global $plugins;
				if ( isset( $plugins[ $payment_method ] ) ) {


					if ( class_exists( 'module_company', false ) && isset( $invoice_data['company_id'] ) && (int) $invoice_data['company_id'] > 0 ) {
						module_company::set_current_company_id( $invoice_data['company_id'] );
					}

					// delete any previously pending payment methods
					//$sql = "DELETE FROM `"._DB_PREFIX."invoice_payment` WHERE invoice_id = $invoice_id AND method = '".db_escape($plugins[''.$payment_method]->get_payment_method_name())."' AND currency_id = '".$invoice_data['currency_id']."' ";
					// insert a temp payment method here.
					$invoice_payment_id = update_insert( 'invoice_payment_id', 'new', 'invoice_payment', array(
						'invoice_id'  => $invoice_id,
						'amount'      => $payment_amount,
						'currency_id' => $invoice_data['currency_id'],
						'method'      => $plugins[ '' . $payment_method ]->get_payment_method_name(),
					) );
					module_cache::clear( 'invoice' );


					$plugins[ '' . $payment_method ]->start_payment( $invoice_id, $payment_amount, $invoice_payment_id );

				}
			}
		}
		// todo - better redirect with errors.
		//redirect_browser($_SERVER['REQUEST_URI']);
	}


	/**
	 * Generate a PDF for the currently load()'d quote
	 * Return the path to the file name for this quote.
	 * @return bool
	 */

	public static function generate_pdf( $invoice_id ) {

		if ( ! function_exists( 'convert_html2pdf' ) ) {
			return false;
		}

		$invoice_id   = (int) $invoice_id;
		$invoice_data = self::get_invoice( $invoice_id );
		$invoice_html = self::invoice_html( $invoice_id, $invoice_data, 'pdf' );
		if ( $invoice_html ) {
			//echo $invoice_html;exit;

			$base_name = basename( preg_replace( '#[^a-zA-Z0-9_]#', '', module_config::c( 'invoice_file_prefix', 'Invoice_' ) ) );
			if ( isset( $invoice_data['credit_note_id'] ) && $invoice_data['credit_note_id'] ) {
				$base_name = basename( preg_replace( '#[^a-zA-Z0-9_]#', '', module_config::c( 'credit_note_file_prefix', 'Credit_Note_' ) ) );
			}
			$file_name      = preg_replace( '#[^a-zA-Z0-9]#', '', $invoice_data['name'] );
			$html_file_name = _UCM_FILE_STORAGE_DIR . 'temp/' . $base_name . $file_name . '.html';
			$pdf_file_name  = _UCM_FILE_STORAGE_DIR . 'temp/' . $base_name . $file_name . '.pdf';

			file_put_contents( $html_file_name, $invoice_html );

			return convert_html2pdf( $html_file_name, $pdf_file_name );


		}

		return false;
	}

	public static function invoice_html( $invoice_id, $invoice_data, $mode = 'html' ) {

		if ( $invoice_id && $invoice_data ) {
			// spit out the invoice html into a file, then pass it to the pdf converter
			// to convert it into a PDF.


			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_print.php' ) );
			module_template::init_template( 'invoice_print', ob_get_clean(), 'Used for printing out an invoice for the customer.', 'html' );
			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_print_basic.php' ) );
			module_template::init_template( 'invoice_print_basic', ob_get_clean(), 'Alternative template for printing out an invoice for the customer.', 'html' );
			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/credit_note_pdf.php' ) );
			module_template::init_template( 'credit_note_pdf', ob_get_clean(), 'Used for printing out a a credit note for the customer.', 'html' );


			$invoice = $invoice_data;

			if ( class_exists( 'module_company', false ) && isset( $invoice_data['company_id'] ) && (int) $invoice_data['company_id'] > 0 ) {
				module_company::set_current_company_id( $invoice_data['company_id'] );
			}

			$job_data     = module_job::get_job( current( $invoice_data['job_ids'] ) );
			$website_data = $job_data['website_id'] ? module_website::get_website( $job_data['website_id'] ) : array();
			$website_data = array_merge( $website_data, isset( $invoice_data['website_id'] ) && $invoice_data['website_id'] ? module_website::get_website( $invoice_data['website_id'] ) : array() );

			$invoice_template = isset( $invoice_data['invoice_template_print'] ) && strlen( $invoice_data['invoice_template_print'] ) ? $invoice_data['invoice_template_print'] : module_customer::c( 'invoice_template_print_default', 'invoice_print', $invoice_data['customer_id'] );

			$invoice_template_suffix = '';
			if ( $invoice_template != 'invoice_print' ) {
				$invoice_template_suffix = str_replace( 'invoice_print', '', $invoice_template );
			}

			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_task_list.php' ) );
			$task_list_html = ob_get_clean();
			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_payment_history.php' ) );
			$payment_history = ob_get_clean();
			ob_start();
			include( module_theme::include_ucm( 'includes/plugin_invoice/template/invoice_payment_methods.php' ) );
			$payment_methods = ob_get_clean();


			$replace                    = self::get_replace_fields( $invoice_id, $invoice_data );
			$replace['payment_history'] = $payment_history;
			$replace['payment_methods'] = $payment_methods;
			$replace['task_list']       = $task_list_html;

			$replace['external_invoice_template_html'] = '';
			//$external_invoice_template = module_template::get_template_by_key('invoice_external');
			$external_invoice_template = false;
			if ( isset( $invoice_template_suffix ) && strlen( $invoice_template_suffix ) > 0 ) {
				$external_invoice_template = module_template::get_template_by_key( 'invoice_external' . $invoice_template_suffix );
				if ( ! $external_invoice_template->template_id ) {
					$external_invoice_template = false;
				}
			}
			if ( ! $external_invoice_template ) {
				$external_invoice_template = module_template::get_template_by_key( 'invoice_external' );
			}


			$external_invoice_template->assign_values( $replace );
			$replace['external_invoice_template_html'] = $external_invoice_template->replace_content();

			if ( isset( $invoice_data['credit_note_id'] ) && $invoice_data['credit_note_id'] ) {
				if ( $invoice_data['invoice_template_print'] ) {
					$invoice_data['invoice_template_print'] = 'credit_note_pdf';
				}
				$invoice_template = 'credit_note_pdf';
			}


			ob_start();
			$template = module_template::get_template_by_key( $invoice_template );
			if ( ! $template || $template->template_key != $invoice_template ) {
				echo "Invoice template $invoice_template not found";
			} else {
				$template->assign_values( $replace );
				echo $template->render( 'html' );
			}
			$invoice_html = ob_get_clean();

			return $invoice_html;
		}

		return false;
	}

	public static function add_history( $invoice_id, $message ) {
		if ( class_exists( 'module_note', false ) && module_note::is_plugin_enabled() ) {
			module_note::save_note( array(
				'owner_table' => 'invoice',
				'owner_id'    => $invoice_id,
				'note'        => $message,
				'rel_data'    => self::link_open( $invoice_id ),
				'note_time'   => time(),
			) );
		}
	}

	public static function customer_id_changed( $old_customer_id, $new_customer_id ) {
		$old_customer_id = (int) $old_customer_id;
		$new_customer_id = (int) $new_customer_id;
		if ( $old_customer_id > 0 && $new_customer_id > 0 ) {
			$sql = "UPDATE `" . _DB_PREFIX . "invoice` SET customer_id = " . $new_customer_id . " WHERE customer_id = " . $old_customer_id;
			query( $sql );
		}
	}

	public static function check_invoice_merge( $invoice_id ) {
		$invoice_data = self::get_invoice( $invoice_id );
		$sql          = "SELECT invoice_id FROM `" . _DB_PREFIX . "invoice` i WHERE";
		$sql          .= " invoice_id != " . (int) $invoice_id;
		//$sql .= " AND total_tax_rate = '".db_escape($invoice_data['total_tax_rate'])."'";
		$sql .= " AND customer_id = " . (int) $invoice_data['customer_id'];
		$sql .= " AND deposit_job_id = 0";
		$sql .= " AND (date_sent IS NULL OR date_sent = '0000-00-00') ";

		return qa( $sql );
	}

	public static function email_sent( $args ) { //$invoice_id,$template_name=''){
		$invoice_id    = $args['invoice_id'];
		$template_name = $args['template_name'];
		$template_type = ! empty( $args['template_type'] ) ? $args['template_type'] : 'due';
		// add sent date if it doesn't exist
		$invoice = self::get_invoice( $invoice_id, true );
		//if(!$invoice['date_sent'] || $invoice['date_sent'] == '0000-00-00'){
		update_insert( 'invoice_id', $invoice_id, 'invoice', array(
			'date_sent' => date( 'Y-m-d' ),
		) );
		module_cache::clear( 'invoice' );

		// we fire into the new json based hook call.
		// this will do things like
		//}
		/*switch($template_name){
            case 'invoice_email_overdue':
                self::add_history($invoice_id,_l('Overdue Invoice Emailed'));
                break;
            case 'invoice_email_paid':
                self::add_history($invoice_id,_l('Receipt Emailed'));
                break;
            case 'invoice_email_due':
            default:
                self::add_history($invoice_id,_l('Invoice Emailed'));


        }*/
	}

	public static function get_finance_recurring_items( $hook, $search ) {
		/**
		 * next_due_date
		 * url
		 * type (i or e)
		 * amount
		 * currency_id
		 * days
		 * months
		 * years
		 * last_transaction_finance_id
		 * account_name
		 * categories
		 * finance_recurring_id
		 */
		// find any unpaid invoices.
		$invoices = self::get_invoices( array( 'date_paid' => '0000-00-00' ) );
		$return   = array();
		foreach ( $invoices as $invoice ) {
			// filter out invoices that haven't been sent yet? probably should...
			//$invoice = self::get_invoice($invoice['invoice_id']);
			if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
				continue;
			}
			// check if this invoice is part of a subscription, put in some additional info for this subscriptions
			// 'recurring_text'
			if ( $invoice['member_id'] ) {
				$member_name = module_member::link_open( $invoice['member_id'], true );
			} else if ( $invoice['customer_id'] ) {
				$member_name = module_customer::link_open( $invoice['customer_id'], true );
			} else {
				$member_name = _l( 'N/A' );
			}
			$recurring_text = _l( 'Payment from %s', $member_name );
			if ( class_exists( 'module_subscription', false ) && isset( $invoice['invoice_subscription_ids'] ) ) {
				$sql = "SELECT sh.*, s.name FROM `" . _DB_PREFIX . "subscription_history` sh LEFT JOIN `" . _DB_PREFIX . "subscription` s USING (subscription_id) WHERE sh.invoice_id = " . (int) $invoice['invoice_id'] . "";
				$res = qa1( $sql );
				if ( $res ) {
					$subscription_name = module_subscription::link_open( $res['subscription_id'], true, $res );
					$recurring_text    = _l( 'Payment from %s on subscription %s', $member_name, $subscription_name );
				}
			}
			if ( ! isset( $invoice['c_total_amount_due'] ) ) {
				$invoice                       = module_invoice::get_invoice( $invoice['invoice_id'] );
				$invoice['c_total_amount_due'] = $invoice['total_amount_due'];
			}

			$return[ $invoice['invoice_id'] ] = array(
				'next_due_date'               => ( $invoice['date_due'] && $invoice['date_due'] != '0000-00-00' ) ? $invoice['date_due'] : $invoice['date_created'],
				'url'                         => module_invoice::link_open( $invoice['invoice_id'], true, $invoice ),
				'type'                        => 'i',
				'amount'                      => $invoice['c_total_amount_due'],
				'currency_id'                 => $invoice['currency_id'],
				'days'                        => 0,
				'months'                      => 0,
				'years'                       => 0,
				'last_transaction_finance_id' => 0,
				'account_name'                => '',
				'categories'                  => '',
				'finance_recurring_id'        => 0,
				'recurring_text'              => $recurring_text,
			);
		}
		// find any automatically renewing invoices.
		$invoices = self::get_invoices( array( 'renewing' => 1 ) );
		foreach ( $invoices as $invoice ) {
			// filter out invoices that haven't been sent yet? probably should...
			//$invoice = self::get_invoice($invoice['invoice_id']);
			if ( isset( $invoice['date_cancel'] ) && $invoice['date_cancel'] != '0000-00-00' ) {
				continue;
			}
			// check if this invoice is part of a subscription, put in some additional info for this subscriptions
			// 'recurring_text'
			if ( $invoice['member_id'] ) {
				$member_name = module_member::link_open( $invoice['member_id'], true );
			} else if ( $invoice['customer_id'] ) {
				$member_name = module_customer::link_open( $invoice['customer_id'], true );
			} else {
				$member_name = _l( 'N/A' );
			}
			if ( $invoice['renew_auto'] ) {
				$recurring_text = _l( 'Automatically Renewing invoice for %s', $member_name );
			} else {
				$recurring_text = _l( 'Manually Renewing invoice for %s', $member_name );

			}
			if ( ! isset( $invoice['c_total_amount'] ) ) {
				$invoice                   = module_invoice::get_invoice( $invoice['invoice_id'] );
				$invoice['c_total_amount'] = $invoice['total_amount'];
			}

			$return[] = array(
				'next_due_date'               => date( 'Y-m-d', strtotime( '+' . module_customer::c( 'invoice_due_days', 30, $invoice['customer_id'] ) . ' days', strtotime( $invoice['date_renew'] ) ) ),
				'url'                         => module_invoice::link_open( $invoice['invoice_id'], true, $invoice ),
				'type'                        => 'i',
				'amount'                      => $invoice['c_total_amount'],
				'currency_id'                 => $invoice['currency_id'],
				'days'                        => 0,
				'months'                      => 0,
				'years'                       => 0,
				'last_transaction_finance_id' => 0,
				'account_name'                => '',
				'categories'                  => '',
				'finance_recurring_id'        => 0,
				'recurring_text'              => $recurring_text,
			);
		}

		return $return;
	}

	public static function email_invoice_to_customer( $invoice_id, $debug = false ) {

		// this is a copy of some of the code in invoie_admin_email.php
		// used in the CRON job when sending out automated emails.

		$invoice = module_invoice::get_invoice( $invoice_id );
		// template for sending emails.
		// are we sending the paid one? or the dueone.
		$template_name   = '';
		$template_type   = 'due'; // this is used in our new json based hook feature
		$template_prefix = isset( $invoice['invoice_template_email'] ) && strlen( $invoice['invoice_template_email'] ) ? $invoice['invoice_template_email'] : 'invoice_email';
		if ( isset( $invoice['credit_note_id'] ) && $invoice['credit_note_id'] ) {
			$template_name = 'credit_note_email';
			$template_type = 'credit_note'; // this is used in our new json based hook feature
		} else if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) {
			$template_name = $template_prefix . '_paid';
			$template_type = 'paid'; // this is used in our new json based hook feature
		} else if ( $invoice['overdue'] && $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
			$template_name = $template_prefix . '_overdue';
			$template_type = 'overdue'; // this is used in our new json based hook feature
		} else {
			$template_name = $template_prefix . '_due';
		}
		$template_name = hook_filter_var( 'invoice_email_template', $template_name, $invoice_id, $invoice );
		if ( class_exists( 'module_company', false ) && isset( $invoice_data['company_id'] ) && (int) $invoice_data['company_id'] > 0 ) {
			module_company::set_current_company_id( $invoice_data['company_id'] );
		}
		$template = module_template::get_template_by_key( $template_name );
		if ( ! $template || $template->template_key != $template_name ) {
			// backup default templates incase someone has chosen a template that doesn't exist (eg: created invoice_email_MINE_due but not invoice_email_MINE_paid )
			$template_prefix = 'invoice_email';
			if ( $invoice['date_paid'] && $invoice['date_paid'] != '0000-00-00' ) {
				$template_name = $template_prefix . '_paid';
			} else if ( $invoice['overdue'] && $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
				$template_name = $template_prefix . '_overdue';
			} else {
				$template_name = $template_prefix . '_due';
			}
		}

		$replace = module_invoice::get_replace_fields( $invoice_id, $invoice );

		if ( defined( '_BLOCK_EMAILS' ) && _BLOCK_EMAILS ) {
			$pdf = false;
		} else {
			$pdf = module_invoice::generate_pdf( $invoice_id );
		}

		$send_email_to = array();
		$to            = array();
		if ( $invoice['customer_id'] ) {
			$customer                 = module_customer::get_customer( $invoice['customer_id'] );
			$replace['customer_name'] = $customer['customer_name'];
			if ( $invoice['contact_user_id'] > 0 ) {
				// this invoice has a manually assigned user, only send the invoice to this user.
				// todo: should we also send to accounts? not sure - see if peopel complain
				$primary = module_user::get_user( $invoice['contact_user_id'] );
				if ( $primary ) {
					$send_email_to[] = $primary;
				}
			} else {
				$to = module_user::get_contacts( array( 'customer_id' => $invoice['customer_id'] ) );
				// hunt for 'accounts' extra field
				$field_to_find = strtolower( module_config::c( 'accounts_extra_field_name', 'Accounts' ) );
				foreach ( $to as $contact ) {
					$extras = module_extra::get_extras( array(
						'owner_table' => 'user',
						'owner_id'    => $contact['user_id']
					) );
					foreach ( $extras as $e ) {
						if ( strtolower( $e['extra_key'] ) == $field_to_find ) {
							// this is the accounts contact - woo!
							$send_email_to[] = $contact;
						}
					}
				}
				if ( ! count( $send_email_to ) && $customer['primary_user_id'] ) {
					$primary = module_user::get_user( $customer['primary_user_id'] );
					if ( $primary ) {
						$send_email_to[] = $primary;
					}
				}
			}
		} else if ( $invoice['member_id'] ) {
			$member                   = module_member::get_member( $invoice['member_id'] );
			$to                       = array( $member );
			$replace['customer_name'] = $member['first_name'];
		} else {
			$to = array();
		}


		$template->assign_values( $replace );
		$html = $template->render( 'html' );
		// send an email to this user.
		$email                 = module_email::new_email();
		$email->replace_values = $replace;
		// todo: send to all customer contacts ?
		if ( $send_email_to ) {
			foreach ( $send_email_to as $send_email_t ) {
				if ( ! empty( $send_email_t['user_id'] ) ) {
					$email->set_to( 'user', $send_email_t['user_id'] );
				} else if ( ! empty( $send_email_t['email'] ) ) {
					$email->set_to_manual( $send_email_t['email'] );
				}
			}
		} else {
			foreach ( $to as $t ) {
				if ( ! empty( $t['user_id'] ) ) {
					$email->set_to( 'user', $t['user_id'] );
				} else if ( ! empty( $t['email'] ) ) {
					$email->set_to_manual( $t['email'] );
				}
				break;// only 1? todo: all?
			}
		}
		$email->set_bcc_manual( module_config::c( 'admin_email_address', '' ), '' );
		//$email->set_from('user',); // nfi
		$email->set_subject( $template->description );
		// do we send images inline?
		$email->set_html( $html );
		if ( $pdf ) {
			$email->add_attachment( $pdf );
		}
		$email->invoice_id  = $invoice_id;
		$email->customer_id = $invoice['customer_id'];

		$email->prevent_duplicates = true;

		if ( $email->send( $debug ) ) {
			// it worked successfully!!
			// record a log on the invoice when it's done.
			self::email_sent( array(
				'invoice_id'    => $invoice_id,
				'template_name' => $template_name,
				'template_type' => $template_type,
			) );

			return true;
		} else {
			/// log err?
			return false;
		}
	}

	public function renew_invoice( $invoice_id ) {
		$invoice = $this->get_invoice( $invoice_id );
		if ( strtotime( $invoice['date_renew'] ) <= strtotime( '+' . module_config::c( 'alert_days_in_future', 5 ) . ' days' ) ) {
			// /we are allowed to renew.
			unset( $invoice['invoice_id'] );
			// work out the difference in start date and end date and add that new renewl date to the new order.
			$time_diff = strtotime( $invoice['date_renew'] ) - strtotime( $invoice['date_create'] );
			if ( $time_diff > 0 ) {
				// our renewal date is something in the future.
				if ( ! $invoice['date_create'] || $invoice['date_create'] == '0000-00-00' ) {
					set_message( 'Please set a invoice create date before renewing' );
					redirect_browser( $this->link_open( $invoice_id ) );
				}
				// if the time_diff is 28, 29, 30 or 31 days then we stick to the same day a month in the future.
				if ( module_config::c( 'invoice_renew_monthly_fix', 1 ) && $time_diff >= 2419100 && $time_diff <= 2678500 ) {
					$new_renewal_date = date( 'Y-m-d', strtotime( "+1 month", strtotime( $invoice['date_renew'] ) ) );
				} else {
					// work out the next renewal date.
					$new_renewal_date = date( 'Y-m-d', strtotime( $invoice['date_renew'] ) + $time_diff );
				}

				$ucminvoice                = new UCMInvoice( $invoice_id );
				$invoice['name']           = $ucminvoice->get_new_document_number();
				$invoice['date_create']    = $invoice['date_renew'];
				$invoice['date_renew']     = $new_renewal_date;
				$invoice['date_sent']      = false;
				$invoice['date_paid']      = false;
				$invoice['deposit_job_id'] = 0;
				if ( module_config::c( 'invoice_renew_discounts', 0 ) ) {
					// keep the discounts from previous invoices.
				} else {
					// clear the discounts back to defaults.
					$invoice['discount_amount']      = 0;
					$invoice['discount_description'] = _l( 'Discount:' );
					$invoice['discount_type']        = ! isset( $invoice['discount_type'] ) ? module_config::c( 'invoice_discount_type', _DISCOUNT_TYPE_BEFORE_TAX ) : $invoice['discount_type']; // 1 = After Tax
				}
				$invoice['tax_type'] = ! isset( $invoice['tax_type'] ) ? module_config::c( 'invoice_tax_type', 0 ) : $invoice['tax_type'];
				$invoice['date_due'] = date( 'Y-m-d', strtotime( '+' . module_config::c( 'invoice_due_days', 30 ) . ' days', strtotime( $invoice['date_create'] ) ) );
				$invoice['status']   = module_config::s( 'invoice_status_default', 'New' );
				// todo: copy the "more" listings over to the new invoice
				// todo: copy any notes across to the new listing.

				// hack to copy the 'extra' fields across to the new invoice.
				// save_invoice() does the extra handling, and if we don't do this
				// then it will move the extra fields from the original invoice to this new invoice.
				if ( class_exists( 'module_extra', false ) && module_extra::is_plugin_enabled() ) {
					$owner_table = 'invoice';
					// get extra fields from this invoice
					$extra_fields = module_extra::get_extras( array( 'owner_table' => $owner_table, 'owner_id' => $invoice_id ) );
					$x            = 1;
					foreach ( $extra_fields as $extra_field ) {
						$_REQUEST[ 'extra_' . $owner_table . '_field' ][ 'new' . $x ] = array(
							'key' => $extra_field['extra_key'],
							'val' => $extra_field['extra'],
						);
						$x ++;
					}
				}


				// taxes copy across
				if ( isset( $invoice['taxes'] ) && is_array( $invoice['taxes'] ) ) {
					$invoice['tax_ids']      = array();
					$invoice['tax_names']    = array();
					$invoice['tax_percents'] = array();
					foreach ( $invoice['taxes'] as $tax ) {
						$invoice['tax_ids'][]      = 0;
						$invoice['tax_names'][]    = $tax['name'];
						$invoice['tax_percents'][] = $tax['percent'];
						if ( $tax['increment'] ) {
							$invoice['tax_increment_checkbox'] = 1;
						}
					}
				}

				$new_invoice_id = $this->save_invoice( 'new', $invoice );
				if ( $new_invoice_id ) {
					// now we create the tasks
					$tasks = $this->get_invoice_items( $invoice_id );
					foreach ( $tasks as $task ) {
						unset( $task['invoice_item_id'] );
						if ( $task['custom_description'] ) {
							$task['description'] = $task['custom_description'];
						}
						if ( $task['custom_long_description'] ) {
							$task['long_description'] = $task['custom_long_description'];
						}
						$task['invoice_id'] = $new_invoice_id;
						$task['date_done']  = $invoice['date_create'];
						update_insert( 'invoice_item_id', 'new', 'invoice_item', $task );
					}
					// link this up with the old one.
					update_insert( 'invoice_id', $invoice_id, 'invoice', array( 'renew_invoice_id' => $new_invoice_id ) );
				}

				module_cache::clear( 'invoice' );

				return $new_invoice_id;
			}
		}

		return false;
	}

	public function run_cron( $debug = false ) {

		// we only want to perform these cron actions if we're after a certain time of day
		// because we dont want to be generating these renewals and sending them at midnight, can get confusing
		$after_time  = module_config::c( 'invoice_automatic_after_time', 7 );
		$time_of_day = date( 'G' );
		if ( $time_of_day < $after_time ) {
			if ( $debug ) {
				echo "Not performing automatic invoice operations until after $after_time:00 - it is currently $time_of_day:" . date( 'i' ) . "<br>\n";
			}

			return;
		}

		// find automaitic invoice overdues
		$sql           = "SELECT * FROM `" . _DB_PREFIX . "invoice`  ";
		$sql           .= " WHERE date_due != '0000-00-00' AND date_due <= '" . date( 'Y-m-d' ) . "' AND date_paid = '0000-00-00' AND date_cancel = '0000-00-00'";
		$invoice_items = qa( $sql );
		if ( $debug ) {
			echo "Processing " . count( $invoice_items ) . " overdue invoices:  <br>\n";
		}
		foreach ( $invoice_items as $invoice_item ) {
			module_cache::clear( 'invoice' );
			$invoice = module_invoice::get_invoice( $invoice_item['invoice_id'] );
			if ( $invoice['overdue'] && $invoice['overdue_email_auto'] ) {
				if ( $debug ) {
					echo "Processing overdue for invoice: " . module_invoice::link_open( $invoice['invoice_id'], true ) . " <br>\n";
				}
				if ( $debug ) {
					echo " - last sent: " . $invoice['date_sent'] . " <br>\n";
				}
				if ( $debug ) {
					echo " - due date: " . $invoice['date_due'] . " <br>\n";
				}
				if ( $debug ) {
					echo " - now: " . date( 'Y-m-d' ) . " ( " . time() . " ) <br>\n";
				}
				// if you change this calculation make sure it is changed in the dashboard alerts above to
				$send_email_on = false;
				if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' && strtotime( $invoice['date_sent'] ) > strtotime( $invoice['date_due'] ) ) {
					// we have sent a reminder already (todo: this isn't correct logic, fix it up so it can tell for sure if we have sent a reminder already or not (eg: look through email history table)
					$last_invoice_sent = strtotime( $invoice['date_sent'] );
					if ( module_config::c( 'overdue_email_auto_days_repeat', 7 ) <= 0 ) {
						continue; // skip sendin repeat reminders.
					}
					$send_email_on = strtotime( '+' . module_config::c( 'overdue_email_auto_days_repeat', 7 ) . ' days', $last_invoice_sent );
				} else if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' ) {
					$invoice_is_due = strtotime( $invoice['date_due'] );
					$send_email_on  = strtotime( '+' . module_config::c( 'overdue_email_auto_days', 3 ) . ' days', $invoice_is_due );
					if ( $debug ) {
						echo module_config::c( 'overdue_email_auto_days', 3 ) . " days from " . $invoice['date_due'] . " is " . date( 'Y-m-d', $send_email_on ) . "<br>\n";
					}
				} else {
					// this invoice has not been sent yet, so we don't send an automated overdue notice.
					// the user has to pick a "sent datE" before the system will send overdue notices.
					if ( $debug ) {
						echo " - NOT Sending Overdue Invoice Notice for " . module_invoice::link_open( $invoice['invoice_id'], true ) . " because it has no SENT DATE.<br>\n";
					}
					$send_email_on = false;
				}
				if ( $invoice['date_sent'] && $invoice['date_sent'] != '0000-00-00' && date( 'Y-m-d', $send_email_on ) == $invoice['date_sent'] ) {
					if ( $debug ) {
						echo " - NOT Sending Overdue Invoice Notice for " . module_invoice::link_open( $invoice['invoice_id'], true ) . " because it was last sent today already.<br>\n";
					}
					$send_email_on = false;
				}
				if ( $send_email_on !== false && $debug ) {
					echo " - will send next invoice at: " . date( 'Y-m-d', $send_email_on ) . " ( $send_email_on ) <br>\n";
				}

				if ( $send_email_on !== false && $send_email_on <= strtotime( date( 'Y-m-d' ) ) ) {
					if ( $debug ) {
						echo " - Automatically Sending Overdue Invoice Notice for " . module_invoice::link_open( $invoice['invoice_id'], true ) . "<br>\n";
					}
					if ( $debug ) {
						echo " - Emailing invoice to customer...";
					}
					if ( module_invoice::email_invoice_to_customer( $invoice['invoice_id'], $debug ) ) {
						if ( $debug ) {
							echo "sent successfully<br>\n";
						}
					} else {
						echo "sending overdue invoice email failed for " . module_invoice::link_open( $invoice['invoice_id'], true ) . "<br>\n";
					}
					if ( $debug ) {
						echo "<br>\n";
					}
				}
			}
		}
		// find automatic invoice renewals
		$sql            = "SELECT i.* FROM `" . _DB_PREFIX . "invoice` i ";
		$sql            .= " WHERE i.date_renew != '0000-00-00'";
		$sql            .= " AND i.date_create != '0000-00-00'";
		$sql            .= " AND i.date_cancel = '0000-00-00'";
		$sql            .= " AND i.date_renew <= '" . date( 'Y-m-d' ) . "'";
		$sql            .= " AND (i.renew_invoice_id IS NULL OR i.renew_invoice_id = 0)";
		$sql            .= " AND (i.renew_auto = 1)";
		$renew_invoices = qa( $sql );
		foreach ( $renew_invoices as $renew_invoice ) {
			// time to automatically renew this invoice! woo!
			if ( $debug ) {
				echo "Automatically Renewing invoice " . module_invoice::link_open( $renew_invoice['invoice_id'], true ) . "<br>\n";
			}
			$invoice_data = module_invoice::get_invoice( $renew_invoice['invoice_id'] );
			if ( module_config::c( 'invoice_auto_renew_only_paid_invoices', 1 ) && $invoice_data['total_amount_due'] > 0 ) {
				// invoice hasnt been paid, dont continue with renewl
				if ( $debug ) {
					echo "NOT RENEWING INVOICE because it hasn't been paid yet !!! <br>\n";
				}
			} else {
				$new_invoice_id = $this->renew_invoice( $renew_invoice['invoice_id'] );
				if ( $new_invoice_id ) {
					//module_cache::clear_cache();
					if ( $debug ) {
						echo "invoice Automatically Renewed: " . module_invoice::link_open( $new_invoice_id, true ) . "<br>\n";
					}
					if ( $renew_invoice['renew_email'] ) {
						if ( $debug ) {
							echo "Emailing invoice to customer...";
						}
						if ( module_invoice::email_invoice_to_customer( $new_invoice_id, $debug ) ) {
							if ( $debug ) {
								echo "send successfully";
							}
						} else {
							echo "sending renewed invoice email failed for " . module_invoice::link_open( $new_invoice_id, true ) . "<br>\n";
						}
						if ( $debug ) {
							echo "<br>\n";
						}

					}
				}
			}
		}


	}


	public static function bulk_handle_delete() {
		if ( isset( $_REQUEST['bulk_action'] ) && isset( $_REQUEST['bulk_action']['delete'] ) && $_REQUEST['bulk_action']['delete'] == 'yes' && module_form::check_secure_key() && module_invoice::can_i( 'delete', 'Invoices' ) ) {
			// confirm deletion of these tickets:
			$invoice_ids = isset( $_REQUEST['invoice_bulk_operation'] ) && is_array( $_REQUEST['invoice_bulk_operation'] ) ? $_REQUEST['invoice_bulk_operation'] : array();
			foreach ( $invoice_ids as $invoice_id => $k ) {
				if ( $k != 'yes' ) {
					unset( $invoice_ids[ $invoice_id ] );
				} else {
					$invoice_ids[ $invoice_id ] = module_invoice::link_open( $invoice_id, true );
				}
			}
			if ( count( $invoice_ids ) > 0 ) {
				if ( module_form::confirm_delete( 'invoice_id', _l( "Really delete invoices: %s", implode( ', ', $invoice_ids ) ), self::link_open( false ) ) ) {
					foreach ( $invoice_ids as $invoice_id => $invoice_number ) {
						self::delete_invoice( $invoice_id );
					}
					set_message( _l( "%s invoices deleted successfully", count( $invoice_ids ) ) );
					redirect_browser( self::link_open( false ) );
				}
			}
		}
	}

	public static function create_new_invoice_for_subscription_payment( $invoice_id, $invoice_payment_id, $invoice_payment_subscription_id ) {
		// we have an inbound subscription payment for an invoice.
		// we have to generate a new invoice (or find the generated invoice if one exists)

		// first we have to check if this payment is for this invoice (ie: the first subscription payment)
		$invoice_data = self::get_invoice( $invoice_id );
		if ( $invoice_data['total_amount_due'] > 0 ) {
			// this invoice is unpaid, we apply this subscription payment against thsi invoice
			return array(
				'invoice_id'         => $invoice_id,
				'invoice_payment_id' => $invoice_payment_id,
			);
		}

		// first we look for a generated invoice, this is easiest.
		if ( class_exists( 'module_subscription', false ) ) {
			// check if this invoice is part of a subscription.
			// if it is we hunt through the subscription history until we find a recent unpaid invoice
			// THIS CODE IS SIMILAR TO module_invoice::is_automatic_paying_invoice($invoice_id)
			$subscription_history_item = get_single( 'subscription_history', 'invoice_id', $invoice_id );
			if ( $subscription_history_item && $subscription_history_item['subscription_owner_id'] ) {
				// we have an invoice that is on a subscription!
				$subscription_owner = module_subscription::get_subscription_owner( $subscription_history_item['subscription_owner_id'] );
				// check if there are unpaid invoices that were generated after this invoice.
				if ( $subscription_owner['subscription_owner_id'] == $subscription_history_item['subscription_owner_id'] ) {
					$subscription_history = get_multiple( 'subscription_history', array( 'subscription_owner_id' => $subscription_owner['subscription_owner_id'] ) );
					foreach ( $subscription_history as $h ) {
						if ( $h['invoice_id'] > $invoice_id && $h['paid_date'] == '0000-00-00' ) {
							// found an invoice for this subscription that was generated after the initial invoice that is unpaid.
							// apply subscription payment to this one.
							$invoice_data = module_invoice::get_invoice( $h['invoice_id'] );
							if ( $invoice_data['total_amount_due'] > 0 ) {
								$invoice_payment_id = update_insert( 'invoice_payment_id', false, 'invoice_payment', array(
									'invoice_id'                      => $h['invoice_id'],
									'payment_type'                    => _INVOICE_PAYMENT_TYPE_NORMAL,
									'method'                          => _l( 'Pending Subscription' ),
									'currency_id'                     => $invoice_data['currency_id'],
									'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
								) );

								return array(
									'invoice_id'         => $h['invoice_id'],
									'invoice_payment_id' => $invoice_payment_id,
								);
							}
						}
					}
					// if we get here it means we have a subscription invoice that hasn't been renewed yet.
					$subscription = module_subscription::get_subscription( $subscription_owner['subscription_id'] );
					// we force the renewal of the next invoice in this subscription lot and mark it as paid.
					$invoice_id = module_subscription::generate_subscription_invoice( $subscription_owner['subscription_id'], $subscription_owner['owner_table'], $subscription_owner['owner_id'], date( 'Y-m-d' ), $subscription['amount'] );
					if ( $invoice_id ) {
						$invoice_data       = module_invoice::get_invoice( $invoice_id );
						$invoice_payment_id = update_insert( 'invoice_payment_id', false, 'invoice_payment', array(
							'invoice_id'                      => $invoice_id,
							'payment_type'                    => _INVOICE_PAYMENT_TYPE_NORMAL,
							'method'                          => _l( 'Pending Subscription' ),
							'currency_id'                     => $invoice_data['currency_id'],
							'invoice_payment_subscription_id' => $invoice_payment_subscription_id,
						) );
						if ( $subscription['automatic_email'] && module_config::c( 'invoice_subscription_send_due_email_before_payment', 1 ) ) {
							if ( module_invoice::email_invoice_to_customer( $invoice_id ) ) {

							} else {
								echo " - failed to send subscription invoice " . module_invoice::link_open( $invoice_id, true ) . " to customer <br>\n";
							}
							exit;
						}

						return array(
							'invoice_id'         => $invoice_id,
							'invoice_payment_id' => $invoice_payment_id,
						);
					}
				}
			}
		}
	}

	public static function is_automatic_paying_invoice( $invoice_id ) {


		$invoice_payments = module_invoice::get_invoice_payments( $invoice_id );
		foreach ( $invoice_payments as $payment ) {
			if ( isset( $payment['invoice_payment_subscription_id'] ) && $payment['invoice_payment_subscription_id'] ) {
				return true;
			}
		}
		// check if this is part of a subscription, and if the previous subscription
		if ( class_exists( 'module_subscription', false ) ) {
			// THIS CODE EXISTS
			// check if this invoice is part of a subscription.
			// if it is we hunt through the subscription history until we find a recent unpaid invoice
			$subscription_history_item = get_single( 'subscription_history', 'invoice_id', $invoice_id );
			if ( $subscription_history_item && $subscription_history_item['subscription_owner_id'] ) {
				// we have an invoice that is on a subscription!
				$subscription_owner = module_subscription::get_subscription_owner( $subscription_history_item['subscription_owner_id'] );
				// check if there are unpaid invoices that were generated after this invoice.
				if ( $subscription_owner['subscription_owner_id'] == $subscription_history_item['subscription_owner_id'] ) {
					$subscription_history = get_multiple( 'subscription_history', array( 'subscription_owner_id' => $subscription_owner['subscription_owner_id'] ) );
					foreach ( $subscription_history as $h ) {
						$invoice_payments = module_invoice::get_invoice_payments( $h['invoice_id'] );
						foreach ( $invoice_payments as $payment ) {
							if ( isset( $payment['invoice_payment_subscription_id'] ) && $payment['invoice_payment_subscription_id'] ) {
								$payment_subscription = get_single( 'invoice_payment_subscription', 'invoice_payment_subscription_id', $payment['invoice_payment_subscription_id'] );
								if ( $payment_subscription && ( $payment_subscription['status'] == _INVOICE_SUBSCRIPTION_ACTIVE ) ) { //} || $payment_subscription['status'] == _INVOICE_SUBSCRIPTION_PENDING)){
									return true;
								}
							}
						}
					}
				}
			}
		}

		return false;
	}

	public static function calculate_fee( $invoice_id, $invoice_data, $payment_amount, $options ) {

		/* options array(
                    'percent' => $fee_percent,
                    'amount' => $fee_amount,
                    'description' => $fee_description,
                ) */

		if ( module_config::c( 'invoice_fee_calculate_reverse', 0 ) ) {
			$fee_total = round( ( $payment_amount + ( isset( $options['amount'] ) ? $options['amount'] : 0 ) ) / ( isset( $options['percent'] ) ? 1 - ( $options['percent'] / 100 ) : 1 ), 2 ) - $payment_amount;
		} else {
			$fee_total = round( ( $payment_amount * ( ( isset( $options['percent'] ) ? $options['percent'] : 0 ) / 100 ) ) + ( isset( $options['amount'] ) ? $options['amount'] : 0 ), 2 );
		}

		// do we add in taxes ? nfi. for not - no
		return $fee_total;
	}

	public static function get_fees( $invoice_id, $invoice_data, $invoice_payment_id = false ) {
		// find out what invoice_payments have been successful (or any manually inputted payment id's) to work out the fee
		$invoice_payments = self::get_invoice_payments( $invoice_id );
		// find any completed payments that contain fees
		$fees = array();
		foreach ( $invoice_payments as $invoice_payment_data ) {
			if ( ( $invoice_payment_data['date_paid'] && $invoice_payment_data['date_paid'] != '0000-00-00' ) || ( $invoice_payment_id && $invoice_payment_data['invoice_payment_id'] == $invoice_payment_id ) ) {
				// check for fees
				if ( $invoice_payment_data['fee_total'] != 0 ) {
					$fees[] = array(
						'total'       => $invoice_payment_data['fee_total'],
						'percent'     => $invoice_payment_data['fee_percent'],
						'amount'      => $invoice_payment_data['fee_amount'],
						'description' => $invoice_payment_data['fee_description'],
					);
				}
			}
		}

		return $fees;
	}

	public static function get_invoice_tickets( $invoice_id ) {
		if ( ! (int) $invoice_id ) {
			if ( ! empty( $_GET['ticket_ids'] ) && is_array( $_GET['ticket_ids'] ) ) {
				// the 'create invoice' button from ticket page.
				$return = array();
				foreach ( $_GET['ticket_ids'] as $ticket_id ) {
					$return[] = array( 'ticket_id' => (int) $ticket_id );
				}

				return $return;
			}
			if ( ! empty( $_GET['ticket_id'] ) ) {
				// the 'create invoice' button from ticket page.
				return array( array( 'ticket_id' => (int) $_GET['ticket_id'] ) );
			}

			return array();
		} else {
			return get_multiple( 'ticket_invoice_rel', array( 'invoice_id' => $invoice_id ), 'ticket_id' );
		}
	}

	public static function customer_archived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'invoice` SET `archived` = 1 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}

	public static function customer_unarchived( $hook, $customer_id ) {
		$customer_id = (int) $customer_id;
		if ( $customer_id > 0 ) {
			$sql = 'UPDATE `' . _DB_PREFIX . 'invoice` SET `archived` = 0 WHERE `customer_id` = ' . $customer_id;
			query( $sql );
		}
	}


	public static function api_filter_invoice( $hook, $response, $endpoint, $method ) {
		$response['invoice'] = true;
		switch ( $method ) {
			case 'create':

				$invoice_data = isset( $_POST['invoice_data'] ) && is_array( $_POST['invoice_data'] ) ? $_POST['invoice_data'] : array();

				$response['invoice_id'] = 0;
				if (
					! empty( $invoice_data )
					&& ! empty( $invoice_data['customer_id'] )
					&& ! empty( $invoice_data['line_items'] )
				) {

					$_REQUEST['customer_id']          = isset( $invoice_data['customer_id'] ) ? (int) $invoice_data['customer_id'] : 0;
					$new_invoice                      = module_invoice::get_invoice( 'new', true );
					$new_invoice['customer_id']       = $_REQUEST['customer_id'];
					$new_invoice['date_create']       = date( 'Y-m-d' );
					$new_invoice['default_task_type'] = _TASK_TYPE_QTY_AMOUNT;

					$ucminvoice              = new UCMInvoice();
					$ucminvoice->customer_id = $invoice_data['customer_id'];
					if ( ! empty( $invoice_data['invoice_prefix'] ) ) {
						$ucminvoice->set_manual_prefix( $invoice_data['invoice_prefix'] );
					}
					if ( ! empty( $invoice_data['invoice_increment_config'] ) ) {
						$ucminvoice->set_incrementing_config_name( $invoice_data['invoice_increment_config'] );
					}
					$new_invoice['name'] = $ucminvoice->get_new_document_number();

					$new_invoice['invoice_invoice_item'] = array();
					$x                                   = 0;
					foreach ( $invoice_data['line_items'] as $line_item ) {
						$new_invoice['invoice_invoice_item'][ 'new' . $x ++ ] = array(
							'description'      => $line_item['title'],
							'hours'            => $line_item['quantity'],
							'hourly_rate'      => $line_item['cost'],
							'completed'        => 1, // not needed?
							'manual_task_type' => _TASK_TYPE_QTY_AMOUNT,
						);
					};
					$invoice_id = self::save_invoice( 'new', $new_invoice );

					if ( $invoice_id ) {
						$response['invoice_id'] = $invoice_id;
					}
				}

				break;
			case 'search':

				/*$search = isset($_REQUEST['search'])  && is_array($_REQUEST['search']) ? $_REQUEST['search'] : array();
				$customers = self::get_customers($search);
				$response['customers'] = array();
				foreach($customers as $customer){
					$response['customers'][] = $customer;
				}*/

				break;
		}

		return $response;
	}




	public function autocomplete( $search_string = '', $search_options = array() ) {
		$result = array();

		if ( module_invoice::can_i( 'view', 'Invoices' ) ) {
			$search_array = array(
				'generic' => $search_string,
			);
			if ( ! empty( $_REQUEST['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['customer_id'];
			}
			if ( ! empty( $_REQUEST['vars']['customer_id'] ) ) {
				$search_array['customer_id'] = (int) $_REQUEST['vars']['customer_id'];
			}
			$res = module_invoice::get_invoices( $search_array );
			foreach ( $res as $row ) {
				$result[] = array(
					'key'   => $row['invoice_id'],
					'value' => $row['name']
				);
			}
		}

		return $result;
	}



	public function get_upgrade_sql() {
		$sql = '';

		$fields = get_fields( 'invoice' );
		// member/subscription integration:
		if ( ! isset( $fields['date_renew'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `date_renew` DATE NOT NULL AFTER `date_paid`;';
		}
		if ( ! isset( $fields['renew_invoice_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `renew_invoice_id` INT(11) NOT NULL DEFAULT \'0\' AFTER `date_renew`;';
		}
		if ( ! isset( $fields['currency_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `currency_id` int(11) NOT NULL DEFAULT \'1\' AFTER `discount_description`;';
		}
		if ( ! isset( $fields['cached_total'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `cached_total` DECIMAL(10,2) NOT NULL DEFAULT \'0\' AFTER `currency_id`;';
		}
		if ( ! isset( $fields['c_total_amount'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `c_total_amount` DECIMAL(10,2) NOT NULL DEFAULT \'0\' AFTER `cached_total`;';
		}
		if ( ! isset( $fields['c_total_amount_due'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `c_total_amount_due` DECIMAL(10,2) NOT NULL DEFAULT \'0\' AFTER `c_total_amount`;';
		}
		if ( ! isset( $fields['user_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `user_id` int(11) NOT NULL DEFAULT \'0\' AFTER `currency_id`;';
		}
		if ( ! isset( $fields['contact_user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD `contact_user_id` INT NOT NULL DEFAULT \'-1\' AFTER `user_id`;';
			$sql .= 'UPDATE `' . _DB_PREFIX . 'invoice` SET `contact_user_id` = `user_id` WHERE `user_id` != 0;';
			$sql .= 'UPDATE `' . _DB_PREFIX . 'invoice` SET `user_id` = 0;';
		}
		if ( ! isset( $fields['member_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `member_id` INT NOT NULL DEFAULT \'0\' AFTER `user_id`;';
		}
		if ( ! isset( $fields['website_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `website_id` INT NOT NULL DEFAULT \'0\' AFTER `member_id`;';
		}
		if ( ! isset( $fields['date_create'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `date_create` DATE NOT NULL AFTER `total_tax_rate`;';
		}
		if ( ! isset( $fields['discount_type'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `discount_type` INT NOT NULL DEFAULT \'0\' AFTER `discount_description`;';
		}
		if ( ! isset( $fields['tax_type'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `tax_type` INT NOT NULL DEFAULT \'0\' AFTER `discount_type`;';
		}
		if ( ! isset( $fields['deposit_job_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `deposit_job_id` INT NOT NULL DEFAULT \'0\' AFTER `member_id`;';
		}
		if ( ! isset( $fields['date_cancel'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `date_cancel` DATE NOT NULL AFTER `date_renew`;';
		}
		if ( ! isset( $fields['invoice_template_print'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `invoice_template_print` varchar(50) NOT NULL DEFAULT \'\' AFTER `deposit_job_id`;';
		}
		if ( ! isset( $fields['invoice_template_email'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `invoice_template_email` varchar(50) NOT NULL DEFAULT \'\' AFTER `invoice_template_print`;';
		}
		if ( ! isset( $fields['invoice_template_external'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `invoice_template_external` varchar(50) NOT NULL DEFAULT \'\' AFTER `invoice_template_email`;';
		}
		if ( ! isset( $fields['calendar_show'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD  `calendar_show` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `invoice_template_external`;';
		}
		if ( ! isset( $fields['credit_note_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice` ADD `credit_note_id` int(11) NOT NULL DEFAULT \'0\' AFTER `invoice_template_email`;';
		}
		if ( ! isset( $fields['default_task_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `default_task_type` int(3) NOT NULL DEFAULT  \'0\' AFTER  `invoice_template_email`;';
		}
		if ( ! isset( $fields['renew_auto'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `renew_auto` tinyint (1) NOT NULL DEFAULT  \'0\' AFTER  `date_renew`;';
		}
		if ( ! isset( $fields['renew_email'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `renew_email` tinyint (1) NOT NULL DEFAULT  \'0\' AFTER  `renew_auto`;';
		}
		if ( ! isset( $fields['overdue_email_auto'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `overdue_email_auto` tinyint (1) NOT NULL DEFAULT  \'0\' AFTER  `renew_email`;';
		}
		if ( ! isset( $fields['company_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `company_id` int (11) NOT NULL DEFAULT  \'0\' AFTER  `credit_note_id`;';
		}
		if ( ! isset( $fields['vendor_user_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `vendor_user_id` int (11) NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}
		if ( ! isset( $fields['archived'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD `archived` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `default_task_type`;';
		}
		if ( ! isset( $fields['billing_type'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD `billing_type` tinyint(1) NOT NULL DEFAULT  \'0\' AFTER `archived`;';
		}
		if ( ! isset( $fields['auto_task_numbers'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice` ADD  `auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  \'0\' AFTER  `user_id`;';
		}

		$fields = get_fields( 'invoice_payment' );
		if ( ! isset( $fields['currency_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD `currency_id` int(11) NOT NULL DEFAULT \'1\' AFTER `method`;';
		}
		if ( ! isset( $fields['data'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `data` LONGBLOB NULL AFTER  `date_paid`;';
		}
		if ( ! isset( $fields['other_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `other_id` VARCHAR( 255 ) NOT NULL DEFAULT \'\' AFTER  `data`;';
		}
		if ( ! isset( $fields['payment_type'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `payment_type` TINYINT( 2 ) NOT NULL DEFAULT  \'0\' AFTER  `other_id`;';
		}
		if ( ! isset( $fields['invoice_payment_subscription_id'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `invoice_payment_subscription_id` INT( 11 ) NOT NULL DEFAULT  \'0\' AFTER  `other_id`;';
		}
		/*`fee_percent` decimal(10,2) NOT NULL,
    `fee_amount` decimal(10,2) NOT NULL,
    `fee_description` varchar(255) NOT NULL,
    `fee_total` decimal(10,2) NOT NULL,*/

		if ( ! isset( $fields['fee_percent'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `fee_percent`  decimal(10,2) NOT NULL DEFAULT \'0\' AFTER  `payment_type`;';
		}
		if ( ! isset( $fields['fee_amount'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `fee_amount`  decimal(10,2) NOT NULL DEFAULT \'0\' AFTER  `fee_percent`;';
		}
		if ( ! isset( $fields['fee_total'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `fee_total`  decimal(10,2) NOT NULL DEFAULT \'0\' AFTER  `fee_amount`;';
		}
		if ( ! isset( $fields['fee_description'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_payment` ADD  `fee_description`  varchar(255)  NOT NULL DEFAULT \'\' AFTER  `fee_total`;';
		}


		if ( ! self::db_table_exists( 'currency' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'currency` (
  `currency_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(4) NOT NULL,
  `symbol` varchar(8) NOT NULL,
  `location` TINYINT( 1 ) NOT NULL DEFAULT  \'1\',
  `create_user_id` int(11) NOT NULL,
  `update_user_id` int(11) NULL,
  `date_created` date NOT NULL,
  `date_updated` date NULL,
  PRIMARY KEY (`currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
			$sql .= "INSERT INTO `" . _DB_PREFIX . "currency` (`currency_id`, `code`, `symbol`, `location`, `create_user_id`, `update_user_id`, `date_created`, `date_updated`) VALUES
(1, 'USD', '$', 1, 0, 1, '2011-11-10', '2011-11-10'),
(2, 'AUD', '$', 1, 1, NULL, '2011-11-10', '2011-11-10');";
		}
		if ( ! self::db_table_exists( 'invoice_payment_subscription' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'invoice_payment_subscription` (
    `invoice_payment_subscription_id` int(11) NOT NULL AUTO_INCREMENT,
    `status` int(11) NOT NULL DEFAULT \'0\',
    `days` int(11) NOT NULL DEFAULT \'0\',
    `months` int(11) NOT NULL DEFAULT \'0\',
    `years` int(11) NOT NULL DEFAULT \'0\',
    `date_start` date NOT NULL,
    `date_last_pay` date NOT NULL,
    `date_next` date NOT NULL,
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY (`invoice_payment_subscription_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		}

		if ( ! self::db_table_exists( 'invoice_payment_method' ) ) {
			$sql .= "CREATE TABLE `" . _DB_PREFIX . "invoice_payment_method` (
    `invoice_id` int(11) NOT NULL,
    `payment_method` varchar(40) NOT NULL DEFAULT '',
    `enabled` tinyint(1) NOT NULL DEFAULT '1',
    PRIMARY KEY (`invoice_id`, `payment_method`),
	    KEY `invoice_id` (`invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		}
		if ( ! self::db_table_exists( 'invoice_tax' ) ) {
			$sql .= 'CREATE TABLE `' . _DB_PREFIX . 'invoice_tax` (
    `invoice_tax_id` int(11) NOT NULL AUTO_INCREMENT,
    `invoice_id` int(11) NOT NULL,
    `percent` decimal(10,2) NOT NULL DEFAULT  \'0\',
    `amount` decimal(10,2) NOT NULL DEFAULT  \'0\',
    `name` varchar(50) NOT NULL DEFAULT  \'\',
    `order` INT( 4 ) NOT NULL DEFAULT  \'0\',
    `increment` TINYINT( 1 ) NOT NULL DEFAULT  \'0\',
    `create_user_id` int(11) NOT NULL,
    `update_user_id` int(11) NULL,
    `date_created` date NOT NULL,
    `date_updated` date NULL,
    PRIMARY KEY (`invoice_tax_id`),
    KEY (`invoice_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
		} else {

			$fields = get_fields( 'invoice_tax' );
			if ( ! isset( $fields['increment'] ) ) {
				$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_tax` ADD `increment` TINYINT( 1 ) NOT NULL DEFAULT  \'0\' AFTER `order`;';
			}
		}

		$fields = get_fields( 'invoice_item' );
		if ( ! isset( $fields['date_done'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_item` ADD `date_done` DATE NOT NULL AFTER `description`;';
		}
		if ( ! isset( $fields['hourly_rate'] ) ) {
			$sql .= 'ALTER TABLE `' . _DB_PREFIX . 'invoice_item` ADD `hourly_rate` DOUBLE(10,2) NOT NULL DEFAULT \'-1\' AFTER `hours`;';
		}
		if ( ! isset( $fields['taxable'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `taxable` tinyint(1) NOT NULL DEFAULT \'1\' AFTER `amount`;';
		}
		if ( ! isset( $fields['task_order'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `task_order` int(11) NOT NULL DEFAULT \'0\' AFTER `description`;';
		}
		if ( ! isset( $fields['long_description'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD `long_description` LONGTEXT NULL AFTER `description`;';
		}
		if ( ! isset( $fields['manual_task_type'] ) ) { // if -1 then we use job default_task_type
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `manual_task_type` tinyint(2) NOT NULL DEFAULT \'-1\' AFTER `date_due`;';
		}
		if ( ! isset( $fields['product_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `product_id` int(11) NOT NULL DEFAULT \'0\' AFTER `description`;';
		}
		if ( ! isset( $fields['hours_mins'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `hours_mins`  DECIMAL(10,2) NOT NULL DEFAULT  \'0\' AFTER  `hours`;';
		}
		if ( ! isset( $fields['timer_id'] ) ) {
			$sql .= 'ALTER TABLE  `' . _DB_PREFIX . 'invoice_item` ADD  `timer_id`  INT(11) NULL AFTER  `task_id`;';
		}

		// check for indexes
		self::add_table_index( 'invoice', 'customer_id' );
		self::add_table_index( 'invoice', 'deposit_job_id' );
		self::add_table_index( 'invoice', 'credit_note_id' );
		self::add_table_index( 'invoice', 'date_sent' );
		self::add_table_index( 'invoice', 'date_paid' );
		self::add_table_index( 'invoice', 'date_create' );
		self::add_table_index( 'invoice', 'vendor_user_id' );
		self::add_table_index( 'invoice', 'archived' );

		self::add_table_index( 'invoice_item', 'task_id' );
		self::add_table_index( 'invoice_item', 'invoice_id' );
		self::add_table_index( 'invoice_item', 'product_id' );

		/*$sql_check = 'SHOW INDEX FROM `'._DB_PREFIX.'invoice_item';
        $res = qa($sql_check);
        //print_r($res);exit;
        $add_index=true;
        foreach($res as $r){
            if(isset($r['Column_name']) && $r['Column_name'] == 'task_id'){
                $add_index=false;
            }
        }
        if($add_index){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'invoice_item` ADD INDEX ( `task_id` );';
        }

        $add_index=true;
        foreach($res as $r){
            if(isset($r['Column_name']) && $r['Column_name'] == 'invoice_id'){
                $add_index=false;
            }
        }
        if($add_index){
            $sql .= 'ALTER TABLE  `'._DB_PREFIX.'invoice_item` ADD INDEX ( `invoice_id` );';
        }*/

		return $sql;

	}

	public function get_install_sql() {
		ob_start();
		//  `job_id` INT(11) NULL, (useto be in invoice table)
		?>

		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice` (
		`invoice_id` int(11) NOT NULL auto_increment,
		`customer_id` INT(11) NULL,
		`hourly_rate` DECIMAL(10,2) NULL,
		`name` varchar(255) NOT NULL DEFAULT  '',
		`status` varchar(255) NOT NULL DEFAULT  '',
		`total_tax_name` varchar(20) NOT NULL DEFAULT  '',
		`total_tax_rate` DECIMAL(10,2) NULL,
		`date_create` date NOT NULL,
		`date_sent` date NOT NULL,
		`date_due` date NOT NULL,
		`date_paid` date NOT NULL,
		`date_renew` date NOT NULL,
		`renew_auto` tinyint (1) NOT NULL DEFAULT '0',
		`renew_email` tinyint (1) NOT NULL DEFAULT '0',
		`overdue_email_auto` tinyint (1) NOT NULL DEFAULT '0',
		`date_cancel` date NOT NULL,
		`renew_invoice_id` INT(11) NULL,
		`discount_amount` DECIMAL(10,2) NULL,
		`discount_description` varchar(255) NULL,
		`discount_type` INT NOT NULL DEFAULT '0',
		`tax_type` INT NOT NULL DEFAULT '0',
		`currency_id` int(11) NOT NULL DEFAULT '1',
		`cached_total` DECIMAL(10,2) NOT NULL DEFAULT '0',
		`c_total_amount` DECIMAL(10,2) NOT NULL DEFAULT '0',
		`c_total_amount_due` DECIMAL(10,2) NOT NULL DEFAULT '0',
		`user_id` int(11) NOT NULL DEFAULT '0',
		`contact_user_id` int(11) NOT NULL DEFAULT  '-1',
		`vendor_user_id` int(11) NOT NULL DEFAULT '0',
		`member_id` int(11) NOT NULL DEFAULT '0',
		`website_id` int(11) NOT NULL DEFAULT '0',
		`deposit_job_id` int(11) NOT NULL DEFAULT '0',
		`invoice_template_print` varchar(50) NOT NULL DEFAULT '',
		`invoice_template_email` varchar(50) NOT NULL DEFAULT '',
		`invoice_template_external` varchar(50) NOT NULL DEFAULT '',
		`calendar_show` tinyint(1) NOT NULL DEFAULT  '0',
		`default_task_type` int(3) NOT NULL DEFAULT  '0',
		`auto_task_numbers` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`archived` tinyint(1) NOT NULL DEFAULT  '0',
		`billing_type` tinyint(1) NOT NULL DEFAULT  '0',
		`credit_note_id` int(11) NOT NULL DEFAULT '0',
		`company_id` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY  (`invoice_id`),
		KEY `customer_id` (`customer_id`),
		KEY `deposit_job_id` (`deposit_job_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;


		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice_item` (
		`invoice_item_id` int(11) NOT NULL AUTO_INCREMENT,
		`invoice_id` int(11) NOT NULL,
		`task_id` int(11) NULL,
		`timer_id` int(11) NULL,
		`hours` decimal(10,2) NULL,
		`hours_mins` decimal(10,2) NOT NULL DEFAULT '0',
		`amount` decimal(10,2) NULL,
		`taxable` tinyint(1) NOT NULL DEFAULT '1',
		`completed` decimal(10,2) NULL,
		`description` text NOT NULL,
		`long_description` LONGTEXT NULL,
		`task_order` INT NOT NULL DEFAULT  '0',
		`date_done` date NOT NULL,
		`date_due` date NOT NULL,
		`manual_task_type` tinyint(2) NOT NULL DEFAULT '-1',
		`product_id` int(11) NOT NULL DEFAULT '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`invoice_item_id`),
		KEY `invoice_id` (`invoice_id`),
		KEY `product_id` (`product_id`),
		KEY `task_id` (`task_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice_payment` (
		`invoice_payment_id` int(11) NOT NULL AUTO_INCREMENT,
		`invoice_id` int(11) NOT NULL,
		`invoice_payment_subscription_id` int(11) NOT NULL DEFAULT '0',
		`parent_finance_id` int(11) NULL,
		`amount` decimal(10,2) NOT NULL,
		`method` varchar(50) NOT NULL,
		`currency_id` int(11) NOT NULL DEFAULT '1',
		`date_paid` date NOT NULL,
		`data` LONGBLOB NULL,
		`other_id` VARCHAR( 255 ) NOT NULL DEFAULT '',
		`payment_type` TINYINT( 2 ) NOT NULL DEFAULT  '0',
		`fee_percent` decimal(10,2) NOT NULL,
		`fee_amount` decimal(10,2) NOT NULL,
		`fee_description` varchar(255) NOT NULL,
		`fee_total` decimal(10,2) NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`invoice_payment_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice_payment_method` (
		`invoice_id` int(11) NOT NULL,
		`payment_method` varchar(40) NOT NULL DEFAULT '',
		`enabled` tinyint(1) NOT NULL DEFAULT '1',
		PRIMARY KEY (`invoice_id`, `payment_method`),
		KEY `invoice_id` (`invoice_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice_payment_subscription` (
		`invoice_payment_subscription_id` int(11) NOT NULL AUTO_INCREMENT,
		`status` int(11) NOT NULL DEFAULT '0',
		`days` int(11) NOT NULL DEFAULT '0',
		`months` int(11) NOT NULL DEFAULT '0',
		`years` int(11) NOT NULL DEFAULT '0',
		`date_start` date NOT NULL,
		`date_last_pay` date NOT NULL,
		`date_next` date NOT NULL,
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`invoice_payment_subscription_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>invoice_tax` (
		`invoice_tax_id` int(11) NOT NULL AUTO_INCREMENT,
		`invoice_id` int(11) NOT NULL,
		`percent` decimal(10,2) NOT NULL DEFAULT  '0',
		`amount` decimal(10,2) NOT NULL DEFAULT  '0',
		`name` varchar(50) NOT NULL DEFAULT  '',
		`order` INT( 4 ) NOT NULL DEFAULT  '0',
		`increment` TINYINT( 1 ) NOT NULL DEFAULT  '0',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`invoice_tax_id`),
		KEY (`invoice_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		CREATE TABLE `<?php echo _DB_PREFIX; ?>currency` (
		`currency_id` int(11) NOT NULL AUTO_INCREMENT,
		`code` varchar(4) NOT NULL,
		`symbol` varchar(8) NOT NULL,
		`location` TINYINT( 1 ) NOT NULL DEFAULT  '1',
		`create_user_id` int(11) NOT NULL,
		`update_user_id` int(11) NULL,
		`date_created` date NOT NULL,
		`date_updated` date NULL,
		PRIMARY KEY (`currency_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;

		INSERT INTO `<?php echo _DB_PREFIX; ?>currency` (`currency_id`, `code`, `symbol`, `location`, `create_user_id`, `update_user_id`, `date_created`, `date_updated`) VALUES
		(1, 'USD', '$', 1, 0, 1, '2011-11-10', '2011-11-10'),
		(2, 'AUD', '$', 1, 1, NULL, '2011-11-10', '2011-11-10');

		<?php

		return ob_get_clean();
	}


}


include_once 'class.invoice.php';
