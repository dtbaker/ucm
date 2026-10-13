---
title: Recurring invoices and subscriptions
description: Billing the same customer the same amount on a schedule, without remembering to do it.
sidebar:
  order: 9
help_keys:
  - invoice-invoice_recurring
  - subscription-subscription_admin_list
  - subscription-subscription_admin_edit
  - subscription-subscription_admin
---

There are two ways to bill repeatedly, and they answer different questions.

- **A recurring invoice** copies an existing invoice on a schedule. Use it for retainers
  and fixed monthly fees where the amount is the same every time.
- **A subscription** is a product a customer is signed up to, with a renewal date and
  optionally an automatic payment. Use it when the customer is subscribing to something
  rather than being re-billed for work.

Both depend on [the scheduled task](/running/scheduled-tasks/) running. Nothing recurs on
its own — if invoices stop appearing, check the cron job before anything else.

## Setting up a recurring invoice

Create the invoice once, then set it to recur: choose the interval and the date of the
next one. On that date the scheduled task creates a fresh copy with new dates and a new
number, leaving the original alone.

Whether a recurring invoice is renewed when the previous one is still unpaid is controlled
by `invoice_auto_renew_only_paid_invoices`. Switching it on stops an unpaid customer
quietly accumulating invoices.

## Subscriptions

A subscription attaches a product to a customer with a renewal period. When it falls due,
the scheduled task generates the invoice, and — if you have a gateway that supports it —
can take the payment automatically.

`subscription_merge_invoices` controls whether several subscriptions falling due for the
same customer on the same day become one invoice or several. One is usually what the
customer wants.

## Stopping one

Cancelling a subscription or switching off a recurring invoice stops the next one being
created. It does not touch invoices already raised; cancel those with a
[credit note](/using/credit-notes/) if they should not have gone out.
