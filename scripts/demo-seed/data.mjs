/**
 * The public demo's sample data: a small, made-up web agency's books.
 *
 * Every business and person below is invented, and every address is on example.com. Dates
 * are written as days from the day the seed runs (0 is today, -30 is a month ago), never as
 * calendar dates, so the data reads the same whenever it is made. custom/demo/reset.php
 * moves them forward every day the demo is reset, so they keep reading that way.
 *
 * Nothing here knows how the app stores anything. scripts/demo-seed.mjs turns it into the
 * forms the app's own pages post.
 */

/** The customer the demo's customer login, user@example.com, belongs to. */
export const PORTAL_CUSTOMER = 'Marlowe & Finch Bakery';

const TAX = { name: 'Tax', percent: 10 };

/**
 * Customers, each with a first contact (who becomes the customer's primary contact), any
 * further contacts, and a website or two. The first customer's first contact is the demo's
 * customer login.
 */
export const customers = [
  {
    name: PORTAL_CUSTOMER,
    portal: true,
    contact: { name: 'Ines', last_name: 'Marlowe', email: 'user@example.com', phone: '555-0114', mobile: '555-0151' },
    more: [{ name: 'Tobias', last_name: 'Finch', email: 'tobias.finch@example.com', phone: '555-0114' }],
    address: { line_1: '14 Mill Lane', suburb: 'Hartley', state: 'CA', post_code: '90210', country: 'United States' },
    websites: [{ name: 'Marlowe & Finch: main site', url: 'https://marlowefinch.example.com' }],
  },
  {
    name: 'Kestrel Outdoor Supplies',
    contact: { name: 'Tom', last_name: 'Aldous', email: 'tom.aldous@example.com', phone: '555-0132', mobile: '555-0187' },
    more: [{ name: 'Rhea', last_name: 'Castellan', email: 'rhea.castellan@example.com' }],
    address: { line_1: 'Unit 6, Ridgeway Trading Estate', suburb: 'Brackenfield', state: 'OR', post_code: '97201', country: 'United States' },
    websites: [{ name: 'Kestrel: online shop', url: 'https://kestrel-outdoor.example.com' }],
  },
  {
    name: 'Harbourview Physiotherapy',
    contact: { name: 'Priya', last_name: 'Nair', email: 'priya.nair@example.com', phone: '555-0176' },
    address: { line_1: '2 Quay Street', suburb: 'Port Ellery', state: 'WA', post_code: '98101', country: 'United States' },
    websites: [{ name: 'Harbourview: clinic site', url: 'https://harbourviewphysio.example.com' }],
  },
  {
    name: 'Lumen & Oak Interiors',
    contact: { name: 'Callum', last_name: 'Reyes', email: 'callum.reyes@example.com', phone: '555-0109' },
    address: { line_1: '88 Foundry Row', suburb: 'Oldbridge', state: 'NY', post_code: '10001', country: 'United States' },
    websites: [],
  },
  {
    name: 'Tidewater Brewing Co.',
    contact: { name: 'Sasha', last_name: 'Okafor', email: 'sasha.okafor@example.com', phone: '555-0163', mobile: '555-0120' },
    address: { line_1: 'The Old Net Loft, Quay Road', suburb: 'Saltmarsh', state: 'ME', post_code: '04101', country: 'United States' },
    websites: [{ name: 'Tidewater: taproom site', url: 'https://tidewaterbrewing.example.com' }],
  },
  {
    name: 'Quillfeather Books',
    contact: { name: 'Meg', last_name: 'Dunmore', email: 'meg.dunmore@example.com', phone: '555-0148' },
    address: { line_1: '5 Bellwether Court', suburb: 'Thornby', state: 'VT', post_code: '05401', country: 'United States' },
    websites: [],
  },
];

export const vendors = [
  { name: 'Parcelhawk Hosting', contact: { name: 'Rowan', last_name: 'Pike', email: 'rowan.pike@example.com', phone: '555-0301' } },
  { name: 'Greenline Print & Signage', contact: { name: 'Dee', last_name: 'Hartnett', email: 'dee.hartnett@example.com', phone: '555-0322' } },
  { name: 'Brightside Illustration', contact: { name: 'Noor', last_name: 'Fielding', email: 'noor.fielding@example.com', phone: '555-0344' } },
];

