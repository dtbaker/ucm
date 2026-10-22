/**
 * The demo's sample data and the helpers that turn it into forms. No site is needed:
 *
 *   node --test scripts/demo-seed/data.test.mjs
 */
import assert from 'node:assert/strict';
import { test } from 'node:test';
import * as data from './data.mjs';
import { check, dayOffset, invoiceTotals, phpOptions } from '../demo-seed.mjs';

test('the sample data refers only to things it contains', () => {
  assert.deepEqual(check(), []);
});

test('a broken reference is reported rather than posted', () => {
  const broken = { ...data, invoices: [{ ...data.invoices[0], customer: 'Nobody Ltd', paid: [[-1, 1.5, 'Cheque']] }] };
  const problems = check(broken);
  assert.ok(problems.some((p) => p.includes('Nobody Ltd')), problems.join('\n'));
  assert.ok(problems.some((p) => p.includes('paid 1.5 times over')), problems.join('\n'));
});

test('a completed job with work left is reported', () => {
  const jobs = data.jobs.map((job) => (job.status === 'Completed' ? { ...job, tasks: [{ description: 'x', hours: 1 }] } : job));
  assert.ok(check({ ...data, jobs }).some((p) => p.includes('completed with work left')));
});

test('days are counted from the day given, across a month end', () => {
  const from = new Date(2026, 0, 30);
  assert.equal(dayOffset(0, from), '2026-01-30');
  assert.equal(dayOffset(3, from), '2026-02-02');
  assert.equal(dayOffset(-30, from), '2025-12-31');
});

test('the data reads the same whatever day it is made', () => {
  // Nothing in it is a calendar date: every date is an offset from the day of the run.
  const source = JSON.stringify(data);
  assert.doesNotMatch(source, /20\d\d-\d\d-\d\d/);
});

test('an invoice totals its lines and adds the tax', () => {
  assert.deepEqual(invoiceTotals({ lines: [{ hours: 21, rate: 90 }] }, 10), { net: 1890, total: 2079 });
  assert.deepEqual(invoiceTotals({ lines: [{ amount: 480 }, { amount: 520 }] }, 10), { net: 1000, total: 1100 });
});

test('the options a note form carries are a PHP array, base64 encoded', () => {
  const decoded = Buffer.from(phpOptions({ owner_id: 3, owner_table: 'customer' }), 'base64').toString();
  assert.equal(decoded, 'a:2:{s:8:"owner_id";i:3;s:11:"owner_table";s:8:"customer";}');
});

test('every customer has the one contact the app turns into a primary contact', () => {
  for (const customer of data.customers) {
    assert.match(customer.contact.email, /@example\.com$/, customer.name);
    for (const more of customer.more || []) {
      assert.match(more.email, /@example\.com$/, customer.name);
    }
  }
  for (const vendor of data.vendors) {
    assert.match(vendor.contact.email, /@example\.com$/, vendor.name);
  }
});
