import { test, expect, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, createRecord, deleteRecord, formAuthKey, login } from './seed';

/**
 * Quote, approved, converted to a job, invoiced. The walk the product is built around.
 *
 * Nothing covered it. The smoke suite loads each of these screens on its own, which is
 * not the same thing: every screen in this chain only misbehaves when it is reached with
 * the parameters the conversion uses - a job built from `from_quote_id`, an invoice built
 * from `job_id` and `task_id[]`, tasks that have never had a long description.
 *
 * Four separate diagnostics came out of walking it once, and the last of them was writing
 * PHP's own error text into the task long description and onto the customer's invoice. So
 * this asserts there are no diagnostics at each step, as well as the money.
 */

/** Fail with the diagnostics themselves rather than "expected 1 to be 0". */
async function expectClean(page: Page, step: string) {
  const problems = describeDiagnostics(await page.content());
  expect(problems, `${step} printed PHP diagnostics:\n` + problems.join('\n')).toEqual([]);
}

async function saveQuote(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=quote', {
    form: { _process: 'save_quote', _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the quote save should be accepted').toBeLessThan(400);
}

test.describe('Quote to job to invoice', () => {
  test('converts, carries the tasks across, and prints no PHP errors on the way', async ({ page, browser }) => {
    let quoteId: string | null = null;
    let jobId: string | null = null;
    let invoiceId: string | null = null;
    try {
      test.setTimeout(180000);
      await login(page);

      // --- a quote with two tasks
      const customerId = await aCustomer(page);
      const name = `Conversion ${Date.now().toString(36)}`;
      quoteId = await createRecord(page, '/?m[]=quote', {
        _process: 'save_quote',
        quote_id: 'new',
        name,
        customer_id: customerId,
        _redirect: '',
      }, 'quote_id');

      for (const [description, amount] of [['Design', '100'], ['Build', '250']]) {
        await saveQuote(page, {
          quote_id: quoteId,
          'quote_task[new][description]': description,
          'quote_task[new][amount]': amount,
          'quote_task[new][manual_task_type]': '-1',
        });
      }

      await page.goto('/?m[]=quote&p[]=quote_admin&quote_id=' + quoteId);
      await page.waitForLoadState('load');
      await expectClean(page, 'the quote screen');

      // --- the page the customer is sent, loaded the way a customer loads it: no session.
      const publicLink = (await page.content()).match(
        /external\/m\.quote\/h\.public\/i\.[^/"'\s]+\/hash\.[a-f0-9]+/,
      );
      expect(publicLink, 'the quote screen should publish a public link').toBeTruthy();

      const stranger = await browser.newContext();
      const strangerPage = await stranger.newPage();
      await strangerPage.goto('/' + String(publicLink![0]));
      await strangerPage.waitForLoadState('load');
      // The task list template replaced an empty tax list with a flat array, so every line
      // item printed two warnings onto the page the customer reads.
      await expectClean(strangerPage, 'the public quote page');
      await expect(strangerPage.locator('body')).toContainText('Design');
      await expect(strangerPage.locator('body')).toContainText('Build');

      // --- the customer approves it
      await strangerPage.locator('input[name="quote_approve_name"]').fill('A Customer');
      await strangerPage.locator('input[name="quote_approve_go"]').check();
      await Promise.all([
        strangerPage.waitForNavigation({ waitUntil: 'load' }),
        strangerPage.locator('input[name="quote_approve"]').click(),
      ]);
      await expect(strangerPage.locator('body'), 'approving should be acknowledged')
        .toContainText(/accepted/i);
      await stranger.close();

      // --- convert to a job. The button appears on the quote only once it is approved.
      await page.goto('/?m[]=quote&p[]=quote_admin&quote_id=' + quoteId);
      await page.waitForLoadState('load');
      const convert = page.locator('input[name="butt_convert_to_job"]');
      await expect(convert, 'an approved quote should offer to become a job').toHaveCount(1);
      await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), convert.click()]);

      // The signature plugin read a task row that does not exist yet here, and the job's
      // own fields are null until it is saved.
      await expectClean(page, 'the job built from the quote');
      await expect(page.locator('body'), 'the quote tasks should come across').toContainText('Design');
      await expect(page.locator('body')).toContainText('Build');

      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.locator('input[name="butt_save"]').first().click(),
      ]);
      await expect(page.locator('body'), 'the job should save').toContainText(/saved successfully/i);
      jobId = new URL(page.url()).searchParams.get('job_id');
      expect(jobId, 'saving the job should land on it').toBeTruthy();

      // --- invoice it, through the job's own button and the dialog it opens. That dialog
      // is the only place the task ids are published, and the screen it lands on is the one
      // that used to write PHP's error text into the hidden task fields it then posted.
      await page.locator('#job_generate_invoice_button').click();
      const taskBoxes = page.locator('.invoice_create_task');
      await expect(taskBoxes, 'the dialog should offer the job\'s uninvoiced tasks')
        .toHaveCount(2, { timeout: 20000 });
      for (let i = 0; i < 2; i++) {
        await taskBoxes.nth(i).check();
      }
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.locator('.ui-dialog-buttonpane button').filter({ hasText: /^Create$/ }).first().click(),
      ]);

      expect(page.url(), 'Create should open a new invoice for the job').toContain('invoice_id=new');
      expect(page.url(), 'carrying the chosen tasks').toContain('task_id');
      await expectClean(page, 'the invoice built from the job');

      const descriptions = await page.evaluate(() =>
        Array.from(document.querySelectorAll('[name*="[description]"]'))
          .map((el: any) => el.value || el.textContent || ''));
      expect(
        descriptions.join(' '),
        'no PHP diagnostic should have been captured into a field that is about to be saved',
      ).not.toMatch(/Deprecated|Warning:|htmlspecialchars/);

      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.locator('input[name="butt_save"]').first().click(),
      ]);
      await expect(page.locator('body'), 'the invoice should save').toContainText(/saved successfully/i);
      await expectClean(page, 'the saved invoice');

      // --- and it bills what the quote quoted
      const body = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
      expect(body, 'the first quoted task should be on the invoice').toContain('Design');
      expect(body, 'the second quoted task should be on the invoice').toContain('Build');
      expect(body, 'and at the amounts that were quoted').toContain('350.00');

      // --- the invoice the customer is sent. Its lines came from a quote, so they have no
      // long description, which is the case the template used to deprecate on.
      // Saving an invoice that belongs to a job returns to the job, so the id comes from
      // the job's own invoice list rather than from the address.
      invoiceId = ((await page.content()).match(/invoice_id=(\d+)/) || [])[1] ?? null;
      expect(invoiceId, 'the job should now list the invoice it created').toBeTruthy();
      await page.goto('/?m[]=invoice&p[]=invoice_admin&invoice_id=' + invoiceId);
      await page.waitForLoadState('load');
      const invoiceLink = (await page.content()).match(
        /external\/m\.invoice\/h\.public\/i\.[^/"'\s]+\/hash\.[a-f0-9]+/,
      );
      expect(invoiceLink, 'the invoice screen should publish a public link').toBeTruthy();

      const payer = await browser.newContext();
      const payerPage = await payer.newPage();
      await payerPage.goto('/' + String(invoiceLink![0]));
      await payerPage.waitForLoadState('load');
      await expectClean(payerPage, 'the public invoice page');
      await expect(payerPage.locator('body'), 'and it bills the customer for the work')
        .toContainText('350.00');
      await payer.close();
    } finally {
      // Unwound in the order it was built: the invoice, then the job it was raised from
      // (which takes its tasks), then the quote the job came from (which takes its quote
      // tasks). Left alone this walk added a quote, a job, two tasks and an invoice to the
      // database on every run.
      if (invoiceId) {
        await deleteRecord(page, '/?m[]=invoice', 'save_invoice', 'invoice_id', invoiceId);
      }
      if (jobId) {
        await deleteRecord(page, '/?m[]=job', 'save_job', 'job_id', jobId);
      }
      if (quoteId) {
        await deleteRecord(page, '/?m[]=quote', 'save_quote', 'quote_id', quoteId);
      }
    }
  });
});
