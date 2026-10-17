import { test, expect, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, formAuthKey, login } from './seed';

/**
 * A job, its tasks, and the money they add up to.
 *
 * `quote-to-invoice.spec.ts` walks a job that was built from a quote, with fixed-amount
 * tasks. Nothing covers a job created on its own, and nothing covers the hourly side at
 * all - which is the arithmetic a time-and-materials business actually bills on. An
 * hourly task has no amount of its own: the amount is the job's hourly rate multiplied
 * by the hours, worked out on every read. So a job whose hourly rate quietly changes
 * re-prices every task on it, and nothing would say so.
 *
 * That is not hypothetical. `save_job()` used to fill in a blank hourly rate from the
 * customer default *whenever the key was absent*, so a save that carried only tasks -
 * which is exactly what the job task importer does - wrote the global default over the
 * job's real rate and re-priced every hourly task on it, after warning on an undefined
 * `customer_id` and breaking its own redirect. The third test here is that regression.
 */

function uniqueName(prefix: string): string {
  return `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1e4)}`;
}

/** Post to the job module with a real form token, the way the browser would. */
async function saveJob(page: Page, form: Record<string, string>): Promise<string> {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=job', {
    form: { _process: 'save_job', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the job save should be accepted').toBeLessThan(400);

  return response.headers()['location'] || '';
}

/** The fields the job form always posts. Leaving any of them out is a different test. */
function jobFields(name: string, customerId: string, hourlyRate: string): Record<string, string> {
  return {
    name,
    customer_id: customerId,
    hourly_rate: hourlyRate,
    status: 'New',
    type: 'Website Design',
    currency_id: '1',
    default_task_type: '0', // _TASK_TYPE_HOURS_AMOUNT
    'tax_ids[]': '0',
    'tax_names[]': 'TAX',
    'tax_percents[]': '10',
  };
}

/**
 * The money a summary table prints next to a label, e.g. "Sub Total:".
 *
 * The value has to look like money. The job screen also carries a timer block whose
 * "Total:" is a duration, and in debug mode a diagnostics table of numbers, and either
 * would otherwise answer first.
 */
async function amountLabelled(page: Page, label: RegExp): Promise<string | null> {
  return page.evaluate((source) => {
    const pattern = new RegExp(source, 'i');
    for (const cell of Array.from(document.querySelectorAll('td,th'))) {
      const text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
      if (pattern.test(text) && text.length < 40) {
        const next = cell.nextElementSibling;
        const value = next ? (next.textContent || '').replace(/\s+/g, ' ').trim() : '';
        if (/^[^0-9]{0,3}-?[\d,]+\.\d{2}$/.test(value)) {
          return value;
        }
      }
    }

    return null;
  }, label.source);
}

async function openJob(page: Page, jobId: string) {
  await page.goto('/?m[]=job&p[]=job_admin&job_id=' + jobId);
  await page.waitForLoadState('load');
}

/**
 * The task rows on the job screen, keyed by description.
 *
 * Each row carries its own task id in `rel`, which is the only place the page publishes
 * it. Completed tasks are hidden by default, so this reads textContent rather than
 * innerText - a hidden row's innerText is empty.
 */
async function taskRows(page: Page): Promise<Record<string, { id: string; text: string }>> {
  return page.evaluate(() => {
    const rows: Record<string, { id: string; text: string }> = {};
    document.querySelectorAll('#job_task_listing tr.task_preview[rel]').forEach((tr) => {
      // An invoiced, completed task is locked, and then its description is plain text
      // rather than an edit link - so the cell is read whole, with the furniture that
      // shares it (the discussion link, the timer button) taken out first.
      const cell = tr.querySelector('td:nth-child(2)');
      if (!cell) {
        return;
      }
      const copy = cell.cloneNode(true) as HTMLElement;
      copy.querySelectorAll('.task_job_discussion, .task_job_discussion_holder, .timer_task')
        .forEach((el) => el.remove());
      const description = (copy.textContent || '').replace(/\s+/g, ' ').trim();
      if (!description) {
        return;
      }
      const text = Array.from(tr.querySelectorAll('td'))
        .map((td) => (td.textContent || '').replace(/\s+/g, ' ').trim())
        .join(' | ');
      rows[description] = { id: String(tr.getAttribute('rel')), text };
    });

    return rows;
  });
}

/** Search the job list, which is also its own filter screen. */
async function searchJobs(page: Page, term: string): Promise<string[]> {
  await page.goto('/?m[]=job&p[]=job_admin');
  await page.waitForLoadState('load');
  const search = page.locator('input[name="search[generic]"]').first();
  await search.fill(term);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), search.press('Enter')]);

  return page.evaluate(() => {
    const ids = new Set<string>();
    document.querySelectorAll('a[href*="job_id="]').forEach((a: any) => {
      const m = /job_id=(\d+)/.exec(a.getAttribute('href') || '');
      if (m && m[1] !== '0') {
        ids.add(m[1]);
      }
    });

    return Array.from(ids);
  });
}

