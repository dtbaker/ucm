import { test, expect, Page } from '@playwright/test';
import { login } from './seed';

/**
 * A customer, from created to deleted, through the real screens.
 *
 * Everything else in this suite proves a page renders, or that a save is not refused.
 * None of it proves a feature works: a save can report "saved successfully" and write
 * nothing, a list can render and not contain the record, and a delete can report success
 * and leave the row behind. This walks the whole thing and checks the values at each
 * step.
 *
 * It uses the list's own search box rather than looking for the record on screen,
 * because the development database has well over a hundred customers and a new one does
 * not land on the first page.
 */

/** A name nothing else in the database will have. */
function uniqueName(prefix: string): string {
  return `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1e4)}`;
}

async function openCustomer(page: Page, customerId: string) {
  await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
  await page.waitForLoadState('load');
}

async function saveAndWait(page: Page) {
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.locator('input[name="butt_save"]').first().click(),
  ]);
}

/** Search the customer list and return the ids of the rows that come back. */
async function searchCustomers(page: Page, term: string): Promise<string[]> {
  await page.goto('/?m[]=customer&p[]=customer_admin_list&customer_type_id=0');
  await page.waitForLoadState('load');
  const search = page.locator('input[name="search[generic]"]').first();
  await search.fill(term);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), search.press('Enter')]);

  return page.evaluate(() => {
    const ids = new Set<string>();
    document.querySelectorAll('a[href*="customer_id="]').forEach((a: any) => {
      const m = /customer_id=(\d+)/.exec(a.getAttribute('href') || '');
      // customer_id=0 is the "all customers" link in the page furniture, not a row.
      if (m && m[1] !== '0') {
        ids.add(m[1]);
      }
    });

    return Array.from(ids);
  });
}

test.describe('A customer, end to end', () => {
  test('is created, read back, edited, found by search, and deleted', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const name = uniqueName('Lifecycle Customer');
    const email = `lifecycle-${Date.now()}@example.com`;

    // --- create
    await openCustomer(page, 'new');
    await page.locator('input[name="customer_name"]').fill(name);
    await page.locator('input[name="name"]').first().fill('Lifecycle');
    await page.locator('input[name="last_name"]').first().fill('Contact');
    await page.locator('input[name="email"]').first().fill(email);
    await page.locator('input[name="phone"]').first().fill('+61 7 1111 1111');
    await saveAndWait(page);

    await expect(page.locator('body'), 'the save should report success').toContainText(/saved successfully/i);

    // Saving a new customer lands on the list, so the list's own search is how the
    // record is found again - which is the first thing worth proving anyway.
    const found = await searchCustomers(page, name);
    expect(found, 'the customer that was just created should be findable by name').toHaveLength(1);
    const customerId = found[0];

    // --- read back. The save said it worked; this is whether it did.
    await openCustomer(page, String(customerId));
    await expect(page.locator('input[name="customer_name"]')).toHaveValue(name);
    await expect(page.locator('input[name="email"]').first()).toHaveValue(email);
    await expect(page.locator('input[name="phone"]').first()).toHaveValue('+61 7 1111 1111');

    // --- edit
    const renamed = name + ' (renamed)';
    await page.locator('input[name="customer_name"]').fill(renamed);
    await page.locator('input[name="phone"]').first().fill('+61 7 2222 2222');
    await saveAndWait(page);

    await openCustomer(page, String(customerId));
    await expect(page.locator('input[name="customer_name"]')).toHaveValue(renamed);
    await expect(page.locator('input[name="phone"]').first()).toHaveValue('+61 7 2222 2222');

    // --- find it on the list, by the list's own search
    expect(
      await searchCustomers(page, renamed),
      'the customer list search should find the customer that was just saved',
    ).toContain(String(customerId));

    // --- delete, through the confirmation screen the app really shows
    await openCustomer(page, String(customerId));
    page.on('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);
    await expect(page.locator('body'), 'deleting should ask for confirmation first')
      .toContainText(/really delete/i);

    await page.evaluate(() => {
      const flag = document.getElementById('really_confirm_delete') as HTMLInputElement;
      flag.value = 'yep';
      const form = flag.form as HTMLFormElement;
      const button = document.createElement('input');
      button.type = 'hidden';
      button.name = 'butt_del';
      button.value = '1';
      form.appendChild(button);
      form.submit();
    });
    await page.waitForLoadState('load');

    // --- gone
    expect(
      await searchCustomers(page, renamed),
      'the deleted customer should not come back from a search',
    ).not.toContain(String(customerId));

    // --- and gone from its own screen too. delete_customer() used to leave the
    // module_cache entry behind, so the customer's own page kept rendering the deleted
    // record - name, contacts and all - while the list correctly showed it gone.
    await page.goto('/?m[]=customer&p[]=customer_admin_open&customer_id=' + customerId);
    await page.waitForLoadState('load');
    await expect(
      page.locator('body'),
      "a deleted customer's own screen should not render it out of the cache",
    ).not.toContainText(renamed);
  });
});
