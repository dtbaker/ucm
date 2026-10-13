---
title: 'Configuration'
description: 'Almost everything is configured through the Settings screens and stored in the database. A small number of low level values live in a file, because they are needed before the database is available.'
sidebar:
  order: 1
offline: true
help_keys:
  - config-config_admin
  - config-config_settings
  - config-config_basic_settings
  - config-config_generate
  - config-config_menu
---

## The two places settings live

<table><thead><tr><th>Where</th><th>What is there</th><th>How to change it</th></tr></thead><tbody><tr><td><code>includes/config.local.php</code></td><td>Database connection, the installation secret, and a handful of switches that have to be decided before the database is open.</td><td>Edit the file.</td></tr><tr><td>The <code>config</code> database table</td><td>Everything else. Company details, currency, tax rates, email, templates, permissions, payment gateway credentials, theme.</td><td>The Settings screens in the admin area.</td></tr></tbody></table>

## The settings file

`includes/config.local.php` is created by the installer and is the only configuration file you ever edit. It is not part of the download, so an update never overwrites it.

`includes/config.php` sits next to it and holds the defaults. **Do not edit that one** — it is replaced every time you update. Anything you want to change, define in `config.local.php` instead. It is read first, and a value defined there wins.

### Database connection

```
<?php
define( '_DB_SERVER', 'localhost' );
define( '_DB_NAME',   'your_database' );
define( '_DB_USER',   'your_user' );
define( '_DB_PASS',   'your_password' );
define( '_DB_PREFIX', 'ucm_' );
```

:::caution[These five are all or nothing]
They are defined together. If your file sets some but not all five, the missing ones are never defined and the application stops with an error. Always list all five.
:::

`_DB_SERVER` accepts a port as `hostname:3306` or a socket path as `hostname:/var/run/mysqld/mysqld.sock`.

### The installation secret

```
define( '_UCM_SECRET', '…64 random hexadecimal characters…' );
```

Every secure link the application generates — invoice payment links, quote approval links, the address of your scheduled task — is signed with this. The installer generates one for you.

If it is not set, the application falls back to using the installation path, which is guessable on shared hosting. Set a real one. Once set, do not change it: changing it invalidates every link already sent to a customer.

### Other values you can set

<table><thead><tr><th>Constant</th><th>Default</th><th>What it does</th></tr></thead><tbody><tr><td><code>_REWRITE_LINKS</code></td><td><code>true</code></td><td>Tidy URLs. Set it to <code>false</code> if your server does not rewrite requests — see below.</td></tr><tr><td><code>_UCM_FILE_STORAGE_DIR</code></td><td>the installation folder</td><td>Where uploads, backups, generated PDFs and session files are written. An absolute path with a trailing slash. See below.</td></tr><tr><td><code>_UCM_FILE_STORAGE_HREF</code></td><td>the site address</td><td>The web address that corresponds to the folder above. Only needed if you change it.</td></tr><tr><td><code>_DEBUG_MODE</code></td><td><code>false</code></td><td>Shows PHP errors on screen and adds a timing panel at the foot of each page. Useful for diagnosing a problem; never leave it on for a live site.</td></tr><tr><td><code>_DEBUG_SQL</code></td><td><code>false</code></td><td>Adds every database query to that panel. Only has an effect when <code>_DEBUG_MODE</code> is also on.</td></tr><tr><td><code>_BLOCK_EMAILS</code></td><td><code>false</code></td><td>Stops all outgoing email. Set this on a copy of a live site so that testing cannot mail your real customers.</td></tr><tr><td><code>_CUSTOM_UCM_HOMEPAGE</code></td><td>not set</td><td>Send anyone arriving at the root of the site to this address instead of the dashboard.</td></tr><tr><td><code>_UCM_HIDE_UPGRADE</code></td><td>not set</td><td>Define it to hide and block the built-in upgrade screen. Useful if you manage updates yourself.</td></tr><tr><td><code>_UCM_HIDE_BACKUPS</code></td><td>not set</td><td>Define it to hide and block the backup screen.</td></tr></tbody></table>

:::note[Re-running setup overwrites this file]
If you ever run the setup wizard again, it rewrites `config.local.php` with just the database settings and the secret, discarding anything else you added. Keep a copy of your own lines if you have added any.
:::

### Setting values from the environment instead

If you deploy with a script, or to a platform where writing files is awkward, these environment variables are read before the file: `UCM_DB_SERVER`, `UCM_DB_NAME`, `UCM_DB_USER`, `UCM_DB_PASS`, `UCM_DB_PREFIX`, `UCM_SECRET`, and `UCM_CONFIG_LOCAL` to point the settings file somewhere else entirely. `UCM_DEBUG` turns error display on.

