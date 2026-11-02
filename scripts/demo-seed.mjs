#!/usr/bin/env node
/**
 * Fill the public demo with a believable, made-up agency's books: customers and their
 * contacts, vendors (customers of a Vendors type), jobs with tasks and logged time, quotes,
 * invoices in every state, support tickets, websites, calendar entries and notes.
 *
 *   node scripts/demo-seed.mjs https://demo.ultimateclientmanager.com
 *
 * It signs in as the demo's public administrator login, admin@example.com, the one the
 * login page prints, and posts the forms the app's own pages post, so every total, status
 * and relationship is the one the app works out. See scripts/demo-seed/data.mjs for what
 * is created, and custom/README.md for how it fits with the deploy and the daily reset.
 *
 * Safe to run twice: when the sample customers are already there it does not make them
 * again, and says so. It also refuses to run against a site that is not showing the demo
 * banner, because it would otherwise add a dozen customers to somebody's real books.
 *
 * A demo made by an earlier version is brought up to date instead of rebuilt (see upgrade()).
 *
 * Prints SEEDED on its last line when it created the data, MIGRATED when it only brought
 * existing data up to date, and PRESENT when it did nothing. It writes the same to
 * $GITHUB_OUTPUT as seeded=true|false and migrated=true|false, so a workflow knows whether,
 * and how, the snapshot needs taking again. Exit code 1 is a failure, and says where.
 *
 * Needs Node 18 or later and nothing installed. Never prints a credential.
 */
import { appendFileSync } from 'node:fs';
import { pathToFileURL } from 'node:url';
import * as data from './demo-seed/data.mjs';

/** The public demo logins, printed on the demo's own login page. */
const ADMIN = { email: 'admin@example.com', password: 'password' };
const CUSTOMER_LOGIN = 'user@example.com';
const STAFF_LOGIN = 'staff@example.com';
const LOGIN_PASSWORD = 'password';

/** Days from today, as the YYYY-MM-DD the app reads in any date format setting. */
export function dayOffset(days, from = new Date()) {
  const date = new Date(Date.UTC(from.getFullYear(), from.getMonth(), from.getDate() + days));

  return date.toISOString().slice(0, 10);
}

function money(value) {
  return Math.round(value * 100) / 100;
}

/** What an invoice's lines come to, before and after tax. */
export function invoiceTotals(invoice, taxPercent = data.TAX.percent) {
  const net = money(invoice.lines.reduce((sum, line) => sum + (line.amount ?? line.hours * line.rate), 0));

  return { net, total: money(net * (1 + taxPercent / 100)) };
}

/**
 * The data refers to itself by name (a job's customer, an invoice's job, a ticket's contact),
 * so a typo would only show up as a missing record on the live demo. Catch it first.
 */
export function check(set = data) {
  const problems = [];
  const names = new Set(set.customers.map((customer) => customer.name));
  const jobs = new Map(set.jobs.map((job) => [job.key, job]));
  const need = (ok, message) => ok || problems.push(message);

  need(names.has(set.PORTAL_CUSTOMER), `the portal customer "${set.PORTAL_CUSTOMER}" is not one of the customers`);
  need(set.customers.filter((customer) => customer.portal).length === 1, 'exactly one customer should be the customer login\'s');
  need(set.customers.find((customer) => customer.portal)?.contact.email === CUSTOMER_LOGIN, `the portal customer's first contact is not ${CUSTOMER_LOGIN}`);
  const vendorNames = new Set(set.vendors.map((vendor) => vendor.name));
  need(vendorNames.size === set.vendors.length, 'two vendors have the same name');
  for (const vendor of set.vendors) {
    need(!names.has(vendor.name), `vendor "${vendor.name}" is also one of the customers`);
    need(Boolean(vendor.address?.line_1), `vendor "${vendor.name}" has no address`);
  }
  for (const [kind, rows] of [['job', set.jobs], ['quote', set.quotes], ['invoice', set.invoices], ['ticket', set.tickets], ['file', set.files]]) {
    for (const row of rows) {
      need(names.has(row.customer), `${kind} "${row.name || row.subject}" belongs to "${row.customer}", who is not a customer`);
    }
  }
  for (const [customer] of [...set.notes, ...set.calendar.filter((entry) => entry[5]).map((entry) => [entry[5]])]) {
    need(names.has(customer), `a note or calendar entry names "${customer}", who is not a customer`);
  }
  for (const invoice of set.invoices) {
    const paid = invoice.paid.reduce((sum, [, share]) => sum + share, 0);
    need(paid <= 1, `invoice "${invoice.name}" is paid ${paid} times over`);
    need(invoice.paid.length === 0 || invoice.sent !== undefined, `invoice "${invoice.name}" is paid but was never sent`);
    need(invoice.due === undefined || invoice.due >= invoice.created, `invoice "${invoice.name}" is due before it was made`);
    for (const [ago] of invoice.paid) {
      need(ago <= 0 && ago >= invoice.created, `invoice "${invoice.name}" is paid on a day outside its life`);
    }
    if (invoice.job) {
      const tasks = jobs.get(invoice.job)?.tasks.map((task) => task.description) || [];
      need(jobs.has(invoice.job), `invoice "${invoice.name}" names a job, "${invoice.job}", that does not exist`);
      for (const line of invoice.lines) {
        need(tasks.includes(line.description), `invoice "${invoice.name}" bills "${line.description}", which is not a task of ${invoice.job}`);
      }
    }
  }
  for (const job of set.jobs) {
    need(job.status !== 'Completed' || job.tasks.every((task) => task.done), `job "${job.name}" is completed with work left`);
    need(job.status !== 'Completed' || job.done !== undefined, `job "${job.name}" is completed without a date`);
    for (const task of job.tasks) {
      need(!task.logged || !task.done, `task "${task.description}" is both done and part logged`);
      need(!task.logged || task.logged < task.hours, `task "${task.description}" has logged more than its hours`);
    }
  }
  for (const ticket of set.tickets) {
    need(ticket.messages.length > 0 && ticket.messages[0][0] === 'customer', `ticket "${ticket.subject}" should open with the customer's message`);
  }

  return problems;
}

