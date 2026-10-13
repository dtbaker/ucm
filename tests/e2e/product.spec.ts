import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, formAuthKey, login } from './seed';

/**
 * Products, their categories, and the stock figure the inventory feature exists to keep.
 *
 * A product feeds a quote, job or invoice line, so its price has to arrive on the line
 * correctly; and when inventory control is on, the remaining stock is a number a user
 * trusts. That number is recomputed as `inventory_level - everything used` on every save
 * of a product or an invoice - except on an invoice delete, which does the arithmetic by
 * hand instead. That is the one place it went wrong.
 */

function tag(): string {
  return 'ProdProbe' + Math.random().toString(36).slice(2, 8);
}

/** The product screen lives under the admin settings tabs. */
async function openProduct(page: Page, productId: string) {
  await page.goto('/?m[]=product&p[]=product_admin&product_id=' + productId);
  await page.waitForLoadState('load');
}

async function stock(page: Page, productId: string): Promise<number> {
  await openProduct(page, productId);

  return Number(await page.locator('input[name="inventory_level_current"]').first().inputValue());
}

/** Add one line to an invoice, the way the invoice form posts it. */
async function addLine(page: Page, invoiceId: string, line: Record<string, string>) {
  const key = await formAuthKey(page);
  const form: Record<string, string> = {
    _process: 'save_invoice',
    _redirect: '',
    invoice_id: invoiceId,
    form_auth_key: key,
  };
  for (const [field, value] of Object.entries(line)) {
    form['invoice_invoice_item[new][' + field + ']'] = value;
  }
  const response = await page.request.post('/?m[]=invoice', {
    form,
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'adding an invoice line should be accepted').toBeLessThan(400);
}

/** The money the invoice screen prints next to a label. Same helper as invoice-money. */
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

async function newProduct(page: Page, fields: Record<string, string>): Promise<string> {
  return createRecord(page, '/?m[]=product', {
    _process: 'save_product',
    product_id: 'new',
    currency_id: '1',
    default_task_type: '1',
    billable: '1',
    taxable: '1',
    ...fields,
  }, 'product_id');
}

/** Delete through the confirmation screen the app really shows. */
async function confirmDelete(page: Page) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.locator('input[name="butt_del"]').first().click(),
  ]);
  await expect(page.locator('body'), 'deleting should ask for confirmation first')
    .toContainText(/really delete/i);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.locator('input[name="butt_del"][value="Confirm Delete"], button[name="butt_del"]')
      .first().click(),
  ]);
}

