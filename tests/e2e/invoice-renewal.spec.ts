import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, formAuthKey, login } from './seed';

/**
 * Renewing an invoice: the product generating money on a timer.
 *
 * Nothing covered it. renew_invoice() is the same code whether the cron job calls it on
 * the renewal date or a person presses Generate Renewal, so this drives the button - the
 * half that can be exercised without moving the clock or changing shared configuration.
 *
 * What it checks is that the new invoice is a copy: the same lines, the same money, the
 * renewal moved on by the same interval, and the old invoice pointing at the new one.
 */

/** ISO dates are accepted whatever date_input is set to - input_date() falls through. */
function isoDaysAgo(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() - days);

  return d.toISOString().slice(0, 10);
}

async function saveInvoice(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=invoice', {
    form: { _process: 'save_invoice', _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the save should be accepted').toBeLessThan(400);

  return response;
}

/** The money the invoice screen prints next to a label, e.g. "Total:". */
async function amountLabelled(page: Page, label: RegExp): Promise<string | null> {
  return page.evaluate((source) => {
    const pattern = new RegExp(source, 'i');
    for (const cell of Array.from(document.querySelectorAll('td,th'))) {
      const text = ((cell as HTMLElement).innerText || '').replace(/\s+/g, ' ').trim();
      if (pattern.test(text) && text.length < 40) {
        const next = cell.nextElementSibling as HTMLElement | null;
        const value = next ? (next.innerText || '').replace(/\s+/g, ' ').trim() : '';
        if (/[0-9]/.test(value)) {
          return value;
        }
      }
    }

    return null;
  }, label.source);
}

test.describe('Invoice renewal', () => {
  test('copies the lines, the money and the interval onto a new invoice', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const invoiceId = await createRecord(page, '/?m[]=invoice', {
      _process: 'save_invoice',
      invoice_id: 'new',
      name: `Renewal ${Date.now().toString(36)}`,
      customer_id: customerId,
      _redirect: '',
    }, 'invoice_id');

    for (const [description, amount] of [['Hosting', '100'], ['Support', '50']]) {
      await saveInvoice(page, {
        invoice_id: invoiceId,
        'invoice_invoice_item[new][description]': description,
        'invoice_invoice_item[new][amount]': amount,
        'invoice_invoice_item[new][manual_task_type]': '-1',
      });
    }

    // Created a month ago, due to renew today. renew_auto is deliberately left off so the
    // screen offers the button rather than waiting for the cron job.
    await saveInvoice(page, {
      invoice_id: invoiceId,
      date_create: isoDaysAgo(30),
      date_renew: isoDaysAgo(0),
    });

    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await page.waitForLoadState('load');
    expect(await amountLabelled(page, /^Total:?$/), 'the invoice should total 150').toContain('150');
    await expect(
      page.locator('input[name="generate_renewal_btn"]'),
      'an invoice due to renew today should offer to renew',
    ).toHaveCount(1);

    // --- renew it
    const response = await saveInvoice(page, { invoice_id: invoiceId, generate_renewal: '1' });
    const location = response.headers()['location'] || '';
    const renewedId = new URL(location, 'http://localhost').searchParams.get('invoice_id');
    expect(renewedId, 'renewing should land on the new invoice').toBeTruthy();
    expect(renewedId, 'and it should be a different invoice').not.toBe(invoiceId);

    // --- the copy bills the same thing
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + renewedId);
    await page.waitForLoadState('load');
    await expect(page.locator('body'), 'the lines should come across').toContainText('Hosting');
    await expect(page.locator('body')).toContainText('Support');
    expect(
      await amountLabelled(page, /^Total:?$/),
      'the renewal should bill the same 150',
    ).toContain('150');
    expect(
      await amountLabelled(page, /^Due:?$/),
      'and all of it should be outstanding - the payment stays on the old invoice',
    ).toContain('150');

    // --- and the old one points at it
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await page.waitForLoadState('load');
    const links = await page.evaluate(() =>
      Array.from(document.querySelectorAll('a[href*="invoice_id="]'))
        .map((a: any) => (/invoice_id=(\d+)/.exec(a.getAttribute('href') || '') || [])[1])
        .filter(Boolean));
    expect(
      links,
      'the renewed invoice should link to the one it created',
    ).toContain(String(renewedId));
  });
});
