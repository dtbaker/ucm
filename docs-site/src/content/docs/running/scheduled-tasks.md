---
title: 'Scheduled tasks'
description: 'Some work has to happen whether or not anyone is looking at the site. A scheduled task &mdash; a cron job &mdash; is what makes that happen. Setting it up takes a couple of minutes and the application tells you exactly what to paste.'
sidebar:
  order: 2
offline: true
featured: true
help_keys:
  - config-config_cron
---

:::caution[Nothing warns you if this is missing]
Without a scheduled task the site works perfectly in every visible way. Recurring invoices simply never go out, subscriptions never renew, and reminder emails never arrive. It is worth setting up on the day you install, even if you are not using those features yet.
:::

## What it does

<table><thead><tr><th>Area</th><th>What runs</th></tr></thead><tbody><tr><td>Invoices</td><td>Generates recurring invoices when they fall due, and sends overdue reminders.</td></tr><tr><td>Subscriptions</td><td>Renews subscriptions and takes the payment for the next period.</td></tr><tr><td>Email</td><td>Sends anything queued rather than sent immediately, which is how large newsletter runs are paced.</td></tr><tr><td>Tickets</td><td>Collects replies from the mailbox you configured and files them against the right ticket.</td></tr><tr><td>Newsletters</td><td>Works through a scheduled send, and processes bounces and unsubscribes.</td></tr><tr><td>Jobs, customers, suppliers</td><td>Scheduled reminders and notifications.</td></tr></tbody></table>

## Finding the command

The application works out the exact command for your installation and shows it on a screen of its own. There is no entry for it in the Settings menu, so there are two ways to reach it:

-   If the task has not run in the last day, the dashboard shows an alert reading _CRON Job Not Setup_. Click it — on a new installation this is how you will find the screen.
-   Otherwise go to it directly, by adding `?m=config&p=config_cron` to your site address.

That screen shows two things, both filled in with the real paths for your installation:

-   A **command line**, for hosts that let you schedule a command.
-   A **web address**, for hosts that only let you schedule a URL fetch.

Use whichever your hosting supports. The command line version is preferable where you have the choice — it is not reachable from the internet and it is not subject to a web server’s time limit.

## Setting it up with a command

The screen shows something close to this, with your real path:

```
php /home/youraccount/public_html/cron.php
```

In cPanel, Plesk or DirectAdmin, find _Cron Jobs_ and add a new one with that command, running **every 15 minutes**. In the five-field form that is:

<table><thead><tr><th>Minute</th><th>Hour</th><th>Day of month</th><th>Month</th><th>Day of week</th></tr></thead><tbody><tr><td><code>*/15</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td><td><code>*</code></td></tr></tbody></table>

If your control panel offers a plain crontab line instead:

```
*/15 * * * * php /home/youraccount/public_html/cron.php
```

:::note[If php alone does not work]
Some hosts need the full path, which the Cron screen also shows — commonly `/usr/bin/php`. Others run several PHP versions side by side and need a version-specific binary such as `/usr/local/bin/php84`. If the task appears to run but nothing happens, this is the first thing to check: the command may be running an old PHP that the application refuses to start on.
:::

## Setting it up with a web address

If your host only offers scheduled URL fetches, or you want to use an external cron service, use the full address shown on the Cron screen. It looks like this:

```
https://example.com/cron.php?hash=…a long string…
```

:::caution[The hash is a password — treat it as one]
Without the correct hash the address does nothing, which is what stops anyone on the internet triggering your scheduled work. It does not expire and it does not change, so do not post it anywhere public, including in a support request or a screenshot.

It is derived from your installation secret. If you ever change `_UCM_SECRET`, the address changes too and you must update your cron entry with the new one from the Cron screen.
:::

Schedule a fetch of that address every 15 minutes. Any free cron service will do it, as will your host’s own scheduler.

## How often

Every 15 minutes is the recommendation and suits almost everyone. Once a day is enough if all you use is recurring invoicing.

There is no point going below every three minutes: the task refuses to do any work if it ran less than three minutes ago, so more frequent runs exit immediately without doing anything.

## Checking it is working

If the task has not run in the last day, the dashboard shows a _CRON Job Not Setup_ alert telling you when it last ran, or _Never_ if it never has. No alert means it ran within the last day, which is the result you want.

To test it by hand, open the web address version in a browser. A working run returns a page with little or nothing on it — that is correct, it is not meant to be read. If you see `failed - please check cron.php link in settings`, the hash in the address is wrong; copy it again from the Cron screen.

If you want to watch what it does, turn on cron debugging in Settings and run it again. It then prints each plugin as it starts and finishes, which tells you where it is stopping.

:::note[Run from the command line, it does not need the hash]
The hash only applies to requests over the web. Running `php cron.php` on the server works without one, which is why that version is preferable where you have the choice.
:::

## Common problems

<table><thead><tr><th>Symptom</th><th>Usually means</th></tr></thead><tbody><tr><td>Last run says <em>Never</em></td><td>The cron entry was never created, or the command path is wrong.</td></tr><tr><td>It runs but nothing happens</td><td>The command is calling an older PHP than 8.4. Use a version-specific PHP binary.</td></tr><tr><td><code>failed - please check cron.php link in settings</code></td><td>Wrong or missing hash in the web address.</td></tr><tr><td>Cron emails you errors every 15 minutes</td><td>Something inside a plugin’s scheduled work is failing. Turn on cron debugging to find which, then look at that area’s settings.</td></tr><tr><td>Recurring invoices go out late</td><td>The task is only running once a day. Move it to every 15 minutes.</td></tr><tr><td>Ticket replies are not collected</td><td>The <code>imap</code> PHP extension is missing, or the mailbox settings are wrong. Everything else in cron still runs.</td></tr></tbody></table>