/**
 * Jobs. A task is hourly (hours at the job's rate) unless it has an amount, which makes it
 * a fixed price. `logged` is the hours already worked on it; a task that is `done` has all
 * of its hours logged. `timers` are stretches of time recorded against the job with the
 * timer: [days ago, hours, what it was].
 */
export const jobs = [
  {
    key: 'bakery-site', customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: new website', type: 'Website Design',
    status: 'In Progress', rate: 90, start: -62, due: 18, website: 0,
    tasks: [
      { description: 'Discovery workshop and sitemap', hours: 3, done: true, due: -55 },
      { description: 'Wireframes for the home, menu and order pages', hours: 6, done: true, due: -45 },
      { description: 'Visual design and style guide', hours: 12, done: true, due: -30 },
      { description: 'Online ordering for collection', hours: 20, logged: 13, due: 4 },
      { description: 'Move the existing menu and photos across', hours: 8, logged: 3, due: 9 },
      { description: 'Launch and a morning of staff training', hours: 4, due: 18 },
    ],
    timers: [[3, 2.5, 'Ordering form: pickup slots'], [2, 1.25, 'Ordering form: basket and totals']],
  },
  {
    key: 'bakery-care', customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: care plan, this month', type: 'Maintenance',
    status: 'In Progress', rate: 75, start: -9, due: 21, website: 0,
    tasks: [
      { description: 'Updates, backups and uptime checks', hours: 2, done: true, due: -2 },
      { description: 'Seasonal menu and opening hours changes', hours: 3, logged: 1.5, due: 10 },
      { description: 'Monthly traffic summary', hours: 1, due: 20 },
    ],
    timers: [[4, 0.75, 'Update the Christmas hours banner']],
  },
  {
    key: 'kestrel-shop', customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: shop redesign', type: 'Website Design',
    status: 'Completed', rate: 95, start: -120, due: -28, done: -24, website: 0,
    tasks: [
      { description: 'Category and filter structure', hours: 8, done: true, due: -105 },
      { description: 'Product page template', hours: 14, done: true, due: -90 },
      { description: 'Checkout and shipping rules', hours: 16, done: true, due: -66 },
      { description: 'Speed and mobile pass', hours: 6, done: true, due: -40 },
      { description: 'Go-live and 30 days of fixes', hours: 5, done: true, due: -28 },
    ],
    timers: [],
  },
  {
    key: 'kestrel-photos', customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: product photo retouching', type: 'Design',
    status: 'In Progress', rate: 70, start: -60, due: 14,
    tasks: [
      { description: 'Retouch batch 1 (tents and packs)', amount: 480, done: true, due: -50 },
      { description: 'Retouch batch 2 (stoves and footwear)', amount: 520, done: true, due: -20 },
      { description: 'Retouch batch 3 (clothing)', amount: 640, due: 14 },
    ],
    timers: [[6, 3, 'Clothing: colour matching']],
  },
  {
    key: 'physio-booking', customer: 'Harbourview Physiotherapy', name: 'Harbourview: online booking', type: 'Development',
    status: 'In Progress', rate: 100, start: -40, due: 30, website: 0,
    tasks: [
      { description: 'Map the clinic\'s appointment types', hours: 4, done: true, due: -33 },
      { description: 'Connect the booking calendar', hours: 18, logged: 11, due: 12 },
      { description: 'Reminder emails and cancellations', hours: 10, due: 24 },
      { description: 'Test with the front desk team', hours: 4, due: 30 },
    ],
    timers: [[1, 4, 'Booking calendar: sync with the clinic diary']],
  },
  {
    key: 'lumen-brand', customer: 'Lumen & Oak Interiors', name: 'Lumen & Oak: brand refresh and logo', type: 'Design',
    status: 'Completed', rate: 85, start: -95, due: -50, done: -47,
    tasks: [
      { description: 'Moodboards and three logo routes', hours: 10, done: true, due: -85 },
      { description: 'Final logo, colours and typefaces', hours: 8, done: true, due: -70 },
      { description: 'Stationery and email signature', amount: 350, done: true, due: -55 },
    ],
    timers: [],
  },
  {
    key: 'tidewater-labels', customer: 'Tidewater Brewing Co.', name: 'Tidewater: autumn seasonal labels', type: 'Design',
    status: 'New', rate: 80, start: 5, due: 26, website: 0,
    tasks: [
      { description: 'Concepts for the harvest ale and the porter', hours: 6, due: 12 },
      { description: 'Final artwork, print-ready', hours: 6, due: 20 },
      { description: 'Proof check with the printer', hours: 2, due: 26 },
    ],
    timers: [],
  },
  {
    key: 'quill-newsletter', customer: 'Quillfeather Books', name: 'Quillfeather: newsletter template', type: 'Design',
    status: 'Completed', rate: 75, start: -26, due: -12, done: -10,
    tasks: [
      { description: 'Newsletter layout, three variations', hours: 4, done: true, due: -20 },
      { description: 'Build and test in the main email clients', hours: 3, done: true, due: -12 },
    ],
    timers: [],
  },
];

