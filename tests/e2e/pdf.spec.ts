import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord } from './seed';

/**
 * Regression tests for PDF generation.
 *
 * convert_html2pdf() used to hand the PDF straight to the browser instead of writing the
 * file its callers expect. The visible symptom was the "Email invoice" screen returning a
 * PDF download instead of the email form, and invoice emails going out with no attachment.
 */

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(url => !url.href.includes('login') || url.href.includes('home'));
}

test.describe('PDF generation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('the invoice print route returns a PDF', async ({ page }) => {
    // Its own invoice rather than invoice 1. The print route answers an id it cannot find
    // with 'invalid invoice id' as an HTML page, so once invoice 1 was cleared out of the
    // development database this failed on the content type with nothing wrong in the PDF.
    const invoiceId = await createRecord(page, '/?m[]=invoice', {
      _process: 'save_invoice',
      invoice_id: 'new',
      name: 'PDF print test',
      customer_id: await aCustomer(page),
      _redirect: '',
    }, 'invoice_id');
    try {
      // page.request shares the logged-in session cookies.
      const response = await page.request.get(`/?m[]=invoice&p[]=invoice_admin&print=true&go=true&invoice_id=${invoiceId}`);
      expect(response.status()).toBe(200);
      expect(response.headers()['content-type']).toContain('application/pdf');
      const body = await response.body();
      expect(body.subarray(0, 4).toString()).toBe('%PDF');
      expect(body.length).toBeGreaterThan(1000);
    } finally {
      await deleteRecord(page, '/?m[]=invoice', 'save_invoice', 'invoice_id', invoiceId);
    }
  });

  for (const [label, url] of [
    ['invoice', '/?m[]=invoice&p[]=invoice_admin&invoice_id=1&email=true'],
    ['quote', '/?m[]=quote&p[]=quote_admin&quote_id=1&email=true'],
    ['contract', '/?m[]=contract&p[]=contract_admin&contract_id=1&email=true'],
  ]) {
    test(`the ${label} email screen shows the email form, not a PDF`, async ({ page }) => {
      const response = await page.goto(url);
      expect(response?.headers()['content-type']).toContain('text/html');
      await expect(page.locator('input[name="_process"][value="send_email"]')).toHaveCount(1);
      await expect(page.locator('textarea[name="html_content"]')).toHaveCount(1);
    });
  }
});
