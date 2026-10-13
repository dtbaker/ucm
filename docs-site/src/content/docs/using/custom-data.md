---
title: Custom data forms
description: Building your own forms for information this product does not model.
sidebar:
  order: 20
help_keys:
  - data-admin_data_list
  - data-admin_data
  - data-admin_data_new
  - data-admin_data_open
  - data-admin_data_search
  - data-admin_data_list_type
  - data-admin_data_list_embed
  - data-admin_data_list_output
  - data-data_type_admin
  - data-data_type_admin_list
  - data-data_type_admin_open
  - data-data_type_admin_field_open
  - data-data_type_admin_group_open
---

Custom data lets you build your own forms and store records in them. It is the escape
hatch for information the product does not have a place for: equipment registers,
inspection records, membership details, anything specific to your trade.

## Before using it

Check whether [extra fields](/using/extra-fields/) will do instead. If the information
belongs *to* a customer or a job — one value per record — an extra field is simpler and
appears right on the record. Custom data is for things that are records in their own
right, with many of them.

## Building a form

1. **Create a data type.** This is the form: give it a name and whether it links to a
   customer.
2. **Add fields.** Text, number, date, dropdown, checkbox and so on. Each field has a name
   and a label.
3. **Group the fields**, if the form is long enough to want sections.

## Entering and finding records

Once the type exists it gets its own menu entry. Records are created, listed and searched
there. You choose which fields appear as columns in the list, which is worth setting — the
default columns are rarely the ones you want to scan.

## Embedding a form on your website

A data form can be embedded on your own site so people fill it in themselves and the
records land here. _Embed_ on the data type screen gives you the code.

Be careful with this: it is a public form writing to your database. Only embed a form you
are happy for anyone on the internet to submit, and use a captcha.
