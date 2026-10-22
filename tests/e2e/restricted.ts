import { createHash } from 'node:crypto';
import { Browser, expect, Page } from '@playwright/test';
import { deleteRecord, createRecord, formAuthKey } from './seed';
import { checksumTables } from './db';

/**
 * A signed-in login with a role of our own choosing, for the specs that hold the line
 * between what a role allows and what the save handlers behind the forms will do.
 *
 * The forms and menus have always honoured permissions. The specs that use this post
 * straight to the save endpoints instead, with a real form token, as a login whose role
 * grants nothing but the right to sign in - and check the database did not move.
 *
 * Both the role and the login are made for the run and removed again by the returned
 * cleanup: neither can be found a second time, and leaving them behind is how a role
 * list ends up with hundreds of rows.
 */

/** One row of the role form's permission grid. */
export type Grant = {
  /** the "Category" column of the grid, e.g. "Customer" */
  category: string;
  /** the "Permissions" column, e.g. "Customers" */
  name: string;
  /** which of view, edit, create and delete to tick */
  actions: Array<'view' | 'edit' | 'create' | 'delete'>;
};

/**
 * The id of the grid row `category` / `name`, read off the role form itself.
 *
 * Only the first row of a category prints its name; the rows under it leave that cell
 * blank, so the category is carried down from the last one that had one.
 */
export function permissionId(form: string, category: string, name: string): string {
  const text = (html: string) => html.replace(/<[^>]*>/g, '').replace(/&nbsp;|&raquo;/g, '').trim();
  let current = '';
  for (const row of form.split('<tr').slice(1)) {
    const cells = [...row.matchAll(/<td[^>]*>([\s\S]*?)<\/td>/g)].map(match => match[1]);
    if (cells.length < 3 || !cells[1].includes('&raquo;')) {
      continue;
    }
    current = text(cells[0]) || current;
    if (current === category && text(cells[1]) === name) {
      const id = /data-permission="(\d+)"/.exec(row);
      expect(id, `the "${category} » ${name}" row has no checkbox`).toBeTruthy();

      return id![1];
    }
  }
  throw new Error(`the role form has no "${category} » ${name}" row`);
}

/** "Can User Login" is a per-user setting, listed apart from the grid above. */
export function loginPermissionId(form: string): string {
  const id = /Can User Login\s*<\/td>\s*<td>\s*<input[^>]*data-permission="(\d+)"/.exec(form);
  expect(id, 'the role form has no Can User Login row').toBeTruthy();

  return id![1];
}

/**
 * The "data access" drop downs on the role form: which records a role sees, whatever it
 * may do to them. Given the wording of an option, returns the key the form posts it under
 * and the option's value.
 */
export function dataAccessOption(form: string, label: string): { key: string; value: string } {
  const select = /<select data-permission-drop-down="([\d|]+)">([\s\S]*?)<\/select>/g;
  for (const [, key, options] of form.matchAll(select)) {
    for (const [, value, text] of options.matchAll(/<option value="(\d*)">([^<]*)<\/option>/g)) {
      if (text.trim() === label) {
        return { key, value };
      }
    }
  }
  throw new Error(`the role form has no data access option "${label}"`);
}

async function aRole(page: Page, roleName: string, grants: Grant[], dataAccess: string[]): Promise<string> {
  const form = await (await page.request.get('/?m[]=security&p[]=security_role&security_role_id=new')).text();
  const token = (form.match(/name="form_auth_key" value="([^"]+)"/) || [])[1];
  expect(token, 'the role form published no token').toBeTruthy();

  // Without this one the account answers "Account disabled" and cannot sign in at all.
  const permission: Record<string, Record<string, number>> = {
    [loginPermissionId(form)]: { view: 1 },
  };
  for (const grant of grants) {
    const id = permissionId(form, grant.category, grant.name);
    permission[id] = permission[id] || {};
    for (const action of grant.actions) {
      permission[id][action] = 1;
    }
  }

  const permission_drop_down: Record<string, string> = {};
  for (const label of dataAccess) {
    const option = dataAccessOption(form, label);
    permission_drop_down[option.key] = option.value;
  }

  const saved = await page.request.post('/?m[]=security', {
    form: {
      _process: 'save_security_role',
      security_role_id: 'new',
      name: roleName,
      form_auth_key: token,
      _redirect: '/',
      permission_data: JSON.stringify({ permission, permission_drop_down }),
    },
    failOnStatusCode: false,
    maxRedirects: 0,
  });
  const created = ((saved.headers()['location'] ?? '').match(/security_role_id=(\d+)/) || [])[1];
  expect(created, `the role ${roleName} was not created`).toBeTruthy();

  return created;
}

