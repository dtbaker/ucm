import { test, expect, Page } from '@playwright/test';
import {
  login, formAuthKey, existingId, aCustomer, anInvoice, aFile, anAddress,
  deleteRecord, idInUrl,
} from './seed';

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
 */

type ModuleCase = {
  /** name used in the test titles */
  name: string;
  /** page that renders the form. {id} is replaced with whatever seed() returned. */
  url: string;
  /** the _process value this form submits */
  process: string;
  /**
   * The primary key field, used to delete the record the test saved.
   *
   * Written out per module rather than worked out from badTokenBody: two of those
   * carry a customer_id as well, so guessing picks the wrong one and the cleanup
   * silently deletes nothing.
   */
  key: string;
  /**
   * The field module_form::confirm_delete() hashes, when it is not the record's own
   * key. Both FAQ screens pass 'customer_id' there, so a delete signed with faq_id is
   * silently ignored and the row stays.
   */
  confirmKey?: string;
  /** text inputs to fill before saving, by input name */
  fill: Record<string, string>;
  /** message the app shows after a successful save */
  saved: RegExp;
  /** where the no-token post goes - index.php needs m[] to pick the module */
  postUrl: string;
  /** body for the no-token post, minus the token */
  badTokenBody: Record<string, string>;
  /**
   * Some records cannot be created from their own edit page, so there is nothing to
   * open on a fresh install. When set, this body is posted with a real token first and
   * the id it redirects to is substituted into url.
   */
  seed?: { body: Record<string, string>; idParam: string };
  /**
   * For a module whose save hands back no id at all. Returns the id of every record
   * matching this test's fixture, so the cleanup takes the one just made and any left
   * by an older run with it.
   */
  findIds?: ( page: Page ) => Promise<string[]>;
};

/** The extra field fixture, looked up on the settings screen that lists them all. */
const EXTRA_FIELD_KEY = 'csrf_test_field';

async function findExtraFieldsByKey( page: Page ): Promise<string[]> {
  await page.goto( '/?m[]=extra&p[]=extra_settings' );

  return page.evaluate( ( key ) => {
    const ids = new Set<string>();
    document.querySelectorAll( 'a[href*="extra_default_id="]' ).forEach( ( a: any ) => {
      if ( ( a.textContent || '' ).trim() !== key ) {
        return;
      }
      const found = /extra_default_id=(\d+)/.exec( a.getAttribute( 'href' ) || '' );
      if ( found && found[ 1 ] !== '0' ) {
        ids.add( found[ 1 ] );
      }
    } );

    return Array.from( ids );
  }, EXTRA_FIELD_KEY );
}

/**
 * The ids a list page links to, after narrowing it with its own search box.
 *
 * The row text is deliberately not matched against: these lists print an invoice
 * number, a customer and an amount, not the record's name, so filtering on the name
 * finds nothing and the cleanup silently deletes nothing. The search has already done
 * the narrowing, and the fixture names carry a per-run stamp, so what comes back is
 * this run's record and nothing else - which the callers assert.
 */
async function idsFromSearch(page: Page, listUrl: string, term: string, key: string): Promise<string[]> {
  await page.goto(listUrl + '&search[generic]=' + encodeURIComponent(term));

  return page.evaluate((idKey) => {
    const ids = new Set<string>();
    document.querySelectorAll(`a[href*="${idKey}="]`).forEach((a: any) => {
      const found = new RegExp(idKey + '=(\\d+)').exec(a.getAttribute('href') || '');
      if (found && found[1] !== '0') {
        ids.add(found[1]);
      }
    });

    return Array.from(ids);
  }, key);
}

/** A fixture name nothing else in the database can collide with. */
function stamped(prefix: string): string {
  return `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1e4)}`;
}