class Demo {
  constructor(base) {
    this.base = base.replace(/\/+$/, '');
    this.cookies = new Map();
    this.authKey = '';
    this.requests = 0;
  }

  async send(path, { form, method } = {}) {
    const headers = { cookie: [...this.cookies].map(([k, v]) => `${k}=${v}`).join('; ') };
    let body;
    if (form) {
      body = new URLSearchParams();
      for (const [key, value] of Object.entries(form)) {
        for (const one of Array.isArray(value) ? value : [value]) {
          body.append(key, one === null || one === undefined ? '' : String(one));
        }
      }
      headers['content-type'] = 'application/x-www-form-urlencoded';
    }
    const response = await fetch(this.base + path, {
      method: method || (form ? 'POST' : 'GET'),
      headers,
      body,
      redirect: 'manual',
      signal: AbortSignal.timeout(60000),
    });
    this.requests++;
    for (const line of response.headers.getSetCookie()) {
      const [pair] = line.split(';');
      const at = pair.indexOf('=');
      this.cookies.set(pair.slice(0, at).trim(), pair.slice(at + 1).trim());
    }

    return {
      status: response.status,
      location: response.headers.get('location') || '',
      text: await response.text(),
    };
  }

  /** GET, following redirects, for the pages that are read rather than posted to. */
  async page(path) {
    let result = await this.send(path);
    for (let hops = 0; result.status >= 300 && result.status < 400 && result.location && hops < 5; hops++) {
      const next = new URL(result.location, this.base + '/');
      result = await this.send(next.pathname + next.search);
    }

    return result;
  }

  async login(email = ADMIN.email, password = ADMIN.password) {
    const first = await this.page('/');
    if (!first.text.includes('start theme demo code')) {
      throw new Error('This site is not showing the demo banner, so it is not the demo. Nothing was created.');
    }
    await this.send('/', { form: { _process_login: 'true', email, password } });
    const home = await this.page('/');
    if (home.text.includes('name="_process_login"') || !/log ?out/i.test(home.text)) {
      throw new Error(`${email} could not sign in to ${this.base}. Run the demo's repair first.`);
    }
    if (email !== ADMIN.email) {
      return;
    }
    const key = (await this.page('/?m[]=customer&p[]=customer_admin')).text.match(/form_auth_key = '([a-f0-9]+)'/);
    if (!key) {
      throw new Error('The app did not hand out a form token, so nothing can be saved.');
    }
    this.authKey = key[1];
  }

  /** Post a form with a real token, and return where the app sent us next. */
  async save(path, form, what) {
    const result = await this.send(path, { form: { ...form, form_auth_key: this.authKey } });
    if (result.status < 300 || result.status >= 400 || /Fatal error|Warning:|Parse error/.test(result.text)) {
      throw new Error(`Saving ${what} failed (HTTP ${result.status}) ${result.text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').slice(0, 300)}`);
    }

    return new URL(result.location, this.base + '/');
  }

