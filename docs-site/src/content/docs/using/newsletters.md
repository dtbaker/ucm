---
title: Newsletters
description: Sending bulk email to your own lists, and why it goes out in batches.
sidebar:
  order: 16
help_keys:
  - newsletter-newsletter_list
  - newsletter-newsletter_create
  - newsletter-newsletter_edit
  - newsletter-newsletter_admin
  - newsletter-newsletter_dashboard
  - newsletter-newsletter_send
  - newsletter-newsletter_preview
  - newsletter-newsletter_member
  - newsletter-newsletter_queue
  - newsletter-newsletter_queue_manual
  - newsletter-newsletter_queue_watch
  - newsletter-newsletter_list_draft
  - newsletter-newsletter_list_pending
  - newsletter-newsletter_template
  - newsletter-newsletter_template_edit
  - newsletter-newsletter_template_list
  - newsletter-newsletter_statistics
  - newsletter-newsletter_statistics_link_clicks
  - newsletter-newsletter_settings
  - newsletter-newsletter_settings_bounces
---

The newsletter area sends bulk email to lists you build from your own contacts. It is
separate from the transactional email that goes with invoices and quotes, which lives in
[Templates](/using/templates/).

## Lists and members

A list is a group of recipients. Members can be added from your existing contacts or
imported. A contact can be on several lists, and unsubscribing removes them from the one
they unsubscribed from.

## Writing one

Newsletters use their own templates, so the layout is set once and reused. Write the
content, pick the list, and preview it — the preview sends a real message to you, which is
the only way to find out what it looks like in the mail client your customers actually
use.

## Sending, and the queue

A newsletter is not sent in one go. It goes into a queue and is sent in batches by
[the scheduled task](/running/scheduled-tasks/). This is deliberate:

- Shared hosting enforces an hourly mail limit, and exceeding it gets the rest of the
  batch dropped silently — or the account suspended.
- A long send inside a web request would hit the PHP execution time limit partway through.

So if a newsletter shows as sending but recipients are not receiving it, the cron job is
the first thing to check. The queue screen shows what has gone and what is waiting.

## Bounces

Bounce handling reads a mailbox for delivery failures and marks those addresses, so you
stop sending to addresses that do not exist. It is configured under
_Settings → Newsletter → Bounces_ and needs the `imap` extension, the same as
[tickets](/using/tickets/).

## Statistics

Opens and link clicks are recorded per newsletter. Treat open rates as indicative rather
than exact — mail clients that block images do not register an open.

## Before your first send

Check your sending address passes SPF and DKIM for your domain. Bulk mail from an address
that does not authenticate goes to spam regardless of what this software does. See
[Configuration](/running/configuration/) for the mail settings.
