import { test, expect, Page } from '@playwright/test';
import { createRecord, formAuthKey, login } from './seed';

/**
 * The money kept in the Finance area: income, expense, the recurring schedule, and the
 * two places the app adds them up again - the transaction list's totals row and the tax
 * report.
 *
 * Two escaping bugs had been fixed here before, but nothing exercised the lifecycle, and
 * nothing had ever checked that the tax report's columns reconcile. They did not.
 *
 * Writes mostly go through the module's own POST contract, because the sub total, the tax
 * and the total are worked out in javascript on the form (includes/plugin_finance/js/
 * finance.js) rather than on the server. The quick add row and both delete buttons are
 * driven through the real screens, because that is where the interesting behaviour is.
 */

/** A tag unique to one run, so a filter finds this run's rows and nothing else. */
function tag(): string {
  return 'FinProbe' + Math.random().toString(36).slice(2, 8);
}

async function saveFinance(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=finance', {
    form: { _process: 'save_finance', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the save should be accepted').toBeLessThan(400);

  return response;
}

/** Today, in whatever date format this install is configured for. */
async function today(page: Page): Promise<string> {
  await page.goto('/?m[]=finance&p[]=finance_edit&finance_id=new');
  const value = await page.locator('input[name="transaction_date"]').first().inputValue();
  expect(value, 'a new transaction should default to today').toMatch(/\d/);

  return value;
}

async function openTransaction(page: Page, financeId: string) {
  await page.goto('/?m[]=finance&p[]=finance_edit&finance_id=' + financeId);
  await page.waitForLoadState('load');
}

/** Every cell of every row of the table that carries a totals row, as text. */
async function listRows(page: Page): Promise<string[][]> {
  return page.evaluate(() => {
    const tables = Array.from(document.querySelectorAll('table'));
    const table = tables.find((t) => /Totals:/.test((t as HTMLElement).innerText || ''));
    if (!table) {
      return [];
    }

    return Array.from((table as HTMLTableElement).rows).map((row) =>
      Array.from(row.cells).map((cell) => ((cell as HTMLElement).innerText || '').replace(/\s+/g, ' ').trim())
    );
  });
}

/** The transaction list, filtered to one run's rows. */
async function openList(page: Page, filter: string) {
  await page.goto('/?m[]=finance&p[]=finance&search[generic]=' + encodeURIComponent(filter));
  await page.waitForLoadState('load');
}

function money(text: string): number {
  return Number(text.replace(/[^0-9.-]/g, '') || 0);
}

test.describe('Finance transactions', () => {
  test('keeps an income transaction\'s sub total, tax and total, and adds the list up', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const run = tag();
    const date = await today(page);

    // 100.00 plus 10% tax. The browser form computes these three numbers from each other;
    // posting them is what the form posts, and the assertions are all made on the screen.
    const incomeId = await createRecord(page, '/?m[]=finance', {
      _process: 'save_finance',
      finance_id: 'new',
      name: run + ' income',
      description: 'income probe',
      transaction_date: date,
      type: 'i',
      sub_amount: '100.00',
      taxable_amount: '100.00',
      'tax_ids[]': '0',
      'tax_names[]': 'TAX',
      'tax_percents[]': '10',
      'tax_amount[]': '10.00',
      amount: '110.00',
      currency_id: '1',
    }, 'finance_id');

    // --- read it back field by field, off its own screen
    await openTransaction(page, incomeId);
    await expect(page.locator('input[name="name"]').first(), 'the name should come back')
      .toHaveValue(run + ' income');
    await expect(page.locator('textarea[name="description"]').first()).toHaveValue('income probe');
    await expect(page.locator('input[name="transaction_date"]').first()).toHaveValue(date);
    await expect(page.locator('input[name="type"][value="i"]').first(), 'it should still be income')
      .toBeChecked();
    await expect(page.locator('input[name="sub_amount"]').first()).toHaveValue('100.00');
    await expect(page.locator('input[name="taxable_amount"]').first()).toHaveValue('100.00');
    await expect(page.locator('input[name="tax_percents[]"]').first()).toHaveValue('10.00');
    await expect(page.locator('input[name="tax_amount[]"]').first(), '10% of 100 is 10')
      .toHaveValue('10.00');
    await expect(page.locator('input[name="amount"]').first(), 'and the total is 110')
      .toHaveValue('110.00');

    // --- an expense, with no tax on it
    const expenseId = await createRecord(page, '/?m[]=finance', {
      _process: 'save_finance',
      finance_id: 'new',
      name: run + ' expense',
      transaction_date: date,
      type: 'e',
      sub_amount: '50.25',
      taxable_amount: '0.00',
      amount: '50.25',
      currency_id: '1',
    }, 'finance_id');

    await openTransaction(page, expenseId);
    await expect(page.locator('input[name="type"][value="e"]').first(), 'it should be an expense')
      .toBeChecked();
    await expect(page.locator('input[name="amount"]').first()).toHaveValue('50.25');

    // --- the list's own totals row
    await openList(page, run);
    const rows = await listRows(page);
    const mine = rows.filter((cells) => cells.some((cell) => cell.includes(run)));
    expect(mine.length, 'the filter should find both of this run\'s transactions').toBe(2);

    const income = mine.find((cells) => cells.some((c) => c.includes(' income')));
    const expense = mine.find((cells) => cells.some((c) => c.includes(' expense')));
    expect(income?.join(' '), 'the income row should carry the tax inclusive total as a credit')
      .toContain('+$110.00');
    expect(expense?.join(' '), 'and the expense row should carry its amount as a debit')
      .toContain('-$50.25');

    const totals = rows.find((cells) => cells.some((cell) => /Totals:/.test(cell)));
    expect(totals, 'the list should print a totals row').toBeTruthy();
    const figures = (totals as string[]).filter((cell) => /[0-9]/.test(cell) && /\$/.test(cell));
    expect(figures.map(money), 'credit and debit should total exactly what was entered')
      .toEqual([110, 50.25]);

    // --- delete both, with the button the screen shows and the confirm it raises
    page.on('dialog', (d) => d.accept());
    for (const financeId of [incomeId, expenseId]) {
      await openTransaction(page, financeId);
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.locator('input[name="butt_del"]').first().click(),
      ]);
    }

    await openList(page, run);
    const left = (await listRows(page)).filter((cells) => cells.some((cell) => cell.includes(run)));
    expect(left, 'both deleted transactions should be gone from the list').toEqual([]);
  });

  test('saves and redirects when the form carries no account field', async ({ page }) => {
    await login(page);

    const run = tag();
    const date = await today(page);
    const key = await formAuthKey(page);

    // module_security::check_page() strips the form elements a user is not allowed to
    // change, so a save can arrive without the account select. The handler read the key
    // regardless, which warned twice under E_ALL and - because that printed before the
    // redirect - left the save answering 200 with the warnings as its body.
    const response = await page.request.post('/?m[]=finance', {
      form: {
        _process: 'save_finance',
        finance_id: 'new',
        name: run + ' no account',
        transaction_date: date,
        type: 'i',
        sub_amount: '1.00',
        taxable_amount: '0.00',
        amount: '1.00',
        currency_id: '1',
        form_auth_key: key,
      },
      maxRedirects: 0,
      failOnStatusCode: false,
    });

    expect(await response.text(), 'the save should not print a PHP diagnostic')
      .not.toMatch(/Warning|Notice|Deprecated|Fatal error|headers already sent/i);
    expect(response.status(), 'and it should redirect to the saved transaction').toBe(302);

    const financeId = new URL(response.headers()['location'] || '', 'http://localhost')
      .searchParams.get('finance_id');
    expect(financeId, 'the redirect should name the new transaction').toBeTruthy();

    await openTransaction(page, String(financeId));
    await expect(page.locator('input[name="name"]').first()).toHaveValue(run + ' no account');

    page.on('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);
  });

  test('records a transaction from the quick add row', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const run = tag();

    // The quick add row lives on the transaction list itself and posts a different
    // handler from the full form - credit/debit rather than type/amount.
    await openList(page, run);
    const form = page.locator('#quick_add_form');
    await form.locator('input[name="name"]').first().fill(run + ' quick');
    await form.locator('input[name="description"]').first().fill('quick add probe');
    await form.locator('input[name="debit"]').first().fill('33.33');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      form.locator('input[name="addnew"]').first().click(),
    ]);

    await openList(page, run);
    const rows = await listRows(page);
    const mine = rows.filter((cells) => cells.some((cell) => cell.includes(run)));
    expect(mine.length, 'the quick add row should have created one transaction').toBe(1);
    expect(mine[0].join(' '), 'and it should be a debit of what was typed').toContain('-$33.33');
    expect(mine[0].join(' '), 'with its description').toContain('quick add probe');

    const totals = rows.find((cells) => cells.some((cell) => /Totals:/.test(cell))) as string[];
    const figures = totals.filter((cell) => /[0-9]/.test(cell) && /\$/.test(cell));
    expect(figures.map(money), 'nothing on the credit side, 33.33 on the debit side')
      .toEqual([0, 33.33]);

    // --- and it comes off again
    const link = await page.locator('a[href*="finance_id="]')
      .filter({ hasText: run }).first().getAttribute('href');
    const financeId = new URL(String(link), 'http://localhost').searchParams.get('finance_id');
    expect(financeId, 'the list should link to the new transaction').toBeTruthy();

    page.on('dialog', (d) => d.accept());
    await openTransaction(page, String(financeId));
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);

    await openList(page, run);
    const left = (await listRows(page)).filter((cells) => cells.some((cell) => cell.includes(run)));
    expect(left, 'the deleted transaction should be gone').toEqual([]);
  });

  test('tax report breaks a transaction into sub total, tax and total', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const run = tag();
    // A day of its own, far enough out that nothing else writes to it, so the report's
    // footer covers these two transactions and nothing else and the figures can be
    // checked exactly. input_date() reads an ISO date whatever the configured format is.
    const day = '2031-' + String(1 + Math.floor(Math.random() * 12)).padStart(2, '0')
      + '-' + String(1 + Math.floor(Math.random() * 28)).padStart(2, '0');

    // Posted with no sub_amount, which is the shape of every transaction recorded before
    // the column existed and of every one the quick add row recorded until this round.
    // get_finances() has to work the sub total back out of the amount and the tax, or the
    // report prints an empty sub total beside a full total and its own footer disagrees
    // with the total column next to it.
    const incomeId = await createRecord(page, '/?m[]=finance', {
      _process: 'save_finance',
      finance_id: 'new',
      name: run + ' taxed income',
      transaction_date: day,
      type: 'i',
      taxable_amount: '200.00',
      'tax_ids[]': '0',
      'tax_names[]': 'TAX',
      'tax_percents[]': '10',
      'tax_amount[]': '20.00',
      amount: '220.00',
      currency_id: '1',
      finance_account_id: '',
    }, 'finance_id');

    const expenseId = await createRecord(page, '/?m[]=finance', {
      _process: 'save_finance',
      finance_id: 'new',
      name: run + ' untaxed expense',
      transaction_date: day,
      type: 'e',
      taxable_amount: '0.00',
      amount: '50.25',
      currency_id: '1',
      finance_account_id: '',
    }, 'finance_id');

    await page.goto('/finance.finance/statistic.statistic_tax/?search[type]=ie'
      + '&search[date_from]=' + day + '&search[date_to]=' + day);
    await page.waitForLoadState('load');

    // The report's column count depends on which tax rates appear in the data, so read
    // the roles off the second header row rather than assuming a layout.
    const report = await page.evaluate(() => {
      const table = document.querySelector('table.tableclass') as HTMLTableElement | null;
      if (!table || !table.tHead || !table.tFoot) {
        return null;
      }
      const text = (cell: Element) => ((cell as HTMLElement).innerText || '').replace(/\s+/g, ' ').trim();
      const roles = Array.from(table.tHead.rows[1].cells).map(text);
      const footer = Array.from(table.tFoot.rows[0].cells).map(text);
      const body = Array.from(table.tBodies[0].rows).map((row) => Array.from(row.cells).map(text));

      // "Totals:" then one empty cell spanning the four name columns, then one per role.
      return { roles, figures: footer.slice(2, 2 + roles.length), body };
    });
    expect(report, 'the tax report should render its table').toBeTruthy();
    const { roles, figures, body } = report as { roles: string[]; figures: string[]; body: string[][] };

    expect(body.length, 'the day should hold only these two transactions').toBe(2);
    expect(roles, 'income then expense, with the one tax rate in the data')
      .toEqual(['Sub-Total', 'Tax 10%', 'Total', 'Sub-Total', 'Total']);
    expect(figures.map(money), '200 + 20 = 220 on one side, 50.25 on the other')
      .toEqual([200, 20, 220, 50.25, -50.25]);

    const income = body.find((cells) => cells.some((cell) => cell.includes('taxed income')));
    expect(income?.join(' '), 'the income row should be broken down')
      .toContain('$200.00');
    expect(income?.join(' '), 'with its tax').toContain('$20.00');
    expect(income?.join(' '), 'and its tax inclusive total').toContain('+$220.00');

    const expense = body.find((cells) => cells.some((cell) => cell.includes('untaxed expense')));
    expect(expense?.join(' '), 'an untaxed expense is its own sub total')
      .toContain('$50.25');

    page.on('dialog', (d) => d.accept());
    for (const financeId of [incomeId, expenseId]) {
      await openTransaction(page, financeId);
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.locator('input[name="butt_del"]').first().click(),
      ]);
    }
  });
});

