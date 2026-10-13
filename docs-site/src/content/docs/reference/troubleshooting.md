---
title: 'Troubleshooting'
description: 'Start here before asking for help. Most problems are one of a small number of things, and this page covers them in the order they come up.'
sidebar:
  order: 1
offline: true
featured: true
---

## First: turn the error messages on

By default the application hides PHP errors, which is correct for a live site but means a broken page shows nothing at all. To see what is actually wrong, add this line to `includes/config.local.php`:

```
define( '_DEBUG_MODE', true );
```

Reload the page. Instead of a blank screen you get the error, with the file and line number. That is nearly always enough to tell you what to fix, and it is the single most useful thing you can do before reporting a problem.

:::danger[Turn it off again]
Debug mode shows error messages to everyone, including anyone who finds your site. Those messages can include file paths and query fragments. Remove the line as soon as you have what you need.
:::

## The site will not load at all

### A completely blank page

PHP hit a fatal error and the message is hidden. Turn on debug mode as above. If the page is still blank afterwards, PHP itself failed to start — look in your hosting control panel’s error log, which will have the reason.

### “PHP version of 8.4 or above is REQUIRED”

Exactly what it says. Change the PHP version for your account in your hosting control panel. If you have several domains on one account, make sure you changed it for the right one.

### “Connect failed” followed by a database message

The application cannot reach your database. In order of likelihood:

-   The details in `includes/config.local.php` are wrong. Check for a typo, a stray space inside the quotes, or a missing semicolon.
-   The file does not exist, so defaults are being used. This is what happens after an upgrade if you skipped the step that recreates it — see [Upgrading from 2.x](/install/upgrading/).
-   Your host uses a database server name other than `localhost`.
-   The database user does not have rights on that database.

The message after “Connect failed” comes from the database server itself and is worth reading: _Access denied_ means wrong credentials, _Unknown database_ means the name is wrong, and a connection timeout means the host is wrong.

### The setup wizard appears on a site that was already working

The application connected to a database but could not find its tables, so it concluded it was not installed yet.

:::danger[Do not click through the wizard]
It would build a second, empty installation. Your data is almost certainly fine — the application is simply pointed at the wrong place.

Check the database _name_ and the _table prefix_ in `includes/config.local.php` against what is actually in your database. A wrong prefix is the usual cause: the tables are right there but the application is looking for differently named ones.
:::

## Every link gives a 404

The home page loads but clicking anything gives a “not found” from the web server rather than from the application.

The server is not sending requests to `index.php`. Either `mod_rewrite` is off, the `.htaccess` file did not upload, your server is ignoring `.htaccess`, or you are on nginx.

The quick fix that always works: add this to `includes/config.local.php`.

```
define( '_REWRITE_LINKS', false );
```

Links fall back to a form that needs no rewriting. They are less tidy but entirely functional. The better fix is to get rewriting working — see [Configuration](/running/configuration/).

## Login problems

<table><thead><tr><th>Symptom</th><th>Cause and fix</th></tr></thead><tbody><tr><td>The login form reloads with no message</td><td>Sessions are not persisting. Check that <code>temp/</code> exists and is writable by PHP.</td></tr><tr><td>Logged out after every click</td><td>Same cause, or the site address in <em>System Settings</em> does not match the address you are actually using — for example it says <code>http</code> and you are on <code>https</code>, or it has <code>www</code> and you do not.</td></tr><tr><td>Correct password rejected</td><td>Check caps lock, then confirm you are using the email address rather than a name. If you are locked out entirely, set a new password hash directly in the <code>user</code> table using phpMyAdmin.</td></tr><tr><td>Password reset email never arrives</td><td>A mail problem, not a login problem. See below.</td></tr><tr><td>Logged in but the menu is nearly empty</td><td>That account’s role has few permissions. Check <em>Settings → User Roles</em> as an administrator.</td></tr></tbody></table>

## Email is not being sent

Work through these in order.

1.  ### Send a test message
    
    From _Settings → Email_. If the test arrives and real notifications do not, your mail settings are fine and the problem is the template or the trigger — skip to the last step.
    
2.  ### Check the spam folder
    
    Mail sent by a web server from a domain it is not authorised to send for is very often filed as spam. This is the most common cause by far.
    
3.  ### Switch to SMTP
    
    If you are using the server’s own mail function, many hosts block or silently drop it. Tick _Use SMTP_ and enter credentials for a real mailbox or a transactional email provider. Deliverability improves immediately because the mail is then being sent by a server allowed to send as your domain.
    
4.  ### Check the encryption setting
    
    TLS on port 587 suits most providers. SSL on port 465 is the older alternative. If you see a connection error, this and the port are almost always the cause. Sending over SSL or TLS needs the `openssl` PHP extension.
    
5.  ### Check the sending limit
    
    If mail stops partway through a large send, the rate limit on the same settings screen may be lower than your provider allows, or your provider is rejecting the rate. Adjust it to match.
    
