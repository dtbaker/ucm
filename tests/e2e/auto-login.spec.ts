import { test, expect } from '@playwright/test';
import { login } from './seed';

/**
 * The auto-login link.
 *
 * module_security::get_auto_login_string() turns a user id and their stored password
 * hash into a bearer key, and that key is the whole of several features: the password
 * reset email, the customer welcome email, and every public invoice, quote, job, ticket
 * and contract link that signs the customer in on arrival.
 *
 * It used to work by putting the stored hash into $_REQUEST['password'] and running the
 * ordinary login, which only ever succeeded because password verification accepted a
 * hash as its own password. Closing that hole (31855c3) left auto-login working for
 * exactly one kind of account: one still on the shipped default password. Nothing in the
 * suite noticed, because nothing used the link.
 *
 * So this walks it the way a user would: get a real link out of the app, open it with no
 * session, and check it signs that user in.
 */

/** The auto-login link the user's login settings page offers for copying. */
async function autoLoginLink(page: import('@playwright/test').Page, userId: string): Promise<string> {
  await page.goto('/?m[]=user&p[]=user_admin&user_id=' + userId);
  await page.waitForLoadState('load');
  const link = await page.evaluate(() => {
    const a = Array.from(document.querySelectorAll('a[href*="auto_login="]'))[0] as HTMLAnchorElement | undefined;
    return a ? a.href : '';
  });
  expect(link, 'the user login settings should offer an auto-login link').toContain('auto_login=');
  return link;
}

test.describe('Auto-login links', () => {
  test('a link from the app signs that user in', async ({ page, browser }) => {
    await login(page);
    const link = await autoLoginLink(page, '1');

    // A browser that has never seen this app, the way an emailed link arrives.
    const fresh = await browser.newContext();
    const visitor = await fresh.newPage();
    await visitor.goto(link);
    await visitor.waitForLoadState('load');

    await expect(
      visitor.locator('input[name="password"]'),
      'the link should not land on the login screen',
    ).toHaveCount(0);
    await expect(visitor.locator('body')).toContainText(/log ?out/i);
    await fresh.close();
  });

  test('a key that is not the real one is refused', async ({ page, browser }) => {
    await login(page);
    const link = await autoLoginLink(page, '1');
    const tampered = link.replace(/auto_login=1:(\w)/, (_m, c) => 'auto_login=1:' + (c === 'a' ? 'b' : 'a'));
    expect(tampered, 'the probe should actually differ from the real key').not.toEqual(link);

    const fresh = await browser.newContext();
    const visitor = await fresh.newPage();
    await visitor.goto(tampered);
    await visitor.waitForLoadState('load');

    await expect(
      visitor.locator('input[name="password"]'),
      'a wrong key should leave the visitor logged out',
    ).toBeVisible();
    await fresh.close();
  });
});
