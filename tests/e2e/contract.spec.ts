import { test, expect, Browser, Page } from '@playwright/test';
import { createRecord, login } from './seed';

/**
 * A contract, the link its customer gets, and the signature that comes back.
 *
 * The signature flow is the point of the feature and it is the one part a customer
 * touches: an unauthenticated page, found only through a hash, that writes `date_approved`
 * and `approved_by` onto the contract and notes who signed it. So it is exercised here
 * from a browser context with no session at all, and the hash is checked to actually
 * matter.
 */

function tag(): string {
  return 'ContractProbe' + Math.random().toString(36).slice(2, 8);
}

/** Today, in whatever date format this install is configured for. */
async function today(page: Page): Promise<string> {
  await page.goto('/?m[]=contract&p[]=contract_admin&contract_id=new');
  const value = await page.locator('input[name="date_create"]').first().inputValue();
  expect(value, 'a new contract should default to today').toMatch(/\d/);

  return value;
}

async function openContract(page: Page, contractId: string) {
  await page.goto('/?m[]=contract&p[]=contract_admin&contract_id=' + contractId);
  await page.waitForLoadState('load');
}

/** The link the admin screen offers to hand to the customer. */
async function publicLink(page: Page, contractId: string): Promise<string> {
  await openContract(page, contractId);
  const href = await page.evaluate(() => {
    const link = Array.from(document.querySelectorAll('a'))
      .find((a) => /m\.contract/.test(a.getAttribute('href') || '') && /hash\./.test(a.getAttribute('href') || ''));

    return link ? link.getAttribute('href') : null;
  });
  expect(href, 'the contract screen should offer an external link').toBeTruthy();

  return String(href);
}

/** A browser with no UCM session, which is what the customer has. */
async function asTheCustomer(browser: Browser, url: string) {
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(url);
  await page.waitForLoadState('load');

  return { context, page };
}

