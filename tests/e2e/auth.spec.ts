import { test, expect, Page } from '@playwright/test';

/**
 * How the app answers a logged-out request.
 *
 * A page load should still show the login screen. Anything the browser fetches in the
 * background should get a 401 instead - it used to get the whole login page with a 200,
 * which is how the dashboard got stuck on "Loading Alerts...".
 */

const DASHBOARD_ALERTS = '/?m=dashboard&_process=ajax_dashboard_tabs';

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(u => !u.href.includes('login') || u.href.includes('home'));
}

test.describe('Logged out requests', () => {
  test('a background request gets a 401, not a login page', async ({ request }) => {
    for (const headers of [
      { 'X-Requested-With': 'XMLHttpRequest' },
      { 'Sec-Fetch-Dest': 'script' },
      { 'Sec-Fetch-Dest': 'empty' },
    ]) {
      const response = await request.get(DASHBOARD_ALERTS, { headers });
      expect(response.status(), `headers: ${JSON.stringify(headers)}`).toBe(401);
      expect(response.headers()['content-type']).toContain('application/json');
      expect(await response.json()).toMatchObject({ error: 'not_logged_in' });
    }
  });

  test('ajax.php gets a 401 rather than an empty success', async ({ request }) => {
    const response = await request.post('/ajax.php', { form: { autocomplete: '1' } });
    expect(response.status()).toBe(401);
    expect(await response.json()).toMatchObject({ error: 'not_logged_in' });
  });

  test('opening a page in the browser still shows the login screen', async ({ page }) => {
    const response = await page.goto('/?m[]=customer&p[]=customer_admin');
    expect(response?.status()).toBe(200);
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });
});

test.describe('The working storage folder', () => {
  test('does not hand out generated documents or session files', async ({ page, request }) => {
    // Generate an invoice PDF, which lands in temp/ under an easy to guess name.
    await login(page);
    const pdf = await page.request.get('/?m[]=invoice&p[]=invoice_admin&print=true&go=true&invoice_id=1');
    expect(pdf.status()).toBe(200);

    // Now ask for it straight off disk, with no session at all.
    for (const path of ['/temp/Invoice_1.pdf', '/temp/Invoice_1.html']) {
      const response = await request.get(path);
      expect(response.status(), `${path} should not be downloadable`).toBe(403);
    }
  });

  test('still serves the stylesheet and script cache', async ({ page }) => {
    await login(page);
    await page.goto('/?m[]=home');
    const cached = await page.evaluate(() =>
      Array.from(document.querySelectorAll('link[rel="stylesheet"], script[src]'))
        .map(el => (el as HTMLLinkElement).href || (el as HTMLScriptElement).src)
        .filter(src => src.includes('/temp/')));
    expect(cached.length, 'expected the combined css/js cache to be in use').toBeGreaterThan(0);
    for (const url of cached) {
      const response = await page.request.get(url);
      expect(response.status(), `${url} must still load`).toBe(200);
    }
  });
});

test.describe('Logged in', () => {
  test('the dashboard finishes loading its alerts', async ({ page }) => {
    await login(page);
    await page.goto('/?m[]=home');
    // The alerts arrive as a script that hides this placeholder when it is done.
    await expect(page.locator('#tabs_loading')).toBeHidden({ timeout: 15000 });
  });

  test('the dashboard alerts endpoint returns javascript', async ({ page }) => {
    await login(page);
    const response = await page.request.get(DASHBOARD_ALERTS);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('javascript');
  });
});
