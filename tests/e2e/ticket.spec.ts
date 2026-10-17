import { test, expect, Page } from '@playwright/test';
import { describeDiagnostics } from './diagnostics';
import { aCustomer, formAuthKey, login } from './seed';

/**
 * A support ticket, from raised to replied to billed to deleted.
 *
 * The thing worth proving here is not that the pages render - the smoke suite does that
 * - but that the two sides of a ticket stay apart. An internal note has to stay off the
 * page the customer reads, a customer's reply has to land back on the ticket, and the
 * priority support invoice has to bill once.
 *
 * It billed twice. `generate_priority_invoice()` guards on the ticket's own
 * `invoice_id`, and read it back through the object cache, which still held the row
 * from before the first invoice was written - so a customer who asked for priority
 * support twice inside the cache timeout got two invoices for it.
 *
 * The IMAP side of tickets - collecting mail from a mailbox - is not exercised here and
 * is not covered anywhere; it needs a real mail account.
 */

function uniqueName(prefix: string): string {
  return `${prefix} ${Date.now().toString(36)}${Math.floor(Math.random() * 1e4)}`;
}

async function saveTicket(page: Page, form: Record<string, string>) {
  const key = await formAuthKey(page);
  const response = await page.request.post('/?m[]=ticket', {
    form: { _process: 'save_ticket', _redirect: '', ...form, form_auth_key: key },
    maxRedirects: 0,
    failOnStatusCode: false,
  });
  expect(response.status(), 'the ticket save should be accepted').toBeLessThan(400);

  return response;
}

async function openTicket(page: Page, ticketId: string) {
  await page.goto('/?m[]=ticket&p[]=ticket_admin&ticket_id=' + ticketId);
  await page.waitForLoadState('load');
}

/** Search the ticket list and return the ids that come back. */
async function searchTickets(page: Page, term: string): Promise<string[]> {
  await page.goto('/?m[]=ticket&p[]=ticket_admin_list&search[generic]=' + encodeURIComponent(term));
  await page.waitForLoadState('load');

  return page.evaluate(() => {
    const ids = new Set<string>();
    document.querySelectorAll('a[href*="ticket_id="]').forEach((a: any) => {
      const m = /ticket_id=(\d+)/.exec(a.getAttribute('href') || '');
      if (m && m[1] !== '0') {
        ids.add(m[1]);
      }
    });

    return Array.from(ids);
  });
}

