---
title: The help system
description: Where the help button gets its text, what it sends over the internet, and how to turn the internet part off.
sidebar:
  order: 27
featured: true
help_keys:
  - help-help_settings
  - help-help_article
---

The question mark in the header opens the documentation for the screen you are on. This
page explains where that text comes from, because the honest answer matters to anyone
running this on a private network.

## Where the text comes from, in order

1. **A copy fetched recently** — anything read in the last week is reused.
2. **The copy that shipped in your download.** Every article is included in the files you
   uploaded. Help works with the network cable pulled out.
3. **The documentation site**, if it can be reached, to pick up anything written since
   your release.
4. **The copy that shipped**, again, if the site cannot be reached.

You are never shown a spinner that ends in nothing. There is always text.

## What leaves your server

When an article is opened — and only then, never when a page loads — your server makes one
request:

```
GET https://ultimateclientmanager.com/docs/api/v1/<article>.json?v=3.5&lang=en
```

That is the whole of it. Three things: which article, which major version of this
software, and a language code.

It carries **no** licence or purchase code, **no** web address or domain or IP for your
installation, **no** user name or email, and nothing at all about your customers,
invoices or jobs. There is no installation identifier, anonymous or otherwise.

Two details are worth stating because they are the ones that are usually got wrong:

- **Your browser never makes this request.** Your server does. A request your browser does
  not make cannot carry a `Referer` or an `Origin` header, and those headers are how an
  installation's address usually escapes without anyone intending it.
- **The article name is checked against the list included in your download** before any
  address is built. An article that is not on that list is answered locally and no request
  is made.

:::note[This has not always been true]
Until recently the help button opened a frame on a third party site with this
installation's purchase codes and web address in the address bar, on **every** admin page
load. That is gone, the code that did it is removed, and the design above exists
specifically so it cannot come back by accident. There is a test in the build that asserts
the exact address above, so adding a fourth field to it fails the build.
:::

## Turning the internet part off

_Settings → Help_ → untick **Fetch the latest documentation**.

With that off, the copy in your download is the only source and this installation makes no
outbound request for help, ever. That is the right setting for an air-gapped or
closed-network installation.

If you leave it on and there is no route out, nothing hangs: requests give up after a few
seconds, and after three failures in a row the system stops trying for an hour rather than
retrying on every click.

## Who can see help

By default anyone whose role allows it. If you would rather restrict it, tick **Only show
help menu for Super Administrator**.

On installations upgraded from an older version that restriction is already on, because it
used to be the default — which meant only the very first user account ever saw the help
button. Untick it to let your staff read the documentation for the screen they are on. See
[Staff and permissions](/using/staff-and-permissions/).
