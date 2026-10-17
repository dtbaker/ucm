import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';

/**
 * Loads every page the plugins put in the menu and fails if PHP printed a warning,
 * notice, deprecation or fatal error.
 *
 * includes/config.php turns error reporting off unless debug mode is on, so this only
 * finds anything when the app runs with UCM_DEBUG=1. docker-compose.yml sets that.
 * If it is not set the suite would pass no matter what, so the first check below makes
 * sure diagnostics really are being printed before trusting the rest.
 *
 * The list comes from the "p" entries each plugin registers in $this->links[].
 */

/** module, page */
const PAGES: Array<[string, string]> = [
  ['api', 'api_settings'],
  ['backup', 'backup_settings'],
  ['calendar', 'calendar_settings'],
  ['company', 'company_settings'],
  ['config', 'config_admin'],
  ['contract', 'contract_admin'],
  ['customer', 'customer_admin'],
  ['customer', 'customer_settings'],
  ['data', 'admin_data'],
  ['email', 'email_admin'],
  ['email', 'email_settings'],
  ['faq', 'faq_settings'],
  ['faq', 'faq_ticket'],
  ['file', 'file_admin'],
  ['finance', 'finance'],
  ['finance', 'finance_settings'],
  ['group', 'group_settings'],
  ['help', 'help_settings'],
  ['invoice', 'currency'],
  ['invoice', 'invoice_admin'],
  ['invoice', 'invoice_settings'],
  ['job', 'job_admin'],
  ['language', 'language_settings'],
  ['map', 'map_admin'],
  ['map', 'map_settings'],
  ['member', 'member_admin_list'],
  ['newsletter', 'newsletter_admin'],
  ['newsletter', 'newsletter_member'],
  ['newsletter', 'newsletter_settings'],
  ['paymethod_banktransfer', 'bank_settings'],
  ['paymethod_check', 'check_settings'],
  ['paymethod_other', 'other_settings'],
  ['paymethod_paypal', 'paypal_settings'],
  ['paymethod_square', 'square_settings'],
  ['paymethod_stripe', 'stripe_settings'],
  ['pdf', 'pdf_settings'],
  ['product', 'product_admin'],
  ['quote', 'quote_admin'],
  ['security', 'login_history'],
  ['security', 'security_role'],
  ['statistic', 'statistic_job'],
  ['statistic', 'statistic_list'],
  ['statistic', 'statistic_settings'],
  ['statistic', 'statistic_staff'],
  ['statistic', 'statistic_subscription'],
  ['statistic', 'statistic_tax'],
  ['statistic', 'ticket_report_staff'],
  ['subscription', 'subscription_admin'],
  ['template', 'template'],
  ['theme', 'theme_settings'],
  ['ticket', 'ticket_admin'],
  ['timer', 'timer_admin'],
  ['user', 'contact_admin'],
  ['user', 'user_admin'],
  ['vendor', 'vendor_admin'],
  ['website', 'website_admin'],
];

/**
 * Record pages, with an id that does not exist. Loading a record that is gone is a
 * perfectly ordinary thing to happen - a stale bookmark, a deleted invoice - and it must
 * not start printing notices about missing fields.
 */
const MISSING_RECORD_KEYS: Array<[string, string, string]> = [
  ['invoice', 'invoice_admin', 'invoice_id'],
  ['quote', 'quote_admin', 'quote_id'],
  ['contract', 'contract_admin', 'contract_id'],
  ['customer', 'customer_admin', 'customer_id'],
  ['job', 'job_admin', 'job_id'],
  ['user', 'user_admin', 'user_id'],
  ['product', 'product_admin', 'product_id'],
  ['subscription', 'subscription_admin', 'subscription_id'],
  ['timer', 'timer_admin', 'timer_id'],
  ['website', 'website_admin', 'website_id'],
  ['vendor', 'vendor_admin', 'vendor_id'],
  ['member', 'member_admin_list', 'member_id'],
  ['finance', 'finance', 'finance_id'],
  ['newsletter', 'newsletter_admin', 'newsletter_id'],
  ['security', 'security_role', 'security_role_id'],
  ['faq', 'faq_settings', 'faq_id'],
];

/**
 * A fresh id each run, because several lookups cache their result for a minute and the
 * "not found" path would otherwise only be exercised the first time.
 */
function missingRecordPages(): Array<[string, string, string]> {
  const id = 900000 + Math.floor(Math.random() * 99999);
  return MISSING_RECORD_KEYS.map(([module, page, key]) => [module, page, `${key}=${id}`]);
}

function url(module: string, page: string, query = '') {
  return `/?m[]=${module}&p[]=${page}` + (query ? `&${query}` : '');
}

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(u => !u.href.includes('login') || u.href.includes('home'));
}

test.describe('Plugin pages under E_ALL', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('error reporting is on, so the rest of this suite means something', async ({ page }) => {
    // Without this the whole suite passes no matter how broken the app is, because
    // includes/config.php sets error_reporting to 0 outside debug mode.
    //
    // Passing customer_id as an array makes UCM put an array into string context, which
    // PHP always complains about. If this ever stops warning because that handling was
    // fixed, pick another request that still does - do not delete the check.
    const body = await (await page.request.get('/?m[]=customer&p[]=customer_admin&customer_id[]=1')).text();
    expect(
      DIAGNOSTIC.test(body),
      'No PHP diagnostics came back at all. Run the app with UCM_DEBUG=1 (docker-compose.yml sets it).',
    ).toBe(true);
  });

  async function sweep(page: Page, pages: Array<[string, string] | [string, string, string]>) {
    const problems: string[] = [];

    for (const [module, pageName, query] of pages) {
      const target = url(module, pageName, query);
      const response = await page.request.get(target);

      if (response.status() >= 500) {
        problems.push(`${target}: HTTP ${response.status()}`);
        continue;
      }
      for (const line of describeDiagnostics(await response.text())) {
        problems.push(`${target}: ${line}`);
      }
    }

    return problems;
  }

  test('no page prints a PHP warning, notice, deprecation or error', async ({ page }) => {
    const problems = await sweep(page, PAGES);
    expect(problems, `PHP diagnostics on ${problems.length} page(s):\n` + problems.join('\n')).toEqual([]);
  });

  test('opening a record that does not exist is handled quietly', async ({ page }) => {
    const problems = await sweep(page, missingRecordPages());
    expect(problems, `PHP diagnostics on ${problems.length} page(s):\n` + problems.join('\n')).toEqual([]);
  });
});
