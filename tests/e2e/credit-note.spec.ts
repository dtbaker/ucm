import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, formAuthKey, login } from './seed';
import { describeDiagnostics } from './diagnostics';

/**
 * The credit note screen, includes/plugin_invoice/pages/invoice_admin_credit.php.
 *
 * Nothing in the suite reached this page before. It is only included for an invoice whose
 * credit_note_id is set, and no other test makes one, so it printed "Undefined variable
 * $discounts_allowed" for years with nothing to notice.
 *
 * The credit note is made here rather than borrowed from the database: getting one means
 * cancelling the invoice it comes from, which is not a thing to do to a record that was
 * already there.
 *
 * PHP diagnostics only reach the browser when the app runs with UCM_DEBUG=1
 * (docker-compose.yml sets it). smoke.spec.ts is the suite that checks error reporting
 * really is on, so this one does not repeat that.
 */

/** Post to the invoice module with a real form token, the way the browser would. */
async function saveInvoice(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);

  return page.request.post('/?m[]=invoice', {
    form: { _process: 'save_invoice', _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
}

/** A fresh invoice, cancelled, with a credit note generated against it. */
async function aCreditNote(page: Page): Promise<string> {
  const customerId = await aCustomer(page);
  const invoiceId = await createRecord(page, '/?m[]=invoice', {
    _process: 'save_invoice',
    invoice_id: 'new',
    name: 'Credit Note Test Invoice',
    customer_id: customerId,
    _redirect: '',
  }, 'invoice_id');

  // One task on it, so the credit note has a line to draw and a total to discount.
  await saveInvoice(page, {
    invoice_id: invoiceId,
    'invoice_invoice_item[new][description]': 'Credit note test task',
    'invoice_invoice_item[new][amount]': '100',
    'invoice_invoice_item[new][taxable]': '1',
    'invoice_invoice_item[new][manual_task_type]': '-1',
  });

  // Only a cancelled invoice offers the button. input_date() falls through to strtotime()
  // for an ISO date, so this does not depend on the date format the install is set to.
  await saveInvoice(page, {
    invoice_id: invoiceId,
    date_cancel: new Date().toISOString().slice(0, 10),
  });

  await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
  const generate = page.locator('input[name="butt_generate_credit"]');
  await expect(generate, 'a cancelled invoice should offer "Generate Credit Note"').toHaveCount(1);
  await generate.click();
  await page.waitForURL(/invoice_id=\d+/);

  const creditNoteId = new URL(page.url()).searchParams.get('invoice_id');
  expect(creditNoteId, 'generating a credit note should land on the new record').toBeTruthy();
  expect(creditNoteId, 'the credit note is a new invoice, not the cancelled one').not.toEqual(invoiceId);

  return String(creditNoteId);
}

test.describe('Credit note', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('opens without printing a PHP warning, notice, deprecation or error', async ({ page }) => {
    const creditNoteId = await aCreditNote(page);

    const target = '/?m[]=invoice&p[]=invoice_admin&invoice_id=' + creditNoteId;
    const response = await page.request.get(target);
    expect(response.status(), `${target} should load`).toBeLessThan(500);

    const problems = describeDiagnostics(await response.text());
    expect(problems, `PHP diagnostics on the credit note page:\n` + problems.join('\n')).toEqual([]);
  });

  test('shows the credit note layout, discount fields included', async ({ page }) => {
    const creditNoteId = await aCreditNote(page);
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + creditNoteId);

    await expect(page).toHaveTitle(/credit note/i);
    await expect(page.locator('input[name="_process"][value="save_invoice"]')).toHaveCount(1);
    await expect(page.locator('body')).toContainText('Credit note test task');

    // The discount block is what $discounts_allowed gates, and an undefined variable there
    // is falsey - so the fields quietly going missing is the other half of that bug.
    await expect(page.locator('input[name="discount_amount"]')).toHaveCount(1);
    await expect(page.locator('input[name="discount_description"]')).toHaveCount(1);
    await expect(page.locator('select[name="discount_type"]')).toHaveCount(1);
  });
});
