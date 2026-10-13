---
title: Backups
description: What the built-in backup does, what it does not do, and why you need a second copy somewhere else.
sidebar:
  order: 25
featured: true
help_keys:
  - backup-backup_settings
---

The backup system writes the database and the uploaded files into zip files, so there is
something to restore from when a server fails.

## Running one

_Settings → Backup_. You can run a backup now, or let
[the scheduled task](/running/scheduled-tasks/) run them on a schedule. Scheduled is the
one that matters: a backup you have to remember is a backup you will not have.

`backup_post_delay` pauses between stages, which keeps a large backup from being killed by
a shared host's resource limits.

## What it needs

The `zip` PHP extension. Without it backups do not run and the settings screen says so.
See [Requirements](/install/requirements/).

The backup folder must be writable by the web server.

## What a backup contains

- The database — every record.
- The uploaded files. See [Files](/using/files/).

A database-only backup restores the *list* of your files and none of the files. Make sure
both are included.

:::danger[A backup on the same server is not a backup]
The failure this protects against is the server being lost: a disk failure, an account
suspension, a compromise, a hosting company going under. A zip file sitting on that same
server is lost with it.

Download backups somewhere else, or have your host copy them off automatically. Then
restore one, once, onto a test installation — an untested backup is a hope, not a plan.
:::

## Restoring

Restoring is a manual job: a fresh installation of the same version, the database imported
from the backup, the files unpacked into place, and `includes/config.php` pointed at the
new database. [Installing](/install/installing/) covers the configuration side.

Restore into a *new* database rather than over a live one. If something is wrong with the
backup you want to find out next to a working system, not instead of one.