  /** Save a new record and return the id the app redirected to. */
  async create(path, form, idParam, what) {
    const id = (await this.save(path, form, what)).searchParams.get(idParam);
    if (!id || !/^[1-9]\d*$/.test(id)) {
      throw new Error(`Saving ${what} did not lead to the new record (no ${idParam}).`);
    }

    return id;
  }

  /** The id of the record a list page links to under this name, or null. */
  async findId(listPath, idParam, name) {
    const body = (await this.page(listPath)).text;
    const decoded = (text) => text.replace(/<[^>]*>/g, '').replace(/&amp;/g, '&').replace(/\s+/g, ' ').trim();
    for (const [, href, text] of body.matchAll(/<a\b[^>]*href="([^"]*)"[^>]*>([\s\S]*?)<\/a>/g)) {
      const found = new RegExp(`[?&](?:amp;)?${idParam}=([1-9]\\d*)`).exec(href);
      if (found && decoded(text) === name) {
        return found[1];
      }
    }

    return null;
  }
}

const enc = encodeURIComponent;

/** The first value of an input on a page, whichever order its attributes are written in. */
function inputValue(html, name) {
  for (const [tag] of html.matchAll(/<input\b[^>]*>/g)) {
    if (new RegExp(`name="${name}"`).test(tag)) {
      const value = /value="([^"]*)"/.exec(tag);

      return value ? value[1] : '';
    }
  }

  return null;
}

/** An <option> of a select, by its label. */
function optionValue(html, selectName, label) {
  const select = new RegExp(`<select[^>]*name="${selectName}"[^>]*>([\\s\\S]*?)</select>`).exec(html);
  if (select) {
    for (const [, value, text] of select[1].matchAll(/<option[^>]*value="([^"]*)"[^>]*>([^<]*)</g)) {
      if (text.trim() === label) {
        return value;
      }
    }
  }

  return null;
}

const taxFields = () => ({
  'tax_ids[]': '0',
  'tax_names[]': data.TAX.name,
  'tax_percents[]': String(data.TAX.percent),
});

/** True when every sample customer is missing; a partial set is left for the daily reset. */
async function alreadyThere(demo) {
  for (const customer of data.customers) {
    const id = await demo.findId(`/?m[]=customer&p[]=customer_admin_list&customer_type_id=0&search[generic]=${enc(customer.name)}`, 'customer_id', customer.name);
    if (id) {
      return customer.name;
    }
  }

  return null;
}

/** The key of the address block on the new customer form, which is a hash. */
async function addressKeyOf(demo) {
  const blank = (await demo.page('/?m[]=customer&p[]=customer_admin_edit&customer_id=new')).text;
  const key = /name="address\[([a-f0-9]+)\]\[line_1\]"/.exec(blank)?.[1];
  if (!key) {
    throw new Error('The new customer form has no address block to fill in.');
  }

  return key;
}

/** What the new customer form posts for a customer, or a vendor, and its first contact. */
function customerForm(customer, addressKey, more = {}) {
  const form = {
    _process: 'save_customer',
    customer_id: 'new',
    customer_name: customer.name,
    ...customer.contact,
    user_id: '',
    [`address[${addressKey}][address_id]`]: '',
    [`address[${addressKey}][owner_id]`]: '0',
    [`address[${addressKey}][owner_table]`]: 'customer',
    [`address[${addressKey}][address_type]`]: 'physical',
    ...more,
  };
  for (const [field, value] of Object.entries(customer.address)) {
    form[`address[${addressKey}][${field}]`] = value;
  }

  return form;
}

/**
 * The id of the Vendors customer type, making the type if the demo has not got it. It is the
 * Supplier kind of billing, the same as the installer's own Suppliers type, and it puts
 * "Vendors" in the main menu.
 */
async function vendorTypeId(demo) {
  const { name, plural, icon } = data.VENDOR_TYPE;
  const found = await demo.findId('/?m[]=customer&p[]=customer_settings_types', 'customer_type_id', plural);
  if (found) {
    return { id: found, made: false };
  }
  const id = await demo.create('/?m[]=customer', {
    _process: 'save_customer_type', customer_type_id: '0', type_name: name, type_name_plural: plural,
    menu_position: '0', menu_icon: icon, billing_type: '1',
  }, 'customer_type_id', `the ${plural} customer type`);

  return { id, made: true };
}

/**
 * The vendors, as customers of the Vendors type. Each one that is already there is left alone,
 * so this is safe to run on a demo that has some.
 *
 * @returns {{ids: string[], made: number}} the customer ids, in the order of the data
 */
