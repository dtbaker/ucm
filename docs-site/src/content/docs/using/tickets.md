---
title: Support tickets
description: A ticket queue that can collect replies straight out of an email mailbox.
sidebar:
  order: 11
featured: true
help_keys:
  - ticket-ticket_admin_list
  - ticket-ticket_admin_edit
  - ticket-ticket_admin_edit_messages
  - ticket-ticket_admin
  - ticket-ticket_admin_open
  - ticket-ticket_admin_notify
  - ticket-ticket_settings
  - ticket-ticket_settings_basic
  - ticket-ticket_settings_accounts
  - ticket-ticket_settings_types
  - ticket-ticket_settings_fields
  - ticket-ticket_settings_embed
  - ticket-ticket_settings_bulk
---

Tickets are support requests, raised by you, by a customer in the portal, or by an email
arriving in a mailbox you have connected.

## Where tickets come from

- **The portal.** A contact with a login raises one directly.
- **An embedded form** on your own website. _Settings → Tickets → Embed_ gives you the
  code.
- **Email.** This is the one worth setting up.

## Collecting email

Connect a mailbox under _Settings → Tickets → Accounts_. The system logs into it over
IMAP, reads new messages, and files each one against the right ticket — a reply joins its
existing thread rather than starting a new one.

Three things have to be true for this to work:

1. The `imap` PHP extension is installed. See [Requirements](/install/requirements/).
2. The mailbox details are right, including the port and whether it uses SSL.
3. [The scheduled task](/running/scheduled-tasks/) is running. Mail is collected by cron,
   not when you open the ticket screen.

If tickets stop arriving, check those three in that order.

## Types, fields and defaults

- **Types** categorise tickets — bug, question, billing. Each can have its own default
  assignee.
- **Extra fields** add your own questions to the ticket form. See
  [Extra fields](/using/extra-fields/).
- `ticket_default_user_id` is who an unassigned ticket goes to.
- `ticket_default_account_id` is the mailbox replies are sent from.
- `ticket_turn_around_days` is the target response time, used to flag tickets that are
  running late.

## Billing support time

Switch on `timer_enable_tickets` and you can run a [timer](/using/timers/) against a
ticket, which makes support time billable the same way job time is.
