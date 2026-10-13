---
title: Products
description: A catalogue of the things you sell, so quotes and invoices are built by picking rather than typing.
sidebar:
  order: 12
help_keys:
  - product-product_admin_list
  - product-product_admin_edit
  - product-product_admin
  - product-product_admin_category
  - product-product_admin_category_edit
  - product-product_admin_category_list
  - product-product_settings
  - product-product_supplier_admin
---

A product is anything you put on a quote or an invoice: a physical item, an hour of a
particular kind of work, a recurring service. Keeping them here means consistent wording
and consistent prices, and it makes building a quote a matter of picking lines.

## Adding one

_Products → Create Product_. What matters:

- **Name** — short, what you would say on the phone.
- **Description** — the longer wording that lands on the quote or invoice. Write it for
  the customer.
- **Price** and **tax rate**.
- **Category**, optionally.

## Categories

Categories group products so a long catalogue is navigable. Whether the category name is
shown alongside the product on a quote or invoice line is controlled by
`product_show_category_in_tasks`.

## Suppliers

A product can record which supplier you buy it from, which is useful when a price changes
and you need to find everything affected. Suppliers are customer records with the supplier
type — see [Suppliers and vendors](/using/suppliers/).

## Changing a price

Changing a product's price changes it for future quotes and invoices only. Documents
already raised keep the price they were raised at, which is the correct behaviour — an
invoice must not change under a customer who already has it.

## Subscriptions

A product can be set up as something a customer subscribes to rather than buys once. See
[Recurring invoices and subscriptions](/using/recurring-invoices/).
