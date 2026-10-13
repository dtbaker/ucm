---
title: Finance
description: Income and expenses in one place, recurring entries for bills you know are coming, and where the summary figures come from.
sidebar:
  order: 13
help_keys:
  - finance-finance_list
  - finance-finance_edit
  - finance-finance
  - finance-finance_quick
  - finance-finance_invoice_edit
  - finance-finance_job_edit
  - finance-recurring
  - finance-recurring_list
  - finance-recurring_edit
  - finance-finance_settings
  - finance-dashboard_summary
  - finance-dashboard_summary_widgets
  - finance-dashboard_popup
  - finance-dashboard_popup_amount_paid
  - finance-dashboard_popup_amount_spent
  - finance-dashboard_popup_hours_logged
---

Finance is where money in and money out are recorded together, so you can see what the
business actually did rather than only what it invoiced.

This is a record of transactions, not an accounting package. There is no double entry, no
chart of accounts and no tax return. It is there to answer "what came in and what went
out, and when".

## Income

Payments recorded against invoices appear here automatically. You do not enter them twice
— record the payment on the [invoice](/using/invoices/) and finance follows.

Income that did not come from an invoice is entered directly: a one-off sale, interest, a
refund received.

## Expenses

Entered by hand, or against a record. An expense can be attached to a job or an invoice,
which is how you see whether a particular piece of work made money rather than just how
much it billed.

## Recurring entries

Rent, hosting, a software subscription — anything you know is coming. A recurring entry
creates the transaction on its schedule so the forecast includes bills that have not been
paid yet.

Like everything that recurs, this needs [the scheduled task](/running/scheduled-tasks/)
running.

## Which date a transaction counts on

`finance_date_type` chooses whether a transaction is counted on the date it was raised or
the date it was paid. The two give materially different figures for a business that
invoices on thirty day terms, so pick the one your accountant uses and leave it alone —
changing it later makes this year look unlike last year for no real reason.

## The summary figures

The amounts on [the dashboard](/using/dashboard/) — invoiced, paid, spent, hours logged —
are calculated from the records each time the page loads. Clicking one opens the
transactions behind it, which is the quickest way to find out why a figure is not what you
expected.
