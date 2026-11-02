import { test, expect, Page } from '@playwright/test';
import { anInvoice, formAuthKey, login } from './seed';

/**
 * Sending email through the app, end to end, into the mailhog container.
 *
 * The email plugin builds its messages with PHPMailer 6 from Composer. The unit tests
 * check the message it assembles; this checks the whole path - the compose screen, the
 * queue, SMTP with the saved settings, the attachment on disk - against a real SMTP
 * server, and reads the result back from mailhog's API.
 *
 * The app talks to mailhog by its compose service name; the test talks to it on the
 * published port. The SMTP settings are saved for the duration and put back afterwards,
 * whatever they were.
 */

const MAILHOG_API = process.env.UCM_MAILHOG_API || 'http://localhost:8025';
const MAILHOG_SMTP_FROM_APP = process.env.UCM_MAILHOG_SMTP || 'mailhog:1025';

const SETTING_KEYS = [
  'email_smtp',
  'email_smtp_hostname',
  'email_smtp_auth',
  'email_smtp_authentication',
  'email_smtp_username',
  'email_smtp_password',
];

type Saved = Record<string, string>;

async function readSettings(page: Page): Promise<Saved> {
  await page.goto('/?m[]=email&p[]=email_settings');
  return page.evaluate((keys) => {
    const saved: Record<string, string> = {};
    for (const key of keys) {
      const field = document.querySelector(`[name="config[${key}]"]`) as HTMLInputElement | HTMLSelectElement | null;
      if (!field) {
        continue;
      }
      if (field instanceof HTMLInputElement && field.type === 'checkbox') {
        saved[key] = field.checked ? field.value || '1' : '';
      } else {
        saved[key] = field.value;
      }
    }
    return saved;
  }, SETTING_KEYS);
}

async function saveSettings(page: Page, values: Saved) {
  const form: Record<string, string> = { _process: 'save_config', form_auth_key: await formAuthKey(page) };
  for (const [key, value] of Object.entries(values)) {
    form[`config[${key}]`] = value;
  }
  const response = await page.request.post('/?m[]=config&p[]=config_settings', { form, maxRedirects: 0, failOnStatusCode: false });
  expect(response.status(), 'saving the SMTP settings').toBeLessThan(400);
}

interface MailhogMessage {
  ID: string;
  Raw: { From: string; To: string[]; Data: string };
  Content: { Headers: Record<string, string[]>; Body: string };
  MIME: { Parts: Array<{ Headers: Record<string, string[]>; Body: string; MIME: { Parts: Array<{ Headers: Record<string, string[]>; Body: string }> } | null }> } | null;
}

async function findBySubject(page: Page, subject: string): Promise<MailhogMessage | null> {
  for (let attempt = 0; attempt < 20; attempt++) {
    const response = await page.request.get(`${MAILHOG_API}/api/v2/search?kind=containing&query=${encodeURIComponent(subject)}`);
    expect(response.ok(), 'mailhog should answer').toBeTruthy();
    const body = await response.json();
    if (body.items && body.items.length) {
      return body.items[0] as MailhogMessage;
    }
    await page.waitForTimeout(250);
  }
  return null;
}

async function forget(page: Page, message: MailhogMessage | null) {
  if (message) {
    // Only this message: the mailbox is shared with whatever else is being tried.
    await page.request.delete(`${MAILHOG_API}/api/v1/messages/${message.ID}`, { failOnStatusCode: false });
  }
}

function allHeaders(message: MailhogMessage): string {
  return Object.entries(message.Content.Headers)
    .map(([name, values]) => values.map((value) => `${name}: ${value}`).join('\n'))
    .join('\n');
}

