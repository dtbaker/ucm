import { test, expect, Page } from '@playwright/test';

/**
 * The address block on the customer form is saved by a hook rather than by the customer
 * module, through a set of hidden fields. Nothing covered that round trip, and the save
 * hook used to take the owner table and address type from those hidden fields rather than
 * from its own arguments - so a submit without them wrote a row that get_address() could
 * never find again. This walks the real form.
 */

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(u => !u.href.includes('login') || u.href.includes('home'));
}

test.describe('Customer address', () => {
  test('is still there after the customer is saved', async ({ page }) => {
    // A new name each run, so the customer can be found again on the list afterwards.
    const name = 'Address Test ' + Date.now();

    await login(page);
    await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=new');

    const form = page.locator('form:has(input[name="_process"][value="save_customer"])');
    await form.locator('[name="customer_name"]').first().fill(name);
    await form.locator('[name="name"]').first().fill('Address Test Contact');
    await form.locator('[name="email"]').first().fill('address-test@example.com');
    await form.locator('input[name$="[line_1]"]').first().fill('99 Test Street');
    await form.locator('input[name$="[suburb]"]').first().fill('Testville');
    await form.locator('input[type="submit"][name="butt_save"]').first().click();

    await expect(page.locator('body')).toContainText(/customer saved successfully/i);

    // Saving lands on the customer list, so open the one that was just made.
    await page.getByRole('link', { name }).first().click();
    const customerId = page.url().match(/customer_id[=\/](\d+)/)?.[1];
    expect(customerId, 'the new customer should be on the list').toBeTruthy();

    await page.goto('/?m[]=customer&p[]=customer_admin_edit&customer_id=' + customerId);
    await expect(page.locator('input[name$="[line_1]"]').first()).toHaveValue('99 Test Street');
    await expect(page.locator('input[name$="[suburb]"]').first()).toHaveValue('Testville');
    // An address id means the row was found again, rather than a fresh orphan being made.
    await expect(page.locator('input[name$="[address_id]"]').first()).not.toHaveValue('');
  });
});
