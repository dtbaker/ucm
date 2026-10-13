---
title: Currencies
description: Billing a customer in their own currency, and what the system currency is for.
sidebar:
  order: 26
help_keys:
  - invoice-currency
---

Invoices and quotes can be raised in a currency other than your own.

## The system currency

One currency is the system currency. It is what the dashboard totals and the
[finance](/using/finance/) figures are expressed in, because a total mixing three
currencies means nothing.

Set it before raising anything. Changing it later does not re-express existing records.

## Adding a currency

_Settings → Currency_. Each currency has a code, a symbol, how the symbol is placed, and a
conversion rate against the system currency.

## Rates

The rate is a number you maintain. Nothing fetches live rates, which is a deliberate
simplification rather than a missing feature: an invoice has to stay at the rate it was
raised at, or a customer's balance would change overnight without anyone doing anything.

So the rate is used at the moment a document is created and then stored on it. Updating
the rate affects new documents only.

## Invoicing in a customer's currency

Set the currency on the customer and their quotes and invoices default to it. The document
shows their currency; the finance figures show the converted amount in yours.

Record the payment in the currency the invoice was raised in. If the amount that actually
landed in your bank differs because the rate moved, the difference is a separate finance
entry rather than a change to the invoice.