test.describe('Contracts', () => {
  test('shows a customer its contract and records the signature that comes back', async ({ page, browser }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    const date = await today(page);
    const terms = run + ' - the agreed terms of this engagement.';
    const contractId = await createRecord(page, '/?m[]=contract', {
      _process: 'save_contract',
      contract_id: 'new',
      name: run,
      type: 'Fixed Price',
      date_create: date,
      contract_text: '<p>' + terms + '</p>',
      user_id: '1',
      currency_id: '1',
      _redirect: '',
    }, 'contract_id');

    // --- the staff side
    await openContract(page, contractId);
    await expect(page.locator('input[name="name"]').first()).toHaveValue(run);
    await expect(page.locator('input[name="date_approved"]').first(),
      'a new contract is not approved').toHaveValue('');
    await expect(page.locator('input[name="approved_by"]').first()).toHaveValue('');

    // --- the customer side, with no session of its own
    const link = await publicLink(page, contractId);
    const { context, page: customer } = await asTheCustomer(browser, link);

    await expect(customer.locator('body'), 'the customer should see the contract')
      .toContainText(run);
    await expect(customer.locator('body'), 'and its terms').toContainText(terms);
    await expect(customer.locator('body'), 'which is still pending').toContainText(/Pending/i);
    await expect(customer.locator('body'), 'the staff only banner should not be on it')
      .not.toContainText(/You can send this page to your customer/i);

    // --- sign it
    await customer.locator('input[name="contract_approve_name"]').first().fill('Jane Signatory');
    await customer.locator('input[name="contract_approve_go"]').first().check();
    await Promise.all([
      customer.waitForNavigation({ waitUntil: 'load' }),
      customer.locator('input[name="contract_approve"]').first().click(),
    ]);

    await expect(customer.locator('body'), 'the customer should be told it was accepted')
      .toContainText(/accepted by Jane Signatory/i);
    await expect(customer.locator('body'), 'and the status should have changed')
      .toContainText(/Accepted on/i);
    await expect(customer.locator('body'), 'the approval form should be gone')
      .not.toContainText(/Yes, I approve this contract/i);
    await context.close();

    // --- and the signature should be on the record, not just on the page
    await openContract(page, contractId);
    await expect(page.locator('input[name="approved_by"]').first(),
      'the name the customer typed should be stored').toHaveValue('Jane Signatory');
    const approved = await page.locator('input[name="date_approved"]').first().inputValue();
    expect(approved, 'and the approval date should be filled in').toMatch(/\d/);

    expect(approved, 'which is today, the day the customer signed it').toBe(date);

    await expect(page.locator('body'), 'and the contract should keep a note of who signed it')
      .toContainText('Contract approved by Jane Signatory');
  });

  test('will not show or sign a contract on a wrong hash', async ({ page, browser }) => {
    test.setTimeout(120000);
    await login(page);

    const run = tag();
    const date = await today(page);
    const contractId = await createRecord(page, '/?m[]=contract', {
      _process: 'save_contract',
      contract_id: 'new',
      name: run,
      type: 'Fixed Price',
      date_create: date,
      contract_text: '<p>secret terms</p>',
      user_id: '1',
      currency_id: '1',
      _redirect: '',
    }, 'contract_id');

    const link = await publicLink(page, contractId);
    const wrong = link.replace(/hash\.[0-9a-f]+/, 'hash.' + 'd'.repeat(32));
    expect(wrong, 'the hash should have been swapped out').not.toBe(link);

    const { context, page: stranger } = await asTheCustomer(browser, wrong);
    await expect(stranger.locator('body'), 'a wrong hash should show nothing of the contract')
      .not.toContainText(run);
    await expect(stranger.locator('body')).not.toContainText('secret terms');

    // And it must not be able to sign it either, form or no form.
    const response = await stranger.request.post(wrong, {
      form: {
        contract_approve: '1',
        contract_approve_go: 'yes',
        contract_approve_name: 'Not The Customer',
      },
      failOnStatusCode: false,
    });
    expect(response.status(), 'the post should not fail noisily').toBeLessThan(500);
    await context.close();

    await openContract(page, contractId);
    await expect(page.locator('input[name="approved_by"]').first(),
      'the contract should still be unsigned').toHaveValue('');

    await deleteContract(page, contractId, run);
  });

  test('duplicates without carrying the signature, and deletes cleanly', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    const date = await today(page);
    const contractId = await createRecord(page, '/?m[]=contract', {
      _process: 'save_contract',
      contract_id: 'new',
      name: run,
      type: 'Fixed Price',
      date_create: date,
      contract_text: '<p>terms</p>',
      date_approved: '',
      approved_by: '',
      user_id: '1',
      currency_id: '1',
      _redirect: '',
    }, 'contract_id');

    // Sign it from the staff side, which is the other way a contract gets approved.
    await openContract(page, contractId);
    await page.locator('input[name="date_approved"]').first().fill(date);
    await page.locator('input[name="approved_by"]').first().fill('Signed In Person');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_save"]').last().click(),
    ]);

    await openContract(page, contractId);
    await expect(page.locator('input[name="approved_by"]').first()).toHaveValue('Signed In Person');
    await expect(page.locator('input[name="date_approved"]').first()).toHaveValue(date);
    await expect(page.locator('body'), 'approving from the staff side should be noted too')
      .toContainText('Contract approved by Signed In Person');

    // --- duplicate, with the button the screen shows
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_duplicate"]').first().click(),
    ]);

    const copyId = new URL(page.url()).searchParams.get('contract_id');
    expect(copyId, 'the duplicate should open on its own record').toBeTruthy();
    expect(copyId, 'and it should be a different record').not.toBe(contractId);

    await expect(page.locator('input[name="name"]').first(), 'the copy is marked as one')
      .toHaveValue('(dup) ' + run);
    await expect(page.locator('input[name="approved_by"]').first(),
      'a copy of a signed contract must not arrive already signed').toHaveValue('');
    await expect(page.locator('input[name="date_approved"]').first()).toHaveValue('');

    // The file block counts the contract's own files, which a new copy has none of.
    // It used to search on a `contract_id` the file table does not have, so it counted
    // and linked to every file in the installation instead.
    await expect(page.locator('body'), 'a brand new contract has no files of its own')
      .toContainText('This contract has 0 files');

    await deleteContract(page, String(copyId), '(dup) ' + run);
    await deleteContract(page, contractId, run);
  });
});

/** Delete through the confirmation screen the app really shows, and check it went. */
async function deleteContract(page: Page, contractId: string, name: string) {
  await openContract(page, contractId);
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

  // delete_contract() used to UPDATE a `file.contract_id` column that does not exist, and
  // the SQL error printed before redirect_browser() could send its header - so the delete
  // landed on a page whose whole content was that error, with no message and no list.
  const body = await page.locator('body').innerText();
  expect(body, 'the delete should not print a database error')
    .not.toMatch(/Unknown column|Error: /);
  expect(body, 'the app should say it deleted the contract')
    .toMatch(/contract deleted successfully/i);

  await page.goto('/?m[]=contract&p[]=contract_admin');
  await page.waitForLoadState('load');
  expect(await page.locator('body').innerText(), 'and it should be gone from the list')
    .not.toContain(name);
}
