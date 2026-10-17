import { test, expect, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, formAuthKey, login } from './seed';

/**
 * Recorded time, and the invoice it turns into.
 *
 * This is money on a path nothing tested. A timer's duration is the sum of its
 * segments; an invoice built from a timer bills `round(seconds / 3600, 2)` hours at the
 * invoice's hourly rate. So a segment whose duration is wrong bills the customer wrong,
 * and the only thing standing between the two is arithmetic nobody was checking.
 *
 * Two defects came out of walking it:
 *
 *  - Correcting a segment's start and end times on the segment form left the segment
 *    marked *running*, and the next pause recalculated its duration as "now minus the
 *    start time" - so a corrected two hour segment came back as nineteen hours, and
 *    would have been invoiced at nineteen.
 *  - The invoice built from a timer opened with no customer on it at all, even though
 *    every timer records whose work it was.
 */

/** Post to the timer module with a real form token. */
async function postTimer(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);

  return page.request.post('/?m[]=timer', {
    form: { ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
}

async function openTimer(page: Page, timerId: string) {
  await page.goto('/?m[]=timer&p[]=timer_admin_edit&timer_id=' + timerId);
  await page.waitForLoadState('load');
}

/** The timer screen's own "Total:" row, as the hh:mm:ss it prints. */
async function recordedTotal(page: Page): Promise<string | null> {
  return page.evaluate(() => {
    for (const cell of Array.from(document.querySelectorAll('td,th'))) {
      const text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
      if (/^Total:?$/i.test(text)) {
        for (let next = cell.nextElementSibling; next; next = next.nextElementSibling) {
          const value = (next.textContent || '').replace(/\s+/g, ' ').trim();
          if (/^\d+:\d\d:\d\d$/.test(value)) {
            return value;
          }
        }
      }
    }

    return null;
  });
}

test.describe('Timers', () => {
  test('records time, keeps a corrected duration, and bills it', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const description = `Timer walk ${Date.now().toString(36)}`;

    // --- start one, the way the header's start button does
    const started = await postTimer(page, {
      _process: 'task_timer_clicked',
      description,
      customer_id: customerId,
    });
    const timer = JSON.parse(await started.text()).timer;
    expect(timer && timer.timer_id, 'starting a timer should return it').toBeTruthy();
    expect(timer.running, 'a timer that has just been started should be running').toBeTruthy();
    const timerId = String(timer.timer_id);

    // --- the running segment. The pause link is the only place its id appears.
    await openTimer(page, timerId);
    expect(
      describeDiagnostics(await page.content()),
      'the timer screen should not print PHP diagnostics',
    ).toEqual([]);
    await expect(page.locator('body'), 'and it should say so').toContainText('Running');

    const segmentId = ((await page.content()).match(/timer_segment_id=(\d+)/) || [])[1];
    expect(segmentId, 'a running timer should publish a segment to pause').toBeTruthy();

    // --- correct its times to exactly two hours, the way the segment form does. The
    // field posts a unix timestamp; the text beside it is only for the user.
    const start = Math.floor(Date.now() / 1000) - 24 * 3600;
    await postTimer(page, {
      _process: 'save_timer_segment',
      timer_id: timerId,
      timer_segment_id: String(segmentId),
      start_time: String(start),
      end_time: String(start + 7200),
    });

    await openTimer(page, timerId);
    expect(await recordedTotal(page), 'two hours of recorded time should read as two hours')
      .toBe('02:00:00');
    await expect(
      page.locator('body'),
      'a segment that has been given an end time has finished, so the timer is not still running',
    ).not.toContainText('Pause Timer');

    // --- and pausing does not re-measure it against the clock. This is the regression:
    // the duration used to be recalculated as now-minus-start on every pause, so a
    // segment corrected to two hours a day ago came back as twenty-six.
    await page.goto(
      `/timer.timer_admin?timer_id=${timerId}&_process=complete_timer&timer_segment_id=${segmentId}`
      + `&form_auth_key=${await formAuthKey(page)}`,
    );
    await page.waitForLoadState('load');
    expect(
      await recordedTotal(page),
      'pausing a segment that already has an end time must not re-measure it',
    ).toBe('02:00:00');

    // --- invoice it, through the button the timer screen offers
    await openTimer(page, timerId);
    const invoiceLink = await page.locator('input[name="new_invoice"]').getAttribute('onclick');
    expect(invoiceLink, 'a billable timer should offer to become an invoice').toBeTruthy();
    expect(
      invoiceLink,
      'and the invoice should know whose work it was - it opened with no customer at all',
    ).toContain('customer_id=' + customerId);

    const url = String(invoiceLink).replace(/^.*href='/, '').replace(/';?\s*$/, '');
    await page.goto(url);
    await page.waitForLoadState('load');
    expect(
      describeDiagnostics(await page.content()),
      'the invoice built from a timer should not print PHP diagnostics',
    ).toEqual([]);

    const hourlyRate = Number(await page.locator('input[name="hourly_rate"]').first().inputValue());
    expect(hourlyRate, 'the invoice should carry an hourly rate to bill at').toBeGreaterThan(0);
    const body = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(body, 'the timer should come across as a line on the invoice').toContain(description);
    expect(body, 'at the two hours that were recorded').toContain('2:00 Hours');
    expect(body, 'priced at two hours times the rate')
      .toContain((hourlyRate * 2).toFixed(2));

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_save"]').first().click(),
    ]);
    await expect(page.locator('body'), 'the invoice should save').toContainText(/saved successfully/i);

    // --- and the timer knows it has been billed, so it cannot be billed twice
    await openTimer(page, timerId);
    await expect(
      page.locator('input[name="invoice_id"]').first(),
      'the saved invoice should be linked back onto the timer',
    ).not.toHaveValue(/^0?$/);
    await expect(page.locator('body'), 'and the timer reads as invoiced').toContainText('Invoiced');
    await expect(
      page.locator('input[name="new_invoice"]'),
      'an invoiced timer should not offer to be invoiced again',
    ).toHaveCount(0);
  });
});
