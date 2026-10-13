---
title: Contacts
description: The people at a customer. Who gets emailed, who can log in, and how to move a contact to a different customer.
sidebar:
  order: 3
help_keys:
  - user-contact_admin_list
  - user-contact_admin_edit
  - user-contact_admin
  - user-contact_admin_form
  - user-contact_summary
---

A customer is a company; a contact is a person at it. Quotes and invoices are addressed to
a customer, but they are *emailed* to a contact, so a customer with no contacts cannot be
sent anything.

## Adding a contact

Open the customer and use _Add Contact_. A contact needs a first name and an email
address. Everything else — phone numbers, job title, a second address — is optional.

One contact per customer is marked as the **primary contact**. That is the one that gets
used by default when you email a quote or an invoice, and the one shown in list screens.

## Letting a contact log in

A contact can be given a password and allowed into the customer portal, where they see
their own quotes, invoices, tickets and files. Set this up on the contact record itself:
give them a login and a password, and make sure the customer portal is switched on in
_Settings_.

See [The customer portal](/using/customer-portal/) for what they can do once they are in.

A contact who cannot reset their password is usually a contact with no email address
saved, or an installation that cannot send mail at all — check
[Troubleshooting](/reference/troubleshooting/) before assuming the login is broken.

## Moving a contact to another customer

Contacts are attached to one customer at a time. To move one, edit the contact and change
the customer it belongs to. History attached to the contact — tickets they raised, emails
sent to them — moves with them.

This is the usual fix when the same person is entered twice under two companies. Move one,
then delete the duplicate.
