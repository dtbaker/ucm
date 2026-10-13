---
title: Calendar
description: Seeing jobs, tasks and finance dates on a calendar, and subscribing to them from your own software.
sidebar:
  order: 23
help_keys:
  - calendar-calendar_admin
  - calendar-calendar_admin_edit
  - calendar-calendar_admin_edit2
  - calendar-calendar_settings
  - calendar-ical_full
  - calendar-ical_alerts
  - calendar-ical_finance_recurring
  - calendar-ical_finance_summary
  - calendar-ical_finance_transactions
---

The calendar shows dated things from across the system in one place: task due dates,
appointments you have entered, and finance dates such as when a recurring bill falls due.

## Entries you create

Add an appointment directly on the calendar. It can be attached to a customer or a job, in
which case it also shows on that record.

## Subscribing from your own calendar

The calendar is published as iCalendar feeds, so it can be subscribed to from Outlook,
Apple Calendar, Google Calendar or anything else that speaks the format. There are several
feeds rather than one, so you can take only what you want:

- Everything.
- Alerts only — the things that need action.
- Finance: upcoming recurring payments, transactions, summary dates.

Copy the feed address from _Settings → Calendar_ and add it as a subscribed calendar in
your own software.

:::caution[A feed address is a password]
Anyone with the address can read that feed. Treat it like a password: do not post it
anywhere, and regenerate it if it gets out.
:::

Subscribed calendars are read-only and refresh on your calendar software's own schedule,
which is often slower than you expect — several hours is normal for Google Calendar.
