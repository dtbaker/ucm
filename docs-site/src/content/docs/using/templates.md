---
title: Templates
description: Every piece of text the system sends or prints, editable without touching code.
sidebar:
  order: 15
featured: true
help_keys:
  - template-template_list
  - template-template_edit
  - template-template
  - template-template_print
---

Templates hold the wording of everything the system sends or prints: the email that goes
with an invoice, the welcome message a new customer gets, the reminder for an overdue
account, the layout of the printed quote.

Editing a template needs no programming. It is the single highest-value thing to do after
installing, because the default wording is generic and your customers read it.

## Finding the right one

_Templates_ lists them all. The names are systematic: templates whose key starts
`email_template_` are emails, and the rest of the name says which one. So the email sent
with an invoice is `email_template_invoice`, and so on.

If you are not sure which template produced a message, search the template list for a
distinctive phrase from the message.

## Variables

Templates contain placeholders that are replaced when the message is sent — the customer's
name, the invoice number, the amount, a link to pay. Each template's edit screen lists the
variables available to it, which is the list to work from; a variable that belongs to a
different template is left as-is in the output, which is how `{invoice_number}` ends up
printed literally in a welcome email.

## Editing safely

Change the wording freely. Be careful with two things:

- **Do not delete a variable you did not mean to.** Removing the payment link from the
  invoice email is easy to do and hard to notice.
- **Keep the links.** Quote approval and invoice payment both depend on the link in the
  email.

Send yourself a test after editing. Every template that goes to a customer can be emailed
to you first.

## Print layouts

The printed quote and invoice are templates too. Small changes — wording, which fields
appear — are made here. Anything structural is better done in the `custom/` folder so it
survives the next update; see [Customising](/developers/customising/).
