---
title: Quotes
description: Build a quote from your product list, send it for approval, and turn the approved one into a job or an invoice.
sidebar:
  order: 5
featured: true
help_keys:
  - quote-quote_admin_list
  - quote-quote_admin_create
  - quote-quote_admin_edit
  - quote-quote_admin
  - quote-quote_admin_email
  - quote-quote_admin_email_staff
---

A quote is a priced proposal to a customer or a lead. Once they approve it, it becomes a
job you can work on, or an invoice you can send — you do not retype anything.

## Creating one

_Quotes → Create Quote_, then pick the customer. The quote is built from lines, and each
line can be:

- **A product** from your product list, which brings its description and price with it.
  See [Products](/using/products/).
- **A free text line** you type in, for work that is not a catalogue item.

Each line has a quantity, a unit price and a tax setting. The quote total is the sum, with
tax calculated per line rather than on the total, so mixed tax rates on one quote work
correctly.

## Sending it

_Email Quote_ sends it to the customer's primary contact using a template you can edit
under [Templates](/using/templates/). The email contains a link that lets the customer
open the quote without logging in.

## Approval

The customer opens the link and either approves or declines. Approving records a drawn
signature, the date and their IP address against the quote, so there is a record of who
agreed to what.

You can also mark a quote approved yourself, for the case where the customer agreed on the
phone.

## Turning an approved quote into work

An approved quote has a **Create Invoice** button. If you track the work first, create a
job from it instead and invoice at the end — see [Jobs](/using/jobs/).

Converting copies the lines across. Editing the invoice afterwards does not change the
quote, so the quote stays as a record of what was agreed.

## Quote numbers

Two settings control numbering:

- `quote_name_incrementing` — switch automatic numbering on.
- `quote_name_incrementing_next` — the next number to be used.

Set the second one before you start if you are continuing a sequence from another system.
