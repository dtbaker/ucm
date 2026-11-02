#!/usr/bin/env node
/**
 * Drives the client's Upgrade screen for docs/local-upgrade-test.md, so a run does not need
 * a person at a browser. Uses the Playwright that the repository already has.
 *
 *   node scripts/upgrade-test-drive.mjs check                  Settings, Upgrade, Check for Upgrades: prints the message and each row
 *   node scripts/upgrade-test-drive.mjs upgrade                check, then Start the Upgrade, and wait for every row
 *   node scripts/upgrade-test-drive.mjs manual                 Run Manual Upgrades: prints the page text
 *   node scripts/upgrade-test-drive.mjs codes a b ...          type these codes in the box, then Check for Upgrades ("-" is an empty box)
 *   node scripts/upgrade-test-drive.mjs notice                 whether the "Set a private secret" warning shows on Settings and Upgrade
 *   node scripts/upgrade-test-drive.mjs page <path>            open a page as the admin: status, PHP error text, first lines
 *   node scripts/upgrade-test-drive.mjs help <path>            open a screen and its help panel (the ? button): prints the heading and summary
 *   node scripts/upgrade-test-drive.mjs crawl [max]            follow every link from the dashboard (skipping logout and anything that deletes), report any HTTP error or PHP error text
 *   node scripts/upgrade-test-drive.mjs pdf                    make (or reuse) an invoice, fetch its PDF: %PDF header, size, Producer
 *   node scripts/upgrade-test-drive.mjs mail                   send the settings test email and an invoice email (with its PDF) through the test mailhog, read them back
 *
 * UT_BASE is the client's address (default http://localhost:8110). UT_MAIL_API is the test
 * mailhog's API as seen from the host (default http://localhost:8026) and UT_MAIL_SMTP its
 * SMTP address as seen from the client (default ucm-ut-mail:1025). Always logs in as the
 * installer's seeded admin, so it never touches anything that is not a test install.
 */
import { chromium } from '@playwright/test';

const BASE = process.env.UT_BASE || 'http://localhost:8110';
const UPGRADE = '/?m[]=config&p[]=config_admin&m[]=config&p[]=config_upgrade';
const SETTINGS = '/?m[]=config&p[]=config_admin&m[]=config&p[]=config_basic_settings';
const SETTINGS_ADV = '/?m[]=config&p[]=config_admin&m[]=config&p[]=config_settings';
const MAIL_API = process.env.UT_MAIL_API || 'http://localhost:8026';
const MAIL_SMTP = process.env.UT_MAIL_SMTP || 'ucm-ut-mail:1025';
const [, , cmd = 'check', ...args] = process.argv;

const browser = await chromium.launch();
const page = await browser.newPage({ baseURL: BASE });
page.setDefaultTimeout(60000);

async function login() {
  await page.goto('/');
  if (await page.locator('input[name="email"]').count()) {
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password');
    await page.locator('form:has(input[name="_process_login"])').locator('input[type="submit"], button[type="submit"]').first().click();
    await page.waitForURL((u) => !u.href.includes('login') || u.href.includes('home'));
  }
}

async function bodyText() {
  return (await page.locator('body').innerText()).replace(/\n{3,}/g, '\n\n');
}

async function sectionText() {
  const el = page.locator('#content .content').last();
  return ((await el.count()) ? await el.innerText() : await bodyText()).split('\n').filter((l) => l.trim()).slice(0, 12).join('\n');
}

async function rows() {
  return page.evaluate(() =>
    [...document.querySelectorAll('tr[data-plugin]')].map((tr) => ({
      plugin: tr.getAttribute('data-plugin'),
      ticked: !!tr.querySelector('.update_checkbox input')?.checked,
      cells: [...tr.querySelectorAll('td')].map((td) => td.innerText.trim().replace(/\s+/g, ' ')),
      progress: (tr.querySelector('.update_progress')?.innerText || '').trim(),
    })),
  );
}

