---
title: Invoices
description: Create an invoice, send it, record a payment, and chase the ones that are late.
sidebar:
  order: 7
featured: true
help_keys:
  - invoice-invoice_admin_list
  - invoice-invoice_admin_edit
  - invoice-invoice_admin
  - invoice-invoice_admin_email
  - invoice-invoice_admin_print
  - invoice-invoice_settings
---

Invoices bill customers for work. Most arrive here by being generated from a job or a
quote rather than typed from scratch, which is the way to do it — the lines, amounts and
tracked time come across with the right descriptions already on them.

## Creating one directly

_Invoices → Create Invoice_, pick the customer, add lines. Each line has a description, a
quantity, a unit price and a tax rate. Tax is calculated per line, so an invoice mixing
taxable and non-taxable items totals correctly.

A **deposit** line is a special case: it bills part of the amount now and leaves the rest
outstanding. Whether a deposit line is taxable by default is set by
`deposit_task_taxable_default`.

## Sending it

_Email Invoice_ sends it to the customer's primary contact, using a template you can edit
under [Templates](/using/templates/). The email includes a link to view and, if you have
connected a gateway, to pay it online.

_Print_ produces the PDF. The invoice layout is a template too — see
[Customising](/developers/customising/) for changing it without editing shipped files.

## Recording a payment

Open the invoice and add a payment: amount, date, and method. An invoice is only income
once a payment is recorded against it, so the finance figures and the dashboard both
depend on this being kept up to date.

Payments taken online through a gateway are recorded automatically when the gateway
confirms them. See [Payment methods](/running/payment-methods/).

Part payments are fine. The invoice shows the balance outstanding until it is settled.

## Overdue invoices

The dashboard lists them, and the scheduled task can email reminders — see
[Scheduled tasks](/running/scheduled-tasks/). If reminders are not going out, the cron job
is almost always the reason.

## Correcting a mistake

Do not edit a sent invoice to reduce it. Raise a [credit note](/using/credit-notes/)
instead, which leaves both documents in the record.