6.  ### Check the scheduled task
    
    Some mail is queued rather than sent immediately. If the scheduled task is not running, that mail waits forever. See [Scheduled tasks](/running/scheduled-tasks/).
    

## PDFs

<table><thead><tr><th>Symptom</th><th>Cause and fix</th></tr></thead><tbody><tr><td>The PDF is empty or will not open</td><td>Usually a permissions problem on <code>temp/</code>, where the file is built. Make sure it is writable.</td></tr><tr><td>Images are missing from the PDF</td><td>Image addresses in your template must be reachable by the server itself. A relative path that works in a browser may not resolve when the PDF is generated. Use a full address.</td></tr><tr><td>The layout is wrong</td><td>The PDF engine supports a useful but limited subset of CSS. Modern layout features do not work; tables do. If you have customised the template, simplify the layout.</td></tr><tr><td>Non-Latin characters show as boxes</td><td>The default fonts do not cover every script. This needs a font that does, added to the PDF engine.</td></tr></tbody></table>

## Uploads

<table><thead><tr><th>Symptom</th><th>Cause and fix</th></tr></thead><tbody><tr><td>Upload fails with no message</td><td>The target folder is not writable. See the list in <a href="/install/requirements/">Requirements</a>.</td></tr><tr><td>Large files fail, small ones work</td><td>PHP’s own limits. Raise <code>upload_max_filesize</code> and <code>post_max_size</code>, which most control panels expose.</td></tr><tr><td>Uploads work but downloads give “not found”</td><td>The files are there but not being served. Check you have not moved file storage without setting the matching web address — see <a href="/running/configuration/">Configuration</a>.</td></tr></tbody></table>

## Appearance

<table><thead><tr><th>Symptom</th><th>Cause and fix</th></tr></thead><tbody><tr><td>The page loads with no styling at all</td><td>The combined stylesheet is not being served. Check <code>temp/</code> is writable, and that your server is not denying <code>.css</code> and <code>.js</code> files inside it.</td></tr><tr><td>A style change does not show</td><td>Reload bypassing the cache (<kbd>Ctrl</kbd>/<kbd>Cmd</kbd> + <kbd>Shift</kbd> + <kbd>R</kbd>). If that does not work, delete the files in <code>temp/</code>.</td></tr><tr><td>Mixed content warnings on HTTPS</td><td>The site address in <em>System Settings</em> still says <code>http</code>. Change it to <code>https</code>.</td></tr></tbody></table>

## Things that quietly never happen

These have no error message, because nothing failed — the work simply never started.

<table><thead><tr><th>Symptom</th><th>Cause</th></tr></thead><tbody><tr><td>Recurring invoices never go out</td><td>The scheduled task is not running. See <a href="/running/scheduled-tasks/">Scheduled tasks</a>.</td></tr><tr><td>Subscriptions never renew</td><td>Same.</td></tr><tr><td>Ticket replies are not collected from the mailbox</td><td>Same, or the <code>imap</code> PHP extension is missing.</td></tr><tr><td>A customer paid but the invoice is still unpaid</td><td>The gateway’s webhook is not reaching your site. See <a href="/running/payment-methods/">Payment methods</a>.</td></tr><tr><td>Backups never appear</td><td>The <code>zip</code> PHP extension is missing, or the backup folder is not writable.</td></tr></tbody></table>

## After an update

<table><thead><tr><th>Symptom</th><th>Cause and fix</th></tr></thead><tbody><tr><td>“Unknown column” or “table does not exist”</td><td>The database upgrade has not been run. Go to <em>Settings → Upgrade</em> and click <em>Run Manual Upgrades</em>.</td></tr><tr><td>Your customisations have vanished</td><td>They were edits to shipped files, which the update replaced. Restore them from your backup and move them into <code>custom/</code> this time — see <a href="/developers/customising/">Customising</a>.</td></tr><tr><td>One screen errors, the rest are fine</td><td>Usually a customised file that PHP 8.4 is stricter about. Turn on debug mode to see the exact line.</td></tr><tr><td>Emailed payment links no longer work</td><td>The installation secret changed. See <a href="/install/upgrading/">Upgrading from 2.x</a>. Re-send the affected invoices.</td></tr></tbody></table>

## Getting help

If none of the above resolves it, ask through the item’s comments page on CodeCanyon. Include these four things and you will usually get a usable answer first time:

1.  The **exact error message**, with debug mode on.
2.  Your **PHP version** and whether the server is Apache or nginx.
3.  **What you did** immediately before it happened.
4.  Whether this is a **new installation or an upgrade**, and from which version.

:::caution[Never post credentials]
Do not include database passwords, payment gateway keys, the contents of `config.local.php`, or the address of your scheduled task — that last one contains a password even though it does not look like one. Replace anything sensitive with `…` before posting.
:::
