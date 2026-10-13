---
title: The dashboard
description: What the home screen shows, who sees what, and how to rearrange it.
sidebar:
  order: 1
featured: true
help_keys:
  - pages-home
---

The dashboard is the first screen after logging in, and it shows a different thing to
each kind of person. A staff member sees the work waiting for them. A customer contact
logging into the portal sees their own quotes, invoices and tickets and nothing else.

## What is on it

The dashboard is assembled from widgets, each supplied by a plugin. The ones most
installations see are:

- **Alerts** — overdue invoices, quotes awaiting approval, tasks due. This is the part
  worth reading every morning.
- **Summary figures** — amounts invoiced, amounts paid, hours logged, grouped by period.
- **Recent activity** — the records touched most recently.

If a widget is missing, the plugin that provides it is probably switched off. Check
_Settings → Plugins_ before looking for a dashboard setting.

## Rearranging it

Widget layout is saved per person, not per installation, so moving a widget changes only
your own dashboard. Drag a widget by its heading to move it. The layout is stored against
your user account and the screen you were on, so it survives logging out.

## When the numbers look wrong

The summary figures are calculated from the records as they stand, not from a stored
total, so they cannot drift out of step. If a figure looks wrong, the usual causes are:

- A date range that is not the one you assumed. Most summaries default to the current
  financial year, which is set in [Configuration](/running/configuration/).
- Invoices that are entered but not marked as paid. An invoice counts as income when a
  payment is recorded against it, not when it is sent.
- A second currency. Totals are shown in the system currency; see [Currencies](/using/currencies/).
