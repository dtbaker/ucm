import { test, expect, Page } from '@playwright/test';
import { aCustomer, createRecord, formAuthKey, login } from './seed';

/**
 * A quote, from created to deleted, with its arithmetic checked.
 *
 * The quote module had no coverage at all beyond "the page renders without a warning".
 * This builds one with two tasks, checks it adds them up, reads the values back off the
 * screen, renames it, and deletes it through the confirmation the app really shows.
 *
 * Note what this does *not* cover: turning a quote into an invoice. That does not go
 * straight from quote to invoice - the "Create Invoice" button opens a new invoice with
 * `quote_id` in the URL, and nothing in the invoice module reads it. The conversion runs
 * through the job module (`module_job`, which is what reads `$_REQUEST['task_id']`), so
 * covering it means walking quote to job to invoice. Left for someone with the room to
 * do it properly rather than half-asserted here.
 */

async function saveQuote(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=quote', {
    form: { _process: 'save_quote', _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the save should be accepted').toBeLessThan(400);

  return response;
}

/** The money the quote screen prints next to a label, e.g. "Total:". */
async function amountLabelled(page: Page, label: RegExp): Promise<string | null> {
  return page.evaluate((source) => {
    const pattern = new RegExp(source, 'i');
    for (const cell of Array.from(document.querySelectorAll('td,th'))) {
      const text = ((cell as HTMLElement).innerText || '').replace(/\s+/g, ' ').trim();
      if (pattern.test(text) && text.length < 40) {
        const next = cell.nextElementSibling as HTMLElement | null;
        const value = next ? (next.innerText || '').replace(/\s+/g, ' ').trim() : '';
        if (/[0-9]/.test(value)) {
          return value;
        }
      }
    }

    return null;
  }, label.source);
}

async function openQuote(page: Page, quoteId: string) {
  await page.goto('/?m[]=quote&p[]=quote_admin&quote_id=' + quoteId);
  await page.waitForLoadState('load');
}

test.describe('A quote, end to end', () => {
  test('adds its tasks up, reads back, renames, and deletes', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const customerId = await aCustomer(page);
    const name = `Quote lifecycle ${Date.now().toString(36)}`;
    const quoteId = await createRecord(page, '/?m[]=quote', {
      _process: 'save_quote',
      quote_id: 'new',
      name,
      customer_id: customerId,
      _redirect: '',
    }, 'quote_id');

    for (const [description, amount] of [['Design', '100'], ['Build', '50']]) {
      await saveQuote(page, {
        quote_id: quoteId,
        'quote_task[new][description]': description,
        'quote_task[new][amount]': amount,
        'quote_task[new][manual_task_type]': '-1',
      });
    }

    await openQuote(page, quoteId);
    await expect(page.locator('body')).toContainText('Design');
    await expect(page.locator('body')).toContainText('Build');
    expect(await amountLabelled(page, /^Sub Total:?$/), '100 and 50 should sub-total 150').toContain('150');
    expect(await amountLabelled(page, /^Total:?$/), 'and total 150').toContain('150');
    await expect(page.locator('input[name="name"]').first()).toHaveValue(name);

    // --- rename, and check it stuck
    const renamed = name + ' (renamed)';
    await saveQuote(page, { quote_id: quoteId, name: renamed });
    await openQuote(page, quoteId);
    await expect(page.locator('input[name="name"]').first()).toHaveValue(renamed);
    expect(
      await amountLabelled(page, /^Total:?$/),
      'renaming a quote should not disturb its tasks',
    ).toContain('150');

    // --- delete, through the confirmation screen
    page.on('dialog', (dialog) => dialog.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('form:has(input[name="_process"]) input[name="butt_del"]').first().click(),
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

    // Checked through the list rather than by reopening the quote: a deleted quote's own
    // screen still renders its old values, because module_cache is not cleared on delete.
    await page.goto('/?m[]=quote&p[]=quote_admin');
    await page.waitForLoadState('load');
    const search = page.locator('input[name="search[generic]"]').first();
    await search.fill(renamed);
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), search.press('Enter')]);
    const stillListed = await page.evaluate(() => {
      const ids = new Set<string>();
      document.querySelectorAll('a[href*="quote_id="]').forEach((a: any) => {
        const m = /quote_id=(\d+)/.exec(a.getAttribute('href') || '');
        if (m && m[1] !== '0') {
          ids.add(m[1]);
        }
      });

      return Array.from(ids);
    });
    expect(stillListed, 'the deleted quote should not come back from a search').not.toContain(quoteId);
  });
});