export type Restricted = {
  /** a browser page signed in as the restricted login */
  visitor: Page;
  /** the restricted login's own form token */
  token: () => Promise<string>;
  /**
   * Post a form as the restricted login, with a real token, without following the
   * redirect. Returns the status and where it pointed.
   */
  post: (url: string, body: Record<string, string>) => Promise<{ status: number; location: string; body: string }>;
  /** the ids of the staff login and role made for this run */
  userId: string;
  roleId: string;
  cleanup: () => Promise<void>;
};

/**
 * `page` is the signed-in administrator. Grants default to nothing beyond signing in;
 * `dataAccess` names the data access options to pick, by the wording the role form uses.
 */
export async function loginWith(
  page: Page,
  browser: Browser,
  label: string,
  grants: Grant[] = [],
  dataAccess: string[] = [],
): Promise<Restricted> {
  const stamp = Date.now() + '-' + Math.floor(Math.random() * 1000);
  const roleId = await aRole(page, `Restricted ${label} ${stamp}`, grants, dataAccess);
  const userId = await createRecord(page, '/?m[]=user', {
    _process: 'save_user',
    user_id: 'new',
    name: `Restricted ${label}`,
    email: `restricted-${label.toLowerCase().replace(/\W+/g, '-')}-${stamp}@example.com`,
    password_new: 'Restricted-' + label.replace(/\W+/g, '') + '-9142!',
    role_id: roleId,
    _redirect: '/',
  }, 'user_id');

  const context = await browser.newContext();
  const removeAll = async () => {
    await context.close();
    await deleteRecord(page, '/?m[]=user', 'save_user', 'user_id', userId);
    await deleteRecord(page, '/?m[]=security', 'save_security_role', 'security_role_id', roleId);
  };

  try {
    await page.goto('/?m[]=user&p[]=user_admin&user_id=' + userId);
    const link = await page.evaluate(() => {
      const a = Array.from(document.querySelectorAll('a[href*="auto_login="]'))[0] as HTMLAnchorElement | undefined;

      return a ? a.href : '';
    });
    expect(link, 'the user screen should offer an auto-login link').toContain('auto_login=' + userId);

    const visitor = await context.newPage();
    await visitor.goto(link);
    await visitor.waitForLoadState('load');
    await expect(
      visitor.locator('input[name="password"]'),
      'the restricted login did not sign in',
    ).toHaveCount(0);

    const token = async () => {
      const key = await visitor.evaluate(() => (window as any).ucm?.form_auth_key);
      if (key) {
        return String(key);
      }
      await visitor.goto('/');

      return String(await visitor.evaluate(() => (window as any).ucm?.form_auth_key));
    };
    expect(await token(), 'the restricted login was given no form token').toBeTruthy();

    return {
      visitor,
      token,
      userId,
      roleId,
      post: async (url, body) => {
        const response = await visitor.request.post(url, {
          form: { ...body, form_auth_key: await token() },
          failOnStatusCode: false,
          maxRedirects: 0,
        });

        return {
          status: response.status(),
          location: response.headers()['location'] || '',
          body: await response.text(),
        };
      },
      cleanup: removeAll,
    };
  } catch (error) {
    await removeAll();
    throw error;
  }
}

/**
 * Runs `attempt` and fails if any of `tables` is different afterwards.
 *
 * Whole-table checksums, so an edit to an existing row counts as much as a new one. The
 * login has already signed in by the time this is called, so the sign-in's own writes are
 * in the "before".
 */
export async function expectNoWrites(
  tables: string[],
  attempt: () => Promise<void>,
  what: string,
  /**
   * Reads to do first, where merely viewing a record writes. A job keeps figures cached on
   * its own row, worked out the first time each kind of viewer asks for it, so the first
   * look by a new login moves the table whatever it goes on to post.
   */
  warm?: () => Promise<unknown>,
): Promise<void> {
  if (warm) {
    await warm();
  }
  const before = await checksumTables(tables);
  await attempt();
  const after = await checksumTables(tables);
  for (const table of tables) {
    expect(after[table], `${what} changed ${table}`).toBe(before[table]);
  }
}

export { formAuthKey };

/**
 * The fields the app's own confirm-delete screen posts, so a spec can ask for a delete
 * without the round trip. See deleteRecord() in seed.ts for how the hash is made.
 */
export function deleteBody(process: string, key: string, id: string, button = 'butt_del'): Record<string, string> {
  return {
    _process: process,
    [key]: id,
    [button]: '1',
    _confirm_delete: createHash('md5').update(`delete ${key} ${id}`).digest('hex'),
    really_confirm_delete: 'yep',
    _redirect: '/',
  };
}
