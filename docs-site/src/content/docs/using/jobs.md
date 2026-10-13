---
title: Jobs and tasks
description: Break work into tasks, assign them to staff, track time against them, and invoice what was done.
sidebar:
  order: 6
featured: true
help_keys:
  - job-job_admin_list
  - job-job_admin_create
  - job-job_admin_edit
  - job-job_admin
  - job-job_admin_task_edit
  - job-job_admin_email
  - job-job_admin_email_staff
  - job-job_public
---

A job is a piece of work for a customer, broken into tasks. It is where time tracking,
staff assignment and the eventual invoice all meet.

## Creating a job

_Jobs → Create Job_, pick the customer, and add tasks. A job created from an approved
quote starts with the quote's lines already as tasks.

Each task has:

- **A description** — what is to be done. This is what appears on the invoice, so write it
  for the customer rather than for yourself.
- **An assigned staff member**, optionally.
- **An amount** — either a fixed price or an hourly rate with an estimate.
- **A completion tick.**

## Assigning work

Assigning a task to a staff member can email them automatically. That is controlled by
`job_send_staff_task_email_automatically`; with it off, you assign the task and tell them
yourself.

Staff see their assigned tasks on [the dashboard](/using/dashboard/).

## Tracking time

Start a timer against a task and the recorded time attaches to it, ready to be billed.
See [Timers](/using/timers/).

## The discussion thread

Each job has its own thread, so questions about the work stay with the work rather than in
somebody's inbox. Customers can see the thread if you share the job with them.

## Change requests

If a customer asks for something outside what was agreed, raise it as a **change request**
rather than quietly adding a task. A change request is priced and approved separately, so
the original job total stays as the record of what was quoted.

## Invoicing the job

A job has a **Create Invoice** button. It brings across the tasks, their amounts and any
tracked time. Completed tasks are included; it is worth ticking off what is done before
generating the invoice. See [Invoices](/using/invoices/).
