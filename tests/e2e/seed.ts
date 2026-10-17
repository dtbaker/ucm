import { createHash } from 'node:crypto';
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

/** The customer the suites share, and the contact on it. */
export const SEED_CUSTOMER = 'CSRF Seed Customer';
export const SEED_CONTACT = 'CSRF Seed Contact';

/**
 * The customer every suite that needs one works against.
 *
 * One named record, found again each run and made the first time. It used to take
 * whichever customer happened to be first on the list, which was fine on a database
 * full of old test data and not fine anywhere else: several suites need the customer
 * to have a primary contact with an email - the read-only contact block reads the
 * name, and queueing a newsletter to a group needs an address to send to - and
 * whatever the list offered first was not required to have either. Once the leftovers
 * were cleared and the list started returning a customer with no contact, four tests
 * failed, none of them on anything the application had done wrong.
 *
 * Taking a named record of our own also means the suites stop editing, adding notes to
 * and raising invoices against whichever real customer sorted first on a development
 * database.
 */
export async function aCustomer(page: Page): Promise<string> {
  // customer_admin is the filter page; customer_admin_list is where the rows are.
  const list = '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0'
    + '&search[generic]=' + encodeURIComponent(SEED_CUSTOMER);
  const existing = await idNamedOn(page, list, 'customer_id', SEED_CUSTOMER);
  if (existing && await hasPrimaryContact(page, existing)) {
    return existing;
  }
  // save_customer() only reaches the block that creates the primary contact when
  // user_id is present in the request - the real form posts it empty for a new one.
  // Without it the customer saves with no contact at all.
  const fields = {
    _process: 'save_customer',
    customer_name: SEED_CUSTOMER,
    name: SEED_CONTACT,
    email: 'csrf-seed@example.com',
    user_id: '',
  };
  if (existing) {
    // It is there but has lost its contact - give it one rather than making a second.
    await createRecord(page, '/?m[]=customer', { ...fields, customer_id: existing }, 'customer_id');

    return existing;
  }

  return createRecord(page, '/?m[]=customer', { ...fields, customer_id: 'new' }, 'customer_id');
}

