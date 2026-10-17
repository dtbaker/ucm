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
  if (existing) {
    // It is there but has lost its contact - give it one rather than making a second.
    await createRecord(page, '/?m[]=customer', {
      _process: 'save_customer',
      customer_id: existing,
      customer_name: SEED_CUSTOMER,
      name: SEED_CONTACT,
      email: 'csrf-seed@example.com',
      // save_customer() only reaches the block that creates the primary contact when
      // user_id is present in the request - the real form posts it empty for a new
      // one. Without it the customer saves with no contact at all, which is how the
      // seed customer ended up with primary_user_id 0.
      user_id: '',
    }, 'customer_id');

    return existing;
  }

  return createRecord(page, '/?m[]=customer', {
    _process: 'save_customer',
    customer_id: 'new',
    customer_name: SEED_CUSTOMER,
    name: SEED_CONTACT,
    email: 'csrf-seed@example.com',
    // See above: no user_id in the request means no primary contact is made.
    user_id: '',
  }, 'customer_id');
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
 * Every module's process() handles butt_del the same way, so one helper covers all of
 * them. Going through the app rather than the database is deliberate: it exercises the
 * same path a user takes, and it takes the dependent rows with it.
 */
export async function deleteRecord(
  page: Page,
  postUrl: string,
  process: string,
  key: string,
  id: string,
  confirmKey: string = key,
  extra: Record<string, string> = {},
): Promise<void> {
  const { createHash } = await import( 'node:crypto' );
  // module_form::confirm_delete() hashes the field name it was given and that field's
  // value out of the request. Normally that is the record's own key, so confirmKey
  // defaults to it; the FAQ module passes 'customer_id' instead, and since nothing
  // sends a customer_id the value hashed there is the empty string.
  const confirmValue = confirmKey === key ? id : '';
  await page.request.post( postUrl, {
    form: {
      _process: process,
      [ key ]: id,
      butt_del: '1',
      _confirm_delete: createHash( 'md5' )
        .update( `delete ${ confirmKey } ${ confirmValue }` )
        .digest( 'hex' ),
      really_confirm_delete: 'yep',
      // get_secure_key() hashes the session id, so the token holds for the whole
      // session. A caller deleting in bulk passes it in rather than making this fetch
      // a page per record - that page load, not the delete, is what made the cleanup
      // chore take twenty seconds a row.
      form_auth_key: extra.form_auth_key ?? await formAuthKey( page ),
      _redirect: '/',
      // Anything the module's own confirm screen asks for as well. The customer's asks
      // whether to take the linked records - its invoices, jobs and websites - and
      // without delete_others those outlive the customer they belonged to.
      ...extra,
    },
    // The delete is done by the time the handler redirects, and following that redirect
    // means rendering a whole page per record - which is most of the cost when a test,
    // or the cleanup chore, removes more than one.
    maxRedirects: 0,
    failOnStatusCode: false,
  } );
}

/**
 * The id a save landed on, from the address bar.
 *
 * A test that means to delete what it made has to know what it made, and a cleanup that
 * quietly deletes nothing is worse than no cleanup at all - it reads as if the leak were
 * fixed. So this returns null rather than guessing, and callers assert on it.
 */
export function idInUrl( url: string, key: string ): string | null {
  const value = new URL( url, 'http://localhost' ).searchParams.get( key );

  return value && /^[1-9]\d*$/.test( value ) ? value : null;
}

/**
 * The id of a record listed on `listUrl` whose row carries `name`.
 *
 * For the suites that want one fixed record rather than a new one per run. It matches
 * on the row's text rather than the link's, because several of these lists put the name
 * in one cell and the link in another, and returns null rather than guessing - a reuse
 * helper that quietly returns the wrong record is worse than one that makes a new one,
 * and two earlier attempts at reuse in this suite failed exactly that way.
 */
export async function idNamedOn(
  page: Page,
  listUrl: string,
  key: string,
  name: string,
): Promise<string | null> {
  await page.goto( listUrl );

  return page.evaluate( ( { idKey, wanted } ) => {
    const links = Array.from( document.querySelectorAll( `a[href*="${ idKey }="]` ) );
    for ( const a of links as any[] ) {
      const row = a.closest( 'tr' ) || a.parentElement;
      if ( ! row || ! ( row.textContent || '' ).includes( wanted ) ) {
        continue;
      }
      const found = new RegExp( idKey + '=(\\d+)' ).exec( a.getAttribute( 'href' ) || '' );
      if ( found && found[ 1 ] !== '0' ) {
        return found[ 1 ];
      }
    }

    return null;
  }, { idKey: key, wanted: name } );
}
