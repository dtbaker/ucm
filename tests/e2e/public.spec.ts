import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';
import { login, aCustomer, anInvoice, aFile } from './seed';

/**
 * The pages a customer sees, loaded with no session at all.
 *
 * Everything public goes through ext.php, which hands off to a plugin's external_hook().
 * These are the least exercised pages in the app and the only ones a stranger can reach,
 * so this sweeps them with the parameters missing, with the parameters wrong, and - the
 * case that used to take nine of them down with a fatal error - with an array where a
 * string was expected.
 *
 * The real links, hash and all, are scraped out of the admin screens that publish them,
 * so the customer portal and the public invoice pages are loaded the way a customer
 * would load them.
 */

/** Every hook each plugin answers on ext.php, with the obvious ways to get them wrong. */
const HOOKS: Array<[string, string]> = [
  ['customer', 'public'],
  ['customer', 'public_signup_form'],
  ['customer', 'public_signup'],
  ['invoice', 'public'],
  ['invoice', 'public_print'],
  ['invoice', 'public_pay'],
  ['invoice', 'payment_complete'],
  ['invoice', 'receipt'],
  ['quote', 'public'],
  ['quote', 'public_print'],
  ['contract', 'public'],
  ['contract', 'public_print'],
  ['job', 'public'],
  ['job_discussion', 'public'],
  ['ticket', 'public'],
  ['ticket', 'public_new'],
  ['ticket', 'public_status'],
  ['ticket', 'attachment'],
  ['ticket', 'status'],
  ['file', 'download'],
  ['file', 'download_bucket'],
  ['file', 'view'],
  ['data', 'download'],
  ['faq', 'public'],
  ['faq', 'faq_list_json'],
  ['faq', 'ticket_list'],
  ['member', 'subscribe'],
  ['member', 'subscribe_form'],
  ['newsletter', 'i'],
  ['newsletter', 'l'],
  ['newsletter', 'vo'],
  ['newsletter', 'view_online'],
  ['newsletter', 'unsubscribe'],
  ['newsletter', 'doubleoptin'],
  ['signature', 'signature_job_task'],
  ['change_request', 'public'],
  ['change_request', 'popup'],
  ['change_request', 'script'],
  ['change_request', 'display_change'],
  ['backup', 'download'],
  ['calendar', 'public'],
  ['theme', 'css'],
];

/** Shapes to try for each hook. The array forms are the ones PHP 8 fatals on. */
const SHAPES = [
  '',
  'i=1&hash=nope',
  'i[]=1&hash=nope',
  'i=1&hash[]=nope',
  'e[]=someone@example.com&a[]=1&s[]=1',
];

/** Admin screens that publish real public links, for records that exist or are created. */
async function linkSources(page: Page): Promise<string[]> {
  return [
    '/?m[]=customer&p[]=customer_admin_edit&customer_id=' + await aCustomer(page),
    '/?m[]=invoice&p[]=invoice_admin&invoice_id=' + await anInvoice(page),
    '/?m[]=file&p[]=file_admin&file_id=' + await aFile(page),
  ];
}

test.describe('Public pages under E_ALL', () => {
  test('error reporting is on, so the rest of this suite means something', async ({ page }) => {
    await login(page);
    const body = await (await page.request.get('/?m[]=customer&p[]=customer_admin&customer_id[]=1')).text();
    expect(
      DIAGNOSTIC.test(body),
      'No PHP diagnostics came back at all. Run the app with UCM_DEBUG=1 (docker-compose.yml sets it).',
    ).toBe(true);
  });

  test('no public hook prints a PHP warning, notice, deprecation or error', async ({ browser }) => {
    // A context of its own, so there is no session cookie anywhere near this.
    const context = await browser.newContext();
    const problems: string[] = [];

    for (const [module, hook] of HOOKS) {
      for (const shape of SHAPES) {
        const target = `/ext.php?m=${module}&h=${hook}` + (shape ? `&${shape}` : '');
        const response = await context.request.get(target, { maxRedirects: 0, failOnStatusCode: false });

        if (response.status() >= 500) {
          problems.push(`${target}: HTTP ${response.status()}`);
          continue;
        }
        for (const line of describeDiagnostics(await response.text())) {
          problems.push(`${target}: ${line}`);
        }
      }
    }

    await context.close();
    expect(problems, `PHP diagnostics on ${problems.length} public request(s):\n` + problems.join('\n')).toEqual([]);
  });

  test('the real public links the admin screens hand out load cleanly', async ({ page, browser }) => {
    await login(page);

    // Collect the signed links out of the admin screens that publish them.
    const links = new Set<string>();
    for (const source of await linkSources(page)) {
      await page.goto(source);
      const html = await page.content();
      for (const match of html.matchAll(/external\/m\.[a-z_]+\/h\.[a-z_]+\/i\.[^/"'\s]+\/hash\.[a-f0-9]+/g)) {
        links.add('/' + match[0]);
      }
    }
    expect(links.size, 'the admin screens should publish at least one public link').toBeGreaterThan(0);

    const context = await browser.newContext();
    const problems: string[] = [];
    for (const link of links) {
      const response = await context.request.get(link, { maxRedirects: 0, failOnStatusCode: false });
      if (response.status() >= 500) {
        problems.push(`${link}: HTTP ${response.status()}`);
        continue;
      }
      for (const line of describeDiagnostics(await response.text())) {
        problems.push(`${link}: ${line}`);
      }
    }

    await context.close();
    expect(problems, `PHP diagnostics on ${problems.length} public link(s):\n` + problems.join('\n')).toEqual([]);
  });
});
