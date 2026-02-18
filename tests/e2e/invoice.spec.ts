import { test, expect } from '@playwright/test';

/**
 * E2E tests for invoice creation.
 * Requires: logged-in admin, at least one customer in the system.
 */
test.describe('Invoice', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
    await page.waitForURL(url => !url.href.includes('login'));
  });

  test('invoice create page loads', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin_edit&invoice_id=new');
    await expect(page.locator('body')).toContainText(/invoice|new|create/i);
  });
});
