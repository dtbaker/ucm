import { test, expect } from '@playwright/test';

/**
 * Walks the setup wizard from step 0 to step 5 against an empty database.
 *
 * Needs the throwaway install app, which is not part of `docker compose up`:
 *
 *   ./scripts/install-test.sh
 *   npx playwright test tests/e2e/install.spec.ts
 *
 * The script resets the ucm_install_test database and starts a fresh container on :8092
 * that keeps its own config.local.php, so none of this touches the dev app on :8090.
 * If that container is not running these tests skip rather than fail.
 */

const INSTALL_URL = process.env.UCM_INSTALL_BASE_URL || 'http://localhost:8092';

const DB = {
  host: 'db',
  prefix: 'ucm_',
  name: 'ucm_install_test',
  user: 'ucm',
  pass: 'ucm',
};

test.describe('Setup wizard', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async ({ request }) => {
    let reachable = false;
    try {
      reachable = (await request.get(INSTALL_URL + '/', { timeout: 3000 })).ok();
    } catch {
      reachable = false;
    }
    test.skip(!reachable, `No install-test app at ${INSTALL_URL}. Run ./scripts/install-test.sh first.`);
  });

  test('walks every step and lands on a working install', async ({ page }) => {
    const warnings = /\b(Warning|Notice|Deprecated|Fatal error|Parse error):/;

    // Step 0 checks the environment and redirects straight to step 1 when it is happy.
    await page.goto(INSTALL_URL + '/');
    await expect(page.locator('body')).toContainText('Step #1: Database Connection Details');
    await expect(page.locator('body')).not.toHaveText(warnings);

    // Step 1 - database details. These are prefilled from the container environment.
    await page.locator('input[name="db_host"]').fill(DB.host);
    await page.locator('input[name="db_prefix"]').fill(DB.prefix);
    await page.locator('input[name="db_name"]').fill(DB.name);
    await page.locator('input[name="db_user"]').fill(DB.user);
    await page.locator('input[name="db_pass"]').fill(DB.pass);
    await page.locator('input[name="next"]').click();

    // Step 2 - create the tables. Every plugin must report success.
    await expect(page.locator('body')).toContainText('Step #2: Database Installation', { timeout: 60000 });
    await expect(page.locator('body')).toContainText('Database Installation Success!');
    await expect(page.locator('.error_text')).toHaveCount(0);
    await expect(page.locator('body')).not.toHaveText(warnings);

    await page.getByRole('link', { name: /Continue to Step 3/i }).click();

    // Step 3 - logs the new install in automatically, then offers an optional update check.
    await expect(page.locator('body')).toContainText('Step #3: Initial system update');
    await expect(page.locator('body')).toContainText('Welcome Administrator');
    // There must be a way past this step without a purchase code.
    await page.getByRole('link', { name: /Skip this step/i }).click();

    // Step 4 - email settings.
    await expect(page.locator('body')).toContainText('Step #4: Email Configuration');
    await expect(page.locator('body')).not.toHaveText(warnings);
    await page.getByRole('link', { name: /Complete Setup/i }).click();

    // Step 5 - done.
    await expect(page.locator('body')).toContainText('Step #5: Complete');
    await expect(page.locator('body')).toContainText(/now installed/i);
  });

  test('the installed system loads the dashboard, customers and invoices', async ({ page }) => {
    // A new browser context, so log in to the install we just created.
    await page.goto(INSTALL_URL + '/');
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
    await expect(page.locator('body')).not.toContainText(/invalid username or password/i);

    await page.goto(INSTALL_URL + '/?m[]=customer&p[]=customer_admin');
    await expect(page.locator('body')).toContainText(/customer|client/i);

    await page.goto(INSTALL_URL + '/?m[]=invoice&p[]=invoice_admin');
    await expect(page.locator('body')).toContainText(/invoice/i);
  });
});