async function check() {
  await page.goto(UPGRADE);
  await page.getByRole('button', { name: 'Check for Upgrades' }).or(page.locator('input[name="check_upgrade"]')).first().click();
  await page.waitForLoadState('load');
}

async function authKey() {
  await page.goto('/?m[]=customer&p[]=customer_admin');
  const key = await page.evaluate(() => window.ucm && window.ucm.form_auth_key);
  if (!key) throw new Error('no form auth key on the page');
  return key;
}

async function create(url, body, idParam) {
  const response = await page.request.post(url, { form: { ...body, form_auth_key: await authKey() }, maxRedirects: 0, failOnStatusCode: false });
  const id = new URL(response.headers()['location'] || '', 'http://localhost').searchParams.get(idParam);
  if (!id) throw new Error(`creating a record at ${url} did not redirect to it (HTTP ${response.status()})`);
  return id;
}

async function anInvoice() {
  await page.goto('/?m[]=invoice&p[]=invoice_admin');
  const found = (await page.content()).match(/invoice_id=([1-9]\d*)/);
  if (found) return found[1];
  const customerId = await create('/?m[]=customer', { _process: 'save_customer', customer_id: 'new', customer_name: 'Upgrade Test Customer', name: 'Upgrade Tester', email: 'upgrade-test@example.com', user_id: '' }, 'customer_id');
  return create('/?m[]=invoice', { _process: 'save_invoice', invoice_id: 'new', name: 'Upgrade test invoice', customer_id: customerId, _redirect: '' }, 'invoice_id');
}

async function fetchPdf(invoiceId) {
  const response = await page.request.get(`/?m[]=invoice&p[]=invoice_admin&print=true&go=true&invoice_id=${invoiceId}`);
  const body = await response.body();
  const text = body.toString('latin1');
  const producer = (text.match(/\/Producer\s*\(([^)]*)\)/) || [])[1] || '(none found)';
  // dompdf writes the Producer as UTF-16 with a byte order mark; show the readable part.
  const readable = producer.replace(/[^\x20-\x7e]/g, '');
  return { status: response.status(), type: response.headers()['content-type'], bytes: body.length, header: body.subarray(0, 8).toString('latin1').replace(/\n/g, ' '), producer: readable };
}

function show(label, text) {
  console.log(`--- ${label}`);
  console.log(text);
}

