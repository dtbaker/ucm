import { test, expect } from '@playwright/test';
import { login } from './seed';

/**
 * The advanced settings save confirmation.
 *
 * The message has always survived the redirect and has always been in the HTML. It is
 * drawn inside #message_popdown, which starts display:none and is only ever revealed by
 * JavaScript, so a text-only assertion passes even when the box never opens. This page
 * is the one where that matters: it lays out a few thousand rows, and the auto-dismiss
 * timer used to start at DOM ready, which could expire before the message was painted.
 *
 * So this asserts the box is actually on screen, not just present.
 */

const SETTINGS_URL = '/?m[]=config&p[]=config_settings';
const KEY = 'admin_system_name';

test.describe('Advanced settings', () => {
  test('shows its save confirmation on screen', async ({ page }) => {
    await login(page);
    await page.goto(SETTINGS_URL);

    // Clicking a value swaps the span out for a text input.
    const value = page.locator(`span.config_variable[data-name="config[${KEY}]"]`);
    await expect(value).toHaveCount(1);
    const original = ((await value.textContent()) ?? '').trim();
    await value.click();

    const input = page.locator(`input[name="config[${KEY}]"]`);
    await expect(input).toBeVisible();
    // Save the value it already has: the confirmation does not depend on anything
    // changing, and this leaves the database as it was found.
    await input.fill(original);
    await page.locator('input[type="submit"][name="save"]').click();

    const popdown = page.locator('#message_popdown');
    await expect(popdown).toContainText(/configuration values saved successfully/i);
    // Being in the DOM is not enough - the box starts hidden.
    await expect(popdown).toBeVisible();

    // And the value really is still what it was.
    await expect(page.locator(`span.config_variable[data-name="config[${KEY}]"]`)).toHaveText(original);
  });

  /**
   * The reason the confirmation could go missing on this page in particular. The
   * auto-dismiss timer is wall-clock, so if it starts before anything has been painted
   * it can run out while the browser is still laying out the page - the save works and
   * the confirmation is never seen. A page that reports itself as hidden never paints,
   * which pins that down without depending on how fast the machine is.
   */
  test('does not start the auto-dismiss countdown before the page is painted', async ({ page }) => {
    await page.addInitScript(() => {
      Object.defineProperty(document, 'hidden', { get: () => true, configurable: true });
      Object.defineProperty(document, 'visibilityState', { get: () => 'hidden', configurable: true });
    });

    await login(page);
    await page.goto(SETTINGS_URL);

    const value = page.locator(`span.config_variable[data-name="config[${KEY}]"]`);
    const original = ((await value.textContent()) ?? '').trim();
    await value.click();
    await page.locator(`input[name="config[${KEY}]"]`).fill(original);
    await page.locator('input[type="submit"][name="save"]').click();

    const popdown = page.locator('#message_popdown');
    await expect(popdown).toContainText(/configuration values saved successfully/i);

    // Well past the four seconds the message would otherwise have been given.
    await page.waitForTimeout(6000);

    const display = await popdown.evaluate((el) => getComputedStyle(el).display);
    expect(display, 'the confirmation should still be waiting to be seen').not.toBe('none');
  });
});