async function createVendors(demo, typeId, addressKey) {
  const ids = [];
  let made = 0;
  for (const vendor of data.vendors) {
    let id = await demo.findId(`/?m[]=customer&p[]=customer_admin_list&customer_type_id=${typeId}&search[generic]=${enc(vendor.name)}`, 'customer_id', vendor.name);
    if (!id) {
      id = await demo.create('/?m[]=customer', customerForm(vendor, addressKey, { customer_type_id: typeId }), 'customer_id', `vendor ${vendor.name}`);
      made++;
    }
    ids.push(id);
  }

  return { ids, made };
}

/** The pages that make the app write down the permissions of a type's own menu, contacts and notes. */
const vendorPages = (typeId, customerId) => [
  `/?m[]=customer&p[]=customer_admin_list&customer_type_id=${typeId}`,
  `/?m[]=customer&p[]=customer_admin_edit&customer_id=${customerId}`,
  `/?m[]=user&p[]=contact_admin_list&customer_id=${customerId}`,
];

/**
 * Bring a demo made by an earlier version up to date, without rebuilding it. Today that is one
 * thing: the vendors used to be records of the old Vendors plugin, which is to be merged into
 * the Customers area. They are made again as customers of a Vendors type. The old records are
 * not touched here: the app refuses to delete the first vendor in demo mode, so repair removes
 * them on the server, once their replacements are there (custom/demo/reset.php).
 *
 * @returns {Promise<boolean>} whether anything was made
 */
async function upgrade(demo, log) {
  const type = await vendorTypeId(demo);
  const { ids, made } = await createVendors(demo, type.id, await addressKeyOf(demo));
  if (type.made) {
    log(`Made the ${data.VENDOR_TYPE.plural} customer type.`);
  }
  if (made) {
    log(`Made ${made} of ${data.vendors.length} vendors as customers of that type.`);
  }
  if (type.made || made) {
    for (const page of vendorPages(type.id, ids[0])) {
      await demo.page(page);
    }
  }

  return type.made || made > 0;
}