/** Quotes. `status` is whatever the agency calls it; an accepted one has been approved. */
export const quotes = [
  {
    customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: loyalty card and offers', status: 'Sent', rate: 90, created: -6,
    tasks: [
      { description: 'Digital loyalty card, stamps and rewards', hours: 14 },
      { description: 'Offer emails to members, two templates', hours: 6 },
      { description: 'Staff guide and launch week support', hours: 4 },
    ],
  },
  {
    customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: spring catalogue microsite', status: 'Accepted', rate: 95, created: -34, approved: -29,
    tasks: [
      { description: 'Catalogue microsite, 12 pages', hours: 22 },
      { description: 'Product feed from the shop', amount: 400 },
      { description: 'Launch email and social tiles', hours: 5 },
    ],
  },
  {
    customer: 'Lumen & Oak Interiors', name: 'Lumen & Oak: showroom booking site', status: 'Sent', rate: 85, created: -15,
    tasks: [
      { description: 'Showroom pages and project gallery', hours: 24 },
      { description: 'Appointment booking form', hours: 8 },
    ],
  },
  {
    customer: 'Tidewater Brewing Co.', name: 'Tidewater: taproom events page', status: 'Declined', rate: 80, created: -47,
    tasks: [
      { description: 'Events calendar and ticket links', hours: 12 },
      { description: 'Monthly updates for a year', amount: 600 },
    ],
  },
];

/**
 * Invoices, in every state the list can show. `sent` is how many days ago it went out,
 * absent for a draft; `paid` lists payments as [days ago, share of the total, how], and
 * pays it in full when the shares reach 1 and in part when they do not. `job` names the job
 * whose tasks the lines bill: a line with a task's description is tied to that task.
 */
