import { test, expect, Page } from '@playwright/test';

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
  ['paymethod_authorize', 'authorize_settings'],
  ['paymethod_banktransfer', 'bank_settings'],
  ['paymethod_check', 'check_settings'],
  ['paymethod_coinbase', 'coinbase_settings'],
  ['paymethod_multisafepay', 'multisafepay_settings'],
  ['paymethod_other', 'other_settings'],
  ['paymethod_paynl', 'paynl_settings'],
  ['paymethod_paypal', 'paypal_settings'],
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

const LEVELS = 'Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error';
// PHP prints these as "<b>Warning</b>:  message in <b>/path</b> on line <b>12</b>" with
// html_errors on, and as plain text without it. Both always carry " on line ".
const DIAGNOSTIC = new RegExp(`<b>(?:${LEVELS})</b>:|(?:^|\\n)(?:${LEVELS}):\\s.*\\son line\\s`, 'm');

function url(module: string, page: string) {
  return `/?m[]=${module}&p[]=${page}`;
}

function describeDiagnostics(body: string): string[] {
  const found: string[] = [];
  const re = new RegExp(`(?:<b>)?(${LEVELS})(?:</b>)?:\\s*(.{0,200}?)(?:<br|\\n)`, 'g');
  let match;
  while ((match = re.exec(body)) !== null) {
    found.push(`${match[1]}: ${match[2].replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()}`);
  }
  return [...new Set(found)];
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

  test('no page prints a PHP warning, notice, deprecation or error', async ({ page }) => {
    const problems: string[] = [];

    for (const [module, pageName] of PAGES) {
      const response = await page.request.get(url(module, pageName));
      const body = await response.text();

      if (response.status() >= 500) {
        problems.push(`${module}/${pageName}: HTTP ${response.status()}`);
        continue;
      }
      if (DIAGNOSTIC.test(body)) {
        for (const line of describeDiagnostics(body)) {
          problems.push(`${module}/${pageName}: ${line}`);
        }
      }
    }

    expect(problems, `PHP diagnostics on ${problems.length} page(s):\n` + problems.join('\n')).toEqual([]);
  });
});
