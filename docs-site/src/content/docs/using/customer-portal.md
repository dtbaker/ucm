---
title: The customer portal
description: Letting your customers log in to see their own quotes, invoices and tickets, and the public signup form.
sidebar:
  order: 4
help_keys:
  - customer-customer_public
  - customer-customer_signup
  - customer-customer_signup_form
  - customer-customer_settings
  - customer-customer_settings_basic
  - customer-customer_settings_types
---

The portal is the customer-facing side of the system. A contact with a login sees their
own records and nothing else: their quotes to approve, their invoices to pay, their
tickets, and any files you have shared with them.

## Switching it on

The portal is part of the customer plugin and is available as soon as a contact has a
login. What you choose is how much it shows — the options are in
_Settings → Customers_.

Give a contact a login from their contact record. See [Contacts](/using/contacts/).

## What a customer can do

- **Approve a quote.** They can sign it on screen, which records a drawn signature against
  the quote along with the date and their IP address.
- **Pay an invoice**, if you have connected a payment gateway. See
  [Payment methods](/running/payment-methods/).
- **Raise and reply to tickets.** See [Support tickets](/using/tickets/).
- **Download files** you have attached to their records.

## The public signup form

There is a signup form customers can fill in themselves, which creates a customer record
and a contact with a login. It is useful for a lead capture form on a website; it is also
the thing to be careful with, because it is a public form that writes to your database.

Two templates control what happens afterwards:

- `customer_signup_email_welcome_template` — what is emailed to the person who signed up.
- `customer_signup_email_admin_template` — what is emailed to you.

Both are edited under [Templates](/using/templates/). Set them both before switching the
form on, or the first signup arrives silently and the customer hears nothing back.