test.describe('Products', () => {
  test('puts its price on an invoice line and multiplies it by the quantity', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    const productId = await newProduct(page, {
      name: run + ' widget',
      amount: '25.00',
      quantity: '1',
      unitname: 'box',
    });

    await openProduct(page, productId);
    await expect(page.locator('input[name="name"]').first()).toHaveValue(run + ' widget');
    await expect(page.locator('input[name="amount"]').first(), 'the price should come back')
      .toHaveValue('25');
    await expect(page.locator('input[name="unitname"]').first()).toHaveValue('box');

    const customerId = await aCustomer(page);
    const invoiceId = await createRecord(page, '/?m[]=invoice', {
      _process: 'save_invoice',
      invoice_id: 'new',
      name: run + ' invoice',
      customer_id: customerId,
      _redirect: '',
    }, 'invoice_id');

    await addLine(page, invoiceId, {
      description: run + ' widget',
      product_id: productId,
      manual_task_type: '1',
      hours: '4',
      hourly_rate: '25.00',
    });

    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await page.waitForLoadState('load');
    await expect(page.locator('body'), 'the line should be on the invoice')
      .toContainText(run + ' widget');
    expect(await amountLabelled(page, /^Sub:?$/), '4 boxes at 25 is 100')
      .toContain('100.00');
    expect(await amountLabelled(page, /^Total:?$/)).toContain('100.00');

    // --- clean up
    page.on('dialog', (d) => d.accept());
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await confirmDelete(page);

    await openProduct(page, productId);
    await confirmDelete(page);
    await page.goto('/?m[]=product&p[]=product_admin&search[general]=' + run);
    expect(await page.locator('body').innerText(), 'the deleted product should be gone')
      .not.toContain(run + ' widget');
  });

  test('returns every unit to stock when the invoice that used them is deleted', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    // inventory_level_current is what the form asks for; save_data() stores
    // inventory_level = that + whatever is already used, and the current figure is
    // recomputed from the two on every save.
    const productId = await newProduct(page, {
      name: run + ' stocked',
      amount: '10.00',
      inventory_control: '1',
      inventory_level_current: '10',
    });
    expect(await stock(page, productId), 'it starts with the stock it was given').toBe(10);

    const customerId = await aCustomer(page);
    const invoiceId = await createRecord(page, '/?m[]=invoice', {
      _process: 'save_invoice',
      invoice_id: 'new',
      name: run + ' invoice',
      customer_id: customerId,
      _redirect: '',
    }, 'invoice_id');

    await addLine(page, invoiceId, {
      description: run + ' first shipment',
      product_id: productId,
      manual_task_type: '1',
      hours: '3',
      hourly_rate: '10.00',
    });
    expect(await stock(page, productId), 'three sold leaves seven').toBe(7);

    // A second line for the same product - a back order, a correction, a second size.
    await addLine(page, invoiceId, {
      description: run + ' second shipment',
      product_id: productId,
      manual_task_type: '1',
      hours: '2',
      hourly_rate: '10.00',
    });
    expect(await stock(page, productId), 'two more leaves five').toBe(5);

    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await page.waitForLoadState('load');
    expect(await amountLabelled(page, /^Sub:?$/), 'five units at 10 is 50')
      .toContain('50.00');

    // --- delete the invoice: all five units should come back, not just the last line's
    page.on('dialog', (d) => d.accept());
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
    await confirmDelete(page);

    expect(await stock(page, productId),
      'deleting the invoice returns every unit it used, from both of its lines').toBe(10);

    await openProduct(page, productId);
    await confirmDelete(page);
  });

  test('keeps a category, assigns a product to it, and frees the product when it goes', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    const key = await formAuthKey(page);
    const response = await page.request.post('/?m[]=product', {
      form: {
        _process: 'save_product_category',
        product_category_id: 'new',
        product_category_name: run + ' category',
        form_auth_key: key,
      },
      maxRedirects: 0,
      failOnStatusCode: false,
    });
    const categoryId = new URL(response.headers()['location'] || '', 'http://localhost')
      .searchParams.get('product_category_id');
    expect(categoryId, 'saving a category should redirect to it').toBeTruthy();

    await page.goto('/?m[]=product&p[]=product_admin_category&product_category_id=' + categoryId);
    await page.waitForLoadState('load');
    await expect(page.locator('input[name="product_category_name"]').first())
      .toHaveValue(run + ' category');

    const productId = await newProduct(page, {
      name: run + ' filed',
      amount: '5.00',
      product_category_id: String(categoryId),
    });

    // The list joins the category in, so this proves the assignment, not just the field.
    await page.goto('/?m[]=product&p[]=product_admin&search[general]=' + run);
    await page.waitForLoadState('load');
    const row = await page.evaluate((needle) => {
      const found = Array.from(document.querySelectorAll('tr'))
        .find((r) => ((r as HTMLElement).innerText || '').includes(needle + ' filed'));

      return found
        ? Array.from((found as HTMLTableRowElement).cells)
          .map((c) => ((c as HTMLElement).innerText || '').replace(/\s+/g, ' ').trim())
        : null;
    }, run);
    expect(row, 'the product should be in the list').toBeTruthy();
    expect((row as string[]).join(' | '), 'under its category')
      .toContain(run + ' category');

    // --- deleting the category must free the product, not take it with it
    page.on('dialog', (d) => d.accept());
    await page.goto('/?m[]=product&p[]=product_admin_category&product_category_id=' + categoryId);
    await confirmDelete(page);

    await openProduct(page, productId);
    await expect(page.locator('input[name="name"]').first(),
      'the product should have survived its category').toHaveValue(run + ' filed');
    const category = await page.locator('select[name="product_category_id"]').first().inputValue();
    expect(['', '0'], 'and no longer belong to one').toContain(category);

    await openProduct(page, productId);
    await confirmDelete(page);
  });
});
