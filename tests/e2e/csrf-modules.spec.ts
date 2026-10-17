import { test, expect, Page } from '@playwright/test';
import { login, formAuthKey, existingId, aCustomer, anInvoice, aFile, anAddress, deleteRecord, listedIds } from './seed';

/**
 * Per-module form_auth_key coverage.
 *
 * Each module below has a CSRF guard at the top of its process() method. Guarding a
 * module breaks it *silently* if the form it serves does not publish the token - the
 * page still renders, the save just stops working - so every module gets two tests:
 *
 *   1. fill the real form in the browser, click save, and see it save
 *   2. post the same thing with no token and see a 403
 *
 * The first test is the one that matters. Adding a module here is one entry.
 *
 * Saving a real form makes a real record, so every entry also says how to delete the one
 * it made again. Without that they pile up, one per module per run: by October 2026 this
 * file had left 198 companies, 197 websites, 201 users, 199 contracts and 209 templates
 * behind, and the staff screen - which draws a checkbox per company - had grown to 445KB.
 * The last test in this file fails if that ever starts again.
 */

type ModuleCase = {
  /** name used in the test titles */
  name: string;
  /** page that renders the form. {id} is replaced with whatever seed() returned. */
  url: string;
  /** the _process value this form submits */
  process: string;
  /** text inputs to fill before saving, by input name */
  fill: Record<string, string>;
  /** message the app shows after a successful save */
  saved: RegExp;
  /** where the no-token post goes - index.php needs m[] to pick the module */
  postUrl: string;
  /** body for the no-token post, minus the token */
  badTokenBody: Record<string, string>;
  /** the field this module addresses its record by, in the save redirect and the delete */
  idParam: string;
  /** the text the record's row links under, on the list below */
  label: string;
  /**
   * A page that lists the module's records, used to find the record again when the save
   * redirect does not name it, and by the guard at the bottom of this file. {customer} is
   * replaced the same way it is in url.
   */
  list: string;
  /** the key confirm_delete() was given, where the module does not pass its own id field */
  confirmKey?: string;
  /** the delete button's name, where it is not butt_del */
  deleteButton?: string;
  /**
   * Some records cannot be created from their own edit page, so there is nothing to
   * open on a fresh install. When set, this body is posted with a real token first and
   * the id it redirects to is substituted into url.
   */
  seed?: { body: Record<string, string>; idParam: string };
};

