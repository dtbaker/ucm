import { test, expect, Browser } from '@playwright/test';

/**
 * The password the install ships with.
 *
 * includes/plugin_user/user.php seeds five accounts, and until now all five stored the
 * literal string "password" rather than a hash - which ucm_password_verify() accepted as
 * the password itself. So a new install had five publicly documented logins, four of
 * which are only sample data nobody is meant to sign in as.
 *
 * The administrator keeps the default, because the setup wizard is the only thing that
 * ever sets a password for it and an install that never finished the wizard would
 * otherwise be locked out. The other four are closed, in code, so existing databases are
 * covered as well as new ones.
 *
 * If one of these ever starts passing as "logged in", check whether somebody has
 * deliberately set that account's password to the word "password" - a password actually
 * set through the UI is hashed, and this test cannot tell the two apart.
 */

const SAMPLE_ACCOUNTS = [
  'user@example.com',
  'user1@example.com',
  'user2@example.com',
  'staff@example.com',
];

async function attemptLogin(browser: Browser, email: string, password: string) {
  const context = await browser.newContext();
  try {
    const page = await context.newPage();
    await page.goto('/');
    await page.locator('input[name="email"]').fill(email);
    await page.locator('input[name="password"]').fill(password);
    await page.locator('form:has(input[name="_process_login"])')
      .locator('input[type="submit"], button[type="submit"]').first().click();
    await page.waitForLoadState('load');
    const body = await page.evaluate(() => document.body.innerText);

    return !/invalid username or password/i.test(body) && /log ?out/i.test(body);
  } finally {
    await context.close();
  }
}

test.describe('The shipped default password', () => {
  for (const email of SAMPLE_ACCOUNTS) {
    test(`${email} is not a usable login`, async ({ browser }) => {
      expect(
        await attemptLogin(browser, email, 'password'),
        `${email} accepted the shipped default password`,
      ).toBeFalsy();
    });
  }

  test('the administrator can still sign in', async ({ browser }) => {
    expect(
      await attemptLogin(browser, 'admin@example.com', 'password'),
      'closing the sample accounts must not lock the administrator out',
    ).toBeTruthy();
  });
});
