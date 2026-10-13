---
title: 'Ultimate Client Manager'
description: 'A self hosted CRM, quoting, invoicing and job tracking system that runs on ordinary PHP and MySQL hosting. No monthly fee, no external service, and your data stays in your own database.'
sidebar:
  order: 0
offline: true
---

This documentation ships inside the download. It assumes no programming knowledge for installing and running the software, and gets more technical only in the last few pages, which are for people who want to change how it works.

## Start here

- [Requirements](/install/requirements/) — What your hosting account needs before you begin.
- [Installing](/install/installing/) — Upload the files and walk through the setup wizard.
- [Upgrading from 2.x](/install/upgrading/) — If you already run Ultimate Client Manager, read this first.
- [Troubleshooting](/reference/troubleshooting/) — Blank pages, login problems, missing menus, email that will not send.

:::danger[If you are updating an existing installation]
Do not upload these files over the top of a working site until you have read [Upgrading from 2.x](/install/upgrading/). This release raises the minimum PHP version and changes where database settings are stored. Both of those will stop an old installation from loading if you skip the steps.
:::

## What the software does

Ultimate Client Manager is built as a set of plugins around a small core. Each area of the product is a plugin, which is why the feature list is long and why you can switch parts of it off. The main areas are:

<table><thead><tr><th>Area</th><th>What it covers</th></tr></thead><tbody><tr><td>Customers and contacts</td><td>Companies, the people at them, addresses, custom fields, customer types such as leads and suppliers, and a customer portal those contacts can log into.</td></tr><tr><td>Quotes and contracts</td><td>Quotes built from your product list, sent for approval, and signed off online with a drawn signature.</td></tr><tr><td>Jobs and tasks</td><td>Jobs broken into tasks, assigned to staff, with a discussion thread per job and a change request workflow.</td></tr><tr><td>Invoices and payments</td><td>Invoices, credit notes, recurring subscriptions, tax handling, multiple currencies, and online payment through the gateways described in <a href="/running/payment-methods/">Payment methods</a>.</td></tr><tr><td>Time tracking</td><td>A timer that attaches recorded time to a job or task and carries it through to an invoice.</td></tr><tr><td>Support tickets</td><td>A ticket queue that can collect replies from an email mailbox and file them against the right ticket.</td></tr><tr><td>Finance</td><td>Income and expense transactions, recurring entries, and summary reporting.</td></tr><tr><td>Email and newsletters</td><td>Templated transactional email, plus a bulk newsletter sender with its own subscriber lists.</td></tr><tr><td>Files and data</td><td>File uploads attached to records, and a general purpose structured data store for things the product does not model directly.</td></tr><tr><td>Staff and permissions</td><td>Staff accounts, roles, and per-feature permissions.</td></tr></tbody></table>

Sixty six plugins ship in total. [The plugin system](/developers/plugins/) lists every one of them with its version, and explains how they fit together.

## How the documentation is arranged

-   **Getting started** takes you from a fresh hosting account to a working login, or from an existing 2.x installation to this release.
-   **Running it** covers the settings you will touch after installing: configuration values, the scheduled task that drives recurring work, and connecting a payment gateway.
-   **Making it yours** explains the plugin architecture and the `custom/` folder, which lets you change almost any screen or template without editing the files you were shipped — so your changes survive the next update.
-   **Reference** holds the troubleshooting guide, what changed in this release, and the credits and licences for the third party code included in the download.

## What is in the download

<table><thead><tr><th>Folder</th><th>What it is</th></tr></thead><tbody><tr><td><code>Main&nbsp;Files/</code></td><td>The application. This is what you upload to your web host.</td></tr><tr><td><code>Documentation/</code></td><td>What you are reading. It does not need to be uploaded.</td></tr><tr><td><code>Licensing/</code></td><td>The licence of every third party component included in the application. See <a href="/reference/credits/">Credits and licences</a>.</td></tr></tbody></table>

## Getting help

Support is through the item’s comments page on CodeCanyon. Before posting, it is worth checking [Troubleshooting](/reference/troubleshooting/) — it covers the problems that come up most often, and in most cases tells you exactly which setting or file permission is at fault.

If you report a problem, say which PHP version your host runs, whether the site is on Apache or nginx, and what the page did — a blank screen, an error message, or the wrong result. Those three answers resolve most reports immediately.
