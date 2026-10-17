import { test, expect, Page } from '@playwright/test';
import { createRecord, deleteRecord, formAuthKey, login } from './seed';

/**
 * Customer credit: overpay an invoice, put the difference on the customer's account,
 * spend it on the next invoice.
 *
 * This had no coverage and it loses money without it. add_credit() read the balance
 * through get_customer(), which caches, so a second credit assigned inside the cache
 * lifetime started from the balance as it was before the first one and overwrote it:
 * assign 50 then 30 and the account holds 30.
 *
 * A fresh customer is created rather than reused, because the whole test is about an
 * opening balance of zero.
 */

async function post(page: Page, module: string, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post(`/?m[]=${module}`, {
    form: { _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), `posting to ${module} should be accepted`).toBeLessThan(400);

  return response;
}

/** An invoice for `amount`, paid `paid`, so it is overpaid by the difference. */
async function overpaidInvoice(page: Page, customerId: string, amount: string, paid: string) {
  const invoiceId = await createRecord(page, '/?m[]=invoice', {
    _process: 'save_invoice',
    invoice_id: 'new',
    name: 'Credit test',
    customer_id: customerId,
    _redirect: '',
  }, 'invoice_id');

  await post(page, 'invoice', {
    _process: 'save_invoice',
    invoice_id: invoiceId,
    'invoice_invoice_item[new][description]': 'Item',
    'invoice_invoice_item[new][amount]': amount,
    'invoice_invoice_item[new][manual_task_type]': '-1',
  });
  await post(page, 'invoice', {
    _process: 'save_invoice',
    invoice_id: invoiceId,
    // save_invoice() only takes a new payment when this says so.
    add_payment: 'go',
    'invoice_invoice_payment[new][amount]': paid,
    'invoice_invoice_payment[new][method]': 'Cheque',
    'invoice_invoice_payment[new][date_paid]': new Date().toISOString().slice(0, 10),
  });

  return invoiceId;
}

/** What the customer's own screen says the account holds. */
async function creditOnScreen(page: Page, customerId: string): Promise<string> {
  await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
  await page.waitForLoadState('load');

  return page.locator('input[name="credit"]').first().inputValue();
}

/** The money the invoice screen prints next to a label, e.g. "Due:". */
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

test.describe('Customer credit', () => {
  test('two overpayments both land on the account, and the next invoice spends them', async ({ page }) => {
    let customerId: string | null = null;
    try {
      test.setTimeout(180000);
      await login(page);

      customerId = await createRecord(page, '/?m[]=customer', {
        _process: 'save_customer',
        customer_id: 'new',
        customer_name: `Credit Customer ${Date.now().toString(36)}`,
        name: 'Credit',
        email: `credit-${Date.now()}@example.com`,
      }, 'customer_id');

      expect(
        Number(await creditOnScreen(page, customerId)),
        'a new customer should start with no credit',
      ).toBe(0);

      // --- a 10 invoice paid 60, with the 50 assigned to the account
      const first = await overpaidInvoice(page, customerId, '10', '60');
      await post(page, 'invoice', { _process: 'assign_credit_to_customer', invoice_id: first });
      expect(
        Number(await creditOnScreen(page, customerId)),
        'the overpayment should be on the account',
      ).toBe(50);

      // --- and another, paid 40. This is the one that used to discard the first.
      const second = await overpaidInvoice(page, customerId, '10', '40');
      await post(page, 'invoice', { _process: 'assign_credit_to_customer', invoice_id: second });
      expect(
        Number(await creditOnScreen(page, customerId)),
        'a second overpayment should add to the account, not replace what was there',
      ).toBe(80);

      // --- spend it
      const third = await createRecord(page, '/?m[]=invoice', {
        _process: 'save_invoice',
        invoice_id: 'new',
        name: 'Credit spend test',
        customer_id: customerId,
        _redirect: '',
      }, 'invoice_id');
      await post(page, 'invoice', {
        _process: 'save_invoice',
        invoice_id: third,
        'invoice_invoice_item[new][description]': 'Big one',
        'invoice_invoice_item[new][amount]': '100',
        'invoice_invoice_item[new][manual_task_type]': '-1',
      });
      await post(page, 'invoice', {
        _process: 'save_invoice',
        invoice_id: third,
        apply_credit_from_customer: 'do',
      });

      await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + third);
      await page.waitForLoadState('load');
      expect(
        await amountLabelled(page, /^Due:?$/),
        '80 of credit against a 100 invoice should leave 20 due',
      ).toContain('20');
      expect(
        Number(await creditOnScreen(page, customerId)),
        'and the account should be empty again',
      ).toBe(0);
    } finally {
      // The customer this test made, and with it the three invoices, their payments and
      // their notes: delete_customer() takes the linked records with it, which is why this
      // deletes the customer rather than chasing each invoice.
      if (customerId) {
        await deleteRecord(
          page, '/?m[]=customer', 'save_customer', 'customer_id', customerId,
          'customer_id', { delete_others: '1' },
        );
      }
    }
  });
});
