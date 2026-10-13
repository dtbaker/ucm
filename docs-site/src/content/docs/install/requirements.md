---
title: 'Requirements'
description: 'Check these before you upload anything. Most shared hosting accounts sold today meet all of them, but the PHP version is the one that catches people out.'
sidebar:
  order: 1
offline: true
---

## The short version

<table><thead><tr><th>You need</th><th>Version</th></tr></thead><tbody><tr><td>PHP</td><td><strong>8.4 or newer.</strong> 8.4 and 8.5 are both tested.</td></tr><tr><td>MySQL or MariaDB</td><td>MySQL 8.0, or MariaDB 10.5 or newer, is what this release is developed and tested against.</td></tr><tr><td>Web server</td><td>Apache with <code>mod_rewrite</code>. nginx works with caveats — see below.</td></tr><tr><td>Disk space</td><td>About 120&nbsp;MB for the application, plus whatever your uploaded files come to.</td></tr></tbody></table>

:::danger[PHP 8.4 is a hard requirement]
The application checks the PHP version before it does anything else and stops with a message if it is too old. There is no way to override this. If your host still offers only PHP 7.x or 8.0–8.3, ask them to move your account to 8.4 — most control panels have a PHP version selector that does it in a few seconds.
:::

## PHP extensions

Three are genuinely required. The rest switch individual features on and off.

### Required

<table><thead><tr><th>Extension</th><th>Why</th></tr></thead><tbody><tr><td><code>mysqli</code></td><td>All database access. Without it the application cannot start.</td></tr><tr><td><code>simplexml</code> / <code>libxml</code></td><td>Used throughout for reading XML. The setup wizard tests it with a real parse rather than just checking the extension is listed.</td></tr><tr><td><code>curl</code></td><td>Outbound HTTP, which is how payment gateways are contacted.</td></tr></tbody></table>

### Needed for particular features

<table><thead><tr><th>Extension</th><th>Without it</th></tr></thead><tbody><tr><td><code>gd</code></td><td>Signature images cannot be drawn. If a customer signs a quote and GD is missing, that page fails rather than degrading, so enable it if you use quote signing.</td></tr><tr><td><code>zip</code></td><td>Full backups will not run. The backup settings screen shows a warning saying so.</td></tr><tr><td><code>imap</code></td><td>Tickets and newsletters cannot collect replies from a mailbox. Everything else is unaffected.</td></tr><tr><td><code>openssl</code></td><td>Sending mail through SMTP over SSL or TLS will not work. Plain unencrypted SMTP and the server’s own mail function still do.</td></tr><tr><td><code>mbstring</code></td><td>Note previews are trimmed with a simpler routine that can cut multi-byte characters in half.</td></tr><tr><td><code>fileinfo</code></td><td>Uploaded file types are guessed from the file extension instead of the content.</td></tr></tbody></table>

:::caution[The setup wizard asks for IMAP, and it should not]
The environment check on the first setup screen treats a missing `imap` extension as an error. It is not one — IMAP is only used for pulling email into tickets and newsletters. If you see that message and do not need mailbox collection, click _Ignore Errors_ and carry on. The same screen does not check for GD, which is the one that actually fails hard when it is absent.
:::

## Database

Create an empty database and a user with full rights on it before you start. The installer creates tables, adds indexes and adds foreign key constraints, so the database user needs `CREATE`, `ALTER`, `INDEX`, `REFERENCES`, `SELECT`, `INSERT`, `UPDATE` and `DELETE`. Granting all privileges on that one database is the usual way to do it.

Tables are created as InnoDB. Nothing in the application checks your MySQL version, so an older server may well work — but it is untested, and you would be the first to find out where it does not.

If your database is not on `localhost`, you can give a port or a socket path in the host field during setup, written as `hostname:3306` or `hostname:/path/to/mysql.sock`.

## Web server

### Apache

This is the supported configuration. You need `mod_rewrite` enabled and `AllowOverride` set so that the `.htaccess` files in the download are honoured. Both are the default on virtually all shared hosting.

Those `.htaccess` files do two separate jobs, and both matter:

-   The one in the root turns the site into a single front controller, which is what makes the tidy URLs work.
-   The ones inside the upload, attachment and temporary folders **deny direct web access** to files in them. Without that, anything a user uploads can be fetched straight off your server by anyone who guesses the address.

### nginx

:::danger[nginx needs manual configuration, and the download does not include it]
nginx ignores `.htaccess` entirely. That means two things go wrong at once on an nginx server: the tidy URLs stop resolving, and the rules that protect uploads, ticket attachments and the temporary folder are silently not applied.

If you run nginx you must write the equivalent rules yourself: route everything that is not a real file to `index.php`, and deny direct access to `includes/plugin_file/upload/`, `includes/plugin_data/upload/`, `includes/plugin_ticket/attachments/`, `includes/plugin_email/attachments/`, `includes/plugin_backup/backups/` and `temp/` — except for `.css` and `.js` files in `temp/`, which are the combined stylesheet cache and do need to be served.

If you cannot do the front controller rules, see [Configuration](/running/configuration/) for how to turn tidy URLs off and fall back to plain query string links. The protection rules are not optional in the same way.
:::

## Folder permissions

PHP needs to be able to write to these. On most hosting this is already true because the files belong to your own account; on others you will need to set permissions.

<table><thead><tr><th>Path</th><th>What is written there</th></tr></thead><tbody><tr><td><code>includes/</code></td><td>The database settings file, written once during setup.</td></tr><tr><td><code>temp/</code></td><td>Login sessions, generated PDFs, and the combined stylesheet and script cache.</td></tr><tr><td><code>includes/plugin_file/upload/</code></td><td>Files attached to records.</td></tr><tr><td><code>includes/plugin_data/upload/</code></td><td>Files attached to custom data records.</td></tr><tr><td><code>includes/plugin_ticket/attachments/</code></td><td>Ticket attachments.</td></tr><tr><td><code>includes/plugin_email/attachments/</code></td><td>Attachments on sent email.</td></tr><tr><td><code>includes/plugin_backup/backups/</code></td><td>Generated backup archives.</td></tr></tbody></table>

:::note[The setup wizard will ask you to make includes/config.php writable]
It no longer needs to be. That check is left over from an older version that edited the file directly; this release writes a separate file instead. You can ignore that particular message. The folder `includes/` does need to be writable, which is a different thing.
:::

The wizard checks only the first two rows of that table. The upload folders are created as needed at the moment someone first uploads something, so a permissions problem there shows up later rather than during setup. It is worth confirming them up front.

## Email

Nothing is required to install. To send invoices and notifications you will want either working local mail on the server, or SMTP credentials for a mail provider — your own host’s, or a service such as a transactional email provider. You enter these during setup, or afterwards under Settings, and you can send a test message from that screen.

## A scheduled task

Recurring invoices, subscription renewals, reminder emails and mailbox collection all run from a scheduled task rather than from someone visiting the site. It is not needed to install, but without it those features never fire. Setting it up takes a minute and is covered in [Scheduled tasks](/running/scheduled-tasks/).

## What you do not need

-   **Composer.** Every library the application uses is already inside the download. You never run a build step.
-   **Node, npm or any asset pipeline.** Same reason.
-   **Shell access.** Everything can be done through your hosting control panel and a browser, including the scheduled task on hosts that offer a cron interface.
-   **An internet connection on the server.** The application does not need to reach any outside service to install or run. Outbound access is only used when you take an online payment or check for an update.
