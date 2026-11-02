import { execSync } from 'child_process';
import { readdirSync, readFileSync } from 'fs';
import { test, expect, Page, APIRequestContext } from '@playwright/test';
import { login, formAuthKey } from './seed';

/**
 * A real update, over a real request.
 *
 * Updating is the most dangerous thing the application does to itself, and until now none
 * of it was exercised anywhere. tests/unit/UpgradeIntegrityTest.php covers the decisions -
 * enumeration, checksums, path safety - with no network in the way. This covers the leg
 * that one cannot: the client POSTing to a running update server and writing what comes
 * back onto disk.
 *
 * The installation updates itself from itself. includes/plugin_config/upgrade_server.php
 * is the server half and ships with the app, so the app on :8090 can serve it to itself
 * over localhost. The server and the client are therefore looking at the same files, and
 * a successful update writes every file back byte for byte - which the git check at the
 * end relies on: a working copy that is still clean afterwards is the proof that the
 * bytes that arrived are the bytes that were sent.
 *
 * The `config` plugin is the one updated here deliberately: it is the plugin this code
 * lives in, so nothing outside this scope is touched even if something goes wrong.
 */

const SETTINGS_URL = '/?m[]=config&p[]=config_settings&_process=save_config';
const UPGRADE_URL = '/?m[]=config&p[]=config_upgrade';
const SERVER_URL = 'http://localhost/includes/plugin_config/upgrade_server.php';
const SCOPE = 'includes/plugin_config';

/** Purchase codes the stand-in for Envato's API (tests/e2e/fixtures/envato-stub.php) knows. */
const LICENCE = '00000000-e2e0-4000-8000-000000000001';
const OTHER_ITEM = '00000000-e2e0-4000-8000-000000000002';
const OUTAGE = '00000000-e2e0-4000-8000-0000000000ff';

/** Settings this spec changes, and what they were before it started. */
const CHANGED = ['upgrade_server_enabled', 'ucm_upgrade_url', '_installation_code'] as const;
const originals: Record<string, string> = {};

/**
 * The advanced settings page lays out a few thousand rows, so it is loaded once and every
 * value this spec needs is read out of that one render.
 */
async function openSettings(page: Page): Promise<string> {
  await page.goto('/?m[]=config&p[]=config_settings');
  const key = await page.evaluate(() => (window as any).ucm?.form_auth_key);
  expect(key, 'the settings page should publish a form auth key').toBeTruthy();
  return key;
}

async function readSettings(page: Page, names: readonly string[]): Promise<Record<string, string>> {
  return page.evaluate((wanted) => {
    const out: Record<string, string> = {};
    for (const name of wanted) {
      const span = document.querySelector(`span.config_variable[data-name="config[${name}]"]`);
      out[name] = span ? (span.textContent ?? '').trim() : '';
    }
    return out;
  }, names as string[]);
}

async function saveSettings(page: Page, key: string, values: Record<string, string>) {
  const form: Record<string, string> = { _process: 'save_config', form_auth_key: key };
  for (const [name, value] of Object.entries(values)) {
    form[`config[${name}]`] = value;
  }
  const response = await page.request.post(SETTINGS_URL, { form, maxRedirects: 0, failOnStatusCode: false });
  expect(response.status(), 'saving settings should redirect, not refuse').toBeLessThan(400);
}

function workingCopy(): string {
  return execSync(`git status --porcelain -- ${SCOPE}`, { encoding: 'utf8' });
}

async function postUpgrade(request: APIRequestContext, form: Record<string, string>) {
  const response = await request.post(UPGRADE_URL, { form, failOnStatusCode: false });
  return { status: response.status(), body: await response.text() };
}

test.describe.configure({ mode: 'serial' });

