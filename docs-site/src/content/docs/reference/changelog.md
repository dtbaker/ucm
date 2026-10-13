---
title: 'Changelog'
description: 'What changed in this release. Security fixes are marked, as are the changes that need you to do something.'
sidebar:
  order: 2
offline: true
---

## This release

A full modernisation. The application now runs on current PHP, a long list of security weaknesses has been closed, and features that depended on services which no longer exist have been removed.

:::danger[Read Upgrading from 2.x before updating an existing installation.]
This release needs PHP 8.4 and changes where your database settings are stored. Both will stop an old installation from loading if you skip the steps.
:::

### Platform

-   **PHP 8.4 is now the minimum**, up from 5.3. PHP 8.5 is also tested and supported.
-   The deprecated `mysql_*` database functions are gone entirely; everything uses MySQLi.
-   Deprecated and removed PHP functions have been replaced throughout, so pages no longer produce warnings on a modern PHP.
-   Automated tests now run on every change, covering the installer, the login flow, form security and the pages most likely to break.

### Security

Every item in this section is a security fix.

-   **Password verification bypass fixed.** A flaw let a stored password hash be used in place of the password itself. Anyone who had obtained a copy of the database could log in as any user without knowing their password.
-   **Form security checks now actually block.** The protection against cross-site request forgery existed but its result was discarded, so it stopped nothing. It now blocks, and the check has been applied across the save and delete actions throughout the product — customers, invoices, quotes, jobs, tickets, users, roles, templates, settings, payments and the rest.
-   **Automatic PDF library downloader removed.** A feature fetched a PHP library from a third-party site over an unencrypted connection and ran it, with no signature or checksum. Anyone able to intercept that connection could have run code of their choosing on your server.
-   **Untrusted data is no longer deserialised.** Several places took a value from the request and reconstructed PHP objects from it, which is a well known route to running arbitrary code.
-   **A page that could read any database table has been removed.**
-   **Building a class name with `eval()` has been removed.**
-   **Upload folders are no longer web accessible.** Files you upload, ticket attachments and the temporary folder are now blocked from being fetched directly. Previously anyone who guessed an address could download them, including generated invoices and session files.
-   **Uploads are checked before being saved**, and file actions now verify the user is allowed to perform them.
-   **Database errors are no longer shown on screen** outside debug mode. They could disclose query structure and file paths.
-   **Purchase codes are no longer sent to a third-party site.**
-   **Two scripts were being loaded from a content delivery network that no longer exists**, which meant anyone acquiring that address could have run code in your administrators’ browsers. Both have been removed.
-   **Logged-out background requests now return a proper error** rather than a login page, which stopped some actions failing silently.
-   **The installation secret can now be set from the environment**, so a scripted deployment no longer has to fall back to a guessable value.

### Fixes

-   The setup wizard could not complete on a fresh installation. Fixed.
-   Setup no longer requires a purchase code to finish. It is optional and can be skipped.
-   Setup no longer writes your database settings into a file that updates overwrite. It writes `includes/config.local.php` instead, which is left alone from now on.
-   Setup no longer blocks on an outbound connectivity test against a server that no longer exists.
-   The credit note screen was losing its discount fields. Fixed.
-   The member newsletter screen crashed. Fixed.
-   Opening a record that had been deleted produced an error instead of a message.
-   The email composition form warned about an undefined variable.
-   The custom data search failed on a particular combination of options.
-   Sorting failed in several places under PHP 8.
-   Login history was failing to resolve hostnames.
-   Generated PDFs are now written to disk rather than streamed, which fixes corrupted downloads on some hosts.
-   Wide tables no longer stretch the page on phones.
-   A broken logo image was being loaded from an external site into every printed and emailed invoice. Removed.

### Removed

-   **Facebook and Twitter integration.** Both services withdrew the APIs these used. Nothing has been able to work for years.
-   **The automatic mPDF installer.** See the security section above. PDF generation is unaffected and uses the library included in the download.
-   **reCAPTCHA v1.** Switched off by Google in 2018. Use reCAPTCHA v2.
-   **Payment gateways whose APIs have been discontinued** are no longer offered for new setup. Existing payment records are untouched — see [Payment methods](/running/payment-methods/).
-   Three redundant copies of a JavaScript library, which reduces the download size.

### Documentation and packaging

-   **This documentation.** The product previously shipped without any.
-   **A licensing folder** listing every third-party component included in the download, with the licence of each and its full text. See [Credits and licences](/reference/credits/).
-   The download is now organised into `Main Files`, `Documentation` and `Licensing`, and no longer includes development files that were never of any use to you.

## Things to do after updating

1.  Run _Settings → Upgrade → Run Manual Upgrades_.
2.  Check whether the original sample accounts are still present with their default passwords, under _Settings → Users_.
3.  If you used a payment gateway that has been retired, set up a current one and move any recurring billing across.
4.  If you had edited shipped files, move those changes into `custom/` so the next update leaves them alone. See [Customising](/developers/customising/).
5.  Confirm your scheduled task still runs, particularly if your PHP version changed. See [Scheduled tasks](/running/scheduled-tasks/).

## Earlier versions

Ultimate Client Manager was released and updated regularly through the 2.x series. Those changes were published on the item’s CodeCanyon page at the time and are not reproduced here — this changelog starts with the modernisation release above.
