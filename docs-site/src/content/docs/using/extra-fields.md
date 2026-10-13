---
title: Extra fields
description: Adding your own fields to customers, jobs, invoices and tickets.
sidebar:
  order: 21
help_keys:
  - extra-extra_settings
---

Extra fields add your own information to records that already exist. An account reference
on a customer, a purchase order number on an invoice, a serial number on a ticket.

## Adding one

Extra fields are configured per record type under _Settings_. Give the field a name and a
type, and it appears on that record's edit form.

A field can be set to show in the list screen as a column, and in some areas it becomes
searchable, so an account reference is a field you can actually look a customer up by.

## Where the values can be used

Extra field values can be pulled into [templates](/using/templates/) — an invoice email
quoting the customer's own purchase order number, for instance. The template edit screen
lists the variables available.

## Flags

Some extra fields have special meaning rather than just holding a value. A flag field
marks records in the list, which is a cheap way to maintain your own status that the
product does not have a concept of.

## Settings

- `hide_extra` hides extra fields from screens where they would otherwise show.
- `accounts_extra_field_name` nominates the field holding an accounting system reference,
  for installations that export to one.

## When to use custom data instead

If you find yourself adding six extra fields that only make sense together, you probably
want [a custom data form](/using/custom-data/) instead.