async function seed(demo, log) {
  const today = new Date();
  const day = (offset) => dayOffset(offset, today);
  const ids = { customers: {}, contacts: {}, websites: {}, jobs: {}, tasks: {}, invoices: [], quotes: [], files: [], vendors: [] };

  // The form's own hidden fields name the address block, whose key is a hash.
  const addressKey = await addressKeyOf(demo);

  // Who the staff are, for assigning work to.
  const jobForm = (await demo.page('/?m[]=job&p[]=job_admin&job_id=new')).text;
  const staff = optionValue(jobForm, 'user_id', 'Staff Member') || '';
  const admin = optionValue(jobForm, 'user_id', 'Administrator') || '1';

  // --- customers, and their contacts
  const users = (await demo.page('/?m[]=user&p[]=user_admin')).text.split(/<tr\b/);
  const portalUser = users.find((row) => row.includes(CUSTOMER_LOGIN))?.match(/user_id=([1-9]\d*)/)?.[1] || '';
  for (const customer of data.customers) {
    const form = customerForm(customer, addressKey);
    if (customer.portal) {
      // The customer login already exists, as a contact of nobody. Moving it onto this
      // customer is how the app attaches an existing person to one.
      if (!portalUser) {
        throw new Error(`${CUSTOMER_LOGIN} is not in the users list, so it cannot be linked to ${customer.name}. Run the demo's repair first.`);
      }
      form.user_id = portalUser;
      form.move_user_id = portalUser;
    }
    const id = await demo.create('/?m[]=customer', form, 'customer_id', `customer ${customer.name}`);
    ids.customers[customer.name] = id;
    ids.contacts[customer.name] = inputValue((await demo.page(`/?m[]=customer&p[]=customer_admin_edit&customer_id=${id}`)).text, 'user_id') || '';
    for (const more of customer.more || []) {
      await demo.save('/?m[]=user', { _process: 'save_user', user_id: '0', customer_id: id, ...more }, `contact ${more.name} ${more.last_name}`);
    }
    for (const [index, site] of customer.websites.entries()) {
      ids.websites[`${customer.name}#${index}`] = await demo.create('/?m[]=website', {
        _process: 'save_website', website_id: 'new', customer_id: id, name: site.name, url: site.url, status: 'Live', _redirect: '',
      }, 'website_id', `website ${site.name}`);
    }
  }
  log(`${data.customers.length} customers with their contacts and ${Object.keys(ids.websites).length} websites`);

  // --- vendors, as customers of a type of their own
  const vendorType = await vendorTypeId(demo);
  ids.vendorType = vendorType.id;
  ids.vendors = (await createVendors(demo, vendorType.id, addressKey)).ids;
  log(`${data.vendors.length} vendors, as customers of the ${data.VENDOR_TYPE.plural} type`);

  // --- jobs, with their tasks and the hours worked on them
  for (const job of data.jobs) {
    const customer = data.customers.find((c) => c.name === job.customer);
    const base = {
      _process: 'save_job',
      name: job.name,
      customer_id: ids.customers[job.customer],
      contact_user_id: ids.contacts[job.customer],
      user_id: staff || admin,
      hourly_rate: job.rate,
      status: job.status,
      type: job.type,
      currency_id: '1',
      default_task_type: '0',
      date_start: day(job.start),
      date_due: day(job.due),
      ...taxFields(),
    };
    if (job.website !== undefined && customer.websites[job.website]) {
      base.website_id = ids.websites[`${job.customer}#${job.website}`];
    }
    const form = { ...base, job_id: 'new' };
    job.tasks.forEach((task, index) => {
      const key = `job_task[n${index}]`;
      form[`${key}[description]`] = task.description;
      form[`${key}[date_due]`] = day(task.due);
      form[`${key}[billable]`] = '1';
      form[`${key}[billable_t]`] = '1';
      form[`${key}[taxable]`] = '1';
      form[`${key}[taxable_t]`] = '1';
      form[`${key}[user_id]`] = staff || admin;
      if (task.amount !== undefined) {
        form[`${key}[manual_task_type]`] = '2';
        form[`${key}[amount]`] = task.amount;
      } else {
        form[`${key}[manual_task_type]`] = '-1';
        form[`${key}[hours]`] = task.hours;
      }
      if (task.done) {
        form[`${key}[new_fully_completed]`] = '1';
        form[`${key}[date_done]`] = day(task.due - 1);
      } else if (task.logged) {
        form[`${key}[log_hours]`] = task.logged;
      }
    });
    const id = await demo.create('/?m[]=job', form, 'job_id', `job ${job.name}`);
    ids.jobs[job.key] = id;
    if (job.status === 'Completed') {
      // Adding the last task re-works the job's status, and leaves a job whose work is all
      // done as New. Saying it is completed, on its own, is what the job's form does.
      await demo.save('/?m[]=job', { ...base, job_id: id, date_completed: day(job.done) }, `job ${job.name} completed`);
    }
    // The tasks' ids are only on the job's own page. Opening it also starts the timer the
    // app runs on whoever opens a job, which is cleared away below.
    const page = (await demo.page(`/?m[]=job&p[]=job_admin&job_id=${id}`)).text;
    const rows = [...page.matchAll(/<tr[^>]*task_preview[^>]*rel="(\d+)"/g)].map((m) => m[1]);
    ids.tasks[job.key] = Object.fromEntries(job.tasks.map((task, index) => [task.description, rows[index]]));
  }
  log(`${data.jobs.length} jobs`);

  // --- time recorded with the timer, against a job's task
  let timers = 0;
  for (const job of data.jobs) {
    for (const [ago, hours, what] of job.timers) {
      const open = job.tasks.find((task) => !task.done);
      const started = await demo.send('/?m[]=timer', {
        form: {
          _process: 'task_timer_clicked',
          form_auth_key: demo.authKey,
          description: what,
          customer_id: ids.customers[job.customer],
          owner_table: 'job',
          owner_id: ids.jobs[job.key],
          owner_child_id: open ? ids.tasks[job.key][open.description] : '',
        },
      });
      const timerId = JSON.parse(started.text)?.timer?.timer_id;
      const timerPage = (await demo.page(`/?m[]=timer&p[]=timer_admin_edit&timer_id=${timerId}`)).text;
      const segmentId = /timer_segment_id=(\d+)/.exec(timerPage)?.[1];
      if (!timerId || !segmentId) {
        throw new Error(`Starting the timer "${what}" did not give a timer and a segment to correct.`);
      }
      // A stretch that finished some days ago, in working hours.
      const begin = Date.parse(`${day(-ago)}T14:30:00Z`) / 1000;
      await demo.send('/?m[]=timer', {
        form: {
          _process: 'save_timer_segment', form_auth_key: demo.authKey, timer_id: timerId, timer_segment_id: segmentId,
          start_time: begin, end_time: begin + Math.round(hours * 3600),
        },
      });
      await demo.send('/?m[]=timer', { form: { _process: 'task_timer_clicked', form_auth_key: demo.authKey, timer_id: timerId, finished: '1' } });
      // The timer itself is dated from its stretch, not from the moment it was started.
      await demo.send('/?m[]=timer', {
        form: { _process: 'save_timer', form_auth_key: demo.authKey, timer_id: timerId, start_time: begin, end_time: begin + Math.round(hours * 3600) },
      });
      timers++;
    }
  }
  log(`${timers} timers`);

  // --- quotes
  for (const quote of data.quotes) {
    const form = {
      _process: 'save_quote',
      quote_id: 'new',
      name: quote.name,
      customer_id: ids.customers[quote.customer],
      contact_user_id: ids.contacts[quote.customer],
      user_id: admin,
      hourly_rate: quote.rate,
      status: quote.status,
      type: 'Website Design',
      currency_id: '1',
      default_task_type: '0',
      date_create: day(quote.created),
      ...taxFields(),
    };
    if (quote.approved !== undefined) {
      form.date_approved = day(quote.approved);
      form.approved_by = data.customers.find((c) => c.name === quote.customer).contact.name;
    }
    quote.tasks.forEach((task, index) => {
      const key = `quote_task[n${index}]`;
      form[`${key}[description]`] = task.description;
      form[`${key}[taxable]`] = '1';
      form[`${key}[taxable_t]`] = '1';
      if (task.amount !== undefined) {
        form[`${key}[manual_task_type]`] = '2';
        form[`${key}[amount]`] = task.amount;
      } else {
        form[`${key}[manual_task_type]`] = '-1';
        form[`${key}[hours]`] = task.hours;
      }
    });
    ids.quotes.push(await demo.create('/?m[]=quote', form, 'quote_id', `quote ${quote.name}`));
  }
  log(`${data.quotes.length} quotes`);

  // --- invoices, then the payments against them
  for (const invoice of data.invoices) {
    const { total } = invoiceTotals(invoice);
    const rates = [...new Set(invoice.lines.filter((line) => line.rate).map((line) => line.rate))];
    const form = {
      _process: 'save_invoice',
      _redirect: '',
      invoice_id: 'new',
      name: invoice.name,
      customer_id: ids.customers[invoice.customer],
      contact_user_id: ids.contacts[invoice.customer],
      user_id: admin,
      hourly_rate: rates.length === 1 ? rates[0] : '',
      status: invoice.status,
      currency_id: '1',
      default_task_type: '0',
      date_create: day(invoice.created),
      date_due: day(invoice.due ?? invoice.created + 14),
      ...taxFields(),
    };
    if (invoice.sent !== undefined) {
      form.date_sent = day(invoice.sent);
    }
    invoice.lines.forEach((line, index) => {
      const key = `invoice_invoice_item[n${index}]`;
      form[`${key}[description]`] = line.description;
      form[`${key}[taxable]`] = '1';
      form[`${key}[taxable_t]`] = '1';
      const task = invoice.job ? ids.tasks[invoice.job]?.[line.description] : undefined;
      if (task) {
        form[`${key}[task_id]`] = task;
      }
      if (line.amount !== undefined) {
        form[`${key}[manual_task_type]`] = '2';
        form[`${key}[hourly_rate]`] = line.amount;
      } else {
        form[`${key}[manual_task_type]`] = '0';
        form[`${key}[hours]`] = line.hours;
        form[`${key}[hourly_rate]`] = line.rate;
      }
    });
    const id = await demo.create('/?m[]=invoice', form, 'invoice_id', `invoice ${invoice.name}`);
    ids.invoices.push(id);
    for (const [ago, share, method] of invoice.paid) {
      await demo.save('/?m[]=invoice', {
        _process: 'save_invoice', _redirect: '', invoice_id: id, add_payment: 'go',
        'invoice_invoice_payment[new][amount]': money(total * share),
        'invoice_invoice_payment[new][method]': method,
        'invoice_invoice_payment[new][date_paid]': day(ago),
      }, `a payment on ${invoice.name}`);
    }
    if (invoice.sent !== undefined && invoice.paid.some(([, share]) => share >= 1)) {
      // Paying an invoice in full sends the customer a receipt, and sending it stamps the
      // invoice as sent today. It went out when it was raised.
      await demo.save('/?m[]=invoice', { _process: 'save_invoice', _redirect: '', invoice_id: id, date_sent: day(invoice.sent) }, `${invoice.name}'s sent date`);
    }
  }
  log(`${data.invoices.length} invoices`);

  // --- support tickets: the customer writes on the public page their email links to
  for (const ticket of data.tickets) {
    const customerId = ids.customers[ticket.customer];
    const common = {
      subject: ticket.subject,
      customer_id: customerId,
      user_id: ids.contacts[ticket.customer],
      assigned_user_id: staff || admin,
      priority: '0',
      _redirect: '',
    };
    const ticketId = await demo.create('/?m[]=ticket', { _process: 'save_ticket', ticket_id: 'new', status_id: '2', ...common }, 'ticket_id', `ticket ${ticket.subject}`);
    const publicPath = /external\/m\.ticket\/h\.public\/i\.\d+\/hash\.[a-f0-9]+/.exec((await demo.page(`/?m[]=ticket&p[]=ticket_admin&ticket_id=${ticketId}`)).text)?.[0];
    if (!publicPath) {
      throw new Error(`Ticket ${ticketId} has no public page to write the customer's messages on.`);
    }
    for (const [who, text] of ticket.messages) {
      if (who === 'customer') {
        await demo.save(`/${publicPath}`, { _process: 'send_public_ticket', new_ticket_message: text }, 'a customer message');
      } else {
        await demo.save('/?m[]=ticket', {
          _process: 'save_ticket', ticket_id: ticketId, ...common, new_ticket_message: text, change_status_id: '3', newmsg: 'Submit Message',
        }, 'a reply');
      }
    }
    await demo.save('/?m[]=ticket', { _process: 'save_ticket', ticket_id: ticketId, ...common, status_id: ticket.status, change_status_id: ticket.status }, 'a ticket status');
  }
  log(`${data.tickets.length} tickets`);

  // --- calendar entries
  for (const [offset, hour, length, subject, where, customerName] of data.calendar) {
    const end = hour + length;
    const clock = (value) => `${Math.floor(value)}:${String(Math.round((value % 1) * 60)).padStart(2, '0')}`;
    const result = await demo.send('/?m[]=calendar&p[]=calendar_admin', {
      form: {
        _process: 'save_calendar_entry', form_auth_key: demo.authKey, calendar_id: '0',
        subject, location: where, description: '', customer_id: customerName ? ids.customers[customerName] : '0',
        start: day(offset), start_time: clock(hour), end: day(offset), end_time: clock(end), 'staff_ids[]': staff || admin,
      },
    });
    if (!/Success/.test(result.text)) {
      throw new Error(`The calendar entry "${subject}" was not saved: ${result.text.slice(0, 200)}`);
    }
  }
  log(`${data.calendar.length} calendar entries`);

  // --- files shared with customers
  for (const file of data.files) {
    ids.files.push(await demo.create('/?m[]=file', {
      _process: 'save_file', file_id: 'new', file_name: file.name, file_url: file.url, status: file.status, description: file.description,
      customer_id: ids.customers[file.customer], job_id: ids.jobs[file.job],
    }, 'file_id', `file ${file.name}`));
  }
  log(`${data.files.length} files`);

  // --- notes on customers
  for (const [customerName, ago, text] of data.notes) {
    const options = { owner_id: Number(ids.customers[customerName]), owner_table: 'customer' };
    await demo.save('/?m[]=note', {
      _process: 'save_note', note_id: 'new', options: phpOptions(options), note: text,
      note_time: Math.floor(Date.parse(`${day(-ago)}T15:00:00Z`) / 1000), user_id: admin,
    }, `a note on ${customerName}`);
  }
  log(`${data.notes.length} notes`);

  await clearOpenedTimers(demo);
  await openEveryScreen(demo, ids);
  // And as the other two logins, which is when a permission for their own menus and dashboard is
  // written down. Their roles have only the sign-in permission until repair has run.
  for (const email of [STAFF_LOGIN, CUSTOMER_LOGIN]) {
    const visitor = new Demo(demo.base);
    await visitor.login(email, LOGIN_PASSWORD);
    await openEveryScreen(visitor, ids);
  }

  return ids;
}

