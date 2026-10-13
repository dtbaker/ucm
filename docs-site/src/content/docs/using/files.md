---
title: Files
description: Attaching files to records, sharing them with a customer, and where they are stored on disk.
sidebar:
  order: 14
help_keys:
  - file-file_admin_list
  - file-file_admin_edit
  - file-file_admin
  - file-file_admin_bucket
  - file-file_admin_email
  - file-file_customer_edit
  - file-file_edit
  - file-file_list
  - file-file_list_preview
---

Files can be attached to a customer, a job, a quote, an invoice or a ticket. A file
attached to a record is found by opening the record, which is the point — contracts and
specifications stop living in email.

## Uploading

Open the record and use the file area. Several files can be uploaded at once. Each one can
carry a description, which is worth filling in; a folder of `scan_0041.pdf` is not
searchable by anyone.

## Sharing with a customer

A file can be marked visible to the customer, which makes it downloadable in
[the portal](/using/customer-portal/). Files are private unless you share them.

You can also email a file directly from its record, which sends a link rather than an
attachment — so large files do not bounce.

## Buckets

A bucket is a named group of files that is not tied to one record. Use it for things that
apply to many customers: a standard terms document, a price list, a logo pack.

## Where files go on disk

Uploads are written to the application's own storage folder, not into the database. That
has two consequences worth knowing:

- **Back up the files as well as the database.** A database-only backup restores the list
  of files and none of the files. See [Backups](/using/backups/).
- **The folder has to be writable** by the web server. If uploads fail with no obvious
  error, that is the first thing to check — see
  [Troubleshooting](/reference/troubleshooting/).

## Upload limits

The largest file you can upload is set by PHP, not by this application:
`upload_max_filesize` and `post_max_size`. Most shared hosting defaults to a few megabytes.
[Configuration](/running/configuration/) covers where to change them.
