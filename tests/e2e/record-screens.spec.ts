import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';
import { login } from './seed';

/**
 * Every module's record screen, opened on a record that exists and on a new one.
 *
 * smoke.spec.ts sweeps the pages the plugins put in the menu, and sweeps record pages
 * with an id that does not exist. Neither is the screen people spend their day on: the
 * one with a real record loaded into a form.
 *
 * That gap is not academic. Four separate diagnostics were found by walking quote to job
 * to invoice by hand, every one of them on a record screen reached with real parameters,
 * and one of them was being captured into a hidden field and saved. A diagnostic printed
 * while a form is rendering is a data corruption waiting for somebody with
 * display_errors on, which is most shared hosting.
 *
 * A module with no records yet is skipped rather than failed - a fresh install has
 * almost none, and the "new" screen is checked either way.
 */

/** module, the page that holds the record, the id parameter, the page rows come from. */
const RECORDS: Array<{ module: string; page: string; key: string; list: string }> = [
  { module: 'invoice', page: 'invoice_admin', key: 'invoice_id', list: 'invoice_admin' },
  { module: 'quote', page: 'quote_admin', key: 'quote_id', list: 'quote_admin' },
  { module: 'contract', page: 'contract_admin', key: 'contract_id', list: 'contract_admin' },
  { module: 'customer', page: 'customer_admin_open', key: 'customer_id', list: 'customer_admin_list&customer_type_id=0' },
  { module: 'customer', page: 'customer_admin_edit', key: 'customer_id', list: 'customer_admin_list&customer_type_id=0' },
  { module: 'job', page: 'job_admin', key: 'job_id', list: 'job_admin' },
  { module: 'user', page: 'user_admin', key: 'user_id', list: 'user_admin' },
  { module: 'product', page: 'product_admin', key: 'product_id', list: 'product_admin' },
  { module: 'website', page: 'website_admin', key: 'website_id', list: 'website_admin' },
  { module: 'vendor', page: 'vendor_admin', key: 'vendor_id', list: 'vendor_admin' },
  { module: 'ticket', page: 'ticket_admin', key: 'ticket_id', list: 'ticket_admin' },
  { module: 'file', page: 'file_admin', key: 'file_id', list: 'file_admin' },
  { module: 'finance', page: 'finance', key: 'finance_id', list: 'finance' },
  { module: 'subscription', page: 'subscription_admin', key: 'subscription_id', list: 'subscription_admin' },
  { module: 'member', page: 'member_admin_list', key: 'member_id', list: 'member_admin_list' },
  { module: 'newsletter', page: 'newsletter_admin', key: 'newsletter_id', list: 'newsletter_admin' },
  { module: 'timer', page: 'timer_admin', key: 'timer_id', list: 'timer_admin' },
];

function url(module: string, page: string, query = '') {
  return `/?m[]=${module}&p[]=${page}` + (query ? `&${query}` : '');
}

/** The first real id the module's own list page links to, or null if it has no records. */
async function anExistingId(page: Page, entry: typeof RECORDS[number]): Promise<string | null> {
  const response = await page.request.get(url(entry.module, entry.list), { failOnStatusCode: false });
  const matches = (await response.text()).matchAll(new RegExp(entry.key + '=(\\d+)', 'g'));
  for (const match of matches) {
    if (match[1] !== '0') {
      return match[1];
    }
  }

  return null;
}

test.describe('Record screens under E_ALL', () => {
  test('error reporting is on, so the rest of this suite means something', async ({ page }) => {
    await login(page);
    const body = await (await page.request.get('/?m[]=customer&p[]=customer_admin&customer_id[]=1')).text();
    expect(
      DIAGNOSTIC.test(body),
      'No PHP diagnostics came back at all. Run the app with UCM_DEBUG=1 (docker-compose.yml sets it).',
    ).toBe(true);
  });

  test('no record screen prints a PHP warning, notice, deprecation or error', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const problems: string[] = [];
    const skipped: string[] = [];

    for (const entry of RECORDS) {
      for (const id of ['new', await anExistingId(page, entry)]) {
        if (id === null) {
          skipped.push(`${entry.module}/${entry.page}`);
          continue;
        }
        const target = url(entry.module, entry.page, `${entry.key}=${id}`);
        const response = await page.request.get(target, { failOnStatusCode: false });
        if (response.status() >= 500) {
          problems.push(`${target}: HTTP ${response.status()}`);
          continue;
        }
        for (const line of describeDiagnostics(await response.text())) {
          problems.push(`${target}: ${line}`);
        }
      }
    }

    expect(
      problems,
      `PHP diagnostics on ${problems.length} record screen(s):\n` + problems.join('\n') +
      (skipped.length ? `\n(no records yet, existing-record check skipped: ${skipped.join(', ')})` : ''),
    ).toEqual([]);
  });
});
