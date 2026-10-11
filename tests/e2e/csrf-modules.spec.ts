import { test, expect, Page } from '@playwright/test';

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
  },
  {
    name: 'member',
    url: '/?m[]=member&p[]=member_admin_edit',
    process: 'save_member',
    fill: { first_name: 'CSRF', last_name: 'Test', email: 'csrf-member@example.com' },
    saved: /member saved successfully/i,
    postUrl: '/?m[]=member',
    badTokenBody: { _process: 'save_member', member_id: 'new', first_name: 'Should Not Save' },
  },
  {
    name: 'website',
    url: '/?m[]=website&p[]=website_admin_edit',
    process: 'save_website',
    fill: { name: 'CSRF Test Website' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=website',
    badTokenBody: { _process: 'save_website', website_id: 'new', name: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'subscription',
    url: '/?m[]=subscription&p[]=subscription_admin_edit',
    process: 'save_subscription',
    fill: { name: 'CSRF Test Subscription', amount: '10' },
    saved: /subscription saved successfully/i,
    postUrl: '/?m[]=subscription',
    badTokenBody: { _process: 'save_subscription', subscription_id: 'new', name: 'Should Not Save', amount: '1' },
  },
  {
    name: 'template',
    url: '/?m[]=template&p[]=template_edit&template_id=new',
    process: 'save_template',
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
    fill: { name: 'CSRF Test Group' },
    saved: /group saved successfully/i,
    postUrl: '/?m[]=group',
    badTokenBody: { _process: 'save_group', group_id: 'new', name: 'Should Not Save', owner_table: 'customer' },
  },
  {
    name: 'product category',
    url: '/?m[]=product&p[]=product_admin_category_edit',
    process: 'save_product_category',
    fill: { product_category_name: 'CSRF Test Category' },
    saved: /product category saved successfully/i,
    postUrl: '/?m[]=product',
    badTokenBody: { _process: 'save_product_category', product_category_id: 'new', product_category_name: 'Should Not Save' },
  },
  {
    name: 'extra field',
    url: '/?m[]=extra&p[]=extra_settings&extra_default_id=new',
    process: 'save_extra_default',
    fill: { extra_key: 'csrf_test_field' },
    saved: /extra field saved successfully/i,
    postUrl: '/?m[]=extra',
    badTokenBody: { _process: 'save_extra_default', extra_default_id: 'new', owner_table: 'file', extra_key: 'should_not_save' },
  },
  {
    name: 'FAQ',
    url: '/?m[]=faq&p[]=faq_questions&faq_id=new',
    process: 'save_faq',
    fill: { question: 'CSRF test question?' },
    saved: /faq saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq', faq_id: 'new', question: 'Should Not Save' },
  },
  {
    name: 'FAQ product',
    url: '/?m[]=faq&p[]=faq_products&faq_product_id=new',
    process: 'save_faq_product',
    fill: { name: 'CSRF Test FAQ Product' },
    saved: /faq product saved successfully/i,
    postUrl: '/?m[]=faq',
    badTokenBody: { _process: 'save_faq_product', faq_product_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'user',
    url: '/?m[]=user&p[]=user_admin&user_id=new',
    process: 'save_user',
    fill: { name: 'CSRF Test User', email: 'csrf-user@example.com' },
    saved: /user saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope@example.com', _redirect: '/' },
  },
  {
    name: 'customer contact',
    url: '/?m[]=user&p[]=contact_admin&customer_id=1&user_id=new',
    process: 'save_user',
    fill: { name: 'CSRF Test Contact', email: 'csrf-contact@example.com' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=user',
    badTokenBody: { _process: 'save_user', user_id: '0', name: 'Should Not Save', email: 'nope2@example.com', _redirect: '/' },
  },
  {
    name: 'security role',
    url: '/?m[]=security&p[]=security_role&security_role_id=new',
    process: 'save_security_role',
    fill: { name: 'CSRF Test Role' },
    saved: /role saved successfully/i,
    postUrl: '/?m[]=security',
    badTokenBody: { _process: 'save_security_role', security_role_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'vendor',
    url: '/?m[]=vendor&p[]=vendor_admin_edit&vendor_id=new',
    process: 'save_vendor',
    fill: { vendor_name: 'CSRF Test Vendor', name: 'CSRF Contact' },
    saved: /vendor saved successfully/i,
    postUrl: '/?m[]=vendor',
    badTokenBody: { _process: 'save_vendor', vendor_id: 'new', vendor_name: 'Should Not Save' },
  },
  {
    name: 'contract',
    url: '/?m[]=contract&p[]=contract_admin&contract_id=new',
    process: 'save_contract',
    fill: { name: 'CSRF Test Contract' },
    saved: /contract saved successfully/i,
    postUrl: '/?m[]=contract',
    badTokenBody: { _process: 'save_contract', contract_id: 'new', name: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'newsletter',
    url: '/?m[]=newsletter&p[]=newsletter_edit&newsletter_id=new',
    process: 'save_newsletter',
    fill: { subject: 'CSRF Test Newsletter' },
    saved: /newsletter saved successfully/i,
    postUrl: '/?m[]=newsletter',
    badTokenBody: { _process: 'save_newsletter', newsletter_id: 'new', subject: 'Should Not Save', _redirect: '/' },
  },
  {
    name: 'customer',
    url: '/?m[]=customer&p[]=customer_admin_edit&customer_id=new',
    process: 'save_customer',
    fill: { customer_name: 'CSRF Test Customer', name: 'CSRF Contact', email: 'csrf-customer@example.com' },
    saved: /customer saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer', customer_id: 'new', customer_name: 'Should Not Save' },
  },
  {
    name: 'customer type',
    url: '/?m[]=customer&p[]=customer_settings_types&customer_type_id=new',
    process: 'save_customer_type',
    fill: { type_name: 'CSRF Test Customer Type' },
    saved: /saved successfully/i,
    postUrl: '/?m[]=customer',
    badTokenBody: { _process: 'save_customer_type', customer_type_id: 'new', type_name: 'Should Not Save' },
  },
  {
    name: 'ticket type',
    url: '/?m[]=ticket&p[]=ticket_settings_types&ticket_type_id=new',
    process: 'save_ticket_type',
    fill: { name: 'CSRF Test Ticket Type' },
    saved: /ticket type saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_type', ticket_type_id: 'new', name: 'Should Not Save' },
  },
  {
    name: 'ticket field',
    url: '/?m[]=ticket&p[]=ticket_settings_fields&ticket_data_key_id=new',
    process: 'save_ticket_data_key',
    fill: { key: 'csrf_test_key' },
    saved: /ticket field saved successfully/i,
    postUrl: '/?m[]=ticket',
    badTokenBody: { _process: 'save_ticket_data_key', ticket_data_key_id: 'new', key: 'should_not_save' },
  },
  {
    name: 'job',
    url: '/?m[]=job&p[]=job_admin&job_id=new&customer_id=1',
    process: 'save_job',
    fill: { name: 'CSRF Test Job' },
    saved: /job saved successfully/i,
    postUrl: '/?m[]=job',
    badTokenBody: { _process: 'save_job', job_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
  },
  {
    name: 'quote',
    url: '/?m[]=quote&p[]=quote_admin&quote_id=new&customer_id=1',
    process: 'save_quote',
    fill: { name: 'CSRF Test Quote' },
    saved: /quote saved successfully/i,
    postUrl: '/?m[]=quote',
    badTokenBody: { _process: 'save_quote', quote_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
  },
];

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(url => !url.href.includes('login') || url.href.includes('home'));
}

/** Every page publishes the live token as ucm.form_auth_key. */
async function formAuthKey(page: Page): Promise<string> {
  await page.goto('/?m[]=customer&p[]=customer_admin');
  const key = await page.evaluate(() => (window as any).ucm?.form_auth_key);
  expect(key).toBeTruthy();
  return key;
}

test.describe('Form auth key, per module', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
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

      await page.goto(url);

      const form = page.locator(`form:has(input[name="_process"][value="${mod.process}"])`);
      await expect(form).toHaveCount(1);

      // The guard is useless, and the form is broken, if the token is not on the page.
      await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);

      for (const [field, value] of Object.entries(mod.fill)) {
        await form.locator(`[name="${field}"]`).first().fill(value);
      }
      await form.locator('input[type="submit"][name="butt_save"]').first().click();

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
 * Notes and addresses open in a popup rather than on a page of their own, and the note
 * popup posts itself with jQuery's serialize(), so the token has to be a field inside
 * the form rather than something the JS adds. These drive the real popups.
 */
test.describe('Form auth key, popups', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('a note can still be added from a customer', async ({ page }) => {
    await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=1');
    await page.getByRole('link', { name: 'Add New Note' }).first().click();

    const modal = page.locator('.ucm-modal-popup:visible').first();
    const form = modal.locator('form:has(textarea[name="note"]), form:has([name="note"])').first();
    await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);

    await form.locator('[name="note"]').first().fill('CSRF popup note');
    await modal.getByRole('button', { name: 'Save note' }).click();

    await expect(page.locator('body')).toContainText('CSRF popup note');
    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
  });

  test('saving a note is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=note', {
      form: { _process: 'save_note', note_id: 'new', note: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });

  test('the address popup publishes a token', async ({ page }) => {
    await page.goto('/?m[]=address&p[]=address_popup&address_id=1');
    const form = page.locator('form:has(input[name="_process"][value="save_from_popup"])');
    await expect(form).toHaveCount(1);
    await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);
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
    await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);

    await form.locator('[name="name"]').first().fill('CSRF Test Transaction');
    await form.locator('[name="credit"]').first().fill('12.34');
    await form.locator('input[type="submit"][name="addnew"]').first().click();

    await expect(page.locator('body')).toContainText(/transaction saved successfully/i);
  });

  test('saving a transaction is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=finance', {
      form: { _process: 'save_finance', finance_id: 'new', name: 'Should Not Save', amount: '1' },
    });
    expect(response.status()).toBe(403);
  });

  test('the invoice email screen publishes a token', async ({ page }) => {
    await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=1&email=true');
    const form = page.locator('form:has(input[name="_process"][value="send_email"])');
    await expect(form.first().locator('input[name="form_auth_key"]')).toHaveCount(1);
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
    await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);

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
    await expect(form.locator('input[name="form_auth_key"]')).toHaveCount(1);

    await form.locator('[name="name"]').first().fill('CSRF Test Invoice');
    await form.locator('input[type="submit"][name="butt_save"]').first().click();

    await expect(page.locator('body')).toContainText(/invoice saved successfully/i);
  });

  test('saving an invoice is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=invoice', {
      form: { _process: 'save_invoice', invoice_id: 'new', name: 'Should Not Save', customer_id: '1', _redirect: '/' },
    });
    expect(response.status()).toBe(403);
  });

  test('starting a payment is deliberately left outside the token check', async ({ page }) => {
    // Payments round trip through gateway pages that cannot carry the token. If this
    // ever starts returning 403, paying an invoice has been broken.
    const response = await page.request.post('/?m[]=invoice&invoice_id=1', {
      form: { _process: 'make_payment' },
    });
    expect(response.status()).not.toBe(403);
  });

  test('saving a change request is refused without a token', async ({ page }) => {
    const response = await page.request.post('/?m[]=change_request', {
      form: { _process: 'save_change_request', change_request_id: 'new', request: 'Should Not Save' },
    });
    expect(response.status()).toBe(403);
  });
});