export const invoices = [
  {
    customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: website design, deposit', created: -62, sent: -62, due: -48,
    status: 'Sent', paid: [[-55, 1, 'Bank transfer']],
    lines: [{ description: 'Website design: 50% deposit', amount: 2200 }],
  },
  {
    customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: website design, second stage', created: -24, sent: -24, due: -10,
    status: 'Sent', paid: [[-12, 0.5, 'Bank transfer']], job: 'bakery-site',
    lines: [
      { description: 'Discovery workshop and sitemap', hours: 3, rate: 90 },
      { description: 'Wireframes for the home, menu and order pages', hours: 6, rate: 90 },
      { description: 'Visual design and style guide', hours: 12, rate: 90 },
    ],
  },
  {
    customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: care plan, last month', created: -22, sent: -22, due: -8,
    status: 'Sent', paid: [[-9, 1, 'Credit card']],
    lines: [{ description: 'Monthly care plan: updates, backups, edits', hours: 5, rate: 75 }],
  },
  {
    customer: PORTAL_CUSTOMER, name: 'Marlowe & Finch: care plan, this month', created: -1, sent: -1, due: 13,
    status: 'Sent', paid: [],
    lines: [{ description: 'Monthly care plan: updates, backups, edits', hours: 5, rate: 75 }],
  },
  {
    customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: shop redesign, first stage', created: -88, sent: -88, due: -74,
    status: 'Sent', paid: [[-79, 1, 'Cheque']], job: 'kestrel-shop',
    lines: [
      { description: 'Category and filter structure', hours: 8, rate: 95 },
      { description: 'Product page template', hours: 14, rate: 95 },
    ],
  },
  {
    customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: shop redesign, final invoice', created: -27, sent: -27, due: -13,
    status: 'Sent', paid: [[-18, 1, 'Cheque']], job: 'kestrel-shop',
    lines: [
      { description: 'Checkout and shipping rules', hours: 16, rate: 95 },
      { description: 'Speed and mobile pass', hours: 6, rate: 95 },
      { description: 'Go-live and 30 days of fixes', hours: 5, rate: 95 },
    ],
  },
  {
    customer: 'Kestrel Outdoor Supplies', name: 'Kestrel: photo retouching, batches 1 and 2', created: -19, sent: -19, due: -5,
    status: 'Sent', paid: [], job: 'kestrel-photos',
    lines: [
      { description: 'Retouch batch 1 (tents and packs)', amount: 480 },
      { description: 'Retouch batch 2 (stoves and footwear)', amount: 520 },
    ],
  },
  {
    customer: 'Harbourview Physiotherapy', name: 'Harbourview: online booking, discovery', created: -37, sent: -37, due: -23,
    status: 'Sent', paid: [[-30, 1, 'Bank transfer']], job: 'physio-booking',
    lines: [{ description: 'Map the clinic\'s appointment types', hours: 4, rate: 100 }],
  },
  {
    customer: 'Harbourview Physiotherapy', name: 'Harbourview: online booking, stage 1', created: -4, sent: -4, due: 10,
    status: 'Sent', paid: [],
    lines: [{ description: 'Connect the booking calendar: 11 hours so far', hours: 11, rate: 100 }],
  },
  {
    customer: 'Lumen & Oak Interiors', name: 'Lumen & Oak: brand refresh and logo', created: -46, sent: -46, due: -32,
    status: 'Sent', paid: [[-33, 1, 'Credit card']], job: 'lumen-brand',
    lines: [
      { description: 'Moodboards and three logo routes', hours: 10, rate: 85 },
      { description: 'Final logo, colours and typefaces', hours: 8, rate: 85 },
      { description: 'Stationery and email signature', amount: 350 },
    ],
  },
  {
    customer: 'Quillfeather Books', name: 'Quillfeather: newsletter template', created: -10, sent: -10, due: 4,
    status: 'Sent', paid: [[-3, 1, 'Credit card']], job: 'quill-newsletter',
    lines: [
      { description: 'Newsletter layout, three variations', hours: 4, rate: 75 },
      { description: 'Build and test in the main email clients', hours: 3, rate: 75 },
    ],
  },
  {
    customer: 'Tidewater Brewing Co.', name: 'Tidewater: seasonal labels, deposit', created: -1,
    status: 'Draft', paid: [],
    lines: [{ description: 'Autumn seasonal labels: 30% deposit', amount: 480 }],
  },
];

/**
 * Support tickets, raised by the customer's primary contact. Each message is [who, text], who
 * being 'staff' or 'customer'. The status is the app's own: 2 New, 3 Replied, 5 In Progress,
 * 6 Resolved.
 */
export const tickets = [
  {
    customer: PORTAL_CUSTOMER, subject: 'Opening hours on the website are still showing summer times', status: 3,
    messages: [
      ['customer', 'Hi, our shop hours changed on Monday but the website still shows the summer hours. Could you update them before the weekend?'],
      ['staff', 'Thanks Ines, I have changed the hours on the home page and the contact page. It can take a few minutes to show if your browser has the old page saved; a refresh will clear it.'],
    ],
  },
  {
    customer: PORTAL_CUSTOMER, subject: 'Can we add gluten-free labels to the menu page?', status: 5,
    messages: [
      ['customer', 'We would like a small gluten-free badge next to the items on the menu page. Is that a quick job or part of the care plan?'],
      ['staff', 'It is a small change, so it comes out of this month\'s care plan hours. Could you send me the list of items that should carry the badge?'],
      ['customer', 'Of course, the sourdough, the almond cake and both flapjacks. Thank you!'],
    ],
  },
  {
    customer: 'Kestrel Outdoor Supplies', subject: 'Checkout shows the wrong shipping rate for Alaska', status: 6,
    messages: [
      ['customer', 'A customer in Alaska was charged the standard rate, but we only ship there with the freight option.'],
      ['staff', 'Found it: the Alaska and Hawaii rule was listed after the catch-all rate. I have moved it up and tested an order for each state.'],
      ['customer', 'Brilliant, that is working now. Thanks for the quick fix.'],
    ],
  },
  {
    customer: 'Harbourview Physiotherapy', subject: 'Booking confirmation emails going to spam', status: 2,
    messages: [
      ['customer', 'Two patients told us the booking confirmation went to their spam folder. Is there something we can do?'],
    ],
  },
  {
    customer: 'Tidewater Brewing Co.', subject: 'Question about label sizes for the printer', status: 3,
    messages: [
      ['customer', 'The printer asked what bleed we need on the new labels. Do you have that to hand?'],
      ['staff', 'Yes: 3mm bleed on every side, and keep any text 5mm inside the trim. I will put it in the brief for the printer too.'],
    ],
  },
];