test.describe('Recurring transactions', () => {
  test('advances the schedule by one period when a payment is recorded', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const run = tag();
    const date = await today(page);

    const key = await formAuthKey(page);
    const response = await page.request.post('/?m[]=finance', {
      form: {
        _process: 'save_recurring',
        finance_recurring_id: 'new',
        name: run + ' monthly',
        description: 'recurring probe',
        amount: '25.50',
        currency_id: '1',
        type: 'e',
        recurringtype: 'recurring',
        start_date: date,
        end_date: '',
        days: '0',
        months: '1',
        years: '0',
        finance_account_id: '',
        form_auth_key: key,
      },
      maxRedirects: 0,
      failOnStatusCode: false,
    });
    expect(response.status(), 'saving a recurring transaction should be accepted').toBeLessThan(400);

    // The save redirects to the listing rather than to the record, so find it by name.
    await page.goto('/finance.finance/finance.recurring/');
    await page.waitForLoadState('load');

    const recurringId = await page.evaluate((needle) => {
      const link = Array.from(document.querySelectorAll('a'))
        .find((a) => (a.innerText || '').includes(needle) && /finance_recurring_id=/.test(a.href));

      return link ? new URL(link.href).searchParams.get('finance_recurring_id') : null;
    }, run);
    expect(recurringId, 'the new recurring transaction should be in the upcoming list').toBeTruthy();

    /** The dates the upcoming list projects for this schedule, in order. */
    const schedule = async (): Promise<string[]> => {
      await page.goto('/finance.finance/finance.recurring/');
      await page.waitForLoadState('load');

      return page.evaluate((needle) => {
        return Array.from(document.querySelectorAll('tr'))
          .filter((row) => (row as HTMLElement).innerText.includes(needle))
          .map((row) => ((row as HTMLTableRowElement).cells[0] as HTMLElement).innerText
            .replace(/\s+/g, ' ').trim());
      }, run);
    };

    const before = await schedule();
    expect(before.length, 'the list should project several occurrences').toBeGreaterThan(2);
    expect(before[0], 'the first one falls on the start date, which is today').toContain(date);

    const gap = Number((before[1].match(/\((\d+) days?\)/) || [])[1]);
    expect(gap, 'one month on is 28 to 31 days away').toBeGreaterThanOrEqual(28);
    expect(gap, 'one month on is 28 to 31 days away').toBeLessThanOrEqual(31);

    // --- record the payment, from the form the Record link opens, with its own button
    await page.goto('/finance.finance/finance.recurring?finance_id=new&record_new=1'
      + '&finance_recurring_id=' + recurringId);
    await page.waitForLoadState('load');
    await expect(page.locator('input[name="name"]').first(), 'the form should be prefilled')
      .toHaveValue(run + ' monthly');
    await expect(page.locator('input[name="amount"]').first()).toHaveValue('25.50');

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_save"]').first().click(),
    ]);

    // --- the schedule should have moved on by exactly one occurrence
    const after = await schedule();
    expect(after.length, 'the schedule should still be running').toBeGreaterThan(1);
    expect(after[0].replace(/\s*\(.*\)/, ''),
      'the next due date should be what was the second occurrence')
      .toBe(before[1].replace(/\s*\(.*\)/, ''));

    // --- and the payment itself is a real transaction
    await openList(page, run);
    const rows = (await listRows(page)).filter((cells) => cells.some((cell) => cell.includes(run)));
    expect(rows.length, 'recording the payment should create one transaction').toBe(1);
    expect(rows[0].join(' '), 'for the recurring amount, as a debit').toContain('-$25.50');

    const link = await page.locator('a[href*="finance_id="]')
      .filter({ hasText: run }).first().getAttribute('href');
    const financeId = new URL(String(link), 'http://localhost').searchParams.get('finance_id');

    // --- clean up: the schedule, then the transaction it created
    page.on('dialog', (d) => d.accept());
    await page.goto('/finance.finance/finance.recurring/?finance_recurring_id=' + recurringId);
    await page.waitForLoadState('load');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);
    expect(await schedule(), 'the deleted schedule should be gone from the upcoming list')
      .toEqual([]);

    await openTransaction(page, String(financeId));
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);
    await openList(page, run);
    expect((await listRows(page)).filter((cells) => cells.some((cell) => cell.includes(run))),
      'and so should the transaction it recorded').toEqual([]);
  });
});