/**
 * The app writes down a permission the first time a page asks for it, and the demo's
 * staff and customer roles can only be given permissions that are written down. So the
 * administrator opens each kind of page once, a list and a record, and repair, which runs
 * after this, finds them all.
 */
async function openEveryScreen(demo, ids) {
  const customer = Object.values(ids.customers)[0];
  const first = (key) => Object.values(ids[key])[0];
  const pages = [
    '/',
    `/?m[]=customer&p[]=customer_admin_edit&customer_id=${customer}`,
    `/?m[]=user&p[]=contact_admin&customer_id=${customer}`,
    '/?m[]=user&p[]=contact_admin_list&customer_id=' + customer,
    ...vendorPages(ids.vendorType, ids.vendors[0]),
    '/?m[]=quote&p[]=quote_admin',
    `/?m[]=quote&p[]=quote_admin&quote_id=${ids.quotes[0]}`,
    '/?m[]=invoice&p[]=invoice_admin',
    `/?m[]=invoice&p[]=invoice_admin&invoice_id=${ids.invoices[0]}`,
    '/?m[]=job&p[]=job_admin',
    '/?m[]=ticket&p[]=ticket_admin_list',
    '/?m[]=website&p[]=website_admin',
    `/?m[]=website&p[]=website_admin_edit&website_id=${first('websites')}`,
    '/?m[]=file&p[]=file_admin',
    `/?m[]=file&p[]=file_admin_edit&file_id=${ids.files[0]}`,
    '/?m[]=calendar&p[]=calendar_admin',
    '/?m[]=contract&p[]=contract_admin',
    '/?m[]=timer&p[]=timer_admin_list',
    '/?m[]=faq&p[]=faq_admin',
    '/?m[]=customer&p[]=customer_admin_list',
  ];
  for (const page of pages) {
    await demo.page(page);
  }
  // The calendar draws itself from a feed, and that is what asks which entries may be seen.
  const feed = /DATA_FEED_URL\s*=\s*"([^"]+)"/.exec((await demo.page('/?m[]=calendar&p[]=calendar_admin')).text)?.[1];
  if (feed) {
    await demo.send(`${feed.replace(/&amp;/g, '&')}_process=ajax_calendar&method=list`, { form: { showdate: dayOffset(0), viewtype: 'month' } });
  }
  // The dashboards of the other themes ask for permissions the default one does not.
  for (const theme of ['theme_adminlte', 'theme_blocks', 'whitelabel1', 'pro', 'metis']) {
    await demo.page(`/?demo_theme=${theme}`);
  }
  // Opening a job or ticket starts the automatic timer again, for those who may.
  if (demo.authKey) {
    await clearOpenedTimers(demo);
  }
}

