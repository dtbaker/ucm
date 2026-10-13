---
title: Timers
description: Recording time against a task, a ticket or a quote, and getting it onto an invoice.
sidebar:
  order: 10
help_keys:
  - timer-timer_admin_list
  - timer-timer_admin_edit
  - timer-timer_admin
  - timer-timer_admin_edit_segment
---

A timer records time against something — usually a task on a job — and that time carries
through to the invoice. It is the link between hours worked and money billed.

## Starting one

A start button appears on the records timers are enabled for. Which those are is a
setting, not a fixed list:

- `timer_enable_jobs` — tasks on jobs.
- `timer_enable_tickets` — support tickets.
- `timer_enable_quotes` — quotes.

Running timers are shown in the header, so a timer left running overnight is visible
rather than discovered at the end of the month.

## Segments

A timer is made of segments: start, stop, start again. That means a task worked on across
three days is one timer with three segments, not three separate entries, and the total is
the sum.

Segments can be edited after the fact. If you forgot to start a timer, add a segment with
the times you actually worked rather than running a timer for show.

## Billable and non-billable

`timer_default_billable` sets what a new timer assumes. Non-billable time is still
recorded — it is just not carried onto the invoice, which is what you want for internal
work, rework and the first ten minutes of understanding what the customer actually asked
for.

## Automatic timers

`timer_automatic` starts a timer when you open a task. It suits work where every minute is
billed and catches the time people forget to record. It is also the setting most likely to
produce timers nobody meant to start, so review the timer list before invoicing.

## Getting time onto an invoice

Generating an invoice from a job brings billable time with it, priced at the task's rate.
Check the totals before sending: this is the step where an unnoticed running timer turns
into a surprising invoice.
