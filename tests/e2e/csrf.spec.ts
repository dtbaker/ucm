import { test, expect, Page } from '@playwright/test';

/**
 * Regression tests for the form_auth_key (CSRF) check.
 *
 * module_form::process() used to call check_secure_key() and throw the result away, so a
 * missing or forged token was accepted. These tests fail if that ever comes back.
 */

async function login(page: Page) {
  await page.goto('/');
  await page.locator('input[name="email"]').fill('admin@example.com');
  await page.locator('input[name="password"]').fill('password');
  await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
  await page.waitForURL(url => !url.href.includes('login') || url.href.includes('home'));
}

/** Read a live form_auth_key out of a rendered page. Every page publishes it as ucm.form_auth_key. */
async function currentFormAuthKey(page: Page): Promise<string> {
  await page.goto('/?m[]=customer&p[]=customer_admin');
  const key = await page.evaluate(() => (window as any).ucm?.form_auth_key);
  expect(key).toBeTruthy();
  return key;
}

test.describe('Form auth key', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('a form submit with no auth key is refused', async ({ page }) => {
    const response = await page.goto('/?m[]=form&_process=save_fieldset_options');
    expect(response?.status()).toBe(403);
    await expect(page.locator('body')).toContainText(/invalid or expired form submission/i);
  });

  test('a form submit with a forged auth key is refused', async ({ page }) => {
    const response = await page.goto('/?m[]=form&_process=save_fieldset_options&form_auth_key=not-the-real-key');
    expect(response?.status()).toBe(403);
    await expect(page.locator('body')).toContainText(/invalid or expired form submission/i);
  });

  test('an empty auth key is refused', async ({ page }) => {
    const response = await page.goto('/?m[]=form&_process=save_fieldset_options&form_auth_key=');
    expect(response?.status()).toBe(403);
  });

  test('an array auth key is refused rather than crashing', async ({ page }) => {
    // $_REQUEST['form_auth_key'] can be made an array from the query string.
    const response = await page.goto('/?m[]=form&_process=save_fieldset_options&form_auth_key[]=x');
    expect(response?.status()).toBe(403);
  });

  test('the real auth key is accepted', async ({ page }) => {
    const key = await currentFormAuthKey(page);
    const response = await page.goto('/?m[]=form&_process=save_fieldset_options&form_auth_key=' + encodeURIComponent(key));
    expect(response?.status()).not.toBe(403);
    await expect(page.locator('body')).not.toContainText(/invalid or expired form submission/i);
  });

  test('pages publish a form auth key for submits to use', async ({ page }) => {
    const key = await currentFormAuthKey(page);
    expect(key).toMatch(/^[a-f0-9]{32}$/);
  });
});