/** Calendar entries: [days from today, start hour, hours long, subject, where, customer or null]. */
export const calendar = [
  [0, 14, 1, 'Marlowe & Finch: ordering form walkthrough', 'Video call', PORTAL_CUSTOMER],
  [1, 10, 1.5, 'Harbourview: booking calendar test with the front desk', 'Harbourview Physiotherapy, 2 Quay Street', 'Harbourview Physiotherapy'],
  [3, 9, 1, 'Team: planning for the week', 'Studio', null],
  [4, 15, 1, 'Lumen & Oak: showroom site, questions on the quote', 'Video call', 'Lumen & Oak Interiors'],
  [6, 11, 2, 'Tidewater: label concepts review', 'The Old Net Loft', 'Tidewater Brewing Co.'],
  [8, 9, 1, 'Team: planning for the week', 'Studio', null],
  [12, 13, 1, 'Kestrel: catalogue microsite kick-off', 'Video call', 'Kestrel Outdoor Supplies'],
  [-2, 16, 1, 'Quillfeather: newsletter hand-over', 'Video call', 'Quillfeather Books'],
  [-9, 10, 1.5, 'Marlowe & Finch: website stage 2 sign-off', 'The bakery, 14 Mill Lane', PORTAL_CUSTOMER],
];

/**
 * Files the agency has shared with a customer: a name, a link, a status and a few words.
 * They are links, not uploads, so the demo never holds a file that a restore or a deploy
 * could lose, or that a visitor deleting the record could take off the disk.
 */
export const files = [
  { customer: PORTAL_CUSTOMER, job: 'bakery-site', name: 'Marlowe & Finch style guide v2', url: 'https://example.com/files/marlowe-finch-style-guide-v2.pdf', status: 'Approved', description: 'Colours, typefaces and logo rules signed off at the stage 2 review.' },
  { customer: PORTAL_CUSTOMER, job: 'bakery-site', name: 'Website sitemap and wireframes', url: 'https://example.com/files/marlowe-finch-wireframes.pdf', status: 'Approved', description: 'The page list and the wireframes for the home, menu and order pages.' },
  { customer: PORTAL_CUSTOMER, job: 'bakery-site', name: 'Menu photography shortlist', url: 'https://example.com/files/marlowe-finch-menu-photos.zip', status: 'For review', description: 'Twenty photographs picked for the menu page. Please mark the ones you do not want.' },
  { customer: 'Kestrel Outdoor Supplies', job: 'kestrel-photos', name: 'Retouching guidelines', url: 'https://example.com/files/kestrel-retouching-guidelines.pdf', status: 'Approved', description: 'How shadows, colour and backgrounds should look across the catalogue.' },
  { customer: 'Lumen & Oak Interiors', job: 'lumen-brand', name: 'Lumen & Oak brand pack', url: 'https://example.com/files/lumen-oak-brand-pack.zip', status: 'Approved', description: 'Final logo files, colours and the email signature.' },
];

/** Notes on a customer: [customer, days ago, the note]. */
export const notes = [
  [PORTAL_CUSTOMER, 40, 'Ines prefers a short call to a long email. Tobias handles the invoices; copy him on anything with a total.'],
  [PORTAL_CUSTOMER, 12, 'Agreed to move the care plan to the first of the month. Ordering form launches after the second stage invoice is paid.'],
  ['Kestrel Outdoor Supplies', 21, 'Tom would like the spring catalogue online before the trade show. Photography has to be finished first.'],
  ['Harbourview Physiotherapy', 8, 'Front desk uses the older diary software. Booking sync has to keep working with it.'],
  ['Lumen & Oak Interiors', 30, 'Waiting on the showroom photographs before the site quote can go ahead.'],
];

export { TAX };