try {
  await login();
  if (cmd === 'check' || cmd === 'upgrade') {
    await check();
    const r = await rows();
    const text = await bodyText();
    show('page (upgrade section)', await sectionText());
    show('rows', r.map((x) => `${x.ticked ? '[x]' : '[ ]'} ${x.plugin} | ${x.cells.slice(1, 5).join(' | ')}`).join('\n') || '(none)');
    if (cmd === 'upgrade') {
      if (!r.length) throw new Error('nothing to upgrade');
      const started = Date.now();
      await page.locator('#upgrade_start').click();
      page.on('dialog', (d) => { console.log('DIALOG: ' + d.message()); d.dismiss(); });
      await page.waitForFunction(() => window.ucm && window.ucm.upgrade && window.ucm.upgrade.all_done === true, null, { timeout: 600000 });
      console.log(`finished in ${((Date.now() - started) / 1000).toFixed(1)}s; button now: ${await page.locator('#upgrade_start').inputValue()}`);
      show('progress', (await rows()).filter((x) => x.ticked || x.progress).map((x) => `## ${x.plugin}\n${x.progress}`).join('\n'));
    }
  } else if (cmd === 'manual') {
    await page.goto(UPGRADE);
    await page.locator('input[name="run_upgrade"]').click();
    await page.waitForLoadState('load');
    show('manual upgrade page', await bodyText());
  } else if (cmd === 'codes') {
    await page.goto(UPGRADE);
    const boxes = page.locator('input[name="license_codes[]"]');
    while ((await boxes.count()) < args.length) await page.locator('#license_codes_holder .add_addit').last().click();
    for (let i = 0; i < (await boxes.count()); i++) await boxes.nth(i).fill(i < args.length && args[i] !== '-' ? args[i] : '');
    await page.locator('input[name="check_upgrade"]').click();
    await page.waitForLoadState('load');
    const text = await bodyText();
    show('page', await sectionText());
    show('rows', (await rows()).map((x) => `${x.ticked ? '[x]' : '[ ]'} ${x.plugin} | ${x.cells.slice(1, 5).join(' | ')}`).join('\n') || '(none)');
    await page.goto(UPGRADE);
    console.log('box now holds: ' + JSON.stringify(await boxes.evaluateAll((els) => els.map((e) => e.value))));
    console.log('thanks-for-purchasing line present: ' + (await bodyText()).includes('thanks for purchasing'));
  } else if (cmd === 'notice') {
    for (const [name, url] of [['Settings', SETTINGS], ['Advanced settings', SETTINGS_ADV], ['Upgrade', UPGRADE]]) {
      const res = await page.goto(url);
      const warn = (await page.content()).includes('Set a private secret for this site');
      console.log(`${name}: HTTP ${res.status()} secret warning ${warn ? 'SHOWN' : 'not shown'}`);
    }
  } else if (cmd === 'page') {
    const res = await page.goto(args[0]);
    const text = await bodyText();
    const err = text.match(/(Fatal error|Parse error|Uncaught|Warning:|Notice:|Deprecated:)[^\n]*/);
    console.log(`HTTP ${res.status()} php error: ${err ? err[0] : 'none'}`);
    console.log(text.split('\n').filter((l) => l.trim()).slice(0, parseInt(args[1] || '6', 10)).join('\n'));
  } else if (cmd === 'help') {
    await page.goto(args[0]);
    await page.locator('#header_help').click();
    await page.waitForTimeout(2500);
    const lines = (await bodyText()).split('\n').map((l) => l.trim()).filter(Boolean);
    const at = lines.lastIndexOf('Help');
    console.log(lines.slice(at, at + 4).join('\n'));
  } else if (cmd === 'crawl') {
    const max = parseInt(args[0] || '400', 10);
    const skip = /logout|delete|remove|_process=|unlink|purge|empty|reset|cron\.php|download|backup_|\bexport|print=true|pdf=|login/i;
    const seen = new Set();
    const queue = ['/', '/?m[]=config&p[]=config_admin&m[]=config&p[]=config_payment'];
    const bad = [];
    const mentions = [];
    while (queue.length && seen.size < max) {
      const url = queue.shift();
      if (seen.has(url)) continue;
      seen.add(url);
      let res;
      try { res = await page.goto(url, { waitUntil: 'domcontentloaded' }); } catch (e) { bad.push(`${url} -> ${String(e).slice(0, 80)}`); continue; }
      const type = res.headers()['content-type'] || '';
      if (!type.includes('html')) continue;
      const html = await page.content();
      const err = html.match(/(Fatal error|Parse error|Uncaught [A-Za-z\\]+|Stack trace:|Call to undefined|Class "[^"]+" not found|Warning: (?!\{)|Notice: |Deprecated: )[^<\n]{0,160}/);
      if (res.status() >= 400 || err) bad.push(`${url} -> HTTP ${res.status()} ${err ? err[0] : ''}`);
      if (/authorize|coinbase|multisafepay|paynl|pay\.nl/i.test(html) && /paymethod|payment/i.test(url)) mentions.push(url);
      for (const href of await page.$$eval('a[href]', (as) => as.map((a) => a.getAttribute('href')))) {
        if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto') || /^https?:/i.test(href) && !href.startsWith(BASE)) continue;
        const rel = href.startsWith(BASE) ? href.slice(BASE.length) : href;
        if (!/^\/?(\?m|index\.php\?(m|p)|[a-z_]+\.[a-z_]+)/.test(rel) || skip.test(rel)) continue;
        const u = rel.startsWith('/') ? rel : '/' + rel;
        if (!seen.has(u) && !queue.includes(u)) queue.push(u);
      }
    }
    console.log(`crawled ${seen.size} pages, ${bad.length} with an error`);
    for (const b of bad) console.log('  BAD ' + b);
    if (mentions.length) console.log('pages naming a retired gateway: ' + mentions.join(' '));
    if (bad.length) process.exitCode = 1;
  } else if (cmd === 'pdf') {
    const id = await anInvoice();
    const pdf = await fetchPdf(id);
    console.log(`invoice ${id}: HTTP ${pdf.status} ${pdf.type} ${pdf.bytes} bytes, starts "${pdf.header}", Producer: ${pdf.producer}`);
    if (pdf.status !== 200 || !pdf.header.startsWith('%PDF') || pdf.bytes < 1000) process.exitCode = 1;
  } else if (cmd === 'mail') {
    const id = await anInvoice();
    const key = await authKey();
    const settings = { email_smtp: '1', email_smtp_hostname: MAIL_SMTP, email_smtp_auth: '', email_smtp_authentication: '', email_smtp_username: '', email_smtp_password: '' };
    const form = { _process: 'save_config', form_auth_key: key };
    for (const [k, v] of Object.entries(settings)) form[`config[${k}]`] = v;
    const saved = await page.request.post('/?m[]=config&p[]=config_settings', { form, maxRedirects: 0, failOnStatusCode: false });
    console.log('saved SMTP settings: HTTP ' + saved.status());
    const find = async (needle) => {
      for (let i = 0; i < 20; i++) {
        const r = await page.request.get(`${MAIL_API}/api/v2/search?kind=containing&query=${encodeURIComponent(needle)}`);
        const j = await r.json();
        if (j.items && j.items.length) return j.items[0];
        await page.waitForTimeout(250);
      }
      return null;
    };
    const tag = Date.now();
    const to = `settings-test-${tag}@example.net`;
    await page.goto('/?m[]=email&p[]=email_settings');
    await page.locator('input[name="email"]').fill(to);
    await page.locator('input[name="send"]').click();
    const sentOk = (await bodyText()).includes('Test email sent successfully');
    const m1 = await find(to);
    console.log(`settings test email: page says sent=${sentOk}; mailhog has it=${!!m1}${m1 ? '; multipart/alternative=' + /multipart\/alternative/i.test(m1.Raw.Data) + '; X-Mailer/generator: ' + ((m1.Content.Headers['X-Mailer'] || [])[0] || '(none)') : ''}`);
    const marker = `Upgrade test ${tag}`;
    await page.goto(`/?m[]=invoice&p[]=invoice_admin&invoice_id=${id}&email=true`);
    const f = page.locator('form:has(input[name="_process"][value="send_email"])');
    const box = f.locator('input[name="custom_to[]"]');
    if (await box.count()) await box.first().fill(`to-${tag}@example.net`);
    const listsPdf = /\.pdf/i.test((await f.textContent()) || '');
    await f.locator('input[name="subject"]').fill(marker);
    await f.locator('textarea[name="html_content"]').evaluate((el, html) => { el.value = html; }, `<p>Hello from <strong>${marker}</strong> - Gr\u00fc\u00dfe</p>`);
    await f.locator('input[type="submit"][name="send"], button[name="send"]').first().click();
    const m2 = await find(marker);
    if (m2) {
      const raw = m2.Raw.Data;
      console.log(`invoice email: mailhog has it=true; PDF listed on screen=${listsPdf}; has application/pdf part=${/Content-Type: application\/pdf/i.test(raw)}; multipart/mixed=${/multipart\/mixed/i.test(JSON.stringify(m2.Content.Headers))}`);
    } else {
      console.log('invoice email: NOT in mailhog');
      process.exitCode = 1;
    }
    for (const m of [m1, m2]) if (m) await page.request.delete(`${MAIL_API}/api/v1/messages/${m.ID}`, { failOnStatusCode: false });
    if (!m1 || !m2) process.exitCode = 1;
  } else {
    throw new Error('unknown command ' + cmd);
  }
} finally {
  await browser.close();
}