/** The array a note form carries its owner in: base64 of a PHP-serialized array. */
export function phpOptions(options) {
  const parts = Object.entries(options).map(([key, value]) => {
    const name = `s:${Buffer.byteLength(key)}:"${key}";`;

    return name + (typeof value === 'number' ? `i:${value};` : `s:${Buffer.byteLength(value)}:"${value}";`);
  });

  return Buffer.from(`a:${parts.length}:{${parts.join('')}}`).toString('base64');
}

/**
 * Opening a job as an administrator starts a timer called "Opened Job" that runs while the
 * page is open. The seed opens every job to find its tasks, so it throws those away,
 * leaving the timers that were recorded on purpose.
 */
async function clearOpenedTimers(demo) {
  const list = (await demo.page('/?m[]=timer&p[]=timer_admin_list')).text;
  for (const [, row] of list.matchAll(/<tr\b([\s\S]*?)<\/tr>/g)) {
    const id = /timer_id=([1-9]\d*)/.exec(row)?.[1];
    if (id && /Opened (Job|Ticket|Quote|Invoice|Customer)/.test(row)) {
      await demo.send('/?m[]=timer', { form: { _process: 'task_timer_clicked', form_auth_key: demo.authKey, timer_id: id, delete_completely: '1' } });
    }
  }
}

