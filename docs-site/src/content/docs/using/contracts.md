---
title: Contracts
description: A document sent for approval and signature, which can then become a job and an invoice.
sidebar:
  order: 18
help_keys:
  - contract-contract_admin_list
  - contract-contract_admin_create
  - contract-contract_admin_edit
  - contract-contract_admin
  - contract-contract_admin_email
---

A contract works like a [quote](/using/quotes/) — it is sent to a customer or lead, they
approve it, and the approved contract becomes a job or an invoice. The difference is
emphasis: a quote is a price, a contract is terms.

## Creating one

_Contracts → Create Contract_, pick the customer, and write the content. Contracts use
templates, so standard terms are written once and reused rather than pasted each time. See
[Templates](/using/templates/).

## Sending and signing

Emailing the contract sends the customer a link. They read it and sign on screen; the
drawn signature, the date and their IP address are recorded against the contract.

That is an ordinary electronic signature. Whether it satisfies a particular legal
requirement depends on your jurisdiction and what you are signing — take advice rather
than assuming.

## Numbering

- `contract_name_incrementing` switches automatic numbering on.
- `contract_name_incrementing_next` is the next number.

## After approval

An approved contract can be turned into a [job](/using/jobs/) to track the work, or
straight into an [invoice](/using/invoices/).