async function expectClean(page: Page, step: string) {
  const problems = describeDiagnostics(await page.content());
  expect(problems, `${step} printed PHP diagnostics:\n` + problems.join('\n')).toEqual([]);
}

test.describe('Jobs and tasks', () => {
  test('prices hourly tasks off the job rate, totals them, and taxes the total', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const customerId = await aCustomer(page);
    const name = uniqueName('Job hourly');
    const location = await saveJob(page, { job_id: 'new', ...jobFields(name, customerId, '80') });
    const jobId = new URL(location, 'http://localhost').searchParams.get('job_id');
    expect(jobId, 'saving a new job should redirect to it').toBeTruthy();

    // Two hourly tasks, posted one at a time the way the task form posts them. Neither
    // carries an amount: the amount is the job's rate times the hours, and that is the
    // whole point of the hourly task type.
    for (const [description, hours] of [['Hourly first', '2'], ['Hourly second', '3']]) {
      await saveJob(page, {
        job_id: String(jobId),
        ...jobFields(name, customerId, '80'),
        'job_task[new][description]': description,
        'job_task[new][hours]': hours,
        'job_task[new][manual_task_type]': '-1',
        'job_task[new][billable_t]': '1',
        'job_task[new][billable]': '1',
        'job_task[new][taxable_t]': '1',
        'job_task[new][taxable]': '1',
      });
    }

    await openJob(page, String(jobId));
    await expectClean(page, 'the job screen');

    const rows = await taskRows(page);
    expect(Object.keys(rows).sort(), 'both tasks should be on the job').toEqual(
      ['Hourly first', 'Hourly second'],
    );
    expect(rows['Hourly first'].text, '2 hours at 80 an hour is 160').toContain('160.00');
    expect(rows['Hourly second'].text, '3 hours at 80 an hour is 240').toContain('240.00');

    expect(await amountLabelled(page, /^Sub Total:?$/), '160 and 240 sub-total to 400')
      .toContain('400.00');
    expect(await amountLabelled(page, /^Tax:?$/), '10% of 400 is 40').toContain('40.00');
    expect(await amountLabelled(page, /^Total:?$/), '400 plus 40 tax is 440').toContain('440.00');

    const body = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(body, '2 and 3 hours is 5 hours of work on the job').toMatch(/5:00 Hours Total/);
    expect(body, 'and none of it done yet').toMatch(/0:00 Hours Done/);

    // --- the rate is what prices the work, so changing it re-prices every hourly task
    await saveJob(page, { job_id: String(jobId), ...jobFields(name, customerId, '100') });
    await openJob(page, String(jobId));
    const repriced = await taskRows(page);
    expect(repriced['Hourly first'].text, '2 hours at the new 100 rate is 200').toContain('200.00');
    expect(repriced['Hourly second'].text, '3 hours at the new 100 rate is 300').toContain('300.00');
    expect(await amountLabelled(page, /^Sub Total:?$/), 'and the job sub-totals to 500')
      .toContain('500.00');
  });

  test('completes a task, logs its hours, and bills exactly that task', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const name = uniqueName('Job billing');
    const location = await saveJob(page, { job_id: 'new', ...jobFields(name, customerId, '80') });
    const jobId = String(new URL(location, 'http://localhost').searchParams.get('job_id'));

    for (const [description, hours] of [['Hourly done', '2'], ['Hourly todo', '3']]) {
      await saveJob(page, {
        job_id: jobId,
        ...jobFields(name, customerId, '80'),
        'job_task[new][description]': description,
        'job_task[new][hours]': hours,
        'job_task[new][manual_task_type]': '-1',
        'job_task[new][billable_t]': '1',
        'job_task[new][billable]': '1',
        'job_task[new][taxable_t]': '1',
        'job_task[new][taxable]': '1',
      });
    }

    await openJob(page, jobId);
    const tasks = await taskRows(page);
    expect(Object.keys(tasks).sort(), 'both tasks should be on the job')
      .toEqual(['Hourly done', 'Hourly todo']);
    const taskIds: Record<string, string> = {
      'Hourly done': tasks['Hourly done'].id,
      'Hourly todo': tasks['Hourly todo'].id,
    };

    // --- complete one of them. Completing an hourly task that has no logged time logs
    // the full estimate against it, which is what "Hours Done" counts.
    await saveJob(page, {
      job_id: jobId,
      ...jobFields(name, customerId, '80'),
      [`job_task[${taskIds['Hourly done']}][description]`]: 'Hourly done',
      [`job_task[${taskIds['Hourly done']}][hours]`]: '2',
      [`job_task[${taskIds['Hourly done']}][fully_completed_t]`]: '1',
      [`job_task[${taskIds['Hourly done']}][fully_completed]`]: '1',
    });

    await openJob(page, jobId);
    await expectClean(page, 'the job screen after completing a task');
    const afterComplete = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(afterComplete, 'the 2 hour task is done').toMatch(/2:00 Hours Done/);
    expect(afterComplete, 'and the other 3 hours are not').toMatch(/3:00 Hours \/ 1 Tasks Remain/);
    expect(afterComplete, '2 completed hours at 80 plus tax is 176 waiting to be invoiced')
      .toMatch(/Pending \$?176\.00 Invoice/);

    // --- invoice it, through the dialog the job's own button opens
    await page.locator('#job_generate_invoice_button').click();
    const boxes = page.locator('.invoice_create_task');
    await expect(boxes, "the dialog should offer the job's uninvoiced tasks")
      .toHaveCount(2, { timeout: 20000 });
    const completedBox = page.locator('.invoice_create_task[name*="' + taskIds['Hourly done'] + '"]');
    await expect(completedBox, 'a completed task should be ticked for invoicing by default')
      .toBeChecked();
    await expect(
      page.locator('.invoice_create_task[name*="' + taskIds['Hourly todo'] + '"]'),
      'an unfinished task should not be',
    ).not.toBeChecked();

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('.ui-dialog-buttonpane button').filter({ hasText: /^Create$/ }).first().click(),
    ]);
    expect(page.url(), 'Create should open a new invoice for the job').toContain('invoice_id=new');
    await expectClean(page, 'the invoice built from the job');

    const draft = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(draft, 'the completed task should be on the invoice').toContain('Hourly done');
    expect(draft, 'the unfinished one should not').not.toContain('Hourly todo');
    expect(draft, 'billed at 2 hours times the 80 rate').toContain('160.00');

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_save"]').first().click(),
    ]);
    await expect(page.locator('body'), 'the invoice should save').toContainText(/saved successfully/i);

    // --- the job now knows it has been invoiced for that work, and will not offer the
    // same task again.
    await openJob(page, jobId);
    const invoiced = (await page.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(invoiced, 'the job should record what it has invoiced').toMatch(/Invoiced:\s*\$?176\.00/);

    await page.locator('#job_generate_invoice_button').click();
    await expect(
      page.locator('.invoice_create_task'),
      'an invoiced task should not be offered for invoicing a second time',
    ).toHaveCount(1, { timeout: 20000 });

    // --- and it cannot be deleted out from under the invoice that bills it
    await saveJob(page, {
      job_id: jobId,
      ...jobFields(name, customerId, '80'),
      [`job_task[${taskIds['Hourly done']}][description]`]: '-DELETE-',
    });
    await openJob(page, jobId);
    expect(
      Object.keys(await taskRows(page)),
      'a task that has been invoiced must not be deletable',
    ).toContain('Hourly done');
  });

  test('a save that carries no hourly rate leaves the rate alone', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);

    const customerId = await aCustomer(page);
    const name = uniqueName('Job rate');
    const location = await saveJob(page, { job_id: 'new', ...jobFields(name, customerId, '123') });
    const jobId = String(new URL(location, 'http://localhost').searchParams.get('job_id'));

    await saveJob(page, {
      job_id: jobId,
      ...jobFields(name, customerId, '123'),
      'job_task[new][description]': 'Rate task',
      'job_task[new][hours]': '2',
      'job_task[new][manual_task_type]': '-1',
      'job_task[new][billable_t]': '1',
      'job_task[new][billable]': '1',
    });

    await openJob(page, jobId);
    await expect(page.locator('input[name="hourly_rate"]')).toHaveValue(/123/);
    expect((await taskRows(page))['Rate task'].text, '2 hours at 123 is 246').toContain('246.00');

    // A partial save - job id and tasks, no hourly rate. This is the shape the job task
    // importer posts, and it used to be enough to re-price the whole job.
    const key = await formAuthKey(page);
    const response = await page.request.post('/?m[]=job', {
      form: {
        _process: 'save_job',
        job_id: jobId,
        'job_task[new][description]': 'Imported task',
        'job_task[new][hours]': '1',
        'job_task[new][manual_task_type]': '-1',
        form_auth_key: key,
      },
      maxRedirects: 0,
      failOnStatusCode: false,
    });
    expect(
      describeDiagnostics(await response.text()),
      'a save that carries only tasks should not print PHP diagnostics',
    ).toEqual([]);

    await openJob(page, jobId);
    await expect(
      page.locator('input[name="hourly_rate"]'),
      'a save that never mentioned the hourly rate must not change it',
    ).toHaveValue(/123/);
    const rows = await taskRows(page);
    expect(rows['Rate task'].text, 'and the existing task is still priced at the job rate')
      .toContain('246.00');
  });

  test('deletes a task, re-totals the job, and deletes the job', async ({ page }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const name = uniqueName('Job delete');
    const location = await saveJob(page, { job_id: 'new', ...jobFields(name, customerId, '50') });
    const jobId = String(new URL(location, 'http://localhost').searchParams.get('job_id'));

    for (const [description, hours] of [['Hourly keep', '1'], ['Hourly drop', '4']]) {
      await saveJob(page, {
        job_id: jobId,
        ...jobFields(name, customerId, '50'),
        'job_task[new][description]': description,
        'job_task[new][hours]': hours,
        'job_task[new][manual_task_type]': '-1',
        'job_task[new][billable_t]': '1',
        'job_task[new][billable]': '1',
      });
    }

    await openJob(page, jobId);
    expect(await amountLabelled(page, /^Sub Total:?$/), '1 and 4 hours at 50 is 250')
      .toContain('250.00');
    const before = await taskRows(page);
    expect(Object.keys(before).sort(), 'both tasks should be on the job')
      .toEqual(['Hourly drop', 'Hourly keep']);
    const taskIds: Record<string, string> = {
      'Hourly keep': before['Hourly keep'].id,
      'Hourly drop': before['Hourly drop'].id,
    };

    // The task edit form deletes by posting the delete key as the description.
    await saveJob(page, {
      job_id: jobId,
      ...jobFields(name, customerId, '50'),
      [`job_task[${taskIds['Hourly drop']}][description]`]: '-DELETE-',
    });

    await openJob(page, jobId);
    const left = await taskRows(page);
    expect(Object.keys(left), 'the deleted task should be gone').toEqual(['Hourly keep']);
    expect(await amountLabelled(page, /^Sub Total:?$/), 'and the job should re-total without it')
      .toContain('50.00');

    // --- the job itself, through the confirmation screen the app really shows
    expect(await searchJobs(page, name), 'the job should be findable before it is deleted')
      .toContain(jobId);

    await openJob(page, jobId);
    page.on('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
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

    expect(await searchJobs(page, name), 'the deleted job should not come back from a search')
      .not.toContain(jobId);
  });
});