const MODULES: ModuleCase[] = [
  {
    name: 'company',
    url: '/?m[]=company&p[]=company_edit',
    process: 'save_company',
    key: 'company_id',
    fill: { name: 'CSRF Test Company' },
    saved: /company saved successfully/i,
    postUrl: '/?m[]=company',
    badTokenBody: { _process: 'save_company', company_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'member',
    seed: { body: { _process: 'save_member', member_id: 'new', first_name: 'CSRF', last_name: 'Seed', email: 'csrf-member-seed@example.com', _redirect: '' }, idParam: 'member_id' },
    url: '/?m[]=member&p[]=member_admin_edit&member_id={id}',
    process: 'save_member',
    key: 'member_id',
    fill: { first_name: 'CSRF', last_name: 'Test', email: 'csrf-member@example.com' },
    saved: /member saved successfully/i,
    postUrl: '/?m[]=member',
    badTokenBody: { _process: 'save_member', member_id: 'new', first_name: 'Should Not Save' },
  },
  {
    name: 'website',
    url: '/?m[]=website&p[]=website_admin_edit',
    process: 'save_website',
    key: 'website_id',
    fill: { name: 'CSRF Test Website' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=website',
    badTokenBody: { _process: 'save_website', website_id: 'new', name: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'subscription',
    url: '/?m[]=subscription&p[]=subscription_admin_edit',
    process: 'save_subscription',
    key: 'subscription_id',
    fill: { name: 'CSRF Test Subscription', amount: '10' },
    saved: /subscription saved successfully/i,
    postUrl: '/?m[]=subscription',
    badTokenBody: { _process: 'save_subscription', subscription_id: 'new', name: 'Should Not Save', amount: '1' },
  },
  {
    name: 'template',
    url: '/?m[]=template&p[]=template_edit&template_id=new',
    process: 'save_template',
    key: 'template_id',
    fill: { template_key: 'csrf_test_template', description: 'CSRF test template' },
    saved: /template saved successfully/i,
    postUrl: '/?m[]=template',
    badTokenBody: { _process: 'save_template', template_id: 'new', template_key: 'should_not_save' },
  },
  {
    name: 'group',
    // Groups are created alongside the record they belong to, never from this page,
    // so a fresh install has none to open.
    seed: { body: { _process: 'save_group', group_id: 'new', name: 'CSRF Seed Group', owner_table: 'customer' }, idParam: 'group_id' },
    url: '/?m[]=group&p[]=group_settings&group_id={id}',
    process: 'save_group',
    key: 'group_id',
    fill: { name: 'CSRF Test Group' },
    saved: /group saved successfully/i,
    postUrl: '/?m[]=group',
    badTokenBody: { _process: 'save_group', group_id: 'new', name: 'Should Not Save', owner_table: 'customer' },
  },
  {
    name: 'product category',
    url: '/?m[]=product&p[]=product_admin_category_edit',
    process: 'save_product_category',
    key: 'product_category_id',
    fill: { product_category_name: 'CSRF Test Category' },
    saved: /product category saved successfully/i,
    postUrl: '/?m[]=product',
    badTokenBody: { _process: 'save_product_category', product_category_id: 'new', product_category_name: 'Should Not Save' },
  },
  {
    name: 'extra field',
    url: '/?m[]=extra&p[]=extra_settings&extra_default_id=new',
    // save_extra_default ends in redirect_browser( REQUEST_URI ), so neither the
    // response nor the page it lands on carries the new id. The settings screen lists
    // every extra field though, so the fixture is found there by its own key.
    findIds: findExtraFieldsByKey,
    process: 'save_extra_default',
    key: 'extra_default_id',
    fill: { extra_key: EXTRA_FIELD_KEY },
    saved: /extra field saved successfully/i,
    postUrl: '/?m[]=extra',
    badTokenBody: { _process: 'save_extra_default', extra_default_id: 'new', owner_table: 'file', extra_key: 'should_not_save' },
  },
  {
    name: 'FAQ',
    url: '/?m[]=faq&p[]=faq_questions&faq_id=new',
    process: 'save_faq',
    key: 'faq_id',
    confirmKey: 'customer_id',
    fill: { question: 'CSRF test question?' },
    saved: /faq saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq', faq_id: 'new', question: 'Should Not Save' },
  },
  {
    name: 'FAQ product',
    url: '/?m[]=faq&p[]=faq_products&faq_product_id=new',
    process: 'save_faq_product',
    key: 'faq_product_id',
    confirmKey: 'customer_id',
    fill: { name: 'CSRF Test FAQ Product' },
    saved: /faq product saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq_product', faq_product_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'user',
    url: '/?m[]=user&p[]=user_admin&user_id=new',
    process: 'save_user',
    key: 'user_id',
    fill: { name: 'CSRF Test User', email: 'csrf-user@example.com' },
    saved: /user saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope@example.com', _redirect: '/' },
  },
  {
    name: 'customer contact',
    url: '/?m[]=user&p[]=contact_admin&customer_id={customer}&user_id=new',
    process: 'save_user',
    key: 'user_id',
    fill: { name: 'CSRF Test Contact', email: 'csrf-contact@example.com' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope2@example.com', _redirect: '/' },
  },
  {
    name: 'security role',
    url: '/?m[]=security&p[]=security_role&security_role_id=new',
    process: 'save_security_role',
    key: 'security_role_id',
    fill: { name: 'CSRF Test Role' },
    saved: /role saved successfully/i,
    postUrl: '/?m[]=security',
    badTokenBody: { _process: 'save_security_role', security_role_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'vendor',
    seed: { body: { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'CSRF Seed Vendor', name: 'CSRF Seed Contact', _redirect: '' }, idParam: 'vendor_id' },
    url: '/?m[]=vendor&p[]=vendor_admin_edit&vendor_id={id}',
    process: 'save_vendor',
    key: 'vendor_id',
    fill: { vendor_name: 'CSRF Test Vendor', name: 'CSRF Contact' },
    saved: /vendor saved successfully/i,
    postUrl: '/?m[]=vendor',
    badTokenBody: { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Should Not Save' },
  },
  {
    name: 'contract',
    seed: { body: { _process: 'save_contract', contract_id: 'new', name: 'CSRF Seed Contract', _redirect: '' }, idParam: 'contract_id' },
    url: '/?m[]=contract&p[]=contract_admin&contract_id={id}',
    process: 'save_contract',
    key: 'contract_id',
    fill: { name: 'CSRF Test Contract' },
    saved: /contract saved successfully/i,
    postUrl: '/?m[]=contract',
    badTokenBody: { _process: 'save_contract', contract_id: 'new', name: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'newsletter',
    url: '/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=new',
    process: 'save_newsletter',
    key: 'newsletter_id',
    fill: { subject: 'CSRF Test Newsletter' },
    saved: /newsletter saved successfully/i,
    postUrl: '/?m[]=newsletter',
    badTokenBody: { _process: 'save_newsletter', newsletter_id: 'new', subject: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'customer',
    seed: { body: { _process: 'save_customer', customer_id: 'new', customer_name: 'CSRF Seed Subject', name: 'CSRF Seed Contact', _redirect: '' }, idParam: 'customer_id' },
    url: '/?m[]=customer&p[]=customer_admin_edit&customer_id={id}',
    process: 'save_customer',
    key: 'customer_id',
    fill: { customer_name: 'CSRF Test Customer', name: 'CSRF Contact', email: 'csrf-customer@example.com' },
    saved: /customer saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer', customer_id: 'new', customer_name: 'Should Not Save' },
  },
  {
    name: 'customer type',
    url: '/?m[]=customer&p[]=customer_settings_types&customer_type_id=new',
    process: 'save_customer_type',
    key: 'customer_type_id',
    fill: { type_name: 'CSRF Test Customer Type' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer_type', customer_type_id: 'new', type_name: 'Should Not Save' },
  },
  {
    name: 'ticket type',
    url: '/?m[]=ticket&p[]=ticket_settings_types&ticket_type_id=new',
    process: 'save_ticket_type',
    key: 'ticket_type_id',
    fill: { name: 'CSRF Test Ticket Type' },
    saved: /ticket type saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_type', ticket_type_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'ticket field',
    url: '/?m[]=ticket&p[]=ticket_settings_fields&ticket_data_key_id=new',
    process: 'save_ticket_data_key',
    key: 'ticket_data_key_id',
    fill: { key: 'csrf_test_key' },
    saved: /ticket field saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_data_key', ticket_data_key_id: 'new', key: 'should_not_save' },
  },
  {
    name: 'job',
    url: '/?m[]=job&p[]=job_admin&job_id=new&customer_id={customer}',
    process: 'save_job',
    key: 'job_id',
    fill: { name: 'CSRF Test Job' },
    saved: /job saved successfully/i,
    postUrl: '/?m[]=job',
    badTokenBody: { _process: 'save_job', job_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
  },
  {
    name: 'quote',
    seed: { body: { _process: 'save_quote', quote_id: 'new', name: 'CSRF Seed Quote', customer_id: '{customer}', _redirect: '' }, idParam: 'quote_id' },
    url: '/?m[]=quote&p[]=quote_admin&quote_id={id}',
    process: 'save_quote',
    key: 'quote_id',
    fill: { name: 'CSRF Test Quote' },
    saved: /quote saved successfully/i,
    postUrl: '/?m[]=quote',
    badTokenBody: { _process: 'save_quote', quote_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
  },
  {
    name: 'data type',
    url: '/?m[]=data&p[]=data_type_admin_open&data_type_id=new',
    process: 'save_data_type',
    key: 'data_type_id',
    fill: { data_type_name: 'CSRF Test Data Type' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=data',
    badTokenBody: { _process: 'save_data_type', data_type_id: 'new', data_type_name: 'Should Not Save' },
  },
];

test.describe('Form auth key, per module', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  for (const mod of MODULES) {
    test(`the ${mod.name} form still saves`, async ({ page }) => {
      let url = mod.url;
      // Whatever this test brings into being, so the finally below can take it away.
      // Every one of these forms used to leave its record behind, which is how the
      // subscription table reached 208 rows named 'CSRF Test Subscription' and made
      // every customer and invoice screen too slow for the suite to drive.
      let createdId: string | null = null;

      try {
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
          // The save below updates this record rather than making another, so this is
          // the row to remove.
          createdId = String(id);
        }

        if (url.includes('{customer}')) {
          url = url.replace('{customer}', await aCustomer(page));
        }

        await page.goto(url);

        const form = page.locator(`form:has(input[name="_process"][value="${mod.process}"])`);
        await expect(form).toHaveCount(1);

        // The guard is useless, and the form is broken, if the token is not on the page.
        // More than one is fine - the bulk action and export controls publish their own.
        await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

        for (const [field, value] of Object.entries(mod.fill)) {
          await form.locator(`[name="${field}"]`).first().fill(value);
        }
        await form.locator('input[type="submit"][name="butt_save"]').first().click();

        await expect(page.locator('body')).toContainText(mod.saved);
        await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);

        // A save lands on the record it wrote, which is where the id comes from. Asserted
        // rather than shrugged off: a cleanup that deletes nothing looks exactly like a
        // cleanup that works, and that is how two earlier attempts at this went unnoticed.
        createdId = createdId ?? idInUrl(page.url(), mod.key);
        expect(
          createdId ?? mod.findIds,
          `saving a ${mod.name} should land on the record, so the test can delete it again`,
        ).toBeTruthy();
      } finally {
        const doomed = createdId ? [createdId] : mod.findIds ? await mod.findIds(page) : [];
        expect(
          doomed.length,
          `the ${mod.name} test should have something to clean up`,
        ).toBeGreaterThan(0);
        for (const id of doomed) {
          await deleteRecord(page, mod.postUrl, mod.process, mod.key, id, mod.confirmKey);
        }
      }
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
 * Notes and addresses open in a popup rather than on a page of their own, and the note
 * popup posts itself with jQuery's serialize(), so the token has to be a field inside
 * the form rather than something the JS adds. These drive the real popups.
 */
test.describe('Form auth key, popups', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('a note can still be added from a customer', async ({ page }) => {
    const customerId = await aCustomer(page);
    await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
    await page.getByRole('link', { name: 'Add New Note' }).first().click();

    const modal = page.locator('.ucm-modal-popup:visible').first();
    const form = modal.locator('form:has(textarea[name="note"]), form:has([name="note"])').first();
    await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

    const note = stamped('CSRF popup note');
    await form.locator('[name="note"]').first().fill(note);
    await modal.getByRole('button', { name: 'Save note' }).click();

    await expect(page.locator('body')).toContainText(note);
    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);

    // Take it off again. Notes are not deleted through a _process at all - the screen
    // renders a link carrying do_delete - so this clicks the app's own link. Left in
    // place they pile up on the one customer every suite shares, and that customer's
    // screen is on the critical path of half the suite.
    page.on('dialog', d => d.accept());
    const customerPage = '/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId;
    const row = page.locator('tr', { hasText: note }).first();
    await expect(row.locator('a.note_delete'), 'the note should be listed with a delete link')
      .toHaveCount(1);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      row.locator('a.note_delete').first().click(),
    ]);

    // Checked back on the customer screen, not on wherever the delete redirected to:
    // asserting against the landing page passes whether or not anything was removed,
    // which is how the first version of this cleanup did nothing at all and said it had.
    await page.goto(customerPage);
    await expect(
      page.locator('tr', { hasText: note }),
      'the note this test added should be gone from the customer',
    ).toHaveCount(0);
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
    const name = stamped('CSRF Test Transaction');
    try {
      await page.goto('/?m[]=finance&p[]=finance');

      const form = page.locator('form#quick_add_form').first();
      await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

      await form.locator('[name="name"]').first().fill(name);
      await form.locator('[name="credit"]').first().fill('12.34');
      await form.locator('input[type="submit"][name="addnew"]').first().click();

      await expect(page.locator('body')).toContainText(/transaction saved successfully/i);
    } finally {
      // Quick add redirects to the list without saying which row it wrote, so the
      // fixture is found by its stamped name. Searching rather than scanning the list:
      // the list is paged, and the row just written is not reliably on the first page.
      const doomed = await idsFromSearch(page, '/?m[]=finance&p[]=finance_list', name, 'finance_id');
      expect(doomed, 'the transaction this test saved should be findable again, exactly once')
        .toHaveLength(1);
      await deleteRecord(page, '/?m[]=finance', 'save_finance', 'finance_id', doomed[0]);
    }
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
    let invoiceId: string | null = null;
    const name = stamped('CSRF Test Invoice');
    try {
      await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=new&customer_id=1');
      const form = page.locator('form:has(input[name="_process"][value="save_invoice"])');
      await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

      await form.locator('[name="name"]').first().fill(name);
      await form.locator('input[type="submit"][name="butt_save"]').first().click();

      await expect(page.locator('body')).toContainText(/invoice saved successfully/i);

      invoiceId = idInUrl(page.url(), 'invoice_id');
    } finally {
      // Saving an invoice does not land back on it - it returns to whatever the invoice
      // belongs to - so the address bar usually has no id and the row is found by name.
      const doomed = invoiceId
        ? [invoiceId]
        : await idsFromSearch(page, '/?m[]=invoice&p[]=invoice_admin', name, 'invoice_id');
      expect(doomed, 'the invoice this test saved should be findable again, exactly once')
        .toHaveLength(1);
      await deleteRecord(page, '/?m[]=invoice', 'save_invoice', 'invoice_id', doomed[0]);
    }
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
    let fileId: string | null = null;
    try {
      await page.goto('/?m[]=file&p[]=file_admin&file_id=new');
      const form = page.locator('form:has(input[name="_process"][value="save_file"])');
      await expect(form.locator('input[name="form_auth_key"]').first()).toBeAttached();

      await form.locator('input[type="submit"][name="butt_save"]').first().click();
      await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
      await expect(page.locator('body')).toContainText(/saved successfully/i);

      // Saved with nothing uploaded, so the row has no name to find it by later - the
      // address bar is the only handle on it. Asserted, because without the id this
      // test used to leave a nameless file row behind on every run.
      fileId = idInUrl(page.url(), 'file_id');
      expect(fileId, 'saving a file should land on it, so this test can remove it').toBeTruthy();
    } finally {
      if (fileId) {
        await deleteRecord(page, '/?m[]=file', 'save_file', 'file_id', fileId);
      }
    }
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
