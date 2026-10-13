---
title: 'Installing'
description: 'Upload the files, create a database, open the site in a browser and answer the setup wizard. On a typical shared hosting account the whole thing takes about ten minutes, and most of that is waiting for the upload.'
sidebar:
  order: 2
offline: true
featured: true
---

:::note[Already running Ultimate Client Manager?]
This page is for a brand new installation. If you are updating an existing site, go to [Upgrading from 2.x](/install/upgrading/) instead — the steps are different and the order matters.
:::

## Before you start

Have these three things ready. Your hosting control panel provides all of them.

-   FTP, SFTP or file manager access to your web space.
-   An **empty** MySQL database, plus a username and password with full rights on it. Write down the database name, username, password, and the database host (usually `localhost`).
-   Confirmation that your account runs **PHP 8.4 or newer**. See [Requirements](/install/requirements/).

## The installation

1.  ### Upload the application
    
    Open the download and find the `Main Files` folder. Upload **the contents of that folder** — not the folder itself — to where you want the site to live.
    
    To run it at `https://example.com/`, upload into `public_html` (or `htdocs`, or `www`, depending on your host). To run it at `https://example.com/crm/`, create a `crm` folder inside that and upload into it. Both work equally well.
    
    When the upload finishes you should see `index.php`, `init.php`, `cron.php` and the `includes` folder sitting at the top level of the destination.
    
    :::note
    Uploading by FTP means transferring several thousand small files, which is slow. If your control panel has a file manager that can unzip an archive on the server, upload the zip and extract it there instead — it is usually several times faster. Remember to move the _contents_ of `Main Files` up to the right level afterwards.
    :::
    
2.  ### Check the folder permissions
    
    PHP needs to be able to write to `includes/` and `temp/`. On most hosting this is already true. If the next step complains, set those two folders to permission `755`, and only use `777` if `755` is not enough — `777` lets any account on the server write to them.
    
3.  ### Open the site in a browser
    
    Go to the address you uploaded to. Because there is no database yet, the application knows it is not installed and sends you to the setup wizard automatically.
    
    The wizard begins by checking the server: PHP version, the extensions it wants, and whether the folders it needs are writable. If everything passes, it moves straight on to the database screen and you will not see this check at all.
    
    If something fails you get a _Setup Error_ screen listing what it found, with two buttons: _Try Again_ and _Ignore Errors_. Fix anything about permissions or PHP version before continuing. A complaint about `includes/config.php` being writable, or about the IMAP extension, can safely be ignored — see [Requirements](/install/requirements/) for why.
    
4.  ### Enter your database details
    
    Five fields:
    
    <table><thead><tr><th>Field</th><th>What to enter</th></tr></thead><tbody><tr><td>Database Host</td><td>Almost always <code>localhost</code>. If your host gave you a different server name, use that. You can append a port as <code>hostname:3306</code>.</td></tr><tr><td>Database Prefix</td><td>Prepended to every table name. The default <code>ucm_</code> is fine. Change it only if you are sharing one database with another application.</td></tr><tr><td>Database Name</td><td>The empty database you created.</td></tr><tr><td>Database Username</td><td>The user with rights on it.</td></tr><tr><td>Database Password</td><td>That user’s password.</td></tr></tbody></table>
    
    Press continue. The wizard makes a real connection with what you typed, so if the details are wrong you are told immediately, with the error the database server gave.
    
    When the connection succeeds, the wizard writes your settings to `includes/config.local.php` and generates a random secret value for your installation, which is stored in the same file.
    
    :::caution[Keep includes/config.local.php]
    It holds your database password and the secret that signs every secure link the application generates. Back it up along with your database. If you lose the secret, links that were already emailed to customers stop validating. Never copy this file to another installation.
    :::
    
5.  ### Let it build the database
    
    The next screen creates the tables. It works through every plugin in turn, printing each name and whether it succeeded, which is why the list is long. Nothing is asked of you.
    
    At the end you get _Database Installation Success_ and a link to continue. If any plugin reports a failure you get a _Retry_ link instead — retrying is safe and usually clears a transient problem. A failure that persists is nearly always the database user lacking a privilege; see [Troubleshooting](/reference/troubleshooting/).
    
