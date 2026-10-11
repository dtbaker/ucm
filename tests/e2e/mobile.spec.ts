import { test, expect, Page } from '@playwright/test';

/**
 * Pages must not scroll sideways on a phone.
 *
 * Wide tables on edit pages used to stretch the whole page, which put the last columns
 * out of reach. They scroll on their own now.
 */

const PHONE = { width: 375, height: 800 };

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(u => !u.href.includes('login') || u.href.includes('home'));
}

/** How far past the viewport the page can scroll. A few pixels of grid gutter is tolerated. */
async function horizontalOverflow(page: Page): Promise<number> {
  return page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
}

test.describe('On a phone', () => {
  test.beforeEach(async ({ page }) => {
    await page.setViewportSize(PHONE);
    await login(page);
  });

  for (const [label, url] of [
    ['the invoice edit page', '/?m[]=invoice&p[]=invoice_admin&invoice_id=1'],
    ['the quote edit page', '/?m[]=quote&p[]=quote_admin&quote_id=1'],
    ['the invoice list', '/?m[]=invoice&p[]=invoice_admin'],
    ['the customer list', '/?m[]=customer&p[]=customer_admin'],
    ['the dashboard', '/?m[]=home'],
  ]) {
    test(`${label} does not stretch sideways`, async ({ page }) => {
      await page.goto(url);
      expect(await horizontalOverflow(page)).toBeLessThanOrEqual(10);
    });
  }

  test('a wide table scrolls inside itself rather than stretching the page', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=1');
    const scrollable = await page.evaluate(() => {
      const tables = Array.from(document.querySelectorAll('.content_box_wheader > table.tableclass_rows'));
      const wide = tables.filter(t => t.scrollWidth > t.clientWidth);
      return { total: tables.length, wide: wide.length };
    });
    expect(scrollable.total).toBeGreaterThan(0);
    expect(scrollable.wide).toBeGreaterThan(0);
  });
});