export async function run(base, log = (line) => console.log(line)) {
  const problems = check();
  if (problems.length) {
    throw new Error(`The sample data does not hold together:\n  ${problems.join('\n  ')}`);
  }
  const demo = new Demo(base);
  await demo.login();
  const present = await alreadyThere(demo);
  if (present) {
    log(`Sample data is already there ("${present}" exists), so it was not made again.`);
    if (await upgrade(demo, (line) => log(`  ${line}`))) {
      log(`Brought up to date: ${demo.requests} requests.`);

      return 'migrated';
    }

    return 'present';
  }
  log('Creating the sample data:');
  await seed(demo, (line) => log(`  ${line}`));
  log(`Done: ${demo.requests} requests.`);

  return 'seeded';
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const base = process.argv[2];
  if (!base) {
    console.error('usage: node scripts/demo-seed.mjs <demo url>');
    process.exit(64);
  }
  try {
    const outcome = await run(base);
    if (process.env.GITHUB_OUTPUT) {
      appendFileSync(process.env.GITHUB_OUTPUT, `seeded=${outcome === 'seeded'}\nmigrated=${outcome === 'migrated'}\n`);
    }
    console.log(outcome.toUpperCase());
  } catch (error) {
    console.error(`Failed: ${error.message}`);
    process.exit(1);
  }
}