### Moving file storage outside the web root

By default everything uploaded is stored inside your web space, protected by `.htaccess` rules. If you would rather have it somewhere the web server cannot reach at all, set:

```
define( '_UCM_FILE_STORAGE_DIR', '/home/youraccount/ucm-files/' );
```

Create that folder and make it writable by PHP. The application creates the subfolders it needs. Move your existing files across before switching, or they will appear to vanish.

:::caution[One part of that folder does have to be reachable]
The combined stylesheet and script cache is written to `temp/` inside it and is served to browsers. If you move storage outside the web root you must also set `_UCM_FILE_STORAGE_HREF` to a web address that maps to it, or pages will load without their styling.
:::

## Tidy URLs

By default the application produces addresses like `/customer.customer_admin/` rather than `/?m=customer&p=customer_admin`. This relies on the web server sending every request that is not a real file to `index.php`, which the included `.htaccess` arranges on Apache.

On nginx, or on an Apache server without `mod_rewrite`, that does not happen and every link in the application gives a 404. There are two fixes:

-   **Preferred:** configure the rewrite in your server config. On nginx, route anything that is not an existing file or directory to `index.php`.
-   **Otherwise:** add `define( '_REWRITE_LINKS', false );` to `includes/config.local.php`. Links fall back to the query string form, which needs no rewriting at all. Everything works; the addresses are just uglier.

There is no setting for this in the admin area — it has to go in the file, because it is needed before any page is built.

## The Settings screens

Everything else is in the admin area under _Settings_. The main areas:

<table><thead><tr><th>Screen</th><th>What it covers</th></tr></thead><tbody><tr><td>System Settings</td><td>Company name and address, the site address, currency, date format, timezone, and the administrator email that system notices go to. Set this up first — it feeds invoices, quotes and every email template.</td></tr><tr><td>Email</td><td>Whether to use SMTP or the server’s own mail, SMTP credentials, encryption, and sending limits. There is a test sender on this screen.</td></tr><tr><td>Payment Methods</td><td>Which methods customers are offered and the credentials for each. See <a href="/running/payment-methods/">Payment methods</a>.</td></tr><tr><td>Users</td><td>Staff and customer accounts, and their passwords.</td></tr><tr><td>User Roles</td><td>Roles and per-feature permissions. Each plugin contributes its own permissions, so this list grows with what you have switched on.</td></tr><tr><td>Templates</td><td>The wording of every email the system sends, and the layouts used for invoices and quotes.</td></tr><tr><td>Theme</td><td>Which of the included themes to use, and a box for your own CSS. See <a href="/developers/customising/">Customising</a>.</td></tr><tr><td>Languages</td><td>Translating the interface. See below.</td></tr><tr><td>Cron</td><td>Shows the exact command and the web address for your scheduled task. See <a href="/running/scheduled-tasks/">Scheduled tasks</a>.</td></tr><tr><td>Backup</td><td>Database and file backups, if the <code>zip</code> extension is available.</td></tr><tr><td>Upgrade</td><td>Runs database upgrades after a file update, and checks for new versions.</td></tr></tbody></table>

## Currency, tax and dates

Currency and tax are per-installation defaults set under _System Settings_, with per-invoice overrides where that makes sense. If you invoice in more than one currency, add the extra currencies first — they appear as a choice on each invoice afterwards.

Date format is a display preference and changing it does not alter stored data, so it is safe to change at any time.

## Languages

The interface text runs through a translation layer, so it can be translated without editing any code. _Settings → Language_ lists the strings the application has seen and lets you supply replacements.

Translations you add are stored in the database. You can also drop PHP language files into `includes/plugin_language/custom/`, which is a separate mechanism from the `custom/` override folder described in [Customising](/developers/customising/) — do not confuse the two.

## Email sending

Two modes:

-   **The server’s own mail.** The default. Nothing to configure. Works on many hosts, and on others is silently dropped or goes to spam.
-   **SMTP.** Tick _Use SMTP_ and give a hostname, username, password and encryption type. Choose _TLS_ for most providers. This is more reliable and much better for deliverability, because the mail is then sent by a server that is allowed to send as your domain.

There is a sending rate limit on the same screen, expressed as a number per minute, hour or day. Set it to match whatever your provider allows, or large newsletter sends will be rejected partway through.

Always use the test sender after changing anything here. If the test arrives and real notifications do not, the problem is the template or the trigger, not the mail settings.
