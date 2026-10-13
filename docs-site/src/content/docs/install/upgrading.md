---
title: 'Upgrading from 2.x'
description: 'This is the largest update the product has had. It moves from PHP 5 to PHP 8.4, changes where your database settings live, and removes some features that no longer work. Read the whole page before you touch your live site.'
sidebar:
  order: 3
offline: true
---

:::danger[Two things will break your site if you skip them]
1.  **PHP 8.4.** This release refuses to run on anything older. Confirm your host offers 8.4 _before_ you upload.
2.  **Your database settings.** On an older installation they live inside `includes/config.php`, which this update overwrites. Copy them out first.

Both are covered step by step below. Neither takes more than a minute if you do it in the right order.
:::

## Before you begin

### Check your PHP version

In your hosting control panel, find the PHP version selector and confirm 8.4 or newer is available. If it is not, stop here and ask your host to make it available. Do not upgrade the files first and sort out PHP afterwards — that leaves your site down in the meantime.

If you are not sure which version you are on now, create a file called `phpcheck.php` containing `<?php echo PHP_VERSION;`, upload it, open it in a browser, and delete it afterwards.

### Take a backup you have actually tested

Back up both halves. A database dump without the files, or files without the database, will not restore a working site.

-   **The database.** Export it from phpMyAdmin or your control panel’s backup tool. Download the file to your own computer.
-   **The files.** Download the whole installation folder, or use your control panel to make an archive of it.

The application also has its own backup tool at _Settings → Upgrade_, with a _Create a Backup_ button. It is a reasonable extra, but take a real backup through your host as well — the built-in one needs the `zip` PHP extension and writes to a folder inside your web space, which is the same place you are about to overwrite.

:::caution[Try it somewhere else first if you can]
If your hosting allows a second site or a subdomain, restore your backup there and do the upgrade on that copy first. Ten minutes of setup buys you the ability to find out what breaks without your real site being down. This is worth doing if you have customised anything.
:::

### Copy your database settings out

This is the step people miss.

Open your current `includes/config.php` in a text editor. Near the top you will find five lines like this, with your real values in them:

```
define( '_DB_SERVER', 'localhost' );
define( '_DB_NAME',   'your_database_name' );
define( '_DB_USER',   'your_database_user' );
define( '_DB_PASS',   'your_database_password' );
define( '_DB_PREFIX', 'ucm_' );
```

Copy all five lines somewhere safe. You will paste them into a new file after the upload.

If you already have a file called `includes/config.local.php`, your settings are in there instead and you do not need to do anything — that file is not part of the update and will not be overwritten. Note down that it exists, though, so you know to leave it alone.

### Note anything you have customised

Make a list of files you have edited directly. The usual ones are invoice templates, the header or footer, and stylesheets. You will need to reapply those changes, and this is a good moment to move them into the `custom/` folder so that the next update leaves them alone. See [Customising](/developers/customising/).

If you already use a `custom/` folder, it is untouched by the update.

## The upgrade

1.  ### Put the site into maintenance, or pick a quiet time
    
    There is no maintenance mode built in. The practical approach is to do this when nobody is using the system. Between uploading the files and running the database upgrade the site will show errors, so you do not want customers in the middle of paying an invoice.
    
2.  ### Switch PHP to 8.4
    
    Do this now, before uploading. Your existing installation will probably break the moment you switch — that is expected and is why you are doing it immediately before the upload rather than days earlier.
    
3.  ### Upload the new files over the top
    
    Upload the contents of `Main Files` into your installation folder, replacing what is there. Do not delete the old installation first — your uploaded files, ticket attachments and backups live inside it.
    
    Specifically, these must survive the upload. If your FTP client offers “overwrite existing files” rather than “delete destination first”, they will:
    
    -   `includes/plugin_file/upload/` — everything attached to a record
    -   `includes/plugin_data/upload/`
    -   `includes/plugin_ticket/attachments/`
    -   `includes/plugin_email/attachments/`
    -   `includes/plugin_backup/backups/`
    -   `custom/` — if you have one
    -   `includes/config.local.php` — if you have one
4.  ### Restore your database settings
    
    If you already had an `includes/config.local.php`, skip this step.
    
    Otherwise create a new file called `includes/config.local.php` containing the five lines you copied earlier, with a PHP opening tag at the top:
    
    ```
    <?php
    define( '_DB_SERVER', 'localhost' );
    define( '_DB_NAME',   'your_database_name' );
    define( '_DB_USER',   'your_database_user' );
    define( '_DB_PASS',   'your_database_password' );
    define( '_DB_PREFIX', 'ucm_' );
    ```
    
    :::caution[All five lines, or none]
    The five database settings are treated as a set. If you define some but not others, the missing ones are never set at all and the application stops with an error. Include all five even if one of them is the default.
    :::
    
    Upload it into the `includes` folder. Do not put your settings back into `includes/config.php` — that file is part of the application and will be overwritten again by the next update. `config.local.php` is yours and is never touched.
    
5.  ### Open the site and log in
    
    Go to your site. You should see the login screen. Log in as you normally would.
    
    If you get a database connection error, the settings in `config.local.php` are wrong — check for a stray space or a missing quote. If you get the _setup wizard_ instead of a login screen, the application connected to a database but could not find your tables, which usually means the database name or the table prefix is wrong. Stop and fix it rather than letting the wizard run; it would start building a second, empty installation.
    