const MODULES: ModuleCase[] = [
  {
    name: 'company',
    url: '/?m[]=company&p[]=company_edit',
    process: 'save_company',
    fill: { name: 'CSRF Test Company' },
    saved: /company saved successfully/i,
    postUrl: '/?m[]=company',
    badTokenBody: { _process: 'save_company', company_id: 'new', name: 'Should Not Save' },
    idParam: 'company_id',
    label: 'CSRF Test Company',
    list: '/?m[]=company&p[]=company_settings',
  },
  {
    name: 'member',
    url: '/?m[]=member&p[]=member_admin_edit',
    process: 'save_member',
    fill: { first_name: 'CSRF', last_name: 'Test', email: 'csrf-member@example.com' },
    saved: /member saved successfully/i,
    postUrl: '/?m[]=member',
    badTokenBody: { _process: 'save_member', member_id: 'new', first_name: 'Should Not Save' },
    idParam: 'member_id',
    label: 'CSRF Test',
    list: '/?m[]=member&p[]=member_admin_list',
  },
  {
    name: 'website',
    url: '/?m[]=website&p[]=website_admin_edit',
    process: 'save_website',
    fill: { name: 'CSRF Test Website' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=website',
    badTokenBody: { _process: 'save_website', website_id: 'new', name: 'Should Not Save', _redirect: '/' },
    idParam: 'website_id',
    label: 'CSRF Test Website',
    list: '/?m[]=website&p[]=website_admin_list',
  },
  {
    name: 'subscription',
    url: '/?m[]=subscription&p[]=subscription_admin_edit',
    process: 'save_subscription',
    fill: { name: 'CSRF Test Subscription', amount: '10' },
    saved: /subscription saved successfully/i,
    postUrl: '/?m[]=subscription',
    badTokenBody: { _process: 'save_subscription', subscription_id: 'new', name: 'Should Not Save', amount: '1' },
    idParam: 'subscription_id',
    label: 'CSRF Test Subscription',
    list: '/?m[]=subscription&p[]=subscription_admin_list',
  },
  {
    name: 'template',
    url: '/?m[]=template&p[]=template_edit&template_id=new',
    process: 'save_template',
    fill: { template_key: 'csrf_test_template', description: 'CSRF test template' },
    saved: /template saved successfully/i,
    postUrl: '/?m[]=template',
    badTokenBody: { _process: 'save_template', template_id: 'new', template_key: 'should_not_save' },
    idParam: 'template_id',
    label: 'csrf_test_template',
    list: '/?m[]=template&p[]=template_list',
  },
  {
    name: 'group',
    // Groups are created alongside the record they belong to, never from this page,
    // so a fresh install has none to open.
    seed: { body: { _process: 'save_group', group_id: 'new', name: 'CSRF Seed Group', owner_table: 'customer' }, idParam: 'group_id' },
    url: '/?m[]=group&p[]=group_settings&group_id={id}',
    process: 'save_group',
    fill: { name: 'CSRF Test Group' },
    saved: /group saved successfully/i,
    postUrl: '/?m[]=group',
    badTokenBody: { _process: 'save_group', group_id: 'new', name: 'Should Not Save', owner_table: 'customer' },
    idParam: 'group_id',
    label: 'CSRF Test Group',
    list: '/?m[]=group&p[]=group_settings',
  },
  {
    name: 'product category',
    url: '/?m[]=product&p[]=product_admin_category_edit',
    process: 'save_product_category',
    fill: { product_category_name: 'CSRF Test Category' },
    saved: /product category saved successfully/i,
    postUrl: '/?m[]=product',
    badTokenBody: { _process: 'save_product_category', product_category_id: 'new', product_category_name: 'Should Not Save' },
    idParam: 'product_category_id',
    label: 'CSRF Test Category',
    list: '/?m[]=product&p[]=product_admin_category_list',
  },
  {
    name: 'extra field',
    url: '/?m[]=extra&p[]=extra_settings&extra_default_id=new',
    process: 'save_extra_default',
    fill: { extra_key: 'csrf_test_field' },
    saved: /extra field saved successfully/i,
    postUrl: '/?m[]=extra',
    badTokenBody: { _process: 'save_extra_default', extra_default_id: 'new', owner_table: 'file', extra_key: 'should_not_save' },
    idParam: 'extra_default_id',
    label: 'csrf_test_field',
    list: '/?m[]=extra&p[]=extra_settings',
  },
  {
    name: 'FAQ',
    url: '/?m[]=faq&p[]=faq_questions&faq_id=new',
    process: 'save_faq',
    fill: { question: 'CSRF test question?' },
    saved: /faq saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq', faq_id: 'new', question: 'Should Not Save' },
    idParam: 'faq_id',
    label: 'CSRF test question?',
    list: '/?m[]=faq&p[]=faq_questions',
    // faq.php hands confirm_delete() customer_id rather than the faq's own id.
    confirmKey: 'customer_id',
  },
  {
    name: 'FAQ product',
    url: '/?m[]=faq&p[]=faq_products&faq_product_id=new',
    process: 'save_faq_product',
    fill: { name: 'CSRF Test FAQ Product' },
    saved: /faq product saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq_product', faq_product_id: 'new', name: 'Should Not Save' },
    idParam: 'faq_product_id',
    label: 'CSRF Test FAQ Product',
    list: '/?m[]=faq&p[]=faq_products',
    confirmKey: 'customer_id',
  },
  {
    name: 'user',
    url: '/?m[]=user&p[]=user_admin&user_id=new',
    process: 'save_user',
    fill: { name: 'CSRF Test User', email: 'csrf-user@example.com' },
    saved: /user saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope@example.com', _redirect: '/' },
    idParam: 'user_id',
    label: 'CSRF Test User',
    list: '/?m[]=user&p[]=user_admin_list',
  },
  {
    name: 'customer contact',
    url: '/?m[]=user&p[]=contact_admin&customer_id={customer}&user_id=new',
    process: 'save_user',
    fill: { name: 'CSRF Test Contact', email: 'csrf-contact@example.com' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope2@example.com', _redirect: '/' },
    idParam: 'user_id',
    label: 'CSRF Test Contact',
    list: '/?m[]=user&p[]=contact_admin_list&customer_id={customer}',
    // user.php looks for butt_del_contact on this one, and butt_del on the staff form.
    deleteButton: 'butt_del_contact',
  },
  {
    name: 'security role',
    url: '/?m[]=security&p[]=security_role&security_role_id=new',
    process: 'save_security_role',
    fill: { name: 'CSRF Test Role' },
    saved: /role saved successfully/i,
    postUrl: '/?m[]=security',
    badTokenBody: { _process: 'save_security_role', security_role_id: 'new', name: 'Should Not Save' },
    idParam: 'security_role_id',
    label: 'CSRF Test Role',
    list: '/?m[]=security&p[]=security_role_list',
  },
  {
    name: 'vendor',
    url: '/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=new',
    process: 'save_vendor',
    fill: { vendor_name: 'CSRF Test Vendor', name: 'CSRF Contact' },
    saved: /vendor saved successfully/i,
    postUrl: '/?m[]=vendor',
    badTokenBody: { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Should Not Save' },
    idParam: 'vendor_id',
    label: 'CSRF Test Vendor',
    list: '/?m[]=vendor&p[]=vendor_admin_list',
  },
  {
    name: 'contract',
    url: '/?m[]=contract&p[]=contract_admin&contract_id=new',
    process: 'save_contract',
    fill: { name: 'CSRF Test Contract' },
    saved: /contract saved successfully/i,
    postUrl: '/?m[]=contract',
    badTokenBody: { _process: 'save_contract', contract_id: 'new', name: 'Should Not Save', _redirect: '/' },
    idParam: 'contract_id',
    label: 'CSRF Test Contract',
    list: '/?m[]=contract&p[]=contract_admin',
  },
  {
    name: 'newsletter',
    url: '/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=new',
    process: 'save_newsletter',
    fill: { subject: 'CSRF Test Newsletter' },
    saved: /newsletter saved successfully/i,
    postUrl: '/?m[]=newsletter',
    badTokenBody: { _process: 'save_newsletter', newsletter_id: 'new', subject: 'Should Not Save', _redirect: '/' },
    idParam: 'newsletter_id',
    label: 'CSRF Test Newsletter',
    // newsletter_list only shows the ones that have been sent.
    list: '/?m[]=newsletter&p[]=newsletter_list_draft',
  },
  {
    name: 'customer',
    url: '/?m[]=customer&p[]=customer_admin_edit&customer_id=new',
    process: 'save_customer',
    fill: { customer_name: 'CSRF Test Customer', name: 'CSRF Contact', email: 'csrf-customer@example.com' },
    saved: /customer saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer', customer_id: 'new', customer_name: 'Should Not Save' },
    idParam: 'customer_id',
    label: 'CSRF Test Customer',
    list: '/?m[]=customer&p[]=customer_admin_list&customer_type_id=0',
  },
  {
    name: 'customer type',
    url: '/?m[]=customer&p[]=customer_settings_types&customer_type_id=new',
    process: 'save_customer_type',
    fill: { type_name: 'CSRF Test Customer Type' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer_type', customer_type_id: 'new', type_name: 'Should Not Save' },
    idParam: 'customer_type_id',
    label: 'CSRF Test Customer Type',
    list: '/?m[]=customer&p[]=customer_settings_types',
  },
  {
    name: 'ticket type',
    url: '/?m[]=ticket&p[]=ticket_settings_types&ticket_type_id=new',
    process: 'save_ticket_type',
    fill: { name: 'CSRF Test Ticket Type' },
    saved: /ticket type saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_type', ticket_type_id: 'new', name: 'Should Not Save' },
    idParam: 'ticket_type_id',
    label: 'CSRF Test Ticket Type',
    list: '/?m[]=ticket&p[]=ticket_settings_types',
  },
  {
    name: 'ticket field',
    url: '/?m[]=ticket&p[]=ticket_settings_fields&ticket_data_key_id=new',
    process: 'save_ticket_data_key',
    fill: { key: 'csrf_test_key' },
    saved: /ticket field saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_data_key', ticket_data_key_id: 'new', key: 'should_not_save' },
    idParam: 'ticket_data_key_id',
    label: 'csrf_test_key',
    list: '/?m[]=ticket&p[]=ticket_settings_fields',
  },
  {
    name: 'job',
    url: '/?m[]=job&p[]=job_admin&job_id=new&customer_id={customer}',
    process: 'save_job',
    fill: { name: 'CSRF Test Job' },
    saved: /job saved successfully/i,
    postUrl: '/?m[]=job',
    badTokenBody: { _process: 'save_job', job_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
    idParam: 'job_id',
    label: 'CSRF Test Job',
    list: '/?m[]=job&p[]=job_admin',
  },
  {
    name: 'quote',
    url: '/?m[]=quote&p[]=quote_admin&quote_id=new&customer_id={customer}',
    process: 'save_quote',
    fill: { name: 'CSRF Test Quote' },
    saved: /quote saved successfully/i,
    postUrl: '/?m[]=quote',
    badTokenBody: { _process: 'save_quote', quote_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
    idParam: 'quote_id',
    label: 'CSRF Test Quote',
    list: '/?m[]=quote&p[]=quote_admin',
  },
  {
    name: 'data type',
    url: '/?m[]=data&p[]=data_type_admin_open&data_type_id=new',
    process: 'save_data_type',
    fill: { data_type_name: 'CSRF Test Data Type' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=data',
    badTokenBody: { _process: 'save_data_type', data_type_id: 'new', data_type_name: 'Should Not Save' },
    idParam: 'data_type_id',
    label: 'CSRF Test Data Type',
    list: '/?m[]=data&p[]=data_type_admin_list',
  },
];

/**
 * The record the test in hand has made, for the afterEach below. Playwright runs the
 * tests in a file one at a time in the same worker, so one slot is enough.
 */
let created: { mod: ModuleCase; id: string } | null = null;

test.describe('Form auth key, per module', () => {
  test.beforeEach(async ({ page }) => {
    created = null;
    await login(page);
  });

  // In afterEach rather than at the end of each test, so the record is taken away again
  // even when an assertion fails.
  test.afterEach(async ({ page }) => {
    if (!created) {
      return;
    }
    const { mod, id } = created;
    created = null;
    await deleteRecord(page, mod.postUrl, mod.process, mod.idParam, id, {
      confirmKey: mod.confirmKey,
      button: mod.deleteButton,
    });
  });

  for (const mod of MODULES) {
    test(`the ${mod.name} form still saves`, async ({ page }) => {
      let url = mod.url;

      if (mod.seed) {
        const key = await formAuthKey(page);
        const seeded = await page.request.post(mod.postUrl, {
          form: { ...mod.seed.body, form_auth_key: key },
          maxRedirects: 0,
          failOnStatusCode: false,
        });
        const location = seeded.headers()['location'] || '';
        const id = new URL(location, 'http://localhost').searchParams.get(mod.seed.idParam);
        expect(id, `seeding a ${mod.name} should redirect to the new record`).toBeTruthy();
        url = url.replace('{id}', String(id));
      }

      if (url.includes('{customer}')) {
        url = url.replace('{customer}', await aCustomer(page));
      }

      // Resolved before the save, because looking it up afterwards would navigate away
      // from the page the assertions below read.
      const list = await listFor(page, mod);

      await page.goto(url);

      const form = page.locator(`form:has(input[name="_process"][value="${mod.process}"])`);
      await expect(form).toHaveCount(1);

      // The guard is useless, and the form is broken, if the token is not on the page.
      // More than one is fine - the bulk action and export controls publish their own.
      await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

      for (const [field, value] of Object.entries(mod.fill)) {
        await form.locator(`[name="${field}"]`).first().fill(value);
      }
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        form.locator('input[type="submit"][name="butt_save"]').first().click(),
      ]);

      // Found before the assertions below rather than after, so afterEach still has the
      // record to delete when one of them fails.
      const id = await savedId(page, mod, list);
      expect(id, `the ${mod.name} test cannot find the record it just saved, so it cannot delete it again`)
        .toBeTruthy();
      created = { mod, id: String(id) };

      await expect(page.locator('body')).toContainText(mod.saved);
      await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
    });

    test(`the ${mod.name} form is refused without a token`, async ({ page }) => {
      const response = await page.request.post(mod.postUrl, {
        form: mod.badTokenBody,
      });
      expect(response.status()).toBe(403);
      expect(await response.text()).toMatch(/invalid or expired form submission/i);
    });
  }
});

/**
 * The id of the record a save just made.
 *
 * Most modules redirect to the record itself, so the id is already in the url. The ones
 * that go back to their list instead - and the extra fields screen, which redirects to
 * itself - are looked up by name. The fixture names in the table above belong to this
 * spec alone, so the newest row carrying one is the row the save just made.
 */
async function savedId(page: Page, mod: ModuleCase, list: string): Promise<string | null> {
  const inUrl = idIn(page.url(), mod.idParam);
  if (inUrl) {
    return inUrl;
  }

  const ids = await listedIds(page, list, mod.idParam, mod.label);

  return ids.length ? ids[ids.length - 1] : null;
}

/** A record id out of a url the app has redirected to. */
function idIn(url: string, idParam: string): string | null {
  const match = new RegExp(`[?&]${idParam}=(\\d+)`).exec(url);

  return match && match[1] !== '0' ? match[1] : null;
}

/** A module's list page, with the customer the contact list needs filled in. */
async function listFor(page: Page, mod: ModuleCase): Promise<string> {
  return mod.list.includes('{customer}') ? mod.list.replace('{customer}', await aCustomer(page)) : mod.list;
}

/**
 * Notes and addresses open in a popup rather than on a page of their own, and the note
 * popup posts itself with jQuery's serialize(), so the token has to be a field inside
 * the form rather than something the JS adds. These drive the real popups.
 */
test.describe('Form auth key, popups', () => {
  /** What the test in hand has to take away again, set as soon as it knows the id. */
  let madeHere: (() => Promise<void>) | null = null;

  test.beforeEach(async ({ page }) => {
    madeHere = null;
    await login(page);
  });

  test.afterEach(async () => {
    const remove = madeHere;
    madeHere = null;
    if (remove) {
      await remove();
    }
  });

  test('a note can still be added from a customer', async ({ page }) => {
    const customerId = await aCustomer(page);
    await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
    await page.getByRole('link', { name: 'Add New Note' }).first().click();

    const modal = page.locator('.ucm-modal-popup:visible').first();
    const form = modal.locator('form:has(textarea[name="note"]), form:has([name="note"])').first();
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    await form.locator('[name="note"]').first().fill('CSRF popup note');
    await modal.getByRole('button', { name: 'Save note' }).click();

    await expect(page.locator('body')).toContainText('CSRF popup note');
    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);

    // This note is left behind, unlike the fixtures above. The only delete a note has is
    // the trash link on its row, and that goes to handle_hook( 'note_delete' ), which
    // calls note_delete( owner_table, owner_id ) with no note id - so it would take every
    // note on the customer with it, not the one this test made.
  });

  test('saving a note is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=note', {
      form: { _process: 'save_note', note_id: 'new', note: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });

  test('the address popup publishes a token', async ({ page }) => {
    const addressId = await anAddress(page);
    expect(addressId, 'the customer form should carry an address id').toBeTruthy();

    await page.goto('/?m[]=address&p[]=address_popup&address_id=' + addressId);
    const form = page.locator('form:has(input[name="_process"][value="save_from_popup"])');
    await expect(form).toHaveCount(1);
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();
  });

  test('saving an address is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=address', {
      form: { _process: 'save_from_popup', _redirect: '/' },
    });
    expect(response.status()).toBe(403);
  });

  test('the quick add transaction form still saves', async ({ page }) => {
    await page.goto('/?m[]=finance&p[]=finance');

    const form = page.locator('form#quick_add_form').first();
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    await form.locator('[name="name"]').first().fill('CSRF Test Transaction');
    await form.locator('[name="credit"]').first().fill('12.34');
    await form.locator('input[type="submit"][name="addnew"]').first().click();

    await expect(page.locator('body')).toContainText(/transaction saved successfully/i);

    // The quick add row goes back to the list rather than to the transaction, so the
    // transaction it made is the newest one on the list carrying its name. finance.php
    // deletes this one without asking to confirm.
    const listed = await listedIds(page, '/?m[]=finance&p[]=finance', 'finance_id', 'CSRF Test Transaction');
    const transaction = listed[listed.length - 1];
    expect(transaction, 'the new transaction should be on the finance list').toBeTruthy();
    madeHere = () => deleteRecord(page, '/?m[]=finance', 'save_finance', 'finance_id', transaction);
  });

  test('saving a transaction is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=finance', {
      form: { _process: 'save_finance', finance_id: 'new', name: 'Should Not Save', amount: '1' },
    });
    expect(response.status()).toBe(403);
  });

  test('the invoice email screen publishes a token', async ({ page }) => {
    const invoiceId = await anInvoice(page);
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId + '&email=true');
    const form = page.locator('form:has(input[name="_process"][value="send_email"])');
    await expect(form.first().locator('input[name="form_auth_key"]').first()).toBeAttached();
  });

  test('sending an email is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=email', {
      form: { _process: 'send_email', to_email: 'nobody@example.com', subject: 'Should Not Send' },
    });
    expect(response.status()).toBe(403);
  });

  /**
   * Settings are the one place where getting this wrong is dangerous rather than just
   * annoying: check_secure_key() reads a config value to build the token, and
   * module_config::c() returns false when it is called re-entrantly, which would make
   * get_secure_key() mint and store a brand new secret and log everybody out. Saving
   * settings with the real form is the check that nothing like that happens.
   */
  test('the settings page still saves, and does not change the signing secret', async ({ page }) => {
    const keyBefore = await page.evaluate(() => (window as any).ucm?.form_auth_key);

    await page.goto('/?m[]=config&p[]=config_settings');
    const form = page.locator('form:has(input[name="_process"][value="save_config"])');
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    // Submit unchanged - exactly what clicking Save does.
    await form.locator('input[type="submit"][name="save"]').first().click();
    await expect(page.locator('body')).toContainText(/configuration values saved successfully/i);

    // If the secret had been regenerated the token would differ and we would be logged out.
    await page.goto('/?m[]=customer&p[]=customer_admin');
    const keyAfter = await page.evaluate(() => (window as any).ucm?.form_auth_key);
    expect(keyAfter).toBe(keyBefore);
  });

  test('saving settings is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=config&p[]=config_settings', {
      form: { _process: 'save_config', 'config[admin_system_name]': 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });

  test('the invoice form still saves', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=new&customer_id=1');
    const form = page.locator('form:has(input[name="_process"][value="save_invoice"])');
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    await form.locator('[name="name"]').first().fill('CSRF Test Invoice');
    await form.locator('input[type="submit"][name="butt_save"]').first().click();

    await expect(page.locator('body')).toContainText(/invoice saved successfully/i);

    // This invoice is left behind too. Saving one goes back to the invoice list rather
    // than to the invoice, and finding it again means reading a list that is already
    // thousands of rows long - it wants a fixture that names itself, which is a change to
    // more of the suite than this one.
  });

  test('saving an invoice is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=invoice', {
      form: { _process: 'save_invoice', invoice_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
    });
    expect(response.status()).toBe(403);
  });

  test('completing a payment from the admin link is refused without a token', async ({ page }) => {
    // make_payment is only reached from the "complete" links on the invoice and credit
    // pages, and both send the token. Customers paying an invoice do not come through
    // here - that is external_hook('public_pay'), which checks its own signed link - and
    // nor do gateway callbacks, which use external_hook('ipn'/'event_ipn').
    const response = await page.request.post('/?m[]=invoice&invoice_id=1', {
      form: { _process: 'make_payment' },
    });
    expect(response.status()).toBe(403);
  });

  test('the customer payment route does not go through the token check', async ({ page }) => {
    // Customers pay through external_hook('public_pay'), which checks its own signed
    // link hash and then calls handle_payment() directly. The token guard on
    // _process=make_payment must not reach it, or every payment link breaks.
    //
    // The hash here is wrong on purpose: a real one needs the install's secret. What
    // matters is the failure mode - the route declines quietly, it does not 403 from
    // the form token check, which would mean the guard had leaked into this path.
    const response = await page.request.get(
      '/external/m.invoice/h.public_pay/i.1/hash.00000000000000000000000000000000',
      { headers: { Cookie: '' } },
    );
    expect(response.status()).toBe(200);
    expect(await response.text()).not.toContain('Invalid or expired form submission');
  });

  /**
   * The calendar and the map save over background requests whose URL the page builds,
   * so the token travels in the URL. These drive the exact URLs the page hands to its
   * scripts rather than a hand-written one.
   */
  test('the calendar data feed still works', async ({ page }) => {
    await page.goto('/?m[]=calendar&p[]=calendar_admin');
    const feedUrl = await page.evaluate(() => {
      const m = document.documentElement.innerHTML.match(/DATA_FEED_URL = "([^"]+)"/);
      return m ? m[1] : null;
    });
    expect(feedUrl, 'the calendar page should publish a data feed url').toBeTruthy();
    expect(feedUrl).toContain('form_auth_key=');

    const ok = await page.request.post(feedUrl + '_process=ajax_calendar&method=list', {
      form: { showdate: '2026-10-12', viewtype: 'week' },
    });
    expect(ok.status()).not.toBe(403);
  });

  test('the calendar data feed is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m=calendar&_process=ajax_calendar&method=list', {
      form: { showdate: '2026-10-12', viewtype: 'week' },
    });
    expect(response.status()).toBe(403);
  });

  test('the map page sends a token when it saves coordinates', async ({ page }) => {
    await page.goto('/?m[]=map&p[]=map_admin');
    const html = await page.content();
    expect(html).toContain('form_auth_key: ucm.form_auth_key');
  });

  test('saving map coordinates is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m=map&p=map_admin&_process=ajax_save_map_coords', {
      form: { address_id: '1', address_hash: 'x', lat: '1', lng: '2' },
    });
    expect(response.status()).toBe(403);
  });

  test('the file form still saves', async ({ page }) => {
    // The file form has no plain text field - the description is a rich text editor -
    // so this saves it as it stands, which is what the Save button does.
    await page.goto('/?m[]=file&p[]=file_admin&file_id=new');
    const form = page.locator('form:has(input[name="_process"][value="save_file"])');
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      form.locator('input[type="submit"][name="butt_save"]').first().click(),
    ]);

    // This record has no name to find it by - the form has no plain text field - so the
    // id has to come off the url the save landed on.
    const fileId = idIn(page.url(), 'file_id');
    expect(fileId, 'the save should land on the file it made').toBeTruthy();
    madeHere = () => deleteRecord(page, '/?m[]=file', 'save_file', 'file_id', String(fileId));

    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
    await expect(page.locator('body')).toContainText(/saved successfully/i);
  });

  test('saving a file is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=file', {
      form: { _process: 'save_file', file_id: 'new', description: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });

  test('downloading a file is deliberately left outside the token check', async ({ page }) => {
    // Download links are plain hrefs scattered through the app and shared by hand.
    const fileId = await aFile(page);
    const response = await page.request.get('/?m[]=file&_process=download&file_id=' + fileId);
    expect(response.status()).not.toBe(403);
  });

  test('the encrypted field popup puts a token in its save urls', async ({ page }) => {
    await page.goto('/?m[]=encrypt&p[]=encrypt_popup&encrypt_field_id=1&encrypt_id=0&callback_id=x');
    const html = await page.content();
    expect(html).toMatch(/_process=save_encrypt&form_auth_key=[a-f0-9]{32}/);
  });

  test('saving an encryption key is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m=encrypt&_process=save_encrypt_key', {
      form: { encrypt_key_id: '0', encrypt_key_name: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });

  /**
   * Bulk actions and CSV export run from the list pages through the pagination hook,
   * not through a module's process(), and they delete or dump whole result sets.
   */
  test('a bulk action is refused without a token', async ({ page }) => {
    const response = await page.request.get('/?m[]=ticket&p[]=ticket_admin&bulk_action_go=yes');
    expect(response.status()).toBe(403);
  });

  test('adding results to a group is refused without a token', async ({ page }) => {
    const response = await page.request.get('/?m[]=ticket&p[]=ticket_admin&add_group_go=yes');
    expect(response.status()).toBe(403);
  });

  test('a CSV export is refused without a token', async ({ page }) => {
    const response = await page.request.get('/?m[]=invoice&p[]=invoice_admin&import_export_go=yes');
    expect(response.status()).toBe(403);
  });

  test('the bulk actions box still submits', async ({ page }) => {
    // Nothing is selected, so nothing is deleted - this is about the submit getting
    // through rather than about what the action does.
    await aFile(page);
    await page.goto('/?m[]=file&p[]=file_admin');
    await page.getByRole('link', { name: '(bulk actions)' }).first().click();

    const form = page.locator('form:has(input[name="bulk_action_go"])').first();
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    await page.locator('#bulk_action_button').first().click();
    await page.waitForLoadState();
    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
  });

  test('the bulk and export controls carry a token without putting it in the markup', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin');
    await expect(page.locator('input[name="bulk_action_go"]').first()).toBeAttached();

    // The token rides on the button, not in a hidden field. A hidden field would travel
    // in the query string on these list forms, which are GET when rewrite links are on,
    // and from there into history, access logs and Referer headers.
    await expect(page.locator('#import_export_button[data-form-auth-key]').first()).toBeAttached();

    // A token inside a POST form is fine - it travels in the body. The thing that must
    // never happen is a token inside a GET form, where it ends up in the URL.
    const leaky = await page.evaluate(() =>
      [...document.querySelectorAll('input[name="form_auth_key"]')]
        .map((i) => i.closest('form'))
        .filter((f): f is HTMLFormElement => !!f)
        .filter((f) => (f.getAttribute('method') || 'get').toLowerCase() !== 'post')
        .map((f) => f.getAttribute('action') || '(no action)'),
    );
    expect(leaky, 'a token in a GET form ends up in the query string').toEqual([]);

    // The helper that moves it into the form at click time has to be loaded, or the
    // buttons silently stop working.
    expect(await page.evaluate(() => typeof (window as any).ucm?.submit_guarded)).toBe('function');

    // And the export still works when it is submitted with the token.
    const key = await page.evaluate(() => (window as any).ucm?.form_auth_key);
    const csv = await page.request.get('/?m[]=invoice&p[]=invoice_admin&import_export_go=yes&form_auth_key=' + key);
    expect(csv.status()).toBe(200);
    expect(csv.headers()['content-type']).toContain('csv');
  });

  test('every script the page asks for carries a version marker', async ({ page }) => {
    // Without ?ver= a browser keeps a cached copy of javascript.js for ever, so a fix
    // shipped in an update never reaches anyone who has already loaded the page.
    await page.goto('/?m[]=invoice&p[]=invoice_admin');
    const unversioned = await page.evaluate(() => {
      const urls = [...document.querySelectorAll('script[src], link[rel="stylesheet"][href]')]
        .map((el) => el.getAttribute('src') || el.getAttribute('href') || '')
        .filter(Boolean);
      // Only our own files. We cannot version someone else's CDN.
      const ours = urls.filter((u) => !/^(https?:)?\/\//i.test(u) || u.includes(location.host));
      return ours.filter((u) => !u.includes('?'));
    });
    expect(unversioned, 'these assets would be cached for ever').toEqual([]);
  });

  test('saving a change request is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=change_request', {
      form: { _process: 'save_change_request', change_request_id: 'new', request: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });
});

/**
 * Every fixture above is deleted again as its test finishes. This is what notices when
 * one of them stops being.
 *
 * A module whose delete quietly breaks leaves one more record behind on every run and
 * nothing else in the suite complains, until a list pages the record another test is
 * looking for out of view or a screen that draws a control per record grows to half a
 * megabyte. Both have happened here. Two is slack for a record an interrupted run left
 * behind, not room to grow in.
 */
const LEFTOVERS_ALLOWED = 2;

test.describe('Form auth key fixtures', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('the records the module tests make are deleted again', async ({ page }) => {
    // Reading every page of twenty-odd lists is more than the default timeout allows,
    // and a list that has already been let go is the slowest of all.
    test.slow();

    const piling: string[] = [];
    for (const mod of MODULES) {
      // Counting stops as soon as there are too many: the number is not interesting past
      // that point, and stopping is what keeps this from crawling over a list that has
      // already collected a few hundred.
      const ids = await listedIds(page, await listFor(page, mod), mod.idParam, mod.label, LEFTOVERS_ALLOWED + 1);
      if (ids.length > LEFTOVERS_ALLOWED) {
        piling.push(`${mod.name}: more than ${LEFTOVERS_ALLOWED} records named ${JSON.stringify(mod.label)}`);
      }
    }

    expect(
      piling,
      'these modules are leaving their fixtures behind - delete the rows, then fix the cleanup that should have',
    ).toEqual([]);
  });
});
