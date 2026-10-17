import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, deleteRecord, formAuthKey, login } from './seed';

/**
 * The arithmetic an invoicing product exists to do.
 *
 * Nothing else in the suite checks a number. The pages render, the saves are not
 * refused, the PDF comes back - but an invoice that adds its lines up wrong, or a
 * payment that does not come off the balance, would pass all of it.
 *
 * Writes go through the module's own POST contract rather than the line-item UI, which
 * builds its rows in javascript; the assertions are all made against the rendered page,
 * so the thing being checked is still what a user would see.
 */

/** Post to the invoice module with a real form token, the way the browser would. */
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
    const cells = Array.from(document.querySelectorAll('td,th'));
    for (const cell of cells) {
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

async function openInvoice(page: Page, invoiceId: string) {
  await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
  await page.waitForLoadState('load');
}

/** Removes the invoices this spec raised, newest first. */
async function removeInvoices(page: Page, made: string[]) {
  for (const id of [...made].reverse()) {
    await deleteRecord(page, '/?m[]=invoice', 'save_invoice', 'invoice_id', id);
  }
}

test.describe('Invoice arithmetic', () => {
  test('adds its lines up, and takes a payment off the balance', async ({ page }) => {
    const made: string[] = [];
    try {
      test.setTimeout(120000);
      await login(page);

      const customerId = await aCustomer(page);
      const invoiceId = await createRecord(page, '/?m[]=invoice', {
        _process: 'save_invoice',
        invoice_id: 'new',
        name: 'Invoice arithmetic test',
        customer_id: customerId,
        _redirect: '',
      }, 'invoice_id');
      made.push(invoiceId);

      // Two lines, added one at a time the way the form posts them.
      for (const [description, amount] of [['Widget', '100'], ['Gadget', '50']]) {
        await saveInvoice(page, {
          invoice_id: invoiceId,
          'invoice_invoice_item[new][description]': description,
          'invoice_invoice_item[new][amount]': amount,
          'invoice_invoice_item[new][manual_task_type]': '-1',
        });
      }

      await openInvoice(page, invoiceId);
      await expect(page.locator('body')).toContainText('Widget');
      await expect(page.locator('body')).toContainText('Gadget');
      expect(
        await amountLabelled(page, /^Total:?$/),
        '100 and 50 should total 150',
      ).toContain('150');

      // Pay part of it.
      await saveInvoice(page, {
        invoice_id: invoiceId,
        // save_invoice() only takes a new payment when this says so.
        add_payment: 'go',
        'invoice_invoice_payment[new][amount]': '50',
        'invoice_invoice_payment[new][method]': 'Cheque',
        'invoice_invoice_payment[new][date_paid]': new Date().toISOString().slice(0, 10),
      });

      await openInvoice(page, invoiceId);
      expect(
        await amountLabelled(page, /^Paid:?$/),
        'the payment should be recorded against the invoice',
      ).toContain('50');
      expect(
        await amountLabelled(page, /^Due:?$/),
        'a 50 payment against a 150 invoice should leave 100 due',
      ).toContain('100');

      // And the rest.
      await saveInvoice(page, {
        invoice_id: invoiceId,
        add_payment: 'go',
        'invoice_invoice_payment[new][amount]': '100',
        'invoice_invoice_payment[new][method]': 'Cheque',
        'invoice_invoice_payment[new][date_paid]': new Date().toISOString().slice(0, 10),
      });

      await openInvoice(page, invoiceId);
      expect(
        await amountLabelled(page, /^Paid:?$/),
        'both payments should be recorded',
      ).toContain('150');
      const due = await amountLabelled(page, /^Due:?$/);
      expect(
        due === null || /^0(\.0+)?$/.test(due.replace(/[^0-9.]/g, '')),
        `a fully paid invoice should have nothing due, got ${due}`,
      ).toBeTruthy();
    } finally {
      await removeInvoices(page, made);
    }
  });
});
