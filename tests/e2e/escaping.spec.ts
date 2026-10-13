import { test, expect, Page } from '@playwright/test';
import { DIAGNOSTIC, describeDiagnostics } from './diagnostics';
import { createRecord, login } from './seed';

/**
 * Record ids that go straight back out into the page.
 *
 * Several edit screens read an id out of the request and printed it into a hidden field
 * without escaping it. The id is not always a number - "new" opens the create form, and
 * the data module also uses "import" - so these cannot simply be cast to int, and casting
 * them was how an earlier attempt at this broke record creation. They are escaped instead.
 *
 * The payload closes the value attribute and opens a tag. If it comes back intact the page
 * is injectable; if it comes back with the quote and the angle bracket encoded, it is not.
 * Asserting on the raw payload rather than on "no alert fired" is deliberate - a payload
 * can fail to fire for reasons that have nothing to do with the escaping.
 */

const PAYLOAD = '"><script>alert(1)</script>';

/**
 * Pages that take an id straight from the query string.
 *
 * `marker` is text the page only prints once it has reached the part that echoes the id.
 * Without it this suite was green against the unpatched code on three of these seven: a
 * bogus id makes some screens fall back to the create form, so the payload never reaches
 * an echo and the test proves nothing. Checked by reverting each fix in turn - four pages
 * reflected the payload, three did not.
 */
type Target = { name: string; marker: string; proven: boolean; url: string | ((page: Page) => Promise<string | null>) };

/** The data field screen insists on a type and a fieldset that exist. Find a pair. */
async function aDataFieldUrl(page: Page): Promise<string | null> {
  // Make the pair rather than hunt for one. A fresh install has no data types at all, and
  // the ids in this development database are not the ids CI will have.
  const stamp = Date.now();
  const dataTypeId = await createRecord(page, '/?m[]=data', {
    _process: 'save_data_type',
    data_type_id: 'new',
    data_type_name: 'Escaping Type ' + stamp,
  }, 'data_type_id');

  const groupId = await createRecord(page, '/?m[]=data', {
    _process: 'save_data_field_group',
    data_field_group_id: 'new',
    data_type_id: dataTypeId,
    name: 'Escaping Fieldset ' + stamp,
    _redirect: '/',
  }, 'data_field_group_id');

  return `/?m[]=data&p[]=data_type_admin_field_open&data_type_id=${dataTypeId}`
    + `&data_field_group_id=${groupId}&data_field_id=${encodeURIComponent(PAYLOAD)}`;
}

const REFLECTED: Target[] = [
  { name: 'data type', proven: true, marker: 'save_data_type',
    url: `/?m[]=data&p[]=data_type_admin_open&data_type_id=${encodeURIComponent(PAYLOAD)}` },
  // Needs a real type and fieldset: without both the page says "No type defined" and never
  // renders the form, so the payload would have nowhere to land and the test would pass on
  // nothing. The ids are looked up rather than written down - a fresh install numbers them
  // differently from this development database.
  { name: 'data field', proven: true, marker: 'save_data_field', url: aDataFieldUrl },
  { name: 'contact list', proven: true, marker: 'contact_admin_list',
    url: `/?m[]=user&p[]=contact_admin_list&customer_id=${encodeURIComponent(PAYLOAD)}` },
  { name: 'map search', proven: true, marker: 'geocoder.geocode',
    url: `/?m[]=map&p[]=map_admin&search[location]=${encodeURIComponent(PAYLOAD)}` },
  // These three escape the id too, but a bad id sends them to the create form before the
  // echo, so the escaping is hardening rather than a demonstrated fix. Kept so that a
  // future change which does let the value through is caught here.
  { name: 'security role', proven: false, marker: 'save_security_role',
    url: `/?m[]=security&p[]=security_role_edit&security_role_id=${encodeURIComponent(PAYLOAD)}` },
  { name: 'template', proven: false, marker: 'save_template',
    url: `/?m[]=template&p[]=template_edit&template_id=${encodeURIComponent(PAYLOAD)}` },
  { name: 'invoice print', proven: false, marker: 'Print Multiple PDFs',
    url: `/?m[]=invoice&p[]=invoice_admin_print&invoice_ids=${encodeURIComponent(PAYLOAD)}` },
];

test.describe('Reflected ids are escaped', () => {
  for (const entry of REFLECTED) {
    test(`${entry.name} does not reflect the payload`, async ({ page }) => {
      await login(page);
      const url = typeof entry.url === 'string' ? entry.url : await entry.url(page);
      test.skip(url === null, `${entry.name} has no record to open`);
      const response = await page.request.get(url as string, { failOnStatusCode: false });
      const body = await response.text();

      // A screen the login cannot reach proves nothing either way, so say so rather than
      // passing quietly on a redirect to the dashboard.
      test.skip(response.status() >= 400, `${entry.name} answered ${response.status()}`);

      // A page that never rendered the part under test cannot say anything about it.
      if (entry.proven) {
        expect(body, `${entry.name} did not reach the markup that echoes the id`).toContain(entry.marker);
      }

      expect(body, `${entry.name} reflected the payload unescaped`).not.toContain(PAYLOAD);
      expect(body, `${entry.name} reflected an unescaped script tag`).not.toContain('<script>alert(1)</script>');
      expect(
        describeDiagnostics(body).join('\n'),
        `${entry.name} printed a PHP diagnostic`,
      ).toBe('');
    });
  }

  test('error reporting is on, so the rest of this suite means something', async ({ page }) => {
    await login(page);
    const body = await (await page.request.get('/?m[]=customer&p[]=customer_admin&customer_id[]=1')).text();
    expect(
      DIAGNOSTIC.test(body),
      'No PHP diagnostics came back at all. Run the app with UCM_DEBUG=1 (docker-compose.yml sets it).',
    ).toBe(true);
  });
});