6.  ### Skip or enter your purchase code
    
    You are now logged in as the administrator automatically, and the site is already usable.
    
    This screen offers to check for updates, which needs the purchase code from your CodeCanyon downloads page. **This step is optional.** If you do not want to enter it now, click _Skip this step_ and carry on — nothing in the product is locked behind it. Entering the code does not activate anything or contact a licence server; it is stored so the update check knows which item you own.
    
    :::note
    The update check contacts the vendor’s server and sends your installation address and the IP of the computer you are using along with the version information. If you would rather that did not happen, skip this step. You can enter the code later under Settings, or never.
    :::
    
7.  ### Set up email
    
    This screen configures outgoing mail. You can leave it entirely alone and click through: with nothing set, the application uses the server’s own mail function, which works on a lot of hosts.
    
    If your host blocks that, or your mail ends up in spam folders, tick _Use SMTP_ and fill in the hostname, username, password and encryption type. Choose _TLS_ for most providers, including Google. There is a test message sender on the same screen — use it before you move on, because mail problems are much easier to diagnose here than later.
    
    These settings are stored in the database and can be changed at any time under _Settings → Email_.
    
8.  ### Finish
    
    The last screen confirms you are done and offers a link into the main settings area. Follow it, because there are two things to do immediately.
    

## Do these two things now

:::danger[1. Change the default passwords]
The installer creates five accounts so that the system has something to show you, and **all five have the password `password`**. Two of them are privileged. Until you change them, anyone who finds your site can log in as an administrator.

Go to _Settings → Users_, open each account and set a real password, or delete the ones you do not want. The accounts are:

<table><thead><tr><th>Email</th><th>Who it is</th></tr></thead><tbody><tr><td><code>admin@example.com</code></td><td>Administrator. Keep this one and change its password and address to yours.</td></tr><tr><td><code>staff@example.com</code></td><td>A sample staff member with an hourly rate set.</td></tr><tr><td><code>user@example.com</code></td><td>A sample non-staff user.</td></tr><tr><td><code>user1@example.com</code></td><td>A contact at the first sample customer.</td></tr><tr><td><code>user2@example.com</code></td><td>A contact at the second sample customer.</td></tr></tbody></table>

The administrator account is the first user created and is permanently treated as the super administrator, so do not delete it — change it to your own email address instead.
:::

:::caution[2. Set up the scheduled task]
Recurring invoices, subscription renewals, overdue reminders and mailbox collection only run when the scheduled task runs. Nothing warns you if it is missing; the features simply never fire. [Scheduled tasks](/running/scheduled-tasks/) walks through it.
:::

## Then set up your own details

The sample data is there to show you the shape of things. Work through these when you are ready:

-   **Settings → System Settings** — your company name, address, currency, date format and timezone. Do this first; it affects how everything else is displayed and what appears on invoices.
-   **Settings → Payments** — see [Payment methods](/running/payment-methods/).
-   **Delete the sample customers** — _Bobs Printing Service_ and _Richards Roof Repairs_. Delete the two sample contacts (`user1@example.com` and `user2@example.com`) at the same time, since they belong to those customers.
-   **Settings → Templates** — the wording of the emails your customers receive.

## Installing without the wizard

If you are deploying from a script, or to a platform where writing a config file is awkward, the database settings can come from environment variables instead and the wizard will not need to write anything. Set `UCM_DB_SERVER`, `UCM_DB_NAME`, `UCM_DB_USER`, `UCM_DB_PASS`, `UCM_DB_PREFIX` and `UCM_SECRET` before the first request.

:::caution[Set UCM_SECRET if you go this route]
It is the value every secure link in the application is signed with. If it is not set, the application falls back to using the installation path, which is guessable on shared hosting. Generate 64 random hexadecimal characters and set it once, then keep it — changing it later invalidates links that have already been sent out.
:::

You still visit the site in a browser to let the wizard build the tables. It will show the database screen pre-filled from the environment, and the settings file will not be written. More detail is in [Configuration](/running/configuration/).
