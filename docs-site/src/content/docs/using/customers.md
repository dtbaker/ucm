---
title: Customers and leads
description: Customers are the record everything else hangs off. This is how to create one, what the fields are for, and how leads differ.
sidebar:
  order: 2
featured: true
help_keys:
  - customer-customer_admin_list
  - customer-customer_admin_open
  - customer-customer_admin_edit
  - customer-customer_summary
  - customer-customer_admin_email
  - customer-customer_admin_email_welcome
---

Customers are the core record in this system. Contacts, quotes, jobs, websites, invoices,
tickets and files all sit underneath one, which is why it is worth getting the customer
record right before doing anything else.

## Creating a customer

_Customers → Create Customer_. The only field that is genuinely required is the name.
Everything else can be filled in later, and most of it is there to be pulled into
documents:

- **Name** — appears on every quote, invoice and PDF.
- **Addresses** — a customer can have several. One is the billing address used on
  invoices; the others are for delivery or site visits.
- **Contacts** — the actual people. See [Contacts](/using/contacts/). A customer with no
  contact cannot be emailed, because there is no address to send to.
- **Extra fields** — anything this product does not model. See
  [Extra fields](/using/extra-fields/).

## Leads, customers and suppliers

They are the same record with a different **customer type**. Changing the type changes
which menu the record appears under and which fields are shown; it does not move any data,
and nothing attached to the record is lost. So converting a lead into a customer is a
single dropdown, not a migration.

Types are defined in _Settings → Customers → Types_. Each one gets its own menu entry and
its own numbering sequence.

## Customer numbers

Customers can be numbered automatically. Two settings control it:

- `customer_mask_0` — the format, written as a `sprintf()` pattern. `CUST-%05d` gives
  `CUST-00017`.
- `customer_next_0` — the next number to be used.

If you are moving from another system and want to keep its numbering, set `customer_next_0`
to one above your highest existing number before importing.

## Deleting a customer

Deleting a customer does not delete the invoices, jobs and quotes underneath it — those
are kept, because deleting financial records silently would be the wrong thing to do. If
you are tidying up rather than correcting a mistake, consider changing the customer type
to something inactive instead.