test.describe('Sending email through mailhog', () => {
  let original: Saved = {};

  test.beforeEach(async ({ page }) => {
    await login(page);
    original = await readSettings(page);
    await saveSettings(page, {
      email_smtp: '1',
      email_smtp_hostname: MAILHOG_SMTP_FROM_APP,
      email_smtp_auth: '',
      email_smtp_authentication: '',
      email_smtp_username: '',
      email_smtp_password: '',
    });
  });

  test.afterEach(async ({ page }) => {
    await saveSettings(page, original);
  });

  test('the settings test email arrives as HTML', async ({ page }) => {
    const address = `settings-test-${Date.now()}@example.net`;
    await page.goto('/?m[]=email&p[]=email_settings');
    await page.locator('input[name="email"]').fill(address);
    await page.locator('input[name="send"]').click();
    await expect(page.locator('body')).toContainText('Test email sent successfully');

    const message = await findBySubject(page, address);
    try {
      expect(message, 'the test email should reach mailhog').not.toBeNull();
      expect(message!.Raw.To).toContain(address);
      expect(allHeaders(message!)).toMatch(/Test Email from/);
      expect(allHeaders(message!)).toMatch(/multipart\/alternative/i);
      expect(message!.Raw.Data).toMatch(/Content-Type: text\/html/i);
      expect(message!.Raw.Data).toMatch(/Content-Type: text\/plain/i);
    } finally {
      await forget(page, message);
    }
  });

  test('an invoice email with attachments and a bcc arrives intact', async ({ page }) => {
    const invoiceId = await anInvoice(page);
    const marker = `Mailhog check ${Date.now()}`;
    const bcc = `bcc-${Date.now()}@example.net`;
    const to = `to-${Date.now()}@example.net`;

    await page.goto(`/?m[]=invoice&p[]=invoice_admin&invoice_id=${invoiceId}&email=true`);
    const form = page.locator('form:has(input[name="_process"][value="send_email"])');

    // A customer with no contact on it offers an empty address box instead of a list.
    const recipientBox = form.locator('input[name="custom_to[]"]');
    if (await recipientBox.count()) {
      await recipientBox.first().fill(to);
    }
    // The invoice PDF is attached by the app itself and listed on the screen.
    const listsPdf = /\.pdf/i.test((await form.textContent()) ?? '');
    await form.locator('input[name="subject"]').fill(marker);
    await form.locator('input[name="bcc"]').fill(bcc);
    await form.locator('input[type="file"][name="manual_attachment[]"]').first().setInputFiles({
      name: 'terms & notes.txt',
      mimeType: 'text/plain',
      buffer: Buffer.from('Attachment body for the mailhog check\n'),
    });
    await form.locator('textarea[name="html_content"]').evaluate((el, html) => {
      (el as HTMLTextAreaElement).value = html;
    }, `<p>Hello from <strong>${marker}</strong> - Grüße</p>`);
    await form.locator('input[type="submit"][name="send"], button[name="send"]').first().click();

    const message = await findBySubject(page, marker);
    try {
      expect(message, 'the invoice email should reach mailhog').not.toBeNull();
      const raw = message!.Raw.Data;
      // Envelope: the blind copy was delivered, but is not written in the message.
      expect(message!.Raw.To).toContain(bcc);
      expect(raw).not.toContain(`To: ${bcc}`);
      expect(raw).not.toMatch(new RegExp(`^(To|Cc):.*${bcc.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`, 'mi'));
      // Structure: mixed, holding the text/HTML alternative and the attachment.
      expect(allHeaders(message!)).toMatch(/multipart\/mixed/i);
      expect(raw).toMatch(/multipart\/alternative/i);
      expect(raw).toMatch(/Content-Type: text\/html/i);
      expect(raw).toMatch(/Content-Disposition: attachment; filename="terms & notes\.txt"/i);
      if (listsPdf) {
        expect(raw).toMatch(/Content-Type: application\/pdf/i);
        expect(raw).toMatch(/filename="?[^";\r\n]+\.pdf/i);
      }
      // The body and the non-ASCII text survived.
      const decoded = await page.request.get(`${MAILHOG_API}/api/v1/messages/${message!.ID}/download`);
      expect(await decoded.text()).toContain(marker);
    } finally {
      await forget(page, message);
    }
  });
});
