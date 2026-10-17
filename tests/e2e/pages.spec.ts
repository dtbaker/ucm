import { test, expect } from '@playwright/test';

/**
 * E2E tests for key page rendering.
 * Requires logged-in session - uses login before each test.
 */
test.describe('Page rendering', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
    await page.waitForURL(url => !url.href.includes('login') || url.href.includes('home'));
  });

  test('home/dashboard loads after login', async ({ page }) => {
    await expect(page.locator('.content, .main-content, #content, [class*="content"]').first()).toBeVisible({ timeout: 10000 });
  });

  test('can navigate to customer list', async ({ page }) => {
    await page.goto('/?m[]=customer&p[]=customer_admin');
    await expect(page.locator('body')).toContainText(/customer|client/i);
  });

  test('can navigate to invoice list', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin');
    await expect(page.locator('body')).toContainText(/invoice/i);
  });
});
