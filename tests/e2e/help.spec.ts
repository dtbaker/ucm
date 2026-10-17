import { test, expect } from '@playwright/test';
import { login } from './seed';

/**
 * The help button in the page header.
 *
 * There was a unit test for what leaves the server and for the screen-to-article
 * mapping, and nothing at all for the button a person actually clicks. The themes all
 * wrote it out as `<a href="#">` and relied on help_panel.js to make it do something,
 * so every way of losing the JavaScript - a slow load, a middle click, a custom theme -
 * left the question mark doing nothing. It carries its real address now.
 */

test.describe('The help button', () => {
  test('is a real link to this screen\'s article, and opens the panel', async ({ page }) => {
    await login(page);
    await page.goto('/invoice.invoice_admin');
    await page.waitForLoadState('load');

    const button = page.locator('#header_help');
    await expect(button, 'every admin screen should offer help').toHaveCount(1);

    // A real address in the markup, not one the JavaScript patches in afterwards.
    await expect(
      button,
      'the help button should link to the article for the screen it is on',
    ).toHaveAttribute('href', /\/using\/invoices\/?$/);
    await expect(button).toHaveAttribute('target', '_blank');
    await expect(button).toHaveAttribute('rel', /noopener/);

    // An ordinary click opens the panel instead of leaving the page.
    await button.click();
    // open_modal moves the hidden #help_popup markup into the modal body.
    const panel = page.locator('.modal-body .help_panel');
    await expect(panel, 'clicking should open the help panel').toBeVisible({ timeout: 20000 });
    await expect(
      panel.locator('.help_panel_title'),
      'and the panel should hold the article for this screen',
    ).toContainText(/invoice/i);
    expect(page.url(), 'the panel should not navigate away from the screen').toContain('invoice');
  });
});