test.describe('Updating from an update server', () => {
  test.beforeAll(async ({ browser }) => {
    test.setTimeout(120000);
    const page = await browser.newPage();
    await login(page);
    const key = await openSettings(page);
    Object.assign(originals, await readSettings(page, CHANGED));
    await saveSettings(page, key, {
      upgrade_server_enabled: '1',
      ucm_upgrade_url: SERVER_URL,
      // The update server only answers a request that carries a purchase code it can verify.
      _installation_code: LICENCE,
    });
    await page.close();
  });

  test.afterAll(async ({ browser }) => {
    test.setTimeout(120000);
    const page = await browser.newPage();
    await login(page);
    const key = await openSettings(page);
    await saveSettings(page, key, {
      upgrade_server_enabled: originals.upgrade_server_enabled || '0',
      ucm_upgrade_url: originals.ucm_upgrade_url || 'https://api.ultimateclientmanager.com/upgrade.php',
      _installation_code: originals._installation_code || '',
    });
    await page.close();
  });

  test('the update server answers without a warning in the body', async ({ request }) => {
    const response = await request.post(SERVER_URL.replace('http://localhost', ''), {
      form: { installation_code: LICENCE, current_plugins: '{}', requested_plugin: 'config', client_protocol: '2' },
    });
    expect(response.status()).toBe(200);
    const body = await response.text();
    // A bare request used to read two $_REQUEST keys with no isset(), so under E_ALL the
    // warnings landed in front of the JSON and the client's json_decode() returned null.
    expect(body.trimStart().startsWith('{'), `response should be JSON, got: ${body.slice(0, 200)}`).toBe(true);
    const data = JSON.parse(body);
    expect(data.protocol).toBeGreaterThanOrEqual(2);
    expect(data.available_updates.plugins.config.files.length).toBeGreaterThan(0);
  });

  test('a request with no purchase code, or one that is not a sale of UCM, gets a message and no files', async ({ request }) => {
    const path = SERVER_URL.replace('http://localhost', '');
    const ask = async (code: string | null) => {
      const form: Record<string, string> = { current_plugins: '{}', requested_plugin: 'config', get_file_contents: '1', client_protocol: '2' };
      if (code !== null) form.installation_code = code;
      const response = await request.post(path, { form });
      expect(response.status()).toBe(200);
      return JSON.parse(await response.text());
    };

    for (const code of [null, 'free', 'not-a-code', OTHER_ITEM, '00000000-e2e0-4000-8000-000000000003']) {
      const data = await ask(code);
      expect(data.available_updates.plugins, `code ${code}`).toEqual([]);
      expect(data.available_updates.licence_required, `code ${code}`).toBe(true);
      expect(data.available_updates.message, `code ${code}`).toMatch(/licence code/i);
    }
    const unreachable = await ask(OUTAGE);
    expect(unreachable.available_updates.plugins).toEqual([]);
    expect(unreachable.available_updates.message).toContain('try again');
  });

  test('a valid code is answered with the item it is for', async ({ request }) => {
    const response = await request.post(SERVER_URL.replace('http://localhost', ''), {
      form: { installation_code: LICENCE, current_plugins: '{}', requested_plugin: 'config', client_protocol: '2' },
    });
    const data = JSON.parse(await response.text());
    expect(Object.keys(data.licence_codes)).toEqual([LICENCE]);
  });

  test('every file in a download comes with a checksum that matches it', async ({ request }) => {
    const response = await request.post(SERVER_URL.replace('http://localhost', ''), {
      form: {
        installation_code: LICENCE,
        current_plugins: '{}',
        requested_plugin: 'config',
        get_file_contents: '1',
        client_protocol: '2',
      },
    });
    const data = JSON.parse(await response.text());
    const update = data.available_updates.plugins.config;
    const names = Object.keys(update.file_contents);
    expect(names.length).toBeGreaterThan(0);

    const { createHash } = await import('crypto');
    for (const name of names) {
      const body = Buffer.from(update.file_contents[name], 'base64');
      expect(update.file_checksums[name], `${name} should carry a checksum`).toBeTruthy();
      expect(createHash('sha256').update(body).digest('hex'), `${name} checksum`).toBe(update.file_checksums[name]);
    }
  });

  test('a newer release is offered with no plugin bump, to a client that says which it runs', async ({ request }) => {
    // Every plugin at a version far above anything on the server, so no plugin is offered
    // and the core rows can only be there because of the release.
    const current: Record<string, string> = {};
    for (const entry of readdirSync('includes')) {
      if (entry.startsWith('plugin_')) current[entry.slice('plugin_'.length)] = '999';
    }
    const release = /define\(\s*'_SCRIPT_VERSION',\s*'([^']+)'/.exec(readFileSync('init.php', 'utf8'))![1];
    const ask = async (extra: Record<string, string>) => {
      const response = await request.post(SERVER_URL.replace('http://localhost', ''), {
        form: { installation_code: LICENCE, current_plugins: JSON.stringify(current), client_protocol: '2', ...extra },
      });
      expect(response.status()).toBe(200);
      return (await response.text()).trim();
    };

    const older = JSON.parse(await ask({ release_version: '3.555' }));
    expect(Object.keys(older.available_updates.plugins)).toEqual(['corefiles', 'database']);
    const core = older.available_updates.plugins.corefiles;
    expect(core.installed_version).toBe('3.555');
    expect(core.available_version).toBe(release);
    expect(core.description).toContain(`${release}: `);
    expect(core.files).toContain('init.php');

    // The same release, and a client that sends none at all, get the empty answer.
    expect(await ask({ release_version: release })).toBe('');
    expect(await ask({})).toBe('');
  });

  test('an upgrade with no form auth key is refused', async ({ page }) => {
    await login(page);
    const before = workingCopy();

    const result = await postUpgrade(page.request, {
      install_upgrade: 'yes',
      via_ajax: 'yes',
      plugin_name: 'config',
    });

    expect(result.body).toContain('out of date');
    expect(result.body).not.toContain('installed</strong> successfully');
    expect(workingCopy(), 'a refused upgrade must not write anything').toBe(before);
  });

  test('an upgrade installs the files it is sent, unchanged', async ({ page }) => {
    await login(page);
    const before = workingCopy();
    const key = await formAuthKey(page);

    const result = await postUpgrade(page.request, {
      install_upgrade: 'yes',
      via_ajax: 'yes',
      plugin_name: 'config',
      form_auth_key: key,
    });

    expect(result.status).toBe(200);
    const data = JSON.parse(result.body);
    expect(data.errors, `the upgrade reported: ${data.message}`).toEqual([]);
    expect(data.success).toBe(1);
    expect(data.message).toContain('installed</strong> successfully');
    expect(data.message).not.toContain('REFUSED');

    // The server and the client are the same installation, so every file written is the
    // file that was already there. Anything else - a truncated write, a mangled encoding,
    // a path escaping the plugin - shows up here as a dirty working copy.
    expect(workingCopy(), 'the update wrote something different from what it was sent').toBe(before);
  });

  test('an installation already up to date is offered nothing', async ({ page }) => {
    // The server here is this same installation, so there is genuinely nothing to send.
    // An update mechanism that offers an update anyway is one that never stops nagging.
    await login(page);
    const key = await formAuthKey(page);
    const response = await page.request.post(UPGRADE_URL, {
      form: { check_upgrade: 'yes', form_auth_key: key },
      failOnStatusCode: false,
    });
    const body = await response.text();

    expect(response.status()).toBe(200);
    expect(body).toContain('No upgrade available at this time');
    expect(body).not.toContain('Incorrect API url');
    expect(body).not.toMatch(/\b(Warning|Notice|Deprecated|Fatal error)<\/b>:/);
  });
  test('without a licence code the Upgrade page asks for one and does not contact the update server', async ({ page }) => {
    test.setTimeout(120000);
    await login(page);
    const settingsKey = await openSettings(page);
    await saveSettings(page, settingsKey, { _installation_code: '' });
    try {
      const key = await formAuthKey(page);
      const response = await page.request.post(UPGRADE_URL, {
        form: { check_upgrade: 'yes', form_auth_key: key },
        failOnStatusCode: false,
      });
      const body = await response.text();
      expect(response.status()).toBe(200);
      expect(body).toContain('Automated updates need a licence code');
      expect(body).not.toContain('No upgrade available at this time');

      await page.goto(UPGRADE_URL);
      await expect(page.getByText('Please enter your license code below to receive automated updates').first()).toBeVisible();
      await expect(page.getByText('the address of this site are sent to the update server').first()).toBeVisible();
    } finally {
      const again = await openSettings(page);
      await saveSettings(page, again, { _installation_code: LICENCE });
    }
  });
});