/** Whether this customer's form comes back with a primary contact name on it. */
async function hasPrimaryContact(page: Page, customerId: string): Promise<boolean> {
  await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
  const name = page.locator('input[name="name"]').first();
  if (!(await name.count())) {
    return false;
  }

  return (await name.inputValue()).trim() !== '';
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
  // invoice_admin, not invoice_admin_list: the list page sets no $invoice_safe of its
  // own and dies when it is reached directly, so asking for it finds no invoice and
  // raises a fresh one every run. invoice_admin includes the list itself.
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

/**
 * Deletes a record through the app's own confirm-delete, hash and all.
 *
 * A fixture that is made and left behind is not free: every run adds another row, the
 * screens that draw one control per record grow with them, and the lists that page at
 * twenty rows eventually push the record a later test is looking for out of view. Both
 * have already happened here, which is why a test that makes a record deletes it again.
 *
 * module_form::confirm_delete() redirects to a confirmation screen and takes the post
 * back when it carries md5( 'delete <key> <value>' ) and the literal 'yep'. Sending both
 * at once is the request that screen makes, without the round trip. Modules that delete
 * without confirming ignore the two extra fields.
 */
export async function deleteRecord(
  page: Page,
  postUrl: string,
  process: string,
  key: string,
  id: string,
  options: {
    /** the key confirm_delete() was given, where a module does not pass its own id field */
    confirmKey?: string;
    /** the delete button's name, where it is not butt_del */
    button?: string;
    /**
     * Anything else the module's own confirm screen asks for. The customer's asks
     * whether to take the linked records - its invoices, jobs and websites - and
     * without delete_others those outlive the customer they belonged to.
     */
    extra?: Record<string, string>;
    /**
     * A token to use instead of fetching one. get_secure_key() hashes the session id,
     * so it holds for the whole session; a caller deleting in bulk passes it in rather
     * than making this load a page per record, which is what made the cleanup chore
     * take twenty seconds a row.
     */
    authKey?: string;
  } = {},
): Promise<void> {
  const confirmKey = options.confirmKey || key;
  // The hash covers whatever the module handed confirm_delete(), which is not always the
  // record's own id - faq.php asks about customer_id - and is the empty string when the
  // post does not carry that field at all.
  const confirmValue = confirmKey === key ? id : '';
  await page.request.post(postUrl, {
    form: {
      _process: process,
      [key]: id,
      [options.button || 'butt_del']: '1',
      _confirm_delete: createHash('md5').update(`delete ${confirmKey} ${confirmValue}`).digest('hex'),
      really_confirm_delete: 'yep',
      form_auth_key: options.authKey ?? await formAuthKey(page),
      _redirect: '/',
      ...(options.extra || {}),
    },
    // The delete is done by the time the handler redirects, and following that redirect
    // means rendering a whole page per record.
    maxRedirects: 0,
    failOnStatusCode: false,
  });
}

/**
 * The id a save landed on, from the address bar.
 *
 * A test that means to delete what it made has to know what it made, and a cleanup
 * that quietly deletes nothing is worse than no cleanup at all - it reads as if the
 * leak were fixed. So this returns null rather than guessing, and callers assert on it.
 */
export function idInUrl(url: string, key: string): string | null {
  const value = new URL(url, 'http://localhost').searchParams.get(key);

  return value && /^[1-9]\d*$/.test(value) ? value : null;
}

/**
 * The id of a record listed on `listUrl` whose row carries `name`.
 *
 * For the suites that want one fixed record rather than a new one per run. It matches
 * on the row's text rather than the link's, because several of these lists put the
 * name in one cell and the link in another, and returns null rather than guessing - a
 * reuse helper that quietly returns the wrong record is worse than one that makes a
 * new one, and two earlier attempts at reuse in this suite failed exactly that way.
 */
export async function idNamedOn(
  page: Page,
  listUrl: string,
  key: string,
  name: string,
): Promise<string | null> {
  await page.goto(listUrl);

  return page.evaluate(({ idKey, wanted }) => {
    const links = Array.from(document.querySelectorAll(`a[href*="${idKey}="]`));
    for (const a of links as any[]) {
      const row = a.closest('tr') || a.parentElement;
      if (!row || !(row.textContent || '').includes(wanted)) {
        continue;
      }
      const found = new RegExp(idKey + '=(\\d+)').exec(a.getAttribute('href') || '');
      if (found && found[1] !== '0') {
        return found[1];
      }
    }

    return null;
  }, { idKey: key, wanted: name });
}

/**
 * The ids a list page links a named record under, lowest first.
 *
 * Rows are matched on the text of their own link rather than anywhere in the body, so the
 * term echoed back in the list's search box is not counted as a row. Every page of the
 * list is read: a record a test has just made is rarely on the first one, and a count
 * that stopped at twenty could not tell two leftovers from two hundred.
 *
 * enough stops the walk once that many have been found, for a caller that only wants to
 * know whether there are more than a few.
 */
export async function listedIds(
  page: Page,
  listUrl: string,
  idParam: string,
  label: string,
  enough = Infinity,
): Promise<string[]> {
  const ids = new Set<string>();
  const first = await (await page.request.get(listUrl)).text();
  collectIds(first, idParam, label, ids);

  // The pagination links carry the page number in pg<table id>, and the summary above
  // them says how many records there are in total. Separators in an href are written
  // &amp;, so the parameter before the one being read is not always a bare &.
  const param = (first.match(/[?&](?:amp;)?(pg[a-z_]+)=\d+/) || [])[1];
  const total = Number((first.match(/Showing records \d+ to \d+ of (\d+)/) || [])[1] || 0);
  const perPage = Number((first.match(/Showing records \d+ to (\d+) of \d+/) || [])[1] || 0);
  if (param && perPage > 0) {
    const pages = Math.ceil(total / perPage);
    const joiner = listUrl.includes('?') ? '&' : '?';
    for (let pageNumber = 1; pageNumber < pages && ids.size < enough; pageNumber++) {
      collectIds(await (await page.request.get(`${listUrl}${joiner}${param}=${pageNumber}`)).text(), idParam, label, ids);
    }
  }

  return [...ids].sort((a, b) => Number(a) - Number(b));
}

function collectIds(body: string, idParam: string, label: string, into: Set<string>) {
  const id = new RegExp(`[?&](?:amp;)?${idParam}=(\\d+)`);
  for (const [, href, text] of body.matchAll(/<a\b[^>]*href="([^"]*)"[^>]*>([\s\S]*?)<\/a>/g)) {
    if (!text.replace(/<[^>]*>/g, '').includes(label)) {
      continue;
    }
    const match = id.exec(href);
    // id=0 is the "all records" link in the page furniture, not a row.
    if (match && match[1] !== '0') {
      into.add(match[1]);
    }
  }
}
