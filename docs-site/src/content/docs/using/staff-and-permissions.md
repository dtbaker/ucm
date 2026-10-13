---
title: Staff and permissions
description: Adding staff, what the roles control, and why the first user is different from everyone else.
sidebar:
  order: 22
featured: true
help_keys:
  - user-user_admin_list
  - user-user_admin_edit
  - user-user_admin
  - user-user_admin_edit_staff
  - user-user_admin_edit_login
  - user-user_admin_edit_company
  - user-user_profile
  - user-user_summary
---

Staff are people in your business who log in. They are a different thing from customer
contacts, who log into [the portal](/using/customer-portal/) and see only their own
records.

## Adding a staff member

_Settings → Users_, create the user, give them a login and assign a role. They get an
email with their details if mail is working.

## Roles

A role is a set of permissions. Permissions are per feature and per action — view, edit,
create, delete — so a role can be allowed to see invoices but not edit them, or to create
jobs but not delete anyone else's.

Build roles around what people do rather than around seniority. "Can raise an invoice but
not credit one" is a sentence that maps onto a permission; "senior" is not.

## The first user is special

The user created during installation is the super administrator, and some things are only
ever available to it. If you are locked out of a setting, check whether you are logged in
as that account.

Make sure it has a real email address on it and a password you have not reused. It is the
account that can change everything.

## Switching help on for everyone

A new installation shows the documentation to any staff member whose role allows it. If
you want it restricted to the super administrator, tick _Only show help menu for Super
Administrator_ under _Settings → Help_.

On installations upgraded from an earlier version that restriction is already on, because
it used to be the default. Untick it to let your staff read the documentation for the
screen they are on.

## When somebody leaves

Disable the login rather than deleting the user. Deleting removes the person who did the
work from the records of the work; disabling stops the login and keeps the history.
