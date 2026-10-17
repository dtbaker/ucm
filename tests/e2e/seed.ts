import { expect, Page } from '@playwright/test';

/**
 * Logging in, and finding or creating the records the browser suites need.
 *
 * A freshly installed copy has no customers, invoices or files at all, so the tests that
 * need an existing record make one. An existing record is always preferred, so on a
 * development database with real data in it nothing new is created.
 */

export async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(url => !url.href.includes('login') || url.href.includes('home'));
}

/** Every page publishes the live token as ucm.form_auth_key. */
export async function formAuthKey(page: Page): Promise<string> {
  await page.goto('/?m[]=customer&p[]=customer_admin');
  const key = await page.evaluate(() => (window as any).ucm?.form_auth_key);
  expect(key).toBeTruthy();
  return key;
}

/**
 * A fresh install has no customers, invoices or files, so the handful of tests that need
 * an existing record find one or make one. An existing record is always preferred, so on
 * a development database with real data in it nothing new is created.
 */
export async function existingId(page: Page, listUrl: string, param: string): Promise<string | null> {
  await page.goto(listUrl);
  const match = (await page.content()).match(new RegExp(param + '=([1-9]\\d*)'));
  return match ? match[1] : null;
}

/** Post a form with a real token and return the id the app redirects to. */
export async function createRecord(page: Page, url: string, body: Record<string, string>, idParam: string): Promise<string> {
  const key = await formAuthKey(page);
  const response = await page.request.post(url, {
    form: { ...body, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  const location = response.headers()['location'] || '';
  const id = new URL(location, 'http://localhost').searchParams.get(idParam);
  expect(id, `creating a record at ${url} should redirect to it`).toBeTruthy();
  return String(id);
}

export async function aCustomer(page: Page): Promise<string> {
  // customer_admin is the filter page; customer_admin_list is where the rows are.
  const existing = await existingId(page, '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0', 'customer_id');
  if (existing) {
    return existing;
  }
  return createRecord(page, '/?m[]=customer', {
    _process: 'save_customer',
    customer_id: 'new',
    customer_name: 'CSRF Seed Customer',
    name: 'CSRF Seed Contact',
    email: 'csrf-seed@example.com',
  }, 'customer_id');
}

/**
 * The address popup only opens for an address that already exists, and a new customer has
 * none until something is typed into the address block on its form.
 */
export async function anAddress(page: Page): Promise<string | null> {
  const customerId = await aCustomer(page);
  const existing = await addressIdOn(page, customerId);
  if (existing) {
    return existing;
  }

  await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
  const line1 = page.locator('input[name$="[line_1]"]').first();
  if (!(await line1.count())) {
    return null;
  }
  const field = await line1.getAttribute('name');
  const name = await page.locator('[name="customer_name"]').first().inputValue();

  const key = await formAuthKey(page);
  await page.request.post('/?m[]=customer', {
    form: {
      _process: 'save_customer',
      customer_id: customerId,
      customer_name: name,
      [String(field)]: '1 CSRF Street',
      form_auth_key: key,
    },
    maxRedirects: 0,
    failOnStatusCode: false,
  });

  return addressIdOn(page, customerId);
}

/** The customer form carries the address row's id in a hidden field. */
export async function addressIdOn(page: Page, customerId: string): Promise<string | null> {
  await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
  const field = page.locator('input[name$="[address_id]"]').first();
  if (!(await field.count())) {
    return null;
  }
  const value = await field.inputValue();

  return /^[1-9]\d*$/.test(value) ? value : null;
}

export async function anInvoice(page: Page): Promise<string> {
  const existing = await existingId(page, '/?m[]=invoice&p[]=invoice_admin', 'invoice_id');
  if (existing) {
    return existing;
  }
  const customerId = await aCustomer(page);
  return createRecord(page, '/?m[]=invoice', {
    _process: 'save_invoice',
    invoice_id: 'new',
    name: 'CSRF Seed Invoice',
    customer_id: customerId,
    _redirect: '',
  }, 'invoice_id');
}

export async function aFile(page: Page): Promise<string> {
  const existing = await existingId(page, '/?m[]=file&p[]=file_admin', 'file_id');
  if (existing) {
    return existing;
  }
  return createRecord(page, '/?m[]=file', {
    _process: 'save_file',
    file_id: 'new',
    file_name: 'CSRF Seed File',
  }, 'file_id');
}

/** Deletes a record through the app's own confirm-delete, hash and all. */
export async function deleteRecord(
  page: Page,
  postUrl: string,
  process: string,
  key: string,
  id: string,
): Promise<void> {
  const { createHash } = await import( 'node:crypto' );
  await page.request.post( postUrl, {
    form: {
      _process: process,
      [ key ]: id,
      butt_del: '1',
      // module_form::confirm_delete() accepts md5( 'delete <key> <value>' ) plus the
      // literal 'yep', which is how the confirmation screen posts back.
      _confirm_delete: createHash( 'md5' ).update( `delete ${ key } ${ id }` ).digest( 'hex' ),
      really_confirm_delete: 'yep',
      form_auth_key: await formAuthKey( page ),
      _redirect: '/',
    },
    failOnStatusCode: false,
  } );
}