6.  ### Run the database upgrade
    
    Go to _Settings → Upgrade_ and click **Run Manual Upgrades**.
    
    This works through every plugin, compares the version recorded in your database against the version in the files you just uploaded, and applies whatever schema changes are needed. It prints each plugin as it goes. You do not need a purchase code for this — the code is only for the separate _Check for Upgrades_ button, which downloads new versions.
    
    If it stops partway, you can click the same button again safely. It picks up from the plugins that have not been updated yet.
    
7.  ### Check the site over
    
    Walk through the things you use most. Open a customer, open an invoice, generate a PDF, send yourself a test email from _Settings → Email_. See [What to check afterwards](#afterwards) below for the full list.
    

## What changed that affects you

### Passwords

Stored passwords were previously MD5 hashes. This release uses modern password hashing. You do not need to do anything and nobody needs to reset anything: existing passwords keep working, and each account is quietly upgraded to the stronger hash the next time that person logs in successfully.

:::caution[If your installation still has the original sample accounts]
Older versions shipped with accounts whose password was literally `password` — `admin@example.com`, `staff@example.com` and three others. If you never changed or deleted them, they are still there and still work. Go to _Settings → Users_ and deal with them now. This is the single most common way an installation of this product gets broken into.
:::

### Where your settings live

The installer used to write your database details into `includes/config.php`. It now writes a separate `includes/config.local.php`, which is never shipped and never overwritten. This is why step 4 above exists, and it means future updates are a straight file copy with nothing to preserve by hand.

Everything else — company details, email settings, templates, permissions — was already in the database and is unaffected.

### Features that have been removed

<table><thead><tr><th>Removed</th><th>Why, and what happens to your data</th></tr></thead><tbody><tr><td>Facebook and Twitter integration</td><td>Both services removed the APIs these used years ago, so the plugins could not do anything. No data is affected.</td></tr><tr><td>The automatic mPDF installer</td><td>A feature that downloaded a PDF library from a third party over an unencrypted connection and ran it. It was a serious security hole and has been removed. PDF generation uses the library already included in the download and is unaffected.</td></tr><tr><td>reCAPTCHA v1</td><td>Switched off by Google in 2018. If your installation had it configured, move to reCAPTCHA v2 under <em>Settings</em>.</td></tr><tr><td>Some payment gateways</td><td>Gateways whose APIs have been discontinued are no longer offered for new setup. <strong>Your payment history is kept.</strong> See <a href="/running/payment-methods/">Payment methods</a> for which ones, and what to do if you were using one.</td></tr></tbody></table>

:::tip[Historical records are never deleted]
Removing a payment method does not remove payments taken with it. Invoices stay paid, the payment stays on the record, and the method it was taken by still shows on screen and in reports. What stops is the ability to take a _new_ payment that way.
:::

### Secure links in emails already sent

The application signs the links it emails to customers — invoice payment links, quote approval links and similar — using a secret value for your installation.

Older installations did not have a real secret and fell back to using the installation path. If you follow step 4 above and create `config.local.php` with only the five database lines, that fallback continues and links already in your customers’ inboxes keep working.

That fallback is guessable, so setting a proper secret is better. To do it, add one more line to `includes/config.local.php` with 64 random hexadecimal characters of your own:

```
define( '_UCM_SECRET', '8f2c...put 64 random hex characters here...' );
```

:::caution[Changing the secret invalidates links already sent]
Any payment or approval link a customer already has will stop validating. If you are going to do it, do it as part of this upgrade rather than later, and be ready to re-send anything outstanding. Once set, never change it again.
:::

### PHP 8.4 and your own customisations

If you have written your own plugin, or edited core files, PHP 8.4 is stricter than PHP 5 about several things that used to be warnings. The most common ones to fix are passing `null` where a string is expected, accessing an array key that does not exist, and functions removed from PHP since version 7. If a page you customised now shows an error, that is almost always the cause. [Troubleshooting](/reference/troubleshooting/) explains how to turn on error display long enough to see it.

## What to check afterwards

-   **Log in.** Then log in as a non-administrator staff member too, and confirm the menus are what that person should see.
-   **Open an invoice and generate a PDF.** This exercises the PDF library and your invoice template at the same time.
-   **Send a test email** from _Settings → Email_.
-   **Check the scheduled task still runs.** If you moved hosting or changed the PHP version, the path to PHP in your cron entry may have changed. See [Scheduled tasks](/running/scheduled-tasks/).
-   **Take a test payment** if you accept them online, using your gateway’s test mode. See [Payment methods](/running/payment-methods/).
-   **Open the customer portal** as one of your contacts.
-   **Look at a record with a file attached** and download it, to confirm the upload folders came through the upload intact.
-   **Change the sample account passwords** if you have not already.

## If it goes wrong

Restoring is the reverse of the upgrade and takes a few minutes:

1.  Switch PHP back to the version you were on.
2.  Delete the installation folder’s contents and upload your file backup in its place.
3.  Import your database dump over the top of the database.

Both halves have to go back together. Restoring the files without the database leaves old code against an upgraded schema, which fails in confusing ways.

Before you roll back, it is worth checking [Troubleshooting](/reference/troubleshooting/) — a white screen after an upgrade is usually one missing step rather than a failed upgrade, and the page tells you how to find out which.