test.describe('Tickets', () => {
  test('keeps internal notes off the customer page and takes the customer reply back', async ({ page, browser }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const subject = uniqueName('Ticket walk');
    const common = {
      subject,
      customer_id: customerId,
      change_user_id: '1',
      assigned_user_id: '1',
      status_id: '2',
      priority: '0',
    };

    await saveTicket(page, { ticket_id: 'new', ...common });
    const found = await searchTickets(page, subject);
    expect(found, 'the ticket that was just raised should be findable by subject').toHaveLength(1);
    const ticketId = found[0];

    // --- read it back, field by field
    await openTicket(page, ticketId);
    expect(
      describeDiagnostics(await page.content()),
      'the ticket screen should not print PHP diagnostics',
    ).toEqual([]);
    await expect(
      page.locator('h1, h2, h3').first(),
      'the ticket should read back under the subject it was raised with',
    ).toContainText(subject);

    const publicPath = ((await page.content()).match(
      /external\/m\.ticket\/h\.public\/i\.\d+\/hash\.[a-f0-9]+/,
    ) || [])[0];
    expect(publicPath, 'the ticket screen should publish a link for the customer').toBeTruthy();

    // --- an internal note
    const note = `Internal note ${Date.now().toString(36)}`;
    await saveTicket(page, {
      ticket_id: ticketId,
      ...common,
      new_ticket_message: note,
      private_message: '1',
      change_status_id: '2',
      newmsg: 'Submit Message',
    });

    await openTicket(page, ticketId);
    await expect(page.locator('body'), 'staff should see the internal note').toContainText(note);

    // --- the page the customer is sent, loaded the way a customer loads it: no session
    const stranger = await browser.newContext();
    const strangerPage = await stranger.newPage();
    await strangerPage.goto('/' + String(publicPath));
    await strangerPage.waitForLoadState('load');
    expect(
      describeDiagnostics(await strangerPage.content()),
      'the public ticket page should not print PHP diagnostics',
    ).toEqual([]);
    await expect(strangerPage.locator('body'), 'the customer should see the ticket')
      .toContainText('Ticket');
    await expect(
      strangerPage.locator('body'),
      'but an internal note must never reach the customer',
    ).not.toContainText(note);

    // --- a reply meant for the customer
    const answer = `Public answer ${Date.now().toString(36)}`;
    await saveTicket(page, {
      ticket_id: ticketId,
      ...common,
      new_ticket_message: answer,
      change_status_id: '3',
      newmsg: 'Submit Message',
    });

    await strangerPage.reload();
    await strangerPage.waitForLoadState('load');
    await expect(strangerPage.locator('body'), 'a reply that is not private should reach them')
      .toContainText(answer);
    await expect(
      strangerPage.locator('body'),
      'and the internal note still should not',
    ).not.toContainText(note);

    await openTicket(page, ticketId);
    await expect(
      page.locator('select[name="status_id"]'),
      'the status the reply was sent with should have been applied',
    ).toHaveValue('3');

    // --- the customer replies, from the public page
    const reply = `Customer reply ${Date.now().toString(36)}`;
    // The reply box is folded away until the customer asks for it.
    await strangerPage.locator('input[name="reply"]').first().click();
    await strangerPage.locator('textarea[name="new_ticket_message"]').fill(reply);
    await Promise.all([
      strangerPage.waitForNavigation({ waitUntil: 'load' }),
      strangerPage.locator('input[name="newmsg"]').first().click(),
    ]);
    await expect(strangerPage.locator('body'), "the customer's own reply should be on their page")
      .toContainText(reply);
    await stranger.close();

    await openTicket(page, ticketId);
    await expect(page.locator('body'), 'and it should land on the ticket for staff')
      .toContainText(reply);

    // --- delete it, through the confirmation screen the app really shows
    page.on('dialog', (d) => d.accept());
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"]').first().click(),
    ]);
    await expect(page.locator('body'), 'deleting should ask for confirmation first')
      .toContainText(/really delete/i);

    // Press the button the confirmation screen actually shows. Submitting the form from
    // script with a made up butt_del value looks equivalent and is not - the handler never
    // deletes, and the test then reports the app as broken when it is not.
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'load' }),
      page.locator('input[name="butt_del"][value="Confirm Delete"], button[name="butt_del"]').first().click(),
    ]);

    await expect(page.locator('body'), 'the app should say it deleted the ticket')
      .toContainText(/ticket deleted successfully/i);

    expect(await searchTickets(page, subject), 'the deleted ticket should be gone from the list')
      .not.toContain(ticketId);
  });

  test('bills priority support once, however many times it is asked for', async ({ page, browser }) => {
    test.setTimeout(180000);
    await login(page);

    const customerId = await aCustomer(page);
    const subject = uniqueName('Ticket priority');
    await saveTicket(page, {
      ticket_id: 'new',
      subject,
      customer_id: customerId,
      change_user_id: '1',
      assigned_user_id: '1',
      status_id: '2',
      priority: '0',
    });
    const found = await searchTickets(page, subject);
    expect(found, 'the ticket should be findable by subject').toHaveLength(1);
    const ticketId = found[0];

    await openTicket(page, ticketId);
    const publicPath = ((await page.content()).match(
      /external\/m\.ticket\/h\.public\/i\.\d+\/hash\.[a-f0-9]+/,
    ) || [])[0];
    expect(publicPath, 'the ticket should publish a public link').toBeTruthy();

    // The customer asks for priority support twice - a double click, a refresh of the
    // page it lands on, a second visit inside the cache window. Each has to land on the
    // same invoice.
    const customer = await browser.newContext();
    const customerPage = await customer.newPage();
    const invoiceUrls: string[] = [];
    for (let attempt = 0; attempt < 2; attempt++) {
      const response = await customerPage.request.post('/' + String(publicPath), {
        form: { _process: 'send_public_ticket', generate_priority_invoice: '1' },
        maxRedirects: 0,
        failOnStatusCode: false,
      });
      const location = response.headers()['location'] || '';
      expect(location, 'asking for priority support should land on an invoice').toContain('invoice');
      invoiceUrls.push(location);
    }
    expect(
      new URL(invoiceUrls[1], 'http://localhost').searchParams.get('i'),
      'asking twice must not raise a second invoice for the same ticket',
    ).toBe(new URL(invoiceUrls[0], 'http://localhost').searchParams.get('i'));

    await customerPage.goto(invoiceUrls[0]);
    await customerPage.waitForLoadState('load');
    expect(
      describeDiagnostics(await customerPage.content()),
      'the priority support invoice should not print PHP diagnostics',
    ).toEqual([]);
    const invoiceBody = (await customerPage.locator('body').innerText()).replace(/\s+/g, ' ');
    expect(invoiceBody, 'the invoice should say which ticket it is for').toContain('Ticket #');
    expect(invoiceBody, 'and bill something for it').toMatch(/\$[1-9][\d,]*\.\d\d/);
    await customer.close();

    // --- and the ticket still answers for staff afterwards
    await openTicket(page, ticketId);
    expect(
      describeDiagnostics(await page.content()),
      'the ticket screen should still be clean after raising the invoice',
    ).toEqual([]);
  });
});
